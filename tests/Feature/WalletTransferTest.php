<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 💸 โอนเงินหน้าเว็บ (/user/wallet/transfer) — แก้ 29 ต.ค. 2026
 *
 * กติกาที่ต้องเป็นจริง:
 * - หักค่าธรรมเนียม + เช็คยอดขั้นต่ำตาม WalletSetting เหมือนหน้าโอน QR
 *   (บั๊กเดิม: เรียก transfer() ตรง ๆ → โอนฟรีและโอนต่ำกว่าขั้นต่ำได้)
 * - โทเคนฟอร์มเดิมส่งซ้ำ ต้องไม่โอนซ้ำ
 * - กระเป๋าที่ไม่ได้ตั้ง PIN ไม่ต้องกรอก PIN · ตั้งไว้แล้วต้องกรอกถูก
 *
 * @group wallet
 */
class WalletTransferTest extends TestCase
{
    use RefreshDatabase;

    private function setFee(float $fee, float $min): void
    {
        WalletSetting::updateOrCreate(['key' => 'transfer_fee_amount'], ['group' => 'fees', 'value' => (string) $fee, 'type' => 'number', 'label' => 'ค่าธรรมเนียมการโอน', 'is_active' => true]);
        WalletSetting::updateOrCreate(['key' => 'transfer_min_amount'], ['group' => 'limits', 'value' => (string) $min, 'type' => 'number', 'label' => 'ยอดโอนขั้นต่ำ', 'is_active' => true]);
        Cache::forget('wallet_settings');
    }

    /** @return array{0: User, 1: Wallet, 2: Wallet} */
    private function makePair(float $balance = 1000, ?string $pin = null): array
    {
        $sender = User::factory()->create(['role' => 'user']);
        $receiver = User::factory()->create(['role' => 'user']);
        $from = Wallet::factory()->create(['user_id' => $sender->id, 'balance' => $balance, 'status' => 'active']);
        if ($pin !== null) {
            $from->forceFill(['pin_hash' => password_hash($pin, PASSWORD_DEFAULT)])->save();
        }
        $to = Wallet::factory()->create(['user_id' => $receiver->id, 'balance' => 0, 'status' => 'active']);

        return [$sender, $from->fresh(), $to];
    }

    public function test_web_transfer_charges_the_configured_fee(): void
    {
        $this->setFee(5, 10);
        [$sender, $from, $to] = $this->makePair(1000);

        $this->actingAs($sender)->post(route('user.wallet.transfer.submit'), [
            'wallet_address' => $to->wallet_address,
            'amount' => 200,
            'transfer_token' => (string) Str::uuid(),
        ])->assertRedirect(route('user.wallet.index'));

        $this->assertEquals(795.0, (float) $from->fresh()->balance, 'ต้องหัก 200 + ค่าธรรมเนียม 5');
        $this->assertEquals(200.0, (float) $to->fresh()->balance);
    }

    public function test_web_transfer_below_minimum_is_refused(): void
    {
        $this->setFee(5, 10);
        [$sender, $from, $to] = $this->makePair(1000);

        $this->actingAs($sender)->from(route('user.wallet.transfer'))->post(route('user.wallet.transfer.submit'), [
            'wallet_address' => $to->wallet_address,
            'amount' => 5,
        ])->assertRedirect(route('user.wallet.transfer'))->assertSessionHas('error');

        $this->assertEquals(1000.0, (float) $from->fresh()->balance);
    }

    public function test_resubmitting_the_same_form_token_does_not_transfer_twice(): void
    {
        $this->setFee(0, 0);
        [$sender, $from, $to] = $this->makePair(1000);
        $payload = ['wallet_address' => $to->wallet_address, 'amount' => 100, 'transfer_token' => (string) Str::uuid()];

        $this->actingAs($sender)->post(route('user.wallet.transfer.submit'), $payload);
        $this->actingAs($sender)->post(route('user.wallet.transfer.submit'), $payload)
            ->assertRedirect(route('user.wallet.transactions'));

        $this->assertEquals(900.0, (float) $from->fresh()->balance, 'กดซ้ำด้วยโทเคนเดิมต้องโอนครั้งเดียว');
    }

    public function test_failed_transfer_frees_the_token_for_a_retry(): void
    {
        $this->setFee(0, 0);
        [$sender, $from, $to] = $this->makePair(1000, pin: '123456');
        $payload = ['wallet_address' => $to->wallet_address, 'amount' => 100, 'transfer_token' => (string) Str::uuid()];

        $this->actingAs($sender)->post(route('user.wallet.transfer.submit'), $payload + ['pin' => '000000'])
            ->assertSessionHas('error');
        $this->assertEquals(1000.0, (float) $from->fresh()->balance);

        $this->actingAs($sender)->post(route('user.wallet.transfer.submit'), $payload + ['pin' => '123456'])
            ->assertRedirect(route('user.wallet.index'));
        $this->assertEquals(900.0, (float) $from->fresh()->balance);
    }

    public function test_wallet_with_pin_must_send_it(): void
    {
        [$sender, $from, $to] = $this->makePair(1000, pin: '123456');

        $this->actingAs($sender)->post(route('user.wallet.transfer.submit'), [
            'wallet_address' => $to->wallet_address,
            'amount' => 100,
        ])->assertSessionHasErrors('pin');

        $this->assertEquals(1000.0, (float) $from->fresh()->balance);
    }

    public function test_percentage_fee_uses_one_formula_everywhere(): void
    {
        WalletSetting::updateOrCreate(['key' => 'transfer_fee_type'], ['group' => 'fees', 'value' => 'percentage', 'type' => 'string', 'label' => 'ประเภทค่าธรรมเนียม', 'is_active' => true]);
        WalletSetting::updateOrCreate(['key' => 'transfer_fee_amount'], ['group' => 'fees', 'value' => '2', 'type' => 'number', 'label' => 'ค่าธรรมเนียมการโอน', 'is_active' => true]);
        WalletSetting::updateOrCreate(['key' => 'transfer_fee_min'], ['group' => 'fees', 'value' => '3', 'type' => 'number', 'label' => 'ค่าธรรมเนียมขั้นต่ำ', 'is_active' => true]);
        Cache::forget('wallet_settings');

        $this->assertEquals(20.0, WalletSetting::calculateTransferFee(1000), '2% ของ 1000');
        $this->assertEquals(3.0, WalletSetting::calculateTransferFee(50), '2% ของ 50 = 1 → ขั้นต่ำ 3');
    }
}
