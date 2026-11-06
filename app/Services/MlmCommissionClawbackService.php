<?php

namespace App\Services;

use App\Models\EarningsLedger;
use App\Models\MlmCommission;
use App\Models\MlmMember;
use App\Models\MlmPvTransaction;
use App\Models\Order;
use App\Models\User;
use App\Models\WalletDebt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MlmCommissionClawbackService
 *
 * จัดการ Clawback (เรียกคืน) MLM Commission เมื่อมีการ Refund
 * - ค้นหา Commission ทั้งหมดที่เกี่ยวข้องกับ Order
 * - สร้างหนี้สำหรับทุกคนที่ได้รับ Commission
 * - ถ้ามีเงินใน Wallet → หักทันที
 * - ถ้าไม่พอ → สร้างเป็นหนี้รอหักจากรายได้ในอนาคต
 */
class MlmCommissionClawbackService
{
    protected DebtCollectionService $debtService;

    public function __construct()
    {
        $this->debtService = new DebtCollectionService;
    }

    /**
     * Clawback ทุก Commission ที่เกี่ยวข้องกับ Order + ย้อน PV ของออเดอร์ (เรียกซ้ำได้ ไม่ดึงซ้ำ)
     *
     * 🔒 F13: ล็อกออเดอร์ก่อน แล้วล็อกแถวคอม (ลำดับเดียวกับฝั่งจ่าย MlmCalculationService::payApprovedCommissions)
     *    เดิมอ่านแถวโดยไม่ล็อก → แอดมินกดจ่ายพร้อมกัน แถวกลายเป็น cancelled แต่เงินเข้ากระเป๋าไปแล้ว
     * 🛡️ F11/M6: หัก total PV ผู้ซื้อ (แถว refund ติดลบ), หัก PV ขา binary และดึงคู่ที่ใช้ PV ก้อนนี้คืนตามสัดส่วน
     *
     * @param  int|null  $adminId  Admin ที่ทำการ Refund
     */
    public function clawbackOrderCommissions(Order $order, ?int $adminId = null): array
    {
        return DB::transaction(function () use ($order, $adminId) {
            Order::whereKey($order->id)->lockForUpdate()->first();

            $results = [
                'order_id' => $order->id,
                'total_commissions' => 0,
                'total_clawback_amount' => 0,
                'affected_users' => [],
                'debts_created' => [],
                'deducted_from_wallets' => [],
                'cancelled_pending' => [],
                // F32: คอมของออเดอร์นี้ที่จ่ายไปแล้วแต่ดึงคืนไม่ได้ (กลายเป็นหนี้) — กองทุนย้อนส่วนนี้ไม่ได้
                'own_unrecovered_amount' => 0.0,
                'binary_pair_clawbacks' => [],
                'pv_reversal' => null,
            ];

            // ค้นหา Commission ทั้งหมดที่เกี่ยวข้องกับ Order นี้
            // ⚠️ ข้อมูลจริงบันทึก source_type เป็น FQCN (App\Models\Order) ผ่าน Order::class
            //    ต้อง match ทั้ง FQCN และ short name เดิม ไม่งั้น clawback หาไม่เจอเลย
            $commissions = MlmCommission::whereIn('source_type', [Order::class, 'Order'])
                ->where('source_id', $order->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($commissions->isEmpty()) {
                // ลองค้นหาจาก OrderItem
                $orderItemIds = $order->items()->pluck('id');
                $commissions = MlmCommission::whereIn('source_type', [\App\Models\OrderItem::class, 'OrderItem'])
                    ->whereIn('source_id', $orderItemIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
            }

            $results['total_commissions'] = $commissions->count();

            // 🔒 กัน deadlock กับฝั่งจ่าย: คู่ binary ของออเดอร์อื่นที่อาจถูกดึงคืน → ล็อก "ออเดอร์ต้นทาง → แถวคู่" ก่อนแตะสมาชิก/กระเป๋า
            //    (ฝั่งจ่ายล็อก ออเดอร์ → แถวคอม → กองทุน → กระเป๋า → สมาชิก) เดิมล็อกสมาชิกก่อนแถวคู่ → วนรอกันได้
            $this->lockCrossOrderPairRows($order);

            // 🛡️ F8: แถว pooled = เงินกองของออเดอร์ที่ไม่มีเจ้าของ (user_id null) และไม่เคยออกจาก mlm_pool
            //    → ยกเลิกแถว (ออเดอร์คืนเงินแล้ว เงินนี้ไม่ใช่เงินกองรายสัปดาห์อีก) โดยไม่สร้างหนี้/ไม่แตะกระเป๋าใคร
            //    ต้องแยกก่อน groupBy('user_id') — key null จะส่ง '' เข้า int $userId แล้วพังทั้ง transaction
            [$pooled, $commissions] = $commissions->partition(fn ($c) => $c->status === MlmCommission::STATUS_POOLED);
            foreach ($pooled as $poolRow) {
                $poolRow->update([
                    'status' => 'cancelled',
                    'rejection_reason' => "Refund - pooled amount released - Order #{$order->id}",
                    'rejected_at' => now(),
                ]);
                $results['cancelled_pending'][] = $poolRow->id;
            }
            // แถวที่ไม่มีเจ้าของแต่ไม่ใช่ pooled (ไม่ควรมี) — ข้าม ไม่เดาเจ้าของ
            $commissions = $commissions->filter(fn ($c) => $c->user_id !== null);

            // จัดกลุ่มตาม user_id
            $commissionsByUser = $commissions->groupBy('user_id');
            $reversedIds = [];

            foreach ($commissionsByUser as $userId => $userCommissions) {
                $userResult = $this->clawbackUserCommissions(
                    $userId,
                    $userCommissions,
                    $order,
                    $adminId
                );

                $results['total_clawback_amount'] += $userResult['clawback_amount'];
                $results['own_unrecovered_amount'] = round($results['own_unrecovered_amount'] + (float) $userResult['debt_created'], 2);
                $results['affected_users'][] = $userResult;
                $reversedIds = array_merge($reversedIds, $userResult['reversed_commission_ids']);

                if (! empty($userResult['debt_id'])) {
                    $results['debts_created'][] = $userResult['debt_id'];
                }

                if ($userResult['deducted_from_wallet'] > 0) {
                    $results['deducted_from_wallets'][] = [
                        'user_id' => $userId,
                        'amount' => $userResult['deducted_from_wallet'],
                    ];
                }

                if (! empty($userResult['cancelled_commission_ids'])) {
                    $results['cancelled_pending'] = array_merge(
                        $results['cancelled_pending'],
                        $userResult['cancelled_commission_ids']
                    );
                }
            }

            // 🛡️ F11/M6: ย้อน PV — คู่ binary ของออเดอร์นี้ที่เพิ่งถูกดึงคืน ให้คืน PV ที่ใช้กลับเข้าขาก่อนหัก
            $ownPairs = $commissions->whereIn('id', $reversedIds)->where('type', 'binary_pair')->values();
            $results['pv_reversal'] = $this->reverseOrderPv($order, $ownPairs, $adminId, $results);

            Log::info('MLM Commission clawback completed', $results);

            return $results;
        });
    }

    /**
     * ล็อกคู่ binary ของออเดอร์อื่นที่อาจใช้ PV ของออเดอร์นี้ (ผู้ที่ได้ PV ขาจากออเดอร์นี้ ตาม binary_leg_credits)
     * ลำดับ: แถวออเดอร์ต้นทาง (เรียง id) → แถวคู่ (เรียง id) — ลำดับเดียวกับ MlmCalculationService::payApprovedCommissions
     */
    protected function lockCrossOrderPairRows(Order $order): void
    {
        $alreadyReversed = MlmPvTransaction::where('order_id', $order->id)
            ->where('transaction_type', MlmPvTransaction::TYPE_REFUND)
            ->exists();
        if ($alreadyReversed) {
            return;
        }

        $rows = MlmPvTransaction::where('order_id', $order->id)
            ->where('transaction_type', 'purchase')
            ->where('pv_amount', '>', 0)
            ->get(['id', 'binary_leg_credits', 'created_at']);

        $memberIds = [];
        foreach ($rows as $row) {
            foreach ((array) ($row->binary_leg_credits ?? []) as $credit) {
                if (! empty($credit['m'])) {
                    $memberIds[(int) $credit['m']] = true;
                }
            }
        }
        if ($memberIds === []) {
            return;
        }

        $since = $rows->min('created_at');
        $candidates = fn () => MlmCommission::query()
            ->whereIn('mlm_member_id', array_keys($memberIds))
            ->where('type', 'binary_pair')
            ->whereIn('status', ['pending', 'approved', 'paid'])
            ->where(fn ($q) => $q->where('source_id', '!=', $order->id)->orWhereNotIn('source_type', [Order::class, 'Order']))
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since));

        $orderIds = $candidates()->where('source_type', Order::class)->pluck('source_id')->filter()->unique()->sort()->values()->all();
        if ($orderIds !== []) {
            Order::whereIn('id', $orderIds)->orderBy('id')->lockForUpdate()->get(['id']);
        }
        $candidates()->orderBy('id')->lockForUpdate()->get(['id']);
    }

