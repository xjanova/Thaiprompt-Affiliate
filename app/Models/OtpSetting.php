<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class OtpSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'enabled',
        'twilio_sid',
        'twilio_token',
        'twilio_from',
        'nexmo_key',
        'nexmo_secret',
        'nexmo_from',
        'custom_api_url',
        'custom_api_key',
        'custom_api_headers',
        'otp_length',
        'otp_expiry_minutes',
        'max_attempts',
        'alphanumeric',
        'rate_limit_per_phone',
        'rate_limit_window',
        'message_template',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'alphanumeric' => 'boolean',
        'otp_length' => 'integer',
        'otp_expiry_minutes' => 'integer',
        'max_attempts' => 'integer',
        'rate_limit_per_phone' => 'integer',
        'rate_limit_window' => 'integer',
    ];

    protected $hidden = [
        'twilio_token',
        'nexmo_secret',
        'custom_api_key',
    ];

    /**
     * Get active OTP settings (cached)
     */
    public static function getActive(): ?self
    {
        return Cache::remember('otp_settings', 3600, function () {
            return self::first();
        });
    }

    /**
     * Clear the settings cache
     */
    public static function clearCache(): void
    {
        Cache::forget('otp_settings');
    }

    /**
     * Check if OTP is enabled
     */
    public static function isEnabled(): bool
    {
        $settings = self::getActive();
        return $settings && $settings->enabled;
    }

    /**
     * Get message template
     */
    public function getMessageTemplateAttribute($value): string
    {
        return $value ?? 'Your verification code is: {code}. Valid for {expiry} minutes.';
    }

    /**
     * Get formatted message
     */
    public function getFormattedMessage(string $code): string
    {
        return str_replace(
            ['{code}', '{expiry}'],
            [$code, $this->otp_expiry_minutes],
            $this->message_template
        );
    }
}
