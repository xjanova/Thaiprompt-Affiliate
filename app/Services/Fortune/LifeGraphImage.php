<?php

namespace App\Services\Fortune;

use App\Support\ThaiFontText;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * 🖼️ รูปกราฟชีวิต 1080×1350 (4:5) สำหรับส่งลูกค้าพร้อมคำทำนาย 39 / บทสรุป 99
 *
 * ดีไซน์เดียวกับการ์ด SVG ของ GenLotto (LifeGraphCard) แต่วาดด้วย GD + resources/fonts/NotoSansThai-Bold.ttf
 * เพราะเซิร์ฟเวอร์ไม่มี Imagick/rsvg/เบราว์เซอร์ (ตรวจ 2026-10-12)
 *   - วาดที่ 2 เท่าแล้วย่อด้วย imagecopyresampled ⇒ เส้นกราฟ/วงกลมเรียบ
 *   - ข้อความทุกบรรทัดผ่าน ThaiFontText::safe (ฟอนต์ไม่มี ▲ ▼ – — ✦ อีโมจิ) + ThaiFontText::draw (วรรณยุกต์ไม่จม)
 *     ⇒ สามเหลี่ยมพีค/ต่ำวาดเป็นรูปทรง ขีดยาวแปลงเป็น "-"
 *   - ดาวพื้นหลังสุ่มคงที่จากวันเกิด (คนเดิมได้ภาพเดิม)
 * ล้ม = คืน null แล้วส่งคำทำนายต่อตามปกติ (รูปเป็นของแถม ห้ามขวางบิล)
 */
final class LifeGraphImage
{
    public const W = 1080;

    public const H = 1350;

    private const S = 2;

    private const GOLD = '#f4d58d';

    private const INK = '#f5f2ff';

    private const MUTED = '#a9a3cf';

    private const GOOD = '#7ee0a1';

    private const BAD = '#ff8a7a';

    private const SHORT = [1 => 'อา', 2 => 'จ', 3 => 'อ', 4 => 'พ', 5 => 'พฤ', 6 => 'ศ', 7 => 'ส', 8 => 'รา'];

    private string $font;

    private \GdImage $im;

    public function __construct(?string $font = null)
    {
        $this->font = $font ?? resource_path('fonts/NotoSansThai-Bold.ttf');
    }

    /** วาดแล้วบันทึกลง public disk — คืน URL หรือ null */
    public function save(array $g, string $key): ?string
    {
        try {
            $path = 'fortune/charts/lifegraph-'.preg_replace('/[^A-Za-z0-9_-]/', '', $key).'.jpg';
            Storage::disk('public')->put($path, $this->render($g));

            return Storage::disk('public')->url($path);
        } catch (\Throwable $e) {
            Log::warning('LifeGraphImage: วาดรูปกราฟชีวิตไม่สำเร็จ', ['error' => $e->getMessage(), 'key' => $key]);

            return null;
        }
    }

