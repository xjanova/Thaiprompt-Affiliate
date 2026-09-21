<?php

namespace App\Services\Fortune;

/**
 * 📅 บล็อก "ปฏิทินโหรไทย" สำหรับพรอมต์แม่หมอ — ป้อนวัน/เวลามงคลที่คำนวณจริงจาก ThaiRuekYam
 *
 * ทำไมต้องมี (2026-09-21): พรอมต์หลายจุดสั่งให้แม่หมอพูดเรื่องฤกษ์ แต่ไม่มีตัวเลขจริงป้อนให้เลย
 *   ⇒ ลูกค้าถาม "ออกรถวันไหนดี" แล้ว AI เดาวันเอง · บทสรุป 99 เขียนย่อหน้า "วันมงคล" ทุกบิลโดยไม่มีปฏิทิน
 *
 * จุดที่ใช้:
 *   - forCustomerText()  — ลูกค้าถามฤกษ์เอง (ดูดวง 39 ต่อคำถาม · คุยต่อ Pro Session · Celtic ถามตอบ)
 *   - generalBlock()     — บทสรุปบิล 99 (ย่อหน้าวันมงคลมีทุกบท ไม่ว่าลูกค้าจะถามหรือไม่)
 *   - todayLine()        — บรรทัด "วันนี้ตามปฏิทินหลวง" ใน {thai_context} (แทนวันพระที่เคยเดาจากวันที่สากล)
 *
 * ⚠️ กฎด่านตรวจ (บทเรียน celticTopicContext 2026-09-04): ตรวจเฉพาะ "ข้อความที่ลูกค้าพิมพ์"
 *    ห้ามส่งคำตอบ AI เข้ามา — พื้นดวงพูดถึง "ฤกษ์ดี" เอง จะทำให้บล็อกยิงทุกเทิร์น
 * ⚠️ คีย์เวิร์ดทุกตัวเช็คแล้วว่าไม่ใช่ส่วนย่อยของคำฮิต (รู้สึก → ห้ามใช้ "สึก" · วันดีคืนดี → ไม่นับ)
 */
final class AuspiciousTimingDirective
{
    /**
     * คีย์เวิร์ดงาน → หมวดใน ThaiRuekYam::ACTIVITIES — ตรวจตามลำดับ ตัวแรกที่เจอชนะ
     *   ลำดับสำคัญ: ตั้งศาล ก่อน บ้าน · หมั้น ก่อน แต่ง · ลาบวช ก่อน บวช · สร้างบ้าน ก่อน บ้านใหม่ · ขึ้นบ้าน ก่อน ทำบุญ
     */
    public const ACTIVITY_KEYWORDS = [
        'shrine' => ['ตั้งศาล', 'ศาลพระภูมิ', 'ยกศาล', 'ย้ายศาล', 'ศาลเจ้าที่', 'ศาลตายาย'],
        'engage' => ['หมั้น', 'สู่ขอ'],
        // ⚠️ ห้ามใส่ 'สมรส' เปล่า ๆ — ชน "คู่สมรส" (ลูกค้าเล่าเรื่องสามีภรรยา ไม่ได้จะแต่งงาน)
        'wedding' => ['แต่งงาน', 'งานแต่ง', 'ฤกษ์แต่ง', 'จดทะเบียนสมรส', 'จดทะเบียนแต่งงาน', 'งานสมรส', 'วิวาห์', 'ส่งตัวเจ้าสาว', 'ส่งตัวเข้าหอ'],
        'lasikkha' => ['ลาสิกขา', 'สึกพระ', 'ลาบวช'],
        'ordain' => ['อุปสมบท', 'บรรพชา', 'บวช'],
        'funeral' => ['งานศพ', 'เผาศพ', 'ฌาปนกิจ', 'สวดอภิธรรม', 'ฝังศพ'],
        'pillar' => ['เสาเอก', 'ปลูกบ้าน', 'สร้างบ้าน', 'ปลูกเรือน', 'เสาเข็ม', 'ก่อสร้าง', 'สร้างตึก'],
        'house' => ['ขึ้นบ้านใหม่', 'เข้าบ้านใหม่', 'บ้านใหม่', 'ขึ้นคอนโด', 'เข้าคอนโด', 'คอนโดใหม่'],
        'merit' => ['ทำบุญบ้าน', 'เลี้ยงพระ', 'นิมนต์พระ', 'ทำบุญ'],
        'worship' => ['บวงสรวง', 'ไหว้ครู', 'ครอบครู', 'บูชาเทพ', 'บูชาครู'],
        'car' => ['ออกรถ', 'รถใหม่', 'ถอยรถ', 'ซื้อรถ', 'รับรถ'],
        'shop' => ['เปิดร้าน', 'เปิดกิจการ', 'เปิดบริษัท', 'เปิดธุรกิจ', 'เปิดสาขา', 'เปิดคลินิก', 'เปิดออฟฟิศ', 'เริ่มกิจการ', 'จดทะเบียนบริษัท', 'เปิดบูธ'],
        'travel' => ['ออกเดินทาง', 'เดินทางไกล', 'ฤกษ์เดินทาง', 'ไปต่างประเทศ', 'ย้ายไปต่างประเทศ'],
        'topknot' => ['โกนจุก', 'โกนผมไฟ', 'โกนผมเด็ก'],
        'land' => ['ซื้อที่ดิน', 'ขายที่ดิน', 'โอนที่ดิน', 'ที่ดิน', 'โอนบ้าน', 'ทำสัญญา', 'เซ็นสัญญา', 'รังวัด'],
        'plant' => ['ปลูกต้นไม้', 'เพาะปลูก', 'ลงกล้า', 'ดำนา', 'หว่านข้าว', 'ปลูกผัก'],
    ];

