<?php

namespace App\Services;

use App\Models\MlmProspect;
use App\Models\LineSignupFlow;
use Illuminate\Support\Facades\Log;

/**
 * Smart Response Service
 *
 * Generates intelligent, context-aware responses for LINE conversations:
 * - Dynamic response templates
 * - Personalized messages
 * - Emoji and tone adjustment
 * - Multi-language support (Thai/English)
 * - Response variations to avoid repetition
 */
class SmartResponseService
{
    public function __construct(
        private AiConversationService $aiService,
        private ConversationContextService $contextService
    ) {}

    /**
     * Generate smart response based on user message and context
     *
     * @param MlmProspect $prospect
     * @param string $userMessage
     * @param string $currentStep
     * @return array ['message' => string, 'quick_replies' => array, 'metadata' => array]
     */
    public function generateResponse(MlmProspect $prospect, string $userMessage, string $currentStep): array
    {
        // Get context
        $context = $this->contextService->getContext($prospect);

        // Detect intent
        $intent = $this->aiService->detectIntent($userMessage, $currentStep);

        // Check sentiment
        $sentiment = $this->aiService->analyzeSentiment($userMessage);

        // Generate appropriate response based on intent
        $response = match($intent['intent']) {
            'ask_help' => $this->generateHelpResponse($prospect, $currentStep),
            'confused' => $this->generateClarificationResponse($prospect, $currentStep),
            'restart' => $this->generateRestartResponse($prospect),
            'cancel' => $this->generateCancelResponse($prospect),
            default => $this->generateStandardResponse($prospect, $currentStep, $sentiment),
        };

        // Add personality and emojis based on sentiment
        $response['message'] = $this->enhanceWithPersonality($response['message'], $sentiment);

        // Track behavior if user is confused
        if ($intent['intent'] === 'confused') {
            $this->contextService->markBehavior($prospect, 'confusion');
        }

        return $response;
    }

    /**
     * Generate help response
     *
     * @param MlmProspect $prospect
     * @param string $currentStep
     * @return array
     */
    private function generateHelpResponse(MlmProspect $prospect, string $currentStep): array
    {
        $step = LineSignupFlow::getByStepKey($currentStep);

        if (!$step) {
            return [
                'message' => "ฉันช่วยอะไรได้บ้างคะ? 😊\n\nพิมพ์ 'เริ่มใหม่' เพื่อเริ่มต้นใหม่",
                'quick_replies' => ['เริ่มใหม่', 'ช่วยเหลือ'],
                'metadata' => ['type' => 'help'],
            ];
        }

        $helpText = $this->getHelpTextForStep($step);

        return [
            'message' => "💡 ช่วยเหลือ: {$step->step_key}\n\n{$helpText}",
            'quick_replies' => $this->getQuickRepliesForStep($step),
            'metadata' => ['type' => 'help', 'step' => $currentStep],
        ];
    }

    /**
     * Generate clarification response when user is confused
     *
     * @param MlmProspect $prospect
     * @param string $currentStep
     * @return array
     */
    private function generateClarificationResponse(MlmProspect $prospect, string $currentStep): array
    {
        $step = LineSignupFlow::getByStepKey($currentStep);

        if (!$step) {
            return $this->generateHelpResponse($prospect, $currentStep);
        }

        // Check if user has been confused before
        $hasBeenConfused = $this->contextService->hasShownBehavior($prospect, 'confusion');

        if ($hasBeenConfused) {
            // User is confused multiple times - offer extra help
            $message = "เข้าใจค่ะว่าคุณอาจสับสน 😊\n\n";
            $message .= "ขอให้ฉันอธิบายอีกครั้งง่ายๆ นะคะ:\n\n";
            $message .= $this->getSimplifiedExplanation($step);
            $message .= "\n\nถ้ายังไม่แน่ใจ กดปุ่มด้านล่างได้เลยค่ะ";

            return [
                'message' => $message,
                'quick_replies' => ['ตัวอย่าง', 'เริ่มใหม่', 'ติดต่อทีมงาน'],
                'metadata' => ['type' => 'clarification', 'repeated' => true],
            ];
        }

        // First time confusion
        $message = "ขออธิบายเพิ่มเติมค่ะ 😊\n\n";
        $message .= $step->message_text . "\n\n";
        $message .= "📝 ตัวอย่าง: " . $this->getExampleForStep($step);

        return [
            'message' => $message,
            'quick_replies' => $this->getQuickRepliesForStep($step),
            'metadata' => ['type' => 'clarification'],
        ];
    }

