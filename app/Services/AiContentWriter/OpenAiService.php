<?php

namespace App\Services\AiContentWriter;

use App\Models\AiContentSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI Service
 *
 * จัดการการเชื่อมต่อกับ OpenAI API
 * รองรับ GPT-4, GPT-4 Turbo, GPT-3.5 Turbo
 */
class OpenAiService
{
    /**
     * Base URL ของ OpenAI API
     */
    protected string $baseUrl = 'https://api.openai.com/v1';

    /**
     * API Key
     */
    protected ?string $apiKey;

    /**
     * Organization ID (optional)
     */
    protected ?string $organizationId;

    /**
     * โมเดลที่รองรับ
     */
    public const MODELS = [
        'gpt-4o' => 'GPT-4o (ล่าสุด)',
        'gpt-4o-mini' => 'GPT-4o Mini (เร็ว, ประหยัด)',
        'gpt-4-turbo' => 'GPT-4 Turbo',
        'gpt-4' => 'GPT-4',
        'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
    ];

    /**
     * ราคาต่อ 1M tokens (USD)
     */
    public const PRICING = [
        'gpt-4o' => ['input' => 2.50, 'output' => 10.00],
        'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
        'gpt-4-turbo' => ['input' => 10.00, 'output' => 30.00],
        'gpt-4' => ['input' => 30.00, 'output' => 60.00],
        'gpt-3.5-turbo' => ['input' => 0.50, 'output' => 1.50],
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->apiKey = AiContentSetting::getValue('openai_api_key');
        $this->organizationId = AiContentSetting::getValue('openai_organization_id');
    }

    /**
     * ตรวจสอบว่าพร้อมใช้งานหรือไม่
     */
    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * ทดสอบการเชื่อมต่อ
     */
    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'ยังไม่ได้ตั้งค่า API Key',
            ];
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(10)
                ->get($this->baseUrl.'/models');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'เชื่อมต่อ OpenAI สำเร็จ',
                ];
            }

            return [
                'success' => false,
                'message' => 'เชื่อมต่อล้มเหลว: '.$response->body(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ];
        }
    }

    /**
     * สร้าง Content ด้วย Chat Completion
     */
    public function generateContent(
        string $systemPrompt,
        string $userPrompt,
        array $options = []
    ): array {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'ยังไม่ได้ตั้งค่า API Key',
            ];
        }

        $model = $options['model'] ?? 'gpt-4o-mini';
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 2000;

        $messages = [];

        if (! empty($systemPrompt)) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt,
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $userPrompt,
        ];

        $startTime = microtime(true);

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(120)
                ->post($this->baseUrl.'/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => $temperature,
                    'max_tokens' => $maxTokens,
                ]);

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '';
                $usage = $data['usage'] ?? [];

                // คำนวณค่าใช้จ่าย
                $cost = $this->calculateCost($model, $usage);

                return [
                    'success' => true,
                    'content' => $content,
                    'usage' => [
                        'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                        'completion_tokens' => $usage['completion_tokens'] ?? 0,
                        'total_tokens' => $usage['total_tokens'] ?? 0,
                        'cost' => $cost,
                    ],
                    'model' => $model,
                    'response_time_ms' => $responseTime,
                ];
            }

            $error = $response->json()['error']['message'] ?? $response->body();

            Log::error('OpenAI API Error', [
                'status' => $response->status(),
                'error' => $error,
            ]);

            return [
                'success' => false,
                'error' => $error,
                'error_code' => 'API_ERROR',
            ];

        } catch (\Exception $e) {
            Log::error('OpenAI Exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
                'error_code' => 'EXCEPTION',
            ];
        }
    }

    /**
     * Stream Content (สำหรับ real-time output)
     */
    public function streamContent(
        string $systemPrompt,
        string $userPrompt,
        callable $callback,
        array $options = []
    ): array {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'ยังไม่ได้ตั้งค่า API Key',
            ];
        }

        $model = $options['model'] ?? 'gpt-4o-mini';
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 2000;

        $messages = [];

        if (! empty($systemPrompt)) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt,
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $userPrompt,
        ];

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withOptions(['stream' => true])
                ->timeout(120)
                ->post($this->baseUrl.'/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => $temperature,
                    'max_tokens' => $maxTokens,
                    'stream' => true,
                ]);

            $fullContent = '';

            foreach (explode("\n", $response->body()) as $line) {
                if (str_starts_with($line, 'data: ')) {
                    $data = substr($line, 6);

                    if ($data === '[DONE]') {
                        break;
                    }

                    $json = json_decode($data, true);
                    $chunk = $json['choices'][0]['delta']['content'] ?? '';

                    if ($chunk) {
                        $fullContent .= $chunk;
                        $callback($chunk);
                    }
                }
            }

            return [
                'success' => true,
                'content' => $fullContent,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * คำนวณค่าใช้จ่าย
     */
    protected function calculateCost(string $model, array $usage): float
    {
        $pricing = self::PRICING[$model] ?? self::PRICING['gpt-4o-mini'];

        $inputCost = ($usage['prompt_tokens'] ?? 0) / 1_000_000 * $pricing['input'];
        $outputCost = ($usage['completion_tokens'] ?? 0) / 1_000_000 * $pricing['output'];

        return round($inputCost + $outputCost, 6);
    }

    /**
     * ดึง Headers สำหรับ Request
     */
    protected function getHeaders(): array
    {
        $headers = [
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
        ];

        if ($this->organizationId) {
            $headers['OpenAI-Organization'] = $this->organizationId;
        }

        return $headers;
    }
}
