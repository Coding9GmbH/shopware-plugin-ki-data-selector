<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class AnalyzeTrendingSearchesTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'search_optimizer.analyze_trending';
    }

    public static function getDefaultInterval(): int
    {
        return 3600; // Run every hour
    }
}