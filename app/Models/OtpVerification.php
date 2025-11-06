<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OtpVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone',
        'channel',
        'line_user_id',
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
     * Get the user that owns the OTP verification
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

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
        int $expiryMinutes = 5,
        ?int $userId = null
    ): self {
        $settings = OtpSetting::first();
        $length = $settings->otp_length ?? 6;
        $alphanumeric = $settings->alphanumeric ?? false;

        // Delete any existing unverified OTPs for this phone
        $query = self::where('phone', $phone)
            ->where('purpose', $purpose)
            ->where('verified', false);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $query->delete();

        return self::create([
            'user_id' => $userId,
            'phone' => $phone,
            'channel' => 'sms',
            'otp_code' => self::generateOTP($length, $alphanumeric),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes($expiryMinutes),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Create new OTP for user (LINE or SMS based on preference)
     */
    public static function createForUser(
        User $user,
        string $purpose = '2fa_verification',
        int $expiryMinutes = 5,
        ?string $channel = null
    ): self {
        $settings = OtpSetting::first();
        $length = $settings->otp_length ?? 6;
        $alphanumeric = $settings->alphanumeric ?? false;

        // Auto-detect channel if not specified
        if (!$channel) {
            $twoFactorSettings = TwoFactorUserSetting::where('user_id', $user->id)->first();
            $channel = $twoFactorSettings?->preferred_method ?? 'sms';
        }

        // Delete any existing unverified OTPs for this user
        self::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->where('verified', false)
            ->delete();

        $data = [
            'user_id' => $user->id,
            'channel' => $channel,
            'otp_code' => self::generateOTP($length, $alphanumeric),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes($expiryMinutes),
            'ip_address' => request()->ip(),
        ];

        // Add channel-specific identifiers
        if ($channel === 'line' && $user->line_user_id) {
            $data['line_user_id'] = $user->line_user_id;
        } elseif ($channel === 'sms' && $user->phone) {
            $data['phone'] = $user->phone;
        }

        return self::create($data);
    }

    /**
     * Verify OTP code (legacy method - for backward compatibility)
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
     * Verify OTP code for user
     */
    public static function verifyForUser(User $user, string $code, string $purpose = '2fa_verification'): bool
    {
        $otp = self::where('user_id', $user->id)
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
     * Check rate limit by phone
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
     * Check rate limit for user
     */
    public static function checkRateLimitForUser(int $userId): bool
    {
        $settings = OtpSetting::first();
        $limit = $settings->rate_limit_per_phone ?? 3;
        $window = $settings->rate_limit_window ?? 60;

        $count = self::where('user_id', $userId)
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
