<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * ล็อกไม่ให้ CheckRole เด้งผู้ใช้วนลูปอีก
 *
 * 🚨 บั๊กจริงบน prod (2026-07-27) — กระทบ 55 บัญชี (affiliate 35 / provider 19 / manager 1):
 *    routes/web.php หุ้ม /user/* ด้วย 'role:user' → คนที่ role ไม่ใช่ 'user' โดนบล็อก
 *    แล้ว CheckRole::redirectToDashboard() เด้งไป route('user.home') ซึ่งอยู่ใน group
 *    ที่ถูกหุ้มอยู่นั่นเอง → โดนบล็อกซ้ำ เด้งที่เดิม วนไม่รู้จบ = ERR_TOO_MANY_REDIRECTS
 *    เจอตั้งแต่ล็อกอินเสร็จ เพราะ LoginController เด้งทุกคน (ที่ไม่ใช่ admin/seller) ไป user.home
 *
 * เทสต์นี้ล็อกไว้ 3 ทาง (ห้ามหลุดข้อไหน):
 *    1. ทุก role ที่มีจริงในระบบ ยิงเข้า /user/home แล้วต้องไม่ถูกเด้งกลับที่เดิม
 *    2. role สมาชิกตาม MEMBER_ROLES ต้อง "เข้าได้จริง" ไม่ใช่แค่ไม่วนลูป
 *    3. route group /user/* ต้องหุ้มด้วย MEMBER_ROLES ครบทุกตัว (กันคนแก้กลับเป็น role:user)
 *
 * ไม่แตะ DB เลย (สร้าง User ในหน่วยความจำ) — บั๊กนี้อยู่ที่ตรรกะ middleware + ตาราง route ล้วนๆ
 */
class RoleRedirectLoopTest extends TestCase
{
    /**
     * role ทั้งหมดที่มีบัญชีจริงบน prod (นับจาก DB 2026-07-27)
     * บวก role สมมติที่แอดมินอาจสร้างเพิ่มภายหลัง เพื่อทดสอบ fallback
     *
     * @var array<string>
     */
    private const ALL_ROLES = [
        'user',         // 777 บัญชี
        'affiliate',    // 35 บัญชี — เคยวนลูป
        'provider',     // 19 บัญชี — เคยวนลูป
        'manager',      // 1 บัญชี — เคยวนลูป
        'seller',       // 1 บัญชี
        'admin',        // 2 บัญชี
        'super_admin',  // 1 บัญชี
        'instructor',   // ยังไม่มีบัญชีจริง แต่มีเมนูใน config/menus.php
        'partner',      // role สมมติ — ไม่มีในระบบ ใช้ทดสอบ fallback หน้าแรกสาธารณะ
    ];

    /**
     * ทุก role ยิงเข้า /user/home ต้องไม่โดนเด้งกลับมาที่ /user/home อีก
     *
     * ใช้ middleware ชุดจริงที่หุ้ม route อยู่ (อ่านจากตาราง route)
     * ถ้าใครแก้ web.php ให้แคบลง เทสต์นี้จะจับได้ทันที
     */
    public function test_no_role_gets_redirected_back_to_the_page_that_blocked_it(): void
    {
        $target = route('user.home');
        $allowedRoles = $this->roleMiddlewareParamsOf('user.home');

        foreach (self::ALL_ROLES as $role) {
            $response = $this->runCheckRole($role, $target, $allowedRoles);

            if ($response->isRedirect()) {
                $this->assertNotSame(
                    $this->pathOf($target),
                    $this->pathOf($response->headers->get('Location')),
                    "role '{$role}' ถูกเด้งกลับไปหน้าเดิมที่บล็อกมัน = redirect loop"
                );
            } else {
                $this->assertSame(200, $response->getStatusCode(), "role '{$role}' ได้ response แปลกๆ");
            }
        }
    }

    /**
     * role สมาชิกต้องเข้า /user/* ได้จริง
     *
     * เจตนาของระบบ MLM/affiliate: /user/* คือพื้นที่ข้อมูลของตัวเอง
     * (กระเป๋าเงิน/ค่าคอมมิชชั่น/ผู้มุ่งหวัง/KYC) นักขายกับผู้ให้บริการ
     * ต้องเปิดดูของตัวเองได้ ไม่ใช่แค่ "ไม่วนลูป" แล้วถูกเตะออกไปหน้าร้าน
     */
    public function test_member_roles_can_actually_enter_the_user_area(): void
    {
        $target = route('user.home');
        $allowedRoles = $this->roleMiddlewareParamsOf('user.home');

        foreach (CheckRole::MEMBER_ROLES as $role) {
            $response = $this->runCheckRole($role, $target, $allowedRoles);

            $this->assertSame(
                200,
                $response->getStatusCode(),
                "role '{$role}' อยู่ใน MEMBER_ROLES แต่เข้า /user/home ไม่ได้"
            );
        }
    }

