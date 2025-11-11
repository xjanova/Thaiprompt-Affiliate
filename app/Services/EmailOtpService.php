<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Email OTP Verification Service
 *
 * Generates and verifies OTP codes for email verification
 * with rate limiting and security features.
 */
class EmailOtpService
{
    /**
     * OTP code length
     */
    private const OTP_LENGTH = 6;

    /**
     * OTP validity duration in seconds (10 minutes)
     */
    private const OTP_VALIDITY = 600;

    /**
     * Maximum OTP attempts before lockout
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Cooldown period between OTP sends in seconds (1 minute)
     */
    private const SEND_COOLDOWN = 60;

    /**
     * Generate and send OTP to email
     *
     * @param string $email
     * @param string $purpose 'signup', 'login', 'reset_password'
     * @return array
     */
    public function sendOtp(string $email, string $purpose = 'signup'): array
    {
        $email = strtolower(trim($email));

        // Check cooldown
        if ($this->isOnCooldown($email)) {
            $remainingSeconds = $this->getRemainingCooldown($email);
            return [
                'success' => false,
                'message' => "กรุณารอ {$remainingSeconds} วินาที ก่อนขอรหัส OTP ใหม่",
                'retry_after' => $remainingSeconds,
            ];
        }

        // Generate OTP code
        $otp = $this->generateOtp();

        // Store OTP in cache
        $this->storeOtp($email, $otp, $purpose);

        // Send email
        try {
            $this->sendOtpEmail($email, $otp, $purpose);

            // Set cooldown
            $this->setCooldown($email);

            Log::info('OTP sent successfully', [
                'email' => $email,
                'purpose' => $purpose,
            ]);

            return [
                'success' => true,
                'message' => 'ส่งรหัส OTP ไปยังอีเมลของคุณเรียบร้อยแล้ว',
                'expires_in' => self::OTP_VALIDITY,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send OTP email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'ไม่สามารถส่งอีเมลได้ กรุณาลองอีกครั้ง',
            ];
        }
    }

    /**
     * Verify OTP code
     *
     * @param string $email
     * @param string $otpCode
     * @param string $purpose
     * @return array
     */
    public function verifyOtp(string $email, string $otpCode, string $purpose = 'signup'): array
    {
        $email = strtolower(trim($email));
        $otpCode = trim($otpCode);

        // Check if OTP exists
        $storedData = $this->getStoredOtp($email);

        if (!$storedData) {
            return [
                'valid' => false,
                'message' => 'ไม่พบรหัส OTP หรือหมดอายุแล้ว กรุณาขอรหัสใหม่',
            ];
        }

        // Check purpose
        if ($storedData['purpose'] !== $purpose) {
            return [
                'valid' => false,
                'message' => 'รหัส OTP ไม่ตรงกับวัตถุประสงค์',
            ];
        }

        // Increment attempt counter
        $attempts = $this->incrementAttempts($email);

        // Check if locked out
        if ($attempts > self::MAX_ATTEMPTS) {
            $this->lockoutEmail($email);

            Log::warning('OTP verification locked out', [
                'email' => $email,
                'attempts' => $attempts,
            ]);

            return [
                'valid' => false,
                'message' => 'คุณใส่รหัส OTP ผิดเกินจำนวนครั้งที่กำหนด กรุณาขอรหัสใหม่',
                'locked_out' => true,
            ];
        }

        // Verify OTP code
        if ($storedData['otp'] !== $otpCode) {
            $remainingAttempts = self::MAX_ATTEMPTS - $attempts;

            Log::info('Invalid OTP code entered', [
                'email' => $email,
                'attempts' => $attempts,
                'remaining' => $remainingAttempts,
            ]);

            return [
                'valid' => false,
                'message' => "รหัส OTP ไม่ถูกต้อง เหลือโอกาสอีก {$remainingAttempts} ครั้ง",
                'remaining_attempts' => $remainingAttempts,
            ];
        }

        // OTP is valid - clear cache and mark as verified
        $this->clearOtp($email);
        $this->markAsVerified($email);

        Log::info('OTP verified successfully', [
            'email' => $email,
            'purpose' => $purpose,
        ]);

        return [
            'valid' => true,
            'message' => 'ยืนยันอีเมลสำเร็จ',
        ];
    }

    /**
     * Check if email is verified
     *
     * @param string $email
     * @return bool
     */
    public function isVerified(string $email): bool
    {
        $email = strtolower(trim($email));
        return Cache::has("otp_verified:{$email}");
    }

