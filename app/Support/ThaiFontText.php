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

    /** วรรณยุกต์ไทย ่ ้ ๊ ๋ ์ */
    private const TONE_MARKS = ["\u{0E48}", "\u{0E49}", "\u{0E4A}", "\u{0E4B}", "\u{0E4C}"];

    /** สระบน/นิคหิต ที่วรรณยุกต์ต้องลอยอยู่เหนือ ั ิ ี ึ ื ํ */
    private const UPPER_VOWELS = ["\u{0E31}", "\u{0E34}", "\u{0E35}", "\u{0E36}", "\u{0E37}", "\u{0E4D}"];

    /** ยกวรรณยุกต์ขึ้นกี่เท่าของขนาดฟอนต์ (pt) — วัดจาก NotoSansThai-Bold ให้ลอยพ้นสระบนพอดี */
    private const TONE_LIFT = 0.30;

    /**
     * แยกวรรณยุกต์ที่ต้อง "ยกลอย" ออกจากข้อความ
     *
     * GD ไม่ทำ Thai shaping → วรรณยุกต์ที่ตามสระบน (เรื่อง ที่ ชื่อ) ถูกวาดตำแหน่งเดียวกับสระ = จมหาย
     * (2026-10-11 เห็นในรูปจริง: "หัวใจของเรื่อง" → "เรือง" · ชื่อลูกค้า "ชื่อ" → "ชือ")
     *
     * @return array{0:string,1:array<int,array{0:string,1:string}>} [ข้อความที่ถอดวรรณยุกต์เหล่านั้นออก, [[ข้อความก่อนหน้า, วรรณยุกต์], ...]]
     *
     * @example ThaiFontText::liftStackedToneMarks('เรื่อง') // ['เรือง', [['เรื', '่']]]
     */
    public static function liftStackedToneMarks(string $text): array
    {
        $chars = mb_str_split($text);
        $base = '';
        $lifted = [];

        foreach ($chars as $i => $ch) {
            if (in_array($ch, self::TONE_MARKS, true)) {
                $prev = $chars[$i - 1] ?? '';
                $next = $chars[$i + 1] ?? '';
                // ตามสระบน (เรื่อง) หรือตามด้วยสระอำ (น้ำ — นิคหิตของ ำ อยู่ตำแหน่งเดียวกัน)
                if (in_array($prev, self::UPPER_VOWELS, true) || $next === "\u{0E33}") {
                    $lifted[] = [$base, $ch];

                    continue;
                }
            }

            $base .= $ch;
        }

        return [$base, $lifted];
    }

    /**
     * วาดข้อความด้วย imagettftext โดยยกวรรณยุกต์บนสระบนให้ลอยพ้น — ใช้แทน imagettftext ตรงๆ
     *
     * ตำแหน่ง/ความกว้างข้อความเท่าเดิมทุกอย่าง (วรรณยุกต์กว้าง 0) ต่างแค่วรรณยุกต์ที่เคยจมจะลอยขึ้นมาให้เห็น
     */
    public static function draw(\GdImage $img, float $size, int $x, int $y, int $color, string $font, string $text): void
    {
        [$base, $lifted] = self::liftStackedToneMarks($text);
        imagettftext($img, $size, 0, $x, $y, $color, $font, $base);

        $lift = (int) round($size * self::TONE_LIFT);
        foreach ($lifted as [$prefix, $mark]) {
            // วรรณยุกต์กว้าง 0 + ระยะซ้ายติดลบ → วางที่ปากกาหลังพยัญชนะ/สระ จะลอยเหนือตัวก่อนหน้าพอดี
            imagettftext($img, $size, 0, $x + self::advance($size, $font, $prefix), $y - $lift, $color, $font, $mark);
        }
    }

    /**
     * ระยะปากกาเลื่อนหลังวาด $text (advance) — วัดจาก bbox ของ "$text + I" ลบ bbox ของ "I"
     * (bbox ของ $text อย่างเดียวไม่ใช่ advance เพราะตัวท้ายอาจเป็นวรรณยุกต์/สระบนที่กว้าง 0)
     */
    public static function advance(float $size, string $font, string $text): int
    {
        if ($text === '') {
            return 0;
        }

        $with = imagettfbbox($size, 0, $font, $text.'I');
        $ref = imagettfbbox($size, 0, $font, 'I');

        return (int) round(max($with[2], $with[4]) - max($ref[2], $ref[4]));
    }
}
