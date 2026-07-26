<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class VendorStore extends Model
{
    use SoftDeletes;

    /**
     * Platform/Admin Store Slug
     * ใช้สำหรับบริการของ Platform เอง (Tarot, Fortune, ฯลฯ)
     */
    public const PLATFORM_STORE_SLUG = 'thaiprompt-official';

    /** ชนิดร้าน — ร้านขายเองปกติ / ร้าน affiliate Lazada / ร้าน affiliate+dropship AliExpress */
    public const STORE_TYPE_VENDOR = 'vendor';

    public const STORE_TYPE_LAZADA = 'lazada_affiliate';

    public const STORE_TYPE_ALIEXPRESS = 'aliexpress_affiliate';

    /** slug ร้าน affiliate มาตรฐาน (ใช้ค้น/สร้าง) */
    public const LAZADA_STORE_SLUG = 'lazada-affiliate';

    public const ALIEXPRESS_STORE_SLUG = 'aliexpress';

    protected $fillable = [
        'user_id',
        'package_id',
        'store_name',
        'store_slug',
        'store_description',
        'store_logo',
        'store_banner',
        'banner_position_y',
        'store_domain',
        'store_email',
        'store_phone',
        'store_address',
        'store_city',
        'store_state',
        'store_postal_code',
        'store_country',
        'business_type',
        'tax_id',
        'company_name',
        'primary_color',
        'secondary_color',
        'store_theme',
        'custom_css',
        'facebook_url',
        'line_oa_id',
        'instagram_url',
        'twitter_url',
        'tiktok_url',
        'commission_rate',
        'minimum_order_amount',
        'shipping_fee',
        'free_shipping_threshold',
        'enable_cod',
        'enable_reviews',
        'auto_approve_orders',
        'total_products',
        'total_orders',
        'total_sales',
        'total_revenue',
        'rating_average',
        'rating_count',
        'is_active',
        'is_verified',
        'verified_at',
        'is_featured_home',
        'featured_home_order',
        'status',
        'store_type',
        'platform_slug',
        'suspension_reason',
        'subscription_started_at',
        'subscription_expires_at',
        'subscription_status',
        'trial_ends_at',
    ];

    protected $casts = [
        'banner_position_y' => 'integer',
        'commission_rate' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'enable_cod' => 'boolean',
        'enable_reviews' => 'boolean',
        'auto_approve_orders' => 'boolean',
        'total_products' => 'integer',
        'total_orders' => 'integer',
        'total_sales' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'rating_average' => 'decimal:2',
        'rating_count' => 'integer',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'is_featured_home' => 'boolean',
        'featured_home_order' => 'integer',
        'subscription_started_at' => 'datetime',
        'subscription_expires_at' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($store) {
            if (empty($store->store_slug)) {
                $store->store_slug = Str::slug($store->store_name);
            }
        });

        // ล้างแคช "ร้านแนะนำ" ของหน้าร้านเมื่อข้อมูลร้านเปลี่ยน
        // (StorefrontController::getFeaturedStores แคชไว้ 10 นาที — ถ้าไม่ล้าง
        //  แอดมินติ๊ก/ปลดร้านแนะนำแล้วหน้าแรกยังเป็นของเก่า)
        $forgetFeatured = function () {
            try {
                \Illuminate\Support\Facades\Cache::forget('storefront_featured_stores_v2');
            } catch (\Throwable $e) {
                // cache store อาจใช้ไม่ได้ตอน migrate/seed — ห้ามทำให้การบันทึกล้ม
            }
        };

        static::saved($forgetFeatured);
        static::deleted($forgetFeatured);
    }

    // ========================================
    // Platform Store (Admin/Official Store)
    // ========================================

    /**
     * ดึง Platform Store (ร้านค้าของ Admin/Platform)
     * ถ้ายังไม่มีจะสร้างใหม่อัตโนมัติ
     */
    public static function getPlatformStore(): self
    {
        return static::firstOrCreate(
            ['store_slug' => self::PLATFORM_STORE_SLUG],
            [
                'store_name' => config('app.name', 'Thaiprompt').' Official',
                'store_description' => 'ร้านค้าอย่างเป็นทางการของ Platform',
                'is_active' => true,
                'is_verified' => true,
                'verified_at' => now(),
                'status' => 'active',
            ]
        );
    }

    /**
     * ดึง Platform Store ID
     * ถ้ายังไม่มี Platform Store จะสร้างใหม่
     */
    public static function getPlatformStoreId(): int
    {
        return static::getPlatformStore()->id;
    }

    /**
     * ตรวจสอบว่าเป็น Platform Store หรือไม่
     */
    public function isPlatformStore(): bool
    {
        return $this->store_slug === self::PLATFORM_STORE_SLUG;
    }

    /**
     * Get the owner (user) of this store
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias for owner relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the current package
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(VendorPackage::class, 'package_id');
    }

    /**
     * Get all products in this store
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'store_id');
    }

    /**
     * Get all orders for this store
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'store_id');
    }

    /**
     * รีวิวทั้งหมดของร้านค้า (ผ่านสินค้า)
     *
     * ดึงรีวิวทั้งหมดจากสินค้าทุกชิ้นในร้านค้านี้
     */
    public function reviews(): HasManyThrough
    {
        return $this->hasManyThrough(
            ProductReview::class,  // โมเดลปลายทาง
            Product::class,        // โมเดลกลาง
            'store_id',            // Foreign key บนตาราง products
            'product_id',          // Foreign key บนตาราง product_reviews
            'id',                  // Local key บนตาราง vendor_stores
            'id'                   // Local key บนตาราง products
        );
    }

    /**
     * Get all subscriptions
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(VendorSubscription::class, 'store_id');
    }

    /**
     * Get active subscription
     */
    public function activeSubscription()
    {
        return $this->hasOne(VendorSubscription::class, 'store_id')
            ->where('status', 'active')
            ->latest();
    }

    /**
     * Get all active features
     */
    public function features(): HasMany
    {
        return $this->hasMany(VendorFeatureUsage::class, 'store_id');
    }

    /**
     * Get active features only
     */
    public function activeFeatures(): HasMany
    {
        return $this->features()->where('is_active', true);
    }

    /**
     * Get all public products
     */
    public function publicProducts(): HasMany
    {
        return $this->hasMany(VendorPublicProduct::class, 'store_id');
    }

    /**
     * Get all marketing campaigns
     */
    public function marketingCampaigns(): HasMany
    {
        return $this->hasMany(VendorMarketingCampaign::class, 'store_id');
    }

    /**
     * ผู้ติดตามร้านค้า
     */
    public function followers(): HasMany
    {
        return $this->hasMany(StoreFollower::class, 'store_id');
    }

    /**
     * Trophy ที่ร้านค้าได้รับ
     */
    public function trophyAchievements(): HasMany
    {
        return $this->hasMany(StoreTrophyAchievement::class, 'store_id');
    }

    /**
     * Premium Store record
     */
    public function premiumStore(): HasOne
    {
        return $this->hasOne(PremiumStore::class, 'store_id');
    }

    /**
     * คูปองของร้านค้า
     */
    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class, 'store_id');
    }

    /**
     * Get analytics records
     */
    public function analytics(): HasMany
    {
        return $this->hasMany(VendorAnalytics::class, 'store_id');
    }

    // ========================================
    // SMS GATEWAY RELATIONSHIPS
    // ========================================

    /**
     * SMS Gateway Subscription ล่าสุดที่ active
     */
    public function smsGatewaySubscription(): HasOne
    {
        return $this->hasOne(SmsGatewaySubscription::class, 'store_id')
            ->where(function ($q) {
                $q->where('status', 'active')->orWhere('status', 'trial');
            })
            ->where('expires_at', '>', now())
            ->latest();
    }

    /**
     * SMS Gateway Subscriptions ทั้งหมด
     */
    public function smsGatewaySubscriptions(): HasMany
    {
        return $this->hasMany(SmsGatewaySubscription::class, 'store_id');
    }

    /**
     * อุปกรณ์ SMS Checker ของร้าน
     */
    public function smsCheckerDevices(): HasMany
    {
        return $this->hasMany(SmsCheckerDevice::class, 'store_id');
    }

    /**
     * บัญชีธนาคารสำหรับ SMS Gateway
     */
    public function smsGatewayBankAccounts(): HasMany
    {
        return $this->hasMany(SmsGatewayBankAccount::class, 'store_id');
    }

    // ========================================
    // ERP RELATIONSHIPS (ระบบ HR ฟรี)
    // ========================================

    /**
     * พนักงานทั้งหมดของร้าน
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'store_id');
    }

    /**
     * พนักงานที่ยัง active
     */
    public function activeEmployees(): HasMany
    {
        return $this->employees()->where('employment_status', 'active');
    }

    /**
     * แผนกทั้งหมดของร้าน
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class, 'store_id');
    }

    /**
     * ตำแหน่งงานทั้งหมดของร้าน
     */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class, 'store_id');
    }

    /**
     * กะการทำงานของร้าน
     */
    public function workShifts(): HasMany
    {
        return $this->hasMany(WorkShift::class, 'store_id');
    }

    /**
     * Scope: Only active stores
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }

    /**
     * Scope: กรองตามชนิดร้าน (vendor / lazada_affiliate / aliexpress_affiliate)
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('store_type', $type);
    }

    /**
     * เป็นร้าน affiliate (สินค้าซื้อผ่านลิงก์นอก) หรือไม่
     */
    public function isAffiliateStore(): bool
    {
        return in_array($this->store_type, [self::STORE_TYPE_LAZADA, self::STORE_TYPE_ALIEXPRESS], true);
    }

    /**
     * Scope: Only verified stores
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Check if subscription is active
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription_status === 'active' &&
               $this->subscription_expires_at &&
               $this->subscription_expires_at->isFuture();
    }

    /**
     * Check if in trial period
     */
    public function isOnTrial(): bool
    {
        return $this->subscription_status === 'trial' &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }

    /**
     * Check if subscription is expired
     */
    public function isSubscriptionExpired(): bool
    {
        return $this->subscription_status === 'expired' ||
               ($this->subscription_expires_at && $this->subscription_expires_at->isPast());
    }

    /**
     * เช็คว่าร้านค้ามีสิทธิ์รับชำระเงินเข้าบัญชีตนเอง (Direct Payment)
     * เปิดให้เฉพาะแพคเกจที่มี allow_direct_payment = true (Enterprise)
     */
    public function allowsDirectPayment(): bool
    {
        return $this->package && $this->package->allow_direct_payment;
    }

    /**
     * ตรวจสอบว่าร้านค้ามีสิทธิ์ใช้ SMS Payment Gateway หรือไม่
     * เช็ค: 1) Official Shop 2) Premium Store 3) Active Subscription
     */
    public function hasSmsGatewayAccess(): bool
    {
        return SmsGatewaySubscription::storeHasAccess($this->id);
    }

    /**
     * Get store logo URL
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->store_logo) {
            return null;
        }

        return asset($this->store_logo);
    }

    /**
     * Get store banner URL
     */
    public function getBannerUrlAttribute(): ?string
    {
        if (! $this->store_banner) {
            return null;
        }

        return asset($this->store_banner);
    }

    /**
     * Get store URL
     */
    public function getStoreUrlAttribute(): string
    {
        if ($this->store_domain) {
            return 'https://'.$this->store_domain;
        }

        return route('vendor.stores.show', $this->store_slug);
    }

    /**
     * Check if store can add more products
     */
    public function canAddProducts(): bool
    {
        if (! $this->package) {
            return false;
        }

        return ! $this->package->isProductLimitReached($this);
    }

    /**
     * Check if store can process more orders this month
     */
    public function canProcessOrders(): bool
    {
        if (! $this->package) {
            return false;
        }

        return ! $this->package->isMonthlyOrderLimitReached($this);
    }

    /**
     * Check if store has a specific feature
     */
    public function hasFeature(string $featureSlug): bool
    {
        // Check package features first
        if ($this->package && $this->package->allowsFeature($featureSlug)) {
            return true;
        }

        // Check purchased add-on features
        return $this->activeFeatures()
            ->whereHas('feature', function ($q) use ($featureSlug) {
                $q->where('feature_slug', $featureSlug);
            })
            ->exists();
    }

    /**
     * Calculate commission for an amount
     */
    public function calculateCommission(float $amount): float
    {
        return $amount * ($this->commission_rate / 100);
    }

    /**
     * Calculate store earning after commission
     */
    public function calculateEarning(float $amount): float
    {
        return $amount - $this->calculateCommission($amount);
    }

    /**
     * เพิ่มจำนวนการเข้าชมร้านค้า
     *
     * บันทึกการเข้าชมลงในตาราง vendor_store_visits
     * เพื่อใช้ในการวิเคราะห์ข้อมูลและสถิติ
     */
    public function incrementVisitCount(): void
    {
        // บันทึกการเข้าชมผ่าน VendorStoreVisit model
        VendorStoreVisit::recordVisit($this, request());
    }

    /**
     * Increment product count
     */
    public function incrementProductCount(): void
    {
        $this->increment('total_products');
    }

    /**
     * Decrement product count
     */
    public function decrementProductCount(): void
    {
        $this->decrement('total_products');
    }

    /**
     * Update store statistics
     */
    public function updateStatistics(): void
    {
        $this->total_products = $this->products()->count();
        $this->total_orders = $this->orders()->count();
        $this->total_sales = $this->orders()->where('status', 'completed')->sum('total_amount');
        $this->total_revenue = $this->orders()->where('status', 'completed')->sum('store_earning');
        $this->save();
    }
}
