<?php

namespace Tests\Feature;

use App\Models\FortuneVoiceUsage;
use App\Models\Setting;
use App\Services\FortuneAIService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Mockery;
use Tests\Concerns\BuildsJuntraServerSchema;
use Tests\TestCase;

/**
 * 🎬 (2026-11-04) POST /api/v1/juntra/server/chat/greeting — คำทักทายเฉพาะตัวของลูกค้าคุ้นเคย (ข้อ 17.7)
 *
 * เสียงออกลำโพง → AI ต้องไม่ได้เห็นข้อมูลที่พูดออกเสียงไม่ได้ · ต่อคน 1 ครั้ง/ห้อง/วัน · เพดานทั้งเว็บ
 */
class JuntraRegularGreetingApiTest extends TestCase
{
    use BuildsJuntraServerSchema;

    private array $aiCalls = [];

    private array $aiScript = [];

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
        config(['services.juntra.server_client_ids' => []]);
        Cache::flush();

        $client = app(ClientRepository::class)->create(null, 'Juntra Chantra SSO', 'https://xn--82c4af5bzdj.online/auth/thaiprompt/callback', 'oauth_users');
        Passport::actingAsClient($client, [], 'api-oauth');

        $ai = Mockery::mock(FortuneAIService::class);
        // 📚 แชทเว็บดึงคำตอบตัวอย่างของแอดมิน (RAG) ก่อนยิง AI — ในเทสต์ส่ง system message ผ่านไปเฉย ๆ
        $ai->shouldReceive('injectAdminQARagFewShot')->andReturnUsing(fn (string $system) => $system);
        $ai->shouldReceive('chatWithCustomSystemPrompt')->andReturnUsing(function (...$args) {
            $this->aiCalls[] = ['system' => $args[0], 'user' => $args[1]];
            $next = array_shift($this->aiScript);
            if ($next instanceof \Throwable) {
                throw $next;
            }

            return ['response' => FortuneAIService::stripInternalContextTags((string) ($next ?? '')), 'provider' => 'gemini'];
        });
        $this->app->instance(FortuneAIService::class, $ai);
    }

    private function greet(array $overrides = [])
    {
        return $this->withToken('server-token')->postJson('/api/v1/juntra/server/chat/greeting', $overrides + [
            'user_ref' => '42',
            'persona' => 'maemor',
            'facts' => ['name' => 'สมใจ', 'zodiac' => 'มีน', 'visits' => 9, 'days_away' => 12, 'last_topic' => 'love'],
        ]);
    }

    public function test_a_regular_gets_a_personal_greeting_once_a_day_per_room(): void
    {
        $this->aiScript = ['{warmly} คุณสมใจ ห่างหายไป 12 วัน แม่หมอคิดถึงนะ วันนี้อยากรู้เรื่องไหนจ๊ะ'];

        $res = $this->greet()->assertOk()
            ->assertJsonPath('data.say', '[warmly] คุณสมใจ ห่างหายไป 12 วัน แม่หมอคิดถึงนะ วันนี้อยากรู้เรื่องไหนจ๊ะ')
            ->assertJsonPath('data.greeting', 'คุณสมใจ ห่างหายไป 12 วัน แม่หมอคิดถึงนะ วันนี้อยากรู้เรื่องไหนจ๊ะ')
            ->assertJsonPath('data.cached', false);

        $this->assertStringContainsString('ห่างหายไป 12 วัน', $this->aiCalls[0]['user']);
        $this->assertStringContainsString('ความรัก', $this->aiCalls[0]['user']);

        // ขอซ้ำวันเดียวกัน = ข้อความเดิม (เสียงเดิมจากแคช) ไม่เรียก AI
        $this->greet()->assertOk()->assertJsonPath('data.cached', true)->assertJsonPath('data.say', $res->json('data.say'));
        $this->assertCount(1, $this->aiCalls);

        $usage = FortuneVoiceUsage::sole();
        $this->assertSame(['greeting', 'maemor', true], [$usage->kind, $usage->persona, (bool) $usage->success]);
    }

    public function test_things_that_must_not_be_spoken_never_reach_the_ai(): void
    {
        $this->aiScript = ['{warmly} คุณสมใจ แม่หมอดีใจที่คุณกลับมานะ'];

        $this->greet(['facts' => [
            'name' => 'สมใจ',
            'partner_name' => 'สมชาย',
            'last_reading' => 'ไพ่หอคอย ความรักพัง',
            'credit' => 5000,
            'phone' => '0812345678',
        ]])->assertOk();

        $sent = $this->aiCalls[0]['system'].$this->aiCalls[0]['user'];
        foreach (['สมชาย', 'หอคอย', '5000', '0812345678'] as $secret) {
            $this->assertStringNotContainsString($secret, $sent);
        }

        // หมวดสุขภาพ/คุณไสย และชื่อที่มีตัวเลข/อีเมล ไม่รับตั้งแต่ประตู
        $this->greet(['user_ref' => '43', 'facts' => ['last_topic' => 'health']])->assertStatus(422);
        $this->greet(['user_ref' => '43', 'facts' => ['last_topic' => 'kunsai']])->assertStatus(422);
        $this->greet(['user_ref' => '43', 'facts' => ['name' => '0812345678']])->assertStatus(422);
        $this->greet(['user_ref' => '43', 'facts' => ['name' => 'a@b.co']])->assertStatus(422);
    }

    public function test_an_invented_number_or_busaba_saying_luuk_is_not_spoken(): void
    {
        $this->aiScript = ['{warmly} คุณสมใจ นี่ครั้งที่ 50 แล้วนะ'];
        $this->greet()->assertStatus(503);

        $this->aiScript = ['{warmly} ลูกจ๋า บุษบาคิดถึงนะคะ'];
        $this->greet(['persona' => 'busaba', 'user_ref' => '44'])->assertStatus(503);

        // 🗣️ (2026-11-10, owner) แม่หมอก็เลิกเรียก "ลูก" แล้ว — หลุดมา = ไม่พูด
        $this->aiScript = ['{warmly} สมใจลูก แม่หมอคิดถึงนะคะ'];
        $this->greet(['user_ref' => '46'])->assertStatus(503);

        // ชื่อเรียกของลูกค้าเองมีคำว่า "ลูก" ได้
        $this->aiScript = ['{warmly} คุณลูกหมีคะ บุษบาดีใจที่คุณกลับมาค่ะ'];
        $this->greet(['persona' => 'busaba', 'user_ref' => '45', 'facts' => ['name' => 'ลูกหมี']])->assertOk();
    }

    public function test_the_site_wide_daily_cap_sends_everyone_to_the_recorded_clip(): void
    {
        Setting::create(['key' => 'voice_regular_greeting_daily_cap', 'value' => '2', 'type' => 'integer', 'group' => 'fortune']);
        $this->aiScript = array_fill(0, 5, '{warmly} คุณกลับมาแล้ว แม่หมอดีใจจัง');

        $this->greet(['user_ref' => '1'])->assertOk();
        $this->greet(['user_ref' => '2'])->assertOk();
        $this->greet(['user_ref' => '3'])->assertStatus(429)->assertJsonPath('error', 'greeting_cap');
        // คนที่ได้ไปแล้วยังได้ของเดิม (ไม่นับเพดานซ้ำ)
        $this->greet(['user_ref' => '1'])->assertOk()->assertJsonPath('data.cached', true);
        $this->assertCount(2, $this->aiCalls);
    }

    public function test_when_the_ai_is_down_the_web_falls_back(): void
    {
        $this->aiScript = [new \RuntimeException('down'), new \RuntimeException('luna down')];

        $this->greet()->assertStatus(503)->assertJsonPath('error', 'greeting_unavailable');
        $this->assertFalse((bool) FortuneVoiceUsage::sole()->success);
    }
}
