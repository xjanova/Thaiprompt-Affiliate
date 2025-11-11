<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiGenPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'image_credits',
        'video_credits',
        'duration_days',
        'is_recurring',
        'recurring_period',
        'features',
        'provider_access',
        'is_active',
        'is_popular',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'image_credits' => 'integer',
        'video_credits' => 'integer',
        'duration_days' => 'integer',
        'is_recurring' => 'boolean',
        'features' => 'array',
        'provider_access' => 'array',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the subscriptions for this package.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(AiGenSubscription::class, 'package_id');
    }

    /**
     * Get active subscriptions for this package.
     */
    public function activeSubscriptions(): HasMany
    {
        return $this->subscriptions()->where('status', 'active');
    }

    /**
     * Check if package has access to a provider.
     */
    public function hasProviderAccess(int $providerId): bool
    {
        // If provider_access is null, allow all
        if (empty($this->provider_access)) {
            return true;
        }
        return in_array($providerId, $this->provider_access);
    }

    /**
     * Scope for active packages.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for popular packages.
     */
    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    /**
     * Get formatted price.
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2) . ' ' . $this->currency;
    }
}
