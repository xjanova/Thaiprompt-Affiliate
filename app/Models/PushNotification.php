<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PushNotification Model
 *
 * จัดการการส่ง Push Notification
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $sent_by
 * @property string $title
 * @property string $body
 * @property string $type
 * @property string $target_type
 * @property string $status
 */
class PushNotification extends Model
{
    use SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'push_notifications';

    /**
     * Fields ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'sent_by',
        'title',
        'body',
        'data',
        'type',
        'target_type',
        'icon',
        'image',
        'action_url',
        'status',
        'error_message',
        'scheduled_at',
        'sent_at',
    ];

    /**
     * Casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้น
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'general',
        'target_type' => 'individual',
        'status' => 'pending',
    ];

    // =====================================================
    // Relationships
    // =====================================================

    /**
     * ความสัมพันธ์กับ User (ผู้รับ)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ User (ผู้ส่ง/แอดมิน)
     */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * การแจ้งเตือนที่ส่งไปยังผู้ใช้
     */
    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    // =====================================================
    // Scopes
    // =====================================================

    /**
     * Scope สำหรับ notification ที่รอส่ง
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope สำหรับ notification ที่ส่งแล้ว
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope สำหรับ notification ที่ล้มเหลว
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope สำหรับ notification ที่พร้อมส่ง
     */
    public function scopeReadyToSend($query)
    {
        return $query->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            });
    }

    // =====================================================
    // Accessors
    // =====================================================

    /**
     * ชื่อประเภทภาษาไทย
     */
    public function getTypeTextAttribute(): string
    {
        return match ($this->type) {
            'general' => 'ทั่วไป',
            'order' => 'ออเดอร์',
            'rider' => 'ไรเดอร์',
            'wallet' => 'กระเป๋าเงิน',
            'promotion' => 'โปรโมชั่น',
            'system' => 'ระบบ',
            'ticket' => 'Ticket',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * ชื่อเป้าหมายภาษาไทย
     */
    public function getTargetTypeTextAttribute(): string
    {
        return match ($this->target_type) {
            'individual' => 'รายบุคคล',
            'all_users' => 'ผู้ใช้ทั้งหมด',
            'riders' => 'ไรเดอร์ทั้งหมด',
            'sellers' => 'ผู้ขายทั้งหมด',
            'segment' => 'กลุ่มที่กำหนด',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * ชื่อสถานะภาษาไทย
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'รอส่ง',
            'sent' => 'ส่งแล้ว',
            'failed' => 'ส่งไม่สำเร็จ',
            'cancelled' => 'ยกเลิก',
            default => 'ไม่ทราบ',
        };
    }

    // =====================================================
    // Methods
    // =====================================================

    /**
     * ส่ง notification
     */
    public function send(): bool
    {
        // TODO: Implement actual push notification sending
        // ใช้ Expo Push API หรือ Firebase Cloud Messaging

        try {
            // สำหรับตอนนี้ใช้เป็น mock
            $this->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            // สร้าง user notification
            $this->createUserNotification();

            return true;
        } catch (\Exception $e) {
            $this->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * สร้าง user notification
     */
    protected function createUserNotification(): void
    {
        if ($this->target_type === 'individual' && $this->user_id) {
            UserNotification::create([
                'user_id' => $this->user_id,
                'push_notification_id' => $this->id,
                'title' => $this->title,
                'body' => $this->body,
                'data' => $this->data,
                'type' => $this->type,
                'icon' => $this->icon,
                'image' => $this->image,
                'action_url' => $this->action_url,
            ]);
        }
    }

    /**
     * ยกเลิก notification
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}
