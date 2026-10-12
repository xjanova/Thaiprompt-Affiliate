<?php

namespace App\Services\Fortune;

/**
 * 📈 กราฟชีวิต — มหาทักษา (ดาวเสวยอายุ) + ดาวแทรก + ทักษาจร → คะแนนรายปีอายุ 0–100 จากดวงกำเนิด
 *
 * 🔁 พอร์ตจาก GenLotto (site/app/Astro/LifeGraph.php · ที่มาตำรา docs/lifegraph.md ในรีโป genlotto) — ตรรกะเหมือนกันทุกบรรทัด
 *    ต่างแค่ผังดวงกำเนิดมาจาก ThaiAstrologyService::natalChartSnapshot() (ดวงเดียวกับรูปผัง/พรอมต์ของแม่หมอ)
 *    แก้ตำรา/น้ำหนักที่ GenLotto ก่อน แล้วพอร์ตตาม · LifeGraphTest เทียบผลกับค่าที่ GenLotto คำนวณ
 *
 * ตำรา (ใส่เฉพาะที่ ≥ 2 แหล่งตรงกัน):
 *   ดาวเสวยอายุ: เริ่มที่ดาวเจ้าวันเกิด เดินตามลำดับทักษา ช่วงละตามกำลังพระเคราะห์ (รวม 108 ปี)
 *   ดาวแทรก: ในช่วงดาวเสวยแต่ละช่วง ดาว 8 องค์แทรกตามลำดับทักษา เริ่มจากดาวเสวยเอง ("เสวยอายุตัวเอง")
 *     ยาว = ปีดาวเสวย × ปีดาวแทรก ÷ 108 ปี (ปีละ 12 เดือน เดือนละ 30 วัน วันละ 60 นาฑี)
 *     = 200 × ปีดาวเสวย × ปีดาวแทรก นาฑี ลงตัวเสมอ — horasatthai (สูตร) · พรหมชาติฉบับสมบูรณ์ (ตาราง) · ajanton (ตารางคนเกิดวันเสาร์)
 *   ทักษาจร: นับอายุย่างจากดาววันเกิดเป็น ๑ เดินตามทักษาปีละช่อง พ้นอาทิตย์แล้วเข้าตากลาง ๑ ปีก่อนไปจันทร์ (วง 9 ช่อง)
 *     ปีที่ตกตากลางใช้พฤหัสเป็นบริวารจร — หมอจุ๊บ (joop-astro 2554) · อ.แอน (sana-anong5 2558)
 *     คนเกิดพุธกลางคืน (ราหู): สองแหล่งไม่ตรงกัน ⇒ ไม่คำนวณ (OMITTED)
 *   คำพยากรณ์ (ดาวเสวยดี/ร้าย · ดาวแทรก 64 คู่ · ทักษาจรกาลี/ศรีจร · ความหมายภูมิ) อยู่ใน LifeGraphTexts พร้อมแหล่ง
 *   ภูมิทักษาของดาวเสวยตายตัวตามลำดับช่วง (ช่วงที่ n = ภูมิที่ n ของทุกคน) ⇒ ไม่ใช้ให้คะแนน (OMITTED)
 * คะแนน: WEIGHTS เป็นค่าน้ำหนักออกแบบ ไม่ใช่ตัวเลขจากตำรา — ทุกคะแนนมีเหตุผลภาษาไทยกำกับ (component + delta + reason)
 * ไม่ใช้ข้อความทำนายใน Horoscope.php (เขียนเองไม่มีแหล่ง)
 */
final class LifeGraph
{
    /** เปลี่ยนตำรา/น้ำหนัก ⇒ เพิ่มเลข (หน้าเว็บแสดงไว้ เทียบผลข้ามรุ่นได้) */
    public const VERSION = 3;

    /** 1 ปีในตำรามหาทักษา = 12 เดือน × 30 วัน × 60 นาฑี */
    public const NATHI_PER_YEAR = 21600;

    public const NATHI_PER_MONTH = 1800;

    public const NATHI_PER_DAY = 60;

    /** ตากลางในวงทักษาจร (ไม่ใช่เลขดาว) */
    public const CENTER = -1;

    /** วงทักษาจร 9 ช่อง: ลำดับทักษา + ตากลางหลังอาทิตย์ */
    public const JON_RING = [1, self::CENTER, 2, 3, 4, 7, 5, 8, 6];

    /** ปีตกตากลางใช้ดาวนี้เป็นบริวารจร (๕ พฤหัสบดี) */
    public const CENTER_BORIWAN = 5;

    /** ภพร้ายเมื่อรู้ลัคนา (ทุสถานะ: อริ มรณะ วินาศน์) */
    public const BAD_HOUSES = [6, 8, 12];

