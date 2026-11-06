<?php

namespace Tests\Feature\Admin;

use App\Models\PayoutSetting;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\WalletService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * หน้ารายได้แพลตฟอร์มหลังถอดระบบ payout_requests ที่ใช้งานไม่ได้ออก (2026-11-06)
 *
 * ตรวจ:
 *  - แผง "คำขอถอนเงินรออนุมัติ" อ่านจาก withdrawal_requests (ระบบถอนเงินจริง) ไม่ใช่ตารางว่าง
 *  - หน้าตั้งค่าพักเงินผู้ขาย + Earnings Ledger ยังเปิดได้
 *  - route / คำสั่งตั้งเวลาของ payout_requests หายไปแล้ว
 * ต้องใช้ MySQL (RefreshDatabase)
 */
class PlatformRevenueWithdrawalPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Http::fake();
        Cache::flush();

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    /**
     * สร้างคำขอถอนเงินแบบตรงตาราง (ไม่ผ่าน WithdrawalService เพื่อไม่ต้องตั้ง KYC/PIN)
     */
    private function makeWithdrawal(string $status, float $amount, float $fee = 10.0): WithdrawalRequest
    {
        $user = User::factory()->create();
        $wallet = app(WalletService::class)->getOrCreateWallet($user);

        $data = [
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'request_id' => 'WD-TEST-'.$status.'-'.$user->id,
            'amount' => $amount,
            'fee' => $fee,
            'net_amount' => $amount - $fee,
            'status' => $status,
        ];
        if ($status === 'completed') {
            $data['transfer_completed_at'] = now();
        }

        return WithdrawalRequest::forceCreate($data);
    }

    public function test_dashboard_lists_real_pending_withdrawals(): void
    {
        $pending = $this->makeWithdrawal('pending', 500);
        $this->makeWithdrawal('completed', 300);

        $response = $this->actingAs($this->admin)->get(route('admin.platform-revenue.index'));

        $response->assertOk();
        $response->assertSee('คำขอถอนเงินรออนุมัติ');
        $response->assertSee($pending->request_id);
        $response->assertSee(route('admin.withdrawals.show', $pending), false);
        // รอดำเนินการ 1 รายการ ยอดสุทธิ 490
        $response->assertSee('รอดำเนินการ: 1 รายการ');
        $response->assertSee('฿490.00');
    }

    public function test_dashboard_empty_state(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.platform-revenue.index'));

        $response->assertOk();
        $response->assertSee('ไม่มีคำขอถอนเงินรออนุมัติ');
    }

    public function test_api_stats_counts_pending_withdrawals(): void
    {
        $this->makeWithdrawal('pending', 200);
        $this->makeWithdrawal('pending', 100);
        $this->makeWithdrawal('rejected', 100);

        $this->actingAs($this->admin)
            ->getJson(route('admin.platform-revenue.api.stats'))
            ->assertOk()
            ->assertJsonPath('data.pending_payouts', 2);
    }

    public function test_settings_and_earnings_pages_still_open(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.platform-revenue.payouts.settings'))
            ->assertOk()
            ->assertSee(route('admin.platform-revenue.index'), false);

        $this->actingAs($this->admin)
            ->get(route('admin.platform-revenue.earnings.index'))
            ->assertOk();
    }

    public function test_settings_update_still_saves_holding_days(): void
    {
        $setting = PayoutSetting::first() ?? PayoutSetting::forceCreate([
            'earning_type' => 'seller_sale',
            'payout_mode' => 'manual',
            'min_payout' => 100,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.platform-revenue.payouts.settings.update', $setting), [
                'payout_mode' => 'manual',
                'min_payout' => 100,
                'holding_days' => 9,
                'requires_approval' => true,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.platform-revenue.payouts.settings'));

        $this->assertSame(9, (int) $setting->fresh()->holding_days);
    }

    public function test_dead_payout_request_routes_and_schedule_are_gone(): void
    {
        foreach (['index', 'show', 'approve', 'reject', 'process', 'bulk-approve', 'api.pending'] as $name) {
            $this->assertFalse(Route::has('admin.platform-revenue.payouts.'.$name), "route payouts.{$name} ควรถูกลบแล้ว");
        }
        $this->assertFalse(Route::has('admin.platform-revenue.earnings.show'));

        $commands = collect(app(Schedule::class)->events())->pluck('command')->implode("\n");
        $this->assertStringNotContainsString('payouts:process-scheduled', $commands);
        // เช็คไฟล์แทน class_exists — classmap ของ composer ที่ยังไม่ dump ใหม่จะ include ไฟล์ที่ลบไปแล้ว
        $this->assertFileDoesNotExist(app_path('Services/PayoutService.php'));
        $this->assertFileDoesNotExist(app_path('Models/PayoutRequest.php'));
        $this->assertFileDoesNotExist(app_path('Console/Commands/ProcessScheduledPayouts.php'));
    }
}
