<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class RevenueTrackingService
{
    private Connection $connection;
    private RequestStack $requestStack;
    private LoggerInterface $logger;
    private EntityRepository $orderRepository;
    
    private const SESSION_KEY = 'search_optimizer_tracking';
    private const TRACKING_DURATION = 3600; // 1 hour
    
    public function __construct(
        Connection $connection,
        RequestStack $requestStack,
        LoggerInterface $logger,
        EntityRepository $orderRepository
    ) {
        $this->connection = $connection;
        $this->requestStack = $requestStack;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
    }
    
    /**
     * Track a search term in the session
     */
    public function trackSearch(string $searchTerm, ?string $customerId = null): void
    {
        $session = $this->requestStack->getSession();
        if (!$session) {
            return;
        }
        
        $tracking = $session->get(self::SESSION_KEY, []);
        
        $tracking[] = [
            'term' => $searchTerm,
            'timestamp' => time(),
            'customer_id' => $customerId
        ];
        
        // Keep only recent searches
        $tracking = array_filter($tracking, function($item) {
            return $item['timestamp'] > (time() - self::TRACKING_DURATION);
        });
        
        $session->set(self::SESSION_KEY, array_values($tracking));
    }
    
    /**
     * Link an order to search terms
     */
    public function trackOrderRevenue(OrderEntity $order, Context $context): void
    {
        $session = $this->requestStack->getSession();
        if (!$session) {
            return;
        }
        
        $tracking = $session->get(self::SESSION_KEY, []);
        if (empty($tracking)) {
            return;
        }
        
        $orderAmount = $order->getAmountTotal();
        $customerId = $order->getOrderCustomer() ? $order->getOrderCustomer()->getCustomerId() : null;
        $salesChannelId = $order->getSalesChannelId();
        
        // Get unique search terms from the session
        $searchTerms = [];
        $earliestTimestamp = time();
        
        foreach ($tracking as $item) {
            $searchTerms[$item['term']] = $item['timestamp'];
            if ($item['timestamp'] < $earliestTimestamp) {
                $earliestTimestamp = $item['timestamp'];
            }
        }
        
        // Track revenue for each search term
        foreach ($searchTerms as $term => $timestamp) {
            try {
                $this->connection->insert('search_optimizer_revenue_tracking', [
                    'id' => Uuid::randomBytes(),
                    'search_term' => $term,
                    'order_id' => Uuid::fromHexToBytes($order->getId()),
                    'order_amount' => $orderAmount,
                    'customer_id' => $customerId ? Uuid::fromHexToBytes($customerId) : null,
                    'sales_channel_id' => $salesChannelId ? Uuid::fromHexToBytes($salesChannelId) : null,
                    'search_to_order_time' => time() - $timestamp,
                    'created_at' => (new \DateTime())->format('Y-m-d H:i:s.v')
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Failed to track revenue for search term', [
                    'search_term' => $term,
                    'order_id' => $order->getId(),
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Clear tracking after order
        $session->set(self::SESSION_KEY, []);
    }
    
    /**
     * Get revenue statistics for search terms
     */
    public function getRevenueStats(
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null,
        ?string $salesChannelId = null
    ): array {
        $params = [];
        $conditions = [];
        
        if ($startDate) {
            $conditions[] = 'created_at >= :startDate';
            $params['startDate'] = $startDate->format('Y-m-d H:i:s');
        }
        
        if ($endDate) {
            $conditions[] = 'created_at <= :endDate';
            $params['endDate'] = $endDate->format('Y-m-d H:i:s');
        }
        
        if ($salesChannelId) {
            $conditions[] = 'sales_channel_id = :salesChannelId';
            $params['salesChannelId'] = Uuid::fromHexToBytes($salesChannelId);
        }
        
        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        $sql = "
            SELECT 
                search_term,
                COUNT(DISTINCT order_id) as order_count,
                COUNT(DISTINCT customer_id) as customer_count,
                SUM(order_amount) as total_revenue,
                AVG(order_amount) as avg_order_value,
                AVG(search_to_order_time) as avg_conversion_time
            FROM search_optimizer_revenue_tracking
            {$whereClause}
            GROUP BY search_term
            ORDER BY total_revenue DESC
        ";
        
        return $this->connection->fetchAllAssociative($sql, $params);
    }
    
    /**
     * Calculate ROI for search terms
     */
    public function calculateSearchROI(array $searchTermStats): array
    {
        $results = [];
        
        foreach ($searchTermStats as $stats) {
            $searchTerm = $stats['search_term'];
            
            // Get search frequency
            $searchCount = $this->getSearchCount($searchTerm, $stats['start_date'] ?? null, $stats['end_date'] ?? null);
            
            if ($searchCount > 0) {
                $conversionRate = ($stats['order_count'] / $searchCount) * 100;
                $revenuePerSearch = $stats['total_revenue'] / $searchCount;
                
                $results[] = [
                    'search_term' => $searchTerm,
                    'search_count' => $searchCount,
                    'order_count' => $stats['order_count'],
                    'total_revenue' => $stats['total_revenue'],
                    'conversion_rate' => round($conversionRate, 2),
                    'revenue_per_search' => round($revenuePerSearch, 2),
                    'avg_order_value' => round($stats['avg_order_value'], 2),
                    'avg_conversion_time_minutes' => round($stats['avg_conversion_time'] / 60, 1)
                ];
            }
        }
        
        // Sort by revenue per search
        usort($results, function($a, $b) {
            return $b['revenue_per_search'] <=> $a['revenue_per_search'];
        });
        
        return $results;
    }
    
    /**
     * Get top revenue-generating search terms
     */
    public function getTopRevenueTerms(int $limit = 10, ?string $salesChannelId = null): array
    {
        $params = ['limit' => $limit];
        $conditions = [];
        
        if ($salesChannelId) {
            $conditions[] = 'sales_channel_id = :salesChannelId';
            $params['salesChannelId'] = Uuid::fromHexToBytes($salesChannelId);
        }
        
        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        $sql = "
            SELECT 
                search_term,
                COUNT(DISTINCT order_id) as orders,
                SUM(order_amount) as revenue,
                AVG(order_amount) as avg_order_value
            FROM search_optimizer_revenue_tracking
            {$whereClause}
            GROUP BY search_term
            ORDER BY revenue DESC
            LIMIT :limit
        ";
        
        return $this->connection->fetchAllAssociative($sql, $params);
    }
    
    private function getSearchCount(string $searchTerm, ?\DateTime $startDate, ?\DateTime $endDate): int
    {
        $params = ['search_term' => $searchTerm];
        $conditions = ['search_term = :search_term'];
        
        if ($startDate) {
            $conditions[] = 'created_at >= :startDate';
            $params['startDate'] = $startDate->format('Y-m-d H:i:s');
        }
        
        if ($endDate) {
            $conditions[] = 'created_at <= :endDate';
            $params['endDate'] = $endDate->format('Y-m-d H:i:s');
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        
        $count = $this->connection->fetchOne("
            SELECT COUNT(*) FROM search_optimizer_log
            {$whereClause}
        ", $params);
        
        return (int) $count;
    }
}