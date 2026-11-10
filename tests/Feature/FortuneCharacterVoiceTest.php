<?php

namespace Tests\Feature;

use App\Models\FortuneCharacterVoiceClip;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneVoiceCharacter;
use App\Models\FortuneVoiceLine;
use App\Models\Setting;
use App\Services\Fortune\FortuneCharacterVoiceService;
use App\Services\Fortune\JuntraVoiceLibrary;
use App\Services\Fortune\VoiceLoudnessNormalizer;
use App\Services\Tts\ElevenLabsTtsProvider;
use App\Services\Tts\XDreamerTtsProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\Concerns\BuildsJuntraServerSchema;
use Tests\TestCase;

/**
 * 🎭 เสียงพากย์แม่หมอจันทรา/น้องบุษบา/แม่หมอโหมดคุณไสย — ElevenLabs ตรง (mock HTTP ทั้งหมด ไม่ยิงจริง)
 *
 * ครอบคลุม: key เข้ารหัส/ไม่หลุด · สร้างเสียงในคำขอเดียว · แคชไม่สั่งซ้ำ · กดซ้ำระหว่างสร้าง · เพดานรายวัน ·
 *           endpoint ของเว็บจันทรา · หน้าแอดมิน (บันทึก key / เสียงตัวอย่าง / เลือกเสียง) · X-DREAMER ใน chain เดิมของบอท
 */
class FortuneCharacterVoiceTest extends TestCase
{
    use BuildsJuntraServerSchema;

    private const KEY = 'sk_TESTKEY1234567890abcdef0123456789';

    private const XD_KEY = 'axm-TESTKEY1234567890abcdef';

    private const XD_AUDIO_URL = 'https://media.xman4289.com/gen/77.mp3';

    /** @var array<int, array{path: string, method: string, body: array, key: ?string}> */
    private array $calls = [];

    /** คำตอบพิเศษต่อคำขอ — Http::fake ซ้อนกันไม่ได้ (ตัวแรกที่ลงทะเบียนชนะเสมอ) จึงสลับผ่าน property นี้ */
    private $override = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildJuntraServerSchema();
        (require database_path('migrations/2026_11_03_100000_create_fortune_character_voices.php'))->up();
        // ตัวละคร + คลังบท (2026-11-04) — เทสต์เดิมทั้งไฟล์ต้องผ่านเมื่อมีตารางนี้ด้วย
        (require database_path('migrations/2026_11_04_150000_create_fortune_voice_characters_and_lines.php'))->up();
        // ตาราง key/value ที่เก็บ key ElevenLabs (สคีมาเดียวกับของจริง)
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

