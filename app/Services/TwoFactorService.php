<?php

namespace App\Services;

use App\Models\TwoFactorSetting;
use App\Models\TwoFactorUserSetting;
use App\Models\User;
use Illuminate\Support\Facades\Session;

/**
 * TwoFactorService - บริการจัดการ Two-Factor Authentication
 *
 * รองรับการยืนยันตัวตนผ่าน:
 * - Google Authenticator (TOTP) - หลัก
 * - SMS OTP - สำรอง
 * - LINE OTP - สำรอง
 */
class TwoFactorService
{
    private OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Check if 2FA is required for specific action
     */
    public function isRequired(string $action, User $user, ?float $amount = null): bool
    {
        // Check global 2FA settings
        if (! TwoFactorSetting::isRequiredFor($action, $user, $amount)) {
            return false;
        }

        // Check user's 2FA settings
        $userSettings = TwoFactorUserSetting::where('user_id', $user->id)->first();

        if (! $userSettings || ! $userSettings->enabled) {
            // If 2FA is required but user hasn't set it up, they must set it up
            return false;
        }

        // Check if user is within grace period
        if ($userSettings->isWithinGracePeriod()) {
            return false;
        }

        // Check if device is trusted
        $deviceFingerprint = $this->getDeviceFingerprint();
        if ($deviceFingerprint && $userSettings->isTrustedDevice($deviceFingerprint)) {
            return false;
        }

        return true;
    }

    /**
     * Verify 2FA code - รองรับทั้ง Google Authenticator และ OTP
     */
    public function verifyCode(User $user, string $code, string $action = 'verification', bool $rememberDevice = false): array
    {
        $userSettings = TwoFactorUserSetting::where('user_id', $user->id)->first();

        if (! $userSettings || ! $userSettings->enabled) {
            return [
                'success' => false,
                'message' => '2FA ยังไม่ได้เปิดใช้งาน',
            ];
        }

        // ตรวจสอบตามวิธีที่เลือก
        $preferredMethod = $userSettings->preferred_method;
        $isValid = false;

        if ($preferredMethod === 'authenticator') {
            // ใช้ Google Authenticator (TOTP)
            $isValid = $userSettings->verifyGoogle2FACode($code);
        } else {
            // ใช้ OTP (SMS/LINE/Email)
            $result = $this->otpService->verifyOTPForUser($user, $code, '2fa_'.$action);
            $isValid = $result['success'] ?? false;
        }

        if (! $isValid) {
            // Record failed attempt
            $userSettings->recordFailedVerification();

            return [
                'success' => false,
                'message' => 'รหัสยืนยันไม่ถูกต้อง กรุณาลองใหม่',
            ];
        }

        // Record successful verification
        $userSettings->recordSuccessfulVerification();

        // Remember device if requested
        if ($rememberDevice && TwoFactorSetting::canRememberDevice()) {
            $deviceFingerprint = $this->getDeviceFingerprint();
            if ($deviceFingerprint) {
                $userSettings->addTrustedDevice($deviceFingerprint, $this->getDeviceName());
            }
        }

        // Clear verification session
        Session::forget('2fa_verification_pending');

        // Mark as verified in session
        Session::put('2fa_verified_at', now()->toDateTimeString());
        Session::put('2fa_verified_for', $action);

        return [
            'success' => true,
            'message' => 'ยืนยันตัวตนสำเร็จ',
        ];
    }

    /**
     * Send 2FA code to user (สำหรับ OTP methods เท่านั้น)
     */
    public function sendCode(User $user, string $action = 'verification'): array
    {
        $userSettings = TwoFactorUserSetting::getOrCreateForUser($user);

        if (! $userSettings->enabled) {
            return [
                'success' => false,
                'message' => '2FA ยังไม่ได้เปิดใช้งาน',
            ];
        }

        // ถ้าใช้ Google Authenticator ไม่ต้องส่ง code
        if ($userSettings->preferred_method === 'authenticator') {
            return [
                'success' => true,
                'message' => 'กรุณากรอกรหัสจากแอป Google Authenticator',
                'method' => 'authenticator',
            ];
        }

        // Check if user has at least one verified method
        $availableMethods = $userSettings->getAvailableMethods();

        if (empty($availableMethods)) {
            return [
                'success' => false,
                'message' => 'ไม่มีวิธี 2FA ที่ใช้ได้ กรุณาตั้งค่า 2FA ก่อน',
            ];
        }

        // Use preferred method
        $channel = $userSettings->preferred_method;

        // Fallback to first available method if preferred is not available
        if (! in_array($channel, $availableMethods)) {
            $channel = $availableMethods[0];
        }

        // Send OTP
        $result = $this->otpService->sendOTPToUser($user, '2fa_'.$action, $channel);

        if ($result['success']) {
            // Store verification session
            Session::put('2fa_verification_pending', [
                'user_id' => $user->id,
                'action' => $action,
                'channel' => $channel,
                'expires_at' => now()->addMinutes(15)->toDateTimeString(),
            ]);
        }

        return $result;
    }

