<?php

declare(strict_types=1);

namespace EduQR\Repositories;

use EduQR\Support\Database;
use PDO;

final class AnswerRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function submitAnswer(int $questionId, int $participantId, string $value): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO answers (question_id, participant_id, answer_value) 
             VALUES (:question_id, :participant_id, :answer_value)
             ON DUPLICATE KEY UPDATE answer_value = :answer_value_update"
        );
        $stmt->execute([
            'question_id'         => $questionId,
            'participant_id'      => $participantId,
            'answer_value'        => $value,
            'answer_value_update' => $value,
        ]);
    }

    public function findAnswer(int $questionId, int $participantId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM answers WHERE question_id = :question_id AND participant_id = :participant_id LIMIT 1"
        );
        $stmt->execute([
            'question_id'    => $questionId,
            'participant_id' => $participantId,
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function getResultsForQuestion(int $questionId): array
    {
        $stmt = $this->db->prepare(
            "SELECT answer_value, COUNT(*) as count 
             FROM answers 
             WHERE question_id = :question_id 
             GROUP BY answer_value"
        );
        $stmt->execute(['question_id' => $questionId]);

        return $stmt->fetchAll() ?: [];
    }

    public function getAnswersForQuestion(int $questionId): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, p.nickname 
             FROM answers a
             JOIN participants p ON a.participant_id = p.id
             WHERE a.question_id = :question_id 
             ORDER BY a.created_at ASC"
        );
        $stmt->execute(['question_id' => $questionId]);

        return $stmt->fetchAll() ?: [];
    }
}
