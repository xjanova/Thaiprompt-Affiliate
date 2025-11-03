<?php

namespace App\Services\AI;

use App\Models\AiBotProfile;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConversationManager
{
    private BaseAiService $aiService;
    private AiBotProfile $bot;

    public function __construct(AiBotProfile $bot)
    {
        $this->bot = $bot;
        $this->aiService = AiServiceFactory::createFromBot($bot);
    }

    /**
     * เริ่มบทสนทนาใหม่
     */
    public function startConversation(?User $user = null, ?string $lineUserId = null): AiConversation
    {
        return AiConversation::create([
            'bot_profile_id' => $this->bot->id,
            'user_id' => $user?->id,
            'line_user_id' => $lineUserId,
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    /**
     * ส่งข้อความและรับคำตอบ
     */
    public function sendMessage(
        AiConversation $conversation,
        string $userMessage,
        array $options = []
    ): array {
        // บันทึกข้อความจากผู้ใช้
        $conversation->addMessage('user', $userMessage);

        // ดึง context (ข้อความล่าสุด)
        $messages = $this->buildContext($conversation, $options);

        // ส่ง request ไปยัง AI
        $startTime = microtime(true);
        $response = $this->aiService->chat($messages, $this->getAiOptions($options));
        $responseTime = (microtime(true) - $startTime) * 1000;

        if (!$response['success']) {
            // บันทึก error
            $this->aiService->logUsage(
                $conversation,
                ['messages' => $messages],
                $response,
                $responseTime,
                'error',
                $response['error'] ?? 'Unknown error'
            );

            return [
                'success' => false,
                'error' => $response['error'] ?? 'AI request failed',
            ];
        }

        // บันทึกคำตอบจาก AI
        $conversation->addMessage('assistant', $response['content'], [
            'tokens_used' => $response['usage']['total_tokens'] ?? 0,
            'model_used' => $this->bot->model->model_identifier,
            'provider_used' => $this->bot->provider->display_name,
            'response_time_ms' => $responseTime,
        ]);

        // บันทึก usage log
        $this->aiService->logUsage(
            $conversation,
            ['messages' => $messages],
            $response,
            $responseTime,
            'success'
        );

        return [
            'success' => true,
            'message' => $response['content'],
            'tokens_used' => $response['usage']['total_tokens'] ?? 0,
            'finish_reason' => $response['finish_reason'] ?? 'stop',
        ];
    }

    /**
     * สร้าง context สำหรับส่งไปยัง AI
     */
    private function buildContext(AiConversation $conversation, array $options): array
    {
        $messages = [];

        // เพิ่ม system prompt
        if ($this->bot->system_prompt) {
            $messages[] = [
                'role' => 'system',
                'content' => $this->bot->system_prompt,
            ];
        }

        // ดึงข้อความล่าสุด (จำกัดจำนวนตาม context_size)
        $contextSize = $options['context_size'] ?? $this->getOptimalContextSize();

        $recentMessages = $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->limit($contextSize)
            ->get()
            ->reverse()
            ->values();

        foreach ($recentMessages as $message) {
            if (in_array($message->role, ['user', 'assistant'])) {
                $messages[] = [
                    'role' => $message->role,
                    'content' => $message->content,
                ];
            }
        }

        // ถ้ามี RAG context ให้เพิ่มเข้าไป
        if ($this->bot->enable_knowledge_base && !empty($options['rag_context'])) {
            $messages[] = [
                'role' => 'system',
                'content' => "Context from knowledge base:\n" . $options['rag_context'],
            ];
        }

        return $messages;
    }

    /**
     * รับ AI options จาก bot profile
     */
    private function getAiOptions(array $overrides = []): array
    {
        return array_merge([
            'temperature' => $this->bot->temperature ?? 0.7,
            'max_tokens' => $this->bot->max_tokens ?? 2000,
            'top_p' => $this->bot->top_p ?? 1,
            'frequency_penalty' => $this->bot->frequency_penalty ?? 0,
            'presence_penalty' => $this->bot->presence_penalty ?? 0,
        ], $overrides);
    }

    /**
     * คำนวณ context size ที่เหมาะสม
     */
    private function getOptimalContextSize(): int
    {
        $contextWindow = $this->bot->model->context_window ?? 4096;

        // ใช้ 70% ของ context window สำหรับ history
        // ที่เหลือเผื่อไว้สำหรับ response
        return (int) floor(($contextWindow * 0.7) / 200); // สมมติแต่ละข้อความใช้ ~200 tokens
    }

    /**
     * สรุปบทสนทนา (สำหรับ title)
     */
    public function summarizeConversation(AiConversation $conversation): ?string
    {
        $firstMessages = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->limit(2)
            ->get();

        if ($firstMessages->isEmpty()) {
            return null;
        }

        $summary = $firstMessages->pluck('content')->implode(' ');

        return mb_substr($summary, 0, 50) . (mb_strlen($summary) > 50 ? '...' : '');
    }

    /**
     * ล้าง context เก่า (prune old messages)
     */
    public function pruneOldMessages(AiConversation $conversation, int $keepLast = 50): int
    {
        $allMessages = $conversation->messages()->orderBy('created_at', 'desc')->get();

        if ($allMessages->count() <= $keepLast) {
            return 0;
        }

        $messagesToDelete = $allMessages->skip($keepLast)->pluck('id');

        return AiMessage::whereIn('id', $messagesToDelete)->delete();
    }

    /**
     * Archive conversation
     */
    public function archiveConversation(AiConversation $conversation): void
    {
        $conversation->archive();
    }

    /**
     * ดึงสถิติของบทสนทนา
     */
    public function getConversationStats(AiConversation $conversation): array
    {
        return [
            'total_messages' => $conversation->total_messages,
            'total_tokens' => $conversation->total_tokens,
            'total_cost' => $conversation->total_cost,
            'duration_minutes' => $conversation->started_at
                ? $conversation->started_at->diffInMinutes($conversation->last_message_at ?? now())
                : 0,
            'avg_response_time' => $conversation->getAverageResponseTime(),
            'user_messages' => $conversation->userMessages()->count(),
            'assistant_messages' => $conversation->assistantMessages()->count(),
        ];
    }

    /**
     * ค้นหาบทสนทนาที่มี user_id หรือ line_user_id
     */
    public function findActiveConversation(?int $userId, ?string $lineUserId): ?AiConversation
    {
        $query = AiConversation::where('bot_profile_id', $this->bot->id)
            ->where('status', 'active');

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($lineUserId) {
            $query->where('line_user_id', $lineUserId);
        } else {
            return null;
        }

        return $query->orderBy('last_message_at', 'desc')->first();
    }

    /**
     * สร้างหรือหาบทสนทนาที่มีอยู่
     */
    public function findOrCreateConversation(?User $user = null, ?string $lineUserId = null): AiConversation
    {
        $existing = $this->findActiveConversation($user?->id, $lineUserId);

        if ($existing) {
            return $existing;
        }

        return $this->startConversation($user, $lineUserId);
    }
}
