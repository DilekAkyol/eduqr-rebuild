<?php

declare(strict_types=1);

namespace EduQR\Repositories;

use EduQR\Support\Database;
use PDO;

final class CourseRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function findByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM courses WHERE user_id = :user_id AND status = 'active' ORDER BY created_at DESC");
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll() ?: [];
    }

    public function updateStatus(int $id, int $userId, string $status): void
    {
        $stmt = $this->db->prepare(
            "UPDATE courses SET status = :status WHERE id = :id AND user_id = :user_id"
        );
        $stmt->execute([
            'status'  => $status,
            'id'      => $id,
            'user_id' => $userId,
        ]);
    }

    public function create(
        int $userId,
        string $title,
        string $code,
        ?string $term = null,
        ?string $description = null,
        string $defaultLanguage = 'tr',
        ?string $titleEn = null,
        ?string $descriptionEn = null
    ): int {
        $stmt = $this->db->prepare(
            "INSERT INTO courses (user_id, title, title_en, code, term, description, description_en, default_language) 
             VALUES (:user_id, :title, :title_en, :code, :term, :description, :description_en, :default_language)"
        );
        $stmt->execute([
            'user_id'          => $userId,
            'title'            => $title,
            'title_en'         => $titleEn,
            'code'             => $code,
            'term'             => $term,
            'description'      => $description,
            'description_en'   => $descriptionEn,
            'default_language' => $defaultLanguage,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findByIdAndUserId(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM courses WHERE id = :id AND user_id = :user_id LIMIT 1"
        );
        $stmt->execute([
            'id'      => $id,
            'user_id' => $userId,
        ]);
        $course = $stmt->fetch();

        return $course ?: null;
    }

    public function findArchivedByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM courses WHERE user_id = :user_id AND status = 'archived' ORDER BY created_at DESC");
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll() ?: [];
    }
}
