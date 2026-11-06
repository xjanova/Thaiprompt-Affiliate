<?php

namespace App\Services;

use App\Models\FortuneCommission;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\MlmMember;
use App\Models\Wallet;
use App\Models\WalletDebt;
use App\Models\WalletTransaction;
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
     * บิลที่นับเป็น "เคยมีบิลที่ชำระแล้ว" ต้องมียอดอย่างน้อยเท่านี้ — กันแถวยอดเศษสตางค์/0 ที่ is_paid ค้าง
     *   ถูกนับเป็นสิทธิ์ถาวร (กฎยกเว้นทุกตัวต้องมี de-minimis — ดูบทเรียน MIN_REAL_MONEY_EXEMPT 2026-09-01)
     *   ราคาต่ำสุดจริงคือไพ่ 9฿ บนจันทรา
     */
    public const MIN_PAID_BILL = 1.0;

    /**
     * จ่ายคอมมิชชั่นดูดวงให้ Level 1 + Level 2
     *
     * Flow:
     * 1. ตรวจสอบว่าบิลนี้จ่ายไปแล้วหรือยัง (ป้องกันซ้ำ)
     * 2. หา sponsor (Level 1) → คำนวณ + จ่าย
     * 3. หา grandparent (Level 2) → คำนวณ + จ่าย (ถ้าเปิด)
     *
     * @param  FortuneReading  $reading  บิลดูดวงที่ชำระเงินแล้ว
     * @param  MlmMember  $mlmMember  สมาชิก MLM ของคนดูดวง
     * @param  FortuneTellingSetting  $settings  การตั้งค่าดูดวง
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

        // ใช้ amount_paid จริง, fallback ไปที่ deep_reading_price (กรณี amount_paid ยังไม่อัพเดท)
        $readingPrice = (float) ($reading->amount_paid ?? 0);

        // 💳 (2026-08-23) เพดานฐานคิดค่าแนะนำ = ราคาแพ็กเกจ ห้ามเกิน
        //
        //   จ่ายด้วยบัตร (Stripe) → createCheckoutSession เขียน
        //   amount_paid = ราคาแพ็กเกจ + ค่าธรรมเนียมต่างประเทศ (Celtic = 99 + 15 = 114)
        //   ค่าธรรมเนียมก้อนนี้ Stripe หักไป = **ต้นทุนผ่านทาง ไม่ใช่รายได้ของร้าน**
        //   ถ้าไม่ตัดออก = จ่ายค่าแนะนำบนเงินที่ร้านไม่เคยได้รับ
        //
        //   ⚠️ ห้ามใช้วิธี "ลบ service_fee ออกตรงๆ" — ข้อมูลจริงบน prod พิสูจน์แล้วว่าพัง:
        //      บิล FTU-260524-P7572 มี amount_paid=99.27 (ยอด QR ไทย) แต่ service_fee=15
        //      ลบตรงๆ จะเหลือ 84.27 = **จ่ายค่าแนะนำขาด** ทั้งที่ร้านได้เงินเต็ม
        //
        //   วิธีที่ถูกคือ clamp: ยอด QR ไทยบวกสตางค์สุ่มได้สูงสุด +0.99 (suffix 01-99)
        //   ดังนั้นอะไรที่เกิน "ราคาแพ็กเกจ + 0.99" = รวมค่าธรรมเนียมบัตรมาแน่นอน
        //   → บิลไทยทุกใบไม่ถูกแตะเลย (99.65 ≤ 99.99) บิลบัตรเท่านั้นที่ถูกตัดกลับเป็น 99
        //   เงื่อนไข service_fee > 0 คือกุญแจกันพลาด — prod ยืนยันแล้วว่า service_fee > 0
        //   มีเฉพาะบิลที่จ่ายผ่าน Stripe เท่านั้น (บิล QR ไทยเป็น 0/null ทุกใบ)
        //   ถ้าตัดเงื่อนไขนี้ทิ้ง จะไปโดนบิลเก่ายุคราคา 49฿ กับบิลที่ลูกค้าโอนเผื่อเป็นเลขกลม (100)
        //   ซึ่งร้านได้เงินเต็มจริง ไม่ควรถูกลดฐานค่าแนะนำ
        $packagePrice = $reading->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS
            ? (float) ($settings->celtic_cross_price ?? 0)
            : (float) ($settings->deep_reading_price ?? 0);

        $serviceFee = (float) ($reading->service_fee ?? 0);

        if ($serviceFee > 0 && $packagePrice > 0 && $readingPrice > $packagePrice + 0.99) {
            Log::info('FortuneCommission: ตัดค่าธรรมเนียมบัตรออกจากฐานค่าแนะนำ', [
                'reading_id' => $reading->id,
                'amount_paid' => $reading->amount_paid,
                'service_fee' => $reading->service_fee,
                'package_price' => $packagePrice,
                'commission_base' => $packagePrice,
            ]);

            $readingPrice = $packagePrice;
        }

        if ($readingPrice <= 0) {
            $readingPrice = (float) ($settings->deep_reading_price ?? 0);
        }

        // 💰 (F23/M13) จ่ายค่าแนะนำเฉพาะบิลที่รับเงินครบราคาแพคเกจ
        //   เดิมเช็คแค่ amount_paid > 0 → แอดมินมาร์คจ่าย 0.50 บาทกับบิล Celtic ก็จ่าย 10 + 2 บาท
        //   เผื่อโอนขาดได้ไม่เกิน fuzzy_underpay_max_baht (ค่าเดียวกับที่ระบบจับคู่ SMS ยอมรับว่าจ่ายครบ)
        //   บิลที่ไม่มีราคาแพคเกจในตั้งค่า (เช่นบิลจันทราซึ่งคิดเป็น % ของยอด) ไม่ถูกด่านนี้ — ยังโดนเพดานยอดรับด้านล่าง
        //   บิลโอนขาด/โอนเติม และบิลสลับแพคเกจที่เก็บเงินเพิ่ม: amount_paid = แค่ยอดก้อนหลัง
        //   เงินก้อนก่อนอยู่ใน partial_paid_total → ใช้ moneyReceived() ไม่ใช่ amount_paid ตรง ๆ
        $expectedPrice = $this->expectedBillPrice($reading, $settings);
        $underpayTolerance = max(0.0, (float) ($settings->fuzzy_underpay_max_baht ?? 1.00));
        $amountPaid = $this->moneyReceived($reading, $expectedPrice);
        if ($expectedPrice > 0 && $amountPaid + $underpayTolerance < $expectedPrice) {
            Log::warning('FortuneCommission: ยอดที่รับต่ำกว่าราคาแพคเกจ → ไม่จ่ายค่าแนะนำ', [
                'reading_id' => $reading->id,
                'reading_type' => $reading->reading_type,
                'amount_paid' => (float) ($reading->amount_paid ?? 0),
                'partial_paid_total' => (float) ($reading->partial_paid_total ?? 0),
                'money_received' => $amountPaid,
                'expected_price' => $expectedPrice,
                'underpay_tolerance' => $underpayTolerance,
                'reason' => 'underpaid_below_package_price',
            ]);

            return;
        }

        // 💰 (F23) ฐานเพดานค่าแนะนำรวม — บิลโอนขาด/โอนเติมใช้เงินที่รับจริงทั้งหมด (ไม่ใช่แค่ยอดก้อนหลัง)
        //   บิลปกติ = $readingPrice เดิม (ตัดค่าธรรมเนียมบัตรออกแล้ว)
        $revenueBase = $this->hasPartialCredit($reading) ? max($readingPrice, $amountPaid) : $readingPrice;

        // 🐛 Fix 2026-06-12: เช็คจ่ายซ้ำแยกราย level (เดิมเช็ครวมทั้งบิล —
        // ถ้า L1 สำเร็จแต่ L2 fail การ retry จะถูกข้ามทั้งบิล L2 ไม่มีวันได้จ่าย)
        // + แยก try/catch ราย level — L1 ล้มเหลวต้องไม่บล็อก L2
        // (unique index fc_reading_user_level_unique เป็น backstop กัน race ที่ DB)

        // ===== Level 1: จ่ายให้ sponsor ตรง =====
        if ($this->levelAlreadyPaid($reading, 1)) {
            Log::info('FortuneCommission [L1]: บิลนี้จ่าย L1 ไปแล้ว ข้าม', ['reading_id' => $reading->id]);
        } else {
            try {
                $this->payLevel1($reading, $mlmMember, $settings, $readingPrice, $revenueBase);
            } catch (\Throwable $l1Err) {
                Log::error('FortuneCommission [L1]: จ่ายล้มเหลว (ไม่บล็อก L2)', [
                    'reading_id' => $reading->id,
                    'error' => $l1Err->getMessage(),
                ]);
            }
        }

        // ===== Level 2: จ่ายให้ grandparent (ถ้าเปิด) =====
        // 📦 (2026-07-15) ส่ง reading_type เข้าไปด้วย — แพคเกจ deep 39฿ ปิดชั้นหลานได้
        //    แยกจาก celtic 99฿ (ดู FortuneTellingSetting::packageCommissionRate)
        if ($settings->isFortuneLevel2Enabled($reading->reading_type)) {
            if ($this->levelAlreadyPaid($reading, 2)) {
                Log::info('FortuneCommission [L2]: บิลนี้จ่าย L2 ไปแล้ว ข้าม', ['reading_id' => $reading->id]);
            } else {
                try {
                    $this->payLevel2($reading, $mlmMember, $settings, $readingPrice, $revenueBase);
                } catch (\Throwable $l2Err) {
                    Log::error('FortuneCommission [L2]: จ่ายล้มเหลว', [
                        'reading_id' => $reading->id,
                        'error' => $l2Err->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * 🌙 (2026-09-23) ผู้รับค่าแนะนำต้อง "เคยมีบิลที่ชำระแล้ว" อย่างน้อย 1 บิล — ทุกช่องทาง
     *
     * เจ้าของสั่ง: "ผู้เชิญต้องเคยเปิดบิลที่ชำระบิลแล้ว จึงจะได้รับค่าคอมทุกช่องทาง"
     *   - แทนเกณฑ์รักษายอดรายเดือน (MlmRetentionHelper) — เดิมผู้เชิญต้องมีบิล/PV ในเดือนนั้นหรือ 7 วันล่าสุด
     *     prod 2026-09-23 ผ่านเกณฑ์เดิมแค่ 195 จาก 1,112 คน ค่าแนะนำที่เหลือเข้ากระเป๋ากลางหมด
     *     (MlmRetentionHelper ยังใช้กับค่าคอมร้านค้า/ไบนารีตามเดิม — ที่นี่เลิกใช้กับค่าแนะนำดูดวงเท่านั้น)
     *   - บิลช่องทางไหนก็นับ: บอท FB/LINE และเว็บ/แอพจันทรา (บิลจันทราก็เป็นแถว fortune_readings)
     *   - เคยครั้งเดียวพอ ไม่หมดอายุ · บิลที่ยกเลิก/คืนเงินแล้ว (is_paid = false) หรือยอดต่ำกว่า MIN_PAID_BILL ไม่นับ
     *   - ตำแหน่งที่ถูกปิด (status ≠ active เช่นบัญชีเงาที่รวมแล้ว) หรือแอดมินตัดสิทธิ์ (is_qualified) ไม่ได้รับ
     */
    public function isEligibleRecipient(MlmMember $member): bool
    {
        return $this->ineligibleReason($member, 'sponsor') === null;
    }

    /**
     * เหตุที่ไม่ได้รับ (ใช้เป็นเหตุผลของกระเป๋ากลาง) — null = มีสิทธิ์รับ
     *
     * @param  string  $role  'sponsor' (สายตรง) | 'grandparent' (ชั้นหลาน)
     */
    protected function ineligibleReason(MlmMember $member, string $role): ?string
    {
        if ($member->status !== 'active' || ! $member->is_qualified || ! $member->user_id) {
            return "{$role}_inactive";
        }

        $hasPaidBill = FortuneReading::where('user_id', $member->user_id)
            ->where('is_paid', true)
            ->where('amount_paid', '>=', self::MIN_PAID_BILL)
            ->exists();

        return $hasPaidBill ? null : "{$role}_no_paid_bill";
    }

    /**
     * ตรวจว่าบิลนี้จ่ายคอมมิชชั่น level นี้ไปแล้วหรือยัง (กันจ่ายซ้ำราย level)
     *
     * ♻️ (F24) ไม่นับเฉพาะแถวที่ถูกดึงคืนตอน void บิล — บิลที่ void แล้วลูกค้าจ่ายจริงรอบใหม่ต้องได้ค่าแนะนำอีกครั้ง
     *   เดิมนับทุกแถว → ผู้แนะนำไม่ได้ค่าแนะนำของบิลนั้นอีกเลย
     *   ⚠️ แถวที่แอดมินกดปฏิเสธเอง (reject) / จันทรายกเลิก ยังนับว่าจ่ายแล้ว — ห้ามปลุกกลับมาจ่าย
     */
    protected function levelAlreadyPaid(FortuneReading $reading, int $level): bool
    {
        $voidReversedIds = FortuneCommission::where('fortune_reading_id', $reading->id)
            ->where('level', $level)
            ->reversedByVoid()
            ->pluck('id');

        return FortuneCommission::where('fortune_reading_id', $reading->id)
            ->where('level', $level)
            ->whereNotIn('id', $voidReversedIds)
            ->exists();
    }

    /**
     * 💰 (F23) บิลนี้มีเงินโอนขาด/เงินที่รับไว้ก่อนสะสมอยู่ไหม (partial_paid_total)
     */
    protected function hasPartialCredit(FortuneReading $reading): bool
    {
        return (float) ($reading->partial_paid_total ?? 0) > 0;
    }

    /**
     * 💰 (F23) เงินที่บิลนี้รับจริงทั้งหมด (ใช้ตัดสินด่านยอดขั้นต่ำ + ฐานเพดานค่าแนะนำ)
     *
     * บิลปกติ = amount_paid
     * บิลที่มี partial_paid_total (โอนขาดแล้วโอนเติม / สลับแพคเกจแล้วเก็บเงินเพิ่ม) — amount_paid เป็นแค่ยอดก้อนหลัง
     *   ⚠️ ความหมายของ partial_paid_total ไม่เหมือนกันทุกทาง: ทางสลิปที่ยอดสะสมครบ = รวมก้อนสุดท้ายแล้ว,
     *      ทาง UPA ตรงเป๊ะ/สลับแพคเกจ = ยังไม่รวมก้อนหลัง → บวกกันได้ แต่ห้ามเกินยอดเป้าหมายของบิล
     *      (partial_target_total หรือราคาแพคเกจ) กันนับเงินก้อนเดียวกันสองรอบ
     */
    protected function moneyReceived(FortuneReading $reading, float $expectedPrice): float
    {
        $amountPaid = (float) ($reading->amount_paid ?? 0);
        if (! $this->hasPartialCredit($reading)) {
            return $amountPaid;
        }

        $partial = (float) $reading->partial_paid_total;
        $ceiling = max((float) ($reading->partial_target_total ?? 0), $expectedPrice);
        $sum = round($partial + $amountPaid, 2);
        if ($ceiling > 0) {
            $sum = min($sum, $ceiling);
        }

        return round(max($amountPaid, $partial, $sum), 2);
    }

    /**
     * 💰 (F23) ราคาแพคเกจของบิล ตามตั้งค่าแม่หมอ — 0 = ไม่มีราคาแพคเกจ (ไม่ใช้ด่านยอดขั้นต่ำ)
     *
     * ลำดับเดียวกับ Api\Admin\Fortune\FortuneReadingsController::billAmountFor (deep / celtic / basic)
     */
    protected function expectedBillPrice(FortuneReading $reading, FortuneTellingSetting $settings): float
    {
        return match ($reading->reading_type) {
            FortuneReading::READING_TYPE_DEEP => (float) ($settings->deep_reading_price ?? 0),
            FortuneReading::READING_TYPE_CELTIC_CROSS => (float) ($settings->celtic_cross_price ?? 0),
            FortuneReading::READING_TYPE_BASIC, null => (float) ($settings->reading_price ?? 0),
            default => 0.0,
        };
    }

    /**
     * 💰 (F23) ตัดค่าแนะนำของ level นี้ไม่ให้ยอดรวมของบิล (แถวที่ยังไม่ถูกดึงคืน) เกินฐานรายรับของบิล
     *
     * ฐาน = ยอดที่ร้านได้จริง (ตัดค่าธรรมเนียมบัตรออกแล้ว · บิลโอนขาดรวมเงินทุกก้อน ดู moneyReceived)
     *
     * @return float ยอดที่จ่ายได้ (0 = เต็มเพดานแล้ว ข้าม level นี้)
     */
    protected function capToBillRevenue(FortuneReading $reading, float $readingPrice, float $amount, int $level): float
    {
        $alreadyPaid = (float) FortuneCommission::where('fortune_reading_id', $reading->id)
            ->where('status', '!=', FortuneCommission::STATUS_REJECTED)
            ->sum('amount');
        $room = round(max(0.0, $readingPrice - $alreadyPaid), 2);

        if ($amount <= $room) {
            return $amount;
        }

        Log::warning("FortuneCommission [L{$level}]: ค่าแนะนำเกินยอดที่รับของบิล → ตัดลงให้ไม่เกินยอดรับ", [
            'reading_id' => $reading->id,
            'reading_type' => $reading->reading_type,
            'reading_price' => $readingPrice,
            'already_paid' => $alreadyPaid,
            'requested' => $amount,
            'capped_to' => $room,
            'reason' => 'commission_exceeds_bill_revenue',
        ]);

        return $room;
    }

    /**
     * จ่ายคอมมิชชั่น Level 1 (สายตรง)
     *
     * ถ้าหา sponsor ไม่ได้ (ไม่มีผู้แนะนำ / ไม่มีสิทธิ์รับ — ดู isEligibleRecipient / user หาย):
     *   → fallback เข้ากระเป๋ากลาง (ถ้าเปิด isFortuneCentralFallbackEnabled)
     */
    protected function payLevel1(
        FortuneReading $reading,
        MlmMember $mlmMember,
        FortuneTellingSetting $settings,
        float $readingPrice,
        ?float $revenueBase = null
    ): void {
        // คำนวณ amount ก่อน — ใช้ทั้ง path sponsor และ central fallback
        // 📦 (2026-07-15) reading_type ตัดสินอัตราเมื่อเปิดโหมดแยกแพคเกจ
        $readingType = $reading->reading_type;
        $commissionAmount = $settings->getFortuneLevel1Amount($readingPrice, $readingType);
        if ($commissionAmount <= 0) {
            return;
        }

        // 💰 (F23) ค่าแนะนำรวมของบิลห้ามเกินยอดที่รับ
        $commissionAmount = $this->capToBillRevenue($reading, $revenueBase ?? $readingPrice, $commissionAmount, 1);
        if ($commissionAmount <= 0) {
            return;
        }

        $commissionType = $settings->getFortuneLevel1CommissionType($readingType);
        // commission_rate = ตัวเลขที่ตั้งไว้ (บันทึกลง DB ไว้ตรวจย้อนหลัง)
        $commissionRate = $settings->getFortuneCommissionRateForRecord(1, $readingType, $commissionAmount);

        // หา sponsor (ผู้แนะนำตรง)
        if (! $mlmMember->unilevel_sponsor_id) {
            Log::info('FortuneCommission [L1]: สมาชิกไม่มีผู้แนะนำ → fallback กระเป๋ากลาง', [
                'reading_id' => $reading->id,
                'mlm_member_id' => $mlmMember->id,
            ]);
            $this->payToCentralWallet(
                $reading, $mlmMember, $settings,
                level: 1,
                commissionType: $commissionType,
                commissionRate: $commissionRate,
                amount: $commissionAmount,
                readingPrice: $readingPrice,
                reason: 'no_referrer',
            );

            return;
        }

        $sponsor = MlmMember::with('user')->find($mlmMember->unilevel_sponsor_id);
        if (! $sponsor || ! $sponsor->user) {
            Log::info('FortuneCommission [L1]: ผู้แนะนำไม่พบหรือไม่มี user → fallback กระเป๋ากลาง', [
                'reading_id' => $reading->id,
                'sponsor_id' => $mlmMember->unilevel_sponsor_id,
            ]);
            $this->payToCentralWallet(
                $reading, $mlmMember, $settings,
                level: 1,
                commissionType: $commissionType,
                commissionRate: $commissionRate,
                amount: $commissionAmount,
                readingPrice: $readingPrice,
                reason: 'sponsor_missing',
            );

            return;
        }

        // ผู้แนะนำต้องมีสิทธิ์รับ — เคยมีบิลที่ชำระแล้ว (ไม่ roll up — กฎดูดวง)
        $ineligible = $this->ineligibleReason($sponsor, 'sponsor');
        if ($ineligible !== null) {
            Log::info('FortuneCommission [L1]: ผู้แนะนำไม่มีสิทธิ์รับ → fallback กระเป๋ากลาง (ไม่ roll up)', [
                'reading_id' => $reading->id,
                'sponsor_id' => $sponsor->id,
                'reason' => $ineligible,
            ]);
            $this->payToCentralWallet(
                $reading, $mlmMember, $settings,
                level: 1,
                commissionType: $commissionType,
                commissionRate: $commissionRate,
                amount: $commissionAmount,
                readingPrice: $readingPrice,
                reason: $ineligible,
            );

            return;
        }

        // สร้าง record + จ่ายเข้า wallet (sponsor ตรง)
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
     *
     * ถ้าหา grandparent ไม่ได้ (ไม่มี / ไม่มีสิทธิ์รับ — ดู isEligibleRecipient / user หาย):
     *   → fallback เข้ากระเป๋ากลาง (ถ้าเปิด isFortuneCentralFallbackEnabled)
     */
    protected function payLevel2(
        FortuneReading $reading,
        MlmMember $mlmMember,
        FortuneTellingSetting $settings,
        float $readingPrice,
        ?float $revenueBase = null
    ): void {
        // คำนวณ amount ก่อน — ใช้ทั้ง path grandparent และ central fallback
        // 📦 (2026-07-15) reading_type ตัดสินอัตราเมื่อเปิดโหมดแยกแพคเกจ
        $readingType = $reading->reading_type;
        $commissionAmount = $settings->getFortuneLevel2Amount($readingPrice, $readingType);
        if ($commissionAmount <= 0) {
            return;
        }

        // 💰 (F23) ค่าแนะนำรวมของบิลห้ามเกินยอดที่รับ (หักส่วนที่ L1 ได้ไปแล้ว)
        $commissionAmount = $this->capToBillRevenue($reading, $revenueBase ?? $readingPrice, $commissionAmount, 2);
        if ($commissionAmount <= 0) {
            return;
        }

        $commissionType = $settings->getFortuneLevel2CommissionType($readingType);
        $commissionRate = $settings->getFortuneCommissionRateForRecord(2, $readingType, $commissionAmount);

        // helper closure สำหรับ fallback
        $fallback = function (string $reason) use (
            $reading, $mlmMember, $settings, $commissionType, $commissionRate, $commissionAmount, $readingPrice
        ) {
            $this->payToCentralWallet(
                $reading, $mlmMember, $settings,
                level: 2,
                commissionType: $commissionType,
                commissionRate: $commissionRate,
                amount: $commissionAmount,
                readingPrice: $readingPrice,
                reason: $reason,
            );
        };

        // หา sponsor ตรง
        if (! $mlmMember->unilevel_sponsor_id) {
            Log::info('FortuneCommission [L2]: ไม่มีผู้แนะนำ → fallback กระเป๋ากลาง', [
                'reading_id' => $reading->id,
            ]);
            $fallback('no_referrer');

            return;
        }

        $sponsor = MlmMember::find($mlmMember->unilevel_sponsor_id);
        if (! $sponsor || ! $sponsor->unilevel_sponsor_id) {
            Log::info('FortuneCommission [L2]: ไม่มี grandparent → fallback กระเป๋ากลาง', [
                'reading_id' => $reading->id,
            ]);
            $fallback('no_grandparent');

            return;
        }

        // หา grandparent (sponsor ของ sponsor)
        $grandparent = MlmMember::with('user')->find($sponsor->unilevel_sponsor_id);
        if (! $grandparent || ! $grandparent->user) {
            Log::info('FortuneCommission [L2]: grandparent ไม่พบหรือไม่มี user → fallback กระเป๋ากลาง', [
                'reading_id' => $reading->id,
                'grandparent_id' => $sponsor->unilevel_sponsor_id,
            ]);
            $fallback('grandparent_missing');

            return;
        }

        // ชั้นหลานก็ต้องเคยมีบิลที่ชำระแล้ว — กติกาเดียวกับสายตรง
        $ineligible = $this->ineligibleReason($grandparent, 'grandparent');
        if ($ineligible !== null) {
            Log::info('FortuneCommission [L2]: grandparent ไม่มีสิทธิ์รับ → fallback กระเป๋ากลาง', [
                'reading_id' => $reading->id,
                'grandparent_id' => $grandparent->id,
                'reason' => $ineligible,
            ]);
            $fallback($ineligible);

            return;
        }

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
     * สร้างข้อความแจ้งรายได้ค่าแนะนำที่ยังไม่ได้บอก user (FB 24h-safe)
     *
     * เรียกจาก FortuneChannelManager::processMessage ทุกครั้งที่ user ทักแชทมา
     * → ใช้ replyMessage ฟรี ไม่กิน push quota / ไม่ติดกฎ FB 24h
     *
     * Flow:
     * 1. หา FortuneCommission ของ user นี้ ที่ status=PAID + chat_notified_at IS NULL
     * 2. รวมยอด + นับ + build ข้อความสรุป
     * 3. mark chat_notified_at = now() ทั้งหมด → กันซ้ำ
     * 4. return ข้อความ หรือ null ถ้าไม่มี
     *
     * @param  int  $userId  ผู้รับคอมมิชชั่น (recipient)
     * @return string|null ข้อความสรุปรายได้ หรือ null ถ้าไม่มี
     */
    public function buildPendingChatNotification(int $userId): ?string
    {
        // กันกระเป๋ากลาง — ไม่ต้องแจ้งตัวเอง (เป็น admin/system รู้อยู่แล้ว)
        $settings = FortuneTellingSetting::getSettings();
        $centralUserId = $settings->getFortuneCentralUserId();
        if ($centralUserId && $centralUserId === $userId) {
            return null;
        }

        try {
            $pending = FortuneCommission::where('user_id', $userId)
                ->where('status', FortuneCommission::STATUS_PAID)
                ->whereNull('chat_notified_at')
                ->orderBy('created_at')
                ->limit(20) // กันรายการเยอะเกิน — แสดง 20 ล่าสุด
                ->get();

            if ($pending->isEmpty()) {
                return null;
            }

            $totalAmount = (float) $pending->sum('amount');
            $count = $pending->count();
            $l1Count = $pending->where('level', 1)->count();
            $l2Count = $pending->where('level', 2)->count();

            // ดึงยอดรวมในกระเป๋า (เพื่อบอก current balance)
            $wallet = Wallet::where('user_id', $userId)->first();
            $balance = $wallet ? (float) $wallet->balance : 0;

            // build ข้อความ
            $lines = [];
            $lines[] = '💰 ข่าวดี! มีรายได้ค่าแนะนำเข้ากระเป๋า';
            $lines[] = '─────────────────────';
            $lines[] = sprintf('🎉 ได้รับ %d รายการ รวม %s บาท', $count, number_format($totalAmount, 2));

            $breakdown = [];
            if ($l1Count > 0) {
                $breakdown[] = "สายตรง {$l1Count} ราย";
            }
            if ($l2Count > 0) {
                $breakdown[] = "ชั้นหลาน {$l2Count} ราย";
            }
            if (! empty($breakdown)) {
                $lines[] = '🌳 '.implode(' • ', $breakdown);
            }

            $lines[] = '💎 ยอดในกระเป๋าปัจจุบัน: '.number_format($balance, 2).' บาท';

            // 💼 ลิงก์เข้ากระเป๋า — login ผ่าน Facebook OAuth (signed redirect → wallet)
            // ลูกค้ากดได้เลย: FB OAuth login → auto match → redirect ไปหน้า wallet
            $walletUrl = route('user.wallet.index');
            $loginUrl = route('facebook.login', ['redirect' => $walletUrl]);

            $lines[] = '';
            $lines[] = '👉 เข้าหน้ากระเป๋า/ถอนเงิน:';
            $lines[] = $loginUrl;
            $lines[] = '';
            $lines[] = '⚠️ การถอนต้องยืนยันตัวตน (KYC) ก่อน';
            $lines[] = '✨ ขอบคุณที่ช่วยแนะนำเพื่อนๆ มาดูดวงนะคะ';

            $message = implode("\n", $lines);

            // mark all as notified — กันส่งซ้ำ
            FortuneCommission::whereIn('id', $pending->pluck('id'))
                ->update(['chat_notified_at' => now()]);

            Log::info('FortuneCommission [Notify]: ส่งสรุปรายได้ผ่านแชท', [
                'user_id' => $userId,
                'count' => $count,
                'total_amount' => $totalAmount,
                'commission_ids' => $pending->pluck('id')->toArray(),
            ]);

            return $message;
        } catch (\Throwable $e) {
            // ห้าม fail การ reply ปกติเพราะ notify error
            Log::warning('FortuneCommission [Notify]: build notification ล้มเหลว (non-blocking)', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * จ่ายค่าแนะนำเข้ากระเป๋ากลาง (Central Wallet Fallback)
     *
     * เรียกเมื่อหา recipient จริงไม่ได้ (no_referrer/sponsor_inactive/no_grandparent/etc.)
     * → เงินไปกระเป๋ากลางพร้อม notes บอกเหตุผล → audit trail สมบูรณ์
     *
     * Skip เงียบๆ ถ้า:
     * - ปิด fortune_central_fallback_enabled
     * - ไม่ได้ตั้ง fortune_central_user_id
     * - User กลางถูกลบ / ไม่มี MlmMember
     * (เงินยังคงอยู่ในบัญชีบริษัทแต่ไม่มี record — เหมือนเดิม)
     *
     * @param  string  $reason  เหตุผล fallback (no_referrer/sponsor_inactive/no_grandparent/etc.)
     */
    protected function payToCentralWallet(
        FortuneReading $reading,
        MlmMember $fromMember,
        FortuneTellingSetting $settings,
        int $level,
        string $commissionType,
        float $commissionRate,
        float $amount,
        float $readingPrice,
        string $reason,
    ): void {
        if (! $settings->isFortuneCentralFallbackEnabled()) {
            Log::debug('FortuneCommission [Central]: fallback ปิดอยู่ → ไม่จ่ายเข้ากระเป๋ากลาง', [
                'reading_id' => $reading->id,
                'level' => $level,
                'reason' => $reason,
                'amount' => $amount,
            ]);

            return;
        }

        $centralUserId = $settings->getFortuneCentralUserId();
        if (! $centralUserId) {
            return; // isFortuneCentralFallbackEnabled() น่าจะจัดการไว้แล้ว แต่กันชน
        }

        // หา MlmMember ของ user กลาง
        $centralMember = MlmMember::with('user')->where('user_id', $centralUserId)->first();
        if (! $centralMember || ! $centralMember->user) {
            Log::warning('FortuneCommission [Central]: หา MlmMember ของ user กลางไม่เจอ → ข้าม fallback', [
                'reading_id' => $reading->id,
                'level' => $level,
                'central_user_id' => $centralUserId,
                'reason' => $reason,
                'lost_amount' => $amount,
            ]);

            return;
        }

        // กัน edge case: ถ้า fromMember เป็นคนเดียวกับ centralMember → ไม่จ่ายตัวเอง
        if ($fromMember->id === $centralMember->id) {
            Log::info('FortuneCommission [Central]: fromMember = centralMember → ข้าม (ไม่จ่ายตัวเอง)', [
                'reading_id' => $reading->id,
                'central_user_id' => $centralUserId,
            ]);

            return;
        }

        // จ่ายเข้ากระเป๋ากลาง พร้อม notes บอกเหตุผล
        DB::transaction(function () use (
            $reading, $centralMember, $fromMember, $level, $commissionType, $commissionRate, $amount, $readingPrice, $reason
        ) {
            $levelName = $level === 1 ? 'สายตรง' : 'ชั้นหลาน';
            $reasonNote = $this->reasonToThaiNote($reason);

            $commission = $this->createOrRestoreCommissionRow([
                'user_id' => $centralMember->user_id,
                'from_user_id' => $reading->user_id,
                'fortune_reading_id' => $reading->id,
                'mlm_member_id' => $centralMember->id,
                'from_mlm_member_id' => $fromMember->id,
                'level' => $level,
                'commission_type' => $commissionType,
                'commission_rate' => $commissionRate,
                'amount' => $amount,
                'reading_price' => $readingPrice,
                'status' => FortuneCommission::STATUS_PAID,
                'paid_at' => now(),
                'notes' => "[CENTRAL_FALLBACK:{$reason}] ค่าแนะนำดูดวง L{$level} ({$levelName}) {$amount} บาท — {$reasonNote}",
            ]);

            $this->depositToWallet($centralMember, $commission, $reading, $level, $amount);

            if (! $commission->wasRecentlyCreated) {
                $this->settleClawbackDebt($commission, $reading);
            }
        });

        Log::info('FortuneCommission [Central/กระเป๋ากลาง]: จ่ายสำเร็จ', [
            'reading_id' => $reading->id,
            'level' => $level,
            'reason' => $reason,
            'central_user_id' => $centralMember->user_id,
            'amount' => $amount,
            'reading_price' => $readingPrice,
        ]);
    }

    /**
     * แปลง reason code → คำอธิบายภาษาไทย (ใช้ใน notes)
     */
    protected function reasonToThaiNote(string $reason): string
    {
        return match ($reason) {
            'no_referrer' => 'ลูกค้าไม่มีผู้แนะนำ',
            'sponsor_missing' => 'ผู้แนะนำหายจากระบบ',
            'sponsor_inactive' => 'ตำแหน่งผู้แนะนำถูกปิด/ตัดสิทธิ์ (ไม่ roll up)',
            'sponsor_no_paid_bill' => 'ผู้แนะนำยังไม่เคยมีบิลที่ชำระแล้ว (ไม่ roll up)',
            'no_grandparent' => 'ไม่มี grandparent (ผู้แนะนำของผู้แนะนำ)',
            'grandparent_missing' => 'grandparent หายจากระบบ',
            'grandparent_inactive' => 'ตำแหน่ง grandparent ถูกปิด/ตัดสิทธิ์',
            'grandparent_no_paid_bill' => 'grandparent ยังไม่เคยมีบิลที่ชำระแล้ว',
            default => "เหตุผล: {$reason}",
        };
    }

    /**
     * สร้าง commission โดยตรง (ข้าม active check) — ใช้สำหรับ retroactive fix เท่านั้น
     *
     * @param  MlmMember  $recipient  ผู้รับคอมมิชชั่น
     * @param  MlmMember  $fromMember  สมาชิกที่จ่ายดูดวง
     * @param  int  $level  1 หรือ 2
     * @param  float  $amount  จำนวนเงิน
     * @param  float  $readingPrice  ราคา reading
     */
    public function forceCreateCommission(
        FortuneReading $reading,
        MlmMember $recipient,
        MlmMember $fromMember,
        int $level,
        float $amount,
        float $readingPrice,
        FortuneTellingSetting $settings
    ): void {
        // เช็คซ้ำ: ห้ามจ่าย reading+level เดียวกัน 2 ครั้ง
        $exists = FortuneCommission::where('fortune_reading_id', $reading->id)
            ->where('level', $level)
            ->exists();
        if ($exists) {
            return;
        }

        // 📦 (2026-07-15) รู้จัก reading_type ด้วย — โหมดแยกแพคเกจจ่ายเป็นบาทคงที่
        // 🐛 เดิม fallback ของ L2 ที่นี่เป็น `?? 1` ขณะที่ payLevel2()/model ใช้ `?? 5`
        //    → บันทึก commission_rate ไม่ตรงกับ path ปกติ (rate เป็นแค่ข้อมูลบันทึกไว้ตรวจ
        //      ไม่ได้เอาไปคูณ — $amount ส่งมาจาก caller — แต่ทำให้รายงานย้อนหลังอ่านผิด)
        $readingType = $reading->reading_type;
        $commissionType = $level === 1
            ? $settings->getFortuneLevel1CommissionType($readingType)
            : $settings->getFortuneLevel2CommissionType($readingType);

        if ($settings->isFortunePackageRatesEnabled()) {
            $commissionRate = $amount;
        } else {
            $commissionRate = $level === 1
                ? (float) ($settings->fortune_level1_commission_amount ?? 10)
                : (float) ($settings->fortune_level2_commission_amount ?? 5);
        }

        $this->createCommissionAndPay(
            reading: $reading,
            recipientMember: $recipient,
            fromMember: $fromMember,
            level: $level,
            commissionType: $commissionType,
            commissionRate: $commissionRate,
            amount: $amount,
            readingPrice: $readingPrice,
        );
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

            // สร้าง FortuneCommission record (หรือใช้แถว rejected เดิมของบิลที่ void แล้วจ่ายใหม่ — F24)
            $commission = $this->createOrRestoreCommissionRow([
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

            // 🧾 (F25/F24) แถวที่นำกลับมาจ่ายใหม่ เคยดึงคืนไม่ครบจนเป็นหนี้ → หักหนี้ของแถวนี้จากยอดที่เพิ่งเข้า
            $settled = 0.0;
            if (! $commission->wasRecentlyCreated) {
                $settled = $this->settleClawbackDebt($commission, $reading);
            }

            // 🧾 F14/M7: หนี้ค้างอื่น (ดึงคืนคอม MLM/คืนเงิน) → หักจากค่าแนะนำที่เพิ่งเข้ากระเป๋า (ส่วนที่เหลือหลังหักหนี้ของแถวนี้)
            //    เดิมค่าแนะนำดูดวงเข้ากระเป๋าตรงโดยไม่หักหนี้ → ผู้มีหนี้ได้เงินแล้วโอน/ใช้ออกไปได้
            $rest = round($amount - $settled, 2);
            if ($rest > 0 && $recipientMember->user_id) {
                (new DebtCollectionService)->collectFromWalletIncome(
                    (int) $recipientMember->user_id,
                    $rest,
                    FortuneCommission::class,
                    (int) $commission->id,
                    'ค่าแนะนำดูดวง #'.$commission->id
                );
            }
        });
    }

    /**
     * ♻️ (F24) สร้างแถวค่าแนะนำ หรือนำแถว rejected เดิมของบิล+level นี้กลับมาใช้
     *
     * บิลที่ถูก void แล้วลูกค้าจ่ายจริงรอบใหม่ มีแถวเดิมสถานะ rejected ค้างอยู่ — สร้างแถวใหม่ให้ผู้รับคนเดิม
     * จะชน unique fc_reading_user_level_unique (fortune_reading_id, user_id, level) → ใช้แถวเดิมแทน
     * และต่อท้าย notes ไว้ตรวจย้อนหลัง
     *   - ใช้เฉพาะแถวที่ถูกดึงคืนตอน void (FortuneCommission::scopeReversedByVoid) — แถวที่แอดมินปฏิเสธเองห้ามปลุก
     *   - ใช้เฉพาะแถวของผู้รับคนเดิม — ผู้รับเปลี่ยน (ย้ายสาย) สร้างแถวใหม่ ไม่ทับประวัติการดึงคืน/หนี้ของคนเก่า
     * ⚠️ เรียกภายใน DB::transaction เท่านั้น (ล็อกแถวที่จะนำกลับมาใช้)
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function createOrRestoreCommissionRow(array $attributes): FortuneCommission
    {
        $rejected = FortuneCommission::where('fortune_reading_id', $attributes['fortune_reading_id'])
            ->where('level', $attributes['level'])
            ->where('user_id', $attributes['user_id'])
            ->reversedByVoid()
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if (! $rejected) {
            return FortuneCommission::create($attributes);
        }

        $rejected->update(array_merge($attributes, [
            'wallet_transaction_id' => null,
            'chat_notified_at' => null,
            'rejected_at' => null,
            'notes' => trim((string) ($rejected->notes ?? '')).' | ♻️ จ่ายใหม่หลังยกเลิก: '.($attributes['notes'] ?? ''),
        ]));

        return $rejected;
    }

    /**
     * 🧾 (F25/F24) หักหนี้ FortuneClawback ของแถวค่าแนะนำนี้ จากเงินที่เพิ่งจ่ายเข้ากระเป๋า
     *
     * void บิลตอนผู้รับถอนเงินไปแล้ว → ดึงคืนได้ไม่ครบ ส่วนที่ขาดเป็น WalletDebt(source_id = แถวนี้)
     * ระบบเก็บหนี้ที่มีอยู่ (DebtCollectionService/PayoutService) หักจาก EarningsLedger/ตอนถอนเท่านั้น
     * ค่าแนะนำดูดวงเข้ากระเป๋าตรง → ถ้าไม่หักตรงนี้ บิลที่จ่ายใหม่จะได้เงินซ้ำทั้งที่หนี้ยังค้าง
     * ⚠️ เรียกภายใน DB::transaction เดียวกับการจ่าย (ล็อกกระเป๋า + หนี้) · ไม่ส่งข้อความหาผู้ใช้ทาง LINE
     */
    protected function settleClawbackDebt(FortuneCommission $commission, FortuneReading $reading): float
    {
        $settled = 0.0;
        $debts = WalletDebt::where('user_id', $commission->user_id)
            ->where('source_type', 'FortuneClawback')
            ->where('source_id', $commission->id)
            ->active()
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        if ($debts->isEmpty()) {
            return 0.0;
        }

        $wallet = Wallet::where('user_id', $commission->user_id)->lockForUpdate()->first();
        if (! $wallet) {
            return 0.0;
        }

        $budget = round((float) $commission->amount, 2);
        foreach ($debts as $debt) {
            $before = round((float) ($wallet->balance ?? 0), 2);
            $take = round(min((float) $debt->remaining_amount, $budget, max(0.0, $before)), 2);
            if ($take <= 0) {
                break;
            }

            $after = round($before - $take, 2);
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'type' => 'fee',
                'amount' => $take,
                'balance_before' => $before,
                'balance_after' => $after,
                'currency' => $wallet->currency,
                'description' => "หักหนี้ดึงคืนค่าแนะนำดูดวง — บิล #{$reading->id} (หนี้ #{$debt->id})",
                'reference_type' => WalletDebt::class,
                'reference_id' => $debt->id,
                'status' => 'completed',
                'metadata' => [
                    'reading_id' => $reading->id,
                    'commission_id' => $commission->id,
                    'mode' => 'fortune_clawback_debt_collect',
                ],
                'completed_at' => now(),
            ]);
            // หักแค่ยอดคงเหลือ — total_income ถูกหักเต็มจำนวนไปแล้วตอนดึงคืน ห้ามหักซ้ำ
            $wallet->update(['balance' => $after, 'last_transaction_at' => now()]);
            $debt->deduct($take);
            $budget = round($budget - $take, 2);
            $settled = round($settled + $take, 2);

            Log::warning('FortuneCommission: หักหนี้ดึงคืนค่าแนะนำจากค่าแนะนำที่จ่ายใหม่', [
                'reading_id' => $reading->id,
                'commission_id' => $commission->id,
                'debt_id' => $debt->id,
                'deducted' => $take,
            ]);
        }

        return $settled;
    }

    /**
     * เพิ่มเงินเข้า wallet ผู้รับคอมมิชชั่น
     *
     * ⚠️ ทำ wallet + transaction โดยตรง (ไม่ผ่าน WalletService)
     * เพื่อหลีกเลี่ยงปัญหา nested transaction + WalletLog
     * ที่ทำให้ deposit ล้มเหลวเมื่อสร้าง wallet ใหม่ภายใน DB::transaction เดียวกัน
     */
    protected function depositToWallet(
        MlmMember $recipientMember,
        FortuneCommission $commission,
        FortuneReading $reading,
        int $level,
        float $amount
    ): void {
        try {
            // หา wallet ที่มีอยู่ หรือสร้างใหม่โดยตรง
            // 🐛 Fix 2026-06-12: lockForUpdate กัน race — สองคอมมิชชั่นเข้ากระเป๋าเดียว
            // พร้อมกันเคยอ่าน balance เดิมซ้ำ → ยอดเงินหาย (lost update)
            $wallet = Wallet::where('user_id', $recipientMember->user_id)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                $wallet = Wallet::create([
                    'user_id' => $recipientMember->user_id,
                    'balance' => 0,
                    'currency' => 'THB',
                    'status' => 'active',
                ]);
                // refresh เพื่อโหลด default values (wallet_address, etc.) จาก DB
                $wallet->refresh();
            }

            if ($wallet->status !== 'active') {
                // 🐛 Fix 2026-06-12: ต้อง throw เพื่อให้ commission record rollback ด้วย
                // (เดิม return เงียบ → commission สถานะ PAID แต่เงินไม่เข้ากระเป๋า)
                throw new \RuntimeException(
                    "wallet ไม่ active สำหรับ user {$recipientMember->user_id} (status: {$wallet->status})"
                );
            }

            $balanceBefore = (float) ($wallet->balance ?? 0);
            $balanceAfter = $balanceBefore + $amount;
            $levelName = $level === 1 ? 'สายตรง' : 'หลาน';

            // สร้าง WalletTransaction โดยตรง
            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'type' => 'deposit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => $wallet->currency,
                'description' => "ค่าแนะนำดูดวง L{$level}({$levelName}) {$amount} บาท จากบิล #{$reading->id}",
                'reference_type' => FortuneCommission::class,
                'reference_id' => $commission->id,
                'status' => 'completed',
                'metadata' => [
                    'reading_id' => $reading->id,
                    'level' => $level,
                    'buyer_user_id' => $reading->user_id,
                    'mode' => 'fortune_commission',
                ],
                'completed_at' => now(),
            ]);

            // อัพเดท wallet balance โดยตรง
            $wallet->update([
                'balance' => $balanceAfter,
                'total_income' => (float) ($wallet->total_income ?? 0) + $amount,
                'last_transaction_at' => now(),
            ]);

            // อัพเดท wallet_transaction_id ใน commission record
            $commission->update(['wallet_transaction_id' => $transaction->id]);
        } catch (\Throwable $walletErr) {
            Log::warning("FortuneCommission: เพิ่มเงินเข้า wallet L{$level} ไม่สำเร็จ — rollback commission", [
                'user_id' => $recipientMember->user_id,
                'amount' => $amount,
                'error' => $walletErr->getMessage(),
                'trace' => $walletErr->getTraceAsString(),
            ]);

            // 🐛 Fix 2026-06-12: rethrow ให้ DB::transaction ที่ครอบอยู่ rollback
            // commission record ด้วย — ห้ามมี record สถานะ PAID โดยเงินไม่เข้ากระเป๋า
            // (caller จับ exception ราย level ใน distributeCommissions แล้ว ไม่กระทบคำทำนาย)
            throw $walletErr;
        }
    }
}
