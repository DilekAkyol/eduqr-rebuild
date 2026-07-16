<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use EduQR\Config;
use EduQR\Repositories\UserRepository;

Config::load(__DIR__ . '/../.env');

$name = $argv[1] ?? "Stajyer Admin";
$email = $argv[2] ?? "admin@eduqr.local";
$password = $argv[3] ?? "Admin1234!";

if ($argc < 4 && $argc > 1) {
    echo "Kullanım: php bin/user-add.php \"[Ad Soyad]\" [E-posta] [Şifre]\n";
    echo "Örnek: php bin/user-add.php \"Ahmet Yilmaz\" \"ahmet@domain.com\" \"Password123!\"\n\n";
}

try {
    $userRepo = new UserRepository();

    // E-posta ile önceden eklenmiş mi kontrol et
    $existing = $userRepo->findByEmail($email);
    if ($existing !== null) {
        echo "HATA: '{$email}' e-postasına sahip bir kullanıcı zaten mevcut.\n";
        exit(0);
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $userId = $userRepo->create($name, $email, $passwordHash, 'admin');

    echo "Kullanıcı başarıyla oluşturuldu! 🎉\n";
    echo "ID: {$userId}\n";
    echo "E-posta: {$email}\n";
    echo "Şifre: {$password}\n";

} catch (Throwable $e) {
    echo "HATA: " . $e->getMessage() . "\n";
    exit(1);
}
