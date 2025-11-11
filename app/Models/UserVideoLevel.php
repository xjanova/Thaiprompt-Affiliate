<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserVideoLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'current_level_id',
        'current_exp',
        'total_exp',
        'videos_watched',
        'quests_completed',
        'total_coins_earned',
        'last_level_up_at',
    ];

    protected $casts = [
        'current_exp' => 'integer',
        'total_exp' => 'integer',
        'videos_watched' => 'integer',
        'quests_completed' => 'integer',
        'total_coins_earned' => 'decimal:2',
        'last_level_up_at' => 'datetime',
    ];

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get current level
     */
    public function currentLevel()
    {
        return $this->belongsTo(VideoLevel::class, 'current_level_id');
    }

    /**
     * Add experience
     */
    public function addExp($amount)
    {
        $this->current_exp += $amount;
        $this->total_exp += $amount;
        $this->save();

        // Check for level up
        return $this->checkLevelUp();
    }

    /**
     * Check and process level up
     */
    public function checkLevelUp()
    {
        $currentLevel = $this->currentLevel;
        $nextLevel = $currentLevel->next_level;

        if ($nextLevel && $this->current_exp >= $nextLevel->required_exp) {
            $this->levelUp($nextLevel);
            return true;
        }

        return false;
    }

    /**
     * Level up
     */
    protected function levelUp($newLevel)
    {
        $oldLevel = $this->currentLevel;

        $this->current_level_id = $newLevel->id;
        $this->current_exp = $this->current_exp - $newLevel->required_exp; // Carry over excess EXP
        $this->last_level_up_at = now();
        $this->save();

        // Award level up bonus
        if ($newLevel->coin_bonus > 0) {
            $coinModel = VideoCoin::firstOrCreate(['user_id' => $this->user_id]);
            $coinModel->addCoins($newLevel->coin_bonus, 'earned_level_up', 'VideoLevel', $newLevel->id, "Level up to {$newLevel->name}");
        }

        // Check if can level up again (multiple levels at once)
        $this->checkLevelUp();
    }

    /**
     * Increment videos watched
     */
    public function incrementVideosWatched()
    {
        $this->increment('videos_watched');
    }

    /**
     * Increment quests completed
     */
    public function incrementQuestsCompleted()
    {
        $this->increment('quests_completed');
    }

    /**
     * Get progress to next level
     */
    public function getProgressToNextLevelAttribute()
    {
        $nextLevel = $this->currentLevel->next_level;

        if (!$nextLevel) {
            return 100; // Max level
        }

        return ($this->current_exp / $nextLevel->required_exp) * 100;
    }
}
