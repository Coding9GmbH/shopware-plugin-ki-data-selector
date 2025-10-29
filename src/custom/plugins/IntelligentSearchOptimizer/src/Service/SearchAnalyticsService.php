<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;

class SearchAnalyticsService
{
    private EntityRepository $searchLogRepository;
    private Connection $connection;

    public function __construct(
        EntityRepository $searchLogRepository,
        Connection $connection
    ) {
        $this->searchLogRepository = $searchLogRepository;
        $this->connection = $connection;
    }

    public function getTopSearchTerms(
        ?string $salesChannelId = null,
        ?string $languageId = null,
        int $limit = 10,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null,
        Context $context = null
    ): array {
        $query = $this->connection->createQueryBuilder()
            ->select([
                'search_term',
                'COUNT(*) as search_count',
                'AVG(result_count) as avg_results',
                'SUM(CASE WHEN result_count = 0 THEN 1 ELSE 0 END) as zero_results_count',
                'SUM(converted) as conversions',
                'COUNT(DISTINCT session_id) as unique_sessions'
            ])
            ->from('search_query_log')
            ->groupBy('search_term')
            ->orderBy('search_count', 'DESC')
            ->setMaxResults($limit);

        if ($salesChannelId) {
            $query->andWhere('sales_channel_id = :salesChannelId')
                ->setParameter('salesChannelId', $salesChannelId);
        }

        if ($languageId) {
            $query->andWhere('language_id = :languageId')
                ->setParameter('languageId', $languageId);
        }

        if ($from) {
            $query->andWhere('created_at >= :from')
                ->setParameter('from', $from->format('Y-m-d H:i:s'));
        }

        if ($to) {
            $query->andWhere('created_at <= :to')
                ->setParameter('to', $to->format('Y-m-d H:i:s'));
        }

        $results = $query->execute()->fetchAllAssociative();

        foreach ($results as &$result) {
            $result['conversion_rate'] = $result['search_count'] > 0 
                ? round(($result['conversions'] / $result['search_count']) * 100, 2) 
                : 0;
        }

        return $results;
    }

    public function getZeroResultSearches(
        ?string $salesChannelId = null,
        ?string $languageId = null,
        int $limit = 50,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null
    ): array {
        $query = $this->connection->createQueryBuilder()
            ->select([
                'search_term',
                'COUNT(*) as search_count',
                'MAX(created_at) as last_searched',
                'COUNT(DISTINCT session_id) as unique_sessions'
            ])
            ->from('search_query_log')
            ->where('result_count = 0')
            ->groupBy('search_term')
            ->orderBy('search_count', 'DESC')
            ->setMaxResults($limit);

        if ($salesChannelId) {
            $query->andWhere('sales_channel_id = :salesChannelId')
                ->setParameter('salesChannelId', $salesChannelId);
        }

        if ($languageId) {
            $query->andWhere('language_id = :languageId')
                ->setParameter('languageId', $languageId);
        }

        if ($from) {
            $query->andWhere('created_at >= :from')
                ->setParameter('from', $from->format('Y-m-d H:i:s'));
        }

        if ($to) {
            $query->andWhere('created_at <= :to')
                ->setParameter('to', $to->format('Y-m-d H:i:s'));
        }

        return $query->execute()->fetchAllAssociative();
    }

