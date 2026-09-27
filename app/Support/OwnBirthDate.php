<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * 🎂 OwnBirthDate — "วันเกิดของเจ้าชะตาเอง" ที่ลูกค้าพิมพ์มากลางแชท (นอกขั้นถามวันเกิด)
 *
 * 🐛 (2026-09-27, owner) *"ลูกค้าเปลี่ยนวันเกิด แม่หมอบอทรับว่ารับวันเกิดแล้ว แต่ไม่เปลี่ยนในบิลให้ตรงจริง
 *    วันเวลา เมืองเกิดด้วย"*
 *
 *    เคสจริง FTU-260927-A4514 (LINE, Celtic 99 ระหว่างรอโอน):
 *      ลูกค้า: "อย่าทำนายผิดวันเกิดนะคะ" → "27/6/2521"
 *      แม่หมอ (AI ฟังระหว่างรอโอน): *"ได้ข้อมูลวันเกิด 27 มิถุนายน 2521 แล้วนะคะลูก แม่หมอรับทราบ…"*
 *      แต่ `fortune_readings.birth_date` = NULL — ไม่มีโค้ดตัวไหนเก็บ แอดมินต้องแก้มือ
 *    ต้นเหตุ: ขั้นถามวันเกิด (39/99) เก็บครบ แต่ **เลนแชทอิสระ** (รอโอน / ถาม-ตอบ / คุยต่อ)
 *    ไม่มีตัวอ่านวันเกิดเลย มีแต่ตัวอ่านเวลาเกิด ⇒ AI เห็นข้อความลูกค้าแล้วรับปากเอง
 *
 * คลาสนี้ตอบคำถามเดียว: *ข้อความนี้บอก "วันเกิดของตัวเอง" มาไหม* — ไม่เขียน DB ไม่เรียก AI
 *   (ตัวอ่านวันเกิดหลัก parseBirthDate() มี AI fallback — ห้ามปล่อยข้อความแชททั่วไปไหลเข้า
 *    เพราะจะเดาเลขอะไรก็ได้เป็นวันเกิด) ⇒ ที่นี่อ่านเฉพาะรูปวันที่ชัด ๆ: ตัวเลข d/m/y · วัน เดือนไทย ปี
 *
 * 2 ทางที่นับว่าเป็นของเจ้าชะตา:
 *   • bare — ทั้งข้อความมีแต่ข้อมูลเกิด ("27/6/2521" · "เกิด 27 มิ.ย. 21 ตี 5 เชียงใหม่ค่ะ")
 *   • self — มีคำบ่งชี้ว่าเป็นของตัวเองนำหน้าวันที่ ("หนูเกิด…" · "วันเกิดของฉันคือ…" · "แก้วันเกิดเป็น…")
 * ⛔ วันเกิดคนอื่นต้องไม่หลุด — "แฟนเกิด 3/6/2497" · "วันเกิดแม่ 1/1/2500" · "แฟนหนูเกิด…"
 *    (รายการญาติชุดเดียวกับ ThaiProvinces::resolveBirthplace — ตัวกันที่เกิดของคนอื่นที่พิสูจน์แล้ว)
 *
 * ⚠️ ผู้เรียกเป็นคนตัดสินว่าจะ "ใช้ทันที" หรือ "ถามยืนยันก่อน" — ที่นี่แค่อ่าน:
 *    ก่อนทำนาย (รอโอน/เปิดไพ่) = เขียนลงบิลได้เลย · หลังทำนายแล้ว = ถามยืนยันก่อนเสมอ
 *    ("3/6/2497" ที่ส่งตามหลัง "แล้วแฟนหนูล่ะ" คือวันเกิดแฟน — กลางวงถาม-ตอบกำกวมจริง)
 *
 * @see \Tests\Unit\Support\OwnBirthDateTest
 */
final class OwnBirthDate
{
    /** ทั้งข้อความมีแต่ข้อมูลเกิด */
    public const BASIS_BARE = 'bare';

