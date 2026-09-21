<?php

namespace Tests\Unit\Services;

use App\Services\Fortune\ThaiLunarCalendar;
use App\Services\Fortune\ThaiRuekYam;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * 🗓️ ฤกษ์ยามตามตำรา + ปฏิทินหลวง — ตรึงกับแหล่งอ้างอิงภายนอก (ไม่แตะ DB ไม่บูต Laravel)
 *
 * พอร์ตจาก GenLotto tests/verify_ruek.mjs + verify_lunar.php — ข้อมูลอ้างอิงชุดเดียวกัน:
 *   1) กาลโยค + เวลาเถลิงศก เทียบประกาศสงกรานต์ 41 ปี (พ.ศ. 2480–2600 ทุก 3 ปี)
 *   2) ยามอัฏฐกาล 7 วาร × 16 ยาม เทียบตารางที่ตรงกัน 5 แหล่ง
 *   3) ฤกษ์บน + เวลาเปลี่ยนฤกษ์ เทียบปฏิทินฤกษ์ myhora 1,370 วัน (fixtures/myhora_ruek.txt)
 *   4) ฤกษ์ล่างทุกตารางที่ใส่ในระบบ เทียบเครื่องหมายในปฏิทิน myhora ทุกวัน
 *   5) ปฏิทินหลวง: วันสำคัญทางศาสนา + วันขึ้น 1 ค่ำ เดือน 5 ทุกปี 2568–2600
 *
 * ถ้าเทสต์นี้ตก = ตัวเลขที่แม่หมอบอกลูกค้าผิดตำรา — ห้ามแก้ค่าคาดหวังให้ผ่าน ต้องหาต้นเหตุ
 */
