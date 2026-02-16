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
                usleep(500000); // 0.5 วินาที
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
     */
    protected function sendLineResponse(LineFortuneService $lineService, string $userId, array $result, array $extra = []): bool
    {
        $action = $result['action'] ?? 'unknown';
        $message = $result['message'] ?? '';
        $reading = $result['reading'] ?? null;

        // ใช้ Flex Message ตาม action
        return match ($action) {
            // ทำนายพื้นฐานเสร็จ → ส่งคำทำนาย + Upsell Flex
            'basic_done' => $this->sendLineBasicDoneResponse($lineService, $userId, $result),

            // รอชำระเงิน → ส่ง Payment Flex
            'pending_payment' => $this->sendLinePaymentResponse($lineService, $userId, $result),

            // ทำนายละเอียดเสร็จ → ส่งทีละคำถาม
            'completed' => $this->sendLineDeepReadingResponse($lineService, $userId, $result),

            // Help → ส่ง Welcome Flex
            'help', 'filtered' => $this->sendLineHelpResponse($lineService, $userId, $result),

            // อื่นๆ → ส่งข้อความธรรมดา
            default => $lineService->sendMessage($userId, $message),
        };
    }

    /**
     * ส่ง Response เมื่อทำนายพื้นฐานเสร็จ (LINE)
     */
    protected function sendLineBasicDoneResponse(LineFortuneService $lineService, string $userId, array $result): bool
    {
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? 'คุณ';
        $billRef = $reading?->bill_reference;

        // ส่ง Birth Chart / Quick Chart ก่อนคำทำนาย (ถ้ามี)
        $chartUrl = $result['chart_image_url'] ?? null;
        if ($chartUrl) {
            try {
                $lineService->sendImage($userId, $chartUrl);
                usleep(500000); // 0.5 วินาที
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
     */
    protected function sendLinePaymentResponse(LineFortuneService $lineService, string $userId, array $result): bool
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
        if ($chartUrl) {
            try {
                $lineService->sendImage($userId, $chartUrl);
                usleep(500000);
            } catch (\Exception $imgErr) {
                Log::warning('FortuneChannelManager LINE: ส่ง chart image ก่อนบิลไม่สำเร็จ', [
                    'error' => $imgErr->getMessage(),
                ]);
            }
        }

        // ส่ง Payment Flex พร้อมยอด unique amount สำหรับเช็คผ่าน SMS payment checker
        $paymentFlex = $lineService->buildPaymentFlexMessage($bankAccounts, $amount, $expiresAt, $billRef);

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
     * ปิดท้ายด้วยการ์ดขอบคุณ + ปุ่ม engagement
     */
    protected function sendLineDeepReadingResponse(LineFortuneService $lineService, string $userId, array $result): bool
    {
        $deepReadings = $result['deep_readings'] ?? [];
        $thankYou = $result['thank_you'] ?? '';
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? 'คุณ';

        // ถ้าไม่มี deep_readings (format เก่า) → ส่งข้อความเดียว
        if (empty($deepReadings)) {
            return $lineService->sendMessage($userId, $result['message'] ?? '');
        }

        // ส่ง Birth Chart ก่อนคำทำนาย (ถ้ามี)
        $chartUrl = $result['chart_image_url'] ?? null;
        if ($chartUrl) {
            try {
                $lineService->sendImage($userId, $chartUrl);
                usleep(500000);
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

            // หน่วงเวลาเล็กน้อยระหว่างข้อความ (ป้องกัน rate limit)
            usleep(500000); // 0.5 วินาที
        }

        // ส่ง Thank You Flex Message ปิดท้าย — มีปุ่ม engagement
        $thankYouFlex = $lineService->buildThankYouFlexMessage($userName);
        $lineService->sendRichMessage($userId, [
            'alt_text' => '🙏 ขอบคุณที่ไว้วางใจค่ะ',
            'contents' => $thankYouFlex,
        ]);

        return true;
    }

    /**
     * ส่ง Response Help/Welcome (LINE)
     */
    protected function sendLineHelpResponse(LineFortuneService $lineService, string $userId, array $result): bool
    {
        $welcomeFlex = $lineService->buildWelcomeFlexMessage();

        return $lineService->sendRichMessage($userId, [
            'alt_text' => 'แม่หมอจันทรายินดีต้อนรับค่ะ',
            'contents' => $welcomeFlex,
        ]);
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
