<?php declare(strict_types=1);

namespace Coding9\KIDataSelector\Core\Service;

/**
 * Validates SQL queries for read-only access and security
 *
 * Ensures only SELECT statements are executed and prevents
 * any data modification or schema changes.
 *
 * @package Coding9\KIDataSelector\Core\Service
 */
class SqlValidatorService
{
    // Forbidden SQL keywords (case-insensitive)
    private const FORBIDDEN_KEYWORDS = [
        'ALTER', 'DROP', 'TRUNCATE', 'CREATE', 'REPLACE',
        'INSERT', 'UPDATE', 'DELETE', 'MERGE',
        'GRANT', 'REVOKE', 'ATTACH', 'DETACH',
        'ANALYZE', 'EXPLAIN', 'DESCRIBE', 'SHOW',
        'SET', 'USE', 'PRAGMA', 'CALL', 'HANDLER',
        'LOAD', 'OUTFILE', 'INFILE', 'INTO',
        'LOCK', 'UNLOCK', 'KILL', 'FLUSH', 'SHUTDOWN',
        'PREPARE', 'EXECUTE', 'DEALLOCATE'
    ];

    // Dangerous patterns
    private const FORBIDDEN_PATTERNS = [
        '/\/\*!/',           // MySQL conditional comments
        '/--\s/',            // SQL comments
        '/#/',               // MySQL comments
        '/;\s*\w/',          // Multiple statements
        '/\bxp_\w+/i',       // SQL Server extended procedures
        '/\bsp_\w+/i',       // SQL Server system procedures
    ];

    private SchemaProvider $schemaProvider;

    public function __construct(
        SchemaProvider $schemaProvider
    ) {
        $this->schemaProvider = $schemaProvider;
    }

    /**
     * Validate SQL query
     *
     * @param string $sql SQL query to validate
     * @param bool $checkSchema Check if tables exist (slower but more secure)
     * @return array Validation result ['valid' => bool, 'error' => string|null, 'sql' => string]
     */
    public function validate(string $sql, bool $checkSchema = false): array
    {
        $sql = trim($sql);

        // Must not be empty
        if (empty($sql)) {
            return [
                'valid' => false,
                'error' => 'SQL query is empty',
                'sql' => $sql
            ];
        }

        // Must start with SELECT
        if (!preg_match('/^\s*SELECT\s+/i', $sql)) {
            return [
                'valid' => false,
                'error' => 'Only SELECT queries are allowed',
                'sql' => $sql
            ];
        }

        // Must not contain multiple statements (semicolon check)
        if (preg_match('/;\s*\w/i', $sql)) {
            return [
                'valid' => false,
                'error' => 'Multiple statements are not allowed',
                'sql' => $sql
            ];
        }

        // Remove trailing semicolon if present
        $sql = rtrim($sql, "; \t\n\r\0\x0B");

        // Check forbidden keywords
        foreach (self::FORBIDDEN_KEYWORDS as $keyword) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $sql)) {
                return [
                    'valid' => false,
                    'error' => "Forbidden keyword detected: {$keyword}",
                    'sql' => $sql
                ];
            }
        }

        // Check forbidden patterns
        foreach (self::FORBIDDEN_PATTERNS as $pattern) {
            if (preg_match($pattern, $sql)) {
                return [
                    'valid' => false,
                    'error' => 'Forbidden SQL pattern detected',
                    'sql' => $sql
                ];
            }
        }

        // Optional: Check if tables exist
        if ($checkSchema) {
            $tableValidation = $this->validateTables($sql);
            if (!$tableValidation['valid']) {
                return $tableValidation;
            }
        }

        return [
            'valid' => true,
            'error' => null,
            'sql' => $sql
        ];
    }

    /**
     * Validate that all referenced tables exist
     *
     * @param string $sql SQL query
     * @return array Validation result
     */
    private function validateTables(string $sql): array
    {
        try {
            // Extract table names (simplified regex, may not catch all cases)
            preg_match_all('/FROM\s+`?(\w+)`?|JOIN\s+`?(\w+)`?/i', $sql, $matches);

            $tableNames = array_merge($matches[1], $matches[2]);
            $tableNames = array_filter($tableNames);
            $tableNames = array_unique($tableNames);

            $existingTables = $this->schemaProvider->getAllTableNames();

            foreach ($tableNames as $tableName) {
                if (!in_array($tableName, $existingTables, true)) {
                    return [
                        'valid' => false,
                        'error' => "Table '{$tableName}' does not exist",
                        'sql' => $sql
                    ];
                }
            }

            return [
                'valid' => true,
                'error' => null,
                'sql' => $sql
            ];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => 'Failed to validate table names: ' . $e->getMessage(),
                'sql' => $sql
            ];
        }
    }

    /**
     * Remove LIMIT/OFFSET from SQL (for pagination handling)
     *
     * @param string $sql SQL query
     * @return string SQL without LIMIT/OFFSET
     */
    public function removeLimitOffset(string $sql): string
    {
        // Remove LIMIT and OFFSET clauses
        $sql = preg_replace('/\s+LIMIT\s+\d+(\s*,\s*\d+)?/i', '', $sql);
        $sql = preg_replace('/\s+OFFSET\s+\d+/i', '', $sql);

        return trim($sql);
    }

    /**
     * Extract ORDER BY clause from SQL
     *
     * @param string $sql SQL query
     * @return string|null ORDER BY clause or null
     */
    public function extractOrderBy(string $sql): ?string
    {
        if (preg_match('/ORDER\s+BY\s+(.+?)(?:\s+LIMIT|\s+OFFSET|$)/i', $sql, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
