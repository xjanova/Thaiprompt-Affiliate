<?php

namespace Tests\Unit\Services;

use App\Services\Fortune\AstroAspects;
use App\Services\Fortune\PlanetEphemeris;
use App\Services\Fortune\ThaiAstrologyService;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * 🔭 การผูกลัคนาแบบ "ตามตำรา ไม่เดา" + มุมสัมพันธ์ (2026-09-09)
 *
 * ที่มา: ตรวจระบบด้วยสายตาโหรจริงแล้วพบว่า
 *   - ไม่รู้เวลาเกิด 97.8% ของบิล แต่ระบบเดาลัคนาจากเที่ยงวัน (ถูกแค่ 8%)
 *   - เลนที่ลูกค้าจ่ายเงินไม่เคยได้ "มุมสัมพันธ์" (กุม/เล็ง/ตรีโกณ) เลยสักครั้ง
 *
 * เทสต์ชุดนี้ล็อกพฤติกรรมใหม่ไว้: ไม่มีข้อมูล = พูดตามตรง ไม่ใช่เดาให้เนียน
 */
class LagnaAndAspectsTest extends TestCase
{
    protected ThaiAstrologyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ThaiAstrologyService;
    }

    /** @test */
    public function ephemeris_includes_uranus_as_the_tenth_planet(): void
    {
        $positions = (new PlanetEphemeris)->positions(Carbon::create(2026, 9, 9, 12, 0, 0));

        $this->assertCount(10, $positions, 'ตำราไทยสมัยใหม่นับมฤตยูเป็นดาวลำดับที่ 10');
        $this->assertArrayHasKey('Uranus', $positions);
        $this->assertSame('มฤตยู', $positions['Uranus']['th']);
        $this->assertSame(0, $positions['Uranus']['num'], 'มฤตยู = ดาวเลข ๐');
    }

    /**
     * @test
     *
     * ราหู/เกตุ ถอยหลังตลอดโดยธรรมชาติ — ติดป้าย "พักร" ทุกวันคือสัญญาณรบกวน
     * โหรอ่านแล้วสะดุดทันที เพราะไม่มีใครเรียกราหูว่าพักร
     */
    public function nodes_are_never_labelled_retrograde(): void
    {
        $positions = (new PlanetEphemeris)->positions(Carbon::create(2026, 9, 9, 12, 0, 0));

        $this->assertFalse($positions['Rahu']['retro']);
        $this->assertFalse($positions['Ketu']['retro']);
        $this->assertTrue($positions['Rahu']['always_retro']);
    }

    /** @test */
    public function known_birth_time_produces_a_real_lagna(): void
    {
        $block = $this->service->formatPersonBlock('1990-05-15 08:30', null, false, 'เชียงใหม่');

        $this->assertStringContainsString('⬆️ ลัคนา: ราศี', $block);
        $this->assertStringContainsString('เกิดที่ จ.เชียงใหม่', $block);
        $this->assertStringContainsString('นวางค์ลัคนา', $block, 'รู้เวลาเกิด → คำนวณนวางค์ได้');
        $this->assertSame('lagna', $this->service->lagnaBasis());
    }

    /**
     * @test
     *
     * 🚨 หัวใจของรอบนี้ — ไม่รู้เวลาเกิด ห้ามพิมพ์ว่า "ลัคนาราศี..." เด็ดขาด
     */
    public function unknown_birth_time_never_claims_a_lagna(): void
    {
        $block = $this->service->formatPersonBlock('1990-05-15', null, false);

        $this->assertStringNotContainsString('⬆️ ลัคนา: ราศี', $block);
        $this->assertStringContainsString('ผูกไม่ได้', $block);
        $this->assertStringNotContainsString('นวางค์', $block, 'นวางค์ช่องละ 3°20\' — เดาเวลาแล้วไม่มีทางถูก');
        $this->assertContains($this->service->lagnaBasis(), ['moon', 'none']);
    }

    /**
     * @test
     *
     * จันทร์ไม่ย้ายราศีวันนั้น → ตำราให้ยกจันทร์ลัคน์ขึ้นเป็นภพที่ 1 ได้
     */
    public function it_falls_back_to_moon_lagna_when_the_moon_stays_in_one_sign(): void
    {
        $basis = null;
        $block = '';

        // ไล่หาวันที่จันทร์อยู่ราศีเดียวทั้งวัน (เกิดราว 57% ของวัน)
        for ($d = 1; $d <= 28; $d++) {
            $ymd = sprintf('1990-05-%02d', $d);
            $block = $this->service->formatPersonBlock($ymd, null, false);
            if ($this->service->lagnaBasis() === 'moon') {
                $basis = 'moon';
                break;
            }
        }

        $this->assertSame('moon', $basis, 'ต้องมีอย่างน้อย 1 วันในเดือนที่จันทร์ไม่ย้ายราศี');
        $this->assertStringContainsString('จันทร์ลัคน์', $block);
        $this->assertStringContainsString('ห้ามเรียกว่าลัคนา', $block);
        $this->assertStringContainsString('ภพ', $block, 'จันทร์ลัคน์ใช้นับภพได้ตามตำรา');
    }

    /**
     * @test
     *
     * จันทร์ย้ายราศีระหว่างวัน + ไม่รู้เวลาเกิด = ชี้ภพไม่ได้จริง ๆ → ต้องห้ามใช้ภพทั้งหมด
     */
    public function it_forbids_houses_when_even_the_moon_sign_is_uncertain(): void
    {
        $found = false;

        for ($d = 1; $d <= 28; $d++) {
            $ymd = sprintf('1990-05-%02d', $d);
            $block = $this->service->formatPersonBlock($ymd, null, false);
            if ($this->service->lagnaBasis() === 'none') {
                $found = true;
                $this->assertStringContainsString('จันทร์ย้ายราศี', $block);
                $this->assertStringContainsString('ห้ามพูดถึงภพ', $block);
                break;
            }
        }

        $this->assertTrue($found, 'ต้องมีอย่างน้อย 1 วันในเดือนที่จันทร์ย้ายราศี');
    }

    /** @test */
    public function lagna_directive_matches_the_basis_the_chart_actually_used(): void
    {
        $this->assertStringContainsString(
            'ลัคนา & เรือนชะตา',
            ThaiAstrologyService::lagnaSectionDirective('lagna')
        );

        $moon = ThaiAstrologyService::lagnaSectionDirective('moon');
        $this->assertStringContainsString('จันทร์ลัคน์ & เรือนชะตา', $moon);
        $this->assertStringContainsString('ห้ามเรียกว่า "ลัคนา"', $moon);

        // ไม่มีฐานให้นับภพ → ต้องไม่มีเซคชั่นนี้เลย (ไม่งั้นสั่งให้เขียนของที่ผังไม่มี)
        $this->assertSame('', ThaiAstrologyService::lagnaSectionDirective('none'));
    }

    /** @test */
    public function it_resolves_the_basis_before_building_the_chart(): void
    {
        $this->assertSame('none', $this->service->resolveLagnaBasis(null, null));
        $this->assertSame('lagna', $this->service->resolveLagnaBasis('1990-05-15', 8.5));
        $this->assertContains($this->service->resolveLagnaBasis('1990-05-15', null), ['moon', 'none']);
    }

    /** @test */
    public function aspect_separation_never_exceeds_180_degrees(): void
    {
        $this->assertEqualsWithDelta(10.0, AstroAspects::separation(5.0, 355.0), 0.001);
        $this->assertEqualsWithDelta(180.0, AstroAspects::separation(0.0, 180.0), 0.001);
        $this->assertEqualsWithDelta(90.0, AstroAspects::separation(350.0, 80.0), 0.001);
    }

    /** @test */
    public function it_names_thai_aspects_correctly(): void
    {
        $this->assertSame('กุม', AstroAspects::between(10.0, 12.0)['name']);
        $this->assertSame('เล็ง', AstroAspects::between(10.0, 190.0)['name']);
        $this->assertSame('ตรีโกณ', AstroAspects::between(10.0, 130.0)['name']);
        $this->assertSame('จตุโกณ', AstroAspects::between(10.0, 100.0)['name']);
        $this->assertNull(AstroAspects::between(10.0, 55.0), '45° ไม่ใช่มุมที่ตำราไทยใช้');
    }

    /**
     * @test
     *
     * ☋ (2026-09-11) เกตุไทยเดินเอง ไม่ได้ผูกตรงข้ามราหูแล้ว — ห้ามมี "มุมแฝด" อีก
     *
     * สมัยเกตุ = ราหู + 180° ทุกมุมที่ดาวทำกับราหู จะมีมุมคู่กับเกตุที่ orb เท่ากันเป๊ะ
     * (กุมราหู ⇔ เล็งเกตุ · จตุโกณราหู ⇔ จตุโกณเกตุ) กินช่อง 6 มุมที่โชว์ในผังไปฟรี ๆ
     * ผังจริงของบิล 12840 เคยขึ้น "จันทร์ จตุโกณ ราหู 1.2°" คู่กับ "จันทร์ จตุโกณ เกตุ 1.2°"
     */
    public function ketu_no_longer_mirrors_every_rahu_aspect(): void
    {
        $positions = (new PlanetEphemeris)->positions(Carbon::create(2026, 9, 9, 12, 0, 0));

        $sep = AstroAspects::separation($positions['Rahu']['lon'], $positions['Ketu']['lon']);
        $this->assertGreaterThan(30.0, abs($sep - 180.0), 'เกตุไทยไม่ได้อยู่ตรงข้ามราหู');

        // orb ของมุมที่ดาวแต่ละดวงทำกับราหู / กับเกตุ
        $orbWith = ['Rahu' => [], 'Ketu' => []];
        foreach (AstroAspects::withinChart($positions) as $a) {
            foreach (['Rahu', 'Ketu'] as $node) {
                if ($a['a_key'] === $node || $a['b_key'] === $node) {
                    $other = $a['a_key'] === $node ? $a['b_key'] : $a['a_key'];
                    $orbWith[$node][$other] = $a['orb'];
                }
            }
        }

        foreach ($orbWith['Rahu'] as $planet => $orb) {
            if ($planet === 'Ketu' || ! isset($orbWith['Ketu'][$planet])) {
                continue;
            }
            $this->assertNotSame($orb, $orbWith['Ketu'][$planet], "มุม {$planet}-ราหู กับ {$planet}-เกตุ ต้องไม่ใช่เงาของกัน");
        }
    }

    /**
     * @test
     *
     * ดาวจรในเลนจ่ายเงินต้องเป็นเกตุไทย + บอกความเร็วจริง (ราศีละ ~2 เดือน ไม่ใช่ 1 ปีครึ่งแบบราหู)
     * ไม่งั้นผังบอกตำแหน่งหนึ่ง แต่บรรทัดความเร็วสอนโมเดลอีกแบบ = โมเดลเลือกเชื่ออันเดียว
     */
    public function transit_block_uses_thai_ketu_position_and_speed(): void
    {
        $block = $this->service->formatTransitBlock(null, Carbon::create(2026, 9, 16, 9, 0, 0, 'Asia/Bangkok'));

        $this->assertStringContainsString('☋ เกตุ จรราศีพิจิก', $block, 'เกตุไทย 16 ก.ย. 2569 อยู่พิจิก ไม่ใช่สิงห์ตรงข้ามราหู');
        $this->assertStringContainsString('เกตุ ~2 เดือน', $block);
        $this->assertStringNotContainsString('ราหู-เกตุ ~1 ปีครึ่ง', $block);
    }

    /** @test */
    public function paid_lanes_now_receive_natal_and_transit_aspects(): void
    {
        $block = $this->service->formatPersonBlock('1990-05-15 08:30', null, true, 'กรุงเทพมหานคร');

        $this->assertStringContainsString('มุมสัมพันธ์ในดวงกำเนิด', $block);
        $this->assertStringContainsString('ดาวจรกระทบดวงกำเนิด', $block);
    }

    /**
     * @test
     *
     * ไม่รู้เวลาเกิด = จันทร์คลาด ±6.6° ซึ่งกว้างกว่า orb ของมุมส่วนใหญ่
     * ⇒ มุมที่มีจันทร์ร่วมคือการเดา ไม่ใช่การคำนวณ ต้องไม่โผล่ในบล็อกมุม
     */
    public function moon_aspects_are_dropped_when_birth_time_is_unknown(): void
    {
        $block = $this->service->formatPersonBlock('1990-05-15', null, false);

        $lines = array_filter(
            explode("\n", $block),
            fn ($l) => str_contains($l, 'กุม') || str_contains($l, 'เล็ง')
                || str_contains($l, 'ตรีโกณ') || str_contains($l, 'จตุโกณ')
        );

        foreach ($lines as $line) {
            $this->assertStringNotContainsString('จันทร์', $line, "มุมที่มีจันทร์ต้องถูกตัดทิ้ง: {$line}");
        }
    }
}
