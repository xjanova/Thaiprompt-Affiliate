<?php

namespace App\Models\Concerns;

use App\Models\MlmGlobalSetting;
use App\Models\MlmProductPv;

/**
 * Trait CalculatesEarnings
 *
 * คำนวณรายได้สุทธิสำหรับผู้ขาย/ผู้ให้บริการ
 * ใช้ได้กับ Product, Service, และ model อื่นๆ
 *
 * สูตร:
 * 1. ราคาขาย
 * 2. หัก Platform Fee
 * 3. หัก Provider PV (Service only)
 * 4. หัก Cashback (ผู้ขายจ่าย)
 * 5. หัก Promotion (ถ้าเป็นของร้าน)
 * 6. หัก VAT
 * 7. หัก MLM Commission (คำนวณจาก PV)
 * = รายได้สุทธิ
 *
 * @package App\Models\Concerns
 */
trait CalculatesEarnings
{
    /**
     * คำนวณรายได้สุทธิแบบ Real-time
     *
     * @param float|null $quantity จำนวน (default: 1)
     * @param float|null $sellerPromotion ส่วนลดจากผู้ขาย (บาท)
     * @param float|null $platformPromotion ส่วนลดจากระบบ (บาท)
     * @return array
     */
    public function calculateNetEarnings(
        ?float $quantity = 1,
        ?float $sellerPromotion = 0,
        ?float $platformPromotion = 0
    ): array {
        // ราคาขาย
        $salePrice = $this->price * $quantity;

        // 1. หัก Platform Fee
        $platformFeePercentage = $this->platform_fee_percentage ?? 10.00;
        $platformFeeAmount = $salePrice * ($platformFeePercentage / 100);

        // 2. หัก Provider PV (สำหรับ Service)
        $providerPvAmount = 0;
        if (isset($this->provider_pv_percentage)) {
            $providerPvAmount = $salePrice * ($this->provider_pv_percentage / 100);
        }

        // 3. หัก Cashback (ผู้ขายจ่าย)
        $cashbackPercentage = $this->cashback_percentage ?? 0;
        $cashbackAmount = $salePrice * ($cashbackPercentage / 100);

        // 4. หัก Promotion จากผู้ขาย
        $sellerPromotionAmount = $sellerPromotion ?? 0;

        // 5. คำนวณ Platform Promotion (หักจาก Platform Fee, ไม่เกินที่กำหนด)
        $platformPromotionAmount = $platformPromotion ?? 0;
        $maxPlatformPromotion = $platformFeeAmount * 0.5; // ไม่เกิน 50% ของ Platform Fee
        $platformPromotionAmount = min($platformPromotionAmount, $maxPlatformPromotion);

        // Platform Fee สุทธิหลังหัก Promotion
        $netPlatformFee = $platformFeeAmount - $platformPromotionAmount;

        // 6. ยอดก่อนหัก VAT และ MLM Commission
        $beforeTax = $salePrice
            - $netPlatformFee
            - $providerPvAmount
            - $cashbackAmount
            - $sellerPromotionAmount;

        // 7. หัก VAT
        $vatPercentage = $this->vat_percentage ?? MlmGlobalSetting::get('vat_rate', 7.00);
        $vatEnabled = MlmGlobalSetting::get('vat_enabled', true);
        $vatAmount = $vatEnabled ? ($beforeTax * ($vatPercentage / 100)) : 0;

        // 8. คำนวณ MLM Commission (จาก PV)
        $mlmCommission = $this->calculateMlmCommission($quantity);

        // 9. รายได้สุทธิ
        $netEarnings = $beforeTax - $vatAmount - $mlmCommission['total_amount'];

        // 10. กำไรสุทธิ (ถ้ามี cost_price)
        $costPrice = ($this->cost_price ?? 0) * $quantity;
        $netProfit = $netEarnings - $costPrice;

        return [
            'sale_price' => round($salePrice, 2),
            'quantity' => $quantity,

            // ค่าธรรมเนียมต่างๆ
            'platform_fee' => [
                'percentage' => $platformFeePercentage,
                'amount' => round($platformFeeAmount, 2),
                'after_promotion' => round($netPlatformFee, 2),
            ],
            'provider_pv' => [
                'percentage' => $this->provider_pv_percentage ?? 0,
                'amount' => round($providerPvAmount, 2),
            ],
            'cashback' => [
                'percentage' => $cashbackPercentage,
                'amount' => round($cashbackAmount, 2),
            ],

            // Promotions
            'seller_promotion' => round($sellerPromotionAmount, 2),
            'platform_promotion' => round($platformPromotionAmount, 2),
            'total_promotions' => round($sellerPromotionAmount + $platformPromotionAmount, 2),

            // ภาษี
            'vat' => [
                'enabled' => $vatEnabled,
                'percentage' => $vatPercentage,
                'amount' => round($vatAmount, 2),
            ],

            // MLM Commission
            'mlm_commission' => [
                'pv_total' => $mlmCommission['pv_total'],
                'total_amount' => round($mlmCommission['total_amount'], 2),
                'levels' => $mlmCommission['levels'],
            ],

            // รายได้สุทธิ
            'before_tax_and_mlm' => round($beforeTax, 2),
            'net_earnings' => round($netEarnings, 2),
            'earnings_percentage' => round(($netEarnings / $salePrice) * 100, 2),

            // ต้นทุนและกำไร
            'cost_price' => round($costPrice, 2),
            'has_cost_price' => !empty($this->cost_price),
            'net_profit' => round($netProfit, 2),
            'profit_margin' => $costPrice > 0 ? round(($netProfit / $salePrice) * 100, 2) : null,
        ];
    }

