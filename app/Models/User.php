<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'status',
        'sponsor_id',
        'referral_code',
        'mlm_level',
        'mlm_position',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'line_user_id',
        'line_display_name',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'kyc_verified_at' => 'datetime',
        'password' => 'hashed',
        'kyc_required' => 'boolean',
    ];

    // Relationships
    public function vendor()
    {
        return $this->hasOne(Vendor::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function sponsor()
    {
        return $this->belongsTo(User::class, 'sponsor_id');
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'sponsor_id');
    }

    public function mlmNetwork()
    {
        return $this->hasOne(MlmNetwork::class);
    }

    public function downline()
    {
        return $this->hasMany(MlmGenealogy::class, 'ancestor_id');
    }

    public function upline()
    {
        return $this->hasMany(MlmGenealogy::class, 'descendant_id');
    }

    public function commissions()
    {
        return $this->hasMany(Commission::class);
    }

    public function bonuses()
    {
        return $this->hasMany(Bonus::class);
    }

    public function currentRank()
    {
        return $this->hasOne(UserRank::class)->where('is_current', true)->with('rank');
    }

    public function rankHistory()
    {
        return $this->hasMany(UserRank::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function invitations()
    {
        return $this->hasMany(Invitation::class, 'inviter_id');
    }

    public function wishlist()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function adminEmployee()
    {
        return $this->hasOne(AdminEmployee::class);
    }

    public function vendorEmployments()
    {
        return $this->hasMany(VendorEmployee::class);
    }

    public function kycVerification()
    {
        return $this->hasOne(UserKycVerification::class);
    }

    public function lineOaMessages()
    {
        return $this->hasMany(LineOaMessage::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function hotelBookings()
    {
        return $this->hasMany(HotelBooking::class);
    }

    public function hotelReviews()
    {
        return $this->hasMany(HotelReview::class);
    }

    // Helper Methods
    public function isVendor(): bool
    {
        return $this->hasRole('vendor') && $this->vendor()->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isAdminEmployee(): bool
    {
        return $this->adminEmployee()->where('employment_status', 'active')->exists();
    }

    public function isVendorEmployee(): bool
    {
        return $this->vendorEmployments()->where('employment_status', 'active')->exists();
    }

    public function generateReferralCode(): string
    {
        return strtoupper(substr($this->name, 0, 3) . rand(10000, 99999));
    }

    public function getTotalDownlineCount(): int
    {
        return $this->downline()->count();
    }

    public function getDirectReferralsCount(): int
    {
        return $this->referrals()->count();
    }

    public function isKycVerified(): bool
    {
        return $this->kyc_verified_at !== null;
    }

    public function hasLineConnected(): bool
    {
        return !empty($this->line_user_id);
    }

    public function canWithdraw(float $amount = 0): bool
    {
        // Check if KYC is required based on LINE OA config
        $lineConfig = \App\Models\LineOaConfig::getActive();

        if (!$lineConfig || !$lineConfig->require_kyc_for_withdrawal) {
            return true;
        }

        if (!$lineConfig->requiresKycForWithdrawal($amount)) {
            return true;
        }

        return $this->isKycVerified();
    }
}
