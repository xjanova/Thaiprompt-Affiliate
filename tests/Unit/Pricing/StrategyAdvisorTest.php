<?php

namespace Tests\Unit\Pricing;

use App\Services\Pricing\PricingEngine;
use App\Services\Pricing\StrategyAdvisor;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * ที่ปรึกษากลยุทธ์ราคา 3 แบบ (เจาะตลาด / สมดุล / พรีเมียม)
 *
 * ไม่ใช้ DB: ฉีดค่าตั้งผ่าน closure — ตัวเลขทุกตัวมาจาก PricingEngine ชุดเดียวกับตอนตัดเงินจริง
 * ตัวอย่างหลัก: ต้นทุน 100, GP 15%, ไม่จด VAT, ปิดระบบแนะนำ, เป้ากำไร 20%
 *  - ราคาคุ้มทุน = 100 ÷ 0.85 = 117.65
 *  - เจาะตลาด (กำไร 10%) = 100 ÷ 0.75 = 133.34 → ปัดเลขสวย 139
 *  - สมดุล (กำไร 20%) = 100 ÷ 0.65 = 153.85 → 159
 *  - พรีเมียม (กำไร 30%) = 100 ÷ 0.55 = 181.82 → 189
 */
class StrategyAdvisorTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $settings
     */
    private function advisor(array $settings = []): StrategyAdvisor
    {
        $engine = new PricingEngine(
            fn (string $key, mixed $default) => array_key_exists($key, $settings) ? $settings[$key] : $default,
            fn () => 999,
        );

        return new StrategyAdvisor($engine);
    }

    /**
     * @return array<string, mixed>
     */
    private function baseInput(array $overrides = []): array
    {
        return array_merge([
            'cost' => 100,
            'target_margin_percent' => 20,
            'gp_rate' => 15,
            'mlm_enabled' => false,
            'vat_registered' => false,
            'delivery' => 'none',
        ], $overrides);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function byKey(array $plan): array
    {
        $out = [];
        foreach ($plan['strategies'] as $strategy) {
            $out[$strategy['key']] = $strategy;
        }

        return $out;
    }

    private function hasWarning(array $plan, string $code): bool
    {
        return in_array($code, array_column($plan['warnings'], 'code'), true);
    }

    public function test_three_strategies_with_charm_prices_and_target_margins(): void
    {
        $plan = $this->advisor()->plan($this->baseInput());
        $s = $this->byKey($plan);

        $this->assertSame(['penetration', 'balanced', 'premium'], array_column($plan['strategies'], 'key'));
        $this->assertSame(139.0, $s['penetration']['price']);
        $this->assertSame(159.0, $s['balanced']['price']);
        $this->assertSame(189.0, $s['premium']['price']);

        $this->assertSame(153.85, $plan['min_price_for_target']);
        $this->assertSame(117.65, $plan['breakeven_price']);

        $this->assertGreaterThanOrEqual(10.0, $s['penetration']['margin_percent']);
        $this->assertGreaterThanOrEqual(20.0, $s['balanced']['margin_percent']);
        $this->assertGreaterThanOrEqual(30.0, $s['premium']['margin_percent']);

        // ทุกกลยุทธ์มีการแบ่งเงินครบ และกำไรตรงกับ breakdown
        foreach ($s as $strategy) {
            $this->assertIsArray($strategy['breakdown']);
            $this->assertSame($strategy['price'], $strategy['breakdown']['unit_price']);
            $this->assertSame($strategy['profit_per_unit'], $strategy['breakdown']['profit']);
            $this->assertSame(0, $strategy['breakeven_units'], 'ไม่มีค่าใช้จ่ายคงที่ = ขายชิ้นแรกก็มีกำไร');
        }

        $this->assertSame(35.15, $s['balanced']['profit_per_unit']);
        $this->assertSame('balanced', $plan['recommended']);
    }

    public function test_breakeven_units_cover_fixed_monthly_cost(): void
    {
        $plan = $this->advisor()->plan($this->baseInput(['fixed_monthly_cost' => 1000, 'monthly_volume' => 100]));
        $s = $this->byKey($plan);

        $this->assertSame(29, $s['balanced']['breakeven_units'], '1000 ÷ 35.15 = 28.45 → 29 ชิ้น');
        $this->assertSame(130, $s['penetration']['monthly']['volume']);
        $this->assertSame(100, $s['balanced']['monthly']['volume']);
        $this->assertSame(70, $s['premium']['monthly']['volume']);
        $this->assertSame(round(35.15 * 100 - 1000, 2), $s['balanced']['monthly']['profit']);
    }

    public function test_competitor_above_our_cost_gives_undercut_penetration(): void
    {
        $plan = $this->advisor()->plan($this->baseInput(['competitor_price' => 250]));
        $s = $this->byKey($plan);

        $this->assertSame(229.0, $s['penetration']['price']);
        $this->assertLessThan(0, $s['penetration']['vs_competitor_percent']);
        $this->assertSame(249.0, $s['balanced']['price']);
        $this->assertSame(289.0, $s['premium']['price']);
        $this->assertLessThan($s['balanced']['price'], $s['penetration']['price']);
        $this->assertLessThan($s['premium']['price'], $s['balanced']['price']);
    }

    public function test_competitor_below_breakeven_warns_and_recommends_premium(): void
    {
        $plan = $this->advisor()->plan($this->baseInput(['competitor_price' => 110]));

        $this->assertTrue($this->hasWarning($plan, 'COMPETITOR_BELOW_BREAKEVEN'));
        $this->assertSame('premium', $plan['recommended']);

        // ราคาเจาะตลาดต้องไม่ต่ำกว่าราคาที่ยังมีกำไรขั้นต่ำ แม้คู่แข่งจะถูกกว่า
        $s = $this->byKey($plan);
        $this->assertGreaterThan(0, $s['penetration']['profit_per_unit']);
    }

    public function test_rider_delivery_free_threshold(): void
    {
        $plan = $this->advisor()->plan($this->baseInput(['delivery' => 'rider']));

        // ค่าเริ่มต้น 30 บาท (2 กม.แรก) + 10 บาท/กม. × (3 − 2) = 40 บาท
        $this->assertSame(40.0, $plan['delivery']['estimated_fee']);
        // 2 × 40 ÷ 22.11% ≈ 362 → ปัดขึ้นทีละ 50 = 400
        $this->assertSame(400.0, $plan['delivery']['free_delivery_threshold']);
        $this->assertSame(3, $plan['delivery']['units_for_free_delivery']);

        $joined = implode("\n", $plan['recommendations']);
        $this->assertStringContainsString('ส่งฟรีเมื่อซื้อครบ 400', $joined);
    }

    public function test_rider_fee_follows_settings(): void
    {
        $plan = $this->advisor(['rider.base_fee' => '25', 'rider.per_km_fee' => '8', 'rider.free_km' => '1', 'rider.min_fee' => '25'])
            ->plan($this->baseInput(['delivery' => 'rider', 'avg_distance_km' => 4]));

        $this->assertSame(49.0, $plan['delivery']['estimated_fee'], '25 + 8 × (4 − 1)');
    }

    public function test_parcel_uses_given_fee(): void
    {
        $plan = $this->advisor()->plan($this->baseInput(['delivery' => 'parcel', 'delivery_fee' => 60]));

        $this->assertSame('parcel', $plan['delivery']['mode']);
        $this->assertSame(60.0, $plan['delivery']['estimated_fee']);
        $this->assertNotNull($plan['delivery']['free_delivery_threshold']);
    }

    public function test_unreachable_target_is_capped_with_warning(): void
    {
        // GP 60% + VAT 7/107 → กำไรสูงสุดราว 33.46%
        $plan = $this->advisor()->plan($this->baseInput(['gp_rate' => 60, 'vat_registered' => true, 'target_margin_percent' => 50]));

        $this->assertTrue($this->hasWarning($plan, 'TARGET_MARGIN_UNREACHABLE'));
        $this->assertLessThan(34.0, $plan['max_margin_percent']);
        foreach ($plan['strategies'] as $strategy) {
            $this->assertNotNull($strategy['price']);
            $this->assertGreaterThan(0, $strategy['profit_per_unit']);
        }
    }

    public function test_no_profitable_price_when_fees_exceed_revenue(): void
    {
        $plan = $this->advisor()->plan($this->baseInput(['gp_rate' => 95, 'vat_registered' => true]));

        $this->assertTrue($this->hasWarning($plan, 'NO_PROFITABLE_PRICE'));
        foreach ($plan['strategies'] as $strategy) {
            $this->assertNull($strategy['price']);
        }
    }

    public function test_referral_pv_is_deducted_in_every_strategy(): void
    {
        $plan = $this->advisor()->plan($this->baseInput(['mlm_enabled' => true, 'pv' => 10, 'commission_per_pv' => 1]));

        foreach ($plan['strategies'] as $strategy) {
            $this->assertSame(10.0, $strategy['breakdown']['referral_pool_amount']);
            $this->assertGreaterThanOrEqual($strategy['target_margin_percent'], $strategy['margin_percent']);
        }
    }

    public function test_bundle_offer_keeps_minimum_margin(): void
    {
        $plan = $this->advisor()->plan($this->baseInput());

        $this->assertNotNull($plan['bundle']);
        $this->assertSame(3, $plan['bundle']['quantity']);
        $this->assertSame(449.0, $plan['bundle']['price']);
        $this->assertGreaterThanOrEqual(10.0, $plan['bundle']['margin_percent']);
        $this->assertSame(300.0, $plan['bundle']['breakdown']['cost_total']);
    }

    public function test_gp_comparison_across_packages(): void
    {
        $plan = $this->advisor()->plan($this->baseInput([
            'monthly_volume' => 100,
            'compare_gp_rates' => [
                ['key' => 'free', 'label' => 'แพ็กเกจ Free', 'gp_rate' => 15],
                ['key' => 'premium', 'label' => 'แพ็กเกจ Premium', 'gp_rate' => 7],
            ],
        ]));

        $this->assertCount(2, $plan['gp_comparison']);
        $this->assertSame('premium', $plan['gp_comparison'][0]['key'], 'GP ต่ำสุดกำไรมากสุด ขึ้นก่อน');
        // ที่ราคาสมดุล 159: GP ต่างกัน 8% = 12.72 บาทต่อชิ้น
        $this->assertSame(12.72, $plan['gp_comparison'][0]['extra_profit_per_unit']);
        $this->assertSame(0.0, $plan['gp_comparison'][1]['extra_profit_per_unit']);
        $this->assertStringContainsString('แพ็กเกจ Premium', implode("\n", $plan['recommendations']));

        $empty = $this->advisor()->plan($this->baseInput(['gp_rate' => 95, 'vat_registered' => true]));
        $this->assertSame([], $empty['gp_comparison']);
    }

    public function test_current_price_loss_is_flagged(): void
    {
        $plan = $this->advisor()->plan($this->baseInput(['current_price' => 110]));

        $this->assertTrue($this->hasWarning($plan, 'CURRENT_PRICE_LOSS'));
        $this->assertLessThan(0, $plan['current']['profit_per_unit']);
    }

    public function test_recommendations_are_actionable_thai(): void
    {
        $plan = $this->advisor()->plan($this->baseInput(['competitor_price' => 250, 'delivery' => 'rider']));

        $this->assertNotEmpty($plan['recommendations']);
        foreach ($plan['recommendations'] as $text) {
            $this->assertIsString($text);
            $this->assertMatchesRegularExpression('/[\x{0E00}-\x{0E7F}]/u', $text);
        }
        foreach ($plan['warnings'] as $warning) {
            $this->assertMatchesRegularExpression('/^[A-Z_]+$/', $warning['code']);
        }

        $joined = implode("\n", $plan['recommendations']);
        $this->assertStringContainsString('ลงท้ายด้วยเลข 9', $joined);
        $this->assertStringContainsString('ราคาคุ้มทุน', $joined);
        $this->assertStringContainsString('ราคาที่เคยขายจริง', $joined, 'ต้องเตือนเรื่องราคาก่อนลดต้องเป็นราคาจริง');
        $this->assertStringContainsString('จด VAT', $joined);
        $this->assertNotEmpty($plan['formula_steps']);
    }

    public function test_cost_only_or_competitor_only_inputs(): void
    {
        $competitorOnly = $this->advisor()->plan(['cost' => 0, 'competitor_price' => 120, 'gp_rate' => 10, 'mlm_enabled' => false]);
        $this->assertTrue($this->hasWarning($competitorOnly, 'COST_MISSING'));
        $prices = array_column($competitorOnly['strategies'], 'price');
        $this->assertLessThan($prices[1], $prices[0]);
        $this->assertLessThan($prices[2], $prices[1]);
    }

    public function test_missing_cost_and_competitor_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->advisor()->plan(['cost' => 0]);
    }

    public function test_negative_cost_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->advisor()->plan(['cost' => -5, 'competitor_price' => 100]);
    }

    public function test_charm_pricing_rounding(): void
    {
        $advisor = $this->advisor();

        // [ราคาเข้า, ผลที่คาด] — ใช้คู่ค่าเพราะ key ของ array PHP เป็นทศนิยมไม่ได้
        $up = [[12.3, 13.0], [23, 25.0], [26, 29.0], [29.5, 35.0], [30, 35.0], [99.5, 109.0], [243, 249.0], [249, 249.0],
            [250, 259.0], [1234, 1239.0], [1999.5, 2090.0], [2345, 2390.0]];
        foreach ($up as [$input, $expected]) {
            $this->assertSame($expected, $advisor->charmUp((float) $input), "charmUp({$input})");
        }

        $down = [[485.5, 479.0], [23, 19.0], [26, 25.0], [100.5, 99.0], [2345, 2290.0], [15.7, 15.0]];
        foreach ($down as [$input, $expected]) {
            $this->assertSame($expected, $advisor->charmDown((float) $input), "charmDown({$input})");
        }
    }

    public function test_market_price_summary(): void
    {
        $summary = StrategyAdvisor::summarizeMarketPrices([400, '100', 300, 200, 'abc', 0, -5]);

        $this->assertSame(4, $summary['count']);
        $this->assertSame(100.0, $summary['min']);
        $this->assertSame(175.0, $summary['p25']);
        $this->assertSame(250.0, $summary['median']);
        $this->assertSame(325.0, $summary['p75']);
        $this->assertSame(400.0, $summary['max']);

        $this->assertSame(0, StrategyAdvisor::summarizeMarketPrices([])['count']);
    }
}
