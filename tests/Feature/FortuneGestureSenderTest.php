<?php

namespace Tests\Feature;

use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\Fortune\FortuneGestureSender;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

/**
 * 👉 แม่หมอส่งสติกเกอร์ / กดหัวใจ บน Facebook (2026-09-27)
 *
 * เจ้าของ: "ใช้รูปมือ แทนการใช้คำพูดเร่งให้เลือกแพคเกจ หรืออีโมชั่นอื่นๆ ของเฟชบุ๊ค เพื่อให้เหมือนคน"
 *
 * ล็อกไว้ 4 เรื่อง:
 *   1. ตัดเฉพาะบรรทัดเร่ง — คำตอบ AI + รายการแพคเกจห้ามหาย
 *   2. กดหัวใจเฉพาะ "คำขอบคุณล้วน" — ขอบคุณแล้วถามต่อ = คำถาม ห้ามนับ
 *   3. สวิตช์ปิด / ติดคูลดาวน์ = ข้อความเดิมทุกตัวอักษร
 *   4. รูปคำขอที่ยิงไป Graph ตรงเอกสาร Meta (sticker_id / sender_action react)
 *
 * ไม่แตะ DB — settings สร้างในหน่วยความจำ (exists=true) · Graph ถูก fake
 */
class FortuneGestureSenderTest extends TestCase
{
    private const PSID = '31000000000000077';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        FortuneConversationService::$pendingPaymentWarning = null;
        Mockery::close();
        parent::tearDown();
    }

    private function settings(bool $gesturesOn = true): FortuneTellingSetting
    {
        $s = new FortuneTellingSetting;
        $s->forceFill([
            'fortune_gestures_fb' => $gesturesOn,
            'facebook_page_token' => 'TOKEN_TEST',
            'enable_deep_reading' => true,
            'enable_celtic_cross' => true,
        ]);
        $s->exists = true;

        return $s;
    }

    /** FB service จริงที่ไม่เรียก constructor (constructor อ่าน DB) — ตั้ง token + ผู้รับที่ resolve แล้ว */
    private function fbService(): FacebookWebhookService
    {
        $svc = (new \ReflectionClass(FacebookWebhookService::class))->newInstanceWithoutConstructor();
        foreach (['pageAccessToken' => 'TOKEN_TEST', 'lastResolvedRecipient' => self::PSID, 'settings' => $this->settings()] as $prop => $val) {
            $r = new \ReflectionProperty(FacebookWebhookService::class, $prop);
            $r->setAccessible(true);
            $r->setValue($svc, $val);
        }

        return $svc;
    }

    // ── 1. ตัดบรรทัดเร่ง ────────────────────────────────────────────

    public function test_ตัดเฉพาะบรรทัดเร่ง_คำตอบเอไอและรายการแพคเกจอยู่ครบ(): void
    {
        $msg = "ได้เลยค่ะ แม่หมอดูให้ได้ทั้งเรื่องงานและความรักนะคะ\n\n"
            .FortuneGestureSender::TIER_WAITING_LINE."\n"
            ."🔹 *\"39\"* — ดูพื้นดวง 39 บาท\n"
            .'❌ *"ยกเลิก"* — หากไม่ต้องการตอนนี้';

        $out = FortuneGestureSender::withoutWaitingLine($msg);

        $this->assertNotNull($out);
        $this->assertStringNotContainsString('ยังรอเจ้าชะตาเลือกแพคเกจ', $out);
        $this->assertStringContainsString('แม่หมอดูให้ได้ทั้งเรื่องงานและความรัก', $out);
        $this->assertStringContainsString('ดูพื้นดวง 39 บาท', $out);
        $this->assertStringContainsString('ยกเลิก', $out);
    }

    public function test_ไม่มีบรรทัดเร่ง_หรือมีแต่บรรทัดเร่ง_ห้ามใช้สติกเกอร์(): void
    {
        $this->assertNull(FortuneGestureSender::withoutWaitingLine('สวัสดีค่ะ เลือกแพคเกจได้เลย'));
        $this->assertNull(FortuneGestureSender::withoutWaitingLine(FortuneGestureSender::TIER_WAITING_LINE));
    }

    // ── 2. คำขอบคุณล้วน ───────────────────────────────────────────

    public function test_คำขอบคุณล้วน(): void
    {
        foreach (['ขอบคุณค่ะ', 'ขอบคุณมากๆ ค่ะแม่หมอ 🙏', 'ขอบคุนนะคะ', 'สาธุ 🙏🙏', 'แม่หมอ ขอบคุณค่ะ', 'Thank you!', 'ขอบพระคุณมากค่ะ'] as $t) {
            $this->assertTrue(FortuneGestureSender::looksLikePureThanks($t), "ต้องนับเป็นคำขอบคุณ: {$t}");
        }
    }

    public function test_ขอบคุณแล้วถามต่อ_หรือข้อความอื่น_ไม่ใช่คำขอบคุณล้วน(): void
    {
        foreach (['ขอบคุณค่ะ แล้วเรื่องงานล่ะคะ', 'ดูดวงความรัก', '39', '🙏', 'ok', '', 'ไม่ขอบคุณ'] as $t) {
            $this->assertFalse(FortuneGestureSender::looksLikePureThanks($t), "ต้องไม่นับ: {$t}");
        }
    }

    // ── 3. สวิตช์ / คูลดาวน์ ───────────────────────────────────────

    public function test_สวิตช์ปิด_ไม่ได้สิทธิ์ใช้ท่า(): void
    {
        $sender = new FortuneGestureSender($this->settings(false));

        $this->assertFalse($sender->claim(self::PSID, FortuneGestureSender::GESTURE_NUDGE_CHOOSE));
    }

    public function test_คูลดาวน์_ครั้งที่สองในช่วงเดียวกันไม่ได้สิทธิ์(): void
    {
        $sender = new FortuneGestureSender($this->settings());

        $this->assertTrue($sender->claim(self::PSID, FortuneGestureSender::GESTURE_NUDGE_CHOOSE));
        $this->assertFalse($sender->claim(self::PSID, FortuneGestureSender::GESTURE_NUDGE_CHOOSE));
        // คนละท่า / คนละคน ไม่กินคูลดาวน์กัน
        $this->assertTrue($sender->claim(self::PSID, FortuneGestureSender::GESTURE_REACT));
        $this->assertTrue($sender->claim('31000000000000088', FortuneGestureSender::GESTURE_NUDGE_CHOOSE));
    }

    // ── 4. รูปคำขอ Graph ───────────────────────────────────────────

    public function test_กดหัวใจ_ยิง_sender_action_react_พร้อม_mid_ของลูกค้า(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['recipient_id' => self::PSID], 200)]);
        $sender = new FortuneGestureSender($this->settings());

        $this->assertTrue($sender->reactToInbound($this->fbService(), self::PSID, 'm_ABC', 'ขอบคุณค่ะ', false));

        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/me/messages')
            && $r['sender_action'] === 'react'
            && $r['payload']['message_id'] === 'm_ABC'
            && $r['payload']['reaction'] === '❤️'
            && $r['recipient']['id'] === self::PSID
            && ! str_contains($r->url(), 'access_token'));
    }

    public function test_ข้อความธรรมดา_ไม่กดหัวใจ_และไม่ยิงอะไรเลย(): void
    {
        Http::fake();
        $sender = new FortuneGestureSender($this->settings());

        $this->assertFalse($sender->reactToInbound($this->fbService(), self::PSID, 'm_ABC', 'อยากดูดวงความรักค่ะ', false));
        $this->assertFalse($sender->reactToInbound($this->fbService(), self::PSID, null, '', true), 'ไม่มี mid = กดไม่ได้');
        Http::assertNothingSent();
    }

    public function test_ส่งสติกเกอร์_ยิง_message_sticker_id(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['recipient_id' => self::PSID, 'message_id' => 'm.1'], 200)]);

        $this->assertTrue($this->fbService()->sendSticker(self::PSID, FortuneGestureSender::STICKER_NUDGE_CHOOSE));

        Http::assertSent(fn (Request $r) => $r['message'] === ['sticker_id' => FortuneGestureSender::STICKER_NUDGE_CHOOSE]
            && $r['messaging_type'] === 'RESPONSE');
    }

    public function test_graph_ปฏิเสธ_คืน_false_ไม่โยน_exception(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['code' => 100, 'message' => 'bad']], 400)]);

        $this->assertFalse($this->fbService()->sendSticker(self::PSID, 1));
    }

    // ── ตัวเรนเดอร์ FB (FortuneChannelManager) ────────────────────

    private function manager(bool $gesturesOn = true): FortuneChannelManager
    {
        $settings = $this->settings($gesturesOn);
        $this->app->instance(FortuneGestureSender::class, new FortuneGestureSender($settings));

        $mgr = (new \ReflectionClass(FortuneChannelManager::class))->newInstanceWithoutConstructor();
        $r = new \ReflectionProperty(FortuneChannelManager::class, 'settings');
        $r->setAccessible(true);
        $r->setValue($mgr, $settings);

        return $mgr;
    }

    private function render(FortuneChannelManager $mgr, FacebookWebhookService $fb, array $result): bool
    {
        $m = new \ReflectionMethod(FortuneChannelManager::class, 'sendFacebookResponse');
        $m->setAccessible(true);

        return $m->invoke($mgr, $fb, self::PSID, $result);
    }

    private function chitchatResult(): array
    {
        return [
            'action' => 'tier_choice_chitchat',
            'message' => "ได้เลยค่ะ แม่หมอดูให้ได้นะคะ\n\n".FortuneGestureSender::TIER_WAITING_LINE."\n🔹 ดูพื้นดวง 39 บาท",
            'reading' => null,
        ];
    }

    public function test_คุยเล่นตอนเลือกแพคเกจ_ตัดประโยคเร่ง_แล้วส่งมือชี้ตามหลังกล่องปุ่ม(): void
    {
        $order = [];
        $fb = Mockery::mock(FacebookWebhookService::class)->makePartial();
        $fb->shouldReceive('sendQuickReplies')->once()
            ->withArgs(function ($uid, $text) use (&$order) {
                $order[] = 'menu';

                return ! str_contains($text, 'ยังรอเจ้าชะตาเลือกแพคเกจ') && str_contains($text, 'ดูพื้นดวง 39 บาท');
            })->andReturn(true);
        $fb->shouldReceive('sendSticker')->once()
            ->with(self::PSID, FortuneGestureSender::STICKER_NUDGE_CHOOSE)
            ->andReturnUsing(function () use (&$order) {
                $order[] = 'sticker';

                return true;
            });

        $this->assertTrue($this->render($this->manager(), $fb, $this->chitchatResult()));
        $this->assertSame(['menu', 'sticker'], $order, 'สติกเกอร์ต้องตามหลังกล่องปุ่ม');
    }

    public function test_คุยเล่นรอบสองในคูลดาวน์_ได้ประโยคเร่งเดิม_ไม่มีสติกเกอร์(): void
    {
        $mgr = $this->manager();
        $fb = Mockery::mock(FacebookWebhookService::class)->makePartial();
        $fb->shouldReceive('sendQuickReplies')->once()->andReturn(true);
        $fb->shouldReceive('sendSticker')->once()->andReturn(true);
        $this->render($mgr, $fb, $this->chitchatResult());

        $fb2 = Mockery::mock(FacebookWebhookService::class)->makePartial();
        $fb2->shouldReceive('sendQuickReplies')->once()
            ->withArgs(fn ($uid, $text) => str_contains($text, FortuneGestureSender::TIER_WAITING_LINE))
            ->andReturn(true);
        $fb2->shouldNotReceive('sendSticker');

        $this->assertTrue($this->render($mgr, $fb2, $this->chitchatResult()));
    }

    public function test_สวิตช์ปิด_ข้อความเดิมทุกตัวอักษร(): void
    {
        $result = $this->chitchatResult();
        $fb = Mockery::mock(FacebookWebhookService::class)->makePartial();
        $fb->shouldReceive('sendQuickReplies')->once()
            ->withArgs(fn ($uid, $text) => $text === $result['message'])
            ->andReturn(true);
        $fb->shouldNotReceive('sendSticker');

        $this->assertTrue($this->render($this->manager(false), $fb, $result));
    }

    public function test_บอกลา_คำอวยพรก่อน_แล้วสติกเกอร์ไหว้(): void
    {
        $order = [];
        $fb = Mockery::mock(FacebookWebhookService::class)->makePartial();
        $fb->shouldReceive('sendMessage')->once()
            ->andReturnUsing(function () use (&$order) {
                $order[] = 'blessing';

                return true;
            });
        $fb->shouldReceive('sendSticker')->once()
            ->with(self::PSID, FortuneGestureSender::STICKER_FAREWELL)
            ->andReturnUsing(function () use (&$order) {
                $order[] = 'sticker';

                return true;
            });

        $sent = $this->render($this->manager(), $fb, [
            'action' => 'farewell_blessing',
            'message' => '🙏 สาธุค่ะ ขอบุญรักษา ✨',
            'reading' => null,
        ]);

        $this->assertTrue($sent);
        $this->assertSame(['blessing', 'sticker'], $order);
    }
}
