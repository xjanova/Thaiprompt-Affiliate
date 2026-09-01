<?php

namespace App\Services;

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
        6 => ['planet' => 'saturn',  'friends' => ['rahu', 'jupiter'],    'enemies' => ['sun', 'mars'],       'element' => 'ดิน', 'lucky_color' => 'ม่วง'],
    ];

    /**
     * สร้าง birth chart จากวันเกิด
     *
     * @param  string  $birthDate  วันเกิด (Y-m-d)
     * @param  string  $name  ชื่อผู้ใช้
     * @param  string|null  $gender  เพศ
     * @return string|null URL ของภาพ chart หรือ null ถ้าเกิดข้อผิดพลาด
     */
    public function generateBirthChart(string $birthDate, string $name, ?string $gender = null): ?string
    {
        try {
            // ✅ เช็ค GD extension ก่อนสร้าง chart
            if (! extension_loaded('gd')) {
                Log::error('FortuneChart: GD extension ไม่ได้ติดตั้ง! ไม่สามารถสร้าง chart ได้', [
                    'birthDate' => $birthDate,
                    'name' => $name,
                    'php_version' => PHP_VERSION,
                    'loaded_extensions' => implode(', ', get_loaded_extensions()),
                ]);

                return null;
            }

            $date = Carbon::parse($birthDate);
            $dayOfWeek = $date->dayOfWeek;

            $planetPositions = $this->calculatePlanetPositions($dayOfWeek);
            $chaochana = self::CHAOCHANA[$dayOfWeek];
            $mainPlanet = self::PLANETS[$chaochana['planet']];

            $thaiDays = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
            $dayName = $thaiDays[$dayOfWeek];

            $chartData = [
                'name' => $name,
                'birthDate' => $date->format('d/m/').($date->year + 543),
                'dayOfWeek' => $dayName,
                'mainPlanet' => $mainPlanet['name'],
                'mainPlanetSymbol' => $mainPlanet['symbol'],
                'mainPlanetColor' => $mainPlanet['color'],
                'planetPositions' => $planetPositions,
                'chaochana' => $chaochana,
                'isFullChart' => true,
            ];

            $pngData = $this->buildPngChart($chartData);

            $url = $this->saveChartAsImage($pngData, "birth-chart-{$dayOfWeek}");

            Log::info('FortuneChart: สร้าง birth chart สำเร็จ', [
                'name' => $name,
                'birthDate' => $birthDate,
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
     *
     * @param  int  $dayOfWeek  0-6
     * @return array [house_number => [planet_keys]]
     */
    public function calculatePlanetPositions(int $dayOfWeek): array
    {
        $positions = array_fill(1, 12, []);

        // ดาวเจ้าชนะอยู่ภพตนุ (1) เสมอ
        $chaochana = self::CHAOCHANA[$dayOfWeek];
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
     * คำนวณดาวโคจร ณ วันที่กำหนด (transit)
     *
     * ใช้สูตรจัดวางดาวตามวันในปี โดยดาวแต่ละดวงมีความเร็วโคจรต่างกัน
     * - ดาวเร็ว (พุธ, ศุกร์, อาทิตย์, จันทร์) เปลี่ยนภพบ่อย
     * - ดาวช้า (เสาร์, พฤหัส, ราหู, เกตุ) อยู่ภพนานหลายเดือน
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
     * คำนวณ Transit ในอนาคตหลายช่วงเวลา เทียบกับดวงกำเนิด
     *
     * @param  int  $birthDayOfWeek  วันเกิด (0=อาทิตย์...6=เสาร์)
     * @param  array  $periods  ช่วงเวลาที่ต้องการ [['label' => 'ชื่อ', 'months' => จำนวนเดือน]]
     * @return array ข้อมูล transit แต่ละช่วง
     */
    public function calculateFutureTransits(int $birthDayOfWeek, array $periods = []): array
    {
        if (empty($periods)) {
            $periods = [
                ['label' => 'ปัจจุบัน', 'months' => 0],
                ['label' => 'อีก 1 เดือน', 'months' => 1],
                ['label' => 'อีก 3 เดือน', 'months' => 3],
                ['label' => 'อีก 6 เดือน', 'months' => 6],
                ['label' => 'อีก 12 เดือน', 'months' => 12],
            ];
        }

        $chaochana = self::CHAOCHANA[$birthDayOfWeek] ?? null;
        $now = Carbon::now('Asia/Bangkok');
        $results = [];

        foreach ($periods as $period) {
            $targetDate = $now->copy()->addMonths($period['months']);
            $positions = $this->calculateTransitForDate($targetDate);

            $periodData = [
                'label' => $period['label'],
                'months' => $period['months'],
                'date' => $targetDate->format('d/m/').($targetDate->year + 543),
                'positions' => $positions,
                'friend_impacts' => [],
                'enemy_impacts' => [],
            ];

            // วิเคราะห์ดาวมิตร/ศัตรูในแต่ละช่วง
            if ($chaochana) {
                foreach ($chaochana['friends'] as $friend) {
                    foreach ($positions as $houseNum => $planets) {
                        if (in_array($friend, $planets)) {
                            $periodData['friend_impacts'][] = [
                                'planet' => $friend,
                                'planet_name' => self::PLANETS[$friend]['name'] ?? $friend,
                                'house' => $houseNum,
                                'house_name' => self::HOUSES[$houseNum]['name'] ?? '',
                                'house_meaning' => self::HOUSES[$houseNum]['meaning'] ?? '',
                            ];
                        }
                    }
                }

                foreach ($chaochana['enemies'] as $enemy) {
                    foreach ($positions as $houseNum => $planets) {
                        if (in_array($enemy, $planets)) {
                            $periodData['enemy_impacts'][] = [
                                'planet' => $enemy,
                                'planet_name' => self::PLANETS[$enemy]['name'] ?? $enemy,
                                'house' => $houseNum,
                                'house_name' => self::HOUSES[$houseNum]['name'] ?? '',
                                'house_meaning' => self::HOUSES[$houseNum]['meaning'] ?? '',
                            ];
                        }
                    }
                }
            }

            $results[] = $periodData;
        }

        return $results;
    }

    /**
     * สร้าง PNG chart ด้วย GD — รองรับภาษาไทย + ใช้ได้ใน Facebook/LINE
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
     * หา path ฟอนต์ภาษาไทย
     */
    protected function getThaiFont(): string
    {
        // ✅ ใช้ฟอนต์ใน resources/ เป็นหลัก (หลีกเลี่ยง system paths ที่ถูก open_basedir บล็อค)
        $localFont = resource_path('fonts/NotoSansThai-Bold.ttf');
        if (@file_exists($localFont)) {
            return $localFont;
        }

        // fallback: ลอง system paths (suppress warning เพื่อป้องกัน open_basedir error)
        $systemPaths = [
            '/usr/share/fonts/truetype/noto/NotoSansThai-Bold.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansThai-Regular.ttf',
            '/usr/share/fonts/truetype/tlwg/TlwgTypo-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        ];

        foreach ($systemPaths as $path) {
            if (@file_exists($path)) {
                return $path;
            }
        }

        // fallback สุดท้าย — ใช้ path ใน resources เสมอ (อาจไม่มีจริง แต่ดีกว่า system path ที่ถูกบล็อค)
        return $localFont;
    }

    /**
     * หา path ฟอนต์สำหรับ Unicode symbols (☉☽♂☿♃♀♄)
     */
    protected function getSymbolFont(): string
    {
        // ✅ ใช้ DejaVuSans เป็นหลัก — รองรับ Unicode astrological symbols (☉☽♂☿♃♀♄) ครบ
        $dejaVu = resource_path('fonts/DejaVuSans.ttf');
        if (@file_exists($dejaVu)) {
            return $dejaVu;
        }

        // fallback: ลอง system paths (suppress warning เพื่อป้องกัน open_basedir)
        $systemPaths = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/freefont/FreeSans.ttf',
        ];

        foreach ($systemPaths as $path) {
            if (@file_exists($path)) {
                return $path;
            }
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
