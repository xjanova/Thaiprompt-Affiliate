<?php

namespace App\Models\BotAutomation;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotSalesMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'sender_type',
        'message_type',
        'message',
        'product_data',
        'attachments',
        'is_ai_generated',
        'ai_prompt_used',
        'tokens_used',
        'intent_detected',
        'entities_extracted',
        'sentiment_score',
        'read_at',
        'clicked_at',
    ];

    protected $casts = [
        'product_data' => 'array',
        'attachments' => 'array',
        'is_ai_generated' => 'boolean',
        'entities_extracted' => 'array',
        'read_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    /**
     * Get the conversation
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(BotSalesConversation::class);
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
     * Mark as clicked (for product recommendations)
     */
    public function markAsClicked(): void
    {
        if (!$this->clicked_at) {
            $this->update(['clicked_at' => now()]);
        }
    }

    /**
     * Scope: Product recommendations
     */
    public function scopeProductRecommendations($query)
    {
        return $query->where('message_type', 'product_recommendation');
    }

    /**
     * Scope: AI generated
     */
    public function scopeAiGenerated($query)
    {
        return $query->where('is_ai_generated', true);
    }

    /**
     * Scope: By intent
     */
    public function scopeByIntent($query, string $intent)
    {
        return $query->where('intent_detected', $intent);
    }

    /**
     * Get sentiment label
     */
    public function getSentimentLabel(): string
    {
        if ($this->sentiment_score === null) {
            return 'neutral';
        }

        if ($this->sentiment_score > 50) {
            return 'positive';
        } elseif ($this->sentiment_score < -50) {
            return 'negative';
        }

        return 'neutral';
    }

    /**
     * Boot method to update conversation
     */
    protected static function booted()
    {
        static::created(function ($message) {
            $conversation = $message->conversation;
            $conversation->increment('message_count');
            $conversation->updateLastInteraction();

            // Increment product views if it's a product recommendation
            if ($message->message_type === 'product_recommendation') {
                $conversation->increment('product_views');
                $conversation->increment('products_recommended');
            }
        });
    }
}
