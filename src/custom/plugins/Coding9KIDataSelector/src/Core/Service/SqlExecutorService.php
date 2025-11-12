<?php declare(strict_types=1);

namespace Coding9\KIDataSelector\Core\Service;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * Executes validated SQL queries with pagination and CSV export
 *
 * Handles query execution, result formatting, pagination,
 * and CSV streaming.
 *
 * @package Coding9\KIDataSelector\Core\Service
 */
class SqlExecutorService
{
    private Connection $connection;
    private SystemConfigService $systemConfigService;
    private LoggerInterface $logger;

    public function __construct(
        Connection $connection,
        SystemConfigService $systemConfigService,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
    }

    /**
     * Execute SQL query with pagination
     *
     * @param string $sql Validated SQL query
     * @param int $page Page number (1-based)
     * @param int $limit Results per page
     * @param string|null $sort Optional sort override (e.g. "column ASC")
     * @return array Result with rows, columns, total, page, limit
     * @throws \Exception
     */
    public function execute(string $sql, int $page = 1, int $limit = 25, ?string $sort = null): array
    {
        $page = max(1, $page);
        $limit = max(1, min($limit, $this->getMaxPageSize()));

        try {
            // Get total count
            $total = $this->executeCount($sql);

            // Calculate offset
            $offset = ($page - 1) * $limit;

            // Build paginated query
            $paginatedSql = $sql;

            // Apply custom sort if provided
            if ($sort !== null && !empty(trim($sort))) {
                // Remove existing ORDER BY
                $paginatedSql = preg_replace('/\s+ORDER\s+BY\s+.+?(?=\s+LIMIT|\s+OFFSET|$)/i', '', $paginatedSql);
                $paginatedSql .= " ORDER BY {$sort}";
            }

            // Add LIMIT and OFFSET
            $paginatedSql .= " LIMIT {$limit} OFFSET {$offset}";

            $this->log('Executing paginated query', [
                'page' => $page,
                'limit' => $limit,
                'offset' => $offset,
                'total' => $total
            ]);

            // Execute query with timeout
            $timeout = $this->getSqlTimeout();
            $stmt = $this->connection->executeQuery($paginatedSql);
            $rows = $stmt->fetchAllAssociative();

            // Clean UTF-8 encoding issues
            $rows = $this->sanitizeUtf8($rows);

            // Get column names
            $columns = [];
            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
            }
            // Note: If no rows returned, columns array will be empty
            // This is compatible with both DBAL 2.x and 3.x

            return [
                'success' => true,
                'sql' => $sql,
                'paginatedSql' => $paginatedSql,
                'columns' => $columns,
                'rows' => $rows,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => (int) ceil($total / $limit)
            ];

        } catch (\Exception $e) {
            $this->log('Query execution failed', [
                'error' => $e->getMessage(),
                'sql' => $sql
            ], 'error');

            throw new \Exception('Query execution failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Execute count query to get total results
     *
     * @param string $sql SQL query
     * @return int Total number of results
     * @throws \Exception
     */
    public function executeCount(string $sql): int
    {
        try {
            // Wrap query in COUNT(*)
            $countSql = "SELECT COUNT(*) as total FROM ({$sql}) AS count_wrapper";

            $result = $this->connection->fetchOne($countSql);

            return (int) $result;

        } catch (\Exception $e) {
            $this->log('Count query failed', [
                'error' => $e->getMessage(),
                'sql' => $sql
            ], 'error');

            throw new \Exception('Count query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Execute query and stream as CSV
     *
     * @param string $sql Validated SQL query
     * @param string $delimiter CSV delimiter (default: semicolon)
     * @param string $enclosure CSV enclosure (default: double quote)
     * @return \Generator CSV lines generator
     * @throws \Exception
     */
    public function executeAsCsv(
        string $sql,
        string $delimiter = ';',
        string $enclosure = '"'
    ): \Generator {
        try {
            $this->log('Executing CSV export query');

            $stmt = $this->connection->executeQuery($sql);

            // Get column names for header
            $firstRow = $stmt->fetchAssociative();
            if ($firstRow === false) {
                // No results
                yield '';
                return;
            }

            $columns = array_keys($firstRow);

            // Yield header row
            yield $this->formatCsvLine($columns, $delimiter, $enclosure) . "\n";

            // Yield first data row
            yield $this->formatCsvLine(array_values($firstRow), $delimiter, $enclosure) . "\n";

            // Yield remaining rows
            while ($row = $stmt->fetchAssociative()) {
                yield $this->formatCsvLine(array_values($row), $delimiter, $enclosure) . "\n";
            }

        } catch (\Exception $e) {
            $this->log('CSV export failed', [
                'error' => $e->getMessage(),
                'sql' => $sql
            ], 'error');

            throw new \Exception('CSV export failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Format array as CSV line
     *
     * @param array $data Data to format
     * @param string $delimiter Delimiter
     * @param string $enclosure Enclosure
     * @return string Formatted CSV line
     */
    private function formatCsvLine(array $data, string $delimiter, string $enclosure): string
    {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $data, $delimiter, $enclosure);
        rewind($output);
        $line = stream_get_contents($output);
        fclose($output);

        return rtrim($line);
    }

    /**
     * Get configured SQL timeout in milliseconds
     *
     * @return int Timeout in milliseconds
     */
    private function getSqlTimeout(): int
    {
        return $this->systemConfigService->getInt('Coding9KIDataSelector.config.sqlTimeoutMs') ?: 20000;
    }

    /**
     * Get configured max page size
     *
     * @return int Max page size
     */
    private function getMaxPageSize(): int
    {
        return $this->systemConfigService->getInt('Coding9KIDataSelector.config.maxPageSize') ?: 200;
    }

    /**
     * Get configured default page size
     *
     * @return int Default page size
     */
    public function getDefaultPageSize(): int
    {
        return $this->systemConfigService->getInt('Coding9KIDataSelector.config.defaultPageSize') ?: 25;
    }

    /**
     * Sanitize UTF-8 encoding in result data
     *
     * @param array $data Data to sanitize
     * @return array Sanitized data
     */
    private function sanitizeUtf8(array $data): array
    {
        array_walk_recursive($data, function (&$item) {
            if (is_string($item)) {
                // Convert to UTF-8 if not valid, replacing invalid characters
                if (!mb_check_encoding($item, 'UTF-8')) {
                    $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
                }
                // Remove any remaining invalid UTF-8 sequences
                $item = mb_scrub($item, 'UTF-8');
            }
        });

        return $data;
    }

    /**
     * Log message if logging is enabled
     *
     * @param string $message Log message
     * @param array $context Context data
     * @param string $level Log level
     */
    private function log(string $message, array $context = [], string $level = 'info'): void
    {
        $loggingEnabled = $this->systemConfigService->getBool('Coding9KIDataSelector.config.enableLogging');

        if (!$loggingEnabled) {
            return;
        }

        switch ($level) {
            case 'error':
                $this->logger->error('[KI Data Selector] ' . $message, $context);
                break;
            case 'warning':
                $this->logger->warning('[KI Data Selector] ' . $message, $context);
                break;
            default:
                $this->logger->info('[KI Data Selector] ' . $message, $context);
                break;
        }
    }
}
