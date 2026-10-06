<?php

namespace Tests\Feature\Fortune;

use App\Jobs\DeliverTakeoverDeferredJob;
use App\Jobs\ProcessBufferedCelticMessageJob;
use App\Jobs\SendFortuneBubbleJob;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\ConversationStateAtomic;
use App\Services\Fortune\FortuneChatLogService;
use App\Services\Fortune\ReplyTokenVault;
use App\Services\Fortune\TakeoverIngress;
use App\Services\Fortune\TakeoverResumeService;
use App\Services\Fortune\TakeoverSendGuard;
use App\Services\FortuneTakeoverService;
use App\Services\LineAlertService;
use App\Services\LineFortuneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\TestCase;

/**
 * 🤫 (2026-10-06) bug-hunt ของงาน "บอทเงียบระหว่างแอดมินเทคโอเวอร์" — ของที่จ่ายแล้วต้องไม่หาย ไม่ซ้ำ ไม่ค้าง
 *
 * C1 รูปช้าไม่ทับของที่พัก · C2 ส่งไม่ออก = เก็บไว้ลองใหม่ / เทคโอเวอร์ซ้ำ = หยุด · L1 พักหลังจบ = ส่งเลย + ตัวกวาด
 * L2 พักพร้อมกันไม่ทับกัน · L3 บับเบิ้ลของแอดมินไม่ถูกพัก · L5 คืนงาน/สถานะระดับลูกค้า · L6 คำถามค้างไม่ถูกตอบซ้ำ
 * L7 LINE ห้าม push กล่องทำต่อ · L8 กล่องล้าสมัยไม่ส่ง · L10 ส่งไม่ออกถอยเทคโอเวอร์ · L11 เลื่อนนาฬิกา · L12 echo แอดมิน
 */
#[Group('takeover-silence')]
class TakeoverDeliveryHardeningTest extends TestCase
{
    use BuildsAdminAppWorld;
    use RefreshDatabase;

    private const FB_UID = '61550000000077';

    private const FB_UID_2 = '61550000000088';

    private const LINE_UID = 'U1234567890abcdef1234567890abcdef';

    private const PAGE_ID = '100000000000001';

    /** FB ส่งไม่ออก (โค้ด 190 = ไม่ retry ในตัว) */
    private bool $fbFails = false;

    /** เรียกหลัง FB ส่งสำเร็จแต่ละครั้ง (จำลองแอดมินกดเทคโอเวอร์ซ้ำระหว่างส่ง) */
    private ?\Closure $afterFbSend = null;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Storage::fake('local');
        TakeoverSendGuard::endMemoScope();
        FortuneTellingSetting::clearSettingsCache();

        $settings = FortuneTellingSetting::getSettings();
        $settings->is_enabled = true;
        $settings->facebook_page_token = 'EAAtestPageToken';
        $settings->facebook_app_id = '999000999';
        $settings->line_enabled = true;
        $settings->line_channel_access_token = 'line-test-token';
        $settings->line_channel_secret = 'line-test-secret';
        $settings->admin_handover_enabled = true;
        $settings->admin_handover_timeout = 1; // ค่าบน prod
        $settings->save();
        FortuneTellingSetting::clearSettingsCache();

        $this->app->instance(FortuneChatLogService::class, new class extends FortuneChatLogService
        {
            public function record(string $platform, string $userId, string $role, ?string $text, array $meta = []): void {}
        });

        $this->mock(LineAlertService::class, fn ($m) => $m->shouldIgnoreMissing());

