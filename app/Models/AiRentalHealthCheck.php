<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model สำหรับ AI Rental Health Checks
 *
 * จัดการการตรวจสอบสุขภาพของ deployments
 *
 * @property int $id
 * @property int $deployment_id ID deployment
 * @property int $user_id ID ผู้ใช้
 * @property string $check_type ประเภทการตรวจสอบ
 * @property string $check_url URL ตรวจสอบ
 * @property string $http_method HTTP method
 * @property array|null $request_headers Headers
 * @property array|null $request_body Body
 * @property string $status สถานะสุขภาพ
 * @property int|null $http_status_code HTTP status
 * @property float|null $response_time_ms Response time
 * @property bool $is_success สำเร็จหรือไม่
 * @property string|null $response_body Response body
 * @property string|null $error_message Error message
 * @property string|null $error_details Error details
 * @property float|null $cpu_usage CPU %
 * @property float|null $memory_usage Memory %
 * @property float|null $gpu_usage GPU %
 * @property int|null $memory_used_mb Memory used MB
 * @property int|null $memory_total_mb Memory total MB
 * @property float|null $disk_usage Disk %
 * @property int|null $active_connections Connections
 * @property int|null $queue_length Queue length
 * @property bool $threshold_exceeded Threshold exceeded
 * @property array|null $thresholds Thresholds
 * @property bool $alert_triggered Alert triggered
 * @property int|null $alert_id Alert ID
 * @property int $retry_count Retry count
 * @property int $max_retries Max retries
 * @property \Carbon\Carbon|null $next_retry_at Next retry
 * @property bool $auto_recovery_attempted Recovery attempted
 * @property string $recovery_action Recovery action
 * @property bool $recovery_success Recovery success
 * @property string|null $recovery_notes Recovery notes
 * @property \Carbon\Carbon $checked_at เวลาตรวจสอบ
 * @property \Carbon\Carbon|null $started_at เริ่มเมื่อ
 * @property \Carbon\Carbon|null $completed_at เสร็จเมื่อ
 * @property int|null $duration_ms Duration
 * @property string $check_frequency ความถี่
 * @property \Carbon\Carbon|null $next_check_at ตรวจสอบครั้งถัดไป
 * @property array|null $metadata ข้อมูลเพิ่มเติม
 * @property array|null $headers_received Headers ที่ได้รับ
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AiRentalHealthCheck extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_rental_health_checks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'deployment_id',
        'user_id',
        'check_type',
        'check_url',
        'http_method',
        'request_headers',
        'request_body',
        'status',
        'http_status_code',
        'response_time_ms',
        'is_success',
        'response_body',
        'error_message',
        'error_details',
        'cpu_usage',
        'memory_usage',
        'gpu_usage',
        'memory_used_mb',
        'memory_total_mb',
        'disk_usage',
        'active_connections',
        'queue_length',
        'threshold_exceeded',
        'thresholds',
        'alert_triggered',
        'alert_id',
        'retry_count',
        'max_retries',
        'next_retry_at',
        'auto_recovery_attempted',
        'recovery_action',
        'recovery_success',
        'recovery_notes',
        'checked_at',
        'started_at',
        'completed_at',
        'duration_ms',
        'check_frequency',
        'next_check_at',
        'metadata',
        'headers_received',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request_headers' => 'array',
        'request_body' => 'array',
        'thresholds' => 'array',
        'metadata' => 'array',
        'headers_received' => 'array',
        'is_success' => 'boolean',
        'threshold_exceeded' => 'boolean',
        'alert_triggered' => 'boolean',
        'auto_recovery_attempted' => 'boolean',
        'recovery_success' => 'boolean',
        'cpu_usage' => 'decimal:2',
        'memory_usage' => 'decimal:2',
        'gpu_usage' => 'decimal:2',
        'disk_usage' => 'decimal:2',
        'response_time_ms' => 'decimal:2',
        'http_status_code' => 'integer',
        'memory_used_mb' => 'integer',
        'memory_total_mb' => 'integer',
        'active_connections' => 'integer',
        'queue_length' => 'integer',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
        'duration_ms' => 'integer',
        'checked_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'next_check_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
     * ความสัมพันธ์กับ User
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ Alert
     *
     * @return BelongsTo
     */
    public function alert(): BelongsTo
    {
        return $this->belongsTo(AiRentalAlert::class, 'alert_id');
    }

    /**
     * Scope: Health checks ที่สำเร็จ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccessful($query)
    {
        return $query->where('is_success', true);
    }

    /**
     * Scope: Health checks ที่ล้มเหลว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('is_success', false);
    }

    /**
     * Scope: Health checks ตาม status
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Health checks ที่ unhealthy
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnhealthy($query)
    {
        return $query->whereIn('status', ['degraded', 'unhealthy']);
    }

    /**
     * Scope: Health checks ล่าสุด
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('checked_at', 'desc');
    }

    /**
     * Scope: Health checks สำหรับ deployment
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $deploymentId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDeployment($query, int $deploymentId)
    {
        return $query->where('deployment_id', $deploymentId);
    }

    /**
     * ตรวจสอบว่า healthy หรือไม่
     *
     * @return bool
     */
    public function isHealthy(): bool
    {
        return $this->status === 'healthy' && $this->is_success;
    }

    /**
     * ตรวจสอบว่าควร retry หรือไม่
     *
     * @return bool
     */
    public function shouldRetry(): bool
    {
        return !$this->is_success && $this->retry_count < $this->max_retries;
    }

    /**
     * เพิ่ม retry count
     *
     * @return bool
     */
    public function incrementRetry(): bool
    {
        $this->retry_count++;

        // คำนวณเวลา retry ครั้งถัดไป (exponential backoff)
        $backoffMinutes = pow(2, $this->retry_count); // 2, 4, 8, 16...
        $this->next_retry_at = now()->addMinutes($backoffMinutes);

        return $this->save();
    }

    /**
     * ตรวจสอบ thresholds
     *
     * @return bool
     */
    public function checkThresholds(): bool
    {
        if (!$this->thresholds) {
            return false;
        }

        $exceeded = false;

        // CPU threshold
        if (isset($this->thresholds['cpu']) && $this->cpu_usage > $this->thresholds['cpu']) {
            $exceeded = true;
        }

        // Memory threshold
        if (isset($this->thresholds['memory']) && $this->memory_usage > $this->thresholds['memory']) {
            $exceeded = true;
        }

        // GPU threshold
        if (isset($this->thresholds['gpu']) && $this->gpu_usage > $this->thresholds['gpu']) {
            $exceeded = true;
        }

        // Response time threshold
        if (isset($this->thresholds['response_time']) && $this->response_time_ms > $this->thresholds['response_time']) {
            $exceeded = true;
        }

        $this->threshold_exceeded = $exceeded;
        $this->save();

        return $exceeded;
    }

    /**
     * พยายาม auto-recovery
     *
     * @return bool
     */
    public function attemptRecovery(): bool
    {
        if ($this->auto_recovery_attempted) {
            return false;
        }

        $this->auto_recovery_attempted = true;

        // TODO: Implement actual recovery logic
        switch ($this->recovery_action) {
            case 'restart':
                // Restart deployment
                $this->recovery_notes = 'Attempted to restart deployment';
                break;
            case 'scale':
                // Scale up resources
                $this->recovery_notes = 'Attempted to scale up resources';
                break;
            case 'notify':
                // Send notification
                $this->recovery_notes = 'Notification sent to admin';
                break;
            default:
                $this->recovery_notes = 'No recovery action configured';
        }

        return $this->save();
    }

    /**
     * คำนวณ uptime percentage จาก health checks
     *
     * @param int $deploymentId
     * @param int $hours ย้อนหลังกี่ชั่วโมง
     * @return float
     */
    public static function calculateUptime(int $deploymentId, int $hours = 24): float
    {
        $total = static::forDeployment($deploymentId)
            ->where('checked_at', '>=', now()->subHours($hours))
            ->count();

        if ($total === 0) {
            return 0;
        }

        $successful = static::forDeployment($deploymentId)
            ->where('checked_at', '>=', now()->subHours($hours))
            ->successful()
            ->count();

        return ($successful / $total) * 100;
    }
}
