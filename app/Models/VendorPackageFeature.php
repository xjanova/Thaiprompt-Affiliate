<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorPackageFeature extends Model
{
    protected $fillable = [
        'feature_name',
        'feature_slug',
        'display_name',
        'description',
        'icon',
        'feature_type',
        'price',
        'billing_cycle',
        'category',
        'required_package_level',
        'settings_schema',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'required_package_level' => 'integer',
        'settings_schema' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get all stores using this feature
     */
    public function usage(): HasMany
    {
        return $this->hasMany(VendorFeatureUsage::class, 'feature_id');
    }

    /**
     * Get active usage
     */
    public function activeUsage(): HasMany
    {
        return $this->usage()->where('is_active', true);
    }

    /**
     * Scope: Only active features
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Available for package level
     */
    public function scopeAvailableForLevel($query, int $level)
    {
        return $query->where('required_package_level', '<=', $level);
    }

    /**
     * Check if feature is available for a package
     */
    public function isAvailableForPackage(VendorPackage $package): bool
    {
        return $this->required_package_level <= $package->level;
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        $price = '฿' . number_format($this->price, 0);

        $cycle = match($this->billing_cycle) {
            'monthly' => '/เดือน',
            'yearly' => '/ปี',
            'lifetime' => ' (ตลอดชีพ)',
            default => '',
        };

        return $price . $cycle;
    }

    /**
     * Get category display name
     */
    public function getCategoryDisplayAttribute(): string
    {
        return match($this->category) {
            'marketing' => 'การตลาด',
            'analytics' => 'วิเคราะห์ข้อมูล',
            'ai' => 'AI & Automation',
            'storage' => 'พื้นที่จัดเก็บ',
            'integration' => 'เชื่อมต่อ',
            default => $this->category,
        };
    }
}
