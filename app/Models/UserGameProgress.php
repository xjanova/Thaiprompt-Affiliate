<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGameProgress extends Model
{
    use HasFactory;

    protected $table = 'user_game_progress';

    protected $fillable = [
        'user_id',
        'game_id',
        'highest_score',
        'highest_wave',
        'total_plays',
        'total_kills',
        'bosses_defeated',
        'total_playtime_seconds',
        'last_played_at',
        'unlocked_ships',
        'unlocked_weapons',
        'current_ship',
        'current_weapon',
        'powerups_collected',
        'accuracy_percentage',
    ];

    protected $casts = [
        'unlocked_ships' => 'array',
        'unlocked_weapons' => 'array',
        'last_played_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function hasUnlockedShip($shipSlug)
    {
        $unlocked = $this->unlocked_ships ?? [];
        return in_array($shipSlug, $unlocked);
    }

    public function hasUnlockedWeapon($weaponSlug)
    {
        $unlocked = $this->unlocked_weapons ?? [];
        return in_array($weaponSlug, $unlocked);
    }

    public function unlockShip($shipSlug)
    {
        $unlocked = $this->unlocked_ships ?? [];
        if (!in_array($shipSlug, $unlocked)) {
            $unlocked[] = $shipSlug;
            $this->unlocked_ships = $unlocked;
            $this->save();
        }
    }

    public function unlockWeapon($weaponSlug)
    {
        $unlocked = $this->unlocked_weapons ?? [];
        if (!in_array($weaponSlug, $unlocked)) {
            $unlocked[] = $weaponSlug;
            $this->unlocked_weapons = $unlocked;
            $this->save();
        }
    }

    public function updateStats($data)
    {
        if (isset($data['score']) && $data['score'] > $this->highest_score) {
            $this->highest_score = $data['score'];
        }

        if (isset($data['wave']) && $data['wave'] > $this->highest_wave) {
            $this->highest_wave = $data['wave'];
        }

        $this->total_plays += 1;
        $this->total_kills += $data['kills'] ?? 0;
        $this->bosses_defeated += $data['bosses'] ?? 0;
        $this->total_playtime_seconds += $data['playtime'] ?? 0;
        $this->powerups_collected += $data['powerups'] ?? 0;
        $this->last_played_at = now();

        $this->save();
    }
}
