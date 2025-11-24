<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Model สำหรับ AI Model Deployments
 *
 * จัดการการ deploy AI models ไปยัง Cloud GPU
 *
 * @property int $id
 * @property int $user_id ID ผู้ใช้
 * @property int $cloud_config_id ID config
 * @property string $model_name ชื่อ model
 * @property string $model_source แหล่งที่มา
 * @property string|null $model_id Model ID
 * @property string|null $model_type ประเภท model
 * @property string|null $model_version Version
 * @property string|null $deployment_id Deployment ID
 * @property string $deployment_name ชื่อ deployment
 * @property string|null $deployment_description คำอธิบาย
 * @property array|null $model_config การตั้งค่า model
 * @property array|null $runtime_config การตั้งค่า runtime
 * @property int $max_batch_size Batch size
 * @property int $timeout_seconds Timeout
 * @property string $status สถานะ
 * @property \Carbon\Carbon|null $deployed_at เวลา deploy สำเร็จ
 * @property \Carbon\Carbon|null $stopped_at เวลาหยุด
 * @property string|null $api_endpoint API endpoint
 * @property string|null $websocket_endpoint WebSocket endpoint
 * @property string|null $health_check_url Health check URL
 * @property int $total_requests จำนวน requests
 * @property int $successful_requests Requests สำเร็จ
 * @property int $failed_requests Requests ล้มเหลว
 * @property float|null $avg_response_time_ms เวลาตอบสนองเฉลี่ย
 * @property int|null $uptime_percentage Uptime %
 * @property float|null $cost_per_hour ค่าใช้จ่ายต่อชั่วโมง
 * @property float $total_cost ค่าใช้จ่ายรวม
 * @property int $total_hours ชั่วโมงรวม
 * @property string|null $last_error Error ล่าสุด
 * @property \Carbon\Carbon|null $last_error_at เวลา error ล่าสุด
 * @property string|null $build_logs Build logs
 * @property bool $auto_stop หยุดอัตโนมัติ
 * @property int|null $auto_stop_minutes หยุดหลัง (นาที)
 * @property bool $auto_restart Restart อัตโนมัติ
 * @property array|null $metadata ข้อมูลเพิ่มเติม
 * @property string|null $notes บันทึก
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class AiRentalDeployment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_rental_deployments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'cloud_config_id',
        'model_name',
        'model_source',
        'model_id',
        'model_type',
        'model_version',
        'deployment_id',
        'deployment_name',
        'deployment_description',
        'model_config',
        'runtime_config',
        'max_batch_size',
        'timeout_seconds',
        'status',
        'deployed_at',
        'stopped_at',
        'api_endpoint',
        'websocket_endpoint',
        'health_check_url',
        'total_requests',
        'successful_requests',
        'failed_requests',
        'avg_response_time_ms',
        'uptime_percentage',
        'cost_per_hour',
        'total_cost',
        'total_hours',
        'last_error',
        'last_error_at',
        'build_logs',
        'auto_stop',
        'auto_stop_minutes',
        'auto_restart',
        'metadata',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'cloud_config_id' => 'integer',
        'model_config' => 'array',
        'runtime_config' => 'array',
        'max_batch_size' => 'integer',
        'timeout_seconds' => 'integer',
        'deployed_at' => 'datetime',
        'stopped_at' => 'datetime',
        'total_requests' => 'integer',
        'successful_requests' => 'integer',
        'failed_requests' => 'integer',
        'avg_response_time_ms' => 'decimal:2',
        'uptime_percentage' => 'integer',
        'cost_per_hour' => 'decimal:4',
        'total_cost' => 'decimal:2',
        'total_hours' => 'integer',
        'last_error_at' => 'datetime',
        'auto_stop' => 'boolean',
        'auto_stop_minutes' => 'integer',
        'auto_restart' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot method
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // สร้าง deployment_id อัตโนมัติ
        static::creating(function ($model) {
            if (empty($model->deployment_id)) {
                $model->deployment_id = 'dep_' . Str::random(16);
            }
        });
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
     * ความสัมพันธ์กับ Cloud Config
     *
     * @return BelongsTo
     */
    public function cloudConfig(): BelongsTo
    {
        return $this->belongsTo(AiRentalCloudConfig::class, 'cloud_config_id');
    }

    /**
     * ตรวจสอบว่ากำลัง running อยู่หรือไม่
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * ตรวจสอบว่าหยุดแล้วหรือไม่
     *
     * @return bool
     */
    public function isStopped(): bool
    {
        return $this->status === 'stopped';
    }

    /**
     * ตรวจสอบว่ามี error หรือไม่
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return $this->status === 'error';
    }

    /**
     * คำนวณ success rate
     *
     * @return float
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_requests === 0) {
            return 0;
        }

        return round(($this->successful_requests / $this->total_requests) * 100, 2);
    }

    /**
     * คำนวณ error rate
     *
     * @return float
     */
    public function getErrorRateAttribute(): float
    {
        if ($this->total_requests === 0) {
            return 0;
        }

        return round(($this->failed_requests / $this->total_requests) * 100, 2);
    }

    /**
     * บันทึก request
     *
     * @param bool $success สำเร็จหรือไม่
     * @param float|null $responseTime เวลาตอบสนอง (ms)
     * @return void
     */
    public function recordRequest(bool $success, ?float $responseTime = null): void
    {
        $this->increment('total_requests');

        if ($success) {
            $this->increment('successful_requests');
        } else {
            $this->increment('failed_requests');
        }

        // คำนวณ average response time
        if ($responseTime !== null) {
            $totalResponseTime = ($this->avg_response_time_ms ?? 0) * ($this->total_requests - 1);
            $this->avg_response_time_ms = ($totalResponseTime + $responseTime) / $this->total_requests;
            $this->save();
        }
    }

    /**
     * บันทึก error
     *
     * @param string $error ข้อความ error
     * @return void
     */
    public function recordError(string $error): void
    {
        $this->last_error = $error;
        $this->last_error_at = now();
        $this->status = 'error';
        $this->save();
    }

    /**
     * เริ่ม deployment
     *
     * @return void
     */
    public function markAsDeploying(): void
    {
        $this->status = 'deploying';
        $this->save();
    }

    /**
     * Deployment สำเร็จ
     *
     * @return void
     */
    public function markAsRunning(): void
    {
        $this->status = 'running';
        $this->deployed_at = now();
        $this->save();

        // อัพเดท cloud config
        $this->cloudConfig->incrementDeployments();
        $this->cloudConfig->recordUsage();
    }

    /**
     * หยุด deployment
     *
     * @return void
     */
    public function markAsStopped(): void
    {
        $this->status = 'stopped';
        $this->stopped_at = now();
        $this->save();

        // คำนวณค่าใช้จ่าย
        if ($this->deployed_at && $this->cost_per_hour) {
            $hours = ceil($this->deployed_at->diffInMinutes($this->stopped_at) / 60);
            $cost = $hours * $this->cost_per_hour;

            $this->total_hours += $hours;
            $this->total_cost += $cost;
            $this->save();

            // อัพเดท cloud config
            $this->cloudConfig->addCost($cost, $hours);
        }
    }

    /**
     * คำนวณเวลาที่ใช้ไป (ชั่วโมง)
     *
     * @return float
     */
    public function getElapsedHoursAttribute(): float
    {
        if (!$this->deployed_at) {
            return 0;
        }

        $end = $this->stopped_at ?? now();
        return round($this->deployed_at->diffInMinutes($end) / 60, 2);
    }

    /**
     * คำนวณค่าใช้จ่ายปัจจุบัน
     *
     * @return float
     */
    public function getCurrentCostAttribute(): float
    {
        if (!$this->cost_per_hour || !$this->isRunning()) {
            return $this->total_cost;
        }

        return $this->total_cost + ($this->elapsed_hours * $this->cost_per_hour);
    }

    /**
     * Scope: เฉพาะ deployments ที่กำลัง running
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope: เฉพาะ deployments ที่หยุดแล้ว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStopped($query)
    {
        return $query->where('status', 'stopped');
    }

    /**
     * Scope: ของ user ที่ระบุ
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
     * Scope: จาก Hugging Face
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFromHuggingFace($query)
    {
        return $query->where('model_source', 'huggingface');
    }
}
