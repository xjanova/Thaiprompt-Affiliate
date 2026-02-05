<?php

namespace App\Services;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\PaymentBankAccount;
use App\Models\UniquePaymentAmount;
use App\Models\SmsPaymentNotification;
use Illuminate\Support\Facades\Log;

/**
 * Fortune Conversation Service
 *
 * จัดการ conversational flow สำหรับดูดวงผ่าน Facebook Messenger
 *
 * Flow:
 * 1. User พิมพ์ "ดูดวง" → ดึงโปรไฟล์ + ทำนายพื้นฐานฟรี
 * 2. เสนอดูดวงละเอียด 49 บาท → ถามวันเกิด + 3 คำถาม
 * 3. สร้างบิล + unique amount → แสดงบัญชีธนาคาร
 * 4. SMS match → ส่งคำทำนายละเอียดผ่าน Messenger
 */
class FortuneConversationService
{
    protected FortuneTellingSetting $settings;
    protected FortuneAIService $aiService;
    protected FacebookWebhookService $facebookService;

    /**
     * ราคาดูดวงละเอียด (บาท)
     */
    public const DEEP_READING_PRICE = 49;

    /**
     * จำนวนคำถามที่ต้องการ
     */
    public const REQUIRED_QUESTIONS = 3;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
        $this->aiService = new FortuneAIService($this->settings);
        $this->facebookService = new FacebookWebhookService($this->settings);
    }

    /**
     * ประมวลผลข้อความจาก Messenger
     *
     * @param string $facebookUserId
     * @param string $messageText
     * @param array|null $userProfile
     * @return array ผลลัพธ์ ['action' => '...', 'message' => '...', 'reading' => FortuneReading|null]
     */
    public function processMessage(string $facebookUserId, string $messageText, ?array $userProfile = null): array
    {
        // ตรวจสอบว่ามี conversation ที่กำลังดำเนินอยู่หรือไม่
        $activeReading = FortuneReading::findActiveConversation($facebookUserId);

        if ($activeReading) {
            return $this->continueConversation($activeReading, $messageText, $userProfile);
        }

        // ตรวจสอบว่าเป็นคำขอดูดวงหรือไม่
        if ($this->isFortuneRequest($messageText)) {
            return $this->startNewConversation($facebookUserId, $messageText, $userProfile);
        }

        // ไม่ใช่คำขอดูดวง
        return [
            'action' => 'help',
            'message' => $this->getHelpMessage(),
            'reading' => null,
        ];
    }

    /**
     * เริ่มต้น conversation ใหม่ - ทำนายพื้นฐานฟรี
     *
     * @param string $facebookUserId
     * @param string $messageText
     * @param array|null $userProfile
     * @return array
     */
    protected function startNewConversation(string $facebookUserId, string $messageText, ?array $userProfile = null): array
    {
        // ดึงโปรไฟล์ถ้ายังไม่มี
        if (empty($userProfile)) {
            $userProfile = $this->facebookService->getUserProfile($facebookUserId);
        }

        // สร้าง FortuneReading ใหม่
        $reading = FortuneReading::create([
            'facebook_user_id' => $facebookUserId,
            'facebook_user_name' => $userProfile['name'] ?? null,
            'user_profile' => $userProfile,
            'questions' => [$messageText],
            'reading_type' => 'basic',
            'conversation_status' => FortuneReading::STATUS_NEW,
            'response_type' => 'private_message',
        ]);

        try {
            // ทำนายพื้นฐานฟรี
            $basicPrompt = $this->buildBasicPrompt($userProfile, $messageText);
            $aiResult = $this->aiService->generateFortuneTelling(
                [$messageText],
                $userProfile,
                null,
                $basicPrompt,
                'basic'
            );

            // บันทึกคำทำนายพื้นฐาน
            $reading->saveBasicReading(
                $aiResult['response'],
                $aiResult['provider'],
                $aiResult['model'],
                $aiResult['tokens_used']
            );

            // สร้างข้อความเชิญชวนดูดวงละเอียด
            $upsellMessage = $this->getUpsellMessage($userProfile['name'] ?? 'คุณ');

            // เพิ่มเลขที่บิลอ้างอิงท้ายคำทำนาย
            $billRefMessage = $this->getBillReferenceMessage($reading->bill_reference);

            return [
                'action' => 'basic_done',
                'message' => $aiResult['response'] . "\n\n" . $billRefMessage . "\n\n" . $upsellMessage,
                'reading' => $reading,
                'show_quick_replies' => true,
            ];

        } catch (\Exception $e) {
            Log::error('Fortune Conversation: ทำนายพื้นฐานล้มเหลว', [
                'facebook_user_id' => $facebookUserId,
                'error' => $e->getMessage(),
            ]);

            return [
                'action' => 'error',
                'message' => "ขออภัยค่ะ เกิดข้อผิดพลาดในการทำนาย กรุณาลองใหม่อีกครั้ง 🙏",
                'reading' => $reading,
            ];
        }
    }

    /**
     * ดำเนินการต่อ conversation ที่มีอยู่
     *
     * @param FortuneReading $reading
     * @param string $messageText
     * @param array|null $userProfile
     * @return array
     */
    protected function continueConversation(FortuneReading $reading, string $messageText, ?array $userProfile = null): array
    {
        $status = $reading->conversation_status;

        // ตรวจสอบว่าต้องการยกเลิกหรือไม่
        if ($this->isCancelRequest($messageText)) {
            $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);
            return [
                'action' => 'cancelled',
                'message' => "ยกเลิกแล้วค่ะ หากต้องการดูดวงใหม่ พิมพ์ 'ดูดวง' ได้เลยนะคะ 🔮",
                'reading' => $reading,
            ];
        }

        return match ($status) {
            FortuneReading::STATUS_BASIC_DONE => $this->handleAfterBasic($reading, $messageText),
            FortuneReading::STATUS_COLLECTING_BIRTHDATE => $this->handleBirthdateInput($reading, $messageText),
            FortuneReading::STATUS_COLLECTING_QUESTIONS => $this->handleQuestionInput($reading, $messageText),
            FortuneReading::STATUS_PENDING_PAYMENT => $this->handlePendingPayment($reading, $messageText),
            default => [
                'action' => 'unknown',
                'message' => $this->getHelpMessage(),
                'reading' => $reading,
            ],
        };
    }

    /**
     * จัดการหลังทำนายพื้นฐาน - ถามว่าต้องการดูดวงละเอียดไหม
     *
     * @param FortuneReading $reading
     * @param string $messageText
     * @return array
     */
    protected function handleAfterBasic(FortuneReading $reading, string $messageText): array
    {
        // ตรวจสอบว่าต้องการดูดวงละเอียดหรือไม่
        if ($this->isDeepReadingAccepted($messageText)) {
            $reading->updateConversationStatus(FortuneReading::STATUS_COLLECTING_BIRTHDATE);

            return [
                'action' => 'collecting_birthdate',
                'message' => $this->getBirthdateRequestMessage(),
                'reading' => $reading,
            ];
        }

        // ไม่ต้องการ → จบ conversation
        $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

        return [
            'action' => 'declined',
            'message' => "ไม่เป็นไรค่ะ หากต้องการดูดวงอีกครั้ง พิมพ์ 'ดูดวง' ได้เลยนะคะ ✨\n\nอย่าลืมส่งต่อให้เพื่อนๆ มาลองดูดวงด้วยกันนะคะ 🔮",
            'reading' => $reading,
        ];
    }

    /**
     * จัดการ input วันเกิด
     *
     * @param FortuneReading $reading
     * @param string $messageText
     * @return array
     */
    protected function handleBirthdateInput(FortuneReading $reading, string $messageText): array
    {
        $birthDate = $this->parseBirthDate($messageText);

        if (!$birthDate) {
            return [
                'action' => 'invalid_birthdate',
                'message' => "ขอโทษค่ะ ไม่เข้าใจวันเกิด กรุณาพิมพ์ใหม่ในรูปแบบ:\n\n📅 วัน/เดือน/ปี เช่น 15/08/1990\n📅 หรือ 15 สิงหาคม 2533\n\nพิมพ์ 'ยกเลิก' หากต้องการยกเลิก",
                'reading' => $reading,
            ];
        }

        // บันทึกวันเกิด
        $reading->update([
            'birth_date' => $birthDate,
            'conversation_status' => FortuneReading::STATUS_COLLECTING_QUESTIONS,
        ]);
        $reading->setConversationState('collected_questions', []);

        return [
            'action' => 'collecting_questions',
            'message' => $this->getQuestionsRequestMessage($reading->facebook_user_name ?? 'คุณ', $birthDate),
            'reading' => $reading,
        ];
    }

    /**
     * จัดการ input คำถาม
     *
     * @param FortuneReading $reading
     * @param string $messageText
     * @return array
     */
    protected function handleQuestionInput(FortuneReading $reading, string $messageText): array
    {
        // พยายาม parse คำถามหลายข้อจากข้อความเดียว
        $questions = $this->parseMultipleQuestions($messageText);

        foreach ($questions as $question) {
            if (!empty(trim($question))) {
                $reading->addQuestion(trim($question));
            }
        }

        $collectedQuestions = $reading->getCollectedQuestions();
        $questionCount = count($collectedQuestions);

        if ($questionCount < self::REQUIRED_QUESTIONS) {
            $remaining = self::REQUIRED_QUESTIONS - $questionCount;
            return [
                'action' => 'need_more_questions',
                'message' => "✅ รับคำถามแล้ว {$questionCount} ข้อ\n\nกรุณาพิมพ์คำถามอีก {$remaining} ข้อค่ะ\n\nหรือพิมพ์ทุกคำถามในครั้งเดียว คั่นด้วย , หรือขึ้นบรรทัดใหม่",
                'reading' => $reading,
            ];
        }

        // ได้ครบ 3 คำถามแล้ว → สร้างบิลรอชำระ
        return $this->createPaymentBill($reading, $collectedQuestions);
    }

    /**
     * จัดการเมื่อรอชำระเงิน
     *
     * @param FortuneReading $reading
     * @param string $messageText
     * @return array
     */
    protected function handlePendingPayment(FortuneReading $reading, string $messageText): array
    {
        // ตรวจสอบว่าต้องการดูบัญชีอีกครั้งหรือไม่
        if ($this->isBankAccountRequest($messageText)) {
            return [
                'action' => 'show_bank_accounts',
                'message' => $this->getBankAccountsMessage($reading),
                'reading' => $reading,
            ];
        }

        // ตรวจสอบยอดเงิน
        $uniqueAmount = $reading->uniquePaymentAmount;
        if ($uniqueAmount && $uniqueAmount->expires_at < now()) {
            // หมดอายุแล้ว → สร้างใหม่
            $questions = $reading->getCollectedQuestions();
            return $this->createPaymentBill($reading, $questions);
        }

        return [
            'action' => 'waiting_payment',
            'message' => "💳 รอรับชำระเงินอยู่ค่ะ\n\nยอดที่ต้องโอน: ฿" . number_format($reading->amount_paid, 2) . "\n\n" .
                        "เมื่อโอนแล้วระบบจะตรวจสอบอัตโนมัติและส่งคำทำนายให้ทันทีค่ะ ✨\n\n" .
                        "พิมพ์ 'บัญชี' เพื่อดูบัญชีธนาคารอีกครั้ง\n" .
                        "พิมพ์ 'ยกเลิก' หากต้องการยกเลิก",
            'reading' => $reading,
        ];
    }

    /**
     * สร้างบิลรอชำระเงิน
     *
     * @param FortuneReading $reading
     * @param array $questions
     * @return array
     */
    protected function createPaymentBill(FortuneReading $reading, array $questions): array
    {
        try {
            // สร้าง unique amount
            $uniqueAmount = UniquePaymentAmount::generate(
                self::DEEP_READING_PRICE,
                $reading->id,
                'fortune_reading',
                60  // หมดอายุใน 60 นาที
            );

            if (!$uniqueAmount) {
                return [
                    'action' => 'error',
                    'message' => "ขออภัยค่ะ ไม่สามารถสร้างบิลได้ในขณะนี้ กรุณาลองใหม่อีกครั้ง 🙏",
                    'reading' => $reading,
                ];
            }

            // อัพเดท reading
            $reading->update([
                'questions' => $questions,
            ]);
            $reading->setPendingPayment($uniqueAmount);

            // สร้างข้อความสรุป + บัญชีธนาคาร
            $message = $this->getPaymentSummaryMessage($reading, $questions, $uniqueAmount);

            Log::info('Fortune Conversation: สร้างบิลรอชำระ', [
                'reading_id' => $reading->id,
                'unique_amount' => $uniqueAmount->unique_amount,
                'facebook_user_id' => $reading->facebook_user_id,
            ]);

            return [
                'action' => 'pending_payment',
                'message' => $message,
                'reading' => $reading,
            ];

        } catch (\Exception $e) {
            Log::error('Fortune Conversation: สร้างบิลล้มเหลว', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'action' => 'error',
                'message' => "ขออภัยค่ะ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง 🙏",
                'reading' => $reading,
            ];
        }
    }

    /**
     * ประมวลผลเมื่อชำระเงินสำเร็จ
     *
     * @param FortuneReading $reading
     * @param SmsPaymentNotification|null $notification
     * @return array
     */
    public function processPaymentConfirmed(FortuneReading $reading, ?SmsPaymentNotification $notification = null): array
    {
        try {
            // ยืนยันการชำระเงิน
            $reading->confirmPayment($notification);

            // ทำนายละเอียด
            $questions = $reading->questions ?? $reading->getCollectedQuestions();
            $userProfile = $reading->user_profile;
            $birthDate = $reading->birth_date?->format('Y-m-d');

            $deepPrompt = $this->buildDeepPrompt($userProfile, $questions, $birthDate);
            $aiResult = $this->aiService->generateFortuneTelling(
                $questions,
                $userProfile,
                null,
                $deepPrompt,
                'deep',
                $birthDate
            );

            // บันทึกคำทำนายละเอียด
            $reading->saveDeepReading(
                $aiResult['response'],
                $aiResult['provider'],
                $aiResult['model'],
                $aiResult['tokens_used']
            );

            // สร้างข้อความขอบคุณพร้อมเลขที่บิล
            $thankYouMessage = $this->getThankYouMessage(
                $reading->facebook_user_name ?? 'คุณ',
                $reading->bill_reference
            );

            Log::info('Fortune Conversation: ทำนายละเอียดสำเร็จ', [
                'reading_id' => $reading->id,
                'tokens_used' => $aiResult['tokens_used'],
            ]);

            return [
                'action' => 'completed',
                'message' => $aiResult['response'] . "\n\n" . $thankYouMessage,
                'reading' => $reading,
            ];

        } catch (\Exception $e) {
            Log::error('Fortune Conversation: ทำนายละเอียดล้มเหลว', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'action' => 'error',
                'message' => "ขออภัยค่ะ เกิดข้อผิดพลาดในการทำนาย กรุณาติดต่อแอดมิน 🙏",
                'reading' => $reading,
            ];
        }
    }

    // ============================================================
    // Helper Methods - Message Builders
    // ============================================================

    /**
     * สร้างข้อความ upsell ชวนดูดวงละเอียด
     */
    protected function getUpsellMessage(string $name): string
    {
        $price = self::DEEP_READING_PRICE;
        return "═══════════════════════\n" .
               "🌟 *ดูดวงละเอียด* 🌟\n" .
               "═══════════════════════\n\n" .
               "คุณ{$name} อยากรู้ลึกกว่านี้ไหมคะ?\n\n" .
               "📍 บอกวันเดือนปีเกิด\n" .
               "📍 ถามได้ 3 คำถาม\n" .
               "📍 เพียง {$price} บาท\n\n" .
               "ตอบ 'ต้องการ' หรือ 'เอา' เพื่อเริ่มต้นค่ะ ✨\n" .
               "ตอบ 'ไม่' หากไม่ต้องการ";
    }

    /**
     * สร้างข้อความขอวันเกิด
     */
    protected function getBirthdateRequestMessage(): string
    {
        return "🎂 *กรุณาบอกวันเดือนปีเกิดค่ะ*\n\n" .
               "📅 พิมพ์ในรูปแบบ: วัน/เดือน/ปี\n" .
               "📅 ตัวอย่าง: 15/08/1990 หรือ 15/08/2533\n" .
               "📅 หรือพิมพ์: 15 สิงหาคม 2533\n\n" .
               "ข้อมูลนี้จะช่วยให้คำทำนายแม่นยำขึ้นค่ะ ✨";
    }

    /**
     * สร้างข้อความขอคำถาม
     */
    protected function getQuestionsRequestMessage(string $name, string $birthDate): string
    {
        $formattedDate = $this->formatThaiDate($birthDate);
        $count = self::REQUIRED_QUESTIONS;

        return "✅ รับวันเกิดแล้ว: {$formattedDate}\n\n" .
               "═══════════════════════\n" .
               "🔮 *ตั้งคำถาม {$count} ข้อ*\n" .
               "═══════════════════════\n\n" .
               "คุณ{$name} ต้องการถามเรื่องอะไรบ้างคะ?\n\n" .
               "💡 ตัวอย่างคำถาม:\n" .
               "• การเงินปีนี้เป็นอย่างไร\n" .
               "• ความรักจะสมหวังไหม\n" .
               "• การงานจะเจริญก้าวหน้าไหม\n\n" .
               "📝 พิมพ์ทีละข้อ หรือพิมพ์ทั้ง {$count} ข้อในครั้งเดียว\n" .
               "(คั่นด้วย , หรือขึ้นบรรทัดใหม่)";
    }

    /**
     * สร้างข้อความสรุป + บัญชีธนาคาร
     */
    protected function getPaymentSummaryMessage(FortuneReading $reading, array $questions, UniquePaymentAmount $uniqueAmount): string
    {
        $name = $reading->facebook_user_name ?? 'คุณ';
        $birthDate = $reading->birth_date ? $this->formatThaiDate($reading->birth_date->format('Y-m-d')) : '';
        $amount = number_format($uniqueAmount->unique_amount, 2);
        $expiresAt = $uniqueAmount->expires_at->format('H:i');

        $billRef = $reading->bill_reference;

        $message = "═══════════════════════\n";
        $message .= "📋 *สรุปคำถาม*\n";
        $message .= "═══════════════════════\n\n";
        $message .= "🔖 เลขที่บิล: {$billRef}\n";
        $message .= "👤 ชื่อ: {$name}\n";
        $message .= "🎂 วันเกิด: {$birthDate}\n\n";
        $message .= "❓ คำถาม:\n";

        foreach ($questions as $i => $q) {
            $num = $i + 1;
            $message .= "{$num}. {$q}\n";
        }

        $message .= "\n═══════════════════════\n";
        $message .= "💰 *ยอดชำระ: ฿{$amount}*\n";
        $message .= "⏰ หมดอายุ: {$expiresAt} น.\n";
        $message .= "═══════════════════════\n\n";

        // เพิ่มบัญชีธนาคาร
        $message .= $this->getBankAccountsListMessage();

        $message .= "\n⚠️ *สำคัญ*: กรุณาโอนยอด ฿{$amount} (ตรงตามทศนิยม)\n";
        $message .= "เพื่อให้ระบบตรวจสอบอัตโนมัติได้ถูกต้อง\n\n";
        $message .= "เมื่อโอนแล้วรอสักครู่ ระบบจะส่งคำทำนายให้ทันทีค่ะ ✨";

        return $message;
    }

    /**
     * ดึงบัญชีธนาคารที่เปิด SMS Checker
     */
    protected function getBankAccountsListMessage(): string
    {
        $accounts = PaymentBankAccount::active()
            ->smsCheckerEnabled()
            ->ordered()
            ->get();

        if ($accounts->isEmpty()) {
            // ถ้าไม่มีบัญชีที่เปิด SMS Checker ให้ดึงบัญชีที่ active ทั้งหมด
            $accounts = PaymentBankAccount::active()->ordered()->get();
        }

        if ($accounts->isEmpty()) {
            return "🏦 กรุณาติดต่อแอดมินเพื่อขอบัญชีธนาคาร\n";
        }

        $message = "🏦 *บัญชีที่รับโอน*:\n\n";

        foreach ($accounts as $account) {
            $message .= "📌 {$account->bank_name}\n";
            $message .= "   เลขบัญชี: {$account->account_number}\n";
            $message .= "   ชื่อ: {$account->account_name}\n";

            if ($account->hasPromptpay()) {
                $message .= "   พร้อมเพย์: {$account->promptpay_id}\n";
            }

            $message .= "\n";
        }

        return $message;
    }

    /**
     * สร้างข้อความบัญชีธนาคาร (สำหรับขอดูซ้ำ)
     */
    protected function getBankAccountsMessage(FortuneReading $reading): string
    {
        $amount = number_format($reading->amount_paid, 2);
        $message = "💰 *ยอดชำระ: ฿{$amount}*\n\n";
        $message .= $this->getBankAccountsListMessage();
        $message .= "⚠️ กรุณาโอนยอด ฿{$amount} ตรงตามทศนิยมค่ะ";

        return $message;
    }

    /**
     * สร้างข้อความขอบคุณหลังชำระเงิน
     *
     * @param string $name ชื่อผู้ใช้
     * @param string|null $billReference เลขที่บิลอ้างอิง
     */
    protected function getThankYouMessage(string $name, ?string $billReference = null): string
    {
        $billInfo = $billReference ? "🔖 เลขที่บิล: {$billReference}\n\n" : "";

        return "═══════════════════════\n" .
               "🙏 *ขอบคุณที่ใช้บริการค่ะ*\n" .
               "═══════════════════════\n\n" .
               $billInfo .
               "คุณ{$name} หวังว่าคำทำนายจะเป็นประโยชน์นะคะ ✨\n\n" .
               "📢 อย่าลืมส่งต่อให้เพื่อนๆ มาลองดูดวงด้วยกันนะคะ\n" .
               "พิมพ์ 'ดูดวง' เมื่อต้องการดูดวงอีกครั้ง 🔮";
    }

    /**
     * สร้างข้อความ help
     */
    protected function getHelpMessage(): string
    {
        return "🔮 *ระบบดูดวง AI*\n\n" .
               "พิมพ์ 'ดูดวง' เพื่อเริ่มดูดวงฟรี\n" .
               "หลังจากนั้นสามารถเลือกดูดวงละเอียดได้ค่ะ ✨";
    }

    /**
     * สร้างข้อความเลขที่บิลอ้างอิง
     *
     * @param string|null $billReference
     * @return string
     */
    protected function getBillReferenceMessage(?string $billReference): string
    {
        if (empty($billReference)) {
            return '';
        }

        return "═══════════════════════\n" .
               "🔖 *เลขที่บิลอ้างอิง*\n" .
               "📌 {$billReference}\n" .
               "═══════════════════════\n" .
               "(เก็บไว้อ้างอิงหากมีปัญหาค่ะ)";
    }

    // ============================================================
    // Helper Methods - Parsers
    // ============================================================

    /**
     * ตรวจสอบว่าเป็นคำขอดูดวงหรือไม่
     */
    protected function isFortuneRequest(string $text): bool
    {
        $keywords = ['ดูดวง', 'ทำนาย', 'หมอดู', 'ดวง', 'horoscope', 'fortune'];
        $text = mb_strtolower(trim($text));

        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ตรวจสอบว่าต้องการดูดวงละเอียดหรือไม่
     */
    protected function isDeepReadingAccepted(string $text): bool
    {
        $acceptKeywords = ['ต้องการ', 'เอา', 'ใช่', 'ได้', 'ok', 'yes', 'ตกลง', 'โอเค', 'อยาก', 'สนใจ', 'ละเอียด'];
        $text = mb_strtolower(trim($text));

        foreach ($acceptKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ตรวจสอบว่าต้องการยกเลิกหรือไม่
     */
    protected function isCancelRequest(string $text): bool
    {
        $cancelKeywords = ['ยกเลิก', 'cancel', 'ไม่เอา', 'เลิก', 'หยุด', 'stop'];
        $text = mb_strtolower(trim($text));

        foreach ($cancelKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ตรวจสอบว่าต้องการดูบัญชีธนาคารหรือไม่
     */
    protected function isBankAccountRequest(string $text): bool
    {
        $keywords = ['บัญชี', 'ธนาคาร', 'โอน', 'bank', 'account', 'เลขบัญชี'];
        $text = mb_strtolower(trim($text));

        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse วันเกิดจากข้อความ
     */
    protected function parseBirthDate(string $text): ?string
    {
        $text = trim($text);

        // รูปแบบ: dd/mm/yyyy หรือ dd-mm-yyyy
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $text, $matches)) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = (int) $matches[3];

            // แปลง พ.ศ. เป็น ค.ศ. ถ้าจำเป็น
            if ($year > 2400) {
                $year -= 543;
            }

            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        // รูปแบบ: dd เดือนไทย yyyy
        $thaiMonths = [
            'มกราคม' => 1, 'กุมภาพันธ์' => 2, 'มีนาคม' => 3, 'เมษายน' => 4,
            'พฤษภาคม' => 5, 'มิถุนายน' => 6, 'กรกฎาคม' => 7, 'สิงหาคม' => 8,
            'กันยายน' => 9, 'ตุลาคม' => 10, 'พฤศจิกายน' => 11, 'ธันวาคม' => 12,
            'ม.ค.' => 1, 'ก.พ.' => 2, 'มี.ค.' => 3, 'เม.ย.' => 4,
            'พ.ค.' => 5, 'มิ.ย.' => 6, 'ก.ค.' => 7, 'ส.ค.' => 8,
            'ก.ย.' => 9, 'ต.ค.' => 10, 'พ.ย.' => 11, 'ธ.ค.' => 12,
        ];

        foreach ($thaiMonths as $monthName => $monthNum) {
            if (preg_match('/(\d{1,2})\s*' . $monthName . '\s*(\d{4})/', $text, $matches)) {
                $day = (int) $matches[1];
                $year = (int) $matches[2];

                if ($year > 2400) {
                    $year -= 543;
                }

                if (checkdate($monthNum, $day, $year)) {
                    return sprintf('%04d-%02d-%02d', $year, $monthNum, $day);
                }
            }
        }

        return null;
    }

    /**
     * Parse คำถามหลายข้อจากข้อความเดียว
     */
    protected function parseMultipleQuestions(string $text): array
    {
        // แยกด้วย , หรือ newline หรือ ?
        $questions = preg_split('/[,\n\?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        // ตัด whitespace และกรองออก
        $questions = array_map('trim', $questions);
        $questions = array_filter($questions, fn($q) => mb_strlen($q) > 2);

        // ถ้า parse ไม่ได้ ใช้ทั้งข้อความเป็น 1 คำถาม
        if (empty($questions)) {
            return [trim($text)];
        }

        return array_values($questions);
    }

    /**
     * แปลงวันที่เป็นรูปแบบไทย
     */
    protected function formatThaiDate(string $date): string
    {
        try {
            $d = \Carbon\Carbon::parse($date);
            $thaiMonths = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                           'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
            $thaiYear = $d->year + 543;

            return "{$d->day} {$thaiMonths[$d->month]} {$thaiYear}";
        } catch (\Exception $e) {
            return $date;
        }
    }

    // ============================================================
    // Prompt Builders
    // ============================================================

    /**
     * สร้าง prompt สำหรับทำนายพื้นฐาน
     * เป็นหมอดูหญิง ใช้คำแทนตัวว่า "ทางเพจ"
     */
    protected function buildBasicPrompt(?array $userProfile, string $question): string
    {
        $name = $userProfile['name'] ?? 'คุณ';
        $gender = isset($userProfile['gender']) ? ($userProfile['gender'] === 'male' ? 'ชาย' : 'หญิง') : '';
        $genderPrefix = $gender === 'ชาย' ? 'คุณพี่' : ($gender === 'หญิง' ? 'คุณ' : 'คุณ');

        return "คุณเป็นหมอดูหญิงชื่อดัง พูดจาอบอุ่นเป็นกันเอง ใช้คำแทนตัวว่า \"ทางเพจ\" ทำนายแบบฟันธงแต่อ่อนโยน ไม่เกิน 250 คำ

ข้อมูลผู้ขอดูดวง:
- ชื่อ: {$name}
" . ($gender ? "- เพศ: {$gender}\n" : "") . "
คำถาม/เรื่องที่ต้องการดู: {$question}

แนวทางการตอบ:
- เรียกผู้ถามว่า \"{$genderPrefix}{$name}\" อย่างเป็นกันเอง
- ใช้ \"ทางเพจ\" แทนตัวเอง เช่น \"ทางเพจเห็นว่า...\" \"ทางเพจขอบอกตรงๆ ว่า...\"
- ทำนายฟันธง ชัดเจน กล้าบอกทั้งดีและไม่ดี
- ให้คำแนะนำที่ปฏิบัติได้ เช่น สีมงคล ช่วงเวลาที่ดี
- พูดจาอบอุ่นเหมือนพี่สาวที่ห่วงใย
- ท้ายคำทำนายให้บอกว่า \"ถ้าบอกวันเดือนปีเกิดให้ทางเพจ จะได้ทำนายได้ละเอียดและแม่นยำยิ่งขึ้นนะคะ 🎂\"";
    }

    /**
     * สร้าง prompt สำหรับทำนายละเอียด
     * เป็นหมอดูหญิง ใช้คำแทนตัวว่า "ทางเพจ" ทำนายแม่นยำ
     */
    protected function buildDeepPrompt(?array $userProfile, array $questions, ?string $birthDate): string
    {
        $name = $userProfile['name'] ?? 'คุณ';
        $gender = isset($userProfile['gender']) ? ($userProfile['gender'] === 'male' ? 'ชาย' : 'หญิง') : '';
        $genderPrefix = $gender === 'ชาย' ? 'คุณพี่' : ($gender === 'หญิง' ? 'คุณ' : 'คุณ');
        $questionsText = implode("\n", array_map(fn($i, $q) => ($i + 1) . ". {$q}", array_keys($questions), $questions));

        $birthInfo = '';
        if ($birthDate) {
            $birthInfo = "วันเดือนปีเกิด: " . $this->formatThaiDate($birthDate);
        }

        return "คุณเป็นหมอดูหญิงชื่อดังระดับประเทศ ประสบการณ์กว่า 30 ปี เชี่ยวชาญโหราศาสตร์ไทย โหราศาสตร์สากล ไพ่ทาโรต์ เลขศาสตร์ และลายมือ คุณพูดจาอบอุ่นเป็นกันเอง เหมือนพี่สาวที่ห่วงใย ใช้คำแทนตัวว่า \"ทางเพจ\" เสมอ

ข้อมูลผู้ขอดูดวง:
- ชื่อ: {$name} (เรียกว่า \"{$genderPrefix}{$name}\")
" . ($gender ? "- เพศ: {$gender}\n" : "") . "
" . ($birthInfo ? "- {$birthInfo}\n" : "") . "
คำถาม:
{$questionsText}

แนวทางการทำนายอย่างละเอียด:
1. เปิดด้วยการทักทายอบอุ่น เช่น \"{$genderPrefix}{$name} คะ ทางเพจขอทำนายให้อย่างละเอียดเลยนะคะ...\"
2. วิเคราะห์ดวงชะตาจากวันเกิด - ราศี ลัคนา ธาตุประจำตัว ดาวเคราะห์ที่ส่งผล และจุดแข็งจุดอ่อนของดวง
3. ตอบแต่ละคำถามอย่างละเอียด ฟันธง กล้าบอกตรงๆ ทั้งดีและไม่ดี ระบุช่วงเวลาชัดเจน (เช่น \"ช่วงเดือนมีนาคม-เมษายน\" \"ประมาณ 2 สัปดาห์ข้างหน้า\")
4. ให้คำแนะนำที่ปฏิบัติได้จริง:
   - สีมงคลประจำตัว สีที่ควรหลีกเลี่ยง
   - เลขมงคล เลขที่ควรระวัง
   - วันที่เหมาะทำสิ่งสำคัญ
   - สิ่งที่ควรทำและไม่ควรทำ
   - ทิศมงคล สถานที่ที่เป็นมงคล
5. บอกสิ่งที่ควรระวังอย่างตรงไปตรงมา แต่ให้กำลังใจและทางแก้ไขด้วย
6. ปิดท้ายด้วยกำลังใจอบอุ่น เช่น \"ทางเพจเชื่อว่า{$genderPrefix}{$name}จะผ่านพ้นไปได้ด้วยดีค่ะ\" หรือ \"ขอให้โชคดีมีแต่สิ่งดีๆ เข้ามานะคะ 🙏✨\"

ตอบเป็นภาษาไทย อบอุ่น เป็นกันเอง เหมือนพี่สาวที่คอยแนะนำน้อง";
    }
}
