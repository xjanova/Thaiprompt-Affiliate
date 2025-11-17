<?php

namespace App\Services;

use App\Models\LineBotKeyword;
use App\Models\AiBotProfile;
use Illuminate\Support\Facades\Log;

class LineHybridBotService
{
    /**
     * Hybrid Mode Bot Service
     *
     * วิธีการทำงาน:
     * 1. ตรวจสอบ keyword ที่ตั้งค่าไว้ก่อน (keyword matching)
     * 2. ถ้าเจอ keyword → ตอบด้วยบอทพื้นฐาน
     * 3. ถ้าไม่เจอ → ส่งให้ AI provider ที่ตั้งค่าไว้
     *
     * Keyword Examples:
     * - "info" / "ข้อมูล" → Show user profile card
     * - "kyc" / "ยืนยันตัวตน" → Start KYC process
     * - "reset" / "รีเซ็ต" → Reset signup flow
     */

    private LineService $lineService;
    private LineBotKeyword $keywordModel;
    private KeywordActivityLogService $activityLogService;

    public function __construct(LineService $lineService, KeywordActivityLogService $activityLogService)
    {
        $this->lineService = $lineService;
        $this->keywordModel = new LineBotKeyword();
        $this->activityLogService = $activityLogService;
    }

    /**
     * ประมวลผลข้อความตาม Hybrid Mode
     *
     * @param string $lineUserId LINE User ID
     * @param string $messageText ข้อความที่ผู้ใช้ส่งมา
     * @param User|null $user User model (if registered)
     * @param AiBotProfile|null $aiBot AI Bot to use as fallback
     * @return array
     */
    public function processMessage(
        string $lineUserId,
        string $messageText,
        ?object $user = null,
        ?AiBotProfile $aiBot = null
    ): array {
        // Step 1: ตรวจสอบ keyword ก่อน
        $keywordMatch = $this->matchKeyword($messageText, $user);

        if ($keywordMatch) {
            // พบ keyword → ใช้บอทพื้นฐาน
            Log::info('Hybrid Bot: Keyword matched', [
                'line_user_id' => $lineUserId,
                'keyword' => $keywordMatch['keyword'],
                'response_type' => 'basic_bot',
            ]);

            return $this->handleKeywordResponse($lineUserId, $keywordMatch, $user, $messageText);
        }

        // Step 2: ไม่พบ keyword → ส่งให้ AI
        Log::info('Hybrid Bot: No keyword match, using AI', [
            'line_user_id' => $lineUserId,
            'ai_bot_id' => $aiBot?->id,
        ]);

        // บันทึก no match event
        $this->activityLogService->logNoMatch($messageText, $lineUserId);

        return $this->handleAiResponse($lineUserId, $messageText, $user, $aiBot);
    }

    /**
     * ตรวจสอบ keyword ที่ตรงกับข้อความ
     *
     * @param string $messageText
     * @param object|null $user
     * @return array|null
     */
    private function matchKeyword(string $messageText, ?object $user): ?array
    {
        $lowerText = strtolower(trim($messageText));

        // Built-in keywords (ตั้งค่าไว้เสมอ)
        $builtInKeywords = [
            // User Info Command
            ['keyword' => 'info', 'type' => 'info', 'trigger_words' => ['info', 'ข้อมูล', 'profile', 'โปรไฟล์']],

            // KYC Command
            ['keyword' => 'kyc', 'type' => 'kyc', 'trigger_words' => ['kyc', 'ยืนยันตัวตน', 'verify', 'verification', 'ยืนยัน']],

            // Reset Command
            ['keyword' => 'reset', 'type' => 'reset', 'trigger_words' => ['reset', 'รีเซ็ต', 'restart', 'เริ่มใหม่']],

            // Help Command
            ['keyword' => 'help', 'type' => 'help', 'trigger_words' => ['help', 'ช่วยเหลือ', 'guide', 'คู่มือ']],
        ];

        // ตรวจสอบ built-in keywords
        foreach ($builtInKeywords as $keyword) {
            foreach ($keyword['trigger_words'] as $trigger) {
                if (str_contains($lowerText, strtolower($trigger))) {
                    return $keyword;
                }
            }
        }

        // ตรวจสอบ custom keywords จากฐานข้อมูล
        $customKeywords = LineBotKeyword::where('is_active', true)
            ->get()
            ->filter(function ($k) use ($lowerText) {
                $triggers = json_decode($k->trigger_words, true) ?? [];
                foreach ($triggers as $trigger) {
                    if (str_contains($lowerText, strtolower($trigger))) {
                        return true;
                    }
                }
                return false;
            })
            ->first();

        if ($customKeywords) {
            return [
                'keyword' => $customKeywords->keyword,
                'type' => $customKeywords->response_type,
                'response' => $customKeywords->response_text,
                'is_custom' => true,
                'model' => $customKeywords, // ใช้สำหรับ logging
            ];
        }

        return null;
    }