    /**
     * ⚖️ ค่าน้ำหนักออกแบบ — ไม่ใช่ตำรา (ตำราบอกเพียงว่าอะไรดีหรือร้าย ไม่ได้ให้ตัวเลข) · แก้ที่นี่ที่เดียว แล้วเพิ่ม VERSION
     *   main_* = ดาวเสวยอายุ (เต็มปี): main_nature = ตำราว่าช่วงดาวนั้นดี/ร้าย (LifeGraphTexts::PERIOD)
     *     · main_dignity / main_house_bad = ดาวเสวยในดวงเดิม (LifeGraphTexts::NATAL_RULE — sanook · bloggang)
     *   sub_* / pair / combo = ดาวแทรก คูณสัดส่วนเวลาที่แทรกในปีนั้น · combo = ตำราว่าคู่เสวย-แทรกดี/ร้าย (ผสม/ขัดกัน = 0)
     *     · pair (ดาวคู่ทั่วไป) ใช้เฉพาะคู่ที่ไม่มีคำทายเสวย-แทรก (SUB = null) — มีคำทายแล้วตำราเฉพาะคู่ชนะ (v3)
     *   jon = ทักษาจรปีอายุ: ดาวเสวยเป็นศรีจร (+) / กาลีจร (−) — ภูมิเดิมของดาวเสวยได้ผลตาม LifeGraphTexts::JON
     *   tone = ผลรวมคะแนนของดาวเสวย (ไม่รวมดาวแทรก) ≥ good → ช่วงดี · ≤ bad → ช่วงร้าย
     */
    public const WEIGHTS = [
        'base' => 50,
        'main_nature' => ['ดี' => 8, 'ร้าย' => -8],
        'main_dignity' => ['อุจ' => 12, 'เกษตร' => 10, 'ประ' => -8, 'นิจ' => -12],
        'main_house_bad' => -8,
        'sub_dignity' => ['อุจ' => 6, 'เกษตร' => 5, 'ประ' => -4, 'นิจ' => -6],
        'sub_house_bad' => -4,
        'pair' => ['คู่มิตร' => 6, 'คู่สมพล' => 6, 'คู่ศัตรู' => -6],
        'combo' => ['ดี' => 5, 'ร้าย' => -5],
        'jon' => ['ศรี' => 4, 'กาลกิณี' => -4],
        'tone' => ['good' => 8, 'bad' => -8],
    ];

    /** ตำราที่ยืนยันไม่ถึง 2 แหล่ง หรือแหล่งขัดกัน ⇒ ไม่คำนวณ (แสดงบนหน้าเว็บด้วย) */
    public const OMITTED = [
        ['item' => 'ทักษาจรของคนเกิดพุธกลางคืน (ราหู)', 'reason' => 'อ.แอนให้นับอายุจรจากพุธ ส่วนหมอจุ๊บบอกว่ามีสองแบบ (มีราหู/ไม่มีราหู) ให้ผลต่างกัน — ไม่ถึง 2 แหล่งที่ตรงกัน'],
        ['item' => 'ตัวดาวตากลาง (เกตุ หรือ พฤหัส)', 'reason' => 'pantip: บางท่านว่าเกตุ (๙) บางท่านว่าพฤหัส (๕) — ใช้เฉพาะข้อที่ 2 แหล่งตรงกัน คือปีตกตากลางใช้พฤหัสเป็นบริวารจร'],
        ['item' => 'คะแนนและคำทายจากภูมิทักษาของดาวเสวย/ดาวแทรก (เช่น ดาวเสวยเป็นกาลกิณี)', 'reason' => 'ภูมิของดาวเสวยตายตัวตามลำดับช่วง (ช่วงที่ 3 = เดช ช่วงที่ 8 = กาลกิณี ของทุกคน) กราฟทุกคนจะมีโครงเดียวกัน · ศรรวสาระบุว่าอ่านช่วงวัยจากชื่อภูมิไม่ได้ และไม่พบแหล่งใดทำนายแบบนี้'],
        ['item' => 'ดาวแทรก 5 คู่: เสาร์×ราหู · เสาร์×พุธ · พฤหัส×จันทร์ · ราหู×อาทิตย์ · ราหู×พฤหัส', 'reason' => 'ตำรา 3 ชุด (พรหมชาติ · horasatthai · วัดท่าซุง) ให้ผลขัดกัน — ไม่มีคำทำนายและไม่ให้คะแนน'],
        ['item' => 'ทักษาจร: ภูมิกาลกิณีเดิมเป็นศรีจร', 'reason' => 'bloggang ว่าได้ลาภ · หมอจุ๊บว่าเสียเงิน · สอ้านว่าไว้ใจไม่ได้ — ขัดกัน'],
        ['item' => 'รายละเอียดในคำทำนายดาวแทรก (ทิศ สีผิวคน ความตาย)', 'reason' => 'แต่ละฉบับไม่ตรงกัน ใช้เฉพาะใจความร่วม · เรื่องความตายเขียนเป็น "ระวังสุขภาพ"'],
        ['item' => 'กราฟชีวิตจากเลขวันเดือนปีเกิด (แบบเว็บดูดวง)', 'reason' => 'แต่ละเว็บใช้สูตรต่างกัน (ตาราง 12 ช่อง · เลขชะตา 1–9 กับดาวประจำปี · สเกลคะแนนต่างกัน) ไม่มี 2 แหล่งที่ตรงกัน'],
        ['item' => 'นับวัย ระบบ อ.พลูหลวง / ทักษาวัยระบบใหม่', 'reason' => 'อีกระบบหนึ่ง (ทศวรรษละดาว รวมเนปจูน พลูโต) มีแหล่งเดียว และตัวอย่างในหน้านั้นนับไม่สม่ำเสมอ'],
        ['item' => 'คะแนนบวกของภพดี (ศุภะ ลาภะ)', 'reason' => 'พบในบทความสายเดียว (pookazza → sanook/tnews/teenee) — ภพร้าย 6/8/12 มีทั้งสายนี้และ bloggang'],
        ['item' => 'ช่วงย่อยชั้นที่ 3 (แทรกซ้อนในดาวแทรก)', 'reason' => 'ไม่พบในแหล่งที่ตรวจ'],
    ];

