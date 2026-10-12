<?php

namespace Tests\Unit\Services;

use App\Models\FortuneTellingSetting;
use App\Services\Fortune\LifeGraph;
use App\Services\Fortune\LifeGraphImage;
use App\Services\Fortune\LifeGraphService;
use App\Services\Fortune\LifeGraphTables;
use App\Services\Fortune\LifeGraphTexts;
use App\Services\Fortune\ThaiAstrologyService;
use App\Services\FortuneConversationService;
use Carbon\Carbon;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 📈 กราฟชีวิต (พอร์ตจาก GenLotto) — ไม่แตะ DB
 *
 * ค่าอ้างอิงคำนวณด้วย GenLotto (site/app/Astro/LifeGraph.php, ณ 2026-10-12 12:00) — พอร์ตต้องได้ผลเดียวกันทุกปี
 * ถ้าแก้ตำรา/น้ำหนักที่ GenLotto แล้วพอร์ตตาม ให้คำนวณค่าอ้างอิงใหม่จาก GenLotto
 */
class LifeGraphTest extends TestCase
{
    private const NOW = '2026-10-12 12:00:00';

    /** [วันเกิด, ชั่วโมงเกิด, จังหวัด, md5(json คะแนนอายุ 0–100), ช่วงดาวเสวย, พีค, ต่ำ, บรรทัดการ์ด 2 บรรทัดแรก] */
    private const GENLOTTO = [
        ['1990-05-16', 21.5, 'กรุงเทพมหานคร', 'a15709029a4f0f3bb02655b76358d004', '8:0-12,6:12-33,1:33-39,2:39-54,3:54-62,4:62-79,7:79-89,5:89-108', [12, 21, 27], [33, 5, 0],
            ['อาทิตย์เสวยอายุ (ช่วงร้าย): ร้อนใจ มีเกณฑ์โยกย้ายที่อยู่ ระวังเรื่องกับผู้ใหญ่และคดีความ ดูแลสุขภาพและการเงินให้ดี', 'พฤหัสบดีแทรกถึง 16 ก.ค. 2570: ได้ลาภจากผู้ใหญ่หรือผู้ทรงศีล']],
        ['1985-01-06', 8.25, 'กรุงเทพมหานคร', 'ffd5b90d4c8c0f6bd2683ec4a577c0b1', '1:0-6,2:6-21,3:21-29,4:29-46,7:46-56,5:56-75,8:75-87,6:87-108', [57, 62, 71], [80, 75, 0],
            ['พุธเสวยอายุ (ช่วงดี): มีลาภและทรัพย์ ได้คู่ครอง ได้เป็นใหญ่ มียศ ชื่อเสียง และบริวาร', 'อาทิตย์แทรกถึง 22 พ.ค. 2570: ระวังต้องโทษ มีอุปสรรค เสียทรัพย์']],
        ['1985-01-06', null, 'กรุงเทพมหานคร', '36b01199a4a7a02a8951fdebd220ba42', '1:0-6,2:6-21,3:21-29,4:29-46,7:46-56,5:56-75,8:75-87,6:87-108', [57, 62, 71], [75, 80, 25],
            ['พุธเสวยอายุ (ช่วงดี): มีลาภและทรัพย์ ได้คู่ครอง ได้เป็นใหญ่ มียศ ชื่อเสียง และบริวาร', 'อาทิตย์แทรกถึง 22 พ.ค. 2570: ระวังต้องโทษ มีอุปสรรค เสียทรัพย์']],
        ['1975-11-23', 14 + 10 / 60, 'เชียงใหม่', '53d80692d65c555c588298c968574de0', '1:0-6,2:6-21,3:21-29,4:29-46,7:46-56,5:56-75,8:75-87,6:87-108', [57, 71, 65], [46, 51, 75],
            ['เสาร์เสวยอายุ (ช่วงร้าย): เสียทรัพย์ ระวังเจ็บป่วย อุบัติเหตุ ศัตรูเก่า และการสูญเสียของรัก มีเรื่องกับคนใกล้ตัว', 'ศุกร์แทรกถึง 18 ส.ค. 2570: ช่วงแรกเสียของ มีเรื่องเพราะผู้หญิง ภายหลังมิตรให้ลาภ']],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(self::NOW);
        LifeGraphService::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        LifeGraphService::flush();
        parent::tearDown();
    }

