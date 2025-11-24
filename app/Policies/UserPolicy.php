<?php

namespace App\Policies;

use App\Models\User;

/**
 * User Authorization Policy
 *
 * ตรวจสอบสิทธิ์การจัดการผู้ใช้
 */
class UserPolicy
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
     * ตรวจสอบสิทธิ์ในการดูรายการผู้ใช้
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin' || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการดูข้อมูลผู้ใช้
     */
    public function view(User $user, User $model): bool
    {
        // Admin ดูได้ทั้งหมด
        if ($user->role === 'admin' || $user->is_super_admin) {
            return true;
        }

        // User ดูได้เฉพาะข้อมูลตัวเอง
        return $user->id === $model->id;
    }

    /**
     * ตรวจสอบสิทธิ์ในการสร้างผู้ใช้
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin' || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการแก้ไขผู้ใช้
     */
    public function update(User $user, User $model): bool
    {
        // Admin แก้ไขได้ทั้งหมด
        if ($user->role === 'admin' || $user->is_super_admin) {
            return true;
        }

        // User แก้ไขได้เฉพาะข้อมูลตัวเอง
        return $user->id === $model->id;
    }

    /**
     * ตรวจสอบสิทธิ์ในการลบผู้ใช้
     *
     * ⚠️ CRITICAL: ลบผู้ใช้เป็นการกระทำที่ไม่อาจย้อนกลับได้
     */
    public function delete(User $user, User $model): bool
    {
        // ห้ามลบตัวเอง
        if ($user->id === $model->id) {
            return false;
        }

        // เฉพาะ Admin เท่านั้น
        return $user->role === 'admin' || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการ restore ผู้ใช้
     */
    public function restore(User $user, User $model): bool
    {
        return $user->role === 'admin' || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการลบถาวร
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการเปลี่ยน role
     *
     * ⚠️ CRITICAL: การเปลี่ยน role เป็น privilege escalation
     */
    public function changeRole(User $user, User $model): bool
    {
        // ห้ามเปลี่ยน role ตัวเอง
        if ($user->id === $model->id) {
            return false;
        }

        // เฉพาะ Super Admin เท่านั้น
        return $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการ block/unblock ผู้ใช้
     */
    public function block(User $user, User $model): bool
    {
        // ห้าม block ตัวเอง
        if ($user->id === $model->id) {
            return false;
        }

        // Admin/Super Admin เท่านั้น
        return $user->role === 'admin' || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการอนุมัติ KYC
     */
    public function approveKyc(User $user, User $model): bool
    {
        return $user->role === 'admin' || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการเปลี่ยน permissions
     */
    public function changePermissions(User $user, User $model): bool
    {
        // เฉพาะ Super Admin เท่านั้น
        return $user->is_super_admin;
    }
}
