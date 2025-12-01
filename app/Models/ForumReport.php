<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * ForumReport Model - การรายงานโพสต์ที่ไม่เหมาะสม
 *
 * @property int $id
 * @property int $user_id ผู้รายงาน
 * @property string $reportable_type ประเภท (thread/post)
 * @property int $reportable_id ID ของ item
 * @property string $reason_type ประเภทเหตุผล
 * @property string|null $reason_detail รายละเอียด
 * @property string $status สถานะ
 * @property int|null $reviewed_by ผู้ตรวจสอบ
 * @property \Carbon\Carbon|null $reviewed_at เวลาตรวจสอบ
 * @property string|null $reviewer_notes บันทึกผู้ตรวจสอบ
 * @property string|null $action_taken การดำเนินการ
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read User $user
 * @property-read User|null $reviewer
 * @property-read ForumThread|ForumPost $reportable
 */
class ForumReport extends Model
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'forum_reports';

    /**
     * คอลัมน์ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'reportable_type',
        'reportable_id',
        'reason_type',
        'reason_detail',
        'status',
        'reviewed_by',
        'reviewed_at',
        'reviewer_notes',
        'action_taken',
    ];

    /**
     * การ cast ค่า
     *
     * @var array<string, string>
     */
    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    /**
     * ประเภทเหตุผลที่รองรับ
     *
     * @var array
     */
    public static array $reasonTypes = [
        'spam' => 'สแปม',
        'harassment' => 'คุกคาม',
        'inappropriate' => 'เนื้อหาไม่เหมาะสม',
        'misinformation' => 'ข้อมูลเท็จ',
        'hate_speech' => 'Hate Speech',
        'copyright' => 'ละเมิดลิขสิทธิ์',
        'other' => 'อื่นๆ',
    ];

    /**
     * สถานะที่รองรับ
     *
     * @var array
     */
    public static array $statuses = [
        'pending' => 'รอตรวจสอบ',
        'reviewed' => 'กำลังตรวจสอบ',
        'resolved' => 'แก้ไขแล้ว',
        'dismissed' => 'ยกเลิก',
    ];

    /**
     * การดำเนินการที่รองรับ
     *
     * @var array
     */
    public static array $actions = [
        'none' => 'ไม่มี',
        'warning' => 'เตือน',
        'hide_content' => 'ซ่อนเนื้อหา',
        'delete_content' => 'ลบเนื้อหา',
        'ban_user' => 'แบนผู้ใช้',
    ];

    /**
     * ความสัมพันธ์กับผู้รายงาน
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับผู้ตรวจสอบ
     *
     * @return BelongsTo
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * ความสัมพันธ์ polymorphic กับ item ที่ถูกรายงาน
     *
     * @return MorphTo
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope: รายงานที่รอตรวจสอบ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: รายงานที่แก้ไขแล้ว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * เริ่มตรวจสอบ
     *
     * @param User $reviewer
     * @return void
     */
    public function startReview(User $reviewer): void
    {
        $this->update([
            'status' => 'reviewed',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * แก้ไขเสร็จสิ้น
     *
     * @param string $action การดำเนินการ
     * @param string|null $notes บันทึก
     * @return void
     */
    public function resolve(string $action, ?string $notes = null): void
    {
        $this->update([
            'status' => 'resolved',
            'action_taken' => $action,
            'reviewer_notes' => $notes,
            'reviewed_at' => now(),
        ]);

        // ดำเนินการตาม action
        $this->executeAction($action);
    }

    /**
     * ยกเลิกรายงาน
     *
     * @param string|null $notes บันทึก
     * @return void
     */
    public function dismiss(?string $notes = null): void
    {
        $this->update([
            'status' => 'dismissed',
            'action_taken' => 'none',
            'reviewer_notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * ดำเนินการตาม action
     *
     * @param string $action
     * @return void
     */
    protected function executeAction(string $action): void
    {
        $reportable = $this->reportable;
        $contentOwner = $reportable->user;

        match ($action) {
            'warning' => $contentOwner->increment('forum_warnings_count'),
            'hide_content' => $reportable->update(['is_hidden' => true]),
            'delete_content' => $reportable->delete(),
            'ban_user' => $contentOwner->update([
                'forum_banned_until' => now()->addDays(7),
            ]),
            default => null,
        };

        // ตรวจสอบและให้โทรฟี่ไม่ดี
        if (in_array($action, ['warning', 'ban_user'])) {
            app(\App\Services\ForumTrophyService::class)->checkAndAwardNegativeTrophies($contentOwner);
        }
    }

    /**
     * ดึงชื่อประเภทเหตุผล
     *
     * @return string
     */
    public function getReasonTypeLabelAttribute(): string
    {
        return self::$reasonTypes[$this->reason_type] ?? $this->reason_type;
    }

    /**
     * ดึงชื่อสถานะ
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return self::$statuses[$this->status] ?? $this->status;
    }

    /**
     * ดึงชื่อการดำเนินการ
     *
     * @return string
     */
    public function getActionTakenLabelAttribute(): string
    {
        return self::$actions[$this->action_taken] ?? $this->action_taken ?? 'ไม่มี';
    }
}
