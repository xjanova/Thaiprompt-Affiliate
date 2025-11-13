<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Gemini Service
 */
class GoogleGeminiService implements AiServiceInterface
{
    protected string $apiKey;
    protected string $apiEndpoint;

    public function __construct()
    {
        $this->apiKey = config('services.google.api_key') ?? env('GOOGLE_API_KEY');
        $this->apiEndpoint = 'https://generativelanguage.googleapis.com/v1';
    }

    public function chat(array $params): array
    {
        try {
            $model = $params['model'] ?? 'gemini-pro';

            // Build contents array for Gemini
            $contents = [];
            foreach ($params['messages'] as $message) {
                if ($message['role'] !== 'system') {
                    $contents[] = [
                        'role' => $message['role'] === 'assistant' ? 'model' : 'user',
                        'parts' => [['text' => $message['content']]],
                    ];
                }
            }

            $response = Http::post("{$this->apiEndpoint}/models/{$model}:generateContent", [
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => $params['temperature'] ?? 0.7,
                    'maxOutputTokens' => $params['max_tokens'] ?? 2000,
                    'topP' => $params['top_p'] ?? 1.0,
                ],
            ], [
                'key' => $this->apiKey,
            ]);

            if (!$response->successful()) {
                throw new \Exception("Google Gemini API error: " . $response->body());
            }

            $data = $response->json();

            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            return [
                'content' => $content,
                'tokens_used' => $this->estimateTokens($content),
                'cost' => 0, // Gemini has free tier
                'model' => $model,
                'finish_reason' => $data['candidates'][0]['finishReason'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Google Gemini: Chat request failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function generate(string $prompt, array $options = []): array
    {
        return $this->chat([
            'model' => $options['model'] ?? 'gemini-pro',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 2000,
        ]);
    }

    protected function estimateTokens(string $text): int
    {
        // Rough estimation: ~4 characters per token
        return (int) (strlen($text) / 4);
    }
}
