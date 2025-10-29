<?php declare(strict_types=1);

namespace Coding9\KIDataSelector\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Migration to create kidata_query_log table
 *
 * Stores query execution history with prompts, generated SQL,
 * execution status and row counts.
 *
 * @package Coding9\KIDataSelector\Migration
 */
class Migration1700000000CreateKiDataTables extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1700000000;
    }

    /**
     * @param Connection $connection
     * @return void
     */
    public function update(Connection $connection): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `kidata_query_log` (
            `id` BINARY(16) NOT NULL,
            `prompt` LONGTEXT NOT NULL,
            `sql_query` LONGTEXT NOT NULL,
            `executed` TINYINT(1) NOT NULL DEFAULT 0,
            `row_count` INT NULL,
            `created_at` DATETIME(3) NOT NULL,
            PRIMARY KEY (`id`),
            INDEX `idx_created_at` (`created_at`),
            INDEX `idx_executed` (`executed`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

        $connection->executeStatement($sql);
    }

    /**
     * @param Connection $connection
     * @return void
     */
    public function updateDestructive(Connection $connection): void
    {
        // No destructive changes needed
    }
}
