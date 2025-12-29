<?php

namespace App\Services\AiContentWriter;

use App\Models\AiContentSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Claude Service (Anthropic)
 *
 * จัดการการเชื่อมต่อกับ Anthropic Claude API
 * รองรับ Claude 3.5 Sonnet, Claude 3 Opus, Haiku
 */
class ClaudeService
{
    /**
     * Base URL ของ Claude API
     */
    protected string $baseUrl = 'https://api.anthropic.com/v1';

    /**
     * API Key
     */
    protected ?string $apiKey;

    /**
     * API Version
     */
    protected string $apiVersion = '2023-06-01';

    /**
     * โมเดลที่รองรับ
     */
    public const MODELS = [
        'claude-sonnet-4-5-20250929' => 'Claude 4.5 Sonnet (ล่าสุด)',
        'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet',
        'claude-3-opus-20240229' => 'Claude 3 Opus (ฉลาดสุด)',
        'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
        'claude-3-haiku-20240307' => 'Claude 3 Haiku (เร็ว)',
    ];

    /**
     * ราคาต่อ 1M tokens (USD)
     */
    public const PRICING = [
        'claude-sonnet-4-5-20250929' => ['input' => 3.00, 'output' => 15.00],
        'claude-3-5-sonnet-20241022' => ['input' => 3.00, 'output' => 15.00],
        'claude-3-opus-20240229' => ['input' => 15.00, 'output' => 75.00],
        'claude-3-sonnet-20240229' => ['input' => 3.00, 'output' => 15.00],
        'claude-3-haiku-20240307' => ['input' => 0.25, 'output' => 1.25],
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->apiKey = AiContentSetting::getValue('claude_api_key');
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
            // ทดสอบด้วยการส่ง request ง่ายๆ
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(10)
                ->post($this->baseUrl.'/messages', [
                    'model' => 'claude-3-haiku-20240307',
                    'max_tokens' => 10,
                    'messages' => [
                        ['role' => 'user', 'content' => 'Hi'],
                    ],
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'เชื่อมต่อ Claude สำเร็จ',
                ];
            }

            $error = $response->json()['error']['message'] ?? $response->body();

            return [
                'success' => false,
                'message' => 'เชื่อมต่อล้มเหลว: '.$error,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ];
        }
    }

    /**
     * สร้าง Content
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

        $model = $options['model'] ?? 'claude-3-haiku-20240307';
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 2000;

        $payload = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'messages' => [
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ];

        // Claude ใช้ system prompt แยก
        if (! empty($systemPrompt)) {
            $payload['system'] = $systemPrompt;
        }

        // Temperature (Claude ใช้ 0-1)
        if ($temperature > 0) {
            $payload['temperature'] = min(1, $temperature);
        }

        $startTime = microtime(true);

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(120)
                ->post($this->baseUrl.'/messages', $payload);

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $data = $response->json();

                // Claude ส่ง content เป็น array
                $content = '';
                foreach ($data['content'] ?? [] as $block) {
                    if ($block['type'] === 'text') {
                        $content .= $block['text'];
                    }
                }

                $usage = $data['usage'] ?? [];

                // คำนวณค่าใช้จ่าย
                $cost = $this->calculateCost($model, [
                    'prompt_tokens' => $usage['input_tokens'] ?? 0,
                    'completion_tokens' => $usage['output_tokens'] ?? 0,
                ]);

                return [
                    'success' => true,
                    'content' => $content,
                    'usage' => [
                        'prompt_tokens' => $usage['input_tokens'] ?? 0,
                        'completion_tokens' => $usage['output_tokens'] ?? 0,
                        'total_tokens' => ($usage['input_tokens'] ?? 0) + ($usage['output_tokens'] ?? 0),
                        'cost' => $cost,
                    ],
                    'model' => $model,
                    'response_time_ms' => $responseTime,
                ];
            }

            $error = $response->json()['error']['message'] ?? $response->body();

            Log::error('Claude API Error', [
                'status' => $response->status(),
                'error' => $error,
            ]);

            return [
                'success' => false,
                'error' => $error,
                'error_code' => 'API_ERROR',
            ];

        } catch (\Exception $e) {
            Log::error('Claude Exception', [
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
     * คำนวณค่าใช้จ่าย
     */
    protected function calculateCost(string $model, array $usage): float
    {
        $pricing = self::PRICING[$model] ?? self::PRICING['claude-3-haiku-20240307'];

        $inputCost = ($usage['prompt_tokens'] ?? 0) / 1_000_000 * $pricing['input'];
        $outputCost = ($usage['completion_tokens'] ?? 0) / 1_000_000 * $pricing['output'];

        return round($inputCost + $outputCost, 6);
    }

    /**
     * ดึง Headers สำหรับ Request
     */
    protected function getHeaders(): array
    {
        return [
            'x-api-key' => $this->apiKey,
            'anthropic-version' => $this->apiVersion,
            'Content-Type' => 'application/json',
        ];
    }
}
