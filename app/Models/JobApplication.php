<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_posting_id',
        'application_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'date_of_birth',
        'nationality',
        'resume_path',
        'cover_letter_path',
        'cover_letter_text',
        'portfolio_url',
        'linkedin_url',
        'additional_documents',
        'years_of_experience',
        'current_company',
        'current_position',
        'current_salary',
        'expected_salary',
        'notice_period_days',
        'available_from',
        'highest_education',
        'university',
        'major',
        'gpa',
        'skills',
        'languages',
        'certifications',
        'referral_source',
        'status',
        'interview_date',
        'interview_location',
        'interview_notes',
        'interview_rating',
        'interviewed_by',
        'overall_rating',
        'evaluation_notes',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'offered_salary',
        'offer_date',
        'offer_response_deadline',
        'joining_date',
        'source_ip',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'available_from' => 'date',
        'current_salary' => 'decimal:2',
        'expected_salary' => 'decimal:2',
        'notice_period_days' => 'integer',
        'years_of_experience' => 'integer',
        'gpa' => 'decimal:2',
        'additional_documents' => 'array',
        'skills' => 'array',
        'languages' => 'array',
        'certifications' => 'array',
        'interview_date' => 'datetime',
        'interview_rating' => 'integer',
        'overall_rating' => 'integer',
        'reviewed_at' => 'datetime',
        'offered_salary' => 'decimal:2',
        'offer_date' => 'date',
        'offer_response_deadline' => 'date',
        'joining_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($application) {
            if (empty($application->application_number)) {
                $application->application_number = 'APP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            }
        });
    }

    /**
     * Get the job posting
     */
    public function jobPosting()
    {
        return $this->belongsTo(JobPosting::class);
    }

    /**
     * Get the interviewer
     */
    public function interviewer()
    {
        return $this->belongsTo(User::class, 'interviewed_by');
    }

    /**
     * Get the reviewer
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Move to next stage
     */
    public function moveToStage($stage, $userId = null, $notes = null)
    {
        $this->status = $stage;
        if ($userId) {
            $this->reviewed_by = $userId;
            $this->reviewed_at = now();
        }
        if ($notes) {
            $this->evaluation_notes = $notes;
        }
        $this->save();
    }

    /**
     * Schedule interview
     */
    public function scheduleInterview($dateTime, $location, $userId = null)
    {
        $this->status = 'interview_scheduled';
        $this->interview_date = $dateTime;
        $this->interview_location = $location;
        if ($userId) {
            $this->interviewed_by = $userId;
        }
        $this->save();
    }

    /**
     * Reject application
     */
    public function reject($reason, $userId = null)
    {
        $this->status = 'rejected';
        $this->rejection_reason = $reason;
        if ($userId) {
            $this->reviewed_by = $userId;
            $this->reviewed_at = now();
        }
        $this->save();
    }

    /**
     * Send offer
     */
    public function sendOffer($salary, $offerDate = null, $responseDeadline = null)
    {
        $this->status = 'offer_sent';
        $this->offered_salary = $salary;
        $this->offer_date = $offerDate ?? now();
        $this->offer_response_deadline = $responseDeadline;
        $this->save();
    }

    /**
     * Accept offer
     */
    public function acceptOffer($joiningDate = null)
    {
        $this->status = 'offer_accepted';
        $this->joining_date = $joiningDate;
        $this->save();
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'new' => 'bg-blue-100 text-blue-800',
            'reviewing' => 'bg-yellow-100 text-yellow-800',
            'shortlisted' => 'bg-purple-100 text-purple-800',
            'interview_scheduled', 'interviewed', 'second_interview' => 'bg-indigo-100 text-indigo-800',
            'offer_pending', 'offer_sent' => 'bg-orange-100 text-orange-800',
            'offer_accepted' => 'bg-green-100 text-green-800',
            'offer_rejected', 'rejected', 'withdrawn' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    /**
     * Scope: By status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Active applications (not rejected/withdrawn)
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['rejected', 'withdrawn', 'offer_rejected']);
    }
}