    public function getSearchTrends(
        ?string $salesChannelId = null,
        ?string $languageId = null,
        string $interval = 'day',
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null
    ): array {
        $dateFormat = match($interval) {
            'hour' => '%Y-%m-%d %H:00:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d'
        };

        $query = $this->connection->createQueryBuilder()
            ->select([
                "DATE_FORMAT(created_at, '$dateFormat') as period",
                'COUNT(*) as total_searches',
                'COUNT(DISTINCT search_term) as unique_terms',
                'COUNT(DISTINCT session_id) as unique_sessions',
                'SUM(CASE WHEN result_count = 0 THEN 1 ELSE 0 END) as zero_results',
                'SUM(converted) as conversions'
            ])
            ->from('search_query_log')
            ->groupBy('period')
            ->orderBy('period', 'DESC');

        if ($salesChannelId) {
            $query->andWhere('sales_channel_id = :salesChannelId')
                ->setParameter('salesChannelId', $salesChannelId);
        }

        if ($languageId) {
            $query->andWhere('language_id = :languageId')
                ->setParameter('languageId', $languageId);
        }

        if ($from) {
            $query->andWhere('created_at >= :from')
                ->setParameter('from', $from->format('Y-m-d H:i:s'));
        }

        if ($to) {
            $query->andWhere('created_at <= :to')
                ->setParameter('to', $to->format('Y-m-d H:i:s'));
        }

        $results = $query->execute()->fetchAllAssociative();

        foreach ($results as &$result) {
            $result['conversion_rate'] = $result['total_searches'] > 0 
                ? round(($result['conversions'] / $result['total_searches']) * 100, 2) 
                : 0;
            $result['zero_results_rate'] = $result['total_searches'] > 0 
                ? round(($result['zero_results'] / $result['total_searches']) * 100, 2) 
                : 0;
        }

        return $results;
    }

    public function getSearchStatsSummary(
        ?string $salesChannelId = null,
        ?string $languageId = null,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null
    ): array {
        $query = $this->connection->createQueryBuilder()
            ->select([
                'COUNT(*) as total_searches',
                'COUNT(DISTINCT search_term) as unique_terms',
                'COUNT(DISTINCT session_id) as unique_sessions',
                'SUM(CASE WHEN result_count = 0 THEN 1 ELSE 0 END) as zero_results',
                'SUM(converted) as conversions',
                'AVG(result_count) as avg_results'
            ])
            ->from('search_query_log');

        if ($salesChannelId) {
            $query->andWhere('sales_channel_id = :salesChannelId')
                ->setParameter('salesChannelId', $salesChannelId);
        }

        if ($languageId) {
            $query->andWhere('language_id = :languageId')
                ->setParameter('languageId', $languageId);
        }

        if ($from) {
            $query->andWhere('created_at >= :from')
                ->setParameter('from', $from->format('Y-m-d H:i:s'));
        }

        if ($to) {
            $query->andWhere('created_at <= :to')
                ->setParameter('to', $to->format('Y-m-d H:i:s'));
        }

        $result = $query->execute()->fetchAssociative();

        $result['conversion_rate'] = $result['total_searches'] > 0 
            ? round(($result['conversions'] / $result['total_searches']) * 100, 2) 
            : 0;
        $result['zero_results_rate'] = $result['total_searches'] > 0 
            ? round(($result['zero_results'] / $result['total_searches']) * 100, 2) 
            : 0;

        return $result;
    }

    public function getLowPerformingSearches(
        ?string $salesChannelId = null,
        ?string $languageId = null,
        int $minSearchCount = 5,
        float $maxConversionRate = 5.0,
        int $limit = 20
    ): array {
        $query = $this->connection->createQueryBuilder()
            ->select([
                'search_term',
                'COUNT(*) as search_count',
                'AVG(result_count) as avg_results',
                'SUM(converted) as conversions',
                'MAX(created_at) as last_searched'
            ])
            ->from('search_query_log')
            ->where('result_count > 0')
            ->groupBy('search_term')
            ->having('COUNT(*) >= :minCount')
            ->andHaving('(SUM(converted) / COUNT(*)) * 100 <= :maxRate')
            ->setParameter('minCount', $minSearchCount)
            ->setParameter('maxRate', $maxConversionRate)
            ->orderBy('search_count', 'DESC')
            ->setMaxResults($limit);

        if ($salesChannelId) {
            $query->andWhere('sales_channel_id = :salesChannelId')
                ->setParameter('salesChannelId', $salesChannelId);
        }

        if ($languageId) {
            $query->andWhere('language_id = :languageId')
                ->setParameter('languageId', $languageId);
        }

        $results = $query->execute()->fetchAllAssociative();

        foreach ($results as &$result) {
            $result['conversion_rate'] = $result['search_count'] > 0 
                ? round(($result['conversions'] / $result['search_count']) * 100, 2) 
                : 0;
        }

        return $results;
    }
}