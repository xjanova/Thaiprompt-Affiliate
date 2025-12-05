<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PushNotificationDelivery Model
 *
 * ติดตามการส่ง Push Notification ไปยังแต่ละเครื่อง
 * รองรับการ retry เมื่อเครื่องกลับมา online
 *
 * @property int $id
 * @property int $notification_id รหัส notification
 * @property int $device_id รหัสเครื่อง
 * @property string $status สถานะ (pending, sent, delivered, failed, retry_pending)
 * @property int $attempt_count จำนวนครั้งที่พยายามส่ง
 * @property \Carbon\Carbon|null $sent_at เวลาที่ส่ง
 * @property \Carbon\Carbon|null $delivered_at เวลาที่ส่งถึง
 * @property string|null $error_message ข้อความ error
 * @property \Carbon\Carbon|null $next_retry_at เวลาที่จะ retry ถัดไป
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PushNotificationDelivery extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'push_notification_deliveries';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'notification_id',
        'device_id',
        'status',
        'attempt_count',
        'sent_at',
        'delivered_at',
        'error_message',
        'next_retry_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'attempt_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The model's default values.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'pending',
        'attempt_count' => 0,
    ];

    /**
     * สถานะที่เป็นไปได้
     */
    const STATUS_PENDING = 'pending';           // รอส่ง
    const STATUS_SENT = 'sent';                 // ส่งไปยัง FCM/APNs แล้ว
    const STATUS_DELIVERED = 'delivered';       // ส่งถึงเครื่องแล้ว
    const STATUS_FAILED = 'failed';             // ล้มเหลวถาวร (หลัง retry หมดแล้ว)
    const STATUS_RETRY_PENDING = 'retry_pending'; // รอ retry (เครื่องอาจ offline)

    /**
     * จำนวนครั้งสูงสุดที่จะ retry
     */
    const MAX_RETRY_ATTEMPTS = 5;

    /**
     * ความสัมพันธ์กับ MobilePushNotification
     *
     * @return BelongsTo
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(MobilePushNotification::class, 'notification_id');
    }

    /**
     * ความสัมพันธ์กับ MobileDevice
     *
     * @return BelongsTo
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(MobileDevice::class, 'device_id');
    }

    /**
     * Scope: เฉพาะที่รอ retry
     */
    public function scopePendingRetry($query)
    {
        return $query->where('status', self::STATUS_RETRY_PENDING)
            ->where('attempt_count', '<', self::MAX_RETRY_ATTEMPTS)
            ->where(function ($q) {
                $q->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', now());
            });
    }

    /**
     * Scope: สำหรับเครื่องที่กลับมา online
     */
    public function scopeForDevice($query, int $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    /**
     * Scope: รอส่ง
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: ส่งสำเร็จ
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    /**
     * Scope: ล้มเหลว
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('status', [self::STATUS_FAILED, self::STATUS_RETRY_PENDING]);
    }

    /**
     * Mark as sent (ส่งไปยัง FCM/APNs แล้ว)
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'attempt_count' => $this->attempt_count + 1,
        ]);
    }

    /**
     * Mark as delivered (ได้รับการยืนยันว่าส่งถึงแล้ว)
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark for retry (เครื่องอาจ offline)
     *
     * @param string|null $errorMessage
     */
    public function markForRetry(?string $errorMessage = null): void
    {
        $attemptCount = $this->attempt_count + 1;

        // ถ้า retry เกินกำหนดแล้ว ให้ fail ถาวร
        if ($attemptCount >= self::MAX_RETRY_ATTEMPTS) {
            $this->markAsFailed($errorMessage . ' (exceeded max retry attempts)');
            return;
        }

        // คำนวณ delay สำหรับ retry ถัดไป (exponential backoff)
        // retry_1: 1 นาที, retry_2: 5 นาที, retry_3: 30 นาที, retry_4: 2 ชั่วโมง
        $delayMinutes = match ($attemptCount) {
            1 => 1,
            2 => 5,
            3 => 30,
            4 => 120,
            default => 120,
        };

        $this->update([
            'status' => self::STATUS_RETRY_PENDING,
            'attempt_count' => $attemptCount,
            'error_message' => $errorMessage,
            'next_retry_at' => now()->addMinutes($delayMinutes),
        ]);
    }

    /**
     * Mark as failed (ล้มเหลวถาวร)
     *
     * @param string|null $errorMessage
     */
    public function markAsFailed(?string $errorMessage = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * ตรวจสอบว่าสามารถ retry ได้หรือไม่
     */
    public function canRetry(): bool
    {
        return $this->status === self::STATUS_RETRY_PENDING
            && $this->attempt_count < self::MAX_RETRY_ATTEMPTS
            && ($this->next_retry_at === null || $this->next_retry_at <= now());
    }

    /**
     * สถานะเป็นภาษาไทย
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'รอส่ง',
            self::STATUS_SENT => 'ส่งแล้ว (รอยืนยัน)',
            self::STATUS_DELIVERED => 'ส่งถึงแล้ว',
            self::STATUS_FAILED => 'ล้มเหลว',
            self::STATUS_RETRY_PENDING => 'รอ retry',
            default => $this->status,
        };
    }

    /**
     * สี Badge ตามสถานะ
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_SENT => 'blue',
            self::STATUS_DELIVERED => 'green',
            self::STATUS_FAILED => 'red',
            self::STATUS_RETRY_PENDING => 'orange',
            default => 'gray',
        };
    }
}
