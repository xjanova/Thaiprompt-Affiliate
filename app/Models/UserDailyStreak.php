<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDailyStreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'current_streak',
        'longest_streak',
        'last_claim_date',
        'total_claims',
        'total_rewards',
    ];

    protected $casts = [
        'last_claim_date' => 'date',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    private function getRewardForDay($dayNumber): array
    {
        $cycleDay = (($dayNumber - 1) % 7) + 1;
        $cycleMultiplier = floor(($dayNumber - 1) / 7) + 1;

        $baseRewards = [
            1 => 10, 2 => 15, 3 => 20, 4 => 25,
            5 => 30, 6 => 40, 7 => 50,
        ];

        $amount = $baseRewards[$cycleDay] * $cycleMultiplier;

        return [
            'type' => 'coins',
            'amount' => $amount,
            'bonus' => $dayNumber % 7 == 0,
        ];
    }
}
