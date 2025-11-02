<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LineBotConversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'line_user_id',
        'user_id',
        'ai_setting_id',
        'session_id',
        'status',
        'last_message_at',
        'message_count',
        'context',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'message_count' => 'integer',
        'context' => 'array',
    ];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($conversation) {
            if (!$conversation->session_id) {
                $conversation->session_id = Str::uuid();
            }
        });
    }

    /**
     * Get user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get AI setting
     */
    public function aiSetting()
    {
        return $this->belongsTo(LineBotAiSetting::class, 'ai_setting_id');
    }

    /**
     * Get messages
     */
    public function messages()
    {
        return $this->hasMany(LineBotMessage::class, 'conversation_id');
    }

    /**
     * Get recent messages
     */
    public function recentMessages(int $limit = 10)
    {
        return $this->hasMany(LineBotMessage::class, 'conversation_id')
            ->orderBy('created_at', 'desc')
            ->limit($limit);
    }

    /**
     * Add message
     */
    public function addMessage(string $role, string $message, array $metadata = []): LineBotMessage
    {
        $msg = $this->messages()->create([
            'role' => $role,
            'message' => $message,
            'metadata' => $metadata,
        ]);

        $this->update([
            'last_message_at' => now(),
            'message_count' => $this->message_count + 1,
        ]);

        return $msg;
    }

    /**
     * Get conversation history for AI
     */
    public function getHistoryForAI(int $limit = 10): array
    {
        return $this->messages()
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($msg) {
                return [
                    'role' => $msg->role,
                    'content' => $msg->message,
                ];
            })
            ->toArray();
    }

    /**
     * Close conversation
     */
    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }

    /**
     * Archive conversation
     */
    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }
}