    /** เอ่ยศัพท์ฤกษ์ตรง ๆ — นับเสมอ (ใช้ได้แม้ไม่บอกงาน → ให้ฤกษ์งานมงคลทั่วไป) */
    private const EXPLICIT_INTENT = '/ฤกษ์|วันมงคล/u';

    /**
     * ถามหา "วัน/ช่วง/เวลาที่ดี" — นับเมื่อไม่ใช่คำถามทำนาย
     * ⚠️ (จับผี 2026-09-21) เดิมนับ "วันดี/วันที่ดี" ด้วย ⇒ "ชีวิตจะมีวันที่ดีขึ้นไหม" / "ขอให้มีวันดี ๆ" ได้ปฏิทินฤกษ์
     */
    private const GOOD_TIME_INTENT = '/(?:วัน|ช่วง|เดือน|เวลา)(?:ไหน|ใด)(?:ที่)?(?:ดี|เหมาะ|สะดวก)|เมื่อไ(?:หร่|ร)(?:ดี|เหมาะ)|กี่โมงดี/u';

    /**
     * ถามเวลาแบบกว้าง — นับเฉพาะเมื่อ "ข้อความเดียวกัน" บอกงานด้วย และไม่ใช่คำถามทำนาย
     * ⚠️ (จับผี 2026-09-21) เดิมมี `ควร(ทำ|ไป|ย้าย…)` ⇒ "คู่สมรสนอกใจ ควรทำยังไงดี" ได้ฤกษ์แต่งงาน — ตัดทิ้ง
     */
    private const WEAK_INTENT = '/(?:วัน|ช่วง|เดือน|เวลา)(?:ไหน|ใด)|กี่โมง/u';

    /** สัญญาณคำถามทำนาย (ไม่ใช่ถามฤกษ์) — "จะได้แต่งงานช่วงไหน" / "งานจะดีขึ้นช่วงไหน" = ถามว่าจะเกิดเมื่อไหร่ */
    private const PREDICTION_MARKER = '/จะได้|จะมี|มีโอกาส|ได้แต่ง|จะเจอ|ได้เจอ|ดวงจะ|จะดีขึ้น|จะเข้า|จะกลับ|จะหาย|จะรวย|ถูกหวย/u';

