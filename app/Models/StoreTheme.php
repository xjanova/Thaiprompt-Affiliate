<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreTheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'preview_image',
        'screenshots',
        'color_scheme',
        'typography',
        'layout_options',
        'category',
        'has_custom_header',
        'has_custom_footer',
        'has_slider',
        'has_product_filters',
        'has_mega_menu',
        'is_responsive',
        'price',
        'is_premium',
        'is_active',
        'is_featured',
        'popularity_score',
    ];

    protected $casts = [
        'screenshots' => 'array',
        'color_scheme' => 'array',
        'typography' => 'array',
        'layout_options' => 'array',
        'has_custom_header' => 'boolean',
        'has_custom_footer' => 'boolean',
        'has_slider' => 'boolean',
        'has_product_filters' => 'boolean',
        'has_mega_menu' => 'boolean',
        'is_responsive' => 'boolean',
        'price' => 'decimal:2',
        'is_premium' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'popularity_score' => 'integer',
    ];

    /**
     * Get all vendor themes using this theme
     */
    public function vendorThemes(): HasMany
    {
        return $this->hasMany(VendorTheme::class);
    }

    /**
     * Increment popularity score
     */
    public function incrementPopularity(): void
    {
        $this->increment('popularity_score');
    }

    /**
     * Scope: Active themes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Featured themes
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: Free themes
     */
    public function scopeFree($query)
    {
        return $query->where('price', 0);
    }

    /**
     * Scope: Premium themes
     */
    public function scopePremium($query)
    {
        return $query->where('is_premium', true)->where('price', '>', 0);
    }

    /**
     * Scope: By category
     */
    public function scopeOfCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Popular themes
     */
    public function scopePopular($query)
    {
        return $query->orderByDesc('popularity_score');
    }
}
