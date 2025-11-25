<?php

namespace App\Helpers;

/**
 * TurnstileHelper
 *
 * Helper class สำหรับตรวจสอบสถานะการตั้งค่า Cloudflare Turnstile
 * ใช้เพื่อแสดง warning ใน Admin Panel และตรวจสอบสถานะ
 */
class TurnstileHelper
{
    /**
     * สถานะการตั้งค่า Turnstile
     */
    public const STATUS_DISABLED = 'disabled';           // ปิดใช้งาน (ปลอดภัย)
    public const STATUS_ACTIVE = 'active';               // เปิดใช้งานและตั้งค่าครบ (ปลอดภัย)
    public const STATUS_MISCONFIGURED = 'misconfigured'; // เปิดแต่ไม่มี keys (อันตราย!)

    /**
     * ตรวจสอบสถานะการตั้งค่า Turnstile
     *
     * @return string STATUS_DISABLED | STATUS_ACTIVE | STATUS_MISCONFIGURED
     */
    public static function getStatus(): string
    {
        $enabled = config('turnstile.enabled', false);
        $siteKey = config('turnstile.site_key', '');
        $secretKey = config('turnstile.secret_key', '');

        // ถ้าปิดใช้งาน - OK
        if (!$enabled) {
            return self::STATUS_DISABLED;
        }

        // ถ้าเปิดแต่ไม่มี keys - อันตราย!
        if (empty($siteKey) || empty($secretKey)) {
            return self::STATUS_MISCONFIGURED;
        }

        // เปิดและตั้งค่าครบ - OK
        return self::STATUS_ACTIVE;
    }

    /**
     * ตรวจสอบว่า Turnstile ทำงานจริงหรือไม่
     *
     * @return bool true ถ้าทำงานจริง (enabled + มี keys)
     */
    public static function isActive(): bool
    {
        return self::getStatus() === self::STATUS_ACTIVE;
    }

    /**
     * ตรวจสอบว่า Turnstile ตั้งค่าผิดพลาดหรือไม่
     *
     * @return bool true ถ้า enabled=true แต่ไม่มี keys (False Sense of Security)
     */
    public static function isMisconfigured(): bool
    {
        return self::getStatus() === self::STATUS_MISCONFIGURED;
    }

    /**
     * ตรวจสอบว่า Turnstile ปิดใช้งานหรือไม่
     *
     * @return bool
     */
    public static function isDisabled(): bool
    {
        return self::getStatus() === self::STATUS_DISABLED;
    }

    /**
     * รับข้อความสถานะสำหรับแสดงใน Admin Panel
     *
     * @return array{status: string, message: string, type: string, icon: string}
     */
    public static function getStatusInfo(): array
    {
        $status = self::getStatus();

        return match ($status) {
            self::STATUS_DISABLED => [
                'status' => 'disabled',
                'message' => 'Turnstile ปิดใช้งาน',
                'description' => 'Bot protection ไม่ได้เปิดใช้งาน ระบบไม่มีการป้องกัน bot',
                'type' => 'info',
                'icon' => 'info-circle',
                'badge_class' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            ],
            self::STATUS_ACTIVE => [
                'status' => 'active',
                'message' => 'Turnstile ทำงานปกติ',
                'description' => 'Bot protection เปิดใช้งานและตั้งค่าครบถ้วน',
                'type' => 'success',
                'icon' => 'check-circle',
                'badge_class' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            ],
            self::STATUS_MISCONFIGURED => [
                'status' => 'misconfigured',
                'message' => '⚠️ Turnstile ตั้งค่าไม่ครบ!',
                'description' => 'SECURITY WARNING: Turnstile เปิดใช้งานแต่ไม่มี Site Key หรือ Secret Key - ระบบจะ BYPASS ทั้งหมด! นี่คือ False Sense of Security',
                'type' => 'danger',
                'icon' => 'exclamation-triangle',
                'badge_class' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            ],
        };
    }

    /**
     * รับรายละเอียดการตั้งค่าทั้งหมด
     *
     * @return array
     */
    public static function getConfigDetails(): array
    {
        $siteKey = config('turnstile.site_key', '');
        $secretKey = config('turnstile.secret_key', '');

        return [
            'enabled' => config('turnstile.enabled', false),
            'site_key_configured' => !empty($siteKey),
            'secret_key_configured' => !empty($secretKey),
            'site_key_preview' => !empty($siteKey) ? substr($siteKey, 0, 8) . '***' : '(ไม่ได้ตั้งค่า)',
            'bypass_admin' => config('turnstile.bypass_admin', true),
            'theme' => config('turnstile.theme', 'auto'),
            'size' => config('turnstile.size', 'normal'),
            'points' => config('turnstile.points', []),
            'status' => self::getStatus(),
            'status_info' => self::getStatusInfo(),
        ];
    }
}
