<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use EduQR\Config;
use EduQR\Support\Database;

Config::load(__DIR__ . '/../.env');

$backupsDir = __DIR__ . '/../backups';
if (!is_dir($backupsDir)) {
    mkdir($backupsDir, 0755, true);
    // Create htaccess to prevent direct access
    file_put_contents($backupsDir . '/.htaccess', "Deny from all\n");
}

$timestamp = date('Ymd_His');
$backupFile = $backupsDir . "/backup_{$timestamp}.sql";

try {
    $db = Database::connect();
    echo "Connecting to database and starting backup...\n";

    // Set UTF-8
    $db->exec("SET NAMES utf8mb4");

    $tables = [];
    $stmt = $db->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    if (empty($tables)) {
        echo "No tables found in the database.\n";
        exit(0);
    }

    $sqlDump = "-- eduQR Database Backup\n";
    $sqlDump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sqlDump .= "-- ------------------------------------------------------\n\n";
    $sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        $sqlDump .= "--\n-- Table structure for table `{$table}`\n--\n\n";
        $sqlDump .= "DROP TABLE IF EXISTS `{$table}`;\n";
        
        $createStmt = $db->query("SHOW CREATE TABLE `{$table}`");
        $createRow = $createStmt->fetch(PDO::FETCH_NUM);
        $sqlDump .= $createRow[1] . ";\n\n";

        $sqlDump .= "--\n-- Dumping data for table `{$table}`\n--\n\n";
        
        $dataStmt = $db->query("SELECT * FROM `{$table}`");
        $rowCount = 0;
        
        while ($dataRow = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
            $rowCount++;
            $columns = array_keys($dataRow);
            $escapedColumns = array_map(fn($col) => "`{$col}`", $columns);
            
            $values = [];
            foreach ($dataRow as $val) {
                if ($val === null) {
                    $values[] = 'NULL';
                } else {
                    $values[] = $db->quote((string)$val);
                }
            }
            
            $sqlDump .= "INSERT INTO `{$table}` (" . implode(', ', $escapedColumns) . ") VALUES (" . implode(', ', $values) . ");\n";
        }
        $sqlDump .= "\n-- Table `{$table}` rows: {$rowCount}\n\n";
        echo "Backed up table `{$table}` with {$rowCount} rows.\n";
    }

    $sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";

    if (file_put_contents($backupFile, $sqlDump) === false) {
        throw new RuntimeException("Failed to write backup file to: {$backupFile}");
    }

    echo "Database backup successfully saved to: " . realpath($backupFile) . "\n";
    exit(0);
} catch (Exception $e) {
    echo "Error during database backup: " . $e->getMessage() . "\n";
    exit(1);
}
