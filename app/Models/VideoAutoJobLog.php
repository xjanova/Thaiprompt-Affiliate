<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * VideoAutoJobLog Model
 *
 * Log การทำงานของ Jobs ในระบบ Video Automation
 *
 * @property int $id
 * @property int $job_id Job ที่เกี่ยวข้อง
 * @property int|null $project_id โปรเจกต์
 * @property string $level ระดับ log
 * @property string|null $step ขั้นตอน
 * @property string $message ข้อความ
 * @property array|null $context ข้อมูลเพิ่มเติม
 * @property string|null $api_service API service
 * @property string|null $api_endpoint Endpoint
 * @property string|null $api_method HTTP Method
 * @property int|null $api_status_code Status Code
 * @property int|null $api_response_time Response time (ms)
 * @property array|null $api_request Request data
 * @property array|null $api_response Response data
 * @property int|null $memory_usage Memory (bytes)
 * @property float|null $cpu_usage CPU %
 * @property \Carbon\Carbon $logged_at เวลา log
 */
class VideoAutoJobLog extends Model
{
    /**
     * ไม่ใช้ timestamps แบบปกติ
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'video_auto_job_logs';

    /**
     * คอลัมน์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'job_id',
        'project_id',
        'level',
        'step',
        'message',
        'context',
        'api_service',
        'api_endpoint',
        'api_method',
        'api_status_code',
        'api_response_time',
        'api_request',
        'api_response',
        'memory_usage',
        'cpu_usage',
        'logged_at',
    ];

    /**
     * การแปลงประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'context' => 'array',
        'api_request' => 'array',
        'api_response' => 'array',
        'api_status_code' => 'integer',
        'api_response_time' => 'integer',
        'memory_usage' => 'integer',
        'cpu_usage' => 'decimal:2',
        'logged_at' => 'datetime',
    ];

    /**
     * ระดับ log ที่รองรับ
     *
     * @var array<string, array>
     */
    public const LEVELS = [
        'debug' => [
            'label' => 'Debug',
            'color' => 'gray',
            'icon' => 'fa-bug',
        ],
        'info' => [
            'label' => 'Info',
            'color' => 'blue',
            'icon' => 'fa-info-circle',
        ],
        'notice' => [
            'label' => 'Notice',
            'color' => 'blue',
            'icon' => 'fa-bell',
        ],
        'warning' => [
            'label' => 'Warning',
            'color' => 'yellow',
            'icon' => 'fa-exclamation-triangle',
        ],
        'error' => [
            'label' => 'Error',
            'color' => 'red',
            'icon' => 'fa-times-circle',
        ],
        'critical' => [
            'label' => 'Critical',
            'color' => 'red',
            'icon' => 'fa-fire',
        ],
        'alert' => [
            'label' => 'Alert',
            'color' => 'orange',
            'icon' => 'fa-bell',
        ],
        'emergency' => [
            'label' => 'Emergency',
            'color' => 'red',
            'icon' => 'fa-skull',
        ],
    ];

    // =========================================
    // Boot
    // =========================================

    /**
     * Boot the model
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($log) {
            if (empty($log->logged_at)) {
                $log->logged_at = now();
            }

            // บันทึก memory usage
            if (empty($log->memory_usage)) {
                $log->memory_usage = memory_get_usage(true);
            }
        });
    }

    // =========================================
    // Relationships
    // =========================================

    /**
     * Job ที่เกี่ยวข้อง
     *
     * @return BelongsTo
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(VideoAutoJob::class, 'job_id');
    }

    /**
     * โปรเจกต์ที่เกี่ยวข้อง
     *
     * @return BelongsTo
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(VideoAutoProject::class, 'project_id');
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * Scope: กรองตาม level
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|array $level
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLevel($query, $level)
    {
        if (is_array($level)) {
            return $query->whereIn('level', $level);
        }

        return $query->where('level', $level);
    }

    /**
     * Scope: กรองเฉพาะ errors
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeErrors($query)
    {
        return $query->whereIn('level', ['error', 'critical', 'alert', 'emergency']);
    }

    /**
     * Scope: กรองเฉพาะ API calls
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApiCalls($query)
    {
        return $query->whereNotNull('api_service');
    }

    /**
     * Scope: กรองตาม API service
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $service
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForService($query, string $service)
    {
        return $query->where('api_service', $service);
    }

    /**
     * Scope: เรียงตามเวลาล่าสุด
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatest($query)
    {
        return $query->orderByDesc('logged_at');
    }

    /**
     * Scope: กรองตามช่วงเวลา
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Carbon\Carbon $from
     * @param \Carbon\Carbon|null $to
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBetween($query, $from, $to = null)
    {
        $query->where('logged_at', '>=', $from);

        if ($to) {
            $query->where('logged_at', '<=', $to);
        }

        return $query;
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * ดึงข้อมูล level
     *
     * @return array|null
     */
    public function getLevelInfoAttribute(): ?array
    {
        return self::LEVELS[$this->level] ?? null;
    }

