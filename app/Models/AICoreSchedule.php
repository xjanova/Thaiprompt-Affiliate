<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AI Core Schedule Model
 *
 * จัดการตารางเวลาการเปิด/ปิด AI features
 * รองรับการตั้งเวลาอัตโนมัติและ recurring schedules
 *
 * @property int $id
 * @property int|null $tenant_id Tenant เฉพาะ (null = ทุก tenant)
 * @property int $feature_id Feature ที่จะตั้งเวลา
 * @property string $schedule_name ชื่อ schedule
 * @property string|null $description คำอธิบาย
 * @property string $action การกระทำ (enable/disable/toggle)
 * @property string $schedule_type ประเภท schedule
 * @property \Carbon\Carbon|null $starts_at วันเวลาเริ่มต้น
 * @property \Carbon\Carbon|null $ends_at วันเวลาสิ้นสุด
 * @property string|null $recurrence รูปแบบการทำซ้ำ
 * @property array|null $recurrence_days วันที่ทำซ้ำ
 * @property string|null $recurrence_time เวลาที่ทำซ้ำ
 * @property string|null $cron_expression Cron expression
 * @property string $timezone Timezone
 * @property bool $is_enabled เปิด/ปิด schedule
 * @property string $status สถานะ schedule
 * @property \Carbon\Carbon|null $last_executed_at ครั้งล่าสุดที่ทำงาน
 * @property \Carbon\Carbon|null $next_execution_at ครั้งถัดไปที่จะทำงาน
 * @property int $execution_count จำนวนครั้งที่ทำงานแล้ว
 * @property int|null $max_executions จำนวนครั้งสูงสุด
 * @property int $retry_count จำนวนครั้งที่ retry
 * @property int $max_retries จำนวน retry สูงสุด
 * @property string|null $last_error Error ล่าสุด
 * @property bool $notify_on_execution แจ้งเตือนเมื่อทำงาน
 * @property bool $notify_on_failure แจ้งเตือนเมื่อล้มเหลว
 * @property array|null $notification_channels ช่องทางแจ้งเตือน
 * @property array|null $metadata ข้อมูลเพิ่มเติม
 * @property int|null $created_by ผู้สร้าง schedule
 */
class AICoreSchedule extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_core_schedules';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'feature_id',
        'schedule_name',
        'description',
        'action',
        'schedule_type',
        'starts_at',
        'ends_at',
        'recurrence',
        'recurrence_days',
        'recurrence_time',
        'cron_expression',
        'timezone',
        'is_enabled',
        'status',
        'last_executed_at',
        'next_execution_at',
        'execution_count',
        'max_executions',
        'retry_count',
        'max_retries',
        'last_error',
        'notify_on_execution',
        'notify_on_failure',
        'notification_channels',
        'metadata',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_enabled' => 'boolean',
        'notify_on_execution' => 'boolean',
        'notify_on_failure' => 'boolean',
        'execution_count' => 'integer',
        'max_executions' => 'integer',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
        'recurrence_days' => 'array',
        'notification_channels' => 'array',
        'metadata' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'last_executed_at' => 'datetime',
        'next_execution_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ Tenant
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(AICoreTenant::class, 'tenant_id');
    }

    /**
     * ความสัมพันธ์กับ Feature
     *
     * @return BelongsTo
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(AICoreFeature::class, 'feature_id');
    }

    /**
     * ความสัมพันธ์กับผู้สร้าง
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: เฉพาะ schedules ที่ active
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_enabled', true)
            ->whereIn('status', ['pending', 'active']);
    }

    /**
     * Scope: เฉพาะ schedules ที่ถึงเวลาทำงาน
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDue($query)
    {
        return $query->active()
            ->where('next_execution_at', '<=', now());
    }

    /**
     * Scope: กรองตาม schedule type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeType($query, string $type)
    {
        return $query->where('schedule_type', $type);
    }

    /**
     * ตรวจสอบว่า schedule ถึงเวลาทำงานหรือไม่
     *
     * @return bool
     */
    public function isDue(): bool
    {
        if (!$this->is_enabled || !in_array($this->status, ['pending', 'active'])) {
            return false;
        }

        return $this->next_execution_at && $this->next_execution_at->isPast();
    }

    /**
     * ตรวจสอบว่า schedule ถึงจำนวนครั้งสูงสุดหรือยัง
     *
     * @return bool
     */
    public function hasReachedMaxExecutions(): bool
    {
        if ($this->max_executions === null) {
            return false;
        }

        return $this->execution_count >= $this->max_executions;
    }

    /**
     * ตรวจสอบว่า schedule ถึงจำนวน retry สูงสุดหรือยัง
     *
     * @return bool
     */
    public function hasReachedMaxRetries(): bool
    {
        return $this->retry_count >= $this->max_retries;
    }

    /**
     * บันทึกการ execute
     *
     * @param bool $success สำเร็จหรือไม่
     * @param string|null $error ข้อความ error
     * @return bool
     */
    public function recordExecution(bool $success, ?string $error = null): bool
    {
        $updates = [
            'last_executed_at' => now(),
            'execution_count' => $this->execution_count + 1,
        ];

        if ($success) {
            $updates['retry_count'] = 0; // Reset retry count on success
            $updates['last_error'] = null;

            // คำนวณ next execution
            if ($this->schedule_type === 'recurring') {
                $updates['next_execution_at'] = $this->calculateNextExecution();
            } elseif ($this->hasReachedMaxExecutions()) {
                $updates['status'] = 'completed';
                $updates['next_execution_at'] = null;
            }
        } else {
            $updates['retry_count'] = $this->retry_count + 1;
            $updates['last_error'] = $error;

            if ($this->hasReachedMaxRetries()) {
                $updates['status'] = 'failed';
            }
        }

        return $this->update($updates);
    }

    /**
     * คำนวณเวลา execution ครั้งถัดไป
     *
     * @return \Carbon\Carbon|null
     */
    public function calculateNextExecution(): ?\Carbon\Carbon
    {
        if ($this->schedule_type === 'once') {
            return null;
        }

        if ($this->schedule_type === 'cron' && $this->cron_expression) {
            // TODO: Implement cron expression parsing
            // สามารถใช้ library เช่น dragonmantank/cron-expression
            return null;
        }

        if ($this->schedule_type === 'recurring') {
            $now = now($this->timezone);

            return match ($this->recurrence) {
                'daily' => $now->addDay()->setTimeFromTimeString($this->recurrence_time ?? '00:00'),
                'weekly' => $now->addWeek()->setTimeFromTimeString($this->recurrence_time ?? '00:00'),
                'monthly' => $now->addMonth()->setTimeFromTimeString($this->recurrence_time ?? '00:00'),
                'yearly' => $now->addYear()->setTimeFromTimeString($this->recurrence_time ?? '00:00'),
                default => null,
            };
        }

        return null;
    }

    /**
     * เปิดใช้งาน schedule
     *
     * @return bool
     */
    public function enable(): bool
    {
        return $this->update([
            'is_enabled' => true,
            'status' => 'active',
        ]);
    }

    /**
     * ปิดใช้งาน schedule
     *
     * @return bool
     */
    public function disable(): bool
    {
        return $this->update([
            'is_enabled' => false,
        ]);
    }

    /**
     * ยกเลิก schedule
     *
     * @return bool
     */
    public function cancel(): bool
    {
        return $this->update([
            'status' => 'cancelled',
            'is_enabled' => false,
        ]);
    }
}
