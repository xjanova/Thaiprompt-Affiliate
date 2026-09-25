<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * ตรวจเลขบัตรประชาชนไทย 13 หลัก (รวมหลักตรวจสอบ checksum)
 *
 * สูตรกรมการปกครอง:
 *   ผลรวม = Σ (หลักที่ i × (14 − i))  สำหรับ i = 1..12
 *   หลักที่ 13 = (11 − (ผลรวม mod 11)) mod 10
 *
 * รับค่าที่มีขีด/ช่องว่างคั่นได้ (เช่น 1-1037-02071-83-1) — ตัดออกก่อนตรวจ
 * หลักแรกเป็น 0 ไม่ได้ (ไม่มีเลขบัตรขึ้นต้นด้วย 0)
 */
class ThaiNationalId implements ValidationRule
{
    /**
     * ตัดทุกอย่างที่ไม่ใช่ตัวเลขออก (ใช้ก่อนบันทึกลงฐานข้อมูลด้วย)
     */
    public static function normalize(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    /**
     * เลขบัตรถูกต้องตามรูปแบบ + checksum หรือไม่ (ฟังก์ชันล้วน ไม่แตะฐานข้อมูล)
     */
    public static function isValid(mixed $value): bool
    {
        $digits = self::normalize($value);

        if (strlen($digits) !== 13 || $digits[0] === '0') {
            return false;
        }

        // เลขซ้ำทั้ง 13 หลัก (เช่น 1111111111111) = เลขทดสอบ ไม่ใช่เลขจริง
        if (count(array_unique(str_split($digits))) === 1) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += ((int) $digits[$i]) * (13 - $i);
        }

        $check = (11 - ($sum % 11)) % 10;

        return $check === (int) $digits[12];
    }

    /**
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = self::normalize($value);

        if (strlen($digits) !== 13) {
            $fail('เลขบัตรประชาชนต้องเป็นตัวเลข 13 หลัก');

            return;
        }

        if (! self::isValid($digits)) {
            $fail('เลขบัตรประชาชนไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง');
        }
    }
}
