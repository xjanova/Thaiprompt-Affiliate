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
        'package_id',
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
        'carried_left_pv_expires_at',
        'carried_right_pv_expires_at',
        'carry_forward_days',
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
            'carried_left_pv_expires_at' => 'datetime',
            'carried_right_pv_expires_at' => 'datetime',
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
     * Get the MLM plan (commission plan - single default plan for all members)
     */
    public function plan()
    {
        return $this->belongsTo(MlmPlan::class, 'mlm_plan_id');
    }

    /**
     * Get the MLM package (membership package - Bronze, Silver, Gold, etc.)
     */
    public function package()
    {
        return $this->belongsTo(MlmPackage::class, 'package_id');
    }

    /**
     * Get sponsor (alias สำหรับ unilevel sponsor - ใช้เป็น default sponsor)
     *
     * เพิ่ม relationship นี้เพื่อแก้ Bug #1: Missing sponsor_id relationship
     * ระบบใช้ unilevel_sponsor_id เป็น sponsor หลัก
     */
    public function sponsor()
    {
        return $this->belongsTo(MlmMember::class, 'unilevel_sponsor_id');
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
     * Get sponsor_id (accessor สำหรับ unilevel_sponsor_id)
     *
     * เพิ่ม accessor นี้เพื่อแก้ Bug #1: Missing sponsor_id relationship
     * ให้ $member->sponsor_id คืนค่าเดียวกับ $member->unilevel_sponsor_id
     */
    public function getSponsorIdAttribute()
    {
        return $this->unilevel_sponsor_id;
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

    /**
     * ตรวจสอบและลบ carried PV ที่หมดอายุ
     *
     * แก้ Bug #3: Missing carry forward PV expiry logic
     * ตรวจสอบ expiry date ของ carried PV และรีเซ็ตถ้าหมดอายุ
     *
     * @return bool True ถ้ามี PV หมดอายุ
     */
    public function expireCarriedPv(): bool
    {
        $hasExpired = false;
        $now = now();

        // ตรวจสอบ left leg carried PV
        if ($this->carried_left_pv > 0 && $this->carried_left_pv_expires_at && $now->greaterThan($this->carried_left_pv_expires_at)) {
            $this->carried_left_pv = 0;
            $this->carried_left_pv_expires_at = null;
            $hasExpired = true;
        }

        // ตรวจสอบ right leg carried PV
        if ($this->carried_right_pv > 0 && $this->carried_right_pv_expires_at && $now->greaterThan($this->carried_right_pv_expires_at)) {
            $this->carried_right_pv = 0;
            $this->carried_right_pv_expires_at = null;
            $hasExpired = true;
        }

        if ($hasExpired) {
            $this->save();
        }

        return $hasExpired;
    }

    /**
     * ตั้งค่า expiry date สำหรับ carried PV
     *
     * @param string $leg 'left' หรือ 'right'
     * @param float $pvAmount จำนวน PV ที่จะ carry forward
     * @return void
     */
    public function setCarriedPvExpiry(string $leg, float $pvAmount): void
    {
        $carryForwardDays = $this->carry_forward_days ?? 30; // Default 30 วัน
        $expiryDate = now()->addDays($carryForwardDays);

        if ($leg === 'left') {
            $this->carried_left_pv = $pvAmount;
            $this->carried_left_pv_expires_at = $expiryDate;
        } elseif ($leg === 'right') {
            $this->carried_right_pv = $pvAmount;
            $this->carried_right_pv_expires_at = $expiryDate;
        }

        $this->save();
    }
}
