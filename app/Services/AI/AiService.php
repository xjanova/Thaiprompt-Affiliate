<?php

namespace App\Services\AI;

use App\Models\AiBotProfile;
use App\Models\AiConversation;
use App\Models\AiMessage;
use Illuminate\Support\Facades\Log;

/**
 * AI Service - Unified interface สำหรับ AI text generation
 *
 * Service นี้เป็น wrapper รอบ AiServiceFactory และ BaseAiService
 * เพื่อให้ใช้งานง่ายกว่าสำหรับ Bot Automation และ Chatbot features
 */
class AiService
{
    /**
     * สร้าง text จาก AI
     *
     * @param array $params {
     *     @type int $bot_profile_id ID ของ bot profile (required)
     *     @type string $prompt คำสั่งหรือคำถาม (required)
     *     @type string|null $system_prompt System prompt สำหรับกำหนด behavior ของ AI
     *     @type array|null $conversation_history ประวัติการสนทนา [['role' => 'user/assistant', 'content' => '...']]
     *     @type int $max_tokens จำนวน tokens สูงสุด (default: 500)
     *     @type float $temperature ระดับความสุ่ม 0-1 (default: 0.7)
     *     @type int|null $user_id ID ของผู้ใช้
     *     @type int|null $rental_id ID ของ rental (ถ้ามี)
     * }
     * @return array {
     *     @type string $text ข้อความที่ AI สร้าง
     *     @type int $tokens_used จำนวน tokens ที่ใช้
     *     @type float $cost ค่าใช้จ่ายในการเรียกใช้ (ถ้ามี)
     * }
     *
     * @throws \Exception
     */
    public function generateText(array $params): array
    {
        // ตรวจสอบ required parameters
        if (empty($params['bot_profile_id'])) {
            throw new \InvalidArgumentException('bot_profile_id is required');
        }

        if (empty($params['prompt'])) {
            throw new \InvalidArgumentException('prompt is required');
        }

        try {
            // โหลด bot profile
            $botProfile = AiBotProfile::with(['provider', 'model'])->findOrFail($params['bot_profile_id']);

            // ตรวจสอบว่า bot profile มี provider และ model
            if (!$botProfile->provider || !$botProfile->model) {
                throw new \Exception('Bot profile missing provider or model configuration');
            }

            // สร้าง AI service จาก factory
            $aiService = AiServiceFactory::createFromBot($botProfile);

            // เตรียม messages array
            $messages = $this->prepareMessages($params);

            // เตรียม options
            $options = [
                'max_tokens' => $params['max_tokens'] ?? $botProfile->max_tokens ?? 500,
                'temperature' => $params['temperature'] ?? $botProfile->temperature ?? 0.7,
            ];

            // เรียก AI service
            $startTime = microtime(true);
            $response = $aiService->chat($messages, $options);
            $responseTime = (microtime(true) - $startTime) * 1000; // milliseconds

            // ตรวจสอบว่า API call สำเร็จหรือไม่
            if (isset($response['success']) && $response['success'] === false) {
                throw new \Exception($response['error'] ?? 'AI service returned error');
            }

            // คำนวณ tokens และ cost
            $usage = $response['usage'] ?? [];
            $totalTokens = $usage['total_tokens'] ?? 0;
            $promptTokens = $usage['prompt_tokens'] ?? 0;
            $completionTokens = $usage['completion_tokens'] ?? 0;

            // คำนวณ cost จาก model pricing
            $cost = $this->calculateCost($botProfile->model, $promptTokens, $completionTokens);

            // สร้าง conversation record (ถ้ามี user_id)
            $conversation = null;
            if (!empty($params['user_id'])) {
                $conversation = $this->createConversationRecord($botProfile, $params, $messages, $response);

                // Log usage
                $this->logUsageRecord($conversation, $response, $responseTime, $cost);
            }

            return [
                'text' => $response['content'] ?? '',
                'tokens_used' => $totalTokens,
                'cost' => $cost,
                'finish_reason' => $response['finish_reason'] ?? 'stop',
                'conversation_id' => $conversation?->id,
            ];

        } catch (\Exception $e) {
            Log::error('AiService::generateText failed', [
                'error' => $e->getMessage(),
                'bot_profile_id' => $params['bot_profile_id'] ?? null,
                'prompt_preview' => substr($params['prompt'] ?? '', 0, 100),
            ]);

            throw $e;
        }
    }

