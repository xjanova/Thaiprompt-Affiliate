<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;

/**
 * Wallet Authorization Policy
 *
 * ตรวจสอบสิทธิ์การเข้าถึง Wallets (ข้อมูลสำคัญมาก!)
 */
class WalletPolicy
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
     * เป็นแอดมินหรือไม่ — รับทั้งคอลัมน์ role (admin/super_admin) แบบที่ด่าน role:admin,super_admin ของ /admin ใช้
     * และ role model (role_id) แบบที่ hasRole() ใช้ (2026-11-06: เดิมเช็คแค่ role === 'admin'
     * แอดมินที่ได้สิทธิ์ผ่าน role model หรือ role = super_admin จึงโดน 403 ตอนคืนเงิน/ย้อน/โอน)
     */
    private function isAdmin(User $user): bool
    {
        return in_array($user->role, ['admin', 'super_admin'], true)
            || $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * ตรวจสอบสิทธิ์ในการดูรายการ Wallets ทั้งหมด
     *
     * ⚠️ SENSITIVE: เฉพาะ Admin ที่มี permission เท่านั้น
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_all_wallets')
            || $this->isAdmin($user)
            || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการดู Wallet
     *
     * ⚠️ CRITICAL: ต้องเป็นเจ้าของหรือ Admin เท่านั้น
     */
    public function view(User $user, Wallet $wallet): bool
    {
        // Admin ที่มี permission ดูได้ทั้งหมด
        if ($user->hasPermission('view_all_wallets')
            || $this->isAdmin($user)
            || $user->is_super_admin) {
            return true;
        }

        // User ธรรมดาดูได้เฉพาะ wallet ของตัวเอง
        return $wallet->user_id === $user->id;
    }

    /**
     * ตรวจสอบสิทธิ์ในการสร้าง Wallet
     */
    public function create(User $user): bool
    {
        // เฉพาะ Admin/System เท่านั้น
        return $this->isAdmin($user) || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการแก้ไข Wallet
     *
     * ⚠️ CRITICAL: Wallet balance ต้องระวังมาก
     */
    public function update(User $user, Wallet $wallet): bool
    {
        // เฉพาะ Admin/Super Admin เท่านั้น
        // User ธรรมดาไม่สามารถแก้ไข balance โดยตรง
        return $this->isAdmin($user) || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการลบ Wallet
     *
     * ⚠️ CRITICAL: ลบ Wallet เป็นเรื่องอันตราย
     */
    public function delete(User $user, Wallet $wallet): bool
    {
        // เฉพาะ Super Admin เท่านั้น
        return $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการ restore Wallet
     */
    public function restore(User $user, Wallet $wallet): bool
    {
        return $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการลบถาวร
     */
    public function forceDelete(User $user, Wallet $wallet): bool
    {
        return $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการดู transactions
     */
    public function viewTransactions(User $user, Wallet $wallet): bool
    {
        // Admin หรือ เจ้าของ wallet
        if ($user->hasPermission('view_all_wallets')
            || $this->isAdmin($user)
            || $user->is_super_admin) {
            return true;
        }

        return $wallet->user_id === $user->id;
    }

    /**
     * ตรวจสอบสิทธิ์ในการเติมเงิน
     */
    public function deposit(User $user, Wallet $wallet): bool
    {
        // เฉพาะ Admin เท่านั้น
        return $this->isAdmin($user) || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการถอนเงิน
     */
    public function withdraw(User $user, Wallet $wallet): bool
    {
        // เฉพาะ Admin เท่านั้น
        return $this->isAdmin($user) || $user->is_super_admin;
    }
}
