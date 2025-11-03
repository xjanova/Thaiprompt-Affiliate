<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorMarketingCampaign extends Model
{
    protected $fillable = [
        'store_id',
        'campaign_name',
        'campaign_type',
        'description',
        'target_type',
        'target_segment',
        'content',
        'scheduled_at',
        'started_at',
        'ended_at',
        'budget',
        'spent',
        'sent_count',
        'opened_count',
        'clicked_count',
        'conversion_count',
        'revenue_generated',
        'status',
    ];

    protected $casts = [
        'target_segment' => 'array',
        'content' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'budget' => 'decimal:2',
        'spent' => 'decimal:2',
        'sent_count' => 'integer',
        'opened_count' => 'integer',
        'clicked_count' => 'integer',
        'conversion_count' => 'integer',
        'revenue_generated' => 'decimal:2',
    ];

    /**
     * Get the store
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    /**
     * Scope: Filter by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Active campaigns
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope: Scheduled campaigns
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Start campaign
     */
    public function start(): bool
    {
        $this->status = 'running';
        $this->started_at = now();
        return $this->save();
    }

    /**
     * Complete campaign
     */
    public function complete(): bool
    {
        $this->status = 'completed';
        $this->ended_at = now();
        return $this->save();
    }

    /**
     * Cancel campaign
     */
    public function cancel(): bool
    {
        $this->status = 'cancelled';
        return $this->save();
    }

    /**
     * Get open rate percentage
     */
    public function getOpenRateAttribute(): float
    {
        if ($this->sent_count === 0) {
            return 0;
        }

        return round(($this->opened_count / $this->sent_count) * 100, 2);
    }

    /**
     * Get click rate percentage
     */
    public function getClickRateAttribute(): float
    {
        if ($this->sent_count === 0) {
            return 0;
        }

        return round(($this->clicked_count / $this->sent_count) * 100, 2);
    }

    /**
     * Get conversion rate percentage
     */
    public function getConversionRateAttribute(): float
    {
        if ($this->clicked_count === 0) {
            return 0;
        }

        return round(($this->conversion_count / $this->clicked_count) * 100, 2);
    }

    /**
     * Get ROI percentage
     */
    public function getRoiAttribute(): ?float
    {
        if (!$this->budget || $this->budget == 0) {
            return null;
        }

        $profit = $this->revenue_generated - $this->spent;
        return round(($profit / $this->spent) * 100, 2);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'scheduled' => 'blue',
            'running' => 'green',
            'completed' => 'indigo',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get campaign type display
     */
    public function getTypeDisplayAttribute(): string
    {
        return match($this->campaign_type) {
            'email' => 'อีเมล',
            'line_broadcast' => 'LINE Broadcast',
            'discount' => 'ส่วนลด',
            'promotion' => 'โปรโมชั่น',
            default => $this->campaign_type,
        };
    }
}
