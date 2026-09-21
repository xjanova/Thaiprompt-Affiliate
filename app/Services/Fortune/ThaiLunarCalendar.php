<?php

namespace App\Services\Fortune;

/**
 * 📅 ปฏิทินจันทรคติไทยแบบปฏิทินหลวง (ขึ้น/แรม x ค่ำ เดือน y) — พอร์ตจาก GenLotto `Astro/ThaiLunar.php`
 *
 * ทำไมไม่คิดจากมุมจันทร์-อาทิตย์: ดิถีดาราศาสตร์กับดิถีในปฏิทินหลวงต่างกันได้ 1 วัน
 *   เพราะปฏิทินหลวงเป็นปฏิทินคำนวณ — เดือนคี่ 29 วัน เดือนคู่ 30 วัน · ปีอธิกมาสมีเดือน 8 หลัง (30 วัน)
 *   · ปีอธิกวารเพิ่ม 1 วันให้เดือน 7 · ปีเริ่มที่ขึ้น 1 ค่ำ เดือน 5
 *   วันพระ วันฤกษ์ล่าง (ดิถีโชค/มหาสูญ/พิฆาต ฯลฯ) ต้องอิงปฏิทินหลวง ไม่ใช่มุมจันทร์
 *
 * ชนิดของปีเป็นประกาศทางการ ไม่ใช่สูตรตายตัว → เก็บชนิดปีที่ยืนยันแล้วเป็นตาราง แล้วเดินหน้าจากจุดยึด
 *   ขึ้น 1 ค่ำ เดือน 5 ปีมะโรง = 9 เม.ย. 2567 (ค.ศ. 2024)
 *   ปี 2028–2057 = ชนิดปีจากปฏิทินโหรฯ myhora (ยังไม่มีประกาศทางการ) → ติดธง provisional
 *   พ้นตาราง = กฎเมตอน 19 ปี 7 อธิกมาสแบบประมาณ → ติดธง approx
 *
 * ✅ ตรวจแล้ว (GenLotto tests/verify_lunar.php + tests/Unit/Services/ThaiRuekYamTest ที่นี่):
 *   วันสำคัญทางศาสนา 15 วัน 2567–2570 + วันขึ้น 1 ค่ำ เดือน 5 ทุกปี 2568–2600 ตรง myhora ทุกวัน
 *
 * ⚠️ ก่อน 9 เม.ย. 2567 คืน null — ไม่มีข้อมูลยืนยัน
 *    ⇒ ใช้หา "วันจันทรคติของวันเกิดลูกค้า" ไม่ได้ ใช้ได้กับวันนี้/วันข้างหน้าเท่านั้น (ฤกษ์ วันพระ)
 */
final class ThaiLunarCalendar
{
    /** จุดยึด: วันที่ (ค.ศ.) ของ "ขึ้น 1 ค่ำ เดือน 5" ของปีจันทรคติที่ขึ้นต้นในปีนั้น */
    public const ANCHOR_DATE = '2024-04-09';

    public const ANCHOR_YEAR = 2024;

    /**
     * ชนิดปี (นับจากปีจันทรคติที่เริ่มเดือน 5 ในปี ค.ศ. นั้น)
     *   'M' = อธิกมาส (มีเดือน 8 หลัง) · 'W' = อธิกวาร (เดือน 7 มี 30 วัน) · 'N' = ปกติ
     *   2567 ปกติ · 2568 อธิกวาร · 2569 อธิกมาส (ประกาศสงกรานต์ 2569) · 2570 ปกติ
     */
    public const YEAR_TYPE = [
        2024 => 'N', 2025 => 'W', 2026 => 'M', 2027 => 'N',
        // 2028+ = ปฏิทินโหรฯ myhora (คำนวณ ยังไม่มีประกาศทางการ) — ตรวจวันขึ้น 1 ค่ำ เดือน 5 ทุกปีในเทสต์
        2028 => 'N', 2029 => 'M', 2030 => 'N', 2031 => 'M', 2032 => 'W', 2033 => 'N', 2034 => 'M', 2035 => 'W',
        2036 => 'N', 2037 => 'M', 2038 => 'N', 2039 => 'N', 2040 => 'M', 2041 => 'W', 2042 => 'N', 2043 => 'M',
        2044 => 'N', 2045 => 'M', 2046 => 'W', 2047 => 'N', 2048 => 'M', 2049 => 'N', 2050 => 'M', 2051 => 'N',
        2052 => 'W', 2053 => 'M', 2054 => 'N', 2055 => 'W', 2056 => 'M', 2057 => 'N',
    ];

