<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Content\Product\Events\ProductSearchResultEvent;
use Shopware\Core\Content\Product\Events\ProductSuggestResultEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Elasticsearch\Framework\ElasticsearchHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntitySearchResultLoadedEvent;
use Swag\IntelligentSearchOptimizer\Service\SearchQueryNormalizer;
use Swag\IntelligentSearchOptimizer\Service\SynonymService;

class SearchEventSubscriber implements EventSubscriberInterface
{
    private EntityRepository $searchLogRepository;
    private SystemConfigService $systemConfigService;
    private ?ElasticsearchHelper $elasticsearchHelper;
    private LoggerInterface $logger;
    private RequestStack $requestStack;
    private SearchQueryNormalizer $normalizer;
    private SynonymService $synonymService;
    private EntityRepository $redirectRepository;

    public function __construct(
        EntityRepository $searchLogRepository,
        EntityRepository $redirectRepository,
        SystemConfigService $systemConfigService,
        ?ElasticsearchHelper $elasticsearchHelper,
        LoggerInterface $logger,
        RequestStack $requestStack,
        SearchQueryNormalizer $normalizer,
        SynonymService $synonymService
    ) {
        $this->searchLogRepository = $searchLogRepository;
        $this->redirectRepository = $redirectRepository;
        $this->systemConfigService = $systemConfigService;
        $this->elasticsearchHelper = $elasticsearchHelper;
        $this->logger = $logger;
        $this->requestStack = $requestStack;
        $this->normalizer = $normalizer;
        $this->synonymService = $synonymService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductSearchResultEvent::class => 'onProductSearchResult',
            ProductSuggestResultEvent::class => 'onProductSuggestResult',
            'product.search.result.loaded' => 'onSearchResultLoaded',
        ];
    }

    public function onProductSearchResult(ProductSearchResultEvent $event): void
    {
        $this->handleSearchEvent(
            $event->getResult()->getTotal(),
            $event->getContext(),
            $event->getSalesChannelContext()->getSalesChannelId(),
            $event->getSalesChannelContext()->getLanguageId(),
            'search'
        );
    }

    public function onProductSuggestResult(ProductSuggestResultEvent $event): void
    {
        $this->handleSearchEvent(
            $event->getResult()->getTotal(),
            $event->getContext(),
            $event->getSalesChannelContext()->getSalesChannelId(),
            $event->getSalesChannelContext()->getLanguageId(),
            'suggest'
        );
    }

    public function onSearchResultLoaded(EntitySearchResultLoadedEvent $event): void
    {
        if ($event->getDefinition()->getEntityName() !== 'product') {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $searchTerm = $request->get('search');
        if (!$searchTerm) {
            return;
        }

        $salesChannelId = $request->attributes->get('sw-sales-channel-id');
        $languageId = $request->attributes->get('sw-language-id');

        $this->checkForRedirect($searchTerm, $salesChannelId, $languageId, $event->getContext());
    }

    private function handleSearchEvent(
        int $resultCount,
        Context $context,
        string $salesChannelId,
        string $languageId,
        string $source
    ): void {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $searchTerm = $request->get('search');
        if (!$searchTerm || empty(trim($searchTerm))) {
            return;
        }

        $pluginConfig = $this->systemConfigService->get('IntelligentSearchOptimizer.config', $salesChannelId);
        if (!$pluginConfig['enabled'] ?? true) {
            return;
        }

        $normalizedTerm = $this->normalizer->normalize($searchTerm);
        
        $processedTerm = $this->synonymService->processSynonyms(
            $normalizedTerm,
            $salesChannelId,
            $languageId,
            $context
        );

        $sessionId = $request->getSession()->getId();
        $customerId = $context->getSource()->getUserId();

        $isElasticsearchUsed = $this->elasticsearchHelper && 
                              $this->elasticsearchHelper->allowSearch(
                                  $context,
                                  $salesChannelId
                              );

        $logData = [
            'id' => Uuid::randomHex(),
            'searchTerm' => $searchTerm,
            'normalizedTerm' => $processedTerm,
            'resultCount' => $resultCount,
            'salesChannelId' => $salesChannelId,
            'languageId' => $languageId,
            'customerId' => $customerId,
            'sessionId' => $sessionId,
            'searchSource' => $isElasticsearchUsed ? 'elasticsearch_' . $source : 'database_' . $source,
            'converted' => false,
            'createdAt' => new \DateTime(),
        ];

        try {
            $this->searchLogRepository->create([$logData], $context);
        } catch (\Exception $e) {
            $this->logger->error('Failed to log search query: ' . $e->getMessage(), [
                'searchTerm' => $searchTerm,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function checkForRedirect(
        string $searchTerm,
        string $salesChannelId,
        string $languageId,
        Context $context
    ): void {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('searchTerm', $searchTerm));
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        $criteria->addFilter(new EqualsFilter('languageId', $languageId));
        $criteria->setLimit(1);
        $criteria->addSorting(new \Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting('priority', 'DESC'));

        $redirect = $this->redirectRepository->search($criteria, $context)->first();
        
        if ($redirect && $redirect->getTargetUrl()) {
            header('Location: ' . $redirect->getTargetUrl());
            exit;
        }
    }
}