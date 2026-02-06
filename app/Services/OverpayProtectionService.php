<?php

namespace App\Services;

use App\Models\MlmGlobalSetting;
use Illuminate\Support\Facades\Log;

/**
 * OverpayProtectionService
 *
 * บริการป้องกันการจ่ายคอมมิชชั่นเกิน (Overpay Protection)
 *
 * ตรวจสอบ 3 จุดหลัก:
 * 1. ตรวจสอบตอนตั้งค่า (Settings Validation) - ก่อนบันทึก
 * 2. ตรวจสอบตอนคำนวณ (Runtime Guard) - ก่อนจ่ายจริง
 * 3. ตรวจสอบ PV Rate >=1 - ป้องกันค่าผิดปกติ
 *
 * สูตรคำนวณ overpay:
 * - PV Pool = PV × commission_per_pv (งบประมาณทั้งหมดสำหรับจ่ายคอมมิชชั่น)
 * - Max Commission = PV Pool × (max_commission_percentage / 100)
 * - ถ้า Total Commission > Max Commission → เกิด Overpay
 */
class OverpayProtectionService
{
    /**
     * ตรวจสอบว่าการตั้งค่า unilevel levels จะทำให้เกิด overpay หรือไม่
     *
     * @param array $levels unilevel levels [['level' => 1, 'percentage' => 10], ...]
     * @param float|null $commissionPerPv อัตราแปลง PV→บาท (ถ้า null ดึงจาก settings)
     * @param float|null $maxPercentage เปอร์เซ็นต์สูงสุดที่อนุญาต (ถ้า null ดึงจาก settings)
     * @return array ['is_valid' => bool, 'total_percentage' => float, 'max_percentage' => float, 'message' => string]
     */
    public static function validateLevelConfiguration(
        array $levels,
        ?float $commissionPerPv = null,
        ?float $maxPercentage = null
    ): array {
        $commissionPerPv = $commissionPerPv ?? (float) MlmGlobalSetting::get('commission_per_pv', 1);
        $maxPercentage = $maxPercentage ?? (float) MlmGlobalSetting::get('max_commission_percentage', 40);

        // คำนวณผลรวมเปอร์เซ็นต์ทุกชั้น
        $totalPercentage = 0;
        foreach ($levels as $level) {
            $totalPercentage += ($level['percentage'] ?? 0);
        }

        // คำนวณ effective commission rate
        // สูตร: ถ้า commission_per_pv = 1 → totalPercentage คือ % ตรงๆ
        // ถ้า commission_per_pv ≠ 1 → ต้องคำนวณ effective rate
        $effectiveRate = $totalPercentage * $commissionPerPv;

        // ตรวจสอบ overpay
        $isValid = $effectiveRate <= $maxPercentage;

        $message = $isValid
            ? sprintf('ปลอดภัย: ผลรวมคอมมิชชั่น %.2f%% (สูงสุด %.2f%%)', $effectiveRate, $maxPercentage)
            : sprintf(
                'เกิน Overpay! ผลรวมคอมมิชชั่น %.2f%% เกินกว่าที่อนุญาต %.2f%% - กรุณาลดเปอร์เซ็นต์หรือจำนวนชั้น',
                $effectiveRate,
                $maxPercentage
            );

        return [
            'is_valid' => $isValid,
            'total_percentage' => $totalPercentage,
            'effective_rate' => $effectiveRate,
            'max_percentage' => $maxPercentage,
            'commission_per_pv' => $commissionPerPv,
            'level_count' => count($levels),
            'message' => $message,
        ];
    }

