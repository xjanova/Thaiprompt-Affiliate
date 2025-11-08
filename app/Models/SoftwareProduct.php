<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SoftwareProduct extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'short_description',
        'description',
        'features',
        'base_price',
        'price_label',
        'currency',
        'main_image_url',
        'gallery_images',
        'technical_specs',
        'demo_url',
        'documentation_url',
        'is_customizable',
        'is_featured',
        'is_active',
        'requires_consultation',
        'estimated_delivery_days',
        'meta_data',
        'meta_title',
        'meta_description',
        'tags',
        'view_count',
        'quotation_count',
        'order_count',
        'sort_order',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'features' => 'array',
        'gallery_images' => 'array',
        'technical_specs' => 'array',
        'is_customizable' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'requires_consultation' => 'boolean',
        'estimated_delivery_days' => 'integer',
        'meta_data' => 'array',
        'tags' => 'array',
        'view_count' => 'integer',
        'quotation_count' => 'integer',
        'order_count' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });

        static::updating(function ($product) {
            if ($product->isDirty('name') && empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    /**
     * Get the category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(SoftwareProductCategory::class, 'category_id');
    }

    /**
     * Get all options
     */
    public function options(): HasMany
    {
        return $this->hasMany(SoftwareProductOption::class, 'software_product_id')->orderBy('sort_order');
    }

    /**
     * Get active options
     */
    public function activeOptions(): HasMany
    {
        return $this->options()->where('is_active', true);
    }

    /**
     * Get all quotations for this product
     */
    public function quotations(): HasMany
    {
        return $this->hasMany(SoftwareQuotation::class, 'software_product_id');
    }

    /**
     * Scope: Only active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Only featured products
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: Only customizable products
     */
    public function scopeCustomizable($query)
    {
        return $query->where('is_customizable', true);
    }

    /**
     * Scope: Ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Increment view count
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * Increment quotation count
     */
    public function incrementQuotationCount(): void
    {
        $this->increment('quotation_count');
    }

    /**
     * Increment order count
     */
    public function incrementOrderCount(): void
    {
        $this->increment('order_count');
    }

    /**
     * Get formatted base price
     */
    public function getFormattedBasePriceAttribute(): string
    {
        return number_format($this->base_price, 2);
    }

    /**
     * Get price display (with label if exists)
     */
    public function getPriceDisplayAttribute(): string
    {
        $price = $this->formatted_base_price;
        if ($this->price_label) {
            return "{$this->price_label} {$price} {$this->currency}";
        }
        return "{$price} {$this->currency}";
    }
}
