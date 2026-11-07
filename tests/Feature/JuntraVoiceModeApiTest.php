<?php

namespace Tests\Feature;

use App\Models\FortuneTellingSetting;
use App\Models\FortuneVoiceUsage;
use App\Models\Setting;
use App\Services\Fortune\FortuneVoiceTranscribeService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\Concerns\BuildsJuntraServerSchema;
use Tests\TestCase;

/**
 * 🔈 (2026-11-04) โหมดคุยด้วยเสียง (+฿100) ฝั่ง Thaiprompt — ถอดเสียงไมค์ (Scribe) · สถานะระบบเสียง ·
 * เพดานต่อลูกค้า · บันทึกต้นทุนลง fortune_voice_usages (mock HTTP ทั้งหมด ไม่ยิงจริง)
 */
class JuntraVoiceModeApiTest extends TestCase
{
    use BuildsJuntraServerSchema;

    private const KEY = 'sk_TESTKEY1234567890abcdef0123456789';

    /** @var array<int, array{path:string, body:array, key:?string, multipart:array}> */
    private array $calls = [];

    private $override = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildJuntraServerSchema();
        (require database_path('migrations/2026_11_03_100000_create_fortune_character_voices.php'))->up();
        (require database_path('migrations/2026_11_04_120000_create_fortune_voice_usages_table.php'))->up();
        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('type')->default('string');
                $table->string('group')->default('general');
                $table->timestamps();
            });
        }
        config(['services.juntra.server_client_ids' => []]);
        Cache::flush();
        Storage::fake('public');
        FortuneTellingSetting::clearSettingsCache();

        $client = app(ClientRepository::class)->create(null, 'Juntra Chantra SSO', 'https://xn--82c4af5bzdj.online/auth/thaiprompt/callback', 'oauth_users');
        Passport::actingAsClient($client, [], 'api-oauth');

        Http::fake(function (HttpRequest $request) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);
            $this->calls[] = [
                'path' => $path,
                'body' => $request->isMultipart() ? [] : (array) $request->data(),
                'multipart' => $request->isMultipart() ? collect($request->data())->mapWithKeys(fn ($p) => [$p['name'] => is_string($p['contents']) ? $p['contents'] : '<file>'])->all() : [],
                'key' => $request->header('xi-api-key')[0] ?? null,
            ];

            if ($this->override && ($custom = ($this->override)($path, $request)) !== null) {
                return $custom;
            }

            return match (true) {
                str_starts_with($path, '/v1/text-to-speech/') => Http::response(str_repeat("\xFF\xFB\x90\x00", 400), 200, ['Content-Type' => 'audio/mpeg']),
                // เครดิตบัญชีเหลือ 90,000 ตัว (health เช็คไม่เสียโควต้า)
                $path === '/v1/user/subscription' => Http::response(['character_count' => 10000, 'character_limit' => 100000, 'tier' => 'creator']),
                $path === '/v1/speech-to-text' => Http::response([
                    'language_code' => 'tha',
                    'text' => 'อยากรู้เรื่องความรักค่ะ',
                    'words' => [
                        ['text' => 'อยากรู้', 'start' => 0.12, 'end' => 0.6, 'type' => 'word'],
                        ['text' => ' ', 'start' => 0.6, 'end' => 0.62, 'type' => 'spacing'],
                        ['text' => '(เสียงหัวเราะ)', 'start' => 0.62, 'end' => 1.1, 'type' => 'audio_event'],
                        ['text' => 'เรื่องความรักค่ะ', 'start' => 1.1, 'end' => 2.35, 'type' => 'word'],
                    ],
                ]),
                default => Http::response(['detail' => ['status' => 'not_found']], 404),
            };
        });
    }

    protected function tearDown(): void
    {
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    private function setKey(): void
    {
        FortuneTellingSetting::getGlobalSettings()->setElevenlabsApiKey(self::KEY);
    }

    private function sttCalls(): array
    {
        return array_values(array_filter($this->calls, fn ($c) => $c['path'] === '/v1/speech-to-text'));
    }

    private function transcribe(array $extra = [], ?UploadedFile $file = null)
    {
        return $this->withToken('server-token')->post('/api/v1/juntra/server/voice/transcribe', $extra + [
            // ไฟล์ปลอมต้องมีเนื้อ — create() รายงานขนาดแต่ไฟล์จริงว่าง
            'audio' => $file ?? UploadedFile::fake()->createWithContent('mic.webm', str_repeat('webm', 500)),
            'user_ref' => '42',
            'persona' => 'busaba',
        ], ['Accept' => 'application/json']);
    }

    // ───────────────────────────── ถอดเสียง ─────────────────────────────

    public function test_transcribe_returns_text_and_word_times_and_logs_the_seconds(): void
    {
        $this->setKey();

        $this->transcribe()->assertOk()
            ->assertJsonPath('data.text', 'อยากรู้เรื่องความรักค่ะ')
            ->assertJsonPath('data.provider', 'scribe')
            ->assertJsonPath('data.duration', 2.35)
            ->assertJsonCount(2, 'data.words')
            ->assertJsonPath('data.words.1', ['text' => 'เรื่องความรักค่ะ', 'start' => 1.1, 'end' => 2.35]);

        $call = $this->sttCalls()[0];
        $this->assertSame(self::KEY, $call['key']);
        $this->assertSame('scribe_v2', $call['multipart']['model_id']);
        $this->assertSame('th', $call['multipart']['language_code']);
        $this->assertSame('word', $call['multipart']['timestamps_granularity']);

        $usage = FortuneVoiceUsage::sole();
        $this->assertSame('stt', $usage->kind);
        $this->assertSame('42', $usage->user_ref);
        $this->assertSame('busaba', $usage->persona);
        $this->assertEqualsWithDelta(2.35, $usage->seconds, 0.001);
        $this->assertEqualsWithDelta(2.35, (new FortuneVoiceTranscribeService)->secondsUsedToday(), 0.001);
    }

    public function test_transcribe_without_a_key_never_calls_the_vendor(): void
    {
        $this->transcribe()->assertStatus(503);
        $this->assertSame([], $this->sttCalls());
    }

    public function test_transcribe_stops_at_the_daily_seconds_cap(): void
    {
        $this->setKey();
        Setting::set(FortuneVoiceTranscribeService::DAILY_SECONDS_SETTING, 2);

        $this->transcribe()->assertOk();          // 2.35 วิ — เกินเพดานหลังจ่ายแล้ว (เพดานแบบนุ่ม)
        $this->transcribe()->assertStatus(429);
        $this->assertCount(1, $this->sttCalls());
    }

    public function test_transcribe_vendor_errors_are_generic_and_never_echo_the_key(): void
    {
        $this->setKey();
        $this->override = fn ($path) => $path === '/v1/speech-to-text'
            ? Http::response(['detail' => ['status' => 'quota_exceeded', 'message' => 'quota for '.self::KEY]], 401)
            : null;

        $res = $this->transcribe()->assertStatus(502);
        $this->assertStringNotContainsString('TESTKEY', $res->getContent());
        $this->assertStringNotContainsString('ElevenLabs', $res->json('error'));
        $this->assertFalse(FortuneVoiceUsage::sole()->success);
        $this->assertStringNotContainsString('TESTKEY', json_encode(FortuneVoiceUsage::sole()->meta));
    }

    public function test_transcribe_refuses_wrong_or_oversized_files_and_a_missing_user(): void
    {
        $this->setKey();

        $this->transcribe([], UploadedFile::fake()->create('x.php', 5, 'text/x-php'))->assertStatus(422)->assertJsonValidationErrors('audio');
        $this->transcribe([], UploadedFile::fake()->create('long.webm', 900, 'audio/webm'))->assertStatus(422)->assertJsonValidationErrors('audio');
        $this->transcribe(['user_ref' => '../x'])->assertStatus(422)->assertJsonValidationErrors('user_ref');
        $this->assertSame([], $this->sttCalls());
    }

    // ───────────────────────────── สร้างเสียง ─────────────────────────────

    public function test_speak_for_narration_counts_toward_the_daily_cap_and_logs_only_new_audio(): void
    {
        $this->setKey();
        $speak = fn (string $text, array $extra = []) => $this->withToken('server-token')->postJson('/api/v1/juntra/server/voice/speak', $extra + [
            'persona' => 'maemor', 'text' => $text, 'purpose' => 'narration', 'user_ref' => '42',
        ]);

        $speak('[calm] ไพ่ใบแรกของลูกคือดวงดาว')->assertOk();
        $speak('[calm] ไพ่ใบแรกของลูกคือดวงดาว')->assertOk(); // แคช — ไม่เสียเงิน ไม่บันทึก

        $usage = FortuneVoiceUsage::sole();
        $this->assertSame('tts', $usage->kind);
        $this->assertSame('narration', $usage->purpose);
        $this->assertSame('42', $usage->user_ref);
        $this->assertGreaterThan(0, $usage->chars);

        FortuneTellingSetting::getGlobalSettings()->update(['character_voice_daily_char_limit' => 40]);
        FortuneTellingSetting::clearSettingsCache();
        $speak('[calm] บทที่สองยาวกว่าเดิมมากจนเกินเพดานตัวอักษรของวันนี้แน่นอน')->assertStatus(429);

        $speak('x', ['purpose' => 'lottery'])->assertStatus(422)->assertJsonValidationErrors('purpose');
    }

    public function test_one_customer_hammering_voice_does_not_block_another(): void
    {
        $this->setKey();
        config(['ratelimit.juntra_server.voice_per_user' => 2]);

        foreach ([1, 2] as $i) {
            $this->transcribe()->assertOk();
        }
        $this->transcribe()->assertStatus(429)->assertJsonPath('reason_code', 'rate_limited');
        $this->transcribe(['user_ref' => '43'])->assertOk();

        // poll คลิป/health ไม่มี user_ref — ใช้แค่เพดานรวม
        $this->withToken('server-token')->getJson('/api/v1/juntra/server/voice/health')->assertOk();
    }

    public function test_health_reports_what_is_available_and_what_is_left_today(): void
    {
        $this->withToken('server-token')->getJson('/api/v1/juntra/server/voice/health')->assertOk()
            ->assertJsonPath('data.tts.available', false)
            ->assertJsonPath('data.stt.available', false);

        $this->setKey();
        $this->transcribe()->assertOk();

        $this->withToken('server-token')->getJson('/api/v1/juntra/server/voice/health')->assertOk()
            ->assertJsonPath('data.tts.available', true)
            ->assertJsonPath('data.stt.available', true)
            ->assertJsonPath('data.stt.seconds_limit', FortuneVoiceTranscribeService::DEFAULT_DAILY_SECONDS)
            ->assertJsonPath('data.stt.seconds_used_today', 2.35);

        // health ถามบัญชี (ไม่เสียโควต้า) ครั้งเดียวแล้วจำไว้ — เรียกซ้ำไม่ยิงอีก
        $this->withToken('server-token')->getJson('/api/v1/juntra/server/voice/health')->assertOk()
            ->assertJsonPath('data.tts.reason', null)
            ->assertJsonPath('data.tts.account_chars_left', 90000);
        $this->assertSame(['/v1/speech-to-text', '/v1/user/subscription'], array_column($this->calls, 'path'));
    }

    private function health()
    {
        return $this->withToken('server-token')->getJson('/api/v1/juntra/server/voice/health')->assertOk();
    }

    public function test_health_is_not_ready_when_the_key_is_rejected(): void
    {
        $this->setKey();
        $this->override = fn ($path) => $path === '/v1/user/subscription'
            ? Http::response(['detail' => ['status' => 'invalid_api_key', 'message' => 'Invalid API key']], 401)
            : null;

        $this->health()->assertJsonPath('data.tts.available', false)->assertJsonPath('data.tts.reason', 'account');
    }

    public function test_health_is_not_ready_when_the_account_is_out_of_credits(): void
    {
        $this->setKey();
        $this->override = fn ($path) => $path === '/v1/user/subscription'
            ? Http::response(['character_count' => 100000, 'character_limit' => 100000])
            : null;

        $this->health()->assertJsonPath('data.tts.available', false)
            ->assertJsonPath('data.tts.reason', 'no_credits')
            ->assertJsonPath('data.tts.chars_left', 0);
    }

    public function test_chars_left_is_the_smaller_of_todays_quota_and_the_account_credits(): void
    {
        $this->setKey();
        $this->override = fn ($path) => $path === '/v1/user/subscription'
            ? Http::response(['character_count' => 98000, 'character_limit' => 100000])
            : null;

        // โควต้าวันนี้เหลือ 30,000 แต่บัญชีเหลือ 2,000 → ขายได้แค่ 2,000 (เว็บซ่อนปุ่มเองเมื่อไม่พอเล่าหนึ่งชุด)
        $this->health()->assertJsonPath('data.tts.available', true)->assertJsonPath('data.tts.chars_left', 2000);
    }

    public function test_a_key_that_cannot_read_credits_still_counts_as_ready(): void
    {
        $this->setKey();
        $this->override = fn ($path) => $path === '/v1/user/subscription'
            ? Http::response(['detail' => ['status' => 'missing_permissions', 'message' => 'user_read']], 401)
            : null;

        $this->health()->assertJsonPath('data.tts.available', true)
            ->assertJsonPath('data.tts.account_chars_left', null)
            ->assertJsonPath('data.tts.chars_left', 30000);
    }

    public function test_three_customer_voice_failures_in_a_row_close_the_sale_for_a_while(): void
    {
        $this->setKey();
        $log = fn (string $purpose, bool $ok, int $minutesAgo = 0) => FortuneVoiceUsage::forceCreate([
            'kind' => 'tts', 'purpose' => $purpose, 'provider' => 'elevenlabs', 'persona' => 'busaba',
            'chars' => 100, 'success' => $ok, 'created_at' => now()->subMinutes($minutesAgo),
        ]);

        // เสียงตัวอย่างของแอดมินล้มไม่นับ
        foreach (range(1, 3) as $_) {
            $log('sample', false);
        }
        $this->health()->assertJsonPath('data.tts.available', true);

        $log('narration', false);
        $log('chat', false);
        $this->health()->assertJsonPath('data.tts.available', true);   // ล้ม 2 ยังขาย

        $log('narration', false);
        $this->health()->assertJsonPath('data.tts.available', false)->assertJsonPath('data.tts.reason', 'failing');

        // สำเร็จหนึ่งครั้ง = กลับมาขาย
        $log('narration', true);
        $this->health()->assertJsonPath('data.tts.available', true);
    }

    public function test_old_failures_do_not_keep_the_sale_closed(): void
    {
        $this->setKey();
        foreach (range(1, 3) as $_) {
            FortuneVoiceUsage::forceCreate([
                'kind' => 'tts', 'purpose' => 'narration', 'provider' => 'elevenlabs', 'persona' => 'busaba',
                'chars' => 100, 'success' => false, 'created_at' => now()->subMinutes(20),
            ]);
        }

        $this->health()->assertJsonPath('data.tts.available', true);
    }
}
