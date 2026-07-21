<?php

declare(strict_types=1);

namespace EduQR\Middleware;

use EduQR\Support\Database;

final class RateLimitMiddleware
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_SECONDS = 300;

    public static function check(string $ip, string $action = 'login'): void
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            "SELECT COUNT(*) as cnt FROM login_attempts
             WHERE ip_hash = :ip_hash AND action = :action AND created_at > DATE_SUB(NOW(), INTERVAL :window SECOND)"
        );
        $stmt->execute([
            'ip_hash' => hash('sha256', $ip),
            'action' => $action,
            'window' => self::WINDOW_SECONDS,
        ]);
        $row = $stmt->fetch();

        if ((int)($row['cnt'] ?? 0) >= self::MAX_ATTEMPTS) {
            http_response_code(429);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Çok fazla deneme yaptınız. Lütfen 5 dakika bekleyin.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public static function record(string $ip, string $action = 'login', bool $succeeded = false): void
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            "INSERT INTO login_attempts (ip_hash, action, succeeded) VALUES (:ip_hash, :action, :succeeded)"
        );
        $stmt->execute([
            'ip_hash' => hash('sha256', $ip),
            'action' => $action,
            'succeeded' => $succeeded ? 1 : 0,
        ]);
    }
}
