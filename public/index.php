<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Support/View.php';
require_once __DIR__ . '/../src/Support/Router.php';
require_once __DIR__ . '/../src/Support/Request.php';
require_once __DIR__ . '/../src/Support/RateLimiter.php';
require_once __DIR__ . '/../src/EmailValidator.php';
require_once __DIR__ . '/../src/Service/SeverityCalculator.php';
require_once __DIR__ . '/../src/Service/WordPressPluginService.php';
require_once __DIR__ . '/../src/Repository/TemplateEmailRepository.php';
require_once __DIR__ . '/../src/Repository/SubmissionRepository.php';
require_once __DIR__ . '/../src/Repository/DirectoryRepository.php';
require_once __DIR__ . '/../src/Repository/CommentRepository.php';
require_once __DIR__ . '/../src/Repository/AdminUserRepository.php';
require_once __DIR__ . '/../src/Security/AdminSessionService.php';
require_once __DIR__ . '/../src/Security/CommentSpamService.php';
require_once __DIR__ . '/../src/Controller/HomeController.php';
require_once __DIR__ . '/../src/Controller/SubmissionController.php';
require_once __DIR__ . '/../src/Controller/DirectoryController.php';

session_start();

$pdo = require __DIR__ . '/../config/database.php';

$templateRepository = new TemplateEmailRepository($pdo);
$submissionRepository = new SubmissionRepository($pdo);
$directoryRepository = new DirectoryRepository($pdo);
$commentRepository = new CommentRepository($pdo);
$adminUserRepository = new AdminUserRepository($pdo);
$severityCalculator = new SeverityCalculator();
$wordPressPluginService = new WordPressPluginService();
$emailValidator = new EmailValidator();
$rateLimiter = new RateLimiter();
$adminSessionService = new AdminSessionService($adminUserRepository);
$commentSpamService = new CommentSpamService();

$homeController = new HomeController($directoryRepository);
$submissionController = new SubmissionController(
    $templateRepository,
    $submissionRepository,
    $directoryRepository,
    $severityCalculator,
    $wordPressPluginService,
    $emailValidator,
    $rateLimiter
);
$directoryController = new DirectoryController(
    $directoryRepository,
    $commentRepository,
    $adminUserRepository,
    $adminSessionService,
    $commentSpamService,
    $wordPressPluginService
);

$request = Request::fromGlobals();
$method = $request->method();
$path = $request->path();
$query = $request->queryAll();
$post = $request->postAll();

$router = new Router();

$router->add('GET', '/', function () use ($homeController): void {
    $homeController->index();
});

$router->add('GET', '/about', function () use ($homeController): void {
    $homeController->about();
});

$router->add('GET', '/impressum', function () use ($homeController): void {
    $homeController->imprint();
});

$router->add('GET', '/datenschutz', function () use ($homeController): void {
    $homeController->privacy();
});

$router->add('GET', '/submit-report', function () use ($submissionController): void {
    $submissionController->create();
});

$router->add('POST', '/submissions', function () use ($submissionController, $post): void {
    $flash = $submissionController->store($post);
    if (($flash['type'] ?? 'info') === 'success' && !empty($flash['submission_id'])) {
        header('Location: /reports/' . (int)$flash['submission_id']);
        return;
    }

    $submissionController->create($flash['message'], $flash['type'], $post);
});

$router->add('POST', '/api/validate-email', function () use ($submissionController, $post): void {
    $submissionController->validateEmailApi($post);
});

$router->add('GET', '/api/plugin-version', function () use ($submissionController, $query): void {
    $submissionController->pluginVersionApi($query);
});

$router->add('GET', '/api/plugin-slug-suggestions', function () use ($submissionController, $query): void {
    $submissionController->pluginSlugSuggestionsApi($query);
});

$router->add('GET', '/software', function () use ($directoryController, $query): void {
    $directoryController->softwareIndex((string)($query['q'] ?? ''));
});

$router->add('GET', '/reports', function () use ($directoryController, $query): void {
    $directoryController->reportsIndex((string)($query['q'] ?? ''), (string)($query['severity'] ?? ''));
});

$router->add('GET', '/admin/login', function () use ($directoryController): void {
    $directoryController->adminLoginPage();
});

$router->add('GET', '/admin', function () use ($directoryController): void {
    $directoryController->adminPanel();
});

$router->add('POST', '/admin/login', function () use ($directoryController, $post): void {
    $flash = $directoryController->adminLogin($post);
    if (($flash['type'] ?? 'danger') === 'success') {
        $directoryController->adminPanel($flash['message'], $flash['type']);
        return;
    }

    $directoryController->adminLoginPage($flash['message'], $flash['type'], $post);
});

