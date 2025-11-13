<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LeaderboardSeason extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'name',
        'season_number',
        'status',
        'starts_at',
        'ends_at',
        'rewards',
        'theme',
        'badge_image',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'rewards' => 'array',
    ];

    /**
     * Relationships
     */
    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function leaderboard()
    {
        return $this->hasMany(GameLeaderboard::class, 'season_id');
    }

    /**
     * Check if season is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               Carbon::now()->between($this->starts_at, $this->ends_at);
    }

    /**
     * Get top players for this season
     */
    public function getTopPlayers(int $limit = 100)
    {
        return GameLeaderboard::where('season_id', $this->id)
            ->with('user:id,name,profile_picture')
            ->select('user_id', DB::raw('MAX(score) as best_score'), DB::raw('COUNT(*) as games_played'))
            ->groupBy('user_id')
            ->orderBy('best_score', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * End season and distribute rewards
     */
    public function end()
    {
        $this->update(['status' => 'finished']);

        if ($this->rewards) {
            $this->distributeRewards();
        }
    }

    private function distributeRewards()
    {
        $topPlayers = $this->getTopPlayers(count($this->rewards));

        foreach ($topPlayers as $index => $playerData) {
            $rank = $index + 1;
            
            if (isset($this->rewards[$rank])) {
                $reward = $this->rewards[$rank];
                $user = User::find($playerData->user_id);
                
                if ($user && isset($reward['amount'])) {
                    $user->increment('wallet_balance', $reward['amount']);
                }
            }
        }
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming');
    }

    public function scopeFinished($query)
    {
        return $query->where('status', 'finished');
    }
}
