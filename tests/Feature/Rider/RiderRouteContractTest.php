<?php

namespace Tests\Feature\Rider;

use App\Http\Controllers\Api\V1\MobileApiController;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * สัญญา route ของระบบไรเดอร์ (ไม่ใช้ DB — ตรวจที่ตาราง route ล้วนๆ รันบนเครื่อง dev ได้)
 *
 * - ทุก route ไรเดอร์ชี้ไป method ที่มีอยู่จริง (กัน 500 แบบ RIDER-07/RIDER-09)
 * - API /api/v1/rider/* อยู่หลัง auth:sanctum + throttle และเส้นที่เขียนข้อมูลมี throttle ของตัวเอง
 * - ไฟล์เอกสารไรเดอร์เปิดได้เฉพาะ signed URL / แอดมิน / เจ้าของ (session)
 * - เส้นเก่าที่พัง (/fresh-market/rider/gps/*, rider methods ใน MobileApiController) ต้องไม่กลับมา
 */
#[Group('rider')]
class RiderRouteContractTest extends TestCase
{
    /**
     * API ที่แอปไรเดอร์ใช้: ชื่อ route => [HTTP method, uri]
     */
    private const API_ROUTES = [
        'api.v1.rider.status' => ['GET', 'api/v1/rider/status'],
        'api.v1.rider.register' => ['POST', 'api/v1/rider/register'],
        'api.v1.rider.document' => ['POST', 'api/v1/rider/document'],
        'api.v1.rider.documents' => ['GET', 'api/v1/rider/documents'],
        'api.v1.rider.permissions' => ['POST', 'api/v1/rider/permissions'],
        'api.v1.rider.profile' => ['PUT', 'api/v1/rider/profile'],
        'api.v1.rider.availability' => ['POST', 'api/v1/rider/availability'],
        'api.v1.rider.location' => ['POST', 'api/v1/rider/location'],
        'api.v1.rider.earnings' => ['GET', 'api/v1/rider/earnings'],
        'api.v1.rider.jobs.available' => ['GET', 'api/v1/rider/jobs/available'],
        'api.v1.rider.jobs.current' => ['GET', 'api/v1/rider/jobs/current'],
        'api.v1.rider.jobs.history' => ['GET', 'api/v1/rider/jobs/history'],
        'api.v1.rider.jobs.show' => ['GET', 'api/v1/rider/jobs/{id}'],
        'api.v1.rider.jobs.accept' => ['POST', 'api/v1/rider/jobs/{id}/accept'],
        'api.v1.rider.jobs.reject' => ['POST', 'api/v1/rider/jobs/{id}/reject'],
        'api.v1.rider.jobs.release' => ['POST', 'api/v1/rider/jobs/{id}/release'],
        'api.v1.rider.jobs.status' => ['POST', 'api/v1/rider/jobs/{id}/status'],
        'api.v1.rider.jobs.deliver' => ['POST', 'api/v1/rider/jobs/{id}/deliver'],
        'api.v1.rider.jobs.fail' => ['POST', 'api/v1/rider/jobs/{id}/fail'],
        'api.v1.rider.jobs.gps-lost' => ['POST', 'api/v1/rider/jobs/{id}/gps-lost'],
        'api.v1.rider.jobs.gps-off' => ['POST', 'api/v1/rider/jobs/{id}/gps-off'],
    ];

    /**
     * prefix ชื่อ route ของระบบไรเดอร์ที่ต้องชี้ไป method ที่มีอยู่จริง
     */
    private const NAME_PREFIXES = [
        'api.v1.rider.',
        'user.rider.',
        'admin.riders.',
        'admin.rider-jobs.',
        'taladsod.track.',
        'taladsod.rider.',
    ];

    public function test_rider_api_routes_exist_with_auth_and_throttle(): void
    {
        foreach (self::API_ROUTES as $name => [$method, $uri]) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "ไม่พบ route {$name}");
            $this->assertContains($method, $route->methods(), "{$name} ต้องเป็น {$method}");
            $this->assertSame($uri, $route->uri(), "{$name} uri ไม่ตรง");

            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth:sanctum', $middleware, "{$name} ต้องอยู่หลัง auth:sanctum");
            $this->assertContains('throttle:api', $middleware, "{$name} ต้องมี throttle:api");