    /** @return string JPEG 1080×1350 */
    public function render(array $g): string
    {
        if (! function_exists('imagecreatetruecolor') || ! is_file($this->font)) {
            throw new \RuntimeException('ต้องมี GD และฟอนต์ NotoSansThai');
        }
        $this->im = imagecreatetruecolor(self::W * self::S, self::H * self::S);
        imagealphablending($this->im, true);
        imageantialias($this->im, false); // antialias ของ GD ทิ้งความหนาเส้น (วาดได้แค่ 1px) — ความเรียบมาจากการวาด 2 เท่าแล้วย่อ
        $pr = $g['profile'];
        $cur = $g['current'];

        $this->background((string) $pr['birth_date']);
        $this->rrect(24, 24, 1032, 1302, 30, null, $this->col('#e9c46a', 50), 2);
        $this->rrect(36, 36, 1008, 1278, 22, null, $this->col('#e9c46a', 102), 1);

        // ── หัวการ์ด ─────────────────────────────────────────────
        $this->text(540, 96, 'กราฟชีวิต ตามตำรามหาทักษา', 30, self::GOLD, 'middle');
        $name = ThaiFontText::safe((string) $pr['name'], 'ดวงชะตาของคุณ');
        $this->text(540, 164, mb_strlen($name) > 24 ? mb_substr($name, 0, 24) : $name, 56, self::INK, 'middle');
        $born = 'เกิดวัน'.$pr['birth_day_th'].' · '.LifeGraphTables::thDate($pr['birth_date']).($pr['birth_time'] ? ' · '.$pr['birth_time'].' น.' : '');
        $this->text(540, 214, $born, 28, self::MUTED, 'middle');
        $this->text(540, 254, 'ดาวเจ้าวันเกิด '.$pr['birth_lord_th'].($pr['lagna'] ? ' · ลัคนาราศี'.$pr['lagna'] : ' · ไม่ทราบเวลาเกิด'), 26, self::MUTED, 'middle');

        // ── ช่วงนี้ ───────────────────────────────────────────────
        $this->rrect(70, 284, 940, 116, 20, $this->col('#ffffff', 119), $this->col('#e9c46a', 82), 1);
        $this->text(150, 326, 'ตอนนี้อายุ', 24, self::MUTED, 'middle');
        $this->text(150, 386, (string) $cur['age'], 62, self::GOLD, 'middle');
        $this->line(236, 300, 236, 384, $this->col('#e9c46a', 89), 1);
        if ($cur['period']) {
            $p = $cur['period'];
            $this->text(264, 334, 'ดาวเสวย '.$p['planet_th'].' · อายุ '.$p['from_age'].'-'.$p['to_age'].' ปี', 32, self::INK);
            $this->text(990, 334, 'ช่วง'.$p['tone'], 30, $p['tone'] === 'ดี' ? self::GOOD : ($p['tone'] === 'ร้าย' ? self::BAD : self::INK), 'end');
        }
        if ($cur['sub']) {
            $this->text(264, 378, 'ดาวแทรก '.$cur['sub']['planet_th'].' ถึงราว '.LifeGraphTables::thDate($cur['sub']['until_date']), 26, self::MUTED);
        }

        $this->chart($g);
        $this->extremes($g);

        // ── คำพยากรณ์ช่วงนี้ ─────────────────────────────────────
        $this->rrect(70, 1108, 940, 156, 18, $this->col('#ffffff', 121), $this->col('#e9c46a', 82), 1);
        $this->text(94, 1142, 'ตามตำรา ช่วงนี้', 24, self::GOLD);
        $lines = [];
        foreach ((array) ($g['reading']['card'] ?? []) as $l) {
            array_push($lines, ...$this->wrap($this->clean((string) $l), 22, 892));
        }
        foreach (array_slice($lines, 0, 4) as $i => $l) {
            $this->text(94, 1174 + $i * 27, $l, 22, self::INK);
        }

        $this->text(540, 1284, 'แม่หมอจันทราพยากรณ์', 22, self::GOLD, 'middle');
        $this->text(540, 1302, 'เพื่อความบันเทิง · คะแนนเป็นค่าที่ออกแบบจากตำรามหาทักษา ไม่ใช่การรับรองอนาคต', 16, self::MUTED, 'middle');

        $out = imagecreatetruecolor(self::W, self::H);
        imagecopyresampled($out, $this->im, 0, 0, 0, 0, self::W, self::H, self::W * self::S, self::H * self::S);
        unset($this->im); // คืนภาพ 2 เท่า (~23MB) ก่อนเข้ารหัส — งาน cron ใช้ memory_limit 128M
        ob_start();
        imagejpeg($out, null, 90);

        return (string) ob_get_clean();
    }