class ThaiRuekYamTest extends TestCase
{
    /**
     * ประกาศสงกรานต์ (myhora /calendar/songkran.aspx?year=…)
     * [พ.ศ., จ.ศ., เถลิงศก (เวลาท้องถิ่นกรุงเทพฯ), ธงชัย/อธิบดี วัน·ยาม·ราศี·ดิถี·ฤกษ์, อุบาทว์/โลกาวินาศ วัน·ยาม·ราศี·ดิถี·ฤกษ์]
     */
    private const SONGKRAN = <<<'TXT'
2480 1299 04-15 13:58 1,2,1,7,9,3,3,3,6,6,7,4,8,3,8,7,2,19,5,16
2483 1302 04-15 08:36 3,5,7,2,3,6,3,6,9,9,2,7,6,6,2,10,2,22,8,19
2486 1305 04-16 03:14 5,1,5,5,9,9,3,9,12,12,4,3,4,1,8,1,2,25,11,22
2489 1308 04-15 21:52 7,4,3,8,3,0,3,12,15,15,6,6,2,4,2,4,2,28,14,25
2492 1311 04-15 16:30 2,7,1,3,9,3,3,15,18,18,1,2,8,7,8,7,2,1,17,1
2495 1314 04-15 11:07 4,3,7,6,3,6,3,18,21,21,3,5,6,2,2,10,2,4,20,4
2498 1317 04-16 05:45 6,6,5,1,9,9,3,21,24,24,5,1,4,5,8,1,2,7,23,7
2501 1320 04-16 00:23 1,2,3,4,3,0,3,24,27,27,7,4,2,8,2,4,2,10,26,10
2504 1323 04-15 19:01 3,5,1,7,9,3,3,27,3,3,2,7,8,3,8,7,2,13,2,13
2507 1326 04-15 13:39 5,1,7,2,3,6,3,30,6,6,4,3,6,6,2,10,2,16,5,16
2510 1329 04-16 08:16 7,4,5,5,9,9,3,3,9,9,6,6,4,1,8,1,2,19,8,19
2513 1332 04-16 02:54 2,7,3,8,3,0,3,6,12,12,1,2,2,4,2,4,2,22,11,22
2516 1335 04-15 21:32 4,3,1,3,9,3,3,9,15,15,3,5,8,7,8,7,2,25,14,25
2519 1338 04-15 16:10 6,6,7,6,3,6,3,12,18,18,5,1,6,2,2,10,2,28,17,1
2522 1341 04-16 10:48 1,2,5,1,9,9,3,15,21,21,7,4,4,5,8,1,2,1,20,4
2525 1344 04-16 05:25 3,5,3,4,3,0,3,18,24,24,2,7,2,8,2,4,2,4,23,7
2528 1347 04-16 00:03 5,1,1,7,9,3,3,21,27,27,4,3,8,3,8,7,2,7,26,10
2531 1350 04-15 18:41 7,4,7,2,3,6,3,24,3,3,6,6,6,6,2,10,2,10,2,13
2534 1353 04-16 13:19 2,7,5,5,9,9,3,27,6,6,1,2,4,1,8,1,2,13,5,16
2537 1356 04-16 07:57 4,3,3,8,3,0,3,30,9,9,3,5,2,4,2,4,2,16,8,19
2540 1359 04-16 02:34 6,6,1,3,9,3,3,3,12,12,5,1,8,7,8,7,2,19,11,22
2543 1362 04-15 21:12 1,2,7,6,3,6,3,6,15,15,7,4,6,2,2,10,2,22,14,25
2546 1365 04-16 15:50 3,5,5,1,9,9,3,9,18,18,2,7,4,5,8,1,2,25,17,1
2549 1368 04-16 10:28 5,1,3,4,3,0,3,12,21,21,4,3,2,8,2,4,2,28,20,4
2552 1371 04-16 05:06 7,4,1,7,9,3,3,15,24,24,6,6,8,3,8,7,2,1,23,7
2555 1374 04-15 23:43 2,7,7,2,3,6,3,18,27,27,1,2,6,6,2,10,2,4,26,10
2558 1377 04-16 18:21 4,3,5,5,9,9,3,21,3,3,3,5,4,1,8,1,2,7,2,13
2561 1380 04-16 12:59 6,6,3,8,3,0,3,24,6,6,5,1,2,4,2,4,2,10,5,16
2564 1383 04-16 07:37 1,2,1,3,9,3,3,27,9,9,7,4,8,7,8,7,2,13,8,19
2567 1386 04-16 02:15 3,5,7,6,3,6,3,30,12,12,2,7,6,2,2,10,2,16,11,22
2570 1389 04-16 20:52 5,1,5,1,9,9,3,3,15,15,4,3,4,5,8,1,2,19,14,25
2573 1392 04-16 15:30 7,4,3,4,3,0,3,6,18,18,6,6,2,8,2,4,2,22,17,1
2576 1395 04-16 10:08 2,7,1,7,9,3,3,9,21,21,1,2,8,3,8,7,2,25,20,4
2579 1398 04-16 04:46 4,3,7,2,3,6,3,12,24,24,3,5,6,6,2,10,2,28,23,7
2582 1401 04-16 23:24 6,6,5,5,9,9,3,15,27,27,5,1,4,1,8,1,2,1,26,10
2585 1404 04-16 18:01 1,2,3,8,3,0,3,18,3,3,7,4,2,4,2,4,2,4,2,13
2588 1407 04-16 12:39 3,5,1,3,9,3,3,21,6,6,2,7,8,7,8,7,2,7,5,16
2591 1410 04-16 07:17 5,1,7,6,3,6,3,24,9,9,4,3,6,2,2,10,2,10,8,19
2594 1413 04-17 01:55 7,4,5,1,9,9,3,27,12,12,6,6,4,5,8,1,2,13,11,22
2597 1416 04-16 20:33 2,7,3,4,3,0,3,30,15,15,1,2,2,8,2,4,2,16,14,25
2600 1419 04-16 15:10 4,3,1,7,9,3,3,3,18,18,3,5,8,3,8,7,2,19,17,1
TXT;

