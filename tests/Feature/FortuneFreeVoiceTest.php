<?php

namespace Tests\Feature;

use App\Models\FortuneTellingSetting;
use App\Models\FortuneVoiceUsage;
use App\Models\Setting;
use App\Services\Fortune\FortuneFreeVoiceService;
use App\Services\TelegramAlertService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Mockery;
use Tests\Concerns\BuildsJuntraServerSchema;
use Tests\TestCase;

/**
 * 🎬 (2026-11-04) เสียงอ่านคำทำนายฟรี — Google Cloud → gTTS → ไม่มีเสียง · ไม่ใช้ ElevenLabs
 * + การ์ดหลังบ้าน (กุญแจเข้ารหัส · ทดสอบกุญแจ · เพดานเดือน · เสียงต่อห้อง) — mock HTTP ทั้งหมด
 */
class FortuneFreeVoiceTest extends TestCase
{
    use BuildsJuntraServerSchema;

    private const GKEY = 'AIzaSyTESTKEY1234567890abcdefghijklmn';

    /** @var array<int,array{host:string,path:string,body:array,query:array,key:?string}> */
    private array $calls = [];

    private $override = null;

    private array $alerts = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildJuntraServerSchema();
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
        config(['services.juntra.server_client_ids' => [], 'services.google.tts_api_key' => null, 'services.google.translate.api_key' => null,
            'services.google_cloud.translate_api_key' => null, 'services.google.api_key' => null]);
        Cache::flush();
        Storage::fake('public');
        FortuneTellingSetting::clearSettingsCache();

        $client = app(ClientRepository::class)->create(null, 'Juntra Chantra SSO', 'https://xn--82c4af5bzdj.online/auth/thaiprompt/callback', 'oauth_users');
        Passport::actingAsClient($client, [], 'api-oauth');

        $telegram = Mockery::mock(TelegramAlertService::class);
        $telegram->shouldReceive('send')->andReturnUsing(function ($text, $dedupe = null) {
            $this->alerts[] = [$text, $dedupe];

            return true;
        });
        $this->app->instance(TelegramAlertService::class, $telegram);

