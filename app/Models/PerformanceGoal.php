<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class PerformanceGoal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'set_by',
        'goal_title',
        'description',
        'category',
        'start_date',
        'target_date',
        'target_value',
        'measurement_unit',
        'weight_percentage',
        'priority',
        'current_progress',
        'current_value',
        'status',
        'completion_date',
        'completion_notes',
        'achievement_rating',
        'evaluation_notes',
        'evaluated_by',
        'evaluated_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'target_date' => 'date',
        'completion_date' => 'date',
        'target_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'current_progress' => 'decimal:2',
        'weight_percentage' => 'integer',
        'achievement_rating' => 'integer',
        'evaluated_at' => 'datetime',
    ];

    /**
     * Get the employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who set the goal
     */
    public function setter()
    {
        return $this->belongsTo(User::class, 'set_by');
    }

    /**
     * Get the evaluator
     */
    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    /**
     * Update progress
     */
    public function updateProgress($currentValue = null)
    {
        if ($currentValue !== null) {
            $this->current_value = $currentValue;

            if ($this->target_value > 0) {
                $this->current_progress = min(100, ($currentValue / $this->target_value) * 100);
            }
        }

        // Auto-update status based on progress and dates
        $this->updateStatus();
        $this->save();
    }

    /**
     * Update status based on progress and dates
     */
    public function updateStatus()
    {
        $now = now();

        if ($this->status === 'cancelled' || $this->status === 'completed') {
            return;
        }

        if ($now->lessThan($this->start_date)) {
            $this->status = 'not_started';
        } elseif ($this->current_progress >= 100) {
            $this->status = 'completed';
            if (!$this->completion_date) {
                $this->completion_date = now();
            }
        } elseif ($now->greaterThan($this->target_date)) {
            $this->status = 'overdue';
        } elseif ($this->current_progress > 0) {
            // Check if on track based on expected progress
            $totalDays = Carbon::parse($this->start_date)->diffInDays($this->target_date);
            $elapsedDays = Carbon::parse($this->start_date)->diffInDays($now);
            $expectedProgress = $totalDays > 0 ? ($elapsedDays / $totalDays) * 100 : 0;

            if ($this->current_progress < ($expectedProgress * 0.8)) {
                $this->status = 'at_risk';
            } else {
                $this->status = 'in_progress';
            }
        }
    }

    /**
     * Complete the goal
     */
    public function complete($notes = null)
    {
        $this->status = 'completed';
        $this->completion_date = now();
        $this->completion_notes = $notes;
        $this->current_progress = 100;
        $this->save();
    }

    /**
     * Evaluate the goal
     */
    public function evaluate($evaluatorId, $rating, $notes = null)
    {
        $this->evaluated_by = $evaluatorId;
        $this->evaluated_at = now();
        $this->achievement_rating = $rating;
        $this->evaluation_notes = $notes;
        $this->save();
    }

    /**
     * Get progress color class
     */
    public function getProgressColorAttribute()
    {
        return match($this->status) {
            'completed' => 'text-green-600',
            'in_progress' => 'text-blue-600',
            'at_risk' => 'text-yellow-600',
            'overdue' => 'text-red-600',
            default => 'text-gray-600'
        };
    }

    /**
     * Get days remaining
     */
    public function getDaysRemainingAttribute()
    {
        if ($this->status === 'completed') {
            return 0;
        }
        return max(0, now()->diffInDays($this->target_date, false));
    }

    /**
     * Scope: Active goals
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['not_started', 'in_progress', 'at_risk']);
    }

    /**
     * Scope: Overdue goals
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }
}
