<?php

namespace App\Support;

/**
 * ThaiFontText — กรองข้อความก่อนวาดลงรูปด้วยฟอนต์ไทยของรีโป (resources/fonts/NotoSansThai-Bold.ttf ผ่าน GD)
 *
 * 🔤 ฟอนต์นี้มีแค่ อักษรไทย + ASCII + Latin-1 (ขาด 12 ตัว) — ตรวจ cmap ด้วย fontTools 2026-09-12
 *    ตัวที่ไม่มี (อีโมจิ · ✦ ✨ · อักษรลาว/จีน · ± ½ ² ฯลฯ) ขึ้นเป็นกล่องสี่เหลี่ยม
 *    และอีโมจิ 4 ไบต์ GD ถอดผิดเป็นตัว Latin-1 เพี้ยนๆ ("ð���")
 *    ชื่อลูกค้าจากเฟซบุ๊ก/LINE มีของพวกนี้บ่อย ⇒ ต้องกรองก่อนวาดทุกครั้ง
 *
 * ⚠️ ข้อความตายตัวในโค้ด (หัวรูป/ป้าย) ห้ามใส่อีโมจิ/✦/✨ ตั้งแต่แรก — ประกายให้วาดเป็นรูปทรงแทน
 *    (FortuneChartService::drawCenteredTextWithSparkles)
 *
 * @see \Tests\Unit\Services\ChartPngTextCoverageTest
 */
final class ThaiFontText
{
    /**
     * ช่วงอักขระที่ NotoSansThai-Bold.ttf มีจริง (ใช้ใน regex character class)
     *
     * ASCII 0020-007E · Latin-1 00A0-00FF ยกเว้น ¤ ¦ ¬ soft-hyphen ± ² ³ µ ¹ ¼ ½ ¾ · ไทย 0E01-0E3A, 0E3F-0E5B
     */
    public const COVERED_CLASS = '\x{0020}-\x{007E}\x{00A0}-\x{00A3}\x{00A5}\x{00A7}-\x{00AB}\x{00AE}-\x{00B0}\x{00B4}\x{00B6}-\x{00B8}\x{00BA}\x{00BB}\x{00BF}-\x{00FF}\x{0E01}-\x{0E3A}\x{0E3F}-\x{0E5B}';

    /**
     * ตัดอักขระที่ฟอนต์ไทยไม่มีออก (แทนด้วยช่องว่าง แล้วยุบช่องว่างซ้อน)
     *
     * @param  string  $text  ข้อความดิบ (เช่น ชื่อลูกค้า)
     * @param  string  $fallback  ใช้แทนเมื่อกรองแล้วไม่เหลืออะไรเลย
     * @return string ข้อความที่ฟอนต์ไทยวาดได้ครบทุกตัว
     *
     * @example ThaiFontText::safe('น้องมิว💕✨', 'เจ้าชะตา') // 'น้องมิว'
     * @example ThaiFontText::safe('ສົມໃຈ 🌙', 'เจ้าชะตา')   // 'เจ้าชะตา'
     */
    public static function safe(string $text, string $fallback): string
    {
        $clean = (string) preg_replace('/[^'.self::COVERED_CLASS.']+/u', ' ', $text);
        $clean = trim((string) preg_replace('/\s+/u', ' ', $clean));

        return $clean !== '' ? $clean : $fallback;
    }
}
