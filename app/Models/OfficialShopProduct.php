<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * OfficialShopProduct Model
 *
 * สินค้าที่ถูกคัดเลือกเข้า Official Shop
 * - AI คัดเลือกจากคะแนนรวม (ai_featured)
 * - สินค้าใหม่โปรโมท 7 วัน (new_product)
 * - สินค้าขายดีรายเดือน (best_seller)
 *
 * @property int $id
 * @property int $product_id
 * @property int $store_id
 * @property string $selection_type
 * @property float $ai_score
 * @property array|null $score_breakdown
 * @property bool $is_active
 * @property bool $is_locked
 * @property string $badge_type
 * @property string $badge_color
 * @property \Carbon\Carbon $selected_at
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon|null $removed_at
 * @property string|null $removal_reason
 * @property int $display_order
 */
class OfficialShopProduct extends Model
{
    /**
     * ตารางที่ใช้
     *
     * @var string
     */
    protected $table = 'official_shop_products';

    /**
     * ฟิลด์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'product_id',
        'store_id',
        'selection_type',
        'ai_score',
        'score_breakdown',
        'is_active',
        'is_locked',
        'badge_type',
        'badge_color',
        'selected_at',
        'expires_at',
        'removed_at',
        'removal_reason',
        'display_order',
    ];

    /**
     * การ cast ประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'score_breakdown' => 'array',
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
        'ai_score' => 'decimal:2',
        'display_order' => 'integer',
        'selected_at' => 'datetime',
        'expires_at' => 'datetime',
        'removed_at' => 'datetime',
    ];

    /**
     * ประเภทการคัดเลือก
     */
    public const TYPE_AI_FEATURED = 'ai_featured';

    public const TYPE_NEW_PRODUCT = 'new_product';

    public const TYPE_BEST_SELLER = 'best_seller';

    /**
     * ประเภทกรอบ
     */
    public const BADGE_GOLD = 'gold';

    public const BADGE_NEW = 'new';

    public const BADGE_BESTSELLER = 'bestseller';

    // ===== Relationships =====

    /**
     * สินค้าต้นทาง
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * ร้านค้าเจ้าของสินค้า
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    /**
     * การแจ้งเตือน
     */
    public function warnings(): HasMany
    {
        return $this->hasMany(OfficialShopWarning::class);
    }

    // ===== Scopes =====

    /**
     * เฉพาะที่ active
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * เฉพาะ AI Featured
     */
    public function scopeAiFeatured($query)
    {
        return $query->where('selection_type', self::TYPE_AI_FEATURED);
    }

    /**
     * เฉพาะสินค้าใหม่
     */
    public function scopeNewProducts($query)
    {
        return $query->where('selection_type', self::TYPE_NEW_PRODUCT);
    }

    /**
     * เฉพาะสินค้าขายดี
     */
    public function scopeBestSellers($query)
    {
        return $query->where('selection_type', self::TYPE_BEST_SELLER);
    }

    /**
     * ยังไม่หมดอายุ
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * หมดอายุแล้ว
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * คะแนนต่ำกว่าเกณฑ์
     */
    public function scopeBelowMinScore($query, float $minScore)
    {
        return $query->where('ai_score', '<', $minScore);
    }

    // ===== Static Methods =====

