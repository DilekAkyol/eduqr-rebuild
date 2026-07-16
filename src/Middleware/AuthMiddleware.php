<?php

declare(strict_types=1);

namespace EduQR\Middleware;

use EduQR\Services\AuthService;

final class AuthMiddleware
{
    public static function handle(): void
    {
        if (!AuthService::check()) {
            http_response_code(302);
            header('Location: ' . eduqr_path('/login'));
            exit;
        }
    }
}
