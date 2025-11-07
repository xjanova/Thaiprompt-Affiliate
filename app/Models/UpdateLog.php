<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UpdateLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'system_update_id',
        'from_version',
        'to_version',
        'status',
        'message',
        'error_details',
        'migration_results',
        'backup_path',
        'backup_size',
        'started_at',
        'completed_at',
        'duration',
        'initiated_by',
        'is_auto_update',
    ];

    protected $casts = [
        'migration_results' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration' => 'integer',
        'backup_size' => 'integer',
        'is_auto_update' => 'boolean',
    ];

    /**
     * Get the system update
     */
    public function systemUpdate()
    {
        return $this->belongsTo(SystemUpdate::class);
    }

    /**
     * Get the user who initiated
     */
    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted($message = null)
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'duration' => $this->started_at ? now()->diffInSeconds($this->started_at) : null,
            'message' => $message ?? 'Update completed successfully',
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed($error, $errorDetails = null)
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'duration' => $this->started_at ? now()->diffInSeconds($this->started_at) : null,
            'message' => $error,
            'error_details' => $errorDetails,
        ]);
    }
}
