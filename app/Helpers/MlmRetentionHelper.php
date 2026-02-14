<?php

namespace App\Helpers;

use App\Models\MlmGlobalSetting;
use App\Models\MlmMember;
use Carbon\Carbon;

/**
 * MlmRetentionHelper - ตรวจสอบสถานะรักษายอดของสมาชิก MLM
 *
 * ⚠️ Helper กลางที่ทุก Service ต้องใช้ตัวเดียวกัน
 * เพื่อให้เกณฑ์ตรวจสอบ active status เป็นมาตรฐานเดียว
 *
 * เกณฑ์:
 * 1. ถ้า volume_retention_enabled = false → ถือว่า active ทุกคน
 * 2. ถ้า member มี status != 'active' หรือ is_qualified = false → ไม่ active
 * 3. ตรวจ PV จากการซื้อส่วนตัวในเดือนนี้ >= เกณฑ์ที่กำหนด → active
 * 4. ถ้ายังอยู่ใน grace period → ถือว่า active ชั่วคราว
 */
class MlmRetentionHelper
{
    /**
     * ตรวจสอบว่าสมาชิก active หรือไม่ (ใช้ร่วมกันทุก Service)
     *
     * @param  MlmMember  $member  สมาชิกที่ต้องการตรวจ
     * @return bool true = active, false = ไม่รักษายอด
     */
    public static function isMemberActive(MlmMember $member): bool
    {
        // ตรวจว่าระบบรักษายอดเปิดอยู่หรือไม่
        $retentionEnabled = MlmGlobalSetting::get('volume_retention_enabled', true);

        if (! $retentionEnabled) {
            // ระบบรักษายอดปิด → ถือว่า active ทุกคน (ยกเว้น suspended/inactive)
            return $member->status === 'active';
        }

        // ตรวจสถานะ static ก่อน (ถ้าถูก admin ปิด manual → ไม่ active ทันที)
        if ($member->status !== 'active' || ! $member->is_qualified) {
            return false;
        }

        // ตรวจ PV จากการซื้อส่วนตัวในเดือนปัจจุบัน
        $requiredMonthlyPv = (float) MlmGlobalSetting::get('volume_retention_monthly_pv', 100);
        $startOfMonth = Carbon::now()->startOfMonth();

        $monthlyPv = $member->pvTransactions()
            ->where('created_at', '>=', $startOfMonth)
            ->where('transaction_type', 'purchase')
            ->sum('pv_amount');

        if ($monthlyPv >= $requiredMonthlyPv) {
            return true;
        }

        // ตรวจ grace period (ผ่อนผันหลังซื้อล่าสุด)
        $graceDays = (int) MlmGlobalSetting::get('volume_retention_grace_days', 7);

        $lastPurchaseDate = $member->last_purchase_at
            ?? $member->pvTransactions()
                ->where('transaction_type', 'purchase')
                ->orderBy('created_at', 'desc')
                ->value('created_at');

        if ($lastPurchaseDate) {
            $daysSinceLastPurchase = Carbon::parse($lastPurchaseDate)->diffInDays(now());

            if ($daysSinceLastPurchase <= $graceDays) {
                return true;
            }
        }

        return false;
    }

    /**
     * ดึงข้อมูลสถานะรักษายอดแบบละเอียด (สำหรับแสดง UI)
     *
     * @param  MlmMember  $member  สมาชิก
     * @return array ข้อมูลสถานะ
     */
    public static function getRetentionStatus(MlmMember $member): array
    {
        $retentionEnabled = MlmGlobalSetting::get('volume_retention_enabled', true);
        $requiredMonthlyPv = (float) MlmGlobalSetting::get('volume_retention_monthly_pv', 100);
        $graceDays = (int) MlmGlobalSetting::get('volume_retention_grace_days', 7);

        $startOfMonth = Carbon::now()->startOfMonth();
        $monthlyPv = $member->pvTransactions()
            ->where('created_at', '>=', $startOfMonth)
            ->where('transaction_type', 'purchase')
            ->sum('pv_amount');

        $lastPurchaseDate = $member->last_purchase_at
            ?? $member->pvTransactions()
                ->where('transaction_type', 'purchase')
                ->orderBy('created_at', 'desc')
                ->value('created_at');

        $daysSinceLastPurchase = $lastPurchaseDate
            ? Carbon::parse($lastPurchaseDate)->diffInDays(now())
            : null;

        // กำหนดสถานะ
        if (! $retentionEnabled) {
            $status = $member->status === 'active' ? 'active' : 'inactive';
            $color = $status === 'active' ? 'green' : 'red';
        } elseif ($member->status !== 'active' || ! $member->is_qualified) {
            $status = 'inactive';
            $color = 'red';
        } elseif ($monthlyPv >= $requiredMonthlyPv) {
            $status = 'active';
            $color = 'green';
        } elseif ($daysSinceLastPurchase !== null && $daysSinceLastPurchase <= $graceDays) {
            $status = 'grace_period';
            $color = 'yellow';
        } else {
            $status = 'inactive';
            $color = 'red';
        }

        return [
            'retention_enabled' => $retentionEnabled,
            'status' => $status,
            'color' => $color,
            'is_active' => self::isMemberActive($member),
            'monthly_pv' => round($monthlyPv, 2),
            'required_pv' => $requiredMonthlyPv,
            'pv_percentage' => $requiredMonthlyPv > 0
                ? round(min($monthlyPv / $requiredMonthlyPv * 100, 100), 1)
                : 100,
            'remaining_pv' => max(0, round($requiredMonthlyPv - $monthlyPv, 2)),
            'days_since_last_purchase' => $daysSinceLastPurchase,
            'grace_days' => $graceDays,
            'in_grace_period' => $status === 'grace_period',
            'member_status' => $member->status,
            'is_qualified' => $member->is_qualified,
        ];
    }
}
