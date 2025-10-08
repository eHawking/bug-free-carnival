<?php
require_once __DIR__ . '/config.php';
session_start();

function db()
{
    static $pdo = null;
    if ($pdo) return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (Exception $e) {
        http_response_code(500);
        echo 'DB connection failed. Update admin/config.php and ensure DB exists.';
        exit;
    }
    return $pdo;
}
?>
