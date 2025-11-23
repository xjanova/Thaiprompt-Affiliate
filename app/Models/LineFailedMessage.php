<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * LINE Failed Message Model
 *
 * เก็บข้อความ LINE ที่ส่งล้มเหลวสำหรับระบบ Auto-Retry
 *
 * @property int $id
 * @property string $line_user_id LINE User ID ของผู้รับ
 * @property string $message_type ประเภทข้อความ
 * @property array $message_payload ข้อมูลข้อความ (JSON)
 * @property string|null $error_type ประเภท error
 * @property string|null $error_message รายละเอียด error
 * @property array|null $error_context ข้อมูลเพิ่มเติมเกี่ยวกับ error
 * @property int $retry_count จำนวนครั้งที่ retry แล้ว
 * @property int $max_retries จำนวนครั้งสูงสุดที่จะ retry
 * @property Carbon|null $next_retry_at เวลาที่จะ retry ครั้งถัดไป
 * @property Carbon|null $last_retry_at เวลาที่ retry ครั้งล่าสุด
 * @property string $status สถานะปัจจุบัน
 * @property Carbon|null $resolved_at เวลาที่ส่งสำเร็จหรือยกเลิก
 * @property array|null $metadata ข้อมูลเพิ่มเติม
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class LineFailedMessage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'line_failed_messages';

    /**
     * สถานะต่างๆ ของข้อความ
     */
    const STATUS_PENDING = 'pending';      // รอการ retry
    const STATUS_RETRYING = 'retrying';    // กำลัง retry
    const STATUS_SUCCEEDED = 'succeeded';  // ส่งสำเร็จแล้ว
    const STATUS_FAILED = 'failed';        // ล้มเหลว (แต่จะ retry ต่อ)
    const STATUS_ABANDONED = 'abandoned';  // ยกเลิกแล้ว (retry เกินจำนวนครั้ง)

    /**
     * ประเภทของ Error
     */
    const ERROR_TYPE_NETWORK = 'network';           // ปัญหาเครือข่าย
    const ERROR_TYPE_RATE_LIMIT = 'rate_limit';     // เกิน rate limit
    const ERROR_TYPE_TIMEOUT = 'timeout';           // Timeout
    const ERROR_TYPE_INVALID_TOKEN = 'invalid_token'; // Token ไม่ถูกต้อง
    const ERROR_TYPE_USER_NOT_FOUND = 'user_not_found'; // ไม่พบ user
    const ERROR_TYPE_BLOCKED = 'blocked';           // User บล็อกบอท
    const ERROR_TYPE_UNKNOWN = 'unknown';           // ไม่ทราบสาเหตุ

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'line_user_id',
        'message_type',
        'message_payload',
        'error_type',
        'error_message',
        'error_context',
        'retry_count',
        'max_retries',
        'next_retry_at',
        'last_retry_at',
        'status',
        'resolved_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'message_payload' => 'array',
        'error_context' => 'array',
        'metadata' => 'array',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
        'next_retry_at' => 'datetime',
        'last_retry_at' => 'datetime',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ LineErrorLog
     *
     * @return HasMany
     */
    public function errorLogs(): HasMany
    {
        return $this->hasMany(LineErrorLog::class, 'failed_message_id');
    }

    /**
     * Scope: ดึงข้อความที่รอการ retry
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePendingRetry($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('retry_count', '<', \DB::raw('max_retries'))
            ->where(function ($q) {
                $q->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', now());
            });
    }

    /**
     * Scope: ดึงข้อความที่ล้มเหลว (รวมถึงที่จะ retry)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('status', [self::STATUS_FAILED, self::STATUS_PENDING]);
    }

    /**
     * Scope: ดึงข้อความที่ยกเลิกแล้ว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAbandoned($query)
    {
        return $query->where('status', self::STATUS_ABANDONED);
    }

    /**
     * Scope: ดึงข้อความของ user คนใดคนหนึ่ง
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $lineUserId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, string $lineUserId)
    {
        return $query->where('line_user_id', $lineUserId);
    }

    /**
     * ตรวจสอบว่าควร retry หรือไม่
     *
     * @return bool
     */
    public function shouldRetry(): bool
    {
        // ถ้าส่งสำเร็จหรือยกเลิกแล้ว ไม่ต้อง retry
        if (in_array($this->status, [self::STATUS_SUCCEEDED, self::STATUS_ABANDONED])) {
            return false;
        }

        // ถ้า retry เกินจำนวนครั้งแล้ว ไม่ต้อง retry
        if ($this->retry_count >= $this->max_retries) {
            return false;
        }

        // ถ้ายังไม่ถึงเวลา retry ไม่ต้อง retry
        if ($this->next_retry_at && $this->next_retry_at->isFuture()) {
            return false;
        }

        return true;
    }

    /**
     * คำนวณเวลา retry ครั้งถัดไป (Exponential Backoff)
     *
     * @return Carbon
     */
    public function calculateNextRetryTime(): Carbon
    {
        // Exponential backoff: 2^retry_count seconds
        // 1st retry: 2s, 2nd: 4s, 3rd: 8s, 4th: 16s, 5th: 32s
        $seconds = min(pow(2, $this->retry_count), 60); // Max 60 seconds

        return now()->addSeconds($seconds);
    }

    /**
     * เพิ่มจำนวนครั้งการ retry
     *
     * @return void
     */
    public function incrementRetryCount(): void
    {
        $this->retry_count++;
        $this->last_retry_at = now();
        $this->next_retry_at = $this->calculateNextRetryTime();
        $this->status = self::STATUS_PENDING;
        $this->save();
    }

    /**
     * ทำเครื่องหมายว่าส่งสำเร็จ
     *
     * @return void
     */
    public function markAsSucceeded(): void
    {
        $this->status = self::STATUS_SUCCEEDED;
        $this->resolved_at = now();
        $this->save();
    }

    /**
     * ทำเครื่องหมายว่ายกเลิก (retry เกินจำนวนครั้ง)
     *
     * @return void
     */
    public function markAsAbandoned(): void
    {
        $this->status = self::STATUS_ABANDONED;
        $this->resolved_at = now();
        $this->save();
    }

    /**
     * บันทึก error ที่เกิดขึ้น
     *
     * @param string $errorType
     * @param string $errorMessage
     * @param array|null $errorContext
     * @return void
     */
    public function recordError(string $errorType, string $errorMessage, ?array $errorContext = null): void
    {
        $this->error_type = $errorType;
        $this->error_message = $errorMessage;
        $this->error_context = $errorContext;
        $this->save();
    }

    /**
     * สร้าง failed message จาก exception
     *
     * @param string $lineUserId
     * @param string $messageType
     * @param array $messagePayload
     * @param \Exception $exception
     * @param int $maxRetries
     * @return self
     */
    public static function createFromException(
        string $lineUserId,
        string $messageType,
        array $messagePayload,
        \Exception $exception,
        int $maxRetries = 5
    ): self {
        return self::create([
            'line_user_id' => $lineUserId,
            'message_type' => $messageType,
            'message_payload' => $messagePayload,
            'error_type' => self::classifyException($exception),
            'error_message' => $exception->getMessage(),
            'error_context' => [
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ],
            'retry_count' => 0,
            'max_retries' => $maxRetries,
            'next_retry_at' => now()->addSeconds(2), // First retry after 2 seconds
            'status' => self::STATUS_PENDING,
        ]);
    }

    /**
     * จำแนกประเภท exception
     *
     * @param \Exception $exception
     * @return string
     */
    protected static function classifyException(\Exception $exception): string
    {
        $message = strtolower($exception->getMessage());

        if (str_contains($message, 'network') || str_contains($message, 'connection')) {
            return self::ERROR_TYPE_NETWORK;
        }

        if (str_contains($message, 'rate limit') || str_contains($message, 'too many requests')) {
            return self::ERROR_TYPE_RATE_LIMIT;
        }

        if (str_contains($message, 'timeout')) {
            return self::ERROR_TYPE_TIMEOUT;
        }

        if (str_contains($message, 'invalid token') || str_contains($message, 'unauthorized')) {
            return self::ERROR_TYPE_INVALID_TOKEN;
        }

        if (str_contains($message, 'user not found') || str_contains($message, '404')) {
            return self::ERROR_TYPE_USER_NOT_FOUND;
        }

        if (str_contains($message, 'blocked')) {
            return self::ERROR_TYPE_BLOCKED;
        }

        return self::ERROR_TYPE_UNKNOWN;
    }
}
