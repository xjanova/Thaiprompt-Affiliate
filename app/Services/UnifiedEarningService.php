<?php

namespace App\Services;

use App\Models\User;
use App\Models\MlmMember;

/**
 * Unified Earning Service
 *
 * รวม earnings และ referrals จาก MLM system
 * เพื่อใช้ในการคำนวณ rank points
 */
class UnifiedEarningService
{
    /**
     * คำนวณ total earnings จาก MLM system
     *
     * @param User $user
     * @return float
     */
    public function getTotalEarnings(User $user): float
    {
        // Earnings จาก MLM commissions
        $mlmEarnings = $user->mlmCommissions()
            ->whereIn('status', ['approved', 'paid'])
            ->sum('commission_amount');

        return $mlmEarnings;
    }

    /**
     * คำนวณ total referrals จาก MLM system
     *
     * @param User $user
     * @return int
     */
    public function getTotalReferrals(User $user): int
    {
        // Referrals จาก MLM system (นับ direct referrals)
        $mlmMember = $user->mlmMembers()->first();

        if (!$mlmMember) {
            return 0;
        }

        return $mlmMember->total_direct_referrals ?? 0;
    }

    /**
     * ดึงข้อมูลสถิติรวมสำหรับ dashboard
     *
     * @param User $user
     * @return array
     */
    public function getEarningsBreakdown(User $user): array
    {
        $mlmMember = $user->mlmMembers()->first();

        $mlmEarnings = $user->mlmCommissions()
            ->whereIn('status', ['approved', 'paid'])
            ->sum('commission_amount');

        $mlmReferrals = $mlmMember?->total_direct_referrals ?? 0;

        return [
            'mlm' => [
                'earnings' => $mlmEarnings,
                'referrals' => $mlmReferrals,
                'member_code' => $mlmMember?->member_code,
                'total_team_pv' => $mlmMember?->total_team_pv ?? 0,
            ],
            'total' => [
                'earnings' => $mlmEarnings,
                'referrals' => $mlmReferrals,
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
     * ตรวจสอบว่า user มี MLM membership หรือไม่
     *
     * @param User $user
     * @return bool
     */
    public function hasMlm(User $user): bool
    {
        return $user->mlmMembers()->exists();
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
            'has_mlm' => $this->hasMlm($user),
        ];
    }
}
