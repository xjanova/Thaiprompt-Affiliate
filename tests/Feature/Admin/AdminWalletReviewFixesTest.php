<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletAdminAdjustment;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * 🛡️ แก้ตามรีวิว PR 2662 (2026-11-06) — ทุกข้อเป็นเส้นทางที่ทำให้จ่ายเงินซ้ำ/ข้ามการตรวจสอบได้
 *
 * 1. ย้อนรายการได้เฉพาะรายการที่แอดมินสร้างเอง (รายการถอน/ออเดอร์/คอมมิชชั่น มีขั้นตอนกลับรายการของตัวเอง)
 * 2. รายการคืนเงินย้อนไม่ได้
 * 3. คืนเงินต้องอ้างรายการหักของกระเป๋าเดียวกัน คืนรวมไม่เกินยอดที่หัก และไม่รับ reference_type จากฟอร์ม
 * 4. API แอปแอดมินต้องส่ง Idempotency-Key
 * 5. super admin ก็แตะกระเป๋าตัวเองไม่ได้ · ฟอร์มฝากเงินแบบเก่าผ่านเส้นทางที่มี audit
 * 6. ฟอร์มย้อนรายการตรวจ expected_balance
 * 7. ลบกระเป๋าแล้วแถว audit ยังอยู่
 * 8. ยอดรวมหน้า audit นับ rollback ตามทิศทางจริง
 * 9. Policy รับแอดมินทั้งแบบคอลัมน์ role และ role model
 *
 * @group wallet
 */
class AdminWalletReviewFixesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(bool $super = false): User
    {
        return User::factory()->create(['role' => 'admin', 'is_super_admin' => $super]);
    }

    private function walletFor(?User $owner = null, float $balance = 1000): Wallet
    {
        $owner ??= User::factory()->create(['role' => 'user']);

        return Wallet::factory()->create(['user_id' => $owner->id, 'balance' => $balance, 'status' => 'active'])->fresh();
    }

    private function service(): WalletService
    {
        return app(WalletService::class);
    }

    private function rollbackForm(Wallet $wallet, WalletTransaction $tx, array $overrides = []): array
    {
        return array_merge([
            'transaction_id' => $tx->id,
            'reason' => 'ย้อนเพื่อทดสอบ',
            'idempotency_key' => (string) Str::uuid(),
            'expected_balance' => (string) $wallet->fresh()->balance,
            'confirmed' => '1',
        ], $overrides);
    }

    private function refundForm(Wallet $wallet, WalletTransaction $debit, string $amount, array $overrides = []): array
    {
        return array_merge([
            'transaction_id' => $debit->id,
            'amount' => $amount,
            'reason' => 'คืนค่าบริการที่ไม่ได้ใช้',
            'idempotency_key' => (string) Str::uuid(),
            'expected_balance' => (string) $wallet->fresh()->balance,
            'confirmed' => '1',
        ], $overrides);
    }

    /** รายการหักค่าบริการของระบบ (ไม่ใช่ของแอดมิน) */
    private function serviceFee(Wallet $wallet, float $amount, string $refType = 'service_booking', int $refId = 77): WalletTransaction
    {
        return $this->service()->deductForService($wallet, $amount, 'ค่าบริการทดสอบ', $refType, $refId);
    }

    // ─────────────── 1. ย้อนได้เฉพาะรายการของแอดมิน ───────────────

    public function test_rollback_refuses_transactions_owned_by_other_flows(): void
    {
        $admin = $this->admin();
        $wallet = $this->walletFor(balance: 1000);

        $systemTxs = [
            // คำขอถอนที่รออนุมัติ — ย้อนแล้วปฏิเสธ = ได้เงินคืน 2 รอบ
            $this->service()->deductForService($wallet, 100, 'คำขอถอนเงิน #WD1', 'withdrawal_request', 9, [], 'withdrawal'),
            // ชำระออเดอร์ด้วยกระเป๋า — ย้อนแล้วออเดอร์ยังจ่ายแล้ว + คืนเงินออเดอร์ได้อีก
            $this->service()->deductForService($wallet, 50, 'ชำระออเดอร์', 'order', 12, [], 'withdrawal'),
            // คอมมิชชั่น — ย้อนแล้ว ledger ยังบอกว่าจ่ายแล้ว
            $this->service()->deposit($wallet, 80, 'คอมมิชชั่น', 'mlm_commission', 5, [], 'commission'),
            // ค่าบริการของระบบ
            $this->serviceFee($wallet, 20),
        ];
        $balance = (float) $wallet->fresh()->balance;

        foreach ($systemTxs as $tx) {
            $this->actingAs($admin)
                ->post(route('admin.wallet.rollback-transaction', $wallet->id), $this->rollbackForm($wallet, $tx))
                ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'เฉพาะรายการที่แอดมินเติม/หักเอง'));
        }

        $this->assertEquals($balance, (float) $wallet->fresh()->balance);
        $this->assertSame(0, WalletAdminAdjustment::count());
        $this->assertSame(0, WalletTransaction::where('reference_type', 'rollback')->count());
    }

    public function test_rollback_button_is_shown_only_on_admin_entries(): void
    {
        $admin = $this->admin();
        $wallet = $this->walletFor(balance: 1000);
        $systemTx = $this->service()->deductForService($wallet, 100, 'คำขอถอนเงิน', 'withdrawal_request', 9, [], 'withdrawal');
        $adminTx = $this->service()->adminAdjust($wallet, 'credit', 25, 'เติมโดยแอดมิน', $admin);

        $html = $this->actingAs($admin)->get(route('admin.wallet.show', $wallet->id))->assertOk()->getContent();

        $this->assertStringContainsString("\$dispatch('wallet-rollback', { id: {$adminTx->id},", $html);
        $this->assertStringNotContainsString("\$dispatch('wallet-rollback', { id: {$systemTx->id},", $html);
    }

    // ─────────────── 2. คืนเงินย้อนไม่ได้ ───────────────

    public function test_refunds_cannot_be_rolled_back(): void
    {
        $admin = $this->admin();
        $wallet = $this->walletFor(balance: 1000);
        $debit = $this->service()->adminAdjust($wallet, 'debit', 200, 'หักผิด', $admin);
        $refund = $this->service()->refund($wallet, $debit->id, 200, 'คืนที่หักผิด', $admin);
        // รายการคืนเงินแบบเก่า (ก่อน PR) ที่ reference_type เป็นค่าอิสระ
        $legacyRefund = $this->service()->deposit($wallet, 30, 'คืนเงินแบบเก่า', 'admin_adjustment', $admin->id, [], 'refund');
        $balance = (float) $wallet->fresh()->balance;

        foreach ([$refund, $legacyRefund] as $tx) {
            $this->actingAs($admin)
                ->post(route('admin.wallet.rollback-transaction', $wallet->id), $this->rollbackForm($wallet, $tx))
                ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'คืนเงินย้อนไม่ได้'));
        }

        $this->assertEquals($balance, (float) $wallet->fresh()->balance);
    }

    // ─────────────── 3. คืนเงินต้องอ้างรายการหัก + มีเพดาน ───────────────

    public function test_refund_is_linked_to_a_debit_and_capped_at_what_was_taken(): void
    {
        $admin = $this->admin();
        $wallet = $this->walletFor(balance: 500);
        $fee = $this->serviceFee($wallet, 100); // 400

        $this->actingAs($admin)
            ->post(route('admin.wallet.refund', $wallet->id), $this->refundForm($wallet, $fee, '60'))
            ->assertSessionHas('success');
        $this->assertEquals(460.0, (float) $wallet->fresh()->balance);

        $tx = WalletTransaction::where('type', 'refund')->sole();
        $this->assertSame('admin_refund', $tx->reference_type);
        $this->assertSame($fee->id, (int) $tx->reference_id);
        $this->assertSame($fee->id, (int) WalletAdminAdjustment::sole()->original_transaction_id);

        // คืนเกินส่วนที่เหลือ (เหลือ 40) → ปฏิเสธ
        $this->actingAs($admin)
            ->post(route('admin.wallet.refund', $wallet->id), $this->refundForm($wallet, $fee, '40.01'))
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'คืนเงินได้สูงสุด ฿40.00'));

        // คืนส่วนที่เหลือพอดี → ได้ แล้วคืนอีกไม่ได้
        $this->actingAs($admin)
            ->post(route('admin.wallet.refund', $wallet->id), $this->refundForm($wallet, $fee, '40'))
            ->assertSessionHas('success');
        $this->actingAs($admin)
            ->post(route('admin.wallet.refund', $wallet->id), $this->refundForm($wallet, $fee, '1'))
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'คืนเงินครบแล้ว'));

        $this->assertEquals(500.0, (float) $wallet->fresh()->balance);
        $this->assertSame(2, WalletAdminAdjustment::where('action', 'refund')->count());
    }

    public function test_refund_without_a_debit_or_against_the_wrong_kind_of_transaction_is_refused(): void
    {
        $admin = $this->admin();
        $wallet = $this->walletFor(balance: 1000);
        $other = $this->walletFor(balance: 1000);
        $otherFee = $this->serviceFee($other, 50);
        $withdrawal = $this->service()->deductForService($wallet, 100, 'คำขอถอนเงิน', 'withdrawal_request', 9, [], 'withdrawal');
        $orderPay = $this->service()->deductForService($wallet, 30, 'ชำระออเดอร์', 'order', 12, [], 'withdrawal');
        $transferOut = $this->service()->adminTransfer($wallet, $other, 10, 'ย้ายเงิน', $admin)['out_transaction'];
        $credit = $this->service()->adminAdjust($wallet, 'credit', 10, 'เติม', $admin);
        $balance = (float) $wallet->fresh()->balance;

        // ไม่ระบุรายการ → validation error
        $this->actingAs($admin)
            ->post(route('admin.wallet.refund', $wallet->id), array_diff_key($this->refundForm($wallet, $credit, '10'), ['transaction_id' => 1]))
            ->assertSessionHasErrors('transaction_id');

        // รายการของกระเป๋าอื่น
        $this->actingAs($admin)
            ->post(route('admin.wallet.refund', $wallet->id), $this->refundForm($wallet, $otherFee, '10'))
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'ไม่พบรายการหักเงิน'));

        // ถอนเงิน / ชำระออเดอร์ / โอนออก / รายการเงินเข้า
        foreach ([$withdrawal, $orderPay, $transferOut, $credit] as $tx) {
            $this->actingAs($admin)
                ->post(route('admin.wallet.refund', $wallet->id), $this->refundForm($wallet, $tx, '5'))
                ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'คืนเงินได้เฉพาะรายการหักค่าบริการ'));
        }

        $this->assertEquals($balance, (float) $wallet->fresh()->balance);
        $this->assertSame(0, WalletAdminAdjustment::where('action', 'refund')->count());
    }

    public function test_refund_form_cannot_poison_another_transactions_rollback_state(): void
    {
        $admin = $this->admin();
        $wallet = $this->walletFor(balance: 1000);
        $victim = $this->service()->adminAdjust($wallet, 'credit', 300, 'เติมผิด', $admin);
        $fee = $this->serviceFee($wallet, 50);

        // ส่ง reference_type=rollback,reference_id=<victim> มาด้วย — ต้องไม่ถูกใช้
        $this->actingAs($admin)
            ->post(route('admin.wallet.refund', $wallet->id), $this->refundForm($wallet, $fee, '10', [
                'reference_type' => 'rollback',
                'reference_id' => $victim->id,
            ]))
            ->assertSessionHas('success');

        $this->assertSame(0, WalletTransaction::where('reference_type', 'rollback')->count());

        // รายการที่ถูกเล็งยังย้อนได้ตามปกติ
        $this->actingAs($admin)
            ->post(route('admin.wallet.rollback-transaction', $wallet->id), $this->rollbackForm($wallet, $victim))
            ->assertSessionHas('success');
    }

    public function test_refund_and_rollback_of_the_same_debit_cannot_both_happen(): void
    {
        $admin = $this->admin();

        // คืนเงินก่อน → ย้อนรายการหักไม่ได้
        $w1 = $this->walletFor(balance: 500);
        $debit1 = $this->service()->adminAdjust($w1, 'debit', 100, 'หักผิด', $admin);
        $this->service()->refund($w1, $debit1->id, 30, 'คืนบางส่วน', $admin);
        $this->actingAs($admin)
            ->post(route('admin.wallet.rollback-transaction', $w1->id), $this->rollbackForm($w1, $debit1))
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'มีการคืนเงินอ้างอิงไปแล้ว'));
        $this->assertEquals(430.0, (float) $w1->fresh()->balance);

        // ย้อนก่อน → คืนเงินอ้างรายการนั้นไม่ได้
        $w2 = $this->walletFor(balance: 500);
        $debit2 = $this->service()->adminAdjust($w2, 'debit', 100, 'หักผิด', $admin);
        $this->service()->rollbackTransaction($debit2, 'ย้อนที่หักผิด', $admin);
        $this->actingAs($admin)
            ->post(route('admin.wallet.refund', $w2->id), $this->refundForm($w2, $debit2, '10'))
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'คืนเงินครบแล้ว'));
        $this->assertEquals(500.0, (float) $w2->fresh()->balance);
    }

    public function test_refund_counts_what_the_owning_flow_already_refunded(): void
    {
        $admin = $this->admin();
        $wallet = $this->walletFor(balance: 500);
        $fee = $this->serviceFee($wallet, 100, 'service_booking', 77);
        // ระบบจองบริการคืนเงินเองไปแล้ว 70 (อ้างการจองเดียวกัน)
        $this->service()->deposit($wallet, 70, 'คืนค่าจองที่ยกเลิก', 'service_booking', 77, [], 'refund');

        $this->assertEquals(30.0, $this->service()->adminRefundableRemaining($fee->fresh()));

        $this->actingAs($admin)
            ->post(route('admin.wallet.refund', $wallet->id), $this->refundForm($wallet, $fee, '31'))
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'คืนเงินได้สูงสุด ฿30.00'));
    }

    // ─────────────── 4. API ต้องมี Idempotency-Key ───────────────

    public function test_admin_app_adjust_requires_an_idempotency_key(): void
    {
        $super = $this->admin(super: true);
        $wallet = $this->walletFor(balance: 100);
        Sanctum::actingAs($super, ['admin']);

        $this->postJson(route('api.admin.finance.wallets.adjust', $wallet->id), ['amount' => 40, 'reason' => 'เติมผ่านแอป'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'IDEMPOTENCY_KEY_REQUIRED');

        $this->postJson(route('api.admin.finance.wallets.adjust', $wallet->id), ['amount' => 40, 'reason' => 'เติมผ่านแอป'], ['Idempotency-Key' => str_repeat('k', 65)])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'IDEMPOTENCY_KEY_REQUIRED');

        $this->assertEquals(100.0, (float) $wallet->fresh()->balance);
        $this->assertSame(0, WalletAdminAdjustment::count());

        $this->postJson(route('api.admin.finance.wallets.adjust', $wallet->id), ['amount' => 40, 'reason' => 'เติมผ่านแอป'], ['Idempotency-Key' => (string) Str::uuid()])
            ->assertOk();
        $this->assertEquals(140.0, (float) $wallet->fresh()->balance);
    }

    // ─────────────── 5. super admin + ฟอร์มฝากเงินแบบเก่า ───────────────

    public function test_super_admin_cannot_touch_their_own_wallet_either(): void
    {
        $super = $this->admin(super: true);
        $own = $this->walletFor($super, 0);

        $this->actingAs($super)
            ->post(route('admin.wallet.adjust-balance', $own->id), [
                'direction' => 'credit',
                'amount' => '100',
                'reason' => 'เติมให้ตัวเอง',
                'idempotency_key' => (string) Str::uuid(),
                'expected_balance' => '0',
                'confirmed' => '1',
            ])
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'ตัวเอง'));

        Sanctum::actingAs($super, ['admin']);
        $this->postJson(route('api.admin.finance.wallets.adjust', $own->id), ['amount' => 100, 'reason' => 'เติมให้ตัวเอง'], ['Idempotency-Key' => (string) Str::uuid()])
            ->assertStatus(422);

        $this->assertEquals(0.0, (float) $own->fresh()->balance);
        $this->assertSame(0, WalletAdminAdjustment::count());
    }

    public function test_legacy_deposit_route_is_audited_idempotent_and_never_self(): void
    {
        $super = $this->admin(super: true);
        $member = User::factory()->create(['role' => 'user']);
        $wallet = $this->walletFor($member, 10);
        $payload = [
            'user_id' => $member->id,
            'amount' => '90',
            'reason' => 'ชดเชยผ่านฟอร์มเก่า',
            'idempotency_key' => (string) Str::uuid(),
        ];

        // ไม่มีเหตุผล / ไม่มีโทเคน → ปฏิเสธ
        $this->actingAs($super)->post(route('admin.wallet.deposit'), array_diff_key($payload, ['reason' => 1]))->assertSessionHasErrors('reason');
        $this->actingAs($super)->post(route('admin.wallet.deposit'), array_diff_key($payload, ['idempotency_key' => 1]))->assertSessionHasErrors('idempotency_key');
        $this->assertEquals(10.0, (float) $wallet->fresh()->balance);

        // ส่ง 2 รอบด้วยโทเคนเดิม → เติมครั้งเดียว + มี audit
        $this->actingAs($super)->post(route('admin.wallet.deposit'), $payload)->assertSessionHas('success');
        $this->actingAs($super)->post(route('admin.wallet.deposit'), $payload)->assertSessionHas('info');
        $this->assertEquals(100.0, (float) $wallet->fresh()->balance);
        $audit = WalletAdminAdjustment::sole();
        $this->assertSame('credit', $audit->action);
        $this->assertSame($super->id, $audit->admin_id);
        $this->assertSame('ชดเชยผ่านฟอร์มเก่า', $audit->reason);

        // เติมให้ตัวเอง → ปฏิเสธ
        $this->actingAs($super)
            ->post(route('admin.wallet.deposit'), array_merge($payload, ['user_id' => $super->id, 'idempotency_key' => (string) Str::uuid()]))
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'ตัวเอง'));
        $this->assertSame(0.0, (float) (Wallet::where('user_id', $super->id)->value('balance') ?? 0));

        // แอดมินทั่วไปใช้ route นี้ไม่ได้
        $this->actingAs($this->admin())
            ->post(route('admin.wallet.deposit'), array_merge($payload, ['idempotency_key' => (string) Str::uuid()]))
            ->assertSessionHas('error');
        $this->assertEquals(100.0, (float) $wallet->fresh()->balance);
        $this->assertSame(1, WalletAdminAdjustment::count());
    }

    // ─────────────── 6. ฟอร์มย้อนต้องมี expected_balance ───────────────

    public function test_rollback_form_checks_the_balance_seen_at_confirmation(): void
    {
        $admin = $this->admin();
        $wallet = $this->walletFor(balance: 0);
        $credit = $this->service()->adminAdjust($wallet, 'credit', 300, 'เติมผิด', $admin);

        $this->actingAs($admin)
            ->post(route('admin.wallet.rollback-transaction', $wallet->id), array_diff_key($this->rollbackForm($wallet, $credit), ['expected_balance' => 1]))
            ->assertSessionHasErrors('expected_balance');

        $this->actingAs($admin)
            ->post(route('admin.wallet.rollback-transaction', $wallet->id), $this->rollbackForm($wallet, $credit, ['expected_balance' => '250']))
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'ยอดเงินในกระเป๋าเปลี่ยนไป'));

        $this->assertEquals(300.0, (float) $wallet->fresh()->balance);
    }

    // ─────────────── 7. audit อยู่ต่อเมื่อกระเป๋าถูกลบ ───────────────

    public function test_audit_rows_outlive_a_deleted_wallet(): void
    {
        $admin = $this->admin();
        $wallet = $this->walletFor(balance: 0);
        $this->service()->adminAdjust($wallet, 'credit', 50, 'เติมก่อนลบ', $admin);

        DB::table('wallets')->where('id', $wallet->id)->delete();

        $audit = WalletAdminAdjustment::sole();
        $this->assertNull($audit->wallet_id);
        $this->assertSame('เติมก่อนลบ', $audit->reason);
        $this->assertEquals(50.0, (float) $audit->amount);
    }

    // ─────────────── 8. ยอดรวมหน้า audit นับ rollback ───────────────

    public function test_audit_page_totals_include_rollbacks_in_their_real_direction(): void
    {
        $super = $this->admin(super: true);
        $admin = $this->admin();
        $wallet = $this->walletFor(balance: 1000);
        $credit = $this->service()->adminAdjust($wallet, 'credit', 300, 'เติมผิด', $admin);   // +300
        $debit = $this->service()->adminAdjust($wallet, 'debit', 120, 'หักผิด', $admin);      // −120
        $this->service()->rollbackTransaction($credit, 'ย้อนเติม', $admin);                     // −300
        $this->service()->rollbackTransaction($debit, 'ย้อนหัก', $admin);                       // +120

        $response = $this->actingAs($super)->get(route('admin.wallet.adjustments', ['wallet_id' => $wallet->id]))->assertOk();
        $summary = $response->viewData('summary');

        $this->assertEquals(420.0, (float) $summary->total_in);
        $this->assertEquals(420.0, (float) $summary->total_out);
        $this->assertEquals(1000.0, (float) $wallet->fresh()->balance);

        // แถวย้อนรายการหักต้องแสดงเป็นเงินเข้า
        $rollbackOfDebit = WalletAdminAdjustment::where('action', 'rollback')->where('original_transaction_id', $debit->id)->sole();
        $this->assertTrue($rollbackOfDebit->is_credit);
        $this->assertFalse(WalletAdminAdjustment::where('action', 'rollback')->where('original_transaction_id', $credit->id)->sole()->is_credit);
    }

    // ─────────────── 9. Policy รับแอดมินทั้งสองแบบ ───────────────

    public function test_policy_accepts_admins_from_the_role_column_and_the_role_model(): void
    {
        $wallet = $this->walletFor(balance: 100);

        $roleColumnSuper = User::factory()->create(['role' => 'super_admin', 'is_super_admin' => false]);
        $role = Role::create(['name' => 'admin', 'display_name' => 'ผู้ดูแลระบบ']);
        $roleModelAdmin = User::factory()->create(['role' => 'user', 'is_super_admin' => false, 'role_id' => $role->id]);
        $member = User::factory()->create(['role' => 'user', 'is_super_admin' => false]);

        foreach ([$roleColumnSuper, $roleModelAdmin] as $user) {
            foreach (['deposit', 'withdraw', 'update'] as $ability) {
                $this->assertTrue(Gate::forUser($user)->allows($ability, $wallet), "{$user->role} ต้องได้สิทธิ์ {$ability}");
            }
        }
        foreach (['deposit', 'withdraw', 'update'] as $ability) {
            $this->assertFalse(Gate::forUser($member)->allows($ability, $wallet));
        }
    }

    public function test_role_column_super_admin_can_refund_rollback_and_transfer_through_the_admin_area(): void
    {
        $operator = User::factory()->create(['role' => 'super_admin', 'is_super_admin' => false]);
        $wallet = $this->walletFor(balance: 500);
        $other = $this->walletFor(balance: 0);
        $fee = $this->serviceFee($wallet, 100);
        $credit = $this->service()->adminAdjust($wallet, 'credit', 50, 'เติม', $this->admin());

        $this->actingAs($operator)
            ->post(route('admin.wallet.refund', $wallet->id), $this->refundForm($wallet, $fee, '10'))
            ->assertSessionHas('success');

        $this->actingAs($operator)
            ->post(route('admin.wallet.rollback-transaction', $wallet->id), $this->rollbackForm($wallet, $credit))
            ->assertSessionHas('success');

        $this->actingAs($operator)
            ->post(route('admin.wallet.admin-transfer'), [
                'from_wallet_id' => $wallet->id,
                'to_wallet_id' => $other->id,
                'amount' => '20',
                'reason' => 'ย้ายเงินทดสอบ',
                'idempotency_key' => (string) Str::uuid(),
                'expected_balance' => (string) $wallet->fresh()->balance,
                'confirmed' => '1',
            ])
            ->assertSessionHas('success');

        $this->assertEquals(390.0, (float) $wallet->fresh()->balance); // 500 −100 +50 +10 −50 −20
        $this->assertEquals(20.0, (float) $other->fresh()->balance);
    }
}