        Http::fake(function (Request $r) {
            if (str_contains($r->url(), '/me/messages')) {
                if ($this->fbFails) {
                    return Http::response(['error' => ['code' => 190, 'message' => 'token expired']], 400);
                }
                if ($this->afterFbSend) {
                    ($this->afterFbSend)($r);
                }
            }

            return Http::response(['message_id' => 'm_1', 'recipient_id' => self::FB_UID], 200);
        });
    }

    protected function tearDown(): void
    {
        TakeoverSendGuard::endMemoScope();
        $this->tearDownAdminAppWorld();

        parent::tearDown();
    }

    // ============================================================
    // ตัวช่วย
    // ============================================================

    private function fbTexts(): string
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), '/me/messages'))
            ->map(fn ($pair) => json_encode($pair[0]->data(), JSON_UNESCAPED_UNICODE))
            ->implode("\n");
    }

    private function lineRequests(): array
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'api.line.me/v2/bot/message/'))->all();
    }

    private function takeover(int $minutes = 20, ?string $startedAgo = '-1 minute'): array
    {
        return [
            'admin_takeover_reason' => FortuneReading::TAKEOVER_REASON_MANUAL,
            'admin_takeover_started_at' => now()->modify($startedAgo),
            'admin_takeover_until' => now()->addMinutes($minutes),
        ];
    }

    private function paidCeltic(array $attrs = [], ?array $state = null): FortuneReading
    {
        return $this->makeReading(array_merge([
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'conversation_status' => FortuneReading::STATUS_CELTIC_PICKING,
            'is_paid' => true,
            'amount_paid' => 99,
            'paid_at' => now()->subMinutes(10),
            'bill_reference' => FortuneReading::generateBillReference(),
        ], $attrs), $state);
    }

    private function deferStartBox(FortuneReading $reading, string $text = 'ตัดบิลเรียบร้อย เริ่มเปิดไพ่ใบที่ 1 ได้เลยค่ะ'): void
    {
        TakeoverResumeService::deferResponse($reading, TakeoverResumeService::ITEM_CELTIC_START, [
            'action' => 'celtic_paid_start',
            'message' => $text,
        ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);
    }

    private function deferred(FortuneReading $reading): array
    {
        return (array) $reading->fresh()->getConversationState('takeover_deferred', []);
    }

    // ============================================================
    // C1 — โหลดรูปช้าแล้วเขียนทับ state ทั้งก้อน
    // ============================================================

    public function test_c1_slow_image_download_does_not_wipe_items_deferred_meanwhile(): void
    {
        $reading = $this->paidCeltic($this->takeover());

        $handled = TakeoverIngress::intercept('facebook', self::FB_UID, [
            'kind' => 'image',
            'image_fetcher' => function () use ($reading) {
                // ระหว่าง "โหลดรูป" (prod ช้าได้ถึง 20 วิ) — ตัดบิล SMS พักกล่องเริ่มเปิดไพ่จากอีกโปรเซส
                $this->deferStartBox(FortuneReading::find($reading->id));
                ConversationStateAtomic::set($reading->id, 'sms_marker', 'จากอีกโปรเซส');

                return base64_encode('JPEGBYTES-SLIP');
            },
        ]);

        $this->assertTrue($handled);
        $fresh = $reading->fresh();
        $deferred = (array) $fresh->getConversationState('takeover_deferred');
        $this->assertArrayHasKey(TakeoverResumeService::ITEM_CELTIC_START, $deferred, 'ของที่พักระหว่างโหลดรูปต้องไม่ถูกเขียนทับ');
        $this->assertArrayHasKey(TakeoverResumeService::ITEM_CELTIC_RESUME, $deferred);
        $this->assertSame('จากอีกโปรเซส', $fresh->getConversationState('sms_marker'));

        // M2 — รูปเก็บโฟลเดอร์ของตัวเอง · flow สลิปได้ "สำเนา" (ทางนั้นลบไฟล์หลังหยิบไปตรวจ)
        $images = (array) $fresh->getConversationState('takeover_images');
        $this->assertCount(1, $images);
        $this->assertStringStartsWith(TakeoverIngress::IMAGE_DIR.'/', $images[0]['path']);
        Storage::disk('local')->assertExists($images[0]['path']);
        $slipCopy = Cache::get('fortune:pending_slip:facebook:'.self::FB_UID);
        $this->assertNotSame($images[0]['path'], $slipCopy);
        Storage::disk('local')->assertExists($slipCopy);
    }

    // ============================================================
    // C2 — ส่งไม่ออกต้องไม่หาย / เทคโอเวอร์ซ้ำระหว่างส่ง = หยุด
    // ============================================================

    public function test_c2_failed_send_keeps_the_item_counts_the_attempt_and_delivers_on_retry(): void
    {
        $reading = $this->paidCeltic($this->takeover());
        $this->deferStartBox($reading);

        $this->fbFails = true;
        app(FortuneTakeoverService::class)->resume($reading->fresh(), null, true, true);

        $item = $this->deferred($reading)[TakeoverResumeService::ITEM_CELTIC_START] ?? null;
        $this->assertIsArray($item, 'ส่งไม่ออก (Graph error) = ของต้องยังอยู่ในรายการพัก');
        $this->assertSame(1, (int) $item['attempts']);
        $this->assertNull($reading->fresh()->admin_takeover_until);

        // job รอบถัดไป (backoff) — ส่งออกแล้วค่อยลบ
        $this->fbFails = false;
        $report = TakeoverResumeService::deliverDeferred('facebook', self::FB_UID);

        $this->assertSame([TakeoverResumeService::ITEM_CELTIC_START], $report['delivered']);
        $this->assertStringContainsString('เริ่มเปิดไพ่ใบที่ 1', $this->fbTexts());
        $this->assertNull($reading->fresh()->getConversationState('takeover_deferred'));
    }

    public function test_c2_admin_retaking_over_mid_delivery_leaves_the_rest_for_the_next_end(): void
    {
        $first = $this->paidCeltic();
        $second = $this->paidCeltic([
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            'conversation_status' => FortuneReading::STATUS_COLLECTING_BIRTHDATE,
            'amount_paid' => 39,
        ]);
        ConversationStateAtomic::setChild($first, 'takeover_deferred', TakeoverResumeService::ITEM_CELTIC_START, [
            'at' => now()->toIso8601String(),
            'fp' => TakeoverResumeService::stateFingerprint($first),
            'payload' => ['action' => 'celtic_paid_start', 'message' => 'กล่องแรกของบิลแรก'],
        ]);
        ConversationStateAtomic::setChild($second, 'takeover_deferred', TakeoverResumeService::ITEM_PAYFIRST_BIRTHDATE, [
            'at' => now()->toIso8601String(),
            'fp' => TakeoverResumeService::stateFingerprint($second),
            'payload' => ['action' => 'payfirst_birthdate', 'message' => 'ขอวันเกิดหน่อยค่ะ'],
        ]);

        // แอดมินกดเทคโอเวอร์ซ้ำทันทีที่กล่องแรกออก
        $this->afterFbSend = function () use ($first) {
            FortuneReading::query()->whereKey($first->id)->update($this->takeover(30, 'now'));
            $this->afterFbSend = null;
        };

        $report = TakeoverResumeService::deliverDeferred('facebook', self::FB_UID);

        $this->assertTrue($report['blocked']);
        $this->assertStringContainsString('กล่องแรกของบิลแรก', $this->fbTexts());
        $this->assertStringNotContainsString('ขอวันเกิดหน่อยค่ะ', $this->fbTexts(), 'เทคโอเวอร์ซ้ำ = หยุดส่งทันที');
        $this->assertArrayNotHasKey(TakeoverResumeService::ITEM_CELTIC_START, $this->deferred($first));
        $this->assertArrayHasKey(TakeoverResumeService::ITEM_PAYFIRST_BIRTHDATE, $this->deferred($second), 'ที่เหลือรอจบรอบหน้า');
    }

    // ============================================================
    // L1 — ไม่ค้างถาวร
    // ============================================================

    public function test_l1_item_deferred_after_the_takeover_already_ended_is_dispatched_right_away(): void
    {
        Queue::fake();
        $reading = $this->paidCeltic(); // เทคโอเวอร์จบไปแล้วระหว่าง "เช็ค → พัก" ของผู้เรียก

        TakeoverResumeService::deferPaid($reading, TakeoverResumeService::ITEM_CELTIC_START, 'Celtic 99', [
            'action' => 'celtic_paid_start', 'message' => 'เริ่มเปิดไพ่',
        ]);

        Queue::assertPushed(DeliverTakeoverDeferredJob::class, fn ($job) => $job->userId === self::FB_UID);
    }

    public function test_l1_sweeper_dispatches_stuck_items_only_for_customers_no_longer_taken_over(): void
    {
        Queue::fake();
        $free = $this->paidCeltic();
        $busy = $this->paidCeltic(array_merge(
            ['platform_user_id' => self::FB_UID_2, 'facebook_user_id' => self::FB_UID_2],
            $this->takeover()
        ));
        foreach ([$free, $busy] as $r) {
            ConversationStateAtomic::setChild($r, 'takeover_deferred', TakeoverResumeService::ITEM_CELTIC_START, [
                'at' => now()->subHour()->toIso8601String(),
                'payload' => ['action' => 'celtic_paid_start', 'message' => 'x'],
            ]);
        }

        $this->artisan('fortune:expire-conversations')->assertExitCode(0);

        Queue::assertPushed(DeliverTakeoverDeferredJob::class, fn ($job) => $job->userId === self::FB_UID);
        Queue::assertNotPushed(DeliverTakeoverDeferredJob::class, fn ($job) => $job->userId === self::FB_UID_2);
    }

    // ============================================================
    // L2 — พักพร้อมกันจากสำเนาเก่าไม่ทับกัน
    // ============================================================

    public function test_l2_two_stale_copies_deferring_different_items_keep_both(): void
    {
        $reading = $this->paidCeltic($this->takeover());
        $copyA = FortuneReading::find($reading->id);
        $copyB = FortuneReading::find($reading->id);

        TakeoverResumeService::defer($copyA, TakeoverResumeService::ITEM_CELTIC_ANSWERS);
        TakeoverResumeService::defer($copyB, TakeoverResumeService::ITEM_BUBBLES);

        $deferred = $this->deferred($reading);
        $this->assertArrayHasKey(TakeoverResumeService::ITEM_CELTIC_ANSWERS, $deferred);
        $this->assertArrayHasKey(TakeoverResumeService::ITEM_BUBBLES, $deferred);
    }

    // ============================================================
    // L3 — บับเบิ้ล 2..N ของ "แอดมินสั่ง AI ตอบแทน"
    // ============================================================

    public function test_l3_bubbles_queued_inside_the_admin_scope_are_sent_during_takeover(): void
    {
        $reading = $this->paidCeltic($this->takeover());

        $adminJob = TakeoverSendGuard::asHumanAdmin(fn () => new SendFortuneBubbleJob(
            'facebook', self::FB_UID, ['ท่อนที่สองของคำตอบแอดมิน'], null, [], 1, 1, (int) $reading->id
        ));
        $botJob = new SendFortuneBubbleJob('facebook', self::FB_UID, ['ท่อนของบอทที่ต้องพัก'], null, [], 1, 1, (int) $reading->id);

        $this->assertTrue($adminJob->asHumanAdmin);
        $this->assertFalse($botJob->asHumanAdmin);

        // worker รันทีหลัง — นอกขอบเขต asHumanAdmin ของโปรเซสที่สั่ง
        $adminJob->handle();
        $botJob->handle();

        $this->assertStringContainsString('ท่อนที่สองของคำตอบแอดมิน', $this->fbTexts());
        $this->assertStringNotContainsString('ท่อนของบอทที่ต้องพัก', $this->fbTexts());
        $this->assertArrayHasKey(TakeoverResumeService::ITEM_BUBBLES, $this->deferred($reading));
    }

    // ============================================================
    // L5 — คืนงาน/สถานะระดับลูกค้า
    // ============================================================

    public function test_l5_resume_ends_every_bill_of_the_customer_and_status_reports_customer_level(): void
    {
        $this->actAs($this->makeAdmin());
        $tapped = $this->paidCeltic(); // บิลที่แอปเปิดอยู่ — ไม่ได้เทคโอเวอร์
        $holder = $this->paidCeltic($this->takeover()); // บิลอื่นของคนเดียวกันถือเทคโอเวอร์อยู่
        $this->deferStartBox($holder);

        $this->getJson('/api/admin/chat/takeover-status?reading_id='.$tapped->id)->assertOk()
            ->assertJsonPath('data.is_takeover', false)
            ->assertJsonPath('data.customer.is_takeover', true)
            ->assertJsonPath('data.customer.reading_id', $holder->id);

        $this->postJson('/api/admin/chat/resume', ['reading_id' => $tapped->id, 'deliver_deferred' => false])->assertOk()
            ->assertJsonPath('data.customer.is_takeover', false);

        $this->assertNull($holder->fresh()->admin_takeover_until, 'คืนงานต้องปิดเทคโอเวอร์ทุกบิลของลูกค้า');
        $this->assertFalse(TakeoverSendGuard::isTakenOver('facebook', self::FB_UID, true));
        $this->assertSame([], $this->deferred($holder), 'deliver_deferred=false ต้องมีผลจริง (ไม่ถูกเมิน)');
        $this->assertStringNotContainsString('ตัดบิลเรียบร้อย', $this->fbTexts());
        $this->assertDatabaseHas('fortune_takeover_logs', ['fortune_reading_id' => $holder->id, 'reason' => 'deferred_discarded']);
    }

    // ============================================================
    // L6 — คำถามที่ค้างตอนจบเทคโอเวอร์ = แอดมินคุยไปแล้ว ห้าม AI ตอบ
    // ============================================================

    public function test_l6_pending_question_is_cleared_without_charging_quota_and_not_recovered(): void
    {
        Queue::fake();
        $reading = $this->paidCeltic(array_merge([
            'conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
            'celtic_questions_used' => 2,
        ], $this->takeover(20, '-10 minutes')), [
            'celtic_pending_q' => ['งานใหม่จะดีไหมคะ'],
            'celtic_pending_q_at' => now()->subMinutes(5)->toIso8601String(),
        ]);

        app(FortuneTakeoverService::class)->resume($reading->fresh(), null, true, true);

        $fresh = $reading->fresh();
        $this->assertNull($fresh->getConversationState('celtic_pending_q'));
        $this->assertNull($fresh->getConversationState('celtic_pending_q_at'));
        $this->assertSame(2, (int) $fresh->celtic_questions_used, 'ไม่ตัดโควตา');

        $this->artisan('fortune:celtic-answer-recover')->assertExitCode(0);
        Queue::assertNotPushed(ProcessBufferedCelticMessageJob::class);
    }

    // ============================================================
    // L7 — LINE: กล่อง "แม่หมอกลับมาแล้ว" ห้าม push
    // ============================================================

    public function test_l7_line_resume_box_never_pushes_and_rides_on_the_next_reply(): void
    {
        $reading = $this->paidCeltic(array_merge([
            'platform' => 'line',
            'platform_user_id' => self::LINE_UID,
            'facebook_user_id' => self::LINE_UID,
            'conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
        ], $this->takeover()));
        TakeoverResumeService::defer($reading, TakeoverResumeService::ITEM_CELTIC_RESUME);

        // คืนงานตอนที่ไม่มี replyToken สด → ห้าม push · จอดรอ
        app(FortuneTakeoverService::class)->resume($reading->fresh(), null, true, true);

        $this->assertSame([], $this->lineRequests(), 'LINE: ห้าม push กล่องทำต่อ');
        $item = $this->deferred($reading)[TakeoverResumeService::ITEM_CELTIC_RESUME] ?? null;
        $this->assertTrue((bool) ($item['await_reply'] ?? false));

        // ลูกค้าทักมา → บอทตอบด้วย reply → กล่องแนบไปด้วย (ฟรี)
        TakeoverSendGuard::bindReplyToken('rt-next', 'line', self::LINE_UID);
        $ok = (new LineFortuneService(FortuneTellingSetting::getSettings()))
            ->replyMessage('rt-next', [['type' => 'text', 'text' => 'คำตอบของรอบนี้']]);

        $this->assertTrue($ok);
        $requests = $this->lineRequests();
        $this->assertCount(1, $requests);
        $this->assertStringContainsString('/message/reply', $requests[0][0]->url());
        $messages = $requests[0][0]->data()['messages'];
        $this->assertCount(2, $messages);
        $this->assertStringContainsString('แม่หมอกลับมาดูแลต่อแล้ว', $messages[0]['text']);
        $this->assertSame('คำตอบของรอบนี้', $messages[1]['text']);
        $this->assertArrayNotHasKey(TakeoverResumeService::ITEM_CELTIC_RESUME, $this->deferred($reading));
    }

    public function test_l7_line_resume_box_uses_a_banked_reply_token_when_there_is_one(): void
    {
        $reading = $this->paidCeltic(array_merge([
            'platform' => 'line',
            'platform_user_id' => self::LINE_UID,
            'facebook_user_id' => self::LINE_UID,
        ], $this->takeover()));
        TakeoverResumeService::defer($reading, TakeoverResumeService::ITEM_CELTIC_RESUME);
        ReplyTokenVault::remember('line', self::LINE_UID, 'rt-banked');

        app(FortuneTakeoverService::class)->resume($reading->fresh(), null, true, true);

        $requests = $this->lineRequests();
        $this->assertCount(1, $requests);
        $this->assertStringContainsString('/message/reply', $requests[0][0]->url());
        $this->assertStringContainsString("พิมพ์ *'พร้อม'*", $requests[0][0]->data()['messages'][0]['text']);
        $this->assertSame([], $this->deferred($reading));
    }

    // ============================================================
    // L8 — กล่องที่พักล้าสมัยเมื่อลูกค้าเดินต่อไปแล้ว
    // ============================================================

    public function test_l8_start_box_is_not_resent_after_the_customer_already_opened_card_one(): void
    {
        $reading = $this->paidCeltic($this->takeover());
        $this->deferStartBox($reading);

        // ลูกค้าเปิดไพ่ใบที่ 1 ไปแล้ว (เช่น แอดมินพาเปิด) — สถานะยังเป็น celtic_picking เหมือนเดิม
        ConversationStateAtomic::set($reading, 'celtic_cards', ['1' => ['name' => 'The Fool']]);

        app(FortuneTakeoverService::class)->resume($reading->fresh(), null, true, true);

        $this->assertStringNotContainsString('เริ่มเปิดไพ่ใบที่ 1', $this->fbTexts());
        $this->assertSame([], $this->deferred($reading), 'กล่องล้าสมัย = ลบทิ้ง ไม่ค้าง');
    }

    // ============================================================
    // L10 / L11 / L12
    // ============================================================

    public function test_l10_failed_admin_send_reverts_only_a_takeover_that_request_started(): void
    {
        $this->actAs($this->makeAdmin());
        $fresh = $this->makeReading(['conversation_status' => FortuneReading::STATUS_TIER_CHOICE]);
        $this->fbFails = true;

        $this->postJson('/api/admin/chat/send', ['reading_id' => $fresh->id, 'text' => 'สวัสดีค่ะ'])->assertStatus(502);
        $this->assertNull($fresh->fresh()->admin_takeover_until, 'เริ่มเองแต่ส่งไม่ออก = ถอยกลับ');
        // เทคโอเวอร์ที่ถอยใน request เดียวกัน = ไม่เคยมีผล — ไม่ทิ้งร่องรอยให้คิว "ลูกค้าขอคุยกับคน" นับว่ารับเรื่องแล้ว
        $this->assertDatabaseMissing('fortune_takeover_logs', ['fortune_reading_id' => $fresh->id, 'action' => 'takeover']);
        $this->assertDatabaseMissing('fortune_takeover_logs', ['fortune_reading_id' => $fresh->id, 'action' => 'resume']);

        $already = $this->makeReading(array_merge(
            ['platform_user_id' => self::FB_UID_2, 'facebook_user_id' => self::FB_UID_2],
            $this->takeover(5)
        ));
        $this->postJson('/api/admin/chat/send', ['reading_id' => $already->id, 'text' => 'รอแป๊บนะคะ'])->assertStatus(502);
        $this->assertTrue($already->fresh()->isAdminTakenOver(), 'แค่ต่อเวลาเทคโอเวอร์เดิม = ไม่ถอย');
    }

    public function test_l11_celtic_qa_window_is_shifted_by_the_takeover_overlap(): void
    {
        $reading = $this->paidCeltic(array_merge([
            'conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
            'celtic_first_answered_at' => now()->subMinutes(10),
        ], $this->takeover(20, '-8 minutes')));

        app(FortuneTakeoverService::class)->resume($reading->fresh(), null, true, true);

        // หน้าต่าง 15 นาทีเริ่ม -10 · เทคโอเวอร์ -8 → ตอนนี้ = ทับกัน 8 นาที → เลื่อนเป็น -2
        $shifted = $reading->fresh()->celtic_first_answered_at;
        $this->assertEqualsWithDelta(now()->subMinutes(2)->getTimestamp(), $shifted->getTimestamp(), 5);
        $this->assertTrue($reading->fresh()->canAskMoreCeltic());
    }

    public function test_l12_human_typed_page_inbox_echo_starts_a_takeover_but_bot_and_suite_echoes_do_not(): void
    {
        // ไม่ทดสอบการเก็บ Q&A ของแอดมินตรงนี้ (เป็น job แยก) — ปิดไว้ไม่ให้ไปเรียก AI
        $settings = FortuneTellingSetting::getSettings();
        $settings->admin_qa_capture_enabled = false;
        $settings->save();
        FortuneTellingSetting::clearSettingsCache();

        $reading = $this->paidCeltic();
        $echo = fn (array $message) => [
            'object' => 'page',
            'entry' => [[
                'id' => self::PAGE_ID,
                'time' => time(),
                'messaging' => [[
                    'sender' => ['id' => self::PAGE_ID],
                    'recipient' => ['id' => self::FB_UID],
                    'timestamp' => time() * 1000,
                    'message' => array_merge(['is_echo' => true, 'mid' => 'mid.'.uniqid()], $message),
                ]],
            ]],
        ];

        // echo ของบอทเอง / Meta Business Suite (แยกจาก automation ไม่ได้) → ไม่เทคโอเวอร์
        $this->postJson('/webhook/facebook', $echo(['text' => 'ข้อความบอท', 'app_id' => 999000999]))->assertOk();
        $this->postJson('/webhook/facebook', $echo(['text' => 'จาก Business Suite', 'app_id' => 263902037430900]))->assertOk();
        $this->assertNull($reading->fresh()->admin_takeover_until);

        // แอดมินพิมพ์เองใน Page Inbox (ไม่มี app_id) → เทคโอเวอร์ max(30, ค่าที่ตั้ง=1)
        $this->postJson('/webhook/facebook', $echo(['text' => 'สวัสดีค่ะ แอดมินเองนะคะ']))->assertOk();

        $fresh = $reading->fresh();
        $this->assertTrue($fresh->isAdminTakenOver());
        $this->assertSame(FortuneReading::TAKEOVER_REASON_AUTO_REPLY, $fresh->admin_takeover_reason);
        $this->assertGreaterThanOrEqual(29, $fresh->takeoverRemainingMinutes());
    }

    // ============================================================
    // L9 — ล็อกกันส่งบทสรุปซ้อน
    // ============================================================

    public function test_l9_summary_redeliver_skips_a_reading_another_process_is_sending(): void
    {
        $reading = $this->paidCeltic(['conversation_status' => FortuneReading::STATUS_COMPLETED], [
            'celtic_summary_delivered' => false,
            'celtic_finale_text' => 'บทสรุปไพ่ 10 ใบของคุณ',
        ]);
        Cache::add("fortune:celtic_summary_send:{$reading->id}", 1, 180); // อีกโปรเซสถือล็อกอยู่

        $this->artisan('fortune:celtic-summary-redeliver', ['--reading' => $reading->id]);

        $this->assertStringNotContainsString('บทสรุปไพ่ 10 ใบ', $this->fbTexts());
    }
}
