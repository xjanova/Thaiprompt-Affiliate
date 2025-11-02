<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LineBotMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'role',
        'message',
        'message_type',
        'metadata',
        'tokens_used',
        'response_time',
        'is_ai_generated',
    ];

    protected $casts = [
        'metadata' => 'array',
        'tokens_used' => 'integer',
        'response_time' => 'float',
        'is_ai_generated' => 'boolean',
    ];

    /**
     * Get conversation
     */
    public function conversation()
    {
        return $this->belongsTo(LineBotConversation::class, 'conversation_id');
    }

    /**
     * Check if message is from user
     */
    public function isFromUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Check if message is from assistant
     */
    public function isFromAssistant(): bool
    {
        return $this->role === 'assistant';
    }
}
