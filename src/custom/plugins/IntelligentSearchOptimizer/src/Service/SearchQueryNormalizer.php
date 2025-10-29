<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;

class SearchQueryNormalizer
{
    private SystemConfigService $systemConfigService;
    
    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }
    
    public function normalize(string $searchTerm): string
    {
        $normalized = mb_strtolower(trim($searchTerm));
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        // Keep alphanumeric, spaces, and common separators
        $normalized = preg_replace('/[^\p{L}\p{N}\s\-\_\.\,\&\+\/]/u', '', $normalized);
        
        return $normalized;
    }
    
    /**
     * Generate search variations by replacing configured characters
     */
    public function generateSearchVariations(string $searchTerm, ?string $salesChannelId = null): array
    {
        $enabled = $this->systemConfigService->get(
            'IntelligentSearchOptimizer.config.enableCharacterNormalization',
            $salesChannelId
        ) ?? true;
        
        if (!$enabled) {
            return [$searchTerm];
        }
        
        // Get configured character mappings
        $characterMappings = $this->getCharacterMappings($salesChannelId);
        
        if (empty($characterMappings)) {
            // Default behavior if no mappings configured
            $characterMappings = [['-', ' ']];
        }
        
        $variations = [$searchTerm];
        
        foreach ($characterMappings as $mapping) {
            if (!is_array($mapping) || count($mapping) < 2) {
                continue;
            }
            
            $newVariations = [];
            foreach ($variations as $variant) {
                // Generate all possible replacements for this mapping
                $generatedVariants = $this->generateVariantsForMapping($variant, $mapping);
                $newVariations = array_merge($newVariations, $generatedVariants);
            }
            
            $variations = array_merge($variations, $newVariations);
        }
        
        // Add fully normalized version
        $fullyNormalized = $this->fullyNormalize($searchTerm, $characterMappings);
        if (!in_array($fullyNormalized, $variations)) {
            $variations[] = $fullyNormalized;
        }
        
        return array_unique($variations);
    }
    
    /**
     * Get character mappings from configuration
     */
    private function getCharacterMappings(?string $salesChannelId): array
    {
        $mappingsConfig = $this->systemConfigService->get(
            'IntelligentSearchOptimizer.config.characterMappings',
            $salesChannelId
        );
        
        if (empty($mappingsConfig)) {
            return [];
        }
        
        // Parse configuration string into mappings
        $mappings = [];
        $lines = explode("\n", $mappingsConfig);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue; // Skip empty lines and comments
            }
            
            // Support multiple formats:
            // "- " (hyphen to space)
            // "-,_,/ " (multiple characters to space)
            // "ä ae" (umlaut to ascii)
            $parts = preg_split('/\s+/', $line, 2);
            if (count($parts) === 2) {
                $sources = explode(',', $parts[0]);
                $target = $parts[1];
                
                foreach ($sources as $source) {
                    $source = trim($source);
                    if (!empty($source)) {
                        $mappings[] = [$source, $target];
                    }
                }
            }
        }
        
        return $mappings;
    }
    
    /**
     * Generate all variants for a specific character mapping
     */
    private function generateVariantsForMapping(string $text, array $mapping): array
    {
        $variants = [];
        $source = $mapping[0];
        $target = $mapping[1];
        
        // Only generate variant if source character exists in text
        if (strpos($text, $source) !== false) {
            $variants[] = str_replace($source, $target, $text);
        }
        
        // Also check reverse mapping if configured
        $bidirectional = $this->systemConfigService->get(
            'IntelligentSearchOptimizer.config.bidirectionalMapping',
            null
        ) ?? true;
        
        if ($bidirectional && strpos($text, $target) !== false && $target !== $source) {
            $variants[] = str_replace($target, $source, $text);
        }
        
        return $variants;
    }
    
    /**
     * Create a fully normalized version using all mappings
     */
    private function fullyNormalize(string $text, array $mappings): string
    {
        $normalized = mb_strtolower($text);
        
        // Apply all mappings to create most normalized form
        foreach ($mappings as $mapping) {
            $source = $mapping[0];
            $target = $mapping[1];
            
            // For normalization, always use space as target if it's one of the options
            if ($target === ' ' || $source === ' ') {
                $normalized = str_replace($source === ' ' ? $target : $source, ' ', $normalized);
            }
        }
        
        // Clean up multiple spaces
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        return trim($normalized);
    }
}