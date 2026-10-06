<?php

namespace Tests\Feature\Fortune;

use App\Http\Controllers\Admin\FortuneTakeoverController;
use App\Jobs\RetryPayFirstPushJob;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\TakeoverResumeService;
use App\Services\FortuneTakeoverService;
use App\Services\LineAlertService;
use App\Services\SmsPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\TestCase;

/**
 * 🤫 (2026-10-06) จ่ายเงินระหว่างเทคโอเวอร์ → บันทึกเงิน/สถานะเหมือนเดิม แต่ไม่ส่งหาลูกค้า + แจ้งแอดมิน + พักของ
 * จบเทคโอเวอร์ → ส่งของที่พักครั้งเดียว (คืนงานซ้ำไม่ส่งซ้ำ) · แอดมินเลือก "จัดการเองแล้ว" ได้
 * แอดมินส่งข้อความ → ถึงลูกค้าเสมอ + เริ่ม/ต่อเทคโอเวอร์
 */
#[Group('takeover-silence')]
class TakeoverPaymentResumeAndAdminSendTest extends TestCase
{
    use BuildsAdminAppWorld;
    use RefreshDatabase;

    private const FB_UID = '61550000000077';

    /** @var array<int, array{0:string,1:array}> */
    private array $adminAlerts = [];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        FortuneTellingSetting::clearSettingsCache();

        $settings = FortuneTellingSetting::getSettings();
        $settings->is_enabled = true;
        $settings->facebook_page_token = 'EAAtestPageToken';
        $settings->admin_handover_enabled = true;
        $settings->admin_handover_timeout = 1; // ค่าที่ migration 2026_04_27_130000 ตั้งไว้
        $settings->save();
        FortuneTellingSetting::clearSettingsCache();

        Http::fake(['*' => Http::response(['message_id' => 'm_1', 'recipient_id' => self::FB_UID], 200)]);

