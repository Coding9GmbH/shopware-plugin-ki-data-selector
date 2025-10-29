<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;

class SynonymService
{
    private EntityRepository $synonymRepository;

    public function __construct(EntityRepository $synonymRepository)
    {
        $this->synonymRepository = $synonymRepository;
    }

    public function processSynonyms(
        string $searchTerm,
        string $salesChannelId,
        string $languageId,
        Context $context
    ): string {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(
            new MultiFilter(MultiFilter::CONNECTION_OR, [
                new EqualsFilter('keyword', $searchTerm),
                new EqualsFilter('synonym', $searchTerm)
            ])
        );
        $criteria->addFilter(
            new MultiFilter(MultiFilter::CONNECTION_OR, [
                new EqualsFilter('salesChannelId', null),
                new EqualsFilter('salesChannelId', $salesChannelId)
            ])
        );
        $criteria->addFilter(
            new MultiFilter(MultiFilter::CONNECTION_OR, [
                new EqualsFilter('languageId', null),
                new EqualsFilter('languageId', $languageId)
            ])
        );

        $synonyms = $this->synonymRepository->search($criteria, $context);
        
        if ($synonyms->getTotal() === 0) {
            return $searchTerm;
        }

        foreach ($synonyms as $synonym) {
            if ($synonym->getKeyword() === $searchTerm) {
                return $synonym->getSynonym();
            }
        }

        return $searchTerm;
    }

    public function getAllSynonymsForTerm(
        string $searchTerm,
        string $salesChannelId,
        string $languageId,
        Context $context
    ): array {
        $processed = $this->processSynonyms($searchTerm, $salesChannelId, $languageId, $context);
        $terms = [$searchTerm];
        
        if ($processed !== $searchTerm) {
            $terms[] = $processed;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new EqualsFilter('keyword', $processed));
        $criteria->addFilter(
            new MultiFilter(MultiFilter::CONNECTION_OR, [
                new EqualsFilter('salesChannelId', null),
                new EqualsFilter('salesChannelId', $salesChannelId)
            ])
        );
        $criteria->addFilter(
            new MultiFilter(MultiFilter::CONNECTION_OR, [
                new EqualsFilter('languageId', null),
                new EqualsFilter('languageId', $languageId)
            ])
        );

        $additionalSynonyms = $this->synonymRepository->search($criteria, $context);
        
        foreach ($additionalSynonyms as $synonym) {
            if (!in_array($synonym->getSynonym(), $terms, true)) {
                $terms[] = $synonym->getSynonym();
            }
        }

        return array_unique($terms);
    }
}