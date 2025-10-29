<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Psr\Log\LoggerInterface;

class SearchLogCleanupService
{
    private EntityRepository $searchLogRepository;
    private SystemConfigService $systemConfigService;
    private LoggerInterface $logger;

    public function __construct(
        EntityRepository $searchLogRepository,
        SystemConfigService $systemConfigService,
        LoggerInterface $logger = null
    ) {
        $this->searchLogRepository = $searchLogRepository;
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
    }

    public function cleanup(?string $salesChannelId = null): int
    {
        $context = Context::createDefaultContext();
        $retentionDays = $this->systemConfigService->get(
            'IntelligentSearchOptimizer.config.logRetentionDays',
            $salesChannelId
        ) ?? 90;

        $batchSize = $this->systemConfigService->get(
            'IntelligentSearchOptimizer.config.cleanupBatchSize',
            $salesChannelId
        ) ?? 1000;

        $cutoffDate = new \DateTime();
        $cutoffDate->modify("-{$retentionDays} days");

        $deletedCount = 0;

        do {
            $criteria = new Criteria();
            $criteria->addFilter(new RangeFilter('createdAt', [
                RangeFilter::LT => $cutoffDate->format('Y-m-d H:i:s')
            ]));
            $criteria->setLimit($batchSize);

            $results = $this->searchLogRepository->searchIds($criteria, $context);
            
            if ($results->getTotal() === 0) {
                break;
            }

            $ids = array_map(function ($id) {
                return ['id' => $id];
            }, $results->getIds());

            $this->searchLogRepository->delete($ids, $context);
            $deletedCount += count($ids);

            if ($this->logger) {
                $this->logger->info(sprintf(
                    'Deleted %d old search log entries',
                    count($ids)
                ));
            }

        } while ($results->getTotal() > 0);

        if ($this->logger) {
            $this->logger->info(sprintf(
                'Search log cleanup completed. Total deleted: %d entries older than %s',
                $deletedCount,
                $cutoffDate->format('Y-m-d')
            ));
        }

        return $deletedCount;
    }

    public function getOldEntriesCount(?string $salesChannelId = null): int
    {
        $context = Context::createDefaultContext();
        $retentionDays = $this->systemConfigService->get(
            'IntelligentSearchOptimizer.config.logRetentionDays',
            $salesChannelId
        ) ?? 90;

        $cutoffDate = new \DateTime();
        $cutoffDate->modify("-{$retentionDays} days");

        $criteria = new Criteria();
        $criteria->addFilter(new RangeFilter('createdAt', [
            RangeFilter::LT => $cutoffDate->format('Y-m-d H:i:s')
        ]));

        return $this->searchLogRepository->searchIds($criteria, $context)->getTotal();
    }
}