    /**
     * หัก PV ของออเดอร์ที่คืนเงิน (audit F11, เจ้าของตัดสิน M6) — ทำครั้งเดียวต่อออเดอร์ (มีแถว refund แล้ว = ข้าม)
     *
     * - total_pv ของผู้ซื้อ: หักเท่าที่ซื้อ ไม่ต่ำกว่า 0 + เขียนแถว mlm_pv_transactions type refund (pv ติดลบ)
     * - PV รักษายอด: แถว purchase ของออเดอร์ที่มีแถว refund ไม่ถูกนับ (MlmPvTransaction::countsForRetention)
     *   และ last_purchase_at ย้อนเป็นการซื้อจริงล่าสุดที่ยังไม่คืนเงิน
     * - PV ขา binary + คู่ที่ใช้ PV ก้อนนี้: MlmBinaryService::reverseOrderLegPv แล้วดึงเงินคู่คืนที่นี่
     * - team PV: เส้นทางเงินจริง (MlmCommissionService) ไม่ได้บวก total_team_pv → ไม่มีอะไรให้หัก
     *
     * @param  \Illuminate\Support\Collection<int, MlmCommission>  $ownPairs
     */
    protected function reverseOrderPv(Order $order, $ownPairs, ?int $adminId, array &$results): array
    {
        $already = MlmPvTransaction::where('order_id', $order->id)
            ->where('transaction_type', MlmPvTransaction::TYPE_REFUND)
            ->exists();
        if ($already) {
            return ['status' => 'already_reversed'];
        }

        $purchases = MlmPvTransaction::where('order_id', $order->id)
            ->where('transaction_type', 'purchase')
            ->where('pv_amount', '>', 0)
            ->orderBy('id')
            ->get();

        $report = ['status' => 'reversed', 'members' => [], 'legs' => [], 'unrecovered_pv' => 0.0];
        $binary = new MlmBinaryService;
        $pendingOwnPairs = collect($ownPairs);

        foreach ($purchases as $row) {
            /** @var MlmMember|null $member */
            $member = MlmMember::whereKey($row->mlm_member_id)->lockForUpdate()->first();
            if (! $member) {
                continue;
            }

            $pv = round((float) $row->pv_amount, 2);
            $before = round((float) $member->total_pv, 2);
            $after = round(max(0.0, $before - $pv), 2);

            MlmPvTransaction::create([
                'mlm_member_id' => $member->id,
                'mlm_plan_id' => $row->mlm_plan_id,
                'transaction_type' => MlmPvTransaction::TYPE_REFUND,
                'order_id' => $order->id,
                'pv_amount' => -$pv,
                'sales_amount' => -round((float) $row->sales_amount, 2),
                'previous_balance' => $before,
                'new_balance' => $after,
                'attributed_leg' => $row->attributed_leg,
                'description' => "หัก PV คืนเงินออเดอร์ #{$order->order_number}",
                'created_by' => $adminId,
            ]);

            $member->total_pv = $after;
            // การซื้อที่ถูกคืนเงินเป็นการซื้อล่าสุด → ย้อนวันซื้อล่าสุดเป็นการซื้อจริงที่ยังไม่คืนเงิน (grace period รักษายอด)
            if ($member->last_purchase_at && $row->created_at && $member->last_purchase_at->gte($row->created_at->copy()->startOfSecond())) {
                $member->last_purchase_at = $member->pvTransactions()->countsForRetention()->max('created_at');
            }
            $member->save();

            $report['members'][] = ['member_id' => (int) $member->id, 'pv' => $pv, 'total_pv_before' => $before, 'total_pv_after' => $after];

            $legs = $binary->reverseOrderLegPv($member, $order, $pv, $row->binary_leg_credits, $pendingOwnPairs, $row->created_at);
            $pendingOwnPairs = collect();
            $report['legs'] = array_merge($report['legs'], $legs['legs']);
            $report['unrecovered_pv'] = round($report['unrecovered_pv'] + $legs['unrecovered_pv'], 2);

            foreach ($legs['pair_claws'] as $claw) {
                $this->clawBinaryPairPortion($claw, $order, $adminId, $results);
            }
        }

        // ไม่มีแถว PV (engine ถูกเรียกตรง) แต่มีคู่ของออเดอร์นี้ → คืน PV ที่คู่ใช้กลับเข้าขาอย่างเดียว
        if ($purchases->isEmpty() && $pendingOwnPairs->isNotEmpty()) {
            $buyer = MlmMember::where('user_id', $order->user_id)->first();
            if ($buyer) {
                $binary->reverseOrderLegPv($buyer, $order, 0.0, [], $pendingOwnPairs, null);
            }
            $report['status'] = 'pairs_restored_only';
        } elseif ($purchases->isEmpty()) {
            $report['status'] = 'no_pv';
        }

        return $report;
    }

