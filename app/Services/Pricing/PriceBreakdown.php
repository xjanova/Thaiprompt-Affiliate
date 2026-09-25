<?php

namespace App\Services\Pricing;

/**
 * ผลการคำนวณราคา 1 รายการ (ราคาต่อหน่วย × จำนวน) จาก PricingEngine
 *
 * เป็น value object แบบอ่านอย่างเดียว — ทุกจุดในระบบ (checkout, แบ่งเงิน, ตัวจำลองของผู้ขาย,
 * API แอป, หน้าแอดมิน) ต้องอ่านตัวเลขจาก object นี้ ห้ามคำนวณซ้ำเอง
 *
 * ชื่อ property เป็น snake_case ให้ตรงกับคีย์ใน toArray() และสัญญาใน PLAN
 * จำนวนเงินทุกตัวปัดทศนิยม 2 ตำแหน่งแล้ว (สตางค์)
 */
final class PriceBreakdown
{
    /**
     * @param  array<int, array{key: string, label_th: string, amount: float, kind: string}>  $lines
     * @param  array<int, array{code: string, message: string}>  $warnings
     */
    public function __construct(
        /** ราคาต่อหน่วยที่ลูกค้าจ่าย (รวม VAT แล้ว) */
        public readonly float $unit_price,
        /** จำนวนชิ้น */
        public readonly int $quantity,
        /** ยอดขาย = ราคาต่อหน่วย × จำนวน */
        public readonly float $gross,
        /** อัตรา GP (%) */
        public readonly float $gp_rate,
        /** ค่า GP ที่แพลตฟอร์มได้ */
        public readonly float $gp_amount,
        /** อัตรา VAT (%) ที่ใช้ (0 เมื่อผู้ขายไม่ได้จดทะเบียน VAT) */
        public readonly float $vat_rate,
        /** VAT ที่ถอดออกจากราคา (ราคารวม VAT แล้ว) */
        public readonly float $vat_amount,
        /** PV รวม (PV ต่อชิ้น × จำนวน) — 0 เมื่อระบบแนะนำปิด */
        public readonly float $pv_total,
        /** เงินเข้ากองทุนค่าแนะนำ (ระบบ MLM) */
        public readonly float $referral_pool_amount,
        /** เงินคืนลูกค้า (cashback) */
        public readonly float $cashback_amount,
        /** ใครออกเงินคืน: platform | seller */
        public readonly string $cashback_funded_by,
        /** ค่าธรรมเนียมรับชำระเงินที่หักจากผู้ขาย */
        public readonly float $payment_fee,
        /** ค่าส่งที่ลูกค้าจ่าย (ส่งผ่านไปผู้ให้บริการขนส่ง ไม่ใช่รายได้ผู้ขาย) */
        public readonly float $shipping_fee,
        /** ค่าส่งที่ผู้ขายออกให้ลูกค้า (โปรส่งฟรี) */
        public readonly float $seller_shipping_subsidy,
        /** ยอดที่ผู้ขายได้รับสุทธิ (อาจติดลบถ้าตั้งราคาต่ำเกินไป — ฝั่งจ่ายเงินจริงต้อง clamp เอง) */
        public readonly float $seller_net,
        /** รายได้สุทธิของแพลตฟอร์ม = GP − cashback ที่แพลตฟอร์มออก */
        public readonly float $platform_net,
        /** ยอดที่ลูกค้าจ่ายทั้งหมด = ยอดขาย + ค่าส่ง */
        public readonly float $buyer_total,
        /** ต้นทุนรวม (null เมื่อไม่ได้ระบุต้นทุน) */
        public readonly ?float $cost_total,
        /** กำไร = ยอดสุทธิผู้ขาย − ต้นทุนรวม (null เมื่อไม่ได้ระบุต้นทุน) */
        public readonly ?float $profit,
        /** กำไรคิดเป็น % ของยอดขาย (null เมื่อไม่ได้ระบุต้นทุน) */
        public readonly ?float $margin_percent,
        /** รายการบรรทัดสำหรับแสดงผล (หักออกเป็นค่าติดลบ) */
        public readonly array $lines,
        /** คำเตือน เช่น ขายขาดทุน */
        public readonly array $warnings,
    ) {}

    /**
     * ผู้ขายขาดทุนหรือไม่ (ได้สุทธิติดลบ หรือกำไรติดลบเมื่อระบุต้นทุน)
     */
    public function isLoss(): bool
    {
        return $this->seller_net < 0 || ($this->profit !== null && $this->profit < 0);
    }

    /**
     * มีคำเตือนรหัสนี้หรือไม่
     */
    public function hasWarning(string $code): bool
    {
        foreach ($this->warnings as $warning) {
            if (($warning['code'] ?? null) === $code) {
                return true;
            }
        }

        return false;
    }

    /**
     * แปลงเป็น array สำหรับ JSON/API/view — ตัวเลขทุกตัวเป็น float/int จริง (ไม่ใช่ string)
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'unit_price' => $this->unit_price,
            'quantity' => $this->quantity,
            'gross' => $this->gross,
            'gp_rate' => $this->gp_rate,
            'gp_amount' => $this->gp_amount,
            'vat_rate' => $this->vat_rate,
            'vat_amount' => $this->vat_amount,
            'pv_total' => $this->pv_total,
            'referral_pool_amount' => $this->referral_pool_amount,
            'cashback_amount' => $this->cashback_amount,
            'cashback_funded_by' => $this->cashback_funded_by,
            'payment_fee' => $this->payment_fee,
            'shipping_fee' => $this->shipping_fee,
            'seller_shipping_subsidy' => $this->seller_shipping_subsidy,
            'seller_net' => $this->seller_net,
            'platform_net' => $this->platform_net,
            'buyer_total' => $this->buyer_total,
            'cost_total' => $this->cost_total,
            'profit' => $this->profit,
            'margin_percent' => $this->margin_percent,
            'is_loss' => $this->isLoss(),
            'lines' => $this->lines,
            'warnings' => $this->warnings,
        ];
    }
}
