<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\FortuneResponseTemplate;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\Fortune\FortunePageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * 🌐 (2026-11-03) T9 — ชั้นส่ง FacebookWebhookService ใต้ FB web-only
 *
 * ล็อกกติกา (design หัวข้อ 7 แถว T8/T9):
 *  1. ธงเปิด + PSID: ข้อความเรื่องเงิน ("ค่าครู 99 บาท") ถูกบล็อก ไม่ยิง Graph เลย + Log::critical
 *  2. caller ส่ง paid_delivery → ผ่าน (ทุกก้อนที่ถูกซอยข้างในด้วย)
 *  3. ลูกค้าระดับ A (legacy paid in-flight) → ข้อความผ่าน แต่รูป QR ไม่ผ่าน
 *  4. ปุ่มราคาถูกตัดทิ้ง ข้อความที่เหลือยังส่ง
 *  5. ธงปิด = ส่งเหมือนเดิมทุกบิต
 *  6. setupMessengerProfile แบบเว็บ: WEB_ROOM, ไม่มีราคา/ไม่มี /auth/facebook/ไม่มี MENU_DEEP_FORTUNE
 *  7. sendFortuneTelling: ยังไม่จ่าย = ไม่ส่ง · จ่ายแล้ว = ส่ง DM + CTA ห้องแม่หมอ (ไม่มี 'ดูดวงละเอียด')
 */
class FbWebOnlySendLayerTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '61550000000099';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Http::fake(['*' => Http::response(['message_id' => 'mid.1', 'result' => 'success'], 200)]);
    }

    protected function tearDown(): void
    {
        FortunePageContext::forget();
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    private function settings(bool $webOnly, array $extra = []): void
    {
        FortuneTellingSetting::clearSettingsCache();
        FortuneTellingSetting::getSettings()->forceFill(array_merge([
            'facebook_page_token' => 'TOKEN_TEST',
            'facebook_web_only' => $webOnly,
            'respond_in_comment' => false,
            'enable_deep_reading' => true,
            'enable_celtic_cross' => true,
        ], $extra))->save();
        FortuneTellingSetting::clearSettingsCache();

        $this->assertSame($webOnly, (bool) FortuneTellingSetting::getSettings()->facebook_web_only, 'ตั้งธงไม่ลง DB');
    }

    private function fb(bool $webOnly, array $extra = []): FacebookWebhookService
    {
        $this->settings($webOnly, $extra);

        return new FacebookWebhookService;
    }

    /** ข้อความทั้งหมดที่ยิงไป Graph (json ของทุก request รวมกัน) */
    private function sentBodies(): string
    {
        return collect(Http::recorded())
            ->map(fn ($pair) => json_encode($pair[0]->data(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->implode("\n");
    }

    private function paidInPrediction(): FortuneReading
    {
        return FortuneReading::create([
            'platform' => 'facebook',
            'facebook_user_id' => self::PSID,
            'facebook_user_name' => 'ลูกค้า FB',
            'questions' => ['การเงินปีนี้'],
            'is_paid' => true,
            'conversation_status' => FortuneReading::IN_PREDICTION_STATUSES[0],
        ]);
    }

    // ── 1-2. ข้อความ ─────────────────────────────────────────────

    public function test_ธงเปิด_บล็อกค่าครูที่ชั้นส่ง_ไม่ยิง_graph_และ_log_critical(): void
    {
        $fb = $this->fb(true);
        Log::spy();

        $this->assertFalse($fb->sendMessage(self::PSID, 'ดูดวงละเอียดค่าครู 99 บาทค่ะ'));

        Http::assertNothingSent();
        Log::shouldHaveReceived('critical')
            ->withArgs(fn ($msg, $ctx = []) => str_contains($msg, 'บล็อกข้อความเรื่องเงิน') && ($ctx['method'] ?? null) === 'sendMessage')
            ->once();
    }

    public function test_ธงเปิด_paid_delivery_ปล่อยผ่าน(): void
    {
        $fb = $this->fb(true);

        $this->assertTrue($fb->sendMessage(self::PSID, 'ช่วงนี้จะมีคนโอนเงินมาให้ 5,000 บาท', ['paid_delivery' => true]));

        Http::assertSentCount(1);
        $this->assertStringContainsString('5,000 บาท', $this->sentBodies());
    }

    public function test_ธงเปิด_ข้อความธรรมดาส่งได้_และคำว่าบทบาทไม่โดน(): void
    {
        $fb = $this->fb(true);

        $this->assertTrue($fb->sendMessage(self::PSID, 'แม่หมอฟังอยู่นะคะ บทบาทของเราช่วงนี้สำคัญค่ะ'));

        Http::assertSentCount(1);
    }

    public function test_ธงปิด_ข้อความเรื่องเงินส่งได้ตามเดิม(): void
    {
        $fb = $this->fb(false);

        $this->assertTrue($fb->sendMessage(self::PSID, 'ค่าครู 99 บาท โอนแล้วส่งสลิปได้เลยค่ะ'));

        Http::assertSentCount(1);
    }

    public function test_ธงเปิด_ระดับ_a_ข้อความเรื่องเงินผ่าน_แต่รูป_qr_ไม่ผ่าน(): void
    {
        $fb = $this->fb(true);
        $this->paidInPrediction();

        $this->assertTrue($fb->sendMessage(self::PSID, 'ดวงการเงิน: จะมีรายรับเข้ามา 20,000 บาท'));
        Http::assertSentCount(1);

        $qr = url('storage/qrcodes/fortune/fortune_pp_77.png');
        $this->assertFalse($fb->sendImage(self::PSID, $qr));
        Http::assertSentCount(1);
    }

    public function test_ธงเปิด_ระดับ_a_ไม่ปลดล็อกข้อความขาย_และปุ่มราคา(): void
    {
        $fb = $this->fb(true);
        $this->paidInPrediction();

        // ข้อความขาย/ทางจ่าย → ยังบล็อกแม้เป็นระดับ A
        $this->assertFalse($fb->sendMessage(self::PSID, 'อยากดูลึกต่อ ค่าครู 99 บาท โอนเข้าเลขบัญชีนี้ได้เลยค่ะ'));
        Http::assertNothingSent();

        // ปุ่มราคาถูกตัด ปุ่มอื่นยังส่ง
        $this->assertTrue($fb->sendQuickReplies(self::PSID, 'ไพ่ใบถัดไปพร้อมแล้วค่ะ', [
            ['title' => '🔹 ดูดวง 39฿', 'payload' => 'TIER_DEEP_39'],
            ['title' => '✨ พร้อม', 'payload' => 'TAROT_READY'],
        ]));
        $body = $this->sentBodies();
        $this->assertStringContainsString('TAROT_READY', $body);
        $this->assertStringNotContainsString('TIER_DEEP_39', $body);
    }

    public function test_ธงเปิด_ข้อความยาว_paid_delivery_ทุกก้อนผ่าน(): void
    {
        $fb = $this->fb(true);
        $long = str_repeat("คำทำนายช่วงนี้ดีมากค่ะ จะมีคนโอนเงินมาให้ 3,000 บาท\n", 60); // > 1800 ตัวอักษร

        $this->assertTrue($fb->sendQuickReplies(self::PSID, $long, [
            ['title' => '🌙 เข้าห้องแม่หมอ', 'payload' => 'WEB_ROOM'],
        ], ['paid_delivery' => true]));

        $this->assertGreaterThan(1, count(Http::recorded()), 'ก้อนแรก ๆ ต้องถูกส่งด้วย ไม่ใช่โดนบล็อกกลางทาง');
        $this->assertStringContainsString('WEB_ROOM', $this->sentBodies());
    }

    // ── 3-4. ปุ่ม / template / รูป ───────────────────────────────

    public function test_ธงเปิด_ปุ่มราคาถูกตัด_ปุ่มอื่นยังส่ง(): void
    {
        $fb = $this->fb(true);

        $this->assertTrue($fb->sendQuickReplies(self::PSID, 'เลือกได้เลยค่ะ', [
            ['title' => 'ดู vip ส่วนตัว 99บาท', 'payload' => 'TIER_CELTIC_99'],
            ['title' => '🔹 ดูดวง 39฿', 'payload' => 'TIER_DEEP_39'],
            ['title' => '🌙 เข้าห้องแม่หมอ', 'payload' => 'WEB_ROOM'],
        ]));

        $body = $this->sentBodies();
        $this->assertStringContainsString('WEB_ROOM', $body);
        $this->assertStringNotContainsString('TIER_CELTIC_99', $body);
        $this->assertStringNotContainsString('TIER_DEEP_39', $body);
    }

    public function test_ธงเปิด_button_template_หัวการ์ดเรื่องเงินถูกบล็อก_paid_delivery_ผ่าน(): void
    {
        $fb = $this->fb(true);
        $payload = [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => 'สแกน QR พร้อมเพย์ แล้วส่งสลิปได้เลยค่ะ',
                    'buttons' => [['type' => 'postback', 'title' => 'โอนแล้ว', 'payload' => 'REPORT_PAYMENT']],
                ],
            ],
        ];

        $this->assertFalse($fb->sendButtonTemplate(self::PSID, $payload));
        Http::assertNothingSent();

        $this->assertTrue($fb->sendButtonTemplate(self::PSID, $payload, ['paid_delivery' => true]));
        Http::assertSentCount(1);
    }

    public function test_ธงเปิด_button_template_ปุ่มเงินหมดทุกปุ่ม_ไม่ส่งกล่องเปล่า(): void
    {
        $fb = $this->fb(true);

        $this->assertFalse($fb->sendButtonTemplate(self::PSID, [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => 'แม่หมอพร้อมแล้วค่ะ',
                    'buttons' => [['type' => 'web_url', 'title' => 'จ่ายด้วยบัตร', 'url' => 'https://checkout.stripe.com/c/pay/x']],
                ],
            ],
        ]));

        Http::assertNothingSent();
    }

    public function test_ธงเปิด_รูป_qr_บล็อก_รูปทั่วไปผ่าน_paid_delivery_ผ่าน(): void
    {
        $fb = $this->fb(true);

        $this->assertFalse($fb->sendImage(self::PSID, url('storage/qrcodes/fortune/fortune_pp_12.png')));
        Http::assertNothingSent();

        $this->assertTrue($fb->sendImage(self::PSID, 'https://example.test/tarot/the-moon.jpg'));
        Http::assertSentCount(1);

        $this->assertTrue($fb->sendImage(self::PSID, url('storage/qrcodes/fortune/fortune_pp_12.png'), null, ['paid_delivery' => true]));
        Http::assertSentCount(2);
    }

    public function test_ธงเปิด_รูปที่ตรงกับ_qr_จ่ายเงินที่ตั้งไว้ถูกบล็อก(): void
    {
        $fb = $this->fb(true, ['payment_qr_image' => 'fortune/maemor-bank.png']);

        $this->assertFalse($fb->sendImage(self::PSID, asset('storage/fortune/maemor-bank.png')));
        Http::assertNothingSent();
    }

    public function test_ธงเปิด_ตอบคอมเมนต์สาธารณะห้ามมีราคา(): void
    {
        $fb = $this->fb(true);

        $this->assertFalse($fb->replyToComment('123_456', 'ทักแชทมาได้เลย ค่าครูแค่ 39 บาท'));
        Http::assertNothingSent();

        $this->assertTrue($fb->replyToComment('123_456', 'แม่หมอทักไปใน inbox แล้วนะคะ 🌙'));
        Http::assertSentCount(1);
    }

    // ── 6. Messenger profile ─────────────────────────────────────

    public function test_ธงเปิด_setup_messenger_profile_แบบเว็บไม่มีราคา(): void
    {
        $fb = $this->fb(true);

        $this->assertTrue($fb->setupMessengerProfile()['success']);

        $body = $this->sentBodies();
        $this->assertStringContainsString('"payload":"WEB_ROOM"', $body);
        $this->assertStringNotContainsString('MENU_DEEP_FORTUNE', $body);
        $this->assertStringNotContainsString('/auth/facebook', $body);
        $this->assertStringNotContainsString('รายได้', $body);
        $this->assertStringNotContainsString('หลักล้าน', $body);
        $this->assertFalse(FacebookWebhookService::containsMoneyText($body), 'โปรไฟล์ต้องไม่มีคำเรื่องเงิน');

        $sent = Http::recorded()[0][0]->data();
        $this->assertLessThanOrEqual(3, count($sent['persistent_menu'][0]['call_to_actions']));
        $this->assertLessThanOrEqual(4, count($sent['ice_breakers'][0]['call_to_actions']));
    }

    public function test_ธงปิด_setup_messenger_profile_เหมือนเดิม(): void
    {
        $fb = $this->fb(false);

        $this->assertTrue($fb->setupMessengerProfile()['success']);

        $body = $this->sentBodies();
        $this->assertStringContainsString('MENU_DEEP_FORTUNE', $body);
        $this->assertStringContainsString('/auth/facebook', $body);
        $this->assertStringNotContainsString('WEB_ROOM', $body);
    }

    // ── 7. sendFortuneTelling ────────────────────────────────────

    private function ctaTemplate(): void
    {
        FortuneResponseTemplate::query()->delete();
        FortuneResponseTemplate::create([
            'name' => 'ทดสอบ',
            'slug' => 'test-basic',
            'type' => 'basic',
            'body' => "🔮 คำทำนาย\n\n{response}\n\n💡 ต้องการคำทำนายเชิงลึกละเอียดกว่านี้?\nพิมพ์: \"ดูดวงละเอียด\" เพื่อรับคำทำนายแบบเจาะลึก\nค่าครูเพียง {price} บาท",
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_ธงเปิด_send_fortune_telling_ยังไม่จ่าย_ไม่ส่งคำทำนายบน_fb(): void
    {
        $fb = $this->fb(true);
        $this->ctaTemplate();
        $reading = FortuneReading::create([
            'platform' => 'facebook',
            'facebook_user_id' => self::PSID,
            'questions' => ['ความรัก'],
            'is_paid' => false,
        ]);

        $this->assertFalse($fb->sendFortuneTelling($reading, 'ความรักช่วงนี้สดใสค่ะ'));
        Http::assertNothingSent();
    }

    public function test_ธงเปิด_send_fortune_telling_จ่ายแล้ว_ส่ง_dm_พร้อม_cta_เว็บ_ไม่ตอบใต้คอมเมนต์(): void
    {
        $fb = $this->fb(true, ['respond_in_comment' => true, 'deep_reading_price' => 39]);
        $this->ctaTemplate();
        $reading = FortuneReading::create([
            'platform' => 'facebook',
            'facebook_user_id' => self::PSID,
            'facebook_comment_id' => '111_222',
            'questions' => ['การเงิน'],
            'is_paid' => true,
        ]);

        $this->assertTrue($fb->sendFortuneTelling($reading, "การเงินดีขึ้นค่ะ\nจะมีคนโอนเงินมาคืน"));

        $body = $this->sentBodies();
        $this->assertStringContainsString(FacebookWebhookService::WEB_ONLY_PAID_CLOSING_CTA, $body);
        $this->assertStringContainsString('จะมีคนโอนเงินมาคืน', $body, 'เนื้อคำทำนายของ AI ห้ามถูกตัด');
        $this->assertStringNotContainsString('ดูดวงละเอียด', $body);
        $this->assertStringNotContainsString('ค่าครู', $body);
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '111_222/comments'));
    }

    public function test_ธงปิด_send_fortune_telling_เหมือนเดิม(): void
    {
        $fb = $this->fb(false);
        $this->ctaTemplate();
        $reading = FortuneReading::create([
            'platform' => 'facebook',
            'facebook_user_id' => self::PSID,
            'questions' => ['ความรัก'],
            'is_paid' => false,
        ]);

        $this->assertTrue($fb->sendFortuneTelling($reading, 'ความรักช่วงนี้สดใสค่ะ'));
        $this->assertStringContainsString('ดูดวงละเอียด', $this->sentBodies());
        $this->assertStringNotContainsString(FacebookWebhookService::WEB_ONLY_PAID_CLOSING_CTA, $this->sentBodies());
    }
}
