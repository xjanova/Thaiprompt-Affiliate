<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMissionProgress extends Model
{
    use HasFactory;

    protected $table = 'user_mission_progress';

    protected $fillable = [
        'user_id',
        'mission_id',
        'current_progress',
        'is_completed',
        'is_claimed',
        'completed_at',
        'claimed_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'is_claimed' => 'boolean',
        'completed_at' => 'datetime',
        'claimed_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mission()
    {
        return $this->belongsTo(GameMission::class, 'mission_id');
    }

    /**
     * Update progress
     */
    public function updateProgress(int $value)
    {
        $this->current_progress += $value;

        // Check if mission completed
        if (!$this->is_completed && $this->current_progress >= $this->mission->target) {
            $this->is_completed = true;
            $this->completed_at = now();
        }

        $this->save();

        return $this;
    }

    /**
     * Set progress (override)
     */
    public function setProgress(int $value)
    {
        $this->current_progress = $value;

        // Check if mission completed
        if (!$this->is_completed && $this->current_progress >= $this->mission->target) {
            $this->is_completed = true;
            $this->completed_at = now();
        }

        $this->save();

        return $this;
    }

    /**
     * Claim reward
     */
    public function claimReward()
    {
        if (!$this->is_completed) {
            throw new \Exception('Mission not completed yet');
        }

        if ($this->is_claimed) {
            throw new \Exception('Reward already claimed');
        }

        $mission = $this->mission;

        // Award reward based on type
        switch ($mission->reward_type) {
            case 'coins':
                $this->user->increment('wallet_balance', $mission->reward_amount);
                break;

            case 'skin':
                // Award skin (if not owned)
                if ($mission->reward_item) {
                    $skin = GameSkin::where('slug', $mission->reward_item)->first();
                    if ($skin && !$this->user->gameSkins()->where('skin_id', $skin->id)->exists()) {
                        $this->user->gameSkins()->attach($skin->id, [
                            'purchase_price' => 0,
                            'purchased_at' => now(),
                        ]);
                    }
                }
                break;

            case 'boost':
                // Award temporary boost (implement as needed)
                break;
        }

        // Award points if any
        if ($mission->reward_points > 0) {
            // You can implement a points system here
            // $this->user->increment('points', $mission->reward_points);
        }

        $this->is_claimed = true;
        $this->claimed_at = now();
        $this->save();

        return [
            'success' => true,
            'reward' => [
                'type' => $mission->reward_type,
                'amount' => $mission->reward_amount,
                'item' => $mission->reward_item,
                'points' => $mission->reward_points,
            ],
        ];
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentage(): int
    {
        if ($this->mission->target == 0) {
            return 0;
        }

        return min(100, (int)(($this->current_progress / $this->mission->target) * 100));
    }
}
