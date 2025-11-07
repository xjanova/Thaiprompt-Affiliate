<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UpdateNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'system_update_id',
        'user_id',
        'type',
        'is_read',
        'read_at',
        'is_dismissed',
        'dismissed_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'is_dismissed' => 'boolean',
        'dismissed_at' => 'datetime',
    ];

    /**
     * Get the system update
     */
    public function systemUpdate()
    {
        return $this->belongsTo(SystemUpdate::class);
    }

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark as read
     */
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark as dismissed
     */
    public function dismiss()
    {
        $this->update([
            'is_dismissed' => true,
            'dismissed_at' => now(),
        ]);
    }

    /**
     * Scope for unread
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for not dismissed
     */
    public function scopeNotDismissed($query)
    {
        return $query->where('is_dismissed', false);
    }
}
