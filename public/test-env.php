<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';
\EduQR\Config::load(__DIR__ . '/../.env');

$host = \EduQR\Config::require('DB_HOST');
$port = \EduQR\Config::get('DB_PORT', '3306');
$db   = \EduQR\Config::require('DB_NAME');
$user = \EduQR\Config::require('DB_USER');
$pass = \EduQR\Config::get('DB_PASS', '');

$dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

echo "DSN: {$dsn}\n";
echo "USER: {$user}\n";
echo "PASS: '{$pass}' (length: " . strlen($pass) . ")\n";

try {
    $pdo = new PDO($dsn, $user, $pass);
    echo "SUCCESS!\n";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
