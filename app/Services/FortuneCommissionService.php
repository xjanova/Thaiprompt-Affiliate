<?php

namespace App\Services;

use App\Models\FortuneCommission;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\MlmMember;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FortuneCommissionService
 *
 * จัดการคอมมิชชั่นจากบิลดูดวง (แยกจากระบบ MLM commissions)
 * - Level 1 (สายตรง): จ่ายให้ sponsor ตรง
 * - Level 2 (ชั้นหลาน): จ่ายให้ sponsor ของ sponsor
 *
 * ใช้ผัง mlm_members เดิม แต่บันทึกคอมมิชชั่นใน fortune_commissions
 * ไม่กระทบ mlm_commissions (ระบบซื้อของจ่ายแยกกัน)
 */
class FortuneCommissionService
{
    /**
     * จ่ายคอมมิชชั่นดูดวงให้ Level 1 + Level 2
     *
     * Flow:
     * 1. ตรวจสอบว่าบิลนี้จ่ายไปแล้วหรือยัง (ป้องกันซ้ำ)
     * 2. หา sponsor (Level 1) → คำนวณ + จ่าย
     * 3. หา grandparent (Level 2) → คำนวณ + จ่าย (ถ้าเปิด)
     *
     * @param FortuneReading $reading บิลดูดวงที่ชำระเงินแล้ว
     * @param MlmMember $mlmMember สมาชิก MLM ของคนดูดวง
     * @param FortuneTellingSetting $settings การตั้งค่าดูดวง
     */
    public function distributeCommissions(
        FortuneReading $reading,
        MlmMember $mlmMember,
        FortuneTellingSetting $settings
    ): void {
        // ตรวจสอบว่าบิลชำระเงินแล้ว (ไม่จ่ายคอมมิชชั่นสำหรับบิลฟรี)
        if (! $reading->is_paid || (float) ($reading->amount_paid ?? 0) <= 0) {
            Log::debug('FortuneCommission: บิลยังไม่ชำระเงินหรือจำนวน 0 ข้าม', [
                'reading_id' => $reading->id,
                'is_paid' => $reading->is_paid,
                'amount_paid' => $reading->amount_paid,
            ]);

            return;
        }

        // ตรวจสอบว่าบิลนี้จ่ายคอมมิชชั่นใน fortune_commissions ไปแล้วหรือยัง
        $alreadyDistributed = FortuneCommission::where('fortune_reading_id', $reading->id)->exists();
        if ($alreadyDistributed) {
            Log::info('FortuneCommission: บิลนี้จ่ายไปแล้ว ข้าม', [
                'reading_id' => $reading->id,
            ]);

            return;
        }

        // ใช้ amount_paid จริง, fallback ไปที่ deep_reading_price (กรณี amount_paid ยังไม่อัพเดท)
        $readingPrice = (float) ($reading->amount_paid ?? 0);
        if ($readingPrice <= 0) {
            $readingPrice = (float) ($settings->deep_reading_price ?? 0);
        }

        // ===== Level 1: จ่ายให้ sponsor ตรง =====
        $this->payLevel1($reading, $mlmMember, $settings, $readingPrice);

        // ===== Level 2: จ่ายให้ grandparent (ถ้าเปิด) =====
        if ($settings->isFortuneLevel2Enabled()) {
            $this->payLevel2($reading, $mlmMember, $settings, $readingPrice);
        }
    }

