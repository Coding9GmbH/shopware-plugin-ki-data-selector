<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1700000001CreateSearchTables extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1700000001;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `search_query_log` (
                `id` BINARY(16) NOT NULL,
                `search_term` VARCHAR(255) NOT NULL,
                `normalized_term` VARCHAR(255) NOT NULL,
                `result_count` INT(11) NOT NULL DEFAULT 0,
                `sales_channel_id` BINARY(16) NULL,
                `language_id` BINARY(16) NULL,
                `customer_id` BINARY(16) NULL,
                `session_id` VARCHAR(255) NULL,
                `clicked_product_id` BINARY(16) NULL,
                `converted` TINYINT(1) NOT NULL DEFAULT 0,
                `search_source` VARCHAR(50) NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                KEY `idx_search_term` (`search_term`),
                KEY `idx_normalized_term` (`normalized_term`),
                KEY `idx_result_count` (`result_count`),
                KEY `idx_created_at` (`created_at`),
                KEY `idx_sales_channel` (`sales_channel_id`),
                CONSTRAINT `fk_search_log_sales_channel` FOREIGN KEY (`sales_channel_id`) 
                    REFERENCES `sales_channel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_search_log_language` FOREIGN KEY (`language_id`) 
                    REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_search_log_customer` FOREIGN KEY (`customer_id`) 
                    REFERENCES `customer` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_search_log_product` FOREIGN KEY (`clicked_product_id`) 
                    REFERENCES `product` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `search_synonym` (
                `id` BINARY(16) NOT NULL,
                `keyword` VARCHAR(255) NOT NULL,
                `synonym` VARCHAR(255) NOT NULL,
                `language_id` BINARY(16) NULL,
                `sales_channel_id` BINARY(16) NULL,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_keyword_synonym_lang_channel` (`keyword`, `synonym`, `language_id`, `sales_channel_id`),
                KEY `idx_keyword` (`keyword`),
                KEY `idx_synonym` (`synonym`),
                KEY `idx_active` (`active`),
                CONSTRAINT `fk_synonym_language` FOREIGN KEY (`language_id`) 
                    REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_synonym_sales_channel` FOREIGN KEY (`sales_channel_id`) 
                    REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `search_redirect` (
                `id` BINARY(16) NOT NULL,
                `search_term` VARCHAR(255) NOT NULL,
                `target_url` VARCHAR(500) NOT NULL,
                `target_type` VARCHAR(50) NOT NULL,
                `target_entity_id` BINARY(16) NULL,
                `language_id` BINARY(16) NULL,
                `sales_channel_id` BINARY(16) NULL,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `priority` INT(11) NOT NULL DEFAULT 0,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_term_lang_channel` (`search_term`, `language_id`, `sales_channel_id`),
                KEY `idx_search_term` (`search_term`),
                KEY `idx_active_priority` (`active`, `priority`),
                CONSTRAINT `fk_redirect_language` FOREIGN KEY (`language_id`) 
                    REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_redirect_sales_channel` FOREIGN KEY (`sales_channel_id`) 
                    REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}