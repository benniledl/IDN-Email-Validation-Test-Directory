<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Support/View.php';
require_once __DIR__ . '/../src/Service/SeverityCalculator.php';
require_once __DIR__ . '/../src/Service/WordPressPluginService.php';
require_once __DIR__ . '/../src/Repository/TemplateEmailRepository.php';
require_once __DIR__ . '/../src/Repository/SubmissionRepository.php';
require_once __DIR__ . '/../src/Controller/HomeController.php';
require_once __DIR__ . '/../src/Controller/SubmissionController.php';
require_once __DIR__ . '/../src/Controller/DirectoryController.php';

session_start();

$pdo = require __DIR__ . '/../config/database.php';

$templateRepository = new TemplateEmailRepository($pdo);
$submissionRepository = new SubmissionRepository($pdo);
$severityCalculator = new SeverityCalculator();
$wordPressPluginService = new WordPressPluginService();

$homeController = new HomeController($submissionRepository);
$submissionController = new SubmissionController($templateRepository, $submissionRepository, $severityCalculator, $wordPressPluginService);
$directoryController = new DirectoryController($submissionRepository, $wordPressPluginService);

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($method === 'GET' && $path === '/') {
    $homeController->index();
    exit;
}

if ($method === 'GET' && $path === '/submit-report') {
    $submissionController->create();
    exit;
}

if ($method === 'POST' && $path === '/submissions') {
    $flash = $submissionController->store($_POST);

    if (($flash['type'] ?? 'info') === 'success' && !empty($flash['submission_id'])) {
        header('Location: /reports/' . (int)$flash['submission_id']);
        exit;
    }

    $submissionController->create($flash['message'], $flash['type'], $_POST);
    exit;
}

if ($method === 'GET' && $path === '/software') {
    $directoryController->softwareIndex();
    exit;
}

if ($method === 'GET' && $path === '/admin/login') {
    $directoryController->adminLoginPage();
    exit;
}

if ($method === 'GET' && $path === '/admin') {
    $directoryController->adminPanel();
    exit;
}

if ($method === 'POST' && $path === '/admin/login') {
    $flash = $directoryController->adminLogin($_POST);
    if (($flash['type'] ?? 'danger') === 'success') {
        $directoryController->adminPanel($flash['message'], $flash['type']);
        exit;
    }

    $directoryController->adminLoginPage($flash['message'], $flash['type'], $_POST);
    exit;
}

if ($method === 'POST' && $path === '/admin/logout') {
    $flash = $directoryController->adminLogout($_POST);
    $directoryController->adminLoginPage($flash['message'], $flash['type']);
    exit;
}

if ($method === 'GET' && preg_match('#^/software/(\d+)$#', $path, $matches) === 1) {
    $directoryController->softwareDetail((int)$matches[1]);
    exit;
}

if ($method === 'POST' && preg_match('#^/software/(\d+)/comments$#', $path, $matches) === 1) {
    $softwareId = (int)$matches[1];
    $flash = $directoryController->storeSoftwareComment($softwareId, $_POST);
    $directoryController->softwareDetail($softwareId, $flash['message'], $flash['type'], $_POST);
    exit;
}

if ($method === 'POST' && preg_match('#^/software/(\d+)/admin/solution$#', $path, $matches) === 1) {
    $softwareId = (int)$matches[1];
    $flash = $directoryController->adminAddSoftwareSolution($softwareId, $_POST);
    $directoryController->softwareDetail($softwareId, $flash['message'], $flash['type'], $_POST);
    exit;
}

if ($method === 'POST' && preg_match('#^/software/(\d+)/comments/(\d+)/hide$#', $path, $matches) === 1) {
    $softwareId = (int)$matches[1];
    $flash = $directoryController->adminHideSoftwareComment((int)$matches[2]);
    $directoryController->softwareDetail($softwareId, $flash['message'], $flash['type'], $_POST);
    exit;
}


if ($method === 'POST' && preg_match('#^/software/(\d+)/admin/hide$#', $path, $matches) === 1) {
    $softwareId = (int)$matches[1];
    $flash = $directoryController->adminHideCustomSoftware($softwareId);
    $directoryController->softwareIndex($flash['message'], $flash['type']);
    exit;
}

if ($method === 'POST' && $path === '/admin/users') {
    $flash = $directoryController->adminAddUser($_POST);
    $directoryController->adminPanel($flash['message'], $flash['type'], $_POST);
    exit;
}

if ($method === 'POST' && $path === '/admin/users/password') {
    $flash = $directoryController->adminResetUserPassword($_POST);
    $directoryController->adminPanel($flash['message'], $flash['type'], $_POST);
    exit;
}

if ($method === 'POST' && $path === '/admin/users/status') {
    $flash = $directoryController->adminSetUserStatus($_POST);
    $directoryController->adminPanel($flash['message'], $flash['type'], $_POST);
    exit;
}

if ($method === 'GET' && preg_match('#^/reports/(\d+)$#', $path, $matches) === 1) {
    $directoryController->reportDetail((int)$matches[1]);
    exit;
}

if ($method === 'POST' && preg_match('#^/reports/(\d+)/comments$#', $path, $matches) === 1) {
    $reportId = (int)$matches[1];
    $flash = $directoryController->storeReportComment($reportId, $_POST);
    $directoryController->reportDetail($reportId, $flash['message'], $flash['type'], $_POST);
    exit;
}

if ($method === 'POST' && preg_match('#^/reports/(\d+)/admin/hide$#', $path, $matches) === 1) {
    $reportId = (int)$matches[1];
    $flash = $directoryController->adminHideSubmission($reportId);
    $redirectSoftware = isset($_POST['software_id']) ? (int)$_POST['software_id'] : 0;
    if ($redirectSoftware > 0) {
        $directoryController->softwareDetail($redirectSoftware, $flash['message'], $flash['type'], $_POST);
        exit;
    }

    $directoryController->reportDetail($reportId, $flash['message'], $flash['type'], $_POST);
    exit;
}

if ($method === 'POST' && preg_match('#^/reports/(\d+)/admin/severity$#', $path, $matches) === 1) {
    $reportId = (int)$matches[1];
    $flash = $directoryController->adminOverrideSeverity($reportId, $_POST);
    $directoryController->reportDetail($reportId, $flash['message'], $flash['type'], $_POST);
    exit;
}

if ($method === 'POST' && preg_match('#^/reports/(\d+)/comments/(\d+)/hide$#', $path, $matches) === 1) {
    $reportId = (int)$matches[1];
    $flash = $directoryController->adminHideReportComment((int)$matches[2]);
    $directoryController->reportDetail($reportId, $flash['message'], $flash['type'], $_POST);
    exit;
}

http_response_code(404);
View::render('not-found', ['resource' => 'Page']);
