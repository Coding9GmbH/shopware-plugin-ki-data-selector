<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Controller\Api;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Swag\IntelligentSearchOptimizer\Service\SearchAnalyticsService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class SearchAnalyticsController extends AbstractController
{
    private SearchAnalyticsService $analyticsService;
    private EntityRepository $searchLogRepository;
    private EntityRepository $synonymRepository;
    private EntityRepository $redirectRepository;

    public function __construct(
        SearchAnalyticsService $analyticsService,
        EntityRepository $searchLogRepository,
        EntityRepository $synonymRepository,
        EntityRepository $redirectRepository
    ) {
        $this->analyticsService = $analyticsService;
        $this->searchLogRepository = $searchLogRepository;
        $this->synonymRepository = $synonymRepository;
        $this->redirectRepository = $redirectRepository;
    }

    /**
     * @Route("/api/search-optimizer/analytics/dashboard", name="api.search_optimizer.analytics.dashboard", methods={"GET"})
     */
    public function dashboard(Request $request, Context $context): JsonResponse
    {
        $salesChannelId = $request->query->get('salesChannelId');
        $languageId = $request->query->get('languageId');
        $from = $request->query->get('from') ? new \DateTime($request->query->get('from')) : new \DateTime('-30 days');
        $to = $request->query->get('to') ? new \DateTime($request->query->get('to')) : new \DateTime();

        $summary = $this->analyticsService->getSearchStatsSummary(
            $salesChannelId,
            $languageId,
            $from,
            $to
        );

        $topSearches = $this->analyticsService->getTopSearchTerms(
            $salesChannelId,
            $languageId,
            10,
            $from,
            $to,
            $context
        );

        $zeroResults = $this->analyticsService->getZeroResultSearches(
            $salesChannelId,
            $languageId,
            10,
            $from,
            $to
        );

        $trends = $this->analyticsService->getSearchTrends(
            $salesChannelId,
            $languageId,
            'day',
            $from,
            $to
        );

        $lowPerforming = $this->analyticsService->getLowPerformingSearches(
            $salesChannelId,
            $languageId
        );

        return new JsonResponse([
            'summary' => $summary,
            'topSearches' => $topSearches,
            'zeroResults' => $zeroResults,
            'trends' => $trends,
            'lowPerforming' => $lowPerforming
        ]);
    }

    /**
     * @Route("/api/search-optimizer/analytics/top-searches", name="api.search_optimizer.analytics.top_searches", methods={"GET"})
     */
    public function topSearches(Request $request, Context $context): JsonResponse
    {
        $salesChannelId = $request->query->get('salesChannelId');
        $languageId = $request->query->get('languageId');
        $limit = (int) $request->query->get('limit', 50);
        $from = $request->query->get('from') ? new \DateTime($request->query->get('from')) : null;
        $to = $request->query->get('to') ? new \DateTime($request->query->get('to')) : null;

        $results = $this->analyticsService->getTopSearchTerms(
            $salesChannelId,
            $languageId,
            $limit,
            $from,
            $to,
            $context
        );

        return new JsonResponse(['data' => $results]);
    }

    /**
     * @Route("/api/search-optimizer/analytics/zero-results", name="api.search_optimizer.analytics.zero_results", methods={"GET"})
     */
    public function zeroResults(Request $request): JsonResponse
    {
        $salesChannelId = $request->query->get('salesChannelId');
        $languageId = $request->query->get('languageId');
        $limit = (int) $request->query->get('limit', 50);
        $from = $request->query->get('from') ? new \DateTime($request->query->get('from')) : null;
        $to = $request->query->get('to') ? new \DateTime($request->query->get('to')) : null;

        $results = $this->analyticsService->getZeroResultSearches(
            $salesChannelId,
            $languageId,
            $limit,
            $from,
            $to
        );

        return new JsonResponse(['data' => $results]);
    }

    /**
     * @Route("/api/search-optimizer/analytics/export", name="api.search_optimizer.analytics.export", methods={"GET"})
     */
    public function export(Request $request, Context $context): Response
    {
        $salesChannelId = $request->query->get('salesChannelId');
        $languageId = $request->query->get('languageId');
        $type = $request->query->get('type', 'all');
        $from = $request->query->get('from') ? new \DateTime($request->query->get('from')) : new \DateTime('-30 days');
        $to = $request->query->get('to') ? new \DateTime($request->query->get('to')) : new \DateTime();

        $csv = [];
        
        switch ($type) {
            case 'top':
                $data = $this->analyticsService->getTopSearchTerms($salesChannelId, $languageId, 1000, $from, $to, $context);
                $csv[] = ['Search Term', 'Search Count', 'Avg Results', 'Zero Results Count', 'Conversions', 'Conversion Rate'];
                foreach ($data as $row) {
                    $csv[] = [
                        $row['search_term'],
                        $row['search_count'],
                        round($row['avg_results'], 2),
                        $row['zero_results_count'],
                        $row['conversions'],
                        $row['conversion_rate'] . '%'
                    ];
                }
                break;
                
            case 'zero':
                $data = $this->analyticsService->getZeroResultSearches($salesChannelId, $languageId, 1000, $from, $to);
                $csv[] = ['Search Term', 'Search Count', 'Last Searched', 'Unique Sessions'];
                foreach ($data as $row) {
                    $csv[] = [
                        $row['search_term'],
                        $row['search_count'],
                        $row['last_searched'],
                        $row['unique_sessions']
                    ];
                }
                break;
                
            default:
                $csv[] = ['Type', 'Search Term', 'Count', 'Details'];
                
                $topSearches = $this->analyticsService->getTopSearchTerms($salesChannelId, $languageId, 100, $from, $to, $context);
                foreach ($topSearches as $row) {
                    $csv[] = [
                        'Top Search',
                        $row['search_term'],
                        $row['search_count'],
                        'Conversion Rate: ' . $row['conversion_rate'] . '%'
                    ];
                }
                
                $zeroResults = $this->analyticsService->getZeroResultSearches($salesChannelId, $languageId, 100, $from, $to);
                foreach ($zeroResults as $row) {
                    $csv[] = [
                        'Zero Results',
                        $row['search_term'],
                        $row['search_count'],
                        'Last: ' . $row['last_searched']
                    ];
                }
        }

        $output = '';
        foreach ($csv as $row) {
            $output .= '"' . implode('","', array_map('str_replace', ['"'], ['""'], $row)) . "\"\n";
        }

        $response = new Response($output);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="search-analytics-' . date('Y-m-d') . '.csv"');

        return $response;
    }

    /**
     * @Route("/api/search-optimizer/synonyms", name="api.search_optimizer.synonyms.list", methods={"GET"})
     */
    public function listSynonyms(Request $request, Context $context): JsonResponse
    {
        $criteria = new Criteria();
        $criteria->setLimit((int) $request->query->get('limit', 25));
        $criteria->setOffset((int) $request->query->get('offset', 0));
        
        if ($salesChannelId = $request->query->get('salesChannelId')) {
            $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        }
        
        if ($languageId = $request->query->get('languageId')) {
            $criteria->addFilter(new EqualsFilter('languageId', $languageId));
        }
        
        $criteria->addSorting(new FieldSorting('keyword', FieldSorting::ASCENDING));
        $criteria->addAssociation('language');
        $criteria->addAssociation('salesChannel');

        $result = $this->synonymRepository->search($criteria, $context);

        return new JsonResponse([
            'data' => $result->getEntities(),
            'total' => $result->getTotal()
        ]);
    }

    /**
     * @Route("/api/search-optimizer/synonyms", name="api.search_optimizer.synonyms.create", methods={"POST"})
     */
    public function createSynonym(Request $request, Context $context): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $data['id'] = Uuid::randomHex();
        $data['createdAt'] = new \DateTime();
        
        $this->synonymRepository->create([$data], $context);

        return new JsonResponse(['success' => true, 'id' => $data['id']]);
    }

    /**
     * @Route("/api/search-optimizer/synonyms/{id}", name="api.search_optimizer.synonyms.update", methods={"PATCH"})
     */
    public function updateSynonym(string $id, Request $request, Context $context): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $data['id'] = $id;
        $data['updatedAt'] = new \DateTime();
        
        $this->synonymRepository->update([$data], $context);

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/api/search-optimizer/synonyms/{id}", name="api.search_optimizer.synonyms.delete", methods={"DELETE"})
     */
    public function deleteSynonym(string $id, Context $context): JsonResponse
    {
        $this->synonymRepository->delete([['id' => $id]], $context);

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/api/search-optimizer/redirects", name="api.search_optimizer.redirects.list", methods={"GET"})
     */
    public function listRedirects(Request $request, Context $context): JsonResponse
    {
        $criteria = new Criteria();
        $criteria->setLimit((int) $request->query->get('limit', 25));
        $criteria->setOffset((int) $request->query->get('offset', 0));
        
        if ($salesChannelId = $request->query->get('salesChannelId')) {
            $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        }
        
        if ($languageId = $request->query->get('languageId')) {
            $criteria->addFilter(new EqualsFilter('languageId', $languageId));
        }
        
        $criteria->addSorting(new FieldSorting('priority', FieldSorting::DESCENDING));
        $criteria->addSorting(new FieldSorting('searchTerm', FieldSorting::ASCENDING));
        $criteria->addAssociation('language');
        $criteria->addAssociation('salesChannel');

        $result = $this->redirectRepository->search($criteria, $context);

        return new JsonResponse([
            'data' => $result->getEntities(),
            'total' => $result->getTotal()
        ]);
    }

    /**
     * @Route("/api/search-optimizer/redirects", name="api.search_optimizer.redirects.create", methods={"POST"})
     */
    public function createRedirect(Request $request, Context $context): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $data['id'] = Uuid::randomHex();
        $data['createdAt'] = new \DateTime();
        
        $this->redirectRepository->create([$data], $context);

        return new JsonResponse(['success' => true, 'id' => $data['id']]);
    }

    /**
     * @Route("/api/search-optimizer/redirects/{id}", name="api.search_optimizer.redirects.update", methods={"PATCH"})
     */
    public function updateRedirect(string $id, Request $request, Context $context): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $data['id'] = $id;
        $data['updatedAt'] = new \DateTime();
        
        $this->redirectRepository->update([$data], $context);

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/api/search-optimizer/redirects/{id}", name="api.search_optimizer.redirects.delete", methods={"DELETE"})
     */
    public function deleteRedirect(string $id, Context $context): JsonResponse
    {
        $this->redirectRepository->delete([['id' => $id]], $context);

        return new JsonResponse(['success' => true]);
    }
}