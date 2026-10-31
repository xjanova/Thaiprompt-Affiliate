<?php

namespace Tests\Feature\User;

use App\Models\CryptoCurrency;
use App\Models\CryptoTransaction;
use App\Models\CryptoWallet;
use App\Models\TwoFactorSetting;
use App\Models\TwoFactorUserSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * หน้าถอน TPIX ของสมาชิก (GET /user/tpix/withdrawal) และเส้นทางส่งคำขอ (POST)
 *
 * กติกาที่ต้องเป็นจริง:
 * - เปิดหน้าได้ แสดงยอด ขั้นต่ำ ค่าธรรมเนียม และรายการถอนล่าสุดของตัวเอง
 * - ฟอร์มส่งฟิลด์ตรงกับ processWithdrawal และมีกล่อง Turnstile เมื่อตั้ง key แล้ว
 * - หักยอดตรงจำนวน + ค่าธรรมเนียม ยอดไม่พอต้องไม่หักและไม่สร้างรายการ
 * - ไม่ผ่าน Turnstile หรือ 2FA ต้องไม่หักยอด
 */
class TpixWithdrawalPageTest extends TestCase
{
    use RefreshDatabase;

    private const ADDRESS = '0x1111111111111111111111111111111111111111';

    private function makeCurrency(array $overrides = []): CryptoCurrency
    {
        return CryptoCurrency::create(array_merge([
            'code' => 'TPIX',
            'name' => 'Thaiprompt Index',
            'symbol' => 'TPIX',
            'network' => 'tpix',
            'decimals' => 18,
            'min_withdrawal' => 0.1,
            'max_withdrawal' => 1000,
            'withdrawal_fee' => 0.01,
            'is_active' => true,
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

    private function makeWithdrawal(User $user, CryptoCurrency $currency, CryptoWallet $wallet, string $toAddress): CryptoTransaction
    {
        return CryptoTransaction::create([
            'user_id' => $user->id,
            'crypto_currency_id' => $currency->id,
            'crypto_wallet_id' => $wallet->id,
            'type' => 'withdrawal',
            'amount' => 2.5,
            'fee' => 0.01,
            'to_address' => $toAddress,
            'status' => 'pending',
            'network' => 'tpix',
        ]);
    }

    private function useTurnstileKeys(): void
    {
        config([
            'turnstile.enabled' => true,
            'turnstile.site_key' => 'test-site-key',
            'turnstile.secret_key' => 'test-secret-key',
            'turnstile.bypass_admin' => false,
            'turnstile.points.tpix_withdrawal' => true,
        ]);
    }

    private function submit(array $data = []): TestResponse
    {
        return $this->from(route('user.tpix.withdrawal'))
            ->post(route('user.tpix.withdrawal.process'), array_merge([
                'address' => self::ADDRESS,
                'amount' => '2.5',
            ], $data));
    }

    public function test_page_opens_with_balance_limits_and_form_fields(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $currency = $this->makeCurrency();
        $this->makeWallet($user, $currency, '12.5');

        $response = $this->actingAs($user)->get(route('user.tpix.withdrawal'));

        $response->assertOk()
            ->assertViewIs('user.tpix.withdrawal')
            ->assertSee('ถอน TPIX')
            ->assertSee('12.50000000')
            ->assertSee('0.10000000')
            ->assertSee('0.01000000')
            ->assertSee('ยังไม่มีรายการถอน')
            ->assertSee('action="'.route('user.tpix.withdrawal.process').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="address"', false)
            ->assertSee('name="amount"', false);
    }

    public function test_page_lists_only_the_members_own_withdrawals(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);
        $currency = $this->makeCurrency();
        $wallet = $this->makeWallet($user, $currency);
        $otherWallet = $this->makeWallet($other, $currency);

        $this->makeWithdrawal($user, $currency, $wallet, '0xaaaa00000000000000000000000000000000bbbb');
        $this->makeWithdrawal($other, $currency, $otherWallet, '0xcccc00000000000000000000000000000000dddd');

        $this->actingAs($user)->get(route('user.tpix.withdrawal'))
            ->assertOk()
            ->assertSee('0xaaaa')
            ->assertDontSee('0xcccc')
            ->assertSee('รอดำเนินการ')
            ->assertDontSee('ยังไม่มีรายการถอน');
    }

    public function test_page_carries_the_turnstile_widget_when_keys_are_set(): void
    {
        $this->useTurnstileKeys();
        $user = User::factory()->create(['role' => 'user']);
        $this->makeWallet($user, $this->makeCurrency());

        $this->actingAs($user)->get(route('user.tpix.withdrawal'))
            ->assertOk()
            ->assertSee('data-sitekey="test-site-key"', false);
    }

    public function test_page_shows_no_widget_without_a_site_key(): void
    {
        config(['turnstile.enabled' => true, 'turnstile.site_key' => '', 'turnstile.points.tpix_withdrawal' => true]);
        $user = User::factory()->create(['role' => 'user']);
        $this->makeWallet($user, $this->makeCurrency());

        $this->actingAs($user)->get(route('user.tpix.withdrawal'))
            ->assertOk()
            ->assertDontSee('cf-turnstile');
    }

    public function test_balance_too_low_disables_the_submit_button_and_explains_why(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->makeWallet($user, $this->makeCurrency(), '0.05');

        $this->actingAs($user)->get(route('user.tpix.withdrawal'))
            ->assertOk()
            ->assertSee('ยอดคงเหลือไม่ถึงขั้นต่ำ');
    }

    public function test_suspended_wallet_sees_why_it_cannot_withdraw(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->makeWallet($user, $this->makeCurrency(), '10', 'suspended');

        $this->actingAs($user)->get(route('user.tpix.withdrawal'))
            ->assertOk()
            ->assertSee('ถูกระงับการใช้งาน');
    }

    public function test_page_redirects_with_a_thai_message_when_withdrawal_is_off(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->makeWallet($user, $this->makeCurrency(['withdrawal_enabled' => false]));

        $this->actingAs($user)->get(route('user.tpix.withdrawal'))
            ->assertRedirect(route('user.tpix.wallet'))
            ->assertSessionHas('error', 'ขณะนี้ปิดการถอน TPIX ชั่วคราว');
    }

    public function test_page_requires_login(): void
    {
        $this->makeCurrency();

        $this->get(route('user.tpix.withdrawal'))->assertRedirect();
    }

    public function test_post_deducts_amount_plus_fee_and_records_a_pending_withdrawal(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $currency = $this->makeCurrency();
        $wallet = $this->makeWallet($user, $currency);

        $this->actingAs($user)->submit()
            ->assertRedirect(route('user.tpix.wallet'))
            ->assertSessionHas('success');

        $this->assertSame('7.490000000000000000', $wallet->fresh()->balance);

        $transaction = CryptoTransaction::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('withdrawal', $transaction->type);
        $this->assertSame('pending', $transaction->status);
        $this->assertSame('tpix', $transaction->network);
        $this->assertSame(self::ADDRESS, $transaction->to_address);
        $this->assertEquals(2.5, (float) $transaction->amount);
        $this->assertEquals(0.01, (float) $transaction->fee);
        $this->assertNotEmpty($transaction->transaction_id);
    }

    public function test_post_can_withdraw_the_exact_balance_without_float_rounding_blocking_it(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $currency = $this->makeCurrency();
        $wallet = $this->makeWallet($user, $currency, '0.11');

        $this->actingAs($user)->submit(['amount' => '0.1'])
            ->assertRedirect(route('user.tpix.wallet'));

        $this->assertSame('0.000000000000000000', $wallet->fresh()->balance);
    }

    /** @return array<string, array{array<string, string>, string}> */
    public static function refusedInputs(): array
    {
        return [
            'ที่อยู่ผิดรูปแบบ' => [['address' => '0x123'], 'ที่อยู่ปลายทางไม่ถูกต้อง'],
            'ต่ำกว่าขั้นต่ำ' => [['amount' => '0.05'], 'ขั้นต่ำ'],
            'เกินเพดาน' => [['amount' => '1000.5'], 'สูงสุด'],
            'ยอดไม่พอ' => [['amount' => '9.995'], 'ยอดคงเหลือไม่พอ'],
        ];
    }

    #[DataProvider('refusedInputs')]
    public function test_post_refuses_bad_input_without_touching_the_balance(array $data, string $message): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $currency = $this->makeCurrency();
        $wallet = $this->makeWallet($user, $currency);

        $this->actingAs($user)->submit($data)
            ->assertRedirect(route('user.tpix.withdrawal'))
            ->assertSessionHasInput('amount');

        $this->assertStringContainsString($message, (string) session('error'));
        $this->assertSame('10.000000000000000000', $wallet->fresh()->balance);
        $this->assertSame(0, CryptoTransaction::count());
    }

    /** @return array<string, array{array<string, string>}> */
    public static function invalidFields(): array
    {
        return [
            'ไม่กรอกที่อยู่' => [['address' => '']],
            'ไม่กรอกจำนวน' => [['amount' => '']],
            'จำนวนติดลบ' => [['amount' => '-5']],
            'ทศนิยมเกิน 8 ตำแหน่ง' => [['amount' => '0.123456789']],
            'เลขยกกำลัง' => [['amount' => '1e3']],
        ];
    }

    #[DataProvider('invalidFields')]
    public function test_post_rejects_invalid_fields(array $data): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $currency = $this->makeCurrency();
        $wallet = $this->makeWallet($user, $currency);

        $this->actingAs($user)->submit($data)
            ->assertRedirect(route('user.tpix.withdrawal'))
            ->assertSessionHasErrors();

        $this->assertSame('10.000000000000000000', $wallet->fresh()->balance);
        $this->assertSame(0, CryptoTransaction::count());
    }

    public function test_post_refuses_a_suspended_wallet(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $currency = $this->makeCurrency();
        $wallet = $this->makeWallet($user, $currency, '10', 'suspended');

        $this->actingAs($user)->submit()
            ->assertRedirect(route('user.tpix.withdrawal'))
            ->assertSessionHas('error', 'กระเป๋า TPIX ของคุณถูกระงับการใช้งาน');

        $this->assertSame('10.000000000000000000', $wallet->fresh()->balance);
        $this->assertSame(0, CryptoTransaction::count());
    }

    public function test_post_refuses_when_withdrawal_is_off(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $currency = $this->makeCurrency(['withdrawal_enabled' => false]);
        $wallet = $this->makeWallet($user, $currency);

        $this->actingAs($user)->submit()
            ->assertSessionHas('error', 'ขณะนี้ปิดการถอน TPIX ชั่วคราว');

        $this->assertSame('10.000000000000000000', $wallet->fresh()->balance);
    }

    public function test_post_takes_the_wallet_lock_inside_a_transaction(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $wallet = $this->makeWallet($user, $this->makeCurrency());
        $baseLevel = DB::transactionLevel();
        $lockLevels = [];

        DB::listen(function ($query) use (&$lockLevels) {
            if (str_contains($query->sql, 'for update')) {
                $lockLevels[] = DB::transactionLevel();
            }
        });

        $this->actingAs($user)->submit()->assertRedirect(route('user.tpix.wallet'));

        // lockForUpdate นอก transaction ปล่อยล็อกทันที กดซ้อนสองครั้งจะถอนเกินยอด
        $this->assertNotEmpty($lockLevels);
        $this->assertGreaterThan($baseLevel, min($lockLevels));
        $this->assertSame('7.490000000000000000', $wallet->fresh()->balance);
    }

    public function test_post_without_a_turnstile_token_is_refused(): void
    {
        $this->useTurnstileKeys();
        $user = User::factory()->create(['role' => 'user']);
        $currency = $this->makeCurrency();
        $wallet = $this->makeWallet($user, $currency);

        $this->actingAs($user)->submit()
            ->assertRedirect(route('user.tpix.withdrawal'))
            ->assertSessionHasErrors('turnstile');

        $this->assertSame('10.000000000000000000', $wallet->fresh()->balance);
        $this->assertSame(0, CryptoTransaction::count());
    }

    public function test_post_with_a_valid_turnstile_token_goes_through(): void
    {
        $this->useTurnstileKeys();
        Http::fake(['*' => Http::response(['success' => true])]);
        $user = User::factory()->create(['role' => 'user']);
        $currency = $this->makeCurrency();
        $wallet = $this->makeWallet($user, $currency);

        $this->actingAs($user)->submit(['cf-turnstile-response' => 'good-token'])
            ->assertRedirect(route('user.tpix.wallet'));

        $this->assertSame('7.490000000000000000', $wallet->fresh()->balance);
    }

    public function test_post_waits_for_two_factor_before_touching_the_balance(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $currency = $this->makeCurrency();
        $wallet = $this->makeWallet($user, $currency);

        TwoFactorSetting::create(['enabled' => true, 'require_on_withdrawal' => true]);
        TwoFactorSetting::clearCache();
        TwoFactorUserSetting::create(['user_id' => $user->id, 'enabled' => true]);

        $response = $this->actingAs($user)->submit();

        $this->assertStringContainsString('/two-factor/verify', (string) $response->headers->get('Location'));
        $this->assertSame('10.000000000000000000', $wallet->fresh()->balance);
        $this->assertSame(0, CryptoTransaction::count());
    }
}
