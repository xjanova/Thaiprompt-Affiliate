<?php

namespace App\Policies;

use App\Models\NFCReader;
use App\Models\User;

/**
 * NFC Reader Authorization Policy
 *
 * ตรวจสอบสิทธิ์การเข้าถึง NFC Readers
 */
class NFCReaderPolicy
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
     * ตรวจสอบสิทธิ์ในการดูรายการ NFC Readers
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin' || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการดู NFC Reader
     */
    public function view(User $user, NFCReader $nfcReader): bool
    {
        // Admin ดูได้ทั้งหมด
        if ($user->role === 'admin' || $user->is_super_admin) {
            return true;
        }

        // Merchant ดูได้เฉพาะของตัวเอง
        return $nfcReader->merchant_id === $user->id;
    }

    /**
     * ตรวจสอบสิทธิ์ในการสร้าง NFC Reader
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin' || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการแก้ไข NFC Reader
     */
    public function update(User $user, NFCReader $nfcReader): bool
    {
        // Admin แก้ไขได้ทั้งหมด
        if ($user->role === 'admin' || $user->is_super_admin) {
            return true;
        }

        // Merchant แก้ไขได้เฉพาะของตัวเอง
        return $nfcReader->merchant_id === $user->id;
    }

    /**
     * ตรวจสอบสิทธิ์ในการลบ NFC Reader
     *
     * ⚠️ CRITICAL: ป้องกัน IDOR vulnerability
     */
    public function delete(User $user, NFCReader $nfcReader): bool
    {
        // เฉพาะ Admin/Super Admin เท่านั้น
        return $user->role === 'admin' || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการ restore NFC Reader
     */
    public function restore(User $user, NFCReader $nfcReader): bool
    {
        return $user->role === 'admin' || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการลบถาวร
     */
    public function forceDelete(User $user, NFCReader $nfcReader): bool
    {
        return $user->is_super_admin;
    }
}
