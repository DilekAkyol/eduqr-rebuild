<?php

declare(strict_types=1);

namespace EduQR\Repositories;

use EduQR\Support\Database;
use PDO;

final class SessionRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function findByCourseId(int $courseId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM sessions WHERE course_id = :course_id ORDER BY created_at DESC");
        $stmt->execute(['course_id' => $courseId]);

        return $stmt->fetchAll() ?: [];
    }

    public function create(int $courseId, string $title, string $shortCode): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO sessions (course_id, title, short_code) VALUES (:course_id, :title, :short_code)"
        );
        $stmt->execute([
            'course_id'  => $courseId,
            'title'      => $title,
            'short_code' => $shortCode,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM sessions WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $session = $stmt->fetch();

        return $session ?: null;
    }

    public function findByShortCode(string $shortCode): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM sessions WHERE short_code = :short_code LIMIT 1");
        $stmt->execute(['short_code' => $shortCode]);
        $session = $stmt->fetch();

        return $session ?: null;
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare("UPDATE sessions SET status = :status WHERE id = :id");
        $stmt->execute([
            'status' => $status,
            'id'     => $id,
        ]);
    }
}
