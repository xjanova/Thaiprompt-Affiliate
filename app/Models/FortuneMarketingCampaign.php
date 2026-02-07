<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * FortuneMarketingCampaign Model
 *
 * แคมเปญการตลาดดูดวงอัตโนมัติ
 * ตั้งหัวข้อ + เวลา + เป้าหมาย → AI สร้างข้อความ → ส่งอัตโนมัติ
 *
 * @property int $id
 * @property string $name ชื่อแคมเปญ
 * @property string|null $topic หัวข้อ/เรื่อง
 * @property string|null $description คำอธิบาย
 * @property string|null $message_template ข้อความที่กำหนดเอง
 * @property string|null $ai_generated_message ข้อความที่ AI สร้าง
 * @property string|null $ai_prompt prompt สำหรับ AI
 * @property bool $use_ai_generate ใช้ AI สร้างข้อความหรือไม่
 * @property string $target_audience กลุ่มเป้าหมาย: all, paid, recent, new
 * @property int|null $target_limit จำนวนคนสูงสุดที่จะส่ง
 * @property string $target_platform platform: all, facebook, line
 * @property array|null $target_filters ตัวกรองเพิ่มเติม
 * @property string $schedule_type ประเภทตาราง: once, daily, weekly, custom
 * @property \Carbon\Carbon|null $scheduled_at เวลาที่กำหนดส่ง
 * @property string|null $recurring_cron cron expression สำหรับส่งซ้ำ
 * @property \Carbon\Carbon|null $recurring_until ส่งซ้ำจนถึงวันที่
 * @property \Carbon\Carbon|null $last_run_at ส่งล่าสุดเมื่อ
 * @property \Carbon\Carbon|null $next_run_at ส่งครั้งถัดไปเมื่อ
 * @property string $status สถานะ: draft, scheduled, sending, completed, paused, cancelled
 * @property int $sent_count จำนวนที่ส่งสำเร็จ
 * @property int $failed_count จำนวนที่ส่งไม่สำเร็จ
 * @property int $total_target จำนวนเป้าหมายทั้งหมด
 * @property string|null $last_error ข้อผิดพลาดล่าสุด
 * @property int|null $created_by แอดมินที่สร้าง
 */
class FortuneMarketingCampaign extends Model
{
    use SoftDeletes;

    protected $table = 'fortune_marketing_campaigns';

    // สถานะแคมเปญ
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_SENDING = 'sending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_CANCELLED = 'cancelled';

    // กลุ่มเป้าหมาย
    public const TARGET_ALL = 'all';
    public const TARGET_PAID = 'paid';
    public const TARGET_RECENT = 'recent';
    public const TARGET_NEW = 'new';

    // ประเภทตาราง
    public const SCHEDULE_ONCE = 'once';
    public const SCHEDULE_DAILY = 'daily';
    public const SCHEDULE_WEEKLY = 'weekly';
    public const SCHEDULE_CUSTOM = 'custom';

    protected $fillable = [
        'name',
        'topic',
        'description',
        'message_template',
        'ai_generated_message',
        'ai_prompt',
        'use_ai_generate',
        'target_audience',
        'target_limit',
        'target_platform',
        'target_filters',
        'schedule_type',
        'scheduled_at',
        'recurring_cron',
        'recurring_until',
        'last_run_at',
        'next_run_at',
        'status',
        'sent_count',
        'failed_count',
        'total_target',
        'last_error',
        'created_by',
    ];

    protected $casts = [
        'use_ai_generate' => 'boolean',
        'target_filters' => 'array',
        'target_limit' => 'integer',
        'scheduled_at' => 'datetime',
        'recurring_until' => 'datetime',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'total_target' => 'integer',
    ];

    protected $attributes = [
        'status' => 'draft',
        'use_ai_generate' => true,
        'target_audience' => 'all',
        'target_platform' => 'all',
        'schedule_type' => 'once',
        'sent_count' => 0,
        'failed_count' => 0,
        'total_target' => 0,
    ];

    // ============================================================
    // Relationships
    // ============================================================

    /**
     * แอดมินที่สร้าง
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ============================================================
    // Scopes
    // ============================================================

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_SCHEDULED, self::STATUS_SENDING]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * แคมเปญที่ถึงเวลาส่งแล้ว
     */
    public function scopeReadyToSend($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->where(function ($q) {
                // ส่งครั้งเดียว: ถึงเวลาแล้ว
                $q->where(function ($sub) {
                    $sub->where('schedule_type', self::SCHEDULE_ONCE)
                        ->where('scheduled_at', '<=', now());
                })
                // ส่งซ้ำ: ถึง next_run_at แล้ว
                ->orWhere(function ($sub) {
                    $sub->whereIn('schedule_type', [self::SCHEDULE_DAILY, self::SCHEDULE_WEEKLY, self::SCHEDULE_CUSTOM])
                        ->whereNotNull('next_run_at')
                        ->where('next_run_at', '<=', now());
                });
            });
    }

