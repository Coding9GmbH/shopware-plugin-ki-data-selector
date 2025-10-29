<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Psr\Log\LoggerInterface;

class SearchIntentService
{
    public const INTENT_INFORMATIONAL = 'informational';
    public const INTENT_TRANSACTIONAL = 'transactional';
    public const INTENT_NAVIGATIONAL = 'navigational';
    
    private Connection $connection;
    private LoggerInterface $logger;
    private array $patternCache = [];
    
    public function __construct(
        Connection $connection,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->logger = $logger;
    }
    
    /**
     * Detect the intent of a search query
     */
    public function detectIntent(string $searchTerm, ?string $languageId = null): array
    {
        $searchTerm = trim(mb_strtolower($searchTerm));
        
        // Load patterns if not cached
        if (empty($this->patternCache)) {
            $this->loadPatterns();
        }
        
        $scores = [
            self::INTENT_INFORMATIONAL => 0,
            self::INTENT_TRANSACTIONAL => 0,
            self::INTENT_NAVIGATIONAL => 0,
        ];
        
        $matchedPatterns = [];
        
        // Check against all patterns
        foreach ($this->patternCache as $pattern) {
            if ($this->matchesPattern($searchTerm, $pattern['pattern'])) {
                $scores[$pattern['intent_type']] += $pattern['priority'];
                $matchedPatterns[] = $pattern;
            }
        }
        
        // Analyze query structure for additional signals
        $structureScores = $this->analyzeQueryStructure($searchTerm);
        foreach ($structureScores as $intent => $score) {
            $scores[$intent] += $score;
        }
        
        // Determine primary intent
        $maxScore = max($scores);
        $primaryIntent = self::INTENT_TRANSACTIONAL; // Default
        
        if ($maxScore > 0) {
            $primaryIntent = array_search($maxScore, $scores);
        }
        
        // Calculate confidence
        $totalScore = array_sum($scores);
        $confidence = $totalScore > 0 ? $maxScore / $totalScore : 0;
        
        return [
            'primary_intent' => $primaryIntent,
            'confidence' => round($confidence, 2),
            'scores' => $scores,
            'matched_patterns' => $matchedPatterns,
            'recommendations' => $this->getRecommendations($primaryIntent, $searchTerm)
        ];
    }
    
    /**
     * Get recommendations based on intent
     */
    public function getRecommendations(string $intent, string $searchTerm): array
    {
        $recommendations = [];
        
        switch ($intent) {
            case self::INTENT_INFORMATIONAL:
                $recommendations[] = [
                    'action' => 'prioritize_content',
                    'description' => 'Show blog posts, guides, and help content first'
                ];
                $recommendations[] = [
                    'action' => 'include_faq',
                    'description' => 'Include relevant FAQ entries in search results'
                ];
                $recommendations[] = [
                    'action' => 'suggest_categories',
                    'description' => 'Suggest relevant content categories'
                ];
                break;
                
            case self::INTENT_TRANSACTIONAL:
                $recommendations[] = [
                    'action' => 'show_products',
                    'description' => 'Prioritize product listings'
                ];
                $recommendations[] = [
                    'action' => 'include_filters',
                    'description' => 'Show price and availability filters prominently'
                ];
                $recommendations[] = [
                    'action' => 'display_promotions',
                    'description' => 'Highlight any active promotions or discounts'
                ];
                break;
                
            case self::INTENT_NAVIGATIONAL:
                $recommendations[] = [
                    'action' => 'show_pages',
                    'description' => 'Prioritize static pages and navigation links'
                ];
                $recommendations[] = [
                    'action' => 'quick_links',
                    'description' => 'Provide quick links to common destinations'
                ];
                break;
        }
        
        return $recommendations;
    }
    
