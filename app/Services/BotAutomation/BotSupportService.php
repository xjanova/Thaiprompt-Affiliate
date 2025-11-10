<?php

namespace App\Services\BotAutomation;

use App\Models\BotAutomation\BotSupportConversation;
use App\Models\BotAutomation\BotSupportMessage;
use App\Models\BotAutomation\BotAutomation;
use App\Services\AI\AiService;
use Illuminate\Support\Facades\Log;

class BotSupportService
{
    public function __construct(
        protected AiService $aiService
    ) {}

    /**
     * Create new support conversation
     */
    public function createConversation(array $data): BotSupportConversation
    {
        return BotSupportConversation::create(array_merge($data, [
            'ticket_number' => BotSupportConversation::generateTicketNumber(),
            'status' => 'open',
            'is_ai_handled' => true,
        ]));
    }

    /**
     * Handle incoming support message
     */
    public function handleMessage(BotSupportConversation $conversation, string $message, int $senderId): array
    {
        // Save customer message
        $customerMessage = BotSupportMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $senderId,
            'sender_type' => 'customer',
            'message' => $message,
        ]);

        // Generate AI response if bot-handled
        if ($conversation->is_ai_handled && !$conversation->requires_human) {
            try {
                $aiResponse = $this->generateAiResponse($conversation, $message);

                // Check confidence score
                if ($aiResponse['confidence'] < 70) {
                    $conversation->flagForHuman();
                    return [
                        'requires_human' => true,
                        'message' => 'เราจะมีเจ้าหน้าที่ติดต่อกลับในเร็วๆ นี้',
                    ];
                }

                // Save bot response
                $botMessage = BotSupportMessage::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $conversation->automation->user_id,
                    'sender_type' => 'bot',
                    'message' => $aiResponse['message'],
                    'is_ai_generated' => true,
                    'ai_prompt_used' => $aiResponse['prompt'] ?? null,
                    'tokens_used' => $aiResponse['tokens_used'] ?? 0,
                ]);

                return [
                    'success' => true,
                    'message' => $aiResponse['message'],
                    'confidence' => $aiResponse['confidence'],
                ];
            } catch (\Exception $e) {
                Log::error("AI support response failed", [
                    'conversation_id' => $conversation->id,
                    'error' => $e->getMessage(),
                ]);

                $conversation->flagForHuman();

                return [
                    'success' => false,
                    'requires_human' => true,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => true,
            'message' => 'Message received, agent will respond shortly',
        ];
    }

    /**
     * Generate AI response for support
     */
    protected function generateAiResponse(BotSupportConversation $conversation, string $message): array
    {
        $automation = $conversation->automation;
        $botProfile = $automation->aiBotProfile;

        if (!$botProfile) {
            throw new \Exception("AI bot profile not configured");
        }

        // Get conversation history
        $history = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get()
            ->map(fn($msg) => [
                'role' => $msg->sender_type === 'customer' ? 'user' : 'assistant',
                'content' => $msg->message,
            ])
            ->toArray();

        // Build support prompt
        $systemPrompt = $this->buildSupportPrompt($conversation);

        $response = $this->aiService->generateText([
            'bot_profile_id' => $botProfile->id,
            'prompt' => $message,
            'system_prompt' => $systemPrompt,
            'conversation_history' => $history,
            'max_tokens' => 500,
            'temperature' => 0.7,
        ]);

        // Calculate confidence score based on response
        $confidence = $this->calculateConfidence($message, $response['text']);

        return [
            'message' => $response['text'],
            'confidence' => $confidence,
            'tokens_used' => $response['tokens_used'] ?? 0,
            'prompt' => $systemPrompt,
        ];
    }

    /**
     * Build support system prompt
     */
    protected function buildSupportPrompt(BotSupportConversation $conversation): string
    {
        $category = $conversation->category ?? 'general';

        $basePrompt = "คุณเป็น AI Support Assistant ที่เป็นมิตรและช่วยเหลือดี มีหน้าที่ตอบคำถามและแก้ไขปัญหาของลูกค้า\n\n";
        $basePrompt .= "หมวดหมู่: {$category}\n";
        $basePrompt .= "ระดับความสำคัญ: {$conversation->priority}\n\n";
        $basePrompt .= "กรุณาตอบคำถามอย่างชัดเจน กระชับ และเป็นมิตร หากไม่แน่ใจในคำตอบ ให้แจ้งว่าจะให้เจ้าหน้าที่ติดต่อกลับ";

        return $basePrompt;
    }

    /**
     * Calculate confidence score
     */
    protected function calculateConfidence(string $question, string $answer): int
    {
        // Simple confidence calculation
        // In production, you might want more sophisticated scoring
        $confidence = 80;

        // Lower confidence for short answers
        if (mb_strlen($answer) < 20) {
            $confidence -= 20;
        }

        // Lower confidence if response contains uncertainty phrases
        $uncertainPhrases = ['ไม่แน่ใจ', 'อาจจะ', 'บางที', 'ไม่ทราบ'];
        foreach ($uncertainPhrases as $phrase) {
            if (mb_stripos($answer, $phrase) !== false) {
                $confidence -= 30;
                break;
            }
        }

        return max(0, min(100, $confidence));
    }

    /**
     * Assign conversation to human agent
     */
    public function assignToAgent(BotSupportConversation $conversation, int $agentId): void
    {
        $conversation->assignToAgent($agentId);

        Log::info("Support conversation assigned to agent", [
            'conversation_id' => $conversation->id,
            'agent_id' => $agentId,
        ]);
    }

    /**
     * Resolve conversation
     */
    public function resolveConversation(BotSupportConversation $conversation): void
    {
        $conversation->resolve();

        Log::info("Support conversation resolved", [
            'conversation_id' => $conversation->id,
            'resolution_time' => $conversation->resolution_time_minutes,
        ]);
    }

    /**
     * Get support statistics
     */
    public function getStatistics(int $automationId = null): array
    {
        $query = BotSupportConversation::query();

        if ($automationId) {
            $query->where('automation_id', $automationId);
        }

        $total = $query->count();
        $open = $query->where('status', 'open')->count();
        $resolved = $query->where('status', 'resolved')->count();
        $aiHandled = $query->where('is_ai_handled', true)->count();
        $requiresHuman = $query->where('requires_human', true)->count();
        $avgResolutionTime = $query->whereNotNull('resolution_time_minutes')->avg('resolution_time_minutes');

        return [
            'total_conversations' => $total,
            'open' => $open,
            'resolved' => $resolved,
            'ai_handled' => $aiHandled,
            'requires_human' => $requiresHuman,
            'ai_success_rate' => $total > 0 ? ($aiHandled / $total) * 100 : 0,
            'avg_resolution_time_minutes' => round($avgResolutionTime ?? 0, 2),
        ];
    }

    /**
     * Get conversation analytics
     */
    public function getConversationAnalytics(BotSupportConversation $conversation): array
    {
        return [
            'ticket_number' => $conversation->ticket_number,
            'status' => $conversation->status,
            'priority' => $conversation->priority,
            'message_count' => $conversation->message_count,
            'is_ai_handled' => $conversation->is_ai_handled,
            'requires_human' => $conversation->requires_human,
            'first_response_time' => $conversation->first_response_at?->diffInMinutes($conversation->created_at),
            'resolution_time' => $conversation->resolution_time_minutes,
            'satisfaction_rating' => $conversation->customer_satisfaction_rating,
        ];
    }
}
