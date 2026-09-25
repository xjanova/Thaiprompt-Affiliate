<?php

namespace App\Support;

use App\Models\SiteSetting;

/**
 * 📬 ช่องทางติดต่อ/ขอลบข้อมูล "ชุดเดียว" ที่ใช้ทั้งเว็บ แอป และ Play Console (audit PLAY-25)
 *
 * เดิมหน้า privacy ใช้อีเมล Gmail ส่วนตัว ส่วนแอปใช้ support@thaiprompt.com / support@thaiprompt.online
 * ปนกัน → ตอนนี้ทุกหน้าบนเว็บอ่านจากที่นี่ที่เดียว:
 *   1. site_settings.contact_email (แอดมินตั้งที่หน้า "ตั้งค่าเว็บไซต์") ถ้ามี
 *   2. ไม่งั้นใช้ DEFAULT_SUPPORT_EMAIL — ต้องตรงกับ APP_INFO.SUPPORT_EMAIL ในแอป (thaiprompt/config/appConfig.ts)
 */
final class ContactInfo
{
    /** อีเมลโดเมนบริษัท — ตรงกับ SUPPORT_EMAIL ของแอป */
    public const DEFAULT_SUPPORT_EMAIL = 'support@thaiprompt.online';

    /**
     * อีเมลติดต่อทีมงาน / เจ้าหน้าที่คุ้มครองข้อมูลส่วนบุคคล (DPO)
     */
    public static function supportEmail(): string
    {
        try {
            $email = trim((string) (SiteSetting::getSetting()->contact_email ?? ''));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        } catch (\Throwable $e) {
            // ตาราง/แคชใช้ไม่ได้ (เช่นตอน migrate) → ใช้ค่าเริ่มต้น หน้า legal ต้องเปิดได้เสมอ
        }

        return self::DEFAULT_SUPPORT_EMAIL;
    }
}
