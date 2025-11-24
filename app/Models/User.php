<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * ฟิลด์ที่อนุญาตให้ mass assignment (ปลอดภัย)
     *
     * ⚠️ หมายเหตุ: ลบ sensitive fields ออกแล้วเพื่อป้องกัน privilege escalation
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'member_number',
        'profile_picture',
        // ✅ ลบ 'role', 'role_id', 'is_super_admin', 'is_hotel_admin' ออกแล้ว
        'managed_hotel_id',
        // ✅ ลบ 'blocked_at' ออกแล้ว
        'current_rank_id',
        'rank_points',
        'rank_updated_at',
        // ✅ ลบ 'permissions' ออกแล้ว
        'preferred_language',
        'menu_theme_preference',
        'game_preferences',
        // LINE OA fields
        'line_user_id',
        'line_display_name',
        'line_picture_url',
        'line_access_token',
        'line_linked_at',
        'line_verified',
        // Contact fields
        'phone',
        'phone_verified',
        'phone_verified_at',
        // Address fields
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        // Shipping address fields
        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'shipping_phone',
        // Additional profile
        'date_of_birth',
        'gender',
        // ✅ ลบ 'kyc_status', 'kyc_verified_at' ออกแล้ว
        // Thai ID Card fields
        'id_card_number',
        'thai_first_name',
        'thai_last_name',
        'english_first_name',
        'english_last_name',
        'id_card_birth_date',
        'id_card_religion',
        'id_card_address',
        'id_card_issue_date',
        'id_card_expiry_date',
    ];

    /**
     * ฟิลด์ที่ป้องกันไม่ให้ mass assignment (Sensitive Fields)
     *
     * ⚠️ SECURITY: ฟิลด์เหล่านี้ต้องแก้ไขผ่านการตรวจสอบสิทธิ์เท่านั้น
     *
     * @var array<int, string>
     */
    protected $guarded = [
        'role',              // ป้องกัน role manipulation
        'role_id',           // ป้องกัน role manipulation
        'is_super_admin',    // ป้องกัน privilege escalation
        'is_hotel_admin',    // ป้องกัน privilege escalation
        'blocked_at',        // เฉพาะ admin เท่านั้น
        'permissions',       // ป้องกัน permission bypass
        'kyc_status',        // เฉพาะ admin/system เท่านั้น
        'kyc_verified_at',   // เฉพาะ admin/system เท่านั้น
    ];

    /**
     * ฟิลด์ที่ซ่อนไม่ให้แสดงใน API response (Sensitive Data)
     *
     * ⚠️ SECURITY: เพิ่มฟิลด์ sensitive เพื่อป้องกัน data exposure
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'line_access_token',
        // ✅ เพิ่ม sensitive fields
        'permissions',           // ข้อมูลสิทธิ์ภายใน
        'id_card_number',        // เลขบัตรประชาชน (PII)
        'kyc_verified_at',       // ข้อมูล KYC ภายใน
        'blocked_at',            // ข้อมูล moderation ภายใน
    ];

    /**
     * Get profile picture URL or default avatar
     * Priority: LINE picture > Uploaded picture > Default avatar
     */
    public function getProfilePictureUrlAttribute(): string
    {
        // Priority 1: Use LINE profile picture if available
        if ($this->line_picture_url) {
            return $this->line_picture_url;
        }

        // Priority 2: Use uploaded profile picture with cache busting
        if ($this->profile_picture) {
            $path = $this->profile_picture;

            // Check if file exists
            if (file_exists(public_path($path))) {
                // Add cache busting using file modification time
                $timestamp = filemtime(public_path($path));
                return asset($path) . '?v=' . $timestamp;
            }

            // If path doesn't work, try with storage prefix
            $storagePath = 'storage/' . $path;
            if (file_exists(public_path($storagePath))) {
                $timestamp = filemtime(public_path($storagePath));
                return asset($storagePath) . '?v=' . $timestamp;
            }
        }

        // Priority 3: Return default avatar based on first letter of name
        $initial = strtoupper(substr($this->name, 0, 1));
        return "https://ui-avatars.com/api/?name={$initial}&background=random&color=fff&size=200";
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'blocked_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'is_hotel_admin' => 'boolean',
            'permissions' => 'array',
            'game_preferences' => 'array',
            'rank_updated_at' => 'datetime',
            'line_linked_at' => 'datetime',
            'line_verified' => 'boolean',
            'phone_verified' => 'boolean',
            'phone_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'kyc_verified_at' => 'datetime',
            'id_card_birth_date' => 'date',
            'id_card_issue_date' => 'date',
            'id_card_expiry_date' => 'date',
        ];
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin === true;
    }

    /**
     * Get the role associated with the user
     */
    public function roleModel()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Get the managed hotel (for hotel admins)
     */
    public function managedHotel()
    {
        return $this->belongsTo(Hotel::class, 'managed_hotel_id');
    }

    /**
     * Check if user is hotel admin
     */
    public function isHotelAdmin(): bool
    {
        return $this->is_hotel_admin === true && $this->managed_hotel_id !== null;
    }

    /**
     * Get the wallet associated with the user
     */
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Get all wallet transactions for the user
     */
    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Get all withdrawal requests for the user
     */
    public function withdrawalRequests()
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    /**
     * Get all payment methods for the user
     */
    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    /**
     * Get default payment method
     */
    public function defaultPaymentMethod()
    {
        return $this->hasOne(PaymentMethod::class)->where('is_default', true);
    }

    /**
     * Cryptocurrency Wallet Relationships
     */

    /**
     * Get all crypto wallets for the user
     */
    public function cryptoWallets()
    {
        return $this->hasMany(CryptoWallet::class);
    }

    /**
     * Get default crypto wallet
     */
    public function defaultCryptoWallet()
    {
        return $this->hasOne(CryptoWallet::class)->where('is_default', true);
    }

    /**
     * Get all crypto addresses across all wallets
     */
    public function cryptoAddresses()
    {
        return $this->hasManyThrough(CryptoAddress::class, CryptoWallet::class);
    }

    /**
     * Get all crypto transactions
     */
    public function cryptoTransactions()
    {
        return $this->hasMany(CryptoTransaction::class);
    }

    /**
     * Get all crypto exchange transactions
     */
    public function cryptoExchangeTransactions()
    {
        return $this->hasMany(CryptoExchangeTransaction::class);
    }

    /**
     * Get all crypto withdrawal requests
     */
    public function cryptoWithdrawalRequests()
    {
        return $this->hasMany(CryptoWithdrawalRequest::class);
    }

    /**
     * Get all crypto deposit addresses
     */
    public function cryptoDepositAddresses()
    {
        return $this->hasMany(CryptoDepositAddress::class);
    }

    /**
     * TPIX Token System Relationships
     */

    /**
     * Get all tokens created by the user
     */
    public function createdTokens()
    {
        return $this->hasMany(TPIXToken::class, 'creator_id');
    }

    /**
     * Get all token balances for the user
     */
    public function tokenBalances()
    {
        return $this->hasMany(TPIXTokenBalance::class);
    }

    /**
     * Get all token transfers (sent and received)
     */
    public function tokenTransfers()
    {
        return $this->hasMany(TPIXTokenTransfer::class, 'user_id');
    }

    /**
     * Get all stakes made by the user
     */
    public function stakes()
    {
        return $this->hasMany(TPIXStake::class);
    }

    /**
     * Get all referral codes used by the user
     */
    public function referralUses()
    {
        return $this->hasMany(TPIXReferralUse::class);
    }

    /**
     * Get all referral rewards earned
     */
    public function referralRewards()
    {
        return $this->hasMany(TPIXReferralReward::class);
    }

    /**
     * TPIX DEX Relationships
     */

    /**
     * Get all swaps made by the user
     */
    public function swaps()
    {
        return $this->hasMany(TPIXSwap::class);
    }

    /**
     * Get all liquidity positions of the user
     */
    public function liquidityPositions()
    {
        return $this->hasMany(TPIXLiquidityPosition::class);
    }

    /**
     * Get active liquidity positions only
     */
    public function activeLiquidityPositions()
    {
        return $this->hasMany(TPIXLiquidityPosition::class)->where('status', 'active');
    }

    /**
     * Get all notifications for the user
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get two-factor authentication settings for the user
     */
    public function twoFactorSettings()
    {
        return $this->hasOne(TwoFactorUserSetting::class);
    }

    /**
     * Get membership retention status
     */
    public function retentionStatus()
    {
        return $this->hasOne(MembershipRetentionStatus::class);
    }

    /**
     * Get membership retention history
     */
    public function retentionHistory()
    {
        return $this->hasMany(MembershipRetentionHistory::class);
    }

    /**
     * Get membership retention transactions
     */
    public function retentionTransactions()
    {
        return $this->hasMany(MembershipRetentionTransaction::class);
    }

    /**
     * Get KYC verification records
     */
    public function kycVerifications()
    {
        return $this->hasMany(KycVerification::class);
    }

    /**
     * Get the latest KYC verification
     */
    public function latestKycVerification()
    {
        return $this->hasOne(KycVerification::class)->latestOfMany();
    }

    /**
     * Accounting System Relationships
     */

    /**
     * Get accounting settings for the user
     */
    public function accountingSettings()
    {
        return $this->hasOne(\App\Models\AccountingSetting::class);
    }

    /**
     * Get accounting companies for the user
     */
    public function accountingCompanies()
    {
        return $this->hasMany(\App\Models\AccountingCompany::class);
    }

    /**
     * Get accounting contacts for the user
     */
    public function accountingContacts()
    {
        return $this->hasMany(\App\Models\AccountingContact::class);
    }

    /**
     * Get accounting products for the user
     */
    public function accountingProducts()
    {
        return $this->hasMany(\App\Models\AccountingProduct::class);
    }

    /**
     * Get accounting invoices for the user
     */
    public function accountingInvoices()
    {
        return $this->hasMany(\App\Models\AccountingInvoice::class);
    }

    /**
     * Get accounting expenses for the user
     */
    public function accountingExpenses()
    {
        return $this->hasMany(\App\Models\AccountingExpense::class);
    }

    /**
     * Get FlowAccount connection for the user
     */
    public function flowAccountConnection()
    {
        return $this->hasOne(\App\Models\AccountingFlowaccountConnection::class);
    }

    /**
     * Check if user has KYC verified
     */
    public function isKycVerified(): bool
    {
        return $this->kyc_status === 'approved';
    }

    /**
     * Check if user has KYC pending
     */
    public function isKycPending(): bool
    {
        return $this->kyc_status === 'pending';
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadNotificationsCountAttribute(): int
    {
        return $this->notifications()->unread()->notExpired()->count();
    }

    /**
     * Get the current rank
     */
    public function currentRank()
    {
        return $this->belongsTo(Rank::class, 'current_rank_id');
    }

    /**
     * Get all rank promotions
     */
    public function rankPromotions()
    {
        return $this->hasMany(RankPromotion::class);
    }

    /**
     * Get active rank promotions
     */
    public function activeRankPromotions()
    {
        return $this->rankPromotions()->where('status', 'active');
    }

    /**
     * Get rank progress records
     */
    public function rankProgress()
    {
        return $this->hasMany(UserRankProgress::class);
    }

    /**
     * Get progress for next rank
     */
    public function getNextRankProgressAttribute()
    {
        if (!$this->currentRank) {
            // Get default rank
            $defaultRank = Rank::where('is_default', true)->first();
            if ($defaultRank) {
                return $this->rankProgress()->where('target_rank_id', $defaultRank->id)->first();
            }
            return null;
        }

        $nextRank = $this->currentRank->next_rank;
        if (!$nextRank) {
            return null;
        }

        return $this->rankProgress()->where('target_rank_id', $nextRank->id)->first();
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        // Super admin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check role-based permissions (new system)
        if ($this->roleModel && $this->roleModel->hasPermission($permission)) {
            return true;
        }

        // Fallback to old permissions array for backward compatibility
        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions);
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Grant permission to user
     */
    public function grantPermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            $this->permissions = $permissions;
            $this->save();
        }
    }

    /**
     * Revoke permission from user
     */
    public function revokePermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        $permissions = array_filter($permissions, fn($p) => $p !== $permission);
        $this->permissions = array_values($permissions);
        $this->save();
    }

    /**
     * Get all available permissions
     */
    public static function availablePermissions(): array
    {
        return [
            'view_dashboard',
            'manage_users',
            'view_reports',
            'manage_settings',
            'manage_branding',
            'manage_permissions',
            'manage_wallets',
            'approve_withdrawals',
            'view_all_wallets',
            'manage_wallet_settings',
            'manage_ranks',
            'approve_rank_promotions',
            'view_rank_analytics',
            'view_kyc_verifications',
            'approve_kyc',
            'manage_kyc',
            'manage_crypto_wallets',
            'approve_crypto_withdrawals',
            'view_all_crypto_wallets',
            'manage_crypto_settings',
            'manage_crypto_currencies',
        ];
    }

    /**
     * Get all permissions for this user (from role)
     */
    public function getAllPermissions(): array
    {
        if ($this->isSuperAdmin()) {
            return static::availablePermissions();
        }

        if ($this->roleModel) {
            return $this->roleModel->getPermissionNames();
        }

        // Fallback to old permissions array
        return $this->permissions ?? [];
    }

    /**
     * Get the display name of the user's role
     */
    public function getRoleDisplayName(): string
    {
        if ($this->roleModel) {
            return $this->roleModel->display_name;
        }

        // Fallback to old role field
        return match($this->role) {
            'user' => 'ผู้ใช้ทั่วไป',
            'seller' => 'ผู้ขาย',
            'admin' => 'ผู้ดูแลระบบ',
            'super_admin' => 'ผู้ดูแลระบบสูงสุด',
            default => $this->role,
        };
    }

    /**
     * Check if user has a specific role or one of multiple roles
     *
     * @param string|array $roles Role name(s) to check
     * @return bool
     */
    public function hasRole($roles): bool
    {
        // Super admin has all roles
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Convert single role to array for consistent handling
        $roles = is_array($roles) ? $roles : [$roles];

        // Check against roleModel name (new system)
        if ($this->roleModel) {
            return in_array(strtolower($this->roleModel->name), array_map('strtolower', $roles));
        }

        // Fallback to old role field
        return in_array(strtolower($this->role), array_map('strtolower', $roles));
    }

    /**
     * Add rank points
     */
    public function addRankPoints(int $points): void
    {
        $this->rank_points += $points;
        $this->save();
    }

    /**
     * Check if eligible for next rank
     */
    public function checkRankEligibility(): ?array
    {
        if (!$this->currentRank) {
            return null;
        }

        $nextRank = $this->currentRank->next_rank;
        if (!$nextRank) {
            return null;
        }

        return $nextRank->checkUserEligibility($this);
    }

    /**
     * Get all AI bot profiles owned by this user
     */
    public function ownedBots()
    {
        return $this->hasMany(\App\Models\AiBotProfile::class, 'owner_id');
    }

    /**
     * MLM Relationships
     */

    /**
     * Get all MLM memberships for this user
     */
    public function mlmMembers()
    {
        return $this->hasMany(\App\Models\MlmMember::class);
    }

    /**
     * Get all MLM commissions for this user
     */
    public function mlmCommissions()
    {
        return $this->hasMany(\App\Models\MlmCommission::class);
    }

    /**
     * Get all general commissions for this user (from commissions table)
     *
     * คอมมิชชั่นทั่วไปจากตาราง commissions
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function commissions()
    {
        return $this->hasMany(\App\Models\Commission::class);
    }

    /**
     * Get active MLM membership for a specific plan
     */
    public function getMlmMember($planId)
    {
        return $this->mlmMembers()
            ->where('mlm_plan_id', $planId)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Hotel Booking Relationships
     */

    /**
     * Get user's hotel bookings
     */
    public function hotelBookings()
    {
        return $this->hasMany(\App\Models\HotelBooking::class);
    }

    /**
     * Get user's hotel reviews
     */
    public function hotelReviews()
    {
        return $this->hasMany(\App\Models\HotelReview::class);
    }

    /**
     * Get hotels owned by this user (if hotel owner)
     */
    public function ownedHotels()
    {
        return $this->hasMany(\App\Models\Hotel::class, 'owner_id');
    }

    /**
     * Get upcoming hotel bookings
     */
    public function upcomingHotelBookings()
    {
        return $this->hotelBookings()
            ->whereIn('status', ['confirmed', 'pending'])
            ->where('check_in_date', '>=', now())
            ->orderBy('check_in_date', 'asc');
    }

    /**
     * Get past hotel bookings
     */
    public function pastHotelBookings()
    {
        return $this->hotelBookings()
            ->whereIn('status', ['completed', 'checked_out'])
            ->where('check_out_date', '<', now())
            ->orderBy('check_out_date', 'desc');
    }

    /**
     * Video Reward System Relationships
     */

    /**
     * Get user's video level
     */
    public function videoLevel()
    {
        return $this->hasOne(\App\Models\UserVideoLevel::class);
    }

    /**
     * Get user's video coin balance
     */
    public function videoCoin()
    {
        return $this->hasOne(\App\Models\VideoCoin::class);
    }

    /**
     * Get user's video watches
     */
    public function videoWatches()
    {
        return $this->hasMany(\App\Models\UserVideoWatch::class);
    }

    /**
     * Get user's watch sessions
     */
    public function videoWatchSessions()
    {
        return $this->hasMany(\App\Models\VideoWatchSession::class);
    }

    /**
     * Get user's quest progress
     */
    public function questProgress()
    {
        return $this->hasMany(\App\Models\UserQuestProgress::class);
    }

    /**
     * Get user's daily streak
     */
    public function dailyStreak()
    {
        return $this->hasOne(\App\Models\UserDailyStreak::class);
    }

    /**
     * Get coin exchange requests
     */
    public function coinExchangeRequests()
    {
        return $this->hasMany(\App\Models\CoinExchangeRequest::class);
    }

    /**
     * Get users referred by this user (video system)
     */
    public function videoReferrals()
    {
        return $this->hasMany(User::class, 'video_referred_by');
    }

    /**
     * Get user who referred this user (video system)
     */
    public function videoReferrer()
    {
        return $this->belongsTo(User::class, 'video_referred_by');
    }

    /**
     * Get referral rewards given to this user
     */
    public function videoReferralRewards()
    {
        return $this->hasMany(\App\Models\VideoReferralReward::class, 'referrer_id');
    }

    /**
     * Scopes
     */

    /**
     * Scope to filter users by role
     */
    public function scopeRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope to filter super admins
     */
    public function scopeSuperAdmin($query)
    {
        return $query->where('is_super_admin', true);
    }

    /**
     * Scope to filter verified users
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Game System Relationships
     */

    /**
     * Get user's game progress
     */
    public function gameProgress()
    {
        return $this->hasMany(UserGameProgress::class);
    }

    /**
     * Get user's game skins
     */
    public function gameSkins()
    {
        return $this->belongsToMany(GameSkin::class, 'user_game_skins', 'user_id', 'skin_id')
            ->withTimestamps()
            ->withPivot(['purchase_price', 'purchased_at']);
    }

    /**
     * Get user's game achievements
     */
    public function achievements()
    {
        return $this->belongsToMany(GameAchievement::class, 'user_achievements', 'user_id', 'achievement_id')
            ->withTimestamps()
            ->withPivot('unlocked_at');
    }

    /**
     * Get user's game transactions
     */
    public function gameTransactions()
    {
        return $this->hasMany(GameTransaction::class);
    }

    /**
     * Generate a unique member number
     */
    public static function generateMemberNumber(): string
    {
        $prefix = config('member.prefix', 'MEM');
        $startingNumber = config('member.starting_number', 1);
        $padding = config('member.padding', 5);

        // Get the last member number
        $lastUser = static::whereNotNull('member_number')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastUser && $lastUser->member_number) {
            // Extract number from last member number
            $lastNumber = (int) preg_replace('/\D/', '', $lastUser->member_number);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = $startingNumber;
        }

        // Generate member number with padding
        $memberNumber = $prefix . str_pad($nextNumber, $padding, '0', STR_PAD_LEFT);

        // Ensure uniqueness
        while (static::where('member_number', $memberNumber)->exists()) {
            $nextNumber++;
            $memberNumber = $prefix . str_pad($nextNumber, $padding, '0', STR_PAD_LEFT);
        }

        return $memberNumber;
    }

    /**
     * Boot method to auto-generate member number
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (config('member.auto_generate', true) && empty($user->member_number)) {
                $user->member_number = static::generateMemberNumber();
            }

            // ⚠️ LOCK THEME TO ARROW-X ONLY
            // บังคับให้ theme เป็น arrow-x เสมอ
            $user->menu_theme_preference = 'arrow-x';
        });

        static::saving(function ($user) {
            // ⚠️ LOCK THEME TO ARROW-X ONLY
            // ป้องกันการเปลี่ยน theme เป็นอย่างอื่น
            if ($user->isDirty('menu_theme_preference') && $user->menu_theme_preference !== 'arrow-x') {
                $user->menu_theme_preference = 'arrow-x';
            }
        });
    }

    /**
     * Mutator สำหรับ menu_theme_preference
     *
     * ⚠️ LOCK THEME TO ARROW-X ONLY
     * บังคับให้ค่าเป็น 'arrow-x' เสมอ ไม่ว่าจะพยายามตั้งเป็นอะไร
     *
     * @param string $value
     * @return void
     */
    public function setMenuThemePreferenceAttribute($value): void
    {
        // ⚠️ CRITICAL: ห้ามเปลี่ยนค่านี้!
        // บังคับให้เป็น arrow-x เท่านั้น
        // ไม่ยอมรับค่าอื่นเด็ดขาด
        $this->attributes['menu_theme_preference'] = 'arrow-x';

        // Log warning ถ้ามีการพยายามเปลี่ยนเป็นค่าอื่น
        if ($value !== 'arrow-x') {
            \Log::warning('Attempted to set menu_theme_preference to non-arrow-x value', [
                'user_id' => $this->id ?? 'new',
                'attempted_value' => $value,
                'forced_value' => 'arrow-x',
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
            ]);
        }
    }
}
