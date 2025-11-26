<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'rank_id',
        'requirement_type',
        'name',
        'name_th',
        'description',
        'description_th',
        'target_value',
        'operator',
        'unit',
        'is_required',
        'weight',
        'is_active',
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'weight' => 'integer',
    ];

    /**
     * Get the rank that owns this requirement
     */
    public function rank(): BelongsTo
    {
        return $this->belongsTo(Rank::class);
    }

    /**
     * Get display name based on current locale
     */
    public function getDisplayNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'th' && $this->name_th ? $this->name_th : $this->name;
    }

    /**
     * Get display description based on current locale
     */
    public function getDisplayDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $locale === 'th' && $this->description_th ? $this->description_th : $this->description;
    }

    /**
     * Get user's current value for this requirement
     *
     * รองรับ requirement types:
     * - points: คะแนนสะสม
     * - referrals: จำนวนคนแนะนำโดยตรง
     * - sales: ยอดขายส่วนตัว
     * - active_referrals: จำนวนคนแนะนำที่ active
     * - team_sales: ยอดขายทีม
     * - consecutive_months: จำนวนเดือนต่อเนื่อง
     * - diamond_legs: จำนวนลูกทีมระดับเพชร (Diamond)
     * - crown_legs: จำนวนลูกทีมระดับมงกุฎ (Crown)
     * - royal_legs: จำนวนลูกทีมระดับรอยัล (Royal)
     *
     * @param User $user
     * @return float
     */
    public function getUserCurrentValue(User $user): float
    {
        $mlmMember = $user->mlmMembers()->first();
        if (!$mlmMember) {
            return 0;
        }

        // Calculate sales from MLM commissions
        $totalSales = $user->mlmCommissions()
            ->whereIn('status', ['approved', 'paid'])
            ->sum('commission_amount');

        return match($this->requirement_type) {
            'points' => $user->rank_points ?? 0,
            'referrals' => $mlmMember->total_direct_referrals ?? 0,
            'sales' => $totalSales,
            'active_referrals' => $this->getActiveReferralsCount($mlmMember),
            'team_sales' => $mlmMember->total_team_pv ?? 0,
            'consecutive_months' => $this->getConsecutiveMonths($mlmMember),
            'diamond_legs' => $this->getLegsCountByRankName($user, 'Diamond'),
            'crown_legs' => $this->getLegsCountByRankName($user, 'Crown'),
            'royal_legs' => $this->getLegsCountByRankName($user, 'Royal'),
            default => 0,
        };
    }

    /**
     * Check if user meets this requirement
     */
    public function checkUserMeetsRequirement(User $user): bool
    {
        $currentValue = $this->getUserCurrentValue($user);
        $targetValue = (float) $this->target_value;

        return match($this->operator) {
            '>=' => $currentValue >= $targetValue,
            '>' => $currentValue > $targetValue,
            '=' => $currentValue == $targetValue,
            '<=' => $currentValue <= $targetValue,
            '<' => $currentValue < $targetValue,
            default => false,
        };
    }

    /**
     * Get active referrals count
     */
    private function getActiveReferralsCount(\App\Models\MlmMember $mlmMember): int
    {
        return \App\Models\MlmMember::where('unilevel_sponsor_id', $mlmMember->id)
            ->where('is_qualified', true)
            ->count();
    }

    /**
     * Get consecutive months count
     */
    private function getConsecutiveMonths(\App\Models\MlmMember $mlmMember): int
    {
        // This would need more complex logic based on your business rules
        // For now, returning a simple calculation
        $monthsSinceCreation = now()->diffInMonths($mlmMember->joined_at ?? $mlmMember->created_at);
        return $monthsSinceCreation;
    }

    /**
     * นับจำนวนลูกทีมโดยตรงที่มีระดับตามที่ระบุ (Legs)
     *
     * ใช้สำหรับ requirement เช่น:
     * - diamond_legs: ต้องมีลูกทีมโดยตรงระดับ Diamond กี่คน
     * - crown_legs: ต้องมีลูกทีมโดยตรงระดับ Crown กี่คน
     * - royal_legs: ต้องมีลูกทีมโดยตรงระดับ Royal กี่คน
     *
     * @param User $user ผู้ใช้ที่ต้องการตรวจสอบ
     * @param string $rankName ชื่อระดับที่ต้องการนับ (เช่น 'Diamond', 'Crown', 'Royal')
     * @return int จำนวนลูกทีมที่มีระดับตามที่ระบุ
     */
    private function getLegsCountByRankName(User $user, string $rankName): int
    {
        // หาระดับที่ต้องการ
        $targetRank = \App\Models\Rank::where('name', $rankName)->first();
        if (!$targetRank) {
            return 0;
        }

        // หา MLM Member ของ user
        $mlmMember = $user->mlmMembers()->first();
        if (!$mlmMember) {
            return 0;
        }

        // นับลูกทีมโดยตรงที่มีระดับเท่ากับหรือสูงกว่าระดับเป้าหมาย
        return \App\Models\MlmMember::where('unilevel_sponsor_id', $mlmMember->id)
            ->whereHas('user', function ($query) use ($targetRank) {
                $query->whereHas('currentRank', function ($rankQuery) use ($targetRank) {
                    $rankQuery->where('level', '>=', $targetRank->level);
                });
            })
            ->count();
    }

    /**
     * Get progress percentage for this requirement
     */
    public function getProgressPercentage(User $user): float
    {
        $currentValue = $this->getUserCurrentValue($user);
        $targetValue = (float) $this->target_value;

        if ($targetValue <= 0) {
            return 0;
        }

        $percentage = ($currentValue / $targetValue) * 100;
        return min(100, max(0, $percentage));
    }
}