        $this->mock(LineAlertService::class, function ($mock) {
            $mock->shouldReceive('alertUnusualActivity')->andReturnUsing(function ($pattern, $details) {
                $this->adminAlerts[] = [$pattern, $details];
            });
            $mock->shouldIgnoreMissing();
        });
    }

    protected function tearDown(): void
    {
        $this->tearDownAdminAppWorld();

        parent::tearDown();
    }

    private function sentTexts(): string
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), '/me/messages'))
            ->map(fn ($pair) => json_encode($pair[0]->data(), JSON_UNESCAPED_UNICODE))
            ->implode("\n");
    }

    private function sentCount(): int
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), '/me/messages'))->count();
    }

    private function unpaidCelticUnderTakeover(): FortuneReading
    {
        return $this->makeReading([
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'conversation_status' => FortuneReading::STATUS_CELTIC_PENDING_PAYMENT,
            'bill_reference' => FortuneReading::generateBillReference(),
            'amount_paid' => 99,
            'admin_takeover_reason' => FortuneReading::TAKEOVER_REASON_MANUAL,
            'admin_takeover_started_at' => now()->subMinute(),
            'admin_takeover_until' => now()->addMinutes(20),
        ]);
    }

    // ============================================================
    // จ่ายเงินระหว่างเทคโอเวอร์
    // ============================================================

    public function test_admin_mark_paid_celtic_records_payment_but_defers_the_start_box_and_alerts_admin(): void
    {
        $this->actAs($this->makeAdmin());
        $reading = $this->unpaidCelticUnderTakeover();

        $this->postJson("/api/admin/fortune/readings/{$reading->id}/mark-paid", ['amount' => 99])->assertOk();

        $fresh = $reading->fresh();
        $this->assertTrue((bool) $fresh->is_paid, 'เงินต้องถูกบันทึกเหมือนเดิม');
        $this->assertSame(FortuneReading::STATUS_CELTIC_PICKING, $fresh->conversation_status, 'สถานะเดินต่อเหมือนเดิม');
        $this->assertSame(0, $this->sentCount(), 'ห้ามส่งอะไรหาลูกค้าระหว่างเทคโอเวอร์');

        $deferred = (array) $fresh->getConversationState('takeover_deferred');
        $this->assertArrayHasKey(TakeoverResumeService::ITEM_CELTIC_START, $deferred);
        $this->assertSame(FortuneReading::STATUS_CELTIC_PICKING, $deferred[TakeoverResumeService::ITEM_CELTIC_START]['status']);
        $this->assertStringContainsString('ตัดบิลเรียบร้อย', $deferred[TakeoverResumeService::ITEM_CELTIC_START]['payload']['message']);

        $this->assertCount(1, $this->adminAlerts);
        $this->assertStringContainsString('จ่ายแล้วระหว่างเทคโอเวอร์', $this->adminAlerts[0][0]);

        // แอปแอดมินเห็นของที่พักไว้
        $this->getJson('/api/admin/chat/takeover-status?reading_id='.$reading->id)->assertOk()
            ->assertJsonPath('data.is_takeover', true)
            ->assertJsonPath('data.deferred.0.item', TakeoverResumeService::ITEM_CELTIC_START);
        $row = collect($this->getJson('/api/admin/takeover/conversations')->assertOk()->json('data.data'))->firstWhere('reading_id', $reading->id);
        $this->assertSame(TakeoverResumeService::ITEM_CELTIC_START, $row['deferred'][0]['item']);
    }

    public function test_pay_first_deep_payment_defers_birthdate_box_without_retry_job(): void
    {
        Queue::fake();
        $reading = $this->makeReading([
            'conversation_status' => FortuneReading::STATUS_PAID,
            'is_paid' => true, 'amount_paid' => 39, 'paid_at' => now(),
            'bill_reference' => FortuneReading::generateBillReference(),
            'admin_takeover_reason' => FortuneReading::TAKEOVER_REASON_MANUAL,
            'admin_takeover_started_at' => now()->subMinute(),
            'admin_takeover_until' => now()->addMinutes(20),
        ]);

        app(SmsPaymentService::class)->handleDeepPayFirstPaymentMatched($reading, null, 'facebook', self::FB_UID, 39.0);

        $fresh = $reading->fresh();
        $this->assertSame(FortuneReading::STATUS_COLLECTING_BIRTHDATE, $fresh->conversation_status);
        $this->assertSame(0, $this->sentCount());
        $this->assertArrayHasKey(TakeoverResumeService::ITEM_PAYFIRST_BIRTHDATE, (array) $fresh->getConversationState('takeover_deferred'));
        Queue::assertNotPushed(RetryPayFirstPushJob::class);
        $this->assertCount(1, $this->adminAlerts);
    }

    // ============================================================
    // จบเทคโอเวอร์
    // ============================================================

    public function test_resume_delivers_deferred_paid_items_exactly_once(): void
    {
        $admin = $this->actAs($this->makeAdmin());
        $reading = $this->unpaidCelticUnderTakeover();
        $this->postJson("/api/admin/fortune/readings/{$reading->id}/mark-paid", ['amount' => 99])->assertOk();
        $this->assertSame(0, $this->sentCount());

        $res = $this->postJson('/api/admin/chat/resume', ['reading_id' => $reading->id])->assertOk();
        $res->assertJsonPath('data.deliver_deferred', true)
            ->assertJsonPath('data.deferred.0.item', TakeoverResumeService::ITEM_CELTIC_START);

        $this->assertStringContainsString('ตัดบิลเรียบร้อย', $this->sentTexts());
        $afterFirst = $this->sentCount();
        $this->assertGreaterThan(0, $afterFirst);
        $fresh = $reading->fresh();
        $this->assertNull($fresh->admin_takeover_until);
        $this->assertNull($fresh->getConversationState('takeover_deferred'));

        // คืนงานซ้ำ / cron กวาดซ้ำ → ไม่ส่งซ้ำ
        $this->postJson('/api/admin/chat/resume', ['reading_id' => $reading->id])->assertOk();
        app(FortuneTakeoverService::class)->cleanupExpired();
        TakeoverResumeService::deliverDeferred('facebook', self::FB_UID);
        $this->assertSame($afterFirst, $this->sentCount());
    }

    public function test_resume_already_handled_sends_nothing_and_marks_deep_reading_delivered(): void
    {
        $this->actAs($this->makeAdmin());
        $reading = $this->makeReading([
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'is_paid' => true, 'amount_paid' => 39, 'paid_at' => now()->subMinutes(5),
            'bill_reference' => FortuneReading::generateBillReference(),
            'deep_response' => 'คำทำนายเชิงลึก',
            'admin_takeover_reason' => FortuneReading::TAKEOVER_REASON_MANUAL,
            'admin_takeover_started_at' => now()->subMinute(),
            'admin_takeover_until' => now()->addMinutes(20),
        ]);
        TakeoverResumeService::defer($reading, TakeoverResumeService::ITEM_DEEP_READING);

        $this->postJson('/api/admin/chat/resume', ['reading_id' => $reading->id, 'deliver_deferred' => false])
            ->assertOk()->assertJsonPath('data.deliver_deferred', false);

        $fresh = $reading->fresh();
        $this->assertSame(0, $this->sentCount());
        $this->assertNull($fresh->getConversationState('takeover_deferred'));
        $this->assertTrue((bool) $fresh->getConversationState('reading_sent_directly'));
        $this->assertDatabaseHas('fortune_takeover_logs', ['fortune_reading_id' => $reading->id, 'reason' => 'deferred_discarded']);

        // cron ส่งคำทำนายค้างต้องไม่หยิบไปส่งเอง
        $this->artisan('fortune:check-pending')->assertExitCode(0);
        $this->assertSame(0, $this->sentCount());
    }

    public function test_resume_delivers_a_deferred_deep_reading_once(): void
    {
        $reading = $this->makeReading([
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'is_paid' => true, 'amount_paid' => 39, 'paid_at' => now()->subMinutes(5),
            'bill_reference' => FortuneReading::generateBillReference(),
            'deep_response' => 'คำทำนายเชิงลึกฉบับเต็มของคุณ',
            'admin_takeover_reason' => FortuneReading::TAKEOVER_REASON_MANUAL,
            'admin_takeover_started_at' => now()->subMinute(),
            'admin_takeover_until' => now()->addMinutes(20),
        ]);
        $this->artisan('fortune:process-deep', ['readingId' => $reading->id, 'platform' => 'facebook', 'userId' => self::FB_UID]);
        $this->assertSame(0, $this->sentCount());

        app(FortuneTakeoverService::class)->resume($reading->fresh(), null, true, true);

        $this->assertStringContainsString('คำทำนายเชิงลึกฉบับเต็มของคุณ', $this->sentTexts());
        $this->assertTrue((bool) $reading->fresh()->getConversationState('reading_sent_directly'));
        $after = $this->sentCount();

        app(FortuneTakeoverService::class)->resume($reading->fresh(), null, true, true);
        $this->artisan('fortune:check-pending')->assertExitCode(0);
        $this->assertSame($after, $this->sentCount(), 'ส่งครั้งเดียว');
    }

    public function test_expiry_by_cron_delivers_deferred_items(): void
    {
        $reading = $this->unpaidCelticUnderTakeover();
        $reading->forceFill(['is_paid' => true, 'paid_at' => now(), 'conversation_status' => FortuneReading::STATUS_CELTIC_PICKING])->save();
        TakeoverResumeService::deferResponse($reading, TakeoverResumeService::ITEM_CELTIC_START,
            ['action' => 'celtic_paid_start', 'message' => 'เริ่มเปิดไพ่ใบแรกได้เลยค่ะ'], ['message_tag' => 'POST_PURCHASE_UPDATE']);
        $reading->forceFill(['admin_takeover_until' => now()->subSecond()])->save();

        $this->assertSame(1, app(FortuneTakeoverService::class)->cleanupExpired());

        $this->assertStringContainsString('เริ่มเปิดไพ่ใบแรก', $this->sentTexts());
        $this->assertDatabaseHas('fortune_takeover_logs', ['fortune_reading_id' => $reading->id, 'action' => 'auto_expire']);
    }

    // ============================================================
    // แอดมินส่งข้อความ
    // ============================================================

    public function test_app_chat_send_starts_a_takeover_and_reaches_the_customer(): void
    {
        $this->actAs($this->makeAdmin());
        $reading = $this->makeReading(['conversation_status' => FortuneReading::STATUS_TIER_CHOICE]);

        $res = $this->postJson('/api/admin/chat/send', ['reading_id' => $reading->id, 'text' => 'สวัสดีค่ะ แอดมินเองค่ะ'])->assertOk();

        $res->assertJsonPath('data.delivered', true)->assertJsonPath('data.is_takeover', true);
        $this->assertStringContainsString('แอดมินเองค่ะ', $this->sentTexts());
        $fresh = $reading->fresh();
        $this->assertTrue($fresh->isAdminTakenOver());
        $this->assertGreaterThanOrEqual(29, $fresh->takeoverRemainingMinutes(), 'ค่าเริ่มต้น 30 นาที (ไม่ใช่ 1 นาทีจาก setting)');
    }

    public function test_app_chat_send_under_takeover_is_delivered_and_keeps_takeover_alive(): void
    {
        $this->actAs($this->makeAdmin());
        $reading = $this->makeReading([
            'admin_takeover_reason' => FortuneReading::TAKEOVER_REASON_MANUAL,
            'admin_takeover_started_at' => now()->subMinutes(25),
            'admin_takeover_until' => now()->addMinutes(5),
        ]);

        $this->postJson('/api/admin/chat/send', ['reading_id' => $reading->id, 'text' => 'รอแป๊บนะคะ'])->assertOk()
            ->assertJsonPath('data.delivered', true);

        $this->assertStringContainsString('รอแป๊บนะคะ', $this->sentTexts());
        $this->assertGreaterThanOrEqual(29, $reading->fresh()->takeoverRemainingMinutes());
    }

    public function test_web_panel_send_is_delivered_under_takeover(): void
    {
        $admin = $this->makeAdmin();
        Auth::login($admin);
        $reading = $this->makeReading([
            'admin_takeover_reason' => FortuneReading::TAKEOVER_REASON_MANUAL,
            'admin_takeover_started_at' => now()->subMinutes(1),
            'admin_takeover_until' => now()->addMinutes(20),
        ]);

        $request = HttpRequest::create('/admin/takeover/'.$reading->id.'/send', 'POST', ['message' => 'แอดมินตอบจากเว็บ']);
        $response = app(FortuneTakeoverController::class)->sendMessage($request, $reading);

        $this->assertTrue($response->getData(true)['success']);
        $this->assertStringContainsString('แอดมินตอบจากเว็บ', $this->sentTexts());
    }
}
