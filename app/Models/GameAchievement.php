<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameAchievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'slug',
        'name',
        'description',
        'icon',
        'type',
        'requirement',
        'reward_points',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withTimestamps()
            ->withPivot('unlocked_at');
    }

    public function checkAndUnlock(User $user, $value)
    {
        if ($value >= $this->requirement) {
            if (!$user->achievements()->where('achievement_id', $this->id)->exists()) {
                $user->achievements()->attach($this->id, [
                    'unlocked_at' => now()
                ]);
                return true;
            }
        }
        return false;
    }
}
