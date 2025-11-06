<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\Request;

class AttendanceService
{
    /**
     * Check in employee
     */
    public function checkIn($employeeId, $location = null)
    {
        $employee = Employee::findOrFail($employeeId);
        $today = today();

        // Check if already checked in today
        $existingRecord = AttendanceRecord::where('employee_id', $employeeId)
                                         ->where('date', $today)
                                         ->first();

        if ($existingRecord && $existingRecord->check_in) {
            throw new \Exception('Already checked in today');
        }

        if (!$existingRecord) {
            $existingRecord = new AttendanceRecord([
                'employee_id' => $employeeId,
                'date' => $today,
                'status' => 'present',
            ]);
        }

        $existingRecord->check_in = now();
        $existingRecord->check_in_location = $location;
        $existingRecord->check_in_ip = Request::ip();

        // Check if late
        if ($employee->work_start_time) {
            $existingRecord->checkIfLate($employee->work_start_time);
        }

        $existingRecord->save();

        return $existingRecord;
    }

    /**
     * Check out employee
     */
    public function checkOut($employeeId, $location = null)
    {
        $employee = Employee::findOrFail($employeeId);
        $today = today();

        $record = AttendanceRecord::where('employee_id', $employeeId)
                                 ->where('date', $today)
                                 ->first();

        if (!$record) {
            throw new \Exception('No check-in record found for today');
        }

        if ($record->check_out) {
            throw new \Exception('Already checked out today');
        }

        $record->check_out = now();
        $record->check_out_location = $location;
        $record->check_out_ip = Request::ip();

        // Calculate work hours
        $record->calculateWorkHours();

        // Check if early leave
        if ($employee->work_end_time) {
            $record->checkIfEarlyLeave($employee->work_end_time);
        }

        // Calculate overtime
        if ($employee->work_start_time && $employee->work_end_time) {
            $expectedHours = Carbon::parse($employee->work_start_time)
                                  ->diffInHours(Carbon::parse($employee->work_end_time));
            if ($record->work_hours > $expectedHours) {
                $record->overtime_hours = $record->work_hours - $expectedHours;
            }
        }

        $record->save();

        return $record;
    }

    /**
     * Mark employee as absent
     */
    public function markAbsent($employeeId, $date, $remarks = null)
    {
        $existingRecord = AttendanceRecord::where('employee_id', $employeeId)
                                         ->where('date', $date)
                                         ->first();

        if ($existingRecord) {
            $existingRecord->status = 'absent';
            $existingRecord->remarks = $remarks;
            $existingRecord->save();
            return $existingRecord;
        }

        return AttendanceRecord::create([
            'employee_id' => $employeeId,
            'date' => $date,
            'status' => 'absent',
            'remarks' => $remarks,
        ]);
    }

    /**
     * Mark employee on leave
     */
    public function markOnLeave($employeeId, $date, $remarks = null)
    {
        $existingRecord = AttendanceRecord::where('employee_id', $employeeId)
                                         ->where('date', $date)
                                         ->first();

        if ($existingRecord) {
            $existingRecord->status = 'leave';
            $existingRecord->remarks = $remarks;
            $existingRecord->save();
            return $existingRecord;
        }

        return AttendanceRecord::create([
            'employee_id' => $employeeId,
            'date' => $date,
            'status' => 'leave',
            'remarks' => $remarks,
        ]);
    }

    /**
     * Auto-generate attendance records for a date
     */
    public function autoGenerateAttendance($date)
    {
        $employees = Employee::where('employment_status', 'active')->get();

        foreach ($employees as $employee) {
            $exists = AttendanceRecord::where('employee_id', $employee->id)
                                     ->where('date', $date)
                                     ->exists();

            if (!$exists) {
                // Check if it's a weekend
                $carbonDate = Carbon::parse($date);
                $status = $carbonDate->isWeekend() ? 'weekend' : 'absent';

                AttendanceRecord::create([
                    'employee_id' => $employee->id,
                    'date' => $date,
                    'status' => $status,
                ]);
            }
        }
    }

    /**
     * Get employee attendance summary
     */
    public function getEmployeeAttendanceSummary($employeeId, $month, $year)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $records = AttendanceRecord::where('employee_id', $employeeId)
                                  ->whereBetween('date', [$startDate, $endDate])
                                  ->get();

        return [
            'total_days' => $records->count(),
            'present' => $records->where('status', 'present')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'leave' => $records->where('status', 'leave')->count(),
            'late' => $records->where('is_late', true)->count(),
            'early_leave' => $records->where('is_early_leave', true)->count(),
            'total_work_hours' => $records->sum('work_hours'),
            'total_overtime_hours' => $records->sum('overtime_hours'),
            'attendance_rate' => $records->count() > 0
                ? round(($records->where('status', 'present')->count() / $records->count()) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get department attendance summary
     */
    public function getDepartmentAttendanceSummary($departmentId, $date)
    {
        $employees = Employee::where('department_id', $departmentId)
                            ->where('employment_status', 'active')
                            ->get();

        $totalEmployees = $employees->count();
        $present = AttendanceRecord::where('date', $date)
                                  ->where('status', 'present')
                                  ->whereIn('employee_id', $employees->pluck('id'))
                                  ->count();

        return [
            'total_employees' => $totalEmployees,
            'present' => $present,
            'absent' => $totalEmployees - $present,
            'attendance_rate' => $totalEmployees > 0
                ? round(($present / $totalEmployees) * 100, 2)
                : 0,
        ];
    }
}
