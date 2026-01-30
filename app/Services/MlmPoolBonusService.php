<?php

namespace App\Services;

use App\Models\MlmMember;
use App\Models\MlmCommission;
use App\Models\MlmGlobalSetting;
use App\Models\MlmPoolBonusPeriod;
use App\Models\MlmPoolBonusShare;
use App\Models\Order;
use App\Models\Rank;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * MlmPoolBonusService - จัดการ Pool Bonus (All Sale Bonus)
 *
 * Pool Bonus คือโบนัสที่หักจากยอดขายรวมของระบบ
 * แล้วแบ่งให้สมาชิกที่มีคุณสมบัติตามจำนวน shares
 *
 * Features:
 * - รอบการคำนวณ: รายวัน/รายสัปดาห์/รายเดือน
 * - คุณสมบัติ: active member, มียอด PV ขั้นต่ำ, rank ขั้นต่ำ
 * - Shares: ตาม rank หรือ performance
 * - Rank Shares: rank สูงได้ shares มากกว่า
 */
class MlmPoolBonusService
{
    /**
     * สร้าง period ใหม่สำหรับรอบถัดไป
     *
     * @param string $periodType daily/weekly/monthly
     * @return MlmPoolBonusPeriod
     */
    public function createPeriod(string $periodType = null): MlmPoolBonusPeriod
    {
        $periodType = $periodType ?? MlmGlobalSetting::get('pool_bonus_period_type', 'weekly');

        // คำนวณ start/end ตาม period type
        $dates = $this->calculatePeriodDates($periodType);

        // ตรวจสอบว่ามี period นี้อยู่แล้วหรือไม่
        $existing = MlmPoolBonusPeriod::where('period_type', $periodType)
            ->where('period_start', $dates['start'])
            ->first();

        if ($existing) {
            return $existing;
        }

        $poolPercentage = MlmGlobalSetting::get('pool_bonus_percentage', 5);

        return MlmPoolBonusPeriod::create([
            'period_type' => $periodType,
            'period_start' => $dates['start'],
            'period_end' => $dates['end'],
            'pool_percentage' => $poolPercentage,
            'status' => 'pending',
        ]);
    }

