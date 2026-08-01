<?php

namespace Tests\Unit\Services;

use App\Services\Fortune\FacebookContentPolicy;
use PHPUnit\Framework\TestCase;

/**
 * ทดสอบกฎคอนเทนต์ FB — ห้ามอีโมจิ + แฮชแท็กไม่เกิน 3
 *
 * ฟังก์ชันบริสุทธิ์ล้วน ไม่แตะ DB/Cache จึง extend PHPUnit TestCase ตรง ๆ (เร็วกว่า boot Laravel)
 */
class FacebookContentPolicyTest extends TestCase
{
    /**
     * ลบอีโมจิได้ครบทุกตระกูลที่โพสจริงใช้
     *
     * @test
     */
    public function ลบอีโมจิได้ครบทุกแบบ(): void
    {
        $cases = [
            // อีโมจิประจำวันเกิดที่ฝังในโค้ดโพสดวงรายวัน
            '☀️ 【คนเกิดวันอาทิตย์】' => '【คนเกิดวันอาทิตย์】',
            '🌙 ดวงวันนี้' => 'ดวงวันนี้',
            '🔴🟢🟠🔵🟣 ครบสี' => 'ครบสี',
            // ป้ายของมงคล
            '🎨 สี: แดง | 🔢 เลข: 5 | 🧭 ทิศ: เหนือ' => 'สี: แดง | เลข: 5 | ทิศ: เหนือ',
            // ที่ AI ชอบใส่
            '✨ วันนี้ดีมาก 🙏 ขอให้โชคดี 💎' => 'วันนี้ดีมาก ขอให้โชคดี',
            '🃏 ไพ่วันนี้' => 'ไพ่วันนี้',
            '💬 คอมเมนต์เล่าให้ฟัง' => 'คอมเมนต์เล่าให้ฟัง',
            // อีโมจิผสม (ZWJ + skin tone) ต้องไม่เหลือเศษ
            '👨‍👩‍👧 ครอบครัว' => 'ครอบครัว',
            '👍🏽 ดี' => 'ดี',
            // keycap: เก็บ "เลขข้อ" ไว้ (ไม่ใช่อีโมจิแล้ว) แต่ # * ที่ลอยอยู่ต้องหาย
            '1️⃣ ข้อแรก' => '1 ข้อแรก',
            '2️⃣ ข้อสอง' => '2 ข้อสอง',
            '#️⃣ แท็ก' => 'แท็ก',
            '*️⃣ ดาว' => 'ดาว',
        ];

        foreach ($cases as $input => $expected) {
            $this->assertSame($expected, FacebookContentPolicy::clean($input), "input: {$input}");
        }
    }

    /**
     * 🚨 อักขระที่ "ห้ามโดนลบ" — เคยพลาดมาแล้วถ้าเหวี่ยงแหกว้างไป
     *
     * เส้นคั่น ━━━ กับวงเล็บ 【】 เป็นโครงสร้างของโพสดวงรายวัน
     * ถ้าหายไปโพสจะกลายเป็นก้อนเดียวอ่านไม่ออก
     *
     * @test
     */
    public function ต้องไม่ลบตัวอักษรไทยและอักขระโครงสร้าง(): void
    {
        foreach ([
            'สวัสดีค่ะ แม่หมอจันทราพยากรณ์',
            '━━━━━━━━━━━━━━━━',
            '【คนเกิดวันพฤหัสบดี】',
            'ราคา 39 บาท — คุ้มมาก… "จริงนะ"',
            '#ดูดวง #ดวงรายวัน',
            'A/B test 100% (ok)',
        ] as $text) {
            $this->assertSame($text, FacebookContentPolicy::clean($text), "ห้ามเปลี่ยน: {$text}");
        }
    }

