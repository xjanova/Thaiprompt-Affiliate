<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeService extends BaseAiService
{
    public function chat(array $messages, array $options = []): array
    {
        $startTime = microtime(true);

        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'x-api-key' => $this->getApiKey(),
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ])
                ->post($this->getEndpoint() . '/messages', [
                    'model' => $this->model->model_identifier,
                    'messages' => $this->formatMessagesForClaude($messages),
                    'max_tokens' => $options['max_tokens'] ?? 4096,
                    'temperature' => $options['temperature'] ?? 0.7,
                ]);

            $responseTime = (microtime(true) - $startTime) * 1000;

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'content' => $data['content'][0]['text'] ?? '',
                    'finish_reason' => $data['stop_reason'] ?? 'end_turn',
                    'usage' => [
                        'prompt_tokens' => $data['usage']['input_tokens'] ?? 0,
                        'completion_tokens' => $data['usage']['output_tokens'] ?? 0,
                        'total_tokens' => ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0),
                    ],
                    'response_time_ms' => $responseTime,
                    'raw_response' => $data,
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response->json()['error']['message'] ?? 'Unknown error',
                    'response_time_ms' => $responseTime,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Claude API Error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response_time_ms' => (microtime(true) - $startTime) * 1000,
            ];
        }
    }

    /**
     * Claude ใช้ format messages แตกต่างเล็กน้อย
     */
    private function formatMessagesForClaude(array $messages): array
    {
        // Claude ต้องการ messages เป็น user/assistant สลับกัน
        // และ system message ต้องแยกออกมา
        return array_values(array_filter($messages, function ($msg) {
            return $msg['role'] !== 'system';
        }));
    }

    public function chatStream(array $messages, array $options, callable $callback): array
    {
        // TODO: Implement streaming with Server-Sent Events
        return ['success' => false, 'error' => 'Streaming not implemented yet'];
    }

    public function createEmbedding(string $text): array
    {
        // Claude ไม่มี embedding API
        // ต้องใช้ OpenAI หรือ Cohere แทน
        return [
            'success' => false,
            'error' => 'Claude does not support embeddings',
        ];
    }

    public function testConnection(): array
    {
        if (!$this->hasApiKey()) {
            return ['success' => false, 'message' => 'ไม่พบ API Key'];
        }

        try {
            // ทดสอบด้วยการส่ง request สั้นๆ
            $response = $this->chat([
                ['role' => 'user', 'content' => 'Hi'],
            ], ['max_tokens' => 10]);

            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'เชื่อมต่อ Claude สำเร็จ',
                    'details' => ['test_response' => $response['content']],
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? 'Connection failed',
                ];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
