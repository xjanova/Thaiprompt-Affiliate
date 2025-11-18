<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AI Core Alert Model
 *
 * จัดการการแจ้งเตือนและ alerts ต่างๆ
 * เช่น quota ใกล้หมด, feature error, scheduled action, etc.
 *
 * @property int $id
 * @property int|null $tenant_id Tenant ที่เกี่ยวข้อง
 * @property int|null $feature_id Feature ที่เกี่ยวข้อง
 * @property int|null $user_id ผู้ใช้ที่เกี่ยวข้อง
 * @property string $alert_type ประเภท alert
 * @property string $severity ระดับความสำคัญ
 * @property string $title หัวข้อ alert
 * @property string $message ข้อความ alert
 * @property string|null $action_url URL สำหรับดำเนินการ
 * @property string|null $action_label ข้อความปุ่มดำเนินการ
 * @property bool $requires_action ต้องดำเนินการหรือไม่
 * @property string $status สถานะ alert
 * @property \Carbon\Carbon|null $read_at วันเวลาที่อ่าน
 * @property \Carbon\Carbon|null $acknowledged_at วันเวลาที่รับทราบ
 * @property \Carbon\Carbon|null $resolved_at วันเวลาที่แก้ไขแล้ว
 * @property array|null $notification_channels ช่องทางที่ส่ง
 * @property bool $notification_sent ส่งการแจ้งเตือนแล้ว
 * @property \Carbon\Carbon|null $notification_sent_at วันเวลาที่ส่ง
 * @property string|null $alert_key รหัสสำหรับจัดกลุ่ม
 * @property int $occurrence_count จำนวนครั้งที่เกิด
 * @property \Carbon\Carbon|null $first_occurred_at ครั้งแรกที่เกิด
 * @property \Carbon\Carbon|null $last_occurred_at ครั้งล่าสุดที่เกิด
 * @property \Carbon\Carbon|null $expires_at วันเวลาที่หมดอายุ
 * @property bool $auto_dismiss ปิดอัตโนมัติเมื่อหมดอายุ
 * @property array|null $related_data ข้อมูลที่เกี่ยวข้อง
 * @property array|null $metadata Metadata
 */
class AICoreAlert extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_core_alerts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'feature_id',
        'user_id',
        'alert_type',
        'severity',
        'title',
        'message',
        'action_url',
        'action_label',
        'requires_action',
        'status',
        'read_at',
        'acknowledged_at',
        'resolved_at',
        'notification_channels',
        'notification_sent',
        'notification_sent_at',
        'alert_key',
        'occurrence_count',
        'first_occurred_at',
        'last_occurred_at',
        'expires_at',
        'auto_dismiss',
        'related_data',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'requires_action' => 'boolean',
        'notification_sent' => 'boolean',
        'auto_dismiss' => 'boolean',
        'occurrence_count' => 'integer',
        'notification_channels' => 'array',
        'related_data' => 'array',
        'metadata' => 'array',
        'read_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'notification_sent_at' => 'datetime',
        'first_occurred_at' => 'datetime',
        'last_occurred_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ Tenant
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(AICoreTenant::class, 'tenant_id');
    }

    /**
     * ความสัมพันธ์กับ Feature
     *
     * @return BelongsTo
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(AICoreFeature::class, 'feature_id');
    }

    /**
     * ความสัมพันธ์กับ User
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: เฉพาะ alerts ที่ยังไม่อ่าน
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnread($query)
    {
        return $query->where('status', 'unread');
    }

    /**
     * Scope: เฉพาะ alerts ที่ยังไม่แก้ไข
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnresolved($query)
    {
        return $query->whereNotIn('status', ['resolved', 'dismissed']);
    }

    /**
     * Scope: กรองตาม severity
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $severity
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope: กรองตาม alert type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeType($query, string $type)
    {
        return $query->where('alert_type', $type);
    }

    /**
     * Scope: เฉพาะ alerts ที่หมดอายุแล้ว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<', now());
    }

    /**
     * ตรวจสอบว่าอ่านแล้วหรือยัง
     *
     * @return bool
     */
    public function isRead(): bool
    {
        return $this->status !== 'unread' || $this->read_at !== null;
    }

    /**
     * ตรวจสอบว่ารับทราบแล้วหรือยัง
     *
     * @return bool
     */
    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }

    /**
     * ตรวจสอบว่าแก้ไขแล้วหรือยัง
     *
     * @return bool
     */
    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    /**
     * ตรวจสอบว่าหมดอายุหรือยัง
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * ทำเครื่องหมายว่าอ่านแล้ว
     *
     * @return bool
     */
    public function markAsRead(): bool
    {
        if ($this->isRead()) {
            return true;
        }

        return $this->update([
            'status' => 'read',
            'read_at' => now(),
        ]);
    }

    /**
     * ทำเครื่องหมายว่ารับทราบแล้ว
     *
     * @return bool
     */
    public function acknowledge(): bool
    {
        return $this->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
        ]);
    }

    /**
     * ทำเครื่องหมายว่าแก้ไขแล้ว
     *
     * @return bool
     */
    public function resolve(): bool
    {
        return $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    /**
     * ยกเลิก/ปิด alert
     *
     * @return bool
     */
    public function dismiss(): bool
    {
        return $this->update([
            'status' => 'dismissed',
        ]);
    }

    /**
     * เพิ่มจำนวนครั้งที่เกิด (สำหรับ alert ซ้ำ)
     *
     * @return bool
     */
    public function incrementOccurrence(): bool
    {
        return $this->update([
            'occurrence_count' => $this->occurrence_count + 1,
            'last_occurred_at' => now(),
        ]);
    }

    /**
     * ส่งการแจ้งเตือน
     *
     * @return bool
     */
    public function sendNotification(): bool
    {
        // TODO: Implement notification sending logic
        // ส่งผ่าน channels ที่กำหนดไว้ใน notification_channels

        return $this->update([
            'notification_sent' => true,
            'notification_sent_at' => now(),
        ]);
    }

    /**
     * สร้าง alert ใหม่หรือเพิ่ม occurrence ถ้ามีอยู่แล้ว
     *
     * @param array $attributes
     * @return static
     */
    public static function createOrIncrement(array $attributes): static
    {
        $alertKey = $attributes['alert_key'] ?? null;

        if (!$alertKey) {
            return static::create($attributes);
        }

        $existing = static::where('alert_key', $alertKey)
            ->where('status', '!=', 'resolved')
            ->first();

        if ($existing) {
            $existing->incrementOccurrence();
            return $existing;
        }

        $attributes['first_occurred_at'] = now();
        $attributes['last_occurred_at'] = now();

        return static::create($attributes);
    }
}