    /**
     * ตรวจสอบว่าค่า PV Rate ถูกต้อง (>= 1)
     *
     * @param float $pvRate อัตรา PV
     * @return array ['is_valid' => bool, 'message' => string]
     */
    public static function validatePvRate(float $pvRate): array
    {
        // PV Rate ต้อง >= 0.01 (ห้ามเป็น 0 หรือลบ)
        // แนะนำให้ >= 1 สำหรับการใช้งานปกติ
        if ($pvRate < 0.01) {
            return [
                'is_valid' => false,
                'value' => $pvRate,
                'message' => 'PV Rate ต้องมากกว่า 0 (ค่าแนะนำ: 1 ขึ้นไป)',
            ];
        }

        $warning = null;
        if ($pvRate < 1) {
            $warning = sprintf('PV Rate %.4f ต่ำกว่า 1 - ควรตรวจสอบว่าตั้งใจหรือไม่', $pvRate);
        }

        return [
            'is_valid' => true,
            'value' => $pvRate,
            'warning' => $warning,
            'message' => $warning ?? 'PV Rate ถูกต้อง',
        ];
    }

    /**
     * ตรวจสอบว่าค่า commission_per_pv ถูกต้อง
     *
     * @param float $commissionPerPv อัตราแปลง PV→บาท
     * @return array ['is_valid' => bool, 'message' => string]
     */
    public static function validateCommissionPerPv(float $commissionPerPv): array
    {
        if ($commissionPerPv <= 0) {
            return [
                'is_valid' => false,
                'value' => $commissionPerPv,
                'message' => 'Commission Per PV ต้องมากกว่า 0',
            ];
        }

        return [
            'is_valid' => true,
            'value' => $commissionPerPv,
            'message' => 'Commission Per PV ถูกต้อง',
        ];
    }

    /**
     * Runtime Guard: ตรวจสอบ commission ก่อนจ่ายจริง
     *
     * ใช้ตรวจสอบว่ายอด commission ทั้งหมดไม่เกิน budget ที่กำหนด
     *
     * @param float $totalPv PV รวม
     * @param float $totalCommission Commission รวมที่คำนวณได้
     * @param float|null $commissionPerPv อัตราแปลง (ถ้า null ดึงจาก settings)
     * @param float|null $maxPercentage % สูงสุด (ถ้า null ดึงจาก settings)
     * @return array ['allowed' => bool, 'max_allowed' => float, 'excess' => float, 'ratio' => float]
     */
    public static function checkRuntimeOverpay(
        float $totalPv,
        float $totalCommission,
        ?float $commissionPerPv = null,
        ?float $maxPercentage = null
    ): array {
        $commissionPerPv = $commissionPerPv ?? (float) MlmGlobalSetting::get('commission_per_pv', 1);
        $maxPercentage = $maxPercentage ?? (float) MlmGlobalSetting::get('max_commission_percentage', 40);

        // งบประมาณสูงสุดที่จ่ายได้
        $maxAllowed = $totalPv * $commissionPerPv * ($maxPercentage / 100);

        if ($maxAllowed <= 0) {
            return [
                'allowed' => false,
                'max_allowed' => 0,
                'total_commission' => $totalCommission,
                'excess' => $totalCommission,
                'ratio' => 0,
                'message' => 'ไม่สามารถจ่ายคอมมิชชั่นได้ (งบประมาณ = 0)',
            ];
        }

        $excess = max(0, $totalCommission - $maxAllowed);
        $ratio = $totalCommission > 0 ? min(1, $maxAllowed / $totalCommission) : 1;
        $allowed = $totalCommission <= $maxAllowed;

        return [
            'allowed' => $allowed,
            'max_allowed' => round($maxAllowed, 2),
            'total_commission' => round($totalCommission, 2),
            'excess' => round($excess, 2),
            'ratio' => round($ratio, 4),
            'percentage_used' => round(($totalCommission / $maxAllowed) * 100, 2),
            'message' => $allowed
                ? sprintf('ปลอดภัย: ใช้ %.2f%% ของงบประมาณ', ($totalCommission / $maxAllowed) * 100)
                : sprintf('Overpay! เกิน ฿%.2f (%.2f%% ของงบประมาณ)', $excess, ($totalCommission / $maxAllowed) * 100),
        ];
    }

