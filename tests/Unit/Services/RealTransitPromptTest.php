<?php

namespace Tests\Unit\Services;

use App\Models\FortuneTellingSetting;
use App\Services\Fortune\PlanetEphemeris;
use App\Services\Fortune\ThaiAstrologyService;
use App\Services\FortuneAIService;
use App\Services\FortuneConversationService;
use App\Support\ThaiProvinces;
use Carbon\Carbon;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 🔭 ช่อง {transit_info} ของพรอมต์ดูดวง 39 ต้องเป็น "ดาวจรจริง" (2026-09-11)
 *
 * ที่มา: ช่องนี้เคยได้ภพของดาวจรจากสูตร (ปี×31 + วันในปี) ÷ (ความเร็ว/12) ใน
 *   FortuneChartService::calculateTransitForDate() + บังคับเกตุห่างราหู 6 ภพ + ตาราง "เหตุการณ์ดาว"
 *   12 เดือนตายตัว แล้วติดป้ายส่งโมเดลว่า "คำนวณจากหลักเจ้าชนะ ห้ามแต่งตำแหน่งเอง"
 *   ⇒ พรอมต์บิล 39 ที่จ่ายเงินแล้วมีดาวจร 2 ชุดขัดกันในใบเดียว (ชุดสูตร + ผังจริงที่ต่อท้าย)
 *
 * เทสต์ชุดนี้ล็อก 3 อย่าง:
 *   1. ตำแหน่งทุกจุดตรวจ (ตอนนี้ / 1 / 3 / 6 / 12 เดือน) = PlanetEphemeris::positions() ณ วันนั้น
 *      และเปลี่ยนตามวันที่จริง — เทียบกับ ephemeris ตอนรัน ไม่ฮาร์ดโค้ดราศี
 *      (เครื่องคำนวณดาวปรับความแม่นได้ เช่น เกตุไทยสุริยยาตร์ เทสต์ต้องไม่พังตาม)
 *   2. ภพพิมพ์เฉพาะเมื่อมีฐานนับภพจริง (ลัคนา / จันทร์ลัคน์) — ไม่มีฐาน = ไม่มีเลขภพเลย
 *   3. พรอมต์ใบจริงของเลน 39 มี "ดาวจรวันนี้" หลายก้อน (template + ผังที่ต่อท้าย) — ทุกก้อนต้องตรงกันทุกตัวอักษร
 *
 * ไม่แตะ DB — สร้าง service ด้วย newInstanceWithoutConstructor (constructor อ่าน settings จาก DB)
 */
class RealTransitPromptTest extends TestCase
{
    /** จุดตรวจที่บล็อกต้องมี — "ตอนนี้" + TRANSIT_OUTLOOK_MONTHS */
    private const CHECKPOINTS = [0, 1, 3, 6, 12];

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────
    // 1. ตรงกับ PlanetEphemeris + เปลี่ยนตามวันที่
    // ─────────────────────────────────────────────────────────────

    public function test_every_checkpoint_matches_planet_ephemeris(): void
    {
        $now = $this->bangkok(2026, 9, 11);
        $block = (new ThaiAstrologyService)->formatTransitOutlookBlock('1990-05-15', null, null, $now);

        $this->assertNotSame('', $block);
        $eph = new PlanetEphemeris;

        foreach (self::CHECKPOINTS as $months) {
            $real = $eph->positions($now->copy()->addMonthsNoOverflow($months)->setTime(12, 0, 0));

            // รวมเกตุด้วย — ต้องมาจาก positions() เท่านั้น ไม่ใช่ราหู + 6 ภพแบบสูตรเดิม
            foreach (ThaiAstrologyService::TIMING_PLANETS as $key) {
                $this->assertStringContainsString(
                    $this->cellLabel($months).' ราศี'.$real[$key]['sign'],
                    $this->timelineRow($block, $real[$key]['th']),
                    "ดาว{$real[$key]['th']} ที่จุดตรวจ {$months} เดือน ต้องตรงกับ PlanetEphemeris"
                );
            }
        }
    }

