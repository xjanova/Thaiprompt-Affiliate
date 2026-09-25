<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * FreshMarketSeller - ผู้ขายในตลาดสดไทยพร๊อม
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $line_user_id
 * @property string $shop_name
 * @property string|null $shop_description
 * @property string|null $shop_image
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $address
 * @property string|null $province
 * @property string|null $district
 * @property string|null $sub_district
 * @property string|null $phone
 * @property \Carbon\Carbon|null $phone_verified_at
 * @property string|null $line_display_name
 * @property string $subscription_type
 * @property \Carbon\Carbon|null $subscription_expires_at
 * @property bool $is_verified
 * @property bool $is_active
 * @property bool $is_suspended
 * @property int $total_listings
 * @property int $total_sales
 * @property float $total_revenue
 * @property float $rating_average
 * @property int $rating_count
 * @property int|null $mlm_member_id
 * @property string|null $referral_code
 */
class FreshMarketSeller extends Model
{
    use SoftDeletes;

    protected $table = 'fresh_market_sellers';

    protected $fillable = [
        'user_id',
        'line_user_id',
        'shop_name',
        'shop_description',
        'shop_image',
        'latitude',
        'longitude',
        'address',
        'province',
        'district',
        'sub_district',
        'phone',
        'phone_verified_at',
        'line_display_name',
        'subscription_type',
        'subscription_expires_at',
        'is_verified',
        'is_active',
        'is_suspended',
        'total_listings',
        'total_sales',
        'total_revenue',
        'rating_average',
        'rating_count',
        'broadcast_credits',
        'broadcast_package_type',
        'broadcast_package_expires_at',
        'mlm_member_id',
        'referral_code',
        // ร้านเคลื่อนที่ + เปิด/ปิดร้าน (แก้ผ่าน FreshMarketShopPresenceService เท่านั้น)
        'is_mobile',
        'current_latitude',
        'current_longitude',
        'location_label',
        'location_updated_at',
        'is_open',
        'opened_at',
        'closes_at',
        'live_location_sharing',
    ];

    /**
     * คอลัมน์สถานะหน้าร้าน — ใส่ในรายการ select ของ eager load ทุกครั้งที่ต้องรู้ว่าร้านเปิดอยู่ไหม
     * เช่น ->with('seller:id,shop_name,rating_average,'.implode(',', FreshMarketSeller::PRESENCE_COLUMNS))
     */
    public const PRESENCE_COLUMNS = [
        'is_mobile', 'is_open', 'opened_at', 'closes_at', 'location_label',
        'current_latitude', 'current_longitude', 'location_updated_at', 'live_location_sharing',
    ];

    /**
     * รายการคอลัมน์สำหรับ eager load ร้านในรายการสินค้า (ชื่อร้าน + คะแนน + สถานะหน้าร้าน + พิกัด)
     * ใช้: ->with('seller:'.FreshMarketSeller::SUMMARY_COLUMNS)
     */
    public const SUMMARY_COLUMNS = 'id,user_id,shop_name,shop_image,rating_average,is_active,is_suspended,is_verified,latitude,longitude,'
        .'is_mobile,is_open,opened_at,closes_at,location_label,current_latitude,current_longitude,location_updated_at,live_location_sharing';

    /** ข้อความตอนร้านปิด (เว็บ/แอปใช้ข้อความเดียวกัน) */
    public const CLOSED_MESSAGE = 'ปิดอยู่ — ติดตามร้านเพื่อรับแจ้งเตือนเมื่อเปิด';

    /** ร้านเคลื่อนที่ที่เปิดส่งตำแหน่งสด แต่ตำแหน่งเงียบเกินกี่นาที = ถือว่าปิด (คำสั่งกวาดจะปิดร้านให้) */
    public const LIVE_LOCATION_STALE_MINUTES = 30;

