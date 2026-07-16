<?php

declare(strict_types=1);

namespace EduQR\Support;

use EduQR\Config;
use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connect(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $host = Config::require('DB_HOST');
        $port = Config::get('DB_PORT', '3306');
        $db   = Config::require('DB_NAME');
        $user = Config::require('DB_USER');
        $pass = Config::get('DB_PASS', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // SQL enjeksiyon koruması için önemli
        ];

        self::$pdo = new PDO($dsn, $user, $pass, $options);

        return self::$pdo;
    }
}