    public function test_port_matches_genlotto_for_every_year(): void
    {
        foreach (self::GENLOTTO as [$date, $hour, $province, $md5, $periods, $peaks, $lows, $card]) {
            $g = (new LifeGraph)->compute(['name' => 'x', 'birth_date' => $date, 'birth_hour' => $hour, 'birth_province' => $province], new \DateTimeImmutable(self::NOW));
            $label = "{$date} ".var_export($hour, true)." {$province}";
            $this->assertNotNull($g, $label);
            $this->assertSame($md5, md5(json_encode(array_column($g['years'], 'score'))), "คะแนนรายปีต้องเท่า GenLotto: {$label}");
            $this->assertSame($periods, implode(',', array_map(fn ($p) => $p['planet'].':'.$p['from_age'].'-'.$p['to_age'], $g['periods'])), $label);
            $this->assertSame($peaks, array_column($g['peaks'], 'age'), $label);
            $this->assertSame($lows, array_column($g['lows'], 'age'), $label);
            $this->assertSame($card, array_slice($g['reading']['card'], 0, 2), $label);
        }
    }

    public function test_texts_are_sourced_and_conflicts_left_out(): void
    {
        $this->assertCount(64, LifeGraphTexts::SUB);
        $nulls = array_keys(array_filter(LifeGraphTexts::SUB, fn ($v) => $v === null));
        sort($nulls);
        $this->assertSame(['5-2', '7-4', '7-8', '8-1', '8-5'], $nulls, 'คู่ดาวแทรกที่ตำราขัดกันต้องไม่มีคำทาย');
        foreach (array_filter(LifeGraphTexts::SUB) + LifeGraphTexts::PERIOD as $k => $t) {
            $this->assertGreaterThanOrEqual(2, count(array_unique($t['src'])), "{$k} ต้องมี ≥ 2 แหล่ง");
            $this->assertSame([], array_diff($t['src'], array_keys(LifeGraphTexts::SOURCES)), "{$k} แหล่งต้องอยู่ใน SOURCES");
            $this->assertDoesNotMatchRegularExpression('/ตาย|ผิวดำ|ผิวแดง/u', $t['text'], "{$k} ห้ามคำทำนายความตาย/สีผิว");
        }
    }

    public function test_period_line_in_natal_block_uses_the_same_texts(): void
    {
        // เกิดวันอาทิตย์ อายุ 3 = อาทิตย์เสวย — config period_tone เดิมว่า "ช่วงเด่น มีอำนาจ" ขัดตำรา (ทุกฉบับว่าร้าย)
        $period = (new ThaiAstrologyService)->getPlanetaryPeriod(0, 3, 9.0);
        $this->assertSame('อาทิตย์', $period['planet']);
        $this->assertSame('ตำราว่าเป็นช่วงร้าย: '.LifeGraphTexts::PERIOD[1]['text'], $period['tone']);
        $this->assertSame(['อาทิตย์' => 1, 'พฤหัส' => 5, 'ดาวพฤหัสบดี' => 5, 'ราหู' => 8, 'xx' => null], [
            'อาทิตย์' => LifeGraphTables::planetNum('อาทิตย์'), 'พฤหัส' => LifeGraphTables::planetNum('พฤหัส'),
            'ดาวพฤหัสบดี' => LifeGraphTables::planetNum('ดาวพฤหัสบดี'), 'ราหู' => LifeGraphTables::planetNum('ราหู'), 'xx' => LifeGraphTables::planetNum('xx'),
        ]);
    }