    /**
     * ดึงเงินคู่ binary (ของออเดอร์อื่น) คืนตามสัดส่วนที่ PV ของออเดอร์ที่คืนเงินถูกใช้ไป
     *
     * ยังไม่จ่าย → ลดยอด (ครบ = ยกเลิก) · จ่ายแล้ว → หักกระเป๋าเท่าที่มี ส่วนที่ขาดเป็นหนี้ MlmClawback (ครบ = clawback)
     *
     * @param  array{commission: MlmCommission, amount: float, fully: bool, pv: float}  $claw
     */
    protected function clawBinaryPairPortion(array $claw, Order $order, ?int $adminId, array &$results): void
    {
        /** @var MlmCommission $row */
        $row = $claw['commission'];
        $amount = round((float) $claw['amount'], 2);
        $summary = [
            'commission_id' => $row->id,
            'user_id' => $row->user_id,
            'source_order_id' => $row->source_id,
            'pv' => $claw['pv'],
            'amount' => $amount,
            'fully' => $claw['fully'],
            'status_before' => $row->status,
            'deducted_from_wallet' => 0.0,
            'debt_id' => null,
        ];

        if (in_array($row->status, ['pending', 'approved'], true)) {
            $remaining = round(max(0.0, (float) $row->commission_amount - $amount), 2);
            if ($claw['fully'] || $remaining <= 0) {
                $row->update([
                    'status' => 'cancelled',
                    'rejection_reason' => "Clawback (binary PV refunded) - Order #{$order->id}",
                    'rejected_at' => now(),
                ]);
                $results['cancelled_pending'][] = $row->id;
            } else {
                $row->update(['commission_amount' => $remaining]);
            }
        } elseif ($row->status === 'paid' && $amount > 0 && $row->user_id) {
            $money = $this->processClawback((int) $row->user_id, $amount, $order, $adminId);
            $summary['deducted_from_wallet'] = $money['deducted_from_wallet'];
            $summary['debt_id'] = $money['debt_id'];

            if ($money['deducted_from_wallet'] > 0) {
                $results['deducted_from_wallets'][] = ['user_id' => (int) $row->user_id, 'amount' => $money['deducted_from_wallet']];
            }
            if ($money['debt_id']) {
                $results['debts_created'][] = $money['debt_id'];
            }

            $row->update([
                'status' => $claw['fully'] ? 'clawback' : 'paid',
                'notes' => trim((string) $row->notes.' | Binary clawback '.number_format($amount, 2)." (PV ของออเดอร์ #{$order->id} ถูกคืนเงิน)"),
            ]);
        }

        $results['total_clawback_amount'] += $amount;
        $results['binary_pair_clawbacks'][] = $summary;
    }

