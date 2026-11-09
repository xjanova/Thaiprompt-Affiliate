<?php

namespace Tests\Unit\Services;

use App\Services\Fortune\JuntraVoiceLibrary;
use App\Services\Fortune\VoicePlanParser;
use PHPUnit\Framework\TestCase;

/**
 * 🔈 (2026-11-04) แผนเสียงของโหมดคุยด้วยเสียง — ด่านฝั่งเซิร์ฟเวอร์ที่ไม่เชื่อ AI 100%
 *
 * ทุกข้อที่ด่านนี้ทิ้ง/แปลงเป็นคลิป = เงิน ElevenLabs ที่ไม่ต้องจ่าย และเสียงห้องอื่นที่ลูกค้าไม่ต้องได้ยิน
 */
class VoicePlanParserTest extends TestCase
{
    private function plan(string $reply, string $persona = 'busaba', ?array $available = null, string $kind = 'reply'): array
    {
        [$display, $block] = VoicePlanParser::extract($reply);

        return VoicePlanParser::plan($persona, $display, $block, $available, $kind);
    }

    public function test_the_block_is_removed_from_the_chat_text_and_parsed(): void
    {
        $reply = "ความรักช่วงนี้ไปได้ดีค่ะ ✨\n\n[[VOICE]]\npre: prefix_1\nsay: [softly] ความรักช่วงนี้ไปได้ดีนะคะ ใจเย็น ๆ ไว้\npost: ask_more_1\n[[/VOICE]]";

        [$display] = VoicePlanParser::extract($reply);
        $this->assertSame('ความรักช่วงนี้ไปได้ดีค่ะ ✨', $display);

        $plan = $this->plan($reply);
        $this->assertSame('ai', $plan['source']);
        $this->assertSame('prefix_1', $plan['pre']);
        $this->assertSame('[softly] ความรักช่วงนี้ไปได้ดีนะคะ ใจเย็น ๆ ไว้', $plan['say']);
        $this->assertSame('ask_more_1', $plan['post']);
        $this->assertSame([$plan['say']], $plan['say_parts']);
    }

    /**
     * คำตอบจริงผ่านตัวกรองขาออกของ FortuneAIService ก่อนถึง JuntraChatService — ตัวกรองลอก [softly] ทิ้ง
     * AI จึงเขียนแท็กเป็น {softly} แล้วตัวนี้แปลงกลับเป็น [softly] ให้ ElevenLabs
     */
    public function test_a_reply_that_went_through_the_outbound_sanitizer_still_yields_the_plan(): void
    {
        $raw = "ความรักช่วงนี้ไปได้ดีค่ะ ✨\n[[VOICE]]\npre: prefix_1\nsay: {softly} ความรักช่วงนี้กำลังไปได้ดีนะคะ\npost: ask_more_1\n[[/VOICE]]";
        $plan = $this->plan(\App\Services\FortuneAIService::stripInternalContextTags($raw));

        $this->assertSame('ai', $plan['source']);
        $this->assertSame('prefix_1', $plan['pre']);
        $this->assertSame('[softly] ความรักช่วงนี้กำลังไปได้ดีนะคะ', $plan['say']);
        $this->assertSame('ask_more_1', $plan['post']);

        // บทที่เป็นคำเติมล้วนแบบมีแท็กปีกกา ก็ยังกลายเป็นคลิป
        $plan = $this->plan("อืม\n[[VOICE]]\nsay: {softly} อืม… ค่ะ\n[[/VOICE]]");
        $this->assertNull($plan['say']);
    }

    public function test_a_reply_cut_off_inside_the_block_never_shows_the_block(): void
    {
        [$display, $block] = VoicePlanParser::extract("ดีค่ะ\n[[VOICE]]\npre: ack_1\nsay: ความรัก");
        $this->assertSame('ดีค่ะ', $display);
        $this->assertNotNull($block);

        [$display, $block] = VoicePlanParser::extract('ดีค่ะ [[VOI');
        $this->assertSame('ดีค่ะ', $display);
        $this->assertNull($block);
    }

    public function test_clip_ids_outside_the_room_or_without_a_file_are_dropped(): void
    {
        $reply = "ok\n[[VOICE]]\npre: prefix_9\nsay:\npost: soothe_1\n[[/VOICE]]";
        $plan = $this->plan($reply);
        // prefix_9 ไม่มีในคลัง · soothe เป็นของระบบเลือก ไม่ใช่ของ AI → ทิ้งทั้งคู่ → ถอยไปตัดจากคำตอบ
        $this->assertSame('fallback', $plan['source']);
        $this->assertNull($plan['post']);

        // เว็บบอกว่ามีไฟล์แค่ ack_1 → ask_more_1 ใช้ไม่ได้
        $plan = $this->plan("ฟังอยู่ค่ะ\n[[VOICE]]\npre: ack_1\nsay:\npost: ask_more_1\n[[/VOICE]]", 'busaba', ['ack_1']);
        $this->assertSame('ack_1', $plan['pre']);
        $this->assertNull($plan['post']);
    }