    /**
     * ดึง label ของ level
     *
     * @return string
     */
    public function getLevelLabelAttribute(): string
    {
        return self::LEVELS[$this->level]['label'] ?? $this->level;
    }

    /**
     * ดึงสีของ level
     *
     * @return string
     */
    public function getLevelColorAttribute(): string
    {
        return self::LEVELS[$this->level]['color'] ?? 'gray';
    }

    /**
     * ดึง icon ของ level
     *
     * @return string
     */
    public function getLevelIconAttribute(): string
    {
        return self::LEVELS[$this->level]['icon'] ?? 'fa-circle';
    }

    /**
     * ตรวจสอบว่าเป็น error หรือไม่
     *
     * @return bool
     */
    public function getIsErrorAttribute(): bool
    {
        return in_array($this->level, ['error', 'critical', 'alert', 'emergency']);
    }

    /**
     * ตรวจสอบว่าเป็น API call หรือไม่
     *
     * @return bool
     */
    public function getIsApiCallAttribute(): bool
    {
        return !empty($this->api_service);
    }

    /**
     * คำนวณ memory แบบ human readable
     *
     * @return string|null
     */
    public function getMemoryUsageHumanAttribute(): ?string
    {
        if (!$this->memory_usage) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->memory_usage;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * คำนวณ API response time แบบ human readable
     *
     * @return string|null
     */
    public function getApiResponseTimeHumanAttribute(): ?string
    {
        if (!$this->api_response_time) {
            return null;
        }

        if ($this->api_response_time >= 1000) {
            return round($this->api_response_time / 1000, 2) . ' วินาที';
        }

        return $this->api_response_time . ' ms';
    }

    /**
     * ตรวจสอบว่า API call สำเร็จหรือไม่
     *
     * @return bool|null
     */
    public function getApiSuccessAttribute(): ?bool
    {
        if (!$this->api_status_code) {
            return null;
        }

        return $this->api_status_code >= 200 && $this->api_status_code < 400;
    }

    // =========================================
    // Static Methods
    // =========================================

    /**
     * สร้าง log ใหม่
     *
     * @param int $jobId
     * @param string $level
     * @param string $message
     * @param array $options
     * @return static
     */
    public static function make(int $jobId, string $level, string $message, array $options = []): static
    {
        return static::create(array_merge([
            'job_id' => $jobId,
            'level' => $level,
            'message' => $message,
        ], $options));
    }

    /**
     * สร้าง info log
     *
     * @param int $jobId
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function info(int $jobId, string $message, array $context = []): static
    {
        return static::make($jobId, 'info', $message, ['context' => $context]);
    }

    /**
     * สร้าง error log
     *
     * @param int $jobId
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function error(int $jobId, string $message, array $context = []): static
    {
        return static::make($jobId, 'error', $message, ['context' => $context]);
    }

    /**
     * ดึง summary ของ logs
     *
     * @param int $jobId
     * @return array
     */
    public static function getSummary(int $jobId): array
    {
        return static::where('job_id', $jobId)
            ->selectRaw('level, COUNT(*) as count')
            ->groupBy('level')
            ->pluck('count', 'level')
            ->toArray();
    }

    /**
     * ลบ logs เก่า
     *
     * @param int $days
     * @return int
     */
    public static function pruneOld(int $days = 30): int
    {
        return static::where('logged_at', '<', now()->subDays($days))->delete();
    }
}
