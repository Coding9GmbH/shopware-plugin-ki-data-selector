<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\IntelligentSearchOptimizer\Service\SearchQueryNormalizer;

class SearchQueryNormalizerTest extends TestCase
{
    private SearchQueryNormalizer $normalizer;
    private SystemConfigService $systemConfigService;

    protected function setUp(): void
    {
        $this->systemConfigService = $this->createMock(SystemConfigService::class);
        $this->normalizer = new SearchQueryNormalizer($this->systemConfigService);
    }

    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize(string $input, string $expected): void
    {
        $result = $this->normalizer->normalize($input);
        static::assertEquals($expected, $result);
    }

    public function normalizeDataProvider(): array
    {
        return [
            'lowercase conversion' => ['TEST', 'test'],
            'trim whitespace' => ['  test  ', 'test'],
            'multiple spaces' => ['test  product', 'test product'],
            'special characters removed' => ['test@product!', 'testproduct'],
            'hyphens preserved' => ['test-product', 'test-product'],
            'unicode support' => ['Tëst-Pröduct', 'tëst-pröduct'],
            'numbers preserved' => ['test123', 'test123'],
        ];
    }

    /**
     * @dataProvider generateSearchVariationsDataProvider
     */
    public function testGenerateSearchVariations(string $input, array $expected, bool $enabled = true): void
    {
        $this->systemConfigService
            ->method('get')
            ->willReturn($enabled);

        $result = $this->normalizer->generateSearchVariations($input);
        
        static::assertEqualsCanonicalizing($expected, $result);
    }

    public function generateSearchVariationsDataProvider(): array
    {
        return [
            'with hyphen' => [
                'test-product',
                ['test-product', 'test product']
            ],
            'with space' => [
                'test product',
                ['test product', 'test-product']
            ],
            'multiple hyphens' => [
                'test-product-name',
                ['test-product-name', 'test product name']
            ],
            'multiple spaces' => [
                'test product name',
                ['test product name', 'test-product-name']
            ],
            'mixed hyphens and spaces' => [
                'test-product name',
                ['test-product name', 'test product name', 'test-product-name']
            ],
            'no hyphens or spaces' => [
                'testproduct',
                ['testproduct']
            ],
            'disabled feature' => [
                'test-product',
                ['test-product'],
                false
            ],
        ];
    }

    public function testGenerateSearchVariationsWithDisabledFeature(): void
    {
        $this->systemConfigService
            ->method('get')
            ->with('IntelligentSearchOptimizer.config.enableHyphenSpaceNormalization', null)
            ->willReturn(false);

        $result = $this->normalizer->generateSearchVariations('test-product');
        
        static::assertEquals(['test-product'], $result);
    }

    public function testGenerateSearchVariationsWithSalesChannelConfig(): void
    {
        $salesChannelId = 'test-channel-id';
        
        $this->systemConfigService
            ->expects(static::once())
            ->method('get')
            ->with('IntelligentSearchOptimizer.config.enableHyphenSpaceNormalization', $salesChannelId)
            ->willReturn(true);

        $result = $this->normalizer->generateSearchVariations('test-product', $salesChannelId);
        
        static::assertContains('test product', $result);
    }
}