<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyRewardClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'claim_date',
        'day_number',
        'reward_type',
        'reward_amount',
        'reward_item',
        'bonus_applied',
    ];

    protected $casts = [
        'claim_date' => 'date',
        'bonus_applied' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
