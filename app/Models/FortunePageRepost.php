<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 🔁 FortunePageRepost — สมุดบันทึกการรีโพสจากเพจต้นทางลงเพจสาขา (1 แถว = 1 โพส)
 *
 * @property int $id
 * @property int $fortune_page_id สาขาปลายทาง
 * @property int|null $source_fortune_page_id สาขาต้นทาง
 * @property string $source_post_id โพสต้นฉบับ
 * @property string|null $source_object_id photo/video id ของต้นฉบับ
 * @property \Carbon\Carbon|null $source_created_at
 * @property string $media_type photo / video / text
 * @property \Carbon\Carbon $slot_at เวลาที่ตั้งให้ขึ้นบนเพจสาขา
 * @property string|null $slot_source_post_id โพสต้นทางที่ใช้เป็นช่องเวลา
 * @property string|null $target_post_id
 * @property string|null $target_object_id
 * @property string $status scheduled / published / failed / cancelled
 * @property string|null $error
 * @property string|null $caption_preview
 * @property \Carbon\Carbon|null $checked_at
 */
class FortunePageRepost extends Model
{
    /** จองแถวไว้แล้ว กำลังยิง Graph (ถ้าค้างเกิน 30 นาที = โปรเซสตายกลางทาง) */
    public const STATUS_SENDING = 'sending';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    /** สถานะที่ถือว่า "ช่องเวลานี้มีโพสแล้ว" */
    public const OCCUPYING = [self::STATUS_SENDING, self::STATUS_SCHEDULED, self::STATUS_PUBLISHED];

    protected $table = 'fortune_page_reposts';

    protected $fillable = [
        'fortune_page_id',
        'source_fortune_page_id',
        'source_post_id',
        'source_object_id',
        'source_created_at',
        'media_type',
        'slot_at',
        'slot_source_post_id',
        'target_post_id',
        'target_object_id',
        'status',
        'error',
        'caption_preview',
        'checked_at',
    ];

    protected $casts = [
        'source_created_at' => 'datetime',
        'slot_at' => 'datetime',
        'checked_at' => 'datetime',
    ];

    /**
     * สาขาปลายทาง
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(FortunePage::class, 'fortune_page_id');
    }

    /**
     * สาขาต้นทาง
     */
    public function sourcePage(): BelongsTo
    {
        return $this->belongsTo(FortunePage::class, 'source_fortune_page_id');
    }

    /**
     * ป้ายสถานะภาษาไทย (ไว้โชว์หลังบ้าน)
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_SENDING => 'กำลังส่ง',
            self::STATUS_SCHEDULED => 'ตั้งเวลาแล้ว',
            self::STATUS_PUBLISHED => 'ขึ้นหน้าเพจแล้ว',
            self::STATUS_FAILED => 'ส่งไม่ผ่าน',
            self::STATUS_CANCELLED => 'ยกเลิกแล้ว',
            default => $this->status,
        };
    }

    /**
     * ป้ายประเภทสื่อภาษาไทย
     */
    public function getMediaLabelAttribute(): string
    {
        return match ($this->media_type) {
            'photo' => 'รูปภาพ',
            'video' => 'วิดีโอ',
            'text' => 'ข้อความ',
            default => $this->media_type,
        };
    }

    /**
     * ยกเลิกได้ไหม — เฉพาะที่ตั้งเวลาไว้และยังไม่ถึงเวลา
     */
    public function isCancellable(): bool
    {
        return $this->status === self::STATUS_SCHEDULED
            && $this->slot_at !== null
            && $this->slot_at->isFuture();
    }

    /**
     * ลิงก์เปิดโพสต้นฉบับบน Facebook
     */
    public function getSourceUrlAttribute(): string
    {
        return 'https://www.facebook.com/'.$this->source_post_id;
    }
}
