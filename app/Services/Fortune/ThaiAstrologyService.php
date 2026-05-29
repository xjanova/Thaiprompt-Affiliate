<?php

namespace App\Services\Fortune;

use Carbon\Carbon;

/**
 * บริการคำนวณดวงดาวพื้นฐานจากวันเดือนปีเกิด (โหราศาสตร์ไทย + ราศีสากล)
 *
 * 🌟 จุดประสงค์ (2026-05-30 — bill FTU-260530-Z4397):
 *   เดิม Celtic Cross 99฿ "ใช้พลังไพ่ล้วน" — ส่ง birthDate=null เข้า AI เสมอ
 *   เมื่อลูกค้าพิมพ์วันเกิดมาในคำถาม (เช่น "เกิด 27/6/1978 ... คู่ปรับเกิด 6/2/2532")
 *   AI เห็นแค่ "ตัวเลขดิบ" แล้วเดานิสัยเอง — ไม่ได้คำนวณดวงดาวจริง
 *   → service นี้ทำหน้าที่:
 *      1. ตรวจจับวันเกิดจากข้อความอิสระ (รองรับหลายคน เช่นถามความเข้ากันคู่รัก)
 *      2. คำนวณ ราศี / ดาวเจ้าชนะ / ธาตุ / ดาวมิตร-ศัตรู / สี-เลขมงคล / พื้นนิสัย
 *      3. สร้างบล็อกข้อมูลพร้อม directive ให้ AI ผสานกับไพ่ → ทำนายแม่นขึ้น
 *
 * ⚠️ ออกแบบเป็น stateless ไม่มี dependency (ไม่ผูก API key pool เหมือน FortuneAIService)
 *    → สร้าง `new ThaiAstrologyService()` ได้เลย ไม่เปลือง pool key
 *
 * 📌 หมายเหตุ: ตาราง getPlanetByDayOfWeek / getZodiacSign คัดลอกตรรกะมาจาก
 *    FortuneAIService::formatBirthDateSection (ที่ Deep 39฿ ใช้) — ตั้งใจแยกสำเนา
 *    เพื่อไม่แตะ flow Deep 39฿ ที่ทำงานอยู่ (commercial paid flow). ถ้าแก้ค่าดาว/สี
 *    ต้อง sync ทั้งสองที่
 */
class ThaiAstrologyService
{
    /** จำนวนคนสูงสุดที่จะคำนวณต่อหนึ่งข้อความ (กัน prompt บวม + กัน abuse) */
    public const MAX_PEOPLE = 3;