    /** ข้อความตามต่อที่บอกแค่เดือน ("ขอเดือนธันวาค่ะ") ต้องสั้นกว่านี้ — กันข้อความเล่าเรื่องยาวที่บังเอิญมีชื่อเดือน */
    private const MONTH_FOLLOWUP_MAX_CHARS = 40;

    /** ชื่อเดือนสากลในข้อความลูกค้า → เลขเดือน (ใช้รูปที่ไม่ชนชื่อราศี: พฤษภา ≠ ราศีพฤษภ) */
    private const MONTH_WORDS = [
        1 => ['มกรา', 'ม.ค.'], 2 => ['กุมภา', 'ก.พ.'], 3 => ['มีนา', 'มี.ค.'], 4 => ['เมษา', 'เม.ย.'],
        5 => ['พฤษภา', 'พ.ค.'], 6 => ['มิถุนา', 'มิ.ย.'], 7 => ['กรกฎา', 'ก.ค.'], 8 => ['สิงหา', 'ส.ค.'],
        9 => ['กันยา', 'ก.ย.'], 10 => ['ตุลา', 'ต.ค.'], 11 => ['พฤศจิกา', 'พ.ย.'], 12 => ['ธันวา', 'ธ.ค.'],
    ];

    private const TH_MONTH_SHORT = [1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'];

    private const WD_SHORT = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];

    /** ดาวทักษา → วันในสัปดาห์ (ราหู = พุธกลางคืน ไม่กระทบฤกษ์กลางวัน จึงไม่อยู่ในตาราง) */
    private const PLANET_WD = ['อาทิตย์' => 0, 'จันทร์' => 1, 'อังคาร' => 2, 'พุธ' => 3, 'พฤหัสบดี' => 4, 'ศุกร์' => 5, 'เสาร์' => 6];

    /** วันที่ต้องหาฤกษ์ล่วงหน้า — งานทั่วไป / เมื่อบอกงาน */
    private const HORIZON_GENERAL = 30;

    private const HORIZON_ACTIVITY = 90;

    private ThaiRuekYam $engine;

    public function __construct(?ThaiRuekYam $engine = null)
    {
        $this->engine = $engine ?? new ThaiRuekYam;
    }

    // ─────────────────────────────── ตรวจเจตนา ───────────────────────────────

    /** หมวดงานที่ลูกค้าพูดถึง (null = ไม่พูดถึงงานเฉพาะ) */
    public static function detectActivity(string $text): ?string
    {
        $t = mb_strtolower($text);
        foreach (self::ACTIVITY_KEYWORDS as $key => $words) {
            foreach ($words as $w) {
                if (mb_strpos($t, $w) !== false) {
                    return $key;
                }
            }
        }

        return null;
    }

    /**
     * ข้อความนี้ถามหาฤกษ์ไหม
     *
     * @param  bool  $hasActivity  ข้อความ "เดียวกันนี้" บอกงานมาด้วย (ผ่อนเกณฑ์ให้คำถามเวลาแบบกว้าง)
     */
    public static function asksForTiming(string $text, bool $hasActivity): bool
    {
        if (preg_match(self::EXPLICIT_INTENT, $text) === 1) {
            return true;
        }
        if (preg_match(self::PREDICTION_MARKER, $text) === 1) {
            return false; // ถามว่าจะเกิดเมื่อไหร่ = งานทำนาย ไม่ใช่หาฤกษ์
        }
        if (preg_match(self::GOOD_TIME_INTENT, $text) === 1) {
            return true;
        }

        return $hasActivity && preg_match(self::WEAK_INTENT, $text) === 1;
    }

