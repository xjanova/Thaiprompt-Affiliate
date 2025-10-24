<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MigrationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'system_update_id',
        'migration_file',
        'migration_name',
        'batch',
        'status',
        'step_number',
        'description',
        'started_at',
        'completed_at',
        'duration_milliseconds',
        'rows_affected',
        'sql_executed',
        'output',
        'error_message',
        'error_trace',
        'can_rollback',
        'rollback_sql',
    ];

    protected $casts = [
        'batch' => 'integer',
        'step_number' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_milliseconds' => 'integer',
        'rows_affected' => 'integer',
        'error_trace' => 'array',
        'can_rollback' => 'boolean',
    ];

    /**
     * Get the system update
     */
    public function systemUpdate(): BelongsTo
    {
        return $this->belongsTo(SystemUpdate::class);
    }

    /**
     * Check if completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if running
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if skipped
     */
    public function isSkipped(): bool
    {
        return $this->status === 'skipped';
    }

    /**
     * Start the migration
     */
    public function start(): bool
    {
        $this->status = 'running';
        $this->started_at = now();
        return $this->save();
    }

    /**
     * Complete the migration
     */
    public function complete(?int $rowsAffected = null, ?string $output = null): bool
    {
        $this->status = 'completed';
        $this->completed_at = now();
        $this->rows_affected = $rowsAffected;
        $this->output = $output;

        if ($this->started_at) {
            $this->duration_milliseconds = $this->started_at->diffInMilliseconds($this->completed_at);
        }

        return $this->save();
    }

    /**
     * Fail the migration
     */
    public function fail(string $error, ?array $trace = null): bool
    {
        $this->status = 'failed';
        $this->completed_at = now();
        $this->error_message = $error;
        $this->error_trace = $trace;

        if ($this->started_at) {
            $this->duration_milliseconds = $this->started_at->diffInMilliseconds($this->completed_at);
        }

        return $this->save();
    }

    /**
     * Skip the migration
     */
    public function skip(string $reason): bool
    {
        $this->status = 'skipped';
        $this->output = $reason;
        return $this->save();
    }

    /**
     * Get duration in seconds
     */
    public function getDurationInSeconds(): ?float
    {
        if ($this->duration_milliseconds) {
            return $this->duration_milliseconds / 1000;
        }
        return null;
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColor(): string
    {
        return match($this->status) {
            'completed' => 'success',
            'running' => 'info',
            'failed' => 'danger',
            'skipped' => 'warning',
            'pending' => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Scope by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for completed
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
