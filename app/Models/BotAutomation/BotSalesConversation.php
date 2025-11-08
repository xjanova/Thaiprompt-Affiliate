<?php

namespace App\Models\BotAutomation;

use App\Models\User;
use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BotSalesConversation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'automation_id',
        'customer_id',
        'assigned_sales_rep_id',
        'conversation_id',
        'stage',
        'status',
        'channel',
        'customer_interests',
        'recommended_products',
        'estimated_order_value',
        'ai_confidence_score',
        'is_ai_handled',
        'requires_human',
        'ai_next_action',
        'message_count',
        'product_views',
        'products_recommended',
        'first_contact_at',
        'last_interaction_at',
        'converted_at',
        'order_id',
        'actual_order_value',
        'metadata',
    ];

    protected $casts = [
        'customer_interests' => 'array',
        'recommended_products' => 'array',
        'estimated_order_value' => 'decimal:2',
        'is_ai_handled' => 'boolean',
        'requires_human' => 'boolean',
        'first_contact_at' => 'datetime',
        'last_interaction_at' => 'datetime',
        'converted_at' => 'datetime',
        'actual_order_value' => 'decimal:2',
        'metadata' => 'array',
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
     * Get the assigned sales rep
     */
    public function assignedSalesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_sales_rep_id');
    }

    /**
     * Get the order (if converted)
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get all messages
     */
    public function messages(): HasMany
    {
        return $this->hasMany(BotSalesMessage::class, 'conversation_id');
    }

    /**
     * Scope: Active conversations
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Converted conversations
     */
    public function scopeConverted($query)
    {
        return $query->where('status', 'converted');
    }

    /**
     * Scope: By stage
     */
    public function scopeByStage($query, string $stage)
    {
        return $query->where('stage', $stage);
    }

    /**
     * Move to next stage
     */
    public function advanceStage(): void
    {
        $stages = ['awareness', 'interest', 'consideration', 'intent', 'purchase'];
        $currentIndex = array_search($this->stage, $stages);

        if ($currentIndex !== false && $currentIndex < count($stages) - 1) {
            $this->update(['stage' => $stages[$currentIndex + 1]]);
        }
    }

    /**
     * Mark as converted
     */
    public function markAsConverted(int $orderId, float $orderValue): void
    {
        $this->update([
            'status' => 'converted',
            'stage' => 'purchase',
            'converted_at' => now(),
            'order_id' => $orderId,
            'actual_order_value' => $orderValue,
        ]);
    }

    /**
     * Mark as abandoned
     */
    public function markAsAbandoned(): void
    {
        $this->update([
            'status' => 'abandoned',
            'stage' => 'lost',
        ]);
    }

    /**
     * Assign to sales rep
     */
    public function assignToSalesRep(int $salesRepId): void
    {
        $this->update([
            'assigned_sales_rep_id' => $salesRepId,
            'is_ai_handled' => false,
        ]);
    }

    /**
     * Update last interaction
     */
    public function updateLastInteraction(): void
    {
        $this->update(['last_interaction_at' => now()]);
    }

    /**
     * Generate conversation ID
     */
    public static function generateConversationId(): string
    {
        return 'SALES-' . strtoupper(uniqid());
    }

    /**
     * Boot method
     */
    protected static function booted()
    {
        static::creating(function ($conversation) {
            if (!$conversation->conversation_id) {
                $conversation->conversation_id = self::generateConversationId();
            }
            if (!$conversation->first_contact_at) {
                $conversation->first_contact_at = now();
            }
        });
    }
}