    public function test_today_block_is_the_same_transit_block_the_chart_uses(): void
    {
        $now = $this->bangkok(2026, 9, 11);
        $astro = new ThaiAstrologyService;
        $block = $astro->formatTransitOutlookBlock('1990-05-15', null, null, $now);

        // formatTransitBlock ตัวเดิม — ข้อความของ "วันนี้" ต้องตรงกันทุกตัวอักษร
        $this->assertStringStartsWith($astro->formatTransitBlock($this->anchorOf($astro), $now), $block);
    }

    public function test_transit_text_changes_with_the_date(): void
    {
        $astro = new ThaiAstrologyService;
        $sep = $this->bangkok(2026, 9, 11);
        $mar = $this->bangkok(2027, 3, 11);

        $a = $astro->formatTransitOutlookBlock('1990-05-15', null, null, $sep);
        $b = $astro->formatTransitOutlookBlock('1990-05-15', null, null, $mar);

        $this->assertNotSame($a, $b);
        $this->assertStringContainsString('ดาวจรวันนี้ (11 กันยายน 2569)', $a);
        $this->assertStringContainsString('ดาวจรวันนี้ (11 มีนาคม 2570)', $b);

        $eph = new PlanetEphemeris;
        $pa = $eph->positions($sep->copy()->setTime(12, 0, 0));
        $pb = $eph->positions($mar->copy()->setTime(12, 0, 0));
        $moved = array_filter(
            ThaiAstrologyService::TIMING_PLANETS,
            fn (string $k) => $pa[$k]['sign'] !== $pb[$k]['sign']
        );

        // อาทิตย์ย้ายราศีทุกเดือน ⇒ ห่าง 6 เดือนต้องมีอย่างน้อย 1 ดวงเสมอ
        $this->assertNotEmpty($moved);
        foreach ($moved as $k) {
            $this->assertStringContainsString('ตอนนี้ ราศี'.$pa[$k]['sign'], $this->timelineRow($a, $pa[$k]['th']));
            $this->assertStringContainsString('ตอนนี้ ราศี'.$pb[$k]['sign'], $this->timelineRow($b, $pb[$k]['th']));
        }
    }

    public function test_sign_change_marker_is_computed_not_left_to_the_model(): void
    {
        $now = $this->bangkok(2026, 9, 11);
        $block = (new ThaiAstrologyService)->formatTransitOutlookBlock('1990-05-15', null, null, $now);
        $eph = new PlanetEphemeris;

        // อาทิตย์: ตอนนี้ → อีก 1 เดือน ย้ายราศีแน่นอน (เดินราศีละ ~30 วัน)
        $sunNow = $eph->positions($now->copy()->setTime(12, 0, 0))['Sun']['sign'];
        $sunNext = $eph->positions($now->copy()->addMonthsNoOverflow(1)->setTime(12, 0, 0))['Sun']['sign'];
        $this->assertNotSame($sunNow, $sunNext);
        $this->assertStringContainsString(
            'อีก 1 เดือน ราศี'.$sunNext,
            $this->timelineRow($block, 'อาทิตย์')
        );
        $this->assertMatchesRegularExpression(
            '/อีก 1 เดือน ราศี'.preg_quote($sunNext, '/').'[^→]*\(ย้ายราศี\)/u',
            $this->timelineRow($block, 'อาทิตย์')
        );
    }

    // ─────────────────────────────────────────────────────────────
    // 2. ภพ — มีฐานจริงเท่านั้น
    // ─────────────────────────────────────────────────────────────

    public function test_houses_follow_the_real_lagna_when_birth_time_is_known(): void
    {
        $now = $this->bangkok(2026, 9, 11);
        $astro = new ThaiAstrologyService;
        $block = $astro->formatTransitOutlookBlock('1990-05-15 08:30', null, 'เชียงใหม่', $now);

        $this->assertSame('lagna', $astro->lagnaBasis());
        $this->assertStringContainsString('ภพนับจากลัคนา', $block);

        // ลัคนาคำนวณแยกด้วยตัวเดียวกับผัง (พิกัดเชียงใหม่ เวลา 08:30)
        $coords = ThaiProvinces::coords('เชียงใหม่');
        $lagna = $astro->siderealLagna(Carbon::parse('1990-05-15 08:30'), $coords['lat'], $coords['lon']);
        $this->assertNotNull($lagna);

        $this->assertHousesCountedFrom($lagna, $block, $now);
    }

