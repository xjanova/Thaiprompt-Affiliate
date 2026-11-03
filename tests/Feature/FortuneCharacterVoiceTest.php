<?php

namespace Tests\Feature;

use App\Models\FortuneCharacterVoiceClip;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\FortuneCharacterVoiceService;
use App\Services\Tts\XDreamerTtsProvider;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\Concerns\BuildsJuntraServerSchema;
use Tests\TestCase;

/**
 * 🎭 เสียงพากย์แม่หมอจันทรา/น้องบุษบา — ElevenLabs V3 ผ่าน X-DREAMER (mock HTTP ทั้งหมด ไม่ยิงจริง)
 *
 * ครอบคลุม: key เข้ารหัส/ไม่หลุด · async poll · แคชไม่สั่งซ้ำ · เพดานรายวัน · endpoint ของเว็บจันทรา ·
 *           หน้าแอดมิน (บันทึก key / เสียงตัวอย่าง / เลือกเสียง) · provider ใน chain เสียงเดิมของบอท
 */
class FortuneCharacterVoiceTest extends TestCase
{
    use BuildsJuntraServerSchema;

    private const KEY = 'axm-TESTKEY1234567890abcdef';

    private const AUDIO_URL = 'https://media.xman4289.com/gen/77.mp3';

    /** @var array<int, array{tool: string, args: array}> */
    private array $toolCalls = [];

    /** สถานะที่ get_generation จะตอบ */
    private string $remoteStatus = 'completed';

    /** คำตอบพิเศษต่อ tool — Http::fake ซ้อนกันไม่ได้ (ตัวแรกที่ลงทะเบียนชนะเสมอ) จึงสลับผ่าน property นี้ */
    private $override = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildJuntraServerSchema();
        (require database_path('migrations/2026_11_03_100000_create_fortune_character_voices.php'))->up();
        config(['services.juntra.server_client_ids' => []]);
        Cache::flush();
        Storage::fake('public');
        FortuneTellingSetting::clearSettingsCache();

