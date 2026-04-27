<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * โพสดวงประจำวันรายเจ้าของวันเกิด (auto-post)
 */
class FortuneDailyHoroscopePost extends Model
{
    protected $table = 'fortune_daily_horoscope_posts';

    public const STATUS_PENDING = 'pending';
    public const STATUS_GENERATING = 'generating';
    public const STATUS_READY = 'ready';
    public const STATUS_PUBLISHING = 'publishing';
    public const STATUS_POSTED = 'posted';
    public const STATUS_FAILED = 'failed';

    /**
     * Map: day_of_birth (1-7) → ชื่อวัน
     */
    public const DAY_NAMES = [
        1 => 'จันทร์',
        2 => 'อังคาร',
        3 => 'พุธ',
        4 => 'พฤหัสบดี',
        5 => 'ศุกร์',
        6 => 'เสาร์',
        7 => 'อาทิตย์',
    ];

    /**
     * Map: day_of_birth → "กำลังวัน" (เลขในโหราศาสตร์ไทย)
     * อาทิตย์=1, จันทร์=2, อังคาร=3, พุธ=4, พฤหัส=5, ศุกร์=6, เสาร์=7
     */
    public const DAY_POWER = [
        1 => 15, // จันทร์
        2 => 8,  // อังคาร
        3 => 17, // พุธ
        4 => 19, // พฤหัสบดี
        5 => 21, // ศุกร์
        6 => 10, // เสาร์
        7 => 6,  // อาทิตย์
    ];

    /**
     * Map: day_of_birth → emoji
     */
    public const DAY_EMOJI = [
        1 => '🌕', // จันทร์
        2 => '🔴', // อังคาร
        3 => '💚', // พุธ
        4 => '🟠', // พฤหัสบดี
        5 => '💙', // ศุกร์
        6 => '🟣', // เสาร์
        7 => '☀️', // อาทิตย์
    ];

    protected $fillable = [
        'post_date',
        'day_of_birth',
        'tarot_card_id',
        'is_reversed',
        'caption',
        'image_path',
        'image_url',
        'status',
        'fb_post_id',
        'fb_post_url',
        'error_message',
        'generated_at',
        'posted_at',
    ];

    protected $casts = [
        'post_date' => 'date',
        'day_of_birth' => 'integer',
        'is_reversed' => 'boolean',
        'generated_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์: ไพ่ที่สุ่มได้
     */
    public function tarotCard(): BelongsTo
    {
        return $this->belongsTo(TarotCard::class, 'tarot_card_id');
    }

    /**
     * ชื่อวันเกิด (ภาษาไทย)
     */
    public function getDayNameAttribute(): string
    {
        return self::DAY_NAMES[$this->day_of_birth] ?? '?';
    }

    /**
     * Emoji ของวันเกิด
     */
    public function getDayEmojiAttribute(): string
    {
        return self::DAY_EMOJI[$this->day_of_birth] ?? '✨';
    }

    /**
     * Mark as posted
     */
    public function markPosted(?string $fbPostId, ?string $fbPostUrl = null): void
    {
        $this->update([
            'status' => self::STATUS_POSTED,
            'fb_post_id' => $fbPostId,
            'fb_post_url' => $fbPostUrl,
            'posted_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => mb_substr($error, 0, 1000),
        ]);
    }
}
