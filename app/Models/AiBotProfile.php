<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiBotProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'owner_id',
        'provider_id',
        'model_id',
        'name',
        'description',
        'display_name',
        'avatar_url',
        'system_prompt',
        'temperature',
        'max_tokens',
        'top_p',
        'frequency_penalty',
        'presence_penalty',
        'response_scope',
        'enable_knowledge_base',
        'enable_web_search',
        'enable_code_interpreter',
        'line_oa_channel_id',
        'line_oa_channel_secret',
        'line_oa_access_token',
        'is_public',
        'is_rentable',
        'is_active',
        'rental_price_per_month',
        'rental_price_per_message',
        'commission_rate',
    ];

    protected $casts = [
        'temperature' => 'decimal:2',
        'top_p' => 'decimal:2',
        'frequency_penalty' => 'decimal:2',
        'presence_penalty' => 'decimal:2',
        'response_scope' => 'array',
        'enable_knowledge_base' => 'boolean',
        'enable_web_search' => 'boolean',
        'enable_code_interpreter' => 'boolean',
        'is_public' => 'boolean',
        'is_rentable' => 'boolean',
        'is_active' => 'boolean',
        'rental_price_per_month' => 'decimal:2',
        'rental_price_per_message' => 'decimal:4',
        'commission_rate' => 'decimal:2',
    ];

    /**
     * Get the owner of this bot
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the AI provider
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'provider_id');
    }

    /**
     * Get the AI model
     */
    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'model_id');
    }

    /**
     * Get all knowledge bases for this bot
     */
    public function knowledgeBases(): HasMany
    {
        return $this->hasMany(KnowledgeBase::class, 'bot_profile_id');
    }

    /**
     * Get all rentals for this bot
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(AiBotRental::class, 'bot_profile_id');
    }

    /**
     * Get active rentals
     */
    public function activeRentals(): HasMany
    {
        return $this->rentals()->where('is_active', true);
    }

    /**
     * Get all conversations using this bot
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(AiConversation::class, 'bot_profile_id');
    }

    /**
     * Get usage logs
     */
    public function usageLogs(): HasMany
    {
        return $this->hasMany(AiUsageLog::class, 'bot_profile_id');
    }

    /**
     * Scope: Only active bots
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Only public bots
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope: Only rentable bots
     */
    public function scopeRentable($query)
    {
        return $query->where('is_rentable', true)
                     ->where('is_active', true);
    }

    /**
     * Check if bot is rentable
     */
    public function isRentable(): bool
    {
        return $this->is_rentable && $this->is_active && $this->is_public;
    }

    /**
     * Check if bot has LINE OA configured
     */
    public function hasLineOaConfigured(): bool
    {
        return !empty($this->line_oa_channel_id)
            && !empty($this->line_oa_channel_secret)
            && !empty($this->line_oa_access_token);
    }

    /**
     * Get full display name with provider
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->display_name} ({$this->provider->display_name} - {$this->model->display_name})";
    }

    /**
     * Calculate total usage cost for a period
     */
    public function calculateUsageCost($startDate = null, $endDate = null): float
    {
        $query = $this->usageLogs();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query->sum('cost');
    }

    /**
     * Get total rental income
     */
    public function getTotalRentalIncome(): float
    {
        return $this->rentals()
            ->where('is_active', true)
            ->sum('owner_earning');
    }

    /**
     * Get active rental count
     */
    public function getActiveRentalCount(): int
    {
        return $this->activeRentals()->count();
    }
}
