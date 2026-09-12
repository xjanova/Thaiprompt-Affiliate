<?php

namespace Tests\Unit\Services;

use App\Services\FortuneChartService;
use App\Support\ThaiFontText;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 🔤 รูป PNG ผังดวง (buildPngChart) ต้องไม่มีกล่องเต้าหู้/ตัวเพี้ยน และดาวกองภพเดียวกันต้องไม่ทับกัน (2026-09-12)
 *
 * ที่มา: เรนเดอร์ quick chart บนเครื่อง dev แล้วเห็น
 *   - ✨ ✦ ในหัวรูป/หัวผัง → กล่องสี่เหลี่ยม (NotoSansThai ไม่มีสองตัวนี้)
 *   - 💚 💔 🌙 → GD ถอด UTF-8 4 ไบต์ผิดเป็นตัว Latin-1 "ð���"
 *   - ชื่อลูกค้าจากเฟซบุ๊ก/LINE มีอีโมจิ/อักษรลาวได้ → กล่องเหมือนกัน
 *   - ดาวภพเดียวกันเยื้องกันแค่ 18px วงดาว 46px ทับกันเกือบมิด — เกิด 2,835 จาก 2,922 วัน (2024-2031)
 *   รูปนี้ส่งถึงลูกค้าจริง (quick chart + ผังวันเกิดแบบเดิม)
 *
 * เทสต์ชุดนี้ล็อก:
 *   1. ทุกข้อความที่ buildPngChart ส่งเข้าฟอนต์ไทย มีแต่ตัวที่ NotoSansThai-Bold.ttf มีจริง
 *      (ไทย + ASCII + Latin-1 ยกเว้น 12 ตัวที่ฟอนต์ขาด — ตรวจ cmap ด้วย fontTools 2026-09-12)
 *   2. buildPngChart ไม่เรียก imagettftext ตรงๆ — ทุกข้อความต้องผ่าน drawCenteredText (ไม่งั้นข้อ 1 มองไม่เห็น)
 *   3. ThaiFontText::safe() ตัดอีโมจิ/ลาว/Latin-1 ที่ขาด · ชื่อไทยล้วนผ่านครบ · กรองจนว่าง = ชื่อสำรอง
 *   4. ดาวกองภพเดียวกัน 1-5 ดวง อยู่ในแถบวงดาว และวงไม่ทับกัน
 *   5. quick chart ทุกวัน 2024-2031 + ผังวันเกิดทุกแบบ: วงดาว/ชื่อดาว ไม่ทับวงหรือชื่อของดวงอื่นเลย
 *
 * ไม่แตะ DB · ข้อ 1 และ 5 ต้องมี GD + FreeType (CI ติดตั้ง gd ไว้แล้ว)
 */
class ChartPngTextCoverageTest extends TestCase
{
    /** Latin-1 ที่ NotoSansThai-Bold.ttf ในรีโปไม่มี — เขียนแยกจาก ThaiFontText โดยตั้งใจ (ถ้าคลาสเพี้ยน เทสต์ต้องจับได้) */
    private const LATIN1_MISSING = [0xA4, 0xA6, 0xAC, 0xAD, 0xB1, 0xB2, 0xB3, 0xB5, 0xB9, 0xBC, 0xBD, 0xBE];

    /** เรขาคณิตเดียวกับใน buildPngChart — แก้ที่นั่นต้องแก้ที่นี่ด้วย */
    private const INNER_R = 275;      // วงภพ (ขอบนอกของแถบวงดาว)

    private const CENTER_R = 175;     // วงกลาง (ขอบในของแถบวงดาว)

    private const PLANET_R = (self::INNER_R + self::CENTER_R) / 2 + 22;

    private const BADGE = 46;         // เส้นผ่านศูนย์กลางวงดาว (ขนาดเต็ม)

    private const NAME_OFFSET = 26;   // ชื่อดาวอยู่ใต้วง (ขนาดเต็ม)

    private const NAME_PT = 13;       // ขนาดตัวอักษรชื่อดาว (ขนาดเต็ม)

