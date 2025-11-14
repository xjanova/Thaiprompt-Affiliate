<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Cart Model
 *
 * ตะกร้าสินค้าหลักที่รองรับการเพิ่มสินค้าหลายรายการ
 * ใช้สำหรับระบบเติมเงิน wallet และฟีเจอร์อื่นๆ ที่ต้องการตะกร้า
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $session_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection|CartItem[] $items
 */
class Cart extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'carts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'session_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ User
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ CartItem
     *
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * คำนวณยอดรวมทั้งหมดในตะกร้า
     *
     * @return float
     */
    public function getTotalAttribute(): float
    {
        return $this->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });
    }

    /**
     * นับจำนวนรายการสินค้าทั้งหมดในตะกร้า
     *
     * @return int
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * ตรวจสอบว่าตะกร้าว่างหรือไม่
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->items()->count() === 0;
    }

    /**
     * ล้างสินค้าทั้งหมดในตะกร้า
     *
     * @return void
     */
    public function clear(): void
    {
        $this->items()->delete();
    }

    /**
     * Scope: ดึงตะกร้าของผู้ใช้ที่ระบุ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: ดึงตะกร้าที่มีรายการสินค้า
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithItems($query)
    {
        return $query->has('items');
    }
}