    /** มีคำบ่งชี้ว่าเป็นวันเกิดของตัวเอง */
    public const BASIS_SELF = 'self';

    /**
     * ช่วงอายุที่รับ — ชุดเดียวกับ FortuneConversationService::MIN/MAX_ACCEPTED_BIRTH_AGE
     * (นอกช่วงนี้แทบทั้งหมดคืออ่านผิด เช่นวันนัด/วันเหตุการณ์ในปีนี้)
     */
    public const MIN_AGE = 7;

    public const MAX_AGE = 99;

    /** ระยะสูงสุดระหว่างคำบ่งชี้กับวันที่ (ตัวอักษร) — "หนูเกิดวันอังคารที่ 27/6/2521" ≈ 20 */
    private const SELF_SPAN_MAX = 40;

    /** สรรพนามแทนตัวเอง */
    private const SELF_PRONOUN = '(?:หนู|ฉัน|ชั้น|ผม|ดิฉัน|เรา|ตัวเอง|ข้าพเจ้า|กู|ข้า)';

    /** "เกิด" ที่แปลว่าคลอด — ตัด "จะเกิดอะไรขึ้น / เกิดเรื่อง / เกิดปัญหา" (ชุดเดียวกับ extractStatedBirthHour) */
    private const BORN = '(?:เกิด|คลอด)(?!อะไร|เรื่อง|ปัญหา|ขึ้น|ผล|เหตุ|ความ)';

    private const BIRTHDAY = 'วัน(?:เดือนปี)?เกิด';

    /**
     * คนอื่น/สัตว์เลี้ยง — ชุดเดียวกับ ThaiProvinces::KIN (+ คู่/หัวหน้า/เจ้าหนี้)
     * "แม่" ที่ตามด้วย "หมอ" = เรียกแม่หมอ ไม่ใช่แม่ของลูกค้า · "ลูกค้า" ไม่ใช่ลูก
     */
    private const KIN = '(?:แฟน|สามี|ภรรยา|ภริยา|เมีย|ผัว|ลูก(?!ค้า)|พ่อ|แม่(?!หมอ)|พี่|น้อง|เพื่อน|หลาน|ปู่|ย่า|ยาย|ญาติ'
        .'|หมา|แมว|เขา|เธอ|คู่|หัวหน้า|เจ้านาย|ลูกหนี้|เจ้าหนี้|คนรัก|กิ๊ก|ชู้)';

    /** คำขยายหลังคำเรียกญาติ ("แฟนเก่า" · "ลูกสาว" · "พี่ชาย") */
    private const KIN_MOD = '(?:เก่า|ใหม่|สนิท|ชาย|สาว|คนนี้|คนนั้น|คนแรก|คนเล็ก|คนโต)';

    /** คำปฏิเสธ — วันที่ที่ตามหลังคือ "วันที่ผิด" ที่ลูกค้ากำลังแย้ง ไม่ใช่วันเกิดที่ถูก */
    private const NEGATION = '(?:ไม่ใช่|ไม่ถูก|ผิด|คลาดเคลื่อน)';

    /**
     * ชื่อเดือนไทย → เลขเดือน (ยาวก่อนสั้น — "มิถุนายน" ต้องมาก่อน "มิถุนา")
     * ตัวย่อเขียนเป็น regex เพราะคนพิมพ์ทั้ง "มิ.ย." "มิ.ย" "มิย" "มิ ย"
     *
     * @var array<string, int>
     */
    private const MONTHS = [
        'พฤศจิกายน' => 11, 'กุมภาพันธ์' => 2, 'กรกฎาคม' => 7, 'พฤษภาคม' => 5, 'มิถุนายน' => 6,
        'สิงหาคม' => 8, 'ธันวาคม' => 12, 'มกราคม' => 1, 'มีนาคม' => 3, 'เมษายน' => 4,
        'กันยายน' => 9, 'ตุลาคม' => 10,
        'พฤศจิกา' => 11, 'กรกฎา' => 7, 'พฤษภา' => 5, 'มิถุนา' => 6, 'กุมภา' => 2, 'สิงหา' => 8,
        'ธันวา' => 12, 'มกรา' => 1, 'มีนา' => 3, 'เมษา' => 4, 'กันยา' => 9, 'ตุลา' => 10,
        'มี\.?\s*ค\.?' => 3, 'เม\.?\s*ย\.?' => 4, 'มิ\.?\s*ย\.?' => 6,
        'ม\.?\s*ค\.?' => 1, 'ก\.?\s*พ\.?' => 2, 'พ\.?\s*ค\.?' => 5, 'ก\.?\s*ค\.?' => 7, 'ส\.?\s*ค\.?' => 8,
        'ก\.?\s*ย\.?' => 9, 'ต\.?\s*ค\.?' => 10, 'พ\.?\s*ย\.?' => 11, 'ธ\.?\s*ค\.?' => 12,
    ];

