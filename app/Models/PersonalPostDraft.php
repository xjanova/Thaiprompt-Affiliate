<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * โพสต์เตรียมล่วงหน้าสำหรับเฟซบุ๊กส่วนตัว (เจ้าของกดโพสต์เอง)
 *
 * @property int $id
 * @property \Carbon\Carbon $post_date วันที่ของชุดโพสต์
 * @property int $slot ช่องที่ 1-4 ของวัน
 * @property string $source_type google_trend | tech_news
 * @property string|null $topic หัวข้อสั้น
 * @property string|null $source_title หัวข่าวต้นทาง
 * @property string|null $source_url ลิงก์ข่าวต้นทาง
 * @property string|null $source_name ชื่อสำนักข่าว
 * @property array|null $news_context หัวข่าวที่ใช้เป็นข้อเท็จจริง
 * @property string|null $caption บทความพร้อมโพสต์
 * @property string|null $image_prompt prompt ภาพ (อังกฤษ)
 * @property string|null $image_path path ภาพบน public disk
 * @property string|null $image_provider ผู้ให้บริการภาพที่ใช้จริง
 * @property string|null $text_provider ผู้ให้บริการ AI ที่เขียนบทความ
 * @property string $status draft | generating | ready | failed | posted | skipped
 * @property string|null $error ข้อความผิดพลาดล่าสุด
 * @property \Carbon\Carbon|null $posted_at เวลาที่เจ้าของกดว่าโพสต์แล้ว
 */
class PersonalPostDraft extends Model
{
    /** จำนวนโพสต์ต่อวัน */
    public const SLOTS_PER_DAY = 4;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_GENERATING = 'generating';

    public const STATUS_READY = 'ready';

    public const STATUS_FAILED = 'failed';

    public const STATUS_POSTED = 'posted';

    public const STATUS_SKIPPED = 'skipped';

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'รอสร้าง',
        self::STATUS_GENERATING => 'กำลังสร้าง',
        self::STATUS_READY => 'พร้อมโพสต์',
        self::STATUS_FAILED => 'สร้างไม่สำเร็จ',
        self::STATUS_POSTED => 'โพสต์แล้ว',
        self::STATUS_SKIPPED => 'ข้าม',
    ];

    public const SOURCE_LABELS = [
        'google_trend' => 'เทรนด์ Google',
        'tech_news' => 'ข่าวเทค',
    ];

    protected $table = 'personal_post_drafts';

    protected $fillable = [
        'post_date',
        'slot',
        'source_type',
        'topic',
        'source_title',
        'source_url',
        'source_name',
        'news_context',
        'caption',
        'image_prompt',
        'image_path',
        'image_provider',
        'text_provider',
        'status',
        'error',
        'posted_at',
    ];

    protected $casts = [
        'post_date' => 'date',
        'slot' => 'integer',
        'news_context' => 'array',
        'posted_at' => 'datetime',
    ];

    /**
     * URL สาธารณะของภาพ (null = ยังไม่มีภาพ)
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }

    /**
     * ชื่อสถานะภาษาไทย
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * ชื่อที่มาภาษาไทย
     */
    public function getSourceLabelAttribute(): string
    {
        return self::SOURCE_LABELS[$this->source_type] ?? $this->source_type;
    }

    /**
     * พร้อมให้เจ้าของกดโพสต์ไหม (มีบทความแล้ว)
     */
    public function isPostable(): bool
    {
        return in_array($this->status, [self::STATUS_READY, self::STATUS_POSTED], true)
            && trim((string) $this->caption) !== '';
    }
}
