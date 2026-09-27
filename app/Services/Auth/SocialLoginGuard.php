<?php

namespace App\Services\Auth;

use App\Exceptions\SocialLoginRefusedException;
use App\Http\Middleware\EnsureAccountActive;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * กติกาความปลอดภัยของการเข้าสู่ระบบด้วย Facebook / Google (ทั้งเว็บและแอป)
 *
 * 1. ผูกบัญชีเดิมด้วยอีเมลอัตโนมัติ ได้เฉพาะเมื่อบัญชีเรา "ยืนยันอีเมลแล้ว" และ "ไม่ใช่แอดมิน"
 *    ไม่งั้นใครก็สมัคร/แก้โปรไฟล์ใส่อีเมลเหยื่อไว้ก่อน พอเหยื่อกด Google/Facebook
 *    ตัวตนของเหยื่อจะถูกผูกเข้าบัญชีคนร้าย (prod: 100 บัญชีมีอีเมลจริง ยืนยันแล้วแค่ 6)
 *    → ไม่เข้าเงื่อนไข = ปฏิเสธ (ไม่ผูก ไม่สร้างบัญชีใหม่) ให้เข้าด้วยอีเมล+รหัสผ่าน
 * 2. บัญชีถูกระงับ = เข้าไม่ได้
 * 3. บัญชีที่ต้องยืนยันตัวตน 2 ขั้นตอนตอนล็อกอิน = ห้ามเข้าทางลัด ต้องเข้าด้วยรหัสผ่าน (กัน 2FA ถูกข้าม)
 */
class SocialLoginGuard
{
    public const EMAIL_TAKEN_MESSAGE = 'มีบัญชีที่ใช้อีเมลนี้อยู่แล้ว กรุณาเข้าสู่ระบบด้วยอีเมลและรหัสผ่าน';

    public const TWO_FACTOR_MESSAGE = 'บัญชีนี้เปิดยืนยันตัวตน 2 ขั้นตอน กรุณาเข้าสู่ระบบด้วยรหัสผ่าน';

    public const CHECK_FAILED_MESSAGE = 'ตรวจสอบบัญชีไม่สำเร็จ กรุณาลองใหม่อีกครั้ง หรือเข้าสู่ระบบด้วยอีเมลและรหัสผ่าน';

    /**
     * บัญชีผู้ดูแลระบบ (เกณฑ์เดียวกับ middleware role: และการลบบัญชี)
     */
    public function isPrivileged(User $user): bool
    {
        return (bool) $user->is_super_admin
            || in_array($user->role, ['admin', 'super_admin'], true);
    }

    /**
     * ผูกบัญชีนี้กับ Facebook/Google ด้วยอีเมลอัตโนมัติได้ไหม
     */
    public function canAutoLinkByEmail(User $user): bool
    {
        return $user->email_verified_at !== null && ! $this->isPrivileged($user);
    }

    /**
     * ไม่เข้าเงื่อนไขผูกด้วยอีเมล → ปฏิเสธ
     *
     * @throws SocialLoginRefusedException
     */
    public function assertCanAutoLinkByEmail(User $user, string $provider): void
    {
        if ($this->canAutoLinkByEmail($user)) {
            return;
        }

        Log::warning("Social login ({$provider}): ปฏิเสธผูกบัญชีด้วยอีเมล", [
            'user_id' => $user->id,
            'email_verified' => $user->email_verified_at !== null,
            'privileged' => $this->isPrivileged($user),
        ]);

        throw new SocialLoginRefusedException(self::EMAIL_TAKEN_MESSAGE, 'email_taken');
    }

    /**
     * บัญชีนี้เข้าสู่ระบบด้วย Facebook/Google ได้ไหม (ระงับ / 2FA)
     *
     * ตรวจ 2FA ไม่สำเร็จ (DB มีปัญหา) = ปฏิเสธไว้ก่อน (fail closed)
     *
     * @throws SocialLoginRefusedException
     */
    public function assertCanSignIn(User $user, string $provider): void
    {
        if ($user->isSuspended()) {
            throw new SocialLoginRefusedException(EnsureAccountActive::SUSPENDED_MESSAGE, 'suspended');
        }

        try {
            $twoFactorRequired = app(TwoFactorService::class)->isRequired('login', $user);
        } catch (Throwable $e) {
            Log::error("Social login ({$provider}): ตรวจ 2FA ไม่สำเร็จ — ปฏิเสธไว้ก่อน", [
                'user_id' => $user->id,
                'exception' => class_basename($e),
            ]);

            throw new SocialLoginRefusedException(self::CHECK_FAILED_MESSAGE, 'two_factor_check_failed');
        }

        if ($twoFactorRequired) {
            Log::info("Social login ({$provider}): บัญชีต้องใช้ 2FA — ให้เข้าด้วยรหัสผ่าน", ['user_id' => $user->id]);

            throw new SocialLoginRefusedException(self::TWO_FACTOR_MESSAGE, 'two_factor');
        }
    }
}
