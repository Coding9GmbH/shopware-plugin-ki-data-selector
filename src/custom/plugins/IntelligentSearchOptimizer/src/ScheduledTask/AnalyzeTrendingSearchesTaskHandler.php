<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Swag\IntelligentSearchOptimizer\Service\TrendingSearchService;
use Shopware\Core\Framework\Context;

class AnalyzeTrendingSearchesTaskHandler extends ScheduledTaskHandler
{
    private TrendingSearchService $trendingSearchService;
    
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        TrendingSearchService $trendingSearchService
    ) {
        parent::__construct($scheduledTaskRepository);
        $this->trendingSearchService = $trendingSearchService;
    }

    public static function getHandledMessages(): iterable
    {
        return [AnalyzeTrendingSearchesTask::class];
    }

    public function run(): void
    {
        // Analyze trends for all sales channels
        $this->trendingSearchService->analyzeTrends();
    }
}