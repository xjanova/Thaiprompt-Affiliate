<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TPIXStake extends Model
{
    use HasFactory;

    protected $table = 'tpix_stakes';

    protected $fillable = [
        'pool_id',
        'user_id',
        'amount',
        'staked_amount',  // Alias for amount
        'lock_period_days',
        'apy',
        'rewards_earned',
        'rewards_claimed',
        'status',
        'staked_at',
        'unlock_at',
        'unlock_date',  // Alias for unlock_at
        'unstaked_at',
        'last_reward_claim_at',
        'stake_tx_hash',
        'blockchain_tx_hash',  // Alias for stake_tx_hash
        'unstake_tx_hash',
    ];

    protected $casts = [
        'staked_at' => 'datetime',
        'unlock_at' => 'datetime',
        'unlock_date' => 'datetime',
        'unstaked_at' => 'datetime',
        'last_reward_claim_at' => 'datetime',
        'apy' => 'decimal:2',
        'lock_period_days' => 'integer',
    ];

    /**
     * Accessors & Mutators for field aliases
     */
    public function getStakedAmountAttribute($value)
    {
        return $value ?? $this->attributes['amount'] ?? null;
    }

    public function setStakedAmountAttribute($value)
    {
        $this->attributes['amount'] = $value;
        $this->attributes['staked_amount'] = $value;
    }

    public function getUnlockDateAttribute($value)
    {
        return $value ?? $this->attributes['unlock_at'] ?? null;
    }

    public function setUnlockDateAttribute($value)
    {
        $this->attributes['unlock_at'] = $value;
        $this->attributes['unlock_date'] = $value;
    }

    public function getBlockchainTxHashAttribute($value)
    {
        return $value ?? $this->attributes['stake_tx_hash'] ?? null;
    }

    public function setBlockchainTxHashAttribute($value)
    {
        $this->attributes['stake_tx_hash'] = $value;
        $this->attributes['blockchain_tx_hash'] = $value;
    }

    /**
     * Relationships
     */
    public function pool()
    {
        return $this->belongsTo(TPIXStakingPool::class, 'pool_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeUnstaked($query)
    {
        return $query->where('status', 'unstaked');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeUnlocked($query)
    {
        return $query->where('status', 'active')
            ->where('unlock_at', '<=', now());
    }

    public function scopeLocked($query)
    {
        return $query->where('status', 'active')
            ->where('unlock_at', '>', now());
    }

    /**
     * Helpers
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isUnstaked(): bool
    {
        return $this->status === 'unstaked';
    }

    public function isLocked(): bool
    {
        return $this->isActive() && $this->unlock_at > now();
    }

    public function isUnlocked(): bool
    {
        return $this->isActive() && $this->unlock_at <= now();
    }

    public function canUnstake(): bool
    {
        return $this->isUnlocked();
    }

    public function getDaysStaked(): int
    {
        $endDate = $this->unstaked_at ?? now();
        return $this->staked_at->diffInDays($endDate);
    }

    public function getDaysUntilUnlock(): int
    {
        if ($this->isUnlocked()) {
            return 0;
        }

        return now()->diffInDays($this->unlock_at);
    }

    public function calculateCurrentRewards(): string
    {
        if (!$this->isActive()) {
            return $this->rewards_earned;
        }

        $daysStaked = $this->getDaysStaked();
        $pool = $this->pool;

        if (!$pool) {
            return '0';
        }

        return $pool->calculateRewards($this->amount, $daysStaked);
    }

    public function updateRewards()
    {
        $newRewards = $this->calculateCurrentRewards();
        $this->update(['rewards_earned' => $newRewards]);
    }

    public function claimRewards(string $amount): bool
    {
        if (bccomp($amount, $this->getUnclaimedRewards(), 8) > 0) {
            return false;
        }

        $this->increment('rewards_claimed', $amount);
        return true;
    }

    public function getUnclaimedRewards(): string
    {
        $this->updateRewards();
        return bcsub($this->rewards_earned, $this->rewards_claimed, 8);
    }

    public function unstake()
    {
        if (!$this->canUnstake()) {
            throw new \Exception('Cannot unstake yet - still locked');
        }

        $this->update([
            'status' => 'unstaked',
            'unstaked_at' => now(),
        ]);

        // Update pool stats
        $this->pool->decrement('total_staked', $this->amount);
        $this->pool->decrement('stakers_count');
    }
}
