<?php

namespace App\Services;

use App\Models\PlatformTransaction;
use App\Models\PlatformWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * บันทึก "รายจ่ายของแพลตฟอร์ม" ที่เกิดจากโปรโมชัน (เงินคืนลูกค้า, ส่วนลดออเดอร์ที่แพลตฟอร์มออกให้)
 *
 * เดิมเงินคืน/ส่วนลดถูกจ่ายให้ลูกค้าโดยไม่มีบัญชีไหนรับต้นทุน (audit G15) — บริการนี้ทำให้ทุกบาทมีที่มา:
 *  - กระเป๋าต้นทาง (ปกติ = fee) มีเงินพอ → หักจริง (PlatformTransaction expense, completed)
 *  - มีเงินไม่พอ → ไม่หักยอด แต่บันทึกเป็นรายจ่ายค้างจ่าย (status = pending, sub_type ลงท้าย _unfunded)
 *    ให้แอดมินเห็นว่าแพลตฟอร์มแบกต้นทุนโปรโมชันที่ยังไม่มีเงินรองรับเท่าไร
 *
 * กันบันทึกซ้ำด้วย (sub_type, source_type, source_id) ภายใต้ lock ของกระเป๋า
 */
class PlatformExpenseService
{
    /**
     * บันทึกรายจ่ายโปรโมชันครั้งเดียวต่อแหล่งที่มา
     *
     * @param  string  $walletSlug  กระเป๋าที่ออกเงิน (fee = รายได้ค่าธรรมเนียมแพลตฟอร์ม)
     * @param  string  $subType  ประเภทย่อย เช่น cashback_expense, order_discount
     * @return PlatformTransaction|null null เมื่อจำนวนเงิน <= 0
     */
    public function recordPromoExpense(
        string $walletSlug,
        float $amount,
        string $subType,
        string $sourceType,
        int $sourceId,
        string $description,
        array $metadata = [],
        ?int $relatedUserId = null
    ): ?PlatformTransaction {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            return null;
        }

        $wallet = $this->resolveWallet($walletSlug);

        return DB::transaction(function () use ($wallet, $amount, $subType, $sourceType, $sourceId, $description, $metadata, $relatedUserId) {
            /** @var PlatformWallet $locked */
            $locked = PlatformWallet::whereKey($wallet->id)->lockForUpdate()->first();

            // เคยบันทึกแล้ว (ทั้งแบบหักจริงและแบบค้างจ่าย) → คืนรายการเดิม
            $existing = PlatformTransaction::where('platform_wallet_id', $locked->id)
                ->whereIn('sub_type', [$subType, $subType.'_unfunded'])
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->where('type', PlatformTransaction::TYPE_EXPENSE)
                ->first();
            if ($existing) {
                return $existing;
            }

            $balance = round((float) $locked->balance, 4);

            if ($balance + 0.00001 >= $amount) {
                $tx = $locked->deductFunds($amount, $subType, $sourceType, $sourceId, $metadata);
                $tx->update(['description' => $description, 'related_user_id' => $relatedUserId]);

                return $tx;
            }

            // เงินในกระเป๋าไม่พอ → บันทึกเป็นรายจ่ายค้างจ่าย ไม่แตะยอดคงเหลือ
            Log::warning('Platform promo expense is unfunded', [
                'wallet' => $locked->slug,
                'sub_type' => $subType,
                'amount' => $amount,
                'balance' => $balance,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]);

            return PlatformTransaction::create([
                'platform_wallet_id' => $locked->id,
                'type' => PlatformTransaction::TYPE_EXPENSE,
                'sub_type' => $subType.'_unfunded',
                'amount' => $amount,
                'balance_before' => $balance,
                'balance_after' => $balance,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'related_user_id' => $relatedUserId,
                'description' => $description.' (ค้างจ่าย: เงินในกระเป๋าไม่พอ)',
                'metadata' => array_merge($metadata, ['unfunded' => true]),
                'status' => 'pending',
            ]);
        });
    }

    /**
     * คืนรายจ่ายโปรโมชัน (เช่น เรียกเงินคืนลูกค้ากลับได้ตอนคืนเงินออเดอร์) — ครั้งเดียวต่อแหล่งที่มา
     *
     * @param  float  $amount  จำนวนที่เรียกคืนได้จริง
     */
    public function reverseExpense(
        string $walletSlug,
        float $amount,
        string $subType,
        string $sourceType,
        int $sourceId,
        string $description,
        array $metadata = []
    ): ?PlatformTransaction {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            return null;
        }

        $wallet = $this->resolveWallet($walletSlug);
        $reverseSubType = $subType.'_reversal';

        return DB::transaction(function () use ($wallet, $amount, $subType, $reverseSubType, $sourceType, $sourceId, $description, $metadata) {
            $locked = PlatformWallet::whereKey($wallet->id)->lockForUpdate()->first();

            $existing = PlatformTransaction::where('platform_wallet_id', $locked->id)
                ->where('sub_type', $reverseSubType)
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->first();
            if ($existing) {
                return $existing;
            }

            $original = PlatformTransaction::where('platform_wallet_id', $locked->id)
                ->whereIn('sub_type', [$subType, $subType.'_unfunded'])
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->where('type', PlatformTransaction::TYPE_EXPENSE)
                ->first();

            // รายจ่ายค้างจ่าย (ไม่เคยหักจริง) → ปิดรายการค้างแทนการเติมเงินกลับ
            if ($original && $original->status === 'pending') {
                $original->update([
                    'status' => 'cancelled',
                    'metadata' => array_merge($original->metadata ?? [], ['reversed_at' => now()->toIso8601String()]),
                ]);

                return $original;
            }

            $tx = $locked->addFunds($amount, $reverseSubType, $sourceType, $sourceId, $metadata);
            $tx->update(['description' => $description]);

            return $tx;
        });
    }

    /**
     * รายจ่ายโปรโมชันที่เคยบันทึกไว้ของแหล่งที่มานี้ (ทั้งแบบหักจริงและแบบค้างจ่าย) — null = ไม่เคยลง
     *
     * ใช้ตอนคืนเงิน: คืนรายจ่ายเท่ายอดที่เคยลงจริง ไม่ใช่เดาจากยอดส่วนลดในออเดอร์
     */
    public function findExpense(string $walletSlug, string $subType, string $sourceType, int $sourceId): ?PlatformTransaction
    {
        $wallet = $this->resolveWallet($walletSlug);

        return PlatformTransaction::where('platform_wallet_id', $wallet->id)
            ->whereIn('sub_type', [$subType, $subType.'_unfunded'])
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('type', PlatformTransaction::TYPE_EXPENSE)
            ->orderBy('id')
            ->first();
    }

    private function resolveWallet(string $slug): PlatformWallet
    {
        return match ($slug) {
            'fee' => PlatformWallet::getFeeWallet(),
            'vat' => PlatformWallet::getVatWallet(),
            'mlm_pool' => PlatformWallet::getMlmPoolWallet(),
            'refund_pool' => PlatformWallet::getRefundPoolWallet(),
            'admin_shop' => PlatformWallet::getAdminShopWallet(),
            'admin_services' => PlatformWallet::getAdminServicesWallet(),
            default => PlatformWallet::findBySlug($slug) ?? PlatformWallet::getFeeWallet(),
        };
    }
}
