<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineSignupStepLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'step_name',
        'status',
        'step_data',
        'error_message',
        'attempts',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'step_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Step statuses
     */
    public const STATUS_STARTED = 'started';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_FAILED = 'failed';

    /**
     * Get the session that owns this step log
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(LineSignupSession::class, 'session_id');
    }

    /**
     * Mark step as completed
     */
    public function markAsCompleted(array $stepData = []): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();

        if (!empty($stepData)) {
            $this->step_data = array_merge($this->step_data ?? [], $stepData);
        }

        $this->save();
    }

    /**
     * Mark step as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->status = self::STATUS_FAILED;
        $this->error_message = $errorMessage;
        $this->attempts++;
        $this->save();
    }

    /**
     * Get duration in seconds
     */
    public function getDurationInSeconds(): ?int
    {
        if (!$this->completed_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->completed_at);
    }
}
