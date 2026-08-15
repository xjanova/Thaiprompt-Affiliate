<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * โพสคอนเทนต์สายมูที่สร้าง+โพส (audit trail)
 *
 * 1 row = 1 slot ของ 1 วัน (post_date + slot_hour unique)
 */
class FortuneMysticPost extends Model
{
    protected $table = 'fortune_mystic_posts';

    public const STATUS_PENDING = 'pending';
    public const STATUS_RESEARCHING = 'researching';
    public const STATUS_GENERATING = 'generating';
    public const STATUS_READY = 'ready';
    public const STATUS_PUBLISHING = 'publishing';
    public const STATUS_POSTED = 'posted';
    public const STATUS_FAILED = 'failed';

    // 🏬 (2026-08-15) ติดป้ายสาขาให้อัตโนมัติตอนสร้างแถว — สมุดกันโพสซ้ำต้องแยกรายสาขา
    use \App\Models\Concerns\BelongsToFortunePage;

    protected $fillable = [
        'fortune_page_id',
        'post_date',
        'slot_hour',
        'topic_id',
        'sub_topic',
        'sources',
        'search_provider',
        'caption',
        'hashtags',
        'image_path',
        'image_url',
        'image_provider',
        'status',
        'fb_post_id',
        'fb_post_url',
        'error_message',
        'researched_at',
        'generated_at',
        'posted_at',
    ];

    protected $casts = [
        'post_date' => 'date',
        'slot_hour' => 'integer',
        'sources' => 'array',
        'hashtags' => 'array',
        'researched_at' => 'datetime',
        'generated_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์: หมวดที่สุ่มได้
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(FortuneMysticTopic::class, 'topic_id');
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
     * Mark as failed (เก็บ error message ตัดที่ 1000 ตัวอักษร)
     */
    public function markFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => mb_substr($error, 0, 1000),
        ]);
    }
}
