<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserQuestProgress extends Model
{
    use HasFactory;

    protected $table = 'user_quest_progress';

    protected $fillable = [
        'user_id',
        'quest_id',
        'current_progress',
        'target_value',
        'progress_percentage',
        'completed',
        'completed_at',
        'reward_claimed',
        'reward_claimed_at',
        'coins_earned',
        'exp_earned',
        'money_earned',
        'period_start',
        'period_end',
        'metadata',
    ];

    protected $casts = [
        'current_progress' => 'integer',
        'target_value' => 'integer',
        'progress_percentage' => 'decimal:2',
        'completed' => 'boolean',
        'completed_at' => 'datetime',
        'reward_claimed' => 'boolean',
        'reward_claimed_at' => 'datetime',
        'coins_earned' => 'decimal:2',
        'exp_earned' => 'integer',
        'money_earned' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the quest
     */
    public function quest()
    {
        return $this->belongsTo(VideoQuest::class, 'quest_id');
    }

    /**
     * Update progress
     */
    public function updateProgress($increment = 1)
    {
        $this->current_progress += $increment;
        $this->progress_percentage = ($this->current_progress / $this->target_value) * 100;

        // Check if completed
        if ($this->current_progress >= $this->target_value && !$this->completed) {
            $this->markCompleted();
        }

        $this->save();
    }

    /**
     * Mark as completed
     */
    protected function markCompleted()
    {
        $this->completed = true;
        $this->completed_at = now();

        $quest = $this->quest;

        // Calculate rewards (including bonus for extra progress)
        $baseCoins = $quest->reward_coins;
        $baseExp = $quest->reward_exp;
        $baseMoney = $quest->reward_money;

        // Bonus for exceeding target
        $extraProgress = max(0, $this->current_progress - $this->target_value);
        $bonusCoins = $extraProgress * $quest->bonus_coins_per_extra;

        $this->coins_earned = $baseCoins + $bonusCoins;
        $this->exp_earned = $baseExp;
        $this->money_earned = $baseMoney;
    }

    /**
     * Claim rewards
     */
    public function claimRewards()
    {
        if (!$this->completed || $this->reward_claimed) {
            return null;
        }

        $this->reward_claimed = true;
        $this->reward_claimed_at = now();
        $this->save();

        return [
            'coins' => $this->coins_earned,
            'exp' => $this->exp_earned,
            'money' => $this->money_earned,
        ];
    }

    /**
     * Scope for completed quests
     */
    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }

    /**
     * Scope for unclaimed rewards
     */
    public function scopeUnclaimedRewards($query)
    {
        return $query->where('completed', true)
            ->where('reward_claimed', false);
    }
}