    protected $casts = [
        'phone_verified_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'subscription_expires_at' => 'datetime',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'is_suspended' => 'boolean',
        'total_listings' => 'integer',
        'total_sales' => 'integer',
        'total_revenue' => 'decimal:2',
        'rating_average' => 'decimal:2',
        'rating_count' => 'integer',
        'broadcast_credits' => 'integer',
        'broadcast_package_expires_at' => 'datetime',
        'is_mobile' => 'boolean',
        'current_latitude' => 'decimal:8',
        'current_longitude' => 'decimal:8',
        'location_updated_at' => 'datetime',
        'is_open' => 'boolean',
        'opened_at' => 'datetime',
        'closes_at' => 'datetime',
        'live_location_sharing' => 'boolean',
    ];

    /**
     * สร้าง referral_code อัตโนมัติ
     */
    protected static function booted(): void
    {
        static::creating(function (self $seller) {
            if (empty($seller->referral_code)) {
                $seller->referral_code = 'TSD'.strtoupper(Str::random(7));
            }
        });
    }

    // ===== Relationships =====

    /**
     * เจ้าของร้าน (User)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * สมาชิก MLM
     */
    public function mlmMember(): BelongsTo
    {
        return $this->belongsTo(MlmMember::class, 'mlm_member_id');
    }

    /**
     * สินค้าทั้งหมดของร้าน
     */
    public function listings(): HasMany
    {
        return $this->hasMany(FreshMarketListing::class, 'seller_id');
    }

    /**
     * ออเดอร์ทั้งหมดของร้าน
     */
    public function orders(): HasMany
    {
        return $this->hasMany(FreshMarketOrder::class, 'seller_id');
    }

    /**
     * Referrals ที่แนะนำ
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(FreshMarketReferral::class, 'referrer_seller_id');
    }

    /**
     * ผู้ซื้อที่ติดตามร้านนี้
     */
    public function followers(): HasMany
    {
        return $this->hasMany(FreshMarketShopFollower::class, 'seller_id');
    }

    // ===== Scopes =====