    /**
     * ตอบสนองต่อ keyword ที่ตรงกัน (Built-in Bot)
     *
     * @param string $lineUserId
     * @param array $keyword
     * @param object|null $user
     * @param string|null $messageText ข้อความต้นฉบับสำหรับ logging
     * @return array
     */
    private function handleKeywordResponse(string $lineUserId, array $keyword, ?object $user, ?string $messageText = null): array
    {
        $type = $keyword['type'];

        switch ($type) {
            case 'info':
                return $this->handleInfoCommand($lineUserId, $user);

            case 'kyc':
                return $this->handleKycCommand($lineUserId, $user);

            case 'reset':
                return $this->handleResetCommand($lineUserId);

            case 'help':
                return $this->handleHelpCommand($lineUserId);

            case 'custom':
            case 'text':
            case 'flex_message':
            case 'quick_reply':
                return $this->handleCustomKeyword($lineUserId, $keyword, $messageText);

            default:
                return [
                    'success' => false,
                    'message' => 'Unknown keyword type',
                ];
        }
    }

    /**
     * Handle: info / ข้อมูล
     */
    private function handleInfoCommand(string $lineUserId, ?object $user): array
    {
        if (!$user) {
            $this->lineService->sendPushMessage(
                $lineUserId,
                '❌ คุณยังไม่ได้ลงทะเบียน\n\n' .
                'กรุณาสมัครสมาชิกที่เว็บไซต์ของเราก่อน'
            );

            return ['success' => true, 'type' => 'basic_bot', 'handler' => 'info'];
        }

        // Send user info card
        $this->lineService->sendUserInfoCard($user);

        return ['success' => true, 'type' => 'basic_bot', 'handler' => 'info'];
    }

    /**
     * Handle: kyc / ยืนยันตัวตน
     */
    private function handleKycCommand(string $lineUserId, ?object $user): array
    {
        if (!$user) {
            $this->lineService->sendPushMessage(
                $lineUserId,
                '❌ คุณยังไม่ได้ลงทะเบียน\n\n' .
                'กรุณาสมัครสมาชิกก่อน แล้วจึงสามารถทำ KYC ได้'
            );

            return ['success' => true, 'type' => 'basic_bot', 'handler' => 'kyc'];
        }

        $kycService = app(\App\Services\LineKycService::class);
        $kycService->startKycProcess($lineUserId, $user);

        return ['success' => true, 'type' => 'basic_bot', 'handler' => 'kyc'];
    }

    /**
     * Handle: reset / รีเซ็ต
     */
    private function handleResetCommand(string $lineUserId): array
    {
        $prospectService = app(\App\Services\MlmProspectService::class);
        $prospect = $prospectService->getProspectByLineUserId($lineUserId);

        if (!$prospect) {
            $this->lineService->sendPushMessage(
                $lineUserId,
                '⚠️ ไม่พบข้อมูลการสมัครสมาชิก'
            );

            return ['success' => true, 'type' => 'basic_bot', 'handler' => 'reset'];
        }

        $signupService = app(\App\Services\LineSignupService::class);
        $signupService->resetConversation($prospect);

        return ['success' => true, 'type' => 'basic_bot', 'handler' => 'reset'];
    }