    private function chart(array $g): void
    {
        [$x0, $x1, $yt, $y0, $y1] = [110, 1010, 440, 482, 862];
        $maxA = $g['max_age'] + 1;
        $X = static fn (float $age): float => $x0 + $age / $maxA * ($x1 - $x0);
        $Y = static fn (float $v): float => $y1 - max(0.0, min(100.0, $v)) / 100 * ($y1 - $y0);
        foreach ($g['periods'] as $p) {
            $a = $X($p['from_age']);
            $b = $X(min($p['to_age'], $maxA));
            if ($b - $a < 1) {
                continue;
            }
            $this->rect($a, $y0, $b - $a, $y1 - $y0, $this->col($p['hex'], 109));
            $this->rrect($a + 1, $yt, $b - $a - 2, 32, 6, $this->col($p['hex'], 10), null);
            $label = $b - $a > 70 ? $p['planet_th'] : ($b - $a > 30 ? self::SHORT[$p['planet']] : '');
            if ($label !== '') {
                $this->text(($a + $b) / 2, $yt + 23, $label, 20, $this->luma($p['hex']) > .6 ? '#1c1c2e' : '#ffffff', 'middle');
            }
        }
        for ($v = 0; $v <= 100; $v += 25) {
            $y = $Y($v);
            $v === 50 ? $this->dashed($x0, $y, $x1, $y, $this->col('#ffffff', 82), 8, 6) : $this->line($x0, $y, $x1, $y, $this->col('#ffffff', 114), 1);
            $this->text($x0 - 12, $y + 7, (string) $v, 20, self::MUTED, 'end');
        }
        $pts = $this->smooth(array_map(static fn ($y) => [$X($y['age'] + 0.5), $Y($y['score'])], $g['years']));
        $area = [];
        foreach ($pts as [$px, $py]) {
            array_push($area, $px * self::S, $py * self::S);
        }
        array_push($area, end($pts)[0] * self::S, $y1 * self::S, $pts[0][0] * self::S, $y1 * self::S);
        imagefilledpolygon($this->im, $area, $this->col(self::GOLD, 108));
        $this->polyline($pts, $this->col(self::GOLD, 98), 12);
        $this->polyline($pts, $this->col(self::GOLD, 0), 4);

        foreach ([[$g['peaks'], true], [$g['lows'], false]] as [$list, $up]) {
            usort($list, static fn ($a, $b) => $a['age'] <=> $b['age']);
            $placed = [];
            foreach ($list as $pk) {
                $px = $X($pk['age'] + 0.5);
                $py = $Y($pk['score']);
                $c = $up ? self::GOOD : self::BAD;
                $this->circle($px, $py, 8, $this->col($c), $this->col('#0b1033'), 3);
                $lx = max($x0 + 30, min($x1 - 30, $px));
                $ly = $up ? $py - 18 : $py + 34;
                foreach ($placed as [$qx, $qy]) {
                    if (abs($qx - $lx) < 64 && abs($qy - $ly) < 26) {
                        $ly = $up ? $qy - 26 : $qy + 26;
                    }
                }
                $ly = $up ? max($y0 + 20, $ly) : min($y1 - 8, $ly);
                $placed[] = [$lx, $ly];
                $this->triangle($lx - 16, $ly - 8, $up, $this->col($c));
                $this->text($lx - 4, $ly, (string) $pk['age'], 20, $c);
            }
        }
        $cur = $g['current'];
        if ((float) $cur['age_decimal'] <= $maxA && isset($g['years'][$cur['age']])) {
            $cx = $X((float) $cur['age_decimal']);
            $this->dashed($cx, $y0, $cx, $y1, $this->col('#ff9d70'), 6, 5, 2);
            $this->circle($cx, $Y($g['years'][$cur['age']]['score']), 10, $this->col('#ff9d70'), $this->col('#ffffff'), 3);
        }
        for ($a = 0; $a <= $g['max_age']; $a += 10) {
            $this->text($X($a + 0.5), $y1 + 32, (string) $a, 20, self::MUTED, 'middle');
        }
        $this->text($x1, $y1 + 62, 'อายุ (ปี) · เส้นทอง = คะแนนรายปี · เส้นประส้ม = ตอนนี้', 18, self::MUTED, 'end');
    }

    private function extremes(array $g): void
    {
        foreach ([[70, 'ช่วงรุ่งของชีวิต', $g['peaks'], self::GOOD], [555, 'ช่วงควรระวัง', $g['lows'], self::BAD]] as [$bx, $title, $list, $c]) {
            $this->rrect($bx, 944, 455, 150, 18, $this->col('#ffffff', 121), $this->col($c, 70), 1);
            $this->text($bx + 24, 980, $title, 26, $c);
            foreach (array_slice($list, 0, 3) as $i => $pk) {
                $this->text($bx + 24, 1016 + $i * 32, 'อายุ '.$pk['age'].' (พ.ศ. '.$pk['year_be'].') · '.$pk['main_planet_th'].'/'.$pk['sub_planet_th'], 23, self::INK);
            }
        }
    }

