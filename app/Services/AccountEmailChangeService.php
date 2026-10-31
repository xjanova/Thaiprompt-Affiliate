<?php

namespace App\Services;

use App\Mail\AccountEmailChangedMail;
use App\Mail\VerifyAccountEmailMail;
use App\Models\User;
use App\Support\AccountEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/**
 * 🔒 (2026-10-29) เปลี่ยนอีเมลบัญชีต้องยืนยันตัวตนก่อน — ปิดทางยึดบัญชีด้วย session ที่ถูกขโมย
 *
 * เดิม: PUT /user/profile เปลี่ยนอีเมลได้ทันที ไม่ถามรหัสผ่าน ไม่รีเซ็ตสถานะยืนยัน ไม่แจ้งอีเมลเดิม
 *   → คนที่ได้ session ไปย้ายบัญชีเข้าอีเมลตัวเอง แล้วกด "ลืมรหัสผ่าน" ยึดบัญชีได้ถาวร
 *
 * ระบบไม่มีป้ายบอกว่าบัญชีไหน "มีรหัสผ่านที่ใช้ได้จริง" (สมัครผ่าน LINE/Facebook/Google ได้รหัสผ่านสุ่ม)
 * จึงแยกตามอีเมลเดิมแทน:
 *   - อีเมลเดิมเป็นอีเมลจริง (MODE_PASSWORD) → ใส่รหัสผ่านปัจจุบัน หรือรหัส 6 หลักที่ส่งไป "อีเมลเดิม"
 *       (กล่องเดิมคือช่องรีเซ็ตรหัสผ่านอยู่แล้ว จึงแข็งเท่ารหัสผ่าน — บัญชี Google ที่ไม่รู้รหัสใช้ทางนี้)
 *       ⚠️ ห้ามให้ใช้รหัสที่ส่งไป "อีเมลใหม่" แทนรหัสผ่าน — คนขโมย session จะเลือกทางนั้นแล้วข้ามรหัสผ่านได้
 *       สำเร็จ → email_verified_at = null + ส่งลิงก์ยืนยันไปอีเมลใหม่ + แจ้งเตือนอีเมลเดิม
 *   - อีเมลเดิมเป็นอีเมลสังเคราะห์ของระบบ (MODE_NEW_EMAIL_CODE — บัญชี LINE/FB ที่ไม่เคยมีรหัสผ่าน)
 *       → รหัส 6 หลักที่ส่งไป "อีเมลใหม่" ; สำเร็จ = พิสูจน์กล่องใหม่แล้ว → email_verified_at = now()
 *
 * รหัสผูกกับบัญชี + อีเมลใหม่ที่ขอ (purpose) — ขอรหัสสำหรับอีเมลหนึ่ง เอาไปเปลี่ยนเป็นอีเมลอื่นไม่ได้
 */
class AccountEmailChangeService
{
    public const MODE_PASSWORD = 'password';

    public const MODE_NEW_EMAIL_CODE = 'new_email_code';

    /** ลองรหัสผ่านผิดได้กี่ครั้งต่อช่วงเวลา (กันเดารหัสผ่านผ่านฟอร์มโปรไฟล์ด้วย session ที่ขโมยมา) */
    private const MAX_PASSWORD_ATTEMPTS = 5;

    private const PASSWORD_DECAY_SECONDS = 900;

    /** ลิงก์ยืนยันอีเมลใหม่ใช้ได้กี่ชั่วโมง */
    public const VERIFY_LINK_HOURS = 24;

    public function __construct(private readonly EmailOtpService $otp) {}

    /** บัญชีนี้ยืนยันการเปลี่ยนอีเมลด้วยวิธีไหน */
    public function mode(User $user): string
    {
        return AccountEmail::isDeliverable($user->email) ? self::MODE_PASSWORD : self::MODE_NEW_EMAIL_CODE;
    }

    /** ค่าที่ส่งมาคือการเปลี่ยนอีเมลจริงไหม (ต่างแค่ตัวพิมพ์เล็ก/ใหญ่ = กล่องเดิม ไม่นับ) */
    public function isChange(User $user, mixed $newEmail): bool
    {
        return is_string($newEmail) && trim($newEmail) !== '' && ! AccountEmail::same($user->email, $newEmail);
    }

    /**
     * ส่งรหัส 6 หลักสำหรับเปลี่ยนอีเมล — MODE_PASSWORD ส่งไปอีเมลเดิม, MODE_NEW_EMAIL_CODE ส่งไปอีเมลใหม่
     *
     * @return array{success: bool, message: string, sent_to?: string}
     */
    public function sendCode(User $user, string $newEmail): array
    {
        $newEmail = trim($newEmail);
        $target = $this->mode($user) === self::MODE_PASSWORD ? (string) $user->email : $newEmail;

        $result = $this->otp->sendOtp($target, $this->purpose($user, $newEmail), [
            'มีคำขอเปลี่ยนอีเมลของบัญชี '.config('app.name').' ('.$user->name.')',
            'อีเมลใหม่ที่ขอ: '.$newEmail,
            $target === $newEmail
                ? 'กรอกรหัสด้านล่างในหน้าโปรไฟล์เพื่อยืนยันว่าอีเมลนี้เป็นของคุณ'
                : 'ถ้าคุณไม่ได้ขอเปลี่ยนอีเมล อย่าบอกรหัสนี้กับใคร และเปลี่ยนรหัสผ่านทันที',
        ]);

        if ($result['success'] ?? false) {
            $result['message'] = 'ส่งรหัสยืนยันไปที่ '.AccountEmail::mask($target).' แล้ว (หมดอายุใน 10 นาที)';
            $result['sent_to'] = AccountEmail::mask($target);
        }

        return $result;
    }

