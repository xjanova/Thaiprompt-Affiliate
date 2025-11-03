<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocalAiService extends BaseAiService
{
    private string $ollamaEndpoint = 'http://localhost:11434';

    public function chat(array $messages, array $options = []): array
    {
        $startTime = microtime(true);

        try {
            $response = Http::timeout(120)
                ->post($this->ollamaEndpoint . '/api/chat', [
                    'model' => $this->model->model_identifier,
                    'messages' => $this->formatMessages($messages),
                    'stream' => false,
                    'options' => [
                        'temperature' => $options['temperature'] ?? 0.7,
                        'num_predict' => $options['max_tokens'] ?? 2000,
                    ],
                ]);

            $responseTime = (microtime(true) - $startTime) * 1000;

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'content' => $data['message']['content'] ?? '',
                    'finish_reason' => $data['done'] ? 'stop' : 'length',
                    'usage' => [
                        'prompt_tokens' => $data['prompt_eval_count'] ?? 0,
                        'completion_tokens' => $data['eval_count'] ?? 0,
                        'total_tokens' => ($data['prompt_eval_count'] ?? 0) + ($data['eval_count'] ?? 0),
                    ],
                    'response_time_ms' => $responseTime,
                    'raw_response' => $data,
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Ollama request failed',
                    'response_time_ms' => $responseTime,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Ollama API Error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response_time_ms' => (microtime(true) - $startTime) * 1000,
            ];
        }
    }

    public function chatStream(array $messages, array $options, callable $callback): array
    {
        // TODO: Implement streaming
        return ['success' => false, 'error' => 'Streaming not implemented yet'];
    }

    public function createEmbedding(string $text): array
    {
        try {
            $response = Http::timeout(60)
                ->post($this->ollamaEndpoint . '/api/embeddings', [
                    'model' => 'nomic-embed-text', // หรือโมเดล embedding อื่นที่ติดตั้งไว้
                    'prompt' => $text,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'embedding' => $data['embedding'] ?? [],
                    'tokens' => 0, // Ollama ไม่ return token count
                ];
            } else {
                return ['success' => false, 'error' => 'Embedding failed'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function testConnection(): array
    {
        try {
            $response = Http::timeout(5)->get($this->ollamaEndpoint . '/api/tags');

            if ($response->successful()) {
                $models = $response->json()['models'] ?? [];

                return [
                    'success' => true,
                    'message' => 'เชื่อมต่อ Ollama สำเร็จ',
                    'details' => [
                        'endpoint' => $this->ollamaEndpoint,
                        'models_count' => count($models),
                    ],
                ];
            } else {
                return ['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อ Ollama ได้'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Ollama ไม่ได้ทำงาน: ' . $e->getMessage()];
        }
    }

    /**
     * Override calculateCost สำหรับ Local AI (ฟรี)
     */
    protected function calculateCost(int $promptTokens, int $completionTokens): float
    {
        return 0.0; // Local AI ไม่มีค่าใช้จ่าย
    }
}
