<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineSignupConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'role',
        'message',
        'metadata',
        'message_type',
        'line_message_payload',
    ];

    protected $casts = [
        'metadata' => 'array',
        'line_message_payload' => 'array',
    ];

    /**
     * Message roles
     */
    public const ROLE_USER = 'user';
    public const ROLE_ASSISTANT = 'assistant';
    public const ROLE_SYSTEM = 'system';

    /**
     * Message types
     */
    public const TYPE_TEXT = 'text';
    public const TYPE_FLEX = 'flex';
    public const TYPE_TEMPLATE = 'template';
    public const TYPE_QUICK_REPLY = 'quick_reply';

    /**
     * Get the session that owns this conversation
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(LineSignupSession::class, 'session_id');
    }
}