    private function background(string $seed): void
    {
        $stops = [[0.0, [11, 16, 51]], [0.55, [28, 18, 69]], [1.0, [12, 10, 38]]];
        $h = self::H * self::S;
        for ($y = 0; $y < $h; $y++) {
            $t = $y / $h;
            [$a, $b] = $t <= .55 ? [$stops[0], $stops[1]] : [$stops[1], $stops[2]];
            $k = ($t - $a[0]) / ($b[0] - $a[0]);
            $rgb = array_map(static fn ($i) => (int) round($a[1][$i] + ($b[1][$i] - $a[1][$i]) * $k), [0, 1, 2]);
            imageline($this->im, 0, $y, self::W * self::S, $y, imagecolorallocate($this->im, ...$rgb));
        }
        for ($r = 600; $r > 40; $r -= 20) { // แสงทองมุมขวาบน
            imagefilledellipse($this->im, (int) (885 * self::S), (int) (108 * self::S), $r * 2 * self::S, $r * 2 * self::S, $this->col('#e9c46a', 125));
        }
        $s = crc32($seed) ?: 1;
        for ($i = 0; $i < 110; $i++) {
            $s = ($s * 1103515245 + 12345) & 0x7FFFFFFF;
            $x = 40 + $s / 0x7FFFFFFF * 1000;
            $s = ($s * 1103515245 + 12345) & 0x7FFFFFFF;
            $y = 40 + $s / 0x7FFFFFFF * 1270;
            $s = ($s * 1103515245 + 12345) & 0x7FFFFFFF;
            $r = 0.6 + $s / 0x7FFFFFFF * 1.6;
            $s = ($s * 1103515245 + 12345) & 0x7FFFFFFF;
            $gold = $s / 0x7FFFFFFF > .8;
            $s = ($s * 1103515245 + 12345) & 0x7FFFFFFF;
            $alpha = (int) round(127 * (1 - (.2 + $s / 0x7FFFFFFF * .55)));
            $d = (int) max(2, round($r * 2 * self::S));
            imagefilledellipse($this->im, (int) ($x * self::S), (int) ($y * self::S), $d, $d, $this->col($gold ? self::GOLD : '#ffffff', $alpha));
        }
    }

    // ── ตัวช่วยวาด (พิกัดแบบ 1080×1350 คูณ S ข้างใน) ─────────────

    private function col(string $hex, int $alpha = 0): int
    {
        $c = hexdec(ltrim($hex, '#'));

        return imagecolorallocatealpha($this->im, ($c >> 16) & 255, ($c >> 8) & 255, $c & 255, max(0, min(127, $alpha)));
    }

    private function text(float $x, float $y, string $s, int $px, string $hex, string $anchor = 'start'): void
    {
        $s = $this->clean($s);
        if ($s === '') {
            return;
        }
        $pt = $px * 0.75 * self::S;
        $w = ThaiFontText::advance($pt, $this->font, $s);
        $dx = $anchor === 'middle' ? $w / 2 : ($anchor === 'end' ? $w : 0);
        ThaiFontText::draw($this->im, $pt, (int) round($x * self::S - $dx), (int) round($y * self::S), $this->col($hex), $this->font, $s);
    }

    /** ตัดอักขระที่ฟอนต์ไม่มี (ขีดยาว → "-") */
    private function clean(string $s): string
    {
        return ThaiFontText::safe(str_replace(['—', '–', '…', '“', '”', '【', '】'], ['-', '-', '...', '"', '"', '[', ']'], $s), '');
    }

    /** ตัดบรรทัดที่ช่องว่าง/จุดคั่น ตามความกว้างจริงของฟอนต์ */
    private function wrap(string $s, int $px, float $maxWidth): array
    {
        $pt = $px * 0.75 * self::S;
        $parts = preg_split('/(?<= )|(?<=·)/u', trim($s)) ?: [$s];
        $out = [];
        $cur = '';
        foreach ($parts as $p) {
            if ($cur !== '' && ThaiFontText::advance($pt, $this->font, rtrim($cur.$p)) > $maxWidth * self::S) {
                $out[] = rtrim($cur);
                $cur = '';
            }
            $cur .= $p;
        }
        if (trim($cur) !== '') {
            $out[] = rtrim($cur);
        }

        return $out;
    }

    private function rect(float $x, float $y, float $w, float $h, int $color): void
    {
        imagefilledrectangle($this->im, (int) ($x * self::S), (int) ($y * self::S), (int) (($x + $w) * self::S) - 1, (int) (($y + $h) * self::S) - 1, $color);
    }

    /** สี่เหลี่ยมมุมมน = รูปหลายเหลี่ยมชิ้นเดียว (สีโปร่งไม่ซ้อนกันที่มุม) */
    private function rrect(float $x, float $y, float $w, float $h, float $r, ?int $fill, ?int $stroke, int $thick = 1): void
    {
        $pts = [];
        foreach ([[$x + $w - $r, $y + $r, -90], [$x + $w - $r, $y + $h - $r, 0], [$x + $r, $y + $h - $r, 90], [$x + $r, $y + $r, 180]] as [$cx, $cy, $start]) {
            for ($i = 0; $i <= 8; $i++) {
                $a = deg2rad($start + $i * 90 / 8);
                array_push($pts, (int) round(($cx + $r * cos($a)) * self::S), (int) round(($cy + $r * sin($a)) * self::S));
            }
        }
        if ($fill !== null) {
            imagefilledpolygon($this->im, $pts, $fill);
        }
        if ($stroke !== null) {
            imagesetthickness($this->im, $thick * self::S);
            imagepolygon($this->im, $pts, $stroke);
            imagesetthickness($this->im, 1);
        }
    }