    /**
     * คำนวณวันที่ start/end ของ period
     *
     * @param string $periodType
     * @return array ['start' => Carbon, 'end' => Carbon]
     */
    protected function calculatePeriodDates(string $periodType): array
    {
        $now = Carbon::now();

        return match ($periodType) {
            'daily' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
            'weekly' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
            ],
            'monthly' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            default => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
            ],
        };
    }

    /**
     * คำนวณยอดขายรวมในช่วง period
     *
     * @param MlmPoolBonusPeriod $period
     * @return array ['total_sales' => float, 'total_pv' => float]
     */
    public function calculatePeriodSales(MlmPoolBonusPeriod $period): array
    {
        // ดึงยอดขายจาก Orders ที่ paid ในช่วง period
        $sales = Order::where('status', 'paid')
            ->whereBetween('paid_at', [$period->period_start, $period->period_end])
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_sales')
            ->first();

        // ดึงยอด PV จาก commissions
        $pv = MlmCommission::whereBetween('created_at', [$period->period_start, $period->period_end])
            ->selectRaw('COALESCE(SUM(pv_amount), 0) as total_pv')
            ->first();

        return [
            'total_sales' => (float) ($sales->total_sales ?? 0),
            'total_pv' => (float) ($pv->total_pv ?? 0),
        ];
    }

    /**
     * ดึงสมาชิกที่มีคุณสมบัติรับ Pool Bonus
     *
     * เงื่อนไข:
     * 1. สถานะ active
     * 2. มี PV ส่วนตัวขั้นต่ำในรอบนี้
     * 3. มี rank ขั้นต่ำ (ถ้าตั้งค่าไว้)
     *
     * @param MlmPoolBonusPeriod $period
     * @return \Illuminate\Support\Collection
     */
    public function getQualifiedMembers(MlmPoolBonusPeriod $period): \Illuminate\Support\Collection
    {
        $minPersonalPv = MlmGlobalSetting::get('pool_bonus_min_personal_pv', 0);
        $minTeamPv = MlmGlobalSetting::get('pool_bonus_min_team_pv', 0);
        $minRankLevel = MlmGlobalSetting::get('pool_bonus_min_rank_level', 0);
        $requireActive = MlmGlobalSetting::get('pool_bonus_require_active', true);

        // Query สมาชิกที่ qualify
        $query = MlmMember::with(['user', 'user.rank'])
            ->where('status', 'active');

        if ($requireActive) {
            $query->where('is_qualified', true);
        }

        $members = $query->get();

        // Filter ตามเงื่อนไข
        return $members->filter(function ($member) use ($period, $minPersonalPv, $minTeamPv, $minRankLevel) {
            // คำนวณ PV ส่วนตัวในรอบนี้
            $personalPv = $this->getMemberPersonalPv($member, $period);

            if ($personalPv < $minPersonalPv) {
                return false;
            }

            // คำนวณ PV ทีมในรอบนี้
            $teamPv = $this->getMemberTeamPv($member, $period);

            if ($teamPv < $minTeamPv) {
                return false;
            }

            // ตรวจสอบ rank
            // แก้ Bug #9: ใช้ currentRank แทน rank (User model ไม่มี rank relationship)
            if ($minRankLevel > 0) {
                $userRank = $member->user->currentRank ?? null;
                if (!$userRank || $userRank->level < $minRankLevel) {
                    return false;
                }
            }

            // เก็บค่าไว้ใช้ต่อ
            $member->calculated_personal_pv = $personalPv;
            $member->calculated_team_pv = $teamPv;

            return true;
        });
    }

    /**
     * ดึง PV ส่วนตัวของสมาชิกในรอบนี้
     *
     * @param MlmMember $member
     * @param MlmPoolBonusPeriod $period
     * @return float
     */
    protected function getMemberPersonalPv(MlmMember $member, MlmPoolBonusPeriod $period): float
    {
        return (float) MlmCommission::where('mlm_member_id', $member->id)
            ->whereBetween('created_at', [$period->period_start, $period->period_end])
            ->sum('pv_amount');
    }

    /**
     * ดึง PV ทีมของสมาชิกในรอบนี้
     *
     * @param MlmMember $member
     * @param MlmPoolBonusPeriod $period
     * @return float
     */
    protected function getMemberTeamPv(MlmMember $member, MlmPoolBonusPeriod $period): float
    {
        // ดึง downline ทั้งหมด (รองรับทั้ง path ที่ขึ้นต้นด้วย member id และที่อยู่กลางทาง)
        $downlineIds = MlmMember::where(function ($q) use ($member) {
                $q->where('unilevel_path', 'like', $member->id . '/%')
                  ->orWhere('unilevel_path', 'like', '%/' . $member->id . '/%')
                  ->orWhere('unilevel_sponsor_id', $member->id);
            })
            ->pluck('id')
            ->toArray();

        if (empty($downlineIds)) {
            return 0;
        }

        return (float) MlmCommission::whereIn('mlm_member_id', $downlineIds)
            ->whereBetween('created_at', [$period->period_start, $period->period_end])
            ->sum('pv_amount');
    }

    /**
     * คำนวณจำนวน shares ของสมาชิก
     *
     * Shares ขึ้นอยู่กับ:
     * 1. Rank ของสมาชิก (rank สูง = shares มาก)
     * 2. ค่า pool_bonus_share_mode
     *
     * @param MlmMember $member
     * @return int
     */
    public function calculateMemberShares(MlmMember $member): int
    {
        $shareMode = MlmGlobalSetting::get('pool_bonus_share_mode', 'rank_based');
        $baseShares = MlmGlobalSetting::get('pool_bonus_base_shares', 1);

        switch ($shareMode) {
            case 'equal':
                // ทุกคนได้เท่ากัน
                return $baseShares;

            case 'rank_based':
                // ตาม rank (ใช้ pool_bonus_shares จาก setting หรือ rank level)
                $rankShares = $this->getRankSharesConfig();
                // แก้ Bug #9: ใช้ currentRank แทน rank
                $userRank = $member->user->currentRank ?? null;

                if ($userRank && isset($rankShares[$userRank->id])) {
                    return (int) $rankShares[$userRank->id];
                }

                // ถ้าไม่มี config ให้ใช้ rank level เป็น shares
                return $userRank ? max(1, $userRank->level) : $baseShares;

            case 'pv_based':
                // ตาม PV ที่ทำได้ (ทุก X PV = 1 share)
                $pvPerShare = MlmGlobalSetting::get('pool_bonus_pv_per_share', 100);
                $personalPv = $member->calculated_personal_pv ?? $member->total_pv;

                return max($baseShares, (int) floor($personalPv / $pvPerShare));

            default:
                return $baseShares;
        }
    }

    /**
     * ดึง config shares ตาม rank
     *
     * @return array [rank_id => shares]
     */
    protected function getRankSharesConfig(): array
    {
        $config = MlmGlobalSetting::get('pool_bonus_rank_shares', []);

        if (is_string($config)) {
            $config = json_decode($config, true) ?? [];
        }

        return $config;
    }

    /**
     * คำนวณและกระจาย Pool Bonus สำหรับ period
     *
     * @param MlmPoolBonusPeriod $period
     * @return array ผลลัพธ์การคำนวณ
     */
    public function calculateAndDistribute(MlmPoolBonusPeriod $period): array
    {
        if (!MlmGlobalSetting::get('pool_bonus_enabled', false)) {
            return [
                'success' => false,
                'message' => 'Pool Bonus is disabled',
            ];
        }

        DB::beginTransaction();

        try {
            $period->startCalculation();

            // 1. คำนวณยอดขายรวม
            $salesData = $this->calculatePeriodSales($period);
            $poolPercentage = $period->pool_percentage;
            $poolAmount = ($salesData['total_sales'] * $poolPercentage) / 100;

            // 2. ดึงสมาชิกที่ qualify
            $qualifiedMembers = $this->getQualifiedMembers($period);
            $qualifiedCount = $qualifiedMembers->count();

            if ($qualifiedCount === 0 || $poolAmount <= 0) {
                $period->update([
                    'total_sales' => $salesData['total_sales'],
                    'total_pv' => $salesData['total_pv'],
                    'pool_amount' => $poolAmount,
                    'qualified_members_count' => 0,
                    'status' => 'pending',
                    'notes' => $qualifiedCount === 0 ? 'ไม่มีสมาชิกที่ qualify' : 'ยอด pool = 0',
                ]);

                DB::commit();

                return [
                    'success' => true,
                    'message' => $qualifiedCount === 0 ? 'ไม่มีสมาชิกที่ qualify' : 'ยอด pool = 0',
                    'qualified_count' => 0,
                    'pool_amount' => $poolAmount,
                ];
            }

            // 3. คำนวณ total shares
            $totalShares = 0;
            $memberSharesData = [];

            foreach ($qualifiedMembers as $member) {
                $shares = $this->calculateMemberShares($member);
                $totalShares += $shares;

                $memberSharesData[] = [
                    'member' => $member,
                    'shares' => $shares,
                    'personal_pv' => $member->calculated_personal_pv ?? 0,
                    'team_pv' => $member->calculated_team_pv ?? 0,
                ];
            }

            // 4. คำนวณ amount per share
            $amountPerShare = $totalShares > 0 ? $poolAmount / $totalShares : 0;

            // 5. อัพเดท period
            $period->update([
                'total_sales' => $salesData['total_sales'],
                'total_pv' => $salesData['total_pv'],
                'pool_amount' => $poolAmount,
                'qualified_members_count' => $qualifiedCount,
                'amount_per_share' => $amountPerShare,
                'total_shares' => $totalShares,
            ]);

            // 6. สร้าง shares สำหรับแต่ละสมาชิก
            $createdShares = [];
            foreach ($memberSharesData as $data) {
                $member = $data['member'];
                $shares = $data['shares'];
                $totalAmount = $shares * $amountPerShare;

                $share = MlmPoolBonusShare::create([
                    'pool_period_id' => $period->id,
                    'mlm_member_id' => $member->id,
                    'user_id' => $member->user_id,
                    'shares' => $shares,
                    'share_amount' => $amountPerShare,
                    'total_amount' => $totalAmount,
                    'qualification_reason' => 'qualified',
                    'rank_id' => $member->user->current_rank_id,
                    'personal_pv' => $data['personal_pv'],
                    'team_pv' => $data['team_pv'],
                    'status' => 'pending',
                ]);

                $createdShares[] = $share;
            }

            // 7. บันทึกรายละเอียดการคำนวณ
            $period->finishCalculation([
                'total_sales' => $salesData['total_sales'],
                'total_pv' => $salesData['total_pv'],
                'pool_percentage' => $poolPercentage,
                'pool_amount' => $poolAmount,
                'qualified_count' => $qualifiedCount,
                'total_shares' => $totalShares,
                'amount_per_share' => $amountPerShare,
                'calculated_at' => now()->toISOString(),
            ]);

            DB::commit();

            Log::info('Pool Bonus calculated successfully', [
                'period_id' => $period->id,
                'pool_amount' => $poolAmount,
                'qualified_count' => $qualifiedCount,
                'total_shares' => $totalShares,
            ]);

            return [
                'success' => true,
                'message' => 'คำนวณ Pool Bonus สำเร็จ',
                'period_id' => $period->id,
                'pool_amount' => $poolAmount,
                'qualified_count' => $qualifiedCount,
                'total_shares' => $totalShares,
                'amount_per_share' => $amountPerShare,
                'shares_created' => count($createdShares),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Pool Bonus calculation failed', [
                'period_id' => $period->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * อนุมัติและจ่าย Pool Bonus
     *
     * @param MlmPoolBonusPeriod $period
     * @return array
     */
    public function approveAndPay(MlmPoolBonusPeriod $period): array
    {
        if ($period->status !== 'pending') {
            return [
                'success' => false,
                'message' => 'Period status ไม่ถูกต้อง: ' . $period->status,
            ];
        }

        DB::beginTransaction();

        try {
            $shares = $period->shares()->where('status', 'pending')->get();
            $paidCount = 0;
            $totalPaid = 0;

            foreach ($shares as $share) {
                // สร้าง commission record
                $commission = MlmCommission::create([
                    'mlm_member_id' => $share->mlm_member_id,
                    'mlm_plan_id' => $share->member->mlm_plan_id ?? 1,
                    'user_id' => $share->user_id,
                    'from_member_id' => null,
                    'source_type' => 'App\\Models\\MlmPoolBonusPeriod',
                    'source_id' => $period->id,
                    'type' => 'pool_bonus',
                    'level' => null,
                    'pv_amount' => $share->personal_pv,
                    'sales_amount' => 0,
                    'commission_amount' => $share->total_amount,
                    'percentage' => $period->pool_percentage,
                    'status' => 'approved',
                    'approved_at' => now(),
                    'notes' => sprintf(
                        'Pool Bonus รอบ %s - %s (%d shares × %.2f บาท)',
                        $period->period_start->format('d/m/Y'),
                        $period->period_end->format('d/m/Y'),
                        $share->shares,
                        $share->share_amount
                    ),
                ]);

                // อัพเดท share
                $share->markAsPaid($commission->id);

                $paidCount++;
                $totalPaid += $share->total_amount;
            }

            // อัพเดท period
            $period->markAsDistributed([
                'paid_count' => $paidCount,
                'total_paid' => $totalPaid,
                'distributed_at' => now()->toISOString(),
            ]);

            DB::commit();

            Log::info('Pool Bonus distributed successfully', [
                'period_id' => $period->id,
                'paid_count' => $paidCount,
                'total_paid' => $totalPaid,
            ]);

            return [
                'success' => true,
                'message' => 'จ่าย Pool Bonus สำเร็จ',
                'paid_count' => $paidCount,
                'total_paid' => $totalPaid,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Pool Bonus distribution failed', [
                'period_id' => $period->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ดึงสรุป Pool Bonus
     *
     * @param int $limit
     * @return array
     */
    public function getSummary(int $limit = 10): array
    {
        $periods = MlmPoolBonusPeriod::with(['shares' => function ($q) {
                $q->where('status', 'paid')->limit(5);
            }])
            ->orderBy('period_start', 'desc')
            ->limit($limit)
            ->get();

        $totals = MlmPoolBonusPeriod::where('status', 'distributed')
            ->selectRaw('
                SUM(pool_amount) as total_pool,
                SUM(qualified_members_count) as total_members,
                COUNT(*) as total_periods
            ')
            ->first();

        return [
            'periods' => $periods,
            'totals' => [
                'total_pool' => (float) ($totals->total_pool ?? 0),
                'total_members' => (int) ($totals->total_members ?? 0),
                'total_periods' => (int) ($totals->total_periods ?? 0),
            ],
        ];
    }

    /**
     * รัน Pool Bonus อัตโนมัติ (สำหรับ cron job)
     *
     * @return array
     */
    public function runAutoPoolBonus(): array
    {
        if (!MlmGlobalSetting::get('pool_bonus_enabled', false)) {
            return ['success' => false, 'message' => 'Pool Bonus is disabled'];
        }

        if (!MlmGlobalSetting::get('pool_bonus_auto_distribute', false)) {
            return ['success' => false, 'message' => 'Auto distribute is disabled'];
        }

        // สร้าง period ใหม่
        $period = $this->createPeriod();

        // คำนวณ
        $calcResult = $this->calculateAndDistribute($period);

        if (!$calcResult['success']) {
            return $calcResult;
        }

        // จ่ายอัตโนมัติถ้าเปิดไว้
        if (MlmGlobalSetting::get('pool_bonus_auto_pay', false)) {
            return $this->approveAndPay($period);
        }

        return $calcResult;
    }
}
