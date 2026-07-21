<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use EduQR\Config;
use EduQR\Support\Database;

Config::load(__DIR__ . '/../.env');

echo "eduQR End-to-End Smoke Test Diagnostic\n";
echo "======================================\n";

$failed = false;

// 1. Check PHP version and required extensions (NFR-10)
echo "Checking PHP Environment:\n";
$phpMin = '8.1.0';
if (version_compare(PHP_VERSION, $phpMin, '<')) {
    echo "  [FAIL] PHP version is " . PHP_VERSION . ". Expected >= {$phpMin}\n";
    $failed = true;
} else {
    echo "  [PASS] PHP version is " . PHP_VERSION . "\n";
}

$requiredExtensions = ['pdo_mysql', 'mbstring', 'gd', 'intl', 'json'];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        echo "  [FAIL] Required PHP extension '{$ext}' is NOT loaded.\n";
        $failed = true;
    } else {
        echo "  [PASS] PHP extension '{$ext}' is loaded.\n";
    }
}

// 2. Check Database connectivity
echo "\nChecking Database Connection:\n";
try {
    $db = Database::connect();
    $stmt = $db->query("SELECT VERSION()");
    $ver = $stmt->fetchColumn();
    echo "  [PASS] Database connected. Version: {$ver}\n";
} catch (Exception $e) {
    echo "  [FAIL] Database connection failed: " . $e->getMessage() . "\n";
    $failed = true;
}

// 3. Check Locale files
echo "\nChecking Localization Files:\n";
$localesDir = __DIR__ . '/../locales';
$enFile = $localesDir . '/en.json';

if (!file_exists($enFile)) {
    echo "  [FAIL] Reference translation file en.json is missing.\n";
    $failed = true;
} else {
    try {
        json_decode(file_get_contents($enFile), true, 512, JSON_THROW_ON_ERROR);
        echo "  [PASS] Reference file en.json is present and valid JSON.\n";
    } catch (Exception $e) {
        echo "  [FAIL] Reference file en.json failed to parse: " . $e->getMessage() . "\n";
        $failed = true;
    }
}

$localesStr = Config::get('APP_LOCALES', 'en,tr');
$locales = array_map('trim', explode(',', $localesStr));
foreach ($locales as $locale) {
    if ($locale === 'en') {
        continue;
    }
    $localeFile = $localesDir . '/' . $locale . '.json';
    if (!file_exists($localeFile)) {
        echo "  [FAIL] Configured locale file {$locale}.json is missing.\n";
        $failed = true;
    } else {
        try {
            json_decode(file_get_contents($localeFile), true, 512, JSON_THROW_ON_ERROR);
            echo "  [PASS] Locale file {$locale}.json is present and valid JSON.\n";
        } catch (Exception $e) {
            echo "  [FAIL] Locale file {$locale}.json failed to parse: " . $e->getMessage() . "\n";
            $failed = true;
        }
    }
}

echo "======================================\n";
if ($failed) {
    echo "SMOKE TEST RESULT: FAILED\n";
    exit(1);
} else {
    echo "SMOKE TEST RESULT: SUCCESS (All systems operational)\n";
    exit(0);
}
