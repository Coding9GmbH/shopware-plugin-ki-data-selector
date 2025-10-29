<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Tests\Unit\Decorator;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\SearchKeyword\ProductSearchBuilderInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use Swag\IntelligentSearchOptimizer\Decorator\ProductSearchBuilderDecorator;
use Swag\IntelligentSearchOptimizer\Service\SearchQueryNormalizer;
use Swag\IntelligentSearchOptimizer\Service\SynonymService;
use Shopware\Core\Framework\Uuid\Uuid;

class ProductSearchBuilderDecoratorTest extends TestCase
{
    private ProductSearchBuilderDecorator $decorator;
    private ProductSearchBuilderInterface $innerService;
    private SynonymService $synonymService;
    private SearchQueryNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->innerService = $this->createMock(ProductSearchBuilderInterface::class);
        $this->synonymService = $this->createMock(SynonymService::class);
        $this->normalizer = $this->createMock(SearchQueryNormalizer::class);

        $this->decorator = new ProductSearchBuilderDecorator(
            $this->innerService,
            $this->synonymService,
            $this->normalizer
        );
    }

    public function testBuildWithNoSearchTerm(): void
    {
        $request = new Request();
        $criteria = new Criteria();
        $context = $this->createSalesChannelContext();

        $this->innerService
            ->expects(static::once())
            ->method('build')
            ->with($request, $criteria, $context);

        $this->normalizer
            ->expects(static::never())
            ->method('generateSearchVariations');

        $this->decorator->build($request, $criteria, $context);
    }

    public function testBuildWithSearchTermAndVariations(): void
    {
        $request = new Request(['search' => 'test-product']);
        $criteria = new Criteria();
        $context = $this->createSalesChannelContext();

        $this->innerService
            ->expects(static::once())
            ->method('build');

        $this->normalizer
            ->expects(static::once())
            ->method('generateSearchVariations')
            ->with('test-product', $context->getSalesChannelId())
            ->willReturn(['test-product', 'test product']);

        $this->normalizer
            ->expects(static::exactly(2))
            ->method('normalize')
            ->willReturnOnConsecutiveCalls('test-product', 'test product');

        $this->synonymService
            ->expects(static::exactly(2))
            ->method('getAllSynonymsForTerm')
            ->willReturn(['test-product']);

        $this->decorator->build($request, $criteria, $context);

        // Verify that queries were added to criteria
        $queries = $criteria->getQueries();
        static::assertNotEmpty($queries);
    }

    public function testBuildWithSynonyms(): void
    {
        $request = new Request(['search' => 'mobile']);
        $criteria = new Criteria();
        $context = $this->createSalesChannelContext();

        $this->innerService->expects(static::once())->method('build');

        $this->normalizer
            ->expects(static::once())
            ->method('generateSearchVariations')
            ->willReturn(['mobile']);

        $this->normalizer
            ->expects(static::once())
            ->method('normalize')
            ->willReturn('mobile');

        $this->synonymService
            ->expects(static::once())
            ->method('getAllSynonymsForTerm')
            ->willReturn(['mobile', 'phone', 'smartphone']);

        $this->normalizer
            ->expects(static::exactly(3))
            ->method('generateSearchVariations')
            ->willReturnCallback(function ($term) {
                return [$term];
            });

        $this->decorator->build($request, $criteria, $context);

        $queries = $criteria->getQueries();
        static::assertNotEmpty($queries);
    }

    public function testBuildWithHyphenSpaceVariations(): void
    {
        $request = new Request(['search' => 'test-product-name']);
        $criteria = new Criteria();
        $context = $this->createSalesChannelContext();

        $this->innerService->expects(static::once())->method('build');

        $this->normalizer
            ->expects(static::any())
            ->method('generateSearchVariations')
            ->willReturnCallback(function ($term) {
                if ($term === 'test-product-name') {
                    return ['test-product-name', 'test product name'];
                }
                return [$term];
            });

        $this->normalizer
            ->expects(static::any())
            ->method('normalize')
            ->willReturnCallback(function ($term) {
                return strtolower($term);
            });

        $this->synonymService
            ->expects(static::any())
            ->method('getAllSynonymsForTerm')
            ->willReturnCallback(function ($term) {
                return [$term];
            });

        $this->decorator->build($request, $criteria, $context);

        $queries = $criteria->getQueries();
        static::assertNotEmpty($queries);
    }

    private function createSalesChannelContext(): SalesChannelContext
    {
        $context = $this->createMock(SalesChannelContext::class);
        $innerContext = $this->createMock(Context::class);
        
        $context->method('getSalesChannelId')->willReturn(Uuid::randomHex());
        $context->method('getContext')->willReturn($innerContext);
        
        $innerContext->method('getLanguageId')->willReturn(Uuid::randomHex());

        return $context;
    }
}