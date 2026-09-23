<?php

namespace Tests\Unit\Services;

use App\Services\CloudflareService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ปุ่ม One-Click Optimization (Admin → Cloudflare) ต้องไม่เปิดของที่เคยพังระบบจริง
 *
 * 🔧 (2026-09-23) เดิมปุ่มนี้ตั้ง Hotlink Protection = on (ต้นเหตุรูปใน LINE หาย 2026-09-19),
 *    Rocket Loader = on (ชนกับ Alpine.js), Auto Minify (Cloudflare เลิกใช้แล้ว) และตั้ง SSL = full
 *    ทับโซนที่เป็น strict อยู่ (xman4289.com) — กดครั้งเดียวพังซ้ำทั้งชุด
 *
 * ไม่ใช้ DB และไม่ยิง Cloudflare จริง — สร้าง service โดยไม่ผ่าน constructor แล้ว fake HTTP
 */
class CloudflareOneClickPlanTest extends TestCase
{
    public function test_plan_turns_off_the_settings_that_broke_production(): void
    {
        $plan = collect($this->service()->oneClickOptimizationPlan())->keyBy('setting');

        $this->assertSame('off', $plan['hotlink_protection']['value'] ?? null, 'Hotlink Protection ต้องเป็น off — เปิดแล้วรูปใน LINE หาย');
        $this->assertSame('off', $plan['rocket_loader']['value'] ?? null, 'Rocket Loader ต้องเป็น off — ชนกับ Alpine.js');
        $this->assertArrayNotHasKey('minify', $plan->all(), 'Auto Minify เลิกใช้แล้ว ยิงไปก็ error');
        $this->assertSame(['off', 'flexible'], $plan['ssl']['only_upgrade_from'] ?? null, 'SSL ต้องขยับขึ้นเท่านั้น');
    }

    public function test_one_click_never_downgrades_strict_ssl(): void
    {
        $patches = $this->runOneClickWithCurrentSsl('strict');

        $this->assertArrayNotHasKey('ssl', $patches, 'โซนที่เป็น strict ต้องไม่ถูกลดเป็น full');
        $this->assertSame('off', $patches['hotlink_protection'] ?? null);
        $this->assertSame('off', $patches['rocket_loader'] ?? null);
        $this->assertArrayNotHasKey('minify', $patches);
    }

    public function test_one_click_raises_flexible_ssl_to_full(): void
    {
        $patches = $this->runOneClickWithCurrentSsl('flexible');

        $this->assertSame('full', $patches['ssl'] ?? null, 'flexible (ไม่เข้ารหัสฝั่ง origin) ต้องถูกยกเป็น full');
    }

    public function test_status_treats_the_new_values_as_optimized(): void
    {
        Http::fake(['api.cloudflare.com/*' => Http::response(['success' => true, 'result' => [
            ['id' => 'hotlink_protection', 'value' => 'off'],
            ['id' => 'rocket_loader', 'value' => 'off'],
            ['id' => 'ssl', 'value' => 'strict'],
            ['id' => 'min_tls_version', 'value' => '1.0'],
        ]])]);

        $status = $this->service()->checkOptimizationStatus()['status'];

        $this->assertTrue($status['hotlink_protection']['optimized'], 'Hotlink ปิด = ถูกต้อง ไม่ต้องเตือนให้กดปุ่ม');
        $this->assertTrue($status['rocket_loader']['optimized'], 'Rocket Loader ปิด = ถูกต้อง');
        $this->assertTrue($status['ssl']['optimized'], 'strict ดีกว่า full ต้องนับว่าผ่าน');
        $this->assertFalse($status['min_tls_version']['optimized'], 'TLS 1.0 ต้องขึ้นว่ายังไม่ได้ปรับ');
    }

    /**
     * รันปุ่มกับโซนปลอมที่ SSL ปัจจุบันเป็นค่าที่กำหนด แล้วคืน [setting => ค่าที่ PATCH ไป]
     *
     * @return array<string, mixed>
     */
    private function runOneClickWithCurrentSsl(string $currentSsl): array
    {
        Http::fake([
            'api.cloudflare.com/client/v4/zones/zone-test/settings/ssl' => Http::response(['success' => true, 'result' => ['id' => 'ssl', 'value' => $currentSsl]]),
            'api.cloudflare.com/*' => Http::response(['success' => true, 'result' => ['value' => 'ok']]),
        ]);

        $this->service()->oneClickOptimization();

        return collect(Http::recorded())
            ->map(fn (array $pair) => $pair[0])
            ->filter(fn (Request $r) => $r->method() === 'PATCH')
            ->mapWithKeys(fn (Request $r) => [basename((string) parse_url($r->url(), PHP_URL_PATH)) => $r->data()['value'] ?? null])
            ->all();
    }

    private function service(): CloudflareService
    {
        $service = (new \ReflectionClass(CloudflareService::class))->newInstanceWithoutConstructor();

        foreach (['apiToken' => 'test-token', 'zoneId' => 'zone-test'] as $property => $value) {
            (new \ReflectionProperty(CloudflareService::class, $property))->setValue($service, $value);
        }

        return $service;
    }
}
