<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiProvider extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'provider_type',
        'api_endpoint',
        'api_version',
        'is_active',
        'is_available',
        'config',
        'pricing',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_available' => 'boolean',
        'config' => 'array',
        'pricing' => 'array',
    ];

    /**
     * Get all models for this provider
     */
    public function models(): HasMany
    {
        return $this->hasMany(AiModel::class, 'provider_id');
    }

    /**
     * Get active models
     */
    public function activeModels(): HasMany
    {
        return $this->models()->where('is_active', true);
    }

    /**
     * Get all bot profiles using this provider
     */
    public function botProfiles(): HasMany
    {
        return $this->hasMany(AiBotProfile::class, 'provider_id');
    }

    /**
     * Check if provider is available
     */
    public function isAvailable(): bool
    {
        return $this->is_active && $this->is_available;
    }
}
