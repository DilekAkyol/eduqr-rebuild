<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use EduQR\Config;
use EduQR\Support\Database;

Config::load(__DIR__ . '/../.env');

try {
    $db = Database::connect();
    echo "Veritabanına bağlanıldı. Migrasyonlar başlatılıyor...\n";

    $migrationsDir = __DIR__ . '/../database/migrations';
    if (!is_dir($migrationsDir)) {
        throw new RuntimeException("Migrasyon dizini bulunamadı: {$migrationsDir}");
    }

    $files = glob($migrationsDir . '/*.sql');
    sort($files); // Dosyaları alfabetik sırayla çalıştır

    foreach ($files as $file) {
        $filename = basename($file);
        echo "Uygulanıyor: {$filename} ... ";

        $sql = file_get_contents($file);
        if ($sql === false) {
            echo "HATA (Dosya okunamadı!)\n";
            continue;
        }

        // Yorum satırlarını temizle ve sorguları ayır
        $queries = explode(';', $sql);

        try {
            foreach ($queries as $query) {
                $query = trim($query);
                if ($query === '') {
                    continue;
                }
                $db->exec($query);
            }
            echo "BAŞARILI\n";
        } catch (PDOException $e) {
            // MySQL 1061 (Index exists) veya 1060 (Duplicate column name) durumlarında geçmek için:
            if (in_array($e->errorInfo[1], [1060, 1061], true)) {
                echo "GEÇİLDİ (Daha önce uygulanmış veya kolon/index mevcut)\n";
            } else {
                echo "HATA!\n";
                throw $e;
            }
        }
    }

    echo "\nTüm migrasyonlar başarıyla uygulandı! 🎉\n";

} catch (Throwable $e) {
    echo "\nFATAL HATA: " . $e->getMessage() . "\n";
    exit(1);
}
