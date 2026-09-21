<?php

namespace App\Services\Fortune;

/**
 * 🔢 เลขศาสตร์ไทย — ชื่อ / เบอร์โทร / ทะเบียน / เลขบัตร / วันเกิด — พอร์ตจาก GenLotto `Astro/Numerology.php`
 *
 * 🇹🇭 (2026-09-21 เจ้าของสั่ง "ยึดตำราโหราจาก genlotto เป็นหลักทั้งหมด")
 *   ของเดิมใน HoroscopeNumerologyService ใช้ตาราง "กลุ่มวรรค" (ก ข ฃ = 1 · ค ฅ ฆ = 2 …) และนับแค่พยัญชนะ
 *   ⇒ ไม่ใช่ตารางเลขศาสตร์ไทยที่ใช้ตั้งชื่อ · สระ/วรรณยุกต์หายหมด · ผูกเลขกับดาวแบบฝรั่ง (3=พฤหัส 4=ราหู)
 *   ตอนนี้ใช้ตารางของ GenLotto (ยืนยัน 2 แหล่ง) + เลขดาวแบบไทย (1 อาทิตย์ … 8 ราหู 9 เกตุ)
 *
 * ตารางค่าอักษร (ตำราเลขศาสตร์มาตรฐานที่ใช้ตั้งชื่อ):
 *   1 ก ด ท ถ ภ ฤ ฤๅ · สระ า ุ ำ · ไม้เอก
 *   2 ข ช ง บ ป · สระ เ แ ู · ไม้โท
 *   3 ฆ ต ฑ ฒ ฃ · ไม้จัตวา
 *   4 ค ธ ญ ร ษ · สระ ะ ิ โ · ไม้หันอากาศ
 *   5 ฉ ณ ฌ น ม ห ฎ ฬ ฮ · สระ ึ
 *   6 จ ล ว อ · สระ ใ
 *   7 ซ ศ ส · ไม้ตรี · สระ ี ื
 *   8 ผ ฝ พ ฟ ย · ไม้ไต่คู้
 *   9 ฏ ฐ · สระ ไ · การันต์
 */
final class ThaiNumerology
{
    public const LETTER = [
        'ก' => 1, 'ด' => 1, 'ท' => 1, 'ถ' => 1, 'ภ' => 1, 'ฤ' => 1, 'ๅ' => 1, 'า' => 1, 'ุ' => 1, 'ำ' => 1, '่' => 1,
        'ข' => 2, 'ช' => 2, 'ง' => 2, 'บ' => 2, 'ป' => 2, 'เ' => 2, 'แ' => 2, 'ู' => 2, '้' => 2,
        'ฆ' => 3, 'ต' => 3, 'ฑ' => 3, 'ฒ' => 3, 'ฃ' => 3, '๋' => 3,
        'ค' => 4, 'ธ' => 4, 'ญ' => 4, 'ร' => 4, 'ษ' => 4, 'ะ' => 4, 'ิ' => 4, 'โ' => 4, 'ั' => 4, 'ฅ' => 4,
        'ฉ' => 5, 'ณ' => 5, 'ฌ' => 5, 'น' => 5, 'ม' => 5, 'ห' => 5, 'ฎ' => 5, 'ฬ' => 5, 'ฮ' => 5, 'ึ' => 5,
        'จ' => 6, 'ล' => 6, 'ว' => 6, 'อ' => 6, 'ใ' => 6,
        'ซ' => 7, 'ศ' => 7, 'ส' => 7, '๊' => 7, 'ี' => 7, 'ื' => 7,
        'ผ' => 8, 'ฝ' => 8, 'พ' => 8, 'ฟ' => 8, 'ย' => 8, '็' => 8,
        'ฏ' => 9, 'ฐ' => 9, 'ไ' => 9, '์' => 9,
    ];

    /** ตัวอักษรละติน (ตารางเดียวกัน) เผื่อกรอกชื่ออังกฤษ */
    public const LATIN = [
        'A' => 1, 'I' => 1, 'J' => 1, 'Q' => 1, 'Y' => 1,
        'B' => 2, 'K' => 2, 'R' => 2,
        'C' => 3, 'G' => 3, 'L' => 3, 'S' => 3,
        'D' => 4, 'M' => 4, 'T' => 4,
        'E' => 5, 'H' => 5, 'N' => 5, 'X' => 5,
        'U' => 6, 'V' => 6, 'W' => 6,
        'O' => 7, 'Z' => 7,
        'F' => 8, 'P' => 8,
    ];

