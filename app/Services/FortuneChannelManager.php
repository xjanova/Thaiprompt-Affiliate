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

        // เพิ่ม platform info
        $result['platform'] = $platform;
        $result['user_id'] = $userId;

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
                usleep(200000); // ⚡ 0.2 วินาที (ลดจาก 0.5)
            } catch (\Exception $imgErr) {
                Log::warning('FortuneChannelManager: Failed to send chart image', [
                    'platform' => $platform,
                    'error' => $imgErr->getMessage(),
                ]);
            }
        }

        // เพิ่ม quick replies ถ้ามี
        if (! empty($result['show_quick_replies'])) {
            $options['quick_replies'] = $this->getQuickReplies($action);
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

        // ใช้ Flex Message สวยงามทุก action — ไม่ส่ง text ธรรมดาอีกต่อไป!
        return match ($action) {
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

            // ยืนยันดูดวง → Flex สวยๆ พร้อมราคา + สิทธิ์
            'awaiting_confirmation' => $this->sendLineConfirmationResponse($lineService, $userId, $result, $replyToken),

            // ขอวันเกิด → Flex พร้อมรูปแบบ + ราคา
            'collecting_birthdate' => $this->sendLineBirthdateResponse($lineService, $userId, $result, $replyToken),

            // วันเกิดผิดรูปแบบ → Flex แจ้ง error + ตัวอย่าง
            'invalid_birthdate', 'retry_birthdate' => $this->sendLineInvalidBirthdateResponse($lineService, $userId, $result, $replyToken),

            // หมดสิทธิ์ฟรี → Flex แนะนำดูดวงละเอียดพร้อมราคา
            'ai_limit' => $this->sendLineAiLimitResponse($lineService, $userId, $result, $replyToken),

            // เช็คสิทธิ์ → Flex แสดงสิทธิ์ + ราคา
            'check_remaining' => $this->sendLineCheckRemainingResponse($lineService, $userId, $result, $replyToken),

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

            // อื่นๆ → Flex ข้อผิดพลาด (fallback สวยกว่า text ธรรมดา)
            default => $this->sendLineFallbackResponse($lineService, $userId, $message, $replyToken),
        };
    }

    /**
     * ส่ง Response เมื่อทำนายพื้นฐานเสร็จ (LINE)
     *
     * ⚡ ปรับปรุง: ใช้ replyToken สำหรับ chart+คำทำนาย (เร็วขึ้น)
     */
    protected function sendLineBasicDoneResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? 'คุณ';
        $billRef = $reading?->bill_reference;

        // ส่ง Birth Chart / Quick Chart ก่อนคำทำนาย (ถ้ามี)
        $chartUrl = $result['chart_image_url'] ?? null;
        if ($chartUrl) {
            try {
                // ⚡ ใช้ replyToken ส่ง chart + คำทำนาย รวมกัน (เร็วมาก!)
                if ($replyToken) {
                    $message = $result['message'] ?? '';
                    $parts = explode('═══════════════════════', $message);
                    $prediction = trim($parts[0] ?? $message);
                    $fortuneFlex = $lineService->buildFortuneFlexMessage($prediction, $userName, $billRef);

                    $replyMessages = [
                        [
                            'type' => 'image',
                            'originalContentUrl' => $chartUrl,
                            'previewImageUrl' => $chartUrl,
                        ],
                        [
                            'type' => 'flex',
                            'altText' => 'คำทำนายจากแม่หมอจันทรา',
                            'contents' => $fortuneFlex,
                        ],
                    ];

                    // เพิ่ม Upsell ถ้าเปิดดูดวงละเอียด (รวมใน replyMessage เดียว สูงสุด 5 ข้อความ)
                    if ($this->settings->isDeepReadingEnabled()) {
                        $upsellFlex = $lineService->buildUpsellFlexMessage($userName, $this->getReadingPrice());
                        $replyMessages[] = [
                            'type' => 'flex',
                            'altText' => 'ดูดวงละเอียด',
                            'contents' => $upsellFlex,
                        ];
                    }

                    $sent = $lineService->replyMessage($replyToken, $replyMessages);
                    if ($sent) {
                        return true;
                    }
                    // ถ้า reply ล้มเหลว → fallback ด้านล่าง
                    Log::warning('FortuneChannelManager: replyMessage ล้มเหลว (basic_done) fallback เป็น push');
                }

                $lineService->sendImage($userId, $chartUrl);
                usleep(200000); // 0.2 วินาที (ลดจาก 0.5)
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

        // ส่ง Flex Message คำทำนาย
        $fortuneFlex = $lineService->buildFortuneFlexMessage($prediction, $userName, $billRef);
        $lineService->sendRichMessage($userId, [
            'alt_text' => 'คำทำนายจากแม่หมอจันทรา',
            'contents' => $fortuneFlex,
        ]);

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

        // ดึงบัญชีธนาคาร
        $bankAccounts = \App\Models\PaymentBankAccount::active()
            ->smsCheckerEnabled()
            ->ordered()
            ->get()
            ->map(fn ($a) => [
                'bank_name' => $a->bank_name,
                'account_number' => $a->account_number,
                'account_name' => $a->account_name,
            ])
            ->toArray();

        if (empty($bankAccounts)) {
            $bankAccounts = \App\Models\PaymentBankAccount::active()
                ->ordered()
                ->get()
                ->map(fn ($a) => [
                    'bank_name' => $a->bank_name,
                    'account_number' => $a->account_number,
                    'account_name' => $a->account_name,
                ])
                ->toArray();
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
                usleep(200000); // 0.2 วินาที (ลดจาก 0.5)
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
        $userName = $reading?->facebook_user_name ?? 'คุณ';

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
                usleep(200000); // 0.2 วินาที (ลดจาก 0.5)
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
            usleep(200000); // 0.2 วินาที (ลดจาก 0.5)
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
            $userId, $welcomeFlex, 'แม่หมอจันทรายินดีต้อนรับค่ะ', $replyToken
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
        $userName = $reading?->facebook_user_name ?? 'คุณ';
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

        return $lineService->sendFlexWithReplyFallback(
            $userId, $questionFlex, "📝 เลือกหมวดคำถามข้อที่ {$questionNumber}", $replyToken
        );
    }

    // ============================================================
    // 🆕 LINE Flex Handlers — ข้อความสวยงามทุกจุด
    // ============================================================

    /**
     * ส่ง Flex ยืนยันดูดวง (awaiting_confirmation)
     */
    protected function sendLineConfirmationResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? 'คุณ';

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
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? 'คุณ';
        $remaining = $result['remaining'] ?? 0;
        $used = $result['used'] ?? 0;
        $total = $result['total'] ?? 1;
        $isUnlimited = $result['is_unlimited'] ?? ($remaining >= 99);
        $deepPrice = $this->getReadingPrice();
        $deepEnabled = $this->settings->isDeepReadingEnabled();

        $flex = $lineService->buildCheckRemainingFlexMessage($userName, $remaining, $used, $total, $deepPrice, $deepEnabled, $isUnlimited);

        return $lineService->sendFlexWithReplyFallback($userId, $flex, "📊 สิทธิ์คงเหลือ: {$remaining}", $replyToken);
    }

    /**
     * ส่ง Flex ปฏิเสธ/ยกเลิก (declined, cancelled)
     */
    protected function sendLineDeclinedResponse(LineFortuneService $lineService, string $userId, array $result, string $type = 'declined', ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? 'คุณ';
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
        $userName = $reading?->facebook_user_name ?? 'คุณ';

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
                usleep(200000);
            } catch (\Exception $e) {
                Log::warning('FortuneChannelManager: ส่ง chart image ไม่สำเร็จ (view_reading)', ['error' => $e->getMessage()]);
            }
        }

        // ดูดวงละเอียด → ส่ง Flex คำทำนาย + Thank You
        if ($action === 'view_reading_deep' && ! empty($reading?->deep_response)) {
            // ส่ง Fortune Flex
            $fortuneFlex = $lineService->buildFortuneFlexMessage($reading->deep_response, $userName, $reading->bill_reference);
            $lineService->sendRichMessage($userId, ['alt_text' => '🌟 คำทำนายเชิงลึก', 'contents' => $fortuneFlex]);
            usleep(200000);

            // ส่ง Thank You
            $thankYouFlex = $lineService->buildThankYouFlexMessage($userName);

            return $lineService->sendRichMessage($userId, ['alt_text' => '🙏 ขอบคุณค่ะ', 'contents' => $thankYouFlex]);
        }

        // ดูดวงพื้นฐาน → ส่ง Flex คำทำนาย
        if (! empty($reading?->basic_response)) {
            $fortuneFlex = $lineService->buildFortuneFlexMessage($reading->basic_response, $userName);

            return $lineService->sendFlexWithReplyFallback($userId, $fortuneFlex, '🔮 คำทำนายล่าสุด', $replyToken);
        }

        // Fallback
        return $lineService->sendMessageWithReplyFallback($userId, $result['message'] ?? 'ไม่พบคำทำนาย', $replyToken);
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
        $flex = [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => '#6B46C1']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'md',
                'contents' => [
                    ['type' => 'text', 'text' => '🔮', 'size' => 'lg', 'flex' => 0],
                    ['type' => 'text', 'text' => 'แม่หมอจันทราดูดวง', 'color' => '#FFFFFF', 'size' => 'md', 'weight' => 'bold', 'flex' => 1, 'paddingStart' => 'md'],
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
                    ['type' => 'button', 'style' => 'primary', 'color' => '#6B46C1', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '🔮 ดูดวง', 'text' => 'ดูดวง']],
                    ['type' => 'button', 'style' => 'secondary', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '📊 เช็คสิทธิ์', 'text' => 'เช็คสิทธิ์']],
                ],
            ],
        ];

        return $lineService->sendFlexWithReplyFallback($userId, $flex, '🔮 ข้อความจากแม่หมอจันทรา', $replyToken);
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
        // Job จะ: confirmPayment → สร้าง chart → สร้างคำทำนาย 3 ข้อ → ส่ง Messenger → save DB
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