            if (in_array($method, ['POST', 'PUT'], true)) {
                $own = array_filter($middleware, fn ($m) => is_string($m) && str_starts_with($m, 'throttle:') && $m !== 'throttle:api');
                $this->assertNotEmpty($own, "{$name} (เขียนข้อมูล) ต้องมี throttle เฉพาะเส้น");
            }
        }
    }

    public function test_every_rider_route_points_to_an_existing_controller_method(): void
    {
        $checked = 0;

        /** @var RoutingRoute $route */
        foreach (Route::getRoutes() as $route) {
            $name = (string) $route->getName();
            if (! $this->hasRiderPrefix($name)) {
                continue;
            }

            $action = $route->getActionName();
            $this->assertStringContainsString('@', $action, "{$name} ต้องชี้ไป controller");

            [$class, $method] = explode('@', $action);
            $this->assertTrue(class_exists($class), "{$name}: ไม่พบคลาส {$class}");
            $this->assertTrue(method_exists($class, $method), "{$name}: ไม่พบ method {$class}::{$method}");
            $this->assertTrue((new \ReflectionMethod($class, $method))->isPublic(), "{$name}: {$method} ต้องเป็น public");

            $checked++;
        }

        $this->assertGreaterThan(50, $checked, 'ต้องพบ route ไรเดอร์ครบทุกกลุ่ม');
    }

    public function test_document_files_are_never_public(): void
    {
        $signed = Route::getRoutes()->getByName('api.v1.rider.documents.file');
        $this->assertNotNull($signed);
        $this->assertContains('signed', $signed->gatherMiddleware(), 'ไฟล์เอกสารจากแอปต้องใช้ signed URL');

        $admin = Route::getRoutes()->getByName('admin.riders.document');
        $this->assertNotNull($admin);
        $this->assertContains('role:admin,super_admin', $admin->gatherMiddleware(), 'ไฟล์เอกสารฝั่งหลังบ้านต้องเป็นแอดมินเท่านั้น');

        $own = Route::getRoutes()->getByName('user.rider.documents.file');
        $this->assertNotNull($own);
        $this->assertContains('auth', $own->gatherMiddleware(), 'ไฟล์เอกสารของตัวเองต้องล็อกอิน');

        $photo = Route::getRoutes()->getByName('taladsod.track.rider-photo');
        $this->assertNotNull($photo, 'รูปไรเดอร์ในหน้าติดตามต้องผ่าน route ของ token');
    }

    public function test_admin_static_rider_routes_are_registered_before_the_rider_wildcard(): void
    {
        $request = \Illuminate\Http\Request::create('/admin/riders/settings', 'GET');
        $this->assertSame('admin.riders.settings', Route::getRoutes()->match($request)->getName());

        $request = \Illuminate\Http\Request::create('/admin/riders/dispatch-monitor', 'GET');
        $this->assertSame('admin.riders.dispatch-monitor', Route::getRoutes()->match($request)->getName());
    }

    public function test_legacy_broken_rider_endpoints_are_gone(): void
    {
        foreach (Route::getRoutes() as $route) {
            $this->assertStringNotContainsString('fresh-market/rider/gps', $route->uri(), 'API GPS เดิมถูกรวมเข้า /rider/* แล้ว');
            $this->assertStringNotContainsString('fresh-market/rider/job/update-status', $route->uri());
        }

        foreach (['getRiderStatus', 'registerRider', 'uploadRiderDocument', 'updateRiderPermissions', 'setRiderAvailability',
            'updateRiderLocation', 'getAvailableJobs', 'acceptJob', 'getCurrentJob', 'updateJobStatus'] as $method) {
            $this->assertFalse(method_exists(MobileApiController::class, $method), "ต้องลบ MobileApiController::{$method} แล้ว (ย้ายไป RiderApiController)");
        }
    }

    private function hasRiderPrefix(string $name): bool
    {
        foreach (self::NAME_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
