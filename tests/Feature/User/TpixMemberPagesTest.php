<?php

namespace Tests\Feature\User;

use App\Models\CryptoAddress;
use App\Models\CryptoCurrency;
use App\Models\CryptoTransaction;
use App\Models\CryptoWallet;
use App\Models\TwoFactorSetting;
use App\Models\TwoFactorUserSetting;
use App\Models\User;
use App\Services\Crypto\CryptoWalletService;
use App\Services\Crypto\TPIXBlockchainService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use ValueError;

/**
 * หน้ากระเป๋า TPIX ของสมาชิก: ฝาก ส่ง ประวัติ รายละเอียด และยอดในหน้ากระเป๋า
 *
 * กติกาที่ต้องเป็นจริง:
 * - ทุกหน้าเปิดได้ทั้งตอนมีข้อมูลและตอนว่าง เห็นเฉพาะรายการของตัวเอง
 * - ห้ามแสดงที่อยู่ที่ไม่มี private key ให้สมาชิกโอนเหรียญจริงเข้ามา
 * - การส่งย้ายยอดตรงจำนวน ยอดไม่พอหรือกระเป๋าถูกระงับต้องไม่ขยับยอดและไม่สร้างรายการ
 */
class TpixMemberPagesTest extends TestCase
{
    use RefreshDatabase;

    private const REAL_ADDRESS = '0x1111111111111111111111111111111111111111';

