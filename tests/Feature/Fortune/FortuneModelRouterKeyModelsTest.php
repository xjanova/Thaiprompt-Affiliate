<?php

namespace Tests\Feature\Fortune;

use App\Models\AiApiKey;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\CelticCrossService;
use App\Services\Fortune\FortuneModelRouter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * 🎛️ (2026-10-30, owner) router ต้องใช้โมเดลตามที่ตั้งบน key ไม่ใช่ชื่อที่ฝังในโค้ด
 *
 * เหตุจริง: เปลี่ยน key ไป gen 6 แล้ว เทิร์นคุณไสย์/คำถามยาก/คุยต่อ 39 ยังวิ่ง gpt-5.6 เงียบๆ
 */
class FortuneModelRouterKeyModelsTest extends TestCase
{
    use RefreshDatabase;

    private function key(array $attrs): AiApiKey
    {
        return AiApiKey::create($attrs + [
            'name' => 'เทส',
            'provider' => 'openai',
            'api_key' => 'sk-'.str_repeat('t1', 20),
            'is_active' => true,
            'priority' => 50,
        ]);
    }

    private function router(): FortuneModelRouter
    {
        return new FortuneModelRouter(new FortuneTellingSetting);
    }

    private function reading(): FortuneReading
    {
        return (new FortuneReading)->forceFill(['id' => 1, 'platform' => 'line', 'facebook_user_id' => 'U-test']);
    }

    public function test_models_follow_the_keys_set_by_the_admin(): void
    {
        $this->key(['purpose' => 'sensitive', 'model' => 'gpt-6.1-sol']);
        $this->key(['purpose' => 'prediction', 'model' => 'gpt-6-luna']);

        $this->assertSame('gpt-6.1-sol', FortuneModelRouter::strongModel());
        $this->assertSame('gpt-6-luna', FortuneModelRouter::defaultModel());
    }

    public function test_falls_back_when_no_key_can_supply_a_model(): void
    {
        // ปิดอยู่ · ไม่ได้ตั้งโมเดล · คนละค่าย — นับไม่ได้ทั้งหมด
        $this->key(['purpose' => 'sensitive', 'model' => 'gpt-6.1-sol', 'is_active' => false]);
        $this->key(['purpose' => 'sensitive', 'model' => null]);
        $this->key(['purpose' => 'sensitive', 'model' => 'gemini-2.5-pro', 'provider' => 'gemini']);

        $this->assertSame(FortuneModelRouter::MODEL_STRONG, FortuneModelRouter::strongModel());
        $this->assertSame(FortuneModelRouter::MODEL_DEFAULT, FortuneModelRouter::defaultModel());
    }

    public function test_highest_priority_key_decides_the_model(): void
    {
        $this->key(['purpose' => 'prediction', 'model' => 'gpt-6.1-terra', 'priority' => 10]);
        $this->key(['purpose' => 'prediction_deep', 'model' => 'gpt-6-luna', 'priority' => 90]);

        $this->assertSame('gpt-6-luna', FortuneModelRouter::defaultModel());
    }

    public function test_black_magic_celtic_turn_overrides_with_the_key_model(): void
    {
        $this->key(['purpose' => 'sensitive', 'model' => 'gpt-6.1-sol']);
        $this->mock(CelticCrossService::class, function (MockInterface $mock) {
            $mock->shouldReceive('isBlackMagicModeForced')->andReturn(true);
        });

        $this->assertSame(['openai' => 'gpt-6.1-sol'], $this->router()->celticOverrides($this->reading(), 'โดนของไหม'));
    }

    public function test_normal_turns_keep_the_key_model(): void
    {
        $this->key(['purpose' => 'sensitive', 'model' => 'gpt-6.1-sol']);
        $this->key(['purpose' => 'prediction', 'model' => 'gpt-6-luna']);
        $this->mock(CelticCrossService::class, function (MockInterface $mock) {
            $mock->shouldReceive('isBlackMagicModeForced')->andReturn(false);
        });

        // คำถามสั้นกว่าเกณฑ์ = ไม่ใช่คำถามยาก จึงไม่ยกระดับ
        $this->assertNull($this->router()->celticOverrides($this->reading(), 'ค่ะ'));
        $this->assertSame('gpt-6-luna', $this->router()->proSessionModel($this->reading(), 'ค่ะ'));
    }
}
