<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceReview extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'reviewer_id',
        'review_period',
        'review_date',
        'period_start_date',
        'period_end_date',
        'review_type',
        'technical_skills_rating',
        'communication_rating',
        'teamwork_rating',
        'leadership_rating',
        'problem_solving_rating',
        'productivity_rating',
        'quality_of_work_rating',
        'punctuality_rating',
        'initiative_rating',
        'adaptability_rating',
        'overall_rating',
        'overall_grade',
        'strengths',
        'areas_for_improvement',
        'achievements',
        'goals_for_next_period',
        'training_needs',
        'reviewer_comments',
        'employee_comments',
        'status',
        'submitted_at',
        'acknowledged_at',
        'recommend_promotion',
        'recommend_salary_increase',
        'recommended_increase_percentage',
    ];

    protected $casts = [
        'review_date' => 'date',
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'technical_skills_rating' => 'integer',
        'communication_rating' => 'integer',
        'teamwork_rating' => 'integer',
        'leadership_rating' => 'integer',
        'problem_solving_rating' => 'integer',
        'productivity_rating' => 'integer',
        'quality_of_work_rating' => 'integer',
        'punctuality_rating' => 'integer',
        'initiative_rating' => 'integer',
        'adaptability_rating' => 'integer',
        'overall_rating' => 'decimal:2',
        'submitted_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'recommend_promotion' => 'boolean',
        'recommend_salary_increase' => 'boolean',
        'recommended_increase_percentage' => 'decimal:2',
    ];

    /**
     * Get the employee being reviewed
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the reviewer
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Calculate overall rating from individual ratings
     */
    public function calculateOverallRating()
    {
        $ratings = [
            $this->technical_skills_rating,
            $this->communication_rating,
            $this->teamwork_rating,
            $this->leadership_rating,
            $this->problem_solving_rating,
            $this->productivity_rating,
            $this->quality_of_work_rating,
            $this->punctuality_rating,
            $this->initiative_rating,
            $this->adaptability_rating,
        ];

        $validRatings = array_filter($ratings, fn($r) => !is_null($r));

        if (count($validRatings) > 0) {
            $this->overall_rating = array_sum($validRatings) / count($validRatings);
            $this->overall_grade = $this->calculateGrade($this->overall_rating);
            return $this->overall_rating;
        }

        return null;
    }

    /**
     * Calculate grade from rating
     */
    protected function calculateGrade($rating)
    {
        if ($rating >= 4.5) return 'A';
        if ($rating >= 3.5) return 'B';
        if ($rating >= 2.5) return 'C';
        if ($rating >= 1.5) return 'D';
        return 'F';
    }

    /**
     * Get grade label
     */
    public function getGradeLabelAttribute()
    {
        return match($this->overall_grade) {
            'A' => 'Excellent',
            'B' => 'Good',
            'C' => 'Average',
            'D' => 'Below Average',
            'F' => 'Poor',
            default => 'Not Rated'
        };
    }

    /**
     * Submit review
     */
    public function submit()
    {
        $this->calculateOverallRating();
        $this->status = 'submitted';
        $this->submitted_at = now();
        $this->save();
    }

    /**
     * Acknowledge review (by employee)
     */
    public function acknowledge()
    {
        $this->status = 'acknowledged';
        $this->acknowledged_at = now();
        $this->save();
    }

    /**
     * Scope: By review type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('review_type', $type);
    }

    /**
     * Scope: By period
     */
    public function scopeByPeriod($query, $period)
    {
        return $query->where('review_period', $period);
    }
}
