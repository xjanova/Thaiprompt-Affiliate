<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvestmentPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'name_th',
        'description',
        'description_th',
        'min_amount',
        'max_amount',
        'roi_rate',
        'roi_frequency',
        'term_days',
        'lock_days',
        'allow_compound',
        'allow_early_withdrawal',
        'early_withdrawal_penalty',
        'referral_bonus_rate',
        'is_active',
        'is_featured',
        'max_investors',
        'current_investors',
        'sort_order',
        'required_rank_id',
        'requires_kyc',
        'features',
        'terms_conditions',
        'icon',
        'color',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'roi_rate' => 'decimal:4',
        'early_withdrawal_penalty' => 'decimal:2',
        'referral_bonus_rate' => 'decimal:2',
        'allow_compound' => 'boolean',
        'allow_early_withdrawal' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'requires_kyc' => 'boolean',
        'features' => 'array',
        'terms_conditions' => 'array',
    ];

    /**
     * Get the rank required for this plan
     */
    public function requiredRank(): BelongsTo
    {
        return $this->belongsTo(Rank::class, 'required_rank_id');
    }

    /**
     * Get all staking positions for this plan
     */
    public function stakingPositions(): HasMany
    {
        return $this->hasMany(StakingPosition::class);
    }

    /**
     * Get active staking positions
     */
    public function activePositions(): HasMany
    {
        return $this->hasMany(StakingPosition::class)->where('status', 'active');
    }

    /**
     * Scope for active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured plans
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for available plans (not full)
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('max_investors')
                    ->orWhereRaw('current_investors < max_investors');
            });
    }

    /**
     * Check if user can invest in this plan
     */
    public function canUserInvest(User $user): bool
    {
        // Check if plan is active
        if (!$this->is_active) {
            return false;
        }

        // Check if plan is full
        if ($this->max_investors && $this->current_investors >= $this->max_investors) {
            return false;
        }

        // Check KYC requirement
        if ($this->requires_kyc && $user->kyc_status !== 'verified') {
            return false;
        }

        // Check rank requirement
        if ($this->required_rank_id && (!$user->current_rank_id || $user->currentRank->level < $this->requiredRank->level)) {
            return false;
        }

        return true;
    }

    /**
     * Calculate expected ROI for given amount
     */
    public function calculateExpectedROI(float $amount): float
    {
        $dailyRate = $this->roi_rate / 100;

        switch ($this->roi_frequency) {
            case 'daily':
                $distributionCount = $this->term_days;
                break;
            case 'weekly':
                $distributionCount = ceil($this->term_days / 7);
                $dailyRate *= 7;
                break;
            case 'monthly':
                $distributionCount = ceil($this->term_days / 30);
                $dailyRate *= 30;
                break;
            default:
                $distributionCount = $this->term_days;
        }

        return $amount * $dailyRate * $distributionCount;
    }

    /**
     * Calculate daily ROI amount
     */
    public function calculateDailyROI(float $amount): float
    {
        $rate = $this->roi_rate / 100;

        switch ($this->roi_frequency) {
            case 'daily':
                return $amount * $rate;
            case 'weekly':
                return ($amount * $rate * 7) / 7;
            case 'monthly':
                return ($amount * $rate * 30) / 30;
            default:
                return $amount * $rate;
        }
    }

    /**
     * Increment investor count
     */
    public function incrementInvestors(): void
    {
        $this->increment('current_investors');
    }

    /**
     * Decrement investor count
     */
    public function decrementInvestors(): void
    {
        $this->decrement('current_investors');
    }

    /**
     * Get display name based on locale
     */
    public function getDisplayNameAttribute(): string
    {
        return app()->getLocale() === 'th' && $this->name_th
            ? $this->name_th
            : $this->name;
    }

    /**
     * Get display description based on locale
     */
    public function getDisplayDescriptionAttribute(): ?string
    {
        return app()->getLocale() === 'th' && $this->description_th
            ? $this->description_th
            : $this->description;
    }

    /**
     * Check if plan has space for new investors
     */
    public function hasSpace(): bool
    {
        if (!$this->max_investors) {
            return true;
        }

        return $this->current_investors < $this->max_investors;
    }

    /**
     * Get total invested amount in this plan
     */
    public function getTotalInvestedAttribute(): float
    {
        return $this->stakingPositions()
            ->whereIn('status', ['active', 'locked', 'matured'])
            ->sum('amount');
    }

    /**
     * Get total ROI paid from this plan
     */
    public function getTotalRoiPaidAttribute(): float
    {
        return $this->stakingPositions()
            ->sum('earned_roi');
    }
}
