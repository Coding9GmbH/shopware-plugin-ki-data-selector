<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Content\Mail\Service\AbstractMailService;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Psr\Log\LoggerInterface;

class TrendingSearchService
{
    private Connection $connection;
    private SystemConfigService $systemConfigService;
    private AbstractMailService $mailService;
    private LoggerInterface $logger;
    
    private const TREND_THRESHOLD = 200; // 200% increase
    private const MIN_SEARCHES_FOR_TREND = 10;
    
    public function __construct(
        Connection $connection,
        SystemConfigService $systemConfigService,
        AbstractMailService $mailService,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->systemConfigService = $systemConfigService;
        $this->mailService = $mailService;
        $this->logger = $logger;
    }
    
    /**
     * Analyze search trends for the current hour
     */
    public function analyzeTrends(?string $salesChannelId = null): array
    {
        $currentHour = new \DateTime();
        $currentHour->setTime((int)$currentHour->format('H'), 0, 0);
        
        $previousHour = clone $currentHour;
        $previousHour->modify('-1 hour');
        
        // Get search counts for current hour
        $currentSearches = $this->getSearchCountsByHour($currentHour, $salesChannelId);
        
        // Get search counts for previous hour
        $previousSearches = $this->getSearchCountsByHour($previousHour, $salesChannelId);
        
        $trends = [];
        
        foreach ($currentSearches as $term => $currentCount) {
            $previousCount = $previousSearches[$term] ?? 0;
            
            // Calculate trend
            if ($previousCount > 0) {
                $trendScore = (($currentCount - $previousCount) / $previousCount) * 100;
            } else {
                $trendScore = $currentCount >= self::MIN_SEARCHES_FOR_TREND ? 100 : 0;
            }
            
            // Store trending data
            $this->storeTrendData($term, $currentHour, $currentCount, $previousCount, $trendScore, $salesChannelId);
            
            if ($trendScore >= self::TREND_THRESHOLD && $currentCount >= self::MIN_SEARCHES_FOR_TREND) {
                $trends[] = [
                    'search_term' => $term,
                    'current_count' => $currentCount,
                    'previous_count' => $previousCount,
                    'trend_score' => round($trendScore, 2),
                    'percentage_change' => round($trendScore, 0) . '%'
                ];
            }
        }
        
        // Sort by trend score
        usort($trends, function($a, $b) {
            return $b['trend_score'] <=> $a['trend_score'];
        });
        
        // Send alerts if configured
        if (!empty($trends)) {
            $this->sendTrendAlerts($trends, $salesChannelId);
        }
        
        return $trends;
    }
    
