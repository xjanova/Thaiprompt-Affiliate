<?php

namespace App\Models;

use App\Models\AiContentSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FortuneTellingSetting Model
 *
 * จัดการการตั้งค่าระบบดูดวงผ่าน Facebook Messenger
 * รองรับ AI providers: Gemini, Groq, Qwen, OpenRouter
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
        'ai_model' => 'gemini-2.0-flash-exp',
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
                'ai_model' => 'gemini-2.0-flash-exp',
                'max_free_readings' => 3,
                'reading_price' => 0,
                'is_enabled' => true,
            ]);
        }

        return $settings;
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
                'gemini' => AiContentSetting::getValue('gemini_model', 'gemini-1.5-flash'),
                'openrouter' => AiContentSetting::getValue('claude_model', 'anthropic/claude-3-sonnet'),
                default => 'gemini-1.5-flash',
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

            return match ($provider) {
                'gemini' => AiContentSetting::getValue('gemini_api_key'),
                'openrouter' => AiContentSetting::getValue('claude_api_key')
                    ?? AiContentSetting::getValue('openai_api_key'),
                default => null,
            };
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
คุณเป็นหมอดูมืออาชีพที่มีความสามารถในการวิเคราะห์ดวงชะตาและทำนายอนาคต

ข้อมูลผู้ถาม:
{user_profile}

บริบทจากโพสล่าสุด:
{user_posts}

คำถามที่ต้องการทำนาย:
{questions}

กรุณาทำนายอย่างละเอียด แต่กระชับ โดยใช้ภาษาไทยที่เข้าใจง่าย
แต่ละคำถามควรได้รับคำตอบอย่างน้อย 2-3 ประโยค
ใช้น้ำเสียงที่อบอุ่น เป็นกันเอง และให้กำลังใจ
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
คุณเป็นหมอดูที่ให้คำทำนายสั้นๆ กระชับ

ข้อมูลผู้ถาม:
{user_profile}

คำถาม:
{questions}

ให้คำทำนายสั้นๆ 2-3 ประโยคต่อคำถาม ใช้ภาษาไทยเข้าใจง่าย
ท้ายข้อความให้แนะนำว่า "พิมพ์ 'ดูดวงละเอียด' เพื่อรับคำทำนายเชิงลึก"
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
คุณเป็นหมอดูมืออาชีพระดับสูงที่มีความเชี่ยวชาญด้านโหราศาสตร์ ไพ่ทาโรต์ และศาสตร์การทำนาย

ข้อมูลผู้ถาม:
{user_profile}

บริบทจากโพสล่าสุด:
{user_posts}

คำถามที่ต้องการทำนาย:
{questions}

กรุณาทำนายอย่างละเอียดลึกซึ้ง โดย:
1. วิเคราะห์ดวงชะตาผ่านดาวเคราะห์ที่ส่งผล
2. ทำนายช่วงเวลาที่เหมาะสมและไม่เหมาะสม
3. ให้คำแนะนำเชิงลึกสำหรับแต่ละคำถาม (อย่างน้อย 5-7 ประโยค)
4. แนะนำสีมงคล เลขมงคล วันมงคลประจำสัปดาห์
5. ให้กำลังใจและทางออกสำหรับทุกสถานการณ์
ใช้ภาษาไทยที่สละสลวย อบอุ่น เป็นกันเอง
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
}
