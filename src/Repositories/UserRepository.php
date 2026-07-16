<?php

declare(strict_types=1);

namespace EduQR\Repositories;

use EduQR\Support\Database;
use PDO;

final class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function updateProfile(int $id, string $name, string $email): void
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET name = :name, email = :email WHERE id = :id"
        );
        $stmt->execute([
            'name'  => $name,
            'email' => $email,
            'id'    => $id,
        ]);
    }

    public function create(string $name, string $email, string $passwordHash, string $role = 'instructor', int $isVerified = 0, ?string $verificationCode = null, ?string $verificationExpiresAt = null): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO users (name, email, password_hash, role, is_verified, verification_code, verification_expires_at) 
             VALUES (:name, :email, :password_hash, :role, :is_verified, :verification_code, :verification_expires_at)"
        );
        $stmt->execute([
            'name'                    => $name,
            'email'                   => $email,
            'password_hash'           => $passwordHash,
            'role'                    => $role,
            'is_verified'             => $isVerified,
            'verification_code'       => $verificationCode,
            'verification_expires_at' => $verificationExpiresAt,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateVerificationCode(int $userId, ?string $code, ?string $expiresAt): void
    {
        $stmt = $this->db->prepare(
            "UPDATE users 
             SET verification_code = :code, verification_expires_at = :expires_at 
             WHERE id = :id"
        );
        $stmt->execute([
            'code'       => $code,
            'expires_at' => $expiresAt,
            'id'         => $userId,
        ]);
    }

    public function markAsVerified(int $userId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE users 
             SET is_verified = 1, verification_code = NULL, verification_expires_at = NULL 
             WHERE id = :id"
        );
        $stmt->execute(['id' => $userId]);
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
        $stmt = $this->db->prepare(
            "UPDATE users 
             SET password_hash = :password_hash, verification_code = NULL, verification_expires_at = NULL 
             WHERE id = :id"
        );
        $stmt->execute([
            'password_hash' => $passwordHash,
            'id'            => $userId,
        ]);
    }

    /**
     * Kullanıcıyı ve ona ait tüm verileri (kurslar, oturumlar, katılımcılar) siler.
     */
    public function deleteById(int $userId): void
    {
        // Önce kullanıcının kurslarına ait oturumların katılımcı ve cevaplarını sil
        $this->db->prepare(
            "DELETE a FROM answers a
             JOIN participants p ON a.participant_id = p.id
             JOIN sessions s ON p.session_id = s.id
             JOIN courses c ON s.course_id = c.id
             WHERE c.user_id = :user_id"
        )->execute(['user_id' => $userId]);

        $this->db->prepare(
            "DELETE p FROM participants p
             JOIN sessions s ON p.session_id = s.id
             JOIN courses c ON s.course_id = c.id
             WHERE c.user_id = :user_id"
        )->execute(['user_id' => $userId]);

        $this->db->prepare(
            "DELETE q FROM questions q
             JOIN sessions s ON q.session_id = s.id
             JOIN courses c ON s.course_id = c.id
             WHERE c.user_id = :user_id"
        )->execute(['user_id' => $userId]);

        $this->db->prepare(
            "DELETE s FROM sessions s
             JOIN courses c ON s.course_id = c.id
             WHERE c.user_id = :user_id"
        )->execute(['user_id' => $userId]);

        $this->db->prepare(
            "DELETE FROM courses WHERE user_id = :user_id"
        )->execute(['user_id' => $userId]);

        // Soru bankasını sil
        $this->db->prepare(
            "DELETE FROM question_bank WHERE user_id = :user_id"
        )->execute(['user_id' => $userId]);

        // Son olarak kullanıcıyı sil
        $this->db->prepare(
            "DELETE FROM users WHERE id = :id"
        )->execute(['id' => $userId]);
    }
}
