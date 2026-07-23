<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use EduQR\Config;

// Dynamically generate .env.testing from .env if it does not exist
$envPath = __DIR__ . '/../.env';
$testingEnvPath = __DIR__ . '/../.env.testing';

if (!file_exists($testingEnvPath)) {
    if (!file_exists($envPath)) {
        // Fallback minimal configuration for testing environments where .env is completely missing
        $fallbackEnv = "DB_HOST=127.0.0.1\nDB_PORT=3306\nDB_NAME=eduqr_rebuild_test\nDB_USER=root\nDB_PASS=\nAPP_SECRET=testing_secret\n";
        file_put_contents($testingEnvPath, $fallbackEnv);
    } else {
        $envContent = file_get_contents($envPath);
        // Replace DB_NAME with testing db
        $envContent = preg_replace('/DB_NAME\s*=\s*.*/', 'DB_NAME=eduqr_rebuild_test', $envContent);
        file_put_contents($testingEnvPath, $envContent);
    }
}

Config::load($testingEnvPath);

// Initialize i18n for translations helper t() in tests
\EduQR\I18n\I18nService::init();

// Set up the testing database schema
try {
    $host = Config::require('DB_HOST');
    $port = Config::get('DB_PORT', '3306');
    $user = Config::require('DB_USER');
    $pass = Config::get('DB_PASS', '');

    // Connect to MySQL server first (without database target)
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `eduqr_rebuild_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

    // Connect to the newly created database and run migrations
    $pdo->exec("USE `eduqr_rebuild_test`;");

    $migrationsDir = __DIR__ . '/../database/migrations';
    if (is_dir($migrationsDir)) {
        $files = glob($migrationsDir . '/*.sql');
        sort($files);
        foreach ($files as $file) {
            $sql = file_get_contents($file);
            if ($sql === false) {
                continue;
            }
            $queries = explode(';', $sql);
            foreach ($queries as $query) {
                $query = trim($query);
                if ($query === '') {
                    continue;
                }
                try {
                    $pdo->exec($query);
                } catch (PDOException $e) {
                    // Skip table already exists, duplicate column, duplicate index, or non-existent index drop errors to keep bootstrapping idempotent
                    if (!in_array($e->errorInfo[1], [1050, 1060, 1061, 1091], true)) {
                        throw $e;
                    }
                }
            }
        }
    }
} catch (Throwable $e) {
    fwrite(STDERR, "TEST BOOTSTRAP ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
