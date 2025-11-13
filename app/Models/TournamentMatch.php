<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TournamentMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'participant_id',
        'score',
        'rank_in_match',
        'stats',
        'played_at',
    ];

    protected $casts = [
        'stats' => 'array',
        'played_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function tournament()
    {
        return $this->belongsTo(GameTournament::class, 'tournament_id');
    }

    public function participant()
    {
        return $this->belongsTo(TournamentParticipant::class, 'participant_id');
    }
}