    /**
     * Generate restart confirmation response
     *
     * @param MlmProspect $prospect
     * @return array
     */
    private function generateRestartResponse(MlmProspect $prospect): array
    {
        $context = $this->contextService->getContext($prospect);
        $progress = $context['metadata']['progress'] ?? 0;

        $message = "คุณต้องการเริ่มใหม่ใช่ไหมคะ? 🔄\n\n";

        if ($progress > 0) {
            $message .= "⚠️ ข้อมูลที่กรอกไปแล้ว {$progress}% จะถูกล้าง\n\n";
        }

        $message .= "ยืนยันการเริ่มใหม่หรือไม่?";

        return [
            'message' => $message,
            'quick_replies' => ['ใช่ เริ่มใหม่', 'ไม่ใช่ ทำต่อ'],
            'metadata' => ['type' => 'restart_confirmation', 'progress' => $progress],
        ];
    }

    /**
     * Generate cancel confirmation response
     *
     * @param MlmProspect $prospect
     * @return array
     */
    private function generateCancelResponse(MlmProspect $prospect): array
    {
        $message = "เสียใจด้วยค่ะที่คุณต้องการยกเลิก 😢\n\n";
        $message .= "หากคุณเปลี่ยนใจ สามารถกลับมาสมัครได้ทุกเมื่อค่ะ\n\n";
        $message .= "ต้องการยกเลิกจริงๆ หรือไม่?";

        return [
            'message' => $message,
            'quick_replies' => ['ยืนยันยกเลิก', 'ไม่ยกเลิก ทำต่อ'],
            'metadata' => ['type' => 'cancel_confirmation'],
        ];
    }

    /**
     * Generate standard response for current step
     *
     * @param MlmProspect $prospect
     * @param string $currentStep
     * @param array $sentiment
     * @return array
     */
    private function generateStandardResponse(MlmProspect $prospect, string $currentStep, array $sentiment): array
    {
        $step = LineSignupFlow::getByStepKey($currentStep);

        if (!$step) {
            return [
                'message' => 'ขออภัยค่ะ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง',
                'quick_replies' => [],
                'metadata' => ['type' => 'error'],
            ];
        }

        // Get message from flow
        $message = $step->message_text;

        // Personalize message
        $message = $this->personalizeMessage($message, $prospect);

        // Add progress indicator
        $progress = $prospect->conversation_progress_percent ?? 0;
        if ($progress > 0) {
            $message .= "\n\n📊 ความคืบหน้า: {$progress}%";
        }

        return [
            'message' => $message,
            'quick_replies' => $this->getQuickRepliesForStep($step),
            'metadata' => ['type' => 'standard', 'step' => $currentStep],
        ];
    }

    /**
     * Enhance message with personality based on sentiment
     *
     * @param string $message
     * @param array $sentiment
     * @return string
     */
    private function enhanceWithPersonality(string $message, array $sentiment): string
    {
        // Add appropriate emoji based on sentiment
        $emoji = match($sentiment['sentiment']) {
            'positive' => '😊',
            'negative' => '🤔',
            default => '✨',
        };

        // Add friendly tone if sentiment is negative
        if ($sentiment['sentiment'] === 'negative') {
            $message = "เข้าใจค่ะ " . $emoji . "\n\n" . $message;
        }

        return $message;
    }