    public const SOURCES = [
        ['topic' => 'ดาวแทรก: สูตรและลำดับ', 'title' => 'horasatthai — มหาทักษา ๘ องค์', 'url' => 'https://horasatthai.com/horasat/maha-taksa'],
        ['topic' => 'ดาวแทรก: ตารางทุกวันเกิด', 'title' => 'วิทยาทาน — มหาทักษา ตำราพรหมชาติฉบับสมบูรณ์', 'url' => 'https://sites.google.com/view/arnoon266/%E0%B8%AB%E0%B8%99%E0%B8%B2%E0%B9%81%E0%B8%A3%E0%B8%81/%E0%B8%A1%E0%B8%AB%E0%B8%B2%E0%B8%97%E0%B8%81%E0%B8%A9%E0%B8%B2-%E0%B8%A7%E0%B8%B2%E0%B8%94%E0%B8%A7%E0%B8%A2%E0%B8%81%E0%B8%B3%E0%B8%A5%E0%B8%87%E0%B9%80%E0%B8%97%E0%B8%A7%E0%B8%94%E0%B8%B2%E0%B9%80%E0%B8%AA%E0%B8%A7%E0%B8%A2%E0%B8%AD%E0%B8%B2%E0%B8%A2-%E0%B8%95%E0%B8%B3%E0%B8%A3%E0%B8%B2%E0%B8%9E%E0%B8%A3%E0%B8%AB%E0%B8%A1%E0%B8%8A%E0%B8%B2%E0%B8%95%E0%B8%89%E0%B8%9A%E0%B8%9A%E0%B8%AA%E0%B8%A1%E0%B8%9A%E0%B8%A3%E0%B8%93'],
        ['topic' => 'ดาวแทรก: ตารางคนเกิดวันเสาร์', 'title' => 'ajanton — คนเกิดวันเสาร์ พระเคราะห์เสวยอายุ', 'url' => 'https://ajanton.blogspot.com/2015/06/blog-post_1.html'],
        ['topic' => 'ทักษาจร (อายุย่าง · ตากลาง)', 'title' => 'หมอจุ๊บ — กลวิธีการนับทักษา', 'url' => 'http://joop-astro.blogspot.com/2011/07/blog-post_6023.html'],
        ['topic' => 'ทักษาจร (ตากลาง · พฤหัสเป็นบริวาร)', 'title' => 'อ.แอน — หลักทักษาในวิชาโหราศาสตร์ ตอนที่ ๓', 'url' => 'https://sana-anong5.blogspot.com/2015/11/blog-post_49.html'],
        ['topic' => 'ตากลางต่างสำนัก', 'title' => 'pantip — อายุจรตากลางและนับวัย', 'url' => 'https://pantip.com/topic/36493077'],
    ];

    public function __construct(private readonly ThaiAstrologyService $astro = new ThaiAstrologyService) {}

    /**
     * ช่วงดาวเสวยอายุตั้งแต่แรกเกิดจนครอบอายุ $untilAge (เกิน 108 ปีวนรอบใหม่)
     *
     * @return list<array{planet:int, from:int, to:int, years:int}>
     */
    public static function mainPeriods(int $birthLord, int $untilAge = 108): array
    {
        $order = LifeGraphTables::THAKSA_ORDER;
        $start = array_search($birthLord, $order, true);
        $start = $start === false ? 0 : $start;
        $out = [];
        for ($i = 0, $from = 0; $from < $untilAge; $i++) {
            $p = $order[($start + $i) % 8];
            $y = LifeGraphTables::PERIOD_YEARS[$p];
            $out[] = ['planet' => $p, 'from' => $from, 'to' => $from + $y, 'years' => $y];
            $from += $y;
        }

        return $out;
    }

    /**
     * ดาวแทรก 8 องค์ในช่วงดาวเสวย $main — offset/length เป็นนาฑีนับจากต้นช่วง
     *
     * @return list<array{planet:int, offset:int, length:int}>
     */
    public static function subPeriods(int $main): array
    {
        $order = LifeGraphTables::THAKSA_ORDER;
        $start = (int) array_search($main, $order, true);
        $unit = intdiv(self::NATHI_PER_YEAR, 108); // 200 นาฑี = 1/108 ปี
        $out = [];
        $off = 0;
        for ($i = 0; $i < 8; $i++) {
            $p = $order[($start + $i) % 8];
            $len = $unit * LifeGraphTables::PERIOD_YEARS[$main] * LifeGraphTables::PERIOD_YEARS[$p];
            $out[] = ['planet' => $p, 'offset' => $off, 'length' => $len];
            $off += $len;
        }

        return $out;
    }

    /** @return array{y:int, m:int, d:int, n:int, text:string} นาฑี → ปี เดือน วัน นาฑี แบบตำรา */
    public static function duration(int $nathi): array
    {
        $y = intdiv($nathi, self::NATHI_PER_YEAR);
        $r = $nathi % self::NATHI_PER_YEAR;
        $m = intdiv($r, self::NATHI_PER_MONTH);
        $r %= self::NATHI_PER_MONTH;
        $d = intdiv($r, self::NATHI_PER_DAY);
        $n = $r % self::NATHI_PER_DAY;
        $parts = array_filter([$y ? $y.' ปี' : '', $m ? $m.' เดือน' : '', $d ? $d.' วัน' : '', $n ? $n.' นาฑี' : '']);

        return ['y' => $y, 'm' => $m, 'd' => $d, 'n' => $n, 'text' => $parts ? implode(' ', $parts) : '0 วัน'];
    }

    /**
     * ทักษาจรของปีอายุย่าง $ageYang — null เมื่อเกิดพุธกลางคืน (ตำราไม่ตรงกัน)
     *
     * @return ?array{age_yang:int, center:bool, boriwan:int, bhava:array<int,string>, kalakini:int}
     */
    public static function thaksaJon(int $birthLord, int $ageYang): ?array
    {
        $start = array_search($birthLord, self::JON_RING, true);
        if ($birthLord === 8 || $start === false || $ageYang < 1) {
            return null;
        }
        $slot = self::JON_RING[($start + $ageYang - 1) % 9];
        $center = $slot === self::CENTER;
        $boriwan = $center ? self::CENTER_BORIWAN : $slot;
        $bhava = [];
        foreach (LifeGraphTables::thaksa($boriwan) as $row) {
            $bhava[$row['planet']] = $row['bhava'];
        }

        return ['age_yang' => $ageYang, 'center' => $center, 'boriwan' => $boriwan, 'bhava' => $bhava, 'kalakini' => (int) array_search('กาลกิณี', $bhava, true)];
    }

