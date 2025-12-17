<?php

namespace App\Services\AI;

use App\Models\AiProvider;
use App\Models\AiModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * PostXAgent AI Service
 *
 * Service สำหรับเชื่อมต่อกับ PostXAgent AI Manager Core
 * รองรับการส่ง chat request ไปยัง AI ที่รันบน PostXAgent (C# .NET 8.0)
 *
 * Features:
 * - REST API communication
 * - Health check และ status monitoring
 * - Automatic retry with exponential backoff
 * - Fallback support
 * - Usage logging
 *
 * @see https://github.com/xjanova/PostXAgent
 */
class PostXAgentService extends BaseAiService
{
    /**
     * PostXAgent API base URL
     */
    protected string $apiUrl;

    /**
     * Authentication config
     */
    protected array $authConfig;

    /**
     * Retry config
     */
    protected array $retryConfig;

    /**
     * สร้าง PostXAgentService instance
     *
     * @param AiProvider $provider
     * @param AiModel $model
     */
    public function __construct(AiProvider $provider, AiModel $model)
    {
        parent::__construct($provider, $model);

        $this->apiUrl = config('postxagent.api_url', 'http://localhost:8000/api/v1');
        $this->authConfig = config('postxagent.auth', []);
        $this->retryConfig = config('postxagent.retry', [
            'max_attempts' => 3,
            'delay_ms' => 1000,
            'multiplier' => 2,
        ]);
    }

    /**
     * ส่ง chat completion request ไปยัง PostXAgent
     *
     * @param array $messages รูปแบบ [['role' => 'user', 'content' => '...'], ...]
     * @param array $options ตัวเลือกเพิ่มเติม (temperature, max_tokens, etc.)
     * @return array ['content' => string, 'tokens' => int, 'finish_reason' => string]
     */
    public function chat(array $messages, array $options = []): array
    {
        $startTime = microtime(true);

        try {
            // ตรวจสอบว่า PostXAgent พร้อมใช้งานหรือไม่
            if (!$this->isAvailable()) {
                throw new \Exception('PostXAgent ไม่พร้อมใช้งาน กรุณาตรวจสอบการเชื่อมต่อ');
            }

            // เตรียม request body
            $requestBody = $this->prepareRequestBody($messages, $options);

            // ส่ง request พร้อม retry
            $response = $this->sendRequestWithRetry('/ai/chat', $requestBody);

            // Parse response
            $result = $this->parseResponse($response);

            // Log success
            $this->logRequest($requestBody, $result, microtime(true) - $startTime, 'success');

            return $result;
        } catch (\Exception $e) {
            // Log error
            $this->logRequest(
                $messages,
                ['error' => $e->getMessage()],
                microtime(true) - $startTime,
                'error'
            );

            throw $e;
        }
    }

    /**
     * ส่ง completion request แบบ streaming
     *
     * @param array $messages
     * @param array $options
     * @param callable $callback function($chunk) - เรียกทุกครั้งที่ได้ chunk ใหม่
     * @return array
     */
    public function chatStream(array $messages, array $options, callable $callback): array
    {
        $fullContent = '';
        $totalTokens = 0;

        try {
            $requestBody = $this->prepareRequestBody($messages, array_merge($options, ['stream' => true]));

            $response = Http::timeout(config('postxagent.timeout', 30))
                ->withHeaders($this->getAuthHeaders())
                ->withOptions(['stream' => true])
                ->post($this->apiUrl . '/ai/chat/stream', $requestBody);

            // อ่าน stream
            $body = $response->body();
            $lines = explode("\n", $body);

            foreach ($lines as $line) {
                if (empty(trim($line))) {
                    continue;
                }

                // Parse SSE format
                if (str_starts_with($line, 'data: ')) {
                    $data = json_decode(substr($line, 6), true);

                    if ($data && isset($data['content'])) {
                        $fullContent .= $data['content'];
                        $callback($data['content']);

                        if (isset($data['tokens'])) {
                            $totalTokens = $data['tokens'];
                        }
                    }

                    if (isset($data['done']) && $data['done']) {
                        break;
                    }
                }
            }

            return [
                'content' => $fullContent,
                'tokens' => $totalTokens,
                'finish_reason' => 'stop',
            ];
        } catch (\Exception $e) {
            Log::error('PostXAgent Stream Error', [
                'error' => $e->getMessage(),
                'messages' => $messages,
            ]);

            throw $e;
        }
    }

    /**
     * สร้าง embeddings
     *
     * @param string $text
     * @return array vector embeddings
     */
    public function createEmbedding(string $text): array
    {
        try {
            $response = $this->sendRequestWithRetry('/ai/embeddings', [
                'text' => $text,
                'model' => $this->model->model_identifier ?? 'default',
            ]);

            return $response['embedding'] ?? [];
        } catch (\Exception $e) {
            Log::error('PostXAgent Embedding Error', [
                'error' => $e->getMessage(),
                'text_length' => strlen($text),
            ]);

            throw $e;
        }
    }

    /**
     * ทดสอบการเชื่อมต่อกับ PostXAgent
     *
     * @return array ['success' => bool, 'message' => string, 'details' => array]
     */
    public function testConnection(): array
    {
        try {
            $status = $this->getStatus();

            if ($status['online']) {
                return [
                    'success' => true,
                    'message' => 'เชื่อมต่อ PostXAgent สำเร็จ',
                    'details' => $status,
                ];
            }

            return [
                'success' => false,
                'message' => 'PostXAgent ไม่ตอบสนอง',
                'details' => $status,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'ไม่สามารถเชื่อมต่อ PostXAgent: ' . $e->getMessage(),
                'details' => [
                    'api_url' => $this->apiUrl,
                    'error' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * ตรวจสอบสถานะ PostXAgent
     *
     * @return array
     */
    public function getStatus(): array
    {
        $cacheKey = 'postxagent_status';
        $cacheTtl = config('postxagent.health_check.cache_ttl', 30);

        return Cache::remember($cacheKey, $cacheTtl, function () {
            try {
                $response = Http::timeout(5)
                    ->withHeaders($this->getAuthHeaders())
                    ->get($this->apiUrl . '/status');

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'online' => true,
                        'version' => $data['version'] ?? 'unknown',
                        'providers' => $data['providers'] ?? [],
                        'uptime' => $data['uptime'] ?? null,
                        'checked_at' => now()->toDateTimeString(),
                    ];
                }

                return [
                    'online' => false,
                    'error' => 'HTTP ' . $response->status(),
                    'checked_at' => now()->toDateTimeString(),
                ];
            } catch (\Exception $e) {
                return [
                    'online' => false,
                    'error' => $e->getMessage(),
                    'checked_at' => now()->toDateTimeString(),
                ];
            }
        });
    }

    /**
     * ตรวจสอบว่า PostXAgent พร้อมใช้งานหรือไม่
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        if (!config('postxagent.enabled', false)) {
            return false;
        }

        $status = $this->getStatus();

        return $status['online'] ?? false;
    }

    /**
     * ดึงรายการ AI providers ที่ PostXAgent รองรับ
     *
     * @return array
     */
    public function getAvailableProviders(): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders($this->getAuthHeaders())
                ->get($this->apiUrl . '/ai/providers');

            if ($response->successful()) {
                return $response->json()['providers'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::warning('PostXAgent: ไม่สามารถดึงรายการ providers', [
                'error' => $e->getMessage(),
            ]);

            // Return config fallback
            return config('postxagent.available_providers', []);
        }
    }

    /**
     * ดึงรายการ models สำหรับ provider ที่ระบุ
     *
     * @param string $provider
     * @return array
     */
    public function getAvailableModels(string $provider): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders($this->getAuthHeaders())
                ->get($this->apiUrl . '/ai/providers/' . $provider . '/models');

            if ($response->successful()) {
                return $response->json()['models'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::warning('PostXAgent: ไม่สามารถดึงรายการ models', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            // Return config fallback
            $providers = config('postxagent.available_providers', []);

            return $providers[$provider]['models'] ?? [];
        }
    }

    /**
     * Clear status cache
     *
     * @return void
     */
    public function clearStatusCache(): void
    {
        Cache::forget('postxagent_status');
    }

    /**
     * เตรียม request body
     *
     * @param array $messages
     * @param array $options
     * @return array
     */
    protected function prepareRequestBody(array $messages, array $options = []): array
    {
        $defaults = config('postxagent.defaults', []);

        // ใช้ provider/model จาก PostXAgent config ถ้ามี หรือจาก model entity
        $providerName = $options['provider'] ?? $this->config['postxagent_provider'] ?? $defaults['provider'] ?? 'ollama';
        $modelName = $options['model'] ?? $this->model->model_identifier ?? $defaults['model'] ?? 'llama3.2:3b';

        return [
            'provider' => $providerName,
            'model' => $modelName,
            'messages' => $this->formatMessages($messages),
            'temperature' => $options['temperature'] ?? $defaults['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? $this->model->max_output_tokens ?? $defaults['max_tokens'] ?? 2048,
            'stream' => $options['stream'] ?? false,
            'metadata' => [
                'source' => 'thaiprompt-affiliate',
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * ส่ง request พร้อม retry
     *
     * @param string $endpoint
     * @param array $body
     * @return array
     * @throws \Exception
     */
    protected function sendRequestWithRetry(string $endpoint, array $body): array
    {
        $maxAttempts = $this->retryConfig['max_attempts'] ?? 3;
        $delayMs = $this->retryConfig['delay_ms'] ?? 1000;
        $multiplier = $this->retryConfig['multiplier'] ?? 2;

        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::timeout(config('postxagent.timeout', 30))
                    ->withHeaders($this->getAuthHeaders())
                    ->post($this->apiUrl . $endpoint, $body);

                if ($response->successful()) {
                    return $response->json();
                }

                // ถ้า 4xx ไม่ต้อง retry
                if ($response->status() >= 400 && $response->status() < 500) {
                    throw new \Exception('PostXAgent API Error: ' . $response->body());
                }

                throw new \Exception('PostXAgent HTTP Error: ' . $response->status());
            } catch (\Exception $e) {
                $lastException = $e;

                if ($attempt < $maxAttempts) {
                    $sleepMs = $delayMs * pow($multiplier, $attempt - 1);
                    usleep($sleepMs * 1000);

                    Log::warning("PostXAgent: Retrying request (attempt {$attempt}/{$maxAttempts})", [
                        'endpoint' => $endpoint,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        throw $lastException ?? new \Exception('PostXAgent: Request failed after ' . $maxAttempts . ' attempts');
    }

    /**
     * Parse response จาก PostXAgent
     *
     * @param array $response
     * @return array
     */
    protected function parseResponse(array $response): array
    {
        return [
            'content' => $response['content'] ?? $response['message'] ?? '',
            'tokens' => $response['usage']['total_tokens'] ?? 0,
            'finish_reason' => $response['finish_reason'] ?? 'stop',
            'usage' => [
                'prompt_tokens' => $response['usage']['prompt_tokens'] ?? 0,
                'completion_tokens' => $response['usage']['completion_tokens'] ?? 0,
                'total_tokens' => $response['usage']['total_tokens'] ?? 0,
            ],
        ];
    }

    /**
     * สร้าง authentication headers
     *
     * @return array
     */
    protected function getAuthHeaders(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Source' => 'thaiprompt-affiliate',
        ];

        $authType = $this->authConfig['type'] ?? 'api_key';

        if ($authType === 'api_key' && !empty($this->authConfig['api_key'])) {
            $headers['X-API-Key'] = $this->authConfig['api_key'];
        } elseif ($authType === 'jwt' && !empty($this->authConfig['jwt_token'])) {
            $headers['Authorization'] = 'Bearer ' . $this->authConfig['jwt_token'];
        }

        return $headers;
    }

    /**
     * Log request
     *
     * @param array $request
     * @param array $response
     * @param float $duration
     * @param string $status
     * @return void
     */
    protected function logRequest(array $request, array $response, float $duration, string $status): void
    {
        if (!config('postxagent.logging.enabled', true)) {
            return;
        }

        $channel = config('postxagent.logging.channel', 'postxagent');
        $level = $status === 'error' ? 'error' : config('postxagent.logging.level', 'info');

        Log::channel($channel)->$level('PostXAgent Request', [
            'status' => $status,
            'duration_ms' => round($duration * 1000, 2),
            'provider' => $request['provider'] ?? 'unknown',
            'model' => $request['model'] ?? 'unknown',
            'messages_count' => count($request['messages'] ?? []),
            'response_tokens' => $response['tokens'] ?? 0,
            'error' => $response['error'] ?? null,
        ]);
    }
}