    /**
     * คำนวณ MLM Commission จาก PV
     *
     * @param float $quantity
     * @return array
     */
    protected function calculateMlmCommission(float $quantity = 1): array
    {
        // ดึง PV
        $pvValue = $this->getPvValue();
        $pvTotal = $pvValue * $quantity;

        if ($pvTotal <= 0) {
            return [
                'pv_total' => 0,
                'total_amount' => 0,
                'levels' => [],
            ];
        }

        // ดึงอัตรา PV to Money
        $pvToMoneyRate = MlmGlobalSetting::get('pv_to_money_rate', 1.00);

        // ดึงโครงสร้าง Unilevel จาก MLM settings
        $unilevelLevels = MlmGlobalSetting::get('unilevel_levels', []);

        $commissions = [];
        $totalCommission = 0;

        foreach ($unilevelLevels as $level) {
            $levelNumber = $level['level'] ?? 0;
            $percentage = $level['percentage'] ?? 0;

            if ($percentage > 0) {
                // คำนวณคอมของแต่ละ level
                $levelPv = $pvTotal * ($percentage / 100);
                $levelAmount = $levelPv * $pvToMoneyRate;

                // หัก VAT จากคอม (ผู้รับคอมก็ต้องจ่ายภาษี)
                $vatPercentage = MlmGlobalSetting::get('vat_rate', 7.00);
                $vatEnabled = MlmGlobalSetting::get('vat_enabled', true);
                $vatAmount = $vatEnabled ? ($levelAmount * ($vatPercentage / 100)) : 0;
                $netAmount = $levelAmount - $vatAmount;

                $commissions[] = [
                    'level' => $levelNumber,
                    'percentage' => $percentage,
                    'pv' => round($levelPv, 2),
                    'amount_before_vat' => round($levelAmount, 2),
                    'vat_amount' => round($vatAmount, 2),
                    'net_amount' => round($netAmount, 2),
                ];

                $totalCommission += $levelAmount; // หักจากผู้ขายคือก่อน VAT
            }
        }

        return [
            'pv_total' => round($pvTotal, 2),
            'pv_to_money_rate' => $pvToMoneyRate,
            'total_amount' => round($totalCommission, 2),
            'levels' => $commissions,
        ];
    }

    /**
     * ดึงค่า PV ของสินค้า/บริการ
     *
     * @return float
     */
    protected function getPvValue(): float
    {
        // ถ้ามี pv_value ตั้งไว้โดยตรง
        if (isset($this->pv_value) && $this->pv_value > 0) {
            return (float) $this->pv_value;
        }

        // ถ้าเป็น Product ให้เช็คจาก mlm_product_pv
        if ($this instanceof \App\Models\Product) {
            $productPv = MlmProductPv::where('product_id', $this->id)
                ->where('mlm_plan_id', MlmGlobalSetting::get('default_mlm_plan_id', 1))
                ->first();

            if ($productPv && $productPv->pv_value > 0) {
                return (float) $productPv->pv_value;
            }
        }

        // Default: ใช้ราคาสินค้าเป็น PV
        $pvCalculationMethod = MlmGlobalSetting::get('pv_calculation_method', 'price_based');

        if ($pvCalculationMethod === 'price_based') {
            return (float) $this->price;
        }

        return 0;
    }

    /**
     * ได้ข้อมูลสรุปแบบย่อสำหรับแสดงใน UI
     *
     * @return array
     */
    public function getEarningsSummary(): array
    {
        $earnings = $this->calculateNetEarnings();

        return [
            'sale_price' => $earnings['sale_price'],
            'net_earnings' => $earnings['net_earnings'],
            'earnings_percentage' => $earnings['earnings_percentage'],
            'net_profit' => $earnings['net_profit'],
            'has_cost_price' => $earnings['has_cost_price'],
        ];
    }
}
