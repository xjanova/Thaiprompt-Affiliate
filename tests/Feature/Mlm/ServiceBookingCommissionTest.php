<?php

namespace Tests\Feature\Mlm;

use App\Models\MlmCommission;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\MlmPvTransaction;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WalletDebt;
use App\Models\WalletTransaction;
use App\Services\DebtCollectionService;
use App\Services\ServiceBookingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Feature\Money\MoneyTestCase;

/**
 * จองบริการ จบงาน → จ่ายผู้ให้บริการ + คอม MLM ขั้น 2f (audit F20 · เจ้าของตัดสิน M3, M4, M7, M10)
 *
 * F20 มี 3 ชั้นที่บังกันอยู่ ต้องแก้พร้อมกัน:
 * 1. ส่ง User ไปที่ WalletService::deposit() (ต้องเป็น Wallet) → TypeError ผู้ให้บริการไม่ได้เงิน booking ค้าง in_progress
 * 2. increment คอลัมน์ personal_pv ที่ไม่มีจริง → คอม MLM ล้มเงียบ
 * 3. จบงานซ้ำไม่มี state guard → จ่ายผู้ให้บริการ/คอม 2 รอบ และ source_type ของคอมเป็น null (ตัวกันซ้ำมองไม่เห็น)
 *
 * ค่าตั้งเหมือน prod (unilevel 5/3/2, commission_per_pv 1, max 50%) ปิด rollup และเกณฑ์รักษายอด
 * สายงาน: U3 → U2 → U1 → buyer · ผู้ให้บริการ = ผู้ใช้ที่ไม่ใช่สมาชิก MLM
 */
class ServiceBookingCommissionTest extends MoneyTestCase
{
    protected MlmPlan $plan;

    /** @var array<string, MlmMember> */
    protected array $m = [];

    protected User $buyer;

    protected User $providerUser;

    protected int $providerId;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();

        foreach ([
            'mlm_enabled' => ['1', 'boolean'],
            'unilevel_enabled' => ['1', 'boolean'],
            'unilevel_levels' => [json_encode([
                ['level' => 1, 'percentage' => 5], ['level' => 2, 'percentage' => 3], ['level' => 3, 'percentage' => 2],
            ]), 'json'],
            'unilevel_max_depth' => ['10', 'integer'],
            'unilevel_max_commission_per_level' => [null, 'decimal'],
            'commission_per_pv' => ['1', 'decimal'],
            'max_commission_percentage' => ['50.00', 'decimal'],
            'rollup_enabled' => ['0', 'boolean'],
            'volume_retention_enabled' => ['0', 'boolean'],
            'binary_enabled' => ['0', 'boolean'],
        ] as $key => [$value, $type]) {
            DB::table('mlm_global_settings')->updateOrInsert(['key' => $key], ['value' => $value, 'type' => $type]);
        }
        Cache::flush();

        $this->plan = MlmPlan::create([
            'name' => 'Hybrid', 'name_th' => 'ไฮบริด', 'slug' => 'hybrid-'.Str::random(5),
            'type' => 'hybrid', 'is_active' => true, 'is_default' => true,
        ]);

        $prev = null;
        foreach (['U3', 'U2', 'U1', 'buyer'] as $tag) {
            $user = $this->makeUser('สมาชิก '.$tag, $tag === 'buyer' ? 5000 : 0);
            $this->m[$tag] = MlmMember::create([
                'user_id' => $user->id,
                'mlm_plan_id' => $this->plan->id,
                'member_code' => 'SB-'.$tag.'-'.Str::random(4),
                'original_sponsor_id' => $prev?->id,
                'unilevel_sponsor_id' => $prev?->id,
                'status' => 'active',
                'is_qualified' => true,
                'joined_at' => now()->subMonth(),
            ]);
            $prev = $this->m[$tag];
        }
        $this->buyer = User::find($this->m['buyer']->user_id);