    /**
     * Scope: เฉพาะร้านที่เปิดใช้งาน
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_suspended', false);
    }

    /**
     * Scope: ค้นหาร้านใกล้พิกัด (Haversine)
     */
    public function scopeNearby($query, float $lat, float $lng, float $radiusKm = 10)
    {
        return $query->selectRaw('*, (
            6371 * acos(
                cos(radians(?)) * cos(radians(latitude))
                * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude))
            )
        ) AS distance_km', [$lat, $lng, $lat])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance_km', '<=', $radiusKm)
            ->orderBy('distance_km');
    }

    // ===== Methods =====

    /**
     * ตรวจสอบสมาชิกยังไม่หมดอายุ
     */
    public function hasActiveSubscription(): bool
    {
        if ($this->subscription_type === 'free') {
            return true;
        }

        return $this->subscription_expires_at && $this->subscription_expires_at->isFuture();
    }

    /**
     * เป็นสมาชิกแบบเสียเงิน (หรือช่วงทดลองใช้) ที่ยังไม่หมดอายุหรือไม่
     */
    public function hasPaidSubscription(): bool
    {
        return $this->subscription_type !== 'free'
            && $this->subscription_expires_at
            && $this->subscription_expires_at->isFuture();
    }

    /**
     * ตรวจสอบว่าลงขายได้อีกหรือไม่
     */
    public function canCreateListing(): bool
    {
        if (! $this->is_active || $this->is_suspended) {
            return false;
        }

        $settings = FreshMarketSetting::getSettings();

        // เก็บเฉพาะ GP (ไม่มีแพ็กเกจสมาชิก) → ไม่จำกัดจำนวนลงขาย
        if ($settings->fee_mode === 'percentage') {
            return true;
        }

        // สมาชิกรายเดือน/ช่วงทดลอง (ยังไม่หมดอายุ) → โควต้าสมาชิก (0 = ไม่จำกัด)
        if ($this->hasPaidSubscription()) {
            $max = (int) $settings->max_listings_subscribed;

            return $max === 0 || $this->total_listings < $max;
        }

        return $this->total_listings < (int) $settings->max_listings_free;
    }

    /**
     * สถานะร้านแบบคีย์เดียว (ใช้แสดงป้าย/ปุ่มในหน้าแอดมิน)
     *
     * @return string suspended|inactive|unverified|active
     */
    public function getStatusKeyAttribute(): string
    {
        if ($this->is_suspended) {
            return 'suspended';
        }

        if (! $this->is_active) {
            return 'inactive';
        }

        if (! $this->is_verified) {
            return 'unverified';
        }

        return 'active';
    }

    /**
     * ชื่อเดิมที่หน้าแอดมินเก่าเรียก ($seller->status) → คีย์สถานะ (อ่านอย่างเดียว ไม่มีคอลัมน์จริง)
     */
    public function getStatusAttribute(): string
    {
        return $this->status_key;
    }

    /**
     * ชื่อเดิมที่หน้าแอดมินเก่าเรียก ($seller->rating) → คะแนนเฉลี่ย
     */
    public function getRatingAttribute(): float
    {
        return (float) $this->rating_average;
    }

    /**
     * ป้ายสถานะร้านภาษาไทย
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status_key) {
            'suspended' => 'ถูกระงับ',
            'inactive' => 'ปิดร้าน',
            'unverified' => 'รอยืนยัน',
            default => 'เปิดขาย',
        };
    }

    /**
     * ผู้ซื้อมองเห็นร้านนี้หรือไม่
     */
    public function isVisibleToBuyers(): bool
    {
        if (! $this->is_active || $this->is_suspended) {
            return false;
        }

        return FreshMarketListing::autoApproveSellers() || (bool) $this->is_verified;
    }

    /**
     * ร้านมีพิกัดสำหรับเรียกไรเดอร์หรือไม่
     */
    public function hasPickupLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null
            && (float) $this->latitude != 0.0 && (float) $this->longitude != 0.0;
    }

    // ===== ร้านเคลื่อนที่ (รถเข็น/ตลาดนัด) + เปิด/ปิดร้าน =====

    /**
     * ค่าคอลัมน์ดิบ (null = ไม่ได้โหลดคอลัมน์นี้มา เช่น eager load แบบเลือกคอลัมน์)
     */
    protected function rawAttribute(string $key): mixed
    {
        $attributes = $this->getAttributes();

        return array_key_exists($key, $attributes) ? $attributes[$key] : null;
    }

    /**
     * เป็นร้านเคลื่อนที่หรือไม่ (ตำแหน่งร้าน = จุดที่เปิดร้านวันนี้)
     */
    public function isMobileShop(): bool
    {
        return (bool) $this->rawAttribute('is_mobile');
    }

    /**
     * มีตำแหน่งปัจจุบัน (ร้านเคลื่อนที่) ที่ใช้ได้หรือไม่
     */
    public function hasCurrentLocation(): bool
    {
        $lat = $this->rawAttribute('current_latitude');
        $lng = $this->rawAttribute('current_longitude');

        return $lat !== null && $lng !== null
            && \App\Services\DeliveryFeeCalculator::isValidCoordinate($lat, $lng);
    }

    /**
     * เปิดส่งตำแหน่งสดไว้ แต่ตำแหน่งเงียบเกิน 30 นาที (ร้านน่าจะปิดไปแล้วแต่ลืมกดปิด)
     */
    public function isLiveLocationStale(): bool
    {
        if (! $this->rawAttribute('live_location_sharing')) {
            return false;
        }

        return ! $this->location_updated_at
            || $this->location_updated_at->lt(now()->subMinutes(self::LIVE_LOCATION_STALE_MINUTES));
    }

    /**
     * ร้านเปิดรับออเดอร์อยู่ตอนนี้หรือไม่
     *
     * - is_open = false → ปิด
     * - เลยเวลาปิดที่ตั้งไว้ (closes_at) → ปิด (คำสั่งกวาดจะตั้ง is_open = false ให้ภายหลัง)
     * - ร้านเคลื่อนที่: ต้องมีตำแหน่งปัจจุบัน และถ้าเปิดตำแหน่งสดไว้ ตำแหน่งต้องไม่เงียบเกิน 30 นาที
     * - ไม่ได้โหลดคอลัมน์ is_open มา (eager load แบบเลือกคอลัมน์ / แถวที่เพิ่งสร้าง) → ถือว่าเปิดตามพฤติกรรมเดิม
     */
    public function isOpenNow(): bool
    {
        if (! array_key_exists('is_open', $this->getAttributes())) {
            return true;
        }

        if (! $this->is_open) {
            return false;
        }

        if ($this->closes_at && $this->closes_at->lte(now())) {
            return false;
        }

        if ($this->isMobileShop()) {
            return $this->hasCurrentLocation() && ! $this->isLiveLocationStale();
        }

        return true;
    }

    /**
     * รับออเดอร์ได้ตอนนี้ (ผู้ซื้อเห็นร้าน + ร้านเปิดอยู่)
     */
    public function acceptsOrders(): bool
    {
        return $this->isVisibleToBuyers() && $this->isOpenNow();
    }

    /**
     * ตำแหน่งร้านที่เปิดเผยต่อผู้ซื้อได้ (เฉพาะตอนร้านเปิดเท่านั้น)
     *
     * ร้านเคลื่อนที่ = ตำแหน่งที่เปิดร้านวันนี้ / ตำแหน่งสด · ร้านประจำ = ที่อยู่ร้านที่ลงทะเบียน
     * ร้านปิด → null (ไม่เปิดเผยตำแหน่งล่าสุดของร้านเคลื่อนที่)
     *
     * @return array{latitude: float, longitude: float, label: ?string, updated_at: ?string, is_live: bool, source: string}|null
     */
    public function publicLocation(): ?array
    {
        if (! $this->isOpenNow()) {
            return null;
        }

        if ($this->isMobileShop()) {
            $live = (bool) $this->rawAttribute('live_location_sharing');

            return [
                'latitude' => (float) $this->current_latitude,
                'longitude' => (float) $this->current_longitude,
                'label' => $this->location_label,
                'updated_at' => $this->location_updated_at?->toIso8601String(),
                'is_live' => $live,
                'source' => $live ? 'live' : 'pinned',
            ];
        }

        if (! $this->hasPickupLocation()) {
            return null;
        }

        return [
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'label' => $this->address,
            'updated_at' => null,
            'is_live' => false,
            'source' => 'fixed',
        ];
    }

    /**
     * จุดรับของ (ไรเดอร์ + คิดค่าส่ง): ร้านเคลื่อนที่ = ตำแหน่งปัจจุบัน, ร้านประจำ = ที่อยู่ร้าน
     *
     * ใช้ตำแหน่งปัจจุบันแม้ร้านเพิ่งกดปิด (ออเดอร์ที่รับไว้ก่อนปิดยังต้องให้ไรเดอร์ไปรับที่จุดล่าสุด)
     *
     * @return array{latitude: float, longitude: float, address: string, is_mobile: bool}|null
     */
    public function pickupPoint(): ?array
    {
        if ($this->isMobileShop() && $this->hasCurrentLocation()) {
            $label = trim((string) $this->location_label);

            return [
                'latitude' => (float) $this->current_latitude,
                'longitude' => (float) $this->current_longitude,
                'address' => $label !== '' ? $label.' (ร้านเคลื่อนที่)' : ($this->address ?: (string) $this->shop_name),
                'is_mobile' => true,
            ];
        }

        if ($this->hasPickupLocation()) {
            return [
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude,
                'address' => $this->address ?: (string) $this->shop_name,
                'is_mobile' => false,
            ];
        }

        return null;
    }

    /**
     * สถานะหน้าร้าน (เปิด/ปิด + ตำแหน่ง) สำหรับ API และหน้าเว็บ
     *
     * @param  bool  $forOwner  เจ้าของร้านเห็นตำแหน่ง/ชื่อจุดล่าสุดแม้ร้านปิด
     * @return array<string, mixed>
     */
    public function presencePayload(bool $forOwner = false): array
    {
        $open = $this->isOpenNow();
        $mobile = $this->isMobileShop();
        $visibilityLoaded = array_key_exists('is_active', $this->getAttributes());

        $location = $this->publicLocation();

        if (! $location && $forOwner && $mobile && $this->hasCurrentLocation()) {
            $location = [
                'latitude' => (float) $this->current_latitude,
                'longitude' => (float) $this->current_longitude,
                'label' => $this->location_label,
                'updated_at' => $this->location_updated_at?->toIso8601String(),
                'is_live' => false,
                'source' => 'last_known',
            ];
        }

        return [
            'is_mobile' => $mobile,
            'is_open' => $open,
            'status' => $open ? 'open' : 'closed',
            'status_text' => $open ? 'เปิดอยู่' : 'ปิดอยู่',
            'closed_message' => $open ? null : self::CLOSED_MESSAGE,
            'can_order' => $open && (! $visibilityLoaded || $this->isVisibleToBuyers()),
            'opened_at' => $open ? $this->opened_at?->toIso8601String() : null,
            'closes_at' => $open ? $this->closes_at?->toIso8601String() : null,
            'location_label' => ($open || $forOwner) ? $this->location_label : null,
            'live_location_sharing' => $open && (bool) $this->rawAttribute('live_location_sharing'),
            'location_updated_at' => ($mobile && ($open || $forOwner)) ? $this->location_updated_at?->toIso8601String() : null,
            'location' => $location,
        ];
    }

    /**
     * ยอดค่า GP ค้างชำระของร้าน (จากออเดอร์เก็บเงินปลายทางที่หักจาก wallet ไม่ได้)
     */
    public function outstandingGpDebt(): float
    {
        if (! $this->user_id) {
            return 0.0;
        }

        return round((float) WalletDebt::active()
            ->forUser((int) $this->user_id)
            ->where('source_type', \App\Services\FreshMarketService::DEBT_SOURCE_GP)
            ->sum('remaining_amount'), 2);
    }

    /**
     * อัพเดทสถิติ
     */
    public function refreshStats(): void
    {
        $this->update([
            // นับสินค้าที่ยังไม่ถูกลบ (ขายอยู่ + ของหมดชั่วคราว) — ใช้คุมโควต้าลงขาย
            'total_listings' => $this->listings()->whereIn('status', ['active', 'sold_out', 'draft'])->count(),
            'total_sales' => $this->orders()->where('order_status', 'completed')->count(),
            'total_revenue' => $this->orders()->where('order_status', 'completed')->sum('seller_earning'),
        ]);
    }

    /**
     * อัพเดทคะแนนรีวิวเฉลี่ยจาก orders ที่มี buyer_rating
     */
    public function updateRating(): void
    {
        $stats = $this->orders()
            ->whereNotNull('buyer_rating')
            ->selectRaw('AVG(buyer_rating) as avg_rating, COUNT(*) as total')
            ->first();

        $this->update([
            'rating_average' => round($stats->avg_rating ?? 0, 2),
            'rating_count' => $stats->total ?? 0,
        ]);
    }

    /**
     * หาผู้ขายจาก LINE User ID
     */
    public static function findByLineUserId(string $lineUserId): ?self
    {
        return self::where('line_user_id', $lineUserId)->first();
    }

    // ===== Broadcast Methods =====

    /**
     * ตรวจสอบว่ามี broadcast credits คงเหลือ
     */
    public function hasBroadcastCredits(): bool
    {
        if ($this->broadcast_package_type === 'unlimited'
            && $this->broadcast_package_expires_at?->isFuture()) {
            return true;
        }

        return $this->broadcast_credits > 0;
    }

    /**
     * ใช้ broadcast credit 1 ครั้ง (atomic เพื่อป้องกัน race condition)
     */
    public function useBroadcastCredit(): bool
    {
        // unlimited ไม่ต้องหักเครดิต
        if ($this->broadcast_package_type === 'unlimited'
            && $this->broadcast_package_expires_at?->isFuture()) {
            return true;
        }

        // atomic decrement: หักเฉพาะเมื่อยังมีเครดิตเหลือ
        $updated = static::where('id', $this->id)
            ->where('broadcast_credits', '>', 0)
            ->update(['broadcast_credits' => \Illuminate\Support\Facades\DB::raw('broadcast_credits - 1')]);

        if ($updated > 0) {
            $this->refresh();

            return true;
        }

        return false;
    }
}