        Http::fake(function (HttpRequest $request) {
            $host = (string) parse_url($request->url(), PHP_URL_HOST);
            $path = (string) parse_url($request->url(), PHP_URL_PATH);
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $this->calls[] = ['host' => $host, 'path' => $path, 'query' => $query,
                'body' => $request->method() === 'POST' ? (array) $request->data() : [],
                'key' => $request->header('x-goog-api-key')[0] ?? null];

            if ($this->override && ($custom = ($this->override)($host, $path, $request)) !== null) {
                return $custom;
            }

            $mp3 = str_repeat("\xFF\xFB\x90\x00", 400);

            return match (true) {
                $path === '/v1/text:synthesize' => Http::response(['audioContent' => base64_encode($mp3)]),
                $path === '/v1/voices' => Http::response(['voices' => [
                    ['name' => 'th-TH-Neural2-C', 'ssmlGender' => 'FEMALE'],
                    ['name' => 'th-TH-Standard-A', 'ssmlGender' => 'FEMALE'],
                ]]),
                $host === 'translate.google.com' => Http::response($mp3, 200, ['Content-Type' => 'audio/mpeg']),
                default => Http::response('unexpected', 500),
            };
        });
    }

    protected function tearDown(): void
    {
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    private function setKey(?string $key = self::GKEY): void
    {
        FortuneTellingSetting::getGlobalSettings()->setGoogleTtsApiKey($key);
    }

    private function speak(string $text = '[softly] ไพ่ใบนี้บอกว่าความรักกำลังมา', string $persona = 'busaba')
    {
        return $this->withToken('server-token')->postJson('/api/v1/juntra/server/voice/free-speak', [
            'persona' => $persona, 'text' => $text, 'user_ref' => '42',
        ]);
    }

    private function hosts(): array
    {
        return array_column($this->calls, 'host');
    }

    public function test_without_a_google_key_it_reads_with_gtts_and_strips_emotion_tags(): void
    {
        $res = $this->speak()->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.provider', 'gtts');

        $this->assertSame(['translate.google.com'], $this->hosts());
        $this->assertSame('ไพ่ใบนี้บอกว่าความรักกำลังมา', $this->calls[0]['query']['q']);
        $this->assertStringContainsString('/storage/fortune-free-voice/', $res->json('data.url'));
        $this->assertNotContains('api.elevenlabs.io', $this->hosts());
    }

    public function test_the_admin_google_key_is_used_with_the_rooms_voice_and_cached(): void
    {
        $this->setKey();
        Setting::set(FortuneFreeVoiceService::ROOM_VOICES_SETTING, json_encode(['busaba' => 'th-TH-Standard-A']), 'string', 'fortune_voice');

        $first = $this->speak()->assertOk()->assertJsonPath('data.provider', 'google_tts');
        $call = $this->calls[0];
        $this->assertSame('/v1/text:synthesize', $call['path']);
        $this->assertSame(self::GKEY, $call['key']);
        $this->assertSame('th-TH-Standard-A', $call['body']['voice']['name']);
        $this->assertSame('ไพ่ใบนี้บอกว่าความรักกำลังมา', $call['body']['input']['text']);
        $this->assertStringNotContainsString('key=', $this->calls[0]['path'].http_build_query($call['query']));

        // ฟังซ้ำ = ไฟล์เดิม ไม่เรียก Google ไม่นับเพดาน
        $this->speak()->assertOk()->assertJsonPath('data.url', $first->json('data.url'));
        $this->assertCount(1, $this->calls);
        $this->assertSame(1, FortuneVoiceUsage::where('provider', 'google_tts')->count());
    }

    public function test_reaching_the_monthly_cap_falls_back_to_gtts_and_80_percent_alerts_once(): void
    {
        $this->setKey();
        Setting::set(FortuneFreeVoiceService::MONTHLY_CAP_SETTING, 40, 'integer', 'fortune_voice');

        // 28 ตัวอักษร = 70% → ยังไม่เตือน
        $this->speak('ไพ่ใบนี้บอกว่าความรักกำลังมา')->assertJsonPath('data.provider', 'google_tts');
        $this->assertSame([], $this->alerts);

        // อีก 8 = 36/40 = 90% → เตือน
        $this->speak('ใจเย็นนะ')->assertJsonPath('data.provider', 'google_tts');
        $this->assertCount(1, $this->alerts);
        $this->assertStringStartsWith('google_tts_cap_80:', $this->alerts[0][1]);

        // 36 + 32 > 40 → gTTS (ไม่ยิง Google)
        $before = count($this->calls);
        $this->speak('ไพ่อีกใบบอกว่าการงานกำลังไปได้ดี')->assertJsonPath('data.provider', 'gtts');
        $this->assertSame(['translate.google.com'], array_column(array_slice($this->calls, $before), 'host'));
    }

    public function test_google_failing_falls_back_and_everything_failing_returns_none(): void
    {
        $this->setKey();
        $this->override = fn ($host, $path) => $path === '/v1/text:synthesize' ? Http::response(['error' => ['message' => 'quota']], 429) : null;
        $this->speak()->assertOk()->assertJsonPath('data.provider', 'gtts');

        $this->override = fn () => Http::response('down', 503);
        $this->speak('อีกบทหนึ่งที่ไม่เคยสร้าง')->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.status', 'none')
            ->assertJsonPath('data.url', null);
    }

    public function test_free_speak_validates_input(): void
    {
        $this->speak('', 'busaba')->assertStatus(422);
        $this->speak('ข้อความ', 'stranger')->assertStatus(422);
        $this->speak(str_repeat('ก', 1201))->assertStatus(422);
        $this->assertSame([], $this->calls);
    }

    // ───────────────────────────── หน้าแอดมิน ─────────────────────────────

    public function test_admin_key_is_tested_before_saving_and_never_echoed(): void
    {
        $this->withoutMiddleware();
        $this->override = fn ($host, $path) => $path === '/v1/voices'
            ? Http::response(['error' => ['status' => 'PERMISSION_DENIED', 'message' => 'Cloud Text-to-Speech API has not been used in project 123 before or it is disabled.']], 403)
            : null;

        $this->from('/admin/fortune/character-voices')->put('/admin/fortune/character-voices/google', [
            'google_tts_api_key' => self::GKEY,
            'google_tts_monthly_char_cap' => 500000,
        ])->assertRedirect('/admin/fortune/character-voices')->assertSessionHasErrors('google_tts_api_key');

        $errors = json_encode(session('errors')->getMessages(), JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('ยังไม่ได้เปิด Cloud Text-to-Speech API', $errors);
        $this->assertStringNotContainsString('TESTKEY', $errors);
        $this->assertNull(session()->getOldInput('google_tts_api_key'));
        $this->assertNull(FortuneTellingSetting::getGlobalSettings()->getGoogleTtsApiKey());
    }

    public function test_admin_saves_key_encrypted_cap_and_room_voices(): void
    {
        $this->withoutMiddleware();

        $this->from('/admin/fortune/character-voices')->put('/admin/fortune/character-voices/google', [
            'google_tts_api_key' => self::GKEY,
            'google_tts_monthly_char_cap' => 500000,
            'voices' => ['maemor' => 'th-TH-Neural2-C', 'busaba' => 'th-TH-Standard-A', 'kunsai' => ''],
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success', '✅ บันทึกกุญแจ Google Cloud แล้ว (พบเสียงไทย 2 เสียง)');

        $this->assertSame(self::GKEY, FortuneTellingSetting::getGlobalSettings()->getGoogleTtsApiKey());
        $this->assertStringNotContainsString('TESTKEY', (string) Setting::where('key', FortuneTellingSetting::GOOGLE_TTS_KEY_SETTING)->value('value'));
        $this->assertSame('AIza••••••••klmn', FortuneTellingSetting::getGlobalSettings()->getGoogleTtsApiKeyMasked());
        $this->assertSame(500000, (new FortuneFreeVoiceService)->monthlyCap());
        $this->assertSame('th-TH-Standard-A', FortuneFreeVoiceService::roomVoices()['busaba']);
        $this->assertSame(FortuneFreeVoiceService::DEFAULT_ROOM_VOICES['kunsai'], FortuneFreeVoiceService::roomVoices()['kunsai']);
        $this->assertNotContains('/v1/text:synthesize', array_column($this->calls, 'path'), 'ทดสอบกุญแจไม่กินโควตา');

        // บันทึกรอบหน้าเว้นกุญแจ = กุญแจเดิม · ลบได้
        $this->put('/admin/fortune/character-voices/google', ['google_tts_monthly_char_cap' => 300000])->assertRedirect();
        $this->assertSame(self::GKEY, FortuneTellingSetting::getGlobalSettings()->getGoogleTtsApiKey());
        $this->delete('/admin/fortune/character-voices/google/key')->assertRedirect();
        $this->assertNull(FortuneTellingSetting::getGlobalSettings()->getGoogleTtsApiKey());

        // ชื่อเสียงแปลก ๆ ไม่รับ
        $this->put('/admin/fortune/character-voices/google', ['google_tts_monthly_char_cap' => 300000, 'voices' => ['maemor' => '"><script>']])
            ->assertSessionHasErrors('voices.maemor');
    }

    public function test_admin_test_button_and_sample(): void
    {
        $this->withoutMiddleware();

        $this->postJson('/admin/fortune/character-voices/google/test', ['key' => self::GKEY])
            ->assertOk()
            ->assertJsonPath('voices.0.name', 'th-TH-Neural2-C')
            ->assertJsonPath('voices.0.gender', 'female');
        $this->assertSame(self::GKEY, $this->calls[0]['key']);

        $this->postJson('/admin/fortune/character-voices/google/test')->assertStatus(422)->assertJsonPath('error', 'ยังไม่ได้ใส่กุญแจ Google Cloud');

        $this->setKey();
        $url = $this->postJson('/admin/fortune/character-voices/google/sample', ['persona' => 'kunsai', 'voice' => 'th-TH-Neural2-C'])
            ->assertOk()->json('url');
        $this->assertNotEmpty($url);
        $this->assertStringNotContainsString('[', end($this->calls)['body']['input']['text']);
        $synth = count(array_filter($this->calls, fn ($c) => $c['path'] === '/v1/text:synthesize'));
        $this->postJson('/admin/fortune/character-voices/google/sample', ['persona' => 'kunsai', 'voice' => 'th-TH-Neural2-C'])
            ->assertOk()->assertJsonPath('url', $url);
        $this->assertSame($synth, count(array_filter($this->calls, fn ($c) => $c['path'] === '/v1/text:synthesize')), 'กดซ้ำไม่กินโควตา');
    }

    public function test_admin_card_compiles(): void
    {
        foreach (['index', '_google-free-voice'] as $view) {
            $compiled = Blade::compileString(file_get_contents(resource_path("views/admin/fortune/character-voices/{$view}.blade.php")));
            $tmp = tempnam(sys_get_temp_dir(), 'blade');
            file_put_contents($tmp, $compiled);
            exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($tmp), $out, $code);
            @unlink($tmp);
            $this->assertSame(0, $code, $view.': '.implode("\n", $out));
        }
    }
}
