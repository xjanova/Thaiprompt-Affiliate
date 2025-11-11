<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGenUsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider_id',
        'subscription_id',
        'generation_type',
        'prompt',
        'parameters',
        'credits_used',
        'is_free_quota',
        'status',
        'result_url',
        'result_data',
        'error_message',
        'ip_address',
        'user_agent',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'parameters' => 'array',
        'result_data' => 'array',
        'credits_used' => 'integer',
        'is_free_quota' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the usage log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the provider for this usage log.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiGenProvider::class, 'provider_id');
    }

    /**
     * Get the subscription for this usage log.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(AiGenSubscription::class, 'subscription_id');
    }

    /**
     * Get duration in seconds.
     */
    public function getDurationAttribute(): ?int
    {
        if ($this->started_at && $this->completed_at) {
            return $this->completed_at->diffInSeconds($this->started_at);
        }
        return null;
    }

    /**
     * Scope for successful generations.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed generations.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for free quota usage.
     */
    public function scopeFreeQuota($query)
    {
        return $query->where('is_free_quota', true);
    }

    /**
     * Scope for paid usage.
     */
    public function scopePaid($query)
    {
        return $query->where('is_free_quota', false);
    }
}
