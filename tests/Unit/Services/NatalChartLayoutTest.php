<?php

namespace Tests\Unit\Services;

use App\Services\Fortune\ThaiAstrologyService;
use App\Services\FortuneChartService;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * 🖼️ รูปผังดวงกำเนิด (buildNatalPngChart) — ป้ายภพและป้ายดาวต้องไม่ทับกัน ไม่ล้ำเส้นแบ่งภพ (2026-10-11)
 *
 * ที่มา: owner ขอ "ตัวหนังสืออ่านง่ายกว่านี้ แต่ไม่ทับกัน อะไรต่ออะไรมันเล็กไป"
 *   ⇒ ขยายป้ายภพเป็น 36pt และเปลี่ยนดาวเป็นป้ายแคปซูล [ไอคอน | ชื่อดาว 24pt] บนเทมเพลต AI
 *   ตำแหน่งทุกชิ้นคิดใน natalLayout() — ภพที่ดาวกองกัน (3+ ดวงพบ ~67% ของผัง) ต้องหาที่ว่างให้เองโดยไม่ทับ
 *
 * เทสต์ชุดนี้ล็อก (ผังจริงทุก 90 วัน 1950-2010 ทั้งมี/ไม่มีเวลาเกิด + เคสดาวกอง 7 ดวง + ฐาน 'none'):
 *   1. ดาวทุกดวงของผังอยู่ในรูปครบ ดวงละครั้ง และอยู่ในช่องของภพ/ราศีตัวเอง
 *   2. ป้ายภพอยู่ในช่องตัวเอง (ห่างเส้นแบ่ง · ใต้วงแหวนทอง · นอกวงกลาง)
 *   3. ป้ายดาวอยู่ในช่องตัวเอง ไม่ทับป้ายภพ และไม่ทับป้ายดาวดวงอื่น
 *   4. ไม่มีช่องไหนตกโหมด "ยอมเบียด" (forced)
 *
 * ไม่แตะ DB · ต้องมี GD + FreeType (วัดความกว้างตัวอักษรจริง)
 */
class NatalChartLayoutTest extends TestCase
{
    public function test_labels_and_planets_never_overlap_on_real_charts(): void
    {
        if (! function_exists('imagettfbbox')) {
            $this->markTestSkipped('ต้องมี GD + FreeType');
        }

        $svc = new FortuneChartService;
        $font = resource_path('fonts/NotoSansThai-Bold.ttf');

        $cases = [
            ['1962-02-04 12:00', null, null],  // ดาว 7 ดวงกองช่องเดียว
            [$this->findBirthDayWithBasis('none'), null, null],
        ];
        $d0 = Carbon::create(1950, 1, 1);
        for ($k = 0; $k < 22000; $k += 90) {
            $ymd = $d0->copy()->addDays($k)->format('Y-m-d');
            $cases[] = [$ymd, 10.0, 'กรุงเทพมหานคร'];
            $cases[] = [$ymd, null, null];
        }

        $charts = 0;
        foreach ($cases as [$ymd, $hour, $province]) {
            $d = $svc->natalChartData($ymd, 'ทดสอบ', $hour, $province);
            if ($d === null) {
                continue;
            }
            $charts++;
            $this->assertChartLayoutClean($svc, $d, $svc->natalLayout($d, $font), "{$ymd} ".($hour === null ? 'ไม่รู้เวลา' : 'มีเวลา'));
        }

        $this->assertGreaterThan(400, $charts, 'ผังตัวอย่างน้อยผิดปกติ');
    }

    private function assertChartLayoutClean(FortuneChartService $svc, array $d, array $layout, string $case): void
    {
        $dividers = FortuneChartService::NATAL_DIVIDERS;
        $none = [-9e9, -9e9, -9e9, -9e9];

        // 1. ดาวครบ ดวงละครั้ง อยู่ช่องตัวเอง
        $houseMode = ! empty($d['planetPositions']);
        $order = (array) config('thai_astrology_knowledge.zodiac_order', []);
        $expected = [];
        for ($i = 1; $i <= 12; $i++) {
            $keys = $houseMode ? ($d['planetPositions'][$i] ?? []) : ($d['signPositions'][$order[$i - 1] ?? ''] ?? []);
            foreach ($keys as $key) {
                $expected[$key] = $i;
            }
        }
        $placed = array_column($layout['planets'], 'sector', 'key');
        ksort($expected);
        ksort($placed);
        $this->assertSame($expected, $placed, "{$case}: ดาวต้องครบและอยู่ช่องของตัวเอง");
        $this->assertCount(count($expected), $layout['planets'], "{$case}: ดาววาดซ้ำ");

        $labelsBySector = array_column($layout['labels'], null, 'sector');
        $this->assertCount(12, $labelsBySector, "{$case}: ป้ายช่องต้องครบ 12");

        // 2. ป้ายภพอยู่ในช่องตัวเอง
        foreach ($labelsBySector as $sector => $l) {
            $this->assertTrue(
                $svc->natalBoxFree($l['box'], $dividers[$sector - 1], $dividers[$sector], $none, []),
                "{$case}: ป้ายช่อง {$sector} ({$l['title']}) ล้ำเส้นแบ่ง/วงแหวน/วงกลาง"
            );
        }

        // 3-4. ป้ายดาวอยู่ในช่อง · ไม่ทับป้ายภพ · ไม่ทับกัน · ไม่ตกโหมดยอมเบียด
        $bySector = [];
        foreach ($layout['planets'] as $pl) {
            $bySector[$pl['sector']][] = $pl;
        }
        foreach ($bySector as $sector => $pls) {
            foreach ($pls as $k => $pl) {
                $this->assertArrayNotHasKey('forced', $pl, "{$case}: ช่อง {$sector} วางดาวไม่ลงจนต้องเบียด");
                $others = array_map(fn (array $o) => $o['box'], array_values(array_filter($pls, fn ($o, $j) => $j !== $k, ARRAY_FILTER_USE_BOTH)));
                $this->assertTrue(
                    $svc->natalBoxFree($pl['box'], $dividers[$sector - 1], $dividers[$sector], $labelsBySector[$sector]['box'], $others),
                    "{$case}: ดาว {$pl['planet']['name']} ช่อง {$sector} ทับป้าย/ดาวอื่น หรือล้ำเส้นแบ่ง"
                );
            }
        }
    }

    private function findBirthDayWithBasis(string $basis): string
    {
        $astro = new ThaiAstrologyService;
        for ($d = 1; $d <= 28; $d++) {
            $ymd = sprintf('1990-05-%02d', $d);
            if ($astro->resolveLagnaBasis($ymd, null) === $basis) {
                return $ymd;
            }
        }

        $this->fail("ไม่พบวันเกิดเดือน พ.ค. 2533 ที่ฐานนับภพเป็น {$basis}");
    }
}
