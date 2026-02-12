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
        'sun'     => ['name' => 'อาทิตย์', 'symbol' => "\u{2609}", 'color' => '#FF6B35', 'day' => 0],
        'moon'    => ['name' => 'จันทร์',   'symbol' => "\u{263D}", 'color' => '#C0C0C0', 'day' => 1],
        'mars'    => ['name' => 'อังคาร',   'symbol' => "\u{2642}", 'color' => '#E74C3C', 'day' => 2],
        'mercury' => ['name' => 'พุธ',      'symbol' => "\u{263F}", 'color' => '#2ECC71', 'day' => 3],
        'jupiter' => ['name' => 'พฤหัสบดี', 'symbol' => "\u{2643}", 'color' => '#F39C12', 'day' => 4],
        'venus'   => ['name' => 'ศุกร์',    'symbol' => "\u{2640}", 'color' => '#3498DB', 'day' => 5],
        'saturn'  => ['name' => 'เสาร์',    'symbol' => "\u{2644}", 'color' => '#8E44AD', 'day' => 6],
        'rahu'    => ['name' => 'ราหู',     'symbol' => "\u{260A}", 'color' => '#34495E', 'day' => -1],
        'ketu'    => ['name' => 'เกตุ',     'symbol' => "\u{260B}", 'color' => '#95A5A6', 'day' => -2],
    ];

    // ภพ 12 ภพ
    public const HOUSES = [
        1  => ['name' => 'ตนุ',       'meaning' => 'ตัวตน',         'color' => '#E74C3C'],
        2  => ['name' => 'กดุมภ',     'meaning' => 'ทรัพย์',        'color' => '#F39C12'],
        3  => ['name' => 'สหัชชะ',    'meaning' => 'พี่น้อง',       'color' => '#2ECC71'],
        4  => ['name' => 'พันธุ',     'meaning' => 'ครอบครัว',      'color' => '#3498DB'],
        5  => ['name' => 'ปุตตะ',     'meaning' => 'ลูก/สร้างสรรค์', 'color' => '#9B59B6'],
        6  => ['name' => 'อริ',       'meaning' => 'ศัตรู/โรค',     'color' => '#E67E22'],
        7  => ['name' => 'ปัตนิ',     'meaning' => 'คู่ครอง',       'color' => '#E91E63'],
        8  => ['name' => 'มรณะ',      'meaning' => 'เปลี่ยนแปลง',   'color' => '#607D8B'],
        9  => ['name' => 'ศุภะ',      'meaning' => 'โชคลาภ',       'color' => '#FF9800'],
        10 => ['name' => 'กัมมะ',     'meaning' => 'การงาน',       'color' => '#795548'],
        11 => ['name' => 'ลาภะ',      'meaning' => 'ลาภผล',        'color' => '#4CAF50'],
        12 => ['name' => 'วินาศ',     'meaning' => 'อุปสรรค',      'color' => '#9E9E9E'],
    ];

    // ตารางเจ้าชนะตามวันเกิด
    public const CHAOCHANA = [
        0 => ['planet' => 'sun',     'friends' => ['jupiter', 'mars'],    'enemies' => ['saturn', 'rahu']],
        1 => ['planet' => 'moon',    'friends' => ['mercury', 'venus'],   'enemies' => ['rahu', 'saturn']],
        2 => ['planet' => 'mars',    'friends' => ['sun', 'jupiter'],     'enemies' => ['mercury', 'saturn']],
        3 => ['planet' => 'mercury', 'friends' => ['moon', 'venus'],      'enemies' => ['rahu', 'mars']],
        4 => ['planet' => 'jupiter', 'friends' => ['sun', 'mars'],        'enemies' => ['rahu', 'saturn']],
        5 => ['planet' => 'venus',   'friends' => ['mercury', 'moon'],    'enemies' => ['sun', 'mars']],
        6 => ['planet' => 'saturn',  'friends' => ['rahu', 'jupiter'],    'enemies' => ['sun', 'mars']],
    ];

    /**
     * สร้าง birth chart จากวันเกิด
     *
     * @param string $birthDate วันเกิด (Y-m-d)
     * @param string $name ชื่อผู้ใช้
     * @param string|null $gender เพศ
     * @return string|null URL ของภาพ chart หรือ null ถ้าเกิดข้อผิดพลาด
     */
    public function generateBirthChart(string $birthDate, string $name, ?string $gender = null): ?string
    {
        try {
            $date = Carbon::parse($birthDate);
            $dayOfWeek = $date->dayOfWeek;

            $planetPositions = $this->calculatePlanetPositions($dayOfWeek);
            $chaochana = self::CHAOCHANA[$dayOfWeek];
            $mainPlanet = self::PLANETS[$chaochana['planet']];

            $thaiDays = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
            $dayName = $thaiDays[$dayOfWeek];

            $chartData = [
                'name' => $name,
                'birthDate' => $date->format('d/m/') . ($date->year + 543),
                'dayOfWeek' => $dayName,
                'mainPlanet' => $mainPlanet['name'],
                'mainPlanetSymbol' => $mainPlanet['symbol'],
                'mainPlanetColor' => $mainPlanet['color'],
                'planetPositions' => $planetPositions,
                'chaochana' => $chaochana,
                'isFullChart' => true,
            ];

            $svg = $this->buildSvgChart($chartData);
            return $this->saveSvgAsImage($svg, "birth-chart-{$dayOfWeek}");

        } catch (\Exception $e) {
            Log::warning('FortuneChart: Failed to generate birth chart', [
                'error' => $e->getMessage(),
                'birthDate' => $birthDate,
            ]);
            return null;
        }
    }

    /**
     * สร้าง chart แบบด่วน (ไม่มีวันเกิด) - แสดงดวงดาวช่วงปัจจุบัน
     *
     * @param string $name ชื่อผู้ใช้
     * @return string|null URL ของภาพ chart
     */
    public function generateQuickChart(string $name): ?string
    {
        try {
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
                'transitDate' => $now->format('d/m/') . ($now->year + 543),
            ];

            $svg = $this->buildSvgChart($chartData);
            return $this->saveSvgAsImage($svg, "quick-chart");

        } catch (\Exception $e) {
            Log::warning('FortuneChart: Failed to generate quick chart', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * คำนวณตำแหน่งดาวในภพตามหลักเจ้าชนะ
     * ใช้วันเกิดเป็นฐาน → ดาวเจ้าชนะอยู่ภพตนุ(1) → ดาวอื่นกระจายตามลำดับ
     *
     * @param int $dayOfWeek 0-6
     * @return array [house_number => [planet_keys]]
     */
    protected function calculatePlanetPositions(int $dayOfWeek): array
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
        $positions = array_fill(1, 12, []);
        $now = Carbon::now('Asia/Bangkok');
        $dayOfYear = $now->dayOfYear;

        // กระจายดาวตามวันในปี (ง่ายแต่ดูเหมือนจริง)
        $planetKeys = array_keys(self::PLANETS);
        foreach ($planetKeys as $idx => $key) {
            $house = (($dayOfYear + $idx * 37) % 12) + 1;
            $positions[$house][] = $key;
        }

        return $positions;
    }

    /**
     * สร้าง SVG chart
     *
     * @param array $chartData ข้อมูล chart
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
        <stop offset="0%" style="stop-color:#1a0a3e"/>
        <stop offset="100%" style="stop-color:#0d0521"/>
    </radialGradient>
    <radialGradient id="glow" cx="50%" cy="50%" r="50%">
        <stop offset="0%" style="stop-color:#8B5CF6;stop-opacity:0.3"/>
        <stop offset="100%" style="stop-color:#8B5CF6;stop-opacity:0"/>
    </radialGradient>
    <filter id="shadow">
        <feDropShadow dx="0" dy="0" stdDeviation="3" flood-color="#8B5CF6" flood-opacity="0.5"/>
    </filter>
    <filter id="textGlow">
        <feDropShadow dx="0" dy="0" stdDeviation="2" flood-color="#FFD700" flood-opacity="0.8"/>
    </filter>
</defs>

<!-- Background -->
<rect width="{$width}" height="{$height}" fill="url(#bg)" rx="20"/>

<!-- Stars decoration -->
SVG;

        // วาดดาวกะพริบ (decorative stars)
        for ($i = 0; $i < 60; $i++) {
            $sx = rand(10, $width - 10);
            $sy = rand(10, $height - 10);
            $sr = rand(1, 3) * 0.5;
            $opacity = rand(3, 8) / 10;
            $svg .= "<circle cx=\"{$sx}\" cy=\"{$sy}\" r=\"{$sr}\" fill=\"white\" opacity=\"{$opacity}\"/>\n";
        }

        // Glow effect ตรงกลาง
        $svg .= "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$outerR}\" fill=\"url(#glow)\"/>\n";

        // วงกลมนอก (outer ring)
        $svg .= "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$outerR}\" fill=\"none\" stroke=\"#8B5CF6\" stroke-width=\"2\" opacity=\"0.8\"/>\n";
        $svg .= "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$innerR}\" fill=\"none\" stroke=\"#6D28D9\" stroke-width=\"1.5\" opacity=\"0.6\"/>\n";
        $svg .= "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$centerR}\" fill=\"#1a0a3e\" stroke=\"#A78BFA\" stroke-width=\"2\" filter=\"url(#shadow)\"/>\n";

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
            $svg .= "<text x=\"{$tx}\" y=\"{$ty}\" text-anchor=\"middle\" dominant-baseline=\"middle\" fill=\"{$house['color']}\" font-size=\"11\" font-weight=\"bold\" opacity=\"0.9\">{$i}.{$house['name']}</text>\n";

            // ดาวเคราะห์ในภพ
            $planets = $chartData['planetPositions'][$i] ?? [];
            if (!empty($planets)) {
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
                    $svg .= "<circle cx=\"{$px}\" cy=\"{$py}\" r=\"14\" fill=\"{$planet['color']}\" opacity=\"0.2\"/>\n";
                    $svg .= "<text x=\"{$px}\" y=\"" . ($py + 1) . "\" text-anchor=\"middle\" dominant-baseline=\"middle\" fill=\"{$planet['color']}\" font-size=\"16\" font-weight=\"bold\">{$planet['symbol']}</text>\n";
                    // ชื่อดาวเล็กๆ ด้านล่าง
                    $svg .= "<text x=\"{$px}\" y=\"" . ($py + 16) . "\" text-anchor=\"middle\" fill=\"{$planet['color']}\" font-size=\"7\" opacity=\"0.8\">{$planet['name']}</text>\n";
                }
            }
        }

        // === ตรงกลาง: ข้อมูลผู้ใช้ ===
        $name = mb_substr($chartData['name'], 0, 15);

        if ($chartData['isFullChart']) {
            // Birth chart
            $mainColor = $chartData['mainPlanetColor'] ?? '#FFD700';
            $svg .= "<text x=\"{$cx}\" y=\"" . ($cy - 50) . "\" text-anchor=\"middle\" fill=\"#FFD700\" font-size=\"13\" font-weight=\"bold\" filter=\"url(#textGlow)\">BIRTH CHART</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"" . ($cy - 30) . "\" text-anchor=\"middle\" fill=\"white\" font-size=\"16\" font-weight=\"bold\">{$name}</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"" . ($cy - 10) . "\" text-anchor=\"middle\" fill=\"#A78BFA\" font-size=\"12\">วัน{$chartData['dayOfWeek']}</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"" . ($cy + 10) . "\" text-anchor=\"middle\" fill=\"#D1D5DB\" font-size=\"11\">{$chartData['birthDate']}</text>\n";

            // ดาวเจ้าชนะ
            $svg .= "<text x=\"{$cx}\" y=\"" . ($cy + 35) . "\" text-anchor=\"middle\" fill=\"{$mainColor}\" font-size=\"28\" filter=\"url(#textGlow)\">{$chartData['mainPlanetSymbol']}</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"" . ($cy + 55) . "\" text-anchor=\"middle\" fill=\"{$mainColor}\" font-size=\"11\" font-weight=\"bold\">ดาวเจ้าชนะ: {$chartData['mainPlanet']}</text>\n";

            // Legend มิตร/ศัตรู
            $friendNames = implode(' ', array_map(fn($k) => self::PLANETS[$k]['name'], $chartData['chaochana']['friends']));
            $enemyNames = implode(' ', array_map(fn($k) => self::PLANETS[$k]['name'], $chartData['chaochana']['enemies']));
            $svg .= "<text x=\"{$cx}\" y=\"" . ($cy + 75) . "\" text-anchor=\"middle\" fill=\"#4ADE80\" font-size=\"9\">มิตร: {$friendNames}</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"" . ($cy + 90) . "\" text-anchor=\"middle\" fill=\"#F87171\" font-size=\"9\">ศัตรู: {$enemyNames}</text>\n";
        } else {
            // Quick chart (ไม่มีวันเกิด)
            $svg .= "<text x=\"{$cx}\" y=\"" . ($cy - 45) . "\" text-anchor=\"middle\" fill=\"#FFD700\" font-size=\"13\" font-weight=\"bold\" filter=\"url(#textGlow)\">TRANSIT CHART</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"" . ($cy - 20) . "\" text-anchor=\"middle\" fill=\"white\" font-size=\"16\" font-weight=\"bold\">{$name}</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"" . ($cy + 5) . "\" text-anchor=\"middle\" fill=\"#A78BFA\" font-size=\"11\">ดวงดาวโคจรขณะนี้</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"" . ($cy + 25) . "\" text-anchor=\"middle\" fill=\"#D1D5DB\" font-size=\"10\">{$chartData['transitDate']}</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"" . ($cy + 55) . "\" text-anchor=\"middle\" fill=\"#8B5CF6\" font-size=\"32\" filter=\"url(#textGlow)\">\u{2728}</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"" . ($cy + 80) . "\" text-anchor=\"middle\" fill=\"#C4B5FD\" font-size=\"9\">บอกวันเกิดเพื่อดู Birth Chart</text>\n";
        }

        // Title ด้านบน
        $svg .= "<text x=\"{$cx}\" y=\"30\" text-anchor=\"middle\" fill=\"#FFD700\" font-size=\"18\" font-weight=\"bold\" filter=\"url(#textGlow)\">~~ แม่หมอจันทรา ~~</text>\n";
        $svg .= "<text x=\"{$cx}\" y=\"50\" text-anchor=\"middle\" fill=\"#C4B5FD\" font-size=\"10\">โหราศาสตร์เจ้าชนะ | ดวงดาว 9 ดวง | ภพ 12 ภพ</text>\n";

        // Footer
        $svg .= "<text x=\"{$cx}\" y=\"" . ($height - 20) . "\" text-anchor=\"middle\" fill=\"#6B7280\" font-size=\"9\">holyzonethailand | Powered by AI Astrology</text>\n";

        $svg .= "</svg>";

        return $svg;
    }

    /**
     * บันทึก SVG เป็นไฟล์ภาพ SVG แล้ว return URL
     *
     * @param string $svg SVG XML
     * @param string $prefix prefix ชื่อไฟล์
     * @return string|null URL ของภาพ
     */
    protected function saveSvgAsImage(string $svg, string $prefix): ?string
    {
        try {
            $filename = "{$prefix}-" . Str::random(8) . '.svg';
            $path = "fortune/charts/{$filename}";

            Storage::disk('public')->put($path, $svg);

            return Storage::disk('public')->url($path);
        } catch (\Exception $e) {
            Log::error('FortuneChart: Failed to save SVG', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * ลบ chart เก่า (เรียกจาก scheduler)
     *
     * @param int $daysOld จำนวนวันที่เก่ากว่านี้จะถูกลบ
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
