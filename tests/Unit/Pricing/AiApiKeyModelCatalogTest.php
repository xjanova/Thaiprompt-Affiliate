<?php

namespace Tests\Unit\Pricing;

use App\Models\AiApiKey;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * 💰 รายการโมเดล + ราคาใน AI API Key Pool (2026-10-30)
 *
 * กันไม่ให้ dropdown กับตารางราคาเดินคนละทาง — โมเดลที่เลือกได้ต้องรู้ราคา
 * (ยกเว้นที่ค่ายไม่ประกาศราคาจริงๆ) เพื่อให้คอลัมน์ "ค่าใช้จ่าย" ไม่ว่าง
 */
class AiApiKeyModelCatalogTest extends TestCase
{
    /** ค่ายไม่ประกาศราคาต่อหน่วย — ยอมให้แสดง "ไม่ทราบราคา" */
    private const UNPRICED = [
        'openai/gpt-5.4-pro',
        'minimax/speech-01-hd',
        'minimax/speech-01-turbo',
    ];

    #[Test]
    public function every_selectable_model_has_a_price(): void
    {
        $missing = [];
        foreach (AiApiKey::MODELS_BY_PROVIDER as $provider => $models) {
            foreach ($models as $model) {
                if (AiApiKey::pricingFor($provider, $model) === null && ! in_array("{$provider}/{$model}", self::UNPRICED, true)) {
                    $missing[] = "{$provider}/{$model}";
                }
            }
        }

        $this->assertSame([], $missing);
    }

    #[Test]
    public function every_priced_model_is_selectable(): void
    {
        $orphans = [];
        foreach (AiApiKey::MODEL_PRICING as $provider => $rows) {
            foreach (array_keys($rows) as $model) {
                if (! in_array($model, AiApiKey::MODELS_BY_PROVIDER[$provider] ?? [], true)) {
                    $orphans[] = "{$provider}/{$model}";
                }
            }
        }

        $this->assertSame([], $orphans);
    }

    #[Test]
    public function cost_is_input_plus_output_per_million_tokens(): void
    {
        // gpt-5.6-luna = $0.20 เข้า / $1.20 ออก ต่อ 1M
        $this->assertEqualsWithDelta(0.80, AiApiKey::estimateCostUsd('openai', 'gpt-5.6-luna', 1_000_000, 500_000), 1e-9);
        $this->assertEqualsWithDelta(0.0, AiApiKey::estimateCostUsd('openai', 'gpt-5.6-luna', 0, 0), 1e-9);
    }

    #[Test]
    public function unknown_or_character_priced_models_have_no_token_cost(): void
    {
        $this->assertNull(AiApiKey::estimateCostUsd('openai', 'no-such-model', 1000, 1000));
        $this->assertNull(AiApiKey::estimateCostUsd('openai', 'tts-1', 1000, 0));
        $this->assertNull(AiApiKey::estimateCostUsd('openai', null, 1000, 1000));
    }

    #[Test]
    public function free_models_cost_zero(): void
    {
        $this->assertSame(0.0, (float) AiApiKey::estimateCostUsd('typhoon', 'typhoon-v2.5-30b-a3b-instruct', 5000, 5000));
    }

    #[Test]
    public function anthropic_dated_snapshot_uses_alias_price(): void
    {
        $this->assertSame(
            AiApiKey::pricingFor('anthropic', 'claude-haiku-4-5'),
            AiApiKey::pricingFor('anthropic', 'claude-haiku-4-5-20251001'),
        );
    }

    #[Test]
    public function default_model_is_first_in_list_and_labels_cover_every_model(): void
    {
        $labels = AiApiKey::modelOptionLabels();

        foreach (AiApiKey::MODELS_BY_PROVIDER as $provider => $models) {
            $this->assertSame($models[0], AiApiKey::defaultModelFor($provider));
            $this->assertSame($models, array_keys($labels[$provider]));
            foreach ($labels[$provider] as $model => $label) {
                $this->assertStringStartsWith($model.' — ', $label);
            }
        }

        $this->assertNull(AiApiKey::defaultModelFor('no-such-provider'));
    }
}
