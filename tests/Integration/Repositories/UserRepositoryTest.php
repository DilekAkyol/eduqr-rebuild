<?php
declare(strict_types=1);

namespace EduQR\Tests\Integration\Repositories;

use EduQR\Tests\IntegrationTestCase;
use EduQR\Repositories\UserRepository;

final class UserRepositoryTest extends IntegrationTestCase
{
    private ?UserRepository $repo = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new UserRepository();
    }

    public function test_user_crud_operations(): void
    {
        // 1. Create User
        $id = $this->repo->create(
            'Test Name',
            'test@example.com',
            'hashedpassword',
            'instructor',
            0,
            'CONF12',
            date('Y-m-d H:i:s', time() + 3600)
        );

        $this->assertGreaterThan(0, $id);

        // 2. Find by Email & ID
        $userByEmail = $this->repo->findByEmail('test@example.com');
        $this->assertNotNull($userByEmail);
        $this->assertEquals('Test Name', $userByEmail['name']);
        $this->assertEquals((int)$userByEmail['id'], $id);

        $userById = $this->repo->findById($id);
        $this->assertNotNull($userById);
        $this->assertEquals('test@example.com', $userById['email']);

        // 3. Update Profile
        $this->repo->updateProfile($id, 'Updated Name', 'updated@example.com');
        $updatedUser = $this->repo->findById($id);
        $this->assertEquals('Updated Name', $updatedUser['name']);
        $this->assertEquals('updated@example.com', $updatedUser['email']);

        // 4. Mark as Verified
        $this->repo->markAsVerified($id);
        $verifiedUser = $this->repo->findById($id);
        $this->assertEquals(1, (int)$verifiedUser['is_verified']);
        $this->assertNull($verifiedUser['verification_code']);

        // 5. Update Password
        $this->repo->updatePassword($id, 'newpasswordhash');
        $pwdUser = $this->repo->findById($id);
        $this->assertEquals('newpasswordhash', $pwdUser['password_hash']);

        // 6. Delete User
        $this->repo->deleteById($id);
        $this->assertNull($this->repo->findById($id));
    }
}
