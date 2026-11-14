<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\FortuneSavedQuestion;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneWebHandoff;
use App\Models\LineBotKeyword;
use App\Models\UniquePaymentAmount;
use App\Services\Fortune\FbBillingGuard;
use App\Services\Fortune\FortuneBotMode;
use App\Services\Fortune\FortunePackageOffer;
use App\Services\Fortune\FortunePageContext;
use App\Services\Fortune\WebHandoffCopy;
use App\Services\FortuneConversationService;
use App\Services\FortuneWebLinkService;
use App\Services\LineAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 🌐 (2026-11-03) T4 — ด่านขาเข้า FB web-only ผ่าน processMessage จริง (ไม่ stub ด่าน)
 *
 * ล็อกกติกา:
 *  1. ข้อความเงิน/ทำนาย/สลิป/ป้ายปุ่ม/คำตอบ consent/force_tier/เมนู armed บน FB = กล่องพาไปห้องแม่หมอ
 *     0 บิลใหม่ · 0 UPA · ไม่มี action เงิน — ทั้ง FCS ที่ setPlatform('facebook') และที่ไม่ได้เรียก
 *  2. ระดับ A (จ่ายแล้วกำลังส่งของ) → โฟลเดิม ไม่โดนด่าน
 *  3. ระดับ B (บิลค้างก่อน T0) + "โอนแล้ว" → ตอบสั้นไม่ขาย + ฝากแอดมิน ไม่มีบิลใหม่
 *  4. soft state ที่ยังไม่จ่ายถูกปิดเป็น moved_to_web
 *  5. LINE ข้อความเดียวกัน = ผลเหมือนตอนธงปิด
 *  6. สร้างลิงก์ไม่ได้ → ข้อความ fail-closed ไม่มีบิล
 *  7. คีย์เวิร์ด DB ที่ตอบเรื่องเงินถูกข้ามบน FB (LINE ยังได้) · directive ของ AI แบบ web-only
 */
class FortuneFbWebOnlyModeTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '61550000000044';

    private const LINE_UID = 'U0123456789abcdef0123456789abcdee';

    /** action ที่ห้ามเกิดบน FB ใต้ web-only (ออกบิล/ขอเงิน/เริ่มโฟลทำนาย) */
    private const MONEY_ACTIONS = [
        'pending_payment', 'waiting_payment', 'payment_info', 'payment_method_choice', 'tier_choice',
        'pricing_menu', 'show_bank_account', 'bank_account', 'collecting_birthdate', 'collecting_questions',
        'draw_tarot_card', 'free_card', 'celtic_pending_payment', 'stripe_checkout', 'consent_gate',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Http::fake();
        $this->existingCustomer();
    }

    /**
     * ลูกค้าเดิม (มีบัญชีผูก PSID แล้ว) — ได้กล่องตามชนิดเจตนา (ราคา/ดูดวง/ห้อง)
     *
     * 🎁 (2026-11-14) PSID ที่ยังไม่มีบัญชีได้กล่อง "วันนี้มีสิทธิ์ดูดวงฟรี" ใบเดียวกันทุกชนิด
     * ไฟล์นี้ล็อกการแยกชนิดกล่อง จึงตั้งเป็นลูกค้าเดิม · กล่องคนมาครั้งแรกเทสแยกใน FortuneWebHandoffTest
     */
    private function existingCustomer(): void
    {
        \App\Models\User::factory()->create()->forceFill(['facebook_psid' => self::PSID])->save();
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
            'fortune_bot_mode' => FortuneBotMode::MODE_DAILY,
            'facebook_web_only' => false,
            'enable_web_fortune_button' => false,
            'enable_ai_chat' => false,
        ], $override))->save();

        FortuneTellingSetting::clearSettingsCache();

        return FortuneTellingSetting::getSettings();
    }

    private function webOnly(array $override = []): FortuneTellingSetting
    {
        return $this->settings(array_merge(['facebook_web_only' => true], $override));
    }

    private function fcs(FortuneTellingSetting $settings, ?string $platform = 'facebook'): FortuneConversationService
    {
        $fcs = new FortuneConversationService($settings);

        return $platform === null ? $fcs : $fcs->setPlatform($platform);
    }

    private function reading(string $uid, string $status, bool $paid, array $state = [], array $extra = []): FortuneReading
    {
        $platform = str_starts_with($uid, 'U') ? 'line' : 'facebook';
        $r = new FortuneReading;
        $r->forceFill(array_merge([
            'platform' => $platform,
            'facebook_user_id' => $uid,
            'platform_user_id' => $uid,
            'facebook_user_name' => 'ทดสอบ',
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'conversation_status' => $status,
            'is_paid' => $paid,
            'questions' => [],
            'response_type' => 'private_message',
            'ai_response' => '',
            'ai_provider' => '',
            'conversation_state' => $state,
        ], $extra))->save();

        return $r;
    }

    private function assertNoBillingHappened(array $result, int $readingsBefore, string $label): void
    {
        $this->assertNotContains($result['action'] ?? null, self::MONEY_ACTIONS, "{$label}: ห้ามได้ action เงิน");
        $this->assertSame($readingsBefore, FortuneReading::count(), "{$label}: ห้ามสร้าง reading ใหม่");
        $this->assertSame(0, UniquePaymentAmount::count(), "{$label}: ห้ามจองยอดโอน (UPA)");
        $this->assertSame(
            0,
            FortuneReading::whereIn('conversation_status', FortuneReading::PENDING_DISPLAY_STATUSES)->where('is_paid', false)->count(),
            "{$label}: ห้ามมีบิลค้างจ่าย"
        );
        $this->assertSame(
            0,
            FortuneReading::whereNotNull('stripe_session_id')->count(),
            "{$label}: ห้ามสร้าง Stripe session"
        );
    }

    private function assertHandoff(array $result, string $label): void
    {
        $this->assertSame('transfer_box', $result['action'] ?? null, "{$label}: ต้องได้กล่องพาไปห้องแม่หมอ");
        $this->assertTrue((bool) ($result['web_handoff'] ?? false), "{$label}: ต้องเป็น web handoff");
        $this->assertTrue((bool) ($result['web_only_gate'] ?? false), "{$label}: ต้องมาจากด่าน web-only");
        $this->assertNull($result['reading'] ?? null);
        $this->assertDoesNotMatchRegularExpression(WebHandoffCopy::REPLY_FORBIDDEN_REGEX, (string) $result['message'], "{$label}: ข้อความห้ามพูดเรื่องเงิน");
    }

    // ============================================================
    // 1. ข้อความเงิน/ทำนาย → กล่อง ไม่มีบิล
    // ============================================================

    public static function blockedMessages(): array
    {
        return [
            'ดูดวง' => ['ดูดวง', WebHandoffCopy::KIND_READING],
            '39' => ['39', WebHandoffCopy::KIND_READING],
            '99' => ['99', WebHandoffCopy::KIND_READING],
            'ดูดวง 99 บาท' => ['ดูดวง 99 บาท', null],
            'ราคาเท่าไหร่' => ['ราคาเท่าไหร่', WebHandoffCopy::KIND_PRICE],
            'ขอเลขบัญชี' => ['ขอเลขบัญชี', WebHandoffCopy::KIND_PRICE],
            'ดูคุณไสย' => ['ดูคุณไสย', WebHandoffCopy::KIND_READING],
            'ทำไม่เป็น' => ['ทำไม่เป็น', WebHandoffCopy::KIND_CANNOT],
            'ยืนยันจ่ายเงินไทยได้' => ['ยืนยันจ่ายเงินไทยได้', null],
            'ป้ายปุ่ม ดูดวงเลย' => ['🔮 ดูดวงเลย', null],
            'ป้ายปุ่ม ดูละเอียด 39฿' => ['💎 ดูดวงละเอียด 39฿', null],
            'ป้ายปุ่ม คุณไสย 99฿' => ['🪬 ดูคุณไสย 99฿', null],
            'ป้ายปุ่ม ไพ่ฟรี' => ['🃏 ไพ่ฟรี 1 ใบ', null],
            'ป้ายปุ่ม ดวงวันนี้' => ['🌞 ดวงวันนี้', null],
            'เมนู' => ['เมนู', null],
            'ตอบกล่องกติกา' => ['พร้อมบูชาครู', null],
            'celtic' => ['celtic', null],
            'โอนแล้ว (ไม่มีบิล)' => ['โอนแล้วค่ะ', WebHandoffCopy::KIND_SLIP_FORWARDED],
        ];
    }

    #[DataProvider('blockedMessages')]
    public function test_ข้อความเงินและทำนายบน_fb_ได้กล่องพาไปห้อง_ไม่มีบิล(string $text, ?string $kind): void
    {
        $settings = $this->webOnly();

        // ทั้ง FCS ที่ตั้ง platform และที่ไม่ได้ตั้ง (cron/job สร้างเอง — currentPlatform='line' ค่าเริ่มต้น)
        foreach (['facebook', null] as $platform) {
            Cache::flush();
            $before = FortuneReading::count();

            $res = $this->fcs($settings, $platform)->processMessage(self::PSID, $text, ['name' => 'สมศรี']);

            $label = "{$text} [".($platform ?? 'no-setPlatform').']';
            $this->assertHandoff($res, $label);
            $this->assertNoBillingHappened($res, $before, $label);
            if ($kind !== null) {
                $this->assertSame(WebHandoffCopy::boxText($kind, 'สมศรี'), $res['message'], "{$label}: ชนิดกล่องต้องตรงเจตนา");
            }
            $this->assertNotNull($res['handoff_nonce'], "{$label}: ต้องออก handoff ให้เว็บคุยต่อ");
        }

        $this->assertSame(2, FortuneWebHandoff::where('platform_user_id', self::PSID)->count());
    }

    public function test_แจ้งโอนโดยไม่มีบิล_ฝากเรื่องให้แอดมินและแจ้งเตือนครั้งเดียว(): void
    {
        $settings = $this->webOnly();
        $this->mock(LineAlertService::class, function ($m) {
            $m->shouldReceive('alertSystemError')->once();
        });

        $fcs = $this->fcs($settings, null);
        $fcs->processMessage(self::PSID, 'โอนแล้วค่ะ');
        $res = $fcs->processMessage(self::PSID, 'จ่ายแล้วนะคะ');

        $this->assertHandoff($res, 'แจ้งโอนซ้ำ');
        $this->assertSame(2, FortuneSavedQuestion::where('platform_user_id', self::PSID)->where('platform', 'facebook')->count());
        $this->assertSame(0, FortuneReading::count());
    }

    // ============================================================
    // 2. cache / soft state ที่จะพาไปออกบิล
    // ============================================================

    public function test_คำตอบ_consent_ที่ค้าง_ไม่ออกบิล_และล้างธง(): void
    {
        $settings = $this->webOnly();
        Cache::put('fortune:consent_pending:'.self::PSID, 'deep', 600);

        $res = $this->fcs($settings)->processMessage(self::PSID, 'พร้อมบูชาครู');

        $this->assertHandoff($res, 'consent');
        $this->assertNoBillingHappened($res, 0, 'consent');
        $this->assertFalse(Cache::has('fortune:consent_pending:'.self::PSID), 'ต้องล้างกล่องกติกาที่ค้าง');
    }

    public function test_force_tier_ใน_cache_ไม่ออกบิล_และล้างธง(): void
    {
        $settings = $this->webOnly();
        Cache::put('fortune:force_tier:'.self::PSID, 'deep', 600);

        // ข้อความตอบรับธรรมดา — โฟลเดิมจะ pull force_tier แล้วออกบิล 39 ทันที
        $res = $this->fcs($settings)->processMessage(self::PSID, 'ค่ะ');

        $this->assertTrue((bool) ($res['web_only_gate'] ?? false), 'ต้องถูกด่านดัก ไม่ไหลลงโฟลเดิม');
        $this->assertNoBillingHappened($res, 0, 'force_tier');
        $this->assertFalse(Cache::has('fortune:force_tier:'.self::PSID));
    }

    public function test_เมนูแพ็กเกจที่_armed_ไว้_ไม่ออกบิล_และล้างเมนู(): void
    {
        $settings = $this->webOnly();
        FortunePackageOffer::arm(self::PSID, ['deep', 'celtic', 'celtic_blackmagic']);
        $this->assertNotEmpty(FortunePackageOffer::pendingTiers(self::PSID));

        $res = $this->fcs($settings)->processMessage(self::PSID, 'ดูคุณไสย');

        $this->assertHandoff($res, 'armed menu');
        $this->assertNoBillingHappened($res, 0, 'armed menu');
        $this->assertSame([], FortunePackageOffer::pendingTiers(self::PSID));
    }

    public function test_soft_state_ที่ยังไม่จ่ายถูกปิดเป็น_moved_to_web(): void
    {
        $settings = $this->webOnly();
        $tier = $this->reading(self::PSID, FortuneReading::STATUS_TIER_CHOICE, false);
        $collecting = $this->reading(self::PSID, FortuneReading::STATUS_COLLECTING_BIRTHDATE, false, [], [
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
        ]);

        $res = $this->fcs($settings)->processMessage(self::PSID, '1');

        $this->assertTrue((bool) ($res['web_only_gate'] ?? false));
        $this->assertSame(2, FortuneReading::count(), 'ห้ามสร้าง reading ใหม่');
        foreach ([$tier, $collecting] as $r) {
            $r->refresh();
            $this->assertSame(FortuneReading::STATUS_COMPLETED, $r->conversation_status);
            $this->assertSame('moved_to_web', $r->getConversationState('cancellation_reason'));
            $this->assertFalse((bool) $r->is_paid);
        }
        $this->assertSame(0, UniquePaymentAmount::count());
    }

    public function test_คุยเล่นตอน_ai_ปิด_ได้กล่องห้อง_และบันทึกบทสนทนา(): void
    {
        $settings = $this->webOnly(['enable_ai_chat' => false]);

        $res = $this->fcs($settings)->processMessage(self::PSID, 'สวัสดีค่ะแม่หมอ');

        $this->assertHandoff($res, 'chitchat');
        $this->assertSame(WebHandoffCopy::boxText(WebHandoffCopy::KIND_ROOM), $res['message']);
        $this->assertSame(0, FortuneReading::count());
    }

    /**
     * เจ้าของสั่ง 2026-11-04: คุยในเพจก่อน แล้วค่อยส่งการ์ดเข้าเว็บ — คุยเล่นต้องได้คำตอบ AI ไม่ใช่การ์ดทันที
     */
    public function test_คุยเล่นตอน_ai_เปิด_แม่หมอคุยในเพจก่อน_ไม่ส่งการ์ด(): void
    {
        $settings = $this->webOnly(['enable_ai_chat' => true]);
        $fcs = new class($settings) extends FortuneConversationService
        {
            public int $aiCalls = 0;

            public function tryAIChatResponse(string $userId, string $messageText, ?array $userProfile = null, ?array $dmContext = null, bool $bypassBuffer = false): ?array
            {
                $this->aiCalls++;

                return ['action' => 'ai_chat_response', 'message' => 'คุยต่อบน FB', 'reading' => null];
            }
        };
        $fcs->setPlatform('facebook');

        $before = FortuneReading::count();
        $res = $fcs->processMessage(self::PSID, 'สวัสดีค่ะแม่หมอ วันนี้เหนื่อยมากเลย');

        $this->assertSame(1, $fcs->aiCalls, 'คุยเล่นต้องให้แม่หมอ AI ตอบในเพจ');
        $this->assertSame('ai_chat_response', $res['action'] ?? null);
        $this->assertSame('คุยต่อบน FB', $res['message']);
        $this->assertTrue((bool) ($res['web_only_gate'] ?? false), 'ต้องมาจากด่าน web-only');
        $this->assertNoBillingHappened($res, $before, 'chitchat-ai-on');
        $this->assertSame(0, FortuneWebHandoff::where('platform_user_id', self::PSID)->count(), 'ยังไม่อยากดูดวง = ยังไม่ส่งการ์ด');
    }

    public function test_คุยเล่นตอน_ai_ตอบไม่ได้_ได้การ์ดเข้าเว็บแทนความเงียบ(): void
    {
        $settings = $this->webOnly(['enable_ai_chat' => true]);
        $fcs = new class($settings) extends FortuneConversationService
        {
            public function tryAIChatResponse(string $userId, string $messageText, ?array $userProfile = null, ?array $dmContext = null, bool $bypassBuffer = false): ?array
            {
                return null;
            }
        };
        $fcs->setPlatform('facebook');

        $res = $fcs->processMessage(self::PSID, 'สวัสดีค่ะแม่หมอ');

        $this->assertHandoff($res, 'chitchat-ai-null');
        $this->assertSame(WebHandoffCopy::boxText(WebHandoffCopy::KIND_ROOM), $res['message']);
        $this->assertSame(1, FortuneWebHandoff::where('platform_user_id', self::PSID)->count());
    }

    public function test_เล่าเรื่องยาวเกินเพดาน_ยังได้กล่องห้อง_ไม่เงียบ(): void
    {
        $settings = $this->webOnly();

        $res = $this->fcs($settings)->processMessage(self::PSID, str_repeat('ช่วงนี้ชีวิตวุ่นวายมาก ', 120));

        $this->assertHandoff($res, 'long-story');
    }

    public function test_พิมพ์รัวติดกัน_ได้กล่องเดียว_แต่ทุกข้อความถูกบันทึก(): void
    {
        $settings = $this->webOnly();

        $first = $this->fcs($settings)->processMessage(self::PSID, 'สวัสดีค่ะ');
        $second = $this->fcs($settings)->processMessage(self::PSID, 'อยู่ไหมคะ');

        $this->assertHandoff($first, 'burst-1');
        $this->assertSame('silent_skip', $second['action'], 'ข้อความที่สองใน 2 นาทีต้องไม่ส่งกล่องซ้ำ');
        $this->assertSame(1, FortuneWebHandoff::where('platform_user_id', self::PSID)->count());

        $this->travel(3)->minutes();
        $third = $this->fcs($settings)->processMessage(self::PSID, 'แม่หมอคะ');
        $this->assertHandoff($third, 'burst-after-window');
    }

    // ============================================================
    // 3. ระดับ A — ของที่จ่ายแล้วกำลังส่ง (โฟลเดิม)
    // ============================================================

    public function test_legacy_paid_celtic_picking_ยังได้โฟลทำนายเดิม(): void
    {
        $settings = $this->webOnly();
        $reading = $this->reading(self::PSID, FortuneReading::STATUS_CELTIC_PICKING, true);

        $res = $this->fcs($settings)->processMessage(self::PSID, 'ดูดวง');

        $this->assertSame('celtic_restart_hint', $res['action'], 'Celtic ที่จ่ายแล้วต้องเปิดไพ่ต่อตามเดิม');
        $this->assertArrayNotHasKey('web_handoff', $res);
        $this->assertSame(FortuneReading::STATUS_CELTIC_PICKING, $reading->fresh()->conversation_status);
        $this->assertSame(0, FortuneWebHandoff::count());
    }

    public function test_legacy_paid_deep_collecting_ไม่โดนด่าน(): void
    {
        $settings = $this->webOnly();
        $reading = $this->reading(self::PSID, FortuneReading::STATUS_COLLECTING_BIRTHDATE, true, [], [
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
        ]);

        $res = $this->fcs($settings)->processMessage(self::PSID, '12 มีนาคม 2530');

        $this->assertArrayNotHasKey('web_only_gate', $res, 'Deep ที่จ่ายแล้วกำลังเก็บวันเกิด ต้องไม่โดนด่าน');
        $this->assertSame(0, FortuneWebHandoff::count());
        $this->assertTrue((bool) $reading->fresh()->is_paid);
        $this->assertNotSame('completed', $reading->fresh()->getConversationState('cancellation_reason'));
        $this->assertNull($reading->fresh()->getConversationState('cancellation_reason'), 'ห้ามปิดบิลที่จ่ายแล้ว');
    }

    public function test_legacy_paid_pro_session_ไม่โดนด่าน(): void
    {
        $settings = $this->webOnly();
        $reading = $this->reading(self::PSID, FortuneReading::STATUS_COMPLETED, true, ['pro_session_active' => true]);
        // ให้พ้น grace 30 นาที — เหลือแค่ pro_session_active ที่ทำให้เป็นระดับ A
        $reading->timestamps = false;
        $reading->forceFill(['updated_at' => now()->subHours(2)])->save();

        $res = $this->fcs($settings)->processMessage(self::PSID, 'แล้วเรื่องงานล่ะคะ');

        $this->assertArrayNotHasKey('web_only_gate', $res);
        $this->assertSame(0, FortuneWebHandoff::count());
    }

    // ============================================================
    // 4. ระดับ B — บิลค้างก่อน T0
    // ============================================================

    public function test_บิลค้างระดับ_b_แจ้งโอน_ตอบสั้นไม่ขาย_ไม่สร้างบิลใหม่(): void
    {
        $settings = $this->webOnly();
        $this->mock(LineAlertService::class, function ($m) {
            $m->shouldReceive('alertSystemError')->once();
        });
        $bill = $this->reading(self::PSID, FortuneReading::STATUS_PENDING_PAYMENT, false, [], [
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
        ]);

        $res = $this->fcs($settings, null)->processMessage(self::PSID, 'โอนแล้วค่ะ');

        $this->assertSame('web_only_payment_ack', $res['action']);
        $this->assertSame(WebHandoffCopy::SLIP_RECEIVED, $res['message']);
        $this->assertSame($bill->id, $res['pending_bill_id']);
        $this->assertSame(1, FortuneReading::count(), 'ห้ามสร้างบิลใหม่');
        $this->assertSame(0, UniquePaymentAmount::count(), 'ห้ามออก QR/ยอดใหม่');
        $this->assertSame(FortuneReading::STATUS_PENDING_PAYMENT, $bill->fresh()->conversation_status, 'บิลเดิมยังรอยอด (SMS/สลิปเข้าได้)');
        $this->assertSame(1, FortuneSavedQuestion::where('platform_user_id', self::PSID)->where('platform', 'facebook')->count());
    }

    public function test_บิลค้างระดับ_b_ขอเลขบัญชี_ได้กล่องห้อง_ไม่ส่งเลขบัญชี(): void
    {
        $settings = $this->webOnly();
        $bill = $this->reading(self::PSID, FortuneReading::STATUS_CELTIC_PENDING_PAYMENT, false);

        $res = $this->fcs($settings)->processMessage(self::PSID, 'ขอเลขบัญชีอีกทีค่ะ');

        $this->assertHandoff($res, 'tier B bank');
        $this->assertSame(1, FortuneReading::count());
        $this->assertSame(FortuneReading::STATUS_CELTIC_PENDING_PAYMENT, $bill->fresh()->conversation_status, 'ไม่แตะบิลค้าง (ปล่อยหมดอายุเอง)');
    }

    // ============================================================
    // 5. LINE ไม่เปลี่ยน / ธงปิดไม่เปลี่ยน
    // ============================================================

    public function test_line_ข้อความเดียวกันได้ผลเหมือนตอนธงปิด(): void
    {
        foreach (['ดูดวง', 'ราคาเท่าไหร่', 'สวัสดีค่ะ', 'โอนแล้วค่ะ'] as $text) {
            $off = $this->runLine($this->settings(['facebook_web_only' => false]), $text);
            $on = $this->runLine($this->webOnly(), $text);

            $this->assertSame($off, $on, "LINE '{$text}' ต้องได้ผลเหมือนเดิม");
            $this->assertNotTrue($on['web_only_gate'], "LINE '{$text}' ห้ามโดนด่าน");
        }
    }

    /**
     * รัน LINE หนึ่งข้อความบนฐานข้อมูลสะอาด แล้วคืน "ลายเซ็น" ของผลลัพธ์
     */
    private function runLine(FortuneTellingSetting $settings, string $text): array
    {
        FortuneReading::query()->delete();
        FortuneWebHandoff::query()->delete();
        Cache::flush();

        $res = $this->fcs($settings, 'line')->processMessage(self::LINE_UID, $text);

        return [
            'action' => $res['action'] ?? null,
            'readings' => FortuneReading::count(),
            'statuses' => FortuneReading::pluck('conversation_status')->all(),
            'web_only_gate' => $res['web_only_gate'] ?? false,
            'handoffs' => FortuneWebHandoff::count(),
        ];
    }

    public function test_ธงปิด_fb_เดินโฟลเดิม_ไม่มีกล่องห้อง(): void
    {
        $settings = $this->settings(['facebook_web_only' => false]);

        $res = $this->fcs($settings)->processMessage(self::PSID, 'ดูดวง');

        $this->assertArrayNotHasKey('web_only_gate', $res);
        $this->assertSame(0, FortuneWebHandoff::count());
    }

    // ============================================================
    // 6. fail-closed
    // ============================================================

    public function test_สร้างลิงก์ไม่ได้_ได้ข้อความ_fail_closed_และไม่มีบิล(): void
    {
        $settings = $this->webOnly();
        $this->mock(FortuneWebLinkService::class, function ($m) {
            $m->shouldReceive('isEnabledFor')->andReturn(true);
            $m->shouldReceive('generateChatLink')->andThrow(new \RuntimeException('APP_KEY หาย'));
        });
        $this->mock(LineAlertService::class, function ($m) {
            $m->shouldReceive('alertSystemError')->atLeast()->once();
        });

        foreach (['ดูดวง', '99', 'ขอเลขบัญชี'] as $text) {
            $res = $this->fcs($settings)->processMessage(self::PSID, $text);

            $this->assertSame('transfer_box', $res['action'], $text);
            $this->assertTrue($res['fail_closed'], "{$text}: ต้อง fail-closed");
            $this->assertSame(WebHandoffCopy::FAIL_CLOSED, $res['fb_template']['text']);
            $this->assertSame(['WEB_ROOM'], array_column($res['fb_template']['quick_replies'], 'payload'));
            $this->assertNoBillingHappened($res, 0, $text);
        }
    }

    // ============================================================
    // 7. คีย์เวิร์ด DB + directive ของ AI
    // ============================================================

    public function test_คีย์เวิร์ด_db_ที่ตอบเรื่องเงินถูกข้ามบน_fb_แต่_line_ยังได้(): void
    {
        $money = LineBotKeyword::create([
            'keyword' => 'fb_web_only_test_price',
            'trigger_words' => ['ทักทายแม่หมอ'],
            'response_type' => 'text',
            'response_text' => 'ค่าครู 39 บาท โอนได้เลยนะคะ',
            'category' => 'fortune',
            'priority' => 90,
            'is_active' => true,
        ]);
        $plain = LineBotKeyword::create([
            'keyword' => 'fb_web_only_test_plain',
            'trigger_words' => ['ทักทายแม่หมอ'],
            'response_type' => 'text',
            'response_text' => 'สวัสดีค่ะ แม่หมอยินดีที่ได้คุยด้วยนะคะ',
            'category' => 'fortune',
            'priority' => 10,
            'is_active' => true,
        ]);
        Cache::forget('fortune:db_keywords');

        $call = function (FortuneConversationService $fcs, string $uid) {
            $m = new \ReflectionMethod($fcs, 'checkDatabaseKeywords');
            $m->setAccessible(true);

            return $m->invoke($fcs, 'ทักทายแม่หมอค่ะ', $uid);
        };

        $settings = $this->webOnly();
        $this->assertSame($plain->id, $call($this->fcs($settings, null), self::PSID)?->id, 'FB ต้องข้ามคำตอบเรื่องเงิน');
        $this->assertSame($money->id, $call($this->fcs($settings, 'line'), self::LINE_UID)?->id, 'LINE ต้องได้เหมือนเดิม');

        $off = $this->settings(['facebook_web_only' => false]);
        $this->assertSame($money->id, $call($this->fcs($off, 'facebook'), self::PSID)?->id, 'ธงปิด = เหมือนเดิม');
    }

    public function test_directive_ของ_ai_แบบ_web_only(): void
    {
        $settings = $this->webOnly();
        $call = function (FortuneConversationService $fcs, string $platform, string $uid): string {
            $m = new \ReflectionMethod($fcs, 'buildTransferChatDirective');
            $m->setAccessible(true);

            return (string) $m->invoke($fcs, $platform, $uid);
        };

        $fb = $call($this->fcs($settings), 'facebook', self::PSID);
        $this->assertStringContainsString('ห้องแม่หมอ', $fb);
        $this->assertStringContainsString('ไม่ทำนาย', $fb);
        $this->assertStringContainsString('[OFFER_FORTUNE]', $fb);
        $this->assertDoesNotMatchRegularExpression('/\d+\s*(บาท|฿)/u', $fb, 'directive ห้ามมีราคา');

        // LINE ไม่ได้ directive web-only (โหมด daily ไม่ฉีดอะไรเลยเหมือนเดิม)
        $this->assertSame('', $call($this->fcs($settings, 'line'), 'line', self::LINE_UID));

        // ลูกค้าที่จ่ายแล้วกำลังส่งของ → ห้ามสั่ง "ไม่ทำนาย" ทับ แต่ยังห้ามพูดเงิน
        $this->reading(self::PSID, FortuneReading::STATUS_CELTIC_AWAITING_QUESTION, true);
        $legacy = $call($this->fcs($settings), 'facebook', self::PSID);
        $this->assertStringNotContainsString('ไม่ทำนาย', $legacy);
        $this->assertStringContainsString('ห้ามพูดเรื่องราคา', $legacy);

        $off = $this->settings(['facebook_web_only' => false]);
        $this->assertSame('', $call($this->fcs($off), 'facebook', '61550000000099'));
    }

    public function test_จัดกลุ่มเจตนา(): void
    {
        $fcs = $this->fcs($this->webOnly());
        $m = new \ReflectionMethod($fcs, 'classifyWebOnlyIntent');
        $m->setAccessible(true);

        $cases = [
            'โอนแล้วค่ะ' => 'payment_claim',
            'ส่งสลิปให้แล้วนะ' => 'payment_claim',
            'ทำไม่เป็นค่ะ' => 'cannot',
            '39' => 'reading',
            '99 ฿' => 'reading',
            'ราคาเท่าไหร่คะ' => 'money',
            'ขอเลขบัญชีหน่อย' => 'money',
            'ดูดวงความรักค่ะ' => 'reading',
            'อยากเปิดไพ่' => 'reading',
            'สวัสดีค่ะ' => 'chat',
            'วันนี้เหนื่อยจังเลยค่ะ' => 'chat',
            '' => 'chat',
        ];
        foreach ($cases as $text => $expected) {
            $this->assertSame($expected, $m->invoke($fcs, $text, self::PSID), "'{$text}'");
        }
    }

    // ============================================================
    // T5 — hard guard ที่จุดสร้างบิลทุกจุด (FbBillingGuard)
    // ============================================================

    /**
     * เรียกเมธอด protected/public ของ object ผ่าน reflection
     */
    private function invoke(object $obj, string $method, mixed ...$args): mixed
    {
        $m = new \ReflectionMethod($obj, $method);
        $m->setAccessible(true);

        return $m->invoke($obj, ...$args);
    }

    /**
     * บิลค้างจ่ายพร้อมยอดโอนที่จองไว้ (UPA reserved)
     */
    private function pendingBill(string $uid, string $type = FortuneReading::READING_TYPE_CELTIC_CROSS, bool $expired = false): FortuneReading
    {
        $status = $type === FortuneReading::READING_TYPE_CELTIC_CROSS
            ? FortuneReading::STATUS_CELTIC_PENDING_PAYMENT
            : FortuneReading::STATUS_PENDING_PAYMENT;
        $r = $this->reading($uid, $status, false, [], ['reading_type' => $type]);
        $upa = UniquePaymentAmount::generate($type === FortuneReading::READING_TYPE_CELTIC_CROSS ? 99.0 : 39.0, $r->id, 'fortune_reading', 180);
        $this->assertNotNull($upa, 'fixture: จองยอด UPA ไม่สำเร็จ');
        if ($expired) {
            $upa->forceFill(['expires_at' => now()->subMinutes(5)])->save();
        }
        $r->forceFill(['unique_payment_amount_id' => $upa->id, 'amount_paid' => $upa->unique_amount])->save();

        return $r->fresh();
    }

    private function reservedUpaCount(): int
    {
        return UniquePaymentAmount::where('status', 'reserved')->count();
    }

    private function assertMovedToWeb(FortuneReading $r, string $label): void
    {
        $r = $r->fresh();
        $this->assertSame(FortuneReading::STATUS_COMPLETED, $r->conversation_status, "{$label}: บิลต้องถูกปิด");
        $this->assertSame('moved_to_web', $r->getConversationState('cancellation_reason'), "{$label}: เหตุผลต้องเป็น moved_to_web");
    }

    public function test_จุดสร้างบิล_deep_เมนูจ่าย_และ_stripe_บน_fb_ถูกบล็อก_แม้ไม่ได้_set_platform(): void
    {
        $settings = $this->webOnly(['enable_stripe_payment' => true]);
        // FCS ที่ cron/job สร้างเอง — currentPlatform = 'line' ค่าเริ่มต้น แต่ลูกค้าเป็น FB
        $fcs = $this->fcs($settings, null);

        $cases = [
            'createPaymentBill' => [[]],
            'askPaymentMethod' => [],
            'startStripePaymentFlow' => ['th'],
            'startStripeForeignFallback' => [],
        ];
        foreach ($cases as $method => $args) {
            Cache::flush();
            $r = $this->reading(self::PSID, FortuneReading::STATUS_TIER_CHOICE, false, [], ['reading_type' => FortuneReading::READING_TYPE_DEEP]);

            $res = $this->invoke($fcs, $method, $r, ...$args);

            $this->assertHandoff($res, $method);
            $this->assertSame($method, $res['bill_guard'] ?? null, "{$method}: ต้องหยุดที่ hard guard ของจุดนี้");
            $this->assertSame(0, UniquePaymentAmount::count(), "{$method}: ห้ามจองยอดโอน");
            $this->assertSame(0, FortuneReading::whereNotNull('stripe_session_id')->count(), "{$method}: ห้ามสร้าง Stripe session");
            $this->assertMovedToWeb($r, $method);
        }

        Http::assertNotSent(fn ($req) => str_contains($req->url(), 'stripe.com'));
    }

    public function test_celtic_บน_fb_ไม่ออกบิล_ไม่จองยอด(): void
    {
        $fcs = $this->fcs($this->webOnly(['enable_celtic_cross' => true]), null);

        foreach (['startCelticCrossFlow', 'doStartCelticCrossFlow'] as $method) {
            Cache::flush();
            $r = $this->reading(self::PSID, FortuneReading::STATUS_TIER_CHOICE, false);

            $res = $this->invoke($fcs, $method, $r, true);

            $this->assertHandoff($res, $method);
            $this->assertSame('doStartCelticCrossFlow', $res['bill_guard'] ?? null);
            $this->assertSame(0, UniquePaymentAmount::count(), "{$method}: ห้ามจองยอด Celtic");
            $this->assertMovedToWeb($r, $method);
        }
    }

    public function test_celtic_ที่จ่ายแล้วยังไม่ใช้สิทธิ์_ยัง_resume_ได้_ไม่ออกบิลใหม่(): void
    {
        $fcs = $this->fcs($this->webOnly(['enable_celtic_cross' => true]));
        $paid = $this->reading(self::PSID, FortuneReading::STATUS_CELTIC_PICKING, true, ['celtic_pick_index' => 0], [
            'paid_at' => now()->subMinutes(10),
            'celtic_questions_used' => 0,
        ]);
        $new = $this->reading(self::PSID, FortuneReading::STATUS_TIER_CHOICE, false);

        $res = $this->invoke($fcs, 'doStartCelticCrossFlow', $new, true);

        $this->assertNotSame('transfer_box', $res['action'] ?? null, 'ของที่จ่ายแล้วต้องส่งต่อให้จบ ไม่ใช่พาไปเว็บ');
        $this->assertSame(0, UniquePaymentAmount::count());
        $this->assertSame(FortuneReading::STATUS_CELTIC_PICKING, $paid->fresh()->conversation_status);
        $this->assertSame(FortuneReading::STATUS_COMPLETED, $new->fresh()->conversation_status);
    }

    public function test_top_up_revive_reissue_บน_fb_ไม่จองยอดใหม่_แต่_line_และธงปิดยังทำได้(): void
    {
        $settings = $this->webOnly();
        $fcs = $this->fcs($settings, null);

        $bill = $this->pendingBill(self::PSID);
        $upaId = $bill->unique_payment_amount_id;

        $this->assertNull($this->invoke($fcs, 'createPartialTopupBill', $bill, 20.0), 'top-up บน FB ต้องได้ null');
        $this->assertNull($bill->reissueThaiQrAmount(), 'reissue QR บน FB ต้องได้ null');
        $this->assertSame(1, UniquePaymentAmount::count(), 'ห้ามมี UPA ใบใหม่');
        $this->assertSame($upaId, $bill->fresh()->unique_payment_amount_id);

        $dead = $this->reading(self::PSID, FortuneReading::STATUS_COMPLETED, false, [], ['bill_reference' => 'FTU-T5-DEAD']);
        $this->assertNull($dead->reviveBillForRepay(), 'revive บน FB ต้องได้ null');
        $this->assertSame(FortuneReading::STATUS_COMPLETED, $dead->fresh()->conversation_status);
        $this->assertSame(1, UniquePaymentAmount::count());

        // LINE ธงเปิด = พฤติกรรมเดิม
        $lineBill = $this->pendingBill(self::LINE_UID);
        $this->assertNotNull($lineBill->reissueThaiQrAmount(), 'LINE ต้องไม่โดน guard');
        $this->assertNotNull($this->invoke($this->fcs($settings, 'line'), 'createPartialTopupBill', $lineBill->fresh(), 20.0), 'LINE top-up ต้องได้');

        // FB ธงปิด = พฤติกรรมเดิม
        $off = $this->settings(['facebook_web_only' => false]);
        $fbOff = $this->pendingBill('61550000000077');
        $this->assertNotNull($fbOff->reissueThaiQrAmount(), 'ธงปิด FB ต้องทำได้ตามเดิม');
        $this->assertNotNull($this->invoke($this->fcs($off), 'createPartialTopupBill', $fbOff->fresh(), 20.0));
    }

    public function test_โอนขาดบน_fb_พักยอดให้แอดมิน_ไม่ออก_qr_เติม_ส่วน_line_ได้_top_up_ตามเดิม(): void
    {
        $this->mock(LineAlertService::class, function ($m) {
            $m->shouldReceive('alertSystemError')->once();
        });
        $settings = $this->webOnly();

        $bill = $this->pendingBill(self::PSID);
        $oldUpa = $bill->uniquePaymentAmount;

        $res = $this->invoke($this->fcs($settings, null), 'handlePartialPayment', $bill, ['amount' => 50.00, 'transRef' => 'T5-SHORT-FB-1'], 'facebook', self::PSID, 'active_bill');

        $this->assertSame('web_only_payment_ack', $res['action']);
        $this->assertSame(WebHandoffCopy::SLIP_RECEIVED, $res['message']);
        $this->assertDoesNotMatchRegularExpression(WebHandoffCopy::FORBIDDEN_REGEX, $res['message']);
        $fresh = $bill->fresh();
        $this->assertEqualsWithDelta(50.0, (float) $fresh->partial_paid_total, 0.001, 'เงินที่เข้ามาต้องถูกเครดิตไว้ ไม่หาย');
        $this->assertNotNull($fresh->partial_hold_at, 'ต้องพักให้แอดมินตรวจ');
        $this->assertSame(0, $this->reservedUpaCount(), 'ห้ามมียอดเติมที่จองไว้ (ไม่มี QR เติม)');
        $this->assertSame('cancelled', $oldUpa->fresh()->status, 'ยอดเดิมต้องถูกปลด กัน SMS จับคู่ระหว่างพัก');
        $this->assertSame(1, UniquePaymentAmount::count(), 'ห้ามสร้าง UPA ใบใหม่');
        $this->assertSame(1, FortuneSavedQuestion::where('platform', 'facebook')->count(), 'ต้องฝากเรื่องให้แอดมิน');

        // LINE ธงเปิด → top-up ตามเดิม (มียอดเติมใบใหม่)
        $lineBill = $this->pendingBill(self::LINE_UID);
        $lineRes = $this->invoke($this->fcs($settings, 'line'), 'handlePartialPayment', $lineBill, ['amount' => 50.00, 'transRef' => 'T5-SHORT-LINE-1'], 'line', self::LINE_UID, 'active_bill');
        $this->assertSame('partial_topup', $lineRes['action']);
        $lineFresh = $lineBill->fresh();
        $this->assertLessThan(60.0, (float) $lineFresh->amount_paid, 'LINE ต้องได้ยอดเติม (ส่วนที่ขาด ~49) แทนยอดเต็ม 99');
        $this->assertSame('reserved', $lineFresh->uniquePaymentAmount->status, 'LINE ต้องมียอดเติมที่จองไว้');
    }

    public function test_stripe_บน_fb_ไม่สร้าง_session_และทิ้ง_checkout_ไม่_reissue_qr(): void
    {
        $settings = $this->webOnly(['enable_stripe_payment' => false, 'enable_stripe_foreign_fallback' => true]);
        $stripe = new \App\Services\Fortune\FortuneStripeService($settings);

        $r = $this->reading(self::PSID, FortuneReading::STATUS_PENDING_PAYMENT, false, [], ['reading_type' => FortuneReading::READING_TYPE_DEEP]);
        $res = $stripe->createCheckoutSession($r, 'foreign');
        $this->assertFalse($res['success']);
        $this->assertSame('fb_web_only', $res['error']);
        $this->assertNull($r->fresh()->stripe_session_id);
        Http::assertNotSent(fn ($req) => str_contains($req->url(), 'stripe.com'));

        // ทิ้งหน้าจ่ายบัตร (เลนต่างประเทศ เมนูปิด) — เดิมจะจองยอด QR ไทยใหม่ + คืนสถานะรอจ่าย
        $abandoned = $this->reading(self::PSID, FortuneReading::STATUS_PENDING_STRIPE_PAYMENT, false, [], [
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            'bill_reference' => 'FTU-T5-STRIPE',
        ]);
        $stripe->restoreAfterCheckoutAbandoned($abandoned, 'cancel_page');
        $this->assertSame(0, UniquePaymentAmount::count(), 'ห้ามจองยอด QR ไทยใหม่บน FB');
        $this->assertMovedToWeb($abandoned, 'stripe abandon');

        // LINE ธงเปิด = คืนสถานะ + จองยอดใหม่ตามเดิม
        $line = $this->reading(self::LINE_UID, FortuneReading::STATUS_PENDING_STRIPE_PAYMENT, false, [], [
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            'bill_reference' => 'FTU-T5-STRIPE-L',
        ]);
        $stripe->restoreAfterCheckoutAbandoned($line, 'cancel_page');
        $this->assertSame(FortuneReading::STATUS_PENDING_PAYMENT, $line->fresh()->conversation_status);
        $this->assertSame(1, $this->reservedUpaCount(), 'LINE ต้องได้ยอด QR ไทยใหม่');
    }

    public function test_กลับจากเลนบัตรบน_fb_ไม่จองยอด_qr_ใหม่(): void
    {
        $fcs = $this->fcs($this->webOnly(), null);
        $r = $this->reading(self::PSID, FortuneReading::STATUS_PENDING_STRIPE_PAYMENT, false, [], [
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'bill_reference' => 'FTU-T5-BACK',
        ]);

        $res = $this->invoke($fcs, 'returnFromStripeForeignFallback', $r);

        $this->assertHandoff($res, 'returnFromStripeForeignFallback');
        $this->assertSame(0, UniquePaymentAmount::count());
        $this->assertMovedToWeb($r, 'returnFromStripeForeignFallback');
    }

    public function test_บิล_fb_หมดอายุ_ไม่ส่ง_dm_ไม่แบน_เหตุผล_moved_to_web(): void
    {
        $this->webOnly();
        $this->mock(\App\Services\FortuneChannelManager::class, function ($m) {
            $m->shouldNotReceive('getPlatform');
        });
        $this->mock(\App\Services\Fortune\BillTrollGuardService::class, function ($m) {
            $m->shouldNotReceive('maybeBanAfterUnpaidCancel');
        });
        $this->mock(\App\Services\FcmNotificationService::class, function ($m) {
            $m->shouldReceive('notifyFortuneReadingCancelled')->once();
        });

        $bill = $this->pendingBill(self::PSID, FortuneReading::READING_TYPE_DEEP, true);

        $this->assertSame(1, FortuneReading::cancelExpiredPendingBills());

        $this->assertMovedToWeb($bill, 'expire');
        $this->assertSame('cancelled', $bill->uniquePaymentAmount->fresh()->status, 'ยังต้องปลดยอดที่จองไว้');
        $this->assertFalse((bool) $bill->fresh()->getConversationState('cancel_warning_sent'), 'ห้ามส่งข้อความเตือนสติ');
    }

    public function test_บิล_fb_หมดอายุตอนธงปิด_ยังเตือนและเช็คแบนตามเดิม(): void
    {
        $this->settings(['facebook_web_only' => false]);
        $this->mock(\App\Services\FortuneChannelManager::class, function ($m) {
            $m->shouldReceive('getPlatform')->once()->andReturn(null);
        });
        $this->mock(\App\Services\Fortune\BillTrollGuardService::class, function ($m) {
            $m->shouldReceive('maybeBanAfterUnpaidCancel')->once();
        });
        $this->mock(\App\Services\FcmNotificationService::class, function ($m) {
            $m->shouldReceive('notifyFortuneReadingCancelled')->once();
        });

        $bill = $this->pendingBill(self::PSID, FortuneReading::READING_TYPE_DEEP, true);

        $this->assertSame(1, FortuneReading::cancelExpiredPendingBills());
        $this->assertSame('auto_expired', $bill->fresh()->getConversationState('cancellation_reason'));
    }

    public function test_แอดมินออกบิลส่วนต่างให้ลูกค้า_fb_ไม่ได้(): void
    {
        $fcs = $this->fcs($this->webOnly(), null);
        $r = $this->reading(self::PSID, FortuneReading::STATUS_COMPLETED, true, [], ['reading_type' => FortuneReading::READING_TYPE_DEEP]);

        $res = $this->invoke($fcs, 'applyAdminSwitchCharge', $r, FortuneReading::READING_TYPE_CELTIC_CROSS, 39.0, 99.0, []);

        $this->assertFalse($res['ok']);
        $this->assertSame(0, UniquePaymentAmount::count());
        $this->assertTrue((bool) $r->fresh()->is_paid, 'บิลที่จ่ายแล้วต้องไม่ถูกแตะ');
    }

    public function test_fb_billing_guard_ตัดสินตามช่องทางที่_resolve_ได้(): void
    {
        $this->webOnly();
        $fb = $this->reading(self::PSID, FortuneReading::STATUS_TIER_CHOICE, false);
        $line = $this->reading(self::LINE_UID, FortuneReading::STATUS_TIER_CHOICE, false);

        $this->assertTrue(FbBillingGuard::appliesTo($fb));
        $this->assertTrue(FbBillingGuard::appliesTo(null, self::PSID, 'line'), 'PSID ตัวเลข = FB แม้ hint บอก line');
        $this->assertFalse(FbBillingGuard::appliesTo($line));
        $this->assertFalse(FbBillingGuard::appliesTo(null, self::LINE_UID, 'facebook'));
        $this->assertFalse(FbBillingGuard::appliesTo(null, 'tg_12345', 'facebook'), 'Telegram นอกขอบเขต');

        $this->settings(['facebook_web_only' => false]);
        $this->assertFalse(FbBillingGuard::appliesTo($fb));
    }
}
