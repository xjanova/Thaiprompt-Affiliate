<?php

namespace App\Services;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\PaymentBankAccount;
use App\Models\UniquePaymentAmount;
use App\Models\SmsPaymentNotification;
use Illuminate\Support\Facades\Cache;
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
 *
 * Anti-Spam Features:
 * - Rate limiting per user
 * - Prompt injection detection
 * - Repetitive message detection
 * - AI attack pattern detection
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

    /**
     * ความยาวคำถามขั้นต่ำ (ตัวอักษร)
     */
    public const MIN_QUESTION_LENGTH = 5;

    /**
     * ความยาวคำถามสูงสุด (ตัวอักษร)
     */
    public const MAX_QUESTION_LENGTH = 500;

    /**
     * ความยาวข้อความสูงสุดที่รับ (ป้องกัน spam)
     */
    public const MAX_MESSAGE_LENGTH = 1000;

    /**
     * Rate Limiting: จำนวนข้อความสูงสุดต่อนาที
     */
    public const MAX_MESSAGES_PER_MINUTE = 10;

    /**
     * Rate Limiting: จำนวนข้อความสูงสุดต่อชั่วโมง
     */
    public const MAX_MESSAGES_PER_HOUR = 60;

    /**
     * Rate Limiting: จำนวน AI calls สูงสุดต่อวัน (ต่อ user) - fallback ถ้าไม่ได้ตั้งค่า
     * ปกติใช้ค่าจาก settings.max_free_readings แทน
     */
    public const MAX_AI_CALLS_PER_DAY = 1;

    /**
     * จำนวนข้อความซ้ำที่ยอมรับได้
     */
    public const MAX_REPETITIVE_MESSAGES = 3;

    /**
     * Prompt Injection Patterns - คำสั่งที่พยายาม manipulate AI
     */
    protected const PROMPT_INJECTION_PATTERNS = [
        // System prompt manipulation
        'ignore previous', 'ignore above', 'disregard previous', 'forget previous',
        'ignore all instructions', 'ignore your instructions', 'new instructions',
        'system prompt', 'system:', 'assistant:', 'user:', '[INST]', '[/INST]',
        'you are now', 'pretend you are', 'act as', 'roleplay as',
        'from now on', 'starting now', 'override', 'bypass',
        // Thai variants
        'ลืมคำสั่งก่อนหน้า', 'เพิกเฉยคำสั่ง', 'คำสั่งใหม่', 'เปลี่ยนบทบาท',
        'แกล้งทำเป็น', 'สมมติว่าคุณ', 'ตั้งแต่ตอนนี้',
        // Jailbreak attempts
        'jailbreak', 'dan mode', 'developer mode', 'unrestricted mode',
        'no restrictions', 'without restrictions', 'unlock', 'enable all',
        // Output manipulation
        'output:', 'print:', 'echo:', 'return:', 'respond with:',
        'say exactly', 'repeat after me', 'copy this',
        // API/prompt reveal attempts
        'show prompt', 'reveal prompt', 'what is your prompt', 'show instructions',
        'what are your instructions', 'display system', 'show system message',
    ];

    /**
     * AI Attack Patterns - รูปแบบการโจมตีจาก AI อื่น
     */
    protected const AI_ATTACK_PATTERNS = [
        // Structured prompts from other AIs
        'as an ai', 'as a language model', 'as an assistant',
        'i am an ai', 'i am a bot', 'i am chatgpt', 'i am claude',
        'generate a response', 'create a prompt', 'write a prompt',
        'test your capabilities', 'test your limits', 'stress test',
        // Automated testing patterns
        'benchmark', 'evaluation:', 'test case:', 'scenario:',
        'input:', 'expected output:', 'actual output:',
        // Mass generation requests
        'generate 100', 'create 50', 'list all', 'enumerate all',
        'give me every', 'tell me everything about',
    ];

    /**
     * Meaningless/Random Chat Patterns - ถามเรื่อยเปื่อย
     *
     * ⚠️ ปรับให้ไม่เข้มงวดเกินไป - ให้ผ่านไปตอบเป็นข้อความช่วยเหลือแทน
     * ไม่บล็อก แต่ให้ตอบเป็นข้อความแนะนำวิธีใช้
     */
    protected const MEANINGLESS_PATTERNS = [
        // Random letters/numbers only
        '/^[a-z]{1,3}$/i',
        '/^[ก-ฮ]{1,2}$/u',
        '/^[0-9]{1,3}$/u',
        // Just punctuation
        '/^[\s\.\,\!\?\-\_]+$/',
        // Testing messages
        '/^(test|เทส|123|abc)$/ui',
    ];

    /**
     * คำที่เกี่ยวข้องกับการดูดวง (ใช้ตรวจจับคำถามที่เกี่ยวข้อง)
     */
    protected const FORTUNE_RELATED_KEYWORDS = [
        // หัวข้อดูดวง
        'ความรัก', 'แฟน', 'คู่ครอง', 'เนื้อคู่', 'คนรัก', 'สามี', 'ภรรยา', 'แต่งงาน', 'หย่า', 'เลิกกัน', 'รักซ้อน',
        'การงาน', 'งาน', 'ทำงาน', 'อาชีพ', 'เปลี่ยนงาน', 'หางาน', 'เจ้านาย', 'ลูกน้อง', 'เลื่อนตำแหน่ง', 'ถูกไล่ออก',
        'การเงิน', 'เงิน', 'รายได้', 'หนี้', 'รวย', 'จน', 'ลงทุน', 'หุ้น', 'ค้าขาย', 'ขายของ', 'กำไร', 'ขาดทุน',
        'สุขภาพ', 'ป่วย', 'โรค', 'อุบัติเหตุ', 'เจ็บ', 'ตาย', 'อายุยืน',
        'การเรียน', 'สอบ', 'เรียน', 'มหาวิทยาลัย', 'สอบติด', 'สอบตก',
        'โชคลาภ', 'หวย', 'ลอตเตอรี่', 'เลขเด็ด', 'โชค', 'ลาภ', 'ถูกหวย',
        'ครอบครัว', 'พ่อแม่', 'ลูก', 'พี่น้อง', 'ญาติ',
        'ย้ายบ้าน', 'ซื้อบ้าน', 'ซื้อรถ', 'เดินทาง', 'ไปต่างประเทศ',
        // คำเกี่ยวกับดวง
        'ดวง', 'ดูดวง', 'ทำนาย', 'หมอดู', 'ราศี', 'ลัคนา', 'ไพ่', 'ทาโรต์', 'เลขศาสตร์', 'ลายมือ',
        'ปีชง', 'ปีนักษัตร', 'ธาตุ', 'ดาว', 'เคราะห์', 'มงคล', 'อัปมงคล',
        'วันเกิด', 'เดือนเกิด', 'ปีเกิด',
        // คำถามทั่วไปเกี่ยวกับอนาคต
        'อนาคต', 'จะเป็นยังไง', 'จะดีไหม', 'จะสำเร็จไหม', 'จะรวยไหม', 'จะมีแฟนไหม',
        'เมื่อไหร่', 'ช่วงไหน', 'ปีนี้', 'ปีหน้า', 'เดือนนี้', 'เดือนหน้า',
    ];

    /**
     * คำที่ไม่เกี่ยวกับดูดวง (off-topic)
     */
    protected const OFF_TOPIC_KEYWORDS = [
        // เขียนโค้ด/โปรแกรม
        'code', 'โค้ด', 'เขียนโปรแกรม', 'programming', 'javascript', 'python', 'php', 'html', 'css',
        'function', 'class', 'variable', 'array', 'loop', 'if else', 'database', 'sql', 'api',
        // คำถามทั่วไป
        'สูตรอาหาร', 'ทำอาหาร', 'วิธีทำ', 'recipe',
        'แนะนำร้าน', 'ร้านอาหาร', 'ร้านกาแฟ', 'โรงแรม',
        'แปลภาษา', 'translate', 'แปลให้หน่อย',
        'เล่าเรื่อง', 'นิทาน', 'เรื่องผี', 'เรื่องตลก', 'มุก', 'joke',
        'คำนวณ', 'บวก', 'ลบ', 'คูณ', 'หาร', 'เปอร์เซ็นต์', 'calculate',
        'ดาวน์โหลด', 'download', 'ลิงค์', 'link', 'url',
        'hack', 'แฮก', 'crack', 'เจาะระบบ', 'password', 'รหัสผ่าน',
        'เขียนบทความ', 'เขียนเรียงความ', 'รายงาน', 'การบ้าน', 'homework',
    ];

    /**
     * ตัวอย่างคำถามดูดวง (แสดงให้ผู้ใช้ดู)
     */
    protected const EXAMPLE_QUESTIONS = [
        'ความรัก' => [
            'ปีนี้จะมีคู่ครองไหม',
            'แฟนรักจริงหรือเปล่า',
            'เมื่อไหร่จะได้แต่งงาน',
        ],
        'การงาน' => [
            'ควรเปลี่ยนงานไหม',
            'ปีนี้จะได้เลื่อนตำแหน่งไหม',
            'ธุรกิจจะรุ่งหรือร่วง',
        ],
        'การเงิน' => [
            'จะรวยเมื่อไหร่',
            'ลงทุนตอนนี้ดีไหม',
            'หนี้จะหมดเมื่อไหร่',
        ],
        'สุขภาพ' => [
            'ปีนี้สุขภาพจะเป็นอย่างไร',
            'ควรระวังเรื่องอะไร',
            'จะอายุยืนไหม',
        ],
        'โชคลาภ' => [
            'ดวงโชคลาภปีนี้เป็นอย่างไร',
            'จะถูกหวยไหม',
            'เลขมงคลของฉันคือเลขอะไร',
        ],
    ];

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
        // Pre-filter พร้อม Rate Limiting: ตรวจจับ spam/attack ก่อนประมวลผล
        $filterResult = $this->preFilterWithRateLimit($facebookUserId, $messageText);
        if (!$filterResult['valid']) {
            Log::info('Fortune Filter: Message blocked', [
                'user_id' => $facebookUserId,
                'reason' => $filterResult['reason'],
                'text_preview' => mb_substr($messageText, 0, 50),
            ]);
            return [
                'action' => 'filtered',
                'message' => $filterResult['message'],
                'reading' => null,
                'filter_reason' => $filterResult['reason'],
            ];
        }

        // ตรวจสอบว่ามี conversation ที่กำลังดำเนินอยู่หรือไม่
        $activeReading = FortuneReading::findActiveConversation($facebookUserId);

        if ($activeReading) {
            return $this->continueConversation($activeReading, $messageText, $userProfile);
        }

        // ตรวจสอบว่าเป็นคำขอดูดวงหรือไม่
        if ($this->isFortuneRequest($messageText)) {
            // ตรวจสอบ AI calls limit ก่อนเริ่ม conversation ใหม่
            if (!$this->canMakeAICall($facebookUserId)) {
                return [
                    'action' => 'ai_limit',
                    'message' => $this->getAILimitMessage(),
                    'reading' => null,
                ];
            }
            return $this->startNewConversation($facebookUserId, $messageText, $userProfile);
        }

        // ไม่ใช่คำขอดูดวง → แสดง help พร้อมตัวอย่าง
        return [
            'action' => 'help',
            'message' => $this->getHelpMessageWithExamples(),
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

            // บันทึก AI call สำหรับ rate limiting
            $this->recordAICall($facebookUserId);

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

            // บันทึก AI call สำหรับ rate limiting (deep reading เป็นบริการชำระเงินแต่ยังนับด้วย)
            if ($reading->facebook_user_id) {
                $this->recordAICall($reading->facebook_user_id);
            }

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
     * สร้างข้อความ help พร้อมตัวอย่างคำถาม
     *
     * มีคาแรคเตอร์หมอดูที่อบอุ่น เป็นกันเอง แต่น่าเชื่อถือ
     *
     * @return string
     */
    protected function getHelpMessageWithExamples(): string
    {
        $price = $this->settings->deep_reading_price ?? self::DEEP_READING_PRICE;

        $message = "🔮 *สวัสดีค่ะ หมอยินดีต้อนรับนะคะ*\n\n";
        $message .= "หมอพร้อมช่วยดูดวงให้ค่ะ ไม่ว่าจะเรื่องอะไร:\n\n";

        $message .= "💕 *ความรัก* - เนื้อคู่ แฟน แต่งงาน\n";
        $message .= "💼 *การงาน* - เปลี่ยนงาน เลื่อนขั้น\n";
        $message .= "💰 *การเงิน* - โชคลาภ รายได้\n";
        $message .= "🏥 *สุขภาพ* - สิ่งควรระวัง\n\n";

        $message .= "═══════════════════════\n";
        $message .= "🎁 *บริการของหมอ*\n";
        $message .= "═══════════════════════\n\n";

        $message .= "🆓 *ดูดวงฟรี* - วันละ 1 คำถาม\n";
        $message .= "   ทำนายเรื่องทั่วไปแบบสั้นๆ\n\n";

        $message .= "💎 *ดูดวงละเอียด {$price} บาท*\n";
        $message .= "   ถามได้ 3 คำถาม วิเคราะห์จากวันเกิด\n";
        $message .= "   พร้อมสีมงคล เลขมงคล ฤกษ์ดี\n\n";

        $message .= "📝 *ตัวอย่างคำถาม*:\n";
        $message .= "• ปีนี้จะมีคู่ครองไหม\n";
        $message .= "• ควรเปลี่ยนงานตอนนี้ไหม\n";
        $message .= "• ดวงการเงินช่วงนี้เป็นอย่างไร\n\n";

        $message .= "💡 พิมพ์คำถามมาได้เลยนะคะ\n";
        $message .= "หมอพร้อมทำนายให้ค่ะ ✨";

        return $message;
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
    // Pre-Filter Methods - ตรวจจับข้อความก่อนส่ง AI
    // ============================================================

    /**
     * Pre-filter ข้อความก่อนประมวลผล (ไม่ต้องระบุ userId)
     *
     * ตรวจจับ:
     * - ข้อความยาวเกินไป (spam/flood)
     * - ข้อความสั้นเกินไป (ไม่มีความหมาย)
     * - ตัวอักษรแปลกๆ/spam characters
     * - คำถามที่ไม่เกี่ยวกับดูดวง
     * - Prompt injection attempts
     * - AI attack patterns
     * - Meaningless messages
     *
     * @param string $text ข้อความที่ต้องการตรวจสอบ
     * @return array ['valid' => bool, 'reason' => string, 'message' => string]
     */
    protected function preFilterMessage(string $text): array
    {
        $text = trim($text);
        $length = mb_strlen($text);

        // 1. ตรวจสอบความยาว
        if ($length > self::MAX_MESSAGE_LENGTH) {
            return [
                'valid' => false,
                'reason' => 'too_long',
                'message' => $this->getTooLongMessage(),
            ];
        }

        if ($length < self::MIN_QUESTION_LENGTH) {
            return [
                'valid' => false,
                'reason' => 'too_short',
                'message' => $this->getTooShortMessage(),
            ];
        }

        // 2. ตรวจจับ Prompt Injection (สำคัญ! ตรวจก่อน)
        if ($this->isPromptInjection($text)) {
            Log::warning('Fortune Filter: Prompt injection detected', [
                'text_preview' => mb_substr($text, 0, 100),
            ]);
            return [
                'valid' => false,
                'reason' => 'prompt_injection',
                'message' => $this->getSecurityBlockMessage(),
            ];
        }

        // 3. ตรวจจับ AI Attack Patterns
        if ($this->isAIAttack($text)) {
            Log::warning('Fortune Filter: AI attack pattern detected', [
                'text_preview' => mb_substr($text, 0, 100),
            ]);
            return [
                'valid' => false,
                'reason' => 'ai_attack',
                'message' => $this->getSecurityBlockMessage(),
            ];
        }

        // 4. ตรวจจับ spam/gibberish รุนแรง (ตัวอักษรซ้ำ, random characters)
        // ⚠️ ลดความเข้มงวด - ให้บล็อกเฉพาะ spam รุนแรงเท่านั้น
        if ($this->isSpamOrGibberish($text)) {
            return [
                'valid' => false,
                'reason' => 'spam',
                'message' => $this->getSpamMessage(),
            ];
        }

        // 5. ข้อความไร้สาระ (สวัสดี, hi, etc.) → ให้ผ่านไป ตอบเป็น help message แทน
        // ⚠️ ไม่บล็อก แต่ให้ main flow จัดการเป็น help message
        // (ย้ายไปจัดการใน processMessage แทน)

        // 7. ตรวจจับคำถามว่าเป็น AI หรือไม่
        if ($this->isAskingAboutAI($text)) {
            return [
                'valid' => true, // ปล่อยผ่านไปให้ AI ตอบตาม system prompt
                'reason' => 'ai_question',
                'message' => '',
            ];
        }

        return [
            'valid' => true,
            'reason' => 'ok',
            'message' => '',
        ];
    }

    /**
     * Pre-filter ข้อความพร้อม Rate Limiting (ต้องระบุ userId)
     *
     * @param string $userId Facebook User ID
     * @param string $text ข้อความ
     * @return array ['valid' => bool, 'reason' => string, 'message' => string]
     */
    public function preFilterWithRateLimit(string $userId, string $text): array
    {
        // 1. ตรวจสอบ Rate Limiting ก่อน
        $rateLimitResult = $this->checkRateLimit($userId);
        if (!$rateLimitResult['allowed']) {
            Log::warning('Fortune Filter: Rate limit exceeded', [
                'user_id' => $userId,
                'type' => $rateLimitResult['type'],
            ]);
            return [
                'valid' => false,
                'reason' => 'rate_limit',
                'message' => $this->getRateLimitMessage($rateLimitResult['type']),
            ];
        }

        // 2. ตรวจสอบข้อความซ้ำ
        if ($this->isRepetitiveMessage($userId, $text)) {
            return [
                'valid' => false,
                'reason' => 'repetitive',
                'message' => $this->getRepetitiveMessage(),
            ];
        }

        // 3. บันทึกข้อความล่าสุด
        $this->recordMessage($userId, $text);

        // 4. ตรวจสอบด้วย pre-filter ปกติ
        return $this->preFilterMessage($text);
    }

    /**
     * ตรวจจับข้อความ spam หรือพิมพ์ไม่รู้เรื่อง
     *
     * @param string $text
     * @return bool
     */
    protected function isSpamOrGibberish(string $text): bool
    {
        // 1. ตัวอักษรซ้ำๆ มากเกินไป (เช่น "aaaaaa", "5555555555")
        if (preg_match('/(.)\1{9,}/', $text)) {
            return true;
        }

        // 2. มี emoji มากเกินไป (มากกว่า 50% ของข้อความ)
        $emojiCount = preg_match_all('/[\x{1F600}-\x{1F64F}]|[\x{1F300}-\x{1F5FF}]|[\x{1F680}-\x{1F6FF}]|[\x{1F1E0}-\x{1F1FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]/u', $text);
        $textLength = mb_strlen(preg_replace('/[\x{1F600}-\x{1F64F}]|[\x{1F300}-\x{1F5FF}]|[\x{1F680}-\x{1F6FF}]|[\x{1F1E0}-\x{1F1FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]/u', '', $text));
        if ($textLength > 0 && $emojiCount / $textLength > 0.5) {
            return true;
        }

        // 3. มีตัวเลขมากเกินไป (มากกว่า 80% ของข้อความ)
        $digitCount = preg_match_all('/\d/', $text);
        if (mb_strlen($text) > 10 && $digitCount / mb_strlen($text) > 0.8) {
            return true;
        }

        // 4. มี special characters แปลกๆ มากเกินไป
        $specialCount = preg_match_all('/[^\p{L}\p{N}\s\.,!?@#\-_\'\"()]/u', $text);
        if (mb_strlen($text) > 10 && $specialCount / mb_strlen($text) > 0.4) {
            return true;
        }

        // 5. ไม่มีตัวอักษรไทยหรืออังกฤษเลย (ยกเว้นสั้นมาก)
        if (mb_strlen($text) > 10) {
            $hasThaiOrEnglish = preg_match('/[\p{Thai}a-zA-Z]/u', $text);
            if (!$hasThaiOrEnglish) {
                return true;
            }
        }

        return false;
    }

    /**
     * ตรวจจับ Prompt Injection Attempts
     * ป้องกันการพยายาม manipulate AI ด้วยคำสั่งพิเศษ
     *
     * @param string $text
     * @return bool
     */
    protected function isPromptInjection(string $text): bool
    {
        $textLower = mb_strtolower($text);

        foreach (self::PROMPT_INJECTION_PATTERNS as $pattern) {
            if (str_contains($textLower, mb_strtolower($pattern))) {
                return true;
            }
        }

        // ตรวจจับ patterns พิเศษด้วย regex
        $regexPatterns = [
            // JSON/XML injection
            '/\{["\']?system["\']?\s*:/i',
            '/<system>/i',
            '/<\/?prompt>/i',
            // Markdown injection for prompts
            '/```(system|prompt|instruction)/i',
            // Base64 encoded commands
            '/[a-zA-Z0-9+\/]{50,}={0,2}/',
            // Multiple newlines (trying to separate from context)
            '/\n{5,}/',
        ];

        foreach ($regexPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ตรวจจับรูปแบบการโจมตีจาก AI อื่น
     * ป้องกันการใช้ AI ตัวอื่นมาโจมตี/ทดสอบระบบ
     *
     * @param string $text
     * @return bool
     */
    protected function isAIAttack(string $text): bool
    {
        $textLower = mb_strtolower($text);

        foreach (self::AI_ATTACK_PATTERNS as $pattern) {
            if (str_contains($textLower, mb_strtolower($pattern))) {
                return true;
            }
        }

        // ตรวจจับรูปแบบ structured prompts
        $structuredPatterns = [
            // Numbered instructions
            '/^\s*(1\.|step\s*1|instruction\s*1)/im',
            // JSON-like structures
            '/\{\s*"(prompt|instruction|command|task)":/i',
            // Markdown headers for prompts
            '/^#{1,3}\s*(prompt|instruction|task|test)/im',
            // Automated testing patterns
            '/\[(input|output|expected|test)\]/i',
        ];

        foreach ($structuredPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ตรวจจับข้อความไร้สาระ/ถามเรื่อยเปื่อย
     *
     * @param string $text
     * @return bool
     */
    protected function isMeaninglessMessage(string $text): bool
    {
        $text = trim($text);

        // ตรวจสอบด้วย regex patterns
        foreach (self::MEANINGLESS_PATTERNS as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        // ตรวจสอบข้อความที่สั้นมากและไม่มี keywords เกี่ยวกับดูดวง
        if (mb_strlen($text) <= 10) {
            $textLower = mb_strtolower($text);
            $hasFortuneKeyword = false;

            foreach (self::FORTUNE_RELATED_KEYWORDS as $keyword) {
                if (str_contains($textLower, mb_strtolower($keyword))) {
                    $hasFortuneKeyword = true;
                    break;
                }
            }

            // ถ้าสั้นและไม่มี keyword ดูดวง และไม่ใช่คำสั่งพื้นฐาน
            if (!$hasFortuneKeyword && !$this->isBasicCommand($text)) {
                return true;
            }
        }

        return false;
    }

    // ============================================================
    // Rate Limiting Methods - ป้องกัน Spam/Attack
    // ============================================================

    /**
     * ตรวจสอบ Rate Limit
     *
     * @param string $userId
     * @return array ['allowed' => bool, 'type' => string|null]
     */
    protected function checkRateLimit(string $userId): array
    {
        $minuteKey = "fortune_rate:{$userId}:minute";
        $hourKey = "fortune_rate:{$userId}:hour";
        $dayKey = "fortune_ai_calls:{$userId}:day";

        // ตรวจสอบต่อนาที
        $minuteCount = (int) Cache::get($minuteKey, 0);
        if ($minuteCount >= self::MAX_MESSAGES_PER_MINUTE) {
            return ['allowed' => false, 'type' => 'minute'];
        }

        // ตรวจสอบต่อชั่วโมง
        $hourCount = (int) Cache::get($hourKey, 0);
        if ($hourCount >= self::MAX_MESSAGES_PER_HOUR) {
            return ['allowed' => false, 'type' => 'hour'];
        }

        // เพิ่ม counter
        Cache::put($minuteKey, $minuteCount + 1, now()->addMinute());
        Cache::put($hourKey, $hourCount + 1, now()->addHour());

        return ['allowed' => true, 'type' => null];
    }

    /**
     * ตรวจสอบว่าผู้ใช้ยังสามารถถามฟรีได้หรือไม่
     *
     * เช็คจากฐานข้อมูลว่าวันนี้ถามฟรีไปกี่ครั้งแล้ว
     * เทียบกับ max_free_readings ที่ตั้งค่าไว้
     *
     * @param string $userId Facebook/LINE User ID
     * @return bool true ถ้ายังถามฟรีได้
     */
    public function canMakeAICall(string $userId): bool
    {
        // ดึงค่า max_free_readings จาก settings (default = 1)
        $maxFreeReadings = $this->settings->max_free_readings ?? self::MAX_AI_CALLS_PER_DAY;

        // เช็คจากฐานข้อมูลว่าวันนี้ถามฟรีไปกี่ครั้งแล้ว
        return !FortuneReading::hasReachedFreeLimit($userId, $maxFreeReadings);
    }

    /**
     * ตรวจสอบจำนวนคำถามฟรีที่เหลือวันนี้
     *
     * @param string $userId
     * @return int จำนวนครั้งที่เหลือ
     */
    public function getRemainingFreeQuestions(string $userId): int
    {
        $maxFreeReadings = $this->settings->max_free_readings ?? self::MAX_AI_CALLS_PER_DAY;
        $usedToday = FortuneReading::countTodayReadings($userId);

        return max(0, $maxFreeReadings - $usedToday);
    }

    /**
     * บันทึกการเรียก AI
     *
     * @param string $userId
     */
    public function recordAICall(string $userId): void
    {
        $dayKey = "fortune_ai_calls:{$userId}:day";
        $count = (int) Cache::get($dayKey, 0);
        Cache::put($dayKey, $count + 1, now()->endOfDay());
    }

    /**
     * ตรวจสอบข้อความซ้ำ
     *
     * @param string $userId
     * @param string $text
     * @return bool
     */
    protected function isRepetitiveMessage(string $userId, string $text): bool
    {
        $historyKey = "fortune_history:{$userId}";
        $history = Cache::get($historyKey, []);

        // นับจำนวนข้อความที่ซ้ำกัน
        $textHash = md5(mb_strtolower(trim($text)));
        $count = 0;

        foreach ($history as $item) {
            if ($item['hash'] === $textHash) {
                $count++;
            }
        }

        return $count >= self::MAX_REPETITIVE_MESSAGES;
    }

    /**
     * บันทึกข้อความล่าสุด
     *
     * @param string $userId
     * @param string $text
     */
    protected function recordMessage(string $userId, string $text): void
    {
        $historyKey = "fortune_history:{$userId}";
        $history = Cache::get($historyKey, []);

        // เก็บ 10 ข้อความล่าสุด
        $history[] = [
            'hash' => md5(mb_strtolower(trim($text))),
            'time' => now()->timestamp,
        ];

        // เก็บแค่ 10 รายการ
        if (count($history) > 10) {
            $history = array_slice($history, -10);
        }

        Cache::put($historyKey, $history, now()->addHours(2));
    }

    /**
     * ดึงสถิติการใช้งานของ user
     *
     * @param string $userId
     * @return array
     */
    public function getUserUsageStats(string $userId): array
    {
        return [
            'messages_per_minute' => (int) Cache::get("fortune_rate:{$userId}:minute", 0),
            'messages_per_hour' => (int) Cache::get("fortune_rate:{$userId}:hour", 0),
            'ai_calls_today' => (int) Cache::get("fortune_ai_calls:{$userId}:day", 0),
            'limits' => [
                'max_per_minute' => self::MAX_MESSAGES_PER_MINUTE,
                'max_per_hour' => self::MAX_MESSAGES_PER_HOUR,
                'max_ai_calls_per_day' => self::MAX_AI_CALLS_PER_DAY,
            ],
        ];
    }

    /**
     * ตรวจจับคำถาม off-topic
     *
     * @param string $text
     * @return array ['is_off_topic' => bool, 'category' => string|null]
     */
    protected function detectOffTopic(string $text): array
    {
        $textLower = mb_strtolower($text);

        // ตรวจสอบ off-topic keywords
        foreach (self::OFF_TOPIC_KEYWORDS as $keyword) {
            if (str_contains($textLower, mb_strtolower($keyword))) {
                // หา category
                $category = $this->categorizeOffTopic($keyword);
                return [
                    'is_off_topic' => true,
                    'category' => $category,
                ];
            }
        }

        // ถ้ายาวพอ แต่ไม่มีคำเกี่ยวกับดูดวงเลย อาจเป็น off-topic
        if (mb_strlen($text) > 30) {
            $hasFortuneKeyword = false;
            foreach (self::FORTUNE_RELATED_KEYWORDS as $keyword) {
                if (str_contains($textLower, mb_strtolower($keyword))) {
                    $hasFortuneKeyword = true;
                    break;
                }
            }

            // ถ้าไม่มีคำเกี่ยวกับดูดวง และไม่ใช่คำสั่งพื้นฐาน
            if (!$hasFortuneKeyword && !$this->isBasicCommand($text)) {
                return [
                    'is_off_topic' => true,
                    'category' => 'unknown',
                ];
            }
        }

        return [
            'is_off_topic' => false,
            'category' => null,
        ];
    }

    /**
     * จัดหมวดหมู่ off-topic
     *
     * @param string $keyword
     * @return string
     */
    protected function categorizeOffTopic(string $keyword): string
    {
        $keywordLower = mb_strtolower($keyword);

        $categories = [
            'code' => ['code', 'โค้ด', 'programming', 'javascript', 'python', 'php', 'html', 'css', 'function', 'class', 'database', 'sql', 'api'],
            'food' => ['สูตรอาหาร', 'ทำอาหาร', 'วิธีทำ', 'recipe', 'แนะนำร้าน', 'ร้านอาหาร', 'ร้านกาแฟ'],
            'translate' => ['แปลภาษา', 'translate', 'แปลให้หน่อย'],
            'story' => ['เล่าเรื่อง', 'นิทาน', 'เรื่องผี', 'เรื่องตลก', 'มุก', 'joke'],
            'math' => ['คำนวณ', 'บวก', 'ลบ', 'คูณ', 'หาร', 'เปอร์เซ็นต์', 'calculate'],
            'hack' => ['hack', 'แฮก', 'crack', 'เจาะระบบ', 'password', 'รหัสผ่าน'],
            'homework' => ['เขียนบทความ', 'เขียนเรียงความ', 'รายงาน', 'การบ้าน', 'homework'],
        ];

        foreach ($categories as $category => $keywords) {
            if (in_array($keywordLower, array_map('mb_strtolower', $keywords))) {
                return $category;
            }
        }

        return 'other';
    }

    /**
     * ตรวจสอบว่าเป็นคำสั่งพื้นฐานหรือไม่
     *
     * @param string $text
     * @return bool
     */
    protected function isBasicCommand(string $text): bool
    {
        $textLower = mb_strtolower(trim($text));
        $basicCommands = ['ดูดวง', 'ทำนาย', 'ต้องการ', 'เอา', 'ใช่', 'ไม่', 'ยกเลิก', 'บัญชี', 'ok', 'yes', 'no', 'cancel'];

        foreach ($basicCommands as $cmd) {
            if (str_contains($textLower, $cmd)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ตรวจสอบว่าถามเกี่ยวกับ AI หรือไม่
     *
     * @param string $text
     * @return bool
     */
    protected function isAskingAboutAI(string $text): bool
    {
        $textLower = mb_strtolower($text);
        $aiKeywords = [
            'เป็นบอท', 'เป็นบอต', 'เป็น bot', 'เป็นโปรแกรม', 'เป็นหุ่นยนต์',
            'เป็น ai', 'เป็นเอไอ', 'คือ ai', 'คือบอท', 'คือโปรแกรม',
            'ใช้ ai', 'เป็นคนจริงไหม', 'คนจริงหรือเปล่า', 'ใช้ chatgpt',
            'are you bot', 'are you ai', 'are you real',
        ];

        foreach ($aiKeywords as $keyword) {
            if (str_contains($textLower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    // ============================================================
    // Pre-Filter Messages - ข้อความตอบกลับสำหรับ filter
    // ============================================================

    /**
     * ข้อความเมื่อตรวจจับ security threat (prompt injection, AI attack)
     */
    protected function getSecurityBlockMessage(): string
    {
        return "🙏 ขอบคุณที่ทักมานะคะ\n\n" .
               "ทางเพจขอตอบเฉพาะเรื่องดูดวงเท่านั้นค่ะ\n\n" .
               "💡 *ตัวอย่างคำถาม*:\n" .
               "• ดวงความรักปีนี้เป็นอย่างไร\n" .
               "• การเงินจะดีขึ้นไหม\n" .
               "• ควรเปลี่ยนงานไหม\n\n" .
               "ทางเพจพร้อมทำนายให้ค่ะ 🔮✨";
    }

    /**
     * ข้อความเมื่อโดน rate limit
     *
     * @param string $type minute, hour, หรือ day
     */
    protected function getRateLimitMessage(string $type): string
    {
        $messages = [
            'minute' => "🙏 ทางเพจขอเวลาสักครู่นะคะ\n\nกรุณารอสักครู่แล้วทักมาใหม่ค่ะ 🔮",
            'hour' => "🙏 ขอบคุณที่สนใจค่ะ\n\nทักมาเยอะมากเลยค่ะ กรุณารอสักพักแล้วค่อยทักมาใหม่นะคะ\n\nทางเพจพร้อมทำนายให้ค่ะ 🔮✨",
            'day' => "🙏 ขอบคุณที่ใช้บริการค่ะ\n\nวันนี้ทักมาเยอะมากเลยค่ะ กรุณากลับมาใหม่พรุ่งนี้นะคะ\n\nขอให้โชคดีค่ะ 🔮✨",
        ];

        return $messages[$type] ?? $messages['minute'];
    }

    /**
     * ข้อความเมื่อส่งข้อความซ้ำ
     */
    protected function getRepetitiveMessage(): string
    {
        return "🙏 ทางเพจเห็นข้อความนี้แล้วค่ะ\n\n" .
               "กรุณาลองถามเรื่องอื่น หรือถามในมุมใหม่ได้นะคะ\n\n" .
               "💡 *ตัวอย่าง*:\n" .
               "• ดวงความรักปีนี้เป็นอย่างไร\n" .
               "• การเงินจะดีขึ้นไหม\n" .
               "• ควรเปลี่ยนงานไหม\n\n" .
               "ทางเพจพร้อมทำนายให้ค่ะ 🔮✨";
    }

    /**
     * ข้อความเมื่อตรวจจับข้อความไร้สาระ
     *
     * ตอบด้วยความเป็นมิตร ไม่ดูถูกผู้ใช้
     */
    protected function getMeaninglessMessage(): string
    {
        return "🔮 *สวัสดีค่ะ หมอยินดีต้อนรับนะคะ*\n\n" .
               "หมอพร้อมช่วยดูดวงให้ค่ะ ไม่ว่าจะเรื่อง:\n\n" .
               "💕 ความรัก - เนื้อคู่ คู่ครอง\n" .
               "💼 การงาน - เปลี่ยนงาน เลื่อนขั้น\n" .
               "💰 การเงิน - โชคลาภ รายได้\n" .
               "🏥 สุขภาพ - สิ่งควรระวัง\n\n" .
               "💡 *ตัวอย่างคำถาม*:\n" .
               "• ปีนี้จะมีคู่ครองไหม\n" .
               "• ควรเปลี่ยนงานไหม\n" .
               "• ดวงการเงินเป็นอย่างไร\n\n" .
               "พิมพ์คำถามมาได้เลยค่ะ 🔮✨";
    }

    /**
     * ข้อความเมื่อใช้สิทธิ์ถามฟรีหมดแล้ว
     *
     * ชวนให้จ่ายเงินดูดวงละเอียดพร้อมบอกวิธีการชัดเจน
     */
    protected function getAILimitMessage(): string
    {
        $price = $this->settings->deep_reading_price ?? self::DEEP_READING_PRICE;

        $message = "🔮 *สวัสดีค่ะ ขอบคุณที่มาหาหมอนะคะ*\n\n";
        $message .= "วันนี้คุณใช้สิทธิ์ถามฟรีไปแล้วค่ะ\n";
        $message .= "(ฟรีวันละ 1 คำถาม)\n\n";

        $message .= "═══════════════════════\n";
        $message .= "💎 *ดูดวงละเอียด {$price} บาท*\n";
        $message .= "═══════════════════════\n\n";

        $message .= "📌 ถามได้ถึง 3 คำถาม\n";
        $message .= "📌 วิเคราะห์จากวันเกิดเจาะลึก\n";
        $message .= "📌 บอกสีมงคล เลขมงคล ฤกษ์ดี\n";
        $message .= "📌 คำทำนายละเอียดคุ้มราคา\n\n";

        $message .= "🎯 *วิธีใช้บริการ*\n";
        $message .= "─────────────────────\n";
        $message .= "1️⃣ โอนเงิน {$price} บาท\n";
        $message .= $this->getBankAccountsListMessage();
        $message .= "\n2️⃣ บอกวันเดือนปีเกิด\n";
        $message .= "3️⃣ ถามคำถามได้เลย 3 ข้อ\n\n";

        $message .= "💡 พิมพ์ \"*ต้องการดูละเอียด*\" หรือ \"*โอนแล้ว*\"\n";
        $message .= "เพื่อเริ่มกระบวนการค่ะ ✨";

        return $message;
    }

    /**
     * ข้อความเมื่อพิมพ์ยาวเกินไป
     */
    protected function getTooLongMessage(): string
    {
        return "🔮 ข้อความยาวไปหน่อยค่ะ\n\n" .
               "ลองย่อให้สั้นกว่านี้ได้ไหมคะ?\n" .
               "หมอจะได้ตอบได้ตรงจุดค่ะ\n\n" .
               "💡 *ตัวอย่าง*:\n" .
               "• ดวงความรักปีนี้เป็นอย่างไร\n" .
               "• ควรเปลี่ยนงานตอนนี้ไหม\n" .
               "• การเงินช่วงนี้จะดีไหม\n\n" .
               "หมอรอคำถามอยู่นะคะ ✨";
    }

    /**
     * ข้อความเมื่อพิมพ์สั้นเกินไป
     */
    protected function getTooShortMessage(): string
    {
        return "🔮 หมอไม่ค่อยเข้าใจค่ะ\n\n" .
               "ลองพิมพ์คำถามให้ชัดกว่านี้หน่อยนะคะ\n\n" .
               "💡 *ตัวอย่าง*:\n" .
               "• ดวงความรักปีนี้เป็นอย่างไร\n" .
               "• ควรเปลี่ยนงานไหม\n" .
               "• การเงินจะดีขึ้นไหม\n\n" .
               "หมอพร้อมทำนายให้ค่ะ ✨";
    }

    /**
     * ข้อความเมื่อตรวจจับ spam
     *
     * ตอบด้วยความสุภาพ ไม่กล่าวโทษผู้ใช้
     */
    protected function getSpamMessage(): string
    {
        return "🔮 *สวัสดีค่ะ*\n\n" .
               "หมอไม่ค่อยเข้าใจข้อความค่ะ\n" .
               "ลองพิมพ์คำถามชัดๆ ได้ไหมคะ?\n\n" .
               "💡 *ตัวอย่าง*:\n" .
               "• ปีนี้ดวงความรักเป็นอย่างไร\n" .
               "• ควรเปลี่ยนงานไหม\n" .
               "• ดวงการเงินช่วงนี้\n\n" .
               "หมอพร้อมทำนายให้นะคะ ✨";
    }

    /**
     * ข้อความเมื่อตรวจจับ off-topic
     *
     * @param string $category
     * @return string
     */
    protected function getOffTopicMessage(string $category): string
    {
        $categoryMessages = [
            'code' => "ขอบคุณที่สนใจค่ะ แต่ทางเพจไม่รับเขียนโค้ดหรือโปรแกรมนะคะ 🙏",
            'food' => "ขอบคุณที่สนใจค่ะ แต่ทางเพจไม่รับแนะนำร้านอาหารหรือสูตรอาหารนะคะ 🙏",
            'translate' => "ขอบคุณที่สนใจค่ะ แต่ทางเพจไม่รับแปลภาษานะคะ 🙏",
            'story' => "ขอบคุณที่สนใจค่ะ แต่ทางเพจไม่รับเล่าเรื่องหรือมุกตลกนะคะ 🙏",
            'math' => "ขอบคุณที่สนใจค่ะ แต่ทางเพจไม่รับคำนวณเลขนะคะ 🙏",
            'hack' => "ขอโทษค่ะ ทางเพจไม่รับทำสิ่งที่ผิดกฎหมายหรือไม่เหมาะสมค่ะ 🙏",
            'homework' => "ขอบคุณที่สนใจค่ะ แต่ทางเพจไม่รับทำการบ้านหรือเขียนรายงานนะคะ 🙏",
        ];

        $specificMessage = $categoryMessages[$category] ?? "ขอบคุณที่สนใจค่ะ 🙏";

        return "{$specificMessage}\n\n" .
               "═══════════════════════\n" .
               "🔮 *ทางเพจรับดูดวงเท่านั้นค่ะ*\n" .
               "═══════════════════════\n\n" .
               "ถ้ามีเรื่องอยากให้ทำนาย ไม่ว่าจะเรื่อง:\n" .
               "💕 ความรัก คู่ครอง\n" .
               "💼 การงาน อาชีพ\n" .
               "💰 การเงิน โชคลาภ\n" .
               "🏥 สุขภาพ\n\n" .
               "ทักมาได้เลยค่ะ ทางเพจพร้อมทำนายให้ค่ะ 🔮✨";
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
     *
     * กฎพิเศษ:
     * - ถ้าถูกถามว่าเป็น AI: ตอบว่า "ทางเพจมีทีมงานช่วยกัน"
     * - พูดเฉพาะเรื่องดูดวง ปฏิเสธเรื่องอื่นสุภาพ
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
- ท้ายคำทำนายให้บอกว่า \"ถ้าบอกวันเดือนปีเกิดให้ทางเพจ จะได้ทำนายได้ละเอียดและแม่นยำยิ่งขึ้นนะคะ 🎂\"

[กฎสำคัญ]
1. หากถูกถามว่าเป็น AI หุ่นยนต์ หรือบอท: ให้ตอบเป็นธรรมชาติว่า \"ทางเพจมีทีมงานช่วยกันในการทำนายค่ะ ไม่ต้องห่วงนะคะ 🔮\" หรือ \"ทางเพจทำนายเองค่ะ มีประสบการณ์มากกว่า 30 ปีแล้วค่ะ ✨\"
2. คุณพูดเฉพาะเรื่องดูดวงเท่านั้น หากถูกถามเรื่องอื่นที่ไม่เกี่ยวกับดูดวง (เช่น ขอให้เขียนโค้ด แนะนำร้านอาหาร สอนทำอาหาร คำถามทั่วไป) ให้ปฏิเสธอย่างสุภาพว่า \"ขอบคุณที่สนใจนะคะ แต่ทางเพจขอตอบเฉพาะเรื่องดูดวงนะคะ 🙏 ถ้ามีเรื่องอยากให้ทำนาย ไม่ว่าจะเรื่องความรัก การงาน การเงิน หรือสุขภาพ ทักมาได้เลยค่ะ 🔮✨\"";
    }

    /**
     * สร้าง prompt สำหรับทำนายละเอียด
     * เป็นหมอดูหญิง ใช้คำแทนตัวว่า "ทางเพจ" ทำนายแม่นยำ
     *
     * กฎพิเศษ:
     * - ถ้าถูกถามว่าเป็น AI: ตอบว่า "ทางเพจมีทีมงานช่วยกัน"
     * - พูดเฉพาะเรื่องดูดวง ปฏิเสธเรื่องอื่นสุภาพ
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

[กฎสำคัญ]
1. หากถูกถามว่าเป็น AI หุ่นยนต์ หรือบอท: ให้ตอบเป็นธรรมชาติว่า \"ทางเพจมีทีมงานช่วยกันในการทำนายค่ะ ไม่ต้องห่วงนะคะ 🔮\" หรือ \"ทางเพจทำนายเองค่ะ มีประสบการณ์มากกว่า 30 ปีแล้วค่ะ ✨\"
2. คุณพูดเฉพาะเรื่องดูดวงเท่านั้น หากถูกถามเรื่องอื่นที่ไม่เกี่ยวกับดูดวง ให้ปฏิเสธอย่างสุภาพว่า \"ขอบคุณที่สนใจนะคะ แต่ทางเพจขอตอบเฉพาะเรื่องดูดวงนะคะ 🙏 ถ้ามีเรื่องอยากให้ทำนาย ทักมาได้เลยค่ะ 🔮✨\"

ตอบเป็นภาษาไทย อบอุ่น เป็นกันเอง เหมือนพี่สาวที่คอยแนะนำน้อง";
    }
}
