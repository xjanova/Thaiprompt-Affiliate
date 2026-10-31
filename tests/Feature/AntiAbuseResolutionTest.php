<?php

namespace Tests\Feature;

use App\Models\ServiceBookingDispute;
use App\Models\ServiceBookingPenalty;
use App\Models\User;
use App\Models\UserTrustScore;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * การตัดสินข้อร้องเรียนและการหักค่าปรับ
 *
 * จุดนี้ขยับเงินกับคะแนนความน่าเชื่อถือ จึงต้องเกิดครั้งเดียว และล้มแล้วต้องไม่เหลือครึ่งทาง
 */
class AntiAbuseResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => 'admin', 'is_super_admin' => true]));
    }

    private function makeBooking(User $customer): int
    {
        $categoryId = DB::table('service_categories')->insertGetId([
            'name' => 'ทำความสะอาด', 'slug' => 'cleaning-'.Str::random(6), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $serviceId = DB::table('services')->insertGetId([
            'category_id' => $categoryId, 'owner_id' => User::factory()->create()->id, 'name' => 'ทำความสะอาดบ้าน',
            'slug' => 'home-'.Str::random(6), 'created_at' => now(), 'updated_at' => now(),
        ]);

        return DB::table('service_bookings')->insertGetId([
            'booking_number' => 'SB-'.Str::random(8),
            'user_id' => $customer->id,
            'service_id' => $serviceId,
            'scheduled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeDispute(User $accused, array $attributes = []): ServiceBookingDispute
    {
        $reporter = User::factory()->create();

        return ServiceBookingDispute::create(array_merge([
            'dispute_number' => 'DSP-'.Str::random(8),
            'service_booking_id' => $this->makeBooking($reporter),
            'reporter_user_id' => $reporter->id,
            'reporter_type' => 'provider',
            'accused_type' => 'user',
            'accused_user_id' => $accused->id,
            'dispute_type' => 'no_show',
            'title' => 'ลูกค้าไม่อยู่ตามนัด',
            'description' => 'รอตั้งแต่เช้าแต่ไม่มีใครเปิดประตู',
            'status' => 'pending',
            'priority' => 'medium',
        ], $attributes));
    }

    private function makePenalty(User $customer, array $attributes = []): ServiceBookingPenalty
    {
        return ServiceBookingPenalty::create(array_merge([
            'service_booking_id' => $this->makeBooking($customer),
            'user_id' => $customer->id,
            'penalized_type' => 'user',
            'penalty_type' => 'no_show',
            'penalty_amount' => 150,
            'status' => 'pending',
            'reason' => 'ลูกค้าไม่อยู่ตามนัด',
        ], $attributes));
    }

    private function resolve(ServiceBookingDispute $dispute, array $data)
    {
        return $this->post(route('admin.anti-abuse.disputes.resolve', $dispute), $data);
    }

    /**
     * ฟอร์มในหน้าตัดสินต้องส่งไป resolve พร้อมชื่อฟิลด์ที่ resolveDispute รับจริง
     */
    public function test_the_modal_posts_to_the_resolve_route(): void
    {
        $response = $this->get(route('admin.anti-abuse.disputes.show', $this->makeDispute(User::factory()->create())));

        $response->assertOk()
            ->assertSee('name="resolution"', false)
            ->assertSee('value="favor_reporter"', false)
            ->assertSee('value="favor_accused"', false)
            ->assertSee('value="mutual"', false)
            ->assertDontSee('value="resolved_favor_reporter"', false);
    }

    /**
     * ไม่มีเหตุผลการตัดสิน ต้องตัดสินไม่ได้ และหน้าเดิมต้องเห็นข้อความเป็นภาษาไทย
     */
    public function test_resolving_requires_notes_and_shows_the_error_on_the_page(): void
    {
        $dispute = $this->makeDispute(User::factory()->create());

        $this->from(route('admin.anti-abuse.disputes.show', $dispute))
            ->followingRedirects()
            ->post(route('admin.anti-abuse.disputes.resolve', $dispute), ['resolution' => 'dismissed'])
            ->assertOk()
            ->assertSee('กรุณาบันทึกเหตุผลการตัดสิน');

        $this->assertSame('pending', $dispute->fresh()->status);
    }

    /**
     * กดตัดสินสองครั้งต้องได้ค่าปรับและการหักคะแนนครั้งเดียว ครั้งที่สองต้องบอกว่าตัดสินไปแล้ว
     */
    public function test_resolving_twice_creates_one_penalty_and_one_trust_deduction(): void
    {
        $accused = User::factory()->create();
        $dispute = $this->makeDispute($accused);
        $data = ['resolution' => 'favor_reporter', 'resolution_notes' => 'มีหลักฐานชัดเจน', 'penalty_amount' => 300];

        $this->resolve($dispute, $data)->assertSessionHas('success');
        $this->resolve($dispute, ['penalty_amount' => 900] + $data)->assertSessionHas('error', 'ข้อร้องเรียนนี้ถูกตัดสินไปแล้ว');

        $this->assertSame(1, ServiceBookingPenalty::where('service_booking_id', $dispute->service_booking_id)->count());
        $this->assertSame(1, (int) UserTrustScore::getOrCreateForUser($accused)->dispute_lost_count);
        $this->assertSame('300.00', (string) $dispute->fresh()->penalty_amount);
    }

    /**
     * ผลที่ผู้ถูกร้องไม่ได้แพ้ ต้องไม่บันทึกค่าปรับหรือการแบนที่ไม่ได้เกิดจริง
     */
    public function test_only_a_lost_dispute_records_a_penalty_and_a_ban(): void
    {
        foreach (['favor_accused', 'mutual', 'dismissed'] as $resolution) {
            $dispute = $this->makeDispute(User::factory()->create());

            $this->resolve($dispute, [
                'resolution' => $resolution,
                'resolution_notes' => 'ตรวจสอบแล้ว',
                'penalty_amount' => 300,
                'resulted_in_ban' => 1,
            ])->assertSessionHas('success');

            $fresh = $dispute->fresh();
            $this->assertSame('0.00', (string) $fresh->penalty_amount);
            $this->assertFalse($fresh->resulted_in_ban);
            $this->assertSame(0, ServiceBookingPenalty::where('service_booking_id', $dispute->service_booking_id)->count());
        }
    }

    /**
     * เส้นสถานะด่วนต้องใช้กับสถานะระหว่างสอบสวนเท่านั้น ห้ามปิดเคสหรือล้างยอดเงิน
     */
    public function test_the_status_route_refuses_final_statuses_and_keeps_money_fields(): void
    {
        $dispute = $this->makeDispute(User::factory()->create(), ['refund_amount' => 500, 'penalty_amount' => 100]);
        $url = route('admin.anti-abuse.disputes.status', $dispute);

        foreach (['resolved_favor_reporter', 'dismissed', 'escalated'] as $status) {
            $this->post($url, ['status' => $status])->assertSessionHasErrors('status');
        }

        $this->post($url, ['status' => 'investigating'])->assertSessionHas('success');

        $fresh = $dispute->fresh();
        $this->assertSame('investigating', $fresh->status);
        $this->assertSame('500.00', (string) $fresh->refund_amount);
        $this->assertSame('100.00', (string) $fresh->penalty_amount);
    }

    /**
     * ข้อร้องเรียนที่ตัดสินแล้วต้องเปิดกลับด้วยเส้นสถานะด่วนไม่ได้
     */
    public function test_the_status_route_cannot_reopen_a_closed_dispute(): void
    {
        $dispute = $this->makeDispute(User::factory()->create(), ['status' => 'resolved_favor_reporter', 'resolved_at' => now()]);

        $this->post(route('admin.anti-abuse.disputes.status', $dispute), ['status' => 'investigating'])
            ->assertSessionHas('error', 'ข้อร้องเรียนนี้ถูกตัดสินไปแล้ว');

        $this->assertSame('resolved_favor_reporter', $dispute->fresh()->status);
    }

    /**
     * หักค่าปรับต้องตัดกระเป๋าจริง และกดซ้ำต้องตัดครั้งเดียว
     */
    public function test_charging_debits_the_wallet_once(): void
    {
        $customer = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $customer->id, 'balance' => 500]);
        $penalty = $this->makePenalty($customer);
        $url = route('admin.anti-abuse.penalties.charge', $penalty);

        $this->post($url)->assertSessionHas('success');
        $this->post($url)->assertSessionHas('error');

        $this->assertSame(350.0, (float) $wallet->fresh()->balance);

        $transactions = WalletTransaction::where('reference_type', 'service_booking_penalty')
            ->where('reference_id', $penalty->id)
            ->get();

        $this->assertCount(1, $transactions);
        $this->assertSame('fee', $transactions->first()->type);

        $fresh = $penalty->fresh();
        $this->assertSame('charged', $fresh->status);
        $this->assertSame('wallet', $fresh->payment_method);
        $this->assertSame((string) $transactions->first()->id, $fresh->transaction_id);
    }

    /**
     * ยอดไม่พอต้องคงสถานะ pending และไม่มีรายการธุรกรรมค้าง
     */
    public function test_insufficient_balance_leaves_the_penalty_pending(): void
    {
        $customer = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $customer->id, 'balance' => 100]);
        $penalty = $this->makePenalty($customer);

        $this->post(route('admin.anti-abuse.penalties.charge', $penalty))
            ->assertSessionHas('error', 'ยอดเงินในกระเป๋าของผู้ถูกปรับไม่พอ');

        $this->assertSame('pending', $penalty->fresh()->status);
        $this->assertSame(100.0, (float) $wallet->fresh()->balance);
        $this->assertSame(0, WalletTransaction::where('reference_id', $penalty->id)->where('reference_type', 'service_booking_penalty')->count());
    }

    /**
     * ไม่มีกระเป๋าหรือกระเป๋าถูกระงับ ต้องไม่บันทึกว่าหักแล้ว
     */
    public function test_a_missing_or_suspended_wallet_leaves_the_penalty_pending(): void
    {
        $withoutWallet = $this->makePenalty(User::factory()->create());

        $this->post(route('admin.anti-abuse.penalties.charge', $withoutWallet))
            ->assertSessionHas('error', 'ผู้ถูกปรับยังไม่มีกระเป๋าเงิน');

        $this->assertSame('pending', $withoutWallet->fresh()->status);

        $customer = User::factory()->create();
        Wallet::factory()->create(['user_id' => $customer->id, 'balance' => 500, 'status' => 'suspended']);
        $suspended = $this->makePenalty($customer);

        $this->post(route('admin.anti-abuse.penalties.charge', $suspended))
            ->assertSessionHas('error', 'กระเป๋าเงินของผู้ถูกปรับถูกระงับอยู่');

        $this->assertSame('pending', $suspended->fresh()->status);
    }

    /**
     * ค่าปรับของผู้ให้บริการต้องตัดจากกระเป๋าของบัญชีผู้ให้บริการ ไม่ใช่ลูกค้าในการจอง
     */
    public function test_a_provider_penalty_debits_the_provider_account_wallet(): void
    {
        $customer = User::factory()->create();
        $customerWallet = Wallet::factory()->create(['user_id' => $customer->id, 'balance' => 500]);

        $providerUser = User::factory()->create();
        $providerWallet = Wallet::factory()->create(['user_id' => $providerUser->id, 'balance' => 500]);

        $providerId = DB::table('service_providers')->insertGetId([
            'user_id' => $providerUser->id,
            'owner_id' => User::factory()->create()->id,
            'name' => 'ช่างสมชาย',
            'phone' => '0800000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $penalty = $this->makePenalty($customer, [
            'user_id' => null,
            'provider_id' => $providerId,
            'penalized_type' => 'provider',
        ]);

        $this->post(route('admin.anti-abuse.penalties.charge', $penalty))->assertSessionHas('success');

        $this->assertSame(350.0, (float) $providerWallet->fresh()->balance);
        $this->assertSame(500.0, (float) $customerWallet->fresh()->balance);
        $this->assertSame('charged', $penalty->fresh()->status);
    }
}