        $this->fakeVendors();
    }

    protected function tearDown(): void
    {
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    private function setKey(?string $key = self::KEY): void
    {
        FortuneTellingSetting::getGlobalSettings()->setElevenlabsApiKey($key);
    }

    private function fakeVendors(?callable $override = null): void
    {
        if ($override) {
            $this->override = $override;

            return;
        }

        Http::fake(function (HttpRequest $request) {
            // X-DREAMER (chain เสียงเดิมของบอทเท่านั้น)
            if ($request->url() === self::XD_AUDIO_URL) {
                return Http::response(str_repeat("\xFF\xFB\x90\x00", 400), 200, ['Content-Type' => 'audio/mpeg']);
            }
            if (str_contains($request->url(), 'xman4289.com/api/mcp')) {
                $tool = $request['params']['name'] ?? null;
                $result = match ($tool) {
                    'generate_speech' => ['id' => 77, 'status' => 'pending', 'result_urls' => []],
                    'get_generation' => ['id' => 77, 'status' => 'completed', 'result_urls' => [self::XD_AUDIO_URL]],
                    default => [],
                };

                return Http::response(['jsonrpc' => '2.0', 'id' => 1, 'result' => ['structuredContent' => $result, 'isError' => false]]);
            }

            $path = (string) parse_url($request->url(), PHP_URL_PATH);
            $this->calls[] = [
                'path' => $path,
                'url' => $request->url(),
                'method' => $request->method(),
                'body' => $request->method() === 'POST' ? (array) $request->data() : [],
                'key' => $request->header('xi-api-key')[0] ?? null,
            ];

            if ($this->override && ($custom = ($this->override)($path, $request)) !== null) {
                return $custom;
            }

            return match (true) {
                str_starts_with($path, '/v1/text-to-speech/') => Http::response(str_repeat("\xFF\xFB\x90\x00", 400), 200, ['Content-Type' => 'audio/mpeg']),
                $path === '/v1/user/subscription' => Http::response(['tier' => 'creator', 'character_count' => 1000, 'character_limit' => 100000, 'next_invoice' => ['subtotal_cents' => 2200]]),
                $path === '/v1/voices' => Http::response(['voices' => array_map(fn ($v) => [
                    'voice_id' => $v['id'],
                    'name' => strtok($v['name'], ' '),
                    'category' => 'premade',
                    'labels' => ['gender' => $v['gender'], 'accent' => 'american'],
                ], ElevenLabsTtsProvider::FALLBACK_VOICES)]),
                $path === '/v1/models' => Http::response([
                    ['model_id' => 'eleven_v4', 'name' => 'Eleven v4', 'can_do_text_to_speech' => true, 'languages' => [['language_id' => 'en'], ['language_id' => 'th']], 'model_rates' => ['character_cost_multiplier' => 1], 'maximum_text_length_per_request' => 10000],
                    ['model_id' => 'eleven_v4_turbo', 'name' => 'Eleven v4 Turbo', 'can_do_text_to_speech' => true, 'languages' => [['language_id' => 'th']], 'model_rates' => ['character_cost_multiplier' => 0.5], 'maximum_text_length_per_request' => 10000],
                    ['model_id' => 'eleven_multilingual_v2', 'name' => 'Multilingual v2', 'can_do_text_to_speech' => true, 'languages' => [['language_id' => 'en'], ['language_id' => 'ja']], 'model_rates' => ['character_cost_multiplier' => 1]],
                    ['model_id' => 'eleven_english_sts_v2', 'name' => 'STS', 'can_do_text_to_speech' => false, 'languages' => [['language_id' => 'th']]],
                ]),
                $path === '/v1/audio-effects/catalog' => Http::response([
                    'filter_presets' => [['filter_preset_id' => 'old_radio', 'name' => 'Old Radio', 'description' => null]],
                    'environments' => [['environment_id' => 'hall', 'name' => 'Hall', 'description' => null], ['environment_id' => 'bad id!', 'name' => 'x']],
                    'background_noises' => [['background_noise_id' => 'cafe', 'name' => 'Café', 'description' => null]],
                ]),
                default => Http::response(['detail' => ['status' => 'not_found', 'message' => 'not found']], 404),
            };
        });
    }

    private function ttsCalls(): array
    {
        return array_values(array_filter($this->calls, fn ($c) => str_starts_with($c['path'], '/v1/text-to-speech/')));
    }

    private function actAsJuntraServer(): void
    {
        $client = app(ClientRepository::class)->create(null, 'Juntra Chantra SSO', 'https://xn--82c4af5bzdj.online/auth/thaiprompt/callback', 'oauth_users');
        Passport::actingAsClient($client, [], 'api-oauth');
    }

    /** เปิดหน้าแอดมินในเทสต์ — ข้าม middleware + layout เปล่า (layout จริงต้องใช้ตารางอีกหลายสิบตัว) */
    private function asAdminWithBareLayout(): void
    {
        $this->withoutMiddleware();
        // withoutMiddleware() ปิด SubstituteBindings ด้วย → {character}/{line} จะได้โมเดลเปล่า — ผูกโมเดลจาก route เองตอนจับคู่ได้
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Routing\Events\RouteMatched::class, function ($event) {
            app('router')->substituteBindings($event->route);
            app('router')->substituteImplicitBindings($event->route);
        });
        // ตาราง users ของเทสต์นี้มีแค่คอลัมน์ที่ใช้ — ล็อกอินด้วย user ที่ไม่บันทึกลง DB
        $this->actingAs((new \App\Models\User)->forceFill(['id' => 1, 'name' => 'แอดมิน', 'email' => 'admin@example.test', 'role' => 'admin']));
        view()->share('errors', new \Illuminate\Support\ViewErrorBag); // withoutMiddleware() ปิดตัวแชร์ $errors ด้วย
        $layouts = sys_get_temp_dir().'/cv-layout-'.getmypid();
        @mkdir($layouts.'/layouts', 0777, true);
        file_put_contents($layouts.'/layouts/admin-v4.blade.php', "@yield('content')\n@stack('scripts')");
        \Illuminate\Support\Facades\View::getFinder()->prependLocation($layouts);
        \Illuminate\Support\Facades\View::getFinder()->flush();
    }

    // ───────────────────────────── key ─────────────────────────────

    public function test_key_is_stored_encrypted_in_settings_and_masked(): void
    {
        $this->setKey();
        $settings = FortuneTellingSetting::getGlobalSettings();

        $raw = DB::table('settings')->where('key', FortuneTellingSetting::ELEVENLABS_KEY_SETTING)->value('value');
        $this->assertNotEmpty($raw);
        $this->assertStringNotContainsString('TESTKEY', (string) $raw);

        $this->assertSame(self::KEY, $settings->getElevenlabsApiKey());
        $this->assertSame('sk_••••••••6789', $settings->getElevenlabsApiKeyMasked());

        // ลบ key = ลบแถว
        $settings->setElevenlabsApiKey(null);
        $this->assertNull($settings->getElevenlabsApiKey());
        $this->assertFalse(Setting::where('key', FortuneTellingSetting::ELEVENLABS_KEY_SETTING)->exists());
    }

    public function test_unreadable_key_after_app_key_change_counts_as_not_set(): void
    {
        Setting::set(FortuneTellingSetting::ELEVENLABS_KEY_SETTING, 'not-an-encrypted-payload', 'encrypted', 'fortune_voice');

        $this->assertNull(FortuneTellingSetting::getGlobalSettings()->getElevenlabsApiKey());
        $this->assertFalse((new ElevenLabsTtsProvider(FortuneTellingSetting::getGlobalSettings()))->isAvailable());
    }

    public function test_vendor_errors_are_readable_and_never_echo_the_key(): void
    {
        $this->setKey();
        $this->fakeVendors(fn ($path) => str_starts_with($path, '/v1/text-to-speech/')
            ? Http::response(['detail' => ['status' => 'quota_exceeded', 'message' => 'Quota exceeded for '.self::KEY]], 401)
            : null);

        $result = (new FortuneCharacterVoiceService)->request('maemor', 'สวัสดีจ้ะลูก', 'chat');

        $this->assertFalse($result['success']);
        $this->assertSame(502, $result['code']);
        $this->assertStringContainsString('โควต้าตัวอักษร ElevenLabs หมด', $result['error']);
        $this->assertStringNotContainsString('TESTKEY', $result['error']);
        $this->assertStringNotContainsString('TESTKEY', (string) FortuneCharacterVoiceClip::first()->error);

        $this->fakeVendors(fn ($path) => str_starts_with($path, '/v1/text-to-speech/')
            ? Http::response(['detail' => 'raw '.self::KEY], 500)
            : null);
        $raw = (new FortuneCharacterVoiceService)->request('maemor', 'อีกข้อความ', 'chat');
        $this->assertStringNotContainsString('TESTKEY', $raw['error']);
    }

    // ───────────────────────────── service ─────────────────────────────

    public function test_speech_is_synthesized_stored_then_served_from_cache(): void
    {
        $this->setKey();
        $service = new FortuneCharacterVoiceService;

        $first = $service->request('maemor', "**ลูกเอ๋ย** ใจเย็น ๆ นะ ✨🔮\n\n[[OFFER:love]]", 'chat');

        $this->assertTrue($first['success']);
        $this->assertSame(200, $first['code']);
        $clip = $first['clip']->fresh();
        $this->assertSame('completed', $clip->status);
        $this->assertSame('elevenlabs', $clip->provider);
        $this->assertSame('ลูกเอ๋ย ใจเย็น ๆ นะ', $clip->text, 'markdown/emoji/ป้ายระบบถูกลอกออกก่อนส่งไปอ่าน');
        Storage::disk('public')->assertExists($clip->audio_path);
        $this->assertNotEmpty($clip->audio_url);

        // ส่ง xi-api-key + เสียงค่าเริ่มต้นของแม่หมอ + โมเดล Eleven v3 + mp3
        $call = $this->ttsCalls()[0];
        $this->assertSame(self::KEY, $call['key']);
        $this->assertSame('/v1/text-to-speech/'.FortuneCharacterVoiceService::PERSONAS['maemor']['default_voice'], $call['path']);
        $this->assertStringContainsString('output_format=mp3_44100_128', $call['url']);
        $this->assertSame('eleven_v3', $call['body']['model_id']);
        $this->assertSame('ลูกเอ๋ย ใจเย็น ๆ นะ', $call['body']['text']);

        // ข้อความเดิม = ไฟล์เดิม ไม่สั่งงานซ้ำ
        $again = $service->request('maemor', "**ลูกเอ๋ย** ใจเย็น ๆ นะ ✨🔮\n\n[[OFFER:love]]", 'chat');
        $this->assertSame($clip->id, $again['clip']->id);
        $this->assertCount(1, $this->ttsCalls());
        $this->assertSame(1, $again['clip']->fresh()->hits);
        $this->assertSame(200, $again['code']);
    }

    /** 🔊 (2026-11-10) ไม่มี ffmpeg = ข้ามขั้นปรับความดัง เก็บไฟล์ดิบตามเดิม คลิปไม่ล้ม */
    public function test_clip_is_stored_unchanged_when_ffmpeg_is_unavailable(): void
    {
        $this->setKey();
        config(['app.ffmpeg_path' => base_path('no-such-dir/ffmpeg-missing')]);
        $normalizer = new VoiceLoudnessNormalizer([]); // ปิดตำแหน่งสำรอง ~/bin/ffmpeg
        $this->assertFalse($normalizer->available());

        $service = new FortuneCharacterVoiceService(null, null, null, $normalizer);
        $res = $service->request('kunsai', 'ฟังแม่หมอนะคะ คุณไม่ได้อยู่คนเดียว', 'chat');

        $this->assertTrue($res['success']);
        $clip = $res['clip']->fresh();
        $this->assertSame('completed', $clip->status);
        Storage::disk('public')->assertExists($clip->audio_path);
        $this->assertSame(str_repeat("\xFF\xFB\x90\x00", 400), Storage::disk('public')->get($clip->audio_path), 'ไฟล์ดิบจาก ElevenLabs ไม่ถูกแตะ');
    }

    public function test_double_tap_while_generating_is_not_ordered_twice_and_stale_pending_can_retry(): void
    {
        $this->setKey();
        $service = new FortuneCharacterVoiceService;

        // คำขอแรกกำลังสร้างอยู่ (แถว pending สด) → คำขอที่สองได้ clip เดิม 202 ไม่ยิง ElevenLabs
        $service->request('busaba', 'สวัสดีค่า บุษบาเองค่ะ', 'chat');
        $clip = FortuneCharacterVoiceClip::first();
        $clip->update(['status' => 'pending', 'audio_url' => null]);

        $b = $service->request('busaba', 'สวัสดีค่า บุษบาเองค่ะ', 'chat');
        $this->assertSame(202, $b['code']);
        $this->assertSame($clip->id, $b['clip']->id);
        $this->assertCount(1, $this->ttsCalls());

        // ค้างนานเกิน (เครื่องที่สร้างล้มกลางทาง) → poll เห็นเป็น failed และสั่งใหม่ได้
        DB::table('fortune_character_voice_clips')->update(['updated_at' => now()->subMinutes(10)]);
        $this->assertSame('failed', $service->refresh($clip)->status);

        $retry = $service->request('busaba', 'สวัสดีค่า บุษบาเองค่ะ', 'sample');
        $this->assertTrue($retry['success']);
        $this->assertSame('completed', $retry['clip']->fresh()->status);
        $this->assertCount(2, $this->ttsCalls());
    }

    public function test_broken_audio_marks_clip_failed(): void
    {
        $this->setKey();
        $this->fakeVendors(fn ($path) => str_starts_with($path, '/v1/text-to-speech/')
            ? Http::response('{"detail":"oops"}', 200, ['Content-Type' => 'application/json'])
            : null);

        $result = (new FortuneCharacterVoiceService)->request('maemor', 'ไพ่ใบนี้บอกว่า...', 'chat');

        $this->assertFalse($result['success']);
        $this->assertSame(502, $result['code']);
        $this->assertSame('failed', $result['clip']->fresh()->status);
    }

    public function test_daily_char_limit_blocks_new_chat_orders_but_not_cached_ones(): void
    {
        $this->setKey();
        FortuneTellingSetting::getGlobalSettings()->update(['character_voice_daily_char_limit' => 1000]);
        FortuneTellingSetting::clearSettingsCache();
        $service = new FortuneCharacterVoiceService;

        $this->assertTrue($service->request('maemor', 'สั้น ๆ', 'chat')['success']);

        $long = str_repeat('ก', 1001);
        $blocked = $service->request('maemor', $long, 'chat');
        $this->assertSame(429, $blocked['code']);
        $this->assertCount(1, $this->ttsCalls());

        // ของในแคชยังเล่นได้
        $this->assertSame(200, $service->request('maemor', 'สั้น ๆ', 'chat')['code']);
    }

    public function test_without_key_nothing_is_called(): void
    {
        $result = (new FortuneCharacterVoiceService)->request('maemor', 'สวัสดี', 'chat');

        $this->assertSame(503, $result['code']);
        $this->assertSame([], $this->calls);
    }

    // ───────────────────────────── API เว็บจันทรา ─────────────────────────────

    public function test_juntra_server_gets_a_finished_voice_and_can_poll_it(): void
    {
        $this->setKey();
        $this->actAsJuntraServer();

        $res = $this->withToken('server-token')->postJson('/api/v1/juntra/server/voice/speak', [
            'persona' => 'busaba', 'text' => 'วันนี้ดวงความรักสดใสมากเลยค่า', 'wait' => 0,
        ])->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.persona', 'busaba');

        $this->assertNotEmpty($res->json('data.url'));

        $this->withToken('server-token')->getJson('/api/v1/juntra/server/voice/clips/'.$res->json('data.clip_id'))
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_juntra_server_gets_generic_error_not_vendor_reason(): void
    {
        $this->setKey();
        $this->actAsJuntraServer();
        $this->fakeVendors(fn ($path) => str_starts_with($path, '/v1/text-to-speech/')
            ? Http::response(['detail' => ['status' => 'voice_not_found', 'message' => 'A voice with that ID does not exist']], 400)
            : null);

        $this->withToken('server-token')->postJson('/api/v1/juntra/server/voice/speak', [
            'persona' => 'maemor', 'text' => 'ไพ่ใบนี้บอกว่า...',
        ])->assertStatus(502)
            ->assertJsonPath('error', 'สร้างเสียงไม่สำเร็จ ลองใหม่อีกครั้งภายหลังนะคะ');

        // สาเหตุจริงเก็บไว้ให้แอดมินเห็น
        $this->assertStringContainsString('ไม่พบเสียงนี้ในบัญชี ElevenLabs', (string) FortuneCharacterVoiceClip::first()->error);
    }

    public function test_kunsai_persona_has_its_own_voice(): void
    {
        $this->setKey();
        $this->actAsJuntraServer();

        $this->withToken('server-token')->postJson('/api/v1/juntra/server/voice/speak', [
            'persona' => 'kunsai', 'text' => '[whispers] ลูกเอ๋ย สิ่งที่ตามลูกมาไม่ใช่เรื่องบังเอิญ',
        ])->assertOk()->assertJsonPath('data.persona', 'kunsai');

        $this->assertSame('/v1/text-to-speech/Z3R5wn05IrDiVCyEkUrK', $this->ttsCalls()[0]['path']);
        $this->assertStringStartsWith('[whispers]', $this->ttsCalls()[0]['body']['text'], 'แท็กอารมณ์ v3 ถูกส่งไปตามเดิม');

        (new FortuneCharacterVoiceService)->selectVoice('kunsai', 'hpp4J3VqNfWAUOO0d1Us');
        $this->assertSame('hpp4J3VqNfWAUOO0d1Us', (new FortuneCharacterVoiceService)->voiceFor('kunsai'));
        $this->assertSame(FortuneCharacterVoiceService::PERSONAS['maemor']['default_voice'], (new FortuneCharacterVoiceService)->voiceFor('maemor'), 'เปลี่ยนเสียงคุณไสยไม่กระทบแม่หมอปกติ');
    }

    public function test_juntra_server_rejects_unknown_persona(): void
    {
        $this->setKey();
        $this->actAsJuntraServer();

        $this->withToken('server-token')->postJson('/api/v1/juntra/server/voice/speak', [
            'persona' => 'someone', 'text' => 'สวัสดี',
        ])->assertStatus(422);
        $this->assertSame([], $this->calls);
    }

    // ───────────────────────────── หน้าแอดมิน ─────────────────────────────

    public function test_admin_rejected_key_is_not_saved_nor_echoed(): void
    {
        $this->withoutMiddleware();
        $this->fakeVendors(fn ($path) => Http::response(['detail' => ['status' => 'invalid_api_key', 'message' => 'Invalid API key: '.self::KEY]], 401));

        $res = $this->from('/admin/fortune/character-voices')->put('/admin/fortune/character-voices/settings', [
            'elevenlabs_api_key' => self::KEY,
        ]);

        $res->assertRedirect('/admin/fortune/character-voices')->assertSessionHasErrors('elevenlabs_api_key');
        $this->assertStringNotContainsString('TESTKEY', json_encode(session('errors')->getMessages()));
        $this->assertNull(session()->getOldInput('elevenlabs_api_key'));
        $this->assertNull(FortuneTellingSetting::getGlobalSettings()->getElevenlabsApiKey());
    }

    public function test_admin_valid_key_is_tested_then_saved(): void
    {
        $this->withoutMiddleware();

        $this->from('/admin/fortune/character-voices')->put('/admin/fortune/character-voices/settings', [
            'elevenlabs_api_key' => self::KEY,
            'elevenlabs_tts_model' => 'eleven_v4_turbo',
            'character_voice_daily_char_limit' => 50000,
        ])->assertRedirect()->assertSessionHas('success', '✅ บันทึก ElevenLabs key แล้ว (เหลือ 99,000 ตัวอักษรในรอบบิลนี้)');

        FortuneTellingSetting::clearSettingsCache();
        $settings = FortuneTellingSetting::getGlobalSettings();
        $this->assertSame(self::KEY, $settings->getElevenlabsApiKey());
        $this->assertSame(50000, (int) $settings->character_voice_daily_char_limit);
        $this->assertSame('eleven_v4_turbo', (new ElevenLabsTtsProvider($settings))->model());
        $this->assertSame([], $this->ttsCalls(), 'ทดสอบ key ไม่เสียโควต้า');

        // บันทึกรอบหน้าเว้นช่อง key ว่าง = key เดิมยังอยู่ · เว้นโมเดลว่าง = กลับไปค่าเริ่มต้น
        $this->put('/admin/fortune/character-voices/settings', ['elevenlabs_api_key' => ''])->assertRedirect();
        FortuneTellingSetting::clearSettingsCache();
        $this->assertSame(self::KEY, FortuneTellingSetting::getGlobalSettings()->getElevenlabsApiKey());
        $this->assertSame('eleven_v3', (new ElevenLabsTtsProvider(FortuneTellingSetting::getGlobalSettings()))->model());
    }

    // ───────────────────────────── เลือกโมเดล (v4 / v4 Turbo / v3) ─────────────────────────────

    public function test_model_list_is_read_live_and_flags_thai(): void
    {
        $this->setKey();

        $list = (new ElevenLabsTtsProvider(FortuneTellingSetting::getGlobalSettings()))->listModels();

        $this->assertTrue($list['live']);
        $byId = collect($list['models'])->keyBy('id');
        $this->assertSame(['eleven_v4', 'eleven_v4_turbo', 'eleven_multilingual_v2'], $byId->keys()->all(), 'โมเดลที่สร้างเสียงจากข้อความไม่ได้ไม่ถูกนับ');
        $this->assertTrue($byId['eleven_v4_turbo']['thai']);
        $this->assertSame(0.5, $byId['eleven_v4_turbo']['cost']);
        $this->assertFalse($byId['eleven_multilingual_v2']['thai']);
        $this->assertSame([], $this->ttsCalls(), 'ดูรายชื่อโมเดลไม่เสียโควต้า');
    }

    public function test_model_list_falls_back_when_vendor_is_down(): void
    {
        $this->setKey();
        $this->fakeVendors(fn ($path) => $path === '/v1/models' ? Http::response('upstream error', 503) : null);

        $list = (new ElevenLabsTtsProvider(FortuneTellingSetting::getGlobalSettings()))->listModels();

        $this->assertFalse($list['live']);
        $this->assertSame(ElevenLabsTtsProvider::FALLBACK_MODELS, $list['models']);
        $this->assertContains('eleven_v4', array_column($list['models'], 'id'));
    }

    public function test_admin_rejects_model_without_thai_and_saves_nothing(): void
    {
        $this->withoutMiddleware();

        $this->from('/admin/fortune/character-voices')->put('/admin/fortune/character-voices/settings', [
            'elevenlabs_api_key' => self::KEY,
            'elevenlabs_tts_model' => 'eleven_multilingual_v2',
            'character_voice_daily_char_limit' => 50000,
        ])->assertRedirect('/admin/fortune/character-voices')->assertSessionHasErrors('elevenlabs_tts_model');

        FortuneTellingSetting::clearSettingsCache();
        $settings = FortuneTellingSetting::getGlobalSettings();
        $this->assertNull($settings->getElevenlabsApiKey(), 'โมเดลไม่ผ่าน = key ใหม่ก็ยังไม่ถูกบันทึก');
        $this->assertSame('eleven_v3', (new ElevenLabsTtsProvider($settings))->model());
        $this->assertNull(session()->getOldInput('elevenlabs_api_key'));
    }

    public function test_admin_switches_existing_key_to_v4_and_keeps_current_model_selectable(): void
    {
        $this->withoutMiddleware();
        $this->setKey();

        // โมเดลที่ใช้อยู่ (v3) ไม่อยู่ในรายชื่อสดของบัญชี แต่กดบันทึกซ้ำได้ ไม่ถูกบังคับเปลี่ยน
        $this->put('/admin/fortune/character-voices/settings', ['elevenlabs_tts_model' => 'eleven_v3'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->put('/admin/fortune/character-voices/settings', ['elevenlabs_tts_model' => 'eleven_v4'])
            ->assertRedirect()->assertSessionHasNoErrors();
        FortuneTellingSetting::clearSettingsCache();
        $this->assertSame('eleven_v4', (new ElevenLabsTtsProvider(FortuneTellingSetting::getGlobalSettings()))->model());
        $this->assertSame(self::KEY, FortuneTellingSetting::getGlobalSettings()->getElevenlabsApiKey());
    }

    public function test_account_ai_pools_menu_links_the_key_pool_and_elevenlabs(): void
    {
        $group = collect(config('menus.admin'))->firstWhere('id', 'ai-account-pools');

        $this->assertNotNull($group, 'มีเมนู Account AI Pools ด้านนอก');
        $routes = array_column($group['submenu'], 'route');
        $this->assertContains('admin.ai-api-keys.index', $routes);
        $this->assertContains('admin.fortune.character-voices.index', $routes);
        foreach ($routes as $route) {
            $this->assertTrue(\Illuminate\Support\Facades\Route::has($route), $route);
        }
    }

    // ───────────────────────────── จูนเสียงละเอียด ─────────────────────────────

    private function setModel(string $model): void
    {
        FortuneTellingSetting::getGlobalSettings()->setElevenlabsTtsModel($model);
        FortuneTellingSetting::clearSettingsCache();
    }

    private function fullTuning(): array
    {
        return [
            'output_format' => 'mp3_44100_192',
            'language_code' => 'th',
            'apply_text_normalization' => 'on',
            'seed' => 42,
            'voice' => ['stability' => 0.3, 'similarity_boost' => 0.9, 'style' => 0.4, 'use_speaker_boost' => false, 'speed' => 1.5, 'clone_strength' => 0.2],
            'effects' => ['filter_preset_id' => 'old_radio', 'environment_id' => 'hall', 'send_level' => 0.6, 'distance' => 0, 'pan' => -0.3, 'background_noise_id' => null],
        ];
    }

    public function test_untuned_payload_is_empty_so_existing_cache_keys_stay(): void
    {
        $provider = new ElevenLabsTtsProvider(FortuneTellingSetting::getGlobalSettings());

        $this->assertSame([], $provider->tuningPayload([]));
        $this->assertSame([], $provider->tuningPayload(['output_format' => 'mp3_44100_128', 'apply_text_normalization' => 'auto', 'voice' => null, 'effects' => ['pan' => 0, 'distance' => 0]]));
        $this->assertSame([], (new FortuneCharacterVoiceService)->tuningFor('maemor')['voice'] ?? [], 'ยังไม่เคยจูน = ไม่ส่งค่าน้ำเสียง');
    }

    public function test_payload_only_sends_what_each_model_accepts(): void
    {
        $this->setModel('eleven_v4');
        $v4 = (new ElevenLabsTtsProvider(FortuneTellingSetting::getGlobalSettings()))->tuningPayload($this->fullTuning());
        $this->assertSame(['stability' => 0.3, 'similarity_boost' => 0.9, 'clone_strength' => 0.2], $v4['body']['voice_settings'], 'v4 ไม่มี style/speed');
        $this->assertSame('mp3_44100_192', $v4['output_format']);
        $this->assertSame('th', $v4['body']['language_code']);
        $this->assertSame('on', $v4['body']['apply_text_normalization']);
        $this->assertSame(42, $v4['body']['seed']);
        $this->assertSame(['filter_preset_id' => 'old_radio', 'environment_id' => 'hall', 'send_level' => 0.6, 'pan' => -0.3], $v4['body']['audio_effects'], 'ระยะห่าง 0 = ค่ากลาง ไม่ต้องส่ง');

        $this->setModel('eleven_v3');
        $v3 = (new ElevenLabsTtsProvider(FortuneTellingSetting::getGlobalSettings()))->tuningPayload($this->fullTuning());
        $this->assertSame(['stability' => 0.5], $v3['body']['voice_settings'], 'v3 รับแค่ stability 0/0.5/1');

        $this->setModel('eleven_multilingual_v2');
        $old = (new ElevenLabsTtsProvider(FortuneTellingSetting::getGlobalSettings()))->tuningPayload($this->fullTuning());
        $this->assertSame(['stability' => 0.3, 'similarity_boost' => 0.9, 'style' => 0.4, 'use_speaker_boost' => false, 'speed' => 1.2], $old['body']['voice_settings'], 'speed ถูกตัดที่ 1.2');
        $this->assertArrayNotHasKey('language_code', $old['body'], 'multilingual_v2 ไม่รับ language_code');
    }

    public function test_tuned_voice_is_sent_and_gets_its_own_cache_file(): void
    {
        $this->setKey();
        $this->setModel('eleven_v4');
        $service = new FortuneCharacterVoiceService(FortuneTellingSetting::getGlobalSettings());
        $plain = $service->request('busaba', 'สวัสดีค่า', 'chat');
        $this->assertArrayNotHasKey('voice_settings', $this->ttsCalls()[0]['body']);

        FortuneTellingSetting::getGlobalSettings()->setElevenlabsTuning([
            'output_format' => 'mp3_44100_192',
            'personas' => ['busaba' => ['custom' => true, 'stability' => 0.35, 'similarity_boost' => 0.8, 'speed' => 1.1, 'environment_id' => 'hall', 'send_level' => 0.4]],
        ]);
        FortuneTellingSetting::clearSettingsCache();
        $service = new FortuneCharacterVoiceService(FortuneTellingSetting::getGlobalSettings());

        $tuned = $service->request('busaba', 'สวัสดีค่า', 'chat');
        $this->assertNotSame($plain['clip']->id, $tuned['clip']->id, 'จูนแล้ว = ไฟล์ใหม่');
        $call = $this->ttsCalls()[1];
        $this->assertStringContainsString('output_format=mp3_44100_192', $call['url']);
        $this->assertSame(['stability' => 0.35, 'similarity_boost' => 0.8], $call['body']['voice_settings']);
        $this->assertSame(['environment_id' => 'hall', 'send_level' => 0.4], $call['body']['audio_effects']);

        $service->request('busaba', 'สวัสดีค่า', 'chat');
        $this->assertCount(2, $this->ttsCalls(), 'ค่าจูนเดิม = ไฟล์เดิม ไม่สั่งซ้ำ');
    }

    public function test_admin_saves_tuning_and_rejects_unknown_effects_or_out_of_range_values(): void
    {
        $this->withoutMiddleware();
        $this->setKey();
        $base = ['output_format' => 'mp3_44100_128', 'language_code' => 'th', 'apply_text_normalization' => 'auto'];

        $this->from('/admin/fortune/character-voices')->put('/admin/fortune/character-voices/tuning', $base + [
            'personas' => ['kunsai' => ['custom' => '1', 'stability' => '0.2', 'similarity_boost' => '0.7', 'speed' => '0.9', 'use_speaker_boost' => '0', 'seed' => '777', 'environment_id' => 'hall', 'send_level' => '0.8', 'pan' => '0']],
        ])->assertRedirect()->assertSessionHasNoErrors();

        FortuneTellingSetting::clearSettingsCache();
        $saved = FortuneTellingSetting::getGlobalSettings()->getElevenlabsTuning();
        $this->assertSame('th', $saved['language_code']);
        $this->assertTrue($saved['personas']['kunsai']['custom']);
        $this->assertSame(0.2, $saved['personas']['kunsai']['stability']);
        $this->assertSame(777, $saved['personas']['kunsai']['seed']);
        $this->assertSame('hall', $saved['personas']['kunsai']['environment_id']);
        $this->assertFalse($saved['personas']['maemor']['custom'], 'ตัวละครที่ไม่ได้ส่งมา = ไม่จูน');

        // เอฟเฟกต์ที่ไม่มีในบัญชี = ปฏิเสธ (ElevenLabs จะตอบ 404 ทั้งคำขอ) และไม่ทับค่าเดิม
        $this->from('/admin/fortune/character-voices')->put('/admin/fortune/character-voices/tuning', $base + [
            'personas' => ['kunsai' => ['background_noise_id' => 'thunderstorm']],
        ])->assertSessionHasErrors('personas.kunsai.background_noise_id');

        $this->from('/admin/fortune/character-voices')->put('/admin/fortune/character-voices/tuning', $base + [
            'personas' => ['maemor' => ['speed' => '2']],
        ])->assertSessionHasErrors('personas.maemor.speed');

        $this->from('/admin/fortune/character-voices')->put('/admin/fortune/character-voices/tuning', ['output_format' => 'wav_44100', 'apply_text_normalization' => 'auto'])
            ->assertSessionHasErrors('output_format');

        FortuneTellingSetting::clearSettingsCache();
        $this->assertSame('hall', FortuneTellingSetting::getGlobalSettings()->getElevenlabsTuning()['personas']['kunsai']['environment_id']);
    }

    public function test_admin_previews_unsaved_tuning_without_saving_it(): void
    {
        $this->withoutMiddleware();
        $this->setKey();
        $this->setModel('eleven_v4');
        $base = ['output_format' => 'mp3_44100_192', 'language_code' => '', 'apply_text_normalization' => 'auto'];
        $form = $base + ['persona' => 'busaba', 'text' => 'ลองฟังเสียงบุษบาค่ะ', 'personas' => ['busaba' => [
            'custom' => '1', 'stability' => '0.3', 'similarity_boost' => '0.9', 'style' => '0.4', 'use_speaker_boost' => '1',
            'speed' => '1.1', 'clone_strength' => '', 'seed' => '', 'environment_id' => 'hall', 'send_level' => '0.5', 'distance' => '0', 'pan' => '0',
        ]]];

        $res = $this->postJson('/admin/fortune/character-voices/tuning/preview', $form)->assertOk()->assertJsonPath('success', true);
        $this->assertSame('completed', $res->json('data.status'));
        $this->assertNotEmpty($res->json('data.url'));

        $call = $this->ttsCalls()[0];
        $this->assertStringContainsString('output_format=mp3_44100_192', $call['url']);
        $this->assertSame('ลองฟังเสียงบุษบาค่ะ', $call['body']['text']);
        $this->assertSame(['stability' => 0.3, 'similarity_boost' => 0.9], $call['body']['voice_settings'], 'v4 ส่งเฉพาะค่าที่โมเดลรับ');
        $this->assertSame(['environment_id' => 'hall', 'send_level' => 0.5], $call['body']['audio_effects']);

        FortuneTellingSetting::clearSettingsCache();
        $this->assertSame([], FortuneTellingSetting::getGlobalSettings()->getElevenlabsTuning()['personas'] ?? [], 'ลองฟัง = ไม่บันทึกค่า');

        // ค่าเดิมกดซ้ำ = ไฟล์เดิม ไม่เสียโควต้าซ้ำ
        $this->postJson('/admin/fortune/character-voices/tuning/preview', $form)->assertOk();
        $this->assertCount(1, $this->ttsCalls());

        // บันทึกค่าเดียวกันแล้ว ห้องแชทได้ไฟล์ที่ลองฟังไว้ทันที (กุญแจแคชตรงกัน)
        $this->from('/admin/fortune/character-voices')->put('/admin/fortune/character-voices/tuning', $form)->assertSessionHasNoErrors();
        FortuneTellingSetting::clearSettingsCache();
        $chat = (new FortuneCharacterVoiceService(FortuneTellingSetting::getGlobalSettings()))->request('busaba', 'ลองฟังเสียงบุษบาค่ะ', 'chat');
        $this->assertSame($res->json('data.clip_id'), $chat['clip']->id);
        $this->assertCount(1, $this->ttsCalls());
    }

    public function test_tuning_preview_rejects_bad_input_before_calling_elevenlabs(): void
    {
        $this->withoutMiddleware();
        $this->setKey();
        $base = ['output_format' => 'mp3_44100_128', 'apply_text_normalization' => 'auto'];

        $this->postJson('/admin/fortune/character-voices/tuning/preview', $base + ['persona' => 'nobody'])
            ->assertStatus(422)->assertJsonPath('error', 'ไม่รู้จักตัวละครนี้');
        $this->postJson('/admin/fortune/character-voices/tuning/preview', $base + ['persona' => 'maemor', 'personas' => ['maemor' => ['speed' => '2']]])
            ->assertStatus(422)->assertJsonValidationErrors('personas.maemor.speed');
        $this->postJson('/admin/fortune/character-voices/tuning/preview', $base + ['persona' => 'maemor', 'personas' => ['maemor' => ['background_noise_id' => 'thunderstorm']]])
            ->assertStatus(422)->assertJsonPath('success', false);

        $this->assertCount(0, $this->ttsCalls());
    }

    public function test_audio_effect_catalog_is_read_live_and_drops_bad_ids(): void
    {
        $this->setKey();

        $live = (new ElevenLabsTtsProvider(FortuneTellingSetting::getGlobalSettings()))->listAudioEffects();
        $this->assertTrue($live['live']);
        $this->assertSame([['id' => 'hall', 'name' => 'Hall']], $live['effects']['environments']);

        $this->fakeVendors(fn ($path) => $path === '/v1/audio-effects/catalog' ? Http::response('down', 503) : null);
        $down = (new ElevenLabsTtsProvider(FortuneTellingSetting::getGlobalSettings()))->listAudioEffects();
        $this->assertFalse($down['live']);
        $this->assertSame(ElevenLabsTtsProvider::FALLBACK_AUDIO_EFFECTS, $down['effects']);
    }

    public function test_tuning_form_renders_saved_values_and_marks_unused_fields(): void
    {
        $html = view('admin.fortune.character-voices._elevenlabs-tuning', [
            'model' => 'eleven_v4',
            'supportedSettings' => ElevenLabsTtsProvider::supportedVoiceSettings('eleven_v4'),
            'audioEffects' => ['effects' => ElevenLabsTtsProvider::FALLBACK_AUDIO_EFFECTS, 'live' => false],
            'tuning' => ['output_format' => 'mp3_44100_192', 'personas' => ['kunsai' => ['custom' => true, 'background_noise_id' => 'cafe', 'seed' => 9]]],
        ])->render();

        $this->assertStringContainsString('name="personas[kunsai][speed]"', $html);
        $this->assertStringContainsString('value="mp3_44100_192" selected', $html);
        $this->assertStringContainsString('value="cafe" selected', $html);
        $this->assertStringContainsString('value="9"', $html);
        // ป้ายครบทุกค่า (6 × 3 ตัวละคร) ให้ Alpine สลับตามดรอปดาวน์ · ตอนโหลดโชว์เฉพาะที่ v4 ไม่ใช้ ที่เหลือ x-cloak
        $this->assertSame(6 * 3, substr_count($html, 'x-show="!supports('));
        $this->assertSame(3 * 3, preg_match_all('/x-show="!supports\(\'(style|speed|use_speaker_boost)\'\)" class=/', $html), 'v4: style / speed / speaker boost ไม่ใช้ × 3 ตัวละคร');
        $this->assertSame(3 * 3, preg_match_all('/x-show="!supports\(\'(stability|similarity_boost|clone_strength)\'\)" x-cloak/', $html));
    }

    public function test_tuning_preview_uses_the_model_picked_in_the_dropdown_without_saving_it(): void
    {
        $this->withoutMiddleware();
        $this->setKey();
        $this->setModel('eleven_v4');
        $form = ['output_format' => 'mp3_44100_128', 'apply_text_normalization' => 'auto', 'persona' => 'maemor', 'text' => 'ทดสอบโมเดลค่ะ',
            'personas' => ['maemor' => ['custom' => '1', 'stability' => '0.4', 'similarity_boost' => '0.8', 'style' => '0.3']]];

        $this->postJson('/admin/fortune/character-voices/tuning/preview', $form + ['model' => 'eleven_v4_turbo'])->assertOk()->assertJsonPath('success', true);
        $this->assertSame('eleven_v4_turbo', $this->ttsCalls()[0]['body']['model_id']);
        $this->assertSame(['stability' => 0.4, 'similarity_boost' => 0.8], $this->ttsCalls()[0]['body']['voice_settings']);
        FortuneTellingSetting::clearSettingsCache();
        $this->assertSame('eleven_v4', (new ElevenLabsTtsProvider(FortuneTellingSetting::getGlobalSettings()))->model(), 'ลองฟัง = ไม่เปลี่ยนโมเดลที่บันทึกไว้');

        // โมเดลที่บันทึกไว้ = ไฟล์คนละไฟล์กับที่ลองฟังโมเดลอื่น (กุญแจแคชมีชื่อโมเดล)
        $this->postJson('/admin/fortune/character-voices/tuning/preview', $form + ['model' => 'eleven_v4'])->assertOk();
        $this->assertSame('eleven_v4', $this->ttsCalls()[1]['body']['model_id']);

        // พูดไทยไม่ได้ / ไม่มีในบัญชี / ชื่อแปลก = ไม่ยิง ElevenLabs
        foreach (['eleven_multilingual_v2', 'eleven_v9', 'Bad Model!'] as $bad) {
            $this->postJson('/admin/fortune/character-voices/tuning/preview', $form + ['model' => $bad])->assertStatus(422);
        }
        $this->assertCount(2, $this->ttsCalls());
    }

    public function test_settings_page_shows_cost_per_model_and_dropdown_drives_tuning_badges(): void
    {
        $this->setKey();
        $this->setModel('eleven_v4');
        $this->asAdminWithBareLayout();

        $html = $this->get('/admin/fortune/character-voices')->assertOk()->getContent();

        $this->assertStringContainsString('x-model="tuneModel"', $html);
        $this->assertStringContainsString('Eleven v4 Turbo — 0.5 เครดิต/ตัวอักษร', $html);
        $this->assertStringContainsString('Eleven v4 — 1 เครดิต/ตัวอักษร (ใช้อยู่)', $html);
        $this->assertStringContainsString('1,000 เครดิต', $html);
        $this->assertStringContainsString('500 เครดิต', $html);
        $this->assertStringContainsString('≈ $0.22', $html, 'แพ็ก $22 / 100,000 เครดิต × 1,000 ตัว');
        $this->assertStringContainsString('≈ $0.11', $html);
        $this->assertStringContainsString('≈ 200,000 ตัวอักษร', $html, 'โมเดลครึ่งราคาพูดได้สองเท่าของเครดิตในแพ็ก');
        // Js::from เข้ารหัสเครื่องหมายคำพูดเป็น "
        $settings = str_replace('"', '\\'.'u0022', '"eleven_v4_turbo":["stability","similarity_boost","clone_strength"]');
        $this->assertStringContainsString($settings, $html, 'ส่งค่าที่แต่ละโมเดลรับให้ Alpine');
    }

    public function test_tuning_partial_compiles(): void
    {
        $compiled = Blade::compileString(file_get_contents(resource_path('views/admin/fortune/character-voices/_elevenlabs-tuning.blade.php')));
        $tmp = tempnam(sys_get_temp_dir(), 'blade');
        file_put_contents($tmp, $compiled);

        exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($tmp), $out, $code);
        @unlink($tmp);
        $this->assertSame(0, $code, implode("\n", $out));
    }

    public function test_admin_accepts_restricted_key_that_cannot_read_quota(): void
    {
        $this->withoutMiddleware();
        $this->fakeVendors(fn ($path) => $path === '/v1/user/subscription'
            ? Http::response(['detail' => ['status' => 'missing_permissions', 'message' => 'The API key you used is missing the permission user_read']], 401)
            : null);

        $this->from('/admin/fortune/character-voices')->put('/admin/fortune/character-voices/settings', [
            'elevenlabs_api_key' => self::KEY,
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');

        FortuneTellingSetting::clearSettingsCache();
        $this->assertSame(self::KEY, FortuneTellingSetting::getGlobalSettings()->getElevenlabsApiKey());
    }

    public function test_admin_sample_then_select_voice_per_persona(): void
    {
        $this->withoutMiddleware();
        $this->setKey();
        $eve = 'BZgkqPqms7Kj9ulSkVzn';

        $this->postJson('/admin/fortune/character-voices/sample', ['persona' => 'busaba', 'voice_id' => $eve])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.voice_id', $eve);

        $this->assertStringContainsString('[laughs]', $this->ttsCalls()[0]['body']['text'], 'แท็กอารมณ์ v3 ถูกส่งไปตามเดิม');
        $this->assertSame('sample', FortuneCharacterVoiceClip::first()->purpose);

        $this->postJson('/admin/fortune/character-voices/select', ['persona' => 'busaba', 'voice_id' => $eve])->assertOk();
        $this->assertSame($eve, (new FortuneCharacterVoiceService)->voiceFor('busaba'));

        // เสียงที่ไม่มีในบัญชีและไม่ใช่เสียงมาตรฐาน = ปฏิเสธ ไม่สั่งงาน
        $this->postJson('/admin/fortune/character-voices/sample', ['persona' => 'maemor', 'voice_id' => 'NotARealVoice123'])
            ->assertStatus(422);
        $this->assertCount(1, $this->ttsCalls());
    }

    public function test_admin_quota_check_reports_remaining_characters(): void
    {
        $this->withoutMiddleware();
        $this->setKey();

        $this->postJson('/admin/fortune/character-voices/credits')
            ->assertOk()
            ->assertJsonPath('balance', 99000)
            ->assertJsonPath('limit', 100000);
    }

    public function test_admin_view_compiles(): void
    {
        $compiled = Blade::compileString(file_get_contents(resource_path('views/admin/fortune/character-voices/index.blade.php')));
        $tmp = tempnam(sys_get_temp_dir(), 'blade');
        file_put_contents($tmp, $compiled);

        exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($tmp), $out, $code);
        @unlink($tmp);
        $this->assertSame(0, $code, implode("\n", $out));
    }

    public function test_admin_page_asks_for_the_elevenlabs_key_not_xdreamer(): void
    {
        $view = file_get_contents(resource_path('views/admin/fortune/character-voices/index.blade.php'));

        $this->assertStringContainsString('name="elevenlabs_api_key"', $view);
        $this->assertStringContainsString('name="elevenlabs_tts_model"', $view);
        $this->assertStringNotContainsString('xdreamer_', $view);
    }

    // ───────────────────────────── chain เสียงเดิมของบอท (X-DREAMER ยังอยู่) ─────────────────────────────

    public function test_xdreamer_provider_in_bot_chain_still_uses_maemor_voice(): void
    {
        FortuneTellingSetting::getGlobalSettings()->update(['xdreamer_api_key' => self::XD_KEY, 'maemor_voice_id' => 'Z3R5wn05IrDiVCyEkUrK']);
        FortuneTellingSetting::clearSettingsCache();

        $out = storage_path('app/tmp-voice/test-'.uniqid().'.mp3');
        $result = (new XDreamerTtsProvider(FortuneTellingSetting::getGlobalSettings()))
            ->synthesize('คำทำนายของลูกวันนี้', ['output_path' => $out, 'voice' => 'Thai_female_1_sample1']);

        $this->assertTrue($result['success'], (string) $result['error']);
        $this->assertFileExists($out);
        $this->assertSame('Z3R5wn05IrDiVCyEkUrK', $result['voice_used']);
        @unlink($out);
    }

    // ─────────────────── ตัวละคร + คลังบทพูด + ตารางคลิป (2026-11-04) ───────────────────

    public function test_migration_seeds_builtin_characters_and_library_lines(): void
    {
        $this->assertSame(['maemor', 'busaba', 'kunsai'], array_keys(FortuneCharacterVoiceService::personas()));
        $this->assertTrue(FortuneCharacterVoiceService::personas()['busaba']['builtin']);
        $this->assertSame(JuntraVoiceLibrary::DEFAULT_CLIPS['busaba'], JuntraVoiceLibrary::clips('busaba'));
        $this->assertSame(array_sum(array_map('count', JuntraVoiceLibrary::DEFAULT_CLIPS)), FortuneVoiceLine::count());

        // seed ซ้ำไม่ทับค่าที่แอดมินแก้
        FortuneVoiceLine::where('persona', 'busaba')->where('line_key', 'ack_1')->first()->update(['script' => '[softly] อือ…']);
        \App\Support\FortuneVoiceDefaults::seed();
        $this->assertSame('[softly] อือ…', JuntraVoiceLibrary::clips('busaba')['ack_1']);

        // เสียงที่เลือกไว้ก่อนมีตาราง (คอลัมน์ในค่ากลาง) ยังใช้ได้
        FortuneTellingSetting::getGlobalSettings()->update(['busaba_voice_id' => 'EXAVITQu4vr4xnSDxMaL']);
        FortuneTellingSetting::clearSettingsCache();
        $this->assertSame('EXAVITQu4vr4xnSDxMaL', (new FortuneCharacterVoiceService(FortuneTellingSetting::getGlobalSettings()))->voiceFor('busaba'));
    }

    public function test_admin_adds_a_character_for_a_new_room_with_its_own_voice_and_model(): void
    {
        $this->setKey();
        $this->setModel('eleven_v4');
        $this->asAdminWithBareLayout();
        $voice = ElevenLabsTtsProvider::FALLBACK_VOICES[2]['id'];

        $this->post('/admin/fortune/character-voices/characters', [
            'key' => 'tarot_room', 'label' => 'ยายทองไพ่ยิปซี', 'brief' => 'หญิงวัย 60 ใจดี',
            'sample_text' => '[softly] มาจ้ะหลาน', 'voice_id' => $voice, 'model' => 'eleven_v4_turbo', 'copy_lines_from' => 'maemor',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertTrue(FortuneCharacterVoiceService::isPersona('tarot_room'));
        $this->assertSame(FortuneVoiceLine::where('persona', 'maemor')->count(), FortuneVoiceLine::where('persona', 'tarot_room')->count(), 'คัดลอกบทจากแม่หมอ');
        $this->assertSame(JuntraVoiceLibrary::DEFAULT_CLIPS['maemor']['bye_1'], JuntraVoiceLibrary::clips('tarot_room')['bye_1']);

        // ห้องใหม่ขอเสียง → ใช้เสียงและโมเดลของตัวละครนั้น
        $result = (new FortuneCharacterVoiceService(FortuneTellingSetting::getGlobalSettings()))->request('tarot_room', 'ไพ่ใบนี้บอกว่าดีจ้ะ', 'chat');
        $this->assertTrue($result['success']);
        $call = $this->ttsCalls()[0];
        $this->assertSame('eleven_v4_turbo', $call['body']['model_id']);
        $this->assertStringContainsString('/v1/text-to-speech/'.$voice, $call['url']);

        // หน้าตัวละคร + คลังของตัวใหม่เปิดได้
        $this->get('/admin/fortune/character-voices/characters')->assertOk()->assertSee('ยายทองไพ่ยิปซี')->assertSee('tarot_room');
        $this->get('/admin/fortune/character-voices/library?persona=tarot_room')->assertOk()->assertSee('ยายทองไพ่ยิปซี');

        // รหัสซ้ำ / ผิดรูปแบบ = ไม่สร้าง
        $this->post('/admin/fortune/character-voices/characters', ['key' => 'tarot_room', 'label' => 'x', 'sample_text' => 'x'])->assertSessionHasErrors('key');
        $this->post('/admin/fortune/character-voices/characters', ['key' => 'Bad Key!', 'label' => 'x', 'sample_text' => 'x'])->assertSessionHasErrors('key');

        // ลบตัวใหม่ได้ (บทหายด้วย) · ตัวของห้องเดิมลบไม่ได้
        $tarot = FortuneVoiceCharacter::where('key', 'tarot_room')->first();
        $this->delete('/admin/fortune/character-voices/characters/'.$tarot->id)->assertSessionHas('success');
        $this->assertFalse(FortuneCharacterVoiceService::isPersona('tarot_room'));
        $this->assertSame(0, FortuneVoiceLine::where('persona', 'tarot_room')->count());
        $this->assertSame(JuntraVoiceLibrary::clips('maemor'), JuntraVoiceLibrary::clips('tarot_room'), 'ตัวที่ลบแล้วถอยไปใช้คลังแม่หมอ');

        $this->flushSession(); // ไม่มี StartSession → error ของคำขอก่อนหน้าค้างใน session
        $maemor = FortuneVoiceCharacter::where('key', 'maemor')->first();
        $this->delete('/admin/fortune/character-voices/characters/'.$maemor->id)->assertSessionHas('error');
        $this->put('/admin/fortune/character-voices/characters/'.$maemor->id, ['label' => 'แม่หมอจันทรา', 'sample_text' => 'สวัสดีจ้ะ', 'is_active' => '0'])->assertSessionHasNoErrors();
        $this->assertTrue(FortuneCharacterVoiceService::isPersona('maemor'), 'ตัวของห้องเดิมปิดไม่ได้');
    }

    public function test_library_line_generates_with_the_batch_model_and_voice_and_reuses_the_file(): void
    {
        $this->setKey();
        $this->setModel('eleven_v4');
        $this->asAdminWithBareLayout();
        $line = FortuneVoiceLine::where('persona', 'busaba')->where('line_key', 'listen_1')->first();
        $voice = ElevenLabsTtsProvider::FALLBACK_VOICES[1]['id'];

        $res = $this->postJson("/admin/fortune/character-voices/library/lines/{$line->id}/generate", ['model' => 'eleven_v4_turbo', 'voice_id' => $voice])
            ->assertOk()->assertJsonPath('data.status', 'completed');
        $this->assertSame('eleven_v4_turbo', $this->ttsCalls()[0]['body']['model_id']);
        $this->assertStringContainsString('/v1/text-to-speech/'.$voice, $this->ttsCalls()[0]['url']);
        $this->assertSame($res->json('data.clip_id'), $line->fresh()->clip_id);
        $this->assertSame('library', FortuneCharacterVoiceClip::find($res->json('data.clip_id'))->purpose);
        $this->assertSame(0, (new FortuneCharacterVoiceService(FortuneTellingSetting::getGlobalSettings()))->charsUsedToday(), 'สร้างคลังไม่กินเพดานรายวันของลูกค้า');

        // แบบเดิมซ้ำ = ไฟล์เดิม ไม่ยิง ElevenLabs
        $this->postJson("/admin/fortune/character-voices/library/lines/{$line->id}/generate", ['model' => 'eleven_v4_turbo', 'voice_id' => $voice])->assertOk();
        $this->assertCount(1, $this->ttsCalls());

        // ไม่ส่งโมเดล/เสียง = ของบรรทัด → ของตัวละคร → ค่ากลาง
        $this->postJson("/admin/fortune/character-voices/library/lines/{$line->id}/generate")->assertOk();
        $this->assertSame('eleven_v4', $this->ttsCalls()[1]['body']['model_id']);

        // โมเดลที่พูดไทยไม่ได้ / เสียงแปลก = ไม่ยิง
        $this->postJson("/admin/fortune/character-voices/library/lines/{$line->id}/generate", ['model' => 'eleven_multilingual_v2'])->assertStatus(422);
        $this->postJson("/admin/fortune/character-voices/library/lines/{$line->id}/generate", ['voice_id' => 'NotAVoice123'])->assertStatus(422);
        $this->assertCount(2, $this->ttsCalls());

        // แก้ข้อความ = ไฟล์เดิมไม่ตรงบท ปลดออก · แก้แค่เปิดปิด = ไฟล์ยังอยู่
        $this->putJson("/admin/fortune/character-voices/library/lines/{$line->id}", ['script' => $line->script, 'is_active' => false])->assertOk();
        $this->assertNotNull($line->fresh()->clip_id);
        $this->assertArrayNotHasKey('listen_1', JuntraVoiceLibrary::clips('busaba'), 'ปิดแล้ว AI ไม่เห็นบทนี้');
        $this->putJson("/admin/fortune/character-voices/library/lines/{$line->id}", ['script' => '[softly] เล่าต่อได้เลยค่ะ', 'is_active' => true])->assertOk();
        $this->assertNull($line->fresh()->clip_id);
        $this->assertSame('[softly] เล่าต่อได้เลยค่ะ', JuntraVoiceLibrary::clips('busaba')['listen_1']);

        // หน้าคลังเปิดได้ · เพิ่มบทหมวดใหม่ได้ id ต่อเลข
        $this->get('/admin/fortune/character-voices/library?persona=busaba')->assertOk()->assertSee('listen_1');
        $this->post('/admin/fortune/character-voices/library/lines', ['persona' => 'busaba', 'category' => 'greet', 'script' => '[warmly] ยินดีต้อนรับค่ะ'])->assertSessionHasNoErrors();
        $this->post('/admin/fortune/character-voices/library/lines', ['persona' => 'busaba', 'category' => 'greet', 'script' => '[warmly] มาแล้วเหรอคะ'])->assertSessionHasNoErrors();
        $this->assertSame(['greet_1', 'greet_2'], FortuneVoiceLine::where('category', 'greet')->orderBy('id')->pluck('line_key')->all());
        $this->post('/admin/fortune/character-voices/library/lines', ['persona' => 'busaba', 'category' => 'Greet 2', 'script' => 'x'])->assertSessionHasErrors('category');
    }

    public function test_clip_table_lists_filters_and_deletes_clips(): void
    {
        $this->setKey();
        $this->asAdminWithBareLayout();
        $service = new FortuneCharacterVoiceService(FortuneTellingSetting::getGlobalSettings());
        $chat = $service->request('maemor', 'ดวงเดือนนี้ดีนะลูก', 'chat')['clip'];
        $line = FortuneVoiceLine::where('persona', 'busaba')->where('line_key', 'ack_1')->first();
        $lib = $this->postJson("/admin/fortune/character-voices/library/lines/{$line->id}/generate")->json('data.clip_id');

        $this->get('/admin/fortune/character-voices/all-clips')->assertOk()
            ->assertSee('ดวงเดือนนี้ดีนะลูก')->assertSee('ack_1')->assertSee('คลังบทพูด');
        $this->get('/admin/fortune/character-voices/all-clips?purpose=chat')->assertOk()
            ->assertSee('ดวงเดือนนี้ดีนะลูก')->assertDontSee('ack_1');

        $this->delete('/admin/fortune/character-voices/all-clips', ['ids' => [$lib]])->assertSessionHas('success');
        $this->assertNull(FortuneCharacterVoiceClip::find($lib));
        $this->assertNull($line->fresh()->clip_id, 'บทที่ผูกไฟล์ที่ลบ กลับเป็นยังไม่มีเสียง');
        $this->assertNotNull(FortuneCharacterVoiceClip::find($chat->id));
        $this->delete('/admin/fortune/character-voices/all-clips', ['ids' => []])->assertSessionHasErrors('ids');
    }

    public function test_juntra_server_reads_the_playable_library_with_urls(): void
    {
        $this->setKey();
        $this->actAsJuntraServer();
        $line = FortuneVoiceLine::where('persona', 'busaba')->where('line_key', 'bye_1')->first();
        $clip = (new FortuneCharacterVoiceService(FortuneTellingSetting::getGlobalSettings()))->request('busaba', $line->script, 'library')['clip'];
        $line->update(['clip_id' => $clip->id]);

        $res = $this->withToken('server-token')->getJson('/api/v1/juntra/server/voice/library?persona=busaba')->assertOk();
        $this->assertSame(['bye_1'], array_keys($res->json('data.clips')), 'เฉพาะบทที่มีไฟล์แล้ว');
        $this->assertSame($clip->audio_url, $res->json('data.clips.bye_1.url'));
        $this->assertStringNotContainsString('[', $res->json('data.clips.bye_1.text'));

        $this->withToken('server-token')->getJson('/api/v1/juntra/server/voice/library?persona=nobody')->assertStatus(422);
    }
}
