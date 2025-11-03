<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'seller_id',
        'product_name',
        'product_sku',
        'product_image',
        'product_attributes',
        'unit_price',
        'quantity',
        'subtotal',
        'discount_amount',
        'total',
        'commission_rate',
        'commission_amount',
        'seller_earning',
        'status',
    ];

    protected $casts = [
        'product_attributes' => 'array',
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'seller_earning' => 'decimal:2',
    ];

    /**
     * Get the order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the seller
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Get reviews for this item
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class, 'order_item_id');
    }

    /**
     * Check if this item has been reviewed
     */
    public function hasReview(): bool
    {
        return $this->reviews()->exists();
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'processing' => 'primary',
            'shipped' => 'info',
            'delivered' => 'success',
            'completed' => 'success',
            'cancelled' => 'danger',
            'refunded' => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Get status label in Thai
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'รอดำเนินการ',
            'processing' => 'กำลังเตรียมสินค้า',
            'shipped' => 'จัดส่งแล้ว',
            'delivered' => 'ส่งถึงแล้ว',
            'completed' => 'สำเร็จ',
            'cancelled' => 'ยกเลิก',
            'refunded' => 'คืนเงิน',
            default => 'ไม่ทราบสถานะ',
        };
    }
}
