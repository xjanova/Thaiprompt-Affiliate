<?php

namespace App\Models\BotAutomation;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BotSupportConversation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'automation_id',
        'customer_id',
        'assigned_agent_id',
        'ticket_number',
        'subject',
        'priority',
        'status',
        'channel',
        'initial_message',
        'tags',
        'category',
        'is_ai_handled',
        'requires_human',
        'ai_confidence_score',
        'ai_suggested_response',
        'message_count',
        'first_response_at',
        'resolved_at',
        'closed_at',
        'resolution_time_minutes',
        'customer_satisfaction_rating',
        'customer_feedback',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_ai_handled' => 'boolean',
        'requires_human' => 'boolean',
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Get the automation
     */
    public function automation(): BelongsTo
    {
        return $this->belongsTo(BotAutomation::class);
    }

    /**
     * Get the customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the assigned agent
     */
    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    /**
     * Get all messages
     */
    public function messages(): HasMany
    {
        return $this->hasMany(BotSupportMessage::class, 'conversation_id');
    }

    /**
     * Scope: Open conversations
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'pending', 'in_progress']);
    }

    /**
     * Scope: Resolved conversations
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Scope: AI handled
     */
    public function scopeAiHandled($query)
    {
        return $query->where('is_ai_handled', true);
    }

    /**
     * Scope: Requires human
     */
    public function scopeRequiresHuman($query)
    {
        return $query->where('requires_human', true);
    }

    /**
     * Assign to agent
     */
    public function assignToAgent(int $agentId): void
    {
        $this->update([
            'assigned_agent_id' => $agentId,
            'status' => 'in_progress',
        ]);
    }

    /**
     * Mark as resolved
     */
    public function resolve(): void
    {
        $resolutionTime = $this->created_at->diffInMinutes(now());

        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_time_minutes' => $resolutionTime,
        ]);
    }

    /**
     * Close conversation
     */
    public function close(): void
    {
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    /**
     * Flag for human assistance
     */
    public function flagForHuman(): void
    {
        $this->update([
            'requires_human' => true,
            'is_ai_handled' => false,
        ]);
    }

    /**
     * Generate ticket number
     */
    public static function generateTicketNumber(): string
    {
        return 'SUP-' . strtoupper(uniqid());
    }

    /**
     * Boot method
     */
    protected static function booted()
    {
        static::creating(function ($conversation) {
            if (!$conversation->ticket_number) {
                $conversation->ticket_number = self::generateTicketNumber();
            }
        });
    }
}