    /**
     * Handle: help / ช่วยเหลือ
     */
    private function handleHelpCommand(string $lineUserId): array
    {
        $message = <<<'HELP'
📚 คำสั่งที่พร้อมใช้งาน:

💬 **Chat ปกติ**: พิมพ์ข้อความปกติ ระบบ AI จะตอบให้

📋 **info** / **ข้อมูล**
   └─ แสดงข้อมูลบัญชีของคุณ

🔐 **kyc** / **ยืนยันตัวตน** / **verify**
   └─ เริ่มการยืนยันตัวตน (ID Card + Selfie)

🔄 **reset** / **รีเซ็ต** / **restart**
   └─ เริ่มการสมัครสมาชิกใหม่

❓ **help** / **ช่วยเหลือ**
   └─ แสดงรายการคำสั่งนี้

---

💡 **ส่วนอื่นๆ**: เขียนคำถามปกติ AI จะตอบให้หรือช่วยเหลือ

มีคำถามอื่นไหม? 😊
HELP;

        $this->lineService->sendPushMessage($lineUserId, $message);

        return ['success' => true, 'type' => 'basic_bot', 'handler' => 'help'];
    }

    /**
     * Handle custom keyword
     *
     * @param string $lineUserId
     * @param array $keyword
     * @param string|null $messageText ข้อความต้นฉบับจากผู้ใช้
     * @return array
     */
    private function handleCustomKeyword(string $lineUserId, array $keyword, ?string $messageText = null): array
    {
        $response = $keyword['response'] ?? 'ขอโทษค่ะ ไม่มีคำตอบสำหรับคำสั่งนี้';

        $this->lineService->sendPushMessage($lineUserId, $response);

        // บันทึก custom keyword match
        if (isset($keyword['model']) && $keyword['model'] instanceof LineBotKeyword) {
            $this->activityLogService->logKeywordMatch(
                $keyword['model'],
                $messageText ?? '',
                $lineUserId
            );
        }

        return [
            'success' => true,
            'type' => 'basic_bot',
            'handler' => 'custom_keyword',
            'keyword' => $keyword['keyword'],
        ];
    }

    /**
     * ส่งไปให้ AI เมื่อไม่พบ keyword
     *
     * @param string $lineUserId
     * @param string $messageText
     * @param object|null $user
     * @param AiBotProfile|null $aiBot
     * @return array
     */
    private function handleAiResponse(
        string $lineUserId,
        string $messageText,
        ?object $user,
        ?AiBotProfile $aiBot
    ): array {
        // ถ้าไม่มี AI bot ที่ configured ให้ส่งข้อความค่าเริ่มต้น
        if (!$aiBot || !$aiBot->is_active) {
            $this->lineService->sendPushMessage(
                $lineUserId,
                "ขอโทษค่ะ ขณะนี้ AI Bot ยังไม่พร้อมใช้งาน\n\n" .
                "พิมพ์ 'help' เพื่อดูรายการคำสั่งที่พร้อมใช้ 📋"
            );

            return [
                'success' => true,
                'type' => 'fallback',
                'message' => 'AI bot not available',
            ];
        }

        try {
            // ส่งไปให้ AI provider ที่ตั้งค่าไว้
            $manager = new \App\Services\AI\ConversationManager($aiBot);
            $conversation = $manager->findOrCreateConversation($user, $lineUserId);
            $result = $manager->sendMessage($conversation, $messageText);

            if ($result['success']) {
                // ส่งข้อความจาก AI กลับไปยัง LINE
                $this->lineService->sendPushMessage($lineUserId, $result['message']);

                Log::info('Hybrid Bot: AI response sent', [
                    'line_user_id' => $lineUserId,
                    'ai_bot_id' => $aiBot->id,
                    'tokens_used' => $result['tokens_used'] ?? 0,
                ]);

                return [
                    'success' => true,
                    'type' => 'ai_bot',
                    'ai_provider' => $aiBot->provider->name ?? 'Unknown',
                    'ai_bot_id' => $aiBot->id,
                ];
            } else {
                throw new \Exception($result['error'] ?? 'AI error');
            }

        } catch (\Exception $e) {
            Log::error('Hybrid Bot: AI error', [
                'error' => $e->getMessage(),
                'line_user_id' => $lineUserId,
            ]);

            // Fallback message
            $this->lineService->sendPushMessage(
                $lineUserId,
                "ขออภัยค่ะ ขณะนี้ AI ประสบปัญหาชั่วคราว\n\n" .
                "กรุณาลองใหม่อีกครั้งในภายหลัง 🙏"
            );

            return [
                'success' => false,
                'type' => 'ai_bot',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ได้รับสรุป mode ของข้อความที่ส่งมา
     *
     * @return string 'keyword' | 'ai' | 'hybrid'
     */
    public function getMode(): string
    {
        return 'hybrid';
    }
}
