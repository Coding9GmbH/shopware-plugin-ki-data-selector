<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\Test\TestDefaults;

class SearchIntegrationTest extends TestCase
{
    use IntegrationTestBehaviour;
    use SalesChannelApiTestBehaviour;

    private EntityRepository $productRepository;
    private EntityRepository $synonymRepository;
    private EntityRepository $searchLogRepository;
    private string $salesChannelId;
    private Context $context;

    protected function setUp(): void
    {
        $this->productRepository = $this->getContainer()->get('product.repository');
        $this->synonymRepository = $this->getContainer()->get('search_synonym.repository');
        $this->searchLogRepository = $this->getContainer()->get('search_query_log.repository');
        $this->context = Context::createDefaultContext();
        
        $this->salesChannelId = $this->createSalesChannel()['id'];
    }

    public function testSearchWithHyphenSpaceNormalization(): void
    {
        // Create test products
        $productWithHyphen = $this->createProduct('Test-Product', 'test-product-001');
        $productWithSpace = $this->createProduct('Test Product', 'test-product-002');
        
        // Search for "Test-Product"
        $response1 = $this->searchProducts('Test-Product');
        static::assertCount(2, $response1['elements'], 'Should find both products when searching with hyphen');
        
        // Search for "Test Product"
        $response2 = $this->searchProducts('Test Product');
        static::assertCount(2, $response2['elements'], 'Should find both products when searching with space');
    }

    public function testSearchWithSynonyms(): void
    {
        // Create products
        $mobileProduct = $this->createProduct('Mobile Phone', 'mobile-001');
        $smartphoneProduct = $this->createProduct('Smartphone Device', 'smartphone-001');
        
        // Create synonym
        $this->createSynonym('mobile', 'smartphone');
        
        // Search for "mobile"
        $response = $this->searchProducts('mobile');
        static::assertGreaterThanOrEqual(1, count($response['elements']), 'Should find products via synonym');
    }

    public function testSearchLogging(): void
    {
        $searchTerm = 'unique-test-search-' . Uuid::randomHex();
        
        // Perform search
        $this->searchProducts($searchTerm);
        
        // Check if search was logged
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('searchTerm', $searchTerm));
        
        $logs = $this->searchLogRepository->search($criteria, $this->context);
        static::assertEquals(1, $logs->getTotal(), 'Search should be logged');
        
        $log = $logs->first();
        static::assertEquals($searchTerm, $log->getSearchTerm());
    }

    public function testZeroResultSearchLogging(): void
    {
        $nonExistentTerm = 'nonexistent-product-' . Uuid::randomHex();
        
        // Perform search that returns no results
        $response = $this->searchProducts($nonExistentTerm);
        static::assertCount(0, $response['elements']);
        
        // Check if zero-result search was logged
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('searchTerm', $nonExistentTerm));
        $criteria->addFilter(new EqualsFilter('resultCount', 0));
        
        $logs = $this->searchLogRepository->search($criteria, $this->context);
        static::assertEquals(1, $logs->getTotal(), 'Zero-result search should be logged');
    }

    public function testMultipleHyphenSpaceVariations(): void
    {
        // Create products with multiple hyphens/spaces
        $product1 = $this->createProduct('Test-Product-Name', 'test-001');
        $product2 = $this->createProduct('Test Product Name', 'test-002');
        $product3 = $this->createProduct('Test-Product Name', 'test-003');
        
        // All search variations should find all products
        $searchTerms = [
            'Test-Product-Name',
            'Test Product Name',
            'Test-Product Name',
            'Test Product-Name'
        ];
        
        foreach ($searchTerms as $term) {
            $response = $this->searchProducts($term);
            static::assertGreaterThanOrEqual(
                3, 
                count($response['elements']), 
                "Search for '{$term}' should find all product variations"
            );
        }
    }

    private function createProduct(string $name, string $productNumber): string
    {
        $productId = Uuid::randomHex();
        
        $data = [
            'id' => $productId,
            'productNumber' => $productNumber,
            'name' => $name,
            'price' => [
                [
                    'currencyId' => TestDefaults::CURRENCY,
                    'gross' => 100,
                    'net' => 100,
                    'linked' => false
                ]
            ],
            'tax' => [
                'name' => 'test',
                'taxRate' => 19
            ],
            'stock' => 100,
            'active' => true,
            'visibilities' => [
                [
                    'salesChannelId' => $this->salesChannelId,
                    'visibility' => ProductEntity::VISIBILITY_ALL
                ]
            ]
        ];
        
        $this->productRepository->create([$data], $this->context);
        
        return $productId;
    }

    private function createSynonym(string $keyword, string $synonym): void
    {
        $data = [
            'id' => Uuid::randomHex(),
            'keyword' => $keyword,
            'synonym' => $synonym,
            'active' => true,
            'createdAt' => new \DateTime()
        ];
        
        $this->synonymRepository->create([$data], $this->context);
    }

    private function searchProducts(string $searchTerm): array
    {
        $response = $this->request(
            'POST',
            '/store-api/search',
            [
                'search' => $searchTerm
            ]
        );
        
        static::assertSame(200, $response->getStatusCode());
        
        return json_decode($response->getContent(), true);
    }
}