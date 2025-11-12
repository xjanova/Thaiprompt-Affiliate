<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Anthropic Claude Service
 */
class AnthropicService implements AiServiceInterface
{
    protected string $apiKey;
    protected string $apiEndpoint;

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key') ?? env('ANTHROPIC_API_KEY');
        $this->apiEndpoint = 'https://api.anthropic.com/v1';
    }

    public function chat(array $params): array
    {
        try {
            // Extract system prompt
            $systemPrompt = '';
            $messages = [];

            foreach ($params['messages'] as $message) {
                if ($message['role'] === 'system') {
                    $systemPrompt = $message['content'];
                } else {
                    $messages[] = $message;
                }
            }

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->post("{$this->apiEndpoint}/messages", [
                'model' => $params['model'] ?? 'claude-3-sonnet-20240229',
                'messages' => $messages,
                'system' => $systemPrompt,
                'max_tokens' => $params['max_tokens'] ?? 2000,
                'temperature' => $params['temperature'] ?? 0.7,
            ]);

            if (!$response->successful()) {
                throw new \Exception("Anthropic API error: " . $response->body());
            }

            $data = $response->json();

            return [
                'content' => $data['content'][0]['text'] ?? '',
                'tokens_used' => ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0),
                'cost' => $this->calculateCost($data['usage'] ?? [], $params['model'] ?? 'claude-3-sonnet-20240229'),
                'model' => $params['model'] ?? 'claude-3-sonnet-20240229',
                'finish_reason' => $data['stop_reason'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Anthropic: Chat request failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function generate(string $prompt, array $options = []): array
    {
        return $this->chat([
            'model' => $options['model'] ?? 'claude-3-sonnet-20240229',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 2000,
        ]);
    }

    protected function calculateCost(array $usage, string $model): float
    {
        $pricing = [
            'claude-3-opus-20240229' => ['input' => 0.015, 'output' => 0.075],
            'claude-3-sonnet-20240229' => ['input' => 0.003, 'output' => 0.015],
            'claude-3-haiku-20240307' => ['input' => 0.00025, 'output' => 0.00125],
        ];

        $modelPricing = $pricing[$model] ?? $pricing['claude-3-sonnet-20240229'];

        $inputCost = ($usage['input_tokens'] ?? 0) / 1000 * $modelPricing['input'];
        $outputCost = ($usage['output_tokens'] ?? 0) / 1000 * $modelPricing['output'];

        return round($inputCost + $outputCost, 6);
    }
}