    /** ความหมายผลรวมเลขศาสตร์ (ตาราง GenLotto) — ไม่อยู่ในรายการ = กลาง */
    public const SUM_TONE = [
        'ดีมาก' => [14, 15, 19, 23, 24, 32, 36, 40, 41, 42, 44, 45, 46, 50, 51, 54, 55, 56, 59, 63, 64, 65, 69, 79, 90, 95, 99, 100],
        'ดี' => [2, 5, 6, 9, 20, 26, 35, 39, 47, 49, 53, 58, 60, 61, 71, 72, 74, 75, 78, 80, 81, 86, 87, 89, 91, 92, 96, 97],
        'ไม่ดี' => [3, 7, 8, 10, 11, 12, 13, 16, 17, 18, 21, 22, 25, 27, 28, 29, 30, 31, 33, 34, 37, 38, 43, 48, 52, 57, 62, 66, 67, 68, 70, 73, 76, 77, 82, 83, 84, 85, 88, 93, 94, 98],
    ];

    /** เลขดาวไทย (GenLotto `ThaiTables::NUM_TH`) — 1 อาทิตย์ … 8 ราหู 9 เกตุ 0 มฤตยู */
    public const PLANET_OF_NUMBER = [1 => 'อาทิตย์', 2 => 'จันทร์', 3 => 'อังคาร', 4 => 'พุธ', 5 => 'พฤหัสบดี', 6 => 'ศุกร์', 7 => 'เสาร์', 8 => 'ราหู', 9 => 'เกตุ', 0 => 'มฤตยู'];

    /**
     * ค่าตัวเลขของชื่อ
     *
     * @return array{digits:int[], sum:int, root:int, letters:array<int, array{0:string,1:int}>, tone:string}
     */
    public static function name(string $name): array
    {
        $digits = [];
        $letters = [];
        foreach (mb_str_split(mb_strtoupper(trim($name))) as $ch) {
            $v = self::LETTER[$ch] ?? self::LATIN[$ch] ?? null;
            if ($v === null) {
                continue;
            }
            $digits[] = $v;
            $letters[] = [$ch, $v];
        }
        $sum = array_sum($digits);

        return ['digits' => $digits, 'sum' => $sum, 'root' => self::reduce($sum), 'letters' => $letters, 'tone' => self::sumTone($sum)];
    }

    /** ผลรวมนี้ดี/ไม่ดีตามตาราง */
    public static function sumTone(int $sum): string
    {
        foreach (self::SUM_TONE as $tone => $list) {
            if (in_array($sum, $list, true)) {
                return $tone;
            }
        }

        return 'กลาง';
    }

    /** ตัวเลขล้วนจากข้อความ (บัตร/โทร/บ้านเลขที่) */
    public static function digitsOf(string $raw): array
    {
        $clean = preg_replace('/[^0-9]/', '', $raw) ?? '';

        return array_map('intval', $clean === '' ? [] : str_split($clean));
    }

    /**
     * วิเคราะห์ชุดตัวเลข: ผลรวม / รากเลข / เลขท้าย / ความถี่ / คู่เลขติดกัน
     *
     * @return array{digits:int[], sum:int, root:int, last2:?string, last3:?string, freq:int[], pairs:string[]}
     */
    public static function analyzeDigits(string $raw): array
    {
        $d = self::digitsOf($raw);
        if ($d === []) {
            return ['digits' => [], 'sum' => 0, 'root' => 0, 'last2' => null, 'last3' => null, 'freq' => [], 'pairs' => []];
        }
        $freq = array_fill(0, 10, 0);
        foreach ($d as $x) {
            $freq[$x]++;
        }
        $pairs = [];
        for ($i = 0; $i < count($d) - 1; $i++) {
            $pairs[] = $d[$i].$d[$i + 1];
        }
        $n = count($d);
        $sum = array_sum($d);

        return [
            'digits' => $d,
            'sum' => $sum,
            'root' => self::reduce($sum),
            'last2' => $n >= 2 ? $d[$n - 2].$d[$n - 1] : null,
            'last3' => $n >= 3 ? $d[$n - 3].$d[$n - 2].$d[$n - 1] : null,
            'freq' => $freq,
            'pairs' => array_values(array_unique($pairs)),
        ];
    }

    /** ลดเป็นเลขเดี่ยว 1–9 (0 → 9) */
    public static function reduce(int $n): int
    {
        while ($n > 9) {
            $n = array_sum(array_map('intval', str_split((string) $n)));
        }

        return $n === 0 ? 9 : $n;
    }

    /** Life path: ผลรวม วัน+เดือน+ปี ค.ศ. → 1–9 (เก็บ 11/22 ไว้) */
    public static function lifePath(int $d, int $m, int $y): array
    {
        $sum = self::digitSum($d) + self::digitSum($m) + self::digitSum($y);
        $n = $sum;
        while ($n > 9 && $n !== 11 && $n !== 22) {
            $n = self::digitSum($n);
        }

        return ['sum' => $sum, 'number' => $n, 'root' => self::reduce($sum)];
    }

    private static function digitSum(int $n): int
    {
        return array_sum(array_map('intval', str_split((string) abs($n))));
    }
}
