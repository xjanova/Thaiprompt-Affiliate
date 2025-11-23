<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * คูปองส่วนลด
 *
 * คูปองที่ผู้ใช้ได้รับและสามารถนำไปใช้ได้
 *
 * @property int $id
 * @property string $code รหัสคูปอง
 * @property int|null $user_id เจ้าของคูปอง
 * @property int|null $template_id Template ที่สร้างมา
 * @property string $discount_type ประเภทส่วนลด
 * @property float $discount_value มูลค่าส่วนลด
 * @property float $min_purchase ยอดซื้อขั้นต่ำ
 * @property float|null $max_discount ส่วนลดสูงสุด
 * @property int $usage_limit จำนวนครั้งที่ใช้ได้
 * @property int $used_count จำนวนครั้งที่ใช้ไปแล้ว
 * @property \Carbon\Carbon|null $expires_at วันหมดอายุ
 * @property bool $is_active เปิดใช้งาน?
 */
class Coupon extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'user_id',
        'template_id',
        'discount_type',
        'discount_value',
        'min_purchase',
        'max_discount',
        'usage_limit',
        'used_count',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'discount_value' => 'decimal:2',
        'min_purchase' => 'decimal:2',
        'max_discount' => 'decimal:2',
    ];

    /**
     * ความสัมพันธ์กับผู้ใช้
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ Template
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(CouponTemplate::class);
    }

    /**
     * ตรวจสอบว่าคูปองใช้งานได้หรือไม่
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && now()->gt($this->expires_at)) {
            return false;
        }

        if ($this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * คำนวณส่วนลด
     */
    public function calculateDiscount(float $orderTotal): float
    {
        if ($orderTotal < $this->min_purchase) {
            return 0;
        }

        switch ($this->discount_type) {
            case 'percentage':
                $discount = $orderTotal * ($this->discount_value / 100);
                if ($this->max_discount) {
                    $discount = min($discount, $this->max_discount);
                }
                return $discount;

            case 'fixed':
                return min($this->discount_value, $orderTotal);

            case 'free_shipping':
                return 0; // จะคำนวณในส่วนของค่าจัดส่ง

            default:
                return 0;
        }
    }
}
