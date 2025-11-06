<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingEnrollment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'training_course_id',
        'employee_id',
        'enrolled_by',
        'enrolled_at',
        'attendance_status',
        'attendance_percentage',
        'absence_reason',
        'assessment_score',
        'passing_score',
        'passed',
        'completion_date',
        'trainer_feedback',
        'employee_feedback',
        'employee_rating',
        'certificate_path',
        'certificate_number',
        'certificate_issue_date',
        'certificate_expiry_date',
        'status',
        'remarks',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'attendance_percentage' => 'integer',
        'assessment_score' => 'decimal:2',
        'passing_score' => 'integer',
        'passed' => 'boolean',
        'completion_date' => 'date',
        'employee_rating' => 'integer',
        'certificate_issue_date' => 'date',
        'certificate_expiry_date' => 'date',
    ];

    /**
     * Get the training course
     */
    public function trainingCourse()
    {
        return $this->belongsTo(TrainingCourse::class);
    }

    /**
     * Get the employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the enroller
     */
    public function enroller()
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    /**
     * Complete training
     */
    public function complete($score = null, $passed = true)
    {
        $this->status = 'completed';
        $this->attendance_status = 'completed';
        $this->completion_date = now();

        if ($score !== null) {
            $this->assessment_score = $score;
            $this->passed = $score >= $this->passing_score;
        } else {
            $this->passed = $passed;
        }

        $this->save();

        // Update course completed count
        $this->trainingCourse->increment('completed_count');

        // Issue certificate if applicable
        if ($this->passed && $this->trainingCourse->provides_certificate) {
            $this->issueCertificate();
        }
    }

    /**
     * Issue certificate
     */
    public function issueCertificate()
    {
        $this->certificate_number = 'CERT-' . date('Y') . '-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
        $this->certificate_issue_date = now();

        if ($this->trainingCourse->validity_months) {
            $this->certificate_expiry_date = now()->addMonths($this->trainingCourse->validity_months);
        }

        $this->save();

        // TODO: Generate PDF certificate
        // $this->certificate_path = $this->generateCertificatePdf();
    }

    /**
     * Mark as absent
     */
    public function markAbsent($reason = null)
    {
        $this->attendance_status = 'absent';
        $this->absence_reason = $reason;
        $this->save();
    }

    /**
     * Cancel enrollment
     */
    public function cancel()
    {
        $this->status = 'cancelled';
        $this->save();

        // Decrement enrolled count
        $this->trainingCourse->decrement('enrolled_count');
    }

    /**
     * Check if certificate is expired
     */
    public function getIsCertificateExpiredAttribute()
    {
        if (!$this->certificate_expiry_date) {
            return false;
        }
        return now()->greaterThan($this->certificate_expiry_date);
    }

    /**
     * Get pass/fail status
     */
    public function getPassFailStatusAttribute()
    {
        if (!$this->assessment_score) {
            return 'Not assessed';
        }
        return $this->passed ? 'Passed' : 'Failed';
    }

    /**
     * Scope: Completed enrollments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Passed enrollments
     */
    public function scopePassed($query)
    {
        return $query->where('passed', true);
    }
}
