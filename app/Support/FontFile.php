<?php

namespace App\Support;

/**
 * FontFile — ตรวจว่า path ที่จะส่งให้ GD (imagettftext/imagettfbbox) เป็น "ไฟล์ฟอนต์จริง"
 *
 * 🚨 ที่มา (2026-09-12): resources/fonts/DejaVuSans.ttf ในรีโป (commit 4286cf86a, 2026-02-16)
 *    ไม่ใช่ฟอนต์ — ข้างในเป็นหน้า "Page not found · GitHub" (ดาวน์โหลดผิด URL มา)
 *    file_exists() ผ่าน ⇒ ตัวหาฟอนต์คืน path นี้ ⇒ @imagettfbbox/@imagettftext ล้มเงียบ
 *    ⇒ สัญลักษณ์ดาว ☉☽♂ ในรูป PNG ไม่เคยขึ้นบน prod เลย 7 เดือน โดยไม่มี error ที่ไหน
 *
 * กฎ: ตัวหาฟอนต์ทุกตัวต้องเช็คด้วย isReal() แทน file_exists()/is_file()
 *     ไฟล์ที่ไม่ใช่ฟอนต์ = ข้ามไปตัวถัดไปในลิสต์ (fallback ทำงานได้จริง แทนที่จะวาดว่างเปล่า)
 *
 * @see \Tests\Unit\Services\ResourceFontFilesTest
 */
final class FontFile
{
    /**
     * 4 ไบต์แรก (sfnt version / tag) ของไฟล์ฟอนต์ที่ FreeType อ่านได้
     */
    public const SIGNATURES = [
        "\x00\x01\x00\x00", // TrueType
        'OTTO',             // OpenType (CFF)
        'true',             // TrueType ของ Apple
        'ttcf',             // TrueType Collection
    ];

    /**
     * ไฟล์นี้เป็นฟอนต์จริงไหม (มีอยู่ + อ่านได้ + ขึ้นต้นด้วย signature ของฟอนต์)
     *
     * ใช้ @ ทุกจุด — path ระบบ (/usr/share/fonts/...) อาจโดน open_basedir บล็อก
     * และ Laravel แปลง warning เป็น ErrorException ⇒ ถ้าไม่ปิดเสียง ทั้งรูปจะล้ม
     *
     * @param  string  $path  path ของไฟล์ฟอนต์
     * @return bool true = ส่งให้ GD ได้
     */
    public static function isReal(string $path): bool
    {
        if ($path === '' || ! @is_file($path)) {
            return false;
        }

        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }

        $head = @fread($handle, 4);
        fclose($handle);

        return is_string($head) && in_array($head, self::SIGNATURES, true);
    }

    /**
     * คืน path แรกในลิสต์ที่เป็นฟอนต์จริง
     *
     * @param  iterable<string>  $paths  path เรียงตามลำดับที่อยากใช้
     * @return string|null null = ไม่มีฟอนต์จริงสักตัว
     */
    public static function firstReal(iterable $paths): ?string
    {
        foreach ($paths as $path) {
            if (self::isReal($path)) {
                return $path;
            }
        }

        return null;
    }
}
