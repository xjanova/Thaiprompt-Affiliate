<?php

namespace App\Services;

use App\Models\MlmMember;
use App\Models\ServiceBooking;
use App\Models\ServiceProvider;
use App\Models\User;
use App\Services\MlmCommissionService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ServiceCommissionService
 *
 * บริการคำนวณและแจกจ่ายคอมมิชชั่นและ PV สำหรับ Service Booking
 * รวมถึงการจ่ายเงินให้ Provider และระบบ MLM
 */
class ServiceCommissionService
{
    public function __construct(
        protected WalletService $walletService,
        protected MlmCommissionService $mlmCommissionService
    ) {
    }

    /**
     * ประมวลผลการจ่ายเงินและคอมมิชชั่นเมื่อบริการเสร็จสิ้น
     *
     * @param ServiceBooking $booking
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

            // 1. จ่ายเงินให้ Provider
            $results['provider_payment'] = $this->payProvider($booking);

            // 2. จ่าย Cashback ให้ลูกค้า (ถ้ามี)
            if ($booking->service && $booking->service->cashback_percentage > 0) {
                $results['customer_cashback'] = $this->payCustomerCashback($booking);
            }

            // 3. คำนวณและแจกจ่าย PV สำหรับ Provider
            if ($booking->provider_pv_amount > 0) {
                $results['provider_pv'] = $this->distributeProviderPV($booking);
            }

            // 4. คำนวณคอมมิชชั่น MLM (ถ้าลูกค้าเป็นสมาชิก MLM)
            $results['mlm_commission'] = $this->distributeMlmCommission($booking);

            // 5. จ่ายคอมมิชชั่นให้ผู้แนะนำ (ถ้ามี)
            $results['referrer_commission'] = $this->payReferrerCommission($booking);

            // บันทึก log
            Log::info('Service booking commission processed', [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'results' => $results,
            ]);

            return $results;
        });
    }

    /**
     * จ่ายเงินให้ Provider
     *
     * @param ServiceBooking $booking
     * @return array|null
     */
    protected function payProvider(ServiceBooking $booking): ?array
    {
        if (!$booking->provider) {
            return null;
        }

        $providerUser = $booking->provider->user;
        if (!$providerUser) {
            Log::warning('Provider user not found', ['provider_id' => $booking->provider->id]);
            return null;
        }

        // ใช้ค่า provider_earnings ที่คำนวณไว้แล้ว (snapshot)
        $providerEarnings = $booking->provider_earnings;

        if ($providerEarnings <= 0) {
            return null;
        }

        try {
            // เพิ่มเงินเข้า wallet ของ provider
            $transaction = $this->walletService->deposit(
                $providerUser,
                $providerEarnings,
                'service_income',
                "รายได้จากบริการ #{$booking->booking_number}",
                [
                    'booking_id' => $booking->id,
                    'service_id' => $booking->service_id,
                    'total_amount' => $booking->total_amount,
                    'platform_fee' => $booking->platform_fee_amount,
                    'pv_deducted' => $booking->provider_pv_amount,
                ]
            );

            // อัพเดทรายได้รวมของ provider
            $booking->provider->increment('total_earnings', $providerEarnings);

            return [
                'user_id' => $providerUser->id,
                'provider_id' => $booking->provider->id,
                'amount' => $providerEarnings,
                'transaction_id' => $transaction->id ?? null,
            ];
        } catch (\Exception $e) {
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
     *
     * @param ServiceBooking $booking
     * @return array|null
     */
    protected function payCustomerCashback(ServiceBooking $booking): ?array
    {
        if (!$booking->service || $booking->service->cashback_percentage <= 0) {
            return null;
        }

        $cashbackPercentage = $booking->service->cashback_percentage;
        $cashbackAmount = ($booking->total_amount * $cashbackPercentage) / 100;

        if ($cashbackAmount <= 0) {
            return null;
        }

        try {
            $transaction = $this->walletService->deposit(
                $booking->user,
                $cashbackAmount,
                'service_cashback',
                "Cashback จากบริการ #{$booking->booking_number}",
                [
                    'booking_id' => $booking->id,
                    'service_id' => $booking->service_id,
                    'cashback_percentage' => $cashbackPercentage,
                ]
            );

            return [
                'user_id' => $booking->user_id,
                'amount' => $cashbackAmount,
                'percentage' => $cashbackPercentage,
                'transaction_id' => $transaction->id ?? null,
            ];
        } catch (\Exception $e) {
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
     * @param ServiceBooking $booking
     * @return array|null
     */
    protected function distributeProviderPV(ServiceBooking $booking): ?array
    {
        if (!$booking->provider || $booking->provider_pv_amount <= 0) {
            return null;
        }

        $providerUser = $booking->provider->user;
        if (!$providerUser) {
            return null;
        }

        // หา MLM Member ของ Provider
        $mlmMember = MlmMember::where('user_id', $providerUser->id)->first();

        if (!$mlmMember) {
            // ถ้า provider ไม่ได้เป็นสมาชิก MLM ให้เพิ่ม PV เข้า user โดยตรง
            $providerUser->increment('total_pv', $booking->provider_pv_amount);

            return [
                'user_id' => $providerUser->id,
                'pv_amount' => $booking->provider_pv_amount,
                'mlm_member' => false,
            ];
        }

        try {
            // เพิ่ม PV ให้ MLM Member
            $mlmMember->increment('personal_pv', $booking->provider_pv_amount);
            $mlmMember->increment('total_pv', $booking->provider_pv_amount);

            // คำนวณคอมมิชชั่น MLM จาก PV ของ Provider
            $commissionResult = $this->mlmCommissionService->calculateCommissionsWithRollup(
                $mlmMember,
                $booking->provider_pv_amount,
                'service_pv',
                $booking->id
            );

            return [
                'user_id' => $providerUser->id,
                'mlm_member_id' => $mlmMember->id,
                'pv_amount' => $booking->provider_pv_amount,
                'mlm_member' => true,
                'commission_result' => $commissionResult,
            ];
        } catch (\Exception $e) {
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
     * @param ServiceBooking $booking
     * @return array|null
     */
    protected function distributeMlmCommission(ServiceBooking $booking): ?array
    {
        // หา MLM Member ของลูกค้า
        $mlmMember = MlmMember::where('user_id', $booking->user_id)->first();

        if (!$mlmMember) {
            // ลูกค้าไม่ได้เป็นสมาชิก MLM
            return null;
        }

        // คำนวณ PV จากการจอง (ใช้ pv_value ของ service หรือ total_amount)
        $pvValue = $booking->service->pv_value ?? $booking->total_amount;

        if ($pvValue <= 0) {
            return null;
        }

        try {
            // เพิ่ม PV ให้ลูกค้า (Personal PV)
            $mlmMember->increment('personal_pv', $pvValue);
            $mlmMember->increment('total_pv', $pvValue);

            // คำนวณคอมมิชชั่น MLM
            $commissionResult = $this->mlmCommissionService->calculateCommissionsWithRollup(
                $mlmMember,
                $pvValue,
                'service_booking',
                $booking->id
            );

            // อัพเดทการจองด้วย mlm_member_id และ commission_amount
            $booking->update([
                'mlm_member_id' => $mlmMember->id,
                'commission_amount' => $commissionResult['total_amount'] ?? 0,
            ]);

            return [
                'user_id' => $booking->user_id,
                'mlm_member_id' => $mlmMember->id,
                'pv_value' => $pvValue,
                'commission_result' => $commissionResult,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to distribute MLM commission', [
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * จ่ายคอมมิชชั่นให้ผู้แนะนำ (Referrer)
     *
     * @param ServiceBooking $booking
     * @return array|null
     */
    protected function payReferrerCommission(ServiceBooking $booking): ?array
    {
        // หา Affiliate/Referrer ของลูกค้า
        $customer = $booking->user;
        if (!$customer || !$customer->referred_by) {
            return null;
        }

        $referrer = User::find($customer->referred_by);
        if (!$referrer) {
            return null;
        }

        // คำนวณคอมมิชชั่นผู้แนะนำ (ใช้ค่าจากระบบหรือ default 5%)
        $referralPercentage = config('services.referral_commission_percentage', 5);
        $referralCommission = ($booking->total_amount * $referralPercentage) / 100;

        if ($referralCommission <= 0) {
            return null;
        }

        try {
            $transaction = $this->walletService->deposit(
                $referrer,
                $referralCommission,
                'referral_commission',
                "คอมมิชชั่นแนะนำบริการ #{$booking->booking_number}",
                [
                    'booking_id' => $booking->id,
                    'referred_user_id' => $customer->id,
                    'percentage' => $referralPercentage,
                ]
            );

            return [
                'referrer_id' => $referrer->id,
                'referred_user_id' => $customer->id,
                'amount' => $referralCommission,
                'percentage' => $referralPercentage,
                'transaction_id' => $transaction->id ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to pay referrer commission', [
                'booking_id' => $booking->id,
                'referrer_id' => $referrer->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * คำนวณสรุปรายได้และค่าใช้จ่ายของการจอง
     *
     * @param ServiceBooking $booking
     * @return array
     */
    public function calculateBookingSummary(ServiceBooking $booking): array
    {
        $service = $booking->service;
        $pvValue = $service->pv_value ?? $booking->total_amount;
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
