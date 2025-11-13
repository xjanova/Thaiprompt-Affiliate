<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameLeaderboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'game_id',
        'score',
        'wave_reached',
        'ship_used',
        'weapon_used',
        'playtime_seconds',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function scopeTopScores($query, $limit = 10)
    {
        return $query->orderBy('score', 'desc')->limit($limit);
    }

    public function scopeForGame($query, $gameId)
    {
        return $query->where('game_id', $gameId);
    }
}
