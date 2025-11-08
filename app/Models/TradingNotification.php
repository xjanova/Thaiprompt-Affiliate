<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradingNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bot_id',
        'type',
        'title',
        'message',
        'data',
        'priority',
        'channels',
        'is_read',
        'read_at',
        'is_sent',
        'sent_at',
    ];

    protected $casts = [
        'data' => 'array',
        'channels' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'is_sent' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bot()
    {
        return $this->belongsTo(TradingBot::class, 'bot_id');
    }

    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function markAsSent(): void
    {
        $this->update([
            'is_sent' => true,
            'sent_at' => now(),
        ]);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'critical']);
    }
}