    public function test_houses_count_from_moon_lagna_when_birth_time_is_unknown(): void
    {
        $now = $this->bangkok(2026, 9, 11);
        $astro = new ThaiAstrologyService;
        $ymd = $this->findBirthDayWithBasis($astro, 'moon');

        $block = $astro->formatTransitOutlookBlock($ymd, null, null, $now);

        $this->assertSame('moon', $astro->lagnaBasis());
        $this->assertStringContainsString('ภพนับจากจันทร์ลัคน์', $block);
        $this->assertStringNotContainsString('(นับจากลัคนา)', $block, 'ห้ามเรียกจันทร์ลัคน์ว่าลัคนา');

        $moonSign = (new PlanetEphemeris)->positions(Carbon::parse($ymd)->setTime(0, 0, 0))['Moon']['sign'];
        $this->assertHousesCountedFrom($moonSign, $block, $now);
    }

    public function test_no_house_numbers_at_all_when_the_chart_has_no_anchor(): void
    {
        $now = $this->bangkok(2026, 9, 11);
        $astro = new ThaiAstrologyService;
        $ymd = $this->findBirthDayWithBasis($astro, 'none');

        $block = $astro->formatTransitOutlookBlock($ymd, null, null, $now);

        $this->assertSame('none', $astro->lagnaBasis());
        $this->assertNotSame('', $block, 'ไม่มีภพ ≠ ไม่มีดาวจร — ราศีของดาวจรยังต้องส่งให้โมเดล');
        $this->assertDoesNotMatchRegularExpression('/ภพ\s*\d/u', $block, 'ไม่มีฐานนับภพ = ห้ามมีเลขภพ');
        $this->assertStringContainsString('ดวงนี้ไม่มีภพ', $block);
    }

    // ─────────────────────────────────────────────────────────────
    // ดาวมิตร/ศัตรู — ต้องมาจากดาวเจ้าเรือนชุดเดียวกับหัวผัง
    // ─────────────────────────────────────────────────────────────

    public function test_friend_and_enemy_tags_follow_the_birth_day_ruler(): void
    {
        $now = $this->bangkok(2026, 9, 11);
        $sunday = $this->firstWeekday(Carbon::SUNDAY);

        $block = (new ThaiAstrologyService)->formatTransitOutlookBlock($sunday, null, null, $now);

        // คนวันอาทิตย์: มิตร = พฤหัสบดี, อังคาร · ศัตรู = เสาร์, ราหู
        $this->assertStringContainsString('พฤหัสบดี (ดาวมิตร):', $this->timelineRow($block, 'พฤหัสบดี'));
        $this->assertStringContainsString('อังคาร (ดาวมิตร):', $this->timelineRow($block, 'อังคาร'));
        $this->assertStringContainsString('เสาร์ (ดาวศัตรู):', $this->timelineRow($block, 'เสาร์'));
        $this->assertStringContainsString('ราหู (ดาวศัตรู):', $this->timelineRow($block, 'ราหู'));
        $this->assertStringNotContainsString('(ดาว', $this->timelineRow($block, 'อาทิตย์'), 'ดาวเจ้าเรือนเองไม่ใช่มิตร/ศัตรู');
    }

    public function test_wednesday_night_birth_uses_rahu_as_the_ruler(): void
    {
        $now = $this->bangkok(2026, 9, 11);
        $wednesday = $this->firstWeekday(Carbon::WEDNESDAY);

        $block = (new ThaiAstrologyService)->formatTransitOutlookBlock($wednesday.' 20:00', null, null, $now);

        // พุธกลางคืน = ราหู: มิตร = เสาร์ · ศัตรู = อาทิตย์ จันทร์ พุธ พฤหัสบดี (อนุมานจาก CHAOCHANA)
        $this->assertStringContainsString('เสาร์ (ดาวมิตร):', $this->timelineRow($block, 'เสาร์'));
        $this->assertStringContainsString('อาทิตย์ (ดาวศัตรู):', $this->timelineRow($block, 'อาทิตย์'));
        $this->assertStringContainsString('พฤหัสบดี (ดาวศัตรู):', $this->timelineRow($block, 'พฤหัสบดี'));
    }

