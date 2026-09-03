<?php

namespace App\Services\Fortune;

use App\Services\FortuneChartService;
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
     * 🕛 เวลาเกิดมาตรฐานเมื่อลูกค้าไม่ได้บอก (owner directive 2026-09-02)
     *
     * "เราไม่ได้ถามเวลาเกิดอยู่แล้ว ให้ใช้เวลามาตรฐานในการคำนวณหากจำเป็น
     *  แต่ถ้าลูกค้าบอกเวลาเกิด ก็จะยิ่งแม่นยำ ก็นำมาคำนวณด้วย ถ้าไม่ได้บอกจะยึดเวลา 12.00 น."
     *
     * เที่ยงวัน = จุดกึ่งกลางของวัน → คลาดเคลื่อนสูงสุด ±12 ชม. (น้อยที่สุดเท่าที่เป็นไปได้)
     * และเป็นมาตรฐานที่โหราศาสตร์สากลใช้กับดวงที่ไม่ทราบเวลาเกิด (noon chart)
     */
    public const DEFAULT_BIRTH_HOUR = 12.0;

    /** วันพุธ (Carbon::dayOfWeek) — วันเดียวที่ตำราไทยแบ่งเป็น 2 ดาว (พุธ / ราหู) */
    public const WEDNESDAY = 3;

    /** 🕛 ชั่วโมง (float) → "HH:MM" สำหรับโชว์ในผัง */
    public static function hourLabel(?float $hour): string
    {
        if ($hour === null) {
            return '-';
        }
        $h = (int) floor($hour);
        $m = (int) round(($hour - $h) * 60);
        if ($m >= 60) {
            $h++;
            $m = 0;
        }

        return sprintf('%02d:%02d', $h % 24, $m);
    }

    /** 🌙 ย่ำค่ำ — เข้าเขต "กลางคืน" ทางโหร */
    public const NIGHT_STARTS_AT = 18.0;

    /** 🌅 ย่ำรุ่ง — ออกจากเขต "กลางคืน" */
    public const NIGHT_ENDS_AT = 6.0;

    /** เวลาเกิดที่ parse ได้ล่าสุด (ชม. เป็น float รวมนาที, 0-23.99) — null = ไม่ได้ระบุ */
    protected ?float $lastBirthHour = null;

    /** ราศีลัคนาที่คำนวณได้ล่าสุด — ใช้ผูกภพให้ดาวจร (null = คำนวณไม่ได้) */
    protected ?string $lastLagna = null;

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

        // parse เวลาเกิด (ถ้ามี) — เก็บไว้ใช้คำนวณลัคนา
        // ⚠️ ใช้ตัวเข้มงวด — ข้อความแชทเต็มไปด้วยตัวเลข (หนี้ 2.50 แสน / เงินเดือน 12.50)
        //    ตัวหลวมจะอ่านเป็นเวลาเกิดแล้วทำให้ลัคนา+ภพเพี้ยนทั้งผัง
        $this->lastBirthHour = $this->extractStatedBirthHour($text);

        $multi = count($dates) > 1;
        $people = '';

        foreach ($dates as $i => $d) {
            // ป้ายกำกับคน — ถ้ามีหลายคนให้ระบุลำดับ เพื่อให้ AI map กับบริบท (เจ้าชะตา vs คู่/อีกฝ่าย)
            $label = $multi
                ? '👤 คนที่ '.($i + 1)." (เกิด {$d['raw']})"
                : "👤 เจ้าของวันเกิด (เกิด {$d['raw']})";

            $people .= $label."\n".$this->formatPersonBlock($d['ymd'], null, $i === 0)."\n";
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
            ."• 🌀 ดวงพื้น (ผูกดวง) = ดาวทั้ง 9 (☉☽♂☿♃♀♄☊☋) คำนวณจาก ephemeris ของเราเอง — ใช้ \"ตำแหน่งดาว+ภพ+เกษตร/อุจ/นิจ\" ในการอ่าน\n"
            ."   • ดาวในภพไหน = เรื่องนั้นเด่น (เช่น อังคารในปัตนิ = คู่เด็ดขาด/ขัดแย้ง · ศุกร์ในกัมมะ = งานสาย art-รัก)\n"
            ."   • ดาวอุจ = พลังแรงดี · ดาวเกษตร = บ้านตัวเอง-มั่นคง · ดาวนิจ = อ่อน-ต้องระวัง · ดาวพักร = พลังสะท้อนใน/ทบทวน\n"
            ."   📐 ลัคนา+ภพ มีให้เสมอ (ไม่บอกเวลาเกิด = คำนวณจากเวลามาตรฐาน 12:00 น.) → *อ่านได้เลย ห้ามออกตัวว่าดูไม่ได้*\n"
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
        $zElA = $this->getZodiacElement($this->getZodiacSignForDate($a));
        $zElB = $this->getZodiacElement($this->getZodiacSignForDate($b));
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
    /**
     * @param  float|null  $birthHour  เวลาเกิดเป็นชั่วโมง (13.5 = 13:30) — null = ใช้ค่าที่ parse ไว้
     *                                 หรือเวลามาตรฐาน 12:00 น. (self::DEFAULT_BIRTH_HOUR)
     */
    public function formatPersonBlock(string $ymd, ?float $birthHour = null, bool $withTransit = true): string
    {
        // ผู้เรียกระบุเวลามาเอง (เช่น Deep 39 ที่ดึงจากข้อความลูกค้า) → ใช้ตัวนั้น
        if ($birthHour !== null) {
            $this->lastBirthHour = $birthHour;
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}[ T](\d{1,2}):(\d{2})/', trim($ymd), $m)) {
            // 🕛 (2026-09-02) สตริง "Y-m-d H:i" = ผู้เรียกรู้เวลาเกิด (FortuneReading::birthDateTimeForChart)
            //    รับทางนี้เพื่อไม่ต้องเพิ่มพารามิเตอร์ใหม่ตลอดสาย generateWithRetryAndFallback (9 ตัว)
            $h = (int) $m[1];
            $min = (int) $m[2];
            if ($h <= 23 && $min <= 59) {
                $this->lastBirthHour = $h + $min / 60.0;
            }
        } else {
            // วันที่ล้วน = ไม่ทราบเวลา → ล้างค่าค้างจาก call ก่อน (instance เดิมถูกใช้ซ้ำหลายคน)
            $this->lastBirthHour = null;
        }

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
        $calendarDow = $date->dayOfWeek;  // 0=อาทิตย์ ... 6=เสาร์ (ตามปฏิทิน)

        // 🌅 (2026-09-03) วันทางโหรเปลี่ยนตอน "ย่ำรุ่ง 06:00" ไม่ใช่เที่ยงคืน
        //    เกิดพุธ ตี 2 ⇒ ทางโหรยังเป็นอังคารกลางคืน ⇒ ดาวเจ้าเรือน = อังคาร
        //    ทุกอย่างในระบบเจ้าชนะ (ดาวเจ้าเรือน/ทักษา/มหาทักษา/อักษร/วันมงคล)
        //    ต้องเดินจากวัน**ทางโหร** ตัวนี้ ห้ามใช้วันปฏิทินอีก
        $dow = $this->thaiWeekday($calendarDow, $this->lastBirthHour);
        $dayShifted = $dow !== $calendarDow;

        $dayName = $dayNames[$dow];
        $zodiac = $this->getZodiacSignForDate($date);
        $p = $this->getPlanetByDayOfWeek($dow);

        // 🌙 พุธกลางคืน = ราหู — ต้องทับ "ดาวเจ้าชนะ" ทั้งชุด ไม่ใช่แค่ทักษา
        //    ไม่งั้นผังจะขัดกันเอง (หัวบอก "ดาวพุธ" แต่ทักษา/ดาวเสวยอายุเดินจากราหู)
        $nightRahu = $this->isWednesdayNight($dow, $this->lastBirthHour);
        if ($nightRahu) {
            $p = $this->rahuRulerProfile($p);
        }
        if ($this->isNightBirth($this->lastBirthHour)) {
            $dayName .= ' กลางคืน';
        }

        $base = "📅 {$date->day} {$thaiMonths[$date->month]} {$thaiYear} (วัน{$dayName}, อายุ {$age} ปี)\n";

        // 🔎 เปลี่ยนวันเพราะย่ำรุ่ง = **ต้องบอกลูกค้า ห้ามเปลี่ยนเงียบ**
        //    ลูกค้าเห็นปฏิทินว่า "วันพุธ" แต่ผังบอก "อังคาร" จะงงและคิดว่าบอทพัง
        if ($dayShifted) {
            $calName = $dayNames[$calendarDow];
            $hh = self::hourLabel($this->lastBirthHour);
            $base .= "   ⚠️ ปฏิทินคือวัน{$calName} แต่เกิด {$hh} น. ซึ่ง**ก่อนย่ำรุ่ง 06:00** — "
                ."ทางโหรจึงนับเป็น \"วัน{$dayName}\"\n"
                ."   → เปิดคำทำนายด้วยการอธิบายข้อนี้สั้น ๆ 1 ประโยค (เจ้าชะตาจำว่าตัวเองเกิดวัน{$calName})\n";
        }

        $base .= "♈ ราศี: {$zodiac}\n"
            ."⭐ ดาวเจ้าชนะ: {$p['planet']} | 🔥 ธาตุ: {$p['element']}\n"
            ."🤝 ดาวมิตร: {$p['friends']} | ⚔️ ดาวศัตรู: {$p['enemies']}\n"
            ."🎨 สีมงคล: {$p['lucky_color']} | 🔢 เลขมงคล: {$p['lucky_number']}\n"
            ."💎 พื้นนิสัย (ตามดาวเจ้าชนะ): {$p['personality']}\n";

        // 🔯 (2026-06-03) เติมระบบโหรเจ้าชนะให้ครบ: ทักษา + ดาวเสวยอายุ + นักษัตร/ชง + คู่ธาตุ
        $base .= $this->formatThaksaLine($dow);
        $base .= $this->formatPeriodLine($dow, $age);
        $base .= $this->formatLuckyDayLine($dow);
        $base .= $this->formatZodiacYearLine($date->year);
        $base .= $this->formatBirthChartBlock($zodiac, $date, $dow);
        $base .= $this->formatNamingLine($dow);
        $base .= $this->formatLifePathLine($date->day, $date->month, $date->year);
        $base .= $this->formatPersonalYearLine($date->day, $date->month);
        $base .= $this->formatElementPairingLine($zodiac, (string) $p['element']);

        // 🔭 ดาวจรวันนี้ — ต้องแนบไปด้วย ไม่งั้น AI แต่ง transit เอง (เคส FTU-260902-E4391)
        //    หลายคนในบล็อกเดียว (ดูความเข้ากัน) แนบครั้งเดียวพอ → ผู้เรียกส่ง false มา
        if ($withTransit) {
            $base .= $this->formatTransitBlock($this->lastLagna);
        }

        return $base;
    }

    /**
     * ✍️ ชื่อมงคล (อักษรทักษา) — อักษรมงคลควรมีในชื่อ + อักษรกาลกิณีห้ามใช้
     *   เดช(2)/ศรี(3)/มนตรี(6) = อักษรดี · กาลกิณี(7) = อักษรห้าม
     */
    protected function formatNamingLine(int $dayOfWeek): string
    {
        $thaksa = $this->getThaksa($dayOfWeek, $this->lastBirthHour);
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
            $out .= '✍️ ชื่อมงคล (อักษรควรมีในชื่อ) — '.implode(' | ', $good)."\n";
        }
        if (isset($letters[$kalaP])) {
            $out .= "🚫 อักษรกาลกิณี (ห้ามใช้ในชื่อ): {$letters[$kalaP]}\n";
        }

        return $out;
    }

    /**
     * 🌀 ผูกดวงเต็มสูตร — คำนวณตำแหน่งดาวทั้ง 9 (☉☽♂☿♃♀♄☊☋) ด้วย PlanetEphemeris
     *
     * - ดาวอาทิตย์ แม่นเป๊ะ (±0.5° จาก JPL)
     * - ดาวจันทร์ แม่นระดับราศี (~±5° ใช้พอ)
     * - ดาวอื่น 5 ดวง คำนวณจาก Keplerian elements (พุธ-ศุกร์-อังคาร-พฤหัส-เสาร์)
     * - ราหู/เกตุ จากจุดโหนดจันทร์ (เป๊ะ ±0.1°)
     * - ตรวจพักร (retrograde) ทุกดาว
     * - ลัคนา (ถ้ามีเวลาเกิด) + 12 ภพ + เกษตร/อุจ/นิจ ทุกดาว
     *
     * @param  string  $zodiacName  ชื่อราศีไทย (จาก getZodiacSign)
     */
    protected function formatBirthChartBlock(string $zodiacName, \Carbon\Carbon $date, int $dow): string
    {
        $sunSign = $this->extractSignFromName($zodiacName);
        if ($sunSign === null) {
            return '';
        }

        // 🕛 (2026-09-02 owner directive) ลัคนาต้องมี "เสมอ" — ไม่มีเวลาเกิดให้ยึด 12:00 น.
        //
        //   ⚠️ ทำไม: เราไม่ได้ถามเวลาเกิดอยู่แล้ว ⇒ เดิม $lagna = null ⇒ ไม่มีภพสักดวง
        //     แต่ persona ยังสั่ง AI ให้อ่าน "ภพ" ⇒ AI แต่งภพขึ้นเองทุกดวง
        //     (เคสจริง FTU-260902-E4391 มโนภพ 8 จุด: ศุกร์ภพตนุ/พุธภพศุภะ/จันทร์ภพลาภะ ฯลฯ)
        //   ยึดเที่ยงวันเป็นมาตรฐานโหราศาสตร์ = ได้ภพจริงคำนวณได้ ตรวจสอบได้ และคลาดน้อยสุด
        //   (เที่ยงวันคือจุดกึ่งกลางของวัน — ผิดพลาดสูงสุด ±12 ชม. แทนที่จะเป็น ±24)
        //   ลูกค้าบอกเวลาเกิดเมื่อไหร่ → ใช้ของจริงทันที แม่นขึ้นทั้งลัคนาและดาวจันทร์
        $hourKnown = $this->lastBirthHour !== null;
        $birthHour = $hourKnown ? $this->lastBirthHour : self::DEFAULT_BIRTH_HOUR;

        $hh = (int) floor($birthHour);
        $mm = (int) round(($birthHour - $hh) * 60);
        if ($mm >= 60) {
            $mm = 0;
            $hh++;
        }
        $dtForCalc = $date->copy()->setTime($hh, $mm, 0);
        $lagna = $this->siderealLagna($dtForCalc);
        $this->lastLagna = $lagna;
        $timeStr = sprintf('%02d:%02d', $hh, $mm);

        // ⭐ คำนวณตำแหน่งดาวทั้ง 9 ด้วย ephemeris ของเราเอง
        $eph = new PlanetEphemeris;
        $positions = $eph->positions($dtForCalc);

        $out = "🌀 ดวงพื้น (ผูกดวงเต็มสูตร — คำนวณดาว 9 ดวง):\n";

        if ($lagna === null) {
            // siderealLagna คืน null ได้เมื่อ config ราศีหาย — ไม่ควรเกิด แต่กันไว้
            $out .= "   ⬆️ ลัคนา: (คำนวณไม่ได้ — ห้ามอ้างอิงภพในคำทำนาย)\n";
        } elseif ($hourKnown) {
            $out .= "   ⬆️ ลัคนา: ราศี{$lagna} (จากเวลาเกิด {$timeStr} น. ที่เจ้าชะตาบอก · LST + ละติจูดกรุงเทพ)\n";
        } else {
            $out .= "   ⬆️ ลัคนา: ราศี{$lagna} (⏱️ คำนวณจากเวลามาตรฐาน 12:00 น. — เจ้าชะตายังไม่ได้บอกเวลาเกิด)\n";
        }

        // ตำแหน่งดาวทั้ง 9 + ภพ (ถ้ามีลัคนา) + ดิ๊กนิตี้
        $out .= "   📍 ตำแหน่งดาว:\n";
        foreach ($positions as $key => $p) {
            $deg = floor($p['lon']) % 30;
            $line = "      {$p['sym']} {$p['th']} ราศี{$p['sign']} ".sprintf('%2d°', $deg);
            if ($lagna !== null) {
                $h = $this->houseNumber($lagna, $p['sign']);
                $line .= " · ภพ{$h}";
            }
            $dig = $this->dignityOf($p['th'], $p['sign']);
            if ($dig !== '' && strpos($dig, 'เป็นกลาง') === false) {
                $line .= ' · '.$dig;
            }
            if (! empty($p['retro'])) {
                $line .= ' · ⏪ พักร';
            }
            $out .= $line."\n";
        }

        // 12 ภพ สำหรับให้ AI อ้างอิง
        $out .= "   🏛️ 12 ภพ: ตนุ(ตัวเอง)/กฎุมพะ(ทรัพย์)/สหัชชะ(พี่น้อง)/พันธุ(บ้าน)/ปุตตะ(บุตร-รัก)/อริ(ศัตรู-โรค)/ปัตนิ(คู่)/มรณะ(วิกฤต)/ศุภะ(โชค-บุญ)/กัมมะ(งาน)/ลาภะ(โชคลาภ)/วินาสนะ(สูญเสีย)\n";

        // 🔒 กฎการใช้ผัง — ต้องมีทุกครั้ง ไม่งั้น AI เติมภพ/ดาวที่ไม่มีเอง (เคส FTU-260902-E4391)
        $out .= "   🔒 *ใช้ได้เฉพาะดาวและภพที่ระบุข้างบนเท่านั้น* — ❌ ห้ามอ้างว่าดาวดวงใดอยู่ภพอื่นนอกรายการนี้\n";

        if (! $hourKnown && $lagna !== null) {
            $out .= "   ⏱️ ลัคนา+ภพ ข้างบนคำนวณจากเวลามาตรฐาน 12:00 น. — *อ่านได้ตามปกติ ไม่ต้องออกตัว*\n"
                ."      แต่ถ้าเจ้าชะตาบอกเวลาเกิดจริงจะแม่นขึ้น (ลัคนาเลื่อนได้ ~1 ราศีต่อ 2 ชม.)\n"
                ."      → ชวนถามเวลาเกิดได้ *ครั้งเดียวแบบเนียนๆ* ตอนท้าย ❌ ห้ามทวงซ้ำ ❌ ห้ามเอามาเป็นข้ออ้างไม่ฟันธง\n";
        }

        return $out;
    }

    /**
     * 🔭 ดาวจร (transit) ณ วันนี้ — ตำแหน่งดาว 9 ดวงจริง + ภพที่ตกกับดวงของเจ้าชะตา
     *
     * ⚠️ ทำไมต้องมี (2026-09-02 — เคส FTU-260902-E4391):
     *   persona สั่ง AI ว่า "ดูตำแหน่งดาวเคราะห์ปัจจุบัน (transit) → ดูภพที่ได้รับผล"
     *   แต่ระบบ **ไม่เคยป้อน transit ให้เลยสักตัว** ⇒ AI แต่งเอง 8 จุด และมีจุดที่ผิด
     *   ดาราศาสตร์ชัดๆ ("2-30 กันยายน ดาวจันทร์โคจรภพตนุ" — จันทร์อยู่ราศีละ ~2.5 วัน)
     *   ⇒ ป้อนของจริงจาก [[PlanetEphemeris]] ไปเลย ดีกว่าห้ามพูดถึง เพราะดาวจรคือหัวใจ
     *     ของการระบุ "ช่วงเวลา" ที่ลูกค้าจ่ายเงินมาเพื่อฟัง
     *
     * @param  string|null  $lagna  ราศีลัคนาของเจ้าชะตา (ได้จาก siderealLagna) — มี = คำนวณภพให้ด้วย
     * @param  Carbon|null  $now  วันที่ต้องการ (null = วันนี้)
     * @return string ว่าง = คำนวณไม่ได้
     */
    public function formatTransitBlock(?string $lagna = null, ?Carbon $now = null): string
    {
        try {
            $now ??= Carbon::now('Asia/Bangkok');
            $positions = (new PlanetEphemeris)->positions($now->copy()->setTime(12, 0, 0));
        } catch (\Throwable $e) {
            return '';
        }

        if (empty($positions)) {
            return '';
        }

        $thaiMonths = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        $today = "{$now->day} {$thaiMonths[$now->month]} ".($now->year + 543);

        $out = "🔭 ดาวจรวันนี้ ({$today}) — ตำแหน่งจริง ใช้ผูก \"ช่วงเวลา\" ในคำทำนาย:\n";
        foreach ($positions as $p) {
            $line = "   {$p['sym']} {$p['th']} จรราศี{$p['sign']}";
            if ($lagna !== null) {
                $h = $this->houseNumber($lagna, $p['sign']);
                if ($h > 0) {
                    $line .= " · ทับภพ{$h}";
                }
            }
            if (! empty($p['retro'])) {
                $line .= ' · ⏪ พักร';
            }
            $out .= $line."\n";
        }

        $out .= "   🔒 *ระบุช่วงเวลาได้เฉพาะจากดาวจรชุดนี้* — ❌ ห้ามแต่งว่าดาวดวงใดจะย้ายเข้าภพไหนวันไหน\n"
            ."   ⏳ ความเร็วดาว (ใช้ประเมินว่าเรื่องจะกินเวลาแค่ไหน — ห้ามบอกวันที่เป๊ะเกินจริง):\n"
            ."      จันทร์ ~2 วันครึ่ง/ราศี · อาทิตย์-พุธ-ศุกร์ ~1 เดือน/ราศี · อังคาร ~6 สัปดาห์\n"
            ."      พฤหัสบดี ~1 ปี · เสาร์ ~2 ปีครึ่ง · ราหู-เกตุ ~1 ปีครึ่ง (เดินถอยหลัง)\n";

        return $out;
    }

    /**
     * คืนเลขภพ 1-12 (ใช้ภายในคู่กับ houseFromLagna ที่คืนข้อความ)
     */
    protected function houseNumber(string $lagna, string $sign): int
    {
        $order = (array) config('thai_astrology_knowledge.zodiac_order', []);
        $i = array_search($lagna, $order, true);
        $j = array_search($sign, $order, true);
        if ($i === false || $j === false) {
            return 0;
        }

        return (($j - $i + 12) % 12) + 1;
    }

    /**
     * 🌀 คำนวณลัคนา จาก Local Sidereal Time (แม่นจริง — ราศีขึ้นเร็วต่างกัน)
     *
     *   1. คำนวณ LST จาก JD + ลองจิจูดสถานที่ (กรุงเทพ 100.5°E)
     *   2. หา RA ของลัคนา = LST (จุดที่ horizon ตัด equator)
     *   3. แปลง RA → ecliptic longitude ด้วยสูตร Asc (Meeus ch.13)
     *      tan(λ) = -cos(LST) / (sin(LST)·cos(ε) + tan(φ)·sin(ε))
     *      โดย ε = ความเอียง 23.44°, φ = ละติจูดสถานที่
     *
     * @param  \Carbon\Carbon  $dt  วันเวลาเกิด (Thai time UTC+7)
     * @param  float  $lat  ละติจูด (default กรุงเทพ 13.75)
     * @param  float  $lon  ลองจิจูด (default กรุงเทพ 100.5)
     */
    public function siderealLagna(\Carbon\Carbon $dt, float $lat = 13.75, float $lon = 100.5): ?string
    {
        $order = (array) config('thai_astrology_knowledge.zodiac_order', []);
        if (count($order) !== 12) {
            return null;
        }

        $eph = new PlanetEphemeris;
        $jdUT = $eph->julianDay($dt) - 7.0 / 24.0; // Thai → UT

        // Greenwich Mean Sidereal Time (Meeus ch.12 eq. 12.4 — แม่นแม้วันที่ห่าง J2000)
        // แยก integer day + fractional hour เพื่อกัน float precision loss
        $jd0 = floor($jdUT - 0.5) + 0.5; // 0h UT
        $hUT = ($jdUT - $jd0) * 24.0;
        $d0 = $jd0 - 2451545.0;
        $t = ($jdUT - 2451545.0) / 36525.0;
        $gmstH = 6.697374558 + 0.06570982441908 * $d0
            + 1.00273790935 * $hUT + 0.000026 * $t * $t;
        $gmstDeg = fmod($gmstH * 15.0, 360.0);
        if ($gmstDeg < 0) {
            $gmstDeg += 360.0;
        }
        $lst = fmod($gmstDeg + $lon, 360.0);
        if ($lst < 0) {
            $lst += 360.0;
        }

        // Ecliptic obliquity (ความเอียงสุริยวิถี)
        $epsilon = deg2rad(23.4392911 - 0.0130041667 * $t);
        $lstRad = deg2rad($lst);
        $phiRad = deg2rad($lat);

        // ลัคนา — Meeus 13.6 (Ascendant คือจุดขึ้นตะวันออก ไม่ใช่ Descendant)
        //   λ_asc = atan2( cos(LST), -[sin(LST)·cos(ε) + tan(φ)·sin(ε)] )
        // วิธีนี้ได้ quadrant ถูก — ลัคนาอยู่ "ทางตะวันออก" (LST+90° ประมาณ)
        $asc = rad2deg(atan2(
            cos($lstRad),
            -(sin($lstRad) * cos($epsilon) + tan($phiRad) * sin($epsilon))
        ));
        if ($asc < 0) {
            $asc += 360.0;
        }

        // 🇹🇭 (2026-09-03) สูตร Meeus คืนลัคนาแบบ **สายนะ (tropical)** — ต้องลบอายนางศ
        //    ให้เป็นนิรายนะก่อน ไม่งั้นลัคนาเพี้ยนเกือบเต็มราศีเทียบปฏิทินโหรไทย
        //    (ชื่อเมธอด "sidereal" เดิมหมายถึง Local Sidereal Time = เวลาดาราคติ
        //     คนละเรื่องกับ "ราศีนิรายนะ" — เป็นที่มาของความเข้าใจผิดตอนเขียนครั้งแรก)
        $signIdx = $eph->toSidereal($asc, $jdUT)['sign_index'];

        return (string) $order[$signIdx];
    }

    /**
     * @deprecated ใช้ siderealLagna แทน — สูตรเก่า 2 ชม./ราศี ไม่แม่น
     */
    public function approximateLagna(string $sunSign, int $hour): ?string
    {
        $order = (array) config('thai_astrology_knowledge.zodiac_order', []);
        if (count($order) !== 12) {
            return null;
        }
        $sunIdx = array_search($sunSign, $order, true);
        if ($sunIdx === false) {
            return null;
        }
        $shift = (int) floor((($hour - 6 + 24) % 24) / 2);
        $lagnaIdx = ($sunIdx + $shift) % 12;

        return (string) $order[$lagnaIdx];
    }

    /**
     * นับว่าราศีหนึ่งตกภพที่เท่าไหร่ของลัคนา (1-12)
     */
    public function houseFromLagna(string $lagna, string $sign): string
    {
        $order = (array) config('thai_astrology_knowledge.zodiac_order', []);
        $houses = (array) config('thai_astrology_knowledge.twelve_houses', []);
        $i = array_search($lagna, $order, true);
        $j = array_search($sign, $order, true);
        if ($i === false || $j === false) {
            return '';
        }
        $h = (($j - $i + 12) % 12) + 1;
        $name = (string) ($houses[$h]['name'] ?? '');
        $icon = (string) ($houses[$h]['icon'] ?? '');

        return "ภพที่ {$h} ({$icon} {$name})";
    }

    /**
     * 🌟 ระดับกำลังของดาวในราศี (เกษตร/อุจ/นิจ/กลาง)
     */
    public function dignityOf(string $planet, string $sign): string
    {
        $dig = (array) config("thai_astrology_knowledge.planet_dignity.{$planet}", []);
        $label = (array) config('thai_astrology_knowledge.dignity_label', []);
        if (empty($dig)) {
            return '';
        }
        if (in_array($sign, (array) ($dig['rules'] ?? []), true)) {
            return (string) ($label['rules'] ?? '');
        }
        if ($sign === ($dig['exalted'] ?? '')) {
            return (string) ($label['exalted'] ?? '');
        }
        if ($sign === ($dig['debilitated'] ?? '')) {
            return (string) ($label['debilitated'] ?? '');
        }

        return (string) ($label['neutral'] ?? '');
    }

    /**
     * ตัดชื่อราศีไทยล้วนจาก "เมษ (Aries)" → "เมษ"
     */
    protected function extractSignFromName(string $zodiacName): ?string
    {
        $order = (array) config('thai_astrology_knowledge.zodiac_order', []);
        foreach ($order as $name) {
            if (mb_strpos($zodiacName, (string) $name) === 0) {
                return (string) $name;
            }
        }

        return null;
    }

    /**
     * 📜 อักษร/ดาวมงคลของวันเกิด (สำหรับวิเคราะห์ชื่อ — public ใช้ที่อื่นได้)
     *
     * @return array{good_planets:array<string>, good_letters:array<string>, kala_planet:string, kala_letters:string}
     */
    public function getNamingGuide(int $dayOfWeek): array
    {
        $thaksa = $this->getThaksa($dayOfWeek, $this->lastBirthHour);
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
        $thaksa = $this->getThaksa($dayOfWeek, $this->lastBirthHour);
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
            $out .= '📅 วันมงคล (ทำเรื่องสำคัญ): '.implode(' · ', $good)."\n";
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

        return '📆 ดวงรายปี '.($currentYear + 543)." (Personal Year {$py}): {$text}\n";
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
        $thaksa = $this->getThaksa($dayOfWeek, $this->lastBirthHour);
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
        $period = $this->getPlanetaryPeriod($dayOfWeek, $age, $this->lastBirthHour);
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
    /**
     * 🌙 (2026-09-03) จุดเริ่มทักษา — รองรับ **พุธกลางคืน = ราหู** ตามตำราไทย
     *
     * ตำราไทยมีดาว 8 ดวง (รวมราหู) และ "วันพุธ" แบ่งเป็น 2 ดาว:
     *   พุธกลางวัน (ย่ำรุ่ง–ย่ำค่ำ) = พุธ · พุธกลางคืน (ย่ำค่ำ–ย่ำรุ่ง) = **ราหู**
     * โหรจริงตรวจข้อนี้เป็นข้อแรก ๆ ของคนเกิดวันพุธ
     *
     * ⚠️ ระบบมีข้อมูลราหูครบอยู่แล้วทุกช่อง (thaksa_order · planet_meta · period_tone ·
     *    naming_letters · planet_to_day 'วันพุธ (กลางคืน)') และ config เขียนกำกับไว้ด้วยว่า
     *    "พุธกลางคืน=ราหู ต้องมีเวลาเกิด" — **แต่ไม่เคยถูกต่อสายกับเวลาเกิดจริง**
     *    เพราะเวลาเกิดไม่เคยถูกเก็บเลย (prod: 1,253 บิลจ่ายเงิน มี birth_time 0 ใบ)
     *
     * 🕛 ไม่รู้เวลาเกิด → คงพฤติกรรมเดิม (พุธกลางวัน) · **ห้ามเดา**
     *    การเดาผิดข้างเปลี่ยนดาวเจ้าเรือนทั้งดวง แย่กว่าใช้ค่ากลาง
     *
     * @param  float|null  $birthHour  ชั่วโมงเกิด (18.5 = 18:30) · null = ไม่ทราบ
     * @return int|null null = วันเกิดไม่ถูกต้อง
     */
    protected function thaksaStartIndex(int $dayOfWeek, ?float $birthHour = null): ?int
    {
        $startMap = (array) config('thai_astrology_knowledge.day_to_thaksa_start', []);
        if (! isset($startMap[$dayOfWeek])) {
            return null;
        }

        if ($dayOfWeek === self::WEDNESDAY && $this->isNightBirth($birthHour)) {
            $order = (array) config('thai_astrology_knowledge.thaksa_order', []);
            $rahu = array_search('ราหู', $order, true);
            if ($rahu !== false) {
                return (int) $rahu;
            }
        }

        return (int) $startMap[$dayOfWeek];
    }

    /**
     * 🌙 ชุดข้อมูล "ดาวเจ้าเรือน" ของคนเกิดพุธกลางคืน = ราหู
     *
     * 🚫 ไม่แต่งข้อมูลโหรขึ้นใหม่แม้แต่ช่องเดียว — ประกอบจากของที่มีอยู่จริงในระบบ:
     *   - ธาตุ / สี / จุดเด่น / กำลังพระเคราะห์ ← `config/thai_astrology_knowledge.php`
     *     ช่อง `planet_meta.ราหู` (คอมเมนต์ในไฟล์เขียนกำกับไว้เองว่าเป็น "ดาวที่ 8
     *     ที่ getPlanetByDayOfWeek ไม่มี" — คือเตรียมไว้รอจุดนี้พอดี)
     *   - ดาวมิตร/ศัตรู ← **อนุมานย้อนจาก `FortuneChartService::CHAOCHANA`**
     *     (วันไหนระบุราหูเป็นมิตร ⇒ ราหูก็มีวันนั้นเป็นมิตร) ไม่ใช่การเดา
     *   - เลขมงคล ← เลขดาวราหู = 8 ตามเลขศาสตร์ไทย (อาทิตย์ 1 … ราหู 8)
     *
     * @param  array  $fallback  ชุดของ "พุธกลางวัน" ใช้เป็นค่าตั้งต้นเผื่อ config หาย
     */
    protected function rahuRulerProfile(array $fallback): array
    {
        $meta = (array) config('thai_astrology_knowledge.planet_meta.ราหู', []);
        if ($meta === []) {
            return $fallback;   // config หาย → อย่าเดา ใช้ของเดิมไปก่อน
        }

        $friends = [];
        $enemies = [];
        foreach (FortuneChartService::CHAOCHANA as $row) {
            $self = FortuneChartService::PLANETS[$row['planet']]['name'] ?? null;
            if ($self === null) {
                continue;
            }
            if (in_array('rahu', $row['friends'] ?? [], true)) {
                $friends[] = $self;
            }
            if (in_array('rahu', $row['enemies'] ?? [], true)) {
                $enemies[] = $self;
            }
        }

        return [
            'planet' => 'ราหู (☊) — เกิดวันพุธกลางคืน',
            'element' => 'ธาตุ'.($meta['element'] ?? 'ลม'),
            'friends' => $friends === [] ? ($fallback['friends'] ?? '-') : implode(', ', array_unique($friends)),
            'enemies' => $enemies === [] ? ($fallback['enemies'] ?? '-') : implode(', ', array_unique($enemies)),
            'lucky_color' => (string) ($meta['color'] ?? ''),
            'unlucky_color' => $fallback['unlucky_color'] ?? '',
            'lucky_number' => '8',
            'lucky_days' => $fallback['lucky_days'] ?? '',
            'unlucky_days' => $fallback['unlucky_days'] ?? '',
            'personality' => (string) ($meta['trait'] ?? ''),
        ];
    }

    /** 🌙 เกิดกลางคืนไหม (ย่ำค่ำ 18:00 – ย่ำรุ่ง 06:00) · null = ไม่ทราบเวลา = ไม่ใช่ */
    protected function isNightBirth(?float $birthHour): bool
    {
        if ($birthHour === null) {
            return false;
        }

        return $birthHour >= self::NIGHT_STARTS_AT || $birthHour < self::NIGHT_ENDS_AT;
    }

    /**
     * 🌅 "วันทางโหร" — วันในสัปดาห์ที่ใช้หาดาวเจ้าเรือน ซึ่งเปลี่ยนตอน **ย่ำรุ่ง 06:00**
     *    ไม่ใช่เที่ยงคืนแบบปฏิทินสากล
     *
     * ⇒ เกิด "วันพุธ ตี 2" ทางโหรยังเป็น **อังคารกลางคืน** (ดาวเจ้าเรือน = อังคาร)
     * ⇒ เกิด "วันพฤหัส ตี 3" ทางโหรคือ **พุธกลางคืน** (ดาวเจ้าเรือน = ราหู)
     *
     * ⚠️ นี่คือเหตุผลที่ขอบเขตราหูไม่ใช่ "วันพุธ 18:00–05:59" ตามปฏิทิน
     *    แต่เป็น "พุธ 18:00 → พฤหัส 05:59" ตามวันโหร
     *
     * 🕛 ไม่ทราบเวลาเกิด → คืนวันตามปฏิทินเหมือนเดิม (ห้ามเดา)
     *
     * @param  int  $calendarDayOfWeek  Carbon::dayOfWeek (0=อาทิตย์..6=เสาร์)
     */
    public function thaiWeekday(int $calendarDayOfWeek, ?float $birthHour = null): int
    {
        if ($birthHour !== null && $birthHour < self::NIGHT_ENDS_AT) {
            return ($calendarDayOfWeek + 6) % 7;   // ก่อนย่ำรุ่ง = ยังเป็นคืนของ "เมื่อวาน"
        }

        return $calendarDayOfWeek;
    }

    /**
     * 🌙 คนเกิดวันพุธกลางคืน — ดาวเจ้าเรือนคือ "ราหู" ไม่ใช่ "พุธ"
     *
     * @param  int  $dayOfWeek  วัน**ทางโหร**แล้ว (ผ่าน thaiWeekday มาก่อน)
     */
    public function isWednesdayNight(int $dayOfWeek, ?float $birthHour = null): bool
    {
        return $dayOfWeek === self::WEDNESDAY && $this->isNightBirth($birthHour);
    }

    public function getThaksa(int $dayOfWeek, ?float $birthHour = null): array
    {
        $order = (array) config('thai_astrology_knowledge.thaksa_order', []);
        $bhava = (array) config('thai_astrology_knowledge.thaksa_bhava', []);
        $start = $this->thaksaStartIndex($dayOfWeek, $birthHour);

        if (empty($order) || empty($bhava) || $start === null) {
            return [];
        }

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
    public function getPlanetaryPeriod(int $dayOfWeek, int $age, ?float $birthHour = null): array
    {
        $order = (array) config('thai_astrology_knowledge.thaksa_order', []);
        $meta = (array) config('thai_astrology_knowledge.planet_meta', []);
        $tones = (array) config('thai_astrology_knowledge.period_tone', []);
        // wednesday-night charts start the maha-thaksa cycle on Rahu (12y) not Mercury (17y)
        $start = $this->thaksaStartIndex($dayOfWeek, $birthHour);

        if (empty($order) || $start === null || $age < 0) {
            return [];
        }

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
    /**
     * ⏰ ดึงเวลาเกิดจากข้อความ — รองรับ "HH:MM", "HH.MM น.", "เวลา 14:30", "ตอน 8 โมง", "เกิดเช้า/บ่าย"
     *
     * @return int|null ชั่วโมง 0-23 หรือ null = ไม่พบ
     */
    /**
     * 🕛 ดึง "เวลาเกิด" แบบเข้มงวด — ต้องมีคำบ่งชี้การเกิดอยู่ใกล้ๆ ตัวเลขเท่านั้น
     *
     * ⚠️ ทำไมต้องแยกจาก extractBirthHourFromText (2026-09-02):
     *   ตัวเดิมจับ "HH.MM" ล้วน ⇒ ประโยคอย่าง *"ปีนี้จะได้เงินเดือน 2.50 หมื่นไหม"*
     *   ถูกอ่านเป็นเวลาเกิด 02:50 → ลัคนาเพี้ยนทั้งผัง แล้วยังติดป้ายว่า
     *   "จากเวลาเกิดที่เจ้าชะตาบอก" = โกหกลูกค้า
     *   ยิ่งตอนนี้ลัคนาถูกคำนวณเสมอ ความเพี้ยนจะลามไปทุกภพ ⇒ ต้องเข้มงวด
     *
     * วิธี: หาคำบ่งชี้ (เกิด/คลอด/ลืมตา) แล้วอ่านเวลาเฉพาะในหน้าต่างรอบคำนั้น
     *
     * @return float|null null = ไม่ได้บอกเวลาเกิด → ผู้เรียกใช้ DEFAULT_BIRTH_HOUR
     */
    public function extractStatedBirthHour(string $text): ?float
    {
        $t = trim($text);
        if ($t === '') {
            return null;
        }

        $len = mb_strlen($t);
        $offset = 0;

        // ไล่ทุกตำแหน่งที่มีคำบ่งชี้ — คนอาจพิมพ์วันเกิดหลายที่ในข้อความเดียว
        while ($offset < $len) {
            $hit = null;
            foreach (['เกิด', 'คลอด', 'ลืมตา'] as $cue) {
                $pos = mb_strpos($t, $cue, $offset);
                if ($pos !== false && ($hit === null || $pos < $hit)) {
                    $hit = $pos;
                }
            }
            if ($hit === null) {
                return null;
            }

            // 🚫 "เกิด" ที่ไม่ได้แปลว่าคลอด — "จะเกิดอะไรขึ้น" เป็นคำถามดวงที่พบบ่อยที่สุด
            //    ถ้าไม่กรอง ประโยค "หนี้ 2.30 แสน จะเกิดอะไรขึ้น" จะถูกอ่านเป็นเวลาเกิด 02:30
            $after = mb_substr($t, $hit + 4, 8);
            $isBirthCue = true;
            foreach (['อะไร', 'เรื่อง', 'ปัญหา', 'ขึ้น', 'ผล', 'เหตุ', 'ความ'] as $notBirth) {
                if (mb_strpos($after, $notBirth) === 0) {
                    $isBirthCue = false;
                    break;
                }
            }

            if ($isBirthCue) {
                // หน้าต่างรอบคำบ่งชี้ — เผื่อทั้ง "เกิด 21:30" และ "21:30 น. ... เกิด"
                //   (สระ/วรรณยุกต์ไทยนับเป็นตัวอักษรแยก → ต้องเผื่อย้อนหลังกว้างกว่าที่คิด)
                $start = max(0, $hit - 45);
                $window = mb_substr($t, $start, 90);

                $hour = $this->extractBirthHourFromText($window);
                if ($hour !== null) {
                    return $hour;
                }
            }

            $offset = $hit + 1;
        }

        return null;
    }

    /**
     * 🕛 (2026-09-03) ดึงเวลาเกิดจาก "คำตอบของคำถามวันเกิด" — ผ่อนกฎคำบ่งชี้
     *
     * ⚠️ ทำไมต้องมีตัวนี้: `extractStatedBirthHour()` บังคับว่าต้องมีคำว่า
     *    เกิด/คลอด/ลืมตา อยู่ใกล้ตัวเลข ซึ่งถูกสำหรับแชททั่วไป **แต่ผิดสำหรับ
     *    ตอนที่บอทเพิ่งถามวันเกิดไปเอง** — ลูกค้าตอบสั้น ๆ ว่า "1/1/2521 06:30"
     *    ไม่มีคำว่า "เกิด" เลย ⇒ ของเดิมทิ้งเวลาไปเงียบ ๆ ทุกครั้ง
     *    (prod 3 ก.ย. 2569: บิลจ่ายเงิน 1,253 ใบ มี birth_time = 0 ใบ)
     *
     * เงื่อนไขที่ยังต้องผ่าน (กันอ่านมั่ว): ข้อความต้องมี **วันเดือนปีเกิด**
     * อยู่ด้วย หรือมีคำบ่งชี้การเกิด — ประโยคอย่าง "เงินเดือน 2.50 หมื่น"
     * จึงยังไม่ถูกอ่านเป็นเวลา
     *
     * 🎭 ตัดวันเดือนปีทิ้งก่อนอ่านเวลาเสมอ — "01.01.2521" มี "01.01" ซึ่งตรงแพทเทิร์น
     *    HH.MM พอดี ถ้าไม่ตัดจะได้เวลาเกิด 01:01 จากวันที่ของตัวเอง
     *
     * @return float|null null = ไม่ได้บอกเวลา → ผู้เรียกใช้ DEFAULT_BIRTH_HOUR
     */
    public function extractBirthHourFromAnswer(string $text): ?float
    {
        $t = $this->normalizeThaiDigits(trim($text));
        if ($t === '') {
            return null;
        }

        $masked = $this->maskBirthDates($t);

        $hasDate = $masked !== $t;
        $hasCue = preg_match('/เกิด|คลอด|ลืมตา/u', $t) === 1;

        if (! $hasDate && ! $hasCue) {
            return null;
        }

        $hour = $this->extractBirthHourFromText($masked);
        if ($hour !== null) {
            return $hour;
        }

        // ตอบมาแค่ช่วงเวลากว้าง ๆ ("ตอนเช้า") — ในบริบทนี้แปลว่าเวลาเกิดแน่นอน
        // จึงไม่ต้องมีคำว่า "เกิด" นำหน้าเหมือนใน extractBirthHourFromText
        // (เที่ยงคืน ถูก extractBirthHourFromText คืน 0.0 ไปแล้ว จึงไม่มีทางหลุดมาชน "เที่ยง" ตรงนี้)
        if (preg_match('/(เช้าตรู่|เช้ามืด|เช้า|สาย|เที่ยง|บ่าย|เย็น|ค่ำ|ดึก|กลางคืน)/u', $masked, $m)) {
            $map = [
                'เช้าตรู่' => 5.0, 'เช้ามืด' => 5.0, 'เช้า' => 8.0, 'สาย' => 10.0, 'เที่ยง' => 12.0,
                'บ่าย' => 14.0, 'เย็น' => 17.0, 'ค่ำ' => 20.0, 'ดึก' => 23.0, 'กลางคืน' => 22.0,
            ];

            return $map[$m[1]] ?? null;
        }

        return null;
    }

    /**
     * 🎭 ลบ "วันเดือนปี" ออกจากข้อความก่อนอ่านเวลา
     *
     * 🚨 (2026-09-03) บั๊กจริงที่เจอตอนจับผี: แพทเทิร์นเวลา `HH.MM` ตรงกับ **วันที่ที่คั่นด้วยจุด**
     *    พอดีเป๊ะ ⇒ ประโยคธรรมดาอย่าง *"ผมเกิด 01.01.2521 ครับ"* ถูกอ่านเป็นเวลาเกิด 01:01
     *    (ทดสอบจริง: 15.08.2530 → 15:08 · 03.09.2540 → 03:09)
     *    ผลคือ birth_time ผิด ⇒ ลัคนาและภพทั้ง 12 เพี้ยนทั้งผังของบิลที่จ่ายเงินแล้ว
     *    แถมพรอมต์ยังสั่งให้แม่หมอประกาศว่า "รับเวลาเกิดแล้ว ปรับผังใหม่แล้ว" = โกหกลูกค้า
     *
     * ต้องเรียกก่อนอ่านเวลา **ทุกเส้นทาง** (ทั้งตัวเข้มงวดและตัวคำตอบวันเกิด)
     */
    protected function maskBirthDates(string $text): string
    {
        $months = 'มกราคม|กุมภาพันธ์|มีนาคม|เมษายน|พฤษภาคม|มิถุนายน|กรกฎาคม|สิงหาคม|กันยายน|ตุลาคม|พฤศจิกายน|ธันวาคม'
            .'|ม\.?ค\.?|ก\.?พ\.?|มี\.?ค\.?|เม\.?ย\.?|พ\.?ค\.?|มิ\.?ย\.?|ก\.?ค\.?|ส\.?ค\.?|ก\.?ย\.?|ต\.?ค\.?|พ\.?ย\.?|ธ\.?ค\.?';

        return (string) preg_replace(
            [
                '/\d{1,2}\s*[\/\-.]\s*\d{1,2}\s*[\/\-.]\s*\d{2,4}/u',              // 1/1/2521 · 01.01.2521
                '/\d{1,2}\s*(?:'.$months.')\s*(?:พ\.?ศ\.?|ค\.?ศ\.?)?\s*\d{2,4}/u', // 29 มกราคม 2516
            ],
            ' ',
            $text
        );
    }

    /**
     * 🇹🇭 เลขไทยแบบคำ → ตัวเลข (ใช้กับนาฬิกาไทย "ตีห้า" / "บ่ายสองโมง" / "ยี่สิบนาฬิกา")
     *
     * ⚠️ ลำดับใน regex ต้องยาวก่อนสั้นเสมอ — ไม่งั้น "ยี่สิบเอ็ด" ถูกจับแค่ "ยี่" = 2
     */
    protected function thaiWordToInt(string $w): ?int
    {
        $map = [
            'ยี่สิบสาม' => 23, 'ยี่สิบสอง' => 22, 'ยี่สิบเอ็ด' => 21, 'ยี่สิบ' => 20,
            'สิบเก้า' => 19, 'สิบแปด' => 18, 'สิบเจ็ด' => 17, 'สิบหก' => 16, 'สิบห้า' => 15,
            'สิบสี่' => 14, 'สิบสาม' => 13, 'สิบสอง' => 12, 'สิบเอ็ด' => 11, 'สิบ' => 10,
            'หนึ่ง' => 1, 'นึง' => 1, 'เอ็ด' => 1, 'สอง' => 2, 'สาม' => 3, 'สี่' => 4,
            'ห้า' => 5, 'หก' => 6, 'เจ็ด' => 7, 'แปด' => 8, 'เก้า' => 9,
        ];

        return $map[$w] ?? null;
    }

    /**
     * 🇹🇭 อ่าน "นาฬิกาแบบไทย" ให้เป็นเวลาสากล (owner directive 2026-09-03)
     *   *"บอทต้องแยกแยะเข้าใจได้เอง แม้บอกเวลาแบบไทยๆ ก็ต้องประมาณได้ว่าเป็นเวลาสากลแบบไหน"*
     *
     * รองรับ: ตีห้า · ตี 5 ครึ่ง · บ่ายโมง · บ่าย 2 โมง · บ่ายสองโมง · หกโมงเช้า ·
     *         สี่โมงเย็น · สองทุ่มครึ่ง · ยี่สิบนาฬิกา · เที่ยงคืน · ย่ำรุ่ง/ย่ำค่ำ
     *
     * 🐛 บั๊กที่แก้ไปด้วย — **"บ่าย 2 โมง" เคยอ่านเป็น 02:00 (ผิดไป 12 ชม.)**
     *    เพราะกฎเดิม `(\d)\s*โมง\s*(บ่าย)?` คาดว่าตัวบอกช่วงอยู่ *หลัง* ตัวเลข
     *    แต่คนไทยพูด "บ่าย" นำหน้า ⇒ จับได้แค่ "2 โมง" แล้วคืน 2.0 ดิบๆ
     *
     * 🚫 จงใจไม่จับ "เที่ยง" เดี่ยวๆ (เที่ยงวัน/เที่ยงตรง) — เป็นคำที่โผล่ในประโยคทั่วไปบ่อย
     *    ("ตอนเที่ยงจะไปสัมภาษณ์") ปล่อยให้ลำดับ 3 ที่บังคับติดกับคำว่า "เกิด" จัดการ
     *
     * @return float|null ชั่วโมงทศนิยม (20.5 = 20:30) · null = ไม่ใช่นาฬิกาไทย
     */
    public function parseThaiClock(string $text): ?float
    {
        $t = $this->normalizeThaiDigits($text);

        $words = implode('|', array_keys([
            'ยี่สิบสาม' => 0, 'ยี่สิบสอง' => 0, 'ยี่สิบเอ็ด' => 0, 'ยี่สิบ' => 0,
            'สิบเก้า' => 0, 'สิบแปด' => 0, 'สิบเจ็ด' => 0, 'สิบหก' => 0, 'สิบห้า' => 0,
            'สิบสี่' => 0, 'สิบสาม' => 0, 'สิบสอง' => 0, 'สิบเอ็ด' => 0, 'สิบ' => 0,
            'หนึ่ง' => 0, 'นึง' => 0, 'เอ็ด' => 0, 'สอง' => 0, 'สาม' => 0, 'สี่' => 0,
            'ห้า' => 0, 'หก' => 0, 'เจ็ด' => 0, 'แปด' => 0, 'เก้า' => 0,
        ]));
        $n = '(?:(?<d>\d{1,2})|(?<w>'.$words.'))';
        $min = '(?:\s*[:\.](?<mm>\d{2})|\s*(?<half>ครึ่ง))?';

        $hourOf = function (array $m): ?int {
            $d = (string) ($m['d'] ?? '');

            return $d !== '' ? (int) $d : $this->thaiWordToInt((string) ($m['w'] ?? ''));
        };
        $fracOf = function (array $m): float {
            if ((string) ($m['half'] ?? '') !== '') {
                return 0.5;
            }
            $mm = (string) ($m['mm'] ?? '');

            return ($mm !== '' && (int) $mm < 60) ? ((int) $mm) / 60.0 : 0.0;
        };

        // เที่ยงคืน = 00:00 · ย่ำรุ่ง = 06:00 · ย่ำค่ำ = 18:00
        if (preg_match('/เที่ยงคืน/u', $t)) {
            return 0.0;
        }
        if (preg_match('/ย่ำรุ่ง/u', $t)) {
            return 6.0;
        }
        if (preg_match('/ย่ำค่ำ/u', $t)) {
            return 18.0;
        }

        // ตี 1-6 → 01:00-06:00 · ต้องติดตัวเลข/คำเลข ไม่งั้น "ตีราคา 5 แสน" กลายเป็นเวลาเกิด
        if (preg_match('/ตี\s*'.$n.$min.'/u', $t, $m)) {
            $h = $hourOf($m);
            if ($h !== null && $h >= 1 && $h <= 6) {
                return $h + $fracOf($m);
            }
        }

        // "บ่ายโมง" (ไม่มีตัวเลขเลย) = 13:00
        if (preg_match('/บ่าย\s*โมง'.$min.'/u', $t, $m)) {
            return 13.0 + $fracOf($m);
        }

        // "บ่าย N โมง" = N+12 (บ่ายสองโมง = 14:00)
        if (preg_match('/บ่าย\s*'.$n.'\s*โมง'.$min.'/u', $t, $m)) {
            $h = $hourOf($m);
            if ($h !== null && $h >= 1 && $h <= 5) {
                return $h + 12 + $fracOf($m);
            }
        }

        // "N โมงเช้า" 1-11 → ตรงตัว · "N โมงเย็น" 1-6 → +12 (สี่โมงเย็น = 16:00)
        if (preg_match('/'.$n.'\s*โมง\s*(?<p>เช้า|เย็น)'.$min.'/u', $t, $m)) {
            $h = $hourOf($m);
            $p = (string) ($m['p'] ?? '');
            if ($h !== null && $p === 'เย็น' && $h >= 1 && $h <= 6) {
                return $h + 12 + $fracOf($m);
            }
            if ($h !== null && $p === 'เช้า' && $h >= 1 && $h <= 11) {
                return $h + $fracOf($m);
            }
        }

        // "ทุ่มนึง" / "ทุ่มหนึ่ง" = 19:00 — ตัวเลขอยู่ *หลัง* หน่วย (คนไทยพูดแบบนี้บ่อย)
        if (preg_match('/ทุ่ม\s*(?:นึง|หนึ่ง)'.$min.'/u', $t, $m)) {
            return 19.0 + $fracOf($m);
        }

        // "N ทุ่ม" 1-5 → 19:00-23:00 · (?!เท) กัน "ทุ่มเท" ที่ไม่เกี่ยวกับเวลา
        if (preg_match('/'.$n.'\s*ทุ่ม(?!เท)'.$min.'/u', $t, $m)) {
            $h = $hourOf($m);
            if ($h !== null && $h >= 1 && $h <= 5) {
                return $h + 18 + $fracOf($m);
            }
        }

        // "N นาฬิกา" 0-23 → ตรงตัว (ระบบ 24 ชม. อยู่แล้ว)
        if (preg_match('/'.$n.'\s*นาฬิกา'.$min.'/u', $t, $m)) {
            $h = $hourOf($m);
            if ($h !== null && $h >= 0 && $h <= 23) {
                return $h + $fracOf($m);
            }
        }

        // "N โมง" ลอยๆ ไม่มีเช้า/เย็น — คนไทยหมายถึงกลางวัน (6 โมง = 06:00, 10 โมง = 10:00)
        if (preg_match('/'.$n.'\s*โมง'.$min.'/u', $t, $m)) {
            $h = $hourOf($m);
            if ($h !== null && $h >= 1 && $h <= 11) {
                return $h + $fracOf($m);
            }
        }

        return null;
    }

    public function extractBirthHourFromText(string $text): ?float
    {
        // 🎭 ตัดวันเดือนปีทิ้งก่อนเสมอ — ไม่งั้น "01.01.2521" กลายเป็นเวลา 01:01 (ดู maskBirthDates)
        $t = $this->maskBirthDates($this->normalizeThaiDigits($text));

        // 🕛 ลำดับ 0: นาฬิกาแบบไทย (ตี/ทุ่ม/โมง/บ่าย/นาฬิกา) — ต้องมาก่อน HH:MM
        //    เพราะ "ตี 5.30" ต้องได้ 05:30 ไม่ใช่ให้กฎ HH.MM คว้าไปตีความเอง
        $thaiClock = $this->parseThaiClock($t);
        if ($thaiClock !== null) {
            return $thaiClock;
        }

        // ลำดับ 1: HH:MM หรือ HH.MM (+ optional น./AM/PM) — เก็บนาทีด้วย
        if (preg_match('/(?<!\d)(\d{1,2})[:\.](\d{2})\s*(น\.?|AM|PM|am|pm)?/u', $t, $m)) {
            $h = (int) $m[1];
            $min = (int) $m[2];
            if ($min >= 60) {
                $min = 0;
            }
            $suffix = mb_strtolower((string) ($m[3] ?? ''));
            if (($suffix === 'pm' || $suffix === 'p.m.') && $h < 12) {
                $h += 12;
            } elseif (($suffix === 'am' || $suffix === 'a.m.') && $h === 12) {
                $h = 0;
            }
            if ($h >= 0 && $h <= 23) {
                return $h + $min / 60.0; // ⭐ คืนเป็น float รวมนาที
            }
        }

        // ลำดับ 2: คำไทย "โมงเช้า/บ่าย/เย็น/ค่ำ/ดึก"
        if (preg_match('/(\d{1,2})\s*โมง\s*(เช้า|บ่าย|เย็น|ค่ำ|ดึก)?/u', $t, $m)) {
            $h = (int) $m[1];
            $period = (string) ($m[2] ?? '');
            if ($period === 'บ่าย' || $period === 'เย็น') {
                $h = $h < 12 ? $h + 12 : $h;
            } elseif ($period === 'ค่ำ' || $period === 'ดึก') {
                $h = $h < 12 ? $h + 18 : $h;
            }
            if ($h >= 0 && $h <= 23) {
                return (float) $h;
            }
        }

        // ลำดับ 3: ช่วงเวลาคร่าวๆ — ต้องต่อจากคำว่า "เกิด" แบบชิด ๆ เท่านั้น
        //
        // 🐛 (2026-09-03) ของเดิมใช้ `เกิด.*?(...)` = ข้ามกี่ตัวอักษรก็ได้ ⇒ ประโยค
        //    "หนูเกิดมาลำบาก ตอนเที่ยงจะไปสัมภาษณ์งาน" ถูกอ่านเป็นเวลาเกิด 12:00
        //    แล้วผังดวงทั้งใบเพี้ยน + ติดป้ายว่า "จากเวลาเกิดที่เจ้าชะตาบอก" = โกหกลูกค้า
        //    ใหม่: อนุญาตเฉพาะคำเชื่อมบอกเวลาไม่กี่คำระหว่าง "เกิด" กับช่วงเวลา
        if (preg_match('/เกิด\s*(?:ตอน|ช่วง|เวลา|ประมาณ|ราว\s*ๆ?)?\s*(เช้าตรู่|เช้า|สาย|เที่ยง|บ่าย|เย็น|ค่ำ|ดึก|กลางคืน)/u', $t, $m)) {
            $map = [
                'เช้าตรู่' => 5.0, 'เช้า' => 8.0, 'สาย' => 10.0, 'เที่ยง' => 12.0,
                'บ่าย' => 14.0, 'เย็น' => 17.0, 'ค่ำ' => 20.0, 'ดึก' => 23.0, 'กลางคืน' => 22.0,
            ];

            return $map[$m[1]] ?? null;
        }

        // 🚫 จงใจ **ไม่** จับคำว่า "เที่ยง" เดี่ยว ๆ ที่นี่ — เป็นคำที่โผล่ในประโยคทั่วไปบ่อย
        //    ("ตอนเที่ยงจะไปสัมภาษณ์") ถ้าจับ จะกลายเป็นเวลาเกิด 12:00 ให้คนที่ไม่ได้บอก
        //    ⇒ ย้ายไปไว้ใน extractBirthHourFromAnswer() ซึ่งรู้แน่ว่าทั้งข้อความคือคำตอบวันเกิด
        return null;
    }

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
                // 🔯 (2026-09-03) เดิม 'ราหู, ดาวพฤหัสบดี' → ขัดกับแถววันพฤหัสบดีที่ระบุ
                //    เสาร์เป็น "ศัตรู" · คู่เดียวในตารางที่ฝั่งหนึ่งว่ามิตร อีกฝั่งว่าศัตรู
                //    แก้เป็นศุกร์ (ศุกร์เป็นกลางกับเสาร์อยู่แล้ว จึงไม่สร้างข้อขัดแย้งใหม่)
                //    ⚠️ ตารางนี้มี 3 ชุดในระบบ ต้องแก้พร้อมกันเสมอ — ดู ChaochanaConsistencyTest
                'friends' => 'ราหู, ดาวศุกร์',
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
        // 🇹🇭 (2026-09-03) ตารางเดิมเป็น **ราศีสากล** (มังกร 22 ธ.ค.–19 ม.ค.)
        //    ต่างจากราศีไทยเกือบครึ่งเดือน · ตารางนี้เปลี่ยนเป็นช่วงวันแบบไทย (นิรายนะ)
        //    ⚠️ ยังเป็นแค่ค่าประมาณเพราะไม่รู้ปีเกิด — ถ้ามี Carbon ให้ใช้
        //       getZodiacSignForDate() ซึ่งคำนวณจากดวงอาทิตย์จริง
        //
        // 🐛 (จับผีรอบเดียวกัน) รอบแรกผมเปลี่ยนแค่ "ตัวเลขวัน" แต่ลืมว่าราศีไทยยกช้ากว่า
        //    ราศีสากลเกือบเดือน ⇒ **ชื่อราศีทุกแถวต้องเลื่อนถอยไป 1 ราศีด้วย**
        //    ความหมายของแต่ละแถว = "ราศีที่ครอบวันที่ 1 ถึง end_day ของเดือนนั้น"
        //    เช่น ก.ย. 1–16 = สิงห์ (อาทิตย์ยกเข้ากันย์ 17 ก.ย.) ไม่ใช่กันย์
        //    ตัวเลขยึดจากวันอาทิตย์ยกราศีที่ ephemeris คำนวณได้ (ตรงกับปฏิทินโหรไทย)
        $signs = [
            ['name' => 'ธนู (Sagittarius)', 'end_month' => 1, 'end_day' => 14],   // ยกเข้ามังกร 15 ม.ค.
            ['name' => 'มังกร (Capricorn)', 'end_month' => 2, 'end_day' => 12],   // ยกเข้ากุมภ์ 13 ก.พ.
            ['name' => 'กุมภ์ (Aquarius)', 'end_month' => 3, 'end_day' => 14],    // ยกเข้ามีน 15 มี.ค.
            ['name' => 'มีน (Pisces)', 'end_month' => 4, 'end_day' => 13],        // ยกเข้าเมษ 14 เม.ย. (สงกรานต์)
            ['name' => 'เมษ (Aries)', 'end_month' => 5, 'end_day' => 14],         // ยกเข้าพฤษภ 15 พ.ค.
            ['name' => 'พฤษภ (Taurus)', 'end_month' => 6, 'end_day' => 15],       // ยกเข้าเมถุน 16 มิ.ย.
            ['name' => 'เมถุน (Gemini)', 'end_month' => 7, 'end_day' => 16],      // ยกเข้ากรกฎ 17 ก.ค.
            ['name' => 'กรกฎ (Cancer)', 'end_month' => 8, 'end_day' => 16],       // ยกเข้าสิงห์ 17 ส.ค.
            ['name' => 'สิงห์ (Leo)', 'end_month' => 9, 'end_day' => 16],         // ยกเข้ากันย์ 17 ก.ย.
            ['name' => 'กันย์ (Virgo)', 'end_month' => 10, 'end_day' => 17],      // ยกเข้าตุลย์ 18 ต.ค.
            ['name' => 'ตุลย์ (Libra)', 'end_month' => 11, 'end_day' => 16],      // ยกเข้าพิจิก 17 พ.ย.
            ['name' => 'พิจิก (Scorpio)', 'end_month' => 12, 'end_day' => 15],    // ยกเข้าธนู 16 ธ.ค.
        ];

        foreach ($signs as $sign) {
            if ($month === $sign['end_month'] && $day <= $sign['end_day']) {
                return $sign['name'];
            }
            if ($month < $sign['end_month']) {
                return $sign['name'];
            }
        }

        return 'ธนู (Sagittarius)'; // 16 ธ.ค. เป็นต้นไป (อาทิตย์ยกเข้าธนูแล้ว)
    }

    /**
     * 🇹🇭 ราศีเกิดแบบนิรายนะ คำนวณจากตำแหน่งดวงอาทิตย์จริงของวันนั้น
     *
     * ใช้ตัวนี้เสมอเมื่อมี Carbon ในมือ — แม่นกว่าตารางช่วงวัน และไม่ขัดกับ
     * บรรทัด "ตำแหน่งดาวอาทิตย์" ในผังดวงเดียวกัน
     */
    public function getZodiacSignForDate(\Carbon\Carbon $date): string
    {
        return (new PlanetEphemeris)->zodiacSignLabel($date);
    }
}
