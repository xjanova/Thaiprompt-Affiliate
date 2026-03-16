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
    ];

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
    ];

    /**
     * สร้าง referral_code อัตโนมัติ
     */
    protected static function booted(): void
    {
        static::creating(function (self $seller) {
            if (empty($seller->referral_code)) {
                $seller->referral_code = 'TSD' . strtoupper(Str::random(7));
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
        return $query->selectRaw("*, (
            6371 * acos(
                cos(radians(?)) * cos(radians(latitude))
                * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude))
            )
        ) AS distance_km", [$lat, $lng, $lat])
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
     * ตรวจสอบว่าลงขายได้อีกหรือไม่
     */
    public function canCreateListing(): bool
    {
        if (! $this->is_active || $this->is_suspended) {
            return false;
        }

        $settings = FreshMarketSetting::getSettings();

        if ($this->hasActiveSubscription() && $this->subscription_type !== 'free') {
            $max = $settings->max_listings_subscribed;

            return $max === 0 || $this->total_listings < $max;
        }

        return $this->total_listings < $settings->max_listings_free;
    }

    /**
     * อัพเดทสถิติ
     */
    public function refreshStats(): void
    {
        $this->update([
            'total_listings' => $this->listings()->where('status', 'active')->count(),
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
