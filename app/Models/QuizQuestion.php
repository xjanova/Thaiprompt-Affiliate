<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuizQuestion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'quiz_id',
        'type',
        'question',
        'explanation',
        'image_url',
        'points',
        'order',
        'metadata',
    ];

    protected $casts = [
        'points' => 'integer',
        'order' => 'integer',
        'metadata' => 'array',
    ];

    const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    const TYPE_TRUE_FALSE = 'true_false';
    const TYPE_MULTIPLE_ANSWER = 'multiple_answer';
    const TYPE_SHORT_ANSWER = 'short_answer';

    /**
     * Get the quiz this question belongs to
     */
    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Get all options for this question
     */
    public function options()
    {
        return $this->hasMany(QuestionOption::class, 'question_id')->orderBy('order');
    }

    /**
     * Get correct options
     */
    public function correctOptions()
    {
        return $this->options()->where('is_correct', true);
    }

    /**
     * Get answers for this question
     */
    public function answers()
    {
        return $this->hasMany(QuizAnswer::class, 'question_id');
    }

    /**
     * Check if an option is correct
     */
    public function isCorrectOption($optionId)
    {
        return $this->correctOptions()->where('id', $optionId)->exists();
    }

    /**
     * Get correct option IDs
     */
    public function getCorrectOptionIds()
    {
        return $this->correctOptions()->pluck('id')->toArray();
    }

    /**
     * Check if answer is correct (for multiple answer questions)
     */
    public function checkMultipleAnswer(array $selectedOptionIds)
    {
        $correctIds = $this->getCorrectOptionIds();
        sort($correctIds);
        sort($selectedOptionIds);

        return $correctIds === $selectedOptionIds;
    }
}
