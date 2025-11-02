<?php

namespace App\Services;

use App\Models\LineBotAiSetting;
use App\Models\LineBotConversation;
use App\Models\LineBotKnowledgeBase;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineBotAiService
{
    private ?LineBotAiSetting $settings;

    public function __construct(?LineBotAiSetting $settings = null)
    {
        $this->settings = $settings ?? LineBotAiSetting::getActive();
    }

    /**
     * Generate AI response
     */
    public function generateResponse(
        string $userMessage,
        ?LineBotConversation $conversation = null,
        array $additionalContext = []
    ): array {
        if (!$this->settings) {
            throw new Exception('AI settings not configured');
        }

        $startTime = microtime(true);

        try {
            // Build context
            $context = $this->buildContext($conversation, $additionalContext);

            // Build messages array
            $messages = $this->buildMessages($userMessage, $conversation, $context);

            // Get response from AI provider
            $response = $this->callAiProvider($messages);

            $responseTime = microtime(true) - $startTime;

            return [
                'success' => true,
                'message' => $response['message'],
                'tokens_used' => $response['tokens_used'] ?? null,
                'response_time' => $responseTime,
                'provider' => $this->settings->provider,
            ];
        } catch (Exception $e) {
            Log::error('AI generation failed', [
                'error' => $e->getMessage(),
                'provider' => $this->settings->provider,
            ]);

            // Use fallback if enabled
            if ($this->settings->enable_fallback) {
                return [
                    'success' => false,
                    'message' => $this->settings->fallback_message,
                    'error' => $e->getMessage(),
                ];
            }

            throw $e;
        }
    }

    /**
     * Build context from knowledge bases
     */
    private function buildContext(?LineBotConversation $conversation, array $additional = []): string
    {
        $contextParts = [];

        // Add knowledge base content
        if ($this->settings->knowledge_base_sources) {
            $knowledgeBases = $this->settings->activeKnowledgeBases;

            foreach ($knowledgeBases as $kb) {
                $contextParts[] = $kb->getFormattedContent();
            }
        }

        // Add additional context
        if (!empty($additional)) {
            $contextParts[] = implode("\n", $additional);
        }

        return implode("\n\n---\n\n", $contextParts);
    }

    /**
     * Build messages for AI
     */
    private function buildMessages(
        string $userMessage,
        ?LineBotConversation $conversation,
        string $context
    ): array {
        $messages = [];

        // Add system prompt
        $systemPrompt = $this->settings->system_prompt ?? 'You are a helpful assistant.';

        // Add context to system prompt
        if (!empty($context)) {
            $systemPrompt .= "\n\nContext and Knowledge Base:\n" . $context;
        }

        $messages[] = [
            'role' => 'system',
            'content' => $systemPrompt,
        ];

        // Add conversation history if enabled
        if ($this->settings->enable_conversation_history && $conversation) {
            $history = $conversation->getHistoryForAI(
                $this->settings->conversation_memory_limit
            );

            foreach ($history as $msg) {
                $messages[] = $msg;
            }
        }

        // Add current user message
        $messages[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];

        return $messages;
    }

    /**
     * Call AI provider
     */
    private function callAiProvider(array $messages): array
    {
        return match ($this->settings->provider) {
            'openai' => $this->callOpenAI($messages),
            'deepseek' => $this->callDeepSeek($messages),
            'anthropic' => $this->callAnthropic($messages),
            'gemini' => $this->callGemini($messages),
            'custom' => $this->callCustomProvider($messages),
            default => throw new Exception('Unsupported AI provider: ' . $this->settings->provider),
        };
    }

    /**
     * Call OpenAI API
     */
    private function callOpenAI(array $messages): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->settings->api_key,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $this->settings->model,
            'messages' => $messages,
            'temperature' => $this->settings->temperature,
            'max_tokens' => $this->settings->max_tokens,
        ]);

        if (!$response->successful()) {
            throw new Exception('OpenAI API error: ' . $response->body());
        }

        $data = $response->json();

        return [
            'message' => $data['choices'][0]['message']['content'] ?? '',
            'tokens_used' => $data['usage']['total_tokens'] ?? null,
        ];
    }

    /**
     * Call DeepSeek API
     */
    private function callDeepSeek(array $messages): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->settings->api_key,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.deepseek.com/v1/chat/completions', [
            'model' => $this->settings->model ?: 'deepseek-chat',
            'messages' => $messages,
            'temperature' => $this->settings->temperature,
            'max_tokens' => $this->settings->max_tokens,
        ]);

        if (!$response->successful()) {
            throw new Exception('DeepSeek API error: ' . $response->body());
        }

        $data = $response->json();

        return [
            'message' => $data['choices'][0]['message']['content'] ?? '',
            'tokens_used' => $data['usage']['total_tokens'] ?? null,
        ];
    }

    /**
     * Call Anthropic (Claude) API
     */
    private function callAnthropic(array $messages): array
    {
        // Anthropic uses different format
        $systemMessage = '';
        $conversationMessages = [];

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemMessage = $msg['content'];
            } else {
                $conversationMessages[] = $msg;
            }
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->settings->api_key,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->settings->model ?: 'claude-3-sonnet-20240229',
            'system' => $systemMessage,
            'messages' => $conversationMessages,
            'temperature' => $this->settings->temperature,
            'max_tokens' => $this->settings->max_tokens,
        ]);

        if (!$response->successful()) {
            throw new Exception('Anthropic API error: ' . $response->body());
        }

        $data = $response->json();

        return [
            'message' => $data['content'][0]['text'] ?? '',
            'tokens_used' => $data['usage']['total_tokens'] ?? null,
        ];
    }

    /**
     * Call Google Gemini API
     */
    private function callGemini(array $messages): array
    {
        // Convert messages format for Gemini
        $contents = [];
        foreach ($messages as $msg) {
            if ($msg['role'] !== 'system') {
                $contents[] = [
                    'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                    'parts' => [['text' => $msg['content']]],
                ];
            }
        }

        $model = $this->settings->model ?: 'gemini-pro';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->settings->api_key}";

        $response = Http::timeout(60)->post($url, [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $this->settings->temperature,
                'maxOutputTokens' => $this->settings->max_tokens,
            ],
        ]);

        if (!$response->successful()) {
            throw new Exception('Gemini API error: ' . $response->body());
        }

        $data = $response->json();

        return [
            'message' => $data['candidates'][0]['content']['parts'][0]['text'] ?? '',
            'tokens_used' => null, // Gemini doesn't return token count in same format
        ];
    }

    /**
     * Call custom provider
     */
    private function callCustomProvider(array $messages): array
    {
        if (!$this->settings->api_endpoint) {
            throw new Exception('Custom API endpoint not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->settings->api_key,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post($this->settings->api_endpoint, [
            'messages' => $messages,
            'temperature' => $this->settings->temperature,
            'max_tokens' => $this->settings->max_tokens,
        ]);

        if (!$response->successful()) {
            throw new Exception('Custom API error: ' . $response->body());
        }

        $data = $response->json();

        // Assuming OpenAI-compatible format
        return [
            'message' => $data['choices'][0]['message']['content'] ?? $data['message'] ?? '',
            'tokens_used' => $data['usage']['total_tokens'] ?? null,
        ];
    }

    /**
     * Test AI connection
     */
    public function testConnection(): array
    {
        try {
            $response = $this->generateResponse('Hello! This is a test message.');

            return [
                'success' => true,
                'message' => 'Connection successful',
                'response' => $response['message'],
                'provider' => $this->settings->provider,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }
}
