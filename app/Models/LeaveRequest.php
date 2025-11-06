<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class LeaveRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'start_day_type',
        'end_day_type',
        'reason',
        'contact_during_leave',
        'attachment',
        'status',
        'approved_by',
        'approved_at',
        'approval_remarks',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_days' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the leave type
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Get the approver
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the rejector
     */
    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Calculate total leave days
     */
    public function calculateTotalDays()
    {
        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);

        // Base days
        $totalDays = $start->diffInDays($end) + 1;

        // Adjust for half days
        if ($this->start_day_type !== 'full_day') {
            $totalDays -= 0.5;
        }
        if ($this->end_day_type !== 'full_day' && !$start->isSameDay($end)) {
            $totalDays -= 0.5;
        }

        // Exclude weekends (optional - depends on company policy)
        $days = $totalDays;
        $current = $start->copy();
        $weekendDays = 0;

        while ($current->lessThanOrEqualTo($end)) {
            if ($current->isWeekend()) {
                $weekendDays++;
            }
            $current->addDay();
        }

        $totalDays -= $weekendDays;

        $this->total_days = max(0, $totalDays);
        return $this->total_days;
    }

    /**
     * Approve the leave request
     */
    public function approve($approverId, $remarks = null)
    {
        $this->status = 'approved';
        $this->approved_by = $approverId;
        $this->approved_at = now();
        $this->approval_remarks = $remarks;
        $this->save();

        // Deduct from employee's leave balance
        $this->deductFromBalance();
    }

    /**
     * Reject the leave request
     */
    public function reject($rejectorId, $reason)
    {
        $this->status = 'rejected';
        $this->rejected_by = $rejectorId;
        $this->rejected_at = now();
        $this->rejection_reason = $reason;
        $this->save();
    }

    /**
     * Cancel the leave request
     */
    public function cancel($reason = null)
    {
        $this->status = 'cancelled';
        $this->cancelled_at = now();
        $this->cancellation_reason = $reason;
        $this->save();

        // Restore leave balance if already approved
        if ($this->approved_at) {
            $this->restoreBalance();
        }
    }

    /**
     * Deduct leave days from employee balance
     */
    protected function deductFromBalance()
    {
        $employee = $this->employee;
        $leaveType = $this->leaveType;

        // Map leave type codes to balance fields
        $balanceField = match($leaveType->code) {
            'annual' => 'annual_leave_balance',
            'sick' => 'sick_leave_balance',
            'personal' => 'personal_leave_balance',
            default => null
        };

        if ($balanceField && $employee->{$balanceField} >= $this->total_days) {
            $employee->{$balanceField} -= $this->total_days;
            $employee->save();
        }
    }

    /**
     * Restore leave balance when cancelled
     */
    protected function restoreBalance()
    {
        $employee = $this->employee;
        $leaveType = $this->leaveType;

        $balanceField = match($leaveType->code) {
            'annual' => 'annual_leave_balance',
            'sick' => 'sick_leave_balance',
            'personal' => 'personal_leave_balance',
            default => null
        };

        if ($balanceField) {
            $employee->{$balanceField} += $this->total_days;
            $employee->save();
        }
    }

    /**
     * Scope: Pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope: By date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->where(function($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }
}
