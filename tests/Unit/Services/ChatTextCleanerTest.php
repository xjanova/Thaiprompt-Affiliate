<?php

namespace Tests\Unit\Services;

use App\Services\Fortune\ChatTextCleaner;
use App\Services\LineFortuneService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * 🧹 ล้างของที่โมเดลเขียนติดมาก่อนถึงแชทลูกค้า (2026-09-23)
 *
 * เจ้าของรายงาน: "เมื่อวาน แม่หมอทำนายใน LINE มีสัญลักษณ์พิเศษปรากฏในคำทำนาย"
 *   - FTU-260922-Z3061: คำทำนาย 39 มี "## Section A" / "## Section B" / "**ตัวหนา**" ดิบบน LINE
 *   - FTU-260922-G4552: คำตอบคุยต่อ 2 ครั้งเป็น `{ "response": "…\n\n…" }` ทั้งก้อน
 *
 * ทรงข้อความในเทสต์นี้ถอดจากของจริงบน prod ย้อนหลัง 30 วัน (18 ทรงของป้าย Section · หัวข้อ ## / ### · ** ค้างเดี่ยว)
 * ไม่ boot แอป — คลาสที่ทดสอบไม่แตะ config/DB
 */
class ChatTextCleanerTest extends TestCase
{
    /**
     * ป้ายโครงพรอมต์ที่หลุดมาเป็นหัวข้อ — ทรงจริงจาก prod → ผลที่ลูกค้าควรเห็น
     *
     * @return array<string, array{string, string}>
     */
    public static function sectionLabelShapes(): array
    {
        return [
            'ป้ายล้วน ##' => ['## Section A', ''],
            'ป้ายล้วน ไม่มี #' => ['Section B', ''],
            '## + ชื่อหัวข้อ' => ['## Section B — ภาพรวมชีวิตช่วงนี้', 'ภาพรวมชีวิตช่วงนี้'],
            'อีโมจิ + ตัวหนา' => ['🌙 **Section A — ทายเจ้าชะตา**', '🌙 ทายเจ้าชะตา'],
            'ตัวหนาล้วน' => ['**Section B — ภาพรวมชีวิตช่วงนี้**', 'ภาพรวมชีวิตช่วงนี้'],
            'ไม่มีเครื่องหมาย' => ['Section A — ทายเจ้าชะตา', 'ทายเจ้าชะตา'],
            '## + อีโมจิ' => ['## 🌙 Section A — ทายลูก', '🌙 ทายลูก'],
            '### + อีโมจิ' => ['### 🌙 Section A — ทายเจ้าชะตา', '🌙 ทายเจ้าชะตา'],
            '## + อีโมจิ B' => ['## 🔮 Section B — ภาพรวมชีวิตช่วงนี้', '🔮 ภาพรวมชีวิตช่วงนี้'],
            'ชื่อลูกค้าในหัวข้อ' => ['🌙 **Section A — ทายคุณสมใจ ใจดี**', '🌙 ทายคุณสมใจ ใจดี'],
            'อีโมจิ + ป้ายล้วน' => ['🌙 **Section A**', ''],
            // จับผี 2026-09-23: ตัวหนาปิดก่อนโคลอน — เดิมเหลือ ": ทายเจ้าชะตา"
            'ตัวหนาปิดก่อนโคลอน' => ['**Section A**: ทายเจ้าชะตา', 'ทายเจ้าชะตา'],
            'ไม่มีตัวคั่น + หัวข้อไทย' => ['## Section A ทายเจ้าชะตา', 'ทายเจ้าชะตา'],
        ];
    }

    /**
     * ประโยคภาษาอังกฤษที่บังเอิญขึ้นต้นด้วย "Section A" ไม่ใช่ป้าย — ป้ายจริงตามด้วยตัวคั่น/หัวข้อไทย/ท้ายบรรทัดเสมอ
     */
    #[Test]
    public function an_english_sentence_starting_with_section_is_not_a_label(): void
    {
        $text = 'Section A of the plan is ready';

        $this->assertSame($text, ChatTextCleaner::stripMarkdown($text));
    }

    #[Test]
    public function crlf_text_is_cleaned_without_leaving_carriage_returns(): void
    {
        $this->assertSame('ตัวหนา ค่ะ', ChatTextCleaner::stripMarkdown("## Section A\r\n\r\n**ตัวหนา** ค่ะ\r\n"));
        $this->assertSame("ไม่มี markdown\r\nบรรทัดสอง", ChatTextCleaner::stripMarkdown("ไม่มี markdown\r\nบรรทัดสอง"));
    }

    #[Test]
    #[DataProvider('sectionLabelShapes')]
    public function section_labels_are_removed_but_the_thai_title_stays(string $in, string $expected): void
    {
        $this->assertSame($expected, ChatTextCleaner::stripMarkdown($in));
    }

    #[Test]
    public function markdown_headings_and_bold_do_not_reach_the_customer(): void
    {
        $this->assertSame('🔮 ภาพรวมชีวิตช่วงนี้', ChatTextCleaner::stripMarkdown('## 🔮 ภาพรวมชีวิตช่วงนี้'));
        $this->assertSame('🕛 ลัคนา & เรือนชะตา', ChatTextCleaner::stripMarkdown('### 🕛 ลัคนา & เรือนชะตา'));
        $this->assertSame('', ChatTextCleaner::stripMarkdown('##'));

        // ตัวล้างเดิมทำ "**x**" เหลือ "*x*" — ต้องหายทั้งคู่
        $this->assertSame(
            'ภาพรวมชีวิตช่วงนี้ ลูกกำลังอยู่ในระยะสร้างฐาน ไพ่สี่ดาบหงาย',
            ChatTextCleaner::stripMarkdown('ภาพรวมชีวิตช่วงนี้ **ลูกกำลังอยู่ในระยะสร้างฐาน** ไพ่สี่ดาบหงาย')
        );
        $this->assertSame('✨ เคล็ดเสริมดวง', ChatTextCleaner::stripMarkdown('✨ **เคล็ดเสริมดวง**'));
        $this->assertSame('ผลลัพธ์ สำคัญมาก นะ', ChatTextCleaner::stripMarkdown('ผลลัพธ์ ***สำคัญมาก*** นะ'));

        // ตัวหนาคร่อมบรรทัด / โดนตัดจบ — ** ค้างเดี่ยวต้องไม่เหลือ (เจอจริง 24 บรรทัดใน 30 วัน)
        $this->assertSame(
            "ลูกยังคบแฟนต่อได้อีกนานค่ะ\nแต่ยังไม่ฟันธงว่าจะสร้างทุกอย่างด้วยกันจนสุดทาง",
            ChatTextCleaner::stripMarkdown("**ลูกยังคบแฟนต่อได้อีกนานค่ะ\nแต่ยังไม่ฟันธงว่าจะสร้างทุกอย่างด้วยกันจนสุดทาง**")
        );
    }

    #[Test]
    public function the_old_single_star_and_underscore_rules_still_apply(): void
    {
        $this->assertSame(
            '🌟✨ บทสรุปสุดท้ายจากแม่หมอจันทรา ✨🌟',
            ChatTextCleaner::stripMarkdown('🌟✨ *บทสรุปสุดท้ายจากแม่หมอจันทรา* ✨🌟')
        );
        $this->assertSame(
            'กดปุ่ม "🎧 อ่านให้ฟัง" ด้านล่าง (หรือพิมพ์ก็ได้)',
            ChatTextCleaner::stripMarkdown('กดปุ่ม *"🎧 อ่านให้ฟัง"* ด้านล่าง _(หรือพิมพ์ก็ได้)_')
        );
    }

    /**
     * ของที่ไม่ใช่ markdown ต้องผ่านไปทุกตัวอักษร — แฮชแท็ก · snake_case · เส้นคั่น · รายการ · keycap
     */
    #[Test]
    public function text_without_markdown_is_returned_byte_for_byte(): void
    {
        foreach ([
            '#ดูดวง #แม่หมอจันทรา',
            'ตรวจ user_id และ snake_case',
            '═══════════════════════',
            "- สีมงคล: ชมพู แดงอ่อน\n- เลขมงคล: 3, 6, 9",
            '#️⃣ เลข 3 เป็นเลขเด่น',
            "ลูกจะได้งานใหม่ก่อนสิ้นเดือนตุลาคมค่ะ\n\n\n\nเว้นบรรทัดเดิมไม่แตะ",
        ] as $text) {
            $this->assertSame($text, ChatTextCleaner::stripMarkdown($text));
        }
    }

    /**
     * 🔒 ด่านตรวจนับอีโมจิหัวข้อด้วย str_contains — ล้างแล้วอีโมจิทุกตัวต้องยังอยู่
     */
    #[Test]
    public function every_header_emoji_survives(): void
    {
        $emoji = ['🎯', '🌟', '🔮', '🕛', '💞', '💼', '💰', '🍀', '🌿', '🕉️'];
        $text = implode("\n\n", array_map(fn (string $e) => "## {$e} **หัวข้อ**\n\nเนื้อหา", $emoji));
        $out = ChatTextCleaner::stripMarkdown($text);

        foreach ($emoji as $e) {
            $this->assertStringContainsString("{$e} หัวข้อ", $out);
        }
    }

    #[Test]
    public function a_deep39_prediction_shaped_like_prod_comes_out_clean_and_stable(): void
    {
        $prediction = "🌟 คำทำนายเชิงลึกของคุณทดสอบ\n═══════════════════════\n❓ คำถามที่ 1: ขอดูพื้นดวงโดยรวมของเจ้าชะตา\n═══════════════════════\n\n"
            ."## Section A\n\nหมอจันทราดูดวงคุณทดสอบแล้วเห็นว่าเป็นคนใจร้อนแต่รักจริง\n\n"
            ."## 🕛 ลัคนา & เรือนชะตา\n\nลัคนาตุลย์ ทำให้ลูกมีเสน่ห์\n\n"
            ."## Section B\n\nภาพรวมชีวิตช่วงนี้ **ลูกกำลังอยู่ในระยะสร้างฐาน ไม่ใช่ระยะเก็บผลเต็มที่**\n\n"
            .'**ดาวพฤหัสบดีในภพ 2** เป็นแรงหนุนหลักของชีวิต';

        $out = ChatTextCleaner::stripMarkdown($prediction);

        $this->assertStringNotContainsString('#', $out);
        $this->assertStringNotContainsString('*', $out);
        $this->assertStringNotContainsString('Section', $out);
        $this->assertStringNotContainsString("\n\n\n", $out, 'บรรทัดป้ายที่ลบทิ้งต้องไม่ทิ้งช่องว่างซ้อน');
        $this->assertStringContainsString("═══════════════════════\n\nหมอจันทราดูดวงคุณทดสอบ", $out);
        $this->assertStringContainsString("🕛 ลัคนา & เรือนชะตา\n\nลัคนาตุลย์", $out);
        $this->assertStringContainsString('ดาวพฤหัสบดีในภพ 2 เป็นแรงหนุนหลักของชีวิต', $out);
        $this->assertSame($out, ChatTextCleaner::stripMarkdown($out), 'ล้างซ้ำต้องได้ผลเดิม');
    }

    /**
     * คำทำนาย 39 บน LINE ประกอบ Flex จาก deep_response ตรง ๆ — ต้องล้างที่ตัวสร้าง Flex เอง
     */
    #[Test]
    public function line_prediction_bubbles_carry_no_markdown(): void
    {
        $line = (new \ReflectionClass(LineFortuneService::class))->newInstanceWithoutConstructor();

        $prediction = "## Section A\n\n".str_repeat('หมอจันทราดูดวงแล้วเห็นว่าลูกเป็นคนใจร้อนแต่รักจริง ', 20)
            ."\n\n## 🔮 ภาพรวมชีวิตช่วงนี้\n\n**ลูกกำลังอยู่ในระยะสร้างฐาน**\n\n## Section B — คำแนะนำ\n\n"
            .str_repeat('ช่วงนี้ให้เก็บเงินก่อนใช้ ', 40);

        $texts = [];
        foreach ($line->buildSplitFortuneMessages($prediction, 'ทดสอบ', 'FTU-TEST-1') as $bubble) {
            array_walk_recursive($bubble, function ($value, $key) use (&$texts) {
                if ($key === 'text' && is_string($value)) {
                    $texts[] = $value;
                }
            });
        }
        $all = implode("\n", $texts);

        $this->assertGreaterThan(1, count($texts));
        $this->assertStringNotContainsString('##', $all);
        $this->assertStringNotContainsString('**', $all);
        $this->assertStringNotContainsString('Section', $all);
        $this->assertStringContainsString('🔮 ภาพรวมชีวิตช่วงนี้', $all);
        $this->assertStringContainsString('คำแนะนำ', $all);

        foreach ($line->splitTextForFlexPublic("## Section A\n\n**ตัวหนา** ค่ะ", 4500) as $chunk) {
            $this->assertSame('ตัวหนา ค่ะ', $chunk);
        }

        // ข้อความที่เป็นป้ายล้วน ล้างแล้วว่าง → คืนต้นฉบับ (text ว่าง = LINE ปฏิเสธทั้งข้อความ)
        $this->assertSame('## Section A', $line->cleanForChat('## Section A'));
    }

    /**
     * จับผี 2026-09-23: sendDeepReadingFlexSafe เคยผ่าก้อนก่อนแล้วค่อยล้าง — ก้อนที่มีแค่ "## Section A"
     * กลายเป็น Flex text ว่าง → LINE ปฏิเสธทั้ง carousel → ถอยไปส่งทีละกล่อง (เปลือง push)
     */
    #[Test]
    public function deep_reading_carousel_never_carries_an_empty_or_markdown_text(): void
    {
        $line = new class extends LineFortuneService
        {
            /** @var array<int, array> */
            public array $sent = [];

            public function __construct() {}

            public function sendRichMessagePriority(string $recipientId, array $richContent): bool
            {
                $this->sent[] = $richContent;

                return true;
            }

            public function sendMessagePriority(string $recipientId, string $message, array $options = []): bool
            {
                $this->sent[] = ['text' => $message];

                return true;
            }
        };

        $answer = "## Section A\n\n".str_repeat('ลูกเป็นคนใจร้อนแต่รักจริงและทำงานหนักเพื่อครอบครัว ', 28)
            ."\n\n## Section B — คำแนะนำ\n\n**ช่วงนี้ให้เก็บเงินก่อนใช้** ".str_repeat('แล้วจะเห็นผลภายในสามเดือน ', 12);
        $this->assertGreaterThan(1500, mb_strlen($answer), 'ต้องยาวพอให้เข้าเส้นผ่าก้อน');

        $this->assertTrue($line->sendDeepReadingFlexSafe('U-test', 1, 'ขอดูพื้นดวง', $answer, 1));
        $this->assertCount(1, $line->sent, 'ต้องจบใน carousel เดียว ไม่ถอยไปส่งทีละกล่อง');

        $texts = [];
        array_walk_recursive($line->sent, function ($value, $key) use (&$texts) {
            if ($key === 'text' && is_string($value)) {
                $texts[] = $value;
            }
        });

        $this->assertNotContains('', array_map('trim', $texts), 'Flex text ว่าง = LINE ปฏิเสธทั้งข้อความ');
        $all = implode("\n", $texts);
        $this->assertStringNotContainsString('##', $all);
        $this->assertStringNotContainsString('**', $all);
        $this->assertStringNotContainsString('Section', $all);
        $this->assertStringContainsString('ช่วงนี้ให้เก็บเงินก่อนใช้', $all);
    }

    // ─────────────────────────────── JSON ที่หลุดจากทางสำรอง ───────────────────────────────

    #[Test]
    public function a_json_wrapped_reply_is_unwrapped_to_plain_text(): void
    {
        // ทรงจริง FTU-260922-G4552: ขึ้นบรรทัดหลัง { และมี \n\n แบบ escape ในสตริง
        $leaked = "{\n \"response\": \"การเงินของลูกจะดีขึ้นอย่างเห็นได้ชัดค่ะ\\n\\nด้วยดาวพฤหัสบดีที่ครองตำแหน่งอุจ\"\n}";

        $this->assertSame(
            "การเงินของลูกจะดีขึ้นอย่างเห็นได้ชัดค่ะ\n\nด้วยดาวพฤหัสบดีที่ครองตำแหน่งอุจ",
            ChatTextCleaner::unwrapJsonReply($leaked)
        );
        $this->assertSame('ตอบค่ะ', ChatTextCleaner::unwrapJsonReply("```json\n{\"reply\":\"ตอบค่ะ\"}\n```"));
    }

    #[Test]
    public function a_json_reply_cut_off_mid_string_still_yields_the_text(): void
    {
        // max_tokens ตัดกลางสตริง + \u escape ครึ่งตัว
        $cut = "{\n \"response\": \"ลูกจะประสบความสำเร็จตามที่หวังค่ะ\\n\\nส่วนรายรับ\\u0E";

        $this->assertSame("ลูกจะประสบความสำเร็จตามที่หวังค่ะ\n\nส่วนรายรับ", ChatTextCleaner::unwrapJsonReply($cut));
    }

    #[Test]
    public function a_cut_right_after_a_backslash_still_yields_the_text(): void
    {
        $this->assertSame('ลูกจะได้งานใหม่ค่ะ', ChatTextCleaner::unwrapJsonReply('{"response": "ลูกจะได้งานใหม่ค่ะ\\'));
    }

    /**
     * จับผี 2026-09-23: JSON ที่เสียเพราะ " ไม่ได้ escape เคยโดนมองเป็น "ตัดจบ" แล้วเหลือคำตอบครึ่งท่อน
     */
    #[Test]
    public function unescaped_quotes_inside_the_reply_keep_the_whole_answer(): void
    {
        $this->assertSame(
            'แม่หมอบอกว่า "สู้ๆ" นะคะ ลูกจะได้งานใหม่ค่ะ',
            ChatTextCleaner::unwrapJsonReply('{"response": "แม่หมอบอกว่า "สู้ๆ" นะคะ ลูกจะได้งานใหม่ค่ะ"}')
        );

        // หลายคีย์ + JSON เสีย = ไม่เดา คืนต้นฉบับ (ดีกว่าได้คำตอบครึ่งท่อนเงียบ ๆ)
        $multi = '{"response": "บอกว่า "สู้ๆ" นะ", "offer_fortune": true}';
        $this->assertSame($multi, ChatTextCleaner::unwrapJsonReply($multi));
    }

    #[Test]
    public function non_json_or_json_without_a_reply_field_is_left_alone(): void
    {
        foreach ([
            'ลูกจะได้งานใหม่ก่อนสิ้นเดือนค่ะ',
            '{ลูก} คือคำในวงเล็บปีกกา',
            '{"foo":"bar"}',
            '',
        ] as $text) {
            $this->assertSame($text, ChatTextCleaner::unwrapJsonReply($text));
        }
    }

    #[Test]
    public function plain_reply_also_drops_a_speaker_label_copied_from_history(): void
    {
        $this->assertSame('ลูกจะได้งานค่ะ', ChatTextCleaner::plainReply('[หมอจันทรา]: ลูกจะได้งานค่ะ'));
        $this->assertSame('ลูกจะได้งานค่ะ', ChatTextCleaner::plainReply('แม่หมอ: ลูกจะได้งานค่ะ'));
        $this->assertSame('แม่หมอขอบอกว่าลูกจะได้งานค่ะ', ChatTextCleaner::plainReply('แม่หมอขอบอกว่าลูกจะได้งานค่ะ'));
        // ป้ายนำหน้า JSON — ต้องตัดป้ายก่อนถึงจะแกะได้
        $this->assertSame('ลูกจะได้งานค่ะ', ChatTextCleaner::plainReply('[หมอจันทรา]: {"response": "ลูกจะได้งานค่ะ"}'));
        $this->assertSame(
            "การเงินดีขึ้นค่ะ\n\nช่วงปลายปี",
            ChatTextCleaner::plainReply('{"response": "การเงินดีขึ้นค่ะ\\n\\nช่วงปลายปี"}')
        );
    }
}
