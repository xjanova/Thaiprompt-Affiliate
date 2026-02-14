<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rank extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_th',
        'description',
        'description_th',
        'level',
        'icon',
        'color',
        'badge_icon',
        'avatar_frame',
        'frame_animation',
        'stars',
        'commission_rate',
        'bonus_multiplier',
        'promotion_bonus',
        'max_downline_level_bonus',
        'unilevel_commission_levels',
        'privileges',
        'min_points',
        'min_referrals',
        'min_sales',
        'monthly_maintenance_pv',
        'withdrawal_fee_discount',
        'max_withdrawals_per_month',
        'is_active',
        'is_default',
        'is_top_tier',
        'sort_order',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'bonus_multiplier' => 'decimal:2',
        'promotion_bonus' => 'decimal:2',
        'min_sales' => 'decimal:2',
        'monthly_maintenance_pv' => 'decimal:2',
        'withdrawal_fee_discount' => 'decimal:2',
        'unilevel_commission_levels' => 'array',
        'privileges' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'is_top_tier' => 'boolean',
        'level' => 'integer',
        'min_points' => 'integer',
        'min_referrals' => 'integer',
        'stars' => 'integer',
        'max_downline_level_bonus' => 'integer',
        'max_withdrawals_per_month' => 'integer',
    ];

    /**
     * Get requirements for this rank
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(RankRequirement::class);
    }

    /**
     * Get active requirements
     */
    public function activeRequirements(): HasMany
    {
        return $this->requirements()->where('is_active', true);
    }

    /**
     * Get bonuses for this rank
     */
    public function bonuses(): HasMany
    {
        return $this->hasMany(RankBonus::class);
    }

    /**
     * Get active bonuses
     */
    public function activeBonuses(): HasMany
    {
        return $this->bonuses()->where('is_active', true);
    }

    /**
     * Get users with this rank
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'current_rank_id');
    }

    /**
     * Get MLM members with this rank
     *
     * Note: Rank is primarily tracked on User model, but MLM members can have separate ranks
     */
    public function mlmMembers(): HasMany
    {
        return $this->hasMany(MlmMember::class, 'rank_id');
    }

    /**
     * Get promotions to this rank
     */
    public function promotionsTo(): HasMany
    {
        return $this->hasMany(RankPromotion::class, 'to_rank_id');
    }

    /**
     * Get promotions from this rank
     */
    public function promotionsFrom(): HasMany
    {
        return $this->hasMany(RankPromotion::class, 'from_rank_id');
    }

    /**
     * Get user progress tracking for this rank
     */
    public function userProgress(): HasMany
    {
        return $this->hasMany(UserRankProgress::class, 'target_rank_id');
    }

    /**
     * Get display name based on current locale
     */
    public function getDisplayNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $locale === 'th' && $this->name_th ? $this->name_th : $this->name;
    }

    /**
     * Get display description based on current locale
     */
    public function getDisplayDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();

        return $locale === 'th' && $this->description_th ? $this->description_th : $this->description;
    }

    /**
     * Get icon URL
     */
    public function getIconUrlAttribute(): ?string
    {
        if (! $this->icon) {
            return null;
        }

        // ถ้าเป็น emoji หรือ FontAwesome class ไม่ใช่ URL
        if ($this->isIconEmoji() || $this->isIconFontAwesome()) {
            return null;
        }

        if (str_starts_with($this->icon, 'http')) {
            return $this->icon;
        }

        return asset($this->icon);
    }

    /**
     * ตรวจสอบว่า icon เป็น emoji หรือไม่
     */
    public function isIconEmoji(): bool
    {
        if (! $this->icon) {
            return false;
        }

        // Emoji pattern - ตรวจสอบว่ามี emoji character หรือไม่
        $emojiPattern = '/[\x{1F300}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u';

        return preg_match($emojiPattern, $this->icon) === 1;
    }

    /**
     * ตรวจสอบว่า icon เป็น FontAwesome class หรือไม่
     */
    public function isIconFontAwesome(): bool
    {
        if (! $this->icon) {
            return false;
        }

        // FontAwesome pattern: fa-, fas, far, fab, fal, fad
        return preg_match('/^(fa[srlbd]?|fa)\s/', $this->icon) === 1;
    }

    /**
     * ตรวจสอบว่า icon เป็น image path/URL หรือไม่
     */
    public function isIconImage(): bool
    {
        if (! $this->icon) {
            return false;
        }

        // ถ้าเป็น URL หรือ path ที่มี extension รูปภาพ
        if (str_starts_with($this->icon, 'http') || str_starts_with($this->icon, '/')) {
            return true;
        }

        // ตรวจสอบ extension
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
        $extension = strtolower(pathinfo($this->icon, PATHINFO_EXTENSION));

        return in_array($extension, $imageExtensions);
    }

    /**
     * ดึง icon type สำหรับการ render
     *
     * @return string 'emoji'|'fontawesome'|'image'|'none'
     */
    public function getIconTypeAttribute(): string
    {
        if (! $this->icon) {
            return 'none';
        }

        if ($this->isIconEmoji()) {
            return 'emoji';
        }

        if ($this->isIconFontAwesome()) {
            return 'fontawesome';
        }

        if ($this->isIconImage()) {
            return 'image';
        }

        return 'none';
    }

    /**
     * ดึง display icon - ใช้ badge_icon (emoji) ก่อน ถ้าไม่มีใช้ icon
     * สำหรับแสดงผลแบบ emoji/text
     */
    public function getDisplayIconAttribute(): string
    {
        // ใช้ badge_icon (emoji) เป็นหลัก
        if ($this->badge_icon) {
            return $this->badge_icon;
        }

        // ถ้า icon เป็น emoji ใช้ได้เลย
        if ($this->isIconEmoji()) {
            return $this->icon;
        }

        // Fallback เป็นตัวอักษรแรกของชื่อ
        return mb_substr($this->name ?? '?', 0, 1);
    }

    /**
     * Get badge icon URL
     */
    public function getBadgeIconUrlAttribute(): ?string
    {
        if (! $this->badge_icon) {
            return null;
        }

        if (str_starts_with($this->badge_icon, 'http')) {
            return $this->badge_icon;
        }

        return asset($this->badge_icon);
    }

    /**
     * Get avatar frame URL
     * คืนค่า URL กรอบอวาต้าร์ที่อัพโหลดโดย Admin
     */
    public function getAvatarFrameUrlAttribute(): ?string
    {
        if (! $this->avatar_frame) {
            return null;
        }

        if (str_starts_with($this->avatar_frame, 'http')) {
            return $this->avatar_frame;
        }

        return asset('storage/'.$this->avatar_frame);
    }

    /**
     * ตรวจสอบว่ามีกรอบอวาต้าร์ custom หรือไม่
     */
    public function hasCustomFrame(): bool
    {
        return ! empty($this->avatar_frame);
    }

    /**
     * ดึงประเภท animation ของกรอบ
     */
    public function getFrameAnimationType(): string
    {
        return $this->frame_animation ?? 'none';
    }

    /**
     * Scope to get active ranks
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by level
     */
    public function scopeByLevel($query)
    {
        return $query->orderBy('level');
    }

    /**
     * Get next rank
     */
    public function getNextRankAttribute(): ?Rank
    {
        return static::where('level', '>', $this->level)
            ->where('is_active', true)
            ->orderBy('level')
            ->first();
    }

    /**
     * Get previous rank
     */
    public function getPreviousRankAttribute(): ?Rank
    {
        return static::where('level', '<', $this->level)
            ->where('is_active', true)
            ->orderBy('level', 'desc')
            ->first();
    }

    /**
     * Check if user meets requirements for this rank
     */
    public function checkUserEligibility(User $user): array
    {
        $requirements = $this->activeRequirements;
        $results = [];
        $metCount = 0;

        foreach ($requirements as $requirement) {
            $met = $requirement->checkUserMeetsRequirement($user);
            $results[] = [
                'requirement' => $requirement,
                'met' => $met,
                'current_value' => $requirement->getUserCurrentValue($user),
                'target_value' => $requirement->target_value,
            ];

            if ($met) {
                $metCount++;
            }
        }

        return [
            'eligible' => $metCount === $requirements->count(),
            'met_count' => $metCount,
            'total_count' => $requirements->count(),
            'requirements' => $results,
        ];
    }

    /**
     * ดึงเปอร์เซ็นต์คอมมิชชันสำหรับชั้นลูกทีมที่ระบุ
     *
     * @param  int  $downlineLevel  ระดับลูกทีม (1 = ลูกตรง, 2 = ลูกของลูก, ...)
     * @return float เปอร์เซ็นต์คอมมิชชัน
     */
    public function getDownlineCommissionRate(int $downlineLevel): float
    {
        // ถ้าเกินจำนวนชั้นที่กำหนด คืนค่า 0
        if ($downlineLevel > $this->max_downline_level_bonus) {
            return 0;
        }

        // ถ้ามี unilevel_commission_levels กำหนดไว้
        if ($this->unilevel_commission_levels && isset($this->unilevel_commission_levels[$downlineLevel - 1])) {
            return (float) $this->unilevel_commission_levels[$downlineLevel - 1];
        }

        // ค่าเริ่มต้น: ลดลงตามระดับ
        $defaultRates = [10, 5, 3, 2, 1, 1, 0.5, 0.5, 0.25, 0.25];

        return $defaultRates[$downlineLevel - 1] ?? 0;
    }

    /**
     * ตรวจสอบว่าผู้ใช้มีสิทธิพิเศษที่ระบุหรือไม่
     *
     * @param  string  $privilege  รหัสสิทธิพิเศษ
     */
    public function hasPrivilege(string $privilege): bool
    {
        if (! $this->privileges) {
            return false;
        }

        return in_array($privilege, $this->privileges);
    }

    /**
     * ดึงรายการสิทธิพิเศษทั้งหมดพร้อมคำอธิบาย
     */
    public function getPrivilegesWithDescriptions(): array
    {
        $allPrivileges = self::getAvailablePrivileges();
        $result = [];

        if (! $this->privileges) {
            return $result;
        }

        foreach ($this->privileges as $privilege) {
            if (isset($allPrivileges[$privilege])) {
                $result[$privilege] = $allPrivileges[$privilege];
            }
        }

        return $result;
    }

    /**
     * รายการสิทธิพิเศษที่มีในระบบ
     */
    public static function getAvailablePrivileges(): array
    {
        return [
            'vip_support' => [
                'name' => 'VIP Support',
                'name_th' => 'บริการ VIP พิเศษ',
                'description' => 'Priority customer support',
                'description_th' => 'บริการลูกค้าสัมพันธ์ลำดับแรก',
                'icon' => '⭐',
            ],
            'priority_withdrawal' => [
                'name' => 'Priority Withdrawal',
                'name_th' => 'ถอนเงินด่วน',
                'description' => 'Fast-track withdrawal processing',
                'description_th' => 'ถอนเงินรวดเร็วกว่าปกติ',
                'icon' => '💨',
            ],
            'exclusive_products' => [
                'name' => 'Exclusive Products',
                'name_th' => 'สินค้าเอ็กซ์คลูซีฟ',
                'description' => 'Access to exclusive products',
                'description_th' => 'เข้าถึงสินค้าพิเศษเฉพาะกลุ่ม',
                'icon' => '🎁',
            ],
            'leadership_training' => [
                'name' => 'Leadership Training',
                'name_th' => 'อบรมผู้นำ',
                'description' => 'Access to leadership training programs',
                'description_th' => 'เข้าร่วมโปรแกรมอบรมผู้นำ',
                'icon' => '📚',
            ],
            'travel_rewards' => [
                'name' => 'Travel Rewards',
                'name_th' => 'รางวัลท่องเที่ยว',
                'description' => 'Eligible for travel reward programs',
                'description_th' => 'สิทธิ์รับรางวัลท่องเที่ยว',
                'icon' => '✈️',
            ],
            'car_bonus' => [
                'name' => 'Car Bonus',
                'name_th' => 'โบนัสรถยนต์',
                'description' => 'Eligible for car bonus program',
                'description_th' => 'สิทธิ์รับโบนัสรถยนต์',
                'icon' => '🚗',
            ],
            'house_bonus' => [
                'name' => 'House Bonus',
                'name_th' => 'โบนัสบ้าน',
                'description' => 'Eligible for house bonus program',
                'description_th' => 'สิทธิ์รับโบนัสบ้าน',
                'icon' => '🏠',
            ],
            'global_bonus_pool' => [
                'name' => 'Global Bonus Pool',
                'name_th' => 'แบ่งปันกำไรทั่วโลก',
                'description' => 'Share in global profit pool',
                'description_th' => 'รับส่วนแบ่งจากกองทุนกำไรทั่วโลก',
                'icon' => '🌍',
            ],
            'personal_assistant' => [
                'name' => 'Personal Assistant',
                'name_th' => 'ผู้ช่วยส่วนตัว',
                'description' => 'Dedicated personal assistant',
                'description_th' => 'ผู้ช่วยส่วนตัวประจำ',
                'icon' => '👤',
            ],
            'unlimited_withdrawals' => [
                'name' => 'Unlimited Withdrawals',
                'name_th' => 'ถอนไม่จำกัด',
                'description' => 'No limit on withdrawal frequency',
                'description_th' => 'ถอนเงินได้ไม่จำกัดจำนวนครั้ง',
                'icon' => '♾️',
            ],
        ];
    }

    /**
     * ดึงระดับเริ่มต้น (Bronze)
     */
    public static function getDefaultRank(): ?Rank
    {
        return static::where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * ดึงระดับสูงสุด (Legend)
     */
    public static function getTopTierRank(): ?Rank
    {
        return static::where('is_top_tier', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Scope สำหรับ Top Tier
     */
    public function scopeTopTier($query)
    {
        return $query->where('is_top_tier', true);
    }

    /**
     * คำนวณคอมมิชชันรวมสำหรับยอดขายที่กำหนด
     *
     * @param  float  $saleAmount  ยอดขาย
     * @return float จำนวนคอมมิชชัน
     */
    public function calculateCommission(float $saleAmount): float
    {
        // คอมมิชชันพื้นฐานจาก commission_rate
        $baseCommission = $saleAmount * ($this->commission_rate / 100);

        // คูณด้วย bonus_multiplier
        return $baseCommission * $this->bonus_multiplier;
    }
}
