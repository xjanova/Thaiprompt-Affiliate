<?php

namespace App\Models\BotAutomation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BotSocialPlatform extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'icon',
        'description',
        'required_credentials',
        'api_endpoints',
        'is_active',
        'max_posts_per_day',
        'rate_limit_per_hour',
        'supported_media_types',
        'post_settings',
    ];

    protected $casts = [
        'required_credentials' => 'array',
        'api_endpoints' => 'array',
        'is_active' => 'boolean',
        'supported_media_types' => 'array',
        'post_settings' => 'array',
    ];

    /**
     * Get all connections for this platform
     */
    public function connections(): HasMany
    {
        return $this->hasMany(BotPlatformConnection::class, 'platform_id');
    }

    /**
     * Scope: Only active platforms
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if platform supports media type
     */
    public function supportsMediaType(string $type): bool
    {
        return in_array($type, $this->supported_media_types ?? []);
    }

    /**
     * Get API endpoint
     */
    public function getApiEndpoint(string $type): ?string
    {
        return $this->api_endpoints[$type] ?? null;
    }
}
