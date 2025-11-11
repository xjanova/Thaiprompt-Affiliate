<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VideoContent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'channel_id',
        'title',
        'description',
        'video_url',
        'video_type',
        'video_id',
        'thumbnail',
        'duration_seconds',
        'required_watch_seconds',
        'required_watch_percentage',
        'reward_coins',
        'reward_exp',
        'is_active',
        'reward_enabled',
        'view_count',
        'completion_count',
        'tags',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'required_watch_seconds' => 'integer',
        'required_watch_percentage' => 'integer',
        'reward_coins' => 'decimal:2',
        'reward_exp' => 'integer',
        'is_active' => 'boolean',
        'reward_enabled' => 'boolean',
        'view_count' => 'integer',
        'completion_count' => 'integer',
        'tags' => 'array',
    ];

    /**
     * Get the channel
     */
    public function channel()
    {
        return $this->belongsTo(VideoChannel::class, 'channel_id');
    }

    /**
     * Get user watches for this video
     */
    public function userWatches()
    {
        return $this->hasMany(UserVideoWatch::class, 'video_id');
    }

    /**
     * Get watch sessions
     */
    public function watchSessions()
    {
        return $this->hasMany(VideoWatchSession::class, 'video_id');
    }

    /**
     * Check if user has completed watching this video
     */
    public function isCompletedBy($userId)
    {
        return $this->userWatches()
            ->where('user_id', $userId)
            ->where('completed', true)
            ->exists();
    }

    /**
     * Get user's watch progress
     */
    public function getWatchProgress($userId)
    {
        return $this->userWatches()
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Calculate required watch time
     */
    public function getRequiredWatchTimeAttribute()
    {
        if ($this->required_watch_seconds) {
            return $this->required_watch_seconds;
        }
        return ($this->duration_seconds * $this->required_watch_percentage) / 100;
    }

    /**
     * Scope for active videos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for videos with rewards
     */
    public function scopeRewardEnabled($query)
    {
        return $query->where('reward_enabled', true);
    }

    /**
     * Increment view count
     */
    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    /**
     * Increment completion count
     */
    public function incrementCompletionCount()
    {
        $this->increment('completion_count');
    }
}
