<?php

namespace App\Policies;

use App\Models\Rank;
use App\Models\User;

/**
 * Rank Authorization Policy
 *
 * ตรวจสอบสิทธิ์การจัดการระดับชั้น/ยศ (Rank System)
 *
 * ⚠️ CRITICAL: Rank มีผลต่อ commission rate และ bonus multiplier
 */
class RankPolicy
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
     * ตรวจสอบสิทธิ์ในการดูรายการ Ranks
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin' || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการดู Rank
     */
    public function view(User $user, Rank $rank): bool
    {
        return $user->role === 'admin' || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการสร้าง Rank
     *
     * ⚠️ CRITICAL: การสร้าง rank ใหม่มีผลต่อระบบ commission
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin' || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการแก้ไข Rank
     *
     * ⚠️ CRITICAL: การแก้ไข commission_rate และ bonus_multiplier
     * มีผลกระทบโดยตรงต่อรายได้ของ user ทุกคน
     */
    public function update(User $user, Rank $rank): bool
    {
        return $user->role === 'admin' || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการลบ Rank
     *
     * ⚠️ CRITICAL: ลบ rank ได้เฉพาะเมื่อไม่มี user ใช้งาน
     */
    public function delete(User $user, Rank $rank): bool
    {
        return $user->role === 'admin' || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการ restore
     */
    public function restore(User $user, Rank $rank): bool
    {
        return $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการลบถาวร
     */
    public function forceDelete(User $user, Rank $rank): bool
    {
        return $user->is_super_admin;
    }
}
