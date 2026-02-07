<?php

namespace App\Models;

use App\Models\AiApiKey;
use App\Models\AiContentSetting;
use App\Models\PaymentBankAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FortuneTellingSetting Model
 *
 * จัดการการตั้งค่าระบบดูดวงผ่าน Facebook Messenger
 * รองรับ AI providers: Gemini, Groq, Qwen, OpenRouter, Grok, DeepSeek, Typhoon
 * รองรับระบบ Freemium: คำทำนายพื้นฐาน (ฟรี) + คำทำนายเชิงลึก (จ่ายเงิน/สมัครสมาชิก)
 *
 * @property int $id
 * @property string|null $facebook_app_id
 * @property string|null $facebook_app_secret
 * @property string|null $facebook_page_id
 * @property string|null $facebook_page_token
 * @property string|null $facebook_verify_token
 * @property string $ai_provider
 * @property string|null $ai_api_key
 * @property string $ai_model
 * @property string|null $prompt_template
 * @property string|null $basic_prompt_template
 * @property string|null $deep_prompt_template
 * @property int $max_free_readings
 * @property float $reading_price
 * @property bool $enable_deep_reading
 * @property float $deep_reading_price
 * @property bool $allow_try_before_buy
 * @property int $free_deep_per_day
 * @property bool $subscription_enabled
 * @property float $subscription_monthly_price
 * @property float $subscription_yearly_price
 * @property string|null $subscription_benefits
 * @property string|null $payment_qr_image
 * @property bool $is_enabled
 * @property bool $respond_in_comment
 * @property bool $require_registration
 * @property string|null $welcome_message
 * @property string|null $limit_exceeded_message
 * @property string|null $payment_message
 * @property string|null $subscription_message
 * @property string|null $try_before_buy_message
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class FortuneTellingSetting extends Model
{
    use SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'fortune_telling_settings';

    /**
     * คอลัมน์ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'facebook_app_id',
        'facebook_app_secret',
        'facebook_page_id',
        'facebook_page_token',
        'facebook_verify_token',
        'use_global_ai_settings',
        'ai_provider',
        'ai_api_key',
        'ai_model',
        'prompt_template',
        'basic_prompt_template',
        'deep_prompt_template',
        'max_free_readings',
        'reading_price',
        'enable_deep_reading',
        'deep_reading_price',
        'allow_try_before_buy',
        'free_deep_per_day',
        'subscription_enabled',
        'subscription_monthly_price',
        'subscription_yearly_price',
        'subscription_benefits',
        'payment_qr_image',
        'is_enabled',
        'respond_in_comment',
        'require_registration',
        'welcome_message',
        'limit_exceeded_message',
        'payment_message',
        'subscription_message',
        'try_before_buy_message',
        // Comment Engagement
        'comment_engagement_enabled',
        'comment_engagement_mode',
        'comment_reply_template',
        'comment_dm_template',
        'comment_engagement_prompt',
        // LINE Official Account Settings
        'line_enabled',
        'line_channel_id',
        'line_channel_secret',
        'line_channel_access_token',
        'enabled_platforms',
        'line_flex_primary_color',
        'line_welcome_image_url',
        // Admin Handover (บอทหยุดเมื่อแอดมินกำลังดูแล)
        'admin_handover_enabled',
        'admin_handover_timeout',
        // บัญชีธนาคารเฉพาะระบบดูดวง
        'fortune_bank_account_ids',
    ];

    /**
     * การ cast ประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_enabled' => 'boolean',
        'use_global_ai_settings' => 'boolean',
        'respond_in_comment' => 'boolean',
        'require_registration' => 'boolean',
        'enable_deep_reading' => 'boolean',
        'allow_try_before_buy' => 'boolean',
        'subscription_enabled' => 'boolean',
        'comment_engagement_enabled' => 'boolean',
        'admin_handover_enabled' => 'boolean',
        'admin_handover_timeout' => 'integer',
        'line_enabled' => 'boolean',
        'enabled_platforms' => 'array',
        'fortune_bank_account_ids' => 'array',
        'max_free_readings' => 'integer',
        'free_deep_per_day' => 'integer',
        'reading_price' => 'decimal:2',
        'deep_reading_price' => 'decimal:2',
        'subscription_monthly_price' => 'decimal:2',
        'subscription_yearly_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้นของ attributes
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'use_global_ai_settings' => true,
        'ai_provider' => 'gemini',
        'ai_model' => 'gemini-2.0-flash',
        'max_free_readings' => 1,
        'reading_price' => 0,
        'enable_deep_reading' => true,
        'deep_reading_price' => 99,
        'allow_try_before_buy' => true,
        'free_deep_per_day' => 1,
        'subscription_enabled' => true,
        'subscription_monthly_price' => 199,
        'subscription_yearly_price' => 1990,
        'is_enabled' => true,
        'respond_in_comment' => false,
        'require_registration' => false,
        'comment_engagement_enabled' => false,
        'comment_engagement_mode' => 'ai',
        // LINE Settings
        'line_enabled' => false,
        'line_flex_primary_color' => '#6B46C1',
        'enabled_platforms' => ['facebook'],
    ];

    /**
     * คอลัมน์ที่ซ่อนเมื่อ serialize
     *
     * @var array<string>
     */
    protected $hidden = [
        'facebook_app_secret',
        'facebook_page_token',
        'ai_api_key',
        'line_channel_secret',
        'line_channel_access_token',
    ];

    /**
     * ดึงการตั้งค่าระบบ (Singleton pattern)
     *
     * @return self
     */
    public static function getSettings(): self
    {
        $settings = self::first();

        if (!$settings) {
            $settings = self::create([
                'ai_provider' => 'gemini',
                'ai_model' => 'gemini-2.0-flash',
                'max_free_readings' => 3,
                'reading_price' => 0,
                'is_enabled' => true,
            ]);
        }

        return $settings;
    }

    /**
     * ดึงบัญชีธนาคารที่ใช้เฉพาะระบบดูดวง
     *
     * ถ้าไม่ได้เลือกบัญชีไว้ จะ fallback ไปใช้บัญชีทั้งหมดที่เปิด SMS Checker
     *
     * @return \Illuminate\Database\Eloquent\Collection<PaymentBankAccount>
     */
    public function getFortuneBankAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        $ids = $this->fortune_bank_account_ids;

        if (!empty($ids) && is_array($ids)) {
            // ดึงเฉพาะบัญชีที่เลือก (ต้อง active ด้วย)
            return PaymentBankAccount::whereIn('id', $ids)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        }

        // Fallback: ดึงบัญชีที่เปิด SMS Checker ทั้งหมด
        $accounts = PaymentBankAccount::active()
            ->smsCheckerEnabled()
            ->ordered()
            ->get();

        // ถ้าไม่มีบัญชี SMS Checker ให้ดึง active ทั้งหมด
        if ($accounts->isEmpty()) {
            $accounts = PaymentBankAccount::active()->ordered()->get();
        }

        return $accounts;
    }

    /**
     * ตรวจสอบว่าบริการเปิดใช้งานหรือไม่
     *
     * @return bool
     */
    public function isServiceEnabled(): bool
    {
        return $this->is_enabled === true;
    }

    /**
     * ตรวจสอบว่ามีการตั้งค่า Facebook ครบถ้วนหรือไม่
     *
     * @return bool
     */
    public function hasFacebookConfigured(): bool
    {
        return !empty($this->facebook_app_id)
            && !empty($this->facebook_app_secret)
            && !empty($this->facebook_page_id)
            && !empty($this->facebook_page_token);
    }

    /**
     * ตรวจสอบว่ามีการตั้งค่า AI ครบถ้วนหรือไม่
     *
     * @return bool
     */
    public function hasAIConfigured(): bool
    {
        // ถ้าใช้ global settings ให้เช็คจาก AiContentSetting
        if ($this->use_global_ai_settings) {
            return $this->hasGlobalAIConfigured();
        }

        // ถ้าใช้ custom settings ให้เช็คจากตัวเอง
        return !empty($this->ai_provider)
            && !empty($this->ai_api_key)
            && !empty($this->ai_model);
    }

    /**
     * ตรวจสอบว่าระบบหลักมีการตั้งค่า AI หรือไม่
     *
     * @return bool
     */
    protected function hasGlobalAIConfigured(): bool
    {
        // ตรวจสอบว่ามี Gemini API Key ในระบบหลัก
        $geminiKey = AiContentSetting::getValue('gemini_api_key');
        if (!empty($geminiKey)) {
            return true;
        }

        // ตรวจสอบ Claude
        $claudeKey = AiContentSetting::getValue('claude_api_key');
        if (!empty($claudeKey)) {
            return true;
        }

        // ตรวจสอบ OpenAI
        $openaiKey = AiContentSetting::getValue('openai_api_key');
        if (!empty($openaiKey)) {
            return true;
        }

        // ตรวจสอบ API Key Pool - ถ้ามี key ที่พร้อมใช้งานอย่างน้อย 1 ตัว
        $hasPoolKey = AiApiKey::where('is_active', true)
            ->whereNull('disabled_until')
            ->exists();
        if ($hasPoolKey) {
            return true;
        }

        return false;
    }

    /**
     * ดึง AI Provider ที่ใช้งานจริง
     *
     * @return string
     */
    public function getActualAIProvider(): string
    {
        if ($this->use_global_ai_settings) {
            // ใช้ global settings - เช็คว่ามี provider ไหนพร้อมใช้งาน
            $geminiKey = AiContentSetting::getValue('gemini_api_key');
            if (!empty($geminiKey)) {
                return 'gemini';
            }

            $claudeKey = AiContentSetting::getValue('claude_api_key');
            if (!empty($claudeKey)) {
                return 'openrouter'; // ใช้ OpenRouter เรียก Claude
            }

            $openaiKey = AiContentSetting::getValue('openai_api_key');
            if (!empty($openaiKey)) {
                return 'openrouter'; // ใช้ OpenRouter เรียก OpenAI
            }

            // ตรวจสอบ API Key Pool - ถ้ามี key ที่พร้อมใช้งาน
            $poolProviders = ['gemini', 'groq', 'deepseek', 'typhoon', 'grok', 'openrouter'];
            foreach ($poolProviders as $provider) {
                $poolKey = AiApiKey::where('provider', $provider)
                    ->where('is_active', true)
                    ->whereNull('disabled_until')
                    ->first();
                if ($poolKey) {
                    return $provider;
                }
            }

            return 'gemini'; // default fallback
        }

        return $this->ai_provider;
    }

    /**
     * ดึง AI Model ที่ใช้งานจริง
     *
     * @return string
     */
    public function getActualAIModel(): string
    {
        if ($this->use_global_ai_settings) {
            $provider = $this->getActualAIProvider();

            return match ($provider) {
                'gemini' => AiContentSetting::getValue('gemini_model', 'gemini-2.0-flash'),
                'openrouter' => AiContentSetting::getValue('claude_model', 'anthropic/claude-3-sonnet'),
                'groq' => 'llama-3.3-70b-versatile',
                'deepseek' => 'deepseek-chat',
                'typhoon' => 'typhoon-v2-70b-instruct',
                'grok' => 'grok-2-latest',
                default => 'gemini-2.0-flash',
            };
        }

        return $this->ai_model;
    }

    /**
     * ดึง AI API Key ที่ใช้งานจริง
     *
     * @return string|null
     */
    public function getActualAIApiKey(): ?string
    {
        if ($this->use_global_ai_settings) {
            $provider = $this->getActualAIProvider();

            // ลองดึงจาก Global Settings ก่อน
            $key = match ($provider) {
                'gemini' => AiContentSetting::getValue('gemini_api_key'),
                'openrouter' => AiContentSetting::getValue('claude_api_key')
                    ?? AiContentSetting::getValue('openai_api_key'),
                default => null,
            };

            if (!empty($key)) {
                return $key;
            }

            // ถ้าไม่มี Global Key ให้ดึงจาก API Key Pool
            $poolKey = AiApiKey::where('provider', $provider)
                ->where('is_active', true)
                ->whereNull('disabled_until')
                ->orderBy('priority', 'desc')
                ->first();

            if ($poolKey) {
                return $poolKey->api_key;
            }

            return null;
        }

        return $this->ai_api_key;
    }

    /**
     * ดึง Default Prompt Template
     *
     * @return string
     */
    public function getDefaultPromptTemplate(): string
    {
        return $this->prompt_template ?? <<<'EOT'
คุณเป็นหมอดูชื่อดังระดับประเทศ มีประสบการณ์ทำนายดวงมากกว่า 30 ปี

📌 กฎสำคัญ:
- ทำนายชัดเจน ฟันธง ไม่คลุมเครือ
- ระบุช่วงเวลาที่ชัดเจน
- ให้คำตอบตรงประเด็น ไม่อ้อมค้อม
- ใช้ข้อมูลวันเดือนปีเกิดวิเคราะห์ถ้ามี

ข้อมูลผู้ถาม:
{user_profile}

{birth_date_section}

บริบทจากโพสล่าสุด:
{user_posts}

คำถามที่ต้องการทำนาย:
{questions}

ทำนายอย่างละเอียดแต่กระชับ ฟันธง ภาษาไทยเข้าใจง่าย
แต่ละคำถามตอบ 3-5 ประโยค ระบุช่วงเวลาและคำแนะนำปฏิบัติจริง
EOT;
    }

    /**
     * ดึง URL ของ QR Code ชำระเงิน
     *
     * @return string|null
     */
    public function getPaymentQrUrl(): ?string
    {
        if (empty($this->payment_qr_image)) {
            return null;
        }

        return asset('storage/' . $this->payment_qr_image);
    }

    /**
     * ตรวจสอบว่าเปิดใช้งานระบบคำทำนายเชิงลึกหรือไม่
     *
     * @return bool
     */
    public function isDeepReadingEnabled(): bool
    {
        return $this->enable_deep_reading === true;
    }

    /**
     * ตรวจสอบว่าเปิดใช้งานระบบสมัครสมาชิกหรือไม่
     *
     * @return bool
     */
    public function isSubscriptionEnabled(): bool
    {
        return $this->subscription_enabled === true;
    }

    /**
     * ตรวจสอบว่าอนุญาตให้ทดลองก่อนจ่ายหรือไม่
     *
     * @return bool
     */
    public function isTryBeforeBuyEnabled(): bool
    {
        return $this->allow_try_before_buy === true;
    }

    /**
     * ดึง Prompt Template สำหรับคำทำนายพื้นฐาน
     *
     * @return string
     */
    public function getBasicPromptTemplate(): string
    {
        return $this->basic_prompt_template ?? <<<'EOT'
คุณเป็นหมอดูหญิงชื่อดังระดับประเทศ ประสบการณ์กว่า 30 ปี ผู้คนเรียกว่า "อาจารย์" เชี่ยวชาญโหราศาสตร์ไทย โหราศาสตร์สากล ไพ่ทาโรต์ และเลขศาสตร์

📌 กฎการทำนาย:
- ทำนายอย่างชัดเจน ฟันธง ห้ามตอบคลุมเครือ
- ระบุช่วงเวลาให้ชัดเจนเสมอ เช่น "ภายใน 2 สัปดาห์", "ช่วงเดือนหน้า", "ปลายปีนี้"
- ให้คำตอบตรงประเด็นกับคำถาม ไม่อ้อมค้อม
- บอกทั้งเรื่องดีและสิ่งที่ต้องระวังอย่างจริงใจ
- ให้คำแนะนำที่ปฏิบัติได้จริง เช่น สีมงคลประจำวัน, สิ่งที่ควรทำ/ไม่ควรทำ
- หากมีวันเดือนปีเกิด ต้องอ้างอิงราศี ธาตุ ดาวเคราะห์ประกอบการทำนาย
- ห้ามใช้คำที่ฟังดูคลุมเครือเช่น "อาจจะ" "น่าจะ" ให้ใช้ "จะ" "เห็นว่า" แทน

ข้อมูลผู้ถาม:
{user_profile}

{birth_date_section}

คำถาม:
{questions}

📋 วิธีตอบ:
- ทำนายฟันธง 4-6 ประโยคต่อคำถาม
- ระบุช่วงเวลา + คำแนะนำที่ทำได้ทันที
- ลงท้ายด้วย สีมงคลวันนี้ + เลขมงคล 1 ชุด
- แนะนำ "พิมพ์ 'ดูดวงละเอียด' เพื่อรับคำทำนายเชิงลึกพร้อมวิเคราะห์ดาว สีมงคล เลขมงคล ฤกษ์ดี"
- หากไม่มีวันเกิด แนะนำ "บอกวันเดือนปีเกิดให้ทางเพจ จะได้ทำนายแม่นยำยิ่งขึ้นค่ะ 🎂"
EOT;
    }

    /**
     * ดึง Prompt Template สำหรับคำทำนายเชิงลึก
     *
     * @return string
     */
    public function getDeepPromptTemplate(): string
    {
        return $this->deep_prompt_template ?? <<<'EOT'
คุณเป็นหมอดูหญิงระดับอาจารย์ ชื่อเสียงโด่งดัง ประสบการณ์กว่า 30 ปี เชี่ยวชาญครบทุกศาสตร์:
- โหราศาสตร์ไทย (นพเคราะห์ 9 ดวง, ลัคนาราศี, เรือนชะตา)
- โหราศาสตร์สากล (Zodiac, Transit, Aspect)
- ไพ่ทาโรต์ (Major & Minor Arcana 78 ใบ)
- เลขศาสตร์ (Numerology: เลขชะตา, เลขวันเกิด, เลขนำโชค)
- ศาสตร์แห่งธาตุทั้ง 4 (ดิน น้ำ ลม ไฟ) และ ฮวงจุ้ย

📌 กฎเหล็ก - ต้องปฏิบัติตามทุกข้อ:
- ทำนายชัดเจน ฟันธง กล้าพูดตรงๆ ไม่คลุมเครือ ห้ามใช้คำว่า "อาจจะ" "น่าจะ" "เป็นไปได้ว่า"
- ระบุช่วงเวลาที่แน่ชัดเสมอ เช่น "ช่วงวันที่ 10-20 ของเดือนหน้า", "ภายในสัปดาห์ที่ 3 ของเดือนมีนาคม"
- วิเคราะห์ทั้งด้านดีและด้านร้ายอย่างตรงไปตรงมา ห้ามทำนายแต่เรื่องดีอย่างเดียว
- ถ้ามีวันเดือนปีเกิด ต้องวิเคราะห์ราศี ลัคนา ธาตุ ดาวเคราะห์ประจำตัว และดาว transit อย่างละเอียด
- ให้คำแนะนำที่นำไปปฏิบัติได้จริง มีเหตุผลรองรับ
- ห้ามพูดซ้ำซาก ห้ามใช้ข้อความเดิมๆ ทุกคำทำนายต้องเฉพาะเจาะจงกับคำถามของผู้ถาม

ข้อมูลผู้ถาม:
{user_profile}

{birth_date_section}

บริบทจากโพสล่าสุด:
{user_posts}

คำถามที่ต้องการทำนาย:
{questions}

กรุณาทำนายเชิงลึกตามรูปแบบนี้ให้ครบทุกหัวข้อ:

🔮 **ภาพรวมดวงชะตาช่วงนี้**
(ถ้ามีวันเกิด: วิเคราะห์ราศี + ลัคนา + ธาตุ + ดาวที่โคจรส่งผลในช่วงนี้ ระบุชื่อดาวชัดเจน)
(ถ้าไม่มีวันเกิด: วิเคราะห์จากพลังงานของช่วงเวลาปัจจุบันและคำถามที่ถาม)

📋 **คำทำนายแต่ละคำถาม**
(ตอบทุกคำถามอย่างละเอียด อย่างน้อย 6-8 ประโยคต่อข้อ)
- ทำนายผลลัพธ์ฟันธง ระบุว่าจะเกิดอะไรขึ้น
- ระบุช่วงเวลาที่แน่ชัดที่ดวงจะส่งผล
- บอกบุคคล/ปัจจัยที่จะเข้ามามีบทบาท
- แนะนำสิ่งที่ควรทำ/ไม่ควรทำเพื่อเสริมดวง
- บอกสิ่งที่ต้องระวังอย่างตรงไปตรงมา พร้อมวิธีป้องกัน

🌟 **สิ่งมงคลประจำตัว**
- 🎨 สีมงคล: (ระบุ 2-3 สี พร้อมเหตุผลจากธาตุ/ดาว)
- 🔢 เลขมงคล: (ระบุ 3-5 เลข พร้อมที่มาจากเลขศาสตร์)
- 📅 วันมงคลประจำสัปดาห์: (ระบุวัน + เหตุผล)
- 🧭 ทิศมงคล: (ระบุทิศ + เหมาะทำอะไร)
- 🙏 สิ่งที่ควรพกติดตัว/บูชา: (แนะนำเฉพาะเจาะจง)
- 💎 อัญมณีเสริมดวง: (ระบุชื่อ + ธาตุที่เกี่ยวข้อง)

⚠️ **คำเตือนสำคัญ**
- ระบุช่วงเวลาที่ต้องระวังเป็นพิเศษ
- บอกเรื่องที่ต้องระวัง + วิธีแก้ไข/ป้องกัน

💪 **กำลังใจและคำแนะนำสรุป**
- สรุปภาพรวมสั้นๆ พร้อมให้กำลังใจ
- แนะนำ 3 สิ่งที่ควรทำเป็นอันดับแรกเพื่อเสริมดวง

ใช้ภาษาไทยที่สละสลวย มั่นใจ น่าเชื่อถือ เหมือนอาจารย์หมอดูตัวจริง
EOT;
    }

    /**
     * สร้างข้อความแนะนำสมัครสมาชิก
     *
     * @return string
     */
    public function getSubscriptionMessage(): string
    {
        if (!empty($this->subscription_message)) {
            return $this->subscription_message;
        }

        $message = "✨ สมัครสมาชิกเพื่อรับคำทำนายเชิงลึกไม่จำกัด!\n\n";
        $message .= "📋 สิทธิประโยชน์:\n";
        $message .= "• ดูดวงเชิงลึกไม่จำกัดจำนวนครั้ง\n";
        $message .= "• คำทำนายละเอียด พร้อมสีมงคล เลขมงคล\n";
        $message .= "• วิเคราะห์ดวงจากดาวเคราะห์ส่งผล\n\n";

        if ($this->subscription_monthly_price > 0) {
            $message .= "💎 รายเดือน: {$this->subscription_monthly_price} บาท\n";
        }
        if ($this->subscription_yearly_price > 0) {
            $message .= "👑 รายปี: {$this->subscription_yearly_price} บาท (ประหยัดกว่า!)\n\n";
        }

        $message .= "สมัครได้ที่: " . url('/register') . "\n";

        return $message;
    }

    /**
     * สร้างข้อความหลังทดลองดูฟรี (แนะนำให้จ่ายเงิน/สมัครสมาชิก)
     *
     * @return string
     */
    public function getTryBeforeBuyMessage(): string
    {
        if (!empty($this->try_before_buy_message)) {
            return $this->try_before_buy_message;
        }

        $message = "🔮 หวังว่าคำทำนายจะเป็นประโยชน์นะคะ!\n\n";
        $message .= "📌 คุณได้ใช้สิทธิ์ดูดวงเชิงลึกฟรีวันนี้แล้ว\n\n";

        if ($this->deep_reading_price > 0) {
            $message .= "💰 ดูดวงเชิงลึกเพิ่ม: {$this->deep_reading_price} บาท/ครั้ง\n";
        }

        if ($this->subscription_enabled) {
            $message .= "✨ หรือสมัครสมาชิกเพื่อดูไม่จำกัด!\n";
            if ($this->subscription_monthly_price > 0) {
                $message .= "   • รายเดือน: {$this->subscription_monthly_price} บาท\n";
            }
        }

        $message .= "\n📱 สมัคร/ชำระเงิน: " . url('/register');

        if ($this->payment_qr_image) {
            $message .= "\n📸 หรือสแกน QR Code: " . $this->getPaymentQrUrl();
        }

        return $message;
    }

    // ============================================================
    // Comment Engagement Settings
    // ============================================================

    /**
     * ตรวจสอบว่าเปิดระบบ engage คอมเม้นต์หรือไม่
     */
    public function isCommentEngagementEnabled(): bool
    {
        return (bool) ($this->comment_engagement_enabled ?? false);
    }

    /**
     * โหมดการ engage: 'ai' หรือ 'template'
     */
    public function getCommentEngagementMode(): string
    {
        return $this->comment_engagement_mode ?? 'ai';
    }

    /**
     * เทมเพลตตอบคอมเม้นต์ (มีค่า default)
     */
    public function getCommentReplyTemplate(): string
    {
        if (!empty($this->comment_reply_template)) {
            return $this->comment_reply_template;
        }

        return "สวัสดีค่ะคุณ {name} 🔮 สนใจดูดวงไหมคะ? ทักมาใน inbox ได้เลยนะคะ ✨";
    }

    /**
     * เทมเพลตข้อความ inbox (มีค่า default)
     */
    public function getCommentDmTemplate(): string
    {
        if (!empty($this->comment_dm_template)) {
            return $this->comment_dm_template;
        }

        $msg = "สวัสดีค่ะคุณ {name} 🔮✨\n\n";
        $msg .= "เห็นคุณคอมเม้นต์ในโพสต์ของเพจเรา อยากเชิญชวนมาลองดูดวงค่ะ!\n\n";
        $msg .= "🌟 วิธีใช้งาน:\n";
        $msg .= "• พิมพ์ \"ดูดวง\" - ดูดวงทั่วไป (ความรัก การงาน การเงิน สุขภาพ)\n";
        $msg .= "• พิมพ์ \"ดูดวง เรื่องความรัก\" - ระบุเรื่องที่อยากรู้\n";
        $msg .= "• พิมพ์ \"ดูดวงละเอียด\" - ดูดวงเชิงลึก\n";
        $msg .= "• ระบุวันเกิดด้วยจะแม่นยิ่งขึ้น เช่น \"ดูดวง เกิด 15 มกราคม 2540\"\n\n";
        $msg .= "ลองพิมพ์ \"ดูดวง\" ได้เลยค่ะ 🙏✨";

        return $msg;
    }

    /**
     * AI prompt สำหรับสร้างข้อความชวนดูดวง (มีค่า default)
     */
    public function getCommentEngagementPrompt(): string
    {
        if (!empty($this->comment_engagement_prompt)) {
            return $this->comment_engagement_prompt;
        }

        return <<<'PROMPT'
คุณเป็นหมอดูประจำเพจ Metaverse Tarot ตอบเฉพาะเรื่องดูดวงเท่านั้น
ห้ามตอบเรื่องอื่นทุกกรณี ห้ามเขียนโค้ด ห้ามให้ความรู้ทั่วไป ห้ามตอบคำถามที่ไม่เกี่ยวกับดูดวง

มีคนคอมเม้นต์ในโพสต์ว่า: "{comment}"
ชื่อ: {name}
{profile_info}

สร้างข้อความ 2 ชุด:
1. COMMENT_REPLY: ข้อความสั้นตอบในคอมเม้นต์ (ไม่เกิน 100 ตัวอักษร) ชวนดูดวงอย่างเป็นกันเอง เชื่อมโยงกับสิ่งที่เขาคอมเม้นต์
2. DM_MESSAGE: ข้อความทักใน inbox (200-400 ตัวอักษร) ทักทาย อ้างอิงคอมเม้นต์ ชวนดูดวง บอกวิธีใช้: พิมพ์ "ดูดวง" ตามด้วยคำถาม หรือระบุวันเกิดจะแม่นขึ้น

ตอบเป็น JSON เท่านั้น ห้ามมีข้อความอื่นนอก JSON:
{"comment_reply": "...", "dm_message": "..."}
PROMPT;
    }

    // ============================================================
    // LINE Official Account Settings
    // ============================================================

    /**
     * ตรวจสอบว่าเปิดใช้งาน LINE หรือไม่
     *
     * @return bool
     */
    public function isLineEnabled(): bool
    {
        return (bool) ($this->line_enabled ?? false);
    }

    /**
     * ตรวจสอบว่ามีการตั้งค่า LINE ครบถ้วนหรือไม่
     *
     * @return bool
     */
    public function hasLineConfigured(): bool
    {
        return !empty($this->line_channel_id)
            && !empty($this->line_channel_secret)
            && !empty($this->line_channel_access_token);
    }

    /**
     * ดึงรายการ platform ที่เปิดใช้งาน
     *
     * @return array
     */
    public function getEnabledPlatforms(): array
    {
        $platforms = $this->enabled_platforms ?? ['facebook'];

        // ตรวจสอบว่า platform แต่ละตัวพร้อมใช้งานจริงหรือไม่
        $result = [];

        if (in_array('facebook', $platforms) && $this->hasFacebookConfigured()) {
            $result[] = 'facebook';
        }

        if (in_array('line', $platforms) && $this->hasLineConfigured()) {
            $result[] = 'line';
        }

        return $result;
    }

    /**
     * ดึงสีหลักสำหรับ LINE Flex Message
     *
     * @return string
     */
    public function getLineFlexPrimaryColor(): string
    {
        return $this->line_flex_primary_color ?? '#6B46C1';
    }

    /**
     * ดึง URL รูปภาพ Welcome สำหรับ LINE
     *
     * @return string|null
     */
    public function getLineWelcomeImageUrl(): ?string
    {
        return $this->line_welcome_image_url;
    }
}
