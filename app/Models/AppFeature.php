<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'feature_key',
        'feature_name',
        'description',
        'is_enabled',
        'category',
        'sort_order',
        'config',
        'min_app_version',
        'platform',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'sort_order' => 'integer',
        'config' => 'array',
    ];

    /**
     * Scope to get only enabled features
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope to get features by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get features by platform
     */
    public function scopeByPlatform($query, $platform)
    {
        return $query->where(function ($q) use ($platform) {
            $q->where('platform', $platform)
              ->orWhere('platform', 'all')
              ->orWhereNull('platform');
        });
    }

    /**
     * Check if feature is available for specific app version
     */
    public function isAvailableForVersion($version)
    {
        if (!$this->min_app_version) {
            return true;
        }

        return version_compare($version, $this->min_app_version, '>=');
    }
}
