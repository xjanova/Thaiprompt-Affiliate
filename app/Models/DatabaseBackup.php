<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DatabaseBackup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'backup_name',
        'backup_path',
        'backup_type',
        'file_size',
        'compression',
        'status',
        'created_by',
        'trigger_type',
        'database_info',
        'notes',
        'started_at',
        'completed_at',
        'duration_seconds',
        'error_message',
        'is_verified',
        'verified_at',
        'auto_delete',
        'delete_after',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'database_info' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_seconds' => 'integer',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'auto_delete' => 'boolean',
        'delete_after' => 'datetime',
    ];

    /**
     * Get the user who created the backup
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if backup is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if backup is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if backup failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if backup file exists
     */
    public function fileExists(): bool
    {
        return Storage::exists($this->backup_path);
    }

    /**
     * Get backup file size in human readable format
     */
    public function getHumanReadableSize(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get download URL
     */
    public function getDownloadUrl(): string
    {
        return route('admin.backups.download', $this->id);
    }

    /**
     * Delete backup file
     */
    public function deleteFile(): bool
    {
        if ($this->fileExists()) {
            return Storage::delete($this->backup_path);
        }
        return true;
    }

    /**
     * Verify backup
     */
    public function verify(): bool
    {
        if (!$this->fileExists()) {
            return false;
        }

        $this->is_verified = true;
        $this->verified_at = now();
        return $this->save();
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(int $fileSize): bool
    {
        $this->status = 'completed';
        $this->completed_at = now();
        $this->file_size = $fileSize;

        if ($this->started_at) {
            $this->duration_seconds = $this->started_at->diffInSeconds($this->completed_at);
        }

        return $this->save();
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $error): bool
    {
        $this->status = 'failed';
        $this->completed_at = now();
        $this->error_message = $error;

        if ($this->started_at) {
            $this->duration_seconds = $this->started_at->diffInSeconds($this->completed_at);
        }

        return $this->save();
    }

    /**
     * Check if backup should be deleted
     */
    public function shouldBeDeleted(): bool
    {
        return $this->auto_delete &&
               $this->delete_after &&
               $this->delete_after < now();
    }

    /**
     * Scope for completed backups
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed backups
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for verified backups
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope for backups that should be deleted
     */
    public function scopeShouldBeDeleted($query)
    {
        return $query->where('auto_delete', true)
            ->where('delete_after', '<', now());
    }

    /**
     * Delete old backups
     */
    public static function deleteOldBackups(): int
    {
        $deleted = 0;
        $backups = self::shouldBeDeleted()->get();

        foreach ($backups as $backup) {
            $backup->deleteFile();
            $backup->delete();
            $deleted++;
        }

        return $deleted;
    }
}
