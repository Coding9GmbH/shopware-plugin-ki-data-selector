<?php declare(strict_types=1);

namespace Coding9\KIDataSelector\Core\Api\Controller;

use Coding9\KIDataSelector\Core\Service\KiChatGptService;
use Coding9\KIDataSelector\Core\Service\PromptBuilder;
use Coding9\KIDataSelector\Core\Service\SqlExecutorService;
use Coding9\KIDataSelector\Core\Service\SqlValidatorService;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * API Controller for KI Data Selector
 *
 * Provides endpoints for:
 * - /api/_action/kidata/query: Generate and execute SQL queries
 * - /api/_action/kidata/export: Export query results as CSV
 *
 * @package Coding9\KIDataSelector\Core\Api\Controller
 */
class KiDataController extends AbstractController
{
    private PromptBuilder $promptBuilder;
    private KiChatGptService $chatGptService;
    private SqlValidatorService $validator;
    private SqlExecutorService $executor;
    private Connection $connection;
    private LoggerInterface $logger;

    public function __construct(
        PromptBuilder $promptBuilder,
        KiChatGptService $chatGptService,
        SqlValidatorService $validator,
        SqlExecutorService $executor,
        Connection $connection,
        LoggerInterface $logger
    ) {
        $this->promptBuilder = $promptBuilder;
        $this->chatGptService = $chatGptService;
        $this->validator = $validator;
        $this->executor = $executor;
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * Generate and optionally execute SQL query from natural language
     *
     * POST /api/_action/kidata/query
     *
     * Request body:
     * {
     *   "prompt": "Give me all orders from last week",
     *   "page": 1,
     *   "limit": 25,
     *   "sort": null,
     *   "execute": true,
     *   "checkSchema": false
     * }
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function query(Request $request): JsonResponse
    {
        try {
            // Parse request
            $data = json_decode($request->getContent(), true);

            $prompt = $data['prompt'] ?? '';
            $sql = $data['sql'] ?? ''; // Allow passing pre-generated SQL
            $page = max(1, (int) ($data['page'] ?? 1));
            $limit = max(1, min((int) ($data['limit'] ?? 25), 200));
            $sort = $data['sort'] ?? null;
            $execute = (bool) ($data['execute'] ?? false);
            $checkSchema = (bool) ($data['checkSchema'] ?? false);

            // If no SQL provided, generate it
            if (empty($sql)) {
                if (empty($prompt)) {
                    return new JsonResponse([
                        'success' => false,
                        'error' => 'Prompt or SQL is required'
                    ], 400);
                }

                // Check for error context (query retry with error feedback)
                $errorContext = $data['errorContext'] ?? null;

                // Build messages for ChatGPT
                if ($errorContext && isset($errorContext['failedSql']) && isset($errorContext['error'])) {
                    // Build error correction prompt with original context
                    $originalPrompt = $errorContext['originalPrompt'] ?? null;
                    $messages = $this->promptBuilder->buildErrorCorrectionMessages(
                        $errorContext['failedSql'],
                        $errorContext['error'],
                        $originalPrompt
                    );
                } else {
                    // Normal prompt
                    $messages = $this->promptBuilder->buildMessages($prompt);
                }

                // Generate SQL
                $sql = $this->chatGptService->generateSql($messages);
            }

            // Validate SQL
            $validation = $this->validator->validate($sql, $checkSchema);

            if (!$validation['valid']) {
                $this->logQuery($prompt, $sql, false, 0);

                return new JsonResponse([
                    'success' => false,
                    'error' => $validation['error'],
                    'sql' => $sql,
                    'executed' => false
                ], 400);
            }

            $sql = $validation['sql'];

            // If execute is false, just return the SQL
            if (!$execute) {
                $this->logQuery($prompt, $sql, false, 0);

                return new JsonResponse([
                    'success' => true,
                    'sql' => $sql,
                    'executed' => false,
                    'columns' => [],
                    'rows' => [],
                    'total' => 0,
                    'page' => $page,
                    'limit' => $limit
                ]);
            }

            // Remove LIMIT/OFFSET for consistent pagination
            $cleanSql = $this->validator->removeLimitOffset($sql);

            // Execute query
            $result = $this->executor->execute($cleanSql, $page, $limit, $sort);

            // Log successful execution
            $this->logQuery($prompt, $sql, true, $result['total']);

            return new JsonResponse([
                'success' => true,
                'sql' => $sql,
                'executed' => true,
                'columns' => $result['columns'],
                'rows' => $result['rows'],
                'total' => $result['total'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'totalPages' => $result['totalPages']
            ]);

        } catch (\Exception $e) {
            $this->logger->error('[KI Data Selector] Query failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
                'executed' => false
            ], 500);
        }
    }

    /**
     * Export query results as CSV
     *
     * POST /api/_action/kidata/export
     *
     * Request body:
     * {
     *   "prompt": "Give me all orders from last week",
     *   "delimiter": ";",
     *   "enclosure": "\""
     * }
     *
     * @param Request $request
     * @return StreamedResponse|JsonResponse
     */
    public function export(Request $request)
    {
        try {
            // Parse request
            $data = json_decode($request->getContent(), true);

            $sql = $data['sql'] ?? ''; // Use pre-generated SQL
            $delimiter = $data['delimiter'] ?? ';';
            $enclosure = $data['enclosure'] ?? '"';
            $checkSchema = (bool) ($data['checkSchema'] ?? false);

            if (empty($sql)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'SQL is required'
                ], 400);
            }

            // Validate SQL
            $validation = $this->validator->validate($sql, $checkSchema);

            if (!$validation['valid']) {
                return new JsonResponse([
                    'success' => false,
                    'error' => $validation['error'],
                    'sql' => $sql
                ], 400);
            }

            $sql = $validation['sql'];

            // Remove LIMIT/OFFSET for full export
            $cleanSql = $this->validator->removeLimitOffset($sql);

            // Log export
            $this->logQuery('CSV Export', $sql, true, null);

            // Stream CSV response
            $filename = 'kidata-export-' . date('Ymd-His') . '.csv';

            $response = new StreamedResponse(function () use ($cleanSql, $delimiter, $enclosure) {
                foreach ($this->executor->executeAsCsv($cleanSql, $delimiter, $enclosure) as $line) {
                    echo $line;
                    flush();
                }
            });

            $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
            $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');

            return $response;

        } catch (\Exception $e) {
            $this->logger->error('[KI Data Selector] Export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save a query for later use
     *
     * POST /api/_action/kidata/save
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveQuery(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $name = $data['name'] ?? '';
            $description = $data['description'] ?? '';
            $sql = $data['sql'] ?? '';
            $prompt = $data['prompt'] ?? '';

            if (empty($name) || empty($sql)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Name and SQL are required'
                ], 400);
            }

            $id = Uuid::randomBytes();
            $this->connection->insert('kidata_saved_query', [
                'id' => $id,
                'name' => $name,
                'description' => $description,
                'sql_query' => $sql,
                'original_prompt' => $prompt,
                'created_at' => (new \DateTime())->format('Y-m-d H:i:s.u')
            ]);

            return new JsonResponse([
                'success' => true,
                'id' => bin2hex($id)
            ]);

        } catch (\Exception $e) {
            $this->logger->error('[KI Data Selector] Failed to save query', [
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of saved queries
     *
     * GET /api/_action/kidata/saved
     *
     * @return JsonResponse
     */
    public function getSavedQueries(): JsonResponse
    {
        try {
            $queries = $this->connection->fetchAllAssociative(
                'SELECT id, name, description, created_at, updated_at
                 FROM kidata_saved_query
                 ORDER BY created_at DESC'
            );

            // Convert binary IDs to hex
            foreach ($queries as &$query) {
                $query['id'] = bin2hex($query['id']);
            }

            return new JsonResponse([
                'success' => true,
                'queries' => $queries
            ]);

        } catch (\Exception $e) {
            $this->logger->error('[KI Data Selector] Failed to get saved queries', [
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single saved query
     *
     * GET /api/_action/kidata/saved/{id}
     *
     * @param string $id
     * @return JsonResponse
     */
    public function getSavedQuery(string $id): JsonResponse
    {
        try {
            $query = $this->connection->fetchAssociative(
                'SELECT id, name, description, sql_query, original_prompt, created_at, updated_at
                 FROM kidata_saved_query
                 WHERE id = :id',
                ['id' => hex2bin($id)]
            );

            if (!$query) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Query not found'
                ], 404);
            }

            $query['id'] = bin2hex($query['id']);

            return new JsonResponse([
                'success' => true,
                'query' => $query
            ]);

        } catch (\Exception $e) {
            $this->logger->error('[KI Data Selector] Failed to get saved query', [
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete saved query
     *
     * DELETE /api/_action/kidata/saved/{id}
     *
     * @param string $id
     * @return JsonResponse
     */
    public function deleteSavedQuery(string $id): JsonResponse
    {
        try {
            $this->connection->delete('kidata_saved_query', [
                'id' => hex2bin($id)
            ]);

            return new JsonResponse([
                'success' => true
            ]);

        } catch (\Exception $e) {
            $this->logger->error('[KI Data Selector] Failed to delete saved query', [
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Log query execution to database
     *
     * @param string $prompt User prompt
     * @param string $sql Generated SQL
     * @param bool $executed Whether query was executed
     * @param int|null $rowCount Number of rows (null for exports)
     */
    private function logQuery(string $prompt, string $sql, bool $executed, ?int $rowCount): void
    {
        try {
            $this->connection->insert('kidata_query_log', [
                'id' => Uuid::randomBytes(),
                'prompt' => $prompt,
                'sql_query' => $sql,
                'executed' => $executed ? 1 : 0,
                'row_count' => $rowCount,
                'created_at' => (new \DateTime())->format('Y-m-d H:i:s.u')
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the request
            $this->logger->error('[KI Data Selector] Failed to log query', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
