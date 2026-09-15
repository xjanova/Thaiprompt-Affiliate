<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\SlipVerification;
use App\Models\User;
use App\Services\Fortune\SlipOkService;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository;
use Laravel\Sanctum\Sanctum;
use League\OAuth2\Server\ResourceServer;
use Mockery;
use Psr\Http\Message\ServerRequestInterface;
use Tests\Concerns\BuildsJuntraServerSchema;
use Tests\TestCase;

/**
 * 🧾 /api/v1/juntra/server/slips/* + ประตู 'juntra.server' (CONTRACT §A, §B)
 *
 * เป้าหมายเจ้าของ: สลิปหนึ่งใบใช้ได้ครั้งเดียว ข้ามบอทแม่หมอจันทรา + วอลเลตเว็บ จันทรา.online
 *   ห้ามตอบ "ใช้แล้ว" จากการเดา — ต้องมีหลักฐานเชิงบวก (registry / QR / SMS ที่ตัดบิลแล้ว)
 *
 * รันบน sqlite :memory: เท่านั้น (ดู BuildsJuntraServerSchema)
 */
class JuntraServerSlipApiTest extends TestCase
{
    use BuildsJuntraServerSchema;

    private const JUNTRA_REDIRECT = 'https://xn--82c4af5bzdj.online/auth/thaiprompt/callback';

    private const REF = '015258144839BTF01234';

    /** @var array{0: string, 1: string}|null คีย์ RSA ของ Passport (สร้างครั้งเดียวต่อคลาส) */
    private static ?array $passportKeys = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildJuntraServerSchema();
        $this->usePassportKeys();

        config([
            'services.juntra.server_client_ids' => [],
            'smschecker.local_qr_precheck' => true,
        ]);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        FortuneTellingSetting::clearSettingsCache();
        SlipVerification::flushEventListeners();

        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────────
    // helpers
    // ─────────────────────────────────────────────────────────────────────

    private function usePassportKeys(): void
    {
        if (self::$passportKeys === null) {
            $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            openssl_pkey_export($key, $private);
            self::$passportKeys = [$private, openssl_pkey_get_details($key)['key']];
        }

        config([
            'passport.private_key' => self::$passportKeys[0],
            'passport.public_key' => self::$passportKeys[1],
        ]);
    }

    private function makeClient(string $redirect = self::JUNTRA_REDIRECT, bool $revoked = false): Client
    {
        $client = app(ClientRepository::class)->create(null, 'Juntra Chantra SSO', $redirect, 'oauth_users');

        if ($revoked) {
            $client->forceFill(['revoked' => true])->save();
        }

        return $client;
    }

    /** ทำตัวเป็นเซิร์ฟเวอร์จันทรา (token client_credentials ของ client ที่ให้มา) */
    private function actingAsServer(?Client $client = null): Client
    {
        $client ??= $this->makeClient();
        // guard 'api' ของโปรเจกต์นี้ไม่ใช่ Passport — ใช้ 'api-oauth' (driver passport)
        Passport::actingAsClient($client, [], 'api-oauth');

        return $client;
    }

    private function slipOkResponse(array $dataOverrides = [], bool $success = true, ?int $code = null, int $status = 200): void
    {
        $body = [
            'success' => $success,
            'message' => $success ? '✅' : 'error',
            'data' => array_merge([
                'success' => $success,
                'transRef' => self::REF,
                'amount' => 100.37,
                'sendingBank' => '014',
                'receivingBank' => '004',
                'transTimestamp' => now()->utc()->format('Y-m-d\TH:i:s.v\Z'),
                'sender' => ['displayName' => 'นาย ทดสอบ ผู้โอน', 'name' => 'MR. TEST'],
                'receiver' => [
                    'displayName' => 'จันทรา พยากรณ์',
                    'name' => 'CHANTRA',
                    'account' => ['value' => 'xxx-x-x5514-x'],
                ],
            ], $dataOverrides),
        ];
        if ($code !== null) {
            $body['code'] = $code;
        }

        Http::fake(['api.slipok.com/*' => Http::response($body, $status)]);
    }

