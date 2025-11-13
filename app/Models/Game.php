<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'thumbnail',
        'category',
        'min_level_required',
        'is_active',
        'requires_auth',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_auth' => 'boolean',
        'settings' => 'array',
    ];

    public function userProgress()
    {
        return $this->hasMany(UserGameProgress::class);
    }

    public function leaderboard()
    {
        return $this->hasMany(GameLeaderboard::class)
            ->orderBy('score', 'desc')
            ->limit(100);
    }

    public function achievements()
    {
        return $this->hasMany(GameAchievement::class);
    }

    public function getTopScores($limit = 10)
    {
        return $this->leaderboard()
            ->with('user:id,name,profile_picture')
            ->limit($limit)
            ->get();
    }
}
