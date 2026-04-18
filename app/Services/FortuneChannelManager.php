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
     * FortuneTakeoverService — เช็คสถานะเทคโอเวอร์ (defense in depth)
     */
    protected FortuneTakeoverService $takeoverService;

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
        $this->takeoverService = app(FortuneTakeoverService::class);
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

        // 🛑 Defense-in-depth: เช็ค takeover ก่อนเรียก AI ทุกครั้ง
        // (controller ควรเช็คแล้ว แต่ถ้าหลุดมา ให้เงียบ)
        if ($this->takeoverService->isActiveByPlatform($platform, $userId)) {
            Log::info('🛑 ChannelManager: ข้ามข้อความ (กำลังถูกเทคโอเวอร์)', [
                'platform' => $platform,
                'user_id' => $userId,
                'message_preview' => mb_substr($messageText, 0, 50),
            ]);

            return [
                'action' => 'skipped_takeover',
                'platform' => $platform,
                'user_id' => $userId,
                'reading' => null,
            ];
        }

        // ดึงโปรไฟล์ถ้ายังไม่มี
        if (empty($userProfile)) {
            $platformService = $this->getPlatform($platform);
            if ($platformService) {
                $userProfile = $platformService->getUserProfile($userId);
            }
        }

        // ✅ ตั้ง platform ก่อน processMessage เพื่อให้ saveQuestionForAdmin() เก็บค่าถูก
        $this->conversationService->setPlatform($platform);

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

        // ✅ dedup_skip: ข้อความซ้ำ → ข้ามเงียบๆ ไม่ต้องส่งอะไร
        if ($action === 'dedup_skip') {
            return true;
        }

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

        // สำหรับ Facebook ใช้ Button/Generic Template ที่สวยงาม
        if ($platform === self::PLATFORM_FACEBOOK && $platformService instanceof FacebookWebhookService) {
            return $this->sendFacebookResponse($platformService, $userId, $result, $extra);
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
                usleep(500000); // 0.5s — ลดจาก 1.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
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
     * ส่ง Response สำหรับ Facebook ด้วย Button/Generic Template
     *
     * ใช้ FacebookRichMessageService สร้าง templates แล้วส่งผ่าน
     * FacebookWebhookService.sendButtonTemplate()
     *
     * ⚠️ ไม่แตะ SMS Payment / UniquePaymentAmount / confirmPayment()
     * เปลี่ยนแค่วิธี display ข้อมูล (จาก text → Button Template)
     */
    protected function sendFacebookResponse(FacebookWebhookService $fbService, string $userId, array $result, array $extra = []): bool
    {
        $action = $result['action'] ?? 'unknown';
        $message = $result['message'] ?? '';
        $reading = $result['reading'] ?? null;

        Log::info('Facebook sendFacebookResponse: เริ่มจัดการ action', [
            'action' => $action,
            'user_id' => $userId,
            'reading_id' => $reading?->id ?? null,
        ]);

        $richService = new FacebookRichMessageService($this->settings);

        try {
            $sent = match ($action) {
                // ทำนายพื้นฐานเสร็จ → ส่งคำทำนาย + Upsell Template
                'basic_done' => $this->sendFacebookBasicDoneResponse($fbService, $richService, $userId, $result),

                // รอชำระเงิน → ส่ง Payment Template + QR
                'pending_payment' => $this->sendFacebookPaymentResponse($fbService, $richService, $userId, $result),

                // ทำนายละเอียดเสร็จ → ส่งคำทำนาย + LINE invite + affiliate
                'completed' => $this->sendFacebookCompletedResponse($fbService, $richService, $userId, $result),

                // ยืนยันดูดวง → Quick Replies เลือกหมวด
                'awaiting_confirmation' => $this->sendFacebookWithQuickReplies($fbService, $richService, $userId, $message, $action),

                // ขอวันเกิด → Birthdate prompt Template
                'collecting_birthdate' => $this->sendFacebookBirthdateResponse($fbService, $richService, $userId, $result),

                // เลือกคำถาม → Quick Replies (+ ส่งรูปไพ่ยิปซีก่อนถ้ามี)
                'collecting_questions', 'need_more_questions', 'retry_question'
                    => $this->sendFacebookQuestionWithTarotImage($fbService, $richService, $userId, $message, $action, $result),

                // เช็คสิทธิ์ → Check Remaining Template
                'check_remaining' => $this->sendFacebookCheckRemainingResponse($fbService, $richService, $userId, $result),

                // หมดสิทธิ์ฟรี → AI Limit Template
                'ai_limit' => $this->sendFacebookAiLimitResponse($fbService, $richService, $userId, $result),

                // บิลหมดอายุ → Payment Expired Template
                'payment_expired' => $this->sendFacebookPaymentExpiredResponse($fbService, $richService, $userId, $result),

                // ปฏิเสธ / ยกเลิก → Declined Template
                'declined', 'cancelled' => $this->sendFacebookDeclinedResponse($fbService, $richService, $userId, $result),

                // Help → Welcome Template
                'help', 'filtered' => $this->sendFacebookHelpResponse($fbService, $richService, $userId, $result),

                // รอชำระเงิน (เตือนซ้ำ) → Waiting Payment Template
                'waiting_payment' => $this->sendFacebookWaitingPaymentResponse($fbService, $richService, $userId, $result),

                // ยืนยันชำระเงินสำเร็จ → Payment Confirmed Template
                'payment_confirmed_wait' => $this->sendFacebookPaymentConfirmedResponse($fbService, $richService, $userId, $result),

                // เช็คสถานะ → เช็คสิทธิ์
                'check_status' => $this->sendFacebookCheckRemainingResponse($fbService, $richService, $userId, $result),

                // แชร์ลิงก์เชิญเพื่อน
                'share_link' => $this->sendFacebookShareResponse($fbService, $richService, $userId, $result),

                // สายงาน/รายได้ → ส่ง text + Button Template (ปุ่มกดลิงก์)
                'downline_info', 'earnings_info'
                    => $this->sendFacebookButtonLinkResponse($fbService, $userId, $message, $result),

                // AI detect intent ดูดวงเชิงลึก → ส่งข้อความ AI + redirect เข้า deep reading flow
                'ai_redirect_deep_reading' => $this->sendFacebookDeepReadingRedirect($fbService, $richService, $userId, $result),

                // keyword matched, AI chat, throttle, busy ฯลฯ → ส่ง text + Quick Replies
                'keyword_matched', 'ai_chat_response', 'fortune_throttled', 'busy', 'busy_processing',
                'bank_account_info', 'partial', 'processing', 'queued',
                'share_no_user', 'share_error',
                'deep_reading_disabled',
                'view_reading_basic', 'view_reading_deep', 'view_reading_processing', 'view_reading_empty',
                'view_later',
                'invalid_birthdate', 'retry_birthdate',
                'restart_from_birthdate',
                'error',
                'ai_ask_save_question',
                'fortune_ready_notification',
                'send_chart', 'deep_reading_result', 'reading_complete', 'reading_ready'
                    => $this->sendFacebookTextWithOptionalQuickReplies($fbService, $richService, $userId, $message, $action, $result),

                // ✅ สุ่มไพ่ยิปซี → ส่ง Quick Reply พร้อมปุ่มเลือกไพ่
                'draw_tarot_card' => $fbService->sendQuickReplies($userId, $message, [
                    ['content_type' => 'text', 'title' => '🃏 สุ่มไพ่ยิปซี', 'payload' => 'DRAW_TAROT'],
                    ['content_type' => 'text', 'title' => '🔮 เลือกไพ่ 1', 'payload' => 'DRAW_TAROT_1'],
                    ['content_type' => 'text', 'title' => '✨ เลือกไพ่ 2', 'payload' => 'DRAW_TAROT_2'],
                ]),

                // อื่นๆ → ส่ง text ธรรมดา
                default => $fbService->sendMessage($userId, $message ?: 'ระบบกำลังดำเนินการ 🙏'),
            };

            // Log ถ้าส่งไม่สำเร็จ
            if (! $sent) {
                Log::warning('Facebook sendFacebookResponse: ส่งไม่สำเร็จ, fallback เป็น text', [
                    'action' => $action,
                    'user_id' => $userId,
                ]);
                // Fallback ส่ง text ธรรมดา
                if ($message) {
                    $fbService->sendMessage($userId, mb_substr($message, 0, 2000));
                }
            }

            return $sent;
        } catch (\Exception $e) {
            Log::error('Facebook sendFacebookResponse exception — fallback เป็น text', [
                'action' => $action,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            // Fallback ส่ง text ธรรมดาเสมอ
            $fallbackText = $message ?: 'ระบบกำลังดำเนินการ กรุณารอสักครู่ 🙏';

            return $fbService->sendMessage($userId, mb_substr($fallbackText, 0, 2000));
        }
    }

    // ============================================================
    // Facebook Response Handlers (เทียบเท่า sendLine*Response)
    // ============================================================

    /**
     * Facebook: ส่งคำทำนายพื้นฐาน + Upsell + LINE invite
     */
    protected function sendFacebookBasicDoneResponse(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        $message = $result['message'] ?? '';
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? $result['user_name'] ?? 'คุณ';

        // ส่ง Birth Chart ก่อน (ถ้ามี)
        $chartUrl = $result['chart_image_url'] ?? null;
        if ($chartUrl) {
            try {
                $fbService->sendImage($userId, $chartUrl);
                usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            } catch (\Exception $e) {
                Log::warning('Facebook: ส่ง chart image ไม่สำเร็จ', ['error' => $e->getMessage()]);
            }
        }

        // ส่งคำทำนาย (text — อาจยาว ต้องแบ่ง)
        if (! empty($message)) {
            // แยก message ออกจากส่วน upsell
            $parts = explode('═══════════════════════', $message);
            $prediction = trim($parts[0] ?? $message);
            if (! empty($prediction)) {
                $fbService->sendMessage($userId, $prediction);
                usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            }
        }

        // ส่ง Upsell Template (ถ้าเปิดดูดวงละเอียด)
        if ($this->settings->isDeepReadingEnabled()) {
            $upsellTemplate = $richService->buildUpsellTemplate($userName, $this->getReadingPrice());

            return $fbService->sendButtonTemplate($userId, $upsellTemplate);
        }

        // ถ้าไม่มี deep reading → ส่ง LINE invite
        $lineInvite = $richService->buildLineInviteTemplate();
        if ($lineInvite) {
            return $fbService->sendButtonTemplate($userId, $lineInvite);
        }

        return true;
    }

    /**
     * Facebook: ส่งข้อมูลชำระเงิน
     * ⚠️ ไม่แก้ logic การจับคู่ SMS — แค่แสดงข้อมูลบิลสวยขึ้น
     */
    protected function sendFacebookPaymentResponse(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        $reading = $result['reading'] ?? null;
        $message = $result['message'] ?? '';

        // ส่ง Birth Chart ก่อน (ถ้ามี)
        $chartUrl = $result['chart_image_url'] ?? null;
        if ($chartUrl) {
            try {
                $fbService->sendImage($userId, $chartUrl);
                usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            } catch (\Exception $e) {
                Log::warning('Facebook: ส่ง chart image ไม่สำเร็จ (payment)', ['error' => $e->getMessage()]);
            }
        }

        // ✅ ส่งข้อมูลบิล + QR เป็นชุดเดียว (ไม่ซ้ำกับ Payment Template)
        // ส่งเฉพาะ text ข้อมูลบิล (ไม่ส่ง Payment Template อีก เพราะข้อมูลซ้ำ)
        if (! empty($message)) {
            $fbService->sendMessage($userId, $message);
            usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
        }

        // ส่งภาพ QR Code ชำระเงิน (ถ้ามี) — ส่งครั้งเดียว
        $paymentQrUrl = $result['payment_qr_url'] ?? null;
        if ($paymentQrUrl) {
            try {
                $fbService->sendImage($userId, $paymentQrUrl);
                usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            } catch (\Exception $e) {
                Log::warning('Facebook: ส่ง QR Code ไม่สำเร็จ', ['error' => $e->getMessage()]);
            }
        }

        // ✅ ส่ง Payment Template เฉพาะปุ่มกด (ไม่มี QR ซ้ำ)
        if ($reading) {
            $paymentTemplate = $richService->buildPaymentTemplate($reading);

            return $fbService->sendButtonTemplate($userId, $paymentTemplate);
        }

        return true;
    }

    /**
     * Facebook: ส่งคำทำนายละเอียดเสร็จ + LINE invite + affiliate
     */
    protected function sendFacebookCompletedResponse(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        $message = $result['message'] ?? '';

        // ส่ง Birth Chart ก่อน (ถ้ามี)
        $chartUrl = $result['chart_image_url'] ?? null;
        if ($chartUrl) {
            try {
                $fbService->sendImage($userId, $chartUrl);
                usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            } catch (\Exception $e) {
                Log::warning('Facebook: ส่ง chart image ไม่สำเร็จ (completed)', ['error' => $e->getMessage()]);
            }
        }

        // ส่งคำทำนาย (text)
        if (! empty($message)) {
            $fbService->sendMessage($userId, $message);
            usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
        }

        // ส่ง Reading Complete Template + LINE invite + affiliate
        $completeTemplate = $richService->buildReadingCompleteTemplate();

        return $fbService->sendButtonTemplate($userId, $completeTemplate);
    }

    /**
     * Facebook: ส่งข้อความ + Quick Replies ตาม action
     */
    /**
     * Facebook: ส่งรูปไพ่ยิปซีก่อน + Quick Replies เลือกคำถาม
     */
    protected function sendFacebookQuestionWithTarotImage(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, string $message, string $action, array $result): bool
    {
        // ✅ ส่งรูปไพ่ยิปซีก่อน (ถ้ามี)
        $tarotImageUrl = $result['tarot_image_url'] ?? null;
        if ($tarotImageUrl) {
            try {
                $fbService->sendImage($userId, $tarotImageUrl);
            } catch (\Exception $imgErr) {
                Log::warning('Facebook: ส่งรูปไพ่ยิปซีไม่สำเร็จ', [
                    'error' => $imgErr->getMessage(),
                    'image_url' => $tarotImageUrl,
                ]);
            }
        }

        return $this->sendFacebookWithQuickReplies($fbService, $richService, $userId, $message, $action);
    }

    protected function sendFacebookWithQuickReplies(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, string $message, string $action): bool
    {
        $quickReplies = $richService->getQuickRepliesForAction($action);

        if (! empty($quickReplies) && ! empty($message)) {
            return $fbService->sendQuickReplies($userId, $message, $quickReplies);
        }

        return $fbService->sendMessage($userId, $message ?: 'พิมพ์คำถามมาได้เลย 🔮');
    }

    /**
     * Facebook: ขอวันเกิด
     */
    protected function sendFacebookBirthdateResponse(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        // ✅ ส่งเฉพาะ Button Template เท่านั้น (ไม่ส่ง text ก่อน — เพราะ template มีข้อความขอวันเกิดอยู่แล้ว)
        $template = $richService->buildBirthdatePromptTemplate($this->getReadingPrice());

        return $fbService->sendButtonTemplate($userId, $template);
    }

    /**
     * Facebook: เช็คสิทธิ์ดูดวง
     */
    protected function sendFacebookCheckRemainingResponse(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        // ดึงข้อมูลจาก result
        $remaining = $result['remaining'] ?? 0;
        $maxFree = $result['max_free'] ?? (int) ($this->settings->max_free_readings ?? 3);
        $todayCount = $result['today_count'] ?? 0;

        $template = $richService->buildCheckRemainingTemplate($remaining, $maxFree, $todayCount);

        return $fbService->sendButtonTemplate($userId, $template);
    }

    /**
     * Facebook: หมดสิทธิ์ฟรี
     */
    protected function sendFacebookAiLimitResponse(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        $message = $result['message'] ?? '';

        // ส่ง text ข้อความเดิม
        if (! empty($message)) {
            $fbService->sendMessage($userId, $message);
            usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
        }

        $template = $richService->buildAiLimitTemplate($this->getReadingPrice());

        return $fbService->sendButtonTemplate($userId, $template);
    }

    /**
     * Facebook: บิลหมดอายุ
     */
    protected function sendFacebookPaymentExpiredResponse(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        $message = $result['message'] ?? '';

        if (! empty($message)) {
            $fbService->sendMessage($userId, $message);
            usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
        }

        $template = $richService->buildPaymentExpiredTemplate();

        return $fbService->sendButtonTemplate($userId, $template);
    }

    /**
     * Facebook: ปฏิเสธ/ยกเลิก
     */
    protected function sendFacebookDeclinedResponse(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        $message = $result['message'] ?? '';

        if (! empty($message)) {
            $fbService->sendMessage($userId, $message);
            usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
        }

        $template = $richService->buildDeclinedTemplate();

        return $fbService->sendButtonTemplate($userId, $template);
    }

    /**
     * Facebook: Help / Welcome
     */
    protected function sendFacebookHelpResponse(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        $userName = $result['user_name'] ?? 'คุณ';
        $template = $richService->buildWelcomeTemplate($userName);

        return $fbService->sendButtonTemplate($userId, $template);
    }

    /**
     * Facebook: เตือนชำระเงิน
     */
    protected function sendFacebookWaitingPaymentResponse(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        $message = $result['message'] ?? '';

        // ส่ง text ก่อน (มีรายละเอียดบัญชี)
        if (! empty($message)) {
            $fbService->sendMessage($userId, $message);
            usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
        }

        $template = $richService->buildWaitingPaymentTemplate($result['remaining_time'] ?? 'ไม่ทราบ');

        return $fbService->sendButtonTemplate($userId, $template);
    }

    /**
     * Facebook: ยืนยันชำระเงินสำเร็จ
     */
    protected function sendFacebookPaymentConfirmedResponse(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        $template = $richService->buildPaymentConfirmedTemplate();

        return $fbService->sendButtonTemplate($userId, $template);
    }

    /**
     * Facebook: แชร์ลิงก์เชิญเพื่อน
     */
    protected function sendFacebookShareResponse(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        $referralUrl = $result['referral_url'] ?? null;

        if ($referralUrl) {
            $template = $richService->buildAffiliateShareTemplate($referralUrl);

            return $fbService->sendButtonTemplate($userId, $template);
        }

        // ถ้าไม่มี referral URL → ส่ง LINE invite แทน
        $lineInvite = $richService->buildLineInviteTemplate();
        if ($lineInvite) {
            return $fbService->sendButtonTemplate($userId, $lineInvite);
        }

        return $fbService->sendMessage($userId, $result['message'] ?? 'กรุณาสมัครสมาชิกก่อนเพื่อรับลิงก์เชิญเพื่อน');
    }

    /**
     * Facebook: ส่ง text + Quick Replies (ถ้ามี) สำหรับ actions ทั่วไป
     *
     * ใช้กับ actions ที่ไม่ต้องการ Button Template เช่น:
     * keyword_matched, ai_chat_response, error, ฯลฯ
     */
    protected function sendFacebookTextWithOptionalQuickReplies(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, string $message, string $action, array $result): bool
    {
        if (empty($message)) {
            $message = 'ระบบกำลังดำเนินการ 🙏';
        }

        // 🔍 Strip control tags ที่ไม่ควรโชว์ให้ลูกค้า (เผื่อหลุดจาก service layer)
        $offerFortune = (bool) ($result['offer_fortune'] ?? false);
        if (mb_strpos($message, '[OFFER_FORTUNE]') !== false) {
            $offerFortune = true;
            $message = trim(str_replace('[OFFER_FORTUNE]', '', $message));
        }

        // ส่ง chart image ก่อน (ถ้ามี)
        $chartUrl = $result['chart_image_url'] ?? null;
        if ($chartUrl) {
            try {
                $fbService->sendImage($userId, $chartUrl);
                usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            } catch (\Exception $e) {
                Log::warning('Facebook: ส่ง chart ไม่สำเร็จ', ['error' => $e->getMessage()]);
            }
        }

        // ส่ง QR code (ถ้ามี)
        $paymentQrUrl = $result['payment_qr_url'] ?? null;
        if ($paymentQrUrl) {
            $fbService->sendMessage($userId, $message);
            usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            try {
                $fbService->sendImage($userId, $paymentQrUrl);
            } catch (\Exception $e) {
                Log::warning('Facebook: ส่ง QR ไม่สำเร็จ', ['error' => $e->getMessage()]);
            }

            return true;
        }

        // เช็คว่ามี Quick Replies สำหรับ action นี้ไหม
        $quickReplies = $richService->getQuickRepliesForAction($action);

        // ⚡ Fallback quick replies สำหรับ actions ที่ RichMessageService ไม่ได้ครอบคลุม
        // เพื่อให้ UX สอดคล้องกับ LINE (ปุ่ม "คุยกับแม่หมอ" เสมอ)
        if (empty($quickReplies)) {
            $quickReplies = $this->getFacebookFallbackQuickReplies($action, $result, $offerFortune);
        }

        if (! empty($quickReplies)) {
            return $fbService->sendQuickReplies($userId, $message, $quickReplies);
        }

        return $fbService->sendMessage($userId, $message);
    }

    /**
     * Fallback quick replies สำหรับ Facebook (เมื่อ FacebookRichMessageService ไม่ได้ครอบคลุม)
     *
     * ใช้เฉพาะ actions ที่เราเพิ่ม/ปรับปรุงใหม่ — ai_chat_response, processing (stuck),
     * restart_from_birthdate, invalid_birthdate
     */
    protected function getFacebookFallbackQuickReplies(string $action, array $result, bool $offerFortune): array
    {
        return match ($action) {
            'ai_chat_response' => $offerFortune
                ? [
                    ['content_type' => 'text', 'title' => '🔮 เริ่มดูดวง', 'payload' => 'START_FORTUNE'],
                    ['content_type' => 'text', 'title' => '💎 ดูดวงละเอียด', 'payload' => 'DEEP_FORTUNE'],
                    ['content_type' => 'text', 'title' => '💬 คุยกับแม่หมอ', 'payload' => 'TALK_HUMAN'],
                ]
                : [
                    ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'START_FORTUNE'],
                    ['content_type' => 'text', 'title' => '💎 ดูดวงละเอียด', 'payload' => 'DEEP_FORTUNE'],
                    ['content_type' => 'text', 'title' => '💬 คุยกับแม่หมอ', 'payload' => 'TALK_HUMAN'],
                ],

            // PAID stuck → เช็คสถานะ + คุยกับแม่หมอ
            'processing' => ($result['is_stuck'] ?? false)
                ? [
                    ['content_type' => 'text', 'title' => '🔍 เช็คสถานะ', 'payload' => 'CHECK_STATUS'],
                    ['content_type' => 'text', 'title' => '💬 คุยกับแม่หมอ', 'payload' => 'TALK_HUMAN'],
                ]
                : [],

            // ยูสเซ่อร์ขอเริ่มใหม่ระหว่างกรอกวันเกิด
            'restart_from_birthdate' => [
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'START_FORTUNE'],
                ['content_type' => 'text', 'title' => '💎 ดูดวงละเอียด', 'payload' => 'DEEP_FORTUNE'],
                ['content_type' => 'text', 'title' => '💬 คุยกับแม่หมอ', 'payload' => 'TALK_HUMAN'],
            ],

            // วันเกิดผิดรูปแบบ
            'invalid_birthdate', 'retry_birthdate' => [
                ['content_type' => 'text', 'title' => '🔄 เริ่มใหม่', 'payload' => 'RESTART'],
                ['content_type' => 'text', 'title' => '❌ ยกเลิก', 'payload' => 'CANCEL'],
                ['content_type' => 'text', 'title' => '💬 คุยกับแม่หมอ', 'payload' => 'TALK_HUMAN'],
            ],

            default => [],
        };
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

                // สุ่มไพ่ยิปซี → ส่งรูปไพ่ (ถ้ามี) + text + quick reply
                'draw_tarot_card' => $this->sendLineTarotCardResponse($lineService, $userId, $result, $replyToken),

                // ยืนยันดูดวง → ถ้าเป็น "รอคำถาม" ส่ง TopicFlex / ถ้าเป็นปกติ ส่ง ConfirmationFlex
                'awaiting_confirmation' => $this->sendLineAwaitingResponse($lineService, $userId, $result, $replyToken),

                // ขอวันเกิด → Flex พร้อมรูปแบบ + ราคา
                'collecting_birthdate' => $this->sendLineBirthdateResponse($lineService, $userId, $result, $replyToken),

                // วันเกิดผิดรูปแบบ → Flex แจ้ง error + ตัวอย่าง
                'invalid_birthdate', 'retry_birthdate' => $this->sendLineInvalidBirthdateResponse($lineService, $userId, $result, $replyToken),

                // 🔄 ยูสเซ่อร์ขอเริ่มใหม่ระหว่างกรอกวันเกิด → ส่ง text + quick reply เริ่มใหม่
                'restart_from_birthdate' => $this->sendLineMessageWithQuickReply(
                    $lineService, $userId, $message ?: '🔄 ยกเลิกการดูดวงรอบก่อนแล้ว — พิมพ์ "ดูดวง" เพื่อเริ่มใหม่',
                    $replyToken,
                    [
                        ['label' => '🔮 ดูดวง', 'text' => 'ดูดวง'],
                        ['label' => '💎 ดูดวงละเอียด', 'text' => 'ดูดวงละเอียด'],
                        ['label' => '💬 คุยกับแม่หมอ', 'text' => 'คุยกับแม่หมอ'],
                    ]
                ),

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

                // ✅ ยืนยันชำระเงินสำเร็จ → Flex สีเขียว "ได้รับเงินแล้ว กำลังวิเคราะห์"
                'payment_confirmed_wait' => $this->sendLinePaymentConfirmedResponse($lineService, $userId, $result, $replyToken),

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

                // ข้อความซ้ำซ้อน (mutex lock) / กำลังประมวลผลอยู่ → ส่ง text สั้นๆ
                'busy' => $lineService->sendMessageWithReplyFallback($userId, $message ?: 'กำลังประมวลผลอยู่ กรุณารอสักครู่ 🙏', $replyToken),

                // แสดงบัญชีธนาคาร → ส่ง text (ไม่มีปุ่มดูดวง)
                'bank_account_info' => $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken),

                // busy_processing (จาก FortuneCheckPendingReadings — แจ้งคนใช้งานมาก)
                'busy_processing' => $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken),

                // Keyword auto-reply จากฐานข้อมูล → ส่งตาม response_type
                'keyword_matched' => $this->sendLineKeywordResponse($lineService, $userId, $result, $replyToken),

                // AI detect intent ดูดวงเชิงลึก → ส่งข้อความ AI + redirect เข้า deep reading flow
                'ai_redirect_deep_reading' => $this->sendLineDeepReadingRedirect($lineService, $userId, $result, $replyToken),

                // AI Chat ทั่วไป → ส่ง text + Quick Replies default (ดูดวง / คุยกับแม่หมอ)
                'ai_chat_response' => $this->sendLineAiChatResponse($lineService, $userId, $message, $replyToken, $result),

                // Gatekeeper throttle → ส่งข้อความ "รอสักครู่" แทน
                'fortune_throttled' => $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken),

                // แชร์ลิงก์เชิญเพื่อน / ไม่มี user / error
                'share_link', 'share_no_user', 'share_error' => $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken),

                // สายงาน/รายได้ → ส่ง Flex พร้อมปุ่มกดลิงก์ (ไม่ใช่ URL text)
                'downline_info', 'earnings_info' => $this->sendLineButtonLinkResponse($lineService, $userId, $message, $result, $replyToken),

                // AI ตอบไม่ได้ → ส่งข้อความพร้อม quick reply ให้เลือก "ฝาก/ไม่ฝาก"
                // ใช้ replyMessage ก่อน (เร็ว + ฟรี) → fallback เป็น pushMessage
                'ai_ask_save_question' => $this->sendLineMessageWithQuickReply(
                    $lineService, $userId, $message, $replyToken,
                    $result['quick_reply_options'] ?? [
                        ['label' => '📝 ฝากถึงแอดมิน', 'text' => 'ฝากคำถามถึงแอดมิน'],
                        ['label' => '❌ ไม่ฝาก', 'text' => 'ไม่ฝากคำถาม'],
                    ]
                ),

                // แจ้งเตือนคำทำนายพร้อม → ส่ง Flex สวยงาม (สะดุดตา + ปุ่มกดอ่าน)
                // ✅ ใช้ Flex Message แทน text ธรรมดา → ลูกค้าเห็นชัดกว่า ไม่พลาด
                // ✅ ใช้ priority push (ข้าม Gatekeeper) เพราะลูกค้าจ่ายเงินแล้ว ต้องแจ้งให้ได้
                'fortune_ready_notification' => $this->sendLineFortuneReadyNotification(
                    $lineService, $userId, $result, $replyToken
                ),

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
                // fallback ส่ง text ธรรมดาถ้า Flex ส่งไม่ได้ (ลอง reply ก่อน push)
                if ($message) {
                    $lineService->sendMessageWithReplyFallback($userId, mb_substr($message, 0, 2000), $replyToken);
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
            $fallbackText = $message ?: 'ระบบกำลังดำเนินการ กรุณารอสักครู่ 🙏';

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
                        // ส่วนที่เกิน replyMessage → รวมเป็น carousel แล้ว push ครั้งเดียว
                        if (! empty($overflowBubbles)) {
                            usleep(500_000); // 0.5s — ลดจาก 1.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
                            if (count($overflowBubbles) > 1) {
                                $carousel = ['type' => 'carousel', 'contents' => $overflowBubbles];
                                $lineService->sendRichMessage($userId, ['alt_text' => 'คำทำนาย (ต่อ)', 'contents' => $carousel]);
                            } else {
                                $lineService->sendRichMessage($userId, ['alt_text' => 'คำทำนาย (ต่อ)', 'contents' => $overflowBubbles[0]]);
                            }
                        }

                        return true;
                    }
                    Log::warning('FortuneChannelManager: replyMessage ล้มเหลว (basic_done) fallback เป็น push');
                }

                $lineService->sendImage($userId, $chartUrl);
                usleep(500_000); // 0.5s — ลดจาก 1.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
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

        // ✅ รวมทุก bubble เป็น carousel เดียว — ป้องกัน LINE rate limit
        if (count($fortuneBubbles) > 1) {
            $carousel = ['type' => 'carousel', 'contents' => $fortuneBubbles];
            $lineService->sendRichMessage($userId, [
                'alt_text' => "คำทำนายจาก{$this->settings->getFortuneBrandName()}",
                'contents' => $carousel,
            ]);
        } elseif (count($fortuneBubbles) === 1) {
            $lineService->sendRichMessage($userId, [
                'alt_text' => "คำทำนายจาก{$this->settings->getFortuneBrandName()}",
                'contents' => $fortuneBubbles[0],
            ]);
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

        // ✅ ไม่ส่งรูปไพ่ตรงนี้ — ส่งรวมตอนคำทำนายทีเดียว (ประหยัด push + ลดโอกาสค้าง)

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
                $result['message'] ?? 'กรุณาติดต่อแอดมินเพื่อชำระเงิน 🙏',
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
        $qrImageUrl = $result['payment_qr_url'] ?? null;
        $paymentFlex = $lineService->buildPaymentFlexMessage($bankAccounts, $amount, $expiresAt, $billRef);

        // ⚡ ใช้ replyToken ส่ง chart + QR + payment รวมกัน (เร็วมาก! LINE จำกัด 5 messages)
        if ($replyToken) {
            $replyMessages = [];
            if ($chartUrl) {
                $replyMessages[] = [
                    'type' => 'image',
                    'originalContentUrl' => $chartUrl,
                    'previewImageUrl' => $chartUrl,
                ];
            }
            // ส่ง PromptPay QR Code (Dynamic — มียอดเงินฝังอยู่ สแกนจ่ายได้เลย)
            if ($qrImageUrl) {
                $replyMessages[] = [
                    'type' => 'image',
                    'originalContentUrl' => $qrImageUrl,
                    'previewImageUrl' => $qrImageUrl,
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
                usleep(500_000); // 0.5s — ลดจาก 1.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            } catch (\Exception $imgErr) {
                Log::warning('FortuneChannelManager LINE: ส่ง chart image ก่อนบิลไม่สำเร็จ', [
                    'error' => $imgErr->getMessage(),
                ]);
            }
        }

        // ส่ง PromptPay QR Code ผ่าน pushMessage (fallback)
        if ($qrImageUrl) {
            try {
                $lineService->sendImage($userId, $qrImageUrl);
                usleep(500_000); // 0.5s — ลดจาก 1s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            } catch (\Exception $qrErr) {
                Log::warning('FortuneChannelManager LINE: ส่ง PromptPay QR ไม่สำเร็จ', [
                    'error' => $qrErr->getMessage(),
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
        $totalQuestions = count($deepReadings);

        // ✅ รวม messages ทั้งหมดที่ต้องส่ง → ใช้ replyToken batch 5 ข้อความแรก (ฟรี)
        // ส่วนเกิน → push (เสียโควต้า แต่ลูกค้าจ่ายเงินแล้วสมควร)
        $allMessages = [];

        // 1. Birth Chart (ถ้ามี)
        if ($chartUrl) {
            $allMessages[] = [
                'type' => 'image',
                'originalContentUrl' => $chartUrl,
                'previewImageUrl' => $chartUrl,
            ];
        }

        // 2. สร้าง Flex bubbles สำหรับแต่ละข้อ
        foreach ($deepReadings as $dr) {
            $questionNum = $dr['question_number'];
            $question = $dr['question'];
            $answer = $dr['answer'];

            // ไพ่ยิปซี (ถ้ามี) — ส่งรูป + ชื่อไพ่เป็น push ทีหลัง (เกิน 5 messages)
            $tarotCard = $dr['tarot_card'] ?? null;
            $tarotImageUrl = $tarotCard['image_url'] ?? null;
            if ($tarotImageUrl) {
                $cardName = $tarotCard['name_th'] ?? $tarotCard['card_name_th'] ?? 'ไพ่ยิปซี';
                $isReversed = $tarotCard['is_reversed'] ?? false;
                $position = $isReversed ? '(กลับหัว)' : '(หงาย)';

                $allMessages[] = [
                    'type' => 'image',
                    'originalContentUrl' => $tarotImageUrl,
                    'previewImageUrl' => $tarotImageUrl,
                    '_meta' => ['tarot' => true, 'label' => "🃏 ไพ่ข้อที่ {$questionNum}: {$cardName} {$position}"],
                ];
            }

            $flex = $lineService->buildDeepReadingFlexMessage(
                $questionNum,
                $question,
                $answer,
                $totalQuestions
            );

            $allMessages[] = [
                'type' => 'flex',
                'altText' => "🔮 คำทำนายข้อ {$questionNum}/{$totalQuestions}: {$question}",
                'contents' => $flex,
                '_meta' => ['question_num' => $questionNum, 'question' => $question, 'answer' => $answer],
            ];
        }

        // 3. Thank You Flex ปิดท้าย
        $thankYouFlex = $lineService->buildThankYouFlexMessage($userName);
        $allMessages[] = [
            'type' => 'flex',
            'altText' => '🙏 ขอบคุณที่ไว้วางใจค่ะ',
            'contents' => $thankYouFlex,
        ];

        // ✅ ใช้ replyToken ส่ง batch 5 ข้อความแรก (ฟรี!)
        $replyBatch = array_slice($allMessages, 0, 5);
        $pushBatch = array_slice($allMessages, 5);

        // ลบ _meta ก่อนส่ง LINE API (LINE ไม่รู้จัก field นี้)
        $cleanMessages = function (array $messages): array {
            return array_map(function ($msg) {
                unset($msg['_meta']);
                return $msg;
            }, $messages);
        };

        $sentCount = 0;
        $replyUsed = false;

        if ($replyToken && ! empty($replyBatch)) {
            $sent = $lineService->replyMessage($replyToken, $cleanMessages($replyBatch));
            if ($sent) {
                $replyUsed = true;
                $sentCount += count($replyBatch);
                Log::info('LINE DeepReading: ส่ง reply batch สำเร็จ (ฟรี!)', [
                    'count' => count($replyBatch),
                    'remaining_push' => count($pushBatch),
                ]);
            }
        }

        // ถ้า reply ล้มเหลว → push ทั้งหมด
        if (! $replyUsed) {
            $pushBatch = $allMessages;
        }

        // ส่วนเกิน (หรือทั้งหมดถ้า reply ล้มเหลว) → push ทีละข้อความ
        foreach ($pushBatch as $msg) {
            $meta = $msg['_meta'] ?? [];
            unset($msg['_meta']);

            try {
                if ($msg['type'] === 'image') {
                    $lineService->sendImage($userId, $msg['originalContentUrl']);
                    // ส่ง label ไพ่ (ถ้ามี)
                    if (! empty($meta['label'])) {
                        usleep(500_000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
                        $lineService->sendMessage($userId, $meta['label']);
                    }
                } elseif ($msg['type'] === 'flex') {
                    $sent = $lineService->sendRichMessage($userId, [
                        'alt_text' => $msg['altText'],
                        'contents' => $msg['contents'],
                    ]);
                    if (! $sent && ! empty($meta['answer'])) {
                        // Fallback: ส่งเป็น text
                        $qNum = $meta['question_num'] ?? '?';
                        $textMsg = "🔮 คำทำนายข้อที่ {$qNum}/{$totalQuestions}\n❓ {$meta['question']}\n\n{$meta['answer']}";
                        $lineService->sendMessage($userId, mb_substr($textMsg, 0, 5000));
                    }
                }
                $sentCount++;
                usleep(500_000); // 0.5s ระหว่าง push messages (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            } catch (\Exception $sendErr) {
                Log::warning('LINE DeepReading: ส่งข้อความล้มเหลว', [
                    'type' => $msg['type'],
                    'error' => $sendErr->getMessage(),
                ]);
            }
        }

        return $sentCount > 0;
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

        $questionFlex = $lineService->buildQuestionSelectionFlexMessage(
            $questionNumber,
            $totalQuestions,
            $userName,
            $previousQuestion
        );

        Log::info('LINE QuestionSelection: กำลังส่ง Flex เลือกหมวดคำถาม', [
            'user_id' => $userId,
            'question_number' => $questionNumber,
            'has_reply_token' => ! empty($replyToken),
            'reading_id' => $reading?->id,
        ]);

        // ✅ ไม่ส่งรูปไพ่ตรงนี้ — ส่งตอนคำทำนายทีเดียว (ประหยัด push + ไม่ค้าง)
        // ส่งแค่ Flex เลือกคำถาม (ใช้ replyToken → ฟรี + เร็ว)
        return $lineService->sendFlexWithReplyFallback(
            $userId, $questionFlex, "📝 เลือกหมวดคำถามข้อที่ {$questionNumber}", $replyToken
        );
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
        $remaining = $result['remaining'] ?? (($result['show_quick_replies'] ?? false) ? 1 : 0);
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
     * LINE: ส่งข้อความไพ่ยิปซี + quick reply (draw_tarot_card)
     *
     * ✅ ไม่ส่งรูปไพ่ตรงนี้ — ส่งตอนคำทำนายทีเดียว (ประหยัด push + เร็วขึ้น)
     * ส่งแค่ text บอกชื่อไพ่ + ปุ่มสุ่มไพ่
     */
    protected function sendLineTarotCardResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $message = $result['message'] ?? '';

        // ส่ง text + quick reply ปุ่มสุ่มไพ่ (ใช้ replyMessage → ฟรี!)
        return $this->sendLineMessageWithQuickReply(
            $lineService, $userId, $message, $replyToken,
            [['label' => '🃏 สุ่มไพ่ยิปซี', 'text' => 'สุ่มไพ่']]
        );
    }

    /**
     * ส่ง Flex วันเกิดผิดรูปแบบ (invalid_birthdate)
     */
    protected function sendLineInvalidBirthdateResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        // ⬅️ ใช้ text + quick replies แทน Flex เดิม เพื่อให้มีปุ่ม escape
        $message = $result['message'] ?? "ไม่เข้าใจรูปแบบวันเกิด ลองใหม่:\n\n📅 วัน/เดือน/ปี เช่น 15/08/1990";

        return $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, [
            ['label' => '🔄 เริ่มใหม่', 'text' => 'เริ่มใหม่'],
            ['label' => '❌ ยกเลิก', 'text' => 'ยกเลิก'],
            ['label' => '💬 คุยกับแม่หมอ', 'text' => 'คุยกับแม่หมอ'],
        ]);
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

        // ส่ง PromptPay QR Code ซ้ำ (ถ้ามี) เพื่อให้ผู้ใช้สแกนจ่ายได้สะดวก
        $qrImageUrl = $result['payment_qr_url'] ?? null;
        if ($qrImageUrl && $replyToken) {
            $replyMessages = [
                [
                    'type' => 'image',
                    'originalContentUrl' => $qrImageUrl,
                    'previewImageUrl' => $qrImageUrl,
                ],
                [
                    'type' => 'flex',
                    'altText' => '💰 ยอดชำระ ฿'.number_format($amount, 2),
                    'contents' => $flex,
                ],
            ];
            $sent = $lineService->replyMessage($replyToken, $replyMessages);
            if ($sent) {
                return true;
            }
            $replyToken = null; // ✅ token อาจถูกใช้แล้ว ห้ามใช้ซ้ำ
        }

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
        $isStuck = (bool) ($result['is_stuck'] ?? false);

        // ⏳ ถ้ารอนาน → ส่ง text + quick replies (เช็คสถานะ / คุยกับแม่หมอ)
        // แทน Flex ธรรมดา เพื่อให้มีทางออกชัดเจน
        if ($isStuck) {
            $message = $result['message'] ?? '⏳ คำทำนายใช้เวลานานกว่าปกติ';

            return $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, [
                ['label' => '🔍 เช็คสถานะ', 'text' => 'เช็คสถานะ'],
                ['label' => '💬 คุยกับแม่หมอ', 'text' => 'คุยกับแม่หมอ'],
            ]);
        }

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
     * ส่ง Flex ยืนยันชำระเงินสำเร็จ (payment_confirmed_wait)
     *
     * ✅ ข้อความแรกที่ลูกค้าเห็นหลังจ่ายเงิน — สีเขียว "จ่ายแล้ว รอวิเคราะห์"
     * เรียกจาก SmsPaymentService หลัง confirmPayment() สำเร็จ
     */
    protected function sendLinePaymentConfirmedResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $billRef = $reading?->bill_reference ?? '-';
        $userName = $reading?->facebook_user_name ?? 'คุณ';

        // ถ้าไม่มี reading object → ลองดึง bill ref จาก message หรือ result
        if (! $reading && ! empty($result['facebook_user_id'])) {
            $reading = \App\Models\FortuneReading::where('facebook_user_id', $result['facebook_user_id'])
                ->where('is_paid', true)
                ->latest()
                ->first();
            $billRef = $reading?->bill_reference ?? $billRef;
            $userName = $reading?->facebook_user_name ?? $userName;
        }

        $flex = $lineService->buildPaymentConfirmedFlexMessage($billRef, $userName);

        return $lineService->sendFlexWithReplyFallback($userId, $flex, '✅ ชำระเงินสำเร็จ! กำลังวิเคราะห์ดวงชะตา...', $replyToken);
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
        $chartUrl = $result['chart_image_url'] ?? $reading?->reading_image_url;

        // ⭐ ดูดวงละเอียด (เสียเงิน) → ส่งคำทำนายผ่าน replyToken ก่อน (ฟรี+เร็ว+เชื่อถือได้)
        // สำคัญ: replyToken ต้องใช้สำหรับ content ที่ลูกค้าจ่ายเงิน ไม่ใช่ chart image!
        if ($action === 'view_reading_deep' && ! empty($reading?->deep_response)) {
            // ✅ แสดงวันเวลาชำระเงินในคำทำนาย
            $paidAt = $reading->paid_at ? $reading->paid_at->format('d/m/Y H:i') : ($reading->created_at ? $reading->created_at->format('d/m/Y H:i') : null);
            $fortuneBubbles = $lineService->buildSplitFortuneMessages($reading->deep_response, $userName, $reading->bill_reference, $paidAt);

            // ✅ V3: ตรวจ JSON size ก่อนส่ง — ป้องกัน carousel > 50KB ที่ LINE reject
            $sent = false;

            if (count($fortuneBubbles) === 1) {
                // Bubble เดียว → ส่งตรง
                $flexContent = $fortuneBubbles[0];
                $jsonSize = strlen(json_encode($flexContent, JSON_UNESCAPED_UNICODE));

                if ($jsonSize < 45000) {
                    if ($replyToken) {
                        $sent = $lineService->replyWithFlex($replyToken, $flexContent, '🌟 คำทำนายเชิงลึก');
                        if ($sent) {
                            $replyToken = null;
                        }
                    }
                    if (! $sent) {
                        $sent = $lineService->sendRichMessagePriority($userId, ['alt_text' => '🌟 คำทำนายเชิงลึก', 'contents' => $flexContent]);
                    }
                }
            } elseif (count($fortuneBubbles) > 1) {
                // หลาย bubbles → ลอง carousel ถ้า JSON ไม่เกิน 45KB
                $carousel = ['type' => 'carousel', 'contents' => array_slice($fortuneBubbles, 0, 12)];
                $carouselJson = json_encode($carousel, JSON_UNESCAPED_UNICODE);

                if (strlen($carouselJson) < 45000) {
                    // Carousel ขนาดพอดี → ส่ง carousel เดียว
                    if ($replyToken) {
                        $sent = $lineService->replyWithFlex($replyToken, $carousel, '🌟 คำทำนายเชิงลึก');
                        if ($sent) {
                            $replyToken = null;
                        }
                    }
                    if (! $sent) {
                        $sent = $lineService->sendRichMessagePriority($userId, ['alt_text' => '🌟 คำทำนายเชิงลึก', 'contents' => $carousel]);
                    }
                } else {
                    // ✅ Carousel ใหญ่เกิน → ส่งทีละ bubble (เหมือนที่แอดมิน resend ได้ครบ)
                    Log::info('LINE view_reading_deep: carousel ใหญ่เกิน → ส่งทีละ bubble', [
                        'reading_id' => $reading->id ?? null,
                        'bubble_count' => count($fortuneBubbles),
                        'carousel_json_size' => strlen($carouselJson),
                    ]);

                    $sent = true;
                    foreach ($fortuneBubbles as $idx => $bubble) {
                        $bubbleSent = false;
                        // bubble แรก ลองใช้ replyToken (ฟรี)
                        if ($idx === 0 && $replyToken) {
                            $bubbleSent = $lineService->replyWithFlex($replyToken, $bubble, '🌟 คำทำนายเชิงลึก');
                            if ($bubbleSent) {
                                $replyToken = null;
                            }
                        }
                        if (! $bubbleSent) {
                            $bubbleSent = $lineService->sendRichMessagePriority($userId, [
                                'alt_text' => '🌟 คำทำนายเชิงลึก (ส่วนที่ '.($idx + 1).')',
                                'contents' => $bubble,
                            ]);
                        }
                        if (! $bubbleSent) {
                            $sent = false;
                            Log::warning("LINE view_reading_deep: ส่ง bubble ที่ ".($idx + 1)." ไม่สำเร็จ", [
                                'reading_id' => $reading->id ?? null,
                            ]);
                        }
                        if ($idx < count($fortuneBubbles) - 1) {
                            usleep(500_000); // 0.5s — ลดจาก 0.8s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
                        }
                    }
                }
            }

            // ✅ Fallback สุดท้าย: ถ้าทุก Flex ล้มเหลว → ส่งเป็น text ธรรมดา (ดีกว่าไม่ได้อ่าน)
            if (! $sent) {
                Log::warning('LINE view_reading_deep: Flex ทุกวิธีล้มเหลว → fallback text', [
                    'reading_id' => $reading->id ?? null,
                    'user_id' => $userId,
                    'response_len' => mb_strlen($reading->deep_response),
                ]);
                // ตัดเป็น chunks ≤ 5000 ตัวอักษร แล้วส่งทีละ chunk
                $textChunks = $lineService->splitTextForFlexPublic($reading->deep_response, 4800);
                foreach ($textChunks as $idx => $chunk) {
                    $header = $idx === 0 ? "🌟 คำทำนายเชิงลึกของคุณ{$userName}\n📋 {$reading->bill_reference}\n═══════════════════════\n\n" : "(ต่อ)\n\n";
                    $lineService->sendMessagePriority($userId, mb_substr($header.$chunk, 0, 5000));
                    if ($idx < count($textChunks) - 1) {
                        usleep(500_000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
                    }
                }
                $sent = true; // ถือว่าส่งแล้ว (อย่างน้อย text)
            }

            if ($sent) {
                Log::info('LINE view_reading_deep: ส่งคำทำนายสำเร็จ', ['reading_id' => $reading->id ?? null]);
            }

            // ส่ง chart image ทีหลัง (ไม่สำคัญเท่าคำทำนาย)
            if ($chartUrl) {
                try {
                    usleep(500_000); // 0.5s — ลดจาก 1.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
                    $lineService->sendImage($userId, $chartUrl);
                } catch (\Exception $e) {
                    Log::warning('FortuneChannelManager: ส่ง chart image ไม่สำเร็จ (view_reading)', ['error' => $e->getMessage()]);
                }
            }

            // ส่ง Thank You ทีหลัง (ไม่สำคัญ)
            try {
                usleep(500_000); // 0.5s — ลดจาก 1.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
                $thankYouFlex = $lineService->buildThankYouFlexMessage($userName);
                $lineService->sendRichMessage($userId, ['alt_text' => '🙏 ขอบคุณค่ะ', 'contents' => $thankYouFlex]);
            } catch (\Exception $e) {
                // ไม่สำคัญ ข้ามได้
            }

            return $sent;
        }

        // ดูดวงพื้นฐาน (ฟรี) → ส่ง chart image ก่อนก็ได้
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
                usleep(500_000); // 0.5s — ลดจาก 1.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            } catch (\Exception $e) {
                Log::warning('FortuneChannelManager: ส่ง chart image ไม่สำเร็จ (view_reading)', ['error' => $e->getMessage()]);
            }
        }

        // ดูดวงพื้นฐาน → รวมเป็น Carousel เดียว (ป้องกัน LINE rate limit)
        if (! empty($reading?->basic_response)) {
            $fortuneBubbles = $lineService->buildSplitFortuneMessages($reading->basic_response, $userName);

            if (count($fortuneBubbles) > 1) {
                // ✅ รวมทุก bubble เป็น carousel เดียว
                $carousel = ['type' => 'carousel', 'contents' => $fortuneBubbles];
                if ($replyToken) {
                    $sent = $lineService->replyWithFlex($replyToken, $carousel, '🔮 คำทำนายล่าสุด');
                    if (! $sent) {
                        $lineService->sendRichMessage($userId, ['alt_text' => '🔮 คำทำนายล่าสุด', 'contents' => $carousel]);
                    }
                } else {
                    $lineService->sendRichMessage($userId, ['alt_text' => '🔮 คำทำนายล่าสุด', 'contents' => $carousel]);
                }
            } elseif (count($fortuneBubbles) === 1) {
                if ($replyToken) {
                    $sent = $lineService->replyWithFlex($replyToken, $fortuneBubbles[0], '🔮 คำทำนายล่าสุด');
                    if (! $sent) {
                        $lineService->sendRichMessage($userId, ['alt_text' => '🔮 คำทำนายล่าสุด', 'contents' => $fortuneBubbles[0]]);
                    }
                } else {
                    $lineService->sendRichMessage($userId, ['alt_text' => '🔮 คำทำนายล่าสุด', 'contents' => $fortuneBubbles[0]]);
                }
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
                usleep(500_000); // 0.5s — ลดจาก 1.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
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

        // สร้าง Flex content
        $flexContent = null;
        $allBubbles = [];
        $altText = '🌟 คำทำนายเชิงลึก';

        if (! empty($collectedQuestions)) {
            $deepResponse = $reading->deep_response ?? $message;
            $parsedQA = $this->parseDeepResponseByQuestions($deepResponse, $collectedQuestions);

            if (! empty($parsedQA)) {
                $totalQuestions = count($parsedQA);
                $altText = "🔮 คำทำนายเชิงลึก {$totalQuestions} ข้อ";
                foreach ($parsedQA as $idx => $qa) {
                    $questionNum = $idx + 1;
                    $allBubbles[] = $lineService->buildDeepReadingFlexMessage(
                        $questionNum,
                        $qa['question'],
                        $qa['answer'],
                        $totalQuestions
                    );
                }

                if (count($allBubbles) > 1) {
                    $flexContent = ['type' => 'carousel', 'contents' => array_slice($allBubbles, 0, 12)];
                } elseif (count($allBubbles) === 1) {
                    $flexContent = $allBubbles[0];
                }
            }
        }

        // Fallback: ใช้ buildSplitFortuneMessages (แบ่ง text ยาวเป็นหลาย bubble)
        if (! $flexContent) {
            $paidAt = $reading?->paid_at ? $reading->paid_at->format('d/m/Y H:i') : null;
            $fortuneBubbles = $lineService->buildSplitFortuneMessages($message, $userName, $billRef, $paidAt);

            if (count($fortuneBubbles) > 1) {
                $flexContent = ['type' => 'carousel', 'contents' => $fortuneBubbles];
            } elseif (count($fortuneBubbles) === 1) {
                $flexContent = $fortuneBubbles[0];
            }
        }

        if (! $flexContent) {
            return false;
        }

        // ⭐ ส่งคำทำนายเชิงลึก (เสียเงิน) พร้อม retry — ต้องส่งให้ได้!
        $sent = $lineService->sendRichMessage($userId, ['alt_text' => $altText, 'contents' => $flexContent]);

        // 🔄 Retry 1: รอ 5 วิ แล้วลองใหม่
        if (! $sent) {
            Log::warning('LINE DeepResult: push ครั้งแรกไม่สำเร็จ → retry ใน 5 วิ', ['reading_id' => $reading->id ?? null]);
            sleep(5);
            $sent = $lineService->sendRichMessage($userId, ['alt_text' => $altText, 'contents' => $flexContent]);
        }

        // 🔄 Retry 2: รอ 10 วิ แล้วลองอีก
        if (! $sent) {
            Log::warning('LINE DeepResult: push ครั้งที่ 2 ไม่สำเร็จ → retry ใน 10 วิ', ['reading_id' => $reading->id ?? null]);
            sleep(10);
            $sent = $lineService->sendRichMessage($userId, ['alt_text' => $altText, 'contents' => $flexContent]);
        }

        // ⚡ Fallback: carousel ส่งไม่ได้ → ลองส่งทีละ bubble (ป้องกัน Flex size limit เกิน 50KB)
        if (! $sent && ! empty($allBubbles) && count($allBubbles) > 1) {
            Log::warning('LINE DeepResult: carousel ส่งไม่ได้ → fallback ส่งทีละ bubble', [
                'reading_id' => $reading->id ?? null,
                'bubble_count' => count($allBubbles),
            ]);
            $individualSentCount = 0;
            foreach ($allBubbles as $bubble) {
                $bubbleSent = $lineService->sendRichMessage($userId, [
                    'alt_text' => $altText,
                    'contents' => $bubble,
                ]);
                if ($bubbleSent) {
                    $individualSentCount++;
                }
                usleep(500_000); // 0.5s — ลดจาก 1.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            }
            $sent = $individualSentCount > 0;
        }

        // ⚡ Fallback สุดท้าย: ส่ง text ธรรมดา (ดีกว่าไม่ส่งอะไรเลย!)
        if (! $sent) {
            Log::warning('LINE DeepResult: Flex ไม่ได้เลย → fallback text ธรรมดา', [
                'reading_id' => $reading->id ?? null,
            ]);
            $textMessage = mb_substr($message, 0, 5000);
            $sent = $lineService->sendMessage($userId, $textMessage);
        }

        if (! $sent) {
            Log::error('LINE DeepResult: ส่งคำทำนายเชิงลึกไม่สำเร็จทุกวิธี!', ['reading_id' => $reading->id ?? null, 'user_id' => $userId]);
        }

        return $sent;
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
     * ส่งข้อความพร้อม Quick Reply ปุ่มเลือก
     *
     * ลอง replyMessage ก่อน (เร็ว + ฟรี) → fallback เป็น pushMessage
     */
    /**
     * Default quick replies ที่ติดท้ายทุก AI chat response
     *
     * ทำให้ลูกค้ามีทางเลือกเสมอ: เริ่มดูดวง / คุยกับคน
     * ถ้า AI ใส่ [OFFER_FORTUNE] tag → จะได้ปุ่มเริ่มดูดวงเด่นขึ้น
     */
    protected function getDefaultQuickReplies(bool $offerFortune = false): array
    {
        if ($offerFortune) {
            return [
                ['label' => '🔮 เริ่มดูดวง', 'text' => 'ดูดวง'],
                ['label' => '💎 ดูดวงละเอียด', 'text' => 'ดูดวงละเอียด'],
                ['label' => '💬 คุยกับแม่หมอ', 'text' => 'คุยกับแม่หมอ'],
                ['label' => '💬 คุยต่อ', 'text' => 'ขอคำแนะนำเพิ่ม'],
            ];
        }

        return [
            ['label' => '🔮 ดูดวง', 'text' => 'ดูดวง'],
            ['label' => '💎 ดูดวงละเอียด', 'text' => 'ดูดวงละเอียด'],
            ['label' => '💬 คุยกับแม่หมอ', 'text' => 'คุยกับแม่หมอ'],
            ['label' => '📖 อ่านคำทำนาย', 'text' => 'ดูคำทำนาย'],
        ];
    }

    /**
     * ส่ง AI chat response พร้อม default quick replies
     *
     * ตรวจ [OFFER_FORTUNE] tag ในข้อความ AI → ถ้ามี ให้ปุ่มเริ่มดูดวงเด่นขึ้น
     */
    protected function sendLineAiChatResponse(
        LineFortuneService $lineService,
        string $userId,
        string $message,
        ?string $replyToken,
        array $result = []
    ): bool {
        // ถ้า result บ่งบอกว่า AI แนะนำเริ่มดูดวง → ใช้ quick replies แบบ offer
        $offerFortune = (bool) ($result['offer_fortune'] ?? false);

        // ตรวจ [OFFER_FORTUNE] tag ในข้อความ (fallback ถ้า result ไม่ได้ตั้ง flag)
        if (! $offerFortune && mb_strpos($message, '[OFFER_FORTUNE]') !== false) {
            $offerFortune = true;
            $message = trim(str_replace('[OFFER_FORTUNE]', '', $message));
        }

        return $this->sendLineMessageWithQuickReply(
            $lineService,
            $userId,
            $message,
            $replyToken,
            $this->getDefaultQuickReplies($offerFortune)
        );
    }

    protected function sendLineMessageWithQuickReply(LineFortuneService $lineService, string $userId, string $message, ?string $replyToken, array $quickReplies): bool
    {
        // สร้าง Quick Reply items
        $quickReplyItems = [];
        foreach ($quickReplies as $reply) {
            $quickReplyItems[] = [
                'type' => 'action',
                'action' => [
                    'type' => 'message',
                    'label' => $reply['label'] ?? $reply,
                    'text' => $reply['text'] ?? $reply,
                ],
            ];
        }

        $textMessage = [
            'type' => 'text',
            'text' => $message,
            'quickReply' => ['items' => $quickReplyItems],
        ];

        // ลอง replyMessage ก่อน (เร็วกว่า + ฟรี)
        if ($replyToken) {
            $result = $lineService->replyMessage($replyToken, [$textMessage]);
            if ($result) {
                return true;
            }
        }

        // Fallback: pushMessage พร้อม quick replies
        return $lineService->sendMessage($userId, $message, [
            'quick_replies' => $quickReplies,
        ]);
    }

    /**
     * ส่ง LINE Quick Reply แบบ priority (ข้าม Gatekeeper)
     *
     * ใช้สำหรับแจ้งเตือนสำคัญหลังชำระเงิน เช่น "คำทำนายพร้อมแล้ว"
     * ✅ ลอง replyMessage ก่อน (ฟรี) → fallback pushMessagePriority (ข้าม Gatekeeper)
     */
    protected function sendLinePriorityQuickReply(LineFortuneService $lineService, string $userId, string $message, ?string $replyToken, array $quickReplies): bool
    {
        // สร้าง Quick Reply items
        $quickReplyItems = [];
        foreach ($quickReplies as $reply) {
            $quickReplyItems[] = [
                'type' => 'action',
                'action' => [
                    'type' => 'message',
                    'label' => $reply['label'] ?? $reply,
                    'text' => $reply['text'] ?? $reply,
                ],
            ];
        }

        $textMessage = [
            'type' => 'text',
            'text' => $message,
            'quickReply' => ['items' => $quickReplyItems],
        ];

        // ลอง replyMessage ก่อน (ฟรี)
        if ($replyToken) {
            $result = $lineService->replyMessage($replyToken, [$textMessage]);
            if ($result) {
                return true;
            }
        }

        // ✅ Fallback: pushMessagePriority (ข้าม Gatekeeper — ลูกค้าจ่ายเงินแล้ว ต้องส่งให้ได้)
        Log::info('LINE sendLinePriorityQuickReply: ใช้ priority push แจ้งคำทำนายพร้อม', [
            'user_id' => $userId,
        ]);

        return $lineService->sendMessagePriority($userId, $message, [
            'quick_replies' => $quickReplies,
        ]);
    }

    /**
     * ส่ง LINE Flex แจ้ง "คำทำนายพร้อมแล้ว" (fortune_ready_notification)
     *
     * ✅ ใช้ Flex Message สวยงาม (สีม่วง+ทอง) แทน text ธรรมดา
     * ✅ มีปุ่ม "อ่านคำทำนาย" กดได้ทันที (เหมือน Facebook Button Template)
     * ✅ ใช้ priority push (ข้าม Gatekeeper) เพราะลูกค้าจ่ายเงินแล้ว
     * ✅ Fallback เป็น text ถ้า Flex ล้มเหลว
     */
    protected function sendLineFortuneReadyNotification(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? 'คุณ';
        $billRef = $reading?->bill_reference;
        $message = $result['message'] ?? '';

        try {
            // ✅ สร้าง Flex Message สวยงาม
            $flex = $lineService->buildFortuneReadyFlexMessage($userName, $billRef);

            // ลอง replyToken ก่อน (ฟรี + เร็ว)
            $sent = false;
            if ($replyToken) {
                $sent = $lineService->replyWithFlex($replyToken, $flex, '🔮 คำทำนายพร้อมแล้ว!');
            }

            // Fallback: pushMessagePriority (ข้าม Gatekeeper — ลูกค้าจ่ายเงินแล้ว)
            if (! $sent) {
                $sent = $lineService->sendRichMessagePriority($userId, [
                    'alt_text' => '🔮 คำทำนายเชิงลึกพร้อมแล้ว! กดอ่านได้เลยค่ะ',
                    'contents' => $flex,
                ]);
            }

            if ($sent) {
                Log::info('LINE sendLineFortuneReadyNotification: ส่ง Flex สำเร็จ', [
                    'user_id' => $userId,
                    'reading_id' => $reading?->id,
                    'used_reply' => empty($replyToken) ? false : $sent,
                ]);

                return true;
            }
        } catch (\Exception $flexErr) {
            Log::warning('LINE sendLineFortuneReadyNotification: Flex ล้มเหลว → fallback text', [
                'user_id' => $userId,
                'error' => $flexErr->getMessage(),
            ]);
        }

        // ✅ Fallback: ส่งเป็น text + quick replies (ถ้า Flex ล้มเหลว)
        Log::info('LINE sendLineFortuneReadyNotification: fallback text+quick_replies', [
            'user_id' => $userId,
        ]);

        return $lineService->sendMessagePriority($userId, $message ?: "🔮✨ คุณ{$userName}คะ คำทำนายพร้อมแล้วค่ะ!\n\nกดปุ่ม 'อ่านคำทำนาย' ด้านล่างเลยค่ะ ✨", [
            'quick_replies' => [
                ['label' => '📖 อ่านคำทำนาย', 'text' => 'อ่านคำทำนาย'],
                ['label' => '⏰ ไว้ดูทีหลัง', 'text' => 'ไว้ดูทีหลัง'],
            ],
        ]);
    }

    /**
     * ส่ง LINE Flex Message พร้อมปุ่มลิงก์ (URI action)
     *
     * สำหรับ downline_info, earnings_info — แสดงข้อความ + ปุ่มกดไปเว็บ
     */
    protected function sendLineButtonLinkResponse(LineFortuneService $lineService, string $userId, string $message, array $result, ?string $replyToken = null): bool
    {
        $buttons = $result['buttons'] ?? [];
        $brandName = $this->settings->getFortuneBrandName();
        $primaryColor = $this->settings->getLineFlexPrimaryColor();

        // สร้าง Flex buttons
        $flexButtons = [];
        foreach ($buttons as $btn) {
            $flexButtons[] = [
                'type' => 'button',
                'style' => 'primary',
                'color' => $primaryColor,
                'height' => 'sm',
                'margin' => 'sm',
                'action' => [
                    'type' => 'uri',
                    'label' => mb_substr($btn['label'], 0, 20),
                    'uri' => $btn['url'],
                ],
            ];
        }

        $flex = [
            'type' => 'bubble',
            'styles' => [
                'header' => ['backgroundColor' => $primaryColor],
            ],
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => $brandName,
                        'color' => '#FFFFFF',
                        'size' => 'sm',
                        'weight' => 'bold',
                    ],
                ],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => $message,
                        'wrap' => true,
                        'size' => 'sm',
                        'color' => '#333333',
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'sm',
                'contents' => $flexButtons,
            ],
        ];

        $altText = mb_substr($message, 0, 100);

        // ✅ ใช้ replyMessage เท่านั้น — ไม่ push (ประหยัดโควต้า)
        // เป็นการตอบ user message → replyToken ควรใช้ได้เสมอ
        if ($replyToken) {
            $sent = $lineService->replyWithFlex($replyToken, $flex, $altText);
            if ($sent) {
                return true;
            }
            $replyToken = null; // ✅ token อาจถูกใช้แล้ว ห้ามใช้ซ้ำ
        }

        // Fallback: ส่ง text ธรรมดา (push เพราะ replyToken ใช้แล้วหรือไม่มี)
        return $lineService->sendMessage($userId, $message);
    }

    /**
     * ส่ง Facebook Button Template พร้อมปุ่มลิงก์ (web_url)
     *
     * สำหรับ downline_info, earnings_info — แสดงข้อความ + ปุ่มกดไปเว็บ
     */
    protected function sendFacebookButtonLinkResponse(FacebookWebhookService $fbService, string $userId, string $message, array $result): bool
    {
        $buttons = $result['buttons'] ?? [];

        if (empty($buttons)) {
            return $fbService->sendMessage($userId, $message);
        }

        // สร้าง Facebook web_url buttons (สูงสุด 3 ปุ่ม)
        $fbButtons = [];
        foreach (array_slice($buttons, 0, 3) as $btn) {
            $fbButtons[] = [
                'type' => 'web_url',
                'url' => $btn['url'],
                'title' => mb_substr($btn['label'], 0, 20),
            ];
        }

        // ส่ง Button Template
        try {
            return $fbService->sendButtonTemplate($userId, mb_substr($message, 0, 640), $fbButtons);
        } catch (\Exception $e) {
            Log::warning('Facebook: Button Template ล้มเหลว — fallback text', [
                'error' => $e->getMessage(),
            ]);

            return $fbService->sendMessage($userId, $message);
        }
    }

    /**
     * Facebook: AI detect intent ดูดวงเชิงลึก → ส่งข้อความ AI + redirect เข้า deep reading flow
     *
     * ส่งข้อความ AI ตอบก่อน (แนะนำบริการ) แล้วเรียก startDeepReadingFlow()
     * ส่ง birthdate collection response ตามหลัง (เป็น 2 ข้อความต่อกัน)
     */
    protected function sendFacebookDeepReadingRedirect(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        $message = $result['message'] ?? '';

        // ส่งข้อความ AI ตอบก่อน (แนะนำบริการดูดวงเชิงลึก)
        if (! empty($message)) {
            $fbService->sendMessage($userId, $message);
            usleep(500000);
        }

        // เริ่ม deep reading flow → ได้ result ใหม่ (collecting_birthdate)
        $deepResult = $this->conversationService->startDeepReadingFlowPublic($userId);

        if (($deepResult['action'] ?? '') === 'deep_reading_disabled') {
            // ดูดวงเชิงลึกปิดอยู่ → แจ้งผู้ใช้
            return $fbService->sendMessage($userId, $deepResult['message'] ?? 'บริการดูดวงเชิงลึกปิดให้บริการชั่วคราวค่ะ');
        }

        // ส่ง response ตาม action ที่ได้ (ปกติจะเป็น collecting_birthdate)
        return $this->sendFacebookResponse($fbService, $userId, $deepResult);
    }

    /**
     * LINE: AI detect intent ดูดวงเชิงลึก → ส่งข้อความ AI + redirect เข้า deep reading flow
     */
    protected function sendLineDeepReadingRedirect(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $message = $result['message'] ?? '';

        // ส่งข้อความ AI ตอบก่อน (แนะนำบริการดูดวงเชิงลึก)
        if (! empty($message)) {
            $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken);
            $replyToken = null; // ใช้ replyToken ได้ครั้งเดียว
            usleep(500000);
        }

        // เริ่ม deep reading flow → ได้ result ใหม่ (collecting_birthdate)
        $deepResult = $this->conversationService->startDeepReadingFlowPublic($userId);

        if (($deepResult['action'] ?? '') === 'deep_reading_disabled') {
            return $lineService->sendMessageWithReplyFallback($userId, $deepResult['message'] ?? 'บริการดูดวงเชิงลึกปิดให้บริการชั่วคราวค่ะ', $replyToken);
        }

        // ส่ง response ตาม action ที่ได้ (ปกติจะเป็น collecting_birthdate)
        return $this->sendLineResponse($lineService, $userId, $deepResult, ['reply_token' => $replyToken]);
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
        // ✅ ตรวจจับ LINE user จาก ID format เป็น fallback (กรณี reading เก่าที่ไม่มี platform)
        $userId = $reading->platform_user_id ?? $reading->facebook_user_id;
        $platform = $reading->platform;
        if (! $platform) {
            $platform = (preg_match('/^U[0-9a-f]{32}$/i', $userId)) ? self::PLATFORM_LINE : self::PLATFORM_FACEBOOK;
        }

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
