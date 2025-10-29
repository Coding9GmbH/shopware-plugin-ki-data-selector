<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Tests\Unit\Subscriber;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\Events\ProductSearchResultEvent;
use Shopware\Core\Content\Product\Events\ProductSuggestResultEvent;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Elasticsearch\Framework\ElasticsearchHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Swag\IntelligentSearchOptimizer\Service\SearchQueryNormalizer;
use Swag\IntelligentSearchOptimizer\Service\SynonymService;
use Swag\IntelligentSearchOptimizer\Subscriber\SearchEventSubscriber;
use Shopware\Core\Framework\Uuid\Uuid;

class SearchEventSubscriberTest extends TestCase
{
    private SearchEventSubscriber $subscriber;
    private EntityRepository $searchLogRepository;
    private EntityRepository $redirectRepository;
    private SystemConfigService $systemConfigService;
    private ElasticsearchHelper $elasticsearchHelper;
    private LoggerInterface $logger;
    private RequestStack $requestStack;
    private SearchQueryNormalizer $normalizer;
    private SynonymService $synonymService;

    protected function setUp(): void
    {
        $this->searchLogRepository = $this->createMock(EntityRepository::class);
        $this->redirectRepository = $this->createMock(EntityRepository::class);
        $this->systemConfigService = $this->createMock(SystemConfigService::class);
        $this->elasticsearchHelper = $this->createMock(ElasticsearchHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->normalizer = $this->createMock(SearchQueryNormalizer::class);
        $this->synonymService = $this->createMock(SynonymService::class);

        $this->subscriber = new SearchEventSubscriber(
            $this->searchLogRepository,
            $this->redirectRepository,
            $this->systemConfigService,
            $this->elasticsearchHelper,
            $this->logger,
            $this->requestStack,
            $this->normalizer,
            $this->synonymService
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $events = SearchEventSubscriber::getSubscribedEvents();

        static::assertArrayHasKey(ProductSearchResultEvent::class, $events);
        static::assertArrayHasKey(ProductSuggestResultEvent::class, $events);
        static::assertArrayHasKey('product.search.result.loaded', $events);
    }

    public function testOnProductSearchResult(): void
    {
        $searchTerm = 'test product';
        $request = new Request(['search' => $searchTerm]);
        $session = $this->createMock(SessionInterface::class);
        $session->method('getId')->willReturn('test-session-id');
        $request->setSession($session);

        $this->requestStack->method('getCurrentRequest')->willReturn($request);
        $this->systemConfigService->method('get')->willReturn(['enabled' => true]);
        $this->normalizer->method('normalize')->willReturn('test product');
        $this->synonymService->method('processSynonyms')->willReturn('test product');
        $this->elasticsearchHelper->method('allowSearch')->willReturn(true);

        $searchResult = new EntitySearchResult(
            'product',
            10,
            new ProductCollection(),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext->method('getSalesChannelId')->willReturn(Uuid::randomHex());
        $salesChannelContext->method('getLanguageId')->willReturn(Uuid::randomHex());

        $event = new ProductSearchResultEvent(
            $request,
            $searchResult,
            $salesChannelContext
        );

        $this->searchLogRepository
            ->expects(static::once())
            ->method('create')
            ->with(static::callback(function ($data) use ($searchTerm) {
                return $data[0]['searchTerm'] === $searchTerm
                    && $data[0]['resultCount'] === 10
                    && $data[0]['searchSource'] === 'elasticsearch_search';
            }));

        $this->subscriber->onProductSearchResult($event);
    }

    public function testOnProductSearchResultWithZeroResults(): void
    {
        $searchTerm = 'nonexistent';
        $request = new Request(['search' => $searchTerm]);
        $session = $this->createMock(SessionInterface::class);
        $session->method('getId')->willReturn('test-session-id');
        $request->setSession($session);

        $this->requestStack->method('getCurrentRequest')->willReturn($request);
        $this->systemConfigService->method('get')->willReturn(['enabled' => true]);
        $this->normalizer->method('normalize')->willReturn('nonexistent');
        $this->synonymService->method('processSynonyms')->willReturn('nonexistent');
        $this->elasticsearchHelper->method('allowSearch')->willReturn(false);

        $searchResult = new EntitySearchResult(
            'product',
            0,
            new ProductCollection(),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext->method('getSalesChannelId')->willReturn(Uuid::randomHex());
        $salesChannelContext->method('getLanguageId')->willReturn(Uuid::randomHex());

        $event = new ProductSearchResultEvent(
            $request,
            $searchResult,
            $salesChannelContext
        );

        $this->searchLogRepository
            ->expects(static::once())
            ->method('create')
            ->with(static::callback(function ($data) {
                return $data[0]['resultCount'] === 0
                    && $data[0]['searchSource'] === 'database_search';
            }));

        $this->subscriber->onProductSearchResult($event);
    }

    public function testOnProductSearchResultWithDisabledPlugin(): void
    {
        $request = new Request(['search' => 'test']);
        $this->requestStack->method('getCurrentRequest')->willReturn($request);
        $this->systemConfigService->method('get')->willReturn(['enabled' => false]);

        $searchResult = $this->createMock(EntitySearchResult::class);
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        
        $event = new ProductSearchResultEvent(
            $request,
            $searchResult,
            $salesChannelContext
        );

        $this->searchLogRepository
            ->expects(static::never())
            ->method('create');

        $this->subscriber->onProductSearchResult($event);
    }

    public function testOnProductSearchResultWithNoSearchTerm(): void
    {
        $request = new Request();
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $searchResult = $this->createMock(EntitySearchResult::class);
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        
        $event = new ProductSearchResultEvent(
            $request,
            $searchResult,
            $salesChannelContext
        );

        $this->searchLogRepository
            ->expects(static::never())
            ->method('create');

        $this->subscriber->onProductSearchResult($event);
    }

    public function testErrorHandlingDuringLogging(): void
    {
        $searchTerm = 'test';
        $request = new Request(['search' => $searchTerm]);
        $session = $this->createMock(SessionInterface::class);
        $request->setSession($session);

        $this->requestStack->method('getCurrentRequest')->willReturn($request);
        $this->systemConfigService->method('get')->willReturn(['enabled' => true]);
        $this->normalizer->method('normalize')->willReturn('test');
        $this->synonymService->method('processSynonyms')->willReturn('test');

        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('getTotal')->willReturn(5);
        
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext->method('getSalesChannelId')->willReturn(Uuid::randomHex());
        $salesChannelContext->method('getLanguageId')->willReturn(Uuid::randomHex());

        $event = new ProductSearchResultEvent(
            $request,
            $searchResult,
            $salesChannelContext
        );

        $this->searchLogRepository
            ->expects(static::once())
            ->method('create')
            ->willThrowException(new \Exception('Database error'));

        $this->logger
            ->expects(static::once())
            ->method('error')
            ->with(static::stringContains('Failed to log search query'));

        $this->subscriber->onProductSearchResult($event);
    }
}