<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Subscriber;

use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Order\OrderEntity;
use Swag\IntelligentSearchOptimizer\Service\RevenueTrackingService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;

class OrderEventSubscriber implements EventSubscriberInterface
{
    private RevenueTrackingService $revenueTrackingService;
    private LoggerInterface $logger;
    
    public function __construct(
        RevenueTrackingService $revenueTrackingService,
        LoggerInterface $logger
    ) {
        $this->revenueTrackingService = $revenueTrackingService;
        $this->logger = $logger;
    }
    
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutOrderPlacedEvent::class => 'onOrderPlaced',
        ];
    }
    
    public function onOrderPlaced(CheckoutOrderPlacedEvent $event): void
    {
        try {
            $order = $event->getOrder();
            $context = $event->getContext();
            
            // Track revenue for search terms
            $this->revenueTrackingService->trackOrderRevenue($order, $context);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to track order revenue', [
                'order_id' => $event->getOrder()->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }
}