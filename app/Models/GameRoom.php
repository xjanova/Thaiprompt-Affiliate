<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GameRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'room_code',
        'name',
        'max_players',
        'current_players',
        'status',
        'settings',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * Generate a unique room code
     */
    public static function generateRoomCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (static::where('room_code', $code)->exists());

        return $code;
    }

    /**
     * Relationships
     */
    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function players()
    {
        return $this->hasMany(GameRoomPlayer::class, 'room_id');
    }

    public function activePlayers()
    {
        return $this->players()->where('status', 'active');
    }

    /**
     * Check if room is full
     */
    public function isFull(): bool
    {
        return $this->current_players >= $this->max_players;
    }

    /**
     * Check if room can be joined
     */
    public function canJoin(): bool
    {
        return $this->status === 'waiting' && !$this->isFull();
    }

    /**
     * Start the game
     */
    public function start()
    {
        $this->update([
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    /**
     * End the game
     */
    public function end()
    {
        $this->update([
            'status' => 'finished',
            'ended_at' => now(),
        ]);
    }

    /**
     * Update player count
     */
    public function updatePlayerCount()
    {
        $this->update([
            'current_players' => $this->activePlayers()->count()
        ]);
    }
}
