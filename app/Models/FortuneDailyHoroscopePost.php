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

    // 🏬 (2026-08-15) ติดป้ายสาขาให้อัตโนมัติตอนสร้างแถว — สมุดกันโพสซ้ำต้องแยกรายสาขา
    use \App\Models\Concerns\BelongsToFortunePage;

    protected $fillable = [
        'fortune_page_id',
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

    /**
     * 🎯 (2026-05-21) หา post ของวันนี้ตาม day_of_birth (ใช้ใน DM greeting)
     *
     * คืน post ที่ status=posted (มีรูป + caption ครบ) ของวันนี้สำหรับวันเกิดที่ระบุ
     * คืน null ถ้า scheduler ยังไม่ run หรือถูกปิด
     *
     * @param  int  $dayOfBirth  1=จันทร์ ... 7=อาทิตย์
     */
    public static function findTodayForDayOfBirth(int $dayOfBirth): ?self
    {
        if ($dayOfBirth < 1 || $dayOfBirth > 7) {
            return null;
        }

        return self::where('post_date', now()->toDateString())
            ->where('day_of_birth', $dayOfBirth)
            ->whereNotNull('caption')
            ->whereIn('status', [self::STATUS_POSTED, self::STATUS_READY])
            ->latest('updated_at')
            ->first();
    }

    /**
     * 🎯 (2026-05-21) ดึง caption แบบสั้น (~140 chars) สำหรับ DM teaser
     *
     * ตัดด้วย mb_substr + suffix "..." ถ้าเกิน เพื่อให้ลูกค้าอยากเปิด DM อ่านเต็ม
     */
    public function getShortCaption(int $maxChars = 140): string
    {
        $text = (string) ($this->caption ?? '');
        $text = trim(preg_replace('/\s+/', ' ', $text));

        if ($text === '') {
            return '';
        }

        if (mb_strlen($text) <= $maxChars) {
            return $text;
        }

        return mb_substr($text, 0, $maxChars - 1).'…';
    }
}
