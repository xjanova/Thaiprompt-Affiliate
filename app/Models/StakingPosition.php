<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class StakingPosition extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'position_number',
        'user_id',
        'wallet_id',
        'investment_plan_id',
        'amount',
        'roi_rate',
        'roi_frequency',
        'term_days',
        'lock_days',
        'expected_roi',
        'earned_roi',
        'pending_roi',
        'total_withdrawn',
        'invested_at',
        'unlock_date',
        'maturity_date',
        'last_roi_distribution_at',
        'withdrawn_at',
        'status',
        'auto_compound',
        'compound_amount',
        'compound_count',
        'referrer_id',
        'referral_bonus',
        'rank_bonus_multiplier',
        'approved_by',
        'approved_at',
        'admin_notes',
        'early_withdrawal_penalty',
        'withdrawal_reason',
        'metadata',
        'transaction_hash',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'roi_rate' => 'decimal:4',
        'expected_roi' => 'decimal:2',
        'earned_roi' => 'decimal:2',
        'pending_roi' => 'decimal:2',
        'total_withdrawn' => 'decimal:2',
        'compound_amount' => 'decimal:2',
        'referral_bonus' => 'decimal:2',
        'rank_bonus_multiplier' => 'decimal:2',
        'early_withdrawal_penalty' => 'decimal:2',
        'auto_compound' => 'boolean',
        'invested_at' => 'datetime',
        'unlock_date' => 'datetime',
        'maturity_date' => 'datetime',
        'last_roi_distribution_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'approved_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($position) {
            if (!$position->position_number) {
                $position->position_number = 'STK' . strtoupper(uniqid());
            }
        });
    }

    /**
     * Get the user who owns this position
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the wallet for this position
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the investment plan
     */
    public function investmentPlan(): BelongsTo
    {
        return $this->belongsTo(InvestmentPlan::class);
    }

    /**
     * Get the referrer
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * Get the admin who approved
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get all ROI distributions
     */
    public function roiDistributions(): HasMany
    {
        return $this->hasMany(RoiDistribution::class);
    }

    /**
     * Get completed ROI distributions
     */
    public function completedDistributions(): HasMany
    {
        return $this->hasMany(RoiDistribution::class)->where('status', 'completed');
    }

    /**
     * Get pending ROI distributions
     */
    public function pendingDistributions(): HasMany
    {
        return $this->hasMany(RoiDistribution::class)->where('status', 'pending');
    }

    /**
     * Scope for active positions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for locked positions
     */
    public function scopeLocked($query)
    {
        return $query->where('status', 'locked');
    }

    /**
     * Scope for matured positions
     */
    public function scopeMatured($query)
    {
        return $query->where('status', 'matured');
    }

    /**
     * Scope for pending positions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for positions ready for ROI distribution
     */
    public function scopeReadyForDistribution($query)
    {
        return $query->whereIn('status', ['active', 'locked'])
            ->where(function ($q) {
                $q->whereNull('last_roi_distribution_at')
                    ->orWhereDate('last_roi_distribution_at', '<', now()->toDateString());
            });
    }

    /**
     * Scope for positions that should be matured
     */
    public function scopeShouldBeMature($query)
    {
        return $query->whereIn('status', ['active', 'locked'])
            ->where('maturity_date', '<=', now());
    }

    /**
     * Check if position is locked
     */
    public function isLocked(): bool
    {
        if ($this->status !== 'active' && $this->status !== 'locked') {
            return false;
        }

        return $this->unlock_date && now()->lt($this->unlock_date);
    }

    /**
     * Check if position is matured
     */
    public function isMatured(): bool
    {
        return $this->maturity_date && now()->gte($this->maturity_date);
    }

    /**
     * Check if can withdraw
     */
    public function canWithdraw(): bool
    {
        if ($this->status === 'withdrawn' || $this->status === 'cancelled') {
            return false;
        }

        // Check if unlocked
        if ($this->isLocked()) {
            $plan = $this->investmentPlan;
            return $plan && $plan->allow_early_withdrawal;
        }

        return true;
    }

    /**
     * Get days elapsed since investment
     */
    public function getDaysElapsedAttribute(): int
    {
        if (!$this->invested_at) {
            return 0;
        }

        return $this->invested_at->diffInDays(now());
    }

    /**
     * Get days remaining until maturity
     */
    public function getDaysRemainingAttribute(): int
    {
        if (!$this->maturity_date) {
            return 0;
        }

        if ($this->isMatured()) {
            return 0;
        }

        return now()->diffInDays($this->maturity_date);
    }

    /**
     * Get days until unlock
     */
    public function getDaysUntilUnlockAttribute(): int
    {
        if (!$this->unlock_date) {
            return 0;
        }

        if (!$this->isLocked()) {
            return 0;
        }

        return now()->diffInDays($this->unlock_date);
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->term_days <= 0) {
            return 0;
        }

        $elapsed = $this->days_elapsed;
        $progress = ($elapsed / $this->term_days) * 100;

        return min(100, max(0, $progress));
    }

    /**
     * Get ROI progress percentage
     */
    public function getRoiProgressPercentageAttribute(): float
    {
        if ($this->expected_roi <= 0) {
            return 0;
        }

        $progress = ($this->earned_roi / $this->expected_roi) * 100;

        return min(100, max(0, $progress));
    }

    /**
     * Get remaining ROI
     */
    public function getRemainingRoiAttribute(): float
    {
        return max(0, $this->expected_roi - $this->earned_roi);
    }

    /**
     * Get total value (principal + earned ROI)
     */
    public function getTotalValueAttribute(): float
    {
        return $this->amount + $this->earned_roi;
    }

    /**
     * Calculate early withdrawal amount
     */
    public function calculateEarlyWithdrawalAmount(): array
    {
        $plan = $this->investmentPlan;
        $penalty = 0;
        $netAmount = $this->amount + $this->earned_roi;

        if ($plan && $plan->early_withdrawal_penalty > 0) {
            $penalty = $netAmount * ($plan->early_withdrawal_penalty / 100);
            $netAmount -= $penalty;
        }

        return [
            'principal' => $this->amount,
            'earned_roi' => $this->earned_roi,
            'total' => $this->amount + $this->earned_roi,
            'penalty' => $penalty,
            'net_amount' => $netAmount,
        ];
    }

    /**
     * Approve position
     */
    public function approve(User $admin): void
    {
        $this->update([
            'status' => 'active',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'invested_at' => now(),
            'unlock_date' => now()->addDays($this->lock_days),
            'maturity_date' => now()->addDays($this->term_days),
        ]);
    }

    /**
     * Mark as matured
     */
    public function markAsMatured(): void
    {
        $this->update([
            'status' => 'matured',
        ]);
    }

    /**
     * Add earned ROI
     */
    public function addEarnedROI(float $amount): void
    {
        $this->increment('earned_roi', $amount);
        $this->update([
            'last_roi_distribution_at' => now(),
        ]);
    }

    /**
     * Mark as withdrawn
     */
    public function markAsWithdrawn(bool $isEarly = false): void
    {
        $this->update([
            'status' => $isEarly ? 'early_withdrawn' : 'withdrawn',
            'withdrawn_at' => now(),
        ]);
    }
}