    public function test_filler_only_or_library_text_in_say_becomes_a_clip_not_a_new_voice(): void
    {
        $plan = $this->plan("อืม\n[[VOICE]]\npre:\nsay: [softly] อืมมม… ค่ะ\npost:\n[[/VOICE]]");
        $this->assertNull($plan['say']);
        $this->assertSame('ack', JuntraVoiceLibrary::categoryOf((string) $plan['pre']));

        $plan = $this->plan("x\n[[VOICE]]\npre: prefix_1\nsay: มีอะไรอยากถามอีกไหมคะ?\npost:\n[[/VOICE]]");
        $this->assertNull($plan['say']);
        $this->assertSame('prefix_1', $plan['pre']);
        $this->assertSame('ask_more_1', $plan['post']);
    }

    public function test_a_very_short_say_is_not_synthesized_but_a_short_number_is(): void
    {
        $plan = $this->plan("x\n[[VOICE]]\npre: listen_1\nsay: ได้ค่ะ\npost:\n[[/VOICE]]");
        $this->assertNull($plan['say']);

        $plan = $this->plan("x\n[[VOICE]]\npre:\nsay: เลข 7\npost:\n[[/VOICE]]");
        $this->assertSame('เลข 7', $plan['say']);
    }

    public function test_say_is_cleaned_one_tag_no_emoji_no_price_and_capped(): void
    {
        $say = '[excited] [laughs] **ดีมาก** ✨ ไพ่เปิดแล้วค่ะ ราคา 39 บาท นะคะ ดูด้านล่างได้เลย https://x.y [whispers]';
        $plan = $this->plan("x\n[[VOICE]]\nsay: {$say}\n[[/VOICE]]");
        $this->assertStringStartsWith('[excited] ', $plan['say']);
        $this->assertStringNotContainsString('[laughs]', $plan['say']);
        $this->assertStringNotContainsString('[whispers]', $plan['say']);
        $this->assertStringNotContainsString('✨', $plan['say']);
        $this->assertStringNotContainsString('39', $plan['say']);
        $this->assertStringNotContainsString('http', $plan['say']);
        $this->assertStringNotContainsString('**', $plan['say']);

        $long = implode(' ', array_fill(0, 40, 'ความรักกำลังมา'));
        $plan = $this->plan("x\n[[VOICE]]\nsay: {$long}\n[[/VOICE]]");
        $this->assertLessThanOrEqual(VoicePlanParser::SAY_MAX_CHARS, mb_strlen($plan['say']));
        $this->assertCount(2, $plan['say_parts']);
        $this->assertSame($plan['say'], implode(' ', $plan['say_parts']));
    }

    public function test_without_a_block_the_reply_is_cut_at_a_phrase_boundary(): void
    {
        $reply = implode(' ', array_fill(0, 40, 'ไพ่ใบนี้บอกว่าดี')).' ✨';
        $plan = $this->plan($reply);
        $this->assertSame('fallback', $plan['source']);
        $this->assertLessThanOrEqual(VoicePlanParser::FALLBACK_MAX_CHARS, mb_strlen($plan['say']));
        $this->assertStringEndsWith('ไพ่ใบนี้บอกว่าดี', $plan['say']);
    }

    /** 🤍 (2026-11-09 owner) เลิกจับ "คำตอบวิกฤต" แล้ว — คำตอบที่มีเบอร์สายด่วนได้แผนเสียงตามปกติ */
    public function test_a_reply_with_a_hotline_number_is_voiced_normally(): void
    {
        $reply = "บุษบาเป็นห่วงนะคะ โทร 1323 ได้ฟรี 24 ชั่วโมง\n[[VOICE]]\nsay: บุษบาเป็นห่วงนะคะ โทรหาสายด่วน 1323 ได้เลยนะคะ\n[[/VOICE]]";
        $plan = $this->plan($reply);
        $this->assertSame('ai', $plan['source']);
        $this->assertNotNull($plan['say']);
    }

    public function test_a_guard_reply_never_synthesizes(): void
    {
        $plan = VoicePlanParser::plan('maemor', 'แม่หมอพิมพ์บทสวดให้ไม่ได้นะลูก', null, null, 'guard');
        $this->assertSame('guard', $plan['source']);
        $this->assertNull($plan['say']);
        $this->assertSame('ask_more_1', $plan['post']);
    }

    public function test_every_room_has_every_category_the_prompt_promises(): void
    {
        foreach (['maemor', 'busaba', 'kunsai'] as $persona) {
            foreach ([...JuntraVoiceLibrary::AI_CATEGORIES, 'soothe'] as $category) {
                $this->assertNotNull(JuntraVoiceLibrary::first($persona, null, $category), "{$persona} ไม่มีหมวด {$category}");
            }
            foreach (JuntraVoiceLibrary::clips($persona) as $id => $script) {
                $this->assertMatchesRegularExpression('/^[a-z_]+_\d+$/', $id);
                $this->assertDoesNotMatchRegularExpression('/\d+\s*บาท|฿/u', $script, "{$persona}.{$id} มีราคา");
            }
        }
        // บุษบาเรียกลูกค้าว่า "คุณ" — ห้ามมี "ลูก" ในบท
        foreach (JuntraVoiceLibrary::clips('busaba') as $id => $script) {
            $this->assertStringNotContainsString('ลูก', $script, "busaba.{$id}");
        }
    }
}
