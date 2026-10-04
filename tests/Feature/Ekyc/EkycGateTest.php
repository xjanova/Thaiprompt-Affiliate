<?php

namespace Tests\Feature\Ekyc;

use App\Http\Middleware\EnsureEkycVerified;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Ekyc\Concerns\BuildsEkycFixtures;
use Tests\TestCase;

/**
 * 🪪 ด่าน `ekyc.verified` — บังคับเฉพาะแอป X-App-Build ≥ 44 · ปิดรับงานไรเดอร์ไม่ถูกบล็อกเลย (ต้องใช้ MySQL)
 */
#[Group('ekyc')]
class EkycGateTest extends TestCase
{
    use BuildsEkycFixtures;
    use RefreshDatabase;

    private const GATED_ROUTES = [
        '/api/v1/cart/checkout',
        '/api/v1/fresh-market/orders',
        '/api/v1/seller/application',
        '/api/v1/rider/register',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Storage::fake('local');
        Storage::fake('public');
        Cache::flush();
        $this->configureEkyc();
    }

    public function test_gate_applies_only_to_new_app_builds(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        foreach (self::GATED_ROUTES as $route) {
            // แอปรุ่นเก่า / ไม่ส่ง header / ค่าแปลก / เว็บ → แบบเดิม
            foreach ([[], ['X-App-Build' => '43'], ['X-App-Build' => '44abc']] as $headers) {
                $this->assertNotSame(EnsureEkycVerified::CODE, $this->postJson($route, [], $headers)->json('code'), "{$route} ต้องไม่บล็อกแอปรุ่นเก่า");
            }

            $this->postJson($route, [], ['X-App-Build' => '44'])
                ->assertStatus(422)
                ->assertJsonPath('success', false)
                ->assertJsonPath('code', 'KYC_REQUIRED')
                ->assertJsonPath('message', 'กรุณายืนยันตัวตนก่อนใช้งานส่วนนี้ ใช้เวลาประมาณ 1 นาที')
                ->assertJsonPath('data.kyc_status', 'none');

            $this->postJson($route, [], ['X-App-Build' => '57'])->assertJsonPath('code', 'KYC_REQUIRED');
        }

        // รอตรวจอยู่ → ยังบล็อก แต่บอกสถานะ pending
        $user->forceFill(['kyc_status' => 'pending'])->save();
        $this->postJson('/api/v1/cart/checkout', [], ['X-App-Build' => '44'])
            ->assertJsonPath('code', 'KYC_REQUIRED')
            ->assertJsonPath('data.kyc_status', 'pending');

        // ปิดการบังคับ → ไม่บล็อก
        config(['ekyc.enforce' => false]);
        $this->assertNotSame('KYC_REQUIRED', $this->postJson('/api/v1/cart/checkout', [], ['X-App-Build' => '44'])->json('code'));
        config(['ekyc.enforce' => true]);

        // ยืนยันแล้ว → ผ่านด่าน (ไปเจอ validation ของแต่ละเส้นแทน)
        $user->forceFill(['kyc_status' => 'approved', 'kyc_verified_at' => now()])->save();
        foreach (self::GATED_ROUTES as $route) {
            $this->assertNotSame('KYC_REQUIRED', $this->postJson($route, [], ['X-App-Build' => '44'])->json('code'), $route);
        }
    }

    public function test_rider_going_offline_is_never_blocked_but_online_requires_kyc(): void
    {
        $rider = $this->makeRider();
        Sanctum::actingAs($rider->user);
        $headers = ['X-App-Build' => '44'];

        $this->postJson('/api/v1/rider/availability', ['availability' => 'online', 'latitude' => 13.73, 'longitude' => 100.52], $headers)
            ->assertStatus(422)
            ->assertJsonPath('code', 'KYC_REQUIRED');

        // ไรเดอร์ที่ online ค้างอยู่ก่อนอัปเดตแอป ต้องปิดรับงานได้เสมอ
        $rider->forceFill(['availability' => 'online'])->save();
        $this->postJson('/api/v1/rider/availability', ['availability' => 'offline'], $headers)
            ->assertOk()
            ->assertJsonPath('data.availability', 'offline');
        $this->assertSame('offline', $rider->fresh()->availability);

        // แอปรุ่นเก่าเปิดรับงานได้ตามเดิม
        $this->assertNotSame('KYC_REQUIRED', $this->postJson('/api/v1/rider/availability', [
            'availability' => 'online', 'latitude' => 13.73, 'longitude' => 100.52,
        ], ['X-App-Build' => '43'])->json('code'));

        // ยืนยันตัวตนแล้ว → เปิดรับงานด้วยแอปรุ่นใหม่ได้
        $rider->user->forceFill(['kyc_status' => 'approved', 'kyc_verified_at' => now()])->save();
        $this->postJson('/api/v1/rider/availability', ['availability' => 'offline'], $headers)->assertOk();
        $this->assertNotSame('KYC_REQUIRED', $this->postJson('/api/v1/rider/availability', [
            'availability' => 'online', 'latitude' => 13.73, 'longitude' => 100.52,
        ], $headers)->json('code'));
    }

    public function test_gate_routes_carry_the_middleware(): void
    {
        $routes = app('router')->getRoutes();

        foreach ([
            ['POST', 'api/v1/cart/checkout', 'ekyc.verified'],
            ['POST', 'api/v1/fresh-market/orders', 'ekyc.verified'],
            ['POST', 'api/v1/seller/application', 'ekyc.verified'],
            ['POST', 'api/v1/rider/register', 'ekyc.verified'],
            ['POST', 'api/v1/rider/availability', 'ekyc.verified:availability=online'],
        ] as [$method, $uri, $middleware]) {
            $route = $routes->match(\Illuminate\Http\Request::create('/'.$uri, $method));
            $this->assertContains($middleware, $route->gatherMiddleware(), "{$method} {$uri}");
        }

        // เส้น eKYC เองอยู่ใต้ auth:sanctum + throttle แยกตัวนับ
        foreach ([
            ['GET', 'api/v1/ekyc/status', 'throttle:60,1,api-ekyc-status'],
            ['POST', 'api/v1/ekyc/sessions', 'throttle:6,1,api-ekyc-session'],
            ['POST', 'api/v1/ekyc/sessions/x/id-card', 'throttle:15,1,api-ekyc-card'],
            ['PATCH', 'api/v1/ekyc/sessions/x/id-card', 'throttle:20,1,api-ekyc-card-fix'],
            ['POST', 'api/v1/ekyc/sessions/x/face', 'throttle:6,1,api-ekyc-face'],
        ] as [$method, $uri, $throttle]) {
            $middleware = $routes->match(\Illuminate\Http\Request::create('/'.$uri, $method))->gatherMiddleware();
            $this->assertContains('auth:sanctum', $middleware, "{$method} {$uri}");
            $this->assertContains($throttle, $middleware, "{$method} {$uri}");
        }

        // alias เดิมของหน้าเว็บผู้ขายยังเป็นตัวเดิม
        $this->assertSame(\App\Http\Middleware\EnsureKycVerified::class, app('router')->getMiddleware()['kyc.verified']);
    }

    public function test_ekyc_endpoints_require_login(): void
    {
        $this->getJson('/api/v1/ekyc/status')->assertUnauthorized();
        $this->postJson('/api/v1/ekyc/sessions', ['consent' => true, 'consent_version' => '2026-10-04'])->assertUnauthorized();
    }
}