    /** คู่ของดาวสองดวงตามตำราดาวคู่ (คู่ธาตุไม่นับ — ๒-๕ เป็นทั้งคู่ธาตุและคู่ศัตรู) */
    public static function pairKind(int $a, int $b): ?string
    {
        foreach (['คู่มิตร' => LifeGraphTables::PAIR_FRIEND, 'คู่สมพล' => LifeGraphTables::PAIR_SOMPHON, 'คู่ศัตรู' => LifeGraphTables::PAIR_ENEMY] as $kind => $pairs) {
            foreach ($pairs as [$x, $y]) {
                if (($x === $a && $y === $b) || ($x === $b && $y === $a)) {
                    return $kind;
                }
            }
        }

        return null;
    }

    /**
     * @param  array  $profile  {name, birth_date ("Y-m-d" หรือ "Y-m-d H:i"), birth_hour (?float — เวลาเกิดที่ล็อกต่อบิล), birth_province (?string)}
     * @return array|null null = ผูกดวงไม่ได้ (วันเกิดอ่านไม่ออก) — ห้ามแต่งกราฟแทน
     */
    public function compute(array $profile, \DateTimeImmutable $now, int $maxAge = 100): ?array
    {
        $birthRaw = trim((string) ($profile['birth_date'] ?? ''));
        $hour = isset($profile['birth_hour']) ? (float) $profile['birth_hour'] : null;
        $province = isset($profile['birth_province']) && $profile['birth_province'] !== '' ? (string) $profile['birth_province'] : null;
        $snap = $birthRaw !== '' ? $this->astro->natalChartSnapshot($birthRaw, $hour, $province) : null;
        $birthLord = $snap !== null ? LifeGraphTables::planetNum((string) $snap['ruler']['name']) : null;
        if ($snap === null || $birthLord === null) {
            return null;
        }
        // ผังเดียวกับรูปผัง/พรอมต์: วันทางโหรเปลี่ยน 06:00 · พุธกลางคืน = ราหู · ไม่รู้เวลา = 12:00 (วันตามปฏิทิน)
        $birthDate = substr($birthRaw, 0, 10);
        $birthDay = new \DateTimeImmutable($birthDate);
        $hasTime = $snap['birth_hour'] !== null;
        $hasLagna = $snap['basis'] === 'lagna';
        $dayName = str_replace(' ', '', (string) $snap['day_name']);
        $notes = [];
        if (! $hasTime) {
            $notes[] = 'ไม่ทราบเวลาเกิด — หาดาวเจ้าวันเกิดจากวันตามปฏิทิน (ถ้าเกิดก่อน 06:00 วันทางโหรคือวันก่อนหน้า) และไม่นับภพ 6/8/12 เพราะไม่มีลัคนา';
            if ($birthLord === 4) {
                $notes[] = 'เกิดวันพุธแต่ไม่ทราบเวลา — ถือเป็นพุธกลางวัน ถ้าเกิด 18:00 เป็นต้นไปจะเป็นราหู (พุธกลางคืน) และกราฟเปลี่ยนทั้งเส้น';
            }
        }
        if ($birthLord === 8) {
            $notes[] = 'เกิดพุธกลางคืน (ราหู) — ไม่คำนวณทักษาจร เพราะตำราไม่ตรงกัน';
        }

        // ดาวตามเลข (natalChartSnapshot คีย์ด้วยชื่ออังกฤษ Sun, Moon …) · มาตรฐานดาวคิดจากตาราง GenLotto ชุดเดียวกัน
        $byNum = static function (?array $s): array {
            $out = [];
            foreach ((array) ($s['planets'] ?? []) as $p) {
                $out[(int) $p['num']] = $p;
            }

            return $out;
        };
        $dig = static fn (int $p, array $pl): string => LifeGraphTables::dignity($p, (int) array_search($pl['sign'], LifeGraphTables::SIGNS, true));
        $planets = $byNum($snap);
        // มาตรฐานดาวกำเนิด — ไม่รู้เวลา: เทียบต้นวัน/ปลายวัน ดาวที่ย้ายราศีจนมาตรฐานต่างกันไม่นับ
        $edges = $hasTime ? null : [
            $byNum($this->astro->natalChartSnapshot($birthDate, 0.0, $province)),
            $byNum($this->astro->natalChartSnapshot($birthDate, 23.98, $province)),
        ];
        $bhavaOf = [];
        foreach (LifeGraphTables::thaksa($birthLord) as $row) {
            $bhavaOf[$row['planet']] = $row['bhava'];
        }
        $info = [];
        foreach (LifeGraphTables::THAKSA_ORDER as $p) {
            $pl = $planets[$p];
            $sure = $edges === null || ! isset($edges[0][$p], $edges[1][$p]) || $dig($p, $edges[0][$p]) === $dig($p, $edges[1][$p]);
            if (! $sure) {
                $notes[] = 'ไม่ทราบเวลาเกิด — '.$pl['th'].'ย้ายราศีในวันนั้น ('.$edges[0][$p]['sign'].' → '.$edges[1][$p]['sign'].') จึงไม่นับมาตรฐานดาวของ'.$pl['th'];
            }
            $house = $hasLagna && $pl['house'] !== null ? (int) $pl['house'] : null;
            $info[$p] = [
                'planet' => $p,
                'planet_th' => LifeGraphTables::planetTh($p),
                'sign' => $pl['sign'],
                'dignity' => $sure ? $dig($p, $pl) : null,
                'house' => $house,
                'house_name' => $house !== null ? LifeGraphTables::HOUSE_NAMES[$house] : null,
                'bhava' => $bhavaOf[$p],
                'hex' => LifeGraphTables::THAKSA_HEX[$p],
            ];
        }
        $profile['birth_time'] = $hasTime ? sprintf('%02d:%02d', (int) floor($snap['birth_hour']), (int) round(fmod($snap['birth_hour'], 1.0) * 60)) : null;
        $natal = ['lagna' => $hasLagna ? ['sign' => $snap['anchor']] : null];

        $w = self::WEIGHTS;
        $parts = static function (int $p, string $role) use ($info, $w): array {
            $label = ($role === 'main' ? 'ดาวเสวย' : 'ดาวแทรก').LifeGraphTables::planetTh($p);
            $i = $info[$p];
            $out = [];
            if ($role === 'main') {
                $t = LifeGraphTexts::PERIOD[$p];
                $out[] = ['key' => 'main_nature', 'planet' => $p, 'delta' => (float) $w['main_nature'][$t['tone']], 'reason' => 'ตำราว่าช่วง'.LifeGraphTables::planetTh($p).'เสวยอายุเป็นช่วง'.$t['tone']];
            }
            if ($i['dignity'] !== null && isset($w[$role.'_dignity'][$i['dignity']])) {
                $out[] = ['key' => $role.'_dignity', 'planet' => $p, 'delta' => (float) $w[$role.'_dignity'][$i['dignity']], 'reason' => $label.'กำเนิดอยู่ราศี'.$i['sign'].' = '.$i['dignity']];
            }
            if ($i['house'] !== null && in_array($i['house'], self::BAD_HOUSES, true)) {
                $out[] = ['key' => $role.'_house', 'planet' => $p, 'delta' => (float) $w[$role.'_house_bad'], 'reason' => $label.'สถิตภพที่ '.$i['house'].' '.$i['house_name'].' (นับจากลัคนา)'];
            }

            return $out;
        };

        $periods = self::mainPeriods($birthLord, $maxAge + 1);
        $by = (int) $birthDay->format('Y');
        $years = [];
        foreach ($periods as $pi => $per) {
            for ($age = max(0, $per['from']); $age < min($per['to'], $maxAge + 1); $age++) {
                $main = $per['planet'];
                $comps = $parts($main, 'main');
                $ys = ($age - $per['from']) * self::NATHI_PER_YEAR;
                $ye = $ys + self::NATHI_PER_YEAR;
                $subs = [];
                foreach (self::subPeriods($main) as $sp) {
                    $ov = min($ye, $sp['offset'] + $sp['length']) - max($ys, $sp['offset']);
                    if ($ov <= 0) {
                        continue;
                    }
                    $share = $ov / self::NATHI_PER_YEAR;
                    $tag = $ov < self::NATHI_PER_YEAR ? ' · แทรก '.self::duration($ov)['text'].' ในปีนี้' : '';
                    $subs[] = ['planet' => $sp['planet'], 'planet_th' => LifeGraphTables::planetTh($sp['planet']), 'share' => round($share, 4)];
                    foreach ($parts($sp['planet'], 'sub') as $c) {
                        $comps[] = ['delta' => $c['delta'] * $share, 'reason' => $c['reason'].$tag] + $c;
                    }
                    $t = LifeGraphTexts::SUB[$main.'-'.$sp['planet']] ?? null;
                    if ($t !== null && isset($w['combo'][$t['tone']])) {
                        $comps[] = ['key' => 'combo', 'planet' => $sp['planet'], 'delta' => $w['combo'][$t['tone']] * $share, 'reason' => 'ตำรา'.LifeGraphTables::planetTh($main).'เสวย '.LifeGraphTables::planetTh($sp['planet']).'แทรก: '.$t['text'].$tag];
                    }
                    // คู่ดาว (ตำราดาวคู่ทั่วไป) ใช้เฉพาะคู่ที่ตำราเสวย-แทรกไม่มีคำทาย — ตำราเฉพาะคู่ชนะ
                    //   (v2 ให้ทั้งสองชั้น: คู่สมพล +6 กลบตำรา "ร้าย" −5 ⇒ 5 คู่ร้ายได้คะแนนบวก — ตรวจพบ 2026-10-12)
                    $kind = $sp['planet'] === $main || $t !== null ? null : self::pairKind($main, $sp['planet']);
                    if ($kind !== null) {
                        $comps[] = ['key' => 'pair', 'planet' => $sp['planet'], 'delta' => $w['pair'][$kind] * $share, 'reason' => 'ดาวเสวย'.LifeGraphTables::planetTh($main).'กับดาวแทรก'.LifeGraphTables::planetTh($sp['planet']).'เป็น'.$kind.$tag];
                    }
                }
                $jon = self::thaksaJon($birthLord, $age + 1);
                $jb = $jon['bhava'][$main] ?? null; // ภูมิจรของดาวเสวยปีนี้
                $jt = $jb !== null ? (LifeGraphTexts::JON[$bhavaOf[$main]][$jb === 'ศรี' ? 'sri' : 'kali'] ?? null) : null;
                if (isset($w['jon'][$jb]) && $jt !== null) {
                    $comps[] = ['key' => 'jon', 'planet' => $main, 'delta' => (float) $w['jon'][$jb], 'reason' => 'ทักษาจรอายุย่าง '.$jon['age_yang'].': ดาวเสวย'.LifeGraphTables::planetTh($main).' (ภูมิ'.$bhavaOf[$main].'เดิม) เป็น'.($jb === 'ศรี' ? 'ศรีจร' : 'กาลีจร').' — '.$jt.($jon['center'] ? ' (ปีตกตากลาง ใช้พฤหัสบดีเป็นบริวารจร)' : '')];
                }
                $sum = $w['base'] + array_sum(array_column($comps, 'delta'));
                $dom = $subs[0]; // subs เรียงตามเวลา · ดาวแทรกประจำปี = ดวงที่แทรกนานที่สุดในปีนั้น
                foreach ($subs as $s) {
                    $dom = $s['share'] > $dom['share'] ? $s : $dom;
                }
                $years[] = [
                    'age' => $age,
                    'year_ce' => $by + $age,
                    'year_be' => $by + $age + 543,
                    'main_planet' => $main,
                    'main_planet_th' => LifeGraphTables::planetTh($main),
                    'sub_planet' => $dom['planet'],
                    'sub_planet_th' => $dom['planet_th'],
                    'subs' => $subs,
                    'period' => $pi,
                    'jon' => $jon === null ? null : [
                        'age_yang' => $jon['age_yang'], 'center' => $jon['center'],
                        'boriwan' => $jon['boriwan'], 'boriwan_th' => LifeGraphTables::planetTh($jon['boriwan']),
                        'main_bhava' => $jon['bhava'][$main],
                        'kalakini' => $jon['kalakini'], 'kalakini_th' => LifeGraphTables::planetTh($jon['kalakini']),
                    ],
                    'score' => round(max(0.0, min(100.0, $sum)), 1),
                    'components' => array_map(static fn ($c) => ['key' => $c['key'], 'planet' => $c['planet'], 'delta' => round($c['delta'], 2), 'reason' => $c['reason']], $comps),
                ];
            }
        }

        // ช่วงดาวเสวย + ดาวแทรก (อายุเป็นปีทศนิยม · วันที่เป็นค่าประมาณ: บวก ปี เดือน วัน จากวันเกิด)
        $at = static function (int $nathi) use ($birthDay): array {
            $d = self::duration($nathi);

            return ['text' => $nathi === 0 ? 'แรกเกิด' : 'อายุ '.$d['text'], 'date' => $birthDay->modify(sprintf('+%d years +%d months +%d days', $d['y'], $d['m'], $d['d']))->format('Y-m-d')];
        };
        $periodsOut = [];
        foreach ($periods as $pi => $per) {
            $mp = $parts($per['planet'], 'main');
            $ms = array_sum(array_column($mp, 'delta'));
            $inRange = array_values(array_filter($years, static fn ($y) => $y['period'] === $pi));
            $subsOut = [];
            foreach (self::subPeriods($per['planet']) as $k => $sp) {
                $abs = $per['from'] * self::NATHI_PER_YEAR + $sp['offset'];
                $st = $at($abs);
                $subsOut[] = [
                    'planet' => $sp['planet'],
                    'planet_th' => LifeGraphTables::planetTh($sp['planet']),
                    'own' => $k === 0,
                    'from_age' => round($abs / self::NATHI_PER_YEAR, 4),
                    'to_age' => round(($abs + $sp['length']) / self::NATHI_PER_YEAR, 4),
                    'length' => self::duration($sp['length'])['text'],
                    'from_text' => $st['text'],
                    'from_date' => $st['date'],
                    'pair' => $k === 0 ? null : self::pairKind($per['planet'], $sp['planet']),
                    'tone' => LifeGraphTexts::SUB[$per['planet'].'-'.$sp['planet']]['tone'] ?? null,
                    'text' => LifeGraphTexts::SUB[$per['planet'].'-'.$sp['planet']]['text'] ?? null,
                ];
            }
            $periodsOut[] = [
                'from_age' => $per['from'],
                'to_age' => $per['to'],
                'years' => $per['years'],
                'planet' => $per['planet'],
                'planet_th' => LifeGraphTables::planetTh($per['planet']),
                'hex' => LifeGraphTables::THAKSA_HEX[$per['planet']],
                'bhava' => $bhavaOf[$per['planet']],
                'nature' => LifeGraphTexts::PERIOD[$per['planet']]['tone'],
                'text' => LifeGraphTexts::PERIOD[$per['planet']]['text'],
                'tone' => $ms >= $w['tone']['good'] ? 'ดี' : ($ms <= $w['tone']['bad'] ? 'ร้าย' : 'กลาง'),
                'main_score' => round($ms, 2),
                'reasons' => array_map(static fn ($c) => ['key' => $c['key'], 'delta' => $c['delta'], 'reason' => $c['reason']], $mp),
                'score_avg' => $inRange ? round(array_sum(array_column($inRange, 'score')) / count($inRange), 1) : null,
                'from_date' => $birthDay->modify('+'.$per['from'].' years')->format('Y-m-d'),
                'subs' => $subsOut,
            ];
        }

        // อายุปัจจุบัน + ดาวแทรกตอนนี้ (สัดส่วนปีตามปฏิทิน)
        $ageNow = $birthDay > $now ? 0 : (int) $birthDay->diff($now)->y;
        $last = $birthDay->modify('+'.$ageNow.' years');
        $next = $birthDay->modify('+'.($ageNow + 1).' years');
        $ageDec = $ageNow + max(0.0, min(0.9999, ($now->getTimestamp() - $last->getTimestamp()) / max(1, $next->getTimestamp() - $last->getTimestamp())));
        $current = ['age' => $ageNow, 'age_decimal' => round($ageDec, 3), 'year' => null, 'period' => null, 'sub' => null];
        foreach ($years as $y) {
            if ($y['age'] === $ageNow) {
                $current['year'] = $y;
            }
        }
        foreach ($periodsOut as $po) {
            if ($ageDec >= $po['from_age'] && $ageDec < $po['to_age']) {
                $current['period'] = ['planet' => $po['planet'], 'planet_th' => $po['planet_th'], 'from_age' => $po['from_age'], 'to_age' => $po['to_age'], 'tone' => $po['tone'], 'until_date' => $birthDay->modify('+'.$po['to_age'].' years')->format('Y-m-d')];
                foreach ($po['subs'] as $si => $s) {
                    if ($ageDec >= $s['from_age'] && $ageDec < $s['to_age']) {
                        $nextSub = $po['subs'][$si + 1] ?? null;
                        $current['sub'] = $s + ['until_date' => $nextSub ? $nextSub['from_date'] : $current['period']['until_date']];
                    }
                }
            }
        }

        $scores = array_column($years, 'score');
        $flat = $scores === [] || max($scores) - min($scores) < 1.0;
        $peaks = $flat ? [] : self::extremes($years, true);
        $lows = $flat ? [] : self::extremes($years, false);
        $who = 'เกิดวัน'.$dayName.' '.LifeGraphTables::thDate($birthDate).($hasTime ? ' เวลา '.$profile['birth_time'].' น.' : ' (ไม่ทราบเวลาเกิด)').' · ดาวเจ้าวันเกิด'.LifeGraphTables::planetTh($birthLord);

        return [
            'version' => self::VERSION,
            'computed_at' => $now->format('Y-m-d H:i:s'),
            'max_age' => $maxAge,
            'profile' => [
                'name' => (string) ($profile['name'] ?? ''),
                'birth_date' => $birthDate,
                'birth_time' => $hasTime ? $profile['birth_time'] : null,
                'birth_day_th' => $dayName,
                'birth_lord' => $birthLord,
                'birth_lord_th' => LifeGraphTables::planetTh($birthLord),
                'lagna' => $hasLagna ? $natal['lagna']['sign'] : null,
            ],
            'natal' => ['planets' => array_values($info), 'thaksa' => LifeGraphTables::thaksa($birthLord)],
            'current' => $current,
            'years' => array_map(static function ($y) {
                unset($y['period']);

                return $y;
            }, $years),
            'periods' => $periodsOut,
            'peaks' => $peaks,
            'lows' => $lows,
            'reading' => self::reading($current, $periodsOut, $info, $bhavaOf, $birthLord, $peaks, $lows, $who),
            'weights' => self::WEIGHTS,
            'bad_houses' => self::BAD_HOUSES,
            'notes' => $notes,
            'omitted' => self::OMITTED,
            'sources' => self::SOURCES,
            'text_sources' => LifeGraphTexts::SOURCES,
        ];
    }

