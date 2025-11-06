<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'code',
        'description',
        'department_id',
        'level',
        'min_salary',
        'max_salary',
        'requirements',
        'responsibilities',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
    ];

    /**
     * Get the department this position belongs to
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get all employees with this position
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get active employees count
     */
    public function getActiveEmployeesCountAttribute()
    {
        return $this->employees()->where('employment_status', 'active')->count();
    }

    /**
     * Get job postings for this position
     */
    public function jobPostings()
    {
        return $this->hasMany(JobPosting::class);
    }

    /**
     * Get salary range formatted
     */
    public function getSalaryRangeAttribute()
    {
        if ($this->min_salary && $this->max_salary) {
            return number_format($this->min_salary, 2) . ' - ' . number_format($this->max_salary, 2);
        }
        return 'Not specified';
    }
}
