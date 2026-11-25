<?php

declare(strict_types=1);

/**
 * Keeps an already-imported database in step with the code.
 *
 * Adds any column the application needs but an older `fittrack2.sql` import
 * does not have yet (exercise difficulty/equipment, gym + coaching payments).
 * It runs once per login session, only ever ADDS columns, and never touches data.
 * The same changes are in fittrack2.sql (fresh installs) and
 * database/payment_schedule_migration.sql (manual run).
 */
const FITTRACK_SCHEMA_VERSION = 3;

function fittrackEnsureSchema(PDO $pdo): void
{
    if (($_SESSION['fittrack_schema_version'] ?? null) === FITTRACK_SCHEMA_VERSION) {
        return;
    }

    // table => [column => definition]
    $required = [
        'exercises' => [
            'difficulty' => "ENUM('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner'",
            'equipment' => 'VARCHAR(120) NULL',
        ],
        'gym_memberships' => [
            'payment_method' => "ENUM('cash','qr') NOT NULL DEFAULT 'cash'",
            'payment_reference' => 'VARCHAR(120) NULL',
            'payment_proof' => 'VARCHAR(255) NULL',
        ],
        'coach_bookings' => [
            'payment_method' => "ENUM('cash','qr') NULL",
            'payment_status' => "ENUM('unpaid','pending','paid','rejected') NOT NULL DEFAULT 'unpaid'",
            'payment_amount' => 'DECIMAL(10,2) NULL',
            'payment_reference' => 'VARCHAR(120) NULL',
            'payment_proof' => 'VARCHAR(255) NULL',
            'payment_note' => 'TEXT NULL',
            'payment_submitted_at' => 'DATETIME NULL',
            'payment_reviewed_at' => 'DATETIME NULL',
        ],
    ];

    try {
        $tables = array_keys($required);
        $marks = implode(',', array_fill(0, count($tables), '?'));
        $q = $pdo->prepare(
            'SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (' . $marks . ')'
        );
        $q->execute($tables);

        $existing = [];

        foreach ($q->fetchAll() as $row) {
            $existing[strtolower((string) $row['TABLE_NAME'])][strtolower((string) $row['COLUMN_NAME'])] = true;
        }

        foreach ($required as $table => $columns) {
            if (empty($existing[$table])) {
                continue; // table itself is missing: the SQL import has not been run
            }

            foreach ($columns as $column => $definition) {
                if (!isset($existing[$table][$column])) {
                    $pdo->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
                }
            }
        }
        $_SESSION['fittrack_schema_version'] = FITTRACK_SCHEMA_VERSION;
    } catch (Throwable $exception) {
        error_log('FitTrack schema check failed: ' . $exception->getMessage());
    }
}
