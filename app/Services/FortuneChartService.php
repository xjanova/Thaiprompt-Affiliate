<?php

namespace App\Services;

use App\Services\Fortune\ThaiAstrologyService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * FortuneChartService - สร้างภาพดวงชะตาแบบ SVG
 *
 * สร้าง birth chart แบบ 12 ภพ (เรือนชะตา) โหราศาสตร์ไทย
 * คำนวณตำแหน่งดาวเคราะห์ 9 ดวง ตามหลักเจ้าชนะ
 */
class FortuneChartService
{
    // ดาวเคราะห์ 9 ดวง
    public const PLANETS = [
        'sun' => ['name' => 'อาทิตย์', 'symbol' => "\u{2609}", 'color' => '#FF6B35', 'day' => 0],
        'moon' => ['name' => 'จันทร์',   'symbol' => "\u{263D}", 'color' => '#C0C0C0', 'day' => 1],
        'mars' => ['name' => 'อังคาร',   'symbol' => "\u{2642}", 'color' => '#E74C3C', 'day' => 2],
        'mercury' => ['name' => 'พุธ',      'symbol' => "\u{263F}", 'color' => '#2ECC71', 'day' => 3],
        'jupiter' => ['name' => 'พฤหัสบดี', 'symbol' => "\u{2643}", 'color' => '#F39C12', 'day' => 4],
        'venus' => ['name' => 'ศุกร์',    'symbol' => "\u{2640}", 'color' => '#3498DB', 'day' => 5],
        'saturn' => ['name' => 'เสาร์',    'symbol' => "\u{2644}", 'color' => '#8E44AD', 'day' => 6],
        'rahu' => ['name' => 'ราหู',     'symbol' => "\u{260A}", 'color' => '#34495E', 'day' => -1],
        'ketu' => ['name' => 'เกตุ',     'symbol' => "\u{260B}", 'color' => '#95A5A6', 'day' => -2],
    ];

    // ภพ 12 ภพ
    public const HOUSES = [
        1 => ['name' => 'ตนุ',       'meaning' => 'ตัวตน',         'color' => '#E74C3C'],
        2 => ['name' => 'กดุมภ',     'meaning' => 'ทรัพย์',        'color' => '#F39C12'],
        3 => ['name' => 'สหัชชะ',    'meaning' => 'พี่น้อง',       'color' => '#2ECC71'],
        4 => ['name' => 'พันธุ',     'meaning' => 'ครอบครัว',      'color' => '#3498DB'],
        5 => ['name' => 'ปุตตะ',     'meaning' => 'ลูก/สร้างสรรค์', 'color' => '#9B59B6'],
        6 => ['name' => 'อริ',       'meaning' => 'ศัตรู/โรค',     'color' => '#E67E22'],
        7 => ['name' => 'ปัตนิ',     'meaning' => 'คู่ครอง',       'color' => '#E91E63'],
        8 => ['name' => 'มรณะ',      'meaning' => 'เปลี่ยนแปลง',   'color' => '#607D8B'],
        9 => ['name' => 'ศุภะ',      'meaning' => 'โชคลาภ',       'color' => '#FF9800'],
        10 => ['name' => 'กัมมะ',     'meaning' => 'การงาน',       'color' => '#795548'],
        11 => ['name' => 'ลาภะ',      'meaning' => 'ลาภผล',        'color' => '#4CAF50'],
        12 => ['name' => 'วินาศ',     'meaning' => 'อุปสรรค',      'color' => '#9E9E9E'],
    ];

    // ตารางเจ้าชนะตามวันเกิด
    public const CHAOCHANA = [
        0 => ['planet' => 'sun',     'friends' => ['jupiter', 'mars'],    'enemies' => ['saturn', 'rahu'],    'element' => 'ไฟ',  'lucky_color' => 'แดง'],
        1 => ['planet' => 'moon',    'friends' => ['mercury', 'venus'],   'enemies' => ['rahu', 'saturn'],    'element' => 'น้ำ', 'lucky_color' => 'เหลือง'],
        2 => ['planet' => 'mars',    'friends' => ['sun', 'jupiter'],     'enemies' => ['mercury', 'saturn'], 'element' => 'ไฟ',  'lucky_color' => 'ชมพู'],
        3 => ['planet' => 'mercury', 'friends' => ['moon', 'venus'],      'enemies' => ['rahu', 'mars'],      'element' => 'ดิน', 'lucky_color' => 'เขียว'],
        4 => ['planet' => 'jupiter', 'friends' => ['sun', 'mars'],        'enemies' => ['rahu', 'saturn'],    'element' => 'ลม',  'lucky_color' => 'ส้ม'],
        5 => ['planet' => 'venus',   'friends' => ['mercury', 'moon'],    'enemies' => ['sun', 'mars'],       'element' => 'น้ำ', 'lucky_color' => 'ฟ้า'],
        6 => ['planet' => 'saturn',  'friends' => ['rahu', 'venus'],      'enemies' => ['sun', 'mars'],       'element' => 'ดิน', 'lucky_color' => 'ม่วง'],
    ];

    /**
     * 🚨 (2026-09-03) กติกาของตารางข้างบน — อ่านก่อนแก้ทุกครั้ง
     *
     * ตารางนี้เก็บ "รายวันเกิด" (7 แถว) ไม่ใช่ "รายคู่ดาว" (21 คู่)
     * ⇒ ความสัมพันธ์ของดาว 1 คู่ถูกเขียนไว้ **2 ที่** และไม่มีอะไรบังคับให้ตรงกัน
     *   นี่คือช่องที่ทำให้เกิดข้อขัดแย้งจริงมาแล้ว: แถววันพฤหัสบดีบอก "เสาร์ = ศัตรู"
     *   แต่แถววันเสาร์บอก "พฤหัสบดี = มิตร" — โหรที่อ่านผัง 2 ใบเห็นทันที
     *
     * 📜 ที่มาของข้อผิดพลาด: ตารางนี้เกิดใน commit 445e248e6 ซึ่งเป็นงาน **วาดรูป
     *   SVG birth chart** (ตอนนั้นเป็น protected const ใช้ในตัววาดรูปอย่างเดียว)
     *   แต่ละแถวถูกเขียนให้ได้ "มิตร 2 ศัตรู 2" เท่ากันทุกวันเพื่อให้รูปสมดุล
     *   ต่อมาถูกเลื่อนขั้นเป็น public แล้วเอาไปใช้เป็น**แหล่งตำราในคำทำนายจริง**
     *   โดยไม่มีใครออดิทตอนเลื่อนขั้น ⇒ ช่องมิตรของเสาร์ที่ต้องเติมให้ครบ 2
     *   เลยถูกใส่ "พฤหัสบดี" ทั้งที่อีกแถวบอกว่าเป็นศัตรู
     *
     * ✅ กติกาที่ต้องรักษาไว้ (มีเทสต์ล็อกที่ ChaochanaConsistencyTest):
     *   1. ห้ามมีคู่ไหนที่ฝั่งหนึ่งว่า "มิตร" อีกฝั่งว่า "ศัตรู"
     *   2. ไม่สมมาตรได้ ถ้าอีกฝั่ง**เงียบ** (= เป็นกลาง) — ตรงกับตำราที่มิตรภาพ
     *      ไม่จำเป็นต้องตอบแทนกันเสมอ เช่น เสาร์ถือศุกร์เป็นมิตร แต่ศุกร์เป็นกลางกับเสาร์
     *   3. แก้ที่นี่แล้วต้องรันเทสต์ — ห้ามแก้แถวเดียวแล้วจบ
     */
    public const CHAOCHANA_PAIR_RULE = 'ห้ามมีคู่ดาวที่ฝั่งหนึ่งว่ามิตร อีกฝั่งว่าศัตรู';

    /**
     * 🌙 (2026-09-05) ดัชนี "วันเกิดที่ 8" — พุธกลางคืน (ดาวเจ้าเรือน = ราหู)
     *
     * ตำราไทยมีดาว 8 ดวง และ "วันพุธ" แบ่งเป็น 2 ดาว:
     *   พุธกลางวัน (ย่ำรุ่ง–ย่ำค่ำ) = พุธ · พุธกลางคืน (ย่ำค่ำ–ย่ำรุ่ง) = **ราหู**
     * โหรจริงตรวจข้อนี้เป็นข้อแรก ๆ ของคนเกิดวันพุธ — เพจโฆษณาว่า "โหราศาสตร์ไทย
     * หลักเจ้าชนะ" แล้วมีแค่ 7 วันเกิด = จุดที่โดนจับได้ง่ายที่สุด
     *
     * ⚠️ **จงใจไม่ใส่เป็นแถวที่ 7 ของ CHAOCHANA** — ตารางนั้นถูกวนทั้งใบอยู่หลายที่
     *    (`ThaiAstrologyService::rahuRulerProfile()` อนุมานมิตร/ศัตรูของราหูจากมัน,
     *     `ChaochanaConsistencyTest` เทียบทีละแถวกับอีก 2 ตารางในระบบ) การยัดราหู
     *    เข้าไปจะทำให้ราหูกลายเป็นมิตร/ศัตรูของตัวเอง และเทียบกับ
     *    `getPlanetByDayOfWeek(7)` ที่ไม่มีจริง ⇒ แยกเป็นค่าคงที่ต่างหาก แล้วเข้าถึง
     *    ผ่าน `chaochanaFor()` จุดเดียว
     */
    public const WEDNESDAY_NIGHT = 7;

    /**
     * 🌙 แถวเจ้าชนะของคนเกิด "พุธกลางคืน" (ราหู)
     *
     * 🚫 ไม่แต่งตำราขึ้นใหม่แม้แต่ช่องเดียว — **อนุมานย้อนจาก CHAOCHANA ที่มีอยู่**:
     *   - วันเสาร์ระบุ `rahu` เป็นมิตร ⇒ ราหูถือเสาร์เป็นมิตร
     *   - วันอาทิตย์/จันทร์/พุธ/พฤหัสบดี ระบุ `rahu` เป็นศัตรู ⇒ ราหูถือ 4 ดวงนั้นเป็นศัตรู
     *   - อังคาร/ศุกร์ เงียบทั้งคู่ ⇒ เป็นกลาง (ตำราไม่บังคับให้มิตรภาพตอบแทนกันเสมอ
     *     ดูกติกาข้อ 2 ของ CHAOCHANA_PAIR_RULE)
     *   - ธาตุ/สี ← `config/thai_astrology_knowledge.php` ช่อง `planet_meta.ราหู`
     */
    public const CHAOCHANA_WEDNESDAY_NIGHT = [
        'planet' => 'rahu',
        'friends' => ['saturn'],
        'enemies' => ['sun', 'moon', 'mercury', 'jupiter'],
        'element' => 'ลม',
        'lucky_color' => 'เทา-ตุ่น-รุ้ง-ทอง',
    ];

