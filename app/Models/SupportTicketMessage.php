<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SupportTicketMessage Model
 *
 * จัดการข้อมูลข้อความใน Ticket
 *
 * @property int $id
 * @property int $ticket_id
 * @property int $user_id
 * @property string $message
 * @property bool $is_from_admin
 */
class SupportTicketMessage extends Model
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'support_ticket_messages';

    /**
     * Fields ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'is_from_admin',
        'attachments',
        'is_read',
        'read_at',
    ];

    /**
     * Casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_from_admin' => 'boolean',
        'attachments' => 'array',
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
        'is_from_admin' => false,
        'is_read' => false,
    ];

    // =====================================================
    // Relationships
    // =====================================================

    /**
     * ความสัมพันธ์กับ Ticket
     *
     * @return BelongsTo
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
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

    // =====================================================
    // Scopes
    // =====================================================

    /**
     * Scope สำหรับข้อความที่ยังไม่อ่าน
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope สำหรับข้อความจากแอดมิน
     */
    public function scopeFromAdmin($query)
    {
        return $query->where('is_from_admin', true);
    }

    /**
     * Scope สำหรับข้อความจากผู้ใช้
     */
    public function scopeFromUser($query)
    {
        return $query->where('is_from_admin', false);
    }

    // =====================================================
    // Methods
    // =====================================================

    /**
     * ทำเครื่องหมายว่าอ่านแล้ว
     *
     * @return void
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
}
