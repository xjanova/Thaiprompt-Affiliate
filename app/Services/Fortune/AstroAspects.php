<?php

namespace App\Services\Fortune;

/**
 * 📐 มุมสัมพันธ์ระหว่างดาว (กุม/เล็ง/ตรีโกณ/จตุโกณ) — แหล่งเดียวของทั้งระบบ
 *
 * ⚠️ ทำไมต้องแยกออกมา (2026-09-09):
 *   ตารางมุม + ตัวคำนวณเดิมอยู่ใน DailyAstroBrief เป็น `private const` ⇒ **เลนดวงรายวัน
 *   ใช้ได้เลนเดียว** ส่วนเลนที่ลูกค้าจ่ายเงิน (39฿ / 99฿) ได้แค่ "ตำแหน่งดาว + ภพ"
 *   ไม่เคยได้ "ดาวดวงไหนเล็ง/กุมดวงไหน" เลยสักครั้ง — ซึ่งคือสิ่งแรกที่โหรจริงมอง
 *   ⇒ ยกขึ้นมาเป็นคลาสกลาง ให้ทั้ง 3 เลนอ่านตารางเดียวกัน แก้ที่เดียวจบ
 *
 * ศัพท์ใช้ตามตำราไทย: กุม (0°) · เล็ง (180°) · ตรีโกณ (120°) · จตุโกณ (90°) · สัมพันธ์ (60°)
 */
class AstroAspects
{
    /**
     * มุมที่นับ + ระยะคลาดเคลื่อนที่ยอมรับ (orb องศา)
     *
     * orb กว้างกว่าโหรสากลนิดหน่อย เพราะ ephemeris ของเราแม่นระดับ ~±0.3°
     * และคำทำนายไม่ได้ต้องการความละเอียดระดับลิปดา
     *
     * @var array<int, array{angle:int, orb:int, name:string, nature:string}>
     */
    public const ASPECTS = [
        ['angle' => 0,   'orb' => 8, 'name' => 'กุม',     'nature' => 'ผสานพลัง'],
        ['angle' => 60,  'orb' => 5, 'name' => 'สัมพันธ์', 'nature' => 'เกื้อกูลเบา ๆ'],
        ['angle' => 90,  'orb' => 6, 'name' => 'จตุโกณ',  'nature' => 'ขัดแย้ง-ต้องออกแรง'],
        ['angle' => 120, 'orb' => 7, 'name' => 'ตรีโกณ',  'nature' => 'ส่งเสริม-ไหลลื่น'],
        ['angle' => 180, 'orb' => 8, 'name' => 'เล็ง',     'nature' => 'ดึงกันคนละทาง'],
    ];

    /**
     * ดาวที่ไม่ควรนับมุมด้วยกัน — คู่ที่ผลลัพธ์ตายตัวจนไม่มีความหมายทางพยากรณ์
     *
     * ราหู-เกตุ อยู่ตรงข้ามกัน 180° ตลอดกาลโดยนิยาม ⇒ ถ้านับจะขึ้น "เล็ง" ทุกดวง ทุกวัน
     * = สัญญาณรบกวนล้วน ๆ ที่ทำให้บล็อกมุมดูเหมือนมีของทั้งที่ไม่มี
     *
     * @var array<int, array{0:string, 1:string}>
     */
    public const IGNORED_PAIRS = [
        ['Rahu', 'Ketu'],
    ];

    /**
     * ผลต่างมุม 2 ลองจิจูด → ช่วง [0,180]
     */
    public static function separation(float $lonA, float $lonB): float
    {
        $d = fmod(abs($lonA - $lonB), 360.0);

        return $d > 180.0 ? 360.0 - $d : $d;
    }

    /**
     * ดาว 2 ดวงนี้ทำมุมอะไรกันหรือเปล่า — null = ไม่เข้าเกณฑ์มุมไหนเลย
     *
     * @return array{name:string, nature:string, angle:int, orb:float}|null
     */
    public static function between(float $lonA, float $lonB): ?array
    {
        $sep = self::separation($lonA, $lonB);
        $best = null;

        foreach (self::ASPECTS as $aspect) {
            $orb = abs($sep - $aspect['angle']);
            if ($orb > $aspect['orb']) {
                continue;
            }
            // ดาวคู่หนึ่งจับมุมเดียว — เอามุมที่คลาดน้อยที่สุด
            if ($best === null || $orb < $best['orb']) {
                $best = [
                    'name' => $aspect['name'],
                    'nature' => $aspect['nature'],
                    'angle' => $aspect['angle'],
                    'orb' => round($orb, 1),
                ];
            }
        }

        return $best;
    }

