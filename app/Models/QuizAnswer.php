<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'option_id',
        'answer_text',
        'selected_options',
        'is_correct',
        'points_earned',
    ];

    protected $casts = [
        'selected_options' => 'array',
        'is_correct' => 'boolean',
        'points_earned' => 'integer',
    ];

    /**
     * Get the attempt this answer belongs to
     */
    public function attempt()
    {
        return $this->belongsTo(QuizAttempt::class, 'attempt_id');
    }

    /**
     * Get the question this answer is for
     */
    public function question()
    {
        return $this->belongsTo(QuizQuestion::class, 'question_id');
    }

    /**
     * Get the selected option
     */
    public function option()
    {
        return $this->belongsTo(QuestionOption::class, 'option_id');
    }

    /**
     * Check if the answer is correct and update
     */
    public function checkAndUpdateCorrectness()
    {
        $question = $this->question;

        switch ($question->type) {
            case QuizQuestion::TYPE_MULTIPLE_CHOICE:
            case QuizQuestion::TYPE_TRUE_FALSE:
                $this->is_correct = $question->isCorrectOption($this->option_id);
                break;

            case QuizQuestion::TYPE_MULTIPLE_ANSWER:
                $this->is_correct = $question->checkMultipleAnswer($this->selected_options ?? []);
                break;

            case QuizQuestion::TYPE_SHORT_ANSWER:
                // For short answer, instructor needs to grade manually
                // Or we can implement simple text matching
                $this->is_correct = false; // Default to false, needs manual grading
                break;
        }

        $this->points_earned = $this->is_correct ? $question->points : 0;
        $this->save();

        return $this;
    }

    /**
     * Scope for correct answers
     */
    public function scopeCorrect($query)
    {
        return $query->where('is_correct', true);
    }

    /**
     * Scope for incorrect answers
     */
    public function scopeIncorrect($query)
    {
        return $query->where('is_correct', false);
    }
}
