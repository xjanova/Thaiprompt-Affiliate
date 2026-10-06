<?php

namespace Tests\Feature\AdminApp;

use App\Models\AiApiKey;
use App\Models\AiApiKeySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\TestCase;

/**
 * 🤖 GET/POST /api/admin/fortune/ai-pool — คีย์ต้องไม่หลุด · เขียนได้เฉพาะ super admin / manage_api_keys
 */
#[Group('admin-app')]
class AdminAppAiPoolTest extends TestCase
{
    use BuildsAdminAppWorld;
    use RefreshDatabase;

    private const SECRET_A = 'AIzaSyTESTsecretValue000000000000wxyz';

    private const SECRET_B = 'sk-deepseek-TESTsecret00000000000000abcd';

    protected function tearDown(): void
    {
        $this->tearDownAdminAppWorld();

        parent::tearDown();
    }

    public function test_index_masks_keys_and_reports_health_usage_and_modes(): void
    {
        $healthy = AiApiKey::create([
            'name' => 'Gemini หลัก', 'provider' => 'gemini', 'api_key' => self::SECRET_A,
            'is_active' => true, 'priority' => 80, 'purpose' => 'prediction', 'last_test_passed_at' => now(),
            'last_error' => 'boom for https://x.test/v1?key='.self::SECRET_A,
        ]);
        AiApiKey::create([
            'name' => 'DeepSeek', 'provider' => 'deepseek', 'api_key' => self::SECRET_B, 'is_active' => true,
        ]);
        DB::table('ai_api_key_usage_logs')->insert([
            ['ai_api_key_id' => $healthy->id, 'total_tokens' => 1200, 'is_success' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['ai_api_key_id' => $healthy->id, 'total_tokens' => 0, 'is_success' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['ai_api_key_id' => $healthy->id, 'total_tokens' => 999, 'is_success' => 1, 'created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)],
        ]);
        AiApiKeySetting::updateForProvider('gemini', ['rotation_mode' => 'least_used']);

        $this->actAs($this->makeAdmin());
        $res = $this->getJson('/api/admin/fortune/ai-pool')->assertOk();

        $body = $res->getContent();
        $this->assertStringNotContainsString(self::SECRET_A, $body);
        $this->assertStringNotContainsString(self::SECRET_B, $body);
        $this->assertStringNotContainsString('api_key"', $body);

        $res->assertJsonPath('data.summary.total', 2)
            ->assertJsonPath('data.summary.healthy', 1)
            ->assertJsonPath('data.global_mode_writable', false)
            ->assertJsonPath('data.can_manage', false);

        $gem = collect($res->json('data.keys'))->firstWhere('id', $healthy->id);
        $this->assertSame('••••wxyz', $gem['key_masked']);
        $this->assertTrue($gem['healthy']);
        $this->assertSame(['prediction'], $gem['purposes']);
        $this->assertSame(['requests' => 2, 'tokens' => 1200, 'errors' => 1], $gem['usage_today']);

        $deep = collect($res->json('data.keys'))->firstWhere('provider', 'deepseek');
        $this->assertFalse($deep['healthy'], 'ยังไม่เคยเทสผ่าน = ไม่ลงสนาม');

        $this->assertSame('least_used', collect($res->json('data.providers'))->firstWhere('provider', 'gemini')['rotation_mode']);
        $this->assertContains('smart', collect($res->json('data.modes'))->pluck('key')->all());
    }

    public function test_super_admin_can_toggle_test_and_set_provider_mode(): void
    {
        $key = AiApiKey::create([
            'name' => 'DeepSeek', 'provider' => 'deepseek', 'api_key' => self::SECRET_B,
            'is_active' => true, 'last_test_passed_at' => now()->subDay(),
        ]);
        $this->actAs($this->makeSuperAdmin());

        $this->postJson("/api/admin/fortune/ai-pool/keys/{$key->id}/toggle")->assertOk()
            ->assertJsonPath('data.key.is_active', false);
        $this->postJson("/api/admin/fortune/ai-pool/keys/{$key->id}/toggle")->assertOk()
            ->assertJsonPath('data.key.is_active', true);

        // รูทีนเทสของเว็บไม่รองรับ deepseek → ไม่ผ่าน · บันทึก last_test_failed_at แบบเดียวกับกดบนเว็บ (ไม่ยิงเน็ตจริง)
        $test = $this->postJson("/api/admin/fortune/ai-pool/keys/{$key->id}/test")->assertOk();
        $test->assertJsonPath('success', false)->assertJsonPath('data.passed', false);
        $this->assertStringNotContainsString(self::SECRET_B, $test->getContent());
        $this->assertNotNull($key->fresh()->last_test_failed_at);

        $this->postJson('/api/admin/fortune/ai-pool/mode', ['mode' => 'smart'])
            ->assertStatus(422)->assertJsonPath('error_code', 'GLOBAL_MODE_ENV_ONLY');
        $this->postJson('/api/admin/fortune/ai-pool/mode', ['provider' => 'gemini', 'mode' => 'warp'])->assertStatus(422);
        $this->postJson('/api/admin/fortune/ai-pool/mode', ['provider' => 'nope', 'mode' => 'smart'])->assertStatus(422);
        $this->postJson('/api/admin/fortune/ai-pool/mode', ['provider' => 'gemini', 'mode' => 'priority'])->assertOk()
            ->assertJsonPath('data.rotation_mode', 'priority');
        $this->assertSame('priority', AiApiKeySetting::where('provider', 'gemini')->value('rotation_mode'));
    }
}
