<?php

namespace Tests\Feature\AdminApp;

use App\Models\FortuneReading;
use App\Models\SmsPaymentNotification;
use App\Services\AdminApp\FortuneBillBuckets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\TestCase;

/**
 * 🧾 GET /api/admin/fortune/bills + /stats — กองสถานะบิลของแอปแอดมิน
 *
 * ล็อกไว้:
 *   1. SQL ของแต่ละกอง (statusSql) ตรงกับฝั่ง PHP (statusOf) ทุกรูปบิล และแต่ละใบอยู่ได้กองเดียว
 *   2. แถวแชทที่ไม่เคยออกบิล + ไพ่ฟรีไม่มียอด ไม่โผล่เป็นบิล (rule_fortune_reading_row_is_not_a_bill)
 *   3. บิลยกเลิก = completed + ไม่จ่าย + มีเหตุผล — ไม่ใช่ status 'cancelled' อย่างเดียว
 *   4. ช่องทางอ่านจากคอลัมน์ platform (LINE id อยู่ใน facebook_user_id ได้)
 */
#[Group('admin-app')]
class AdminAppFortuneBillsTest extends TestCase
{
    use BuildsAdminAppWorld;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // เที่ยงวัน — กันเทสต์ "จ่ายวันนี้" เพี้ยนตอนรันคร่อมเที่ยงคืน
        $this->travelTo(now()->startOfDay()->addHours(12));
    }

    protected function tearDown(): void
    {
        $this->tearDownAdminAppWorld();

        parent::tearDown();
    }

    /**
     * ชุดบิลครบทุกรูป — key = ชื่อเคส · value = [reading, กองที่คาดหวัง|null (null = ไม่ใช่บิล)]
     *
     * @return array<string, array{0: FortuneReading, 1: string|null}>
     */
    private function seedBills(): array
    {
        $pending = FortuneReading::STATUS_PENDING_PAYMENT;

        return [
            'paid' => [$this->makeReading(['is_paid' => true, 'amount_paid' => 39.42, 'paid_at' => now()->subMinutes(5)]), 'paid'],
            'paid_celtic_line' => [$this->makeReading([
                'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
                'platform' => 'line',
                'platform_user_id' => 'U1234567890abcdef1234567890abcdef',
                'facebook_user_id' => 'U1234567890abcdef1234567890abcdef',
                'conversation_status' => FortuneReading::STATUS_CELTIC_PICKING,
                'is_paid' => true, 'amount_paid' => 99.17, 'paid_at' => now()->subMinutes(3),
            ]), 'paid'],
            'slip_sent' => [$this->makeReading([
                'conversation_status' => $pending, 'amount_paid' => 39.21, 'slip_received_at' => now()->subMinutes(20),
            ]), 'awaiting'],
            'transfer_reported' => [$this->makeReading([
                'conversation_status' => FortuneReading::STATUS_CELTIC_PENDING_PAYMENT,
                'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
                'amount_paid' => 99.33, 'transfer_reported' => true, 'transfer_reported_at' => now()->subMinutes(9),
            ]), 'awaiting'],
            'floating' => [$this->makeReading(['is_floating' => true, 'is_paid' => true, 'amount_paid' => 39.01, 'paid_at' => now()]), 'awaiting'],
            'unpaid_open' => [$this->makeReading(['conversation_status' => $pending, 'amount_paid' => 39.55]), 'unpaid'],
            'unpaid_stale' => [$this->makeReading([
                'conversation_status' => $pending, 'amount_paid' => 39.56, 'created_at' => now()->subDays(10),
            ]), 'closed'],
            'slip_on_stale_bill' => [$this->makeReading([
                'conversation_status' => $pending, 'amount_paid' => 39.57, 'created_at' => now()->subDays(10),
                'slip_received_at' => now()->subDays(9),
            ]), 'closed'],
            'cancelled_auto' => [$this->makeReading(['amount_paid' => 39.61], ['cancellation_reason' => 'auto_expired']), 'cancelled'],
            'rejected_in_app' => [$this->makeReading(['conversation_status' => 'cancelled', 'amount_paid' => 39.62]), 'cancelled'],
            'abandoned' => [$this->makeReading(['amount_paid' => 39.63]), 'closed'],
            'json_null_reason' => [$this->makeReading(['amount_paid' => 39.64], ['cancellation_reason' => null]), 'closed'],
            'refunded_via_api' => [$this->makeReading(['amount_paid' => 39.71, 'paid_at' => now()->subHour()]), 'refunded'],
            'approval_voided' => [$this->makeReading(['amount_paid' => 39.72], [
                'approval_voided' => true, 'cancellation_reason' => 'approval_voided',
            ]), 'refunded'],
            'juntra' => [$this->makeReading([
                'reading_type' => FortuneReading::READING_TYPE_JUNTRA, 'bill_reference' => 'JW-1001',
                'is_paid' => true, 'amount_paid' => 99, 'paid_at' => now(),
            ]), 'paid'],

            // ⬇️ ไม่ใช่บิล
            'menu_row' => [$this->makeReading(['conversation_status' => FortuneReading::STATUS_TIER_CHOICE]), null],
            'menu_left' => [$this->makeReading(), null],
            'free_card' => [$this->makeReading(['reading_type' => FortuneReading::READING_TYPE_FREE_CARD]), null],
        ];
    }

    public function test_bucket_sql_matches_php_and_every_bill_lands_in_exactly_one_bucket(): void
    {
        $bills = $this->seedBills();

        $billedIds = FortuneBillBuckets::billedScope(FortuneReading::query())->pluck('id')->all();

        foreach ($bills as $case => [$reading, $expected]) {
            $this->assertSame($expected !== null, in_array($reading->id, $billedIds, true), "billed scope: {$case}");

            if ($expected === null) {
                continue;
            }

            $this->assertSame($expected, FortuneBillBuckets::statusOf($reading->fresh()), "PHP bucket: {$case}");

            $hits = [];
            foreach (FortuneBillBuckets::STATUSES as $status) {
                $found = FortuneBillBuckets::applyStatus(FortuneReading::query()->whereKey($reading->id), $status)->exists();
                if ($found) {
                    $hits[] = $status;
                }
            }
            $this->assertSame([$expected], $hits, "SQL bucket: {$case}");
        }
    }

    public function test_list_and_stats_shape(): void
    {
        $bills = $this->seedBills();
        $this->actAs($this->makeAdmin());

        $sms = SmsPaymentNotification::create([
            'bank' => 'KBANK', 'type' => 'credit', 'amount' => 39.42, 'sms_timestamp' => now()->subMinutes(5),
            'device_id' => 'dev-1', 'nonce' => 'n-1', 'status' => 'matched', 'sender_or_receiver' => 'นาย ก',
        ]);
        $bills['paid'][0]->forceFill(['sms_notification_id' => $sms->id, 'questions' => ['งานใหม่จะผ่านไหม']])->save();

        $all = $this->getJson('/api/admin/fortune/bills?per_page=100')->assertOk();
        $this->assertSame(15, $all->json('data.total'));
        $this->assertSame(1, $all->json('data.current_page'));
        $ids = collect($all->json('data.data'))->pluck('id')->all();
        $this->assertNotContains($bills['menu_row'][0]->id, $ids);
        $this->assertNotContains($bills['free_card'][0]->id, $ids);

        $paid = collect($all->json('data.data'))->firstWhere('id', $bills['paid'][0]->id);
        $this->assertSame('paid', $paid['status']);
        $this->assertSame(39.42, $paid['amount_thb']);
        $this->assertTrue($paid['sms_match']['matched']);
        $this->assertSame('KBANK', $paid['sms_match']['bank']);
        $this->assertSame('งานใหม่จะผ่านไหม', $paid['question_preview']);
        $this->assertStringStartsWith('FTU-', $paid['bill_number']);
        $this->assertSame('facebook', $paid['platform']);
        foreach (['package', 'package_label', 'status_label', 'stage', 'customer_name', 'created_at', 'paid_at',
            'slip_image_url', 'slip', 'customer_prior_paid_count', 'actions'] as $field) {
            $this->assertArrayHasKey($field, $paid, $field);
        }

        // LINE: platform มาจากคอลัมน์ ไม่ใช่เดาจาก facebook_user_id
        $line = collect($all->json('data.data'))->firstWhere('id', $bills['paid_celtic_line'][0]->id);
        $this->assertSame('line', $line['platform']);
        $this->assertSame('celtic', $line['package']);

        $awaiting = $this->getJson('/api/admin/fortune/bills?status=awaiting')->assertOk();
        $this->assertSame(3, $awaiting->json('data.total'));
        $this->assertSame($bills['slip_sent'][0]->id, $awaiting->json('data.data.0.id'), 'รอนานสุดขึ้นก่อน');
        $this->assertSame('slip_received', $awaiting->json('data.data.0.status_reason'));
        $this->assertTrue($awaiting->json('data.data.0.actions.can_mark_paid'));

        $cancelled = $this->getJson('/api/admin/fortune/bills?status=cancelled')->assertOk();
        $this->assertEqualsCanonicalizing(
            [$bills['cancelled_auto'][0]->id, $bills['rejected_in_app'][0]->id],
            collect($cancelled->json('data.data'))->pluck('id')->all()
        );
        $this->assertSame('ยกเลิกโดยระบบ', collect($cancelled->json('data.data'))->firstWhere('id', $bills['cancelled_auto'][0]->id)['status_label']);

        $this->getJson('/api/admin/fortune/bills?platform=line')->assertOk()->assertJsonPath('data.total', 1);
        $this->getJson('/api/admin/fortune/bills?search='.$bills['paid'][0]->bill_reference)->assertOk()->assertJsonPath('data.total', 1);
        $this->getJson('/api/admin/fortune/bills?status=bogus')->assertStatus(422);

        $stats = $this->getJson('/api/admin/fortune/bills/stats')->assertOk();
        $this->assertSame([
            'awaiting' => 3, 'unpaid' => 1, 'paid' => 3, 'refunded' => 2, 'cancelled' => 2, 'closed' => 4, 'all' => 15,
        ], $stats->json('data.counts'));
        // จ่ายวันนี้ = paid + paid_celtic_line (บิลจันทราไม่นับ · บิลลอยนับเพราะเงินเข้าจริง)
        $this->assertSame(3, $stats->json('data.paid_today.count'));
        $this->assertSame(round(39.42 + 99.17 + 39.01, 2), $stats->json('data.paid_today.revenue_thb'));
    }

    public function test_prior_paid_count_and_slip_stream(): void
    {
        Storage::fake('local');
        $this->actAs($this->makeAdmin());

        $this->makeReading(['is_paid' => true, 'amount_paid' => 39, 'paid_at' => now()->subDays(3), 'created_at' => now()->subDays(3)]);
        $this->makeReading(['is_paid' => true, 'amount_paid' => 99, 'paid_at' => now()->subDays(2), 'created_at' => now()->subDays(2)]);
        Storage::disk('local')->put('fortune/slips/test.jpg', 'fake-jpeg-bytes');
        $bill = $this->makeReading([
            'conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT, 'amount_paid' => 39.11,
            'slip_received_at' => now()->subMinutes(2), 'slip_image_path' => 'fortune/slips/test.jpg',
        ]);

        $row = collect($this->getJson('/api/admin/fortune/bills?status=awaiting')->assertOk()->json('data.data'))->first();
        $this->assertSame($bill->id, $row['id']);
        $this->assertSame(2, $row['customer_prior_paid_count']);
        $this->assertTrue($row['slip']['image_requires_auth']);
        $this->assertStringEndsWith("/api/admin/fortune/bills/{$bill->id}/slip", $row['slip_image_url']);

        $res = $this->get("/api/admin/fortune/bills/{$bill->id}/slip")->assertOk();
        $this->assertStringContainsString('no-store', (string) $res->headers->get('Cache-Control'));

        $noSlip = $this->makeReading(['conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT, 'amount_paid' => 39.12]);
        $this->getJson("/api/admin/fortune/bills/{$noSlip->id}/slip")->assertStatus(404)->assertJsonPath('error_code', 'SLIP_NOT_FOUND');
    }
}
