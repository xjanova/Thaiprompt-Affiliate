<?php

namespace App\Services;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneUserCredit;
use App\Models\SmsPaymentNotification;
use App\Models\UniquePaymentAmount;
use App\Services\FortuneChannelManager;
use App\Services\LineFortuneService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Fortune Conversation Service
 *
 * จัดการ conversational flow สำหรับดูดวงผ่าน Facebook Messenger
 *
 * Flow:
 * 1. User พิมพ์ข้อความ → แจ้งสิทธิ์ดูดวงฟรีที่เหลือวันนี้ + ถามว่าจะดูไหม
 * 2. User ยืนยัน → ดึงโปรไฟล์ + ทำนายพื้นฐานฟรี
 * 3. เสนอดูดวงละเอียด 49 บาท → ถามวันเกิด + 2 คำถาม
 * 4. สร้างบิล + unique amount → แสดงบัญชีธนาคาร
 * 5. SMS match → ส่งคำทำนายละเอียดผ่าน Messenger
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
    public const REQUIRED_QUESTIONS = 2;

    /**
     * ความยาวคำถามขั้นต่ำ (ตัวอักษร) - ลดลงเพื่อให้คุยได้สะดวก
     */
    public const MIN_QUESTION_LENGTH = 1;

    /**
     * ความยาวคำถามสูงสุด (ตัวอักษร)
     */
    public const MAX_QUESTION_LENGTH = 500;

    /**
     * ความยาวข้อความสูงสุดที่รับ (ป้องกัน spam) - เพิ่มให้พิมพ์ได้เยอะขึ้น
     */
    public const MAX_MESSAGE_LENGTH = 2000;

    /**
     * คำถามสำเร็จรูปแยกตามหมวดหมู่
     *
     * ใช้สำหรับ Quick Reply buttons — user กดเลือกหมวดแทนพิมพ์เอง
     * แต่ละหมวดมี 3 คำถาม เพื่อให้ไม่ซ้ำกันเมื่อกดหมวดเดิมหลายครั้ง
     */
    protected const CATEGORY_QUESTION_MAP = [
        'love' => [
            'ดวงความรักและเนื้อคู่ในช่วงนี้เป็นอย่างไร',
            'จะมีคู่ครองหรือคนรักใหม่เข้ามาไหม',
            'ความสัมพันธ์กับคนรักจะเป็นอย่างไร',
        ],
        'work' => [
            'ดวงการงานและอาชีพในช่วงนี้เป็นอย่างไร',
            'จะได้เลื่อนตำแหน่งหรือเปลี่ยนงานไหม',
            'ธุรกิจหรืองานที่ทำจะเจริญก้าวหน้าไหม',
        ],
        'money' => [
            'ดวงการเงินและรายได้ในช่วงนี้เป็นอย่างไร',
            'จะมีโชคลาภหรือรายได้พิเศษไหม',
            'การลงทุนหรือการออมเงินจะเป็นอย่างไร',
        ],
        'health' => [
            'ดวงสุขภาพในช่วงนี้ต้องระวังอะไรบ้าง',
            'สุขภาพโดยรวมจะเป็นอย่างไร',
            'มีเรื่องสุขภาพอะไรที่ควรใส่ใจเป็นพิเศษ',
        ],
    ];

    /**
     * Rate Limiting: จำนวนข้อความสูงสุดต่อนาที - เพิ่มให้คุยได้ลื่นขึ้น
     */
    public const MAX_MESSAGES_PER_MINUTE = 30;

    /**
     * Rate Limiting: จำนวนข้อความสูงสุดต่อชั่วโมง - เพิ่มให้คุยได้ลื่นขึ้น
     */
    public const MAX_MESSAGES_PER_HOUR = 200;

    /**
     * Rate Limiting: จำนวน AI calls สูงสุดต่อวัน (ต่อ user) - fallback ถ้าไม่ได้ตั้งค่า
     * ปกติใช้ค่าจาก settings.max_free_readings แทน
     * เพิ่มเป็น 5 เพื่อให้คุยได้หลายรอบ
     */
    public const MAX_AI_CALLS_PER_DAY = 5;

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

    protected FortuneChartService $chartService;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
        $this->aiService = new FortuneAIService($this->settings);
        $this->facebookService = new FacebookWebhookService($this->settings);
        $this->chartService = new FortuneChartService;
    }

    /**
     * ดึงราคาดูดวงจากการตั้งค่าระบบ
     *
     * ลำดับการดึงราคา:
     * 1. deep_reading_price (ราคาเชิงลึกจากส่วน Freemium — ถ้าเปิดและตั้งราคาไว้)
     * 2. reading_price (ราคาดูดวงพื้นฐาน/ครั้ง — ตั้งจากหน้า settings หลัก)
     * 3. DEEP_READING_PRICE constant (fallback สุดท้าย = 49 บาท)
     *
     * ⚠️ สำคัญ: ต้อง cast เป็น float เพราะ Laravel decimal:2 cast
     * จะคืนค่า "0.00" เป็น string ซึ่ง PHP ถือว่าเป็น truthy
     *
     * @return float ราคาดูดวง (บาท)
     */
    protected function getDeepReadingPrice(): float
    {
        // ลำดับที่ 1: ราคาเชิงลึก (Freemium section)
        $deepPrice = (float) ($this->settings->deep_reading_price ?? 0);
        if ($deepPrice > 0) {
            return $deepPrice;
        }

        // ลำดับที่ 2: ราคาดูดวงพื้นฐาน/ครั้ง (ตั้งจากหน้า settings หลัก)
        $readingPrice = (float) ($this->settings->reading_price ?? 0);
        if ($readingPrice > 0) {
            return $readingPrice;
        }

        // ลำดับที่ 3: fallback ค่า default
        return self::DEEP_READING_PRICE;
    }

    /**
     * ดึง QR Code URL สำหรับชำระเงิน
     *
     * ลำดับ: 1) QR จากการตั้งค่าดูดวง (payment_qr_image)
     *        2) QR จากบัญชีธนาคารตัวแรกที่มี (qr_image)
     *
     * @return string|null URL ของ QR Code หรือ null ถ้าไม่มี
     */
    protected function getPaymentQrImageUrl(): ?string
    {
        // 1. เช็ค QR จากการตั้งค่าดูดวง
        $settingsQr = $this->settings->getPaymentQrUrl();
        if ($settingsQr) {
            return $settingsQr;
        }

        // 2. เช็ค QR จากบัญชีธนาคาร
        $accounts = $this->settings->getFortuneBankAccounts();
        foreach ($accounts as $account) {
            if (! empty($account->qr_image_url)) {
                return $account->qr_image_url;
            }
        }

        return null;
    }

    /**
     * ประมวลผลข้อความจาก Messenger
     *
     * @return array ผลลัพธ์ ['action' => '...', 'message' => '...', 'reading' => FortuneReading|null]
     */
    public function processMessage(string $facebookUserId, string $messageText, ?array $userProfile = null): array
    {
        try {
            // Pre-filter พร้อม Rate Limiting: ตรวจจับ spam รุนแรงเท่านั้น
            $filterResult = $this->preFilterWithRateLimit($facebookUserId, $messageText);
            if (! $filterResult['valid']) {
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

            // ✅ ตรวจสอบคำสั่งพิเศษ: เช็คสิทธิ์ดูดวง
            if ($this->isCheckRemainingRequest($messageText)) {
                return $this->handleCheckRemaining($facebookUserId);
            }

            // ✅ ตรวจสอบคำสั่ง "ไว้ดูทีหลัง" (จากปุ่ม quick reply หลังคำทำนายพร้อม)
            if ($this->isViewLaterRequest($messageText)) {
                return $this->handleViewLater($facebookUserId);
            }

            // ✅ ตรวจสอบคำสั่งพิเศษ: ดูคำทำนายล่าสุด
            if ($this->isViewLastReadingRequest($messageText)) {
                return $this->handleViewLastReading($facebookUserId);
            }

            // ตรวจสอบว่ามี conversation ที่กำลังดำเนินอยู่หรือไม่
            $activeReading = FortuneReading::findActiveConversation($facebookUserId);

            // 🔍 Debug log: ติดตามสถานะ conversation
            Log::info('Fortune processMessage: ตรวจสอบ active conversation', [
                'facebook_user_id' => $facebookUserId,
                'has_active' => ! is_null($activeReading),
                'active_status' => $activeReading?->conversation_status,
                'active_id' => $activeReading?->id,
                'text_preview' => mb_substr($messageText, 0, 30),
            ]);

            if ($activeReading) {
                // ✅ ตรวจสอบคำขอยกเลิกก่อน — ทุกสถานะ (ปิดทุก conversation ค้าง)
                if ($this->isCancelRequest($messageText)) {
                    $this->closeAllActiveConversations($facebookUserId);

                    return [
                        'action' => 'cancelled',
                        'message' => "ยกเลิกแล้วค่ะ หากต้องการดูดวงใหม่ พิมพ์ 'ดูดวง' ได้เลยนะคะ 🔮",
                        'reading' => $activeReading,
                    ];
                }

                // ถ้าอยู่ในสถานะ awaiting_confirmation: เช็คว่าผู้ใช้ยืนยันจะดูดวงหรือไม่
                if ($activeReading->conversation_status === FortuneReading::STATUS_AWAITING_CONFIRMATION) {
                    return $this->handleConfirmationResponse($activeReading, $facebookUserId, $messageText, $userProfile);
                }

                // ถ้าอยู่ในสถานะ basic_done: เช็คว่ารับ deep reading หรือไม่
                // ถ้าไม่ใช่ → ปิด conversation เก่าแล้วเริ่มทำนายใหม่ทันที (ไม่ต้องถามซ้ำ)
                if ($activeReading->conversation_status === FortuneReading::STATUS_BASIC_DONE) {
                    if ($this->isDeepReadingAccepted($messageText)) {
                        return $this->continueConversation($activeReading, $messageText, $userProfile);
                    }
                    // ปิด conversation เก่า
                    $activeReading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

                    // ✅ V3: ทุกกรณีให้ถามคำถามก่อนเสมอ → แล้วค่อยทำนาย → ชวนดูเชิงลึก
                    if ($this->containsFortuneKeyword($messageText)) {
                        return $this->askForQuestionBeforeReading($facebookUserId, $messageText, $userProfile);
                    }
                    // ✅ ถ้าไม่ใช่คำถามดูดวง → ถาม confirmation ตามปกติ
                    return $this->askFortuneConfirmation($facebookUserId, $messageText, $userProfile);
                }

                // ✅ สถานะอื่นๆ (collecting_birthdate, collecting_questions, pending_payment)
                // → ส่งต่อให้ continueConversation() จัดการตามสถานะ
                return $this->continueConversation($activeReading, $messageText, $userProfile);
            }

            // ✅ ตรวจสอบว่าเป็นคำขอดูดวงละเอียด (บริการเสียเงิน) → ข้าม limit ฟรี
            // เมื่อผู้ใช้กดปุ่ม "💎 ดูดวงละเอียด" จาก ai_limit → ต้องเข้า flow เก็บวันเกิด+คำถาม ไม่ใช่วน ai_limit ซ้ำ
            // ใช้ isExplicitDeepReadingRequest() ที่เข้มงวดกว่า เพื่อไม่ให้ keyword ทั่วไป (เช่น "ใช่", "ได้") trigger ผิดพลาด
            if ($this->isExplicitDeepReadingRequest($messageText)) {
                return $this->startDeepReadingFlow($facebookUserId, $userProfile);
            }

            // ✅ ตรวจสอบ AI calls limit ก่อนส่งให้ AI (เฉพาะบริการฟรี)
            if (! $this->canMakeAICall($facebookUserId)) {
                return [
                    'action' => 'ai_limit',
                    'message' => $this->getAILimitMessage(),
                    'reading' => null,
                ];
            }

            // ✅ V3: ทุกกรณีถามคำถามก่อนเสมอ → ทำนายตามคำถาม → ชวนดูเชิงลึก
            // ไม่ว่าจะพิมพ์ "ดูดวง" หรือ "ดวงการเงินปีนี้" → ถามคำถามก่อนแล้วค่อยทำนาย
            if ($this->containsFortuneKeyword($messageText)) {
                return $this->askForQuestionBeforeReading($facebookUserId, $messageText, $userProfile);
            }

            // ถ้าข้อความไม่ชัดเจนว่าจะดูดวง → ถามยืนยันก่อน แจ้งสิทธิ์ฟรีที่เหลือ
            return $this->askFortuneConfirmation($facebookUserId, $messageText, $userProfile);

        } catch (\Exception $e) {
            // ✅ จับ exception ทุกชนิดที่หลุดมา ไม่ให้ error bubble ไปถึง controller
            Log::error('Fortune processMessage: เกิดข้อผิดพลาด', [
                'facebook_user_id' => $facebookUserId,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'error_file' => $e->getFile().':'.$e->getLine(),
                'trace_short' => mb_substr($e->getTraceAsString(), 0, 800),
                'text_preview' => mb_substr($messageText, 0, 50),
            ]);

            // ✅ ป้องกัน null userProfile - ใช้ is_array ก่อนเข้าถึง array key
            $name = (is_array($userProfile) && isset($userProfile['name'])) ? $userProfile['name'] : 'คุณ';

            // ✅ ถ้ามี conversation ค้างอยู่ → ตอบตามสถานะแทนที่จะเริ่มใหม่
            try {
                $activeReading = FortuneReading::findActiveConversation($facebookUserId);
                if ($activeReading) {
                    $status = $activeReading->conversation_status;

                    // ถ้าอยู่ระหว่างเก็บคำถาม → แจ้งให้พิมพ์คำถามอีกครั้ง
                    if ($status === FortuneReading::STATUS_COLLECTING_QUESTIONS) {
                        $collected = count($activeReading->getCollectedQuestions());
                        $remaining = max(0, self::REQUIRED_QUESTIONS - $collected);

                        return [
                            'action' => 'retry_question',
                            'message' => "ขอโทษค่ะ ระบบขัดข้องชั่วคราว 🙏\n\n"
                                ."ตอนนี้จันทรารับคำถามแล้ว {$collected} ข้อ\n"
                                ."กรุณาพิมพ์คำถามอีก {$remaining} ข้อใหม่อีกครั้งนะคะ",
                            'reading' => $activeReading,
                        ];
                    }

                    // ถ้าอยู่ระหว่างเก็บวันเกิด → แจ้งให้พิมพ์วันเกิดอีกครั้ง
                    if ($status === FortuneReading::STATUS_COLLECTING_BIRTHDATE) {
                        return [
                            'action' => 'retry_birthdate',
                            'message' => "ขอโทษค่ะ ระบบขัดข้องชั่วคราว 🙏\n\nกรุณาพิมพ์วันเกิดอีกครั้งนะคะ\n📅 เช่น 15/08/1990",
                            'reading' => $activeReading,
                        ];
                    }

                    // ถ้ารอชำระเงิน → แจ้งยอดชำระ
                    if ($status === FortuneReading::STATUS_PENDING_PAYMENT) {
                        return $this->handlePendingPayment($activeReading, $messageText);
                    }
                }
            } catch (\Exception $innerErr) {
                Log::error('Fortune: Error recovery ล้มเหลว', ['error' => $innerErr->getMessage()]);
            }

            return [
                'action' => 'error',
                'message' => $this->getFallbackMessage($name),
                'reading' => null,
            ];
        }
    }

    /**
     * ข้อความ fallback เมื่อระบบมีปัญหา - ยังคงเป็นมิตรกับผู้ใช้
     *
     * @param  string  $name  ชื่อผู้ใช้
     */
    protected function getFallbackMessage(string $name): string
    {
        return "🔮 สวัสดีค่ะ คุณ{$name} ✨\n\n".
               "เพจดูดวงหมอจันทรายินดีต้อนรับค่ะ\n\n".
               "บอกจันทราได้เลยนะคะว่าอยากรู้เรื่องอะไร:\n".
               "💕 ความรัก - เนื้อคู่ คู่ครอง\n".
               "💼 การงาน - เปลี่ยนงาน เลื่อนตำแหน่ง\n".
               "💰 การเงิน - รายได้ การลงทุน\n".
               "🏥 สุขภาพ - ระวังอะไรบ้าง\n\n".
               'พิมพ์มาได้เลยค่ะ 🔮';
    }

    /**
     * สร้าง fallback response เมื่อ AI ติดต่อไม่ได้
     * ตอบแบบเป็นธรรมชาติเหมือนหมอดูจริง ไม่บอกว่าระบบมีปัญหา
     *
     * @param  string  $messageText  ข้อความจากผู้ใช้
     * @param  array|null  $userProfile  โปรไฟล์ผู้ใช้
     */
    protected function getFallbackFortuneResponse(string $messageText, ?array $userProfile = null): string
    {
        $name = $userProfile['name'] ?? 'คุณ';
        $text = mb_strtolower(trim($messageText));

        // ทักทาย
        $greetings = ['สวัสดี', 'หวัดดี', 'ดีจ้า', 'ดีค่ะ', 'ดีครับ', 'hi', 'hello', 'hey'];
        foreach ($greetings as $greeting) {
            if (str_contains($text, $greeting)) {
                return "🔮 สวัสดีค่ะ คุณ{$name} ✨\n\n".
                       "เพจดูดวงหมอจันทรายินดีต้อนรับค่ะ พร้อมช่วยดูดวงให้ค่ะ\n\n".
                       "ไม่ว่าจะเรื่องความรัก 💕 การงาน 💼 การเงิน 💰 หรือสุขภาพ 🏥\n".
                       "ถามมาได้เลยนะคะ แล้วอย่าลืมบอกวันเดือนปีเกิดให้จันทราด้วยนะคะ จะได้ทำนายได้แม่นยำยิ่งขึ้น 🎂\n\n".
                       'ฝากส่งต่อให้เพื่อนๆ มาลองดูดวงด้วยกันนะคะ 🔮✨';
            }
        }

        // คำถามเกี่ยวกับความรัก
        $loveKeywords = ['ความรัก', 'แฟน', 'คู่ครอง', 'เนื้อคู่', 'คนรัก', 'สามี', 'ภรรยา', 'แต่งงาน', 'รัก', 'เลิก'];
        foreach ($loveKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return $this->getRandomFallback('love', $name);
            }
        }

        // คำถามเกี่ยวกับการงาน
        $workKeywords = ['การงาน', 'งาน', 'อาชีพ', 'เปลี่ยนงาน', 'หางาน', 'เจ้านาย', 'เลื่อนตำแหน่ง', 'ธุรกิจ'];
        foreach ($workKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return $this->getRandomFallback('work', $name);
            }
        }

        // คำถามเกี่ยวกับการเงิน
        $moneyKeywords = ['การเงิน', 'เงิน', 'รายได้', 'หนี้', 'รวย', 'ลงทุน', 'หุ้น', 'ค้าขาย'];
        foreach ($moneyKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return $this->getRandomFallback('money', $name);
            }
        }

        // คำถามเกี่ยวกับสุขภาพ
        $healthKeywords = ['สุขภาพ', 'ป่วย', 'โรค', 'อุบัติเหตุ', 'เจ็บ'];
        foreach ($healthKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return $this->getRandomFallback('health', $name);
            }
        }

        // คำถามเกี่ยวกับดวงทั่วไป
        if ($this->isFortuneRequest($text)) {
            return $this->getRandomFallback('general', $name);
        }

        // ข้อความอื่นๆ ทั่วไป
        return "🔮 สวัสดีค่ะ คุณ{$name}\n\n".
               "เพจดูดวงหมอจันทรายินดีต้อนรับค่ะ จันทราพร้อมดูดวงให้ค่ะ ✨\n\n".
               "ลองบอกจันทราว่าอยากรู้เรื่องอะไร:\n".
               "💕 ความรัก - เนื้อคู่ คู่ครอง\n".
               "💼 การงาน - เปลี่ยนงาน เลื่อนตำแหน่ง\n".
               "💰 การเงิน - รายได้ การลงทุน\n".
               "🏥 สุขภาพ - ระวังอะไรบ้าง\n\n".
               'บอกวันเดือนปีเกิดมาด้วยนะคะ จะได้ทำนายได้แม่นยำยิ่งขึ้น 🎂✨';
    }

    /**
     * สุ่มข้อความ fallback ตามหมวดหมู่
     *
     * @param  string  $category  หมวดหมู่ (love, work, money, health, general)
     * @param  string  $name  ชื่อผู้ใช้
     */
    protected function getRandomFallback(string $category, string $name): string
    {
        $responses = [
            'love' => [
                "🔮 คุณ{$name} คะ จันทราเห็นว่าช่วงนี้ดวงความรักกำลังมีการเปลี่ยนแปลงค่ะ\n\n".
                "💕 สำหรับคนมีคู่: ช่วงนี้ควรให้เวลากับคนรักมากขึ้น มีเรื่องดีๆ รออยู่ข้างหน้าค่ะ\n".
                "💕 สำหรับคนโสด: ดวงเปิดรับคนใหม่ ลองเปิดใจดูนะคะ\n\n".
                "📅 ช่วงเวลาที่ดี: 2-3 เดือนข้างหน้า\n".
                "🎨 สีมงคล: ชมพู, แดง\n\n".
                'ถ้าบอกวันเดือนปีเกิดให้จันทรา จะได้ทำนายได้แม่นยำยิ่งขึ้นนะคะ 🎂',

                "🔮 คุณ{$name} คะ จันทราขอบอกตรงๆ เลยนะคะ\n\n".
                "💕 ดวงความรักของคุณช่วงนี้ มีทั้งสิ่งดีและสิ่งที่ต้องระวังค่ะ\n".
                "✅ เรื่องดี: จะมีคนเข้ามาให้ความสนใจ หรือคนรักจะแสดงความรักมากขึ้น\n".
                "⚠️ ระวัง: อย่าใจร้อน อย่าตัดสินใจเรื่องใหญ่เรื่องความรักเร็วเกินไป\n\n".
                "🔢 เลขมงคล: 9, 19\n\n".
                'อยากรู้ละเอียดกว่านี้ บอกวันเกิดมาได้เลยนะคะ 🎂✨',
            ],
            'work' => [
                "🔮 คุณ{$name} คะ จันทราเห็นดวงการงานช่วงนี้ค่ะ\n\n".
                "💼 ดวงการงานกำลังอยู่ในช่วงที่ต้องอดทนและพัฒนาตัวเอง\n".
                "✅ โอกาสใหม่ๆ จะเริ่มเข้ามาในช่วง 1-3 เดือนข้างหน้า\n".
                "✅ คนที่คิดจะเปลี่ยนงาน ช่วงนี้เป็นจังหวะที่ดีค่ะ\n".
                "⚠️ ระวังเรื่องเพื่อนร่วมงาน อย่าไว้ใจคนง่ายเกินไป\n\n".
                "📅 วันมงคล: วันพฤหัสบดี\n".
                "🎨 สีมงคล: เหลือง, ส้ม\n\n".
                'บอกวันเกิดมาด้วยนะคะ จะได้วิเคราะห์ดวงได้ลึกขึ้น 🎂',

                "🔮 คุณ{$name} คะ จันทราขอทำนายดวงการงานให้นะคะ\n\n".
                "💼 ช่วงนี้เป็นจังหวะที่ดีสำหรับการเริ่มต้นสิ่งใหม่ค่ะ\n".
                "✅ มีเกณฑ์ได้รับข่าวดีเรื่องงาน\n".
                "✅ คนทำธุรกิจจะเริ่มเห็นผลลัพธ์\n".
                "⚠️ แต่อย่าประมาท ทำทุกอย่างให้รอบคอบ\n\n".
                "🔢 เลขมงคล: 5, 14\n\n".
                'ถ้าอยากรู้ละเอียดกว่านี้ บอกวันเดือนปีเกิดมานะคะ 🎂✨',
            ],
            'money' => [
                "🔮 คุณ{$name} คะ จันทราเห็นดวงการเงินค่ะ\n\n".
                "💰 ดวงการเงินช่วงนี้: ต้องระมัดระวังเรื่องรายจ่ายค่ะ\n".
                "✅ มีเกณฑ์ได้เงินก้อน หรือรายได้เพิ่มในช่วง 2-4 เดือนข้างหน้า\n".
                "✅ เหมาะกับการออมเงินและวางแผนการเงิน\n".
                "⚠️ ระวังการลงทุนที่เสี่ยงสูง ช่วงนี้ยังไม่ใช่จังหวะ\n\n".
                "🎨 สีมงคลการเงิน: เขียว, ทอง\n".
                "📅 วันมงคล: วันพุธ\n\n".
                'บอกวันเกิดมาด้วยนะคะ จะได้ทำนายเรื่องการเงินได้แม่นขึ้น 🎂',

                "🔮 คุณ{$name} คะ จันทราขอบอกเรื่องการเงินนะคะ\n\n".
                "💰 ดวงการเงินของคุณกำลังจะดีขึ้นค่ะ\n".
                "✅ มีโอกาสได้รับเงินจากทางที่ไม่คาดคิด\n".
                "✅ คนที่ค้าขายจะเริ่มมีลูกค้าเพิ่มขึ้น\n".
                "⚠️ แต่ระวังเรื่องการใช้จ่ายฟุ่มเฟือย\n\n".
                "🔢 เลขมงคลการเงิน: 3, 8, 24\n\n".
                'อยากรู้ละเอียดกว่านี้ บอกวันเดือนปีเกิดมานะคะ 🎂✨',
            ],
            'health' => [
                "🔮 คุณ{$name} คะ จันทราเห็นดวงสุขภาพค่ะ\n\n".
                "🏥 ช่วงนี้ต้องดูแลสุขภาพให้ดีค่ะ\n".
                "✅ ออกกำลังกายเบาๆ สม่ำเสมอ จะช่วยได้มาก\n".
                "✅ พักผ่อนให้เพียงพอ อย่าหักโหมมากเกินไป\n".
                "⚠️ ระวังเรื่องการเดินทาง และอาหารการกิน\n\n".
                "📅 ช่วงที่ต้องระวังเป็นพิเศษ: 2-3 สัปดาห์ข้างหน้า\n".
                "🎨 สีมงคล: เขียว, ขาว\n\n".
                'บอกวันเกิดมาด้วยนะคะ จะได้วิเคราะห์ดวงสุขภาพได้ละเอียดขึ้น 🎂',
            ],
            'general' => [
                "🔮 คุณ{$name} คะ จันทรายินดีดูดวงให้ค่ะ ✨\n\n".
                "⭐ ดวงโดยรวมช่วงนี้: กำลังอยู่ในช่วงเปลี่ยนผ่าน มีทั้งเรื่องดีและสิ่งที่ต้องระวังค่ะ\n\n".
                "✅ เรื่องดี: จะมีโอกาสใหม่ๆ เข้ามา ทั้งเรื่องงานและเรื่องส่วนตัว\n".
                "✅ การเงินมีเกณฑ์ดีขึ้น\n".
                "⚠️ ระวัง: เรื่องสุขภาพ อย่าประมาท ดูแลตัวเองให้ดี\n\n".
                "🎨 สีมงคล: น้ำเงิน, ทอง\n".
                "🔢 เลขมงคล: 7, 16\n".
                "📅 วันมงคล: วันพฤหัสบดี\n\n".
                "บอกวันเดือนปีเกิดให้จันทรานะคะ จะได้ทำนายได้แม่นยำยิ่งขึ้น 🎂\n".
                'ถ้าคำทำนายถูกใจ ฝากส่งต่อให้เพื่อนๆ มาลองดูดวงด้วยกันนะคะ 🔮✨',

                "🔮 คุณ{$name} คะ จันทราขอทำนายดวงให้นะคะ\n\n".
                "⭐ ภาพรวมดวงชะตา: กำลังเข้าสู่ช่วงที่ดีค่ะ\n\n".
                "💕 ความรัก: มีเกณฑ์ได้พบคนถูกใจ หรือความสัมพันธ์จะแน่นแฟ้นขึ้น\n".
                "💼 การงาน: มีความก้าวหน้า อาจได้รับข้อเสนอใหม่\n".
                "💰 การเงิน: ระมัดระวังเรื่องรายจ่าย แต่มีเกณฑ์ได้เงินเข้ามา\n".
                "🏥 สุขภาพ: ดูแลตัวเองให้ดี พักผ่อนให้เพียงพอ\n\n".
                "🎨 สีมงคล: ม่วง, ครีม\n".
                "🔢 เลขมงคล: 2, 11, 29\n\n".
                'อยากรู้ละเอียดกว่านี้ บอกวันเดือนปีเกิดมานะคะ 🎂✨',
            ],
        ];

        $categoryResponses = $responses[$category] ?? $responses['general'];
        $index = array_rand($categoryResponses);

        return $categoryResponses[$index];
    }

    /**
     * ตรวจสอบว่าเป็นคำสั่งเช็คสิทธิ์ดูดวงหรือไม่
     */
    protected function isCheckRemainingRequest(string $text): bool
    {
        $keywords = ['เช็คสิทธิ์', 'เหลือกี่ครั้ง', 'สิทธิ์เหลือ', 'ดูสิทธิ์', 'ครั้งที่เหลือ', 'เช็คดวง', 'สถานะ'];
        $text = mb_strtolower(trim($text));

        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * แสดงสิทธิ์ดูดวงฟรีที่เหลือวันนี้
     */
    protected function handleCheckRemaining(string $facebookUserId): array
    {
        // ⚡ ดึงข้อมูลครั้งเดียว (ลด DB queries ซ้ำจาก 4 เหลือ 2)
        $maxFreeReadings = $this->settings->max_free_readings ?? self::MAX_AI_CALLS_PER_DAY;
        $usedToday = FortuneReading::countTodayReadings($facebookUserId);
        $userCredit = FortuneUserCredit::findByUser($facebookUserId);
        $price = $this->getDeepReadingPrice();

        // คำนวณสิทธิ์จากข้อมูลที่ดึงมาแล้ว (ไม่เรียก getRemainingFreeQuestions ซ้ำ)
        $normalRemaining = max(0, $maxFreeReadings - $usedToday);
        if ($userCredit) {
            if ($userCredit->isCurrentlyUnlimited()) {
                $normalRemaining = 99;
            } elseif ($userCredit->isDailyResetActive()) {
                $normalRemaining = max($normalRemaining, $maxFreeReadings);
            } else {
                $normalRemaining += $userCredit->getRemainingCredits();
            }
        }
        $remaining = $normalRemaining;

        $message = "🔮 *สิทธิ์ดูดวงของคุณวันนี้*\n";
        $message .= "═══════════════════════\n\n";
        $message .= "📊 ใช้ไปแล้ว: {$usedToday} / {$maxFreeReadings} ครั้ง\n";

        // แสดงข้อมูลเครดิตพิเศษ
        if ($userCredit && $userCredit->isCurrentlyUnlimited()) {
            $message .= "🌟 ดูดวงฟรีไม่จำกัด!\n\n";
        } elseif ($userCredit && $userCredit->getRemainingCredits() > 0) {
            $bonusCredits = $userCredit->getRemainingCredits();
            $message .= "🎁 เครดิตฟรีเพิ่มเติม: {$bonusCredits} ครั้ง\n";
            $message .= "✅ เหลือฟรีรวม: {$remaining} ครั้ง\n\n";
        } else {
            $message .= "✅ เหลือฟรี: {$remaining} ครั้ง\n\n";
        }

        if ($remaining > 0) {
            $message .= "💡 พิมพ์คำถามมาได้เลยนะคะ\n";
            $message .= "ไม่ว่าจะเรื่องความรัก การงาน การเงิน สุขภาพ\n";
            $message .= 'จันทราพร้อมทำนายให้ค่ะ 🔮✨';
        } else {
            $message .= "⏰ สิทธิ์ฟรีวันนี้หมดแล้วค่ะ\n";
            if ($this->settings->isDeepReadingEnabled()) {
                $message .= "กลับมาใหม่พรุ่งนี้ หรือ\n\n";
                $message .= "💎 *ดูดวงละเอียด เริ่มต้น {$price} บาท*\n";
                $message .= "📌 ถามได้ 2 คำถาม วิเคราะห์จากวันเกิด\n";
                $message .= "📌 พร้อมสีมงคล เลขมงคล ฤกษ์ดี\n\n";
                $message .= 'กดปุ่มด้านล่างเพื่อเริ่มค่ะ 👇';
            } else {
                $message .= 'กลับมาใหม่พรุ่งนี้ได้นะคะ 🙏';
            }
        }

        return [
            'action' => 'check_remaining',
            'message' => $message,
            'reading' => null,
            'remaining' => $remaining,
            'used' => $usedToday,
            'total' => $maxFreeReadings,
            'is_unlimited' => $userCredit && $userCredit->isCurrentlyUnlimited(),
        ];
    }

    /**
     * ตรวจสอบว่าเป็นคำขอดูคำทำนายล่าสุดหรือไม่
     *
     * ตรวจสอบว่าเป็นคำขอ "ไว้ดูทีหลัง" หรือไม่
     *
     * รองรับจากปุ่ม quick reply หลังคำทำนายพร้อม
     */
    protected function isViewLaterRequest(string $text): bool
    {
        $keywords = [
            'ไว้ดูทีหลัง', 'ดูทีหลัง', 'ไว้ก่อน', 'เดี๋ยวดู',
        ];
        $text = mb_strtolower(trim($text));

        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * จัดการคำขอ "ไว้ดูทีหลัง" — แจ้งว่าพิมพ์ "ดูคำทำนาย" ได้ทุกเมื่อ
     */
    protected function handleViewLater(string $facebookUserId): array
    {
        return [
            'action' => 'view_later',
            'message' => "ได้เลยค่ะ! เมื่อพร้อมดู พิมพ์ 'ดูคำทำนาย' ได้ทุกเมื่อนะคะ 🔮",
            'reading' => null,
        ];
    }

    /**
     * ตรวจสอบว่าเป็นคำขอดูคำทำนายล่าสุดหรือไม่
     *
     * รองรับคำสั่ง:
     * - "ดูคำทำนาย" / "ดูผลทำนาย" / "ดูผล" / "ผลทำนาย"
     * - "คำทำนายล่าสุด" / "ดูดวงล่าสุด"
     * - "ดูผลดูดวง" / "ผลดูดวง"
     */
    protected function isViewLastReadingRequest(string $text): bool
    {
        $keywords = [
            'ดูคำทำนาย', 'ดูผลทำนาย', 'ดูผล', 'ผลทำนาย', 'ผลดูดวง',
            'คำทำนายล่าสุด', 'ดูดวงล่าสุด', 'ดูผลดูดวง', 'ผลล่าสุด',
            'ดูผลล่าสุด', 'ขอดูผล', 'ขอดูคำทำนาย',
        ];
        $text = mb_strtolower(trim($text));

        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * แสดงคำทำนายล่าสุดของผู้ใช้
     *
     * กรณีที่เป็นไปได้:
     * 1. ชำระเงินแล้ว + มี deep_response → ส่งคำทำนายละเอียด
     * 2. ชำระเงินแล้ว + ยังไม่มี deep_response → แจ้งว่ากำลังประมวลผล
     * 3. ไม่เสียเงิน + มี basic_response → ส่งคำทำนายพื้นฐาน
     * 4. ไม่มีคำทำนายเลย → แจ้งไม่พบ
     */
    protected function handleViewLastReading(string $facebookUserId): array
    {
        // ดึงคำทำนายล่าสุด (ไม่รวม conversation ที่อยู่ระหว่างดำเนินการ)
        $lastReading = FortuneReading::where('facebook_user_id', $facebookUserId)
            ->where(function ($q) {
                $q->whereNotNull('basic_response')
                    ->orWhereNotNull('deep_response');
            })
            ->latest()
            ->first();

        // ถ้าไม่มีคำทำนาย → เช็คว่ามีบิลที่ชำระเงินแล้วแต่ยังไม่ได้คำทำนายหรือไม่
        if (! $lastReading) {
            $paidButNoResponse = FortuneReading::where('facebook_user_id', $facebookUserId)
                ->where('is_paid', true)
                ->whereNull('deep_response')
                ->latest()
                ->first();

            if ($paidButNoResponse) {
                return [
                    'action' => 'view_reading_processing',
                    'message' => "🔮 คุณมีบิลที่ชำระเงินแล้วค่ะ\n"
                        ."📋 เลขที่บิล: {$paidButNoResponse->bill_reference}\n\n"
                        ."⏳ ระบบกำลังสร้างคำทำนายให้อยู่ค่ะ\n"
                        ."กรุณารอสักครู่ หรือทักแชทแอดมินหากรอนานเกิน 5 นาทีนะคะ 🙏",
                    'reading' => $paidButNoResponse,
                ];
            }

            return [
                'action' => 'view_reading_empty',
                'message' => "🔮 ยังไม่มีคำทำนายค่ะ\n\n"
                    ."พิมพ์คำถามมาได้เลยนะคะ\n"
                    .'จันทราพร้อมดูดวงให้ค่ะ ✨',
                'reading' => null,
            ];
        }

        // กรณี 1: ชำระเงินแล้ว + มี deep_response
        if ($lastReading->is_paid && ! empty($lastReading->deep_response)) {
            $name = $lastReading->facebook_user_name ?? 'คุณ';

            $message = "🌟 *คำทำนายเชิงลึกล่าสุดของคุณ{$name}*\n";
            $message .= '📋 เลขที่บิล: '.($lastReading->bill_reference ?? '-')."\n";
            $message .= '📅 วันที่: '.$lastReading->created_at->format('d/m/Y H:i')."\n";
            $message .= "═══════════════════════\n\n";
            $message .= $lastReading->deep_response;

            return [
                'action' => 'view_reading_deep',
                'message' => $message,
                'reading' => $lastReading,
                'chart_image_url' => $lastReading->reading_image_url,
            ];
        }

        // กรณี 2: ชำระเงินแล้ว + ยังไม่มี deep_response (กำลังประมวลผล)
        if ($lastReading->is_paid && empty($lastReading->deep_response)) {
            return [
                'action' => 'view_reading_processing',
                'message' => "🔮 คำทำนายเชิงลึกกำลังประมวลผลค่ะ\n"
                    ."📋 เลขที่บิล: {$lastReading->bill_reference}\n\n"
                    ."⏳ ระบบ AI กำลังสร้างคำทำนายให้อยู่ค่ะ\n"
                    ."ใช้เวลาประมาณ 1-2 นาที\n\n"
                    ."💡 พิมพ์ 'ดูผล' อีกครั้งเพื่อเช็คสถานะได้นะคะ\n"
                    .'หรือทักแชทแอดมินหากรอนานเกิน 5 นาที 🙏',
                'reading' => $lastReading,
            ];
        }

        // กรณี 3: มี basic_response (ไม่เสียเงิน)
        if (! empty($lastReading->basic_response)) {
            $name = $lastReading->facebook_user_name ?? 'คุณ';

            $message = "🔮 *คำทำนายล่าสุดของคุณ{$name}*\n";
            $message .= '📅 วันที่: '.$lastReading->created_at->format('d/m/Y H:i')."\n";
            $message .= "═══════════════════════\n\n";
            $message .= $lastReading->basic_response;

            // ชวน upsell ถ้าเปิดอยู่
            if ($this->settings->isDeepReadingEnabled()) {
                $price = $this->getDeepReadingPrice();
                $message .= "\n\n═══════════════════════\n";
                $message .= "💎 อยากรู้ลึกกว่านี้? ดูดวงละเอียดเริ่มต้น {$price} บาท\n";
                $message .= "พิมพ์ 'ดูดวงละเอียด' ได้เลยค่ะ ✨";
            }

            return [
                'action' => 'view_reading_basic',
                'message' => $message,
                'reading' => $lastReading,
            ];
        }

        // Fallback
        return [
            'action' => 'view_reading_empty',
            'message' => "🔮 ยังไม่มีคำทำนายค่ะ พิมพ์คำถามมาได้เลยนะคะ ✨",
            'reading' => null,
        ];
    }

    /**
     * ปิด conversation ที่ยังค้างอยู่ทั้งหมดของผู้ใช้
     *
     * ป้องกัน orphan conversations ที่ทำให้ findActiveConversation() สับสน
     * เรียกก่อนสร้าง conversation ใหม่เสมอ
     */
    protected function closeAllActiveConversations(string $facebookUserId): int
    {
        // ✅ ยกเลิกบิล (UniquePaymentAmount) ของ reading ที่ pending_payment ก่อน
        $pendingReadings = FortuneReading::where('facebook_user_id', $facebookUserId)
            ->where('conversation_status', FortuneReading::STATUS_PENDING_PAYMENT)
            ->where('is_paid', false)
            ->whereNotNull('unique_payment_amount_id')
            ->with('uniquePaymentAmount')
            ->get();

        foreach ($pendingReadings as $pendingReading) {
            if ($pendingReading->uniquePaymentAmount && $pendingReading->uniquePaymentAmount->status === 'reserved') {
                $pendingReading->uniquePaymentAmount->cancel();

                Log::info('Fortune: ยกเลิกบิล UniquePaymentAmount เนื่องจากลูกค้ากดยกเลิก', [
                    'facebook_user_id' => $facebookUserId,
                    'reading_id' => $pendingReading->id,
                    'bill_reference' => $pendingReading->bill_reference,
                    'unique_amount_id' => $pendingReading->unique_payment_amount_id,
                    'amount' => $pendingReading->amount_paid,
                ]);
            }
        }

        // ปิดทุก conversation ที่ค้างอยู่
        $closed = FortuneReading::where('facebook_user_id', $facebookUserId)
            ->whereIn('conversation_status', [
                FortuneReading::STATUS_AWAITING_CONFIRMATION,
                FortuneReading::STATUS_BASIC_DONE,
                FortuneReading::STATUS_COLLECTING_BIRTHDATE,
                FortuneReading::STATUS_COLLECTING_QUESTIONS,
                FortuneReading::STATUS_PENDING_PAYMENT,
                FortuneReading::STATUS_NEW,
            ])
            ->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

        if ($closed > 0) {
            Log::info('Fortune: ปิด conversation เก่าที่ค้างอยู่', [
                'facebook_user_id' => $facebookUserId,
                'closed_count' => $closed,
                'cancelled_bills' => $pendingReadings->count(),
            ]);

            // ส่ง FCM push ไปบอกแอพ SMS Checker ว่าบิลถูกยกเลิก
            // เพื่อให้แอพอัพเดทสถานะทันทีโดยไม่ต้องรอ polling cycle
            try {
                $fcmService = app(FcmNotificationService::class);
                foreach ($pendingReadings as $cancelledReading) {
                    $fcmService->notifyFortuneReadingCancelled($cancelledReading);
                }
            } catch (\Exception $e) {
                Log::warning('Fortune: FCM cancelled notification failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $closed;
    }

    /**
     * ถามผู้ใช้ก่อนว่าจะดูดวงไหม พร้อมแจ้งสิทธิ์ฟรีที่เหลือวันนี้
     *
     * สร้าง reading ในสถานะ awaiting_confirmation แล้วส่งข้อความถาม
     * เก็บข้อความต้นฉบับไว้ใน conversation_state เพื่อใช้ตอนทำนายจริง
     *
     * @param  string  $messageText  ข้อความต้นฉบับจากผู้ใช้
     */
    protected function askFortuneConfirmation(string $facebookUserId, string $messageText, ?array $userProfile = null): array
    {
        // ⚡ ใช้ profile ที่ส่งมาจาก FortuneChannelManager (ลดการเรียก API ซ้ำ)
        // ไม่เรียก facebookService->getUserProfile อีก เพราะ:
        // 1. LINE user → facebookService จะเรียก Facebook API ผิด platform
        // 2. profile ถูก fetch แล้วใน FortuneChannelManager::processMessage()
        if (! is_array($userProfile) || empty($userProfile)) {
            $userProfile = [
                'name' => 'คุณ',
                'id' => $facebookUserId,
            ];
        }

        $name = $userProfile['name'] ?? 'คุณ';
        $remaining = $this->getRemainingFreeQuestions($facebookUserId);
        $userCredit = FortuneUserCredit::findByUser($facebookUserId);

        // ✅ ปิด conversation เก่าที่ยังค้างอยู่ทั้งหมดก่อนสร้างใหม่
        $this->closeAllActiveConversations($facebookUserId);

        // สร้าง reading ในสถานะรอยืนยัน เก็บข้อความต้นฉบับไว้
        $reading = FortuneReading::create([
            'facebook_user_id' => $facebookUserId,
            'facebook_user_name' => $name,
            'user_profile' => $userProfile,
            'questions' => [$messageText],
            'reading_type' => 'basic',
            'conversation_status' => FortuneReading::STATUS_AWAITING_CONFIRMATION,
            'response_type' => 'private_message',
            'ai_response' => '',
            'ai_provider' => '',
        ]);

        // เก็บข้อความต้นฉบับไว้ใน state เพื่อส่งให้ AI ตอนยืนยัน
        $reading->setConversationState('original_message', $messageText);

        // สร้างข้อความแจ้งสิทธิ์ฟรี
        $message = "🔮 สวัสดีค่ะ คุณ{$name} ✨\n\n";
        $message .= "เพจดูดวงหมอจันทรายินดีต้อนรับค่ะ\n\n";

        if ($userCredit && $userCredit->isCurrentlyUnlimited()) {
            $message .= "🌟 คุณมีสิทธิ์ดูดวงฟรีไม่จำกัด! (โปรโมชั่นพิเศษ)\n\n";
        } elseif ($remaining >= 99) {
            $message .= "🌟 คุณมีสิทธิ์ดูดวงฟรีไม่จำกัด!\n\n";
        } elseif ($remaining > 0) {
            $message .= "📊 วันนี้คุณมีสิทธิ์ดูดวงฟรี {$remaining} ครั้งค่ะ\n\n";
        } else {
            $message .= "⏰ สิทธิ์ฟรีวันนี้หมดแล้วค่ะ\n\n";
        }

        if ($remaining > 0) {
            $message .= "💫 จะให้จันทราดูดวงให้ไหมคะ?\n";
            $message .= "ไม่ว่าจะเรื่อง ความรัก 💕 การงาน 💼 การเงิน 💰 สุขภาพ 🏥\n\n";
            $message .= 'กดเลือกด้านล่าง หรือพิมพ์คำถามมาได้เลยค่ะ 👇';
        } else {
            // สิทธิ์ฟรีหมด → ปิด conversation แล้วแนะนำดูดวงละเอียด
            $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

            if ($this->settings->isDeepReadingEnabled()) {
                $price = $this->getDeepReadingPrice();
                $message .= "กลับมาใหม่พรุ่งนี้ได้นะคะ หรือ\n\n";
                $message .= "💎 *ดูดวงละเอียด เริ่มต้น {$price} บาท*\n";
                $message .= "📌 ถามได้ 2 คำถาม วิเคราะห์จากวันเกิด\n";
                $message .= "📌 พร้อมสีมงคล เลขมงคล ฤกษ์ดี\n\n";
                $message .= 'กดปุ่มด้านล่างเพื่อเริ่มค่ะ 👇';
            } else {
                $message .= 'กลับมาใหม่พรุ่งนี้ได้นะคะ 🙏';
            }
        }

        return [
            'action' => 'awaiting_confirmation',
            'message' => $message,
            'reading' => $reading,
            'show_quick_replies' => ($remaining > 0),
            'remaining' => $remaining,
        ];
    }

    /**
     * จัดการเมื่อผู้ใช้ตอบกลับจากการถามยืนยันดูดวง
     *
     * ถ้ายืนยัน → ดึงข้อความต้นฉบับจาก state แล้วส่งให้ AI ทำนาย
     * ถ้าปฏิเสธ → ปิด conversation แล้วบอกลา
     * ถ้าพิมพ์อย่างอื่น → ถือเป็นข้อความใหม่ เริ่มทำนายเลย
     */
    protected function handleConfirmationResponse(FortuneReading $reading, string $facebookUserId, string $messageText, ?array $userProfile = null): array
    {
        $name = $reading->facebook_user_name ?? 'คุณ';

        // ถ้าผู้ใช้ปฏิเสธ → ปิด conversation
        if ($this->isDeclineResponse($messageText)) {
            $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

            return [
                'action' => 'declined',
                'message' => "🔮 ไม่เป็นไรค่ะ คุณ{$name}\n\n".
                             "เมื่อไหร่อยากดูดวง ทักมาหาจันทราได้ตลอดนะคะ ✨\n".
                             'ขอให้โชคดีค่ะ 🙏',
                'reading' => $reading,
            ];
        }

        // ถ้ายืนยัน หรือพิมพ์ข้อความอื่นเข้ามา → เริ่มทำนายเลย
        // ดึงข้อความต้นฉบับจาก state (ถ้ามี) หรือใช้ข้อความใหม่
        $originalMessage = $reading->getConversationState('original_message', $messageText);
        $awaitingType = $reading->getConversationState('awaiting_type');

        // ถ้าเป็น "รอคำถาม" (awaiting_type=question) → ใช้ข้อความใหม่เสมอ (เพราะผู้ใช้เลือกหัวข้อ/พิมพ์คำถาม)
        // ยกเว้นตอบสั้นมาก "ดู", "เอา", "ใช่" → ถือเป็นขอดูดวงทั่วไป
        if ($awaitingType === 'question') {
            // ✅ Fix: ถ้าผู้ใช้กด "ดูดวง" ซ้ำจาก Rich Menu (generic request)
            // → ไม่ส่งไป AI ทันที แต่ถามหัวข้อใหม่อีกครั้ง
            if ($this->isGenericFortuneRequest($messageText)) {
                // ปิด reading เก่า
                $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

                // ถามหัวข้อใหม่
                return $this->askForQuestionBeforeReading($facebookUserId, $messageText, $userProfile);
            }

            // ถ้าพิมพ์คำถามใหม่ (เช่น "ดูดวงความรัก", "การเงินปีนี้") → ใช้เป็นคำถาม
            $questionText = $messageText;

            // V3: ถ้าตอบสั้นมาก "ดู", "เอา", "ดูเลย" → ใช้ original_message ที่เก็บไว้
            // เพราะผู้ใช้อาจพิมพ์คำถามเฉพาะมาก่อนแล้ว (เช่น "ดวงการเงินปีนี้")
            $shortConfirms = ['ดู', 'เอา', 'ใช่', 'ได้', 'ok', 'yes', 'ตกลง', 'โอเค', 'ดูเลย', 'ดูค่ะ', 'ดูครับ', 'เอาค่ะ', 'เอาครับ'];
            $textLower = mb_strtolower(trim($messageText));
            foreach ($shortConfirms as $sc) {
                if ($textLower === $sc) {
                    // ใช้คำถามเดิมที่ผู้ใช้เคยพิมพ์มา ถ้ามีและไม่ใช่ generic
                    if ($originalMessage && ! $this->isGenericFortuneRequest($originalMessage)) {
                        $questionText = $originalMessage;
                    } else {
                        $questionText = 'ดูดวงรวมทุกด้าน';
                    }
                    break;
                }
            }
        } else {
            // flow ปกติ (ยืนยันดูดวง) → ถ้าตอบสั้น ใช้ original, ถ้าพิมพ์ใหม่ ใช้ใหม่
            $isSimpleConfirm = $this->isSimpleConfirmResponse($messageText);
            $questionText = $isSimpleConfirm ? $originalMessage : $messageText;
        }

        // ปิด reading ที่รอยืนยันนี้ (จะสร้างใหม่ใน startNewConversation)
        $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

        // ตรวจสอบ limit อีกครั้งก่อนส่งให้ AI
        if (! $this->canMakeAICall($facebookUserId)) {
            return [
                'action' => 'ai_limit',
                'message' => $this->getAILimitMessage(),
                'reading' => null,
            ];
        }

        // เริ่มทำนายจริง
        return $this->startNewConversation($facebookUserId, $questionText, $userProfile);
    }

    /**
     * ตรวจสอบว่าผู้ใช้ปฏิเสธดูดวงหรือไม่
     */
    protected function isDeclineResponse(string $text): bool
    {
        $declineKeywords = ['ไม่', 'ไม่เอา', 'ไม่ต้อง', 'ไม่ต้องการ', 'ยังก่อน', 'ไว้ก่อน', 'ไม่ดู', 'no'];
        $text = mb_strtolower(trim($text));

        foreach ($declineKeywords as $keyword) {
            if ($text === $keyword || str_starts_with($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ตรวจสอบว่าผู้ใช้ตอบยืนยันแบบสั้นๆ (ดู, เอา, ใช่ ฯลฯ)
     * ใช้เพื่อแยกว่าเป็นการยืนยันหรือเป็นคำถามใหม่
     */
    protected function isSimpleConfirmResponse(string $text): bool
    {
        $confirmKeywords = ['ดู', 'เอา', 'ใช่', 'ได้', 'ok', 'yes', 'ตกลง', 'โอเค', 'อยาก', 'ดูเลย', 'ดูดวง', 'เอาเลย', 'ต้องการ', 'ดูค่ะ', 'ดูครับ', 'เอาค่ะ', 'เอาครับ'];
        $text = mb_strtolower(trim($text));

        foreach ($confirmKeywords as $keyword) {
            if ($text === $keyword || str_starts_with($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ตรวจสอบว่าข้อความมีคำเกี่ยวกับดูดวงหรือไม่
     * ใช้เพื่อตัดสินว่าควรข้ามขั้นตอนยืนยัน (confirmation) แล้วเริ่มทำนายทันที
     */
    protected function containsFortuneKeyword(string $text): bool
    {
        $textLower = mb_strtolower(trim($text));

        foreach (self::FORTUNE_RELATED_KEYWORDS as $keyword) {
            if (str_contains($textLower, mb_strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    /**
     * ตรวจสอบว่าเป็นคำขอดูดวงแบบกว้างๆ ไม่มีคำถามเฉพาะเจาะจง
     *
     * เช่น "ดูดวง", "ดูดวงค่ะ", "ทำนาย", "หมอดู" → true
     * แต่ "ดูดวงความรัก", "จะมีแฟนไหม", "การเงินปีหน้า" → false
     *
     * ใช้เพื่อตัดสินว่าควรถามคำถามก่อนเข้า AI หรือเข้าเลย
     */
    protected function isGenericFortuneRequest(string $text): bool
    {
        // คำขอดูดวงแบบกว้างๆ (ไม่มีคำถามเจาะจง)
        $genericPatterns = [
            'ดูดวง', 'ดูดวงค่ะ', 'ดูดวงครับ', 'ดูดวงหน่อย', 'ดูดวงให้หน่อย',
            'ทำนาย', 'ทำนายค่ะ', 'ทำนายครับ', 'ทำนายให้หน่อย',
            'หมอดู', 'อยากดูดวง', 'ขอดูดวง', 'ดูดวงด้วย',
            'ดูดวงเลย', 'ดูดวงสิ', 'ดูดวงที', 'ดูดวงนะ',
        ];

        $textClean = mb_strtolower(trim($text));
        // ลบคำลงท้าย (ค่ะ, ครับ, นะ, หน่อย, จ้า ฯลฯ) เพื่อเปรียบเทียบ
        $textNormalized = preg_replace('/\s*(ค่ะ|ครับ|คะ|จ้า|จ้ะ|จ๊ะ|นะ|นะคะ|นะครับ|หน่อย|ด้วย|ที|สิ|เลย)\s*$/u', '', $textClean);

        foreach ($genericPatterns as $pattern) {
            if ($textClean === mb_strtolower($pattern) || $textNormalized === mb_strtolower($pattern)) {
                return true;
            }
        }

        // ถ้ามีแค่ 1-2 คำที่เกี่ยวกับดวง (สั้นมาก ≤15 ตัวอักษร) → ถือเป็น generic
        // เช่น "ดูดวง" = 6 chars, "ทำนาย" = 6 chars
        if (mb_strlen($textNormalized) <= 15) {
            $coreFortuneWords = ['ดูดวง', 'ทำนาย', 'หมอดู', 'ดวง', 'ไพ่', 'ทาโรต์'];
            foreach ($coreFortuneWords as $word) {
                if ($textNormalized === mb_strtolower($word)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * ถามผู้ใช้ว่าอยากถามเรื่องอะไร ก่อนเข้าสู่การทำนาย
     *
     * V3: ทุกกรณีต้องรับคำถามก่อน → ทำนายตามคำถาม → ชวนดูเชิงลึก
     * - ถ้าผู้ใช้พิมพ์คำถามเฉพาะมาแล้ว (เช่น "ดวงการเงินปีนี้") → เก็บไว้ + ถามยืนยัน
     * - ถ้าพิมพ์แค่ "ดูดวง" เฉยๆ → ถามให้เลือกหัวข้อ
     */
    protected function askForQuestionBeforeReading(string $facebookUserId, string $messageText, ?array $userProfile = null): array
    {
        if (! is_array($userProfile) || empty($userProfile)) {
            $userProfile = [
                'name' => 'คุณ',
                'id' => $facebookUserId,
            ];
        }

        $name = $userProfile['name'] ?? 'คุณ';
        $remaining = $this->getRemainingFreeQuestions($facebookUserId);

        // เช็คสิทธิ์ก่อน
        if (! $this->canMakeAICall($facebookUserId)) {
            return [
                'action' => 'ai_limit',
                'message' => $this->getAILimitMessage(),
                'reading' => null,
            ];
        }

        // ปิด conversation เก่า
        $this->closeAllActiveConversations($facebookUserId);

        // ตรวจสอบว่าผู้ใช้มีคำถามเฉพาะแล้วหรือยัง
        $hasSpecificQuestion = ! $this->isGenericFortuneRequest($messageText);

        // สร้าง reading ในสถานะรอคำถาม
        $reading = FortuneReading::create([
            'facebook_user_id' => $facebookUserId,
            'facebook_user_name' => $name,
            'user_profile' => $userProfile,
            'questions' => [$messageText],
            'reading_type' => 'basic',
            'conversation_status' => FortuneReading::STATUS_AWAITING_CONFIRMATION,
            'response_type' => 'private_message',
            'ai_response' => '',
            'ai_provider' => '',
        ]);

        // เก็บว่าเป็น "รอคำถาม" (ไม่ใช่รอยืนยัน)
        $reading->setConversationState('awaiting_type', 'question');
        $reading->setConversationState('original_message', $messageText);

        // V3: ถ้ามีคำถามเฉพาะแล้ว → แจ้งว่ารับคำถามแล้ว + ถามยืนยัน
        if ($hasSpecificQuestion) {
            $message = "🔮 คุณ{$name} ถามว่า: \"{$messageText}\"\n\n";
            $message .= "✨ จันทราพร้อมทำนายให้แล้วค่ะ\n\n";

            if ($remaining < 99) {
                $message .= "📊 สิทธิ์ฟรีคงเหลือ: {$remaining} ครั้ง\n\n";
            }

            $message .= "กด \"ดูเลย\" เพื่อรับคำทำนาย\nหรือพิมพ์คำถามใหม่ได้ค่ะ 👇";

            return [
                'action' => 'awaiting_confirmation',
                'message' => $message,
                'reading' => $reading,
                'show_quick_replies' => true,
                'remaining' => $remaining,
                'quick_replies' => [
                    ['label' => '✨ ดูเลย', 'text' => 'ดูเลย'],
                    ['label' => '💕 เปลี่ยนเป็นความรัก', 'text' => 'ดูดวงความรัก'],
                    ['label' => '💼 เปลี่ยนเป็นการงาน', 'text' => 'ดูดวงการงาน'],
                    ['label' => '🌟 ดวงรวมทุกด้าน', 'text' => 'ดูดวงรวมทุกด้าน'],
                ],
            ];
        }

        // ไม่มีคำถามเฉพาะ → ถามให้เลือกหัวข้อ
        $message = "🔮 สวัสดีค่ะ คุณ{$name}\n\n";
        $message .= "จันทราพร้อมทำนายให้แล้วค่ะ ✨\n\n";
        $message .= "📝 อยากถามเรื่องอะไรคะ? พิมพ์มาได้เลย\n";
        $message .= "เช่น:\n";
        $message .= "💕 \"ความรักปีนี้จะเป็นยังไง\"\n";
        $message .= "💼 \"การงานจะดีขึ้นไหม\"\n";
        $message .= "💰 \"การเงินเดือนนี้เป็นยังไง\"\n";
        $message .= "🏥 \"สุขภาพช่วงนี้ต้องระวังอะไร\"\n\n";

        if ($remaining < 99) {
            $message .= "📊 สิทธิ์ฟรีคงเหลือ: {$remaining} ครั้ง\n\n";
        }

        $message .= "👇 พิมพ์คำถาม หรือเลือกหัวข้อด้านล่างค่ะ";

        return [
            'action' => 'awaiting_confirmation',
            'message' => $message,
            'reading' => $reading,
            'show_quick_replies' => true,
            'remaining' => $remaining,
            'quick_replies' => [
                ['label' => '💕 ความรัก', 'text' => 'ดูดวงความรัก'],
                ['label' => '💼 การงาน', 'text' => 'ดูดวงการงาน'],
                ['label' => '💰 การเงิน', 'text' => 'ดูดวงการเงิน'],
                ['label' => '🌟 ดวงรวม', 'text' => 'ดูดวงรวมทุกด้าน'],
            ],
        ];
    }

    /**
     * สร้างบริบทจากประวัติผู้ใช้ (Personalization)
     * ดึงสรุปคำทำนายก่อนหน้าให้ AI ใช้อ้างอิง
     *
     * @return string บริบทสำหรับใส่ใน prompt
     */
    protected function buildUserContext(string $facebookUserId): string
    {
        try {
            // ดึงคำทำนาย 5 ครั้งล่าสุดของผู้ใช้
            $previousReadings = FortuneReading::where('facebook_user_id', $facebookUserId)
                ->where('conversation_status', FortuneReading::STATUS_COMPLETED)
                ->whereNotNull('basic_response')
                ->where('basic_response', '!=', '')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get(['questions', 'categories', 'reading_type', 'birth_date', 'created_at']);

            if ($previousReadings->isEmpty()) {
                return '';
            }

            $context = "\n[ประวัติผู้ใช้คนนี้ - ใช้เป็นบริบทในการทำนาย ทำให้รู้สึกว่าจำเรื่องเก่าได้]\n";

            // นับหมวดที่ถามบ่อย
            $categoryCount = [];
            foreach ($previousReadings as $reading) {
                $cats = $reading->categories ?? [];
                foreach ($cats as $cat) {
                    $categoryCount[$cat] = ($categoryCount[$cat] ?? 0) + 1;
                }
            }

            $context .= "- เคยดูดวง {$previousReadings->count()} ครั้ง\n";

            if (! empty($categoryCount)) {
                arsort($categoryCount);
                $topCategories = array_slice($categoryCount, 0, 3, true);
                $catStr = implode(', ', array_map(fn ($c, $n) => "{$c}({$n}ครั้ง)", array_keys($topCategories), $topCategories));
                $context .= "- หมวดที่สนใจมากที่สุด: {$catStr}\n";
            }

            // วันเกิด (ถ้าเคยบอก)
            $birthDate = $previousReadings->pluck('birth_date')->filter()->first();
            if ($birthDate) {
                $context .= "- เคยบอกวันเกิด: {$birthDate}\n";
            }

            // คำถามล่าสุด 3 ข้อ
            $recentQuestions = [];
            foreach ($previousReadings->take(3) as $reading) {
                $qs = $reading->questions ?? [];
                if (! empty($qs)) {
                    $recentQuestions[] = mb_substr($qs[0], 0, 50);
                }
            }
            if (! empty($recentQuestions)) {
                $context .= '- คำถามล่าสุด: '.implode(' | ', $recentQuestions)."\n";
            }

            $context .= "- ให้ทำนายต่อยอดจากครั้งก่อนได้ เช่น \"จากที่จันทราเคยบอกไว้...\" หรือ \"จันทราจำได้ว่าครั้งก่อน...\"\n";

            return $context;
        } catch (\Exception $e) {
            Log::warning('Fortune: buildUserContext failed', ['error' => $e->getMessage()]);

            return '';
        }
    }

    /**
     * ตรวจจับหมวดคำถามอัตโนมัติจากข้อความ
     *
     * @return string|null หมวดที่ตรวจจับได้ หรือ null
     */
    protected function detectCategory(string $text): ?string
    {
        $textLower = mb_strtolower(trim($text));

        $categories = [
            'ความรัก' => ['ความรัก', 'แฟน', 'คู่ครอง', 'เนื้อคู่', 'คนรัก', 'สามี', 'ภรรยา', 'แต่งงาน', 'หย่า', 'เลิกกัน', 'รักซ้อน', 'มีคู่'],
            'การงาน' => ['การงาน', 'งาน', 'ทำงาน', 'อาชีพ', 'เปลี่ยนงาน', 'หางาน', 'เจ้านาย', 'ลูกน้อง', 'เลื่อนตำแหน่ง', 'ถูกไล่ออก', 'ธุรกิจ'],
            'การเงิน' => ['การเงิน', 'เงิน', 'รายได้', 'หนี้', 'รวย', 'จน', 'ลงทุน', 'หุ้น', 'ค้าขาย', 'ขายของ', 'กำไร', 'ขาดทุน'],
            'สุขภาพ' => ['สุขภาพ', 'ป่วย', 'โรค', 'อุบัติเหตุ', 'เจ็บ', 'อายุยืน'],
            'โชคลาภ' => ['โชคลาภ', 'หวย', 'ลอตเตอรี่', 'เลขเด็ด', 'โชค', 'ลาภ', 'ถูกหวย'],
            'การเรียน' => ['การเรียน', 'สอบ', 'เรียน', 'มหาวิทยาลัย', 'สอบติด', 'สอบตก'],
        ];

        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($textLower, $keyword)) {
                    return $category;
                }
            }
        }

        return null;
    }

    /**
     * เริ่มต้น conversation ใหม่ - ทำนายพื้นฐานฟรี
     */
    protected function startNewConversation(string $facebookUserId, string $messageText, ?array $userProfile = null): array
    {
        // ⚡ ใช้ profile ที่ส่งมาจาก FortuneChannelManager (ลดการเรียก API ซ้ำ)
        if (! is_array($userProfile) || empty($userProfile)) {
            $userProfile = [
                'name' => 'คุณ',
                'id' => $facebookUserId,
            ];
        }

        // ✅ ปิด conversation เก่าที่ยังค้างอยู่ทั้งหมดก่อนสร้างใหม่
        // ป้องกัน orphan conversations ที่ทำให้ findActiveConversation() สับสน
        $this->closeAllActiveConversations($facebookUserId);

        $reading = null;
        $name = $userProfile['name'] ?? 'คุณ';

        try {
            // สร้าง FortuneReading ใหม่ (ใส่ค่าเริ่มต้นให้ ai_response/ai_provider ป้องกัน NOT NULL error)
            $reading = FortuneReading::create([
                'facebook_user_id' => $facebookUserId,
                'facebook_user_name' => $name,
                'user_profile' => $userProfile,
                'questions' => [$messageText],
                'reading_type' => 'basic',
                'conversation_status' => FortuneReading::STATUS_NEW,
                'response_type' => 'private_message',
                'ai_response' => '',
                'ai_provider' => '',
            ]);

            // ✅ ดึงบริบทจากประวัติผู้ใช้ (Personalization)
            $userContext = $this->buildUserContext($facebookUserId);

            // ✅ ตรวจจับหมวดคำถามอัตโนมัติ
            $detectedCategory = $this->detectCategory($messageText);
            if ($detectedCategory) {
                $reading->update(['categories' => [$detectedCategory]]);
            }

            // ทำนายพื้นฐานฟรี - ใช้ retry + auto-switch provider
            $basicPrompt = $this->buildBasicPrompt($userProfile, $messageText, $userContext, $detectedCategory);

            Log::info('Fortune: กำลังเรียก AI', [
                'facebook_user_id' => $facebookUserId,
                'provider' => $this->settings->getActualAIProvider(),
                'has_api_key' => ! empty($this->settings->getActualAIApiKey()),
                'prompt_length' => mb_strlen($basicPrompt),
            ]);

            $aiResult = $this->aiService->generateWithRetryAndFallback(
                [$messageText],
                $userProfile,
                null,
                $basicPrompt,
                'basic'
            );

            // ✅ บันทึกคำทำนายพื้นฐาน (ตั้ง responded_at ก่อน recordAICall)
            // เพื่อให้ countTodayReadings() นับ reading นี้ถูกต้อง
            $reading->saveBasicReading(
                $aiResult['response'],
                $aiResult['provider'],
                $aiResult['model'],
                $aiResult['tokens_used']
            );

            // ✅ บันทึก AI call สำหรับ rate limiting (หลัง saveBasicReading เพื่อให้ responded_at ถูกตั้งก่อน)
            $this->recordAICall($facebookUserId);

            Log::info('Fortune: AI ตอบสำเร็จ', [
                'facebook_user_id' => $facebookUserId,
                'provider' => $aiResult['provider'],
                'model' => $aiResult['model'],
                'response_length' => mb_strlen($aiResult['response']),
            ]);

            // ✅ สร้าง Birth Chart / Quick Chart ส่งก่อนคำทำนาย
            $chartImageUrl = null;
            try {
                $birthDate = $reading->birth_date?->format('Y-m-d');
                if ($birthDate) {
                    $chartImageUrl = $this->chartService->generateBirthChart($birthDate, $name, $userProfile['gender'] ?? null);
                } else {
                    $chartImageUrl = $this->chartService->generateQuickChart($name);
                }
                if ($chartImageUrl) {
                    $reading->update(['reading_image_url' => $chartImageUrl]);
                }
            } catch (\Throwable $chartErr) {
                Log::error('Fortune: Chart generation failed (basic reading)', [
                    'error' => $chartErr->getMessage(),
                    'error_class' => get_class($chartErr),
                    'reading_id' => $reading->id ?? null,
                ]);
            }

            // สร้างข้อความเชิญชวนดูดวงละเอียด
            $upsellMessage = $this->getUpsellMessage($name);

            // แสดงจำนวนสิทธิ์ฟรีที่เหลือ (รวมเครดิตพิเศษจากแอดมิน/โปรโมชั่น)
            $remainingMessage = $this->getRemainingCreditsMessage($facebookUserId);

            // ทำนายฟรี → ไม่แสดงเลขบิล (bill_reference สร้างเฉพาะ deep reading)
            return [
                'action' => 'basic_done',
                'message' => $aiResult['response']."\n\n".$remainingMessage."\n\n".$upsellMessage,
                'reading' => $reading,
                'show_quick_replies' => true,
                'chart_image_url' => $chartImageUrl,
            ];

        } catch (\Exception $e) {
            Log::error('Fortune Conversation: ทำนายพื้นฐานล้มเหลว', [
                'facebook_user_id' => $facebookUserId,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace_short' => mb_substr($e->getTraceAsString(), 0, 500),
                'ai_provider' => $this->settings->getActualAIProvider(),
                'ai_model' => $this->settings->getActualAIModel(),
                'has_api_key' => ! empty($this->settings->getActualAIApiKey()),
            ]);

            // ลบ reading ที่สร้างไว้เพื่อไม่ให้นับรวมใน daily limit
            // เพราะการทำนายไม่สำเร็จ ไม่ควรหักสิทธิ์ฟรีของผู้ใช้
            if ($reading) {
                try {
                    $reading->forceDelete();
                } catch (\Exception $deleteError) {
                    Log::warning('Fortune Conversation: ลบ reading ที่ล้มเหลวไม่สำเร็จ', [
                        'reading_id' => $reading->id,
                        'error' => $deleteError->getMessage(),
                    ]);
                }
            }

            // แจ้งผู้ใช้สั้นๆ ว่าระบบมีปัญหาชั่วคราว (หลังจาก retry + สลับ provider หมดแล้ว)
            return [
                'action' => 'error',
                'message' => "🔮 คุณ{$name} คะ ขออภัยนะคะ ระบบกำลังปรับปรุงชั่วคราวค่ะ 🙏\n\n".
                             "กรุณาลองพิมพ์มาใหม่อีกครั้งในอีก 1-2 นาทีนะคะ\n".
                             'จันทราพร้อมดูดวงให้ค่ะ ✨',
                'reading' => null,
            ];
        }
    }

    /**
     * ดำเนินการต่อ conversation ที่มีอยู่
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
     */
    protected function handleAfterBasic(FortuneReading $reading, string $messageText): array
    {
        // ตรวจสอบว่าต้องการดูดวงละเอียดหรือไม่
        if ($this->isDeepReadingAccepted($messageText)) {
            // ✅ ตรวจสอบว่าเปิดใช้งานดูดวงละเอียดหรือไม่
            if (! $this->settings->isDeepReadingEnabled()) {
                $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

                return [
                    'action' => 'deep_reading_disabled',
                    'message' => "🔮 ขออภัยค่ะ บริการดูดวงละเอียดถูกปิดการใช้งานชั่วคราวค่ะ\n\n".
                                 "💫 สามารถดูดวงทั่วไปฟรีได้ตามปกตินะคะ\n".
                                 "พิมพ์คำถามมาได้เลยค่ะ หรือพิมพ์ 'ดูดวง' เพื่อเริ่มใหม่ 🙏",
                    'reading' => $reading,
                ];
            }

            // ⚠️ เปลี่ยน reading_type เป็น 'deep' + สร้าง bill_reference
            // เพราะ reading เดิมเป็น basic → ต้องแปลงให้เป็น deep reading
            // boot creating event ไม่ fire ตอน update ดังนั้นต้องสร้าง bill_reference เอง
            $updateData = [
                'reading_type' => 'deep',
                'conversation_status' => FortuneReading::STATUS_COLLECTING_BIRTHDATE,
            ];
            if (empty($reading->bill_reference)) {
                $updateData['bill_reference'] = FortuneReading::generateBillReference();
            }
            $reading->update($updateData);

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
     * เริ่ม flow ดูดวงละเอียด (บริการเสียเงิน) — สร้าง reading ใหม่ + ถามวันเกิด
     *
     * ใช้เมื่อผู้ใช้กดปุ่ม "💎 ดูดวงละเอียด" โดยไม่มี active reading (เช่น หลังจาก ai_limit)
     * ข้าม canMakeAICall() เพราะเป็นบริการเสียเงิน ไม่ใช่บริการฟรี
     *
     * @param string $facebookUserId
     * @param array|null $userProfile
     * @return array
     */
    protected function startDeepReadingFlow(string $facebookUserId, ?array $userProfile = null): array
    {
        try {
            // ✅ ตรวจสอบว่าเปิดใช้งานดูดวงละเอียดหรือไม่
            if (! $this->settings->isDeepReadingEnabled()) {
                Log::info('Fortune: ผู้ใช้ขอดูดวงละเอียด แต่ระบบปิดการใช้งานอยู่', [
                    'facebook_user_id' => $facebookUserId,
                ]);

                return [
                    'action' => 'deep_reading_disabled',
                    'message' => "🔮 ขออภัยค่ะ บริการดูดวงละเอียดถูกปิดการใช้งานชั่วคราวค่ะ\n\n".
                                 "💫 สามารถดูดวงทั่วไปฟรีได้ตามปกตินะคะ\n".
                                 "พิมพ์คำถามมาได้เลยค่ะ หรือพิมพ์ 'ดูดวง' เพื่อเริ่มใหม่ 🙏",
                    'reading' => null,
                ];
            }

            // ⚡ ใช้ profile จาก FortuneChannelManager (ไม่เรียก API ซ้ำ)
            if (! is_array($userProfile) || empty($userProfile)) {
                $userProfile = ['name' => 'คุณ', 'id' => $facebookUserId];
            }

            // ปิด conversation เก่าที่ยังค้างอยู่ทั้งหมด
            $this->closeAllActiveConversations($facebookUserId);

            $name = $userProfile['name'] ?? 'คุณ';

            // สร้าง FortuneReading ใหม่สำหรับ deep reading
            $reading = FortuneReading::create([
                'facebook_user_id' => $facebookUserId,
                'facebook_user_name' => $name,
                'user_profile' => $userProfile,
                'questions' => [],
                'reading_type' => 'deep',
                'conversation_status' => FortuneReading::STATUS_COLLECTING_BIRTHDATE,
                'response_type' => 'private_message',
                'ai_response' => '',
                'ai_provider' => '',
            ]);

            Log::info('Fortune: เริ่ม deep reading flow ใหม่ (ข้าม free limit)', [
                'facebook_user_id' => $facebookUserId,
                'reading_id' => $reading->id,
            ]);

            return [
                'action' => 'collecting_birthdate',
                'message' => $this->getBirthdateRequestMessage(),
                'reading' => $reading,
            ];
        } catch (\Exception $e) {
            Log::error('Fortune: เกิดข้อผิดพลาดในการเริ่ม deep reading flow', [
                'facebook_user_id' => $facebookUserId,
                'error' => $e->getMessage(),
            ]);

            return [
                'action' => 'error',
                'message' => "ขอโทษค่ะ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้งนะคะ 🙏",
                'reading' => null,
            ];
        }
    }

    /**
     * จัดการ input วันเกิด
     */
    protected function handleBirthdateInput(FortuneReading $reading, string $messageText): array
    {
        $birthDate = $this->parseBirthDate($messageText);

        if (! $birthDate) {
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
     * จัดการ input คำถาม — เก็บทีละข้อ
     *
     * รับข้อความทั้งหมดเป็น 1 คำถาม (ไม่ split อีกต่อไป)
     * ถ้ายังไม่ครบ 2 ข้อ → return action 'need_more_questions'
     * ถ้าครบ 2 ข้อ → สร้างบิลรอชำระ
     */
    protected function handleQuestionInput(FortuneReading $reading, string $messageText): array
    {
        try {
            // เก็บข้อความทั้งหมดเป็น 1 คำถาม (ไม่ split เหมือนเดิม)
            $question = trim($messageText);
            if (! empty($question)) {
                $reading->addQuestion($question);
            }

            $collectedQuestions = $reading->getCollectedQuestions();
            $questionCount = count($collectedQuestions);

            Log::info('Fortune: handleQuestionInput', [
                'reading_id' => $reading->id,
                'question_count' => $questionCount,
                'required' => self::REQUIRED_QUESTIONS,
                'text_preview' => mb_substr($messageText, 0, 40),
            ]);

            if ($questionCount < self::REQUIRED_QUESTIONS) {
                $nextNumber = $questionCount + 1;

                return [
                    'action' => 'need_more_questions',
                    'message' => "✅ รับคำถามข้อที่ {$questionCount} แล้วค่ะ\n\n".
                                 "📝 คำถามข้อที่ {$nextNumber} จาก ".self::REQUIRED_QUESTIONS." — เลือกหมวดหรือพิมพ์เองได้เลยค่ะ 👇",
                    'reading' => $reading,
                    'question_number' => $nextNumber,
                ];
            }

            // ได้ครบ 2 คำถามแล้ว → สร้างบิลรอชำระ
            Log::info('Fortune: ครบ 2 คำถาม กำลังสร้างบิล', [
                'reading_id' => $reading->id,
                'questions' => $collectedQuestions,
            ]);

            return $this->createPaymentBill($reading, $collectedQuestions);

        } catch (\Exception $e) {
            Log::error('Fortune: handleQuestionInput ล้มเหลว', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
                'error_file' => $e->getFile().':'.$e->getLine(),
                'trace' => mb_substr($e->getTraceAsString(), 0, 500),
            ]);

            // Re-throw เพื่อให้ processMessage catch handler จัดการ
            throw $e;
        }
    }

    /**
     * จัดการเมื่อรอชำระเงิน
     */
    protected function handlePendingPayment(FortuneReading $reading, string $messageText): array
    {
        // ตรวจสอบยอดเงินว่าหมดอายุหรือยัง
        $uniqueAmount = $reading->uniquePaymentAmount;

        if (! $uniqueAmount || $uniqueAmount->expires_at < now()) {
            // บิลหมดอายุ → ปิด conversation กลับไปแชทปกติ
            $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

            Log::info('Fortune: บิลดูดวงละเอียดหมดอายุ กลับเป็นแชทปกติ', [
                'reading_id' => $reading->id,
                'facebook_user_id' => $reading->facebook_user_id,
            ]);

            return [
                'action' => 'payment_expired',
                'message' => "⏰ บิลดูดวงละเอียดหมดอายุแล้วค่ะ\n\n".
                             "ถ้าต้องการดูดวงละเอียดอีกครั้ง พิมพ์ 'ดูดวงละเอียด' ได้เลยนะคะ\n".
                             'หรือพิมพ์คำถามใหม่มาได้เลยค่ะ จันทราพร้อมดูดวงให้ค่ะ 🔮✨',
                'reading' => $reading,
            ];
        }

        // บิลยังไม่หมดอายุ → ไม่ว่าจะพิมพ์อะไรมา แสดงยอด+บัญชีธนาคาร+เวลาเหลือ
        $payAmount = number_format($uniqueAmount->unique_amount, 2);
        $expiresAt = $uniqueAmount->expires_at->format('H:i');
        $billRef = $reading->bill_reference;

        // คำนวณเวลาที่เหลือ
        $remainingMinutes = (int) now()->diffInMinutes($uniqueAmount->expires_at, false);
        $remainingMinutes = max(0, $remainingMinutes);

        $message = "🔮 จันทรารอคำทำนายละเอียดให้อยู่ค่ะ\n\n";
        $message .= "กรุณาโอนเงินเพื่อรับคำทำนายนะคะ 🙏\n\n";
        $message .= "═══════════════════════\n";
        $message .= "💰 *ยอดชำระ: ฿{$payAmount}*\n";
        $message .= "🔖 เลขที่บิล: {$billRef}\n";
        $message .= "⏰ โอนก่อน: {$expiresAt} น.\n";
        $message .= "⏳ เหลือเวลาอีก: {$remainingMinutes} นาที\n";
        $message .= "═══════════════════════\n\n";

        // แสดงบัญชีธนาคารทุกครั้ง
        $message .= $this->getBankAccountsListMessage();

        $message .= "⚠️ *สำคัญ*: กรุณาโอนยอด ฿{$payAmount} (ตรงตามทศนิยม)\n";
        $message .= "เพื่อให้ระบบตรวจสอบอัตโนมัติได้ถูกต้อง\n\n";
        $message .= "เมื่อโอนแล้วรอสักครู่ ระบบจะส่งคำทำนายให้ทันทีค่ะ ✨\n\n";
        if ($remainingMinutes <= 10) {
            $message .= "⚡ เหลือเวลาอีก {$remainingMinutes} นาทีนะคะ รีบโอนก่อนบิลหมดอายุค่ะ\n\n";
        }
        $message .= "พิมพ์ 'ยกเลิก' หากต้องการยกเลิก";

        return [
            'action' => 'waiting_payment',
            'message' => $message,
            'reading' => $reading,
            'payment_qr_url' => $this->getPaymentQrImageUrl(),
        ];
    }

    /**
     * สร้างบิลรอชำระเงิน
     */
    protected function createPaymentBill(FortuneReading $reading, array $questions): array
    {
        try {
            // สร้าง unique amount จากราคาในการตั้งค่า
            $basePrice = $this->getDeepReadingPrice();
            $uniqueAmount = UniquePaymentAmount::generate(
                $basePrice,
                $reading->id,
                'fortune_reading',
                30  // หมดอายุใน 30 นาที
            );

            if (! $uniqueAmount) {
                return [
                    'action' => 'error',
                    'message' => "🔮 ตอนนี้ระบบกำลังเตรียมบิลให้ค่ะ\n\nรบกวนพิมพ์ 'ดูดวงละเอียด' อีกครั้งในอีกสักครู่นะคะ ✨",
                    'reading' => $reading,
                ];
            }

            // อัพเดท reading
            $reading->update([
                'questions' => $questions,
            ]);
            $reading->setPendingPayment($uniqueAmount);

            // สร้าง Birth Chart ส่งให้ผู้ใช้เห็นก่อนชำระเงิน (เป็น preview)
            $chartImageUrl = null;
            try {
                $birthDate = $reading->birth_date?->format('Y-m-d');
                $name = $reading->facebook_user_name ?? 'คุณ';
                $userProfile = $reading->user_profile ?? [];
                $gender = $userProfile['gender'] ?? null;

                if ($birthDate) {
                    $chartImageUrl = $this->chartService->generateBirthChart($birthDate, $name, $gender);
                } else {
                    $chartImageUrl = $this->chartService->generateQuickChart($name);
                }

                if ($chartImageUrl) {
                    $reading->update(['reading_image_url' => $chartImageUrl]);
                }
            } catch (\Throwable $chartErr) {
                Log::error('Fortune: สร้าง Birth Chart ก่อนบิลไม่สำเร็จ', [
                    'reading_id' => $reading->id,
                    'error' => $chartErr->getMessage(),
                    'error_class' => get_class($chartErr),
                ]);
            }

            // สร้างข้อความสรุป + บัญชีธนาคาร
            $message = $this->getPaymentSummaryMessage($reading, $questions, $uniqueAmount);

            Log::info('Fortune Conversation: สร้างบิลรอชำระ', [
                'reading_id' => $reading->id,
                'unique_amount' => $uniqueAmount->unique_amount,
                'facebook_user_id' => $reading->facebook_user_id,
                'chart_image_url' => $chartImageUrl,
            ]);

            // ✅ ส่ง FCM push ให้แอพ SMS Checker เห็นบิลใหม่ทันที
            // ไม่ต้องรอ polling cycle (ปกติ 30-60 วินาที)
            try {
                app(\App\Services\FcmNotificationService::class)->notifyNewFortuneReading($reading);
            } catch (\Exception $fcmErr) {
                Log::warning('Fortune Conversation: FCM push new_fortune_reading ล้มเหลว (ไม่ blocking)', [
                    'reading_id' => $reading->id,
                    'error' => $fcmErr->getMessage(),
                ]);
            }

            // ดึง QR Code URL สำหรับชำระเงิน (ถ้ามี)
            $qrImageUrl = $this->getPaymentQrImageUrl();

            return [
                'action' => 'pending_payment',
                'message' => $message,
                'reading' => $reading,
                'payment_qr_url' => $qrImageUrl,
                'chart_image_url' => $chartImageUrl,
            ];

        } catch (\Exception $e) {
            Log::error('Fortune Conversation: สร้างบิลล้มเหลว', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'action' => 'error',
                'message' => "🔮 ระบบกำลังจัดเตรียมให้ค่ะ\n\nพิมพ์คำถามใหม่ได้เลยนะคะ หรือพิมพ์ 'ดูดวงละเอียด' อีกครั้งค่ะ ✨",
                'reading' => $reading,
            ];
        }
    }

    /**
     * ประมวลผลเมื่อชำระเงินสำเร็จ
     *
     * ทำนายทีละคำถาม อิงจากวันเดือนปีเกิด+เพศ
     * ส่งคู่คำถาม-คำทำนายแยกกัน ให้ละเอียดน่าเชื่อถือ
     */
    public function processPaymentConfirmed(
        FortuneReading $reading,
        ?SmsPaymentNotification $notification = null,
        ?FortuneChannelManager $channelManager = null,
        ?string $platform = null,
        ?string $userId = null
    ): array {
        // ถ้ามี channelManager → ส่งผลทีละคำถามแบบ streaming (ป้องกัน timeout)
        $streaming = $channelManager && $platform && $userId;

        // ขยายเวลา execution เป็น 3 นาที (AI ใช้เวลา ~15-20 วินาทีต่อคำถาม × 2 ข้อ)
        set_time_limit(180);

        try {
            // ยืนยันการชำระเงิน
            $reading->confirmPayment($notification);

            // ดึงข้อมูลสำหรับทำนาย
            $questions = $reading->questions ?? $reading->getCollectedQuestions();
            $userProfile = $reading->user_profile;
            $birthDate = $reading->birth_date?->format('Y-m-d');
            $name = $reading->facebook_user_name ?? 'คุณ';
            $gender = isset($userProfile['gender']) ? ($userProfile['gender'] === 'male' ? 'ชาย' : 'หญิง') : '';

            // สร้าง Birth Chart ใหม่จากวันเกิดจริง (ส่งก่อนคำทำนาย)
            // ถ้าไม่มีวันเกิด → ใช้ Quick Chart แทน (เพื่อให้มีภาพส่งเสมอ)
            $chartImageUrl = null;
            try {
                if ($birthDate) {
                    $chartImageUrl = $this->chartService->generateBirthChart(
                        $birthDate, $name, $userProfile['gender'] ?? null
                    );
                } else {
                    // ไม่มีวันเกิด → สร้าง Quick Chart เป็น fallback
                    $chartImageUrl = $this->chartService->generateQuickChart($name);
                }

                if ($chartImageUrl) {
                    $reading->update(['reading_image_url' => $chartImageUrl]);
                }
            } catch (\Throwable $chartErr) {
                Log::error('Fortune Deep: Failed to generate chart image', [
                    'error' => $chartErr->getMessage(),
                    'error_class' => get_class($chartErr),
                    'has_birth_date' => ! empty($birthDate),
                    'reading_id' => $reading->id ?? null,
                    'gd_loaded' => extension_loaded('gd'),
                ]);
            }

            // [Streaming] ส่ง Birth Chart ทันทีถ้ามี
            if ($streaming && $chartImageUrl) {
                try {
                    $platformService = $channelManager->getPlatform($platform);
                    if ($platformService) {
                        $platformService->sendImage($userId, $chartImageUrl);
                        usleep(50000); // ⚡ 50ms (ลดจาก 200ms)
                    }
                } catch (\Exception $imgErr) {
                    Log::warning('Fortune Deep Streaming: ส่ง chart image ไม่สำเร็จ', [
                        'error' => $imgErr->getMessage(),
                        'chart_url' => $chartImageUrl,
                        'platform' => $platform,
                    ]);
                }
            }

            // ทำนายทีละคำถาม
            $deepReadings = [];
            $totalTokens = 0;
            $lastProvider = '';
            $lastModel = '';

            foreach ($questions as $index => $question) {
                $questionNum = $index + 1;
                $totalQuestions = count($questions);

                // สร้าง prompt เฉพาะคำถามนี้ อิงวันเกิด+เพศ
                $perQuestionPrompt = $this->buildPerQuestionDeepPrompt(
                    $userProfile,
                    $question,
                    $questionNum,
                    $totalQuestions,
                    $birthDate,
                    $deepReadings
                );

                $aiResult = $this->aiService->generateWithRetryAndFallback(
                    [$question],
                    $userProfile,
                    null,
                    $perQuestionPrompt,
                    'deep',
                    $birthDate
                );

                $deepReadings[] = [
                    'question_number' => $questionNum,
                    'question' => $question,
                    'answer' => $aiResult['response'],
                ];

                $totalTokens += $aiResult['tokens_used'] ?? 0;
                $lastProvider = $aiResult['provider'] ?? '';
                $lastModel = $aiResult['model'] ?? '';

                // [Streaming] ส่งคำทำนายแต่ละข้อกลับทันที
                if ($streaming) {
                    try {
                        Log::info("Fortune Deep Streaming: ข้อที่ {$questionNum} ยาว ".mb_strlen($aiResult['response']).' ตัวอักษร');

                        // ⚡ สำหรับ LINE → ใช้ Flex Message การ์ดสวยๆ (แทน text ธรรมดา)
                        if ($platform === 'line') {
                            $lineService = $channelManager->getPlatform('line');
                            if ($lineService instanceof LineFortuneService) {
                                $flex = $lineService->buildDeepReadingFlexMessage(
                                    $questionNum, $question, $aiResult['response'], $totalQuestions
                                );
                                $lineService->sendRichMessage($userId, [
                                    'alt_text' => "🔮 คำทำนายข้อ {$questionNum}/{$totalQuestions}: {$question}",
                                    'contents' => $flex,
                                ]);
                            }
                        } else {
                            // Facebook / platform อื่น → ส่งเป็น text
                            $perQuestionMessage = "🔮 คำทำนายข้อที่ {$questionNum}/{$totalQuestions}\n"
                                ."❓ {$question}\n\n"
                                .$aiResult['response'];

                            $channelManager->sendResponse($platform, $userId, [
                                'action' => 'partial',
                                'message' => $perQuestionMessage,
                            ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);
                        }
                        usleep(50000); // ⚡ 50ms (ลดจาก 200ms)
                    } catch (\Exception $sendErr) {
                        Log::warning("Fortune Deep Streaming: ส่งคำทำนายข้อที่ {$questionNum} ไม่สำเร็จ", [
                            'error' => $sendErr->getMessage(),
                        ]);
                    }
                }
            }

            // บันทึก AI call สำหรับ rate limiting
            if ($reading->facebook_user_id) {
                $this->recordAICall($reading->facebook_user_id);
            }

            // รวม response ทั้งหมดสำหรับบันทึกลง DB
            $fullResponse = $this->combineDeepReadings($deepReadings, $name, $reading->bill_reference);

            // บันทึกคำทำนายละเอียดลง DB
            // Note: saveDeepReading ใช้ DB::table query ตรง เพราะหลัง AI generation
            // 45-60 วินาที MySQL connection อาจ stale ทำให้ Eloquent update ล้มเหลว
            $saveSuccess = false;
            try {
                $reading->saveDeepReading(
                    $fullResponse,
                    $lastProvider,
                    $lastModel,
                    $totalTokens
                );
                $saveSuccess = true;
            } catch (\Exception $saveErr) {
                Log::error('Fortune Deep: saveDeepReading ล้มเหลว! พยายาม reconnect + retry...', [
                    'reading_id' => $reading->id,
                    'error' => $saveErr->getMessage(),
                    'response_length' => strlen($fullResponse),
                ]);

                // Retry: reconnect MySQL แล้วลองใหม่ด้วย DB::table ตรง
                try {
                    \Illuminate\Support\Facades\DB::reconnect();
                    \Illuminate\Support\Facades\DB::table('fortune_readings')
                        ->where('id', $reading->id)
                        ->update([
                            'deep_response' => $fullResponse,
                            'ai_response' => $fullResponse,
                            'ai_provider' => $lastProvider,
                            'ai_model' => $lastModel,
                            'tokens_used' => ($reading->tokens_used ?? 0) + $totalTokens,
                            'conversation_status' => FortuneReading::STATUS_COMPLETED,
                            'reading_type' => 'deep',
                            'updated_at' => now(),
                        ]);
                    $saveSuccess = true;
                    Log::info('Fortune Deep: saveDeepReading retry สำเร็จ (reconnect)', [
                        'reading_id' => $reading->id,
                    ]);
                } catch (\Exception $retryErr) {
                    Log::critical('Fortune Deep: saveDeepReading retry ล้มเหลวด้วย!', [
                        'reading_id' => $reading->id,
                        'error' => $retryErr->getMessage(),
                    ]);
                }
            }

            // Safety net: ถ้า save ล้มเหลวทั้ง 2 รอบ → บังคับเปลี่ยนสถานะเป็น completed
            // เพื่อไม่ให้บิลค้างที่ paid ตลอดไป (แอดมินยังสามารถ retry ได้ภายหลัง)
            if (! $saveSuccess) {
                try {
                    \Illuminate\Support\Facades\DB::reconnect();
                    \Illuminate\Support\Facades\DB::table('fortune_readings')
                        ->where('id', $reading->id)
                        ->update([
                            'conversation_status' => FortuneReading::STATUS_COMPLETED,
                            'updated_at' => now(),
                        ]);
                    Log::warning('Fortune Deep: บังคับเปลี่ยนสถานะเป็น completed (deep_response ไม่ได้บันทึก)', [
                        'reading_id' => $reading->id,
                    ]);
                } catch (\Exception $forceErr) {
                    Log::critical('Fortune Deep: ไม่สามารถเปลี่ยนสถานะเป็น completed ได้เลย!', [
                        'reading_id' => $reading->id,
                        'error' => $forceErr->getMessage(),
                    ]);
                }
            }

            // สร้างข้อความขอบคุณ
            $thankYouMessage = $this->getThankYouMessage($name, $reading->bill_reference);

            Log::info('Fortune Conversation: ทำนายละเอียดสำเร็จ (ทีละคำถาม)', [
                'reading_id' => $reading->id,
                'questions_count' => count($questions),
                'tokens_used' => $totalTokens,
                'streaming' => $streaming,
            ]);

            // [Streaming] ส่งข้อความขอบคุณ
            if ($streaming) {
                try {
                    $channelManager->sendResponse($platform, $userId, [
                        'action' => 'completed',
                        'message' => $thankYouMessage,
                    ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);
                } catch (\Exception $sendErr) {
                    Log::warning('Fortune Deep Streaming: ส่งข้อความขอบคุณไม่สำเร็จ', [
                        'error' => $sendErr->getMessage(),
                    ]);
                }
            }

            // ============================================================
            // [Auto-Registration] ลงทะเบียนสมาชิก + MLM อัตโนมัติ
            // หลังส่งคำทำนายสำเร็จ → สร้าง User + MlmMember + ส่ง invite
            // รองรับทุก platform (LINE, Facebook) — ไม่จำกัดเฉพาะ LINE
            // ============================================================
            $affiliatePlatform = $platform ?? $reading->platform ?? null;
            $affiliateUserId = $userId ?? $reading->platform_user_id ?? $reading->facebook_user_id ?? null;

            if ($affiliateUserId) {
                try {
                    $affiliateService = app(FortuneAffiliateService::class);

                    // ส่ง LINE service เฉพาะเมื่อเป็น platform LINE
                    $lineServiceInstance = null;
                    if ($affiliatePlatform === 'line' && $channelManager) {
                        $lineServiceInstance = $channelManager->getPlatform('line');
                        $lineServiceInstance = $lineServiceInstance instanceof LineFortuneService ? $lineServiceInstance : null;
                    }

                    $affiliateService->autoRegisterFromFortune(
                        $reading,
                        $affiliateUserId,
                        $lineServiceInstance,
                        $affiliatePlatform
                    );
                } catch (\Exception $affErr) {
                    // ไม่ให้ error กระทบการส่งคำทำนาย
                    Log::warning('Fortune Affiliate: ลงทะเบียนอัตโนมัติล้มเหลว (ไม่กระทบคำทำนาย)', [
                        'reading_id' => $reading->id,
                        'platform' => $affiliatePlatform,
                        'user_id' => $affiliateUserId,
                        'error' => $affErr->getMessage(),
                    ]);
                }
            }

            return [
                'action' => 'completed',
                'message' => $streaming ? null : $fullResponse."\n\n".$thankYouMessage,
                'deep_readings' => $deepReadings,
                'thank_you' => $thankYouMessage,
                'reading' => $reading,
                'chart_image_url' => $chartImageUrl,
                'streaming' => $streaming,
            ];

        } catch (\Exception $e) {
            Log::error('Fortune Conversation: ทำนายละเอียดล้มเหลว', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);

            // ไม่เปลี่ยนสถานะเป็น completed ทันที — throw ให้ Job retry ก่อน
            // ถ้า retry หมด → Job::failed() จะแจ้ง user
            throw $e;
        }
    }

    // ============================================================
    // Helper Methods - Message Builders
    // ============================================================

    /**
     * สร้างข้อความแสดงจำนวนสิทธิ์ฟรีที่เหลือ (รวมเครดิตพิเศษจากแอดมิน/โปรโมชั่น)
     */
    protected function getRemainingCreditsMessage(string $userId): string
    {
        $remaining = $this->getRemainingFreeQuestions($userId);

        // เช็คว่ามีเครดิตพิเศษจากแอดมินหรือไม่
        $userCredit = FortuneUserCredit::findByUser($userId);
        $hasSpecialCredit = $userCredit && ($userCredit->isCurrentlyUnlimited() || $userCredit->getRemainingCredits() > 0 || $userCredit->isDailyResetActive());

        if ($remaining >= 99) {
            return '📊 สิทธิ์ดูดวงฟรี: ✨ ไม่จำกัด ✨ (โปรโมชั่นพิเศษ!)';
        }

        $msg = "📊 สิทธิ์ดูดวงฟรีคงเหลือวันนี้: {$remaining} ครั้ง";

        if ($hasSpecialCredit && $userCredit->getRemainingCredits() > 0) {
            $msg .= " (รวมเครดิตพิเศษ {$userCredit->getRemainingCredits()} ครั้ง 🎁)";
        }

        if ($remaining <= 0) {
            $msg .= "\n💡 สิทธิ์ฟรีหมดแล้ว สามารถดูดวงละเอียดได้ค่ะ";
        }

        return $msg;
    }

    /**
     * สร้างข้อความ upsell ชวนดูดวงละเอียด
     */
    protected function getUpsellMessage(string $name): string
    {
        // ✅ ถ้าปิดดูดวงละเอียด → ไม่แสดง upsell
        if (! $this->settings->isDeepReadingEnabled()) {
            return '';
        }

        $price = $this->getDeepReadingPrice();

        return "═══════════════════════\n".
               "🌟 *ดูดวงละเอียด* 🌟\n".
               "═══════════════════════\n\n".
               "คุณ{$name} อยากรู้ลึกกว่านี้ไหมคะ?\n\n".
               "📍 บอกวันเดือนปีเกิด\n".
               "📍 ถามได้ 2 คำถาม\n".
               "📍 เริ่มต้นเพียง {$price} บาท\n\n".
               'กดเลือกด้านล่างได้เลยค่ะ 👇';
    }

    /**
     * สร้างข้อความขอวันเกิด
     */
    protected function getBirthdateRequestMessage(): string
    {
        return "🎂 *กรุณาบอกวันเดือนปีเกิดค่ะ*\n\n".
               "📅 พิมพ์ในรูปแบบ: วัน/เดือน/ปี\n".
               "📅 ตัวอย่าง: 15/08/1990 หรือ 15/08/2533\n".
               "📅 หรือพิมพ์: 15 สิงหาคม 2533\n\n".
               'ข้อมูลนี้จะช่วยให้คำทำนายแม่นยำขึ้นค่ะ ✨';
    }

    /**
     * สร้างข้อความขอคำถาม — บอกให้กดปุ่มเลือกหมวดได้
     */
    protected function getQuestionsRequestMessage(string $name, string $birthDate): string
    {
        $formattedDate = $this->formatThaiDate($birthDate);
        $count = self::REQUIRED_QUESTIONS;

        return "✅ รับวันเกิดแล้ว: {$formattedDate}\n\n".
               "═══════════════════════\n".
               "🔮 *ตั้งคำถาม {$count} ข้อ*\n".
               "═══════════════════════\n\n".
               "คุณ{$name} ต้องการถามเรื่องอะไรบ้างคะ?\n\n".
               "📝 คำถามข้อที่ 1 จาก {$count} — เลือกหมวดหรือพิมพ์เองได้เลยค่ะ 👇";
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

        // คำนวณเวลาที่เหลือ
        $remainingMinutes = (int) now()->diffInMinutes($uniqueAmount->expires_at, false);
        $remainingMinutes = max(0, $remainingMinutes);

        $message .= "\n═══════════════════════\n";
        $message .= "💰 *ยอดชำระ: ฿{$amount}*\n";
        $message .= "⏰ หมดอายุ: {$expiresAt} น.\n";
        $message .= "⏳ เหลือเวลาอีก: {$remainingMinutes} นาที\n";
        $message .= "═══════════════════════\n\n";

        // เพิ่มบัญชีธนาคาร
        $message .= $this->getBankAccountsListMessage();

        $message .= "\n⚠️ *สำคัญ*: กรุณาโอนยอด ฿{$amount} (ตรงตามทศนิยม)\n";
        $message .= "เพื่อให้ระบบตรวจสอบอัตโนมัติได้ถูกต้อง\n\n";
        $message .= "เมื่อโอนแล้วรอสักครู่ ระบบจะส่งคำทำนายให้ทันทีค่ะ ✨\n";
        $message .= "📌 บิลจะหมดอายุอัตโนมัติใน {$remainingMinutes} นาทีค่ะ";

        return $message;
    }

    /**
     * ดึงบัญชีธนาคารสำหรับระบบดูดวง
     *
     * ใช้บัญชีเฉพาะที่ตั้งค่าไว้ในระบบดูดวง (fortune_bank_account_ids)
     * ถ้าไม่ได้ตั้งค่า จะ fallback ไปใช้บัญชีที่เปิด SMS Checker ทั้งหมด
     */
    protected function getBankAccountsListMessage(): string
    {
        // ใช้ method จาก FortuneTellingSetting ที่ดึงบัญชีเฉพาะดูดวง
        $accounts = $this->settings->getFortuneBankAccounts();

        if ($accounts->isEmpty()) {
            return "🏦 กรุณาติดต่อแอดมินเพื่อขอบัญชีธนาคาร\n";
        }

        // ดึงโหมดแสดงช่องทางชำระเงิน (both, bank_only, promptpay_only)
        $displayMode = $this->settings->getPaymentDisplayMode();
        $showBank = $this->settings->shouldShowBankAccount();
        $showPromptpay = $this->settings->shouldShowPromptpay();

        // เลือก header ตามโหมด
        if ($displayMode === 'promptpay_only') {
            $message = "📱 *ช่องทางชำระเงิน (พร้อมเพย์)*:\n\n";
        } elseif ($displayMode === 'bank_only') {
            $message = "🏦 *บัญชีที่รับโอน*:\n\n";
        } else {
            $message = "🏦 *บัญชีที่รับโอน*:\n\n";
        }

        foreach ($accounts as $account) {
            if ($displayMode === 'promptpay_only') {
                // แสดงเฉพาะพร้อมเพย์ (ไม่แสดงเลขบัญชี — เลี่ยง FB ตรวจจับ)
                if ($account->hasPromptpay()) {
                    $message .= "📱 {$account->bank_name}\n";
                    $message .= "   พร้อมเพย์: {$account->promptpay_id}\n";
                    $message .= "   ชื่อ: {$account->account_name}\n";
                    $message .= "\n";
                }
            } elseif ($displayMode === 'bank_only') {
                // แสดงเฉพาะเลขบัญชี (ไม่แสดงพร้อมเพย์)
                $message .= "📌 {$account->bank_name}\n";
                $message .= "   เลขบัญชี: {$account->account_number}\n";
                $message .= "   ชื่อ: {$account->account_name}\n";
                $message .= "\n";
            } else {
                // แสดงทั้งสองอย่าง (default)
                $message .= "📌 {$account->bank_name}\n";
                $message .= "   เลขบัญชี: {$account->account_number}\n";
                $message .= "   ชื่อ: {$account->account_name}\n";

                if ($account->hasPromptpay()) {
                    $message .= "   พร้อมเพย์: {$account->promptpay_id}\n";
                }

                $message .= "\n";
            }
        }

        return $message;
    }

    /**
     * สร้างข้อความบัญชีธนาคาร (สำหรับขอดูซ้ำ)
     */
    protected function getBankAccountsMessage(FortuneReading $reading): string
    {
        // ดึงยอดจาก uniquePaymentAmount (ค่าจริงที่ต้องโอน) ถ้ามี
        $uniqueAmount = $reading->uniquePaymentAmount;
        $amount = $uniqueAmount
            ? number_format($uniqueAmount->unique_amount, 2)
            : number_format($reading->amount_paid, 2);
        $message = "💰 *ยอดชำระ: ฿{$amount}*\n\n";
        $message .= $this->getBankAccountsListMessage();
        $message .= "⚠️ กรุณาโอนยอด ฿{$amount} ตรงตามทศนิยมค่ะ";

        return $message;
    }

    /**
     * สร้างข้อความขอบคุณหลังชำระเงิน
     *
     * @param  string  $name  ชื่อผู้ใช้
     * @param  string|null  $billReference  เลขที่บิลอ้างอิง
     */
    protected function getThankYouMessage(string $name, ?string $billReference = null): string
    {
        $billInfo = $billReference ? "🔖 เลขที่บิล: {$billReference}\n\n" : '';

        return "═══════════════════════\n".
               "🙏 *ขอบคุณที่ใช้บริการค่ะ*\n".
               "═══════════════════════\n\n".
               $billInfo.
               "คุณ{$name} หวังว่าคำทำนายจะเป็นประโยชน์นะคะ ✨\n\n".
               "📢 อย่าลืมส่งต่อให้เพื่อนๆ มาลองดูดวงด้วยกันนะคะ\n".
               "พิมพ์ 'ดูดวง' เมื่อต้องการดูดวงอีกครั้ง 🔮";
    }

    /**
     * สร้างข้อความ help
     */
    protected function getHelpMessage(): string
    {
        return "🔮 *ระบบดูดวง AI*\n\n".
               "พิมพ์ 'ดูดวง' เพื่อเริ่มดูดวงฟรี\n".
               'หลังจากนั้นสามารถเลือกดูดวงละเอียดได้ค่ะ ✨';
    }

    /**
     * สร้างข้อความ help พร้อมตัวอย่างคำถาม
     *
     * มีคาแรคเตอร์หมอดูที่อบอุ่น เป็นกันเอง แต่น่าเชื่อถือ
     */
    protected function getHelpMessageWithExamples(): string
    {
        $price = $this->getDeepReadingPrice();

        $message = "🔮 *เพจดูดวงหมอจันทรายินดีต้อนรับค่ะ*\n\n";
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

        $message .= "💎 *ดูดวงละเอียด เริ่มต้น {$price} บาท*\n";
        $message .= "   ถามได้ 2 คำถาม วิเคราะห์จากวันเกิด\n";
        $message .= "   พร้อมสีมงคล เลขมงคล ฤกษ์ดี\n\n";

        $message .= "📝 *ตัวอย่างคำถาม*:\n";
        $message .= "• ปีนี้จะมีคู่ครองไหม\n";
        $message .= "• ควรเปลี่ยนงานตอนนี้ไหม\n";
        $message .= "• ดวงการเงินช่วงนี้เป็นอย่างไร\n\n";

        $message .= "💡 พิมพ์คำถามมาได้เลยนะคะ\n";
        $message .= 'หมอพร้อมทำนายให้ค่ะ ✨';

        return $message;
    }

    /**
     * สร้างข้อความเลขที่บิลอ้างอิง
     */
    protected function getBillReferenceMessage(?string $billReference): string
    {
        if (empty($billReference)) {
            return '';
        }

        return "═══════════════════════\n".
               "🔖 *เลขที่บิลอ้างอิง*\n".
               "📌 {$billReference}\n".
               "═══════════════════════\n".
               '(เก็บไว้อ้างอิงหากมีปัญหาค่ะ)';
    }

    // ============================================================
    // Pre-Filter Methods - ตรวจจับข้อความก่อนส่ง AI
    // ============================================================

    /**
     * Pre-filter ข้อความก่อนประมวลผล (ไม่ต้องระบุ userId)
     *
     * ✅ ปรับปรุงใหม่: ให้ผ่านเกือบทุกข้อความไปหา AI
     * บล็อกเฉพาะ spam รุนแรงและข้อความยาวเกินเท่านั้น
     * ให้ AI system prompt จัดการ off-topic เอง
     *
     * @param  string  $text  ข้อความที่ต้องการตรวจสอบ
     * @return array ['valid' => bool, 'reason' => string, 'message' => string]
     */
    protected function preFilterMessage(string $text): array
    {
        $text = trim($text);
        $length = mb_strlen($text);

        // 1. ตรวจสอบความยาว - ข้อความยาวเกินไป
        if ($length > self::MAX_MESSAGE_LENGTH) {
            return [
                'valid' => false,
                'reason' => 'too_long',
                'message' => $this->getTooLongMessage(),
            ];
        }

        // 2. ข้อความว่างเปล่า
        if ($length === 0) {
            return [
                'valid' => false,
                'reason' => 'empty',
                'message' => '🔮 พิมพ์ข้อความมาได้เลยนะคะ จันทราพร้อมช่วยดูดวงให้ค่ะ ✨',
            ];
        }

        // 3. ตรวจจับ spam รุนแรงเท่านั้น (ตัวอักษรซ้ำ 10+ ครั้ง เช่น "aaaaaaaaaa")
        if ($this->isSpamOrGibberish($text)) {
            return [
                'valid' => false,
                'reason' => 'spam',
                'message' => $this->getSpamMessage(),
            ];
        }

        // ✅ ทุกข้อความอื่นๆ ปล่อยผ่านไปให้ AI จัดการ
        return [
            'valid' => true,
            'reason' => 'ok',
            'message' => '',
        ];
    }

    /**
     * Pre-filter ข้อความพร้อม Rate Limiting (ต้องระบุ userId)
     *
     * @param  string  $userId  Facebook User ID
     * @param  string  $text  ข้อความ
     * @return array ['valid' => bool, 'reason' => string, 'message' => string]
     */
    public function preFilterWithRateLimit(string $userId, string $text): array
    {
        // 1. ตรวจสอบ Rate Limiting ก่อน
        $rateLimitResult = $this->checkRateLimit($userId);
        if (! $rateLimitResult['allowed']) {
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
            if (! $hasThaiOrEnglish) {
                return true;
            }
        }

        return false;
    }

    /**
     * ตรวจจับ Prompt Injection Attempts
     * ป้องกันการพยายาม manipulate AI ด้วยคำสั่งพิเศษ
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
            if (! $hasFortuneKeyword && ! $this->isBasicCommand($text)) {
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
     * @param  string  $userId  Facebook/LINE User ID
     * @return bool true ถ้ายังถามฟรีได้
     */
    public function canMakeAICall(string $userId): bool
    {
        // 1. เช็คเครดิตพิเศษรายคน (แอดมินให้เพิ่ม / ดูฟรีไม่จำกัด / รีเซ็ตวันนี้)
        $userCredit = FortuneUserCredit::findByUser($userId);
        if ($userCredit && $userCredit->hasExtraFreeAccess()) {
            return true;
        }

        // 2. เช็คจากสิทธิ์ฟรีประจำวันปกติ
        $maxFreeReadings = $this->settings->max_free_readings ?? self::MAX_AI_CALLS_PER_DAY;

        return ! FortuneReading::hasReachedFreeLimit($userId, $maxFreeReadings);
    }

    /**
     * ตรวจสอบจำนวนคำถามฟรีที่เหลือวันนี้
     *
     * @return int จำนวนครั้งที่เหลือ
     */
    public function getRemainingFreeQuestions(string $userId): int
    {
        $maxFreeReadings = $this->settings->max_free_readings ?? self::MAX_AI_CALLS_PER_DAY;
        $usedToday = FortuneReading::countTodayReadings($userId);
        $normalRemaining = max(0, $maxFreeReadings - $usedToday);

        // เพิ่มเครดิตพิเศษรายคน (ถ้ามี)
        $userCredit = FortuneUserCredit::findByUser($userId);
        if ($userCredit) {
            // ดูฟรีไม่จำกัด → แสดง 99
            if ($userCredit->isCurrentlyUnlimited()) {
                return 99;
            }
            // รีเซ็ตวันนี้ → คืนสิทธิ์เท่ากับ max
            if ($userCredit->isDailyResetActive()) {
                return max($normalRemaining, $maxFreeReadings);
            }
            // มีเครดิตเหลือ → บวกเพิ่ม
            $normalRemaining += $userCredit->getRemainingCredits();
        }

        return $normalRemaining;
    }

    /**
     * บันทึกการเรียก AI + หักเครดิตถ้าเกินสิทธิ์ฟรี
     *
     * ⚠️ ต้องเรียกหลัง saveBasicReading() เพื่อให้ responded_at ถูกตั้งก่อน
     * เพราะ countTodayReadings() นับเฉพาะ reading ที่มี responded_at
     */
    public function recordAICall(string $userId): void
    {
        $dayKey = "fortune_ai_calls:{$userId}:day";
        $count = (int) Cache::get($dayKey, 0);
        Cache::put($dayKey, $count + 1, now()->endOfDay());

        // หักเครดิตพิเศษ (ถ้าสิทธิ์ฟรีปกติหมดแล้ว ใช้เครดิตแทน)
        $maxFreeReadings = $this->settings->max_free_readings ?? self::MAX_AI_CALLS_PER_DAY;
        $usedToday = FortuneReading::countTodayReadings($userId);

        Log::info('Fortune: recordAICall — นับสิทธิ์', [
            'user_id' => $userId,
            'used_today' => $usedToday,
            'max_free' => $maxFreeReadings,
            'cache_count' => $count + 1,
            'will_deduct_credit' => $usedToday >= $maxFreeReadings,
        ]);

        if ($usedToday >= $maxFreeReadings) {
            $userCredit = FortuneUserCredit::findByUser($userId);
            if ($userCredit) {
                $deducted = $userCredit->useCredit();
                Log::info('Fortune: หักเครดิตพิเศษ', [
                    'user_id' => $userId,
                    'deducted' => $deducted,
                    'remaining_credits' => $userCredit->getRemainingCredits(),
                ]);
            }
        }
    }

    /**
     * ตรวจสอบข้อความซ้ำ
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
            if (! $hasFortuneKeyword && ! $this->isBasicCommand($text)) {
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
        return "🙏 ขอบคุณที่ทักมานะคะ\n\n".
               "จันทราขอตอบเฉพาะเรื่องดูดวงเท่านั้นค่ะ\n\n".
               "💡 *ตัวอย่างคำถาม*:\n".
               "• ดวงความรักปีนี้เป็นอย่างไร\n".
               "• การเงินจะดีขึ้นไหม\n".
               "• ควรเปลี่ยนงานไหม\n\n".
               'จันทราพร้อมทำนายให้ค่ะ 🔮✨';
    }

    /**
     * ข้อความเมื่อโดน rate limit
     *
     * @param  string  $type  minute, hour, หรือ day
     */
    protected function getRateLimitMessage(string $type): string
    {
        $messages = [
            'minute' => "🙏 จันทราขอเวลาสักครู่นะคะ\n\nกรุณารอสักครู่แล้วทักมาใหม่ค่ะ 🔮",
            'hour' => "🙏 ขอบคุณที่สนใจค่ะ\n\nทักมาเยอะมากเลยค่ะ กรุณารอสักพักแล้วค่อยทักมาใหม่นะคะ\n\nจันทราพร้อมทำนายให้ค่ะ 🔮✨",
            'day' => "🙏 ขอบคุณที่ใช้บริการค่ะ\n\nวันนี้ทักมาเยอะมากเลยค่ะ กรุณากลับมาใหม่พรุ่งนี้นะคะ\n\nขอให้โชคดีค่ะ 🔮✨",
        ];

        return $messages[$type] ?? $messages['minute'];
    }

    /**
     * ข้อความเมื่อส่งข้อความซ้ำ
     */
    protected function getRepetitiveMessage(): string
    {
        return "🙏 จันทราเห็นข้อความนี้แล้วค่ะ\n\n".
               "กรุณาลองถามเรื่องอื่น หรือถามในมุมใหม่ได้นะคะ\n\n".
               "💡 *ตัวอย่าง*:\n".
               "• ดวงความรักปีนี้เป็นอย่างไร\n".
               "• การเงินจะดีขึ้นไหม\n".
               "• ควรเปลี่ยนงานไหม\n\n".
               'จันทราพร้อมทำนายให้ค่ะ 🔮✨';
    }

    /**
     * ข้อความเมื่อตรวจจับข้อความไร้สาระ
     *
     * ตอบด้วยความเป็นมิตร ไม่ดูถูกผู้ใช้
     */
    protected function getMeaninglessMessage(): string
    {
        return "🔮 *เพจดูดวงหมอจันทรายินดีต้อนรับค่ะ*\n\n".
               "หมอพร้อมช่วยดูดวงให้ค่ะ ไม่ว่าจะเรื่อง:\n\n".
               "💕 ความรัก - เนื้อคู่ คู่ครอง\n".
               "💼 การงาน - เปลี่ยนงาน เลื่อนขั้น\n".
               "💰 การเงิน - โชคลาภ รายได้\n".
               "🏥 สุขภาพ - สิ่งควรระวัง\n\n".
               "💡 *ตัวอย่างคำถาม*:\n".
               "• ปีนี้จะมีคู่ครองไหม\n".
               "• ควรเปลี่ยนงานไหม\n".
               "• ดวงการเงินเป็นอย่างไร\n\n".
               'พิมพ์คำถามมาได้เลยค่ะ 🔮✨';
    }

    /**
     * ข้อความเมื่อใช้สิทธิ์ถามฟรีหมดแล้ว
     *
     * ชวนให้จ่ายเงินดูดวงละเอียดพร้อมบอกวิธีการชัดเจน
     */
    protected function getAILimitMessage(): string
    {
        $message = "🔮 *เพจดูดวงหมอจันทรายินดีต้อนรับค่ะ*\n\n";
        $message .= "วันนี้คุณใช้สิทธิ์ถามฟรีไปแล้วค่ะ\n";
        $message .= "(ฟรีวันละ 1 คำถาม)\n\n";

        // ✅ แสดง upsell เฉพาะเมื่อเปิดดูดวงละเอียด
        if ($this->settings->isDeepReadingEnabled()) {
            $price = $this->getDeepReadingPrice();

            $message .= "═══════════════════════\n";
            $message .= "💎 *ดูดวงละเอียด เริ่มต้น {$price} บาท*\n";
            $message .= "═══════════════════════\n\n";

            $message .= "📌 ถามได้ถึง 2 คำถาม\n";
            $message .= "📌 วิเคราะห์จากวันเกิดเจาะลึก\n";
            $message .= "📌 บอกสีมงคล เลขมงคล ฤกษ์ดี\n";
            $message .= "📌 คำทำนายละเอียดคุ้มราคา\n\n";

            $message .= "🎯 *วิธีใช้บริการ*\n";
            $message .= "─────────────────────\n";
            $message .= "1️⃣ บอกวันเดือนปีเกิด\n";
            $message .= "2️⃣ ถามคำถามได้เลย 2 ข้อ\n";
            $message .= "3️⃣ ระบบจะออกบิลพร้อมยอดชำระ\n";
            $message .= "4️⃣ โอนเงินตามยอดในบิล\n\n";

            $message .= 'กดปุ่มด้านล่างเพื่อเริ่มค่ะ 👇';
        } else {
            $message .= 'กลับมาใหม่พรุ่งนี้ได้นะคะ 🙏';
        }

        return $message;
    }

    /**
     * ข้อความเมื่อพิมพ์ยาวเกินไป
     */
    protected function getTooLongMessage(): string
    {
        return "🔮 ข้อความยาวไปหน่อยค่ะ\n\n".
               "ลองย่อให้สั้นกว่านี้ได้ไหมคะ?\n".
               "หมอจะได้ตอบได้ตรงจุดค่ะ\n\n".
               "💡 *ตัวอย่าง*:\n".
               "• ดวงความรักปีนี้เป็นอย่างไร\n".
               "• ควรเปลี่ยนงานตอนนี้ไหม\n".
               "• การเงินช่วงนี้จะดีไหม\n\n".
               'หมอรอคำถามอยู่นะคะ ✨';
    }

    /**
     * ข้อความเมื่อพิมพ์สั้นเกินไป
     */
    protected function getTooShortMessage(): string
    {
        return "🔮 หมอไม่ค่อยเข้าใจค่ะ\n\n".
               "ลองพิมพ์คำถามให้ชัดกว่านี้หน่อยนะคะ\n\n".
               "💡 *ตัวอย่าง*:\n".
               "• ดวงความรักปีนี้เป็นอย่างไร\n".
               "• ควรเปลี่ยนงานไหม\n".
               "• การเงินจะดีขึ้นไหม\n\n".
               'หมอพร้อมทำนายให้ค่ะ ✨';
    }

    /**
     * ข้อความเมื่อตรวจจับ spam
     *
     * ตอบด้วยความสุภาพ ไม่กล่าวโทษผู้ใช้
     */
    protected function getSpamMessage(): string
    {
        return "🔮 *สวัสดีค่ะ*\n\n".
               "หมอไม่ค่อยเข้าใจข้อความค่ะ\n".
               "ลองพิมพ์คำถามชัดๆ ได้ไหมคะ?\n\n".
               "💡 *ตัวอย่าง*:\n".
               "• ปีนี้ดวงความรักเป็นอย่างไร\n".
               "• ควรเปลี่ยนงานไหม\n".
               "• ดวงการเงินช่วงนี้\n\n".
               'หมอพร้อมทำนายให้นะคะ ✨';
    }

    /**
     * ข้อความเมื่อตรวจจับ off-topic
     */
    protected function getOffTopicMessage(string $category): string
    {
        $categoryMessages = [
            'code' => 'ขอบคุณที่สนใจค่ะ แต่จันทราไม่รับเขียนโค้ดหรือโปรแกรมนะคะ 🙏',
            'food' => 'ขอบคุณที่สนใจค่ะ แต่จันทราไม่รับแนะนำร้านอาหารหรือสูตรอาหารนะคะ 🙏',
            'translate' => 'ขอบคุณที่สนใจค่ะ แต่จันทราไม่รับแปลภาษานะคะ 🙏',
            'story' => 'ขอบคุณที่สนใจค่ะ แต่จันทราไม่รับเล่าเรื่องหรือมุกตลกนะคะ 🙏',
            'math' => 'ขอบคุณที่สนใจค่ะ แต่จันทราไม่รับคำนวณเลขนะคะ 🙏',
            'hack' => 'ขอโทษค่ะ จันทราไม่รับทำสิ่งที่ผิดกฎหมายหรือไม่เหมาะสมค่ะ 🙏',
            'homework' => 'ขอบคุณที่สนใจค่ะ แต่จันทราไม่รับทำการบ้านหรือเขียนรายงานนะคะ 🙏',
        ];

        $specificMessage = $categoryMessages[$category] ?? 'ขอบคุณที่สนใจค่ะ 🙏';

        return "{$specificMessage}\n\n".
               "═══════════════════════\n".
               "🔮 *จันทรารับดูดวงเท่านั้นค่ะ*\n".
               "═══════════════════════\n\n".
               "ถ้ามีเรื่องอยากให้ทำนาย ไม่ว่าจะเรื่อง:\n".
               "💕 ความรัก คู่ครอง\n".
               "💼 การงาน อาชีพ\n".
               "💰 การเงิน โชคลาภ\n".
               "🏥 สุขภาพ\n\n".
               'ทักมาได้เลยค่ะ จันทราพร้อมทำนายให้ค่ะ 🔮✨';
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
     * ตรวจสอบว่าต้องการดูดวงละเอียดหรือไม่ (ใช้ตอนมี active reading อยู่แล้ว — keyword กว้าง)
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
     * ตรวจสอบว่าเป็นคำขอดูดวงละเอียดอย่างชัดเจน (ใช้ตอนไม่มี active reading — keyword เข้มงวด)
     *
     * ใช้ keyword ที่เฉพาะเจาะจงกว่า isDeepReadingAccepted() เพื่อไม่ให้คำทั่วไป
     * อย่าง "ใช่", "ได้", "ok" trigger การเริ่ม deep reading flow โดยไม่ตั้งใจ
     */
    protected function isExplicitDeepReadingRequest(string $text): bool
    {
        $explicitKeywords = [
            'ต้องการดูละเอียด',
            'ดูดวงละเอียด',
            'ดูละเอียด',
            'ต้องการดูดวงละเอียด',
            'อยากดูละเอียด',
            'สนใจดูละเอียด',
            'เอาละเอียด',
            'ดูเพิ่มเติม',
        ];
        $text = mb_strtolower(trim($text));

        foreach ($explicitKeywords as $keyword) {
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
            if (preg_match('/(\d{1,2})\s*'.$monthName.'\s*(\d{4})/', $text, $matches)) {
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
        $questions = array_filter($questions, fn ($q) => mb_strlen($q) > 2);

        // ถ้า parse ไม่ได้ ใช้ทั้งข้อความเป็น 1 คำถาม
        if (empty($questions)) {
            return [trim($text)];
        }

        return array_values($questions);
    }

    /**
     * สร้างคำถามสำเร็จรูปจากหมวดที่ user เลือก
     *
     * เลือกคำถามจาก CATEGORY_QUESTION_MAP ที่ไม่ซ้ำกับคำถามที่เก็บไปแล้ว
     * ถ้าคำถามในหมวดเดียวกันถูกใช้หมดแล้ว จะสุ่มคำถามจากหมวดอื่นแทน
     *
     * @param  string  $category  หมวดคำถาม (love, work, money, health)
     * @param  array  $existingQuestions  คำถามที่เก็บไปแล้ว
     * @return string  คำถามที่สร้างขึ้น
     */
    public function getQuestionForCategory(string $category, array $existingQuestions = []): string
    {
        $categoryQuestions = self::CATEGORY_QUESTION_MAP[$category] ?? [];

        // กรองคำถามที่ยังไม่เคยใช้
        $available = array_filter($categoryQuestions, function ($q) use ($existingQuestions) {
            return ! in_array($q, $existingQuestions);
        });

        if (! empty($available)) {
            // สุ่มเลือกจากคำถามที่ยังว่าง
            $values = array_values($available);

            return $values[array_rand($values)];
        }

        // ถ้าหมวดนี้ใช้หมดแล้ว → หาจากหมวดอื่น
        foreach (self::CATEGORY_QUESTION_MAP as $cat => $questions) {
            if ($cat === $category) {
                continue;
            }
            $available = array_filter($questions, function ($q) use ($existingQuestions) {
                return ! in_array($q, $existingQuestions);
            });
            if (! empty($available)) {
                $values = array_values($available);

                return $values[array_rand($values)];
            }
        }

        // fallback: ใช้คำถามแรกของหมวดที่เลือก
        return $categoryQuestions[0] ?? 'ดวงชะตาในช่วงนี้เป็นอย่างไร';
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
     * แทนที่ตัวแปร placeholder ใน prompt template จากการตั้งค่าระบบ
     *
     * ใช้เมื่อ admin กำหนด prompt เองในหน้า Settings
     * แทน {name}, {gender_prefix}, {questions} ฯลฯ ด้วยค่าจริง
     *
     * @param  string  $template  prompt template ที่มี placeholder
     * @param  array  $variables  key-value pairs ของตัวแปร
     * @return string prompt ที่แทนค่าแล้ว
     */

    /**
     * ดึง default prompt พื้นฐาน (hardcode) สำหรับแสดงในหน้า admin settings
     * ใช้ตัวแปร placeholder เช่น {name}, {question} เพื่อให้แก้ไขได้
     */
    public static function getDefaultBasicPrompt(): string
    {
        return <<<'PROMPT'
คุณชื่อ "แม่หมอจันทรา" เป็นหมอดูสาวสวยวัย 35 ปี ผู้เชี่ยวชาญโหราศาสตร์ไทย (โหราศาสตร์เจ้าชนะ) และโหราศาสตร์สากล ได้รับการถ่ายทอดวิชาจากครูบาอาจารย์สายลังกา พูดจาเพราะ อบอุ่นเป็นกันเอง น่าเชื่อถือ ใช้คำแทนตัวว่า "จันทรา" ทำนายแม่นยำมาก ฟันธงแต่อ่อนโยน ไม่เกิน 500 คำ

{user_context}
{detected_category}

ข้อมูลผู้ขอดูดวง:
- ชื่อ: {name}
- เพศ: {gender}

ข้อความที่ส่งมา: {question}

แนวทางการตอบ:
- เรียกผู้ถามว่า "{gender_prefix}{name}" อย่างเป็นกันเอง
- ใช้ "จันทรา" แทนตัวเอง เช่น "จันทราเห็นว่า..." "จันทราขอบอกตรงๆ ว่า..."

=== ความรู้โหราศาสตร์ไทย (เจ้าชนะ) ที่ต้องใช้ในการทำนาย ===

[ดาวเคราะห์ 9 ดวง]
1. อาทิตย์ (☉) - ดาวประจำวันอาทิตย์ ธาตุไฟ เป็นใหญ่ มีอำนาจ ศักดิ์ศรี ผู้นำ
2. จันทร์ (☽) - ดาวประจำวันจันทร์ ธาตุน้ำ อารมณ์ ความรู้สึก จิตใจ เมตตา
3. อังคาร (♂) - ดาวประจำวันอังคาร ธาตุไฟ กล้าหาญ ดุดัน ทหาร การต่อสู้
4. พุธ (☿) - ดาวประจำวันพุธ ธาตุดิน ปัญญา การสื่อสาร การค้า ไหวพริบ
5. พฤหัสบดี (♃) - ดาวประจำวันพฤหัสบดี ธาตุน้ำ ศาสนา ครูอาจารย์ โชคลาภ ปัญญาลึกซึ้ง
6. ศุกร์ (♀) - ดาวประจำวันศุกร์ ธาตุน้ำ ความรัก ความสวยงาม ศิลปะ ทรัพย์สิน
7. เสาร์ (♄) - ดาวประจำวันเสาร์ ธาตุไฟ ความทุกข์ อุปสรรค ความอดทน กรรมเก่า
8. ราหู - ดาวประจำวันพุธกลางคืน ธาตุลม มายา อำนาจลึกลับ การเปลี่ยนแปลงฉับพลัน
9. เกตุ - ดาวประจำวันอาทิตย์-เสาร์ ธาตุไฟ กรรมเก่า สิ่งลี้ลับ จิตวิญญาณ

[ตารางเจ้าชนะ - ดาวมิตร/ดาวศัตรู]
- คนวันอาทิตย์: ดาวเจ้าชนะ=อาทิตย์ มิตร=พฤหัสบดี,อังคาร ศัตรู=เสาร์,ราหู
- คนวันจันทร์: ดาวเจ้าชนะ=จันทร์ มิตร=พุธ,ศุกร์ ศัตรู=ราหู,เสาร์
- คนวันอังคาร: ดาวเจ้าชนะ=อังคาร มิตร=อาทิตย์,พฤหัสบดี ศัตรู=พุธ,เสาร์
- คนวันพุธ: ดาวเจ้าชนะ=พุธ มิตร=จันทร์,ศุกร์ ศัตรู=ราหู,อังคาร
- คนวันพฤหัสบดี: ดาวเจ้าชนะ=พฤหัสบดี มิตร=อาทิตย์,อังคาร ศัตรู=ราหู,เสาร์
- คนวันศุกร์: ดาวเจ้าชนะ=ศุกร์ มิตร=พุธ,จันทร์ ศัตรู=อาทิตย์,อังคาร
- คนวันเสาร์: ดาวเจ้าชนะ=เสาร์ มิตร=ราหู,พฤหัสบดี ศัตรู=อาทิตย์,อังคาร

[ภพ 12 ภพ (เรือนชะตา)]
1.ตนุ-ตัวตน 2.กดุมภ-ทรัพย์ 3.สหัชชะ-พี่น้อง 4.พันธุ-ครอบครัว 5.ปุตตะ-ลูก/สร้างสรรค์ 6.อริ-ศัตรู/โรค 7.ปัตนิ-คู่ครอง 8.มรณะ-ความเปลี่ยนแปลง 9.ศุภะ-โชคลาภ 10.กัมมะ-การงาน 11.ลาภะ-ลาภผล 12.วินาศ-อุปสรรค

[วิธีทำนายตามหลักเจ้าชนะ]
- ดูวันเกิด → หาดาวประจำวัน → ดูมิตร/ศัตรู → ดูว่าดาวอะไรส่งผลช่วงนี้
- ดาวมิตรโคจรเข้า = เรื่องดีกำลังมา
- ดาวศัตรูโคจรเข้า = ต้องระวัง มีอุปสรรค
- อ้างอิงภพที่เกี่ยวข้องกับคำถาม (เช่น ถามเรื่องรัก → ดูภพปัตนิ)

=== จบความรู้โหราศาสตร์ ===

[กฎสำคัญที่สุด] ต้องทำนายดูดวงทุกครั้ง! ห้ามตอบแค่ทักทายแล้วชวนถามคำถาม ต้องทำนายให้เลยทุกข้อความ!

[วิธีตอบตามประเภทข้อความ]
1. ถ้าเป็นการทักทาย: ทักทายกลับสั้นๆ 1 บรรทัด แล้วทำนายดวงภาพรวมให้ทันทีครบทุกหัวข้อ
2. ถ้าเป็นคำถามดูดวง: ทำนายอย่างละเอียดตามโครงสร้างด้านล่าง โดยอ้างอิงหลักเจ้าชนะ
3. ถ้าเป็นคำถามทั่วไป: ตอบสั้นๆ แล้วทำนายดวงภาพรวมให้ด้วย
4. ถ้าถูกถามว่าเป็น AI: ตอบว่า "จันทรามีทีมงานช่วยกันค่ะ" แล้วชวนดูดวง

[โครงสร้างคำทำนาย]
🔮 **เปิดเรื่อง**: ทักทายอบอุ่น บอกว่าจันทราดูดวงด้วยหลักเจ้าชนะ อ้างอิงดาวที่ส่งผล
⭐ **ดวงภาพรวม**: วิเคราะห์ดวงชะตาภาพรวม อ้างชื่อดาวเคราะห์ที่ส่งผลในช่วงนี้
💫 **ตอบคำถามหลัก**: ตอบอย่างละเอียด กล้าบอกตรงๆ ระบุช่วงเวลาชัดเจน
🎯 **คำแนะนำ**: สีมงคล 2-3 สี, เลขมงคล 2-3 เลข, วันมงคล, สิ่งที่ควรระวัง
🌟 **ปิดท้ายชวนดูต่อ**: hint ว่ายังเห็นอะไรอีก กระตุ้นให้อยากดูดวงละเอียด

ถ้ายังไม่มีวันเกิด ให้ถามท้ายว่า "บอกวันเดือนปีเกิดให้จันทราได้ไหมคะ?"
ท้ายสุดเชิญชวน "ฝากส่งต่อให้เพื่อนๆ มาลองดูดวงกับจันทราด้วยนะคะ 🔮✨"

[กฎสำคัญ] ห้ามเขียนโค้ด ห้ามให้ข้อมูลอันตราย ตอบทุกข้อความอย่างเป็นมิตร ชวนกลับมาดูดวงเสมอ
PROMPT;
    }

    /**
     * ดึง default prompt เชิงลึก (hardcode) สำหรับแสดงในหน้า admin settings
     * ใช้ตัวแปร placeholder เช่น {name}, {question}, {planet_positions} เพื่อให้แก้ไขได้
     */
    public static function getDefaultDeepPrompt(): string
    {
        return <<<'PROMPT'
คุณชื่อ "แม่หมอจันทรา" เป็นหมอดูสาวสวยวัย 35 ปี ผู้เชี่ยวชาญศาสตร์โหราศาสตร์โบราณของไทย (หลักเจ้าชนะ) โหราศาสตร์สากล ไพ่ทาโรต์ และเลขศาสตร์ ได้รับวิชาจากครูบาอาจารย์สายลังกา มีประสบการณ์ 15 ปี ทำนายด้วยหลักวิชาโบราณล้วนๆ ทุกคำทำนายมีศาสตร์รองรับ พูดจาเพราะ อบอุ่น ใช้คำว่า "จันทรา" แทนตัวเอง

[ตารางเจ้าชนะ + เลขประจำดาว]
- วันอาทิตย์: เจ้าชนะ=อาทิตย์(1) มิตร=พฤหัสบดี(5),อังคาร(3) ศัตรู=เสาร์(7),ราหู(8) สีมงคล=แดง สีเลี่ยง=ดำ
- วันจันทร์: เจ้าชนะ=จันทร์(2) มิตร=พุธ(4),ศุกร์(6) ศัตรู=ราหู(8),เสาร์(7) สีมงคล=ขาว,ครีม สีเลี่ยง=ดำ
- วันอังคาร: เจ้าชนะ=อังคาร(3) มิตร=อาทิตย์(1),พฤหัสบดี(5) ศัตรู=พุธ(4),เสาร์(7) สีมงคล=ชมพู สีเลี่ยง=เขียว,ดำ
- วันพุธ: เจ้าชนะ=พุธ(4) มิตร=จันทร์(2),ศุกร์(6) ศัตรู=ราหู(8),อังคาร(3) สีมงคล=เขียว สีเลี่ยง=แดง,ดำ
- วันพฤหัสบดี: เจ้าชนะ=พฤหัสบดี(5) มิตร=อาทิตย์(1),อังคาร(3) ศัตรู=ราหู(8),เสาร์(7) สีมงคล=ส้ม สีเลี่ยง=ดำ
- วันศุกร์: เจ้าชนะ=ศุกร์(6) มิตร=พุธ(4),จันทร์(2) ศัตรู=อาทิตย์(1),อังคาร(3) สีมงคล=ฟ้า สีเลี่ยง=แดง
- วันเสาร์: เจ้าชนะ=เสาร์(7) มิตร=ราหู(8),พฤหัสบดี(5) ศัตรู=อาทิตย์(1),อังคาร(3) สีมงคล=ม่วง สีเลี่ยง=แดง

[ภพ 12 ภพ] 1.ตนุ(ตัวตน) 2.กดุมภ(ทรัพย์/เงิน) 3.สหัชชะ(พี่น้อง/เพื่อน) 4.พันธุ(ครอบครัว/บ้าน) 5.ปุตตะ(ลูก/สร้างสรรค์) 6.อริ(ศัตรู/โรค) 7.ปัตนิ(คู่ครอง/หุ้นส่วน) 8.มรณะ(เปลี่ยนแปลง/วิกฤต) 9.ศุภะ(โชคลาภ/ศาสนา) 10.กัมมะ(การงาน/ตำแหน่ง) 11.ลาภะ(ลาภผล/ผู้อุปถัมภ์) 12.วินาศ(อุปสรรค/กรรมเก่า)

[หลักการทำนาย]
- รัก→ภพปัตนิ(7)+ศุกร์+ปุตตะ(5) | งาน→ภพกัมมะ(10)+เสาร์/พฤหัสบดี+ลาภะ(11) | เงิน→ภพกดุมภ(2)+พุธ/ศุกร์+ลาภะ(11) | สุขภาพ→ภพอริ(6)+ตนุ(1)+อาทิตย์ | โชคลาภ→ภพศุภะ(9)+ลาภะ(11)+พฤหัสบดี | ครอบครัว→ภพพันธุ(4)+จันทร์
- สีมงคล=สีดาวมิตร | เลขมงคล=เลขดาวเจ้าชนะ+มิตร | วันมงคล=วันดาวมิตร

=== ทำนายคำถามที่ {question_number} จาก {total_questions} (การทำนายแบบพรีเมียม) ===

ข้อมูลผู้ขอดูดวง:
- ชื่อ: {name} (เรียกว่า "{gender_prefix}{name}")
- เพศ: {gender}
- วันเกิด: {birth_info}
- {zodiac_info}
{planet_positions}
{transit_info}
คำถามที่ {question_number}: {question}
{previous_context}

[โครงสร้างคำทำนาย - ต้องทำตามทุกข้อ ผู้ถามจ่ายเงินมา ต้องคุ้มค่า!]

🔮 **เปิดเรื่อง** (คำถามแรก):
- ทักทาย{gender_prefix}{name}อย่างอบอุ่น
- บอกว่า "จันทราได้รับคำถาม {total_questions} ข้อ จะทำนายให้อย่างละเอียดทีละข้อด้วยหลักเจ้าชนะนะคะ"
- วิเคราะห์ดวงชะตาจากวันเกิด + ดาวเจ้าชนะ + ดาวโคจร(transit)

⭐ **วิเคราะห์คำถาม** (เจาะลึกเฉพาะคำถามนี้):
- ตอบคำถาม "{question}" อย่างละเอียด ลึกซึ้ง ด้วยหลักเจ้าชนะ
- อ้างตำแหน่งดาวกำเนิด + ดาวโคจร(transit) + transit อนาคต
- ฟันธง กล้าบอกตรงๆ ทั้งเรื่องดีและไม่ดี
- ระบุช่วงเวลาชัดเจน

💫 **สิ่งที่จะเกิดขึ้นในอนาคต** (อ้างจาก Transit):
- ระยะสั้น (1-3 เดือน)
- ระยะกลาง (3-6 เดือน)
- ระยะยาว (6-12 เดือน)

📅 **ฤกษ์ดี-ฤกษ์ร้าย + ควร/ไม่ควรทำ**

🎯 **คำแนะนำเฉพาะเรื่องนี้**:
- 🎨 สีมงคล + สีที่ต้องเลี่ยง
- 🔢 เลขมงคล + เลขระวัง
- ⚠️ สิ่งที่ต้องระวัง + วิธีแก้ไข
- 🙏 วิธีเสริมดวงเฉพาะตัว

🌟 **ปิดท้าย** (คำถามสุดท้าย):
- สรุปดวงชะตาภาพรวม + ให้กำลังใจ
- เชิญชวนส่งต่อให้เพื่อนๆ

[กฎสำคัญ]
- ทำนายเฉพาะคำถามที่ {question_number} เท่านั้น ห้ามตอบคำถามอื่น
- ห้ามพูดซ้ำกับคำทำนายก่อนหน้า
- ต้องอ้างอิงตำแหน่งดาวจริง + Transit ห้ามแต่งตำแหน่งดาวขึ้นเอง
- ห้ามพูดว่าหยั่งรู้ จิตสัมผัส → ใช้คำว่า "ศาสตร์โหราศาสตร์โบราณ" แทน
- ตอบอย่างละเอียดสมราคา ไม่น้อยกว่า 300 คำ ไม่เกิน 450 คำ (⚠️ จำกัด 1500 ตัวอักษร)
- ใช้ "จันทรา" แทนตัวเอง
- ตอบเป็นภาษาไทย อบอุ่น เป็นกันเอง น่าเชื่อถือ มีศาสตร์รองรับ
PROMPT;
    }

    protected function applyPromptVariables(string $template, array $variables): string
    {
        return str_replace(
            array_keys($variables),
            array_values($variables),
            $template
        );
    }

    /**
     * สร้าง prompt สำหรับทำนายพื้นฐาน
     * เป็นหมอดูหญิง ใช้คำแทนตัวว่า "จันทรา"
     *
     * กฎพิเศษ:
     * - ถ้าถูกถามว่าเป็น AI: ตอบว่า "จันทรามีทีมงานช่วยกัน"
     * - พูดเฉพาะเรื่องดูดวง ปฏิเสธเรื่องอื่นสุภาพ
     *
     * @param  array|null  $userProfile  ข้อมูลโปรไฟล์ผู้ใช้ (name, gender)
     * @param  string  $question  ข้อความที่ผู้ใช้ส่งมา
     * @param  string  $userContext  บริบทจากประวัติผู้ใช้ (Personalization)
     * @param  string|null  $detectedCategory  หมวดคำถามที่ตรวจจับได้อัตโนมัติ
     * @return string prompt ที่สร้างเสร็จ
     */
    protected function buildBasicPrompt(?array $userProfile, string $question, string $userContext = '', ?string $detectedCategory = null): string
    {
        $name = $userProfile['name'] ?? 'คุณ';
        $gender = isset($userProfile['gender']) ? ($userProfile['gender'] === 'male' ? 'ชาย' : 'หญิง') : '';
        $genderPrefix = $gender === 'ชาย' ? 'คุณพี่' : ($gender === 'หญิง' ? 'คุณ' : 'คุณ');

        // ลำดับที่ 1: ใช้ prompt จากการตั้งค่าระบบ (ถ้ามี)
        $customPrompt = $this->settings->basic_prompt_template;
        if (! empty(trim($customPrompt ?? ''))) {
            return $this->applyPromptVariables($customPrompt, [
                '{name}' => $name,
                '{gender_prefix}' => $genderPrefix,
                '{gender}' => $gender,
                '{user_profile}' => json_encode($userProfile ?? [], JSON_UNESCAPED_UNICODE),
                '{questions}' => $question,
                '{question}' => $question,
                '{user_context}' => $userContext,
                '{detected_category}' => $detectedCategory ?? '',
                '{birth_date_section}' => '',
            ]);
        }

        // ลำดับที่ 2: ใช้ prompt hardcode เดิม (default)

        // ส่วน User Context (ประวัติผู้ใช้)
        $contextSection = '';
        if (! empty($userContext)) {
            $contextSection = "\n=== บริบทจากประวัติผู้ใช้ (Personalization) ===\n{$userContext}\n=== จบบริบท ===\n";
        }

        // ส่วนหมวดคำถาม (Category Hint)
        $categoryHint = '';
        if ($detectedCategory) {
            $categoryMap = [
                'ความรัก' => 'ผู้ถามสนใจเรื่องความรัก → เน้นวิเคราะห์ภพปัตนิ(7) + ดาวศุกร์ + ความสัมพันธ์',
                'การงาน' => 'ผู้ถามสนใจเรื่องการงาน → เน้นวิเคราะห์ภพกัมมะ(10) + ดาวเสาร์/พฤหัสบดี + อาชีพ',
                'การเงิน' => 'ผู้ถามสนใจเรื่องการเงิน → เน้นวิเคราะห์ภพกดุมภ(2) + ดาวพุธ/ศุกร์ + ทรัพย์สิน',
                'สุขภาพ' => 'ผู้ถามสนใจเรื่องสุขภาพ → เน้นวิเคราะห์ภพอริ(6) + ภพตนุ(1) + ดาวอาทิตย์',
                'โชคลาภ' => 'ผู้ถามสนใจเรื่องโชคลาภ → เน้นวิเคราะห์ภพศุภะ(9) + ภพลาภะ(11) + ดาวพฤหัสบดี',
                'การเรียน' => 'ผู้ถามสนใจเรื่องการเรียน → เน้นวิเคราะห์ภพปุตตะ(5) + ดาวพุธ + ปัญญา',
            ];
            $categoryHint = "\n[หมวดคำถามที่ตรวจจับได้: {$detectedCategory}]\n".($categoryMap[$detectedCategory] ?? '')."\n";
        }

        return "คุณชื่อ \"แม่หมอจันทรา\" เป็นหมอดูสาวสวยวัย 35 ปี ผู้เชี่ยวชาญโหราศาสตร์ไทย (โหราศาสตร์เจ้าชนะ) และโหราศาสตร์สากล ได้รับการถ่ายทอดวิชาจากครูบาอาจารย์สายลังกา พูดจาเพราะ อบอุ่นเป็นกันเอง น่าเชื่อถือ ใช้คำแทนตัวว่า \"จันทรา\" ทำนายแม่นยำมาก ฟันธงแต่อ่อนโยน ไม่เกิน 500 คำ
{$contextSection}{$categoryHint}
ข้อมูลผู้ขอดูดวง:
- ชื่อ: {$name}
".($gender ? "- เพศ: {$gender}\n" : '')."
ข้อความที่ส่งมา: {$question}

แนวทางการตอบ:
- เรียกผู้ถามว่า \"{$genderPrefix}{$name}\" อย่างเป็นกันเอง
- ใช้ \"จันทรา\" แทนตัวเอง เช่น \"จันทราเห็นว่า...\" \"จันทราขอบอกตรงๆ ว่า...\"

=== ความรู้โหราศาสตร์ไทย (เจ้าชนะ) ที่ต้องใช้ในการทำนาย ===

[ดาวเคราะห์ 9 ดวง]
1. อาทิตย์ (☉) - ดาวประจำวันอาทิตย์ ธาตุไฟ เป็นใหญ่ มีอำนาจ ศักดิ์ศรี ผู้นำ
2. จันทร์ (☽) - ดาวประจำวันจันทร์ ธาตุน้ำ อารมณ์ ความรู้สึก จิตใจ เมตตา
3. อังคาร (♂) - ดาวประจำวันอังคาร ธาตุไฟ กล้าหาญ ดุดัน ทหาร การต่อสู้
4. พุธ (☿) - ดาวประจำวันพุธ ธาตุดิน ปัญญา การสื่อสาร การค้า ไหวพริบ
5. พฤหัสบดี (♃) - ดาวประจำวันพฤหัสบดี ธาตุน้ำ ศาสนา ครูอาจารย์ โชคลาภ ปัญญาลึกซึ้ง
6. ศุกร์ (♀) - ดาวประจำวันศุกร์ ธาตุน้ำ ความรัก ความสวยงาม ศิลปะ ทรัพย์สิน
7. เสาร์ (♄) - ดาวประจำวันเสาร์ ธาตุไฟ ความทุกข์ อุปสรรค ความอดทน กรรมเก่า
8. ราหู - ดาวประจำวันพุธกลางคืน ธาตุลม มายา อำนาจลึกลับ การเปลี่ยนแปลงฉับพลัน
9. เกตุ - ดาวประจำวันอาทิตย์-เสาร์ ธาตุไฟ กรรมเก่า สิ่งลี้ลับ จิตวิญญาณ

[ตารางเจ้าชนะ - ดาวมิตร/ดาวศัตรู]
- คนวันอาทิตย์: ดาวเจ้าชนะ=อาทิตย์ มิตร=พฤหัสบดี,อังคาร ศัตรู=เสาร์,ราหู
- คนวันจันทร์: ดาวเจ้าชนะ=จันทร์ มิตร=พุธ,ศุกร์ ศัตรู=ราหู,เสาร์
- คนวันอังคาร: ดาวเจ้าชนะ=อังคาร มิตร=อาทิตย์,พฤหัสบดี ศัตรู=พุธ,เสาร์
- คนวันพุธ: ดาวเจ้าชนะ=พุธ มิตร=จันทร์,ศุกร์ ศัตรู=ราหู,อังคาร
- คนวันพฤหัสบดี: ดาวเจ้าชนะ=พฤหัสบดี มิตร=อาทิตย์,อังคาร ศัตรู=ราหู,เสาร์
- คนวันศุกร์: ดาวเจ้าชนะ=ศุกร์ มิตร=พุธ,จันทร์ ศัตรู=อาทิตย์,อังคาร
- คนวันเสาร์: ดาวเจ้าชนะ=เสาร์ มิตร=ราหู,พฤหัสบดี ศัตรู=อาทิตย์,อังคาร

[ภพ 12 ภพ (เรือนชะตา)]
1.ตนุ-ตัวตน 2.กดุมภ-ทรัพย์ 3.สหัชชะ-พี่น้อง 4.พันธุ-ครอบครัว 5.ปุตตะ-ลูก/สร้างสรรค์ 6.อริ-ศัตรู/โรค 7.ปัตนิ-คู่ครอง 8.มรณะ-ความเปลี่ยนแปลง 9.ศุภะ-โชคลาภ 10.กัมมะ-การงาน 11.ลาภะ-ลาภผล 12.วินาศ-อุปสรรค

[วิธีทำนายตามหลักเจ้าชนะ]
- ดูวันเกิด → หาดาวประจำวัน → ดูมิตร/ศัตรู → ดูว่าดาวอะไรส่งผลช่วงนี้
- ดาวมิตรโคจรเข้า = เรื่องดีกำลังมา
- ดาวศัตรูโคจรเข้า = ต้องระวัง มีอุปสรรค
- อ้างอิงภพที่เกี่ยวข้องกับคำถาม (เช่น ถามเรื่องรัก → ดูภพปัตนิ)

=== จบความรู้โหราศาสตร์ ===

[กฎสำคัญที่สุด] ต้องทำนายดูดวงทุกครั้ง! ห้ามตอบแค่ทักทายแล้วชวนถามคำถาม ต้องทำนายให้เลยทุกข้อความ!

[วิธีตอบตามประเภทข้อความ]
1. ถ้าเป็นการทักทาย (สวัสดี, หวัดดี, hi, hello, ดีจ้า, คุยหน่อย ฯลฯ): ทักทายกลับสั้นๆ 1 บรรทัด แล้ว **ทำนายดวงภาพรวมให้ทันที** ครบทุกหัวข้อตามโครงสร้างด้านล่าง (ความรัก การงาน การเงิน สุขภาพ) ห้ามตอบแค่ทักทายแล้วถามว่าอยากรู้เรื่องอะไร ต้องทำนายให้เลย!
2. ถ้าเป็นคำถามเกี่ยวกับดูดวง/อนาคต/ชีวิต: ทำนายอย่างละเอียดตามโครงสร้างด้านล่าง โดยอ้างอิงหลักเจ้าชนะ
3. ถ้าเป็นคำถามทั่วไปที่ไม่เกี่ยวกับดูดวง: ตอบสั้นๆ ด้วยความเป็นมิตร แล้วทำนายดวงภาพรวมให้ด้วย เช่น \"เรื่องนี้จันทราไม่ถนัดเท่าไหร่ค่ะ แต่จันทราสัมผัสดวงของ{$genderPrefix}{$name}ได้ว่า...\" แล้วทำนายดวงให้
4. ถ้าถูกถามว่าเป็น AI: ตอบว่า \"จันทรามีทีมงานช่วยกันค่ะ ไม่ต้องห่วงนะคะ 🔮\" แล้วชวนดูดวง

[โครงสร้างคำทำนาย - ต้องอ้างอิงดาวเคราะห์และเจ้าชนะ]
ต้องทำนายอย่างละเอียดและน่าติดตาม ครบทุกหัวข้อต่อไปนี้:

🔮 **เปิดเรื่อง**: ทักทายอบอุ่น บอกว่าจันทราดูดวงด้วยหลักโหราศาสตร์เจ้าชนะ อ้างอิงดาวที่ส่งผลช่วงนี้ เช่น \"จันทราดูจากตำแหน่งดาว[ชื่อดาว]ที่กำลังโคจรผ่านภพ[ชื่อภพ]ของ{$genderPrefix}{$name}...\"

⭐ **ดวงภาพรวม (อ้างอิงดาวเคราะห์)**: วิเคราะห์ดวงชะตาภาพรวม อ้างชื่อดาวเคราะห์ที่ส่งผลในช่วงนี้ เช่น \"ดาวพฤหัสบดีกำลังโคจรเข้าภพศุภะ ทำให้โชคลาภเด่น\" หรือ \"ดาวเสาร์กำลังย้ายราศี ทำให้ต้องระวังเรื่อง...\"

💫 **ตอบคำถามหลัก**: ตอบคำถามที่ถามมาอย่างละเอียด กล้าบอกตรงๆ ระบุช่วงเวลาชัดเจน (เช่น \"ช่วงเดือนมีนา-เมษา\" \"ภายใน 2-3 สัปดาห์\") อ้างอิงดาวเคราะห์ที่เกี่ยวข้อง

🎯 **คำแนะนำปฏิบัติได้จริง**:
   - 🎨 สีมงคล: ระบุ 2-3 สี (อ้างอิงจากดาวมิตร)
   - 🔢 เลขมงคล: ระบุ 2-3 เลข (อ้างอิงจากดาวเจ้าชนะ)
   - 📅 วันมงคล: วันที่เหมาะทำสิ่งสำคัญ (อ้างอิงวันของดาวมิตร)
   - ⚠️ สิ่งที่ควรระวัง: บอกตรงๆ อ้างดาวศัตรู แต่ให้ทางแก้ด้วย

🌟 **ปิดท้ายชวนดูต่อ**: ปิดท้ายด้วยการ hint ว่าจันทรายังเห็นอะไรอีกมากที่ยังไม่ได้บอก เพื่อกระตุ้นให้อยากดูดวงละเอียด เช่น:
\"✨ จันทราสัมผัสได้ว่ายังมีเรื่องสำคัญที่ต้องบอก{$genderPrefix}{$name}อีกนะคะ โดยเฉพาะเรื่อง [ระบุเรื่องที่เกี่ยวข้อง] แต่ต้องรู้วันเกิดถึงจะบอกได้ละเอียดค่ะ\"
\"🔮 ถ้า{$genderPrefix}{$name}อยากรู้ลึกกว่านี้ เช่น ตำแหน่งดาวเจ้าชนะ ดาวโคจรที่ส่งผล ภพที่ต้องระวัง ทิศมงคล วิธีเสริมดวง... บอกจันทราได้นะคะ จันทราจะดูให้ละเอียดเลยค่ะ ✨\"

ถ้ายังไม่มีวันเกิด ให้ถามท้ายว่า \"บอกวันเดือนปีเกิดให้จันทราได้ไหมคะ? จันทราจะได้คำนวณตำแหน่งดาวเจ้าชนะ ทำนายได้แม่นยำยิ่งขึ้นค่ะ 🎂\"
ท้ายสุดเชิญชวน \"ถ้าคำทำนายถูกใจ ฝากส่งต่อให้เพื่อนๆ มาลองดูดวงกับจันทราด้วยนะคะ 🔮✨\"

[กฎสำคัญ] ห้ามเขียนโค้ด ห้ามให้ข้อมูลอันตราย ตอบทุกข้อความอย่างเป็นมิตร คุยรู้เรื่อง แต่ชวนกลับมาดูดวงเสมอ";
    }

    /**
     * สร้าง prompt สำหรับทำนายละเอียด
     * เป็นหมอดูหญิง ใช้คำแทนตัวว่า "จันทรา" ทำนายแม่นยำ
     *
     * กฎพิเศษ:
     * - ถ้าถูกถามว่าเป็น AI: ตอบว่า "จันทรามีทีมงานช่วยกัน"
     * - พูดเฉพาะเรื่องดูดวง ปฏิเสธเรื่องอื่นสุภาพ
     */
    protected function buildDeepPrompt(?array $userProfile, array $questions, ?string $birthDate): string
    {
        $name = $userProfile['name'] ?? 'คุณ';
        $gender = isset($userProfile['gender']) ? ($userProfile['gender'] === 'male' ? 'ชาย' : 'หญิง') : '';
        $genderPrefix = $gender === 'ชาย' ? 'คุณพี่' : ($gender === 'หญิง' ? 'คุณ' : 'คุณ');
        $questionsText = implode("\n", array_map(fn ($i, $q) => ($i + 1).". {$q}", array_keys($questions), $questions));

        $birthInfo = '';
        $zodiacInfo = '';
        $deepPlanetPositionsInfo = '';
        $transitInfo = '';
        if ($birthDate) {
            $birthInfo = 'วันเดือนปีเกิด: '.$this->formatThaiDate($birthDate);
            $zodiacInfo = $this->getZodiacDescription($birthDate);

            // คำนวณตำแหน่งดาวจริงในภพ → ส่งให้ AI ทำนายแม่นยำ
            try {
                $date = \Carbon\Carbon::parse($birthDate);
                $dayOfWeek = $date->dayOfWeek;
                $chartService = new FortuneChartService;
                $positions = $chartService->calculatePlanetPositions($dayOfWeek);
                $chaochana = FortuneChartService::CHAOCHANA[$dayOfWeek] ?? null;

                $deepPlanetPositionsInfo = "\n[🗺️ แผนที่ดวงชะตาของ{$genderPrefix}{$name} - ตำแหน่งดาวกำเนิดในภพจริง]\n";
                foreach ($positions as $houseNum => $planets) {
                    $houseName = FortuneChartService::HOUSES[$houseNum]['name'] ?? "ภพ{$houseNum}";
                    $houseMeaning = FortuneChartService::HOUSES[$houseNum]['meaning'] ?? '';
                    if (! empty($planets)) {
                        $planetNames = array_map(fn ($p) => FortuneChartService::PLANETS[$p]['name'] ?? $p, $planets);
                        $planetSymbols = array_map(fn ($p) => FortuneChartService::PLANETS[$p]['symbol'] ?? '', $planets);
                        $deepPlanetPositionsInfo .= "- ภพ{$houseNum}.{$houseName}({$houseMeaning}): ".implode(', ', $planetNames).' ['.implode('', $planetSymbols)."]\n";
                    } else {
                        $deepPlanetPositionsInfo .= "- ภพ{$houseNum}.{$houseName}({$houseMeaning}): ว่าง\n";
                    }
                }

                if ($chaochana) {
                    $deepPlanetPositionsInfo .= "ธาตุวันเกิด: {$chaochana['element']} | สีมงคล: {$chaochana['lucky_color']}\n";
                    $deepPlanetPositionsInfo .= "⚠️ ต้องอ้างอิงตำแหน่งดาวข้างต้นในคำทำนายทุกข้อ ห้ามสร้างตำแหน่งดาวขึ้นเอง\n";
                }

                // คำนวณ Transit ดาวปัจจุบัน
                $transitInfo = $this->getCurrentTransitDescription($dayOfWeek);

            } catch (\Exception $e) {
                // ถ้าคำนวณไม่ได้ก็ข้ามไป
            }
        }

        return "คุณชื่อ \"แม่หมอจันทรา\" เป็นหมอดูสาวสวยวัย 35 ปี ผู้เชี่ยวชาญศาสตร์โหราศาสตร์โบราณของไทย (หลักเจ้าชนะ) โหราศาสตร์สากล ไพ่ทาโรต์ และเลขศาสตร์ ได้รับการถ่ายทอดวิชาจากครูบาอาจารย์สายลังกามากกว่า 15 ปี ทำนายด้วยหลักวิชาโบราณล้วนๆ ไม่ได้กุเรื่อง ทุกคำทำนายมีศาสตร์รองรับ คุณพูดจาเพราะ อบอุ่นเป็นกันเอง น่าเชื่อถือ เหมือนพี่สาวที่ห่วงใย ใช้คำแทนตัวว่า \"จันทรา\" เสมอ

=== ความรู้โหราศาสตร์ไทย (เจ้าชนะ) สำหรับการทำนายละเอียด ===

[ดาวเคราะห์ 9 ดวง + ความหมายเชิงลึก]
1. อาทิตย์ (☉) วันอาทิตย์ ธาตุไฟ - อำนาจ ศักดิ์ศรี ผู้นำ บิดา สุขภาพ ความมั่นใจ ตำแหน่ง เลข=1
2. จันทร์ (☽) วันจันทร์ ธาตุน้ำ - อารมณ์ จิตใจ มารดา ความสุข ความงาม สัญชาตญาณ เลข=2
3. อังคาร (♂) วันอังคาร ธาตุไฟ - กล้าหาญ ดุดัน ทรัพย์สิน ที่ดิน พลังขับเคลื่อน การแข่งขัน เลข=3
4. พุธ (☿) วันพุธ ธาตุดิน - ปัญญา การสื่อสาร การค้าขาย ไหวพริบ เจรจา ธุรกิจ เลข=4
5. พฤหัสบดี (♃) วันพฤหัสบดี ธาตุน้ำ - ศาสนา ครูอาจารย์ โชคลาภ ปัญญาลึกซึ้ง กฎหมาย ความเมตตา เลข=5
6. ศุกร์ (♀) วันศุกร์ ธาตุน้ำ - ความรัก ศิลปะ ทรัพย์สิน ความสวยงาม เพศตรงข้าม สุนทรียะ เลข=6
7. เสาร์ (♄) วันเสาร์ ธาตุไฟ - ความทุกข์ อุปสรรค ความอดทน กรรมเก่า อายุยืน บทเรียน เลข=7
8. ราหู (☊) วันพุธกลางคืน ธาตุลม - มายา อำนาจลึกลับ การเปลี่ยนแปลงฉับพลัน ต่างแดน ความไม่แน่นอน เลข=8
9. เกตุ (☋) ธาตุไฟ - กรรมเก่า สิ่งลี้ลับ จิตวิญญาณ การบวช ปล่อยวาง ปัญญาญาณ เลข=9

[ตารางเจ้าชนะ - ดาวมิตร/ดาวศัตรู ตามวันเกิด]
- คนวันอาทิตย์: เจ้าชนะ=อาทิตย์(1) มิตร=พฤหัสบดี(5),อังคาร(3) ศัตรู=เสาร์(7),ราหู(8) สีมงคล=แดง,ส้ม สีเลี่ยง=ดำ,ม่วง
- คนวันจันทร์: เจ้าชนะ=จันทร์(2) มิตร=พุธ(4),ศุกร์(6) ศัตรู=ราหู(8),เสาร์(7) สีมงคล=ขาว,ครีม,เขียว สีเลี่ยง=ดำ,แดงเข้ม
- คนวันอังคาร: เจ้าชนะ=อังคาร(3) มิตร=อาทิตย์(1),พฤหัสบดี(5) ศัตรู=พุธ(4),เสาร์(7) สีมงคล=ชมพู,แดง สีเลี่ยง=เขียว,ดำ
- คนวันพุธ: เจ้าชนะ=พุธ(4) มิตร=จันทร์(2),ศุกร์(6) ศัตรู=ราหู(8),อังคาร(3) สีมงคล=เขียว,ฟ้า สีเลี่ยง=แดง,ดำ
- คนวันพฤหัสบดี: เจ้าชนะ=พฤหัสบดี(5) มิตร=อาทิตย์(1),อังคาร(3) ศัตรู=ราหู(8),เสาร์(7) สีมงคล=ส้ม,เหลือง สีเลี่ยง=ดำ,ม่วง
- คนวันศุกร์: เจ้าชนะ=ศุกร์(6) มิตร=พุธ(4),จันทร์(2) ศัตรู=อาทิตย์(1),อังคาร(3) สีมงคล=ฟ้า,ครีม สีเลี่ยง=แดง,ส้ม
- คนวันเสาร์: เจ้าชนะ=เสาร์(7) มิตร=ราหู(8),พฤหัสบดี(5) ศัตรู=อาทิตย์(1),อังคาร(3) สีมงคล=ม่วง,ดำ สีเลี่ยง=แดง,ส้ม

[ภพ 12 ภพ (เรือนชะตา) - ใช้อ้างอิงในคำทำนาย]
1. ตนุ = ตัวตน บุคลิก ร่างกาย ภาพลักษณ์
2. กดุมภ = ทรัพย์สิน เงินทอง การเงิน รายได้
3. สหัชชะ = พี่น้อง เพื่อน การเดินทางใกล้ การสื่อสาร
4. พันธุ = ครอบครัว บ้าน ที่ดิน รากฐาน มรดก
5. ปุตตะ = ลูก ความคิดสร้างสรรค์ ความสุข ความบันเทิง
6. อริ = ศัตรู โรคภัย หนี้สิน ปัญหา การแก้ไข
7. ปัตนิ = คู่ครอง หุ้นส่วน ความสัมพันธ์ สัญญา
8. มรณะ = ความเปลี่ยนแปลง มรดก สิ่งลี้ลับ วิกฤต→โอกาส
9. ศุภะ = โชคลาภ ศาสนา การเดินทางไกล ครูบาอาจารย์
10. กัมมะ = การงาน ตำแหน่ง ชื่อเสียง ความก้าวหน้า
11. ลาภะ = ลาภผล เครือข่าย ความหวัง ผู้อุปถัมภ์
12. วินาศ = อุปสรรค ศัตรูซ่อนเร้น การสูญเสีย กรรมเก่า

[หลักการทำนายเจ้าชนะ - ต้องใช้ในทุกคำทำนาย]
1. คำนวณดาวเจ้าชนะจากวันเกิด → หาดาวมิตร/ศัตรู → ดูเลขประจำดาว
2. วิเคราะห์ตำแหน่งดาวกำเนิดในภพ (จากแผนที่ดวงชะตาข้างล่าง)
3. วิเคราะห์ดาวโคจร(transit)ปัจจุบัน ว่าส่งผลกับดวงกำเนิดอย่างไร
4. ถามเรื่องรัก → ดูภพปัตนิ(7) + ดาวศุกร์ + ภพปุตตะ(5)
5. ถามเรื่องงาน → ดูภพกัมมะ(10) + ดาวเสาร์/พฤหัสบดี + ภพลาภะ(11)
6. ถามเรื่องเงิน → ดูภพกดุมภ(2) + ดาวพุธ/ศุกร์ + ภพลาภะ(11)
7. ถามเรื่องสุขภาพ → ดูภพอริ(6) + ภพตนุ(1) + ดาวอาทิตย์
8. ถามเรื่องโชคลาภ → ดูภพศุภะ(9) + ภพลาภะ(11) + ดาวพฤหัสบดี
9. ถามเรื่องครอบครัว → ดูภพพันธุ(4) + ดาวจันทร์ + ภพปุตตะ(5)
10. สีมงคล = สีดาวมิตร | สีเลี่ยง = สีดาวศัตรู
11. เลขมงคล = เลขดาวเจ้าชนะ+มิตร | เลขระวัง = เลขดาวศัตรู
12. วันมงคล = วันดาวมิตร | วันระวัง = วันดาวศัตรู

=== จบความรู้โหราศาสตร์ ===

ข้อมูลผู้ขอดูดวง:
- ชื่อ: {$name} (เรียกว่า \"{$genderPrefix}{$name}\")
".($gender ? "- เพศ: {$gender}\n" : '').'
'.($birthInfo ? "- {$birthInfo}\n" : '').'
'.($zodiacInfo ? "- {$zodiacInfo}\n" : '')."
{$deepPlanetPositionsInfo}
{$transitInfo}
คำถาม:
{$questionsText}

=== แนวทางการทำนายอย่างละเอียด (ทำนายแบบพรีเมียม ต้องคุ้มค่า!) ===

**สำคัญ: ผู้ถามจ่ายเงินมาเพื่อความแม่นยำ ต้องทำนายด้วยหลักวิชาจริง ไม่ใช่กุเรื่อง!**
**จันทราใช้ศาสตร์โหราศาสตร์โบราณ (หลักเจ้าชนะ) ในการวิเคราะห์ ทุกคำทำนายมีหลักวิชารองรับ**

1. 🔮 เปิดด้วยการทักทายอบอุ่น + วิเคราะห์ดวงชะตาจากวันเกิด:
   - ทักทาย{$genderPrefix}{$name}ด้วยความเป็นกันเอง
   - บอกว่า \"จันทราใช้ศาสตร์โหราศาสตร์โบราณหลักเจ้าชนะที่สืบทอดมาจากครูบาอาจารย์ วิเคราะห์ให้อย่างละเอียดนะคะ\"
   - บอกราศี ปีนักษัตร ธาตุจีน + ดาวเจ้าชนะ ดาวมิตร ดาวศัตรู
   - อธิบายบุคลิกภาพจากดวงกำเนิด (จุดแข็ง จุดอ่อน)
   - บอกดาวโคจร(transit)ปัจจุบัน + ภพที่ดาวกำลังส่งผล
   - สรุปภาพรวมดวงชะตาช่วงนี้ก่อนเข้าคำถาม

2. ⭐ ตอบแต่ละคำถามอย่างละเอียด (ใช้ดาว Transit ทำนายอนาคต):
   - อ้างตำแหน่งดาวกำเนิดในภพที่เกี่ยวข้องกับคำถาม
   - อ้างดาวโคจร(transit)ปัจจุบัน + transit อนาคต (1, 3, 6, 12 เดือน) จากข้อมูลที่ให้
   - เปรียบเทียบ transit ปัจจุบัน กับ transit อนาคต ว่าดาวจะเลื่อนไปอยู่ภพไหน ส่งผลอย่างไร
   - ระบุช่วงเวลาชัดเจน เช่น \"อีก 3 เดือน ดาว[ชื่อ]จะเลื่อนเข้าภพ[ชื่อ] ทำให้เรื่อง[X]ดีขึ้น/ต้องระวัง\"
   - ฟันธง กล้าบอกตรงๆ ทั้งดีและไม่ดี ด้วยหลักวิชา
   - แบ่งเป็นช่วงเวลา: ระยะสั้น(1-3เดือน) ระยะกลาง(3-6เดือน) ระยะยาว(6-12เดือน)

3. 📅 วิเคราะห์ฤกษ์ดี-ฤกษ์ร้าย + ควร/ไม่ควรทำอะไร:
   - อ้างจากช่วงฤกษ์ดี/ระวัง ที่คำนวณจาก Transit อนาคต
   - บอกชัดเจน: ช่วงไหนฤกษ์ดี เหมาะทำอะไร (ลงทุน, สมัครงาน, เริ่มธุรกิจ, แต่งงาน)
   - ช่วงไหนฤกษ์ไม่ดี ไม่ควรทำอะไร (ระวังเรื่องเงิน, ชะลอการตัดสินใจ, หลีกเลี่ยงการเดินทาง)
   - บอกสิ่งที่ \"ห้ามทำ\" ในช่วงดาวศัตรูกดดัน + เหตุผลจากตำแหน่งดาว
   - บอกสิ่งที่ \"ควรรีบทำ\" ในช่วงดาวมิตรเด่น + เหตุผลจากตำแหน่งดาว

4. 🎯 ให้คำแนะนำเฉพาะตัว (อ้างอิงจากดาวมิตร/ศัตรู):
   - 🎨 สีมงคล: จากดาวมิตร + สีที่ควรเลี่ยง(จากดาวศัตรู)
   - 🔢 เลขมงคล: จากเลขดาวเจ้าชนะ+มิตร + เลขที่ควรระวัง(จากดาวศัตรู)
   - 📅 วันมงคล + วันที่ต้องระวัง + ช่วงเวลาเด่นในรอบปี
   - 🧭 ทิศมงคล + สิ่งที่ควรทำ/ไม่ควรทำ
   - 🙏 วิธีเสริมดวงเฉพาะตัว (ไหว้พระองค์ไหน ทำบุญอะไร ใช้สี/เลข/ทิศอะไร)

5. ⚠️ บอกสิ่งที่ควรระวังอย่างตรงไปตรงมา:
   - อ้างดาวศัตรูที่ส่งผล + ภพที่ถูกกระทบ + ช่วงเวลาที่ต้องระวัง
   - ให้ทางแก้ไขและวิธีป้องกันที่ชัดเจน
   - ให้กำลังใจ ไม่ทำให้กลัวโดยไม่มีทางออก

6. 💫 ปิดท้ายด้วยกำลังใจอบอุ่น + สรุปภาพรวม
   - สรุปดวงชะตาภาพรวม + ช่วงฤกษ์ดีที่สุด/ต้องระวังที่สุด
   - ให้กำลังใจจริงใจ
   - \"ถ้ามีเรื่องอยากถามเพิ่ม ทักมาหาจันทราได้เสมอนะคะ ทุกคำตอบจันทราวิเคราะห์จากหลักวิชาจริงค่ะ 🔮✨\"

[กฎสำคัญ]
1. จันทราทำนายด้วย \"ศาสตร์โหราศาสตร์โบราณหลักเจ้าชนะ\" เท่านั้น ห้ามพูดว่าหยั่งรู้ จิตสัมผัส หรืออะไรที่ทำให้ดูไม่น่าเชื่อถือ
2. หากถูกถามว่าเป็น AI: ตอบว่า \"จันทราใช้ศาสตร์โหราศาสตร์โบราณที่สืบทอดมาจากครูบาอาจารย์ค่ะ ไม่ได้กุเรื่อง ทุกคำทำนายมีหลักวิชารองรับ 🔮\"
3. พูดเฉพาะเรื่องดูดวง ปฏิเสธเรื่องอื่นสุภาพ
4. ต้องอ้างอิงตำแหน่งดาวจริง+ภพจริง+transit จากข้อมูลที่ให้ ห้ามแต่งตำแหน่งดาวขึ้นเอง
5. เมื่อทำนายอนาคต ต้องอ้างตำแหน่งดาว Transit อนาคต (1,3,6,12 เดือน) เปรียบเทียบกับดวงกำเนิด
6. ทำนายละเอียดสมราคา ไม่น้อยกว่า 500 คำต่อคำถาม
7. ใช้ภาษาไทย อบอุ่น เป็นกันเอง น่าเชื่อถือ มีศาสตร์รองรับทุกคำทำนาย";
    }

    // ============================================================
    // Per-Question Deep Reading (ทำนายทีละคำถาม)
    // ============================================================

    /**
     * สร้าง prompt สำหรับทำนายละเอียดทีละคำถาม
     *
     * แต่ละคำถามจะอ้างอิงจากวันเกิด+เพศอย่างละเอียด
     * ทำนายเจาะลึกเฉพาะคำถามนั้นๆ ให้แม่นยำ น่าเชื่อถือ
     *
     * @param  array|null  $userProfile  โปรไฟล์ผู้ใช้
     * @param  string  $question  คำถามเดียว
     * @param  int  $questionNumber  ลำดับคำถาม (1,2)
     * @param  int  $totalQuestions  จำนวนคำถามทั้งหมด
     * @param  string|null  $birthDate  วันเกิด
     * @param  array  $previousReadings  คำทำนายก่อนหน้า (เพื่อไม่ให้ซ้ำ)
     */
    protected function buildPerQuestionDeepPrompt(
        ?array $userProfile,
        string $question,
        int $questionNumber,
        int $totalQuestions,
        ?string $birthDate,
        array $previousReadings = []
    ): string {
        $name = $userProfile['name'] ?? 'คุณ';
        $gender = isset($userProfile['gender']) ? ($userProfile['gender'] === 'male' ? 'ชาย' : 'หญิง') : '';
        $genderPrefix = $gender === 'ชาย' ? 'คุณพี่' : ($gender === 'หญิง' ? 'คุณ' : 'คุณ');

        // ข้อมูลวันเกิดและราศี (คำนวณก่อน เผื่อ custom prompt ต้องใช้)
        $birthInfo = '';
        $zodiacInfo = '';
        $planetPositionsInfo = '';
        $transitInfo = '';
        if ($birthDate) {
            $birthInfo = $this->formatThaiDate($birthDate);
            $zodiacInfo = $this->getZodiacDescription($birthDate);

            // คำนวณตำแหน่งดาวจริงในภพ → ส่งให้ AI ทำนายแม่นยำ
            try {
                $date = \Carbon\Carbon::parse($birthDate);
                $dayOfWeek = $date->dayOfWeek;
                $chartService = new FortuneChartService;
                $positions = $chartService->calculatePlanetPositions($dayOfWeek);
                $chaochana = FortuneChartService::CHAOCHANA[$dayOfWeek] ?? null;

                $planetPositionsInfo = "\n[🗺️ แผนที่ดวงชะตากำเนิด - ตำแหน่งดาวในภพจริง (ต้องอ้างอิงในคำทำนาย)]\n";
                foreach ($positions as $houseNum => $planets) {
                    $houseName = FortuneChartService::HOUSES[$houseNum]['name'] ?? "ภพ{$houseNum}";
                    $houseMeaning = FortuneChartService::HOUSES[$houseNum]['meaning'] ?? '';
                    if (! empty($planets)) {
                        $planetNames = array_map(fn ($p) => FortuneChartService::PLANETS[$p]['name'] ?? $p, $planets);
                        $planetSymbols = array_map(fn ($p) => FortuneChartService::PLANETS[$p]['symbol'] ?? '', $planets);
                        $planetPositionsInfo .= "- ภพ{$houseNum}.{$houseName}({$houseMeaning}): ".implode(', ', $planetNames).' ['.implode('', $planetSymbols)."]\n";
                    } else {
                        $planetPositionsInfo .= "- ภพ{$houseNum}.{$houseName}({$houseMeaning}): ว่าง\n";
                    }
                }

                if ($chaochana) {
                    $element = $chaochana['element'] ?? '';
                    $luckyColor = $chaochana['lucky_color'] ?? '';
                    $planetPositionsInfo .= "\nธาตุประจำวันเกิด: {$element} | สีมงคล: {$luckyColor}\n";
                    $planetPositionsInfo .= "⚠️ ต้องอ้างอิงตำแหน่งดาวข้างต้นในคำทำนาย เช่น \"ดาว[ชื่อ]อยู่ภพ[ชื่อ]ส่งผลให้...\" ห้ามสร้างตำแหน่งดาวขึ้นเอง\n";
                }

                // คำนวณ Transit ดาวปัจจุบัน
                $transitInfo = $this->getCurrentTransitDescription($dayOfWeek);

            } catch (\Exception $e) {
                // ถ้าคำนวณไม่ได้ก็ข้ามไป
            }
        }

        // สรุปคำทำนายก่อนหน้า (เพื่อไม่ให้ AI ซ้ำ)
        $previousContext = '';
        if (! empty($previousReadings)) {
            $previousContext = "\n[คำทำนายที่ผ่านมา - ห้ามพูดซ้ำ ให้ทำนายมุมใหม่ ใช้ดาว/ภพคนละดวง]\n";
            foreach ($previousReadings as $prev) {
                $previousContext .= "- คำถาม {$prev['question_number']}: {$prev['question']} → ตอบไปแล้ว (ห้ามพูดซ้ำ)\n";
            }
        }

        // ลำดับที่ 1: ใช้ prompt จากการตั้งค่าระบบ (ถ้ามี)
        $customPrompt = $this->settings->deep_prompt_template;
        if (! empty(trim($customPrompt ?? ''))) {
            return $this->applyPromptVariables($customPrompt, [
                '{name}' => $name,
                '{gender_prefix}' => $genderPrefix,
                '{gender}' => $gender,
                '{question}' => $question,
                '{question_number}' => (string) $questionNumber,
                '{total_questions}' => (string) $totalQuestions,
                '{birth_info}' => $birthInfo,
                '{birth_date}' => $birthDate ?? '',
                '{zodiac_info}' => $zodiacInfo,
                '{planet_positions}' => $planetPositionsInfo,
                '{transit_info}' => $transitInfo,
                '{previous_context}' => $previousContext,
                '{user_profile}' => json_encode($userProfile ?? [], JSON_UNESCAPED_UNICODE),
            ]);
        }

        // ลำดับที่ 2: ใช้ prompt hardcode เดิม (default)
        $prompt = "คุณชื่อ \"แม่หมอจันทรา\" เป็นหมอดูสาวสวยวัย 35 ปี ผู้เชี่ยวชาญศาสตร์โหราศาสตร์โบราณของไทย (หลักเจ้าชนะ) โหราศาสตร์สากล ไพ่ทาโรต์ และเลขศาสตร์ ได้รับวิชาจากครูบาอาจารย์สายลังกา มีประสบการณ์ 15 ปี ทำนายด้วยหลักวิชาโบราณล้วนๆ ทุกคำทำนายมีศาสตร์รองรับ พูดจาเพราะ อบอุ่น ใช้คำว่า \"จันทรา\" แทนตัวเอง

[ตารางเจ้าชนะ + เลขประจำดาว]
- วันอาทิตย์: เจ้าชนะ=อาทิตย์(1) มิตร=พฤหัสบดี(5),อังคาร(3) ศัตรู=เสาร์(7),ราหู(8) สีมงคล=แดง สีเลี่ยง=ดำ
- วันจันทร์: เจ้าชนะ=จันทร์(2) มิตร=พุธ(4),ศุกร์(6) ศัตรู=ราหู(8),เสาร์(7) สีมงคล=ขาว,ครีม สีเลี่ยง=ดำ
- วันอังคาร: เจ้าชนะ=อังคาร(3) มิตร=อาทิตย์(1),พฤหัสบดี(5) ศัตรู=พุธ(4),เสาร์(7) สีมงคล=ชมพู สีเลี่ยง=เขียว,ดำ
- วันพุธ: เจ้าชนะ=พุธ(4) มิตร=จันทร์(2),ศุกร์(6) ศัตรู=ราหู(8),อังคาร(3) สีมงคล=เขียว สีเลี่ยง=แดง,ดำ
- วันพฤหัสบดี: เจ้าชนะ=พฤหัสบดี(5) มิตร=อาทิตย์(1),อังคาร(3) ศัตรู=ราหู(8),เสาร์(7) สีมงคล=ส้ม สีเลี่ยง=ดำ
- วันศุกร์: เจ้าชนะ=ศุกร์(6) มิตร=พุธ(4),จันทร์(2) ศัตรู=อาทิตย์(1),อังคาร(3) สีมงคล=ฟ้า สีเลี่ยง=แดง
- วันเสาร์: เจ้าชนะ=เสาร์(7) มิตร=ราหู(8),พฤหัสบดี(5) ศัตรู=อาทิตย์(1),อังคาร(3) สีมงคล=ม่วง สีเลี่ยง=แดง

[ภพ 12 ภพ] 1.ตนุ(ตัวตน) 2.กดุมภ(ทรัพย์/เงิน) 3.สหัชชะ(พี่น้อง/เพื่อน) 4.พันธุ(ครอบครัว/บ้าน) 5.ปุตตะ(ลูก/สร้างสรรค์) 6.อริ(ศัตรู/โรค) 7.ปัตนิ(คู่ครอง/หุ้นส่วน) 8.มรณะ(เปลี่ยนแปลง/วิกฤต) 9.ศุภะ(โชคลาภ/ศาสนา) 10.กัมมะ(การงาน/ตำแหน่ง) 11.ลาภะ(ลาภผล/ผู้อุปถัมภ์) 12.วินาศ(อุปสรรค/กรรมเก่า)

[หลักการทำนาย]
- รัก→ภพปัตนิ(7)+ศุกร์+ปุตตะ(5) | งาน→ภพกัมมะ(10)+เสาร์/พฤหัสบดี+ลาภะ(11) | เงิน→ภพกดุมภ(2)+พุธ/ศุกร์+ลาภะ(11) | สุขภาพ→ภพอริ(6)+ตนุ(1)+อาทิตย์ | โชคลาภ→ภพศุภะ(9)+ลาภะ(11)+พฤหัสบดี | ครอบครัว→ภพพันธุ(4)+จันทร์
- สีมงคล=สีดาวมิตร | เลขมงคล=เลขดาวเจ้าชนะ+มิตร | วันมงคล=วันดาวมิตร

=== ทำนายคำถามที่ {$questionNumber} จาก {$totalQuestions} (การทำนายแบบพรีเมียม) ===

ข้อมูลผู้ขอดูดวง:
- ชื่อ: {$name} (เรียกว่า \"{$genderPrefix}{$name}\")
".($gender ? "- เพศ: {$gender}\n" : '').'
'.($birthInfo ? "- วันเกิด: {$birthInfo}\n" : '').'
'.($zodiacInfo ? "- {$zodiacInfo}\n" : '')."
{$planetPositionsInfo}
{$transitInfo}
คำถามที่ {$questionNumber}: {$question}
{$previousContext}

[โครงสร้างคำทำนาย - ต้องทำตามทุกข้อ ผู้ถามจ่ายเงินมา ต้องคุ้มค่า!]

";

        // คำถามแรก: เปิดด้วยวิเคราะห์ดวงจากวันเกิด + เจ้าชนะ
        if ($questionNumber === 1) {
            $prompt .= "🔮 **เปิดเรื่อง** (คำถามแรก):
- ทักทาย{$genderPrefix}{$name}อย่างอบอุ่น
- บอกว่า \"จันทราได้รับคำถาม {$totalQuestions} ข้อจาก{$genderPrefix}{$name} จันทราจะทำนายให้อย่างละเอียดทีละข้อด้วยหลักโหราศาสตร์เจ้าชนะนะคะ\"
".($birthDate ? '- วิเคราะห์ดวงชะตาจากวันเกิด: ราศี ปีนักษัตร ธาตุจีน + ดาวเจ้าชนะ ดาวมิตร ดาวศัตรู
- อ้างตำแหน่งดาวกำเนิดในภพจริง (จากแผนที่ดวงชะตาด้านบน)
- อ้างดาวโคจร(transit)ปัจจุบัน ที่กำลังส่งผล + ภพที่ดาวโคจรผ่าน
- บอกจุดแข็งจุดอ่อนของดวงชะตา + บุคลิกภาพ' : "- บอกว่าจันทราใช้ศาสตร์โหราศาสตร์โบราณหลักเจ้าชนะในการวิเคราะห์ดวงชะตาของ{$genderPrefix}{$name}").'

';
        }

        $prompt .= "⭐ **วิเคราะห์คำถาม** (เจาะลึกเฉพาะคำถามนี้ ด้วยศาสตร์โหราศาสตร์โบราณ):
- ตอบคำถาม \"{$question}\" อย่างละเอียด ลึกซึ้ง ด้วยหลักเจ้าชนะ
".($birthDate ? "- อ้างตำแหน่งดาวกำเนิดที่อยู่ในภพที่เกี่ยวข้องกับคำถาม (จากแผนที่ดวงชะตาด้านบน)
- อ้างดาวโคจร(transit)ปัจจุบัน + transit อนาคต (1, 3, 6, 12 เดือน) ที่ส่งผลต่อเรื่องนี้
- เปรียบเทียบ transit ปัจจุบัน vs อนาคต ว่าดาวจะเลื่อนไปภพไหน ส่งผลดีขึ้น/ต้องระวังอย่างไร
- ระบุว่าราศี+ปีนักษัตร+ธาตุของ{$genderPrefix}{$name}ส่งผลต่อเรื่องนี้อย่างไร
- เช่น ถามเรื่องรัก→อ้างดาวที่อยู่ในภพปัตนิ+ศุกร์ ถามเรื่องงาน→อ้างดาวที่อยู่ภพกัมมะ" : '- ใช้ศาสตร์โหราศาสตร์โบราณหลักเจ้าชนะในการทำนาย').'
- ฟันธง กล้าบอกตรงๆ ทั้งเรื่องดีและไม่ดี ด้วยหลักวิชา
- ระบุช่วงเวลาชัดเจน อ้าง transit อนาคต เช่น \"อีก 3 เดือน ดาว[ชื่อ]จะเลื่อนเข้าภพ[ชื่อ] ส่งผลให้...\"

💫 **สิ่งที่จะเกิดขึ้นในอนาคต** (อ้างจาก Transit อนาคตที่คำนวณ):
- ระยะสั้น (1-3 เดือน): อ้าง transit อีก 1 เดือน → ดาวอะไรอยู่ภพไหน ส่งผลอะไร
- ระยะกลาง (3-6 เดือน): อ้าง transit อีก 3-6 เดือน → ดาวเลื่อนไปไหน เรื่องอะไรจะเปลี่ยน
- ระยะยาว (6-12 เดือน): อ้าง transit อีก 12 เดือน → แนวโน้มดวงดาวระยะยาว

📅 **ฤกษ์ดี-ฤกษ์ร้าย + ควร/ไม่ควรทำ** (อ้างจากวิเคราะห์ฤกษ์ด้านบน):
- ช่วงฤกษ์ดี: ช่วงไหน ทำอะไรได้ เช่น ลงทุน สมัครงาน เริ่มธุรกิจ ตัดสินใจสำคัญ
- ช่วงฤกษ์ระวัง: ช่วงไหน ไม่ควรทำอะไร เช่น ชะลอการตัดสินใจ ระวังเรื่องเงิน ระวังคนรอบข้าง
- สิ่งที่ \"ห้ามทำ\" เด็ดขาดในช่วงนี้ + เหตุผลจากตำแหน่งดาวศัตรู
- สิ่งที่ \"ควรรีบทำ\" ในช่วงนี้ + เหตุผลจากตำแหน่งดาวมิตร

🎯 **คำแนะนำเฉพาะเรื่องนี้**:
- 🎨 สีมงคล: จากดาวมิตร + สีที่ต้องเลี่ยง(จากดาวศัตรู)
- 🔢 เลขมงคล: จากเลขดาวเจ้าชนะ+ดาวมิตร + เลขระวัง(จากดาวศัตรู)
- ⚠️ สิ่งที่ต้องระวัง (อ้างดาวศัตรู+ภพที่ถูกกระทบ) + วิธีแก้ไขชัดเจน
- 🙏 วิธีเสริมดวงเฉพาะตัว: ไหว้พระองค์ไหน ทำบุญอะไร ใช้สี/เลข/ทิศอะไร

';

        // คำถามสุดท้าย: ปิดด้วยกำลังใจ
        if ($questionNumber === $totalQuestions) {
            $prompt .= "🌟 **ปิดท้าย** (คำถามสุดท้าย):
- สรุปดวงชะตาภาพรวมของ{$genderPrefix}{$name} + ช่วงฤกษ์ดีที่สุด/ต้องระวังที่สุดในรอบปี
- ให้กำลังใจอบอุ่น จริงใจ
- \"ทุกคำทำนายจันทราวิเคราะห์จากศาสตร์โหราศาสตร์โบราณ หลักเจ้าชนะค่ะ ไม่ได้กุเรื่อง 🔮\"
- \"ถ้ามีเรื่องอะไรอยากถามเพิ่มเติม ทักมาหาจันทราได้เสมอนะคะ ✨\"
- เชิญชวนส่งต่อให้เพื่อนๆ มาดูดวงกับจันทรา

";
        }

        $prompt .= "[กฎสำคัญ]
- ทำนายเฉพาะคำถามที่ {$questionNumber} เท่านั้น ห้ามตอบคำถามอื่น
- ห้ามพูดซ้ำกับคำทำนายก่อนหน้า ใช้ดาว/ภพคนละดวง
- ต้องอ้างอิงตำแหน่งดาวจริงจากแผนที่ดวงชะตา + Transit ปัจจุบัน + Transit อนาคต ห้ามแต่งตำแหน่งดาวขึ้นเอง
- เมื่อทำนายอนาคต ต้องอ้าง Transit อนาคต (1,3,6,12 เดือน) เปรียบเทียบกับดวงกำเนิด
- ห้ามพูดว่าหยั่งรู้ จิตสัมผัส → ใช้คำว่า \"ศาสตร์โหราศาสตร์โบราณ\" หรือ \"หลักเจ้าชนะ\" แทน
- ตอบอย่างละเอียดสมราคา ไม่น้อยกว่า 300 คำ ไม่เกิน 450 คำ (⚠️ จำกัด 1500 ตัวอักษร เพราะส่งผ่าน Messenger ที่มี limit)
- ใช้ \"จันทรา\" แทนตัวเอง
- ตอบเป็นภาษาไทย อบอุ่น เป็นกันเอง น่าเชื่อถือ มีศาสตร์รองรับ ทำให้อยากดูดวงอีก";

        return $prompt;
    }

    /**
     * รวมคำทำนายทีละคำถามเป็นข้อความเดียว (สำหรับบันทึกลง DB)
     *
     * @param  array  $deepReadings  ข้อมูลคำทำนายแต่ละข้อ
     * @param  string  $name  ชื่อผู้ใช้
     * @param  string|null  $billRef  เลขที่บิล
     */
    protected function combineDeepReadings(array $deepReadings, string $name, ?string $billRef = null): string
    {
        $combined = '';

        foreach ($deepReadings as $reading) {
            $combined .= "═══════════════════════\n";
            $combined .= "❓ คำถามที่ {$reading['question_number']}: {$reading['question']}\n";
            $combined .= "═══════════════════════\n\n";
            $combined .= $reading['answer']."\n\n";
        }

        return $combined;
    }

    /**
     * คำนวณราศีและข้อมูลโหราศาสตร์จากวันเกิด
     *
     * @param  string  $birthDate  วันเกิด (Y-m-d)
     */
    protected function getZodiacDescription(string $birthDate): string
    {
        try {
            $date = \Carbon\Carbon::parse($birthDate);
            $month = $date->month;
            $day = $date->day;
            $year = $date->year;

            // ราศีตามโหราศาสตร์สากล (Western Zodiac)
            $zodiac = match (true) {
                ($month == 3 && $day >= 21) || ($month == 4 && $day <= 19) => ['ราศีเมษ (Aries)', 'ไฟ', 'ดาวอังคาร', 'กล้าหาญ ร้อนแรง เป็นผู้นำ มีพลัง'],
                ($month == 4 && $day >= 20) || ($month == 5 && $day <= 20) => ['ราศีพฤษภ (Taurus)', 'ดิน', 'ดาวศุกร์', 'มั่นคง อดทน รักความสวยงาม ภักดี'],
                ($month == 5 && $day >= 21) || ($month == 6 && $day <= 20) => ['ราศีเมถุน (Gemini)', 'ลม', 'ดาวพุธ', 'ฉลาด ช่างพูด ปรับตัวเก่ง ไหวพริบดี'],
                ($month == 6 && $day >= 21) || ($month == 7 && $day <= 22) => ['ราศีกรกฎ (Cancer)', 'น้ำ', 'ดวงจันทร์', 'อ่อนโยน รักครอบครัว อารมณ์ลึกซึ้ง เอาใจใส่'],
                ($month == 7 && $day >= 23) || ($month == 8 && $day <= 22) => ['ราศีสิงห์ (Leo)', 'ไฟ', 'ดวงอาทิตย์', 'มีเสน่ห์ ผู้นำ มั่นใจ ใจกว้าง'],
                ($month == 8 && $day >= 23) || ($month == 9 && $day <= 22) => ['ราศีกันย์ (Virgo)', 'ดิน', 'ดาวพุธ', 'ละเอียด พิถีพิถัน ชอบวิเคราะห์ มีระเบียบ'],
                ($month == 9 && $day >= 23) || ($month == 10 && $day <= 22) => ['ราศีตุลย์ (Libra)', 'ลม', 'ดาวศุกร์', 'รักความยุติธรรม มีเสน่ห์ ชอบความสมดุล ทูต'],
                ($month == 10 && $day >= 23) || ($month == 11 && $day <= 21) => ['ราศีพิจิก (Scorpio)', 'น้ำ', 'ดาวพลูโต', 'ลึกลับ เข้มแข็ง มีพลังแฝง เด็ดเดี่ยว'],
                ($month == 11 && $day >= 22) || ($month == 12 && $day <= 21) => ['ราศีธนู (Sagittarius)', 'ไฟ', 'ดาวพฤหัส', 'รักอิสระ มองโลกกว้าง โชคดี มองการณ์ไกล'],
                ($month == 12 && $day >= 22) || ($month == 1 && $day <= 19) => ['ราศีมังกร (Capricorn)', 'ดิน', 'ดาวเสาร์', 'ขยัน อดทน ทะเยอทะยาน รับผิดชอบสูง'],
                ($month == 1 && $day >= 20) || ($month == 2 && $day <= 18) => ['ราศีกุมภ์ (Aquarius)', 'ลม', 'ดาวยูเรนัส', 'คิดนอกกรอบ เป็นตัวเอง สร้างสรรค์ ก้าวหน้า'],
                default => ['ราศีมีน (Pisces)', 'น้ำ', 'ดาวเนปจูน', 'จิตใจอ่อนโยน สัญชาตญาณแม่น จินตนาการล้ำ'],
            };

            // วันเกิดตามโหราศาสตร์ไทย + เจ้าชนะ
            $thaiDayOfWeek = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
            $dayName = $thaiDayOfWeek[$date->dayOfWeek];

            // ดาวเจ้าชนะตามวันเกิด
            $chaochana = [
                0 => ['ดาวอาทิตย์', 'พฤหัสบดี+อังคาร', 'เสาร์+ราหู'],
                1 => ['ดาวจันทร์', 'พุธ+ศุกร์', 'ราหู+เสาร์'],
                2 => ['ดาวอังคาร', 'อาทิตย์+พฤหัสบดี', 'พุธ+เสาร์'],
                3 => ['ดาวพุธ', 'จันทร์+ศุกร์', 'ราหู+อังคาร'],
                4 => ['ดาวพฤหัสบดี', 'อาทิตย์+อังคาร', 'ราหู+เสาร์'],
                5 => ['ดาวศุกร์', 'พุธ+จันทร์', 'อาทิตย์+อังคาร'],
                6 => ['ดาวเสาร์', 'ราหู+พฤหัสบดี', 'อาทิตย์+อังคาร'],
            ];
            $cc = $chaochana[$date->dayOfWeek] ?? $chaochana[0];

            // ========== ปีนักษัตร (12 ปี) ==========
            $chineseZodiacAnimals = ['วอก (ลิง)', 'ระกา (ไก่)', 'จอ (สุนัข)', 'กุน (หมู)', 'ชวด (หนู)', 'ฉลู (วัว)', 'ขาล (เสือ)', 'เถาะ (กระต่าย)', 'มะโรง (งูใหญ่)', 'มะเส็ง (งูเล็ก)', 'มะเมีย (ม้า)', 'มะแม (แพะ)'];
            $zodiacAnimalIndex = $year % 12;
            $zodiacAnimal = $chineseZodiacAnimals[$zodiacAnimalIndex];

            // ธาตุจีน (วัฏจักร 10 ปี = 5 ธาตุ x 2)
            $chineseElements = ['ทอง (金)', 'ทอง (金)', 'น้ำ (水)', 'น้ำ (水)', 'ไม้ (木)', 'ไม้ (木)', 'ไฟ (火)', 'ไฟ (火)', 'ดิน (土)', 'ดิน (土)'];
            $elementIndex = $year % 10;
            $chineseElement = $chineseElements[$elementIndex];

            // ========== เลขมงคลจริง จากดาวเจ้าชนะ+มิตร ==========
            // เลขประจำดาว: อาทิตย์=1, จันทร์=2, อังคาร=3, พุธ=4, พฤหัสบดี=5, ศุกร์=6, เสาร์=7, ราหู=8, เกตุ=9
            $planetNumbers = [
                0 => [1, 5, 3],   // อาทิตย์=1 มิตร=พฤหัส(5)+อังคาร(3)
                1 => [2, 4, 6],   // จันทร์=2 มิตร=พุธ(4)+ศุกร์(6)
                2 => [3, 1, 5],   // อังคาร=3 มิตร=อาทิตย์(1)+พฤหัส(5)
                3 => [4, 2, 6],   // พุธ=4 มิตร=จันทร์(2)+ศุกร์(6)
                4 => [5, 1, 3],   // พฤหัส=5 มิตร=อาทิตย์(1)+อังคาร(3)
                5 => [6, 4, 2],   // ศุกร์=6 มิตร=พุธ(4)+จันทร์(2)
                6 => [7, 8, 5],   // เสาร์=7 มิตร=ราหู(8)+พฤหัส(5)
            ];
            $luckyNums = $planetNumbers[$date->dayOfWeek] ?? [1, 5, 9];
            // เลขมงคลรวม = ผลรวมเลขดาวเจ้าชนะ+มิตร
            $luckySum = array_sum($luckyNums) % 10 ?: 10;
            $luckyNumberStr = implode(', ', $luckyNums).", {$luckySum}";

            // ========== เลขที่ควรระวัง จากดาวศัตรู ==========
            $enemyNumbers = [
                0 => [7, 8],   // อาทิตย์: ศัตรู=เสาร์(7)+ราหู(8)
                1 => [8, 7],   // จันทร์: ศัตรู=ราหู(8)+เสาร์(7)
                2 => [4, 7],   // อังคาร: ศัตรู=พุธ(4)+เสาร์(7)
                3 => [8, 3],   // พุธ: ศัตรู=ราหู(8)+อังคาร(3)
                4 => [8, 7],   // พฤหัส: ศัตรู=ราหู(8)+เสาร์(7)
                5 => [1, 3],   // ศุกร์: ศัตรู=อาทิตย์(1)+อังคาร(3)
                6 => [1, 3],   // เสาร์: ศัตรู=อาทิตย์(1)+อังคาร(3)
            ];
            $unluckyNums = $enemyNumbers[$date->dayOfWeek] ?? [8, 7];
            $unluckyNumberStr = implode(', ', $unluckyNums);

            // ========== อายุปัจจุบัน ==========
            $age = $date->age;

            // ========== คำนวณปีพุทธศักราชเกิด ==========
            $buddhistYear = $year + 543;

            return "ราศี: {$zodiac[0]} | ธาตุราศี: {$zodiac[1]} | ดาวประจำราศี: {$zodiac[2]} | ลักษณะ: {$zodiac[3]} | เกิดวัน{$dayName} | ดาวเจ้าชนะ: {$cc[0]} | ดาวมิตร: {$cc[1]} | ดาวศัตรู: {$cc[2]} | ปีนักษัตร: ปี{$zodiacAnimal} | ธาตุจีน: {$chineseElement} | อายุ: {$age} ปี (พ.ศ. {$buddhistYear}) | เลขมงคล: {$luckyNumberStr} | เลขควรระวัง: {$unluckyNumberStr}";

        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * คำนวณตำแหน่ง Transit ดาวปัจจุบัน + เหตุการณ์ดาวสำคัญ
     *
     * ใช้ส่งให้ AI เพื่อให้ทำนาย timing แม่นยำ
     * อ้างอิงตำแหน่งดาวจริงๆ ในช่วงเวลาปัจจุบัน
     *
     * @param  int  $birthDayOfWeek  วันเกิด (0=อาทิตย์ ... 6=เสาร์)
     * @return string ข้อมูล transit สำหรับใส่ใน prompt
     */
    protected function getCurrentTransitDescription(int $birthDayOfWeek): string
    {
        try {
            $chartService = new FortuneChartService;

            // คำนวณ Transit อนาคตหลายช่วง (ปัจจุบัน, 1, 3, 6, 12 เดือน)
            $futureTransits = $chartService->calculateFutureTransits($birthDayOfWeek);

            $result = "\n[🌟 ตำแหน่งดาวโคจร (Transit) ปัจจุบัน + อนาคต — คำนวณจากหลักเจ้าชนะ]\n";
            $result .= "(ข้อมูลนี้คำนวณจากศาสตร์โหราศาสตร์โบราณ ใช้อ้างอิงในคำทำนาย)\n\n";

            foreach ($futureTransits as $transit) {
                $label = $transit['label'];
                $date = $transit['date'];

                $result .= "📆 [{$label}] ({$date}):\n";

                // ดาวมิตรที่ส่งผลดี
                if (! empty($transit['friend_impacts'])) {
                    foreach ($transit['friend_impacts'] as $impact) {
                        $result .= "  ✅ ดาวมิตร \"{$impact['planet_name']}\" โคจรภพ{$impact['house']}.{$impact['house_name']}({$impact['house_meaning']}) → ส่งผลดีด้าน{$impact['house_meaning']}\n";
                    }
                }

                // ดาวศัตรูที่ต้องระวัง
                if (! empty($transit['enemy_impacts'])) {
                    foreach ($transit['enemy_impacts'] as $impact) {
                        $result .= "  ⚠️ ดาวศัตรู \"{$impact['planet_name']}\" โคจรภพ{$impact['house']}.{$impact['house_name']}({$impact['house_meaning']}) → ระวังด้าน{$impact['house_meaning']}\n";
                    }
                }

                $result .= "\n";
            }

            // ========== เหตุการณ์ดาวสำคัญตามเดือน ==========
            $now = \Carbon\Carbon::now('Asia/Bangkok');
            $transitEvents = $this->getTransitEvents($now->month);
            if ($transitEvents) {
                $result .= "[📅 เหตุการณ์ดาวสำคัญช่วงนี้]\n";
                $result .= $transitEvents;
            }

            // วิเคราะห์ฤกษ์ดี/ไม่ดีจาก Transit
            $result .= $this->analyzeTransitLuckPeriods($futureTransits);

            $result .= "\n⚠️ กฎ: ต้องอ้างอิงตำแหน่งดาว Transit ข้างต้นในคำทำนาย เช่น:\n";
            $result .= "- \"ช่วงนี้ดาว[ชื่อ]กำลังโคจรผ่านภพ[ชื่อ] ส่งผลให้...\"\n";
            $result .= "- \"อีก 3 เดือนข้างหน้า ดาว[ชื่อ]จะเลื่อนเข้าภพ[ชื่อ] จึงเป็นช่วงที่...\"\n";
            $result .= "- ห้ามแต่งตำแหน่งดาวขึ้นเอง ใช้เฉพาะข้อมูลที่ให้เท่านั้น\n";

            return $result;

        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * วิเคราะห์ช่วงเวลาฤกษ์ดี/ไม่ดี จาก Transit อนาคต
     *
     * เปรียบเทียบดาวมิตร vs ศัตรู ในแต่ละช่วง
     * ช่วงไหนดาวมิตรเด่น = ฤกษ์ดี ควรลงมือทำ
     * ช่วงไหนดาวศัตรูเด่น = ต้องระวัง ควรชะลอ
     *
     * @param  array  $futureTransits  ข้อมูล transit แต่ละช่วง
     * @return string ข้อมูลวิเคราะห์ฤกษ์
     */
    protected function analyzeTransitLuckPeriods(array $futureTransits): string
    {
        $result = "\n[🔮 วิเคราะห์ฤกษ์ดี-ฤกษ์ระวัง จากตำแหน่งดาวอนาคต]\n";

        $bestPeriod = null;
        $worstPeriod = null;
        $bestScore = -999;
        $worstScore = 999;

        foreach ($futureTransits as $transit) {
            if ($transit['months'] === 0) {
                continue; // ข้ามปัจจุบัน ดูเฉพาะอนาคต
            }

            // คะแนน = จำนวนดาวมิตรในภพดี - จำนวนดาวศัตรูในภพสำคัญ
            $goodHouses = [1, 2, 5, 9, 10, 11]; // ภพดี
            $badHouses = [6, 8, 12]; // ภพท้าทาย

            $friendScore = 0;
            $enemyScore = 0;

            foreach ($transit['friend_impacts'] as $impact) {
                if (in_array($impact['house'], $goodHouses)) {
                    $friendScore += 2; // ดาวมิตรในภพดี = +2
                } else {
                    $friendScore += 1; // ดาวมิตรในภพอื่น = +1
                }
            }

            foreach ($transit['enemy_impacts'] as $impact) {
                if (in_array($impact['house'], $goodHouses)) {
                    $enemyScore += 2; // ดาวศัตรูในภพดี = ลบ 2 (กดดันเรื่องดี)
                } elseif (in_array($impact['house'], $badHouses)) {
                    $enemyScore += 1; // ดาวศัตรูในภพร้าย = ลบ 1
                }
            }

            $score = $friendScore - $enemyScore;

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestPeriod = $transit;
            }
            if ($score < $worstScore) {
                $worstScore = $score;
                $worstPeriod = $transit;
            }
        }

        if ($bestPeriod) {
            $result .= "✅ ช่วงฤกษ์ดีที่สุด: {$bestPeriod['label']} ({$bestPeriod['date']}) → ดาวมิตรเด่น เหมาะเริ่มต้นสิ่งใหม่ ตัดสินใจสำคัญ\n";
        }
        if ($worstPeriod) {
            $result .= "⚠️ ช่วงที่ต้องระวังที่สุด: {$worstPeriod['label']} ({$worstPeriod['date']}) → ดาวศัตรูกดดัน ควรชะลอการตัดสินใจ ระวังรอบด้าน\n";
        }

        return $result;
    }

    /**
     * เหตุการณ์ดาวสำคัญตามเดือน (ช่วงเวลาดาวโคจรที่มีผลกระทบ)
     *
     * ข้อมูลนี้ช่วยให้ AI ระบุ timing ได้ชัดเจน ไม่ต้องแต่งเอง
     *
     * @param  int  $month  เดือนปัจจุบัน (1-12)
     * @return string เหตุการณ์ดาว
     */
    protected function getTransitEvents(int $month): string
    {
        // เหตุการณ์ดาวประจำเดือน (ปรับปรุงได้ตามปี)
        $events = [
            1 => "- ดาวเสาร์โคจรช้า ช่วงต้นปีเหมาะวางแผนระยะยาว\n- ดาวพฤหัสบดีเสริมโชคลาภช่วงกลางเดือน\n- ดาวอังคารให้พลังขับเคลื่อนเรื่องงาน\n",
            2 => "- ดาวศุกร์เสริมเรื่องความรักและความสัมพันธ์\n- ช่วงกลางเดือนดาวพุธย้ายราศีอาจมีการเปลี่ยนแปลงเรื่องการสื่อสาร\n- ดาวพฤหัสบดีเสริมด้านการเงินและโชคลาภ\n",
            3 => "- ดาวอังคารให้พลังแรงกล้า เหมาะเริ่มต้นสิ่งใหม่\n- ดาวพฤหัสบดีส่งเสริมการเรียนรู้และเดินทาง\n- ช่วงปลายเดือนดาวเสาร์กดดันเรื่องการงาน\n",
            4 => "- ดาวอาทิตย์ให้พลังอำนาจและความมั่นใจ\n- ดาวศุกร์ย้ายราศี เปิดโอกาสใหม่ด้านความรัก\n- ดาวราหูส่งผลให้เกิดเหตุไม่คาดฝัน ต้องรอบคอบ\n",
            5 => "- ดาวพุธเสริมเรื่องการค้าขายและเจรจา\n- ดาวจันทร์เต็มดวงกลางเดือน เสริมพลังสัญชาตญาณ\n- ดาวเสาร์โคจรถอยหลัง ระวังเรื่องสุขภาพ\n",
            6 => "- ดาวอาทิตย์ครึ่งปี เป็นช่วงทบทวนและปรับแผน\n- ดาวพฤหัสบดีเสริมเรื่องการเงินและลาภลอย\n- ดาวศุกร์เสริมเสน่ห์และมิตรภาพ\n",
            7 => "- ดาวอังคารให้พลังต่อสู้แข่งขัน เหมาะเลื่อนตำแหน่ง\n- ดาวเกตุส่งผลด้านจิตวิญญาณ การทำบุญได้ผลดี\n- ช่วงกลางเดือนระวังดาวศัตรูกดดันเรื่องเงิน\n",
            8 => "- ดาวพฤหัสบดีเสริมโชคลาภสูงสุดในรอบปี\n- ดาวอาทิตย์ให้ความมั่นใจและอำนาจ\n- ดาวพุธเสริมเรื่องการเจรจาต่อรอง\n",
            9 => "- ดาวศุกร์เสริมความรักและศิลปะ\n- ดาวเสาร์ให้บทเรียนเรื่องความอดทน\n- ช่วงปลายเดือนดาวราหูเปลี่ยนทิศ ระวังเรื่องไม่คาดคิด\n",
            10 => "- ดาวพลูโตส่งผลให้เกิดการเปลี่ยนแปลงครั้งใหญ่\n- ดาวพฤหัสบดีเสริมเรื่องการงานและตำแหน่ง\n- ดาวจันทร์เสริมเรื่องครอบครัวและความสุข\n",
            11 => "- ดาวพุธย้ายราศี เปิดโอกาสด้านการสื่อสารและธุรกิจ\n- ดาวศุกร์เสริมเรื่องทรัพย์สินและความมั่งคั่ง\n- ช่วงปลายเดือนดาวอังคารเสริมพลังต่อสู้\n",
            12 => "- ดาวพฤหัสบดีเสริมโชคลาภปิดท้ายปี\n- ดาวเสาร์สอนบทเรียนสำคัญเรื่องความอดทน\n- ช่วงส่งท้ายปีเหมาะสำหรับวางแผนปีหน้า ตั้งเป้าหมายใหม่\n",
        ];

        return $events[$month] ?? '';
    }
}
