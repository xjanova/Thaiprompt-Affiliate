<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 🔒 (2026-09-21) ห้ามผู้ใช้ตั้งอีเมลในโดเมนที่ระบบสงวนไว้ — @thaiprompt.local (รวมโดเมนย่อย)
 *
 * ทำไม: บอทดูดวงสร้างบัญชีให้ลูกค้าด้วยอีเมลสังเคราะห์ที่เดาได้ (fb_{PSID}@thaiprompt.local,
 *   line_{uid}@…, tg_…@…) และมีเส้นหาบัญชีลูกค้าจากอีเมลรูปแบบนั้น — ใครจองอีเมลนั้นไว้ก่อน
 *   (สมัคร/แก้โปรไฟล์) จะยึดบัญชีของลูกค้าจริงได้ พร้อมสมาชิก MLM ลิงก์เชิญ และค่าแนะนำทั้งสาย
 *
 * ใช้กับทุกช่องอีเมลที่ผู้ใช้/ภายนอกกำหนดได้ (สมัคร, แก้โปรไฟล์, แอดมินสร้าง/แก้ผู้ใช้, API, OAuth)
 *   — โดเมนนี้สร้างได้จากโค้ดของระบบเท่านั้น
 */
class NotReservedEmailDomain implements ValidationRule
{
    /** โดเมนที่มีแต่ระบบสร้าง (โดเมนย่อยของมันก็สงวนด้วย) */
    public const RESERVED_DOMAINS = ['thaiprompt.local'];

    public const MESSAGE = 'ไม่สามารถใช้อีเมลโดเมนนี้ได้ กรุณาใช้อีเมลจริงของคุณ';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (self::isReserved($value)) {
            $fail(self::MESSAGE);
        }
    }

    /** อีเมลนี้อยู่ในโดเมนสงวนไหม — ใช้ได้ทั้งตอน validate และกับอีเมลที่ได้จาก OAuth */
    public static function isReserved(mixed $email): bool
    {
        if (! is_string($email)) {
            return false;
        }

        $at = strrpos($email, '@');
        if ($at === false) {
            return false;
        }

        // ตัดช่องว่าง/จุดท้ายโดเมน ("x@Thaiprompt.Local." ต้องโดนเหมือนกัน)
        $domain = rtrim(mb_strtolower(trim(substr($email, $at + 1))), '.');

        foreach (self::RESERVED_DOMAINS as $reserved) {
            if ($domain === $reserved || str_ends_with($domain, '.'.$reserved)) {
                return true;
            }
        }

        return false;
    }
}
