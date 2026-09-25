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
        // 🐛 (2026-09-25) audit G5: เดิม insert wallet_transactions เองโดยไม่มี balance_before (NOT NULL)
        //    → QueryException ทุกครั้ง + ไม่มี lock ทำให้แอดมินกดซ้ำ/2 request พร้อมกันจ่ายซ้ำได้
        //    ตอนนี้: จ่ายทีละรายการใน transaction ของตัวเอง, lock แถวคอมแล้วตรวจสถานะซ้ำ,
        //    หักกองทุน MLM + ฝากเข้า wallet ผ่าน WalletService (type = commission) → รายการที่ล้มไม่ลากรายการอื่น
        $query = MlmCommission::where('status', 'approved')->orderBy('id');

        if ($commissionIds) {
            $query->whereIn('id', (array) $commissionIds);
        }

        $ids = $query->pluck('id');
        $paidCount = 0;
        $walletService = app(WalletService::class);
        $revenueService = new PlatformRevenueService;

        foreach ($ids as $commissionId) {
            try {
                $paid = DB::transaction(function () use ($commissionId, $walletService, $revenueService) {
                    /** @var MlmCommission|null $commission */
                    $commission = MlmCommission::whereKey($commissionId)->lockForUpdate()->first();

                    // ถูกจ่าย/ยกเลิกไปแล้วโดย request อื่น → ข้าม
                    if (! $commission || $commission->status !== 'approved') {
                        return false;
                    }

                    $amount = round((float) $commission->commission_amount, 2);
                    $user = $commission->user_id ? \App\Models\User::withTrashed()->find($commission->user_id) : null;

                    if (! $user || $amount <= 0) {
                        Log::warning('MLM commission payout skipped: no user or zero amount', [
                            'commission_id' => $commission->id,
                        ]);

                        return false;
                    }

                    // กันจ่ายซ้ำ: เคยมีรายการ wallet ของคอมนี้แล้ว → แค่ปิดสถานะ
                    $existingTx = WalletTransaction::where('reference_type', MlmCommission::class)
                        ->where('reference_id', $commission->id)
                        ->where('type', 'commission')
                        ->first();
                    if ($existingTx) {
                        $commission->markAsPaid($existingTx->id);

                        return false;
                    }

                    $wallet = $walletService->getOrCreateWallet($user);
                    if (! $wallet->isActive()) {
                        Log::warning('MLM commission payout skipped: wallet inactive', [
                            'commission_id' => $commission->id,
                            'user_id' => $user->id,
                        ]);

                        return false;
                    }

                    // หักกองทุน MLM ก่อน (ไม่พอ = throw → ข้ามรายการนี้ทั้งก้อน)
                    $revenueService->payMlmCommission($amount, $user->id, 'MlmCommission', $commission->id, [
                        'commission_type' => $commission->type,
                        'from_member_id' => $commission->from_member_id,
                    ]);

                    $walletTransaction = $walletService->deposit(
                        $wallet,
                        $amount,
                        'คอมมิชชัน MLM: '.($commission->type ?? 'commission'),
                        MlmCommission::class,
                        (int) $commission->id,
                        [
                            'mlm_commission_id' => $commission->id,
                            'commission_type' => $commission->type,
                        ],
                        'commission'
                    );

                    $commission->markAsPaid($walletTransaction->id);

                    if ($commission->mlm_member_id) {
                        MlmMember::whereKey($commission->mlm_member_id)->increment('total_earnings', $amount);
                    }

                    return true;
                });

                if ($paid) {
                    $paidCount++;
                }
            } catch (\Throwable $e) {
                // กองทุนไม่พอ/wallet มีปัญหา → ข้ามรายการนี้ รายการอื่นจ่ายต่อได้
                Log::warning('MLM commission payout failed', [
                    'commission_id' => $commissionId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('MLM commissions paid', ['count' => $paidCount, 'requested' => $ids->count()]);

        return $paidCount;
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
