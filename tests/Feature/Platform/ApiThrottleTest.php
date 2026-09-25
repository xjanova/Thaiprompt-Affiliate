<?php

namespace Tests\Feature\Platform;

use App\Http\Middleware\EnsureAccountActive;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * ล็อก rate limit + ตัวกันบัญชีถูกระงับของ API (audit CC-03 / CC-01)
 *
 * เดิม /api/v1/login และ /register ไม่มี throttle เลย และกลุ่ม /api/v1 ไม่มี rate limit
 * (Laravel 11 ไม่ใส่ throttle ให้กลุ่ม api เอง) → brute force รหัสผ่านผ่านแอปได้ไม่จำกัด
 *
 * ไม่ใช้ DB — ตรวจที่ตาราง route / middleware group / limiter ล้วนๆ
 */
class ApiThrottleTest extends TestCase
{
    public function test_login_and_register_are_throttled(): void
    {
        $login = $this->routeFor('POST', 'api/v1/login');
        $this->assertContains('throttle.login', $login->gatherMiddleware(), 'login ต้องผ่าน ThrottleLogin (ล็อกหลังผิดหลายครั้ง)');
        $this->assertContains('throttle:6,1,api-login', $login->gatherMiddleware());
        $this->assertContains('throttle:api', $login->gatherMiddleware());

        $register = $this->routeFor('POST', 'api/v1/register');
        $this->assertContains('throttle:6,1,api-register', $register->gatherMiddleware());
    }

    /**
     * throttle แบบตัวเลขต้องมี prefix ของตัวเอง — ไม่งั้น ThrottleRequests ใช้ key = sha1(user id) ร่วมกันทุก route
     * (เพิ่มสินค้า 10 ครั้งแล้วกด checkout = 429 / ไรเดอร์ส่ง GPS ถี่แล้วกดส่งไม่สำเร็จไม่ได้)
     */
    public function test_numeric_throttles_on_app_routes_use_their_own_counter(): void
    {
        $prefixes = [];

        foreach ([
            ['POST', 'api/v1/cart/items'],
            ['POST', 'api/v1/cart/checkout'],
            ['POST', 'api/v1/cart/promo'],
            ['POST', 'api/v1/addresses'],
            ['POST', 'api/v1/wallet/withdraw'],
            ['POST', 'api/v1/wallet/pin'],
            ['POST', 'api/v1/wallet/bank-accounts'],
            ['POST', 'api/v1/orders/{id}/cancel'],
            ['POST', 'api/v1/events/batch'],
            ['DELETE', 'api/v1/account'],
            ['POST', 'api/v1/web-session'],
            ['POST', 'api/v1/rider/register'],
            ['POST', 'api/v1/rider/location'],
            ['POST', 'api/v1/rider/jobs/{id}/release'],
            ['POST', 'api/v1/rider/jobs/{id}/fail'],
            ['POST', 'api/v1/rider/jobs/{id}/deliver'],
            ['POST', 'api/v1/seller/orders/{orderId}/action'],
        ] as [$method, $uri]) {
            $numeric = array_values(array_filter(
                $this->routeFor($method, $uri)->gatherMiddleware(),
                fn ($m) => is_string($m) && preg_match('/^throttle:\d+,\d+/', $m) === 1
            ));

            $this->assertNotEmpty($numeric, "{$method} {$uri} ต้องมี throttle เฉพาะเส้น");

            foreach ($numeric as $middleware) {
                $parts = explode(',', substr($middleware, strlen('throttle:')));
                $this->assertCount(3, $parts, "{$method} {$uri}: {$middleware} ต้องมี prefix (throttle:N,M,ชื่อ)");
                $prefixes[$parts[2]][] = "{$method} {$uri}";
            }
        }

        // prefix ซ้ำกันได้เฉพาะเส้นทางที่ตั้งใจใช้ตัวนับร่วม (เช่น DELETE /account กับ POST /account/delete)
        foreach ($prefixes as $prefix => $routes) {
            $this->assertCount(1, array_unique($routes), "prefix {$prefix} ถูกใช้ซ้ำหลายเส้น: ".implode(', ', $routes));
        }
    }

