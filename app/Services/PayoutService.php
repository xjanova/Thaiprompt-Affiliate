<?php

namespace App\Services;

use App\Models\EarningsLedger;
use App\Models\PayoutRequest;
use App\Models\PlatformWallet;
use App\Models\User;
use App\Models\WalletDebt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PayoutService
 *
 * จัดการการจ่ายเงินให้ผู้ขาย/Affiliate
 * รองรับ 4 โหมด: manual, auto_schedule, realtime, hybrid
 *
 * ⚠️ ยังไม่มีทางเข้า: ไม่มีโค้ดไหนในระบบสร้างแถว payout_requests
 *    `createPayoutRequest()` เป็นตัวเดียวที่สร้าง แต่เขียนทับ API ที่ไม่เคยมีจริง
 *    (`PayoutSetting::getSettingByType()` — ของจริงชื่อ `getByType()`) มาตั้งแต่
 *    commit c6e918bba "feat: add complete Platform Revenue System" และไม่มี route,
 *    controller หรือ command ไหนเรียกมันเลย จึงถอดออกใน stage 2 (2026-11-06)
 *    พร้อม helper ที่มีแต่มันเรียก (linkEarningsToPayout, calculatePayoutFee,
 *    calculateNextPayoutDate, resolvePayoutType)
 *
 *    ผลที่ตามมา: หน้า /admin/payouts, ปุ่มอนุมัติ/ปฏิเสธ/จ่าย, bulk-approve และ
 *    คำสั่ง payouts:process-scheduled ทำงานบนตารางที่ว่างเสมอ เงินจริงของผู้ขาย
 *    เดินผ่าน SellerPayoutService + WithdrawalService ไม่ได้ผ่านทางนี้
 *    ถ้าจะใช้ระบบนี้จริง ต้องเขียนตัวสร้างแถวใหม่ (ดู git show c6e918bba เป็นต้นแบบ)
 */
class PayoutService
{
    protected PlatformRevenueService $revenueService;

    public function __construct()
    {
        $this->revenueService = new PlatformRevenueService;
    }

    /**
     * อนุมัติ Payout Request
     *
     * @param  int  $approvedBy  User ID ของ Admin
     */
    public function approvePayout(PayoutRequest $payoutRequest, int $approvedBy, ?string $note = null): PayoutRequest
    {
        if ($payoutRequest->status !== PayoutRequest::STATUS_PENDING) {
            throw new \Exception('สามารถอนุมัติได้เฉพาะ request ที่รอดำเนินการ');
        }

        return DB::transaction(function () use ($payoutRequest, $approvedBy, $note) {
            // 🔒 (2026-10-13) lock + ตรวจสถานะซ้ำ — กดอนุมัติซ้ำ/อนุมัติชนกับปฏิเสธ ต้องจ่ายครั้งเดียว
            $locked = PayoutRequest::whereKey($payoutRequest->id)->lockForUpdate()->first();
            if (! $locked || $locked->status !== PayoutRequest::STATUS_PENDING) {
                throw new \Exception('สามารถอนุมัติได้เฉพาะ request ที่รอดำเนินการ');
            }
            $payoutRequest->setRawAttributes($locked->getAttributes(), true);

            $payoutRequest->update([
                'status' => PayoutRequest::STATUS_APPROVED,
                'approved_by' => $approvedBy,
                'approved_at' => now(),
                'approval_note' => $note,
            ]);

            Log::info('Payout approved', [
                'payout_request_id' => $payoutRequest->id,
                'approved_by' => $approvedBy,
            ]);

            // ประมวลผลจ่ายเงินทันที
            $this->processPayout($payoutRequest);

            return $payoutRequest->fresh();
        });
    }

