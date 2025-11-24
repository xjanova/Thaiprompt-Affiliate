<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model สำหรับ AI Rental Budget Limits
 *
 * จัดการ budget limits และ spending controls
 *
 * @property int $id
 * @property int $user_id ID ผู้ใช้
 * @property int|null $cloud_config_id ID config
 * @property int|null $deployment_id ID deployment
 * @property string $budget_name ชื่อ budget
 * @property string|null $description คำอธิบาย
 * @property string $budget_type ประเภท budget
 * @property float $limit_amount วงเงินจำกัด
 * @property float $current_spending ค่าใช้จ่ายปัจจุบัน
 * @property int $warning_threshold_1 Threshold 1
 * @property int $warning_threshold_2 Threshold 2
 * @property int $warning_threshold_3 Threshold 3
 * @property bool $threshold_1_triggered Triggered 1
 * @property bool $threshold_2_triggered Triggered 2
 * @property bool $threshold_3_triggered Triggered 3
 * @property \Carbon\Carbon|null $threshold_1_triggered_at
 * @property \Carbon\Carbon|null $threshold_2_triggered_at
 * @property \Carbon\Carbon|null $threshold_3_triggered_at
 * @property bool $auto_stop_at_limit หยุดอัตโนมัติ
 * @property bool $auto_notify_email แจ้งเตือน email
 * @property bool $auto_notify_push แจ้งเตือน push
 * @property string $status สถานะ
 * @property bool $is_active เปิดใช้งาน
 * @property \Carbon\Carbon|null $period_start เริ่ม period
 * @property \Carbon\Carbon|null $period_end สิ้นสุด period
 * @property \Carbon\Carbon|null $last_reset_at รีเซ็ตล่าสุด
 * @property int $total_alerts_sent จำนวน alerts
 * @property int $times_exceeded เกินกี่ครั้ง
 * @property \Carbon\Carbon|null $last_exceeded_at เกินล่าสุด
 * @property bool $allow_rollover อนุญาต rollover
 * @property float $rollover_amount จำนวน rollover
 * @property array|null $notification_emails Emails แจ้งเตือน
 * @property array|null $webhook_urls Webhook URLs
 * @property array|null $metadata ข้อมูลเพิ่มเติม
 * @property string|null $notes บันทึก
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class AiRentalBudgetLimit extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_rental_budget_limits';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'cloud_config_id',
        'deployment_id',
        'budget_name',
        'description',
        'budget_type',
        'limit_amount',
        'current_spending',
        'warning_threshold_1',
        'warning_threshold_2',
        'warning_threshold_3',
        'threshold_1_triggered',
        'threshold_2_triggered',
        'threshold_3_triggered',
        'threshold_1_triggered_at',
        'threshold_2_triggered_at',
        'threshold_3_triggered_at',
        'auto_stop_at_limit',
        'auto_notify_email',
        'auto_notify_push',
        'status',
        'is_active',
        'period_start',
        'period_end',
        'last_reset_at',
        'total_alerts_sent',
        'times_exceeded',
        'last_exceeded_at',
        'allow_rollover',
        'rollover_amount',
        'notification_emails',
        'webhook_urls',
        'metadata',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'limit_amount' => 'decimal:2',
        'current_spending' => 'decimal:2',
        'rollover_amount' => 'decimal:2',
        'warning_threshold_1' => 'integer',
        'warning_threshold_2' => 'integer',
        'warning_threshold_3' => 'integer',
        'threshold_1_triggered' => 'boolean',
        'threshold_2_triggered' => 'boolean',
        'threshold_3_triggered' => 'boolean',
        'auto_stop_at_limit' => 'boolean',
        'auto_notify_email' => 'boolean',
        'auto_notify_push' => 'boolean',
        'is_active' => 'boolean',
        'allow_rollover' => 'boolean',
        'total_alerts_sent' => 'integer',
        'times_exceeded' => 'integer',
        'notification_emails' => 'array',
        'webhook_urls' => 'array',
        'metadata' => 'array',
        'threshold_1_triggered_at' => 'datetime',
        'threshold_2_triggered_at' => 'datetime',
        'threshold_3_triggered_at' => 'datetime',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'last_reset_at' => 'datetime',
        'last_exceeded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ User
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ Cloud Config
     *
     * @return BelongsTo
     */
    public function cloudConfig(): BelongsTo
    {
        return $this->belongsTo(AiRentalCloudConfig::class, 'cloud_config_id');
    }

    /**
     * ความสัมพันธ์กับ Deployment
     *
     * @return BelongsTo
     */
    public function deployment(): BelongsTo
    {
        return $this->belongsTo(AiRentalDeployment::class, 'deployment_id');
    }

    /**
     * Scope: Budget limits ที่ active
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('status', 'active');
    }

    /**
     * Scope: Budget limits ที่เกินแล้ว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExceeded($query)
    {
        return $query->where('status', 'exceeded');
    }

    /**
     * Scope: Budget limits สำหรับ user
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * คำนวณเปอร์เซ็นต์ของค่าใช้จ่าย
     *
     * @return float
     */
    public function getSpendingPercentage(): float
    {
        if ($this->limit_amount == 0) {
            return 0;
        }
        return ($this->current_spending / $this->limit_amount) * 100;
    }

    /**
     * ตรวจสอบว่าเกิน budget หรือยัง
     *
     * @return bool
     */
    public function isExceeded(): bool
    {
        return $this->current_spending >= $this->limit_amount;
    }

    /**
     * ตรวจสอบว่าใกล้เกิน budget หรือยัง (ตาม threshold)
     *
     * @param int $threshold
     * @return bool
     */
    public function isNearLimit(int $threshold = 90): bool
    {
        return $this->getSpendingPercentage() >= $threshold;
    }

    /**
     * เพิ่มค่าใช้จ่าย
     *
     * @param float $amount
     * @return bool
     */
    public function addSpending(float $amount): bool
    {
        $oldSpending = $this->current_spending;
        $this->current_spending += $amount;

        // ตรวจสอบ thresholds
        $this->checkThresholds($oldSpending);

        // ตรวจสอบว่าเกิน limit หรือยัง
        if ($this->isExceeded() && $this->status !== 'exceeded') {
            $this->status = 'exceeded';
            $this->times_exceeded++;
            $this->last_exceeded_at = now();

            // TODO: Trigger alert และ auto-stop (ถ้าเปิดไว้)
        }

        return $this->save();
    }

    /**
     * ตรวจสอบและ trigger thresholds
     *
     * @param float $oldSpending
     * @return void
     */
    protected function checkThresholds(float $oldSpending): void
    {
        $oldPercentage = ($oldSpending / $this->limit_amount) * 100;
        $newPercentage = $this->getSpendingPercentage();

        // Threshold 1
        if ($newPercentage >= $this->warning_threshold_1 && !$this->threshold_1_triggered) {
            $this->threshold_1_triggered = true;
            $this->threshold_1_triggered_at = now();
            $this->total_alerts_sent++;
            // TODO: Send alert
        }

        // Threshold 2
        if ($newPercentage >= $this->warning_threshold_2 && !$this->threshold_2_triggered) {
            $this->threshold_2_triggered = true;
            $this->threshold_2_triggered_at = now();
            $this->total_alerts_sent++;
            // TODO: Send alert
        }

        // Threshold 3
        if ($newPercentage >= $this->warning_threshold_3 && !$this->threshold_3_triggered) {
            $this->threshold_3_triggered = true;
            $this->threshold_3_triggered_at = now();
            $this->total_alerts_sent++;
            // TODO: Send alert
        }
    }

    /**
     * รีเซ็ต budget (สำหรับ budget แบบ period)
     *
     * @return bool
     */
    public function reset(): bool
    {
        // ถ้าอนุญาต rollover ให้เก็บเงินเหลือไว้
        if ($this->allow_rollover && $this->current_spending < $this->limit_amount) {
            $this->rollover_amount = $this->limit_amount - $this->current_spending;
        } else {
            $this->rollover_amount = 0;
        }

        $this->current_spending = 0;
        $this->threshold_1_triggered = false;
        $this->threshold_2_triggered = false;
        $this->threshold_3_triggered = false;
        $this->threshold_1_triggered_at = null;
        $this->threshold_2_triggered_at = null;
        $this->threshold_3_triggered_at = null;
        $this->status = 'active';
        $this->last_reset_at = now();

        // ตั้ง period ใหม่
        $this->setPeriod();

        return $this->save();
    }

    /**
     * ตั้งค่า period ตาม budget_type
     *
     * @return void
     */
    protected function setPeriod(): void
    {
        $this->period_start = now();

        switch ($this->budget_type) {
            case 'daily':
                $this->period_end = now()->addDay();
                break;
            case 'weekly':
                $this->period_end = now()->addWeek();
                break;
            case 'monthly':
                $this->period_end = now()->addMonth();
                break;
            default:
                $this->period_end = null;
        }
    }

    /**
     * ตรวจสอบว่า period หมดอายุหรือยัง
     *
     * @return bool
     */
    public function isPeriodExpired(): bool
    {
        return !is_null($this->period_end) && $this->period_end < now();
    }
}
