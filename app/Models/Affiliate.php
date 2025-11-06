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
        // Binary tree fields
        'binary_parent_id',
        'binary_position',
        'binary_level',
        'binary_left_count',
        'binary_right_count',
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
     * Binary Tree Relationships
     */

    /**
     * Get the binary parent affiliate
     */
    public function binaryParent()
    {
        return $this->belongsTo(Affiliate::class, 'binary_parent_id');
    }

    /**
     * Get left child in binary tree
     */
    public function binaryLeftChild()
    {
        return $this->hasOne(Affiliate::class, 'binary_parent_id')
            ->where('binary_position', 'left');
    }

    /**
     * Get right child in binary tree
     */
    public function binaryRightChild()
    {
        return $this->hasOne(Affiliate::class, 'binary_parent_id')
            ->where('binary_position', 'right');
    }

    /**
     * Get all binary children (left and right)
     */
    public function binaryChildren()
    {
        return $this->hasMany(Affiliate::class, 'binary_parent_id');
    }

    /**
     * Get all binary descendants recursively
     */
    public function binaryDescendants()
    {
        return $this->binaryChildren()->with('binaryDescendants');
    }

    /**
     * Check if has space for new child in binary tree
     */
    public function hasBinarySpace(): bool
    {
        return $this->binaryLeftChild()->exists() === false ||
               $this->binaryRightChild()->exists() === false;
    }

    /**
     * Get available binary position (left or right)
     */
    public function getAvailableBinaryPosition(): ?string
    {
        if (!$this->binaryLeftChild()->exists()) {
            return 'left';
        }
        if (!$this->binaryRightChild()->exists()) {
            return 'right';
        }
        return null;
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
