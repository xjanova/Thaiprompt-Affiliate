<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'level',
        'name',
        'icon',
        'required_exp',
        'max_daily_quests',
        'max_weekly_quests',
        'reward_multiplier',
        'coin_bonus',
        'perks',
        'description',
    ];

    protected $casts = [
        'level' => 'integer',
        'required_exp' => 'integer',
        'max_daily_quests' => 'integer',
        'max_weekly_quests' => 'integer',
        'reward_multiplier' => 'decimal:2',
        'coin_bonus' => 'decimal:2',
        'perks' => 'array',
    ];

    /**
     * Get users at this level
     */
    public function users()
    {
        return $this->hasMany(UserVideoLevel::class, 'current_level_id');
    }

    /**
     * Get next level
     */
    public function getNextLevelAttribute()
    {
        return static::where('level', $this->level + 1)->first();
    }

    /**
     * Get previous level
     */
    public function getPreviousLevelAttribute()
    {
        return static::where('level', $this->level - 1)->first();
    }

    /**
     * Check if user can level up
     */
    public function canUserLevelUp($currentExp)
    {
        return $currentExp >= $this->required_exp;
    }

    /**
     * Scope for level range
     */
    public function scopeForLevel($query, $level)
    {
        return $query->where('level', $level);
    }
}
