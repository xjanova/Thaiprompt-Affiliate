<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware ตรวจสอบสถานะ KYC ของผู้ใช้
 *
 * ใช้สำหรับ routes ที่ต้องการให้ผู้ใช้ผ่านการยืนยันตัวตน (KYC) แล้ว
 * เช่น การเปิดร้านค้า, การถอนเงิน, การทำธุรกรรมสำคัญ
 */
class EnsureKycVerified
{
    /**
     * Handle an incoming request.
     *
     * ตรวจสอบว่าผู้ใช้ได้ผ่านการยืนยันตัวตน (KYC) แล้วหรือยัง
     * ถ้ายังไม่ได้ยืนยัน จะ redirect ไปหน้า onboarding
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // ถ้าไม่มี user ให้ไปหน้า login
        if (! $user) {
            return redirect()->route('login');
        }

        // Super Admin และ Admin ข้ามการตรวจสอบ KYC
        if ($user->is_super_admin || in_array($user->role, ['admin', 'super_admin'])) {
            return $next($request);
        }

        // ตรวจสอบสถานะ KYC
        if (! $this->isKycApproved($user)) {
            // Redirect ไปหน้า onboarding ของ seller
            return redirect()->route('seller.onboarding.index')
                ->with('warning', 'กรุณายืนยันตัวตน (KYC) ก่อนเปิดร้านค้า');
        }

        return $next($request);
    }

    /**
     * ตรวจสอบว่า KYC ได้รับการอนุมัติแล้วหรือไม่
     *
     * @param  \App\Models\User  $user
     */
    private function isKycApproved($user): bool
    {
        // ตรวจสอบจาก kyc_status ใน users table
        if ($user->kyc_status === 'approved') {
            return true;
        }

        // ตรวจสอบจาก KycVerification record (กรณี kyc_status ยังไม่ sync)
        if (method_exists($user, 'kycVerifications')) {
            $latestKyc = $user->kycVerifications()
                ->latest()
                ->first();

            if ($latestKyc && $latestKyc->status === 'approved') {
                return true;
            }
        }

        return false;
    }
}