    /**
     * 📜 คำพยากรณ์ช่วงนี้ตามตำรา (LifeGraphTexts) — items = หน้าเว็บ · card = บรรทัดบนการ์ด · block = บล็อกข้อมูลให้ AI แต่งคำทำนาย
     * ใช้เฉพาะข้อความที่ ≥ 2 แหล่งตรงกัน · คะแนน/จุดพีคบอกชัดว่าเป็นค่าที่ระบบออกแบบ
     */
    private static function reading(array $cur, array $periods, array $info, array $bhavaOf, int $birthLord, array $peaks, array $lows, string $who): array
    {
        $th = [LifeGraphTables::class, 'planetTh'];
        $items = [];
        $card = [];
        $lines = [$who.' · อายุ '.$cur['age'].' ปี'];
        $p = $cur['period'];
        $pi = null;
        foreach ($periods as $k => $po) {
            if ($p && $po['from_age'] === $p['from_age']) {
                $pi = $k;
            }
        }
        if ($p) {
            $t = LifeGraphTexts::PERIOD[$p['planet']];
            $items[] = ['kind' => 'period', 'title' => $th($p['planet']).'เสวยอายุ (อายุ '.$p['from_age'].'–'.$p['to_age'].' ปี)', 'tone' => $t['tone'], 'text' => $t['text'], 'src' => $t['src']];
            $card[] = $th($p['planet']).'เสวยอายุ (ช่วง'.$t['tone'].'): '.$t['text'];
            $lines[] = 'ช่วงนี้: '.$th($p['planet']).'เสวยอายุ อายุ '.$p['from_age'].'–'.$p['to_age'].' ปี — ตำราว่าเป็นช่วง'.$t['tone'].': '.$t['text'];
            $i = $info[$p['planet']];
            $bad = in_array($i['dignity'], ['นิจ', 'ประ'], true) || in_array($i['house'], self::BAD_HOUSES, true);
            $good = in_array($i['dignity'], ['อุจ', 'เกษตร'], true);
            if ($good || $bad) {
                $where = ($i['dignity'] !== null && $i['dignity'] !== 'กลาง' ? 'เป็น'.$i['dignity'].' (ราศี'.$i['sign'].')' : '').(in_array($i['house'], self::BAD_HOUSES, true) ? ' อยู่ภพ'.$i['house_name'] : '');
                $text = $th($p['planet']).'ในดวงเดิม'.trim($where).' — ตำราว่า'.($bad && ! $good ? 'ผลร้ายของช่วงนี้หนักขึ้น' : ($good && ! $bad ? 'ผลดีของช่วงนี้เด่นขึ้น' : 'มีทั้งแรงหนุนและแรงถ่วง'));
                $items[] = ['kind' => 'natal', 'title' => 'ดาวเสวยในดวงเดิม', 'tone' => $bad && ! $good ? 'ร้าย' : ($good && ! $bad ? 'ดี' : 'ผสม'), 'text' => $text, 'src' => LifeGraphTexts::NATAL_RULE['src']];
                $lines[] = 'ดาวเสวยในดวงเดิม: '.$text;
            }
        }
        $s = $cur['sub'];
        if ($p && $s) {
            $t = LifeGraphTexts::SUB[$p['planet'].'-'.$s['planet']] ?? null;
            $title = 'ดาวแทรก'.$th($s['planet']).' ถึงราว '.LifeGraphTables::thDate($s['until_date']);
            $items[] = ['kind' => 'sub', 'title' => $title, 'tone' => $t['tone'] ?? null, 'text' => $t['text'] ?? 'ตำราสามฉบับให้ผลคู่นี้ไม่ตรงกัน จึงไม่ใส่คำทำนาย', 'src' => $t['src'] ?? []];
            if ($t !== null) {
                $card[] = $th($s['planet']).'แทรกถึง '.LifeGraphTables::thDate($s['until_date']).': '.$t['text'];
            }
            $lines[] = 'ดาวแทรกตอนนี้: '.$th($s['planet']).' ถึงราว '.LifeGraphTables::thDate($s['until_date']).($t ? ' — ตำราว่า'.$t['tone'].': '.$t['text'] : ' — ตำราขัดกัน ไม่มีคำทำนาย');
            $nextSub = null;
            foreach ($periods[$pi]['subs'] as $k => $x) {
                if ($x['from_age'] === $s['from_age']) {
                    $nextSub = $periods[$pi]['subs'][$k + 1] ?? null;
                }
            }
            if ($nextSub) {
                $t2 = LifeGraphTexts::SUB[$p['planet'].'-'.$nextSub['planet']] ?? null;
                $lines[] = 'ดาวแทรกถัดไป: '.$th($nextSub['planet']).' เริ่มราว '.LifeGraphTables::thDate($nextSub['from_date']).($t2 ? ' — ตำราว่า'.$t2['tone'].': '.$t2['text'] : ' — ตำราขัดกัน ไม่มีคำทำนาย');
            }
        }
        $jon = self::thaksaJon($birthLord, $cur['age'] + 1);
        if ($jon !== null) {
            $sri = (int) array_search('ศรี', $jon['bhava'], true);
            $parts = [];
            $src = [];
            $tones = [];
            foreach ([[$jon['kalakini'], 'kali', 'กาลีจร', 'ร้าย'], [$sri, 'sri', 'ศรีจร', 'ดี']] as [$pl, $k, $label, $tn]) {
                $txt = LifeGraphTexts::JON[$bhavaOf[$pl]][$k] ?? null;
                if ($txt !== null) {
                    $parts[] = $th($pl).' (ภูมิ'.$bhavaOf[$pl].'เดิม) เป็น'.$label.' — '.$txt;
                    $src = array_merge($src, LifeGraphTexts::JON[$bhavaOf[$pl]][$k.'_src']);
                    $tones[] = $tn;
                }
            }
            if ($parts) {
                $items[] = ['kind' => 'jon', 'title' => 'ทักษาจรปีนี้ (อายุย่าง '.$jon['age_yang'].')', 'tone' => count($tones) === 1 ? $tones[0] : 'ผสม', 'text' => implode(' · ', $parts), 'src' => array_values(array_unique($src))];
                $card[] = 'ปีนี้ (ทักษาจร): '.$parts[0];
                $lines[] = 'ทักษาจรปีนี้ (อายุย่าง '.$jon['age_yang'].'): '.implode(' · ', $parts);
            }
        } elseif ($birthLord === 8) {
            $lines[] = 'ทักษาจร: ไม่คำนวณ (เกิดพุธกลางคืน ตำราไม่ตรงกัน)';
        }
        $np = $pi !== null ? ($periods[$pi + 1] ?? null) : null;
        if ($np) {
            $lines[] = 'ช่วงดาวเสวยถัดไป: '.$np['planet_th'].' อายุ '.$np['from_age'].'–'.$np['to_age'].' ปี — ตำราว่าเป็นช่วง'.LifeGraphTexts::PERIOD[$np['planet']]['tone'];
        }
        if ($peaks) {
            $lines[] = 'จุดเด่นของกราฟ (คะแนนเป็นค่าที่ระบบออกแบบ ไม่ใช่ตัวเลขจากตำรา): ช่วงรุ่ง อายุ '.implode(', ', array_column($peaks, 'age')).' · ช่วงควรระวัง อายุ '.implode(', ', array_column($lows, 'age'));
        }
        $lines[] = 'วิธีเสริมตามตำรา: '.LifeGraphTexts::REMEDY['text'];

        return [
            'items' => $items,
            'card' => array_slice($card, 0, 3),
            'block' => "【กราฟชีวิตตามตำรามหาทักษา — ข้อมูลจากระบบ ใช้ตามนี้ ห้ามแต่งอายุ ช่วงเวลา หรือคะแนนเพิ่ม】\n"
                .implode("\n", $lines)
                ."\nข้อกำชับ: เพื่อการศึกษาและความบันเทิง · ห้ามฟันธงเรื่องความตาย โรคร้าย หรือรับประกันผลทางการเงิน",
        ];
    }

