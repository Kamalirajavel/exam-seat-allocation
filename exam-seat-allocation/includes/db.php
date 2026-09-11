<?php
// =====================================================================
// Database connection (PDO / MySQL)
// Default XAMPP settings - change if your MySQL setup is different.
// =====================================================================

$DB_HOST = 'localhost';
$DB_NAME = 'exam_seat_db';
$DB_USER = 'root';
$DB_PASS = '';

// --- Optional SQLite fallback for quick testing without MySQL/XAMPP ---
// If a file named USE_SQLITE exists in the project root, the app will
// use a local SQLite database instead of MySQL. This is only meant for
// development/testing convenience and is NOT required for normal use.
$useSqlite = file_exists(__DIR__ . '/../USE_SQLITE');

try {
    if ($useSqlite) {
        $pdo = new PDO('sqlite:' . __DIR__ . '/../data/exam_seat_dev.sqlite');
        $pdo->exec('PRAGMA foreign_keys = ON;');
    } else {
        $dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";
        $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed. Please make sure MySQL is running in XAMPP "
        . "and that you have imported sql/schema.sql. (" . $e->getMessage() . ")");
}
