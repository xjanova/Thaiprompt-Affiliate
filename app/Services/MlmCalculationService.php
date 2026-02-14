<?php

namespace App\Services;

use App\Models\MlmCommission;
use App\Models\MlmGlobalSetting;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\Order;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MlmCalculationService
{
    protected $unilevelService;

    protected $binaryService;

    protected $pvService;

    public function __construct()
    {
        $this->unilevelService = new MlmUnilevelService;
        $this->binaryService = new MlmBinaryService;
        $this->pvService = new MlmPvService;
    }

    /**
     * Process commissions for an order
     *
     * ⚠️ WARNING: นี่คือ Legacy engine - ระบบหลักใช้ MlmCommissionService แทน
     * method นี้มี guard ป้องกัน duplicate commission จาก production engine
     *
     * @deprecated ใช้ MlmCommissionService::processOrderCommissions() แทน
     */
    public function processOrderCommissions(Order $order)
    {
        DB::beginTransaction();

        try {
            // แก้ Bug PV-5: ตรวจสอบว่า Order นี้ประมวลผล PV/Commission ไปแล้วหรือยัง
            $existingPvTransaction = \App\Models\MlmPvTransaction::where('order_id', $order->id)->exists();
            if ($existingPvTransaction) {
                Log::info('Order PV already processed, skipping MlmCalculationService', [
                    'order_id' => $order->id,
                ]);
                DB::commit();

                return true;
            }

            // แก้ Warning: Dual Engine Guard - ป้องกัน duplicate commission จาก production engine (MlmCommissionService)
            $existingCommission = MlmCommission::where('source_type', Order::class)
                ->where('source_id', $order->id)
                ->whereIn('type', ['unilevel_direct', 'unilevel_indirect', 'unilevel_rollup', 'binary_pair'])
                ->exists();

            if ($existingCommission) {
                Log::warning('Order commissions already processed by production engine, skipping legacy MlmCalculationService', [
                    'order_id' => $order->id,
                ]);
                DB::commit();

                return true;
            }

            Log::info('MlmCalculationService (legacy) processing order - consider using MlmCommissionService instead', [
                'order_id' => $order->id,
            ]);

            $user = $order->user;
            if (! $user) {
                throw new \Exception('Order has no user');
            }

            // Get all MLM memberships for this user
            $mlmMembers = MlmMember::where('user_id', $user->id)
                ->where('status', 'active')
                ->get();

            foreach ($mlmMembers as $member) {
                $plan = $member->plan;

                if (! $plan || ! $plan->is_active) {
                    continue;
                }

                // Calculate PV for this order
                $pvData = $this->pvService->calculateOrderPv($order, $plan);

                if ($pvData['total_pv'] > 0) {
                    // Record PV transaction
                    $this->pvService->recordPvTransaction($member, $order, $pvData);

                    // Update member PV
                    $member->increment('total_pv', $pvData['total_pv']);
                    $member->update(['last_purchase_at' => now()]);

                    // Process commissions based on plan type
                    if ($plan->type === 'unilevel' || $plan->type === 'hybrid') {
                        $this->unilevelService->calculateUnilevelCommissions(
                            $member,
                            $order,
                            $pvData
                        );
                    }

                    if ($plan->type === 'binary' || $plan->type === 'hybrid') {
                        $this->binaryService->calculateBinaryCommissions(
                            $member,
                            $order,
                            $pvData
                        );
                    }
                }
            }

            DB::commit();
            Log::info('MLM commissions processed for order', ['order_id' => $order->id]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing MLM commissions', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Approve pending commissions
     */
    public function approvePendingCommissions($commissionIds = null)
    {
        $query = MlmCommission::where('status', 'pending');

        if ($commissionIds) {
            $query->whereIn('id', $commissionIds);
        }

        $commissions = $query->get();

        foreach ($commissions as $commission) {
            $commission->approve();
        }

        return $commissions->count();
    }

    /**
     * Pay approved commissions
     * แก้ Bug DIST-1: เพิ่มการหัก MLM Pool wallet เมื่อจ่ายคอมมิชชัน
     * แก้ Bug DIST-2: ใช้ atomic read สำหรับ balance_after
     */
    public function payApprovedCommissions($commissionIds = null)
    {
        DB::beginTransaction();

        try {
            $query = MlmCommission::where('status', 'approved')
                ->with(['user', 'user.wallet', 'member']);

            if ($commissionIds) {
                $query->whereIn('id', $commissionIds);
            }

            $commissions = $query->get();
            $paidCount = 0;
            $revenueService = new PlatformRevenueService;

            foreach ($commissions as $commission) {
                $user = $commission->user;

                if (! $user || ! $user->wallet) {
                    continue;
                }

                // แก้ Bug DIST-1: หักเงินจาก MLM Pool wallet ก่อนจ่ายให้ user
                try {
                    $revenueService->payMlmCommission(
                        $commission->commission_amount,
                        $user->id,
                        'MlmCommission',
                        $commission->id,
                        [
                            'commission_type' => $commission->type,
                            'from_member_id' => $commission->from_member_id,
                        ]
                    );
                } catch (\Exception $e) {
                    // ถ้า MLM Pool ไม่พอจ่าย → ข้ามรายการนี้
                    Log::warning('MLM Pool insufficient for commission payout', [
                        'commission_id' => $commission->id,
                        'amount' => $commission->commission_amount,
                        'error' => $e->getMessage(),
                    ]);

                    continue;
                }

                // แก้ Bug DIST-2: ใช้ increment ก่อน แล้วค่อยอ่าน balance ที่ถูกต้อง
                $user->wallet->increment('balance', $commission->commission_amount);
                $user->wallet->refresh();

                // Create wallet transaction (ใช้ balance หลัง increment เพื่อให้ balance_after ถูกต้อง)
                $walletTransaction = WalletTransaction::create([
                    'wallet_id' => $user->wallet->id,
                    'user_id' => $user->id,
                    'type' => 'commission',
                    'amount' => $commission->commission_amount,
                    'balance_after' => $user->wallet->balance,
                    'description' => 'MLM Commission: '.$commission->type,
                    'status' => 'completed',
                    'metadata' => json_encode([
                        'mlm_commission_id' => $commission->id,
                        'commission_type' => $commission->type,
                    ]),
                ]);

                // Mark commission as paid
                $commission->markAsPaid($walletTransaction->id);

                // Update member earnings
                if ($commission->member) {
                    $commission->member->increment('total_earnings', $commission->commission_amount);
                }

                $paidCount++;
            }

            DB::commit();
            Log::info('MLM commissions paid', ['count' => $paidCount]);

            return $paidCount;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error paying MLM commissions', ['error' => $e->getMessage()]);

            throw $e;
        }
    }

    /**
     * Calculate commission preview for a member
     *
     * ⚠️ IMPORTANT: ใช้ค่าจาก MlmGlobalSetting เท่านั้น
     * ไม่ใช้ค่าจาก MlmPlan (per-plan settings) เพื่อความเป็นเอกภาพ
     */
    public function calculateCommissionPreview(MlmMember $member, $orderAmount, $orderPv = null)
    {
        $plan = $member->plan;

        // ใช้ Global Settings แทน per-plan settings
        $pvRate = MlmGlobalSetting::get('global_pv_rate', 1);

        if (! $orderPv) {
            $orderPv = $orderAmount * $pvRate;
        }

        $preview = [
            'personal_pv' => $orderPv,
            'unilevel_commission' => 0,
            'binary_commission' => 0,
            'total_commission' => 0,
        ];

        // ดึงค่าจาก Global Settings
        $unilevelEnabled = MlmGlobalSetting::get('unilevel_enabled', true);
        $binaryEnabled = MlmGlobalSetting::get('binary_enabled', true);
        $levels = MlmGlobalSetting::get('unilevel_levels', []);
        $binaryMatchPercentage = MlmGlobalSetting::get('binary_match_percentage', 50);

        // ดึงอัตราแปลง PV → บาท (แอดมินตั้งค่าได้)
        $commissionPerPv = (float) MlmGlobalSetting::get('commission_per_pv', 1);

        // Unilevel preview (direct level only)
        if ($unilevelEnabled && ($plan->type === 'unilevel' || $plan->type === 'hybrid')) {
            if (! empty($levels) && isset($levels[0])) {
                $preview['unilevel_commission'] = $orderPv * ($levels[0]['percentage'] / 100) * $commissionPerPv;
            }
        }

        // Binary preview (estimated)
        if ($binaryEnabled && ($plan->type === 'binary' || $plan->type === 'hybrid')) {
            $preview['binary_commission'] = $orderPv * ($binaryMatchPercentage / 100) * $commissionPerPv;
        }

        $preview['total_commission'] = $preview['unilevel_commission'] + $preview['binary_commission'];

        return $preview;
    }

    /**
     * Get member statistics
     */
    public function getMemberStatistics(MlmMember $member)
    {
        return [
            'total_earnings' => $member->total_earnings,
            'total_pv' => $member->total_pv,
            'total_team_pv' => $member->total_team_pv,
            'direct_referrals' => $member->total_direct_referrals,
            'team_members' => $member->total_team_members,
            'left_leg_pv' => $member->left_leg_pv,
            'right_leg_pv' => $member->right_leg_pv,
            'pending_commissions' => $member->commissions()->pending()->sum('commission_amount'),
            'paid_commissions' => $member->commissions()->paid()->sum('commission_amount'),
            // แก้ Bug #33: เพิ่ม whereYear เพื่อป้องกันการรวมข้อมูลข้ามปี
            'this_month_earnings' => $member->commissions()
                ->paid()
                ->whereYear('paid_at', now()->year)
                ->whereMonth('paid_at', now()->month)
                ->sum('commission_amount'),
        ];
    }
}
