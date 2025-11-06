<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'employee_id',
        'department_id',
        'position_id',
        'manager_id',
        // Personal Information
        'first_name',
        'last_name',
        'first_name_th',
        'last_name_th',
        'nickname',
        'date_of_birth',
        'gender',
        'nationality',
        'id_card_number',
        'passport_number',
        // Contact Information
        'personal_email',
        'work_email',
        'mobile_phone',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        // Address
        'current_address',
        'permanent_address',
        // Employment Details
        'hire_date',
        'probation_end_date',
        'contract_start_date',
        'contract_end_date',
        'employment_type',
        'employment_status',
        // Compensation
        'basic_salary',
        'salary_currency',
        'payment_frequency',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        // Work Schedule
        'work_schedule',
        'work_start_time',
        'work_end_time',
        // Tax and Social Security
        'tax_id',
        'social_security_number',
        'provident_fund_number',
        // Leave Balances
        'annual_leave_balance',
        'sick_leave_balance',
        'personal_leave_balance',
        // Profile
        'photo',
        'bio',
        'skills',
        'certifications',
        'languages',
        // System
        'termination_date',
        'termination_reason',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'probation_end_date' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'termination_date' => 'date',
        'basic_salary' => 'decimal:2',
        'annual_leave_balance' => 'decimal:2',
        'sick_leave_balance' => 'decimal:2',
        'personal_leave_balance' => 'decimal:2',
        'skills' => 'array',
        'certifications' => 'array',
        'languages' => 'array',
    ];

    /**
     * Get the user associated with this employee
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the department
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the position
     */
    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Get the manager
     */
    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * Get direct reports (employees managed by this employee)
     */
    public function directReports()
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    /**
     * Get all documents
     */
    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    /**
     * Get attendance records
     */
    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * Get leave requests
     */
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Get payroll records
     */
    public function payrollRecords()
    {
        return $this->hasMany(PayrollRecord::class);
    }

    /**
     * Get salary components
     */
    public function salaryComponents()
    {
        return $this->hasMany(SalaryComponent::class);
    }

    /**
     * Get active salary components
     */
    public function activeSalaryComponents()
    {
        return $this->salaryComponents()->where('is_active', true);
    }

    /**
     * Get performance reviews
     */
    public function performanceReviews()
    {
        return $this->hasMany(PerformanceReview::class);
    }

    /**
     * Get performance goals
     */
    public function performanceGoals()
    {
        return $this->hasMany(PerformanceGoal::class);
    }

    /**
     * Get training enrollments
     */
    public function trainingEnrollments()
    {
        return $this->hasMany(TrainingEnrollment::class);
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get full name in Thai
     */
    public function getFullNameThAttribute()
    {
        if ($this->first_name_th && $this->last_name_th) {
            return trim($this->first_name_th . ' ' . $this->last_name_th);
        }
        return $this->full_name;
    }

    /**
     * Get years of service
     */
    public function getYearsOfServiceAttribute()
    {
        return Carbon::parse($this->hire_date)->diffInYears(now());
    }

    /**
     * Get months of service
     */
    public function getMonthsOfServiceAttribute()
    {
        return Carbon::parse($this->hire_date)->diffInMonths(now());
    }

    /**
     * Check if employee is on probation
     */
    public function getIsOnProbationAttribute()
    {
        if ($this->probation_end_date && $this->employment_status === 'probation') {
            return now()->lessThan($this->probation_end_date);
        }
        return false;
    }

    /**
     * Get age
     */
    public function getAgeAttribute()
    {
        if ($this->date_of_birth) {
            return Carbon::parse($this->date_of_birth)->age;
        }
        return null;
    }

    /**
     * Scope: Active employees
     */
    public function scopeActive($query)
    {
        return $query->where('employment_status', 'active');
    }

    /**
     * Scope: By department
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope: By position
     */
    public function scopeByPosition($query, $positionId)
    {
        return $query->where('position_id', $positionId);
    }
}
