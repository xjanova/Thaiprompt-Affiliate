<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiService extends BaseAiService
{
    /**
     * ส่ง chat completion request
     */
    public function chat(array $messages, array $options = []): array
    {
        $startTime = microtime(true);

        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->getApiKey(),
                    'Content-Type' => 'application/json',
                ])
                ->post($this->getEndpoint() . '/chat/completions', [
                    'model' => $this->model->model_identifier,
                    'messages' => $this->formatMessages($messages),
                    'temperature' => $options['temperature'] ?? 0.7,
                    'max_tokens' => $options['max_tokens'] ?? 2000,
                    'top_p' => $options['top_p'] ?? 1,
                    'frequency_penalty' => $options['frequency_penalty'] ?? 0,
                    'presence_penalty' => $options['presence_penalty'] ?? 0,
                ]);

            $responseTime = (microtime(true) - $startTime) * 1000;

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'content' => $data['choices'][0]['message']['content'] ?? '',
                    'finish_reason' => $data['choices'][0]['finish_reason'] ?? 'stop',
                    'usage' => $data['usage'] ?? [],
                    'response_time_ms' => $responseTime,
                    'raw_response' => $data,
                ];
            } else {
                $error = $response->json();

                return [
                    'success' => false,
                    'error' => $error['error']['message'] ?? 'Unknown error',
                    'error_type' => $error['error']['type'] ?? 'api_error',
                    'response_time_ms' => $responseTime,
                ];
            }
        } catch (\Exception $e) {
            Log::error('OpenAI API Error', [
                'error' => $e->getMessage(),
                'model' => $this->model->model_identifier,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_type' => 'exception',
                'response_time_ms' => (microtime(true) - $startTime) * 1000,
            ];
        }
    }

    /**
     * ส่ง chat แบบ streaming
     */
    public function chatStream(array $messages, array $options, callable $callback): array
    {
        // TODO: Implement streaming
        // OpenAI supports Server-Sent Events (SSE)

        return [
            'success' => false,
            'error' => 'Streaming not implemented yet',
        ];
    }

    /**
     * สร้าง embeddings
     */
    public function createEmbedding(string $text): array
    {
        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->getApiKey(),
                    'Content-Type' => 'application/json',
                ])
                ->post($this->getEndpoint() . '/embeddings', [
                    'model' => 'text-embedding-3-small', // หรือ text-embedding-ada-002
                    'input' => $text,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'embedding' => $data['data'][0]['embedding'] ?? [],
                    'tokens' => $data['usage']['total_tokens'] ?? 0,
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response->json()['error']['message'] ?? 'Unknown error',
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ทดสอบการเชื่อมต่อ
     */
    public function testConnection(): array
    {
        if (!$this->hasApiKey()) {
            return [
                'success' => false,
                'message' => 'ไม่พบ API Key',
            ];
        }

        try {
            // ทดสอบด้วยการ list models
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->getApiKey(),
                ])
                ->get($this->getEndpoint() . '/models');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'เชื่อมต่อ OpenAI สำเร็จ',
                    'details' => [
                        'models_available' => count($response->json()['data'] ?? []),
                    ],
                ];
            } else {
                $error = $response->json();

                return [
                    'success' => false,
                    'message' => $error['error']['message'] ?? 'Connection failed',
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ];
        }
    }
}
