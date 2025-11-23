<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * LINE Error Log Model
 *
 * บันทึก Error ทั้งหมดของระบบ LINE สำหรับ Analytics และ Monitoring
 *
 * @property int $id
 * @property string|null $error_code รหัส error
 * @property string $error_type ประเภท error
 * @property string $error_message ข้อความ error
 * @property array|null $context ข้อมูลบริบท
 * @property string|null $stack_trace Stack trace
 * @property bool $is_recovered แก้ไขตัวเองได้หรือไม่
 * @property string|null $recovery_method วิธีที่ใช้แก้ไข
 * @property string|null $recovery_details รายละเอียดการแก้ไข
 * @property Carbon $occurred_at เวลาที่เกิด error
 * @property Carbon|null $resolved_at เวลาที่แก้ไขแล้ว
 * @property int|null $recovery_time_seconds เวลาที่ใช้ในการแก้ไข (วินาที)
 * @property string $severity ระดับความรุนแรง
 * @property int|null $failed_message_id อ้างอิงไปยัง line_failed_messages
 * @property string|null $line_user_id LINE User ID ที่เกี่ยวข้อง
 * @property array|null $metadata ข้อมูลเพิ่มเติม
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class LineErrorLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'line_error_logs';

    /**
     * ระดับความรุนแรงของ Error
     */
    const SEVERITY_LOW = 'low';           // ไม่ร้ายแรง, ไม่กระทบการใช้งาน
    const SEVERITY_MEDIUM = 'medium';     // ปานกลาง, กระทบบางส่วน
    const SEVERITY_HIGH = 'high';         // ร้ายแรง, กระทบการใช้งาน
    const SEVERITY_CRITICAL = 'critical'; // วิกฤต, ระบบใช้งานไม่ได้

    /**
     * วิธีการแก้ไข Error
     */
    const RECOVERY_AUTO_RETRY = 'auto_retry';         // Retry อัตโนมัติ
    const RECOVERY_CIRCUIT_BREAKER = 'circuit_breaker'; // Circuit breaker
    const RECOVERY_FALLBACK = 'fallback';             // ใช้ fallback mechanism
    const RECOVERY_MANUAL = 'manual';                 // แก้ไขด้วยมือ
    const RECOVERY_IGNORED = 'ignored';               // เพิกเฉย

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'error_code',
        'error_type',
        'error_message',
        'context',
        'stack_trace',
        'is_recovered',
        'recovery_method',
        'recovery_details',
        'occurred_at',
        'resolved_at',
        'recovery_time_seconds',
        'severity',
        'failed_message_id',
        'line_user_id',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'context' => 'array',
        'metadata' => 'array',
        'is_recovered' => 'boolean',
        'recovery_time_seconds' => 'integer',
        'failed_message_id' => 'integer',
        'occurred_at' => 'datetime',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ LineFailedMessage
     *
     * @return BelongsTo
     */
    public function failedMessage(): BelongsTo
    {
        return $this->belongsTo(LineFailedMessage::class, 'failed_message_id');
    }

    /**
     * Scope: ดึง errors ที่ยังไม่ได้แก้ไข
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnresolved($query)
    {
        return $query->where('is_recovered', false)
            ->whereNull('resolved_at');
    }

    /**
     * Scope: ดึง errors ที่แก้ไขแล้ว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeResolved($query)
    {
        return $query->where('is_recovered', true)
            ->whereNotNull('resolved_at');
    }

    /**
     * Scope: ดึง errors ตามระดับความรุนแรง
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $severity
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope: ดึง errors ตามประเภท
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $errorType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $errorType)
    {
        return $query->where('error_type', $errorType);
    }

    /**
     * Scope: ดึง errors ที่เกิดขึ้นในช่วงเวลาที่กำหนด
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Carbon $start
     * @param Carbon|null $end
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOccurredBetween($query, Carbon $start, ?Carbon $end = null)
    {
        $query->where('occurred_at', '>=', $start);

        if ($end) {
            $query->where('occurred_at', '<=', $end);
        }

        return $query;
    }

    /**
     * ทำเครื่องหมายว่าแก้ไขแล้ว
     *
     * @param string $recoveryMethod
     * @param string|null $recoveryDetails
     * @return void
     */
    public function markAsResolved(string $recoveryMethod, ?string $recoveryDetails = null): void
    {
        $this->is_recovered = true;
        $this->recovery_method = $recoveryMethod;
        $this->recovery_details = $recoveryDetails;
        $this->resolved_at = now();

        // คำนวณเวลาที่ใช้ในการแก้ไข
        $this->recovery_time_seconds = $this->occurred_at->diffInSeconds($this->resolved_at);

        $this->save();
    }

    /**
     * สร้าง error log จาก exception
     *
     * @param \Exception $exception
     * @param string $errorType
     * @param string $severity
     * @param array|null $context
     * @param int|null $failedMessageId
     * @param string|null $lineUserId
     * @return self
     */
    public static function createFromException(
        \Exception $exception,
        string $errorType,
        string $severity = self::SEVERITY_MEDIUM,
        ?array $context = null,
        ?int $failedMessageId = null,
        ?string $lineUserId = null
    ): self {
        return self::create([
            'error_code' => $exception->getCode() ?: null,
            'error_type' => $errorType,
            'error_message' => $exception->getMessage(),
            'context' => array_merge($context ?? [], [
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]),
            'stack_trace' => $exception->getTraceAsString(),
            'is_recovered' => false,
            'occurred_at' => now(),
            'severity' => $severity,
            'failed_message_id' => $failedMessageId,
            'line_user_id' => $lineUserId,
        ]);
    }

    /**
     * สร้าง error log แบบง่าย
     *
     * @param string $errorType
     * @param string $errorMessage
     * @param string $severity
     * @param array|null $context
     * @return self
     */
    public static function log(
        string $errorType,
        string $errorMessage,
        string $severity = self::SEVERITY_MEDIUM,
        ?array $context = null
    ): self {
        return self::create([
            'error_type' => $errorType,
            'error_message' => $errorMessage,
            'context' => $context,
            'is_recovered' => false,
            'occurred_at' => now(),
            'severity' => $severity,
        ]);
    }

    /**
     * ดึง error statistics
     *
     * @param int $days จำนวนวันย้อนหลัง
     * @return array
     */
    public static function getStatistics(int $days = 7): array
    {
        $startDate = now()->subDays($days);

        $total = self::where('occurred_at', '>=', $startDate)->count();
        $resolved = self::where('occurred_at', '>=', $startDate)
            ->where('is_recovered', true)
            ->count();
        $unresolved = $total - $resolved;

        $byType = self::where('occurred_at', '>=', $startDate)
            ->selectRaw('error_type, count(*) as count')
            ->groupBy('error_type')
            ->orderByDesc('count')
            ->get()
            ->pluck('count', 'error_type')
            ->toArray();

        $bySeverity = self::where('occurred_at', '>=', $startDate)
            ->selectRaw('severity, count(*) as count')
            ->groupBy('severity')
            ->get()
            ->pluck('count', 'severity')
            ->toArray();

        $avgRecoveryTime = self::where('occurred_at', '>=', $startDate)
            ->where('is_recovered', true)
            ->whereNotNull('recovery_time_seconds')
            ->avg('recovery_time_seconds');

        return [
            'total' => $total,
            'resolved' => $resolved,
            'unresolved' => $unresolved,
            'resolution_rate' => $total > 0 ? round(($resolved / $total) * 100, 2) : 0,
            'by_type' => $byType,
            'by_severity' => $bySeverity,
            'avg_recovery_time_seconds' => round($avgRecoveryTime ?? 0, 2),
        ];
    }
}
