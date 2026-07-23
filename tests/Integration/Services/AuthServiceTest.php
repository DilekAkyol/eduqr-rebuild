<?php
declare(strict_types=1);

namespace EduQR\Tests\Integration\Services;

use EduQR\Tests\IntegrationTestCase;
use EduQR\Services\AuthService;
use EduQR\Repositories\UserRepository;

final class AuthServiceTest extends IntegrationTestCase
{
    private ?AuthService $authService = null;
    private ?UserRepository $userRepo = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
        $this->userRepo = new UserRepository();

        // Ensure session superglobal is clean
        $_SESSION = [];
    }

    public function test_unsuccessful_login_returns_false(): void
    {
        $this->assertFalse($this->authService->login('nonexistent@example.com', 'pwd'));
    }

    public function test_login_unverified_throws_exception(): void
    {
        $hashed = password_hash('correctpassword', PASSWORD_BCRYPT, ['cost' => 12]);
        $this->userRepo->create('Unverified User', 'unverified@example.com', $hashed, 'instructor', 0);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('unverified');

        $this->authService->login('unverified@example.com', 'correctpassword');
    }

    public function test_successful_login_and_logout(): void
    {
        $hashed = password_hash('securepass123', PASSWORD_BCRYPT, ['cost' => 12]);
        $userId = $this->userRepo->create('Verified User', 'verified@example.com', $hashed, 'instructor', 1);

        // Perform successful login
        $result = $this->authService->login('verified@example.com', 'securepass123');
        $this->assertTrue($result);
        $this->assertTrue(AuthService::check());

        $user = AuthService::user();
        $this->assertNotNull($user);
        $this->assertEquals($userId, (int)$user['id']);
        $this->assertEquals('Verified User', $user['name']);
        $this->assertEquals('instructor', $user['role']);

        // Perform logout
        $this->authService->logout();
        $this->assertFalse(AuthService::check());
        $this->assertNull(AuthService::user());
    }
}
