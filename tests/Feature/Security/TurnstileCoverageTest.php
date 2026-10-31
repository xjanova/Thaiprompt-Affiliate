<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\VerifyCloudfareTurnstile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * ตรวจว่า Turnstile คุมครบทุกจุดที่ route ประกาศไว้ และไม่แสดงกล่องเสียตอนยังไม่มี key
 */
class TurnstileCoverageTest extends TestCase
{
    use RefreshDatabase;

    private function useKeys(string $siteKey = 'test-site-key', string $secretKey = 'test-secret-key'): void
    {
        config([
            'turnstile.enabled' => true,
            'turnstile.site_key' => $siteKey,
            'turnstile.secret_key' => $secretKey,
            'turnstile.bypass_admin' => false,
        ]);
    }

    /**
     * ส่งคำขอผ่าน middleware ตรงๆ เพื่อตัดเงื่อนไขของ route (PIN, กระเป๋า, 2FA) ออกจากการทดสอบ
     */
    private function runMiddleware(string $point, ?string $token): int
    {
        $request = Request::create('/withdraw', 'POST', array_filter(['cf-turnstile-response' => $token]));
        $request->headers->set('Accept', 'application/json');

        return (new VerifyCloudfareTurnstile)
            ->handle($request, fn () => response('passed'), $point)
            ->getStatusCode();
    }

    /**
     * render view ตรงๆ เพราะหน้าจริงต้องมีกระเป๋าคริปโตที่เปิดใช้งาน ($errors มาจาก middleware กลุ่ม web)
     */
    private function renderCryptoWithdrawal(): string
    {
        $this->actingAs(User::factory()->create());
        view()->share('errors', new ViewErrorBag);

        return view('user.crypto-wallet.withdraw', ['balances' => [], 'currencies' => collect()])->render();
    }

    /** @return array<string, array{string}> */
    public static function withdrawalPoints(): array
    {
        return [
            'เงินบาท' => ['withdrawal_request'],
            'คริปโต' => ['crypto_withdrawal'],
            'TPIX' => ['tpix_withdrawal'],
        ];
    }

    public function test_every_point_a_route_uses_exists_in_the_config(): void
    {
        // อ่านจากไฟล์ route ตรงๆ — route:list สร้าง controller ทุกตัว ซึ่งล้มเมื่อไม่มี provider ของ KnowledgeBaseController
        $points = [];
        foreach (glob(base_path('routes/*.php')) as $file) {
            preg_match_all('/turnstile:([a-z_]+)/', file_get_contents($file), $matches);
            $points = array_merge($points, $matches[1]);
        }

        $this->assertNotEmpty($points);
        $this->assertSame([], array_values(array_diff(array_unique($points), array_keys(config('turnstile.points')))));
    }

    #[DataProvider('withdrawalPoints')]
    public function test_withdrawal_without_a_token_is_refused(string $point): void
    {
        $this->useKeys();

        $this->assertSame(422, $this->runMiddleware($point, null));
    }

    #[DataProvider('withdrawalPoints')]
    public function test_withdrawal_with_a_rejected_token_is_refused(string $point): void
    {
        $this->useKeys();
        Http::fake(['*' => Http::response(['success' => false, 'error-codes' => ['invalid-input-response']])]);

        $this->assertSame(422, $this->runMiddleware($point, 'bad-token'));
    }

    #[DataProvider('withdrawalPoints')]
    public function test_withdrawal_with_a_valid_token_passes(string $point): void
    {
        $this->useKeys();
        Http::fake(['*' => Http::response(['success' => true])]);

        $this->assertSame(200, $this->runMiddleware($point, 'good-token'));
    }

    public function test_the_withdrawal_switch_can_turn_a_point_off(): void
    {
        $this->useKeys();
        config(['turnstile.points.crypto_withdrawal' => false]);

        $this->assertSame(200, $this->runMiddleware('crypto_withdrawal', null));
    }

    /** @return array<string, array{string}> */
    public static function authPages(): array
    {
        return [
            'เข้าสู่ระบบ' => ['/login'],
            'สมัครสมาชิก' => ['/register'],
        ];
    }

    #[DataProvider('authPages')]
    public function test_auth_page_shows_no_widget_without_a_site_key(string $path): void
    {
        $this->useKeys('', '');

        $this->get($path)->assertOk()
            ->assertDontSee('cf-turnstile')
            ->assertDontSee('challenges.cloudflare.com');
    }

    #[DataProvider('authPages')]
    public function test_auth_page_shows_the_widget_with_a_site_key(string $path): void
    {
        $this->useKeys();

        $this->get($path)->assertOk()
            ->assertSee('data-sitekey="test-site-key"', false)
            ->assertSee('challenges.cloudflare.com/turnstile/v0/api.js', false);
    }

    public function test_crypto_withdrawal_form_carries_the_widget(): void
    {
        $this->useKeys();

        $html = $this->renderCryptoWithdrawal();

        $this->assertStringContainsString('data-sitekey="test-site-key"', $html);
        // สคริปต์ที่ component โหลดเอง — ต้องไม่อ้าง onload callback ที่ไม่มีจริง
        $this->assertStringContainsString('api.js?render=explicit', $html);
        $this->assertStringNotContainsString('onload=', $html);
    }

    public function test_crypto_withdrawal_form_shows_no_widget_without_a_site_key(): void
    {
        $this->useKeys('', '');

        $html = $this->renderCryptoWithdrawal();

        $this->assertStringNotContainsString('cf-turnstile', $html);
    }
}