    /**
     * ปฏิเสธ Payout Request
     */
    public function rejectPayout(PayoutRequest $payoutRequest, int $rejectedBy, string $reason): PayoutRequest
    {
        if (! in_array($payoutRequest->status, [PayoutRequest::STATUS_PENDING, PayoutRequest::STATUS_SCHEDULED])) {
            throw new \Exception('ไม่สามารถปฏิเสธ request นี้ได้');
        }

        return DB::transaction(function () use ($payoutRequest, $rejectedBy, $reason) {
            // 🔒 (2026-10-13) lock + ตรวจสถานะซ้ำ — ห้ามปฏิเสธ (คืนรายได้/คืนหนี้) ทับ request ที่เพิ่งถูกอนุมัติจ่าย
            $locked = PayoutRequest::whereKey($payoutRequest->id)->lockForUpdate()->first();
            if (! $locked || ! in_array($locked->status, [PayoutRequest::STATUS_PENDING, PayoutRequest::STATUS_SCHEDULED])) {
                throw new \Exception('ไม่สามารถปฏิเสธ request นี้ได้');
            }
            $payoutRequest->setRawAttributes($locked->getAttributes(), true);

            // คืนสถานะ earnings
            EarningsLedger::where('payout_request_id', $payoutRequest->id)
                ->update([
                    'payout_request_id' => null,
                    'status' => EarningsLedger::STATUS_AVAILABLE,
                ]);

            // คืนหนี้ที่หัก (ถ้ามี) → สร้าง WalletDebt ใหม่คืนให้ User
            if ($payoutRequest->debt_deduction > 0) {
                WalletDebt::create([
                    'user_id' => $payoutRequest->user_id,
                    'original_amount' => $payoutRequest->debt_deduction,
                    'deducted_amount' => 0,
                    'remaining_amount' => $payoutRequest->debt_deduction,
                    'reason' => 'คืนหนี้จาก Payout ที่ถูกปฏิเสธ #'.$payoutRequest->id,
                    'source_type' => 'PayoutRequest',
                    'source_id' => $payoutRequest->id,
                    'status' => 'active',
                    'priority' => 0,
                ]);

                Log::info('Debt reversed due to payout rejection', [
                    'payout_request_id' => $payoutRequest->id,
                    'user_id' => $payoutRequest->user_id,
                    'reversed_amount' => $payoutRequest->debt_deduction,
                ]);
            }

            $payoutRequest->update([
                'status' => PayoutRequest::STATUS_REJECTED,
                'rejected_by' => $rejectedBy,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            Log::info('Payout rejected', [
                'payout_request_id' => $payoutRequest->id,
                'rejected_by' => $rejectedBy,
                'reason' => $reason,
            ]);

            return $payoutRequest->fresh();
        });
    }

    /**
     * ประมวลผลจ่ายเงิน
     */
    public function processPayout(PayoutRequest $payoutRequest): PayoutRequest
    {
        if ($payoutRequest->status !== PayoutRequest::STATUS_APPROVED) {
            throw new \Exception('สามารถจ่ายได้เฉพาะ request ที่อนุมัติแล้ว');
        }

        return DB::transaction(function () use ($payoutRequest) {
            // ป้องกัน race condition: lock + recheck สถานะภายใน transaction
            $payoutRequest = PayoutRequest::lockForUpdate()->find($payoutRequest->id);
            if (! $payoutRequest || $payoutRequest->status !== PayoutRequest::STATUS_APPROVED) {
                Log::warning('PayoutService: processPayout ถูกเรียกซ้ำหรือสถานะเปลี่ยนแล้ว', [
                    'payout_id' => $payoutRequest->id ?? 'unknown',
                    'current_status' => $payoutRequest->status ?? 'not found',
                ]);

                return $payoutRequest;
            }

            $payoutRequest->update([
                'status' => PayoutRequest::STATUS_PROCESSING,
            ]);

            try {
                // ดึงเงินจาก Platform Wallet ที่เหมาะสม
                // earning_type เป็นคอลัมน์ nullable แถวที่สร้างจากทางอื่นอาจไม่มีค่า
                // ต้อง fallback เป็นสตริงว่าง ไม่งั้น TypeError เพราะ getSourceWallet() รับ string
                $walletSlug = $this->getSourceWallet($payoutRequest->earning_type ?? '');
                $wallet = PlatformWallet::where('slug', $walletSlug)->first();

                if (! $wallet) {
                    throw new \Exception("ไม่พบ Platform Wallet: {$walletSlug}");
                }

                // หักเงินจาก Platform Wallet
                // 🐛 (2026-09-25) เดิมส่ง description เป็น sourceType และ 'PayoutRequest' เข้า ?int $sourceId
                //    → TypeError ทุกครั้ง (catch \Exception จับไม่ได้) ตอนนี้เรียงตาม signature จริง
                $payoutTx = $wallet->deductFunds(
                    (float) $payoutRequest->net_amount,
                    'payout',
                    'PayoutRequest',
                    (int) $payoutRequest->id,
                    ['user_id' => $payoutRequest->user_id, 'earning_type' => $payoutRequest->earning_type]
                );
                $payoutTx->update([
                    'related_user_id' => $payoutRequest->user_id,
                    'description' => "จ่ายเงินให้ User #{$payoutRequest->user_id}",
                ]);

                // โอนเงินเข้า User Wallet
                $this->transferToUserWallet($payoutRequest);

                // อัพเดทสถานะ earnings
                EarningsLedger::where('payout_request_id', $payoutRequest->id)
                    ->update(['status' => EarningsLedger::STATUS_PAID]);

                // สร้าง transaction reference
                $transactionRef = 'PO'.date('Ymd').str_pad($payoutRequest->id, 8, '0', STR_PAD_LEFT);

                $payoutRequest->update([
                    'status' => PayoutRequest::STATUS_PAID,
                    'paid_at' => now(),
                    'payment_reference' => $transactionRef,
                ]);

                Log::info('Payout completed', [
                    'payout_request_id' => $payoutRequest->id,
                    'transaction_ref' => $transactionRef,
                    'amount' => $payoutRequest->net_amount,
                ]);

            } catch (\Exception $e) {
                $payoutRequest->update([
                    'status' => PayoutRequest::STATUS_FAILED,
                    'error_message' => $e->getMessage(),
                ]);

                Log::error('Payout failed', [
                    'payout_request_id' => $payoutRequest->id,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }

            return $payoutRequest->fresh();
        });
    }

    /**
     * ดึง slug ของ wallet ที่เป็นแหล่งเงิน
     */
    protected function getSourceWallet(string $earningType): string
    {
        return match ($earningType) {
            // เงินผู้ขายถูกพักไว้ที่ seller_escrow ตอนแบ่งเงินออเดอร์ (ไม่ใช่กระเป๋า fee ซึ่งเป็นรายได้แพลตฟอร์ม)
            EarningsLedger::TYPE_SELLER_SALE => SellerPayoutService::ESCROW_WALLET_SLUG,
            EarningsLedger::TYPE_MLM_COMMISSION,
            EarningsLedger::TYPE_AFFILIATE_COMMISSION => 'mlm_pool',
            default => 'fee',
        };
    }

    /**
     * โอนเงินเข้า User Wallet
     *
     * ใช้ WalletService เพื่อให้ audit trail, balance tracking,
     * total_income, และ last_transaction_at ถูกต้อง
     */
    protected function transferToUserWallet(PayoutRequest $payoutRequest): void
    {
        $user = User::find($payoutRequest->user_id);
        if (! $user) {
            throw new \Exception("ไม่พบ User #{$payoutRequest->user_id}");
        }

        // ใช้ WalletService เพื่อ consistency กับส่วนอื่นของระบบ
        $walletService = new WalletService;
        $wallet = $walletService->getOrCreateWallet($user);

        // ใช้ deposit() เพื่อให้ balance_before/after, total_income, log ถูกต้อง
        $walletService->deposit(
            $wallet,
            $payoutRequest->net_amount,
            "รับเงินจากการถอน - {$payoutRequest->earning_type}",
            'PayoutRequest',
            $payoutRequest->id,
            ['payout_request_id' => $payoutRequest->id, 'earning_type' => $payoutRequest->earning_type]
        );
    }

    /**
     * ประมวลผล Scheduled Payouts
     * สำหรับ CRON job
     */
    public function processScheduledPayouts(): array
    {
        $results = [
            'processed' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        // ดึง payouts ที่ถึงกำหนด
        $scheduledPayouts = PayoutRequest::where('status', PayoutRequest::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($scheduledPayouts as $payout) {
            try {
                // อัพเดทเป็น approved
                $payout->update([
                    'status' => PayoutRequest::STATUS_APPROVED,
                    'approved_at' => now(),
                    'approval_note' => 'อนุมัติอัตโนมัติโดยระบบตั้งเวลา',
                ]);

                // ประมวลผลจ่ายเงิน
                $this->processPayout($payout);
                $results['processed']++;

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'payout_id' => $payout->id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Scheduled payout failed', [
                    'payout_id' => $payout->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * ยกเลิก Payout Request
     */
    public function cancelPayout(PayoutRequest $payoutRequest, int $cancelledBy, string $reason): PayoutRequest
    {
        if (! in_array($payoutRequest->status, [PayoutRequest::STATUS_PENDING, PayoutRequest::STATUS_SCHEDULED])) {
            throw new \Exception('ไม่สามารถยกเลิก request นี้ได้');
        }

        return DB::transaction(function () use ($payoutRequest, $cancelledBy, $reason) {
            // คืนสถานะ earnings
            EarningsLedger::where('payout_request_id', $payoutRequest->id)
                ->update([
                    'payout_request_id' => null,
                    'status' => EarningsLedger::STATUS_AVAILABLE,
                ]);

            $payoutRequest->update([
                'status' => PayoutRequest::STATUS_CANCELLED,
                'metadata' => array_merge($payoutRequest->metadata ?? [], [
                    'cancelled_by' => $cancelledBy,
                    'cancelled_at' => now()->toIso8601String(),
                    'cancel_reason' => $reason,
                ]),
            ]);

            Log::info('Payout cancelled', [
                'payout_request_id' => $payoutRequest->id,
                'cancelled_by' => $cancelledBy,
            ]);

            return $payoutRequest->fresh();
        });
    }

    /**
     * ดึงสถิติ Payout
     */
    public function getPayoutStats(array $filters = []): array
    {
        $query = PayoutRequest::query();

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['earning_type'])) {
            $query->where('earning_type', $filters['earning_type']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return [
            'total_requests' => (clone $query)->count(),
            'total_gross_amount' => (clone $query)->sum('amount'),
            'total_fee_amount' => (clone $query)->sum('fee'),
            'total_net_amount' => (clone $query)->sum('net_amount'),
            'by_status' => (clone $query)->selectRaw('status, COUNT(*) as count, SUM(net_amount) as total')
                ->groupBy('status')
                ->get()
                ->keyBy('status')
                ->toArray(),
            'pending_count' => (clone $query)->where('status', PayoutRequest::STATUS_PENDING)->count(),
            'pending_amount' => (clone $query)->where('status', PayoutRequest::STATUS_PENDING)->sum('net_amount'),
            'completed_count' => (clone $query)->where('status', PayoutRequest::STATUS_PAID)->count(),
            'completed_amount' => (clone $query)->where('status', PayoutRequest::STATUS_PAID)->sum('net_amount'),
        ];
    }

    /**
     * ดึงรายการ Payout Requests
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPayoutRequests(array $filters = [], int $perPage = 15)
    {
        $query = PayoutRequest::with(['user', 'payoutSetting', 'approvedByUser']);

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['earning_type'])) {
            $query->where('earning_type', $filters['earning_type']);
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
