<?php

namespace App\Services\Chatbot;

use App\Models\AiBotProfile;
use App\Models\AiConversation;
use App\Models\ChatbotKeywordResponse;
use App\Models\ChatbotPlatformIntegration;
use App\Services\AI\AiServiceFactory;
use Illuminate\Support\Facades\Log;

/**
 * Hybrid Chatbot Engine
 *
 * ระบบแชทบอทแบบไฮบริด:
 * 1. ตรวจสอบ Keyword-based Response ก่อน (รวดเร็ว, ไม่เสีย token)
 * 2. ถ้าไม่มี keyword ที่ตรง จะส่งต่อไป AI (ChatGPT, Gemini, etc.)
 * 3. รองรับการทำงานหลายแพลตฟอร์ม
 */
class HybridChatbotEngine
{
    protected AiServiceFactory $aiServiceFactory;

    public function __construct(AiServiceFactory $aiServiceFactory = null)
    {
        $this->aiServiceFactory = $aiServiceFactory ?? new AiServiceFactory();
    }

    /**
     * Process incoming message
     */
    public function processMessage(
        AiBotProfile $botProfile,
        string $message,
        array $context = []
    ): array {
        $startTime = microtime(true);

        try {
            // Step 1: Check keyword-based responses first
            $keywordResponse = $this->checkKeywordResponses($botProfile, $message);

            if ($keywordResponse) {
                Log::info('Chatbot: Keyword match found', [
                    'bot_id' => $botProfile->id,
                    'keyword_id' => $keywordResponse['keyword_id'],
                ]);

                return [
                    'success' => true,
                    'response' => $keywordResponse['text'],
                    'media' => $keywordResponse['media'] ?? null,
                    'quick_replies' => $keywordResponse['quick_replies'] ?? null,
                    'type' => 'keyword',
                    'processing_time_ms' => (microtime(true) - $startTime) * 1000,
                    'tokens_used' => 0,
                    'cost' => 0,
                ];
            }

            // Step 2: Fall back to AI if keyword not found
            if ($botProfile->provider && $botProfile->model) {
                Log::info('Chatbot: Falling back to AI', [
                    'bot_id' => $botProfile->id,
                    'provider' => $botProfile->provider->name,
                    'model' => $botProfile->model->model_identifier,
                ]);

                $aiResponse = $this->getAiResponse($botProfile, $message, $context);

                return [
                    'success' => true,
                    'response' => $aiResponse['content'],
                    'type' => 'ai',
                    'provider' => $botProfile->provider->name,
                    'model' => $botProfile->model->model_identifier,
                    'processing_time_ms' => (microtime(true) - $startTime) * 1000,
                    'tokens_used' => $aiResponse['tokens_used'] ?? 0,
                    'cost' => $aiResponse['cost'] ?? 0,
                ];
            }

            // Step 3: No keyword match and no AI configured - return default message
            return [
                'success' => true,
                'response' => 'ขออภัยครับ ผมไม่เข้าใจคำถามของคุณ กรุณาลองถามใหม่อีกครั้ง',
                'type' => 'default',
                'processing_time_ms' => (microtime(true) - $startTime) * 1000,
                'tokens_used' => 0,
                'cost' => 0,
            ];

        } catch (\Exception $e) {
            Log::error('Chatbot: Error processing message', [
                'bot_id' => $botProfile->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'เกิดข้อผิดพลาดในการประมวลผล กรุณาลองใหม่อีกครั้ง',
                'error_details' => $e->getMessage(),
                'processing_time_ms' => (microtime(true) - $startTime) * 1000,
            ];
        }
    }

    /**
     * Check keyword-based responses
     */
    protected function checkKeywordResponses(AiBotProfile $botProfile, string $message): ?array
    {
        $keywordResponses = ChatbotKeywordResponse::where('bot_profile_id', $botProfile->id)
            ->active()
            ->orderedByPriority()
            ->get();

        foreach ($keywordResponses as $keywordResponse) {
            if ($keywordResponse->matches($message)) {
                // Increment usage count
                $keywordResponse->incrementUsage();

                $fullResponse = $keywordResponse->getFullResponse();
                $fullResponse['keyword_id'] = $keywordResponse->id;

                return $fullResponse;
            }
        }

        return null;
    }

    /**
     * Get AI response
     */
    protected function getAiResponse(
        AiBotProfile $botProfile,
        string $message,
        array $context = []
    ): array {
        $aiService = $this->aiServiceFactory->make($botProfile->provider->name);

        $systemPrompt = $botProfile->system_prompt ?? 'You are a helpful assistant.';
        $conversationHistory = $context['conversation_history'] ?? [];

        // Build messages array
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Add conversation history
        foreach ($conversationHistory as $history) {
            $messages[] = [
                'role' => $history['role'],
                'content' => $history['content'],
            ];
        }

        // Add current message
        $messages[] = [
            'role' => 'user',
            'content' => $message,
        ];

        // Call AI service
        $response = $aiService->chat([
            'model' => $botProfile->model->model_identifier,
            'messages' => $messages,
            'temperature' => $botProfile->temperature ?? 0.7,
            'max_tokens' => $botProfile->max_tokens ?? 2000,
            'top_p' => $botProfile->top_p ?? 1.0,
        ]);

        return $response;
    }

    /**
     * Process message with conversation context
     */
    public function processMessageWithConversation(
        AiBotProfile $botProfile,
        string $message,
        AiConversation $conversation = null
    ): array {
        // Get conversation history
        $conversationHistory = [];
        if ($conversation) {
            $messages = $conversation->messages()
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->reverse();

            foreach ($messages as $msg) {
                $conversationHistory[] = [
                    'role' => $msg->role,
                    'content' => $msg->content,
                ];
            }
        }

        // Process message
        $result = $this->processMessage($botProfile, $message, [
            'conversation_history' => $conversationHistory,
        ]);

        // Save to conversation if exists
        if ($conversation && $result['success']) {
            // Save user message
            $conversation->addMessage('user', $message);

            // Save assistant response
            $conversation->addMessage('assistant', $result['response'], [
                'tokens_used' => $result['tokens_used'] ?? 0,
                'model_used' => $result['model'] ?? null,
                'provider_used' => $result['provider'] ?? null,
                'response_time_ms' => $result['processing_time_ms'] ?? 0,
                'cost' => $result['cost'] ?? 0,
            ]);
        }

        return $result;
    }

    /**
     * Validate bot configuration
     */
    public function validateBotConfiguration(AiBotProfile $botProfile): array
    {
        $errors = [];

        // Check if has keyword responses or AI configured
        $hasKeywordResponses = ChatbotKeywordResponse::where('bot_profile_id', $botProfile->id)
            ->active()
            ->exists();

        $hasAiConfigured = $botProfile->provider_id && $botProfile->model_id;

        if (!$hasKeywordResponses && !$hasAiConfigured) {
            $errors[] = 'บอทต้องมีการตั้งค่า Keyword Responses หรือ AI อย่างน้อย 1 อย่าง';
        }

        // Check AI configuration if exists
        if ($hasAiConfigured) {
            if (!$botProfile->provider->is_active) {
                $errors[] = 'AI Provider ไม่พร้อมใช้งาน';
            }

            if (!$botProfile->model->is_active) {
                $errors[] = 'AI Model ไม่พร้อมใช้งาน';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'has_keyword_responses' => $hasKeywordResponses,
            'has_ai_configured' => $hasAiConfigured,
        ];
    }
}
