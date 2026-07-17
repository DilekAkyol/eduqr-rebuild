<?php

declare(strict_types=1);

namespace EduQR\Repositories;

use EduQR\Support\Database;
use PDO;

final class QuestionBankRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function findByUserId(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM question_bank WHERE user_id = :user_id ORDER BY created_at DESC"
        );
        $stmt->execute(['user_id' => $userId]);
        $rows = $stmt->fetchAll() ?: [];

        foreach ($rows as &$row) {
            if (isset($row['options']) && is_string($row['options'])) {
                $row['options'] = json_decode($row['options'], true);
            }
        }

        return $rows;
    }

    public function create(
        int $userId,
        string $questionText,
        string $type = 'multiple_choice',
        ?array $options = null,
        ?string $correctAnswer = null,
        ?string $sourceTitle = null
    ): int {
        $stmt = $this->db->prepare(
            "INSERT INTO question_bank (user_id, source_title, question_text, type, options, correct_answer)
             VALUES (:user_id, :source_title, :question_text, :type, :options, :correct_answer)"
        );
        $stmt->execute([
            'user_id'       => $userId,
            'source_title'  => $sourceTitle,
            'question_text' => $questionText,
            'type'          => $type,
            'options'       => $options !== null ? json_encode($options, JSON_UNESCAPED_UNICODE) : null,
            'correct_answer'=> $correctAnswer,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findByIds(array $ids, int $userId): array
    {
        if (empty($ids)) return [];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            "SELECT * FROM question_bank WHERE id IN ($placeholders) AND user_id = ?"
        );
        $stmt->execute([...$ids, $userId]);
        $rows = $stmt->fetchAll() ?: [];

        foreach ($rows as &$row) {
            if (isset($row['options']) && is_string($row['options'])) {
                $row['options'] = json_decode($row['options'], true);
            }
        }

        return $rows;
    }

    public function deleteById(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM question_bank WHERE id = :id AND user_id = :user_id"
        );
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public function findById(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM question_bank WHERE id = :id AND user_id = :user_id LIMIT 1"
        );
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $row = $stmt->fetch();
        if ($row) {
            if (isset($row['options']) && is_string($row['options'])) {
                $row['options'] = json_decode($row['options'], true);
            }
            return $row;
        }
        return null;
    }

    public function update(
        int $id,
        int $userId,
        string $questionText,
        string $type,
        ?array $options = null,
        ?string $correctAnswer = null,
        ?string $sourceTitle = null
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE question_bank 
             SET question_text = :question_text, 
                 type = :type, 
                 options = :options, 
                 correct_answer = :correct_answer, 
                 source_title = :source_title
             WHERE id = :id AND user_id = :user_id"
        );
        return $stmt->execute([
            'id'            => $id,
            'user_id'       => $userId,
            'question_text' => $questionText,
            'type'          => $type,
            'options'       => $options !== null ? json_encode($options, JSON_UNESCAPED_UNICODE) : null,
            'correct_answer'=> $correctAnswer,
            'source_title'  => $sourceTitle,
        ]);
    }

    public function countByUserId(int $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM question_bank WHERE user_id = :user_id"
        );
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }
}
