<?php

namespace Tests\Feature\AdminApp;

use App\Models\FortuneReading;
use App\Models\FortuneTakeoverLog;
use App\Services\FacebookWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\TestCase;

/**
 * 💬 กล่องแชท/เทคโอเวอร์ของแอปแอดมิน + chat/extend + กันข้อความ error พา token หลุดออก JSON
 */
#[Group('admin-app')]
class AdminAppTakeoverAndChatTest extends TestCase
{
    use BuildsAdminAppWorld;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $this->tearDownAdminAppWorld();

        parent::tearDown();
    }

    public function test_conversations_stats_and_messages(): void
    {
        $admin = $this->actAs($this->makeAdmin());

        $asked = $this->makeReading([
            'platform' => 'line',
            'platform_user_id' => 'U1234567890abcdef1234567890abcdef',
            'facebook_user_id' => 'U1234567890abcdef1234567890abcdef',
            'facebook_user_name' => 'สมหญิง',
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'conversation_status' => FortuneReading::STATUS_CELTIC_QA_PROMPT,
            'is_paid' => true, 'amount_paid' => 99, 'paid_at' => now()->subMinutes(30),
            'questions' => ['ความรักปีนี้'],
            'admin_takeover_reason' => FortuneReading::TAKEOVER_REASON_CUSTOMER_REQUEST,
            'admin_takeover_started_at' => now()->subMinutes(5),
            'admin_takeover_until' => now()->addMinutes(25),
        ]);
        FortuneTakeoverLog::create([
            'fortune_reading_id' => $asked->id, 'action' => FortuneTakeoverLog::ACTION_TAKEOVER,
            'reason' => FortuneReading::TAKEOVER_REASON_CUSTOMER_REQUEST, 'message' => 'ขอคุยกับแอดมิน', 'platform' => 'line',
        ]);
        FortuneTakeoverLog::create([
            'fortune_reading_id' => $asked->id, 'user_id' => $admin->id, 'action' => FortuneTakeoverLog::ACTION_MESSAGE,
            'message' => 'สักครู่นะคะ', 'platform' => 'line',
        ]);
        $manual = $this->makeReading([
            'admin_takeover_reason' => FortuneReading::TAKEOVER_REASON_MANUAL,
            'admin_takeover_by' => $admin->id,
            'admin_takeover_started_at' => now()->subMinutes(1),
            'admin_takeover_until' => now()->addMinutes(29),
        ]);
        $this->makeReading(['conversation_status' => FortuneReading::STATUS_TIER_CHOICE]); // คุยอยู่ ไม่ได้เทคโอเวอร์

        $list = $this->getJson('/api/admin/takeover/conversations')->assertOk();
        $this->assertSame(2, $list->json('data.total'));

        $row = collect($list->json('data.data'))->firstWhere('reading_id', $asked->id);
        $this->assertSame('line', $row['platform']);
        $this->assertSame('สมหญิง', $row['customer_name']);
        $this->assertTrue($row['is_taken_over']);
        $this->assertTrue($row['requested_by_customer']);
        $this->assertSame('ขอคุยกับแอดมิน', $row['request_keyword']);
        $this->assertSame('ลูกค้าขอคุยกับคน', $row['takeover_reason_label']);
        $this->assertGreaterThan(0, $row['remaining_minutes']);
        // ไม่มีแชทสดใน Redis → ข้อความล่าสุดที่แอดมินส่งผ่านแผง
        $this->assertSame('admin', $row['last_message']['sender']);
        $this->assertFalse($row['unread']);

        $manualRow = collect($list->json('data.data'))->firstWhere('reading_id', $manual->id);
        $this->assertSame($admin->name, $manualRow['takeover_admin']['name']);
        $this->assertFalse($manualRow['requested_by_customer']);

        $this->getJson('/api/admin/takeover/conversations?status=requested')->assertOk()->assertJsonPath('data.total', 1);
        $this->getJson('/api/admin/takeover/conversations?status=active')->assertOk()->assertJsonPath('data.total', 2);
        $this->getJson('/api/admin/takeover/conversations?status=nope')->assertStatus(422);

        $this->getJson('/api/admin/takeover/stats')->assertOk()
            ->assertJsonPath('data.taken_over', 2)
            ->assertJsonPath('data.requested', 1);

        $messages = $this->getJson("/api/admin/takeover/{$asked->id}/messages")->assertOk();
        $this->assertSame('structured', $messages->json('data.source'));
        $senders = collect($messages->json('data.messages'))->pluck('sender')->unique()->values()->all();
        $this->assertContains('customer', $senders);
        $this->assertContains('system', $senders);
        foreach ($messages->json('data.messages') as $m) {
            $this->assertContains($m['sender'], ['customer', 'bot', 'admin', 'system']);
            $this->assertArrayHasKey('text', $m);
            $this->assertArrayHasKey('at', $m);
        }
    }

    public function test_extend_adds_minutes_to_the_current_takeover(): void
    {
        $admin = $this->actAs($this->makeAdmin());
        $until = now()->addMinutes(10)->startOfSecond();
        $reading = $this->makeReading([
            'admin_takeover_reason' => FortuneReading::TAKEOVER_REASON_MANUAL,
            'admin_takeover_started_at' => now()->subMinutes(20),
            'admin_takeover_until' => $until,
        ]);

        $res = $this->postJson('/api/admin/chat/extend', ['reading_id' => $reading->id, 'minutes' => 15])->assertOk();
        $res->assertJsonPath('data.minutes_added', 15)->assertJsonPath('data.is_takeover', true);

        $this->assertSame($until->copy()->addMinutes(15)->getTimestamp(), $reading->fresh()->admin_takeover_until->getTimestamp());
        $this->assertDatabaseHas('fortune_takeover_logs', [
            'fortune_reading_id' => $reading->id, 'action' => FortuneTakeoverLog::ACTION_EXTEND, 'user_id' => $admin->id,
        ]);

        $this->postJson('/api/admin/chat/extend', ['reading_id' => $reading->id])->assertStatus(422);
    }

    public function test_send_failure_never_echoes_tokens_in_json(): void
    {
        $this->actAs($this->makeAdmin());
        $reading = $this->makeReading();

        $this->mock(FacebookWebhookService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')->andThrow(new \RuntimeException(
                'cURL error 56: Recv failure for https://graph.facebook.com/v19.0/me/messages?access_token=EAAGsecretPageToken1234567890'
            ));
        });

        $res = $this->postJson('/api/admin/chat/send', ['reading_id' => $reading->id, 'text' => 'สวัสดีค่ะ'])->assertStatus(500);

        $this->assertStringStartsWith('send failed: ', (string) $res->json('message'));
        $this->assertStringNotContainsString('EAAGsecretPageToken1234567890', $res->getContent());
    }
}