    private function requireGd(): void
    {
        if (! function_exists('imagettfbbox')) {
            $this->markTestSkipped('เครื่องนี้ไม่มี GD + FreeType');
        }
    }

    private function invoke(object $svc, string $method, mixed ...$args): mixed
    {
        $m = new ReflectionMethod(FortuneChartService::class, $method);
        $m->setAccessible(true);

        return $m->invoke($svc, ...$args);
    }

    /**
     * อักขระที่ฟอนต์ไทยไม่มี (คืนเป็นรายการ U+XXXX)
     *
     * @return array<int, string>
     */
    private function uncoveredChars(string $text): array
    {
        $bad = [];
        foreach (mb_str_split($text) as $char) {
            $cp = mb_ord($char);
            $covered = ($cp >= 0x20 && $cp <= 0x7E)
                || ($cp >= 0xA0 && $cp <= 0xFF && ! in_array($cp, self::LATIN1_MISSING, true))
                || ($cp >= 0x0E01 && $cp <= 0x0E3A)
                || ($cp >= 0x0E3F && $cp <= 0x0E5B);
            if (! $covered) {
                $bad[] = sprintf('U+%04X', $cp);
            }
        }

        return array_values(array_unique($bad));
    }

    /** service ที่จดทุกข้อความที่ส่งเข้า drawCenteredText (และยังวาดจริงตามปกติ) */
    private function recordingService(): FortuneChartService
    {
        return new class extends FortuneChartService
        {
            /** @var array<int, array{font: string, text: string}> */
            public array $drawn = [];

            protected function drawCenteredText($img, string $font, float $size, float $x, float $y, string $text, int $color): void
            {
                $this->drawn[] = ['font' => $font, 'text' => $text];
                parent::drawCenteredText($img, $font, $size, $x, $y, $text, $color);
            }
        };
    }

    /** chartData แบบเดียวกับ generateBirthChart() */
    private function birthChartData(FortuneChartService $svc, int $dow, string $name): array
    {
        $chaochana = FortuneChartService::CHAOCHANA[$dow];
        $main = FortuneChartService::PLANETS[$chaochana['planet']];
        $days = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];

