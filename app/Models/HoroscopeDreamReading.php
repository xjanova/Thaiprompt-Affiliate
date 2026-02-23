<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HoroscopeDreamReading Model — บันทึกการทำนายฝัน AI
 *
 * เก็บข้อมูลการทำนายฝันแต่ละครั้ง:
 * คำอธิบายฝัน + คำสำคัญที่ match + ผลทำนาย AI + เลขเด็ด
 *
 * @property int $id
 * @property int|null $user_id FK ผู้ใช้
 * @property string $session_id Session ID
 * @property string $dream_description คำอธิบายฝัน
 * @property array|null $matched_keywords คำสำคัญที่ match
 * @property string|null $ai_interpretation ผลทำนายจาก AI
 * @property array|null $lucky_numbers เลขเด็ด
 * @property bool $is_free ฟรี หรือ ใช้เครดิต
 */
class HoroscopeDreamReading extends Model
{
    protected $table = 'horoscope_dream_readings';

    protected $fillable = [
        'user_id',
        'session_id',
        'dream_description',
        'matched_keywords',
        'ai_interpretation',
        'lucky_numbers',
        'ai_provider_used',
        'ai_model_used',
        'is_free',
        'view_count',
        'share_count',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'matched_keywords' => 'array',
        'lucky_numbers' => 'array',
        'is_free' => 'boolean',
        'view_count' => 'integer',
        'share_count' => 'integer',
    ];

    /**
     * ผู้ใช้ (nullable สำหรับ guest)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * เพิ่มจำนวนการดู
     */
    public function incrementView(): void
    {
        $this->increment('view_count');
    }

    /**
     * แสดงเลขเด็ดเป็น string
     */
    public function getLuckyNumbersDisplayAttribute(): string
    {
        if (empty($this->lucky_numbers)) {
            return '-';
        }

        return implode(', ', $this->lucky_numbers);
    }

    /**
     * นับการทำนายฝันวันนี้ตาม session/user
     */
    public static function countTodayReadings(string $sessionId, ?int $userId = null): int
    {
        $query = static::whereDate('created_at', today());

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId);
        }

        return $query->count();
    }
}
