<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class JobPosting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_title',
        'job_code',
        'department_id',
        'position_id',
        'job_description',
        'requirements',
        'responsibilities',
        'qualifications',
        'benefits',
        'employment_type',
        'location',
        'salary_min',
        'salary_max',
        'salary_currency',
        'show_salary',
        'number_of_openings',
        'posting_date',
        'application_deadline',
        'expected_start_date',
        'hiring_manager_id',
        'contact_email',
        'contact_phone',
        'status',
        'is_featured',
        'is_urgent',
        'show_on_website',
        'slug',
        'meta_title',
        'meta_description',
        'views_count',
        'applications_count',
        'created_by',
    ];

    protected $casts = [
        'posting_date' => 'date',
        'application_deadline' => 'date',
        'expected_start_date' => 'date',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'show_salary' => 'boolean',
        'number_of_openings' => 'integer',
        'is_featured' => 'boolean',
        'is_urgent' => 'boolean',
        'show_on_website' => 'boolean',
        'views_count' => 'integer',
        'applications_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($jobPosting) {
            if (empty($jobPosting->slug)) {
                $jobPosting->slug = Str::slug($jobPosting->job_title . '-' . Str::random(6));
            }
        });
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
     * Get the hiring manager
     */
    public function hiringManager()
    {
        return $this->belongsTo(User::class, 'hiring_manager_id');
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all applications
     */
    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }

    /**
     * Get new applications
     */
    public function newApplications()
    {
        return $this->applications()->where('status', 'new');
    }

    /**
     * Get salary range formatted
     */
    public function getSalaryRangeAttribute()
    {
        if (!$this->show_salary || (!$this->salary_min && !$this->salary_max)) {
            return 'Negotiable';
        }

        if ($this->salary_min && $this->salary_max) {
            return number_format($this->salary_min, 0) . ' - ' . number_format($this->salary_max, 0) . ' ' . $this->salary_currency;
        } elseif ($this->salary_min) {
            return 'From ' . number_format($this->salary_min, 0) . ' ' . $this->salary_currency;
        } elseif ($this->salary_max) {
            return 'Up to ' . number_format($this->salary_max, 0) . ' ' . $this->salary_currency;
        }

        return 'Negotiable';
    }

    /**
     * Check if accepting applications
     */
    public function getIsAcceptingApplicationsAttribute()
    {
        if ($this->status !== 'open') {
            return false;
        }

        if ($this->application_deadline && now()->greaterThan($this->application_deadline)) {
            return false;
        }

        return true;
    }

    /**
     * Increment views count
     */
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    /**
     * Increment applications count
     */
    public function incrementApplications()
    {
        $this->increment('applications_count');
    }

    /**
     * Scope: Open positions
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope: Featured positions
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: Visible on website
     */
    public function scopePublic($query)
    {
        return $query->where('show_on_website', true)
                    ->where('status', 'open');
    }
}