    /**
     * Clawback Commission ของ User คนเดียว
     *
     * @param  \Illuminate\Support\Collection  $commissions
     */
    protected function clawbackUserCommissions(
        int $userId,
        $commissions,
        Order $order,
        ?int $adminId
    ): array {
        $result = [
            'user_id' => $userId,
            'commission_count' => $commissions->count(),
            'clawback_amount' => 0,
            'deducted_from_wallet' => 0,
            'debt_created' => 0,
            'debt_id' => null,
            'cancelled_commission_ids' => [],
            // แถวที่ถูกยกเลิก/ดึงคืนในรอบนี้ (ใช้คืน PV ของคู่ binary ของออเดอร์นี้)
            'reversed_commission_ids' => [],
            'status' => 'processed',
        ];

        $pendingAmount = 0;
        $paidAmount = 0;

        foreach ($commissions as $commission) {
            // ตรวจสอบสถานะ Commission
            if (in_array($commission->status, ['pending', 'approved'])) {
                // ยังไม่จ่าย → ยกเลิกได้เลย
                $commission->update([
                    'status' => 'cancelled',
                    'rejection_reason' => "Clawback due to refund - Order #{$order->id}",
                    'rejected_at' => now(),
                ]);

                $result['cancelled_commission_ids'][] = $commission->id;
                $result['reversed_commission_ids'][] = $commission->id;
                $pendingAmount += $commission->commission_amount;

            } elseif ($commission->status === 'paid') {
                // จ่ายไปแล้ว → ต้องเรียกคืน (คู่ binary ที่เคยถูกดึงคืนบางส่วนจากการคืนเงินออเดอร์อื่น → ดึงเฉพาะที่เหลือ)
                // 🛡️ ยอดคงเหลือ = ยอดตั้งต้น − ดึงคืนแล้ว (สูตรเดียวกับทุกทาง ดู MlmCommission::remainingClawableAmount)
                $paidAmount += $commission->remainingClawableAmount();
                $result['reversed_commission_ids'][] = $commission->id;

                // อัพเดทสถานะเป็น clawback + จดว่าดึงคืนครบแล้ว
                $commission->markFullyClawedBack();
                $commission->forceFill([
                    'status' => 'clawback',
                    'notes' => "Clawback due to refund - Order #{$order->id}",
                ])->save();
            }
        }

        $result['clawback_amount'] = $pendingAmount + $paidAmount;

        // ถ้ามีเงินที่ต้องเรียกคืน (เคยจ่ายไปแล้ว)
        if ($paidAmount > 0) {
            $clawbackResult = $this->processClawback(
                $userId,
                $paidAmount,
                $order,
                $adminId
            );

            $result['deducted_from_wallet'] = $clawbackResult['deducted_from_wallet'];
            $result['debt_created'] = $clawbackResult['debt_amount'];
            $result['debt_id'] = $clawbackResult['debt_id'];
        }

        // ยกเลิก Earnings Ledger ที่เกี่ยวข้อง (ถ้ามี)
        $this->cancelRelatedEarnings($userId, $order);

        return $result;
    }

