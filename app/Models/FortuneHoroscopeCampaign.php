<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FortuneHoroscopeCampaign Model
 *
 * แคมเปญโพสดวงรายวันอัตโนมัติ
 * ตั้งค่า AI สร้างคำทำนาย 7 วันเกิด + ภาพ → โพสลง FB/LINE ตามเวลา
 *
 * @property int $id
 * @property string $name ชื่อแคมเปญ
 * @property string|null $description คำอธิบาย
 * @property bool $post_to_facebook โพสลง Facebook
 * @property bool $post_to_line โพสลง LINE
 * @property bool $use_fortune_settings_tokens ใช้ token จาก FortuneTellingSetting
 * @property string|null $facebook_page_id
 * @property string|null $facebook_page_token (encrypted)
 * @property string|null $line_channel_access_token (encrypted)
 * @property string $ai_text_provider
 * @property string|null $text_prompt_template
 * @property string $ai_image_provider
 * @property string $ai_image_model
 * @property string $image_size
 * @property string|null $image_style
 * @property string|null $image_prompt_template
 * @property string $schedule_time เวลาโพส HH:mm
 * @property string $timezone
 * @property array|null $active_days วันที่โพส [0-6]
 * @property array|null $target_birth_days วันเกิดที่สร้าง [0-6]
 * @property bool $include_image สร้างรูปด้วย
 * @property bool $include_lucky_info แทรกสีมงคล/เลขมงคล
 * @property string $post_format single/combined
 * @property string|null $post_header_template
 * @property string|null $post_footer_template
 * @property string $status draft/active/paused/cancelled
 * @property Carbon|null $last_generated_at
 * @property Carbon|null $last_posted_at
 * @property string|null $last_error
 * @property int|null $created_by
 */
class FortuneHoroscopeCampaign extends Model
{
    use SoftDeletes;

    protected $table = 'fortune_horoscope_campaigns';

    // สถานะ
    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_CANCELLED = 'cancelled';

