<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class AttendanceRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'date',
        'check_in',
        'check_out',
        'check_in_location',
        'check_out_location',
        'check_in_ip',
        'check_out_ip',
        'work_hours',
        'overtime_hours',
        'break_hours',
        'status',
        'is_late',
        'late_minutes',
        'is_early_leave',
        'early_leave_minutes',
        'remarks',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'work_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'break_hours' => 'decimal:2',
        'is_late' => 'boolean',
        'late_minutes' => 'integer',
        'is_early_leave' => 'boolean',
        'early_leave_minutes' => 'integer',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the approver
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Calculate work hours
     */
    public function calculateWorkHours()
    {
        if ($this->check_in && $this->check_out) {
            $checkIn = Carbon::parse($this->check_in);
            $checkOut = Carbon::parse($this->check_out);
            $totalHours = $checkOut->diffInMinutes($checkIn) / 60;
            $this->work_hours = $totalHours - $this->break_hours;
            return $this->work_hours;
        }
        return 0;
    }

    /**
     * Check if late
     */
    public function checkIfLate($expectedCheckIn)
    {
        if ($this->check_in) {
            $checkIn = Carbon::parse($this->check_in);
            $expected = Carbon::parse($this->date->format('Y-m-d') . ' ' . $expectedCheckIn);

            if ($checkIn->greaterThan($expected)) {
                $this->is_late = true;
                $this->late_minutes = $checkIn->diffInMinutes($expected);
            }
        }
    }

    /**
     * Check if early leave
     */
    public function checkIfEarlyLeave($expectedCheckOut)
    {
        if ($this->check_out) {
            $checkOut = Carbon::parse($this->check_out);
            $expected = Carbon::parse($this->date->format('Y-m-d') . ' ' . $expectedCheckOut);

            if ($checkOut->lessThan($expected)) {
                $this->is_early_leave = true;
                $this->early_leave_minutes = $expected->diffInMinutes($checkOut);
            }
        }
    }

    /**
     * Scope: By date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope: By employee
     */
    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope: Present only
     */
    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }
}
