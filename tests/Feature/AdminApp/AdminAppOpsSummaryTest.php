<?php

namespace Tests\Feature\AdminApp;

use App\Models\AiApiKey;
use App\Models\FortuneReading;
use App\Models\FortuneTakeoverLog;
use App\Models\SmsPaymentNotification;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\TestCase;

/**
 * 🏠 GET /api/admin/ops/summary — หน้าแรกของแอปแอดมิน
 *
 * ล็อกไว้:
 *   - รายได้ = เงินเข้าจริง (บิลดูดวงที่กลายเป็นจ่ายแล้ววันนี้ + ออเดอร์ที่จ่ายแล้ววันนี้) ไม่ใช่ค่าคอมที่จ่ายออก
 *     บิลยกเลิก/คืนเงิน (is_paid = 0) · บิลจันทรา · ของเมื่อวาน ไม่นับ · รายชั่วโมงครบ 24 ช่อง
 *   - เทียบเมื่อวาน "ถึงเวลาเดียวกัน" เท่านั้น
 *   - กล่องคิวงานนับตามนิยามที่เขียนในเอกสาร
 */
#[Group('admin-app')]
class AdminAppOpsSummaryTest extends TestCase
{
    use BuildsAdminAppWorld;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 14:30 — มีทั้งชั่วโมงก่อนหน้าและหลังให้เทียบ
        $this->travelTo(now()->startOfDay()->addHours(14)->addMinutes(30));
        Http::fake(); // ห้ามยิง LINE API จริง
    }

    protected function tearDown(): void
    {
        $this->tearDownAdminAppWorld();

        parent::tearDown();
    }

    public function test_revenue_is_real_income_today_with_hourly_and_yesterday_same_time(): void
    {
        $today = now()->startOfDay();
        $yesterday = $today->copy()->subDay();

        // ✅ นับ: จ่ายวันนี้ 09:10 (39.42 · amount_received ชนะ) และ 13:05 (99.17)
        $this->makeReading(['is_paid' => true, 'amount_paid' => 39.42, 'amount_received' => 40.00, 'paid_at' => $today->copy()->setTime(9, 10)]);
        $this->makeReading([
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'is_paid' => true, 'amount_paid' => 99.17, 'paid_at' => $today->copy()->setTime(13, 5),
        ]);
        // ❌ ไม่นับ: บิลจันทรา · บิลที่ถูกพลิกเป็นไม่จ่าย (คืนเงิน) · บิลรอจ่าย
        $this->makeReading(['reading_type' => FortuneReading::READING_TYPE_JUNTRA, 'is_paid' => true, 'amount_paid' => 99, 'paid_at' => $today->copy()->setTime(10, 0)]);
        $this->makeReading(['is_paid' => false, 'amount_paid' => 39.5, 'paid_at' => $today->copy()->setTime(11, 0)]);
        $this->makeReading(['conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT, 'amount_paid' => 39.6]);

        // เมื่อวาน: 08:00 (นับ — ก่อน 14:30) · 20:00 (ไม่นับ — หลังเวลาเดียวกัน)
        $this->makeReading(['is_paid' => true, 'amount_paid' => 39, 'paid_at' => $yesterday->copy()->setTime(8, 0), 'created_at' => $yesterday->copy()->setTime(7, 0)]);
        $this->makeReading(['is_paid' => true, 'amount_paid' => 99, 'paid_at' => $yesterday->copy()->setTime(20, 0), 'created_at' => $yesterday->copy()->setTime(19, 0)]);

        // ร้านค้า: จ่ายแล้ววันนี้ 13:40 (150) · ยังไม่จ่าย (ไม่นับ)
        $buyer = $this->makeMember();
        $this->insertOrder($buyer->id, 'paid', 150.00, $today->copy()->setTime(13, 40));
        $this->insertOrder($buyer->id, 'pending', 999.00, null);

        $this->actAs($this->makeAdmin());
        $res = $this->getJson('/api/admin/ops/summary')->assertOk();

        $this->assertSame(round(40.00 + 99.17, 2), $res->json('data.revenue_today.fortune'));
        $this->assertSame(150.0, (float) $res->json('data.revenue_today.marketplace'));
        $this->assertSame(0.0, (float) $res->json('data.revenue_today.other'));
        $this->assertSame(round(40.00 + 99.17 + 150, 2), $res->json('data.revenue_today.total'));
        $this->assertSame('THB', $res->json('data.revenue_today.currency'));

        $hourly = collect($res->json('data.revenue_today.hourly'));
        $this->assertCount(24, $hourly);
        $this->assertSame(range(0, 23), $hourly->pluck('hour')->all());
        $this->assertSame(40.0, (float) $hourly->firstWhere('hour', 9)['amount']);
        $this->assertSame(round(99.17 + 150, 2), $hourly->firstWhere('hour', 13)['amount']);
        $this->assertSame(0.0, (float) $hourly->firstWhere('hour', 10)['amount']);

        $this->assertSame(39.0, (float) $res->json('data.revenue_yesterday_same_time'));
        $this->assertSame(round(((289.17 - 39) / 39) * 100, 1), $res->json('data.revenue_change_pct'));
    }

    public function test_queue_boxes_count_what_the_doc_says(): void
    {
        $admin = $this->actAs($this->makeAdmin());

        // ลูกค้าขอคุยกับคน (ยังไม่หมดเวลา) 1 · ที่หมดเวลาแล้ว / แอดมินกดเองไม่นับ
        $asked = $this->makeReading([
            'admin_takeover_reason' => FortuneReading::TAKEOVER_REASON_CUSTOMER_REQUEST,
            'admin_takeover_started_at' => now()->subMinutes(12),
            'admin_takeover_until' => now()->addMinutes(18),
        ]);
        FortuneTakeoverLog::create([
            'fortune_reading_id' => $asked->id, 'action' => FortuneTakeoverLog::ACTION_TAKEOVER,
            'reason' => FortuneReading::TAKEOVER_REASON_CUSTOMER_REQUEST, 'message' => 'ขอคุยกับแอดมิน', 'platform' => 'facebook',
        ]);
        $this->makeReading([
            'admin_takeover_reason' => FortuneReading::TAKEOVER_REASON_CUSTOMER_REQUEST,
            'admin_takeover_started_at' => now()->subHours(2),
            'admin_takeover_until' => now()->subHour(),
        ]);
        $this->makeReading([
            'admin_takeover_reason' => FortuneReading::TAKEOVER_REASON_MANUAL,
            'admin_takeover_started_at' => now()->subMinutes(2),
            'admin_takeover_until' => now()->addMinutes(28),
        ]);

        // บิลรอตรวจ 2 (สลิป + แจ้งโอน) · รอโอนเฉย ๆ ไม่นับ
        $this->makeReading(['conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT, 'amount_paid' => 39.21, 'slip_received_at' => now()->subMinutes(30)]);
        $this->makeReading(['conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT, 'amount_paid' => 39.22, 'transfer_reported' => true, 'transfer_reported_at' => now()->subMinutes(5)]);
        $this->makeReading(['conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT, 'amount_paid' => 39.23]);

        // ถอนเงินรออนุมัติ 1 (500) · อนุมัติแล้วไม่นับ
        $member = $this->makeMember();
        $wallet = Wallet::where('user_id', $member->id)->first() ?? Wallet::factory()->create(['user_id' => $member->id]);
        foreach ([['pending', 500], ['approved', 300]] as $i => [$status, $amount]) {
            DB::table('withdrawal_requests')->insert([
                'user_id' => $member->id, 'wallet_id' => $wallet->id, 'request_id' => 'WD-TEST-'.$i,
                'amount' => $amount, 'net_amount' => $amount, 'status' => $status,
                'created_at' => now()->subHours(5), 'updated_at' => now()->subHours(5),
            ]);
        }

        // SMS เงินเข้ายังไม่ผูก 1 · ผูกแล้ว / เงินออก / เก่ากว่า 24 ชม. ไม่นับ
        foreach ([
            ['pending', 'credit', now()->subMinutes(80), 39.17],
            ['matched', 'credit', now()->subMinutes(10), 39.42],
            ['pending', 'debit', now()->subMinutes(10), 100],
            ['pending', 'credit', now()->subDays(2), 39.99],
        ] as $i => [$status, $type, $at, $amount]) {
            $sms = SmsPaymentNotification::create([
                'bank' => 'KBANK', 'type' => $type, 'amount' => $amount, 'sms_timestamp' => $at,
                'device_id' => 'dev-1', 'nonce' => 'n-'.$i, 'status' => $status, 'sender_or_receiver' => 'นาย ก',
            ]);
            DB::table('sms_payment_notifications')->where('id', $sms->id)->update(['created_at' => $at]);
        }

        // บิลค้าง 1: จ่ายแล้วสถานะ paid ไม่ขยับ 6 นาที · ลูกค้ากรอกวันเกิดเงียบไม่ใช่ค้าง
        $this->makeReading(['is_paid' => true, 'amount_paid' => 39, 'paid_at' => now()->subMinutes(8),
            'conversation_status' => FortuneReading::STATUS_PAID, 'updated_at' => now()->subMinutes(6)]);
        $this->makeReading(['is_paid' => true, 'amount_paid' => 39, 'paid_at' => now()->subMinutes(40),
            'conversation_status' => FortuneReading::STATUS_COLLECTING_BIRTHDATE, 'updated_at' => now()->subMinutes(30)]);

        AiApiKey::create(['name' => 'พร้อม', 'provider' => 'gemini', 'api_key' => 'AIzaTestKey000000000000000001', 'is_active' => true, 'last_test_passed_at' => now()]);
        AiApiKey::create(['name' => 'ยังไม่เทส', 'provider' => 'groq', 'api_key' => 'gsk_TestKey000000000000000002', 'is_active' => true]);

        $res = $this->getJson('/api/admin/ops/summary')->assertOk();

        $this->assertSame(1, $res->json('data.queue.customer_requests.count'));
        $this->assertSame(12, $res->json('data.queue.customer_requests.oldest_minutes'));
        $this->assertSame('ขอคุยกับแอดมิน', $res->json('data.queue.customer_requests.preview.0.keyword'));

        $this->assertSame(2, $res->json('data.queue.bills_awaiting.count'));
        $this->assertSame(78.43, $res->json('data.queue.bills_awaiting.amount_thb'));
        $this->assertSame(30, $res->json('data.queue.bills_awaiting.oldest_minutes'));
        $this->assertCount(2, $res->json('data.queue.bills_awaiting.preview'));

        $this->assertSame(1, $res->json('data.queue.withdrawals_pending.count'));
        $this->assertSame(500.0, (float) $res->json('data.queue.withdrawals_pending.amount_thb'));

        $this->assertSame(1, $res->json('data.queue.sms_unmatched.count'));
        $this->assertSame(39.17, $res->json('data.queue.sms_unmatched.amount_thb'));
        $this->assertSame(80, $res->json('data.queue.sms_unmatched.oldest_minutes'));

        $this->assertSame(1, $res->json('data.queue.stuck_readings.count'));
        $this->assertSame('ai_generating_timeout', $res->json('data.queue.stuck_readings.preview.0.stuck_reason'));

        $this->assertSame(['healthy' => 1, 'total' => 2], $res->json('data.health.ai_pool'));
        $this->assertArrayHasKey('line_push', $res->json('data.health'));
        $this->assertArrayHasKey('pending', $res->json('data.health.queue_backlog'));
        $this->assertNotEmpty($res->json('data.health.server_time'));
        $this->assertNotNull($admin);
    }

    public function test_summary_is_cached_briefly_but_timestamps_are_fresh(): void
    {
        $this->actAs($this->makeAdmin());
        $this->makeReading(['conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT, 'amount_paid' => 39.21, 'slip_received_at' => now()->subMinutes(3)]);

        $first = $this->getJson('/api/admin/ops/summary')->assertOk();
        $this->assertSame(1, $first->json('data.queue.bills_awaiting.count'));
        $this->assertSame([], $first->json('data.degraded'));

        // ภายใน 20 วินาที: ข้อมูลชุดเดิม (ใช้ร่วมกันทุกแอดมิน) แต่เวลาของคำตอบเป็นปัจจุบัน
        $this->travel(5)->seconds();
        $this->makeReading(['conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT, 'amount_paid' => 39.22, 'slip_received_at' => now()]);
        $this->actAs($this->makeAdmin());
        $second = $this->getJson('/api/admin/ops/summary')->assertOk();
        $this->assertSame(1, $second->json('data.queue.bills_awaiting.count'));
        $this->assertSame($first->json('data.computed_at'), $second->json('data.computed_at'));
        $this->assertNotSame($first->json('data.generated_at'), $second->json('data.generated_at'));
        $this->assertSame($second->json('data.generated_at'), $second->json('data.health.server_time'));

        // หมดอายุ cache → คำนวณใหม่
        $this->travel(30)->seconds();
        $this->getJson('/api/admin/ops/summary')->assertOk()->assertJsonPath('data.queue.bills_awaiting.count', 2);
    }

    public function test_failing_section_is_null_and_listed_in_degraded(): void
    {
        $this->actAs($this->makeAdmin());
        config(['queue.default' => 'redis']);
        Queue::shouldReceive('size')->andThrow(new \RuntimeException('redis down'));

        $res = $this->getJson('/api/admin/ops/summary')->assertOk();

        $this->assertNull($res->json('data.health.queue_backlog'));
        $this->assertSame(['queue_backlog'], $res->json('data.degraded'));
        // ส่วนอื่นยังมาครบ (กล่องที่อ่านได้ไม่ใช่ null)
        $this->assertSame(0, $res->json('data.queue.bills_awaiting.count'));
        $this->assertNotNull($res->json('data.health.ai_pool'));
    }

    public function test_awaiting_amount_uses_the_same_per_bill_amount_as_the_list(): void
    {
        $this->actAs($this->makeAdmin());

        // บิล Celtic ที่ยังไม่มี amount_paid แต่ออก QR ทศนิยมแล้ว (UPA) + ลูกค้าส่งสลิป
        $upaId = DB::table('unique_payment_amounts')->insertGetId([
            'base_amount' => 99, 'unique_amount' => 99.33, 'decimal_suffix' => 33, 'status' => 'reserved',
            'expires_at' => now()->addHours(3), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->makeReading([
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'conversation_status' => FortuneReading::STATUS_CELTIC_PENDING_PAYMENT,
            'unique_payment_amount_id' => $upaId, 'slip_received_at' => now()->subMinutes(2),
        ]);
        $this->makeReading(['conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT, 'amount_paid' => 39.21, 'transfer_reported' => true]);

        $list = $this->getJson('/api/admin/fortune/bills?status=awaiting')->assertOk();
        $listSum = round(collect($list->json('data.data'))->sum('amount_thb'), 2);
        $this->assertSame(round(99.33 + 39.21, 2), $listSum);

        $this->getJson('/api/admin/fortune/bills/stats')->assertOk()->assertJsonPath('data.awaiting_amount_thb', $listSum);
        $this->getJson('/api/admin/ops/summary')->assertOk()->assertJsonPath('data.queue.bills_awaiting.amount_thb', $listSum);
    }

    private function insertOrder(int $userId, string $paymentStatus, float $total, $paidAt): void
    {
        DB::table('orders')->insert([
            'order_number' => 'ORD-'.uniqid(),
            'user_id' => $userId,
            'subtotal' => $total,
            'total_amount' => $total,
            'payment_status' => $paymentStatus,
            'paid_at' => $paidAt,
            'created_at' => $paidAt ?? now(),
            'updated_at' => $paidAt ?? now(),
        ]);
    }
}
