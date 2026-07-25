<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * ล็อกชุด route ของ Passport ให้เหลือเฉพาะที่ SSO เว็บจันทราใช้จริง
 *
 * 🔐 (2026-07-26) กันช่องโหว่ที่ติดมาตั้งแต่เปิด Passport (2026-07-17):
 *    Passport ลงทะเบียน route ให้เอง 18 เส้น ซึ่งรวม POST /oauth/clients
 *    ที่ middleware มีแค่ 'web','auth' → ผู้ใช้ที่ล็อกอินคนไหนก็สร้าง OAuth client
 *    ของตัวเองได้ แล้วเอาไปหลอกลูกค้าที่ล็อกอินค้างให้กดหน้า consent
 *    → ได้ token ของเหยื่อไปยิง GET /api/user (หลุด id/email/name/line_user_id)
 *
 *    ซ้ำร้าย client ที่สร้างทางนั้นมี provider = null และ TokenGuard ของ Passport
 *    เช็คแบบ ($client->provider && ...) → null = falsy = ข้ามการเช็ค provider
 *
 * เทสต์นี้ล็อกไว้ 2 ทาง (ห้ามหลุดทั้งคู่):
 *   1. เส้น self-service client ต้องไม่มีอยู่จริง
 *   2. เส้นที่ SSO ใช้ต้องยังอยู่ครบ พร้อม middleware เดิมเป๊ะ
 *      — โดยเฉพาะ POST /oauth/token ที่ **ห้ามมี 'web'** ไม่งั้นโดน CSRF
 *      แล้ว จันทรา.online แลก token ไม่ได้ = SSO ตาย
 *
 * ไม่ใช้ DB (ตรวจที่ตาราง route ล้วนๆ) — ช่องโหว่นี้อยู่ที่การลงทะเบียน route
 */
class PassportRouteLockdownTest extends TestCase
{
    /**
     * เส้นที่ต้องมี: [ชื่อ route => [HTTP method, uri, middleware ที่คาดหวัง]]
     *
     * ค่าพวกนี้ลอกจาก vendor/laravel/passport/routes/web.php ตรงๆ
     * ('auth:web' มาจาก config passport.guard ที่ vendor merge มาให้ = 'web')
     */
    private const REQUIRED_ROUTES = [
        'passport.token' => ['POST', 'oauth/token', ['throttle']],
        'passport.authorizations.authorize' => ['GET', 'oauth/authorize', ['web']],
        'passport.authorizations.approve' => ['POST', 'oauth/authorize', ['web', 'auth:web']],
        'passport.authorizations.deny' => ['DELETE', 'oauth/authorize', ['web', 'auth:web']],
    ];

    /**
     * เส้นที่ต้องไม่มี — ชุด "จัดการเอง" ที่เปิดให้ผู้ใช้ทั่วไปสร้าง client/token
     */
    private const FORBIDDEN_ROUTE_NAMES = [
        'passport.clients.index',
        'passport.clients.store',
        'passport.clients.update',
        'passport.clients.destroy',
        'passport.personal.tokens.index',
        'passport.personal.tokens.store',
        'passport.personal.tokens.destroy',
        'passport.tokens.index',
        'passport.tokens.destroy',
        'passport.scopes.index',
        'passport.token.refresh',
    ];

    /**
     * Passport ต้องไม่ลงทะเบียน route เองแล้ว (ต้องเรียก ignoreRoutes() ใน register())
     */
    public function test_passport_auto_route_registration_is_disabled(): void
    {
        $this->assertFalse(
            Passport::$registersRoutes,
            'Passport ยังลงทะเบียน route เองอยู่ — ต้องเรียก Passport::ignoreRoutes() ใน AppServiceProvider::register()'
        );
    }

    /**
     * เส้นที่ SSO เว็บจันทราใช้ต้องยังอยู่ครบ พร้อม middleware เดิม
     */
    public function test_sso_routes_are_still_registered_with_original_middleware(): void
    {
        foreach (self::REQUIRED_ROUTES as $name => [$method, $uri, $middleware]) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "route [{$name}] หายไป — SSO ของ จันทรา.online จะพัง");
            $this->assertSame($uri, $route->uri(), "uri ของ [{$name}] เปลี่ยนไป");
            $this->assertContains($method, $route->methods(), "[{$name}] ไม่รับ method {$method}");
            $this->assertSame(
                $middleware,
                $route->gatherMiddleware(),
                "middleware ของ [{$name}] ไม่ตรงกับของ vendor"
            );
        }
    }

    /**
     * POST /oauth/token ห้ามอยู่ในกลุ่ม 'web' เด็ดขาด
     *
     * juntraweb ยิงมาแบบ server-to-server ไม่มี session/CSRF token
     * ถ้าเผลอใส่ 'web' → VerifyCsrfToken เด้ง 419 → แลก token ไม่ได้
     */
    public function test_token_endpoint_is_not_behind_web_middleware(): void
    {
        $route = Route::getRoutes()->getByName('passport.token');

        $this->assertNotNull($route);
        $this->assertNotContains('web', $route->gatherMiddleware());
    }

    /**
     * เส้น self-service client/token ต้องถูกถอดออกหมด (ตรวจจากชื่อ route)
     */
    public function test_client_self_service_routes_are_not_registered(): void
    {
        foreach (self::FORBIDDEN_ROUTE_NAMES as $name) {
            $this->assertFalse(
                Route::has($name),
                "route [{$name}] ยังถูกลงทะเบียนอยู่ — ผู้ใช้ที่ล็อกอินอาจสร้าง OAuth client เองได้"
            );
        }
    }

    /**
     * ตรวจซ้ำที่ระดับ URI — กันเคสมีคนประกาศ path เดิมด้วยชื่ออื่น
     */
    public function test_no_oauth_route_exposes_client_or_token_management_paths(): void
    {
        $forbiddenPrefixes = [
            'oauth/clients',
            'oauth/personal-access-tokens',
            'oauth/tokens',
            'oauth/scopes',
            'oauth/token/refresh',
        ];

        $registered = [];
        foreach (Route::getRoutes() as $route) {
            $registered[] = $route->uri();
        }

        foreach ($forbiddenPrefixes as $prefix) {
            $hit = array_values(array_filter(
                $registered,
                fn (string $uri) => $uri === $prefix || str_starts_with($uri, $prefix.'/')
            ));

            $this->assertSame([], $hit, "ยังมี route ที่ path ขึ้นต้นด้วย [{$prefix}]");
        }
    }

    /**
     * กัน regression ที่สำคัญที่สุด: จำนวนเส้น oauth/* ต้องเป็น 4 เท่านั้น
     *
     * ถ้าอัปเกรด Passport แล้วมันเพิ่มเส้นใหม่มา (เช่น device flow / userinfo)
     * เทสต์นี้จะแดงให้มาทบทวนก่อนว่าเส้นใหม่ปลอดภัยไหม
     */
    public function test_only_the_four_allowlisted_oauth_routes_exist(): void
    {
        $oauthRoutes = [];
        foreach (Route::getRoutes() as $route) {
            if ($route->uri() === 'oauth' || str_starts_with($route->uri(), 'oauth/')) {
                $oauthRoutes[] = implode('|', $route->methods()).' '.$route->uri();
            }
        }

        sort($oauthRoutes);

        $this->assertSame([
            'DELETE oauth/authorize',
            'GET|HEAD oauth/authorize',
            'POST oauth/authorize',
            'POST oauth/token',
        ], $oauthRoutes);
    }
}
