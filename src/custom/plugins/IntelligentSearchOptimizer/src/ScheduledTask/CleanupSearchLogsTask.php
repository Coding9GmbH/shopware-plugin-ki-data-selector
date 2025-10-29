<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class CleanupSearchLogsTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'intelligent_search_optimizer.cleanup_search_logs';
    }

    public static function getDefaultInterval(): int
    {
        return 86400; // Run once per day
    }
}