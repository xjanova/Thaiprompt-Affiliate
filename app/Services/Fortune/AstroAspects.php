<?php

namespace App\Services\Fortune;

/**
 * 📐 มุมสัมพันธ์ระหว่างดาว (กุม/โยค/จตุโกณ/ตรีโกณ/เล็ง) — แหล่งเดียวของทั้งระบบ
 *
 * ⚠️ ทำไมต้องแยกออกมา (2026-09-09):
 *   ตารางมุม + ตัวคำนวณเดิมอยู่ใน DailyAstroBrief เป็น `private const` ⇒ **เลนดวงรายวัน
 *   ใช้ได้เลนเดียว** ส่วนเลนที่ลูกค้าจ่ายเงิน (39฿ / 99฿) ได้แค่ "ตำแหน่งดาว + ภพ"
 *   ⇒ ยกขึ้นมาเป็นคลาสกลาง ให้ทั้ง 3 เลนอ่านตารางเดียวกัน แก้ที่เดียวจบ
 *
 * 🇹🇭 (2026-09-21 เจ้าของสั่ง "ยึดตำราโหราจาก genlotto เป็นหลักทั้งหมด") — ยกหลักของ GenLotto `Astro/Aspects.php` มาทั้งชุด:
 *   1. **มุมนับจาก "ราศี" ไม่ใช่องศา** — ห่าง 0 ราศี = กุม · 2/10 = โยค · 3/9 = จตุโกณ · 4/8 = ตรีโกณ · 6 = เล็ง
 *      ห่าง 1, 5, 7, 11 ราศี = ไม่มีมุม · ดาว 29° มีน กับ 1° เมษ ห่าง 2° แต่คนละราศี ⇒ ไม่ใช่กุม (ระบบองศาสากลนับ)
 *   2. มุม 60° ตำราไทยเรียก **"โยค"** (เดิมเขียน "สัมพันธ์")
 *   3. orb = องศาที่คลาดจากมุมพอดี ใช้บอกว่า "แน่น" แค่ไหนเท่านั้น (แน่น = คลาด ≤ 8°)
 *   4. ดี/ร้าย: ตรีโกณ-โยค ส่งเสริม · จตุโกณ-เล็ง ขัดแย้ง · กุม ขึ้นกับคู่ดาว
 *      (ศุภเคราะห์ = จันทร์ 2 · พุธ 4 · พฤหัส 5 · ศุกร์ 6 — ศุภกุมศุภ ดี · บาปกุมบาป ร้าย · ปนกัน ผสม)
 */
class AstroAspects
{
    /** ระยะห่างราศี (0–11) → ชื่อมุม — GenLotto `Aspects::THAI_REL` */
    public const SIGN_DISTANCE = [0 => 'กุม', 2 => 'โยค', 10 => 'โยค', 3 => 'จตุโกณ', 9 => 'จตุโกณ', 4 => 'ตรีโกณ', 8 => 'ตรีโกณ', 6 => 'เล็ง'];

    /**
     * มุมพอดีของแต่ละชื่อ (ใช้วัดความแน่นเท่านั้น) + ธรรมชาติ — GenLotto `Aspects::EXACT` / `NATURE`
     *
     * @var array<int, array{angle:int, name:string, nature:string}>
     */
    public const ASPECTS = [
        ['angle' => 0,   'name' => 'กุม',    'nature' => 'ผสานพลัง'],
        ['angle' => 60,  'name' => 'โยค',    'nature' => 'เกื้อกูล'],
        ['angle' => 90,  'name' => 'จตุโกณ', 'nature' => 'ขัดแย้ง'],
        ['angle' => 120, 'name' => 'ตรีโกณ', 'nature' => 'ส่งเสริม'],
        ['angle' => 180, 'name' => 'เล็ง',    'nature' => 'ดึงกัน'],
    ];

    /** ศุภเคราะห์ (เลขดาว) — จันทร์ พุธ พฤหัสบดี ศุกร์ · นอกนั้นบาปเคราะห์ */
    public const BENEFIC = [2, 4, 5, 6];

    /** มุมที่ถือว่า "แน่น" (คลาดจากมุมพอดีไม่เกิน องศา) */
    public const TIGHT_ORB = 8.0;

    /**
     * ดาวที่ไม่ควรนับมุมด้วยกัน — คู่ที่ผลลัพธ์ตายตัวจนไม่มีความหมายทางพยากรณ์
     *
     * ☋ (2026-09-11) **ว่างโดยตั้งใจ** — เกตุไทยสุริยยาตร์เดินเองรอบละ 679 วัน ระยะราหู-เกตุแกว่งจริงทั้งวง
     *    ถ้าวันหน้าจะเพิ่มคู่ใหม่ ต้องเป็นคู่ที่ "มุมตายตัวโดยนิยาม" เท่านั้น
     *
     * @var array<int, array{0:string, 1:string}>
     */
    public const IGNORED_PAIRS = [];

    /**
     * ผลต่างมุม 2 ลองจิจูด → ช่วง [0,180]
     */
    public static function separation(float $lonA, float $lonB): float
    {
        $d = fmod(abs($lonA - $lonB), 360.0);

        return $d > 180.0 ? 360.0 - $d : $d;
    }

