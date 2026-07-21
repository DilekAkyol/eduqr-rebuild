<?php

declare(strict_types=1);

namespace EduQR\Middleware;

final class CsrfMiddleware
{
    public static function generate(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return $_SESSION['_csrf_token'] ?? '';
    }

    public static function validate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $token = $_POST['_csrf_token']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? '';

        if ($token === '' || !hash_equals($_SESSION['_csrf_token'] ?? '', $token)) {
            http_response_code(419);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'CSRF token doğrulaması başarısız. Lütfen sayfayı yenileyip tekrar deneyin.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Single-use: rotate after validation
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
}