    /**
     * 🧭 ทิศประจำดาว (ทิศทั้ง 8 ตามตำรานพเคราะห์)
     *
     * 🚨 (2026-09-05) เดิมตารางนี้ถูกเขียนไว้ **2 ที่ที่ไม่ตรงกัน**:
     *   `HoroscopeDailyService::LUCKY_DIRECTIONS` บอกอาทิตย์ = ตะวันออก
     *   `FortuneHoroscopeService::generateLuckyDirection()` บอกอาทิตย์ = ตะวันออกเฉียงเหนือ
     * ⇒ ลูกค้าที่อ่านโพสกับที่อ่านในแชทได้คนละทิศ (แผลตระกูลเดียวกับ CHAOCHANA)
     *   ชุดนี้ยึดตามมาตรฐาน (อาทิตย์=บูรพา จันทร์=พายัพ อังคาร=ทักษิณ พุธ=อุดร
     *   พฤหัสบดี=อีสาน ศุกร์=อาคเนย์ เสาร์=ประจิม ราหู=หรดี) ซึ่งตรงกับชุดของ
     *   HoroscopeDailyService เดิมทุกช่อง แค่เติมราหูที่ขาดไป
     */
    public const LUCKY_DIRECTIONS = [
        'sun' => 'ตะวันออก',
        'moon' => 'ตะวันตกเฉียงเหนือ',
        'mars' => 'ใต้',
        'mercury' => 'เหนือ',
        'jupiter' => 'ตะวันออกเฉียงเหนือ',
        'venus' => 'ตะวันออกเฉียงใต้',
        'saturn' => 'ตะวันตก',
        'rahu' => 'ตะวันตกเฉียงใต้',
    ];

    /**
     * แถวเจ้าชนะของดัชนีวันเกิด — รองรับวันที่ 8 (พุธกลางคืน)
     *
     * ⚠️ ทุกจุดที่รับดัชนีวันเกิดจากภายนอกต้องเรียกตัวนี้ **ห้ามแตะ CHAOCHANA ตรง ๆ**
     *    ไม่งั้น index 7 จะได้ null เงียบ ๆ แล้วตกไป brief เปล่า
     *
     * @param  int  $dayIndex  0=อาทิตย์ … 6=เสาร์ · 7=พุธกลางคืน
     */
    public static function chaochanaFor(int $dayIndex): ?array
    {
        if ($dayIndex === self::WEDNESDAY_NIGHT) {
            return self::CHAOCHANA_WEDNESDAY_NIGHT;
        }

        return self::CHAOCHANA[$dayIndex] ?? null;
    }

    /**
     * 🜨 ดาวที่ผังดวงกำเนิดจริงมี แต่ PLANETS ไม่มี — มฤตยู (ดาว ๐)
     *
     * ⚠️ จงใจไม่ใส่ใน PLANETS: ตารางนั้นถูกวนทั้งใบหลายที่ (หน้าแอดมิน · ผังสาธิต array_diff ·
     *    ดวงรายวัน) ใส่เพิ่ม = ผังสาธิตเปลี่ยนหน้าตา + หน้าแอดมินมีดาวที่ตารางเจ้าชนะไม่รู้จัก
     */
    protected const NATAL_EXTRA_PLANETS = [
        'uranus' => ['name' => 'มฤตยู', 'symbol' => "\u{26E2}", 'color' => '#0E7490'],
    ];

    /**
     * 🔢 รุ่นหน้าตารูปผังดวงกำเนิด — อยู่ในชื่อไฟล์ (natalChartKey)
     *
     * แก้วิธีวาดเมื่อไหร่ให้เลื่อนเลข ⇒ ไฟล์ของผังเดิมที่วาดใหม่ได้ชื่อใหม่ แยกจากรูปรุ่นก่อนได้ทันที
     */
    protected const NATAL_RENDER_VERSION = 'natal-v1';

