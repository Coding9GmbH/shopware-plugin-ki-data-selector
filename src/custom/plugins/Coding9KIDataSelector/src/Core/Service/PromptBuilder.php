<?php declare(strict_types=1);

namespace Coding9\KIDataSelector\Core\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * Builds system prompts for ChatGPT including schema and examples
 *
 * Creates structured prompts with database schema, SQL examples,
 * and strict rules for generating read-only SELECT queries.
 *
 * @package Coding9\KIDataSelector\Core\Service
 */
class PromptBuilder
{
    private const SYSTEM_PROMPT_TEMPLATE = <<<'PROMPT'
You are a SQL generator for MySQL/MariaDB (Shopware 6 database).

STRICT RULES:
1. Respond ONLY with a single SQL statement
2. NO explanations, NO backticks, NO comments, NO markdown
3. ONLY SELECT statements allowed (with JOIN/GROUP BY/HAVING/ORDER BY/LIMIT/OFFSET)
4. Use ONLY existing tables and columns from the schema provided below
5. Time references like "last week" are relative to NOW() in MySQL
6. Table names may be reserved words - use backticks: `order`, `group`, etc.
7. ALL ID fields (BINARY(16)) MUST be wrapped with HEX() function for readability
8. Return ONLY the SQL string, nothing else

DATABASE SCHEMA:
%s

EXAMPLES:

User: "Give me all orders from last week"
SQL: SELECT HEX(o.id) as id, o.order_number, o.order_date_time FROM `order` o WHERE o.order_date_time >= (NOW() - INTERVAL 7 DAY) ORDER BY o.order_date_time DESC

User: "Give me all sold products from last week"
SQL: SELECT HEX(oli.product_id) as product_id, p.product_number, SUM(oli.quantity) AS qty FROM `order` o JOIN order_line_item oli ON oli.order_id = o.id LEFT JOIN product p ON p.id = oli.product_id WHERE o.order_date_time >= (NOW() - INTERVAL 7 DAY) AND oli.type = 'product' GROUP BY oli.product_id, p.product_number ORDER BY qty DESC

User: "Top 10 customers by revenue last month"
SQL: SELECT HEX(c.id) as id, c.first_name, c.last_name, c.email, SUM(o.amount_total) AS revenue FROM customer c JOIN `order` o ON o.order_customer_id = c.id WHERE o.order_date_time >= (NOW() - INTERVAL 1 MONTH) GROUP BY c.id, c.first_name, c.last_name, c.email ORDER BY revenue DESC LIMIT 10

User: "Show me products with low stock (less than 10)"
SQL: SELECT HEX(p.id) as id, p.product_number, p.stock FROM product p WHERE p.stock < 10 AND p.active = 1 ORDER BY p.stock ASC

User: "Orders with status 'open' today"
SQL: SELECT HEX(o.id) as id, o.order_number, o.order_date_time, sm.technical_name as status FROM `order` o JOIN state_machine_state sm ON o.state_id = sm.id WHERE DATE(o.order_date_time) = CURDATE() AND sm.technical_name = 'open' ORDER BY o.order_date_time DESC

IMPORTANT:
- Always use HEX() for BINARY(16) ID fields (id, product_id, customer_id, etc.)
- Always use table aliases for clarity
- Use backticks for reserved words (order, group, etc.)
- Prefer INNER JOIN over implicit joins
- Use proper date/time functions (NOW(), DATE(), INTERVAL)
- Return ONLY the SQL - no formatting, no explanation
PROMPT;

    private SchemaProvider $schemaProvider;
    private SystemConfigService $systemConfigService;

    public function __construct(
        SchemaProvider $schemaProvider,
        SystemConfigService $systemConfigService
    ) {
        $this->schemaProvider = $schemaProvider;
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * Build system prompt with schema
     *
     * @param bool $useCompactSchema Use compact schema representation
     * @param int $maxTables Maximum tables for compact schema (0 = all)
     * @return string Complete system prompt
     * @throws \Doctrine\DBAL\Exception
     */
    public function buildSystemPrompt(bool $useCompactSchema = true, int $maxTables = 100): string
    {
        $schema = $useCompactSchema
            ? $this->schemaProvider->getCompactSchema($maxTables)
            : $this->schemaProvider->getSchema();

        $schemaJson = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return sprintf(self::SYSTEM_PROMPT_TEMPLATE, $schemaJson);
    }

    /**
     * Build messages array for ChatGPT API
     *
     * @param string $userPrompt User's natural language query
     * @param bool $useCompactSchema Use compact schema
     * @param int $maxTables Maximum tables for compact schema
     * @return array Messages array for API
     * @throws \Doctrine\DBAL\Exception
     */
    public function buildMessages(
        string $userPrompt,
        bool $useCompactSchema = true,
        int $maxTables = 100
    ): array {
        return [
            [
                'role' => 'system',
                'content' => $this->buildSystemPrompt($useCompactSchema, $maxTables)
            ],
            [
                'role' => 'user',
                'content' => trim($userPrompt)
            ]
        ];
    }

    /**
     * Build error correction messages for ChatGPT API
     *
     * @param string $failedSql The SQL query that failed
     * @param string $error The error message
     * @param string|null $originalPrompt The original user prompt for context
     * @param bool $useCompactSchema Use compact schema
     * @param int $maxTables Maximum tables for compact schema
     * @return array Messages array for API
     * @throws \Doctrine\DBAL\Exception
     */
    public function buildErrorCorrectionMessages(
        string $failedSql,
        string $error,
        ?string $originalPrompt = null,
        bool $useCompactSchema = true,
        int $maxTables = 100
    ): array {
        $userPrompt = "Der folgende SQL Query hat einen Fehler verursacht:\n\n";

        // Include original prompt for context if available
        if ($originalPrompt !== null && !empty(trim($originalPrompt))) {
            $userPrompt .= "Ursprüngliche Anfrage:\n{$originalPrompt}\n\n";
        }

        $userPrompt .= "Generierter SQL:\n{$failedSql}\n\n";
        $userPrompt .= "Fehler:\n{$error}\n\n";
        $userPrompt .= "Bitte analysiere den Fehler und generiere einen korrigierten SQL Query.";

        return [
            [
                'role' => 'system',
                'content' => $this->buildSystemPrompt($useCompactSchema, $maxTables)
            ],
            [
                'role' => 'user',
                'content' => $userPrompt
            ]
        ];
    }

    /**
     * Get configured locale for time references
     *
     * @return string Locale code
     */
    private function getLocale(): string
    {
        return $this->systemConfigService->getString('Coding9KIDataSelector.config.locale') ?: 'de_DE';
    }
}