    /** ลำดับค่าในแต่ละบรรทัดของ SONGKRAN */
    private const SONGKRAN_ORDER = [
        ['thongchai', 'wan'], ['athibodi', 'wan'], ['thongchai', 'yam'], ['athibodi', 'yam'], ['thongchai', 'rasi'], ['athibodi', 'rasi'],
        ['thongchai', 'dithi'], ['athibodi', 'dithi'], ['thongchai', 'ruek'], ['athibodi', 'ruek'],
        ['ubat', 'wan'], ['lokawinat', 'wan'], ['ubat', 'yam'], ['lokawinat', 'yam'], ['ubat', 'rasi'], ['lokawinat', 'rasi'],
        ['ubat', 'dithi'], ['lokawinat', 'dithi'], ['ubat', 'ruek'], ['lokawinat', 'ruek'],
    ];

    /** ตัวคำนวณตัวเดียวทั้งคลาส — day() มี memo ภายใน */
    private static ?ThaiRuekYam $engine = null;

    private function engine(): ThaiRuekYam
    {
        return self::$engine ??= new ThaiRuekYam;
    }

    #[Test]
    public function kalayok_matches_songkran_announcements_for_41_years(): void
    {
        $bad = [];
        $ubatBeforeThongchai = 0;
        $worstMin = 0.0;
        foreach (explode("\n", trim(self::SONGKRAN)) as $line) {
            [$be, $cs, $md, $hm, $nums] = explode(' ', trim($line));
            $v = array_map('intval', explode(',', $nums));
            $ky = ThaiRuekYam::kalayok((int) $cs);
            foreach (self::SONGKRAN_ORDER as $i => [$k, $base]) {
                if ($ky[$k][$base] !== $v[$i]) {
                    $bad[] = "{$be} {$k}.{$base}: ได้ {$ky[$k][$base]} ประกาศ {$v[$i]}";
                }
            }
            if (($ky['thongchai']['wan'] + 5) % 7 + 1 === $ky['ubat']['wan']) {
                $ubatBeforeThongchai++;
            }

            // เวลาเถลิงศก (แสดงเป็นเวลาท้องถิ่นกรุงเทพฯ เหมือนประกาศ)
            $y = (int) $be - 543;
            $want = (new \DateTimeImmutable(sprintf('%04d-%s %s:00', $y, $md, $hm), new \DateTimeZone('UTC')))->getTimestamp() / 86400 + 2440587.5;
            $jdLocal = ThaiRuekYam::thalerngsok((int) $cs) + 6.7 / 24;
            $worstMin = max($worstMin, abs($jdLocal - $want) * 1440);

            // จ.ศ. เปลี่ยนตรงเวลาเถลิงศกพอดี
            $this->assertSame((int) $cs, ThaiRuekYam::csAt(ThaiRuekYam::thalerngsok((int) $cs) + 1e-6));
            $this->assertSame((int) $cs - 1, ThaiRuekYam::csAt(ThaiRuekYam::thalerngsok((int) $cs) - 1e-6));
        }

        $this->assertSame([], $bad, 'กาลโยคไม่ตรงประกาศสงกรานต์');
        $this->assertSame(41, $ubatBeforeThongchai, 'วันอุบาทว์ต้องอยู่ก่อนวันธงชัยหนึ่งวันทุกปี');
        $this->assertLessThanOrEqual(1.0, $worstMin, 'เวลาเถลิงศกคลาดเกิน 1 นาที');
    }

    #[Test]
    public function kalayok_linear_formula_equals_the_textbook_formula(): void
    {
        // ธงชัย = (จ.ศ.×10+3) mod 7 · อธิบดี = (จ.ศ. mod 498) mod 7 · อุบาทว์ = (จ.ศ.×10+2) mod 7 · โลกาวินาศ = (จ.ศ.+1120) mod 7
        for ($cs = 1299; $cs <= 1493; $cs++) {
            $a = ThaiRuekYam::kalayok($cs);
            foreach (ThaiRuekYam::kyTraditional($cs) as $k => $wan) {
                $this->assertSame($wan, $a[$k]['wan'], "จ.ศ. {$cs} {$k}");
            }
        }
    }

