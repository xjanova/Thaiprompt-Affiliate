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
 * 3. ตรวจ PV จากการเคลื่อนไหว (ซื้อสินค้า หรือ ดูดวง) ในเดือนนี้ >= เกณฑ์ → active
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

        // ตรวจ PV จากการเคลื่อนไหวในเดือนปัจจุบัน (ซื้อสินค้า หรือ ดูดวง)
        $requiredMonthlyPv = (float) MlmGlobalSetting::get('volume_retention_monthly_pv', 100);
        $startOfMonth = Carbon::now()->startOfMonth();

        $monthlyPv = $member->pvTransactions()
            ->where('created_at', '>=', $startOfMonth)
            ->whereIn('transaction_type', ['purchase', 'fortune_reading'])
            ->sum('pv_amount');

        if ($monthlyPv >= $requiredMonthlyPv) {
            return true;
        }

        // 🐛 Fix 2026-06-12: นับบิลดูดวงที่จ่ายแล้วในเดือนนี้เป็นการเคลื่อนไหวด้วย
        // (commission mode 'static' ของระบบดูดวงไม่สร้าง pvTransactions →
        // ลูกค้าดูดวงที่เป็นผู้แนะนำเคยถูกนับ inactive ทั้งที่เพิ่งจ่ายเงิน
        // → ค่าแนะนำไม่ถูกจ่าย ขัดกับเกณฑ์ข้อ 3 "ซื้อสินค้า หรือ ดูดวง")
        if (self::hasPaidFortuneReadingSince($member, $startOfMonth)) {
            return true;
        }

        // ตรวจ grace period (ผ่อนผันหลังเคลื่อนไหวล่าสุด — ซื้อสินค้า หรือ ดูดวง)
        $graceDays = (int) MlmGlobalSetting::get('volume_retention_grace_days', 7);

        $lastPurchaseDate = $member->last_purchase_at
            ?? $member->pvTransactions()
                ->whereIn('transaction_type', ['purchase', 'fortune_reading'])
                ->orderBy('created_at', 'desc')
                ->value('created_at');

        if ($lastPurchaseDate) {
            $daysSinceLastPurchase = Carbon::parse($lastPurchaseDate)->diffInDays(now());

            if ($daysSinceLastPurchase <= $graceDays) {
                return true;
            }
        }

        // Grace period จากบิลดูดวงล่าสุด (กรณีไม่มี pvTransactions เลย)
        if (self::hasPaidFortuneReadingSince($member, Carbon::now()->subDays($graceDays))) {
            return true;
        }

        return false;
    }

    /**
     * ตรวจว่าสมาชิกมีบิลดูดวงที่จ่ายเงินแล้วตั้งแต่วันที่กำหนดหรือไม่
     *
     * ใช้เป็นหลักฐานการเคลื่อนไหวสำหรับ commission mode 'static'
     * ที่ไม่มีการบันทึก PV transaction
     *
     * @param  MlmMember  $member  สมาชิกที่ต้องการตรวจ
     * @param  Carbon  $since  นับจากวันที่
     */
    protected static function hasPaidFortuneReadingSince(MlmMember $member, Carbon $since): bool
    {
        if (! $member->user_id) {
            return false;
        }

        return \App\Models\FortuneReading::where('user_id', $member->user_id)
            ->where('is_paid', true)
            ->where('paid_at', '>=', $since)
            ->exists();
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
            ->whereIn('transaction_type', ['purchase', 'fortune_reading'])
            ->sum('pv_amount');

        $lastPurchaseDate = $member->last_purchase_at
            ?? $member->pvTransactions()
                ->whereIn('transaction_type', ['purchase', 'fortune_reading'])
                ->orderBy('created_at', 'desc')
                ->value('created_at');

        $daysSinceLastPurchase = $lastPurchaseDate
            ? Carbon::parse($lastPurchaseDate)->diffInDays(now())
            : null;

        // 🐛 Fix 2026-06-12: นับบิลดูดวงที่จ่ายแล้วเป็นการเคลื่อนไหวด้วย (เหมือน isMemberActive)
        // เพื่อให้สถานะที่แสดง UI ตรงกับเกณฑ์จ่ายคอมมิชชั่นจริง
        $hasFortuneActivityThisMonth = self::hasPaidFortuneReadingSince($member, $startOfMonth);

        // กำหนดสถานะ
        if (! $retentionEnabled) {
            $status = $member->status === 'active' ? 'active' : 'inactive';
            $color = $status === 'active' ? 'green' : 'red';
        } elseif ($member->status !== 'active' || ! $member->is_qualified) {
            $status = 'inactive';
            $color = 'red';
        } elseif ($monthlyPv >= $requiredMonthlyPv || $hasFortuneActivityThisMonth) {
            $status = 'active';
            $color = 'green';
        } elseif ($daysSinceLastPurchase !== null && $daysSinceLastPurchase <= $graceDays) {
            $status = 'grace_period';
            $color = 'yellow';
        } elseif (self::hasPaidFortuneReadingSince($member, Carbon::now()->subDays($graceDays))) {
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
