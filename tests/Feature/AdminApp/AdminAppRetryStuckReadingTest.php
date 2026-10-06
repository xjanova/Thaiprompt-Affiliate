<?php

namespace Tests\Feature\AdminApp;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\AdminApp\StuckReadingRetrier;
use App\Services\FortuneChannelManager;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\TestCase;

/**
 * 🛟 (v3) POST /api/admin/fortune/readings/{id}/retry + บิลค้างเกิน 24 ชม. (escalated_24h)
 *
 * ล็อกไว้:
 *   - ด่าน: จ่ายแล้ว · ไม่ใช่จันทรา · ไม่อยู่ในการเทคโอเวอร์ · งาน AI ไม่ได้วิ่งอยู่ · ค้างจริง/ถูกส่งต่อ · กดซ้ำใน 2 นาที = 429
 *   - เซิร์ฟเวอร์เลือกวิธี: ไม่มีวันเกิด → recover_pay_first · สร้างแล้วยังไม่ส่ง → resend · อื่น ๆ → regenerate (สั่งงานหลังตอบ)
 *     · Celtic → celtic_recover (เส้นบิลเดี่ยวของ Emergency Recovery)
 *   - บิลที่ fortune:expire-stuck-paid ปักธงเกิน 24 ชม. โผล่ใน active-readings + ops/summary ด้วย stuck_reason escalated_24h
 */
#[Group('admin-app')]
class AdminAppRetryStuckReadingTest extends TestCase
{
    use BuildsAdminAppWorld;
    use RefreshDatabase;

    /** @var array<int, Closure> งานที่ถูกเลื่อนไปหลังส่งคำตอบ (แทน register_shutdown_function) */
    private array $deferred = [];

    /** @var array<int, array<string, mixed>> ข้อความที่ "ส่ง" ผ่าน channel manager */
    private array $sent = [];

    private bool $sendResult = true;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();

        $test = $this;
        $cm = Mockery::mock(FortuneChannelManager::class);
        $cm->shouldReceive('sendResponse')->andReturnUsing(function ($platform, $userId, $response) use ($test) {
            $test->sent[] = ['platform' => $platform, 'user_id' => $userId, 'response' => $response];

            return $test->sendResult;
        });
        $cm->shouldReceive('getPlatform')->andReturn(null);