    /**
     * สร้างรูปผังดวงกำเนิด (PNG) จากวันเกิด — ผังจริงชุดเดียวกับที่คำทำนายอ้าง
     *
     * 🚨 (2026-09-12) เดิมวาดจาก calculatePlanetPositions($dayOfWeek) = ผังสาธิต 7 แบบทั้งระบบ
     *   (เจ้าชนะ→ภพ 1 · มิตร→9/11/5 · ศัตรู→6/12/8) + วันปฏิทิน (ไม่ข้ามย่ำรุ่ง · ไม่มีพุธกลางคืน)
     *   แล้วส่งให้ลูกค้าดูดวง 39 เป็น "ผังของคุณ" คู่กับคำทำนายที่อ้างผังจริง ⇒ รูปกับข้อความขัดกัน
     *   ใหม่: วาดจาก ThaiAstrologyService::natalChartSnapshot() — ผังตัวเดียวกับ natalPromptBlocks()
     *   ⚠️ อินพุตต้องเป็นชุดเดียวกับพรอมต์ (สตริงวันเกิด + เวลา/จังหวัดจาก statedBirthInputs)
     *      ไม่งั้นลัคนาคนละราศี = ภพในรูปไม่ตรงกับคำทำนาย
     *
     * @param  string  $birthDate  วันเกิด "Y-m-d" หรือ "Y-m-d H:i" (FortuneReading::birthDateTimeForChart)
     * @param  string  $name  ชื่อผู้ใช้
     * @param  string|null  $gender  เพศ (ไม่ได้ใช้วาด — คงไว้ให้ผู้เรียกเดิม)
     * @param  float|null  $birthHour  เวลาเกิดที่ลูกค้าพิมพ์ในคำถาม — ชนะเวลาในสตริงวันเกิด
     * @param  string|null  $birthProvince  จังหวัดเกิด (null = พิกัดกรุงเทพ)
     * @return string|null URL ของภาพ chart หรือ null ถ้าเกิดข้อผิดพลาด
     */
    public function generateBirthChart(
        string $birthDate,
        string $name,
        ?string $gender = null,
        ?float $birthHour = null,
        ?string $birthProvince = null
    ): ?string {
        try {
            // ✅ เช็ค GD extension ก่อนสร้าง chart
            if (! $this->gdAvailable()) {
                Log::error('FortuneChart: GD extension ไม่ได้ติดตั้ง! ไม่สามารถสร้าง chart ได้', [
                    'birthDate' => $birthDate,
                    'name' => $name,
                    'php_version' => PHP_VERSION,
                    'loaded_extensions' => implode(', ', get_loaded_extensions()),
                ]);

                return null;
            }

            $chartData = $this->natalChartData($birthDate, $name, $birthHour, $birthProvince);
            if ($chartData === null) {
                // 🚫 ผูกดวงไม่ได้ = ไม่มีรูป — ห้ามถอยไปวาดผังสาธิตแทน (นั่นคือบั๊กที่เพิ่งแก้)
                Log::warning('FortuneChart: ผูกดวงกำเนิดไม่ได้ — ไม่สร้างรูปผัง', [
                    'birthDate' => $birthDate,
                ]);

                return null;
            }

            $pngData = $this->buildNatalPngChart($chartData);

            $url = $this->saveChartAsImage($pngData, 'birth-chart-'.$chartData['chartKey']);

            Log::info('FortuneChart: สร้าง birth chart สำเร็จ', [
                'name' => $name,
                'birthDate' => $birthDate,
                'basis' => $chartData['basis'],
                'url' => $url,
            ]);

            return $url;

        } catch (\Throwable $e) {
            // ⚠️ ใช้ \Throwable เพื่อจับทั้ง \Exception และ \Error (เช่น GD function not found)
            Log::error('FortuneChart: Failed to generate birth chart', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => substr($e->getTraceAsString(), 0, 500),
                'birthDate' => $birthDate,
                'gd_loaded' => extension_loaded('gd'),
            ]);

            return null;
        }
    }

    /**
     * 🗺️ ข้อมูลที่ส่งให้ตัววาดรูปผังดวงกำเนิด — แยกออกมาให้เทสต์เทียบกับผังในพรอมต์ได้โดยไม่ต้องวาดจริง
     *
     * ทุกค่ามาจาก ThaiAstrologyService::natalChartSnapshot() (ห้ามคำนวณภพ/ดาวเจ้าชนะเองที่นี่):
     *   - planetPositions [ภพ 1-12 => [คีย์ดาว]] — มีเฉพาะเมื่อผังมีฐานนับภพ (ลัคนา/จันทร์ลัคน์)
     *     ฐาน 'none' = **อาร์เรย์ว่าง** ⇒ ตัววาดวางดาวตามราศี (signPositions) และบอกว่าไม่มีภพ
     *   - signPositions [ราศี => [คีย์ดาว]] — มีทุกกรณี (ราศีของดาวไม่ต้องใช้เวลาเกิด)
     *   - ดาวเจ้าชนะ/มิตร/ศัตรู/วันเกิด = ชุดเดียวกับหัวผัง (ย่ำรุ่ง 06:00 + พุธกลางคืน = ราหู)
     *
     * @return array|null null = ผูกดวงไม่ได้ (วันเกิดอ่านไม่ออก)
     */
    public function natalChartData(string $birthDate, string $name, ?float $birthHour = null, ?string $birthProvince = null): ?array
    {
        $snap = (new ThaiAstrologyService)->natalChartSnapshot($birthDate, $birthHour, $birthProvince);
        if ($snap === null) {
            return null;
        }

        $date = Carbon::parse($birthDate);
        $order = (array) config('thai_astrology_knowledge.zodiac_order', []);
        $anchor = $snap['anchor'];

        $planets = [];
        $houses = [];
        $signs = array_fill_keys($order, []);
        foreach ($snap['planets'] as $enKey => $p) {
            $key = strtolower((string) $enKey);
            $meta = self::PLANETS[$key] ?? self::NATAL_EXTRA_PLANETS[$key] ?? [];
            $planets[$key] = [
                'name' => $p['th'],
                'numeral' => self::thaiNumeral($p['num']),
                'color' => $meta['color'] ?? '#6B7280',
                'sign' => $p['sign'],
                'deg' => $p['deg'],
                'retro' => $p['retro'],
                'house' => $p['house'],
                // 🌙 ฐาน 'none' = ไม่รู้เวลาเกิด + จันทร์ย้ายราศีวันนั้น ⇒ ราศีจันทร์ที่คิดตอนเที่ยงอาจผิดราศี
                'uncertain' => $key === 'moon' && $snap['basis'] === 'none',
            ];
            $signs[$p['sign']][] = $key;
            if ($p['house'] !== null) {
                $houses[$p['house']][] = $key;
            }
        }

        // ในช่องเดียวกันเรียงตามองศา — วงเดินตามเข็มนาฬิกา = ลองจิจูดเพิ่มขึ้น
        $byDegree = fn (array $keys): array => collect($keys)->sortBy(fn (string $k) => $planets[$k]['deg'])->values()->all();
        $houses = array_map($byDegree, $houses);
        $signs = array_map($byDegree, $signs);

        // ราศีประจำภพ 1-12 (นับจากราศีภพที่ 1 ตามจักรราศี) — ไม่มีฐาน = ไม่มีภพ
        $houseSigns = [];
        $anchorIdx = $anchor !== null ? array_search($anchor, $order, true) : false;
        if ($anchorIdx !== false && count($order) === 12) {
            for ($h = 1; $h <= 12; $h++) {
                $houseSigns[$h] = $order[($anchorIdx + $h - 1) % 12];
            }
        }

        $ruler = $snap['ruler'];
        $rulerKey = $this->planetKeyByName($ruler['name']);
        $time = $snap['birth_hour'] !== null ? ThaiAstrologyService::hourLabel($snap['birth_hour']) : null;

        return [
            'name' => $name,
            'birthDate' => $date->format('d/m/').($date->year + 543),
            'birthTime' => $time,
            'place' => $snap['place'],
            'dayOfWeek' => $snap['day_name'],
            'calendarDayOfWeek' => $snap['day_shifted'] ? $snap['calendar_day_name'] : null,
            'zodiac' => $snap['zodiac'],
            'mainPlanet' => $ruler['name'],
            'mainPlanetKey' => $rulerKey,
            'mainPlanetNumeral' => $rulerKey !== null ? ($planets[$rulerKey]['numeral'] ?? '') : '',
            'mainPlanetColor' => $rulerKey !== null ? (self::PLANETS[$rulerKey]['color'] ?? '#D97706') : '#D97706',
            'friends' => $ruler['friends'],
            'enemies' => $ruler['enemies'],
            'basis' => $snap['basis'],
            'anchor' => $anchor,
            'houseSigns' => $houseSigns,
            'planetPositions' => $anchor !== null ? array_replace(array_fill(1, 12, []), $houses) : [],
            'signPositions' => $signs,
            'planets' => $planets,
            'chartKey' => $this->natalChartKey($date, $time, $snap['place']),
        ];
    }

    /**
     * 🔑 ชื่อไฟล์ประจำผัง = แฮชของอินพุตที่ใช้ผูกดวงจริง (วันเกิด + เวลาเกิด + จังหวัด) + รุ่นหน้าตา
     *
     * เดิม "birth-chart-{วันในสัปดาห์}" ⇒ ป้ายไฟล์บอกได้แค่ 7 แบบ (สมัยผังสาธิตก็มีแค่ 7 แบบจริง)
     * ใช้ค่าหลังผูกดวงแล้ว (จังหวัดที่ไม่รู้จัก = ไม่มีพิกัด = ผังเดียวกับไม่บอก ⇒ แฮชเดียวกัน)
     * ⚠️ saveChartAsImage() ยังต่อท้ายสุ่ม 8 ตัวเสมอ — ในรูปมีชื่อลูกค้า ผังเดียวกันคนละชื่อห้ามทับไฟล์กัน
     */
    protected function natalChartKey(Carbon $date, ?string $time, ?string $place): string
    {
        return substr(sha1(implode('|', [
            self::NATAL_RENDER_VERSION,
            $date->format('Y-m-d'),
            $time ?? '-',
            $place ?? '-',
        ])), 0, 12);
    }

    /** คีย์ดาวของตัววาด (sun/moon/…/uranus) จากชื่อไทย — null = ไม่รู้จัก */
    protected function planetKeyByName(string $thaiName): ?string
    {
        foreach (self::PLANETS + self::NATAL_EXTRA_PLANETS as $key => $meta) {
            if ($meta['name'] === $thaiName) {
                return $key;
            }
        }

        return null;
    }

    /** เลขดาวแบบเลขไทย (อาทิตย์ ๑ … เกตุ ๙ · มฤตยู ๐) — ฟอนต์ไทยมีครบ ต่างจากสัญลักษณ์ ☉☽ */
    protected static function thaiNumeral(int $num): string
    {
        return mb_chr(0x0E50 + max(0, min(9, $num)), 'UTF-8');
    }

    /** มี GD ให้วาดไหม — แยกเป็นเมธอดให้เทสต์สลับได้ */
    protected function gdAvailable(): bool
    {
        return extension_loaded('gd');
    }

    /**
     * สร้าง birth chart แบบ SVG (สำหรับ admin preview ในเบราว์เซอร์)
     *
     * @return string|null data URI ของ SVG
     */
    public function generateBirthChartSvg(string $birthDate, string $name, ?string $gender = null): ?string
    {
        try {
            $date = Carbon::parse($birthDate);
            $dayOfWeek = $date->dayOfWeek;

            $planetPositions = $this->calculatePlanetPositions($dayOfWeek);
            $chaochana = self::CHAOCHANA[$dayOfWeek];
            $mainPlanet = self::PLANETS[$chaochana['planet']];

            $thaiDays = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];

            $chartData = [
                'name' => $name,
                'birthDate' => $date->format('d/m/').($date->year + 543),
                'dayOfWeek' => $thaiDays[$dayOfWeek],
                'mainPlanet' => $mainPlanet['name'],
                'mainPlanetSymbol' => $mainPlanet['symbol'],
                'mainPlanetColor' => $mainPlanet['color'],
                'planetPositions' => $planetPositions,
                'chaochana' => $chaochana,
                'isFullChart' => true,
            ];

            $svg = $this->buildSvgChart($chartData);

            return 'data:image/svg+xml;base64,'.base64_encode($svg);

        } catch (\Exception $e) {
            Log::warning('FortuneChart: Failed to generate SVG chart', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * สร้าง chart แบบด่วน (ไม่มีวันเกิด) - แสดงดวงดาวช่วงปัจจุบัน
     *
     * @param  string  $name  ชื่อผู้ใช้
     * @return string|null URL ของภาพ chart
     */
    public function generateQuickChart(string $name): ?string
    {
        try {
            // ✅ เช็ค GD extension ก่อนสร้าง chart
            if (! extension_loaded('gd')) {
                Log::error('FortuneChart: GD extension ไม่ได้ติดตั้ง! ไม่สามารถสร้าง quick chart ได้');

                return null;
            }

            $now = Carbon::now('Asia/Bangkok');
            $dayOfWeek = $now->dayOfWeek;

            $chartData = [
                'name' => $name,
                'birthDate' => null,
                'dayOfWeek' => null,
                'mainPlanet' => null,
                'mainPlanetSymbol' => null,
                'mainPlanetColor' => '#8B5CF6',
                'planetPositions' => $this->calculateCurrentTransit(),
                'chaochana' => null,
                'isFullChart' => false,
                'transitDate' => $now->format('d/m/').($now->year + 543),
            ];

            $pngData = $this->buildPngChart($chartData);

            $url = $this->saveChartAsImage($pngData, 'quick-chart');

            Log::info('FortuneChart: สร้าง quick chart สำเร็จ', [
                'name' => $name,
                'url' => $url,
            ]);

            return $url;

        } catch (\Throwable $e) {
            // ⚠️ ใช้ \Throwable เพื่อจับทั้ง \Exception และ \Error
            Log::error('FortuneChart: Failed to generate quick chart', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'gd_loaded' => extension_loaded('gd'),
            ]);

            return null;
        }
    }

    /**
     * จัดวางดาวในภพตามหลักเจ้าชนะ (ผังสาธิต — ไม่ใช่การผูกดวงจริง)
     * ใช้วันเกิดเป็นฐาน → ดาวเจ้าชนะอยู่ภพตนุ(1) → ดาวอื่นกระจายตามลำดับ
     *
     * 🚨 (2026-09-01) อย่าเข้าใจผิดว่านี่คือ ephemeris — มันรับแค่ "วันในสัปดาห์"
     *   มิตร→ภพ 9/11/5 · ศัตรู→ภพ 6/12/8 · ที่เหลือวน 2/3/4/7 แบบตายตัว
     *   ⇒ **ผลลัพธ์มีได้แค่ 7 แบบทั้งระบบ** คนเกิดวันอังคารได้ผังเดียวกันหมดไม่ว่าเกิดปีไหน
     *   เคยถูกใช้ป้อน AI ใน Deep 39 Pro Session แล้วทำให้คำทำนายกว้างจนต้องตอบเซฟ
     *   → ย้ายไปใช้ App\Services\Fortune\ThaiAstrologyService::formatPersonBlock()
     *     (คำนวณดาวจริงด้วย PlanetEphemeris) แล้ว
     *   ⚠️ เมธอดนี้เหลือไว้ให้หน้าแอดมิน/มาร์เก็ตติ้งใช้แสดงผังเท่านั้น
     *     **ห้ามนำกลับไปป้อน AI สำหรับคำทำนายที่ลูกค้าจ่ายเงิน**
     *   🖼️ (2026-09-12) รูป PNG ที่ส่งลูกค้า (generateBirthChart) เลิกใช้เมธอดนี้แล้ว — วาดจากผังจริง
     *     ผ่าน natalChartData() · เหลือพรีวิว SVG หน้าแอดมิน (generateBirthChartSvg) ที่ยังวาดผังสาธิต
     *
     * @param  int  $dayOfWeek  0-6
     * @return array [house_number => [planet_keys]]
     */
    public function calculatePlanetPositions(int $dayOfWeek): array
    {
        $positions = array_fill(1, 12, []);

        // ดาวเจ้าชนะอยู่ภพตนุ (1) เสมอ
        // 🌙 ผ่าน chaochanaFor() — รองรับ index 7 (พุธกลางคืน = ราหู) ที่ไม่มีใน CHAOCHANA
        $chaochana = self::chaochanaFor($dayOfWeek) ?? self::CHAOCHANA[0];
        $mainPlanetKey = $chaochana['planet'];
        $positions[1][] = $mainPlanetKey;

        // จัดวางดาวมิตรในภพดี (ศุภะ=9, ลาภะ=11, กัมมะ=10)
        $friendHouses = [9, 11, 5];
        foreach ($chaochana['friends'] as $idx => $friend) {
            $house = $friendHouses[$idx % count($friendHouses)];
            $positions[$house][] = $friend;
        }

        // จัดวางดาวศัตรูในภพที่ท้าทาย (อริ=6, วินาศ=12, มรณะ=8)
        $enemyHouses = [6, 12, 8];
        foreach ($chaochana['enemies'] as $idx => $enemy) {
            $house = $enemyHouses[$idx % count($enemyHouses)];
            $positions[$house][] = $enemy;
        }

        // วางดาวที่เหลือในภพอื่น
        $placedPlanets = array_merge([$mainPlanetKey], $chaochana['friends'], $chaochana['enemies']);
        $remainingPlanets = array_diff(array_keys(self::PLANETS), $placedPlanets);
        $remainingHouses = [2, 3, 4, 7];

        $houseIdx = 0;
        foreach ($remainingPlanets as $planet) {
            $house = $remainingHouses[$houseIdx % count($remainingHouses)];
            $positions[$house][] = $planet;
            $houseIdx++;
        }

        return $positions;
    }

    /**
     * คำนวณดาวโคจรปัจจุบัน (transit) สำหรับ quick chart
     *
     * @return array [house_number => [planet_keys]]
     */
    protected function calculateCurrentTransit(): array
    {
        return $this->calculateTransitForDate(Carbon::now('Asia/Bangkok'));
    }

    /**
     * จัดวางดาวลงภพ ณ วันที่กำหนด ด้วย "สูตรตามวันในปี" (ผังสาธิต — ไม่ใช่ดาวจรจริง)
     *
     * ใช้สูตรจัดวางดาวตามวันในปี โดยดาวแต่ละดวงมีความเร็วโคจรต่างกัน
     * - ดาวเร็ว (พุธ, ศุกร์, อาทิตย์, จันทร์) เปลี่ยนภพบ่อย
     * - ดาวช้า (เสาร์, พฤหัส, ราหู, เกตุ) อยู่ภพนานหลายเดือน
     *
     * 🚨 (2026-09-11) อย่าเข้าใจผิดว่านี่คือตำแหน่งดาว — ไม่มีการคำนวณดาราศาสตร์เลย
     *   ภพ = (ปี×31 + วันในปี) ÷ (ความเร็ว/12) และเกตุถูกบังคับให้ห่างราหู 6 ภพ
     *   (ไม่มีลัคนาของใครทั้งนั้น ⇒ "ภพ" ในผลลัพธ์ไม่ได้นับจากดวงของลูกค้าคนไหน)
     *   เคยถูกป้อน AI ผ่าน calculateFutureTransits() ในช่อง {transit_info} ของดูดวง 39
     *   โดยติดป้ายว่า "คำนวณจากหลักเจ้าชนะ" → ลบเมธอดนั้นทิ้งแล้ว
     *   ดาวจรจริง = ThaiAstrologyService::formatTransitBlock() / formatTransitOutlookBlock()
     *   ⚠️ เหลือไว้ให้รูป quick chart (generateQuickChart) เท่านั้น
     *     **ห้ามนำไปป้อน AI สำหรับคำทำนายที่ลูกค้าจ่ายเงิน**
     *
     * @param  Carbon  $date  วันที่ต้องการคำนวณ
     * @return array [house_number => [planet_keys]]
     */
    public function calculateTransitForDate(Carbon $date): array
    {
        $positions = array_fill(1, 12, []);
        $dayOfYear = $date->dayOfYear;
        $yearOffset = $date->year * 31; // ให้ดาวเลื่อนตามปีด้วย

        // ดาวแต่ละดวงมีความเร็วโคจรต่างกัน (prime multipliers)
        // ดาวเร็ว → multiplier น้อย (เปลี่ยนภพบ่อย)
        // ดาวช้า → multiplier มาก (อยู่ภพนาน)
        $planetSpeeds = [
            'sun' => 31,      // อาทิตย์ ~1 เดือน/ภพ
            'moon' => 3,       // จันทร์ เร็วมาก ~2.5 วัน/ภพ
            'mars' => 47,      // อังคาร ~45 วัน/ภพ
            'mercury' => 23,   // พุธ ~1 เดือน/ภพ (แต่ retrograde บ่อย)
            'jupiter' => 371,  // พฤหัส ~1 ปี/ภพ (ดาวช้า)
            'venus' => 29,     // ศุกร์ ~1 เดือน/ภพ
            'saturn' => 907,   // เสาร์ ~2.5 ปี/ภพ (ช้ามาก)
            'rahu' => 557,     // ราหู ~1.5 ปี/ภพ (โคจรถอยหลัง)
            'ketu' => 557,     // เกตุ ตรงข้ามราหู
        ];

        foreach ($planetSpeeds as $key => $speed) {
            if ($key === 'ketu') {
                // เกตุอยู่ตรงข้ามราหูเสมอ (ห่าง 6 ภพ)
                $rahuHouse = null;
                foreach ($positions as $h => $planets) {
                    if (in_array('rahu', $planets)) {
                        $rahuHouse = $h;
                        break;
                    }
                }
                $house = $rahuHouse ? ((($rahuHouse - 1 + 6) % 12) + 1) : ((($yearOffset + $dayOfYear * 3 + 6) % 12) + 1);
            } else {
                $house = (int) ((($yearOffset + $dayOfYear) / ($speed / 12)) % 12) + 1;
            }
            // ทำให้อยู่ในช่วง 1-12
            $house = max(1, min(12, $house));
            $positions[$house][] = $key;
        }

        return $positions;
    }

    /**
     * สร้าง PNG chart ด้วย GD — รองรับภาษาไทย + ใช้ได้ใน Facebook/LINE
     *
     * ⚠️ (2026-09-12) เหลือผู้เรียกเดียวคือ generateQuickChart() (isFullChart=false)
     *    รูปผังดวงกำเนิดย้ายไป buildNatalPngChart() แล้ว — กิ่ง isFullChart=true วาดผังสาธิต ห้ามนำกลับมาใช้กับลูกค้า
     *
     * @param  array  $chartData  ข้อมูล chart
     * @return string PNG binary data
     */
    protected function buildPngChart(array $chartData): string
    {
        // 🌟 (2026-05-05) Redesigned — สดใส ดูง่าย ชัดเจนกว่าเดิม
        //   1. Background gradient cream → soft lavender (อบอุ่น สดใส)
        //   2. Pastel zone fills 12 ภพ (alternating colors — แยกแต่ละภพได้ตา)
        //   3. Planet badges ใหญ่ขึ้น + white border + colored fill (ชัดเจน)
        //   4. Center card with soft drop shadow (โดดเด่น)
        //   5. Title with decorative stars (ดูดี มีระดับ)
        $width = 1000;
        $height = 1000;
        $cx = $width / 2;
        $cy = $height / 2;
        $outerR = 420;
        $innerR = 275;
        $centerR = 175;

        // สร้าง canvas
        $img = imagecreatetruecolor($width, $height);
        imagealphablending($img, true);
        imagesavealpha($img, true);

        // โหลดฟอนต์ + ตรวจสอบว่ามีจริง
        $thaiFont = $this->getThaiFont();
        $symbolFont = $this->getSymbolFont();

        Log::debug('FortuneChart: buildPngChart fonts', [
            'thaiFont' => $thaiFont,
            'thaiFont_exists' => @file_exists($thaiFont),
            'symbolFont' => $symbolFont,
            'symbolFont_exists' => @file_exists($symbolFont),
        ]);

        // === สี: สดใส อบอุ่น ===
        $bgWhite = $this->hexColor($img, '#FFFBF7');         // พื้นหลัง: ครีมอ่อน warm
        $bgCenter = $this->hexColor($img, '#FFFFFF');         // วงกลาง: ขาวสะอาด
        $purple = $this->hexColor($img, '#7C3AED');           // เส้นวงนอก: ม่วงสด
        $purpleDark = $this->hexColor($img, '#5B21B6');       // เส้นแบ่งภพ: ม่วงเข้ม
        $purpleLight = $this->hexColor($img, '#A78BFA');      // เส้นวงใน
        $gold = $this->hexColor($img, '#D97706');             // ทอง: ส้มทอง สดใส
        $goldDark = $this->hexColor($img, '#92400E');         // หัวเรื่อง: น้ำตาลทอง
        $textDark = $this->hexColor($img, '#1F2937');         // ข้อความหลัก: เทาเข้มเกือบดำ
        $textGray = $this->hexColor($img, '#4B5563');         // ข้อความรอง
        $textMuted = $this->hexColor($img, '#9CA3AF');        // Footer
        $green = $this->hexColor($img, '#059669');            // มิตร
        $red = $this->hexColor($img, '#DC2626');              // ศัตรู
        $shadowSoft = imagecolorallocatealpha($img, 124, 58, 237, 110); // เงาม่วง
        $whiteOpaque = $this->hexColor($img, '#FFFFFF');

        // === พื้นหลัง gradient (vertical: ครีมบน → ลาเวนเดอร์ล่าง) ===
        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            // ครีม #FFFBF7 (255,251,247) → ลาเวนเดอร์อ่อน #F3EFFF (243,239,255)
            $r = (int) (255 - (255 - 243) * $ratio);
            $g = (int) (251 - (251 - 239) * $ratio);
            $b = (int) (247 + (255 - 247) * $ratio);
            $lineColor = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, $width, $y, $lineColor);
        }

        // === Pastel zone fills 12 ภพ — แยกแต่ละภพให้เห็นได้ตา ===
        // ใช้ imagefilledarc IMG_ARC_PIE → เติมจากศูนย์กลาง outerR แล้วทับ innerR ทีหลัง
        for ($i = 1; $i <= 12; $i++) {
            $startDeg = ($i - 1) * 30 - 90;
            $endDeg = $i * 30 - 90;

            $house = self::HOUSES[$i];
            // pastel เด่น/อ่อน สลับกันให้แยก zone ได้ชัด — alpha 100/108
            $alpha = $i % 2 === 0 ? 108 : 100;
            $hex = ltrim($house['color'], '#');
            $hr = hexdec(substr($hex, 0, 2));
            $hg = hexdec(substr($hex, 2, 2));
            $hb = hexdec(substr($hex, 4, 2));
            $zoneColor = imagecolorallocatealpha($img, $hr, $hg, $hb, $alpha);
            imagefilledarc(
                $img,
                (int) $cx, (int) $cy,
                $outerR * 2, $outerR * 2,
                $startDeg, $endDeg,
                $zoneColor,
                IMG_ARC_PIE
            );
        }

        // === เงานุ่มรอบวงกลางก่อนทับด้วยขาว (depth) ===
        for ($r = $centerR + 16; $r > $centerR; $r -= 2) {
            $alpha = (int) (115 + (127 - 115) * (($centerR + 16 - $r) / 16));
            $shadowRing = imagecolorallocatealpha($img, 124, 58, 237, $alpha);
            imagefilledellipse($img, (int) $cx, (int) $cy, $r * 2, $r * 2, $shadowRing);
        }

        // ทับด้วยวงกลาง (cut-out ของ pastel zones — ทำให้ภพเหลือแค่วงแหวน)
        imagefilledellipse($img, (int) $cx, (int) $cy, $innerR * 2, $innerR * 2, $bgWhite);
        imagefilledellipse($img, (int) $cx, (int) $cy, $centerR * 2, $centerR * 2, $bgCenter);

        // === วงกลม 3 ชั้น (เส้นชัด) ===
        $this->drawCircle($img, $cx, $cy, $outerR, $purple, 4);
        $this->drawCircle($img, $cx, $cy, $innerR, $purpleDark, 2);
        $this->drawCircle($img, $cx, $cy, $centerR, $purple, 3);

        // === เส้นแบ่ง 12 ภพ ===
        imagesetthickness($img, 2);
        for ($i = 0; $i < 12; $i++) {
            $angle = deg2rad($i * 30 - 90);
            $x1 = (int) ($cx + $innerR * cos($angle));
            $y1 = (int) ($cy + $innerR * sin($angle));
            $x2 = (int) ($cx + $outerR * cos($angle));
            $y2 = (int) ($cy + $outerR * sin($angle));
            imageline($img, $x1, $y1, $x2, $y2, $purpleDark);
        }
        imagesetthickness($img, 1);

        // === ชื่อภพ + เลข + ดาวในแต่ละภพ ===
        for ($i = 1; $i <= 12; $i++) {
            $house = self::HOUSES[$i];
            $midAngle = deg2rad(($i - 1) * 30 - 90 + 15);

            $houseColor = $this->hexColor($img, $house['color']);

            // 🏷️ House badge: เลขภพในวงกลมสีพื้น (ใกล้ขอบนอก)
            $badgeR = $outerR - 22;
            $bx = $cx + $badgeR * cos($midAngle);
            $by = $cy + $badgeR * sin($midAngle);
            // วงพื้นขาวมีกรอบสีภพ
            imagefilledellipse($img, (int) $bx, (int) $by, 32, 32, $whiteOpaque);
            imagesetthickness($img, 2);
            imageellipse($img, (int) $bx, (int) $by, 32, 32, $houseColor);
            imagesetthickness($img, 1);
            $this->drawCenteredText($img, $thaiFont, 14, $bx, $by, (string) $i, $houseColor);

            // ชื่อภพ (ใกล้เลข — เยื้องเข้าใน)
            $labelR = $outerR - 56;
            $tx = $cx + $labelR * cos($midAngle);
            $ty = $cy + $labelR * sin($midAngle);
            $this->drawCenteredText($img, $thaiFont, 14, $tx, $ty, $house['name'], $textDark);

            // ดาวเคราะห์ในภพ
            $planets = $chartData['planetPositions'][$i] ?? [];
            if (! empty($planets)) {
                $planetR = ($innerR + $centerR) / 2 + 22;
                $planetCount = count($planets);

                foreach ($planets as $pIdx => $planetKey) {
                    $planet = self::PLANETS[$planetKey];
                    $planetColor = $this->hexColor($img, $planet['color']);
                    $offset = ($pIdx - ($planetCount - 1) / 2) * 18;
                    $px = $cx + ($planetR + $offset) * cos($midAngle);
                    $py = $cy + ($planetR + $offset) * sin($midAngle);

                    // 🌟 Planet badge ใหญ่ขึ้น: เงานุ่ม → กรอบขาว → fill สีดาว → symbol ขาว
                    // 1. เงานุ่ม (drop shadow)
                    imagefilledellipse($img, (int) $px + 2, (int) $py + 2, 46, 46, $shadowSoft);
                    // 2. กรอบขาว (white ring)
                    imagefilledellipse($img, (int) $px, (int) $py, 46, 46, $whiteOpaque);
                    // 3. fill สีดาว (full saturation)
                    imagefilledellipse($img, (int) $px, (int) $py, 40, 40, $planetColor);

                    // สัญลักษณ์ดาว (ขาว — contrast ดี)
                    $this->drawCenteredText($img, $symbolFont, 24, $px, $py - 2, $planet['symbol'], $whiteOpaque);

                    // ชื่อดาว (ภาษาไทย) — ใต้ดวงดาว สีเข้ม contrast ดีกับพื้น cream
                    $this->drawCenteredText($img, $thaiFont, 13, $px, $py + 26, $planet['name'], $textDark);
                }
            }
        }

        // === ตรงกลาง: ข้อมูลผู้ใช้ ===
        $name = mb_substr($chartData['name'], 0, 15);

        if ($chartData['isFullChart']) {
            $mainColor = $this->hexColor($img, $chartData['mainPlanetColor'] ?? '#D97706');

            $this->drawCenteredText($img, $thaiFont, 16, $cx, $cy - 110, '✦ BIRTH CHART ✦', $gold);
            $this->drawCenteredText($img, $thaiFont, 26, $cx, $cy - 80, $name, $textDark);

            // เส้นคั่นใต้ชื่อ
            imagesetthickness($img, 2);
            imageline($img, (int) ($cx - 60), (int) ($cy - 62), (int) ($cx + 60), (int) ($cy - 62), $purpleLight);
            imagesetthickness($img, 1);

            $this->drawCenteredText($img, $thaiFont, 16, $cx, $cy - 42, "วัน{$chartData['dayOfWeek']}", $purple);
            $this->drawCenteredText($img, $thaiFont, 15, $cx, $cy - 20, $chartData['birthDate'], $textGray);

            // ดาวเจ้าชนะ — badge ใหญ่ตรงกลาง (เด่น)
            // วงพื้น colored เล็กตรงกลาง
            $mainPlanetBadgeR = 32;
            imagefilledellipse($img, (int) $cx, (int) ($cy + 18), $mainPlanetBadgeR * 2, $mainPlanetBadgeR * 2, $mainColor);
            imagesetthickness($img, 3);
            imageellipse($img, (int) $cx, (int) ($cy + 18), $mainPlanetBadgeR * 2, $mainPlanetBadgeR * 2, $whiteOpaque);
            imagesetthickness($img, 1);
            $this->drawCenteredText($img, $symbolFont, 32, $cx, $cy + 18, $chartData['mainPlanetSymbol'], $whiteOpaque);

            $this->drawCenteredText($img, $thaiFont, 14, $cx, $cy + 64, "ดาวเจ้าชนะ: {$chartData['mainPlanet']}", $mainColor);

            // มิตร/ศัตรู
            $friendNames = implode(' ', array_map(fn ($k) => self::PLANETS[$k]['name'], $chartData['chaochana']['friends']));
            $enemyNames = implode(' ', array_map(fn ($k) => self::PLANETS[$k]['name'], $chartData['chaochana']['enemies']));
            $this->drawCenteredText($img, $thaiFont, 13, $cx, $cy + 92, "💚 มิตร: {$friendNames}", $green);
            $this->drawCenteredText($img, $thaiFont, 13, $cx, $cy + 114, "💔 ศัตรู: {$enemyNames}", $red);
        } else {
            // Quick chart (ไม่มีวันเกิด)
            $this->drawCenteredText($img, $thaiFont, 16, $cx, $cy - 90, '✦ TRANSIT CHART ✦', $gold);
            $this->drawCenteredText($img, $thaiFont, 26, $cx, $cy - 50, $name, $textDark);

            imagesetthickness($img, 2);
            imageline($img, (int) ($cx - 60), (int) ($cy - 30), (int) ($cx + 60), (int) ($cy - 30), $purpleLight);
            imagesetthickness($img, 1);

            $this->drawCenteredText($img, $thaiFont, 15, $cx, $cy - 8, 'ดวงดาวโคจรขณะนี้', $purple);
            $this->drawCenteredText($img, $thaiFont, 14, $cx, $cy + 16, $chartData['transitDate'], $textGray);
            $this->drawCenteredText($img, $thaiFont, 13, $cx, $cy + 70, '✨ บอกวันเกิดเพื่อดู Birth Chart', $purpleLight);
        }

        // === หัวเรื่องด้านบน ===
        $this->drawCenteredText($img, $thaiFont, 30, $cx, 42, '✨ หมอจันทราพยากรณ์ ✨', $goldDark);
        $this->drawCenteredText($img, $thaiFont, 14, $cx, 72, 'โหราศาสตร์เจ้าชนะ ✦ ดวงดาว 9 ดวง ✦ ภพ 12 ภพ', $purple);

        // === Footer ===
        $this->drawCenteredText($img, $thaiFont, 12, $cx, $height - 22, '🌙 หมอจันทราพยากรณ์ | thaiprompt.online', $textMuted);

        // === Export PNG ===
        ob_start();
        imagepng($img, null, 7);
        $pngData = ob_get_clean();
        imagedestroy($img);

        return $pngData;
    }

    /**
     * 🖼️ วาดรูปผังดวงกำเนิดจริง (PNG 1000×1000) จาก natalChartData()
     *
     * วงนอก 12 ช่อง เดินตามเข็มนาฬิกาจากบนสุด:
     *   - ผังมีฐานนับภพ (ลัคนา/จันทร์ลัคน์) → ช่อง = ภพ 1-12 · ป้ายเลขภพ + ชื่อภพ + ราศีของภพ
     *   - ฐาน 'none' → ช่อง = ราศี เมษ…มีน · **ไม่มีเลข/ชื่อภพเลย** และกลางวงบอกว่าไม่ทราบเวลาเกิด
     * ดาวเขียนด้วยเลขไทยตามแบบผังดวงไทย (อาทิตย์ ๑ … เกตุ ๙ · มฤตยู ๐) + ชื่อดาวใต้วง
     *   ⚠️ ใช้ฟอนต์ไทยตัวเดียว (เลขไทยแทนสัญลักษณ์ดาว) — ฟอนต์ไทยไม่มีอีโมจิ/✦ ⇒ ห้ามใส่ในข้อความรูปนี้
     *      (DejaVuSans.ttf เคยเป็นหน้า HTML 404 — เปลี่ยนเป็นของจริงแล้ว 2026-09-12 แต่ไม่มีอักษรไทย ใช้กับข้อความไทยไม่ได้)
     *
     * @param  array  $d  ผลจาก natalChartData()
     * @return string PNG binary data
     */
    protected function buildNatalPngChart(array $d): string
    {
        $width = 1000;
        $height = 1000;
        $cx = $width / 2;
        $cy = 532;
        $outerR = 440;
        $innerR = 238;
        $centerR = 228;

        // ไม่มีการวางดาวลงภพ = ฐาน 'none' → วงนอกเป็นราศี
        $houseMode = ! empty($d['planetPositions']);
        $order = (array) config('thai_astrology_knowledge.zodiac_order', []);

        $img = imagecreatetruecolor($width, $height);
        imagealphablending($img, true);
        imagesavealpha($img, true);

        $font = $this->getThaiFont();

        $bgCream = $this->hexColor($img, '#FFFBF7');
        $white = $this->hexColor($img, '#FFFFFF');
        $purple = $this->hexColor($img, '#7C3AED');
        $purpleDark = $this->hexColor($img, '#5B21B6');
        $purpleLight = $this->hexColor($img, '#A78BFA');
        $gold = $this->hexColor($img, '#B45309');
        $goldDark = $this->hexColor($img, '#92400E');
        $textDark = $this->hexColor($img, '#1F2937');
        $textGray = $this->hexColor($img, '#4B5563');
        $textMuted = $this->hexColor($img, '#9CA3AF');
        $green = $this->hexColor($img, '#047857');
        $red = $this->hexColor($img, '#B91C1C');
        $shadowSoft = imagecolorallocatealpha($img, 124, 58, 237, 112);
        $mainColor = $this->hexColor($img, $d['mainPlanetColor'] ?? '#D97706');

        // === พื้นหลัง: ครีมบน → ลาเวนเดอร์ล่าง (โทนเดียวกับรูปเดิม) ===
        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            $lineColor = imagecolorallocate($img, (int) (255 - 12 * $ratio), (int) (251 - 12 * $ratio), (int) (247 + 8 * $ratio));
            imageline($img, 0, $y, $width, $y, $lineColor);
        }

        // === พื้นสีพาสเทล 12 ช่อง ===
        for ($i = 1; $i <= 12; $i++) {
            $hex = ltrim(self::HOUSES[$i]['color'], '#');
            $zone = imagecolorallocatealpha(
                $img,
                hexdec(substr($hex, 0, 2)),
                hexdec(substr($hex, 2, 2)),
                hexdec(substr($hex, 4, 2)),
                $i % 2 === 0 ? 108 : 100
            );
            imagefilledarc($img, (int) $cx, (int) $cy, $outerR * 2, $outerR * 2, ($i - 1) * 30 - 90, $i * 30 - 90, $zone, IMG_ARC_PIE);
        }

        // === วงกลาง: เจาะพาสเทลออก + เงานุ่มในร่องระหว่างวง ===
        imagefilledellipse($img, (int) $cx, (int) $cy, $innerR * 2, $innerR * 2, $bgCream);
        for ($r = $centerR + 10; $r > $centerR; $r -= 2) {
            $alpha = (int) (112 + 15 * (($centerR + 10 - $r) / 10));
            imagefilledellipse($img, (int) $cx, (int) $cy, $r * 2, $r * 2, imagecolorallocatealpha($img, 124, 58, 237, $alpha));
        }
        imagefilledellipse($img, (int) $cx, (int) $cy, $centerR * 2, $centerR * 2, $white);

        $this->drawCircle($img, $cx, $cy, $outerR, $purple, 4);
        $this->drawCircle($img, $cx, $cy, $innerR, $purpleDark, 2);
        $this->drawCircle($img, $cx, $cy, $centerR, $purple, 3);

        imagesetthickness($img, 2);
        for ($i = 0; $i < 12; $i++) {
            $a = deg2rad($i * 30 - 90);
            imageline(
                $img,
                (int) ($cx + $innerR * cos($a)), (int) ($cy + $innerR * sin($a)),
                (int) ($cx + $outerR * cos($a)), (int) ($cy + $outerR * sin($a)),
                $purpleDark
            );
        }
        imagesetthickness($img, 1);

        // === ป้ายช่อง + ดาว ===
        for ($i = 1; $i <= 12; $i++) {
            $midDeg = ($i - 1) * 30 - 90 + 15;
            $mid = deg2rad($midDeg);
            $at = fn (float $r): array => [$cx + $r * cos($mid), $cy + $r * sin($mid)];

            // ป้ายข้อความกว้างกว่าสูง ⇒ ช่องซ้าย/ขวาต้องถอยเข้าในมากกว่าช่องบน/ล่าง
            //   ครึ่งความยาวของป้ายตามแนวรัศมี = ครึ่งกว้าง×|cos| + ครึ่งสูง×|sin|
            $cosA = abs(cos($mid));
            $sinA = abs(sin($mid));

            if ($houseMode) {
                // เลขภพคร่อมเส้นวงนอก — เหลือที่ในช่องให้ดาว
                $sectorColor = $this->hexColor($img, self::HOUSES[$i]['color']);
                [$bx, $by] = $at($outerR);
                imagefilledellipse($img, (int) $bx, (int) $by, 28, 28, $white);
                imagesetthickness($img, 2);
                imageellipse($img, (int) $bx, (int) $by, 28, 28, $sectorColor);
                imagesetthickness($img, 1);
                $this->drawCenteredText($img, $font, 12, $bx, $by, (string) $i, $sectorColor);

                // ชื่อภพ + ราศีของภพ ซ้อนกันแนวตั้งบนจอ (ซ้อนตามรัศมี = ช่องซ้าย/ขวาทับกันเอง)
                $half = 34 * $cosA + 20 * $sinA;
                $labelR = $outerR - 16 - $half;
                [$tx, $ty] = $at($labelR);
                $this->drawCenteredText($img, $font, 13, $tx, $ty - 9, self::HOUSES[$i]['name'], $textDark);
                $this->drawCenteredText($img, $font, 11, $tx, $ty + 10, 'ราศี'.($d['houseSigns'][$i] ?? ''), $textGray);

                $keys = $d['planetPositions'][$i] ?? [];
            } else {
                $sign = (string) ($order[$i - 1] ?? '');
                $half = 24 * $cosA + 11 * $sinA;
                $labelR = $outerR - 8 - $half;
                [$tx, $ty] = $at($labelR);
                $this->drawCenteredText($img, $font, 15, $tx, $ty, $sign, $textDark);

                $keys = $d['signPositions'][$sign] ?? [];
            }
            $zoneOuter = $labelR - $half - 8;

            foreach ($this->natalPlanetSlots(count($keys), $midDeg, $innerR + 6, $zoneOuter) as $idx => $slot) {
                $p = $d['planets'][$keys[$idx]] ?? null;
                if ($p === null) {
                    continue;
                }
                $this->drawNatalPlanetBadge(
                    $img, $font,
                    $cx + $slot['r'] * cos($slot['a']),
                    $cy + $slot['r'] * sin($slot['a']),
                    $p, $slot['scale'], $white, $shadowSoft, $textDark
                );
            }
        }

        // === กลางวง: ข้อมูลเจ้าชะตา — เรียงเป็นบรรทัด แล้วจัดกึ่งกลางทั้งก้อน ===
        $zodiacTh = explode(' ', trim((string) $d['zodiac']))[0];
        [$basisTitle, $basisNote] = match ($d['basis']) {
            'lagna' => ["ลัคนาราศี{$d['anchor']}", 'ภพนับจากลัคนา'],
            'moon' => ["จันทร์ลัคน์ราศี{$d['anchor']}", 'ไม่ทราบเวลาเกิด · ภพนับจากจันทร์ลัคน์'],
            default => ['ไม่ทราบเวลาเกิด', 'จันทร์ย้ายราศีวันเกิด · วางดาวตามราศี ไม่มีภพ'],
        };

        $lines = [
            ['text' => 'ผังดวงกำเนิด', 'size' => 14, 'color' => $gold, 'h' => 24],
            ['text' => mb_substr($this->fontSafeText((string) $d['name'], 'เจ้าชะตา'), 0, 18), 'size' => 24, 'color' => $textDark, 'h' => 38],
            ['divider' => true, 'h' => 14],
            ['text' => 'เกิดวัน'.$d['dayOfWeek'], 'size' => 16, 'color' => $purple, 'h' => 26],
        ];
        if ($d['calendarDayOfWeek'] !== null) {
            // 🔎 ข้ามย่ำรุ่ง = ต้องบอกตามตรง (ลูกค้าจำว่าตัวเองเกิดอีกวัน) — ตรงกับคำเตือนในผังข้อความ
            $lines[] = ['text' => "(ปฏิทินคือวัน{$d['calendarDayOfWeek']} · เกิดก่อนย่ำรุ่ง 06:00)", 'size' => 11, 'color' => $textGray, 'h' => 18];
        }
        $lines[] = [
            'text' => $d['birthDate'].($d['birthTime'] !== null ? " · {$d['birthTime']} น." : ' · ไม่ทราบเวลาเกิด'),
            'size' => 14, 'color' => $textGray, 'h' => 22,
        ];
        if ($d['basis'] === 'lagna') {
            // ลัคนาขึ้นกับพิกัด — ไม่รู้จังหวัดต้องบอกเหมือนผังข้อความ
            $lines[] = ['text' => $d['place'] ?? 'ไม่ทราบจังหวัดเกิด · ใช้พิกัดกรุงเทพฯ', 'size' => 11, 'color' => $textGray, 'h' => 18];
        }
        $lines[] = ['ruler' => true, 'h' => 66];
        $lines[] = ['text' => 'ดาวเจ้าชนะ: '.$d['mainPlanet'], 'size' => 14, 'color' => $mainColor, 'h' => 22];
        $lines[] = ['text' => 'มิตร: '.(implode(' ', $d['friends']) ?: '-'), 'size' => 12, 'color' => $green, 'h' => 20];
        $lines[] = ['text' => 'ศัตรู: '.(implode(' ', $d['enemies']) ?: '-'), 'size' => 12, 'color' => $red, 'h' => 20];
        $lines[] = ['text' => 'ราศีเกิด: '.$zodiacTh, 'size' => 12, 'color' => $textDark, 'h' => 22];
        $lines[] = ['text' => $basisTitle, 'size' => 15, 'color' => $purpleDark, 'h' => 26];
        $lines[] = ['text' => $basisNote, 'size' => 11, 'color' => $textGray, 'h' => 18];

        $y = $cy - array_sum(array_column($lines, 'h')) / 2;
        foreach ($lines as $line) {
            $lineMid = $y + $line['h'] / 2;
            if (! empty($line['divider'])) {
                imagesetthickness($img, 2);
                imageline($img, (int) ($cx - 60), (int) $lineMid, (int) ($cx + 60), (int) $lineMid, $purpleLight);
                imagesetthickness($img, 1);
            } elseif (! empty($line['ruler'])) {
                imagefilledellipse($img, (int) $cx, (int) $lineMid, 58, 58, $mainColor);
                imagesetthickness($img, 3);
                imageellipse($img, (int) $cx, (int) $lineMid, 58, 58, $white);
                imagesetthickness($img, 1);
                $this->drawCenteredText($img, $font, 24, $cx, $lineMid - 1, (string) $d['mainPlanetNumeral'], $white);
            } else {
                $this->drawCenteredText($img, $font, $line['size'], $cx, $lineMid, $line['text'], $line['color']);
            }
            $y += $line['h'];
        }

        // === หัวเรื่อง + ท้ายรูป ===
        $this->drawCenteredText($img, $font, 26, $cx, 40, 'หมอจันทราพยากรณ์', $goldDark);
        $this->drawCenteredText($img, $font, 13, $cx, 72, 'โหราศาสตร์ไทย · ตำแหน่งดาวจริง 10 ดวง · ระบบนิรายนะ', $purple);
        $this->drawCenteredText($img, $font, 11, $cx, $height - 16, 'หมอจันทราพยากรณ์ | thaiprompt.online', $textMuted);

        ob_start();
        imagepng($img, null, 7);
        $pngData = ob_get_clean();
        imagedestroy($img);

        return $pngData;
    }

    /**
     * ตัดอักขระที่ฟอนต์ไทยในรีโปไม่มี (อีโมจิ/อักษรลาว/จีน ฯลฯ ในชื่อเฟซบุ๊ก) — ไม่งั้นขึ้นเป็นกล่องสี่เหลี่ยม
     *
     * NotoSansThai ครอบคลุมอักษรไทย + ASCII + Latin-1 (ตรวจ cmap 2026-09-12) · เหลือว่าง = ใช้ $fallback
     */
    protected function fontSafeText(string $text, string $fallback): string
    {
        $clean = (string) preg_replace('/[^\x{0E00}-\x{0E7F}\x{0020}-\x{007E}\x{00A0}-\x{00FF}]+/u', ' ', $text);
        $clean = trim((string) preg_replace('/\s+/u', ' ', $clean));

        return $clean !== '' ? $clean : $fallback;
    }

    /**
     * ตำแหน่งวางดาว n ดวงในช่องเดียว (กว้าง 30°) — วางแถวนอกก่อน แถวไม่พอค่อยย่อขนาด
     *
     * ผังจริงมีดาวกองราศีเดียวกันได้หลายดวง (อาทิตย์-พุธ-ศุกร์ ห่างกันไม่เกิน ~48° เสมอ)
     * ของเดิมเรียงดาวซ้อนกันตามรัศมีห่างแค่ 18px บนวง 46px ⇒ 3 ดวงขึ้นไปทับกันอ่านไม่ออก
     *
     * @return array<int, array{r: float, a: float, scale: float}> a = มุม (เรเดียน)
     */
    protected function natalPlanetSlots(int $count, float $midDeg, float $rMin, float $rMax): array
    {
        if ($count <= 0) {
            return [];
        }

        // ช่องละ 56px ทั้งสองแนว — ก้อน "วง + ชื่อ" สูง ~54px ช่องบน/ล่างของวงเรียงแถวตามแนวตั้ง
        $span = deg2rad(30);
        $scales = [1.0, 0.82, 0.68, 0.56];
        $rows = [];
        $cell = 56.0;
        $scale = 1.0;
        foreach ($scales as $scale) {
            $cell = 56.0 * $scale;
            $rowCount = max(1, (int) floor(($rMax - $rMin) / $cell));
            $rows = [];
            for ($k = 0; $k < $rowCount; $k++) {
                $r = $rMax - $cell / 2 - $k * $cell;
                $rows[] = ['r' => $r, 'cap' => max(1, (int) floor(($r * $span - 6) / $cell))];
            }
            if (array_sum(array_column($rows, 'cap')) >= $count) {
                break;
            }
        }

        $slots = [];
        $left = $count;
        $lastRow = count($rows) - 1;
        foreach ($rows as $k => $row) {
            // ย่อสุดแล้วยังไม่พอ → อัดส่วนที่เหลือลงแถวในสุด (เบียดกันดีกว่าดาวหายจากรูป)
            $m = $k === $lastRow ? $left : min($left, $row['cap']);
            for ($j = 0; $j < $m; $j++) {
                $slots[] = [
                    'r' => $row['r'],
                    'a' => deg2rad($midDeg) + ($j - ($m - 1) / 2) * ($cell / $row['r']),
                    'scale' => $scale,
                ];
            }
            $left -= $m;
            if ($left <= 0) {
                break;
            }
        }

        return $slots;
    }

    /**
     * วาดดาว 1 ดวง: วงสีดาว + เลขไทยตรงกลาง + ชื่อดาวใต้วง
     *
     * ($x, $y) = กึ่งกลางของ "วง + ชื่อ" ทั้งก้อน — ไม่ใช่กึ่งกลางวง
     * ⇒ ก้อนสูงเท่ากันทั้งบน/ล่างจุดวาง ช่องครึ่งล่างของวงชื่อดาวจะไม่ยื่นไปชนป้ายภพ
     */
    protected function drawNatalPlanetBadge($img, string $font, float $x, float $y, array $p, float $scale, int $white, int $shadow, int $textColor): void
    {
        $d = (int) round(30 * $scale);
        $ring = $d + (int) round(6 * $scale);
        $color = $this->hexColor($img, (string) $p['color']);
        $by = $y - 8 * $scale;

        imagefilledellipse($img, (int) $x + 2, (int) $by + 2, $ring, $ring, $shadow);
        imagefilledellipse($img, (int) $x, (int) $by, $ring, $ring, $white);
        imagefilledellipse($img, (int) $x, (int) $by, $d, $d, $color);

        // "?" = ราศีของดาวดวงนี้ยังไม่แน่ (จันทร์ในวันที่จันทร์ย้ายราศี + ไม่รู้เวลาเกิด) — ตรงกับคำเตือนในผังข้อความ
        $label = (string) $p['name'].(! empty($p['uncertain']) ? '?' : '');

        $this->drawCenteredText($img, $font, 14 * $scale, $x, $by - 1, (string) $p['numeral'], $white);
        $this->drawCenteredText($img, $font, max(8.0, 10.5 * $scale), $x, $by + $ring / 2 + 9 * $scale, $label, $textColor);
    }

    /**
     * หา path ฟอนต์ภาษาไทย
     *
     * เช็คด้วย FontFile::isReal() ไม่ใช่ file_exists() — ไฟล์ที่มีอยู่แต่ไม่ใช่ฟอนต์ (เช่นหน้า HTML ที่โหลดผิดมา)
     * ต้องถูกข้ามไป fallback ตัวถัดไป ไม่ใช่ถูกคืนไปให้ GD วาดว่างเปล่าเงียบๆ
     */
    protected function getThaiFont(): string
    {
        // ✅ ใช้ฟอนต์ใน resources/ เป็นหลัก (หลีกเลี่ยง system paths ที่ถูก open_basedir บล็อค)
        $localFont = resource_path('fonts/NotoSansThai-Bold.ttf');
        if (\App\Support\FontFile::isReal($localFont)) {
            return $localFont;
        }

        // fallback: ลอง system paths (suppress warning เพื่อป้องกัน open_basedir error)
        $systemPaths = [
            '/usr/share/fonts/truetype/noto/NotoSansThai-Bold.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansThai-Regular.ttf',
            '/usr/share/fonts/truetype/tlwg/TlwgTypo-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        ];

        $systemFont = \App\Support\FontFile::firstReal($systemPaths);
        if ($systemFont !== null) {
            return $systemFont;
        }

        // fallback สุดท้าย — ใช้ path ใน resources เสมอ (อาจไม่มีจริง แต่ดีกว่า system path ที่ถูกบล็อค)
        return $localFont;
    }

    /**
     * หา path ฟอนต์สำหรับ Unicode symbols (☉☽♂☿♃♀♄☊☋)
     *
     * 🚨 (2026-09-12) resources/fonts/DejaVuSans.ttf เคยเป็นหน้า HTML 404 ตั้งแต่ 2026-02-16
     *    file_exists() ผ่าน ⇒ คืน path นี้ ⇒ สัญลักษณ์ดาวในรูปไม่เคยขึ้น — เปลี่ยนเป็นไฟล์ DejaVu Sans 2.37 ของจริงแล้ว
     *    และเช็คด้วย FontFile::isReal() เพื่อให้ไฟล์เสียข้ามไป fallback แทนการวาดว่างเปล่า
     */
    protected function getSymbolFont(): string
    {
        // ✅ ใช้ DejaVuSans เป็นหลัก — รองรับ Unicode astrological symbols (☉☽♂☿♃♀♄☊☋) ครบ
        $dejaVu = resource_path('fonts/DejaVuSans.ttf');
        if (\App\Support\FontFile::isReal($dejaVu)) {
            return $dejaVu;
        }

        // fallback: ลอง system paths (suppress warning เพื่อป้องกัน open_basedir)
        $systemFont = \App\Support\FontFile::firstReal([
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/freefont/FreeSans.ttf',
        ]);
        if ($systemFont !== null) {
            return $systemFont;
        }

        // fallback สุดท้าย → NotoSansThai (symbols อาจแสดงไม่ครบ)
        return resource_path('fonts/NotoSansThai-Bold.ttf');
    }

    /**
     * สร้างสีจาก hex string
     */
    protected function hexColor($img, string $hex): int
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return imagecolorallocate($img, $r, $g, $b);
    }

    /**
     * สร้างสีจาก hex + alpha (0=opaque, 127=transparent)
     */
    protected function hexColorAlpha($img, string $hex, int $alpha): int
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return imagecolorallocatealpha($img, $r, $g, $b, $alpha);
    }

    /**
     * วาดข้อความจัดกลาง (centered) ด้วย TTF
     *
     * ⚠️ ใช้ @ เพื่อ suppress warning จาก imagettfbbox/imagettftext
     * เพราะ Laravel HandleExceptions แปลง warning → ErrorException ทำให้ chart ล้มเหลวทั้งหมด
     * ถ้า imagettfbbox อ่านไม่ได้ → fallback ใช้ imagettftext ตรงๆ (ไม่ center)
     */
    protected function drawCenteredText($img, string $font, float $size, float $x, float $y, string $text, int $color): void
    {
        // ข้ามถ้า font ไม่มี
        if (empty($font) || ! @file_exists($font)) {
            return;
        }

        $bbox = @imagettfbbox($size, 0, $font, $text);
        if ($bbox !== false) {
            // จัดกลางได้ → คำนวณตำแหน่ง
            $textWidth = $bbox[2] - $bbox[0];
            $textHeight = $bbox[1] - $bbox[7];
            $drawX = $x - $textWidth / 2;
            $drawY = $y + $textHeight / 2;
        } else {
            // imagettfbbox ล้มเหลว → วาดตรงจุดเลย (ไม่ center แต่ดีกว่าไม่มีข้อความ)
            $drawX = $x;
            $drawY = $y;
        }

        @imagettftext($img, $size, 0, (int) $drawX, (int) $drawY, $color, $font, $text);
    }

    /**
     * วาดวงกลม (ไม่ fill) ด้วยเส้นหนา
     */
    protected function drawCircle($img, float $cx, float $cy, float $r, int $color, int $thickness = 1): void
    {
        imagesetthickness($img, $thickness);
        imagearc($img, (int) $cx, (int) $cy, (int) ($r * 2), (int) ($r * 2), 0, 360, $color);
        imagesetthickness($img, 1);
    }

    /**
     * สร้าง SVG chart (ใช้สำหรับ admin preview ในเบราว์เซอร์)
     *
     * @param  array  $chartData  ข้อมูล chart
     * @return string SVG XML
     */
    protected function buildSvgChart(array $chartData): string
    {
        $width = 800;
        $height = 800;
        $cx = $width / 2;
        $cy = $height / 2;
        $outerR = 340;
        $innerR = 220;
        $centerR = 130;

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$width} {$height}" width="{$width}" height="{$height}">
<defs>
    <radialGradient id="bg" cx="50%" cy="50%" r="50%">
        <stop offset="0%" style="stop-color:#FFFFFF"/>
        <stop offset="100%" style="stop-color:#F3F4F6"/>
    </radialGradient>
    <radialGradient id="glow" cx="50%" cy="50%" r="50%">
        <stop offset="0%" style="stop-color:#8B5CF6;stop-opacity:0.08"/>
        <stop offset="100%" style="stop-color:#8B5CF6;stop-opacity:0"/>
    </radialGradient>
    <filter id="shadow">
        <feDropShadow dx="0" dy="1" stdDeviation="2" flood-color="#6D28D9" flood-opacity="0.2"/>
    </filter>
    <filter id="textGlow">
        <feDropShadow dx="0" dy="0" stdDeviation="1" flood-color="#B45309" flood-opacity="0.4"/>
    </filter>
    <style>
        text { font-family: 'Noto Sans Thai', 'Sarabun', 'Prompt', 'Kanit', sans-serif; }
    </style>
