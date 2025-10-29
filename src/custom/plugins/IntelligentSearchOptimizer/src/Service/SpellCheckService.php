<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Psr\Log\LoggerInterface;

class SpellCheckService
{
    private Connection $connection;
    private EntityRepository $dictionaryRepository;
    private EntityRepository $productRepository;
    private LoggerInterface $logger;
    
    private const MIN_WORD_LENGTH = 3;
    private const LEVENSHTEIN_THRESHOLD = 2;
    private const MIN_CONFIDENCE = 0.75;
    
    public function __construct(
        Connection $connection,
        EntityRepository $dictionaryRepository,
        EntityRepository $productRepository,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->dictionaryRepository = $dictionaryRepository;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }
    
    /**
     * Get spelling suggestions for a search term
     */
    public function getSuggestions(string $searchTerm, string $languageId = 'en-GB', Context $context = null): array
    {
        if (!$context) {
            $context = Context::createDefaultContext();
        }
        
        $searchTerm = trim(mb_strtolower($searchTerm));
        if (strlen($searchTerm) < self::MIN_WORD_LENGTH) {
            return [];
        }
        
        // First check if the term exists in our dictionary
        if ($this->isValidWord($searchTerm, $languageId)) {
            return [];
        }
        
        // Check for cached corrections
        $cachedSuggestion = $this->getCachedCorrection($searchTerm, $languageId);
        if ($cachedSuggestion) {
            return [$cachedSuggestion];
        }
        
        // Find suggestions using multiple strategies
        $suggestions = [];
        
        // 1. Levenshtein distance on dictionary
        $suggestions = array_merge($suggestions, $this->findSimilarWords($searchTerm, $languageId));
        
        // 2. Check product names and brands
        $suggestions = array_merge($suggestions, $this->findInProducts($searchTerm, $context));
        
        // 3. Common misspelling patterns
        $suggestions = array_merge($suggestions, $this->applyCommonPatterns($searchTerm));
        
        // Score and rank suggestions
        $rankedSuggestions = $this->rankSuggestions($searchTerm, $suggestions);
        
        // Cache the best suggestion if confidence is high enough
        if (!empty($rankedSuggestions) && $rankedSuggestions[0]['confidence'] >= self::MIN_CONFIDENCE) {
            $this->cacheCorrection($searchTerm, $rankedSuggestions[0]['word'], $rankedSuggestions[0]['confidence'], $languageId);
        }
        
        return array_slice($rankedSuggestions, 0, 3);
    }
    
    /**
     * Add a word to the dictionary
     */
    public function addToDictionary(string $word, string $languageId = 'en-GB', ?string $type = null): void
    {
        $word = trim(mb_strtolower($word));
        if (strlen($word) < self::MIN_WORD_LENGTH) {
            return;
        }
        
        try {
            $this->connection->executeStatement('
                INSERT INTO search_optimizer_dictionary (id, word, frequency, language, type, created_at)
                VALUES (:id, :word, 1, :language, :type, :created_at)
                ON DUPLICATE KEY UPDATE 
                    frequency = frequency + 1,
                    updated_at = :updated_at
            ', [
                'id' => Uuid::randomBytes(),
                'word' => $word,
                'language' => $languageId,
                'type' => $type,
                'created_at' => (new \DateTime())->format('Y-m-d H:i:s.v'),
                'updated_at' => (new \DateTime())->format('Y-m-d H:i:s.v'),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to add word to dictionary', [
                'word' => $word,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Learn from successful searches
     */
    public function learnFromSearch(string $searchTerm, int $resultCount, string $languageId = 'en-GB'): void
    {
        if ($resultCount > 0) {
            // Add successful search terms to dictionary
            $words = $this->tokenizeSearchTerm($searchTerm);
            foreach ($words as $word) {
                $this->addToDictionary($word, $languageId);
            }
        }
    }
    
    private function isValidWord(string $word, string $languageId): bool
    {
        $result = $this->connection->fetchOne('
            SELECT 1 FROM search_optimizer_dictionary
            WHERE word = :word AND language = :language
            LIMIT 1
        ', [
            'word' => $word,
            'language' => $languageId
        ]);
        
        return (bool) $result;
    }
    
    private function getCachedCorrection(string $misspelling, string $languageId): ?string
    {
        return $this->connection->fetchOne('
            SELECT correction FROM search_optimizer_spell_corrections
            WHERE misspelling = :misspelling AND language = :language
            ORDER BY confidence DESC, usage_count DESC
            LIMIT 1
        ', [
            'misspelling' => $misspelling,
            'language' => $languageId
        ]) ?: null;
    }
    
    private function findSimilarWords(string $searchTerm, string $languageId): array
    {
        $suggestions = [];
        
        // For performance, only check words with similar length
        $minLength = max(self::MIN_WORD_LENGTH, strlen($searchTerm) - 2);
        $maxLength = strlen($searchTerm) + 2;
        
        $words = $this->connection->fetchAllAssociative('
            SELECT word, frequency FROM search_optimizer_dictionary
            WHERE language = :language
            AND CHAR_LENGTH(word) BETWEEN :minLength AND :maxLength
            ORDER BY frequency DESC
            LIMIT 1000
        ', [
            'language' => $languageId,
            'minLength' => $minLength,
            'maxLength' => $maxLength
        ]);
        
        foreach ($words as $wordData) {
            $distance = levenshtein($searchTerm, $wordData['word']);
            if ($distance <= self::LEVENSHTEIN_THRESHOLD && $distance > 0) {
                $suggestions[] = [
                    'word' => $wordData['word'],
                    'distance' => $distance,
                    'frequency' => $wordData['frequency']
                ];
            }
        }
        
        return $suggestions;
    }
    