    private function member(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function makeCurrency(array $overrides = []): CryptoCurrency
    {
        return CryptoCurrency::create(array_merge([
            'code' => 'TPIX',
            'name' => 'Thaiprompt Index',
            'symbol' => 'TPIX',
            'network' => 'tpix',
            'decimals' => 18,
            'min_deposit' => 0.01,
            'min_withdrawal' => 0.1,
            'withdrawal_fee' => 0.01,
            'confirmations_required' => 5,
            'is_active' => true,
            'deposit_enabled' => true,
            'withdrawal_enabled' => true,
        ], $overrides));
    }

    private function makeWallet(User $user, CryptoCurrency $currency, string $balance = '10', string $status = 'active'): CryptoWallet
    {
        return CryptoWallet::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'wallet_type' => 'custodial',
            'balance' => $balance,
            'status' => $status,
        ]);
    }

    private function makeTransaction(CryptoWallet $wallet, array $overrides = []): CryptoTransaction
    {
        return CryptoTransaction::create(array_merge([
            'user_id' => $wallet->user_id,
            'crypto_currency_id' => $wallet->currency_id,
            'crypto_wallet_id' => $wallet->id,
            'type' => 'deposit',
            'amount' => 2.5,
            'fee' => 0,
            'status' => 'completed',
            'network' => 'tpix',
        ], $overrides));
    }

    private function send(array $data = []): TestResponse
    {
        return $this->from(route('user.tpix.send'))
            ->post(route('user.tpix.send.process'), array_merge(['amount' => '2.5'], $data));
    }

    private function balanceOf(CryptoWallet $wallet): string
    {
        return (string) $wallet->fresh()->balance;
    }

    // ==================== หน้ากระเป๋า ====================

    public function test_wallet_page_shows_the_real_balance(): void
    {
        $user = $this->member();
        $currency = $this->makeCurrency();
        $this->makeWallet($user, $currency, '12.5');

        $this->actingAs($user)->get(route('user.tpix.wallet'))
            ->assertOk()
            ->assertViewIs('user.tpix.wallet')
            ->assertSee('12.50000000')
            ->assertDontSee('USD');
    }

    public function test_wallet_page_shows_the_baht_value_when_a_rate_exists(): void
    {
        $user = $this->member();
        $currency = $this->makeCurrency();
        $this->makeWallet($user, $currency, '10');
        DB::table('crypto_exchange_rates')->insert([
            'crypto_currency_id' => $currency->id,
            'rate_thb' => 20,
            'buy_rate_thb' => 20,
            'sell_rate_thb' => 20,
            'source' => 'manual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)->get(route('user.tpix.wallet'))
            ->assertOk()
            ->assertSee('200.00');
    }

    // ==================== หน้าฝาก ====================

    public function test_deposit_page_hides_a_generated_address_that_has_no_private_key(): void
    {
        $user = $this->member();
        $this->makeCurrency();

        $response = $this->actingAs($user)->get(route('user.tpix.deposit'));

        $response->assertOk()
            ->assertViewIs('user.tpix.deposit')
            ->assertSee('ยังไม่เปิดรับฝากเหรียญจากภายนอก')
            ->assertSee('ยังไม่มีรายการฝาก')
            ->assertSee('0.00000000');

        $address = CryptoAddress::where('address_type', 'deposit')->sole()->address;
        $this->assertNotEmpty($address);
        $response->assertDontSee($address);
    }

    public function test_deposit_page_hides_a_valid_looking_address_that_has_no_private_key(): void
    {
        $user = $this->member();
        $currency = $this->makeCurrency();
        $wallet = $this->makeWallet($user, $currency);
        $this->makeDepositAddress($wallet, $currency);

        $this->actingAs($user)->get(route('user.tpix.deposit'))
            ->assertOk()
            ->assertDontSee(self::REAL_ADDRESS);
    }

    public function test_deposit_page_shows_an_address_the_system_holds_a_key_for(): void
    {
        $user = $this->member();
        $currency = $this->makeCurrency();
        $wallet = $this->makeWallet($user, $currency);
        $this->makeDepositAddress($wallet, $currency, ['encrypted_private_key' => Crypt::encryptString('key')]);

        $this->actingAs($user)->get(route('user.tpix.deposit'))
            ->assertOk()
            ->assertSee(self::REAL_ADDRESS)
            ->assertSee('0.01000000')
            ->assertDontSee('ยังไม่เปิดรับฝากเหรียญจากภายนอก');
    }

    public function test_deposit_page_lists_only_the_members_own_deposits(): void
    {
        $user = $this->member();
        $other = $this->member();
        $currency = $this->makeCurrency();
        $this->makeTransaction($this->makeWallet($user, $currency), ['amount' => 3.25]);
        $this->makeTransaction($this->makeWallet($other, $currency), ['amount' => 7.75]);

        $this->actingAs($user)->get(route('user.tpix.deposit'))
            ->assertOk()
            ->assertSee('3.25000000')
            ->assertDontSee('7.75000000');
    }

    public function test_deposit_page_redirects_with_a_thai_message_when_disabled(): void
    {
        $user = $this->member();
        $this->makeCurrency(['deposit_enabled' => false]);

        $this->actingAs($user)->get(route('user.tpix.deposit'))
            ->assertRedirect(route('user.tpix.wallet'))
            ->assertSessionHas('error', 'ขณะนี้ปิดการฝาก TPIX ชั่วคราว');
    }

    private function makeDepositAddress(CryptoWallet $wallet, CryptoCurrency $currency, array $overrides = []): CryptoAddress
    {
        return CryptoAddress::create(array_merge([
            'crypto_wallet_id' => $wallet->id,
            'crypto_currency_id' => $currency->id,
            'address' => self::REAL_ADDRESS,
            'network' => 'tpix',
            'address_type' => 'deposit',
        ], $overrides));
    }

    // ==================== หน้าส่ง ====================

    public function test_send_page_opens_with_balance_and_form_fields(): void
    {
        $user = $this->member();
        $this->makeWallet($user, $this->makeCurrency(), '12.5');

        $this->actingAs($user)->get(route('user.tpix.send'))
            ->assertOk()
            ->assertViewIs('user.tpix.send')
            ->assertSee('12.50000000')
            ->assertSee('action="'.route('user.tpix.send.process').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="recipient"', false)
            ->assertSee('name="amount"', false)
            ->assertSee('name="transfer_token"', false);
    }

    public function test_send_page_opens_for_a_member_without_a_wallet(): void
    {
        $user = $this->member();
        $this->makeCurrency();

        $this->actingAs($user)->get(route('user.tpix.send'))
            ->assertOk()
            ->assertSee('ยังไม่มีกระเป๋า TPIX');
    }

    public function test_send_page_warns_a_suspended_wallet(): void
    {
        $user = $this->member();
        $this->makeWallet($user, $this->makeCurrency(), '10', 'suspended');

        $this->actingAs($user)->get(route('user.tpix.send'))
            ->assertOk()
            ->assertSee('ถูกระงับการใช้งาน');
    }

    public function test_send_moves_the_exact_amount_and_writes_both_legs(): void
    {
        $sender = $this->member();
        $recipient = $this->member();
        $currency = $this->makeCurrency();
        $fromWallet = $this->makeWallet($sender, $currency, '10.123456789012345678');
        $toWallet = $this->makeWallet($recipient, $currency, '1');

        $this->actingAs($sender);
        $this->send(['recipient' => $recipient->email, 'amount' => '2.12345678'])
            ->assertRedirect(route('user.tpix.wallet'))
            ->assertSessionHas('success');

        $this->assertSame('8.000000009012345678', $this->balanceOf($fromWallet));
        $this->assertSame('3.123456780000000000', $this->balanceOf($toWallet));

        $out = CryptoTransaction::where('user_id', $sender->id)->sole();
        $in = CryptoTransaction::where('user_id', $recipient->id)->sole();
        $this->assertSame('transfer_out', $out->type);
        $this->assertSame('transfer_in', $in->type);
        $this->assertSame('completed', $out->status);
        $this->assertSame($out->metadata['transfer_ref'], $in->metadata['transfer_ref']);
        $this->assertSame('2.123456780000000000', (string) $in->amount);
    }

    public function test_send_opens_a_wallet_for_a_recipient_who_has_none(): void
    {
        $sender = $this->member();
        $recipient = $this->member();
        $currency = $this->makeCurrency();
        $this->makeWallet($sender, $currency);

        $this->actingAs($sender);
        $this->send(['recipient' => $recipient->email])->assertRedirect(route('user.tpix.wallet'));

        $wallet = CryptoWallet::where('user_id', $recipient->id)->sole();
        $this->assertSame('2.500000000000000000', (string) $wallet->balance);
    }

    public function test_send_finds_the_recipient_by_wallet_address(): void
    {
        $sender = $this->member();
        $recipient = $this->member();
        $currency = $this->makeCurrency();
        $this->makeWallet($sender, $currency);
        $toWallet = $this->makeWallet($recipient, $currency, '0');
        $toWallet->update(['address' => self::REAL_ADDRESS]);

        $this->actingAs($sender);
        $this->send(['recipient' => self::REAL_ADDRESS])->assertRedirect(route('user.tpix.wallet'));

        $this->assertSame('2.500000000000000000', $this->balanceOf($toWallet));
    }

    public function test_send_refuses_more_than_the_balance(): void
    {
        $sender = $this->member();
        $recipient = $this->member();
        $currency = $this->makeCurrency();
        $fromWallet = $this->makeWallet($sender, $currency, '2.49999999');
        $toWallet = $this->makeWallet($recipient, $currency, '0');

        $this->actingAs($sender);
        $this->send(['recipient' => $recipient->email])
            ->assertRedirect(route('user.tpix.send'))
            ->assertSessionHas('error', 'ยอดคงเหลือไม่พอ');

        $this->assertSame('2.499999990000000000', $this->balanceOf($fromWallet));
        $this->assertSame('0.000000000000000000', $this->balanceOf($toWallet));
        $this->assertSame(0, CryptoTransaction::count());
    }

    public function test_send_with_no_wallet_changes_nothing(): void
    {
        $sender = $this->member();
        $recipient = $this->member();
        $this->makeCurrency();

        $this->actingAs($sender);
        $this->send(['recipient' => $recipient->email])
            ->assertRedirect(route('user.tpix.send'))
            ->assertSessionHas('error');

        $this->assertSame(0, CryptoTransaction::count());
    }

    #[DataProvider('blockedWalletStatuses')]
    public function test_send_refuses_a_blocked_sender_wallet(string $status): void
    {
        $sender = $this->member();
        $recipient = $this->member();
        $currency = $this->makeCurrency();
        $fromWallet = $this->makeWallet($sender, $currency, '10', $status);
        $toWallet = $this->makeWallet($recipient, $currency, '0');

        $this->actingAs($sender);
        $this->send(['recipient' => $recipient->email])->assertSessionHas('error');

        $this->assertSame('10.000000000000000000', $this->balanceOf($fromWallet));
        $this->assertSame('0.000000000000000000', $this->balanceOf($toWallet));
    }

    #[DataProvider('blockedWalletStatuses')]
    public function test_send_refuses_a_blocked_recipient_wallet(string $status): void
    {
        $sender = $this->member();
        $recipient = $this->member();
        $currency = $this->makeCurrency();
        $fromWallet = $this->makeWallet($sender, $currency, '10');
        $toWallet = $this->makeWallet($recipient, $currency, '0', $status);

        $this->actingAs($sender);
        $this->send(['recipient' => $recipient->email])
            ->assertSessionHas('error', 'กระเป๋าของผู้รับยังรับเหรียญไม่ได้');

        $this->assertSame('10.000000000000000000', $this->balanceOf($fromWallet));
        $this->assertSame('0.000000000000000000', $this->balanceOf($toWallet));
    }

    public static function blockedWalletStatuses(): array
    {
        return [
            'suspended' => ['suspended'],
            'locked' => ['locked'],
        ];
    }

    public function test_send_refuses_the_members_own_email(): void
    {
        $sender = $this->member();
        $fromWallet = $this->makeWallet($sender, $this->makeCurrency());

        $this->actingAs($sender);
        $this->send(['recipient' => $sender->email])
            ->assertSessionHas('error', 'ส่งให้ตัวเองไม่ได้');

        $this->assertSame('10.000000000000000000', $this->balanceOf($fromWallet));
    }

    public function test_send_says_so_when_the_recipient_is_not_a_member(): void
    {
        $sender = $this->member();
        $this->makeWallet($sender, $this->makeCurrency());

        $this->actingAs($sender);
        $this->send(['recipient' => self::REAL_ADDRESS])
            ->assertSessionHas('error')
            ->assertSessionHasInput('recipient', self::REAL_ADDRESS);
    }

    #[DataProvider('invalidAmounts')]
    public function test_send_rejects_a_malformed_amount(string $amount): void
    {
        $sender = $this->member();
        $recipient = $this->member();
        $currency = $this->makeCurrency();
        $fromWallet = $this->makeWallet($sender, $currency);
        $this->makeWallet($recipient, $currency, '0');

        $this->actingAs($sender);
        $this->send(['recipient' => $recipient->email, 'amount' => $amount])
            ->assertSessionHasErrors('amount');

        $this->assertSame('10.000000000000000000', $this->balanceOf($fromWallet));
    }

    public static function invalidAmounts(): array
    {
        return [
            'negative' => ['-1'],
            'zero' => ['0'],
            'too small' => ['0.00001'],
            'nine decimals' => ['1.123456789'],
            'exponent' => ['1e1'],
        ];
    }

    public function test_send_when_tpix_is_switched_off_changes_nothing(): void
    {
        $sender = $this->member();
        $recipient = $this->member();
        $fromWallet = $this->makeWallet($sender, $this->makeCurrency(['is_active' => false]));

        $this->actingAs($sender);
        $this->send(['recipient' => $recipient->email])->assertSessionHas('error');

        $this->assertSame('10.000000000000000000', $this->balanceOf($fromWallet));
    }

    public function test_a_replayed_form_token_sends_only_once(): void
    {
        $sender = $this->member();
        $recipient = $this->member();
        $currency = $this->makeCurrency();
        $fromWallet = $this->makeWallet($sender, $currency);
        $this->makeWallet($recipient, $currency, '0');
        $data = ['recipient' => $recipient->email, 'transfer_token' => 'token-1'];

        $this->actingAs($sender);
        $this->send($data)->assertRedirect(route('user.tpix.wallet'));
        $this->send($data)->assertRedirect(route('user.tpix.transactions'));

        $this->assertSame('7.500000000000000000', $this->balanceOf($fromWallet));
        $this->assertSame(1, CryptoTransaction::where('user_id', $sender->id)->count());
    }

    public function test_a_refused_send_leaves_the_form_token_usable(): void
    {
        $sender = $this->member();
        $recipient = $this->member();
        $currency = $this->makeCurrency();
        $fromWallet = $this->makeWallet($sender, $currency, '1');
        $this->makeWallet($recipient, $currency, '0');
        $data = ['recipient' => $recipient->email, 'transfer_token' => 'token-2'];

        $this->actingAs($sender);
        $this->send($data)->assertSessionHas('error', 'ยอดคงเหลือไม่พอ');
        $this->send($data + ['amount' => '0.5'])->assertRedirect(route('user.tpix.wallet'));

        $this->assertSame('0.500000000000000000', $this->balanceOf($fromWallet));
    }

    public function test_a_database_error_during_send_shows_no_raw_text_and_frees_the_token(): void
    {
        $sender = $this->member();
        $recipient = $this->member();
        $this->makeWallet($sender, $this->makeCurrency());

        $this->mock(CryptoWalletService::class)
            ->shouldReceive('transfer')->once()->andThrow(new Exception('SQLSTATE secret'));

        $this->actingAs($sender);
        $response = $this->send(['recipient' => $recipient->email, 'transfer_token' => 'token-3']);

        $response->assertSessionHas('error', 'ส่ง TPIX ไม่สำเร็จ กรุณาลองอีกครั้ง');
        $this->assertFalse(Cache::has('tpix-send:'.$sender->id.':token-3'));
    }

    public function test_send_stops_at_the_two_factor_gate_without_moving_the_balance(): void
    {
        $sender = $this->member();
        $recipient = $this->member();
        $currency = $this->makeCurrency();
        $fromWallet = $this->makeWallet($sender, $currency);
        TwoFactorSetting::create(['enabled' => true, 'require_on_transfer' => true]);
        TwoFactorSetting::clearCache();
        TwoFactorUserSetting::create(['user_id' => $sender->id, 'enabled' => true]);

        $this->actingAs($sender);
        $this->send(['recipient' => $recipient->email])
            ->assertRedirectContains('/two-factor/verify');

        $this->assertSame('10.000000000000000000', $this->balanceOf($fromWallet));
    }

    public function test_guests_cannot_send(): void
    {
        $this->post(route('user.tpix.send.process'), ['recipient' => 'a@b.co', 'amount' => '1'])
            ->assertRedirect(route('login'));
    }

    // ==================== หน้าประวัติ ====================

    public function test_transactions_page_lists_only_the_members_own_rows(): void
    {
        $user = $this->member();
        $other = $this->member();
        $currency = $this->makeCurrency();
        $this->makeTransaction($this->makeWallet($user, $currency), ['amount' => 3.25]);
        $this->makeTransaction($this->makeWallet($other, $currency), ['amount' => 7.75]);

        $this->actingAs($user)->get(route('user.tpix.transactions'))
            ->assertOk()
            ->assertViewIs('user.tpix.transactions')
            ->assertSee('3.25000000')
            ->assertDontSee('7.75000000');
    }

    public function test_transactions_page_has_an_empty_state(): void
    {
        $user = $this->member();
        $this->makeCurrency();

        $this->actingAs($user)->get(route('user.tpix.transactions'))
            ->assertOk()
            ->assertSee('ยังไม่มีธุรกรรม');
    }

    public function test_transactions_page_filters_by_type_status_and_date(): void
    {
        $user = $this->member();
        $wallet = $this->makeWallet($user, $this->makeCurrency());
        $this->makeTransaction($wallet, ['amount' => 1.25, 'type' => 'deposit']);
        $this->makeTransaction($wallet, ['amount' => 2.25, 'type' => 'withdrawal', 'status' => 'pending']);
        $old = $this->makeTransaction($wallet, ['amount' => 3.25, 'type' => 'deposit']);
        DB::table('crypto_transactions')->where('id', $old->id)->update(['created_at' => '2026-01-01 10:00:00']);

        $this->actingAs($user);

        $this->get(route('user.tpix.transactions', ['type' => 'withdrawal']))
            ->assertSee('2.25000000')->assertDontSee('1.25000000');

        $this->get(route('user.tpix.transactions', ['status' => 'pending']))
            ->assertSee('2.25000000')->assertDontSee('3.25000000');

        $this->get(route('user.tpix.transactions', ['date_from' => '2026-01-01', 'date_to' => '2026-01-01']))
            ->assertSee('3.25000000')->assertDontSee('1.25000000');

        $this->get(route('user.tpix.transactions', ['type' => 'refund']))
            ->assertOk()->assertSee('ไม่พบรายการตามตัวกรองนี้');
    }

    public function test_transactions_page_keeps_filters_across_pages(): void
    {
        $user = $this->member();
        $wallet = $this->makeWallet($user, $this->makeCurrency());
        for ($i = 0; $i < 21; $i++) {
            $this->makeTransaction($wallet);
        }

        $this->actingAs($user)->get(route('user.tpix.transactions', ['type' => 'deposit']))
            ->assertOk()
            ->assertSee('type=deposit&amp;page=2', false);
    }

    #[DataProvider('badFilters')]
    public function test_transactions_page_rejects_bad_filters_instead_of_crashing(array $query): void
    {
        $user = $this->member();
        $this->makeCurrency();

        $this->actingAs($user)->get(route('user.tpix.transactions', $query))
            ->assertRedirect()
            ->assertSessionHasErrors();
    }

    public static function badFilters(): array
    {
        return [
            'not a date' => [['date_from' => 'abc']],
            'array date' => [['date_to' => ['2026-01-01']]],
            'array type' => [['type' => ['deposit']]],
        ];
    }

    // ==================== หน้ารายละเอียด ====================

    public function test_details_page_shows_a_transfer(): void
    {
        $user = $this->member();
        $wallet = $this->makeWallet($user, $this->makeCurrency());
        $transaction = $this->makeTransaction($wallet, [
            'type' => 'transfer_out',
            'description' => 'ส่งให้ สมชาย',
            'admin_note' => 'บันทึกภายใน',
            'error_message' => 'SQLSTATE secret',
        ]);

        $this->actingAs($user)->get(route('user.tpix.transactions.details', $transaction->id))
            ->assertOk()
            ->assertViewIs('user.tpix.transaction-details')
            ->assertSee('ส่งให้สมาชิก')
            ->assertSee('ส่งให้ สมชาย')
            ->assertSee('-2.50000000')
            ->assertSee($transaction->transaction_id)
            ->assertDontSee('บันทึกภายใน')
            ->assertDontSee('SQLSTATE secret')
            ->assertDontSee('ข้อมูลบนเครือข่าย');
    }

    public function test_details_page_returns_404_for_another_members_transaction(): void
    {
        $user = $this->member();
        $other = $this->member();
        $transaction = $this->makeTransaction($this->makeWallet($other, $this->makeCurrency()));

        $this->actingAs($user)->get(route('user.tpix.transactions.details', $transaction->id))
            ->assertNotFound();
    }

    public function test_details_page_shows_chain_data_and_the_real_explorer(): void
    {
        $user = $this->member();
        $wallet = $this->makeWallet($user, $this->makeCurrency(['explorer_url' => 'http://localhost:4000']));
        $transaction = $this->makeTransaction($wallet, ['tx_hash' => '0xabc']);

        $this->mockChain()->shouldReceive('getTransaction')->once()->with('0xabc')
            ->andReturn(['blockNumber' => 4242]);

        $this->actingAs($user)->get(route('user.tpix.transactions.details', $transaction->id))
            ->assertOk()
            ->assertSee('4242')
            ->assertSee(rtrim(config('tpix_pay.explorer_url'), '/').'/tx/0xabc')
            ->assertDontSee('localhost:4000');
    }

    public function test_details_page_survives_a_chain_failure(): void
    {
        $user = $this->member();
        $transaction = $this->makeTransaction($this->makeWallet($user, $this->makeCurrency()), ['tx_hash' => '0xdef']);

        // ยอดเกิน 2^63 wei ทำ bcdiv ใน formatTransaction โยน ValueError ไม่ใช่ Exception
        $this->mockChain()->shouldReceive('getTransaction')->once()
            ->andThrow(new ValueError('bcdiv(): Argument #1 ($num1) is not well-formed'));

        $this->actingAs($user)->get(route('user.tpix.transactions.details', $transaction->id))
            ->assertOk()
            ->assertSee('ขณะนี้เชื่อมต่อเครือข่ายไม่ได้');
    }

    private function mockChain(): Mockery\MockInterface
    {
        $mock = $this->mock(TPIXBlockchainService::class);
        $mock->shouldReceive('isValidAddress')->andReturn(false);

        return $mock;
    }
}
