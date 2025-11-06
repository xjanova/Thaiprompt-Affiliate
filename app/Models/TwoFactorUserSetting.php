<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TwoFactorUserSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'enabled',
        'preferred_method',
        'backup_methods',
        'phone',
        'phone_verified',
        'phone_verified_at',
        'line_user_id',
        'line_verified',
        'line_verified_at',
        'email',
        'email_verified',
        'email_verified_at',
        'last_verified_at',
        'last_verified_ip',
        'trusted_devices',
        'recovery_codes',
        'recovery_codes_generated_at',
        'total_verifications',
        'failed_attempts',
        'last_failed_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'backup_methods' => 'array',
        'phone_verified' => 'boolean',
        'phone_verified_at' => 'datetime',
        'line_verified' => 'boolean',
        'line_verified_at' => 'datetime',
        'email_verified' => 'boolean',
        'email_verified_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'trusted_devices' => 'array',
        'recovery_codes' => 'array',
        'recovery_codes_generated_at' => 'datetime',
        'total_verifications' => 'integer',
        'failed_attempts' => 'integer',
        'last_failed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the 2FA setting
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate recovery codes
     */
    public function generateRecoveryCodes(int $count = 10): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(Str::random(4) . '-' . Str::random(4));
        }

        $this->recovery_codes = array_map(fn($code) => bcrypt($code), $codes);
        $this->recovery_codes_generated_at = now();
        $this->save();

        return $codes;
    }

    /**
     * Verify a recovery code
     */
    public function verifyRecoveryCode(string $code): bool
    {
        if (!$this->recovery_codes) {
            return false;
        }

        foreach ($this->recovery_codes as $index => $hashedCode) {
            if (password_verify($code, $hashedCode)) {
                // Remove used code
                $codes = $this->recovery_codes;
                unset($codes[$index]);
                $this->recovery_codes = array_values($codes);
                $this->save();

                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has recovery codes
     */
    public function hasRecoveryCodes(): bool
    {
        return $this->recovery_codes && count($this->recovery_codes) > 0;
    }

    /**
     * Add trusted device
     */
    public function addTrustedDevice(string $fingerprint, string $name = null): void
    {
        $devices = $this->trusted_devices ?? [];

        $devices[$fingerprint] = [
            'name' => $name ?? 'Unknown Device',
            'added_at' => now()->toDateTimeString(),
            'last_used_at' => now()->toDateTimeString(),
            'ip' => request()->ip(),
        ];

        $this->trusted_devices = $devices;
        $this->save();
    }

    /**
     * Check if device is trusted
     */
    public function isTrustedDevice(string $fingerprint): bool
    {
        if (!TwoFactorSetting::canRememberDevice()) {
            return false;
        }

        $devices = $this->trusted_devices ?? [];

        if (!isset($devices[$fingerprint])) {
            return false;
        }

        $device = $devices[$fingerprint];
        $addedAt = \Carbon\Carbon::parse($device['added_at']);
        $expiryDays = TwoFactorSetting::getActive()->remember_device_days ?? 30;

        // Check if device trust has expired
        if ($addedAt->addDays($expiryDays)->isPast()) {
            $this->removeTrustedDevice($fingerprint);
            return false;
        }

        // Update last used
        $devices[$fingerprint]['last_used_at'] = now()->toDateTimeString();
        $this->trusted_devices = $devices;
        $this->save();

        return true;
    }

    /**
     * Remove trusted device
     */
    public function removeTrustedDevice(string $fingerprint): void
    {
        $devices = $this->trusted_devices ?? [];

        if (isset($devices[$fingerprint])) {
            unset($devices[$fingerprint]);
            $this->trusted_devices = $devices;
            $this->save();
        }
    }

    /**
     * Remove all trusted devices
     */
    public function removeAllTrustedDevices(): void
    {
        $this->trusted_devices = [];
        $this->save();
    }

    /**
     * Check if user is within grace period
     */
    public function isWithinGracePeriod(): bool
    {
        if (!$this->last_verified_at) {
            return false;
        }

        $gracePeriod = TwoFactorSetting::getGracePeriod();
        return $this->last_verified_at->addMinutes($gracePeriod)->isFuture();
    }

    /**
     * Record successful verification
     */
    public function recordSuccessfulVerification(): void
    {
        $this->last_verified_at = now();
        $this->last_verified_ip = request()->ip();
        $this->total_verifications++;
        $this->failed_attempts = 0;
        $this->save();
    }

    /**
     * Record failed verification
     */
    public function recordFailedVerification(): void
    {
        $this->failed_attempts++;
        $this->last_failed_at = now();
        $this->save();
    }

    /**
     * Check if method is available for user
     */
    public function isMethodAvailable(string $method): bool
    {
        return match ($method) {
            'sms' => $this->phone && $this->phone_verified,
            'line' => $this->line_user_id && $this->line_verified,
            'email' => $this->email && $this->email_verified,
            default => false,
        };
    }

    /**
     * Get available methods for this user
     */
    public function getAvailableMethods(): array
    {
        $globalMethods = TwoFactorSetting::getAvailableMethods();
        $userMethods = [];

        foreach ($globalMethods as $method) {
            if ($this->isMethodAvailable($method)) {
                $userMethods[] = $method;
            }
        }

        return $userMethods;
    }

    /**
     * Get or create 2FA setting for user
     */
    public static function getOrCreateForUser(User $user): self
    {
        return self::firstOrCreate(
            ['user_id' => $user->id],
            [
                'enabled' => false,
                'preferred_method' => TwoFactorSetting::getDefaultMethod(),
                'phone' => $user->phone,
                'phone_verified' => $user->phone_verified ?? false,
                'phone_verified_at' => $user->phone_verified_at,
                'line_user_id' => $user->line_user_id,
                'line_verified' => $user->line_verified ?? false,
                'line_verified_at' => $user->line_linked_at,
                'email' => $user->email,
                'email_verified' => $user->email_verified_at !== null,
                'email_verified_at' => $user->email_verified_at,
            ]
        );
    }
}
