<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VideoChannel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'thumbnail',
        'channel_url',
        'is_active',
        'reward_enabled',
        'base_coins_per_video',
        'priority',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'reward_enabled' => 'boolean',
        'base_coins_per_video' => 'decimal:2',
        'priority' => 'integer',
    ];

    /**
     * Get all videos in this channel
     */
    public function videos()
    {
        return $this->hasMany(VideoContent::class, 'channel_id');
    }

    /**
     * Get active videos
     */
    public function activeVideos()
    {
        return $this->videos()->where('is_active', true);
    }

    /**
     * Get quests for this channel
     */
    public function quests()
    {
        return $this->hasMany(VideoQuest::class, 'channel_id');
    }

    /**
     * Scope for active channels
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for channels with rewards enabled
     */
    public function scopeRewardEnabled($query)
    {
        return $query->where('reward_enabled', true);
    }
}