        return [
            'name' => $name,
            'birthDate' => '15/05/2533',
            'dayOfWeek' => $days[$dow],
            'mainPlanet' => $main['name'],
            'mainPlanetSymbol' => $main['symbol'],
            'mainPlanetColor' => $main['color'],
            'planetPositions' => $svc->calculatePlanetPositions($dow),
            'chaochana' => $chaochana,
            'isFullChart' => true,
        ];
    }

    /** chartData แบบเดียวกับ generateQuickChart() */
    private function quickChartData(FortuneChartService $svc, Carbon $date, string $name): array
    {
        return [
            'name' => $name,
            'birthDate' => null,
            'dayOfWeek' => null,
            'mainPlanet' => null,
            'mainPlanetSymbol' => null,
            'mainPlanetColor' => '#8B5CF6',
            'planetPositions' => $svc->calculateTransitForDate($date),
            'chaochana' => null,
            'isFullChart' => false,
            'transitDate' => $date->format('d/m/').($date->year + 543),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // 1-2. ข้อความที่วาดด้วยฟอนต์ไทย
    // ─────────────────────────────────────────────────────────────

    public function test_ทุกข้อความที่วาดด้วยฟอนต์ไทย_ฟอนต์มีครบทุกตัว(): void
    {
        $this->requireGd();

        $svc = $this->recordingService();
        $thaiFont = $this->invoke($svc, 'getThaiFont');

        // ผังวันเกิดครบ 7 วัน (ข้อความตายตัวของกิ่ง isFullChart) — ชื่อกรองที่หัวเมธอดจุดเดียว ใช้ร่วมทั้งสองกิ่ง
        foreach (array_keys(FortuneChartService::CHAOCHANA) as $dow) {
            $this->invoke($svc, 'buildPngChart', $this->birthChartData($svc, $dow, 'น้องมิว💕✨🌙'));
        }
        // quick chart ต่อชื่อแต่ละแบบ · 2024-06-25 = วันที่ดาวกอง 5 ดวงในภพเดียว (แตะกิ่งจัดวางทุกแบบ)
        foreach (['น้องมิว💕✨🌙', 'ສົມໃຈ ດີ', 'Zoë ½ ²', "สมหญิง\nใจดี"] as $name) {
            $this->invoke($svc, 'buildPngChart', $this->quickChartData($svc, Carbon::create(2024, 6, 25, 12, 0, 0, 'Asia/Bangkok'), $name));
        }

        $thaiTexts = array_values(array_unique(array_column(
            array_filter($svc->drawn, fn (array $d) => $d['font'] === $thaiFont),
            'text'
        )));
        $this->assertGreaterThan(40, count($thaiTexts), 'จดข้อความได้น้อยผิดปกติ — เทสต์อาจผ่านลอยๆ');

        foreach ($thaiTexts as $text) {
            $this->assertSame([], $this->uncoveredChars($text), "ข้อความในรูป \"{$text}\" มีตัวที่ฟอนต์ไทยไม่มี → ขึ้นเป็นกล่อง/ตัวเพี้ยน");
        }

        // ชื่อลูกค้าที่กรองแล้วต้องขึ้นในรูปจริง ไม่ใช่หายไปทั้งบรรทัด
        $this->assertContains('น้องมิว', $thaiTexts);
        $this->assertContains('Zoë', $thaiTexts);
        $this->assertContains('สมหญิง ใจดี', $thaiTexts);
        $this->assertContains('เจ้าชะตา', $thaiTexts, 'ชื่ออักษรลาวล้วน กรองแล้วว่าง ต้องใช้ชื่อสำรอง');
    }

    public function test_รูปผังดวงกำเนิดจริง_ข้อความทุกบรรทัดฟอนต์ไทยมีครบ(): void
    {
        $this->requireGd();

        // buildNatalPngChart (รูปที่ส่งลูกค้าบิล 39/99) — เดิมกรองชื่อด้วยช่วง Latin-1 ทั้งก้อน ⇒ ½ ² ± หลุดไปเป็นกล่อง
        $svc = $this->recordingService();
        $thaiFont = $this->invoke($svc, 'getThaiFont');
        foreach (['น้องมิว💕✨ ½', 'ສົມໃຈ ດີ'] as $name) {
            foreach ([['1990-05-15 08:30', 'เชียงใหม่'], ['1990-05-15', null]] as [$ymd, $province]) {
                $this->invoke($svc, 'buildNatalPngChart', $svc->natalChartData($ymd, $name, null, $province));
            }
        }

        $thaiTexts = array_values(array_unique(array_column(
            array_filter($svc->drawn, fn (array $d) => $d['font'] === $thaiFont),
            'text'
        )));
        $this->assertGreaterThan(20, count($thaiTexts), 'จดข้อความได้น้อยผิดปกติ — เทสต์อาจผ่านลอยๆ');

        foreach ($thaiTexts as $text) {
            $this->assertSame([], $this->uncoveredChars($text), "ข้อความในรูป \"{$text}\" มีตัวที่ฟอนต์ไทยไม่มี → ขึ้นเป็นกล่อง/ตัวเพี้ยน");
        }
        $this->assertContains('น้องมิว', $thaiTexts);
        $this->assertContains('เจ้าชะตา', $thaiTexts, 'ชื่ออักษรลาวล้วน กรองแล้วว่าง ต้องใช้ชื่อสำรอง');
    }

    public function test_build_png_chart_ไม่เรียกฟังก์ชันวาดตัวอักษรของ_gd_ตรงๆ(): void
    {
        $method = new ReflectionMethod(FortuneChartService::class, 'buildPngChart');
        $lines = file((string) $method->getFileName());
        $source = implode('', array_slice($lines, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1));

        $this->assertDoesNotMatchRegularExpression(
            '/\bimage(ttf|ft)text\s*\(/', // imagettftext / imagefttext
            $source,
            'buildPngChart ต้องวาดข้อความผ่าน drawCenteredText เท่านั้น — เรียก imagettftext ตรงๆ เทสต์ข้อความจะมองไม่เห็น'
        );
    }

    // ─────────────────────────────────────────────────────────────
    // 3. ตัวกรองชื่อ
    // ─────────────────────────────────────────────────────────────

    public function test_ตัวกรองชื่อ_ตัดตัวที่ฟอนต์ไม่มี_ชื่อไทยผ่านครบ(): void
    {
        // ชื่อไทยล้วน (สระบน/ล่าง วรรณยุกต์ การันต์) ต้องผ่านครบไม่แหว่ง
        $this->assertSame('สมหญิง ใจดี', ThaiFontText::safe('สมหญิง ใจดี', 'x'));
        $this->assertSame('กำพล ศักดิ์สิทธิ์', ThaiFontText::safe('กำพล ศักดิ์สิทธิ์', 'x'));
        $this->assertSame('Somchai J.', ThaiFontText::safe('Somchai J.', 'x'));

        // อีโมจิ / variation selector / ตัวที่ Latin-1 ขาด / ขึ้นบรรทัดใหม่
        $this->assertSame('น้องมิว', ThaiFontText::safe('น้องมิว💕✨', 'x'));
        $this->assertSame('ดาว', ThaiFontText::safe("ดาว\u{FE0F}", 'x'));
        $this->assertSame('Zoë', ThaiFontText::safe('Zoë ½ ²', 'x'));
        $this->assertSame('แม่ มะลิ', ThaiFontText::safe("แม่\n\tมะลิ", 'x'));

        // กรองแล้วไม่เหลือ = ชื่อสำรอง
        $this->assertSame('เจ้าชะตา', ThaiFontText::safe('ສົມໃຈ 🌙', 'เจ้าชะตา'));
        $this->assertSame('เจ้าชะตา', ThaiFontText::safe('', 'เจ้าชะตา'));

        foreach (['น้องมิว💕✨', 'ສົມໃຈ ດີ', '王小明', 'Zoë ½ ² µ ¼', "a\u{200D}b"] as $raw) {
            $this->assertSame([], $this->uncoveredChars(ThaiFontText::safe($raw, 'เจ้าชะตา')), "กรอง \"{$raw}\" แล้วยังเหลือตัวที่ฟอนต์ไม่มี");
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 4-5. ดาวกองภพเดียวกัน
    // ─────────────────────────────────────────────────────────────

    public function test_ดาวกองภพเดียวกัน_1_ถึง_5_ดวง_อยู่ในแถบวงดาวและวงไม่ทับกัน(): void
    {
        $svc = new FortuneChartService;

        for ($n = 1; $n <= 5; $n++) {
            $slots = $this->invoke($svc, 'planetSlotsInHouse', $n, (float) self::PLANET_R);
            $this->assertCount($n, $slots);

            $points = [];
            foreach ($slots as $slot) {
                $r = self::BADGE / 2 * $slot['scale'];
                $this->assertGreaterThanOrEqual(self::CENTER_R, $slot['radius'] - $r, "{$n} ดวง: วงดาวล้นเข้าวงกลาง");
                $this->assertLessThanOrEqual(self::INNER_R, $slot['radius'] + $r, "{$n} ดวง: วงดาวล้นไปทับวงภพ");

                $angle = deg2rad($slot['angle']);
                $points[] = [$slot['radius'] * cos($angle), $slot['radius'] * sin($angle), $r];
            }

            foreach ($points as $i => [$x1, $y1, $r1]) {
                foreach (array_slice($points, $i + 1) as [$x2, $y2, $r2]) {
                    $this->assertGreaterThan($r1 + $r2, hypot($x1 - $x2, $y1 - $y2), "{$n} ดวง: วงดาวทับกัน");
                }
            }
        }
    }

    public function test_ผังจริงทุกวัน_2024_ถึง_2031_วงดาวและชื่อดาวไม่ทับของดวงอื่น(): void
    {
        $this->requireGd();

        $svc = new FortuneChartService;
        $font = $this->invoke($svc, 'getThaiFont');

        $charts = [];
        for ($d = Carbon::create(2024, 1, 1, 12, 0, 0, 'Asia/Bangkok'); $d->year < 2032; $d->addDay()) {
            $charts['quick '.$d->toDateString()] = $svc->calculateTransitForDate($d);
        }
        for ($dow = 0; $dow <= 7; $dow++) {
            $charts["birth dow={$dow}"] = $svc->calculatePlanetPositions($dow);
        }

        $clashes = [];
        foreach ($charts as $label => $positions) {
            $clash = $this->firstClash($this->screenLayout($svc, $positions, $font));
            if ($clash !== null) {
                $clashes[] = "{$label}: {$clash}";
            }
        }

        $this->assertSame([], array_slice($clashes, 0, 5), count($clashes).' ผังมีดาวทับกัน');
    }

    /**
     * ตำแหน่งบนจอของวงดาว + กล่องชื่อดาว (ความกว้าง/สูงจริงจาก GD) แบบเดียวกับที่ buildPngChart วาด
     *
     * @return array<int, array<string, float|string>>
     */
    private function screenLayout(FortuneChartService $svc, array $positions, string $font): array
    {
        static $boxes = [];
        $items = [];

        foreach ($positions as $house => $planets) {
            $planets = array_values($planets);
            if ($planets === []) {
                continue;
            }
            $mid = deg2rad(($house - 1) * 30 - 90 + 15);
            $slots = $this->invoke($svc, 'planetSlotsInHouse', count($planets), (float) self::PLANET_R);

            foreach ($planets as $i => $key) {
                $slot = $slots[$i];
                $angle = $mid + deg2rad($slot['angle']);
                $x = $slot['radius'] * cos($angle);
                $y = $slot['radius'] * sin($angle);
                $name = FortuneChartService::PLANETS[$key]['name'];
                $size = self::NAME_PT * $slot['scale'];
                $boxes[$name.'@'.$size] ??= @imagettfbbox($size, 0, $font, $name);
                $bbox = $boxes[$name.'@'.$size];

                $items[] = [
                    'key' => $key,
                    'x' => $x,
                    'y' => $y,
                    'r' => self::BADGE / 2 * $slot['scale'],
                    'nx' => $x,
                    'ny' => $y + self::NAME_OFFSET * $slot['scale'],
                    'hw' => ($bbox[2] - $bbox[0]) / 2,
                    'hh' => ($bbox[1] - $bbox[7]) / 2,
                ];
            }
        }

        return $items;
    }

    /** คู่แรกที่ทับกัน (วงชนวง / ชื่อชนวงดวงอื่น / ชื่อชนชื่อ) — ไม่มี = null */
    private function firstClash(array $items): ?string
    {
        foreach ($items as $i => $p) {
            foreach ($items as $j => $q) {
                if ($i === $j) {
                    continue;
                }
                if (hypot($p['x'] - $q['x'], $p['y'] - $q['y']) < $p['r'] + $q['r']) {
                    return "วง {$p['key']} ทับวง {$q['key']}";
                }
                // จุดในกล่องชื่อของ p ที่ใกล้ศูนย์กลางวง q ที่สุด
                $cx = max($p['nx'] - $p['hw'], min($q['x'], $p['nx'] + $p['hw']));
                $cy = max($p['ny'] - $p['hh'], min($q['y'], $p['ny'] + $p['hh']));
                if (hypot($q['x'] - $cx, $q['y'] - $cy) < $q['r']) {
                    return "ชื่อ {$p['key']} ทับวง {$q['key']}";
                }
                if (abs($p['nx'] - $q['nx']) < $p['hw'] + $q['hw'] && abs($p['ny'] - $q['ny']) < $p['hh'] + $q['hh']) {
                    return "ชื่อ {$p['key']} ทับชื่อ {$q['key']}";
                }
            }
        }

        return null;
    }
}