    /**
     * เดือนเป้าหมายที่ลูกค้าเอ่ยถึง (1–12) หรือ 0 = "เดือนหน้า" · null = ไม่เอ่ย
     *
     * ⚠️ (จับผี 2026-09-21) ไม่นับเดือนที่เป็นส่วนของวันที่เต็ม/วันเกิด —
     *    "เกิด 12 มีนาคม 2530 อยากออกรถ วันไหนดี" เคยได้ฤกษ์เดือนมีนาคมปีหน้า (กระโดดไป 6 เดือน)
     */
    public static function detectTargetMonth(string $text): ?int
    {
        $alt = implode('|', array_map(
            static fn (string $w): string => preg_quote($w, '/'),
            array_merge(...array_values(self::MONTH_WORDS))
        ));
        // วันที่เต็ม "12 มีนาคม 2530" / "12 มี.ค. 30" · และ "เกิดเดือนมีนา" = เดือนเกิด ไม่ใช่เดือนที่อยากได้ฤกษ์
        $clean = (string) preg_replace('/\d{1,2}\s*(?:'.$alt.')\S*\s*\d{2,4}/u', ' ', $text);
        $clean = (string) preg_replace('/เกิด\S{0,8}(?:'.$alt.')\S*/u', ' ', $clean);

        if (mb_strpos($clean, 'เดือนหน้า') !== false) {
            return 0;
        }
        foreach (self::MONTH_WORDS as $m => $words) {
            foreach ($words as $w) {
                if (mb_strpos($clean, $w) !== false) {
                    return $m;
                }
            }
        }

        return null;
    }

    // ─────────────────────────────── ดวงเจ้าของงาน ───────────────────────────────

    /**
     * วันกาลกิณี/วันดีของเจ้าของงานจากทักษา — ใช้ตัดวันกาลกิณีออกจากรายการฤกษ์
     * (ตำราหาฤกษ์: เลี่ยงวันกาลกิณีของเจ้าของงาน · GenLotto ไม่มีดวงบุคคลจึงไม่ได้ทำ แม่หมอมีจึงทำได้)
     *
     * @return array{kala_planet:string, kala_wd:?int, good_wd:array<int,string>}|null null = ไม่รู้วันเกิด
     */
    public static function personFromBirth(?\DateTimeInterface $birthDate, ?float $birthHour = null): ?array
    {
        if ($birthDate === null) {
            return null;
        }
        try {
            $astro = new ThaiAstrologyService;
            $dow = $astro->thaiWeekday((int) $birthDate->format('w'), $birthHour);
            $thaksa = $astro->getThaksa($dow, $birthHour);
            $kala = (string) ($thaksa[7]['planet'] ?? '');
            if ($kala === '') {
                return null;
            }
            $good = [];
            foreach ([2, 3, 6] as $i) { // เดช ศรี มนตรี
                $p = (string) ($thaksa[$i]['planet'] ?? '');
                if (isset(self::PLANET_WD[$p])) {
                    $good[self::PLANET_WD[$p]] = (string) ($thaksa[$i]['name'] ?? '');
                }
            }

            return ['kala_planet' => $kala, 'kala_wd' => self::PLANET_WD[$kala] ?? null, 'good_wd' => $good];
        } catch (\Throwable $e) {
            return null; // ผูกทักษาไม่ได้ → ให้ฤกษ์ทั่วไปอย่างเดียว ดีกว่าตัดวันผิด
        }
    }

    // ─────────────────────────────── บล็อกพรอมต์ ───────────────────────────────