    /**
     * Verify recovery code
     */
    public function verifyRecoveryCode(User $user, string $code, string $action = 'verification'): array
    {
        $userSettings = TwoFactorUserSetting::where('user_id', $user->id)->first();

        if (! $userSettings) {
            return [
                'success' => false,
                'message' => '2FA ยังไม่ได้ตั้งค่า',
            ];
        }

        if (! $userSettings->verifyRecoveryCode($code)) {
            return [
                'success' => false,
                'message' => 'รหัสกู้คืนไม่ถูกต้อง',
            ];
        }

        // Record successful verification
        $userSettings->recordSuccessfulVerification();

        // Clear verification session
        Session::forget('2fa_verification_pending');

        // Mark as verified in session
        Session::put('2fa_verified_at', now()->toDateTimeString());
        Session::put('2fa_verified_for', $action);

        return [
            'success' => true,
            'message' => 'ยืนยันด้วยรหัสกู้คืนสำเร็จ',
            'warning' => 'รหัสกู้คืนนี้ถูกใช้แล้วและไม่สามารถใช้ได้อีก',
        ];
    }

    /**
     * Enable 2FA for user with Google Authenticator
     */
    public function enable(User $user, string $preferredMethod = 'authenticator'): array
    {
        $userSettings = TwoFactorUserSetting::getOrCreateForUser($user);

        // ถ้าเลือก Google Authenticator
        if ($preferredMethod === 'authenticator') {
            // สร้าง secret key ถ้ายังไม่มี
            if (! $userSettings->hasGoogle2FASecret()) {
                $secret = $userSettings->generateGoogle2FASecret();
            }

            $userSettings->enabled = true;
            $userSettings->preferred_method = 'authenticator';

            // Generate recovery codes
            $recoveryCodes = $userSettings->generateRecoveryCodes();

            $userSettings->save();

            return [
                'success' => true,
                'message' => 'เปิดใช้งาน 2FA สำเร็จ',
                'recovery_codes' => $recoveryCodes,
                'secret' => $userSettings->getGoogle2FASecret(),
                'qr_code_svg' => $userSettings->getGoogle2FAQRCodeSVG(),
            ];
        }

        // วิธีอื่น (SMS, LINE, Email)
        if (! $userSettings->isMethodAvailable($preferredMethod)) {
            return [
                'success' => false,
                'message' => 'กรุณายืนยัน '.$preferredMethod.' ก่อนเปิดใช้งาน 2FA',
            ];
        }

        $userSettings->enabled = true;
        $userSettings->preferred_method = $preferredMethod;

        // Generate recovery codes
        $recoveryCodes = $userSettings->generateRecoveryCodes();

        $userSettings->save();

        return [
            'success' => true,
            'message' => 'เปิดใช้งาน 2FA สำเร็จ',
            'recovery_codes' => $recoveryCodes,
        ];
    }

    /**
     * Generate Google Authenticator setup data
     */
    public function generateAuthenticatorSetup(User $user): array
    {
        $userSettings = TwoFactorUserSetting::getOrCreateForUser($user);

        // สร้าง secret key ใหม่
        $secret = $userSettings->generateGoogle2FASecret();

        return [
            'success' => true,
            'secret' => $secret,
            'qr_code_svg' => $userSettings->getGoogle2FAQRCodeSVG(),
            'qr_code_url' => $userSettings->getGoogle2FAQRCodeUrl(),
        ];
    }

    /**
     * Verify initial setup code (ใช้ตอนเปิดใช้งาน 2FA ครั้งแรก)
     */
    public function verifySetupCode(User $user, string $code): array
    {
        $userSettings = TwoFactorUserSetting::getOrCreateForUser($user);

        if (! $userSettings->hasGoogle2FASecret()) {
            return [
                'success' => false,
                'message' => 'กรุณาสร้าง QR Code ก่อน',
            ];
        }

        if (! $userSettings->verifyGoogle2FACode($code)) {
            return [
                'success' => false,
                'message' => 'รหัสไม่ถูกต้อง กรุณาตรวจสอบรหัสจากแอป Google Authenticator',
            ];
        }

        return [
            'success' => true,
            'message' => 'รหัสถูกต้อง',
        ];
    }

