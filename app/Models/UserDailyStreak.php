<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserDailyStreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'current_streak',
        'longest_streak',
        'last_claim_date',
        'total_claims',
        'total_rewards',
    ];

    protected $casts = [
        'last_claim_date' => 'date',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function claims()
    {
        return $this->hasMany(DailyRewardClaim::class, 'user_id', 'user_id');
    }

    /**
     * Check if user can claim today
     */
    public function canClaimToday(): bool
    {
        if (!$this->last_claim_date) {
            return true;
        }

        return !$this->last_claim_date->isToday();
    }

    /**
     * Get reward for current streak day
     */
    public function getTodayReward(): array
    {
        $dayNumber = $this->current_streak + 1;

        // Define reward tiers
        $rewards = [
            1 => ['type' => 'coins', 'amount' => 10],
            2 => ['type' => 'coins', 'amount' => 15],
            3 => ['type' => 'coins', 'amount' => 20],
            4 => ['type' => 'coins', 'amount' => 25],
            5 => ['type' => 'coins', 'amount' => 30],
            6 => ['type' => 'coins', 'amount' => 40],
            7 => ['type' => 'coins', 'amount' => 50, 'bonus' => true],
        ];

        // For days beyond 7, repeat cycle with increasing rewards
        $cycleDay = (($dayNumber - 1) % 7) + 1;
        $cycleMultiplier = floor(($dayNumber - 1) / 7) + 1;

        $baseReward = $rewards[$cycleDay];
        $baseReward['amount'] *= $cycleMultiplier;

        // Add milestone bonuses
        if ($dayNumber % 30 == 0) {
            $baseReward['amount'] *= 2;
            $baseReward['bonus'] = true;
            $baseReward['bonus_message'] = '30-Day Streak Bonus! 🎉';
        } elseif ($dayNumber % 7 == 0) {
            $baseReward['bonus'] = true;
            $baseReward['bonus_message'] = '7-Day Streak Bonus! 🔥';
        }

        return $baseReward;
    }

    /**
     * Claim today's reward
     */
    public function claimReward(): array
    {
        if (!$this->canClaimToday()) {
            throw new \Exception('Already claimed today');
        }

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // Check if streak continues
        if ($this->last_claim_date && $this->last_claim_date->equalTo($yesterday)) {
            $this->current_streak++;
        } else {
            $this->current_streak = 1;
        }

        // Update longest streak
        if ($this->current_streak > $this->longest_streak) {
            $this->longest_streak = $this->current_streak;
        }

        // Get reward
        $reward = $this->getTodayReward();

        // Update streak record
        $this->last_claim_date = $today;
        $this->total_claims++;
        $this->total_rewards += $reward['amount'];
        $this->save();

        // Create claim record
        DailyRewardClaim::create([
            'user_id' => $this->user_id,
            'claim_date' => $today,
            'day_number' => $this->current_streak,
            'reward_type' => $reward['type'],
            'reward_amount' => $reward['amount'],
            'reward_item' => $reward['item'] ?? null,
            'bonus_applied' => $reward['bonus'] ?? false,
        ]);

        // Award coins to user
        $this->user->increment('wallet_balance', $reward['amount']);

        return [
            'success' => true,
            'streak' => $this->current_streak,
            'reward' => $reward,
        ];
    }

    /**
     * Get upcoming rewards (next 7 days)
     */
    public function getUpcomingRewards(): array
    {
        $upcoming = [];
        $currentStreak = $this->current_streak;

        for ($i = 1; $i <= 7; $i++) {
            $tempStreak = $currentStreak + $i;
            $dayReward = $this->getRewardForDay($tempStreak);
            $upcoming[] = [
                'day' => $tempStreak,
                'reward' => $dayReward,
            ];
        }

        return $upcoming;
    }

    private function getRewardForDay($dayNumber): array
    {
        $cycleDay = (($dayNumber - 1) % 7) + 1;
        $cycleMultiplier = floor(($dayNumber - 1) / 7) + 1;

        $baseRewards = [
            1 => 10, 2 => 15, 3 => 20, 4 => 25,
            5 => 30, 6 => 40, 7 => 50,
        ];

        $amount = $baseRewards[$cycleDay] * $cycleMultiplier;

        return [
            'type' => 'coins',
            'amount' => $amount,
            'bonus' => $dayNumber % 7 == 0,
        ];
    }
}
