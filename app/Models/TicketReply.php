<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'is_internal_note',
        'attachments',
        'read_at',
    ];

    protected $casts = [
        'is_internal_note' => 'boolean',
        'attachments' => 'array',
        'read_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Update ticket's last_reply_at when a reply is created
        static::created(function ($reply) {
            $reply->ticket->update([
                'last_reply_at' => now(),
            ]);
        });
    }

    /**
     * Get the ticket this reply belongs to
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user who created the reply
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get attachments (polymorphic)
     */
    public function fileAttachments()
    {
        return $this->morphMany(TicketAttachment::class, 'attachable');
    }

    /**
     * Check if reply is from staff
     */
    public function isFromStaff()
    {
        return $this->user && ($this->user->is_super_admin || $this->user->role === 'admin' || $this->user->role === 'moderator');
    }

    /**
     * Check if reply is read
     */
    public function isRead()
    {
        return !is_null($this->read_at);
    }

    /**
     * Mark reply as read
     */
    public function markAsRead()
    {
        if (!$this->isRead()) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Scope to get only public replies
     */
    public function scopePublic($query)
    {
        return $query->where('is_internal_note', false);
    }

    /**
     * Scope to get only internal notes
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal_note', true);
    }

    /**
     * Scope to get unread replies
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