    /**
     * Disable 2FA for user
     */
    public function disable(User $user): array
    {
        $userSettings = TwoFactorUserSetting::where('user_id', $user->id)->first();

        if (! $userSettings) {
            return [
                'success' => false,
                'message' => '2FA ยังไม่ได้เปิดใช้งาน',
            ];
        }

        $userSettings->enabled = false;
        $userSettings->trusted_devices = [];
        $userSettings->recovery_codes = null;
        $userSettings->removeGoogle2FASecret();
        $userSettings->save();

        return [
            'success' => true,
            'message' => 'ปิดใช้งาน 2FA สำเร็จ',
        ];
    }

    /**
     * Get device fingerprint
     */
    private function getDeviceFingerprint(): ?string
    {
        $userAgent = request()->userAgent();
        $ip = request()->ip();

        if (! $userAgent) {
            return null;
        }

        return hash('sha256', $userAgent.'|'.$ip);
    }

    /**
     * Get device name from user agent
     */
    private function getDeviceName(): string
    {
        $userAgent = request()->userAgent();

        if (! $userAgent) {
            return 'อุปกรณ์ที่ไม่รู้จัก';
        }

        // Simple device detection
        if (stripos($userAgent, 'iPhone') !== false) {
            return 'iPhone';
        } elseif (stripos($userAgent, 'iPad') !== false) {
            return 'iPad';
        } elseif (stripos($userAgent, 'Android') !== false) {
            return 'อุปกรณ์ Android';
        } elseif (stripos($userAgent, 'Windows') !== false) {
            return 'คอมพิวเตอร์ Windows';
        } elseif (stripos($userAgent, 'Mac') !== false) {
            return 'Mac';
        } elseif (stripos($userAgent, 'Linux') !== false) {
            return 'คอมพิวเตอร์ Linux';
        }

        return 'อุปกรณ์ที่ไม่รู้จัก';
    }

    /**
     * Check if current session has passed 2FA
     */
    public function isVerified(?string $action = null): bool
    {
        $verifiedAt = Session::get('2fa_verified_at');

        if (! $verifiedAt) {
            return false;
        }

        // Check if verification has expired
        $gracePeriod = TwoFactorSetting::getGracePeriod();
        $verifiedTime = \Carbon\Carbon::parse($verifiedAt);

        if ($verifiedTime->addMinutes($gracePeriod)->isPast()) {
            Session::forget('2fa_verified_at');
            Session::forget('2fa_verified_for');

            return false;
        }

        // Check if verified for specific action
        if ($action) {
            $verifiedFor = Session::get('2fa_verified_for');

            return $verifiedFor === $action;
        }

        return true;
    }

    /**
     * Clear 2FA verification from session
     */
    public function clearVerification(): void
    {
        Session::forget('2fa_verified_at');
        Session::forget('2fa_verified_for');
        Session::forget('2fa_verification_pending');
    }

    /**
     * Get user's 2FA status
     */
    public function getStatus(User $user): array
    {
        $userSettings = TwoFactorUserSetting::where('user_id', $user->id)->first();

        if (! $userSettings) {
            return [
                'enabled' => false,
                'preferred_method' => 'authenticator',
                'methods' => [],
                'has_recovery_codes' => false,
                'has_authenticator' => false,
                'trusted_devices' => [],
            ];
        }

        return [
            'enabled' => $userSettings->enabled,
            'preferred_method' => $userSettings->preferred_method,
            'available_methods' => $userSettings->getAvailableMethods(),
            'has_recovery_codes' => $userSettings->hasRecoveryCodes(),
            'recovery_codes_count' => $userSettings->recovery_codes ? count($userSettings->recovery_codes) : 0,
            'has_authenticator' => $userSettings->hasGoogle2FASecret(),
            'trusted_devices' => $userSettings->trusted_devices ?? [],
            'total_verifications' => $userSettings->total_verifications,
            'last_verified_at' => $userSettings->last_verified_at?->toDateTimeString(),
        ];
    }

    /**
     * Regenerate recovery codes
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $userSettings = TwoFactorUserSetting::where('user_id', $user->id)->first();

        if (! $userSettings || ! $userSettings->enabled) {
            return [
                'success' => false,
                'message' => 'กรุณาเปิดใช้งาน 2FA ก่อน',
            ];
        }

        $codes = $userSettings->generateRecoveryCodes();

        return [
            'success' => true,
            'message' => 'สร้างรหัสกู้คืนใหม่สำเร็จ',
            'recovery_codes' => $codes,
        ];
    }
}