    // ─────────────────────────────────────────────────────────────
    // จุดตรวจ + ขอบ
    // ─────────────────────────────────────────────────────────────

    public function test_checkpoints_are_normalised_and_never_overflow_the_month(): void
    {
        $points = (new ThaiAstrologyService)->transitCheckpoints(null, $this->bangkok(2027, 1, 31), [3, 1, 1, -2]);

        $this->assertSame([0, 1, 3], array_column($points, 'months'), '"ตอนนี้" ใส่ให้เอง · เรียง · ไม่ซ้ำ · ตัดค่าติดลบ');
        $this->assertSame('2027-02-28', $points[1]['date']->format('Y-m-d'), '31 ม.ค. + 1 เดือน ต้องไม่ล้นไป มี.ค.');
        $this->assertSame('12:00', $points[0]['date']->format('H:i'), 'คำนวณเที่ยงวันเหมือน formatTransitBlock');

        foreach ($points as $p) {
            foreach ($p['positions'] as $pos) {
                $this->assertNull($pos['house'], 'ไม่ส่งฐานนับภพ = ไม่มีภพ');
            }
        }
    }

    public function test_unreadable_birth_date_returns_nothing(): void
    {
        $this->assertSame('', (new ThaiAstrologyService)->formatTransitOutlookBlock('ไม่ใช่วันที่', null, null, $this->bangkok(2026, 9, 11)));
    }

    // ─────────────────────────────────────────────────────────────
    // 3. ต่อสายเข้าเลน 39 จริง
    // ─────────────────────────────────────────────────────────────

    public function test_conversation_transit_block_is_real_and_follows_today(): void
    {
        $conv = $this->conversation();

        Carbon::setTestNow($this->bangkok(2026, 9, 11));
        $sep = $this->invoke($conv, 'getCurrentTransitDescription', ['1990-05-15', 'ขอดูพื้นดวงโดยรวม']);
        Carbon::setTestNow($this->bangkok(2027, 3, 11));
        $mar = $this->invoke($conv, 'getCurrentTransitDescription', ['1990-05-15', 'ขอดูพื้นดวงโดยรวม']);

        $this->assertNotSame($sep, $mar);

        // ของเดิมที่แต่งขึ้นต้องไม่หลุดกลับมา
        foreach ([$sep, $mar] as $text) {
            $this->assertStringNotContainsString('คำนวณจากหลักเจ้าชนะ', $text);
            $this->assertStringNotContainsString('ดาวพลูโต', $text);
            $this->assertStringNotContainsString('ช่วงฤกษ์ดีที่สุด', $text);
        }

        $expected = (new ThaiAstrologyService)->formatTransitOutlookBlock('1990-05-15', null, null, $this->bangkok(2026, 9, 11));
        $this->assertStringContainsString(trim($expected), $sep);
    }

    public function test_conversation_transit_block_is_empty_without_a_birth_date(): void
    {
        $this->assertSame('', $this->invoke($this->conversation(), 'getCurrentTransitDescription', [null, 'ถามเรื่องงาน']));
        $this->assertSame('', $this->invoke($this->conversation(), 'getCurrentTransitDescription', ['', 'ถามเรื่องงาน']));
    }