        $this->providerUser = $this->makeUser('ช่างทดสอบ');
        $this->providerId = DB::table('service_providers')->insertGetId([
            'user_id' => $this->providerUser->id, 'owner_id' => $this->providerUser->id, 'name' => 'ช่างทดสอบ',
            'phone' => '0800000000', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** การจองที่จ่ายด้วยกระเป๋าแล้ว และอยู่ระหว่างให้บริการ */
    protected function paidBooking(float $pv, ?float $providerEarnings = null, array $overrides = []): ServiceBooking
    {
        $category = ServiceCategory::create(['name' => 'บริการทดสอบ', 'slug' => 'svc-'.Str::random(8)]);
        $service = Service::create([
            'category_id' => $category->id, 'owner_id' => $this->providerUser->id, 'name' => 'บริการ 1000',
            'slug' => 'service-'.Str::random(8), 'base_price' => 1000, 'requires_location' => false, 'pv_value' => $pv,
        ]);
        $booking = ServiceBooking::create([
            'user_id' => $this->buyer->id, 'service_id' => $service->id, 'scheduled_at' => now()->addDay(),
            'base_price' => 1000, 'subtotal' => 1000, 'total_amount' => 1000,
            'payment_method' => 'wallet', 'payment_status' => 'pending', 'status' => 'pending',
        ]);
        if ($providerEarnings !== null) {
            $booking->forceFill(['provider_id' => $this->providerId, 'provider_earnings' => $providerEarnings])->save();
        }

        app(ServiceBookingService::class)->processPayment($booking->fresh(), 'wallet');
        $booking->refresh()->forceFill(array_merge(['status' => 'in_progress'], $overrides))->save();

        return $booking->fresh();
    }

    protected function complete(ServiceBooking $booking): array
    {
        return app(ServiceBookingService::class)->completeService($booking->fresh());
    }

    protected function rows(ServiceBooking $booking)
    {
        return MlmCommission::where('source_type', ServiceBooking::class)->where('source_id', $booking->id)->get();
    }

    protected function providerIncome(): float
    {
        return round((float) WalletTransaction::where('user_id', $this->providerUser->id)
            ->where('reference_type', 'service_booking')->sum('amount'), 2);
    }

    // ------------------------------------------------------------------ ชั้น 1: TypeError

    /** E2E PaymentPaths 12c: ผู้ซื้อจ่าย 1,000 ผู้ให้บริการต้องได้ 850 และ booking ต้องจบงาน (เดิมได้ 0 ค้าง in_progress) */
    public function test_provider_is_paid_and_booking_completes(): void
    {
        $booking = $this->paidBooking(1000, 850);

        $result = $this->complete($booking);

        $this->assertTrue($result['success']);
        $this->assertSame('completed', $booking->fresh()->status);
        $this->assertEqualsWithDelta(850.00, $this->walletBalance($this->providerUser), 0.001);
        $this->assertEqualsWithDelta(850.00, $this->providerIncome(), 0.001, 'ต้องมี ledger ของเงินผู้ให้บริการ');
        $this->assertEqualsWithDelta(850.00, (float) DB::table('service_providers')->where('id', $this->providerId)->value('total_earnings'), 0.001);
        $this->assertEqualsWithDelta(4000.00, $this->walletBalance($this->buyer), 0.001);
    }

    // ------------------------------------------------------------------ ชั้น 2: คอลัมน์ไม่มี + source_type

    /** E2E PaymentPaths 12b: PV 1,000 → unilevel 50/30/20 แหล่งที่มาเป็น ServiceBooking และเงินกองเก็บเข้า mlm_pool */
    public function test_service_pv_pays_unilevel_with_service_booking_source(): void
    {
        $booking = $this->paidBooking(1000);

        $this->complete($booking);

        $rows = $this->rows($booking);
        $this->assertCount(3, $rows, 'ต้องได้ unilevel 3 ชั้น (เดิม 0 เพราะ personal_pv ไม่มีจริง)');
        $this->assertEqualsWithDelta(100.00, (float) $rows->sum('commission_amount'), 0.001);
        $this->assertSame(['pending'], $rows->pluck('status')->unique()->values()->all(), 'M10: รออนุมัติเอง');
        $this->assertSame(0, MlmCommission::whereNull('source_type')->count(), 'ห้ามมีแถวคอมที่ source_type เป็น null');

        $buyerMember = $this->m['buyer']->fresh();
        $this->assertEqualsWithDelta(1000.00, (float) $buyerMember->total_pv, 0.001);
        $pv = MlmPvTransaction::where('mlm_member_id', $buyerMember->id)->get();
        $this->assertCount(1, $pv);
        $this->assertSame('purchase', $pv->first()->transaction_type);

        // budget = min(PV × 1 × 50% = 500, เงินที่แพลตฟอร์มเก็บไว้จริง 1,000) → เก็บเข้ากอง 500
        $this->assertEqualsWithDelta(500.00, $this->platformBalance('mlm_pool'), 0.001);
        $this->assertSame((int) $buyerMember->id, (int) $booking->fresh()->mlm_member_id);
        $this->assertEqualsWithDelta(100.00, (float) $booking->fresh()->commission_amount, 0.001);
    }

    /** E2E PaymentPaths 12a: services.pv_value = 0 → ไม่มี PV/คอม (M3 — เดิมคอมเมนต์บอกว่าใช้ total_amount แต่ไม่มีเงินกองรองรับ) */
    public function test_zero_pv_service_earns_no_commission(): void
    {
        $booking = $this->paidBooking(0);

        $this->complete($booking);

        $this->assertCount(0, $this->rows($booking));
        $this->assertEqualsWithDelta(0.00, (float) $this->m['buyer']->fresh()->total_pv, 0.001);
        $this->assertSame(0, MlmPvTransaction::count());
        $this->assertEqualsWithDelta(0.00, $this->platformBalance('mlm_pool'), 0.001);
        $this->assertSame('completed', $booking->fresh()->status);
    }

    /** budget ต้องไม่เกินเงินที่แพลตฟอร์มเก็บไว้จริงหลังจ่ายผู้ให้บริการ (1,000 − 950 = 50) */
    public function test_commissions_are_capped_by_money_the_platform_retained(): void
    {
        $booking = $this->paidBooking(1000, 950);

        $this->complete($booking);

        $this->assertEqualsWithDelta(950.00, $this->walletBalance($this->providerUser), 0.001);
        $total = (float) $this->rows($booking)->sum('commission_amount');
        $this->assertEqualsWithDelta(50.00, $total, 0.001, 'คอมรวมต้องเท่ากับ 50 ที่แพลตฟอร์มเก็บไว้ (ไม่เกิน)');
        $this->assertEqualsWithDelta(50.00, $this->platformBalance('mlm_pool'), 0.001);
    }

    // ------------------------------------------------------------------ ชั้น 3: state guard / จบงานซ้ำ

    /** E2E PaymentPaths 12 (completeService ซ้ำ): จ่ายผู้ให้บริการ คอม PV และเงินกองครั้งเดียว */
    public function test_completing_twice_pays_everything_once(): void
    {
        $booking = $this->paidBooking(1000, 850);

        $this->complete($booking);
        $second = $this->complete($booking);

        $this->assertTrue($second['already_completed'] ?? false, 'ครั้งที่ 2 ต้องเป็น no-op');
        $this->assertEqualsWithDelta(850.00, $this->walletBalance($this->providerUser), 0.001);
        $this->assertSame(1, WalletTransaction::where('user_id', $this->providerUser->id)->count());
        $this->assertCount(3, $this->rows($booking));
        $this->assertEqualsWithDelta(1000.00, (float) $this->m['buyer']->fresh()->total_pv, 0.001);
        $this->assertSame(1, MlmPvTransaction::count());
        // budget = min(500, 1,000 − 850 = 150) = 150 เก็บเข้ากองครั้งเดียว
        $this->assertEqualsWithDelta(150.00, $this->platformBalance('mlm_pool'), 0.001);
        $this->assertSame(1, (int) DB::table('service_providers')->where('id', $this->providerId)->value('total_bookings'));
    }

    /** booking ที่ยังไม่จ่ายเงิน จบงานไม่ได้ และไม่มีเงินออก */
    public function test_unpaid_booking_cannot_be_completed(): void
    {
        $category = ServiceCategory::create(['name' => 'บริการทดสอบ', 'slug' => 'svc-'.Str::random(8)]);
        $service = Service::create([
            'category_id' => $category->id, 'owner_id' => $this->providerUser->id, 'name' => 'บริการ 1000',
            'slug' => 'service-'.Str::random(8), 'base_price' => 1000, 'requires_location' => false, 'pv_value' => 1000,
        ]);
        $booking = ServiceBooking::create([
            'user_id' => $this->buyer->id, 'service_id' => $service->id, 'scheduled_at' => now()->addDay(),
            'base_price' => 1000, 'subtotal' => 1000, 'total_amount' => 1000, 'provider_id' => $this->providerId,
            'provider_earnings' => 850, 'payment_method' => 'wallet', 'payment_status' => 'pending', 'status' => 'in_progress',
        ]);

        try {
            $this->complete($booking);
            $this->fail('ต้องจบงานไม่ได้เมื่อยังไม่จ่ายเงิน');
        } catch (\Exception $e) {
            $this->assertStringContainsString('ชำระ', $e->getMessage());
        }

        $this->assertSame('in_progress', $booking->fresh()->status);
        $this->assertEqualsWithDelta(0.00, $this->walletBalance($this->providerUser), 0.001);
        $this->assertCount(0, $this->rows($booking));
    }

    /** booking ที่ยกเลิกแล้ว (แม้ payment_status ยังเป็น paid) จบงานไม่ได้ */
    public function test_cancelled_booking_cannot_be_completed(): void
    {
        $booking = $this->paidBooking(1000, 850, ['status' => 'cancelled']);

        try {
            $this->complete($booking);
            $this->fail('ต้องจบงานไม่ได้เมื่อยกเลิกแล้ว');
        } catch (\Exception $e) {
            $this->assertNotSame('', $e->getMessage());
        }

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertEqualsWithDelta(0.00, $this->walletBalance($this->providerUser), 0.001);
        $this->assertCount(0, $this->rows($booking));
    }

    // ------------------------------------------------------------------ M7

    /** M7: ผู้ให้บริการมีหนี้ค้าง → หักจากรายได้งานนี้ กระเป๋าไม่ติดลบ */
    public function test_provider_income_collects_active_debt_first(): void
    {
        (new DebtCollectionService)->createManualDebt($this->providerUser->id, 100, 'หนี้ทดสอบ', $this->providerUser->id);
        $booking = $this->paidBooking(1000, 850);

        $this->complete($booking);

        $this->assertEqualsWithDelta(750.00, $this->walletBalance($this->providerUser), 0.001);
        $this->assertEqualsWithDelta(0.00, (float) WalletDebt::getTotalDebtForUser($this->providerUser->id), 0.001);
    }
}