    /**
     * ตรวจสอบว่า Seller ยังได้เงินสุทธิเป็นบวกหรือไม่
     *
     * Seller จะถูกหัก: Platform Fee + VAT + MLM Commission
     * ถ้าหักแล้วติดลบ = Seller ตั้งราคาต่ำเกินไป
     *
     * ⚠️ Business Logic:
     * - Platform Fee = ค่าใช้แพลตฟอร์ม (ไปจ่าย pool/rank bonus)
     * - MLM Commission = ค่าการตลาด PV (ไปจ่ายคอมมิชชั่น uplines)
     * - Fee กับ MLM เป็นคนละก้อน แยกจากกัน
     *
     * @param float $grossAmount ยอดขาย
     * @param float $platformFee ค่า Platform Fee
     * @param float $vatAmount ภาษี VAT
     * @param float $mlmCommission ค่า MLM Commission (PV-based)
     * @return array ['sufficient' => bool, 'net_amount' => float, 'message' => string]
     */
    public static function checkSellerNetAmount(
        float $grossAmount,
        float $platformFee,
        float $vatAmount,
        float $mlmCommission
    ): array {
        $totalDeductions = $platformFee + $vatAmount + $mlmCommission;
        $netAmount = $grossAmount - $totalDeductions;
        $sufficient = $netAmount >= 0;

        return [
            'sufficient' => $sufficient,
            'gross_amount' => round($grossAmount, 2),
            'platform_fee' => round($platformFee, 2),
            'vat_amount' => round($vatAmount, 2),
            'mlm_commission' => round($mlmCommission, 2),
            'total_deductions' => round($totalDeductions, 2),
            'net_amount' => round($netAmount, 2),
            'deduction_percentage' => $grossAmount > 0 ? round(($totalDeductions / $grossAmount) * 100, 2) : 0,
            'message' => $sufficient
                ? sprintf('Seller ได้รับ ฿%.2f (หัก %.2f%% จากยอดขาย)', $netAmount, ($totalDeductions / max($grossAmount, 1)) * 100)
                : sprintf('Seller ติดลบ ฿%.2f! ราคาสินค้าต่ำเกินไป ต้องตั้งราคาอย่างน้อย ฿%.2f', abs($netAmount), $totalDeductions),
        ];
    }

    /**
     * ตรวจสอบทุกอย่างพร้อมกัน (Comprehensive Validation)
     *
     * ใช้สำหรับตรวจสอบก่อนบันทึกการตั้งค่า MLM
     *
     * @param array $settings ค่าที่ต้องการตรวจสอบ
     * @return array ['is_valid' => bool, 'errors' => array, 'warnings' => array]
     */
    public static function validateAllSettings(array $settings): array
    {
        $errors = [];
        $warnings = [];

        // ตรวจสอบ PV Rate
        if (isset($settings['global_pv_rate'])) {
            $pvResult = self::validatePvRate((float) $settings['global_pv_rate']);
            if (!$pvResult['is_valid']) {
                $errors[] = $pvResult['message'];
            } elseif (isset($pvResult['warning'])) {
                $warnings[] = $pvResult['warning'];
            }
        }

        // ตรวจสอบ commission_per_pv
        if (isset($settings['commission_per_pv'])) {
            $cpvResult = self::validateCommissionPerPv((float) $settings['commission_per_pv']);
            if (!$cpvResult['is_valid']) {
                $errors[] = $cpvResult['message'];
            }
        }

        // ตรวจสอบ unilevel_levels overpay
        if (isset($settings['unilevel_levels'])) {
            $levels = is_string($settings['unilevel_levels'])
                ? json_decode($settings['unilevel_levels'], true)
                : $settings['unilevel_levels'];

            if (is_array($levels)) {
                $levelResult = self::validateLevelConfiguration(
                    $levels,
                    isset($settings['commission_per_pv']) ? (float) $settings['commission_per_pv'] : null,
                    isset($settings['max_commission_percentage']) ? (float) $settings['max_commission_percentage'] : null
                );

                if (!$levelResult['is_valid']) {
                    $errors[] = $levelResult['message'];
                }
            }
        }

        $isValid = empty($errors);

        if (!$isValid) {
            Log::warning('MLM Settings validation failed', [
                'errors' => $errors,
                'warnings' => $warnings,
                'settings_checked' => array_keys($settings),
            ]);
        }

        return [
            'is_valid' => $isValid,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
