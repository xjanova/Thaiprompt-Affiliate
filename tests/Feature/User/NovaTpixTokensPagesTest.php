<?php

namespace Tests\Feature\User;

use App\Models\CryptoAddress;
use App\Models\CryptoCurrency;
use App\Models\CryptoTransaction;
use App\Models\CryptoWallet;
use App\Models\TPIXToken;
use App\Models\TPIXTokenBalance;
use App\Models\TPIXTokenTransfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * หน้าสมาชิกกลุ่ม TPIX (/user/tpix/*) และตลาดเหรียญ (/user/tokens/*) บนธีมโนวาเต็ม
 *
 * นอกจากเปลือกโนวาแล้ว ยังกันบั๊กที่แก้ไปพร้อมกัน:
 * - GET /user/tokens/create เคยตกไปหา route /{id} (404) และฟอร์มไม่มี action/method
 * - หน้ารายละเอียดเหรียญเคยเปิดร่างของคนอื่นได้ และโชว์ที่อยู่กระเป๋าของผู้ถือเต็ม ๆ
 * - ลิงก์ในอีเมล user.tokens.transactions เคยชี้ method ที่ไม่มี (500)
 */
class NovaTpixTokensPagesTest extends TestCase
{
    use RefreshDatabase;

    private const OTHER_ADDRESS = '0xabcdef0123456789abcdef0123456789abcd9876';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class);
        Http::fake();
    }

    private function member(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'user', 'kyc_status' => 'approved'], $attributes));
    }

    /**
     * หน้าต้องอยู่ในเปลือกโนวาเต็ม ไม่ใช่โหมดสะพาน V4
     */
    private function assertFullNova(string $html): void
    {
        $this->assertStringContainsString('nvm-body', $html);
        $this->assertStringContainsString('nova-member.css', $html);
        $this->assertStringContainsString('tpix-tokens.css', $html);
        $this->assertStringNotContainsString('class="nvm-v4"', $html, 'ยังเป็นโหมดสะพาน V4');
        $this->assertStringNotContainsString('tp-card', $html, 'ยังมีการ์ด V4 หลงอยู่');
        $this->assertStringNotContainsString('fas fa-', $html, 'ยังมีไอคอน Font Awesome หลงอยู่');
    }

    private function currency(): CryptoCurrency
    {
        return CryptoCurrency::create([
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
        ]);
    }

    private function wallet(User $user, CryptoCurrency $currency, string $balance = '12.5'): CryptoWallet
    {
        return CryptoWallet::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'wallet_type' => 'custodial',
            'balance' => $balance,
            'status' => 'active',
        ]);
    }

    private function transaction(CryptoWallet $wallet, array $overrides = []): CryptoTransaction
    {
        return CryptoTransaction::create(array_merge([
            'user_id' => $wallet->user_id,
            'crypto_currency_id' => $wallet->currency_id,
            'crypto_wallet_id' => $wallet->id,
            'type' => 'deposit',
            'amount' => 3.25,
            'fee' => 0,
            'status' => 'completed',
            'network' => 'tpix',
        ], $overrides));
    }

    private function token(User $creator, array $overrides = []): TPIXToken
    {
        return TPIXToken::create(array_merge([
            'creator_id' => $creator->id,
            'name' => 'CoffeeCoin',
            'symbol' => 'COFFEE',
            'description' => 'เหรียญสะสมแต้มร้านกาแฟ',
            'decimals' => 18,
            'total_supply' => '1000000',
            'current_price_tpix' => 0.01,
            'market_cap_tpix' => 10000,
            'status' => 'active',
            'is_listed' => true,
            'holders_count' => 2,
        ], $overrides));
    }

    // ==================== เปลือกโนวา: กลุ่ม TPIX ====================

    public function test_every_tpix_page_renders_in_the_full_nova_shell(): void
    {
        $user = $this->member();
        $currency = $this->currency();
        $wallet = $this->wallet($user, $currency);
        $tx = $this->transaction($wallet);

        foreach ([
            route('user.tpix.wallet'),
            route('user.tpix.deposit'),
            route('user.tpix.withdrawal'),
            route('user.tpix.send'),
            route('user.tpix.transactions'),
            route('user.tpix.transactions.details', $tx->id),
        ] as $url) {
            $response = $this->actingAs($user)->get($url);
            $response->assertOk();
            $this->assertFullNova($response->getContent());
        }
    }

    public function test_wallet_page_shows_real_data_instead_of_placeholders(): void
    {
        $user = $this->member();
        $currency = $this->currency();
        $wallet = $this->wallet($user, $currency);
        $tx = $this->transaction($wallet, ['amount' => 4.75]);
        $token = $this->token($this->member());
        TPIXTokenBalance::create(['token_id' => $token->id, 'user_id' => $user->id, 'wallet_address' => '0x'.str_repeat('1', 40), 'balance' => 250]);

        $this->actingAs($user)->get(route('user.tpix.wallet'))
            ->assertOk()
            ->assertSee('12.50000000')
            ->assertSee('+4.75000000')
            ->assertSee('CoffeeCoin')
            ->assertSee('250.0000')
            ->assertSee(route('user.tpix.transactions.details', $tx->id), false)
            // ตัวเลขปลอมของหน้าเดิม
            ->assertDontSee('+12.5%')
            ->assertDontSee('Rewards Earned')
            ->assertDontSee('กำลังโหลด');
    }

    public function test_wallet_page_does_not_show_a_deposit_address(): void
    {
        $user = $this->member();
        $this->currency();

        $response = $this->actingAs($user)->get(route('user.tpix.wallet'))->assertOk();

        $walletId = CryptoWallet::where('user_id', $user->id)->value('id');
        $address = CryptoAddress::where('crypto_wallet_id', $walletId)->value('address');
        if ($address) {
            $response->assertDontSee($address);
        }
    }

    public function test_transaction_details_of_another_member_is_not_found(): void
    {
        $currency = $this->currency();
        $otherTx = $this->transaction($this->wallet($this->member(), $currency));

        $this->actingAs($this->member())
            ->get(route('user.tpix.transactions.details', $otherTx->id))
            ->assertNotFound();
    }

    public function test_wallet_does_not_link_holdings_of_suspended_tokens(): void
    {
        $user = $this->member();
        $this->wallet($user, $this->currency());
        $active = $this->token($this->member());
        $suspended = $this->token($this->member(), ['name' => 'BannedCoin', 'symbol' => 'BAN', 'status' => 'suspended']);
        $mine = $this->token($user, ['name' => 'MyDraftCoin', 'symbol' => 'MINE', 'status' => 'draft']);
        foreach ([$active, $suspended, $mine] as $i => $t) {
            TPIXTokenBalance::create(['token_id' => $t->id, 'user_id' => $user->id, 'wallet_address' => '0x'.str_repeat((string) ($i + 1), 40), 'balance' => 10 + $i]);
        }

        $this->actingAs($user)->get(route('user.tpix.wallet'))
            ->assertOk()
            ->assertSee(route('user.tokens.show', $active->id), false)
            ->assertSee(route('user.tokens.show', $mine->id), false)
            ->assertSee('BannedCoin')
            ->assertSee('ถูกระงับ')
            ->assertDontSee(route('user.tokens.show', $suspended->id).'"', false);
    }

    public function test_withdrawal_form_keeps_its_action_and_fields(): void
    {
        $user = $this->member();
        $this->wallet($user, $this->currency());

        $this->actingAs($user)->get(route('user.tpix.withdrawal'))
            ->assertOk()
            ->assertSee('action="'.route('user.tpix.withdrawal.process').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="address"', false)
            ->assertSee('name="amount"', false)
            ->assertSee('nvm-turnstile', false);
    }

    public function test_send_form_keeps_its_action_and_one_time_token(): void
    {
        $user = $this->member();
        $this->wallet($user, $this->currency());

        $this->actingAs($user)->get(route('user.tpix.send'))
            ->assertOk()
            ->assertSee('action="'.route('user.tpix.send.process').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="transfer_token"', false)
            ->assertSee('name="recipient"', false)
            ->assertSee('name="amount"', false);
    }

    public function test_empty_filter_submit_is_not_treated_as_a_filter(): void
    {
        $user = $this->member();
        $this->wallet($user, $this->currency());

        $this->actingAs($user)
            ->get(route('user.tpix.transactions', ['type' => '', 'status' => '', 'date_from' => '', 'date_to' => '']))
            ->assertOk()
            ->assertSee('ยังไม่มีธุรกรรม')
            ->assertDontSee('ไม่พบรายการตามตัวกรองนี้')
            ->assertDontSee('ล้างตัวกรอง');
    }

    // ==================== เปลือกโนวา: ตลาดเหรียญ ====================

    public function test_every_token_page_renders_in_the_full_nova_shell(): void
    {
        $user = $this->member();
        $token = $this->token($this->member(), ['is_featured' => true]);

        foreach ([
            route('user.tokens.index'),
            route('user.tokens.tutorial'),
            route('user.tokens.create'),
            route('user.tokens.show', $token->id),
        ] as $url) {
            $response = $this->actingAs($user)->get($url);
            $response->assertOk();
            $this->assertFullNova($response->getContent());
        }
    }

    public function test_create_page_opens_and_posts_to_the_store_route(): void
    {
        // เดิม /user/tokens/create ตกไปหา show('create') → 404 และฟอร์มไม่มี action/method
        $this->actingAs($this->member())->get('/user/tokens/create')
            ->assertOk()
            ->assertViewIs('user.tokens.create')
            ->assertSee('action="'.route('user.tokens.store').'"', false)
            ->assertSee('method="POST"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="github"', false)
            ->assertSee('name="telegram"', false)
            ->assertSee('name="twitter"', false)
            ->assertDontSee('name="category"', false);
    }

    public function test_store_saves_a_draft_with_checkbox_and_social_fields_and_ignores_extra_input(): void
    {
        $user = $this->member();
        $referrer = $this->member();

        $response = $this->actingAs($user)->post(route('user.tokens.store'), [
            'name' => 'Rice Token',
            'symbol' => 'rice',
            'total_supply' => '5000000',
            'decimals' => '18',
            'description' => 'เหรียญชาวนา',
            'is_mintable' => '0',
            'is_burnable' => '1',
            'is_pausable' => '1',
            'is_freezable' => '0',
            'initial_price_tpix' => '0.5',
            'website' => 'https://rice.example',
            'github' => 'https://github.com/rice',
            'telegram' => 'https://t.me/rice',
            'twitter' => '@ricetoken',
            // ช่องที่ฟอร์มไม่มี — ต้องไม่ถูกบันทึก
            'referred_by' => $referrer->id,
            'kyc_required' => '1',
        ]);

        $token = TPIXToken::where('creator_id', $user->id)->firstOrFail();
        $response->assertRedirect(route('user.tokens.show', $token->id))->assertSessionHas('success');

        $this->assertSame('RICE', $token->symbol);
        $this->assertSame('draft', $token->status);
        $this->assertFalse($token->is_mintable);
        $this->assertTrue($token->is_burnable);
        $this->assertTrue($token->is_pausable);
        $this->assertSame('https://github.com/rice', $token->github);
        $this->assertSame('https://t.me/rice', $token->telegram);
        $this->assertSame('@ricetoken', $token->twitter);
        $this->assertNull($token->referred_by);
        $this->assertFalse($token->kyc_required);
    }

    public function test_store_shows_thai_validation_errors(): void
    {
        $this->actingAs($this->member())
            ->from(route('user.tokens.create'))
            ->post(route('user.tokens.store'), ['symbol' => 'BAD SYMBOL!', 'decimals' => '18'])
            ->assertRedirect(route('user.tokens.create'))
            ->assertSessionHasErrors(['name' => 'กรุณากรอกชื่อเหรียญ', 'symbol' => 'สัญลักษณ์ใช้ได้เฉพาะตัวอักษรอังกฤษและตัวเลข']);

        $this->assertSame(0, TPIXToken::count());
    }

    public function test_store_rejects_a_price_too_large_for_the_column_with_a_thai_message(): void
    {
        $this->actingAs($this->member())
            ->from(route('user.tokens.create'))
            ->post(route('user.tokens.store'), [
                'name' => 'BigCoin', 'symbol' => 'BIG', 'total_supply' => '1000', 'decimals' => '18',
                'initial_price_tpix' => '1000000000000',
            ])
            ->assertRedirect(route('user.tokens.create'))
            ->assertSessionHasErrors(['initial_price_tpix' => 'ราคาเริ่มต้นสูงสุดไม่เกิน 999,999,999,999 TPIX']);

        $this->assertSame(0, TPIXToken::count());
    }

    public function test_index_lists_my_own_drafts_but_not_other_members_drafts(): void
    {
        $user = $this->member();
        $this->token($user, ['name' => 'MyDraftCoin', 'symbol' => 'MYD', 'status' => 'draft', 'is_listed' => false]);
        $this->token($this->member(), ['name' => 'SecretDraftCoin', 'symbol' => 'SEC', 'status' => 'draft', 'is_listed' => false]);
        $this->token($this->member(), ['name' => 'ListedCoin', 'symbol' => 'LST']);

        $this->actingAs($user)->get(route('user.tokens.index'))
            ->assertOk()
            ->assertSee('เหรียญที่ฉันสร้าง')
            ->assertSee('MyDraftCoin')
            ->assertSee('ListedCoin')
            ->assertDontSee('SecretDraftCoin');
    }

    public function test_index_search_and_sort_use_plain_get_parameters(): void
    {
        $this->token($this->member(), ['name' => 'Alpha Coin', 'symbol' => 'ALP']);
        $this->token($this->member(), ['name' => 'Beta Coin', 'symbol' => 'BET']);

        $this->actingAs($this->member())->get(route('user.tokens.index', ['search' => 'Alpha', 'sort' => 'holders']))
            ->assertOk()
            ->assertSee('Alpha Coin')
            ->assertDontSee('Beta Coin')
            ->assertSee('<option value="holders" selected', false);
    }

    // ==================== สิทธิ์และความเป็นส่วนตัวหน้ารายละเอียดเหรียญ ====================

    public function test_another_members_draft_token_is_not_found(): void
    {
        $draft = $this->token($this->member(), ['status' => 'draft', 'is_listed' => false]);

        $this->actingAs($this->member())->get(route('user.tokens.show', $draft->id))->assertNotFound();
    }

    public function test_creator_can_open_their_own_draft(): void
    {
        $creator = $this->member();
        $draft = $this->token($creator, ['status' => 'draft', 'is_listed' => false]);

        $this->actingAs($creator)->get(route('user.tokens.show', $draft->id))
            ->assertOk()
            ->assertSee('เหรียญของคุณอยู่ในสถานะร่าง');
    }

    public function test_non_numeric_token_id_is_not_found(): void
    {
        $this->actingAs($this->member())->get('/user/tokens/abc')->assertNotFound();
    }

    public function test_show_masks_other_holders_addresses_and_renders_server_side_data(): void
    {
        $user = $this->member();
        $other = $this->member(['email' => 'holder-secret@example.com']);
        $token = $this->token($this->member());
        $myAddress = '0x'.str_repeat('2', 40);
        TPIXTokenBalance::create(['token_id' => $token->id, 'user_id' => $other->id, 'wallet_address' => self::OTHER_ADDRESS, 'balance' => 900]);
        TPIXTokenBalance::create(['token_id' => $token->id, 'user_id' => $user->id, 'wallet_address' => $myAddress, 'balance' => 100]);
        TPIXTokenTransfer::create([
            'token_id' => $token->id,
            'tx_hash' => '0xhash1',
            'from_address' => self::OTHER_ADDRESS,
            'to_address' => $myAddress,
            'amount' => 100,
            'from_user_id' => $other->id,
            'to_user_id' => $user->id,
            'status' => 'confirmed',
        ]);

        $this->actingAs($user)->get(route('user.tokens.show', $token->id))
            ->assertOk()
            ->assertSee('100.0000')
            ->assertSee('0xabcd…9876')
            ->assertSee('คุณ')
            ->assertDontSee(self::OTHER_ADDRESS)
            ->assertDontSee($myAddress)
            ->assertDontSee('holder-secret@example.com')
            // ปุ่มซื้อ/ขายที่เปิดกล่อง "กำลังพัฒนา" ถูกเอาออก
            ->assertDontSee('กำลังพัฒนา')
            ->assertDontSee('/api/v1/tpix/balances');
    }

    public function test_show_does_not_render_javascript_urls_as_links(): void
    {
        $token = $this->token($this->member(), ['website' => 'javascript:alert(1)', 'github' => 'https://github.com/ok']);

        $this->actingAs($this->member())->get(route('user.tokens.show', $token->id))
            ->assertOk()
            ->assertSee('https://github.com/ok', false)
            ->assertDontSee('javascript:alert(1)', false);
    }

    public function test_token_transactions_link_from_email_redirects_to_the_transfers_section(): void
    {
        // เดิม route ชี้ TokenController::transactions ที่ไม่มีอยู่ → 500
        $token = $this->token($this->member());

        $this->actingAs($this->member())->get(route('user.tokens.transactions', $token->id))
            ->assertRedirect(route('user.tokens.show', $token->id).'#transfers');
    }

    public function test_dead_token_routes_were_removed(): void
    {
        foreach (['show-transfer', 'buy', 'sell', 'referrals', 'referrals.use', 'referrals.verify'] as $name) {
            $this->assertFalse(Route::has('user.tokens.'.$name), "route user.tokens.{$name} ควรถูกลบแล้ว");
        }
        foreach (['index', 'tutorial', 'create', 'store', 'show', 'deploy', 'transfer', 'transactions'] as $name) {
            $this->assertTrue(Route::has('user.tokens.'.$name), "route user.tokens.{$name} ต้องยังอยู่");
        }
    }
}
