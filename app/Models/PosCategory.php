<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PosCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'order',
        'is_active',
        'show_in_pos',
        'parent_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_in_pos' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(PosCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(PosCategory::class, 'parent_id')->orderBy('order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'pos_category_product')
            ->withPivot('order')
            ->withTimestamps()
            ->orderBy('pos_category_product.order');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeShowInPos($query)
    {
        return $query->where('show_in_pos', true);
    }

    public function scopeRootCategories($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Helper Methods
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }

    public function hasProducts(): bool
    {
        return $this->products()->count() > 0;
    }

    public function getActiveProducts()
    {
        return $this->products()->where('is_active', true)->get();
    }

    public function getAllDescendants(): array
    {
        $descendants = [];

        foreach ($this->children as $child) {
            $descendants[] = $child;
            $descendants = array_merge($descendants, $child->getAllDescendants());
        }

        return $descendants;
    }

    public function getBreadcrumb(): array
    {
        $breadcrumb = [$this];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($breadcrumb, $parent);
            $parent = $parent->parent;
        }

        return $breadcrumb;
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);

                // Ensure unique slug
                $count = 1;
                while (static::where('slug', $category->slug)->exists()) {
                    $category->slug = Str::slug($category->name) . '-' . $count;
                    $count++;
                }
            }
        });

        static::updating(function ($category) {
            // Prevent circular parent relationships
            if ($category->parent_id === $category->id) {
                throw new \Exception('Category cannot be its own parent');
            }

            // Check if new parent would create circular dependency
            $parent = $category->parent;
            while ($parent) {
                if ($parent->id === $category->id) {
                    throw new \Exception('Circular parent relationship detected');
                }
                $parent = $parent->parent;
            }
        });
    }
}