    #[Test]
    public function kalayok_days_on_the_2026_calendar_match_myhora(): void
    {
        $e = $this->engine();
        $this->assertSame(['athibodi'], array_column($e->day('2026-09-05')['ky_day'], 'key'), '5 ก.ย. 2569 = วันอธิบดี');
        $keys = array_column($e->day('2026-09-07')['ky_day'], 'key');
        sort($keys);
        $this->assertSame(['lokawinat', 'thongchai'], $keys, '7 ก.ย. 2569 = วันธงชัย + โลกาวินาศ');
        // วันเถลิงศก 16 เม.ย. 2569 ใช้ปีใหม่ทั้งวัน (ไม่ติดอุบาทว์ปีเก่า) · 9 เม.ย. ยังเป็นอุบาทว์ปีเก่า
        $this->assertSame([], $e->day('2026-04-16')['ky_day']);
        $this->assertSame(['ubat'], array_column($e->day('2026-04-09')['ky_day'], 'key'));
    }

    #[Test]
    public function yam_attha_kan_matches_the_five_source_table(): void
    {
        $day = ['1 6 4 2 7 5 3 1', '2 7 5 3 1 6 4 2', '3 1 6 4 2 7 5 3', '4 2 7 5 3 1 6 4', '5 3 1 6 4 2 7 5', '6 4 2 7 5 3 1 6', '7 5 3 1 6 4 2 7'];
        $night = ['1 5 2 6 3 7 4 1', '2 6 3 7 4 1 5 2', '3 7 4 1 5 2 6 3', '4 1 5 2 6 3 7 4', '5 2 6 3 7 4 1 5', '6 3 7 4 1 5 2 6', '7 4 1 5 2 6 3 7'];
        $e = $this->engine();
        $start = new \DateTimeImmutable('2026-09-13'); // วันอาทิตย์
        for ($i = 0; $i < 7; $i++) {
            $d = $e->day($start->modify("+{$i} day")->format('Y-m-d'));
            $n = $e->day($start->modify('+'.($i + 1).' day')->format('Y-m-d'));
            $this->assertSame($day[$i], implode(' ', array_column(array_slice($d['yams'], 4, 8), 'lord')), 'กลางวัน '.ThaiRuekYam::WD[$i]);
            $this->assertSame(
                $night[$i],
                implode(' ', array_column(array_merge(array_slice($d['yams'], 12), array_slice($n['yams'], 0, 4)), 'lord')),
                'กลางคืน '.ThaiRuekYam::WD[$i]
            );
            // ชื่อยามแรกของสองฟาก = ชื่อยามของดาวเจ้าวัน (อาทิตย์: สุริชะ / รวิ)
            $this->assertSame(ThaiRuekYam::YAM_DAY[$i + 1], $d['yams'][4]['name']);
            $this->assertSame(ThaiRuekYam::YAM_NIGHT[$i + 1], $d['yams'][12]['name']);
        }
    }

