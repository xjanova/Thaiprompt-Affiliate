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

        // 💞 (2026-06-03) Compatibility — ถ้ามี ≥2 คน คำนวณความเข้ากันให้เลย
        $compat = '';
        if ($multi) {
            $compat = $this->buildCompatibility($dates[0]['ymd'], $dates[1]['ymd']);
        }

        // directive — บอก AI ชัดเจนว่าให้ใช้ดวงดาวนี้ผสานไพ่ + ห้ามขอวันเกิดซ้ำ
        $directive = "👉 วิธีใช้ดวงดาวนี้ (สำคัญ):\n"
            ."• ผสาน ราศี/ดาวเจ้าชนะ/ธาตุ/พื้นนิสัย เข้ากับไพ่ที่เปิดไว้ — อย่าทำนายจากไพ่อย่างเดียว\n"
            ."• 🏛️ ทักษา: ใช้ เดช(อำนาจ)/ศรี(ทรัพย์-รัก)/มนตรี(ผู้อุปถัมภ์) เสริมจุดแข็ง — กาลกิณี = เตือนสี/ดาวที่ควรเลี่ยง\n"
            ."• ⏳ ดาวเสวยอายุ = \"โทนของช่วงชีวิตนี้\" ใช้บอกว่าตอนนี้เป็นช่วงรุ่ง/ทดสอบ/พลิกผัน แล้วโยงกับไพ่ตำแหน่งอนาคต\n"
            ."• 🐉 ปีนักษัตร/ชง = ถ้าปีนี้ชง → เตือนระวังอย่างสร้างสรรค์ (❌ ห้ามขายพิธีแก้ชงแพง — ทำบุญเองได้)\n"
            ."• 🔥 คู่ธาตุดวง = ถ้าราศีกับดาวขัดกัน สะท้อน \"แรงดึงสองด้าน\" ในตัวเขา ใช้ช่วยอ่านนิสัยลึก\n"
            ."• 📅 วันมงคล/กาลกิณี = ฤกษ์ส่วนบุคคล แนะวันทำเรื่องสำคัญ (เซ็น/แต่ง/เปิดร้าน) แบบ \"เลือกได้ก็ดี\" ไม่ใช่ \"ห้ามเด็ดขาด\"\n"
            ."• ✍️ ชื่อมงคล = ถ้าถามเรื่องตั้งชื่อ/เปลี่ยนชื่อ → แนะอักษรเดช/ศรี/มนตรี (ดี) + เลี่ยงอักษรกาลกิณี (❌ ห้ามขายดูชื่อแพง — เป็นแนวทาง)\n"
            ."• 🔢 เลขชะตา (Life Path) = บุคลิกพื้นฐาน-ภารกิจชีวิต ใช้ยืนยัน/เสริมพื้นนิสัยจากดาวเจ้าชนะ\n"
            ."• 📆 ดวงรายปี (Personal Year) = โทนของ \"ปีนี้\" เชื่อมตรงกับคำถามอนาคต (เช่น ปี 1=เริ่มใหม่, 9=ปิดบท)\n";

        if ($multi) {
            $directive .= "• ถ้าถามเรื่อง \"เข้ากันไหม/ความสัมพันธ์\" → ใช้บล็อก 💞 ความเข้ากันด้านล่าง + เทียบดาวมิตร/ศัตรู ให้ชัด\n";
        }

        $directive .= "• เชื่อมจังหวะดาว/วันมงคล กับไพ่ตำแหน่งอนาคต → ระบุช่วงเวลาให้แม่นขึ้น\n"
            ."• พื้นนิสัยจากดาวเจ้าชนะ = \"พื้นฐานดวง\" ของคนนั้น ใช้ยืนยัน/เสริมสิ่งที่ไพ่บอก\n"
            ."• ⚠️ ทั้งหมดนี้ = \"พื้นฐานดวง\" ประกอบไพ่ ไม่ใช่คำฟันธงแยก — ร้อยให้เป็นเรื่องเดียวกับหน้าไพ่\n"
            .'• ⚠️ เจ้าชะตาให้วันเกิดมาแล้ว — ห้ามถามวันเกิดซ้ำ ใช้ข้อมูลด้านบนได้เลย';

        return "\n━━━━━━━━━━━━━━━━━\n"
            ."🌟 ดวงดาวจากวันเกิด (โหรเจ้าชนะ — แม่หมอคำนวณให้แล้ว ใช้ผสานกับไพ่)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            .$people."\n"
            .($compat !== '' ? $compat."\n" : '')
            .$directive."\n\n";
    }

    /**
     * 💞 คำนวณความเข้ากัน (compatibility) ระหว่าง 2 คน
     *   เทียบ: ธาตุดาวเจ้าชนะ + ดาวมิตร/ศัตรู + ธาตุราศี → คะแนน + สรุป
     *
     * @param  string  $ymdA  วันเกิดคนที่ 1 (Y-m-d)
     * @param  string  $ymdB  วันเกิดคนที่ 2 (Y-m-d)
     * @return string ว่าง = คำนวณไม่ได้
     */
    public function buildCompatibility(string $ymdA, string $ymdB): string
    {
        try {
            $a = Carbon::parse($ymdA);
            $b = Carbon::parse($ymdB);
        } catch (\Throwable $e) {
            return '';
        }

        $pa = $this->getPlanetByDayOfWeek($a->dayOfWeek);
        $pb = $this->getPlanetByDayOfWeek($b->dayOfWeek);
        $score = 0;
        $notes = [];

        // 1) ธาตุดาวเจ้าชนะ (ไทย)
        $elA = trim(str_replace('ธาตุ', '', (string) $pa['element']));
        $elB = trim(str_replace('ธาตุ', '', (string) $pb['element']));
        $toEn = (array) config('thai_astrology_knowledge.thai_element_en', []);
        $matrix = (array) config('fortune_elemental_dignities.matrix', []);
        $tone = $matrix[$toEn[$elA] ?? ''][$toEn[$elB] ?? ''] ?? null;
        if ($tone === 'friendly' || $tone === 'same') {
            $score += 2;
            $notes[] = "ธาตุดาวประจำตัว ({$elA}–{$elB}) เข้ากันดี ✨";
        } elseif ($tone === 'contrary') {
            $score -= 2;
            $notes[] = "ธาตุดาวประจำตัว ({$elA}–{$elB}) ขัดกัน ⚡ ต้องปรับเข้าหากัน";
        } else {
            $notes[] = "ธาตุดาวประจำตัว ({$elA}–{$elB}) เป็นกลาง ➖";
        }

        // 2) ดาวมิตร/ศัตรู — ดาว A อยู่ในมิตรของ B ไหม (ตัดคำ "ดาว" ออกเทียบ)
        $planetA = trim(str_replace(['ดาว', '(☉)', '(☽)', '(♂)', '(☿)', '(♃)', '(♀)', '(♄)'], '', (string) $pa['planet']));
        $planetB = trim(str_replace(['ดาว', '(☉)', '(☽)', '(♂)', '(☿)', '(♃)', '(♀)', '(♄)'], '', (string) $pb['planet']));
        $friendHit = (mb_strpos((string) $pb['friends'], $planetA) !== false) || (mb_strpos((string) $pa['friends'], $planetB) !== false);
        $enemyHit = (mb_strpos((string) $pb['enemies'], $planetA) !== false) || (mb_strpos((string) $pa['enemies'], $planetB) !== false);
        if ($friendHit) {
            $score += 2;
            $notes[] = 'ดาวประจำตัวเป็น "ดาวมิตร" กัน 🤝 หนุนเกื้อกัน';
        }
        if ($enemyHit) {
            $score -= 2;
            $notes[] = 'ดาวประจำตัวเป็น "ดาวศัตรู" กัน ⚔️ ระวังกระทบกระทั่ง';
        }

        // 3) ธาตุราศี
        $zElA = $this->getZodiacElement($this->getZodiacSign($a->month, $a->day));
        $zElB = $this->getZodiacElement($this->getZodiacSign($b->month, $b->day));
        if ($zElA !== null && $zElB !== null) {
            $ztone = $matrix[$toEn[$zElA] ?? ''][$toEn[$zElB] ?? ''] ?? null;
            if ($ztone === 'friendly' || $ztone === 'same') {
                $score += 1;
                $notes[] = "ธาตุราศี ({$zElA}–{$zElB}) เข้ากัน ✨";
            } elseif ($ztone === 'contrary') {
                $score -= 1;
                $notes[] = "ธาตุราศี ({$zElA}–{$zElB}) ขัดกัน ⚡";
            }
        }

        // สรุปคะแนน → ระดับความเข้ากัน
        if ($score >= 3) {
            $verdict = '💞💞 เข้ากันสูง — ดวงหนุนกัน เป็นคู่ที่เกื้อหนุน (ที่เหลือคือใจ+ไพ่)';
        } elseif ($score >= 1) {
            $verdict = '💞 เข้ากันได้ — มีจุดหนุนมากกว่าขัด ปรับนิดหน่อยลงตัว';
        } elseif ($score >= -1) {
            $verdict = '⚖️ ก้ำกึ่ง — เข้ากันได้ถ้าเข้าใจกัน ขึ้นกับการปรับตัว';
        } else {
            $verdict = '⚡ ต้องปรับเยอะ — ดวงมีแรงขัด แต่ "ไม่ใช่คู่ไม่ได้" ถ้าทั้งคู่ตั้งใจเข้าหากัน';
        }

        return "💞 ความเข้ากันของดวง (คนที่ 1 × คนที่ 2):\n"
            .'   '.implode("\n   ", $notes)."\n"
            ."   → {$verdict}\n";
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

        $base = "📅 {$date->day} {$thaiMonths[$date->month]} {$thaiYear} (วัน{$dayName}, อายุ {$age} ปี)\n"
            ."♈ ราศี: {$zodiac}\n"
            ."⭐ ดาวเจ้าชนะ: {$p['planet']} | 🔥 ธาตุ: {$p['element']}\n"
            ."🤝 ดาวมิตร: {$p['friends']} | ⚔️ ดาวศัตรู: {$p['enemies']}\n"
            ."🎨 สีมงคล: {$p['lucky_color']} | 🔢 เลขมงคล: {$p['lucky_number']}\n"
            ."💎 พื้นนิสัย (ตามดาวเจ้าชนะ): {$p['personality']}\n";

        // 🔯 (2026-06-03) เติมระบบโหรเจ้าชนะให้ครบ: ทักษา + ดาวเสวยอายุ + นักษัตร/ชง + คู่ธาตุ
        $base .= $this->formatThaksaLine($dow);
        $base .= $this->formatPeriodLine($dow, $age);
        $base .= $this->formatLuckyDayLine($dow);
        $base .= $this->formatZodiacYearLine($date->year);
        $base .= $this->formatNamingLine($dow);
        $base .= $this->formatLifePathLine($date->day, $date->month, $date->year);
        $base .= $this->formatPersonalYearLine($date->day, $date->month);
        $base .= $this->formatElementPairingLine($zodiac, (string) $p['element']);

        return $base;
    }

    /**
     * ✍️ ชื่อมงคล (อักษรทักษา) — อักษรมงคลควรมีในชื่อ + อักษรกาลกิณีห้ามใช้
     *   เดช(2)/ศรี(3)/มนตรี(6) = อักษรดี · กาลกิณี(7) = อักษรห้าม
     */
    protected function formatNamingLine(int $dayOfWeek): string
    {
        $thaksa = $this->getThaksa($dayOfWeek);
        $letters = (array) config('thai_astrology_knowledge.naming_letters', []);
        if (empty($thaksa) || empty($letters)) {
            return '';
        }

        $dechP = $thaksa[2]['planet'] ?? '';
        $sriP = $thaksa[3]['planet'] ?? '';
        $montriP = $thaksa[6]['planet'] ?? '';
        $kalaP = $thaksa[7]['planet'] ?? '';

        $good = [];
        if (isset($letters[$dechP])) {
            $good[] = "เดช: {$letters[$dechP]}";
        }
        if (isset($letters[$sriP])) {
            $good[] = "ศรี: {$letters[$sriP]}";
        }
        if (isset($letters[$montriP])) {
            $good[] = "มนตรี: {$letters[$montriP]}";
        }

        $out = '';
        if (! empty($good)) {
            $out .= "✍️ ชื่อมงคล (อักษรควรมีในชื่อ) — ".implode(' | ', $good)."\n";
        }
        if (isset($letters[$kalaP])) {
            $out .= "🚫 อักษรกาลกิณี (ห้ามใช้ในชื่อ): {$letters[$kalaP]}\n";
        }

        return $out;
    }

    /**
     * 📜 อักษร/ดาวมงคลของวันเกิด (สำหรับวิเคราะห์ชื่อ — public ใช้ที่อื่นได้)
     *
     * @return array{good_planets:array<string>, good_letters:array<string>, kala_planet:string, kala_letters:string}
     */
    public function getNamingGuide(int $dayOfWeek): array
    {
        $thaksa = $this->getThaksa($dayOfWeek);
        $letters = (array) config('thai_astrology_knowledge.naming_letters', []);
        if (empty($thaksa)) {
            return ['good_planets' => [], 'good_letters' => [], 'kala_planet' => '', 'kala_letters' => ''];
        }

        $goodPlanets = array_values(array_unique(array_filter([
            $thaksa[2]['planet'] ?? '', $thaksa[3]['planet'] ?? '', $thaksa[6]['planet'] ?? '',
        ])));
        $kalaPlanet = $thaksa[7]['planet'] ?? '';

        return [
            'good_planets' => $goodPlanets,
            'good_letters' => array_map(fn ($p) => (string) ($letters[$p] ?? ''), $goodPlanets),
            'kala_planet' => $kalaPlanet,
            'kala_letters' => (string) ($letters[$kalaPlanet] ?? ''),
        ];
    }

    /**
     * 📅 วันมงคล/วันกาลกิณี — derive จากดาวเดช(2)/ศรี(3)/มนตรี(6)/กาลกิณี(7) ในทักษา
     */
    protected function formatLuckyDayLine(int $dayOfWeek): string
    {
        $thaksa = $this->getThaksa($dayOfWeek);
        $map = (array) config('thai_astrology_knowledge.planet_to_day', []);
        if (empty($thaksa) || empty($map)) {
            return '';
        }

        $dech = $thaksa[2]['planet'] ?? '';
        $sri = $thaksa[3]['planet'] ?? '';
        $montri = $thaksa[6]['planet'] ?? '';
        $kala = $thaksa[7]['planet'] ?? '';

        $dechDay = (string) ($map[$dech] ?? '');
        $sriDay = (string) ($map[$sri] ?? '');
        $montriDay = (string) ($map[$montri] ?? '');
        $kalaDay = (string) ($map[$kala] ?? '');

        $good = array_filter(array_unique([$dechDay, $sriDay, $montriDay]));
        if (empty($good) && $kalaDay === '') {
            return '';
        }

        $out = '';
        if (! empty($good)) {
            $out .= "📅 วันมงคล (ทำเรื่องสำคัญ): ".implode(' · ', $good)."\n";
        }
        if ($kalaDay !== '') {
            $out .= "🚫 วันกาลกิณี (เลี่ยงเริ่มงานใหญ่): {$kalaDay}\n";
        }

        return $out;
    }

    /**
     * 🔢 เลขชะตา (Life Path Number) — เลขศาสตร์จากวันเดือนปีเกิด (ค.ศ.)
     */
    protected function formatLifePathLine(int $day, int $month, int $year): string
    {
        $num = $this->lifePathNumber($day, $month, $year);
        $info = (array) config('thai_astrology_knowledge.life_path.'.$num, []);
        if (empty($info)) {
            return '';
        }

        return "🔢 เลขชะตา (Life Path): {$num} — {$info['trait']}\n"
            ."   ✨ จุดแข็ง: {$info['strength']}\n"
            ."   ⚠️ ระวัง: {$info['caution']}\n";
    }

    /**
     * 📆 ดวงรายปี (Personal Year) — โทนของปีปัจจุบันสำหรับคนนี้
     */
    protected function formatPersonalYearLine(int $day, int $month): string
    {
        $currentYear = (int) date('Y');
        $py = $this->personalYearNumber($day, $month, $currentYear);
        $text = (string) config('thai_astrology_knowledge.personal_year.'.$py, '');
        if ($text === '') {
            return '';
        }

        return "📆 ดวงรายปี ".($currentYear + 543)." (Personal Year {$py}): {$text}\n";
    }

    /**
     * คำนวณเลขชะตา (Life Path) — รวมทุกตัวเลขใน d/m/y แล้วลดเป็นเลขเดี่ยว
     *   เก็บ Master Number 11, 22 ไว้ไม่ลด
     */
    public function lifePathNumber(int $day, int $month, int $year): int
    {
        $sum = array_sum(str_split((string) $day))
            + array_sum(str_split((string) $month))
            + array_sum(str_split((string) $year));

        return $this->reduceKeepMaster($sum);
    }

    /**
     * คำนวณ Personal Year — วัน+เดือนเกิด + ปีปัจจุบัน → เลขเดี่ยว (ไม่เก็บ master)
     */
    public function personalYearNumber(int $day, int $month, int $year): int
    {
        $sum = array_sum(str_split((string) $day))
            + array_sum(str_split((string) $month))
            + array_sum(str_split((string) $year));

        // Personal Year ลดถึงเลขเดี่ยว 1-9 (ไม่เก็บ master)
        while ($sum > 9) {
            $sum = array_sum(str_split((string) $sum));
        }

        return $sum;
    }

    /**
     * ลดเลขเดี่ยว แต่เก็บ Master Number (11, 22)
     */
    protected function reduceKeepMaster(int $n): int
    {
        while ($n > 9 && $n !== 11 && $n !== 22) {
            $n = array_sum(str_split((string) $n));
        }

        return $n;
    }

    /**
     * 🏛️ ทักษาพยากรณ์ 8 ภพ — เน้น เดช/ศรี/มนตรี (ดาวดี) + กาลกิณี (ดาวร้าย/สีเลี่ยง)
     */
    protected function formatThaksaLine(int $dayOfWeek): string
    {
        $thaksa = $this->getThaksa($dayOfWeek);
        if (empty($thaksa)) {
            return '';
        }

        $dech = $thaksa[2] ?? null;
        $sri = $thaksa[3] ?? null;
        $montri = $thaksa[6] ?? null;
        $kala = $thaksa[7] ?? null;

        $line = "🏛️ ทักษา: บริวาร={$thaksa[0]['planet']} · เดช={$dech['planet']} · ศรี={$sri['planet']} · มนตรี={$montri['planet']}\n";

        if ($kala) {
            $meta = (array) config('thai_astrology_knowledge.planet_meta.'.$kala['planet'], []);
            $avoidColor = (string) ($meta['color'] ?? '');
            $line .= "⚠️ กาลกิณี (ดาวร้าย): {$kala['planet']}"
                .($avoidColor !== '' ? " → สีกลุ่มนี้ควรเลี่ยงใส่เสริมดวง: {$avoidColor}" : '')."\n";
        }

        return $line;
    }

    /**
     * ⏳ ดาวเสวยอายุ (มหาทักษา) — โทนของช่วงชีวิตปัจจุบัน
     */
    protected function formatPeriodLine(int $dayOfWeek, int $age): string
    {
        $period = $this->getPlanetaryPeriod($dayOfWeek, $age);
        if (empty($period)) {
            return '';
        }

        return "⏳ ดาวเสวยอายุ (ช่วง {$period['from']}-{$period['to']} ปี): {$period['planet']}\n"
            ."   → {$period['tone']}\n";
    }

    /**
     * 🐉 ปีนักษัตร + สถานะชงปีปัจจุบัน
     */
    protected function formatZodiacYearLine(int $birthYear): string
    {
        $zy = $this->getZodiacYear($birthYear);
        if (empty($zy)) {
            return '';
        }
        $chong = $this->getChong($birthYear);

        return "🐉 ปีนักษัตร: ปี{$zy['name']} ({$zy['animal']}) · ธาตุ{$zy['element']}\n"
            .'   '.($chong['text'] ?? '')."\n";
    }

    /**
     * 🔥 คู่ธาตุดวง: ราศี vs ดาวเจ้าชนะ (เสริม/ขัด/กลาง) — diagnostic ในตัว
     */
    protected function formatElementPairingLine(string $zodiacName, string $planetElementRaw): string
    {
        $zEl = $this->getZodiacElement($zodiacName);
        $pEl = trim(str_replace('ธาตุ', '', $planetElementRaw));
        if ($zEl === null || $pEl === '') {
            return '';
        }
        $tone = $this->elementPairTone($zEl, $pEl);
        if ($tone === '') {
            return '';
        }

        return "🔥 คู่ธาตุดวง (ราศีธาตุ{$zEl} × ดาวธาตุ{$pEl}): {$tone}\n";
    }

    /**
     * 🏛️ คำนวณทักษา 8 ภพ จากวันเกิด
     *
     * @param  int  $dayOfWeek  0=อาทิตย์..6=เสาร์
     * @return array<int, array{name:string, icon:string, planet:string, meaning:string}>
     */
    public function getThaksa(int $dayOfWeek): array
    {
        $order = (array) config('thai_astrology_knowledge.thaksa_order', []);
        $bhava = (array) config('thai_astrology_knowledge.thaksa_bhava', []);
        $startMap = (array) config('thai_astrology_knowledge.day_to_thaksa_start', []);

        if (empty($order) || empty($bhava) || ! isset($startMap[$dayOfWeek])) {
            return [];
        }

        $start = (int) $startMap[$dayOfWeek];
        $count = count($order);
        $result = [];

        foreach ($bhava as $i => $b) {
            $planet = $order[($start + $i) % $count];
            $result[$i] = [
                'name' => (string) ($b['name'] ?? ''),
                'icon' => (string) ($b['icon'] ?? ''),
                'planet' => (string) $planet,
                'meaning' => (string) ($b['meaning'] ?? ''),
            ];
        }

        return $result;
    }

    /**
     * ⏳ คำนวณดาวเสวยอายุ (มหาทักษา 108 ปี) — เริ่มที่ดาววันเกิด เดินตามลำดับทักษา
     *
     * @return array{planet:string, from:int, to:int, tone:string} ว่าง = คำนวณไม่ได้
     */
    public function getPlanetaryPeriod(int $dayOfWeek, int $age): array
    {
        $order = (array) config('thai_astrology_knowledge.thaksa_order', []);
        $startMap = (array) config('thai_astrology_knowledge.day_to_thaksa_start', []);
        $meta = (array) config('thai_astrology_knowledge.planet_meta', []);
        $tones = (array) config('thai_astrology_knowledge.period_tone', []);

        if (empty($order) || ! isset($startMap[$dayOfWeek]) || $age < 0) {
            return [];
        }

        $start = (int) $startMap[$dayOfWeek];
        $count = count($order);
        $ageInCycle = $age % 108; // มหาทักษารวม 108 ปี — วนรอบ

        $cursor = 0;
        for ($i = 0; $i < $count; $i++) {
            $planet = $order[($start + $i) % $count];
            $years = (int) ($meta[$planet]['period_years'] ?? 0);
            if ($years <= 0) {
                continue;
            }
            if ($ageInCycle < $cursor + $years) {
                return [
                    'planet' => (string) $planet,
                    'from' => $cursor,
                    'to' => $cursor + $years,
                    'tone' => (string) ($tones[$planet] ?? ''),
                ];
            }
            $cursor += $years;
        }

        return [];
    }

    /**
     * 🐉 ปีนักษัตรจาก ค.ศ.
     *
     * @return array{name:string, animal:string, element:string, index:int} ว่าง = คำนวณไม่ได้
     */
    public function getZodiacYear(int $year): array
    {
        $years = (array) config('thai_astrology_knowledge.zodiac_years', []);
        if (count($years) !== 12) {
            return [];
        }
        $idx = (($year - 4) % 12 + 12) % 12;
        $zy = $years[$idx] ?? null;
        if (! is_array($zy)) {
            return [];
        }

        return [
            'name' => (string) ($zy['name'] ?? ''),
            'animal' => (string) ($zy['animal'] ?? ''),
            'element' => (string) ($zy['element'] ?? ''),
            'index' => $idx,
        ];
    }

    /**
     * 🚫 สถานะชง เทียบปีเกิดกับปีปัจจุบัน
     *
     * @return array{status:string, text:string}
     */
    public function getChong(int $birthYear, ?int $currentYear = null): array
    {
        $currentYear ??= (int) date('Y');
        $birth = $this->getZodiacYear($birthYear);
        $now = $this->getZodiacYear($currentYear);
        $cfg = (array) config('thai_astrology_knowledge.chong', []);

        if (empty($birth) || empty($now)) {
            return ['status' => 'none', 'text' => ''];
        }

        $diff = (($now['index'] - $birth['index']) % 12 + 12) % 12;

        if ($diff === 0) {
            return ['status' => 'self', 'text' => (string) ($cfg['self_text'] ?? '')];
        }
        if ($diff === 6) {
            return ['status' => 'direct', 'text' => (string) ($cfg['direct_text'] ?? '')];
        }

        return ['status' => 'none', 'text' => (string) ($cfg['none_text'] ?? '')];
    }

    /**
     * ♈ ธาตุของราศี (จากชื่อราศีที่ getZodiacSign คืนมา — match prefix ไทย)
     */
    public function getZodiacElement(string $zodiacName): ?string
    {
        $map = (array) config('thai_astrology_knowledge.zodiac_element', []);
        foreach ($map as $prefix => $element) {
            if (mb_strpos($zodiacName, (string) $prefix) === 0) {
                return (string) $element;
            }
        }

        return null;
    }

    /**
     * 🔥 เทียบ 2 ธาตุ (ไทย) → tone (reuse matrix ของ fortune_elemental_dignities)
     */
    public function elementPairTone(string $thaiElA, string $thaiElB): string
    {
        $toEn = (array) config('thai_astrology_knowledge.thai_element_en', []);
        $matrix = (array) config('fortune_elemental_dignities.matrix', []);
        $tones = (array) config('thai_astrology_knowledge.pairing_tone', []);

        $a = $toEn[$thaiElA] ?? null;
        $b = $toEn[$thaiElB] ?? null;
        if ($a === null || $b === null) {
            return '';
        }
        $tone = $matrix[$a][$b] ?? null;
        if ($tone === null) {
            return '';
        }

        return (string) ($tones[$tone] ?? '');
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
