<?php

namespace App\Models\BotAutomation;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotSupportMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'sender_type',
        'message',
        'attachments',
        'is_ai_generated',
        'ai_prompt_used',
        'tokens_used',
        'read_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_ai_generated' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Get the conversation
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(BotSupportConversation::class);
    }

    /**
     * Get the sender
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Mark as read
     */
    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Scope: Unread messages
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope: AI generated messages
     */
    public function scopeAiGenerated($query)
    {
        return $query->where('is_ai_generated', true);
    }

    /**
     * Boot method to update conversation
     */
    protected static function booted()
    {
        static::created(function ($message) {
            $conversation = $message->conversation;
            $conversation->increment('message_count');

            // Update first response time if this is first agent/bot response
            if (!$conversation->first_response_at && in_array($message->sender_type, ['agent', 'bot'])) {
                $conversation->update(['first_response_at' => now()]);
            }
        });
    }
}