    /**
     * ประมวลผล Clawback - หักเงินหรือสร้างหนี้
     */
    protected function processClawback(
        int $userId,
        float $amount,
        Order $order,
        ?int $adminId
    ): array {
        $result = [
            'deducted_from_wallet' => 0,
            'debt_amount' => 0,
            'debt_id' => null,
        ];

        $user = User::withTrashed()->find($userId);
        if (! $user) {
            return $result;
        }

        $amount = round($amount, 2);

        // 🐛 (2026-09-25) เดิม decrement ยอดตรงๆ แล้ว insert wallet_transactions เองด้วย type 'mlm_clawback'
        //    (ไม่อยู่ใน enum) และไม่มี balance_before (NOT NULL) → QueryException ทุกครั้ง การคืนเงินล้มทั้งก้อน
        //    ตอนนี้หักผ่าน WalletService (lock + balance_before/after ครบ) เท่าที่มีเงิน ส่วนที่เหลือเป็นหนี้
        $deducted = $this->deductFromWallet($user, $amount, $order);
        $result['deducted_from_wallet'] = $deducted;

        $debtAmount = round($amount - $deducted, 2);
        if ($debtAmount > 0) {
            $debt = $this->createClawbackDebt($userId, $debtAmount, $order, $adminId);
            $result['debt_amount'] = $debtAmount;
            $result['debt_id'] = $debt->id;
        }

        return $result;
    }

