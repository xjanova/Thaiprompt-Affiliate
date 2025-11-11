<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiGenProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
        'logo_url',
        'supported_features',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'supported_features' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Get the provider configs.
     */
    public function configs(): HasMany
    {
        return $this->hasMany(AiGenProviderConfig::class, 'provider_id');
    }

    /**
     * Get the usage logs for this provider.
     */
    public function usageLogs(): HasMany
    {
        return $this->hasMany(AiGenUsageLog::class, 'provider_id');
    }

    /**
     * Get the generations for this provider.
     */
    public function generations(): HasMany
    {
        return $this->hasMany(AiGenGeneration::class, 'provider_id');
    }

    /**
     * Get a config value by key.
     */
    public function getConfig(string $key, $default = null)
    {
        $config = $this->configs()->where('config_key', $key)->first();
        return $config ? $config->config_value : $default;
    }

    /**
     * Set a config value.
     */
    public function setConfig(string $key, $value, bool $isEncrypted = false): void
    {
        $this->configs()->updateOrCreate(
            ['config_key' => $key],
            [
                'config_value' => $isEncrypted ? encrypt($value) : $value,
                'is_encrypted' => $isEncrypted,
            ]
        );
    }

    /**
     * Check if provider supports a feature.
     */
    public function supportsFeature(string $feature): bool
    {
        return in_array($feature, $this->supported_features ?? []);
    }

    /**
     * Scope for active providers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for providers by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
