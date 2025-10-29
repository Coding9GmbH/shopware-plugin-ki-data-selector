<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Decorator;

use Shopware\Core\Content\Product\SearchKeyword\ProductSearchBuilderInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Swag\IntelligentSearchOptimizer\Service\SynonymService;
use Swag\IntelligentSearchOptimizer\Service\SearchQueryNormalizer;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Query\ScoreQuery;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Query\ContainsQuery;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Query\MultiQuery;

class ProductSearchBuilderDecorator implements ProductSearchBuilderInterface
{
    private ProductSearchBuilderInterface $decorated;
    private SynonymService $synonymService;
    private SearchQueryNormalizer $normalizer;

    public function __construct(
        ProductSearchBuilderInterface $decorated,
        SynonymService $synonymService,
        SearchQueryNormalizer $normalizer
    ) {
        $this->decorated = $decorated;
        $this->synonymService = $synonymService;
        $this->normalizer = $normalizer;
    }

    public function build(Request $request, Criteria $criteria, SalesChannelContext $context): void
    {
        $this->decorated->build($request, $criteria, $context);
        
        $search = $request->get('search');
        
        if (!$search) {
            return;
        }
        
        // Generate search variations (with hyphens/spaces)
        $searchVariations = $this->normalizer->generateSearchVariations($search, $context->getSalesChannelId());
        
        // Collect all terms to search for
        $allSearchTerms = [];
        
        foreach ($searchVariations as $variation) {
            $normalizedSearch = $this->normalizer->normalize($variation);
            
            // Get synonyms for this variation
            $synonymTerms = $this->synonymService->getAllSynonymsForTerm(
                $normalizedSearch,
                $context->getSalesChannelId(),
                $context->getContext()->getLanguageId(),
                $context->getContext()
            );
            
            $allSearchTerms = array_merge($allSearchTerms, $synonymTerms);
            
            // Also add variations of synonyms
            foreach ($synonymTerms as $synonym) {
                $synonymVariations = $this->normalizer->generateSearchVariations($synonym, $context->getSalesChannelId());
                $allSearchTerms = array_merge($allSearchTerms, $synonymVariations);
            }
        }
        
        // Remove duplicates
        $allSearchTerms = array_unique($allSearchTerms);
        
        if (count($allSearchTerms) > 1) {
            // Add all search term variations to the query
            $queries = [];
            
            foreach ($allSearchTerms as $term) {
                $pattern = $this->createSearchPattern($term);
                
                $queries[] = new ScoreQuery(
                    new ContainsQuery('product.name', $pattern),
                    100
                );
                
                $queries[] = new ScoreQuery(
                    new ContainsQuery('product.customSearchKeywords', $pattern),
                    80
                );
                
                $queries[] = new ScoreQuery(
                    new ContainsQuery('product.description', $pattern),
                    60
                );
                
                $queries[] = new ScoreQuery(
                    new ContainsQuery('product.productNumber', $pattern),
                    70
                );
            }
            
            if (!empty($queries)) {
                $criteria->addQuery(
                    new ScoreQuery(
                        new MultiQuery(MultiQuery::CONNECTION_OR, $queries),
                        1000
                    )
                );
            }
        }
    }
    
    private function createSearchPattern(string $term): string
    {
        return '%' . $term . '%';
    }
}