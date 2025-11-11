<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiGenGeneration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'provider_id',
        'usage_log_id',
        'type',
        'prompt',
        'settings',
        'file_path',
        'file_url',
        'thumbnail_url',
        'file_size',
        'mime_type',
        'width',
        'height',
        'duration',
        'external_id',
        'external_data',
        'status',
        'error_message',
        'is_favorite',
        'is_public',
    ];

    protected $casts = [
        'settings' => 'array',
        'external_data' => 'array',
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'duration' => 'integer',
        'is_favorite' => 'boolean',
        'is_public' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user that owns the generation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the provider for this generation.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiGenProvider::class, 'provider_id');
    }

    /**
     * Get the usage log for this generation.
     */
    public function usageLog(): BelongsTo
    {
        return $this->belongsTo(AiGenUsageLog::class, 'usage_log_id');
    }

    /**
     * Get file size in human readable format.
     */
    public function getFormattedFileSizeAttribute(): ?string
    {
        if (!$this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get duration in human readable format.
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->duration) {
            return null;
        }

        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;

        if ($minutes > 0) {
            return sprintf('%d:%02d', $minutes, $seconds);
        }

        return $seconds . 's';
    }

    /**
     * Toggle favorite status.
     */
    public function toggleFavorite(): bool
    {
        $this->is_favorite = !$this->is_favorite;
        return $this->save();
    }

    /**
     * Scope for completed generations.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for favorites.
     */
    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    /**
     * Scope for public generations.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
