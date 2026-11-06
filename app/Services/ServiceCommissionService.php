<?php

namespace App\Services;

use App\Models\MlmCommission;
use App\Models\MlmMember;
use App\Models\MlmPvTransaction;
use App\Models\ServiceBooking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ServiceCommissionService
 *
 * บริการคำนวณและแจกจ่ายคอมมิชชั่นและ PV สำหรับ Service Booking
 * รวมถึงการจ่ายเงินให้ Provider และระบบ MLM
 *
 * 🛡️ audit F20 (ขั้น 2f) — เดิมมี 3 ชั้นที่บังกันอยู่:
 *    1. ส่ง User ไปที่ WalletService::deposit() ที่ต้องเป็น Wallet → TypeError ผู้ให้บริการไม่ได้เงิน booking ค้าง in_progress
 *    2. increment คอลัมน์ mlm_members.personal_pv / users.total_pv ที่ไม่มีจริง → คอม MLM ล้มเงียบ
 *    3. จบงานซ้ำไม่มี state guard + source_type ของคอมเป็น null → จ่าย 2 รอบโดยตัวกันซ้ำมองไม่เห็น
 *    แก้พร้อมกันทั้ง 3 ชั้น (state guard อยู่ที่ ServiceBooking::completeService + ล็อกแถวใน ServiceBookingService)
 *
 * กติกาเงิน MLM ของการจองบริการ (ตามออเดอร์สินค้า F7 / M3 / M4 / M10):
 * - PV = services.pv_value เท่านั้น (0 = ไม่มี PV/คอม — ไม่ fallback เป็น total_amount เพราะไม่มีเงินกองรองรับ)
 * - budget = min(PV × อัตรา × max %, เงินที่แพลตฟอร์มเก็บไว้จริง) — เงินที่เก็บไว้ = ยอดจ่าย − VAT − เงินผู้ให้บริการ − cashback
 * - budget ที่ใช้ถูกเก็บเข้ากระเป๋า mlm_pool (แบบออเดอร์) → คอมที่อนุมัติแล้วมีเงินรองรับ
 * - คอมทุกแถวเป็น pending รออนุมัติเอง (M10) · source_type = ServiceBooking
 */
class ServiceCommissionService
{
    /** source_type ของ platform_transactions (แบบเดียวกับ 'Order' ของออเดอร์) */
    public const POOL_SOURCE_TYPE = 'ServiceBooking';

    public function __construct(
        protected WalletService $walletService,
        protected MlmCommissionService $mlmCommissionService
    ) {}

    /**
     * ประมวลผลการจ่ายเงินและคอมมิชชั่นเมื่อบริการเสร็จสิ้น
     *
     * เรียกครั้งเดียวต่อการจอง (ServiceBookingService::completeService ล็อกแถว + เช็คสถานะก่อนเรียก)
     *
     * @return array ผลลัพธ์การประมวลผล
     */
    public function processCompletedBooking(ServiceBooking $booking): array
    {
        return DB::transaction(function () use ($booking) {
            $results = [
                'provider_payment' => null,
                'customer_cashback' => null,
                'mlm_commission' => null,
                'provider_pv' => null,
                'referrer_commission' => null,
            ];

            // 1. จ่ายเงินให้ Provider (ล้ม = โยนต่อ → ทั้งงานย้อนกลับ booking ไม่ถูกปิดโดยที่ผู้ให้บริการไม่ได้เงิน)
            $results['provider_payment'] = $this->payProvider($booking);

            // 2. จ่าย Cashback ให้ลูกค้า (ถ้ามี)
            if ($booking->service && $booking->service->cashback_percentage > 0) {
                $results['customer_cashback'] = $this->payCustomerCashback($booking);
            }

            // เงินที่แพลตฟอร์มเก็บไว้จริงจากการจองนี้ → เพดานเงินคอม MLM ทั้งหมดของการจอง
            $retained = $this->retainedAmount(
                $booking,
                (float) ($results['provider_payment']['amount'] ?? 0),
                (float) ($results['customer_cashback']['amount'] ?? 0)
            );

            // 3. คอม MLM ฝั่งลูกค้า (PV ของบริการ)
            $results['mlm_commission'] = $this->distributeMlmCommission($booking, $retained);
            $remaining = round(max(0.0, $retained - (float) ($results['mlm_commission']['budget'] ?? 0)), 2);

            // 4. PV ของ Provider (ค่า PV/การตลาดที่ผู้ให้บริการออก) — ใช้ได้เฉพาะเงินที่เหลือหลังข้อ 3
            if ($booking->provider_pv_amount > 0) {
                $results['provider_pv'] = $this->distributeProviderPV($booking, $remaining);
            }

            // 5. ค่าแนะนำบริการแบบเดิม (ปิด — ดู payReferrerCommission)
            $results['referrer_commission'] = $this->payReferrerCommission($booking);

            // บันทึก log
            Log::info('Service booking commission processed', [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'retained' => $retained,
                'results' => $results,
            ]);

            return $results;
        });
    }

