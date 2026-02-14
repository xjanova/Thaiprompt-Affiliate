<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * MlmRebuildTask Model
 *
 * จัดการ task สำหรับ rebuild MLM tree structure
 * รองรับการ resume, progress tracking, และ concurrent execution protection
 *
 * @property int $id
 * @property string $type ประเภท task (unilevel_rebuild, binary_rebuild, etc.)
 * @property string $status สถานะ (pending, running, completed, failed, cancelled)
 * @property int $total_items จำนวนสมาชิกทั้งหมด
 * @property int $processed_items จำนวนที่ประมวลผลแล้ว
 * @property int $failed_items จำนวนที่ล้มเหลว
 * @property float $progress_percentage % ความคืบหน้า
 * @property array|null $config ค่า configuration ที่ใช้ rebuild
 * @property string|null $error_message ข้อความ error
 * @property array|null $error_details รายละเอียด error
 * @property \Carbon\Carbon|null $started_at เวลาเริ่ม
 * @property \Carbon\Carbon|null $completed_at เวลาเสร็จ
 * @property int|null $duration_seconds ระยะเวลาทำงาน (วินาที)
 * @property int|null $initiated_by user_id ที่เริ่ม task
 * @property int|null $last_processed_id ID สุดท้ายที่ประมวลผล (สำหรับ resume)
 * @property string|null $lock_key lock key สำหรับป้องกัน concurrent
 * @property \Carbon\Carbon|null $lock_expires_at เวลา lock หมดอายุ
 */
class MlmRebuildTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'status',
        'total_items',
        'processed_items',
        'failed_items',
        'progress_percentage',
        'config',
        'error_message',
        'error_details',
        'started_at',
        'completed_at',
        'duration_seconds',
        'initiated_by',
        'last_processed_id',
        'lock_key',
        'lock_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'error_details' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'lock_expires_at' => 'datetime',
            'progress_percentage' => 'decimal:2',
        ];
    }

    // =====================================
    // Constants
    // =====================================

    const TYPE_UNILEVEL_REBUILD = 'unilevel_rebuild';

    const TYPE_BINARY_REBUILD = 'binary_rebuild';

    const TYPE_FULL_RECALCULATE = 'full_recalculate';

    const STATUS_PENDING = 'pending';

    const STATUS_RUNNING = 'running';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';

    const STATUS_CANCELLED = 'cancelled';

    // Lock timeout in minutes
    const LOCK_TIMEOUT_MINUTES = 30;

    // =====================================
    // Relationships
    // =====================================

    /**
     * ผู้ที่เริ่ม task
     */
    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    // =====================================
    // Scopes
    // =====================================

    /**
     * Task ที่กำลังทำงาน
     */
    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    /**
     * Task ที่รอดำเนินการ
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Task ที่เสร็จสมบูรณ์
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Task ตามประเภท
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // =====================================
    // Static Methods
    // =====================================

    /**
     * ตรวจสอบว่ามี task กำลังทำงานอยู่หรือไม่
     */
    public static function hasRunningTask(string $type): bool
    {
        return self::where('type', $type)
            ->where('status', self::STATUS_RUNNING)
            ->where(function ($query) {
                $query->whereNull('lock_expires_at')
                    ->orWhere('lock_expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * ดึง task ล่าสุดของประเภทที่ระบุ
     */
    public static function getLatestTask(string $type): ?self
    {
        return self::where('type', $type)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * สร้าง task ใหม่พร้อม lock
     *
     * @return self|null null ถ้ามี task running อยู่
     */
    public static function createWithLock(string $type, array $config, ?int $userId = null): ?self
    {
        // ตรวจสอบว่ามี running task อยู่ไหม
        if (self::hasRunningTask($type)) {
            return null;
        }

        $lockKey = $type.'_'.uniqid();

        return self::create([
            'type' => $type,
            'status' => self::STATUS_PENDING,
            'config' => $config,
            'initiated_by' => $userId,
            'lock_key' => $lockKey,
            'lock_expires_at' => now()->addMinutes(self::LOCK_TIMEOUT_MINUTES),
        ]);
    }

    // =====================================
    // Instance Methods
    // =====================================

    /**
     * เริ่มต้น task
     */
    public function start(int $totalItems): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_RUNNING,
            'total_items' => $totalItems,
            'processed_items' => 0,
            'failed_items' => 0,
            'progress_percentage' => 0,
            'started_at' => now(),
            'lock_expires_at' => now()->addMinutes(self::LOCK_TIMEOUT_MINUTES),
        ]);

        return true;
    }

    /**
     * อัพเดท progress
     */
    public function updateProgress(int $processedItems, int $failedItems = 0, ?int $lastProcessedId = null): void
    {
        $percentage = $this->total_items > 0
            ? round(($processedItems / $this->total_items) * 100, 2)
            : 0;

        $this->update([
            'processed_items' => $processedItems,
            'failed_items' => $failedItems,
            'progress_percentage' => min($percentage, 100),
            'last_processed_id' => $lastProcessedId,
            // ต่ออายุ lock
            'lock_expires_at' => now()->addMinutes(self::LOCK_TIMEOUT_MINUTES),
        ]);
    }

    /**
     * ทำเครื่องหมายว่าเสร็จสมบูรณ์
     */
    public function markCompleted(): void
    {
        $duration = $this->started_at ? now()->diffInSeconds($this->started_at) : null;

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'progress_percentage' => 100,
            'completed_at' => now(),
            'duration_seconds' => $duration,
            'lock_key' => null,
            'lock_expires_at' => null,
        ]);
    }

    /**
     * ทำเครื่องหมายว่าล้มเหลว
     */
    public function markFailed(string $message, ?array $details = null): void
    {
        $duration = $this->started_at ? now()->diffInSeconds($this->started_at) : null;

        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $message,
            'error_details' => $details,
            'completed_at' => now(),
            'duration_seconds' => $duration,
            'lock_key' => null,
            'lock_expires_at' => null,
        ]);
    }

    /**
     * ยกเลิก task
     */
    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'completed_at' => now(),
            'lock_key' => null,
            'lock_expires_at' => null,
        ]);
    }

    /**
     * ต่ออายุ lock
     */
    public function renewLock(): void
    {
        $this->update([
            'lock_expires_at' => now()->addMinutes(self::LOCK_TIMEOUT_MINUTES),
        ]);
    }

    /**
     * ตรวจสอบว่า lock หมดอายุหรือไม่
     */
    public function isLockExpired(): bool
    {
        return $this->lock_expires_at && $this->lock_expires_at->isPast();
    }

    /**
     * ตรวจสอบว่ากำลังทำงานอยู่หรือไม่
     */
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING && ! $this->isLockExpired();
    }

    /**
     * ดึงข้อมูลสำหรับแสดง progress
     */
    public function getProgressData(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'total_items' => $this->total_items,
            'processed_items' => $this->processed_items,
            'failed_items' => $this->failed_items,
            'progress_percentage' => $this->progress_percentage,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'duration_seconds' => $this->duration_seconds,
            'error_message' => $this->error_message,
            'is_running' => $this->isRunning(),
            'can_resume' => $this->status === self::STATUS_FAILED && $this->last_processed_id !== null,
        ];
    }
}
