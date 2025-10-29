<?php declare(strict_types=1);

namespace ProductSetManager\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\PriceCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class ProductSetService
{
    private HttpClientInterface $httpClient;
    private EntityRepository $productRepository;
    private LoggerInterface $logger;
    private string $apiUrl;

    public function __construct(
        HttpClientInterface $httpClient,
        EntityRepository $productRepository,
        LoggerInterface $logger,
        string $apiUrl = 'http://localhost/api/product-set.php'
    ) {
        $this->httpClient = $httpClient;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->apiUrl = $apiUrl;
    }

    public function processProductSet(
        LineItem $setLineItem,
        string $setCode,
        Cart $cart,
        SalesChannelContext $context
    ): void {
        $this->logger->info('Processing product set - start', [
            'setCode' => $setCode,
            'productId' => $setLineItem->getReferencedId()
        ]);
        
        // Rufe externe API auf um Set-Informationen zu erhalten
        $setData = $this->fetchSetData($setCode);
        
        if (!$setData || !isset($setData['components']) || !isset($setData['totalPrice'])) {
            throw new \RuntimeException('Invalid set data received from API');
        }

        $this->logger->info('Set data retrieved', [
            'totalPrice' => $setData['totalPrice'],
            'componentsCount' => count($setData['components'])
        ]);

        // Erstelle Set-Container LineItem
        $setContainerLineItem = new LineItem(
            'set_' . $setLineItem->getReferencedId() . '_' . uniqid(),
            LineItem::PRODUCT_LINE_ITEM_TYPE, // Wichtig: verwende PRODUCT_LINE_ITEM_TYPE
            $setLineItem->getReferencedId(),
            $setLineItem->getQuantity()
        );
        
        $setContainerLineItem->setLabel($setLineItem->getLabel() . ' (Set)');
        $setContainerLineItem->setRemovable(true);
        $setContainerLineItem->setStackable(false);
        
        // Setze den Preis für das Set
        $this->setLineItemPrice($setContainerLineItem, $setData['totalPrice'], $context);
        
        // Füge Komponenten als Child-LineItems hinzu
        $children = new LineItemCollection();
        
        foreach ($setData['components'] as $component) {
            // Multipliziere die Komponentenmenge mit der Set-Menge
            $componentData = $component;
            $componentData['quantity'] = $component['quantity'] * $setLineItem->getQuantity();
            
            $childLineItem = $this->createComponentLineItem($componentData, $context);
            if ($childLineItem) {
                $children->add($childLineItem);
                $this->logger->info('Added component to set', [
                    'productNumber' => $component['productNumber'],
                    'quantity' => $componentData['quantity']
                ]);
            }
        }
        
        $setContainerLineItem->setChildren($children);
        
        // Füge das Set zum Warenkorb hinzu
        $cart->add($setContainerLineItem);
        
        $this->logger->info('Product set processed successfully', [
            'setId' => $setContainerLineItem->getId(),
            'childrenCount' => $children->count()
        ]);
    }

    private function fetchSetData(string $setCode): ?array
    {
        try {
            $response = $this->httpClient->request('GET', $this->apiUrl, [
                'query' => [
                    'code' => $setCode
                ]
            ]);
            
            $data = $response->toArray();
            
            return $data;
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch set data from API', [
                'setCode' => $setCode,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    private function createComponentLineItem(array $componentData, SalesChannelContext $context): ?LineItem
    {
        if (!isset($componentData['productNumber']) || !isset($componentData['quantity'])) {
            return null;
        }

        // Lade Produkt anhand der Produktnummer
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber', $componentData['productNumber']));
        
        $product = $this->productRepository->search($criteria, $context->getContext())->first();
        
        if (!$product instanceof ProductEntity) {
            $this->logger->warning('Product not found for component', [
                'productNumber' => $componentData['productNumber']
            ]);
            return null;
        }

        // Prüfe Bestand
        if ($product->getAvailableStock() < $componentData['quantity']) {
            throw new \RuntimeException(sprintf(
                'Insufficient stock for product %s. Available: %d, Required: %d',
                $product->getName(),
                $product->getAvailableStock(),
                $componentData['quantity']
            ));
        }

        // Erstelle LineItem für Komponente
        $lineItem = new LineItem(
            'component_' . $product->getId() . '_' . uniqid(),
            LineItem::PRODUCT_LINE_ITEM_TYPE,
            $product->getId(),
            $componentData['quantity']
        );
        
        $lineItem->setLabel($product->getName());
        $lineItem->setRemovable(false);
        $lineItem->setStackable(false);
        
        // Wichtig für Bestandsführung: setze die korrekten Payloads
        $lineItem->setPayloadValue('productNumber', $product->getProductNumber());
        $lineItem->setPayloadValue('purchaseSteps', 1);
        $lineItem->setPayloadValue('minPurchase', 1);
        $lineItem->setPayloadValue('maxPurchase', $product->getMaxPurchase() ?? 100);
        $lineItem->setPayloadValue('isCloseout', $product->getIsCloseout());
        
        // Setze Preis auf 0, da der Gesamtpreis bereits im Set enthalten ist
        $this->setLineItemPrice($lineItem, 0, $context);
        
        return $lineItem;
    }

    private function setLineItemPrice(LineItem $lineItem, float $price, SalesChannelContext $context): void
    {
        $taxRules = new TaxRuleCollection();
        $taxRules->add(new \Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule($context->getTaxRules()->first()->getTaxRate()));
        
        $definition = new QuantityPriceDefinition(
            $price,
            $taxRules,
            $lineItem->getQuantity()
        );
        
        $lineItem->setPriceDefinition($definition);
        
        // Berechne den Preis
        $calculatedPrice = new CalculatedPrice(
            $price * $lineItem->getQuantity(),
            $price * $lineItem->getQuantity(),
            new CalculatedTaxCollection(),
            $taxRules,
            $lineItem->getQuantity()
        );
        
        $lineItem->setPrice($calculatedPrice);
    }
}