<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\StaticKernelPluginLoader;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\IntelligentSearchOptimizer\IntelligentSearchOptimizer;

class PluginIntegrationTest extends TestCase
{
    use IntegrationTestBehaviour;
    use KernelTestBehaviour;

    private EntityRepository $searchLogRepository;
    private EntityRepository $synonymRepository;
    private EntityRepository $redirectRepository;
    private Context $context;

    protected function setUp(): void
    {
        $this->searchLogRepository = $this->getContainer()->get('search_query_log.repository');
        $this->synonymRepository = $this->getContainer()->get('search_synonym.repository');
        $this->redirectRepository = $this->getContainer()->get('search_redirect.repository');
        $this->context = Context::createDefaultContext();
    }

    public function testPluginInstallation(): void
    {
        $pluginLoader = $this->getContainer()->get('kernel.plugin_loader');
        $plugin = $pluginLoader->getPluginInstance('IntelligentSearchOptimizer');
        
        static::assertInstanceOf(IntelligentSearchOptimizer::class, $plugin);
    }

    public function testEntitiesAreRegistered(): void
    {
        static::assertNotNull($this->searchLogRepository);
        static::assertNotNull($this->synonymRepository);
        static::assertNotNull($this->redirectRepository);
    }

    public function testCreateSearchLog(): void
    {
        $id = Uuid::randomHex();
        $data = [
            'id' => $id,
            'searchTerm' => 'test product',
            'normalizedTerm' => 'test product',
            'resultCount' => 10,
            'salesChannelId' => null,
            'languageId' => null,
            'sessionId' => 'test-session',
            'converted' => false,
            'searchSource' => 'database_search',
            'createdAt' => new \DateTime()
        ];

        $this->searchLogRepository->create([$data], $this->context);

        $result = $this->searchLogRepository->search(
            new Criteria([$id]),
            $this->context
        );

        static::assertEquals(1, $result->getTotal());
        $entity = $result->first();
        static::assertEquals('test product', $entity->getSearchTerm());
        static::assertEquals(10, $entity->getResultCount());
    }

    public function testCreateSynonym(): void
    {
        $id = Uuid::randomHex();
        $data = [
            'id' => $id,
            'keyword' => 'mobile',
            'synonym' => 'smartphone',
            'active' => true,
            'createdAt' => new \DateTime()
        ];

        $this->synonymRepository->create([$data], $this->context);

        $result = $this->synonymRepository->search(
            new Criteria([$id]),
            $this->context
        );

        static::assertEquals(1, $result->getTotal());
        $entity = $result->first();
        static::assertEquals('mobile', $entity->getKeyword());
        static::assertEquals('smartphone', $entity->getSynonym());
        static::assertTrue($entity->isActive());
    }

    public function testCreateRedirect(): void
    {
        $id = Uuid::randomHex();
        $data = [
            'id' => $id,
            'searchTerm' => 'sale',
            'targetUrl' => '/sale-category',
            'targetType' => 'url',
            'active' => true,
            'priority' => 100,
            'createdAt' => new \DateTime()
        ];

        $this->redirectRepository->create([$data], $this->context);

        $result = $this->redirectRepository->search(
            new Criteria([$id]),
            $this->context
        );

        static::assertEquals(1, $result->getTotal());
        $entity = $result->first();
        static::assertEquals('sale', $entity->getSearchTerm());
        static::assertEquals('/sale-category', $entity->getTargetUrl());
        static::assertEquals(100, $entity->getPriority());
    }

    public function testServicesAreRegistered(): void
    {
        $searchQueryNormalizer = $this->getContainer()->get('Swag\IntelligentSearchOptimizer\Service\SearchQueryNormalizer');
        static::assertNotNull($searchQueryNormalizer);

        $synonymService = $this->getContainer()->get('Swag\IntelligentSearchOptimizer\Service\SynonymService');
        static::assertNotNull($synonymService);

        $analyticsService = $this->getContainer()->get('Swag\IntelligentSearchOptimizer\Service\SearchAnalyticsService');
        static::assertNotNull($analyticsService);
    }

    public function testSubscribersAreRegistered(): void
    {
        $eventDispatcher = $this->getContainer()->get('event_dispatcher');
        $listeners = $eventDispatcher->getListeners();
        
        static::assertArrayHasKey('Shopware\Core\Content\Product\Events\ProductSearchResultEvent', $listeners);
        static::assertArrayHasKey('Shopware\Core\Content\Product\Events\ProductSuggestResultEvent', $listeners);
    }

    public function testAdminApiRoutesExist(): void
    {
        $router = $this->getContainer()->get('router');
        
        $routes = [
            'api.search_optimizer.analytics.dashboard',
            'api.search_optimizer.analytics.top_searches',
            'api.search_optimizer.analytics.zero_results',
            'api.search_optimizer.analytics.export',
            'api.search_optimizer.synonyms.list',
            'api.search_optimizer.synonyms.create',
            'api.search_optimizer.redirects.list',
            'api.search_optimizer.redirects.create'
        ];

        foreach ($routes as $routeName) {
            $route = $router->getRouteCollection()->get($routeName);
            static::assertNotNull($route, "Route {$routeName} should exist");
        }
    }
}