<?php

namespace Tests\Feature;

use App\Contracts\FortuneMessengerSender;
use App\Jobs\SendPricingFollowUpJob;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneWebHandoff;
use App\Services\FacebookRichMessageService;
use App\Services\Fortune\FortuneBotMode;
use App\Services\Fortune\FortunePageContext;
use App\Services\Fortune\WebHandoffCopy;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

/**
 * 🌐 (2026-11-03) T8 — ด่าน egress ของ FortuneChannelManager ใต้ FB web-only
 *
 * ล็อกกติกา (design หัวข้อ 6 แถว T8 + หัวข้อ 7 แถว T8/T9):
 *  1. ธงเปิด + PSID: pending_payment / tier_choice (ไม่ใช่ legacy-paid) → กล่องพาไปห้องแม่หมอแทน ไม่มี QR ไม่มีปุ่มราคา
 *  2. action ขาย (tier_choice / pending_payment) บล็อกแม้ลูกค้ามีของที่จ่ายแล้ว — "ส่งของให้จบอย่างเดียว"
 *  3. action ทำนายของลูกค้าที่จ่ายแล้ว (legacy-paid) ผ่านตามเดิม / ไม่จ่าย = บล็อก
 *  4. ผลจาก sendWebHandoff (web_handoff=true) ผ่านเสมอ ส่ง payload เดิมทุกบิต
 *  5. ปุ่มท้ายข้อความคุยเล่น = WEB_ROOM ปุ่มเดียว / [OFFER_FORTUNE] → WEB_ROOM / ปุ่มขายใน action อื่นถูกตัด
 *  6. pricing_menu ใต้ธง → ไม่ dispatch SendPricingFollowUpJob
 *  7. sendWebHandoff พัง → ข้อความ fail-closed + ปุ่ม WEB_ROOM (ห้ามตกกลับไปส่งข้อความขาย)
 *  8. ธงปิด / Telegram / id LINE = พฤติกรรมเดิมทุกอย่าง
 */
class FbWebOnlyChannelEgressTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '61550000000088';

    private const LINE_UID = 'U0123456789abcdef0123456789abcdef';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        FortunePageContext::forget();
        FortuneTellingSetting::clearSettingsCache();
        Mockery::close();

        parent::tearDown();
    }

    /**
     * ตั้งค่าแถวกลาง (ล้าง memo ก่อน/หลัง — แบบเดียวกับ FortuneTransferModeTest)
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
        ], $override))->save();

        FortuneTellingSetting::clearSettingsCache();

        return FortuneTellingSetting::getSettings();
    }

    /**
     * manager ที่เปิด sendFacebookResponse / buildTransferQuickRepliesFor ให้เทสต์เรียก
     * (ฉีด FCS ปลอมได้ — ใช้ทดสอบ fail-closed)
     */
    private function manager(FortuneTellingSetting $settings, ?FortuneConversationService $fcs = null): FortuneChannelManager
    {
        $m = new class($settings) extends FortuneChannelManager
        {
            public function fb(FortuneMessengerSender $s, string $uid, array $r): bool
            {
                return $this->sendFacebookResponse($s, $uid, $r);
            }

            public function transferQr(string $action, string $uid, string $platform = self::PLATFORM_FACEBOOK): ?array
            {
                return $this->buildTransferQuickRepliesFor($action, $uid, new FacebookRichMessageService($this->settings), $platform);
            }

            public function useConversationService(FortuneConversationService $fcs): void
            {
                $this->conversationService = $fcs;
            }

            // เทสต์ไม่ต้องรอ 15 วิแบบคน
            protected function humanLikeDelayFb(FortuneMessengerSender $fbService, string $userId, int $seconds = 15): void {}
        };

        if ($fcs !== null) {
            $m->useConversationService($fcs);
        }

        return $m;
    }

    private function reading(array $attrs = []): FortuneReading
    {
        return FortuneReading::create(array_merge([
            'facebook_user_id' => self::PSID,
            'facebook_user_name' => 'ทดสอบ',
            'questions' => [],
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            'conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT,
            'response_type' => 'private_message',
            'ai_response' => '',
            'ai_provider' => '',
            'platform' => 'facebook',
            'platform_user_id' => self::PSID,
            'bill_reference' => FortuneReading::generateBillReference(),
            'is_paid' => false,
        ], $attrs));
    }

    /** ลูกค้าระดับ A — จ่ายแล้วกำลังทำนาย */
    private function legacyPaid(): FortuneReading
    {
        return $this->reading([
            'conversation_status' => FortuneReading::STATUS_PAID,
            'is_paid' => true,
            'paid_at' => now(),
            'amount_paid' => 39.00,
        ]);
    }

    private function tierResult(): array
    {
        return [
            'action' => 'tier_choice',
            'message' => 'เลือกแพคเกจค่ะ ดูดวง 39 บาท / vip 99 บาท',
            'deep_price' => 39,
            'celtic_price' => 99,
            'offer_free' => false,
            'celtic_only_intro' => false,
            'black_magic_enabled' => false,
            'tier_meta' => ['deep_window' => 7, 'qa_window' => 15, 'q_limit_text' => 'ไม่จำกัด', 'welcome_line' => 'สวัสดีค่ะ'],
        ];
    }

    /** ตรวจว่าสิ่งที่ส่งออกไปคือ "กล่องพาไปห้องแม่หมอ" อย่างเดียว */
    private function assertWebHandoffOnly(object $sender, string $why): void
    {
        $this->assertCount(1, $sender->sent, "{$why}: ต้องส่งกล่องเดียว ไม่ส่งของเดิมตามไป");
        [$type, , $payload] = $sender->sent[0];
        $this->assertSame('template', $type, "{$why}: ต้องเป็นกล่อง button template");

        $buttons = WebHandoffCopy::buttonsOf($payload);
        $this->assertNotEmpty($buttons, "{$why}: กล่องต้องมีปุ่ม");
        $this->assertSame('web_url', $buttons[0]['type'] ?? null, "{$why}: ปุ่มแรกต้องเป็น web_url");
        $this->assertContains($buttons[0]['title'] ?? null, [WebHandoffCopy::LABEL_WEB_ROOM, WebHandoffCopy::LABEL_ENTER_READING, WebHandoffCopy::LABEL_FIRST_VISIT]);

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $this->assertDoesNotMatchRegularExpression('/TIER_|PAY_METHOD|STRIPE|บาท|฿|ค่าครู|โอน/u', $json, "{$why}: ห้ามมีปุ่ม/คำเรื่องเงิน");
        $this->assertNoImages($sender, $why);
    }

    private function assertNoImages(object $sender, string $why): void
    {
        $this->assertNotContains('image', array_column($sender->sent, 0), "{$why}: ห้ามส่งรูป (QR/การ์ดราคา)");
    }

    // ============================================================
    // 1. บล็อกเงิน/ทำนาย ถ้าไม่ใช่ legacy-paid
    // ============================================================

    public function test_ธงเปิด_pending_payment_ไม่ใช่_legacy_ได้กล่องห้องแม่หมอ_ไม่มี_qr(): void
    {
        $settings = $this->settings(true);
        $sender = $this->recordingSender();
        $bill = $this->reading();
        $before = FortuneReading::count();

        $ok = $this->manager($settings)->fb($sender, self::PSID, [
            'action' => 'pending_payment',
            'message' => 'โอนค่าครู 39 บาท เข้าบัญชีนี้นะคะ',
            'reading' => $bill,
            'payment_qr_url' => 'https://example.com/qrcodes/fortune_pp_1.png',
        ]);

        $this->assertTrue($ok);
        $this->assertWebHandoffOnly($sender, 'pending_payment');
        $this->assertSame($before, FortuneReading::count(), 'ห้ามสร้างบิลเพิ่ม');

        $handoff = FortuneWebHandoff::query()->latest('id')->first();
        $this->assertNotNull($handoff, 'ต้องสร้างแถว handoff ให้เว็บคุยต่อได้');
        $this->assertSame('egress_guard', $handoff->source);
        $this->assertSame(self::PSID, $handoff->platform_user_id);
    }

    public function test_ธงเปิด_tier_choice_ได้กล่องห้องแม่หมอ_ไม่มีการ์ดราคา(): void
    {
        $settings = $this->settings(true);
        $sender = $this->recordingSender();
        // ลูกค้าเดิม (มีบัญชีแล้ว) — PSID ที่ยังไม่มีบัญชีได้กล่อง "ดูดวงฟรี" ใบเดียวกันทุกชนิด (เทสแยกใน FortuneWebHandoffTest)
        \App\Models\User::factory()->create()->forceFill(['facebook_psid' => self::PSID])->save();

        $this->assertTrue($this->manager($settings)->fb($sender, self::PSID, $this->tierResult()));
        $this->assertWebHandoffOnly($sender, 'tier_choice');

        // ข้อความในกล่องคือชุด "ราคา" (ไม่มีตัวเลขเงิน)
        $this->assertSame(
            WebHandoffCopy::boxText(WebHandoffCopy::KIND_PRICE),
            WebHandoffCopy::textOf($sender->sent[0][2])
        );
    }

    public function test_action_ขาย_บล็อกแม้ลูกค้าเป็น_legacy_paid(): void
    {
        $settings = $this->settings(true);
        $paid = $this->legacyPaid();
        $this->assertTrue((new FortuneBotMode($settings))->fbLegacyPaidInFlight(self::PSID), 'fixture ต้องเป็นระดับ A');

        foreach (['tier_choice' => $this->tierResult(), 'pending_payment' => [
            'action' => 'pending_payment', 'message' => 'บิลใหม่ 99 บาท', 'reading' => $paid,
        ], 'pricing_menu' => [
            'action' => 'pricing_menu', 'message' => 'ราคา 39 / 99', 'pricing' => ['celtic_enabled' => true],
        ]] as $action => $result) {
            $sender = $this->recordingSender();
            $this->assertTrue($this->manager($settings)->fb($sender, self::PSID, $result));
            $this->assertWebHandoffOnly($sender, "{$action} (legacy-paid)");
        }
    }

    public function test_action_ทำนาย_ของที่จ่ายแล้วผ่าน_ไม่จ่ายถูกบล็อก(): void
    {
        $settings = $this->settings(true);
        $result = ['action' => 'awaiting_tarot_intention', 'message' => 'ตั้งจิตแล้วกดพร้อมค่ะ'];

        // ไม่จ่าย → บล็อก
        $sender = $this->recordingSender();
        $this->assertTrue($this->manager($settings)->fb($sender, self::PSID, $result));
        $this->assertWebHandoffOnly($sender, 'awaiting_tarot_intention (ไม่จ่าย)');

        // จ่ายแล้ว (ระดับ A) → ส่งของต่อตามเดิม (ปุ่ม "พร้อม")
        $paid = $this->legacyPaid();
        $sender = $this->recordingSender();
        $this->assertTrue($this->manager($settings)->fb($sender, self::PSID, $result + ['reading' => $paid]));
        $this->assertSame('quick_replies', $sender->sent[0][0]);
        $this->assertSame('ตั้งจิตแล้วกดพร้อมค่ะ', $sender->sent[0][1]);
        $this->assertSame(['TAROT_READY'], array_column($sender->sent[0][2], 'payload'));
    }

    public function test_บิลในผลจ่ายแล้วแต่หมด_grace_ยังส่งคำทำนายได้(): void
    {
        $settings = $this->settings(true);
        $old = $this->reading([
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'is_paid' => true,
            'paid_at' => now()->subDays(2),
        ]);
        FortuneReading::whereKey($old->id)->update(['updated_at' => now()->subDays(2)]);

        $sender = $this->recordingSender();
        $this->assertTrue($this->manager($settings)->fb($sender, self::PSID, [
            'action' => 'collecting_questions', 'message' => 'ถามคำถามที่ 2 ได้เลยค่ะ', 'reading' => $old->fresh(),
        ]));
        $this->assertNotSame('template', $sender->sent[0][0] ?? null, 'cron ส่งของของบิลจ่ายแล้ว ห้ามโดนแทนด้วยกล่อง');
    }

    // ============================================================
    // 2. ทางผ่าน
    // ============================================================

    public function test_ผลจาก_send_web_handoff_ผ่านเสมอ_payload_เดิมทุกบิต(): void
    {
        $settings = $this->settings(true);
        $sender = $this->recordingSender();
        $template = ['text' => WebHandoffCopy::FAIL_CLOSED, 'quick_replies' => WebHandoffCopy::webRoomQuickReply()];

        $this->assertTrue($this->manager($settings)->fb($sender, self::PSID, [
            'action' => 'transfer_box', 'message' => 'x', 'fb_template' => $template, 'web_handoff' => true,
        ]));
        $this->assertCount(1, $sender->sent);
        $this->assertSame($template, $sender->sent[0][2]);
        $this->assertSame(0, FortuneWebHandoff::count(), 'ผ่านตรง ไม่สร้าง handoff ซ้ำ');
    }

    public function test_transfer_box_แบบเก่า_ที่มีปุ่ม_line_ถูกแทนด้วยกล่องเว็บ(): void
    {
        $settings = $this->settings(true);
        $sender = $this->recordingSender();

        $this->assertTrue($this->manager($settings)->fb($sender, self::PSID, [
            'action' => 'transfer_box',
            'message' => 'ไปดูที่ LINE',
            'fb_template' => ['attachment' => ['type' => 'template', 'payload' => [
                'template_type' => 'button', 'text' => 'ไปดูที่ LINE',
                'buttons' => [['type' => 'postback', 'title' => '💬 ดูในแชทนี้', 'payload' => 'TRANSFER_STAY_FB']],
            ]]],
        ]));
        $this->assertWebHandoffOnly($sender, 'transfer_box เก่า');
        $this->assertStringNotContainsString('TRANSFER_STAY_FB', json_encode($sender->sent));
    }

    public function test_คุยเล่นและยืนยันสลิป_ไม่โดนบล็อก(): void
    {
        $settings = $this->settings(true);

        foreach (['fuzzy_auto_approved', 'payment_confirmed_wait', 'pdpa_deleted', 'farewell_blessing'] as $action) {
            $sender = $this->recordingSender();
            $this->manager($settings)->fb($sender, self::PSID, ['action' => $action, 'message' => 'ขอบคุณค่ะ']);
            $this->assertSame(0, FortuneWebHandoff::count(), "{$action} ต้องไม่ถูกแทนด้วยกล่อง");
        }
    }

    // ============================================================
    // 3. ปุ่มท้ายข้อความ
    // ============================================================

    public function test_ปุ่มท้ายข้อความคุยเล่น_เป็น_web_room_ปุ่มเดียว_ทั้งลูกค้าใหม่และจ่ายแล้ว(): void
    {
        $settings = $this->settings(true);
        $m = $this->manager($settings);

        $this->assertSame(WebHandoffCopy::webRoomQuickReply(), $m->transferQr('ai_chat_response', self::PSID));
        $this->assertSame(WebHandoffCopy::webRoomQuickReply(), $m->transferQr('keyword_matched', self::PSID));
        $this->assertNull($m->transferQr('payment_check_pending', self::PSID), 'action อื่นไม่แตะ');
        $this->assertNull($m->transferQr('ai_chat_response', 'tg_12345', FortuneChannelManager::PLATFORM_TELEGRAM));

        $this->legacyPaid();
        $this->assertSame(WebHandoffCopy::webRoomQuickReply(), $m->transferQr('ai_chat_response', self::PSID));

        foreach ([$m->transferQr('ai_chat_response', self::PSID)] as $qrs) {
            $this->assertNotContains('TRANSFER_GO', array_column($qrs, 'payload'));
            $this->assertNotContains('TRANSFER_GO_LINE', array_column($qrs, 'payload'));
        }
    }

    public function test_offer_fortune_กลายเป็น_web_room_และแท็กไม่หลุด(): void
    {
        $settings = $this->settings(true);
        $sender = $this->recordingSender();

        $this->assertTrue($this->manager($settings)->fb($sender, self::PSID, [
            'action' => 'ai_chat_response',
            'message' => 'เรื่องนี้น่าเปิดไพ่ดูนะคะ [OFFER_FORTUNE]',
            'turn_count' => 5,
        ]));

        $this->assertSame('quick_replies', $sender->sent[0][0]);
        $this->assertStringNotContainsString('[OFFER_FORTUNE]', $sender->sent[0][1]);
        $this->assertSame([WebHandoffCopy::PAYLOAD_WEB_ROOM], array_column($sender->sent[0][2], 'payload'));
    }

    public function test_ปุ่มขายใน_action_อื่นถูกตัด_ปุ่มกลางยังอยู่_แนบ_web_room(): void
    {
        $settings = $this->settings(true);
        $paid = $this->reading([
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'is_paid' => true,
            'paid_at' => now(),
            'deep_response' => 'คำทำนาย',
        ]);
        $sender = $this->recordingSender();

        // view_reading_deep: ปุ่มเดิม = FORTUNE_EARN_INFO / SHARE_PAGE / MENU_FORTUNE
        $this->assertTrue($this->manager($settings)->fb($sender, self::PSID, [
            'action' => 'view_reading_deep', 'message' => 'คำทำนายของคุณค่ะ', 'reading' => $paid,
        ]));

        $last = end($sender->sent);
        $this->assertSame('quick_replies', $last[0]);
        $payloads = array_column($last[2], 'payload');
        $this->assertNotContains('MENU_FORTUNE', $payloads);
        $this->assertNotContains('FORTUNE_EARN_INFO', $payloads);
        $this->assertContains('SHARE_PAGE', $payloads, 'ปุ่มที่ไม่เกี่ยวกับเงินต้องอยู่ครบ');
        $this->assertSame(WebHandoffCopy::PAYLOAD_WEB_ROOM, end($payloads));
        $this->assertSame(1, count(array_keys($payloads, WebHandoffCopy::PAYLOAD_WEB_ROOM)), 'WEB_ROOM ห้ามซ้ำ');
    }

    // ============================================================
    // 4. SendPricingFollowUpJob
    // ============================================================

    public function test_pricing_menu_ใต้ธงไม่_dispatch_job_อธิบายค่าครู_ธงปิด_dispatch_ตามเดิม(): void
    {
        Queue::fake();
        $result = ['action' => 'pricing_menu', 'message' => 'ราคา', 'pricing' => ['celtic_enabled' => true, 'free_enabled' => true]];

        $this->manager($this->settings(true))->fb($this->recordingSender(), self::PSID, $result);
        Queue::assertNotPushed(SendPricingFollowUpJob::class);

        $this->manager($this->settings(false))->fb($this->recordingSender(), self::PSID, $result);
        Queue::assertPushed(SendPricingFollowUpJob::class);
    }

    // ============================================================
    // 5. fail-closed
    // ============================================================

    public function test_send_web_handoff_พัง_ได้ข้อความ_fail_closed_ไม่ตกไปส่งของขาย(): void
    {
        $settings = $this->settings(true);

        $throwing = Mockery::mock(FortuneConversationService::class);
        $throwing->shouldReceive('sendWebHandoff')->andThrow(new \RuntimeException('boom'));

        $nulling = Mockery::mock(FortuneConversationService::class);
        $nulling->shouldReceive('sendWebHandoff')->andReturn(null);

        foreach (['throw' => $throwing, 'null' => $nulling] as $case => $fcs) {
            $sender = $this->recordingSender();
            $this->manager($settings, $fcs)->fb($sender, self::PSID, $this->tierResult());

            $this->assertCount(1, $sender->sent, "{$case}: ส่งข้อความเดียว");
            $this->assertSame('template', $sender->sent[0][0]);
            $this->assertSame([
                'text' => WebHandoffCopy::FAIL_CLOSED,
                'quick_replies' => WebHandoffCopy::webRoomQuickReply(),
            ], $sender->sent[0][2], "{$case}: ต้องเป็นข้อความ fail-closed + ปุ่ม WEB_ROOM");
            $this->assertNoImages($sender, $case);
        }
    }

    // ============================================================
    // 6. LINE / Telegram / ธงปิด = เหมือนเดิม
    // ============================================================

    public function test_ธงปิด_พฤติกรรมเดิมทุกอย่าง(): void
    {
        $settings = $this->settings(false);
        $sender = $this->recordingSender();

        $this->assertTrue($this->manager($settings)->fb($sender, self::PSID, [
            'action' => 'payment_info', 'message' => 'เลขบัญชี 123-4-56789-0',
        ]));
        $this->assertSame([['text', 'เลขบัญชี 123-4-56789-0', []]], $sender->sent);
        $this->assertSame(0, FortuneWebHandoff::count());

        // ปุ่มท้ายข้อความ = ไม่แตะ (classic)
        $this->assertNull($this->manager($settings)->transferQr('ai_chat_response', self::PSID));
    }

    public function test_telegram_และ_id_line_ไม่ผ่านด่าน(): void
    {
        $settings = $this->settings(true);

        $tg = $this->recordingSender('telegram');
        $this->assertTrue($this->manager($settings)->fb($tg, 'tg_123456', [
            'action' => 'payment_info', 'message' => 'เลขบัญชี 123',
        ]));
        $this->assertSame('text', $tg->sent[0][0], 'Telegram ต้องได้ของเดิม');

        $fb = $this->recordingSender();
        $this->assertTrue($this->manager($settings)->fb($fb, self::LINE_UID, [
            'action' => 'payment_info', 'message' => 'เลขบัญชี 123',
        ]));
        $this->assertSame('text', $fb->sent[0][0], 'id LINE resolve เป็น line → ไม่บล็อก');
        $this->assertSame(0, FortuneWebHandoff::count());
    }

    /**
     * sender บันทึกทุกการส่ง: [ชนิด, ข้อความ, payload/ปุ่ม]
     */
    // ============================================================
    // 🔐 รีวิว — ระดับ A ไม่ปลดล็อก upsell / grace นับจาก paid_at
    // ============================================================

    public function test_ระดับ_a_ไม่ปลดล็อก_basic_done_upsell_มีราคา(): void
    {
        $settings = $this->settings(true);
        $this->legacyPaid();
        $this->assertTrue((new FortuneBotMode($settings))->fbLegacyPaidInFlight(self::PSID), 'fixture ต้องเป็นระดับ A');

        // ดวงพื้นฐานฟรี (บิลในผลไม่ได้จ่าย) + upsell ดูลึก → ไม่ใช่การส่งของที่จ่ายแล้ว
        $basic = $this->reading([
            'reading_type' => 'basic', 'conversation_status' => FortuneReading::STATUS_COMPLETED, 'is_paid' => false,
        ]);
        $sender = $this->recordingSender();
        $this->assertTrue($this->manager($settings)->fb($sender, self::PSID, [
            'action' => 'basic_done',
            'message' => 'ดวงพื้นฐานของเจ้าชะตาค่ะ อยากดูลึก ค่าครู 39 บาทนะคะ',
            'reading' => $basic,
            'quick_reply_options' => [['title' => '🔮 ดูละเอียด', 'payload' => 'DEEP_READING_ACCEPT']],
        ]));
        $this->assertWebHandoffOnly($sender, 'basic_done (ระดับ A)');
    }

    public function test_grace_ระดับ_a_นับจาก_paid_at_ไม่ใช่_updated_at(): void
    {
        $settings = $this->settings(true);
        $done = $this->reading([
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'is_paid' => true,
            'paid_at' => now()->subHours(3),
        ]);
        // cron aftercare / review invite เขียน state → updated_at ขยับเป็นตอนนี้
        FortuneReading::whereKey($done->id)->update(['updated_at' => now(), 'created_at' => now()->subHours(3)]);

        $this->assertFalse((new FortuneBotMode($settings))->fbLegacyPaidInFlight(self::PSID), 'จ่ายนานแล้ว ห้ามยืด grace ด้วย updated_at');

        FortuneReading::whereKey($done->id)->update(['paid_at' => now()->subMinutes(5)]);
        $this->assertTrue((new FortuneBotMode($settings))->fbLegacyPaidInFlight(self::PSID), 'เพิ่งจ่าย = ยังอยู่ใน grace');
    }

    public function test_ข้อความถูกด่านเงินชั้นส่งบล็อก_ได้กล่องห้องแม่หมอแทนความเงียบ_ธงปิดไม่แตะ(): void
    {
        $settings = $this->settings(true);
        $sender = $this->recordingSender();
        $sender->blockMoney = true;

        $this->assertTrue($this->manager($settings)->fb($sender, self::PSID, [
            'action' => 'keyword_matched',
            'message' => 'แม่หมอรับโอนผ่านพร้อมเพย์นะคะ',
        ]));
        $this->assertWebHandoffOnly($sender, 'ข้อความเงินที่ T9 บล็อก');

        // ธงปิด → ไม่ส่งกล่องแทน (พฤติกรรมเดิม)
        $settings = $this->settings(false);
        $sender = $this->recordingSender();
        $sender->blockMoney = true;
        $this->manager($settings)->fb($sender, self::PSID, [
            'action' => 'keyword_matched',
            'message' => 'แม่หมอรับโอนผ่านพร้อมเพย์นะคะ',
        ]);
        $this->assertNotContains('template', array_column($sender->sent, 0), 'ธงปิดห้ามส่งกล่องห้องแม่หมอ');
    }

    private function recordingSender(string $platform = 'facebook'): object
    {
        return new class($platform) implements FortuneMessengerSender
        {
            public array $sent = [];

            /** จำลองด่านเงินชั้นส่ง (T9) — ข้อความมีคำเงิน = ส่งไม่ออก */
            public bool $blockMoney = false;

            public function __construct(private string $platform) {}

            public function sendMessage(string $recipientId, string $message, array $options = []): bool
            {
                if ($this->blockMoney && \App\Services\FacebookWebhookService::containsMoneyText($message)) {
                    return false;
                }
                $this->sent[] = ['text', $message, []];

                return true;
            }

            public function sendQuickReplies(string $recipientId, string $message, array $quickReplies, array $options = []): bool
            {
                if ($this->blockMoney && \App\Services\FacebookWebhookService::containsMoneyText($message)) {
                    return false;
                }
                $this->sent[] = ['quick_replies', $message, $quickReplies];

                return true;
            }

            public function sendImage(string $recipientId, string $imageUrl, ?string $previewUrl = null, array $options = []): bool
            {
                $this->sent[] = ['image', $imageUrl, []];

                return true;
            }

            public function sendButtonTemplate(string $recipientId, array $templatePayload, array $options = []): bool
            {
                $this->sent[] = ['template', '', $templatePayload];

                return true;
            }

            public function sendGenericTemplate(string $recipientId, array $elements, array $options = []): bool
            {
                $this->sent[] = ['generic', '', $elements];

                return true;
            }

            public function sendAudio(string $recipientId, string $audioUrl, array $options = []): bool
            {
                return true;
            }

            public function sendTypingIndicator(string $recipientId, bool $on = true): void {}

            public function getPlatformName(): string
            {
                return $this->platform;
            }
        };
    }
}