    /**
     * สร้างบล็อกดวงดาวสำหรับ Celtic — ตรวจจับวันเกิดในข้อความแล้วคำนวณให้ครบ
     *
     * @param  string  $text  ข้อความอิสระจากลูกค้า (คำถามปัจจุบัน + คำถามเก่า รวมกันได้)
     * @return string บล็อก directive พร้อม inject เข้า prompt — คืนค่าว่าง ('') ถ้าไม่พบวันเกิด
     *
     * @example
     * $block = (new ThaiAstrologyService())
     *     ->buildCelticBirthAstrologyBlock('เกิด 27/6/1978 คู่ปรับเกิด 6/2/2532');
     * // → บล็อกดวงดาว 2 คน + directive ให้ผสานกับไพ่
     */
    public function buildCelticBirthAstrologyBlock(string $text): string
    {
        $dates = $this->extractBirthDatesFromText($text);

        if (empty($dates)) {
            return '';
        }

        $multi = count($dates) > 1;
        $people = '';

        foreach ($dates as $i => $d) {
            // ป้ายกำกับคน — ถ้ามีหลายคนให้ระบุลำดับ เพื่อให้ AI map กับบริบท (เจ้าชะตา vs คู่/อีกฝ่าย)
            $label = $multi
                ? '👤 คนที่ '.($i + 1)." (เกิด {$d['raw']})"
                : "👤 เจ้าของวันเกิด (เกิด {$d['raw']})";

            $people .= $label."\n".$this->formatPersonBlock($d['ymd'])."\n";
        }

        // directive — บอก AI ชัดเจนว่าให้ใช้ดวงดาวนี้ผสานไพ่ + ห้ามขอวันเกิดซ้ำ
        $directive = "👉 วิธีใช้ดวงดาวนี้ (สำคัญ):\n"
            ."• ผสาน ราศี/ดาวเจ้าชนะ/ธาตุ/พื้นนิสัย เข้ากับไพ่ที่เปิดไว้ — อย่าทำนายจากไพ่อย่างเดียว\n";

        if ($multi) {
            $directive .= "• ถ้าถามเรื่อง \"เข้ากันไหม/ความสัมพันธ์\" → เทียบธาตุ + ดาวมิตร/ดาวศัตรู ของทั้งสองคนให้ชัด\n";
        }

        $directive .= "• เชื่อมจังหวะดาว/วันมงคล กับไพ่ตำแหน่งอนาคต → ระบุช่วงเวลาให้แม่นขึ้น\n"
            ."• พื้นนิสัยจากดาวเจ้าชนะ = \"พื้นฐานดวง\" ของคนนั้น ใช้ยืนยัน/เสริมสิ่งที่ไพ่บอก\n"
            .'• ⚠️ เจ้าชะตาให้วันเกิดมาแล้ว — ห้ามถามวันเกิดซ้ำ ใช้ข้อมูลด้านบนได้เลย';

        return "\n━━━━━━━━━━━━━━━━━\n"
            ."🌟 ดวงดาวจากวันเกิดที่เจ้าชะตาให้ (แม่หมอคำนวณให้แล้ว — ใช้ผสานกับไพ่)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            .$people."\n"
            .$directive."\n\n";
    }

