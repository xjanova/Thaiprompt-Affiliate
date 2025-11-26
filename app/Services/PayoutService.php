<?php

namespace App\Services;

use App\Models\User;
use App\Models\EarningsLedger;
use App\Models\PayoutRequest;
use App\Models\PayoutSetting;
use App\Models\WalletDebt;
use App\Models\PlatformWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * PayoutService
 *
 * จัดการการจ่ายเงินให้ผู้ขาย/Affiliate
 * รองรับ 4 โหมด: manual, auto_schedule, realtime, hybrid
 */
class PayoutService
{
    protected PlatformRevenueService $revenueService;

    public function __construct()
    {
        $this->revenueService = new PlatformRevenueService();
    }

    /**
     * สร้าง Payout Request ใหม่
     *
     * @param int $userId
     * @param string $earningType seller_sale, mlm_commission, affiliate_commission
     * @param float|null $requestedAmount ถ้าไม่ระบุจะดึงทั้งหมดที่ available
     * @return PayoutRequest
     */
    public function createPayoutRequest(int $userId, string $earningType, ?float $requestedAmount = null): PayoutRequest
    {
        return DB::transaction(function () use ($userId, $earningType, $requestedAmount) {
            // ดึง setting สำหรับ earning type นี้
            $setting = PayoutSetting::getSettingByType($earningType);
            if (!$setting || !$setting->is_active) {
                throw new \Exception('Payout สำหรับประเภทนี้ถูกปิดใช้งาน');
            }

            // ดึงรายได้ที่ available
            $availableEarnings = EarningsLedger::where('user_id', $userId)
                ->where('earning_type', $earningType)
                ->available()
                ->get();

            $totalAvailable = $availableEarnings->sum('net_amount');

            if ($totalAvailable <= 0) {
                throw new \Exception('ไม่มียอดเงินที่ถอนได้');
            }

            // ตรวจสอบยอดขั้นต่ำ
            if ($totalAvailable < $setting->min_payout) {
                throw new \Exception("ยอดขั้นต่ำในการถอนคือ ฿{$setting->min_payout}");
            }

            // คำนวณยอดที่จะถอน
            $payoutAmount = $requestedAmount ?? $totalAvailable;

            if ($payoutAmount > $totalAvailable) {
                throw new \Exception("ยอดที่ขอถอน (฿{$payoutAmount}) มากกว่ายอดที่ถอนได้ (฿{$totalAvailable})");
            }

            if ($payoutAmount < $setting->min_payout) {
                throw new \Exception("ยอดขั้นต่ำในการถอนคือ ฿{$setting->min_payout}");
            }

            // ตรวจสอบยอดสูงสุด
            if ($setting->max_payout && $payoutAmount > $setting->max_payout) {
                throw new \Exception("ยอดสูงสุดในการถอนคือ ฿{$setting->max_payout}");
            }

            // คำนวณค่าธรรมเนียม
            $fee = $this->calculatePayoutFee($payoutAmount, $setting);
            $netAmount = $payoutAmount - $fee;

            // หักหนี้ (ถ้ามี)
            $debtDeduction = 0;
            $totalDebt = WalletDebt::getTotalDebtForUser($userId);
            if ($totalDebt > 0) {
                $maxDeduction = min($totalDebt, $netAmount * 0.5); // หักได้สูงสุด 50% ของยอด
                $debtResult = WalletDebt::deductAllDebts($userId, $maxDeduction);
                $debtDeduction = $debtResult['total_deducted'];
                $netAmount -= $debtDeduction;
            }

            // กำหนด status ตามโหมด
            $status = PayoutRequest::STATUS_PENDING;
            $scheduledAt = null;

            if ($setting->payout_mode === 'auto_schedule') {
                // คำนวณวันจ่ายถัดไป
                $scheduledAt = $this->calculateNextPayoutDate($setting);
                $status = PayoutRequest::STATUS_SCHEDULED;
            } elseif ($setting->payout_mode === 'realtime' && !$setting->requires_approval) {
                $status = PayoutRequest::STATUS_APPROVED;
            }

            // สร้าง payout request
            $payoutRequest = PayoutRequest::create([
                'user_id' => $userId,
                'payout_setting_id' => $setting->id,
                'earning_type' => $earningType,
                'gross_amount' => $payoutAmount,
                'fee_amount' => $fee,
                'debt_deduction' => $debtDeduction,
                'net_amount' => $netAmount,
                'status' => $status,
                'payment_method' => 'bank_transfer', // default, อาจ expand ได้
                'scheduled_at' => $scheduledAt,
            ]);

            // Link earnings to payout request
            $this->linkEarningsToPayout($availableEarnings, $payoutRequest, $payoutAmount);

            // ถ้า realtime และไม่ต้อง approve → ประมวลผลทันที
            if ($setting->payout_mode === 'realtime' && !$setting->requires_approval) {
                $this->processPayout($payoutRequest);
            }

            Log::info('Payout request created', [
                'payout_request_id' => $payoutRequest->id,
                'user_id' => $userId,
                'amount' => $payoutAmount,
                'status' => $status,
            ]);

            return $payoutRequest;
        });
    }