    /**
     * เงินที่แพลตฟอร์มเก็บไว้จริง = ยอดที่ลูกค้าจ่าย − VAT − เงินผู้ให้บริการ − cashback (ไม่ติดลบ)
     */
    protected function retainedAmount(ServiceBooking $booking, float $providerPaid, float $cashbackPaid): float
    {
        $paid = (float) ($booking->metadata['paid_amount'] ?? $booking->total_amount);

        return round(max(0.0, $paid - (float) $booking->tax_amount - $providerPaid - $cashbackPaid), 2);
    }

    /**
     * จ่ายเงินให้ Provider
     */
    protected function payProvider(ServiceBooking $booking): ?array
    {
        if (! $booking->provider) {
            return null;
        }

        $providerUser = $booking->provider->user;
        if (! $providerUser) {
            Log::warning('Provider user not found', ['provider_id' => $booking->provider->id]);

            return null;
        }

        // ใช้ค่า provider_earnings ที่คำนวณไว้แล้ว (snapshot)
        $providerEarnings = round((float) $booking->provider_earnings, 2);

        if ($providerEarnings <= 0) {
            return null;
        }

        try {
            // 🛡️ F20 ชั้น 1: deposit() รับ Wallet (เดิมส่ง User → TypeError ที่ catch(\Exception) จับไม่ได้)
            $wallet = $this->walletService->getOrCreateWallet($providerUser);
            $transaction = $this->walletService->deposit(
                $wallet,
                $providerEarnings,
                "รายได้จากบริการ #{$booking->booking_number}",
                'service_booking',
                $booking->id,
                [
                    'booking_id' => $booking->id,
                    'service_id' => $booking->service_id,
                    'total_amount' => $booking->total_amount,
                    'platform_fee' => $booking->platform_fee_amount,
                    'pv_deducted' => $booking->provider_pv_amount,
                ],
                'deposit'
            );

            // 🧾 M7: ผู้ให้บริการมีหนี้ค้าง → หักจากรายได้งานนี้ก่อน (กระเป๋าไม่ติดลบ)
            $debtCollected = (new DebtCollectionService)->collectFromWalletIncome(
                $providerUser->id,
                $providerEarnings,
                'service_booking',
                $booking->id,
                "รายได้บริการ #{$booking->booking_number}"
            );

            // อัพเดทรายได้รวมของ provider
            $booking->provider->increment('total_earnings', $providerEarnings);

            return [
                'user_id' => $providerUser->id,
                'provider_id' => $booking->provider->id,
                'amount' => $providerEarnings,
                'debt_collected' => $debtCollected,
                'transaction_id' => $transaction->id ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('Failed to pay provider', [
                'booking_id' => $booking->id,
                'provider_id' => $booking->provider->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * จ่าย Cashback ให้ลูกค้า
     */
    protected function payCustomerCashback(ServiceBooking $booking): ?array
    {
        if (! $booking->service || $booking->service->cashback_percentage <= 0 || ! $booking->user) {
            return null;
        }

        $cashbackPercentage = $booking->service->cashback_percentage;
        $cashbackAmount = round(($booking->total_amount * $cashbackPercentage) / 100, 2);

        if ($cashbackAmount <= 0) {
            return null;
        }

        try {
            // 🛡️ F20 ชั้น 1: ส่ง Wallet (เดิมส่ง User → TypeError)
            $transaction = $this->walletService->deposit(
                $this->walletService->getOrCreateWallet($booking->user),
                $cashbackAmount,
                "Cashback จากบริการ #{$booking->booking_number}",
                'service_booking_cashback',
                $booking->id,
                [
                    'booking_id' => $booking->id,
                    'service_id' => $booking->service_id,
                    'cashback_percentage' => $cashbackPercentage,
                ],
                'bonus'
            );

            // 🧾 M7: หักหนี้ค้างจากเงินเข้าครั้งนี้
            (new DebtCollectionService)->collectFromWalletIncome(
                (int) $booking->user_id,
                $cashbackAmount,
                'service_booking_cashback',
                $booking->id,
                "Cashback บริการ #{$booking->booking_number}"
            );

            $booking->forceFill(['cashback_amount' => $cashbackAmount])->save();

            return [
                'user_id' => $booking->user_id,
                'amount' => $cashbackAmount,
                'percentage' => $cashbackPercentage,
                'transaction_id' => $transaction->id ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('Failed to pay cashback', [
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * แจกจ่าย PV สำหรับ Provider (สำหรับ MLM)
     *
     * @param  float  $availableMoney  เงินที่เหลือให้คอมฝั่งนี้ใช้ได้ (หลังหัก budget ฝั่งลูกค้า)
     */
    protected function distributeProviderPV(ServiceBooking $booking, float $availableMoney): ?array
    {
        if (! $booking->provider || $booking->provider_pv_amount <= 0) {
            return null;
        }

        $providerUser = $booking->provider->user;
        if (! $providerUser) {
            return null;
        }

        // หา MLM Member ของ Provider
        $mlmMember = MlmMember::where('user_id', $providerUser->id)->first();

        if (! $mlmMember) {
            // 🛡️ F20 ชั้น 2: ไม่ใช่สมาชิก MLM → ไม่มี PV (เดิม increment users.total_pv ที่ไม่มีจริง → booking จบไม่ได้)
            return [
                'user_id' => $providerUser->id,
                'pv_amount' => 0,
                'mlm_member' => false,
            ];
        }

        $pv = round((float) $booking->provider_pv_amount, 2);

        if ($this->hasCommissions($booking, $mlmMember)) {
            return null;
        }

        try {
            // PV ของผู้ให้บริการไม่ใช่การซื้อ → ไม่นับรักษายอด (transaction_type ไม่อยู่ใน RETENTION_TYPES)
            $this->recordServicePv($mlmMember, $booking, $pv, 'service_provider');

            $run = $this->runMlm($mlmMember, $booking, $pv, 'service_pv', $availableMoney);

            return [
                'user_id' => $providerUser->id,
                'mlm_member_id' => $mlmMember->id,
                'pv_amount' => $pv,
                'mlm_member' => true,
                'budget' => $run['budget'],
                'commission_result' => $run['result'],
            ];
        } catch (\Throwable $e) {
            Log::error('Failed to distribute provider PV', [
                'booking_id' => $booking->id,
                'provider_id' => $booking->provider->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * คำนวณและแจกจ่ายคอมมิชชั่น MLM สำหรับการจองบริการ
     *
     * @param  float  $availableMoney  เงินที่แพลตฟอร์มเก็บไว้จริงจากการจองนี้
     */
    protected function distributeMlmCommission(ServiceBooking $booking, float $availableMoney): ?array
    {
        // หา MLM Member ของลูกค้า
        $mlmMember = MlmMember::where('user_id', $booking->user_id)->first();

        if (! $mlmMember) {
            // ลูกค้าไม่ได้เป็นสมาชิก MLM
            return null;
        }

        // PV = pv_value ของบริการเท่านั้น (คอลัมน์ NOT NULL default 0 — เดิม `?? total_amount` ไม่เคยทำงาน
        // และถ้าทำงานก็จะสร้างคอมจากบริการที่ไม่ได้ตั้ง PV ไว้ ซึ่งไม่มีเงินกองรองรับ — M3)
        $pvValue = round((float) ($booking->service->pv_value ?? 0), 2);

        if ($pvValue <= 0) {
            return null;
        }

        // 🛡️ ตัวกันซ้ำชั้นที่ 2 (ชั้นแรกคือ state guard): การจองนี้มีคอมฝั่งลูกค้าแล้ว → ไม่คิดซ้ำ
        if ($this->hasCommissions($booking, $mlmMember)) {
            Log::warning('Service booking commissions already calculated, skipping', ['booking_id' => $booking->id]);

            return null;
        }

        try {
            // 🛡️ F20 ชั้น 2: บันทึก PV ผ่าน mlm_pv_transactions + total_pv (เดิม increment personal_pv ที่ไม่มีจริง)
            $this->recordServicePv($mlmMember, $booking, $pvValue, 'purchase');

            $run = $this->runMlm($mlmMember, $booking, $pvValue, 'service_booking', $availableMoney);

            // อัพเดทการจองด้วย mlm_member_id และยอดคอม
            $booking->update([
                'mlm_member_id' => $mlmMember->id,
                'commission_amount' => $run['result']['total_amount'] ?? 0,
                'mlm_pv_total' => $pvValue,
                'mlm_commission_total' => $run['budget'],
            ]);

            return [
                'user_id' => $booking->user_id,
                'mlm_member_id' => $mlmMember->id,
                'pv_value' => $pvValue,
                'budget' => $run['budget'],
                'commission_result' => $run['result'],
            ];
        } catch (\Throwable $e) {
            Log::error('Failed to distribute MLM commission', [
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * คำนวณคอม MLM ภายใน budget แล้วเก็บ budget เข้า mlm_pool (แบบออเดอร์สินค้า)
     *
     * @return array{budget: float, result: array|null}
     */
    protected function runMlm(MlmMember $member, ServiceBooking $booking, float $pv, string $transactionType, float $availableMoney): array
    {
        $budget = OverpayProtectionService::orderBudget($pv, round(max(0.0, $availableMoney), 2));

        if ($budget <= 0) {
            Log::info('Service booking MLM skipped: no commission budget', [
                'booking_id' => $booking->id,
                'pv' => $pv,
                'available_money' => $availableMoney,
            ]);

            return ['budget' => 0.0, 'result' => null];
        }

        $result = $this->mlmCommissionService->calculateCommissionsWithRollup(
            $member,
            $pv,
            $transactionType,
            $booking->id,
            false,
            $budget
        );

        if (! ($result['success'] ?? false)) {
            // คำนวณล้ม (rollback ภายในแล้ว) → ไม่เก็บเงินเข้ากอง
            Log::error('Service booking MLM calculation failed', [
                'booking_id' => $booking->id,
                'error' => $result['error'] ?? null,
            ]);

            return ['budget' => 0.0, 'result' => $result];
        }

        // เงินกองของการจองนี้เข้ากระเป๋า mlm_pool → คอมที่อนุมัติแล้วจ่ายจากเงินของการจองนี้ ไม่ใช่ของออเดอร์อื่น (F7)
        app(PlatformRevenueService::class)->collectMlmPool($budget, self::POOL_SOURCE_TYPE, (int) $booking->id, [
            'booking_number' => $booking->booking_number,
            'transaction_type' => $transactionType,
            'pv' => $pv,
            'available_money' => $availableMoney,
        ]);

        return ['budget' => $budget, 'result' => $result];
    }

    /**
     * บันทึก PV ของการจองบริการ (mlm_pv_transactions + mlm_members.total_pv)
     */
    protected function recordServicePv(MlmMember $member, ServiceBooking $booking, float $pv, string $transactionType): MlmPvTransaction
    {
        $member->refresh();
        $previous = (float) $member->total_pv;

        $row = MlmPvTransaction::create([
            'mlm_member_id' => $member->id,
            'mlm_plan_id' => $member->mlm_plan_id,
            'transaction_type' => $transactionType,
            'order_id' => null,
            'pv_amount' => $pv,
            'sales_amount' => (float) $booking->total_amount,
            'previous_balance' => $previous,
            'new_balance' => $previous + $pv,
            'attributed_leg' => 'personal',
            'binary_leg_credits' => [],
            'description' => "PV จากการจองบริการ #{$booking->booking_number}",
            'notes' => json_encode(['service_booking_id' => $booking->id], JSON_UNESCAPED_UNICODE),
        ]);

        $member->increment('total_pv', $pv);
        if ($transactionType === 'purchase') {
            $member->update(['last_purchase_at' => now()]);
        }

        return $row;
    }

    /**
     * การจองนี้มีคอมที่เกิดจาก PV ของสมาชิกคนนี้แล้วหรือยัง (ฝั่งลูกค้า/ฝั่งผู้ให้บริการแยกด้วย from_member_id)
     */
    protected function hasCommissions(ServiceBooking $booking, MlmMember $member): bool
    {
        return MlmCommission::where('source_type', ServiceBooking::class)
            ->where('source_id', $booking->id)
            ->where('from_member_id', $member->id)
            ->exists();
    }

    /**
     * ค่าแนะนำบริการแบบเดิม (5% ของยอด เข้ากระเป๋าผู้แนะนำทันที) — ปิดแล้ว
     *
     * 🛡️ F20: โค้ดเดิมอ่าน users.referred_by ซึ่งไม่มีคอลัมน์นี้จริง (จึงไม่เคยทำงาน) และถ้าทำงานจะ
     *    ฝากเงินเข้ากระเป๋าที่ถอนได้ทันที นอกการอนุมัติ (M10) และนอก budget ของการจอง (M3)
     *    ผู้แนะนำได้เงินจากการจองผ่าน unilevel ชั้น 1 ของ MLM อยู่แล้ว
     */
    protected function payReferrerCommission(ServiceBooking $booking): ?array
    {
        return null;
    }

    /**
     * คำนวณสรุปรายได้และค่าใช้จ่ายของการจอง
     */
    public function calculateBookingSummary(ServiceBooking $booking): array
    {
        $service = $booking->service;
        // PV = pv_value ของบริการเท่านั้น (ตรงกับ distributeMlmCommission)
        $pvValue = (float) ($service->pv_value ?? 0);
        $cashbackPercentage = $service->cashback_percentage ?? 0;
        $cashbackAmount = ($booking->total_amount * $cashbackPercentage) / 100;

        return [
            'total_amount' => (float) $booking->total_amount,
            'breakdown' => [
                'base_price' => (float) $booking->base_price,
                'distance_price' => (float) $booking->distance_price,
                'options_price' => (float) $booking->options_price,
                'tax_amount' => (float) $booking->tax_amount,
                'discount_amount' => (float) $booking->discount_amount,
            ],
            'platform' => [
                'platform_fee_percentage' => (float) $booking->platform_fee_percentage,
                'platform_fee_amount' => (float) $booking->platform_fee_amount,
                'system_revenue' => (float) $booking->system_revenue,
            ],
            'provider' => [
                'pv_percentage' => (float) $booking->provider_pv_percentage,
                'pv_amount' => (float) $booking->provider_pv_amount,
                'earnings' => (float) $booking->provider_earnings,
            ],
            'customer' => [
                'pv_value' => $pvValue,
                'cashback_percentage' => $cashbackPercentage,
                'cashback_amount' => $cashbackAmount,
            ],
        ];
    }
}