    private function line(float $x1, float $y1, float $x2, float $y2, int $color, int $thick): void
    {
        imagesetthickness($this->im, $thick * self::S);
        imageline($this->im, (int) ($x1 * self::S), (int) ($y1 * self::S), (int) ($x2 * self::S), (int) ($y2 * self::S), $color);
        imagesetthickness($this->im, 1);
    }

    private function dashed(float $x1, float $y1, float $x2, float $y2, int $color, float $dash, float $gap, int $thick = 1): void
    {
        $len = hypot($x2 - $x1, $y2 - $y1);
        for ($d = 0; $d < $len; $d += $dash + $gap) {
            $e = min($len, $d + $dash);
            $this->line($x1 + ($x2 - $x1) * $d / $len, $y1 + ($y2 - $y1) * $d / $len, $x1 + ($x2 - $x1) * $e / $len, $y1 + ($y2 - $y1) * $e / $len, $color, $thick);
        }
    }

    /** เส้นหนาต่อกันเป็นสาย + จุดกลมที่ข้อต่อ (GD ไม่มี line-join) */
    private function polyline(array $pts, int $color, int $thick): void
    {
        $n = count($pts);
        for ($i = 0; $i < $n - 1; $i++) {
            $this->line($pts[$i][0], $pts[$i][1], $pts[$i + 1][0], $pts[$i + 1][1], $color, $thick);
        }
        if ($thick > 2) {
            foreach ($pts as $i => [$px, $py]) {
                if ($i % 2 === 0) {
                    imagefilledellipse($this->im, (int) ($px * self::S), (int) ($py * self::S), $thick * self::S, $thick * self::S, $color);
                }
            }
        }
    }

    private function circle(float $x, float $y, float $r, int $fill, int $stroke, int $strokeW): void
    {
        $d = (int) round(($r + $strokeW) * 2 * self::S);
        imagefilledellipse($this->im, (int) ($x * self::S), (int) ($y * self::S), $d, $d, $stroke);
        $d = (int) round($r * 2 * self::S);
        imagefilledellipse($this->im, (int) ($x * self::S), (int) ($y * self::S), $d, $d, $fill);
    }

    /** สามเหลี่ยม ▲/▼ เป็นรูปทรง (ฟอนต์ไม่มีอักขระนี้) */
    private function triangle(float $x, float $y, bool $up, int $color): void
    {
        $p = $up ? [$x, $y + 7, $x + 14, $y + 7, $x + 7, $y - 6] : [$x, $y - 6, $x + 14, $y - 6, $x + 7, $y + 7];
        imagefilledpolygon($this->im, array_map(fn ($v) => (int) round($v * self::S), $p), $color);
    }

    /** Catmull-Rom ผ่านทุกจุด → จุดย่อย 8 จุดต่อช่วง */
    private function smooth(array $p): array
    {
        $n = count($p);
        $out = [];
        for ($i = 0; $i < $n - 1; $i++) {
            [$a, $b, $c, $e] = [$p[max(0, $i - 1)], $p[$i], $p[$i + 1], $p[min($n - 1, $i + 2)]];
            for ($k = 0; $k < 8; $k++) {
                $t = $k / 8;
                $t2 = $t * $t;
                $t3 = $t2 * $t;
                $out[] = [
                    0.5 * ((2 * $b[0]) + (-$a[0] + $c[0]) * $t + (2 * $a[0] - 5 * $b[0] + 4 * $c[0] - $e[0]) * $t2 + (-$a[0] + 3 * $b[0] - 3 * $c[0] + $e[0]) * $t3),
                    0.5 * ((2 * $b[1]) + (-$a[1] + $c[1]) * $t + (2 * $a[1] - 5 * $b[1] + 4 * $c[1] - $e[1]) * $t2 + (-$a[1] + 3 * $b[1] - 3 * $c[1] + $e[1]) * $t3),
                ];
            }
        }
        $out[] = $p[$n - 1];

        return $out;
    }

    private function luma(string $hex): float
    {
        $c = hexdec(ltrim($hex, '#'));

        return (0.299 * ($c >> 16) + 0.587 * (($c >> 8) & 255) + 0.114 * ($c & 255)) / 255;
    }
}
