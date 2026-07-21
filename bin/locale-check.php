<?php

declare(strict_types=1);

if ($argc < 2) {
    echo "Usage: php bin/locale-check.php <code>\n";
    exit(1);
}

$code = $argv[1];
$localesDir = __DIR__ . '/../locales';
$enFile = $localesDir . '/en.json';
$targetFile = $localesDir . '/' . $code . '.json';

if (!file_exists($enFile)) {
    echo "Error: Reference file en.json not found.\n";
    exit(1);
}

if (!file_exists($targetFile)) {
    echo "Error: Target locale file {$code}.json not found.\n";
    exit(1);
}

try {
    $enData = json_decode(file_get_contents($enFile), true, 512, JSON_THROW_ON_ERROR);
    $targetData = json_decode(file_get_contents($targetFile), true, 512, JSON_THROW_ON_ERROR);
} catch (Exception $e) {
    echo "Error: Failed to parse JSON file(s). " . $e->getMessage() . "\n";
    exit(1);
}

$totalKeys = count($enData);
if ($totalKeys === 0) {
    echo "Error: Reference file en.json is empty.\n";
    exit(1);
}

$missingKeys = [];
$emptyKeys = [];
$translatedKeysCount = 0;

foreach ($enData as $key => $val) {
    if (!array_key_exists($key, $targetData)) {
        $missingKeys[] = $key;
    } elseif (trim((string)$targetData[$key]) === '') {
        $emptyKeys[] = $key;
    } else {
        $translatedKeysCount++;
    }
}

$extraKeys = [];
foreach ($targetData as $key => $val) {
    if (!array_key_exists($key, $enData)) {
        $extraKeys[] = $key;
    }
}

$coverage = ($translatedKeysCount / $totalKeys) * 100;

echo "Translation Coverage Report for [{$code}]:\n";
echo "----------------------------------------\n";
echo "Total reference keys (en.json): {$totalKeys}\n";
echo "Translated and non-empty keys: {$translatedKeysCount}\n";
printf("Coverage: %.2f%%\n", $coverage);

if (!empty($missingKeys)) {
    echo "\n[ERROR] Missing keys (" . count($missingKeys) . "):\n";
    foreach ($missingKeys as $key) {
        echo "  - {$key}\n";
    }
}

if (!empty($emptyKeys)) {
    echo "\n[ERROR] Keys with empty translations (" . count($emptyKeys) . "):\n";
    foreach ($emptyKeys as $key) {
        echo "  - {$key}\n";
    }
}

if (!empty($extraKeys)) {
    echo "\n[WARNING] Extra keys in {$code}.json not present in en.json (" . count($extraKeys) . "):\n";
    foreach ($extraKeys as $key) {
        echo "  - {$key}\n";
    }
}

echo "----------------------------------------\n";
if ($coverage >= 95.0) {
    echo "SUCCESS: Coverage is >= 95% (Passed)\n";
    exit(0);
} else {
    echo "FAILED: Coverage is < 95% (Failed)\n";
    exit(1);
}