    /**
     * admin / super_admin ผ่านได้เสมอ (ใช้ดูแลระบบ) — ห้ามหลุด
     */
    public function test_admins_still_pass_through(): void
    {
        foreach (['admin', 'super_admin'] as $role) {
            $response = $this->runCheckRole($role, route('user.home'), ['user']);

            $this->assertSame(200, $response->getStatusCode(), "role '{$role}' ต้องผ่านได้เสมอ");
        }
    }

    /**
     * seller โดนบล็อกจาก /user/* ได้ (มีพื้นที่ของตัวเอง) แต่ต้องเด้งไป /seller/* ไม่ใช่วนที่เดิม
     */
    public function test_seller_is_redirected_to_its_own_dashboard(): void
    {
        $response = $this->runCheckRole('seller', route('user.home'), ['user']);

        $this->assertTrue($response->isRedirect(), 'seller ต้องถูกเด้งออกจาก /user/*');
        $this->assertSame(
            $this->pathOf(route('seller.dashboard')),
            $this->pathOf($response->headers->get('Location'))
        );
    }

    /**
     * ชั้นกันลูปสุดท้าย: ถ้าหน้าที่จะเด้งไป = หน้าเดิมที่เพิ่งถูกบล็อก ต้องตอบ 403 ห้าม redirect
     *
     * จำลองเคสที่แย่ที่สุด: role แปลกที่ไม่รู้จัก (fallback = หน้าแรกสาธารณะ)
     * ถูกบล็อกที่หน้าแรกสาธารณะเสียเอง → ถ้าไม่มีชั้นนี้จะเด้งใส่ตัวเองไม่รู้จบ
     */
    public function test_blocking_the_fallback_page_itself_returns_403_instead_of_looping(): void
    {
        try {
            $this->runCheckRole('partner', route('home'), ['user']);
            $this->fail('ต้องโยน HttpException 403 แทนการ redirect ใส่ตัวเอง');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    /**
     * route group /user/* ต้องหุ้มด้วย MEMBER_ROLES ครบทุกตัว
     *
     * กันคนแก้ routes/web.php กลับไปเป็น 'role:user' แล้วบั๊กเดิมกลับมา
     */
    public function test_user_area_is_guarded_by_all_member_roles(): void
    {
        $allowedRoles = $this->roleMiddlewareParamsOf('user.home');

        foreach (CheckRole::MEMBER_ROLES as $role) {
            $this->assertContains(
                $role,
                $allowedRoles,
                "routes/web.php ต้องอนุญาต role '{$role}' ให้เข้า /user/* (ดู CheckRole::MEMBER_ROLES)"
            );
        }
    }

    /**
     * รัน CheckRole กับ user ปลอมที่มี role ตามต้องการ (ไม่แตะ DB)
     *
     * ⚠️ return type ต้องเป็น Symfony Response (ไม่ใช่ Illuminate\Http\Response)
     *    เพราะตอนถูกบล็อกจะได้ RedirectResponse กลับมา ซึ่งไม่ได้ extends ตัวของ Illuminate
     *
     * @param  array<string>  $allowedRoles  พารามิเตอร์ของ middleware role:...
     */
    private function runCheckRole(string $role, string $url, array $allowedRoles): Response
    {
        $user = new User;
        $user->role = $role;
        $user->is_super_admin = ($role === 'super_admin');

        $request = Request::create($url, 'GET');
        $request->setUserResolver(fn () => $user);

        return (new CheckRole)->handle(
            $request,
            fn ($request) => new HttpResponse('PASSED', 200),
            ...$allowedRoles
        );
    }

    /**
     * ดึงพารามิเตอร์ของ middleware 'role:...' ที่หุ้ม route ชื่อนั้นอยู่จริง
     *
     * @return array<string>
     */
    private function roleMiddlewareParamsOf(string $routeName): array
    {
        $route = Route::getRoutes()->getByName($routeName);

        $this->assertNotNull($route, "ไม่พบ route ชื่อ {$routeName}");

        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'role:')) {
                return explode(',', substr($middleware, strlen('role:')));
            }
        }

        $this->fail("route {$routeName} ไม่ได้ถูกหุ้มด้วย middleware role: แล้ว");
    }

    /**
     * ตัดเอาเฉพาะ path มาเทียบ (scheme/host อาจต่างกันเมื่ออยู่หลัง proxy)
     */
    private function pathOf(?string $url): string
    {
        return rtrim(parse_url((string) $url, PHP_URL_PATH) ?: '/', '/');
    }
}
