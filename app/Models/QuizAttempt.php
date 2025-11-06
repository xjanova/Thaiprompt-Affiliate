<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'user_id',
        'score',
        'max_score',
        'percentage',
        'passed',
        'started_at',
        'completed_at',
        'time_spent',
        'answers',
        'results',
    ];

    protected $casts = [
        'score' => 'integer',
        'max_score' => 'integer',
        'percentage' => 'decimal:2',
        'passed' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'time_spent' => 'integer',
        'answers' => 'array',
        'results' => 'array',
    ];

    /**
     * Get the quiz this attempt belongs to
     */
    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Get the user who made this attempt
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all answers for this attempt
     */
    public function quizAnswers()
    {
        return $this->hasMany(QuizAnswer::class, 'attempt_id');
    }

    /**
     * Calculate and update the score
     */
    public function calculateScore()
    {
        $totalPoints = 0;
        $earnedPoints = 0;

        foreach ($this->quizAnswers as $answer) {
            $question = $answer->question;
            $totalPoints += $question->points;

            if ($answer->is_correct) {
                $earnedPoints += $question->points;
            }
        }

        $this->score = $earnedPoints;
        $this->max_score = $totalPoints;
        $this->percentage = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0;
        $this->passed = $this->percentage >= $this->quiz->passing_score;

        return $this;
    }

    /**
     * Mark attempt as completed
     */
    public function markCompleted()
    {
        $this->completed_at = now();

        if ($this->started_at) {
            $this->time_spent = now()->diffInSeconds($this->started_at);
        }

        $this->calculateScore();
        $this->save();

        return $this;
    }

    /**
     * Check if attempt is in progress
     */
    public function isInProgress()
    {
        return $this->started_at && !$this->completed_at;
    }

    /**
     * Check if attempt is completed
     */
    public function isCompleted()
    {
        return $this->completed_at !== null;
    }

    /**
     * Get formatted time spent
     */
    public function getFormattedTimeSpentAttribute()
    {
        $minutes = floor($this->time_spent / 60);
        $seconds = $this->time_spent % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Scope for completed attempts
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    /**
     * Scope for passed attempts
     */
    public function scopePassed($query)
    {
        return $query->where('passed', true);
    }

    /**
     * Scope for failed attempts
     */
    public function scopeFailed($query)
    {
        return $query->where('passed', false);
    }
}
