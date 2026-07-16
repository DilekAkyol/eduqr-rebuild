<?php

declare(strict_types=1);

namespace EduQR\Services;

use EduQR\Repositories\UserRepository;

final class AuthService
{
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
    }

    public function login(string $email, string $password): bool
    {
        $user = $this->userRepo->findByEmail($email);
        if ($user === null) {
            return false;
        }

        if (password_verify($password, $user['password_hash'])) {
            if (isset($user['is_verified']) && (int)$user['is_verified'] === 0) {
                throw new \RuntimeException('unverified');
            }

            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            return true;
        }

        return false;
    }

    public function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];
        session_destroy();
    }

    public static function check(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return isset($_SESSION['user_id']);
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return [
            'id'   => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'role' => $_SESSION['user_role'],
        ];
    }
}