    /**
     * วันเกิดของเจ้าชะตาในข้อความนี้
     *
     * @param  bool  $allowBare  นับข้อความที่ "มีแต่ข้อมูลเกิด" (ไม่มีคำบ่งชี้) ว่าเป็นของเจ้าชะตาไหม
     * @return array{ymd: string, basis: string, conflict: array{stated_day: int, parsed_day: int}|null}|null
     *                                                                                                        conflict ≠ null = ลูกค้าบอกวันในสัปดาห์ที่ไม่ตรงกับวันที่ ⇒ ห้ามใช้ ต้องถามกลับ
     */
    public static function find(string $text, bool $allowBare = true): ?array
    {
        $t = self::normalize($text);
        if ($t === '' || mb_strlen($t) > 400) {
            return null;
        }

        $dates = self::dates($t);
        if ($dates === []) {
            return null;
        }

        // 1) bare — ทั้งข้อความคือข้อมูลเกิด และมีวันเกิดเดียว
        if ($allowBare && self::residual($t, $dates) === '') {
            $distinct = array_values(array_unique(array_column($dates, 'ymd')));

            return count($distinct) === 1
                ? self::result($text, $distinct[0], self::BASIS_BARE)
                : null;
        }

        // 2) self — คำบ่งชี้ของตัวเอง แล้ววันที่ตัวแรกที่ตามมาในระยะใกล้ ไม่มีคนอื่นคั่น
        foreach (self::selfCues($t) as [$cueStart, $cueEnd]) {
            foreach ($dates as $d) {
                if ($d['start'] < $cueEnd) {
                    continue;
                }

                $between = substr($t, $cueEnd, $d['start'] - $cueEnd);
                // 🚫 (จับผี) "วันเกิดไม่ใช่ 27/7/2521 ที่ถูกคือ 27/6/2521" — วันที่ที่ตามคำปฏิเสธคือ "ตัวที่ผิด"
                //    ต้องไม่หยิบ · ตัวที่ถูกจะถูกจับโดยคำบ่งชี้ "ที่ถูกคือ / ต้องเป็น" แทน
                if (mb_strlen($between) > self::SELF_SPAN_MAX
                    || preg_match('/'.self::KIN.'|[?？\n]|ไหม|มั้ย|'.self::NEGATION.'/u', $between)) {
                    break; // วันที่ตัวนี้ไม่ได้ผูกกับคำบ่งชี้นี้ — ลองคำบ่งชี้ถัดไป
                }

                // "วันเกิด 3/6/2497 ของแฟน" — เจ้าของวันที่ตามหลัง
                if (preg_match('/^\s*(?:เป็น)?ของ\s*'.self::KIN.'/u', substr($t, $d['end']))) {
                    break;
                }

                return self::result($text, $d['ymd'], self::BASIS_SELF);
            }
        }

        return null;
    }

