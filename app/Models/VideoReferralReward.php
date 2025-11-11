<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoReferralReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_id',
        'referred_id',
        'reward_type',
        'coins_amount',
        'exp_amount',
        'money_amount',
        'percentage',
        'milestone_value',
        'claimed',
        'claimed_at',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'coins_amount' => 'decimal:2',
        'exp_amount' => 'integer',
        'money_amount' => 'decimal:2',
        'percentage' => 'decimal:2',
        'milestone_value' => 'integer',
        'claimed' => 'boolean',
        'claimed_at' => 'datetime',
    ];

    /**
     * Get the referrer (user who invited)
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * Get the referred user (who was invited)
     */
    public function referred()
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    /**
     * Claim reward
     */
    public function claim()
    {
        if ($this->claimed) {
            return null;
        }

        $this->claimed = true;
        $this->claimed_at = now();
        $this->save();

        $rewards = [];

        // Award coins
        if ($this->coins_amount > 0) {
            $videoCoin = VideoCoin::firstOrCreate(['user_id' => $this->referrer_id]);
            $videoCoin->addCoins(
                $this->coins_amount,
                'earned_referral',
                'VideoReferralReward',
                $this->id,
                "Referral reward: {$this->reward_type}"
            );
            $rewards['coins'] = $this->coins_amount;
        }

        // Award experience
        if ($this->exp_amount > 0) {
            $userLevel = UserVideoLevel::where('user_id', $this->referrer_id)->first();
            if ($userLevel) {
                $userLevel->addExp($this->exp_amount);
            }
            $rewards['exp'] = $this->exp_amount;
        }

        // Award money directly to wallet
        if ($this->money_amount > 0) {
            $wallet = Wallet::firstOrCreate(['user_id' => $this->referrer_id]);

            WalletTransaction::create([
                'user_id' => $this->referrer_id,
                'wallet_id' => $wallet->id,
                'type' => 'credit',
                'amount' => $this->money_amount,
                'balance_before' => $wallet->balance,
                'balance_after' => $wallet->balance + $this->money_amount,
                'description' => "Video referral reward: {$this->reward_type}",
                'reference_type' => 'VideoReferralReward',
                'reference_id' => $this->id,
                'status' => 'completed',
            ]);

            $wallet->balance += $this->money_amount;
            $wallet->total_income += $this->money_amount;
            $wallet->save();

            $rewards['money'] = $this->money_amount;
        }

        return $rewards;
    }

    /**
     * Auto-claim all unclaimed rewards for a user
     */
    public static function autoClaimForUser($userId)
    {
        $unclaimedRewards = static::where('referrer_id', $userId)
            ->where('claimed', false)
            ->get();

        $totalRewards = [
            'coins' => 0,
            'exp' => 0,
            'money' => 0,
        ];

        foreach ($unclaimedRewards as $reward) {
            $claimed = $reward->claim();
            if ($claimed) {
                $totalRewards['coins'] += $claimed['coins'] ?? 0;
                $totalRewards['exp'] += $claimed['exp'] ?? 0;
                $totalRewards['money'] += $claimed['money'] ?? 0;
            }
        }

        return $totalRewards;
    }

    /**
     * Scope for unclaimed rewards
     */
    public function scopeUnclaimed($query)
    {
        return $query->where('claimed', false);
    }

    /**
     * Scope for rewards by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('reward_type', $type);
    }
}