        $this->app->instance(StuckReadingRetrier::class, new class(fn (Closure $job) => $test->deferred[] = $job, $cm) extends StuckReadingRetrier
        {
            public function __construct(?Closure $defer, private FortuneChannelManager $cm)
            {
                parent::__construct($defer);
            }

            protected function channelManager(?FortuneTellingSetting $settings = null): FortuneChannelManager
            {
                return $this->cm;
            }
        });
    }

    protected function tearDown(): void
    {
        $this->tearDownAdminAppWorld();

        parent::tearDown();
    }

    public function test_guards_refuse_with_thai_messages(): void
    {
        $this->actAs($this->makeAdmin());
        $stale = ['is_paid' => true, 'amount_paid' => 39, 'paid_at' => now()->subMinutes(20),
            'conversation_status' => FortuneReading::STATUS_PAID, 'updated_at' => now()->subMinutes(6), 'birth_date' => '1995-03-15'];

        $unpaid = $this->makeReading(['conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT]);
        $this->retry($unpaid)->assertStatus(422)->assertJsonPath('error_code', 'NOT_PAID');

        $juntra = $this->makeReading(['reading_type' => FortuneReading::READING_TYPE_JUNTRA] + $stale);
        $this->retry($juntra)->assertStatus(422)->assertJsonPath('error_code', 'JUNTRA_BILL');

        $takenOver = $this->makeReading($stale + ['admin_takeover_until' => now()->addMinutes(20),
            'admin_takeover_reason' => FortuneReading::TAKEOVER_REASON_MANUAL, 'facebook_user_id' => '61550000006001']);
        $this->retry($takenOver)->assertStatus(409)
            ->assertJsonPath('error_code', 'ADMIN_TAKEOVER_ACTIVE')
            ->assertJsonPath('message', 'แอดมินคุมห้องนี้อยู่ — คืนให้บอทก่อนสั่งทำนายซ้ำ')
            ->assertJsonPath('success', false);

        $inFlight = $this->makeReading($stale + ['facebook_user_id' => '61550000006002']);
        Cache::put('fortune:deep_gen:'.$inFlight->id, 1, 300);
        $this->retry($inFlight)->assertStatus(409)->assertJsonPath('error_code', 'GENERATION_IN_FLIGHT');

        $fresh = $this->makeReading(array_merge($stale, ['updated_at' => now()->subSeconds(20), 'facebook_user_id' => '61550000006003']));
        $this->retry($fresh)->assertStatus(409)->assertJsonPath('error_code', 'NOT_STUCK');

        $done = $this->makeReading(array_merge($stale, ['conversation_status' => FortuneReading::STATUS_COMPLETED,
            'deep_response' => 'คำทำนาย', 'facebook_user_id' => '61550000006004']));
        $this->retry($done)->assertStatus(409)->assertJsonPath('error_code', 'NOT_STUCK');

        $this->assertSame([], $this->deferred, 'ไม่มีงานไหนถูกสั่ง');
        $this->assertSame([], $this->sent);
    }

    public function test_regenerate_resets_counters_dispatches_after_response_and_double_tap_does_nothing(): void
    {
        $admin = $this->actAs($this->makeAdmin());
        $reading = $this->makeReading([
            'is_paid' => true, 'amount_paid' => 39, 'paid_at' => now()->subMinutes(30), 'birth_date' => '1995-03-15',
            'conversation_status' => FortuneReading::STATUS_COMPLETED, 'updated_at' => now()->subMinutes(20),
            'ai_response' => 'ข้อความ error เก่า',
        ], ['auto_retry_count' => 5, 'failure_notified' => true, 'reading_sent_directly' => true, 'pay_first_mode' => true]);

        $res = $this->retry($reading)->assertOk();
        $res->assertJsonPath('success', true)
            ->assertJsonPath('action', 'regenerate')
            ->assertJsonPath('data.action', 'regenerate')
            ->assertJsonPath('data.stuck_reason', 'deep_job_failed');
        $this->assertNotSame('', (string) $res->json('message'));

        $fresh = $reading->fresh();
        $this->assertSame(FortuneReading::STATUS_PAID, $fresh->conversation_status);
        $this->assertNull($fresh->ai_response);
        $this->assertSame(0, $fresh->getConversationState('auto_retry_count'));
        $this->assertFalse($fresh->getConversationState('failure_notified'));
        $this->assertFalse($fresh->getConversationState('reading_sent_directly'));
        $this->assertTrue($fresh->getConversationState('pay_first_mode'), 'ธงอื่นคงไว้');
        $this->assertSame($admin->id, $fresh->getConversationState('admin_retry_by'));
        $this->assertCount(1, $this->deferred, 'สั่งงาน AI หลังส่งคำตอบ (ไม่ใช่กลางคำขอ)');

        // กดซ้ำทันที — บิลเพิ่งกลับเป็น paid (ยังไม่ถึงเกณฑ์ค้าง) → ไม่สั่งซ้ำ
        $this->retry($reading)->assertStatus(409)->assertJsonPath('error_code', 'NOT_STUCK');
        $this->assertCount(1, $this->deferred);

        // อีกเครื่องกดพร้อมกัน (ได้ล็อกไปก่อน) → 429 ไม่สั่งอะไร
        $this->travel(3)->minutes(); // ค้างรอบใหม่ (paid ไม่ขยับ > 2 นาที)
        Cache::put(StuckReadingRetrier::LOCK_PREFIX.$reading->id, 999, 120);
        $this->retry($reading)->assertStatus(429)->assertJsonPath('error_code', 'RETRY_COOLDOWN');
        $this->assertCount(1, $this->deferred);

        // ล็อกหมด + ยังค้าง → สั่งได้อีก
        Cache::forget(StuckReadingRetrier::LOCK_PREFIX.$reading->id);
        $this->retry($reading)->assertOk()->assertJsonPath('action', 'regenerate')->assertJsonPath('data.stuck_reason', 'ai_generating_timeout');
        $this->assertCount(2, $this->deferred);
    }

    public function test_double_tap_on_an_escalated_bill_runs_once(): void
    {
        $this->actAs($this->makeAdmin());
        $reading = $this->makeReading([
            'is_paid' => true, 'amount_paid' => 39, 'paid_at' => now()->subHours(30), 'birth_date' => '1995-03-15',
            'conversation_status' => FortuneReading::STATUS_PAID, 'updated_at' => now()->subHours(26),
        ], ['admin_review_needed' => true, 'admin_review_alerted' => true]);

        $this->retry($reading)->assertOk()->assertJsonPath('action', 'regenerate')->assertJsonPath('data.stuck_reason', 'escalated_24h');
        // ยังค้าง (ธงเกิน 24 ชม. + ยังไม่มีคำทำนาย) แต่เพิ่งกด → 429
        $this->retry($reading)->assertStatus(429)
            ->assertJsonPath('error_code', 'RETRY_COOLDOWN')
            ->assertJsonPath('message', 'เพิ่งสั่งทำนายซ้ำบิลนี้ไปแล้ว — รอ 2 นาทีก่อนสั่งอีกครั้ง');
        $this->assertCount(1, $this->deferred);
    }

    public function test_resend_when_generated_but_not_delivered(): void
    {
        $this->actAs($this->makeAdmin());
        $reading = $this->makeReading([
            'is_paid' => true, 'amount_paid' => 39, 'paid_at' => now()->subMinutes(30), 'birth_date' => '1995-03-15',
            'conversation_status' => FortuneReading::STATUS_PAID, 'updated_at' => now()->subMinutes(10),
            'deep_response' => 'คำทำนายเต็ม',
        ], ['reading_sent_directly' => false]);

        $this->retry($reading)->assertOk()
            ->assertJsonPath('action', 'resend')
            ->assertJsonPath('data.delivered', true);

        $this->assertCount(1, $this->sent);
        $this->assertSame('คำทำนายเต็ม', $this->sent[0]['response']['message']);
        $this->assertSame('facebook', $this->sent[0]['platform']);
        $fresh = $reading->fresh();
        $this->assertSame(FortuneReading::STATUS_COMPLETED, $fresh->conversation_status);
        $this->assertTrue($fresh->getConversationState('reading_sent_directly'), 'ตั้งธงส่งแล้ว — check-pending ไม่ส่งซ้ำ');
        $this->assertSame([], $this->deferred);

        // ส่งถึงแล้ว → ไม่ส่งซ้ำอีก
        Cache::flush();
        $again = $this->makeReading([
            'facebook_user_id' => '61550000007001', 'platform_user_id' => '61550000007001',
            'is_paid' => true, 'amount_paid' => 39, 'paid_at' => now()->subMinutes(30), 'birth_date' => '1995-03-15',
            'conversation_status' => FortuneReading::STATUS_PAID, 'updated_at' => now()->subMinutes(10),
            'deep_response' => 'คำทำนายเต็ม',
        ], ['reading_sent_directly' => true]);
        $this->retry($again)->assertStatus(409)->assertJsonPath('error_code', 'ALREADY_DELIVERED');
    }

    public function test_failed_resend_reports_502_and_releases_the_delivery_lock(): void
    {
        $this->actAs($this->makeAdmin());
        $this->sendResult = false;
        $reading = $this->makeReading([
            'is_paid' => true, 'amount_paid' => 39, 'paid_at' => now()->subMinutes(30), 'birth_date' => '1995-03-15',
            'conversation_status' => FortuneReading::STATUS_PAID, 'updated_at' => now()->subMinutes(10),
            'deep_response' => 'คำทำนายเต็ม',
        ]);

        $this->retry($reading)->assertStatus(502)->assertJsonPath('success', false)->assertJsonPath('data.delivered', false);
        $this->assertFalse(Cache::has('fortune:deep_deliver:'.$reading->id), 'ปล่อยล็อกส่งให้ check-pending/ลูกค้าทักกลับ');
        $this->assertNotTrue($reading->fresh()->getConversationState('reading_sent_directly'));
    }

    public function test_no_birthdate_goes_through_the_pay_first_recovery(): void
    {
        $this->actAs($this->makeAdmin());
        $reading = $this->makeReading([
            'is_paid' => true, 'amount_paid' => 39, 'paid_at' => now()->subMinutes(30),
            'conversation_status' => FortuneReading::STATUS_COMPLETED, 'updated_at' => now()->subMinutes(20),
        ]);

        $this->retry($reading)->assertOk()->assertJsonPath('action', 'recover_pay_first');

        $fresh = $reading->fresh();
        $this->assertSame(FortuneReading::STATUS_COLLECTING_BIRTHDATE, $fresh->conversation_status);
        $this->assertTrue($fresh->getConversationState('pay_first_mode'));
        $this->assertSame([], $this->deferred, 'ไม่สั่ง AI ทั้งที่ไม่มีวันเกิด');
    }

    public function test_celtic_uses_the_single_bill_emergency_path(): void
    {
        $this->actAs($this->makeAdmin());
        $reading = $this->makeReading([
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS, 'platform' => 'line',
            'platform_user_id' => 'U1234567890abcdef1234567890abcdef', 'facebook_user_id' => 'U1234567890abcdef1234567890abcdef',
            'is_paid' => true, 'amount_paid' => 99, 'paid_at' => now()->subMinutes(40),
            'conversation_status' => FortuneReading::STATUS_CELTIC_GENERATING, 'updated_at' => now()->subMinutes(5),
        ]);

        $this->retry($reading)->assertOk()
            ->assertJsonPath('action', 'celtic_recover')
            ->assertJsonPath('data.delivered', true);

        $this->assertCount(1, $this->sent);
        $this->assertSame('line', $this->sent[0]['platform']);
        $this->assertStringContainsString('ขออภัยที่ทำให้รอ', $this->sent[0]['response']['message']);

        $done = $this->makeReading([
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS, 'facebook_user_id' => '61550000008001',
            'platform_user_id' => '61550000008001', 'is_paid' => true, 'amount_paid' => 99, 'paid_at' => now()->subDays(2),
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
        ], ['admin_review_needed' => true]);
        $this->retry($done)->assertStatus(409)->assertJsonPath('error_code', 'ALREADY_COMPLETED');
    }

    public function test_escalated_over_24h_shows_up_and_retry_puts_it_back_in_scope(): void
    {
        $this->actAs($this->makeAdmin());

        // Deep จ่ายมา 30 ชม. ยังไม่ให้วันเกิด — fortune:expire-stuck-paid ปักธงแล้ว
        $escalated = $this->makeReading([
            'is_paid' => true, 'amount_paid' => 39, 'paid_at' => now()->subHours(30),
            'conversation_status' => FortuneReading::STATUS_COLLECTING_BIRTHDATE, 'updated_at' => now()->subHours(29),
        ], ['admin_review_needed' => true, 'admin_review_alerted' => true, 'admin_review_reason' => 'stuck_paid_over_24hr']);
        // Celtic ปักธงแต่จบแล้ว (กู้สำเร็จ) — ไม่ใช่ค้าง
        $recovered = $this->makeReading([
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS, 'facebook_user_id' => '61550000009001',
            'platform_user_id' => '61550000009001', 'is_paid' => true, 'amount_paid' => 99, 'paid_at' => now()->subHours(40),
            'conversation_status' => FortuneReading::STATUS_COMPLETED, 'updated_at' => now()->subHours(20),
        ], ['admin_review_needed' => true]);
        // ปักธงเก่ากว่า 30 วัน — ไม่ดึงมา
        $ancient = $this->makeReading([
            'facebook_user_id' => '61550000009002', 'platform_user_id' => '61550000009002',
            'is_paid' => true, 'amount_paid' => 39, 'paid_at' => now()->subDays(40),
            'conversation_status' => FortuneReading::STATUS_PAID, 'updated_at' => now()->subDays(39),
        ], ['admin_review_needed' => true]);

        $list = $this->getJson('/api/admin/fortune/active-readings?per_page=100')->assertOk();
        $rows = collect($list->json('data.data'))->keyBy('reading_id');
        $this->assertSame('escalated_24h', $rows[$escalated->id]['stuck_reason']);
        $this->assertTrue($rows[$escalated->id]['stuck']);
        $this->assertGreaterThanOrEqual(30 * 60, $rows[$escalated->id]['minutes_since_activity'], 'นับจากเวลาจ่าย');
        $this->assertFalse($rows->has($recovered->id));
        $this->assertFalse($rows->has($ancient->id));

        $summary = $this->getJson('/api/admin/ops/summary')->assertOk();
        $this->assertSame(1, $summary->json('data.queue.stuck_readings.count'));
        $this->assertSame('escalated_24h', $summary->json('data.queue.stuck_readings.preview.0.stuck_reason'));

        // กดทำนายซ้ำ → ขอวันเกิดใหม่ + ล้าง admin_review_alerted (ให้คำตอบลูกค้าไหลเข้าบิลที่จ่ายแล้ว)
        $this->retry($escalated)->assertOk()->assertJsonPath('action', 'recover_pay_first');
        $fresh = $escalated->fresh();
        $this->assertFalse($fresh->getConversationState('admin_review_alerted'));
        $this->assertSame(
            $escalated->id,
            FortuneReading::query()->activeConversation((string) $escalated->facebook_user_id)->value('id'),
            'บอทเห็นบิลที่จ่ายแล้วเป็นบทสนทนาที่ยังเปิดอีกครั้ง'
        );
    }

    private function retry(FortuneReading $reading)
    {
        return $this->postJson("/api/admin/fortune/readings/{$reading->id}/retry");
    }
}