    /**
     * หัวใจของบั๊ก — พรอมต์ใบเดียวต้องไม่มีดาวจร 2 ชุดที่ขัดกัน
     *
     * เดินเส้นเดียวกับ processPaymentConfirmed: buildPerQuestionDeepPrompt() สร้างพรอมต์จาก template
     * แล้ว FortuneAIService::buildPrompt() ต่อผังดวง (ซึ่งมีดาวจรวันนี้ในตัว) ท้ายพรอมต์
     * ครอบ 3 ทาง: ไม่รู้เวลา · รู้เวลาจาก DB ("Y-m-d H:i") · ลูกค้าพิมพ์เวลา+จังหวัดในคำถามเอง
     */
    public function test_transit_block_matches_the_chart_block_in_the_same_prompt(): void
    {
        Carbon::setTestNow($this->bangkok(2026, 9, 11));

        $cases = [
            'ไม่รู้เวลาเกิด' => ['1990-05-15', 'ขอดูพื้นดวงโดยรวมของเจ้าชะตา'],
            'รู้เวลาจาก DB' => ['1990-05-15 08:30', 'ขอดูพื้นดวงโดยรวมของเจ้าชะตา'],
            'พิมพ์เวลา+จังหวัดเอง' => ['1990-05-15', 'หนูเกิดเวลา 08:30 น. ที่เชียงใหม่ อยากรู้เรื่องงานค่ะ'],
        ];

        foreach ($cases as $label => [$birthDate, $question]) {
            // ทรงเดียวกับ template ของแอดมินบน prod (ตรวจ 2026-09-11): {transit_info} อยู่ 2 ที่
            //   (ในประโยคคำสั่ง + จุดวาง) และไม่มี {birth_date_section} ⇒ buildPrompt() ต่อผังท้ายอีก 1
            $conv = $this->conversation(
                "5. ผูกข้อมูลเฉพาะของลูกค้า: {planet_positions}, {transit_info}\n"
                ."{planet_positions}\n{transit_info}\nคำถาม: {question}"
            );
            $perQuestion = $this->invoke($conv, 'buildPerQuestionDeepPrompt', [
                ['name' => 'ทดสอบ', 'gender' => 'female'], $question, 1, 1, $birthDate, [], null,
            ]);

            $ai = (new ReflectionClass(FortuneAIService::class))->newInstanceWithoutConstructor();
            $final = $this->invoke($ai, 'buildPrompt', [[$question], ['name' => 'ทดสอบ'], null, $perQuestion, $birthDate]);

            $sections = $this->todayTransitSections($final);
            // (2026-09-12) {transit_info} ในประโยคคำสั่งกลายเป็นป้ายชี้ — ตารางเต็มก้อนเดียวที่บรรทัดวาง
            //   เดิม 3 ก้อน (ในประโยค + บรรทัดวาง + ผังท้าย) ⇒ ตาราง ~40 บรรทัดซ้ำ และแทรกกลางกฎ DNA
            $this->assertCount(2, $sections, "[{$label}] {transit_info} ก้อนเดียว + ผังที่ต่อท้าย 1");
            $this->assertStringContainsString(', ตารางดาวจรจริง 🔭', $final, "[{$label}] ประโยคคำสั่งต้องได้ป้ายชี้ตารางดาวจร");
            $this->assertStringNotContainsString('{transit_info}', $final, "[{$label}] ห้ามเหลือ placeholder ดิบ");
            $this->assertCount(1, array_unique($sections), "[{$label}] ดาวจรทุกบล็อกในพรอมต์ใบเดียวต้องตรงกันทุกตัวอักษร");
            $this->assertStringContainsString('🗓️ ดาวจรล่วงหน้า', $final, "[{$label}] ต้องมีตารางล่วงหน้า 1/3/6/12 เดือน");
            $this->assertStringNotContainsString('คำนวณจากหลักเจ้าชนะ', $final);

            if ($label === 'พิมพ์เวลา+จังหวัดเอง') {
                $this->assertStringContainsString('(นับจากลัคนา)', $sections[0], 'เวลาที่พิมพ์ในคำถามต้องถูกใช้ผูกลัคนาทั้งสองบล็อก');
            }
        }
    }

    // ─────────────────────────────────────────────────────────────
    // helpers
    // ─────────────────────────────────────────────────────────────

    private function bangkok(int $y, int $m, int $d): Carbon
    {
        return Carbon::create($y, $m, $d, 10, 0, 0, 'Asia/Bangkok');
    }

    private function cellLabel(int $months): string
    {
        return $months === 0 ? 'ตอนนี้' : "อีก {$months} เดือน";
    }

    /** แถวของดาวหนึ่งดวงในตารางดาวจรล่วงหน้า (ไม่ใช่บรรทัดในบล็อกดาวจรวันนี้) */
    private function timelineRow(string $block, string $planetTh): string
    {
        foreach (explode("\n", $block) as $line) {
            if (! str_contains($line, ': ตอนนี้ ราศี')) {
                continue;
            }
            $head = (string) strstr($line, ':', true);
            $head = (string) preg_replace('/\s*\((?:ดาวมิตร|ดาวศัตรู)\)$/u', '', $head);
            $name = (string) preg_replace('/^\s*\S+\s+/u', '', $head); // ตัดสัญลักษณ์ดาวออก

            if ($name === $planetTh) {
                return $line;
            }
        }

        $this->fail("ไม่พบแถวดาว{$planetTh}ในตารางดาวจรล่วงหน้า:\n{$block}");
    }

