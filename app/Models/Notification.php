<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'notifiable_type',
        'notifiable_id',
        'action_url',
        'action_text',
        'icon',
        'color',
        'priority',
        'is_important',
        'show_immediately',
        'is_broadcast',
        'is_read',
        'read_at',
        'shown_at',
        'is_archived',
        'archived_at',
        'email_sent',
        'email_sent_at',
        'push_sent',
        'push_sent_at',
        'expires_at',
        'scheduled_at',
        'is_scheduled',
        'is_sent',
    ];

    protected $casts = [
        'data' => 'array',
        'is_important' => 'boolean',
        'show_immediately' => 'boolean',
        'is_broadcast' => 'boolean',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'shown_at' => 'datetime',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
        'email_sent' => 'boolean',
        'email_sent_at' => 'datetime',
        'push_sent' => 'boolean',
        'push_sent_at' => 'datetime',
        'expires_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'is_scheduled' => 'boolean',
        'is_sent' => 'boolean',
    ];

    /**
     * Get the user that owns the notification
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the notifiable model (polymorphic)
     */
    public function notifiable()
    {
        return $this->morphTo();
    }

    /**
     * Mark notification as read
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
     * Mark notification as unread
     */
    public function markAsUnread(): void
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Archive notification
     */
    public function archive(): void
    {
        $this->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);
    }

    /**
     * Unarchive notification
     */
    public function unarchive(): void
    {
        $this->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);
    }

    /**
     * Check if notification is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'wallet' => 'กระเป๋าเงิน',
            'withdrawal' => 'ถอนเงิน',
            'deposit' => 'ฝากเงิน',
            'transfer' => 'โอนเงิน',
            'commission' => 'คอมมิชชั่น',
            'system' => 'ระบบ',
            'announcement' => 'ประกาศ',
            'alert' => 'แจ้งเตือน',
            default => 'ทั่วไป',
        };
    }

    /**
     * Get priority label
     */
    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            'low' => 'ต่ำ',
            'normal' => 'ปกติ',
            'high' => 'สูง',
            'urgent' => 'เร่งด่วน',
            default => 'ปกติ',
        };
    }

    /**
     * Get priority color
     */
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'low' => 'gray',
            'normal' => 'blue',
            'high' => 'orange',
            'urgent' => 'red',
            default => 'blue',
        };
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope for unarchived notifications
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope for archived notifications
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope for important notifications
     */
    public function scopeImportant($query)
    {
        return $query->where('is_important', true);
    }

    /**
     * Scope for non-expired notifications
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope by priority
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for immediate notifications
     */
    public function scopeImmediate($query)
    {
        return $query->where('show_immediately', true);
    }

    /**
     * Scope for broadcast notifications
     */
    public function scopeBroadcast($query)
    {
        return $query->where('is_broadcast', true);
    }

    /**
     * Mark notification as shown
     */
    public function markAsShown(): void
    {
        if (!$this->shown_at) {
            $this->update([
                'shown_at' => now(),
            ]);
        }
    }

    /**
     * Scope for scheduled notifications
     */
    public function scopeScheduled($query)
    {
        return $query->where('is_scheduled', true);
    }

    /**
     * Scope for pending scheduled notifications (not yet sent)
     */
    public function scopePendingScheduled($query)
    {
        return $query->where('is_scheduled', true)
                    ->where('is_sent', false)
                    ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope for sent notifications
     */
    public function scopeSent($query)
    {
        return $query->where('is_sent', true);
    }

    /**
     * Mark notification as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'is_sent' => true,
        ]);
    }
}
