<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserVideoWatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'video_id',
        'watch_time_seconds',
        'total_watch_time',
        'watch_percentage',
        'completed',
        'completed_at',
        'reward_claimed',
        'reward_claimed_at',
        'coins_earned',
        'exp_earned',
        'watch_count',
        'first_watched_at',
        'last_watched_at',
    ];

    protected $casts = [
        'watch_time_seconds' => 'integer',
        'total_watch_time' => 'integer',
        'watch_percentage' => 'decimal:2',
        'completed' => 'boolean',
        'completed_at' => 'datetime',
        'reward_claimed' => 'boolean',
        'reward_claimed_at' => 'datetime',
        'coins_earned' => 'decimal:2',
        'exp_earned' => 'integer',
        'watch_count' => 'integer',
        'first_watched_at' => 'datetime',
        'last_watched_at' => 'datetime',
    ];

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the video
     */
    public function video()
    {
        return $this->belongsTo(VideoContent::class, 'video_id');
    }

    /**
     * Update watch progress
     */
    public function updateProgress($watchTimeSeconds)
    {
        $this->watch_time_seconds = max($this->watch_time_seconds, $watchTimeSeconds);
        $this->total_watch_time += $watchTimeSeconds;
        $this->watch_percentage = ($this->watch_time_seconds / $this->video->duration_seconds) * 100;
        $this->watch_count += 1;
        $this->last_watched_at = now();

        if (!$this->first_watched_at) {
            $this->first_watched_at = now();
        }

        // Check if completed
        $requiredTime = $this->video->required_watch_time;
        if ($this->watch_time_seconds >= $requiredTime && !$this->completed) {
            $this->completed = true;
            $this->completed_at = now();
            $this->video->incrementCompletionCount();
        }

        $this->save();
    }

    /**
     * Claim reward
     */
    public function claimReward()
    {
        if ($this->completed && !$this->reward_claimed && $this->video->reward_enabled) {
            $this->coins_earned = $this->video->reward_coins;
            $this->exp_earned = $this->video->reward_exp;
            $this->reward_claimed = true;
            $this->reward_claimed_at = now();
            $this->save();

            return [
                'coins' => $this->coins_earned,
                'exp' => $this->exp_earned,
            ];
        }

        return null;
    }
}
