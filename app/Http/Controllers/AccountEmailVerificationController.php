<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AccountEmailChangeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ✉️ (2026-10-29) ลิงก์ยืนยันอีเมลบัญชี — GET /account/email/verify/{id}/{hash} (route: account.email.verify)
 *
 * ไม่บังคับล็อกอิน: ลูกค้ามักเปิดลิงก์จากแอปอีเมลบนมือถือที่ไม่ได้ล็อกอินไว้
 * ปลอดภัยเพราะลิงก์เป็น signed URL (หมดอายุ 24 ชม.) และ hash ต้องตรงกับอีเมลปัจจุบันของบัญชี
 * (ลิงก์ไปถึงแค่กล่องอีเมลใหม่ — ใครเปิดได้ก็คือเจ้าของกล่องนั้น)
 */
class AccountEmailVerificationController extends Controller
{
    public function verify(Request $request, int $id, string $hash)
    {
        $back = Auth::check() ? route('user.profile') : route('login');

        // ตรวจลายเซ็นเองแทน middleware signed — ลิงก์หมดอายุจะได้ข้อความไทย ไม่ใช่หน้า 403
        if (! $request->hasValidSignature()) {
            return redirect($back)->with('error', 'ลิงก์ยืนยันอีเมลหมดอายุหรือไม่ถูกต้อง กรุณาขอลิงก์ใหม่ที่หน้าโปรไฟล์');
        }

        $user = User::find($id);

        if (! $user || ! hash_equals(AccountEmailChangeService::verificationHash($user), $hash)) {
            return redirect($back)->with('error', 'ลิงก์นี้ใช้ไม่ได้แล้ว เพราะอีเมลบัญชีถูกเปลี่ยนอีกครั้ง กรุณาขอลิงก์ใหม่ที่หน้าโปรไฟล์');
        }

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return redirect($back)->with('success', 'ยืนยันอีเมลเรียบร้อยแล้ว');
    }
}
