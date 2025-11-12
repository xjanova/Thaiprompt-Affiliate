<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DeepSeek Service
 */
class DeepSeekService implements AiServiceInterface
{
    protected string $apiKey;
    protected string $apiEndpoint;

    public function __construct()
    {
        $this->apiKey = config('services.deepseek.api_key') ?? env('DEEPSEEK_API_KEY');
        $this->apiEndpoint = config('services.deepseek.api_endpoint') ?? 'https://api.deepseek.com/v1';
    }

    public function chat(array $params): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->apiEndpoint}/chat/completions", [
                'model' => $params['model'] ?? 'deepseek-chat',
                'messages' => $params['messages'],
                'temperature' => $params['temperature'] ?? 0.7,
                'max_tokens' => $params['max_tokens'] ?? 2000,
            ]);

            if (!$response->successful()) {
                throw new \Exception("DeepSeek API error: " . $response->body());
            }

            $data = $response->json();

            return [
                'content' => $data['choices'][0]['message']['content'] ?? '',
                'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                'cost' => $this->calculateCost($data['usage'] ?? []),
                'model' => $params['model'] ?? 'deepseek-chat',
                'finish_reason' => $data['choices'][0]['finish_reason'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('DeepSeek: Chat request failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function generate(string $prompt, array $options = []): array
    {
        return $this->chat([
            'model' => $options['model'] ?? 'deepseek-chat',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 2000,
        ]);
    }

    protected function calculateCost(array $usage): float
    {
        // DeepSeek pricing: very cheap
        $inputCost = ($usage['prompt_tokens'] ?? 0) / 1000 * 0.00014;
        $outputCost = ($usage['completion_tokens'] ?? 0) / 1000 * 0.00028;

        return round($inputCost + $outputCost, 6);
    }
}
