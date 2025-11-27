<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * VideoAutoJob Model
 *
 * งานที่รันในระบบ Video Automation
 *
 * @property int $id
 * @property string $uuid UUID
 * @property int $project_id โปรเจกต์
 * @property string $job_type ประเภทงาน
 * @property string $status สถานะ
 * @property string|null $queue_job_id Laravel Queue Job ID
 * @property string $queue_name ชื่อ queue
 * @property int $priority ความสำคัญ
 * @property int $progress ความคืบหน้า
 * @property string|null $current_step ขั้นตอนปัจจุบัน
 * @property string|null $progress_message ข้อความ
 * @property array|null $input_data Input
 * @property array|null $output_data Output
 * @property \Carbon\Carbon|null $queued_at เวลา queue
 * @property \Carbon\Carbon|null $started_at เวลาเริ่ม
 * @property \Carbon\Carbon|null $completed_at เวลาเสร็จ
 * @property int|null $duration เวลาทำงาน
 * @property int|null $estimated_duration เวลาที่คาดว่าใช้
 * @property string|null $error_message Error
 * @property string|null $error_stack_trace Stack trace
 * @property int $retry_count จำนวน retry
 * @property int $max_retries retry สูงสุด
 * @property \Carbon\Carbon|null $next_retry_at retry ครั้งต่อไป
 * @property array|null $resource_usage การใช้ทรัพยากร
 * @property float $api_cost ค่าใช้จ่าย API
 * @property int|null $triggered_by ผู้สั่งรัน
 * @property string $trigger_source แหล่งที่มา
 */
