<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1702500000AddSpellCheckTables extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1702500000;
    }

    public function update(Connection $connection): void
    {
        // Dictionary for spell checking
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `search_optimizer_dictionary` (
                `id` BINARY(16) NOT NULL,
                `word` VARCHAR(255) NOT NULL,
                `frequency` INT(11) NOT NULL DEFAULT 1,
                `language` VARCHAR(5) NOT NULL DEFAULT "en-GB",
                `type` VARCHAR(50) DEFAULT NULL COMMENT "brand, technical, general",
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq.word_language` (`word`, `language`),
                KEY `idx.frequency` (`frequency`),
                KEY `idx.type` (`type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        // Spell corrections mapping
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `search_optimizer_spell_corrections` (
                `id` BINARY(16) NOT NULL,
                `misspelling` VARCHAR(255) NOT NULL,
                `correction` VARCHAR(255) NOT NULL,
                `confidence` DECIMAL(3,2) NOT NULL DEFAULT 0.80,
                `usage_count` INT(11) NOT NULL DEFAULT 0,
                `auto_generated` TINYINT(1) NOT NULL DEFAULT 1,
                `language` VARCHAR(5) NOT NULL DEFAULT "en-GB",
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq.misspelling_language` (`misspelling`, `language`),
                KEY `idx.correction` (`correction`),
                KEY `idx.confidence` (`confidence`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        // Search intent patterns
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `search_optimizer_intent_patterns` (
                `id` BINARY(16) NOT NULL,
                `pattern` VARCHAR(255) NOT NULL,
                `intent_type` VARCHAR(50) NOT NULL COMMENT "informational, transactional, navigational",
                `priority` INT(11) NOT NULL DEFAULT 100,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                KEY `idx.intent_type` (`intent_type`),
                KEY `idx.priority` (`priority`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        // Revenue tracking
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `search_optimizer_revenue_tracking` (
                `id` BINARY(16) NOT NULL,
                `search_term` VARCHAR(500) NOT NULL,
                `order_id` BINARY(16) NOT NULL,
                `order_amount` DECIMAL(10,2) NOT NULL,
                `customer_id` BINARY(16) NULL,
                `sales_channel_id` BINARY(16) NULL,
                `search_to_order_time` INT(11) NULL COMMENT "seconds from search to order",
                `created_at` DATETIME(3) NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx.search_term` (`search_term`(255)),
                KEY `idx.order_id` (`order_id`),
                KEY `idx.created_at` (`created_at`),
                KEY `idx.sales_channel` (`sales_channel_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        // Trending searches
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `search_optimizer_trending` (
                `id` BINARY(16) NOT NULL,
                `search_term` VARCHAR(500) NOT NULL,
                `hour_timestamp` DATETIME NOT NULL,
                `search_count` INT(11) NOT NULL DEFAULT 0,
                `previous_hour_count` INT(11) NOT NULL DEFAULT 0,
                `trend_score` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT "percentage change",
                `sales_channel_id` BINARY(16) NULL,
                `alert_sent` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` DATETIME(3) NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq.term_hour_channel` (`search_term`(255), `hour_timestamp`, `sales_channel_id`),
                KEY `idx.hour_timestamp` (`hour_timestamp`),
                KEY `idx.trend_score` (`trend_score`),
                KEY `idx.alert_sent` (`alert_sent`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        // Add default intent patterns
        $this->insertDefaultIntentPatterns($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // Nothing to do
    }

    private function insertDefaultIntentPatterns(Connection $connection): void
    {
        $patterns = [
            // Informational patterns
            ['pattern' => 'how to%', 'intent_type' => 'informational', 'priority' => 100],
            ['pattern' => 'what is%', 'intent_type' => 'informational', 'priority' => 100],
            ['pattern' => '%guide%', 'intent_type' => 'informational', 'priority' => 90],
            ['pattern' => '%tutorial%', 'intent_type' => 'informational', 'priority' => 90],
            ['pattern' => '%manual%', 'intent_type' => 'informational', 'priority' => 85],
            ['pattern' => '%instructions%', 'intent_type' => 'informational', 'priority' => 85],
            ['pattern' => '%anleitung%', 'intent_type' => 'informational', 'priority' => 90],
            ['pattern' => 'wie%', 'intent_type' => 'informational', 'priority' => 100],
            
            // Transactional patterns
            ['pattern' => 'buy%', 'intent_type' => 'transactional', 'priority' => 100],
            ['pattern' => '%price%', 'intent_type' => 'transactional', 'priority' => 90],
            ['pattern' => '%cheap%', 'intent_type' => 'transactional', 'priority' => 85],
            ['pattern' => '%discount%', 'intent_type' => 'transactional', 'priority' => 85],
            ['pattern' => '%sale%', 'intent_type' => 'transactional', 'priority' => 85],
            ['pattern' => '%deal%', 'intent_type' => 'transactional', 'priority' => 80],
            ['pattern' => 'kaufen%', 'intent_type' => 'transactional', 'priority' => 100],
            ['pattern' => '%preis%', 'intent_type' => 'transactional', 'priority' => 90],
            ['pattern' => '%günstig%', 'intent_type' => 'transactional', 'priority' => 85],
            ['pattern' => '%angebot%', 'intent_type' => 'transactional', 'priority' => 85],
            
            // Navigational patterns
            ['pattern' => '%contact%', 'intent_type' => 'navigational', 'priority' => 95],
            ['pattern' => '%support%', 'intent_type' => 'navigational', 'priority' => 95],
            ['pattern' => '%return%', 'intent_type' => 'navigational', 'priority' => 90],
            ['pattern' => '%shipping%', 'intent_type' => 'navigational', 'priority' => 90],
            ['pattern' => '%kontakt%', 'intent_type' => 'navigational', 'priority' => 95],
            ['pattern' => '%versand%', 'intent_type' => 'navigational', 'priority' => 90],
            ['pattern' => '%rückgabe%', 'intent_type' => 'navigational', 'priority' => 90],
        ];

        foreach ($patterns as $pattern) {
            $connection->insert('search_optimizer_intent_patterns', [
                'id' => \Shopware\Core\Framework\Uuid\Uuid::randomBytes(),
                'pattern' => $pattern['pattern'],
                'intent_type' => $pattern['intent_type'],
                'priority' => $pattern['priority'],
                'active' => 1,
                'created_at' => (new \DateTime())->format('Y-m-d H:i:s.v')
            ]);
        }
    }
}