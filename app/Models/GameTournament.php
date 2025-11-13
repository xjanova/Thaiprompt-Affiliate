<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class GameTournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'name',
        'description',
        'slug',
        'type',
        'status',
        'max_participants',
        'entry_fee',
        'prize_pool',
        'rules',
        'registration_starts',
        'registration_ends',
        'starts_at',
        'ends_at',
        'duration_minutes',
        'banner_image',
        'is_featured',
    ];

    protected $casts = [
        'prize_pool' => 'array',
        'rules' => 'array',
        'registration_starts' => 'datetime',
        'registration_ends' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_featured' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function participants()
    {
        return $this->hasMany(TournamentParticipant::class, 'tournament_id');
    }

    public function matches()
    {
        return $this->hasMany(TournamentMatch::class, 'tournament_id');
    }

    /**
     * Check if tournament is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               Carbon::now()->between($this->starts_at, $this->ends_at);
    }

    /**
     * Check if registration is open
     */
    public function isRegistrationOpen(): bool
    {
        $now = Carbon::now();
        
        return $this->status === 'upcoming' &&
               ($this->registration_starts === null || $now->gte($this->registration_starts)) &&
               ($this->registration_ends === null || $now->lte($this->registration_ends)) &&
               $this->participants()->count() < $this->max_participants;
    }

    /**
     * Get participants count
     */
    public function getParticipantsCountAttribute(): int
    {
        return $this->participants()->count();
    }

    /**
     * Start tournament
     */
    public function start()
    {
        $this->update(['status' => 'active']);
    }

    /**
     * End tournament and distribute prizes
     */
    public function end()
    {
        $this->update(['status' => 'finished']);

        // Calculate final rankings
        $this->calculateFinalRankings();

        // Distribute prizes
        $this->distributePrizes();
    }

    /**
     * Calculate final rankings
     */
    private function calculateFinalRankings()
    {
        $participants = $this->participants()
            ->orderBy('best_score', 'desc')
            ->get();

        $rank = 1;
        foreach ($participants as $participant) {
            $participant->update(['rank' => $rank]);
            $rank++;
        }
    }

    /**
     * Distribute prizes to winners
     */
    private function distributePrizes()
    {
        if (!$this->prize_pool) {
            return;
        }

        foreach ($this->prize_pool as $position => $amount) {
            $rankNumber = is_numeric($position) ? $position : $this->parsePosition($position);
            
            $participant = $this->participants()
                ->where('rank', $rankNumber)
                ->first();

            if ($participant) {
                $participant->update(['prize_won' => $amount]);
                $participant->user->increment('wallet_balance', $amount);
            }
        }
    }

    private function parsePosition($position): int
    {
        // Convert '1st', '2nd', '3rd' to numbers
        return (int) preg_replace('/[^0-9]/', '', $position);
    }

    /**
     * Scopes
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFinished($query)
    {
        return $query->where('status', 'finished');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
