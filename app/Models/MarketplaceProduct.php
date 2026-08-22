<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'platform_id',
        'fulfillment_mode',
        'external_product_id',
        'external_sku',
        'name',
        'description',
        'category',
        'category_l1_id',
        'brand',
        'brand_id',
        'seller_id',
        'seller_name',
        'price',
        'original_price',
        'cost_price',
        'selling_price',
        'markup_percent',
        'price_is_manual',
        'currency',
        'stock_quantity',
        'is_available',
        'main_image_url',
        'images',
        'affiliate_url',
        'affiliate_deeplink',
        'affiliate_link_fetched_at',
        'commission_rate',
        'commission_amount',
        'hyper_commission_rate',
        'cps_commission_rate',
        'cps_commission_amount',
        'bonus_offer_flag',
        'can_get_link',
        'offer_type',
        'attributes',
        'variants',
        'tags',
        'view_count',
        'share_count',
        'click_count',
        'sales_count',
        'sales_7d',
        'rating',
        'review_count',
        'sync_status',
        'source',
        'source_url',
        'internal_product_id',
        'published_at',
        'is_active',
        'last_synced_at',
        // 🔮 (2026-08-22) กำกับของสายมู + ด่านอนุมัติ — ดู migration add_mu_columns_*
        //    `source` เชื่อไม่ได้แล้ว (mu_curated 900 แถว แต่ไฟล์คัดสรรมี 105) ⇒ ใช้ mu_group แทน
        'mu_group',
        'mu_keyword_id',
        'approval_status',
        'mu_verified_at',
        'rejected_reason',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'markup_percent' => 'decimal:2',
        'price_is_manual' => 'boolean',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'hyper_commission_rate' => 'decimal:5',
        'cps_commission_rate' => 'decimal:5',
        'cps_commission_amount' => 'decimal:2',
        'rating' => 'decimal:2',
        'bonus_offer_flag' => 'boolean',
        'can_get_link' => 'boolean',
        'affiliate_link_fetched_at' => 'datetime',
        'is_available' => 'boolean',
        'is_active' => 'boolean',
        'images' => 'array',
        'attributes' => 'array',
        'variants' => 'array',
        'tags' => 'array',
        'published_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'mu_verified_at' => 'datetime',
    ];

    /** ผ่านการตรวจแล้ว — ส่งให้ลูกค้า/ขึ้นหน้าร้านได้ */
    public const APPROVAL_APPROVED = 'approved';

    /** รอคนตรวจ — ส่งลิงก์ให้ลูกค้าได้ แต่ยังไม่ขึ้นหน้าร้าน */
    public const APPROVAL_PENDING = 'pending';

    /** ตีกลับ — ห้ามใช้ทุกกรณี */
    public const APPROVAL_REJECTED = 'rejected';

    /**
     * Get the account that owns this product
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'account_id');
    }

    /**
     * Get the platform for this product
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(MarketplacePlatform::class, 'platform_id');
    }

    /**
     * Get all affiliate links for this product
     */
    public function affiliateLinks(): HasMany
    {
        return $this->hasMany(MarketplaceAffiliateLink::class, 'product_id');
    }

    /**
     * Get all order items for this product
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(MarketplaceOrderItem::class, 'product_id');
    }

    // ════════════════════════════════════════════════════════════
    //  ราคา (Lazada Hub) — ต้นทุน / ราคาเรา / กำไร / ปัดเศษ
    // ════════════════════════════════════════════════════════════

    /**
     * ราคาต้นทุน (ฐานคำนวณ) — ใช้ cost_price ถ้ามี ไม่งั้น fallback ไป price (ราคาบน Lazada)
     */
    public function getEffectiveCostAttribute(): float
    {
        return (float) ($this->cost_price ?? $this->price ?? 0);
    }

    /**
     * ราคาขายที่เราตั้ง (โหมด resell)
     * ลำดับ: selling_price (ตั้งเอง) → คำนวณจาก ต้นทุน × markup → ต้นทุนเปล่า
     */
    public function getEffectiveSellingPriceAttribute(): float
    {
        if ($this->selling_price !== null) {
            return (float) $this->selling_price;
        }

        $cost = $this->effective_cost;

        if ($this->markup_percent !== null) {
            return round($cost * (1 + ((float) $this->markup_percent / 100)), 2);
        }

        return $cost;
    }

    /**
     * กำไรต่อชิ้น (โหมด resell) = ราคาเรา − ต้นทุน
     */
    public function getProfitAttribute(): float
    {
        return round($this->effective_selling_price - $this->effective_cost, 2);
    }

    /**
     * อัตรากำไร (%) เทียบกับต้นทุน
     */
    public function getProfitPercentAttribute(): float
    {
        $cost = $this->effective_cost;

        if ($cost <= 0) {
            return 0.0;
        }

        return round(($this->profit / $cost) * 100, 2);
    }

    /**
     * คำนวณราคาขายจาก ต้นทุน + markup% + กฎปัดเศษ (ใช้ตอนนำเข้า/ตั้งราคาอัตโนมัติ)
     *
     * @param  float  $cost  ต้นทุน (บาท)
     * @param  float  $markupPercent  เปอร์เซ็นต์บวกกำไร
     * @param  string|null  $rounding  กฎปัดเศษ (ดู applyRounding)
     */
    public static function computeSellingPrice(float $cost, float $markupPercent, ?string $rounding = 'baht'): float
    {
        $price = $cost * (1 + ($markupPercent / 100));

        return self::applyRounding($price, $rounding);
    }

    /**
     * ปัดราคาให้สวยตามกฎ
     *  - 'none'  : ปัด 2 ตำแหน่ง (ไม่ปัดสวย)
     *  - 'baht'  : ปัดขึ้นเป็นจำนวนเต็มบาท
     *  - 'ten'   : ปัดขึ้นเป็นหลักสิบ (เช่น 1,234 → 1,240)
     *  - 'nine'  : ลงท้ายเลข 9 (เช่น 1,234 → 1,239)
     */
    public static function applyRounding(float $price, ?string $rounding): float
    {
        if ($price <= 0) {
            return 0.0;
        }

        return match ($rounding) {
            'baht' => (float) ceil($price),
            'ten' => (float) (ceil($price / 10) * 10),
            // ลงท้าย 9 แต่กันต่ำกว่าราคาที่คำนวณ (เช่น cost 1000 markup 0 → 1009 ไม่ใช่ 999 = ขาดทุน)
            'nine' => (function () use ($price) {
                $r = ceil($price / 10) * 10 - 1;

                return (float) ($r < $price ? $r + 10 : $r);
            })(),
            default => round($price, 2),
        };
    }

    /**
     * Scope กรองตามโหมด (affiliate / resell)
     */
    public function scopeMode($query, string $mode)
    {
        return $query->where('fulfillment_mode', $mode);
    }

    /**
     * Scope to get only active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only available products
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)
            ->where('stock_quantity', '>', 0);
    }

    /**
     * Scope to search products
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
            ->orWhere('description', 'like', "%{$search}%")
            ->orWhere('brand', 'like', "%{$search}%");
    }

    /**
     * Scope by platform
     */
    public function scopeByPlatform($query, $platformId)
    {
        return $query->where('platform_id', $platformId);
    }

    /**
     * 🔮 Scope เฉพาะของสายมูจริง
     *
     * 🚨 ห้ามใช้ `where('source', 'mu_curated')` แทนเด็ดขาด —
     *    พร็อดมี 900 แถวที่ป้ายนั้น แต่ไฟล์คัดสรรมีแค่ 105 id
     *    ⇒ อีก 795 แถวเป็นแฟ้ม A4 / สายชาร์จ / เวเฟอร์ ที่ค่าคอมสูงกว่าของสายมูจริงเสียอีก
     *    ⇒ ลูกค้าถามของแก้ปีชง แล้วแม่หมอส่งสายชาร์จไปให้
     *
     * @param  string|null  $group  ระบุกลุ่ม (pixiu/pichong/…) หรือ null = สายมูทุกกลุ่ม
     */
    public function scopeMu($query, ?string $group = null)
    {
        $query->whereNotNull('mu_group');

        return $group !== null ? $query->where('mu_group', $group) : $query;
    }

    /**
     * Scope เฉพาะของที่ผ่านการตรวจแล้ว
     */
    public function scopeApproved($query)
    {
        return $query->where('approval_status', self::APPROVAL_APPROVED);
    }

    /**
     * Scope ของที่พร้อมส่งให้ลูกค้าในแชท — ต้องมีครบ 3 อย่าง
     *   ลิงก์ค่าคอม (ไม่งั้นส่งไปก็ไม่ได้เงิน) · รูป (ลูกค้าต้องเห็นของ) · ของยังมีขาย
     */
    public function scopeSendableInChat($query)
    {
        return $query->where('is_active', true)
            ->where('is_available', true)
            ->whereNotNull('affiliate_url')
            ->where('affiliate_url', '!=', '')
            ->whereNotNull('main_image_url')
            ->where('main_image_url', '!=', '');
    }

    /**
     * Increment view count
     */
    public function incrementViews()
    {
        $this->increment('view_count');
    }

    /**
     * Increment click count
     */
    public function incrementClicks()
    {
        $this->increment('click_count');
    }

    /**
     * Increment share count
     */
    public function incrementShares()
    {
        $this->increment('share_count');
    }
}
