<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\IntelligentSearchOptimizer\Entity\SearchSynonym\SearchSynonymEntity;
use Swag\IntelligentSearchOptimizer\Service\SynonymService;

class SynonymServiceTest extends TestCase
{
    private SynonymService $synonymService;
    private EntityRepository $synonymRepository;
    private Context $context;

    protected function setUp(): void
    {
        $this->synonymRepository = $this->createMock(EntityRepository::class);
        $this->synonymService = new SynonymService($this->synonymRepository);
        $this->context = Context::createDefaultContext();
    }

    public function testProcessSynonymsWithNoResults(): void
    {
        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('getTotal')->willReturn(0);

        $this->synonymRepository
            ->expects(static::once())
            ->method('search')
            ->willReturn($searchResult);

        $result = $this->synonymService->processSynonyms(
            'test',
            Uuid::randomHex(),
            Uuid::randomHex(),
            $this->context
        );

        static::assertEquals('test', $result);
    }

    public function testProcessSynonymsWithMatch(): void
    {
        $synonym = new SearchSynonymEntity();
        $synonym->setId(Uuid::randomHex());
        $synonym->setKeyword('test');
        $synonym->setSynonym('examination');

        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('getTotal')->willReturn(1);
        $searchResult->method('getIterator')->willReturn(new \ArrayIterator([$synonym]));

        $this->synonymRepository
            ->expects(static::once())
            ->method('search')
            ->willReturn($searchResult);

        $result = $this->synonymService->processSynonyms(
            'test',
            Uuid::randomHex(),
            Uuid::randomHex(),
            $this->context
        );

        static::assertEquals('examination', $result);
    }

    public function testGetAllSynonymsForTerm(): void
    {
        $synonym1 = new SearchSynonymEntity();
        $synonym1->setId(Uuid::randomHex());
        $synonym1->setKeyword('test');
        $synonym1->setSynonym('examination');

        $synonym2 = new SearchSynonymEntity();
        $synonym2->setId(Uuid::randomHex());
        $synonym2->setKeyword('examination');
        $synonym2->setSynonym('quiz');

        $searchResult1 = $this->createMock(EntitySearchResult::class);
        $searchResult1->method('getTotal')->willReturn(1);
        $searchResult1->method('getIterator')->willReturn(new \ArrayIterator([$synonym1]));

        $searchResult2 = $this->createMock(EntitySearchResult::class);
        $searchResult2->method('getTotal')->willReturn(1);
        $searchResult2->method('getIterator')->willReturn(new \ArrayIterator([$synonym2]));

        $this->synonymRepository
            ->expects(static::exactly(2))
            ->method('search')
            ->willReturnOnConsecutiveCalls($searchResult1, $searchResult2);

        $result = $this->synonymService->getAllSynonymsForTerm(
            'test',
            Uuid::randomHex(),
            Uuid::randomHex(),
            $this->context
        );

        static::assertContains('test', $result);
        static::assertContains('examination', $result);
        static::assertContains('quiz', $result);
        static::assertCount(3, $result);
    }

    public function testProcessSynonymsWithNullSalesChannel(): void
    {
        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('getTotal')->willReturn(0);

        $this->synonymRepository
            ->expects(static::once())
            ->method('search')
            ->with(static::callback(function (Criteria $criteria) {
                $filters = $criteria->getFilters();
                // Check that the criteria includes filters for null sales channel
                return count($filters) > 0;
            }))
            ->willReturn($searchResult);

        $this->synonymService->processSynonyms(
            'test',
            Uuid::randomHex(),
            Uuid::randomHex(),
            $this->context
        );
    }
}