    /**
     * บล็อกเมื่อลูกค้าถามฤกษ์เอง — '' ถ้าไม่ได้ถาม
     *
     * @param  string  $currentText  ข้อความลูกค้าเทิร์นนี้ (ตัดสินว่า "ถามฤกษ์ไหม")
     * @param  string  $recentText  ข้อความลูกค้าก่อนหน้า (หางานที่คุยค้างไว้ เช่น "จะออกรถ" แล้วถาม "วันไหนดี")
     * @param  array|null  $person  ผลจาก personFromBirth()
     */
    public function forCustomerText(string $currentText, string $recentText = '', ?array $person = null, ?\DateTimeInterface $now = null): string
    {
        $currentActivity = self::detectActivity($currentText);
        $recentActivity = $recentText !== '' ? self::detectActivity($recentText) : null;
        // เดือนเป้าหมายอ่านจาก "ข้อความเทิร์นนี้" เท่านั้น — "เดือนหน้า" ที่พูดเรื่องอื่นเมื่อ 2 เทิร์นก่อนห้ามมาดันช่วงค้นหา
        $month = self::detectTargetMonth($currentText);

        $asks = self::asksForTiming($currentText, $currentActivity !== null);

        // 🔁 ตามต่อจากเทิร์นก่อนที่ถามฤกษ์ไว้แล้ว: "ขอเดือนธันวาค่ะ" — ต้องทำได้จริง เพราะบล็อกบอกลูกค้าว่าบอกเดือนมาได้
        //    (กฎ "บอทสัญญาอะไร ต้องมีโค้ดทำจริง")
        if (! $asks && $month !== null && $recentText !== ''
            && mb_strlen(trim($currentText)) <= self::MONTH_FOLLOWUP_MAX_CHARS
            && preg_match(self::PREDICTION_MARKER, $currentText) !== 1
            && self::asksForTiming($recentText, $recentActivity !== null)) {
            $asks = true;
        }

        if (! $asks) {
            return '';
        }

        return $this->build($currentActivity ?? $recentActivity ?? 'general', $person, $now, $month);
    }

    /**
     * บล็อกปฏิทินโหรทั่วไป — บทสรุป 99 ใช้เสมอ (ย่อหน้าวันมงคลมีทุกบท)
     * ถ้าคำถามทั้งบิลถามฤกษ์งานเฉพาะ ให้ฤกษ์งานนั้นแทนงานมงคลทั่วไป
     */
    public function generalBlock(string $allCustomerText = '', ?array $person = null, ?\DateTimeInterface $now = null): string
    {
        $activity = self::detectActivity($allCustomerText);
        $act = ($activity !== null && self::asksForTiming($allCustomerText, true)) ? $activity : 'general';

        return $this->build($act, $person, $now, $act === 'general' ? null : self::detectTargetMonth($allCustomerText));
    }

    /**
     * บรรทัดวันนี้ตามปฏิทินหลวง + วันพระ — แทนการเดาวันพระจากวันที่สากล 8/15/23/30
     * พูดถึงวันพระเฉพาะ "วันนี้" หรือ "พรุ่งนี้" (≈ 8 วัน/เดือน — ของเดิมโผล่ 4 วัน/เดือน)
     *   ⚠️ (จับผี 2026-09-21) เดิมบอกล่วงหน้า 3 วัน ⇒ คำว่าวันพระโผล่ ~16 วัน/เดือน
     *      AI จะแปะ "ทำบุญวันพระ" ท้ายคำทำนายเป็นของแถมเกือบทุกบิล
     * '' = คำนวณไม่ได้ (ไม่มีข้อมูลปฏิทินหลวง)
     */
    public static function todayLine(?\DateTimeInterface $now = null): string
    {
        $today = self::localDate($now);
        $L = ThaiLunarCalendar::of($today);
        if ($L === null) {
            return '';
        }
        $line = '- วันนี้ตามปฏิทินหลวง: '.$L['label'].' ปี'.$L['animal'];
        if ($L['holy']) {
            return $line.' — 🪷 *วันนี้วันพระ* เหมาะทำบุญ ใส่บาตร ถือศีล';
        }
        $tomorrow = $today->modify('+1 day');
        $x = ThaiLunarCalendar::of($tomorrow);
        if ($x !== null && $x['holy']) {
            return $line.' · พรุ่งนี้ ('.self::thaiShortDate($tomorrow).') เป็นวันพระ '.$x['label'];
        }

        return $line;
    }