    /**
     * ดึงวันเดือนปีเกิดจากข้อความอิสระ
     *
     * รองรับ:
     *   - รูปแบบ วัน/เดือน/ปี (ไทยเขียนวันก่อน) เช่น 27/6/1978, 6-2-2532, 27.6.1978
     *   - ปี ค.ศ. (1900-2100) และ ปี พ.ศ. (2400-2600 → ลบ 543)
     *   - เลขไทย (๒๗/๖/๒๕๓๒) — normalize เป็นอารบิกก่อน
     *
     * กันจับผิด (false positive):
     *   - ต้องเป็นปี 4 หลัก → "เวลา 12.00" (ปี 2 หลัก) ไม่ถูกจับ
     *   - ตรวจ checkdate + อายุ 0-120 ปี → ตัดวันที่ที่เป็นไปไม่ได้/ไม่ใช่วันเกิด
     *
     * @param  string  $text  ข้อความ
     * @param  int  $max  จำนวนคนสูงสุด
     * @return array<int, array{raw: string, ymd: string}> เรียงตามที่พบ, ตัดซ้ำด้วย ymd
     */
    public function extractBirthDatesFromText(string $text, int $max = self::MAX_PEOPLE): array
    {
        $text = $this->normalizeThaiDigits($text);

        // (?<!\d) / (?!\d) กันไม่ให้ไปจับเลขที่อยู่กลางตัวเลขยาว ๆ
        // วัน(1-2)/เดือน(1-2)/ปี(4 หลัก) — delimiter เป็น / . หรือ - (มี space คั่นได้)
        $pattern = '/(?<!\d)(\d{1,2})\s*[\/.\-]\s*(\d{1,2})\s*[\/.\-]\s*(\d{4})(?!\d)/u';

        if (! preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $results = [];
        $seen = [];
        $currentYear = Carbon::now()->year;

        foreach ($matches as $m) {
            $day = (int) $m[1];
            $month = (int) $m[2];
            $year4 = (int) $m[3];

            // แปลงปี พ.ศ. → ค.ศ. (พ.ศ. 2400-2600 = ค.ศ. 1857-2057)
            if ($year4 >= 2400 && $year4 <= 2600) {
                $adYear = $year4 - 543;
            } elseif ($year4 >= 1900 && $year4 <= 2100) {
                $adYear = $year4;
            } else {
                // ปีไม่อยู่ในช่วงที่สมเหตุสมผล → ข้าม (กันจับผิด)
                continue;
            }

            // ตรวจวันที่จริง + อายุสมเหตุสมผล (0-120 ปี)
            if (! checkdate($month, $day, $adYear)) {
                continue;
            }
            if ($adYear > $currentYear || $adYear < $currentYear - 120) {
                continue;
            }

            $ymd = sprintf('%04d-%02d-%02d', $adYear, $month, $day);

            // ตัดวันเกิดซ้ำ (เช่นลูกค้าพิมพ์เลขเดิมในหลายคำถาม)
            if (isset($seen[$ymd])) {
                continue;
            }
            $seen[$ymd] = true;

            $results[] = [
                'raw' => trim($m[0]),
                'ymd' => $ymd,
            ];

            if (count($results) >= $max) {
                break;
            }
        }

        return $results;
    }

    /**
     * สร้างบล็อกดวงดาวของคนหนึ่งคน (กระชับ — สำหรับ inject หลายคน)
     *
     * @param  string  $ymd  วันเกิดรูปแบบ Y-m-d
     * @return string บล็อกข้อความ (ไม่มี directive ท้าย — directive รวมอยู่ที่ buildCelticBirthAstrologyBlock)
     */
    public function formatPersonBlock(string $ymd): string
    {
        try {
            $date = Carbon::parse($ymd);
        } catch (\Throwable $e) {
            return "(วันเกิด: {$ymd})";
        }

        $thaiMonths = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        $dayNames = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];

        $thaiYear = $date->year + 543;
        $age = $date->age;
        $dow = $date->dayOfWeek; // 0=อาทิตย์ ... 6=เสาร์
        $dayName = $dayNames[$dow];
        $zodiac = $this->getZodiacSign($date->month, $date->day);
        $p = $this->getPlanetByDayOfWeek($dow);

        return "📅 {$date->day} {$thaiMonths[$date->month]} {$thaiYear} (วัน{$dayName}, อายุ {$age} ปี)\n"
            ."♈ ราศี: {$zodiac}\n"
            ."⭐ ดาวเจ้าชนะ: {$p['planet']} | 🔥 ธาตุ: {$p['element']}\n"
            ."🤝 ดาวมิตร: {$p['friends']} | ⚔️ ดาวศัตรู: {$p['enemies']}\n"
            ."🎨 สีมงคล: {$p['lucky_color']} | 🔢 เลขมงคล: {$p['lucky_number']}\n"
            ."💎 พื้นนิสัย (ตามดาวเจ้าชนะ): {$p['personality']}\n";
    }

