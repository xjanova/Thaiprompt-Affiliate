<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * HoroscopeZodiacSign Model — 12 ราศีไทย/สากล
 *
 * @property int $id
 * @property string $name_th ชื่อราศีภาษาไทย
 * @property string $name_en ชื่อราศีภาษาอังกฤษ
 * @property string $slug URL slug
 * @property string $date_range_start วันเริ่มต้น (MM-DD)
 * @property string $date_range_end วันสิ้นสุด (MM-DD)
 * @property string|null $date_range_display_th ช่วงวันแสดงผลภาษาไทย
 * @property string $element ธาตุ (ไฟ/ดิน/ลม/น้ำ)
 * @property string $ruling_planet ดาวประจำราศี
 * @property string|null $quality คุณภาพ (Cardinal/Fixed/Mutable)
 * @property string $symbol_emoji สัญลักษณ์ emoji
 * @property string|null $symbol_image_url URL รูปสัญลักษณ์
 * @property string $color_hex สี hex
 * @property string|null $gradient_from สี gradient เริ่มต้น
 * @property string|null $gradient_to สี gradient สิ้นสุด
 * @property string|null $description_th คำอธิบายภาษาไทย
 * @property string|null $personality_th บุคลิกภาพ
 * @property string|null $strengths_th จุดแข็ง
 * @property string|null $weaknesses_th จุดอ่อน
 * @property bool $is_active เปิด/ปิดใช้งาน
 * @property int $sort_order ลำดับ
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class HoroscopeZodiacSign extends Model
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'horoscope_zodiac_signs';

    /**
     * คอลัมน์ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'name_th',
        'name_en',
        'slug',
        'date_range_start',
        'date_range_end',
        'date_range_display_th',
        'element',
        'ruling_planet',
        'quality',
        'symbol_emoji',
        'symbol_image_url',
        'color_hex',
        'gradient_from',
        'gradient_to',
        'description_th',
        'personality_th',
        'strengths_th',
        'weaknesses_th',
        'is_active',
        'sort_order',
    ];

    /**
     * การ cast ข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // =============================================
    // ความสัมพันธ์ (Relationships)
    // =============================================

    /**
     * ดวงรายวันทั้งหมดของราศีนี้
     *
     * @return HasMany
     */
    public function dailyPredictions(): HasMany
    {
        return $this->hasMany(HoroscopeDailyPrediction::class, 'zodiac_sign_id');
    }

    // =============================================
    // Scopes
    // =============================================

    /**
     * เฉพาะราศีที่เปิดใช้งาน
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * เรียงตาม sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // =============================================
    // Accessors / Helpers
    // =============================================

    /**
     * ดึงดวงวันนี้
     *
     * @return HoroscopeDailyPrediction|null
     */
    public function getTodayPrediction(): ?HoroscopeDailyPrediction
    {
        return $this->dailyPredictions()
            ->where('target_date', today())
            ->where('prediction_type', 'zodiac')
            ->where('status', 'generated')
            ->first();
    }

    /**
     * สร้าง Tailwind gradient class จากสี
     *
     * @return string
     */
    public function getGradientClassAttribute(): string
    {
        if ($this->gradient_from && $this->gradient_to) {
            return "from-[{$this->gradient_from}] to-[{$this->gradient_to}]";
        }

        return 'from-indigo-500 to-purple-600';
    }
}