    /** ประกอบบล็อกจริง */
    private function build(string $act, ?array $person, ?\DateTimeInterface $now, ?int $targetMonth): string
    {
        try {
            $today = self::localDate($now);
            $A = ThaiRuekYam::ACTIVITIES[$act] ?? ThaiRuekYam::ACTIVITIES['general'];

            // ช่วงที่หา: เริ่มพรุ่งนี้ (ช่วงเวลาของวันนี้อาจเลยไปแล้ว) · ถ้าลูกค้าเอ่ยเดือน เริ่มต้นเดือนนั้น
            $start = $today->modify('+1 day');
            $horizon = $act === 'general' ? self::HORIZON_GENERAL : self::HORIZON_ACTIVITY;
            if ($targetMonth !== null) {
                $first = $targetMonth === 0
                    ? $today->modify('first day of next month')
                    : $today->setDate((int) $today->format('Y'), $targetMonth, 1);
                if ($first < $today->modify('first day of this month')) {
                    $first = $first->modify('+1 year');
                }
                if ($first > $start) {
                    $start = $first;
                }
                $horizon = 45;
            }

            $found = $this->engine->findDays($act, $start->format('Y-m-d'), $horizon);

            // ตัดวันกาลกิณีของเจ้าของงาน (เฉพาะงานมงคล — งานศพ/เดินทางไม่ใช้เกณฑ์นี้)
            $kalaWd = ($A['mongkol'] ?? false) ? ($person['kala_wd'] ?? null) : null;
            $dropped = 0;
            if ($kalaWd !== null) {
                $before = count($found);
                $found = array_values(array_filter($found, static fn (array $f): bool => $f['D']['wd'] !== $kalaWd));
                $dropped = $before - count($found);
            }

            $max = $act === 'general' ? 4 : 5;
            // เรียง: วันที่มีช่วง ★ (ฤกษ์บนตรงงาน/ยามลัคนาตกธงชัย-อธิบดี) มากก่อน แล้วตามวันที่
            $picked = array_slice($found, 0, 12);
            usort($picked, static fn (array $a, array $b): int => [$b['stars'] > 0, $a['ymd']] <=> [$a['stars'] > 0, $b['ymd']]);
            $picked = array_slice($picked, 0, $max);
            usort($picked, static fn (array $a, array $b): int => strcmp($a['ymd'], $b['ymd']));

            $out = "━━━━━━━━━━━━━━━━━\n"
                ."📅 ปฏิทินโหรไทย — คำนวณจริงจากตำรา (ปฏิทินหลวง · กาลโยค · ฤกษ์บน · ยามอัฏฐกาล)\n"
                ."━━━━━━━━━━━━━━━━━\n";

            $todayLine = self::todayLine($today);
            if ($todayLine !== '') {
                $out .= '•'.mb_substr($todayLine, 1)."\n";
            }
            $out .= '• '.$this->kalayokLine($today)."\n";

            $label = $act === 'general' ? 'วันมงคลทั่วไป' : 'ฤกษ์'.$A['label'];
            $range = self::thaiShortDate($start).' – '.self::thaiShortDate($start->modify('+'.($horizon - 1).' day'));
            $out .= "• {$label} ช่วง {$range}";
            if ($dropped > 0) {
                $out .= ' (ตัดวัน'.ThaiRuekYam::WD[$kalaWd].' ซึ่งเป็นวันกาลกิณีของผู้ถามออกแล้ว)';
            }
            $out .= ":\n";

            // ลูกค้าเอ่ยเดือนมา แต่เดือนนั้นไม่มีวันผ่านเกณฑ์เลย → ต้องบอกตรง ๆ ไม่ใช่ปล่อยให้ AI เข้าใจว่าวันเดือนถัดไปคือเดือนที่ถาม
            if ($targetMonth !== null && $targetMonth !== 0 && $picked !== []) {
                $inMonth = array_filter($picked, static fn (array $f): bool => (int) substr($f['ymd'], 5, 2) === $targetMonth);
                if ($inMonth === []) {
                    $out .= '  ⚠️ เดือน'.self::TH_MONTH_SHORT[$targetMonth].' ที่ลูกถาม *ไม่มีวันผ่านเกณฑ์ตำราเลย* — บอกลูกตรง ๆ แล้วเสนอวันใกล้ที่สุดด้านล่างแทน'."\n";
                }
            }
            if ($picked === []) {
                $out .= "  - ไม่มีวันที่ผ่านเกณฑ์ตำราในช่วงนี้ — บอกลูกตรง ๆ ว่าช่วงนี้ตำราไม่ให้ฤกษ์ ควรเลื่อนออกไป\n";
            }
            foreach ($picked as $f) {
                $out .= '  - '.$this->dayLine($f, $act, $person)."\n";
            }
            if ($picked !== [] && ($A['mongkol'] ?? false)) {
                $out .= "    ★ = ช่วงที่ฤกษ์บนตรงงาน หรือยาม/ลัคนาตกธงชัย-อธิบดี (ดีที่สุดในวันนั้น)\n";
            }

            $rules = $this->rulesLine($act);
            if ($rules !== '') {
                $out .= "• หลักตำรางานนี้: {$rules}\n";
            }
            if (($A['mongkol'] ?? false) && $person === null) {
                $out .= "• ยังไม่รู้วันเกิดเจ้าของงาน จึงยังไม่ได้ตัดวันกาลกิณีของเขาออก — ถ้าลูกบอกวันเกิด ให้เลี่ยงวันกาลกิณีด้วย\n";
            }

            $out .= "กติกาการใช้บล็อกนี้:\n"
                ."• วัน-เวลาฤกษ์ที่บอกลูก *ต้องมาจากรายการนี้เท่านั้น* — ❌ ห้ามแต่งวันหรือเวลาเอง ❌ ห้ามขยับเวลา\n"
                ."• บอกเป็นภาษาคนทั่วไป: วัน เวลา และเหตุผลสั้น ๆ (เช่น \"ตรงดิถีสิทธิโชค\") — ไม่ต้องไล่ศัพท์คำนวณ เว้นลูกถาม\n"
                ."• เวลาคิดที่กรุงเทพฯ — ต่างจังหวัดคลาดไม่กี่นาที ใช้ได้ · นี่คือฤกษ์ตามตำราไทย ไม่ใช่การรับประกันผล\n"
                ."• ถ้าลูกอยากได้ช่วงอื่นนอกรายการ ชวนให้พิมพ์ \"ขอฤกษ์".($act === 'general' ? '' : 'งานนี้')."เดือน…\" มาได้ (ระบบจะเปิดปฏิทินเดือนนั้นให้) — ❌ ห้ามเดาวันของเดือนอื่นเอง\n";

            return $out."\n";
        } catch (\Throwable $e) {
            return ''; // คำนวณล้ม → ไม่ป้อนอะไร ดีกว่าป้อนวันผิด
        }
    }