    /**
     * ข้อความนี้ "มีแต่ข้อมูลเกิด" (วัน/เวลา/จังหวัดเกิด + คำเชื่อม) ไม่มีเรื่องอื่นปนเลยไหม
     *
     * ใช้ตัดสินว่าตอบแบบตายตัวได้ (ทวนสิ่งที่บันทึก) หรือต้องให้ AI ตอบเนื้อความส่วนอื่นด้วย
     * ต้องมีข้อมูลเกิดอย่างน้อย 1 อย่าง — "ค่ะ" เปล่า ๆ ไม่นับ
     */
    public static function isBirthInfoOnly(string $text): bool
    {
        $t = self::normalize($text);
        if ($t === '' || mb_strlen($t) > 120) {
            return false;
        }

        // ต้องมีวันที่ หรือคำว่า "เกิด" — เวลา/ชื่อจังหวัดลอย ๆ ไม่นับ (จับผี: "2 ทุ่มค่ะ" · "18.30" ·
        //   "เลยค่ะ" = จ.เลย เคยผ่าน แล้วถูกนับเป็นข้อมูลเกิดทั้งข้อความ)
        $hasBirthInfo = self::dates($t) !== [] || preg_match('/'.self::BORN.'/u', $t);

        return $hasBirthInfo && self::residual($t, self::dates($t)) === '';
    }

    /**
     * ข้อความพูดถึงคนอื่น/สัตว์เลี้ยงไหม (แฟน/แม่/ลูก/เพื่อน/หมา …) — ใช้กันตัวอ่านวันที่แบบหลวม
     * ไม่ให้หยิบวันเกิดของคนอื่นมาเสนอแก้ ("ไม่ใช่ค่ะ เป็นวันเกิดแฟน 3/6/2497")
     */
    public static function mentionsOtherPerson(string $text): bool
    {
        $t = self::normalize($text);

        return $t !== '' && (bool) preg_match('/'.self::KIN.'/u', $t);
    }

    /**
     * ลูกค้าพูดถึงข้อมูลเกิด (วันเกิด/เวลาเกิด/ที่เกิด) ในข้อความนี้ไหม — ใช้ตัดสินว่าต้องกำกับ AI
     * ว่า "ระบบบันทึกอะไรไว้แล้ว/ยังไม่ได้บันทึก" (ห้ามรับปากลอย ๆ)
     */
    public static function mentionsBirthInfo(string $text): bool
    {
        $t = self::normalize($text);
        if ($t === '') {
            return false;
        }

        return (bool) preg_match(
            '/'.self::BIRTHDAY.'|เวลาเกิด|จังหวัดเกิด|บ้านเกิด|เกิด(?:วันที่|วัน|เดือน|ปี|ตอน|เวลา|ที่|จังหวัด|ช่วง)'
            .'|'.self::SELF_PRONOUN.'\s*'.self::BORN.'/u',
            $t
        ) || self::dates($t) !== [];
    }

    /**
     * ลูกค้ากำลังพูดถึง "การเกิดของตัวเอง" ไหม (มีคำบ่งชี้ของตัวเอง และไม่ได้เป็นของคนอื่น)
     *
     * ใช้เปิดทางเก็บเวลา/จังหวัดเกิดจากข้อความที่ไม่มีวันที่ ("หนูเกิดตี 5 ที่เชียงใหม่ค่ะ")
     * ⛔ "แฟนเกิดตี 5" ต้องไม่ผ่าน — ตัวอ่านเวลาเกิดเองไม่มีด่านกันคนอื่น
     */
    public static function isOwnBirthTalk(string $text): bool
    {
        $t = self::normalize($text);

        return $t !== '' && self::selfCues($t) !== [];
    }