    /**
     * หักเงินจาก Wallet เท่าที่มี (ไม่เกิน $amount)
     *
     * @return float จำนวนที่หักได้จริง
     */
    protected function deductFromWallet(User $user, float $amount, Order $order): float
    {
        return $this->deductWalletUpTo(
            $user,
            $amount,
            "หักคืนคอมมิชชัน MLM — คืนเงินออเดอร์ #{$order->order_number}",
            'MlmCommissionClawback',
            (int) $order->id,
            ['order_number' => $order->order_number]
        );
    }

    /**
     * หักเงินจากกระเป๋าเท่าที่มี (ไม่เกิน $amount และไม่ทำให้ติดลบ) ผ่าน WalletService
     *
     * @return float จำนวนที่หักได้จริง
     */
    protected function deductWalletUpTo(User $user, float $amount, string $description, string $refType, int $refId, array $meta = []): float
    {
        $walletService = app(WalletService::class);
        $wallet = $walletService->getOrCreateWallet($user);

        if (! $wallet->isActive()) {
            return 0.0;
        }

        $take = round(min((float) $wallet->fresh()->balance, $amount), 2);
        if ($take <= 0) {
            return 0.0;
        }

        try {
            $walletService->deductForService($wallet, $take, $description, $refType, $refId, $meta);
        } catch (\Throwable $e) {
            Log::warning('MLM clawback: wallet deduction failed, converting to debt', [
                'user_id' => $user->id,
                'amount' => $take,
                'error' => $e->getMessage(),
            ]);

            return 0.0;
        }

        Log::info('MLM Commission deducted from wallet', [
            'user_id' => $user->id,
            'amount' => $take,
            'reference' => [$refType, $refId],
        ]);

        return $take;
    }

    /**
     * สร้างหนี้ Clawback
     */
    protected function createClawbackDebt(
        int $userId,
        float $amount,
        Order $order,
        ?int $adminId
    ): WalletDebt {
        $debt = WalletDebt::createDebt(
            $userId,
            $amount,
            'MlmClawback',
            $order->id,
            "Clawback MLM Commission จาก Order #{$order->order_number} เนื่องจากการ Refund",
            $adminId,
            1, // Priority สูงสุด
            [
                'order_number' => $order->order_number,
                'clawback_type' => 'mlm_commission',
                'created_at' => now()->toIso8601String(),
            ]
        );

        // ส่ง Notification แจ้งหนี้ใหม่
        try {
            $user = User::find($userId);
            if ($user) {
                app(NotificationService::class)->notifyDebtCreated($user, $debt);
            }
        } catch (\Exception $e) {
            Log::warning('ส่ง Notification หนี้ MLM ล้มเหลว', ['debt_id' => $debt->id]);
        }

        return $debt;
    }

    /**
     * ยกเลิก Earnings ที่เกี่ยวข้อง
     */
    protected function cancelRelatedEarnings(int $userId, Order $order): void
    {
        // ยกเลิก earnings ที่ยัง pending หรือ available
        EarningsLedger::where('user_id', $userId)
            ->where('source_type', 'Order')
            ->where('source_id', $order->id)
            ->where('earning_type', 'mlm_commission')
            ->whereIn('status', [
                EarningsLedger::STATUS_PENDING,
                EarningsLedger::STATUS_AVAILABLE,
            ])
            ->update([
                'status' => EarningsLedger::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancel_reason' => "Order #{$order->order_number} refunded",
            ]);
    }

    /**
     * ดึงสถิติ Clawback
     */
    public function getClawbackStats(array $filters = []): array
    {
        $query = WalletDebt::where('source_type', 'MlmClawback');

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return [
            'total_clawbacks' => (clone $query)->count(),
            'total_amount' => (clone $query)->sum('original_amount'),
            'total_collected' => (clone $query)->sum('deducted_amount'),
            'total_pending' => (clone $query)->where('status', WalletDebt::STATUS_ACTIVE)->sum('remaining_amount'),
            'collection_rate' => $this->calculateCollectionRate($query),
            'by_status' => (clone $query)
                ->selectRaw('status, COUNT(*) as count, SUM(original_amount) as total')
                ->groupBy('status')
                ->get()
                ->keyBy('status')
                ->toArray(),
        ];
    }

