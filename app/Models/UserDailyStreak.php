<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDailyStreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'current_streak',
        'longest_streak',
        'last_activity_date',
        'total_active_days',
    ];

    protected $casts = [
        'current_streak' => 'integer',
        'longest_streak' => 'integer',
        'last_activity_date' => 'date',
        'total_active_days' => 'integer',
    ];

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Update streak on activity
     */
    public function recordActivity()
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        if ($this->last_activity_date == $today) {
            // Already recorded today
            return false;
        }

        if ($this->last_activity_date == $yesterday) {
            // Continuing streak
            $this->current_streak += 1;
        } else {
            // Streak broken, start new
            $this->current_streak = 1;
        }

        // Update longest streak
        if ($this->current_streak > $this->longest_streak) {
            $this->longest_streak = $this->current_streak;
        }

        $this->last_activity_date = $today;
        $this->total_active_days += 1;
        $this->save();

        return true;
    }

    /**
     * Check if streak is broken
     */
    public function checkStreakBroken()
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        if ($this->last_activity_date &&
            $this->last_activity_date != $today &&
            $this->last_activity_date != $yesterday) {
            // Streak is broken
            $this->current_streak = 0;
            $this->save();
            return true;
        }

        return false;
    }

    /**
     * Get streak status
     */
    public function getStreakStatus()
    {
        $this->checkStreakBroken();

        return [
            'current_streak' => $this->current_streak,
            'longest_streak' => $this->longest_streak,
            'is_active_today' => $this->last_activity_date == now()->toDateString(),
            'total_active_days' => $this->total_active_days,
        ];
    }
}
