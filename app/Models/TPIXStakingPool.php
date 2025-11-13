<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TPIXStakingPool extends Model
{
    use HasFactory;

    protected $table = 'tpix_staking_pools';

    protected $fillable = [
        'token_id',
        'name',
        'description',
        'reward_token_id',
        'apy',
        'lock_period_days',
        'min_stake_amount',
        'max_stake_amount',
        'total_staked',
        'total_rewards_distributed',
        'stakers_count',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function token()
    {
        return $this->belongsTo(TPIXToken::class, 'token_id');
    }

    public function rewardToken()
    {
        return $this->belongsTo(TPIXToken::class, 'reward_token_id');
    }

    public function stakes()
    {
        return $this->hasMany(TPIXStake::class, 'pool_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', now());
            })
            ->where(function($q) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>=', now());
            });
    }

    public function scopeUpcoming($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '>', now());
    }

    public function scopeEnded($query)
    {
        return $query->where('ends_at', '<', now());
    }

    /**
     * Helpers
     */
    public function isActiveNow(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at > now()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at < now()) {
            return false;
        }

        return true;
    }

    public function canStake(string $amount): bool
    {
        if (!$this->isActiveNow()) {
            return false;
        }

        if ($this->min_stake_amount && bccomp($amount, $this->min_stake_amount, 8) < 0) {
            return false;
        }

        if ($this->max_stake_amount && bccomp($amount, $this->max_stake_amount, 8) > 0) {
            return false;
        }

        return true;
    }

    public function calculateRewards(string $amount, int $durationDays): string
    {
        // Simple APY calculation
        // Reward = (Amount × APY × Days) / (365 × 100)
        $apy = (string)$this->apy;
        $days = (string)$durationDays;

        $reward = bcmul($amount, $apy, 8);
        $reward = bcmul($reward, $days, 8);
        $reward = bcdiv($reward, '36500', 8);

        return $reward;
    }

    public function getDailyRewardRate(): string
    {
        return bcdiv((string)$this->apy, '365', 8);
    }

    public function getFilledPercentage(): float
    {
        if (!$this->max_stake_amount || $this->max_stake_amount == 0) {
            return 0;
        }

        return ($this->total_staked / $this->max_stake_amount) * 100;
    }
}
