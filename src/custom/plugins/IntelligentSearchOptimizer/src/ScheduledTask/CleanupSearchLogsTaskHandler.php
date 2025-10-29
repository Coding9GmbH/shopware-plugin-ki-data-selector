<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Swag\IntelligentSearchOptimizer\Service\SearchLogCleanupService;

class CleanupSearchLogsTaskHandler extends ScheduledTaskHandler
{
    private SearchLogCleanupService $cleanupService;

    public function __construct(
        EntityRepository $scheduledTaskRepository,
        SearchLogCleanupService $cleanupService
    ) {
        parent::__construct($scheduledTaskRepository);
        $this->cleanupService = $cleanupService;
    }

    public static function getHandledMessages(): iterable
    {
        return [CleanupSearchLogsTask::class];
    }

    public function run(): void
    {
        $this->cleanupService->cleanup();
    }
}