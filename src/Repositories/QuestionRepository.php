<?php

declare(strict_types=1);

namespace EduQR\Repositories;

use EduQR\Support\Database;
use PDO;

final class QuestionRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function create(int $sessionId, string $text, string $type, ?array $options = null, ?string $correctAnswer = null): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO questions (session_id, question_text, type, options, correct_answer, status) 
             VALUES (:session_id, :question_text, :type, :options, :correct_answer, 'draft')"
        );
        $stmt->execute([
            'session_id'     => $sessionId,
            'question_text'  => $text,
            'type'           => $type,
            'options'        => $options !== null ? json_encode($options, JSON_UNESCAPED_UNICODE) : null,
            'correct_answer' => $correctAnswer,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findBySessionId(int $sessionId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM questions WHERE session_id = :session_id ORDER BY id ASC");
        $stmt->execute(['session_id' => $sessionId]);

        $rows = $stmt->fetchAll() ?: [];
        foreach ($rows as &$row) {
            if (isset($row['options'])) {
                $row['options'] = json_decode($row['options'], true);
            }
        }

        return $rows;
    }

    public function findActiveBySessionId(int $sessionId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM questions WHERE session_id = :session_id AND status = 'active' LIMIT 1"
        );
        $stmt->execute(['session_id' => $sessionId]);
        $row = $stmt->fetch();

        if ($row) {
            if (isset($row['options'])) {
                $row['options'] = json_decode($row['options'], true);
            }
            return $row;
        }

        return null;
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare("UPDATE questions SET status = :status WHERE id = :id");
        $stmt->execute([
            'status' => $status,
            'id'     => $id,
        ]);
    }

    public function deactivateAllForSession(int $sessionId): void
    {
        $stmt = $this->db->prepare("UPDATE questions SET status = 'closed' WHERE session_id = :session_id AND status = 'active'");
        $stmt->execute(['session_id' => $sessionId]);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM questions WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row) {
            if (isset($row['options'])) {
                $row['options'] = json_decode($row['options'], true);
            }
            return $row;
        }

        return null;
    }
}
