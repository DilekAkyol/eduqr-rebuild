<?php

declare(strict_types=1);

$envFile = __DIR__ . '/../.env';

if (!file_exists($envFile)) {
    echo "Error: .env file not found.\n";
    exit(1);
}

$envContent = file_get_contents($envFile);
if ($envContent === false) {
    echo "Error: Failed to read .env file.\n";
    exit(1);
}

// Generate a secure 32-byte secret (represented as 64 hex characters)
$newSecret = bin2hex(random_bytes(32));

// Match APP_SECRET=... using regex
$pattern = '/^(APP_SECRET=)(.*)$/m';

if (preg_match($pattern, $envContent)) {
    $envContent = preg_replace($pattern, 'APP_SECRET=' . $newSecret, $envContent);
} else {
    // If not found, append to the end of the file
    $envContent .= "\nAPP_SECRET=" . $newSecret . "\n";
}

if (file_put_contents($envFile, $envContent) === false) {
    echo "Error: Failed to write to .env file.\n";
    exit(1);
}

echo "Successfully rotated APP_SECRET in .env!\n";
exit(0);