    /**
     * คำว่า "เกิด/คลอด" ทุกตัวในข้อความเป็นการเกิดของคนอื่นไหม ("แฟนเกิดตี 2" · "แม่ของหนูเกิดตอนเช้า")
     *
     * ใช้กันตัวอ่านเวลาเกิด (FortuneReading::captureStatedBirthTime — ไม่มีด่านคนอื่นในตัว)
     * ไม่ให้เก็บเวลาเกิดของคนอื่นเป็นของเจ้าชะตา · ไม่มีคำว่าเกิดเลย = false (ให้ตัวอ่านตัดสินเองตามเดิม)
     * "แม่ หนูเกิด…" (เว้นวรรค = เรียกแม่หมอ แล้วพูดถึงตัวเอง) ไม่นับเป็นคนอื่น
     */
    public static function birthCueBelongsToOther(string $text): bool
    {
        $t = self::normalize($text);
        if (! preg_match_all('/'.self::BORN.'/u', $t, $m, PREG_OFFSET_CAPTURE)) {
            return false;
        }

        $otherTail = '/'.self::KIN.self::KIN_MOD.'?(?:\s*ของ\s*'.self::SELF_PRONOUN.'|'.self::SELF_PRONOUN.')?\s*$/u';
        foreach ($m[0] as [$match, $offset]) {
            if (! preg_match($otherTail, mb_substr(substr($t, 0, $offset), -30))) {
                return false; // มีอย่างน้อยหนึ่งจุดที่เป็นการเกิดของเจ้าชะตาเอง
            }
        }

        return true;
    }

    /**
     * แปลงข้อความวันที่ → Y-m-d (ค.ศ.) แบบไม่พึ่ง AI — null = ไม่ใช่วันเกิดที่เป็นไปได้
     */
    public static function parse(string $text): ?string
    {
        $dates = self::dates(self::normalize($text));

        return $dates[0]['ymd'] ?? null;
    }

    // ─────────────────────────────────────────────────────────────

    /**
     * @return array{ymd: string, basis: string, conflict: array{stated_day: int, parsed_day: int}|null}
     */
    private static function result(string $rawText, string $ymd, string $basis): array
    {
        $conflict = null;

        // 🔑 วันในสัปดาห์ที่ลูกค้าบอกเองชนะตัวเลข ([[rule_stated_weekday_outranks_parsed_date]])
        //    "เกิดวันอาทิตย์ 27/6/2521" ทั้งที่ 27/6/2521 เป็นวันอังคาร ⇒ ห้ามเลือกเชื่อเงียบ ๆ
        $stated = StatedBirthDayName::stated($rawText);
        if ($stated !== null) {
            $parsedDay = Carbon::parse($ymd)->dayOfWeek;
            // 🌙 (จับผี) เกิดก่อนรุ่งสาง โหรไทยนับเป็น "วันก่อนหน้า" — "เกิดวันจันทร์ 27/6/2521 ตี 2" ถูกต้องแล้ว
            //    (27/6/2521 เป็นวันอังคารตามปฏิทิน) ห้ามตีเป็นข้อมูลขัดกัน ไม่งั้นถามวนไม่จบ
            $preDawnPreviousDay = $stated === ($parsedDay + 6) % 7 && self::statesPreDawnBirth($rawText);
            if ($parsedDay !== $stated && ! $preDawnPreviousDay) {
                $conflict = ['stated_day' => $stated, 'parsed_day' => $parsedDay];
            }
        }

        return ['ymd' => $ymd, 'basis' => $basis, 'conflict' => $conflict];
    }

    /**
     * ข้อความบอกว่าเกิดช่วงก่อนรุ่งสาง (ตี 1–ตี 5 / 00:00–05:59 / เที่ยงคืน / เช้ามืด) ไหม
     * ตัดวันที่ทิ้งก่อน — "1.12.2530" มี "1.12" หน้าตาเหมือนเวลา
     */
    private static function statesPreDawnBirth(string $rawText): bool
    {
        $t = (string) preg_replace('/(?<!\d)\d{1,2}\s*[\/\-.]\s*\d{1,2}\s*[\/\-.]\s*\d{2,4}(?!\d)/u', ' ', self::normalize($rawText));

        return (bool) preg_match(
            '/ตี\s*[1-5](?!\d)|ตี\s*(?:หนึ่ง|สอง|สาม|สี่|ห้า)|(?<!\d)0?[0-5]\s*[:.]\s*[0-5]\d(?!\d)|เที่ยงคืน|เช้ามืด|ย่ำรุ่ง/u',
            $t
        );
    }

