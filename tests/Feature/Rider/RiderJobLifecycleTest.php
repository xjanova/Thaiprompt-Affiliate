<?php

namespace Tests\Feature\Rider;

use App\Exceptions\RiderJobException;
use App\Models\FreshMarketConversation;
use App\Models\Notification;
use App\Models\PlatformTransaction;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\FreshMarketChannelManager;
use App\Services\RiderDispatchService;
use App\Services\RiderEarningService;
use App\Services\RiderJobService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Rider\Fixtures\FakeDeliverableSource;
use Tests\TestCase;

/**
 * วงจรชีวิตงานไรเดอร์ครบเส้น (ต้องใช้ MySQL — รันบน CI)
 *
 * ครอบคลุม gap จาก audit 2026-09-25:
 *   - RIDER-01/APP-01 สร้างงาน pending ที่ยังไม่มีไรเดอร์ได้จริง (rider_id nullable)
 *   - RIDER-APP-06 รับงานพร้อมกัน → คนที่สองได้ JOB_TAKEN
 *   - RIDER-04/APP-05/G9 state machine + ส่งซ้ำไม่จ่ายเงินซ้ำ
 *   - RIDER-05/APP-08 เครดิตวอลเลตไรเดอร์จริง + ค่าธรรมเนียมแพลตฟอร์ม
 *   - CC-04 COD: หักวอลเลตไรเดอร์ = cod_amount − rider_earnings
 *   - CC-15 ไรเดอร์รับงานออเดอร์ของตัวเองไม่ได้
 *   - RIDER-16 คืนงาน → กลับ pending + ไม่เสนอคนเดิมซ้ำ
 *   - CC-11 ส่งไม่สำเร็จ (failed) ใช้ได้หลังรับของเท่านั้น
 *   - RIDER-30 / RIDER-08 LINE: รับงานผ่าน service เดียวกับแอป, สมัครไรเดอร์ = ส่งลิงก์เว็บ
 */
