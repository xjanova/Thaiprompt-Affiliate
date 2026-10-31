<?php

namespace Tests\Feature;

use App\Models\PaymentTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletDebt;
use App\Services\DebtCollectionService;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 📱 QR รับเงิน / สแกนจ่าย / เติมเงิน / หนี้ — บั๊กที่แก้ 29 ต.ค. 2026
 *
 * - ?amount= ใน QR รับเงินพิมพ์ลง <script> ดิบ (reflected XSS)
 * - ชื่อไทยใน QR ทำ qrcodejs "code length overflow" (QR ว่าง)
 * - key กันโอนซ้ำอัตโนมัติไม่รวมผู้รับ → โอนยอดเท่ากันให้ 2 คนในนาทีเดียว คนที่ 2 ได้ response ปลอม
 * - ตัวกั้น 2FA เรียก route ที่ไม่มีจริง (500) และ fetch ได้ HTML → หน้าสแกนจ่ายค้าง
 * - ?redirect= หลังยืนยัน 2FA รับ javascript: / โดเมนอื่น
 * - เติมเงินรับ payment_method อะไรก็ได้
 * - ยอด "ชำระแล้ว" ของหนี้ไม่นับยอดที่หักไปบางส่วน
 *
 * @group wallet
 */
class WalletQrAndTopupTest extends TestCase
{
    use RefreshDatabase;

    private function member(float $balance = 1000): array
    {
        $user = User::factory()->create(['role' => 'user', 'kyc_status' => 'approved']);
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => $balance, 'status' => 'active']);

        return [$user, $wallet];
    }

    public function test_qr_code_amount_is_numeric_only_and_payload_has_no_name(): void
    {
        [$user] = $this->member();

        $res = $this->actingAs($user)->get(route('user.wallet.qr-code', ['amount' => 'alert(document.cookie)']));

        $res->assertOk()->assertViewHas('requestedAmount', null);
        $res->assertDontSee('alert(document.cookie)', false);
        $payload = json_decode($res->viewData('qrData'), true);
        $this->assertArrayNotHasKey('name', $payload, 'ชื่อไทยใน QR ทำ qrcodejs สร้าง QR ไม่ได้');

        $this->actingAs($user)->get(route('user.wallet.qr-code', ['amount' => '250.456']))
            ->assertViewHas('requestedAmount', 250.46);
    }

    public function test_same_amount_qr_payments_to_two_people_both_go_through(): void
    {
        [$user, $wallet] = $this->member(1000);
        [, $a] = $this->member(0);
        [, $b] = $this->member(0);

        foreach ([$a, $b] as $to) {
            $this->actingAs($user)->postJson(route('user.wallet.qr-transfer.process'), [
                'wallet_address' => $to->wallet_address,
                'amount' => 100,
            ])->assertOk()->assertJsonPath('success', true)->assertHeaderMissing('X-Idempotent-Replay');
        }

        $this->assertEquals(100.0, (float) $a->fresh()->balance);
        $this->assertEquals(100.0, (float) $b->fresh()->balance, 'คนที่ 2 ต้องได้เงินจริง ไม่ใช่ response ซ้ำของคนแรก');
    }

    public function test_qr_payment_needing_2fa_answers_json_with_a_verify_link(): void
    {
        [$user] = $this->member(1000);
        [, $to] = $this->member(0);
        $this->mock(TwoFactorService::class, function ($m) {
            $m->shouldReceive('isRequired')->andReturn(true);
            $m->shouldReceive('isVerified')->andReturn(false);
        });

        $this->actingAs($user)
            ->from(route('user.wallet.qr-transfer'))
            ->postJson(route('user.wallet.qr-transfer.process'), ['wallet_address' => $to->wallet_address, 'amount' => 50])
            ->assertStatus(403)
            ->assertJsonPath('code', 'TWO_FACTOR_REQUIRED')
            ->assertJsonPath('verify_url', fn ($url) => str_contains($url, urlencode(route('user.wallet.qr-transfer'))));

        $this->assertEquals(0.0, (float) $to->fresh()->balance);
    }

    public function test_2fa_redirect_only_accepts_links_on_this_site(): void
    {
        [$user] = $this->member();
        $home = route('user.home');
        $cases = [
            'javascript:alert(1)' => $home,
            '//evil.example/steal' => $home,
            '/\\evil.example' => $home,
            'https://evil.example/x' => $home,
            '/user/wallet' => '/user/wallet',
            url('/user/wallet/qr-transfer') => url('/user/wallet/qr-transfer'),
        ];

        foreach ($cases as $given => $expected) {
            $this->actingAs($user)->get(route('user.two-factor.verify', ['action' => 'transfer', 'redirect' => $given]))
                ->assertOk()->assertViewHas('redirect', $expected);
        }
    }

    public function test_topup_rejects_a_payment_method_that_is_not_offered(): void
    {
        [$user] = $this->member();
        $tx = PaymentTransaction::create([
            'user_id' => $user->id,
            'type' => 'wallet_topup',
            'payment_method' => 'pending',
            'status' => 'pending',
            'amount' => 500,
            'currency' => 'THB',
            'expired_at' => now()->addMinutes(30),
        ]);

        $this->actingAs($user)
            ->from(route('user.wallet.topup.payment', $tx->transaction_id))
            ->post(route('user.wallet.topup.payment.process', $tx->transaction_id), ['payment_method' => 'free_money'])
            ->assertSessionHasErrors('payment_method');

        $this->assertSame('pending', $tx->fresh()->payment_method);
    }

    public function test_debt_summary_counts_partial_deductions_as_paid(): void
    {
        [$user] = $this->member();
        WalletDebt::create(['user_id' => $user->id, 'original_amount' => 1500, 'deducted_amount' => 600, 'remaining_amount' => 900, 'source_type' => 'manual', 'source_id' => 0, 'reason' => 'ทดสอบ', 'status' => 'active', 'priority' => 1]);
        WalletDebt::create(['user_id' => $user->id, 'original_amount' => 300, 'deducted_amount' => 300, 'remaining_amount' => 0, 'source_type' => 'manual', 'source_id' => 0, 'reason' => 'ทดสอบ', 'status' => 'paid', 'priority' => 1]);

        $summary = app(DebtCollectionService::class)->getUserDebtSummary($user->id);

        $this->assertEquals(900.0, (float) $summary['total_debt']);
        $this->assertEquals(900.0, (float) $summary['total_paid'], '600 ที่หักไปแล้ว + 300 ที่ชำระครบ');

        $this->actingAs($user)->get(route('user.wallet.debts'))->assertOk()->assertSee('50%');
    }
}