    /** ราศี (0=เมษ) ของลองจิจูดนิรายนะ */
    public static function signOf(float $lon): int
    {
        $l = fmod($lon, 360.0);
        if ($l < 0) {
            $l += 360.0;
        }

        return (int) floor($l / 30.0) % 12;
    }

    /**
     * ดาว 2 ดวงนี้ทำมุมอะไรกัน (นับตามราศี) — null = ห่าง 1/5/7/11 ราศี ไม่มีมุม
     *
     * @param  float  $lonA  ลองจิจูดนิรายนะดาว A
     * @param  float  $lonB  ลองจิจูดนิรายนะดาว B
     * @param  int|null  $numA  เลขดาว A (1=อาทิตย์ … 8=ราหู 9=เกตุ 0=มฤตยู) — ใช้ตัดสินดี/ร้ายของ "กุม"
     * @param  int|null  $numB  เลขดาว B
     * @return array{name:string, nature:string, angle:int, orb:float, sep:float, tone:string, good:bool, tight:bool}|null
     */
    public static function between(float $lonA, float $lonB, ?int $numA = null, ?int $numB = null): ?array
    {
        $d = ((self::signOf($lonB) - self::signOf($lonA)) % 12 + 12) % 12;
        $name = self::SIGN_DISTANCE[$d] ?? null;
        if ($name === null) {
            return null;
        }

        $aspect = null;
        foreach (self::ASPECTS as $a) {
            if ($a['name'] === $name) {
                $aspect = $a;
                break;
            }
        }

        $sep = self::separation($lonA, $lonB);
        $orb = abs($sep - $aspect['angle']);

        if ($name === 'ตรีโกณ' || $name === 'โยค') {
            $tone = 'ดี';
        } elseif ($name === 'จตุโกณ' || $name === 'เล็ง') {
            $tone = 'ร้าย';
        } elseif ($numA === null || $numB === null) {
            $tone = 'ผสม'; // ไม่รู้คู่ดาว = ตัดสินไม่ได้ ห้ามเดา
        } else {
            $ga = in_array($numA, self::BENEFIC, true);
            $gb = in_array($numB, self::BENEFIC, true);
            $tone = $ga && $gb ? 'ดี' : (! $ga && ! $gb ? 'ร้าย' : 'ผสม');
        }

        return [
            'name' => $name,
            'nature' => $aspect['nature'],
            'angle' => $aspect['angle'],
            'orb' => round($orb, 1),
            'sep' => round($sep, 1),
            'tone' => $tone,
            'good' => $tone === 'ดี',
            'tight' => $orb <= self::TIGHT_ORB,
        ];
    }

    /**
     * มุมระหว่างดาวทุกคู่ในผังเดียว (ดวงกำเนิด ↔ ดวงกำเนิด) — เรียงคลาดน้อยก่อน
     *
     * @param  array<string, array{lon:float, th:string, num?:int}>  $positions  ผลจาก PlanetEphemeris::positions()
     * @param  array<int, string>|null  $only  จำกัดเฉพาะดาวชุดนี้ (null = ทุกดวงที่ส่งมา)
     * @return array<int, array{a:string, b:string, a_key:string, b_key:string, name:string, nature:string, orb:float, tone:string, tight:bool}>
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

                $hit = self::between(
                    $positions[$ka]['lon'] ?? 0.0,
                    $positions[$kb]['lon'] ?? 0.0,
                    $positions[$ka]['num'] ?? null,
                    $positions[$kb]['num'] ?? null
                );
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
                    'tone' => $hit['tone'],
                    'tight' => $hit['tight'],
                ];
            }
        }

        // คลาดน้อย = มุมแน่น = สำคัญกว่า → ขึ้นก่อน
        usort($out, fn ($x, $y) => $x['orb'] <=> $y['orb']);

        return $out;
    }

    /**
     * มุมของ "ดาวจรวันนี้" กระทบ "ดาวในดวงกำเนิด" (นับราศีเหมือนกัน) — หัวใจของการชี้ช่วงเวลา
     *
     * @param  array<string, array{lon:float, th:string, num?:int}>  $transit  ดาวจร ณ วันนี้
     * @param  array<string, array{lon:float, th:string, num?:int}>  $natal  ดาวกำเนิด
     * @param  array<int, string>|null  $transitOnly  ดาวจรที่สนใจ (ปกติเอาเฉพาะดาวช้า = จังหวะชีวิตจริง)
     * @return array<int, array{transit:string, natal:string, transit_key:string, natal_key:string, name:string, nature:string, orb:float, tone:string, tight:bool}>
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

                $hit = self::between($tp['lon'] ?? 0.0, $np['lon'] ?? 0.0, $tp['num'] ?? null, $np['num'] ?? null);
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
                    'tone' => $hit['tone'],
                    'tight' => $hit['tight'],
                ];
            }
        }

        usort($out, fn ($x, $y) => $x['orb'] <=> $y['orb']);

        return $out;
    }

    /** คู่ที่สั่งไม่ให้นับ (ดู IGNORED_PAIRS — ตอนนี้ว่าง) */
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
