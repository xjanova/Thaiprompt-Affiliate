<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quiz extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'article_id',
        'title',
        'description',
        'passing_score',
        'time_limit',
        'max_attempts',
        'randomize_questions',
        'show_results_immediately',
        'show_correct_answers',
        'is_required',
        'is_active',
        'order',
        'settings',
    ];

    protected $casts = [
        'passing_score' => 'integer',
        'time_limit' => 'integer',
        'max_attempts' => 'integer',
        'randomize_questions' => 'boolean',
        'show_results_immediately' => 'boolean',
        'show_correct_answers' => 'boolean',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
        'settings' => 'array',
    ];

    /**
     * Get the article this quiz belongs to
     */
    public function article()
    {
        return $this->belongsTo(LearningArticle::class, 'article_id');
    }

    /**
     * Get all questions for this quiz
     */
    public function questions()
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    /**
     * Get all attempts for this quiz
     */
    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Get attempts by a specific user
     */
    public function userAttempts($userId)
    {
        return $this->attempts()->where('user_id', $userId)->orderBy('created_at', 'desc');
    }

    /**
     * Get the latest attempt by a user
     */
    public function latestAttempt($userId)
    {
        return $this->userAttempts($userId)->first();
    }

    /**
     * Get the best attempt by a user
     */
    public function bestAttempt($userId)
    {
        return $this->userAttempts($userId)->orderBy('score', 'desc')->first();
    }

    /**
     * Check if user has remaining attempts
     */
    public function hasRemainingAttempts($userId)
    {
        if ($this->max_attempts === null) {
            return true; // Unlimited attempts
        }

        $attemptCount = $this->userAttempts($userId)->count();
        return $attemptCount < $this->max_attempts;
    }

    /**
     * Get total points for this quiz
     */
    public function getTotalPointsAttribute()
    {
        return $this->questions()->sum('points');
    }

    /**
     * Get total questions count
     */
    public function getTotalQuestionsAttribute()
    {
        return $this->questions()->count();
    }

    /**
     * Scope for active quizzes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for required quizzes
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }
}
