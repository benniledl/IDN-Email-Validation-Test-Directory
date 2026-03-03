<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Support/View.php';
require_once __DIR__ . '/../src/Service/SeverityCalculator.php';
require_once __DIR__ . '/../src/Repository/TemplateEmailRepository.php';
require_once __DIR__ . '/../src/Repository/SubmissionRepository.php';
require_once __DIR__ . '/../src/Controller/HomeController.php';
require_once __DIR__ . '/../src/Controller/SubmissionController.php';

$pdo = require __DIR__ . '/../config/database.php';

$templateRepository = new TemplateEmailRepository($pdo);
$submissionRepository = new SubmissionRepository($pdo);
$severityCalculator = new SeverityCalculator();

$homeController = new HomeController($templateRepository, $submissionRepository);
$submissionController = new SubmissionController($templateRepository, $submissionRepository, $severityCalculator);

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($method === 'GET' && $path === '/') {
    $homeController->index();
    exit;
}

if ($method === 'POST' && $path === '/submissions') {
    $flash = $submissionController->store($_POST);
    $homeController->index($flash);
    exit;
}

http_response_code(404);
echo 'Not found';
