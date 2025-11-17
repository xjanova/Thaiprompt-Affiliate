<?php

namespace App\Services;

use App\Models\User;
use App\Models\MlmMember;

/**
 * Unified Earning Service
 *
 * รวม earnings และ referrals จากทั้ง Affiliate และ MLM systems
 * เพื่อใช้ในการคำนวณ rank points
 */
class UnifiedEarningService
{
    /**
     * คำนวณ total earnings จากทั้ง Affiliate และ MLM
     *
     * @param User $user
     * @return float
     */
    public function getTotalEarnings(User $user): float
    {
        // Earnings จาก Affiliate system
        $affiliateEarnings = $user->affiliate->total_earnings ?? 0;

        // Earnings จาก MLM system (รวมทุก active memberships)
        $mlmEarnings = MlmMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->sum('total_earnings');

        return $affiliateEarnings + $mlmEarnings;
    }

    /**
     * คำนวณ total referrals จากทั้ง Affiliate และ MLM
     *
     * @param User $user
     * @return int
     */
    public function getTotalReferrals(User $user): int
    {
        // Referrals จาก Affiliate system
        $affiliateReferrals = $user->affiliate->total_referrals ?? 0;

        // Referrals จาก MLM system (นับ unique children จาก unilevel tree)
        $mlmReferrals = MlmMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->get()
            ->map(function ($member) {
                // นับ direct children (level 1)
                return MlmMember::where('unilevel_sponsor_id', $member->id)
                    ->where('status', 'active')
                    ->distinct('user_id')
                    ->count('user_id');
            })
            ->sum();

        return $affiliateReferrals + $mlmReferrals;
    }

    /**
     * ดึงข้อมูลสถิติรวมสำหรับ dashboard
     *
     * @param User $user
     * @return array
     */
    public function getEarningsBreakdown(User $user): array
    {
        $affiliateEarnings = $user->affiliate->total_earnings ?? 0;
        $affiliateReferrals = $user->affiliate->total_referrals ?? 0;

        $mlmEarnings = MlmMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->sum('total_earnings');

        $mlmMembers = MlmMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->get();

        $mlmReferrals = 0;
        foreach ($mlmMembers as $member) {
            $mlmReferrals += MlmMember::where('unilevel_sponsor_id', $member->id)
                ->where('status', 'active')
                ->distinct('user_id')
                ->count('user_id');
        }

        return [
            'affiliate' => [
                'earnings' => $affiliateEarnings,
                'referrals' => $affiliateReferrals,
            ],
            'mlm' => [
                'earnings' => $mlmEarnings,
                'referrals' => $mlmReferrals,
                'memberships_count' => $mlmMembers->count(),
            ],
            'total' => [
                'earnings' => $affiliateEarnings + $mlmEarnings,
                'referrals' => $affiliateReferrals + $mlmReferrals,
            ],
        ];
    }

    /**
     * ดึง total PV จาก MLM (สำหรับข้อมูลเพิ่มเติม)
     *
     * @param User $user
     * @return float
     */
    public function getTotalPv(User $user): float
    {
        return MlmMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->sum('total_pv');
    }

    /**
     * ตรวจสอบว่า user มี Affiliate membership หรือไม่
     *
     * @param User $user
     * @return bool
     */
    public function hasAffiliate(User $user): bool
    {
        return $user->affiliate !== null;
    }

    /**
     * ตรวจสอบว่า user มี MLM membership หรือไม่
     *
     * @param User $user
     * @return bool
     */
    public function hasMlm(User $user): bool
    {
        return MlmMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * ดึงข้อมูลรวมสำหรับ rank calculation
     *
     * @param User $user
     * @return array
     */
    public function getRankCalculationData(User $user): array
    {
        return [
            'total_earnings' => $this->getTotalEarnings($user),
            'total_referrals' => $this->getTotalReferrals($user),
            'total_pv' => $this->getTotalPv($user),
            'has_affiliate' => $this->hasAffiliate($user),
            'has_mlm' => $this->hasMlm($user),
        ];
    }
}
