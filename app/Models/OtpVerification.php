<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OtpVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'otp_code',
        'purpose',
        'verified',
        'expires_at',
        'verified_at',
        'ip_address',
        'attempts',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Generate OTP code
     */
    public static function generateOTP(int $length = 6, bool $alphanumeric = false): string
    {
        if ($alphanumeric) {
            return strtoupper(Str::random($length));
        }

        return str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Create new OTP for phone
     */
    public static function createForPhone(
        string $phone,
        string $purpose = 'phone_verification',
        int $expiryMinutes = 5
    ): self {
        $settings = OtpSetting::first();
        $length = $settings->otp_length ?? 6;
        $alphanumeric = $settings->alphanumeric ?? false;

        // Delete any existing unverified OTPs for this phone
        self::where('phone', $phone)
            ->where('purpose', $purpose)
            ->where('verified', false)
            ->delete();

        return self::create([
            'phone' => $phone,
            'otp_code' => self::generateOTP($length, $alphanumeric),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes($expiryMinutes),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Verify OTP code
     */
    public static function verifyOTP(string $phone, string $code, string $purpose = 'phone_verification'): bool
    {
        $otp = self::where('phone', $phone)
            ->where('purpose', $purpose)
            ->where('verified', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            return false;
        }

        // Increment attempts
        $otp->increment('attempts');

        // Check max attempts
        $settings = OtpSetting::first();
        $maxAttempts = $settings->max_attempts ?? 3;

        if ($otp->attempts > $maxAttempts) {
            $otp->delete();
            return false;
        }

        // Verify code
        if ($otp->otp_code === $code) {
            $otp->update([
                'verified' => true,
                'verified_at' => now(),
            ]);
            return true;
        }

        return false;
    }

    /**
     * Check if phone has verified OTP recently
     */
    public static function hasRecentVerification(string $phone, string $purpose = 'phone_verification', int $minutes = 60): bool
    {
        return self::where('phone', $phone)
            ->where('purpose', $purpose)
            ->where('verified', true)
            ->where('verified_at', '>', now()->subMinutes($minutes))
            ->exists();
    }

    /**
     * Check rate limit
     */
    public static function checkRateLimit(string $phone): bool
    {
        $settings = OtpSetting::first();
        $limit = $settings->rate_limit_per_phone ?? 3;
        $window = $settings->rate_limit_window ?? 60;

        $count = self::where('phone', $phone)
            ->where('created_at', '>', now()->subMinutes($window))
            ->count();

        return $count < $limit;
    }

    /**
     * Scope: Not expired
     */
    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope: Not verified
     */
    public function scopeNotVerified($query)
    {
        return $query->where('verified', false);
    }
}
