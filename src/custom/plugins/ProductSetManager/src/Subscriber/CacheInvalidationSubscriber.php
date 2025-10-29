<?php declare(strict_types=1);

namespace ProductSetManager\Subscriber;

use Shopware\Core\Checkout\Cart\Event\BeforeLineItemAddedEvent;
use Shopware\Core\Checkout\Cart\Event\BeforeLineItemQuantityChangedEvent;
use Shopware\Core\Checkout\Cart\Event\BeforeLineItemRemovedEvent;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Psr\Log\LoggerInterface;

class CacheInvalidationSubscriber implements EventSubscriberInterface
{
    private CacheInterface $cache;
    private EntityRepository $productRepository;
    private LoggerInterface $logger;

    public function __construct(
        CacheInterface $cache,
        EntityRepository $productRepository,
        LoggerInterface $logger
    ) {
        $this->cache = $cache;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeLineItemAddedEvent::class => 'onLineItemModified',
            BeforeLineItemQuantityChangedEvent::class => 'onLineItemModified',
            BeforeLineItemRemovedEvent::class => 'onLineItemModified',
        ];
    }

    public function onLineItemModified($event): void
    {
        $lineItem = $event->getLineItem();
        
        // Only process product line items
        if ($lineItem->getType() !== 'product') {
            return;
        }
        
        $productId = $lineItem->getReferencedId();
        if (!$productId) {
            return;
        }
        
        try {
            // Load product to check for set code
            $criteria = new Criteria([$productId]);
            $product = $this->productRepository->search($criteria, $event->getContext())->first();
            
            if (!$product instanceof ProductEntity) {
                return;
            }
            
            $customFields = $product->getCustomFields();
            $setCode = $customFields['product_set_code'] ?? null;
            
            if ($setCode) {
                // Invalidate cache for this set
                $cacheKey = 'product_set_' . $setCode;
                $this->cache->delete($cacheKey);
                
                $this->logger->info('Invalidated cache for product set', [
                    'setCode' => $setCode,
                    'event' => get_class($event)
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error invalidating cache', [
                'error' => $e->getMessage()
            ]);
        }
    }
}