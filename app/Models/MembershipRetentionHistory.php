<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipRetentionHistory extends Model
{
    use HasFactory;

    protected $table = 'membership_retention_history';

    protected $fillable = [
        'user_id',
        'period_month',
        'required_points',
        'earned_points',
        'status',
        'period_start',
        'period_end',
        'achieved_at',
        'notes',
    ];

    protected $casts = [
        'required_points' => 'decimal:2',
        'earned_points' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'achieved_at' => 'date',
    ];

    /**
     * Get the user that owns the history
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if achieved
     */
    public function isAchieved(): bool
    {
        return $this->status === 'achieved';
    }

    /**
     * Check if failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Scope for achieved records
     */
    public function scopeAchieved($query)
    {
        return $query->where('status', 'achieved');
    }

    /**
     * Scope for failed records
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for specific period
     */
    public function scopeForPeriod($query, string $periodMonth)
    {
        return $query->where('period_month', $periodMonth);
    }
}
