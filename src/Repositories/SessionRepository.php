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
        $now = date('Y-m-d H:i:s');
        $setClause = "status = :status";
        if ($status === 'active') {
            $setClause .= ", started_at = COALESCE(started_at, :now)";
        } elseif ($status === 'paused') {
            $setClause .= ", paused_at = :now";
        } elseif ($status === 'closed') {
            $setClause .= ", closed_at = :now";
        }
        $stmt = $this->db->prepare("UPDATE sessions SET {$setClause} WHERE id = :id");
        $params = ['status' => $status, 'id' => $id];
        if ($status === 'active' || $status === 'paused' || $status === 'closed') {
            $params['now'] = $now;
        }
        $stmt->execute($params);
    }

    public function setAnonymized(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE sessions SET is_anonymized = 1 WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function updateShowResults(int $id, int $val): void
    {
        $stmt = $this->db->prepare("UPDATE sessions SET show_results_to_students = :val WHERE id = :id");
        $stmt->execute([
            'val' => $val,
            'id'  => $id,
        ]);
    }
}