    /**
     * Link earnings ไปยัง payout request
     *
     * @param \Illuminate\Support\Collection $earnings
     * @param PayoutRequest $payoutRequest
     * @param float $targetAmount
     */
    protected function linkEarningsToPayout($earnings, PayoutRequest $payoutRequest, float $targetAmount): void
    {
        $linkedAmount = 0;
        $linkedEarningIds = [];

        foreach ($earnings as $earning) {
            if ($linkedAmount >= $targetAmount) {
                break;
            }

            $earning->payout_request_id = $payoutRequest->id;
            $earning->status = EarningsLedger::STATUS_PROCESSING;
            $earning->save();

            $linkedAmount += $earning->net_amount;
            $linkedEarningIds[] = $earning->id;
        }

        $payoutRequest->metadata = array_merge($payoutRequest->metadata ?? [], [
            'linked_earning_ids' => $linkedEarningIds,
        ]);
        $payoutRequest->save();
    }

    /**
     * คำนวณค่าธรรมเนียมการถอน
     *
     * @param float $amount
     * @param PayoutSetting $setting
     * @return float
     */
    protected function calculatePayoutFee(float $amount, PayoutSetting $setting): float
    {
        $fee = 0;

        // ค่าธรรมเนียมขั้นต่ำ
        if ($setting->fee_fixed > 0) {
            $fee += $setting->fee_fixed;
        }

        // ค่าธรรมเนียมเป็น %
        if ($setting->fee_percentage > 0) {
            $fee += $amount * ($setting->fee_percentage / 100);
        }

        return round($fee, 4);
    }

    /**
     * คำนวณวันจ่ายถัดไป
     *
     * @param PayoutSetting $setting
     * @return Carbon
     */
    protected function calculateNextPayoutDate(PayoutSetting $setting): Carbon
    {
        $schedule = $setting->schedule;
        $now = now();

        if (!$schedule) {
            return $now->addDay(); // default: วันถัดไป
        }

        // รองรับหลายรูปแบบ
        if (isset($schedule['type'])) {
            switch ($schedule['type']) {
                case 'daily':
                    return $now->addDay()->setTime($schedule['hour'] ?? 9, 0);

                case 'weekly':
                    $dayOfWeek = $schedule['day_of_week'] ?? 1; // 1 = Monday
                    $next = $now->copy()->next($dayOfWeek);
                    return $next->setTime($schedule['hour'] ?? 9, 0);

                case 'biweekly':
                    // ทุก 15 และสิ้นเดือน
                    if ($now->day < 15) {
                        return $now->copy()->day(15)->setTime($schedule['hour'] ?? 9, 0);
                    } else {
                        return $now->copy()->endOfMonth()->setTime($schedule['hour'] ?? 9, 0);
                    }

                case 'monthly':
                    $dayOfMonth = $schedule['day_of_month'] ?? 1;
                    $next = $now->copy()->addMonth()->day(min($dayOfMonth, $now->copy()->addMonth()->daysInMonth));
                    return $next->setTime($schedule['hour'] ?? 9, 0);

                case 'custom_days':
                    // กำหนดวันที่เฉพาะ เช่น [1, 15, 28]
                    $days = $schedule['days'] ?? [1, 15];
                    sort($days);
                    foreach ($days as $day) {
                        if ($day > $now->day) {
                            return $now->copy()->day($day)->setTime($schedule['hour'] ?? 9, 0);
                        }
                    }
                    // ถ้าเลยทุกวันแล้ว → วันแรกเดือนหน้า
                    return $now->copy()->addMonth()->day($days[0])->setTime($schedule['hour'] ?? 9, 0);
            }
        }

        return $now->addDay();
    }

