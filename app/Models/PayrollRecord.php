<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'pay_period_start',
        'pay_period_end',
        'payment_date',
        'basic_salary',
        'allowances',
        'bonuses',
        'overtime_pay',
        'commission',
        'gross_salary',
        'tax_deduction',
        'social_security',
        'provident_fund',
        'loan_deduction',
        'other_deductions',
        'total_deductions',
        'net_salary',
        'working_days',
        'present_days',
        'absent_days',
        'leave_days',
        'overtime_hours',
        'payment_method',
        'bank_name',
        'bank_account_number',
        'payment_reference',
        'status',
        'generated_by',
        'approved_by',
        'approved_at',
        'paid_by',
        'paid_at',
        'remarks',
        'payslip_data',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'pay_period_start' => 'date',
        'pay_period_end' => 'date',
        'payment_date' => 'date',
        'basic_salary' => 'decimal:2',
        'allowances' => 'decimal:2',
        'bonuses' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'commission' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'tax_deduction' => 'decimal:2',
        'social_security' => 'decimal:2',
        'provident_fund' => 'decimal:2',
        'loan_deduction' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'working_days' => 'integer',
        'present_days' => 'integer',
        'absent_days' => 'integer',
        'leave_days' => 'integer',
        'overtime_hours' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'payslip_data' => 'array',
    ];

    /**
     * Get the employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the generator
     */
    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Get the approver
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the payer
     */
    public function payer()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * Calculate gross salary
     */
    public function calculateGrossSalary()
    {
        $this->gross_salary = $this->basic_salary
                            + $this->allowances
                            + $this->bonuses
                            + $this->overtime_pay
                            + $this->commission;
        return $this->gross_salary;
    }

    /**
     * Calculate total deductions
     */
    public function calculateTotalDeductions()
    {
        $this->total_deductions = $this->tax_deduction
                                + $this->social_security
                                + $this->provident_fund
                                + $this->loan_deduction
                                + $this->other_deductions;
        return $this->total_deductions;
    }

    /**
     * Calculate net salary
     */
    public function calculateNetSalary()
    {
        $this->calculateGrossSalary();
        $this->calculateTotalDeductions();
        $this->net_salary = $this->gross_salary - $this->total_deductions;
        return $this->net_salary;
    }

    /**
     * Calculate tax deduction (simplified)
     */
    public function calculateTaxDeduction()
    {
        // Simplified tax calculation - should be based on Thai tax brackets
        $taxableIncome = $this->gross_salary - $this->social_security - $this->provident_fund;

        if ($taxableIncome <= 0) {
            $this->tax_deduction = 0;
        } else {
            // Progressive tax rates (simplified)
            $annualIncome = $taxableIncome * 12;
            $monthlyTax = 0;

            if ($annualIncome <= 150000) {
                $monthlyTax = 0;
            } elseif ($annualIncome <= 300000) {
                $monthlyTax = (($annualIncome - 150000) * 0.05) / 12;
            } elseif ($annualIncome <= 500000) {
                $monthlyTax = ((150000 * 0.05) + (($annualIncome - 300000) * 0.10)) / 12;
            } elseif ($annualIncome <= 750000) {
                $monthlyTax = ((150000 * 0.05) + (200000 * 0.10) + (($annualIncome - 500000) * 0.15)) / 12;
            } else {
                $monthlyTax = ((150000 * 0.05) + (200000 * 0.10) + (250000 * 0.15) + (($annualIncome - 750000) * 0.20)) / 12;
            }

            $this->tax_deduction = round($monthlyTax, 2);
        }

        return $this->tax_deduction;
    }

    /**
     * Calculate social security (5% capped at 750 THB)
     */
    public function calculateSocialSecurity()
    {
        $contribution = $this->basic_salary * 0.05;
        $this->social_security = min($contribution, 750);
        return $this->social_security;
    }

    /**
     * Approve payroll
     */
    public function approve($approverId)
    {
        $this->status = 'approved';
        $this->approved_by = $approverId;
        $this->approved_at = now();
        $this->save();
    }

    /**
     * Mark as paid
     */
    public function markAsPaid($paidById, $paymentReference = null)
    {
        $this->status = 'paid';
        $this->paid_by = $paidById;
        $this->paid_at = now();
        $this->payment_date = now()->toDateString();
        if ($paymentReference) {
            $this->payment_reference = $paymentReference;
        }
        $this->save();
    }

    /**
     * Scope: By month and year
     */
    public function scopeByPeriod($query, $month, $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }

    /**
     * Scope: Pending payrolls
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
