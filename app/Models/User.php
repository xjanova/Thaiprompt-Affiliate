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
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_picture',
        'role',
        'is_super_admin',
        'affiliate_id',
        'current_rank_id',
        'rank_points',
        'rank_updated_at',
        'permissions',
        'preferred_language',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get profile picture URL or default avatar
     */
    public function getProfilePictureUrlAttribute(): string
    {
        if ($this->profile_picture && file_exists(public_path($this->profile_picture))) {
            return asset($this->profile_picture);
        }

        // Return default avatar based on first letter of name
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
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'permissions' => 'array',
            'rank_updated_at' => 'datetime',
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
     * Get the affiliate associated with the user
     */
    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    /**
     * Get the commissions earned by the user
     */
    public function commissions()
    {
        return $this->hasMany(Commission::class);
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
     * Get all notifications for the user
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
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

        // Check permissions array
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
            'manage_affiliates',
            'manage_commissions',
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
        ];
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
}