    public function test_prompt_block_is_short_and_only_states_ahead(): void
    {
        $block = LifeGraphService::promptBlock(LifeGraphService::forInputs('1985-01-06', 8.25, 'กรุงเทพมหานคร', 'สมใจ'), 'ทดสอบ');
        $lines = explode("\n", $block);
        $this->assertLessThanOrEqual(6, count($lines), 'อย่าบวม: ≤ 6 บรรทัด');
        $this->assertLessThan(900, mb_strlen($block));
        $this->assertStringContainsString('ตอนนี้อายุ 41: พุธเสวยอายุ 29-46 ปี = ตำราว่าช่วงดี', $block);
        $this->assertStringContainsString('ดาวแทรกตอนนี้ อาทิตย์ ถึงราว 22 พ.ค. 2570 = ตำราว่าร้าย', $block);
        $this->assertStringContainsString('ดาวแทรกถัดไป จันทร์ ตั้งแต่ราว 22 พ.ค. 2570 = ตำราว่าดี', $block);
        $this->assertStringContainsString('พุธ (ภูมิศรีเดิม) เป็นกาลีจร — การเงินฝืด', $block);
        $this->assertStringContainsString('ช่วงรุ่งข้างหน้า อายุ 57, 62, 71 · ช่วงควรระวังข้างหน้า อายุ 75, 80 (จากคะแนนกราฟที่ระบบออกแบบ ไม่ใช่ตำรา)', $block, 'เฉพาะที่ยังมาไม่ถึง เรียงตามอายุ + บอกว่าไม่ใช่ตำรา');
        $this->assertStringNotContainsString(LifeGraphTexts::PERIOD[4]['text'], $block, 'คำทายดาวเสวยเต็มอยู่ในบรรทัด ⏳ ของผังแล้ว ไม่ซ้ำ');
        $this->assertStringNotContainsString('{', $block);

        // เกิดพุธกลางคืน: ไม่มีทักษาจร · คู่อาทิตย์เสวย-พฤหัสแทรกมีคำทาย
        $wed = LifeGraphService::promptBlock(LifeGraphService::forInputs('1990-05-16', 21.5, 'กรุงเทพมหานคร'), 'ทดสอบ');
        $this->assertStringNotContainsString('ทักษาจร', $wed);
        $this->assertStringContainsString('อาทิตย์ในดวงเดิมอยู่ภพอริ — ตำราว่าผลร้ายของช่วงนี้หนักขึ้น', $wed);
        $this->assertSame('', LifeGraphService::promptBlock(LifeGraphService::forInputs('ไม่ใช่วันที่', null, null), 'x'));
    }

    public function test_deep39_prompt_carries_the_block_once_on_the_first_question_only(): void
    {
        $ref = new ReflectionClass(FortuneConversationService::class);
        $svc = $ref->newInstanceWithoutConstructor();
        $settings = new FortuneTellingSetting;
        $settings->deep_prompt_template = '';
        $ref->getProperty('settings')->setValue($svc, $settings);
        $build = new ReflectionMethod($svc, 'buildPerQuestionDeepPrompt');
        $inputs = ['hour' => 8.25, 'province' => 'กรุงเทพมหานคร'];

        $q1 = $build->invokeArgs($svc, [['name' => 'สมใจ', 'gender' => 'female'], 'ขอดูพื้นดวงโดยรวม', 1, 1, '1985-01-06', [], null, 'กรุงเทพมหานคร', $inputs]);
        $this->assertSame(1, substr_count($q1, 'กราฟชีวิตตามตำรามหาทักษา'));
        $this->assertStringContainsString('ดาวแทรกตอนนี้ อาทิตย์ ถึงราว 22 พ.ค. 2570', $q1);

        $q2 = $build->invokeArgs($svc, [['name' => 'สมใจ', 'gender' => 'female'], 'การงานปีหน้า', 2, 2, '1985-01-06', [], null, 'กรุงเทพมหานคร', $inputs]);
        $this->assertSame(0, substr_count($q2, 'กราฟชีวิตตามตำรามหาทักษา'), 'ข้อถัดไปไม่ใส่ซ้ำ');
    }

    public function test_image_is_a_1080x1350_jpeg(): void
    {
        $g = LifeGraphService::forInputs('1985-01-06', 8.25, 'กรุงเทพมหานคร', 'สมใจ <ทดสอบ> 💕');
        $jpg = (new LifeGraphImage)->render($g);
        $info = getimagesizefromstring($jpg);
        $this->assertSame([1080, 1350, 'image/jpeg'], [$info[0], $info[1], $info['mime']]);
        $this->assertLessThan(950 * 1024, strlen($jpg), 'ขนาดพอสำหรับ LINE/FB');
    }

    public function test_memory_guard_parses_limits_and_raises_a_low_one(): void
    {
        $this->assertSame([-1, 134217728, 1073741824, 524288, 1000], [
            LifeGraphImage::bytes('-1'), LifeGraphImage::bytes('128M'), LifeGraphImage::bytes('1G'), LifeGraphImage::bytes('512K'), LifeGraphImage::bytes('1000'),
        ]);
        $old = ini_get('memory_limit');
        try {
            // ขีดต่ำจนเหลือไม่ถึง 64MB → ขยายให้เอง (คำสั่ง cron ใช้ CLI 128M)
            ini_set('memory_limit', (int) ceil((memory_get_usage(true) + 16 * 1048576) / 1048576).'M');
            $this->assertTrue(LifeGraphImage::ensureMemory(64));
            $this->assertGreaterThanOrEqual(256 * 1048576, LifeGraphImage::bytes((string) ini_get('memory_limit')));
        } finally {
            ini_set('memory_limit', $old);
        }
    }
}
