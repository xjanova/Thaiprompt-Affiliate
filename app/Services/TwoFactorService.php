<?php

namespace App\Services;

use App\Models\TwoFactorSetting;
use App\Models\TwoFactorUserSetting;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

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
        if (!TwoFactorSetting::isRequiredFor($action, $user, $amount)) {
            return false;
        }

        // Check user's 2FA settings
        $userSettings = TwoFactorUserSetting::where('user_id', $user->id)->first();

        if (!$userSettings || !$userSettings->enabled) {
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
     * Send 2FA code to user
     */
    public function sendCode(User $user, string $action = 'verification'): array
    {
        $userSettings = TwoFactorUserSetting::getOrCreateForUser($user);

        if (!$userSettings->enabled) {
            return [
                'success' => false,
                'message' => '2FA is not enabled for this user',
            ];
        }

        // Check if user has at least one verified method
        $availableMethods = $userSettings->getAvailableMethods();

        if (empty($availableMethods)) {
            return [
                'success' => false,
                'message' => 'No verified 2FA methods available. Please set up 2FA first.',
            ];
        }

        // Use preferred method
        $channel = $userSettings->preferred_method;

        // Fallback to first available method if preferred is not available
        if (!in_array($channel, $availableMethods)) {
            $channel = $availableMethods[0];
        }

        // Send OTP
        $result = $this->otpService->sendOTPToUser($user, '2fa_' . $action, $channel);

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
     * Verify 2FA code
     */
    public function verifyCode(User $user, string $code, string $action = 'verification', bool $rememberDevice = false): array
    {
        // Verify the OTP
        $result = $this->otpService->verifyOTPForUser($user, $code, '2fa_' . $action);

        if (!$result['success']) {
            // Record failed attempt
            $userSettings = TwoFactorUserSetting::where('user_id', $user->id)->first();
            if ($userSettings) {
                $userSettings->recordFailedVerification();
            }

            return $result;
        }

        // Record successful verification
        $userSettings = TwoFactorUserSetting::where('user_id', $user->id)->first();
        if ($userSettings) {
            $userSettings->recordSuccessfulVerification();

            // Remember device if requested
            if ($rememberDevice && TwoFactorSetting::canRememberDevice()) {
                $deviceFingerprint = $this->getDeviceFingerprint();
                if ($deviceFingerprint) {
                    $userSettings->addTrustedDevice($deviceFingerprint, $this->getDeviceName());
                }
            }
        }

        // Clear verification session
        Session::forget('2fa_verification_pending');

        // Mark as verified in session
        Session::put('2fa_verified_at', now()->toDateTimeString());
        Session::put('2fa_verified_for', $action);

        return [
            'success' => true,
            'message' => '2FA verification successful',
        ];
    }

    /**
     * Verify recovery code
     */
    public function verifyRecoveryCode(User $user, string $code, string $action = 'verification'): array
    {
        $userSettings = TwoFactorUserSetting::where('user_id', $user->id)->first();

        if (!$userSettings) {
            return [
                'success' => false,
                'message' => '2FA is not set up for this user',
            ];
        }

        if (!$userSettings->verifyRecoveryCode($code)) {
            return [
                'success' => false,
                'message' => 'Invalid recovery code',
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
            'message' => 'Recovery code verified successfully',
            'warning' => 'Recovery code has been used and is no longer valid',
        ];
    }

    /**
     * Enable 2FA for user
     */
    public function enable(User $user, string $preferredMethod = 'sms'): array
    {
        $userSettings = TwoFactorUserSetting::getOrCreateForUser($user);

        // Verify that the user has at least one verified contact method
        if (!$userSettings->isMethodAvailable($preferredMethod)) {
            return [
                'success' => false,
                'message' => 'Please verify your ' . $preferredMethod . ' before enabling 2FA',
            ];
        }

        $userSettings->enabled = true;
        $userSettings->preferred_method = $preferredMethod;

        // Generate recovery codes
        $recoveryCodes = $userSettings->generateRecoveryCodes();

        $userSettings->save();

        return [
            'success' => true,
            'message' => '2FA has been enabled successfully',
            'recovery_codes' => $recoveryCodes,
        ];
    }

    /**
     * Disable 2FA for user
     */
    public function disable(User $user): array
    {
        $userSettings = TwoFactorUserSetting::where('user_id', $user->id)->first();

        if (!$userSettings) {
            return [
                'success' => false,
                'message' => '2FA is not enabled',
            ];
        }

        $userSettings->enabled = false;
        $userSettings->trusted_devices = [];
        $userSettings->recovery_codes = null;
        $userSettings->save();

        return [
            'success' => true,
            'message' => '2FA has been disabled',
        ];
    }

    /**
     * Get device fingerprint
     */
    private function getDeviceFingerprint(): ?string
    {
        $userAgent = request()->userAgent();
        $ip = request()->ip();

        if (!$userAgent) {
            return null;
        }

        return hash('sha256', $userAgent . '|' . $ip);
    }

    /**
     * Get device name from user agent
     */
    private function getDeviceName(): string
    {
        $userAgent = request()->userAgent();

        if (!$userAgent) {
            return 'Unknown Device';
        }

        // Simple device detection
        if (stripos($userAgent, 'iPhone') !== false) {
            return 'iPhone';
        } elseif (stripos($userAgent, 'iPad') !== false) {
            return 'iPad';
        } elseif (stripos($userAgent, 'Android') !== false) {
            return 'Android Device';
        } elseif (stripos($userAgent, 'Windows') !== false) {
            return 'Windows PC';
        } elseif (stripos($userAgent, 'Mac') !== false) {
            return 'Mac';
        } elseif (stripos($userAgent, 'Linux') !== false) {
            return 'Linux PC';
        }

        return 'Unknown Device';
    }

    /**
     * Check if current session has passed 2FA
     */
    public function isVerified(string $action = null): bool
    {
        $verifiedAt = Session::get('2fa_verified_at');

        if (!$verifiedAt) {
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

        if (!$userSettings) {
            return [
                'enabled' => false,
                'methods' => [],
                'has_recovery_codes' => false,
                'trusted_devices' => [],
            ];
        }

        return [
            'enabled' => $userSettings->enabled,
            'preferred_method' => $userSettings->preferred_method,
            'available_methods' => $userSettings->getAvailableMethods(),
            'has_recovery_codes' => $userSettings->hasRecoveryCodes(),
            'recovery_codes_count' => $userSettings->recovery_codes ? count($userSettings->recovery_codes) : 0,
            'trusted_devices' => $userSettings->trusted_devices ?? [],
            'total_verifications' => $userSettings->total_verifications,
            'last_verified_at' => $userSettings->last_verified_at?->toDateTimeString(),
        ];
    }
}