    /**
     * มุมระหว่างดาวทุกคู่ในผังเดียว (ดวงกำเนิด ↔ ดวงกำเนิด)
     *
     * @param  array<string, array{lon:float, th:string}>  $positions  ผลจาก PlanetEphemeris::positions()
     * @param  array<int, string>|null  $only  จำกัดเฉพาะดาวชุดนี้ (null = ทุกดวงที่ส่งมา)
     * @return array<int, array{a:string, b:string, a_key:string, b_key:string, name:string, nature:string, orb:float}>
     */
    public static function withinChart(array $positions, ?array $only = null): array
    {
        $keys = array_keys($positions);
        if ($only !== null) {
            $keys = array_values(array_intersect($keys, $only));
        }

        $out = [];
        for ($i = 0; $i < count($keys); $i++) {
            for ($j = $i + 1; $j < count($keys); $j++) {
                $ka = $keys[$i];
                $kb = $keys[$j];
                if (self::isIgnoredPair($ka, $kb)) {
                    continue;
                }

                $hit = self::between($positions[$ka]['lon'] ?? 0.0, $positions[$kb]['lon'] ?? 0.0);
                if ($hit === null) {
                    continue;
                }

                $out[] = [
                    'a' => $positions[$ka]['th'] ?? $ka,
                    'b' => $positions[$kb]['th'] ?? $kb,
                    'a_key' => $ka,
                    'b_key' => $kb,
                    'name' => $hit['name'],
                    'nature' => $hit['nature'],
                    'orb' => $hit['orb'],
                ];
            }
        }

        // คลาดน้อย = มุมแน่น = สำคัญกว่า → ขึ้นก่อน
        usort($out, fn ($x, $y) => $x['orb'] <=> $y['orb']);

        return $out;
    }

    /**
     * มุมของ "ดาวจรวันนี้" กระทบ "ดาวในดวงกำเนิด" — หัวใจของการชี้ช่วงเวลา
     *
     * @param  array<string, array{lon:float, th:string}>  $transit  ดาวจร ณ วันนี้
     * @param  array<string, array{lon:float, th:string}>  $natal  ดาวกำเนิด
     * @param  array<int, string>|null  $transitOnly  ดาวจรที่สนใจ (ปกติเอาเฉพาะดาวช้า = จังหวะชีวิตจริง)
     * @return array<int, array{transit:string, natal:string, transit_key:string, natal_key:string, name:string, nature:string, orb:float}>
     */
    public static function transitToNatal(array $transit, array $natal, ?array $transitOnly = null): array
    {
        $out = [];
        foreach ($transit as $tk => $tp) {
            if ($transitOnly !== null && ! in_array($tk, $transitOnly, true)) {
                continue;
            }
            foreach ($natal as $nk => $np) {
                if (self::isIgnoredPair($tk, $nk)) {
                    continue;
                }

                $hit = self::between($tp['lon'] ?? 0.0, $np['lon'] ?? 0.0);
                if ($hit === null) {
                    continue;
                }

                $out[] = [
                    'transit' => $tp['th'] ?? $tk,
                    'natal' => $np['th'] ?? $nk,
                    'transit_key' => $tk,
                    'natal_key' => $nk,
                    'name' => $hit['name'],
                    'nature' => $hit['nature'],
                    'orb' => $hit['orb'],
                ];
            }
        }

        usort($out, fn ($x, $y) => $x['orb'] <=> $y['orb']);

        return $out;
    }

    /** คู่ที่สั่งไม่ให้นับ (ราหู-เกตุ) */
    private static function isIgnoredPair(string $a, string $b): bool
    {
        foreach (self::IGNORED_PAIRS as [$x, $y]) {
            if (($a === $x && $b === $y) || ($a === $y && $b === $x)) {
                return true;
            }
        }

        return false;
    }
}
