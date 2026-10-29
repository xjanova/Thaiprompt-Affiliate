<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Routing\RedirectController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * ล็อกไม่ให้ Route::redirect() ชี้ไปหน้าที่ไม่มีอยู่จริง
 *
 * 🚨 บั๊กจริงบน prod (พบ 2026-10-28): routes/admin.php เขียน Route::redirect('/', '/dashboard')
 *    ไว้ใน group prefix 'admin' — แต่ปลายทางของ Route::redirect เป็น path จาก root
 *    ไม่ต่อ prefix ให้ → /admin เด้งไป /dashboard ซึ่งไม่มี route = 404
 *    แอดมินเจอตั้งแต่ล็อกอินเสร็จ: เปิด /admin → โดนพาไปล็อกอิน → intended() พากลับ /admin → 404
 *
 * ไม่แตะ DB — ตรวจจากตาราง route ล้วนๆ
 */
class RouteRedirectDestinationTest extends TestCase
{
    /**
     * /admin ต้องพาไปหน้าแดชบอร์ดแอดมินจริง
     */
    public function test_admin_root_redirects_to_the_admin_dashboard(): void
    {
        $route = Route::getRoutes()->match(Request::create('/admin', 'GET'));

        $this->assertSame(
            parse_url(route('admin.dashboard'), PHP_URL_PATH),
            $route->defaults['destination'] ?? null,
            '/admin ต้อง redirect ไป /admin/dashboard'
        );
    }

    /**
     * ทุก Route::redirect() ในระบบ ปลายทางต้องเป็น GET route ที่มีอยู่จริง
     */
    public function test_every_route_redirect_points_to_an_existing_route(): void
    {
        $checked = 0;

        foreach (Route::getRoutes() as $route) {
            // Route::redirect() เก็บชื่อ controller แบบมี \ นำหน้า
            if (ltrim($route->getActionName(), '\\') !== RedirectController::class) {
                continue;
            }

            $destination = $route->defaults['destination'] ?? '';

            // ปลายทางเป็นเว็บภายนอก — ไม่ใช่หน้าที่ระบบเราต้องมี
            if (preg_match('#^https?://#i', $destination)) {
                continue;
            }

            try {
                Route::getRoutes()->match(Request::create($destination, 'GET'));
            } catch (NotFoundHttpException) {
                $this->fail("/{$route->uri()} redirect ไป {$destination} ซึ่งไม่มี route = 404");
            }

            $checked++;
        }

        $this->assertGreaterThan(0, $checked, 'ไม่พบ Route::redirect เลย — ตาราง route โหลดไม่ครบหรือเปล่า');
    }
}
