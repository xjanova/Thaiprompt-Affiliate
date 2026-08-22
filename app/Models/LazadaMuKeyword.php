<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * LazadaMuKeyword — คีย์เวิร์ดที่ระบบใช้ไล่เก็บสินค้าจาก Lazada
 *
 * แหล่งที่มา 2 ทาง:
 *   - `admin` / `seed` = owner ตั้งเอง → เปิดใช้ทันที
 *   - `customer`       = ลูกค้าถามหาของในแชทแล้วเราไม่มี → บันทึกไว้ **ปิดไว้ก่อน**
 *                        รอ owner กดเปิด (ดู `ask_count` ว่ามีคนถามบ่อยแค่ไหน)
 *
 * @property int $id
 * @property string $keyword คำค้น
 * @property string|null $mu_group กลุ่มสายมู
 * @property int|null $product_category_id หมวดปลายทางบนหน้าร้าน
 * @property float|null $min_commission_rate ค่าคอมขั้นต่ำ (%)
 * @property float|null $min_price ราคาต่ำสุดที่รับ
 * @property float|null $max_price ราคาสูงสุดที่รับ
 * @property int $target_count เป้าหมายจำนวนชิ้น
 * @property int $imported_count เก็บได้แล้วกี่ชิ้น
 * @property int $ask_count ลูกค้าถามกี่ครั้ง
 * @property string $source admin | customer | seed
 * @property bool $is_active
 */
class LazadaMuKeyword extends Model
{
    protected $table = 'lazada_mu_keywords';

    protected $fillable = [
        'keyword',
        'mu_group',
        'product_category_id',
        'min_commission_rate',
        'min_price',
        'max_price',
        'target_count',
        'imported_count',
        'ask_count',
        'source',
        'is_active',
        'last_scanned_at',
        'last_found_count',
        'last_error',
    ];

    protected $casts = [
        'min_commission_rate' => 'decimal:2',
        'min_price' => 'decimal:2',
        'max_price' => 'decimal:2',
        'target_count' => 'integer',
        'imported_count' => 'integer',
        'ask_count' => 'integer',
        'last_found_count' => 'integer',
        'is_active' => 'boolean',
        'last_scanned_at' => 'datetime',
    ];

    /** คีย์เวิร์ดที่ owner ตั้งเอง */
    public const SOURCE_ADMIN = 'admin';

    /** คีย์เวิร์ดที่เกิดจากลูกค้าถามหาของในแชท */
    public const SOURCE_CUSTOMER = 'customer';

    /** คีย์เวิร์ดตั้งต้นจาก seeder */
    public const SOURCE_SEED = 'seed';

    /**
     * หมวดปลายทางบนหน้าร้าน
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /**
     * สินค้าที่เก็บมาได้จากคีย์เวิร์ดนี้
     */
    public function products(): HasMany
    {
        return $this->hasMany(MarketplaceProduct::class, 'mu_keyword_id');
    }

    /**
     * เฉพาะคีย์เวิร์ดที่เปิดใช้อยู่
     */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /**
     * คีย์เวิร์ดที่ยังเก็บของไม่ครบเป้า — ตัวนี้คือคิวงานของ cron
     */
    public function scopeUnfinished(Builder $q): Builder
    {
        return $q->whereColumn('imported_count', '<', 'target_count');
    }

    /**
     * ถึงคิวสแกนหรือยัง (เว้นระยะกี่ชั่วโมงต่อคำ)
     *
     * @param  int  $cooldownHours  เว้นกี่ชั่วโมงถึงจะยิงคำเดิมซ้ำ
     */
    public function scopeDueForScan(Builder $q, int $cooldownHours = 6): Builder
    {
        return $q->where(function ($w) use ($cooldownHours) {
            $w->whereNull('last_scanned_at')
                ->orWhere('last_scanned_at', '<=', now()->subHours($cooldownHours));
        });
    }

    /**
     * ค่าคอมขั้นต่ำที่ใช้จริงกับคำนี้ (ไม่ได้ตั้งรายคำ = ใช้ค่ากลาง)
     *
     * @param  float  $fallback  ค่ากลางจาก MarketplaceSetting
     */
    public function effectiveMinCommission(float $fallback): float
    {
        return $this->min_commission_rate !== null
            ? (float) $this->min_commission_rate
            : $fallback;
    }
}