    /**
     * เก็บย่อหน้าไว้ + ไม่ทิ้งช่องว่าง/บรรทัดว่างที่อีโมจิทิ้งไว้
     *
     * @test
     */
    public function จัดระเบียบช่องว่างหลังลบอีโมจิ(): void
    {
        $input = "🔮 หัวข้อ\n\n✨\n\nเนื้อหาย่อหน้าแรก\n\nเนื้อหาย่อหน้าสอง 🙏";

        $out = FacebookContentPolicy::clean($input);

        $this->assertSame("หัวข้อ\n\nเนื้อหาย่อหน้าแรก\n\nเนื้อหาย่อหน้าสอง", $out);
        // ย่อหน้าต้องยังอยู่ (ไม่ยุบเป็นก้อนเดียว)
        $this->assertStringContainsString("\n\n", $out);
        // ห้ามเหลือบรรทัดว่างซ้อน 3 ชั้น
        $this->assertDoesNotMatchRegularExpression("/\n{3,}/", $out);
    }

    /**
     * เพดานแฮชแท็ก 3 อัน — ทั้งแบบ array และแบบบรรทัดเดียว
     *
     * @test
     */
    public function ตัดแฮชแท็กเหลือสามอัน(): void
    {
        $this->assertSame(3, FacebookContentPolicy::MAX_HASHTAGS);

        $many = ['#ก', '#ข', '#ค', '#ง', '#จ', '#ฉ'];
        $this->assertSame(['#ก', '#ข', '#ค'], FacebookContentPolicy::capHashtags($many));

        // น้อยกว่าเพดาน = ไม่แตะ
        $this->assertSame(['#ก'], FacebookContentPolicy::capHashtags(['#ก']));
        $this->assertSame([], FacebookContentPolicy::capHashtags([]));

        // แบบบรรทัดเดียว (generateSmartHashtags คืน string)
        $this->assertSame(
            '#ดวงรายวัน #ดูดวง #โหราศาสตร์ไทย',
            FacebookContentPolicy::capHashtagLine('#ดวงรายวัน #ดูดวง #โหราศาสตร์ไทย #หมอดู #คนเกิดวันจันทร์')
        );

        // โทเคนที่ไม่ใช่แฮชแท็กต้องถูกทิ้ง ไม่ใช่ถูกนับ
        $this->assertSame('#ดูดวง #ดวง', FacebookContentPolicy::capHashtagLine('ข้อความปน #ดูดวง # #ดวง'));
        $this->assertSame('', FacebookContentPolicy::capHashtagLine(''));
    }

    /**
     * ตัดแฮชแท็กที่ AI เขียนปนในเนื้อหา — เก็บ 3 อันแรก ที่เหลือลบ
     *
     * @test
     */
    public function ตัดแฮชแท็กที่ปนในเนื้อหา(): void
    {
        $in = "ดวงวันนี้ดีมาก\n#ดวงประจำวัน #ไพ่ทาโรต์ #ดูดวงฟรี #หมอดู #แม่หมอจันทรา";

        $out = FacebookContentPolicy::capHashtagsInText($in);

        $this->assertSame(3, preg_match_all('/#[^\s#]+/u', $out));
        $this->assertStringContainsString('#ดวงประจำวัน', $out);
        $this->assertStringNotContainsString('#หมอดู', $out);
        $this->assertStringContainsString('ดวงวันนี้ดีมาก', $out, 'เนื้อหาต้องอยู่ครบ');

        // น้อยกว่าเพดาน = ไม่แตะ
        $this->assertSame('ก #ข', FacebookContentPolicy::capHashtagsInText('ก #ข'));
    }

    /**
     * กฎที่ฉีดเข้า prompt — ต้องพูดเรื่องอีโมจิ และตัวกฎเองต้องไม่มีอีโมจิ
     *
     * @test
     */
    public function กฎห้ามอีโมจิในpromptต้องสะอาด(): void
    {
        $rule = FacebookContentPolicy::noEmojiRule();

        $this->assertStringContainsString('อีโมจิ', $rule);
        $this->assertStringEndsWith("\n", $rule, 'ต้องมี \n ปิดท้ายเพื่อต่อในรายการกฎได้');
        // ตัวกฎเองต้องไม่มีอีโมจิ (ไม่งั้นเป็นการสอนโมเดลผิดทาง)
        $this->assertSame($rule, FacebookContentPolicy::stripEmoji($rule));
    }
}
