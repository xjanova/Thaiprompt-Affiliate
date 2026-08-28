<?php

namespace Tests\Unit\Services;

use App\Services\Fortune\FortuneBubbleSplitter;
use PHPUnit\Framework\TestCase;

/**
 * 💬 (2026-08-28, owner) "แยกกล่องบับเบิ้ลตอบ อย่าตอบยาว ๆ ทำเป็นหลายกล่องเพื่อให้เหมือนคนตอบ"
 *
 * เทสต์นี้ล็อกข้อที่ **พังแล้วเงียบ** ทั้งหมด — คนกลุ่มนี้จ่ายเงินมาแล้ว:
 *   1. เนื้อหาหาย — ผ่าแล้วตัวอักษรตกหล่น ลูกค้าได้คำทำนายไม่ครบ ไม่มี error ให้เห็น
 *   2. ตัดกลางอักขระไทย — สระ/วรรณยุกต์เป็น Mark (\p{M}) ตัดผิดที่แล้วได้ "◌ุธ" ขึ้นต้นกล่อง
 *   3. กล่องว่าง / กล่องเศษ — บับเบิ้ล "ค่ะ" ลอยเดี่ยวดูเหมือนบอทค้าง
 *
 * PHPUnit\TestCase ล้วน ไม่ boot Laravel ⇒ ไม่ต้องมี MySQL (rule_phpunit_needs_mysql_no_sqlite)
 */
