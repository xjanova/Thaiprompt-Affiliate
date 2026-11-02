<?php

namespace Tests\Feature\User;

use App\Models\CryptoAddress;
use App\Models\CryptoCurrency;
use App\Models\CryptoExchangeRate;
use App\Models\CryptoExchangeTransaction;
use App\Models\CryptoTransaction;
use App\Models\CryptoWallet;
use App\Models\CryptoWithdrawalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * หน้ากระเป๋าคริปโตของสมาชิกธีมโนวาเต็ม (/user/crypto-wallet/*)
 */
class NovaCryptoWalletPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class);
        Http::fake();
        Cache::flush();
    }

    private function member(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'user', 'kyc_status' => 'approved'], $attributes));
    }

    private function currency(string $code = 'BTC', array $attributes = []): CryptoCurrency
    {
        return CryptoCurrency::forceCreate(array_merge([
            'code' => $code,
            'name' => $code === 'BTC' ? 'Bitcoin' : $code.' Coin',
            'symbol' => $code,
            'network' => $code === 'BTC' ? 'bitcoin' : 'ethereum',
            'min_deposit' => 0.0001,
            'min_withdrawal' => 0.001,
            'withdrawal_fee' => 0.0005,
            'is_active' => true,
            'deposit_enabled' => true,
            'withdrawal_enabled' => true,
            'exchange_enabled' => true,
        ], $attributes));
    }

    private function rate(CryptoCurrency $currency, float $thb = 1000000): CryptoExchangeRate
    {
        return CryptoExchangeRate::forceCreate([
            'crypto_currency_id' => $currency->id,
            'rate_thb' => $thb,
            'buy_rate_thb' => $thb * 1.01,
            'sell_rate_thb' => $thb * 0.99,
            'source' => 'manual',
            'price_change_24h' => 2.5,
            'valid_from' => now()->subMinute(),
            'valid_until' => now()->addDay(),
        ]);
    }

    private function wallet(User $owner, array $attributes = []): CryptoWallet
    {
        return CryptoWallet::forceCreate(array_merge([
            'user_id' => $owner->id,
            'wallet_type' => 'custodial',
            'name' => 'กระเป๋าทดสอบ',
            'status' => 'active',
            'is_default' => true,
            'pin_hash' => Hash::make('123456'),
        ], $attributes));
    }

    private function address(CryptoWallet $wallet, CryptoCurrency $currency, float $balance = 0.5, array $attributes = []): CryptoAddress
    {
        return CryptoAddress::forceCreate(array_merge([
            'crypto_wallet_id' => $wallet->id,
            'crypto_currency_id' => $currency->id,
            'address' => '0x'.str_repeat('a', 40),
            'network' => $currency->network,
            'balance' => $balance,
            'is_active' => true,
        ], $attributes));
    }

    private function transaction(User $owner, CryptoWallet $wallet, CryptoCurrency $currency, array $attributes = []): CryptoTransaction
    {
        return CryptoTransaction::forceCreate(array_merge([
            'user_id' => $owner->id,
            'crypto_wallet_id' => $wallet->id,
            'crypto_currency_id' => $currency->id,
            'transaction_id' => 'CRTX'.strtoupper(\Illuminate\Support\Str::random(20)),
            'network' => $currency->network,
            'type' => 'deposit',
            'amount' => 0.25,
            'fee' => 0.0001,
            'status' => 'completed',
            'from_address' => '0x'.str_repeat('b', 40),
            'to_address' => '0x'.str_repeat('a', 40),
            'tx_hash' => '0x'.str_repeat('c', 64),
        ], $attributes));
    }

    /**
     * สมาชิกที่มีกระเป๋า + เหรียญ + ราคา + ประวัติครบ
     */
    private function richMember(): array
    {
        $user = $this->member();
        $btc = $this->currency('BTC', ['explorer_url' => 'https://example-explorer.test']);
        $this->rate($btc);
        $wallet = $this->wallet($user);
        $this->address($wallet, $btc);
        $tx = $this->transaction($user, $wallet, $btc);

        return [$user, $btc, $wallet, $tx];
    }

    /**
     * หน้าต้องอยู่ในเปลือกโนวาเต็ม ไม่ใช่โหมดสะพาน V4
     */
    private function assertFullNova(string $html): void
    {
        $this->assertStringContainsString('nvm-body', $html);
        $this->assertStringNotContainsString('class="nvm-v4"', $html, 'ยังเป็นโหมดสะพาน V4');
        $this->assertStringNotContainsString('tp-card', $html, 'ยังมีการ์ด V4 หลงอยู่');
        $this->assertStringContainsString('nova-member.css', $html);
        $this->assertStringContainsString('theme-nova/member/crypto-wallet.css', $html);
    }

    public static function pageProvider(): array
    {
        return [
            'index' => ['user.crypto-wallet.index'],
            'wallets' => ['user.crypto-wallet.wallets'],
            'wallet-management' => ['user.crypto-wallet.wallet-management'],
            'trading' => ['user.crypto-wallet.trading'],
            'portfolio' => ['user.crypto-wallet.portfolio'],
            'deposit' => ['user.crypto-wallet.deposit'],
            'transactions' => ['user.crypto-wallet.transactions'],
            'withdraw' => ['user.crypto-wallet.withdraw'],
            'withdrawals' => ['user.crypto-wallet.withdrawals'],
            'exchange' => ['user.crypto-wallet.exchange'],
            'exchange-history' => ['user.crypto-wallet.exchange.history'],
        ];
    }

    /**
     * @dataProvider pageProvider
     */
    public function test_page_renders_full_nova_with_data(string $route): void
    {
        [$user, $btc, $wallet] = $this->richMember();
        CryptoWithdrawalRequest::forceCreate([
            'user_id' => $user->id, 'crypto_wallet_id' => $wallet->id, 'crypto_currency_id' => $btc->id,
            'to_address' => '0x'.str_repeat('d', 40), 'network' => 'bitcoin', 'amount' => 0.1,
            'network_fee' => 0.0005, 'net_amount' => 0.0995, 'status' => 'pending',
        ]);
        CryptoExchangeTransaction::forceCreate([
            'user_id' => $user->id, 'type' => 'buy', 'from_currency_type' => 'fiat', 'from_currency_code' => 'THB',
            'from_amount' => 1000, 'to_currency_type' => 'crypto', 'to_currency_code' => 'BTC', 'to_amount' => 0.001,
            'exchange_rate' => 1010000, 'fee_currency' => 'THB', 'final_from_amount' => 1000, 'final_to_amount' => 0.00099,
            'status' => 'completed', 'to_crypto_wallet_id' => $wallet->id,
        ]);

        $response = $this->actingAs($user)->get(route($route));

        $response->assertOk();
        $this->assertFullNova($response->getContent());
    }

    /**
     * @dataProvider pageProvider
     */
    public function test_page_renders_for_member_without_data(string $route): void
    {
        $user = $this->member();
        $this->wallet($user);

        $response = $this->actingAs($user)->get(route($route));

        $response->assertOk();
        $this->assertFullNova($response->getContent());
    }

    public function test_deposit_currency_and_transaction_detail_render(): void
    {
        [$user, $btc, , $tx] = $this->richMember();

        $deposit = $this->actingAs($user)->get(route('user.crypto-wallet.deposit.currency', 'btc'));
        $deposit->assertOk();
        $this->assertFullNova($deposit->getContent());

        $detail = $this->actingAs($user)->get(route('user.crypto-wallet.transaction.detail', $tx->id));
        $detail->assertOk();
        $this->assertFullNova($detail->getContent());
    }

    /**
     * เดิม 500 — หน้าแลกเปลี่ยนอ่าน buy_price ที่ไม่มี (คีย์จริง buy_rate) และฟอร์มส่ง currency_id แทน currency_code
     */
    public function test_exchange_page_shows_real_rates_and_correct_field_names(): void
    {
        [$user] = $this->richMember();

        $response = $this->actingAs($user)->get(route('user.crypto-wallet.exchange'));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('name="currency_code"', $html);
        $this->assertStringContainsString('name="accept_terms"', $html);
        $this->assertStringNotContainsString('name="currency_id"', $html);
        $this->assertStringContainsString('1,010,000.00', $html, 'ต้องแสดงราคาซื้อจริงจาก buy_rate_thb');
    }

    /**
     * เดิม 500 — eager load relation balances ที่ไม่มีอยู่
     */
    public function test_wallet_management_lists_wallet_assets(): void
    {
        [$user] = $this->richMember();

        $this->actingAs($user)->get(route('user.crypto-wallet.wallet-management'))
            ->assertOk()
            ->assertSee('กระเป๋าทดสอบ')
            ->assertSee('BTC');
    }

    /**
     * เดิม 500 ($cryptoAddress->wallet เป็น null) และโชว์ที่อยู่สุ่มที่ไม่มี private key
     * ใครโอนเหรียญจริงเข้าไปจะสูญหาย — ตอนนี้ต้องไม่แสดงที่อยู่ที่รับเหรียญไม่ได้
     */
    public function test_deposit_currency_hides_address_that_cannot_receive(): void
    {
        $user = $this->member();
        $this->currency('BTC');
        $this->wallet($user);

        $response = $this->actingAs($user)->get(route('user.crypto-wallet.deposit.currency', 'btc'));

        $response->assertOk();
        $response->assertSee('ยังเปิดรับฝาก', false);
        $this->assertStringNotContainsString('nvm-qr__box', $response->getContent());
    }

    public function test_deposit_currency_shows_imported_address(): void
    {
        $user = $this->member();
        $btc = $this->currency('BTC');
        $wallet = $this->wallet($user);
        $this->address($wallet, $btc, 0, ['address' => 'bc1qrealimportedaddress0000000000000', 'is_imported' => true]);
        \App\Models\CryptoDepositAddress::forceCreate([
            'user_id' => $user->id, 'crypto_wallet_id' => $wallet->id, 'crypto_currency_id' => $btc->id,
            'address' => 'bc1qrealimportedaddress0000000000000', 'network' => $btc->network, 'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('user.crypto-wallet.deposit.currency', 'btc'));

        $response->assertOk();
        $response->assertSee('bc1qrealimportedaddress0000000000000');
        $response->assertDontSee('ยังเปิดรับฝาก', false);
    }

    /**
     * เดิม strtoupper() กับโมเดล → 500 และลิงก์ etherscan ตายตัว
     */
    public function test_transaction_detail_shows_hash_and_coin_explorer(): void
    {
        [$user, , , $tx] = $this->richMember();

        $response = $this->actingAs($user)->get(route('user.crypto-wallet.transaction.detail', $tx->id));

        $response->assertOk();
        $response->assertSee($tx->tx_hash);
        $response->assertSee($tx->transaction_id);
        $this->assertStringNotContainsString('etherscan.io', $response->getContent());
    }

    public function test_member_cannot_view_another_members_transaction(): void
    {
        [, , , $tx] = $this->richMember();
        $other = $this->member();
        $this->wallet($other);

        $this->actingAs($other)->get(route('user.crypto-wallet.transaction.detail', $tx->id))->assertNotFound();
    }

    /**
     * เดิมได้ 403 ทุกครั้ง (authorize อ่าน route('wallet') ที่ไม่มี + บังคับ wallet_id ที่ฟอร์มไม่ส่ง)
     */
    public function test_owner_can_set_default_wallet(): void
    {
        $user = $this->member();
        $first = $this->wallet($user);
        $second = $this->wallet($user, ['name' => 'กระเป๋าใบที่สอง', 'is_default' => false]);

        $this->actingAs($user)
            ->from(route('user.crypto-wallet.wallets'))
            ->post(route('user.crypto-wallet.wallet.set-default', $second->id))
            ->assertRedirect(route('user.crypto-wallet.wallets'))
            ->assertSessionHas('success');

        $this->assertTrue((bool) $second->fresh()->is_default);
        $this->assertFalse((bool) $first->fresh()->is_default);
    }

    /**
     * สมาชิกที่ยังไม่มีกระเป๋าหลักต้องตั้งได้ (ไม่วนกลับ /wallets จาก middleware crypto.wallet.exists)
     */
    public function test_member_without_default_can_set_one(): void
    {
        $user = $this->member();
        $wallet = $this->wallet($user, ['is_default' => false]);

        $this->actingAs($user)
            ->from(route('user.crypto-wallet.wallets'))
            ->post(route('user.crypto-wallet.wallet.set-default', $wallet->id))
            ->assertSessionHas('success');

        $this->assertTrue((bool) $wallet->fresh()->is_default);
    }

    public function test_member_cannot_touch_another_members_wallet(): void
    {
        $owner = $this->member();
        $wallet = $this->wallet($owner, ['is_default' => false]);
        $intruder = $this->member();
        $this->wallet($intruder);

        $this->actingAs($intruder)
            ->post(route('user.crypto-wallet.wallet.set-default', $wallet->id))
            ->assertForbidden();
        $this->actingAs($intruder)
            ->delete(route('user.crypto-wallet.wallet.delete', $wallet->id))
            ->assertNotFound();

        $this->assertFalse((bool) $wallet->fresh()->is_default);
        $this->assertNotNull(CryptoWallet::find($wallet->id));
    }

    public function test_member_cannot_cancel_another_members_withdrawal(): void
    {
        [$owner, $btc, $wallet] = $this->richMember();
        $withdrawal = CryptoWithdrawalRequest::forceCreate([
            'user_id' => $owner->id, 'crypto_wallet_id' => $wallet->id, 'crypto_currency_id' => $btc->id,
            'to_address' => '0x'.str_repeat('d', 40), 'network' => 'bitcoin', 'amount' => 0.1,
            'network_fee' => 0.0005, 'net_amount' => 0.0995, 'status' => 'pending',
        ]);
        $intruder = $this->member();
        $this->wallet($intruder);

        $this->actingAs($intruder)
            ->delete(route('user.crypto-wallet.withdrawal.cancel', $withdrawal->id))
            ->assertNotFound();

        $this->assertSame('pending', $withdrawal->fresh()->status);
    }

    /**
     * หน้ารายการไม่แสดงข้อมูลของสมาชิกคนอื่น
     */
    public function test_lists_never_show_another_members_records(): void
    {
        [, , , $tx] = $this->richMember();
        $other = $this->member();
        $this->wallet($other);

        $this->actingAs($other)->get(route('user.crypto-wallet.transactions'))
            ->assertOk()
            ->assertDontSee(Str::limit($tx->transaction_id, 14));
    }

    /**
     * เดิม has() กับค่าว่างจากตัวเลือก "ทั้งหมด" → กรองเป็นค่าว่างแล้วไม่เจออะไร
     */
    public function test_filters_with_empty_values_still_list_records(): void
    {
        [$user, , $wallet, $tx] = $this->richMember();
        CryptoExchangeTransaction::forceCreate([
            'user_id' => $user->id, 'type' => 'buy', 'from_currency_type' => 'fiat', 'from_currency_code' => 'THB',
            'from_amount' => 1000, 'to_currency_type' => 'crypto', 'to_currency_code' => 'BTC', 'to_amount' => 0.001,
            'exchange_rate' => 1010000, 'fee_currency' => 'THB', 'final_from_amount' => 1000, 'final_to_amount' => 0.00099,
            'status' => 'completed', 'to_crypto_wallet_id' => $wallet->id, 'exchange_id' => 'EXTESTFILTER01',
        ]);

        $this->actingAs($user)
            ->get(route('user.crypto-wallet.transactions', ['type' => '', 'currency' => '', 'status' => '']))
            ->assertOk()
            ->assertSee(Str::limit($tx->transaction_id, 14));

        $this->actingAs($user)
            ->get(route('user.crypto-wallet.exchange.history', ['type' => '', 'currency' => '', 'status' => '']))
            ->assertOk()
            ->assertSee('EXTESTFILTER01');
    }

    /**
     * เดิมฟอร์มถอนส่ง currency_id ไม่มี network และ accept_terms ไม่มี name — ส่งยังไงก็ไม่ผ่าน validation
     */
    public function test_withdraw_form_matches_request_fields(): void
    {
        [$user] = $this->richMember();

        $html = $this->actingAs($user)->get(route('user.crypto-wallet.withdraw'))->assertOk()->getContent();

        foreach (['name="currency_code"', 'name="network"', 'name="to_address"', 'name="amount"', 'name="pin"', 'name="accept_terms"', 'name="_token"'] as $field) {
            $this->assertStringContainsString($field, $html, "ฟอร์มถอนขาด {$field}");
        }
        $this->assertStringNotContainsString('name="currency_id"', $html);
    }

    /**
     * เดิมโชว์กำไร/ขาดทุนปลอม (ต้นทุน = มูลค่า × 0.85) และ order book สุ่มด้วย rand()
     */
    public function test_portfolio_and_markets_show_no_fake_numbers(): void
    {
        [$user] = $this->richMember();

        $portfolio = $this->actingAs($user)->get(route('user.crypto-wallet.portfolio'))->assertOk()->getContent();
        $this->assertStringNotContainsString('Total P&amp;L', $portfolio);
        $this->assertStringNotContainsString('Total Invested', $portfolio);

        $markets = $this->actingAs($user)->get(route('user.crypto-wallet.trading'))->assertOk()->getContent();
        $this->assertStringNotContainsString('Order Book', $markets);
        $this->assertStringContainsString('1,000,000.00', $markets);
    }
}
