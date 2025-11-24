<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model สำหรับ AI Rental Alerts
 *
 * จัดการ alerts, notifications และ warnings ในระบบ AI Rental
 *
 * @property int $id
 * @property int $user_id ID ผู้ใช้
 * @property int|null $deployment_id ID deployment
 * @property string $alert_type ประเภท alert
 * @property string $severity ระดับความสำคัญ
 * @property string $title หัวข้อ
 * @property string $message ข้อความ
 * @property string|null $description รายละเอียด
 * @property array|null $trigger_conditions เงื่อนไข trigger
 * @property array|null $alert_data ข้อมูลเพิ่มเติม
 * @property string|null $action_required การกระทำที่แนะนำ
 * @property string|null $action_url URL action
 * @property string|null $action_label ป้ายชื่อ action
 * @property string $status สถานะ
 * @property \Carbon\Carbon|null $sent_at เวลาส่ง
 * @property \Carbon\Carbon|null $read_at เวลาอ่าน
 * @property \Carbon\Carbon|null $acknowledged_at เวลา acknowledge
 * @property \Carbon\Carbon|null $resolved_at เวลาแก้ไข
 * @property array|null $notification_channels ช่องทางแจ้งเตือน
 * @property bool $email_sent ส่ง email แล้ว
 * @property bool $push_sent ส่ง push แล้ว
 * @property bool $webhook_sent ส่ง webhook แล้ว
 * @property bool $auto_resolved แก้ไขอัตโนมัติ
 * @property string|null $resolution_note หมายเหตุการแก้ไข
 * @property float|null $cost_at_alert ค่าใช้จ่าย ณ เวลาที่เกิด
 * @property float|null $budget_limit Budget limit
 * @property int|null $error_count จำนวน errors
 * @property float|null $response_time_ms Response time
 * @property int $priority ลำดับความสำคัญ
 * @property \Carbon\Carbon|null $expires_at หมดอายุเมื่อ
 * @property array|null $metadata ข้อมูลเพิ่มเติม
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class AiRentalAlert extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_rental_alerts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'deployment_id',
        'alert_type',
        'severity',
        'title',
        'message',
        'description',
        'trigger_conditions',
        'alert_data',
        'action_required',
        'action_url',
        'action_label',
        'status',
        'sent_at',
        'read_at',
        'acknowledged_at',
        'resolved_at',
        'notification_channels',
        'email_sent',
        'push_sent',
        'webhook_sent',
        'auto_resolved',
        'resolution_note',
        'cost_at_alert',
        'budget_limit',
        'error_count',
        'response_time_ms',
        'priority',
        'expires_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'trigger_conditions' => 'array',
        'alert_data' => 'array',
        'notification_channels' => 'array',
        'metadata' => 'array',
        'email_sent' => 'boolean',
        'push_sent' => 'boolean',
        'webhook_sent' => 'boolean',
        'auto_resolved' => 'boolean',
        'cost_at_alert' => 'decimal:2',
        'budget_limit' => 'decimal:2',
        'response_time_ms' => 'decimal:2',
        'priority' => 'integer',
        'error_count' => 'integer',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

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
     * ความสัมพันธ์กับ Deployment
     *
     * @return BelongsTo
     */
    public function deployment(): BelongsTo
    {
        return $this->belongsTo(AiRentalDeployment::class, 'deployment_id');
    }

    /**
     * Scope: Alerts ที่ยังไม่ได้อ่าน
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope: Alerts ที่ยังไม่ได้ resolve
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    /**
     * Scope: Alerts ตาม severity
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
     * Scope: Alerts ที่มีความสำคัญสูง (critical/error)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCritical($query)
    {
        return $query->whereIn('severity', ['critical', 'error']);
    }

    /**
     * Scope: Alerts ตาม user
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Alerts ที่ active (ยังไม่หมดอายุและยังไม่ resolve)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereNull('resolved_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * ตรวจสอบว่า alert นี้อ่านแล้วหรือยัง
     *
     * @return bool
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * ตรวจสอบว่า alert นี้ resolve แล้วหรือยัง
     *
     * @return bool
     */
    public function isResolved(): bool
    {
        return !is_null($this->resolved_at);
    }

    /**
     * ตรวจสอบว่า alert นี้หมดอายุแล้วหรือยัง
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return !is_null($this->expires_at) && $this->expires_at < now();
    }

    /**
     * ทำเครื่องหมายว่าอ่านแล้ว
     *
     * @return bool
     */
    public function markAsRead(): bool
    {
        if (!$this->isRead()) {
            $this->read_at = now();
            return $this->save();
        }
        return false;
    }

    /**
     * ทำเครื่องหมายว่า acknowledge แล้ว
     *
     * @return bool
     */
    public function markAsAcknowledged(): bool
    {
        if (is_null($this->acknowledged_at)) {
            $this->acknowledged_at = now();
            $this->status = 'acknowledged';
            return $this->save();
        }
        return false;
    }

    /**
     * ทำเครื่องหมายว่า resolve แล้ว
     *
     * @param string|null $note หมายเหตุ
     * @return bool
     */
    public function markAsResolved(?string $note = null): bool
    {
        if (!$this->isResolved()) {
            $this->resolved_at = now();
            $this->status = 'resolved';
            if ($note) {
                $this->resolution_note = $note;
            }
            return $this->save();
        }
        return false;
    }

    /**
     * ส่ง notification
     *
     * @return void
     */
    public function sendNotification(): void
    {
        // TODO: Implement notification sending
        // - Email notification
        // - Push notification
        // - Webhook notification

        $this->sent_at = now();
        $this->status = 'sent';
        $this->save();
    }
}