    /**
     * คำนวณอัตราการเก็บเงิน
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function calculateCollectionRate($query): float
    {
        $totalOriginal = (clone $query)->sum('original_amount');
        $totalCollected = (clone $query)->sum('deducted_amount');

        if ($totalOriginal <= 0) {
            return 0;
        }

        return round(($totalCollected / $totalOriginal) * 100, 2);
    }

    /**
     * Clawback Commission เฉพาะ User (Manual)
     */
    public function clawbackSingleCommission(
        MlmCommission $commission,
        ?int $adminId = null,
        string $reason = 'Manual clawback'
    ): array {
        return DB::transaction(function () use ($commission, $adminId, $reason) {
            // 🔒 F13: ล็อกแถวแล้วอ่านสถานะล่าสุด — กันชนกับการกดจ่ายพร้อมกัน
            $commission = MlmCommission::whereKey($commission->id)->lockForUpdate()->firstOrFail();

            // 🛡️ ยอดที่ยังดึงคืนได้ = ยอดตั้งต้น − ที่ดึงคืนไปแล้ว (คู่ binary ที่ถูกดึงคืนบางส่วนจากการคืนเงินออเดอร์)
            //    เดิมสร้างหนี้เท่า commission_amount เต็ม → ดึงคืนเกินเงินที่จ่ายจริง
            $remaining = $commission->remainingClawableAmount();

            $result = [
                'commission_id' => $commission->id,
                'user_id' => $commission->user_id,
                'amount' => $remaining,
                'action' => 'cancelled',
                'deducted_from_wallet' => 0.0,
                'debt_id' => null,
            ];

            if (in_array($commission->status, ['pending', 'approved'])) {
                // ยังไม่จ่าย → ยกเลิกได้เลย
                $commission->update([
                    'status' => 'cancelled',
                    'rejection_reason' => $reason,
                    'rejected_at' => now(),
                ]);

            } elseif ($commission->status === 'paid' && $commission->user_id) {
                // จ่ายแล้ว → หักจากกระเป๋าก่อน (เท่าที่มี ไม่ติดลบ) ส่วนที่ขาดเป็นหนี้ — แบบเดียวกับการคืนเงินออเดอร์
                $commission->markFullyClawedBack();
                $commission->forceFill([
                    'status' => 'clawback',
                    'notes' => $reason,
                ])->save();
                $result['action'] = 'clawed_back';

                $user = User::withTrashed()->find($commission->user_id);
                $deducted = ($user && $remaining > 0)
                    ? $this->deductWalletUpTo(
                        $user,
                        $remaining,
                        "หักคืนคอมมิชชัน MLM #{$commission->id} — {$reason}",
                        'MlmCommissionManualClawback',
                        (int) $commission->id,
                        ['commission_id' => $commission->id]
                    )
                    : 0.0;
                $result['deducted_from_wallet'] = $deducted;

                // เงินที่หักคืนได้ → กลับกองทุน MLM (กองทุนเคยจ่ายก้อนนี้ออกไป)
                if ($deducted > 0) {
                    \App\Models\PlatformWallet::getMlmPoolWallet()->addFunds($deducted, 'mlm_clawback_return', 'MlmCommission', (int) $commission->id, [
                        'reason' => mb_substr($reason, 0, 255),
                    ]);
                }

                $debtAmount = round($remaining - $deducted, 2);
                if ($debtAmount > 0) {
                    $debt = WalletDebt::createDebt(
                        $commission->user_id,
                        $debtAmount,
                        'MlmClawback',
                        $commission->id,
                        $reason,
                        $adminId,
                        1,
                        ['commission_id' => $commission->id, 'partial_deducted' => $deducted]
                    );

                    $result['action'] = 'debt_created';
                    $result['debt_id'] = $debt->id;

                    // ส่ง Notification แจ้งหนี้ใหม่
                    try {
                        $debtUser = User::find($commission->user_id);
                        if ($debtUser) {
                            app(NotificationService::class)->notifyDebtCreated($debtUser, $debt);
                        }
                    } catch (\Exception $e) {
                        Log::warning('ส่ง Notification หนี้ MLM ล้มเหลว', ['debt_id' => $debt->id]);
                    }
                }
            }

            Log::info('Single MLM commission clawback', $result);

            return $result;
        });
    }

    /**
     * ดึงรายการ Clawback ทั้งหมด
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getClawbacks(array $filters = [], int $perPage = 20)
    {
        $query = WalletDebt::with(['user'])
            ->where('source_type', 'MlmClawback');

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->paginate($perPage);
    }
}
