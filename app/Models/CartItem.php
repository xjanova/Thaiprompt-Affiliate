<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CartItem Model
 *
 * รายการสินค้าในตะกร้า
 *
 * @property int $id
 * @property int $cart_id
 * @property int $product_id
 * @property int $quantity
 * @property float $price ราคาต่อหน่วยที่บันทึกไว้ (snapshot)
 * @property array|null $attributes คุณสมบัติเพิ่มเติมของสินค้า (เช่น สี ขนาด)
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read Cart $cart
 * @property-read Product $product
 */
class CartItem extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cart_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'price',
        'attributes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'attributes' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ Cart
     *
     * @return BelongsTo
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * ความสัมพันธ์กับ Product
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * คำนวณยอดรวมของรายการนี้
     *
     * @return float
     */
    public function getSubtotalAttribute(): float
    {
        return $this->price * $this->quantity;
    }

    /**
     * ตรวจสอบว่าสินค้ามีสต็อกพอหรือไม่
     *
     * @return bool
     */
    public function hasEnoughStock(): bool
    {
        // ถ้าสินค้าไม่ต้องติดตามสต็อก ให้ผ่านเสมอ
        if (!$this->product->track_inventory) {
            return true;
        }

        return $this->product->stock_quantity >= $this->quantity;
    }

    /**
     * ตรวจสอบว่าสินค้าพร้อมขายหรือไม่
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->product &&
               $this->product->is_active &&
               $this->product->isInStock();
    }

    /**
     * Boot method
     * ตั้งค่าราคาจาก Product อัตโนมัติถ้ายังไม่ได้ระบุ
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cartItem) {
            // ถ้ายังไม่ได้ตั้งราคา ให้ดึงจากสินค้า
            if (!$cartItem->price && $cartItem->product) {
                $cartItem->price = $cartItem->product->price;
            }
        });
    }

    /**
     * Scope: ดึงรายการตาม product_id
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $productId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope: ดึงรายการที่พร้อมขาย
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable($query)
    {
        return $query->whereHas('product', function ($q) {
            $q->where('is_active', true);
        });
    }
}