    private function findInProducts(string $searchTerm, Context $context): array
    {
        $suggestions = [];
        
        // Search in product names and manufacturer names
        $sql = '
            SELECT DISTINCT 
                pt.name,
                pm.name as manufacturer_name
            FROM product p
            LEFT JOIN product_translation pt ON p.id = pt.product_id
            LEFT JOIN product_manufacturer pm ON p.product_manufacturer_id = pm.id
            WHERE p.active = 1
            AND (
                LOWER(pt.name) LIKE :searchPattern
                OR LOWER(pm.name) LIKE :searchPattern
            )
            LIMIT 50
        ';
        
        $results = $this->connection->fetchAllAssociative($sql, [
            'searchPattern' => '%' . $searchTerm . '%'
        ]);
        
        foreach ($results as $result) {
            // Extract potential corrections from product names
            $words = array_merge(
                $this->tokenizeSearchTerm($result['name'] ?? ''),
                $this->tokenizeSearchTerm($result['manufacturer_name'] ?? '')
            );
            
            foreach ($words as $word) {
                $distance = levenshtein($searchTerm, $word);
                if ($distance <= self::LEVENSHTEIN_THRESHOLD && $distance > 0) {
                    $suggestions[] = [
                        'word' => $word,
                        'distance' => $distance,
                        'source' => 'product'
                    ];
                }
            }
        }
        
        return $suggestions;
    }
    
    private function applyCommonPatterns(string $searchTerm): array
    {
        $suggestions = [];
        
        // Common German/English misspelling patterns
        $patterns = [
            // Double letters
            '/(.)\1+/' => '$1',
            // Common German misspellings
            '/ae/' => 'ä',
            '/oe/' => 'ö',
            '/ue/' => 'ü',
            '/ss/' => 'ß',
            // Common English misspellings
            '/teh/' => 'the',
            '/recieve/' => 'receive',
            '/occured/' => 'occurred',
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            $corrected = preg_replace($pattern, $replacement, $searchTerm);
            if ($corrected !== $searchTerm && $this->isValidWord($corrected, 'en-GB')) {
                $suggestions[] = [
                    'word' => $corrected,
                    'pattern' => true
                ];
            }
        }
        
        return $suggestions;
    }
    
    private function rankSuggestions(string $searchTerm, array $suggestions): array
    {
        $scored = [];
        
        foreach ($suggestions as $suggestion) {
            $word = $suggestion['word'];
            $score = 0;
            
            // Base score from Levenshtein distance
            if (isset($suggestion['distance'])) {
                $score = 1 - ($suggestion['distance'] / max(strlen($searchTerm), strlen($word)));
            }
            
            // Boost for frequency
            if (isset($suggestion['frequency'])) {
                $score += min(0.2, $suggestion['frequency'] / 1000);
            }
            
            // Boost for product matches
            if (isset($suggestion['source']) && $suggestion['source'] === 'product') {
                $score += 0.15;
            }
            
            // Boost for pattern matches
            if (isset($suggestion['pattern'])) {
                $score += 0.1;
            }
            
            $scored[$word] = [
                'word' => $word,
                'confidence' => min(1, $score)
            ];
        }
        
        // Remove duplicates and sort by score
        usort($scored, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });
        
        return array_values($scored);
    }
    
    private function cacheCorrection(string $misspelling, string $correction, float $confidence, string $languageId): void
    {
        try {
            $this->connection->insert('search_optimizer_spell_corrections', [
                'id' => Uuid::randomBytes(),
                'misspelling' => $misspelling,
                'correction' => $correction,
                'confidence' => $confidence,
                'usage_count' => 0,
                'auto_generated' => 1,
                'language' => $languageId,
                'created_at' => (new \DateTime())->format('Y-m-d H:i:s.v')
            ]);
        } catch (\Exception $e) {
            // Ignore duplicate key errors
            if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                $this->logger->error('Failed to cache spell correction', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    private function tokenizeSearchTerm(string $searchTerm): array
    {
        // Split by common separators and filter
        $words = preg_split('/[\s\-_\/]+/', mb_strtolower($searchTerm));
        return array_filter($words, function($word) {
            return strlen($word) >= self::MIN_WORD_LENGTH;
        });
    }
}