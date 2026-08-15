<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * โพสของระบบแคมเปญคอนเทนต์อัตโนมัติ (audit trail)
 *
 * 1 row = 1 slot ของ 1 แคมเปญ ใน 1 วัน — unique (campaign_id, post_date, slot_time)
 */
class FortuneContentPost extends Model
{
    protected $table = 'fortune_content_posts';

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
        'campaign_id',
        'post_date',
        'slot_time',
        'sub_topic',
        'mystic_topic_id',
        'sources',
        'search_provider',
        'caption',
        'hashtags',
        'image_prompt',
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
        'sources' => 'array',
        'hashtags' => 'array',
        'researched_at' => 'datetime',
        'generated_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์: แคมเปญเจ้าของโพส
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(FortuneContentCampaign::class, 'campaign_id');
    }

    /**
     * ความสัมพันธ์: หมวดสายมูเดิม (เฉพาะแคมเปญ bridge)
     */
    public function mysticTopic(): BelongsTo
    {
        return $this->belongsTo(FortuneMysticTopic::class, 'mystic_topic_id');
    }

    /**
     * บันทึกว่าโพสสำเร็จ — ล้าง error เก่าด้วย (row ที่เคย failed แล้ว retry สำเร็จ
     * ต้องไม่โชว์ error ค้างใน admin modal)
     */
    public function markPosted(?string $fbPostId, ?string $fbPostUrl = null): void
    {
        $this->update([
            'status' => self::STATUS_POSTED,
            'fb_post_id' => $fbPostId,
            'fb_post_url' => $fbPostUrl,
            'error_message' => null,
            'posted_at' => now(),
        ]);
    }

    /**
     * บันทึกว่าล้มเหลว (ตัด error ที่ 1000 ตัวอักษร)
     */
    public function markFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => mb_substr($error, 0, 1000),
        ]);
    }
}
