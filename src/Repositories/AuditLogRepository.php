<?php

declare(strict_types=1);

namespace EduQR\Repositories;

use EduQR\Support\Database;
use PDO;

final class AuditLogRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function log(string $action, string $actorType, ?int $actorId = null, ?string $entityType = null, ?int $entityId = null, ?array $metadata = null): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO audit_logs (actor_type, actor_id, action, entity_type, entity_id, metadata_json)
             VALUES (:actor_type, :actor_id, :action, :entity_type, :entity_id, :metadata_json)"
        );
        $stmt->execute([
            'actor_type'    => $actorType,
            'actor_id'      => $actorId,
            'action'        => $action,
            'entity_type'   => $entityType,
            'entity_id'     => $entityId,
            'metadata_json' => $metadata !== null ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function index(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT :lim OFFSET :off"
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    public function findByUserId(int $userId, int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM audit_logs WHERE actor_id = :actor_id ORDER BY created_at DESC LIMIT :lim"
        );
        $stmt->bindValue(':actor_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    public function count(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM audit_logs");
        return (int) $stmt->fetchColumn();
    }
}