        $this->fakeXDreamer();
    }

    protected function tearDown(): void
    {
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    private function setKey(?string $key = self::KEY): void
    {
        FortuneTellingSetting::getGlobalSettings()->update(['xdreamer_api_key' => $key]);
        FortuneTellingSetting::clearSettingsCache();
    }

    private function fakeXDreamer(?callable $override = null): void
    {
        if ($override) {
            $this->override = $override;

            return;
        }

        Http::fake(function (HttpRequest $request) {
            $override = $this->override;
            if ($request->url() === self::AUDIO_URL) {
                return Http::response(str_repeat("\xFF\xFB\x90\x00", 400), 200, ['Content-Type' => 'audio/mpeg']);
            }

            $tool = $request['params']['name'] ?? null;
            $args = (array) ($request['params']['arguments'] ?? []);
            $this->toolCalls[] = ['tool' => $tool, 'args' => $args, 'auth' => $request->header('Authorization')[0] ?? null];

            if ($override && ($custom = $override($tool, $args)) !== null) {
                return $custom;
            }

            $result = match ($tool) {
                'generate_speech' => ['id' => 77, 'status' => 'pending', 'credits_used' => 1, 'result_urls' => []],
                'get_generation' => [
                    'id' => 77,
                    'status' => $this->remoteStatus,
                    'result_urls' => $this->remoteStatus === 'completed' ? [self::AUDIO_URL] : [],
                    'error' => $this->remoteStatus === 'failed' ? 'ระบบเสียงขัดข้อง คืนเครดิตแล้ว' : null,
                ],
                'get_credits' => ['balance' => 1234],
                'list_models' => ['models' => [[
                    'id' => 'xd/elevenlabs-v3',
                    'kind' => 'speech',
                    'max_characters' => 5000,
                    'voices' => array_map(fn ($v) => ['id' => $v['id'], 'name' => $v['name'], 'gender' => $v['gender']], XDreamerTtsProvider::FALLBACK_VOICES),
                ]]],
                default => null,
            };

            return Http::response(['jsonrpc' => '2.0', 'id' => 1, 'result' => [
                'content' => [['type' => 'text', 'text' => json_encode($result)]],
                'structuredContent' => $result,
                'isError' => false,
            ]]);
        });
    }

    private function toolCount(string $tool): int
    {
        return count(array_filter($this->toolCalls, fn ($c) => $c['tool'] === $tool));
    }

    private function actAsJuntraServer(): void
    {
        $client = app(ClientRepository::class)->create(null, 'Juntra Chantra SSO', 'https://xn--82c4af5bzdj.online/auth/thaiprompt/callback', 'oauth_users');
        Passport::actingAsClient($client, [], 'api-oauth');
    }

    // ───────────────────────────── key ─────────────────────────────

    public function test_key_is_stored_encrypted_masked_and_never_serialized(): void
    {
        $this->setKey();
        $settings = FortuneTellingSetting::getGlobalSettings();

        $raw = DB::table('fortune_telling_settings')->value('xdreamer_api_key');
        $this->assertNotSame(self::KEY, $raw);
        $this->assertStringNotContainsString('TESTKEY', (string) $raw);

        $this->assertSame(self::KEY, $settings->getXdreamerApiKey());
        $this->assertSame('axm-••••••••cdef', $settings->getXdreamerApiKeyMasked());
        $this->assertArrayNotHasKey('xdreamer_api_key', $settings->toArray());
    }

    public function test_unreadable_key_after_app_key_change_counts_as_not_set(): void
    {
        DB::table('fortune_telling_settings')->update(['xdreamer_api_key' => 'not-an-encrypted-payload']);
        FortuneTellingSetting::clearSettingsCache();

        $this->assertNull(FortuneTellingSetting::getGlobalSettings()->getXdreamerApiKey());
        $this->assertFalse((new XDreamerTtsProvider(FortuneTellingSetting::getGlobalSettings()))->isAvailable());
    }

    public function test_tool_errors_never_echo_the_key(): void
    {
        $this->setKey();
        $this->fakeXDreamer(fn ($tool) => Http::response(['jsonrpc' => '2.0', 'id' => 1, 'result' => [
            'content' => [['type' => 'text', 'text' => 'Insufficient credits for '.self::KEY]],
            'isError' => true,
        ]]));

        $result = (new FortuneCharacterVoiceService)->request('maemor', 'สวัสดีจ้ะลูก', 'chat');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Insufficient credits', $result['error']);
        $this->assertStringNotContainsString('TESTKEY', $result['error']);
        $this->assertStringNotContainsString('TESTKEY', (string) FortuneCharacterVoiceClip::first()->error);
    }

    // ───────────────────────────── service ─────────────────────────────

    public function test_speech_is_ordered_polled_stored_then_served_from_cache(): void
    {
        $this->setKey();
        $service = new FortuneCharacterVoiceService;

        $first = $service->request('maemor', "**ลูกเอ๋ย** ใจเย็น ๆ นะ ✨🔮\n\n[[OFFER:love]]", 'chat', null, 10);

        $this->assertTrue($first['success']);
        $clip = $first['clip']->fresh();
        $this->assertSame('completed', $clip->status);
        $this->assertSame('ลูกเอ๋ย ใจเย็น ๆ นะ', $clip->text, 'markdown/emoji/ป้ายระบบถูกลอกออกก่อนส่งไปอ่าน');
        Storage::disk('public')->assertExists($clip->audio_path);
        $this->assertNotEmpty($clip->audio_url);

        // ส่ง Bearer key + เสียงค่าเริ่มต้นของแม่หมอ + โมเดล ElevenLabs V3
        $order = collect($this->toolCalls)->firstWhere('tool', 'generate_speech');
        $this->assertSame('Bearer '.self::KEY, $order['auth']);
        $this->assertSame('xd/elevenlabs-v3', $order['args']['model']);
        $this->assertSame(FortuneCharacterVoiceService::PERSONAS['maemor']['default_voice'], $order['args']['voice']);

        // ข้อความเดิม = ไฟล์เดิม ไม่สั่งงานซ้ำ
        $again = $service->request('maemor', "**ลูกเอ๋ย** ใจเย็น ๆ นะ ✨🔮\n\n[[OFFER:love]]", 'chat');
        $this->assertSame($clip->id, $again['clip']->id);
        $this->assertSame(1, $this->toolCount('generate_speech'));
        $this->assertSame(1, $again['clip']->fresh()->hits);
        $this->assertSame(200, $again['code']);
    }

    public function test_pending_clip_is_not_ordered_twice_on_double_tap(): void
    {
        $this->setKey();
        $this->remoteStatus = 'processing';
        $service = new FortuneCharacterVoiceService;

        $a = $service->request('busaba', 'สวัสดีค่า บุษบาเองค่ะ', 'chat');
        $b = $service->request('busaba', 'สวัสดีค่า บุษบาเองค่ะ', 'chat');

        $this->assertSame(202, $a['code']);
        $this->assertSame($a['clip']->id, $b['clip']->id);
        $this->assertSame(1, $this->toolCount('generate_speech'));

        // งานเสร็จทีหลัง → poll ได้ไฟล์
        $this->remoteStatus = 'completed';
        $done = $service->refresh($a['clip']->fresh(), 5);
        $this->assertTrue($done->isCompleted());
    }

    public function test_remote_failure_marks_clip_failed(): void
    {
        $this->setKey();
        $this->remoteStatus = 'failed';

        $result = (new FortuneCharacterVoiceService)->request('maemor', 'ไพ่ใบนี้บอกว่า...', 'chat', null, 5);

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

        $this->assertTrue($service->request('maemor', 'สั้น ๆ', 'chat', null, 5)['success']);

        $long = str_repeat('ก', 1001);
        $blocked = $service->request('maemor', $long, 'chat');
        $this->assertSame(429, $blocked['code']);
        $this->assertSame(1, $this->toolCount('generate_speech'));

        // ของในแคชยังเล่นได้
        $this->assertSame(200, $service->request('maemor', 'สั้น ๆ', 'chat')['code']);
    }

    public function test_without_key_nothing_is_called(): void
    {
        $result = (new FortuneCharacterVoiceService)->request('maemor', 'สวัสดี', 'chat');

        $this->assertSame(503, $result['code']);
        $this->assertSame([], $this->toolCalls);
    }

    // ───────────────────────────── API เว็บจันทรา ─────────────────────────────

    public function test_juntra_server_can_request_and_poll_a_voice(): void
    {
        $this->setKey();
        $this->actAsJuntraServer();
        $this->remoteStatus = 'processing';

        $res = $this->withToken('server-token')->postJson('/api/v1/juntra/server/voice/speak', [
            'persona' => 'busaba', 'text' => 'วันนี้ดวงความรักสดใสมากเลยค่า', 'wait' => 0,
        ])->assertStatus(202)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.url', null);

        $clipId = $res->json('data.clip_id');
        $this->remoteStatus = 'completed';

        $this->withToken('server-token')->getJson('/api/v1/juntra/server/voice/clips/'.$clipId.'?wait=1')
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.persona', 'busaba');
    }

    public function test_juntra_server_gets_generic_error_not_vendor_reason(): void
    {
        $this->setKey();
        $this->actAsJuntraServer();
        $this->remoteStatus = 'failed';

        $this->withToken('server-token')->postJson('/api/v1/juntra/server/voice/speak', [
            'persona' => 'maemor', 'text' => 'ไพ่ใบนี้บอกว่า...', 'wait' => 1,
        ])->assertStatus(502)
            ->assertJsonPath('error', 'สร้างเสียงไม่สำเร็จ ลองใหม่อีกครั้งภายหลังนะคะ');
    }

    public function test_kunsai_persona_has_its_own_voice(): void
    {
        $this->setKey();
        $this->actAsJuntraServer();

        $this->withToken('server-token')->postJson('/api/v1/juntra/server/voice/speak', [
            'persona' => 'kunsai', 'text' => '[whispers] ลูกเอ๋ย สิ่งที่ตามลูกมาไม่ใช่เรื่องบังเอิญ', 'wait' => 1,
        ])->assertOk()->assertJsonPath('data.persona', 'kunsai');

        $order = collect($this->toolCalls)->firstWhere('tool', 'generate_speech');
        $this->assertSame('Z3R5wn05IrDiVCyEkUrK', $order['args']['voice']);

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
        $this->assertSame([], $this->toolCalls);
    }

    // ───────────────────────────── หน้าแอดมิน ─────────────────────────────

    public function test_admin_rejected_key_is_not_saved_nor_echoed(): void
    {
        $this->withoutMiddleware();
        $this->fakeXDreamer(fn ($tool) => Http::response(['jsonrpc' => '2.0', 'id' => null, 'error' => ['code' => -32001, 'message' => 'Invalid API key']], 401));

        $res = $this->from('/admin/fortune/character-voices')->put('/admin/fortune/character-voices/settings', [
            'xdreamer_api_key' => self::KEY,
        ]);

        $res->assertRedirect('/admin/fortune/character-voices')->assertSessionHasErrors('xdreamer_api_key');
        $this->assertStringNotContainsString('TESTKEY', json_encode(session('errors')->getMessages()));
        $this->assertNull(session()->getOldInput('xdreamer_api_key'));
        $this->assertNull(FortuneTellingSetting::getGlobalSettings()->getXdreamerApiKey());
    }

    public function test_admin_valid_key_is_tested_then_saved(): void
    {
        $this->withoutMiddleware();

        $this->from('/admin/fortune/character-voices')->put('/admin/fortune/character-voices/settings', [
            'xdreamer_api_key' => self::KEY,
            'character_voice_daily_char_limit' => 50000,
        ])->assertRedirect()->assertSessionHas('success');

        FortuneTellingSetting::clearSettingsCache();
        $settings = FortuneTellingSetting::getGlobalSettings();
        $this->assertSame(self::KEY, $settings->getXdreamerApiKey());
        $this->assertSame(50000, (int) $settings->character_voice_daily_char_limit);
        $this->assertSame(1, $this->toolCount('get_credits'));

        // บันทึกรอบหน้าเว้นช่อง key ว่าง = key เดิมยังอยู่
        $this->put('/admin/fortune/character-voices/settings', ['xdreamer_api_key' => ''])->assertRedirect();
        FortuneTellingSetting::clearSettingsCache();
        $this->assertSame(self::KEY, FortuneTellingSetting::getGlobalSettings()->getXdreamerApiKey());
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

        $sampleCall = collect($this->toolCalls)->firstWhere('tool', 'generate_speech');
        $this->assertStringContainsString('[laughs]', $sampleCall['args']['text'], 'แท็กอารมณ์ v3 ถูกส่งไปตามเดิม');
        $this->assertSame('sample', FortuneCharacterVoiceClip::first()->purpose);

        $this->postJson('/admin/fortune/character-voices/select', ['persona' => 'busaba', 'voice_id' => $eve])->assertOk();
        $this->assertSame($eve, (new FortuneCharacterVoiceService)->voiceFor('busaba'));

        // เสียงที่ไม่มีในรายชื่อ = ปฏิเสธ ไม่สั่งงาน
        $this->postJson('/admin/fortune/character-voices/sample', ['persona' => 'maemor', 'voice_id' => 'NotARealVoice123'])
            ->assertStatus(422);
        $this->assertSame(1, $this->toolCount('generate_speech'));
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

    // ───────────────────────────── chain เสียงเดิมของบอท ─────────────────────────────

    public function test_provider_synthesize_in_bot_chain_uses_maemor_voice_for_foreign_voice_ids(): void
    {
        $this->setKey();
        FortuneTellingSetting::getGlobalSettings()->update(['maemor_voice_id' => 'Z3R5wn05IrDiVCyEkUrK']);
        FortuneTellingSetting::clearSettingsCache();

        $out = storage_path('app/tmp-voice/test-'.uniqid().'.mp3');
        $result = (new XDreamerTtsProvider(FortuneTellingSetting::getGlobalSettings()))
            ->synthesize('คำทำนายของลูกวันนี้', ['output_path' => $out, 'voice' => 'Thai_female_1_sample1']);

        $this->assertTrue($result['success'], (string) $result['error']);
        $this->assertFileExists($out);
        $this->assertSame('Z3R5wn05IrDiVCyEkUrK', $result['voice_used']);
        @unlink($out);

        $this->assertContains('xdreamer', FortuneTellingSetting::getGlobalSettings()->fill(['voice_summary_primary_provider' => 'xdreamer'])->getVoiceProviderChain());
    }
}
