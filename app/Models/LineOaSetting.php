<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class LineOaSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'login_channel_id',        // LINE Login Channel ID (for OAuth - Web)
        'channel_secret',          // LINE Login Channel Secret (for OAuth - Web)
        'redirect_uri',            // LINE Login callback URL (Web)

        // Mobile LINE Login (แยก channel สำหรับ mobile app)
        'mobile_login_channel_id',     // LINE Login Channel ID สำหรับ Mobile App
        'mobile_login_channel_secret', // LINE Login Channel Secret สำหรับ Mobile App

        'messaging_channel_id',    // LINE Messaging API Channel ID (optional)
        'channel_access_token',    // LINE Messaging API Access Token
        'liff_id',
        'custom_oa_name',          // ชื่อ LINE OA ที่กำหนดเอง (ถ้าไม่ใส่จะใช้ชื่อจากโทเค็น)
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
        'mobile_login_channel_secret',
        'channel_access_token',
    ];

    /**
     * Get the active LINE OA settings (cached)
     */
    public static function getActive(): ?self
    {
        try {
            return Cache::remember('line_oa_settings', 3600, function () {
                return self::where('is_active', true)->first();
            });
        } catch (\Exception $e) {
            // Return null if database is not available (e.g., during migrations)
            return null;
        }
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

        return $settings && $settings->enable_line_messaging && ! empty($settings->channel_access_token);
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

    /**
     * ดึงชื่อ LINE OA ที่จะแสดง
     *
     * ถ้ามีการกำหนด custom_oa_name จะใช้ชื่อนั้น
     * ถ้าไม่ได้กำหนดจะ return null เพื่อให้ใช้ชื่อจาก API แทน
     *
     * @return string|null ชื่อ LINE OA หรือ null ถ้าไม่ได้กำหนด
     */
    public function getDisplayOaName(): ?string
    {
        return ! empty($this->custom_oa_name) ? $this->custom_oa_name : null;
    }

    /**
     * ดึงชื่อ LINE OA พร้อม fallback
     *
     * @param  string|null  $apiDisplayName  ชื่อจาก LINE API
     * @param  string  $default  ค่าเริ่มต้นถ้าไม่มีชื่อเลย
     */
    public function getOaNameWithFallback(?string $apiDisplayName = null, string $default = 'LINE OA'): string
    {
        // ใช้ชื่อที่กำหนดเองก่อน
        if (! empty($this->custom_oa_name)) {
            return $this->custom_oa_name;
        }

        // ถ้าไม่มีให้ใช้ชื่อจาก API
        if (! empty($apiDisplayName)) {
            return $apiDisplayName;
        }

        // ถ้าไม่มีทั้งคู่ใช้ค่า default
        return $default;
    }

    /**
     * ตรวจสอบว่ามีการกำหนดชื่อ LINE OA เองหรือไม่
     */
    public function hasCustomOaName(): bool
    {
        return ! empty($this->custom_oa_name);
    }

    /**
     * ตรวจสอบว่า Mobile LINE Login ถูกตั้งค่าแล้วหรือไม่
     */
    public function isMobileLineLoginConfigured(): bool
    {
        return ! empty($this->mobile_login_channel_id) && ! empty($this->mobile_login_channel_secret);
    }

    /**
     * ดึงข้อมูล Mobile LINE Login สำหรับ API
     */
    public function getMobileLineConfig(): ?array
    {
        if (! $this->isMobileLineLoginConfigured()) {
            return null;
        }

        return [
            'channel_id' => $this->mobile_login_channel_id,
            // ไม่ส่ง secret กลับไป - แอพใช้ LINE SDK ที่ไม่ต้องการ secret
        ];
    }
}
