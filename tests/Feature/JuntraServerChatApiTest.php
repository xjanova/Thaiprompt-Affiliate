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

    /** @var array<int,array{system:string,user:string,config:array}> */
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

    /* ───────────── 💬 (2026-09-21) ห้องที่คุยต่อจากคำพยากรณ์ที่จ่ายแล้ว ───────────── */

    /** บริบทแบบที่เว็บสร้าง (ReadingChatContext) — ยาวเกิน 1,000 ตัวแน่ ๆ เหมือนแพ็กเกจ 12 เดือนจริง */
    private function readingContext(): string
    {
        $months = ['ต.ค. 2569', 'พ.ย. 2569', 'ธ.ค. 2569', 'ม.ค. 2570', 'ก.พ. 2570', 'มี.ค. 2570',
            'เม.ย. 2570', 'พ.ค. 2570', 'มิ.ย. 2570', 'ก.ค. 2570', 'ส.ค. 2570', 'ก.ย. 2570'];
        $lines = ['แพ็กเกจ: พยากรณ์ 12 เดือน (ไพ่ 12 ใบ)', 'ไพ่ที่เปิดได้:'];
        foreach ($months as $i => $m) {
            $lines[] = ($i + 1).". เดือนที่ ".($i + 1)." · {$m} — ไพ่ใบที่ ".($i + 1).' · ตั้งตรง';
        }
        $lines[] = 'คำพยากรณ์ฉบับเต็ม:';
        foreach ($months as $m) {
            $lines[] = "## 📅 {$m} · ".($m === 'มี.ค. 2570' ? 'ระวัง' : 'ดี');
            $lines[] = str_repeat("ธีมของเดือน {$m} ", 12);
        }
        $lines[] = 'ท้ายคำพยากรณ์-END';

        return implode("\n", $lines);
    }

    private function startWith(?string $context, string $ref = '42')
    {
        return $this->withToken('server-token')
            ->postJson('/api/v1/juntra/server/chat/start', array_filter(['user_ref' => $ref, 'context' => $context]));
    }

    public function test_a_reading_context_given_at_start_stays_in_the_system_message_after_the_history_window(): void
    {
        $ctx = $this->readingContext();
        $this->assertGreaterThan(1000, mb_strlen($ctx), 'บริบทจริงยาวเกินเพดานข้อความแชทเดิม');

        $res = $this->startWith($ctx)->assertStatus(201);
        $session = $res->json('data.session_id');
        $res->assertJsonPath('data.greeting', \App\Services\Fortune\JuntraChatService::GREETING_READING);

        // เก็บคู่กับห้องครบทุกตัว — ไม่ตัดที่ 1,000 แบบข้อความแชท
        $this->assertSame($ctx, app(\App\Services\Fortune\JuntraChatService::class)->context('jw42', $session));

        // 14 คำถาม: ประวัติเห็นแค่ 12 รายการล่าสุด คำถามแรกเลื่อนหลุดไปแล้ว แต่คำพยากรณ์ยังอยู่ครบ
        for ($i = 1; $i <= 14; $i++) {
            $this->send($session, "คำถามที่ {$i} เรื่องไพ่ชุดนี้")->assertOk()->assertJsonPath('data.kind', 'reply');
        }

        $this->assertCount(14, $this->aiCalls);
        $last = end($this->aiCalls);
        $this->assertStringNotContainsString('คำถามที่ 1 เรื่อง', $last['user'], 'คำถามแรกเลื่อนหลุดหน้าต่างประวัติแล้ว');
        $this->assertStringContainsString('## 📅 มี.ค. 2570 · ระวัง', $last['system']);
        $this->assertStringContainsString('ท้ายคำพยากรณ์-END', $last['system']);
        $this->assertStringContainsString('ห้ามเปิดไพ่ใบใหม่', $last['system']);
        // คุยต่อจากคำพยากรณ์ที่จ่ายแล้ว = ไม่ชวนซื้อแพ็กเกจ แม้เว็บไม่ได้ส่ง grounded มา
        $this->assertStringNotContainsString('[[OFFER:', $last['system']);
        $this->assertSame(600, $last['config']['max_tokens']);
        $this->assertLessThan(0.85, $last['config']['temperature']);
    }

    public function test_a_room_without_context_behaves_exactly_as_before(): void
    {
        $session = $this->startWith(null)->assertStatus(201)
            ->assertJsonPath('data.greeting', \App\Services\Fortune\JuntraChatService::GREETING)
            ->json('data.session_id');

        $this->send($session, 'สวัสดีค่ะแม่หมอ')->assertOk();

        $call = end($this->aiCalls);
        $this->assertStringNotContainsString('ห้ามเปิดไพ่ใบใหม่', $call['system']);
        $this->assertStringNotContainsString('====', $call['system']);
        $this->assertStringContainsString('[[OFFER:', $call['system']);
        $this->assertSame(['temperature' => 0.85, 'max_tokens' => 350], $call['config']);
        $this->assertNull(app(\App\Services\Fortune\JuntraChatService::class)->context('jw42', $session));
    }

    public function test_send_can_hand_the_context_back_after_the_room_expired(): void
    {
        $session = $this->startWith($this->readingContext())->json('data.session_id');
        Cache::flush();   // ห้องหมดอายุ / cache ถูกล้าง

        $this->send($session, 'เดือนมีนาที่ไพ่บอกให้ระวัง หมายถึงอะไรคะ')->assertOk();
        $this->assertStringNotContainsString('📅 มี.ค. 2570', end($this->aiCalls)['system'], 'ไม่ส่งมาอีก = ไม่มีบริบทจริง ๆ');

        // เว็บเปิดห้องต่อพร้อมบริบทที่สร้างใหม่จากฐานข้อมูล
        $this->withToken('server-token')->postJson('/api/v1/juntra/server/chat/send', [
            'user_ref' => '42', 'session_id' => $session, 'text' => 'เดือนมีนาที่ไพ่บอกให้ระวัง หมายถึงอะไรคะ',
            'grounded' => true, 'context' => $this->readingContext(),
        ])->assertOk()->assertJsonPath('data.kind', 'reply');

        $this->assertStringContainsString('## 📅 มี.ค. 2570 · ระวัง', end($this->aiCalls)['system']);

        // และจำไว้ให้รอบต่อไปที่ไม่ได้ส่งมา
        $this->send($session, 'แล้วควรทำยังไงดีคะ')->assertOk();
        $this->assertStringContainsString('## 📅 มี.ค. 2570 · ระวัง', end($this->aiCalls)['system']);
    }

    public function test_a_reading_context_never_leaks_to_another_customer(): void
    {
        $session = $this->startWith($this->readingContext(), '1')->json('data.session_id');

        // ลูกค้าคนที่ 2 เดา session ของคนที่ 1 — ได้ห้องเปล่า ไม่เห็นคำพยากรณ์ของคนอื่น
        $this->send($session, 'สวัสดีค่ะ', '2')->assertOk();

        $this->assertStringNotContainsString('📅', end($this->aiCalls)['system']);
    }

    public function test_an_oversized_context_is_refused(): void
    {
        $this->startWith(str_repeat('ก', \App\Services\Fortune\JuntraChatService::MAX_CONTEXT_CHARS + 1))
            ->assertStatus(422)->assertJsonValidationErrors('context');
    }
}