    /**
     * Get trending searches for a time period
     */
    public function getTrendingSearches(
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null,
        ?string $salesChannelId = null,
        int $limit = 20
    ): array {
        if (!$startDate) {
            $startDate = new \DateTime('-24 hours');
        }
        if (!$endDate) {
            $endDate = new \DateTime();
        }
        
        $params = [
            'startDate' => $startDate->format('Y-m-d H:i:s'),
            'endDate' => $endDate->format('Y-m-d H:i:s'),
            'limit' => $limit
        ];
        
        $conditions = ['hour_timestamp BETWEEN :startDate AND :endDate'];
        
        if ($salesChannelId) {
            $conditions[] = 'sales_channel_id = :salesChannelId';
            $params['salesChannelId'] = Uuid::fromHexToBytes($salesChannelId);
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        
        $sql = "
            SELECT 
                search_term,
                MAX(trend_score) as max_trend_score,
                SUM(search_count) as total_searches,
                COUNT(DISTINCT hour_timestamp) as active_hours
            FROM search_optimizer_trending
            {$whereClause}
            GROUP BY search_term
            HAVING max_trend_score > 0
            ORDER BY max_trend_score DESC
            LIMIT :limit
        ";
        
        return $this->connection->fetchAllAssociative($sql, $params);
    }
    
    /**
     * Get real-time trending searches (last hour)
     */
    public function getRealTimeTrends(?string $salesChannelId = null): array
    {
        $currentHour = new \DateTime();
        $currentHour->setTime((int)$currentHour->format('H'), 0, 0);
        
        $params = ['hour' => $currentHour->format('Y-m-d H:i:s')];
        $conditions = ['hour_timestamp = :hour'];
        
        if ($salesChannelId) {
            $conditions[] = 'sales_channel_id = :salesChannelId';
            $params['salesChannelId'] = Uuid::fromHexToBytes($salesChannelId);
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        
        $sql = "
            SELECT 
                search_term,
                search_count,
                previous_hour_count,
                trend_score,
                created_at
            FROM search_optimizer_trending
            {$whereClause}
            AND trend_score > 50
            ORDER BY trend_score DESC
            LIMIT 10
        ";
        
        $results = $this->connection->fetchAllAssociative($sql, $params);
        
        // Add time ago calculation
        $now = new \DateTime();
        foreach ($results as &$result) {
            $createdAt = new \DateTime($result['created_at']);
            $diff = $now->diff($createdAt);
            $result['time_ago'] = $this->formatTimeAgo($diff);
        }
        
        return $results;
    }
    
    private function getSearchCountsByHour(\DateTime $hour, ?string $salesChannelId): array
    {
        $nextHour = clone $hour;
        $nextHour->modify('+1 hour');
        
        $params = [
            'startTime' => $hour->format('Y-m-d H:i:s'),
            'endTime' => $nextHour->format('Y-m-d H:i:s')
        ];
        
        $conditions = ['created_at >= :startTime AND created_at < :endTime'];
        
        if ($salesChannelId) {
            $conditions[] = 'sales_channel_id = :salesChannelId';
            $params['salesChannelId'] = Uuid::fromHexToBytes($salesChannelId);
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        
        $sql = "
            SELECT 
                search_term,
                COUNT(*) as search_count
            FROM search_optimizer_log
            {$whereClause}
            GROUP BY search_term
        ";
        
        $results = $this->connection->fetchAllAssociative($sql, $params);
        
        $searchCounts = [];
        foreach ($results as $result) {
            $searchCounts[$result['search_term']] = (int)$result['search_count'];
        }
        
        return $searchCounts;
    }
    
    private function storeTrendData(
        string $searchTerm,
        \DateTime $hour,
        int $currentCount,
        int $previousCount,
        float $trendScore,
        ?string $salesChannelId
    ): void {
        try {
            $this->connection->insert('search_optimizer_trending', [
                'id' => Uuid::randomBytes(),
                'search_term' => $searchTerm,
                'hour_timestamp' => $hour->format('Y-m-d H:i:s'),
                'search_count' => $currentCount,
                'previous_hour_count' => $previousCount,
                'trend_score' => $trendScore,
                'sales_channel_id' => $salesChannelId ? Uuid::fromHexToBytes($salesChannelId) : null,
                'alert_sent' => 0,
                'created_at' => (new \DateTime())->format('Y-m-d H:i:s.v')
            ]);
        } catch (\Exception $e) {
            // Update if exists
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $this->connection->update('search_optimizer_trending', 
                    [
                        'search_count' => $currentCount,
                        'previous_hour_count' => $previousCount,
                        'trend_score' => $trendScore,
                        'created_at' => (new \DateTime())->format('Y-m-d H:i:s.v')
                    ],
                    [
                        'search_term' => $searchTerm,
                        'hour_timestamp' => $hour->format('Y-m-d H:i:s'),
                        'sales_channel_id' => $salesChannelId ? Uuid::fromHexToBytes($salesChannelId) : null
                    ]
                );
            } else {
                $this->logger->error('Failed to store trend data', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    private function sendTrendAlerts(array $trends, ?string $salesChannelId): void
    {
        $alertEmail = $this->systemConfigService->get(
            'IntelligentSearchOptimizer.config.trendingAlertEmail',
            $salesChannelId
        );
        
        if (!$alertEmail) {
            return;
        }
        
        // Check if alerts already sent
        $alreadyAlerted = $this->getAlreadyAlertedTerms($trends, $salesChannelId);
        $newTrends = array_filter($trends, function($trend) use ($alreadyAlerted) {
            return !in_array($trend['search_term'], $alreadyAlerted);
        });
        
        if (empty($newTrends)) {
            return;
        }
        
        try {
            // Prepare email content
            $content = $this->prepareTrendAlertContent($newTrends);
            
            // Send email
            $data = [
                'recipients' => [$alertEmail => $alertEmail],
                'senderName' => 'Search Optimizer',
                'subject' => 'Trending Search Alert',
                'contentHtml' => $content,
                'contentPlain' => strip_tags($content)
            ];
            
            $this->mailService->send($data, \Shopware\Core\Framework\Context::createDefaultContext());
            
            // Mark as alerted
            $this->markAsAlerted($newTrends, $salesChannelId);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to send trend alert', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    private function getAlreadyAlertedTerms(array $trends, ?string $salesChannelId): array
    {
        $currentHour = new \DateTime();
        $currentHour->setTime((int)$currentHour->format('H'), 0, 0);
        
        $terms = array_column($trends, 'search_term');
        
        $params = [
            'hour' => $currentHour->format('Y-m-d H:i:s'),
            'terms' => $terms
        ];
        
        $conditions = [
            'hour_timestamp = :hour',
            'alert_sent = 1'
        ];
        
        if ($salesChannelId) {
            $conditions[] = 'sales_channel_id = :salesChannelId';
            $params['salesChannelId'] = Uuid::fromHexToBytes($salesChannelId);
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        
        $sql = "
            SELECT search_term 
            FROM search_optimizer_trending
            {$whereClause}
            AND search_term IN (:terms)
        ";
        
        $stmt = $this->connection->executeQuery($sql, $params, ['terms' => Connection::PARAM_STR_ARRAY]);
        return $stmt->fetchFirstColumn();
    }
    
    private function markAsAlerted(array $trends, ?string $salesChannelId): void
    {
        $currentHour = new \DateTime();
        $currentHour->setTime((int)$currentHour->format('H'), 0, 0);
        
        foreach ($trends as $trend) {
            $conditions = [
                'search_term' => $trend['search_term'],
                'hour_timestamp' => $currentHour->format('Y-m-d H:i:s')
            ];
            
            if ($salesChannelId) {
                $conditions['sales_channel_id'] = Uuid::fromHexToBytes($salesChannelId);
            }
            
            $this->connection->update('search_optimizer_trending', 
                ['alert_sent' => 1],
                $conditions
            );
        }
    }
    
    private function prepareTrendAlertContent(array $trends): string
    {
        $html = '<h2>Trending Search Terms Detected</h2>';
        $html .= '<p>The following search terms are trending significantly:</p>';
        $html .= '<table style="border-collapse: collapse; width: 100%;">';
        $html .= '<tr style="background-color: #f2f2f2;">';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px;">Search Term</th>';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px;">Current Hour</th>';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px;">Previous Hour</th>';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px;">Change</th>';
        $html .= '</tr>';
        
        foreach ($trends as $trend) {
            $html .= '<tr>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . htmlspecialchars($trend['search_term']) . '</td>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $trend['current_count'] . '</td>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $trend['previous_count'] . '</td>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px; color: #28a745; font-weight: bold;">+' . $trend['percentage_change'] . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        
        return $html;
    }
    
    private function formatTimeAgo(\DateInterval $diff): string
    {
        if ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        } elseif ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        } else {
            return 'just now';
        }
    }
}