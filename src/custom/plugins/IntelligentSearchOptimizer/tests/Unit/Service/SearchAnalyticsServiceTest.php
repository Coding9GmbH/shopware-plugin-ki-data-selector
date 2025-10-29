<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Swag\IntelligentSearchOptimizer\Service\SearchAnalyticsService;

class SearchAnalyticsServiceTest extends TestCase
{
    private SearchAnalyticsService $analyticsService;
    private EntityRepository $searchLogRepository;
    private Connection $connection;
    private QueryBuilder $queryBuilder;

    protected function setUp(): void
    {
        $this->searchLogRepository = $this->createMock(EntityRepository::class);
        $this->connection = $this->createMock(Connection::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        
        $this->connection->method('createQueryBuilder')->willReturn($this->queryBuilder);
        
        $this->analyticsService = new SearchAnalyticsService(
            $this->searchLogRepository,
            $this->connection
        );
    }

    public function testGetTopSearchTerms(): void
    {
        $expectedData = [
            [
                'search_term' => 'test product',
                'search_count' => 100,
                'avg_results' => 25.5,
                'zero_results_count' => 5,
                'conversions' => 10,
                'unique_sessions' => 80,
                'conversion_rate' => 10.0
            ]
        ];

        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn($expectedData);

        $this->setupQueryBuilderForTopSearches();
        $this->queryBuilder->method('execute')->willReturn($result);

        $results = $this->analyticsService->getTopSearchTerms();

        static::assertCount(1, $results);
        static::assertEquals('test product', $results[0]['search_term']);
        static::assertEquals(10.0, $results[0]['conversion_rate']);
    }

    public function testGetZeroResultSearches(): void
    {
        $expectedData = [
            [
                'search_term' => 'nonexistent product',
                'search_count' => 50,
                'last_searched' => '2023-01-01 12:00:00',
                'unique_sessions' => 45
            ]
        ];

        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn($expectedData);

        $this->setupQueryBuilderForZeroResults();
        $this->queryBuilder->method('execute')->willReturn($result);

        $results = $this->analyticsService->getZeroResultSearches();

        static::assertCount(1, $results);
        static::assertEquals('nonexistent product', $results[0]['search_term']);
        static::assertEquals(50, $results[0]['search_count']);
    }

    public function testGetSearchTrends(): void
    {
        $expectedData = [
            [
                'period' => '2023-01-01',
                'total_searches' => 1000,
                'unique_terms' => 150,
                'unique_sessions' => 800,
                'zero_results' => 100,
                'conversions' => 50
            ]
        ];

        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn($expectedData);

        $this->setupQueryBuilderForTrends();
        $this->queryBuilder->method('execute')->willReturn($result);

        $results = $this->analyticsService->getSearchTrends();

        static::assertCount(1, $results);
        static::assertEquals(5.0, $results[0]['conversion_rate']);
        static::assertEquals(10.0, $results[0]['zero_results_rate']);
    }

    public function testGetSearchStatsSummary(): void
    {
        $expectedData = [
            'total_searches' => 10000,
            'unique_terms' => 500,
            'unique_sessions' => 8000,
            'zero_results' => 1000,
            'conversions' => 500,
            'avg_results' => 25.5
        ];

        $result = $this->createMock(Result::class);
        $result->method('fetchAssociative')->willReturn($expectedData);

        $this->setupQueryBuilderForSummary();
        $this->queryBuilder->method('execute')->willReturn($result);

        $summary = $this->analyticsService->getSearchStatsSummary();

        static::assertEquals(10000, $summary['total_searches']);
        static::assertEquals(5.0, $summary['conversion_rate']);
        static::assertEquals(10.0, $summary['zero_results_rate']);
    }

    public function testGetLowPerformingSearches(): void
    {
        $expectedData = [
            [
                'search_term' => 'low performing',
                'search_count' => 100,
                'avg_results' => 50,
                'conversions' => 2,
                'last_searched' => '2023-01-01 12:00:00'
            ]
        ];

        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn($expectedData);

        $this->setupQueryBuilderForLowPerforming();
        $this->queryBuilder->method('execute')->willReturn($result);

        $results = $this->analyticsService->getLowPerformingSearches();

        static::assertCount(1, $results);
        static::assertEquals(2.0, $results[0]['conversion_rate']);
    }

    private function setupQueryBuilderForTopSearches(): void
    {
        $this->queryBuilder->method('select')->willReturnSelf();
        $this->queryBuilder->method('from')->willReturnSelf();
        $this->queryBuilder->method('groupBy')->willReturnSelf();
        $this->queryBuilder->method('orderBy')->willReturnSelf();
        $this->queryBuilder->method('setMaxResults')->willReturnSelf();
        $this->queryBuilder->method('andWhere')->willReturnSelf();
        $this->queryBuilder->method('setParameter')->willReturnSelf();
    }

    private function setupQueryBuilderForZeroResults(): void
    {
        $this->setupQueryBuilderForTopSearches();
        $this->queryBuilder->method('where')->willReturnSelf();
    }

    private function setupQueryBuilderForTrends(): void
    {
        $this->setupQueryBuilderForTopSearches();
    }

    private function setupQueryBuilderForSummary(): void
    {
        $this->setupQueryBuilderForTopSearches();
    }

    private function setupQueryBuilderForLowPerforming(): void
    {
        $this->setupQueryBuilderForTopSearches();
        $this->queryBuilder->method('where')->willReturnSelf();
        $this->queryBuilder->method('having')->willReturnSelf();
        $this->queryBuilder->method('andHaving')->willReturnSelf();
    }
}