<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use EduQR\Config;
use EduQR\Support\Database;

Config::load(__DIR__ . '/../.env');

try {
    $db = Database::connect();
    echo "Starting cleanup process...\n";

    // 1. Auto-close sessions that are older than 12 hours (FR-26)
    $autoCloseStmt = $db->prepare("
        UPDATE sessions 
        SET status = 'closed', closed_at = NOW() 
        WHERE status IN ('active', 'paused') 
          AND created_at < DATE_SUB(NOW(), INTERVAL 12 HOUR)
    ");
    $autoCloseStmt->execute();
    $closedCount = $autoCloseStmt->rowCount();
    echo "Auto-closed {$closedCount} inactive sessions older than 12 hours.\n";

    // 2. Auto-anonymize participants' nicknames and device cookies for closed sessions older than 365 days (NFR-34)
    $anonymizeParticipantsStmt = $db->prepare("
        UPDATE participants p
        JOIN sessions s ON p.session_id = s.id
        SET p.nickname = CONCAT('Katılımcı ', p.id), p.device_cookie = CONCAT('anon_', p.id)
        WHERE s.status = 'closed' 
          AND s.closed_at < DATE_SUB(NOW(), INTERVAL 365 DAY) 
          AND s.is_anonymized = 0
    ");
    $anonymizeParticipantsStmt->execute();
    $anonymizedParticipantsCount = $anonymizeParticipantsStmt->rowCount();

    $anonymizeSessionsStmt = $db->prepare("
        UPDATE sessions
        SET is_anonymized = 1
        WHERE status = 'closed' 
          AND closed_at < DATE_SUB(NOW(), INTERVAL 365 DAY) 
          AND is_anonymized = 0
    ");
    $anonymizeSessionsStmt->execute();
    $anonymizedSessionsCount = $anonymizeSessionsStmt->rowCount();

    echo "Anonymized {$anonymizedParticipantsCount} participants in {$anonymizedSessionsCount} closed sessions older than 365 days.\n";
    echo "Cleanup process completed successfully.\n";
    exit(0);
} catch (Exception $e) {
    echo "Error during cleanup: " . $e->getMessage() . "\n";
    exit(1);
}