    /** จุดพีค/จุดต่ำ $n จุด ห่างกันอย่างน้อย $gap ปี พร้อมเหตุผลหลัก 3 ข้อ */
    private static function extremes(array $years, bool $high, int $n = 3, int $gap = 5): array
    {
        $idx = array_keys($years);
        usort($idx, static fn ($a, $b) => ($high ? $years[$b]['score'] <=> $years[$a]['score'] : $years[$a]['score'] <=> $years[$b]['score']) ?: $a <=> $b);
        $pick = [];
        foreach ($idx as $i) {
            foreach ($pick as $j) {
                if (abs($years[$i]['age'] - $years[$j]['age']) < $gap) {
                    continue 2;
                }
            }
            $pick[] = $i;
            if (count($pick) >= $n) {
                break;
            }
        }

        return array_map(static function (int $i) use ($years, $high): array {
            $y = $years[$i];
            $c = array_values(array_filter($y['components'], static fn ($c) => $high ? $c['delta'] > 0 : $c['delta'] < 0));
            usort($c, static fn ($a, $b) => abs($b['delta']) <=> abs($a['delta']));

            return ['age' => $y['age'], 'year_ce' => $y['year_ce'], 'year_be' => $y['year_be'], 'score' => $y['score'], 'main_planet_th' => $y['main_planet_th'], 'sub_planet_th' => $y['sub_planet_th'], 'why' => array_column(array_slice($c, 0, 3), 'reason')];
        }, $pick);
    }
}
