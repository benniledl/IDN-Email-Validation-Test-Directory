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
    $submissionController->create($flash['message'], $flash['type']);
    exit;
}

if ($method === 'GET' && $path === '/software') {
    $directoryController->softwareIndex();
    exit;
}

if ($method === 'GET' && preg_match('#^/software/(\d+)$#', $path, $matches) === 1) {
    $directoryController->softwareDetail((int)$matches[1]);
    exit;
}

if ($method === 'POST' && preg_match('#^/software/(\d+)/comments$#', $path, $matches) === 1) {
    $softwareId = (int)$matches[1];
    $flash = $directoryController->storeSoftwareComment($softwareId, $_POST);
    $directoryController->softwareDetail($softwareId, $flash['message'], $flash['type']);
    exit;
}

if ($method === 'GET' && preg_match('#^/reports/(\d+)$#', $path, $matches) === 1) {
    $directoryController->reportDetail((int)$matches[1]);
    exit;
}

if ($method === 'POST' && preg_match('#^/reports/(\d+)/comments$#', $path, $matches) === 1) {
    $reportId = (int)$matches[1];
    $flash = $directoryController->storeReportComment($reportId, $_POST);
    $directoryController->reportDetail($reportId, $flash['message'], $flash['type']);
    exit;
}

http_response_code(404);
View::render('not-found', ['resource' => 'Page']);
