<?php

namespace Tests\Feature\AdminApp;

use App\Models\AiApiKey;
use App\Models\FortuneReading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\TestCase;

/**
 * 🔐 endpoint ใหม่ของแอปแอดมินทุกตัว: ไม่มี token = 401 · ไม่ใช่แอดมิน = 403 · แอดมิน = 200 + envelope
 */
#[Group('admin-app')]
class AdminAppAccessTest extends TestCase
{
    use BuildsAdminAppWorld;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $this->tearDownAdminAppWorld();

        parent::tearDown();
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private function endpoints(FortuneReading $reading): array
    {
        return [
            ['GET', '/api/admin/ops/summary'],
            ['GET', '/api/admin/fortune/bills'],
            ['GET', '/api/admin/fortune/bills/stats'],
            ['GET', '/api/admin/fortune/active-readings'],
            ['GET', '/api/admin/fortune/ai-pool'],
            ['GET', '/api/admin/fortune/services'],
            ['GET', '/api/admin/takeover/conversations'],
            ['GET', '/api/admin/takeover/stats'],
            ['GET', "/api/admin/takeover/{$reading->id}/messages"],
        ];
    }

    public function test_every_new_endpoint_rejects_guests_and_members_and_serves_admins(): void
    {
        $reading = $this->makeReading(['is_paid' => true, 'amount_paid' => 39, 'paid_at' => now()]);

        foreach ($this->endpoints($reading) as [$method, $uri]) {
            $this->json($method, $uri)->assertStatus(401);
        }

        $this->actAs($this->makeMember());
        foreach ($this->endpoints($reading) as [$method, $uri]) {
            $this->json($method, $uri)->assertStatus(403)->assertJsonPath('error_code', 'NOT_ADMIN');
        }

        $this->actAs($this->makeAdmin());
        foreach ($this->endpoints($reading) as [$method, $uri]) {
            $this->json($method, $uri)->assertOk()->assertJsonPath('success', true);
        }
    }

    public function test_write_endpoints_need_auth_and_ai_pool_writes_need_manage_rights(): void
    {
        $reading = $this->makeReading();
        $key = AiApiKey::create([
            'name' => 'คีย์ทดสอบ',
            'provider' => 'deepseek',
            'api_key' => 'sk-test-0000000000000000SECRET1234',
            'is_active' => true,
        ]);

        $writes = [
            ['POST', '/api/admin/chat/extend', ['reading_id' => $reading->id, 'minutes' => 5]],
            ['POST', "/api/admin/fortune/ai-pool/keys/{$key->id}/toggle", []],
            ['POST', "/api/admin/fortune/ai-pool/keys/{$key->id}/test", []],
            ['POST', '/api/admin/fortune/ai-pool/mode', ['provider' => 'gemini', 'mode' => 'smart']],
        ];

        foreach ($writes as [$method, $uri, $body]) {
            $this->json($method, $uri, $body)->assertStatus(401);
        }

        $this->actAs($this->makeMember());
        foreach ($writes as [$method, $uri, $body]) {
            $this->json($method, $uri, $body)->assertStatus(403);
        }

        // แอดมินธรรมดา (ไม่ใช่ super admin · ไม่มีสิทธิ์ manage_api_keys) แตะคลังคีย์ไม่ได้
        $this->actAs($this->makeAdmin());
        foreach (array_slice($writes, 1) as [$method, $uri, $body]) {
            $this->json($method, $uri, $body)->assertStatus(403)->assertJsonPath('error_code', 'PERMISSION_DENIED');
        }
        $this->assertTrue((bool) $key->fresh()->is_active);

        // แอดมินที่ได้สิทธิ์ manage_api_keys (คอลัมน์ permissions เดิม) ทำได้
        $this->actAs($this->makeAdmin(['permissions' => ['manage_api_keys']]));
        $this->postJson("/api/admin/fortune/ai-pool/keys/{$key->id}/toggle")->assertOk();
        $this->assertFalse((bool) $key->fresh()->is_active);
    }
}
