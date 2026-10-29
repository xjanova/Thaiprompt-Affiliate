<?php

namespace App\Support;

use App\Rules\NotReservedEmailDomain;

/**
 * 📧 อีเมลบัญชีนี้ส่งถึงคนจริงได้ไหม — ใช้ตัดสินว่าจะยืนยันตัวตน/แจ้งเตือนผ่านอีเมลได้หรือเปล่า
 *
 * บัญชีที่ระบบสร้างให้ (บอท LINE/Facebook/Telegram, แอป LINE) ได้อีเมลสังเคราะห์ที่ไม่มีกล่องจดหมายจริง:
 *   line_…@thaiprompt.local, fb_…@thaiprompt.local, tg_…@thaiprompt.local (โดเมนสงวน — NotReservedEmailDomain)
 *   line_…@line.local (MobileLineAuthController), deleted+…@invalid / deleted_…@deleted.local (บัญชีที่ลบแล้ว)
 * → ส่งรหัสยืนยันหรือแจ้งเตือนไปที่อีเมลพวกนี้ = ไม่มีใครได้รับ
 */
final class AccountEmail
{
    /** TLD ใช้งานพิเศษ (RFC 2606 / 6761 / 6762) — ไม่มีกล่องจดหมายจริงบนอินเทอร์เน็ต */
    private const NON_DELIVERABLE_TLDS = ['local', 'invalid', 'test', 'localhost', 'example'];

    /** อีเมลนี้ส่งถึงเจ้าของจริงได้ (ไม่ใช่อีเมลสังเคราะห์ของระบบ) */
    public static function isDeliverable(mixed $email): bool
    {
        if (! is_string($email) || trim($email) === '' || ! filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (NotReservedEmailDomain::isReserved($email)) {
            return false;
        }

        $domain = rtrim(mb_strtolower(substr($email, strrpos($email, '@') + 1)), '.');
        $tld = str_contains($domain, '.') ? substr($domain, strrpos($domain, '.') + 1) : $domain;

        return ! in_array($tld, self::NON_DELIVERABLE_TLDS, true);
    }

    /** อีเมลสองค่านี้คือกล่องเดียวกันไหม (ไม่สนตัวพิมพ์เล็ก/ใหญ่ และช่องว่างหัวท้าย) */
    public static function same(mixed $a, mixed $b): bool
    {
        return mb_strtolower(trim((string) $a)) === mb_strtolower(trim((string) $b));
    }

    /**
     * ปิดบางส่วนของอีเมลไว้แสดงบนหน้าจอ — somchai@gmail.com → so****@gmail.com
     */
    public static function mask(string $email): string
    {
        $at = strrpos($email, '@');
        if ($at === false) {
            return $email;
        }

        $local = substr($email, 0, $at);
        $visible = mb_substr($local, 0, min(2, max(1, mb_strlen($local) - 1)));

        return $visible.str_repeat('*', max(2, mb_strlen($local) - mb_strlen($visible))).substr($email, $at);
    }
}
