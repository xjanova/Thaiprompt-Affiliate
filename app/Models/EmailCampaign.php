<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * EmailCampaign Model
 *
 * Model สำหรับจัดการแคมเปญการส่งอีเมลแบบ Bulk
 *
 * @property int $id
 * @property string $name ชื่อแคมเปญ
 * @property string $subject หัวเรื่องอีเมล
 * @property string|null $description คำอธิบายแคมเปญ
 * @property int|null $template_id เทมเพลตที่ใช้
 * @property string|null $content_html เนื้อหา HTML
 * @property string|null $content_plain เนื้อหา Plain Text
 * @property int $created_by ผู้สร้างแคมเปญ
 * @property int|null $approved_by ผู้อนุมัติแคมเปญ
 * @property \Carbon\Carbon|null $scheduled_at กำหนดส่งเวลา
 * @property \Carbon\Carbon|null $started_at เริ่มส่งเมื่อ
 * @property \Carbon\Carbon|null $completed_at ส่งเสร็จเมื่อ
 * @property string $status สถานะแคมเปญ
 * @property string $target_type ประเภทเป้าหมาย
 * @property array|null $target_filters ตัวกรอง JSON
 * @property int $total_recipients จำนวนผู้รับทั้งหมด
 * @property int $sent_count ส่งแล้วจำนวน
 * @property int $delivered_count ส่งถึงแล้วจำนวน
 * @property int $opened_count เปิดอ่านแล้วจำนวน
 * @property int $clicked_count คลิกแล้วจำนวน
 * @property int $bounced_count ตีกลับจำนวน
 * @property int $failed_count ล้มเหลวจำนวน
 * @property int|null $provider_id ผู้ให้บริการที่ใช้
 * @property int $sending_rate อัตราการส่ง (อีเมล/นาที)
 * @property bool $track_opens ติดตามการเปิดอ่าน
 * @property bool $track_clicks ติดตามการคลิก
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class EmailCampaign extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'email_campaigns';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'subject',
        'description',
        'template_id',
        'content_html',
        'content_plain',
        'created_by',
        'approved_by',
        'scheduled_at',
        'started_at',
        'completed_at',
        'status',
        'target_type',
        'target_filters',
        'total_recipients',
        'sent_count',
        'delivered_count',
        'opened_count',
        'clicked_count',
        'bounced_count',
        'failed_count',
        'provider_id',
        'sending_rate',
        'track_opens',
        'track_clicks',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'target_filters' => 'array',
        'total_recipients' => 'integer',
        'sent_count' => 'integer',
        'delivered_count' => 'integer',
        'opened_count' => 'integer',
        'clicked_count' => 'integer',
        'bounced_count' => 'integer',
        'failed_count' => 'integer',
        'sending_rate' => 'integer',
        'track_opens' => 'boolean',
        'track_clicks' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ EmailTemplate
     *
     * @return BelongsTo
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    /**
     * ความสัมพันธ์กับ User ผู้สร้าง
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * ความสัมพันธ์กับ User ผู้อนุมัติ
     *
     * @return BelongsTo
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * ความสัมพันธ์กับ EmailProvider
     *
     * @return BelongsTo
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(EmailProvider::class, 'provider_id');
    }

    /**
     * ความสัมพันธ์กับ EmailCampaignRecipient
     *
     * @return HasMany
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(EmailCampaignRecipient::class, 'campaign_id');
    }

    /**
     * คำนวณ Open Rate (%)
     *
     * @return float
     */
    public function getOpenRateAttribute(): float
    {
        if ($this->delivered_count === 0) {
            return 0;
        }

        return round(($this->opened_count / $this->delivered_count) * 100, 2);
    }

    /**
     * คำนวณ Click Rate (%)
     *
     * @return float
     */
    public function getClickRateAttribute(): float
    {
        if ($this->delivered_count === 0) {
            return 0;
        }

        return round(($this->clicked_count / $this->delivered_count) * 100, 2);
    }

    /**
     * คำนวณ Bounce Rate (%)
     *
     * @return float
     */
    public function getBounceRateAttribute(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }

        return round(($this->bounced_count / $this->total_recipients) * 100, 2);
    }

    /**
     * คำนวณ Success Rate (%)
     *
     * @return float
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }

        return round(($this->delivered_count / $this->total_recipients) * 100, 2);
    }

    /**
     * เช็คว่าแคมเปญกำลังส่งอยู่หรือไม่
     *
     * @return bool
     */
    public function isSending(): bool
    {
        return $this->status === 'sending';
    }

    /**
     * เช็คว่าแคมเปญส่งเสร็จแล้วหรือไม่
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, ['sent', 'completed']);
    }

    /**
     * เช็คว่าแคมเปญสามารถแก้ไขได้หรือไม่
     *
     * @return bool
     */
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'scheduled']);
    }

    /**
     * เช็คว่าแคมเปญสามารถลบได้หรือไม่
     *
     * @return bool
     */
    public function isDeletable(): bool
    {
        return in_array($this->status, ['draft', 'scheduled', 'cancelled', 'failed']);
    }

    /**
     * Scope: แคมเปญที่เป็นฉบับร่าง
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope: แคมเปญที่กำหนดเวลาแล้ว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope: แคมเปญที่กำลังส่ง
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSending($query)
    {
        return $query->where('status', 'sending');
    }

    /**
     * Scope: แคมเปญที่ส่งเสร็จแล้ว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['sent', 'completed']);
    }
}