    /** บรรทัดกาลโยคของปีที่ครอบวันนี้ */
    private function kalayokLine(\DateTimeImmutable $today): string
    {
        $cs = ThaiRuekYam::csAt(ThaiRuekYam::jdAt($today->format('Y-m-d'), 24) - 1e-7);
        $ky = ThaiRuekYam::kalayok($cs);
        $wd = static fn (string $k): string => 'วัน'.ThaiRuekYam::WD[$ky[$k]['wan'] - 1];
        $thalerng = ThaiRuekYam::thalerngsok($cs) + 7 / 24; // เป็นเวลาไทย
        $thalerngDate = (new \DateTimeImmutable('@'.(int) round(($thalerng - 2440587.5) * 86400)))->setTimezone(new \DateTimeZone('UTC'));

        $line = "กาลโยคปีนี้ (จ.ศ. {$cs} เถลิงศก ".self::thaiShortDate($thalerngDate).'): '
            .'ธงชัย = '.$wd('thongchai').' · อธิบดี = '.$wd('athibodi')
            .' · อุบาทว์ = '.$wd('ubat').' · โลกาวินาศ = '.$wd('lokawinat');

        // วันดีที่ตรงวันร้ายด้วย = ข้อห้ามชนะ (เช่น จ.ศ. 1388 วันจันทร์เป็นทั้งธงชัยและโลกาวินาศ)
        $clash = [];
        foreach (['thongchai', 'athibodi'] as $g) {
            foreach (['ubat', 'lokawinat'] as $b) {
                if ($ky[$g]['wan'] === $ky[$b]['wan']) {
                    $clash[] = $wd($g).'เป็นทั้ง'.$ky[$g]['th'].'และ'.$ky[$b]['th'];
                }
            }
        }
        if ($clash !== []) {
            $line .= ' — ⚠️ '.implode(' · ', $clash).' ⇒ ไม่นับเป็นวันมงคล (ข้อห้ามชนะ)';
        }

        return $line;
    }