    /**
     * Update intent patterns
     */
    public function addPattern(string $pattern, string $intentType, int $priority = 50): void
    {
        try {
            $this->connection->insert('search_optimizer_intent_patterns', [
                'id' => \Shopware\Core\Framework\Uuid\Uuid::randomBytes(),
                'pattern' => $pattern,
                'intent_type' => $intentType,
                'priority' => $priority,
                'active' => 1,
                'created_at' => (new \DateTime())->format('Y-m-d H:i:s.v')
            ]);
            
            // Clear cache
            $this->patternCache = [];
        } catch (\Exception $e) {
            $this->logger->error('Failed to add intent pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Learn from user behavior
     */
    public function learnFromBehavior(string $searchTerm, string $clickedType, Context $context): void
    {
        // Map clicked content type to intent
        $intent = $this->mapClickToIntent($clickedType);
        if (!$intent) {
            return;
        }
        
        // Check if we should create a new pattern
        $existingIntent = $this->detectIntent($searchTerm);
        
        if ($existingIntent['primary_intent'] !== $intent && $existingIntent['confidence'] < 0.7) {
            // User behavior suggests different intent than detected
            // Consider creating a new pattern
            $this->suggestNewPattern($searchTerm, $intent, $clickedType);
        }
    }
    
    private function loadPatterns(): void
    {
        $patterns = $this->connection->fetchAllAssociative('
            SELECT pattern, intent_type, priority 
            FROM search_optimizer_intent_patterns 
            WHERE active = 1 
            ORDER BY priority DESC
        ');
        
        $this->patternCache = $patterns;
    }
    
    private function matchesPattern(string $searchTerm, string $pattern): bool
    {
        // Convert SQL LIKE pattern to regex
        $regex = str_replace(['%', '_'], ['.*', '.'], $pattern);
        $regex = '/^' . $regex . '$/i';
        
        return (bool) preg_match($regex, $searchTerm);
    }
    
    private function analyzeQueryStructure(string $searchTerm): array
    {
        $scores = [
            self::INTENT_INFORMATIONAL => 0,
            self::INTENT_TRANSACTIONAL => 0,
            self::INTENT_NAVIGATIONAL => 0,
        ];
        
        // Question words suggest informational intent
        if (preg_match('/^(what|how|why|when|where|was|wie|warum|wann|wo)\s/i', $searchTerm)) {
            $scores[self::INTENT_INFORMATIONAL] += 50;
        }
        
        // Numbers and specific product codes suggest transactional
        if (preg_match('/\b\d{3,}\b/', $searchTerm) || preg_match('/\b[A-Z]{2,}-?\d{2,}\b/', $searchTerm)) {
            $scores[self::INTENT_TRANSACTIONAL] += 30;
        }
        
        // Brand names at the beginning suggest navigational
        if (preg_match('/^(contact|support|about|kontakt|über uns|impressum)/i', $searchTerm)) {
            $scores[self::INTENT_NAVIGATIONAL] += 40;
        }
        
        // Short queries (1-2 words) are often navigational or transactional
        $wordCount = str_word_count($searchTerm);
        if ($wordCount <= 2) {
            $scores[self::INTENT_TRANSACTIONAL] += 20;
            $scores[self::INTENT_NAVIGATIONAL] += 10;
        }
        
        return $scores;
    }
    
    private function mapClickToIntent(string $clickedType): ?string
    {
        $mapping = [
            'product' => self::INTENT_TRANSACTIONAL,
            'category' => self::INTENT_TRANSACTIONAL,
            'cms_page' => self::INTENT_INFORMATIONAL,
            'blog' => self::INTENT_INFORMATIONAL,
            'landing_page' => self::INTENT_NAVIGATIONAL,
        ];
        
        return $mapping[$clickedType] ?? null;
    }
    
    private function suggestNewPattern(string $searchTerm, string $intent, string $context): void
    {
        // Log potential new pattern for manual review
        $this->logger->info('Potential new intent pattern detected', [
            'search_term' => $searchTerm,
            'suggested_intent' => $intent,
            'context' => $context
        ]);
        
        // Could be extended to automatically create patterns with admin approval
    }
}