    /**
     * เพิ่มสินค้าเข้า Official Shop
     */
    public static function addProduct(
        Product $product,
        string $selectionType = self::TYPE_AI_FEATURED,
        float $aiScore = 0,
        ?array $scoreBreakdown = null
    ): self {
        return DB::transaction(function () use ($product, $selectionType, $aiScore, $scoreBreakdown) {
            // กำหนดประเภทกรอบตาม selection type
            $badgeType = match ($selectionType) {
                self::TYPE_AI_FEATURED => self::BADGE_GOLD,
                self::TYPE_NEW_PRODUCT => self::BADGE_NEW,
                self::TYPE_BEST_SELLER => self::BADGE_BESTSELLER,
                default => self::BADGE_GOLD,
            };

            // กำหนดสีกรอบ
            $badgeColor = match ($badgeType) {
                self::BADGE_GOLD => '#FFD700',
                self::BADGE_NEW => '#10B981',
                self::BADGE_BESTSELLER => '#EF4444',
                default => '#FFD700',
            };

            // กำหนดวันหมดอายุ (สินค้าใหม่ 7 วัน)
            $expiresAt = null;
            $isLocked = false;

            if ($selectionType === self::TYPE_NEW_PRODUCT) {
                $expiresAt = now()->addDays(7);
                $isLocked = true;
            }

            // สร้างรายการ
            $entry = self::create([
                'product_id' => $product->id,
                'store_id' => $product->store_id ?? $product->seller_id,
                'selection_type' => $selectionType,
                'ai_score' => $aiScore,
                'score_breakdown' => $scoreBreakdown,
                'is_active' => true,
                'is_locked' => $isLocked,
                'badge_type' => $badgeType,
                'badge_color' => $badgeColor,
                'selected_at' => now(),
                'expires_at' => $expiresAt,
            ]);

            // อัพเดทสินค้า
            $product->update([
                'is_in_official_shop' => true,
                'official_shop_badge' => $badgeType,
                'is_promotion_locked' => $isLocked,
                'promotion_lock_until' => $expiresAt,
            ]);

            return $entry;
        });
    }

    /**
     * ถอดสินค้าออกจาก Official Shop
     */
    public function removeFromOfficialShop(?string $reason = null): bool
    {
        return DB::transaction(function () use ($reason) {
            $this->update([
                'is_active' => false,
                'removed_at' => now(),
                'removal_reason' => $reason,
            ]);

            // ตรวจสอบว่ายังมี entry อื่นใน Official Shop หรือไม่
            $otherEntries = self::where('product_id', $this->product_id)
                ->where('id', '!=', $this->id)
                ->active()
                ->exists();

            if (! $otherEntries) {
                $this->product?->update([
                    'is_in_official_shop' => false,
                    'official_shop_badge' => null,
                    'is_promotion_locked' => false,
                    'promotion_lock_until' => null,
                ]);
            }

            return true;
        });
    }

    /**
     * ตรวจสอบว่าสินค้านี้อยู่ใน Official Shop หรือไม่
     */
    public static function isInOfficialShop(int $productId, ?string $selectionType = null): bool
    {
        $query = self::where('product_id', $productId)->active();

        if ($selectionType) {
            $query->where('selection_type', $selectionType);
        }

        return $query->exists();
    }

    /**
     * ดึงสินค้า Featured สำหรับแสดงหน้าบ้าน
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getFeaturedProducts(int $limit = 20)
    {
        return self::with(['product.category', 'product.images', 'store'])
            ->aiFeatured()
            ->active()
            ->notExpired()
            ->orderByDesc('ai_score')
            ->orderBy('display_order')
            ->limit($limit)
            ->get();
    }

    /**
     * ดึงสินค้าใหม่ที่กำลังโปรโมท
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getNewPromotionProducts(int $limit = 10)
    {
        return self::with(['product.category', 'product.images', 'store'])
            ->newProducts()
            ->active()
            ->notExpired()
            ->orderByDesc('selected_at')
            ->limit($limit)
            ->get();
    }

    /**
     * ดึงสินค้าขายดีเดือนนี้
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getBestSellerProducts(int $limit = 10)
    {
        return self::with(['product.category', 'product.images', 'store'])
            ->bestSellers()
            ->active()
            ->orderBy('display_order')
            ->limit($limit)
            ->get();
    }

    // ===== Accessors =====

    /**
     * สถานะกรอบแสดงผล
     */
    public function getBadgeLabelAttribute(): string
    {
        return match ($this->badge_type) {
            self::BADGE_GOLD => 'AI แนะนำ',
            self::BADGE_NEW => 'สินค้าใหม่',
            self::BADGE_BESTSELLER => 'ขายดี',
            default => '',
        };
    }

    /**
     * ตรวจสอบว่าหมดอายุหรือยัง
     */
    public function getIsExpiredAttribute(): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * เวลาคงเหลือก่อนหมดอายุ
     */
    public function getTimeRemainingAttribute(): ?string
    {
        if (! $this->expires_at) {
            return null;
        }

        if ($this->is_expired) {
            return 'หมดอายุแล้ว';
        }

        return $this->expires_at->diffForHumans();
    }
}
