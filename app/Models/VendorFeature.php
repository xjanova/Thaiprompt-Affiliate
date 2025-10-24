<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VendorFeature extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'price',
        'price_type',
        'current_version',
        'features_list',
        'icon',
        'category',
        'sort_order',
        'is_active',
        'is_featured',
        'max_purchases',
        'requirements',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features_list' => 'array',
        'requirements' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'max_purchases' => 'integer',
    ];

    /**
     * Get all versions of this feature
     */
    public function versions(): HasMany
    {
        return $this->hasMany(VendorFeatureVersion::class)->orderByDesc('released_at');
    }

    /**
     * Get the latest version
     */
    public function latestVersion()
    {
        return $this->versions()->latest('released_at')->first();
    }

    /**
     * Get all purchases of this feature
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(VendorFeaturePurchase::class);
    }

    /**
     * Get active purchases
     */
    public function activePurchases(): HasMany
    {
        return $this->purchases()->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Get all changelogs
     */
    public function changelogs(): HasMany
    {
        return $this->hasMany(VendorFeatureChangelog::class)->orderByDesc('published_at');
    }

    /**
     * Get published changelogs
     */
    public function publishedChangelogs(): HasMany
    {
        return $this->changelogs()
            ->where('is_visible', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Get usage logs
     */
    public function usageLogs(): HasMany
    {
        return $this->hasMany(VendorFeatureUsageLog::class);
    }

    /**
     * Get vendors who purchased this feature
     */
    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class, 'vendor_feature_purchases')
            ->withPivot(['purchase_number', 'price_paid', 'payment_status', 'status', 'activated_at', 'expires_at'])
            ->withTimestamps();
    }

    /**
     * Check if feature is available for purchase
     */
    public function isAvailableForPurchase(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->max_purchases !== null) {
            $totalPurchases = $this->purchases()->count();
            return $totalPurchases < $this->max_purchases;
        }

        return true;
    }

    /**
     * Get monthly/yearly price based on type
     */
    public function getRecurringPrice(): ?float
    {
        if (in_array($this->price_type, ['monthly', 'yearly'])) {
            return $this->price;
        }

        return null;
    }

    /**
     * Check if vendor has purchased this feature
     */
    public function isPurchasedBy(Vendor $vendor): bool
    {
        return $this->purchases()
            ->where('vendor_id', $vendor->id)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Get total revenue from this feature
     */
    public function getTotalRevenue(): float
    {
        return $this->purchases()
            ->where('payment_status', 'completed')
            ->sum('price_paid');
    }

    /**
     * Scope for active features
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured features
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
