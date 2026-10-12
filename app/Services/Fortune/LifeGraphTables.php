<?php

namespace App\Services\Fortune;

/**
 * 📈 ตารางตำราที่กราฟชีวิตใช้ — สำเนาตรงตัวจาก GenLotto (site/app/Astro/ThaiTables.php + Chart::thaksa)
 *
 * แยกไว้ให้ LifeGraph พอร์ตมาจาก GenLotto ได้ทั้งไฟล์ (ตำราโหรของแม่หมอยึด GenLotto ตั้งแต่ 2026-09-21)
 * แก้ค่าในนี้ ⇒ แก้ฝั่ง GenLotto ด้วย ห้ามให้สองที่ต่างกัน
 * เลขดาว: อาทิตย์ 1 จันทร์ 2 อังคาร 3 พุธ 4 พฤหัส 5 ศุกร์ 6 เสาร์ 7 ราหู 8
 */
final class LifeGraphTables
{
    public const NUM_TH = [1 => 'อาทิตย์', 2 => 'จันทร์', 3 => 'อังคาร', 4 => 'พุธ', 5 => 'พฤหัสบดี', 6 => 'ศุกร์', 7 => 'เสาร์', 8 => 'ราหู'];

    /** ลำดับทักษา (เดินตามเข็ม): อาทิตย์ จันทร์ อังคาร พุธ เสาร์ พฤหัส ราหู ศุกร์ */
    public const THAKSA_ORDER = [1, 2, 3, 4, 7, 5, 8, 6];

    public const THAKSA_BHAVA = ['บริวาร', 'อายุ', 'เดช', 'ศรี', 'มูละ', 'อุตสาหะ', 'มนตรี', 'กาลกิณี'];

    /** กำลังพระเคราะห์ (ปีเสวยอายุ มหาทักษา รวม 108) */
    public const PERIOD_YEARS = [1 => 6, 2 => 15, 3 => 8, 4 => 17, 7 => 10, 5 => 19, 8 => 12, 6 => 21];

    /** ดาวคู่ตามตำราไทย (คู่ธาตุไม่นับ — ๒-๕ เป็นทั้งคู่ธาตุและคู่ศัตรู) */
    public const PAIR_FRIEND = [[1, 5], [2, 4], [3, 6], [7, 8]];

    public const PAIR_ENEMY = [[1, 3], [2, 5], [4, 8], [6, 7]];

    public const PAIR_SOMPHON = [[1, 6], [2, 8], [3, 5], [4, 7]];

    public const THAKSA_HEX = [1 => '#d8402f', 2 => '#f4ecd2', 3 => '#f291b8', 4 => '#3a9b3a', 5 => '#f08a24', 6 => '#3d8de0', 7 => '#3b2a5c', 8 => '#8d929c'];

    /** ราศีไทยตามลำดับ sign_index 0–11 (ตรงกับ config thai_astrology_knowledge.zodiac_order) */
    public const SIGNS = ['เมษ', 'พฤษภ', 'เมถุน', 'กรกฎ', 'สิงห์', 'กันย์', 'ตุลย์', 'พิจิก', 'ธนู', 'มังกร', 'กุมภ์', 'มีน'];

    /** มาตรฐานดาวตำราไทย (sign_index) — ราหูเกษตรกุมภ์ อุจพิจิก นิจพฤษภ · เสาร์เกษตรมังกรราศีเดียว */
    public const DIGNITY = [
        1 => ['rules' => [4], 'exalted' => 0, 'debilitated' => 6, 'detriment' => [10]],
        2 => ['rules' => [3], 'exalted' => 1, 'debilitated' => 7, 'detriment' => [9]],
        3 => ['rules' => [0, 7], 'exalted' => 9, 'debilitated' => 3, 'detriment' => [6, 1]],
        4 => ['rules' => [2, 5], 'exalted' => 5, 'debilitated' => 11, 'detriment' => [8, 11]],
        5 => ['rules' => [8, 11], 'exalted' => 3, 'debilitated' => 9, 'detriment' => [2, 5]],
        6 => ['rules' => [1, 6], 'exalted' => 11, 'debilitated' => 5, 'detriment' => [7, 0]],
        7 => ['rules' => [9], 'exalted' => 6, 'debilitated' => 0, 'detriment' => [3]],
        8 => ['rules' => [10], 'exalted' => 7, 'debilitated' => 1, 'detriment' => [4]],
    ];

    public const HOUSE_NAMES = [1 => 'ตนุ', 2 => 'กดุมภะ', 3 => 'สหัชชะ', 4 => 'พันธุ', 5 => 'ปุตตะ', 6 => 'อริ', 7 => 'ปัตนิ', 8 => 'มรณะ', 9 => 'ศุภะ', 10 => 'กัมมะ', 11 => 'ลาภะ', 12 => 'วินาสน์'];

    private const MONTHS = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

    public static function planetTh(int $num): string
    {
        return self::NUM_TH[$num] ?? '?';
    }

    /** เลขดาวจากชื่อไทย ("พฤหัส" / "พฤหัสบดี" ได้ทั้งคู่) — ไม่รู้จัก = null */
    public static function planetNum(string $th): ?int
    {
        $th = trim((string) preg_replace('/^ดาว/u', '', trim($th)));
        if ($th === 'พฤหัส') {
            return 5;
        }
        $n = array_search($th, self::NUM_TH, true);

        return $n === false ? null : (int) $n;
    }

    /** ทักษา 8 ภูมิจากเลขดาวเจ้าวันเกิด (= GenLotto Chart::thaksa) */
    public static function thaksa(int $birthLord): array
    {
        $start = array_search($birthLord, self::THAKSA_ORDER, true);
        $start = $start === false ? 0 : $start;
        $out = [];
        foreach (self::THAKSA_BHAVA as $i => $bhava) {
            $p = self::THAKSA_ORDER[($start + $i) % 8];
            $out[] = ['bhava' => $bhava, 'planet' => $p, 'planet_th' => self::planetTh($p)];
        }

        return $out;
    }

    /** มาตรฐานดาว: เกษตร / อุจ / นิจ / ประ / กลาง (= GenLotto ThaiTables::dignity) */
    public static function dignity(int $num, int $signIndex): string
    {
        $d = self::DIGNITY[$num] ?? null;
        if ($d === null) {
            return 'กลาง';
        }
        if (in_array($signIndex, $d['rules'], true)) {
            return 'เกษตร';
        }
        if ($signIndex === $d['exalted']) {
            return 'อุจ';
        }
        if ($signIndex === $d['debilitated']) {
            return 'นิจ';
        }
        if (in_array($signIndex, $d['detriment'], true)) {
            return 'ประ';
        }

        return 'กลาง';
    }

    /** วันที่ไทยแบบย่อ (ค.ศ. → พ.ศ.) */
    public static function thDate(string $ymd): string
    {
        [$y, $m, $d] = array_map('intval', explode('-', substr($ymd, 0, 10)));

        return $d.' '.self::MONTHS[$m - 1].' '.($y + 543);
    }
}