    /**
     * Personalize message with user data
     *
     * @param string $message
     * @param MlmProspect $prospect
     * @return string
     */
    private function personalizeMessage(string $message, MlmProspect $prospect): string
    {
        $replacements = [
            '{name}' => $prospect->line_display_name ?? 'คุณ',
            '{sponsor_name}' => $prospect->sponsorUser->name ?? 'ทีม',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }

    /**
     * Get help text for specific step
     *
     * @param LineSignupFlow $step
     * @return string
     */
    private function getHelpTextForStep(LineSignupFlow $step): string
    {
        $helpTexts = [
            'name' => "กรุณากรอกชื่อ-นามสกุลของคุณ\n\nตัวอย่าง:\n• สมชาย ใจดี\n• Somchai Jaidee",
            'phone' => "กรุณากรอกเบอร์โทรศัพท์มือถือ 10 หลัก\n\nรูปแบบที่รองรับ:\n• 0812345678\n• +66812345678\n• 66812345678",
            'email' => "กรุณากรอกอีเมลที่ใช้งานได้\n\nตัวอย่าง:\n• yourname@gmail.com\n• yourname@hotmail.com",
            'address' => "กรุณากรอกที่อยู่ที่สามารถติดต่อได้\n\nตัวอย่าง:\n• 123/45 ถ.สุขุมวิท แขวงคลองเตย เขตคลองเตย กรุงเทพฯ 10110",
        ];

        return $helpTexts[$step->step_key] ?? $step->message_text;
    }

    /**
     * Get simplified explanation for confused users
     *
     * @param LineSignupFlow $step
     * @return string
     */
    private function getSimplifiedExplanation(LineSignupFlow $step): string
    {
        $simple = [
            'name' => "แค่พิมพ์ชื่อของคุณมาได้เลยค่ะ เช่น: สมชาย ใจดี",
            'phone' => "พิมพ์เบอร์มือถือ 10 หลัก เช่น: 0812345678",
            'email' => "พิมพ์อีเมลของคุณ เช่น: somchai@gmail.com",
            'address' => "พิมพ์ที่อยู่บ้านของคุณมาค่ะ",
        ];

        return $simple[$step->step_key] ?? "กรุณากรอกข้อมูลตามที่ขอ";
    }

    /**
     * Get example for step
     *
     * @param LineSignupFlow $step
     * @return string
     */
    private function getExampleForStep(LineSignupFlow $step): string
    {
        $examples = [
            'name' => 'สมชาย ใจดี',
            'phone' => '0812345678',
            'email' => 'somchai@gmail.com',
            'address' => '123 ถ.สุขุมวิท แขวงคลองเตย กรุงเทพฯ 10110',
        ];

        return $examples[$step->step_key] ?? 'ไม่มีตัวอย่าง';
    }

    /**
     * Get quick replies for step
     *
     * @param LineSignupFlow $step
     * @return array
     */
    private function getQuickRepliesForStep(LineSignupFlow $step): array
    {
        // Check if step has custom quick replies
        if ($step->quick_reply_options) {
            return $step->quick_reply_options;
        }

        // Default quick replies
        return ['ตัวอย่าง', 'ข้าม', 'ช่วยเหลือ'];
    }

    /**
     * Generate encouragement message
     *
     * @param MlmProspect $prospect
     * @return string
     */
    public function generateEncouragement(MlmProspect $prospect): string
    {
        $progress = $prospect->conversation_progress_percent ?? 0;

        $messages = [
            0 => "เริ่มกันเลยค่ะ! 💪",
            25 => "ดีมากค่ะ! ทำไปแล้ว 25% 🎉",
            50 => "เยี่ยมเลย! ครึ่งทางแล้ว 🌟",
            75 => "เกือบถึงเป้าแล้ว! เหลืออีกนิดเดียว 🚀",
            90 => "ใกล้เสร็จแล้ว! สู้ๆ ค่ะ 💯",
        ];

        // Find closest milestone
        $closestMilestone = 0;
        foreach (array_keys($messages) as $milestone) {
            if ($progress >= $milestone) {
                $closestMilestone = $milestone;
            }
        }

        return $messages[$closestMilestone] ?? "ทำได้ดีมากค่ะ! 👏";
    }

    /**
     * Generate completion celebration message
     *
     * @param MlmProspect $prospect
     * @return string
     */
    public function generateCompletionMessage(MlmProspect $prospect): string
    {
        $context = $this->contextService->getContext($prospect);
        $stats = $this->contextService->getStatistics($prospect);

        $message = "🎉🎊 ยินดีด้วยค่ะ! {$prospect->line_display_name} 🎊🎉\n\n";
        $message .= "คุณสมัครสมาชิกเรียบร้อยแล้ว! ✅\n\n";
        $message .= "📊 สรุปการสมัคร:\n";
        $message .= "⏱️ ใช้เวลา: {$stats['conversation_duration']} นาที\n";
        $message .= "💬 ข้อความทั้งหมด: {$stats['total_messages']} ข้อความ\n\n";
        $message .= "ขอบคุณที่เลือกเข้าร่วมกับเราค่ะ! 🙏\n";
        $message .= "เรายินดีต้อนรับคุณสู่ครอบครัว! 🏠❤️";

        return $message;
    }
}