    private function slipImage(): UploadedFile
    {
        return UploadedFile::fake()->image('slip.jpg', 480, 800);
    }

    /** รูปสลิปที่มี QR แบบ EMVCo ฝังเลขอ้างอิงไว้ (ให้ด่านถอด QR เองอ่านได้) */
    private function slipImageWithQr(string $transRef): UploadedFile
    {
        $tlv = fn (string $tag, string $value) => $tag.str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT).$value;
        $payload = $tlv('00', $tlv('00', '000001').$tlv('01', '004').$tlv('02', $transRef))
            .$tlv('51', 'TH').$tlv('91', '87A7');

        $png = (new Writer(new GDLibRenderer(400)))->writeString($payload);

        return UploadedFile::fake()->createWithContent('slip.png', $png);
    }

    private function botUsedSlip(string $ref = self::REF, int $readingId = 77): SlipVerification
    {
        return SlipVerification::create([
            'trans_ref' => $ref,
            'fortune_reading_id' => $readingId,
            'consumed_by_platform' => 'facebook',
            'consumed_by_user_id' => 'PSID-1',
            'amount' => 99.07,
            'status' => 'verified',
            'raw' => ['source' => 'bot'],
            'verified_at' => Carbon::parse('2026-09-14 10:00:00'),
        ]);
    }

    private function verifySlip(UploadedFile $file, string $userRef = 'U-1')
    {
        return $this->withToken('server-token')->post('/api/v1/juntra/server/slips/verify', [
            'slip' => $file,
            'user_ref' => $userRef,
            'expected_amount' => '100.37',
        ], ['Accept' => 'application/json']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // A. juntra.server
    // ─────────────────────────────────────────────────────────────────────

    public function test_rejects_request_without_token(): void
    {
        $this->postJson('/api/v1/juntra/server/slips/check', ['refs' => ['ABC1234567']])
            ->assertStatus(401)
            ->assertJsonPath('reason_code', 'unauthenticated');
    }

    public function test_rejects_a_forged_bearer_token(): void
    {
        $this->withToken('not-a-real-jwt')
            ->postJson('/api/v1/juntra/server/slips/check', ['refs' => ['ABC1234567']])
            ->assertStatus(401)
            ->assertJsonPath('reason_code', 'unauthenticated');
    }

    public function test_rejects_a_user_token_even_from_the_juntraweb_client(): void
    {
        $client = $this->makeClient();

        $token = new Token;
        $token->forceFill(['id' => 'tok-user', 'user_id' => 42, 'client_id' => $client->getKey(), 'revoked' => false]);

        $server = Mockery::mock(ResourceServer::class);
        $server->shouldReceive('validateAuthenticatedRequest')->andReturnUsing(
            fn (ServerRequestInterface $r) => $r->withAttribute('oauth_client_id', (string) $client->getKey())
                ->withAttribute('oauth_access_token_id', 'tok-user')
                ->withAttribute('oauth_user_id', '42')
                ->withAttribute('oauth_scopes', [])
        );
        $this->app->instance(ResourceServer::class, $server);

        $repo = Mockery::mock(TokenRepository::class);
        $repo->shouldReceive('find')->andReturn($token);
        $this->app->instance(TokenRepository::class, $repo);

        $this->withToken('user-token')
            ->postJson('/api/v1/juntra/server/slips/check', ['refs' => ['ABC1234567']])
            ->assertStatus(403)
            ->assertJsonPath('reason_code', 'forbidden_client');
    }

    public function test_rejects_a_client_that_is_not_juntraweb(): void
    {
        $this->actingAsServer($this->makeClient('https://evil.example/cb?next=xn--82c4af5bzdj.online'));

        $this->withToken('server-token')
            ->postJson('/api/v1/juntra/server/slips/check', ['refs' => ['ABC1234567']])
            ->assertStatus(403)
            ->assertJsonPath('reason_code', 'forbidden_client');
    }

    public function test_rejects_a_revoked_juntraweb_client(): void
    {
        $this->actingAsServer($this->makeClient(self::JUNTRA_REDIRECT, revoked: true));

        $this->withToken('server-token')
            ->postJson('/api/v1/juntra/server/slips/check', ['refs' => ['ABC1234567']])
            ->assertStatus(403)
            ->assertJsonPath('reason_code', 'forbidden_client');
    }

    public function test_allowlist_overrides_the_redirect_rule(): void
    {
        $juntra = $this->makeClient();
        $other = $this->makeClient('https://ops.example/cb');
        config(['services.juntra.server_client_ids' => [(string) $other->getKey()]]);

        $this->actingAsServer($juntra);
        $this->withToken('server-token')
            ->postJson('/api/v1/juntra/server/slips/check', ['refs' => ['ABC1234567']])
            ->assertStatus(403);

        $this->actingAsServer($other);
        $this->withToken('server-token')
            ->postJson('/api/v1/juntra/server/slips/check', ['refs' => ['ABC1234567']])
            ->assertOk();
    }

    public function test_real_client_credentials_token_from_oauth_token_is_accepted_until_revoked(): void
    {
        $client = $this->makeClient();

        $issued = $this->post('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'scope' => '',
        ]);
        $issued->assertOk()->assertJsonStructure(['token_type', 'expires_in', 'access_token']);
        $accessToken = $issued->json('access_token');

        $this->withToken($accessToken)
            ->postJson('/api/v1/juntra/server/slips/check', ['refs' => ['ABC1234567']])
            ->assertOk()
            ->assertExactJson(['data' => ['used' => []]]);

        DB::table('oauth_access_tokens')->update(['revoked' => true]);

        $this->withToken($accessToken)
            ->postJson('/api/v1/juntra/server/slips/check', ['refs' => ['ABC1234567']])
            ->assertStatus(401)
            ->assertJsonPath('reason_code', 'unauthenticated');
    }

    // ─────────────────────────────────────────────────────────────────────
    // B1. slips/verify
    // ─────────────────────────────────────────────────────────────────────

    public function test_verify_returns_the_full_shape_and_unused_for_a_fresh_slip(): void
    {
        $this->actingAsServer();
        $this->slipOkResponse();

        $this->verifySlip($this->slipImage())
            ->assertOk()
            ->assertJsonPath('data.ok', true)
            ->assertJsonPath('data.error_code', null)
            ->assertJsonPath('data.trans_ref', self::REF)
            ->assertJsonPath('data.amount', 100.37)
            ->assertJsonPath('data.receiver_account', 'xxx-x-x5514-x')
            ->assertJsonPath('data.receiver_name', 'จันทรา พยากรณ์')
            ->assertJsonPath('data.sender_name', 'นาย ทดสอบ ผู้โอน')
            ->assertJsonPath('data.sending_bank', '014')
            ->assertJsonPath('data.receiving_bank', '004')
            ->assertJsonPath('data.receiver_matches', true)
            ->assertJsonPath('data.slip_age_ok', true)
            ->assertJsonPath('data.used', false)
            ->assertJsonPath('data.used_source', null)
            ->assertJsonPath('data.used_by', null)
            ->assertJsonStructure(['data' => ['message', 'trans_timestamp']]);

        Http::assertSentCount(1);
        // นับเพดาน flood guard ก้อนเดียวกับบอท (แพลตฟอร์ม juntraweb)
        $this->assertSame(1, (int) Cache::get('fortune:slipok:spend:juntraweb:U-1'));
    }

    public function test_verify_reports_a_slip_already_used_by_the_bot(): void
    {
        $this->actingAsServer();
        $this->botUsedSlip();
        $this->slipOkResponse();

        $this->verifySlip($this->slipImage())
            ->assertOk()
            ->assertJsonPath('data.ok', true)
            ->assertJsonPath('data.used', true)
            ->assertJsonPath('data.used_source', 'slip_registry')
            ->assertJsonPath('data.used_by.platform', 'facebook')
            ->assertJsonPath('data.used_by.user', 'PSID-1')
            ->assertJsonPath('data.used_by.reading_id', 77)
            ->assertJsonPath('data.used_by.at', Carbon::parse('2026-09-14 10:00:00')->toIso8601String());
    }

    public function test_verify_uses_the_local_qr_precheck_without_spending_slipok_quota(): void
    {
        $this->actingAsServer();
        $this->botUsedSlip();
        Http::fake();

        $this->verifySlip($this->slipImageWithQr(self::REF))
            ->assertOk()
            ->assertJsonPath('data.ok', false)
            ->assertJsonPath('data.error_code', 1012)
            ->assertJsonPath('data.trans_ref', self::REF)
            ->assertJsonPath('data.used', true)
            ->assertJsonPath('data.used_source', 'local_qr')
            ->assertJsonPath('data.used_by.reading_id', 77);

        Http::assertNothingSent();
    }

    public function test_verify_reports_a_slip_that_already_paid_a_bill_through_sms(): void
    {
        $this->actingAsServer();
        $paidAt = now()->subMinutes(30)->utc();
        $this->slipOkResponse([
            'amount' => 250.55,
            'transTimestamp' => $paidAt->format('Y-m-d\TH:i:s.v\Z'),
        ]);

        DB::table('sms_payment_notifications')->insert([
            'bank' => 'KBANK',
            'type' => 'credit',
            'amount' => 250.55,
            'sms_timestamp' => $paidAt->copy()->setTimezone('Asia/Bangkok')->toDateTimeString(),
            'device_id' => 'SMSCHK-T1',
            'nonce' => 'nonce-sms-1',
            'status' => 'matched',
            'matched_transaction_id' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->verifySlip($this->slipImage())
            ->assertOk()
            ->assertJsonPath('data.used', true)
            ->assertJsonPath('data.used_source', 'sms_payment')
            ->assertJsonPath('data.used_by', null);
    }

    public function test_verify_flood_guard_returns_429_without_calling_slipok(): void
    {
        $this->actingAsServer();
        Http::fake();
        Cache::put('fortune:slipok:spend:juntraweb:U-FLOOD', 2, 3600);

        $this->verifySlip($this->slipImage(), 'U-FLOOD')
            ->assertStatus(429)
            ->assertJsonPath('reason_code', 'flood_guard');

        Http::assertNothingSent();
        $this->assertSame(1, (int) Cache::get('fortune:slipok:strikes:juntraweb:U-FLOOD'));
    }

    public function test_verify_returns_503_when_slipok_is_disabled(): void
    {
        $this->actingAsServer();
        DB::table('fortune_telling_settings')->update(['enable_slipok_verify' => false]);
        FortuneTellingSetting::clearSettingsCache();

        $this->verifySlip($this->slipImage())
            ->assertStatus(503)
            ->assertJsonPath('reason_code', 'slipok_disabled');
    }

    public function test_verify_returns_503_when_slipok_gives_no_decision(): void
    {
        $this->actingAsServer();
        Http::fake(['api.slipok.com/*' => Http::response('upstream down', 502)]);

        $this->verifySlip($this->slipImage())
            ->assertStatus(503)
            ->assertJsonPath('reason_code', 'slipok_error');
    }

    public function test_verify_passes_slipok_rejections_through_as_200(): void
    {
        $this->actingAsServer();
        $this->slipOkResponse(['success' => false, 'transRef' => null], false, 1014);

        $this->verifySlip($this->slipImage())
            ->assertOk()
            ->assertJsonPath('data.ok', false)
            ->assertJsonPath('data.error_code', 1014)
            ->assertJsonPath('data.used', false);
    }

    public function test_verify_validates_input(): void
    {
        $this->actingAsServer();

        $this->withToken('server-token')
            ->post('/api/v1/juntra/server/slips/verify', ['slip' => $this->slipImage()], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['user_ref']);
    }

    public function test_per_user_verify_slip_keeps_old_keys_and_adds_the_new_ones(): void
    {
        $user = new User;
        $user->forceFill(['id' => 501, 'name' => 'ลูกค้าเว็บ']);
        Sanctum::actingAs($user);

        $this->botUsedSlip();
        // บัญชีปลายทางเป็นชื่อล้วน (ไม่มีเลข) — กฎใหม่ "เลขบัญชี หรือ ชื่อ" ต้องผ่านเมื่อเปิด log
        $this->slipOkResponse(['receiver' => ['displayName' => 'จันทรา พยากรณ์', 'proxy' => ['value' => null]]]);

        $this->post('/api/v1/juntra/payment/verify-slip', ['slip' => $this->slipImage()], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'ok', 'message', 'error_code', 'trans_ref', 'amount', 'receiver_account', 'receiver_name',
                'sender_name', 'trans_timestamp', 'receiver_matches',
                'slip_age_ok', 'used', 'used_source', 'used_by', 'sending_bank', 'receiving_bank',
            ]])
            ->assertJsonPath('data.receiver_account', null)
            ->assertJsonPath('data.receiver_matches', true)
            ->assertJsonPath('data.used', true)
            ->assertJsonPath('data.used_source', 'slip_registry');
    }

    // ─────────────────────────────────────────────────────────────────────
    // B2. slips/check
    // ─────────────────────────────────────────────────────────────────────

    public function test_check_returns_only_refs_found_in_the_registry(): void
    {
        $this->actingAsServer();
        $this->botUsedSlip();

        $this->withToken('server-token')
            ->postJson('/api/v1/juntra/server/slips/check', ['refs' => [self::REF, 'NEVERSEEN0001']])
            ->assertOk()
            ->assertExactJson(['data' => ['used' => [[
                'ref' => self::REF,
                'platform' => 'facebook',
                'user' => 'PSID-1',
                'reading_id' => 77,
                'at' => Carbon::parse('2026-09-14 10:00:00')->toIso8601String(),
            ]]]]);
    }

    public function test_check_validates_refs(): void
    {
        $this->actingAsServer();

        $this->withToken('server-token')
            ->postJson('/api/v1/juntra/server/slips/check', ['refs' => ['BAD-REF!']])
            ->assertStatus(422);

        $this->withToken('server-token')
            ->postJson('/api/v1/juntra/server/slips/check', ['refs' => array_map(fn ($i) => 'REF'.$i.'XXXXXX', range(1, 26))])
            ->assertStatus(422);

        $this->withToken('server-token')
            ->postJson('/api/v1/juntra/server/slips/check', ['refs' => []])
            ->assertStatus(422);
    }

    // ─────────────────────────────────────────────────────────────────────
    // B3. slips/claim
    // ─────────────────────────────────────────────────────────────────────

    private function claim(array $overrides = [])
    {
        return $this->withToken('server-token')->postJson('/api/v1/juntra/server/slips/claim', array_merge([
            'trans_ref' => self::REF,
            'amount' => 100.37,
            'user_ref' => 'U-1',
            'topup_ref' => 'TUP-AB12CD34',
            'sender_name' => 'นาย ทดสอบ ผู้โอน',
            'receiver_account' => 'xxx-x-x5514-x',
            'sending_bank' => '014',
            'receiving_bank' => '004',
            'trans_timestamp' => '2026-09-15T05:00:00.000Z',
        ], $overrides));
    }

    public function test_claim_records_the_slip_so_the_bot_treats_it_as_duplicate(): void
    {
        $this->actingAsServer();
        Cache::put('fortune:slipok:spend:juntraweb:U-1', 2, 3600);

        $this->claim()->assertStatus(201)->assertExactJson(['data' => ['claimed' => true]]);

        $row = SlipVerification::where('trans_ref', self::REF)->firstOrFail();
        $this->assertNull($row->fortune_reading_id);
        $this->assertSame('juntraweb', $row->consumed_by_platform);
        $this->assertSame('U-1', $row->consumed_by_user_id);
        $this->assertSame('verified', $row->status);
        $this->assertFalse($row->flagged_review);
        $this->assertSame('100.37', (string) $row->amount);
        $this->assertSame('juntraweb', $row->raw['source']);
        $this->assertSame('TUP-AB12CD34', $row->raw['topup_ref']);
        $this->assertSame('2026-09-15T05:00:00.000Z', $row->raw['trans_timestamp']);
        $this->assertNotNull($row->verified_at);

        // จ่ายจริงแล้ว → เคลียร์ตัวนับ flood เหมือนบอท
        $this->assertNull(Cache::get('fortune:slipok:spend:juntraweb:U-1'));

        // บอท (ด่าน 4) ต้องมองว่าสลิปนี้ซ้ำ
        $reading = new FortuneReading;
        $reading->forceFill(['id' => 900]);
        $eval = app(SlipOkService::class)->evaluateForReading($reading, [
            'ok' => true, 'error_code' => null, 'message' => '', 'transRef' => self::REF, 'amount' => 100.37,
            'receiver_account' => 'xxx-x-x5514-x', 'receiver_name' => null, 'trans_timestamp' => null,
        ]);
        $this->assertSame(SlipOkService::DECISION_DUPLICATE, $eval['decision']);
    }

    public function test_claim_is_idempotent_for_the_same_juntraweb_topup(): void
    {
        $this->actingAsServer();

        $this->claim()->assertStatus(201);
        $this->claim()->assertOk()->assertExactJson(['data' => ['claimed' => true, 'idempotent' => true]]);

        $this->assertSame(1, SlipVerification::where('trans_ref', self::REF)->count());
    }

    public function test_claim_conflicts_when_another_topup_or_the_bot_used_the_slip(): void
    {
        $this->actingAsServer();

        $this->claim()->assertStatus(201);
        $this->claim(['topup_ref' => 'TUP-OTHER999'])
            ->assertStatus(409)
            ->assertJsonPath('reason_code', 'already_used')
            ->assertJsonPath('data.platform', 'juntraweb')
            ->assertJsonPath('data.user', 'U-1')
            ->assertJsonPath('data.reading_id', null);

        $this->botUsedSlip('BOTREF0000000001', 77);
        $this->claim(['trans_ref' => 'BOTREF0000000001'])
            ->assertStatus(409)
            ->assertJsonPath('reason_code', 'already_used')
            ->assertJsonPath('data.platform', 'facebook')
            ->assertJsonPath('data.reading_id', 77);
    }

    public function test_claim_loses_cleanly_when_a_racer_inserts_first(): void
    {
        $this->actingAsServer();

        // จำลองอีกคำขอ (บอท LINE) ชิงบันทึกเลขเดียวกันระหว่างทาง — ต้องแพ้ด้วย unique index ไม่ใช่ชนะทั้งคู่
        SlipVerification::creating(function (SlipVerification $model) {
            if ($model->trans_ref === 'RACEREF000000001'
                && ! DB::table('slip_verifications')->where('trans_ref', 'RACEREF000000001')->exists()) {
                DB::table('slip_verifications')->insert([
                    'trans_ref' => 'RACEREF000000001',
                    'fortune_reading_id' => 88,
                    'consumed_by_platform' => 'line',
                    'consumed_by_user_id' => 'U-LINE',
                    'status' => 'verified',
                    'flagged_review' => false,
                    'verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $this->claim(['trans_ref' => 'RACEREF000000001'])
            ->assertStatus(409)
            ->assertJsonPath('reason_code', 'already_used')
            ->assertJsonPath('data.platform', 'line')
            ->assertJsonPath('data.reading_id', 88);

        $this->assertSame(1, SlipVerification::where('trans_ref', 'RACEREF000000001')->count());
    }

    public function test_claim_validates_required_fields(): void
    {
        $this->actingAsServer();

        $this->withToken('server-token')
            ->postJson('/api/v1/juntra/server/slips/claim', ['trans_ref' => self::REF])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'user_ref', 'topup_ref']);
    }
}
