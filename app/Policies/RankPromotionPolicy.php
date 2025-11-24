<?php

namespace App\Policies;

use App\Models\RankPromotion;
use App\Models\User;

/**
 * Rank Promotion Authorization Policy
 *
 * ตรวจสอบสิทธิ์การอนุมัติการเลื่อนยศ
 *
 * ⚠️ CRITICAL: การอนุมัติ promotion มีผลต่อ commission rate
 * ของ user และ downline ทั้งหมด
 */
class RankPromotionPolicy
{
    /**
     * ตรวจสอบก่อนการ authorize ทุกครั้ง
     *
     * Super Admin มีสิทธิ์ทุกอย่าง
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return null;
    }

    /**
     * ตรวจสอบสิทธิ์ในการดูรายการ Promotions
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin' || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการดู Promotion
     */
    public function view(User $user, RankPromotion $promotion): bool
    {
        return $user->role === 'admin' || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการอนุมัติ Promotion
     *
     * ⚠️ CRITICAL: การอนุมัติการเลื่อนยศมีผลกระทบสูงมาก
     * - เปลี่ยน commission rate
     * - เปลี่ยน bonus multiplier
     * - มีผลต่อ downline ทั้งหมด
     */
    public function approve(User $user, RankPromotion $promotion): bool
    {
        return $user->role === 'admin' || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการปฏิเสธ Promotion
     */
    public function reject(User $user, RankPromotion $promotion): bool
    {
        return $user->role === 'admin' || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการลบ Promotion record
     */
    public function delete(User $user, RankPromotion $promotion): bool
    {
        return $user->is_super_admin;
    }
}