class VideoAutoJob extends Model
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'video_auto_jobs';

    /**
     * คอลัมน์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'uuid',
        'project_id',
        'job_type',
        'status',
        'queue_job_id',
        'queue_name',
        'priority',
        'progress',
        'current_step',
        'progress_message',
        'input_data',
        'output_data',
        'queued_at',
        'started_at',
        'completed_at',
        'duration',
        'estimated_duration',
        'error_message',
        'error_stack_trace',
        'retry_count',
        'max_retries',
        'next_retry_at',
        'resource_usage',
        'api_cost',
        'triggered_by',
        'trigger_source',
    ];

    /**
     * การแปลงประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'input_data' => 'array',
        'output_data' => 'array',
        'resource_usage' => 'array',
        'queued_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'priority' => 'integer',
        'progress' => 'integer',
        'duration' => 'integer',
        'estimated_duration' => 'integer',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
        'api_cost' => 'decimal:4',
    ];

    /**
     * ประเภทงานที่รองรับ
     *
     * @var array<string, array>
     */
    public const JOB_TYPES = [
        'generate_music' => [
            'label' => 'สร้างเพลง',
            'icon' => 'fa-music',
            'color' => 'purple',
            'estimated_duration' => 120, // 2 นาที
        ],
        'generate_images' => [
            'label' => 'สร้างภาพ',
            'icon' => 'fa-images',
            'color' => 'blue',
            'estimated_duration' => 180, // 3 นาที
        ],
        'create_video' => [
            'label' => 'สร้างวีดีโอ',
            'icon' => 'fa-video',
            'color' => 'green',
            'estimated_duration' => 300, // 5 นาที
        ],
        'upload_youtube' => [
            'label' => 'อัปโหลด YouTube',
            'icon' => 'fab fa-youtube',
            'color' => 'red',
            'estimated_duration' => 120,
        ],
        'publish_facebook' => [
            'label' => 'โพส Facebook',
            'icon' => 'fab fa-facebook',
            'color' => 'blue',
            'estimated_duration' => 60,
        ],
        'publish_instagram' => [
            'label' => 'โพส Instagram',
            'icon' => 'fab fa-instagram',
            'color' => 'pink',
            'estimated_duration' => 60,
        ],
        'publish_tiktok' => [
            'label' => 'โพส TikTok',
            'icon' => 'fab fa-tiktok',
            'color' => 'black',
            'estimated_duration' => 60,
        ],
        'publish_twitter' => [
            'label' => 'โพส Twitter/X',
            'icon' => 'fab fa-x-twitter',
            'color' => 'black',
            'estimated_duration' => 30,
        ],
        'publish_threads' => [
            'label' => 'โพส Threads',
            'icon' => 'fab fa-threads',
            'color' => 'black',
            'estimated_duration' => 30,
        ],
        'full_pipeline' => [
            'label' => 'ทำทั้งหมด',
            'icon' => 'fa-magic',
            'color' => 'purple',
            'estimated_duration' => 600, // 10 นาที
        ],
    ];

    /**
     * สถานะที่เป็นไปได้
     *
     * @var array<string, array>
     */
    public const STATUSES = [
        'pending' => [
            'label' => 'รอดำเนินการ',
            'color' => 'gray',
            'icon' => 'fa-clock',
        ],
        'queued' => [
            'label' => 'อยู่ใน Queue',
            'color' => 'yellow',
            'icon' => 'fa-list',
        ],
        'running' => [
            'label' => 'กำลังทำงาน',
            'color' => 'blue',
            'icon' => 'fa-spinner fa-spin',
        ],
        'completed' => [
            'label' => 'สำเร็จ',
            'color' => 'green',
            'icon' => 'fa-check',
        ],
        'failed' => [
            'label' => 'ล้มเหลว',
            'color' => 'red',
            'icon' => 'fa-times',
        ],
        'cancelled' => [
            'label' => 'ยกเลิก',
            'color' => 'gray',
            'icon' => 'fa-ban',
        ],
        'retrying' => [
            'label' => 'กำลัง Retry',
            'color' => 'orange',
            'icon' => 'fa-redo',
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

        static::creating(function ($job) {
            if (empty($job->uuid)) {
                $job->uuid = (string) Str::uuid();
            }

            // ตั้งค่า estimated_duration จาก job type
            if (empty($job->estimated_duration)) {
                $job->estimated_duration = self::JOB_TYPES[$job->job_type]['estimated_duration'] ?? 60;
            }
        });
    }

    // =========================================
    // Relationships
    // =========================================

    /**
     * โปรเจกต์ที่เกี่ยวข้อง
     *
     * @return BelongsTo
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(VideoAutoProject::class, 'project_id');
    }

    /**
     * ผู้สั่งรัน
     *
     * @return BelongsTo
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    /**
     * Logs ของ job นี้
     *
     * @return HasMany
     */
    public function logs(): HasMany
    {
        return $this->hasMany(VideoAutoJobLog::class, 'job_id');
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * Scope: กรองตาม status
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|array $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatus($query, $status)
    {
        if (is_array($status)) {
            return $query->whereIn('status', $status);
        }

        return $query->where('status', $status);
    }

    /**
     * Scope: กรองตาม job type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('job_type', $type);
    }

    /**
     * Scope: งานที่รอ retry
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReadyToRetry($query)
    {
        return $query->where('status', 'failed')
            ->whereColumn('retry_count', '<', 'max_retries')
            ->where(function ($q) {
                $q->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', now());
            });
    }

    /**
     * Scope: งานที่กำลังทำงาน
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRunning($query)
    {
        return $query->whereIn('status', ['running', 'queued', 'retrying']);
    }

    /**
     * Scope: เรียงตาม priority
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderByDesc('priority')->orderBy('created_at');
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * ดึงข้อมูล job type
     *
     * @return array|null
     */
    public function getJobTypeInfoAttribute(): ?array
    {
        return self::JOB_TYPES[$this->job_type] ?? null;
    }

    /**
     * ดึง label ของ job type
     *
     * @return string
     */
    public function getJobTypeLabelAttribute(): string
    {
        return self::JOB_TYPES[$this->job_type]['label'] ?? $this->job_type;
    }

    /**
     * ดึงข้อมูล status
     *
     * @return array|null
     */
    public function getStatusInfoAttribute(): ?array
    {
        return self::STATUSES[$this->status] ?? null;
    }

    /**
     * ดึง label ของ status
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status]['label'] ?? $this->status;
    }

    /**
     * ตรวจสอบว่าสามารถ retry ได้หรือไม่
     *
     * @return bool
     */
    public function getCanRetryAttribute(): bool
    {
        return $this->status === 'failed' && $this->retry_count < $this->max_retries;
    }

    /**
     * ตรวจสอบว่ากำลังทำงานอยู่หรือไม่
     *
     * @return bool
     */
    public function getIsRunningAttribute(): bool
    {
        return in_array($this->status, ['running', 'queued', 'retrying']);
    }

    /**
     * คำนวณเวลาทำงานแบบ human readable
     *
     * @return string|null
     */
    public function getDurationHumanAttribute(): ?string
    {
        if (!$this->duration) {
            return null;
        }

        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;

        if ($minutes > 0) {
            return "{$minutes}:{$seconds} นาที";
        }

        return "{$seconds} วินาที";
    }

    /**
     * คำนวณเวลาที่เหลือโดยประมาณ
     *
     * @return int|null
     */
    public function getRemainingTimeAttribute(): ?int
    {
        if (!$this->is_running || !$this->estimated_duration) {
            return null;
        }

        if (!$this->started_at) {
            return $this->estimated_duration;
        }

        $elapsed = now()->diffInSeconds($this->started_at);
        $remaining = $this->estimated_duration - $elapsed;

        return max(0, $remaining);
    }

    // =========================================
    // Methods
    // =========================================

    /**
     * เริ่มทำงาน
     *
     * @return void
     */
    public function start(): void
    {
        $this->status = 'running';
        $this->started_at = now();
        $this->progress = 0;
        $this->save();

        // อัพเดท project status
        $this->project?->updateStatus('generating', 'initializing');
    }

    /**
     * อัพเดท progress
     *
     * @param int $progress
     * @param string|null $message
     * @return void
     */
    public function updateProgress(int $progress, ?string $message = null): void
    {
        $this->progress = min(100, max(0, $progress));

        if ($message) {
            $this->progress_message = $message;
        }

        $this->save();
    }

    /**
     * ทำงานสำเร็จ
     *
     * @param array $outputData
     * @return void
     */
    public function complete(array $outputData = []): void
    {
        $this->status = 'completed';
        $this->completed_at = now();
        $this->progress = 100;
        $this->output_data = $outputData;

        if ($this->started_at) {
            $this->duration = $this->completed_at->diffInSeconds($this->started_at);
        }

        $this->save();
    }

    /**
     * ทำงานล้มเหลว
     *
     * @param string $error
     * @param string|null $stackTrace
     * @return void
     */
    public function fail(string $error, ?string $stackTrace = null): void
    {
        $this->status = 'failed';
        $this->completed_at = now();
        $this->error_message = $error;
        $this->error_stack_trace = $stackTrace;

        if ($this->started_at) {
            $this->duration = $this->completed_at->diffInSeconds($this->started_at);
        }

        // ตั้งเวลา retry
        if ($this->can_retry) {
            $delay = pow(2, $this->retry_count) * 60; // exponential backoff: 1, 2, 4, 8 นาที
            $this->next_retry_at = now()->addSeconds($delay);
        }

        $this->save();

        // อัพเดท project
        $this->project?->recordError($error);
    }

    /**
     * Retry การทำงาน
     *
     * @return bool
     */
    public function retry(): bool
    {
        if (!$this->can_retry) {
            return false;
        }

        $this->increment('retry_count');
        $this->status = 'retrying';
        $this->error_message = null;
        $this->error_stack_trace = null;
        $this->progress = 0;
        $this->progress_message = null;
        $this->started_at = null;
        $this->completed_at = null;
        $this->duration = null;
        $this->next_retry_at = null;
        $this->save();

        return true;
    }

    /**
     * ยกเลิกงาน
     *
     * @return void
     */
    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->completed_at = now();
        $this->save();
    }

    /**
     * บันทึก log
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return VideoAutoJobLog
     */
    public function log(string $level, string $message, array $context = []): VideoAutoJobLog
    {
        return $this->logs()->create([
            'project_id' => $this->project_id,
            'level' => $level,
            'step' => $this->current_step,
            'message' => $message,
            'context' => $context,
        ]);
    }

    /**
     * บันทึก log แบบ info
     *
     * @param string $message
     * @param array $context
     * @return VideoAutoJobLog
     */
    public function logInfo(string $message, array $context = []): VideoAutoJobLog
    {
        return $this->log('info', $message, $context);
    }

    /**
     * บันทึก log แบบ error
     *
     * @param string $message
     * @param array $context
     * @return VideoAutoJobLog
     */
    public function logError(string $message, array $context = []): VideoAutoJobLog
    {
        return $this->log('error', $message, $context);
    }

    /**
     * บันทึก API call
     *
     * @param string $service
     * @param string $endpoint
     * @param string $method
     * @param int $statusCode
     * @param int $responseTime
     * @param array $request
     * @param array $response
     * @return VideoAutoJobLog
     */
    public function logApiCall(
        string $service,
        string $endpoint,
        string $method,
        int $statusCode,
        int $responseTime,
        array $request = [],
        array $response = []
    ): VideoAutoJobLog {
        return $this->logs()->create([
            'project_id' => $this->project_id,
            'level' => $statusCode >= 400 ? 'error' : 'info',
            'step' => $this->current_step,
            'message' => "API Call: {$service} {$method} {$endpoint}",
            'api_service' => $service,
            'api_endpoint' => $endpoint,
            'api_method' => $method,
            'api_status_code' => $statusCode,
            'api_response_time' => $responseTime,
            'api_request' => $this->sanitizeForLog($request),
            'api_response' => $this->sanitizeForLog($response),
        ]);
    }

    /**
     * ลบข้อมูลที่ไม่ควร log
     *
     * @param array $data
     * @return array
     */
    protected function sanitizeForLog(array $data): array
    {
        $sensitiveKeys = ['password', 'token', 'secret', 'key', 'authorization'];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeForLog($value);
            } elseif (is_string($key)) {
                foreach ($sensitiveKeys as $sensitive) {
                    if (stripos($key, $sensitive) !== false) {
                        $data[$key] = '***REDACTED***';
                        break;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * เพิ่มค่าใช้จ่าย API
     *
     * @param float $cost
     * @return void
     */
    public function addApiCost(float $cost): void
    {
        $this->increment('api_cost', $cost);
    }
}