    /**
     * ทำความสะอาดก่อนอ่าน — เลขไทย/ลาว → อารบิก · ตัด markdown · ยุบวรรค
     */
    private static function normalize(string $text): string
    {
        $t = str_replace(
            ['๐', '๑', '๒', '๓', '๔', '๕', '๖', '๗', '๘', '๙', '໐', '໑', '໒', '໓', '໔', '໕', '໖', '໗', '໘', '໙'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $text
        );
        $t = (string) preg_replace('/[*_~`]+/u', ' ', $t);

        return trim((string) preg_replace('/\s+/u', ' ', $t));
    }

    /**
     * วันที่ทุกตัวในข้อความ เรียงตามตำแหน่ง — ตำแหน่งเป็นไบต์ (ใช้ substr เท่านั้น)
     *
     * @return array<int, array{ymd: string, start: int, end: int}>
     */
    private static function dates(string $t): array
    {
        $found = [];

        // วัน/เดือน/ปี ตัวเลข — ปี 2 หรือ 4 หลัก ("19.00-20.30" ได้เดือน 0 → ตกด่าน checkdate)
        if (preg_match_all('/(?<!\d)(\d{1,2})\s*[\/\-.]\s*(\d{1,2})\s*[\/\-.]\s*(\d{4}|\d{2})(?!\d)/u', $t, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($m as $hit) {
                $ymd = self::toYmd((int) $hit[1][0], (int) $hit[2][0], (int) $hit[3][0]);
                if ($ymd !== null) {
                    $found[] = ['ymd' => $ymd, 'start' => $hit[0][1], 'end' => $hit[0][1] + strlen($hit[0][0])];
                }
            }
        }

        // วัน + ชื่อเดือนไทย + ปี ("27 มิถุนายน 2521" · "27มิ.ย.21" · "27 เดือนมิถุนายน พ.ศ. 2521")
        $monthAlt = implode('|', array_keys(self::MONTHS));
        $re = '/(?<!\d)(\d{1,2})\s*(?:เดือน\s*)?('.$monthAlt.')\s*(?:ปี\s*)?(?:พ\.?\s*ศ\.?|ค\.?\s*ศ\.?)?\s*(\d{4}|\d{2})(?!\d)/u';
        if (preg_match_all($re, $t, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($m as $hit) {
                $month = self::monthNumber($hit[2][0]);
                $ymd = $month === null ? null : self::toYmd((int) $hit[1][0], $month, (int) $hit[3][0]);
                if ($ymd !== null) {
                    $found[] = ['ymd' => $ymd, 'start' => $hit[0][1], 'end' => $hit[0][1] + strlen($hit[0][0])];
                }
            }
        }

        usort($found, fn ($a, $b) => $a['start'] <=> $b['start']);

        return $found;
    }

    private static function monthNumber(string $token): ?int
    {
        foreach (self::MONTHS as $pattern => $num) {
            if (preg_match('/^(?:'.$pattern.')$/u', $token)) {
                return $num;
            }
        }

        return null;
    }

    /**
     * วัน/เดือน/ปีดิบ → Y-m-d · ปี 2 หลัก = พ.ศ. ก่อนเสมอ (ThaiBirthYear — [[rule_birthyear_buddhist_first]])
     */
    private static function toYmd(int $day, int $month, int $year): ?string
    {
        $year = ThaiBirthYear::normalize($year);
        if ($year === null || ! checkdate($month, $day, $year)) {
            return null;
        }

        $age = (int) Carbon::now()->format('Y') - $year;
        if ($age < self::MIN_AGE || $age > self::MAX_AGE) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * ตำแหน่งคำบ่งชี้ "ของตัวเอง" ทั้งหมด (ไบต์) — ตัดตัวที่มีคำเรียกคนอื่นติดอยู่ข้างหน้า
     *
     * @return array<int, array{0: int, 1: int}> [เริ่ม, จบ]
     */
    private static function selfCues(string $t): array
    {
        // [แพทเทิร์น, ต้องไม่มีคนอื่นอยู่ในประโยคก่อนหน้าเลยไหม]
        $patterns = [
            // หนูเกิด · ผมเกิด · ตัวเองเกิด
            [self::SELF_PRONOUN.'\s*(?:เป็นคน|เอง)?\s*'.self::BORN, false],
            // วันเกิดหนู · วันเกิดของฉัน
            [self::BIRTHDAY.'\s*(?:ของ\s*)?'.self::SELF_PRONOUN, false],
            // วันเกิดที่ถูก(ต้อง) · วันเกิดจริง · วันเกิดใหม่
            [self::BIRTHDAY.'\s*(?:ที่)?\s*(?:ถูก(?:ต้อง)?|แท้จริง|จริง|ใหม่)', true],
            // แก้วันเกิด · เปลี่ยนวันเกิดเป็น
            ['(?:แก้ไข|แก้|เปลี่ยน|อัพเดท|อัปเดต)\s*(?:เป็น)?\s*'.self::BIRTHDAY, true],
            // คำชี้ "ตัวที่ถูก" หลังประโยคแย้ง — "วันเกิดไม่ใช่ 27/7 ที่ถูกคือ 27/6" · "…ผิด ต้องเป็น 27/6"
            //   ⚠️ (จับผี) เดิมใช้ "วันเกิดผิด/ไม่ใช่" เป็นคำบ่งชี้ตรง ๆ ⇒ หยิบวันที่ถัดไป = ตัวที่ลูกค้าบอกว่าผิด
            //   คำชี้พวกนี้ไม่บอกว่า "ของใคร" ⇒ ประโยคก่อนหน้าต้องไม่เอ่ยถึงคนอื่น ("วันเกิดแฟน ที่ถูกคือ …")
            ['(?:ที่ถูก(?:ต้อง)?(?:\s*คือ)?|ต้องเป็น|(?:แก้|เปลี่ยน)(?:ไข)?\s*เป็น)', true],
            // ขึ้นต้นข้อความด้วย "วันเกิด / เกิด" (คนไทยละสรรพนาม — "เกิด 27/6/2521 ค่ะ")
            ['^'.self::BIRTHDAY, false],
            ['^'.self::BORN, false],
        ];

        $cues = [];
        foreach ($patterns as [$p, $noOtherBefore]) {
            if (! preg_match_all('/'.$p.'/u', $t, $m, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($m[0] as [$match, $offset]) {
                // "แฟนหนูเกิด" · "แม่ของหนูเกิด" — สรรพนามนี้เป็นเจ้าของ ไม่ใช่คนเกิด
                //   คำเรียกญาติที่ "เว้นวรรค" แล้วค่อยเป็นสรรพนาม = เรียกคนฟัง ("แม่ หนูเกิด…") ⇒ ไม่ตัด
                //   (หลักเดียวกับ ThaiProvinces::BIRTH_THIRD_PARTY_TAIL)
                $before = mb_substr(substr($t, 0, $offset), -30);
                if (preg_match('/'.self::KIN.self::KIN_MOD.'?(?:\s*ของ\s*)?$/u', $before)) {
                    continue;
                }
                if ($noOtherBefore && preg_match('/'.self::KIN.'/u', mb_substr(substr($t, 0, $offset), -40))) {
                    continue;
                }

                // หลายแพทเทิร์นเริ่มตำแหน่งเดียวกัน ("^วันเกิด" กับ "วันเกิดที่ถูก") — เก็บตัวที่ยาวสุด
                $end = $offset + strlen($match);
                if (! isset($cues[$offset]) || $end > $cues[$offset][1]) {
                    $cues[$offset] = [$offset, $end];
                }
            }
        }

        ksort($cues);

        return array_values($cues);
    }

    /**
     * สิ่งที่เหลือหลังตัดข้อมูลเกิดทั้งหมดออก — '' = ข้อความนี้มีแต่ข้อมูลเกิด
     *
     * @param  array<int, array{ymd: string, start: int, end: int}>  $dates
     */
    private static function residual(string $t, array $dates): string
    {
        // ตัดวันที่ออกจากท้ายไปหน้า (ออฟเซ็ตไม่เลื่อน)
        foreach (array_reverse($dates) as $d) {
            $t = substr($t, 0, $d['start']).' '.substr($t, $d['end']);
        }

        // เวลาเกิด (ตัวเลข + นาฬิกาไทย + ช่วงของวัน)
        $t = (string) preg_replace([
            '/\d{1,2}\s*[:.]\s*\d{2}\s*(?:น\.?|นาฬิกา)?/u',
            '/(?:ตี|บ่าย)\s*\d{1,2}(?:\s*โมง)?/u',
            '/\d{1,2}\s*(?:น\.|นาฬิกา|โมง(?:เช้า|เย็น)?|ทุ่ม)/u',
            '/(?:ตี|บ่าย)?\s*(?:สิบเอ็ด|สิบสอง|หนึ่ง|สอง|สาม|สี่|ห้า|หก|เจ็ด|แปด|เก้า|สิบ|นึง)\s*(?:โมง(?:เช้า|เย็น)?|ทุ่ม)/u',
            '/ตี\s*(?:หนึ่ง|สอง|สาม|สี่|ห้า|หก)/u',
            '/เที่ยงคืน|เที่ยงวัน|เที่ยง|เช้าตรู่|เช้ามืด|เช้า|สาย|บ่ายโมง|บ่าย|เย็น|หัวค่ำ|ค่ำ|ดึก|กลางคืน|กลางวัน|ย่ำรุ่ง|ย่ำค่ำ|ครึ่ง/u',
        ], ' ', $t);

        // จังหวัด/ชื่อเรียก
        $t = ThaiProvinces::stripNames($t);

        // คำเชื่อม/คำบอกว่าเป็นข้อมูลเกิด/สรรพนามตัวเอง/คำลงท้าย/วันในสัปดาห์/ปีนักษัตร
        $t = (string) preg_replace(
            '/วันเดือนปีเกิด|วันเกิด|เวลาเกิด|จังหวัดเกิด|บ้านเกิด|เกิด|คลอด|วันที่|วัน|เดือน|ปี|พ\.?\s*ศ\.?|ค\.?\s*ศ\.?'
            .'|ที่ถูกต้อง|ที่ถูก|ถูกต้อง|แท้จริง|จริงๆ|จริง|ที่|ใน|จังหวัด|จ\.|อำเภอ|อ\.|เมือง|เวลา|ตอน|ช่วง|ประมาณ|ราวๆ|ราว|น่าจะ'
            .'|คือ|เป็น|ของ|แก้ไข|แก้|เปลี่ยน|อัพเดท|อัปเดต|ใหม่|ขอ|ด้วย|แล้ว|เลย|น่ะ|นะ+|จ๊ะ|จ้ะ+|จ้า+|ค่ะ+|คะ+|ค่า+'
            .'|คร้า+|ค้าบ|ค้า+|คับ+|ครับผม|ครับ+|ฮะ|ฮ่ะ|แม่หมอ'
            .'|'.self::SELF_PRONOUN
            .'|อาทิตย์|จันทร์|จันทร|อังคาร|พุธ|พฤหัสบดี|พฤหัส|ศุกร์|ศุกร|เสาร์|เสาร'
            .'|ชวด|ฉลู|ขาล|เถาะ|มะโรง|มะเส็ง|มะเมีย|มะแม|วอก|ระกา|จอ|กุน/u',
            ' ',
            $t
        );

        // เครื่องหมาย/ตัวเลขเศษ/อีโมจิ/ไม้ยมก/ช่องว่าง (\p{M} = สระ/วรรณยุกต์ที่หลุดเดี่ยว ๆ จากการตัดคำ)
        $t = (string) preg_replace('/[\s\p{P}\p{S}\p{M}\dๆฯ]+/u', '', $t);

        return $t;
    }
}
