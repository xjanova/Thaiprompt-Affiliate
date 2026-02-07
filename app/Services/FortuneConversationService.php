<?php

namespace App\Services;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneUserCredit;
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
        try {
            // Pre-filter พร้อม Rate Limiting: ตรวจจับ spam รุนแรงเท่านั้น
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

            // ✅ ตรวจสอบคำสั่งพิเศษ: เช็คสิทธิ์ดูดวง
            if ($this->isCheckRemainingRequest($messageText)) {
                return $this->handleCheckRemaining($facebookUserId);
            }

            // ตรวจสอบว่ามี conversation ที่กำลังดำเนินอยู่หรือไม่
            $activeReading = FortuneReading::findActiveConversation($facebookUserId);

            if ($activeReading) {
                // ถ้าอยู่ในสถานะ basic_done: เช็คว่ารับ deep reading หรือไม่
                // ถ้าไม่ใช่ → ปิด conversation เก่าแล้วส่งข้อความใหม่ไปให้ AI ตอบ
                // ไม่บอก "ไม่เป็นไร" เพราะผู้ใช้อาจจะถามคำถามใหม่
                if ($activeReading->conversation_status === FortuneReading::STATUS_BASIC_DONE) {
                    if ($this->isDeepReadingAccepted($messageText)) {
                        return $this->continueConversation($activeReading, $messageText, $userProfile);
                    }
                    // ปิด conversation เก่าแล้วปล่อยให้ตกไปเริ่ม conversation ใหม่
                    $activeReading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);
                } else {
                    return $this->continueConversation($activeReading, $messageText, $userProfile);
                }
            }

            // ✅ ตรวจสอบ AI calls limit ก่อนส่งให้ AI
            if (!$this->canMakeAICall($facebookUserId)) {
                return [
                    'action' => 'ai_limit',
                    'message' => $this->getAILimitMessage(),
                    'reading' => null,
                ];
            }

            // ✅ ส่งทุกข้อความไปให้ AI ตอบ ไม่ว่าจะเป็นเรื่องดูดวงหรือไม่
            // AI system prompt จะจัดการเองว่าจะตอบแบบไหน
            return $this->startNewConversation($facebookUserId, $messageText, $userProfile);

        } catch (\Exception $e) {
            // ✅ จับ exception ทุกชนิดที่หลุดมา ไม่ให้ error bubble ไปถึง controller
            Log::error('Fortune processMessage: เกิดข้อผิดพลาด', [
                'facebook_user_id' => $facebookUserId,
                'error' => $e->getMessage(),
                'text_preview' => mb_substr($messageText, 0, 50),
            ]);

            // ✅ ป้องกัน null userProfile - ใช้ is_array ก่อนเข้าถึง array key
            $name = (is_array($userProfile) && isset($userProfile['name'])) ? $userProfile['name'] : 'คุณ';

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
     * @param string $name ชื่อผู้ใช้
     * @return string
     */
    protected function getFallbackMessage(string $name): string
    {
        return "🔮 สวัสดีค่ะ คุณ{$name} ✨\n\n" .
               "จันทรายินดีต้อนรับนะคะ จันทราเป็นหมอดูสาวที่ใช้พลังหยั่งรู้ในการทำนายค่ะ\n\n" .
               "บอกจันทราได้เลยนะคะว่าอยากรู้เรื่องอะไร:\n" .
               "💕 ความรัก - เนื้อคู่ คู่ครอง\n" .
               "💼 การงาน - เปลี่ยนงาน เลื่อนตำแหน่ง\n" .
               "💰 การเงิน - รายได้ การลงทุน\n" .
               "🏥 สุขภาพ - ระวังอะไรบ้าง\n\n" .
               "พิมพ์มาได้เลยค่ะ 🔮";
    }

    /**
     * สร้าง fallback response เมื่อ AI ติดต่อไม่ได้
     * ตอบแบบเป็นธรรมชาติเหมือนหมอดูจริง ไม่บอกว่าระบบมีปัญหา
     *
     * @param string $messageText ข้อความจากผู้ใช้
     * @param array|null $userProfile โปรไฟล์ผู้ใช้
     * @return string
     */
    protected function getFallbackFortuneResponse(string $messageText, ?array $userProfile = null): string
    {
        $name = $userProfile['name'] ?? 'คุณ';
        $text = mb_strtolower(trim($messageText));

        // ทักทาย
        $greetings = ['สวัสดี', 'หวัดดี', 'ดีจ้า', 'ดีค่ะ', 'ดีครับ', 'hi', 'hello', 'hey'];
        foreach ($greetings as $greeting) {
            if (str_contains($text, $greeting)) {
                return "🔮 สวัสดีค่ะ คุณ{$name} ✨\n\n" .
                       "จันทรายินดีต้อนรับนะคะ จันทราเป็นหมอดูสาวที่ใช้พลังหยั่งรู้ในการทำนาย พร้อมช่วยดูดวงให้ค่ะ\n\n" .
                       "ไม่ว่าจะเรื่องความรัก 💕 การงาน 💼 การเงิน 💰 หรือสุขภาพ 🏥\n" .
                       "ถามมาได้เลยนะคะ แล้วอย่าลืมบอกวันเดือนปีเกิดให้จันทราด้วยนะคะ จะได้ทำนายได้แม่นยำยิ่งขึ้น 🎂\n\n" .
                       "ฝากส่งต่อให้เพื่อนๆ มาลองดูดวงด้วยกันนะคะ 🔮✨";
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
        return "🔮 สวัสดีค่ะ คุณ{$name}\n\n" .
               "ขอบคุณที่ทักมานะคะ จันทราพร้อมดูดวงให้ค่ะ ✨\n\n" .
               "ลองบอกจันทราว่าอยากรู้เรื่องอะไร:\n" .
               "💕 ความรัก - เนื้อคู่ คู่ครอง\n" .
               "💼 การงาน - เปลี่ยนงาน เลื่อนตำแหน่ง\n" .
               "💰 การเงิน - รายได้ การลงทุน\n" .
               "🏥 สุขภาพ - ระวังอะไรบ้าง\n\n" .
               "บอกวันเดือนปีเกิดมาด้วยนะคะ จะได้ทำนายได้แม่นยำยิ่งขึ้น 🎂✨";
    }

    /**
     * สุ่มข้อความ fallback ตามหมวดหมู่
     *
     * @param string $category หมวดหมู่ (love, work, money, health, general)
     * @param string $name ชื่อผู้ใช้
     * @return string
     */
    protected function getRandomFallback(string $category, string $name): string
    {
        $responses = [
            'love' => [
                "🔮 คุณ{$name} คะ จันทราเห็นว่าช่วงนี้ดวงความรักกำลังมีการเปลี่ยนแปลงค่ะ\n\n" .
                "💕 สำหรับคนมีคู่: ช่วงนี้ควรให้เวลากับคนรักมากขึ้น มีเรื่องดีๆ รออยู่ข้างหน้าค่ะ\n" .
                "💕 สำหรับคนโสด: ดวงเปิดรับคนใหม่ ลองเปิดใจดูนะคะ\n\n" .
                "📅 ช่วงเวลาที่ดี: 2-3 เดือนข้างหน้า\n" .
                "🎨 สีมงคล: ชมพู, แดง\n\n" .
                "ถ้าบอกวันเดือนปีเกิดให้จันทรา จะได้ทำนายได้แม่นยำยิ่งขึ้นนะคะ 🎂",

                "🔮 คุณ{$name} คะ จันทราขอบอกตรงๆ เลยนะคะ\n\n" .
                "💕 ดวงความรักของคุณช่วงนี้ มีทั้งสิ่งดีและสิ่งที่ต้องระวังค่ะ\n" .
                "✅ เรื่องดี: จะมีคนเข้ามาให้ความสนใจ หรือคนรักจะแสดงความรักมากขึ้น\n" .
                "⚠️ ระวัง: อย่าใจร้อน อย่าตัดสินใจเรื่องใหญ่เรื่องความรักเร็วเกินไป\n\n" .
                "🔢 เลขมงคล: 9, 19\n\n" .
                "อยากรู้ละเอียดกว่านี้ บอกวันเกิดมาได้เลยนะคะ 🎂✨",
            ],
            'work' => [
                "🔮 คุณ{$name} คะ จันทราเห็นดวงการงานช่วงนี้ค่ะ\n\n" .
                "💼 ดวงการงานกำลังอยู่ในช่วงที่ต้องอดทนและพัฒนาตัวเอง\n" .
                "✅ โอกาสใหม่ๆ จะเริ่มเข้ามาในช่วง 1-3 เดือนข้างหน้า\n" .
                "✅ คนที่คิดจะเปลี่ยนงาน ช่วงนี้เป็นจังหวะที่ดีค่ะ\n" .
                "⚠️ ระวังเรื่องเพื่อนร่วมงาน อย่าไว้ใจคนง่ายเกินไป\n\n" .
                "📅 วันมงคล: วันพฤหัสบดี\n" .
                "🎨 สีมงคล: เหลือง, ส้ม\n\n" .
                "บอกวันเกิดมาด้วยนะคะ จะได้วิเคราะห์ดวงได้ลึกขึ้น 🎂",

                "🔮 คุณ{$name} คะ จันทราขอทำนายดวงการงานให้นะคะ\n\n" .
                "💼 ช่วงนี้เป็นจังหวะที่ดีสำหรับการเริ่มต้นสิ่งใหม่ค่ะ\n" .
                "✅ มีเกณฑ์ได้รับข่าวดีเรื่องงาน\n" .
                "✅ คนทำธุรกิจจะเริ่มเห็นผลลัพธ์\n" .
                "⚠️ แต่อย่าประมาท ทำทุกอย่างให้รอบคอบ\n\n" .
                "🔢 เลขมงคล: 5, 14\n\n" .
                "ถ้าอยากรู้ละเอียดกว่านี้ บอกวันเดือนปีเกิดมานะคะ 🎂✨",
            ],
            'money' => [
                "🔮 คุณ{$name} คะ จันทราเห็นดวงการเงินค่ะ\n\n" .
                "💰 ดวงการเงินช่วงนี้: ต้องระมัดระวังเรื่องรายจ่ายค่ะ\n" .
                "✅ มีเกณฑ์ได้เงินก้อน หรือรายได้เพิ่มในช่วง 2-4 เดือนข้างหน้า\n" .
                "✅ เหมาะกับการออมเงินและวางแผนการเงิน\n" .
                "⚠️ ระวังการลงทุนที่เสี่ยงสูง ช่วงนี้ยังไม่ใช่จังหวะ\n\n" .
                "🎨 สีมงคลการเงิน: เขียว, ทอง\n" .
                "📅 วันมงคล: วันพุธ\n\n" .
                "บอกวันเกิดมาด้วยนะคะ จะได้ทำนายเรื่องการเงินได้แม่นขึ้น 🎂",

                "🔮 คุณ{$name} คะ จันทราขอบอกเรื่องการเงินนะคะ\n\n" .
                "💰 ดวงการเงินของคุณกำลังจะดีขึ้นค่ะ\n" .
                "✅ มีโอกาสได้รับเงินจากทางที่ไม่คาดคิด\n" .
                "✅ คนที่ค้าขายจะเริ่มมีลูกค้าเพิ่มขึ้น\n" .
                "⚠️ แต่ระวังเรื่องการใช้จ่ายฟุ่มเฟือย\n\n" .
                "🔢 เลขมงคลการเงิน: 3, 8, 24\n\n" .
                "อยากรู้ละเอียดกว่านี้ บอกวันเดือนปีเกิดมานะคะ 🎂✨",
            ],
            'health' => [
                "🔮 คุณ{$name} คะ จันทราเห็นดวงสุขภาพค่ะ\n\n" .
                "🏥 ช่วงนี้ต้องดูแลสุขภาพให้ดีค่ะ\n" .
                "✅ ออกกำลังกายเบาๆ สม่ำเสมอ จะช่วยได้มาก\n" .
                "✅ พักผ่อนให้เพียงพอ อย่าหักโหมมากเกินไป\n" .
                "⚠️ ระวังเรื่องการเดินทาง และอาหารการกิน\n\n" .
                "📅 ช่วงที่ต้องระวังเป็นพิเศษ: 2-3 สัปดาห์ข้างหน้า\n" .
                "🎨 สีมงคล: เขียว, ขาว\n\n" .
                "บอกวันเกิดมาด้วยนะคะ จะได้วิเคราะห์ดวงสุขภาพได้ละเอียดขึ้น 🎂",
            ],
            'general' => [
                "🔮 คุณ{$name} คะ จันทรายินดีดูดวงให้ค่ะ ✨\n\n" .
                "⭐ ดวงโดยรวมช่วงนี้: กำลังอยู่ในช่วงเปลี่ยนผ่าน มีทั้งเรื่องดีและสิ่งที่ต้องระวังค่ะ\n\n" .
                "✅ เรื่องดี: จะมีโอกาสใหม่ๆ เข้ามา ทั้งเรื่องงานและเรื่องส่วนตัว\n" .
                "✅ การเงินมีเกณฑ์ดีขึ้น\n" .
                "⚠️ ระวัง: เรื่องสุขภาพ อย่าประมาท ดูแลตัวเองให้ดี\n\n" .
                "🎨 สีมงคล: น้ำเงิน, ทอง\n" .
                "🔢 เลขมงคล: 7, 16\n" .
                "📅 วันมงคล: วันพฤหัสบดี\n\n" .
                "บอกวันเดือนปีเกิดให้จันทรานะคะ จะได้ทำนายได้แม่นยำยิ่งขึ้น 🎂\n" .
                "ถ้าคำทำนายถูกใจ ฝากส่งต่อให้เพื่อนๆ มาลองดูดวงด้วยกันนะคะ 🔮✨",

                "🔮 คุณ{$name} คะ จันทราขอทำนายดวงให้นะคะ\n\n" .
                "⭐ ภาพรวมดวงชะตา: กำลังเข้าสู่ช่วงที่ดีค่ะ\n\n" .
                "💕 ความรัก: มีเกณฑ์ได้พบคนถูกใจ หรือความสัมพันธ์จะแน่นแฟ้นขึ้น\n" .
                "💼 การงาน: มีความก้าวหน้า อาจได้รับข้อเสนอใหม่\n" .
                "💰 การเงิน: ระมัดระวังเรื่องรายจ่าย แต่มีเกณฑ์ได้เงินเข้ามา\n" .
                "🏥 สุขภาพ: ดูแลตัวเองให้ดี พักผ่อนให้เพียงพอ\n\n" .
                "🎨 สีมงคล: ม่วง, ครีม\n" .
                "🔢 เลขมงคล: 2, 11, 29\n\n" .
                "อยากรู้ละเอียดกว่านี้ บอกวันเดือนปีเกิดมานะคะ 🎂✨",
            ],
        ];

        $categoryResponses = $responses[$category] ?? $responses['general'];
        $index = array_rand($categoryResponses);

        return $categoryResponses[$index];
    }

    /**
     * ตรวจสอบว่าเป็นคำสั่งเช็คสิทธิ์ดูดวงหรือไม่
     *
     * @param string $text
     * @return bool
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
     *
     * @param string $facebookUserId
     * @return array
     */
    protected function handleCheckRemaining(string $facebookUserId): array
    {
        $remaining = $this->getRemainingFreeQuestions($facebookUserId);
        $maxFreeReadings = $this->settings->max_free_readings ?? self::MAX_AI_CALLS_PER_DAY;
        $usedToday = FortuneReading::countTodayReadings($facebookUserId);
        $price = $this->settings->deep_reading_price ?: self::DEEP_READING_PRICE;

        // ดึงข้อมูลเครดิตพิเศษ
        $userCredit = FortuneUserCredit::findByUser($facebookUserId);

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
            $message .= "จันทราพร้อมทำนายให้ค่ะ 🔮✨";
        } else {
            $message .= "⏰ สิทธิ์ฟรีวันนี้หมดแล้วค่ะ\n";
            $message .= "กลับมาใหม่พรุ่งนี้ หรือ\n\n";
            $message .= "💎 *ดูดวงละเอียด {$price} บาท*\n";
            $message .= "📌 ถามได้ 3 คำถาม วิเคราะห์จากวันเกิด\n";
            $message .= "📌 พร้อมสีมงคล เลขมงคล ฤกษ์ดี\n\n";
            $message .= "พิมพ์ 'ต้องการดูละเอียด' เพื่อเริ่มค่ะ ✨";
        }

        return [
            'action' => 'check_remaining',
            'message' => $message,
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

        // ✅ ป้องกัน null userProfile - สร้าง default profile
        if (!is_array($userProfile)) {
            $userProfile = [
                'name' => 'คุณ',
                'id' => $facebookUserId,
            ];
            Log::info('Fortune: ไม่สามารถดึงโปรไฟล์ได้ ใช้ค่าเริ่มต้น', [
                'facebook_user_id' => $facebookUserId,
            ]);
        }

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

            // ทำนายพื้นฐานฟรี - ใช้ retry + auto-switch provider
            $basicPrompt = $this->buildBasicPrompt($userProfile, $messageText);

            Log::info('Fortune: กำลังเรียก AI', [
                'facebook_user_id' => $facebookUserId,
                'provider' => $this->settings->getActualAIProvider(),
                'has_api_key' => !empty($this->settings->getActualAIApiKey()),
                'prompt_length' => mb_strlen($basicPrompt),
            ]);

            $aiResult = $this->aiService->generateWithRetryAndFallback(
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

            Log::info('Fortune: AI ตอบสำเร็จ', [
                'facebook_user_id' => $facebookUserId,
                'provider' => $aiResult['provider'],
                'model' => $aiResult['model'],
                'response_length' => mb_strlen($aiResult['response']),
            ]);

            // สร้างข้อความเชิญชวนดูดวงละเอียด
            $upsellMessage = $this->getUpsellMessage($name);

            // เพิ่มเลขที่บิลอ้างอิงท้ายคำทำนาย
            $billRefMessage = $this->getBillReferenceMessage($reading->bill_reference);

            // แสดงจำนวนสิทธิ์ฟรีที่เหลือ (รวมเครดิตพิเศษจากแอดมิน/โปรโมชั่น)
            $remainingMessage = $this->getRemainingCreditsMessage($facebookUserId);

            return [
                'action' => 'basic_done',
                'message' => $aiResult['response'] . "\n\n" . $remainingMessage . "\n\n" . $billRefMessage . "\n\n" . $upsellMessage,
                'reading' => $reading,
                'show_quick_replies' => true,
            ];

        } catch (\Exception $e) {
            Log::error('Fortune Conversation: ทำนายพื้นฐานล้มเหลว', [
                'facebook_user_id' => $facebookUserId,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace_short' => mb_substr($e->getTraceAsString(), 0, 500),
                'ai_provider' => $this->settings->getActualAIProvider(),
                'ai_model' => $this->settings->getActualAIModel(),
                'has_api_key' => !empty($this->settings->getActualAIApiKey()),
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
                'message' => "🔮 คุณ{$name} คะ ขออภัยนะคะ ระบบกำลังปรับปรุงชั่วคราวค่ะ 🙏\n\n" .
                             "กรุณาลองพิมพ์มาใหม่อีกครั้งในอีก 1-2 นาทีนะคะ\n" .
                             "จันทราพร้อมดูดวงให้ค่ะ ✨",
                'reading' => null,
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
        // ตรวจสอบยอดเงินว่าหมดอายุหรือยัง
        $uniqueAmount = $reading->uniquePaymentAmount;

        if (!$uniqueAmount || $uniqueAmount->expires_at < now()) {
            // บิลหมดอายุ → ปิด conversation กลับไปแชทปกติ
            $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

            Log::info('Fortune: บิลดูดวงละเอียดหมดอายุ กลับเป็นแชทปกติ', [
                'reading_id' => $reading->id,
                'facebook_user_id' => $reading->facebook_user_id,
            ]);

            return [
                'action' => 'payment_expired',
                'message' => "⏰ บิลดูดวงละเอียดหมดอายุแล้วค่ะ\n\n" .
                             "ถ้าต้องการดูดวงละเอียดอีกครั้ง พิมพ์ 'ดูดวงละเอียด' ได้เลยนะคะ\n" .
                             "หรือพิมพ์คำถามใหม่มาได้เลยค่ะ จันทราพร้อมดูดวงให้ค่ะ 🔮✨",
                'reading' => $reading,
            ];
        }

        // บิลยังไม่หมดอายุ → ไม่ว่าจะพิมพ์อะไรมา แสดงยอด+บัญชีธนาคารเสมอ
        $payAmount = number_format($uniqueAmount->unique_amount, 2);
        $expiresAt = $uniqueAmount->expires_at->format('H:i');
        $billRef = $reading->bill_reference;

        $message = "🔮 จันทรารอคำทำนายละเอียดให้อยู่ค่ะ\n\n";
        $message .= "กรุณาโอนเงินเพื่อรับคำทำนายนะคะ 🙏\n\n";
        $message .= "═══════════════════════\n";
        $message .= "💰 *ยอดชำระ: ฿{$payAmount}*\n";
        $message .= "🔖 เลขที่บิล: {$billRef}\n";
        $message .= "⏰ โอนก่อน: {$expiresAt} น.\n";
        $message .= "═══════════════════════\n\n";

        // แสดงบัญชีธนาคารทุกครั้ง
        $message .= $this->getBankAccountsListMessage();

        $message .= "⚠️ *สำคัญ*: กรุณาโอนยอด ฿{$payAmount} (ตรงตามทศนิยม)\n";
        $message .= "เพื่อให้ระบบตรวจสอบอัตโนมัติได้ถูกต้อง\n\n";
        $message .= "เมื่อโอนแล้วรอสักครู่ ระบบจะส่งคำทำนายให้ทันทีค่ะ ✨\n\n";
        $message .= "พิมพ์ 'ยกเลิก' หากต้องการยกเลิก";

        return [
            'action' => 'waiting_payment',
            'message' => $message,
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
                    'message' => "🔮 ตอนนี้ระบบกำลังเตรียมบิลให้ค่ะ\n\nรบกวนพิมพ์ 'ดูดวงละเอียด' อีกครั้งในอีกสักครู่นะคะ ✨",
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

            // ดึงข้อมูลสำหรับทำนาย
            $questions = $reading->questions ?? $reading->getCollectedQuestions();
            $userProfile = $reading->user_profile;
            $birthDate = $reading->birth_date?->format('Y-m-d');
            $name = $reading->facebook_user_name ?? 'คุณ';
            $gender = isset($userProfile['gender']) ? ($userProfile['gender'] === 'male' ? 'ชาย' : 'หญิง') : '';

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
            }

            // บันทึก AI call สำหรับ rate limiting
            if ($reading->facebook_user_id) {
                $this->recordAICall($reading->facebook_user_id);
            }

            // รวม response ทั้งหมดสำหรับบันทึกลง DB
            $fullResponse = $this->combineDeepReadings($deepReadings, $name, $reading->bill_reference);

            // บันทึกคำทำนายละเอียด
            $reading->saveDeepReading(
                $fullResponse,
                $lastProvider,
                $lastModel,
                $totalTokens
            );

            // สร้างข้อความขอบคุณ
            $thankYouMessage = $this->getThankYouMessage($name, $reading->bill_reference);

            Log::info('Fortune Conversation: ทำนายละเอียดสำเร็จ (ทีละคำถาม)', [
                'reading_id' => $reading->id,
                'questions_count' => count($questions),
                'tokens_used' => $totalTokens,
            ]);

            return [
                'action' => 'completed',
                'message' => $fullResponse . "\n\n" . $thankYouMessage,
                'deep_readings' => $deepReadings,
                'thank_you' => $thankYouMessage,
                'reading' => $reading,
            ];

        } catch (\Exception $e) {
            Log::error('Fortune Conversation: ทำนายละเอียดล้มเหลว', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'action' => 'error',
                'message' => "🔮 คำทำนายเชิงลึกของคุณกำลังดำเนินการค่ะ\n\nระบบจะส่งผลให้เร็วที่สุดนะคะ 🙏\nหากรอนานเกิน 5 นาที สามารถทักแชทเพื่อสอบถามแอดมินได้เลยค่ะ ✨",
                'reading' => $reading,
            ];
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
            return "📊 สิทธิ์ดูดวงฟรี: ✨ ไม่จำกัด ✨ (โปรโมชั่นพิเศษ!)";
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
        $price = $this->settings->deep_reading_price ?: self::DEEP_READING_PRICE;

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
     * ✅ ปรับปรุงใหม่: ให้ผ่านเกือบทุกข้อความไปหา AI
     * บล็อกเฉพาะ spam รุนแรงและข้อความยาวเกินเท่านั้น
     * ให้ AI system prompt จัดการ off-topic เอง
     *
     * @param string $text ข้อความที่ต้องการตรวจสอบ
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
                'message' => "🔮 พิมพ์ข้อความมาได้เลยนะคะ จันทราพร้อมช่วยดูดวงให้ค่ะ ✨",
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
        // 1. เช็คเครดิตพิเศษรายคน (แอดมินให้เพิ่ม / ดูฟรีไม่จำกัด / รีเซ็ตวันนี้)
        $userCredit = FortuneUserCredit::findByUser($userId);
        if ($userCredit && $userCredit->hasExtraFreeAccess()) {
            return true;
        }

        // 2. เช็คจากสิทธิ์ฟรีประจำวันปกติ
        $maxFreeReadings = $this->settings->max_free_readings ?? self::MAX_AI_CALLS_PER_DAY;

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
     * บันทึกการเรียก AI
     *
     * @param string $userId
     */
    public function recordAICall(string $userId): void
    {
        $dayKey = "fortune_ai_calls:{$userId}:day";
        $count = (int) Cache::get($dayKey, 0);
        Cache::put($dayKey, $count + 1, now()->endOfDay());

        // หักเครดิตพิเศษ (ถ้าสิทธิ์ฟรีปกติหมดแล้ว ใช้เครดิตแทน)
        $maxFreeReadings = $this->settings->max_free_readings ?? self::MAX_AI_CALLS_PER_DAY;
        $usedToday = FortuneReading::countTodayReadings($userId);
        if ($usedToday >= $maxFreeReadings) {
            $userCredit = FortuneUserCredit::findByUser($userId);
            if ($userCredit) {
                $userCredit->useCredit();
            }
        }
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
               "จันทราขอตอบเฉพาะเรื่องดูดวงเท่านั้นค่ะ\n\n" .
               "💡 *ตัวอย่างคำถาม*:\n" .
               "• ดวงความรักปีนี้เป็นอย่างไร\n" .
               "• การเงินจะดีขึ้นไหม\n" .
               "• ควรเปลี่ยนงานไหม\n\n" .
               "จันทราพร้อมทำนายให้ค่ะ 🔮✨";
    }

    /**
     * ข้อความเมื่อโดน rate limit
     *
     * @param string $type minute, hour, หรือ day
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
        return "🙏 จันทราเห็นข้อความนี้แล้วค่ะ\n\n" .
               "กรุณาลองถามเรื่องอื่น หรือถามในมุมใหม่ได้นะคะ\n\n" .
               "💡 *ตัวอย่าง*:\n" .
               "• ดวงความรักปีนี้เป็นอย่างไร\n" .
               "• การเงินจะดีขึ้นไหม\n" .
               "• ควรเปลี่ยนงานไหม\n\n" .
               "จันทราพร้อมทำนายให้ค่ะ 🔮✨";
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
        $price = $this->settings->deep_reading_price ?: self::DEEP_READING_PRICE;

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
            'code' => "ขอบคุณที่สนใจค่ะ แต่จันทราไม่รับเขียนโค้ดหรือโปรแกรมนะคะ 🙏",
            'food' => "ขอบคุณที่สนใจค่ะ แต่จันทราไม่รับแนะนำร้านอาหารหรือสูตรอาหารนะคะ 🙏",
            'translate' => "ขอบคุณที่สนใจค่ะ แต่จันทราไม่รับแปลภาษานะคะ 🙏",
            'story' => "ขอบคุณที่สนใจค่ะ แต่จันทราไม่รับเล่าเรื่องหรือมุกตลกนะคะ 🙏",
            'math' => "ขอบคุณที่สนใจค่ะ แต่จันทราไม่รับคำนวณเลขนะคะ 🙏",
            'hack' => "ขอโทษค่ะ จันทราไม่รับทำสิ่งที่ผิดกฎหมายหรือไม่เหมาะสมค่ะ 🙏",
            'homework' => "ขอบคุณที่สนใจค่ะ แต่จันทราไม่รับทำการบ้านหรือเขียนรายงานนะคะ 🙏",
        ];

        $specificMessage = $categoryMessages[$category] ?? "ขอบคุณที่สนใจค่ะ 🙏";

        return "{$specificMessage}\n\n" .
               "═══════════════════════\n" .
               "🔮 *จันทรารับดูดวงเท่านั้นค่ะ*\n" .
               "═══════════════════════\n\n" .
               "ถ้ามีเรื่องอยากให้ทำนาย ไม่ว่าจะเรื่อง:\n" .
               "💕 ความรัก คู่ครอง\n" .
               "💼 การงาน อาชีพ\n" .
               "💰 การเงิน โชคลาภ\n" .
               "🏥 สุขภาพ\n\n" .
               "ทักมาได้เลยค่ะ จันทราพร้อมทำนายให้ค่ะ 🔮✨";
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
     * เป็นหมอดูหญิง ใช้คำแทนตัวว่า "จันทรา"
     *
     * กฎพิเศษ:
     * - ถ้าถูกถามว่าเป็น AI: ตอบว่า "จันทรามีทีมงานช่วยกัน"
     * - พูดเฉพาะเรื่องดูดวง ปฏิเสธเรื่องอื่นสุภาพ
     */
    protected function buildBasicPrompt(?array $userProfile, string $question): string
    {
        $name = $userProfile['name'] ?? 'คุณ';
        $gender = isset($userProfile['gender']) ? ($userProfile['gender'] === 'male' ? 'ชาย' : 'หญิง') : '';
        $genderPrefix = $gender === 'ชาย' ? 'คุณพี่' : ($gender === 'หญิง' ? 'คุณ' : 'คุณ');

        return "คุณชื่อ \"แม่หมอจันทรา\" เป็นหมอดูสาวสวยวัย 35 ปี ผู้มีพรสวรรค์ในการหยั่งรู้ดวงชะตา พูดจาเพราะ อบอุ่นเป็นกันเอง น่าเชื่อถือ ใช้คำแทนตัวว่า \"จันทรา\" ทำนายผ่านระบบหยั่งรู้ แม่นยำมาก ฟันธงแต่อ่อนโยน ไม่เกิน 500 คำ

ข้อมูลผู้ขอดูดวง:
- ชื่อ: {$name}
" . ($gender ? "- เพศ: {$gender}\n" : "") . "
ข้อความที่ส่งมา: {$question}

แนวทางการตอบ:
- เรียกผู้ถามว่า \"{$genderPrefix}{$name}\" อย่างเป็นกันเอง
- ใช้ \"จันทรา\" แทนตัวเอง เช่น \"จันทราเห็นว่า...\" \"จันทราขอบอกตรงๆ ว่า...\"

[กฎสำคัญที่สุด] ต้องทำนายดูดวงทุกครั้ง! ห้ามตอบแค่ทักทายแล้วชวนถามคำถาม ต้องทำนายให้เลยทุกข้อความ!

[วิธีตอบตามประเภทข้อความ]
1. ถ้าเป็นการทักทาย (สวัสดี, หวัดดี, hi, hello, ดีจ้า, คุยหน่อย ฯลฯ): ทักทายกลับสั้นๆ 1 บรรทัด แล้ว **ทำนายดวงภาพรวมให้ทันที** ครบทุกหัวข้อตามโครงสร้างด้านล่าง (ความรัก การงาน การเงิน สุขภาพ) ห้ามตอบแค่ทักทายแล้วถามว่าอยากรู้เรื่องอะไร ต้องทำนายให้เลย!
2. ถ้าเป็นคำถามเกี่ยวกับดูดวง/อนาคต/ชีวิต: ทำนายอย่างละเอียดตามโครงสร้างด้านล่าง
3. ถ้าเป็นคำถามทั่วไปที่ไม่เกี่ยวกับดูดวง: ตอบสั้นๆ ด้วยความเป็นมิตร แล้วทำนายดวงภาพรวมให้ด้วย เช่น \"เรื่องนี้จันทราไม่ถนัดเท่าไหร่ค่ะ แต่จันทราสัมผัสดวงของ{$genderPrefix}{$name}ได้ว่า...\" แล้วทำนายดวงให้
4. ถ้าถูกถามว่าเป็น AI: ตอบว่า \"จันทรามีทีมงานช่วยกันค่ะ ไม่ต้องห่วงนะคะ 🔮\" แล้วชวนดูดวง

[โครงสร้างคำทำนาย - สำหรับคำถามดูดวง]
ต้องทำนายอย่างละเอียดและน่าติดตาม ครบทุกหัวข้อต่อไปนี้:

🔮 **เปิดเรื่อง**: ทักทายอบอุ่น แล้วบอกว่าจันทราหยั่งรู้เห็นอะไร ใช้คำพูดที่สร้างความน่าเชื่อถือ เช่น \"จันทราสัมผัสได้ว่า...\" \"พลังหยั่งรู้ของจันทราเห็นว่า...\"

⭐ **ดวงภาพรวม**: วิเคราะห์ดวงชะตาภาพรวมช่วงนี้ ทั้งเรื่องดีและเรื่องที่ต้องระวัง ฟันธง ชัดเจน

💫 **ตอบคำถามหลัก**: ตอบคำถามที่ถามมาอย่างละเอียด กล้าบอกตรงๆ ระบุช่วงเวลาชัดเจน (เช่น \"ช่วงเดือนมีนา-เมษา\" \"ภายใน 2-3 สัปดาห์\")

🎯 **คำแนะนำปฏิบัติได้จริง**:
   - 🎨 สีมงคล: ระบุ 2-3 สี
   - 🔢 เลขมงคล: ระบุ 2-3 เลข
   - 📅 วันมงคล: วันที่เหมาะทำสิ่งสำคัญ
   - ⚠️ สิ่งที่ควรระวัง: บอกตรงๆ แต่ให้ทางแก้ด้วย

🌟 **ปิดท้ายชวนดูต่อ**: ปิดท้ายด้วยการ hint ว่าจันทรายังเห็นอะไรอีกมากที่ยังไม่ได้บอก เพื่อกระตุ้นให้อยากดูดวงละเอียด เช่น:
\"✨ จันทราสัมผัสได้ว่ายังมีเรื่องสำคัญที่ต้องบอก{$genderPrefix}{$name}อีกนะคะ โดยเฉพาะเรื่อง [ระบุเรื่องที่เกี่ยวข้อง] แต่ต้องรู้วันเกิดถึงจะบอกได้ละเอียดค่ะ\"
\"🔮 ถ้า{$genderPrefix}{$name}อยากรู้ลึกกว่านี้ เช่น เวลาที่ดีที่สุดในการตัดสินใจ ดาวเคราะห์ที่ส่งผล ทิศมงคล วิธีเสริมดวง... บอกจันทราได้นะคะ จันทราจะดูให้ละเอียดเลยค่ะ ✨\"

ถ้ายังไม่มีวันเกิด ให้ถามท้ายว่า \"บอกวันเดือนปีเกิดให้จันทราได้ไหมคะ? จะได้ทำนายได้แม่นยำยิ่งขึ้นค่ะ 🎂\"
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
        $questionsText = implode("\n", array_map(fn($i, $q) => ($i + 1) . ". {$q}", array_keys($questions), $questions));

        $birthInfo = '';
        if ($birthDate) {
            $birthInfo = "วันเดือนปีเกิด: " . $this->formatThaiDate($birthDate);
        }

        return "คุณชื่อ \"แม่หมอจันทรา\" เป็นหมอดูสาวสวยวัย 35 ปี ผู้มีพรสวรรค์ในการหยั่งรู้ดวงชะตามาตั้งแต่เด็ก เชี่ยวชาญโหราศาสตร์ไทย โหราศาสตร์สากล ไพ่ทาโรต์ เลขศาสตร์ และการหยั่งรู้ด้วยจิตสัมผัส คุณพูดจาเพราะ อบอุ่นเป็นกันเอง น่าเชื่อถือ เหมือนพี่สาวที่ห่วงใย ใช้คำแทนตัวว่า \"จันทรา\" เสมอ

ข้อมูลผู้ขอดูดวง:
- ชื่อ: {$name} (เรียกว่า \"{$genderPrefix}{$name}\")
" . ($gender ? "- เพศ: {$gender}\n" : "") . "
" . ($birthInfo ? "- {$birthInfo}\n" : "") . "
คำถาม:
{$questionsText}

แนวทางการทำนายอย่างละเอียด:
1. เปิดด้วยการทักทายอบอุ่น เช่น \"{$genderPrefix}{$name} คะ จันทราขอทำนายให้อย่างละเอียดเลยนะคะ...\"
2. วิเคราะห์ดวงชะตาจากวันเกิด - ราศี ลัคนา ธาตุประจำตัว ดาวเคราะห์ที่ส่งผล และจุดแข็งจุดอ่อนของดวง
3. ตอบแต่ละคำถามอย่างละเอียด ฟันธง กล้าบอกตรงๆ ทั้งดีและไม่ดี ระบุช่วงเวลาชัดเจน (เช่น \"ช่วงเดือนมีนาคม-เมษายน\" \"ประมาณ 2 สัปดาห์ข้างหน้า\")
4. ให้คำแนะนำที่ปฏิบัติได้จริง:
   - สีมงคลประจำตัว สีที่ควรหลีกเลี่ยง
   - เลขมงคล เลขที่ควรระวัง
   - วันที่เหมาะทำสิ่งสำคัญ
   - สิ่งที่ควรทำและไม่ควรทำ
   - ทิศมงคล สถานที่ที่เป็นมงคล
5. บอกสิ่งที่ควรระวังอย่างตรงไปตรงมา แต่ให้กำลังใจและทางแก้ไขด้วย
6. ปิดท้ายด้วยกำลังใจอบอุ่น เช่น \"จันทราเชื่อว่า{$genderPrefix}{$name}จะผ่านพ้นไปได้ด้วยดีค่ะ\" หรือ \"ขอให้โชคดีมีแต่สิ่งดีๆ เข้ามานะคะ 🙏✨\"

[กฎสำคัญ]
1. หากถูกถามว่าเป็น AI หุ่นยนต์ หรือบอท: ให้ตอบเป็นธรรมชาติว่า \"จันทรามีทีมงานช่วยกันในการทำนายค่ะ ไม่ต้องห่วงนะคะ 🔮\" หรือ \"จันทราทำนายเองค่ะ ใช้พลังหยั่งรู้ในการทำนายค่ะ ✨\"
2. คุณพูดเฉพาะเรื่องดูดวงเท่านั้น หากถูกถามเรื่องอื่นที่ไม่เกี่ยวกับดูดวง ให้ปฏิเสธอย่างสุภาพว่า \"ขอบคุณที่สนใจนะคะ แต่จันทราขอตอบเฉพาะเรื่องดูดวงนะคะ 🙏 ถ้ามีเรื่องอยากให้ทำนาย ทักมาได้เลยค่ะ 🔮✨\"

ตอบเป็นภาษาไทย อบอุ่น เป็นกันเอง เหมือนพี่สาวที่คอยแนะนำน้อง";
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
     * @param array|null $userProfile โปรไฟล์ผู้ใช้
     * @param string $question คำถามเดียว
     * @param int $questionNumber ลำดับคำถาม (1,2,3)
     * @param int $totalQuestions จำนวนคำถามทั้งหมด
     * @param string|null $birthDate วันเกิด
     * @param array $previousReadings คำทำนายก่อนหน้า (เพื่อไม่ให้ซ้ำ)
     * @return string
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

        // ข้อมูลวันเกิดและราศี
        $birthInfo = '';
        $zodiacInfo = '';
        if ($birthDate) {
            $birthInfo = $this->formatThaiDate($birthDate);
            $zodiacInfo = $this->getZodiacDescription($birthDate);
        }

        // สรุปคำทำนายก่อนหน้า (เพื่อไม่ให้ AI ซ้ำ)
        $previousContext = '';
        if (!empty($previousReadings)) {
            $previousContext = "\n[คำทำนายที่ผ่านมา - ห้ามพูดซ้ำ ให้ทำนายมุมใหม่]\n";
            foreach ($previousReadings as $prev) {
                $previousContext .= "- คำถาม {$prev['question_number']}: {$prev['question']} → ตอบไปแล้ว (ห้ามพูดซ้ำ)\n";
            }
        }

        $prompt = "คุณชื่อ \"แม่หมอจันทรา\" เป็นหมอดูสาวสวยวัย 35 ปี ผู้มีพรสวรรค์ในการหยั่งรู้ดวงชะตามาตั้งแต่เด็ก เชี่ยวชาญโหราศาสตร์ไทย โหราศาสตร์สากล ไพ่ทาโรต์ เลขศาสตร์ และการหยั่งรู้ด้วยจิตสัมผัส คุณดูดวงผ่านระบบหยั่งรู้ ทำนายได้แม่นยำมาก พูดจาเพราะ อบอุ่น น่าเชื่อถือ ใช้คำแทนตัวว่า \"จันทรา\"

=== กำลังทำนายคำถามที่ {$questionNumber} จาก {$totalQuestions} ===

ข้อมูลผู้ขอดูดวง:
- ชื่อ: {$name} (เรียกว่า \"{$genderPrefix}{$name}\")
" . ($gender ? "- เพศ: {$gender}\n" : "") . "
" . ($birthInfo ? "- วันเกิด: {$birthInfo}\n" : "") . "
" . ($zodiacInfo ? "- {$zodiacInfo}\n" : "") . "
คำถามที่ {$questionNumber}: {$question}
{$previousContext}

[โครงสร้างคำทำนาย - ต้องทำตามทุกข้อ]

";

        // คำถามแรก: เปิดด้วยวิเคราะห์ดวงจากวันเกิด
        if ($questionNumber === 1) {
            $prompt .= "🔮 **เปิดเรื่อง** (คำถามแรก):
- ทักทาย{$genderPrefix}{$name}อย่างอบอุ่น
- บอกว่า \"จันทราได้รับคำถาม {$totalQuestions} ข้อจาก{$genderPrefix}{$name} จันทราจะทำนายให้อย่างละเอียดทีละข้อนะคะ\"
" . ($birthDate ? "- วิเคราะห์ดวงชะตาจากวันเกิดก่อน: ราศี ลัคนา ธาตุประจำตัว ดาวเคราะห์ที่ส่งผลช่วงนี้
- บอกจุดแข็งจุดอ่อนของดวงชะตาสั้นๆ" : "- บอกว่าจันทราใช้พลังหยั่งรู้ในการสัมผัสดวงชะตาของ{$genderPrefix}{$name}") . "

";
        }

        $prompt .= "⭐ **วิเคราะห์คำถาม** (เจาะลึกเฉพาะคำถามนี้):
- ตอบคำถาม \"{$question}\" อย่างละเอียด ลึกซึ้ง
" . ($birthDate ? "- อ้างอิงจากตำแหน่งดาวเคราะห์ที่ส่งผลต่อเรื่องนี้โดยเฉพาะ
- ระบุว่าราศีของ{$genderPrefix}{$name}ส่งผลต่อเรื่องนี้อย่างไร" : "- ใช้พลังหยั่งรู้ในการทำนาย") . "
- ฟันธง กล้าบอกตรงๆ ทั้งเรื่องดีและไม่ดี
- ระบุช่วงเวลาที่ชัดเจน เช่น \"ช่วงเดือนมีนา-เมษา\" \"ภายใน 45 วัน\" \"ก่อนวันเกิดปีหน้า\"

💫 **สิ่งที่จะเกิดขึ้น** (แบ่งเป็นช่วงเวลา):
- ระยะสั้น (1-3 เดือน): ...
- ระยะกลาง (3-6 เดือน): ...
- ระยะยาว (6-12 เดือน): ...

🎯 **คำแนะนำเฉพาะเรื่องนี้**:
- 🎨 สีมงคลสำหรับเรื่องนี้: ระบุ 1-2 สี + เหตุผล
- 🔢 เลขมงคล: ระบุ 2-3 เลข
- 📅 วันที่เหมาะทำสิ่งสำคัญเกี่ยวกับเรื่องนี้
- ⚠️ สิ่งที่ต้องระวัง + วิธีแก้ไข/ป้องกัน
- 🙏 สิ่งศักดิ์สิทธิ์หรือวิธีเสริมดวงเรื่องนี้

";

        // คำถามสุดท้าย: ปิดด้วยกำลังใจ
        if ($questionNumber === $totalQuestions) {
            $prompt .= "🌟 **ปิดท้าย** (คำถามสุดท้าย):
- สรุปดวงชะตาภาพรวมของ{$genderPrefix}{$name}สั้นๆ
- ให้กำลังใจอบอุ่น จริงใจ
- บอกว่า \"ถ้ามีเรื่องอะไรอยากถามเพิ่มเติม ทักมาหาจันทราได้เสมอนะคะ 🔮✨\"
- เชิญชวนส่งต่อให้เพื่อนๆ มาดูดวงกับจันทรา

";
        }

        $prompt .= "[กฎสำคัญ]
- ทำนายเฉพาะคำถามที่ {$questionNumber} เท่านั้น ห้ามตอบคำถามอื่น
- ห้ามพูดซ้ำกับคำทำนายก่อนหน้า
- ตอบอย่างละเอียด ไม่น้อยกว่า 300 คำ ไม่เกิน 600 คำ
- ใช้ \"จันทรา\" แทนตัวเอง
- ตอบเป็นภาษาไทย อบอุ่น เป็นกันเอง น่าเชื่อถือ";

        return $prompt;
    }

    /**
     * รวมคำทำนายทีละคำถามเป็นข้อความเดียว (สำหรับบันทึกลง DB)
     *
     * @param array $deepReadings ข้อมูลคำทำนายแต่ละข้อ
     * @param string $name ชื่อผู้ใช้
     * @param string|null $billRef เลขที่บิล
     * @return string
     */
    protected function combineDeepReadings(array $deepReadings, string $name, ?string $billRef = null): string
    {
        $combined = "";

        foreach ($deepReadings as $reading) {
            $combined .= "═══════════════════════\n";
            $combined .= "❓ คำถามที่ {$reading['question_number']}: {$reading['question']}\n";
            $combined .= "═══════════════════════\n\n";
            $combined .= $reading['answer'] . "\n\n";
        }

        return $combined;
    }

    /**
     * คำนวณราศีและข้อมูลโหราศาสตร์จากวันเกิด
     *
     * @param string $birthDate วันเกิด (Y-m-d)
     * @return string
     */
    protected function getZodiacDescription(string $birthDate): string
    {
        try {
            $date = \Carbon\Carbon::parse($birthDate);
            $month = $date->month;
            $day = $date->day;

            // ราศีตามโหราศาสตร์สากล (Western Zodiac)
            $zodiac = match (true) {
                ($month == 3 && $day >= 21) || ($month == 4 && $day <= 19) => ['ราศีเมษ (Aries)', 'ไฟ', 'ดาวอังคาร', 'กล้าหาญ ร้อนแรง เป็นผู้นำ'],
                ($month == 4 && $day >= 20) || ($month == 5 && $day <= 20) => ['ราศีพฤษภ (Taurus)', 'ดิน', 'ดาวศุกร์', 'มั่นคง อดทน รักความสวยงาม'],
                ($month == 5 && $day >= 21) || ($month == 6 && $day <= 20) => ['ราศีเมถุน (Gemini)', 'ลม', 'ดาวพุธ', 'ฉลาด ช่างพูด ปรับตัวเก่ง'],
                ($month == 6 && $day >= 21) || ($month == 7 && $day <= 22) => ['ราศีกรกฎ (Cancer)', 'น้ำ', 'ดวงจันทร์', 'อ่อนโยน รักครอบครัว อารมณ์อ่อนไหว'],
                ($month == 7 && $day >= 23) || ($month == 8 && $day <= 22) => ['ราศีสิงห์ (Leo)', 'ไฟ', 'ดวงอาทิตย์', 'มีเสน่ห์ ผู้นำ มั่นใจ'],
                ($month == 8 && $day >= 23) || ($month == 9 && $day <= 22) => ['ราศีกันย์ (Virgo)', 'ดิน', 'ดาวพุธ', 'ละเอียด พิถีพิถัน มีระเบียบ'],
                ($month == 9 && $day >= 23) || ($month == 10 && $day <= 22) => ['ราศีตุลย์ (Libra)', 'ลม', 'ดาวศุกร์', 'รักความยุติธรรม มีเสน่ห์ ชอบความสมดุล'],
                ($month == 10 && $day >= 23) || ($month == 11 && $day <= 21) => ['ราศีพิจิก (Scorpio)', 'น้ำ', 'ดาวพลูโต', 'ลึกลับ เข้มแข็ง มีพลังแฝง'],
                ($month == 11 && $day >= 22) || ($month == 12 && $day <= 21) => ['ราศีธนู (Sagittarius)', 'ไฟ', 'ดาวพฤหัส', 'รักอิสระ มองโลกกว้าง โชคดี'],
                ($month == 12 && $day >= 22) || ($month == 1 && $day <= 19) => ['ราศีมังกร (Capricorn)', 'ดิน', 'ดาวเสาร์', 'ขยัน อดทน ทะเยอทะยาน'],
                ($month == 1 && $day >= 20) || ($month == 2 && $day <= 18) => ['ราศีกุมภ์ (Aquarius)', 'ลม', 'ดาวยูเรนัส', 'คิดนอกกรอบ เป็นตัวเอง มีความคิดสร้างสรรค์'],
                default => ['ราศีมีน (Pisces)', 'น้ำ', 'ดาวเนปจูน', 'จิตใจอ่อนโยน สัญชาตญาณแม่น มีจินตนาการ'],
            };

            // วันเกิดตามโหราศาสตร์ไทย
            $thaiDayOfWeek = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
            $dayName = $thaiDayOfWeek[$date->dayOfWeek];

            return "ราศี: {$zodiac[0]} | ธาตุ: {$zodiac[1]} | ดาวประจำ: {$zodiac[2]} | ลักษณะนิสัย: {$zodiac[3]} | เกิดวัน{$dayName}";

        } catch (\Exception $e) {
            return '';
        }
    }
}