    /** ปีสุดท้ายที่ยืนยันกับประกาศทางการ/วันสำคัญจริงแล้ว — หลังจากนี้ติดธง provisional */
    public const CONFIRMED_UNTIL = 2027;

    public const MONTH_TH = [1 => 'อ้าย', 2 => 'ยี่', 3 => 'สาม', 4 => 'สี่', 5 => 'ห้า', 6 => 'หก', 7 => 'เจ็ด', 8 => 'แปด', 9 => 'เก้า', 10 => 'สิบ', 11 => 'สิบเอ็ด', 12 => 'สิบสอง'];

    /** ปีนักษัตรตามปฏิทินหลวง เปลี่ยนที่ขึ้น 1 ค่ำ เดือน 5 */
    public const ANIMAL = ['ชวด', 'ฉลู', 'ขาล', 'เถาะ', 'มะโรง', 'มะเส็ง', 'มะเมีย', 'มะแม', 'วอก', 'ระกา', 'จอ', 'กุน'];

    /**
     * วันจันทรคติของวันที่ (ใช้แค่ปี-เดือน-วัน ไม่สนเวลา)
     *
     * @return array{waxing:bool, day:int, month:int, month_label:string, second_eighth:bool, animal:string, label:string, approx:bool, provisional:bool, holy:bool, month_days:int}|null
     *                                                                                                                                                                                    null = ก่อนจุดยึด (ไม่มีข้อมูลยืนยัน)
     */
    public static function of(\DateTimeInterface $date): ?array
    {
        $anchor = new \DateTimeImmutable(self::ANCHOR_DATE);
        $target = new \DateTimeImmutable($date->format('Y-m-d'));
        if ($target < $anchor) {
            return null;
        }

        $days = (int) $anchor->diff($target)->days;
        $year = self::ANCHOR_YEAR;
        $approx = false;

        // เดินทีละปีจันทรคติ
        while (true) {
            [$months, $len] = self::yearLayout($year, $approx);
            if ($days < $len) {
                break;
            }
            $days -= $len;
            $year++;
        }

        // เดินทีละเดือน เริ่มเดือน 5
        foreach ($months as [$m, $mlen, $second]) {
            if ($days < $mlen) {
                $waxing = $days < 15;
                $d = $waxing ? $days + 1 : $days - 14;
                $animalIdx = (($year - 4) % 12 + 12) % 12;
                $label = ($waxing ? 'ขึ้น ' : 'แรม ').$d.' ค่ำ เดือน'.self::MONTH_TH[$m].($second ? 'หลัง' : '');
                // วันพระ = ขึ้น 8 · ขึ้น 15 · แรม 8 · วันสุดท้ายของเดือน (แรม 14 ในเดือนขาด / แรม 15 ในเดือนเต็ม)
                $holy = ($waxing && ($d === 8 || $d === 15)) || (! $waxing && ($d === 8 || $d === $mlen - 15));

                return [
                    'waxing' => $waxing,
                    'day' => $d,
                    'month' => $m,
                    'month_label' => self::MONTH_TH[$m].($second ? 'หลัง' : ''),
                    'second_eighth' => $second,
                    'animal' => self::ANIMAL[$animalIdx],
                    'label' => $label,
                    'approx' => $approx,
                    'provisional' => $year > self::CONFIRMED_UNTIL,
                    'holy' => $holy,
                    'month_days' => $mlen,
                ];
            }
            $days -= $mlen;
        }

        return null;
    }

    /**
     * โครงเดือนของปีจันทรคติที่เริ่มในปี $year: [[เดือน, จำนวนวัน, เป็นเดือน 8 หลัง?], …] และความยาวรวม
     *
     * @return array{0: array<int, array{0:int,1:int,2:bool}>, 1:int}
     */
    private static function yearLayout(int $year, bool &$approx): array
    {
        if (isset(self::YEAR_TYPE[$year])) {
            $type = self::YEAR_TYPE[$year];
        } else {
            $approx = true;
            // เมตอน: 19 ปี 7 อธิกมาส (ประมาณ) นับจากปีอธิกมาสที่ยืนยันแล้ว 2026
            $type = ((($year - 2026) * 7) % 19 + 19) % 19 < 7 ? 'M' : 'N';
        }

        $months = [];
        foreach ([5, 6, 7, 8, 9, 10, 11, 12, 1, 2, 3, 4] as $m) {
            $len = $m % 2 === 0 ? 30 : 29;
            if ($m === 7 && $type === 'W') {
                $len = 30;
            }
            $months[] = [$m, $len, false];
            if ($m === 8 && $type === 'M') {
                $months[] = [8, 30, true];
            }
        }

        return [$months, array_sum(array_column($months, 1))];
    }
}
