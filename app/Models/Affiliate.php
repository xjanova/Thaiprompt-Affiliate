<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Affiliate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'parent_id',
        'referral_code',
        'level',
        'total_referrals',
        'total_earnings',
        'status',
        'rank_id',
        'rank_points',
        'monthly_sales',
        'team_sales',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_earnings' => 'decimal:2',
            'monthly_sales' => 'decimal:2',
            'team_sales' => 'decimal:2',
        ];
    }

    /**
     * Get the user that owns the affiliate
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent affiliate
     */
    public function parent()
    {
        return $this->belongsTo(Affiliate::class, 'parent_id');
    }

    /**
     * Get the child affiliates
     */
    public function children()
    {
        return $this->hasMany(Affiliate::class, 'parent_id');
    }

    /**
     * Get all commissions earned by this affiliate
     */
    public function commissions()
    {
        return $this->hasMany(Commission::class);
    }

    /**
     * Get the rank
     */
    public function rank()
    {
        return $this->belongsTo(Rank::class);
    }

    /**
     * Generate unique referral code
     */
    public static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        } while (self::where('referral_code', $code)->exists());

        return $code;
    }
}
