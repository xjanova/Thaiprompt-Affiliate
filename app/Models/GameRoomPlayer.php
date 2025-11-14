<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameRoomPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'user_id',
        'player_name',
        'skin_slug',
        'position',
        'direction',
        'score',
        'length',
        'is_alive',
        'status',
        'last_update',
    ];

    protected $casts = [
        'position' => 'array',
        'direction' => 'array',
        'is_alive' => 'boolean',
        'last_update' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function room()
    {
        return $this->belongsTo(GameRoom::class, 'room_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Update player position
     */
    public function updatePosition(array $position, array $direction)
    {
        $this->update([
            'position' => $position,
            'direction' => $direction,
            'last_update' => now(),
        ]);
    }

    /**
     * Mark player as dead
     */
    public function die()
    {
        $this->update([
            'is_alive' => false,
            'status' => 'dead',
        ]);
    }

    /**
     * Update score
     */
    public function updateScore(int $score, int $length)
    {
        $this->update([
            'score' => $score,
            'length' => $length,
        ]);
    }

    /**
     * Mark as disconnected
     */
    public function disconnect()
    {
        $this->update([
            'status' => 'disconnected',
        ]);

        // Update room player count
        $this->room->updatePlayerCount();
    }
}
