<?php

namespace Tests\Feature\RiderR2;

use App\Models\Rider;
use App\Models\User;
use App\Services\DemoDataUserGuard;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * 🛡️ K1-4 (audit 2026-10-04): demo:reset / หน้าแอดมิน "ข้อมูลทดสอบ" ต้องไม่ลบบัญชีจริง
 *
 * เดิม --users ลบทุกอีเมล @example.com / @thaiprompt.com (รวมร้านทางการ + superadmin + admin)
 * และ --kyc ลบ KYC ทุกสถานะ (= ของลูกค้าจริงทั้งหมด)
 */
#[Group('rider-r2')]
class ResetDemoDataSafetyTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    /** @var array<string, User> */
    private array $u = [];

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Queue::fake();
        Notification::fake();
        Cache::flush();

        $this->seedAccounts();
    }

    public function test_users_reset_keeps_official_admin_seller_rider_and_paying_users(): void
    {
        $this->assertSame(0, Artisan::call('demo:reset', ['--users' => true, '--force' => true]));

        foreach (['official', 'superadmin', 'admin', 'manager', 'roleAdmin', 'storeOwner', 'rider', 'paidBuyer', 'walletRich', 'walletHistory', 'sponsorOfReal', 'real'] as $key) {
            $this->assertDatabaseHas('users', ['id' => $this->u[$key]->id]);
        }

        foreach (['demoPlain', 'demoSponsor'] as $key) {
            $this->assertDatabaseMissing('users', ['id' => $this->u[$key]->id]);
        }

        // ร้าน/ไรเดอร์/ออเดอร์ที่จ่ายแล้วยังอยู่ครบ
        $this->assertDatabaseHas('vendor_stores', ['user_id' => $this->u['storeOwner']->id]);
        $this->assertDatabaseHas('riders', ['user_id' => $this->u['rider']->id]);
        $this->assertDatabaseHas('orders', ['user_id' => $this->u['paidBuyer']->id, 'payment_status' => 'paid']);

        // สายงานของผู้ใช้จริงไม่ขาด
        $this->assertSame($this->u['sponsorOfReal']->id, (int) DB::table('users')->where('id', $this->u['realChild']->id)->value('sponsor_id'));
    }

    public function test_dry_run_lists_plan_and_deletes_nothing(): void
    {
        $before = DB::table('users')->count();

        $this->assertSame(0, Artisan::call('demo:reset', ['--dry-run' => true]));
        $output = Artisan::output();

        $this->assertSame($before, DB::table('users')->count());
        $this->assertStringContainsString('demo-plain@example.com', $output);
        $this->assertStringContainsString('official-shop@thaiprompt.com', $output);
        $this->assertStringContainsString('บัญชีร้านค้าทางการ', $output);

        $plan = app(DemoDataUserGuard::class)->plan();
        $deletable = array_column($plan['deletable'], 'email');
        sort($deletable);
        $this->assertSame(['demo-plain@example.com', 'demo-sponsor@thaiprompt.com'], $deletable);

        $protected = collect($plan['protected'])->keyBy('email');
        $this->assertContains('official_shop', $protected['official-shop@thaiprompt.com']['reasons']);
        $this->assertContains('admin', $protected['superadmin@thaiprompt.com']['reasons']);
        $this->assertContains('admin', $protected['staff-role@example.com']['reasons']);
        $this->assertContains('store_owner', $protected['store-owner@example.com']['reasons']);
        $this->assertContains('rider', $protected['rider@example.com']['reasons']);
        $this->assertContains('paid_orders', $protected['paid-buyer@example.com']['reasons']);
        $this->assertContains('wallet_balance', $protected['rich@example.com']['reasons']);
        $this->assertContains('wallet_history', $protected['history@example.com']['reasons']);
        $this->assertContains('sponsor_of_kept_user', $protected['sponsor-of-real@example.com']['reasons']);
    }

    public function test_kyc_reset_only_touches_deletable_demo_users(): void
    {
        $this->assertSame(0, Artisan::call('demo:reset', ['--kyc' => true, '--force' => true]));

        $this->assertDatabaseMissing('kyc_verifications', ['user_id' => $this->u['demoPlain']->id]);
        $this->assertDatabaseHas('kyc_verifications', ['user_id' => $this->u['real']->id]);
        $this->assertDatabaseHas('kyc_verifications', ['user_id' => $this->u['storeOwner']->id]);
        // หมวด KYC ห้ามลบผู้ใช้
        $this->assertDatabaseHas('users', ['id' => $this->u['demoPlain']->id]);
    }

    public function test_production_requires_force_but_dry_run_is_allowed(): void
    {
        $this->app['env'] = 'production';
        $before = DB::table('users')->count();

        $this->assertSame(1, Artisan::call('demo:reset', ['--users' => true]));
        $this->assertSame(0, Artisan::call('demo:reset', ['--dry-run' => true]));
        $this->assertSame($before, DB::table('users')->count());
    }

    public function test_admin_page_shows_dry_run_and_clean_keeps_protected_accounts(): void
    {
        $this->actingAs($this->u['superadmin'])
            ->get(route('admin.demo-data.index'))
            ->assertOk()
            ->assertSee('ตรวจก่อนลบ')
            ->assertSee('demo-plain@example.com')
            ->assertSee('official-shop@thaiprompt.com')
            ->assertSee('บัญชีร้านค้าทางการ')
            ->assertDontSee('Super Admin ที่สร้างตอนติดตั้งจะ');

        $this->actingAs($this->u['superadmin'])
            ->post(route('admin.demo-data.clean'), ['category' => 'users'])
            ->assertRedirect(route('admin.demo-data.index'));

        $this->assertDatabaseHas('users', ['id' => $this->u['official']->id]);
        $this->assertDatabaseHas('users', ['id' => $this->u['superadmin']->id]);
        $this->assertDatabaseHas('users', ['id' => $this->u['storeOwner']->id]);
        $this->assertDatabaseHas('users', ['id' => $this->u['rider']->id]);
        $this->assertDatabaseHas('users', ['id' => $this->u['paidBuyer']->id]);
        $this->assertDatabaseHas('kyc_verifications', ['user_id' => $this->u['real']->id]);
        $this->assertDatabaseMissing('users', ['id' => $this->u['demoPlain']->id]);
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    private function seedAccounts(): void
    {
        $make = fn (string $email, array $extra = []) => User::factory()->create(array_merge(['email' => $email], $extra));

        // ต้องรอด
        $this->u['official'] = $make('official-shop@thaiprompt.com', ['role' => 'seller']);
        $this->u['superadmin'] = $make('superadmin@thaiprompt.com', ['role' => 'admin', 'is_super_admin' => true]);
        $this->u['admin'] = $make('admin@thaiprompt.com', ['role' => 'admin']);
        $this->u['manager'] = $make('manager@thaiprompt.com', ['role' => 'manager']);

        $roleId = DB::table('roles')->where('name', 'super_admin')->value('id')
            ?? DB::table('roles')->insertGetId([
                'name' => 'super_admin',
                'display_name' => 'Super Admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        $this->u['roleAdmin'] = $make('staff-role@example.com', ['role' => 'user', 'role_id' => $roleId]);

        [$seller] = $this->makeSellerWithStore();
        $seller->forceFill(['email' => 'store-owner@example.com'])->save();
        $this->u['storeOwner'] = $seller->fresh();

        $riderUser = $make('rider@example.com');
        (new Rider)->forceFill([
            'user_id' => $riderUser->id,
            'full_name' => 'ไรเดอร์ทดสอบ',
            'phone' => '0811111111',
            'status' => 'approved',
            'availability' => 'offline',
            'rider_type' => 'delivery',
            'vehicle_type' => 'motorcycle',
        ])->save();
        $this->u['rider'] = $riderUser;

        $this->u['paidBuyer'] = $make('paid-buyer@example.com');
        [$s2, $store2] = $this->makeSellerWithStore();
        $product = $this->makeProduct($s2, $store2, ['price' => 100]);
        $this->makeOrder($this->u['paidBuyer'], [['product' => $product, 'qty' => 1]], [
            'payment_status' => 'paid',
            'status' => 'processing',
        ]);

        $this->u['walletRich'] = $make('rich@example.com');
        app(WalletService::class)->getOrCreateWallet($this->u['walletRich'])->forceFill(['balance' => 50])->save();

        $this->u['walletHistory'] = $make('history@example.com');
        $wallet = app(WalletService::class)->getOrCreateWallet($this->u['walletHistory']);
        DB::table('wallet_transactions')->insert([
            'wallet_id' => $wallet->id,
            'user_id' => $this->u['walletHistory']->id,
            'transaction_id' => 'TXN-R2-HIST-1',
            'type' => 'deposit',
            'amount' => 10,
            'balance_before' => 0,
            'balance_after' => 10,
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->u['sponsorOfReal'] = $make('sponsor-of-real@example.com');
        $this->u['realChild'] = $make('child@gmail.test', ['sponsor_id' => $this->u['sponsorOfReal']->id]);

        $this->u['real'] = $make('real@gmail.test');

        // ลบได้: ผู้ใช้ทดสอบเปล่าๆ + ผู้แนะนำที่มีแต่ลูกทีมทดสอบ
        $this->u['demoSponsor'] = $make('demo-sponsor@thaiprompt.com');
        $this->u['demoPlain'] = $make('demo-plain@example.com', ['sponsor_id' => $this->u['demoSponsor']->id]);

        foreach (['demoPlain', 'real', 'storeOwner'] as $key) {
            DB::table('kyc_verifications')->insert([
                'user_id' => $this->u[$key]->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
