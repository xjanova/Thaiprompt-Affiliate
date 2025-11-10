<?php

namespace App\Models\BotAutomation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotAutomationExecution extends Model
{
    protected $fillable = [
        'automation_id',
        'scheduled_post_id',
        'status',
        'execution_type',
        'started_at',
        'completed_at',
        'duration_ms',
        'input_data',
        'output_data',
        'error_message',
        'error_details',
        'stack_trace',
        'tokens_used',
        'cost',
        'platforms_attempted',
        'platforms_succeeded',
        'platforms_failed',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'error_details' => 'array',
        'cost' => 'decimal:4',
        'metadata' => 'array',
    ];

    /**
     * Get the automation
     */
    public function automation(): BelongsTo
    {
        return $this->belongsTo(BotAutomation::class);
    }

    /**
     * Get the scheduled post
     */
    public function scheduledPost(): BelongsTo
    {
        return $this->belongsTo(BotScheduledPost::class);
    }

    /**
     * Start execution
     */
    public function start(array $inputData = []): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
            'input_data' => json_encode($inputData),
        ]);
    }

    /**
     * Complete execution successfully
     */
    public function complete(array $outputData = []): void
    {
        $duration = $this->started_at ? now()->diffInMilliseconds($this->started_at) : 0;

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'duration_ms' => $duration,
            'output_data' => json_encode($outputData),
        ]);
    }

    /**
     * Mark execution as failed
     */
    public function fail(string $errorMessage, array $errorDetails = [], ?string $stackTrace = null): void
    {
        $duration = $this->started_at ? now()->diffInMilliseconds($this->started_at) : 0;

        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'duration_ms' => $duration,
            'error_message' => $errorMessage,
            'error_details' => $errorDetails,
            'stack_trace' => $stackTrace,
        ]);
    }

    /**
     * Update platform statistics
     */
    public function updatePlatformStats(int $attempted, int $succeeded, int $failed): void
    {
        $this->update([
            'platforms_attempted' => $attempted,
            'platforms_succeeded' => $succeeded,
            'platforms_failed' => $failed,
        ]);
    }

    /**
     * Update AI usage
     */
    public function updateAiUsage(int $tokensUsed, float $cost): void
    {
        $this->update([
            'tokens_used' => $tokensUsed,
            'cost' => $cost,
        ]);
    }

    /**
     * Scope: Completed executions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Failed executions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Get success status
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'completed' && $this->platforms_succeeded > 0;
    }
}