    /** บรรทัดของวันหนึ่งในรายการ */
    private function dayLine(array $f, string $act, ?array $person): string
    {
        $D = $f['D'];
        $date = new \DateTimeImmutable($f['ymd']);
        $line = self::WD_SHORT[$D['wd']].' '.self::thaiShortDate($date).' ('.$D['lunar']['label'].')';

        $wins = array_slice($f['wins'], 0, 3);
        if ($wins !== []) {
            $line .= ' เวลา '.implode(' · ', array_map(
                static fn (array $w): string => $w['from_hm'].'–'.$w['to_hm'].($w['star'] ? '★' : ''),
                $wins
            ));
        }

        $why = array_values(array_unique(array_filter($f['goods'])));
        if (isset($person['good_wd'][$D['wd']])) {
            $why[] = 'ตรงวัน'.$person['good_wd'][$D['wd']].'ของผู้ถาม';
        }
        if ($why !== []) {
            $line .= ' · ดี: '.implode(', ', array_slice($why, 0, 4));
        }

        if ($act === 'travel') {
            $line .= ' · เลี่ยงทิศ'.ThaiRuekYam::DIR_LAOLEK[$D['wd']].' (หลาวเหล็ก)';
        }

        return $line;
    }

    /** สรุปหลักตำราของงานในบรรทัดเดียว */
    private function rulesLine(string $act): string
    {
        $A = ThaiRuekYam::ACTIVITIES[$act] ?? [];
        $parts = [];
        if (! empty($A['wd_ban_text'])) {
            $parts[] = $A['wd_ban_text'];
        } elseif ($A['mongkol'] ?? false) {
            $parts[] = 'งานมงคลเลี่ยงวันอังคารและวันเสาร์';
        }
        foreach ($A['dithi_ban'] ?? [] as $r) {
            $parts[] = 'ห้าม'.$r['text'];
        }
        if (! empty($A['months']['good'])) {
            $parts[] = 'เดือนจันทรคติที่นิยม: '.implode(' ', array_map(
                static fn (int $m): string => ThaiLunarCalendar::MONTH_TH[$m],
                $A['months']['good']
            ));
        }
        if (! empty($A['lfj_text'])) {
            $parts[] = $A['lfj_text'];
        }
        if (! empty($A['ruek_prefer'])) {
            $parts[] = 'ฤกษ์บนที่ตำราให้ใช้: '.implode(' ', array_map(
                static fn (int $i): string => ThaiRuekYam::RUEK9[$i]['short'],
                $A['ruek_prefer']
            ));
        }
        if ($act === 'general') {
            $parts[] = 'เลี่ยงดิถีมหาสูญ พิฆาต ทรธึก อายกรรมพลาย และวันอุบาทว์-โลกาวินาศของปี';
        }

        return implode(' · ', $parts);
    }

    /** วันนี้ตามเวลาไทย (ตัดเวลาทิ้ง) */
    private static function localDate(?\DateTimeInterface $now): \DateTimeImmutable
    {
        $tz = new \DateTimeZone('Asia/Bangkok');
        $n = $now !== null
            ? \DateTimeImmutable::createFromInterface($now)->setTimezone($tz)
            : new \DateTimeImmutable('now', $tz);

        return new \DateTimeImmutable($n->format('Y-m-d'));
    }

    /** "16 ต.ค. 2569" */
    private static function thaiShortDate(\DateTimeInterface $d): string
    {
        return (int) $d->format('j').' '.self::TH_MONTH_SHORT[(int) $d->format('n')].' '.((int) $d->format('Y') + 543);
    }
}
