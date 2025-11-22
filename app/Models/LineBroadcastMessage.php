<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LineBroadcastMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'message_type',
        'content',
        'flex_template_id',
        'message_data',
        'target_type',
        'target_users',
        'status',
        'scheduled_at',
        'sent_at',
        'total_recipients',
        'sent_count',
        'failed_count',
        'retry_count',
        'last_retry_at',
        'error_message',
        'created_by',
    ];

    protected $casts = [
        'message_data' => 'array',
        'target_users' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'last_retry_at' => 'datetime',
        'total_recipients' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'retry_count' => 'integer',
    ];

    /**
     * Get flex template
     */
    public function flexTemplate()
    {
        return $this->belongsTo(LineFlexMessageTemplate::class, 'flex_template_id');
    }

    /**
     * Get creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Mark as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }

    /**
     * Increment sent count
     */
    public function incrementSent(): void
    {
        $this->increment('sent_count');
    }

    /**
     * Increment failed count
     */
    public function incrementFailed(): void
    {
        $this->increment('failed_count');
    }

    /**
     * Get success rate
     */
    public function getSuccessRate(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }

        return ($this->sent_count / $this->total_recipients) * 100;
    }

    /**
     * Scope: Scheduled messages
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope: Pending messages
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'scheduled']);
    }

    /**
     * Scope: Failed messages ที่สามารถ retry ได้
     */
    public function scopeRetryable($query)
    {
        return $query->where('status', 'failed')
            ->where('retry_count', '<', 3); // max 3 retries
    }

    /**
     * ตรวจสอบว่าสามารถ retry ได้หรือไม่
     *
     * @return bool
     */
    public function canRetry(): bool
    {
        return $this->status === 'failed' && $this->retry_count < 3;
    }

    /**
     * ตรวจสอบว่าถึงเวลาส่งแล้วหรือยัง
     *
     * @return bool
     */
    public function isReadyToSend(): bool
    {
        return $this->status === 'scheduled'
            && $this->scheduled_at
            && $this->scheduled_at->isPast();
    }

    /**
     * ดึงรายละเอียดสถานะเป็นข้อความ
     *
     * @return string
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'draft' => '📝 แบบร่าง',
            'scheduled' => '⏰ รอส่ง',
            'sending' => '🚀 กำลังส่ง',
            'sent' => '✅ ส่งแล้ว',
            'failed' => '❌ ล้มเหลว',
            default => $this->status,
        };
    }

    /**
     * ดึง badge color สำหรับแสดงสถานะ
     *
     * @return string
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'scheduled' => 'blue',
            'sending' => 'yellow',
            'sent' => 'green',
            'failed' => 'red',
            default => 'gray',
        };
    }
}