    #[Test]
    public function upper_and_lower_ruek_match_the_myhora_calendar_for_1370_days(): void
    {
        $rows = array_values(array_filter(
            preg_split('/\r?\n/', (string) file_get_contents(__DIR__.'/fixtures/myhora_ruek.txt')),
            static fn (string $l): bool => $l !== '' && ! str_starts_with($l, '#')
        ));
        $this->assertCount(1370, $rows);

        $e = $this->engine();
        $toMin = static fn (string $hm): int => (int) substr($hm, 0, 2) * 60 + (int) substr($hm, -2);
        $seqBad = [];
        $worstMin = 0;
        $changes = 0;
        $low = [];
        $krathingDays = 0;
        $chk = static function (string $name, bool $ours, bool $ref, string $ymd) use (&$low): void {
            $low[$name] ??= ['n' => 0, 'bad' => []];
            if ($ref) {
                $low[$name]['n']++;
            }
            if ($ours !== $ref) {
                $low[$name]['bad'][] = $ymd;
            }
        };

        $start = new \DateTimeImmutable('2024-04-01');
        foreach ($rows as $i => $r) {
            $ymd = $start->modify("+{$i} day")->format('Y-m-d');
            $f = explode('|', $r);
            $d = $e->day($ymd, 13.8612, 100.512);

            // ฤกษ์บน: ลำดับฤกษ์ในวันต้องตรง · เวลาเปลี่ยนคลาดได้ไม่เกิน 10 นาที
            //   ตัดช่วงที่สั้นกว่า 10 นาทีติดเที่ยงคืนออกได้ทั้งสองฝั่ง (ปฏิทินอาจนับการเปลี่ยนตอน 23:5x/00:0x ไว้อีกวัน)
            $p = explode('>', $f[0]);
            $refIdx = [];
            $refT = [];
            foreach ($p as $j => $v) {
                if ($j % 2 === 0) {
                    $refIdx[] = (int) $v;
                } else {
                    $refT[] = $toMin($v);
                }
            }
            $ourIdx = array_column($d['ruek_win'], 'idx');
            $ourT = array_map(static fn (array $w): int => $toMin($w['from_hm']), array_slice($d['ruek_win'], 1));
            $variants = static function (array $idx, array $t): array {
                $out = [[$idx, $t]];
                if ($t !== [] && $t[0] < 10) {
                    $out[] = [array_slice($idx, 1), array_slice($t, 1)];
                }
                if ($t !== [] && 1440 - $t[count($t) - 1] < 10) {
                    $out[] = [array_slice($idx, 0, -1), array_slice($t, 0, -1)];
                }

                return $out;
            };
            $match = null;
            foreach ($variants($ourIdx, $ourT) as $o) {
                foreach ($variants($refIdx, $refT) as $rv) {
                    if ($o[0] === $rv[0]) {
                        $match = [$o, $rv];
                        break 2;
                    }
                }
            }
            if ($match === null) {
                $seqBad[] = $ymd;
            } else {
                foreach ($match[0][1] as $j => $t) {
                    $changes++;
                    $worstMin = max($worstMin, abs($t - $match[1][1][$j]));
                }
            }

            $L = $d['lower'];
            if ($L === null) {
                continue; // ก่อน 9 เม.ย. 2567 ยังไม่มีปฏิทินหลวง
            }
            $g = static fn (string $key): bool => in_array($key, array_column($L['good'], 'key'), true);
            $b = static function (string $key, ?string $variant = null) use ($L): bool {
                foreach ($L['bad'] as $x) {
                    if ($x['key'] === $key && ($variant === null || ($x['variant'] ?? null) === $variant)) {
                        return true;
                    }
                }

                return false;
            };
            $chk('ดิถีอำมฤตโชค', $g('amarit'), str_contains($f[1], 'a'), $ymd);
            $chk('ดิถีสิทธิโชค', $g('sittho'), str_contains($f[1], 's'), $ymd);
            // พฤหัส 3 ค่ำ (ตำรา ก ของ myhora) ยืนยันได้แหล่งเดียว จึงไม่ใส่ — เทียบทุกวันที่เหลือ
            $chk('ดิถีมหาสิทธิโชค (พฤหัส 7 ค่ำ)', $g('mahasittho'), str_contains($f[1], 'm') && ! str_contains($f[1], 'mk'), $ymd);
            $chk('ดิถีชัยโชค', $g('chaiyachok'), str_contains($f[1], 'c'), $ymd);
            $chk('ดิถีราชาโชค', $g('rachachok'), str_contains($f[1], 'r'), $ymd);
            $chk('ดิถีมหาสูญ [ก] อ.ทองเจือ', $b('mahasun', 'ก'), str_contains($f[3], 'k'), $ymd);
            $chk('ดิถีมหาสูญ [ข] พรหมชาติ', $b('mahasun', 'ข'), str_contains($f[3], 'x'), $ymd);
            $phlai = false;
            foreach ($L['bad'] as $x) {
                if ($x['key'] === 'phlai' && (string) $x['level'] === $f[4]) {
                    $phlai = true;
                }
            }
            $chk('ดิถีอายกรรมพลาย', $phlai, $f[4] !== '', $ymd);
            $chk('ดิถีพิฆาต (อาทิตย์–พฤหัส)', $b('phikhat'), $d['wd'] <= 4 && $f[5] === '1', $ymd);
            // กระทิงวัน: ใส่เฉพาะวัน=เดือน=ดิถี → ต้องเป็นส่วนย่อยของวันที่ myhora ติดไว้ (myhora นับกว้างกว่า)
            $kr = $b('krathing');
            $chk('กระทิงวัน ⊆ myhora', $kr && $f[6] !== '1', false, $ymd);
            if ($kr) {
                $krathingDays++;
            }
            $chk('ดิถีเรียงหมอน', in_array('riangmon', array_column($L['info'], 'key'), true), ($f[9] ?? '') === '1', $ymd);
        }

        $this->assertSame([], $seqBad, 'ลำดับฤกษ์บนไม่ตรง myhora');
        $this->assertLessThanOrEqual(10, $worstMin, "เวลาเปลี่ยนฤกษ์ {$changes} ครั้ง คลาดเกิน 10 นาที");
        $this->assertGreaterThan(1000, $changes);
        $this->assertGreaterThan(0, $krathingDays);
        foreach ($low as $name => $s) {
            $this->assertSame([], array_slice($s['bad'], 0, 5), "{$name} ({$s['n']} วันใน myhora) ไม่ตรง");
            $this->assertGreaterThan(0, $s['n'] + ($name === 'กระทิงวัน ⊆ myhora' ? 1 : 0), "{$name} ไม่เคยถูกทดสอบเลย");
        }
    }

