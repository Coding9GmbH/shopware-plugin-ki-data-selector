<?php declare(strict_types=1);

namespace ProductSetManager\Subscriber;

use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;

class OrderSubscriber implements EventSubscriberInterface
{
    private EntityRepository $productRepository;
    private LoggerInterface $logger;

    public function __construct(
        EntityRepository $productRepository,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutOrderPlacedEvent::class => ['onOrderPlaced', -100],
        ];
    }

    public function onOrderPlaced(CheckoutOrderPlacedEvent $event): void
    {
        $order = $event->getOrder();
        $context = $event->getContext();
        
        foreach ($order->getLineItems() as $lineItem) {
            // For container line items, stock is automatically handled by child items
            // So we only need to handle the old format (non-container sets)
            
            // Check if this is a set (old format without container)
            $payload = $lineItem->getPayload();
            if (isset($payload['isSet']) && $payload['isSet'] === true && $lineItem->getType() !== 'container') {
                // Get set components
                $setComponents = $payload['setComponents'] ?? [];
                if (empty($setComponents)) {
                    continue;
                }
                
                $this->logger->info('Processing legacy set order for stock management', [
                    'orderId' => $order->getId(),
                    'lineItemId' => $lineItem->getId(),
                    'setQuantity' => $lineItem->getQuantity(),
                    'components' => $setComponents
                ]);
                
                // Update stock for each component
                $this->updateComponentStock($setComponents, $lineItem->getQuantity(), $context);
            }
        }
    }
    
    private function updateComponentStock(array $components, int $setQuantity, Context $context): void
    {
        $productNumbers = array_column($components, 'productNumber');
        if (empty($productNumbers)) {
            return;
        }
        
        // Load products
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('productNumber', $productNumbers));
        $products = $this->productRepository->search($criteria, $context);
        
        $updates = [];
        foreach ($components as $component) {
            $product = $products->filter(function (ProductEntity $p) use ($component) {
                return $p->getProductNumber() === $component['productNumber'];
            })->first();
            
            if (!$product) {
                $this->logger->warning('Component product not found for stock update', [
                    'productNumber' => $component['productNumber']
                ]);
                continue;
            }
            
            // Calculate total quantity to reduce
            $quantityToReduce = $component['quantity'] * $setQuantity;
            
            // Update stock - reduce by the quantity used
            $newStock = $product->getStock() - $quantityToReduce;
            
            $updates[] = [
                'id' => $product->getId(),
                'stock' => max(0, $newStock)
            ];
            
            $this->logger->info('Updating component stock', [
                'productId' => $product->getId(),
                'productNumber' => $product->getProductNumber(),
                'oldStock' => $product->getStock(),
                'newStock' => max(0, $newStock),
                'reduced' => $quantityToReduce
            ]);
        }
        
        if (!empty($updates)) {
            $this->productRepository->update($updates, $context);
        }
    }
}