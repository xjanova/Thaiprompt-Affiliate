<?php

namespace App\Policies;

use App\Models\KycVerification;
use App\Models\User;

/**
 * KYC Verification Authorization Policy
 *
 * ตรวจสอบสิทธิ์การจัดการ KYC Verification
 */
class KycVerificationPolicy
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
     * ตรวจสอบสิทธิ์ในการดูรายการ KYC Verifications
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_kyc_verifications')
            || $user->role === 'admin'
            || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการดู KYC Verification
     */
    public function view(User $user, KycVerification $kycVerification): bool
    {
        // Admin ที่มี permission ดูได้ทั้งหมด
        if ($user->hasPermission('view_kyc_verifications')
            || $user->role === 'admin'
            || $user->is_super_admin) {
            return true;
        }

        // User ดูได้เฉพาะของตัวเอง
        return $kycVerification->user_id === $user->id;
    }

    /**
     * ตรวจสอบสิทธิ์ในการอนุมัติ KYC
     *
     * ⚠️ CRITICAL: การอนุมัติ KYC เป็นเรื่องสำคัญมาก
     */
    public function approve(User $user, KycVerification $kycVerification): bool
    {
        return $user->hasPermission('approve_kyc')
            || $user->role === 'admin'
            || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการปฏิเสธ KYC
     */
    public function reject(User $user, KycVerification $kycVerification): bool
    {
        return $user->hasPermission('approve_kyc')
            || $user->role === 'admin'
            || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการลบ KYC Verification
     *
     * ⚠️ CRITICAL: ป้องกัน IDOR vulnerability
     */
    public function delete(User $user, KycVerification $kycVerification): bool
    {
        // เฉพาะ Admin/Super Admin ที่มี permission เท่านั้น
        return $user->hasPermission('manage_kyc')
            || $user->role === 'admin'
            || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการ restore
     */
    public function restore(User $user, KycVerification $kycVerification): bool
    {
        return $user->role === 'admin' || $user->is_super_admin;
    }

    /**
     * ตรวจสอบสิทธิ์ในการลบถาวร
     */
    public function forceDelete(User $user, KycVerification $kycVerification): bool
    {
        return $user->is_super_admin;
    }
}