    /**
     * แปลงเลขไทย (๐-๙) เป็นเลขอารบิก (0-9)
     *
     * @param  string  $text  ข้อความที่อาจมีเลขไทย
     * @return string ข้อความที่เลขไทยถูกแปลงแล้ว
     */
    protected function normalizeThaiDigits(string $text): string
    {
        return str_replace(
            ['๐', '๑', '๒', '๓', '๔', '๕', '๖', '๗', '๘', '๙'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $text
        );
    }

    /**
     * คำนวณดาวเคราะห์ประจำวันเกิดตามหลักโหราศาสตร์ไทย (เจ้าชนะ)
     *
     * ⚠️ sync กับ FortuneAIService::getPlanetByDayOfWeek (ดู class doc)
     *
     * @param  int  $dayOfWeek  0=อาทิตย์, 1=จันทร์, ...6=เสาร์
     * @return array{planet:string, element:string, friends:string, enemies:string, lucky_color:string, unlucky_color:string, lucky_number:string, lucky_days:string, unlucky_days:string, personality:string}
     */
    public function getPlanetByDayOfWeek(int $dayOfWeek): array
    {
        $planets = [
            0 => [ // อาทิตย์
                'planet' => 'ดาวอาทิตย์ (☉)',
                'element' => 'ธาตุไฟ',
                'friends' => 'ดาวพฤหัสบดี, ดาวอังคาร',
                'enemies' => 'ดาวเสาร์, ราหู',
                'lucky_color' => 'แดง, ส้ม, ทอง',
                'unlucky_color' => 'ดำ, ม่วงเข้ม',
                'lucky_number' => '1, 6, 9',
                'lucky_days' => 'วันพฤหัสบดี, วันอังคาร',
                'unlucky_days' => 'วันเสาร์',
                'personality' => 'มีอำนาจ เป็นผู้นำ มีศักดิ์ศรี มั่นใจ กล้าตัดสินใจ',
            ],
            1 => [ // จันทร์
                'planet' => 'ดาวจันทร์ (☽)',
                'element' => 'ธาตุน้ำ',
                'friends' => 'ดาวพุธ, ดาวศุกร์',
                'enemies' => 'ราหู, ดาวเสาร์',
                'lucky_color' => 'ขาว, ครีม, เงิน',
                'unlucky_color' => 'ดำ, น้ำเงินเข้ม',
                'lucky_number' => '2, 5, 7',
                'lucky_days' => 'วันพุธ, วันศุกร์',
                'unlucky_days' => 'วันเสาร์',
                'personality' => 'อ่อนโยน มีเมตตา จิตใจดี อารมณ์อ่อนไหว รักครอบครัว',
            ],
            2 => [ // อังคาร
                'planet' => 'ดาวอังคาร (♂)',
                'element' => 'ธาตุไฟ',
                'friends' => 'ดาวอาทิตย์, ดาวพฤหัสบดี',
                'enemies' => 'ดาวพุธ, ดาวเสาร์',
                'lucky_color' => 'ชมพู, แดงอ่อน, ส้ม',
                'unlucky_color' => 'เขียว, ดำ',
                'lucky_number' => '3, 6, 9',
                'lucky_days' => 'วันอาทิตย์, วันพฤหัสบดี',
                'unlucky_days' => 'วันเสาร์, วันพุธ',
                'personality' => 'กล้าหาญ ร้อนแรง ทะเยอทะยาน มีพลัง ไม่ยอมแพ้',
            ],
            3 => [ // พุธ
                'planet' => 'ดาวพุธ (☿)',
                'element' => 'ธาตุดิน',
                'friends' => 'ดาวจันทร์, ดาวศุกร์',
                'enemies' => 'ราหู, ดาวอังคาร',
                'lucky_color' => 'เขียว, เขียวอ่อน',
                'unlucky_color' => 'แดง, ชมพูเข้ม',
                'lucky_number' => '4, 2, 7',
                'lucky_days' => 'วันจันทร์, วันศุกร์',
                'unlucky_days' => 'วันอังคาร',
                'personality' => 'ฉลาด มีไหวพริบ พูดเก่ง ค้าขายเก่ง ปรับตัวเก่ง',
            ],
            4 => [ // พฤหัสบดี
                'planet' => 'ดาวพฤหัสบดี (♃)',
                'element' => 'ธาตุน้ำ',
                'friends' => 'ดาวอาทิตย์, ดาวอังคาร',
                'enemies' => 'ราหู, ดาวเสาร์',
                'lucky_color' => 'ส้ม, เหลือง, ทอง',
                'unlucky_color' => 'ดำ, ม่วงเข้ม',
                'lucky_number' => '5, 1, 3',
                'lucky_days' => 'วันอาทิตย์, วันอังคาร',
                'unlucky_days' => 'วันเสาร์',
                'personality' => 'มีปัญญา ใจกว้าง โชคดี ศรัทธา รักความยุติธรรม ผู้ทรงคุณธรรม',
            ],
            5 => [ // ศุกร์
                'planet' => 'ดาวศุกร์ (♀)',
                'element' => 'ธาตุน้ำ',
                'friends' => 'ดาวพุธ, ดาวจันทร์',
                'enemies' => 'ดาวอาทิตย์, ดาวอังคาร',
                'lucky_color' => 'ฟ้า, ฟ้าอ่อน, ขาว',
                'unlucky_color' => 'แดง, ส้มเข้ม',
                'lucky_number' => '6, 2, 4',
                'lucky_days' => 'วันพุธ, วันจันทร์',
                'unlucky_days' => 'วันอาทิตย์, วันอังคาร',
                'personality' => 'รักสวยรักงาม มีเสน่ห์ มีศิลปะ รักความหรูหรา โรแมนติก',
            ],
            6 => [ // เสาร์
                'planet' => 'ดาวเสาร์ (♄)',
                'element' => 'ธาตุไฟ',
                'friends' => 'ราหู, ดาวพฤหัสบดี',
                'enemies' => 'ดาวอาทิตย์, ดาวอังคาร',
                'lucky_color' => 'ม่วง, ดำ, น้ำเงินเข้ม',
                'unlucky_color' => 'แดง, ส้ม',
                'lucky_number' => '7, 5, 8',
                'lucky_days' => 'วันพฤหัสบดี',
                'unlucky_days' => 'วันอาทิตย์, วันอังคาร',
                'personality' => 'อดทน มุ่งมั่น รอบคอบ มีวินัย จริงจัง ทำอะไรทำจริง',
            ],
        ];

        return $planets[$dayOfWeek] ?? $planets[0];
    }

    /**
     * คำนวณราศีจากเดือนและวันเกิด (ราศีสากล Western Zodiac)
     *
     * ⚠️ sync กับ FortuneAIService::getZodiacSign (ดู class doc)
     *
     * @param  int  $month  เดือน (1-12)
     * @param  int  $day  วัน (1-31)
     * @return string ชื่อราศี (ไทย + อังกฤษ)
     */
    public function getZodiacSign(int $month, int $day): string
    {
        $signs = [
            ['name' => 'มังกร (Capricorn)', 'end_month' => 1, 'end_day' => 19],
            ['name' => 'กุมภ์ (Aquarius)', 'end_month' => 2, 'end_day' => 18],
            ['name' => 'มีน (Pisces)', 'end_month' => 3, 'end_day' => 20],
            ['name' => 'เมษ (Aries)', 'end_month' => 4, 'end_day' => 19],
            ['name' => 'พฤษภ (Taurus)', 'end_month' => 5, 'end_day' => 20],
            ['name' => 'เมถุน (Gemini)', 'end_month' => 6, 'end_day' => 20],
            ['name' => 'กรกฎ (Cancer)', 'end_month' => 7, 'end_day' => 22],
            ['name' => 'สิงห์ (Leo)', 'end_month' => 8, 'end_day' => 22],
            ['name' => 'กันย์ (Virgo)', 'end_month' => 9, 'end_day' => 22],
            ['name' => 'ตุลย์ (Libra)', 'end_month' => 10, 'end_day' => 22],
            ['name' => 'พิจิก (Scorpio)', 'end_month' => 11, 'end_day' => 21],
            ['name' => 'ธนู (Sagittarius)', 'end_month' => 12, 'end_day' => 21],
        ];

        foreach ($signs as $sign) {
            if ($month === $sign['end_month'] && $day <= $sign['end_day']) {
                return $sign['name'];
            }
            if ($month < $sign['end_month']) {
                return $sign['name'];
            }
        }

        return 'มังกร (Capricorn)'; // ธันวาคม 22-31
    }
}
