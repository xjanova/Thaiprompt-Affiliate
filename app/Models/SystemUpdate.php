<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SystemUpdate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'update_number',
        'version_from',
        'version_to',
        'update_type',
        'description',
        'changes',
        'status',
        'initiated_by',
        'database_backup_id',
        'total_steps',
        'completed_steps',
        'failed_steps',
        'progress_percentage',
        'started_at',
        'completed_at',
        'duration_seconds',
        'migration_files',
        'error_message',
        'error_details',
        'requires_downtime',
        'downtime_started_at',
        'downtime_ended_at',
        'rollback_reason',
        'rolled_back_at',
        'rolled_back_by',
    ];

    protected $casts = [
        'changes' => 'array',
        'total_steps' => 'integer',
        'completed_steps' => 'integer',
        'failed_steps' => 'integer',
        'progress_percentage' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_seconds' => 'integer',
        'migration_files' => 'array',
        'error_details' => 'array',
        'requires_downtime' => 'boolean',
        'downtime_started_at' => 'datetime',
        'downtime_ended_at' => 'datetime',
        'rolled_back_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($update) {
            if (empty($update->update_number)) {
                $update->update_number = $update->generateUpdateNumber();
            }
        });
    }

    /**
     * Generate unique update number
     */
    public function generateUpdateNumber(): string
    {
        do {
            $number = 'UP-' . date('Ymd') . '-' . strtoupper(Str::random(5));
        } while (self::where('update_number', $number)->exists());

        return $number;
    }

    /**
     * Get the user who initiated
     */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /**
     * Get the user who rolled back
     */
    public function rollbackUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rolled_back_by');
    }

    /**
     * Get the database backup
     */
    public function backup(): BelongsTo
    {
        return $this->belongsTo(DatabaseBackup::class, 'database_backup_id');
    }

    /**
     * Get migration logs
     */
    public function migrationLogs(): HasMany
    {
        return $this->hasMany(MigrationLog::class)->orderBy('step_number');
    }

    /**
     * Get maintenance mode
     */
    public function maintenanceMode()
    {
        return $this->hasOne(SystemMaintenanceMode::class);
    }

    /**
     * Get notifications
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(SystemNotification::class);
    }

    /**
     * Check if update is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if update is running
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if update is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if update failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if update was rolled back
     */
    public function isRolledBack(): bool
    {
        return $this->status === 'rolled_back';
    }

    /**
     * Start the update
     */
    public function start(): bool
    {
        $this->status = 'running';
        $this->started_at = now();
        return $this->save();
    }

    /**
     * Complete the update
     */
    public function complete(): bool
    {
        $this->status = 'completed';
        $this->completed_at = now();
        $this->progress_percentage = 100;

        if ($this->started_at) {
            $this->duration_seconds = $this->started_at->diffInSeconds($this->completed_at);
        }

        return $this->save();
    }

    /**
     * Fail the update
     */
    public function fail(string $error, ?array $details = null): bool
    {
        $this->status = 'failed';
        $this->completed_at = now();
        $this->error_message = $error;
        $this->error_details = $details;

        if ($this->started_at) {
            $this->duration_seconds = $this->started_at->diffInSeconds($this->completed_at);
        }

        return $this->save();
    }

    /**
     * Rollback the update
     */
    public function rollback(string $reason, int $userId): bool
    {
        $this->status = 'rolled_back';
        $this->rollback_reason = $reason;
        $this->rolled_back_at = now();
        $this->rolled_back_by = $userId;

        return $this->save();
    }

    /**
     * Update progress
     */
    public function updateProgress(int $completedSteps, ?int $failedSteps = 0): bool
    {
        $this->completed_steps = $completedSteps;
        $this->failed_steps = $failedSteps;

        if ($this->total_steps > 0) {
            $this->progress_percentage = ($completedSteps / $this->total_steps) * 100;
        }

        return $this->save();
    }

    /**
     * Increment completed steps
     */
    public function incrementCompleted(): bool
    {
        $this->completed_steps++;

        if ($this->total_steps > 0) {
            $this->progress_percentage = ($this->completed_steps / $this->total_steps) * 100;
        }

        return $this->save();
    }

    /**
     * Increment failed steps
     */
    public function incrementFailed(): bool
    {
        $this->failed_steps++;
        return $this->save();
    }

    /**
     * Start downtime
     */
    public function startDowntime(): bool
    {
        $this->downtime_started_at = now();
        return $this->save();
    }

    /**
     * End downtime
     */
    public function endDowntime(): bool
    {
        $this->downtime_ended_at = now();
        return $this->save();
    }

    /**
     * Get downtime duration in minutes
     */
    public function getDowntimeDuration(): ?int
    {
        if ($this->downtime_started_at && $this->downtime_ended_at) {
            return $this->downtime_started_at->diffInMinutes($this->downtime_ended_at);
        }
        return null;
    }

    /**
     * Scope for pending updates
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for running updates
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope for completed updates
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed updates
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope by update type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('update_type', $type);
    }
}
