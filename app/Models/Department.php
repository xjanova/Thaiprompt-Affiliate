<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'parent_id',
        'manager_id',
        'location',
        'phone',
        'email',
        'budget',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'budget' => 'integer',
    ];

    /**
     * Get the parent department
     */
    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    /**
     * Get the child departments
     */
    public function children()
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    /**
     * Get all descendants recursively
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get the department manager
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get all employees in this department
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get all positions in this department
     */
    public function positions()
    {
        return $this->hasMany(Position::class);
    }

    /**
     * Get active employees count
     */
    public function getActiveEmployeesCountAttribute()
    {
        return $this->employees()->where('employment_status', 'active')->count();
    }

    /**
     * Get all job postings for this department
     */
    public function jobPostings()
    {
        return $this->hasMany(JobPosting::class);
    }
}
