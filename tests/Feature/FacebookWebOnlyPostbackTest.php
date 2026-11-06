<?php

namespace Tests\Feature;

use App\Http\Controllers\FacebookWebhookController;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneUserCredit;
use App\Models\FortuneWebHandoff;
use App\Services\FacebookWebhookService;
use App\Services\Fortune\FortuneBotMode;
use App\Services\Fortune\FortunePageContext;
use App\Services\Fortune\WebHandoffCopy;
use App\Services\FortuneWebLinkService;
use App\Services\LineAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 🌐 (2026-11-03) T6 — ปุ่ม postback / quick reply ใต้ FB web-only
 *
 * ล็อกกติกา:
 *  1. ปุ่มนอก whitelist ทุกใบ (เริ่มดูดวง / ราคา / จ่ายเงิน / daily / เมนู / default passthrough)
 *     → กล่องห้องแม่หมอเท่านั้น ไม่เข้าโฟลดูดวง ไม่สร้างบิล
 *  2. INVITE_FREE_WEB ไม่มีทางถอยไป handleInviteReadNow (แม้สร้างลิงก์ไม่ได้)
 *  3. TRANSFER_STAY_* / TRANSFER_GO → พาไปเว็บ ไม่ให้สิทธิ์ดูในแชท
 *  4. ปุ่มปลอดภัย (พัก 7 วัน / เลิกรับ / ภาษา) ยังทำงาน
 *  5. ลูกค้าที่จ่ายแล้วกำลังรับของ (legacy paid) กดปุ่มในโฟลได้ต่อ
 *  6. ธงปิด / LINE = พฤติกรรมเดิมทุกปุ่ม
 */
class FacebookWebOnlyPostbackTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '61550000000066';

    private const LINE_UID = 'U0123456789abcdef0123456789abcdef';

    private FbWebOnlyRecordingSender $sender;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->sender = new FbWebOnlyRecordingSender;
    }

    protected function tearDown(): void
    {
        FortunePageContext::forget();
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    /**
     * ตั้งค่าแถวกลาง (ล้าง memo ก่อน/หลัง — แบบเดียวกับ FortuneTransferModeTest)
     */
    private function settings(array $override = []): FortuneTellingSetting
    {
        FortuneTellingSetting::clearSettingsCache();

        $settings = FortuneTellingSetting::getSettings();
        $settings->forceFill(array_merge([
            'fortune_bot_mode' => FortuneBotMode::MODE_CLASSIC,
            'facebook_web_only' => false,
            'enable_web_fortune_button' => false,
            // เทสต์กดปุ่มรัวเป็นร้อยครั้ง — ปิดด่านกดปุ่มรัวไว้ (มีเทสต์ของตัวเองแยก)
            'enable_nav_flood_guard' => false,
        ], $override))->save();

        FortuneTellingSetting::clearSettingsCache();

        return FortuneTellingSetting::getSettings();
    }

    private function webOnly(array $override = []): FortuneTellingSetting
    {
        return $this->settings(array_merge(['facebook_web_only' => true], $override));
    }

    private function controller(): FbWebOnlyProbeController
    {
        $c = new FbWebOnlyProbeController;
        $c->useSender($this->sender);

        return $c;
    }

    /**
     * ตรวจว่าได้ "กล่องห้องแม่หมอ" 1 กล่อง ชนิดข้อความตรง และไม่มีอะไรหลุดไปโฟลเดิม
     */
    private function assertHandoff(FbWebOnlyProbeController $c, string $kind, string $label): void
    {
        $this->assertSame([], $c->conversational, "{$label}: ห้ามเข้าโฟลดูดวงในแชท");
        $this->assertSame([], $c->legacy, "{$label}: ห้ามเรียก handler เดิม");
        $this->assertCount(1, $this->sender->templates, "{$label}: ต้องส่งกล่องห้องแม่หมอ 1 กล่อง "
            .json_encode($this->sender->messages, JSON_UNESCAPED_UNICODE));

        $box = $this->sender->templates[0]['payload'];
        $expectType = WebHandoffCopy::usesCard($kind) ? 'generic' : 'button';
        $this->assertSame($expectType, $box['attachment']['payload']['template_type'] ?? null, "{$label}: ชนิดกล่องไม่ตรง");
        $this->assertSame(WebHandoffCopy::boxText($kind), WebHandoffCopy::textOf($box), "{$label}: ชนิดข้อความไม่ตรง");
        $this->assertSame('web_url', WebHandoffCopy::buttonsOf($box)[0]['type'] ?? null);

        $json = json_encode($box, JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('TRANSFER_STAY_FB', $json);
        $this->assertStringNotContainsString('line.me', $json);
        $this->assertDoesNotMatchRegularExpression('/บาท|฿|โอน|สลิป|บัญชี|ค่าครู/u', $json, "{$label}: กล่องห้ามมีคำเรื่องเงิน");
    }

    private function resetProbe(FbWebOnlyProbeController $c): void
    {
        $c->conversational = [];
        $c->legacy = [];
        $c->readNowCalls = 0;
        $this->sender->reset();
    }

    // ============================================================
    // 1. ปุ่มนอก whitelist → กล่องห้องแม่หมอ
    // ============================================================

    public function test_ปุ่มเริ่มดูดวง_ราคา_จ่ายเงิน_daily_เมนู_และ_default_ได้กล่องห้องแม่หมอทุกใบ(): void
    {
        $this->webOnly(['fortune_bot_mode' => FortuneBotMode::MODE_TRANSFER]);
        $c = $this->controller();

        $reading = WebHandoffCopy::KIND_READING;
        $price = WebHandoffCopy::KIND_PRICE;
        $cases = [
            // ทุก payload ใน transferShouldRoute
            'FORTUNE_FREE' => $reading, 'FREE_CARD_START' => $reading,
            'TIER_DEEP_39' => $price, 'TIER_CELTIC_99' => $price, 'TIER_CELTIC_BLACKMAGIC' => $price,
            'FORTUNE_BASIC' => $reading, 'FORTUNE_DEEP' => $reading, 'START_FORTUNE' => $reading,
            'DEEP_FORTUNE' => $reading, 'NEW_FORTUNE' => $reading, 'DEEP_READING_ACCEPT' => $reading,
            'DEEP_WITH_BIRTHDATE' => $reading, 'INVITE_READ_NOW' => $reading,
            'FORTUNE_LOVE' => $reading, 'FORTUNE_WORK' => $reading, 'FORTUNE_MONEY' => $reading,
            'FORTUNE_HEALTH' => $reading, 'FORTUNE_OVERVIEW' => $reading,
            // จ่ายเงิน
            'PAY_METHOD_QR_THAI' => $price, 'PAY_METHOD_STRIPE' => $price, 'PAY_METHOD_STRIPE_FOREIGN' => $price,
            'STRIPE_OPEN_CHECKOUT' => $price, 'STRIPE_RESUME' => $price,
            'SHOW_BANK_ACCOUNT' => $price, 'REPORT_PAYMENT' => $price, 'FOREIGN_CONFIRM_PAY' => $price,
            'CANCEL_HELP_TRANSFER' => $price, 'CARD_CONFIRM_YES' => $price, 'DELIVERY_CONFIRM_YES' => $price,
            'PAY_LATER_ACK_YES' => $price, 'SUBSCRIBE' => $price, 'AFFILIATE_RECRUIT_YES' => $price,
            // daily
            'DAILY_BDAY_3' => $reading, 'DAILY_BDAY_7' => $reading, 'DAILY_SHOW_MINE' => $reading,
            'DAILY_FREE_START' => $reading, 'DAILY_VIP_PACKAGES' => $price,
            // เมนู
            'MENU_FORTUNE' => $reading, 'MENU_DEEP_FORTUNE' => $reading, 'MENU_CHECK_REMAINING' => $reading,
            'MENU_OPEN' => $reading, 'MENU_HELP' => $reading, 'MENU_REFERRAL' => $reading,
            'ICEBREAKER_REGISTER' => $reading, 'ICEBREAKER_REFERRAL' => $reading,
            // อื่น ๆ ที่เคยไปทำงานเอง
            'LINE_ADD_FRIEND' => $reading, 'FORTUNE_EARN_INFO' => $reading, 'SHARE_PAGE' => $reading,
            'CANCEL_PAYMENT' => $reading, 'FREE_CARD_DECLINE' => $reading, 'TALK_ADMIN' => $reading,
            // ปุ่มในโฟลจ่ายแล้ว แต่ลูกค้าไม่มีของจ่ายแล้ว = ปุ่มเก่าในประวัติแชท
            'CELTIC_READY' => $reading, 'READ_PREDICTION' => $reading, 'QUESTION_LOVE' => $reading,
            // default passthrough (รหัสปุ่มดิบที่ไม่รู้จัก)
            // 🔐 (รีวิว) payload ที่เป็นข้อความพิมพ์ (ไทย/ช่องว่าง) ไม่อยู่ที่นี่แล้ว — ไปเป็นข้อความผ่านด่านในแชท
            'SOMETHING_UNKNOWN_FROM_OLD_TEMPLATE' => $reading, 'OLD_TEMPLATE_X:99' => $reading,
        ];

        $readingsBefore = FortuneReading::count();

        foreach ($cases as $payload => $kind) {
            foreach (['postback', 'quick'] as $via) {
                $this->resetProbe($c);
                $via === 'postback' ? $c->postback(self::PSID, (string) $payload) : $c->quick(self::PSID, (string) $payload);

                $this->assertHandoff($c, $kind, "{$via}:{$payload}");
            }
        }

        $this->assertSame($readingsBefore, FortuneReading::count(), 'ห้ามมีบิล/reading ใหม่');
        $this->assertNull(Cache::get('fortune:force_tier:'.self::PSID), 'ห้ามตั้ง force_tier (ทางลัดออกบิล)');
        $this->assertSame(count($cases) * 2, FortuneWebHandoff::where('platform_user_id', self::PSID)->count(),
            'ทุกการกดต้องออก handoff ใหม่ (ลิงก์ใหม่ทุกครั้ง)');
    }

    public function test_ชิปที่_payload_เป็นข้อความพิมพ์_ไม่ถูกกลืน_ไปเป็นข้อความผ่านด่านในแชท(): void
    {
        $this->webOnly();
        $c = $this->controller();

        // ชิปยืนยัน PDPA (payload = ข้อความไทย) ต้องถึงโฟลข้อความ — สิทธิ์ลบข้อมูลยังทำบน FB ได้
        foreach (['ยืนยันลบข้อมูลถาวร', 'ไม่ลบข้อมูล', 'ดูดวง 99 บาท'] as $payload) {
            $this->resetProbe($c);
            $c->quick(self::PSID, $payload);

            $this->assertSame([$payload], $c->conversational, "{$payload}: ต้องส่งต่อเป็นข้อความ (ด่าน web-only ใน processMessage ตัดสินต่อ)");
            $this->assertSame([], $this->sender->templates, "{$payload}: ห้ามกลืนเป็นกล่องที่ชั้นปุ่ม");
        }

        // รหัสปุ่มราคา/จ่ายเงินยังโดนด่านปุ่มเหมือนเดิม
        $this->resetProbe($c);
        $c->quick(self::PSID, 'TIER_DEEP_39');
        $this->assertHandoff($c, WebHandoffCopy::KIND_PRICE, 'quick:TIER_DEEP_39');
    }

    public function test_ป้ายปุ่มที่ลูกค้าพิมพ์เองก็โดนด่านเดียวกัน(): void
    {
        $this->webOnly();
        $c = $this->controller();

        // ป้าย "รับดวงฟรีประจำวัน" → resolveQuickReplyPayloadFromTitle → handleQuickReply
        $title = (string) \App\Services\FortuneConversationService::dailyFreeStartQuickReply()['title'];
        $payload = $c->titleToPayload($title);
        $this->assertNotNull($payload);

        $c->quick(self::PSID, $payload);

        $this->assertHandoff($c, WebHandoffCopy::KIND_READING, 'title:'.$title);
    }

    // ============================================================
    // 2. ห้องแม่หมอ / ทางเดิมที่พาไปเว็บ / ทางถอย
    // ============================================================

    public function test_web_room_invite_free_web_transfer_go_และ_get_started_ได้ห้องแม่หมอ(): void
    {
        $this->webOnly();
        $c = $this->controller();

        foreach (['WEB_ROOM', 'INVITE_FREE_WEB', 'TRANSFER_GO', 'TRANSFER_GO_LINE', 'GET_STARTED'] as $payload) {
            foreach (['postback', 'quick'] as $via) {
                $this->resetProbe($c);
                $via === 'postback' ? $c->postback(self::PSID, $payload) : $c->quick(self::PSID, $payload);

                $this->assertHandoff($c, WebHandoffCopy::KIND_ROOM, "{$via}:{$payload}");
                $this->assertSame(0, $c->readNowCalls, "{$payload}: ห้ามตกไป handleInviteReadNow");
            }
        }

        // ❓ เข้ายังไงคะ → คำอธิบาย 2 บรรทัด + ลิงก์ใหม่
        $this->resetProbe($c);
        $c->postback(self::PSID, WebHandoffCopy::PAYLOAD_WEB_ROOM_HOWTO);
        $this->assertHandoff($c, WebHandoffCopy::KIND_HOWTO, 'WEB_ROOM_HOWTO');
    }

    public function test_transfer_stay_ทั้งสองปุ่มพาไปเว็บ_ไม่ให้สิทธิ์ดูในแชท(): void
    {
        $this->webOnly(['fortune_bot_mode' => FortuneBotMode::MODE_TRANSFER]);
        $c = $this->controller();

        foreach (['TRANSFER_STAY_FB', 'TRANSFER_STAY_FB_CONFIRM'] as $payload) {
            foreach (['postback', 'quick'] as $via) {
                $this->resetProbe($c);
                $via === 'postback' ? $c->postback(self::PSID, $payload) : $c->quick(self::PSID, $payload);

                $this->assertHandoff($c, WebHandoffCopy::KIND_CANNOT, "{$via}:{$payload}");
            }
        }

        $credit = FortuneUserCredit::where('facebook_user_id', self::PSID)->first();
        $this->assertEmpty($credit?->fb_fallback_granted_at, 'ห้ามบันทึกสิทธิ์ดูในแชท FB');

        // ชั้นสำรอง: เรียก transferGrantStayOnFb ตรง ๆ ก็ต้องไปเว็บ
        $this->resetProbe($c);
        $c->callProtected('transferGrantStayOnFb', self::PSID);
        $this->assertHandoff($c, WebHandoffCopy::KIND_CANNOT, 'transferGrantStayOnFb');
    }

    public function test_invite_free_web_สร้างลิงก์ไม่ได้ก็ไม่ตกไป_read_now_แต่ส่ง_fail_closed(): void
    {
        $this->webOnly();
        $this->mock(FortuneWebLinkService::class, function ($m) {
            $m->shouldReceive('isEnabledFor')->andReturn(true);
            $m->shouldReceive('isEnabled')->andReturn(true);
            $m->shouldReceive('generateChatLink')->andThrow(new \RuntimeException('APP_KEY หาย'));
        });
        $this->mock(LineAlertService::class, function ($m) {
            $m->shouldReceive('alertSystemError')->atLeast()->once();
        });

        $c = $this->controller();
        $c->quick(self::PSID, 'INVITE_FREE_WEB');

        $this->assertSame(0, $c->readNowCalls, 'ห้ามตกไป handleInviteReadNow');
        $this->assertSame([], $c->conversational);
        $this->assertCount(1, $this->sender->templates);
        $sent = $this->sender->templates[0]['payload'];
        $this->assertSame(WebHandoffCopy::FAIL_CLOSED, $sent['text']);
        $this->assertSame([WebHandoffCopy::PAYLOAD_WEB_ROOM], array_column($sent['quick_replies'], 'payload'));
        $this->assertSame(0, FortuneReading::count());

        // เรียก handleInviteFreeWeb ตรง ๆ ก็ต้องไม่มีทางถอย
        $this->resetProbe($c);
        $c->callProtected('handleInviteFreeWeb', self::PSID);
        $this->assertSame(0, $c->readNowCalls);
        $this->assertSame([], $c->conversational);
    }

    public function test_conversation_service_พัง_ส่งข้อความ_fail_closed_ไม่ออกบิล(): void
    {
        $this->webOnly();
        $c = $this->controller();
        $c->breakConversationService();

        $c->postback(self::PSID, 'TIER_CELTIC_99');

        $this->assertSame([], $c->conversational);
        $this->assertCount(1, $this->sender->templates);
        $this->assertSame(WebHandoffCopy::FAIL_CLOSED, $this->sender->templates[0]['payload']['text']);
        $this->assertSame(0, FortuneReading::count());
    }

    public function test_transfer_should_route_ปิดใต้_web_only(): void
    {
        $this->webOnly(['fortune_bot_mode' => FortuneBotMode::MODE_TRANSFER]);
        $c = $this->controller();

        $this->assertFalse($c->callProtected('transferShouldRoute', self::PSID, 'TIER_CELTIC_99'));
        $this->assertFalse($c->callProtected('transferShouldRoute', self::PSID, 'FORTUNE_FREE'));
    }

    // ============================================================
    // 3. whitelist
    // ============================================================

    public function test_ปุ่มปลอดภัยยังทำงานตามเดิม(): void
    {
        $this->webOnly();
        $c = $this->controller();

        $c->quick(self::PSID, 'INVITE_SNOOZE_7D');
        $this->assertSame([], $this->sender->templates, 'พัก 7 วันต้องไม่ส่งกล่อง');
        $this->assertCount(1, $this->sender->messages);
        $this->assertStringContainsString('7 วัน', $this->sender->messages[0]['text']);

        $this->resetProbe($c);
        $c->quick(self::PSID, 'INVITE_OPTOUT');
        $this->assertSame([], $this->sender->templates);
        $this->assertCount(1, $this->sender->messages);

        $this->resetProbe($c);
        $c->postback(self::PSID, 'LANG_TH');
        $this->assertSame([], $this->sender->templates);
        $this->assertStringContainsString('ภาษาไทย', $this->sender->messages[0]['text'] ?? '');

        $this->assertSame(0, FortuneWebHandoff::count(), 'ปุ่มปลอดภัยไม่ต้องออก handoff');
    }

    public function test_ลูกค้าที่จ่ายแล้วกำลังรับของ_กดปุ่มในโฟลได้ต่อ(): void
    {
        $this->webOnly();
        FortuneReading::create([
            'platform' => 'facebook',
            'facebook_user_id' => self::PSID,
            'facebook_user_name' => 'ลูกค้า FB',
            'questions' => 'การงานจะเป็นอย่างไร',
            'is_paid' => true,
            'conversation_status' => FortuneReading::STATUS_CELTIC_PICKING,
        ]);
        $this->assertTrue((new FortuneBotMode)->fbLegacyPaidInFlight(self::PSID));

        $c = $this->controller();

        $c->postback(self::PSID, 'CELTIC_READY');
        $this->assertSame(['พร้อม'], $c->conversational, 'legacy paid ต้องได้ของต่อ');
        $this->assertSame([], $this->sender->templates);

        $this->resetProbe($c);
        $c->postback(self::PSID, 'READ_PREDICTION');
        $this->assertSame(['อ่านคำทำนาย'], $c->conversational);

        // แต่ปุ่มเริ่มรอบใหม่/ราคา ยังต้องไปห้องแม่หมอแม้เป็นลูกค้าจ่ายแล้ว
        $this->resetProbe($c);
        $c->quick(self::PSID, 'TIER_CELTIC_99');
        $this->assertHandoff($c, WebHandoffCopy::KIND_PRICE, 'legacy:TIER_CELTIC_99');
    }

    // ============================================================
    // 4. ธงปิด / LINE = เหมือนเดิม
    // ============================================================

    public function test_ธงปิด_ปุ่มทำงานแบบเดิมทุกใบ(): void
    {
        $this->settings();
        $c = $this->controller();

        $c->quick(self::PSID, 'TIER_CELTIC_99');
        $this->assertSame(['ดูดวง'], $c->conversational);
        $this->assertSame('celtic', Cache::get('fortune:force_tier:'.self::PSID));
        $this->assertSame([], $this->sender->templates);

        $this->resetProbe($c);
        $c->postback(self::PSID, 'MENU_FORTUNE');
        $this->assertSame(['ดูดวง'], $c->conversational);

        // INVITE_FREE_WEB เมื่อปิดสวิตช์ลิงก์ → ทางเดิม (ไปโฟลดูดวงในแชท)
        $this->resetProbe($c);
        $c->quick(self::PSID, 'INVITE_FREE_WEB');
        $this->assertSame(1, $c->readNowCalls);

        // ปุ่ม WEB_ROOM ที่ค้างในแชทหลังปิดธง → ไม่ส่ง payload ดิบเข้า AI
        $this->resetProbe($c);
        $c->quick(self::PSID, 'WEB_ROOM');
        $this->assertNotContains('WEB_ROOM', $c->conversational);
        $this->assertSame(1, $c->readNowCalls);

        $this->assertSame(0, FortuneWebHandoff::count());
    }

    public function test_ธงเปิด_แต่_id_เป็น_line_ไม่โดนด่าน(): void
    {
        $this->webOnly();
        $c = $this->controller();

        $c->quick(self::LINE_UID, 'TIER_CELTIC_99');

        $this->assertSame(['ดูดวง'], $c->conversational);
        $this->assertSame([], $this->sender->templates);
        $this->assertSame(0, FortuneWebHandoff::count());
    }

    // ============================================================
    // 5. (T7) รูปภาพ / สลิป ระดับ A/B/C
    // ============================================================

    private const IMG_URL = 'https://scontent.fbweb.test/v/slip-123.jpg';

    /** ปลอม classifier ให้ตอบ intent ที่กำหนด (หรือ throw) */
    private function fakeClassifier(?string $intent, bool $throws = false): void
    {
        $this->mock(\App\Services\Fortune\ImageIntentClassifier::class, function ($m) use ($intent, $throws) {
            if ($throws) {
                $m->shouldReceive('classify')->andThrow(new \RuntimeException('vision down'));

                return;
            }
            $m->shouldReceive('classify')->andReturn([
                'intent' => $intent, 'confidence' => 0.95, 'reason' => 'test', 'raw_response' => null,
            ]);
        });
    }

    /** ปลอม CDN รูป + อะไรก็ตามที่ไม่ใช่ CDN = 500 (จับว่ามีการยิงออกไปที่อื่นไหม เช่น SlipOK) */
    private function fakeHttp(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'scontent.fbweb.test/*' => \Illuminate\Support\Facades\Http::response('FAKE-JPEG-BYTES', 200),
            '*' => \Illuminate\Support\Facades\Http::response('', 500),
        ]);
    }

    /** request ที่ยิงออกไปนอกจากโหลดรูปจาก CDN */
    private function nonCdnRequests(): array
    {
        return \Illuminate\Support\Facades\Http::recorded(
            fn ($request) => ! str_contains($request->url(), 'scontent.fbweb.test')
        )->all();
    }

    private function imageEvent(string $psid, string $mid): array
    {
        return [
            'sender' => ['id' => $psid],
            'message' => [
                'mid' => $mid,
                'attachments' => [['type' => 'image', 'payload' => ['url' => self::IMG_URL]]],
            ],
        ];
    }

    private function boxText(int $i = 0): ?string
    {
        return isset($this->sender->templates[$i]) ? WebHandoffCopy::textOf($this->sender->templates[$i]['payload']) : null;
    }

    public function test_รูประดับ_c_คล้ายสลิป_ไม่เรียก_slip_o_k_เก็บหลักฐาน_แจ้งแอดมิน_และส่งกล่อง(): void
    {
        $this->webOnly();
        \Illuminate\Support\Facades\Queue::fake();
        \Illuminate\Support\Facades\Storage::fake('local');
        $this->fakeHttp();
        $this->fakeClassifier(\App\Services\Fortune\ImageIntentClassifier::INTENT_PAYMENT_SLIP);
        $this->mock(LineAlertService::class, fn ($m) => $m->shouldReceive('alertUnusualActivity')->once());

        $c = $this->controller();
        $c->callProtected('processMessage', $this->imageEvent(self::PSID, 'm_t7_c_slip'));

        // ไม่มีการเรียก SlipOK (ไม่มี request ออกนอก CDN และไม่มีนัด job ตรวจสลิป)
        $this->assertSame([], $this->nonCdnRequests(), 'ระดับ C ห้ามยิง SlipOK/API อื่น');
        \Illuminate\Support\Facades\Queue::assertNotPushed(\App\Jobs\VerifySlipFallbackJob::class);

        // เก็บหลักฐาน + คิวแอดมิน
        $files = \Illuminate\Support\Facades\Storage::disk('local')->files('fortune/slips');
        $this->assertCount(1, $files, 'ต้องเก็บรูปเป็นหลักฐาน 1 ไฟล์');
        $this->assertStringContainsString('fbweb_', $files[0]);
        $queued = \App\Models\FortuneSavedQuestion::where('platform_user_id', self::PSID)->where('reason', 'fb_web_only_slip')->first();
        $this->assertNotNull($queued, 'ต้องเข้าคิวแอดมิน');
        $this->assertStringContainsString($files[0], $queued->question);

        // ตอบด้วยกล่อง "ส่งเรื่องให้ทีมแล้ว" 1 กล่อง ไม่มีบิล
        $this->assertCount(1, $this->sender->templates);
        $this->assertSame(WebHandoffCopy::reply(WebHandoffCopy::KIND_SLIP_FORWARDED), $this->boxText());
        $this->assertSame([], $c->conversational);
        $this->assertSame(0, FortuneReading::count());
    }

    public function test_รูประดับ_c_classifierล่ม_ถือว่าอาจเป็นสลิป_เก็บหลักฐานไว้ก่อน(): void
    {
        $this->webOnly();
        \Illuminate\Support\Facades\Queue::fake();
        \Illuminate\Support\Facades\Storage::fake('local');
        $this->fakeHttp();
        $this->fakeClassifier(null, true);
        $this->mock(LineAlertService::class, fn ($m) => $m->shouldReceive('alertUnusualActivity')->once());

        $c = $this->controller();
        $c->callProtected('handleSlipImageOnly', self::PSID, self::IMG_URL, false);

        $this->assertCount(1, \Illuminate\Support\Facades\Storage::disk('local')->files('fortune/slips'));
        $this->assertSame(WebHandoffCopy::reply(WebHandoffCopy::KIND_SLIP_FORWARDED), $this->boxText());
        \Illuminate\Support\Facades\Queue::assertNotPushed(\App\Jobs\VerifySlipFallbackJob::class);
    }

    public function test_รูประดับ_c_ไม่ใช่สลิป_ได้กล่องห้องแม่หมอ_ส่งรัวได้กล่องเดียว(): void
    {
        $this->webOnly();
        \Illuminate\Support\Facades\Storage::fake('local');
        $this->fakeHttp();
        $this->fakeClassifier(\App\Services\Fortune\ImageIntentClassifier::INTENT_GENERAL_PHOTO);
        $this->mock(LineAlertService::class, fn ($m) => $m->shouldNotReceive('alertUnusualActivity'));

        $c = $this->controller();
        $c->callProtected('handleSlipImageOnly', self::PSID, self::IMG_URL, false);
        $c->callProtected('handleSlipImageOnly', self::PSID, self::IMG_URL, false);

        $this->assertCount(1, $this->sender->templates, 'รูปติดกันต้องได้กล่องเดียว');
        $this->assertSame(WebHandoffCopy::boxText(WebHandoffCopy::KIND_ROOM), $this->boxText());
        $this->assertSame([], \Illuminate\Support\Facades\Storage::disk('local')->files('fortune/slips'), 'รูปทั่วไปไม่เก็บ');
        $this->assertSame(0, \App\Models\FortuneSavedQuestion::count());
    }

    public function test_สติกเกอร์ใต้web_only_ทักกลับด้วยกล่องห้องแม่หมอ_ไม่มีการ์ดสินค้า(): void
    {
        $this->webOnly();
        $this->fakeHttp();

        $c = $this->controller();
        $c->callProtected('processMessage', [
            'sender' => ['id' => self::PSID],
            'message' => [
                'mid' => 'm_t7_sticker',
                'attachments' => [['type' => 'image', 'payload' => ['url' => 'https://scontent.fbweb.test/st.png', 'sticker_id' => 369239263222822]]],
            ],
        ]);

        $this->assertCount(1, $this->sender->templates, json_encode($this->sender->messages, JSON_UNESCAPED_UNICODE));
        $this->assertSame(WebHandoffCopy::boxText(WebHandoffCopy::KIND_ROOM), $this->boxText());
        $this->assertSame([], $this->sender->images, 'ห้ามยื่นการ์ดสินค้า/แบนเนอร์');
        foreach ($this->sender->messages as $m) {
            $this->assertNotContains('MENU_FORTUNE', array_column($m['quick_replies'] ?? [], 'payload'));
        }
    }

    public function test_รูประดับ_b_บิลค้างก่อนสลับธง_ตอบสั้น_แจ้งแอดมิน_ไม่ออกบิลใหม่(): void
    {
        $this->webOnly();
        \Illuminate\Support\Facades\Storage::fake('local');
        $this->fakeHttp();
        $this->mock(LineAlertService::class, fn ($m) => $m->shouldReceive('alertUnusualActivity')->once());

        $bill = FortuneReading::create([
            'platform' => 'facebook',
            'facebook_user_id' => self::PSID,
            'facebook_user_name' => 'ลูกค้า FB',
            'questions' => 'การงาน',
            'is_paid' => false,
            'conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT,
        ]);
        $this->assertTrue((new FortuneBotMode)->fbPendingBillOpen(self::PSID));

        $c = $this->controller();
        $c->callProtected('processMessage', $this->imageEvent(self::PSID, 'm_t7_b_slip'));

        $this->assertSame([], $this->sender->templates, 'ระดับ B ไม่ต้องส่งกล่อง (ยังรอตรวจบิลเดิม)');
        $texts = array_column($this->sender->messages, 'text');
        $this->assertContains(WebHandoffCopy::SLIP_RECEIVED, $texts, json_encode($texts, JSON_UNESCAPED_UNICODE));
        foreach ($this->sender->messages as $m) {
            $this->assertDoesNotMatchRegularExpression('/บาท|฿|บัญชี|แจ้งชำระ|QR/u', $m['text']);
            $this->assertNotContains('REPORT_PAYMENT', array_column($m['quick_replies'] ?? [], 'payload'));
        }

        $this->assertSame(1, FortuneReading::count(), 'ห้ามสร้างบิลใหม่');
        $this->assertSame(FortuneReading::STATUS_PENDING_PAYMENT, $bill->fresh()->conversation_status);
        $this->assertTrue(\App\Models\FortuneSavedQuestion::where('reason', 'fb_web_only_slip')->exists(), 'ต้องเข้าคิวแอดมิน');
    }

    public function test_ธงปิด_สลิปบิลค้าง_ยังตอบแบบเดิม(): void
    {
        $this->settings();
        \Illuminate\Support\Facades\Storage::fake('local');
        $this->fakeHttp();

        FortuneReading::create([
            'platform' => 'facebook',
            'facebook_user_id' => self::PSID,
            'questions' => 'การงาน',
            'is_paid' => false,
            'conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT,
            'bill_reference' => 'FTU-T7-0001',
        ]);

        $c = $this->controller();
        $c->callProtected('handleSlipImageOnly', self::PSID, self::IMG_URL, false);

        $this->assertSame([], $this->sender->templates);
        $this->assertCount(1, $this->sender->messages);
        $this->assertStringContainsString('FTU-T7-0001', $this->sender->messages[0]['text']);
        $this->assertContains('REPORT_PAYMENT', array_column($this->sender->messages[0]['options']['quick_replies'] ?? [], 'payload'));
        $this->assertSame(0, \App\Models\FortuneSavedQuestion::count());
    }

    // ============================================================
    // 6. (T7) คอมเมนต์ / รีแอคชัน / DM ชวน
    // ============================================================

    /** ปิดข้อความชวนทุกแถว (migration ของ T11 seed ชุด web มาให้แล้ว) */
    private function clearInvites(): void
    {
        \App\Models\FortuneInviteMessage::query()->update(['is_active' => false]);
    }

    private function seedWebInvite(string $text = 'แม่หมอเปิดห้องรอไว้แล้วนะคะ{name} 🌙 แวะมานั่งคุยกันได้เลยค่ะ'): \App\Models\FortuneInviteMessage
    {
        $this->clearInvites();

        return \App\Models\FortuneInviteMessage::create([
            'message' => $text,
            'category' => 'general',
            'mode' => \App\Models\FortuneInviteMessage::MODE_WEB,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function comment(string $message, string $commentId = '111_222'): array
    {
        return [
            'item' => 'comment',
            'verb' => 'add',
            'message' => $message,
            'from' => ['id' => self::PSID, 'name' => 'สมศรี'],
            'comment_id' => $commentId,
            'post_id' => '111',
        ];
    }

    private function assertWebInviteDm(array $dm, string $label): void
    {
        $this->assertSame(
            [WebHandoffCopy::PAYLOAD_WEB_ROOM, WebHandoffCopy::PAYLOAD_SNOOZE, WebHandoffCopy::PAYLOAD_OPTOUT],
            array_column($dm['quick_replies'] ?? [], 'payload'),
            "{$label}: ปุ่มต้องเป็นชุดห้องแม่หมอ"
        );
        $this->assertDoesNotMatchRegularExpression(WebHandoffCopy::FORBIDDEN_REGEX, $dm['text'], "{$label}: ห้ามพูดเรื่องเงิน/ฟรี/LINE");
        $this->assertDoesNotMatchRegularExpression('#https?://#', $dm['text'], "{$label}: ห้ามมี URL ดิบ");
    }

    public function test_คอมเมนต์ดูดวง_ไม่dispatch_process_fortune_telling_ส่ง_private_replyชวนเข้าห้อง(): void
    {
        $this->webOnly(['enable_public_comment_reply' => true]);
        config(['queue.default' => 'database']);
        \Illuminate\Support\Facades\Queue::fake();

        $this->clearInvites();
        $c = $this->controller();
        // ชุด web ว่าง แต่ลูกค้าขอเอง → ใช้ถ้อยคำกลาง (ต้องตอบ)
        $c->callProtected('processComment', $this->comment('ดูดวง ความรักปีนี้จะเป็นยังไง'));

        \Illuminate\Support\Facades\Queue::assertNotPushed(\App\Jobs\ProcessFortuneTelling::class);
        $this->assertSame(0, FortuneReading::count());
        $this->assertCount(1, $this->sender->messages);
        $dm = $this->sender->messages[0];
        $this->assertSame('111_222', $dm['options']['comment_id'] ?? null, 'ต้องเป็น Private Reply ผูกคอมเมนต์');
        $this->assertWebInviteDm($dm, 'comment:explicit');
        $this->assertSame([['comment_id' => '111_222', 'text' => WebHandoffCopy::COMMENT_REPLY]], $this->sender->commentReplies);
        $this->assertSame([], $this->sender->templates, 'ห้ามสร้างลิงก์จาก comment.from.id');

        // webhook retry คอมเมนต์เดิม → ไม่ส่งซ้ำ
        $this->resetProbe($c);
        $c->callProtected('processComment', $this->comment('ดูดวง ความรักปีนี้จะเป็นยังไง'));
        $this->assertSame([], $this->sender->messages);
        $this->assertSame([], $this->sender->commentReplies);
    }

    public function test_ธงปิด_คอมเมนต์ดูดวง_ยัง_dispatch_เหมือนเดิม(): void
    {
        $this->settings();
        config(['queue.default' => 'database']);
        \Illuminate\Support\Facades\Queue::fake();

        $this->controller()->callProtected('processComment', $this->comment('ดูดวง ความรักปีนี้จะเป็นยังไง'));

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ProcessFortuneTelling::class);
    }

    public function test_คอมเมนต์เรื่องเงิน_ไม่ชวนaffiliate_ได้ข้อความชวนชุดweb(): void
    {
        $this->webOnly(['comment_engagement_enabled' => true, 'enable_public_comment_reply' => true]);
        $invite = $this->seedWebInvite();

        $c = $this->controller();
        $c->callProtected('handleCommentEngagement', $this->comment('ช่วงนี้ไม่มีเงิน ตกงานด้วย', '333_444'));

        $all = json_encode($this->sender->messages, JSON_UNESCAPED_UNICODE)
            .json_encode($this->sender->commentReplies, JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('AFFILIATE_RECRUIT_YES', $all);
        $this->assertStringNotContainsString('เรื่องเงินมาถูกทาง', $all);

        $this->assertCount(1, $this->sender->messages, $all);
        $this->assertWebInviteDm($this->sender->messages[0], 'comment:money');
        $this->assertStringContainsString('แม่หมอเปิดห้องรอไว้แล้ว', $this->sender->messages[0]['text']);
        $this->assertSame(WebHandoffCopy::COMMENT_REPLY, $this->sender->commentReplies[0]['text'] ?? null);
        $this->assertSame(1, (int) $invite->fresh()->send_count);
    }

    public function test_ชุดwebว่าง_engagementไม่_d_m_และไม่ตอบว่าทักไปinboxแล้ว(): void
    {
        $this->webOnly(['comment_engagement_enabled' => true, 'enable_public_comment_reply' => true]);
        $this->clearInvites();

        $c = $this->controller();
        $c->callProtected('handleCommentEngagement', $this->comment('สวยมากค่ะ', '555_666'));

        $this->assertSame([], $this->sender->messages, 'ชุด web ว่าง = ไม่ส่ง (ห้ามตกไปชุดเดิม)');
        $this->assertSame([], $this->sender->commentReplies);
    }

    public function test_รีแอคชัน_d_m_ใช้ชุดweb_ไม่มีกล่องดวงรายวัน_ไม่มีแบนเนอร์(): void
    {
        $this->webOnly(['comment_engagement_enabled' => true]);
        $this->seedWebInvite();
        Cache::put('fb_last_inbound:'.self::PSID, true, now()->addHours(24));

        $c = $this->controller();
        $c->callProtected('processReaction', [
            'from' => ['id' => self::PSID, 'name' => 'สมศรี ใจดี'],
            'post_id' => '111_999',
            'reaction_type' => 'like',
            'verb' => 'add',
        ]);

        $this->assertCount(1, $this->sender->messages, json_encode($this->sender->messages, JSON_UNESCAPED_UNICODE));
        $this->assertWebInviteDm($this->sender->messages[0], 'reaction');
        $this->assertSame([], $this->sender->images);
        $this->assertTrue((bool) \App\Models\FortunePostReaction::where('facebook_user_id', self::PSID)->value('dm_success'));
    }

    public function test_daily_mode_dm_override_ใต้web_only_ได้ปุ่มห้องแม่หมอ(): void
    {
        $this->webOnly(['fortune_bot_mode' => FortuneBotMode::MODE_DAILY]);

        $out = $this->controller()->callProtected('dailyModeDmOverride', self::PSID, 'สมศรี');

        $this->assertNull($out['message']);
        $this->assertSame(WebHandoffCopy::inviteQuickReplies(), $out['quick_replies']);
    }

    // ============================================================
    // 7. (T7) เมนู / ต้อนรับ / referral
    // ============================================================

    public function test_เมนูรู้จักเรา_ไม่มีราคา_มีปุ่มห้องแม่หมอ(): void
    {
        $this->webOnly(['enable_celtic_cross' => true]);
        $c = $this->controller();

        $c->postback(self::PSID, 'MENU_ABOUT_US');

        $this->assertCount(1, $this->sender->templates);
        $json = json_encode($this->sender->templates[0]['payload'], JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('"payload":"WEB_ROOM"', $json);
        $this->assertStringNotContainsString('TIER_', $json);
        $this->assertStringNotContainsString('MENU_REFERRAL', $json);
        $this->assertDoesNotMatchRegularExpression('/บาท|฿/u', $json);
    }

    public function test_ต้อนรับ_สมัคร_ชวนเพื่อน_ใต้web_only_ได้กล่องห้องแม่หมอ(): void
    {
        $this->webOnly();
        $c = $this->controller();

        foreach (['handleGetStarted', 'handleRegisterMenu', 'handleReferralMenu', 'sendFacebookAffiliateInvite'] as $method) {
            $this->resetProbe($c);
            $c->callParent($method, self::PSID);

            $this->assertCount(1, $this->sender->templates, "{$method}: ต้องได้กล่อง 1 กล่อง");
            $this->assertSame(WebHandoffCopy::boxText(WebHandoffCopy::KIND_ROOM), $this->boxText(), $method);
            $json = json_encode($this->sender->templates[0]['payload'], JSON_UNESCAPED_UNICODE);
            $this->assertStringNotContainsString('/auth/facebook', $json, "{$method}: ห้ามพาไปสมัครแยกบัญชี");
            $this->assertSame([], $this->sender->messages, "{$method}: ห้ามมีข้อความอื่น");
        }
    }

    public function test_referral_แคมเปญ_ไม่claim_ไม่ทำนายฟรี_ส่งกล่องห้องแม่หมอ(): void
    {
        $this->webOnly();
        $c = $this->controller();
        $ref = 'MONTHLY_CLAIM_'.now()->format('Y-m');

        $c->callProtected('processMessengerReferral', ['sender' => ['id' => self::PSID], 'referral' => ['ref' => $ref]], $ref);

        $this->assertSame([], $c->conversational, 'ห้ามทำนายฟรีในแชท');
        $this->assertCount(1, $this->sender->templates);
        $this->assertSame(WebHandoffCopy::boxText(WebHandoffCopy::KIND_ROOM), $this->boxText());

        // มากับ GET_STARTED postback → ด่านปุ่มส่งกล่องแล้ว ไม่ส่งซ้ำ
        $this->resetProbe($c);
        $c->callProtected('processMessengerReferral', [
            'sender' => ['id' => self::PSID],
            'postback' => ['payload' => 'GET_STARTED', 'referral' => ['ref' => $ref]],
        ], $ref);
        $this->assertSame([], $this->sender->templates);
        $this->assertSame([], $c->conversational);
    }
}

/**
 * ตัวส่งปลอม — จดทุกอย่างที่จะยิงไป Graph API (ไม่ยิงจริง)
 */
class FbWebOnlyRecordingSender extends FacebookWebhookService
{
    public array $templates = [];

    public array $messages = [];

    public function __construct()
    {
        // จงใจไม่เรียก parent — ไม่ต้องการ token/HTTP client
    }

    public function reset(): void
    {
        $this->templates = [];
        $this->messages = [];
        $this->commentReplies = [];
        $this->images = [];
    }

    public function sendButtonTemplate(string $recipientId, array $templatePayload, array $options = []): bool
    {
        $this->templates[] = ['to' => $recipientId, 'payload' => $templatePayload];

        return true;
    }

    public function sendMessage(string $recipientId, string $message, array $options = []): bool
    {
        $this->messages[] = ['to' => $recipientId, 'text' => $message, 'options' => $options];

        return true;
    }

    public function sendQuickReplies(string $recipientId, string $message, array $quickReplies, array $options = []): bool
    {
        $this->messages[] = ['to' => $recipientId, 'text' => $message, 'quick_replies' => $quickReplies, 'options' => $options];

        return true;
    }

    /** @var array<int, array{comment_id:string, text:string}> ตอบใต้คอมเมนต์สาธารณะ (T7) */
    public array $commentReplies = [];

    /** @var string[] รูปที่ส่ง (แบนเนอร์/การ์ด) (T7) */
    public array $images = [];

    public function replyToComment(string $commentId, string $message): bool
    {
        $this->commentReplies[] = ['comment_id' => $commentId, 'text' => $message];

        return true;
    }

    public function reactToComment(string $commentId, string $type = 'LOVE'): bool
    {
        return true;
    }

    public function sendImage(string $recipientId, string $imageUrl, ?string $previewUrl = null, array $options = []): bool
    {
        $this->images[] = $imageUrl;

        return true;
    }

    public function checkFreeLimit(string $facebookUserId): array
    {
        return ['has_reached_limit' => false, 'remaining' => 1];
    }

    public function checkDeepFreeLimit(string $facebookUserId): array
    {
        return ['has_reached_limit' => false, 'remaining' => 1];
    }

    public function sendTypingIndicator(string $recipientId, bool $on = true): void {}

    public function getUserProfile(string $facebookUserId): ?array
    {
        return null;
    }
}

/**
 * controller ทดสอบ — จดว่าปุ่มไหลไปที่ handler ไหน (ไม่รันโฟลดูดวงจริง)
 */
class FbWebOnlyProbeController extends FacebookWebhookController
{
    /** @var string[] ข้อความที่ถูกส่งเข้าโฟลดูดวงในแชท */
    public array $conversational = [];

    /** @var string[] handler เดิมที่ถูกเรียก */
    public array $legacy = [];

    public int $readNowCalls = 0;

    public function useSender(FacebookWebhookService $sender): void
    {
        $this->facebookService = $sender;
    }

    public function breakConversationService(): void
    {
        $this->conversationService = null;
    }

    public function postback(string $psid, string $payload): void
    {
        $this->processPostback([
            'sender' => ['id' => $psid],
            'postback' => ['payload' => $payload, 'mid' => 'm_'.bin2hex(random_bytes(6))],
        ]);
    }

    public function quick(string $psid, string $payload): void
    {
        $this->handleQuickReply($psid, $payload);
    }

    public function titleToPayload(string $title): ?string
    {
        return $this->resolveQuickReplyPayloadFromTitle($title);
    }

    public function callProtected(string $method, ...$args): mixed
    {
        return $this->{$method}(...$args);
    }

    /** เรียก handler ตัวจริงของ controller (ข้าม stub ด้านล่าง) — ใช้กับเมนูที่ probe จดไว้เฉย ๆ */
    public function callParent(string $method, ...$args): mixed
    {
        return parent::$method(...$args);
    }

    protected function processConversationalMessage(string $senderId, string $messageText): void
    {
        $this->conversational[] = $messageText;
    }

    protected function handleInviteReadNow(string $senderId): void
    {
        $this->readNowCalls++;
        parent::handleInviteReadNow($senderId);
    }

    protected function handleGetStarted(string $senderId): void
    {
        $this->legacy[] = 'handleGetStarted';
    }

    protected function handleFortuneFreePicker(string $senderId): void
    {
        $this->legacy[] = 'handleFortuneFreePicker';
    }

    protected function handleDailyBirthdayPick(string $senderId, string $payload): void
    {
        $this->legacy[] = 'handleDailyBirthdayPick';
    }

    protected function handleDailyShowMine(string $senderId, ?string $askBirthdayMessage = null): void
    {
        $this->legacy[] = 'handleDailyShowMine';
    }

    protected function handleDailyFreeStart(string $senderId): void
    {
        $this->legacy[] = 'handleDailyFreeStart';
    }

    protected function handleAffiliateRecruitYes(string $senderId): void
    {
        $this->legacy[] = 'handleAffiliateRecruitYes';
    }

    protected function handleLineAddFriend(string $senderId): void
    {
        $this->legacy[] = 'handleLineAddFriend';
    }

    protected function handleReferralMenu(string $senderId): void
    {
        $this->legacy[] = 'handleReferralMenu';
    }

    protected function handleRegisterMenu(string $senderId): void
    {
        $this->legacy[] = 'handleRegisterMenu';
    }

    protected function handleFortuneEarnInfo(string $senderId): void
    {
        $this->legacy[] = 'handleFortuneEarnInfo';
    }

    protected function handleSharePage(string $senderId): void
    {
        $this->legacy[] = 'handleSharePage';
    }

    protected function sendHelpMessage(string $userId): void
    {
        $this->legacy[] = 'sendHelpMessage';
    }
}
