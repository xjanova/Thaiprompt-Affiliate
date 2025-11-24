<?php

namespace App\Policies;

use App\Models\NFCCard;
use App\Models\User;

/**
 * NFC Card Authorization Policy
 *
 * ตรวจสอบสิทธิ์การเข้าถึง NFC Cards
 */
class NFCCardPolicy
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
     * ตรวจสอบสิทธิ์ในการดูรายการ NFC Cards
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin' || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการดู NFC Card
     */
    public function view(User $user, NFCCard $nfcCard): bool
    {
        // Admin ดูได้ทั้งหมด
        if ($user->role === 'admin' || $user->is_super_admin) {
            return true;
        }

        // User ธรรมดาดูได้เฉพาะของตัวเอง
        return $nfcCard->user_id === $user->id;
    }

    /**
     * ตรวจสอบสิทธิ์ในการสร้าง NFC Card
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin' || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการแก้ไข NFC Card
     */
    public function update(User $user, NFCCard $nfcCard): bool
    {
        // Admin แก้ไขได้ทั้งหมด
        if ($user->role === 'admin' || $user->is_super_admin) {
            return true;
        }

        // User ธรรมดาแก้ไขได้เฉพาะของตัวเอง
        return $nfcCard->user_id === $user->id;
    }

    /**
     * ตรวจสอบสิทธิ์ในการลบ NFC Card
     *
     * ⚠️ CRITICAL: ป้องกัน IDOR vulnerability
     */
    public function delete(User $user, NFCCard $nfcCard): bool
    {
        // เฉพาะ Admin/Super Admin เท่านั้น
        return $user->role === 'admin' || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการ restore NFC Card
     */
    public function restore(User $user, NFCCard $nfcCard): bool
    {
        return $user->role === 'admin' || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการลบถาวร
     */
    public function forceDelete(User $user, NFCCard $nfcCard): bool
    {
        return $user->is_super_admin;
    }
}
