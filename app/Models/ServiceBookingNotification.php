<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ServiceBookingNotification Model - ประวัติการแจ้งเตือน
 *
 * เก็บบันทึกการส่ง notification ทุกช่องทาง (In-App, LINE, Email, SMS)
 * ใช้สำหรับ tracking และ debugging ระบบ notification
 *
 * @property int $id
 * @property int $booking_id
 * @property int|null $provider_id ผู้ให้บริการ
 * @property int|null $user_id ผู้ใช้/ลูกค้า
 * @property string $notification_type ประเภทการแจ้งเตือน
 * @property string $channel ช่องทาง: in_app, line, email, sms, push
 * @property string $recipient ผู้รับ
 * @property string|null $title หัวข้อ
 * @property string $message ข้อความ
 * @property array|null $data ข้อมูลเพิ่มเติม
 * @property bool $is_sent ส่งสำเร็จ
 * @property \Carbon\Carbon|null $sent_at เวลาที่ส่ง
 * @property bool $is_read อ่านแล้ว
 * @property \Carbon\Carbon|null $read_at เวลาที่อ่าน
 * @property string|null $failed_reason เหตุผลล้มเหลว
 * @property int $retry_count จำนวน retry
 * @property \Carbon\Carbon|null $next_retry_at เวลา retry ครั้งถัดไป
 * @property array|null $provider_response คำตอบจาก provider
 */
class ServiceBookingNotification extends Model
{
    use HasFactory;

    protected $table = 'service_booking_notifications';

    protected $fillable = [
        'booking_id',
        'provider_id',
        'user_id',
        'notification_type',
        'channel',
        'recipient',
        'title',
        'message',
        'data',
        'is_sent',
        'sent_at',
        'is_read',
        'read_at',
        'failed_reason',
        'retry_count',
        'next_retry_at',
        'provider_response',
    ];

    protected $casts = [
        'data' => 'array',
        'is_sent' => 'boolean',
        'sent_at' => 'datetime',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'retry_count' => 'integer',
        'next_retry_at' => 'datetime',
        'provider_response' => 'array',
    ];

    //===========================================
    // Relationships
    //===========================================

    /**
     * การจอง
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(ServiceBooking::class, 'booking_id');
    }

    /**
     * ผู้ให้บริการ (ถ้าส่งถึง provider)
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'provider_id');
    }

    /**
     * ผู้ใช้ (ถ้าส่งถึง customer)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    //===========================================
    // Scopes
    //===========================================

    /**
     * Scope: กรองตามช่องทาง
     */
    public function scopeChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope: การแจ้งเตือนผ่าน LINE
     */
    public function scopeLine($query)
    {
        return $query->where('channel', 'line');
    }

    /**
     * Scope: การแจ้งเตือนที่ส่งสำเร็จ
     */
    public function scopeSent($query)
    {
        return $query->where('is_sent', true);
    }

    /**
     * Scope: การแจ้งเตือนที่ส่งไม่สำเร็จ
     */
    public function scopeFailed($query)
    {
        return $query->where('is_sent', false)
            ->whereNotNull('failed_reason');
    }

    /**
     * Scope: การแจ้งเตือนที่อ่านแล้ว
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope: การแจ้งเตือนที่ยังไม่อ่าน
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: ที่ต้อง retry
     */
    public function scopePendingRetry($query)
    {
        return $query->where('is_sent', false)
            ->whereNotNull('next_retry_at')
            ->where('next_retry_at', '<=', now())
            ->where('retry_count', '<', 3);
    }

    /**
     * Scope: กรองตามประเภท
     */
    public function scopeType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }

    //===========================================
    // Methods - Notification Status
    //===========================================

    /**
     * บันทึกว่าส่งสำเร็จ
     */
    public function markAsSent(): void
    {
        $this->update([
            'is_sent' => true,
            'sent_at' => now(),
            'failed_reason' => null,
        ]);
    }

    /**
     * บันทึกว่าส่งล้มเหลว
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'is_sent' => false,
            'failed_reason' => $reason,
        ]);
    }

    /**
     * บันทึกว่าอ่านแล้ว
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * เพิ่มจำนวน retry
     */
    public function incrementRetry(int $nextRetryMinutes = 5): void
    {
        $this->update([
            'retry_count' => $this->retry_count + 1,
            'next_retry_at' => now()->addMinutes($nextRetryMinutes),
        ]);
    }

    /**
     * บันทึกคำตอบจาก provider (สำหรับ LINE)
     */
    public function setProviderResponse(array $response): void
    {
        $this->update([
            'provider_response' => $response,
        ]);
    }

    //===========================================
    // Methods - Static Creation
    //===========================================

    /**
     * สร้างการแจ้งเตือนใหม่
     *
     * @param ServiceBooking $booking
     * @param string $notificationType
     * @param string $channel
     * @param array $data
     * @return static
     */
    public static function createNotification(
        ServiceBooking $booking,
        string $notificationType,
        string $channel,
        array $data
    ): self {
        return static::create([
            'booking_id' => $booking->id,
            'provider_id' => $data['provider_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'notification_type' => $notificationType,
            'channel' => $channel,
            'recipient' => $data['recipient'],
            'title' => $data['title'] ?? null,
            'message' => $data['message'],
            'data' => $data['extra_data'] ?? null,
        ]);
    }

    //===========================================
    // Accessors
    //===========================================

    /**
     * ประเภทการแจ้งเตือนเป็นภาษาไทย
     */
    public function getTypeTextAttribute(): string
    {
        return match ($this->notification_type) {
            'new_booking' => 'มีงานใหม่',
            'booking_reminder' => 'เตือนก่อนหมดเวลา',
            'booking_accepted' => 'ผู้ให้บริการรับงานแล้ว',
            'booking_rejected' => 'ผู้ให้บริการปฏิเสธงาน',
            'booking_cancelled' => 'งานถูกยกเลิก',
            'provider_on_way' => 'ผู้ให้บริการกำลังเดินทาง',
            'service_started' => 'เริ่มให้บริการ',
            'service_completed' => 'เสร็จสิ้น',
            'payment_received' => 'รับเงินแล้ว',
            default => 'ไม่ทราบประเภท',
        };
    }

    /**
     * ช่องทางเป็นภาษาไทย
     */
    public function getChannelTextAttribute(): string
    {
        return match ($this->channel) {
            'in_app' => 'ในแอพ',
            'line' => 'LINE',
            'email' => 'อีเมล',
            'sms' => 'SMS',
            'push' => 'Push Notification',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * สถานะการส่งเป็นภาษาไทย
     */
    public function getStatusTextAttribute(): string
    {
        if ($this->is_sent) {
            return 'ส่งสำเร็จ';
        }

        if ($this->failed_reason) {
            return 'ส่งไม่สำเร็จ';
        }

        return 'รอส่ง';
    }

    /**
     * สีของสถานะ
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->is_sent) {
            return 'green';
        }

        if ($this->failed_reason) {
            return 'red';
        }

        return 'yellow';
    }

    /**
     * ตรวจสอบว่าสามารถ retry ได้หรือไม่
     */
    public function canRetry(): bool
    {
        return !$this->is_sent && $this->retry_count < 3;
    }

    /**
     * ตรวจสอบว่าถึงเวลา retry หรือยัง
     */
    public function shouldRetry(): bool
    {
        return $this->canRetry()
            && $this->next_retry_at
            && $this->next_retry_at->isPast();
    }
}