    #[Test]
    public function lunar_calendar_matches_myhora_holy_days_and_every_new_year(): void
    {
        $cases = [
            // วันที่ → [ขึ้น?, ค่ำ, เดือน, เดือน 8 หลัง?]
            '2024-04-09' => [true, 1, 5, false],
            '2024-05-22' => [true, 15, 6, false],   // วิสาขบูชา 2567
            '2024-07-20' => [true, 15, 8, false],   // อาสาฬหบูชา 2567
            '2025-05-11' => [true, 15, 6, false],   // วิสาขบูชา 2568
            '2025-07-10' => [true, 15, 8, false],   // อาสาฬหบูชา 2568
            '2026-03-03' => [true, 15, 4, false],   // มาฆบูชา 2569
            '2026-05-31' => [true, 15, 7, false],   // วิสาขบูชา 2569 (อธิกมาส → เดือน 7)
            '2026-07-29' => [true, 15, 8, true],    // อาสาฬหบูชา 2569 = เดือน 8 หลัง
            '2026-08-02' => [false, 4, 8, true],
            '2026-09-11' => [false, 14, 9, false],
            '2026-09-12' => [true, 1, 10, false],
            '2026-09-16' => [true, 5, 10, false],
            '2026-10-01' => [false, 5, 10, false],
            '2026-12-16' => [true, 7, 1, false],
            '2027-01-02' => [false, 9, 1, false],
        ];
        // วันขึ้น 1 ค่ำ เดือน 5 ของทุกปี 2025–2057 (myhora /calendar/thai-03|04-พ.ศ.aspx) — วันก่อนหน้าต้องเป็นแรม 15 ค่ำ เดือน 4
        $newYears = ['2025-03-29', '2026-03-19', '2027-04-07', '2028-03-26', '2029-03-15', '2030-04-03', '2031-03-23', '2032-04-10', '2033-03-31',
            '2034-03-20', '2035-04-08', '2036-03-28', '2037-03-17', '2038-04-05', '2039-03-25', '2040-03-13', '2041-04-01', '2042-03-22', '2043-03-11',
            '2044-03-29', '2045-03-18', '2046-04-06', '2047-03-27', '2048-03-15', '2049-04-03', '2050-03-23', '2051-04-11', '2052-03-30', '2053-03-20',
            '2054-04-08', '2055-03-28', '2056-03-17', '2057-04-05'];
        foreach ($newYears as $ymd) {
            $cases[$ymd] = [true, 1, 5, false];
            $cases[(new \DateTimeImmutable($ymd))->modify('-1 day')->format('Y-m-d')] = [false, 15, 4, false];
        }

        foreach ($cases as $ymd => [$wax, $d, $m, $second]) {
            $r = ThaiLunarCalendar::of(new \DateTimeImmutable($ymd));
            $this->assertNotNull($r, $ymd);
            $this->assertSame([$wax, $d, $m, $second], [$r['waxing'], $r['day'], $r['month'], $r['second_eighth']], "{$ymd} ได้ {$r['label']}");
        }

        // วันพระ: วิสาขบูชา/อาสาฬหบูชา = ขึ้น 15 ค่ำ · 11 ก.ย. 2569 แรม 14 ค่ำ เดือน 9 (เดือนขาด 29 วัน) = วันพระ
        $this->assertTrue(ThaiLunarCalendar::of(new \DateTimeImmutable('2026-05-31'))['holy']);
        $this->assertTrue(ThaiLunarCalendar::of(new \DateTimeImmutable('2026-09-11'))['holy']);
        $this->assertFalse(ThaiLunarCalendar::of(new \DateTimeImmutable('2026-09-10'))['holy']);
        // ก่อนจุดยึด = ไม่มีข้อมูล (ห้ามเดา)
        $this->assertNull(ThaiLunarCalendar::of(new \DateTimeImmutable('1990-05-01')));
    }

