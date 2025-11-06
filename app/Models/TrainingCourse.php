<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingCourse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'course_name',
        'course_code',
        'description',
        'category',
        'type',
        'level',
        'duration_hours',
        'duration_days',
        'max_participants',
        'cost_per_participant',
        'currency',
        'instructor_name',
        'training_provider',
        'provider_contact',
        'start_date',
        'end_date',
        'location',
        'venue',
        'objectives',
        'prerequisites',
        'modules',
        'materials',
        'provides_certificate',
        'certificate_name',
        'validity_months',
        'status',
        'is_mandatory',
        'target_departments',
        'target_positions',
        'enrolled_count',
        'completed_count',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'duration_hours' => 'integer',
        'duration_days' => 'integer',
        'max_participants' => 'integer',
        'cost_per_participant' => 'decimal:2',
        'modules' => 'array',
        'materials' => 'array',
        'provides_certificate' => 'boolean',
        'validity_months' => 'integer',
        'is_mandatory' => 'boolean',
        'target_departments' => 'array',
        'target_positions' => 'array',
        'enrolled_count' => 'integer',
        'completed_count' => 'integer',
    ];

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all enrollments
     */
    public function enrollments()
    {
        return $this->hasMany(TrainingEnrollment::class);
    }

    /**
     * Get active enrollments
     */
    public function activeEnrollments()
    {
        return $this->enrollments()->whereIn('status', ['pending', 'confirmed', 'in_progress']);
    }

    /**
     * Get completed enrollments
     */
    public function completedEnrollments()
    {
        return $this->enrollments()->where('status', 'completed');
    }

    /**
     * Check if course is full
     */
    public function getIsFullAttribute()
    {
        if (!$this->max_participants) {
            return false;
        }
        return $this->enrolled_count >= $this->max_participants;
    }

    /**
     * Get available seats
     */
    public function getAvailableSeatsAttribute()
    {
        if (!$this->max_participants) {
            return 'Unlimited';
        }
        return max(0, $this->max_participants - $this->enrolled_count);
    }

    /**
     * Get completion rate
     */
    public function getCompletionRateAttribute()
    {
        if ($this->enrolled_count == 0) {
            return 0;
        }
        return round(($this->completed_count / $this->enrolled_count) * 100, 2);
    }

    /**
     * Enroll employee
     */
    public function enrollEmployee($employeeId, $enrolledBy = null)
    {
        if ($this->is_full) {
            throw new \Exception('Course is full');
        }

        $enrollment = TrainingEnrollment::create([
            'training_course_id' => $this->id,
            'employee_id' => $employeeId,
            'enrolled_by' => $enrolledBy,
            'enrolled_at' => now(),
            'status' => 'pending',
        ]);

        $this->increment('enrolled_count');

        return $enrollment;
    }

    /**
     * Get total cost
     */
    public function getTotalCostAttribute()
    {
        return $this->cost_per_participant * $this->enrolled_count;
    }

    /**
     * Scope: Active courses
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['scheduled', 'ongoing']);
    }

    /**
     * Scope: Upcoming courses
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', 'scheduled')
                    ->where('start_date', '>', now());
    }

    /**
     * Scope: By category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
