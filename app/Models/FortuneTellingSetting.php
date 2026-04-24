<?php

namespace App\Models;

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
        'line_bot_basic_id',
        'line_channel_secret',
        'line_channel_access_token',
        'enabled_platforms',
        'line_flex_primary_color',
        'line_welcome_image_url',
        'fortune_brand_name',
        // Admin Handover (บอทหยุดเมื่อแอดมินกำลังดูแล)
        'admin_handover_enabled',
        'admin_handover_timeout',
        // Admin Takeover (เทคโอเวอร์แบบใหม่ — LINE+Facebook รวมกัน)
        'ai_resume_command',
        'customer_handoff_keywords',
        'takeover_notify_customer',
        'takeover_customer_message',
        'takeover_resume_message',
        // บัญชีธนาคารเฉพาะระบบดูดวง
        'fortune_bank_account_ids',
        // โหมดแสดงช่องทางชำระเงิน (both, bank_only, promptpay_only)
        'payment_display_mode',
        // Affiliate/MLM Settings สำหรับลงทะเบียนอัตโนมัติ
        'fortune_affiliate_enabled',
        'fortune_auto_register_enabled',
        'fortune_pv_value',
        'fortune_use_global_commission_rate',
        'fortune_custom_commission_per_pv',
        'fortune_affiliate_invite_message',
        // โหมดจ่ายคอมมิชชั่น: 'pv' หรือ 'static'
        'fortune_commission_mode',
        'fortune_static_commission_amount',
        // Level 1/Level 2 commission settings (ระบบดูดวง)
        'fortune_level1_commission_type',
        'fortune_level1_commission_amount',
        'fortune_level2_enabled',
        'fortune_level2_commission_type',
        'fortune_level2_commission_amount',
        // AI Chat ทั่วไป (สนทนาอัจฉริยะ — แยก provider จากทำนาย)
        'enable_ai_chat',
        'chat_ai_provider',
        'chat_ai_model',
        'chat_ai_api_key',
        'chat_system_prompt',
        // ระบบดูดวงสาธารณะ (Horoscope Public)
        'horoscope_public_enabled',
        'horoscope_free_daily_limit',
        'horoscope_dream_free_limit',
        'horoscope_numerology_free_limit',
        'horoscope_seo_title_th',
        'horoscope_seo_description_th',
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
        'customer_handoff_keywords' => 'array',
        'takeover_notify_customer' => 'boolean',
        'line_enabled' => 'boolean',
        'enabled_platforms' => 'array',
        'fortune_bank_account_ids' => 'array',
        'max_free_readings' => 'integer',
        'free_deep_per_day' => 'integer',
        'reading_price' => 'decimal:2',
        'deep_reading_price' => 'decimal:2',
        'subscription_monthly_price' => 'decimal:2',
        'subscription_yearly_price' => 'decimal:2',
        'fortune_affiliate_enabled' => 'boolean',
        'fortune_auto_register_enabled' => 'boolean',
        'fortune_pv_value' => 'decimal:2',
        'fortune_use_global_commission_rate' => 'boolean',
        'fortune_custom_commission_per_pv' => 'decimal:2',
        'fortune_static_commission_amount' => 'decimal:2',
        'fortune_level1_commission_amount' => 'decimal:2',
        'fortune_level2_enabled' => 'boolean',
        'fortune_level2_commission_amount' => 'decimal:2',
        'enable_ai_chat' => 'boolean',
        // ระบบดูดวงสาธารณะ
        'horoscope_public_enabled' => 'boolean',
        'horoscope_free_daily_limit' => 'integer',
        'horoscope_dream_free_limit' => 'integer',
        'horoscope_numerology_free_limit' => 'integer',
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
        'fortune_brand_name' => 'แม่หมอจันทรา',
        'enabled_platforms' => '["facebook"]',
        // Affiliate Settings (เปิดเป็นค่าเริ่มต้น)
        'fortune_affiliate_enabled' => true,
        'fortune_auto_register_enabled' => true,
        'fortune_pv_value' => 0,
        'fortune_use_global_commission_rate' => true,
        'fortune_commission_mode' => 'static',  // 'pv' = ใช้ PV ตาม MLM, 'static' = จ่ายตรง
        'fortune_static_commission_amount' => 10,
        // AI Chat ทั่วไป (ค่าเริ่มต้นเปิดใช้งาน Gemini)
        'enable_ai_chat' => true,
        'chat_ai_provider' => 'groq',
        'chat_ai_model' => 'llama-3.3-70b-versatile',
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
        'chat_ai_api_key',
        'line_channel_secret',
        'line_channel_access_token',
    ];

    /**
     * ดึงการตั้งค่าระบบ (Singleton pattern)
     */
    /**
     * Cache instance สำหรับ request เดียวกัน (ลดการ query DB ซ้ำ)
     */
    protected static ?self $cachedInstance = null;

    public static function getSettings(): self
    {
        // ⚡ Cache per-request: ลดจาก 3+ DB queries เหลือ 1
        if (static::$cachedInstance !== null) {
            return static::$cachedInstance;
        }

        $settings = self::first();

        if (! $settings) {
            $settings = self::create([
                'ai_provider' => 'gemini',
                'ai_model' => 'gemini-2.0-flash',
                'max_free_readings' => 3,
                'reading_price' => 0,
                'is_enabled' => true,
            ]);
        }

        static::$cachedInstance = $settings;

        return $settings;
    }

    /**
     * ล้าง cache (ใช้เมื่ออัพเดทค่า settings)
     */
    public static function clearSettingsCache(): void
    {
        static::$cachedInstance = null;
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

        if (! empty($ids) && is_array($ids)) {
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
     * ดึงโหมดแสดงช่องทางชำระเงิน
     *
     * @return string 'both', 'bank_only', 'promptpay_only'
     */
    public function getPaymentDisplayMode(): string
    {
        return $this->payment_display_mode ?? 'both';
    }

    /**
     * ตรวจสอบว่าควรแสดงเลขบัญชีธนาคารหรือไม่
     * จะไม่แสดงเมื่อโหมดเป็น promptpay_only
     */
    public function shouldShowBankAccount(): bool
    {
        return $this->getPaymentDisplayMode() !== 'promptpay_only';
    }

    /**
     * ตรวจสอบว่าควรแสดงพร้อมเพย์หรือไม่
     * จะไม่แสดงเมื่อโหมดเป็น bank_only
     */
    public function shouldShowPromptpay(): bool
    {
        return $this->getPaymentDisplayMode() !== 'bank_only';
    }

    /**
     * ตรวจสอบว่าบริการเปิดใช้งานหรือไม่
     */
    public function isServiceEnabled(): bool
    {
        return $this->is_enabled === true;
    }

    /**
     * ตรวจสอบว่ามีการตั้งค่า Facebook ครบถ้วนหรือไม่
     */
    public function hasFacebookConfigured(): bool
    {
        return ! empty($this->facebook_app_id)
            && ! empty($this->facebook_app_secret)
            && ! empty($this->facebook_page_id)
            && ! empty($this->facebook_page_token);
    }

    /**
     * ตรวจสอบว่ามีการตั้งค่า AI ครบถ้วนหรือไม่
     */
    public function hasAIConfigured(): bool
    {
        // ถ้าใช้ global settings ให้เช็คจาก AiContentSetting
        if ($this->use_global_ai_settings) {
            return $this->hasGlobalAIConfigured();
        }

        // ถ้าใช้ custom settings ให้เช็คจากตัวเอง
        return ! empty($this->ai_provider)
            && ! empty($this->ai_api_key)
            && ! empty($this->ai_model);
    }

    /**
     * ตรวจสอบว่าระบบหลักมีการตั้งค่า AI หรือไม่
     */
    protected function hasGlobalAIConfigured(): bool
    {
        // ตรวจสอบว่ามี Gemini API Key ในระบบหลัก
        $geminiKey = AiContentSetting::getValue('gemini_api_key');
        if (! empty($geminiKey)) {
            return true;
        }

        // ตรวจสอบ Claude
        $claudeKey = AiContentSetting::getValue('claude_api_key');
        if (! empty($claudeKey)) {
            return true;
        }

        // ตรวจสอบ OpenAI
        $openaiKey = AiContentSetting::getValue('openai_api_key');
        if (! empty($openaiKey)) {
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
     */
    public function getActualAIProvider(): string
    {
        if ($this->use_global_ai_settings) {
            // ใช้ global settings - เช็คว่ามี provider ไหนพร้อมใช้งาน
            $geminiKey = AiContentSetting::getValue('gemini_api_key');
            if (! empty($geminiKey)) {
                return 'gemini';
            }

            $claudeKey = AiContentSetting::getValue('claude_api_key');
            if (! empty($claudeKey)) {
                return 'openrouter'; // ใช้ OpenRouter เรียก Claude
            }

            $openaiKey = AiContentSetting::getValue('openai_api_key');
            if (! empty($openaiKey)) {
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

            if (! empty($key)) {
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
     */
    public function getPaymentQrUrl(): ?string
    {
        if (empty($this->payment_qr_image)) {
            return null;
        }

        return asset('storage/'.$this->payment_qr_image);
    }

    /**
     * ตรวจสอบว่าเปิดใช้งานระบบคำทำนายเชิงลึกหรือไม่
     */
    public function isDeepReadingEnabled(): bool
    {
        return $this->enable_deep_reading === true;
    }

    /**
     * ตรวจสอบว่าเปิดบริการดูดวงฟรีหรือไม่
     *
     * เปิดเมื่อ max_free_readings > 0 เท่านั้น
     * ถ้าปิด → ระบบจะไม่พูดถึงการดูดวงฟรีเลย และชี้ไปที่ดูดวงเสียค่าครูแทน
     */
    public function isFreeReadingEnabled(): bool
    {
        return (int) ($this->max_free_readings ?? 0) > 0;
    }

    /**
     * ตรวจสอบว่าระบบดูดวงสาธารณะ (Frontend /horoscope) ยังมีการใช้งานฟรีอยู่หรือไม่
     *
     * เปิดเมื่ออย่างน้อยหนึ่งใน horoscope_free_daily_limit, horoscope_dream_free_limit,
     * หรือ horoscope_numerology_free_limit มีค่า > 0
     * ใช้สำหรับซ่อน/แสดงคำว่า "ดูดวงฟรี" บนหน้าเว็บ
     */
    public function isHoroscopePublicFreeEnabled(): bool
    {
        return (int) ($this->horoscope_free_daily_limit ?? 0) > 0
            || (int) ($this->horoscope_dream_free_limit ?? 0) > 0
            || (int) ($this->horoscope_numerology_free_limit ?? 0) > 0;
    }

    /**
     * ตรวจสอบว่าเปิดใช้งานระบบสมัครสมาชิกหรือไม่
     */
    public function isSubscriptionEnabled(): bool
    {
        return $this->subscription_enabled === true;
    }

    /**
     * ตรวจสอบว่าอนุญาตให้ทดลองก่อนจ่ายหรือไม่
     */
    public function isTryBeforeBuyEnabled(): bool
    {
        return $this->allow_try_before_buy === true;
    }

    /**
     * ดึง Prompt Template สำหรับคำทำนายพื้นฐาน
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
     */
    public function getSubscriptionMessage(): string
    {
        if (! empty($this->subscription_message)) {
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

        $message .= 'สมัครได้ที่: '.url('/register')."\n";

        return $message;
    }

    /**
     * สร้างข้อความหลังทดลองดูฟรี (แนะนำให้จ่ายเงิน/สมัครสมาชิก)
     */
    public function getTryBeforeBuyMessage(): string
    {
        if (! empty($this->try_before_buy_message)) {
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

        $message .= "\n📱 สมัคร/ชำระเงิน: ".url('/register');

        if ($this->payment_qr_image) {
            $message .= "\n📸 หรือสแกน QR Code: ".$this->getPaymentQrUrl();
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
        if (! empty($this->comment_reply_template)) {
            return $this->comment_reply_template;
        }

        return 'สวัสดีค่ะคุณ {name} 🔮 สนใจดูดวงไหมคะ? ทักมาใน inbox ได้เลยนะคะ ✨';
    }

    /**
     * เทมเพลตข้อความ inbox (มีค่า default)
     *
     * 🎯 Phase L — รับ $userId เพื่อ rotate variant (stable per user)
     *   - ถ้าแอดมินตั้ง custom template → ใช้ตามนั้น (เหมือนเดิม)
     *   - ถ้าไม่ได้ตั้ง → สุ่มจาก 5 variants ตาม crc32($userId)
     *
     * @param  string|null  $userId  facebook user id (ถ้า null → variant แรก)
     */
    public function getCommentDmTemplate(?string $userId = null): string
    {
        if (! empty($this->comment_dm_template)) {
            return $this->comment_dm_template;
        }

        // ดึงราคาจริงจาก admin settings (ไม่ใช่ hardcoded 49)
        $price = (float) ($this->deep_reading_price ?? 0);
        if ($price <= 0) {
            $price = (float) ($this->reading_price ?? 0);
        }
        if ($price <= 0) {
            $price = 39;
        }
        $price = (int) $price;

        $variants = [
            // v1: coffee + self-drawn card (core pitch จากผู้ใช้)
            "สวัสดีค่ะคุณ {name} 🌙\n\n"
                ."ขอบคุณที่คอมเม้นต์ให้เพจเรานะคะ\n"
                ."หมอจันทราอยากเชิญชวนลองดูดวงดู\n\n"
                ."☕ ดูดวงเชิงลึก {$price} บาท — ถูกกว่าค่ากาแฟ 1 แก้วด้วยซ้ำ\n"
                ."วิเคราะห์จาก **ดาวเจ้าชนะของคุณเอง**\n"
                ."+ ไพ่ที่พลังจิตคุณเลือกออกมา (เหมือนจับไพ่เอง)\n\n"
                ."ถ้าพร้อม → พิมพ์ \"ดูดวง\" ได้เลยนะคะ 🙏",

            // v2: testimonial
            "สวัสดีค่ะคุณ {name} ✨\n\n"
                ."หลายคนทักมาบอกว่า คำทำนายของหมอจันทรา\n"
                ."\"เจอจุดที่ไม่เคยคิดมาก่อน\" 🌟\n\n"
                ."💎 ดูดวงเชิงลึก {$price} บาท\n"
                ."• ถามได้ 2 คำถาม\n"
                ."• ดาวเจ้าชนะ + ไพ่ยิปซี + สีมงคล + เลขมงคล\n\n"
                ."พิมพ์ \"ดูดวง\" มาเริ่มได้เลยค่ะ 🔮",

            // v3: emotional / consultant frame
            "สวัสดีค่ะคุณ {name} 💫\n\n"
                ."ถ้ามีเรื่องในใจที่ไม่รู้จะไปปรึกษาใคร\n"
                ."หมอจันทราฟัง + ชี้ทางออกจากดวงของคุณเอง\n\n"
                ."{$price} บาท = ค่าที่ปรึกษาที่ตั้งใจ\n"
                ."วิเคราะห์ดาวเจ้าชนะ + ไพ่ ของคุณคนเดียว\n\n"
                ."พิมพ์ \"ดูดวง\" ลองดูนะคะ 🙏",

            // v4: transit/timing
            "สวัสดีค่ะคุณ {name} 🌟\n\n"
                ."ดาวช่วงนี้โคจรส่งผลพิเศษต่อหลายราศี\n"
                ."อยากรู้ไหมว่า ดาวของคุณจะพาไปทางไหน?\n\n"
                ."🔮 ดูดวงเชิงลึก {$price} บาท\n"
                ."2 คำถาม + ดาวเจ้าชนะ + ไพ่ยิปซี\n\n"
                ."พิมพ์ \"ดูดวง\" ได้เลยนะคะ ✨",

            // v5: no-hedge
            "สวัสดีค่ะคุณ {name} 🪄\n\n"
                ."หมอจันทราไม่กั๊ก — ฟันธงตรงไปตรงมา\n"
                ."ทั้งเรื่องดี และเรื่องต้องระวัง\n\n"
                ."💎 ดูดวงเชิงลึก {$price} บาท\n"
                ."รับคำตอบ 2 ข้อ เจาะตัวคุณคนเดียว\n"
                ."ใช้ดาวเจ้าชนะของคุณ + ไพ่ที่พลังจิตคุณเลือก\n\n"
                ."พิมพ์ \"ดูดวง\" เพื่อเริ่มนะคะ 🙏",
        ];

        if (empty($userId)) {
            return $variants[0];
        }

        $idx = abs(crc32($userId)) % count($variants);

        return $variants[$idx];
    }

    /**
     * AI prompt สำหรับสร้างข้อความชวนดูดวง (มีค่า default)
     */
    public function getCommentEngagementPrompt(): string
    {
        if (! empty($this->comment_engagement_prompt)) {
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
     */
    public function isLineEnabled(): bool
    {
        return (bool) ($this->line_enabled ?? false);
    }

    /**
     * ตรวจสอบว่ามีการตั้งค่า LINE ครบถ้วนหรือไม่
     */
    public function hasLineConfigured(): bool
    {
        return ! empty($this->line_channel_id)
            && ! empty($this->line_channel_secret)
            && ! empty($this->line_channel_access_token);
    }

    /**
     * ดึงรายการ platform ที่เปิดใช้งาน
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
     * ดึงชื่อแบรนด์ดูดวง (ใช้แสดงใน Flex Message)
     */
    public function getFortuneBrandName(): string
    {
        return $this->fortune_brand_name ?? 'แม่หมอจันทรา';
    }

    /**
     * ดึงสีหลักสำหรับ LINE Flex Message
     */
    public function getLineFlexPrimaryColor(): string
    {
        return $this->line_flex_primary_color ?? '#6B46C1';
    }

    /**
     * ดึง URL รูปภาพ Welcome สำหรับ LINE
     */
    public function getLineWelcomeImageUrl(): ?string
    {
        return $this->line_welcome_image_url;
    }

    // ============================
    // Affiliate/MLM Helper Methods
    // ============================

    /**
     * ตรวจสอบว่าเปิดระบบ affiliate สำหรับดูดวงหรือไม่
     */
    public function isFortuneAffiliateEnabled(): bool
    {
        return (bool) ($this->fortune_affiliate_enabled ?? false);
    }

    /**
     * ดึงอัตราคอมมิชชั่นที่ใช้งานจริง (จาก global หรือ custom)
     */
    public function getFortuneEffectiveCommissionRate(): float
    {
        if ($this->fortune_use_global_commission_rate ?? true) {
            return (float) MlmGlobalSetting::get('commission_per_pv', 1);
        }

        return (float) ($this->fortune_custom_commission_per_pv ?? MlmGlobalSetting::get('commission_per_pv', 1));
    }

    /**
     * ดึงโหมดจ่ายคอมมิชชั่น: 'pv' หรือ 'static'
     */
    public function getFortuneCommissionMode(): string
    {
        return $this->fortune_commission_mode ?? 'static';
    }

    /**
     * ดึงจำนวนคอมมิชชั่น static (ใช้เมื่อ mode = static)
     */
    public function getFortuneStaticCommissionAmount(): float
    {
        return (float) ($this->fortune_static_commission_amount ?? 10);
    }

    // ===== Level 1/Level 2 Fortune Commission =====

    /**
     * คำนวณคอมมิชชั่น Level 1 (สายตรง) จากราคาดูดวง
     *
     * @param float $readingPrice ราคาดูดวง
     * @return float จำนวนเงินที่ได้
     */
    public function getFortuneLevel1Amount(float $readingPrice): float
    {
        $type = $this->fortune_level1_commission_type ?? 'fixed';
        $amount = (float) ($this->fortune_level1_commission_amount ?? 10);

        if ($type === 'percent') {
            return round($readingPrice * $amount / 100, 2);
        }

        return round($amount, 2);
    }

    /**
     * คำนวณคอมมิชชั่น Level 2 (ชั้นหลาน) จากราคาดูดวง
     *
     * @param float $readingPrice ราคาดูดวง
     * @return float จำนวนเงินที่ได้
     */
    public function getFortuneLevel2Amount(float $readingPrice): float
    {
        $type = $this->fortune_level2_commission_type ?? 'fixed';
        $amount = (float) ($this->fortune_level2_commission_amount ?? 5);

        if ($type === 'percent') {
            return round($readingPrice * $amount / 100, 2);
        }

        return round($amount, 2);
    }

    /**
     * ตรวจสอบว่าเปิด Level 2 (ชั้นหลาน) หรือไม่
     */
    public function isFortuneLevel2Enabled(): bool
    {
        return (bool) ($this->fortune_level2_enabled ?? true);
    }

    /**
     * ดึงประเภทคอมมิชชั่น Level 1
     */
    public function getFortuneLevel1CommissionType(): string
    {
        return $this->fortune_level1_commission_type ?? 'fixed';
    }

    /**
     * ดึงประเภทคอมมิชชั่น Level 2
     */
    public function getFortuneLevel2CommissionType(): string
    {
        return $this->fortune_level2_commission_type ?? 'fixed';
    }

    // ===== AI Chat ทั่วไป (สนทนาอัจฉริยะ) =====

    /**
     * ดึง AI Provider สำหรับ Chat ทั่วไป
     *
     * แยกจาก getActualAIProvider() ซึ่งใช้สำหรับทำนาย (Grok)
     * Chat ใช้ Gemini เป็นค่าเริ่มต้น — เร็ว ฟรี สนทนาดี
     */
    public function getChatAIProvider(): string
    {
        return $this->chat_ai_provider ?: 'groq';
    }

    /**
     * ดึง AI Model สำหรับ Chat ทั่วไป
     */
    public function getChatAIModel(): string
    {
        return $this->chat_ai_model ?: 'llama-3.3-70b-versatile';
    }

    /**
     * ดึง API Key สำหรับ Chat ทั่วไป
     *
     * ลำดับความสำคัญ:
     * 1. chat_ai_api_key (ตั้งค่าเฉพาะ chat)
     * 2. API Key Pool ตาม chat_ai_provider
     * 3. Global AI Settings (AiContentSetting)
     */
    public function getChatAIApiKey(): ?string
    {
        // 1. ใช้ key เฉพาะ chat ถ้ามี
        if (! empty($this->chat_ai_api_key)) {
            return $this->chat_ai_api_key;
        }

        $provider = $this->getChatAIProvider();

        // 2. ลองดึงจาก API Key Pool
        try {
            $poolKey = AiApiKey::where('provider', $provider)
                ->where('is_active', true)
                ->whereNull('disabled_until')
                ->orderBy('priority', 'desc')
                ->first();
            if ($poolKey) {
                return $poolKey->api_key;
            }
        } catch (\Exception $e) {
            // Pool table อาจไม่มี → ข้ามไป
        }

        // 3. ถ้า chat provider ตรงกับ fortune provider → ใช้ key เดียวกัน
        $fortuneProvider = $this->getActualAIProvider();
        if ($provider === $fortuneProvider && ! empty($this->ai_api_key)) {
            return $this->ai_api_key;
        }

        // 4. ลองดึงจาก Global AI Settings
        $key = match ($provider) {
            'gemini' => AiContentSetting::getValue('gemini_api_key'),
            'openrouter' => AiContentSetting::getValue('claude_api_key')
                ?? AiContentSetting::getValue('openai_api_key'),
            default => null,
        };

        return ! empty($key) ? $key : null;
    }

    /**
     * ดึง System Prompt สำหรับ Chat ทั่วไป (ถ้าว่าง → ใช้ default ใน FortuneAIService)
     */
    public function getChatSystemPrompt(): ?string
    {
        return $this->chat_system_prompt;
    }

    // ============================================================
    // Admin Takeover Helpers (ระบบเทคโอเวอร์)
    // ============================================================

    /**
     * ตรวจสอบว่าเปิดระบบเทคโอเวอร์หรือไม่
     *
     * ใช้ค่า admin_handover_enabled เดิม (ไม่สร้างฟิลด์ซ้ำ)
     */
    public function isTakeoverEnabled(): bool
    {
        return (bool) ($this->admin_handover_enabled ?? false);
    }

    /**
     * ดึงระยะเวลา default ของการเทคโอเวอร์ (นาที)
     */
    public function getTakeoverDefaultMinutes(): int
    {
        return max(1, (int) ($this->admin_handover_timeout ?? 15));
    }

    /**
     * ดึงคำสั่งให้ AI กลับมาทำงาน
     */
    public function getAiResumeCommand(): string
    {
        $cmd = trim((string) ($this->ai_resume_command ?? '/ai'));

        return $cmd !== '' ? $cmd : '/ai';
    }

    /**
     * ดึงรายการคำที่ลูกค้าพิมพ์แล้วให้เทคโอเวอร์อัตโนมัติ
     *
     * @return array<string>
     */
    public function getCustomerHandoffKeywords(): array
    {
        $keywords = $this->customer_handoff_keywords;

        if (! is_array($keywords) || empty($keywords)) {
            // Default keywords — ลูกค้าอยากคุยกับคนจริง
            return [
                'คุยกับคน',
                'คุยกับแอดมิน',
                'คุยกับแม่หมอ',
                'คุยกับเจ้าหน้าที่',
                'ขอคุยกับคน',
                'ขอคุยกับแม่หมอ',
                'ขอแม่หมอ',
                'ต้องการพูดกับคน',
                'อยากคุยกับคน',
                'ติดต่อแอดมิน',
                'ขอแอดมิน',
                'admin',
            ];
        }

        // กรองเฉพาะ string ที่ไม่ว่าง
        return array_values(array_filter(array_map(
            fn ($k) => trim((string) $k),
            $keywords
        ), fn ($k) => $k !== ''));
    }

    /**
     * ตรวจสอบว่าต้องแจ้งลูกค้าเมื่อแม่หมอเข้ามาคุยหรือไม่
     */
    public function shouldNotifyTakeoverToCustomer(): bool
    {
        return (bool) ($this->takeover_notify_customer ?? true);
    }

    /**
     * ดึงข้อความแจ้งลูกค้าเมื่อแม่หมอเข้ามา
     */
    public function getTakeoverCustomerMessage(): string
    {
        $msg = trim((string) ($this->takeover_customer_message ?? ''));

        if ($msg !== '') {
            return $msg;
        }

        $brandName = $this->getFortuneBrandName();

        return "🙏 สวัสดีค่ะ {$brandName} เข้ามาดูแลเอง ขอสักครู่นะคะ 💜";
    }

    /**
     * ดึงข้อความเมื่อ AI กลับมาทำงาน
     */
    public function getTakeoverResumeMessage(): string
    {
        $msg = trim((string) ($this->takeover_resume_message ?? ''));

        if ($msg !== '') {
            return $msg;
        }

        return '✨ ระบบอัจฉริยะกลับมาดูแลต่อแล้ว พิมพ์สอบถามได้เลย';
    }

    /**
     * ดึง unilevel levels เป็น array of objects จาก MlmGlobalSetting
     *
     * ระบบ MLM เก็บแยก 2 key:
     * - unilevel_levels = จำนวนชั้น (integer เช่น 5)
     * - unilevel_percentages = เปอร์เซ็นต์แต่ละชั้น (string เช่น "5,3,2,1,1")
     *
     * Method นี้แปลง unilevel_percentages → [{level: 1, percentage: 5}, {level: 2, percentage: 3}, ...]
     *
     * @return array
     */
    public static function resolveUnilevelLevels(): array
    {
        // ดึง unilevel_percentages จาก MlmGlobalSetting
        // ⚠️ ค่าที่เก็บอาจเป็น:
        //   1. JSON array string: "[5,3,2,1,1]" (จาก frontend JSON.stringify)
        //   2. Comma-separated: "5,3,2,1,1"
        //   3. Array (ถ้า type เป็น 'json' หรือ 'array')
        $percentages = MlmGlobalSetting::get('unilevel_percentages', '');

        // กรณี getTypedValue() คืน array มาเลย (type = json/array)
        if (is_array($percentages) && ! empty($percentages)) {
            return self::buildLevelsFromArray($percentages);
        }

        if (! empty($percentages) && is_string($percentages)) {
            // ลอง JSON decode ก่อน (กรณี "[5,3,2,1,1]")
            $decoded = json_decode($percentages, true);
            if (is_array($decoded) && ! empty($decoded)) {
                return self::buildLevelsFromArray($decoded);
            }

            // Fallback: comma-separated "5,3,2,1,1"
            // ลบ brackets ออกก่อน เผื่อ JSON decode ไม่สำเร็จ
            $cleaned = trim($percentages, '[]');
            $parts = explode(',', $cleaned);
            $levels = [];
            foreach ($parts as $i => $pct) {
                $pctVal = (float) trim($pct);
                if ($pctVal > 0) {
                    $levels[] = ['level' => $i + 1, 'percentage' => $pctVal];
                }
            }
            if (! empty($levels)) {
                return $levels;
            }
        }

        // Fallback: ลองอ่าน unilevel_levels (อาจเป็น JSON array)
        $unilevelLevels = MlmGlobalSetting::get('unilevel_levels', []);

        if (is_string($unilevelLevels)) {
            $decoded = json_decode($unilevelLevels, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (is_array($unilevelLevels)) {
            return $unilevelLevels;
        }

        return [];
    }

    /**
     * แปลง flat array ของ percentages เป็น level config array
     *
     * @param array $percentages เช่น [5, 3, 2, 1, 1]
     * @return array เช่น [{level: 1, percentage: 5}, {level: 2, percentage: 3}, ...]
     */
    protected static function buildLevelsFromArray(array $percentages): array
    {
        $levels = [];
        foreach (array_values($percentages) as $i => $pct) {
            $pctVal = (float) $pct;
            if ($pctVal > 0) {
                $levels[] = ['level' => $i + 1, 'percentage' => $pctVal];
            }
        }

        return $levels;
    }

    /**
     * คำนวณ preview คอมมิชชั่นจากการดูดวง
     *
     * รองรับ 2 โหมด:
     * - 'pv': ใช้ PV คำนวณตาม MLM unilevel levels
     *   สูตร: PV × percentage% × commission_per_pv (เหมือน MlmCommissionService)
     * - 'static': จ่ายตรงตามจำนวนที่ตั้ง (ไม่สน PV, แบ่งตาม level %)
     *
     * แสดง: ราคา, PV, คอมมิชชั่นแต่ละ level, กำไรสุทธิ
     */
    public function calculateFortuneCommissionPreview(): array
    {
        $price = (float) ($this->deep_reading_price ?? 0);
        $mode = $this->getFortuneCommissionMode();

        // ดึง unilevel levels จาก MlmGlobalSetting
        // ⚠️ ระบบเก็บแยก 2 key:
        //   - unilevel_levels = จำนวนชั้น (integer เช่น 5)
        //   - unilevel_percentages = เปอร์เซ็นต์แต่ละชั้น (string เช่น "5,3,2,1,1")
        $unilevelLevels = self::resolveUnilevelLevels();

        // คำนวณตามโหมด
        if ($mode === 'static') {
            return $this->calculateStaticCommissionPreview($price, $unilevelLevels);
        }

        return $this->calculatePvCommissionPreview($price, $unilevelLevels);
    }

    /**
     * คำนวณ preview แบบ PV mode
     *
     * สูตรตรงกับ MlmCommissionService::calculateUnilevelWithRollup() line 224:
     * commissionAmount = (PV × percentage / 100) × commission_per_pv
     */
    protected function calculatePvCommissionPreview(float $price, array $unilevelLevels): array
    {
        // คำนวณ PV จาก global_pv_rate × ราคา (เหมือน OrderDistributionService)
        // ถ้า admin ตั้ง fortune_pv_value ไว้ → ใช้เป็น override
        // ถ้าไม่ได้ตั้ง (0) → คำนวณจาก price × global_pv_rate
        $manualPv = (float) ($this->fortune_pv_value ?? 0);
        $globalPvRate = (float) MlmGlobalSetting::get('global_pv_rate', 1);

        if ($manualPv > 0) {
            $pvValue = $manualPv;
        } else {
            $pvValue = $price * $globalPvRate;
        }

        $commissionPerPv = $this->getFortuneEffectiveCommissionRate();

        $levelBreakdown = [];
        $totalCommission = 0;

        foreach ($unilevelLevels as $levelConfig) {
            if (! is_array($levelConfig)) {
                continue;
            }
            $level = $levelConfig['level'] ?? 0;
            $percentage = (float) ($levelConfig['percentage'] ?? 0);

            // สูตรเดียวกับ MlmCommissionService: (PV × percentage / 100) × commission_per_pv
            $amount = ($pvValue * $percentage / 100) * $commissionPerPv;

            $levelBreakdown[] = [
                'level' => $level,
                'percentage' => $percentage,
                'amount' => round($amount, 4),
            ];

            $totalCommission += $amount;
        }

        $totalCommission = round($totalCommission, 4);
        $netProfit = round($price - $totalCommission, 2);

        return [
            'mode' => 'pv',
            'price' => $price,
            'pv_value' => round($pvValue, 2),
            'global_pv_rate' => $globalPvRate,
            'pv_source' => $manualPv > 0 ? 'manual' : 'auto',
            'commission_per_pv' => $commissionPerPv,
            'levels' => $levelBreakdown,
            'total_commission' => round($totalCommission, 2),
            'net_profit' => $netProfit,
            'profit_percentage' => $price > 0 ? round(($netProfit / $price) * 100, 1) : 0,
        ];
    }

    /**
     * คำนวณ preview แบบ Static mode
     *
     * ระบบดูดวงจ่ายเฉพาะค่าแนะนำ (Direct Referral / Level 1) อย่างเดียว
     * static_amount = จำนวนเงินที่ผู้แนะนำตรงได้รับเต็มจำนวน
     * เช่น ตั้ง 10 บาท → ผู้แนะนำได้ 10 บาท → กำไร = ราคา - 10 = 29 บาท
     */
    protected function calculateStaticCommissionPreview(float $price, array $unilevelLevels): array
    {
        $staticAmount = $this->getFortuneStaticCommissionAmount();

        // จ่ายเฉพาะค่าแนะนำ (Level 1) เต็มจำนวน
        $levelBreakdown = [
            [
                'level' => 1,
                'percentage' => 100,
                'amount' => round($staticAmount, 2),
            ],
        ];

        $totalCommission = round($staticAmount, 2);
        $netProfit = round($price - $totalCommission, 2);

        return [
            'mode' => 'static',
            'price' => $price,
            'static_amount' => $staticAmount,
            'levels' => $levelBreakdown,
            'total_commission' => $totalCommission,
            'net_profit' => $netProfit,
            'profit_percentage' => $price > 0 ? round(($netProfit / $price) * 100, 1) : 0,
        ];
    }
}
