<?php

declare(strict_types=1);

$databasePath = __DIR__ . '/../database/app.sqlite';
$dsn = 'sqlite:' . $databasePath;

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$pdo = new PDO($dsn, null, null, $options);
$pdo->exec('PRAGMA foreign_keys = ON');

return $pdo;
