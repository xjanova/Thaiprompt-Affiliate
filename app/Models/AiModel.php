<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiModel extends Model
{
    protected $fillable = [
        'provider_id',
        'model_identifier',
        'display_name',
        'description',
        'context_window',
        'max_output_tokens',
        'supports_functions',
        'supports_vision',
        'supports_streaming',
        'is_active',
        'pricing',
    ];

    protected $casts = [
        'supports_functions' => 'boolean',
        'supports_vision' => 'boolean',
        'supports_streaming' => 'boolean',
        'is_active' => 'boolean',
        'pricing' => 'array',
    ];

    /**
     * Get the provider
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'provider_id');
    }

    /**
     * Get all bot profiles using this model
     */
    public function botProfiles(): HasMany
    {
        return $this->hasMany(AiBotProfile::class, 'model_id');
    }

    /**
     * Get display name with provider
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->provider->display_name} - {$this->display_name}";
    }

    /**
     * Get pricing (model-specific or provider default)
     */
    public function getPricingInfo(): array
    {
        return $this->pricing ?? $this->provider->pricing ?? [];
    }
}
