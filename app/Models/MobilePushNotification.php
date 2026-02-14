<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MobilePushNotification Model
 *
 * จัดการ Push Notifications ที่ส่งไปยังแอพมือถือ
 * Admin สามารถส่งข้อความถึงผู้ใช้ได้
 *
 * @property int $id
 * @property string $title หัวข้อ notification
 * @property string $body ข้อความ
 * @property string|null $image URL รูปภาพ
 * @property array|null $data ข้อมูลเพิ่มเติม (JSON)
 * @property string $target_platform กลุ่มเป้าหมาย (all, ios, android)
 * @property string $status สถานะ (pending, scheduled, sent, failed)
 * @property \Carbon\Carbon|null $scheduled_at เวลาที่ตั้งเวลาส่ง
 * @property \Carbon\Carbon|null $sent_at เวลาที่ส่งจริง
 * @property int|null $success_count จำนวนส่งสำเร็จ
 * @property int|null $failure_count จำนวนส่งล้มเหลว
 * @property int|null $created_by ผู้สร้าง (admin user id)
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MobilePushNotification extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mobile_push_notifications';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'body',
        'image',
        'data',
        'target_platform',
        'status',
        'scheduled_at',
        'sent_at',
        'success_count',
        'failure_count',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'success_count' => 'integer',
        'failure_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The model's default values.
     *
     * @var array
     */
    protected $attributes = [
        'target_platform' => 'all',
        'status' => 'pending',
        'success_count' => 0,
        'failure_count' => 0,
    ];

    /**
     * สถานะที่เป็นไปได้
     */
    const STATUS_PENDING = 'pending';

    const STATUS_SCHEDULED = 'scheduled';

    const STATUS_SENT = 'sent';

    const STATUS_FAILED = 'failed';

    /**
     * ความสัมพันธ์กับ User (ผู้สร้าง)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * ความสัมพันธ์กับ PushNotificationDelivery
     * ติดตามการส่งไปยังแต่ละเครื่อง
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(PushNotificationDelivery::class, 'notification_id');
    }

    /**
     * จำนวนที่ส่งถึงแล้ว (จาก delivery tracking)
     */
    public function getDeliveredCountAttribute(): int
    {
        return $this->deliveries()
            ->where('status', PushNotificationDelivery::STATUS_DELIVERED)
            ->count();
    }

    /**
     * จำนวนที่รอ retry
     */
    public function getRetryPendingCountAttribute(): int
    {
        return $this->deliveries()
            ->where('status', PushNotificationDelivery::STATUS_RETRY_PENDING)
            ->count();
    }

    /**
     * Scope: เฉพาะ Pending
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: เฉพาะ Scheduled ที่ถึงเวลาแล้ว
     */
    public function scopeReadyToSend($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope: เฉพาะที่ส่งแล้ว
     */
    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * ตรวจสอบว่าสามารถส่งได้หรือยัง
     */
    public function canSend(): bool
    {
        if ($this->status === self::STATUS_SENT) {
            return false;
        }
        if ($this->status === self::STATUS_FAILED) {
            return false;
        }

        if ($this->scheduled_at && $this->scheduled_at > now()) {
            return false;
        }

        return true;
    }

    /**
     * Mark as sent
     */
    public function markAsSent(int $successCount = 0, int $failureCount = 0): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
        ]);
    }

    /**
     * คำนวณ success rate
     */
    public function getSuccessRateAttribute(): float
    {
        $total = $this->success_count + $this->failure_count;
        if ($total === 0) {
            return 0;
        }

        return round(($this->success_count / $total) * 100, 1);
    }

    /**
     * สถานะเป็นภาษาไทย
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'รอส่ง',
            self::STATUS_SCHEDULED => 'ตั้งเวลาไว้',
            self::STATUS_SENT => 'ส่งแล้ว',
            self::STATUS_FAILED => 'ล้มเหลว',
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
            self::STATUS_SCHEDULED => 'blue',
            self::STATUS_SENT => 'green',
            self::STATUS_FAILED => 'red',
            default => 'gray',
        };
    }
}
