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
            'alt_text' => 'คำทำนายจากทางเพจ',
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

        $amount = $reading->amount_paid ?? FortuneConversationService::DEEP_READING_PRICE;
        $expiresAt = $reading->uniquePaymentAmount?->expires_at?->format('H:i') ?? '--:--';
        $billRef = $reading->bill_reference;

        // ส่ง Payment Flex
        $paymentFlex = $lineService->buildPaymentFlexMessage($bankAccounts, $amount, $expiresAt, $billRef);
        return $lineService->sendRichMessage($userId, [
            'alt_text' => "ยอดชำระ ฿" . number_format($amount, 2),
            'contents' => $paymentFlex,
        ]);
    }

    /**
     * ส่ง Response Help/Welcome (LINE)
     */
    protected function sendLineHelpResponse(LineFortuneService $lineService, string $userId, array $result): bool
    {
        $welcomeFlex = $lineService->buildWelcomeFlexMessage();
        return $lineService->sendRichMessage($userId, [
            'alt_text' => 'ทางเพจยินดีต้อนรับค่ะ',
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
     * @param FortuneReading $reading
     * @return array
     */
    public function sendDeepReadingAfterPayment(FortuneReading $reading): array
    {
        // ประมวลผลคำทำนาย
        $result = $this->conversationService->processPaymentConfirmed($reading);

        $platform = $reading->platform ?? self::PLATFORM_FACEBOOK;
        $userId = $reading->platform_user_id ?? $reading->facebook_user_id;

        // ส่งข้อความตอบกลับ
        $this->sendResponse($platform, $userId, $result);

        return $result;
    }
}