    #[Test]
    public function activity_rules_follow_the_textbook(): void
    {
        $e = $this->engine();

        // แต่งงาน 60 วัน: ทุกวันที่แนะนำเป็น อา/จ/ศ ไม่ติดข้อห้าม และมีช่วงเวลาผ่านเกณฑ์ 06:00–18:00
        $sug = $e->findDays('wedding', '2026-09-15', 60);
        $this->assertNotEmpty($sug);
        foreach ($sug as $s) {
            $this->assertContains($s['D']['wd'], [0, 1, 5], $s['ymd']);
            $this->assertTrue($s['ev']['day_ok'], $s['ymd']);
            $this->assertNotEmpty($s['wins'], $s['ymd']);
        }

        // 25 ก.ย. 2569: วันไม่ติดห้าม แต่ทั้งวันไม่มีช่วงผ่าน (เช้าฤกษ์ศตภิษัช = ฤกษ์โลกาวินาศ จ.ศ. 1388 · บ่ายเพชฌฆาต)
        $ev = ThaiRuekYam::evaluate('wedding', $e->day('2026-09-25'));
        $this->assertTrue($ev['day_ok']);
        $this->assertSame([], array_filter($ev['segs'], static fn (array $s): bool => $s['ok']));
        $this->assertNotContains('2026-09-25', array_column($sug, 'ymd'));

        // อังคาร = งานมงคลทุกงานไม่ผ่าน แต่งานศพไม่เกี่ยว
        $tue = $e->day('2026-10-06');
        $this->assertSame(2, $tue['wd']);
        $this->assertFalse(ThaiRuekYam::evaluate('general', $tue)['day_ok']);
        $this->assertFalse(ThaiRuekYam::evaluate('ordain', $tue)['day_ok']);

        // อัคนิโรธตามงาน: บวช 14 · ออกรถ 8 · ขึ้นบ้าน 6
        $byDay = [];
        $cursor = new \DateTimeImmutable('2026-10-01');
        for ($i = 0; $i < 40; $i++) {
            $D = $e->day($cursor->modify("+{$i} day")->format('Y-m-d'));
            $byDay[$D['lunar']['day']] ??= $D;
        }
        $bans = static fn (string $act, array $D): string => implode(' | ', array_column(
            array_filter(ThaiRuekYam::evaluate($act, $D)['items'], static fn (array $x): bool => $x['kind'] === 'ban'),
            'text'
        ));
        $this->assertStringContainsString('14 ค่ำ', $bans('ordain', $byDay[14]));
        $this->assertStringContainsString('8 ค่ำ', $bans('car', $byDay[8]));
        $this->assertStringContainsString('6 ค่ำ', $bans('house', $byDay[6]));
    }
}
