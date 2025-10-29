<?php declare(strict_types=1);

namespace ProductSetManager\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartDataCollectorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class SetDataCollector implements CartDataCollectorInterface
{
    private EntityRepository $productRepository;
    private LoggerInterface $logger;
    private HttpClientInterface $httpClient;
    private CacheInterface $cache;
    private string $apiUrl;

    public function __construct(
        HttpClientInterface $httpClient,
        EntityRepository $productRepository,
        LoggerInterface $logger,
        CacheInterface $cache
    ) {
        $this->httpClient = $httpClient;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->cache = $cache;
        $this->apiUrl = 'http://localhost/api/product-set.php';
    }

    public function collect(
        CartDataCollection $data,
        Cart $original,
        SalesChannelContext $context,
        CartBehavior $behavior
    ): void {
        $productIds = [];
        
        foreach ($original->getLineItems() as $lineItem) {
            if ($lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE && $lineItem->getReferencedId()) {
                $productIds[] = $lineItem->getReferencedId();
            }
        }
        
        if (empty($productIds)) {
            return;
        }
        
        // Lade Produkte
        $criteria = new Criteria($productIds);
        $products = $this->productRepository->search($criteria, $context->getContext());
        
        foreach ($products as $product) {
            // Prüfe auf Set-Code
            $customFields = $product->getCustomFields();
            $setCode = $customFields['product_set_code'] ?? null;
            
            if (!$setCode) {
                continue;
            }
            
            // Hole Set-Daten
            $setData = $this->fetchSetData($setCode);
            if ($setData) {
                $data->set('set-' . $product->getId(), $setData);
                
                $this->logger->info('Set data collected', [
                    'productId' => $product->getId(),
                    'setCode' => $setCode,
                    'price' => $setData['totalPrice'] ?? 0
                ]);
            }
        }
    }
    
    private function fetchSetData(string $setCode): ?array
    {
        try {
            // Cache key for the set data
            $cacheKey = 'product_set_' . $setCode;
            
            // Get from cache with a TTL of 1 hour
            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($setCode) {
                $item->expiresAfter(3600); // 1 hour cache
                
                $this->logger->info('Fetching set data from API', ['setCode' => $setCode]);
                
                $response = $this->httpClient->request('GET', $this->apiUrl, [
                    'query' => ['code' => $setCode]
                ]);
                
                return $response->toArray();
            });
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch set data', [
                'setCode' => $setCode,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}