</defs>

<!-- Background -->
<rect width="{$width}" height="{$height}" fill="url(#bg)" rx="20"/>

<!-- Stars decoration -->
SVG;

        // จุดประดับ (decorative) — ม่วงอ่อนบนพื้นขาว
        for ($i = 0; $i < 50; $i++) {
            $sx = rand(10, $width - 10);
            $sy = rand(10, $height - 10);
            $sr = rand(1, 3) * 0.5;
            $opacity = rand(5, 15) / 100;
            $svg .= "<circle cx=\"{$sx}\" cy=\"{$sy}\" r=\"{$sr}\" fill=\"#8B5CF6\" opacity=\"{$opacity}\"/>\n";
        }

        // Glow effect ตรงกลาง
        $svg .= "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$outerR}\" fill=\"url(#glow)\"/>\n";

        // วงกลมนอก (outer ring) — พื้นขาว เส้นม่วง
        $svg .= "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$outerR}\" fill=\"none\" stroke=\"#6D28D9\" stroke-width=\"2\" opacity=\"0.8\"/>\n";
        $svg .= "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$innerR}\" fill=\"none\" stroke=\"#7C3AED\" stroke-width=\"1.5\" opacity=\"0.6\"/>\n";
        $svg .= "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$centerR}\" fill=\"#F8F9FA\" stroke=\"#8B5CF6\" stroke-width=\"2\" filter=\"url(#shadow)\"/>\n";

        // วาดเส้นแบ่ง 12 ภพ
        for ($i = 0; $i < 12; $i++) {
            $angle = deg2rad($i * 30 - 90);
            $x1 = $cx + $innerR * cos($angle);
            $y1 = $cy + $innerR * sin($angle);
            $x2 = $cx + $outerR * cos($angle);
            $y2 = $cy + $outerR * sin($angle);
            $svg .= "<line x1=\"{$x1}\" y1=\"{$y1}\" x2=\"{$x2}\" y2=\"{$y2}\" stroke=\"#6D28D9\" stroke-width=\"1\" opacity=\"0.5\"/>\n";
        }

        // วาดชื่อภพ + ดาวในแต่ละภพ
        for ($i = 1; $i <= 12; $i++) {
            $house = self::HOUSES[$i];
            $angle = deg2rad(($i - 1) * 30 - 90 + 15); // กลางช่อง
            $midR = ($outerR + $innerR) / 2;

            // ชื่อภพ (วงนอก)
            $tx = $cx + ($outerR - 22) * cos(deg2rad(($i - 1) * 30 - 90 + 15));
            $ty = $cy + ($outerR - 22) * sin(deg2rad(($i - 1) * 30 - 90 + 15));
            $svg .= "<text x=\"{$tx}\" y=\"{$ty}\" text-anchor=\"middle\" dominant-baseline=\"middle\" fill=\"{$house['color']}\" font-size=\"13\" font-weight=\"bold\" opacity=\"0.9\">{$i}.{$house['name']}</text>\n";

            // ดาวเคราะห์ในภพ
            $planets = $chartData['planetPositions'][$i] ?? [];
            if (! empty($planets)) {
                $planetR = ($innerR + $centerR) / 2 + 15;
                $baseAngle = deg2rad(($i - 1) * 30 - 90 + 15);
                $planetCount = count($planets);

                foreach ($planets as $pIdx => $planetKey) {
                    $planet = self::PLANETS[$planetKey];
                    // กระจายดาวในช่องเดียวกัน
                    $offset = ($pIdx - ($planetCount - 1) / 2) * 8;
                    $px = $cx + ($planetR + $offset) * cos($baseAngle);
                    $py = $cy + ($planetR + $offset) * sin($baseAngle);

                    // วงกลมรอบดาว
                    $svg .= "<circle cx=\"{$px}\" cy=\"{$py}\" r=\"16\" fill=\"{$planet['color']}\" opacity=\"0.2\"/>\n";
                    $svg .= "<text x=\"{$px}\" y=\"".($py + 1)."\" text-anchor=\"middle\" dominant-baseline=\"middle\" fill=\"{$planet['color']}\" font-size=\"18\" font-weight=\"bold\">{$planet['symbol']}</text>\n";
                    // ชื่อดาว ด้านล่าง
                    $svg .= "<text x=\"{$px}\" y=\"".($py + 18)."\" text-anchor=\"middle\" fill=\"{$planet['color']}\" font-size=\"12\" opacity=\"0.9\">{$planet['name']}</text>\n";
                }
            }
        }

        // === ตรงกลาง: ข้อมูลผู้ใช้ ===
        $name = mb_substr($chartData['name'], 0, 15);

        if ($chartData['isFullChart']) {
            // Birth chart — สีเข้มบนพื้นขาว
            $mainColor = $chartData['mainPlanetColor'] ?? '#B45309';
            $svg .= "<text x=\"{$cx}\" y=\"".($cy - 55)."\" text-anchor=\"middle\" fill=\"#B45309\" font-size=\"15\" font-weight=\"bold\" filter=\"url(#textGlow)\">BIRTH CHART</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"".($cy - 33)."\" text-anchor=\"middle\" fill=\"#1F2937\" font-size=\"18\" font-weight=\"bold\">{$name}</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"".($cy - 13)."\" text-anchor=\"middle\" fill=\"#6D28D9\" font-size=\"14\">วัน{$chartData['dayOfWeek']}</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"".($cy + 7)."\" text-anchor=\"middle\" fill=\"#6B7280\" font-size=\"13\">{$chartData['birthDate']}</text>\n";

            // ดาวเจ้าชนะ
            $svg .= "<text x=\"{$cx}\" y=\"".($cy + 35)."\" text-anchor=\"middle\" fill=\"{$mainColor}\" font-size=\"30\" filter=\"url(#textGlow)\">{$chartData['mainPlanetSymbol']}</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"".($cy + 58)."\" text-anchor=\"middle\" fill=\"{$mainColor}\" font-size=\"13\" font-weight=\"bold\">ดาวเจ้าชนะ: {$chartData['mainPlanet']}</text>\n";

            // Legend มิตร/ศัตรู
            $friendNames = implode(' ', array_map(fn ($k) => self::PLANETS[$k]['name'], $chartData['chaochana']['friends']));
            $enemyNames = implode(' ', array_map(fn ($k) => self::PLANETS[$k]['name'], $chartData['chaochana']['enemies']));
            $svg .= "<text x=\"{$cx}\" y=\"".($cy + 78)."\" text-anchor=\"middle\" fill=\"#059669\" font-size=\"11\">มิตร: {$friendNames}</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"".($cy + 95)."\" text-anchor=\"middle\" fill=\"#DC2626\" font-size=\"11\">ศัตรู: {$enemyNames}</text>\n";
        } else {
            // Quick chart (ไม่มีวันเกิด) — สีเข้มบนพื้นขาว
            $svg .= "<text x=\"{$cx}\" y=\"".($cy - 50)."\" text-anchor=\"middle\" fill=\"#B45309\" font-size=\"15\" font-weight=\"bold\" filter=\"url(#textGlow)\">TRANSIT CHART</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"".($cy - 25)."\" text-anchor=\"middle\" fill=\"#1F2937\" font-size=\"18\" font-weight=\"bold\">{$name}</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"".($cy)."\" text-anchor=\"middle\" fill=\"#6D28D9\" font-size=\"13\">ดวงดาวโคจรขณะนี้</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"".($cy + 20)."\" text-anchor=\"middle\" fill=\"#6B7280\" font-size=\"12\">{$chartData['transitDate']}</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"".($cy + 55)."\" text-anchor=\"middle\" fill=\"#7C3AED\" font-size=\"32\" filter=\"url(#textGlow)\">\u{2728}</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"".($cy + 80)."\" text-anchor=\"middle\" fill=\"#6B7280\" font-size=\"11\">บอกวันเกิดเพื่อดู Birth Chart</text>\n";
        }

        // Title ด้านบน — สีเข้มบนพื้นขาว
        $svg .= "<text x=\"{$cx}\" y=\"30\" text-anchor=\"middle\" fill=\"#B45309\" font-size=\"22\" font-weight=\"bold\" filter=\"url(#textGlow)\">~~ หมอจันทราพยากรณ์ ~~</text>\n";
        $svg .= "<text x=\"{$cx}\" y=\"55\" text-anchor=\"middle\" fill=\"#6D28D9\" font-size=\"12\">โหราศาสตร์เจ้าชนะ | ดวงดาว 9 ดวง | ภพ 12 ภพ</text>\n";

        // Footer
        $svg .= "<text x=\"{$cx}\" y=\"".($height - 20)."\" text-anchor=\"middle\" fill=\"#9CA3AF\" font-size=\"10\">หมอจันทราพยากรณ์ | Powered by Xman Studio</text>\n";

        $svg .= '</svg>';

        return $svg;
    }

    /**
     * บันทึก SVG เป็นไฟล์ภาพ SVG แล้ว return URL
     *
     * @param  string  $svg  SVG XML
     * @param  string  $prefix  prefix ชื่อไฟล์
     * @return string|null URL ของภาพ
     */
    protected function saveChartAsImage(string $pngData, string $prefix): ?string
    {
        try {
            $filename = "{$prefix}-".Str::random(8).'.png';
            $path = "fortune/charts/{$filename}";

            Storage::disk('public')->put($path, $pngData);

            $url = Storage::disk('public')->url($path);

            Log::debug('FortuneChart: บันทึก chart สำเร็จ', [
                'path' => $path,
                'url' => $url,
                'size' => strlen($pngData),
            ]);

            return $url;
        } catch (\Throwable $e) {
            Log::error('FortuneChart: Failed to save chart image', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'prefix' => $prefix,
            ]);

            return null;
        }
    }

    /**
     * ลบ chart เก่า (เรียกจาก scheduler)
     *
     * @param  int  $daysOld  จำนวนวันที่เก่ากว่านี้จะถูกลบ
     * @return int จำนวนไฟล์ที่ลบ
     */
    public function cleanupOldCharts(int $daysOld = 7): int
    {
        $deleted = 0;
        $files = Storage::disk('public')->files('fortune/charts');
        $cutoff = now()->subDays($daysOld)->timestamp;

        foreach ($files as $file) {
            if (Storage::disk('public')->lastModified($file) < $cutoff) {
                Storage::disk('public')->delete($file);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            Log::info("FortuneChart: Cleaned up {$deleted} old chart files");
        }

        return $deleted;
    }
}
