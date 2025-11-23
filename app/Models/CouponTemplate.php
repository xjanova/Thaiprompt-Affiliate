<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Template คูปองส่วนลด
 *
 * ใช้สำหรับสร้างคูปองอัตโนมัติในระบบรางวัล
 *
 * @property int $id
 * @property string $name ชื่อ Template
 * @property string|null $description คำอธิบาย
 * @property string $owner_type เจ้าของ (admin/seller)
 * @property int|null $seller_id ID ผู้ขาย
 * @property string $code_prefix Prefix รหัส
 * @property string $code_format รูปแบบรหัส
 * @property int $code_length ความยาวรหัส
 * @property string $discount_type ประเภทส่วนลด
 * @property float $discount_value มูลค่าส่วนลด
 * @property float $min_purchase ยอดซื้อขั้นต่ำ
 * @property float|null $max_discount ส่วนลดสูงสุด
 * @property int $usage_limit จำนวนครั้งที่ใช้ได้
 * @property int $validity_days ระยะเวลาใช้งาน
 * @property array|null $applicable_products สินค้าที่ใช้ได้
 * @property array|null $applicable_categories หมวดหมู่ที่ใช้ได้
 * @property array|null $excluded_products สินค้าที่ใช้ไม่ได้
 * @property bool $is_active เปิดใช้งาน?
 */
class CouponTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'owner_type',
        'seller_id',
        'code_prefix',
        'code_format',
        'code_length',
        'discount_type',
        'discount_value',
        'min_purchase',
        'max_discount',
        'usage_limit',
        'validity_days',
        'applicable_products',
        'applicable_categories',
        'excluded_products',
        'is_active',
    ];

    protected $casts = [
        'applicable_products' => 'array',
        'applicable_categories' => 'array',
        'excluded_products' => 'array',
        'is_active' => 'boolean',
        'discount_value' => 'decimal:2',
        'min_purchase' => 'decimal:2',
        'max_discount' => 'decimal:2',
    ];

    /**
     * ความสัมพันธ์กับผู้ขาย
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * ความสัมพันธ์กับคูปองที่สร้างจาก Template นี้
     */
    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class, 'template_id');
    }

    /**
     * ดึงข้อความแสดงส่วนลด
     */
    public function getDiscountText(): string
    {
        switch ($this->discount_type) {
            case 'percentage':
                return $this->discount_value . '%';
            case 'fixed':
                return '฿' . number_format($this->discount_value);
            case 'free_shipping':
                return 'ส่งฟรี';
            default:
                return '';
        }
    }
}
