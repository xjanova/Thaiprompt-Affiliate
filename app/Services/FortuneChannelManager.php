<?php

namespace App\Services;

use App\Contracts\MessagingPlatformInterface;
use App\Jobs\ProcessDeepFortuneReadingJob;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use Illuminate\Support\Facades\Log;

/**
 * Fortune Channel Manager
 *
 * จัดการหลายช่องทาง (Multi-Channel) สำหรับระบบดูดวง
 * รองรับ: Facebook Messenger, LINE Official Account, และช่องทางอื่นในอนาคต
 *
 * Features:
 * - Auto-detect platform จาก user ID format หรือ webhook
 * - ส่งข้อความตอบกลับผ่าน platform ที่เหมาะสม
 * - รองรับ Rich Message (Flex Message, Templates) ตาม platform
 */
class FortuneChannelManager
{
    protected FortuneTellingSetting $settings;

    protected FortuneConversationService $conversationService;

    protected FortuneAIService $aiService;

    /**
     * Platform instances cache
     */
    protected array $platforms = [];

    /**
     * Supported platforms
     */
    public const PLATFORM_FACEBOOK = 'facebook';

    public const PLATFORM_LINE = 'line';

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
        $this->conversationService = new FortuneConversationService($this->settings);
        $this->aiService = new FortuneAIService($this->settings);
    }

    /**
     * ดึงราคาดูดวงจากการตั้งค่าระบบ
     *
     * ลำดับ: deep_reading_price → reading_price → DEEP_READING_PRICE constant
     *
     * @return float ราคาดูดวง (บาท)
     */
    protected function getReadingPrice(): float
    {
        $deepPrice = (float) ($this->settings->deep_reading_price ?? 0);
        if ($deepPrice > 0) {
            return $deepPrice;
        }

        $readingPrice = (float) ($this->settings->reading_price ?? 0);
        if ($readingPrice > 0) {
            return $readingPrice;
        }

        return FortuneConversationService::DEEP_READING_PRICE;
    }

    /**
     * ดึง platform instance
     *
     * @param  string  $platform  ชื่อ platform (facebook, line)
     */
    public function getPlatform(string $platform): ?MessagingPlatformInterface
    {
        if (isset($this->platforms[$platform])) {
            return $this->platforms[$platform];
        }

        $instance = match ($platform) {
            self::PLATFORM_FACEBOOK => new FacebookWebhookService($this->settings),
            self::PLATFORM_LINE => new LineFortuneService($this->settings),
            default => null,
        };

        if ($instance) {
            $this->platforms[$platform] = $instance;
        }

        return $instance;
    }

    /**
     * ประมวลผลข้อความจากทุก platform
     *
     * @param  string  $platform  ชื่อ platform
     * @param  string  $userId  User ID ของ platform นั้น
     * @param  string  $messageText  ข้อความ
     * @param  array|null  $userProfile  โปรไฟล์ผู้ใช้ (optional)
     * @param  array  $extra  ข้อมูลเพิ่มเติม (reply_token สำหรับ LINE, etc.)
     * @return array ผลลัพธ์
     */
    public function processMessage(
        string $platform,
        string $userId,
        string $messageText,
        ?array $userProfile = null,
        array $extra = []
    ): array {
        // บันทึก platform ลงใน context
        $contextUserId = "{$platform}:{$userId}";

        // ดึงโปรไฟล์ถ้ายังไม่มี
        if (empty($userProfile)) {
            $platformService = $this->getPlatform($platform);
            if ($platformService) {
                $userProfile = $platformService->getUserProfile($userId);
            }
        }

        // ใช้ conversation service ประมวลผล
        $result = $this->conversationService->processMessage($userId, $messageText, $userProfile);

        // เพิ่ม platform info + ชื่อผู้ใช้ (ให้ handler ใช้ fallback ได้)
        $result['platform'] = $platform;
        $result['user_id'] = $userId;
        $result['user_name'] = $result['reading']?->facebook_user_name
            ?? $userProfile['name']
            ?? 'คุณ';

        // อัพเดท reading ด้วย platform info
        if ($result['reading'] instanceof FortuneReading) {
            $result['reading']->update([
                'platform' => $platform,
                'platform_user_id' => $userId,
            ]);
        }

        // ส่งข้อความตอบกลับ
        $this->sendResponse($platform, $userId, $result, $extra);

        return $result;
    }

    /**
     * ส่งข้อความตอบกลับตาม platform
     *
     * @param  array  $result  ผลลัพธ์จาก conversation service
     * @param  array  $extra  ข้อมูลเพิ่มเติม
     */
    public function sendResponse(string $platform, string $userId, array $result, array $extra = []): bool
    {
        $action = $result['action'] ?? 'unknown';
        $message = $result['message'] ?? '';

        Log::info('FortuneChannelManager: sendResponse เริ่มส่ง', [
            'platform' => $platform,
            'user_id' => $userId,
            'action' => $action,
            'message_length' => mb_strlen($message),
            'has_quick_replies' => ! empty($result['show_quick_replies']),
            'from_admin' => ! empty($extra['from_admin']),
        ]);

        $platformService = $this->getPlatform($platform);
        if (! $platformService) {
            Log::error('FortuneChannelManager: Platform not found', ['platform' => $platform]);

            return false;
        }

        // สำหรับ LINE ใช้ Flex Message ที่สวยงาม
        if ($platform === self::PLATFORM_LINE && $platformService instanceof LineFortuneService) {
            return $this->sendLineResponse($platformService, $userId, $result, $extra);
        }

        // สำหรับ platform อื่นๆ ส่งข้อความธรรมดา
        $options = [];

        // ถ้าเป็นการส่งจาก admin/ระบบอัตโนมัติ → ส่งผ่าน from_admin flag
        // FacebookWebhookService จะลอง RESPONSE ก่อน แล้ว fallback เป็น MESSAGE_TAG
        if (! empty($extra['from_admin'])) {
            $options['from_admin'] = true;
        }

        // ถ้ากำหนด message_tag มา → ส่งต่อให้ FacebookWebhookService
        // ✅ POST_PURCHASE_UPDATE = update หลังชำระเงิน (ไม่ต้องขออนุมัติ Facebook)
        // ⚠️ HUMAN_AGENT = ต้องได้รับอนุมัติจาก Facebook ก่อนใช้
        if (! empty($extra['message_tag'])) {
            $options['message_tag'] = $extra['message_tag'];
        }

        // ส่ง Birth Chart / Quick Chart ก่อนข้อความทำนาย (ถ้ามี)
        $chartUrl = $result['chart_image_url'] ?? null;
        if ($chartUrl) {
            try {
                $platformService->sendImage($userId, $chartUrl);
                usleep(50000); // ⚡ 50ms (ลดจาก 200ms)
            } catch (\Exception $imgErr) {
                Log::warning('FortuneChannelManager: Failed to send chart image', [
                    'platform' => $platform,
                    'error' => $imgErr->getMessage(),
                ]);
            }
        }

        // เพิ่ม quick replies ถ้ามี (ใช้ค่าจาก result ถ้ามี, fallback เป็น getQuickReplies)
        if (! empty($result['show_quick_replies'])) {
            $options['quick_replies'] = ! empty($result['quick_replies'])
                ? $result['quick_replies']
                : $this->getQuickReplies($action);
        }

        $sent = $platformService->sendMessage($userId, $message, $options);

        Log::info('FortuneChannelManager: sendResponse ผลลัพธ์', [
            'platform' => $platform,
            'user_id' => $userId,
            'action' => $action,
            'sent' => $sent,
            'has_quick_replies' => ! empty($options['quick_replies']),
        ]);

        return $sent;
    }

    /**
     * ส่ง Response สำหรับ LINE ด้วย Flex Message
     *
     * ⚡ ปรับปรุง: ใช้ replyToken สำหรับข้อความแรก (เร็วกว่า + ฟรี)
     * replyToken จาก webhook มีอายุ 1 นาที ใช้ได้ครั้งเดียว
     */
    protected function sendLineResponse(LineFortuneService $lineService, string $userId, array $result, array $extra = []): bool
    {
        $action = $result['action'] ?? 'unknown';
        $message = $result['message'] ?? '';
        $reading = $result['reading'] ?? null;
        $replyToken = $extra['reply_token'] ?? null;

        Log::info('LINE sendLineResponse: เริ่มจัดการ action', [
            'action' => $action,
            'user_id' => $userId,
            'has_reply_token' => ! empty($replyToken),
            'question_number' => $result['question_number'] ?? null,
            'reading_id' => $reading?->id ?? null,
        ]);

        // ⚡ ใช้ Flex Message สวยงามทุก action — ห่อด้วย try-catch เพื่อ fallback เป็น text ถ้า Flex ล้มเหลว
        try {
            $sent = match ($action) {
                // ทำนายพื้นฐานเสร็จ → ส่งคำทำนาย + Upsell Flex
                'basic_done' => $this->sendLineBasicDoneResponse($lineService, $userId, $result, $replyToken),

                // รอชำระเงิน → ส่ง Payment Flex
                'pending_payment' => $this->sendLinePaymentResponse($lineService, $userId, $result, $replyToken),

                // ทำนายละเอียดเสร็จ → ส่งทีละคำถาม
                'completed' => $this->sendLineDeepReadingResponse($lineService, $userId, $result, $replyToken),

                // Help → ส่ง Welcome Flex
                'help', 'filtered' => $this->sendLineHelpResponse($lineService, $userId, $result, $replyToken),

                // เริ่มเก็บคำถาม → ส่ง Flex เลือกหมวด
                'collecting_questions' => $this->sendLineQuestionSelectionResponse($lineService, $userId, $result, $replyToken),

                // ต้องการคำถามเพิ่ม → ส่ง Flex เลือกหมวด (ข้อถัดไป)
                'need_more_questions' => $this->sendLineQuestionSelectionResponse($lineService, $userId, $result, $replyToken),

                // ยืนยันดูดวง → ถ้าเป็น "รอคำถาม" ส่ง TopicFlex / ถ้าเป็นปกติ ส่ง ConfirmationFlex
                'awaiting_confirmation' => $this->sendLineAwaitingResponse($lineService, $userId, $result, $replyToken),

                // ขอวันเกิด → Flex พร้อมรูปแบบ + ราคา
                'collecting_birthdate' => $this->sendLineBirthdateResponse($lineService, $userId, $result, $replyToken),

                // วันเกิดผิดรูปแบบ → Flex แจ้ง error + ตัวอย่าง
                'invalid_birthdate', 'retry_birthdate' => $this->sendLineInvalidBirthdateResponse($lineService, $userId, $result, $replyToken),

                // หมดสิทธิ์ฟรี → Flex แนะนำดูดวงละเอียดพร้อมราคา
                'ai_limit' => $this->sendLineAiLimitResponse($lineService, $userId, $result, $replyToken),

                // เช็คสิทธิ์ → Flex แสดงสิทธิ์ + ราคา
                'check_remaining' => $this->sendLineCheckRemainingResponse($lineService, $userId, $result, $replyToken),

                // สถานะ/สิทธิ์ (จาก Rich Menu) → Flex แสดงสถานะรวม
                'check_status' => $this->sendLineCheckStatusResponse($lineService, $userId, $result, $replyToken),

                // ปฏิเสธ → Flex บอกลา + ปุ่มแชร์
                'declined' => $this->sendLineDeclinedResponse($lineService, $userId, $result, 'declined', $replyToken),

                // ยกเลิก → Flex ยืนยันยกเลิก + ปุ่มดูดวงใหม่
                'cancelled' => $this->sendLineDeclinedResponse($lineService, $userId, $result, 'cancelled', $replyToken),

                // รอชำระเงิน (เตือนซ้ำ) → Flex ยอดเงิน + เวลาเหลือ
                'waiting_payment' => $this->sendLineWaitingPaymentResponse($lineService, $userId, $result, $replyToken),

                // บิลหมดอายุ → Flex แจ้ง + ปุ่มเริ่มใหม่
                'payment_expired' => $this->sendLinePaymentExpiredResponse($lineService, $userId, $result, $replyToken),

                // กำลังประมวลผล → Flex แจ้งสถานะ
                'view_reading_processing' => $this->sendLineProcessingResponse($lineService, $userId, $result, $replyToken),

                // ไม่มีคำทำนาย → Flex เชิญดูดวง
                'view_reading_empty' => $this->sendLineNoReadingResponse($lineService, $userId, $result, $replyToken),

                // ไว้ดูทีหลัง → Flex ยืนยันบันทึก
                'view_later' => $this->sendLineViewLaterResponse($lineService, $userId, $result, $replyToken),

                // ดูดวงละเอียดปิด → Flex แจ้ง
                'deep_reading_disabled' => $this->sendLineDeepDisabledResponse($lineService, $userId, $result, $replyToken),

                // ข้อผิดพลาด → Flex สวยงาม
                'error', 'retry_question' => $this->sendLineErrorResponse($lineService, $userId, $result, $replyToken),

                // ดูคำทำนายพื้นฐาน/ละเอียด → ส่ง Flex คำทำนาย
                'view_reading_basic', 'view_reading_deep' => $this->sendLineViewReadingResponse($lineService, $userId, $result, $replyToken),

                // partial (streaming) → ส่ง text ธรรมดา (Flex ถูก handle ใน FortuneConversationService แล้ว)
                'partial' => $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken),

                // กำลังสร้างคำทำนาย (หลังชำระเงิน) → Flex แจ้งสถานะ
                'queued' => $this->sendLineQueuedResponse($lineService, $userId, $result, $replyToken),

                // ส่ง Chart Image (จาก FortuneProcessDeepReading batch mode)
                'send_chart' => $this->sendLineChartResponse($lineService, $userId, $result, $replyToken),

                // ส่งคำทำนายเชิงลึก (จาก FortuneProcessDeepReading batch mode)
                'deep_reading_result' => $this->sendLineDeepReadingResultResponse($lineService, $userId, $result, $replyToken),

                // ข้อความปิดท้าย (จาก FortuneProcessDeepReading batch mode)
                'reading_complete' => $this->sendLineReadingCompleteResponse($lineService, $userId, $result, $replyToken),

                // แจ้งคำทำนายพร้อม (จาก FortuneProcessDeepReading batch mode)
                'reading_ready' => $this->sendLineReadingReadyResponse($lineService, $userId, $result, $replyToken),

                // กำลังประมวลผล (AI ทำงานอยู่ / PAID status) → Flex แจ้งสถานะ ไม่มีปุ่มดูดวงใหม่
                'processing' => $this->sendLineProcessingResponse($lineService, $userId, $result, $replyToken),

                // ข้อความซ้ำซ้อน (mutex lock) / กำลังประมวลผลอยู่ → ส่ง text สั้นๆ ไม่มีปุ่ม
                'busy' => $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken),

                // แสดงบัญชีธนาคาร → ส่ง text (ไม่มีปุ่มดูดวง)
                'bank_account_info' => $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken),

                // busy_processing (จาก FortuneCheckPendingReadings — แจ้งคนใช้งานมาก)
                'busy_processing' => $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken),

                // Keyword auto-reply จากฐานข้อมูล → ส่งตาม response_type
                'keyword_matched' => $this->sendLineKeywordResponse($lineService, $userId, $result, $replyToken),

                // AI Chat ทั่วไป (Gemini) → ส่ง text ธรรมดา (เป็นธรรมชาติกว่า Flex)
                'ai_chat_response' => $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken),

                // AI ตอบไม่ได้ → ส่งข้อความพร้อม quick reply ให้เลือก "ฝาก/ไม่ฝาก"
                'ai_ask_save_question' => $lineService->sendMessage($userId, $message, [
                    'quick_replies' => $result['quick_reply_options'] ?? [
                        ['label' => '📝 ฝากถึงแอดมิน', 'text' => 'ฝากคำถามถึงแอดมิน'],
                        ['label' => '❌ ไม่ฝาก', 'text' => 'ไม่ฝากคำถาม'],
                    ],
                ]),

                // อื่นๆ → Flex ข้อผิดพลาด (fallback สวยกว่า text ธรรมดา)
                default => $this->sendLineFallbackResponse($lineService, $userId, $message, $replyToken),
            };

            // ⚡ Log ถ้าส่งไม่สำเร็จ (return false) — ช่วยวิเคราะห์ปัญหา
            if (! $sent) {
                Log::warning('LINE sendLineResponse: ส่งไม่สำเร็จ', [
                    'action' => $action,
                    'user_id' => $userId,
                    'message_length' => mb_strlen($message),
                ]);
                // fallback ส่ง text ธรรมดาถ้า Flex ส่งไม่ได้
                if ($message) {
                    $lineService->sendMessage($userId, mb_substr($message, 0, 2000));
                }
            }

            return $sent;
        } catch (\Exception $e) {
            // ⚡ Flex Message ล้มเหลว → fallback ส่ง text ธรรมดา (ดีกว่าไม่ส่งอะไรเลย!)
            Log::error('LINE Flex Message ล้มเหลว — fallback เป็น text', [
                'action' => $action,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'error_file' => $e->getFile().':'.$e->getLine(),
            ]);

            // ส่ง text ธรรมดาเป็น fallback เสมอ
            $fallbackText = $message ?: 'ระบบกำลังดำเนินการค่ะ กรุณารอสักครู่ 🙏';

            return $lineService->sendMessageWithReplyFallback($userId, mb_substr($fallbackText, 0, 2000), $replyToken);
        }
    }

    /**
     * ส่ง Response เมื่อทำนายพื้นฐานเสร็จ (LINE)
     *
     * ⚡ ปรับปรุง: ใช้ replyToken สำหรับ chart+คำทำนาย (เร็วขึ้น)
     */
    protected function sendLineBasicDoneResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? $result['user_name'] ?? 'คุณ';
        $billRef = $reading?->bill_reference;

        // ส่ง Birth Chart / Quick Chart ก่อนคำทำนาย (ถ้ามี)
        $chartUrl = $result['chart_image_url'] ?? null;
        if ($chartUrl) {
            try {
                // ⚡ ใช้ replyToken ส่ง chart + คำทำนาย รวมกัน (เร็วมาก!)
                // LINE อนุญาต max 5 messages ต่อ replyMessage
                if ($replyToken) {
                    $message = $result['message'] ?? '';
                    $parts = explode('═══════════════════════', $message);
                    $prediction = trim($parts[0] ?? $message);

                    // แบ่งคำทำนายเป็นส่วนๆ
                    $fortuneBubbles = $lineService->buildSplitFortuneMessages($prediction, $userName, $billRef);

                    $replyMessages = [
                        [
                            'type' => 'image',
                            'originalContentUrl' => $chartUrl,
                            'previewImageUrl' => $chartUrl,
                        ],
                    ];

                    // ใส่ fortune bubbles (จำกัดให้ reply รวมไม่เกิน 5)
                    $maxFortuneInReply = $this->settings->isDeepReadingEnabled() ? 3 : 4; // เผื่อที่ให้ upsell
                    $inReplyBubbles = array_slice($fortuneBubbles, 0, $maxFortuneInReply);
                    $overflowBubbles = array_slice($fortuneBubbles, $maxFortuneInReply);

                    foreach ($inReplyBubbles as $bubble) {
                        $replyMessages[] = [
                            'type' => 'flex',
                            'altText' => "คำทำนายจาก{$this->settings->getFortuneBrandName()}",
                            'contents' => $bubble,
                        ];
                    }

                    // เพิ่ม Upsell ถ้าเปิดดูดวงละเอียด
                    if ($this->settings->isDeepReadingEnabled() && count($replyMessages) < 5) {
                        $upsellFlex = $lineService->buildUpsellFlexMessage($userName, $this->getReadingPrice());
                        $replyMessages[] = [
                            'type' => 'flex',
                            'altText' => 'ดูดวงละเอียด',
                            'contents' => $upsellFlex,
                        ];
                    }

                    $sent = $lineService->replyMessage($replyToken, $replyMessages);
                    if ($sent) {
                        // ส่วนที่เกิน replyMessage → push ทีหลัง
                        foreach ($overflowBubbles as $bubble) {
                            usleep(50000);
                            $lineService->sendRichMessage($userId, ['alt_text' => 'คำทำนาย (ต่อ)', 'contents' => $bubble]);
                        }

                        return true;
                    }
                    Log::warning('FortuneChannelManager: replyMessage ล้มเหลว (basic_done) fallback เป็น push');
                }

                $lineService->sendImage($userId, $chartUrl);
                usleep(50000); // ⚡ 50ms (ลดจาก 200ms)
            } catch (\Exception $imgErr) {
                Log::warning('FortuneChannelManager: Failed to send LINE chart image (basic_done)', [
                    'error' => $imgErr->getMessage(),
                ]);
            }
        }

        // แยกคำทำนายออกจากข้อความ upsell
        $message = $result['message'] ?? '';
        $parts = explode('═══════════════════════', $message);
        $prediction = trim($parts[0] ?? $message);

        // ⚡ แบ่งคำทำนายเป็นส่วนๆ (ลดปัญหา Flex ยาวเกินไม่ส่ง)
        $fortuneBubbles = $lineService->buildSplitFortuneMessages($prediction, $userName, $billRef);

        foreach ($fortuneBubbles as $bubble) {
            $lineService->sendRichMessage($userId, [
                'alt_text' => "คำทำนายจาก{$this->settings->getFortuneBrandName()}",
                'contents' => $bubble,
            ]);
            usleep(50000); // ⚡ 50ms
        }

        // ส่ง Flex Message Upsell (เฉพาะเมื่อเปิดดูดวงละเอียด)
        if ($this->settings->isDeepReadingEnabled()) {
            $upsellFlex = $lineService->buildUpsellFlexMessage($userName, $this->getReadingPrice());

            return $lineService->sendRichMessage($userId, [
                'alt_text' => 'ดูดวงละเอียด',
                'contents' => $upsellFlex,
            ]);
        }

        return true;
    }

    /**
     * ส่ง Response เมื่อรอชำระเงิน (LINE)
     *
     * ⚡ ปรับปรุง: ใช้ replyToken
     */
    protected function sendLinePaymentResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;

        if (! $reading) {
            return $lineService->sendMessage($userId, $result['message'] ?? 'เกิดข้อผิดพลาด');
        }

        // ✅ ดึงโหมดแสดงช่องทางชำระเงิน (both, bank_only, promptpay_only)
        $displayMode = $this->settings->getPaymentDisplayMode();
        $showBank = $this->settings->shouldShowBankAccount();
        $showPromptpay = $this->settings->shouldShowPromptpay();

        // ดึงบัญชีธนาคารตามโหมด
        $accounts = \App\Models\PaymentBankAccount::active()
            ->smsCheckerEnabled()
            ->ordered()
            ->get();

        if ($accounts->isEmpty()) {
            $accounts = \App\Models\PaymentBankAccount::active()
                ->ordered()
                ->get();
        }

        // จัดรูปแบบตาม payment_display_mode
        $bankAccounts = $accounts->map(function ($a) use ($displayMode, $showBank, $showPromptpay) {
            $info = ['account_name' => $a->account_name];

            if ($displayMode === 'promptpay_only') {
                // โหมดพร้อมเพย์อย่างเดียว — แสดง PromptPay เท่านั้น
                if ($a->hasPromptpay()) {
                    $info['bank_name'] = '📱 พร้อมเพย์';
                    $info['account_number'] = $a->promptpay_id;
                    $info['is_promptpay'] = true;
                } else {
                    return null; // ข้ามบัญชีที่ไม่มี promptpay
                }
            } elseif ($displayMode === 'bank_only') {
                // โหมดบัญชีธนาคารอย่างเดียว
                $info['bank_name'] = $a->bank_name;
                $info['account_number'] = $a->account_number;
            } else {
                // โหมด both — แสดงทั้งสอง
                $info['bank_name'] = $a->bank_name;
                $info['account_number'] = $a->account_number;
                if ($a->hasPromptpay()) {
                    $info['promptpay_id'] = $a->promptpay_id;
                }
            }

            return $info;
        })->filter()->values()->toArray();

        // ⚡ Safety: ถ้าไม่มีบัญชีเลย → ส่ง text แทน
        if (empty($bankAccounts)) {
            Log::error('FortuneChannelManager: ไม่มีบัญชีธนาคาร/พร้อมเพย์', ['display_mode' => $displayMode]);

            return $lineService->sendMessageWithReplyFallback(
                $userId,
                $result['message'] ?? 'กรุณาติดต่อแอดมินเพื่อชำระเงินค่ะ 🙏',
                $replyToken
            );
        }

        // ดึงยอดจาก uniquePaymentAmount (unique amount สำหรับเช็คผ่าน SMS payment checker)
        // ใช้ unique_amount เป็นหลัก เพราะมีทศนิยมเฉพาะสำหรับจับคู่ SMS
        $uniquePayment = $reading->uniquePaymentAmount;
        $amount = $uniquePayment
            ? (float) $uniquePayment->unique_amount
            : ((float) $reading->amount_paid ?: $this->getReadingPrice());
        $expiresAt = $uniquePayment?->expires_at?->format('H:i') ?? '--:--';
        $billRef = $reading->bill_reference;

        // ส่ง Birth Chart ก่อนบิล (ถ้ามี) เพื่อให้ผู้ใช้เห็นภาพดวงดาวก่อนชำระเงิน
        $chartUrl = $result['chart_image_url'] ?? null;
        $paymentFlex = $lineService->buildPaymentFlexMessage($bankAccounts, $amount, $expiresAt, $billRef);

        // ⚡ ใช้ replyToken ส่ง chart+payment รวมกัน (เร็วมาก!)
        if ($replyToken) {
            $replyMessages = [];
            if ($chartUrl) {
                $replyMessages[] = [
                    'type' => 'image',
                    'originalContentUrl' => $chartUrl,
                    'previewImageUrl' => $chartUrl,
                ];
            }
            $replyMessages[] = [
                'type' => 'flex',
                'altText' => 'ยอดชำระ ฿'.number_format($amount, 2),
                'contents' => $paymentFlex,
            ];

            $sent = $lineService->replyMessage($replyToken, $replyMessages);
            if ($sent) {
                return true;
            }
            Log::warning('FortuneChannelManager: replyMessage ล้มเหลว (payment) fallback เป็น push');
        }

        // Fallback: pushMessage
        if ($chartUrl) {
            try {
                $lineService->sendImage($userId, $chartUrl);
                usleep(50000); // ⚡ 50ms (ลดจาก 200ms)
            } catch (\Exception $imgErr) {
                Log::warning('FortuneChannelManager LINE: ส่ง chart image ก่อนบิลไม่สำเร็จ', [
                    'error' => $imgErr->getMessage(),
                ]);
            }
        }

        return $lineService->sendRichMessage($userId, [
            'alt_text' => 'ยอดชำระ ฿'.number_format($amount, 2),
            'contents' => $paymentFlex,
        ]);
    }

    /**
     * ส่ง Response คำทำนายละเอียดทีละคำถาม (LINE)
     *
     * ใช้ Flex Message การ์ดสวยๆ แทน text ธรรมดา
     * แต่ละคำถามเป็นการ์ดแยก มีสีตามหมวด
     * ปิดท้ายด้วยการ์ดขอบคุณ + ปุ่มแชร์ + ปุ่ม engagement
     *
     * ⚡ ปรับปรุง: ลด usleep, ใช้ replyToken ถ้ามี
     */
    protected function sendLineDeepReadingResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $deepReadings = $result['deep_readings'] ?? [];
        $thankYou = $result['thank_you'] ?? '';
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? $result['user_name'] ?? 'คุณ';

        // ถ้าไม่มี deep_readings (format เก่า หรือ streaming thank you) → ส่ง Thank You Flex
        if (empty($deepReadings)) {
            $message = $result['message'] ?? '';
            // ตรวจว่าเป็นข้อความขอบคุณ
            if (mb_strpos($message, 'ขอบคุณ') !== false || mb_strpos($message, 'ขอให้โชคดี') !== false) {
                $thankYouFlex = $lineService->buildThankYouFlexMessage($userName);

                return $lineService->sendFlexWithReplyFallback(
                    $userId, $thankYouFlex, '🙏 ขอบคุณที่ไว้วางใจค่ะ', $replyToken
                );
            }

            return $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken);
        }

        // ส่ง Birth Chart ก่อนคำทำนาย (ถ้ามี)
        $chartUrl = $result['chart_image_url'] ?? null;
        if ($chartUrl) {
            try {
                // ⚡ ส่ง chart ด้วย replyToken (เร็ว + ฟรี)
                if ($replyToken) {
                    $sent = $lineService->replyMessage($replyToken, [
                        [
                            'type' => 'image',
                            'originalContentUrl' => $chartUrl,
                            'previewImageUrl' => $chartUrl,
                        ],
                    ]);
                    if ($sent) {
                        $replyToken = null; // ใช้แล้ว ห้ามใช้ซ้ำ
                    }
                } else {
                    $lineService->sendImage($userId, $chartUrl);
                }
                usleep(50000); // ⚡ 50ms (ลดจาก 200ms)
            } catch (\Exception $imgErr) {
                Log::warning('FortuneChannelManager: Failed to send LINE chart image', [
                    'error' => $imgErr->getMessage(),
                ]);
            }
        }

        $totalQuestions = count($deepReadings);

        // ส่งคำทำนายทีละคำถาม — ใช้ Flex Message การ์ดสวยๆ
        foreach ($deepReadings as $dr) {
            $questionNum = $dr['question_number'];
            $question = $dr['question'];
            $answer = $dr['answer'];

            // สร้าง Flex Message สำหรับคำถามนี้ (มีสีตามหมวด)
            $flex = $lineService->buildDeepReadingFlexMessage(
                $questionNum,
                $question,
                $answer,
                $totalQuestions
            );

            $lineService->sendRichMessage($userId, [
                'alt_text' => "🔮 คำทำนายข้อ {$questionNum}/{$totalQuestions}: {$question}",
                'contents' => $flex,
            ]);

            // ⚡ หน่วงเวลาลด (ป้องกัน rate limit แต่ไม่ช้าเกินไป)
            usleep(50000); // ⚡ 50ms (ลดจาก 200ms)
        }

        // ส่ง Thank You Flex Message ปิดท้าย — มีปุ่มแชร์ + engagement
        $thankYouFlex = $lineService->buildThankYouFlexMessage($userName);
        $lineService->sendRichMessage($userId, [
            'alt_text' => '🙏 ขอบคุณที่ไว้วางใจค่ะ',
            'contents' => $thankYouFlex,
        ]);

        return true;
    }

    /**
     * ส่ง Response Help/Welcome (LINE)
     *
     * ⚡ ปรับปรุง: ใช้ replyToken
     */
    protected function sendLineHelpResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $welcomeFlex = $lineService->buildWelcomeFlexMessage();

        return $lineService->sendFlexWithReplyFallback(
            $userId, $welcomeFlex, "{$this->settings->getFortuneBrandName()}ยินดีต้อนรับค่ะ", $replyToken
        );
    }

    /**
     * ส่ง Response เลือกหมวดคำถาม (LINE)
     *
     * ใช้ Flex Message การ์ดสวยๆ มีปุ่มหมวดคำถามให้กด
     * แทน text ธรรมดาที่บอกให้ "พิมพ์เองได้เลย"
     */
    protected function sendLineQuestionSelectionResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? $result['user_name'] ?? 'คุณ';
        $questionNumber = $result['question_number'] ?? 1;
        $totalQuestions = 2; // ปัจจุบันเก็บ 2 คำถาม

        // ตรวจหาคำถามก่อนหน้า (ถ้าเป็นข้อ 2+)
        $previousQuestion = null;
        if ($reading && $questionNumber > 1) {
            $collected = $reading->getCollectedQuestions();
            $previousQuestion = end($collected) ?: null;
        }

        Log::info('LINE QuestionSelection: กำลังส่ง Flex เลือกหมวดคำถาม', [
            'user_id' => $userId,
            'question_number' => $questionNumber,
            'total_questions' => $totalQuestions,
            'user_name' => $userName,
            'previous_question' => $previousQuestion ? mb_substr($previousQuestion, 0, 30) : null,
            'has_reply_token' => ! empty($replyToken),
            'reading_id' => $reading?->id,
            'reading_status' => $reading?->conversation_status,
            'collected_count' => $reading ? count($reading->getCollectedQuestions()) : 0,
        ]);

        $questionFlex = $lineService->buildQuestionSelectionFlexMessage(
            $questionNumber,
            $totalQuestions,
            $userName,
            $previousQuestion
        );

        $flexJsonSize = strlen(json_encode($questionFlex));
        Log::info('LINE QuestionSelection: Flex JSON built', [
            'json_size' => $flexJsonSize,
            'flex_type' => $questionFlex['type'] ?? 'unknown',
        ]);

        $sent = $lineService->sendFlexWithReplyFallback(
            $userId, $questionFlex, "📝 เลือกหมวดคำถามข้อที่ {$questionNumber}", $replyToken
        );

        Log::info('LINE QuestionSelection: ผลลัพธ์การส่ง', [
            'sent' => $sent,
            'question_number' => $questionNumber,
        ]);

        return $sent;
    }

    // ============================================================
    // 🆕 LINE Flex Handlers — ข้อความสวยงามทุกจุด
    // ============================================================

    /**
     * ส่ง Response awaiting_confirmation — ตรวจสอบว่าเป็น "รอคำถาม" หรือ "รอยืนยัน"
     *
     * ถ้า awaiting_type=question → ส่ง TopicFlex (เลือกหัวข้อดูดวง)
     * ถ้า awaiting_type อื่น → ส่ง ConfirmationFlex (ปกติ)
     */
    protected function sendLineAwaitingResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $awaitingType = $reading?->getConversationState('awaiting_type');

        // ถ้าเป็น "รอคำถาม" → ส่ง Flex เลือกหัวข้อ
        if ($awaitingType === 'question') {
            return $this->sendLineQuestionTopicResponse($lineService, $userId, $result, $replyToken);
        }

        // ปกติ → ส่ง Flex ยืนยัน
        return $this->sendLineConfirmationResponse($lineService, $userId, $result, $replyToken);
    }

    /**
     * ส่ง Flex เลือกหัวข้อดูดวง (เมื่อผู้ใช้พิมพ์ "ดูดวง" เฉยๆ)
     */
    protected function sendLineQuestionTopicResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? $result['user_name'] ?? 'คุณ';
        $remaining = $result['remaining'] ?? 1;
        $isUnlimited = $result['is_unlimited'] ?? ($remaining >= 99);

        $flex = $lineService->buildQuestionTopicFlexMessage($userName, $remaining, $isUnlimited);

        return $lineService->sendFlexWithReplyFallback($userId, $flex, "🔮 อยากถามเรื่องอะไรคะ?", $replyToken);
    }

    /**
     * ส่ง Flex สถานะ/สิทธิ์ (check_status จาก Rich Menu)
     *
     * แสดงข้อมูลรวม: สิทธิ์ฟรี, เครดิตพิเศษ, สถานะสมาชิก
     */
    protected function sendLineCheckStatusResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $userName = $result['user_name'] ?? 'คุณ';
        $remaining = $result['remaining'] ?? 0;
        $used = $result['used'] ?? 0;
        $total = $result['total'] ?? 1;
        $specialCredits = $result['special_credits'] ?? 0;
        $isUnlimited = $result['is_unlimited'] ?? ($remaining >= 99);
        $memberStatus = $result['member_status'] ?? null;
        $walletBalance = $result['wallet_balance'] ?? 0;
        $totalCommission = $result['total_commission'] ?? 0;

        $flex = $lineService->buildStatusFlexMessage(
            $userName,
            $remaining,
            $used,
            $total,
            $specialCredits,
            $isUnlimited,
            $memberStatus,
            $walletBalance,
            $totalCommission,
        );

        return $lineService->sendFlexWithReplyFallback($userId, $flex, "✅ สถานะ: สิทธิ์ฟรี {$remaining} ครั้ง", $replyToken);
    }

    /**
     * ส่ง Flex ยืนยันดูดวง (awaiting_confirmation)
     */
    protected function sendLineConfirmationResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? $result['user_name'] ?? 'คุณ';

        // ดึงสิทธิ์จาก result (ถูกส่งมาจาก FortuneConversationService)
        $remaining = $result['remaining'] ?? ($result['show_quick_replies'] ? 1 : 0);
        $deepReadingEnabled = $this->settings->isDeepReadingEnabled();
        $deepPrice = $this->getReadingPrice();

        $flex = $lineService->buildConfirmationFlexMessage($userName, $remaining, $deepPrice, $deepReadingEnabled);

        return $lineService->sendFlexWithReplyFallback($userId, $flex, "🔮 สวัสดีค่ะ คุณ{$userName}", $replyToken);
    }

    /**
     * ส่ง Flex ขอวันเกิด (collecting_birthdate)
     */
    protected function sendLineBirthdateResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $deepPrice = $this->getReadingPrice();
        $flex = $lineService->buildBirthdateRequestFlexMessage($deepPrice);

        return $lineService->sendFlexWithReplyFallback($userId, $flex, '🎂 กรุณาบอกวันเกิดค่ะ', $replyToken);
    }

    /**
     * ส่ง Flex วันเกิดผิดรูปแบบ (invalid_birthdate)
     */
    protected function sendLineInvalidBirthdateResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $flex = $lineService->buildInvalidBirthdateFlexMessage();

        return $lineService->sendFlexWithReplyFallback($userId, $flex, '⚠️ วันเกิดไม่ถูกต้อง', $replyToken);
    }

    /**
     * ส่ง Flex หมดสิทธิ์ฟรี (ai_limit)
     */
    protected function sendLineAiLimitResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $deepPrice = $this->getReadingPrice();
        $deepEnabled = $this->settings->isDeepReadingEnabled();
        $flex = $lineService->buildAiLimitFlexMessage($deepPrice, $deepEnabled);

        return $lineService->sendFlexWithReplyFallback($userId, $flex, '⏰ สิทธิ์ฟรีหมดแล้วค่ะ', $replyToken);
    }

    /**
     * ส่ง Flex เช็คสิทธิ์ (check_remaining)
     */
    protected function sendLineCheckRemainingResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $userName = $result['user_name'] ?? 'คุณ';
        $remaining = $result['remaining'] ?? 0;
        $used = $result['used'] ?? 0;
        $total = $result['total'] ?? 1;
        $isUnlimited = $result['is_unlimited'] ?? ($remaining >= 99);
        $deepPrice = $this->getReadingPrice();
        $deepEnabled = $this->settings->isDeepReadingEnabled();
        $walletBalance = $result['wallet_balance'] ?? 0;
        $totalCommission = $result['total_commission'] ?? 0;

        $flex = $lineService->buildCheckRemainingFlexMessage($userName, $remaining, $used, $total, $deepPrice, $deepEnabled, $isUnlimited, $walletBalance, $totalCommission);

        return $lineService->sendFlexWithReplyFallback($userId, $flex, "📊 สิทธิ์คงเหลือ: {$remaining}", $replyToken);
    }

    /**
     * ส่ง Flex ปฏิเสธ/ยกเลิก (declined, cancelled)
     */
    protected function sendLineDeclinedResponse(LineFortuneService $lineService, string $userId, array $result, string $type = 'declined', ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? $result['user_name'] ?? 'คุณ';
        $flex = $lineService->buildDeclinedFlexMessage($userName, $type);

        $altText = $type === 'cancelled' ? '✅ ยกเลิกแล้วค่ะ' : '🙏 ไม่เป็นไรค่ะ';

        return $lineService->sendFlexWithReplyFallback($userId, $flex, $altText, $replyToken);
    }

    /**
     * ส่ง Flex รอชำระเงิน — เตือนซ้ำ (waiting_payment)
     */
    protected function sendLineWaitingPaymentResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        if (! $reading) {
            return $lineService->sendMessageWithReplyFallback($userId, $result['message'] ?? 'เกิดข้อผิดพลาด', $replyToken);
        }

        $uniquePayment = $reading->uniquePaymentAmount;
        $amount = $uniquePayment ? (float) $uniquePayment->unique_amount : $this->getReadingPrice();
        $expiresAt = $uniquePayment?->expires_at?->format('H:i') ?? '--:--';
        $billRef = $reading->bill_reference ?? '-';
        $remainingMinutes = $uniquePayment?->expires_at ? max(0, (int) now()->diffInMinutes($uniquePayment->expires_at, false)) : 0;

        $flex = $lineService->buildWaitingPaymentFlexMessage($amount, $billRef, $expiresAt, $remainingMinutes);

        return $lineService->sendFlexWithReplyFallback($userId, $flex, "💰 ยอดชำระ ฿".number_format($amount, 2), $replyToken);
    }

    /**
     * ส่ง Flex บิลหมดอายุ (payment_expired)
     */
    protected function sendLinePaymentExpiredResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $deepPrice = $this->getReadingPrice();
        $flex = $lineService->buildPaymentExpiredFlexMessage($deepPrice);

        return $lineService->sendFlexWithReplyFallback($userId, $flex, '⏰ บิลหมดอายุแล้ว', $replyToken);
    }

    /**
     * ส่ง Flex กำลังประมวลผล (view_reading_processing)
     */
    protected function sendLineProcessingResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $billRef = $reading?->bill_reference ?? '-';
        $flex = $lineService->buildProcessingFlexMessage($billRef);

        return $lineService->sendFlexWithReplyFallback($userId, $flex, '⏳ กำลังสร้างคำทำนาย', $replyToken);
    }

    /**
     * ส่ง Flex กำลังสร้างคำทำนาย (queued — หลังชำระเงินแล้ว)
     */
    protected function sendLineQueuedResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $billRef = $reading?->bill_reference ?? '-';

        // ใช้ Processing Flex เดิม (แจ้งว่ากำลังสร้างคำทำนาย)
        $flex = $lineService->buildProcessingFlexMessage($billRef);

        return $lineService->sendFlexWithReplyFallback($userId, $flex, '✅ ชำระเงินสำเร็จ กำลังสร้างคำทำนาย...', $replyToken);
    }

    /**
     * ส่ง Flex ไม่มีคำทำนาย (view_reading_empty)
     */
    protected function sendLineNoReadingResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $flex = $lineService->buildNoReadingFlexMessage();

        return $lineService->sendFlexWithReplyFallback($userId, $flex, '🔮 ยังไม่มีคำทำนาย', $replyToken);
    }

    /**
     * ส่ง Flex ไว้ดูทีหลัง (view_later)
     */
    protected function sendLineViewLaterResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $flex = $lineService->buildViewLaterFlexMessage();

        return $lineService->sendFlexWithReplyFallback($userId, $flex, '✅ บันทึกแล้วค่ะ', $replyToken);
    }

    /**
     * ส่ง Flex ดูดวงละเอียดปิด (deep_reading_disabled)
     */
    protected function sendLineDeepDisabledResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $flex = $lineService->buildDeepReadingDisabledFlexMessage();

        return $lineService->sendFlexWithReplyFallback($userId, $flex, '🔒 ปิดให้บริการชั่วคราว', $replyToken);
    }

    /**
     * ส่ง Flex ข้อผิดพลาด (error)
     */
    protected function sendLineErrorResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $flex = $lineService->buildErrorFlexMessage();

        return $lineService->sendFlexWithReplyFallback($userId, $flex, '⚠️ เกิดข้อผิดพลาด', $replyToken);
    }

    /**
     * ส่ง Flex ดูคำทำนาย (view_reading_basic, view_reading_deep)
     */
    protected function sendLineViewReadingResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $action = $result['action'] ?? '';
        $userName = $reading?->facebook_user_name ?? $result['user_name'] ?? 'คุณ';

        // ส่ง chart image ก่อน (ถ้ามี)
        $chartUrl = $result['chart_image_url'] ?? $reading?->reading_image_url;
        if ($chartUrl) {
            try {
                if ($replyToken) {
                    $sent = $lineService->replyMessage($replyToken, [
                        ['type' => 'image', 'originalContentUrl' => $chartUrl, 'previewImageUrl' => $chartUrl],
                    ]);
                    if ($sent) {
                        $replyToken = null;
                    }
                } else {
                    $lineService->sendImage($userId, $chartUrl);
                }
                usleep(50000); // ⚡ 50ms
            } catch (\Exception $e) {
                Log::warning('FortuneChannelManager: ส่ง chart image ไม่สำเร็จ (view_reading)', ['error' => $e->getMessage()]);
            }
        }

        // ดูดวงละเอียด → แบ่งส่งเป็นส่วนๆ + Thank You
        if ($action === 'view_reading_deep' && ! empty($reading?->deep_response)) {
            $fortuneBubbles = $lineService->buildSplitFortuneMessages($reading->deep_response, $userName, $reading->bill_reference);
            foreach ($fortuneBubbles as $bubble) {
                $lineService->sendRichMessage($userId, ['alt_text' => '🌟 คำทำนายเชิงลึก', 'contents' => $bubble]);
                usleep(50000); // ⚡ 50ms
            }

            // ส่ง Thank You
            $thankYouFlex = $lineService->buildThankYouFlexMessage($userName);

            return $lineService->sendRichMessage($userId, ['alt_text' => '🙏 ขอบคุณค่ะ', 'contents' => $thankYouFlex]);
        }

        // ดูดวงพื้นฐาน → แบ่งส่งเป็นส่วนๆ
        if (! empty($reading?->basic_response)) {
            $fortuneBubbles = $lineService->buildSplitFortuneMessages($reading->basic_response, $userName);

            // bubble แรก → ใช้ replyToken (เร็ว)
            if ($replyToken && ! empty($fortuneBubbles)) {
                $firstBubble = array_shift($fortuneBubbles);
                $sent = $lineService->replyWithFlex($replyToken, $firstBubble, '🔮 คำทำนายล่าสุด');
                if (! $sent) {
                    // fallback → push
                    $lineService->sendRichMessage($userId, ['alt_text' => '🔮 คำทำนายล่าสุด', 'contents' => $firstBubble]);
                }
                // ส่วนที่เหลือ → push
                foreach ($fortuneBubbles as $bubble) {
                    usleep(50000);
                    $lineService->sendRichMessage($userId, ['alt_text' => '🔮 คำทำนาย (ต่อ)', 'contents' => $bubble]);
                }

                return true;
            }

            // ไม่มี replyToken → push ทั้งหมด
            foreach ($fortuneBubbles as $bubble) {
                $lineService->sendRichMessage($userId, ['alt_text' => '🔮 คำทำนายล่าสุด', 'contents' => $bubble]);
                usleep(50000);
            }

            return true;
        }

        // Fallback
        return $lineService->sendMessageWithReplyFallback($userId, $result['message'] ?? 'ไม่พบคำทำนาย', $replyToken);
    }

    /**
     * ส่ง Chart Image ให้ลูกค้า (send_chart)
     *
     * ใช้โดย FortuneProcessDeepReading batch mode
     * ส่ง chart image + ข้อความแจ้งสถานะ
     */
    protected function sendLineChartResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $chartUrl = $result['chart_image_url'] ?? null;
        $message = $result['message'] ?? '';

        Log::info('LINE sendLineChartResponse: ส่ง chart image', [
            'user_id' => $userId,
            'chart_url' => $chartUrl,
            'has_message' => ! empty($message),
        ]);

        // ส่ง chart image ก่อน (ถ้ามี)
        if ($chartUrl) {
            try {
                if ($replyToken) {
                    $sent = $lineService->replyMessage($replyToken, [
                        ['type' => 'image', 'originalContentUrl' => $chartUrl, 'previewImageUrl' => $chartUrl],
                    ]);
                    if ($sent) {
                        $replyToken = null; // ใช้แล้ว ห้ามใช้ซ้ำ
                    }
                } else {
                    $lineService->sendImage($userId, $chartUrl);
                }
                usleep(50000); // ⚡ 50ms
            } catch (\Exception $imgErr) {
                Log::warning('LINE sendLineChartResponse: ส่ง chart image ไม่สำเร็จ', [
                    'error' => $imgErr->getMessage(),
                ]);
            }
        }

        // ส่งข้อความแจ้ง (ถ้ามี) — ใช้ text ธรรมดา (สั้นๆ)
        if ($message) {
            return $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken);
        }

        return true;
    }

    /**
     * ส่งคำทำนายเชิงลึก (deep_reading_result)
     *
     * ใช้โดย FortuneProcessDeepReading batch mode
     * ข้อความเป็น raw text (deep_response) → แบ่งเป็น Flex bubble สวยๆ
     */
    protected function sendLineDeepReadingResultResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $message = $result['message'] ?? '';
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? $result['user_name'] ?? 'คุณ';
        $billRef = $reading?->bill_reference ?? null;

        Log::info('LINE sendLineDeepReadingResultResponse: ส่งคำทำนายละเอียด', [
            'user_id' => $userId,
            'message_length' => mb_strlen($message),
            'has_reading' => ! empty($reading),
        ]);

        if (empty(trim($message))) {
            return false;
        }

        // ลอง parse คำทำนายจาก reading model (มี collected_questions สำหรับแบ่งเป็น Flex card แต่ละคำถาม)
        $collectedQuestions = $reading ? $reading->getCollectedQuestions() : [];

        if (! empty($collectedQuestions)) {
            // มีคำถามแยก → ลอง parse deep_response ตามคำถาม เพื่อสร้าง Flex card แต่ละข้อ
            $deepResponse = $reading->deep_response ?? $message;
            $parsedQA = $this->parseDeepResponseByQuestions($deepResponse, $collectedQuestions);

            if (! empty($parsedQA)) {
                $totalQuestions = count($parsedQA);
                foreach ($parsedQA as $idx => $qa) {
                    $questionNum = $idx + 1;
                    $flex = $lineService->buildDeepReadingFlexMessage(
                        $questionNum,
                        $qa['question'],
                        $qa['answer'],
                        $totalQuestions
                    );

                    $lineService->sendRichMessage($userId, [
                        'alt_text' => "🔮 คำทำนายข้อ {$questionNum}/{$totalQuestions}: {$qa['question']}",
                        'contents' => $flex,
                    ]);

                    usleep(50000); // ⚡ 50ms
                }

                return true;
            }
        }

        // Fallback: ใช้ buildSplitFortuneMessages (แบ่ง text ยาวเป็นหลาย bubble)
        $fortuneBubbles = $lineService->buildSplitFortuneMessages($message, $userName, $billRef);
        foreach ($fortuneBubbles as $bubble) {
            $lineService->sendRichMessage($userId, ['alt_text' => '🌟 คำทำนายเชิงลึก', 'contents' => $bubble]);
            usleep(50000); // ⚡ 50ms
        }

        return true;
    }

    /**
     * ส่งข้อความปิดท้าย Thank You (reading_complete)
     *
     * ใช้โดย FortuneProcessDeepReading batch mode
     */
    protected function sendLineReadingCompleteResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? $result['user_name'] ?? 'คุณ';

        $thankYouFlex = $lineService->buildThankYouFlexMessage($userName);

        return $lineService->sendRichMessage($userId, [
            'alt_text' => '🙏 ขอบคุณที่ไว้วางใจค่ะ',
            'contents' => $thankYouFlex,
        ]);
    }

    /**
     * ส่งแจ้งเตือนคำทำนายพร้อม (reading_ready)
     *
     * ใช้โดย FortuneProcessDeepReading batch mode (เมื่อ chart ส่งไปก่อนแล้ว)
     */
    protected function sendLineReadingReadyResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $message = $result['message'] ?? '🔮✨ คำทำนายของคุณพร้อมแล้วค่ะ!';

        // สร้าง Flex notification สวยๆ
        $flex = [
            'type' => 'bubble',
            'size' => 'kilo',
            'styles' => ['header' => ['backgroundColor' => $this->settings->getLineFlexPrimaryColor()]],
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '🔮✨', 'size' => 'xl', 'align' => 'center'],
                    ['type' => 'text', 'text' => 'คำทำนายพร้อมแล้วค่ะ!', 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold', 'align' => 'center', 'margin' => 'sm'],
                ],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => $message, 'wrap' => true, 'size' => 'sm', 'color' => '#555555', 'align' => 'center'],
                ],
            ],
        ];

        return $lineService->sendRichMessage($userId, [
            'alt_text' => '🔮 คำทำนายพร้อมแล้วค่ะ!',
            'contents' => $flex,
        ]);
    }

    /**
     * Parse deep_response text ตามคำถามที่เก็บไว้
     *
     * พยายาม match คำถามใน deep_response text เพื่อแยกคำตอบออกมา
     *
     * @param  string  $deepResponse  ข้อความ deep_response ทั้งหมด
     * @param  array  $collectedQuestions  คำถามที่เก็บไว้ ['ดูดวงความรัก', 'ดูดวงการเงิน']
     * @return array [['question' => '...', 'answer' => '...'], ...]
     */
    protected function parseDeepResponseByQuestions(string $deepResponse, array $collectedQuestions): array
    {
        $result = [];
        $totalQuestions = count($collectedQuestions);

        if ($totalQuestions === 0) {
            return [];
        }

        // ลอง split ด้วยรูปแบบต่างๆ ที่ AI มักใช้
        // เช่น "คำถามที่ 1:", "ข้อ 1:", "1.", "**คำถามที่ 1**" ฯลฯ
        $patterns = [
            '/(?:คำถาม(?:ที่)?\s*(\d+)\s*[:：])/u',
            '/(?:ข้อ\s*(\d+)\s*[:：])/u',
            '/(?:^|\n)\s*(\d+)\s*[.)\]]\s*/u',
            '/(?:\*{1,2}คำถาม(?:ที่)?\s*(\d+)\*{1,2}\s*[:：]?)/u',
            '/(?:═{3,})/u', // เส้นแบ่ง
        ];

        // วิธีง่ายที่สุด: ถ้ามี 2 คำถาม ลองแบ่งครึ่ง
        // หรือ split ด้วย pattern คำถามที่พบ
        foreach ($patterns as $pattern) {
            $parts = preg_split($pattern, $deepResponse, -1, PREG_SPLIT_NO_EMPTY);

            // ลบส่วนที่เป็น header (เช่น "คำทำนายเชิงลึก", "เลขที่บิล") → เอาเฉพาะส่วนคำตอบ
            $cleanParts = [];
            foreach ($parts as $part) {
                $trimmed = trim($part);
                // ข้ามส่วนที่เป็น header (สั้นมาก หรือ มีแค่สัญลักษณ์)
                if (mb_strlen($trimmed) < 20) {
                    continue;
                }
                // ข้ามบรรทัดที่เป็น header เช่น "คำทำนายเชิงลึก", "เลขที่บิล"
                if (preg_match('/^[🌟📋═*\s]*(?:คำทำนายเชิงลึก|เลขที่บิล)/u', $trimmed)) {
                    continue;
                }
                $cleanParts[] = $trimmed;
            }

            // ถ้าได้จำนวนส่วนตรงกับจำนวนคำถาม → จับคู่ได้!
            if (count($cleanParts) === $totalQuestions) {
                foreach ($collectedQuestions as $idx => $question) {
                    $result[] = [
                        'question' => $question,
                        'answer' => trim($cleanParts[$idx]),
                    ];
                }

                return $result;
            }
        }

        // ❌ parse ไม่สำเร็จ → คืน empty (จะ fallback ไปใช้ buildSplitFortuneMessages)
        Log::debug('parseDeepResponseByQuestions: parse ไม่สำเร็จ fallback ไปใช้ split', [
            'total_questions' => $totalQuestions,
            'response_length' => mb_strlen($deepResponse),
        ]);

        return [];
    }

    /**
     * ส่ง Response สำหรับ keyword match จากฐานข้อมูล (LineBotKeyword)
     *
     * รองรับ 3 response types:
     * - text: ส่งข้อความ text ผ่าน fallback (Flex สวยถ้ายาว)
     * - flex_message: ส่ง Flex Message JSON โดยตรง
     * - quick_reply: ส่ง text + quick reply buttons
     */
    protected function sendLineKeywordResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $responseType = $result['response_type'] ?? 'text';
        $message = $result['message'] ?? '';

        try {
            switch ($responseType) {
                case 'flex_message':
                    $flexJson = $result['response_flex_json'] ?? null;
                    if ($flexJson) {
                        return $lineService->sendFlexWithReplyFallback(
                            $userId,
                            $flexJson,
                            mb_substr($message ?: 'ข้อมูลจากระบบ', 0, 40),
                            $replyToken
                        );
                    }
                    // fallback เป็น text ถ้าไม่มี flex json
                    return $this->sendLineFallbackResponse($lineService, $userId, $message, $replyToken);

                case 'quick_reply':
                    // ส่ง text พร้อม quick reply buttons ผ่าน sendMessage
                    $quickReplyOptions = $result['quick_reply_options'] ?? [];
                    if (! empty($quickReplyOptions) && ! empty($message)) {
                        return $lineService->sendMessage($userId, $message, [
                            'quick_replies' => $quickReplyOptions,
                        ]);
                    }
                    // fallback เป็น text ถ้าไม่มี quick reply
                    return $this->sendLineFallbackResponse($lineService, $userId, $message, $replyToken);

                case 'text':
                default:
                    return $this->sendLineFallbackResponse($lineService, $userId, $message, $replyToken);
            }
        } catch (\Exception $e) {
            Log::warning('Fortune: sendLineKeywordResponse error', [
                'error' => $e->getMessage(),
                'response_type' => $responseType,
                'keyword_name' => $result['keyword_name'] ?? 'unknown',
            ]);
            // fallback เป็น text ธรรมดา
            return $lineService->sendMessageWithReplyFallback($userId, $message ?: '🔮 มีอะไรให้ช่วยค่ะ?', $replyToken);
        }
    }

    /**
     * ส่ง Flex สำหรับข้อความ default (fallback)
     *
     * แทนที่จะส่ง text ธรรมดา → ส่ง Flex message ที่ดูดี
     * ถ้าข้อความสั้น → ส่ง text ปกติ
     */
    protected function sendLineFallbackResponse(LineFortuneService $lineService, string $userId, string $message, ?string $replyToken = null): bool
    {
        // ถ้าไม่มีข้อความ → ส่งข้อความเริ่มต้น
        if (empty(trim($message))) {
            $message = '🔮 สวัสดีค่ะ พิมพ์คำถามมาได้เลยนะคะ';
        }

        // ถ้าข้อความสั้นมาก → ส่ง text ปกติ
        if (mb_strlen($message) < 50) {
            return $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken);
        }

        // ถ้ายาว → ส่ง Flex สวยๆ
        $brandName = $this->settings->getFortuneBrandName();
        $primaryColor = $this->settings->getLineFlexPrimaryColor();

        $flex = [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => $primaryColor]],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'md',
                'contents' => [
                    ['type' => 'text', 'text' => '🔮', 'size' => 'lg', 'flex' => 0],
                    ['type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md', 'justifyContent' => 'center', 'contents' => [
                        ['type' => 'text', 'text' => "{$brandName}ดูดวง", 'color' => '#FFFFFF', 'size' => 'md', 'weight' => 'bold'],
                    ]],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                'contents' => [
                    ['type' => 'text', 'text' => $message, 'wrap' => true, 'size' => 'sm', 'color' => '#333333', 'lineSpacing' => '6px'],
                ],
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'horizontal', 'spacing' => 'md', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'primary', 'color' => $primaryColor, 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '🔮 ดูดวง', 'text' => 'ดูดวง']],
                ],
            ],
        ];

        return $lineService->sendFlexWithReplyFallback($userId, $flex, "🔮 ข้อความจาก{$brandName}", $replyToken);
    }

    /**
     * ดึง Quick Replies ตาม action
     */
    protected function getQuickReplies(string $action): array
    {
        return match ($action) {
            'awaiting_confirmation' => [
                ['label' => '🔮 ดูเลย', 'text' => 'ดู'],
                ['label' => 'ไม่ต้องการ', 'text' => 'ไม่'],
            ],
            'basic_done' => $this->settings->isDeepReadingEnabled()
                ? [
                    ['label' => '✨ ต้องการ', 'text' => 'ต้องการดูดวงละเอียด'],
                    ['label' => 'ไม่ต้องการ', 'text' => 'ไม่ต้องการ'],
                ]
                : [],
            'collecting_birthdate' => [
                ['label' => 'ยกเลิก', 'text' => 'ยกเลิก'],
            ],
            'pending_payment' => [
                ['label' => '🏦 ดูบัญชี', 'text' => 'บัญชี'],
                ['label' => 'ยกเลิก', 'text' => 'ยกเลิก'],
            ],
            'reading_ready' => [
                ['label' => '🔮 ดูเลย', 'text' => 'ดูคำทำนาย'],
                ['label' => '⏰ ไว้ดูทีหลัง', 'text' => 'ไว้ดูทีหลัง'],
            ],
            'payment_confirmed_wait' => [],
            default => [],
        };
    }

    /**
     * ตรวจสอบว่า platform รองรับหรือไม่
     */
    public function isPlatformSupported(string $platform): bool
    {
        return in_array($platform, [self::PLATFORM_FACEBOOK, self::PLATFORM_LINE]);
    }

    /**
     * ดึงรายการ platform ที่รองรับ
     */
    public function getSupportedPlatforms(): array
    {
        return [
            self::PLATFORM_FACEBOOK => [
                'name' => 'Facebook Messenger',
                'icon' => 'fab fa-facebook-messenger',
                'color' => '#0084FF',
                'supports_rich' => false,
            ],
            self::PLATFORM_LINE => [
                'name' => 'LINE Official Account',
                'icon' => 'fab fa-line',
                'color' => '#00B900',
                'supports_rich' => true,
            ],
        ];
    }

    /**
     * ส่งคำทำนายละเอียดเมื่อชำระเงินสำเร็จ
     *
     * ส่งคำทำนายทีละคำถาม คู่กับคำตอบ
     * ให้ผู้ใช้อ่านทีละข้อ น่าติดตาม
     */
    public function sendDeepReadingAfterPayment(FortuneReading $reading): array
    {
        $platform = $reading->platform ?? self::PLATFORM_FACEBOOK;
        $userId = $reading->platform_user_id ?? $reading->facebook_user_id;

        // Dispatch background job → ไม่ติด web server timeout / webhook 5s timeout
        // Job จะ: confirmPayment → สร้าง chart → สร้างคำทำนาย → ส่ง Messenger → save DB
        ProcessDeepFortuneReadingJob::dispatchSmart(
            $reading->id, null, $platform, $userId
        );

        Log::info('FortuneChannelManager: dispatch ProcessDeepFortuneReadingJob', [
            'reading_id' => $reading->id,
            'platform' => $platform,
            'user_id' => $userId,
        ]);

        return [
            'action' => 'queued',
            'message' => null,
            'reading' => $reading,
            'streaming' => true,
        ];
    }
}
