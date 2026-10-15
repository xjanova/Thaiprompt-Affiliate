<?php

namespace App\Services;

use App\Services\Fortune\ThaiAstrologyService;
use App\Support\ThaiFontText;
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
            $planets = array_values($chartData['planetPositions'][$i] ?? []);
            if (! empty($planets)) {
                $planetR = ($innerR + $centerR) / 2 + 22;
                // 🧩 (2026-09-12) ดาวกองภพเดียวกัน — เดิมเยื้องกันแค่ 18px ตามแนวรัศมี วงดาว 46px เลยทับกันเกือบมิด
                //   quick chart มีดาวกองภพเดียวกันแทบทุกวัน (วัด 2024-2031: กอง 2 ดวง 64% · 3 ดวง 27% · 4-5 ดวง 5%)
                $slots = $this->planetSlotsInHouse(count($planets), $planetR);

                foreach ($planets as $pIdx => $planetKey) {
                    $planet = self::PLANETS[$planetKey];
                    $planetColor = $this->hexColor($img, $planet['color']);
                    $slot = $slots[$pIdx];
                    $slotAngle = $midAngle + deg2rad($slot['angle']);
                    $px = $cx + $slot['radius'] * cos($slotAngle);
                    $py = $cy + $slot['radius'] * sin($slotAngle);
                    $scale = $slot['scale'];
                    $ringSize = (int) round(46 * $scale);
                    $fillSize = (int) round(40 * $scale);

                    // 🌟 Planet badge ใหญ่ขึ้น: เงานุ่ม → กรอบขาว → fill สีดาว → symbol ขาว
                    // 1. เงานุ่ม (drop shadow)
                    imagefilledellipse($img, (int) $px + 2, (int) $py + 2, $ringSize, $ringSize, $shadowSoft);
                    // 2. กรอบขาว (white ring)
                    imagefilledellipse($img, (int) $px, (int) $py, $ringSize, $ringSize, $whiteOpaque);
                    // 3. fill สีดาว (full saturation)
                    imagefilledellipse($img, (int) $px, (int) $py, $fillSize, $fillSize, $planetColor);

                    // สัญลักษณ์ดาว (ขาว — contrast ดี)
                    $this->drawCenteredText($img, $symbolFont, 24 * $scale, $px, $py - 2 * $scale, $planet['symbol'], $whiteOpaque);

                    // ชื่อดาว (ภาษาไทย) — ใต้ดวงดาว สีเข้ม contrast ดีกับพื้น cream
                    $this->drawCenteredText($img, $thaiFont, 13 * $scale, $px, $py + 26 * $scale, $planet['name'], $textDark);
                }
            }
        }

        // 🔤 (2026-09-12) ทุกข้อความที่วาดด้วย $thaiFont ต้องมีแต่ตัวที่ NotoSansThai มีจริง (ไทย/ASCII/Latin-1)
        //   เดิมมี ✦ ✨ (ขึ้นเป็นกล่อง) และ 💚💔🌙 (GD ถอดเป็น "ð���") — ประกายวาดเป็นรูปทรงแทน · อีโมจิตัดทิ้ง
        //   ชื่อลูกค้ากรองผ่าน ThaiFontText::safe() · ล็อกด้วย ChartPngTextCoverageTest

        // === ตรงกลาง: ข้อมูลผู้ใช้ ===
        $name = mb_substr(ThaiFontText::safe((string) $chartData['name'], 'เจ้าชะตา'), 0, 15);

        if ($chartData['isFullChart']) {
            $mainColor = $this->hexColor($img, $chartData['mainPlanetColor'] ?? '#D97706');

            $this->drawCenteredTextWithSparkles($img, $thaiFont, 16, $cx, $cy - 110, 'BIRTH CHART', $gold);
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
            $this->drawCenteredText($img, $thaiFont, 13, $cx, $cy + 92, "มิตร: {$friendNames}", $green);
            $this->drawCenteredText($img, $thaiFont, 13, $cx, $cy + 114, "ศัตรู: {$enemyNames}", $red);
        } else {
            // Quick chart (ไม่มีวันเกิด)
            $this->drawCenteredTextWithSparkles($img, $thaiFont, 16, $cx, $cy - 90, 'TRANSIT CHART', $gold);
            $this->drawCenteredText($img, $thaiFont, 26, $cx, $cy - 50, $name, $textDark);

            imagesetthickness($img, 2);
            imageline($img, (int) ($cx - 60), (int) ($cy - 30), (int) ($cx + 60), (int) ($cy - 30), $purpleLight);
            imagesetthickness($img, 1);

            $this->drawCenteredText($img, $thaiFont, 15, $cx, $cy - 8, 'ดวงดาวโคจรขณะนี้', $purple);
            $this->drawCenteredText($img, $thaiFont, 14, $cx, $cy + 16, $chartData['transitDate'], $textGray);
            $this->drawCenteredTextWithSparkles($img, $thaiFont, 13, $cx, $cy + 70, 'บอกวันเกิดเพื่อดู Birth Chart', $purpleLight);
        }

        // === หัวเรื่องด้านบน ===
        $this->drawCenteredTextWithSparkles($img, $thaiFont, 30, $cx, 42, 'หมอจันทราพยากรณ์', $goldDark);
        $this->drawCenteredText($img, $thaiFont, 14, $cx, 72, 'โหราศาสตร์เจ้าชนะ · ดวงดาว 9 ดวง · ภพ 12 ภพ', $purple);

        // === Footer ===
        $this->drawCenteredText($img, $thaiFont, 12, $cx, $height - 22, 'หมอจันทราพยากรณ์ | thaiprompt.online', $textMuted);

        // === Export PNG ===
        ob_start();
        imagepng($img, null, 7);
        $pngData = ob_get_clean();
        imagedestroy($img);

        return $pngData;
    }

    // ─── 🖼️ ผังดวงกำเนิด (buildNatalPngChart) — เรขาคณิตวัดจากเทมเพลต AI จริง (2026-10-11) ───
    //   เปลี่ยนไฟล์เทมเพลตเมื่อไหร่ ต้องวัดค่าชุดนี้ใหม่ทั้งหมด (สแกนพิกเซลสีทองตามแนวรัศมี + ฟิตวงกลม)

    /** เทมเพลตพื้นภาพ (resource_path) — กรอบทองลายไทย · วงล้อ 12 ช่อง · วงกลาง · กล่องล่าง · โลโก้จันทรา ไม่มีข้อความ */
    protected const NATAL_TEMPLATE = 'images/fortune/natal-chart-template.jpg';

    /** ไอคอนดาว (AI · เหรียญทองลายไทย 160px พื้นโปร่งใส) — resource_path + "/{key}.png" · key ตาม natalChartData */
    protected const NATAL_ICON_DIR = 'images/fortune/planets';

    protected const NATAL_W = 1440;

    protected const NATAL_H = 2160;

    /** ศูนย์กลางวงล้อ (ฟิตจากขอบในวงแหวนทอง) */
    protected const NATAL_CX = 720;

    protected const NATAL_CY = 985;

    /** รัศมีขอบในวงแหวนทอง (ค่าต่ำสุดจาก 12 ช่อง) — ป้าย/ดาวต้องอยู่ด้านในเส้นนี้ */
    protected const NATAL_RING_INNER = 556;

    /** วงกลางกลางผัง (ไม่ตรงศูนย์วงล้อเป๊ะ เยื้อง ~5px) */
    protected const NATAL_DISC = ['x' => 714, 'y' => 981, 'r' => 155];

    /** มุมเส้นแบ่งช่องจริง (องศาจากทิศเหนือ ตามเข็ม) · ช่อง i อยู่ระหว่าง [i-1] กับ [i] — AI วาดคลาดจาก 30° ได้ ≤ 1.3° */
    public const NATAL_DIVIDERS = [-1.3, 28.3, 59.0, 89.9, 120.3, 150.9, 181.4, 211.2, 240.7, 270.1, 299.6, 329.4, 358.7];

    /** พื้นที่เขียนข้อความในกล่องล่าง (ด้านในกรอบทอง) */
    protected const NATAL_PANEL = ['x1' => 250, 'x2' => 1190, 'y1' => 1795, 'y2' => 1970];

    /** ขนาดไฟล์สูงสุด — LINE ใช้ URL เดียวกันเป็น previewImageUrl ซึ่งรับไม่เกิน 1MB */
    public const NATAL_MAX_BYTES = 950_000;

    /**
     * ขนาดตัวอักษร/ป้ายดาว (ขนาดเต็ม) — ภาพ 1440px ถูกย่อเหลือ ~400px บนจอมือถือ ⇒ ต้องใหญ่กว่าผังเดิมมาก
     *   ป้ายช่อง: เลข+ชื่อภพ 36pt · ราศี 24pt
     *   ดาว = ป้ายแคปซูล [ไอคอน 56px | ชื่อดาว 24pt] (owner: "ใช้สัญลักษณ์แทนเลขไทย เจนเป็นรูปมาตัดวาง")
     *     เดิมวงกลม+ชื่อใต้วง กินที่สูง ~100px ⇒ ภพที่ดาวกองต้องย่อจนเล็ก — แคปซูลสูงแค่ 56px วางซ้อนได้แน่นกว่า
     */
    public const NATAL_SIZES = [
        'title_pt' => 36, 'sub_pt' => 24, 'label_gap' => 4,
        'pill_h' => 56, 'pill_gap' => 8, 'pill_pad' => 16, 'name_pt' => 24,
    ];

    /** ขนาดป้ายช่องที่ลอง — ช่องที่ดาวแน่นย่อป้ายช่องก่อน ถ้าทำให้ป้ายดาวใหญ่ขึ้นได้ (ป้ายช่อง 36pt ใหญ่เกินจำเป็นอยู่แล้ว) */
    protected const NATAL_LABEL_SCALES = [1.0, 0.8, 0.66];

    /** ขนาดป้ายดาวที่ลองไล่ลง — ใช้ขนาดใหญ่สุดที่วางครบทุกดวงโดยไม่ทับป้าย/ดาวดวงอื่น/เส้นแบ่งช่อง */
    protected const NATAL_PLANET_SCALES = [1.0, 0.92, 0.84, 0.76, 0.68, 0.6, 0.52, 0.44];

    /** ระยะเว้น (px): ห่างเส้นแบ่งช่อง · ห่างขอบในวงแหวน · ห่างวงกลาง · ห่างป้าย/ดาวด้วยกัน */
    protected const NATAL_GAPS = ['divider' => 8, 'ring' => 8, 'disc' => 10, 'block' => 6];

    /** @var array<string, \GdImage|false> ไอคอนดาวที่โหลดแล้ว (false = ไม่มีไฟล์) */
    protected array $natalIcons = [];

    /**
     * 🖼️ วาดรูปผังดวงกำเนิดจริง (JPEG 1440×2160) จาก natalChartData()
     *
     * 🎨 (2026-10-11, owner: "ตัวหนังสืออ่านง่ายกว่านี้ ไม่ทับกัน อะไรต่ออะไรเล็กไป ทำให้สวยขึ้น
     *    ให้ AI เจนภาพหลักก่อน ส่งผังเก่าให้ดูแล้วลบข้อมูลออก แล้วค่อยเอารายละเอียดมาใส่ทีหลัง")
     *   พื้นภาพ = เทมเพลตตายตัว NATAL_TEMPLATE (AI ออกแบบใหม่จากผังเดิมแล้วลบข้อมูลออก + กรอบทองลายไทย ·
     *   กล่องข้อมูลล่าง · โลโก้จันทรา) — ไม่มีข้อความ/ดาวในเทมเพลตเลย ข้อมูลทุกตัววาดด้วยโค้ดทับ
     *   ดาวใช้ไอคอน AI (NATAL_ICON_DIR) แทนเลขไทย · ไฟล์หายถอยไปวงสี+เลขไทยแบบเดิม
     *   เดิม 1000×1000 PNG: ป้ายภพ 11-13pt · ชื่อดาว 10.5pt · ข้อมูลเกิด 11 บรรทัดอัดอยู่กลางวง
     *   ใหม่: ดู NATAL_SIZES · กลางวงเหลือแค่ดาวเจ้าชนะ · ข้อมูลเกิดย้ายลงกล่องล่าง
     *   ตำแหน่งทุกชิ้นมาจาก natalLayout() — เทสต์ NatalChartLayoutTest ตรวจว่าไม่มีชิ้นไหนทับกันในผังจริง
     *   ⚠️ เส้นแบ่งช่องในเทมเพลต AI วาดเอง ไม่ตรง 30° เป๊ะ (คลาด ≤ 1.3°) → วางป้าย/ดาวตามมุมที่วัดจริง NATAL_DIVIDERS
     *   ⚠️ JPEG ≤ NATAL_MAX_BYTES — LINE ใช้ URL เดียวกันเป็น previewImageUrl (≤ 1MB)
     *
     * วงนอก 12 ช่อง เดินตามเข็มนาฬิกาจากบนสุด:
     *   - ผังมีฐานนับภพ (ลัคนา/จันทร์ลัคน์) → ช่อง = ภพ 1-12 · เลขภพ + ชื่อภพ + ราศีของภพ
     *   - ฐาน 'none' → ช่อง = ราศี เมษ…มีน · **ไม่มีเลข/ชื่อภพเลย** และกล่องล่างบอกว่าไม่ทราบเวลาเกิด
     *   ⚠️ ฟอนต์ไทยไม่มีอีโมจิ/✦ ⇒ ห้ามใส่ในข้อความรูปนี้
     *   ⚠️ ข้อความทุกตัวต้องผ่าน drawCenteredText (เทสต์ ChartPngTextCoverageTest ดักตรวจฟอนต์ที่นั่น)
     *
     * @param  array  $d  ผลจาก natalChartData()
     * @return string JPEG binary data
     */
    protected function buildNatalPngChart(array $d): string
    {
        $cx = self::NATAL_CX;
        $sz = self::NATAL_SIZES;

        $img = $this->natalTemplateCanvas();
        $font = $this->getThaiFont();

        $cream = imagecolorallocate($img, 248, 238, 216);
        $gold = imagecolorallocate($img, 240, 204, 120);
        $goldMuted = imagecolorallocate($img, 226, 192, 126);
        $lavender = imagecolorallocate($img, 208, 194, 255);
        $muted = imagecolorallocate($img, 200, 190, 226);
        $mint = imagecolorallocate($img, 150, 235, 182);
        $rose = imagecolorallocate($img, 255, 172, 188);

        // === หัวภาพ (ใต้โลโก้ในเทมเพลต) ===
        $name = mb_substr(ThaiFontText::safe((string) $d['name'], 'เจ้าชะตา'), 0, 24);
        $this->drawShadowText($img, $font, 19, $cx, 266, 'ผังดวงกำเนิด', $gold);
        $this->drawShadowText($img, $font, $this->fitFontSize($font, 36, $name, 1000, 20), $cx, 318, $name, $cream);

        // === ป้ายช่อง + ดาว (ตำแหน่งทั้งหมดจาก natalLayout) ===
        $layout = $this->natalLayout($d, $font);
        foreach ($layout['labels'] as $l) {
            $titlePt = $sz['title_pt'] * $l['scale'];
            $titleY = $l['sub'] !== '' ? $l['y'] - $l['h'] / 2 + $l['title_h'] / 2 : $l['y'];
            if ($l['house'] !== null) {
                // สองสีในบรรทัดเดียว: เลขภพ (ทอง) + ชื่อภพ (ครีม) — วางทีละท่อนจากซ้าย
                $num = (string) $l['house'];
                $gap = 12 * $l['scale'];
                $numW = $this->textWidth($font, $titlePt, $num);
                $titleW = $this->textWidth($font, $titlePt, $l['title']);
                $startX = $l['x'] - ($numW + $gap + $titleW) / 2;
                $this->drawShadowText($img, $font, $titlePt, $startX + $numW / 2, $titleY, $num, $gold);
                $this->drawShadowText($img, $font, $titlePt, $startX + $numW + $gap + $titleW / 2, $titleY, $l['title'], $cream);
            } else {
                $this->drawShadowText($img, $font, $titlePt, $l['x'], $titleY, $l['title'], $cream);
            }
            if ($l['sub'] !== '') {
                $this->drawShadowText($img, $font, $sz['sub_pt'] * $l['scale'], $l['x'], $l['y'] + $l['h'] / 2 - $l['sub_h'] / 2, $l['sub'], $goldMuted);
            }
        }
        foreach ($layout['planets'] as $pl) {
            $this->drawNatalPlanetPill($img, $font, $pl, $cream);
        }

        // === วงกลาง: ดาวเจ้าชนะ (ไอคอนใหญ่) ===
        $dx = self::NATAL_DISC['x'];
        $dy = self::NATAL_DISC['y'];
        $this->drawShadowText($img, $font, 20, $dx, $dy - 100, 'ดาวเจ้าชนะ', $gold);
        $rulerKey = $d['mainPlanetKey'] ?? $this->natalPlanetKeyByName($d, (string) $d['mainPlanet']);
        if (! $this->drawNatalIcon($img, $rulerKey, $dx, $dy - 10, 124)) {
            $mainColor = $this->hexColor($img, $d['mainPlanetColor'] ?? '#D97706');
            imagefilledellipse($img, $dx, $dy - 10, 116, 116, $gold);
            imagefilledellipse($img, $dx, $dy - 10, 104, 104, $mainColor);
            $this->drawCenteredText($img, $font, 44, $dx, $dy - 12, (string) $d['mainPlanetNumeral'], imagecolorallocate($img, 255, 255, 255));
        }
        $this->drawShadowText($img, $font, $this->fitFontSize($font, 28, (string) $d['mainPlanet'], 260, 16), $dx, $dy + 94, (string) $d['mainPlanet'], $cream);

        // === กล่องล่าง: ข้อมูลเกิด (ซ้าย) · ฐานผัง + มิตร/ศัตรู (ขวา) ===
        $zodiacTh = explode(' ', trim((string) $d['zodiac']))[0];
        [$basisTitle, $basisNote] = match ($d['basis']) {
            'lagna' => ["ลัคนาราศี{$d['anchor']}", 'ภพนับจากลัคนา'],
            'moon' => ["จันทร์ลัคน์ราศี{$d['anchor']}", 'ไม่ทราบเวลาเกิด · ภพนับจากจันทร์ลัคน์'],
            // 🕰️ บอกเหตุผลตรง ๆ ว่าทำไมไม่มีลัคนา/ภพ — ลูกค้าเห็นวงนอกเป็นราศีแล้วนึกว่าผังขาดไป
            default => ['ไม่แสดงลัคนาและภพ', "ไม่ทราบเวลาเกิด · ใส่ลัคนาจะคลาดเคลื่อน\nวงนอกจึงเป็นราศี (บอกเวลาเกิดเพื่อดูภพได้)"],
        };

        $left = [['เกิดวัน'.$d['dayOfWeek'], 30, $lavender]];
        $left[] = [$d['birthDate'].($d['birthTime'] !== null ? " · {$d['birthTime']} น." : ' · ไม่ทราบเวลาเกิด'), 25, $cream];
        if ($d['calendarDayOfWeek'] !== null) {
            // 🔎 ข้ามย่ำรุ่ง = ต้องบอกตามตรง (ลูกค้าจำว่าตัวเองเกิดอีกวัน) — ตรงกับคำเตือนในผังข้อความ
            $left[] = ["(ปฏิทินคือวัน{$d['calendarDayOfWeek']} · เกิดก่อนย่ำรุ่ง 06:00)", 19, $muted];
        }
        if ($d['basis'] === 'lagna') {
            // ลัคนาขึ้นกับพิกัด — ไม่รู้จังหวัดต้องบอกเหมือนผังข้อความ
            $left[] = [$d['place'] ?? 'ไม่ทราบจังหวัดเกิด · ใช้พิกัดกรุงเทพฯ', 21, $muted];
        }
        $left[] = ['ราศีเกิด: '.$zodiacTh, 25, $cream];

        $right = [
            [$basisTitle, 30, $gold],
            ...array_map(fn (string $line): array => [$line, 20, $muted], explode("\n", $basisNote)),
            ['มิตร: '.(implode(' ', $d['friends']) ?: '-'), 25, $mint],
            ['ศัตรู: '.(implode(' ', $d['enemies']) ?: '-'), 25, $rose],
        ];

        $panel = self::NATAL_PANEL;
        $colW = (int) (($panel['x2'] - $panel['x1']) / 2) - 20;
        $this->drawNatalPanelColumn($img, $font, $panel['x1'] + ($panel['x2'] - $panel['x1']) / 4, $left, $colW);
        $this->drawNatalPanelColumn($img, $font, $panel['x1'] + ($panel['x2'] - $panel['x1']) * 3 / 4, $right, $colW);

        $jpeg = $this->encodeJpegWithin($img, self::NATAL_MAX_BYTES);
        imagedestroy($img);

        return $jpeg;
    }

    /**
     * ตำแหน่งป้ายช่อง 12 ช่อง + ป้ายดาวทุกดวงในวงล้อ (แยกจากการวาด ให้เทสต์ตรวจการทับกันได้โดยไม่ต้องเรนเดอร์)
     *
     * @param  array  $d  ผลจาก natalChartData()
     * @return array{
     *   labels: array<int, array{house: ?int, title: string, sub: string, x: float, y: float, w: float, h: float, title_h: float, sub_h: float, sector: int}>,
     *   planets: array<int, array{key: string, planet: array, x: float, y: float, w: float, h: float, scale: float, box: array, sector: int}>
     * } (x,y) = กึ่งกลางก้อน · box = [x1,y1,x2,y2] · sector = ช่อง 1-12
     */
    public function natalLayout(array $d, string $font): array
    {
        $sz = self::NATAL_SIZES;

        // ไม่มีการวางดาวลงภพ = ฐาน 'none' → วงนอกเป็นราศี
        $houseMode = ! empty($d['planetPositions']);
        $order = (array) config('thai_astrology_knowledge.zodiac_order', []);

        $labels = [];
        $planets = [];
        for ($i = 1; $i <= 12; $i++) {
            $from = self::NATAL_DIVIDERS[$i - 1];
            $to = self::NATAL_DIVIDERS[$i];
            $mid = ($from + $to) / 2;

            if ($houseMode) {
                $house = $i;
                $title = self::HOUSES[$i]['name'];
                $sub = 'ราศี'.($d['houseSigns'][$i] ?? '');
                $keys = $d['planetPositions'][$i] ?? [];
            } else {
                $house = null;
                $title = (string) ($order[$i - 1] ?? '');
                $sub = '';
                $keys = $d['signPositions'][$title] ?? [];
            }

            $ps = [];
            foreach ($keys as $key) {
                if (isset($d['planets'][$key])) {
                    $ps[$key] = $d['planets'][$key];
                }
            }

            // ป้ายช่องเต็มขนาดก่อน · ดาวในช่องต้องย่อ → ลองย่อป้ายช่องแทน เลือกแบบที่ป้ายดาวใหญ่กว่า
            $best = null;
            foreach (self::NATAL_LABEL_SCALES as $ls) {
                $label = $this->natalLabelBox($font, $house, $title, $sub, $from, $to, $ls) + ['sector' => $i];
                $placed = $this->natalPlacePlanets($ps, $from, $to, $label['box'], $font);
                $pillScale = $placed[0]['scale'] ?? 1.0;
                if ($best === null || $pillScale > $best[2]) {
                    $best = [$label, $placed, $pillScale];
                }
                if ($pillScale >= 1.0) {
                    break;
                }
            }

            $labels[] = $best[0];
            foreach ($best[1] as $pl) {
                $planets[] = $pl + ['sector' => $i];
            }
        }

        return ['labels' => $labels, 'planets' => $planets];
    }

    /**
     * ป้ายช่อง (เลข+ชื่อภพ / ราศี) ที่อัตราย่อ $ls — ชิดวงแหวนมากที่สุดเท่าที่มุมกรอบป้ายยังอยู่ในช่อง
     *
     * @return array{house: ?int, title: string, sub: string, x: float, y: float, w: float, h: float, title_h: float, sub_h: float, scale: float, box: array}
     */
    protected function natalLabelBox(string $font, ?int $house, string $title, string $sub, float $from, float $to, float $ls): array
    {
        $sz = self::NATAL_SIZES;
        $titlePt = $sz['title_pt'] * $ls;
        $subPt = $sz['sub_pt'] * $ls;

        // ขนาดป้ายจากข้อความจริง — บรรทัดสูงตามขนาดฟอนต์ ไม่ใช่ตาม bbox (ตัวที่มีสระบน/ล่างสูงไม่เท่ากัน)
        $titleW = $this->textWidth($font, $titlePt, ($house !== null ? $house.'  ' : '').$title);
        $titleH = $titlePt * 1.4;
        $subH = $sub !== '' ? $subPt * 1.4 : 0.0;
        $w = max($titleW, $sub !== '' ? $this->textWidth($font, $subPt, $sub) : 0.0);
        $h = $titleH + ($sub !== '' ? $sz['label_gap'] * $ls + $subH : 0.0);

        // ถอยเข้าทีละ 2px จนมุมกรอบป้ายอยู่ในช่อง
        $mid = ($from + $to) / 2;
        $r = self::NATAL_RING_INNER - $h / 2;
        do {
            [$x, $y] = $this->natalPolar($r, $mid);
            $box = [$x - $w / 2, $y - $h / 2, $x + $w / 2, $y + $h / 2];
            $r -= 2;
        } while ($r > self::NATAL_DISC['r'] && ! $this->natalBoxFree($box, $from, $to, [-9e9, -9e9, -9e9, -9e9], []));

        return [
            'house' => $house, 'title' => $title, 'sub' => $sub, 'x' => $x, 'y' => $y,
            'w' => $w, 'h' => $h, 'title_h' => $titleH, 'sub_h' => $subH, 'scale' => $ls, 'box' => $box,
        ];
    }

    /**
     * วางป้ายดาวทุกดวงของช่องหนึ่ง (ดาวกองช่องเดียวกันได้ถึง 6-7 ดวง)
     *
     * วัดจากผังจริง 8,800 ผัง (ทุก 5 วัน 1950-2010 · มี/ไม่มีเวลาเกิด): ภพที่มีดาว ≥ 3 ดวงพบ ~67% ของผัง
     * ⇒ ไล่หาที่ว่างทั้งช่องโดยเริ่มจากจุดใต้ป้ายช่อง (ใกล้สุดก่อน → ป้ายดาวเกาะกลุ่มกันเป็นระเบียบ)
     *   ที่ใต้ป้ายเต็มค่อยขยับไปมุมข้างป้ายใกล้วงแหวน (ช่องกว้างสุดตรงนั้น) · วางไม่ครบค่อยย่อลงหนึ่งขั้น
     *   ดวงกว้างวางก่อน (ช่องแคบรับดวงยาวไม่ได้ ดวงสั้นแทรกช่องว่างที่เหลือได้)
     *
     * @param  array<string, array>  $ps  key ดาว => ข้อมูลดาว
     * @param  array{0: float, 1: float, 2: float, 3: float}  $labelBox  กรอบป้ายช่อง [x1, y1, x2, y2]
     * @return array<int, array{key: string, planet: array, x: float, y: float, w: float, h: float, scale: float, box: array, forced?: bool}>
     *                                                                                                                                        forced = วางไม่ลงทุกขนาดแล้ว เรียงแบบยอมเบียด (เทสต์ NatalChartLayoutTest ห้ามเจอในผังจริง)
     */
    protected function natalPlacePlanets(array $ps, float $fromDeg, float $toDeg, array $labelBox, string $font): array
    {
        if ($ps === []) {
            return [];
        }

        $mid = ($fromDeg + $toDeg) / 2;
        $labelInner = hypot(($labelBox[0] + $labelBox[2]) / 2 - self::NATAL_CX, ($labelBox[1] + $labelBox[3]) / 2 - self::NATAL_CY)
            - ($labelBox[3] - $labelBox[1]) / 2;
        $candidates = $this->natalCandidateSpots($fromDeg, $toDeg);

        foreach (self::NATAL_PLANET_SCALES as $scale) {
            $sizes = [];
            foreach ($ps as $key => $p) {
                $sizes[$key] = $this->natalPillSize($font, $p, $scale);
            }
            // ดวงกว้างก่อน (คงลำดับเดิมเมื่อกว้างเท่ากัน)
            $keys = array_keys($sizes);
            usort($keys, fn ($a, $b) => $sizes[$b]['w'] <=> $sizes[$a]['w']); // usort คงลำดับเดิมเมื่อเท่ากัน (PHP 8)

            // จุดตั้งต้น = ใต้ป้ายช่องพอดี
            [$ax, $ay] = $this->natalPolar($labelInner - self::NATAL_GAPS['block'] - $sizes[$keys[0]]['h'] / 2, $mid);
            $dist = [];
            foreach ($candidates as $c => [$x, $y]) {
                $dist[$c] = ($x - $ax) ** 2 + ($y - $ay) ** 2;
            }
            asort($dist);

            $placed = [];
            $out = [];
            foreach ($keys as $key) {
                $size = $sizes[$key];
                $spot = null;
                foreach (array_keys($dist) as $c) {
                    [$x, $y] = $candidates[$c];
                    $box = [$x - $size['w'] / 2, $y - $size['h'] / 2, $x + $size['w'] / 2, $y + $size['h'] / 2];
                    if ($this->natalBoxFree($box, $fromDeg, $toDeg, $labelBox, $placed)) {
                        $spot = [$x, $y, $box];
                        break;
                    }
                }
                if ($spot === null) {
                    continue 2; // ขนาดนี้วางไม่ครบ → ย่อลง
                }
                $placed[] = $spot[2];
                $out[] = ['key' => $key, 'planet' => $ps[$key], 'x' => $spot[0], 'y' => $spot[1], 'w' => $size['w'], 'h' => $size['h'], 'scale' => $scale, 'box' => $spot[2]];
            }

            return $out;
        }

        // เล็กสุดแล้วยังวางไม่ครบ (ไม่เจอในผังจริง) → เรียงตามแนวกลางช่องจากใต้ป้ายเข้าใน ยอมเบียด ดีกว่าดาวหายจากรูป
        $scale = self::NATAL_PLANET_SCALES[count(self::NATAL_PLANET_SCALES) - 1];
        $out = [];
        $r = $labelInner - self::NATAL_GAPS['block'];
        foreach ($ps as $key => $p) {
            $size = $this->natalPillSize($font, $p, $scale);
            $r -= $size['h'] / 2;
            [$x, $y] = $this->natalPolar($r, $mid);
            $out[] = ['key' => $key, 'planet' => $p, 'x' => $x, 'y' => $y, 'w' => $size['w'], 'h' => $size['h'], 'scale' => $scale,
                'box' => [$x - $size['w'] / 2, $y - $size['h'] / 2, $x + $size['w'] / 2, $y + $size['h'] / 2], 'forced' => true];
            $r -= $size['h'] / 2 + 2;
        }

        return $out;
    }

    /**
     * จุดที่ลองวางป้ายดาวได้ในช่อง (ตารางเชิงขั้ว ห่างกัน ~6px ทั้งแนวรัศมีและแนวโค้ง)
     *
     * @return array<int, array{0: float, 1: float}>
     */
    protected function natalCandidateSpots(float $fromDeg, float $toDeg): array
    {
        static $cache = [];
        $cacheKey = $fromDeg.'|'.$toDeg;
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $step = 6.0;
        $spots = [];
        for ($r = self::NATAL_RING_INNER - 20; $r > self::NATAL_DISC['r'] + 20; $r -= $step) {
            $arc = deg2rad($toDeg - $fromDeg) * $r;
            for ($s = $step / 2; $s < $arc; $s += $step) {
                $spots[] = $this->natalPolar($r, $fromDeg + rad2deg($s / $r));
            }
        }

        return $cache[$cacheKey] = $spots;
    }

    /**
     * ขนาดป้ายแคปซูลดาว [ไอคอน | ชื่อ] ที่อัตราย่อ $scale
     *
     * @return array{w: float, h: float}
     */
    protected function natalPillSize(string $font, array $p, float $scale): array
    {
        $sz = self::NATAL_SIZES;
        $h = $sz['pill_h'] * $scale;
        $nameW = $this->textWidth($font, $this->natalPillFont($scale), $this->natalPlanetLabel($p));

        return ['w' => $h + $sz['pill_gap'] * $scale + $nameW + $sz['pill_pad'] * $scale, 'h' => $h];
    }

    protected function natalPillFont(float $scale): float
    {
        return max(12.0, self::NATAL_SIZES['name_pt'] * $scale);
    }

    /**
     * กรอบ [x1,y1,x2,y2] อยู่ในช่อง (ห่างเส้นแบ่ง · ใต้วงแหวน · นอกวงกลาง) และไม่ทับป้าย/กรอบอื่น
     *
     * @param  array<int, array{0: float, 1: float, 2: float, 3: float}>  $others
     */
    public function natalBoxFree(array $box, float $fromDeg, float $toDeg, array $labelBox, array $others): bool
    {
        $g = self::NATAL_GAPS;
        $span = $toDeg - $fromDeg;

        // ทับป้าย/กรอบอื่นเช็คก่อน (ถูกสุด และตกบ่อยสุด)
        foreach (array_merge([$labelBox], $others) as $o) {
            if ($box[0] < $o[2] + $g['block'] && $o[0] < $box[2] + $g['block'] && $box[1] < $o[3] + $g['block'] && $o[1] < $box[3] + $g['block']) {
                return false;
            }
        }

        foreach ([[$box[0], $box[1]], [$box[2], $box[1]], [$box[0], $box[3]], [$box[2], $box[3]]] as [$px, $py]) {
            $dx = $px - self::NATAL_CX;
            $dy = $py - self::NATAL_CY;
            $dist = hypot($dx, $dy);
            if ($dist > self::NATAL_RING_INNER - $g['ring']) {
                return false;
            }
            // มุมจากทิศเหนือ ตามเข็ม เทียบกับเส้นแบ่งด้านซ้ายของช่อง (ช่อง 1/12 คร่อม 0°)
            $rel = fmod(rad2deg(atan2($dx, -$dy)) - $fromDeg + 720.0, 360.0);
            if ($rel > $span || $dist * sin(deg2rad($rel)) < $g['divider'] || $dist * sin(deg2rad($span - $rel)) < $g['divider']) {
                return false;
            }
        }

        // ระยะใกล้สุดจากจุดศูนย์วงกลางถึงกรอบ
        $ex = max($box[0] - self::NATAL_DISC['x'], 0, self::NATAL_DISC['x'] - $box[2]);
        $ey = max($box[1] - self::NATAL_DISC['y'], 0, self::NATAL_DISC['y'] - $box[3]);

        return hypot($ex, $ey) >= self::NATAL_DISC['r'] + $g['disc'];
    }

    /**
     * พิกัดจุดบนวงล้อ จากรัศมี + มุม (องศาจากทิศเหนือ ตามเข็ม)
     *
     * @return array{0: float, 1: float}
     */
    protected function natalPolar(float $r, float $deg): array
    {
        $a = deg2rad($deg);

        return [self::NATAL_CX + $r * sin($a), self::NATAL_CY - $r * cos($a)];
    }

    /**
     * ผ้าใบผังดวงกำเนิด = เทมเพลต AI (resources/images/fortune/natal-chart-template.jpg)
     *
     * ไฟล์หาย/เปิดไม่ได้ → วาดพื้นสำรองเรขาคณิตเดียวกัน (ม่วงเข้ม + วงทอง + เส้นแบ่ง + กล่องล่าง) ให้รูปยังออกได้
     */
    protected function natalTemplateCanvas(): \GdImage
    {
        $path = resource_path(self::NATAL_TEMPLATE);
        $img = is_file($path) ? @imagecreatefromjpeg($path) : false;
        if ($img instanceof \GdImage && imagesx($img) === self::NATAL_W && imagesy($img) === self::NATAL_H) {
            imagealphablending($img, true);

            return $img;
        }

        Log::warning('FortuneChart: ไม่พบเทมเพลตผังดวงกำเนิด — ใช้พื้นสำรอง', ['path' => $path]);

        $img = imagecreatetruecolor(self::NATAL_W, self::NATAL_H);
        imagealphablending($img, true);
        imagefill($img, 0, 0, imagecolorallocate($img, 28, 14, 56));
        $gold = imagecolorallocate($img, 214, 172, 92);
        $this->drawCircle($img, self::NATAL_CX, self::NATAL_CY, self::NATAL_RING_INNER + 50, $gold, 6);
        $this->drawCircle($img, self::NATAL_CX, self::NATAL_CY, self::NATAL_RING_INNER, $gold, 3);
        $this->drawCircle($img, self::NATAL_DISC['x'], self::NATAL_DISC['y'], self::NATAL_DISC['r'], $gold, 3);
        foreach (array_slice(self::NATAL_DIVIDERS, 0, 12) as $deg) {
            [$x1, $y1] = $this->natalPolar(self::NATAL_DISC['r'], $deg);
            [$x2, $y2] = $this->natalPolar(self::NATAL_RING_INNER, $deg);
            imageline($img, (int) $x1, (int) $y1, (int) $x2, (int) $y2, $gold);
        }
        $p = self::NATAL_PANEL;
        imagerectangle($img, $p['x1'] - 30, $p['y1'] - 10, $p['x2'] + 30, $p['y2'] + 10, $gold);

        return $img;
    }

    /**
     * คอลัมน์ข้อความในกล่องล่าง — เกลี่ยบรรทัดให้เต็มความสูงกล่อง · ย่อฟอนต์บรรทัดที่ยาวเกินคอลัมน์
     *
     * @param  array<int, array{0: string, 1: float, 2: int}>  $lines  [ข้อความ, ขนาด pt, สี]
     */
    protected function drawNatalPanelColumn($img, string $font, float $cx, array $lines, int $maxWidth): void
    {
        $panel = self::NATAL_PANEL;
        $weights = array_map(fn (array $l): float => $l[1] * 1.6, $lines);
        $scale = min(1.0, ($panel['y2'] - $panel['y1']) / array_sum($weights));

        $y = $panel['y1'] + (($panel['y2'] - $panel['y1']) - array_sum($weights) * $scale) / 2;
        foreach ($lines as $k => [$text, $size, $color]) {
            $h = $weights[$k] * $scale;
            $fitted = $this->fitFontSize($font, $size * $scale, $text, $maxWidth, 12);
            $this->drawShadowText($img, $font, $fitted, $cx, $y + $h / 2, $text, $color);
            $y += $h;
        }
    }

    /**
     * ข้อความมีเงา (เข้ม เยื้อง 2px) ให้อ่านออกบนพื้นภาพลวดลาย — ทั้งสองชั้นผ่าน drawCenteredText
     */
    protected function drawShadowText($img, string $font, float $size, float $x, float $y, string $text, int $color): void
    {
        $this->drawCenteredText($img, $font, $size, $x + 2, $y + 2, $text, imagecolorallocatealpha($img, 8, 2, 20, 30));
        $this->drawCenteredText($img, $font, $size, $x, $y, $text, $color);
    }

    /**
     * ความกว้างข้อความ (px) — วัดจากตัวที่วาดจริง (วรรณยุกต์ที่ยกลอยกว้าง 0)
     */
    protected function textWidth(string $font, float $size, string $text): float
    {
        if (empty($font) || ! @file_exists($font)) {
            return 0.0;
        }

        [$base] = ThaiFontText::liftStackedToneMarks($text);
        $bb = @imagettfbbox($size, 0, $font, $base);

        return $bb === false ? 0.0 : (float) (max($bb[2], $bb[4]) - min($bb[0], $bb[6]));
    }

    /**
     * ย่อขนาดฟอนต์ทีละ 0.5pt จนข้อความกว้างไม่เกิน $maxWidth (ไม่ต่ำกว่า $min)
     */
    protected function fitFontSize(string $font, float $size, string $text, float $maxWidth, float $min): float
    {
        while ($size > $min && $this->textWidth($font, $size, $text) > $maxWidth) {
            $size -= 0.5;
        }

        return $size;
    }

    /**
     * เข้ารหัส JPEG ลดคุณภาพทีละขั้นจนไม่เกิน $maxBytes (LINE previewImageUrl ≤ 1MB)
     */
    protected function encodeJpegWithin(\GdImage $img, int $maxBytes): string
    {
        $data = '';
        foreach ([88, 84, 80, 75, 70] as $quality) {
            ob_start();
            imagejpeg($img, null, $quality);
            $data = (string) ob_get_clean();
            if (strlen($data) <= $maxBytes) {
                break;
            }
        }

        return $data;
    }

    /**
     * ชื่อดาวในป้าย — "?" = ราศีของดาวดวงนี้ยังไม่แน่ (จันทร์ในวันที่จันทร์ย้ายราศี + ไม่รู้เวลาเกิด) ตรงกับคำเตือนในผังข้อความ
     */
    protected function natalPlanetLabel(array $p): string
    {
        return (string) $p['name'].(! empty($p['uncertain']) ? '?' : '');
    }

    /** key ดาวจากชื่อไทย (ดาวเจ้าชนะส่งมาแค่ชื่อ) */
    protected function natalPlanetKeyByName(array $d, string $name): ?string
    {
        foreach ($d['planets'] ?? [] as $key => $p) {
            if (($p['name'] ?? null) === $name) {
                return (string) $key;
            }
        }

        return null;
    }

    /**
     * วาดไอคอนดาว (AI) เส้นผ่านศูนย์กลาง $size ที่จุดกึ่งกลาง — ไม่มีไฟล์คืน false ให้ผู้เรียกวาดแบบสำรอง
     */
    protected function drawNatalIcon($img, ?string $key, float $cx, float $cy, int $size): bool
    {
        if ($key === null) {
            return false;
        }
        if (! array_key_exists($key, $this->natalIcons)) {
            $path = resource_path(self::NATAL_ICON_DIR.'/'.basename($key).'.png');
            $this->natalIcons[$key] = is_file($path) ? (@imagecreatefrompng($path) ?: false) : false;
        }
        $icon = $this->natalIcons[$key];
        if (! $icon instanceof \GdImage) {
            return false;
        }

        // เงานุ่มใต้เหรียญ
        imagefilledellipse($img, (int) round($cx + 2), (int) round($cy + 3), $size + 4, $size + 4, imagecolorallocatealpha($img, 0, 0, 0, 80));
        imagecopyresampled($img, $icon, (int) round($cx - $size / 2), (int) round($cy - $size / 2), 0, 0, $size, $size, imagesx($icon), imagesy($icon));

        return true;
    }

    /**
     * ป้ายแคปซูลดาว: [ไอคอน | ชื่อดาว] พื้นม่วงเข้มโปร่ง ขอบสีประจำดาว — ($pl x,y,w,h จาก natalLayout)
     */
    protected function drawNatalPlanetPill($img, string $font, array $pl, int $textColor): void
    {
        $sz = self::NATAL_SIZES;
        $x1 = $pl['x'] - $pl['w'] / 2;
        $y1 = $pl['y'] - $pl['h'] / 2;
        $h = $pl['h'];

        $this->fillRoundedPill($img, $x1, $y1, $pl['w'], $h, (string) $pl['planet']['color']);

        $iconD = (int) round($h - 4);
        if (! $this->drawNatalIcon($img, $pl['key'], $x1 + $h / 2, $pl['y'], $iconD)) {
            // ไม่มีไอคอน → วงสีประจำดาว + เลขไทย (แบบผังเดิม)
            imagefilledellipse($img, (int) round($x1 + $h / 2), (int) round($pl['y']), $iconD, $iconD, $this->hexColor($img, (string) $pl['planet']['color']));
            $this->drawCenteredText($img, $font, 20 * $pl['scale'], $x1 + $h / 2, $pl['y'] - 1, (string) $pl['planet']['numeral'], imagecolorallocate($img, 255, 255, 255));
        }

        $label = $this->natalPlanetLabel($pl['planet']);
        $fontPt = $this->natalPillFont($pl['scale']);
        $nameX = $x1 + $h + $sz['pill_gap'] * $pl['scale'] + $this->textWidth($font, $fontPt, $label) / 2;
        $this->drawShadowText($img, $font, $fontPt, $nameX, $pl['y'], $label, $textColor);
    }

    /**
     * แคปซูลขอบเนียน (supersample ×3): ขอบสีประจำดาว 3px + พื้นม่วงเข้มโปร่ง
     */
    protected function fillRoundedPill($img, float $x, float $y, float $w, float $h, string $borderHex): void
    {
        $ss = 3;
        $W = (int) ceil($w) + 2;
        $H = (int) ceil($h) + 2;
        $big = imagecreatetruecolor($W * $ss, $H * $ss);
        imagealphablending($big, false);
        imagefilledrectangle($big, 0, 0, $W * $ss - 1, $H * $ss - 1, imagecolorallocatealpha($big, 0, 0, 0, 127));

        $hex = ltrim($borderHex, '#');
        $border = imagecolorallocatealpha($big, (int) hexdec(substr($hex, 0, 2)), (int) hexdec(substr($hex, 2, 2)), (int) hexdec(substr($hex, 4, 2)), 0);
        $fill = imagecolorallocatealpha($big, 22, 10, 44, 28);
        $pill = function (int $inset, int $color) use ($big, $W, $H, $ss): void {
            $x1 = $ss + $inset;
            $y1 = $ss + $inset;
            $x2 = ($W - 1) * $ss - $inset;
            $y2 = ($H - 1) * $ss - $inset;
            $d = $y2 - $y1;
            imagefilledellipse($big, $x1 + intdiv($d, 2), $y1 + intdiv($d, 2), $d, $d, $color);
            imagefilledellipse($big, $x2 - intdiv($d, 2), $y1 + intdiv($d, 2), $d, $d, $color);
            imagefilledrectangle($big, $x1 + intdiv($d, 2), $y1, $x2 - intdiv($d, 2), $y2, $color);
        };
        $pill(0, $border);
        $pill(3 * $ss, $fill);

        $small = imagecreatetruecolor($W, $H);
        imagealphablending($small, false);
        imagesavealpha($small, true);
        imagecopyresampled($small, $big, 0, 0, 0, 0, $W, $H, $W * $ss, $H * $ss);
        imagedestroy($big);
        imagecopy($img, $small, (int) round($x) - 1, (int) round($y) - 1, 0, 0, $W, $H);
        imagedestroy($small);
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

        // 🔤 (2026-10-11) วาดผ่าน ThaiFontText::draw — GD ไม่ทำ Thai shaping วรรณยุกต์บนสระบนเคยจมหาย
        //   (ชื่อลูกค้า "ชื่อ" ขึ้นเป็น "ชือ") · ตำแหน่ง/ความกว้างเท่าเดิม ต่างแค่วรรณยุกต์ลอยขึ้นมาให้เห็น
        @ThaiFontText::draw($img, $size, (int) $drawX, (int) $drawY, $color, $font, $text);
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
     * วาดข้อความจัดกลาง + ดาวประกาย 4 แฉกสองข้าง
     *
     * แทน ✦ ✨ ที่เคยพิมพ์ติดข้อความ — ฟอนต์ไทยไม่มีสองตัวนี้ เลยขึ้นเป็นกล่องสี่เหลี่ยม (2026-09-12)
     * ประกายวาดเป็นรูปทรงด้วย GD ไม่พึ่งฟอนต์ใดเลย ⇒ ไม่ขึ้นเป็นกล่องไม่ว่าเครื่องไหนมีฟอนต์อะไร
     */
    protected function drawCenteredTextWithSparkles($img, string $font, float $size, float $x, float $y, string $text, int $color): void
    {
        $this->drawCenteredText($img, $font, $size, $x, $y, $text, $color);

        $bbox = (! empty($font) && @file_exists($font)) ? @imagettfbbox($size, 0, $font, $text) : false;
        if ($bbox === false) {
            // วัดความกว้างข้อความไม่ได้ = ไม่รู้จะวางประกายตรงไหน → ข้ามไป (ตัวข้อความยังขึ้นตามปกติ)
            return;
        }

        $offset = ($bbox[2] - $bbox[0]) / 2 + $size * 0.9;
        $this->drawSparkle($img, $x - $offset, $y, $size * 0.5, $color);
        $this->drawSparkle($img, $x + $offset, $y, $size * 0.5, $color);
    }

    /**
     * ดาวประกาย 4 แฉก (หน้าตาแบบ ✦) — polygon 8 จุด สลับปลายแฉกกับเอว
     */
    protected function drawSparkle($img, float $cx, float $cy, float $r, int $color): void
    {
        $points = [];
        for ($i = 0; $i < 8; $i++) {
            $angle = deg2rad(-90 + $i * 45);
            $length = $i % 2 === 0 ? $r : $r * 0.3;
            $points[] = (int) round($cx + $length * cos($angle));
            $points[] = (int) round($cy + $length * sin($angle));
        }

        imagefilledpolygon($img, $points, $color);
    }

    /**
     * 🧩 ตำแหน่งดาวที่กองอยู่ภพเดียวกัน (buildPngChart) — วงดาวกับชื่อดาวต้องไม่ทับกัน
     *
     * - 1 ดวง = กลางภพ ขนาดเต็ม (หน้าตาเหมือนเดิม)
     * - 2 ดวง = เรียงคู่ตามแนวโค้ง ห่างกัน 68px ย่อเหลือ 90%
     * - 3-5 ดวง = 2 แถว (แถวนอกได้ครึ่งที่ปัดขึ้น) ย่อเหลือ 78% ห่างกันในแถว 56px (แถวละ 3 ดวง = 50px)
     * ระยะเผื่อชื่อดาวใต้วงไว้แล้ว: ภพเฉียง ชื่อยาว (พฤหัสบดี) ต้องไม่ชนวงอีกดวงที่อยู่เยื้องลงไป ·
     * ภพข้างซ้าย/ขวา แนวโค้งเป็นแนวตั้ง ชื่อดาวตัวบนต้องไม่ลงไปทับวงตัวล่าง (รวมภพข้างเคียงที่มี 2 ดวงเหมือนกัน)
     * แถวทั้งหมดอยู่ในแถบวงดาว (ระหว่างวงกลาง r=175 กับวงภพ r=275)
     *
     * @param  int  $count  จำนวนดาวในภพ
     * @param  float  $planetR  รัศมีวงดาวปกติของ buildPngChart
     * @return array<int, array{angle: float, radius: float, scale: float}> องศาที่เยื้องจากกลางภพ · รัศมี · อัตราย่อ
     */
    protected function planetSlotsInHouse(int $count, float $planetR): array
    {
        if ($count <= 1) {
            return [['angle' => 0.0, 'radius' => $planetR, 'scale' => 1.0]];
        }

        [$rows, $scale] = $count === 2
            ? [[[$planetR, 2]], 0.9]
            : [[[$planetR + 7, (int) ceil($count / 2)], [$planetR - 52, intdiv($count, 2)]], 0.78];

        $slots = [];
        foreach ($rows as [$radius, $perRow]) {
            // ระยะห่างตามแนวโค้ง: คู่ขนาด 90% = 68px · แถวละ 2 ดวง (ย่อ 78%) = 56px
            // แถวละ 3 ดวง (มีแค่ตอนกอง 5 ดวง) บีบเหลือ 50px ไม่งั้นแถวยาวล้นไปชนดาวภพข้างๆ
            $gap = $count === 2 ? 68.0 : ($perRow >= 3 ? 50.0 : 56.0);
            for ($k = 0; $k < $perRow; $k++) {
                // ระยะตามแนวโค้ง (px) → แปลงเป็นองศาที่รัศมีของแถวนั้น
                $arc = ($k - ($perRow - 1) / 2) * $gap;
                $slots[] = ['angle' => rad2deg($arc / $radius), 'radius' => $radius, 'scale' => $scale];
            }
        }

        return $slots;
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
     * บันทึกรูปผัง (PNG หรือ JPEG) ลง storage แล้ว return URL
     *
     * 🖼️ (2026-10-11) ผังดวงกำเนิดเปลี่ยนเป็น JPEG (พื้นภาพ AI) — นามสกุลไฟล์ดูจากไบต์จริง ไม่ฟิกซ์ .png
     *
     * @param  string  $pngData  ไบต์รูป (PNG จากผังแบบด่วน · JPEG จากผังดวงกำเนิด)
     * @param  string  $prefix  prefix ชื่อไฟล์
     * @return string|null URL ของภาพ
     */
    protected function saveChartAsImage(string $pngData, string $prefix): ?string
    {
        try {
            $ext = str_starts_with($pngData, "\xFF\xD8\xFF") ? '.jpg' : '.png';
            $filename = "{$prefix}-".Str::random(8).$ext;
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
     * ลบ chart เก่า
     *
     * ⚠️ (2026-09-12) จงใจไม่ต่อ scheduler — reading_image_url ของบิลเก่าชี้ไฟล์ในโฟลเดอร์นี้
     *    และยังถูกส่งซ้ำตอนลูกค้าขอดูคำทำนายล่าสุด/ส่งคำทำนายที่ค้าง ⇒ ลบ 7 วัน = รูปแตกในแชทเงียบๆ
     *    ขนาดจริงบน prod: ~1,950 ไฟล์ 186MB ใน 7 เดือน (ดิสก์ว่าง 189GB) — ถ้าจะลบ ต้องข้ามไฟล์ที่ DB ยังอ้างถึง
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
