<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SMS Gateway Subscription Model
 *
 * จัดการการสมัครสมาชิก SMS Payment Gateway สำหรับร้านค้า
 * ร้านค้าต้องมี subscription ที่ active จึงจะใช้ระบบ SMS Payment Gateway ได้
 * ยกเว้นร้านพรีเมียม (PremiumStore) และ Official Shop ที่ได้ฟรี
 */
class SmsGatewaySubscription extends Model
{
    protected $fillable = [
        'store_id',
        'user_id',
        'pricing_id',
        'status',
        'plan_type',
        'price',
        'currency',
        'started_at',
        'expires_at',
        'cancelled_at',
        'auto_renew',
        'payment_transaction_id',
        'payment_status',
        'next_billing_date',
        'notes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'auto_renew' => 'boolean',
        'next_billing_date' => 'date',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pricing(): BelongsTo
    {
        return $this->belongsTo(SmsGatewayPricing::class, 'pricing_id');
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    // ========================================
    // SCOPES
    // ========================================

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('expires_at', '>', now());
    }

    public function scopeForStore($query, int $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', 'active')
            ->where('auto_renew', false)
            ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'active')
            ->where('expires_at', '<', now());
    }

    // ========================================
    // HELPERS
    // ========================================

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at && $this->expires_at->isFuture();
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial' && $this->expires_at && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isExpiringSoon(int $days = 7): bool
    {
        return $this->expires_at && $this->expires_at->isBetween(now(), now()->addDays($days));
    }

    public function getDaysUntilExpiryAttribute(): int
    {
        if (! $this->expires_at) {
            return 0;
        }

        return max(0, (int) now()->diffInDays($this->expires_at, false));
    }

    public function getFormattedPriceAttribute(): string
    {
        return '฿'.number_format($this->price, 0);
    }

    /**
     * ยกเลิก subscription (ปิด auto_renew, ยังใช้ได้จนหมดอายุ)
     */
    public function cancel(?string $reason = null): bool
    {
        $this->status = 'cancelled';
        $this->cancelled_at = now();
        $this->auto_renew = false;

        if ($reason) {
            $this->notes = $reason;
        }

        return $this->save();
    }

    /**
     * ต่ออายุ subscription
     */
    public function renew(): bool
    {
        if ($this->plan_type === 'monthly') {
            $this->expires_at = ($this->expires_at ?? now())->addMonth();
        } elseif ($this->plan_type === 'yearly') {
            $this->expires_at = ($this->expires_at ?? now())->addYear();
        }

        $this->status = 'active';
        $this->next_billing_date = $this->expires_at->toDateString();

        return $this->save();
    }

    // ========================================
    // STATIC HELPERS
    // ========================================

    /**
     * เช็คว่าร้านค้ามีสิทธิ์ใช้ SMS Gateway หรือไม่
     * ตรวจสอบ: 1) Official Shop 2) Premium Store 3) Active Subscription
     */
    public static function storeHasAccess(?int $storeId): bool
    {
        // Admin/Official Shop (store_id = null) ได้สิทธิ์เสมอ
        if ($storeId === null) {
            return true;
        }

        // เช็ค Official Shop owner
        $store = VendorStore::find($storeId);
        if (! $store) {
            return false;
        }

        $officialEmail = config('shop.official_shop.seller_email', 'official-shop@thaiprompt.com');
        $officialUser = User::where('email', $officialEmail)->first();
        if ($officialUser && $store->user_id === $officialUser->id) {
            return true;
        }

        // เช็ค Premium Store
        $isPremium = PremiumStore::where('store_id', $storeId)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();

        if ($isPremium) {
            return true;
        }

        // เช็ค VendorPackage allow_direct_payment (Enterprise)
        if ($store->allowsDirectPayment()) {
            return true;
        }

        // เช็ค Active Subscription
        return static::forStore($storeId)
            ->where(function ($q) {
                $q->where('status', 'active')->orWhere('status', 'trial');
            })
            ->where('expires_at', '>', now())
            ->exists();
    }
}
