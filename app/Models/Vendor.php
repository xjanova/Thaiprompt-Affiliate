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
    ];

    protected $casts = [
        'featured' => 'boolean',
        'commission_rate' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'rating' => 'decimal:2',
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

    public function addonPurchases()
    {
        return $this->hasMany(AddonPurchase::class);
    }

    public function hotels()
    {
        return $this->hasMany(Hotel::class);
    }

    public function hotelBookings()
    {
        return $this->hasMany(HotelBooking::class);
    }

    public function vendorTheme()
    {
        return $this->hasOne(VendorTheme::class);
    }

    // Addon Management
    public function hasAddon($addonType): bool
    {
        return $this->addonPurchases()
            ->valid()
            ->whereHas('addon', function ($q) use ($addonType) {
                $q->where('type', $addonType);
            })
            ->exists();
    }

    public function hasHotelManagement(): bool
    {
        return $this->hasAddon('hotel_management');
    }

    public function hasStoreTheme(): bool
    {
        return $this->hasAddon('store_theme');
    }

    public function getActiveAddons()
    {
        return $this->addonPurchases()->valid()->with('addon')->get();
    }

    // Helper Methods
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
