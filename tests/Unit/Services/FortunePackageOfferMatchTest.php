<?php

namespace Tests\Unit\Services;

use App\Services\Fortune\FortunePackageOffer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * 🛒 ล็อกด่าน "คำตอบนี้คือการกดปุ่มเลือกแพคเกจ หรือแค่ประโยคที่มีคำว่าคุณไสย"
 *
 * 🚨 ทำไมต้องมีเทสต์นี้ (2026-09-06 · Bunphon Saenchawee r12479 FTU-260906-H1143):
 *   ลูกค้ากดปุ่ม "🪬 ดูคุณไสย 99฿" จากกล่องกระตุ้น ช้าไป 3 ชม. 12 นาที
 *   ตอนนั้น reading ถูกปิดเองไปแล้ว (flow_exit 30 นาที) ⇒ ไม่มีด่านไหนรู้จักคำนี้
 *   ⇒ หล่นเข้าเลนแชทฟรี → บอทตอบ "ไม่ใช่ทางของแม่หมอค่ะ" = **ปฏิเสธคนที่จะจ่าย 99฿**
 *
 * 🎯 เทสต์นี้คุมความเสี่ยงของ *ทางแก้* ไม่ใช่ของบั๊กเดิม:
 *   ธง offer มีอายุ 48 ชม. — ถ้า matchTier เทียบแบบ "สับสตริง" เมื่อไหร่
 *   ประโยคอย่าง "คุณไสยมีจริงไหมคะ" จะกลายเป็นคำสั่งเปิดบิล 99฿ ให้คนที่แค่มาถาม
 *   ⇒ ต้องเทียบ **ทั้งสตริง** เท่านั้น ([[rule_hardship_is_not_a_buy_signal]] ·
 *      [[rule_gate_must_not_swallow_customer_text]])
 *
 * ⚠️ ใช้ PHPUnit\Framework\TestCase ตรง ๆ (ไม่แตะ DB/Cache) — matchTier เป็นฟังก์ชันบริสุทธิ์
 */
class FortunePackageOfferMatchTest extends TestCase
{
    /**
     * ป้ายปุ่มจริงที่เราส่งออกไป ต้องอ่านออกครบทุกทรง
     *
     * 2 ทรงที่ลูกค้าส่งกลับมาได้: `text/payload` และ *ตัวหนังสือบนปุ่ม*
     * ([[rule_fb_quickreply_label_arrives_as_text]])
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function realButtonLabels(): array
    {
        return [
            'nudge text คุณไสย' => ['ดูคุณไสย', 'celtic_blackmagic'],
            'ป้ายเต็มมีอีโมจิ+ราคา' => ['🪬 ดูคุณไสย 99฿', 'celtic_blackmagic'],
            'ป้ายไม่มีอีโมจิ' => ['ดูคุณไสย 99฿', 'celtic_blackmagic'],
            'สะกดมีทัณฑฆาต' => ['ดูคุณไสย์', 'celtic_blackmagic'],
            'พิมพ์สั้น คุณไสย' => ['คุณไสย', 'celtic_blackmagic'],
            'พิมพ์สั้น มนต์ดำ' => ['มนต์ดำ', 'celtic_blackmagic'],
            'payload code คุณไสย' => ['TIER_CELTIC_BLACKMAGIC', 'celtic_blackmagic'],
            'nudge text celtic' => ['celtic', 'celtic'],
            'ป้ายปุ่ม 99 (20 ตัวพอดี)' => ['ดู vip ส่วนตัว 99บาท', 'celtic'],
            'เซลติกภาษาไทย' => ['เซลติก', 'celtic'],
            'payload code celtic' => ['TIER_CELTIC_99', 'celtic'],
        ];
    }

    /**
     * ประโยคที่ลูกค้าพิมพ์เอง — ห้ามอ่านเป็นการกดปุ่มเด็ดขาด
     *
     * @return array<string, array{0: string}>
     */
    public static function customerSentences(): array
    {
        return [
            'คำถามว่ามีจริงไหม' => ['คุณไสยมีจริงไหมคะ'],
            'คำถามเรื่องทางแก้' => ['มนต์ดำแก้ยังไงคะ'],
            'เล่าอาการของตัวเอง' => ['หนูว่าหนูโดนของค่ะ'],
            'ประโยคยาวขอให้ดูให้' => ['แม่หมอคะ หนูโดนคุณไสยหรือเปล่า ช่วยดูให้หน่อย'],
            'ยาวเกินเพดานป้ายปุ่ม' => ['อยากรู้เรื่องคุณไสยที่คนเขาพูดกันว่าทำได้จริงไหม'],
            // "ดูดวง" = ขอดูเมนู ไม่ใช่ "ฉันเลือกแพคเกจ 39"
            'ขอดูเมนู' => ['ดูดวง'],
            'ขอดูเมนูแบบเจาะจง' => ['ดูดวงเชิงลึก'],
            'ทักทาย' => ['สวัสดีค่ะ'],
            // ตัวเลขล้วนมีบล็อก tier-direct เดิมรับอยู่แล้ว — ด่านนี้ต้องไม่ไปยุ่ง
            'ตัวเลข 39' => ['39'],
            'ตัวเลข 99' => ['99'],
            'ข้อความว่าง' => [''],
        ];
    }

    #[DataProvider('realButtonLabels')]
    public function test_ปุ่มที่เราส่งเองต้องอ่านออกเป็นแพคเกจ(string $input, string $expected): void
    {
        $this->assertSame(
            $expected,
            FortunePackageOffer::matchTier($input),
            "ป้ายปุ่ม \"{$input}\" ต้องอ่านได้เป็น {$expected} — ไม่งั้นลูกค้ากดแล้วบอทไม่รู้ว่ากำลังขายอยู่"
        );
    }

    #[DataProvider('customerSentences')]
    public function test_ประโยคลูกค้าห้ามถูกอ่านเป็นการกดปุ่ม(string $input): void
    {
        $this->assertNull(
            FortunePackageOffer::matchTier($input),
            "\"{$input}\" ไม่ใช่การกดปุ่ม — ถ้าจับติดจะกลายเป็นยัดบิล 99฿ ใส่คนที่แค่มาถาม"
        );
    }

    /**
     * ลำดับต้องเป็น คุณไสย ก่อน celtic เสมอ
     *
     * ป้าย "ดูคุณไสย 99฿" มีทั้งคำว่าคุณไสยและเลข 99 — ถ้าลำดับสลับ
     * ลูกค้าที่จ่ายเพื่อดูเรื่องของ จะได้ Celtic ทั่วไปแทน (เคส 2026-09-02 Maliwan r12052)
     */
    public function test_คุณไสยต้องชนะ_celtic_เมื่อป้ายมีทั้งสองอย่าง(): void
    {
        $this->assertSame('celtic_blackmagic', FortunePackageOffer::matchTier('🪬 ดูคุณไสย 99฿'));
    }
}