    // ชื่อวันเกิดภาษาไทย
    public const THAI_DAYS = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];

    protected $fillable = [
        'name',
        'description',
        'post_to_facebook',
        'post_to_line',
        'use_fortune_settings_tokens',
        'facebook_page_id',
        'facebook_page_token',
        'line_channel_access_token',
        'ai_text_provider',
        'text_prompt_template',
        'ai_image_provider',
        'ai_image_model',
        'image_size',
        'image_style',
        'image_prompt_template',
        'schedule_time',
        'timezone',
        'active_days',
        'target_birth_days',
        'include_image',
        'include_lucky_info',
        'post_format',
        'post_header_template',
        'post_footer_template',
        'status',
        'last_generated_at',
        'last_posted_at',
        'last_error',
        'created_by',
    ];

    protected $casts = [
        'post_to_facebook' => 'boolean',
        'post_to_line' => 'boolean',
        'use_fortune_settings_tokens' => 'boolean',
        'facebook_page_token' => 'encrypted',
        'line_channel_access_token' => 'encrypted',
        'active_days' => 'array',
        'target_birth_days' => 'array',
        'include_image' => 'boolean',
        'include_lucky_info' => 'boolean',
        'last_generated_at' => 'datetime',
        'last_posted_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
        'ai_text_provider' => 'auto',
        'ai_image_provider' => 'pollinations',
        'ai_image_model' => 'flux',
        'image_size' => '1024x1024',
        'schedule_time' => '06:00',
        'timezone' => 'Asia/Bangkok',
        'post_to_facebook' => false,
        'post_to_line' => false,
        'use_fortune_settings_tokens' => true,
        'include_image' => true,
        'include_lucky_info' => true,
        'post_format' => 'combined',
    ];

    // ============================================================
    // Relationships
    // ============================================================

    /**
     * เนื้อหาที่ AI สร้าง
     */
    public function contents(): HasMany
    {
        return $this->hasMany(FortuneHoroscopeContent::class, 'campaign_id');
    }

    /**
     * ประวัติการโพส
     */
    public function posts(): HasMany
    {
        return $this->hasMany(FortuneHoroscopePost::class, 'campaign_id');
    }

    /**
     * แอดมินที่สร้าง
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ============================================================
    // Scopes
    // ============================================================

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * แคมเปญที่พร้อมสร้างเนื้อหา (active + ถึงเวลา + วันนี้เป็นวันที่กำหนด)
     */
    public function scopeReadyToGenerate($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) {
                // ยังไม่เคยสร้างวันนี้
                $q->whereNull('last_generated_at')
                    ->orWhereDate('last_generated_at', '<', now($this->timezone ?? 'Asia/Bangkok')->toDateString());
            });
    }

    // ============================================================
    // Methods
    // ============================================================

    /**
     * วันนี้เป็นวันที่กำหนดให้โพสหรือไม่
     */
    public function isActiveDayToday(): bool
    {
        $activeDays = $this->active_days;

        // null = ทุกวัน
        if ($activeDays === null || empty($activeDays)) {
            return true;
        }

        $todayDow = now($this->timezone)->dayOfWeek;

        return in_array($todayDow, $activeDays);
    }

    /**
     * ดึง Carbon instance ของเวลาโพสวันนี้
     */
    public function getScheduleTimeCarbon(): Carbon
    {
        $tz = $this->timezone ?? 'Asia/Bangkok';
        [$hour, $minute] = explode(':', $this->schedule_time ?? '06:00');

        return now($tz)->setTime((int) $hour, (int) $minute, 0);
    }

    /**
     * ดึงวันเกิดที่ต้องสร้างเนื้อหา (0-6)
     */
    public function getTargetBirthDays(): array
    {
        $days = $this->target_birth_days;

        // null = ทุกวันเกิด (0-6)
        if ($days === null || empty($days)) {
            return [0, 1, 2, 3, 4, 5, 6];
        }

        return $days;
    }

    /**
     * ดึง platforms ที่เปิดใช้
     */
    public function getPlatforms(): array
    {
        $platforms = [];
        if ($this->post_to_facebook) {
            $platforms[] = 'facebook';
        }
        if ($this->post_to_line) {
            $platforms[] = 'line';
        }

        return $platforms;
    }

    /**
     * ดึง Facebook Page Token (จาก campaign หรือ FortuneTellingSetting)
     */
    public function getFacebookPageToken(): ?string
    {
        if ($this->use_fortune_settings_tokens) {
            $settings = FortuneTellingSetting::getSettings();

            return $settings->facebook_page_access_token ?? null;
        }

        return $this->facebook_page_token;
    }

    /**
     * ดึง Facebook Page ID (จาก campaign หรือ FortuneTellingSetting)
     */
    public function getFacebookPageId(): ?string
    {
        if ($this->use_fortune_settings_tokens) {
            $settings = FortuneTellingSetting::getSettings();

            return $settings->facebook_page_id ?? null;
        }

        return $this->facebook_page_id;
    }

    /**
     * ดึง LINE Channel Access Token (จาก campaign หรือ FortuneTellingSetting)
     */
    public function getLineChannelAccessToken(): ?string
    {
        if ($this->use_fortune_settings_tokens) {
            $settings = FortuneTellingSetting::getSettings();

            return $settings->line_channel_access_token ?? null;
        }

        return $this->line_channel_access_token;
    }

    /**
     * เปิดใช้งาน
     */
    public function activate(): self
    {
        $this->update(['status' => self::STATUS_ACTIVE]);

        return $this->fresh();
    }

    /**
     * หยุดชั่วคราว
     */
    public function pause(): self
    {
        $this->update(['status' => self::STATUS_PAUSED]);

        return $this;
    }

    /**
     * ยกเลิก
     */
    public function cancel(): self
    {
        $this->update(['status' => self::STATUS_CANCELLED]);

        return $this;
    }

    /**
     * บันทึก error
     */
    public function markError(string $error): self
    {
        $this->update(['last_error' => $error]);

        return $this;
    }

    /**
     * Label สถานะภาษาไทย
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'แบบร่าง',
            self::STATUS_ACTIVE => 'กำลังทำงาน',
            self::STATUS_PAUSED => 'หยุดชั่วคราว',
            self::STATUS_CANCELLED => 'ยกเลิก',
            default => $this->status,
        };
    }

    /**
     * Badge color สำหรับ UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_ACTIVE => 'green',
            self::STATUS_PAUSED => 'yellow',
            self::STATUS_CANCELLED => 'red',
            default => 'gray',
        };
    }
}
