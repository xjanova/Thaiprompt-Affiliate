<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorFeatureChangelog extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_feature_id',
        'vendor_feature_version_id',
        'type',
        'title',
        'description',
        'priority',
        'affected_areas',
        'published_at',
        'is_visible',
    ];

    protected $casts = [
        'affected_areas' => 'array',
        'published_at' => 'datetime',
        'is_visible' => 'boolean',
    ];

    /**
     * Get the feature this changelog belongs to
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(VendorFeature::class, 'vendor_feature_id');
    }

    /**
     * Get the version this changelog belongs to
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(VendorFeatureVersion::class, 'vendor_feature_version_id');
    }

    /**
     * Check if changelog is published
     */
    public function isPublished(): bool
    {
        return $this->is_visible
            && $this->published_at
            && $this->published_at <= now();
    }

    /**
     * Publish the changelog
     */
    public function publish(): bool
    {
        $this->is_visible = true;
        $this->published_at = now();
        return $this->save();
    }

    /**
     * Unpublish the changelog
     */
    public function unpublish(): bool
    {
        $this->is_visible = false;
        return $this->save();
    }

    /**
     * Get badge color based on type
     */
    public function getTypeBadgeColor(): string
    {
        return match($this->type) {
            'added' => 'success',
            'changed' => 'info',
            'deprecated' => 'warning',
            'removed' => 'danger',
            'fixed' => 'primary',
            'security' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get priority badge color
     */
    public function getPriorityBadgeColor(): string
    {
        return match($this->priority) {
            'critical' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Scope for published changelogs
     */
    public function scopePublished($query)
    {
        return $query->where('is_visible', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope by priority
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }
}