$router->add('POST', '/admin/logout', function () use ($directoryController, $post): void {
    $flash = $directoryController->adminLogout($post);
    $directoryController->adminLoginPage($flash['message'], $flash['type']);
});

$router->addRegex('GET', '#^/software/(\d+)$#', function (array $matches) use ($directoryController): void {
    $directoryController->softwareDetail((int)$matches[1]);
});

$router->addRegex('POST', '#^/software/(\d+)/comments$#', function (array $matches) use ($directoryController, $post): void {
    $softwareId = (int)$matches[1];
    $flash = $directoryController->storeSoftwareComment($softwareId, $post);
    $directoryController->softwareDetail($softwareId, $flash['message'], $flash['type'], $post);
});

$router->addRegex('POST', '#^/software/(\d+)/admin/solution$#', function (array $matches) use ($directoryController, $post): void {
    $softwareId = (int)$matches[1];
    $flash = $directoryController->adminAddSoftwareSolution($softwareId, $post);
    $directoryController->softwareDetail($softwareId, $flash['message'], $flash['type'], $post);
});

$router->addRegex('POST', '#^/software/(\d+)/comments/(\d+)/hide$#', function (array $matches) use ($directoryController, $post): void {
    $softwareId = (int)$matches[1];
    $flash = $directoryController->adminHideSoftwareComment((int)$matches[2], $post);
    $directoryController->softwareDetail($softwareId, $flash['message'], $flash['type'], $post);
});

$router->addRegex('POST', '#^/software/(\d+)/admin/hide$#', function (array $matches) use ($directoryController, $post): void {
    $softwareId = (int)$matches[1];
    $flash = $directoryController->adminHideCustomSoftware($softwareId, $post);
    $directoryController->softwareIndex('', $flash['message'], $flash['type']);
});

$router->add('POST', '/admin/users', function () use ($directoryController, $post): void {
    $flash = $directoryController->adminAddUser($post);
    $directoryController->adminPanel($flash['message'], $flash['type'], $post);
});

$router->add('POST', '/admin/users/password', function () use ($directoryController, $post): void {
    $flash = $directoryController->adminResetUserPassword($post);
    if (($flash['type'] ?? 'danger') === 'success') {
        $directoryController->adminPanel($flash['message'], $flash['type']);
        return;
    }

    $directoryController->adminPanel($flash['message'], $flash['type'], $post);
});

$router->add('POST', '/admin/users/status', function () use ($directoryController, $post): void {
    $flash = $directoryController->adminSetUserStatus($post);
    $directoryController->adminPanel($flash['message'], $flash['type']);
});

$router->add('POST', '/admin/users/delete', function () use ($directoryController, $post): void {
    $flash = $directoryController->adminDeleteUser($post);
    $directoryController->adminPanel($flash['message'], $flash['type']);
});

$router->addRegex('GET', '#^/reports/(\d+)$#', function (array $matches) use ($directoryController): void {
    $directoryController->reportDetail((int)$matches[1]);
});

$router->addRegex('POST', '#^/reports/(\d+)/comments$#', function (array $matches) use ($directoryController, $post): void {
    $reportId = (int)$matches[1];
    $flash = $directoryController->storeReportComment($reportId, $post);
    $directoryController->reportDetail($reportId, $flash['message'], $flash['type'], $post);
});

$router->addRegex('POST', '#^/reports/(\d+)/admin/hide$#', function (array $matches) use ($directoryController, $post): void {
    $reportId = (int)$matches[1];
    $flash = $directoryController->adminHideSubmission($reportId, $post);
    $redirectSoftware = (int)($post['software_id'] ?? 0);
    if ($redirectSoftware > 0) {
        $directoryController->softwareDetail($redirectSoftware, $flash['message'], $flash['type'], $post);
        return;
    }

    $directoryController->reportDetail($reportId, $flash['message'], $flash['type'], $post);
});

$router->addRegex('POST', '#^/reports/(\d+)/admin/severity$#', function (array $matches) use ($directoryController, $post): void {
    $reportId = (int)$matches[1];
    $flash = $directoryController->adminOverrideSeverity($reportId, $post);
    $directoryController->reportDetail($reportId, $flash['message'], $flash['type'], $post);
});

$router->addRegex('POST', '#^/reports/(\d+)/comments/(\d+)/hide$#', function (array $matches) use ($directoryController, $post): void {
    $reportId = (int)$matches[1];
    $flash = $directoryController->adminHideReportComment((int)$matches[2], $post);
    $directoryController->reportDetail($reportId, $flash['message'], $flash['type'], $post);
});

if ($router->dispatch($method, $path)) {
    exit;
}

http_response_code(404);
View::render('not-found', ['resource' => 'Page']);
