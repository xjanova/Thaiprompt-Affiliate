<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deployment extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'branch',
        'commit_hash',
        'commit_message',
        'started_by',
        'started_at',
        'completed_at',
        'duration',
        'output',
        'error',
        'rollback_available',
        'rollback_commit',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'rollback_available' => 'boolean',
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the user who started this deployment
     */
    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    /**
     * Check if deployment is running
     */
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * Check if deployment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if deployment failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if deployment is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Mark deployment as running
     */
    public function markAsRunning(): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark deployment as completed
     */
    public function markAsCompleted(string $output, int $duration): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'output' => $output,
            'duration' => $duration,
        ]);
    }

    /**
     * Mark deployment as failed
     */
    public function markAsFailed(string $error, string $output = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'error' => $error,
            'output' => $output,
        ]);
    }

    /**
     * Get duration in human readable format
     */
    public function getDurationHumanAttribute(): ?string
    {
        if (!$this->duration) {
            return null;
        }

        if ($this->duration < 60) {
            return $this->duration . 's';
        }

        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;

        return $minutes . 'm ' . $seconds . 's';
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => '<span class="badge badge-secondary">Pending</span>',
            self::STATUS_RUNNING => '<span class="badge badge-primary">Running</span>',
            self::STATUS_COMPLETED => '<span class="badge badge-success">Completed</span>',
            self::STATUS_FAILED => '<span class="badge badge-danger">Failed</span>',
            self::STATUS_CANCELLED => '<span class="badge badge-warning">Cancelled</span>',
            default => '<span class="badge badge-light">Unknown</span>',
        };
    }

    /**
     * Get short commit hash
     */
    public function getShortCommitAttribute(): ?string
    {
        return $this->commit_hash ? substr($this->commit_hash, 0, 8) : null;
    }

    /**
     * Scope to get recent deployments
     */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Scope to get successful deployments
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to get failed deployments
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }
}
