<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Models\PayrollRecord;
use App\Models\PerformanceReview;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HrmService
{
    /**
     * Get HR dashboard statistics
     */
    public function getDashboardStats($departmentId = null)
    {
        $query = Employee::query();

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $totalEmployees = $query->count();
        $activeEmployees = (clone $query)->where('employment_status', 'active')->count();
        $onProbation = (clone $query)->where('employment_status', 'probation')->count();
        $onNoticePeriod = (clone $query)->where('employment_status', 'notice_period')->count();

        // New hires this month
        $newHires = (clone $query)->whereMonth('hire_date', now()->month)
                                  ->whereYear('hire_date', now()->year)
                                  ->count();

        // Terminations this month
        $terminations = (clone $query)->whereMonth('termination_date', now()->month)
                                      ->whereYear('termination_date', now()->year)
                                      ->count();

        // Attendance today
        $todayAttendance = AttendanceRecord::whereDate('date', today())
                                          ->where('status', 'present')
                                          ->count();

        // Pending leave requests
        $pendingLeaves = LeaveRequest::where('status', 'pending')->count();

        // Pending payrolls
        $pendingPayrolls = PayrollRecord::where('status', 'pending')->count();

        // Average tenure (in years)
        $avgTenure = Employee::where('employment_status', 'active')
                            ->selectRaw('AVG(DATEDIFF(NOW(), hire_date) / 365) as avg_years')
                            ->value('avg_years');

        return [
            'total_employees' => $totalEmployees,
            'active_employees' => $activeEmployees,
            'on_probation' => $onProbation,
            'on_notice_period' => $onNoticePeriod,
            'new_hires_this_month' => $newHires,
            'terminations_this_month' => $terminations,
            'attendance_today' => $todayAttendance,
            'pending_leaves' => $pendingLeaves,
            'pending_payrolls' => $pendingPayrolls,
            'average_tenure_years' => round($avgTenure ?? 0, 1),
        ];
    }

    /**
     * Get employee by department chart data
     */
    public function getEmployeesByDepartment()
    {
        return Employee::select('departments.name', DB::raw('count(*) as total'))
                      ->join('departments', 'employees.department_id', '=', 'departments.id')
                      ->where('employees.employment_status', 'active')
                      ->groupBy('departments.id', 'departments.name')
                      ->get();
    }

    /**
     * Get employee by employment type
     */
    public function getEmployeesByType()
    {
        return Employee::select('employment_type', DB::raw('count(*) as total'))
                      ->where('employment_status', 'active')
                      ->groupBy('employment_type')
                      ->get();
    }

    /**
     * Get headcount trend (last 12 months)
     */
    public function getHeadcountTrend()
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $month = $date->format('Y-m');

            $headcount = Employee::where(function($q) use ($date) {
                $q->where('hire_date', '<=', $date->endOfMonth())
                  ->where(function($q2) use ($date) {
                      $q2->whereNull('termination_date')
                         ->orWhere('termination_date', '>', $date->endOfMonth());
                  });
            })->count();

            $months[] = [
                'month' => $date->format('M Y'),
                'headcount' => $headcount,
            ];
        }

        return $months;
    }

    /**
     * Get attendance statistics for a date range
     */
    public function getAttendanceStats($startDate, $endDate, $departmentId = null)
    {
        $query = AttendanceRecord::whereBetween('date', [$startDate, $endDate]);

        if ($departmentId) {
            $query->whereHas('employee', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $total = $query->count();
        $present = (clone $query)->where('status', 'present')->count();
        $absent = (clone $query)->where('status', 'absent')->count();
        $late = (clone $query)->where('is_late', true)->count();
        $onLeave = (clone $query)->where('status', 'leave')->count();

        return [
            'total_records' => $total,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'on_leave' => $onLeave,
            'attendance_rate' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get upcoming birthdays
     */
    public function getUpcomingBirthdays($days = 30)
    {
        $employees = Employee::whereNotNull('date_of_birth')
                            ->where('employment_status', 'active')
                            ->get()
                            ->filter(function($employee) use ($days) {
                                $birthday = Carbon::parse($employee->date_of_birth);
                                $nextBirthday = $birthday->setYear(now()->year);

                                if ($nextBirthday->isPast()) {
                                    $nextBirthday->addYear();
                                }

                                return $nextBirthday->diffInDays(now()) <= $days;
                            })
                            ->sortBy(function($employee) {
                                $birthday = Carbon::parse($employee->date_of_birth);
                                $nextBirthday = $birthday->setYear(now()->year);
                                if ($nextBirthday->isPast()) {
                                    $nextBirthday->addYear();
                                }
                                return $nextBirthday;
                            })
                            ->values();

        return $employees;
    }

    /**
     * Get upcoming work anniversaries
     */
    public function getUpcomingAnniversaries($days = 30)
    {
        $employees = Employee::whereNotNull('hire_date')
                            ->where('employment_status', 'active')
                            ->get()
                            ->filter(function($employee) use ($days) {
                                $hireDate = Carbon::parse($employee->hire_date);
                                $nextAnniversary = $hireDate->setYear(now()->year);

                                if ($nextAnniversary->isPast()) {
                                    $nextAnniversary->addYear();
                                }

                                return $nextAnniversary->diffInDays(now()) <= $days;
                            })
                            ->sortBy(function($employee) {
                                $hireDate = Carbon::parse($employee->hire_date);
                                $nextAnniversary = $hireDate->setYear(now()->year);
                                if ($nextAnniversary->isPast()) {
                                    $nextAnniversary->addYear();
                                }
                                return $nextAnniversary;
                            })
                            ->values();

        return $employees;
    }

    /**
     * Get expiring documents
     */
    public function getExpiringDocuments($days = 30)
    {
        return \App\Models\EmployeeDocument::whereNotNull('expiry_date')
                                          ->whereBetween('expiry_date', [now(), now()->addDays($days)])
                                          ->with('employee')
                                          ->orderBy('expiry_date')
                                          ->get();
    }

    /**
     * Calculate payroll for all employees
     */
    public function calculatePayrollForMonth($month, $year)
    {
        $employees = Employee::where('employment_status', 'active')->get();
        $payrollService = new PayrollService();

        foreach ($employees as $employee) {
            $payrollService->generatePayroll($employee->id, $month, $year);
        }
    }
}
