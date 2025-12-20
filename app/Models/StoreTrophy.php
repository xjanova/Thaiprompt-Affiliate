<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * StoreTrophy Model
 *
 * Trophy/รางวัลสำหรับร้านค้าตามผลงาน
 *
 * @property int $id
 * @property string $name
 * @property string $name_th
 * @property string|null $description
 * @property string|null $description_th
 * @property string $icon
 * @property string $icon_color
 * @property string $badge_color
 * @property string|null $badge_gradient_from
 * @property string|null $badge_gradient_to
 * @property string|null $image_url
 * @property string $requirement_type
 * @property float $requirement_value
 * @property string $tier
 * @property int $points
 * @property bool $is_active
 * @property int $display_order
 * @property bool $is_premium
 */
class StoreTrophy extends Model
{
    use HasFactory;

    protected $table = 'store_trophies';

    protected $fillable = [
        'name',
        'name_th',
        'description',
        'description_th',
        'icon',
        'icon_color',
        'badge_color',
        'badge_gradient_from',
        'badge_gradient_to',
        'image_url',
        'requirement_type',
        'requirement_value',
        'tier',
        'points',
        'is_active',
        'display_order',
        'is_premium',
    ];

    protected $casts = [
        'requirement_value' => 'decimal:2',
        'points' => 'integer',
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'is_premium' => 'boolean',
    ];

    /**
     * ประเภทเงื่อนไขที่รองรับ
     */
    public const REQUIREMENT_TYPES = [
        'sales_count' => 'จำนวนการขาย',
        'order_count' => 'จำนวนออเดอร์',
        'rating' => 'คะแนนรีวิว',
        'followers' => 'จำนวนผู้ติดตาม',
        'products' => 'จำนวนสินค้า',
        'revenue' => 'รายได้รวม',
        'response_rate' => 'อัตราการตอบกลับ',
        'shipping_speed' => 'ความเร็วจัดส่ง',
    ];

    /**
     * ระดับ Tier
     */
    public const TIERS = [
        'bronze' => ['name' => 'Bronze', 'name_th' => 'บรอนซ์', 'color' => '#CD7F32'],
        'silver' => ['name' => 'Silver', 'name_th' => 'เงิน', 'color' => '#C0C0C0'],
        'gold' => ['name' => 'Gold', 'name_th' => 'ทอง', 'color' => '#FFD700'],
        'platinum' => ['name' => 'Platinum', 'name_th' => 'แพลทินัม', 'color' => '#E5E4E2'],
        'diamond' => ['name' => 'Diamond', 'name_th' => 'เพชร', 'color' => '#B9F2FF'],
    ];

    /**
     * ร้านค้าที่ได้รับ Trophy นี้
     */
    public function achievements(): HasMany
    {
        return $this->hasMany(StoreTrophyAchievement::class, 'trophy_id');
    }

    /**
     * ดึง Trophy ที่ active
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * ดึง Trophy ตาม tier
     */
    public function scopeByTier($query, string $tier)
    {
        return $query->where('tier', $tier);
    }

    /**
     * ดึง Trophy สำหรับ Premium
     */
    public function scopePremiumOnly($query)
    {
        return $query->where('is_premium', true);
    }

    /**
     * ตรวจสอบว่าร้านค้าผ่านเกณฑ์หรือไม่
     *
     * @param VendorStore $store
     * @return bool
     */
    public function checkRequirement(VendorStore $store): bool
    {
        $value = match ($this->requirement_type) {
            'sales_count' => $store->total_orders ?? 0,
            'order_count' => $store->total_orders ?? 0,
            'rating' => $store->rating_average ?? 0,
            'followers' => $store->followers_count ?? 0,
            'products' => $store->total_products ?? 0,
            'revenue' => $store->total_revenue ?? 0,
            'response_rate' => $store->response_rate ?? 0,
            'shipping_speed' => $store->avg_shipping_days ?? 99,
            default => 0,
        };

        // สำหรับ shipping_speed ยิ่งน้อยยิ่งดี
        if ($this->requirement_type === 'shipping_speed') {
            return $value <= $this->requirement_value;
        }

        return $value >= $this->requirement_value;
    }

    /**
     * ให้ Trophy กับร้านค้า
     *
     * @param VendorStore $store
     * @return StoreTrophyAchievement|null
     */
    public function awardTo(VendorStore $store): ?StoreTrophyAchievement
    {
        // ตรวจสอบว่าได้รับแล้วหรือยัง
        $existing = StoreTrophyAchievement::where('store_id', $store->id)
            ->where('trophy_id', $this->id)
            ->first();

        if ($existing) {
            return null;
        }

        // ตรวจสอบเงื่อนไข
        if (!$this->checkRequirement($store)) {
            return null;
        }

        // สร้าง achievement
        $achievement = StoreTrophyAchievement::create([
            'store_id' => $store->id,
            'trophy_id' => $this->id,
            'achieved_at' => now(),
            'snapshot' => [
                'sales_count' => $store->total_orders ?? 0,
                'rating' => $store->rating_average ?? 0,
                'followers' => $store->followers_count ?? 0,
                'products' => $store->total_products ?? 0,
                'revenue' => $store->total_revenue ?? 0,
            ],
        ]);

        // อัพเดท trophy points
        $store->increment('trophy_points', $this->points);

        return $achievement;
    }

    /**
     * ดึงสีของ tier
     */
    public function getTierColorAttribute(): string
    {
        return self::TIERS[$this->tier]['color'] ?? '#CD7F32';
    }

    /**
     * ดึงชื่อ tier ภาษาไทย
     */
    public function getTierNameThAttribute(): string
    {
        return self::TIERS[$this->tier]['name_th'] ?? 'บรอนซ์';
    }
}
