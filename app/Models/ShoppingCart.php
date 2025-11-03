<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShoppingCart extends Model
{
    protected $table = 'shopping_cart';

    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'selected_attributes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'selected_attributes' => 'array',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get subtotal for this item
     */
    public function getSubtotalAttribute(): float
    {
        return $this->product->price * $this->quantity;
    }

    /**
     * Check if product is available
     */
    public function isAvailable(): bool
    {
        return $this->product->isInStock() && $this->product->is_active;
    }

    /**
     * Check if requested quantity is available
     */
    public function hasEnoughStock(): bool
    {
        if (!$this->product->track_inventory) {
            return true;
        }

        return $this->product->stock_quantity >= $this->quantity;
    }

    /**
     * Scope to get cart items for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get cart total for user
     */
    public static function getTotalForUser($userId)
    {
        return static::forUser($userId)
            ->with('product')
            ->get()
            ->sum(function ($item) {
                return $item->product->price * $item->quantity;
            });
    }

    /**
     * Get cart item count for user
     */
    public static function getCountForUser($userId)
    {
        return static::forUser($userId)->sum('quantity');
    }

    /**
     * Clear cart for user
     */
    public static function clearForUser($userId)
    {
        return static::forUser($userId)->delete();
    }
}
