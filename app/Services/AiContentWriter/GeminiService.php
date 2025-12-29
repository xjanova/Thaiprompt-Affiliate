<?php

namespace App\Services\AiContentWriter;

use App\Models\AiContentSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gemini Service (Google)
 *
 * จัดการการเชื่อมต่อกับ Google Gemini API
 * รองรับ Gemini Pro, Gemini Pro Vision
 */
class GeminiService
{
    /**
     * Base URL ของ Gemini API
     */
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    /**
     * API Key
     */
    protected ?string $apiKey;

    /**
     * โมเดลที่รองรับ
     */
    public const MODELS = [
        'gemini-1.5-pro' => 'Gemini 1.5 Pro (ล่าสุด)',
        'gemini-1.5-flash' => 'Gemini 1.5 Flash (เร็ว)',
        'gemini-pro' => 'Gemini Pro',
    ];

    /**
     * ราคาต่อ 1M tokens (USD) - ประมาณการ
     */
    public const PRICING = [
        'gemini-1.5-pro' => ['input' => 3.50, 'output' => 10.50],
        'gemini-1.5-flash' => ['input' => 0.35, 'output' => 1.05],
        'gemini-pro' => ['input' => 0.50, 'output' => 1.50],
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->apiKey = AiContentSetting::getValue('gemini_api_key');
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
            $url = $this->baseUrl.'/models?key='.$this->apiKey;

            $response = Http::timeout(10)->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'เชื่อมต่อ Gemini สำเร็จ',
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

        $model = $options['model'] ?? 'gemini-1.5-flash';
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 2000;

        // รวม system prompt กับ user prompt
        $fullPrompt = '';
        if (! empty($systemPrompt)) {
            $fullPrompt = "Instructions: {$systemPrompt}\n\n";
        }
        $fullPrompt .= $userPrompt;

        $url = $this->baseUrl.'/models/'.$model.':generateContent?key='.$this->apiKey;

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $fullPrompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxTokens,
            ],
        ];

        $startTime = microtime(true);

        try {
            $response = Http::timeout(120)
                ->post($url, $payload);

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $data = $response->json();

                // ดึง content จาก response
                $content = '';
                $candidates = $data['candidates'] ?? [];
                if (! empty($candidates[0]['content']['parts'])) {
                    foreach ($candidates[0]['content']['parts'] as $part) {
                        $content .= $part['text'] ?? '';
                    }
                }

                // ดึง usage metadata
                $usageMetadata = $data['usageMetadata'] ?? [];
                $promptTokens = $usageMetadata['promptTokenCount'] ?? 0;
                $completionTokens = $usageMetadata['candidatesTokenCount'] ?? 0;

                // คำนวณค่าใช้จ่าย
                $cost = $this->calculateCost($model, [
                    'prompt_tokens' => $promptTokens,
                    'completion_tokens' => $completionTokens,
                ]);

                return [
                    'success' => true,
                    'content' => $content,
                    'usage' => [
                        'prompt_tokens' => $promptTokens,
                        'completion_tokens' => $completionTokens,
                        'total_tokens' => $promptTokens + $completionTokens,
                        'cost' => $cost,
                    ],
                    'model' => $model,
                    'response_time_ms' => $responseTime,
                ];
            }

            $error = $response->json()['error']['message'] ?? $response->body();

            Log::error('Gemini API Error', [
                'status' => $response->status(),
                'error' => $error,
            ]);

            return [
                'success' => false,
                'error' => $error,
                'error_code' => 'API_ERROR',
            ];

        } catch (\Exception $e) {
            Log::error('Gemini Exception', [
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
        $pricing = self::PRICING[$model] ?? self::PRICING['gemini-1.5-flash'];

        $inputCost = ($usage['prompt_tokens'] ?? 0) / 1_000_000 * $pricing['input'];
        $outputCost = ($usage['completion_tokens'] ?? 0) / 1_000_000 * $pricing['output'];

        return round($inputCost + $outputCost, 6);
    }
}
