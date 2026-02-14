<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UserNotification Model
 *
 * เก็บประวัติการแจ้งเตือนของผู้ใช้แต่ละคน
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $push_notification_id
 * @property string $title
 * @property string $body
 * @property string $type
 * @property bool $is_read
 */
class UserNotification extends Model
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'user_notifications';

    /**
     * Fields ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'push_notification_id',
        'title',
        'body',
        'data',
        'type',
        'icon',
        'image',
        'action_url',
        'is_read',
        'read_at',
    ];

    /**
     * Casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้น
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'general',
        'is_read' => false,
    ];

    // =====================================================
    // Relationships
    // =====================================================

    /**
     * ความสัมพันธ์กับ User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ PushNotification
     */
    public function pushNotification(): BelongsTo
    {
        return $this->belongsTo(PushNotification::class);
    }

    // =====================================================
    // Scopes
    // =====================================================

    /**
     * Scope สำหรับการแจ้งเตือนที่ยังไม่อ่าน
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope สำหรับการแจ้งเตือนที่อ่านแล้ว
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope สำหรับ type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
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
     * เวลาที่ผ่านมา
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    // =====================================================
    // Methods
    // =====================================================

    /**
     * ทำเครื่องหมายว่าอ่านแล้ว
     */
    public function markAsRead(): void
    {
        if (! $this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * สร้าง notification สำหรับผู้ใช้
     */
    public static function createForUser(int $userId, string $title, string $body, string $type = 'general', array $options = []): self
    {
        return self::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'data' => $options['data'] ?? null,
            'icon' => $options['icon'] ?? null,
            'image' => $options['image'] ?? null,
            'action_url' => $options['action_url'] ?? null,
        ]);
    }
}
