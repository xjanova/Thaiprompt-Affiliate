<?php

namespace Tests\Unit;

use App\Services\FortuneAIService;
use Tests\TestCase;

/**
 * 🌐 (2026-11-04) ป้ายการ์ด [[OFFER:หมวด]] ของห้องแชทเว็บต้องรอดตัวลอกแท็กของ FortuneAIService
 *
 * เคสจริงบน prod: คำตอบ "…กดเลือกการ์ดด้านล่างได้เลยน้า ✨ [[OFFER:love]]" ถึง JuntraChatService เป็น "… []"
 * ลูกค้าถูกชวนให้กดการ์ดที่ไม่มีอยู่จริง (การ์ดไม่ขึ้นเลยตั้งแต่ 19 ก.ย.)
 */
class WebChatOfferTagSurvivesSanitizerTest extends TestCase
{
    public function test_double_bracket_web_offer_tag_is_kept_for_the_chat_service(): void
    {
        $out = FortuneAIService::stripInternalContextTags('บุษบาจะเปิดไพ่ 3 ใบให้นะคะ กดการ์ดด้านล่างได้เลยน้า ✨ [[OFFER:love]]');

        $this->assertStringContainsString('[[OFFER:love]]', $out);
        $this->assertStringContainsString('[[ OFFER : general ]]', FortuneAIService::stripInternalContextTags('ได้เลยค่ะ [[ OFFER : general ]]'));
    }

    /** 🔈 (2026-11-04) แผนเสียงของโหมดคุยด้วยเสียง — ป้ายเปิดต้องรอดถึง JuntraChatService (ตัดบล็อกที่นั่น) */
    public function test_the_voice_plan_block_survives_for_the_chat_service(): void
    {
        $out = FortuneAIService::stripInternalContextTags("ดีค่ะ\n[[VOICE]]\npre: ack_1\nsay: {softly} ใจเย็นไว้นะคะ\npost: ask_more_1\n[[/VOICE]]");

        $this->assertStringContainsString("[[VOICE]]\npre: ack_1\nsay: {softly} ใจเย็นไว้นะคะ\npost: ask_more_1\n[[/VOICE]]", $out);
        // ชั้นเดียวยังโดนลอกเหมือนป้ายอื่น
        $this->assertStringNotContainsString('[VOICE]', FortuneAIService::stripInternalContextTags('ค่ะ [VOICE] ค่ะ'));
    }

    public function test_leaked_single_bracket_tags_are_still_stripped(): void
    {
        $out = FortuneAIService::stripInternalContextTags('แม่หมออยู่ตรงนี้ [OFFER:love] [TURN 3] [DM_COUNT 4] นะลูก [OFFER_FORTUNE]');

        $this->assertStringNotContainsString('[OFFER:love]', $out);
        $this->assertStringNotContainsString('TURN', $out);
        $this->assertStringNotContainsString('DM_COUNT', $out);
        $this->assertStringContainsString('[OFFER_FORTUNE]', $out);
    }
}