    /**
     * อนุมัติ Payout Request
     *
     * @param PayoutRequest $payoutRequest
     * @param int $approvedBy User ID ของ Admin
     * @param string|null $note
     * @return PayoutRequest
     */
    public function approvePayout(PayoutRequest $payoutRequest, int $approvedBy, ?string $note = null): PayoutRequest
    {
        if ($payoutRequest->status !== PayoutRequest::STATUS_PENDING) {
            throw new \Exception('สามารถอนุมัติได้เฉพาะ request ที่รอดำเนินการ');
        }

        return DB::transaction(function () use ($payoutRequest, $approvedBy, $note) {
            $payoutRequest->update([
                'status' => PayoutRequest::STATUS_APPROVED,
                'approved_by' => $approvedBy,
                'approved_at' => now(),
                'admin_note' => $note,
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
     *
     * @param PayoutRequest $payoutRequest
     * @param int $rejectedBy
     * @param string $reason
     * @return PayoutRequest
     */
    public function rejectPayout(PayoutRequest $payoutRequest, int $rejectedBy, string $reason): PayoutRequest
    {
        if (!in_array($payoutRequest->status, [PayoutRequest::STATUS_PENDING, PayoutRequest::STATUS_SCHEDULED])) {
            throw new \Exception('ไม่สามารถปฏิเสธ request นี้ได้');
        }

        return DB::transaction(function () use ($payoutRequest, $rejectedBy, $reason) {
            // คืนสถานะ earnings
            EarningsLedger::where('payout_request_id', $payoutRequest->id)
                ->update([
                    'payout_request_id' => null,
                    'status' => EarningsLedger::STATUS_AVAILABLE,
                ]);

            // คืนหนี้ที่หัก (ถ้ามี)
            if ($payoutRequest->debt_deduction > 0) {
                // TODO: implement debt reversal if needed
            }

            $payoutRequest->update([
                'status' => PayoutRequest::STATUS_REJECTED,
                'rejected_by' => $rejectedBy,
                'rejected_at' => now(),
                'reject_reason' => $reason,
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
     *
     * @param PayoutRequest $payoutRequest
     * @return PayoutRequest
     */
    public function processPayout(PayoutRequest $payoutRequest): PayoutRequest
    {
        if ($payoutRequest->status !== PayoutRequest::STATUS_APPROVED) {
            throw new \Exception('สามารถจ่ายได้เฉพาะ request ที่อนุมัติแล้ว');
        }

        return DB::transaction(function () use ($payoutRequest) {
            $payoutRequest->update([
                'status' => PayoutRequest::STATUS_PROCESSING,
            ]);

            try {
                // ดึงเงินจาก Platform Wallet ที่เหมาะสม
                $walletSlug = $this->getSourceWallet($payoutRequest->earning_type);
                $wallet = PlatformWallet::where('slug', $walletSlug)->first();

                if (!$wallet) {
                    throw new \Exception("ไม่พบ Platform Wallet: {$walletSlug}");
                }

                // หักเงินจาก Platform Wallet
                $wallet->deductFunds(
                    $payoutRequest->net_amount,
                    'payout',
                    "จ่ายเงินให้ User #{$payoutRequest->user_id}",
                    'PayoutRequest',
                    $payoutRequest->id
                );

                // โอนเงินเข้า User Wallet
                $this->transferToUserWallet($payoutRequest);

                // อัพเดทสถานะ earnings
                EarningsLedger::where('payout_request_id', $payoutRequest->id)
                    ->update(['status' => EarningsLedger::STATUS_PAID]);

                // สร้าง transaction reference
                $transactionRef = 'PO' . date('Ymd') . str_pad($payoutRequest->id, 8, '0', STR_PAD_LEFT);

                $payoutRequest->update([
                    'status' => PayoutRequest::STATUS_COMPLETED,
                    'processed_at' => now(),
                    'transaction_ref' => $transactionRef,
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
     *
     * @param string $earningType
     * @return string
     */
    protected function getSourceWallet(string $earningType): string
    {
        return match ($earningType) {
            EarningsLedger::TYPE_SELLER_SALE => 'fee', // จ่ายจากกองทุนค่า Fee
            EarningsLedger::TYPE_MLM_COMMISSION,
            EarningsLedger::TYPE_AFFILIATE_COMMISSION => 'mlm_pool',
            default => 'fee',
        };
    }

    /**
     * โอนเงินเข้า User Wallet
     *
     * @param PayoutRequest $payoutRequest
     */
    protected function transferToUserWallet(PayoutRequest $payoutRequest): void
    {
        $user = User::find($payoutRequest->user_id);
        if (!$user) {
            throw new \Exception("ไม่พบ User #{$payoutRequest->user_id}");
        }

        // ตรวจสอบว่า User มี Wallet หรือไม่
        $wallet = $user->wallet;
        if (!$wallet) {
            // สร้าง wallet ใหม่ถ้ายังไม่มี
            $wallet = $user->wallet()->create([
                'balance' => 0,
                'currency' => 'THB',
            ]);
        }

        // เพิ่มเงินเข้า wallet
        $wallet->increment('balance', $payoutRequest->net_amount);

        // สร้าง transaction record
        if (method_exists($wallet, 'transactions')) {
            $wallet->transactions()->create([
                'user_id' => $user->id,
                'type' => 'payout',
                'amount' => $payoutRequest->net_amount,
                'balance_after' => $wallet->fresh()->balance,
                'description' => "รับเงินจากการถอน - {$payoutRequest->earning_type}",
                'reference_type' => 'PayoutRequest',
                'reference_id' => $payoutRequest->id,
            ]);
        }
    }

    /**
     * ประมวลผล Scheduled Payouts
     * สำหรับ CRON job
     *
     * @return array
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
                    'admin_note' => 'Auto-approved by scheduler',
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
     *
     * @param PayoutRequest $payoutRequest
     * @param int $cancelledBy
     * @param string $reason
     * @return PayoutRequest
     */
    public function cancelPayout(PayoutRequest $payoutRequest, int $cancelledBy, string $reason): PayoutRequest
    {
        if (!in_array($payoutRequest->status, [PayoutRequest::STATUS_PENDING, PayoutRequest::STATUS_SCHEDULED])) {
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
     *
     * @param array $filters
     * @return array
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
            'total_gross_amount' => (clone $query)->sum('gross_amount'),
            'total_fee_amount' => (clone $query)->sum('fee_amount'),
            'total_net_amount' => (clone $query)->sum('net_amount'),
            'by_status' => (clone $query)->selectRaw('status, COUNT(*) as count, SUM(net_amount) as total')
                ->groupBy('status')
                ->get()
                ->keyBy('status')
                ->toArray(),
            'pending_count' => (clone $query)->where('status', PayoutRequest::STATUS_PENDING)->count(),
            'pending_amount' => (clone $query)->where('status', PayoutRequest::STATUS_PENDING)->sum('net_amount'),
            'completed_count' => (clone $query)->where('status', PayoutRequest::STATUS_COMPLETED)->count(),
            'completed_amount' => (clone $query)->where('status', PayoutRequest::STATUS_COMPLETED)->sum('net_amount'),
        ];
    }

    /**
     * ดึงรายการ Payout Requests
     *
     * @param array $filters
     * @param int $perPage
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
