<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'vtasis_db');
define('DB_USER', 'root');
define('DB_PASS', '');
defined('BASE_URL') || define('BASE_URL', '/vtasis/');

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    die('<div style="font-family:sans-serif;padding:2rem;color:#c0392b;"><h2>Database Connection Failed</h2><p>' . htmlspecialchars($e->getMessage()) . '</p></div>');
}
