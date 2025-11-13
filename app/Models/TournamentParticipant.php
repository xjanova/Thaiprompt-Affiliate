<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TournamentParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'user_id',
        'player_name',
        'best_score',
        'total_games',
        'rank',
        'paid_entry',
        'is_disqualified',
        'prize_won',
        'registered_at',
    ];

    protected $casts = [
        'paid_entry' => 'boolean',
        'is_disqualified' => 'boolean',
        'registered_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function tournament()
    {
        return $this->belongsTo(GameTournament::class, 'tournament_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function matches()
    {
        return $this->hasMany(TournamentMatch::class, 'participant_id');
    }

    /**
     * Update best score
     */
    public function updateScore(int $score)
    {
        if ($score > $this->best_score) {
            $this->update(['best_score' => $score]);
        }

        $this->increment('total_games');
    }
}
