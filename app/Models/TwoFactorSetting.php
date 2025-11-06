<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TwoFactorSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'enabled',
        'require_on_login',
        'require_on_withdrawal',
        'require_on_transfer',
        'require_on_profile_change',
        'require_on_password_change',
        'require_on_payment_method_change',
        'role_requirements',
        'allow_sms',
        'allow_line',
        'allow_email',
        'default_method',
        'grace_period_minutes',
        'allow_remember_device',
        'remember_device_days',
        'withdrawal_threshold',
        'transfer_threshold',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'require_on_login' => 'boolean',
        'require_on_withdrawal' => 'boolean',
        'require_on_transfer' => 'boolean',
        'require_on_profile_change' => 'boolean',
        'require_on_password_change' => 'boolean',
        'require_on_payment_method_change' => 'boolean',
        'role_requirements' => 'array',
        'allow_sms' => 'boolean',
        'allow_line' => 'boolean',
        'allow_email' => 'boolean',
        'grace_period_minutes' => 'integer',
        'allow_remember_device' => 'boolean',
        'remember_device_days' => 'integer',
        'withdrawal_threshold' => 'decimal:2',
        'transfer_threshold' => 'decimal:2',
    ];

    /**
     * Get active 2FA settings (cached)
     */
    public static function getActive(): ?self
    {
        return Cache::remember('two_factor_settings', 3600, function () {
            return self::first();
        });
    }

    /**
     * Clear the settings cache
     */
    public static function clearCache(): void
    {
        Cache::forget('two_factor_settings');
    }

    /**
     * Check if 2FA is enabled globally
     */
    public static function isEnabled(): bool
    {
        $settings = self::getActive();
        return $settings && $settings->enabled;
    }

    /**
     * Check if 2FA is required for specific action
     */
    public static function isRequiredFor(string $action, ?User $user = null, ?float $amount = null): bool
    {
        $settings = self::getActive();

        if (!$settings || !$settings->enabled) {
            return false;
        }

        // Check action-specific requirements
        $required = match ($action) {
            'login' => $settings->require_on_login,
            'withdrawal' => $settings->require_on_withdrawal,
            'transfer' => $settings->require_on_transfer,
            'profile_change' => $settings->require_on_profile_change,
            'password_change' => $settings->require_on_password_change,
            'payment_method_change' => $settings->require_on_payment_method_change,
            default => false,
        };

        if (!$required) {
            return false;
        }

        // Check amount threshold for financial transactions
        if ($amount !== null) {
            if ($action === 'withdrawal' && $settings->withdrawal_threshold) {
                return $amount >= $settings->withdrawal_threshold;
            }
            if ($action === 'transfer' && $settings->transfer_threshold) {
                return $amount >= $settings->transfer_threshold;
            }
        }

        // Check role-specific requirements
        if ($user && $settings->role_requirements) {
            $roleRequirements = $settings->role_requirements;
            $userRole = $user->role;

            if (isset($roleRequirements[$userRole])) {
                $roleConfig = $roleRequirements[$userRole];
                if (isset($roleConfig[$action])) {
                    return $roleConfig[$action];
                }
            }
        }

        return $required;
    }

    /**
     * Get available 2FA methods
     */
    public static function getAvailableMethods(): array
    {
        $settings = self::getActive();

        if (!$settings) {
            return ['sms'];
        }

        $methods = [];

        if ($settings->allow_sms) {
            $methods[] = 'sms';
        }

        if ($settings->allow_line) {
            $methods[] = 'line';
        }

        if ($settings->allow_email) {
            $methods[] = 'email';
        }

        return $methods;
    }

    /**
     * Get default 2FA method
     */
    public static function getDefaultMethod(): string
    {
        $settings = self::getActive();
        return $settings->default_method ?? 'sms';
    }

    /**
     * Check if device remembering is allowed
     */
    public static function canRememberDevice(): bool
    {
        $settings = self::getActive();
        return $settings && $settings->allow_remember_device;
    }

    /**
     * Get grace period in minutes
     */
    public static function getGracePeriod(): int
    {
        $settings = self::getActive();
        return $settings->grace_period_minutes ?? 15;
    }
}
