<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    /**
     * Generate payroll for an employee
     */
    public function generatePayroll($employeeId, $month, $year, $generatedBy = null)
    {
        $employee = Employee::with('salaryComponents')->findOrFail($employeeId);

        // Check if payroll already exists
        $existing = PayrollRecord::where('employee_id', $employeeId)
                                ->where('month', $month)
                                ->where('year', $year)
                                ->first();

        if ($existing) {
            throw new \Exception('Payroll for this period already exists');
        }

        $payPeriodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $payPeriodEnd = Carbon::create($year, $month, 1)->endOfMonth();

        // Calculate working days and attendance
        $attendanceData = $this->calculateAttendance($employeeId, $payPeriodStart, $payPeriodEnd);

        // Calculate salary components
        $basicSalary = $employee->basic_salary;
        $allowances = $this->calculateAllowances($employee);
        $bonuses = $this->calculateBonuses($employee, $month, $year);
        $overtimePay = $this->calculateOvertimePay($employee, $attendanceData['overtime_hours']);
        $commission = 0; // Can be calculated based on sales/performance

        // Calculate deductions
        $socialSecurity = $this->calculateSocialSecurity($basicSalary);
        $providentFund = $this->calculateProvidentFund($basicSalary, $employee);
        $taxDeduction = 0; // Will be calculated after gross salary
        $loanDeduction = 0; // Can be retrieved from loan records
        $otherDeductions = $this->calculateOtherDeductions($employee);

        // Create payroll record
        $payroll = new PayrollRecord([
            'employee_id' => $employeeId,
            'month' => $month,
            'year' => $year,
            'pay_period_start' => $payPeriodStart,
            'pay_period_end' => $payPeriodEnd,
            'basic_salary' => $basicSalary,
            'allowances' => $allowances,
            'bonuses' => $bonuses,
            'overtime_pay' => $overtimePay,
            'commission' => $commission,
            'social_security' => $socialSecurity,
            'provident_fund' => $providentFund,
            'loan_deduction' => $loanDeduction,
            'other_deductions' => $otherDeductions,
            'working_days' => $attendanceData['working_days'],
            'present_days' => $attendanceData['present_days'],
            'absent_days' => $attendanceData['absent_days'],
            'leave_days' => $attendanceData['leave_days'],
            'overtime_hours' => $attendanceData['overtime_hours'],
            'payment_method' => 'bank_transfer',
            'bank_name' => $employee->bank_name,
            'bank_account_number' => $employee->bank_account_number,
            'status' => 'draft',
            'generated_by' => $generatedBy ?? auth()->id(),
        ]);

        // Calculate gross, deductions, and net
        $payroll->calculateGrossSalary();
        $payroll->calculateTaxDeduction();
        $payroll->calculateTotalDeductions();
        $payroll->calculateNetSalary();

        // Store detailed breakdown
        $payroll->payslip_data = $this->generatePayslipData($employee, $payroll, $attendanceData);

        $payroll->save();

        return $payroll;
    }

    /**
     * Calculate attendance data
     */
    protected function calculateAttendance($employeeId, $startDate, $endDate)
    {
        $records = AttendanceRecord::where('employee_id', $employeeId)
                                  ->whereBetween('date', [$startDate, $endDate])
                                  ->get();

        $workingDays = $startDate->diffInDaysFiltered(function (Carbon $date) {
            return !$date->isWeekend();
        }, $endDate);

        $presentDays = $records->where('status', 'present')->count();
        $absentDays = $records->where('status', 'absent')->count();
        $leaveDays = $records->where('status', 'leave')->count();
        $overtimeHours = $records->sum('overtime_hours');

        return [
            'working_days' => $workingDays,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'leave_days' => $leaveDays,
            'overtime_hours' => $overtimeHours,
        ];
    }

    /**
     * Calculate allowances
     */
    protected function calculateAllowances($employee)
    {
        $total = 0;

        $allowances = $employee->salaryComponents()
                              ->where('component_type', 'earning')
                              ->where('component_name', 'like', '%allowance%')
                              ->active()
                              ->get();

        foreach ($allowances as $allowance) {
            $total += $allowance->calculateValue($employee->basic_salary);
        }

        return $total;
    }

    /**
     * Calculate bonuses
     */
    protected function calculateBonuses($employee, $month, $year)
    {
        $total = 0;

        $bonuses = $employee->salaryComponents()
                           ->where('component_type', 'earning')
                           ->where('component_name', 'like', '%bonus%')
                           ->where('is_recurring', false)
                           ->active()
                           ->get();

        foreach ($bonuses as $bonus) {
            $total += $bonus->calculateValue($employee->basic_salary);
        }

        return $total;
    }

    /**
     * Calculate overtime pay
     */
    protected function calculateOvertimePay($employee, $overtimeHours)
    {
        // Assume standard work hours per month is 160
        $hourlyRate = $employee->basic_salary / 160;
        $overtimeRate = $hourlyRate * 1.5; // 1.5x for overtime

        return $overtimeHours * $overtimeRate;
    }

    /**
     * Calculate social security (5% capped at 750 THB)
     */
    protected function calculateSocialSecurity($basicSalary)
    {
        $contribution = $basicSalary * 0.05;
        return min($contribution, 750);
    }

    /**
     * Calculate provident fund
     */
    protected function calculateProvidentFund($basicSalary, $employee)
    {
        // Check if employee has provident fund component
        $providentFund = $employee->salaryComponents()
                                 ->where('component_type', 'deduction')
                                 ->where('component_name', 'like', '%provident%')
                                 ->active()
                                 ->first();

        if ($providentFund) {
            return $providentFund->calculateValue($basicSalary);
        }

        return 0;
    }

    /**
     * Calculate other deductions
     */
    protected function calculateOtherDeductions($employee)
    {
        $total = 0;

        $deductions = $employee->salaryComponents()
                              ->where('component_type', 'deduction')
                              ->where('component_name', 'not like', '%provident%')
                              ->where('component_name', 'not like', '%tax%')
                              ->where('component_name', 'not like', '%social%')
                              ->active()
                              ->get();

        foreach ($deductions as $deduction) {
            $total += $deduction->calculateValue($employee->basic_salary);
        }

        return $total;
    }

    /**
     * Generate detailed payslip data
     */
    protected function generatePayslipData($employee, $payroll, $attendanceData)
    {
        return [
            'employee' => [
                'id' => $employee->employee_id,
                'name' => $employee->full_name,
                'position' => $employee->position->title ?? '',
                'department' => $employee->department->name ?? '',
            ],
            'period' => [
                'month' => $payroll->month,
                'year' => $payroll->year,
                'start_date' => $payroll->pay_period_start->format('Y-m-d'),
                'end_date' => $payroll->pay_period_end->format('Y-m-d'),
            ],
            'attendance' => $attendanceData,
            'earnings' => [
                'basic_salary' => (float) $payroll->basic_salary,
                'allowances' => (float) $payroll->allowances,
                'bonuses' => (float) $payroll->bonuses,
                'overtime_pay' => (float) $payroll->overtime_pay,
                'commission' => (float) $payroll->commission,
            ],
            'deductions' => [
                'tax' => (float) $payroll->tax_deduction,
                'social_security' => (float) $payroll->social_security,
                'provident_fund' => (float) $payroll->provident_fund,
                'loan' => (float) $payroll->loan_deduction,
                'other' => (float) $payroll->other_deductions,
            ],
            'summary' => [
                'gross_salary' => (float) $payroll->gross_salary,
                'total_deductions' => (float) $payroll->total_deductions,
                'net_salary' => (float) $payroll->net_salary,
            ],
        ];
    }

    /**
     * Approve payroll
     */
    public function approvePayroll($payrollId, $approverId)
    {
        $payroll = PayrollRecord::findOrFail($payrollId);
        $payroll->approve($approverId);

        return $payroll;
    }

    /**
     * Mark payroll as paid
     */
    public function markAsPaid($payrollId, $paidById, $paymentReference = null)
    {
        $payroll = PayrollRecord::findOrFail($payrollId);
        $payroll->markAsPaid($paidById, $paymentReference);

        return $payroll;
    }

    /**
     * Bulk generate payrolls
     */
    public function bulkGeneratePayrolls($month, $year, $departmentId = null, $generatedBy = null)
    {
        $query = Employee::where('employment_status', 'active');

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $employees = $query->get();
        $generated = [];
        $errors = [];

        foreach ($employees as $employee) {
            try {
                $payroll = $this->generatePayroll($employee->id, $month, $year, $generatedBy);
                $generated[] = $payroll;
            } catch (\Exception $e) {
                $errors[] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->full_name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'generated' => $generated,
            'errors' => $errors,
        ];
    }
}
