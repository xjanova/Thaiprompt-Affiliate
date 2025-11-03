<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserArticleProgress extends Model
{
    use HasFactory;

    protected $table = 'user_article_progress';

    protected $fillable = [
        'user_id',
        'article_id',
        'status',
        'progress_percentage',
        'time_spent',
        'started_at',
        'completed_at',
        'last_accessed_at',
        'metadata',
    ];

    protected $casts = [
        'progress_percentage' => 'integer',
        'time_spent' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the article
     */
    public function article()
    {
        return $this->belongsTo(LearningArticle::class, 'article_id');
    }

    /**
     * Mark as started
     */
    public function markAsStarted()
    {
        if (!$this->started_at) {
            $this->started_at = now();
        }

        $this->status = 'in_progress';
        $this->last_accessed_at = now();
        $this->save();
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted()
    {
        $this->status = 'completed';
        $this->progress_percentage = 100;
        $this->completed_at = now();
        $this->last_accessed_at = now();
        $this->save();
    }

    /**
     * Update progress
     */
    public function updateProgress($percentage, $timeSpent = null)
    {
        $this->progress_percentage = min(100, max(0, $percentage));
        $this->last_accessed_at = now();

        if ($timeSpent !== null) {
            $this->time_spent += $timeSpent;
        }

        if ($this->progress_percentage >= 100) {
            $this->markAsCompleted();
        } else {
            if ($this->status === 'not_started') {
                $this->markAsStarted();
            } else {
                $this->status = 'in_progress';
                $this->save();
            }
        }
    }

    /**
     * Get formatted time spent
     */
    public function getFormattedTimeSpentAttribute()
    {
        if ($this->time_spent < 60) {
            return $this->time_spent . ' วินาที';
        }

        $minutes = floor($this->time_spent / 60);
        $seconds = $this->time_spent % 60;

        if ($minutes < 60) {
            return $minutes . ' นาที';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return $hours . ' ชั่วโมง ' . $remainingMinutes . ' นาที';
    }

    /**
     * Scope: completed
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: in progress
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope: not started
     */
    public function scopeNotStarted($query)
    {
        return $query->where('status', 'not_started');
    }
}
