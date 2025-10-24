<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'category_id',
        'name',
        'slug',
        'description',
        'short_description',
        'price',
        'sale_price',
        'cost_price',
        'sku',
        'stock_quantity',
        'stock_status',
        'manage_stock',
        'weight',
        'dimensions',
        'attributes',
        'featured_image',
        'gallery_images',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'views',
        'sales_count',
        'rating',
        'review_count',
        'status',
        'is_featured',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'rating' => 'decimal:2',
        'manage_stock' => 'boolean',
        'is_featured' => 'boolean',
        'attributes' => 'array',
        'gallery_images' => 'array',
    ];

    // Relationships
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    // Helper Methods
    public function isInStock(): bool
    {
        return $this->stock_status === 'in_stock' && (!$this->manage_stock || $this->stock_quantity > 0);
    }

    public function getFinalPrice(): float
    {
        return $this->sale_price ?? $this->price;
    }

    public function hasDiscount(): bool
    {
        return $this->sale_price !== null && $this->sale_price < $this->price;
    }

    public function getDiscountPercentage(): int
    {
        if (!$this->hasDiscount()) {
            return 0;
        }

        return (int) round((($this->price - $this->sale_price) / $this->price) * 100);
    }

    public function decreaseStock(int $quantity): void
    {
        if ($this->manage_stock) {
            $this->decrement('stock_quantity', $quantity);

            if ($this->stock_quantity <= 0) {
                $this->update(['stock_status' => 'out_of_stock']);
            }
        }
    }

    public function increaseStock(int $quantity): void
    {
        if ($this->manage_stock) {
            $this->increment('stock_quantity', $quantity);

            if ($this->stock_quantity > 0 && $this->stock_status === 'out_of_stock') {
                $this->update(['stock_status' => 'in_stock']);
            }
        }
    }
}
