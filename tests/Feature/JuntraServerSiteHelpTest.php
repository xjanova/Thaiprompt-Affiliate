<?php

namespace Tests\Feature;

use App\Jobs\ExtractCustomerPersonaJob;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\JuntraChatService;
use App\Services\FortuneAIService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Mockery;
use Tests\Concerns\BuildsJuntraServerSchema;
use Tests\TestCase;

/**
 * 🧭 (2026-11-10 owner) แม่หมอบนเว็บต้องช่วยลูกค้าใช้เว็บ/แก้ปัญหา มากกว่าชวนคุยไปเรื่อย
 *
 *   - เว็บส่ง `site_help` = คู่มือเฉพาะหัวข้อที่ตรงคำถาม + สถานะบัญชีจริง → เทิร์นนั้นตอบตามคู่มือ
 *     ไม่ชวนเปิดไพ่ ไม่ใช้ตัวอย่างจากแชทเพจ อุณหภูมิต่ำ
 *   - ทุกเทิร์นของทางเซิร์ฟเวอร์ได้กติกา "พาไปก้าวถัดไป ไม่แต่งฟีเจอร์"
 *   - ทาง token (แอพ) prompt เดิมทุกตัวอักษร
 */
class JuntraServerSiteHelpTest extends TestCase
{
    use BuildsJuntraServerSchema;

    /** @var array<int,array{system:string,user:string,config:array}> */
    private array $aiCalls = [];

    private int $ragCalls = 0;

    private ?string $aiReply = 'แม่หมออยู่ตรงนี้นะคะลูก ✨';

    private const HELP = "## เติมเงินเข้าวอลเลต\n1. กดเมนู \"วอลเลต\" แล้วกด \"เติมเงิน\"\n\nสถานะบัญชีของลูกค้าตอนนี้:\n- เครดิตคงเหลือ ฿1.39";

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildJuntraServerSchema();
        config(['services.juntra.server_client_ids' => []]);
        Cache::flush();
        Bus::fake([ExtractCustomerPersonaJob::class]);

        $client = app(ClientRepository::class)->create(
            null,
            'Juntra Chantra SSO',
            'https://xn--82c4af5bzdj.online/auth/thaiprompt/callback',
            'oauth_users'
        );
        Passport::actingAsClient($client, [], 'api-oauth');

