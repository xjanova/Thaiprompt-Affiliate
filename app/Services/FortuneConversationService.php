<?php

namespace App\Services;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneUserCredit;
use App\Models\LineBotKeyword;
use App\Models\SmsPaymentNotification;
use App\Models\UniquePaymentAmount;
use App\Services\FortuneChannelManager;
use App\Services\LineFortuneService;
use App\Services\LineGatekeeperService;
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
     * Platform ปัจจุบัน ('line' หรือ 'facebook')
     * ใช้สำหรับบันทึก platform ที่ถูกต้องเมื่อ save คำถามรอตอบ
     */
    protected string $currentPlatform = 'line';

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
     * Rate Limiting: จำนวนข้อความสูงสุดต่อนาที
     * เพิ่มเป็น 120 เพื่อไม่จำกัดการใช้งานปกติ — เหลือแค่กัน spam bot
     */
    public const MAX_MESSAGES_PER_MINUTE = 120;

    /**
     * Rate Limiting: จำนวนข้อความสูงสุดต่อชั่วโมง
     * เพิ่มเป็น 1000 เพื่อไม่จำกัดการใช้งานปกติ
     */
    public const MAX_MESSAGES_PER_HOUR = 1000;

    /**
     * Rate Limiting: จำนวน AI calls สูงสุดต่อวัน (ต่อ user) - fallback ถ้าไม่ได้ตั้งค่า
     * ปกติใช้ค่าจาก settings.max_free_readings แทน
     * เพิ่มเป็น 50 เพื่อให้คุยได้ไม่จำกัดในทางปฏิบัติ
     */
    public const MAX_AI_CALLS_PER_DAY = 50;

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
     * ตั้งค่า platform ปัจจุบัน (เรียกจาก FortuneChannelManager ก่อน processMessage)
     *
     * @param string $platform 'line' หรือ 'facebook'
     * @return self
     */
    public function setPlatform(string $platform): self
    {
        $this->currentPlatform = in_array($platform, ['line', 'facebook']) ? $platform : 'line';

        return $this;
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
     * สร้าง Dynamic PromptPay QR Code พร้อมยอดเงิน (EMVCo standard)
     *
     * สร้าง QR Code ที่มียอดเงินฝังอยู่ → ผู้ใช้สแกนจ่ายได้เลย
     * ใช้ PromptPayProvider เดียวกับระบบ Checkout ของ E-commerce
     *
     * LINE ไม่รับ data URI → save เป็นไฟล์ PNG แล้ว return URL สาธารณะ
     *
     * @param float $amount ยอดเงินที่ต้องชำระ (unique amount รวมทศนิยม)
     * @param int|null $readingId ID ของ FortuneReading (ใช้ตั้งชื่อไฟล์)
     * @return string|null URL สาธารณะของ QR Code หรือ null ถ้าสร้างไม่ได้
     */
    protected function generatePromptPayQrImage(float $amount, ?int $readingId = null): ?string
    {
        try {
            // ดึง PromptPay ID + Type
            $promptPayId = $this->getPromptPayId();
            $promptPayType = $this->getPromptPayType();

            if (empty($promptPayId)) {
                Log::warning('Fortune QR: PromptPay ไม่ได้ตั้งค่า — ไม่สามารถสร้าง QR ได้');

                return null;
            }

            // สร้าง EMVCo payload
            $provider = new \App\Services\Payment\PromptPayProvider();
            $emvPayload = $provider->buildPromptPayPayload($promptPayId, $promptPayType, $amount);

            if (empty($emvPayload)) {
                return null;
            }

            // เตรียม directory สำหรับ save ไฟล์
            $filename = 'fortune_pp_'.($readingId ?? uniqid()).'.png';
            $directory = 'qrcodes/fortune';
            $fullDir = storage_path('app/public/'.$directory);

            if (! file_exists($fullDir)) {
                mkdir($fullDir, 0755, true);
            }

            $fullPath = $fullDir.'/'.$filename;

            // ตรวจสอบ storage symlink (สร้างอัตโนมัติถ้ายังไม่มี)
            $symlinkPath = public_path('storage');
            if (! file_exists($symlinkPath)) {
                try {
                    \Artisan::call('storage:link');
                } catch (\Exception $linkErr) {
                    Log::warning('Fortune QR: สร้าง storage symlink ไม่ได้', [
                        'error' => $linkErr->getMessage(),
                    ]);
                }
            }

            // ✅ วิธีที่ 1: ใช้ BaconQrCode Encoder + PHP GD → สร้าง PNG โดยตรง
            // GD extension มีอยู่แทบทุก PHP server — เชื่อถือได้สูงสุด
            if (class_exists(\BaconQrCode\Encoder\Encoder::class) && function_exists('imagecreatetruecolor')) {
                $qrCode = \BaconQrCode\Encoder\Encoder::encode(
                    $emvPayload,
                    \BaconQrCode\Common\ErrorCorrectionLevel::H(),
                    \BaconQrCode\Encoder\Encoder::DEFAULT_BYTE_MODE_ECODING
                );

                $matrix = $qrCode->getMatrix();
                $matrixSize = $matrix->getWidth();

                // คำนวณขนาด module ให้ได้ภาพ ~400px
                $moduleSize = max(1, (int) floor(400 / $matrixSize));
                $margin = $moduleSize * 2; // ขอบ 2 modules
                $realSize = ($moduleSize * $matrixSize) + ($margin * 2);

                $img = imagecreatetruecolor($realSize, $realSize);
                $white = imagecolorallocate($img, 255, 255, 255);
                $black = imagecolorallocate($img, 0, 0, 0);
                imagefill($img, 0, 0, $white);

                for ($y = 0; $y < $matrixSize; $y++) {
                    for ($x = 0; $x < $matrixSize; $x++) {
                        if ($matrix->get($x, $y) === 1) {
                            imagefilledrectangle(
                                $img,
                                $margin + ($x * $moduleSize),
                                $margin + ($y * $moduleSize),
                                $margin + (($x + 1) * $moduleSize) - 1,
                                $margin + (($y + 1) * $moduleSize) - 1,
                                $black
                            );
                        }
                    }
                }

                imagepng($img, $fullPath);
                imagedestroy($img);

                $publicUrl = asset('storage/'.$directory.'/'.$filename);

                Log::info('Fortune QR: สร้าง PromptPay QR PNG สำเร็จ (BaconQrCode+GD)', [
                    'amount' => $amount,
                    'reading_id' => $readingId,
                    'size' => $realSize,
                    'url' => $publicUrl,
                ]);

                return $publicUrl;
            }

            // ✅ วิธีที่ 2: ใช้ QR API สาธารณะ (ไม่ต้อง save ไฟล์)
            $encodedPayload = urlencode($emvPayload);
            $apiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=400x400&ecc=H&data={$encodedPayload}";

            Log::info('Fortune QR: ใช้ QR API สาธารณะ', [
                'amount' => $amount,
                'reading_id' => $readingId,
            ]);

            return $apiUrl;

        } catch (\Exception $e) {
            Log::error('Fortune QR: สร้าง PromptPay QR ล้มเหลว', [
                'amount' => $amount,
                'reading_id' => $readingId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * ดึง PromptPay ID จากบัญชีธนาคารที่ตั้งค่าไว้
     *
     * ลำดับ: 1) บัญชีธนาคารดูดวง 2) บัญชี SMS Checker 3) PaymentGateway config
     *
     * @return string PromptPay ID (เบอร์โทรหรือบัตรประชาชน)
     */
    protected function getPromptPayId(): string
    {
        // 1. เช็คบัญชีธนาคารดูดวง
        $accounts = $this->settings->getFortuneBankAccounts();
        foreach ($accounts as $account) {
            if (method_exists($account, 'hasPromptpay') && $account->hasPromptpay()) {
                return $account->promptpay_id;
            }
        }

        // 2. เช็คบัญชี active ที่มี PromptPay
        $bankAccount = \App\Models\PaymentBankAccount::active()
            ->hasPromptpay()
            ->orderByDesc('is_default')
            ->first();

        if ($bankAccount) {
            return $bankAccount->promptpay_id;
        }

        // 3. Fallback: PaymentGateway config
        try {
            $gateway = \App\Models\PaymentGateway::findByCode('promptpay');

            return $gateway?->getCredential('promptpay_id') ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * ดึงประเภท PromptPay (phone, citizen_id, ewallet)
     *
     * @return string ประเภท PromptPay
     */
    protected function getPromptPayType(): string
    {
        $accounts = $this->settings->getFortuneBankAccounts();
        foreach ($accounts as $account) {
            if (method_exists($account, 'hasPromptpay') && $account->hasPromptpay()) {
                return $account->promptpay_type ?? 'phone';
            }
        }

        $bankAccount = \App\Models\PaymentBankAccount::active()
            ->hasPromptpay()
            ->orderByDesc('is_default')
            ->first();

        if ($bankAccount) {
            return $bankAccount->promptpay_type ?? 'phone';
        }

        try {
            $gateway = \App\Models\PaymentGateway::findByCode('promptpay');

            return $gateway?->getCredential('promptpay_type') ?? 'phone';
        } catch (\Exception $e) {
            return 'phone';
        }
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

            // ✅ Duplicate message filter: ป้องกันข้อความเดียวกันถูก process ซ้ำ
            // เช่น LINE retry, กดปุ่มหลายครั้ง (ตรวจจากข้อความ hash)
            // ⚠️ ไม่ block คำถามข้อถัดไป เพราะเช็ค message content ไม่ใช่ user ID
            $msgHash = md5($facebookUserId.':'.$messageText);
            $dedupKey = "fortune:dedup:{$msgHash}";
            // ใช้ Cache::add() (atomic) แทน has()+put() เพื่อป้องกัน race condition
            // ⚠️ TTL 30 วินาที (เดิม 5s → Facebook retry หลัง ~5s ทำให้ dedup หมดอายุแล้ว process ซ้ำ)
            if (! Cache::add($dedupKey, true, 30)) {
                Log::info('Fortune processMessage: ข้อความซ้ำ (dedup) ข้ามไป', [
                    'facebook_user_id' => $facebookUserId,
                    'text_preview' => mb_substr($messageText, 0, 30),
                ]);

                return [
                    'action' => 'dedup_skip', // ข้อความซ้ำ → ข้ามเงียบๆ ไม่ต้องส่งอะไร
                    'message' => null,
                    'reading' => null,
                ];
            }

            // ✅ Simple mutex: ป้องกัน concurrent processing สำหรับ user คนเดียวกัน
            // ใช้ Cache::put แทน Cache::lock เพื่อให้ทำงานกับทุก cache driver
            $lockKey = "fortune:processing:{$facebookUserId}";
            $lockAcquired = false;

            // พยายามขอ lock — ถ้าไม่ได้ รอแล้วลองใหม่
            // ใช้ Cache::add() (atomic) แทน has()+put() เพื่อป้องกัน race condition
            for ($lockAttempt = 0; $lockAttempt < 3; $lockAttempt++) {
                if (Cache::add($lockKey, true, 30)) { // TTL 30 วินาที (เดิม 8s → สั้นเกินเมื่อ process ใช้เวลานาน เช่น สร้าง chart+QR+AI)
                    $lockAcquired = true;
                    break;
                }
                if ($lockAttempt < 2) {
                    usleep(600_000); // รอ 0.6 วินาที ก่อนลองใหม่
                }
            }

            if (! $lockAcquired) {
                Log::info('Fortune processMessage: concurrent processing (mutex) ข้ามไป', [
                    'facebook_user_id' => $facebookUserId,
                    'text_preview' => mb_substr($messageText, 0, 30),
                ]);

                return [
                    'action' => 'busy',
                    'message' => 'กำลังประมวลผลอยู่ กรุณารอสักครู่ 🙏',
                    'reading' => null,
                ];
            }

            try {

            // ═══════════════════════════════════════════════════════════
            // 🔝 ลำดับที่ 1: เช็คคำทำนายที่รอส่ง/กำลังเตรียม (สำคัญสุด!)
            // ═══════════════════════════════════════════════════════════
            // เมื่อลูกค้าจ่ายเงินแล้ว คำทำนายต้องเป็นลำดับแรกเสมอ
            // ไม่ว่าจะพิมพ์อะไรมา ต้องแจ้งสถานะคำทำนายก่อน
            // ยกเว้นเฉพาะ "ไว้ดูทีหลัง" (ปฏิเสธชัดเจน) และ "ยกเลิก"

            // ✅ ตรวจสอบคำสั่ง "ไว้ดูทีหลัง" (จากปุ่ม quick reply หลังคำทำนายพร้อม)
            if ($this->isViewLaterRequest($messageText)) {
                return $this->handleViewLater($facebookUserId);
            }

            // ✅ V3: เช็คคำทำนายที่พร้อมส่งแต่ยังไม่ได้ส่ง (ไม่ใช้ push เลย — ส่งผ่าน replyMessage ฟรี!)
            // Flow: แจ้ง "คำทำนายพร้อมแล้ว" → user ตอบ → ส่งคำทำนายเต็ม
            $unsentReading = FortuneReading::where('facebook_user_id', $facebookUserId)
                ->where('is_paid', true)
                ->whereNotNull('deep_response')
                ->where('deep_response', '!=', '')
                ->where('conversation_status', FortuneReading::STATUS_COMPLETED)
                ->latest()
                ->first();

            if ($unsentReading) {
                $alreadySent = $unsentReading->getConversationState('reading_sent_directly', false);
                $notificationSent = $unsentReading->getConversationState('reading_notification_sent', false);

                // ✅ FIX: ไม่พึ่ง reading_ready_for_reply flag อีกต่อไป
                // ถ้ามี COMPLETED + deep_response + ยังไม่ส่ง → ต้องจัดการเสมอ
                // (flag อาจไม่ถูก set เนื่องจาก race condition ระหว่าง save กับ set flag)
                if (! $alreadySent) {

                    // ✅ ตั้ง flag ให้ถูกต้อง (self-healing)
                    if (! $unsentReading->getConversationState('reading_ready_for_reply', false)) {
                        $unsentReading->setConversationState('reading_ready_for_reply', true);
                        $unsentReading->setConversationState('reading_ready_at', now()->toIso8601String());
                        Log::info('Fortune processMessage: self-heal ตั้ง reading_ready_for_reply flag', [
                            'reading_id' => $unsentReading->id,
                        ]);
                    }

                    // ถ้าแจ้งเตือนไปแล้ว (push หรือ reply) → user ตอบกลับ → ส่งคำทำนายเต็มทันที!
                    if ($notificationSent) {
                        Log::info('Fortune processMessage: user ตอบกลับ → ส่งคำทำนายเต็มผ่าน replyMessage (ฟรี!)', [
                            'facebook_user_id' => $facebookUserId,
                            'reading_id' => $unsentReading->id,
                            'bill_reference' => $unsentReading->bill_reference,
                        ]);

                        // ✅ Mark as sent (ป้องกัน duplicate)
                        $unsentReading->setConversationState('reading_sent_directly', true);
                        $unsentReading->setConversationState('reading_ready_sent', true);
                        $unsentReading->setConversationState('reading_ready_sent_at', now()->toIso8601String());
                        $unsentReading->setConversationState('delivered_by_reply_message', true);

                        $name = $unsentReading->facebook_user_name ?? 'คุณ';
                        $message = "🌟 *คำทำนายเชิงลึกของคุณ{$name}*\n";
                        $message .= '📋 เลขที่บิล: '.($unsentReading->bill_reference ?? '-')."\n";
                        $message .= '📅 วันที่: '.$unsentReading->created_at->format('d/m/Y H:i')."\n";
                        $message .= "═══════════════════════\n\n";
                        $message .= $unsentReading->deep_response;

                        return [
                            'action' => 'view_reading_deep',
                            'message' => $message,
                            'reading' => $unsentReading,
                            'chart_image_url' => $unsentReading->reading_image_url,
                        ];
                    }

                    // ✅ FIX: ถ้า push เคย attempt แล้วแต่ล้มเหลว (โควต้าหมด/error) →
                    // ส่งคำทำนายเต็มทันทีเลย ไม่ต้องให้ user ส่ง 2 ข้อความ
                    $notifyAttempted = $unsentReading->getConversationState('reading_notification_attempted', false);

                    if ($notifyAttempted) {
                        // Push เคยพยายามแล้วแต่ล้มเหลว → ส่งคำทำนายเต็มทันที (ไม่ต้องถามอีก)
                        Log::info('Fortune processMessage: push เคยล้มเหลว → ส่งคำทำนายเต็มทันทีผ่าน replyMessage', [
                            'facebook_user_id' => $facebookUserId,
                            'reading_id' => $unsentReading->id,
                        ]);

                        $unsentReading->setConversationState('reading_sent_directly', true);
                        $unsentReading->setConversationState('reading_ready_sent', true);
                        $unsentReading->setConversationState('reading_ready_sent_at', now()->toIso8601String());
                        $unsentReading->setConversationState('delivered_by_reply_message', true);
                        $unsentReading->setConversationState('reading_notification_sent', true);

                        $name = $unsentReading->facebook_user_name ?? 'คุณ';
                        $message = "🌟 *คำทำนายเชิงลึกของคุณ{$name}*\n";
                        $message .= '📋 เลขที่บิล: '.($unsentReading->bill_reference ?? '-')."\n";
                        $message .= '📅 วันที่: '.$unsentReading->created_at->format('d/m/Y H:i')."\n";
                        $message .= "═══════════════════════\n\n";
                        $message .= $unsentReading->deep_response;

                        return [
                            'action' => 'view_reading_deep',
                            'message' => $message,
                            'reading' => $unsentReading,
                            'chart_image_url' => $unsentReading->reading_image_url,
                        ];
                    }

                    // ยังไม่เคยพยายาม push → แจ้ง "คำทำนายพร้อมแล้ว จะอ่านเลยไหม?"
                    // (ข้อความนี้ส่งผ่าน replyMessage ฟรี)
                    Log::info('Fortune processMessage: พบคำทำนายพร้อมส่ง → แจ้งเตือน user ผ่าน replyMessage', [
                        'facebook_user_id' => $facebookUserId,
                        'reading_id' => $unsentReading->id,
                        'bill_reference' => $unsentReading->bill_reference,
                    ]);

                    $unsentReading->setConversationState('reading_notification_sent', true);
                    $unsentReading->setConversationState('reading_notification_sent_at', now()->toIso8601String());

                    $name = $unsentReading->facebook_user_name ?? 'คุณ';

                    return [
                        'action' => 'fortune_ready_notification',
                        'message' => "✨ คุณ{$name} คำทำนายเชิงลึกพร้อมแล้ว!\n\n"
                            . '📋 เลขที่บิล: '.($unsentReading->bill_reference ?? '-')."\n\n"
                            . "🔮 พร้อมอ่านเลยไหม? พิมพ์ 'อ่านเลย' หรือกดปุ่มด้านล่างได้เลย ✨",
                        'reading' => $unsentReading,
                        'quick_replies' => ['อ่านคำทำนาย', 'ไว้ดูทีหลัง'],
                    ];
                }
            }

            // ✅ เช็คคำทำนายที่กำลังประมวลผลอยู่ (PAID หรือ COMPLETED แต่ยังไม่มี deep_response)
            // ⚠️ ข้ามถ้ามี active conversation อยู่ (ไม่งั้นจะ block คำถามข้อ 2+)
            $hasActiveConversation = FortuneReading::findActiveConversation($facebookUserId);

            if (! $hasActiveConversation) {
                $processingReading = FortuneReading::where('facebook_user_id', $facebookUserId)
                    ->where('is_paid', true)
                    ->where(function ($q) {
                        $q->where('conversation_status', FortuneReading::STATUS_PAID)
                            ->orWhere(function ($q2) {
                                $q2->where('conversation_status', FortuneReading::STATUS_COMPLETED)
                                    ->where(function ($q3) {
                                        $q3->whereNull('deep_response')
                                            ->orWhere('deep_response', '');
                                    });
                            });
                    })
                    ->where('created_at', '>=', now()->subMinutes(10)) // ลดจาก 1 ชม. เป็น 10 นาที
                    ->latest()
                    ->first();

                if ($processingReading) {
                    $name = $processingReading->facebook_user_name ?? 'คุณ';

                    Log::info('Fortune processMessage: พบคำทำนายกำลังประมวลผล → แจ้งให้รอ', [
                        'facebook_user_id' => $facebookUserId,
                        'reading_id' => $processingReading->id,
                        'status' => $processingReading->conversation_status,
                        'bill_reference' => $processingReading->bill_reference,
                    ]);

                    return [
                        'action' => 'processing',
                        'message' => "🔮 คุณ{$name} กำลังเตรียมคำทำนายอยู่\n\n"
                            . '📋 เลขที่บิล: '.($processingReading->bill_reference ?? '-')."\n"
                            . "⏳ ใช้เวลาประมาณ 1-3 นาที\n\n"
                            . "จะแจ้งให้ทราบทันทีเมื่อคำทำนายพร้อม ✨",
                        'reading' => $processingReading,
                    ];
                }
            }

            // ═══════════════════════════════════════════════════════════
            // 🔽 ลำดับที่ 2: คำสั่งพิเศษ (เฉพาะเมื่อไม่มีคำทำนายค้างรอส่ง)
            // ═══════════════════════════════════════════════════════════

            // ✅ ตรวจสอบคำสั่งพิเศษ: เช็คสิทธิ์ดูดวง
            if ($this->isCheckRemainingRequest($messageText)) {
                return $this->handleCheckRemaining($facebookUserId);
            }

            // ✅ ตรวจสอบคำสั่งพิเศษ: ดูคำทำนายล่าสุด
            if ($this->isViewLastReadingRequest($messageText)) {
                return $this->handleViewLastReading($facebookUserId);
            }

            // ✅ ตรวจสอบคำสั่ง "แชร์" → ส่งลิงก์เชิญเพื่อน
            if ($this->isShareRequest($messageText)) {
                return $this->handleShareRequest($facebookUserId);
            }

            // ✅ ตรวจสอบคำสั่ง "สายงาน" → ดูรายชื่อคนที่แนะนำ (downline)
            if ($this->isDownlineRequest($messageText)) {
                return $this->handleDownlineRequest($facebookUserId);
            }

            // ✅ ตรวจสอบคำสั่ง "รายได้" → ดูรายได้ค่าแนะนำจากสายงาน
            if ($this->isEarningsRequest($messageText)) {
                return $this->handleEarningsRequest($facebookUserId);
            }

            // ✅ ตรวจสอบคำสั่ง "แผนการตลาด" → แสดงรายละเอียดค่าคอมมิชชั่น
            if ($this->isMarketingPlanRequest($messageText)) {
                return $this->handleMarketingPlanRequest($facebookUserId);
            }

            // ✅ ตรวจสอบคำสั่ง "ฝากคำถาม" → เข้าโหมดฝากคำถามถึงแอดมิน
            if ($this->isLeaveQuestionRequest($messageText)) {
                return $this->handleManualLeaveQuestion($facebookUserId, $userProfile);
            }

            // ✅ ตรวจสอบว่าผู้ใช้อยู่ในโหมดฝากคำถาม → บันทึกคำถาม
            $leaveQuestionResult = $this->handleLeaveQuestionMode($facebookUserId, $messageText, $userProfile);
            if ($leaveQuestionResult) {
                return $leaveQuestionResult;
            }

            // ✅ ตรวจสอบคำสั่ง "ดูบัญชี" / "บัญชี" / "ธนาคาร" → แสดงบัญชีธนาคาร
            if ($this->isBankAccountRequest($messageText)) {
                return $this->handleBankAccountRequest($facebookUserId);
            }

            // ✅ ตรวจสอบคำสั่ง "เมนู" / "menu" / "help" → แสดงเมนูครบทุกบริการ
            if ($this->isMenuRequest($messageText)) {
                return $this->handleMenuRequest($facebookUserId);
            }

            // ✅ ตรวจสอบว่าผู้ใช้ตอบกลับปุ่ม "ฝากคำถามถึงแอดมิน" หรือ "ไม่ฝากคำถาม"
            $pendingSaveResult = $this->handlePendingSaveResponse($facebookUserId, $messageText, $userProfile);
            if ($pendingSaveResult) {
                return $pendingSaveResult;
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
                // ✅ ถ้าสถานะเป็น PAID (AI กำลังประมวลผลคำทำนาย) → แจ้งให้รอ
                // ไม่ว่าจะพิมพ์อะไรมา ห้าม cancel/สร้างใหม่ เพราะลูกค้าจ่ายเงินแล้ว
                if ($activeReading->conversation_status === FortuneReading::STATUS_PAID) {
                    Log::info('Fortune processMessage: ผู้ใช้ส่งข้อความระหว่าง AI กำลังประมวลผล (PAID)', [
                        'facebook_user_id' => $facebookUserId,
                        'reading_id' => $activeReading->id,
                        'text_preview' => mb_substr($messageText, 0, 30),
                    ]);

                    // ⏰ ถ้ารอเกิน 3 นาที → แสดงทางออก (เผื่อ queue ตาย)
                    // ใช้ addMinutes(3)->isPast() แทน diffInMinutes เพื่อให้เที่ยงตรง (Carbon cast float → int อาจคลาด)
                    $isStuck = $activeReading->updated_at->copy()->addMinutes(3)->isPast();
                    $waitedMinutes = (int) ceil(abs($activeReading->updated_at->diffInMinutes(now(), true)));

                    $message = "✅ รับชำระเงินเรียบร้อยแล้ว\n\n"
                        . "🔮 จันทรากำลังวิเคราะห์ดวงชะตาให้อย่างละเอียด\n"
                        . "ใช้เวลาประมาณ 2-3 นาที\n\n"
                        . "💡 รอสักครู่ คำทำนายจะส่งไปให้ทันทีเมื่อเสร็จ ✨";

                    if ($isStuck) {
                        $message = "⏳ คำทำนายใช้เวลานานกว่าปกติ (รอมา {$waitedMinutes} นาที)\n\n"
                            . "💡 ขออภัยในความไม่สะดวก — คุณสามารถ:\n"
                            . "• รอเพิ่มอีกสักครู่ (AI อาจทำงานเสร็จใน 1-2 นาที)\n"
                            . "• พิมพ์ 'คุยกับแม่หมอ' เพื่อให้ทีมงานดูแลโดยตรง\n"
                            . "• พิมพ์ 'เช็คสถานะ' เพื่อดูสถานะล่าสุด";
                    }

                    return [
                        'action' => 'processing',
                        'message' => $message,
                        'reading' => $activeReading,
                        'is_stuck' => $isStuck,
                    ];
                }

                // ✅ ตรวจสอบคำขอยกเลิกก่อน — ทุกสถานะ (ปิดทุก conversation ค้าง)
                if ($this->isCancelRequest($messageText)) {
                    $this->closeAllActiveConversations($facebookUserId);

                    return [
                        'action' => 'cancelled',
                        'message' => "ยกเลิกแล้ว หากต้องการดูดวงใหม่ พิมพ์ 'ดูดวง' ได้เลย 🔮",
                        'reading' => $activeReading,
                    ];
                }

                // ✅ FIX: ตรวจสอบคำขอดูดวงละเอียดก่อน — ทุกสถานะ (ยกเว้น PAID)
                // ป้องกันกรณีคลิกปุ่ม "ดูดวงละเอียด" ขณะอยู่ระหว่าง collecting_questions/tarot
                // → ข้อความ "ดูดวงละเอียด" จะถูกเข้าใจผิดเป็นคำถาม/trigger สุ่มไพ่
                // ❌ เดิม: ข้อความถูกส่งไป continueConversation → ค้าง/ผิด flow
                // ✅ ใหม่: ปิด conversation เก่า + เริ่ม deep reading flow ใหม่ทันที
                if ($this->isExplicitDeepReadingRequest($messageText)
                    && ! in_array($activeReading->conversation_status, [
                        FortuneReading::STATUS_PAID,
                        FortuneReading::STATUS_COLLECTING_BIRTHDATE,  // กำลังเก็บวันเกิดอยู่แล้ว (deep reading flow)
                    ])) {
                    Log::info('Fortune processMessage: คำขอดูดวงละเอียดขณะมี active conversation → ปิดเก่า + เริ่ม deep reading ใหม่', [
                        'facebook_user_id' => $facebookUserId,
                        'old_status' => $activeReading->conversation_status,
                        'old_reading_id' => $activeReading->id,
                    ]);

                    $this->closeAllActiveConversations($facebookUserId);

                    return $this->startDeepReadingFlow($facebookUserId, $userProfile);
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

                    // ✅ ถ้าเป็นคำขอดูดวงชัดเจน (เช่น "ดูดวง", "ทำนาย") → fortune flow เลย
                    if ($this->isGenericFortuneRequest($messageText)) {
                        return $this->askForQuestionBeforeReading($facebookUserId, $messageText, $userProfile);
                    }

                    // ✅ ตรวจสอบ keywords จากฐานข้อมูล (auto-reply อัจฉริยะ)
                    $matchedKeyword = $this->checkDatabaseKeywords($messageText);
                    if ($matchedKeyword) {
                        Log::info('Fortune: DB keyword matched (basic_done fallback)', [
                            'user_id' => $facebookUserId,
                            'keyword' => $matchedKeyword->keyword,
                        ]);

                        return $this->handleKeywordMatchResponse($matchedKeyword);
                    }

                    // ✅ AI Chat ทั่วไป — สนทนาเป็นธรรมชาติ + ชวนดูดวง (ไม่ใช้โควต้าฟรี)
                    // ต้องให้ AI Chat จัดการก่อน เพราะ containsFortuneKeyword จับคำกว้างเกิน
                    // (เช่น "งาน", "เงิน", "แฟน") ทำให้ข้อความทั่วไปถูก trigger fortune flow
                    $aiChatResult = $this->tryAIChatResponse($facebookUserId, $messageText, $userProfile);
                    if ($aiChatResult) {
                        return $aiChatResult;
                    }

                    // ✅ ถ้ามีคำเกี่ยวกับดูดวง → ถามยืนยันก่อน (ไม่สร้าง FortuneReading จนกว่าจะยืนยัน)
                    // ⚠️ ใช้ askFortuneConfirmation แทน askForQuestionBeforeReading
                    // เพื่อไม่ให้ใช้โควต้าฟรีจากคำพูดทั่วไปที่มีคำเกี่ยวข้อง
                    if ($this->containsFortuneKeyword($messageText)) {
                        return $this->askFortuneConfirmation($facebookUserId, $messageText, $userProfile);
                    }

                    // ✅ FIX: ถ้าไม่ match อะไรเลยใน basic_done → ตอบเป็น AI Chat fallback
                    // ❌ เดิม: เรียก askFortuneConfirmation → วนลูป confirmation ซ้ำไม่จบ
                    // ✅ ใหม่: ตอบข้อความทั่วไป + ชวนดูดวง (ไม่สร้าง FortuneReading)
                    return [
                        'action' => 'ai_chat_response',
                        'message' => "✨ หมอจันทรารับฟังอยู่\n\nหากต้องการดูดวง พิมพ์ \"ดูดวง\" หรือกดปุ่ม 🔮 ด้านล่างได้เลย",
                        'reading' => null,
                    ];
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

            // ✅ ถ้าเป็นคำขอดูดวงชัดเจน (เช่น "ดูดวง", "ทำนาย", "หมอดู") → ไป fortune flow เลย
            if ($this->isGenericFortuneRequest($messageText)) {
                if (! $this->canMakeAICall($facebookUserId)) {
                    return [
                        'action' => 'ai_limit',
                        'message' => $this->getAILimitMessage(),
                        'reading' => null,
                    ];
                }

                return $this->askForQuestionBeforeReading($facebookUserId, $messageText, $userProfile);
            }

            // ✅ ตรวจสอบ keywords จากฐานข้อมูล (auto-reply อัจฉริยะ)
            // ตอบ small talk, FAQ, อารมณ์ ฯลฯ โดยไม่สร้าง FortuneReading
            $matchedKeyword = $this->checkDatabaseKeywords($messageText);
            if ($matchedKeyword) {
                Log::info('Fortune: DB keyword matched (no active conversation)', [
                    'user_id' => $facebookUserId,
                    'keyword' => $matchedKeyword->keyword,
                    'category' => $matchedKeyword->category,
                ]);

                return $this->handleKeywordMatchResponse($matchedKeyword);
            }

            // ✅ AI Chat ทั่วไป — สนทนาเป็นธรรมชาติ + ชวนดูดวง
            // คำเกี่ยวกับดวง (เช่น "ความรัก", "การเงิน", "ปีนี้") จะถูกจัดการโดย AI Chat
            // ไม่สร้าง FortuneReading → ไม่ใช้สิทธิ์ฟรี
            // ✅ ผู้ใช้ต้องพิมพ์ "ดูดวง" หรือกดปุ่มดูดวงฟรีเท่านั้นจึงจะเริ่มกระบวนการทำนาย
            $aiChatResult = $this->tryAIChatResponse($facebookUserId, $messageText, $userProfile);
            if ($aiChatResult) {
                return $aiChatResult;
            }

            // ถ้า AI Chat ไม่ตอบ + มีคำเกี่ยวกับดวง → ถามยืนยันก่อนเริ่มทำนาย (ไม่สร้าง Reading ยัง)
            if ($this->containsFortuneKeyword($messageText)) {
                return $this->askFortuneConfirmation($facebookUserId, $messageText, $userProfile);
            }

            // ✅ FIX: ถ้าไม่ match อะไรเลย → ตอบข้อความทั่วไป + ชวนดูดวง
            // ❌ เดิม: เรียก askFortuneConfirmation → วนลูป confirmation ซ้ำไม่จบ
            // ✅ ใหม่: ตอบเป็นมิตร + แนะนำให้พิมพ์ "ดูดวง" (ไม่สร้าง FortuneReading)
            return [
                'action' => 'ai_chat_response',
                'message' => "🔮 สวัสดีค่ะ ยินดีต้อนรับ ✨\n\nหมอจันทราพร้อมดูดวงให้คุณค่ะ\n\n💫 พิมพ์ \"ดูดวง\" เพื่อเริ่มดูดวงฟรี\n🔮 หรือพิมพ์ \"ดูดวงความรัก\" \"ดูดวงการเงิน\" ก็ได้ค่ะ\n\nหรือจะคุยเรื่องอื่นก็ได้นะคะ 😊",
                'reading' => null,
            ];

            } finally {
                // ปล่อย mutex lock เสมอ ไม่ว่า return หรือ exception
                // ใช้ Cache::forget แทน $lock->release() เพราะใช้ Cache::put mutex
                if ($lockAcquired) {
                    Cache::forget($lockKey);
                }
            }

        } catch (\Exception $e) {
            // ปล่อย mutex lock กรณี exception หลุดจาก try ด้านใน
            if ($lockAcquired ?? false) {
                try { Cache::forget($lockKey); } catch (\Exception $lockErr) { /* ignore */ }
            }
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
                            'message' => "ขออภัย ระบบขัดข้องชั่วคราว 🙏\n\n"
                                ."ตอนนี้หมอจันทรารับคำถามแล้ว {$collected} ข้อ\n"
                                ."กรุณาพิมพ์คำถามอีก {$remaining} ข้อใหม่อีกครั้ง",
                            'reading' => $activeReading,
                        ];
                    }

                    // ถ้าอยู่ระหว่างเก็บวันเกิด → แจ้งให้พิมพ์วันเกิดอีกครั้ง
                    if ($status === FortuneReading::STATUS_COLLECTING_BIRTHDATE) {
                        return [
                            'action' => 'retry_birthdate',
                            'message' => "ขออภัย ระบบขัดข้องชั่วคราว 🙏\n\nกรุณาพิมพ์วันเกิดอีกครั้ง\n📅 เช่น 15/08/1990",
                            'reading' => $activeReading,
                        ];
                    }

                    // ถ้ารอชำระเงิน → แจ้งยอดชำระ
                    if ($status === FortuneReading::STATUS_PENDING_PAYMENT) {
                        return $this->handlePendingPayment($activeReading, $messageText);
                    }

                    // ✅ FIX: ถ้าอยู่ระหว่างสุ่มไพ่ → แจ้งให้กดปุ่มสุ่มไพ่
                    if ($status === FortuneReading::STATUS_COLLECTING_TAROT) {
                        return [
                            'action' => 'draw_tarot_card',
                            'message' => "🃏 ขออภัย ระบบขัดข้องชั่วคราว 🙏\n\nกรุณากด 'สุ่มไพ่ยิปซี' อีกครั้ง ✨",
                            'reading' => $activeReading,
                        ];
                    }

                    // ✅ FIX: ถ้า PAID → แจ้งกำลังประมวลผล
                    if ($status === FortuneReading::STATUS_PAID) {
                        return [
                            'action' => 'processing',
                            'message' => "🔮 กำลังวิเคราะห์ดวงชะตาให้อยู่\n\nใช้เวลาประมาณ 1-3 นาที กรุณารอสักครู่ ✨",
                            'reading' => $activeReading,
                        ];
                    }

                    // ✅ FIX: ถ้า AWAITING_CONFIRMATION → แจ้งให้ยืนยัน
                    if ($status === FortuneReading::STATUS_AWAITING_CONFIRMATION) {
                        return [
                            'action' => 'awaiting_confirmation',
                            'message' => "🔮 ขออภัย ระบบขัดข้องชั่วคราว 🙏\n\nพิมพ์ 'ดูดวง' เพื่อเริ่มใหม่ได้เลย ✨",
                            'reading' => $activeReading,
                        ];
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
        return "🔮 สวัสดี คุณ{$name} ✨\n\n".
               "เพจดูดวงหมอจันทรายินดีต้อนรับ\n\n".
               "บอกหมอจันทราได้เลยว่าอยากรู้เรื่องอะไร:\n".
               "💕 ความรัก - เนื้อคู่ คู่ครอง\n".
               "💼 การงาน - เปลี่ยนงาน เลื่อนตำแหน่ง\n".
               "💰 การเงิน - รายได้ การลงทุน\n".
               "🏥 สุขภาพ - ระวังอะไรบ้าง\n\n".
               'พิมพ์มาได้เลย 🔮';
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
                return "🔮 สวัสดี คุณ{$name} ✨\n\n".
                       "เพจดูดวงหมอจันทรายินดีต้อนรับ พร้อมช่วยดูดวงให้\n\n".
                       "ไม่ว่าจะเรื่องความรัก 💕 การงาน 💼 การเงิน 💰 หรือสุขภาพ 🏥\n".
                       "ถามมาได้เลย แล้วอย่าลืมบอกวันเดือนปีเกิดให้หมอจันทราด้วย จะได้ทำนายได้แม่นยำยิ่งขึ้น 🎂\n\n".
                       'ฝากส่งต่อให้เพื่อนๆ มาลองดูดวงด้วยกัน 🔮✨';
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
        return "🔮 สวัสดี คุณ{$name}\n\n".
               "เพจดูดวงหมอจันทรายินดีต้อนรับ หมอจันทราพร้อมดูดวงให้ ✨\n\n".
               "ลองบอกหมอจันทราว่าอยากรู้เรื่องอะไร:\n".
               "💕 ความรัก - เนื้อคู่ คู่ครอง\n".
               "💼 การงาน - เปลี่ยนงาน เลื่อนตำแหน่ง\n".
               "💰 การเงิน - รายได้ การลงทุน\n".
               "🏥 สุขภาพ - ระวังอะไรบ้าง\n\n".
               'บอกวันเดือนปีเกิดมาด้วย จะได้ทำนายได้แม่นยำยิ่งขึ้น 🎂✨';
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
                "🔮 คุณ{$name} หมอจันทราเห็นว่าช่วงนี้ดวงความรักกำลังมีการเปลี่ยนแปลง\n\n".
                "💕 สำหรับคนมีคู่: ช่วงนี้ควรให้เวลากับคนรักมากขึ้น มีเรื่องดีๆ รออยู่ข้างหน้า\n".
                "💕 สำหรับคนโสด: ดวงเปิดรับคนใหม่ ลองเปิดใจดู\n\n".
                "📅 ช่วงเวลาที่ดี: 2-3 เดือนข้างหน้า\n".
                "🎨 สีมงคล: ชมพู, แดง\n\n".
                'ถ้าบอกวันเดือนปีเกิดให้หมอจันทรา จะได้ทำนายได้แม่นยำยิ่งขึ้น 🎂',

                "🔮 คุณ{$name} หมอจันทราขอบอกตรงๆ เลย\n\n".
                "💕 ดวงความรักของคุณช่วงนี้ มีทั้งสิ่งดีและสิ่งที่ต้องระวัง\n".
                "✅ เรื่องดี: จะมีคนเข้ามาให้ความสนใจ หรือคนรักจะแสดงความรักมากขึ้น\n".
                "⚠️ ระวัง: อย่าใจร้อน อย่าตัดสินใจเรื่องใหญ่เรื่องความรักเร็วเกินไป\n\n".
                "🔢 เลขมงคล: 9, 19\n\n".
                'อยากรู้ละเอียดกว่านี้ บอกวันเกิดมาได้เลย 🎂✨',
            ],
            'work' => [
                "🔮 คุณ{$name} หมอจันทราเห็นดวงการงานช่วงนี้\n\n".
                "💼 ดวงการงานกำลังอยู่ในช่วงที่ต้องอดทนและพัฒนาตัวเอง\n".
                "✅ โอกาสใหม่ๆ จะเริ่มเข้ามาในช่วง 1-3 เดือนข้างหน้า\n".
                "✅ คนที่คิดจะเปลี่ยนงาน ช่วงนี้เป็นจังหวะที่ดี\n".
                "⚠️ ระวังเรื่องเพื่อนร่วมงาน อย่าไว้ใจคนง่ายเกินไป\n\n".
                "📅 วันมงคล: วันพฤหัสบดี\n".
                "🎨 สีมงคล: เหลือง, ส้ม\n\n".
                'บอกวันเกิดมาด้วย จะได้วิเคราะห์ดวงได้ลึกขึ้น 🎂',

                "🔮 คุณ{$name} หมอจันทราขอทำนายดวงการงานให้\n\n".
                "💼 ช่วงนี้เป็นจังหวะที่ดีสำหรับการเริ่มต้นสิ่งใหม่\n".
                "✅ มีเกณฑ์ได้รับข่าวดีเรื่องงาน\n".
                "✅ คนทำธุรกิจจะเริ่มเห็นผลลัพธ์\n".
                "⚠️ แต่อย่าประมาท ทำทุกอย่างให้รอบคอบ\n\n".
                "🔢 เลขมงคล: 5, 14\n\n".
                'ถ้าอยากรู้ละเอียดกว่านี้ บอกวันเดือนปีเกิดมา 🎂✨',
            ],
            'money' => [
                "🔮 คุณ{$name} หมอจันทราเห็นดวงการเงิน\n\n".
                "💰 ดวงการเงินช่วงนี้: ต้องระมัดระวังเรื่องรายจ่าย\n".
                "✅ มีเกณฑ์ได้เงินก้อน หรือรายได้เพิ่มในช่วง 2-4 เดือนข้างหน้า\n".
                "✅ เหมาะกับการออมเงินและวางแผนการเงิน\n".
                "⚠️ ระวังการลงทุนที่เสี่ยงสูง ช่วงนี้ยังไม่ใช่จังหวะ\n\n".
                "🎨 สีมงคลการเงิน: เขียว, ทอง\n".
                "📅 วันมงคล: วันพุธ\n\n".
                'บอกวันเกิดมาด้วย จะได้ทำนายเรื่องการเงินได้แม่นขึ้น 🎂',

                "🔮 คุณ{$name} หมอจันทราขอบอกเรื่องการเงิน\n\n".
                "💰 ดวงการเงินของคุณกำลังจะดีขึ้น\n".
                "✅ มีโอกาสได้รับเงินจากทางที่ไม่คาดคิด\n".
                "✅ คนที่ค้าขายจะเริ่มมีลูกค้าเพิ่มขึ้น\n".
                "⚠️ แต่ระวังเรื่องการใช้จ่ายฟุ่มเฟือย\n\n".
                "🔢 เลขมงคลการเงิน: 3, 8, 24\n\n".
                'อยากรู้ละเอียดกว่านี้ บอกวันเดือนปีเกิดมา 🎂✨',
            ],
            'health' => [
                "🔮 คุณ{$name} หมอจันทราเห็นดวงสุขภาพ\n\n".
                "🏥 ช่วงนี้ต้องดูแลสุขภาพให้ดี\n".
                "✅ ออกกำลังกายเบาๆ สม่ำเสมอ จะช่วยได้มาก\n".
                "✅ พักผ่อนให้เพียงพอ อย่าหักโหมมากเกินไป\n".
                "⚠️ ระวังเรื่องการเดินทาง และอาหารการกิน\n\n".
                "📅 ช่วงที่ต้องระวังเป็นพิเศษ: 2-3 สัปดาห์ข้างหน้า\n".
                "🎨 สีมงคล: เขียว, ขาว\n\n".
                'บอกวันเกิดมาด้วย จะได้วิเคราะห์ดวงสุขภาพได้ละเอียดขึ้น 🎂',
            ],
            'general' => [
                "🔮 คุณ{$name} หมอจันทรายินดีดูดวงให้ ✨\n\n".
                "⭐ ดวงโดยรวมช่วงนี้: กำลังอยู่ในช่วงเปลี่ยนผ่าน มีทั้งเรื่องดีและสิ่งที่ต้องระวัง\n\n".
                "✅ เรื่องดี: จะมีโอกาสใหม่ๆ เข้ามา ทั้งเรื่องงานและเรื่องส่วนตัว\n".
                "✅ การเงินมีเกณฑ์ดีขึ้น\n".
                "⚠️ ระวัง: เรื่องสุขภาพ อย่าประมาท ดูแลตัวเองให้ดี\n\n".
                "🎨 สีมงคล: น้ำเงิน, ทอง\n".
                "🔢 เลขมงคล: 7, 16\n".
                "📅 วันมงคล: วันพฤหัสบดี\n\n".
                "บอกวันเดือนปีเกิดให้หมอจันทรา จะได้ทำนายได้แม่นยำยิ่งขึ้น 🎂\n".
                'ถ้าคำทำนายถูกใจ ฝากส่งต่อให้เพื่อนๆ มาลองดูดวงด้วยกัน 🔮✨',

                "🔮 คุณ{$name} หมอจันทราขอทำนายดวงให้\n\n".
                "⭐ ภาพรวมดวงชะตา: กำลังเข้าสู่ช่วงที่ดี\n\n".
                "💕 ความรัก: มีเกณฑ์ได้พบคนถูกใจ หรือความสัมพันธ์จะแน่นแฟ้นขึ้น\n".
                "💼 การงาน: มีความก้าวหน้า อาจได้รับข้อเสนอใหม่\n".
                "💰 การเงิน: ระมัดระวังเรื่องรายจ่าย แต่มีเกณฑ์ได้เงินเข้ามา\n".
                "🏥 สุขภาพ: ดูแลตัวเองให้ดี พักผ่อนให้เพียงพอ\n\n".
                "🎨 สีมงคล: ม่วง, ครีม\n".
                "🔢 เลขมงคล: 2, 11, 29\n\n".
                'อยากรู้ละเอียดกว่านี้ บอกวันเดือนปีเกิดมา 🎂✨',
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
        // ✅ ดึงชื่อผู้ใช้จาก reading ล่าสุด (ถ้าเคยใช้งาน → มีชื่อเก็บไว้)
        $latestReading = FortuneReading::where('facebook_user_id', $facebookUserId)
            ->whereNotNull('facebook_user_name')
            ->latest()
            ->first();
        $userName = $latestReading?->facebook_user_name ?? 'คุณ';

        // ✅ ดึงข้อมูล wallet + รายได้ค่าคอม
        $walletBalance = 0;
        $totalCommission = 0;
        $user = \App\Models\User::where('line_user_id', $facebookUserId)->first();
        if ($user && $user->wallet) {
            $walletBalance = $user->wallet->balance ?? 0;
            $totalCommission = \App\Models\WalletTransaction::where('wallet_id', $user->wallet->id)
                ->where('type', 'credit')
                ->where('description', 'LIKE', '%คอมมิชชั่น%')
                ->sum('amount');
        }

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

        $message = "🔮 *สิทธิ์ดูดวงของคุณ{$userName}วันนี้*\n";
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
            $message .= "💡 พิมพ์คำถามมาได้เลย\n";
            $message .= "ไม่ว่าจะเรื่องความรัก การงาน การเงิน สุขภาพ\n";
            $message .= 'หมอจันทราพร้อมทำนายให้ 🔮✨';
        } else {
            $message .= "⏰ สิทธิ์ฟรีวันนี้หมดแล้ว\n";
            if ($this->settings->isDeepReadingEnabled()) {
                $message .= "กลับมาใหม่พรุ่งนี้ หรือ\n\n";
                $message .= "💎 *ดูดวงละเอียด เริ่มต้น {$price} บาท*\n";
                $message .= "📌 ถามได้ 2 คำถาม วิเคราะห์จากวันเกิด\n";
                $message .= "📌 พร้อมสีมงคล เลขมงคล ฤกษ์ดี\n\n";
                $message .= 'กดปุ่มด้านล่างเพื่อเริ่ม 👇';
            } else {
                $message .= 'กลับมาใหม่พรุ่งนี้ได้ 🙏';
            }
        }

        return [
            'action' => 'check_remaining',
            'message' => $message,
            'reading' => null,
            'user_name' => $userName,
            'remaining' => $remaining,
            'used' => $usedToday,
            'total' => $maxFreeReadings,
            'is_unlimited' => $userCredit && $userCredit->isCurrentlyUnlimited(),
            'wallet_balance' => $walletBalance,
            'total_commission' => $totalCommission,
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
            'message' => "ได้เลย! เมื่อพร้อมดู พิมพ์ 'ดูคำทำนาย' ได้ทุกเมื่อ 🔮",
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
            // ✅ เพิ่ม: รองรับปุ่ม quick reply "อ่านคำทำนาย" และคำขอ "อ่านเลย"
            'อ่านคำทำนาย', 'อ่านเลย', 'อ่านผล', 'ขออ่าน',
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
        // ✅ ลำดับที่ 1: เช็คคำทำนายที่ชำระเงินแล้วก่อน (ให้ความสำคัญกับ paid reading)
        $lastPaidReading = FortuneReading::where('facebook_user_id', $facebookUserId)
            ->where('is_paid', true)
            ->latest()
            ->first();

        if ($lastPaidReading) {
            // ชำระเงินแล้ว + มี deep_response → แสดงคำทำนายเชิงลึก
            if (! empty($lastPaidReading->deep_response)) {
                $name = $lastPaidReading->facebook_user_name ?? 'คุณ';

                // ✅ FIX: ตั้ง flag ว่าส่งแล้ว เพื่อป้องกันแจ้งเตือนซ้ำ
                $lastPaidReading->setConversationState('reading_sent_directly', true);
                $lastPaidReading->setConversationState('reading_ready_sent', true);
                $lastPaidReading->setConversationState('reading_ready_sent_at', now()->toIso8601String());

                $message = "🌟 *คำทำนายเชิงลึกล่าสุดของคุณ{$name}*\n";
                $message .= '📋 เลขที่บิล: '.($lastPaidReading->bill_reference ?? '-')."\n";
                $message .= '📅 วันที่: '.$lastPaidReading->created_at->format('d/m/Y H:i')."\n";
                $message .= "═══════════════════════\n\n";
                $message .= $lastPaidReading->deep_response;

                return [
                    'action' => 'view_reading_deep',
                    'message' => $message,
                    'reading' => $lastPaidReading,
                    'chart_image_url' => $lastPaidReading->reading_image_url,
                ];
            }

            // ชำระเงินแล้ว + ยังไม่มี deep_response → กำลังประมวลผล
            return [
                'action' => 'view_reading_processing',
                'message' => "🔮 คำทำนายเชิงลึกกำลังประมวลผล\n"
                    ."📋 เลขที่บิล: {$lastPaidReading->bill_reference}\n\n"
                    ."⏳ ระบบ AI กำลังสร้างคำทำนายให้อยู่\n"
                    ."ใช้เวลาประมาณ 1-2 นาที\n\n"
                    ."💡 พิมพ์ 'ดูผล' อีกครั้งเพื่อเช็คสถานะได้\n"
                    .'หรือทักแชทแอดมินหากรอนานเกิน 5 นาที 🙏',
                'reading' => $lastPaidReading,
            ];
        }

        // ✅ ลำดับที่ 2: ไม่มี paid reading → ดึงคำทำนายล่าสุด (ฟรี)
        $lastReading = FortuneReading::where('facebook_user_id', $facebookUserId)
            ->where(function ($q) {
                $q->whereNotNull('basic_response')
                    ->orWhereNotNull('deep_response');
            })
            ->latest()
            ->first();

        if (! $lastReading) {
            return [
                'action' => 'view_reading_empty',
                'message' => "🔮 ยังไม่มีคำทำนาย\n\n"
                    ."พิมพ์คำถามมาได้เลย\n"
                    .'หมอจันทราพร้อมดูดวงให้ ✨',
                'reading' => null,
            ];
        }

        // กรณี 1: ชำระเงินแล้ว + มี deep_response
        if ($lastReading->is_paid && ! empty($lastReading->deep_response)) {
            $name = $lastReading->facebook_user_name ?? 'คุณ';

            // ✅ FIX: ตั้ง flag ว่าส่งแล้ว เพื่อป้องกันแจ้งเตือนซ้ำ
            $lastReading->setConversationState('reading_sent_directly', true);
            $lastReading->setConversationState('reading_ready_sent', true);
            $lastReading->setConversationState('reading_ready_sent_at', now()->toIso8601String());

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
                'message' => "🔮 คำทำนายเชิงลึกกำลังประมวลผล\n"
                    ."📋 เลขที่บิล: {$lastReading->bill_reference}\n\n"
                    ."⏳ ระบบ AI กำลังสร้างคำทำนายให้อยู่\n"
                    ."ใช้เวลาประมาณ 1-2 นาที\n\n"
                    ."💡 พิมพ์ 'ดูผล' อีกครั้งเพื่อเช็คสถานะได้\n"
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
                $message .= "พิมพ์ 'ดูดวงละเอียด' ได้เลย ✨";
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
            'message' => "🔮 ยังไม่มีคำทำนาย พิมพ์คำถามมาได้เลย ✨",
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
                FortuneReading::STATUS_COLLECTING_TAROT,
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
            'platform' => $this->currentPlatform,
            'platform_user_id' => $facebookUserId,
        ]);

        // เก็บข้อความต้นฉบับไว้ใน state เพื่อส่งให้ AI ตอนยืนยัน
        $reading->setConversationState('original_message', $messageText);

        // สร้างข้อความ — conditional ตามว่าเปิดบริการฟรีหรือไม่
        $message = "🔮 สวัสดี คุณ{$name} ✨\n\n";
        $message .= "เพจดูดวงหมอจันทรายินดีต้อนรับ\n\n";

        $freeEnabled = $this->settings->isFreeReadingEnabled();

        // ถ้าปิดบริการฟรี → ชี้ไปที่ดูดวงเสียค่าครูเลย (ไม่พูดถึงฟรี)
        if (! $freeEnabled) {
            $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

            if ($this->settings->isDeepReadingEnabled()) {
                $price = $this->getDeepReadingPrice();
                $message .= "💎 *ดูดวงโดยแม่หมอจันทรา — ค่าครู {$price} บาท*\n";
                $message .= "📌 ถามได้ 2 คำถาม วิเคราะห์จากวันเกิด\n";
                $message .= "📌 พร้อมสีมงคล เลขมงคล ฤกษ์ดี\n\n";
                $message .= 'กดปุ่มด้านล่างเพื่อเริ่ม 👇';
            } else {
                $message .= '🙏 ขณะนี้บริการปิดชั่วคราว';
            }

            return [
                'action' => 'awaiting_confirmation',
                'message' => $message,
                'reading' => $reading,
                'show_quick_replies' => false,
                'remaining' => 0,
            ];
        }

        // เปิดบริการฟรี — แจ้งสิทธิ์
        if ($userCredit && $userCredit->isCurrentlyUnlimited()) {
            $message .= "🌟 คุณมีสิทธิ์ดูดวงไม่จำกัด! (โปรโมชั่นพิเศษ)\n\n";
        } elseif ($remaining >= 99) {
            $message .= "🌟 คุณมีสิทธิ์ดูดวงไม่จำกัด!\n\n";
        } elseif ($remaining > 0) {
            $message .= "📊 วันนี้คุณมีสิทธิ์ดูดวง {$remaining} ครั้ง\n\n";
        } else {
            $message .= "⏰ สิทธิ์วันนี้หมดแล้ว\n\n";
        }

        if ($remaining > 0) {
            $message .= "💫 จะให้หมอจันทราดูดวงให้ไหม?\n";
            $message .= "ไม่ว่าจะเรื่อง ความรัก 💕 การงาน 💼 การเงิน 💰 สุขภาพ 🏥\n\n";
            $message .= 'กดเลือกด้านล่าง หรือพิมพ์คำถามมาได้เลย 👇';
        } else {
            // สิทธิ์ฟรีหมด → ปิด conversation แล้วแนะนำดูดวงเสียค่าครู
            $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

            if ($this->settings->isDeepReadingEnabled()) {
                $price = $this->getDeepReadingPrice();
                $message .= "กลับมาใหม่พรุ่งนี้ได้ หรือ\n\n";
                $message .= "💎 *ดูดวงโดยแม่หมอจันทรา — ค่าครู {$price} บาท*\n";
                $message .= "📌 ถามได้ 2 คำถาม วิเคราะห์จากวันเกิด\n";
                $message .= "📌 พร้อมสีมงคล เลขมงคล ฤกษ์ดี\n\n";
                $message .= 'กดปุ่มด้านล่างเพื่อเริ่ม 👇';
            } else {
                $message .= 'กลับมาใหม่พรุ่งนี้ได้ 🙏';
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
                'message' => "🔮 ไม่เป็นไร คุณ{$name}\n\n".
                             "เมื่อไหร่อยากดูดวง ทักมาหาหมอจันทราได้ตลอด ✨\n".
                             'ขอให้โชคดีค่ะ 🙏',
                'reading' => $reading,
            ];
        }

        // ✅ ถ้าเป็นคำขอดูดวงเชิงลึก → ปิด conversation เก่า + เข้า deep reading flow เลย
        if ($this->isExplicitDeepReadingRequest($messageText)) {
            $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

            return $this->startDeepReadingFlow($facebookUserId, $userProfile);
        }

        // ถ้ายืนยัน หรือพิมพ์ข้อความอื่นเข้ามา → เริ่มทำนายเลย
        // ดึงข้อความต้นฉบับจาก state (ถ้ามี) หรือใช้ข้อความใหม่
        $originalMessage = $reading->getConversationState('original_message', $messageText);
        $awaitingType = $reading->getConversationState('awaiting_type');

        // ถ้าเป็น "รอคำถาม" (awaiting_type=question) → ใช้ข้อความใหม่เสมอ (เพราะผู้ใช้เลือกหัวข้อ/พิมพ์คำถาม)
        // ยกเว้นตอบสั้นมาก "ดู", "เอา", "ใช่" → ถือเป็นขอดูดวงทั่วไป
        if ($awaitingType === 'question') {
            // ✅ FIX: ถ้าผู้ใช้กด "ดูดวง" (คำเดียว ไม่มีหัวข้อ) ซ้ำจาก Rich Menu
            // → ถือว่าอยากดูดวงรวมทุกด้าน → ทำนายเลย (ไม่วนลูป)
            $textCleanForCheck = mb_strtolower(trim($messageText));
            $pureGenericWords = ['ดูดวง', 'ทำนาย', 'หมอดู', 'ดวง', 'ไพ่', 'ทาโรต์'];
            // ลบคำลงท้ายออก
            $normalizedForCheck = preg_replace('/\s*(ค่ะ|ครับ|คะ|จ้า|จ้ะ|จ๊ะ|นะ|นะคะ|นะครับ|หน่อย|ด้วย|ที|สิ|เลย)\s*$/u', '', $textCleanForCheck);

            $isPureGeneric = in_array($normalizedForCheck, $pureGenericWords);

            if ($isPureGeneric) {
                // คำเดี่ยว "ดูดวง" → ปิด reading เก่า → ทำนายรวมทุกด้านเลย
                $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

                if (! $this->canMakeAICall($facebookUserId)) {
                    return [
                        'action' => 'ai_limit',
                        'message' => $this->getAILimitMessage(),
                        'reading' => null,
                    ];
                }

                return $this->startNewConversation($facebookUserId, 'ดูดวงรวมทุกด้าน', $userProfile);
            }

            // ✅ "ดูดวงความรัก", "ดูดวงการเงิน" → มีหัวข้อแนบ → ใช้เป็นคำถามเลย
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
            // ✅ FIX V3: flow ยืนยันดูดวง (awaiting_type != 'question')
            // ❌ เดิม: ถามคำถามอีกรอบ → ผู้ใช้ต้องยืนยัน 2 ครั้ง → วนลูป
            // ✅ ใหม่: ยืนยันแล้ว → เริ่มทำนายเลย (ลดขั้นตอนจาก 3 → 1)
            $isSimpleConfirm = $this->isSimpleConfirmResponse($messageText);

            // ปิด reading ที่รอยืนยัน
            $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

            // ✅ ตรวจสอบ limit ก่อนเริ่มทำนาย
            if (! $this->canMakeAICall($facebookUserId)) {
                return [
                    'action' => 'ai_limit',
                    'message' => $this->getAILimitMessage(),
                    'reading' => null,
                ];
            }

            if ($isSimpleConfirm) {
                // ตอบ "ใช่", "ดู", "เอา" → ใช้ข้อความต้นฉบับเป็นคำถาม / ถ้าไม่มี → ใช้ "ดูดวงรวมทุกด้าน"
                $questionText = ($originalMessage && ! $this->isGenericFortuneRequest($originalMessage))
                    ? $originalMessage
                    : 'ดูดวงรวมทุกด้าน';

                return $this->startNewConversation($facebookUserId, $questionText, $userProfile);
            }

            if ($this->isGenericFortuneRequest($messageText)) {
                // พิมพ์ "ดูดวง" ซ้ำ → ทำนายรวมทุกด้านเลย (ไม่ถามซ้ำ)
                return $this->startNewConversation($facebookUserId, 'ดูดวงรวมทุกด้าน', $userProfile);
            }

            // พิมพ์ข้อความใหม่ (เช่น "ดวงความรัก") → ใช้เป็นคำถามเลย ทำนายทันที
            return $this->startNewConversation($facebookUserId, $messageText, $userProfile);
        }

        // === ถึงจุดนี้ = awaiting_type === 'question' เท่านั้น ===

        // ปิด reading ที่รอคำถามนี้ (จะสร้างใหม่ใน startNewConversation)
        $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

        // ตรวจสอบ limit อีกครั้งก่อนส่งให้ AI
        if (! $this->canMakeAICall($facebookUserId)) {
            return [
                'action' => 'ai_limit',
                'message' => $this->getAILimitMessage(),
                'reading' => null,
            ];
        }

        // เริ่มทำนายจริง (เฉพาะ awaiting_type=question ที่ผู้ใช้ป้อนคำถามแล้ว)
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
     * ตรวจสอบว่าเป็นคำขอดูดวงที่ชัดเจน (Explicit Fortune Request)
     *
     * ✅ ต้องมีคำนำหน้าชัดเจน: "ดูดวง", "ทำนาย", "หมอดู" ฯลฯ
     * ✅ จับทั้งคำเดี่ยว ("ดูดวง") และมีหัวข้อตาม ("ดูดวงความรัก", "ทำนายการเงิน")
     * ❌ ไม่จับคำถามที่มีแค่ keyword เรื่อง ("ความรักปีนี้", "การเงินจะดีไหม")
     *
     * เหตุผล: ป้องกันข้อความพูดคุยทั่วไปที่มีคำเกี่ยวกับดวง
     * ถูกนับเป็นคำถามดูดวง → ใช้สิทธิ์ฟรีโดยไม่ตั้งใจ
     *
     * @param  string  $text  ข้อความจาก user
     * @return bool true = เป็นคำขอดูดวงชัดเจน
     */
    protected function isGenericFortuneRequest(string $text): bool
    {
        $textClean = mb_strtolower(trim($text));

        // ลบคำลงท้าย (ค่ะ, ครับ, นะ, หน่อย, จ้า ฯลฯ) เพื่อเปรียบเทียบ
        $textNormalized = preg_replace('/\s*(ค่ะ|ครับ|คะ|จ้า|จ้ะ|จ๊ะ|นะ|นะคะ|นะครับ|หน่อย|ด้วย|ที|สิ|เลย)\s*$/u', '', $textClean);

        // ✅ คำนำหน้าที่ชี้เจตนาชัดว่าอยากดูดวง
        // จับทั้งคำเดี่ยว ("ดูดวง") และ "ดูดวง + อะไรก็ตาม" ("ดูดวงความรัก")
        $explicitPrefixes = [
            'ดูดวง', 'ทำนาย', 'หมอดู', 'อยากดูดวง', 'ขอดูดวง',
            'ทำนายดวง', 'ดูดวงให้', 'ทำนายให้', 'ช่วยดูดวง', 'ช่วยทำนาย',
        ];

        foreach ($explicitPrefixes as $prefix) {
            $prefixLower = mb_strtolower($prefix);
            // exact match ("ดูดวง") หรือ starts with ("ดูดวงความรัก")
            if ($textClean === $prefixLower
                || $textNormalized === $prefixLower
                || str_starts_with($textClean, $prefixLower)
                || str_starts_with($textNormalized, $prefixLower)
            ) {
                return true;
            }
        }

        // ✅ คำที่เป็นคำเดียวสั้นๆ เกี่ยวกับดวง → ถือเป็นคำขอดูดวง
        if (mb_strlen($textNormalized) <= 15) {
            $shortExactWords = ['ดวง', 'ไพ่', 'ทาโรต์', 'ดูไพ่', 'เปิดไพ่'];
            foreach ($shortExactWords as $word) {
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
            'platform' => $this->currentPlatform,
            'platform_user_id' => $facebookUserId,
        ]);

        // เก็บว่าเป็น "รอคำถาม" (ไม่ใช่รอยืนยัน)
        $reading->setConversationState('awaiting_type', 'question');
        $reading->setConversationState('original_message', $messageText);

        // V3: ถ้ามีคำถามเฉพาะแล้ว → แจ้งว่ารับคำถามแล้ว + ถามยืนยัน
        if ($hasSpecificQuestion) {
            $message = "🔮 คุณ{$name} ถามว่า: \"{$messageText}\"\n\n";
            $message .= "✨ หมอจันทราพร้อมทำนายให้แล้ว\n\n";

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
        $message = "🔮 สวัสดี คุณ{$name}\n\n";
        $message .= "หมอจันทราพร้อมทำนายให้แล้ว ✨\n\n";
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

            $context .= "- ให้ทำนายต่อยอดจากครั้งก่อนได้ เช่น \"จากที่หมอจันทราเคยบอกไว้...\" หรือ \"หมอจันทราจำได้ว่าครั้งก่อน...\"\n";

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
                'platform' => $this->currentPlatform,
                'platform_user_id' => $facebookUserId,
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

            // ✅ Gatekeeper: เช็คทราฟฟิค AI ทั้งระบบก่อนเรียก
            if (! LineGatekeeperService::canCallAI('fortune')) {
                Log::warning('Fortune: AI ทำนายพื้นฐานถูก throttle โดย Gatekeeper', [
                    'facebook_user_id' => $facebookUserId,
                ]);

                return [
                    'action' => 'fortune_throttled',
                    'message' => "⏳ ขณะนี้มีผู้ขอดูดวงจำนวนมากค่ะ\nกรุณารอสักครู่แล้วพิมพ์ขอดูดวงใหม่นะคะ 🙏✨",
                    'reading' => $reading,
                ];
            }

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

            // ✅ Gatekeeper: บันทึกว่าเรียก AI สำเร็จ (fortune basic)
            LineGatekeeperService::recordAICall('fortune');

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
                'message' => "🔮 คุณ{$name} คะ ขออภัยนะคะ ระบบกำลังปรับปรุงชั่วคราว 🙏\n\n".
                             "กรุณาลองพิมพ์มาใหม่อีกครั้งในอีก 1-2 นาที\n".
                             'หมอจันทราพร้อมดูดวงให้ ✨',
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
                'message' => "ยกเลิกแล้ว หากต้องการดูดวงใหม่ พิมพ์ 'ดูดวง' ได้เลย 🔮",
                'reading' => $reading,
            ];
        }

        return match ($status) {
            FortuneReading::STATUS_BASIC_DONE => $this->handleAfterBasic($reading, $messageText),
            FortuneReading::STATUS_COLLECTING_BIRTHDATE => $this->handleBirthdateInput($reading, $messageText),
            FortuneReading::STATUS_COLLECTING_QUESTIONS => $this->handleQuestionInput($reading, $messageText),
            FortuneReading::STATUS_COLLECTING_TAROT => $this->handleTarotCardDraw($reading, $messageText),
            FortuneReading::STATUS_PENDING_PAYMENT => $this->handlePendingPayment($reading, $messageText),
            // PAID: AI กำลังประมวลผลคำทำนายอยู่ → แจ้งให้รอ
            FortuneReading::STATUS_PAID => [
                'action' => 'processing',
                'message' => "🔮 กำลังประมวลผลคำทำนายอยู่ กรุณารอสักครู่ ✨\n\n"
                    . "ระบบกำลังวิเคราะห์ดวงให้อย่างละเอียด\n"
                    . 'จะส่งให้ทันทีเมื่อเสร็จ 🙏',
                'reading' => $reading,
            ],
            default => [
                'action' => 'help',
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
                    'message' => "🔮 ขออภัยค่ะ บริการดูดวงละเอียดถูกปิดการใช้งานชั่วคราว\n\n".
                                 "💫 สามารถดูดวงทั่วไปฟรีได้ตามปกติ\n".
                                 "พิมพ์คำถามมาได้เลย หรือพิมพ์ 'ดูดวง' เพื่อเริ่มใหม่ 🙏",
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
            'message' => "ไม่เป็นไรค่ะ หากต้องการดูดวงอีกครั้ง พิมพ์ 'ดูดวง' ได้เลย ✨\n\nอย่าลืมส่งต่อให้เพื่อนๆ มาลองดูดวงด้วยกัน 🔮",
            'reading' => $reading,
        ];
    }

    /**
     * เริ่ม deep reading flow (public wrapper สำหรับ ChannelManager redirect)
     *
     * ใช้เมื่อ AI Chat detect intent ว่าผู้ใช้ต้องการดูดวงเชิงลึก
     * และ ChannelManager ต้องเรียก method นี้จากภายนอก
     */
    public function startDeepReadingFlowPublic(string $userId, ?array $userProfile = null): array
    {
        return $this->startDeepReadingFlow($userId, $userProfile);
    }

    /**
     * เริ่ม flow ดูดวงละเอียด (บริการเสียเงิน) — สร้าง reading ใหม่ + ถามวันเกิด
     *
     * ใช้เมื่อผู้ใช้กดปุ่ม "💎 ดูดวงละเอียด" โดยไม่มี active reading (เช่น หลังจาก ai_limit)
     * ข้าม canMakeAICall() เพราะเป็นบริการเสียเงิน ไม่ใช่บริการฟรี
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
                    'message' => "🔮 ขออภัยค่ะ บริการดูดวงละเอียดถูกปิดการใช้งานชั่วคราว\n\n".
                                 "💫 สามารถดูดวงทั่วไปฟรีได้ตามปกติ\n".
                                 "พิมพ์คำถามมาได้เลย หรือพิมพ์ 'ดูดวง' เพื่อเริ่มใหม่ 🙏",
                    'reading' => null,
                ];
            }

            // ⚡ ใช้ profile จาก FortuneChannelManager (ไม่เรียก API ซ้ำ)
            if (! is_array($userProfile) || empty($userProfile)) {
                // ลอง lookup ชื่อจาก reading ก่อนหน้า (basic reading มักมีชื่อจริงจาก API แล้ว)
                $previousName = FortuneReading::where('facebook_user_id', $facebookUserId)
                    ->whereNotNull('facebook_user_name')
                    ->where('facebook_user_name', '!=', 'คุณ')
                    ->where('facebook_user_name', '!=', '')
                    ->latest()
                    ->value('facebook_user_name');

                $userProfile = [
                    'name' => $previousName ?? 'คุณ',
                    'id' => $facebookUserId,
                ];
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
                'platform' => $this->currentPlatform,
                'platform_user_id' => $facebookUserId,
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
                'message' => "ขอโทษค่ะ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง 🙏",
                'reading' => null,
            ];
        }
    }

    /**
     * จัดการ input วันเกิด
     */
    protected function handleBirthdateInput(FortuneReading $reading, string $messageText): array
    {
        // 🔓 Escape hatch — ถ้ายูสเซ่อร์อยากเริ่มใหม่/ยกเลิก/คุยกับคน
        // ใช้ exact match เท่านั้น (ป้องกัน "ไม่ยกเลิกค่ะ" → cancel โดยไม่ได้ตั้งใจ)
        $trimmed = trim($messageText);
        $lower = mb_strtolower($trimmed);
        $restartKeywords = [
            'ดูดวง', 'เริ่มใหม่', 'restart', 'เปลี่ยนเรื่อง',
            'ยกเลิก', 'cancel', 'stop', '/reset', 'reset',
        ];
        $normalizedKeywords = array_map(fn ($k) => mb_strtolower($k), $restartKeywords);
        if (in_array($lower, $normalizedKeywords, true)) {
            // ปิด conversation นี้ → ให้ processMessage สร้างใหม่
            $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

            return [
                'action' => 'restart_from_birthdate',
                'message' => "🔄 ยกเลิกการดูดวงรอบก่อนแล้ว\n\nพิมพ์ 'ดูดวง' หรือเรื่องที่อยากรู้ เพื่อเริ่มใหม่",
                'reading' => $reading,
            ];
        }

        $birthDate = $this->parseBirthDate($messageText);

        if (! $birthDate) {
            return [
                'action' => 'invalid_birthdate',
                'message' => "ไม่เข้าใจรูปแบบวันเกิด ลองใหม่ในรูปแบบนี้:\n\n📅 วัน/เดือน/ปี เช่น 15/08/1990\n📅 หรือ 15 สิงหาคม 2533\n\n💡 พิมพ์ 'ยกเลิก' หรือ 'เริ่มใหม่' หากอยากเริ่มใหม่",
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

            // ✅ หลังรับคำถาม → ให้สุ่มไพ่ยิปซีประกอบคำทำนาย (เฉพาะแบบเสียเงิน)
            $reading->update(['conversation_status' => FortuneReading::STATUS_COLLECTING_TAROT]);

            return [
                'action' => 'draw_tarot_card',
                'message' => "✅ รับคำถามข้อที่ {$questionCount} แล้วค่ะ\n\n"
                    . "🃏 กดสุ่มไพ่ยิปซี 1 ใบ เพื่อประกอบคำทำนายข้อนี้ ✨",
                'reading' => $reading,
                'question_number' => $questionCount,
            ];

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
     * จัดการสุ่มไพ่ยิปซี — สุ่มให้อัตโนมัติเมื่อ user กดปุ่ม/พิมพ์อะไรก็ได้
     *
     * สุ่มไพ่จาก TarotCard model → เก็บใน conversation_state
     * แล้ววนกลับถามคำถามต่อ หรือสร้างบิลถ้าครบ
     */
    protected function handleTarotCardDraw(FortuneReading $reading, string $messageText): array
    {
        try {
            $collectedQuestions = $reading->getCollectedQuestions();
            $questionCount = count($collectedQuestions);
            $currentIndex = $questionCount - 1; // 0-based index ของคำถามล่าสุด

            // สุ่มไพ่ยิปซี 1 ใบ (ไม่ซ้ำกับที่เคยได้)
            $existingCards = $reading->getCollectedTarotCards();
            $usedCardIds = array_column($existingCards, 'card_id');

            $card = \App\Models\TarotCard::active()
                ->when(! empty($usedCardIds), fn ($q) => $q->whereNotIn('id', $usedCardIds))
                ->inRandomOrder()
                ->first();

            if (! $card) {
                // กรณีไพ่หมด (ไม่น่าเกิด — มี 78 ใบ) → ข้ามไป
                Log::warning('Fortune: ไพ่ยิปซีหมด ข้ามขั้นตอน', ['reading_id' => $reading->id]);
                return $this->afterTarotCardDrawn($reading, $collectedQuestions, $questionCount);
            }

            // สุ่มตำแหน่งไพ่ (หงาย/คว่ำ)
            $isReversed = (bool) random_int(0, 1);
            $meaning = $card->getMeaning($isReversed, 'th');
            $cardNameTh = $card->getName('th');
            $cardNameEn = $card->getName('en');
            $position = $isReversed ? '(กลับหัว)' : '(หงาย)';

            // เก็บไพ่ใน conversation state (รวม image_url สำหรับส่งรูป)
            $cardImageUrl = $card->image_url;
            $reading->addTarotCard($currentIndex, $card->id, $cardNameTh, $cardNameEn, $isReversed, $meaning, $cardImageUrl);

            Log::info('Fortune: สุ่มไพ่ยิปซีได้', [
                'reading_id' => $reading->id,
                'question_index' => $currentIndex,
                'card_id' => $card->id,
                'card_name' => $cardNameEn,
                'is_reversed' => $isReversed,
                'has_image' => ! empty($cardImageUrl),
            ]);

            // แจ้งผลไพ่ พร้อมความหมาย แล้ววนกลับถามคำถามต่อ/สร้างบิล
            $tarotMessage = "🃏✨ ได้ไพ่ *{$cardNameTh}* {$position}\n";
            $tarotMessage .= "({$cardNameEn})\n\n";
            $tarotMessage .= "📖 ความหมาย: {$meaning}\n\n";

            $result = $this->afterTarotCardDrawn($reading, $collectedQuestions, $questionCount, $tarotMessage);

            // ✅ เพิ่มรูปไพ่ยิปซีเข้าไปใน response (ส่งก่อนข้อความ)
            if ($cardImageUrl) {
                $result['tarot_image_url'] = $cardImageUrl;
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Fortune: handleTarotCardDraw ล้มเหลว', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            // Fallback: ข้ามขั้นตอนไพ่ไป
            $collectedQuestions = $reading->getCollectedQuestions();
            return $this->afterTarotCardDrawn($reading, $collectedQuestions, count($collectedQuestions));
        }
    }

    /**
     * หลังสุ่มไพ่เสร็จ → ถามคำถามต่อ หรือสร้างบิลถ้าครบ
     */
    protected function afterTarotCardDrawn(FortuneReading $reading, array $collectedQuestions, int $questionCount, string $prefixMessage = ''): array
    {
        if ($questionCount < self::REQUIRED_QUESTIONS) {
            // ยังไม่ครบ → กลับไปถามคำถามต่อ
            $nextNumber = $questionCount + 1;
            $reading->update(['conversation_status' => FortuneReading::STATUS_COLLECTING_QUESTIONS]);

            return [
                'action' => 'need_more_questions',
                'message' => $prefixMessage
                    . "📝 คำถามข้อที่ {$nextNumber} จาก ".self::REQUIRED_QUESTIONS." — เลือกหมวดหรือพิมพ์เองได้เลย 👇",
                'reading' => $reading,
                'question_number' => $nextNumber,
            ];
        }

        // ครบแล้ว → สร้างบิล
        Log::info('Fortune: ครบ 2 คำถาม + ไพ่ยิปซี กำลังสร้างบิล', [
            'reading_id' => $reading->id,
            'questions' => $collectedQuestions,
            'tarot_cards' => $reading->getCollectedTarotCards(),
        ]);

        // เพิ่มข้อความไพ่ก่อนบิล (ถ้ามี)
        $billResult = $this->createPaymentBill($reading, $collectedQuestions);
        if (! empty($prefixMessage)) {
            $billResult['message'] = $prefixMessage . $billResult['message'];
        }

        return $billResult;
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
                'message' => "⏰ บิลดูดวงละเอียดหมดอายุแล้ว\n\n".
                             "ถ้าต้องการดูดวงละเอียดอีกครั้ง พิมพ์ 'ดูดวงละเอียด' ได้เลย\n".
                             'หรือพิมพ์คำถามใหม่มาได้เลย หมอจันทราพร้อมดูดวงให้ 🔮✨',
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

        $message = "🔮 หมอจันทรารอคำทำนายละเอียดให้อยู่\n\n";
        $message .= "กรุณาโอนเงินเพื่อรับคำทำนาย 🙏\n\n";
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
        $message .= "เมื่อโอนแล้วรอสักครู่ ระบบจะส่งคำทำนายให้ทันที ✨\n";
        $message .= "💡 หากโอนแล้วระบบไม่แจ้งเตือน ให้พิมพ์ว่า 'โอนแล้ว' ระบบจะส่งคำทำนายให้\n\n";
        if ($remainingMinutes <= 10) {
            $message .= "⚡ เหลือเวลาอีก {$remainingMinutes} นาทีนะคะ รีบโอนก่อนบิลหมดอายุ\n\n";
        }
        $message .= "พิมพ์ 'ยกเลิก' หากต้องการยกเลิก";

        // สร้าง Dynamic PromptPay QR Code พร้อมยอดเงิน
        $qrImageUrl = $this->generatePromptPayQrImage((float) $uniqueAmount->unique_amount, $reading->id);
        if (! $qrImageUrl) {
            $qrImageUrl = $this->getPaymentQrImageUrl();
        }

        return [
            'action' => 'waiting_payment',
            'message' => $message,
            'reading' => $reading,
            'payment_qr_url' => $qrImageUrl,
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

            // ⏱️ ติดตามเวลา — LINE replyToken หมดอายุ ~30s จึงต้องตอบให้ทัน
            $billStartTime = microtime(true);
            $maxBillTime = 12.0; // วินาที — เหลือเวลาให้ ChannelManager ส่ง response

            // สร้าง Birth Chart ส่งให้ผู้ใช้เห็นก่อนชำระเงิน (เป็น preview)
            // ✅ ข้ามถ้าใช้เวลาเกิน → ส่งบิลก่อน ส่ง chart ทีหลังได้
            $chartImageUrl = null;
            $elapsed = microtime(true) - $billStartTime;
            if ($elapsed < $maxBillTime) {
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
            } else {
                Log::info('Fortune: ข้าม chart generation เพื่อความเร็ว', [
                    'reading_id' => $reading->id,
                    'elapsed' => round($elapsed, 2),
                ]);
            }

            // สร้างข้อความสรุป + บัญชีธนาคาร
            $message = $this->getPaymentSummaryMessage($reading, $questions, $uniqueAmount);

            Log::info('Fortune Conversation: สร้างบิลรอชำระ', [
                'reading_id' => $reading->id,
                'unique_amount' => $uniqueAmount->unique_amount,
                'facebook_user_id' => $reading->facebook_user_id,
                'chart_image_url' => $chartImageUrl,
                'elapsed_ms' => round((microtime(true) - $billStartTime) * 1000),
            ]);

            // ✅ ส่ง FCM push ให้แอพ SMS Checker เห็นบิลใหม่ทันที
            // ⚡ ข้ามถ้าเวลาเหลือน้อย — ป้องกัน replyToken หมดอายุ (FCM ไม่สำคัญเท่าส่งบิลให้ลูกค้า)
            $elapsed = microtime(true) - $billStartTime;
            if ($elapsed < $maxBillTime) {
                try {
                    app(\App\Services\FcmNotificationService::class)->notifyNewFortuneReading($reading);
                } catch (\Exception $fcmErr) {
                    Log::warning('Fortune Conversation: FCM push new_fortune_reading ล้มเหลว (ไม่ blocking)', [
                        'reading_id' => $reading->id,
                        'error' => $fcmErr->getMessage(),
                    ]);
                }
            } else {
                Log::info('Fortune: ข้าม FCM push เพื่อความเร็ว', [
                    'reading_id' => $reading->id,
                    'elapsed' => round($elapsed, 2),
                ]);
            }

            // สร้าง Dynamic PromptPay QR Code พร้อมยอดเงิน (สแกนจ่ายได้เลย)
            // ✅ ข้ามถ้าเวลาเหลือน้อย → ใช้ static QR แทน
            $qrImageUrl = null;
            $elapsed = microtime(true) - $billStartTime;
            if ($elapsed < $maxBillTime) {
                $qrImageUrl = $this->generatePromptPayQrImage(
                    (float) $uniqueAmount->unique_amount,
                    $reading->id
                );
            } else {
                Log::info('Fortune: ข้าม QR generation เพื่อความเร็ว', [
                    'reading_id' => $reading->id,
                    'elapsed' => round($elapsed, 2),
                ]);
            }
            // Fallback: ใช้ static QR จากการตั้งค่า (ถ้า dynamic สร้างไม่ได้)
            if (! $qrImageUrl) {
                $qrImageUrl = $this->getPaymentQrImageUrl();
            }

            $totalElapsed = round((microtime(true) - $billStartTime) * 1000);
            if ($totalElapsed > 5000) {
                Log::warning('Fortune: createPaymentBill ใช้เวลานาน', [
                    'reading_id' => $reading->id,
                    'total_ms' => $totalElapsed,
                    'has_chart' => ! empty($chartImageUrl),
                    'has_qr' => ! empty($qrImageUrl),
                ]);
            }

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
                'message' => "🔮 ระบบกำลังจัดเตรียมให้ค่ะ\n\nพิมพ์คำถามใหม่ได้เลย หรือพิมพ์ 'ดูดวงละเอียด' อีกครั้งค่ะ ✨",
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
                        usleep(500000); // 0.5s — ลดจาก 1.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
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
            $streamingSentCount = 0; // นับจำนวนคำทำนายที่ส่งสำเร็จผ่าน streaming

            foreach ($questions as $index => $question) {
                $questionNum = $index + 1;
                $totalQuestions = count($questions);

                // ดึงไพ่ยิปซีที่ผู้ใช้เปิดได้สำหรับคำถามนี้ (ถ้ามี)
                $tarotCard = $reading->getTarotCardForQuestion($index);

                // สร้าง prompt เฉพาะคำถามนี้ อิงวันเกิด+เพศ+ไพ่ยิปซี
                $perQuestionPrompt = $this->buildPerQuestionDeepPrompt(
                    $userProfile,
                    $question,
                    $questionNum,
                    $totalQuestions,
                    $birthDate,
                    $deepReadings,
                    $tarotCard
                );

                // ✅ Gatekeeper: เช็คทราฟฟิค AI ก่อนเรียกทุกคำถาม
                if (! LineGatekeeperService::canCallAI('fortune')) {
                    Log::warning('Fortune: AI Deep Reading ถูก throttle ที่ข้อ '.$questionNum, [
                        'reading_id' => $reading->id ?? null,
                    ]);
                    break; // หยุดถามข้อต่อไป ส่งผลลัพธ์ที่ได้แล้ว
                }

                $aiResult = $this->aiService->generateWithRetryAndFallback(
                    [$question],
                    $userProfile,
                    null,
                    $perQuestionPrompt,
                    'deep',
                    $birthDate
                );

                // ✅ Gatekeeper: บันทึกว่าเรียก AI สำเร็จ (fortune deep)
                LineGatekeeperService::recordAICall('fortune');

                $totalTokens += $aiResult['tokens_used'] ?? 0;
                $lastProvider = $aiResult['provider'] ?? '';
                $lastModel = $aiResult['model'] ?? '';

                // ✅ ส่วนที่ 2: เรียก AI รอบ 2 สำหรับวิเคราะห์ไพ่ยิปซีแยก (ถ้ามีไพ่)
                $tarotAiResponse = '';
                if (! empty($tarotCard) && LineGatekeeperService::canCallAI('fortune')) {
                    $tarotPrompt = $this->buildTarotOnlyPrompt(
                        $userProfile, $question, $questionNum, $totalQuestions, $birthDate, $tarotCard
                    );

                    try {
                        $tarotAiResult = $this->aiService->generateWithRetryAndFallback(
                            [$question],
                            $userProfile,
                            null,
                            $tarotPrompt,
                            'deep',
                            $birthDate
                        );
                        LineGatekeeperService::recordAICall('fortune');
                        $tarotAiResponse = $tarotAiResult['response'] ?? '';
                        $totalTokens += $tarotAiResult['tokens_used'] ?? 0;

                        Log::info("Fortune Deep: ไพ่ยิปซีข้อ {$questionNum} ยาว ".mb_strlen($tarotAiResponse).' ตัวอักษร');
                    } catch (\Exception $tarotErr) {
                        Log::warning("Fortune Deep: สร้างคำทำนายไพ่ข้อ {$questionNum} ล้มเหลว", [
                            'reading_id' => $reading->id,
                            'error' => $tarotErr->getMessage(),
                        ]);
                    }
                }

                // รวม response สำหรับบันทึกลง DB
                $combinedAnswer = $aiResult['response'];
                if (! empty($tarotAiResponse)) {
                    $combinedAnswer .= "\n\n".$tarotAiResponse;
                }

                $deepReadings[] = [
                    'question_number' => $questionNum,
                    'question' => $question,
                    'answer' => $combinedAnswer,
                    'tarot_card' => $tarotCard,
                    'tarot_reading' => $tarotAiResponse,
                ];

                // [Streaming] ส่งคำทำนายแต่ละข้อกลับทันที
                if ($streaming) {
                    // === ส่วนที่ 1: คำทำนายดวงดาวหลัก ===
                    try {
                        Log::info("Fortune Deep Streaming: ข้อที่ {$questionNum} ยาว ".mb_strlen($aiResult['response']).' ตัวอักษร');

                        $sendSuccess = false;

                        // ⚡ สำหรับ LINE → ใช้ Flex Message การ์ดสวยๆ (แยก bubble อัตโนมัติถ้ายาว)
                        if ($platform === 'line') {
                            $lineService = $channelManager->getPlatform('line');
                            if ($lineService instanceof LineFortuneService) {
                                // ✅ V3: ใช้ sendDeepReadingFlexSafe — แยก bubble + carousel + fallback text อัตโนมัติ
                                $sendSuccess = $lineService->sendDeepReadingFlexSafe(
                                    $userId, $questionNum, $question, $aiResult['response'], $totalQuestions,
                                    "🔮 คำทำนายข้อ {$questionNum}/{$totalQuestions}: {$question}"
                                );
                            }
                        } else {
                            // Facebook / platform อื่น → ส่งเป็น text (แบ่งชิ้นถ้ายาวเกิน 1800 ตัวอักษร)
                            $header = "🔮 คำทำนายข้อที่ {$questionNum}/{$totalQuestions}\n❓ {$question}\n\n";
                            $fullMsg = $header.$aiResult['response'];
                            $fbMaxLen = 1800;

                            if (mb_strlen($fullMsg) <= $fbMaxLen) {
                                $sendSuccess = $channelManager->sendResponse($platform, $userId, [
                                    'action' => 'partial',
                                    'message' => $fullMsg,
                                ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);
                            } else {
                                // แบ่งเป็น chunks ตาม paragraph
                                $chunks = $this->splitLongMessageForFacebook($header, $aiResult['response'], $fbMaxLen);
                                $sendSuccess = true;
                                foreach ($chunks as $chunkIdx => $chunk) {
                                    $partSent = $channelManager->sendResponse($platform, $userId, [
                                        'action' => 'partial',
                                        'message' => $chunk,
                                    ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);
                                    if (! $partSent) {
                                        $sendSuccess = false;
                                    }
                                    if ($chunkIdx < count($chunks) - 1) {
                                        usleep(500_000); // 0.5s ระหว่าง chunks (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
                                    }
                                }
                            }
                        }

                        if ($sendSuccess) {
                            $streamingSentCount++;
                        } else {
                            Log::warning("Fortune Deep Streaming: ส่งคำทำนายข้อที่ {$questionNum} ไม่สำเร็จ (return false)", [
                                'reading_id' => $reading->id,
                                'platform' => $platform,
                                'user_id' => $userId,
                            ]);
                        }

                        usleep(500000); // 0.5s — ลดจาก 1.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
                    } catch (\Exception $sendErr) {
                        Log::warning("Fortune Deep Streaming: ส่งคำทำนายข้อที่ {$questionNum} ไม่สำเร็จ (exception)", [
                            'reading_id' => $reading->id,
                            'error' => $sendErr->getMessage(),
                        ]);
                    }

                    // === ส่งรูปไพ่ยิปซี (ถ้ามี) ก่อนวิเคราะห์ ===
                    if (! empty($tarotCard['image_url'])) {
                        try {
                            $platformService = $channelManager->getPlatform($platform);
                            if ($platformService) {
                                $platformService->sendImage($userId, $tarotCard['image_url']);
                                usleep(500_000); // 0.5s — ลดจาก 0.8s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
                            }
                        } catch (\Exception $imgErr) {
                            Log::warning("Fortune Deep Streaming: ส่งรูปไพ่ข้อ {$questionNum} ไม่สำเร็จ", [
                                'error' => $imgErr->getMessage(),
                            ]);
                        }
                    }

                    // === ส่วนที่ 2: วิเคราะห์ไพ่ยิปซีแยก (ถ้ามี) ===
                    if (! empty($tarotAiResponse)) {
                        try {
                            $tarotSendSuccess = false;
                            $cardNameTh = $tarotCard['card_name_th'] ?? 'ไพ่ยิปซี';

                            if ($platform === 'line') {
                                $lineService = $channelManager->getPlatform('line');
                                if ($lineService instanceof LineFortuneService) {
                                    // ✅ V3: ใช้ sendDeepReadingFlexSafe — แยก bubble + carousel + fallback text อัตโนมัติ
                                    $tarotSendSuccess = $lineService->sendDeepReadingFlexSafe(
                                        $userId, $questionNum, "🃏 วิเคราะห์ไพ่ {$cardNameTh}", $tarotAiResponse, $totalQuestions,
                                        "🃏 วิเคราะห์ไพ่ยิปซี ข้อ {$questionNum}: {$cardNameTh}"
                                    );
                                }
                            } else {
                                // Facebook: แบ่งชิ้นถ้ายาวเกิน 1800 ตัวอักษร
                                $tarotHeader = "🃏 วิเคราะห์ไพ่ยิปซี ข้อ {$questionNum}/{$totalQuestions}\n🎴 ไพ่: {$cardNameTh}\n\n";
                                $fullTarotMsg = $tarotHeader.$tarotAiResponse;
                                $fbMaxLen = 1800;

                                if (mb_strlen($fullTarotMsg) <= $fbMaxLen) {
                                    $tarotSendSuccess = $channelManager->sendResponse($platform, $userId, [
                                        'action' => 'partial',
                                        'message' => $fullTarotMsg,
                                    ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);
                                } else {
                                    $tarotChunks = $this->splitLongMessageForFacebook($tarotHeader, $tarotAiResponse, $fbMaxLen);
                                    $tarotSendSuccess = true;
                                    foreach ($tarotChunks as $chunkIdx => $chunk) {
                                        $partSent = $channelManager->sendResponse($platform, $userId, [
                                            'action' => 'partial',
                                            'message' => $chunk,
                                        ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);
                                        if (! $partSent) {
                                            $tarotSendSuccess = false;
                                        }
                                        if ($chunkIdx < count($tarotChunks) - 1) {
                                            usleep(500_000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
                                        }
                                    }
                                }
                            }

                            if ($tarotSendSuccess) {
                                $streamingSentCount++;
                                Log::info("Fortune Deep Streaming: ส่งไพ่ยิปซีข้อ {$questionNum} สำเร็จ");
                            }

                            usleep(500000); // 0.5s — ลดจาก 1.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
                        } catch (\Exception $tarotSendErr) {
                            Log::warning("Fortune Deep Streaming: ส่งไพ่ยิปซีข้อ {$questionNum} ล้มเหลว", [
                                'reading_id' => $reading->id,
                                'error' => $tarotSendErr->getMessage(),
                            ]);
                        }
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
                    if ($affiliatePlatform === 'line') {
                        // ลองดึงจาก channelManager ก่อน
                        if ($channelManager) {
                            $lineServiceInstance = $channelManager->getPlatform('line');
                            $lineServiceInstance = $lineServiceInstance instanceof LineFortuneService ? $lineServiceInstance : null;
                        }
                        // Fallback: สร้าง LineFortuneService ตรงๆ (กรณี background job ที่ไม่มี channelManager)
                        if (! $lineServiceInstance) {
                            try {
                                $lineServiceInstance = app(LineFortuneService::class);
                            } catch (\Exception $lineErr) {
                                Log::debug('Fortune Affiliate: สร้าง LineFortuneService ไม่ได้ — ข้าม Flex', [
                                    'error' => $lineErr->getMessage(),
                                ]);
                            }
                        }
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

            // ============================================================
            // [MLM Commission] แบ่งคอมมิชชั่น MLM หลังชำระเงินสำเร็จ
            // ใช้ fortune_pv_value จาก settings → ส่งเข้า MlmCommissionService
            // คอมมิชชั่นจะถูกสร้างใน mlm_commissions table ตาม unilevel levels
            // ============================================================
            $this->distributeFortuneCommissions($reading);

            // ============================================================
            // [Affiliate Promo] ส่งข้อความโปรโมทระบบ affiliate หลังดูดวงเสร็จ
            // ส่งทุกครั้ง (repeat user ก็ส่ง) เพื่อจูงใจให้แชร์
            // แสดง: ค่าแนะนำ (จาก settings) + ตัวอย่างรายได้ + ลิงก์แชร์ + ลิงก์เว็บ
            // ============================================================
            if ($affiliateUserId) {
                try {
                    $affiliateServiceForPromo = app(FortuneAffiliateService::class);

                    // ดึง LINE service สำหรับส่ง promo (ใช้ instance ที่สร้างไว้ข้างบน)
                    $lineServiceForPromo = $lineServiceInstance ?? null;

                    $affiliateServiceForPromo->sendPostReadingAffiliatePromotion(
                        $reading,
                        $affiliateUserId,
                        $lineServiceForPromo,
                        $affiliatePlatform
                    );
                } catch (\Exception $promoErr) {
                    Log::warning('Fortune Affiliate Promo: ส่งข้อความโปรโมทล้มเหลว (ไม่กระทบ)', [
                        'reading_id' => $reading->id,
                        'error' => $promoErr->getMessage(),
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
                'streaming_sent_count' => $streamingSentCount,
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
               "📝 คำถามข้อที่ 1 จาก {$count} — เลือกหมวดหรือพิมพ์เองได้เลย 👇";
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
        $message .= "เมื่อโอนแล้วรอสักครู่ ระบบจะส่งคำทำนายให้ทันที ✨\n";
        $message .= "💡 หากโอนแล้วระบบไม่แจ้งเตือน ให้พิมพ์ว่า 'โอนแล้ว' ระบบจะส่งคำทำนายให้\n\n";
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
        $message .= "⚠️ กรุณาโอนยอด ฿{$amount} ตรงตามทศนิยมค่ะ\n";
        $message .= "💡 หากโอนแล้วระบบไม่แจ้งเตือน ให้พิมพ์ว่า 'โอนแล้ว' ระบบจะส่งคำทำนายให้";

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
               "📢 อย่าลืมส่งต่อให้เพื่อนๆ มาลองดูดวงด้วยกัน\n".
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

        $message = "🔮 *เพจดูดวงหมอจันทรายินดีต้อนรับ*\n\n";
        $message .= "หมอพร้อมช่วยดูดวงให้ ไม่ว่าจะเรื่องอะไร:\n\n";

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

        $message .= "💡 พิมพ์คำถามมาได้เลย\n";
        $message .= 'หมอพร้อมทำนายให้ ✨';

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
                'message' => '🔮 พิมพ์ข้อความมาได้เลย หมอจันทราพร้อมช่วยดูดวงให้ ✨',
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
               "หมอจันทราขอตอบเฉพาะเรื่องดูดวงเท่านั้นค่ะ\n\n".
               "💡 *ตัวอย่างคำถาม*:\n".
               "• ดวงความรักปีนี้เป็นอย่างไร\n".
               "• การเงินจะดีขึ้นไหม\n".
               "• ควรเปลี่ยนงานไหม\n\n".
               'หมอจันทราพร้อมทำนายให้ 🔮✨';
    }

    /**
     * ข้อความเมื่อโดน rate limit
     *
     * @param  string  $type  minute, hour, หรือ day
     */
    protected function getRateLimitMessage(string $type): string
    {
        $messages = [
            'minute' => "🙏 หมอจันทราขอเวลาสักครู่นะคะ\n\nกรุณารอสักครู่แล้วทักมาใหม่ค่ะ 🔮",
            'hour' => "🙏 ขอบคุณที่สนใจค่ะ\n\nทักมาเยอะมากเลยค่ะ กรุณารอสักพักแล้วค่อยทักมาใหม่นะคะ\n\nหมอจันทราพร้อมทำนายให้ 🔮✨",
            'day' => "🙏 ขอบคุณที่ใช้บริการค่ะ\n\nวันนี้ทักมาเยอะมากเลยค่ะ กรุณากลับมาใหม่พรุ่งนี้นะคะ\n\nขอให้โชคดีค่ะ 🔮✨",
        ];

        return $messages[$type] ?? $messages['minute'];
    }

    /**
     * ข้อความเมื่อส่งข้อความซ้ำ
     */
    protected function getRepetitiveMessage(): string
    {
        return "🙏 หมอจันทราเห็นข้อความนี้แล้วค่ะ\n\n".
               "กรุณาลองถามเรื่องอื่น หรือถามในมุมใหม่ได้นะคะ\n\n".
               "💡 *ตัวอย่าง*:\n".
               "• ดวงความรักปีนี้เป็นอย่างไร\n".
               "• การเงินจะดีขึ้นไหม\n".
               "• ควรเปลี่ยนงานไหม\n\n".
               'หมอจันทราพร้อมทำนายให้ 🔮✨';
    }

    /**
     * ข้อความเมื่อตรวจจับข้อความไร้สาระ
     *
     * ตอบด้วยความเป็นมิตร ไม่ดูถูกผู้ใช้
     */
    protected function getMeaninglessMessage(): string
    {
        return "🔮 *เพจดูดวงหมอจันทรายินดีต้อนรับ*\n\n".
               "หมอพร้อมช่วยดูดวงให้ ไม่ว่าจะเรื่อง:\n\n".
               "💕 ความรัก - เนื้อคู่ คู่ครอง\n".
               "💼 การงาน - เปลี่ยนงาน เลื่อนขั้น\n".
               "💰 การเงิน - โชคลาภ รายได้\n".
               "🏥 สุขภาพ - สิ่งควรระวัง\n\n".
               "💡 *ตัวอย่างคำถาม*:\n".
               "• ปีนี้จะมีคู่ครองไหม\n".
               "• ควรเปลี่ยนงานไหม\n".
               "• ดวงการเงินเป็นอย่างไร\n\n".
               'พิมพ์คำถามมาได้เลย 🔮✨';
    }

    /**
     * ข้อความเมื่อใช้สิทธิ์ถามฟรีหมดแล้ว
     *
     * ชวนให้จ่ายเงินดูดวงละเอียดพร้อมบอกวิธีการชัดเจน
     */
    protected function getAILimitMessage(): string
    {
        $message = "🔮 *เพจดูดวงหมอจันทรายินดีต้อนรับ*\n\n";
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

            $message .= 'กดปุ่มด้านล่างเพื่อเริ่ม 👇';
        } else {
            $message .= 'กลับมาใหม่พรุ่งนี้ได้ 🙏';
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
               'หมอพร้อมทำนายให้ ✨';
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
            'code' => 'ขอบคุณที่สนใจค่ะ แต่หมอจันทราไม่รับเขียนโค้ดหรือโปรแกรมนะคะ 🙏',
            'food' => 'ขอบคุณที่สนใจค่ะ แต่หมอจันทราไม่รับแนะนำร้านอาหารหรือสูตรอาหารนะคะ 🙏',
            'translate' => 'ขอบคุณที่สนใจค่ะ แต่หมอจันทราไม่รับแปลภาษานะคะ 🙏',
            'story' => 'ขอบคุณที่สนใจค่ะ แต่หมอจันทราไม่รับเล่าเรื่องหรือมุกตลกนะคะ 🙏',
            'math' => 'ขอบคุณที่สนใจค่ะ แต่หมอจันทราไม่รับคำนวณเลขนะคะ 🙏',
            'hack' => 'ขอโทษค่ะ หมอจันทราไม่รับทำสิ่งที่ผิดกฎหมายหรือไม่เหมาะสมค่ะ 🙏',
            'homework' => 'ขอบคุณที่สนใจค่ะ แต่หมอจันทราไม่รับทำการบ้านหรือเขียนรายงานนะคะ 🙏',
        ];

        $specificMessage = $categoryMessages[$category] ?? 'ขอบคุณที่สนใจค่ะ 🙏';

        return "{$specificMessage}\n\n".
               "═══════════════════════\n".
               "🔮 *หมอจันทรารับดูดวงเท่านั้นค่ะ*\n".
               "═══════════════════════\n\n".
               "ถ้ามีเรื่องอยากให้ทำนาย ไม่ว่าจะเรื่อง:\n".
               "💕 ความรัก คู่ครอง\n".
               "💼 การงาน อาชีพ\n".
               "💰 การเงิน โชคลาภ\n".
               "🏥 สุขภาพ\n\n".
               'ทักมาได้เลยค่ะ หมอจันทราพร้อมทำนายให้ 🔮✨';
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
        $acceptKeywords = ['ต้องการ', 'เอา', 'ใช่', 'ได้', 'ok', 'yes', 'ตกลง', 'โอเค', 'อยาก', 'สนใจ', 'ละเอียด', 'เชิงลึก', 'deep'];
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
            'ดูดวงเชิงลึก',
            'ดูเชิงลึก',
            'ต้องการดูเชิงลึก',
            'อยากดูเชิงลึก',
            'สนใจดูเชิงลึก',
            'ดูดวงแบบละเอียด',
            'ดูแบบละเอียด',
            'ดูดวงdeep',
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
        $text = mb_strtolower(trim($text));

        // คำสั่งยกเลิกชัดเจน → ใช้ str_contains (ข้อความยาวก็ match)
        $strongKeywords = ['ยกเลิก', 'cancel', 'stop'];
        foreach ($strongKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        // คำสั้นที่อาจกำกวม → ใช้ exact match เท่านั้น
        // เพื่อไม่ให้ "ไม่เอาดูดวงละเอียด" → ยกเลิกทั้ง session
        $exactKeywords = ['ไม่เอา', 'เลิก', 'หยุด'];
        $textNormalized = preg_replace('/\s*(ค่ะ|ครับ|คะ|จ้า|จ้ะ|นะ|นะคะ|นะครับ)\s*$/u', '', $text);

        foreach ($exactKeywords as $keyword) {
            if ($text === $keyword || $textNormalized === $keyword) {
                return true;
            }
        }

        return false;
    }

    /**
     * ตรวจสอบว่าต้องการดูบัญชีธนาคารหรือไม่
     *
     * ใช้ exact match สำหรับคำสั้น เพื่อไม่ให้ trigger ผิด
     * เช่น "เงินโอนจากงาน" หรือ "ดวงบัญชีการเงิน" ไม่ควร match
     */

    // =====================================================================
    // Database Keyword Matching (Auto-Reply อัจฉริยะ)
    // =====================================================================

    /**
     * ตรวจสอบ keywords จากฐานข้อมูล (LineBotKeyword)
     *
     * ใช้ cache เพื่อลด DB queries — cache หมดอายุทุก 5 นาที
     * ตรวจสอบเฉพาะ category 'fortune' + 'faq' ที่ active
     * เรียงตาม priority สูงสุดก่อน
     *
     * @param  string  $messageText  ข้อความจากผู้ใช้
     * @return LineBotKeyword|null keyword ที่ match หรือ null
     */
    protected function checkDatabaseKeywords(string $messageText): ?LineBotKeyword
    {
        try {
            // ดึง keywords จาก cache (ลดการเรียก DB ทุกข้อความ)
            $keywords = Cache::remember('fortune:db_keywords', 300, function () {
                return LineBotKeyword::active()
                    ->where(function ($query) {
                        $query->where('category', 'fortune')
                            ->orWhere('category', 'faq');
                    })
                    ->byPriority()
                    ->get();
            });

            // ตรวจสอบว่า match keyword ไหน
            foreach ($keywords as $keyword) {
                if ($keyword->matches($messageText)) {
                    return $keyword;
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('Fortune: checkDatabaseKeywords error', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * สร้าง response array จาก LineBotKeyword ที่ match
     *
     * รองรับ response_type: text, flex_message, quick_reply
     * FortuneChannelManager จะจัดการส่งตาม type
     *
     * @param  LineBotKeyword  $keyword  keyword ที่ match
     * @return array response array สำหรับ FortuneChannelManager
     */
    protected function handleKeywordMatchResponse(LineBotKeyword $keyword): array
    {
        return [
            'action' => 'keyword_matched',
            'message' => $keyword->response_text ?? '',
            'reading' => null,
            'keyword_id' => $keyword->id,
            'keyword_name' => $keyword->keyword,
            'response_type' => $keyword->response_type ?? 'text',
            'response_flex_json' => $keyword->response_flex_json,
            'quick_reply_options' => $keyword->quick_reply_options,
        ];
    }

    /**
     * ลอง AI Chat ทั่วไป (Gemini) สำหรับข้อความที่ไม่ใช่เรื่องดูดวง
     *
     * ใช้ provider แยกจากทำนาย (chat_ai_provider) เพื่อตอบสนทนาเป็นธรรมชาติ
     * ไม่สร้าง FortuneReading + ไม่นับ AI call limit
     *
     * @param  string  $userId  ID ผู้ใช้
     * @param  string  $messageText  ข้อความจากผู้ใช้
     * @param  array|null  $userProfile  โปรไฟล์ผู้ใช้
     * @return array|null  ผลลัพธ์ action 'ai_chat_response' หรือ null ถ้าล้มเหลว
     */
    protected function tryAIChatResponse(string $userId, string $messageText, ?array $userProfile = null): ?array
    {
        try {
            // เช็คว่าเปิด AI Chat หรือไม่
            if (! ($this->settings->enable_ai_chat ?? false)) {
                Log::debug('Fortune: AI Chat ปิดอยู่ (enable_ai_chat=false)', ['user_id' => $userId]);

                return null;
            }

            // ✅ Gatekeeper: เช็คทราฟฟิค AI ทั้งระบบก่อนเรียก
            if (! LineGatekeeperService::canCallAI('fortune')) {
                Log::warning('Fortune: AI Chat ถูก throttle โดย Gatekeeper', ['user_id' => $userId]);

                return [
                    'action' => 'ai_chat_response',
                    'message' => "⏳ ขณะนี้มีผู้ใช้งานจำนวนมากค่ะ\nกรุณารอสักครู่แล้วพิมพ์ข้อความมาใหม่นะคะ 🙏✨",
                    'reading' => null,
                ];
            }

            Log::debug('Fortune: กำลังเรียก AI Chat', [
                'user_id' => $userId,
                'provider' => $this->settings->getChatAIProvider(),
                'has_key' => ! empty($this->settings->getChatAIApiKey()),
                'text_preview' => mb_substr($messageText, 0, 30),
            ]);

            // ✅ ดึง conversation history สำหรับ AI (ความจำ 10 ข้อความ)
            $history = $this->getConversationHistoryForAI($userId);

            // 🔢 นับ rapport turns — จำนวนครั้งที่ user พูด
            // เพื่อให้ AI รู้ว่าคุยมากี่รอบแล้ว (≥2 → เสนอดูดวง)
            $userTurnCount = collect($history)->where('role', 'user')->count() + 1; // +1 = ข้อความปัจจุบัน

            // Inject turn context ให้ AI ตัดสินใจเสนอดูดวง
            $messageForAI = $messageText;
            if ($userTurnCount >= 2) {
                $messageForAI = "[TURN {$userTurnCount}] {$messageText}";
            }

            // เรียก AI Chat พร้อม history (ถ้ามี)
            $aiService = new FortuneAIService($this->settings);
            if (! empty($history)) {
                $result = $aiService->generateChatResponseWithHistory($messageForAI, $userProfile, $history);
            } else {
                $result = $aiService->generateChatResponse($messageForAI, $userProfile);
            }

            // ✅ Gatekeeper: บันทึกว่าเรียก AI สำเร็จ
            LineGatekeeperService::recordAICall('fortune');

            $responseText = trim($result['response'] ?? '');

            if (empty($responseText)) {
                return null;
            }

            // ✅ บันทึก conversation history (ทั้งข้อความผู้ใช้ + คำตอบ AI)
            $this->saveConversationMessage($userId, 'user', $messageText);
            $this->saveConversationMessage($userId, 'assistant', $responseText);

            // ✅ ตรวจจับ [OFFER_FORTUNE] — AI สร้าง rapport เสร็จแล้ว เสนอให้เริ่มดูดวง
            // ไม่ redirect เลย — แค่ติดธง offer_fortune ให้ ChannelManager ใส่ปุ่มเริ่มดูดวงเด่น
            $offerFortune = false;
            if (str_contains($responseText, '[OFFER_FORTUNE]')) {
                $responseText = trim(str_replace('[OFFER_FORTUNE]', '', $responseText));
                $offerFortune = true;

                Log::info('Fortune: AI เสนอเริ่มดูดวง (rapport built)', [
                    'user_id' => $userId,
                    'turn_count' => $userTurnCount,
                ]);
            }

            // ✅ ตรวจจับ [DEEP_READING] — AI เข้าใจว่าผู้ใช้ต้องการดูดวงเชิงลึก → redirect เข้า deep reading flow
            if (str_contains($responseText, '[DEEP_READING]')) {
                $responseText = trim(str_replace('[DEEP_READING]', '', $responseText));

                Log::info('Fortune: AI detect intent ดูดวงเชิงลึก → redirect to deep reading flow', [
                    'user_id' => $userId,
                    'original_message' => mb_substr($messageText, 0, 100),
                ]);

                // ปิด conversation เก่า (ถ้ามี) ก่อนเริ่ม deep reading
                $this->closeAllActiveConversations($userId);

                return [
                    'action' => 'ai_redirect_deep_reading',
                    'message' => $responseText,
                    'reading' => null,
                    'redirect_to' => 'deep_reading',
                ];
            }

            // ✅ ตรวจจับ [ASK_SAVE] — AI บอกว่าตอบไม่ได้ → ถามผู้ใช้ก่อนว่าจะฝากคำถามถึงแอดมินไหม
            if (str_contains($responseText, '[ASK_SAVE]')) {
                $responseText = trim(str_replace('[ASK_SAVE]', '', $responseText));

                // เก็บคำถามไว้ใน Cache รอยืนยันจากผู้ใช้ (หมดอายุ 10 นาที)
                $cacheKey = "fortune_pending_save:{$userId}";
                Cache::put($cacheKey, [
                    'question' => $messageText,
                    'ai_response' => $responseText,
                    'user_name' => $userProfile['name'] ?? null,
                ], now()->addMinutes(10));

                Log::info('Fortune: AI ตอบไม่ได้ → ถามผู้ใช้ว่าจะฝากถึงแอดมินไหม', [
                    'user_id' => $userId,
                    'question' => mb_substr($messageText, 0, 100),
                ]);

                return [
                    'action' => 'ai_ask_save_question',
                    'message' => $responseText,
                    'reading' => null,
                    'quick_reply_options' => [
                        ['label' => '📝 ฝากถึงแอดมิน', 'text' => 'ฝากคำถามถึงแอดมิน'],
                        ['label' => '❌ ไม่ฝาก', 'text' => 'ไม่ฝากคำถาม'],
                    ],
                ];
            }

            Log::info('Fortune: AI Chat response สำเร็จ', [
                'user_id' => $userId,
                'provider' => $result['provider'] ?? 'unknown',
                'model' => $result['model'] ?? 'unknown',
                'response_preview' => mb_substr($responseText, 0, 80),
                'history_count' => count($history),
            ]);

            return [
                'action' => 'ai_chat_response',
                'message' => $responseText,
                'reading' => null,
                'chat_provider' => $result['provider'] ?? '',
                'chat_model' => $result['model'] ?? '',
                'offer_fortune' => $offerFortune,  // ChannelManager ใช้เพื่อเลือก quick replies
                'turn_count' => $userTurnCount,
            ];

        } catch (\Exception $e) {
            // AI ล้มเหลว → บันทึกคำถามให้แอดมิน + return null เพื่อ fallback
            Log::warning('Fortune: AI Chat ล้มเหลว → บันทึกคำถามให้แอดมิน', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'text_preview' => mb_substr($messageText, 0, 50),
            ]);

            $this->saveQuestionForAdmin(
                $userId,
                $messageText,
                'ai_failed',
                null,
                $userProfile['name'] ?? null
            );

            return null;
        }
    }

    /**
     * ดึงประวัติสนทนาสำหรับส่งไป AI (ความจำ 10 ข้อความล่าสุด)
     *
     * ใช้ LineBotConversation เก็บ history ร่วมกันทุก platform
     * ปิด conversation ที่ไม่มีข้อความ > 30 นาทีอัตโนมัติ
     *
     * @param  string  $userId  Platform User ID (Facebook PSID / LINE user ID)
     * @param  string  $platform  ชื่อ platform (auto-detect จาก context)
     * @return array  [['role' => 'user'|'assistant', 'content' => '...'], ...]
     */
    protected function getConversationHistoryForAI(string $userId, string $platform = 'facebook'): array
    {
        try {
            $conversation = \App\Models\LineBotConversation::findOrCreateForPlatform(
                $userId,
                $platform,
                30 // timeout 30 นาที
            );

            return $conversation->getHistoryForAI(10);
        } catch (\Exception $e) {
            Log::warning('Fortune: ดึง conversation history ไม่ได้', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * บันทึกข้อความลง conversation history
     *
     * @param  string  $userId  Platform User ID
     * @param  string  $role  'user' หรือ 'assistant'
     * @param  string  $message  เนื้อหาข้อความ
     * @param  string  $platform  ชื่อ platform
     */
    protected function saveConversationMessage(
        string $userId,
        string $role,
        string $message,
        string $platform = 'facebook'
    ): void {
        try {
            $conversation = \App\Models\LineBotConversation::findOrCreateForPlatform(
                $userId,
                $platform,
                30
            );

            $conversation->addMessage($role, mb_substr($message, 0, 2000));
        } catch (\Exception $e) {
            // ไม่ block ระบบหลักถ้าบันทึก history ไม่ได้
            Log::warning('Fortune: บันทึก conversation message ไม่ได้', [
                'user_id' => $userId,
                'role' => $role,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * จัดการเมื่อผู้ใช้กดปุ่ม "ฝากคำถามถึงแอดมิน" หรือ "ไม่ฝากคำถาม"
     *
     * ตรวจสอบว่ามี pending question ใน Cache หรือไม่
     * ถ้ามี → ดำเนินการตามที่ผู้ใช้เลือก (ฝาก/ไม่ฝาก)
     */
    protected function handlePendingSaveResponse(string $userId, string $messageText, ?array $userProfile = null): ?array
    {
        $cacheKey = "fortune_pending_save:{$userId}";
        $pendingData = Cache::get($cacheKey);

        // ไม่มี pending question → ข้าม
        if (! $pendingData) {
            return null;
        }

        $normalizedText = mb_strtolower(trim($messageText));

        // ผู้ใช้เลือก "ฝากคำถามถึงแอดมิน"
        if (str_contains($normalizedText, 'ฝากคำถามถึงแอดมิน') || str_contains($normalizedText, 'ฝากถึงแอดมิน') || $normalizedText === 'ฝาก') {
            // บันทึกคำถามลง DB
            $this->saveQuestionForAdmin(
                $userId,
                $pendingData['question'],
                'ai_cannot_answer',
                $pendingData['ai_response'] ?? null,
                $pendingData['user_name'] ?? $userProfile['name'] ?? null
            );

            // ลบ pending ออกจาก Cache
            Cache::forget($cacheKey);

            Log::info('Fortune: ผู้ใช้ยืนยันฝากคำถามถึงแอดมิน', [
                'user_id' => $userId,
                'question' => mb_substr($pendingData['question'], 0, 100),
            ]);

            return [
                'action' => 'ai_chat_response',
                'message' => "หมอจันทราบันทึกคำถามไว้เรียบร้อยแล้วค่ะ 📝 แอดมินจะตอบกลับให้เร็วที่สุดนะคะ\n\nระหว่างนี้ พิมพ์ ดูดวง หรือถามเรื่องอื่นได้เลยค่ะ ✨",
                'reading' => null,
            ];
        }

        // ผู้ใช้เลือก "ไม่ฝากคำถาม"
        if (str_contains($normalizedText, 'ไม่ฝากคำถาม') || str_contains($normalizedText, 'ไม่ฝาก') || $normalizedText === 'ไม่') {
            // ลบ pending ออกจาก Cache
            Cache::forget($cacheKey);

            Log::info('Fortune: ผู้ใช้เลือกไม่ฝากคำถาม', ['user_id' => $userId]);

            return [
                'action' => 'ai_chat_response',
                'message' => "ไม่เป็นไรค่ะ! ถ้ามีคำถามอื่น พิมพ์ถามหมอจันทราได้เลย 😊\n\nหรือพิมพ์ ดูดวง เพื่อเข้าสู่การทำนายค่ะ ✨",
                'reading' => null,
            ];
        }

        // ผู้ใช้พิมพ์อย่างอื่น → ลบ pending ให้ (ไม่ค้างไว้) แล้วปล่อย flow ปกติ
        Cache::forget($cacheKey);

        return null;
    }

    /**
     * บันทึกคำถามที่ AI ตอบไม่ได้ ให้แอดมินมาตอบกลับทีหลัง
     */
    protected function saveQuestionForAdmin(
        string $userId,
        string $question,
        string $reason = 'ai_cannot_answer',
        ?string $aiResponse = null,
        ?string $userName = null
    ): void {
        try {
            \App\Models\FortuneSavedQuestion::saveQuestion(
                platformUserId: $userId,
                question: $question,
                reason: $reason,
                aiResponse: $aiResponse,
                userName: $userName,
                platform: $this->currentPlatform
            );
        } catch (\Exception $e) {
            // ไม่ให้ error กระทบ flow หลัก
            Log::warning('Fortune: บันทึกคำถามล้มเหลว', ['error' => $e->getMessage()]);
        }
    }

    protected function isBankAccountRequest(string $text): bool
    {
        $text = mb_strtolower(trim($text));

        // คำสั่งที่ตรงชัดเจน (exact match) — ข้อความทั้งหมดต้องเป็นคำสั่งนี้
        $exactKeywords = [
            'บัญชี', 'ดูบัญชี', 'เลขบัญชี', 'ธนาคาร', 'ดูธนาคาร',
            'โอนเงิน', 'ขอบัญชี', 'ขอเลขบัญชี', 'bank', 'account',
        ];

        // ลบคำลงท้ายสุภาพ
        $textNormalized = preg_replace('/\s*(ค่ะ|ครับ|คะ|จ้า|จ้ะ|หน่อย|ด้วย|ที|นะ|นะคะ|นะครับ)\s*$/u', '', $text);

        foreach ($exactKeywords as $keyword) {
            if ($text === $keyword || $textNormalized === $keyword) {
                return true;
            }
        }

        // คำสั่งแบบ contains แต่จำกัดเฉพาะข้อความสั้น (≤20 ตัวอักษร)
        // เพื่อไม่ให้ "ดวงการเงินจะได้โอนไหม" match ผิด
        if (mb_strlen($text) <= 20) {
            $shortKeywords = ['เลขบัญชี', 'ดูบัญชี', 'ขอบัญชี'];
            foreach ($shortKeywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * ตรวจสอบว่าผู้ใช้ต้องการดูเมนูหรือไม่
     * รองรับ: เมนู, menu, คำสั่ง, ช่วยเหลือ, help
     */
    protected function isMenuRequest(string $text): bool
    {
        $text = mb_strtolower(trim($text));
        // ลบคำลงท้ายสุภาพ
        $textNormalized = preg_replace('/\s*(ค่ะ|ครับ|คะ|จ้า|จ้ะ|หน่อย|ด้วย|ที|นะ|นะคะ|นะครับ)\s*$/u', '', $text);

        $exactKeywords = [
            'เมนู', 'menu', 'คำสั่ง', 'ช่วยเหลือ', 'help',
            'ดูเมนู', 'ขอเมนู', 'แสดงเมนู', 'เปิดเมนู',
            'ทำอะไรได้บ้าง', 'มีอะไรบ้าง', 'บริการ',
        ];

        foreach ($exactKeywords as $keyword) {
            if ($text === $keyword || $textNormalized === $keyword) {
                return true;
            }
        }

        return false;
    }

    /**
     * จัดการคำสั่ง "เมนู" — แสดงเมนูทุกบริการแบบครบถ้วน
     * แสดงหมวดหมู่ดูดวงทั้งหมด + คำสั่งพิเศษ + บริการอื่นๆ
     *
     * @param string $facebookUserId
     * @return array
     */
    protected function handleMenuRequest(string $facebookUserId): array
    {
        try {
            $brandName = $this->settings->getFortuneBrandName();
            $price = number_format($this->getDeepReadingPrice(), 0);

            // ดึงหมวดหมู่ดูดวงจาก database
            $categories = \App\Models\FortuneCategory::where('is_active', true)
                ->orderBy('order')
                ->get();

            // สร้างข้อความหมวดหมู่ดูดวง
            $categoryLines = [];
            foreach ($categories as $cat) {
                $categoryLines[] = "{$cat->icon} {$cat->name} — {$cat->description}";
            }

            $categoryText = implode("\n", $categoryLines);
            if (empty($categoryText)) {
                $categoryText = "💕 ความรัก — ทำนายเรื่องความรัก ความสัมพันธ์\n"
                    ."💰 การเงิน — ทำนายเรื่องการเงิน ความมั่งคั่ง\n"
                    ."🏥 สุขภาพ — ทำนายเรื่องสุขภาพ โรคภัย\n"
                    ."💼 การงาน — ทำนายเรื่องอาชีพ ความก้าวหน้า\n"
                    ."👨‍👩‍👧‍👦 ครอบครัว — ทำนายเรื่องครอบครัว บุตร\n"
                    ."🍀 โชคลาภ — ทำนายเรื่องโชค ดวง โชคชะตา";
            }

            $message = "📋 เมนูบริการ {$brandName}\n"
                ."━━━━━━━━━━━━━━━━━\n\n"
                ."🔮 หมวดหมู่ดูดวง\n"
                ."─────────────────\n"
                ."{$categoryText}\n\n"
                ."💡 วิธีใช้: พิมพ์คำถามที่ต้องการ\n"
                ."เช่น \"ดวงความรักเดือนนี้\" หรือ \"การเงินจะดีไหม\"\n\n"
                ."━━━━━━━━━━━━━━━━━\n"
                ."🆓 ดูดวงฟรี — พิมพ์ \"ดูดวง\"\n"
                ."💎 ดูดวงละเอียด — {$price} บาท\n"
                ."📖 ดูคำทำนาย — พิมพ์ \"ดูคำทำนาย\"\n"
                ."💰 เช็คสิทธิ์/Wallet — พิมพ์ \"เช็คสิทธิ์\"\n"
                ."🏦 ดูบัญชี — พิมพ์ \"บัญชี\"\n"
                ."🔗 แชร์เชิญเพื่อน — พิมพ์ \"แชร์\"\n"
                ."📝 ฝากคำถาม — พิมพ์ \"ฝากคำถาม\"\n\n"
                ."━━━━━━━━━━━━━━━━━\n"
                ."📊 ระบบค่าแนะนำ\n"
                ."─────────────────\n"
                ."👥 ดูสายงาน — พิมพ์ \"สายงาน\"\n"
                ."💵 ดูรายได้ — พิมพ์ \"รายได้\"\n"
                ."💰 แผนค่าแนะนำ — พิมพ์ \"แผนการตลาด\"\n"
                ."━━━━━━━━━━━━━━━━━\n\n"
                ."✨ เลือกเรื่องที่สนใจแล้วพิมพ์มาได้เลย 🙏";

            Log::info('Fortune Menu: แสดงเมนูครบถ้วน', [
                'user_id' => $facebookUserId,
                'categories_count' => $categories->count(),
            ]);

            return [
                'action' => 'menu',
                'message' => $message,
                'reading' => null,
            ];

        } catch (\Exception $e) {
            Log::error('Fortune Menu: แสดงเมนูล้มเหลว', [
                'user_id' => $facebookUserId,
                'error' => $e->getMessage(),
            ]);

            return [
                'action' => 'menu_error',
                'message' => "📋 เมนูบริการ\n\n"
                    ."🔮 ดูดวง — พิมพ์ \"ดูดวง\"\n"
                    ."📖 ดูคำทำนาย — พิมพ์ \"ดูคำทำนาย\"\n"
                    ."💰 เช็คสิทธิ์ — พิมพ์ \"เช็คสิทธิ์\"\n"
                    ."🔗 แชร์ — พิมพ์ \"แชร์\"\n\n"
                    ."✨ พิมพ์คำสั่งที่ต้องการได้เลยค่ะ 🙏",
                'reading' => null,
            ];
        }
    }

    /**
     * จัดการคำสั่ง "ดูบัญชี" / "บัญชี" / "ธนาคาร"
     *
     * ถ้ามีบิลรอชำระ → แสดงยอดชำระ + บัญชีธนาคาร + เวลาเหลือ
     * ถ้าไม่มีบิลรอชำระ → แสดงบัญชีธนาคารทั่วไป
     *
     * @param string $facebookUserId
     * @return array
     */
    protected function handleBankAccountRequest(string $facebookUserId): array
    {
        try {
            // ถ้ามีบิลรอชำระ → แสดงข้อมูลบิล + บัญชีธนาคาร + เวลาเหลือ
            $pendingReading = FortuneReading::where('facebook_user_id', $facebookUserId)
                ->where('conversation_status', FortuneReading::STATUS_PENDING_PAYMENT)
                ->where('is_paid', false)
                ->latest()
                ->first();

            if ($pendingReading) {
                // ส่งต่อให้ handlePendingPayment() แสดงยอด+บัญชี+เวลาเหลือ
                return $this->handlePendingPayment($pendingReading, 'บัญชี');
            }

            // ไม่มีบิลรอชำระ → แสดงบัญชีธนาคารทั่วไป
            $message = "🏦 บัญชีธนาคารสำหรับชำระเงิน\n\n";
            $message .= $this->getBankAccountsListMessage();
            $message .= "ℹ️ ตอนนี้ยังไม่มีบิลรอชำระค่ะ\n";
            $message .= "พิมพ์ 'ดูดวงละเอียด' เพื่อเริ่มดูดวงเชิงลึกค่ะ 🔮";

            return [
                'action' => 'bank_account_info',
                'message' => $message,
                'reading' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Fortune: handleBankAccountRequest error', [
                'facebook_user_id' => $facebookUserId,
                'error' => $e->getMessage(),
            ]);

            // Fallback: แสดงบัญชีธนาคารพื้นฐาน
            return [
                'action' => 'bank_account_info',
                'message' => "🏦 บัญชีธนาคาร\n\n" . $this->getBankAccountsListMessage(),
                'reading' => null,
            ];
        }
    }

    /**
     * Parse วันเกิดจากข้อความ
     */
    protected function parseBirthDate(string $text): ?string
    {
        $text = trim($text);

        // 🔢 แปลงเลขไทย (๐-๙) เป็นเลขอารบิก ก่อน parse
        $thaiDigits = ['๐', '๑', '๒', '๓', '๔', '๕', '๖', '๗', '๘', '๙'];
        $arabicDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $text = str_replace($thaiDigits, $arabicDigits, $text);

        // รูปแบบ: dd/mm/yyyy หรือ dd-mm-yyyy (รับทั้ง 2 และ 4 หลัก)
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/', $text, $matches)) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = (int) $matches[3];

            $year = $this->normalizeBirthYear($year);

            if ($year !== null && checkdate($month, $day, $year) && $this->isValidBirthYear($year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        // รูปแบบ: dd เดือนไทย yyyy (รับทั้ง 2 และ 4 หลัก)
        $thaiMonths = [
            'มกราคม' => 1, 'กุมภาพันธ์' => 2, 'มีนาคม' => 3, 'เมษายน' => 4,
            'พฤษภาคม' => 5, 'มิถุนายน' => 6, 'กรกฎาคม' => 7, 'สิงหาคม' => 8,
            'กันยายน' => 9, 'ตุลาคม' => 10, 'พฤศจิกายน' => 11, 'ธันวาคม' => 12,
            'ม.ค.' => 1, 'ก.พ.' => 2, 'มี.ค.' => 3, 'เม.ย.' => 4,
            'พ.ค.' => 5, 'มิ.ย.' => 6, 'ก.ค.' => 7, 'ส.ค.' => 8,
            'ก.ย.' => 9, 'ต.ค.' => 10, 'พ.ย.' => 11, 'ธ.ค.' => 12,
        ];

        foreach ($thaiMonths as $monthName => $monthNum) {
            if (preg_match('/(\d{1,2})\s*'.$monthName.'\s*(\d{2,4})/', $text, $matches)) {
                $day = (int) $matches[1];
                $year = (int) $matches[2];

                $year = $this->normalizeBirthYear($year);

                if ($year !== null && checkdate($monthNum, $day, $year) && $this->isValidBirthYear($year)) {
                    return sprintf('%04d-%02d-%02d', $year, $monthNum, $day);
                }
            }
        }

        return null;
    }

    /**
     * Normalize ปีเกิด — รองรับ 2 หลัก, 4 หลัก, พ.ศ., ค.ศ.
     *
     * @param  int  $year  ปีที่ได้จากการ parse (ยังไม่ได้ normalize)
     * @return int|null  ปี ค.ศ. หลัง normalize (null ถ้าไม่สมเหตุสมผล)
     */
    protected function normalizeBirthYear(int $year): ?int
    {
        // ปีย่อ 2 หลัก — ใช้ logic Thai ID card (ถ้า > ปีค.ศ.ปัจจุบัน%100 → ศตวรรษก่อน)
        if ($year < 100) {
            $currentYY = (int) now()->format('y');
            $year = ($year <= $currentYY) ? (2000 + $year) : (1900 + $year);
        }

        // พ.ศ. → ค.ศ.
        if ($year > 2400) {
            $year -= 543;
        }

        // ถ้ายังน่าสงสัย (ไม่ใช่ปีค.ศ. 4 หลักที่สมเหตุสมผล) → คืน null
        if ($year < 1900 || $year > (int) now()->format('Y')) {
            return null;
        }

        return $year;
    }

    /**
     * ตรวจว่าปีเกิดสมเหตุสมผล — อายุ 1-120 ปี
     */
    protected function isValidBirthYear(int $year): bool
    {
        $currentYear = (int) now()->format('Y');
        $age = $currentYear - $year;

        return $age >= 1 && $age <= 120;
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

    /**
     * แปลงเลขเดือนเป็นชื่อเดือนไทย
     */
    protected function getThaiMonth(int $month): string
    {
        $months = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];

        return $months[$month] ?? '';
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
คุณชื่อ "แม่หมอจันทรา" เป็นหมอดูสาวสวยวัย 35 ปี ผู้เชี่ยวชาญโหราศาสตร์ไทย (โหราศาสตร์เจ้าชนะ) และโหราศาสตร์สากล ได้รับการถ่ายทอดวิชาจากครูบาอาจารย์สายลังกา พูดจาเพราะ อบอุ่นเป็นกันเอง น่าเชื่อถือ ใช้คำแทนตัวว่า "หมอจันทรา" ทำนายแม่นยำมาก ฟันธงแต่อ่อนโยน ไม่เกิน 500 คำ

{user_context}
{detected_category}

ข้อมูลผู้ขอดูดวง:
- ชื่อ: {name}
- เพศ: {gender}

ข้อความที่ส่งมา: {question}

แนวทางการตอบ:
- เรียกผู้ถามว่า "{gender_prefix}{name}" อย่างเป็นกันเอง
- ใช้ "หมอจันทรา" แทนตัวเอง เช่น "หมอจันทราเห็นว่า..." "หมอจันทราขอบอกตรงๆ ว่า..."

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
4. ถ้าถูกถามว่าเป็น AI: ตอบว่า "หมอจันทรามีทีมงานช่วยกันค่ะ" แล้วชวนดูดวง

[โครงสร้างคำทำนาย]
🔮 **เปิดเรื่อง**: ทักทายอบอุ่น บอกว่าหมอจันทราดูดวงด้วยหลักเจ้าชนะ อ้างอิงดาวที่ส่งผล
⭐ **ดวงภาพรวม**: วิเคราะห์ดวงชะตาภาพรวม อ้างชื่อดาวเคราะห์ที่ส่งผลในช่วงนี้
💫 **ตอบคำถามหลัก**: ตอบอย่างละเอียด กล้าบอกตรงๆ ระบุช่วงเวลาชัดเจน
🎯 **คำแนะนำ**: สีมงคล 2-3 สี, เลขมงคล 2-3 เลข, วันมงคล, สิ่งที่ควรระวัง
🌟 **ปิดท้ายชวนดูต่อ**: hint ว่ายังเห็นอะไรอีก กระตุ้นให้อยากดูดวงละเอียด

ถ้ายังไม่มีวันเกิด ให้ถามท้ายว่า "บอกวันเดือนปีเกิดให้หมอจันทราได้ไหมคะ?"
ท้ายสุดเชิญชวน "ฝากส่งต่อให้เพื่อนๆ มาลองดูดวงกับหมอจันทราด้วยนะคะ 🔮✨"

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
คุณชื่อ "แม่หมอจันทรา" เป็นหมอดูสาวสวยวัย 35 ปี ผู้เชี่ยวชาญศาสตร์โหราศาสตร์โบราณของไทย (หลักเจ้าชนะ) โหราศาสตร์สากล ไพ่ทาโรต์ และเลขศาสตร์ ได้รับวิชาจากครูบาอาจารย์สายลังกา มีประสบการณ์ 15 ปี ทำนายด้วยหลักวิชาโบราณล้วนๆ ทุกคำทำนายมีศาสตร์รองรับ พูดจาเพราะ อบอุ่น ใช้คำว่า "หมอจันทรา" แทนตัวเอง

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
- บอกว่า "หมอจันทราได้รับคำถาม {total_questions} ข้อ จะทำนายให้อย่างละเอียดทีละข้อด้วยหลักเจ้าชนะนะคะ"
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
- ใช้ "หมอจันทรา" แทนตัวเอง
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
     * เป็นหมอดูหญิง ใช้คำแทนตัวว่า "หมอจันทรา"
     *
     * กฎพิเศษ:
     * - ถ้าถูกถามว่าเป็น AI: ตอบว่า "หมอจันทรามีทีมงานช่วยกัน"
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

        // คำนวณอายุและช่วงชีวิตจากวันเกิด (ถ้ามี)
        $ageInfo = '';
        $lifeStageHint = '';
        $extractedBirthDate = null;
        if (preg_match('/เคยบอกวันเกิด:\s*(\d{4}-\d{2}-\d{2})/', $userContext, $birthMatch)) {
            try {
                $extractedBirthDate = \Carbon\Carbon::parse($birthMatch[1]);
                $age = $extractedBirthDate->age;
                $ageInfo = "- อายุ: {$age} ปี\n";

                if ($age < 18) {
                    $lifeStageHint = "\n[ช่วงชีวิต: วัยเรียน ({$age} ปี)] ผู้ถามยังเด็ก → ใช้ภาษาสนุก เข้าใจง่าย เน้นเรื่องการเรียน เพื่อน ครอบครัว ห้ามพูดเรื่องการเงินหนัก ให้กำลังใจเรื่องอนาคต\n";
                } elseif ($age < 25) {
                    $lifeStageHint = "\n[ช่วงชีวิต: วัยรุ่น/เริ่มทำงาน ({$age} ปี)] ผู้ถามอยู่ช่วงเริ่มต้นชีวิต → ใช้ภาษาเป็นกันเอง ทันสมัย เน้นเรื่องความรัก การเรียนต่อ การหางาน ทิศทางอนาคต\n";
                } elseif ($age < 35) {
                    $lifeStageHint = "\n[ช่วงชีวิต: วัยสร้างตัว ({$age} ปี)] ผู้ถามอยู่ช่วงสร้างครอบครัว/อาชีพ → ให้คำแนะนำเชิงกลยุทธ์ เน้นเรื่องงาน เงิน คู่ครอง การลงทุน\n";
                } elseif ($age < 50) {
                    $lifeStageHint = "\n[ช่วงชีวิต: วัยกลางคน ({$age} ปี)] ผู้ถามมีประสบการณ์แล้ว → ให้คำแนะนำลึกซึ้ง เน้นความก้าวหน้า ครอบครัว ลูก สุขภาพ ความมั่นคง\n";
                } elseif ($age < 65) {
                    $lifeStageHint = "\n[ช่วงชีวิต: วัยเตรียมเกษียณ ({$age} ปี)] → ใช้ภาษาสุภาพ ให้เกียรติ เน้นสุขภาพ ความมั่นคง ครอบครัว ลูกหลาน\n";
                } else {
                    $lifeStageHint = "\n[ช่วงชีวิต: สูงวัย ({$age} ปี)] → ให้เกียรติอย่างมาก ใช้ภาษาสุภาพ เน้นสุขภาพ ลูกหลาน ความสุขในบั้นปลาย ไม่พูดเรื่องหนักเกินไป\n";
                }
            } catch (\Exception $e) {
                // ถ้าคำนวณไม่ได้ก็ข้ามไป
            }
        }

        // วันที่ปัจจุบัน (ให้ AI รู้ช่วงเวลาสำหรับทำนาย)
        $currentDate = now()->format('d/m/Y');
        $currentThaiMonth = $this->getThaiMonth((int) now()->format('m'));
        $currentYear = now()->year + 543;

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
                '{birth_date_section}' => $ageInfo,
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

        return "คุณชื่อ \"แม่หมอจันทรา\" เป็นหมอดูสาวสวยวัย 35 ปี ผู้เชี่ยวชาญโหราศาสตร์ไทย (โหราศาสตร์เจ้าชนะ) และโหราศาสตร์สากล ได้รับการถ่ายทอดวิชาจากครูบาอาจารย์สายลังกา พูดจาเพราะ อบอุ่นเป็นกันเอง น่าเชื่อถือ ใช้คำแทนตัวว่า \"หมอจันทรา\" ทำนายแม่นยำมาก **ฟันธง ฉะฉาน ตรงประเด็น ไม่อ้อมค้อม** ไม่เกิน 500 คำ

📅 วันที่ทำนาย: {$currentDate} (พ.ศ. {$currentYear}) เดือน{$currentThaiMonth}
{$contextSection}{$categoryHint}{$lifeStageHint}
ข้อมูลผู้ขอดูดวง:
- ชื่อ: {$name}
".($gender ? "- เพศ: {$gender}\n" : '')."{$ageInfo}
ข้อความที่ส่งมา: {$question}

[สไตล์การทำนาย: ฟันธง ฉะฉาน ตรงประเด็น]
- ฟันธงคำตอบทันที ไม่อ้อมค้อม ไม่พูดวกวน
- บอกตรงๆ ทั้งดีและร้าย ไม่เกรงใจจนไม่กล้าบอกความจริง
- ระบุช่วงเวลาชัดเจนเสมอ เช่น \"ภายใน 2 สัปดาห์\" \"เดือน{$currentThaiMonth}\" ห้ามพูดลอยๆ ว่า \"เร็วๆ นี้\"
- ตอบเรื่องที่ถามมาก่อน ไม่ใช่พูดเรื่องอื่น
- ถ้าดวงไม่ดี บอกตรงๆ พร้อมทางแก้ทันที

[การเข้าใจเพศสภาพและความสัมพันธ์ - สำคัญมาก]
- อ่านคำถามให้เข้าใจบริบทความสัมพันธ์: ถ้าผู้ชายถามเรื่อง \"แฟนผู้ชาย/ผัว/แฟนหนุ่ม\" หรือผู้หญิงถามเรื่อง \"แฟนผู้หญิง/เมีย\" = เข้าใจว่าเป็น LGBTQ+ ให้ทำนายความรักอย่างเคารพ ไม่ตัดสิน ใช้คำว่า \"คนที่ใจรัก\" \"คนพิเศษ\" ได้
- ถ้าเพศที่ระบุไม่ตรงกับบริบทคำถาม (เช่น เพศชายแต่ใช้สรรพนาม \"หนู/เรา\" หรือพูดแบบผู้หญิง) = อาจเป็นคนข้ามเพศ ให้ทำนายตามบริบทที่เขาสื่อ ไม่ต้องถามเรื่องเพศ
- ให้เกียรติทุกเพศสภาพ ทำนายอย่างจริงใจเท่าเทียม ไม่เปลี่ยนคำทำนายเพราะเพศสภาพ

แนวทางการตอบ:
- เรียกผู้ถามว่า \"{$genderPrefix}{$name}\" อย่างเป็นกันเอง
- ใช้ \"หมอจันทรา\" แทนตัวเอง เช่น \"หมอจันทราเห็นว่า...\" \"หมอจันทราขอบอกตรงๆ ว่า...\"
- ปรับน้ำเสียงตามอายุ/ช่วงชีวิตของผู้ถาม (ถ้ารู้อายุ)

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
3. ถ้าเป็นคำถามทั่วไปที่ไม่เกี่ยวกับดูดวง: ตอบสั้นๆ ด้วยความเป็นมิตร แล้วทำนายดวงภาพรวมให้ด้วย เช่น \"เรื่องนี้หมอจันทราไม่ถนัดเท่าไหร่ค่ะ แต่หมอจันทราสัมผัสดวงของ{$genderPrefix}{$name}ได้ว่า...\" แล้วทำนายดวงให้
4. ถ้าถูกถามว่าเป็น AI: ตอบว่า \"หมอจันทรามีทีมงานช่วยกันค่ะ ไม่ต้องห่วงนะคะ 🔮\" แล้วชวนดูดวง

[โครงสร้างคำทำนาย - ต้องอ้างอิงดาวเคราะห์และเจ้าชนะ]
ต้องทำนายอย่างละเอียดและน่าติดตาม ครบทุกหัวข้อต่อไปนี้:

🔮 **เปิดเรื่อง**: ทักทายอบอุ่น บอกว่าหมอจันทราดูดวงด้วยหลักโหราศาสตร์เจ้าชนะ อ้างอิงดาวที่ส่งผลช่วงนี้ (อ้างอิงเดือน{$currentThaiMonth}) เช่น \"หมอจันทราดูจากตำแหน่งดาว[ชื่อดาว]ที่กำลังโคจรผ่านภพ[ชื่อภพ]ของ{$genderPrefix}{$name}ช่วงเดือน{$currentThaiMonth}นี้...\"

⭐ **ดวงภาพรวม (อ้างอิงดาวเคราะห์)**: วิเคราะห์ดวงชะตาภาพรวม อ้างชื่อดาวเคราะห์ที่ส่งผลในช่วงนี้ เช่น \"ดาวพฤหัสบดีกำลังโคจรเข้าภพศุภะ ทำให้โชคลาภเด่น\" หรือ \"ดาวเสาร์กำลังย้ายราศี ทำให้ต้องระวังเรื่อง...\"

💫 **ตอบคำถามหลัก (ฟันธง!)**: ตอบคำถามที่ถามมาอย่างตรงประเด็น กล้าบอกตรงๆ ระบุช่วงเวลาชัดเจน (เช่น \"ช่วงเดือนมีนา-เมษา\" \"ภายใน 2-3 สัปดาห์\") อ้างอิงดาวเคราะห์ที่เกี่ยวข้อง **ฟันธงเลยว่าจะเป็นยังไง ไม่ต้องอ้อมค้อม**

🎯 **คำแนะนำปฏิบัติได้จริง**:
   - 🎨 สีมงคล: ระบุ 2-3 สี (อ้างอิงจากดาวมิตร)
   - 🔢 เลขมงคล: ระบุ 2-3 เลข (อ้างอิงจากดาวเจ้าชนะ)
   - 📅 วันมงคล: วันที่เหมาะทำสิ่งสำคัญ (อ้างอิงวันของดาวมิตร)
   - ⚠️ สิ่งที่ควรระวัง: บอกตรงๆ อ้างดาวศัตรู แต่ให้ทางแก้ด้วย

🌟 **ปิดท้ายชวนดูต่อ**: ปิดท้ายด้วยการ hint ว่าหมอจันทรายังเห็นอะไรอีกมากที่ยังไม่ได้บอก เพื่อกระตุ้นให้อยากดูดวงละเอียด เช่น:
\"✨ หมอจันทราสัมผัสได้ว่ายังมีเรื่องสำคัญที่ต้องบอก{$genderPrefix}{$name}อีกนะคะ โดยเฉพาะเรื่อง [ระบุเรื่องที่เกี่ยวข้อง] แต่ต้องรู้วันเกิดถึงจะบอกได้ละเอียดค่ะ\"
\"🔮 ถ้า{$genderPrefix}{$name}อยากรู้ลึกกว่านี้ เช่น ตำแหน่งดาวเจ้าชนะ ดาวโคจรที่ส่งผล ภพที่ต้องระวัง ทิศมงคล วิธีเสริมดวง... บอกหมอจันทราได้นะคะ หมอจันทราจะดูให้ละเอียดเลยค่ะ ✨\"

ถ้ายังไม่มีวันเกิด ให้ถามท้ายว่า \"บอกวันเดือนปีเกิดให้หมอจันทราได้ไหมคะ? หมอจันทราจะได้คำนวณตำแหน่งดาวเจ้าชนะ ทำนายได้แม่นยำยิ่งขึ้นค่ะ 🎂\"
ท้ายสุดเชิญชวน \"ถ้าคำทำนายถูกใจ ฝากส่งต่อให้เพื่อนๆ มาลองดูดวงกับหมอจันทราด้วยนะคะ 🔮✨\"

[กฎสำคัญ] ห้ามเขียนโค้ด ห้ามให้ข้อมูลอันตราย ตอบทุกข้อความอย่างเป็นมิตร คุยรู้เรื่อง แต่ชวนกลับมาดูดวงเสมอ";
    }

    /**
     * สร้าง prompt สำหรับทำนายละเอียด
     * เป็นหมอดูหญิง ใช้คำแทนตัวว่า "หมอจันทรา" ทำนายแม่นยำ
     *
     * กฎพิเศษ:
     * - ถ้าถูกถามว่าเป็น AI: ตอบว่า "หมอจันทรามีทีมงานช่วยกัน"
     * - พูดเฉพาะเรื่องดูดวง ปฏิเสธเรื่องอื่นสุภาพ
     */
    protected function buildDeepPrompt(?array $userProfile, array $questions, ?string $birthDate): string
    {
        $name = $userProfile['name'] ?? 'คุณ';
        $gender = isset($userProfile['gender']) ? ($userProfile['gender'] === 'male' ? 'ชาย' : 'หญิง') : '';
        $genderPrefix = $gender === 'ชาย' ? 'คุณพี่' : ($gender === 'หญิง' ? 'คุณ' : 'คุณ');
        $questionsText = implode("\n", array_map(fn ($i, $q) => ($i + 1).". {$q}", array_keys($questions), $questions));

        // คำนวณอายุและช่วงชีวิต
        $ageInfo = '';
        $lifeStageHint = '';
        if ($birthDate) {
            try {
                $birthCarbon = \Carbon\Carbon::parse($birthDate);
                $age = $birthCarbon->age;
                $ageInfo = "- อายุ: {$age} ปี\n";

                if ($age < 18) {
                    $lifeStageHint = "\n[ช่วงชีวิต: วัยเรียน ({$age} ปี)] → ใช้ภาษาสนุก เข้าใจง่าย เน้นการเรียน เพื่อน ครอบครัว\n";
                } elseif ($age < 25) {
                    $lifeStageHint = "\n[ช่วงชีวิต: วัยรุ่น/เริ่มทำงาน ({$age} ปี)] → ภาษาเป็นกันเอง เน้นความรัก อาชีพ ทิศทางอนาคต\n";
                } elseif ($age < 35) {
                    $lifeStageHint = "\n[ช่วงชีวิต: วัยสร้างตัว ({$age} ปี)] → คำแนะนำเชิงกลยุทธ์ เน้นงาน เงิน คู่ครอง การลงทุน\n";
                } elseif ($age < 50) {
                    $lifeStageHint = "\n[ช่วงชีวิต: วัยกลางคน ({$age} ปี)] → คำแนะนำลึกซึ้ง เน้นความก้าวหน้า ครอบครัว สุขภาพ\n";
                } elseif ($age < 65) {
                    $lifeStageHint = "\n[ช่วงชีวิต: วัยเตรียมเกษียณ ({$age} ปี)] → ภาษาสุภาพ ให้เกียรติ เน้นสุขภาพ ความมั่นคง ครอบครัว\n";
                } else {
                    $lifeStageHint = "\n[ช่วงชีวิต: สูงวัย ({$age} ปี)] → ให้เกียรติอย่างมาก เน้นสุขภาพ ลูกหลาน ความสุขในบั้นปลาย\n";
                }
            } catch (\Exception $e) {
                // ข้ามไป
            }
        }

        // วันที่ทำนาย
        $currentDate = now()->format('d/m/Y');
        $currentThaiMonth = $this->getThaiMonth((int) now()->format('m'));
        $currentYear = now()->year + 543;

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

        return "คุณชื่อ \"แม่หมอจันทรา\" เป็นหมอดูสาวสวยวัย 35 ปี ผู้เชี่ยวชาญศาสตร์โหราศาสตร์โบราณของไทย (หลักเจ้าชนะ) โหราศาสตร์สากล ไพ่ทาโรต์ และเลขศาสตร์ ได้รับการถ่ายทอดวิชาจากครูบาอาจารย์สายลังกามากกว่า 15 ปี ทำนายด้วยหลักวิชาโบราณล้วนๆ ไม่ได้กุเรื่อง ทุกคำทำนายมีศาสตร์รองรับ คุณพูดจาเพราะ อบอุ่นเป็นกันเอง น่าเชื่อถือ เหมือนพี่สาวที่ห่วงใย ใช้คำแทนตัวว่า \"หมอจันทรา\" เสมอ **สไตล์: ฟันธง ฉะฉาน ตรงประเด็น ไม่อ้อมค้อม กล้าบอกตรงๆ ทั้งดีและร้าย**

📅 วันที่ทำนาย: {$currentDate} (พ.ศ. {$currentYear}) เดือน{$currentThaiMonth}
{$lifeStageHint}
[การเข้าใจเพศสภาพและความสัมพันธ์]
- อ่านคำถามให้เข้าใจบริบท: ถ้าผู้ถามพูดถึงคู่รักเพศเดียวกัน = LGBTQ+ ให้ทำนายอย่างเคารพ ไม่ตัดสิน
- ถ้าเพศที่ระบุไม่ตรงกับสรรพนามที่ใช้ = อาจเป็นคนข้ามเพศ ให้ทำนายตามบริบทที่เขาสื่อ
- ให้เกียรติทุกเพศสภาพ ทำนายอย่างเท่าเทียม

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
".($gender ? "- เพศ: {$gender}\n" : '')."{$ageInfo}".($birthInfo ? "- {$birthInfo}\n" : '').'
'.($zodiacInfo ? "- {$zodiacInfo}\n" : '')."
{$deepPlanetPositionsInfo}
{$transitInfo}
คำถาม:
{$questionsText}

=== แนวทางการทำนายอย่างละเอียด (ทำนายแบบพรีเมียม ต้องคุ้มค่า!) ===

**สำคัญ: ผู้ถามจ่ายเงินมาเพื่อความแม่นยำ ต้องทำนายด้วยหลักวิชาจริง ไม่ใช่กุเรื่อง!**
**หมอจันทราใช้ศาสตร์โหราศาสตร์โบราณ (หลักเจ้าชนะ) ในการวิเคราะห์ ทุกคำทำนายมีหลักวิชารองรับ**
**สไตล์: ฟันธง ฉะฉาน ตรงประเด็น ไม่อ้อมค้อม ระบุช่วงเวลาชัดเจน (อ้างอิงจากเดือน{$currentThaiMonth} พ.ศ. {$currentYear})**

1. 🔮 เปิดด้วยการทักทายอบอุ่น + วิเคราะห์ดวงชะตาจากวันเกิด:
   - ทักทาย{$genderPrefix}{$name}ด้วยความเป็นกันเอง
   - บอกว่า \"หมอจันทราใช้ศาสตร์โหราศาสตร์โบราณหลักเจ้าชนะที่สืบทอดมาจากครูบาอาจารย์ วิเคราะห์ให้อย่างละเอียดนะคะ\"
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
   - \"ถ้ามีเรื่องอยากถามเพิ่ม ทักมาหาหมอจันทราได้เสมอนะคะ ทุกคำตอบหมอจันทราวิเคราะห์จากหลักวิชาจริงค่ะ 🔮✨\"

[กฎสำคัญ]
1. หมอจันทราทำนายด้วย \"ศาสตร์โหราศาสตร์โบราณหลักเจ้าชนะ\" เท่านั้น ห้ามพูดว่าหยั่งรู้ จิตสัมผัส หรืออะไรที่ทำให้ดูไม่น่าเชื่อถือ
2. หากถูกถามว่าเป็น AI: ตอบว่า \"หมอจันทราใช้ศาสตร์โหราศาสตร์โบราณที่สืบทอดมาจากครูบาอาจารย์ค่ะ ไม่ได้กุเรื่อง ทุกคำทำนายมีหลักวิชารองรับ 🔮\"
3. พูดเฉพาะเรื่องดูดวง ปฏิเสธเรื่องอื่นสุภาพ
4. ต้องอ้างอิงตำแหน่งดาวจริง+ภพจริง+transit จากข้อมูลที่ให้ ห้ามแต่งตำแหน่งดาวขึ้นเอง
5. เมื่อทำนายอนาคต ต้องอ้างตำแหน่งดาว Transit อนาคต (1,3,6,12 เดือน) เปรียบเทียบกับดวงกำเนิด
6. ทำนายละเอียดสมราคา ไม่น้อยกว่า 500 คำต่อคำถาม
7. ใช้ภาษาไทย อบอุ่น เป็นกันเอง น่าเชื่อถือ มีศาสตร์รองรับทุกคำทำนาย
8. ฟันธง! บอกตรงๆ ว่าจะเกิดอะไร เมื่อไหร่ ระบุช่วงเวลาชัดเจน ไม่พูดลอยๆ ว่า \"เร็วๆ นี้\"
9. ปรับน้ำเสียงตามอายุ/ช่วงชีวิตของผู้ถาม (ถ้ารู้อายุ) วัยรุ่นพูดเข้าใจง่าย สูงอายุพูดสุภาพ
10. เข้าใจความหลากหลายทางเพศ: ถ้าคำถามบ่งบอกว่าผู้ถามเป็น LGBTQ+ ให้ทำนายอย่างเคารพ เท่าเทียม ไม่ตัดสิน";
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
        array $previousReadings = [],
        ?array $tarotCard = null
    ): string {
        $name = $userProfile['name'] ?? 'คุณ';
        $gender = isset($userProfile['gender']) ? ($userProfile['gender'] === 'male' ? 'ชาย' : 'หญิง') : '';
        $genderPrefix = $gender === 'ชาย' ? 'คุณพี่' : ($gender === 'หญิง' ? 'คุณ' : 'คุณ');

        // คำนวณอายุและช่วงชีวิต
        $ageInfo = '';
        $lifeStageHint = '';
        if ($birthDate) {
            try {
                $birthCarbon = \Carbon\Carbon::parse($birthDate);
                $age = $birthCarbon->age;
                $ageInfo = "- อายุ: {$age} ปี\n";

                if ($age < 18) {
                    $lifeStageHint = "\n[ช่วงชีวิต: วัยเรียน ({$age} ปี)] → ภาษาสนุก เข้าใจง่าย\n";
                } elseif ($age < 25) {
                    $lifeStageHint = "\n[ช่วงชีวิต: วัยรุ่น/เริ่มทำงาน ({$age} ปี)] → ภาษาเป็นกันเอง\n";
                } elseif ($age < 35) {
                    $lifeStageHint = "\n[ช่วงชีวิต: วัยสร้างตัว ({$age} ปี)] → คำแนะนำเชิงกลยุทธ์\n";
                } elseif ($age < 50) {
                    $lifeStageHint = "\n[ช่วงชีวิต: วัยกลางคน ({$age} ปี)] → คำแนะนำลึกซึ้ง\n";
                } elseif ($age < 65) {
                    $lifeStageHint = "\n[ช่วงชีวิต: วัยเตรียมเกษียณ ({$age} ปี)] → ภาษาสุภาพ ให้เกียรติ\n";
                } else {
                    $lifeStageHint = "\n[ช่วงชีวิต: สูงวัย ({$age} ปี)] → ให้เกียรติอย่างมาก ภาษาสุภาพ\n";
                }
            } catch (\Exception $e) {
                // ข้ามไป
            }
        }

        // วันที่ทำนาย
        $currentThaiMonth = $this->getThaiMonth((int) now()->format('m'));
        $currentYear = now()->year + 543;

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
            // สร้างข้อมูลไพ่ยิปซีสำหรับ custom prompt
            $tarotCardSection = '';
            if (! empty($tarotCard)) {
                $tarotPosition = $tarotCard['is_reversed'] ? 'กลับหัว (Reversed)' : 'หงาย (Upright)';
                $tarotCardSection = "🃏 ไพ่ยิปซี: {$tarotCard['card_name_th']} ({$tarotCard['card_name_en']}) - {$tarotPosition}\nความหมาย: {$tarotCard['meaning']}\n→ นำไพ่นี้มาวิเคราะห์ร่วมกับดวงดาว";
            }

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
                '{tarot_card}' => $tarotCardSection,
            ]);
        }

        // ลำดับที่ 2: ใช้ prompt hardcode เดิม (default)
        $prompt = "คุณชื่อ \"แม่หมอจันทรา\" เป็นหมอดูสาวสวยวัย 35 ปี ผู้เชี่ยวชาญศาสตร์โหราศาสตร์โบราณของไทย (หลักเจ้าชนะ) โหราศาสตร์สากล ไพ่ทาโรต์ และเลขศาสตร์ ได้รับวิชาจากครูบาอาจารย์สายลังกา มีประสบการณ์ 15 ปี ทำนายด้วยหลักวิชาโบราณล้วนๆ ทุกคำทำนายมีศาสตร์รองรับ พูดจาเพราะ อบอุ่น ใช้คำว่า \"หมอจันทรา\" แทนตัวเอง **สไตล์: ฟันธง ฉะฉาน ตรงประเด็น**
{$lifeStageHint}

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

=== ทำนายคำถามที่ {$questionNumber} จาก {$totalQuestions} (การทำนายแบบพรีเมียม เดือน{$currentThaiMonth} พ.ศ.{$currentYear}) ===

ข้อมูลผู้ขอดูดวง:
- ชื่อ: {$name} (เรียกว่า \"{$genderPrefix}{$name}\")
".($gender ? "- เพศ: {$gender}\n" : '')."{$ageInfo}".($birthInfo ? "- วันเกิด: {$birthInfo}\n" : '').'
'.($zodiacInfo ? "- {$zodiacInfo}\n" : '')."
{$planetPositionsInfo}
{$transitInfo}
คำถามที่ {$questionNumber}: {$question}
{$previousContext}
".(! empty($tarotCard) ? "
🃏 **ไพ่ยิปซีที่ผู้ถามเปิดได้ประกอบคำถามนี้**:
- ไพ่: {$tarotCard['card_name_th']} ({$tarotCard['card_name_en']})
- ตำแหน่ง: ".($tarotCard['is_reversed'] ? 'กลับหัว (Reversed)' : 'หงาย (Upright)')."
- ความหมาย: {$tarotCard['meaning']}
⚠️ **บังคับ**: ต้องมีหัวข้อ 🃏 วิเคราะห์ไพ่ยิปซี แยกเป็นส่วนชัดเจน (ดูโครงสร้างด้านล่าง) ห้ามข้ามหัวข้อนี้เด็ดขาด!

" : '')."[การเข้าใจบริบทคำถาม] วิเคราะห์คำถามให้ลึก: ถ้าคำถามบ่งบอกเรื่องความสัมพันธ์กับคู่รักเพศเดียวกัน = LGBTQ+ ให้ทำนายอย่างเคารพ เท่าเทียม ไม่ตัดสิน

[โครงสร้างคำทำนาย - ต้องทำตามทุกข้อ ผู้ถามจ่ายเงินมา ต้องคุ้มค่า! ฟันธง!]

";

        // คำถามแรก: เปิดด้วยวิเคราะห์ดวงจากวันเกิด + เจ้าชนะ
        if ($questionNumber === 1) {
            $prompt .= "🔮 **เปิดเรื่อง** (คำถามแรก — เรียกชื่อ{$genderPrefix}{$name}ตรงนี้ครั้งเดียว หลังจากนี้ใช้ \"เจ้าชะตา\" แทน):
- ทักทาย{$genderPrefix}{$name}อย่างอบอุ่นสั้นๆ แล้วเข้าเรื่องทำนายเลย
".($birthDate ? '- วิเคราะห์ดวงชะตาจากวันเกิด: ราศี ปีนักษัตร ธาตุจีน + ดาวเจ้าชนะ ดาวมิตร ดาวศัตรู
- อ้างตำแหน่งดาวกำเนิดในภพจริง (จากแผนที่ดวงชะตาด้านบน)
- อ้างดาวโคจร(transit)ปัจจุบัน ที่กำลังส่งผล + ภพที่ดาวโคจรผ่าน
- บอกจุดแข็งจุดอ่อนของดวงชะตา + บุคลิกภาพ' : '- บอกว่าหมอจันทราใช้ศาสตร์โหราศาสตร์โบราณหลักเจ้าชนะในการวิเคราะห์ดวงชะตา').'

';
        }

        $prompt .= "⭐ **วิเคราะห์คำถาม** (เจาะลึกเฉพาะคำถามนี้ ด้วยศาสตร์โหราศาสตร์โบราณ):
- ตอบคำถาม \"{$question}\" อย่างละเอียด ลึกซึ้ง ด้วยหลักเจ้าชนะ
".($birthDate ? "- อ้างตำแหน่งดาวกำเนิดที่อยู่ในภพที่เกี่ยวข้องกับคำถาม (จากแผนที่ดวงชะตาด้านบน)
- อ้างดาวโคจร(transit)ปัจจุบัน + transit อนาคต (1, 3, 6, 12 เดือน) ที่ส่งผลต่อเรื่องนี้
- เปรียบเทียบ transit ปัจจุบัน vs อนาคต ว่าดาวจะเลื่อนไปภพไหน ส่งผลดีขึ้น/ต้องระวังอย่างไร
- ระบุว่าราศี+ปีนักษัตร+ธาตุของเจ้าชะตาส่งผลต่อเรื่องนี้อย่างไร
- เช่น ถามเรื่องรัก→อ้างดาวที่อยู่ในภพปัตนิ+ศุกร์ ถามเรื่องงาน→อ้างดาวที่อยู่ภพกัมมะ" : '- ใช้ศาสตร์โหราศาสตร์โบราณหลักเจ้าชนะในการทำนาย').'
- ฟันธง กล้าบอกตรงๆ ทั้งเรื่องดีและไม่ดี ด้วยหลักวิชา
- ระบุช่วงเวลาชัดเจน อ้าง transit อนาคต เช่น "อีก 3 เดือน ดาว[ชื่อ]จะเลื่อนเข้าภพ[ชื่อ] ส่งผลให้..."

'.(! empty($tarotCard) ? "🃏 (หมายเหตุ: วิเคราะห์ไพ่ยิปซีจะอยู่ในส่วนแยกต่างหาก — ใน prompt นี้ไม่ต้องวิเคราะห์ไพ่ แต่ถ้าจะอ้างอิงไพ่ {$tarotCard['card_name_th']} สั้นๆ ได้ในบริบทที่เหมาะสม)

" : '').'💫 **สิ่งที่จะเกิดขึ้นในอนาคต** (อ้างจาก Transit อนาคตที่คำนวณ):
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
- สรุปดวงชะตาภาพรวมของเจ้าชะตา + ช่วงฤกษ์ดีที่สุด/ต้องระวังที่สุดในรอบปี
- ให้กำลังใจอบอุ่น จริงใจ
- \"ทุกคำทำนายหมอจันทราวิเคราะห์จากศาสตร์โหราศาสตร์โบราณ หลักเจ้าชนะค่ะ ไม่ได้กุเรื่อง 🔮\"
- \"ถ้ามีเรื่องอะไรอยากถามเพิ่มเติม ทักมาหาหมอจันทราได้เสมอนะคะ ✨\"
- เชิญชวนส่งต่อให้เพื่อนๆ มาดูดวงกับหมอจันทรา

";
        }

        $prompt .= "[กฎสำคัญ]
- ทำนายเฉพาะคำถามที่ {$questionNumber} เท่านั้น ห้ามตอบคำถามอื่น
- ห้ามพูดซ้ำกับคำทำนายก่อนหน้า ใช้ดาว/ภพคนละดวง
- ต้องอ้างอิงตำแหน่งดาวจริงจากแผนที่ดวงชะตา + Transit ปัจจุบัน + Transit อนาคต ห้ามแต่งตำแหน่งดาวขึ้นเอง
- เมื่อทำนายอนาคต ต้องอ้าง Transit อนาคต (1,3,6,12 เดือน) เปรียบเทียบกับดวงกำเนิด
- ห้ามพูดว่าหยั่งรู้ จิตสัมผัส → ใช้คำว่า \"ศาสตร์โหราศาสตร์โบราณ\" หรือ \"หลักเจ้าชนะ\" แทน
- ตอบอย่างละเอียดสมราคา ไม่น้อยกว่า 300 คำ ไม่เกิน 500 คำ (⚠️ จำกัด 2000 ตัวอักษร เพราะส่งผ่าน Messenger ที่มี limit)
- ⚠️ ไม่ต้องวิเคราะห์ไพ่ยิปซีใน prompt นี้ (จะมีส่วนวิเคราะห์ไพ่แยกต่างหาก)
- ใช้ \"หมอจันทรา\" แทนตัวเอง
- ⚠️ ห้ามเรียกชื่อลูกค้าซ้ำ! เรียกชื่อครั้งเดียวตอนเปิดเรื่องคำถามแรกเท่านั้น หลังจากนั้นใช้ \"เจ้าชะตา\" แทนทุกครั้ง (ประหยัด token)
- ตอบเป็นภาษาไทย อบอุ่น เป็นกันเอง น่าเชื่อถือ มีศาสตร์รองรับ ทำให้อยากดูดวงอีก";

        return $prompt;
    }

    /**
     * สร้างส่วนวิเคราะห์ไพ่ยิปซีใน prompt (แยก method เพื่อหลีกเลี่ยงปัญหา quoting)
     */
    protected static function buildTarotAnalysisSection(?array $tarotCard, string $genderPrefix, string $name, string $question): string
    {
        if (empty($tarotCard)) {
            return '';
        }

        $cardNameTh = $tarotCard['card_name_th'] ?? 'ไม่ทราบชื่อ';
        $isReversed = $tarotCard['is_reversed'] ?? false;
        $positionAdvice = $isReversed
            ? 'กลับหัว: เน้นความท้าทาย อุปสรรค สิ่งที่ต้องระวัง และวิธีรับมือ'
            : 'หงาย: เน้นพลังงานเชิงบวก โอกาส จุดแข็งที่เสริมดวง';

        return "🃏 **วิเคราะห์ไพ่ยิปซี** (⚠️ ห้ามข้ามหัวข้อนี้! ต้องเขียนอย่างน้อย 100 คำ):

**ย่อหน้าที่ 1 — แนะนำไพ่:** เริ่มด้วยประโยคแบบนี้:
\"ไพ่ที่{$genderPrefix}{$name}เปิดได้คือ ไพ่{$cardNameTh}ค่ะ ไพ่ใบนี้มีความหมายเกี่ยวกับ [อธิบายความหมายหลักของไพ่ 1-2 ประโยค]\"

**ย่อหน้าที่ 2 — วิเคราะห์ร่วมกับดวงดาว:** ต้องมีประโยคที่เชื่อมไพ่กับดวงดาว เช่น:
\"เมื่อวิเคราะห์ไพ่{$cardNameTh}ร่วมกับดวงดาวของ{$genderPrefix}{$name}แล้ว พบว่า ไพ่ใบนี้สอดคล้องกับดาว[ชื่อ]ที่อยู่ภพ[ชื่อ] ซึ่งบ่งบอกว่า...\" + ขยายว่าพลังของไพ่เสริม/ขัดกับดาวอย่างไร

**ย่อหน้าที่ 3 — ตำแหน่งไพ่ + คำแนะนำ:**
- ไพ่ตำแหน่ง{$positionAdvice}
- บอกว่าไพ่ใบนี้แนะนำ{$genderPrefix}{$name}ว่าควรทำอะไร/ระวังอะไรในเรื่อง \"{$question}\"

**ย่อหน้าที่ 4 — สรุปฟันธง:**
\"สรุปจากไพ่{$cardNameTh}ประกอบดวงชะตาของ{$genderPrefix}{$name} หมอจันทราเห็นว่า [ฟันธงชัดเจน 1-2 ประโยค]\"

⚠️ ห้ามเขียนแค่ชื่อไพ่แล้วข้ามไปหัวข้อถัดไป! ต้องมีเนื้อหาวิเคราะห์ครบ 4 ย่อหน้า!

";
    }

    /**
     * สร้าง prompt เฉพาะวิเคราะห์ไพ่ยิปซีเท่านั้น (เรียกแยกจาก prompt หลัก)
     *
     * แยก prompt ไพ่ออกมาเพื่อ:
     * 1. ให้ AI โฟกัสวิเคราะห์ไพ่ได้ละเอียดขึ้น
     * 2. ส่งเป็นข้อความแยก ไม่ถูกตัดจาก limit ของ Messenger
     * 3. ลูกค้าเห็นชัดเจนว่า "ส่วนไพ่ยิปซี" คือส่วนพิเศษเพิ่มเติม
     *
     * @param array|null $userProfile ข้อมูลผู้ใช้
     * @param string $question คำถามที่ถาม
     * @param int $questionNumber ลำดับคำถาม
     * @param int $totalQuestions จำนวนคำถามทั้งหมด
     * @param string|null $birthDate วันเกิด
     * @param array $tarotCard ข้อมูลไพ่ยิปซี
     * @return string prompt สำหรับส่งให้ AI
     */
    protected function buildTarotOnlyPrompt(
        ?array $userProfile,
        string $question,
        int $questionNumber,
        int $totalQuestions,
        ?string $birthDate,
        array $tarotCard
    ): string {
        $name = $userProfile['name'] ?? 'คุณ';
        $gender = isset($userProfile['gender']) ? ($userProfile['gender'] === 'male' ? 'ชาย' : 'หญิง') : '';
        $genderPrefix = $gender === 'ชาย' ? 'คุณพี่' : ($gender === 'หญิง' ? 'คุณ' : 'คุณ');

        $cardNameTh = $tarotCard['card_name_th'] ?? 'ไม่ทราบชื่อ';
        $cardNameEn = $tarotCard['card_name_en'] ?? '';
        $isReversed = $tarotCard['is_reversed'] ?? false;
        $position = $isReversed ? 'กลับหัว (Reversed)' : 'หงาย (Upright)';
        $meaning = $tarotCard['meaning'] ?? '';
        $positionAdvice = $isReversed
            ? 'กลับหัว: เน้นความท้าทาย อุปสรรค สิ่งที่ต้องระวัง และวิธีรับมือ'
            : 'หงาย: เน้นพลังงานเชิงบวก โอกาส จุดแข็งที่เสริมดวง';

        // ข้อมูลดวงดาวสำหรับเชื่อมไพ่กับดวง
        $zodiacInfo = '';
        $planetPositionsInfo = '';
        if ($birthDate) {
            $zodiacInfo = $this->getZodiacDescription($birthDate);
            try {
                $date = \Carbon\Carbon::parse($birthDate);
                $dayOfWeek = $date->dayOfWeek;
                $chartService = new FortuneChartService;
                $positions = $chartService->calculatePlanetPositions($dayOfWeek);
                $planetPositionsInfo = "\n[ตำแหน่งดาวกำเนิดในภพ (อ้างอิงในการวิเคราะห์)]:\n";
                foreach ($positions as $houseNum => $planets) {
                    if (! empty($planets)) {
                        $houseName = FortuneChartService::HOUSES[$houseNum]['name'] ?? "ภพ{$houseNum}";
                        $planetNames = array_map(fn ($p) => FortuneChartService::PLANETS[$p]['name'] ?? $p, $planets);
                        $planetPositionsInfo .= "- ภพ{$houseNum}.{$houseName}: ".implode(', ', $planetNames)."\n";
                    }
                }
            } catch (\Exception $e) {
                // ข้ามไป
            }
        }

        return "คุณชื่อ \"แม่หมอจันทรา\" เป็นหมอดูสาวสวยผู้เชี่ยวชาญไพ่ทาโรต์ + โหราศาสตร์โบราณ พูดจาเพราะ อบอุ่น

=== วิเคราะห์ไพ่ยิปซี — คำถามที่ {$questionNumber}/{$totalQuestions} ===

ข้อมูลผู้ขอดูดวง:
- ชื่อ: {$name} (เรียกว่า \"{$genderPrefix}{$name}\")
".($zodiacInfo ? "- {$zodiacInfo}\n" : '')."
{$planetPositionsInfo}
คำถาม: {$question}

🃏 ไพ่ที่{$genderPrefix}{$name}เปิดได้:
- ไพ่: {$cardNameTh} ({$cardNameEn})
- ตำแหน่ง: {$position}
- ความหมาย: {$meaning}

[โครงสร้างที่ต้องเขียน — ครบทุกย่อหน้า ห้ามข้าม!]

🃏 **วิเคราะห์ไพ่ยิปซี — ไพ่{$cardNameTh}**

**ย่อหน้าที่ 1 — แนะนำไพ่:**
เริ่มประโยค: \"ไพ่ที่{$genderPrefix}{$name}เปิดได้คือ ไพ่{$cardNameTh}ค่ะ ไพ่ใบนี้เป็นไพ่ [Major/Minor Arcana] มีความหมายเกี่ยวกับ [อธิบายความหมายหลักอย่างละเอียด 3-4 ประโยค] สัญลักษณ์บนไพ่ [อธิบายภาพบนไพ่ + ความหมายของสัญลักษณ์]\"

**ย่อหน้าที่ 2 — วิเคราะห์ร่วมกับดวงดาว:**
เริ่มประโยค: \"เมื่อวิเคราะห์ไพ่{$cardNameTh}ร่วมกับดวงดาวของ{$genderPrefix}{$name}แล้ว พบว่า ไพ่ใบนี้สอดคล้องกับดาว[ชื่อดาวจากแผนที่ดวงชะตา]ที่อยู่ภพ[ชื่อภพ] ซึ่งบ่งบอกว่า [วิเคราะห์ 3-4 ประโยค] พลังของไพ่{$cardNameTh} [เสริม/ขัด] กับดาว[ชื่อ]อย่างไร ส่งผลต่อเรื่อง \"{$question}\" อย่างไร\"

**ย่อหน้าที่ 3 — ตำแหน่งไพ่ + คำแนะนำเฉพาะ:**
- ไพ่ตำแหน่ง{$positionAdvice}
- บอกว่าไพ่ใบนี้แนะนำ{$genderPrefix}{$name}ว่า:
  - สิ่งที่ควรทำ (2-3 ข้อ)
  - สิ่งที่ต้องระวัง (2-3 ข้อ)
  - จังหวะเวลาที่เหมาะสม

**ย่อหน้าที่ 4 — สรุปฟันธง:**
\"สรุปจากไพ่{$cardNameTh}ประกอบดวงชะตาของ{$genderPrefix}{$name} หมอจันทราเห็นว่า [ฟันธงชัดเจน 3-4 ประโยค ตอบคำถามตรงๆ]\"

[กฎสำคัญ]
- ต้องเขียนครบ 4 ย่อหน้า อย่างน้อย 200 คำ ไม่เกิน 400 คำ
- ต้องอ้างอิงดาวจากตำแหน่งจริง (แผนที่ดวงชะตาด้านบน) ห้ามแต่งขึ้น
- ใช้ \"หมอจันทรา\" แทนตัวเอง
- ตอบเป็นภาษาไทย อบอุ่น เป็นกันเอง น่าเชื่อถือ
- ฟันธง กล้าบอกตรงๆ ทั้งเรื่องดีและไม่ดี";
    }

    /**
     * แบ่งข้อความยาวสำหรับ Facebook Messenger (max ~2000 chars ต่อ message)
     *
     * ส่ง header เฉพาะ chunk แรก, แบ่งตาม paragraph (\n\n)
     *
     * @param  string  $header  หัวข้อ (ส่งใน chunk แรก)
     * @param  string  $body  เนื้อหาที่จะแบ่ง
     * @param  int  $maxLen  ความยาวสูงสุดต่อ chunk
     * @return array<string>
     */
    protected function splitLongMessageForFacebook(string $header, string $body, int $maxLen = 1800): array
    {
        $body = trim($body);
        $headerLen = mb_strlen($header);

        // ถ้าทั้งหมดสั้นพอ → ส่ง chunk เดียว
        if ($headerLen + mb_strlen($body) <= $maxLen) {
            return [$header.$body];
        }

        // แบ่งตาม paragraph
        $paragraphs = preg_split('/\n\n+/', $body);
        $chunks = [];
        $current = $header; // chunk แรกมี header

        foreach ($paragraphs as $para) {
            $para = trim($para);
            if ($para === '') {
                continue;
            }

            $separator = ($current === $header) ? '' : "\n\n";

            if (mb_strlen($current) + mb_strlen($separator) + mb_strlen($para) > $maxLen && $current !== $header && $current !== '') {
                $chunks[] = trim($current);
                $current = "(ต่อ) ".$para;
            } else {
                $current .= $separator.$para;
            }
        }

        if (trim($current) !== '' && trim($current) !== trim($header)) {
            $chunks[] = trim($current);
        }

        // ถ้าไม่มี chunks → ส่ง body ตัดที่ maxLen
        if (empty($chunks)) {
            return [$header.mb_substr($body, 0, $maxLen - $headerLen)];
        }

        return $chunks;
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
            if (! empty($reading['tarot_card'])) {
                $tarot = $reading['tarot_card'];
                $pos = ($tarot['is_reversed'] ?? false) ? 'กลับหัว' : 'หงาย';
                $combined .= "🃏 ไพ่ยิปซี: {$tarot['card_name_th']} ({$pos})\n";
                if (! empty($tarot['meaning'])) {
                    $combined .= "📖 ความหมาย: {$tarot['meaning']}\n";
                }
            }
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

    // ============================================================
    // MLM Commission Distribution สำหรับ Fortune Reading
    // ============================================================

    /**
     * แบ่งคอมมิชชั่น MLM หลังชำระค่าดูดวงสำเร็จ
     *
     * รองรับ 2 โหมด:
     * - 'pv': ใช้ fortune_pv_value → ส่งเข้า MlmCommissionService (rollup, binary, etc.)
     * - 'static': จ่ายตรงตามจำนวนที่ตั้ง → แบ่งตาม unilevel % → เข้า wallet upline
     *
     * ทุกอย่างอยู่ใน try/catch — ห้าม error กระทบการส่งคำทำนาย
     *
     * @param FortuneReading $reading บันทึกการดูดวงที่ชำระเงินแล้ว
     */
    protected function distributeFortuneCommissions(FortuneReading $reading): void
    {
        try {
            // ตรวจว่าเปิดระบบ affiliate หรือไม่
            if (! $this->settings->isFortuneAffiliateEnabled()) {
                return;
            }

            $mode = $this->settings->getFortuneCommissionMode();

            // ตรวจว่ามีค่าที่จะจ่ายหรือไม่
            if ($mode === 'static') {
                $staticAmount = $this->settings->getFortuneStaticCommissionAmount();
                if ($staticAmount <= 0) {
                    Log::debug('Fortune Commission: static_commission_amount = 0 ข้ามการแบ่ง', [
                        'reading_id' => $reading->id,
                    ]);

                    return;
                }
            } else {
                // คำนวณ PV: ใช้ fortune_pv_value (override) หรือ price × global_pv_rate (auto)
                $manualPv = (float) ($this->settings->fortune_pv_value ?? 0);
                if ($manualPv > 0) {
                    $pvValue = $manualPv;
                } else {
                    $price = (float) ($this->settings->deep_reading_price ?? 0);
                    $globalPvRate = (float) \App\Models\MlmGlobalSetting::get('global_pv_rate', 1);
                    $pvValue = $price * $globalPvRate;
                }

                if ($pvValue <= 0) {
                    Log::debug('Fortune Commission: PV = 0 (ราคา/pv_rate เป็น 0) ข้ามการแบ่ง', [
                        'reading_id' => $reading->id,
                    ]);

                    return;
                }
            }

            // Refresh reading เพื่อให้ได้ user_id ล่าสุด (อาจเพิ่งถูก link จาก auto-register)
            $reading->refresh();

            // ต้องมี user_id ที่ link กับ reading
            if (! $reading->user_id) {
                Log::debug('Fortune Commission: reading ไม่มี user_id ข้ามการแบ่ง', [
                    'reading_id' => $reading->id,
                ]);

                return;
            }

            // หา MlmMember ของ user
            $mlmMember = \App\Models\MlmMember::where('user_id', $reading->user_id)->first();
            if (! $mlmMember) {
                Log::debug('Fortune Commission: user ไม่ใช่สมาชิก MLM ข้ามการแบ่ง', [
                    'reading_id' => $reading->id,
                    'user_id' => $reading->user_id,
                ]);

                return;
            }

            // แบ่งตามโหมด
            if ($mode === 'static') {
                // ✅ ใช้ FortuneCommissionService ใหม่ — จ่าย Level 1 + Level 2 ผ่าน fortune_commissions
                // ตรวจซ้ำอยู่ใน FortuneCommissionService แล้ว
                $fortuneCommissionService = app(\App\Services\FortuneCommissionService::class);
                $fortuneCommissionService->distributeCommissions($reading, $mlmMember, $this->settings);
            } else {
                // PV mode: ยังใช้ MlmCommissionService เดิม (จ่ายผ่าน mlm_commissions)
                // ตรวจซ้ำสำหรับ PV mode
                $alreadyDistributed = \App\Models\MlmCommission::where('source_type', FortuneReading::class)
                    ->where('source_id', $reading->id)
                    ->exists();

                if ($alreadyDistributed) {
                    Log::info('Fortune Commission: reading นี้จ่ายคอมมิชชั่น PV ไปแล้ว ข้าม', [
                        'reading_id' => $reading->id,
                    ]);

                    return;
                }

                $this->distributePvCommissions($reading, $mlmMember);
            }

        } catch (\Exception $e) {
            // ไม่ให้ error กระทบการส่งคำทำนาย
            Log::error('Fortune Commission: แบ่งคอมมิชชั่นล้มเหลว (ไม่กระทบคำทำนาย)', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
                'trace' => mb_substr($e->getTraceAsString(), 0, 500),
            ]);
        }
    }

    /**
     * แบ่งคอมมิชชั่นแบบ PV mode
     *
     * ส่ง PV เข้า MlmCommissionService::calculateCommissionsWithRollup()
     * ซึ่งรองรับ rollup, overpay protection, binary etc. เหมือน orders
     */
    protected function distributePvCommissions(FortuneReading $reading, \App\Models\MlmMember $mlmMember): void
    {
        // คำนวณ PV: ใช้ fortune_pv_value (override) หรือ price × global_pv_rate (auto)
        $manualPv = (float) ($this->settings->fortune_pv_value ?? 0);
        if ($manualPv > 0) {
            $pvValue = $manualPv;
        } else {
            $price = (float) ($this->settings->deep_reading_price ?? 0);
            $globalPvRate = (float) \App\Models\MlmGlobalSetting::get('global_pv_rate', 1);
            $pvValue = $price * $globalPvRate;
        }

        $commissionService = app(\App\Services\MlmCommissionService::class);
        // ดูดวง: ไม่ roll up — ถ้าผู้แนะนำไม่ active ให้ข้ามเลย (disableRollup = true)
        $result = $commissionService->calculateCommissionsWithRollup(
            $mlmMember,
            $pvValue,
            'fortune_reading',
            $reading->id,
            disableRollup: true
        );

        if ($result['success']) {
            Log::info('Fortune Commission [PV mode]: แบ่งคอมมิชชั่นสำเร็จ', [
                'reading_id' => $reading->id,
                'user_id' => $reading->user_id,
                'pv' => $pvValue,
                'amount_paid' => $reading->amount_paid,
                'total_commission' => $result['total_amount'] ?? 0,
                'commission_count' => count($result['commissions'] ?? []),
            ]);

            // แจ้ง LINE OA notification สำหรับทุก commission ที่สร้าง
            if (! empty($result['commissions'])) {
                foreach ($result['commissions'] as $comm) {
                    $this->notifyCommissionRecipientViaLineOa($comm, $reading);
                }
            }
        } else {
            Log::warning('Fortune Commission [PV mode]: แบ่งคอมมิชชั่นไม่สำเร็จ', [
                'reading_id' => $reading->id,
                'error' => $result['error'] ?? 'unknown',
            ]);
        }
    }

    /**
     * แบ่งคอมมิชชั่นแบบ Static mode (ค่าแนะนำ)
     *
     * ระบบดูดวงจ่ายเฉพาะค่าแนะนำ (Direct Referral / Level 1) อย่างเดียว
     * static_amount = จำนวนเงินที่ผู้แนะนำตรงได้รับเต็มจำนวน
     * เช่น ตั้ง 10 บาท → ผู้แนะนำได้ 10 บาท
     *
     * ไม่ผ่าน MlmCommissionService — จ่ายตรงเข้า wallet ผู้แนะนำเลย
     */
    protected function distributeStaticCommissions(FortuneReading $reading, \App\Models\MlmMember $mlmMember): void
    {
        $staticAmount = $this->settings->getFortuneStaticCommissionAmount();
        $commissionAmount = round($staticAmount, 2);

        // หา sponsor (ผู้แนะนำตรง / Level 1) ของสมาชิก
        if (! $mlmMember->unilevel_sponsor_id) {
            Log::debug('Fortune Commission [Static]: สมาชิกไม่มีผู้แนะนำ ข้ามการจ่าย', [
                'reading_id' => $reading->id,
                'mlm_member_id' => $mlmMember->id,
            ]);

            return;
        }

        $sponsor = \App\Models\MlmMember::find($mlmMember->unilevel_sponsor_id);
        if (! $sponsor || ! $sponsor->user) {
            Log::debug('Fortune Commission [Static]: ผู้แนะนำไม่พบหรือไม่มี user', [
                'reading_id' => $reading->id,
                'sponsor_id' => $mlmMember->unilevel_sponsor_id,
            ]);

            return;
        }

        // เช็ค active: ผู้แนะนำต้องมีการเคลื่อนไหว (ซื้อสินค้า/ดูดวง) ในเดือนนี้
        // ถ้าไม่ active → ข้ามเลย ไม่จ่าย ไม่ roll up (กฎดูดวง: ไม่ roll up)
        $isActive = \App\Helpers\MlmRetentionHelper::isMemberActive($sponsor);
        if (! $isActive) {
            Log::info('Fortune Commission [Static]: ผู้แนะนำไม่ active ข้ามการจ่าย (ไม่ roll up)', [
                'reading_id' => $reading->id,
                'sponsor_id' => $sponsor->id,
                'sponsor_user_id' => $sponsor->user_id,
            ]);

            return;
        }

        // สร้าง MlmCommission record
        \App\Models\MlmCommission::create([
            'mlm_member_id' => $sponsor->id,
            'mlm_plan_id' => $mlmMember->mlm_plan_id,
            'user_id' => $sponsor->user_id,
            'from_member_id' => $mlmMember->id,
            'source_type' => FortuneReading::class,
            'source_id' => $reading->id,
            'type' => 'unilevel_direct',
            'level' => 1,
            'commission_amount' => $commissionAmount,
            'pv_amount' => $staticAmount,
            'percentage' => 100,
            'status' => 'pending',
            'is_rollup' => false,
            'tree_type' => 'unilevel',
            'notes' => "ค่าแนะนำดูดวง {$commissionAmount} บาท",
            'calculation_details' => json_encode([
                'mode' => 'static',
                'static_amount' => $staticAmount,
                'type' => 'referral_bonus',
                'fortune_reading_id' => $reading->id,
            ]),
            'created_at' => now(),
        ]);

        // เพิ่มเงินเข้า wallet ของผู้แนะนำ
        $walletService = app(\App\Services\WalletService::class);
        try {
            $wallet = $sponsor->user->wallet;
            if (! $wallet) {
                $wallet = \App\Models\Wallet::create([
                    'user_id' => $sponsor->user_id,
                    'balance' => 0,
                    'currency' => 'THB',
                ]);
            }

            $walletService->deposit(
                $wallet,
                $commissionAmount,
                'fortune_commission',
                "ค่าแนะนำดูดวง {$commissionAmount} บาท จากบิล #{$reading->id}",
                [
                    'reading_id' => $reading->id,
                    'level' => 1,
                    'buyer_user_id' => $reading->user_id,
                    'mode' => 'static_referral',
                ]
            );
        } catch (\Exception $walletErr) {
            Log::warning('Fortune Commission [Static]: เพิ่มเงินเข้า wallet ไม่สำเร็จ', [
                'user_id' => $sponsor->user_id,
                'amount' => $commissionAmount,
                'error' => $walletErr->getMessage(),
            ]);
        }

        Log::info('Fortune Commission [Static/ค่าแนะนำ]: จ่ายสำเร็จ', [
            'reading_id' => $reading->id,
            'buyer_user_id' => $reading->user_id,
            'sponsor_user_id' => $sponsor->user_id,
            'static_amount' => $staticAmount,
            'commission' => $commissionAmount,
            'reading_price' => $reading->amount_paid,
        ]);

        // แจ้ง LINE OA notification ให้ผู้แนะนำ (ส่งผ่าน LINE OA push message)
        $this->notifySponsorViaLineOa($sponsor, $commissionAmount, $reading);
    }

    // ============================================================
    // LINE OA Commission Notification — แจ้งผู้แนะนำผ่าน LINE OA
    // ============================================================

    /**
     * แจ้งผู้แนะนำผ่าน LINE OA เมื่อได้รับค่าคอมมิชชั่น (Static mode)
     *
     * ส่ง Flex Message ผ่าน LINE OA push API → ไม่ใช่ LINE Notify
     * ใช้ line_user_id ของผู้แนะนำที่เก็บในระบบ
     *
     * แสดง: ยอดที่ได้ + ยอดรวมใน wallet + ลิงก์เข้าเว็บ + แจ้ง KYC ถอนเงิน
     */
    protected function notifySponsorViaLineOa(
        \App\Models\MlmMember $sponsor,
        float $amount,
        FortuneReading $reading
    ): void {
        try {
            // ตรวจว่ามี line_user_id หรือไม่
            $user = $sponsor->user;
            if (! $user || empty($user->line_user_id)) {
                // ไม่มี LINE → ส่ง in-app notification เท่านั้น
                $this->notifyCommissionInApp($user, $amount);

                return;
            }

            $lineUserId = $user->line_user_id;

            // ดึง wallet balance ปัจจุบัน (ยอดรวม)
            $wallet = $user->wallet;
            $totalBalance = $wallet ? number_format($wallet->balance, 2) : '0.00';

            // ดึง member code สำหรับแสดง
            $buyerMember = \App\Models\MlmMember::where('user_id', $reading->user_id)->first();
            $buyerCode = $buyerMember?->member_code ?? "#{$reading->id}";

            // สร้าง Flex Message แจ้งค่าคอม
            $appUrl = config('app.url', 'https://main.thaiprompt.online');
            $primaryColor = $this->settings->getLineFlexPrimaryColor();
            $amountText = number_format($amount, 2);

            $flex = [
                'type' => 'bubble',
                'size' => 'kilo',
                'hero' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        ['type' => 'text', 'text' => '💰', 'size' => '3xl', 'align' => 'center'],
                    ],
                    'backgroundColor' => '#06C755',
                    'paddingAll' => 'md',
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => 'ได้รับค่าแนะนำดูดวง!',
                            'weight' => 'bold',
                            'size' => 'md',
                            'color' => '#333333',
                        ],
                        [
                            'type' => 'text',
                            'text' => "+{$amountText} บาท",
                            'size' => 'xxl',
                            'color' => '#06C755',
                            'weight' => 'bold',
                            'margin' => 'md',
                        ],
                        ['type' => 'separator', 'margin' => 'lg'],
                        [
                            'type' => 'box',
                            'layout' => 'horizontal',
                            'contents' => [
                                ['type' => 'text', 'text' => 'จากสมาชิก', 'size' => 'xs', 'color' => '#888888', 'flex' => 3],
                                ['type' => 'text', 'text' => $buyerCode, 'size' => 'xs', 'color' => '#333333', 'align' => 'end', 'flex' => 4],
                            ],
                            'margin' => 'lg',
                        ],
                        ['type' => 'separator', 'margin' => 'lg'],
                        // ✅ แสดงยอด Wallet เด่นชัด
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'margin' => 'lg',
                            'backgroundColor' => '#F0FFF4',
                            'cornerRadius' => 'md',
                            'paddingAll' => 'md',
                            'contents' => [
                                ['type' => 'text', 'text' => '💰 ยอดเงินใน Wallet', 'size' => 'xxs', 'color' => '#888888'],
                                ['type' => 'text', 'text' => "฿{$totalBalance}", 'size' => 'xl', 'color' => '#06C755', 'weight' => 'bold', 'margin' => 'xs'],
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => '💸 ถอนเงินที่เว็บไซต์ (ต้อง KYC)',
                            'size' => 'xxs',
                            'color' => '#AAAAAA',
                            'margin' => 'md',
                            'wrap' => true,
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'button',
                            'style' => 'primary',
                            'color' => $primaryColor,
                            'action' => [
                                'type' => 'uri',
                                'label' => '💰 ดู Wallet',
                                'uri' => $appUrl.'/auth/line?redirect=/user/wallet',
                            ],
                            'height' => 'sm',
                        ],
                    ],
                ],
            ];

            // ส่งผ่าน LINE OA push message (ไม่ใช่ LINE Notify) + retry 1 ครั้ง
            $lineService = new LineFortuneService($this->settings);
            $richPayload = [
                'alt_text' => "💰 ค่าแนะนำดูดวง +{$amountText} บาท | ยอดรวม: {$totalBalance} บาท",
                'contents' => $flex,
            ];

            try {
                $lineService->sendRichMessage($lineUserId, $richPayload);
            } catch (\Exception $firstErr) {
                // retry 1 ครั้ง หลังรอ 500ms
                Log::info('Fortune Commission Notify: retry ครั้งที่ 1', [
                    'line_user_id' => $lineUserId,
                    'first_error' => $firstErr->getMessage(),
                ]);
                usleep(500000); // 0.5s retry delay (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
                $lineService->sendRichMessage($lineUserId, $richPayload);
            }

            // ส่ง in-app notification ด้วย
            $this->notifyCommissionInApp($user, $amount);

            Log::info('Fortune Commission Notify: ส่ง LINE OA notification สำเร็จ', [
                'sponsor_user_id' => $user->id,
                'line_user_id' => $lineUserId,
                'amount' => $amount,
                'total_balance' => $totalBalance,
            ]);

        } catch (\Exception $e) {
            // ไม่ให้ error กระทบ flow หลัก
            Log::warning('Fortune Commission Notify: ส่ง LINE OA notification ล้มเหลว', [
                'sponsor_id' => $sponsor->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * แจ้งผู้ได้รับคอมมิชชั่นผ่าน LINE OA (PV mode — สำหรับทุก level)
     *
     * ส่ง Flex Message ผ่าน LINE OA push API
     * ใช้ line_user_id ของผู้ได้รับที่เก็บในระบบ
     */
    protected function notifyCommissionRecipientViaLineOa(array $commissionData, FortuneReading $reading): void
    {
        try {
            $userId = $commissionData['user_id'] ?? null;
            $amount = (float) ($commissionData['amount'] ?? $commissionData['commission_amount'] ?? 0);
            $level = $commissionData['level'] ?? 1;
            $percentage = $commissionData['percentage'] ?? 0;

            if (! $userId || $amount <= 0) {
                return;
            }

            $user = \App\Models\User::find($userId);
            if (! $user || empty($user->line_user_id)) {
                // ไม่มี LINE → ส่ง in-app notification เท่านั้น
                if ($user) {
                    $this->notifyCommissionInApp($user, $amount);
                }

                return;
            }

            $lineUserId = $user->line_user_id;

            // ดึง wallet balance ปัจจุบัน
            $wallet = $user->wallet;
            $totalBalance = $wallet ? number_format($wallet->balance, 2) : '0.00';

            $appUrl = config('app.url', 'https://main.thaiprompt.online');
            $primaryColor = $this->settings->getLineFlexPrimaryColor();
            $amountText = number_format($amount, 2);

            $flex = [
                'type' => 'bubble',
                'size' => 'kilo',
                'hero' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        ['type' => 'text', 'text' => '💰', 'size' => '3xl', 'align' => 'center'],
                    ],
                    'backgroundColor' => '#06C755',
                    'paddingAll' => 'md',
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => "คอมมิชชั่นดูดวง Level {$level}",
                            'weight' => 'bold',
                            'size' => 'md',
                            'color' => '#333333',
                        ],
                        [
                            'type' => 'text',
                            'text' => "+{$amountText} บาท",
                            'size' => 'xxl',
                            'color' => '#06C755',
                            'weight' => 'bold',
                            'margin' => 'md',
                        ],
                        ['type' => 'separator', 'margin' => 'lg'],
                        [
                            'type' => 'box',
                            'layout' => 'horizontal',
                            'contents' => [
                                ['type' => 'text', 'text' => 'Level / อัตรา', 'size' => 'xs', 'color' => '#888888', 'flex' => 3],
                                ['type' => 'text', 'text' => "Level {$level} ({$percentage}%)", 'size' => 'xs', 'color' => '#333333', 'align' => 'end', 'flex' => 4],
                            ],
                            'margin' => 'lg',
                        ],
                        ['type' => 'separator', 'margin' => 'lg'],
                        // ✅ แสดงยอด Wallet เด่นชัด
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'margin' => 'lg',
                            'backgroundColor' => '#F0FFF4',
                            'cornerRadius' => 'md',
                            'paddingAll' => 'md',
                            'contents' => [
                                ['type' => 'text', 'text' => '💰 ยอดเงินใน Wallet', 'size' => 'xxs', 'color' => '#888888'],
                                ['type' => 'text', 'text' => "฿{$totalBalance}", 'size' => 'xl', 'color' => '#06C755', 'weight' => 'bold', 'margin' => 'xs'],
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => '💸 ถอนเงินที่เว็บไซต์ (ต้อง KYC)',
                            'size' => 'xxs',
                            'color' => '#AAAAAA',
                            'margin' => 'md',
                            'wrap' => true,
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'button',
                            'style' => 'primary',
                            'color' => $primaryColor,
                            'action' => [
                                'type' => 'uri',
                                'label' => '💰 ดู Wallet',
                                'uri' => $appUrl.'/auth/line?redirect=/user/wallet',
                            ],
                            'height' => 'sm',
                        ],
                    ],
                ],
            ];

            // ส่งผ่าน LINE OA push message (มี retry 1 ครั้ง)
            $lineService = new LineFortuneService($this->settings);
            $richPayload = [
                'alt_text' => "💰 คอมมิชชั่นดูดวง Level {$level}: +{$amountText} บาท | ยอดรวม: {$totalBalance} บาท",
                'contents' => $flex,
            ];

            try {
                $lineService->sendRichMessage($lineUserId, $richPayload);
            } catch (\Exception $firstErr) {
                Log::info('Fortune Commission Notify [PV]: retry ครั้งที่ 1', [
                    'user_id' => $userId,
                    'error' => $firstErr->getMessage(),
                ]);
                usleep(500000); // 0.5s retry delay (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
                $lineService->sendRichMessage($lineUserId, $richPayload);
            }

            // ส่ง in-app notification ด้วย
            $this->notifyCommissionInApp($user, $amount);

            Log::info('Fortune Commission Notify [PV]: ส่ง LINE OA notification สำเร็จ', [
                'user_id' => $userId,
                'level' => $level,
                'amount' => $amount,
            ]);

        } catch (\Exception $e) {
            Log::warning('Fortune Commission Notify [PV]: ส่ง LINE OA notification ล้มเหลว', [
                'user_id' => $commissionData['user_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ส่ง in-app notification เมื่อได้รับคอมมิชชั่น
     *
     * ใช้ NotificationService ที่มีอยู่แล้ว
     */
    protected function notifyCommissionInApp(?\App\Models\User $user, float $amount): void
    {
        if (! $user) {
            return;
        }

        try {
            $notificationService = app(\App\Services\NotificationService::class);
            $notificationService->notifyCommissionEarned($user, $amount, 'THB');
        } catch (\Exception $e) {
            Log::debug('Fortune Commission: in-app notification ล้มเหลว', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ============================================================
    // คำสั่ง "แชร์" — ส่งลิงก์เชิญเพื่อน
    // ============================================================

    /**
     * ตรวจสอบว่าเป็นคำขอแชร์ลิงก์หรือไม่
     */
    protected function isShareRequest(string $text): bool
    {
        $text = mb_strtolower(trim($text));
        // ลบคำลงท้ายสุภาพ
        $textNormalized = preg_replace('/\s*(ค่ะ|ครับ|คะ|จ้า|จ้ะ|หน่อย|ด้วย|ที|นะ|นะคะ|นะครับ)\s*$/u', '', $text);

        $exactKeywords = [
            'แชร์', 'share', 'ลิงก์แชร์', 'ลิงค์แชร์',
            'แชร์ลิงก์', 'แชร์ลิงค์', 'ลิงก์เชิญ', 'ลิงค์เชิญ',
            'เชิญเพื่อน', 'ขอลิงก์', 'ขอลิงค์',
            'แชร์เพื่อน', 'ลิงก์ชวนเพื่อน', 'ลิงค์ชวนเพื่อน',
        ];

        foreach ($exactKeywords as $keyword) {
            if ($text === $keyword || $textNormalized === $keyword) {
                return true;
            }
        }

        return false;
    }

    /**
     * จัดการคำสั่ง "แชร์" — สร้างและส่งลิงก์เชิญเพื่อน
     */
    protected function handleShareRequest(string $facebookUserId): array
    {
        try {
            // ✅ ค้นหา User หลายวิธี (ไม่ใช่แค่ line_user_id เดียว)
            $user = \App\Models\User::where('line_user_id', $facebookUserId)->first();

            // Fallback: ค้นหาจาก email pattern
            if (! $user) {
                $user = \App\Models\User::where('email', 'line_'.$facebookUserId.'@thaiprompt.local')->first();
            }

            // Fallback: ค้นหาจาก FortuneReading ที่ link กับ user
            if (! $user) {
                $linkedReading = FortuneReading::where('facebook_user_id', $facebookUserId)
                    ->whereNotNull('user_id')
                    ->latest()
                    ->first();
                if ($linkedReading && $linkedReading->user_id) {
                    $user = \App\Models\User::find($linkedReading->user_id);
                }
            }

            // ⭐ ถ้ายังไม่มี User → สร้างอัตโนมัติจาก FortuneReading (user เคยจ่ายเงินแล้ว)
            if (! $user) {
                $paidReading = FortuneReading::where('facebook_user_id', $facebookUserId)
                    ->where('is_paid', true)
                    ->latest()
                    ->first();

                if ($paidReading) {
                    // สร้าง User อัตโนมัติ
                    $userName = $paidReading->facebook_user_name ?? 'User';
                    $user = \App\Models\User::create([
                        'name' => $userName,
                        'email' => 'line_'.$facebookUserId.'@thaiprompt.local',
                        'password' => \Illuminate\Support\Facades\Hash::make('12345678'),
                        'line_user_id' => $facebookUserId,
                    ]);

                    // Link reading กับ user
                    $paidReading->update(['user_id' => $user->id]);

                    Log::info('Fortune Share: สร้าง User อัตโนมัติจาก paid reading', [
                        'user_id' => $user->id,
                        'line_user_id' => $facebookUserId,
                        'reading_id' => $paidReading->id,
                    ]);
                }
            }

            if (! $user) {
                return [
                    'action' => 'share_no_user',
                    'message' => "🔗 ระบบแชร์ลิงก์เชิญเพื่อน\n\n"
                        ."❌ คุณยังไม่มีบัญชีในระบบค่ะ\n"
                        ."ลองดูดวงสักครั้งก่อนนะคะ ระบบจะสร้างบัญชีให้อัตโนมัติ\n\n"
                        ."พิมพ์คำถามมาได้เลย 🔮",
                    'reading' => null,
                ];
            }

            // สร้าง referral link
            $affiliateService = app(FortuneAffiliateService::class);
            $referralLink = $affiliateService->generateReferralLink($user);

            // ดึงค่าแนะนำจาก settings
            $commissionMode = $this->settings->getFortuneCommissionMode();
            $commissionText = '';
            if ($commissionMode === 'static') {
                $amount = $this->settings->getFortuneStaticCommissionAmount();
                $commissionText = number_format($amount, 0).' บาท';
            } else {
                $preview = $this->settings->calculateFortuneCommissionPreview();
                $level1 = $preview['levels'][0] ?? null;
                $amount = $level1 ? $level1['amount'] : 0;
                $commissionText = number_format($amount, 2).' บาท';
            }

            $message = "🔗 ลิงก์เชิญเพื่อนของคุณ\n\n"
                ."📢 แชร์ลิงก์นี้ให้เพื่อน:\n"
                ."{$referralLink}\n\n"
                ."💰 ทุกครั้งที่เพื่อนดูดวง คุณจะได้รับค่าแนะนำ {$commissionText} เข้า Wallet อัตโนมัติค่ะ ✨\n\n"
                ."📲 ถอนเงินได้ที่เว็บไซต์ เข้าบัญชีภายใน 1-3 วันทำการ\n\n"
                .'กดค้างที่ลิงก์ด้านบนเพื่อคัดลอก แล้วส่งต่อให้เพื่อนได้เลยค่ะ 🎁';

            Log::info('Fortune: ส่งลิงก์แชร์ให้ผู้ใช้', [
                'user_id' => $facebookUserId,
                'referral_link' => $referralLink,
            ]);

            return [
                'action' => 'share_link',
                'message' => $message,
                'reading' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Fortune: handleShareRequest error', [
                'facebook_user_id' => $facebookUserId,
                'error' => $e->getMessage(),
            ]);

            return [
                'action' => 'share_error',
                'message' => "ขออภัยค่ะ ไม่สามารถสร้างลิงก์ได้ในขณะนี้\nกรุณาลองใหม่อีกครั้ง 🙏",
                'reading' => null,
            ];
        }
    }

    // ============================================================
    // คำสั่ง "ฝากคำถามถึงแอดมิน" — โหมดฝากคำถามแบบ user-initiated
    // ============================================================

    /**
     * ตรวจสอบว่าเป็นคำขอฝากคำถามถึงแอดมินหรือไม่
     */
    protected function isLeaveQuestionRequest(string $text): bool
    {
        $text = mb_strtolower(trim($text));
        // ลบคำลงท้ายสุภาพ
        $textNormalized = preg_replace('/\s*(ค่ะ|ครับ|คะ|จ้า|จ้ะ|หน่อย|ด้วย|ที|นะ|นะคะ|นะครับ)\s*$/u', '', $text);

        $keywords = [
            'ฝากคำถาม', 'ฝากคำถามถึงแอดมิน', 'ฝากถึงแอดมิน',
            'ถามแอดมิน', 'ฝากเรื่อง', 'ฝากเรื่องถึงแอดมิน',
            'ติดต่อแอดมิน', 'ขอคุยกับแอดมิน',
        ];

        foreach ($keywords as $keyword) {
            if ($text === $keyword || $textNormalized === $keyword || str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * เข้าโหมดฝากคำถาม — ถามก่อนว่าจะฝากคำถามอะไร
     */
    protected function handleManualLeaveQuestion(string $userId, ?array $userProfile = null): array
    {
        // ตั้ง cache flag ว่าอยู่ในโหมดฝากคำถาม (TTL 5 นาที)
        $cacheKey = "fortune_leave_question_mode:{$userId}";
        Cache::put($cacheKey, [
            'user_name' => $userProfile['name'] ?? null,
            'entered_at' => now()->toDateTimeString(),
        ], 300);

        Log::info('Fortune: ผู้ใช้เข้าโหมดฝากคำถาม', ['user_id' => $userId]);

        return [
            'action' => 'leave_question_prompt',
            'message' => "📝 โหมดฝากคำถามถึงแอดมิน\n\n"
                ."พิมพ์คำถามที่ต้องการฝากถึงแอดมินได้เลยค่ะ\n"
                ."แอดมินจะตอบกลับให้เร็วที่สุดนะคะ\n\n"
                ."💡 พิมพ์ 'ยกเลิก' ถ้าไม่ต้องการฝากคำถามค่ะ",
            'reading' => null,
        ];
    }

    /**
     * จัดการข้อความเมื่ออยู่ในโหมดฝากคำถาม — บันทึกคำถามหรือยกเลิก
     */
    protected function handleLeaveQuestionMode(string $userId, string $messageText, ?array $userProfile = null): ?array
    {
        $cacheKey = "fortune_leave_question_mode:{$userId}";
        $modeData = Cache::get($cacheKey);

        // ไม่อยู่ในโหมดฝากคำถาม → ข้าม
        if (! $modeData) {
            return null;
        }

        $normalizedText = mb_strtolower(trim($messageText));

        // ผู้ใช้ยกเลิก
        if ($normalizedText === 'ยกเลิก' || $normalizedText === 'cancel' || $normalizedText === 'ไม่' || $normalizedText === 'ไม่ฝาก') {
            Cache::forget($cacheKey);

            return [
                'action' => 'leave_question_cancelled',
                'message' => "ยกเลิกการฝากคำถามแล้วค่ะ ✅\n\nพิมพ์ถามหมอจันทราได้เลย 🔮",
                'reading' => null,
            ];
        }

        // บันทึกคำถาม
        $this->saveQuestionForAdmin(
            $userId,
            $messageText,
            'user_initiated',
            null,
            $modeData['user_name'] ?? $userProfile['name'] ?? null
        );

        Cache::forget($cacheKey);

        Log::info('Fortune: บันทึกคำถามจากโหมดฝากคำถาม', [
            'user_id' => $userId,
            'question_preview' => mb_substr($messageText, 0, 100),
        ]);

        return [
            'action' => 'leave_question_saved',
            'message' => "📝 บันทึกคำถามเรียบร้อยแล้วค่ะ!\n\n"
                ."คำถาม: \"{$messageText}\"\n\n"
                ."แอดมินจะตอบกลับให้เร็วที่สุดนะคะ 🙏\n\n"
                ."ระหว่างนี้ พิมพ์ถามหมอจันทราได้เลยค่ะ ✨",
            'reading' => null,
        ];
    }

    // ============================================================
    // คำสั่ง "สายงาน" — ดูรายชื่อคนที่แนะนำมา (downline)
    // ============================================================

    /**
     * ตรวจสอบว่าเป็นคำขอดูสายงานหรือไม่
     */
    protected function isDownlineRequest(string $text): bool
    {
        $text = mb_strtolower(trim($text));
        $textNormalized = preg_replace('/\s*(ค่ะ|ครับ|คะ|จ้า|จ้ะ|หน่อย|ด้วย|ที|นะ|นะคะ|นะครับ)\s*$/u', '', $text);

        $exactKeywords = [
            'สายงาน', 'ดูสายงาน', 'ทีม', 'ดูทีม',
            'คนที่แนะนำ', 'downline', 'ทีมงานฉัน',
            'สมาชิก', 'ดูสมาชิก', 'ลูกทีม', 'ดูลูกทีม',
            'คนในทีม', 'รายชื่อทีม',
        ];

        foreach ($exactKeywords as $keyword) {
            if ($text === $keyword || $textNormalized === $keyword) {
                return true;
            }
        }

        return false;
    }

    /**
     * จัดการคำสั่ง "สายงาน" — แสดงรายชื่อ direct referrals
     */
    protected function handleDownlineRequest(string $facebookUserId): array
    {
        try {
            $user = $this->findUserByPlatformId($facebookUserId);

            // URL สำหรับปุ่มกด
            $treeUrl = url('/user/fortune-referral/tree');
            $commissionUrl = url('/user/fortune-referral/commissions');
            $commissionAmount = $this->getLevel1CommissionText();

            if ($user) {
                $userName = $user->name ?? 'คุณ';

                // ✅ นับจากทั้ง FortuneReferral + MlmMember (ครอบคลุมทุกช่องทาง)
                // 1) จาก fortune_referrals (ลิงก์แชร์ดูดวง)
                $fortuneReferralUserIds = \App\Models\FortuneReferral::where('referrer_user_id', $user->id)
                    ->whereIn('status', ['followed', 'converted'])
                    ->whereNotNull('referred_user_id')
                    ->pluck('referred_user_id')
                    ->toArray();

                $fortuneReferralCount = \App\Models\FortuneReferral::where('referrer_user_id', $user->id)
                    ->whereIn('status', ['followed', 'converted'])
                    ->count();

                $convertedCount = \App\Models\FortuneReferral::where('referrer_user_id', $user->id)
                    ->where('status', 'converted')
                    ->count();

                // 2) จาก MlmMember (ลิงก์ MLM ทั่วไป + ระบบอื่น)
                $mlmDownlineUserIds = [];
                try {
                    $mlmMember = \App\Models\MlmMember::where('user_id', $user->id)->first();
                    if ($mlmMember) {
                        $mlmDownlineUserIds = \App\Models\MlmMember::where('unilevel_sponsor_id', $mlmMember->id)
                            ->pluck('user_id')
                            ->toArray();
                    }
                } catch (\Exception $e) {
                    // table อาจยังไม่มี — ข้ามไป
                }

                // 3) รวมทั้ง 2 แหล่ง (ไม่นับซ้ำ)
                $allDownlineUserIds = array_unique(array_merge($fortuneReferralUserIds, $mlmDownlineUserIds));
                $totalReferrals = max($fortuneReferralCount, count($allDownlineUserIds));

                $message = "👥 สายงานดูดวงของคุณ{$userName}\n"
                    ."═══════════════════════\n\n"
                    ."📊 สมาชิกสายตรง: {$totalReferrals} คน\n"
                    ."💎 จ่ายเงินแล้ว: {$convertedCount} คน\n\n";

                if ($totalReferrals === 0) {
                    $message .= "💡 ยังไม่มีสมาชิก — เริ่มสร้างทีม!\n"
                        ."พิมพ์ \"แชร์\" เพื่อรับลิงก์เชิญเพื่อน\n"
                        ."💰 ค่าแนะนำ: {$commissionAmount} บาท/ครั้ง ตลอดไป!";
                } else {
                    $message .= "กดปุ่มด้านล่างเพื่อดูรายละเอียด 👇";
                }

                Log::info('Fortune: แสดงสายงาน', [
                    'user_id' => $facebookUserId,
                    'total_referrals' => $totalReferrals,
                    'converted' => $convertedCount,
                    'from_fortune_referral' => $fortuneReferralCount,
                    'from_mlm_member' => count($mlmDownlineUserIds),
                ]);
            } else {
                $message = "👥 ดูสายงาน\n\n"
                    ."❌ คุณยังไม่มีบัญชีในระบบค่ะ\n"
                    ."ลองดูดวงสักครั้งก่อนนะคะ ระบบจะสร้างบัญชีให้อัตโนมัติ\n\n"
                    ."พิมพ์คำถามมาได้เลย 🔮";
            }

            return [
                'action' => 'downline_info',
                'message' => $message,
                'reading' => null,
                'buttons' => [
                    ['label' => '📊 ผังสายงาน', 'url' => $treeUrl],
                    ['label' => '💵 รายได้ค่าแนะนำ', 'url' => $commissionUrl],
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Fortune: handleDownlineRequest error', [
                'facebook_user_id' => $facebookUserId,
                'error' => $e->getMessage(),
            ]);

            return [
                'action' => 'downline_info',
                'message' => "ขออภัยค่ะ ไม่สามารถดึงข้อมูลได้ในขณะนี้\nกรุณาลองใหม่อีกครั้ง 🙏",
                'reading' => null,
                'buttons' => [
                    ['label' => '📊 ดูสายงานที่เว็บ', 'url' => url('/user/fortune-referral/tree')],
                ],
            ];
        }
    }

    // ============================================================
    // คำสั่ง "รายได้" — ดูรายได้ค่าแนะนำจากสายงาน
    // ============================================================

    /**
     * ตรวจสอบว่าเป็นคำขอดูรายได้หรือไม่
     */
    protected function isEarningsRequest(string $text): bool
    {
        $text = mb_strtolower(trim($text));
        $textNormalized = preg_replace('/\s*(ค่ะ|ครับ|คะ|จ้า|จ้ะ|หน่อย|ด้วย|ที|นะ|นะคะ|นะครับ)\s*$/u', '', $text);

        $exactKeywords = [
            'รายได้', 'ดูรายได้', 'ค่าแนะนำ', 'คอมมิชชั่น',
            'commission', 'earnings', 'ค่าคอม', 'เงินที่ได้',
            'ดูค่าแนะนำ', 'รายได้ของฉัน', 'เช็ครายได้',
        ];

        foreach ($exactKeywords as $keyword) {
            if ($text === $keyword || $textNormalized === $keyword) {
                return true;
            }
        }

        return false;
    }

    /**
     * จัดการคำสั่ง "รายได้" — แสดงรายได้ค่าแนะนำ
     */
    protected function handleEarningsRequest(string $facebookUserId): array
    {
        try {
            $user = $this->findUserByPlatformId($facebookUserId);

            // URL สำหรับปุ่มกด
            $commissionUrl = url('/user/fortune-referral/commissions');
            $treeUrl = url('/user/fortune-referral/tree');
            $commissionAmount = $this->getLevel1CommissionText();

            if ($user) {
                $userName = $user->name ?? 'คุณ';

                // ดึงสรุปรายได้
                $walletBalance = $user->wallet?->balance ?? 0;
                $paidEarnings = 0;
                $approvedEarnings = 0;

                try {
                    $paidEarnings = \App\Models\FortuneCommission::where('user_id', $user->id)
                        ->where('status', 'paid')
                        ->sum('amount');

                    $approvedEarnings = \App\Models\FortuneCommission::where('user_id', $user->id)
                        ->where('status', 'approved')
                        ->sum('amount');
                } catch (\Exception $e) {
                    // table อาจยังไม่มี — ข้ามไป
                }

                $totalEarnings = $paidEarnings + $approvedEarnings;

                $message = "💵 รายได้ค่าแนะนำของคุณ{$userName}\n"
                    ."═══════════════════════\n\n"
                    ."💰 Wallet: ".number_format($walletBalance, 2)." บาท\n"
                    ."📈 รายได้รวม: ".number_format($totalEarnings, 2)." บาท\n";

                if ($approvedEarnings > 0) {
                    $message .= "   ✅ จ่ายแล้ว: ".number_format($paidEarnings, 2)." บาท\n"
                        ."   ⏳ รออนุมัติ: ".number_format($approvedEarnings, 2)." บาท\n";
                }

                $message .= "\nกดปุ่มด้านล่างเพื่อดูรายละเอียด 👇";

                if ($totalEarnings <= 0) {
                    $message = "💵 รายได้ค่าแนะนำของคุณ{$userName}\n"
                        ."═══════════════════════\n\n"
                        ."💰 Wallet: ".number_format($walletBalance, 2)." บาท\n\n"
                        ."💡 ยังไม่มีรายได้ — เริ่มสร้างรายได้!\n"
                        ."พิมพ์ \"แชร์\" ส่งลิงก์ให้เพื่อน\n"
                        ."💰 ค่าแนะนำ: {$commissionAmount} บาท/ครั้ง";
                }

                Log::info('Fortune: แสดงรายได้', [
                    'user_id' => $facebookUserId,
                    'total_earnings' => $totalEarnings,
                    'wallet_balance' => $walletBalance,
                ]);
            } else {
                $message = "💵 ดูรายได้\n\n"
                    ."❌ คุณยังไม่มีบัญชีในระบบค่ะ\n"
                    ."ลองดูดวงสักครั้งก่อนนะคะ ระบบจะสร้างบัญชีให้อัตโนมัติ\n\n"
                    ."พิมพ์คำถามมาได้เลย 🔮";
            }

            return [
                'action' => 'earnings_info',
                'message' => $message,
                'reading' => null,
                'buttons' => [
                    ['label' => '💵 รายได้ค่าแนะนำ', 'url' => $commissionUrl],
                    ['label' => '👥 ผังสายงาน', 'url' => $treeUrl],
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Fortune: handleEarningsRequest error', [
                'facebook_user_id' => $facebookUserId,
                'error' => $e->getMessage(),
            ]);

            return [
                'action' => 'earnings_info',
                'message' => "ขออภัยค่ะ ไม่สามารถดึงข้อมูลได้ในขณะนี้\nกรุณาลองใหม่อีกครั้ง 🙏",
                'reading' => null,
                'buttons' => [
                    ['label' => '💵 ดูรายได้ที่เว็บ', 'url' => url('/user/fortune-referral/commissions')],
                ],
            ];
        }
    }

    // ============================================================
    // คำสั่ง "แผนการตลาด" — แสดงรายละเอียดแผนค่าแนะนำ
    // ============================================================

    /**
     * ตรวจสอบว่าเป็นคำขอดูแผนการตลาดหรือไม่
     */
    protected function isMarketingPlanRequest(string $text): bool
    {
        $text = mb_strtolower(trim($text));
        $textNormalized = preg_replace('/\s*(ค่ะ|ครับ|คะ|จ้า|จ้ะ|หน่อย|ด้วย|ที|นะ|นะคะ|นะครับ)\s*$/u', '', $text);

        $exactKeywords = [
            'แผนการตลาด', 'แผนรายได้', 'marketing',
            'ค่าแนะนำเท่าไหร่', 'ได้เท่าไหร่',
            'แผนค่าแนะนำ', 'วิธีสร้างรายได้', 'แผนธุรกิจ',
        ];

        foreach ($exactKeywords as $keyword) {
            if ($text === $keyword || $textNormalized === $keyword) {
                return true;
            }
        }

        return false;
    }

    /**
     * จัดการคำสั่ง "แผนการตลาด" — แสดงรายละเอียดค่าคอมมิชชั่น
     */
    protected function handleMarketingPlanRequest(string $facebookUserId): array
    {
        try {
            $brandName = $this->settings->getFortuneBrandName();
            $price = number_format($this->getDeepReadingPrice(), 0);

            // ดึงค่าคอมมิชชั่น Level 1 + Level 2
            $readingPrice = (float) ($this->settings->deep_reading_price ?? 0);
            $level1Amount = number_format($this->settings->getFortuneLevel1Amount($readingPrice), 0);
            $level2Amount = number_format($this->settings->getFortuneLevel2Amount($readingPrice), 0);
            $level2Enabled = $this->settings->fortune_level2_enabled ?? true;

            // ตัวอย่างรายได้: 10 คน × 3 ครั้ง/เดือน × level1
            $level1Raw = $this->settings->getFortuneLevel1Amount($readingPrice);
            $exampleMonthly = 10 * 3 * $level1Raw;

            $message = "💰 แผนค่าแนะนำ {$brandName}\n"
                ."═══════════════════════\n\n"
                ."📌 ราคาดูดวงเชิงลึก: {$price} บาท/ครั้ง\n\n"
                ."🏆 ค่าแนะนำ 2 ชั้น:\n"
                ."┌─ ชั้น 1 (สายตรง): {$level1Amount} บาท/ครั้ง\n"
                ."│  คุณแนะนำเพื่อน → เพื่อนดูดวง → คุณได้ค่าแนะนำ\n"
                ."│\n";

            if ($level2Enabled) {
                $message .= "└─ ชั้น 2 (ชั้นหลาน): {$level2Amount} บาท/ครั้ง\n"
                    ."   เพื่อนแนะนำต่อ → คนนั้นดูดวง → คุณยังได้ค่าแนะนำ\n\n";
            } else {
                $message .= "└─ (ค่าแนะนำชั้นเดียว)\n\n";
            }

            $message .= "📊 ตัวอย่างรายได้:\n"
                ."• แนะนำ 10 คน แต่ละคนดูดวง 3 ครั้ง/เดือน\n"
                ."  = 10 × 3 × {$level1Amount} = ".number_format($exampleMonthly, 0)." บาท/เดือน\n\n"
                ."✅ ค่าแนะนำเข้า Wallet อัตโนมัติทันที\n"
                ."✅ ถอนเงินได้ที่เว็บไซต์ เข้าบัญชี 1-3 วันทำการ\n"
                ."✅ ได้ค่าแนะนำตลอดไป ไม่มีหมดอายุ\n\n"
                ."🚀 เริ่มต้น: พิมพ์ \"แชร์\" เพื่อรับลิงก์เชิญเพื่อน";

            Log::info('Fortune: แสดงแผนการตลาด', ['user_id' => $facebookUserId]);

            return [
                'action' => 'marketing_plan',
                'message' => $message,
                'reading' => null,
            ];

        } catch (\Exception $e) {
            Log::error('Fortune: handleMarketingPlanRequest error', [
                'facebook_user_id' => $facebookUserId,
                'error' => $e->getMessage(),
            ]);

            return [
                'action' => 'marketing_plan_error',
                'message' => "ขออภัยค่ะ ไม่สามารถดึงข้อมูลแผนการตลาดได้ในขณะนี้\nกรุณาลองใหม่อีกครั้ง 🙏",
                'reading' => null,
            ];
        }
    }

    // ============================================================
    // Helper methods สำหรับคำสั่งใหม่
    // ============================================================

    /**
     * ค้นหา User จาก platform user ID (LINE/Facebook)
     * ค้นหาหลายวิธีเพื่อให้ครอบคลุม
     */
    protected function findUserByPlatformId(string $platformUserId): ?\App\Models\User
    {
        // ลองหาจาก line_user_id
        $user = \App\Models\User::where('line_user_id', $platformUserId)->first();
        if ($user) {
            return $user;
        }

        // Fallback: ค้นหาจาก email pattern
        $user = \App\Models\User::where('email', 'line_'.$platformUserId.'@thaiprompt.local')->first();
        if ($user) {
            return $user;
        }

        // Fallback: ค้นหาจาก FortuneReading ที่ link กับ user
        $linkedReading = FortuneReading::where('facebook_user_id', $platformUserId)
            ->whereNotNull('user_id')
            ->latest()
            ->first();

        if ($linkedReading && $linkedReading->user_id) {
            return \App\Models\User::find($linkedReading->user_id);
        }

        return null;
    }

    /**
     * ดึงข้อความค่าแนะนำ Level 1 (สำหรับแสดงในข้อความต่างๆ)
     */
    protected function getLevel1CommissionText(): string
    {
        $readingPrice = (float) ($this->settings->deep_reading_price ?? 0);
        $amount = $this->settings->getFortuneLevel1Amount($readingPrice);

        return number_format($amount, 0);
    }
}