    public function test_app_endpoints_carry_the_api_limiter(): void
    {
        foreach ([
            ['GET', 'api/v1/me'],
            ['POST', 'api/v1/logout'],
            ['POST', 'api/v1/cart/checkout'],
            ['POST', 'api/v1/web-session'],
            ['DELETE', 'api/v1/account'],
            ['GET', 'api/v1/account/deletion-check'],
            ['DELETE', 'api/v1/mobile/push-token'],
            ['POST', 'api/v1/push/token/remove'],
        ] as [$method, $uri]) {
            $this->assertContains('throttle:api', $this->routeFor($method, $uri)->gatherMiddleware(), "{$method} {$uri} ต้องมี throttle:api");
        }

        $this->assertContains('throttle:5,1,api-account-delete', $this->routeFor('DELETE', 'api/v1/account')->gatherMiddleware());
        $this->assertContains('throttle:20,1,api-web-session', $this->routeFor('POST', 'api/v1/web-session')->gatherMiddleware());
    }

    /**
     * webhook จากผู้ให้บริการภายนอกต้องไม่โดน limiter ของแอป (LINE/ชำระเงินยิงจาก IP ชุดเดียว)
     */
    public function test_webhooks_are_not_under_the_app_limiter(): void
    {
        foreach ([['POST', 'api/webhook/line'], ['POST', 'api/webhook/promptpay']] as [$method, $uri]) {
            $this->assertNotContains('throttle:api', $this->routeFor($method, $uri)->gatherMiddleware(), "{$uri} ห้ามโดน throttle:api");
        }
    }

    /**
     * ต้องมี limiter ชื่อ 'api' จริง — ไม่งั้น ThrottleRequests แปลง 'api' เป็น 0 = บล็อกทุก request
     */
    public function test_named_api_limiter_is_defined(): void
    {
        $limiter = RateLimiter::limiter('api');
        $this->assertNotNull($limiter, 'ต้องนิยาม RateLimiter::for(\'api\') ใน bootstrap/app.php');

        // ผู้ใช้ที่ล็อกอิน → นับต่อผู้ใช้
        $user = new User;
        $user->id = 12345;
        $authed = Request::create('/api/v1/me', 'GET');
        $authed->setUserResolver(fn () => $user);
        $limit = $limiter($authed);
        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertSame((int) config('ratelimit.api.authenticated', 120), $limit->maxAttempts);
        $this->assertSame('api-user:12345', $limit->key);

        // ผู้ใช้ทั่วไป เขียนข้อมูล → นับต่อ IP ตามค่าตั้งต้น
        $guestWrite = Request::create('/api/v1/register', 'POST', [], [], [], ['REMOTE_ADDR' => '203.0.113.9']);
        $guestLimit = $limiter($guestWrite);
        $this->assertSame((int) config('ratelimit.api.default', 60), $guestLimit->maxAttempts);
        $this->assertStringContainsString('203.0.113.9', $guestLimit->key);

        // อ่านอย่างเดียว (CGNAT) ได้โควต้ามากกว่า
        $guestRead = Request::create('/api/v1/app/config', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.9']);
        $this->assertGreaterThan($guestLimit->maxAttempts, $limiter($guestRead)->maxAttempts);
    }

    /**
     * ตัวกันบัญชีถูกระงับต้องอยู่ท้ายกลุ่ม web และ api (ครอบทุกกลุ่ม auth:sanctum ในทุกไฟล์ route)
     */
    public function test_account_active_guard_is_on_web_and_api_groups(): void
    {
        $groups = app('router')->getMiddlewareGroups();

        $this->assertContains(EnsureAccountActive::class, $groups['api']);
        $this->assertContains(EnsureAccountActive::class, $groups['web']);
        $this->assertSame(EnsureAccountActive::class, app('router')->getMiddleware()['account.active'] ?? null);
    }

    private function routeFor(string $method, string $uri): \Illuminate\Routing\Route
    {
        $match = null;
        foreach (Route::getRoutes() as $route) {
            if ($route->uri() === $uri && in_array($method, $route->methods(), true)) {
                $match = $route;
                break;
            }
        }

        $this->assertNotNull($match, "ไม่พบ route {$method} {$uri}");

        return $match;
    }
}
