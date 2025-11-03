<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'tokens_used',
        'model_used',
        'provider_used',
        'response_time_ms',
        'function_name',
        'function_arguments',
        'function_response',
        'context_used',
    ];

    protected $casts = [
        'function_arguments' => 'array',
        'function_response' => 'array',
        'context_used' => 'array',
    ];

    /**
     * Valid message roles
     */
    const ROLE_SYSTEM = 'system';
    const ROLE_USER = 'user';
    const ROLE_ASSISTANT = 'assistant';
    const ROLE_FUNCTION = 'function';

    /**
     * Get the conversation that owns this message
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }

    /**
     * Scope: Filter by role
     */
    public function scopeOfRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope: User messages
     */
    public function scopeUserMessages($query)
    {
        return $query->where('role', self::ROLE_USER);
    }

    /**
     * Scope: Assistant messages
     */
    public function scopeAssistantMessages($query)
    {
        return $query->where('role', self::ROLE_ASSISTANT);
    }

    /**
     * Scope: Function call messages
     */
    public function scopeFunctionCalls($query)
    {
        return $query->where('role', self::ROLE_FUNCTION)
                     ->orWhereNotNull('function_name');
    }

    /**
     * Scope: With context (RAG)
     */
    public function scopeWithContext($query)
    {
        return $query->whereNotNull('context_used');
    }

    /**
     * Check if this is a function call
     */
    public function isFunctionCall(): bool
    {
        return !empty($this->function_name);
    }

    /**
     * Check if this message used RAG context
     */
    public function usedContext(): bool
    {
        return !empty($this->context_used);
    }

    /**
     * Get formatted message for API
     */
    public function toApiFormat(): array
    {
        $message = [
            'role' => $this->role,
            'content' => $this->content,
        ];

        if ($this->isFunctionCall()) {
            $message['function_call'] = [
                'name' => $this->function_name,
                'arguments' => $this->function_arguments,
            ];
        }

        return $message;
    }

    /**
     * Get content preview (truncated)
     */
    public function getContentPreview(int $length = 100): string
    {
        if (mb_strlen($this->content) <= $length) {
            return $this->content;
        }

        return mb_substr($this->content, 0, $length) . '...';
    }

    /**
     * Calculate cost based on tokens and pricing
     */
    public function calculateCost(array $pricing): float
    {
        if (!$this->tokens_used || empty($pricing)) {
            return 0.0;
        }

        // Pricing structure: ['input' => price_per_1k_tokens, 'output' => price_per_1k_tokens]
        if ($this->role === self::ROLE_USER) {
            $pricePerToken = ($pricing['input'] ?? 0) / 1000;
        } else {
            $pricePerToken = ($pricing['output'] ?? 0) / 1000;
        }

        return $this->tokens_used * $pricePerToken;
    }

    /**
     * Get word count
     */
    public function getWordCount(): int
    {
        return str_word_count($this->content);
    }

    /**
     * Get character count
     */
    public function getCharacterCount(): int
    {
        return mb_strlen($this->content);
    }
}
