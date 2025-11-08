<?php

namespace App\Services\BotAutomation;

use App\Models\BotAutomation\BotSalesConversation;
use App\Models\BotAutomation\BotSalesMessage;
use App\Models\BotAutomation\BotAutomation;
use App\Models\Product;
use App\Services\AI\AiService;
use Illuminate\Support\Facades\Log;

class BotSalesService
{
    public function __construct(
        protected AiService $aiService
    ) {}

    /**
     * Create new sales conversation
     */
    public function createConversation(array $data): BotSalesConversation
    {
        return BotSalesConversation::create(array_merge($data, [
            'conversation_id' => BotSalesConversation::generateConversationId(),
            'stage' => 'awareness',
            'status' => 'active',
            'is_ai_handled' => true,
        ]));
    }

    /**
     * Handle incoming sales message
     */
    public function handleMessage(BotSalesConversation $conversation, string $message, int $senderId): array
    {
        // Save customer message
        $customerMessage = BotSalesMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $senderId,
            'sender_type' => 'customer',
            'message' => $message,
        ]);

        // Analyze intent and sentiment
        $analysis = $this->analyzeMessage($message);
        $customerMessage->update([
            'intent_detected' => $analysis['intent'],
            'entities_extracted' => $analysis['entities'],
            'sentiment_score' => $analysis['sentiment'],
        ]);

        // Update conversation stage based on intent
        $this->updateStageByIntent($conversation, $analysis['intent']);

        // Generate AI response if bot-handled
        if ($conversation->is_ai_handled && !$conversation->requires_human) {
            try {
                $aiResponse = $this->generateSalesResponse($conversation, $message, $analysis);

                // Save bot response
                $botMessage = BotSalesMessage::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $conversation->automation->user_id,
                    'sender_type' => 'bot',
                    'message' => $aiResponse['message'],
                    'message_type' => $aiResponse['type'] ?? 'text',
                    'product_data' => $aiResponse['products'] ?? null,
                    'is_ai_generated' => true,
                    'tokens_used' => $aiResponse['tokens_used'] ?? 0,
                ]);

                return [
                    'success' => true,
                    'message' => $aiResponse['message'],
                    'products' => $aiResponse['products'] ?? [],
                    'next_action' => $aiResponse['next_action'] ?? null,
                    'confidence' => $aiResponse['confidence'] ?? 80,
                ];
            } catch (\Exception $e) {
                Log::error("AI sales response failed", [
                    'conversation_id' => $conversation->id,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => true,
            'message' => 'Message received, sales rep will respond shortly',
        ];
    }

    /**
     * Analyze customer message
     */
    protected function analyzeMessage(string $message): array
    {
        // Simple intent detection (in production, use ML model)
        $intent = 'general_inquiry';
        $sentiment = 0;
        $entities = [];

        // Detect intent
        if (preg_match('/(?:ต้องการ|อยาก|สนใจ|ซื้อ)/iu', $message)) {
            $intent = 'purchase_intent';
            $sentiment = 50;
        } elseif (preg_match('/(?:ราคา|เท่าไหร่|ค่าใช้จ่าย)/iu', $message)) {
            $intent = 'price_inquiry';
        } elseif (preg_match('/(?:แนะนำ|เลือก|ดีไหม|เหมาะสม)/iu', $message)) {
            $intent = 'recommendation_request';
            $sentiment = 30;
        } elseif (preg_match('/(?:ปัญหา|ไม่ได้|error|bug)/iu', $message)) {
            $intent = 'problem_report';
            $sentiment = -30;
        }

        // Extract product mentions (simple pattern matching)
        // In production, use NER (Named Entity Recognition)

        return [
            'intent' => $intent,
            'sentiment' => $sentiment,
            'entities' => $entities,
        ];
    }

    /**
     * Update conversation stage based on intent
     */
    protected function updateStageByIntent(BotSalesConversation $conversation, string $intent): void
    {
        $stageMap = [
            'general_inquiry' => 'awareness',
            'price_inquiry' => 'interest',
            'recommendation_request' => 'consideration',
            'purchase_intent' => 'intent',
        ];

        $newStage = $stageMap[$intent] ?? $conversation->stage;

        // Only advance stage, don't go backwards
        $stages = ['awareness', 'interest', 'consideration', 'intent', 'purchase'];
        $currentIndex = array_search($conversation->stage, $stages);
        $newIndex = array_search($newStage, $stages);

        if ($newIndex > $currentIndex) {
            $conversation->update(['stage' => $newStage]);
        }
    }

    /**
     * Generate AI sales response
     */
    protected function generateSalesResponse(BotSalesConversation $conversation, string $message, array $analysis): array
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

        // Build sales prompt
        $systemPrompt = $this->buildSalesPrompt($conversation, $analysis);

        // Get product recommendations if needed
        $recommendedProducts = [];
        if (in_array($analysis['intent'], ['recommendation_request', 'purchase_intent'])) {
            $recommendedProducts = $this->getProductRecommendations($conversation, $analysis);
        }

        $response = $this->aiService->generateText([
            'bot_profile_id' => $botProfile->id,
            'prompt' => $message,
            'system_prompt' => $systemPrompt,
            'conversation_history' => $history,
            'max_tokens' => 500,
            'temperature' => 0.8,
        ]);

        // Determine next action
        $nextAction = $this->determineNextAction($conversation, $analysis);

        return [
            'message' => $response['text'],
            'type' => !empty($recommendedProducts) ? 'product_recommendation' : 'text',
            'products' => $recommendedProducts,
            'tokens_used' => $response['tokens_used'] ?? 0,
            'next_action' => $nextAction,
            'confidence' => $this->calculateSalesConfidence($conversation, $analysis),
        ];
    }

    /**
     * Build sales system prompt
     */
    protected function buildSalesPrompt(BotSalesConversation $conversation, array $analysis): string
    {
        $stage = $conversation->stage;
        $intent = $analysis['intent'];

        $prompt = "คุณเป็น AI Sales Assistant มืออาชีพ มีความรู้ลึกซึ้งเกี่ยวกับผลิตภัณฑ์และบริการ\n\n";
        $prompt .= "ขั้นตอนปัจจุบัน: {$stage}\n";
        $prompt .= "ความตั้งใจของลูกค้า: {$intent}\n\n";

        $prompt .= match($stage) {
            'awareness' => "สร้างความสนใจและบอกข้อดีของผลิตภัณฑ์",
            'interest' => "ตอบคำถามและให้ข้อมูลเพิ่มเติม",
            'consideration' => "เปรียบเทียบและแนะนำผลิตภัณฑ์ที่เหมาะสม",
            'intent' => "ช่วยตัดสินใจและปิดการขาย",
            'purchase' => "ช่วยเหลือในกระบวนการสั่งซื้อ",
            default => "ให้ข้อมูลและคำแนะนำ",
        };

        $prompt .= "\n\nตอบอย่างเป็นมิตร กระตุ้นความสนใจ และพยายามปิดการขาย แต่ไม่กดดัน";

        return $prompt;
    }

    /**
     * Get product recommendations
     */
    protected function getProductRecommendations(BotSalesConversation $conversation, array $analysis): array
    {
        // Get products based on customer interests
        $products = Product::active()
            ->inRandomOrder()
            ->limit(3)
            ->get()
            ->map(fn($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'image' => $product->image_url ?? null,
                'url' => route('products.show', $product->slug ?? $product->id),
            ])
            ->toArray();

        return $products;
    }

    /**
     * Determine next action
     */
    protected function determineNextAction(BotSalesConversation $conversation, array $analysis): ?string
    {
        return match($analysis['intent']) {
            'purchase_intent' => 'show_checkout',
            'recommendation_request' => 'show_products',
            'price_inquiry' => 'show_pricing',
            default => null,
        };
    }

    /**
     * Calculate sales confidence score
     */
    protected function calculateSalesConfidence(BotSalesConversation $conversation, array $analysis): int
    {
        $confidence = 70;

        // Higher confidence for advanced stages
        $stageBonus = [
            'awareness' => 0,
            'interest' => 10,
            'consideration' => 20,
            'intent' => 30,
            'purchase' => 40,
        ];

        $confidence += $stageBonus[$conversation->stage] ?? 0;

        // Adjust based on sentiment
        if ($analysis['sentiment'] > 50) {
            $confidence += 15;
        } elseif ($analysis['sentiment'] < -30) {
            $confidence -= 20;
        }

        return max(0, min(100, $confidence));
    }

    /**
     * Mark conversation as converted
     */
    public function markAsConverted(BotSalesConversation $conversation, int $orderId, float $orderValue): void
    {
        $conversation->markAsConverted($orderId, $orderValue);

        Log::info("Sales conversation converted", [
            'conversation_id' => $conversation->id,
            'order_id' => $orderId,
            'order_value' => $orderValue,
        ]);
    }

    /**
     * Get sales statistics
     */
    public function getStatistics(int $automationId = null): array
    {
        $query = BotSalesConversation::query();

        if ($automationId) {
            $query->where('automation_id', $automationId);
        }

        $total = $query->count();
        $converted = $query->where('status', 'converted')->count();
        $active = $query->where('status', 'active')->count();
        $totalRevenue = $query->where('status', 'converted')->sum('actual_order_value');
        $avgOrderValue = $query->where('status', 'converted')->avg('actual_order_value');

        // Conversion rate by stage
        $stageStats = [];
        foreach (['awareness', 'interest', 'consideration', 'intent', 'purchase'] as $stage) {
            $stageStats[$stage] = $query->where('stage', $stage)->count();
        }

        return [
            'total_conversations' => $total,
            'converted' => $converted,
            'active' => $active,
            'conversion_rate' => $total > 0 ? ($converted / $total) * 100 : 0,
            'total_revenue' => $totalRevenue,
            'avg_order_value' => round($avgOrderValue ?? 0, 2),
            'stage_distribution' => $stageStats,
        ];
    }
}
