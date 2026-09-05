<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HoroscopeDailyPrediction Model — ดวงรายวัน AI-generated
 *
 * เก็บคำทำนาย 5 ด้าน + คะแนน 1-5 + เลข/สี/ทิศมงคล
 * รองรับทั้งระบบ 12 ราศี (zodiac) และ 8 วันเกิด (birth_day — 7 วัน + พุธกลางคืน)
 *
 * 🪞 (2026-09-05) แถว birth_day ส่วนใหญ่ถูก **คัดลอกมาจากบทความที่โพสลงเพจ**
 *    (`fortune_horoscope_contents`) ผ่าน [[DailyArticleMirror]] ไม่ได้ยิง AI เอง —
 *    เพื่อให้คำทำนายในแชทตรงกับบนเพจเป๊ะ และจ่ายค่า AI รอบเดียวต่อวัน
 *    ยิง AI เองเฉพาะตอนที่เลนโพสไม่มีบทความให้ (ล้ม/ปิด)
 *
 * @property int $id
 * @property int|null $zodiac_sign_id FK ราศี
 * @property \Carbon\Carbon $target_date วันที่ทำนาย
 * @property int|null $birth_day วันเกิด (0=อาทิตย์..6=เสาร์ · 7=พุธกลางคืน)
 * @property string $prediction_type zodiac|birth_day
 * @property string|null $overall_prediction_th คำทำนายภาพรวม
 * @property string|null $love_prediction_th คำทำนายความรัก
 * @property string|null $career_prediction_th คำทำนายการงาน
 * @property string|null $finance_prediction_th คำทำนายการเงิน
 * @property string|null $health_prediction_th คำทำนายสุขภาพ
 * @property string|null $time_prediction_th คำทำนายรายช่วงเวลา เช้า/เที่ยง/บ่าย/เย็น/กลางคืน
 * @property int $overall_score คะแนนภาพรวม 1-5
 * @property int $love_score คะแนนความรัก
 * @property int $career_score คะแนนการงาน
 * @property int $finance_score คะแนนการเงิน
 * @property int $health_score คะแนนสุขภาพ
 * @property string|null $lucky_number เลขมงคล
 * @property string|null $lucky_color_th สีมงคล
 * @property string|null $lucky_direction_th ทิศมงคล
 * @property string $status สถานะ
 * @property int $view_count จำนวนการดู
 */
class HoroscopeDailyPrediction extends Model
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'horoscope_daily_predictions';

    /**
     * คอลัมน์ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'zodiac_sign_id',
        'target_date',
        'birth_day',
        'prediction_type',
        'overall_prediction_th',
        'love_prediction_th',
        'career_prediction_th',
        'finance_prediction_th',
        'health_prediction_th',
        'time_prediction_th',
        'overall_score',
        'love_score',
        'career_score',
        'finance_score',
        'health_score',
        'lucky_number',
        'lucky_color_th',
        'lucky_direction_th',
        'ai_provider_used',
        'ai_model_used',
        'view_count',
        'share_count',
        'status',
        'error_message',
        'generated_at',
    ];

    /**
     * การ cast ข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'target_date' => 'date',
        'birth_day' => 'integer',
        'overall_score' => 'integer',
        'love_score' => 'integer',
        'career_score' => 'integer',
        'finance_score' => 'integer',
        'health_score' => 'integer',
        'view_count' => 'integer',
        'share_count' => 'integer',
        'generated_at' => 'datetime',
    ];

    // =============================================
    // ความสัมพันธ์
    // =============================================

    /**
     * ราศีที่ทำนาย
     */
    public function zodiacSign(): BelongsTo
    {
        return $this->belongsTo(HoroscopeZodiacSign::class, 'zodiac_sign_id');
    }

    // =============================================
    // Scopes
    // =============================================

    /**
     * เฉพาะที่สร้างสำเร็จ
     */
    public function scopeGenerated($query)
    {
        return $query->where('status', 'generated');
    }

    /**
     * เฉพาะวันนี้
     */
    public function scopeToday($query)
    {
        return $query->where('target_date', today());
    }

    /**
     * เฉพาะประเภทราศี
     */
    public function scopeZodiacType($query)
    {
        return $query->where('prediction_type', 'zodiac');
    }

    /**
     * เฉพาะประเภทวันเกิด
     */
    public function scopeBirthDayType($query)
    {
        return $query->where('prediction_type', 'birth_day');
    }

    // =============================================
    // Accessors / Helpers
    // =============================================

    /**
     * คะแนนเฉลี่ยรวม
     */
    public function getAverageScoreAttribute(): float
    {
        return round((
            ($this->overall_score ?? 0) +
            ($this->love_score ?? 0) +
            ($this->career_score ?? 0) +
            ($this->finance_score ?? 0) +
            ($this->health_score ?? 0)
        ) / 5, 1);
    }

    /**
     * ชื่อวันเกิดภาษาไทย
     */
    public function getBirthDayNameThAttribute(): ?string
    {
        // 🌙 index 7 = พุธกลางคืน (ราหู) — วันเกิดที่ 8 ตามตำราไทย
        $days = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์', 'พุธกลางคืน'];

        return $this->birth_day !== null ? ($days[$this->birth_day] ?? null) : null;
    }

    /**
     * เพิ่มจำนวนการดู
     */
    public function incrementView(): void
    {
        $this->increment('view_count');
    }
}
