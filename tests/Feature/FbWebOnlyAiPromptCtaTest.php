<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\FortuneBotMode;
use App\Services\Fortune\FortunePageContext;
use App\Services\Fortune\RepurchaseCta;
use App\Services\Fortune\WebHandoffCopy;
use App\Services\FortuneAIService;
use App\Services\FortuneConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 🌐 (2026-11-03) T12 — พรอมต์ AI + CTA ซื้อซ้ำ ใต้ FB web-only
 *
 * ล็อกกติกา (design หัวข้อ 6 แถว T12 + หัวข้อ 7):
 *  1. RepurchaseCta: FB + ธงเปิด = ชวนเข้าห้องแม่หมอ · LINE / Telegram / ธงปิด = ข้อความเดิมทุกตัวอักษร
 *  2. ข้อความปิดท้าย Pro Session / Celtic (closingInviteLine, buildProSessionClosingMessage, ยกเลิกที่เลือกแพคเกจ)
 *     ใต้ธงต้องไม่มี 'พิมพ์ "ดูดวง"' — LINE ไม่เปลี่ยน
 *  3. system prompt แชทฝั่ง FB ใต้ธงต้องไม่มีราคา / 'พิมพ์ "ดูดวง"' และมีกฎห้องแม่หมอ — LINE ยังมีราคาเหมือนเดิม
 *  4. พรอมต์ Pro Session (Deep/Celtic) ฝั่ง FB ไม่มีราคา ไม่มี upsell — LINE เหมือนเดิม
 *  5. AI หลอนเปิดไพ่ในแชท FB → ข้อความแทนแบบห้องแม่หมอ (ไม่มี "ค่าครู 99 บาท")
 *  6. Bill Psychology ไม่ทำงานกับลูกค้า FB ใต้ธง
 */
class FbWebOnlyAiPromptCtaTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '61550000000112';

    private const LINE_UID = 'U0123456789abcdef0123456789abc112';

    /** ตัวเลขราคา (เหมือน FortuneAIService::FB_WEB_ONLY_PRICE_REGEX ส่วนตัวเลข) */
    private const PRICE_REGEX = '/\d[\d,\.]*\s*(บาท|฿)|฿\s*\d/u';

    /** ชวนพิมพ์ "ดูดวง" */
    private const TYPE_DUDUANG_REGEX = '/พิมพ์\s*[*_"\'“”‘’\\\\]*\s*ดูดวง/u';

    private const LEGACY_PURPLE_CTA = '💜 หากต้องการดูใหม่ พิมพ์ *"ดูดวง"* ได้ตลอดเลยค่ะ ✨';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        FortunePageContext::forget();
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    /**
     * ตั้งค่าแถวกลาง (ล้าง memo ก่อน/หลัง — แบบเดียวกับ FbWebOnlyChannelEgressTest)
     */
    private function settings(bool $webOnly, array $override = []): FortuneTellingSetting
    {
        FortuneTellingSetting::clearSettingsCache();

        FortuneTellingSetting::getSettings()->forceFill(array_merge([
            'fortune_bot_mode' => FortuneBotMode::MODE_CLASSIC,
            'facebook_web_only' => $webOnly,
            'enable_web_fortune_button' => false,
            'enable_deep_reading' => true,
            'enable_celtic_cross' => true,
            'chat_ai_provider' => 'openai',
            'chat_ai_model' => 'gpt-test-local',
            'chat_ai_api_key' => 'sk-test-t12-local',
        ], $override))->save();

        FortuneTellingSetting::clearSettingsCache();

        return FortuneTellingSetting::getSettings();
    }

    private function fbReading(array $attrs = []): FortuneReading
    {
        return FortuneReading::create(array_merge([
            'facebook_user_id' => self::PSID,
            'facebook_user_name' => 'ทดสอบ',
            'questions' => [],
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'response_type' => 'private_message',
            'ai_response' => '',
            'ai_provider' => '',
            'platform' => 'facebook',
            'platform_user_id' => self::PSID,
            'bill_reference' => FortuneReading::generateBillReference(),
            // ไม่ได้จ่ายวันนี้ → closingInviteLine เข้ากิ่ง "ชวนดูใหม่"
            'is_paid' => false,
        ], $attrs));
    }

    /** บิล LINE — LINE ID นั่งอยู่ในคอลัมน์ facebook_user_id (ดู CLAUDE.md) */
    private function lineReading(array $attrs = []): FortuneReading
    {
        return $this->fbReading(array_merge([
            'facebook_user_id' => self::LINE_UID,
            'platform' => 'line',
            'platform_user_id' => self::LINE_UID,
        ], $attrs));
    }

    /**
     * FCS ที่เปิดเมธอด protected ของ trait ให้เทสต์เรียก
     */
    private function fcs(FortuneTellingSetting $settings): FortuneConversationService
    {
        return new class($settings) extends FortuneConversationService
        {
            public function closing(FortuneReading $r): string
            {
                return $this->closingInviteLine($r);
            }

            public function proClosing(FortuneReading $r): string
            {
                return $this->buildProSessionClosingMessage($r);
            }

            public function proPrompt(FortuneReading $r, string $type): string
            {
                return $this->buildProSessionSystemPrompt($r, $type);
            }

            public function tierChoice(FortuneReading $r, string $text): array
            {
                return $this->handleTierChoice($r, $text);
            }
        };
    }

    /**
     * AI service ที่ดักพรอมต์ก่อนยิงจริง (ไม่มี HTTP)
     */
    private function ai(FortuneTellingSetting $settings): FortuneAIService
    {
        return new class($settings) extends FortuneAIService
        {
            public ?string $captured = null;

            public string $fakeResponse = 'สวัสดีค่ะ แม่หมออยู่ตรงนี้นะคะ';

            public function base(bool $webOnly): string
            {
                return $this->buildChatBaseSystemMessage($webOnly);
            }

            public function sanitize(array $result, string $mode, bool $webOnly): array
            {
                return $this->sanitizeChatResult($result, $mode, $webOnly);
            }

            protected function callChatOpenAICompatible(string $prompt, string $systemMessage, string $apiKey, string $model, string $provider, array $config): array
            {
                $this->captured = $systemMessage;

                return ['response' => $this->fakeResponse, 'tokens_used' => 0];
            }
        };
    }

    /** บรรทัดที่ชวนพิมพ์ "ดูดวง" และไม่ใช่กฎห้าม */
    private function sellTypeLines(string $text): array
    {
        return array_values(array_filter(
            explode("\n", $text),
            fn ($l) => preg_match(self::TYPE_DUDUANG_REGEX, $l) && ! str_contains($l, 'ห้าม')
        ));
    }

    private function priceLines(string $text): array
    {
        return array_values(array_filter(explode("\n", $text), fn ($l) => preg_match(self::PRICE_REGEX, $l)));
    }

    // ───────────────────────── 1. RepurchaseCta ─────────────────────────

    public function test_repurchase_cta_switches_only_for_fb_under_flag(): void
    {
        $this->settings(true);

        $web = RepurchaseCta::webText();
        $this->assertSame(WebHandoffCopy::reply(WebHandoffCopy::KIND_PAID_CTA), $web);
        $this->assertDoesNotMatchRegularExpression(self::TYPE_DUDUANG_REGEX, $web);
        $this->assertDoesNotMatchRegularExpression(self::PRICE_REGEX, $web);

        $this->assertSame($web, RepurchaseCta::repurchaseCta('facebook', self::PSID));
        // PSID ตัวเลขล้วน = FB เสมอ แม้ hint ไม่มา
        $this->assertSame($web, RepurchaseCta::repurchaseCta(null, self::PSID));

        $this->assertSame(RepurchaseCta::LEGACY_TEXT, RepurchaseCta::repurchaseCta('line', self::LINE_UID));
        $this->assertSame('เดิม', RepurchaseCta::repurchaseCta('line', self::LINE_UID, 'เดิม'));
        $this->assertSame(RepurchaseCta::LEGACY_TEXT, RepurchaseCta::repurchaseCta('telegram', 'tg_12345'));

        $this->assertSame($web, RepurchaseCta::forReading($this->fbReading()));
        $this->assertSame('เดิม', RepurchaseCta::forReading($this->lineReading(), 'เดิม'));
        $this->assertSame('เดิม', RepurchaseCta::forReading(null, 'เดิม'));

        $this->assertSame(WebHandoffCopy::webRoomQuickReply(), RepurchaseCta::quickReplies('facebook', self::PSID, [['x' => 1]]));
        $this->assertSame([['x' => 1]], RepurchaseCta::quickReplies('line', self::LINE_UID, [['x' => 1]]));
    }

    public function test_repurchase_cta_flag_off_keeps_legacy_for_fb(): void
    {
        $this->settings(false);

        $this->assertSame(RepurchaseCta::LEGACY_TEXT, RepurchaseCta::repurchaseCta('facebook', self::PSID));
        $this->assertSame('เดิม', RepurchaseCta::forReading($this->fbReading(), 'เดิม'));
        $this->assertFalse(RepurchaseCta::appliesTo('facebook', self::PSID));
    }

    // ───────────────────────── 2. ข้อความปิดท้าย ─────────────────────────

    public function test_closing_messages_have_no_type_duduang_on_fb_under_flag(): void
    {
        $s = $this->settings(true);
        $fcs = $this->fcs($s);
        $reading = $this->fbReading();

        $closing = $fcs->closing($reading);
        $this->assertSame(RepurchaseCta::webText(), $closing);

        $pro = $fcs->proClosing($reading);
        $this->assertDoesNotMatchRegularExpression(self::TYPE_DUDUANG_REGEX, $pro);
        $this->assertStringContainsString(RepurchaseCta::webText(), $pro);

        $tierReading = $this->fbReading(['conversation_status' => FortuneReading::STATUS_TIER_CHOICE]);
        $cancel = $fcs->tierChoice($tierReading, 'ยกเลิก');
        $this->assertSame('cancelled', $cancel['action']);
        // 🙏 (2026-11-09 owner) "ยกเลิกก็ยกเลิกแค่นั้น" — ไม่แนบลิงก์ชวนเข้าเว็บแล้ว
        $this->assertSame(FortuneReading::CANCEL_NOTICE, $cancel['message']);
    }

    public function test_closing_messages_unchanged_on_line(): void
    {
        $s = $this->settings(true);
        $fcs = $this->fcs($s);
        $reading = $this->lineReading();

        $this->assertSame(self::LEGACY_PURPLE_CTA, $fcs->closing($reading));
        $this->assertStringEndsWith('💎 หากต้องการดูใหม่ พิมพ์ *"ดูดวง"* ได้ตลอดเลยค่ะ', $fcs->proClosing($reading));

        $tierReading = $this->lineReading(['conversation_status' => FortuneReading::STATUS_TIER_CHOICE]);
        $cancel = $fcs->tierChoice($tierReading, 'ยกเลิก');
        // 🙏 (2026-11-09 owner) "ยกเลิกก็ยกเลิกแค่นั้น" — ไม่ชวนพิมพ์ "ดูดวง" แล้ว
        $this->assertSame(FortuneReading::CANCEL_NOTICE, $cancel['message']);
    }

    public function test_celtic_repick_line_goes_through_repurchase_cta(): void
    {
        // จุดชวนจับไพ่ใหม่อยู่ลึกในเส้นถามไพ่ Celtic (ต้องมี AI ตอบ [OFF_TOPIC_REPICK]) —
        //   ล็อกว่าข้อความถูกส่งผ่าน RepurchaseCta ไม่ใช่ hardcode 'พิมพ์ "ดูดวง"' ตรงๆ
        $src = file_get_contents(app_path('Services/Fortune/CelticCrossConversationTrait.php'));
        $this->assertStringContainsString(
            "RepurchaseCta::forReading(\$reading, 'พิมพ์ *\"ดูดวง\"* เมื่อพร้อม แม่หมอจะเปิดประตูพลังให้ใหม่อีกครั้ง')",
            $src
        );
        $this->assertStringNotContainsString('."   พิมพ์ *\"ดูดวง\"* เมื่อพร้อม', $src);

        $this->settings(true);
        $legacy = 'พิมพ์ *"ดูดวง"* เมื่อพร้อม แม่หมอจะเปิดประตูพลังให้ใหม่อีกครั้ง';
        $this->assertSame(RepurchaseCta::webText(), RepurchaseCta::forReading($this->fbReading(), $legacy));
        $this->assertSame($legacy, RepurchaseCta::forReading($this->lineReading(), $legacy));
    }

    // ───────────────────────── 3. พรอมต์แชท ─────────────────────────

    public function test_is_fb_web_only_prompt_detection(): void
    {
        $s = $this->settings(true);
        $ai = $this->ai($s);

        $this->assertTrue($ai->isFbWebOnlyPrompt(['id' => self::PSID]));
        $this->assertTrue($ai->isFbWebOnlyPrompt(null, self::PSID));
        $this->assertFalse($ai->isFbWebOnlyPrompt(['id' => self::LINE_UID, 'platform' => 'line']));
        $this->assertFalse($ai->isFbWebOnlyPrompt(['id' => 'tg_998877']));
        $this->assertTrue($ai->isFbWebOnlyPrompt(['_fb_web_only' => true]));
        $this->assertFalse($ai->isFbWebOnlyPrompt(null));

        $ai->setFbWebOnlyContext(false);
        $this->assertFalse($ai->isFbWebOnlyPrompt(['id' => self::PSID]));

        $off = $this->ai($this->settings(false));
        $this->assertFalse($off->isFbWebOnlyPrompt(['id' => self::PSID]));
    }

    public function test_chat_base_prompt_has_no_price_on_fb_but_line_unchanged(): void
    {
        $s = $this->settings(true);
        $ai = $this->ai($s);

        $fb = FortuneAIService::scrubFbWebOnlyPrompt($ai->base(true));
        $this->assertSame([], $this->priceLines($fb), 'พรอมต์ FB ยังมีราคา');
        $this->assertSame([], $this->sellTypeLines($fb), 'พรอมต์ FB ยังชวนพิมพ์ "ดูดวง"');
        $this->assertStringContainsString(FortuneAIService::FB_WEB_ONLY_PROMPT_MARKER, $fb);
        $this->assertStringNotContainsString('[USE_STRIPE]', str_replace('ห้ามใช้ [DEEP_READING] และ [USE_STRIPE]', '', $fb));

        $line = $ai->base(false);
        $this->assertNotSame([], $this->priceLines($line), 'พรอมต์ LINE ต้องยังมีราคาเหมือนเดิม');
        $this->assertStringNotContainsString(FortuneAIService::FB_WEB_ONLY_PROMPT_MARKER, $line);
    }

    public function test_generate_chat_response_prompt_scrubbed_for_fb_only(): void
    {
        $s = $this->settings(true);

        $ai = $this->ai($s);
        $res = $ai->generateChatResponse('สวัสดีค่ะแม่หมอ', ['id' => self::PSID, 'name' => 'ทดสอบ']);
        $this->assertNotNull($ai->captured);
        $this->assertSame([], $this->priceLines($ai->captured), 'พรอมต์แชท FB ยังมีราคา');
        $this->assertSame([], $this->sellTypeLines($ai->captured));
        $this->assertStringContainsString(FortuneAIService::FB_WEB_ONLY_PROMPT_MARKER, $ai->captured);
        $this->assertSame('สวัสดีค่ะ แม่หมออยู่ตรงนี้นะคะ', $res['response']);

        $lineAi = $this->ai($s);
        $lineAi->generateChatResponse('สวัสดีค่ะแม่หมอ', ['id' => self::LINE_UID, 'platform' => 'line', 'name' => 'ทดสอบ']);
        $this->assertNotNull($lineAi->captured);
        $this->assertStringNotContainsString(FortuneAIService::FB_WEB_ONLY_PROMPT_MARKER, $lineAi->captured);
        $this->assertNotSame([], $this->priceLines($lineAi->captured));

        // ธงปิด → FB ได้พรอมต์เดิม
        $offAi = $this->ai($this->settings(false));
        $offAi->generateChatResponse('สวัสดีค่ะแม่หมอ', ['id' => self::PSID, 'name' => 'ทดสอบ']);
        $this->assertStringNotContainsString(FortuneAIService::FB_WEB_ONLY_PROMPT_MARKER, (string) $offAi->captured);
    }

    public function test_scrub_keeps_prohibition_lines_and_is_idempotent(): void
    {
        $prompt = "บรรทัดปกติ\nราคา 99 บาท\nพิมพ์ \"ดูดวง\" เพื่อเริ่ม\nห้ามบอกให้พิมพ์ \"ดูดวง\"\nโอนเข้าเลขบัญชี 123-4-56789-0";
        $once = FortuneAIService::scrubFbWebOnlyPrompt($prompt);
        $this->assertStringContainsString('บรรทัดปกติ', $once);
        $this->assertStringNotContainsString('99 บาท', $once);
        $this->assertStringNotContainsString('พิมพ์ "ดูดวง" เพื่อเริ่ม', $once);
        $this->assertStringNotContainsString('123-4-56789-0', $once);
        $this->assertStringContainsString('ห้ามบอกให้พิมพ์', $once);
        $this->assertSame($once, FortuneAIService::scrubFbWebOnlyPrompt($once));
        $this->assertSame(1, substr_count($once, FortuneAIService::FB_WEB_ONLY_PROMPT_MARKER));
    }

    // ───────────────────────── 4. พรอมต์ Pro Session ─────────────────────────

    public function test_pro_session_prompts_have_no_price_or_upsell_on_fb(): void
    {
        $s = $this->settings(true);
        $fcs = $this->fcs($s);

        foreach (['deep', 'celtic'] as $type) {
            $prompt = $fcs->proPrompt($this->fbReading(), $type);
            $this->assertSame([], $this->priceLines($prompt), "พรอมต์ Pro {$type} ฝั่ง FB ยังมีราคา");
            $this->assertSame([], $this->sellTypeLines($prompt), "พรอมต์ Pro {$type} ฝั่ง FB ยังชวนพิมพ์ ดูดวง");
            $this->assertStringNotContainsString('ลูกค้าจ่าย', $prompt);
            $this->assertStringNotContainsString('สนใจพิมพ์ *ดูดวง*', $prompt);
            $this->assertStringContainsString(FortuneAIService::FB_WEB_ONLY_PROMPT_MARKER, $prompt);
        }
    }

    public function test_pro_session_prompts_unchanged_on_line(): void
    {
        $s = $this->settings(true);
        $fcs = $this->fcs($s);

        $deep = $fcs->proPrompt($this->lineReading(), 'deep');
        $this->assertStringContainsString('ลูกค้าจ่าย 39฿ จบแล้ว', $deep);
        $this->assertStringContainsString('สนใจพิมพ์ *ดูดวง*', $deep);
        $this->assertStringNotContainsString(FortuneAIService::FB_WEB_ONLY_PROMPT_MARKER, $deep);

        $celtic = $fcs->proPrompt($this->lineReading(), 'celtic');
        $this->assertStringContainsString('สมราคา 99฿', $celtic);
        $this->assertStringNotContainsString(FortuneAIService::FB_WEB_ONLY_PROMPT_MARKER, $celtic);
    }

    // ───────────────────────── 5-6. หลอนเปิดไพ่ / Bill Psychology ─────────────────────────

    public function test_hallucination_replacement_is_web_variant_on_fb(): void
    {
        $s = $this->settings(true);
        $ai = $this->ai($s);
        $fake = 'เรียบร้อยแล้วค่ะ หมอจันทราเปิดไพ่ให้แล้ว ไพ่ The Star ขึ้นมาบอกว่าเจ้าชะตาจะเจอโชคใหญ่ในเดือนหน้า ความรักกำลังจะเข้ามาแน่นอนค่ะ';

        $fb = $ai->sanitize(['response' => $fake], 'chat', true);
        $this->assertSame(FortuneAIService::FB_WEB_ONLY_HALLUCINATION_REPLY, $fb['response']);
        $this->assertDoesNotMatchRegularExpression(self::PRICE_REGEX, $fb['response']);
        $this->assertDoesNotMatchRegularExpression(self::TYPE_DUDUANG_REGEX, $fb['response']);

        $line = $ai->sanitize(['response' => $fake], 'chat', false);
        $this->assertStringContainsString('ค่าครู 99 บาท', $line['response']);

        // ปลายทางจริง: generateChatResponse ของลูกค้า FB ส่งธงไปถึง sanitize เอง
        $ai->fakeResponse = $fake;
        $res = $ai->generateChatResponse('สวัสดีค่ะแม่หมอ', ['id' => self::PSID]);
        $this->assertSame(FortuneAIService::FB_WEB_ONLY_HALLUCINATION_REPLY, $res['response']);
    }

    public function test_bill_psychology_skipped_for_fb_under_flag(): void
    {
        $s = $this->settings(true);
        $ai = $this->ai($s);

        $this->assertNull($ai->generateBillPsychologyResponse('ยังไม่ได้โอนค่ะ', ['id' => self::PSID], [], ['has_bill' => true]));
        $this->assertNull($ai->captured, 'ห้ามยิง AI เลนบิลให้ลูกค้า FB ใต้ธง');
    }
}