    /**
     * Resend OTP
     *
     * @param string $email
     * @param string $purpose
     * @return array
     */
    public function resendOtp(string $email, string $purpose = 'signup'): array
    {
        // Clear old OTP first
        $this->clearOtp($email);

        // Send new OTP
        return $this->sendOtp($email, $purpose);
    }

    /**
     * Generate random OTP code
     *
     * @return string
     */
    private function generateOtp(): string
    {
        return str_pad((string)random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Store OTP in cache
     *
     * @param string $email
     * @param string $otp
     * @param string $purpose
     * @return void
     */
    private function storeOtp(string $email, string $otp, string $purpose): void
    {
        $key = "otp:{$email}";
        $data = [
            'otp' => $otp,
            'purpose' => $purpose,
            'created_at' => now()->timestamp,
        ];

        Cache::put($key, $data, now()->addSeconds(self::OTP_VALIDITY));
    }

    /**
     * Get stored OTP data
     *
     * @param string $email
     * @return array|null
     */
    private function getStoredOtp(string $email): ?array
    {
        return Cache::get("otp:{$email}");
    }

    /**
     * Clear OTP from cache
     *
     * @param string $email
     * @return void
     */
    private function clearOtp(string $email): void
    {
        Cache::forget("otp:{$email}");
        Cache::forget("otp_attempts:{$email}");
    }

    /**
     * Mark email as verified
     *
     * @param string $email
     * @return void
     */
    private function markAsVerified(string $email): void
    {
        Cache::put("otp_verified:{$email}", true, now()->addHours(24));
    }

    /**
     * Increment verification attempts
     *
     * @param string $email
     * @return int Current attempt count
     */
    private function incrementAttempts(string $email): int
    {
        $key = "otp_attempts:{$email}";
        $attempts = Cache::get($key, 0) + 1;
        Cache::put($key, $attempts, now()->addSeconds(self::OTP_VALIDITY));
        return $attempts;
    }

    /**
     * Check if email is on cooldown
     *
     * @param string $email
     * @return bool
     */
    private function isOnCooldown(string $email): bool
    {
        return Cache::has("otp_cooldown:{$email}");
    }

    /**
     * Set cooldown for email
     *
     * @param string $email
     * @return void
     */
    private function setCooldown(string $email): void
    {
        Cache::put("otp_cooldown:{$email}", true, now()->addSeconds(self::SEND_COOLDOWN));
    }

    /**
     * Get remaining cooldown time in seconds
     *
     * @param string $email
     * @return int
     */
    private function getRemainingCooldown(string $email): int
    {
        $key = "otp_cooldown:{$email}";
        if (!Cache::has($key)) {
            return 0;
        }

        // Laravel doesn't provide direct way to get TTL, so we estimate
        return self::SEND_COOLDOWN;
    }

    /**
     * Lockout email after too many failed attempts
     *
     * @param string $email
     * @return void
     */
    private function lockoutEmail(string $email): void
    {
        Cache::put("otp_lockout:{$email}", true, now()->addMinutes(30));
        $this->clearOtp($email);
    }

    /**
     * Send OTP email
     *
     * @param string $email
     * @param string $otp
     * @param string $purpose
     * @return void
     */
    private function sendOtpEmail(string $email, string $otp, string $purpose): void
    {
        $subject = match($purpose) {
            'signup' => 'ยืนยันอีเมลสำหรับการสมัครสมาชิก',
            'login' => 'รหัส OTP สำหรับเข้าสู่ระบบ',
            'reset_password' => 'รหัส OTP สำหรับรีเซ็ตรหัสผ่าน',
            default => 'รหัส OTP ของคุณ',
        };

        $message = "รหัส OTP ของคุณคือ: {$otp}\n\n";
        $message .= "รหัสนี้จะหมดอายุใน " . (self::OTP_VALIDITY / 60) . " นาที\n\n";
        $message .= "หากคุณไม่ได้ทำการนี้ กรุณาเพิกเฉยต่ออีเมลนี้";

        Mail::raw($message, function ($mail) use ($email, $subject) {
            $mail->to($email)
                ->subject($subject);
        });
    }

    /**
     * Get OTP statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        // This would need to be implemented with a database table
        // for production use to track OTP sends/verifications
        return [
            'total_sent' => 0,
            'total_verified' => 0,
            'success_rate' => 0,
        ];
    }
}
