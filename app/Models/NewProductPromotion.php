<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * NewProductPromotion Model
 *
 * โปรโมทสินค้าใหม่ฟรี 7 วัน
 * - 1 สินค้า / ร้าน / 7 วัน
 * - ระหว่างโปรโมท ไม่สามารถแก้ไข/ลบสินค้าได้
 * - หากต้องการแก้ไข ต้องติดต่อ Admin ผ่าน Ticket
 *
 * @property int $id
 * @property int $product_id
 * @property int $store_id
 * @property int|null $requested_by
 * @property string $status
 * @property \Carbon\Carbon|null $starts_at
 * @property \Carbon\Carbon|null $ends_at
 * @property bool $is_locked
 * @property string|null $lock_reason
 * @property int $views_during_promo
 * @property int $sales_during_promo
 */
class NewProductPromotion extends Model
{
    /**
     * ตารางที่ใช้
     *
     * @var string
     */
    protected $table = 'new_product_promotions';

    /**
     * ฟิลด์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'product_id',
        'store_id',
        'requested_by',
        'status',
        'starts_at',
        'ends_at',
        'is_locked',
        'lock_reason',
        'views_during_promo',
        'sales_during_promo',
    ];

    /**
     * การ cast ประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_locked' => 'boolean',
        'views_during_promo' => 'integer',
        'sales_during_promo' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * สถานะ
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * จำนวนวันโปรโมท
     */
    public const PROMOTION_DAYS = 7;

    /**
     * Cooldown วัน (1 สินค้า / 7 วัน)
     */
    public const COOLDOWN_DAYS = 7;

    // ===== Relationships =====

    /**
     * สินค้า
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * ร้านค้า
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    /**
     * ผู้ขอโปรโมท
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    // ===== Scopes =====

    /**
     * กำลังโปรโมทอยู่
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('ends_at', '>', now());
    }

    /**
     * หมดอายุแล้ว
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_EXPIRED)
                ->orWhere(function ($sq) {
                    $sq->where('status', self::STATUS_ACTIVE)
                        ->where('ends_at', '<=', now());
                });
        });
    }

    /**
     * รอดำเนินการ
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    // ===== Static Methods =====

    /**
     * ตรวจสอบว่าร้านสามารถขอโปรโมทได้หรือไม่
     *
     * @return array ['can_promote' => bool, 'reason' => string|null, 'available_at' => Carbon|null]
     */
    public static function canStorePromote(int $storeId): array
    {
        // ตรวจสอบว่ามีโปรโมทที่ยังไม่หมดอายุหรือไม่
        $activePromo = self::where('store_id', $storeId)
            ->active()
            ->first();

        if ($activePromo) {
            return [
                'can_promote' => false,
                'reason' => 'ร้านค้ามีสินค้าที่กำลังโปรโมทอยู่แล้ว',
                'available_at' => $activePromo->ends_at,
                'current_product_id' => $activePromo->product_id,
            ];
        }

        // ตรวจสอบ cooldown
        $cooldown = StorePromotionCooldown::where('store_id', $storeId)
            ->where('promotion_type', 'new_product')
            ->first();

        if ($cooldown && $cooldown->available_at && $cooldown->available_at->isFuture()) {
            return [
                'can_promote' => false,
                'reason' => 'ยังไม่ถึงเวลาที่สามารถโปรโมทสินค้าใหม่ได้',
                'available_at' => $cooldown->available_at,
            ];
        }

        return [
            'can_promote' => true,
            'reason' => null,
            'available_at' => null,
        ];
    }

