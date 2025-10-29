<?php declare(strict_types=1);

namespace ProductSetManager\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Psr\Log\LoggerInterface;

class SetPriceProcessor implements CartProcessorInterface
{
    private LoggerInterface $logger;
    private EntityRepository $productRepository;
    private array $processedSets = [];

    public function __construct(
        LoggerInterface $logger,
        EntityRepository $productRepository
    ) {
        $this->logger = $logger;
        $this->productRepository = $productRepository;
    }

    public function process(
        CartDataCollection $data,
        Cart $original,
        Cart $toCalculate,
        SalesChannelContext $context,
        CartBehavior $behavior
    ): void {
        $lineItemsToReplace = [];
        
        foreach ($toCalculate->getLineItems() as $lineItem) {
            if ($lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }
            
            // Skip if already processed
            if (in_array($lineItem->getId(), $this->processedSets, true)) {
                continue;
            }
            
            // Prüfe ob Set-Daten vorhanden sind
            $productId = $lineItem->getReferencedId();
            if (!$productId) {
                continue;
            }
            
            $setData = $data->get('set-' . $productId);
            if (!$setData || !isset($setData['totalPrice'])) {
                continue;
            }
            
            $this->processedSets[] = $lineItem->getId();
            
            $this->logger->info('Converting to container line item', [
                'productId' => $productId,
                'price' => $setData['totalPrice']
            ]);
            
            // Create container line item
            $containerLineItem = $this->createContainerLineItem(
                $lineItem,
                $setData,
                $context
            );
            
            if ($containerLineItem) {
                $lineItemsToReplace[$lineItem->getId()] = $containerLineItem;
            }
        }
        
        // Replace product line items with container line items
        foreach ($lineItemsToReplace as $oldId => $containerLineItem) {
            $toCalculate->remove($oldId);
            $toCalculate->add($containerLineItem);
        }
    }
    
    private function createContainerLineItem(
        LineItem $originalLineItem,
        array $setData,
        SalesChannelContext $context
    ): ?LineItem {
        // Create container line item
        $containerLineItem = new LineItem(
            Uuid::randomHex(),
            LineItem::CONTAINER_LINE_ITEM,
            null,
            $originalLineItem->getQuantity()
        );
        
        $containerLineItem->setLabel($originalLineItem->getLabel() . ' (Set)');
        $containerLineItem->setRemovable(true);
        $containerLineItem->setStackable(true);
        
        // Get tax rules from original item
        $taxRules = $originalLineItem->getPrice() 
            ? $originalLineItem->getPrice()->getTaxRules() 
            : new TaxRuleCollection([new TaxRule(19)]);
        
        // Calculate net price from gross price
        $taxRate = $taxRules->first() ? $taxRules->first()->getTaxRate() : 19;
        $netPrice = $setData['totalPrice'] / (1 + ($taxRate / 100));
        
        // Set price for container (net price for calculation)
        $containerLineItem->setPriceDefinition(
            new QuantityPriceDefinition(
                $netPrice,
                $taxRules,
                $containerLineItem->getQuantity()
            )
        );
        
        // Store set data in payload
        $containerLineItem->setPayloadValue('isSet', true);
        $containerLineItem->setPayloadValue('originalProductId', $originalLineItem->getReferencedId());
        $containerLineItem->setPayloadValue('setComponents', $setData['components'] ?? []);
        
        // Add component line items
        $this->addComponentLineItems($containerLineItem, $setData, $context);
        
        return $containerLineItem;
    }
    
    private function addComponentLineItems(
        LineItem $containerLineItem,
        array $setData,
        SalesChannelContext $context
    ): void {
        $components = $setData['components'] ?? [];
        if (empty($components)) {
            return;
        }
        
        $productNumbers = array_column($components, 'productNumber');
        
        // Load products
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('productNumber', $productNumbers));
        $products = $this->productRepository->search($criteria, $context->getContext());
        
        foreach ($components as $component) {
            $product = $products->filter(function (ProductEntity $p) use ($component) {
                return $p->getProductNumber() === $component['productNumber'];
            })->first();
            
            if (!$product) {
                $this->logger->warning('Component product not found', [
                    'productNumber' => $component['productNumber']
                ]);
                continue;
            }
            
            // Create child line item
            // Child quantity must be component quantity * container quantity
            $childQuantity = $component['quantity'] * $containerLineItem->getQuantity();
            
            $childLineItem = new LineItem(
                Uuid::randomHex(),
                LineItem::PRODUCT_LINE_ITEM_TYPE,
                $product->getId(),
                $childQuantity
            );
            
            $childLineItem->setLabel($product->getTranslation('name') . ' (im Set)');
            $childLineItem->setGood(true);
            $childLineItem->setStackable(true);
            $childLineItem->setRemovable(false);
            
            // Set payload for stock management
            $childLineItem->setPayloadValue('productNumber', $product->getProductNumber());
            
            // Price 0 because it's included in container price
            $childLineItem->setPriceDefinition(
                new QuantityPriceDefinition(
                    0,
                    new TaxRuleCollection([new TaxRule(19)]),
                    $childLineItem->getQuantity()
                )
            );
            
            // Add to container
            $containerLineItem->addChild($childLineItem);
            
            $this->logger->info('Added component to container', [
                'productId' => $product->getId(),
                'componentQuantity' => $component['quantity'],
                'containerQuantity' => $containerLineItem->getQuantity(),
                'totalQuantity' => $childQuantity
            ]);
        }
    }
}