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

            return $this->saveChartAsImage($pngData, "birth-chart-{$dayOfWeek}");

        } catch (\Exception $e) {
            Log::warning('FortuneChart: Failed to generate birth chart', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'birthDate' => $birthDate,
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

            return $this->saveChartAsImage($pngData, 'quick-chart');

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
     * สร้าง PNG chart ด้วย GD — รองรับภาษาไทย + ใช้ได้ใน Facebook/LINE
     *
     * @param  array  $chartData  ข้อมูล chart
     * @return string PNG binary data
     */
    protected function buildPngChart(array $chartData): string
    {
        $width = 800;
        $height = 800;
        $cx = $width / 2;
        $cy = $height / 2;
        $outerR = 340;
        $innerR = 220;
        $centerR = 130;

        // สร้าง canvas
        $img = imagecreatetruecolor($width, $height);
        imagealphablending($img, true);
        imagesavealpha($img, true);

        // โหลดฟอนต์
        $thaiFont = $this->getThaiFont();
        $symbolFont = $this->getSymbolFont();

        // === สีที่ใช้ ===
        $bgColor = $this->hexColor($img, '#0d0521');
        $bgInner = $this->hexColor($img, '#1a0a3e');
        $purple = $this->hexColor($img, '#8B5CF6');
        $purpleDark = $this->hexColor($img, '#6D28D9');
        $purpleLight = $this->hexColor($img, '#A78BFA');
        $purpleFaint = $this->hexColor($img, '#C4B5FD');
        $gold = $this->hexColor($img, '#FFD700');
        $white = $this->hexColor($img, '#FFFFFF');
        $gray = $this->hexColor($img, '#D1D5DB');
        $grayDark = $this->hexColor($img, '#6B7280');
        $green = $this->hexColor($img, '#4ADE80');
        $red = $this->hexColor($img, '#F87171');

        // === พื้นหลัง ===
        imagefilledrectangle($img, 0, 0, $width, $height, $bgColor);

        // ดาวกะพริบ (decorative)
        for ($i = 0; $i < 60; $i++) {
            $sx = rand(10, $width - 10);
            $sy = rand(10, $height - 10);
            $sr = rand(1, 2);
            $starColor = imagecolorallocatealpha($img, 255, 255, 255, rand(40, 100));
            imagefilledellipse($img, $sx, $sy, $sr * 2, $sr * 2, $starColor);
        }

        // Glow ตรงกลาง (วงกลมโปร่งใส)
        for ($r = $outerR; $r > $centerR; $r -= 5) {
            $alpha = (int) (127 - (127 - 100) * ($r - $centerR) / ($outerR - $centerR));
            $glowColor = imagecolorallocatealpha($img, 139, 92, 246, $alpha);
            imagefilledellipse($img, (int) $cx, (int) $cy, $r * 2, $r * 2, $glowColor);
        }

        // === วงกลม 3 ชั้น ===
        // วงนอก
        $this->drawCircle($img, $cx, $cy, $outerR, $purple, 2);
        // วงใน
        $this->drawCircle($img, $cx, $cy, $innerR, $purpleDark, 2);
        // วงกลาง (filled)
        imagefilledellipse($img, (int) $cx, (int) $cy, $centerR * 2, $centerR * 2, $bgInner);
        $this->drawCircle($img, $cx, $cy, $centerR, $purpleLight, 2);

        // === เส้นแบ่ง 12 ภพ ===
        for ($i = 0; $i < 12; $i++) {
            $angle = deg2rad($i * 30 - 90);
            $x1 = (int) ($cx + $innerR * cos($angle));
            $y1 = (int) ($cy + $innerR * sin($angle));
            $x2 = (int) ($cx + $outerR * cos($angle));
            $y2 = (int) ($cy + $outerR * sin($angle));
            imageline($img, $x1, $y1, $x2, $y2, $purpleDark);
        }

        // === ชื่อภพ + ดาวในแต่ละภพ ===
        for ($i = 1; $i <= 12; $i++) {
            $house = self::HOUSES[$i];
            $midAngle = deg2rad(($i - 1) * 30 - 90 + 15);

            // ชื่อภพ (วงนอก)
            $houseColor = $this->hexColor($img, $house['color']);
            $tx = $cx + ($outerR - 25) * cos($midAngle);
            $ty = $cy + ($outerR - 25) * sin($midAngle);
            $label = "{$i}.{$house['name']}";
            $this->drawCenteredText($img, $thaiFont, 13, $tx, $ty, $label, $houseColor);

            // ดาวเคราะห์ในภพ
            $planets = $chartData['planetPositions'][$i] ?? [];
            if (! empty($planets)) {
                $planetR = ($innerR + $centerR) / 2 + 15;
                $planetCount = count($planets);

                foreach ($planets as $pIdx => $planetKey) {
                    $planet = self::PLANETS[$planetKey];
                    $planetColor = $this->hexColor($img, $planet['color']);
                    $offset = ($pIdx - ($planetCount - 1) / 2) * 12;
                    $px = $cx + ($planetR + $offset) * cos($midAngle);
                    $py = $cy + ($planetR + $offset) * sin($midAngle);

                    // วงกลมพื้นหลังดาว
                    $bgPlanet = $this->hexColorAlpha($img, $planet['color'], 90);
                    imagefilledellipse($img, (int) $px, (int) $py, 30, 30, $bgPlanet);

                    // สัญลักษณ์ดาว (ใช้ symbol font)
                    $this->drawCenteredText($img, $symbolFont, 18, $px, $py - 2, $planet['symbol'], $planetColor);

                    // ชื่อดาว (ภาษาไทย)
                    $this->drawCenteredText($img, $thaiFont, 11, $px, $py + 16, $planet['name'], $planetColor);
                }
            }
        }

        // === ตรงกลาง: ข้อมูลผู้ใช้ ===
        $name = mb_substr($chartData['name'], 0, 15);

        if ($chartData['isFullChart']) {
            $mainColor = $this->hexColor($img, $chartData['mainPlanetColor'] ?? '#FFD700');

            $this->drawCenteredText($img, $thaiFont, 15, $cx, $cy - 55, 'BIRTH CHART', $gold);
            $this->drawCenteredText($img, $thaiFont, 18, $cx, $cy - 33, $name, $white);
            $this->drawCenteredText($img, $thaiFont, 14, $cx, $cy - 13, "วัน{$chartData['dayOfWeek']}", $purpleLight);
            $this->drawCenteredText($img, $thaiFont, 13, $cx, $cy + 7, $chartData['birthDate'], $gray);

            // ดาวเจ้าชนะ (symbol ใหญ่)
            $this->drawCenteredText($img, $symbolFont, 30, $cx, $cy + 35, $chartData['mainPlanetSymbol'], $mainColor);
            $this->drawCenteredText($img, $thaiFont, 13, $cx, $cy + 58, "ดาวเจ้าชนะ: {$chartData['mainPlanet']}", $mainColor);

            // มิตร/ศัตรู
            $friendNames = implode(' ', array_map(fn ($k) => self::PLANETS[$k]['name'], $chartData['chaochana']['friends']));
            $enemyNames = implode(' ', array_map(fn ($k) => self::PLANETS[$k]['name'], $chartData['chaochana']['enemies']));
            $this->drawCenteredText($img, $thaiFont, 11, $cx, $cy + 78, "มิตร: {$friendNames}", $green);
            $this->drawCenteredText($img, $thaiFont, 11, $cx, $cy + 95, "ศัตรู: {$enemyNames}", $red);
        } else {
            // Quick chart (ไม่มีวันเกิด)
            $this->drawCenteredText($img, $thaiFont, 15, $cx, $cy - 50, 'TRANSIT CHART', $gold);
            $this->drawCenteredText($img, $thaiFont, 18, $cx, $cy - 25, $name, $white);
            $this->drawCenteredText($img, $thaiFont, 13, $cx, $cy, 'ดวงดาวโคจรขณะนี้', $purpleLight);
            $this->drawCenteredText($img, $thaiFont, 12, $cx, $cy + 20, $chartData['transitDate'], $gray);
            $this->drawCenteredText($img, $thaiFont, 11, $cx, $cy + 70, 'บอกวันเกิดเพื่อดู Birth Chart', $purpleFaint);
        }

        // === หัวเรื่องด้านบน ===
        $this->drawCenteredText($img, $thaiFont, 22, $cx, 32, '~~ จันทราพยากรณ์ ~~', $gold);
        $this->drawCenteredText($img, $thaiFont, 12, $cx, 58, 'โหราศาสตร์เจ้าชนะ | ดวงดาว 9 ดวง | ภพ 12 ภพ', $purpleFaint);

        // === Footer ===
        $this->drawCenteredText($img, $thaiFont, 10, $cx, $height - 20, 'จันทราพยากรณ์ | Powered by Xman Studio', $grayDark);

        // === Export PNG ===
        ob_start();
        imagepng($img, null, 7); // compression level 7
        $pngData = ob_get_clean();
        imagedestroy($img);

        return $pngData;
    }

    /**
     * หา path ฟอนต์ภาษาไทย
     */
    protected function getThaiFont(): string
    {
        // ลองหาฟอนต์ตามลำดับ
        $paths = [
            resource_path('fonts/NotoSansThai-Bold.ttf'),
            '/usr/share/fonts/truetype/noto/NotoSansThai-Bold.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansThai-Regular.ttf',
            '/usr/share/fonts/truetype/tlwg/TlwgTypo-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // fallback — ใช้ GD built-in (จะไม่มีภาษาไทย แต่ไม่ crash)
        return '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
    }

    /**
     * หา path ฟอนต์สำหรับ Unicode symbols (☉☽♂☿♃♀♄)
     */
    protected function getSymbolFont(): string
    {
        $paths = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/freefont/FreeSans.ttf',
            resource_path('fonts/NotoSansThai-Bold.ttf'), // fallback ใช้ฟอนต์ไทย
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

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
     */
    protected function drawCenteredText($img, string $font, float $size, float $x, float $y, string $text, int $color): void
    {
        $bbox = imagettfbbox($size, 0, $font, $text);
        if ($bbox === false) {
            return;
        }
        $textWidth = $bbox[2] - $bbox[0];
        $textHeight = $bbox[1] - $bbox[7];
        $drawX = $x - $textWidth / 2;
        $drawY = $y + $textHeight / 2;
        imagettftext($img, $size, 0, (int) $drawX, (int) $drawY, $color, $font, $text);
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
    <style>
        text { font-family: 'Noto Sans Thai', 'Sarabun', 'Prompt', 'Kanit', sans-serif; }
    </style>
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
            // Birth chart
            $mainColor = $chartData['mainPlanetColor'] ?? '#FFD700';
            $svg .= "<text x=\"{$cx}\" y=\"".($cy - 55)."\" text-anchor=\"middle\" fill=\"#FFD700\" font-size=\"15\" font-weight=\"bold\" filter=\"url(#textGlow)\">BIRTH CHART</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"".($cy - 33)."\" text-anchor=\"middle\" fill=\"white\" font-size=\"18\" font-weight=\"bold\">{$name}</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"".($cy - 13)."\" text-anchor=\"middle\" fill=\"#A78BFA\" font-size=\"14\">วัน{$chartData['dayOfWeek']}</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"".($cy + 7)."\" text-anchor=\"middle\" fill=\"#D1D5DB\" font-size=\"13\">{$chartData['birthDate']}</text>\n";

            // ดาวเจ้าชนะ
            $svg .= "<text x=\"{$cx}\" y=\"".($cy + 35)."\" text-anchor=\"middle\" fill=\"{$mainColor}\" font-size=\"30\" filter=\"url(#textGlow)\">{$chartData['mainPlanetSymbol']}</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"".($cy + 58)."\" text-anchor=\"middle\" fill=\"{$mainColor}\" font-size=\"13\" font-weight=\"bold\">ดาวเจ้าชนะ: {$chartData['mainPlanet']}</text>\n";

            // Legend มิตร/ศัตรู
            $friendNames = implode(' ', array_map(fn ($k) => self::PLANETS[$k]['name'], $chartData['chaochana']['friends']));
            $enemyNames = implode(' ', array_map(fn ($k) => self::PLANETS[$k]['name'], $chartData['chaochana']['enemies']));
            $svg .= "<text x=\"{$cx}\" y=\"".($cy + 78)."\" text-anchor=\"middle\" fill=\"#4ADE80\" font-size=\"11\">มิตร: {$friendNames}</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"".($cy + 95)."\" text-anchor=\"middle\" fill=\"#F87171\" font-size=\"11\">ศัตรู: {$enemyNames}</text>\n";
        } else {
            // Quick chart (ไม่มีวันเกิด)
            $svg .= "<text x=\"{$cx}\" y=\"".($cy - 50)."\" text-anchor=\"middle\" fill=\"#FFD700\" font-size=\"15\" font-weight=\"bold\" filter=\"url(#textGlow)\">TRANSIT CHART</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"".($cy - 25)."\" text-anchor=\"middle\" fill=\"white\" font-size=\"18\" font-weight=\"bold\">{$name}</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"".($cy)."\" text-anchor=\"middle\" fill=\"#A78BFA\" font-size=\"13\">ดวงดาวโคจรขณะนี้</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"".($cy + 20)."\" text-anchor=\"middle\" fill=\"#D1D5DB\" font-size=\"12\">{$chartData['transitDate']}</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"".($cy + 55)."\" text-anchor=\"middle\" fill=\"#8B5CF6\" font-size=\"32\" filter=\"url(#textGlow)\">\u{2728}</text>\n";
            $svg .= "<text x=\"{$cx}\" y=\"".($cy + 80)."\" text-anchor=\"middle\" fill=\"#C4B5FD\" font-size=\"11\">บอกวันเกิดเพื่อดู Birth Chart</text>\n";
        }

        // Title ด้านบน
        $svg .= "<text x=\"{$cx}\" y=\"30\" text-anchor=\"middle\" fill=\"#FFD700\" font-size=\"22\" font-weight=\"bold\" filter=\"url(#textGlow)\">~~ จันทราพยากรณ์ ~~</text>\n";
        $svg .= "<text x=\"{$cx}\" y=\"55\" text-anchor=\"middle\" fill=\"#C4B5FD\" font-size=\"12\">โหราศาสตร์เจ้าชนะ | ดวงดาว 9 ดวง | ภพ 12 ภพ</text>\n";

        // Footer
        $svg .= "<text x=\"{$cx}\" y=\"".($height - 20)."\" text-anchor=\"middle\" fill=\"#6B7280\" font-size=\"10\">จันทราพยากรณ์ | Powered by Xman Studio</text>\n";

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

            return Storage::disk('public')->url($path);
        } catch (\Exception $e) {
            Log::error('FortuneChart: Failed to save chart image', [
                'error' => $e->getMessage(),
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
