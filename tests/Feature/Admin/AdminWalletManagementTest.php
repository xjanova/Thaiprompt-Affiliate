<?php

namespace Tests\Feature\Admin;

use App\Exceptions\WalletAdminException;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletAdminAdjustment;
use App\Models\WalletTransaction;
use App\Services\PaymentGatewayService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * 🛡️ แอดมินจัดการเงินในกระเป๋าผู้ใช้ (2026-11-04)
 *
 * กติกาที่ต้องเป็นจริง:
 * - เติม/หัก/คืนเงิน/ย้อนรายการ/โอน ต้องมีเหตุผล + ผ่านหน้าต่างยืนยัน (confirmed) + บันทึก audit (ใคร/ยอดก่อน-หลัง)
 * - โทเคนฟอร์มเดิมส่งซ้ำ = ไม่แตะเงินซ้ำ
 * - ยอดเปลี่ยนระหว่างยืนยัน (expected_balance ไม่ตรง) = ปฏิเสธ
 * - หักเกินยอด / ย้อนซ้ำ / ย้อนรายการโอนฝั่งเดียว = ปฏิเสธ
 * - แอดมินทั่วไปแตะกระเป๋าตัวเองไม่ได้
 * - service อ่านยอดจาก DB หลัง lock เสมอ (model เก่าในมือไม่ทำให้ยอดเพี้ยน)
 *
 * @group wallet
 */
class AdminWalletManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(bool $super = false): User
    {
        return User::factory()->create(['role' => 'admin', 'is_super_admin' => $super]);
    }

    private function walletFor(?User $owner = null, float $balance = 1000, string $status = 'active'): Wallet
    {
        $owner ??= User::factory()->create(['role' => 'user']);

        return Wallet::factory()->create(['user_id' => $owner->id, 'balance' => $balance, 'status' => $status])->fresh();
    }

    /** ข้อมูลฟอร์มเติม/หักที่ถูกต้อง */
    private function form(Wallet $wallet, array $overrides = []): array
    {
        return array_merge([
            'direction' => 'credit',
            'amount' => '150.50',
            'reason' => 'ชดเชยออเดอร์ที่ตัดเงินซ้ำ',
            'idempotency_key' => (string) Str::uuid(),
            'expected_balance' => (string) $wallet->balance,
            'confirmed' => '1',
        ], $overrides);
    }

    // ───────────────────────── เติม / หัก ─────────────────────────

    public function test_admin_can_credit_a_wallet_and_it_is_audited(): void
    {
        $admin = $this->admin();
        $wallet = $this->walletFor(balance: 1000);

        $this->actingAs($admin)
            ->post(route('admin.wallet.adjust-balance', $wallet->id), $this->form($wallet))
            ->assertRedirect(route('admin.wallet.show', $wallet->id))
            ->assertSessionHas('success');

        $this->assertEquals(1150.50, (float) $wallet->fresh()->balance);

        $audit = WalletAdminAdjustment::where('wallet_id', $wallet->id)->sole();
        $this->assertSame('credit', $audit->action);
        $this->assertSame($admin->id, $audit->admin_id);
        $this->assertSame($admin->name, $audit->admin_name);
        $this->assertEquals(1000.00, (float) $audit->balance_before);
        $this->assertEquals(1150.50, (float) $audit->balance_after);
        $this->assertSame('ชดเชยออเดอร์ที่ตัดเงินซ้ำ', $audit->reason);
        $this->assertNotNull($audit->wallet_transaction_id);

        $tx = WalletTransaction::findOrFail($audit->wallet_transaction_id);
        $this->assertSame('deposit', $tx->type);
        $this->assertSame('admin_adjustment', $tx->reference_type);
        $this->assertEquals(1150.50, (float) $tx->balance_after);
    }

    public function test_admin_can_debit_a_wallet(): void
    {
        $admin = $this->admin();
        $wallet = $this->walletFor(balance: 500);

        $this->actingAs($admin)
            ->post(route('admin.wallet.adjust-balance', $wallet->id), $this->form($wallet, ['direction' => 'debit', 'amount' => '200']))
            ->assertSessionHas('success');

        $this->assertEquals(300.0, (float) $wallet->fresh()->balance);
        $this->assertSame('debit', WalletAdminAdjustment::where('wallet_id', $wallet->id)->sole()->action);
    }

    public function test_debit_beyond_balance_is_refused_and_nothing_changes(): void
    {
        $admin = $this->admin();
        $wallet = $this->walletFor(balance: 100);

        $this->actingAs($admin)
            ->post(route('admin.wallet.adjust-balance', $wallet->id), $this->form($wallet, ['direction' => 'debit', 'amount' => '100.01']))
            ->assertSessionHas('error');

        $this->assertEquals(100.0, (float) $wallet->fresh()->balance);
        $this->assertSame(0, WalletAdminAdjustment::count());
        $this->assertSame(0, WalletTransaction::where('wallet_id', $wallet->id)->count());
    }

    public function test_resubmitting_the_same_form_token_applies_only_once(): void
    {
        $admin = $this->admin();
        $wallet = $this->walletFor(balance: 1000);
        $payload = $this->form($wallet, ['amount' => '100']);

        $this->actingAs($admin)->post(route('admin.wallet.adjust-balance', $wallet->id), $payload)->assertSessionHas('success');
        // กดซ้ำด้วยฟอร์มเดิม (expected_balance เดิมด้วย) → ต้องได้ "ทำไปแล้ว" ไม่ใช่ error และไม่เติมซ้ำ
        $this->actingAs($admin)->post(route('admin.wallet.adjust-balance', $wallet->id), $payload)->assertSessionHas('info');

        $this->assertEquals(1100.0, (float) $wallet->fresh()->balance);
        $this->assertSame(1, WalletAdminAdjustment::count());
        $this->assertSame(1, WalletTransaction::where('wallet_id', $wallet->id)->count());
    }

    public function test_balance_changed_since_confirmation_is_refused(): void
    {
        $admin = $this->admin();
        $wallet = $this->walletFor(balance: 1000);
        $payload = $this->form($wallet, ['expected_balance' => '999.00']);

        $this->actingAs($admin)
            ->post(route('admin.wallet.adjust-balance', $wallet->id), $payload)
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'ยอดเงินในกระเป๋าเปลี่ยนไป'));

        $this->assertEquals(1000.0, (float) $wallet->fresh()->balance);
        $this->assertSame(0, WalletAdminAdjustment::count());
    }

    public function test_missing_confirmation_reason_or_bad_amount_is_rejected(): void
    {
        $admin = $this->admin();
        $wallet = $this->walletFor(balance: 1000);

        foreach ([
            ['confirmed' => null],
            ['reason' => ''],
            ['amount' => '0'],
            ['amount' => '-50'],
            ['amount' => '10.555'],
            ['amount' => (string) (WalletService::ADMIN_MAX_AMOUNT + 1)],
            ['direction' => 'mint'],
            ['idempotency_key' => null],
        ] as $bad) {
            $this->actingAs($admin)
                ->post(route('admin.wallet.adjust-balance', $wallet->id), array_filter($this->form($wallet, $bad), fn ($v) => $v !== null))
                ->assertSessionHasErrors();
        }

        $this->assertEquals(1000.0, (float) $wallet->fresh()->balance);
        $this->assertSame(0, WalletAdminAdjustment::count());
    }

    public function test_regular_member_cannot_adjust_any_wallet(): void
    {
        $member = User::factory()->create(['role' => 'user']);
        $wallet = $this->walletFor(balance: 1000);

        $response = $this->actingAs($member)->post(route('admin.wallet.adjust-balance', $wallet->id), $this->form($wallet));

        $this->assertNotSame(200, $response->getStatusCode());
        $this->assertEquals(1000.0, (float) $wallet->fresh()->balance);
        $this->assertSame(0, WalletAdminAdjustment::count());
    }

    public function test_regular_admin_cannot_top_up_their_own_wallet_but_super_admin_can(): void
    {
        $admin = $this->admin();
        $own = $this->walletFor($admin, 0);

        $this->actingAs($admin)
            ->post(route('admin.wallet.adjust-balance', $own->id), $this->form($own))
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'ตัวเอง'));
        $this->assertEquals(0.0, (float) $own->fresh()->balance);

        $super = $this->admin(super: true);
        $superWallet = $this->walletFor($super, 0);
        $this->actingAs($super)
            ->post(route('admin.wallet.adjust-balance', $superWallet->id), $this->form($superWallet))
            ->assertSessionHas('success');
        $this->assertEquals(150.50, (float) $superWallet->fresh()->balance);
    }

    public function test_service_reads_the_locked_row_not_the_stale_model(): void
    {
        $admin = $this->admin();
        $wallet = $this->walletFor(balance: 100);
        $stale = Wallet::findOrFail($wallet->id); // ถือ model ไว้ตอนยอด 100

        // ระหว่างนั้นผู้ใช้ได้เงินเข้าอีก 50 (อีก request หนึ่ง)
        Wallet::whereKey($wallet->id)->update(['balance' => 150]);

        // หัก 120 จาก model เก่า — ถ้าอ่านจาก model จะได้ -20 (ปฏิเสธ) หรือเขียนทับเป็น -20
        $tx = app(WalletService::class)->adminAdjust($stale, 'debit', 120, 'ทดสอบ lock', $admin);

        $this->assertEquals(150.0, (float) $tx->balance_before);
        $this->assertEquals(30.0, (float) $tx->balance_after);
        $this->assertEquals(30.0, (float) $wallet->fresh()->balance);
    }

    // ───────────────────────── คืนเงิน ─────────────────────────

    public function test_refund_is_recorded_as_refund_and_audited(): void
    {
        $admin = $this->admin();
        $wallet = $this->walletFor(balance: 10);

        $this->actingAs($admin)
            ->post(route('admin.wallet.refund', $wallet->id), array_diff_key($this->form($wallet, ['amount' => '40']), ['direction' => 1]))
            ->assertSessionHas('success');

        $this->assertEquals(50.0, (float) $wallet->fresh()->balance);
        $audit = WalletAdminAdjustment::sole();
        $this->assertSame('refund', $audit->action);
        $this->assertSame('refund', $audit->walletTransaction->type);
    }

    // ───────────────────────── ย้อนรายการ ─────────────────────────

    private function rollbackForm(WalletTransaction $tx, array $overrides = []): array
    {
        return array_merge([
            'transaction_id' => $tx->id,
            'reason' => 'เติมผิดออเดอร์',
            'idempotency_key' => (string) Str::uuid(),
            'confirmed' => '1',
        ], $overrides);
    }

    public function test_rollback_reverses_once_and_refuses_a_second_time(): void
    {
        $admin = $this->admin();
        $wallet = $this->walletFor(balance: 0);
        $deposit = app(WalletService::class)->adminAdjust($wallet, 'credit', 300, 'เติมผิด', $admin);
        $this->assertEquals(300.0, (float) $wallet->fresh()->balance);

        $this->actingAs($admin)
            ->post(route('admin.wallet.rollback-transaction', $wallet->id), $this->rollbackForm($deposit))
            ->assertSessionHas('success');
        $this->assertEquals(0.0, (float) $wallet->fresh()->balance);

        // ย้อนซ้ำด้วยโทเคนใหม่ → ปฏิเสธ
        $this->actingAs($admin)
            ->post(route('admin.wallet.rollback-transaction', $wallet->id), $this->rollbackForm($deposit))
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'ถูกย้อนไปแล้ว'));
        $this->assertEquals(0.0, (float) $wallet->fresh()->balance);

        $rollbackAudit = WalletAdminAdjustment::where('action', 'rollback')->sole();
        $this->assertSame($deposit->id, $rollbackAudit->original_transaction_id);
    }

    public function test_rollback_of_a_debit_gives_the_money_back(): void
    {
        $admin = $this->admin();
        $wallet = $this->walletFor(balance: 500);
        $debit = app(WalletService::class)->adminAdjust($wallet, 'debit', 200, 'หักผิด', $admin);

        $this->actingAs($admin)
            ->post(route('admin.wallet.rollback-transaction', $wallet->id), $this->rollbackForm($debit))
            ->assertSessionHas('success');

        $this->assertEquals(500.0, (float) $wallet->fresh()->balance);
    }

    public function test_rollback_of_transfer_or_of_another_wallets_transaction_is_refused(): void
    {
        $admin = $this->admin();
        $from = $this->walletFor(balance: 500);
        $to = $this->walletFor(balance: 0);
        $result = app(WalletService::class)->adminTransfer($from, $to, 100, 'ย้ายเงิน', $admin);

        // ย้อนรายการโอนฝั่งเดียว → ปฏิเสธ
        $this->actingAs($admin)
            ->post(route('admin.wallet.rollback-transaction', $from->id), $this->rollbackForm($result['out_transaction']))
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'รายการโอน'));

        // รายการของกระเป๋าอื่น ส่งมาที่ URL ของกระเป๋านี้ → ปฏิเสธ
        $this->actingAs($admin)
            ->post(route('admin.wallet.rollback-transaction', $from->id), $this->rollbackForm($result['in_transaction']))
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'ไม่ได้อยู่ในกระเป๋าใบนี้'));

        $this->assertEquals(400.0, (float) $from->fresh()->balance);
        $this->assertEquals(100.0, (float) $to->fresh()->balance);
    }

    // ───────────────────────── โอนโดยแอดมิน ─────────────────────────

    public function test_admin_transfer_moves_money_once_and_audits_both_sides(): void
    {
        $admin = $this->admin();
        $from = $this->walletFor(balance: 1000);
        $to = $this->walletFor(balance: 5);
        $payload = array_merge(array_diff_key($this->form($from, ['amount' => '250']), ['direction' => 1]), [
            'from_wallet_id' => $from->id,
            'to_wallet_id' => $to->id,
        ]);

        $this->actingAs($admin)->post(route('admin.wallet.admin-transfer'), $payload)->assertSessionHas('success');
        $this->actingAs($admin)->post(route('admin.wallet.admin-transfer'), $payload)->assertSessionHas('info');

        $this->assertEquals(750.0, (float) $from->fresh()->balance);
        $this->assertEquals(255.0, (float) $to->fresh()->balance);
        $this->assertEqualsCanonicalizing(['transfer_out', 'transfer_in'], WalletAdminAdjustment::pluck('action')->all());
    }

    public function test_admin_transfer_beyond_balance_changes_nothing(): void
    {
        $admin = $this->admin();
        $from = $this->walletFor(balance: 50);
        $to = $this->walletFor(balance: 0);

        $this->expectException(WalletAdminException::class);
        try {
            app(WalletService::class)->adminTransfer($from, $to, 60, 'เกินยอด', $admin);
        } finally {
            $this->assertEquals(50.0, (float) $from->fresh()->balance);
            $this->assertEquals(0.0, (float) $to->fresh()->balance);
            $this->assertSame(0, WalletAdminAdjustment::count());
        }
    }

    // ───────────────────────── หน้าจอ ─────────────────────────

    public function test_wallet_page_and_audit_page_render(): void
    {
        $admin = $this->admin(super: true);
        $wallet = $this->walletFor(balance: 100);
        app(WalletService::class)->adminAdjust($wallet, 'credit', 25, 'โบนัสทดสอบหน้าจอ', $admin);

        $this->actingAs($admin)->get(route('admin.wallet.show', $wallet->id))
            ->assertOk()
            ->assertSee('walletAdminActions', false)
            ->assertSee('ประวัติการปรับยอดโดยแอดมิน')
            ->assertSee('โบนัสทดสอบหน้าจอ');

        $this->actingAs($admin)->get(route('admin.wallet.adjustments', ['action' => 'credit']))
            ->assertOk()
            ->assertSee('โบนัสทดสอบหน้าจอ')
            ->assertSee($admin->name);

        $this->actingAs($admin)->getJson(route('admin.wallet.wallets-dropdown', ['q' => substr($wallet->wallet_address, 0, 8)]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $wallet->id);
    }

    // ───────────────────────── API แอปแอดมิน ─────────────────────────

    public function test_admin_app_adjust_honours_idempotency_key_header(): void
    {
        $super = $this->admin(super: true);
        $wallet = $this->walletFor(balance: 100);
        Sanctum::actingAs($super, ['admin']);
        $key = (string) Str::uuid();

        $this->postJson(route('api.admin.finance.wallets.adjust', $wallet->id), ['amount' => -40, 'reason' => 'หักผ่านแอป'], ['Idempotency-Key' => $key])
            ->assertOk()->assertJsonPath('data.replayed', false);
        $this->postJson(route('api.admin.finance.wallets.adjust', $wallet->id), ['amount' => -40, 'reason' => 'หักผ่านแอป'], ['Idempotency-Key' => $key])
            ->assertOk()->assertJsonPath('data.replayed', true);

        $this->assertEquals(60.0, (float) $wallet->fresh()->balance);

        // หักเกินยอด → 422 พร้อมข้อความไทย ไม่ใช่ 500 ข้อความดิบ
        $this->postJson(route('api.admin.finance.wallets.adjust', $wallet->id), ['amount' => -500, 'reason' => 'เกินยอด'])
            ->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_admin_app_lock_and_unlock_no_longer_crash(): void
    {
        $super = $this->admin(super: true);
        $wallet = $this->walletFor(balance: 0);
        Sanctum::actingAs($super, ['admin']);

        $this->postJson(route('api.admin.finance.wallets.lock', $wallet->id))->assertOk();
        $this->assertSame('locked', $wallet->fresh()->status);

        $this->postJson(route('api.admin.finance.wallets.unlock', $wallet->id))->assertOk();
        $this->assertSame('active', $wallet->fresh()->status);
    }

    // ───────────────────────── ช่องทางเติมเงินที่ยังไม่ตรวจยอดจริง ─────────────────────────

    public function test_unverified_stripe_and_paypal_wallet_deposits_never_credit(): void
    {
        $member = User::factory()->create(['role' => 'user']);
        $wallet = $this->walletFor($member, 0);
        $service = app(PaymentGatewayService::class);

        foreach ([fn () => $service->processStripePayment($member, 500, 'pm_fake'), fn () => $service->processPayPalPayment($member, 500, 'ORDER-FAKE')] as $call) {
            try {
                $call();
                $this->fail('ต้องปฏิเสธ');
            } catch (\Exception $e) {
                $this->assertStringContainsString('ยังไม่เปิดให้บริการ', $e->getMessage());
            }
        }

        $this->assertEquals(0.0, (float) $wallet->fresh()->balance);
    }
}