    /**
     * เตรียม messages array จาก parameters
     *
     * @param array $params
     * @return array
     */
    protected function prepareMessages(array $params): array
    {
        $messages = [];

        // เพิ่ม system prompt (ถ้ามี)
        if (!empty($params['system_prompt'])) {
            $messages[] = [
                'role' => 'system',
                'content' => $params['system_prompt'],
            ];
        }

        // เพิ่ม conversation history (ถ้ามี)
        if (!empty($params['conversation_history']) && is_array($params['conversation_history'])) {
            foreach ($params['conversation_history'] as $message) {
                if (isset($message['role']) && isset($message['content'])) {
                    $messages[] = [
                        'role' => $message['role'],
                        'content' => $message['content'],
                    ];
                }
            }
        }

        // เพิ่ม prompt ปัจจุบัน
        $messages[] = [
            'role' => 'user',
            'content' => $params['prompt'],
        ];

        return $messages;
    }

    /**
     * สร้าง conversation record
     *
     * @param AiBotProfile $botProfile
     * @param array $params
     * @param array $messages
     * @param array $response
     * @return AiConversation
     */
    protected function createConversationRecord(
        AiBotProfile $botProfile,
        array $params,
        array $messages,
        array $response
    ): AiConversation {
        // สร้าง conversation
        $conversation = AiConversation::create([
            'user_id' => $params['user_id'],
            'bot_profile_id' => $botProfile->id,
            'rental_id' => $params['rental_id'] ?? null,
            'title' => substr($params['prompt'], 0, 100),
            'status' => 'active',
        ]);

        // สร้าง message records
        foreach ($messages as $message) {
            AiMessage::create([
                'conversation_id' => $conversation->id,
                'role' => $message['role'],
                'content' => $message['content'],
            ]);
        }

        // เพิ่ม AI response
        AiMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $response['content'] ?? '',
        ]);

        return $conversation;
    }

    /**
     * คำนวณ cost จาก tokens
     *
     * @param \App\Models\AiModel $model
     * @param int $promptTokens
     * @param int $completionTokens
     * @return float
     */
    protected function calculateCost($model, int $promptTokens, int $completionTokens): float
    {
        $pricing = $model->pricing ?? [];

        $inputCost = ($promptTokens / 1000) * ($pricing['input'] ?? 0);
        $outputCost = ($completionTokens / 1000) * ($pricing['output'] ?? 0);

        return round($inputCost + $outputCost, 6);
    }

    /**
     * บันทึก usage log
     *
     * @param AiConversation $conversation
     * @param array $response
     * @param float $responseTime
     * @param float $cost
     * @return void
     */
    protected function logUsageRecord(
        AiConversation $conversation,
        array $response,
        float $responseTime,
        float $cost
    ): void {
        try {
            $usage = $response['usage'] ?? [];

            \App\Models\AiUsageLog::create([
                'user_id' => $conversation->user_id,
                'bot_profile_id' => $conversation->bot_profile_id,
                'conversation_id' => $conversation->id,
                'rental_id' => $conversation->rental_id,
                'provider_id' => $conversation->botProfile->provider_id ?? null,
                'model_id' => $conversation->botProfile->model_id ?? null,
                'request_type' => 'chat_completion',
                'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                'completion_tokens' => $usage['completion_tokens'] ?? 0,
                'total_tokens' => $usage['total_tokens'] ?? 0,
                'cost' => $cost,
                'response_time_ms' => $responseTime,
                'status' => 'success',
                'metadata' => [
                    'finish_reason' => $response['finish_reason'] ?? 'stop',
                ],
            ]);
        } catch (\Exception $e) {
            // Log error แต่ไม่ throw exception เพราะไม่ต้องการให้ usage logging failure
            // ทำให้ main request fail
            Log::warning('Failed to log AI usage', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversation->id,
            ]);
        }
    }

    /**
     * ทดสอบ AI service ด้วย bot profile
     *
     * @param int $botProfileId
     * @return array
     */
    public function test(int $botProfileId): array
    {
        try {
            $result = $this->generateText([
                'bot_profile_id' => $botProfileId,
                'prompt' => 'Hello, this is a test. Please respond with "Test successful".',
                'max_tokens' => 50,
            ]);

            return [
                'success' => true,
                'message' => 'AI service test successful',
                'response' => $result['text'],
                'tokens_used' => $result['tokens_used'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'AI service test failed',
                'error' => $e->getMessage(),
            ];
        }
    }
}