    /**
     * ขอโปรโมทสินค้าใหม่
     */
    public static function requestPromotion(Product $product, int $storeId, ?int $requestedBy = null): ?self
    {
        // ตรวจสอบสิทธิ์
        $check = self::canStorePromote($storeId);
        if (! $check['can_promote']) {
            return null;
        }

        // ตรวจสอบว่าสินค้านี้เคยโปรโมทแล้วหรือยัง
        if (self::where('product_id', $product->id)->exists()) {
            return null;
        }

        return DB::transaction(function () use ($product, $storeId, $requestedBy) {
            $startsAt = now();
            $endsAt = now()->addDays(self::PROMOTION_DAYS);

            // สร้างรายการโปรโมท
            $promo = self::create([
                'product_id' => $product->id,
                'store_id' => $storeId,
                'requested_by' => $requestedBy,
                'status' => self::STATUS_ACTIVE,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'is_locked' => true,
                'lock_reason' => 'ล็อคระหว่างช่วงโปรโมทสินค้าใหม่ 7 วัน หากต้องการแก้ไขกรุณาติดต่อแอดมิน',
            ]);

            // อัพเดท cooldown
            StorePromotionCooldown::updateOrCreate(
                [
                    'store_id' => $storeId,
                    'promotion_type' => 'new_product',
                ],
                [
                    'last_used_at' => now(),
                    'available_at' => now()->addDays(self::COOLDOWN_DAYS),
                    'usage_count' => DB::raw('usage_count + 1'),
                ]
            );

            // ล็อคสินค้า
            $product->update([
                'is_promotion_locked' => true,
                'promotion_lock_until' => $endsAt,
            ]);

            // เพิ่มเข้า Official Shop
            OfficialShopProduct::addProduct(
                $product,
                OfficialShopProduct::TYPE_NEW_PRODUCT,
                0, // ไม่มี AI score
                null
            );

            return $promo;
        });
    }

    /**
     * สิ้นสุดโปรโมท
     */
    public function endPromotion(): bool
    {
        return DB::transaction(function () {
            $this->update([
                'status' => self::STATUS_EXPIRED,
                'is_locked' => false,
            ]);

            // ปลดล็อคสินค้า
            $this->product?->update([
                'is_promotion_locked' => false,
                'promotion_lock_until' => null,
            ]);

            // ถอดออกจาก Official Shop (สินค้าใหม่)
            $ospEntry = OfficialShopProduct::where('product_id', $this->product_id)
                ->where('selection_type', OfficialShopProduct::TYPE_NEW_PRODUCT)
                ->first();

            if ($ospEntry) {
                $ospEntry->removeFromOfficialShop('โปรโมทสินค้าใหม่หมดอายุ');
            }

            return true;
        });
    }

    /**
     * ยกเลิกโปรโมท
     */
    public function cancelPromotion(?string $reason = null): bool
    {
        return DB::transaction(function () use ($reason) {
            $this->update([
                'status' => self::STATUS_CANCELLED,
                'is_locked' => false,
                'lock_reason' => $reason,
            ]);

            // ปลดล็อคสินค้า
            $this->product?->update([
                'is_promotion_locked' => false,
                'promotion_lock_until' => null,
            ]);

            // ถอดออกจาก Official Shop
            $ospEntry = OfficialShopProduct::where('product_id', $this->product_id)
                ->where('selection_type', OfficialShopProduct::TYPE_NEW_PRODUCT)
                ->first();

            if ($ospEntry) {
                $ospEntry->removeFromOfficialShop($reason ?? 'ยกเลิกโปรโมท');
            }

            return true;
        });
    }

    /**
     * เพิ่มจำนวนการดู
     */
    public function incrementViews(): void
    {
        $this->increment('views_during_promo');
    }

    /**
     * เพิ่มจำนวนการขาย
     */
    public function incrementSales(int $count = 1): void
    {
        $this->increment('sales_during_promo', $count);
    }

    // ===== Accessors =====

    /**
     * สถานะแสดงผล
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'รอดำเนินการ',
            self::STATUS_ACTIVE => 'กำลังโปรโมท',
            self::STATUS_EXPIRED => 'หมดอายุ',
            self::STATUS_CANCELLED => 'ยกเลิก',
            default => $this->status,
        };
    }

    /**
     * เวลาคงเหลือ
     */
    public function getTimeRemainingAttribute(): ?string
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return null;
        }

        if (! $this->ends_at || $this->ends_at->isPast()) {
            return 'หมดอายุแล้ว';
        }

        return $this->ends_at->diffForHumans();
    }

    /**
     * กำลังโปรโมทอยู่หรือไม่
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->ends_at
            && $this->ends_at->isFuture();
    }
}
