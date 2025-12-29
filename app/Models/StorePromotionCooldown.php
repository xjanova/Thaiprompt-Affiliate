<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StorePromotionCooldown Model
 *
 * ป้องกันการ spam โปรโมท
 * - 1 สินค้าใหม่ / ร้าน / 7 วัน
 *
 * @property int $id
 * @property int $store_id
 * @property string $promotion_type
 * @property \Carbon\Carbon|null $last_used_at
 * @property \Carbon\Carbon|null $available_at
 * @property int $usage_count
 */
class StorePromotionCooldown extends Model
{
    /**
     * ตารางที่ใช้
     *
     * @var string
     */
    protected $table = 'store_promotion_cooldowns';

    /**
     * ฟิลด์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'store_id',
        'promotion_type',
        'last_used_at',
        'available_at',
        'usage_count',
    ];

    /**
     * การ cast ประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
        'available_at' => 'datetime',
    ];

    /**
     * ประเภทโปรโมท
     */
    public const TYPE_NEW_PRODUCT = 'new_product';

    // ===== Relationships =====

    /**
     * ร้านค้า
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    // ===== Scopes =====

    /**
     * พร้อมใช้งาน
     */
    public function scopeAvailable($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('available_at')
                ->orWhere('available_at', '<=', now());
        });
    }

    /**
     * ยังอยู่ใน cooldown
     */
    public function scopeOnCooldown($query)
    {
        return $query->where('available_at', '>', now());
    }

    // ===== Static Methods =====

    /**
     * ตรวจสอบว่าร้านพร้อมใช้งานหรือไม่
     */
    public static function isAvailable(int $storeId, string $promotionType): bool
    {
        $cooldown = self::where('store_id', $storeId)
            ->where('promotion_type', $promotionType)
            ->first();

        if (! $cooldown) {
            return true;
        }

        return ! $cooldown->available_at || $cooldown->available_at->isPast();
    }

    /**
     * ดึงเวลาที่จะพร้อมใช้งาน
     */
    public static function getAvailableAt(int $storeId, string $promotionType): ?\Carbon\Carbon
    {
        $cooldown = self::where('store_id', $storeId)
            ->where('promotion_type', $promotionType)
            ->first();

        if (! $cooldown || ! $cooldown->available_at) {
            return null;
        }

        if ($cooldown->available_at->isPast()) {
            return null;
        }

        return $cooldown->available_at;
    }

    /**
     * บันทึกการใช้งาน
     */
    public static function recordUsage(int $storeId, string $promotionType, int $cooldownDays = 7): self
    {
        return self::updateOrCreate(
            [
                'store_id' => $storeId,
                'promotion_type' => $promotionType,
            ],
            [
                'last_used_at' => now(),
                'available_at' => now()->addDays($cooldownDays),
                'usage_count' => \Illuminate\Support\Facades\DB::raw('usage_count + 1'),
            ]
        );
    }

    // ===== Accessors =====

    /**
     * ยังอยู่ใน cooldown หรือไม่
     */
    public function getIsOnCooldownAttribute(): bool
    {
        return $this->available_at && $this->available_at->isFuture();
    }

    /**
     * เวลาคงเหลือ
     */
    public function getTimeRemainingAttribute(): ?string
    {
        if (! $this->is_on_cooldown) {
            return null;
        }

        return $this->available_at->diffForHumans();
    }
}