    /**
     * จ่ายคอมมิชชั่น Level 1 (สายตรง)
     */
    protected function payLevel1(
        FortuneReading $reading,
        MlmMember $mlmMember,
        FortuneTellingSetting $settings,
        float $readingPrice
    ): void {
        // หา sponsor (ผู้แนะนำตรง)
        if (! $mlmMember->unilevel_sponsor_id) {
            Log::debug('FortuneCommission [L1]: สมาชิกไม่มีผู้แนะนำ ข้าม', [
                'reading_id' => $reading->id,
                'mlm_member_id' => $mlmMember->id,
            ]);

            return;
        }

        $sponsor = MlmMember::with('user')->find($mlmMember->unilevel_sponsor_id);
        if (! $sponsor || ! $sponsor->user) {
            Log::debug('FortuneCommission [L1]: ผู้แนะนำไม่พบหรือไม่มี user', [
                'reading_id' => $reading->id,
                'sponsor_id' => $mlmMember->unilevel_sponsor_id,
            ]);

            return;
        }

        // เช็ค active: ผู้แนะนำต้อง active (ไม่ roll up — กฎดูดวง)
        $isActive = \App\Helpers\MlmRetentionHelper::isMemberActive($sponsor);
        if (! $isActive) {
            Log::info('FortuneCommission [L1]: ผู้แนะนำไม่ active ข้าม (ไม่ roll up)', [
                'reading_id' => $reading->id,
                'sponsor_id' => $sponsor->id,
            ]);

            return;
        }

        // คำนวณคอมมิชชั่น
        $commissionAmount = $settings->getFortuneLevel1Amount($readingPrice);
        if ($commissionAmount <= 0) {
            return;
        }

        $commissionType = $settings->getFortuneLevel1CommissionType();
        $commissionRate = (float) ($settings->fortune_level1_commission_amount ?? 10);

        // สร้าง record + จ่ายเข้า wallet
        $this->createCommissionAndPay(
            reading: $reading,
            recipientMember: $sponsor,
            fromMember: $mlmMember,
            level: 1,
            commissionType: $commissionType,
            commissionRate: $commissionRate,
            amount: $commissionAmount,
            readingPrice: $readingPrice,
        );

        Log::info('FortuneCommission [L1/สายตรง]: จ่ายสำเร็จ', [
            'reading_id' => $reading->id,
            'buyer_user_id' => $reading->user_id,
            'sponsor_user_id' => $sponsor->user_id,
            'type' => $commissionType,
            'rate' => $commissionRate,
            'amount' => $commissionAmount,
            'reading_price' => $readingPrice,
        ]);
    }

    /**
     * จ่ายคอมมิชชั่น Level 2 (ชั้นหลาน)
     */
    protected function payLevel2(
        FortuneReading $reading,
        MlmMember $mlmMember,
        FortuneTellingSetting $settings,
        float $readingPrice
    ): void {
        // หา sponsor ตรง
        if (! $mlmMember->unilevel_sponsor_id) {
            return;
        }

        $sponsor = MlmMember::find($mlmMember->unilevel_sponsor_id);
        if (! $sponsor || ! $sponsor->unilevel_sponsor_id) {
            Log::debug('FortuneCommission [L2]: ไม่มี grandparent ข้าม', [
                'reading_id' => $reading->id,
            ]);

            return;
        }

        // หา grandparent (sponsor ของ sponsor)
        $grandparent = MlmMember::with('user')->find($sponsor->unilevel_sponsor_id);
        if (! $grandparent || ! $grandparent->user) {
            Log::debug('FortuneCommission [L2]: grandparent ไม่พบหรือไม่มี user', [
                'reading_id' => $reading->id,
                'grandparent_id' => $sponsor->unilevel_sponsor_id,
            ]);

            return;
        }

        // เช็ค active
        $isActive = \App\Helpers\MlmRetentionHelper::isMemberActive($grandparent);
        if (! $isActive) {
            Log::info('FortuneCommission [L2]: grandparent ไม่ active ข้าม', [
                'reading_id' => $reading->id,
                'grandparent_id' => $grandparent->id,
            ]);

            return;
        }

        // คำนวณคอมมิชชั่น
        $commissionAmount = $settings->getFortuneLevel2Amount($readingPrice);
        if ($commissionAmount <= 0) {
            return;
        }

        $commissionType = $settings->getFortuneLevel2CommissionType();
        $commissionRate = (float) ($settings->fortune_level2_commission_amount ?? 5);

        // สร้าง record + จ่ายเข้า wallet
        $this->createCommissionAndPay(
            reading: $reading,
            recipientMember: $grandparent,
            fromMember: $mlmMember,
            level: 2,
            commissionType: $commissionType,
            commissionRate: $commissionRate,
            amount: $commissionAmount,
            readingPrice: $readingPrice,
        );

        Log::info('FortuneCommission [L2/หลาน]: จ่ายสำเร็จ', [
            'reading_id' => $reading->id,
            'buyer_user_id' => $reading->user_id,
            'grandparent_user_id' => $grandparent->user_id,
            'type' => $commissionType,
            'rate' => $commissionRate,
            'amount' => $commissionAmount,
            'reading_price' => $readingPrice,
        ]);
    }

