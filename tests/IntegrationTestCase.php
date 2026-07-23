<?php
declare(strict_types=1);

namespace EduQR\Tests;

use PHPUnit\Framework\TestCase;
use EduQR\Support\Database;
use PDO;

abstract class IntegrationTestCase extends TestCase
{
    protected ?PDO $db = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = Database::connect();

        // Temporarily disable foreign key constraints to clear tables safely
        $this->db->exec("SET FOREIGN_KEY_CHECKS = 0;");

        $tables = [
            'audit_logs',
            'answers',
            'participants',
            'questions',
            'question_bank',
            'sessions',
            'courses',
            'login_attempts',
            'users'
        ];

        foreach ($tables as $table) {
            $this->db->exec("TRUNCATE TABLE `{$table}`;");
        }

        $this->db->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }
}
