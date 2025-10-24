<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'shop_name',
        'shop_slug',
        'shop_description',
        'shop_logo',
        'shop_banner',
        'shop_email',
        'shop_phone',
        'shop_address',
        'tax_id',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'commission_rate',
        'status',
        'featured',
        'total_sales',
        'total_revenue',
        'rating',
        'total_reviews',
        'is_verified',
        'verification_badge',
        'verified_at',
    ];

    protected $casts = [
        'featured' => 'boolean',
        'is_verified' => 'boolean',
        'commission_rate' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'rating' => 'decimal:2',
        'verified_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasManyThrough(Order::class, Product::class);
    }

    public function posSessions()
    {
        return $this->hasMany(PosSession::class);
    }

    public function posSales()
    {
        return $this->hasMany(PosSale::class);
    }

    public function coupons()
    {
        return $this->hasMany(Coupon::class);
    }

    public function verifications()
    {
        return $this->hasMany(ShopVerification::class);
    }

    public function currentVerification()
    {
        return $this->hasOne(ShopVerification::class)->latest();
    }

    public function employees()
    {
        return $this->hasMany(VendorEmployee::class);
    }

    public function activeEmployees()
    {
        return $this->hasMany(VendorEmployee::class)->where('employment_status', 'active');
    }

    // Helper Methods
    public function isVerified(): bool
    {
        return $this->is_verified === true;
    }

    public function getVerificationBadgeIcon(): ?string
    {
        if (!$this->verification_badge) {
            return null;
        }

        $badges = [
            'bronze' => '🥉',
            'silver' => '🥈',
            'gold' => '🥇',
            'platinum' => '💎',
        ];

        return $badges[$this->verification_badge] ?? null;
    }

    public function getVerificationBadgeColor(): ?string
    {
        if (!$this->verification_badge) {
            return null;
        }

        $colors = [
            'bronze' => '#CD7F32',
            'silver' => '#C0C0C0',
            'gold' => '#FFD700',
            'platinum' => '#E5E4E2',
        ];

        return $colors[$this->verification_badge] ?? null;
    }
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function calculateCommission(float $amount): float
    {
        return $amount * ($this->commission_rate / 100);
    }
}
