<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Storefront\Page\Search\SearchPageLoadedEvent;
use Shopware\Storefront\Page\Suggest\SuggestPageLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\IntelligentSearchOptimizer\Service\SearchQueryNormalizer;
use Swag\IntelligentSearchOptimizer\Service\SynonymService;

class StorefrontSearchSubscriber implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;
    private EntityRepository $searchLogRepository;
    private SearchQueryNormalizer $normalizer;
    private SynonymService $synonymService;

    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepository $searchLogRepository,
        SearchQueryNormalizer $normalizer,
        SynonymService $synonymService
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->searchLogRepository = $searchLogRepository;
        $this->normalizer = $normalizer;
        $this->synonymService = $synonymService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SearchPageLoadedEvent::class => 'onSearchPageLoaded',
            SuggestPageLoadedEvent::class => 'onSuggestPageLoaded',
        ];
    }

    public function onSearchPageLoaded(SearchPageLoadedEvent $event): void
    {
        $page = $event->getPage();
        $searchResult = $page->getListing();
        
        if ($searchResult->getTotal() > 0) {
            return;
        }

        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        $showSuggestions = $this->systemConfigService->get(
            'IntelligentSearchOptimizer.config.showSuggestionsOnZeroResults',
            $salesChannelId
        ) ?? true;

        if (!$showSuggestions) {
            return;
        }

        $searchTerm = $event->getRequest()->get('search');
        if (!$searchTerm) {
            return;
        }

        $suggestions = $this->findAlternativeSuggestions(
            $searchTerm,
            $salesChannelId,
            $event->getContext()->getLanguageId(),
            $event->getContext()
        );

        if (!empty($suggestions)) {
            $page->addExtension('searchSuggestions', new SearchSuggestionsStruct($suggestions));
        }
    }

    public function onSuggestPageLoaded(SuggestPageLoadedEvent $event): void
    {
        $page = $event->getPage();
        $searchResult = $page->getSearchResult();
        
        if ($searchResult->getTotal() > 0) {
            return;
        }

        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        $showSuggestions = $this->systemConfigService->get(
            'IntelligentSearchOptimizer.config.showSuggestionsOnZeroResults',
            $salesChannelId
        ) ?? true;

        if (!$showSuggestions) {
            return;
        }

        $searchTerm = $event->getRequest()->get('search');
        if (!$searchTerm) {
            return;
        }

        $suggestions = $this->findAlternativeSuggestions(
            $searchTerm,
            $salesChannelId,
            $event->getContext()->getLanguageId(),
            $event->getContext()
        );

        if (!empty($suggestions)) {
            $page->addExtension('searchSuggestions', new SearchSuggestionsStruct($suggestions));
        }
    }

    private function findAlternativeSuggestions(
        string $searchTerm,
        string $salesChannelId,
        string $languageId,
        \Shopware\Core\Framework\Context $context
    ): array {
        $normalizedTerm = $this->normalizer->normalize($searchTerm);
        
        // First check for synonyms
        $synonymTerms = $this->synonymService->getAllSynonymsForTerm(
            $normalizedTerm,
            $salesChannelId,
            $languageId,
            $context
        );

        $suggestions = [];
        
        // Add synonym-based suggestions
        foreach ($synonymTerms as $synonym) {
            if ($synonym !== $searchTerm) {
                $suggestions[] = [
                    'term' => $synonym,
                    'type' => 'synonym',
                    'confidence' => 0.9
                ];
            }
        }

        // Find similar successful searches
        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('normalizedTerm', substr($normalizedTerm, 0, 3)));
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [
            new EqualsFilter('normalizedTerm', $normalizedTerm)
        ]));
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        $criteria->addFilter(new EqualsFilter('languageId', $languageId));
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [
            new EqualsFilter('resultCount', 0)
        ]));
        $criteria->addSorting(new FieldSorting('resultCount', FieldSorting::DESCENDING));
        $criteria->setLimit(5);

        $similarSearches = $this->searchLogRepository->search($criteria, $context);
        
        foreach ($similarSearches as $search) {
            $similarity = $this->calculateSimilarity($normalizedTerm, $search->getNormalizedTerm());
            if ($similarity > 0.5) {
                $suggestions[] = [
                    'term' => $search->getSearchTerm(),
                    'type' => 'similar',
                    'confidence' => $similarity
                ];
            }
        }

        // Sort by confidence
        usort($suggestions, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });

        return array_slice($suggestions, 0, 3);
    }

    private function calculateSimilarity(string $str1, string $str2): float
    {
        $len1 = strlen($str1);
        $len2 = strlen($str2);
        
        if ($len1 === 0 || $len2 === 0) {
            return 0.0;
        }

        $maxLen = max($len1, $len2);
        $distance = levenshtein($str1, $str2);
        
        if ($distance === -1) {
            return 0.0;
        }

        return 1 - ($distance / $maxLen);
    }
}