    /**
     * สร้าง FortuneCommission record + เพิ่มเงินเข้า wallet
     */
    protected function createCommissionAndPay(
        FortuneReading $reading,
        MlmMember $recipientMember,
        MlmMember $fromMember,
        int $level,
        string $commissionType,
        float $commissionRate,
        float $amount,
        float $readingPrice,
    ): void {
        DB::transaction(function () use (
            $reading, $recipientMember, $fromMember,
            $level, $commissionType, $commissionRate, $amount, $readingPrice
        ) {
            $levelName = $level === 1 ? 'สายตรง' : 'ชั้นหลาน';

            // สร้าง FortuneCommission record
            $commission = FortuneCommission::create([
                'user_id' => $recipientMember->user_id,
                'from_user_id' => $reading->user_id,
                'fortune_reading_id' => $reading->id,
                'mlm_member_id' => $recipientMember->id,
                'from_mlm_member_id' => $fromMember->id,
                'level' => $level,
                'commission_type' => $commissionType,
                'commission_rate' => $commissionRate,
                'amount' => $amount,
                'reading_price' => $readingPrice,
                'status' => FortuneCommission::STATUS_PAID,
                'paid_at' => now(),
                'notes' => "ค่าแนะนำดูดวง L{$level} ({$levelName}) {$amount} บาท",
            ]);

            // เพิ่มเงินเข้า wallet
            $this->depositToWallet($recipientMember, $commission, $reading, $level, $amount);
        });
    }

    /**
     * เพิ่มเงินเข้า wallet ผู้รับคอมมิชชั่น
     */
    protected function depositToWallet(
        MlmMember $recipientMember,
        FortuneCommission $commission,
        FortuneReading $reading,
        int $level,
        float $amount
    ): void {
        try {
            $walletService = app(WalletService::class);

            $wallet = $recipientMember->user->wallet;
            if (! $wallet) {
                $wallet = Wallet::create([
                    'user_id' => $recipientMember->user_id,
                    'balance' => 0,
                    'currency' => 'THB',
                ]);
            }

            $levelName = $level === 1 ? 'สายตรง' : 'หลาน';
            $transaction = $walletService->deposit(
                $wallet,
                $amount,
                "ค่าแนะนำดูดวง L{$level}({$levelName}) {$amount} บาท จากบิล #{$reading->id}",
                FortuneCommission::class,
                $commission->id,
                [
                    'reading_id' => $reading->id,
                    'level' => $level,
                    'buyer_user_id' => $reading->user_id,
                    'mode' => 'fortune_commission',
                ]
            );

            // อัพเดท wallet_transaction_id ใน commission record
            $commission->update(['wallet_transaction_id' => $transaction->id]);
        } catch (\Exception $walletErr) {
            Log::warning("FortuneCommission: เพิ่มเงินเข้า wallet L{$level} ไม่สำเร็จ", [
                'user_id' => $recipientMember->user_id,
                'amount' => $amount,
                'error' => $walletErr->getMessage(),
            ]);
        }
    }
}
