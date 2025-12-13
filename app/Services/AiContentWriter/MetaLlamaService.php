<?php

namespace App\Services\AiContentWriter;

use App\Models\AiContentSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Meta Llama Service
 *
 * จัดการการเชื่อมต่อกับ Meta Llama API ผ่าน Together AI
 * รองรับ Llama 4 Scout, Maverick และ Llama 3.x models
 * ใช้ OpenAI-compatible API
 *
 * @package App\Services\AiContentWriter
 */
class MetaLlamaService
{
    /**
     * Base URL ของ Together AI API (OpenAI-compatible)
     */
    protected string $baseUrl = 'https://api.together.xyz/v1';

    /**
     * API Key
     */
    protected ?string $apiKey;

    /**
     * โมเดลที่รองรับ
     */
    public const MODELS = [
        'meta-llama/Llama-4-Scout-17B-16E-Instruct' => 'Llama 4 Scout 17B (เร็ว, 10M context)',
        'meta-llama/Llama-4-Maverick-17B-128E-Instruct' => 'Llama 4 Maverick 17B (MoE, ประสิทธิภาพสูง)',
        'meta-llama/Llama-4-Maverick-17B-128E-Instruct-FP8' => 'Llama 4 Maverick FP8 (ประหยัด)',
        'meta-llama/Llama-3.3-70B-Instruct-Turbo' => 'Llama 3.3 70B Turbo',
        'meta-llama/Llama-3.2-11B-Vision-Instruct-Turbo' => 'Llama 3.2 11B Vision',
        'meta-llama/Llama-3.1-8B-Instruct-Turbo' => 'Llama 3.1 8B Turbo (เร็วที่สุด)',
        'meta-llama/Llama-3.1-405B-Instruct-Turbo' => 'Llama 3.1 405B (แม่นที่สุด)',
    ];

    /**
     * ราคาต่อ 1M tokens (USD)
     */
    public const PRICING = [
        'meta-llama/Llama-4-Scout-17B-16E-Instruct' => ['input' => 0.15, 'output' => 0.60],
        'meta-llama/Llama-4-Maverick-17B-128E-Instruct' => ['input' => 0.20, 'output' => 0.60],
        'meta-llama/Llama-4-Maverick-17B-128E-Instruct-FP8' => ['input' => 0.18, 'output' => 0.50],
        'meta-llama/Llama-3.3-70B-Instruct-Turbo' => ['input' => 0.12, 'output' => 0.30],
        'meta-llama/Llama-3.2-11B-Vision-Instruct-Turbo' => ['input' => 0.18, 'output' => 0.18],
        'meta-llama/Llama-3.1-8B-Instruct-Turbo' => ['input' => 0.06, 'output' => 0.06],
        'meta-llama/Llama-3.1-405B-Instruct-Turbo' => ['input' => 0.50, 'output' => 1.50],
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->apiKey = AiContentSetting::getValue('meta_llama_api_key')
            ?? AiContentSetting::getValue('together_ai_api_key');
    }

    /**
     * ตรวจสอบว่าพร้อมใช้งานหรือไม่
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * ทดสอบการเชื่อมต่อ
     *
     * @return array
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'ยังไม่ได้ตั้งค่า Together AI API Key',
            ];
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(10)
                ->get($this->baseUrl . '/models');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'เชื่อมต่อ Meta Llama (Together AI) สำเร็จ',
                ];
            }

            return [
                'success' => false,
                'message' => 'ไม่สามารถเชื่อมต่อได้: ' . ($response->json()['error']['message'] ?? 'Unknown error'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * สร้าง Content
     *
     * @param string $systemPrompt
     * @param string $userPrompt
     * @param array $options
     * @return array
     */
    public function generateContent(string $systemPrompt, string $userPrompt, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'ยังไม่ได้ตั้งค่า Together AI API Key',
                'error_code' => 'not_configured',
            ];
        }

        $startTime = microtime(true);

        try {
            $model = $options['model'] ?? 'meta-llama/Llama-4-Scout-17B-16E-Instruct';
            $maxTokens = $options['max_tokens'] ?? 4096;
            $temperature = $options['temperature'] ?? 0.7;

            $response = Http::withHeaders($this->getHeaders())
                ->timeout(120)
                ->post($this->baseUrl . '/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'max_tokens' => $maxTokens,
                    'temperature' => $temperature,
                    'top_p' => $options['top_p'] ?? 1.0,
                    'stop' => $options['stop'] ?? null,
                ]);

            $responseTime = (microtime(true) - $startTime) * 1000;

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '';
                $usage = $data['usage'] ?? [];

                // คำนวณค่าใช้จ่าย
                $pricing = self::PRICING[$model] ?? ['input' => 0.15, 'output' => 0.60];
                $cost = (
                    ($usage['prompt_tokens'] ?? 0) * $pricing['input'] / 1000000 +
                    ($usage['completion_tokens'] ?? 0) * $pricing['output'] / 1000000
                );

                return [
                    'success' => true,
                    'content' => $content,
                    'model' => $model,
                    'usage' => [
                        'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                        'completion_tokens' => $usage['completion_tokens'] ?? 0,
                        'total_tokens' => $usage['total_tokens'] ?? 0,
                        'cost' => round($cost, 6),
                    ],
                    'response_time_ms' => round($responseTime, 2),
                    'finish_reason' => $data['choices'][0]['finish_reason'] ?? 'stop',
                ];
            }

            $error = $response->json();
            Log::error('Meta Llama API Error', ['response' => $error]);

            return [
                'success' => false,
                'error' => $error['error']['message'] ?? 'Unknown error',
                'error_code' => $error['error']['code'] ?? 'api_error',
                'response_time_ms' => round($responseTime, 2),
            ];

        } catch (\Exception $e) {
            Log::error('Meta Llama Service Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'exception',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        }
    }

    /**
     * ดึง Headers สำหรับ API Request
     *
     * @return array
     */
    protected function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * ดึง Default Model
     *
     * @return string
     */
    public function getDefaultModel(): string
    {
        return 'meta-llama/Llama-4-Scout-17B-16E-Instruct';
    }

    /**
     * ดึงรายการ Models ที่รองรับ
     *
     * @return array
     */
    public function getModels(): array
    {
        return self::MODELS;
    }

    /**
     * ดึงราคาของ Model
     *
     * @param string $model
     * @return array
     */
    public function getPricing(string $model): array
    {
        return self::PRICING[$model] ?? ['input' => 0.15, 'output' => 0.60];
    }
}
