<?php

namespace Tests\Feature;

use App\Models\FortuneTellingSetting;
use App\Models\JuntraChatSlip;
use App\Models\SlipVerification;
use App\Services\FacebookWebhookService;
use App\Services\Juntra\JuntraChatSlipRelay;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\Concerns\BuildsJuntraServerSchema;
use Tests\TestCase;

/**
 * 🧾 สลิปที่ลูกค้าส่งในแชทเพจ → เติมเงินเว็บจันทรา (2026-11-10)
 *
 * ล็อกกติกา:
 *  1. สลิปจริง เข้าบัญชีเรา ยังไม่เคยใช้ → จองเลขอ้างอิงทันที (ใช้ซ้ำข้ามระบบไม่ได้) + พักรอเว็บ
 *  2. กดกริ่งเว็บด้วยเลขอ้างอิงอย่างเดียว · ผลมาจากที่เว็บรายงานกลับ (/resolve) เท่านั้น
 *  3. ส่งรูปเดิมซ้ำ = แถวเดิม ไม่จองซ้ำ · สลิปที่บอท/ระบบอื่นใช้แล้ว = ซ้ำ ไม่พัก
 *  4. /slips/claim ผูกสลิปที่พักเข้าบิลเว็บได้ครั้งเดียว (ต้องมี chat_owner ตรง)
 *  5. /resolve ครั้งเดียว — รายงานซ้ำผลเดิมผ่าน ผลอื่น 409
 *  6. ข้อความตอบลูกค้าต้องไม่โดนด่านเงินชั้นส่งของ FB web-only
 */
class JuntraChatSlipRelayTest extends TestCase
{
    use BuildsJuntraServerSchema;

    private const REF = '015314101010BTF09999';

    private const PSID = '61550000000123';

    private const JUNTRA = 'https://jw.test';

    private const JUNTRA_REDIRECT = 'https://xn--82c4af5bzdj.online/auth/thaiprompt/callback';

    private static ?array $passportKeys = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildJuntraServerSchema();
        (require database_path('migrations/2026_11_10_000000_create_juntra_chat_slips_table.php'))->up();

        if (self::$passportKeys === null) {
            $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            openssl_pkey_export($key, $private);
            self::$passportKeys = [$private, openssl_pkey_get_details($key)['key']];
        }

        config([
            'passport.private_key' => self::$passportKeys[0],
            'passport.public_key' => self::$passportKeys[1],
            'services.juntra.server_client_ids' => [],
            'services.juntra.url' => self::JUNTRA,
            'services.juntra.chat_slip_relay' => true,
        ]);

