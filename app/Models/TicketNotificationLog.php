<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketNotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'type',
        'channel',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Get the ticket this notification is for
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user this notification was sent to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Pending notifications
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Failed notifications
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: Sent notifications
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Mark notification as sent
     */
    public function markAsSent()
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark notification as failed
     */
    public function markAsFailed($errorMessage)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Create notification log
     */
    public static function createLog($ticketId, $userId, $type, $channel = 'email')
    {
        return self::create([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'type' => $type,
            'channel' => $channel,
            'status' => 'pending',
        ]);
    }

    /**
     * Get type label in Thai
     */
    public function getTypeLabelAttribute()
    {
        return match($this->type) {
            'new_ticket' => 'Ticket ใหม่',
            'new_reply' => 'ข้อความตอบกลับใหม่',
            'status_changed' => 'เปลี่ยนสถานะ',
            'assigned' => 'มอบหมายงาน',
            'sla_warning' => 'เตือน SLA ใกล้เกิน',
            'sla_breached' => 'SLA เกินกำหนด',
            'ticket_closed' => 'ปิด Ticket',
            'rating_request' => 'ขอให้คะแนน',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * Get channel label
     */
    public function getChannelLabelAttribute()
    {
        return match($this->channel) {
            'email' => 'อีเมล',
            'in_app' => 'แจ้งเตือนในแอป',
            'sms' => 'SMS',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => 'รอส่ง',
            'sent' => 'ส่งแล้ว',
            'failed' => 'ส่งไม่สำเร็จ',
            default => 'ไม่ระบุ',
        };
    }
}
