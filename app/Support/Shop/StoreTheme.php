<?php

namespace App\Support\Shop;

/**
 * ค่าธีมของหน้าร้านที่ผู้ขายตั้งเอง (สี / CSS เพิ่มเติม / ลิงก์) — ล้างก่อนใส่ลงหน้าสาธารณะ
 *
 * ผู้ขายกรอกค่าเหล่านี้เองในหน้า "ปรับแต่งหน้าร้าน" ถ้าใส่ตรงๆ ลงใน <style> หรือ href
 * จะกลายเป็นช่องให้ฝังโค้ดบนโดเมนหลัก (ปิด </style> แล้วเปิด <script>, javascript: ในลิงก์ ฯลฯ)
 */
final class StoreTheme
{
    private function __construct() {}

    /**
     * สี hex ที่ถูกต้อง (#abc / #aabbcc) — ไม่ถูกต้อง = คืนค่าสำรอง (เช่น 'var(--accent1)')
     */
    public static function color(?string $value, string $fallback): string
    {
        $value = trim((string) $value);

        return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) === 1 ? $value : $fallback;
    }

    /**
     * สีแบรนด์ของร้าน — เหมือน color() แต่ถือว่า "ค่าเริ่มต้นของระบบเดิม" (ม่วง/ชมพู) คือยังไม่ได้ตั้งสี
     * → ใช้สีธีมแทน (ร้านที่ไม่เคยปรับแต่งจะได้สีทอง/น้ำเงินของแพลตฟอร์ม ไม่ใช่ม่วงของธีมเก่า)
     */
    public static function brand(?string $value, string $fallback): string
    {
        $color = self::color($value, $fallback);

        return in_array(strtolower($color), ['#6366f1', '#8b5cf6', '#ec4899'], true) ? $fallback : $color;
    }

    /**
     * CSS เพิ่มเติมของร้าน — ตัดสิ่งที่ใช้หลุดออกจากแท็ก <style> หรือรันโค้ดได้
     */
    public static function css(?string $css, int $maxLength = 20000): string
    {
        $css = (string) $css;
        if (trim($css) === '') {
            return '';
        }

        $css = str_replace(['<', '>'], '', $css);
        $css = (string) preg_replace([
            '/@import\b[^;]*;?/i',
            '/expression\s*\(/i',
            '/javascript\s*:/i',
            '/vbscript\s*:/i',
            '/behavior\s*:/i',
            '/-moz-binding\s*:/i',
        ], '', $css);

        return mb_substr(trim($css), 0, $maxLength);
    }

    /**
     * ลิงก์ที่ผู้ขายตั้ง (โซเชียล / แบนเนอร์) — รับเฉพาะ http(s) และ path ภายในเว็บ
     */
    public static function url(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        return preg_match('#^https?://#i', $url) === 1 ? $url : null;
    }

    /**
     * URL รูปจาก path ในดิสก์ public หรือ URL เต็ม
     */
    public static function image(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        return \Illuminate\Support\Facades\Storage::url(ltrim($path, '/'));
    }
}
