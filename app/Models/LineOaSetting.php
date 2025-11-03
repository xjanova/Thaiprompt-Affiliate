<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class LineOaSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'login_channel_id',        // LINE Login Channel ID (for OAuth)
        'channel_secret',          // LINE Login Channel Secret (for OAuth)
        'redirect_uri',            // LINE Login callback URL
        'messaging_channel_id',    // LINE Messaging API Channel ID (optional)
        'channel_access_token',    // LINE Messaging API Access Token
        'liff_id',
        'require_line_registration',
        'enable_line_messaging',
        'welcome_message',
        'registration_success_message',
        'is_active',
    ];

    protected $casts = [
        'require_line_registration' => 'boolean',
        'enable_line_messaging' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'channel_secret',
        'channel_access_token',
    ];

    /**
     * Get the active LINE OA settings (cached)
     */
    public static function getActive(): ?self
    {
        return Cache::remember('line_oa_settings', 3600, function () {
            return self::where('is_active', true)->first();
        });
    }

    /**
     * Clear the settings cache
     */
    public static function clearCache(): void
    {
        Cache::forget('line_oa_settings');
    }

    /**
     * Check if LINE registration is required
     */
    public static function isRequired(): bool
    {
        $settings = self::getActive();
        return $settings && $settings->require_line_registration;
    }

    /**
     * Check if LINE messaging is enabled
     */
    public static function messagingEnabled(): bool
    {
        $settings = self::getActive();
        return $settings && $settings->enable_line_messaging && !empty($settings->channel_access_token);
    }

    /**
     * Get default welcome message
     */
    public function getWelcomeMessageAttribute($value): string
    {
        return $value ?? 'ยินดีต้อนรับสู่ระบบ Affiliate! ขอบคุณที่เพิ่มเพื่อนกับเรา';
    }

    /**
     * Get default registration success message
     */
    public function getRegistrationSuccessMessageAttribute($value): string
    {
        return $value ?? "🎉 สมัครสมาชิกสำเร็จ!\n\nยินดีต้อนรับสู่ระบบ Affiliate ของเรา คุณได้เข้าร่วมทีมเรียบร้อยแล้ว\n\nคุณสามารถเข้าสู่ระบบและเริ่มต้นสร้างรายได้ได้ทันที!";
    }
}
