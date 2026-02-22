<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * FortuneHoroscopeContent Model
 *
 * เนื้อหาดวงรายวันที่ AI สร้าง
 * แต่ละ record = 1 วันเกิด (อา-ส) ของ 1 วันที่
 *
 * @property int $id
 * @property int $campaign_id
 * @property string $target_date
 * @property int $birth_day 0=อาทิตย์ ถึง 6=เสาร์
 * @property string $birth_day_name ชื่อวันภาษาไทย
 * @property string|null $main_planet ดาวเจ้าชนะ
 * @property array|null $planet_positions ตำแหน่งดาวในภพ
 * @property array|null $chaochana_data ข้อมูลเจ้าชนะ
 * @property string|null $ai_prediction คำทำนายจาก AI
 * @property string|null $ai_prompt_used
 * @property string|null $ai_text_provider_used
 * @property string|null $ai_text_model_used
 * @property string|null $image_url URL ภาพ AI
 * @property string|null $image_path
 * @property string|null $image_prompt_used
 * @property string|null $ai_image_provider_used
 * @property string|null $lucky_color สีมงคล
 * @property string|null $lucky_number เลขมงคล
 * @property string|null $lucky_direction ทิศมงคล
 * @property string $status pending/generating/generated/failed
 * @property string|null $error_message
 */
class FortuneHoroscopeContent extends Model
{
    protected $table = 'fortune_horoscope_contents';

    public const STATUS_PENDING = 'pending';

    public const STATUS_GENERATING = 'generating';

    public const STATUS_GENERATED = 'generated';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'campaign_id',
        'target_date',
        'birth_day',
        'birth_day_name',
        'main_planet',
        'planet_positions',
        'chaochana_data',
        'ai_prediction',
        'ai_prompt_used',
        'ai_text_provider_used',
        'ai_text_model_used',
        'image_url',
        'image_path',
        'image_prompt_used',
        'ai_image_provider_used',
        'lucky_color',
        'lucky_number',
        'lucky_direction',
        'status',
        'error_message',
    ];

    protected $casts = [
        'target_date' => 'date',
        'birth_day' => 'integer',
        'planet_positions' => 'array',
        'chaochana_data' => 'array',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    // ============================================================
    // Relationships
    // ============================================================

    /**
     * แคมเปญที่สังกัด
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(FortuneHoroscopeCampaign::class, 'campaign_id');
    }

    /**
     * โพสที่ใช้เนื้อหานี้
     */
    public function posts(): HasMany
    {
        return $this->hasMany(FortuneHoroscopePost::class, 'content_id');
    }

    // ============================================================
    // Scopes
    // ============================================================

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('target_date', $date);
    }

    public function scopeGenerated($query)
    {
        return $query->where('status', self::STATUS_GENERATED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    // ============================================================
    // Methods
    // ============================================================

    /**
     * เริ่มสร้างเนื้อหา
     */
    public function markGenerating(): self
    {
        $this->update(['status' => self::STATUS_GENERATING]);

        return $this;
    }

    /**
     * สร้างเนื้อหาสำเร็จ
     */
    public function markGenerated(): self
    {
        $this->update(['status' => self::STATUS_GENERATED, 'error_message' => null]);

        return $this;
    }

    /**
     * สร้างเนื้อหาล้มเหลว
     */
    public function markFailed(string $error): self
    {
        $this->update(['status' => self::STATUS_FAILED, 'error_message' => $error]);

        return $this;
    }
}
