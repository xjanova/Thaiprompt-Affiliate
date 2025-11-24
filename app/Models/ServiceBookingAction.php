<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ServiceBookingAction Model - ประวัติการทำงาน (Audit Trail)
 *
 * เก็บบันทึกทุกการเปลี่ยนแปลงและการกระทำทั้งหมดในการจอง
 * ใช้สำหรับ audit, debugging, และติดตามการทำงานของระบบ
 *
 * @property int $id
 * @property int $booking_id
 * @property string $action การกระทำ
 * @property string|null $from_status สถานะเดิม
 * @property string|null $to_status สถานะใหม่
 * @property int|null $user_id ผู้ทำ
 * @property int|null $provider_id ผู้ให้บริการ (ถ้าเกี่ยวข้อง)
 * @property array|null $data ข้อมูลเพิ่มเติม
 * @property string|null $ip_address IP Address
 * @property string|null $user_agent User Agent
 * @property string|null $notes หมายเหตุ
 * @property \Carbon\Carbon $created_at
 */
class ServiceBookingAction extends Model
{
    use HasFactory;

    protected $table = 'service_booking_actions';

    public $timestamps = false; // ใช้แค่ created_at

    protected $fillable = [
        'booking_id',
        'action',
        'from_status',
        'to_status',
        'user_id',
        'provider_id',
        'data',
        'ip_address',
        'user_agent',
        'notes',
    ];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
    ];

    //===========================================
    // Lifecycle Hooks
    //===========================================

    protected static function boot()
    {
        parent::boot();

        // ตั้งค่า created_at อัตโนมัติ
        static::creating(function ($action) {
            $action->created_at = now();
        });
    }

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
     * ผู้ทำ (User, Admin, หรือ Provider)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * ผู้ให้บริการ (ถ้าเกี่ยวข้อง)
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'provider_id');
    }

    //===========================================
    // Scopes
    //===========================================

    /**
     * Scope: เรียงตามเวลาล่าสุด
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope: กรองตามการกระทำ
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: การกระทำโดย user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: การกระทำโดย provider
     */
    public function scopeByProvider($query, int $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    /**
     * Scope: การเปลี่ยนแปลงสถานะ
     */
    public function scopeStatusChanges($query)
    {
        return $query->where('action', 'status_changed')
            ->whereNotNull('from_status')
            ->whereNotNull('to_status');
    }

    //===========================================
    // Methods - Static Creation
    //===========================================

    /**
     * บันทึก action ใหม่
     *
     * @param ServiceBooking $booking
     * @param string $action
     * @param array $data
     * @return static
     */
    public static function log(ServiceBooking $booking, string $action, array $data = []): self
    {
        return static::create([
            'booking_id' => $booking->id,
            'action' => $action,
            'from_status' => $data['from_status'] ?? null,
            'to_status' => $data['to_status'] ?? null,
            'user_id' => $data['user_id'] ?? auth()->id(),
            'provider_id' => $data['provider_id'] ?? null,
            'data' => $data['extra_data'] ?? null,
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'notes' => $data['notes'] ?? null,
        ]);
    }

    //===========================================
    // Accessors
    //===========================================

    /**
     * การกระทำเป็นภาษาไทย
     */
    public function getActionTextAttribute(): string
    {
        return match ($this->action) {
            'created' => 'สร้างการจอง',
            'status_changed' => 'เปลี่ยนสถานะ',
            'paid' => 'ชำระเงินแล้ว',
            'assigned' => 'มอบหมายผู้ให้บริการ',
            'provider_notified' => 'แจ้งเตือนผู้ให้บริการ',
            'provider_viewed' => 'ผู้ให้บริการดูงาน',
            'provider_accepted' => 'ผู้ให้บริการรับงาน',
            'provider_rejected' => 'ผู้ให้บริการปฏิเสธงาน',
            'journey_started' => 'เริ่มเดินทาง',
            'service_started' => 'เริ่มให้บริการ',
            'service_completed' => 'เสร็จสิ้นบริการ',
            'cancelled' => 'ยกเลิกการจอง',
            'payment_completed' => 'ชำระเงินสำเร็จ',
            'payment_refunded' => 'คืนเงินแล้ว',
            'reviewed' => 'รีวิวแล้ว',
            default => $this->action,
        };
    }

    /**
     * แสดงการเปลี่ยนแปลงสถานะ
     */
    public function getStatusChangeTextAttribute(): ?string
    {
        if (!$this->from_status || !$this->to_status) {
            return null;
        }

        return "{$this->from_status} → {$this->to_status}";
    }

    /**
     * แสดงผู้ทำ
     */
    public function getActorNameAttribute(): string
    {
        if ($this->user) {
            return $this->user->name;
        }

        if ($this->provider) {
            return $this->provider->name;
        }

        return 'System';
    }

    /**
     * แสดงเวลาที่ผ่านมา
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * แสดงข้อความสรุป
     */
    public function getSummaryAttribute(): string
    {
        $summary = $this->action_text;

        if ($this->status_change_text) {
            $summary .= " ({$this->status_change_text})";
        }

        if ($this->notes) {
            $summary .= " - {$this->notes}";
        }

        return $summary;
    }

    /**
     * Icon สำหรับแสดงผล
     */
    public function getIconAttribute(): string
    {
        return match ($this->action) {
            'created' => '📝',
            'paid' => '💰',
            'provider_notified' => '🔔',
            'provider_accepted' => '✅',
            'provider_rejected' => '❌',
            'journey_started' => '🚗',
            'service_started' => '🛠️',
            'service_completed' => '✨',
            'cancelled' => '🚫',
            'reviewed' => '⭐',
            default => '📌',
        };
    }
}
