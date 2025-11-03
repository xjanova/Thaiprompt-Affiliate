<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiConversation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'bot_profile_id',
        'rental_id',
        'user_id',
        'line_user_id',
        'title',
        'status',
        'total_messages',
        'total_tokens',
        'total_cost',
        'started_at',
        'last_message_at',
        'archived_at',
    ];

    protected $casts = [
        'total_cost' => 'decimal:4',
        'started_at' => 'datetime',
        'last_message_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * Get the bot profile for this conversation
     */
    public function botProfile(): BelongsTo
    {
        return $this->belongsTo(AiBotProfile::class, 'bot_profile_id');
    }

    /**
     * Get the rental (if this is a rented bot)
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(AiBotRental::class, 'rental_id');
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all messages in this conversation
     */
    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'conversation_id')
                    ->orderBy('created_at', 'asc');
    }

    /**
     * Get user messages only
     */
    public function userMessages(): HasMany
    {
        return $this->messages()->where('role', 'user');
    }

    /**
     * Get assistant messages only
     */
    public function assistantMessages(): HasMany
    {
        return $this->messages()->where('role', 'assistant');
    }

    /**
     * Scope: Active conversations
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: For specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: For specific LINE user
     */
    public function scopeForLineUser($query, string $lineUserId)
    {
        return $query->where('line_user_id', $lineUserId);
    }

    /**
     * Scope: Recent conversations
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('last_message_at', '>=', now()->subDays($days));
    }

    /**
     * Add a message to the conversation
     */
    public function addMessage(string $role, string $content, array $metadata = []): AiMessage
    {
        $message = $this->messages()->create([
            'role' => $role,
            'content' => $content,
            'tokens_used' => $metadata['tokens_used'] ?? null,
            'model_used' => $metadata['model_used'] ?? null,
            'provider_used' => $metadata['provider_used'] ?? null,
            'response_time_ms' => $metadata['response_time_ms'] ?? null,
            'function_name' => $metadata['function_name'] ?? null,
            'function_arguments' => $metadata['function_arguments'] ?? null,
            'function_response' => $metadata['function_response'] ?? null,
            'context_used' => $metadata['context_used'] ?? null,
        ]);

        // Update conversation statistics
        $this->total_messages++;
        $this->last_message_at = now();

        if (isset($metadata['tokens_used'])) {
            $this->total_tokens += $metadata['tokens_used'];
        }

        if (isset($metadata['cost'])) {
            $this->total_cost += $metadata['cost'];
        }

        $this->save();

        return $message;
    }

    /**
     * Archive conversation
     */
    public function archive(): void
    {
        $this->status = 'archived';
        $this->archived_at = now();
        $this->save();
    }

    /**
     * Delete conversation (soft)
     */
    public function softDelete(): void
    {
        $this->status = 'deleted';
        $this->save();
    }

    /**
     * Get conversation context (recent messages)
     */
    public function getContext(int $limit = 10): array
    {
        return $this->messages()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(function ($message) {
                return [
                    'role' => $message->role,
                    'content' => $message->content,
                ];
            })
            ->toArray();
    }

    /**
     * Generate conversation title from first messages
     */
    public function generateTitle(): void
    {
        if ($this->title) {
            return;
        }

        $firstUserMessage = $this->messages()
            ->where('role', 'user')
            ->first();

        if ($firstUserMessage) {
            // Take first 50 characters of the first user message
            $this->title = mb_substr($firstUserMessage->content, 0, 50);

            if (mb_strlen($firstUserMessage->content) > 50) {
                $this->title .= '...';
            }

            $this->save();
        }
    }

    /**
     * Calculate average response time
     */
    public function getAverageResponseTime(): ?int
    {
        $avgResponseTime = $this->messages()
            ->where('role', 'assistant')
            ->whereNotNull('response_time_ms')
            ->avg('response_time_ms');

        return $avgResponseTime ? (int) $avgResponseTime : null;
    }
}
