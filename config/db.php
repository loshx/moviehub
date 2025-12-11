<?php
// config/db.php
// Start session for authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$DB_PATH = __DIR__ . '/../data/movies.db';

if (!file_exists(dirname($DB_PATH))) {
    mkdir(dirname($DB_PATH), 0777, true);
}

try {
    $pdo = new PDO('sqlite:' . $DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
} catch (PDOException $e) {
    die('DB connection failed: ' . $e->getMessage());
}