    // ============================================================
    // Instance Methods
    // ============================================================

    /**
     * ดึงข้อความที่จะส่ง (AI หรือ กำหนดเอง)
     */
    public function getMessageToSend(): ?string
    {
        if ($this->use_ai_generate && $this->ai_generated_message) {
            return $this->ai_generated_message;
        }

        return $this->message_template;
    }

    /**
     * ตรวจสอบว่าแคมเปญพร้อมส่งหรือไม่
     */
    public function isReadyToSend(): bool
    {
        // ต้องมีข้อความ
        if (!$this->getMessageToSend()) {
            return false;
        }

        // ต้องอยู่สถานะ scheduled
        if ($this->status !== self::STATUS_SCHEDULED) {
            return false;
        }

        // เช็คเวลา
        if ($this->schedule_type === self::SCHEDULE_ONCE) {
            return $this->scheduled_at && $this->scheduled_at->lte(now());
        }

        // ส่งซ้ำ: เช็ค next_run_at
        return $this->next_run_at && $this->next_run_at->lte(now());
    }

    /**
     * เริ่มส่ง
     */
    public function markSending(): self
    {
        $this->update(['status' => self::STATUS_SENDING]);
        return $this;
    }

    /**
     * ส่งเสร็จ
     */
    public function markCompleted(int $sent, int $failed): self
    {
        $updateData = [
            'sent_count' => $this->sent_count + $sent,
            'failed_count' => $this->failed_count + $failed,
            'last_run_at' => now(),
        ];

        // ถ้าส่งครั้งเดียว → completed
        if ($this->schedule_type === self::SCHEDULE_ONCE) {
            $updateData['status'] = self::STATUS_COMPLETED;
        } else {
            // ส่งซ้ำ → คำนวณ next_run_at
            $nextRun = $this->calculateNextRun();
            if ($nextRun && (!$this->recurring_until || $nextRun->lte($this->recurring_until))) {
                $updateData['status'] = self::STATUS_SCHEDULED;
                $updateData['next_run_at'] = $nextRun;
            } else {
                $updateData['status'] = self::STATUS_COMPLETED;
                $updateData['next_run_at'] = null;
            }
        }

        $this->update($updateData);
        return $this->fresh();
    }

    /**
     * บันทึก error
     */
    public function markError(string $error): self
    {
        $this->update([
            'status' => self::STATUS_SCHEDULED,
            'last_error' => $error,
        ]);
        return $this;
    }

    /**
     * คำนวณเวลาส่งครั้งถัดไป
     */
    public function calculateNextRun(): ?Carbon
    {
        $now = now();

        return match ($this->schedule_type) {
            self::SCHEDULE_DAILY => $now->copy()->addDay()->setTimeFromTimeString(
                $this->scheduled_at ? $this->scheduled_at->format('H:i:s') : '09:00:00'
            ),
            self::SCHEDULE_WEEKLY => $now->copy()->addWeek()->setTimeFromTimeString(
                $this->scheduled_at ? $this->scheduled_at->format('H:i:s') : '09:00:00'
            ),
            default => null,
        };
    }

    /**
     * ตั้ง scheduled พร้อมคำนวณ next_run_at
     */
    public function activate(): self
    {
        $updateData = ['status' => self::STATUS_SCHEDULED];

        if (in_array($this->schedule_type, [self::SCHEDULE_DAILY, self::SCHEDULE_WEEKLY, self::SCHEDULE_CUSTOM])) {
            $updateData['next_run_at'] = $this->scheduled_at ?? now();
        }

        $this->update($updateData);
        return $this->fresh();
    }

    /**
     * หยุดชั่วคราว
     */
    public function pause(): self
    {
        $this->update(['status' => self::STATUS_PAUSED]);
        return $this;
    }

    /**
     * ยกเลิก
     */
    public function cancel(): self
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
        return $this;
    }

    /**
     * Label สถานะภาษาไทย
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'แบบร่าง',
            self::STATUS_SCHEDULED => 'ตั้งเวลาแล้ว',
            self::STATUS_SENDING => 'กำลังส่ง',
            self::STATUS_COMPLETED => 'ส่งเสร็จแล้ว',
            self::STATUS_PAUSED => 'หยุดชั่วคราว',
            self::STATUS_CANCELLED => 'ยกเลิก',
            default => $this->status,
        };
    }

    /**
     * Label กลุ่มเป้าหมายภาษาไทย
     */
    public function getTargetLabelAttribute(): string
    {
        return match ($this->target_audience) {
            self::TARGET_ALL => 'ทุกคนที่เคยดูดวง',
            self::TARGET_PAID => 'ลูกค้าจ่ายเงิน',
            self::TARGET_RECENT => 'ดูดวงภายใน 7 วัน',
            self::TARGET_NEW => 'ผู้ใช้ใหม่ (ดู 1 ครั้ง)',
            default => $this->target_audience,
        };
    }
}