#[Group('rider')]
class RiderJobLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private const PICKUP_LAT = 13.7291;

    private const PICKUP_LNG = 100.5210;

    private RiderJobService $jobs;

    private RiderDispatchService $dispatch;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Storage::fake('public');
        Cache::flush();
        FakeDeliverableSource::reset();

        Setting::set('rider.require_deposit', '0', 'boolean', 'rider');
        Setting::set('rider.dispatch_mode', 'broadcast', 'string', 'rider');

        $this->jobs = app(RiderJobService::class);
        $this->dispatch = app(RiderDispatchService::class);
    }

    // =====================================================
    // สร้างงาน
    // =====================================================

    public function test_create_job_for_source_creates_open_job_with_fees_and_is_idempotent(): void
    {
        $rider = $this->makeRider();
        $source = $this->makeSource();

        $job = $this->dispatch->createJobForSource($source, 'fresh_market');

        $this->assertNull($job->rider_id);
        $this->assertSame('pending', $job->status);
        $this->assertSame(FakeDeliverableSource::class, $job->source_type);
        $this->assertSame((int) $source->getKey(), (int) $job->source_id);
        $this->assertNotEmpty($job->tracking_token);
        $this->assertGreaterThan(0, (float) $job->rider_earnings);
        $this->assertEqualsWithDelta((float) $job->total_fee, (float) $job->rider_earnings + (float) $job->platform_fee, 0.001);

        // กระจายงาน (broadcast) ให้ไรเดอร์ที่เข้าเงื่อนไขทันที
        $job->refresh();
        $this->assertContains((int) $rider->id, array_map('intval', $job->candidate_riders ?? []));
        $this->assertSame(1, (int) $job->dispatch_round);
        $this->assertTrue(
            Notification::where('user_id', $rider->user_id)->where('type', 'rider_job_offer')->exists(),
            'ไรเดอร์ในรัศมีต้องได้รับแจ้งเตือนงานใหม่ในแอป'
        );

        // เรียกซ้ำ → ได้งานเดิม ไม่สร้างใหม่
        $again = $this->dispatch->createJobForSource($source, 'fresh_market');
        $this->assertSame($job->id, $again->id);
        $this->assertSame(1, RiderJob::forSource($source)->count());
    }

    public function test_party_rider_is_not_offered_and_cannot_accept_own_order(): void
    {
        $buyerRider = $this->makeRider();
        $source = $this->makeSource(['party' => [$buyerRider->user_id], 'customer_id' => $buyerRider->user_id]);

        $job = $this->dispatch->createJobForSource($source, 'fresh_market')->fresh();

        $this->assertNotContains((int) $buyerRider->id, array_map('intval', $job->candidate_riders ?? []));

        $this->assertRiderJobError(RiderJobException::SELF_ORDER, fn () => $this->jobs->accept($job, $buyerRider));
    }

    // =====================================================
    // รับงาน
    // =====================================================

    public function test_second_rider_accepting_gets_job_taken(): void
    {
        $first = $this->makeRider();
        $second = $this->makeRider();
        $job = $this->dispatch->createJobForSource($this->makeSource(), 'fresh_market');

        $accepted = $this->jobs->accept($job, $first);

        $this->assertSame('accepted', $accepted->status);
        $this->assertSame((int) $first->id, (int) $accepted->rider_id);
        $this->assertSame('busy', $first->fresh()->availability);

        $this->assertRiderJobError(RiderJobException::JOB_TAKEN, fn () => $this->jobs->accept($job->fresh(), $second));

        // คนเดิมกดซ้ำ = ได้งานเดิม ไม่ error
        $again = $this->jobs->accept($job->fresh(), $first);
        $this->assertSame((int) $first->id, (int) $again->rider_id);

        $this->assertSame((int) $first->id, (int) RiderJob::find($job->id)->rider_id);
        $this->assertContains(['id' => (int) $job->source_id, 'from' => 'pending', 'to' => 'accepted', 'rider_id' => (int) $first->id], FakeDeliverableSource::$hookCalls);
    }

    public function test_rider_cannot_hold_two_active_jobs(): void
    {
        $rider = $this->makeRider();
        $job1 = $this->dispatch->createJobForSource($this->makeSource(), 'fresh_market');
        $job2 = $this->dispatch->createJobForSource($this->makeSource(), 'fresh_market');

        $this->jobs->accept($job1, $rider);

        $this->assertRiderJobError(RiderJobException::HAS_ACTIVE_JOB, fn () => $this->jobs->accept($job2, $rider));
        $this->assertNull($job2->fresh()->rider_id);
    }

    public function test_rider_without_consent_or_fresh_gps_is_not_eligible(): void
    {
        $noConsent = $this->makeRider(['share_location_consent_at' => null]);
        $stale = $this->makeRider(['last_location_update' => now()->subMinutes(40)]);
        $job = $this->dispatch->createJobForSource($this->makeSource(), 'fresh_market');

        $this->assertRiderJobError(RiderJobException::NOT_ELIGIBLE, fn () => $this->jobs->accept($job, $noConsent));
        $this->assertRiderJobError(RiderJobException::NOT_ELIGIBLE, fn () => $this->jobs->accept($job, $stale));
        $this->assertTrue($job->fresh()->isOpen());
    }

    public function test_cod_job_requires_wallet_credit(): void
    {
        $poor = $this->makeRider([], 10);
        $job = $this->dispatch->createJobForSource($this->makeSource(['cod' => 500]), 'fresh_market');

        $this->assertRiderJobError(RiderJobException::INSUFFICIENT_COD_CREDIT, fn () => $this->jobs->accept($job, $poor));
        $this->assertTrue($job->fresh()->isOpen());
    }

    // =====================================================
    // คืนงาน
    // =====================================================

    public function test_release_returns_job_to_pending_and_excludes_releasing_rider(): void
    {
        $first = $this->makeRider();
        $second = $this->makeRider();
        $job = $this->dispatch->createJobForSource($this->makeSource(), 'fresh_market');

        $this->jobs->accept($job, $first);
        $released = $this->jobs->release($job->fresh(), $first, 'รถเสีย');

        $this->assertSame('pending', $released->status);
        $this->assertNull($released->rider_id);
        $this->assertSame(1, (int) $released->release_count);
        $this->assertSame('online', $first->fresh()->availability);
        $this->assertSame(1, (int) $first->fresh()->cancelled_jobs);

        // กดคืนซ้ำ = ไม่ error ไม่นับซ้ำ
        $this->jobs->release($job->fresh(), $first, 'ซ้ำ');
        $this->assertSame(1, (int) $job->fresh()->release_count);

        // คนที่คืนงานไม่เห็นงานนี้อีก คนอื่นยังเห็นและรับได้
        $this->assertFalse($this->dispatch->availableJobsFor($first->fresh())->contains('id', $job->id));
        $this->assertTrue($this->dispatch->availableJobsFor($second->fresh())->contains('id', $job->id));

        $taken = $this->jobs->accept($job->fresh(), $second);
        $this->assertSame((int) $second->id, (int) $taken->rider_id);
    }

    // =====================================================
    // ส่งของ + เงิน
    // =====================================================

    public function test_deliver_credits_rider_wallet_exactly_once(): void
    {
        $rider = $this->makeRider([], 100);
        $job = $this->dispatch->createJobForSource($this->makeSource(), 'fresh_market');
        $earnings = round((float) $job->rider_earnings, 2);
        $platformFee = round((float) $job->platform_fee, 2);

        $this->jobs->accept($job, $rider);
        $this->jobs->markPickingUp($job->fresh(), $rider);
        $this->jobs->markPickedUp($job->fresh(), $rider, null);
        $this->jobs->markDelivering($job->fresh(), $rider);

        $done = $this->jobs->deliver($job->fresh(), $rider, $this->photo(), 13.74, 100.53, false);

        $this->assertSame('completed', $done->status);
        $this->assertNotNull($done->delivered_at);
        $this->assertNotNull($done->completed_at);
        $this->assertNotNull($done->earnings_settled_at);
        Storage::disk('public')->assertExists($done->delivery_proof_image);

        // ส่งซ้ำ + settle ซ้ำ → ไม่จ่ายซ้ำ
        $this->jobs->deliver($job->fresh(), $rider, $this->photo(), null, null, false);
        app(RiderEarningService::class)->settle($job->fresh());

        $this->assertSame(1, WalletTransaction::where('reference_type', 'rider_job')->where('reference_id', $job->id)->count());
        $this->assertEqualsWithDelta(100 + $earnings, (float) Wallet::where('user_id', $rider->user_id)->value('balance'), 0.001);
        $this->assertSame(1, (int) $rider->fresh()->completed_jobs);
        $this->assertEqualsWithDelta($earnings, (float) $rider->fresh()->total_earnings, 0.001);
        $this->assertSame('online', $rider->fresh()->availability);

        $this->assertSame(1, PlatformTransaction::where('source_type', 'rider_job')->where('source_id', $job->id)->count());
        $this->assertEqualsWithDelta(
            $platformFee,
            (float) PlatformTransaction::where('source_type', 'rider_job')->where('source_id', $job->id)->value('amount'),
            0.001
        );

        $transitions = array_map(fn ($c) => $c['from'].'>'.$c['to'], FakeDeliverableSource::$hookCalls);
        $this->assertContains('delivering>delivered', $transitions);
        $this->assertContains('delivered>completed', $transitions);
    }

    public function test_cod_deliver_debits_rider_wallet_by_cod_minus_earnings(): void
    {
        $rider = $this->makeRider([], 1000);
        $job = $this->dispatch->createJobForSource($this->makeSource(['cod' => 200]), 'fresh_market');
        $earnings = round((float) $job->rider_earnings, 2);

        $this->assertEqualsWithDelta(200.0, (float) $job->cod_amount, 0.001);

        $this->jobs->accept($job, $rider);
        $this->jobs->markPickedUp($job->fresh(), $rider, null);

        // ต้องยืนยันว่าเก็บเงินแล้ว
        $this->assertRiderJobError(
            RiderJobException::COD_CONFIRM_REQUIRED,
            fn () => $this->jobs->deliver($job->fresh(), $rider, $this->photo(), null, null, false)
        );
        $this->assertSame('picked_up', $job->fresh()->status);

        $done = $this->jobs->deliver($job->fresh(), $rider, $this->photo(), null, null, true);
        $this->jobs->deliver($job->fresh(), $rider, $this->photo(), null, null, true); // กดซ้ำ

        $this->assertSame('completed', $done->status);
        $this->assertNotNull($done->fresh()->cod_collected_at);
        $this->assertNotNull($done->fresh()->cod_settled_at);

        $expected = round(1000 - (200 - $earnings), 2);
        $this->assertEqualsWithDelta($expected, (float) Wallet::where('user_id', $rider->user_id)->value('balance'), 0.001);

        $this->assertSame(1, WalletTransaction::where('reference_type', 'rider_job_cod')->where('reference_id', $job->id)->count());
        $this->assertEqualsWithDelta(
            round(200 - $earnings, 2),
            (float) WalletTransaction::where('reference_type', 'rider_job_cod')->where('reference_id', $job->id)->value('amount'),
            0.001
        );
        $this->assertSame(0, WalletTransaction::where('reference_type', 'rider_job')->where('reference_id', $job->id)->count());
    }

    // =====================================================
    // ตัวกันการข้ามขั้น
    // =====================================================

    public function test_transition_guards(): void
    {
        $rider = $this->makeRider();
        $other = $this->makeRider();
        $job = $this->dispatch->createJobForSource($this->makeSource(), 'fresh_market');

        // ยังไม่มีใครรับ → ไรเดอร์เปลี่ยนสถานะไม่ได้
        $this->assertRiderJobError(RiderJobException::NOT_YOUR_JOB, fn () => $this->jobs->markPickingUp($job, $rider));

        $this->jobs->accept($job, $rider);

        // ข้ามขั้นไม่ได้
        $this->assertRiderJobError(RiderJobException::INVALID_TRANSITION, fn () => $this->jobs->markDelivering($job->fresh(), $rider));
        $this->assertRiderJobError(RiderJobException::INVALID_TRANSITION, fn () => $this->jobs->deliver($job->fresh(), $rider, $this->photo(), null, null, false));
        $this->assertRiderJobError(RiderJobException::INVALID_TRANSITION, fn () => $this->jobs->fail($job->fresh(), $rider, 'customer_refused', null));

        // คนอื่นแตะงานไม่ได้
        $this->assertRiderJobError(RiderJobException::NOT_YOUR_JOB, fn () => $this->jobs->markPickedUp($job->fresh(), $other, null));

        $this->jobs->markPickedUp($job->fresh(), $rider, null);

        // หลังรับของ: ยกเลิก/คืนงานไม่ได้ ต้องใช้ fail
        $this->assertRiderJobError(RiderJobException::INVALID_TRANSITION, fn () => $this->jobs->cancel($job->fresh(), 'admin', 'ทดสอบ'));
        $this->assertRiderJobError(RiderJobException::INVALID_TRANSITION, fn () => $this->jobs->release($job->fresh(), $rider, 'ทดสอบ'));
        $this->assertRiderJobError(RiderJobException::INVALID_REASON, fn () => $this->jobs->fail($job->fresh(), $rider, 'bogus', null));
        $this->assertRiderJobError(RiderJobException::INVALID_REASON, fn () => $this->jobs->fail($job->fresh(), $rider, 'other', '  '));

        $failed = $this->jobs->fail($job->fresh(), $rider, 'customer_refused', 'ลูกค้าไม่รับของ');

        $this->assertSame('failed', $failed->status);
        $this->assertSame('customer_refused', $failed->failure_reason);
        $this->assertNotNull($failed->failed_at);
        $this->assertSame('online', $rider->fresh()->availability);

        // สถานะจบแล้ว → ทำอะไรต่อไม่ได้ (ยกเว้นกดซ้ำ fail เดิม)
        $this->assertSame('failed', $this->jobs->fail($job->fresh(), $rider, 'customer_refused', null)->status);
        $this->assertRiderJobError(RiderJobException::INVALID_TRANSITION, fn () => $this->jobs->markDelivering($job->fresh(), $rider));
        $this->assertSame(0, WalletTransaction::where('reference_id', $job->id)->whereIn('reference_type', ['rider_job', 'rider_job_cod'])->count());
    }

    public function test_cancel_jobs_for_source_cancels_open_job_without_calling_back(): void
    {
        $source = $this->makeSource();
        $job = $this->dispatch->createJobForSource($source, 'fresh_market');
        FakeDeliverableSource::$hookCalls = [];

        $this->dispatch->cancelJobsForSource($source, 'buyer', 'ลูกค้ายกเลิกออเดอร์');

        $job->refresh();
        $this->assertSame('cancelled', $job->status);
        $this->assertSame('buyer', $job->cancelled_by);
        $this->assertNotNull($job->cancelled_at);
        $this->assertSame([], FakeDeliverableSource::$hookCalls, 'ออเดอร์สั่งยกเลิกเอง ไม่ต้องเรียก hook กลับ');

        // ยกเลิกแล้วสร้างงานใหม่ได้ (งานเดิมจบแล้ว)
        $new = $this->dispatch->createJobForSource($source, 'fresh_market');
        $this->assertNotSame($job->id, $new->id);
    }

    public function test_admin_reassign_keeps_status_and_moves_rider(): void
    {
        $first = $this->makeRider();
        $second = $this->makeRider();
        $admin = User::factory()->create();
        $job = $this->dispatch->createJobForSource($this->makeSource(), 'fresh_market');

        $this->jobs->accept($job, $first);
        $this->jobs->markPickingUp($job->fresh(), $first);

        $moved = $this->jobs->adminReassign($job->fresh(), $second, $admin);

        $this->assertSame('picking_up', $moved->status);
        $this->assertSame((int) $second->id, (int) $moved->rider_id);
        $this->assertSame('busy', $second->fresh()->availability);
        $this->assertSame('online', $first->fresh()->availability);
    }

    // =====================================================
    // LINE (reply เท่านั้น)
    // =====================================================

    public function test_line_accept_postback_goes_through_rider_job_service(): void
    {
        $first = $this->makeRider(['line_user_id' => 'Uline-first']);
        $second = $this->makeRider(['line_user_id' => 'Uline-second']);
        $pending = $this->makeRider(['line_user_id' => 'Uline-pending', 'status' => 'pending']);
        $job = $this->dispatch->createJobForSource($this->makeSource(), 'fresh_market');

        $manager = new FreshMarketChannelManager;
        $accept = new \ReflectionMethod($manager, 'handleRiderAcceptJobPostback');

        $denied = $accept->invoke($manager, 'Uline-pending', ['job_id' => $job->id]);
        $this->assertStringContainsString('ตรวจสอบ', $denied['text']);

        $ok = $accept->invoke($manager, 'Uline-first', ['job_id' => $job->id]);
        $this->assertStringContainsString('รับงานสำเร็จ', $ok['text']);

        $taken = $accept->invoke($manager, 'Uline-second', ['job_id' => $job->id]);
        $this->assertStringContainsString('ถูกไรเดอร์คนอื่นรับไปแล้ว', $taken['text']);

        $this->assertSame((int) $first->id, (int) $job->fresh()->rider_id);
        $this->assertNotSame((int) $second->id, (int) $job->fresh()->rider_id);
        $this->assertNotSame((int) $pending->id, (int) $job->fresh()->rider_id);
    }

    public function test_line_rider_signup_replies_with_web_link_and_creates_nothing(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['line_user_id' => 'Uline-signup-'.$user->id])->save();
        $conversation = FreshMarketConversation::getOrCreate($user->line_user_id);

        $manager = new FreshMarketChannelManager;
        $reply = (new \ReflectionMethod($manager, 'handleCommand'))
            ->invoke($manager, 'rider', $user->line_user_id, $conversation, []);

        $this->assertStringContainsString('/user/rider/register', $reply['text']);
        $this->assertFalse(Rider::where('user_id', $user->id)->exists(), 'ห้ามสร้าง Rider ครึ่งๆ กลางๆ จากแชท');
    }

    // =====================================================
    // Helpers
    // =====================================================

    /**
     * @param  array<string, mixed>  $config
     */
    private function makeSource(array $config = []): FakeDeliverableSource
    {
        $holder = User::factory()->create();
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        FakeDeliverableSource::configure((int) $holder->id, array_merge([
            'pickup' => [
                'name' => 'ร้านป้าแดง',
                'address' => 'ตลาดบางรัก เขตบางรัก กรุงเทพมหานคร',
                'latitude' => self::PICKUP_LAT,
                'longitude' => self::PICKUP_LNG,
                'phone' => '0811111111',
                'notes' => null,
            ],
            'dropoff' => [
                'name' => 'คุณลูกค้า',
                'address' => '88/8 ซ.เจริญกรุง 30 แขวงบางรัก เขตบางรัก กรุงเทพมหานคร 10500',
                'latitude' => 13.7400,
                'longitude' => 100.5300,
                'phone' => '0822222222',
                'notes' => 'ฝากไว้ที่ป้อม',
            ],
            'cod' => 0,
            'party' => [(int) $buyer->id, (int) $seller->id],
            'customer_id' => (int) $buyer->id,
        ], $config));

        return FakeDeliverableSource::findOrFail($holder->id);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeRider(array $overrides = [], float $walletBalance = 0): Rider
    {
        $user = User::factory()->create();

        $rider = Rider::create(array_merge([
            'user_id' => $user->id,
            'full_name' => 'ไรเดอร์ '.$user->id,
            'phone' => '08'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT),
            'status' => 'approved',
            'availability' => 'online',
            'rider_type' => 'delivery',
            'vehicle_type' => 'motorcycle',
            'last_latitude' => self::PICKUP_LAT + 0.001,
            'last_longitude' => self::PICKUP_LNG + 0.001,
            'last_location_update' => now(),
            'share_location_consent_at' => now(),
            'approved_at' => now(),
        ], $overrides));

        $wallet = app(WalletService::class)->getOrCreateWallet($user);
        $wallet->forceFill(['balance' => $walletBalance])->save();

        return $rider->fresh();
    }

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->create('proof.jpg', 120, 'image/jpeg');
    }

    private function assertRiderJobError(string $code, callable $callback): void
    {
        try {
            $callback();
            $this->fail("ต้องได้ RiderJobException {$code}");
        } catch (RiderJobException $e) {
            $this->assertSame($code, $e->errorCode, 'ได้รหัส '.$e->errorCode.': '.$e->getMessage());
        }
    }
}
