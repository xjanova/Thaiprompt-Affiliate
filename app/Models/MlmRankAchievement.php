<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MlmRankAchievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'mlm_member_id',
        'mlm_plan_id',
        'rank_id',
        'achieved_at',
        'bonus_amount',
        'bonus_paid',
        'bonus_paid_at',
        'requirements_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'achieved_at' => 'datetime',
            'bonus_amount' => 'decimal:2',
            'bonus_paid' => 'boolean',
            'bonus_paid_at' => 'datetime',
            'requirements_snapshot' => 'array',
        ];
    }

    /**
     * Get the member
     */
    public function member()
    {
        return $this->belongsTo(MlmMember::class, 'mlm_member_id');
    }

    /**
     * Get the plan
     */
    public function plan()
    {
        return $this->belongsTo(MlmPlan::class, 'mlm_plan_id');
    }

    /**
     * Get the rank
     */
    public function rank()
    {
        return $this->belongsTo(Rank::class);
    }
}
