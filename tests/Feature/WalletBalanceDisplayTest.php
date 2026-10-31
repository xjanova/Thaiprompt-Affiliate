<?php

namespace Tests\Feature;

use App\Models\MobileAuthToken;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * ยอดกระเป๋าเงินที่แสดงให้ผู้ใช้ต้องมาจากตาราง wallets — ต้องใช้ MySQL
 *
 * ตาราง users ไม่มีคอลัมน์ wallet_balance เดิมการ login ผ่านแอปมือถือและหน้าบริการ
 * จึงได้ยอด 0 ทุกคน แม้ในกระเป๋าจะมีเงินอยู่
 */
class WalletBalanceDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithWallet(float $balance): User
    {
        $user = User::factory()->create(['role' => 'user']);
        Wallet::create(['user_id' => $user->id, 'balance' => $balance, 'currency' => 'THB', 'status' => 'active']);

        return $user;
    }

    private function makeService(): Service
    {
        $category = ServiceCategory::create(['name' => 'นวด', 'slug' => 'massage-'.Str::random(6)]);

        return Service::create([
            'category_id' => $category->id,
            'owner_id' => User::factory()->create()->id,
            'name' => 'นวดไทย 1 ชั่วโมง',
            'slug' => 'thai-massage-'.Str::random(6),
            'base_price' => 500,
            'is_active' => true,
        ]);
    }

    /**
     * จำลองแอปแลก auth code เป็น token ตาม flow PKCE
     */
    private function exchangeMobileLogin(User $user): TestResponse
    {
        $authCode = Str::random(40);
        $codeVerifier = Str::random(64);
        $state = Str::random(32);

        MobileAuthToken::create([
            'login_token' => hash('sha256', Str::random(64)),
            'login_token_expires_at' => now()->addMinutes(5),
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '='),
            'auth_code' => hash('sha256', $authCode),
            'auth_code_expires_at' => now()->addMinutes(5),
            'state' => $state,
            'user_id' => $user->id,
            'device_id' => 'test-device-id',
        ]);

        return $this->postJson('/api/v1/auth/mobile/exchange', [
            'auth_code' => $authCode,
            'code_verifier' => $codeVerifier,
            'state' => $state,
        ]);
    }

    public function test_mobile_login_returns_balance_from_wallets_table(): void
    {
        $response = $this->exchangeMobileLogin($this->makeUserWithWallet(150.5));

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame(150.5, $response->json('data.user.wallet_balance'));
    }

    public function test_mobile_login_returns_zero_for_user_without_wallet(): void
    {
        $response = $this->exchangeMobileLogin(User::factory()->create());

        $response->assertOk();
        $this->assertEquals(0, $response->json('data.user.wallet_balance'));
    }

    public function test_service_page_shows_balance_from_wallets_table(): void
    {
        $this->withoutVite();
        $user = $this->makeUserWithWallet(1250.5);

        $this->actingAs($user)
            ->get(route('user.services.show', $this->makeService()))
            ->assertOk()
            ->assertSee('ยอดคงเหลือ: ฿1,250.50', false);
    }

    public function test_service_page_shows_zero_for_user_without_wallet(): void
    {
        $this->withoutVite();

        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get(route('user.services.show', $this->makeService()))
            ->assertOk()
            ->assertSee('ยอดคงเหลือ: ฿0.00', false);
    }
}