        Storage::fake('local');
        Storage::disk('local')->put('fortune/slips/fbweb_test.jpg', 'fake-slip-bytes-'.uniqid());
        Cache::flush();
    }

    protected function tearDown(): void
    {
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    // ─── helpers ────────────────────────────────────────────────────────

    private function slipOkBody(array $overrides = []): array
    {
        return [
            'success' => true,
            'message' => '✅',
            'data' => array_merge([
                'success' => true,
                'transRef' => self::REF,
                'amount' => 100.00,
                'sendingBank' => '014',
                'receivingBank' => '004',
                'transTimestamp' => now()->utc()->format('Y-m-d\TH:i:s.v\Z'),
                'sender' => ['displayName' => 'นาย ทดสอบ ผู้โอน', 'name' => 'MR. TEST'],
                'receiver' => [
                    'displayName' => 'จันทรา พยากรณ์',
                    'name' => 'CHANTRA',
                    'account' => ['value' => 'xxx-x-x5514-x'],
                ],
            ], $overrides),
        ];
    }

    /** @param  callable|null  $onPing  จำลองเว็บจันทรา (เช่น รายงานผลกลับก่อนตอบกริ่ง) */
    private function fakeHttp(array $slipOk = [], ?callable $onPing = null, int $pingStatus = 200): void
    {
        Http::fake([
            'api.slipok.com/*' => Http::response($this->slipOkBody($slipOk)),
            'jw.test/*' => function (HttpRequest $req) use ($onPing, $pingStatus) {
                if ($onPing) {
                    $onPing($req);
                }

                return Http::response(['ok' => true], $pingStatus);
            },
        ]);
    }

    private function relay(): array
    {
        return app(JuntraChatSlipRelay::class)->handle('facebook', self::PSID, 'fortune/slips/fbweb_test.jpg');
    }

    private function owner(): string
    {
        return JuntraChatSlip::ownerKey('facebook', self::PSID);
    }

    private function actingAsJuntraServer(): void
    {
        $client = app(ClientRepository::class)->create(null, 'Juntra Chantra SSO', self::JUNTRA_REDIRECT, 'oauth_users');
        Passport::actingAsClient($client, [], 'api-oauth');
    }

    private function server(string $path, array $body)
    {
        return $this->withToken('server-token')->postJson('/api/v1/juntra/server/'.$path, $body);
    }

    // ─── 1–3. relay ─────────────────────────────────────────────────────

    public function test_a_good_slip_is_claimed_at_once_parked_and_the_web_is_rung_with_the_ref_only(): void
    {
        $this->fakeHttp();

        $result = $this->relay();

        $this->assertSame(JuntraChatSlipRelay::PARKED, $result['outcome']);
        $this->assertSame(100.0, $result['amount']);

        $sv = SlipVerification::where('trans_ref', self::REF)->firstOrFail();
        $this->assertSame('juntraweb', $sv->consumed_by_platform);
        $this->assertSame($this->owner(), $sv->consumed_by_user_id);
        $this->assertSame('chat_slip', $sv->raw['source']);

        $row = JuntraChatSlip::where('trans_ref', self::REF)->firstOrFail();
        $this->assertSame(JuntraChatSlip::PARKED, $row->status);
        $this->assertSame('100.00', (string) $row->amount);
        $this->assertSame(1, $row->ping_attempts);

        Http::assertSent(fn (HttpRequest $r) => $r->url() === self::JUNTRA.'/api/server/chat-slips/ping'
            && $r->data() === ['trans_ref' => self::REF]);
    }

    public function test_the_outcome_comes_from_what_the_web_reported_back(): void
    {
        $this->fakeHttp(onPing: function () {
            // เว็บจันทราดึงข้อมูล จับคู่บิล เติมเงิน แล้วรายงานกลับก่อนตอบกริ่ง
            JuntraChatSlip::where('trans_ref', self::REF)->update([
                'status' => JuntraChatSlip::CREDITED,
                'topup_ref' => 'TUP-UD38XNEM',
                'credited_amount' => 60.59,
                'resolved_at' => now(),
            ]);
        });

        $result = $this->relay();

        $this->assertSame(JuntraChatSlipRelay::CREDITED, $result['outcome']);
        $this->assertSame('TUP-UD38XNEM', $result['topup_ref']);
        $this->assertSame(60.59, $result['credited']);

        $text = JuntraChatSlipRelay::customerMessage($result);
        $this->assertStringContainsString('60.59', $text);
        $this->assertStringContainsString('TUP-UD38XNEM', $text);
        $this->assertStringContainsString('39.41', $text, 'ส่วนที่โอนเกินต้องบอกลูกค้า');
    }

    public function test_a_failed_ring_leaves_the_slip_parked_for_the_web_sweeper(): void
    {
        $this->fakeHttp(pingStatus: 502);

        $result = $this->relay();

        $this->assertSame(JuntraChatSlipRelay::PARKED, $result['outcome']);
        $this->assertSame(JuntraChatSlip::PARKED, JuntraChatSlip::where('trans_ref', self::REF)->value('status'));
    }

    public function test_sending_the_same_slip_again_reuses_the_parked_row(): void
    {
        $this->fakeHttp();

        $this->relay();
        $again = $this->relay();

        $this->assertSame(JuntraChatSlipRelay::PARKED, $again['outcome']);
        $this->assertSame(1, SlipVerification::where('trans_ref', self::REF)->count());
        $this->assertSame(1, JuntraChatSlip::count());
        $this->assertSame(2, JuntraChatSlip::first()->ping_attempts);
    }

    public function test_a_slip_the_bot_already_used_is_a_duplicate_and_is_not_parked(): void
    {
        SlipVerification::create([
            'trans_ref' => self::REF,
            'fortune_reading_id' => 77,
            'consumed_by_platform' => 'facebook',
            'consumed_by_user_id' => 'PSID-OTHER',
            'amount' => 100,
            'status' => 'verified',
            'raw' => ['source' => 'bot'],
            'verified_at' => Carbon::now(),
        ]);
        $this->fakeHttp();

        $result = $this->relay();

        $this->assertSame(JuntraChatSlipRelay::DUPLICATE, $result['outcome']);
        $this->assertSame(0, JuntraChatSlip::count());
        Http::assertNotSent(fn (HttpRequest $r) => str_starts_with($r->url(), self::JUNTRA));
    }

    public function test_a_slip_another_customer_parked_is_a_duplicate(): void
    {
        $this->fakeHttp();
        app(JuntraChatSlipRelay::class)->handle('facebook', 'SOMEONE-ELSE', 'fortune/slips/fbweb_test.jpg');
        Cache::flush(); // ล้าง hash cache/flood ของ SlipOK — จำลองคนละคนส่งรูปเดียวกัน

        $result = $this->relay();

        $this->assertSame(JuntraChatSlipRelay::DUPLICATE, $result['outcome']);
        $this->assertSame(1, JuntraChatSlip::count());
        $this->assertSame('SOMEONE-ELSE', JuntraChatSlip::first()->platform_user_id);
    }

    public function test_a_slip_to_someone_elses_account_is_not_parked(): void
    {
        // SlipOK (log:true) ตัดสินเองว่าปลายทางไม่ใช่บัญชีที่ผูกไว้ → code 1014
        Http::fake([
            'api.slipok.com/*' => Http::response(['success' => false, 'code' => 1014, 'message' => 'บัญชีผู้รับไม่ตรงกับบัญชีร้านค้า'], 400),
            'jw.test/*' => Http::response(['ok' => true]),
        ]);

        $result = $this->relay();

        $this->assertSame(JuntraChatSlipRelay::WRONG_ACCOUNT, $result['outcome']);
        $this->assertSame(0, SlipVerification::count());
        $this->assertSame(0, JuntraChatSlip::count());
    }

    public function test_switched_off_relay_never_calls_slipok(): void
    {
        config(['services.juntra.chat_slip_relay' => false]);
        $this->fakeHttp();

        $this->assertSame(JuntraChatSlipRelay::UNVERIFIED, $this->relay()['outcome']);
        Http::assertNothingSent();
    }

    // ─── 4. /slips/claim + chat_owner ───────────────────────────────────

    private function claim(array $overrides = [])
    {
        return $this->server('slips/claim', array_merge([
            'trans_ref' => self::REF,
            'amount' => 100,
            'user_ref' => '15',
            'topup_ref' => 'TUP-UD38XNEM',
            'chat_owner' => $this->owner(),
        ], $overrides));
    }

    public function test_claim_with_the_chat_owner_binds_the_parked_slip_to_the_web_bill_once(): void
    {
        $this->fakeHttp();
        $this->relay();
        $this->actingAsJuntraServer();

        $this->claim()->assertStatus(201)->assertJsonPath('data.from_chat', true);

        $sv = SlipVerification::where('trans_ref', self::REF)->firstOrFail();
        $this->assertSame('15', $sv->consumed_by_user_id);
        $this->assertSame('TUP-UD38XNEM', $sv->raw['topup_ref']);
        $this->assertSame($this->owner(), $sv->raw['bound_from']);

        // ซ้ำบิลเดิม = idempotent · บิลอื่น = ใช้แล้ว
        $this->claim()->assertOk()->assertJsonPath('data.idempotent', true);
        $this->claim(['topup_ref' => 'TUP-OTHER000'])->assertStatus(409);
    }

    public function test_claim_without_the_matching_chat_owner_cannot_take_a_parked_slip(): void
    {
        $this->fakeHttp();
        $this->relay();
        $this->actingAsJuntraServer();

        $this->claim(['chat_owner' => null])->assertStatus(409);
        $this->claim(['chat_owner' => 'chat:facebook:SOMEONE-ELSE'])->assertStatus(409);

        $this->assertSame($this->owner(), SlipVerification::where('trans_ref', self::REF)->value('consumed_by_user_id'));
    }

    // ─── 5. fetch / pending / resolve ───────────────────────────────────

    public function test_fetch_and_pending_give_the_web_the_checked_slip(): void
    {
        $this->fakeHttp();
        $this->relay();
        $this->actingAsJuntraServer();

        $this->server('chat-slips/fetch', ['trans_ref' => self::REF])
            ->assertOk()
            ->assertJsonPath('data.status', 'parked')
            ->assertJsonPath('data.platform', 'facebook')
            ->assertJsonPath('data.platform_user_id', self::PSID)
            ->assertJsonPath('data.chat_owner', $this->owner())
            ->assertJsonPath('data.amount', 100)
            ->assertJsonPath('data.receiver_matches', true)
            ->assertJsonPath('data.used', false);

        // ตัวกวาดเห็นเฉพาะที่พักนานกว่า 1 นาที (กันชนกับกริ่ง)
        $this->server('chat-slips/pending', [])->assertOk()->assertJsonCount(0, 'data');
        $this->travel(2)->minutes();
        $this->server('chat-slips/pending', [])->assertOk()->assertJsonCount(1, 'data');

        $this->server('chat-slips/fetch', ['trans_ref' => 'NOPE123'])->assertNotFound();
    }

    public function test_resolve_is_once_only_and_review_holds_the_slip_for_that_bill(): void
    {
        $this->fakeHttp();
        $this->relay();
        $this->actingAsJuntraServer();

        $body = ['trans_ref' => self::REF, 'outcome' => 'review', 'user_ref' => '15', 'topup_ref' => 'TUP-UD38XNEM', 'note' => 'ยอดไม่ตรง'];

        $this->server('chat-slips/resolve', $body)->assertOk()->assertJsonPath('data.status', 'review');
        $this->server('chat-slips/resolve', $body)->assertOk()->assertJsonPath('data.idempotent', true);
        $this->server('chat-slips/resolve', ['outcome' => 'credited'] + $body)->assertStatus(409);

        $sv = SlipVerification::where('trans_ref', self::REF)->firstOrFail();
        $this->assertSame('15', $sv->consumed_by_user_id);
        $this->assertSame('TUP-UD38XNEM', $sv->raw['topup_ref']);

        // มีผลแล้ว = ตัวกวาดไม่หยิบอีก
        $this->travel(2)->minutes();
        $this->server('chat-slips/pending', [])->assertJsonCount(0, 'data');
    }

    public function test_chat_slip_endpoints_need_the_juntra_server_token(): void
    {
        $this->postJson('/api/v1/juntra/server/chat-slips/pending', [])->assertStatus(401);
        $this->postJson('/api/v1/juntra/server/chat-slips/resolve', ['trans_ref' => self::REF, 'outcome' => 'credited'])->assertStatus(401);
    }

    // ─── 6. ข้อความถึงลูกค้า ─────────────────────────────────────────────

    public function test_customer_messages_never_trip_the_fb_web_only_money_gate(): void
    {
        $outcomes = [
            ['outcome' => 'credited', 'amount' => 100.0, 'credited' => 60.59, 'topup_ref' => 'TUP-UD38XNEM'],
            ['outcome' => 'credited', 'amount' => 39.0, 'credited' => 39.0, 'topup_ref' => 'TUP-HA9UNISF'],
            ['outcome' => 'review', 'amount' => 20.0],
            ['outcome' => 'parked', 'amount' => 20.0],
            ['outcome' => 'no_user', 'amount' => 20.0],
            ['outcome' => 'duplicate', 'amount' => 20.0],
            ['outcome' => 'wrong_account', 'amount' => 20.0],
            ['outcome' => 'too_old', 'amount' => 20.0],
        ];

        foreach ($outcomes as $r) {
            foreach ([false, true] as $withGuide) {
                $text = JuntraChatSlipRelay::customerMessage($r, $withGuide);
                $this->assertNotNull($text, $r['outcome']);
                $this->assertFalse(FacebookWebhookService::containsMoneyText($text), $r['outcome'].': '.$text);
            }
        }

        $this->assertNull(JuntraChatSlipRelay::customerMessage(['outcome' => 'unverified']));
        $this->assertNull(JuntraChatSlipRelay::customerMessage(['outcome' => 'unverified'], true));
    }

    /**
     * 🧾 (2026-11-11) เคสจริง: ข้อความ "ทีมงานกำลังเติมให้" อย่างเดียว → ลูกค้าคิดว่ายังไม่เข้า แล้วจ่ายซ้ำบนเว็บ
     */
    public function test_waiting_messages_tell_the_customer_not_to_repeat_and_how_to_use_the_web(): void
    {
        foreach (['review', 'parked', 'no_user'] as $outcome) {
            $text = JuntraChatSlipRelay::customerMessage(['outcome' => $outcome, 'amount' => 39.92]);
            $this->assertStringContainsString('ไม่ต้องทำรายการซ้ำ', $text, $outcome);
            $this->assertStringContainsString('ได้รับหลักฐานจากแชทเพจแล้ว', $text, $outcome.': ตรงกับข้อความบนหน้าเว็บ');
        }

        foreach (JuntraChatSlipRelay::GUIDE_OUTCOMES as $outcome) {
            $plain = JuntraChatSlipRelay::customerMessage(['outcome' => $outcome, 'amount' => 39.92, 'credited' => 39.92]);
            $guided = JuntraChatSlipRelay::customerMessage(['outcome' => $outcome, 'amount' => 39.92, 'credited' => 39.92], true);
            $this->assertStringContainsString('ครั้งหน้าไม่ต้องส่งรูปมาที่แชทนี้', $plain, $outcome);
            $this->assertStringNotContainsString('ส่งรูปวิธีทำไว้ให้ด้านล่าง', $plain, $outcome.': ไม่ส่งรูป = ห้ามอ้างถึงรูป');
            $this->assertStringContainsString('ส่งรูปวิธีทำไว้ให้ด้านล่าง', $guided, $outcome);
        }

        // ผิดบัญชี/เก่าเกิน ไม่ใช่เรื่องใช้เว็บผิด — ไม่สอน ไม่อ้างรูป
        $this->assertNotContains('wrong_account', JuntraChatSlipRelay::GUIDE_OUTCOMES);
        $this->assertStringNotContainsString('ด้านล่างแล้ว', JuntraChatSlipRelay::customerMessage(['outcome' => 'wrong_account'], true));
    }

    public function test_the_guide_image_exists_and_is_never_taken_for_a_payment_image(): void
    {
        $path = strtok(JuntraChatSlipRelay::GUIDE_IMAGE_PATH, '?');
        $this->assertFileExists(public_path($path));
        $this->assertSame(0, preg_match(FacebookWebhookService::FB_WEB_ONLY_MONEY_URL_PATTERN, asset(JuntraChatSlipRelay::GUIDE_IMAGE_PATH)));
    }
}
