<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MembershipRetentionStatus extends Model
{
    use HasFactory;

    protected $table = 'membership_retention_status';

    protected $fillable = [
        'user_id',
        'membership_start_date',
        'status',
        'current_points',
        'required_points',
        'period_start',
        'period_end',
        'next_renewal_date',
        'consecutive_months',
        'last_active_date',
    ];

    protected $casts = [
        'current_points' => 'decimal:2',
        'required_points' => 'decimal:2',
        'membership_start_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'next_renewal_date' => 'date',
        'consecutive_months' => 'integer',
        'last_active_date' => 'date',
    ];

    /**
     * Get the user that owns the retention status
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get retention history records
     */
    public function history()
    {
        return $this->hasMany(MembershipRetentionHistory::class, 'user_id', 'user_id');
    }

    /**
     * Get retention transactions
     */
    public function transactions()
    {
        return $this->hasMany(MembershipRetentionTransaction::class, 'user_id', 'user_id');
    }

    /**
     * Check if status is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if status is expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    /**
     * Get days remaining until expiry
     */
    public function daysRemaining(): int
    {
        if (!$this->next_renewal_date) {
            return 0;
        }

        $days = Carbon::today()->diffInDays($this->next_renewal_date, false);
        return max(0, (int)$days);
    }

    /**
     * Get percentage of points achieved
     */
    public function getPointsPercentage(): float
    {
        if ($this->required_points <= 0) {
            return 0;
        }

        return min(100, ($this->current_points / $this->required_points) * 100);
    }

    /**
     * Get remaining points needed
     */
    public function getRemainingPoints(): float
    {
        return max(0, $this->required_points - $this->current_points);
    }

    /**
     * Get health status color
     */
    public function getHealthColor(): string
    {
        $days = $this->daysRemaining();

        if ($days > 14) {
            return 'green';
        } elseif ($days > 7) {
            return 'yellow';
        } elseif ($days > 3) {
            return 'orange';
        } else {
            return 'red';
        }
    }

    /**
     * Get health status percentage (for progress bar)
     */
    public function getHealthPercentage(): float
    {
        if (!$this->period_start || !$this->period_end) {
            return 0;
        }

        $totalDays = $this->period_start->diffInDays($this->period_end);
        $daysElapsed = $this->period_start->diffInDays(Carbon::today());

        if ($totalDays <= 0) {
            return 100;
        }

        return min(100, ($daysElapsed / $totalDays) * 100);
    }

    /**
     * Scope for active statuses
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for expired statuses
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope for expiring soon
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        $targetDate = Carbon::today()->addDays($days);
        return $query->where('status', 'active')
            ->whereNotNull('next_renewal_date')
            ->where('next_renewal_date', '<=', $targetDate);
    }
}