    /**
     * ตรวจหลักฐานยืนยันตัวตนก่อนเปลี่ยนอีเมล
     *
     * @return array{field: string, message: string}|null null = ผ่าน
     */
    public function confirmationError(User $user, string $newEmail, mixed $password, mixed $code): ?array
    {
        $password = is_string($password) ? $password : '';
        $code = is_string($code) ? preg_replace('/\D/', '', $code) : '';

        if ($this->mode($user) === self::MODE_PASSWORD) {
            if ($password !== '') {
                return $this->passwordError($user, $password);
            }

            if ($code !== '') {
                return $this->codeError($user, (string) $user->email, $newEmail, $code);
            }

            return [
                'field' => 'email_password',
                'message' => 'กรุณากรอกรหัสผ่านปัจจุบันเพื่อยืนยันการเปลี่ยนอีเมล (หรือขอรหัสยืนยันทางอีเมลเดิม)',
            ];
        }

        if ($code === '') {
            return [
                'field' => 'email_code',
                'message' => 'กรุณากดส่งรหัสยืนยันไปที่อีเมลใหม่ แล้วกรอกรหัส 6 หลักที่ได้รับ',
            ];
        }

        return $this->codeError($user, $newEmail, $newEmail, $code);
    }

    /**
     * หลังบันทึกอีเมลใหม่แล้ว: ตั้งสถานะยืนยัน + ส่งลิงก์ยืนยันไปอีเมลใหม่ + แจ้งอีเมลเดิม
     *
     * @param  string  $mode  โหมดของบัญชี "ก่อน" เปลี่ยน (ดูจากอีเมลเดิม)
     */
    public function completeChange(User $user, string $oldEmail, string $mode): void
    {
        if ($mode === self::MODE_NEW_EMAIL_CODE) {
            // กรอกรหัสที่ส่งไปอีเมลใหม่ถูกแล้ว = เป็นเจ้าของกล่องนี้จริง
            $user->forceFill(['email_verified_at' => now()])->save();
        } else {
            // ยังไม่ได้พิสูจน์กล่องใหม่ — ต้องไม่ถือว่ายืนยันแล้ว (Google login ผูกบัญชีจากอีเมลที่ยืนยันแล้วเท่านั้น)
            $user->forceFill(['email_verified_at' => null])->save();
            $this->sendVerificationLink($user);
        }

        if (AccountEmail::isDeliverable($oldEmail) && ! AccountEmail::same($oldEmail, $user->email)) {
            try {
                Mail::to($oldEmail)->send(new AccountEmailChangedMail($user, $oldEmail));
            } catch (\Throwable $e) {
                Log::warning('Email-changed notice to old address failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }
        }

        Log::info('🔒 Account email changed', ['user_id' => $user->id, 'mode' => $mode]);
    }

    /** ส่งลิงก์ยืนยันไปที่อีเมลปัจจุบันของบัญชี (อีเมลระบบ/ยืนยันแล้ว = ไม่ส่ง) */
    public function sendVerificationLink(User $user): bool
    {
        if ($user->email_verified_at || ! AccountEmail::isDeliverable($user->email)) {
            return false;
        }

        try {
            Mail::to($user->email)->send(new VerifyAccountEmailMail($user));

            return true;
        } catch (\Throwable $e) {
            Log::warning('Account email verification link failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /** hash ที่ฝังในลิงก์ยืนยัน — อีเมลเปลี่ยนอีกรอบ ลิงก์เก่าใช้ไม่ได้ทันที */
    public static function verificationHash(User $user): string
    {
        return sha1(mb_strtolower(trim((string) $user->email)));
    }

    /** รหัสผูกกับบัญชีนี้ + อีเมลใหม่ที่ขอ */
    private function purpose(User $user, string $newEmail): string
    {
        return 'email_change:'.$user->id.':'.substr(hash('sha256', mb_strtolower(trim($newEmail))), 0, 16);
    }

    private function passwordError(User $user, string $password): ?array
    {
        $key = 'email-change-password:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, self::MAX_PASSWORD_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

            return [
                'field' => 'email_password',
                'message' => "ใส่รหัสผ่านผิดหลายครั้งเกินไป กรุณารอ {$minutes} นาทีแล้วลองใหม่",
            ];
        }

        if (! Hash::check($password, (string) $user->getAuthPassword())) {
            RateLimiter::hit($key, self::PASSWORD_DECAY_SECONDS);

            return ['field' => 'email_password', 'message' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง'];
        }

        RateLimiter::clear($key);

        return null;
    }

    private function codeError(User $user, string $sentTo, string $newEmail, string $code): ?array
    {
        $result = $this->otp->verifyOtp($sentTo, $code, $this->purpose($user, $newEmail));

        if ($result['valid'] ?? false) {
            return null;
        }

        // รหัสที่ขอไว้สำหรับอีเมลอื่น → บอกให้ขอใหม่ (ข้อความเดิมของ service ไม่ชัดสำหรับลูกค้า)
        $message = ($result['reason'] ?? null) === 'purpose_mismatch'
            ? 'รหัสนี้ขอไว้สำหรับอีเมลอื่น กรุณากดส่งรหัสใหม่สำหรับอีเมลนี้'
            : (string) ($result['message'] ?? 'รหัสยืนยันไม่ถูกต้อง');

        return ['field' => 'email_code', 'message' => $message];
    }
}
