<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiInstallationLog extends Model
{
    protected $fillable = [
        'user_id',
        'installation_type',
        'status',
        'progress_percentage',
        'current_step',
        'total_steps',
        'log_output',
        'error_message',
        'config',
        'started_at',
        'completed_at',
        'failed_at',
    ];

    protected $casts = [
        'config' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Installation statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_DOWNLOADING = 'downloading';
    const STATUS_INSTALLING = 'installing';
    const STATUS_CONFIGURING = 'configuring';
    const STATUS_TESTING = 'testing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Installation types
     */
    const TYPE_DEEPSEEK = 'deepseek';
    const TYPE_OLLAMA = 'ollama';
    const TYPE_CUSTOM = 'custom';

    /**
     * Get the user who initiated the installation
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope: Active installations
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_DOWNLOADING,
            self::STATUS_INSTALLING,
            self::STATUS_CONFIGURING,
            self::STATUS_TESTING,
        ]);
    }

    /**
     * Scope: Completed installations
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: Failed installations
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope: For specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: By installation type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('installation_type', $type);
    }

    /**
     * Update installation progress
     */
    public function updateProgress(string $status, int $percentage, string $step, ?string $logOutput = null): void
    {
        $this->status = $status;
        $this->progress_percentage = min(100, max(0, $percentage));
        $this->current_step = $step;

        if ($logOutput) {
            $this->log_output .= "\n" . $logOutput;
        }

        $this->save();
    }

    /**
     * Mark as completed
     */
    public function markCompleted(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->progress_percentage = 100;
        $this->completed_at = now();
        $this->save();
    }

    /**
     * Mark as failed
     */
    public function markFailed(string $errorMessage): void
    {
        $this->status = self::STATUS_FAILED;
        $this->error_message = $errorMessage;
        $this->failed_at = now();
        $this->save();
    }

    /**
     * Cancel installation
     */
    public function cancel(): void
    {
        $this->status = self::STATUS_CANCELLED;
        $this->save();
    }

    /**
     * Check if installation is in progress
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_DOWNLOADING,
            self::STATUS_INSTALLING,
            self::STATUS_CONFIGURING,
            self::STATUS_TESTING,
        ]);
    }

    /**
     * Check if installation is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if installation failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Get installation duration in seconds
     */
    public function getDuration(): ?int
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? $this->failed_at ?? now();

        return $this->started_at->diffInSeconds($endTime);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDuration(): ?string
    {
        $duration = $this->getDuration();

        if (!$duration) {
            return null;
        }

        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $seconds = $duration % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Get progress status message
     */
    public function getStatusMessage(): string
    {
        $messages = [
            self::STATUS_PENDING => 'รอดำเนินการติดตั้ง',
            self::STATUS_DOWNLOADING => 'กำลังดาวน์โหลด...',
            self::STATUS_INSTALLING => 'กำลังติดตั้ง...',
            self::STATUS_CONFIGURING => 'กำลังตั้งค่า...',
            self::STATUS_TESTING => 'กำลังทดสอบ...',
            self::STATUS_COMPLETED => 'ติดตั้งเสร็จสิ้น',
            self::STATUS_FAILED => 'เกิดข้อผิดพลาด',
            self::STATUS_CANCELLED => 'ยกเลิกการติดตั้ง',
        ];

        return $messages[$this->status] ?? 'ไม่ทราบสถานะ';
    }

    /**
     * Get installation steps based on type
     */
    public function getInstallationSteps(): array
    {
        switch ($this->installation_type) {
            case self::TYPE_DEEPSEEK:
                return [
                    'ตรวจสอบความต้องการของระบบ',
                    'ดาวน์โหลด DeepSeek',
                    'ติดตั้ง dependencies',
                    'ตั้งค่า configuration',
                    'ทดสอบการเชื่อมต่อ',
                    'เสร็จสิ้น',
                ];

            case self::TYPE_OLLAMA:
                return [
                    'ตรวจสอบความต้องการของระบบ',
                    'ดาวน์โหลด Ollama',
                    'ติดตั้ง Ollama',
                    'ดาวน์โหลดโมเดล',
                    'ทดสอบการเชื่อมต่อ',
                    'เสร็จสิ้น',
                ];

            default:
                return [
                    'เริ่มต้นการติดตั้ง',
                    'กำลังดำเนินการ',
                    'เสร็จสิ้น',
                ];
        }
    }

    /**
     * Append to log output
     */
    public function appendLog(string $message): void
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $this->log_output .= "\n[{$timestamp}] {$message}";
        $this->save();
    }
}
