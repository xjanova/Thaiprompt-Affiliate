<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorFeatureVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_feature_id',
        'version',
        'release_notes',
        'changes',
        'change_type',
        'released_at',
        'is_major',
    ];

    protected $casts = [
        'changes' => 'array',
        'released_at' => 'date',
        'is_major' => 'boolean',
    ];

    /**
     * Get the feature this version belongs to
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(VendorFeature::class, 'vendor_feature_id');
    }

    /**
     * Get changelogs for this version
     */
    public function changelogs(): HasMany
    {
        return $this->hasMany(VendorFeatureChangelog::class);
    }

    /**
     * Check if this is a breaking change
     */
    public function isBreakingChange(): bool
    {
        return $this->change_type === 'breaking';
    }

    /**
     * Get version number parts (major.minor.patch)
     */
    public function getVersionParts(): array
    {
        $parts = explode('.', $this->version);
        return [
            'major' => (int) ($parts[0] ?? 0),
            'minor' => (int) ($parts[1] ?? 0),
            'patch' => (int) ($parts[2] ?? 0),
        ];
    }

    /**
     * Compare with another version
     */
    public function isNewerThan(string $version): bool
    {
        return version_compare($this->version, $version, '>');
    }

    /**
     * Scope for major versions
     */
    public function scopeMajor($query)
    {
        return $query->where('is_major', true);
    }

    /**
     * Scope for change type
     */
    public function scopeByChangeType($query, string $type)
    {
        return $query->where('change_type', $type);
    }
}
