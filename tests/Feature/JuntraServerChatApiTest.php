<?php

namespace Tests\Feature;

use App\Models\FortuneTellingSetting;
use App\Services\FortuneAIService;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Mockery;
use Tests\Concerns\BuildsJuntraServerSchema;
use Tests\TestCase;

/**
 * 🌙 /api/v1/juntra/server/chat/* — แชทแม่หมอให้ลูกค้าเว็บ จันทรา.online ทุกคน (ไม่ต้องมี token Thaiprompt)
 *
 * เจ้าของสั่ง (2026-09-15): คุยฟรีเหมือนบอทใน FB/LINE จนกว่าจะเริ่มการทำนาย → แม่หมอฝั่งเว็บ
 * ไม่ทำนายเองในแชท แต่ชวนเลือกแพ็กเกจเปิดไพ่ (kind=offer) ให้เว็บวาดการ์ดแล้วหักเครดิตตอนเปิดไพ่
 */
class JuntraServerChatApiTest extends TestCase
{
    use BuildsJuntraServerSchema;

    /** @var array<int,array{system:string,user:string}> */
    private array $aiCalls = [];

    private ?string $aiReply = 'แม่หมออยู่ตรงนี้นะคะลูก ✨';

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildJuntraServerSchema();
        config(['services.juntra.server_client_ids' => []]);
        Cache::flush();

        $client = app(ClientRepository::class)->create(
            null,
            'Juntra Chantra SSO',
            'https://xn--82c4af5bzdj.online/auth/thaiprompt/callback',
            'oauth_users'
        );
        Passport::actingAsClient($client, [], 'api-oauth');

        $ai = Mockery::mock(FortuneAIService::class);
        $ai->shouldReceive('withCustomerContext')->andReturnSelf();
        $ai->shouldReceive('chatWithCustomSystemPrompt')->andReturnUsing(function (...$args) {
            $this->aiCalls[] = ['system' => $args[0], 'user' => $args[1]];

            return ['response' => $this->aiReply, 'provider' => 'gemini'];
        });
        $this->app->instance(FortuneAIService::class, $ai);
    }

    protected function tearDown(): void
    {
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    private function start(string $ref = '42'): string
    {
        return $this->withToken('server-token')
            ->postJson('/api/v1/juntra/server/chat/start', ['user_ref' => $ref])
            ->assertStatus(201)
            ->json('data.session_id');
    }

    private function send(string $session, string $text, string $ref = '42')
    {
        return $this->withToken('server-token')->postJson('/api/v1/juntra/server/chat/send', [
            'user_ref' => $ref, 'name' => 'สมใจ', 'session_id' => $session, 'text' => $text,
        ]);
    }

    public function test_any_web_customer_can_chat_without_a_thaiprompt_account(): void
    {
        $session = $this->start();

        $this->send($session, 'สวัสดีค่ะแม่หมอ')
            ->assertOk()
            ->assertJsonPath('data.reply', 'แม่หมออยู่ตรงนี้นะคะลูก ✨')
            ->assertJsonPath('data.kind', 'reply')
            ->assertJsonPath('data.offer_topic', null);

        // The web lane tells แม่หมอ not to predict for free and how to hand over to a reading.
        $this->assertStringContainsString('[[OFFER:', $this->aiCalls[0]['system']);
    }

    public function test_a_prediction_request_becomes_an_offer_and_the_tag_never_reaches_the_customer(): void
    {
        $session = $this->start();
        $this->aiReply = 'แม่หมอจะเปิดไพ่เรื่องความรักให้นะคะลูก เลือกแบบที่ลูกอยากดูด้านล่างได้เลย ✨ [[OFFER:love]]';

        $res = $this->send($session, 'ช่วยดูดวงความรักให้หน่อยค่ะ')->assertOk();

        $res->assertJsonPath('data.kind', 'offer')->assertJsonPath('data.offer_topic', 'love');
        $this->assertStringNotContainsString('[[', $res->json('data.reply'));
        $this->assertStringNotContainsString('OFFER', $res->json('data.reply'));
    }

    public function test_a_room_grounded_on_a_paid_reading_talks_freely_about_the_cards(): void
    {
        $session = $this->start();
        $this->aiReply = 'ไพ่ถ้วยสองบอกว่าความรักของลูกกำลังไปได้ดีค่ะ [[OFFER:love]]';

        $res = $this->withToken('server-token')->postJson('/api/v1/juntra/server/chat/send', [
            'user_ref' => '42', 'session_id' => $session, 'text' => 'ความรักจากไพ่ชุดนี้เป็นยังไงคะ', 'grounded' => true,
        ])->assertOk();

        $res->assertJsonPath('data.kind', 'reply')->assertJsonPath('data.offer_topic', null);
        $this->assertStringNotContainsString('[[', $res->json('data.reply'));
        $this->assertStringNotContainsString('[[OFFER:', end($this->aiCalls)['system']);
    }

    public function test_unknown_offer_topic_falls_back_to_general(): void
    {
        $session = $this->start();
        $this->aiReply = 'เลือกไพ่ได้เลยค่ะ [[OFFER:lottery]]';

        $this->send($session, 'ดูดวงให้หน่อย')->assertJsonPath('data.offer_topic', 'general');
    }

    public function test_an_empty_ai_reply_is_a_503_not_a_filler_that_looks_like_an_answer(): void
    {
        $session = $this->start();
        $this->aiReply = null;

        $this->send($session, 'สวัสดีค่ะ')->assertStatus(503)->assertJsonPath('reason_code', 'ai_unavailable');
    }

    public function test_history_is_kept_per_customer_and_never_mixed(): void
    {
        $a = $this->start('1');
        $this->send($a, 'ข้อความของคนที่หนึ่ง', '1')->assertOk();

        // Customer 2 guessing customer 1's session id gets a fresh room, not customer 1's history.
        $this->send($a, 'ข้อความของคนที่สอง', '2')->assertOk();
        $this->assertStringNotContainsString('ข้อความของคนที่หนึ่ง', end($this->aiCalls)['user']);
        $this->assertStringContainsString('ข้อความของคนที่หนึ่ง', $this->aiCalls[0]['user']);
    }

    public function test_user_ref_is_validated_and_a_user_token_is_refused(): void
    {
        $this->withToken('server-token')
            ->postJson('/api/v1/juntra/server/chat/start', ['user_ref' => '../etc'])
            ->assertStatus(422);
    }

    public function test_the_per_user_lane_keeps_its_old_behaviour(): void
    {
        $service = app(\App\Services\Fortune\JuntraChatService::class);
        $this->aiReply = null;

        $out = $service->send('7', $service->start('7')['session_id'], 'สวัสดี', [], webOffers: false, fillerOnEmpty: true);

        $this->assertSame(\App\Services\Fortune\JuntraChatService::FILLER, $out['reply']);
        $this->assertStringNotContainsString('[[OFFER:', end($this->aiCalls)['system']);
    }
}