        $ai = Mockery::mock(FortuneAIService::class);
        $ai->shouldReceive('injectAdminQARagFewShot')->andReturnUsing(function (string $system) {
            $this->ragCalls++;

            return $system;
        });
        $ai->shouldReceive('withCustomerContext')->andReturnSelf();
        $ai->shouldReceive('chatWithCustomSystemPrompt')->andReturnUsing(function (...$args) {
            $this->aiCalls[] = ['system' => $args[0], 'user' => $args[1], 'config' => $args[2] ?? []];

            return ['response' => $this->aiReply, 'provider' => 'gemini'];
        });
        $this->app->instance(FortuneAIService::class, $ai);
    }

    protected function tearDown(): void
    {
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    private function start(): string
    {
        return $this->withToken('server-token')
            ->postJson('/api/v1/juntra/server/chat/start', ['user_ref' => '42'])
            ->assertStatus(201)
            ->json('data.session_id');
    }

    private function send(string $session, string $text, array $extra = [])
    {
        return $this->withToken('server-token')->postJson('/api/v1/juntra/server/chat/send', [
            'user_ref' => '42', 'session_id' => $session, 'text' => $text,
        ] + $extra);
    }

    public function test_a_usage_question_is_answered_from_the_site_help_block_without_an_offer(): void
    {
        $session = $this->start();
        $this->aiReply = "ทำแบบนี้นะคะลูก\n1. กดเมนู \"วอลเลต\"\n2. กด \"เติมเงิน\" [[OFFER:money]]";

        $res = $this->send($session, 'ชำระตรงไหนคะ', ['site_help' => self::HELP])->assertOk();

        // ถามวิธีใช้เว็บ = ได้คำตอบ ไม่ใช่การ์ดเสนอขาย (ป้ายที่ AI เผลอใส่ถูกถอดทิ้ง)
        $res->assertJsonPath('data.kind', 'reply')->assertJsonPath('data.offer_topic', null);
        $this->assertStringNotContainsString('[[', $res->json('data.reply'));

        $call = end($this->aiCalls);
        $this->assertStringContainsString('ตอบจาก "คู่มือใช้เว็บ"', $call['system']);
        $this->assertStringContainsString("====\n".self::HELP."\n====", $call['system']);
        $this->assertStringContainsString('ห้ามแต่งปุ่ม', $call['system']);
        // ไม่ชวนเปิดไพ่ในเทิร์นนี้ และไม่ดึงตัวอย่างจากแชทเพจ (ขั้นตอนบิล/สลิปของเพจขัดกับคู่มือเว็บ)
        $this->assertStringNotContainsString('[[OFFER:หมวด]]', $call['system']);
        $this->assertSame(0, $this->ragCalls);
        $this->assertLessThanOrEqual(0.4, $call['config']['temperature']);
        $this->assertGreaterThanOrEqual(450, $call['config']['max_tokens']);
    }

    public function test_a_normal_web_turn_gets_the_focus_rules_but_no_help_block(): void
    {
        $session = $this->start();

        $this->send($session, 'สวัสดีค่ะแม่หมอ')->assertOk();

        $system = end($this->aiCalls)['system'];
        $this->assertStringContainsString('หน้าที่ของห้องนี้บนเว็บ', $system);
        $this->assertStringContainsString('ห้ามเดาขั้นตอน ชื่อปุ่ม', $system);
        $this->assertStringNotContainsString('คู่มือใช้เว็บ" ระหว่างเส้น', $system);
        // เทิร์นปกติยังชวนเปิดไพ่ได้ และยังใช้ตัวอย่างคำตอบของแอดมินเหมือนเดิม
        $this->assertStringContainsString('[[OFFER:', $system);
        $this->assertSame(1, $this->ragCalls);
    }

    public function test_steps_that_mention_cards_coming_up_are_not_mistaken_for_a_fake_reading(): void
    {
        $session = $this->start();
        // ประโยคนี้เข้าด่าน "มโนว่าเปิดไพ่ให้แล้ว" ของแชทปกติ — แต่ในเทิร์นช่วยใช้เว็บคือขั้นตอนจริงของหน้าเว็บ
        $this->aiReply = 'ทำตามนี้นะคะลูก กดการ์ดแบบที่อยากดู แล้วจิ้มเลือกไพ่ทีละใบจนครบ พอครบแล้วเปิดไพ่ให้แล้วอัตโนมัติ '
            .'คำทำนายจะขึ้นมาบนจอเลยค่ะ ✨';

        $res = $this->send($session, 'เปิดไพ่ยังไงคะ', ['site_help' => self::HELP])->assertOk();

        $this->assertSame($this->aiReply, $res->json('data.reply'));
        $this->assertCount(1, $this->aiCalls, 'คำตอบช่วยใช้เว็บต้องไม่ถูกตีกลับไปลองคีย์อื่น');
    }

    public function test_an_oversized_help_block_is_rejected(): void
    {
        $session = $this->start();

        $this->send($session, 'เติมเงินยังไง', ['site_help' => str_repeat('ก', JuntraChatService::MAX_SITE_HELP_CHARS + 1)])
            ->assertStatus(422);
    }

    public function test_the_token_lane_prompt_has_neither_the_focus_rules_nor_a_help_block(): void
    {
        $chat = app(JuntraChatService::class);
        $session = $chat->start('7')['session_id'];

        $chat->send('7', $session, 'สวัสดีค่ะ');

        $system = end($this->aiCalls)['system'];
        $this->assertStringNotContainsString('หน้าที่ของห้องนี้บนเว็บ', $system);
        $this->assertStringNotContainsString('คู่มือใช้เว็บ', $system);
    }
}
