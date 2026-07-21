<?php

declare(strict_types=1);

namespace EduQR\Repositories;

use EduQR\Support\Database;
use PDO;

final class ParticipantRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function findBySessionIdAndNickname(int $sessionId, string $nickname): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM participants WHERE session_id = :session_id AND LOWER(nickname) = LOWER(:nickname) LIMIT 1"
        );
        $stmt->execute([
            'session_id' => $sessionId,
            'nickname'   => $nickname,
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(int $sessionId, string $nickname, string $deviceCookie, ?string $userAgent): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO participants (session_id, nickname, device_cookie, user_agent) 
             VALUES (:session_id, :nickname, :device_cookie, :user_agent)"
        );
        $stmt->execute([
            'session_id'    => $sessionId,
            'nickname'      => $nickname,
            'device_cookie' => $deviceCookie,
            'user_agent'    => $userAgent,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM participants WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findByDeviceCookieAndSessionId(string $deviceCookie, int $sessionId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM participants WHERE device_cookie = :device_cookie AND session_id = :session_id LIMIT 1"
        );
        $stmt->execute([
            'device_cookie' => $deviceCookie,
            'session_id'    => $sessionId,
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM participants WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function countBySessionId(int $sessionId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM participants WHERE session_id = :session_id");
        $stmt->execute(['session_id' => $sessionId]);
        $row = $stmt->fetch();
        return $row ? (int)($row['cnt'] ?? 0) : 0;
    }

    public function updateNickname(int $id, string $nickname): void
    {
        $stmt = $this->db->prepare("UPDATE participants SET nickname = :nickname WHERE id = :id");
        $stmt->execute(['nickname' => $nickname, 'id' => $id]);
    }

    public function getAllBySessionId(int $sessionId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM participants WHERE session_id = :session_id ORDER BY created_at ASC");
        $stmt->execute(['session_id' => $sessionId]);
        return $stmt->fetchAll() ?: [];
    }

    public function anonymizeAllInSession(int $sessionId): void
    {
        $participants = $this->getAllBySessionId($sessionId);
        foreach ($participants as $idx => $p) {
            $stmt = $this->db->prepare("UPDATE participants SET nickname = :nickname WHERE id = :id");
            $stmt->execute(['nickname' => 'Katılımcı ' . ($idx + 1), 'id' => (int)$p['id']]);
        }
    }
}