    /** ทุกจุดตรวจ ทุกดาว ต้องมีเลขภพที่นับจาก $anchor ถูกต้อง */
    private function assertHousesCountedFrom(string $anchor, string $block, Carbon $now): void
    {
        $order = (array) config('thai_astrology_knowledge.zodiac_order');
        $eph = new PlanetEphemeris;

        foreach (self::CHECKPOINTS as $months) {
            $real = $eph->positions($now->copy()->addMonthsNoOverflow($months)->setTime(12, 0, 0));
            foreach (ThaiAstrologyService::TIMING_PLANETS as $key) {
                $house = ((array_search($real[$key]['sign'], $order, true) - array_search($anchor, $order, true) + 12) % 12) + 1;
                $this->assertStringContainsString(
                    $this->cellLabel($months).' ราศี'.$real[$key]['sign'].' ภพ'.$house,
                    $this->timelineRow($block, $real[$key]['th'])
                );
            }
        }
    }

    /** ราศีที่เป็นภพที่ 1 ของผังล่าสุด (อ่านจาก state ของ service หลังผูกดวง) */
    private function anchorOf(ThaiAstrologyService $astro): ?string
    {
        $prop = (new ReflectionClass($astro))->getProperty('lastLagna');

        return $prop->getValue($astro);
    }

    /** ไล่หาวันเกิด (ไม่รู้เวลา) ที่ฐานนับภพเป็นแบบที่ต้องการ — ไม่ฮาร์ดโค้ดวัน เพราะขึ้นกับจันทร์จริง */
    private function findBirthDayWithBasis(ThaiAstrologyService $astro, string $basis): string
    {
        for ($d = 1; $d <= 28; $d++) {
            $ymd = sprintf('1990-05-%02d', $d);
            if ($astro->resolveLagnaBasis($ymd, null) === $basis) {
                return $ymd;
            }
        }

        $this->fail("ไม่พบวันเกิดเดือน พ.ค. 2533 ที่ฐานนับภพเป็น {$basis}");
    }

    private function firstWeekday(int $dayOfWeek): string
    {
        $d = Carbon::create(1990, 5, 13, 12, 0, 0, 'Asia/Bangkok');
        while ($d->dayOfWeek !== $dayOfWeek) {
            $d->addDay();
        }

        return $d->format('Y-m-d');
    }

    /** บล็อก "🔭 ดาวจรวันนี้" ทุกก้อนในข้อความ — หัวบล็อก + บรรทัดย่อหน้าที่ตามมา */
    private function todayTransitSections(string $text): array
    {
        $sections = [];
        $lines = explode("\n", $text);

        foreach ($lines as $i => $line) {
            if (! str_starts_with($line, '🔭 ดาวจรวันนี้')) {
                continue;
            }
            $block = [$line];
            for ($j = $i + 1; $j < count($lines) && preg_match('/^\s+\S/u', $lines[$j]); $j++) {
                $block[] = $lines[$j];
            }
            $sections[] = implode("\n", $block);
        }

        return $sections;
    }

    /** FortuneConversationService แบบไม่ผ่าน constructor (constructor อ่าน settings จาก DB) */
    private function conversation(?string $deepTemplate = null): FortuneConversationService
    {
        $ref = new ReflectionClass(FortuneConversationService::class);
        $svc = $ref->newInstanceWithoutConstructor();

        if ($deepTemplate !== null) {
            $settings = new FortuneTellingSetting;
            $settings->deep_prompt_template = $deepTemplate;
            $ref->getProperty('settings')->setValue($svc, $settings);
        }

        return $svc;
    }

    private function invoke(object $target, string $method, array $args): mixed
    {
        return (new ReflectionMethod($target, $method))->invokeArgs($target, $args);
    }
}
