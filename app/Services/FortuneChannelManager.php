<?php

namespace App\Services;

use App\Contracts\MessagingPlatformInterface;
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
     * ดึง platform instance
     *
     * @param string $platform ชื่อ platform (facebook, line)
     * @return MessagingPlatformInterface|null
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
     * @param string $platform ชื่อ platform
     * @param string $userId User ID ของ platform นั้น
     * @param string $messageText ข้อความ
     * @param array|null $userProfile โปรไฟล์ผู้ใช้ (optional)
     * @param array $extra ข้อมูลเพิ่มเติม (reply_token สำหรับ LINE, etc.)
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
     * @param string $platform
     * @param string $userId
     * @param array $result ผลลัพธ์จาก conversation service
     * @param array $extra ข้อมูลเพิ่มเติม
     * @return bool
     */
    public function sendResponse(string $platform, string $userId, array $result, array $extra = []): bool
    {
        $platformService = $this->getPlatform($platform);
        if (!$platformService) {
            Log::error('FortuneChannelManager: Platform not found', ['platform' => $platform]);
            return false;
        }

        $message = $result['message'] ?? '';
        $action = $result['action'] ?? 'unknown';

        // สำหรับ LINE ใช้ Flex Message ที่สวยงาม
        if ($platform === self::PLATFORM_LINE && $platformService instanceof LineFortuneService) {
            return $this->sendLineResponse($platformService, $userId, $result, $extra);
        }

        // สำหรับ platform อื่นๆ ส่งข้อความธรรมดา
        $options = [];

        // เพิ่ม quick replies ถ้ามี
        if (!empty($result['show_quick_replies'])) {
            $options['quick_replies'] = $this->getQuickReplies($action);
        }

        return $platformService->sendMessage($userId, $message, $options);
    }

    /**
     * ส่ง Response สำหรับ LINE ด้วย Flex Message
     *
     * @param LineFortuneService $lineService
     * @param string $userId
     * @param array $result
     * @param array $extra
     * @return bool
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

        // แยกคำทำนายออกจากข้อความ upsell
        $message = $result['message'] ?? '';
        $parts = explode("═══════════════════════", $message);
        $prediction = trim($parts[0] ?? $message);

        // ส่ง Flex Message คำทำนาย
        $fortuneFlex = $lineService->buildFortuneFlexMessage($prediction, $userName, $billRef);
        $lineService->sendRichMessage($userId, [
            'alt_text' => 'คำทำนายจากแม่หมอจันทรา',
            'contents' => $fortuneFlex,
        ]);

        // ส่ง Flex Message Upsell
        $upsellFlex = $lineService->buildUpsellFlexMessage($userName, FortuneConversationService::DEEP_READING_PRICE);
        return $lineService->sendRichMessage($userId, [
            'alt_text' => 'ดูดวงละเอียด',
            'contents' => $upsellFlex,
        ]);
    }

    /**
     * ส่ง Response เมื่อรอชำระเงิน (LINE)
     */
    protected function sendLinePaymentResponse(LineFortuneService $lineService, string $userId, array $result): bool
    {
        $reading = $result['reading'] ?? null;

        if (!$reading) {
            return $lineService->sendMessage($userId, $result['message'] ?? 'เกิดข้อผิดพลาด');
        }

        // ดึงบัญชีธนาคาร
        $bankAccounts = \App\Models\PaymentBankAccount::active()
            ->smsCheckerEnabled()
            ->ordered()
            ->get()
            ->map(fn($a) => [
                'bank_name' => $a->bank_name,
                'account_number' => $a->account_number,
                'account_name' => $a->account_name,
            ])
            ->toArray();

        if (empty($bankAccounts)) {
            $bankAccounts = \App\Models\PaymentBankAccount::active()
                ->ordered()
                ->get()
                ->map(fn($a) => [
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
            : ((float) $reading->amount_paid ?: FortuneConversationService::DEEP_READING_PRICE);
        $expiresAt = $uniquePayment?->expires_at?->format('H:i') ?? '--:--';
        $billRef = $reading->bill_reference;

        // ส่ง Payment Flex พร้อมยอด unique amount สำหรับเช็คผ่าน SMS payment checker
        $paymentFlex = $lineService->buildPaymentFlexMessage($bankAccounts, $amount, $expiresAt, $billRef);
        return $lineService->sendRichMessage($userId, [
            'alt_text' => "ยอดชำระ ฿" . number_format($amount, 2),
            'contents' => $paymentFlex,
        ]);
    }

    /**
     * ส่ง Response คำทำนายละเอียดทีละคำถาม (LINE)
     *
     * ส่งแต่ละคู่คำถาม-คำทำนายเป็น message แยก
     * ให้ผู้ใช้ได้อ่านทีละข้อ น่าติดตาม
     */
    protected function sendLineDeepReadingResponse(LineFortuneService $lineService, string $userId, array $result): bool
    {
        $deepReadings = $result['deep_readings'] ?? [];
        $thankYou = $result['thank_you'] ?? '';
        $reading = $result['reading'] ?? null;

        // ถ้าไม่มี deep_readings (format เก่า) → ส่งข้อความเดียว
        if (empty($deepReadings)) {
            return $lineService->sendMessage($userId, $result['message'] ?? '');
        }

        // ส่งคำทำนายทีละคำถาม
        foreach ($deepReadings as $dr) {
            $questionNum = $dr['question_number'];
            $question = $dr['question'];
            $answer = $dr['answer'];

            // สร้างข้อความคู่ คำถาม-คำทำนาย
            $message = "═══════════════════════\n";
            $message .= "❓ คำถามที่ {$questionNum}: {$question}\n";
            $message .= "═══════════════════════\n\n";
            $message .= $answer;

            $lineService->sendMessage($userId, $message);

            // หน่วงเวลาเล็กน้อยระหว่างข้อความ (ป้องกัน rate limit)
            usleep(500000); // 0.5 วินาที
        }

        // ส่งข้อความขอบคุณปิดท้าย
        if ($thankYou) {
            $lineService->sendMessage($userId, $thankYou);
        }

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
     *
     * @param string $action
     * @return array
     */
    protected function getQuickReplies(string $action): array
    {
        return match ($action) {
            'basic_done' => [
                ['label' => '✨ ต้องการ', 'text' => 'ต้องการดูดวงละเอียด'],
                ['label' => 'ไม่ต้องการ', 'text' => 'ไม่ต้องการ'],
            ],
            'collecting_birthdate' => [
                ['label' => 'ยกเลิก', 'text' => 'ยกเลิก'],
            ],
            'pending_payment' => [
                ['label' => '🏦 ดูบัญชี', 'text' => 'บัญชี'],
                ['label' => 'ยกเลิก', 'text' => 'ยกเลิก'],
            ],
            default => [],
        };
    }

    /**
     * ตรวจสอบว่า platform รองรับหรือไม่
     *
     * @param string $platform
     * @return bool
     */
    public function isPlatformSupported(string $platform): bool
    {
        return in_array($platform, [self::PLATFORM_FACEBOOK, self::PLATFORM_LINE]);
    }

    /**
     * ดึงรายการ platform ที่รองรับ
     *
     * @return array
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
     *
     * @param FortuneReading $reading
     * @return array
     */
    public function sendDeepReadingAfterPayment(FortuneReading $reading): array
    {
        // ประมวลผลคำทำนาย (ทีละคำถาม)
        $result = $this->conversationService->processPaymentConfirmed($reading);

        $platform = $reading->platform ?? self::PLATFORM_FACEBOOK;
        $userId = $reading->platform_user_id ?? $reading->facebook_user_id;

        // สำหรับ LINE ใช้ sendResponse ที่จัดการ deep_readings อยู่แล้ว
        if ($platform === self::PLATFORM_LINE) {
            $this->sendResponse($platform, $userId, $result);
            return $result;
        }

        // สำหรับ Facebook: ส่งทีละคำถามเช่นกัน
        $deepReadings = $result['deep_readings'] ?? [];
        $platformService = $this->getPlatform($platform);

        if ($platformService && !empty($deepReadings)) {
            foreach ($deepReadings as $dr) {
                $message = "═══════════════════════\n";
                $message .= "❓ คำถามที่ {$dr['question_number']}: {$dr['question']}\n";
                $message .= "═══════════════════════\n\n";
                $message .= $dr['answer'];

                $platformService->sendMessage($userId, $message);
                usleep(500000); // 0.5 วินาที
            }

            // ส่งข้อความขอบคุณ
            $thankYou = $result['thank_you'] ?? '';
            if ($thankYou) {
                $platformService->sendMessage($userId, $thankYou);
            }
        } else {
            // Fallback: ส่งข้อความรวม
            $this->sendResponse($platform, $userId, $result);
        }

        return $result;
    }
}
