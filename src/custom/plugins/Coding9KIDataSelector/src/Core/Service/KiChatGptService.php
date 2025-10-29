<?php declare(strict_types=1);

namespace Coding9\KIDataSelector\Core\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service for communicating with OpenAI ChatGPT API
 *
 * Sends prompts to OpenAI and receives SQL query responses.
 *
 * @package Coding9\KIDataSelector\Core\Service
 */
class KiChatGptService
{
    private const OPENAI_API_URL = 'https://api.openai.com/v1/chat/completions';
    private const DEFAULT_MODEL = 'gpt-4o-mini';
    private const ALLOWED_MODELS = ['gpt-4o-mini', 'gpt-4o', 'gpt-4-turbo'];

    private HttpClientInterface $httpClient;
    private SystemConfigService $systemConfigService;
    private LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $httpClient,
        SystemConfigService $systemConfigService,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
    }

    /**
     * Generate SQL from natural language prompt
     *
     * @param array $messages Messages array (system + user)
     * @return string Generated SQL query
     * @throws \Exception
     */
    public function generateSql(array $messages): string
    {
        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            throw new \Exception('OpenAI API key not configured. Please set kidata.apiKey in system configuration.');
        }

        $model = $this->getModel();

        $requestData = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.0,
            'max_tokens' => 1000
        ];

        $this->log('Sending request to OpenAI', [
            'model' => $model,
            'message_count' => count($messages),
            'system_prompt_length' => strlen($messages[0]['content'] ?? ''),
            'user_prompt' => $messages[1]['content'] ?? ''
        ]);

        try {
            $response = $this->httpClient->request('POST', self::OPENAI_API_URL, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey
                ],
                'json' => $requestData,
                'timeout' => 30
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $content = $response->getContent(false);
                $this->log('OpenAI API error', [
                    'status_code' => $statusCode,
                    'response' => $content
                ], 'error');
                throw new \Exception("OpenAI API returned status {$statusCode}: {$content}");
            }

            $data = $response->toArray();

            if (!isset($data['choices'][0]['message']['content'])) {
                $this->log('Invalid OpenAI response structure', ['response' => $data], 'error');
                throw new \Exception('Invalid response from OpenAI API');
            }

            $sql = trim($data['choices'][0]['message']['content']);

            // Remove markdown code fences if present (despite instructions)
            $sql = preg_replace('/^```sql\s*/i', '', $sql);
            $sql = preg_replace('/^```\s*/i', '', $sql);
            $sql = preg_replace('/\s*```$/i', '', $sql);
            $sql = trim($sql);

            $this->log('SQL generated successfully', [
                'sql_length' => strlen($sql),
                'tokens_used' => $data['usage']['total_tokens'] ?? 0
            ]);

            return $sql;

        } catch (\Exception $e) {
            $this->log('Failed to generate SQL', [
                'error' => $e->getMessage()
            ], 'error');
            throw new \Exception('Failed to generate SQL: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get configured API key
     *
     * @return string API key
     */
    private function getApiKey(): string
    {
        return $this->systemConfigService->getString('Coding9KIDataSelector.config.apiKey') ?: '';
    }

    /**
     * Get configured model
     *
     * @return string Model name
     */
    private function getModel(): string
    {
        $model = $this->systemConfigService->getString('Coding9KIDataSelector.config.model');

        if (empty($model) || !in_array($model, self::ALLOWED_MODELS, true)) {
            return self::DEFAULT_MODEL;
        }

        return $model;
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

        // Never log API keys
        if (isset($context['api_key'])) {
            unset($context['api_key']);
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
