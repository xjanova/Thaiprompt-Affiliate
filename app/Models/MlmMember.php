<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MlmMember extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'mlm_plan_id',
        'unilevel_sponsor_id',
        'unilevel_level',
        'unilevel_path',
        'binary_sponsor_id',
        'binary_parent_id',
        'binary_position',
        'binary_path',
        'total_direct_referrals',
        'total_team_members',
        'total_pv',
        'total_team_pv',
        'total_earnings',
        'left_leg_pv',
        'right_leg_pv',
        'left_leg_sales',
        'right_leg_sales',
        'left_leg_members',
        'right_leg_members',
        'carried_left_pv',
        'carried_right_pv',
        'status',
        'joined_at',
        'last_purchase_at',
        'is_qualified',
        'member_code',
        'joining_fee_paid',
    ];

    protected function casts(): array
    {
        return [
            'total_pv' => 'decimal:2',
            'total_team_pv' => 'decimal:2',
            'total_earnings' => 'decimal:2',
            'left_leg_pv' => 'decimal:2',
            'right_leg_pv' => 'decimal:2',
            'left_leg_sales' => 'decimal:2',
            'right_leg_sales' => 'decimal:2',
            'carried_left_pv' => 'decimal:2',
            'carried_right_pv' => 'decimal:2',
            'joining_fee_paid' => 'decimal:2',
            'is_qualified' => 'boolean',
            'joined_at' => 'datetime',
            'last_purchase_at' => 'datetime',
        ];
    }

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the MLM plan
     */
    public function plan()
    {
        return $this->belongsTo(MlmPlan::class, 'mlm_plan_id');
    }

    /**
     * Get unilevel sponsor
     */
    public function unilevelSponsor()
    {
        return $this->belongsTo(MlmMember::class, 'unilevel_sponsor_id');
    }

    /**
     * Get binary sponsor
     */
    public function binarySponsor()
    {
        return $this->belongsTo(MlmMember::class, 'binary_sponsor_id');
    }

    /**
     * Get binary parent
     */
    public function binaryParent()
    {
        return $this->belongsTo(MlmMember::class, 'binary_parent_id');
    }

    /**
     * Get binary left child
     */
    public function binaryLeftChild()
    {
        return $this->hasOne(MlmMember::class, 'binary_parent_id')
            ->where('binary_position', 'left');
    }

    /**
     * Get binary right child
     */
    public function binaryRightChild()
    {
        return $this->hasOne(MlmMember::class, 'binary_parent_id')
            ->where('binary_position', 'right');
    }

    /**
     * Get all direct referrals (unilevel)
     */
    public function unilevelChildren()
    {
        return $this->hasMany(MlmMember::class, 'unilevel_sponsor_id');
    }

    /**
     * Get all commissions
     */
    public function commissions()
    {
        return $this->hasMany(MlmCommission::class);
    }

    /**
     * Get PV transactions
     */
    public function pvTransactions()
    {
        return $this->hasMany(MlmPvTransaction::class);
    }

    /**
     * Get genealogy records
     */
    public function genealogy()
    {
        return $this->hasMany(MlmGenealogy::class);
    }

    /**
     * Get rank achievements
     */
    public function rankAchievements()
    {
        return $this->hasMany(MlmRankAchievement::class);
    }

    /**
     * Generate unique member code
     */
    public static function generateMemberCode(): string
    {
        do {
            $code = 'MLM' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        } while (self::where('member_code', $code)->exists());

        return $code;
    }

    /**
     * Get weaker leg PV
     */
    public function getWeakerLegPvAttribute()
    {
        return min($this->left_leg_pv, $this->right_leg_pv);
    }

    /**
     * Get stronger leg PV
     */
    public function getStrongerLegPvAttribute()
    {
        return max($this->left_leg_pv, $this->right_leg_pv);
    }

    /**
     * Scope for active members
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for qualified members
     */
    public function scopeQualified($query)
    {
        return $query->where('is_qualified', true);
    }
}