class FortuneBubbleSplitterTest extends TestCase
{
    private FortuneBubbleSplitter $splitter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->splitter = new FortuneBubbleSplitter;
    }

    /** คำทำนายจริงแบบย่อหน้า — รูปแบบที่ AI คายออกมาเป็นปกติ */
    private function longThaiReading(): string
    {
        return implode("\n\n", [
            'เจ้าชะตาคะ ไพ่ใบแรกที่แม่หมอเปิดได้คือ The Star ซึ่งบอกชัดว่าช่วงนี้เป็นจังหวะฟื้นตัวหลังจากที่เหนื่อยมานาน '
                .'สิ่งที่เคยรู้สึกว่ามืดมนกำลังจะคลี่คลาย แม่หมอเห็นแสงสว่างรออยู่ข้างหน้าแล้วนะคะ',
            'เรื่องการงาน ไพ่ Three of Pentacles ชี้ว่ามีคนพร้อมสนับสนุนเจ้าชะตาอยู่ แต่ต้องเป็นฝ่ายเอ่ยปากขอก่อน '
                .'อย่ารอให้เขาเดามาเอง เพราะเขาก็ไม่กล้าเสนอตัวเหมือนกันค่ะ ช่วงนี้เหมาะกับการรวมทีมมากกว่าลุยเดี่ยว',
            'เรื่องเงินทอง แม่หมอฟันธงว่าจะมีรายรับก้อนหนึ่งเข้ามาภายในเดือนนี้ แต่มันจะมาพร้อมรายจ่ายที่ไม่ได้วางแผนไว้ '
                .'ให้กันไว้อย่างน้อยหนึ่งในสามก่อนใช้ อย่าเพิ่งรีบซื้อของชิ้นใหญ่นะคะ',
            'เรื่องความรัก ไพ่ Two of Cups กลับหัว บอกว่ามีความเข้าใจผิดค้างอยู่ระหว่างเจ้าชะตากับคนคนหนึ่ง '
                .'เรื่องนี้แก้ได้ด้วยการพูดตรง ๆ ครั้งเดียว ไม่ต้องอ้อมค้อม แม่หมอเห็นว่าอีกฝ่ายรออยู่แล้วค่ะ',
            'สรุปรวมคือช่วงนี้ดวงกำลังขึ้น แต่ต้องเป็นฝ่ายเริ่มก่อนทุกเรื่อง ไม่ว่าจะงาน เงิน หรือใจ '
                .'แม่หมอขอให้เจ้าชะตาผ่านช่วงนี้ไปได้ด้วยดีนะคะ',
        ]);
    }

    public function test_ข้อความสั้นไม่ต้องผ่า(): void
    {
        $short = 'เจ้าชะตาคะ วันนี้ดวงกำลังขึ้น ลุยได้เลยค่ะ ✨';

        $this->assertSame([$short], $this->splitter->split($short));
    }

    public function test_ข้อความว่างคืนอาเรย์ว่าง(): void
    {
        $this->assertSame([], $this->splitter->split('   '));
    }

    public function test_คำทำนายยาวถูกผ่าเป็นหลายกล่อง(): void
    {
        $bubbles = $this->splitter->split($this->longThaiReading());

        $this->assertGreaterThan(1, count($bubbles), 'คำทำนายยาวต้องถูกผ่ามากกว่า 1 กล่อง');
        $this->assertLessThanOrEqual(FortuneBubbleSplitter::MAX_BUBBLES, count($bubbles));
    }

    /**
     * 🚨 ข้อที่สำคัญที่สุด — ผ่าแล้วห้ามมีตัวอักษรหาย
     *
     * เทียบแบบ "ลอกช่องว่าง/ขึ้นบรรทัดออกให้หมดแล้วต้องเท่ากันเป๊ะ"
     * เพราะตัวผ่าได้รับอนุญาตให้ปรับเฉพาะตัวคั่น ไม่ใช่เนื้อความ
     */
    public function test_ผ่าแล้วเนื้อหาต้องครบไม่หายแม้แต่ตัวเดียว(): void
    {
        foreach ([$this->longThaiReading(), str_repeat('แม่หมอเห็นว่าเจ้าชะตากำลังจะได้ข่าวดีเรื่องงานค่ะ ', 40)] as $source) {
            $joined = implode('', $this->splitter->split($source));

            $this->assertSame(
                preg_replace('/\s+/u', '', $source),
                preg_replace('/\s+/u', '', $joined),
                'เนื้อหาหายหรือเพี้ยนหลังผ่า — ลูกค้าที่จ่ายเงินจะได้คำทำนายไม่ครบ'
            );
        }
    }

    /**
     * 🇹🇭 ห้ามตัดกลางอักขระไทย
     *
     * ลายเซ็นของการตัดผิด: กล่องขึ้นต้นด้วยสระ/วรรณยุกต์ (\p{M}) ที่หลุดจากพยัญชนะ
     */
    public function test_ห้ามมีกล่องที่ขึ้นต้นด้วยสระหรือวรรณยุกต์ลอย(): void
    {
        $bubbles = $this->splitter->split($this->longThaiReading());

        foreach ($bubbles as $i => $bubble) {
            $this->assertSame(0, preg_match('/^\p{M}/u', $bubble), "กล่องที่ {$i} ขึ้นต้นด้วย Mark ลอย = ตัดกลางตัวอักษร");
            $this->assertNotSame('', trim($bubble), "กล่องที่ {$i} ว่างเปล่า");
        }
    }

    public function test_ไม่มีกล่องเศษสั้นจิ๋วลอยเดี่ยว(): void
    {
        $bubbles = $this->splitter->split($this->longThaiReading());

        // กล่องเดียวยกเว้นได้ (ไม่มีอะไรให้ยุบรวม) — ตั้งแต่ 2 กล่องขึ้นไปต้องไม่มีเศษ
        if (count($bubbles) < 2) {
            $this->markTestSkipped('ผ่าได้กล่องเดียว — ไม่มีเศษให้ตรวจ');
        }

        foreach ($bubbles as $i => $bubble) {
            $this->assertGreaterThanOrEqual(
                FortuneBubbleSplitter::MIN_CHARS,
                mb_strlen($bubble),
                "กล่องที่ {$i} สั้นเกินไป — ควรถูกยุบรวมกับกล่องก่อนหน้า"
            );
        }
    }

    public function test_เพดานจำนวนกล่องถูกเคารพและเนื้อหายังครบ(): void
    {
        $source = $this->longThaiReading();

        foreach ([1, 2, 3] as $max) {
            $bubbles = $this->splitter->split($source, $max);

            $this->assertLessThanOrEqual($max, count($bubbles), "เกินเพดาน {$max} กล่อง");
            $this->assertSame(
                preg_replace('/\s+/u', '', $source),
                preg_replace('/\s+/u', '', implode('', $bubbles)),
                "เนื้อหาหายตอนจำกัดที่ {$max} กล่อง"
            );
        }
    }

    /** ข้อความไม่มีบรรทัดว่างเลย (AI บางรอบคายมาเป็นพืดเดียว) ต้องยังผ่าได้ */
    public function test_ข้อความพืดเดียวไม่มีย่อหน้ายังผ่าได้(): void
    {
        $source = str_repeat('แม่หมอเห็นว่าเจ้าชะตาจะได้ข่าวดีเรื่องงานในเดือนนี้ค่ะ ให้เตรียมตัวไว้ให้พร้อมนะคะ ', 12);

        $bubbles = $this->splitter->split($source);

        $this->assertGreaterThan(1, count($bubbles), 'พืดเดียวก็ต้องผ่าได้ที่คำลงท้ายประโยค');
        $this->assertSame(
            preg_replace('/\s+/u', '', $source),
            preg_replace('/\s+/u', '', implode('', $bubbles))
        );
    }
}
