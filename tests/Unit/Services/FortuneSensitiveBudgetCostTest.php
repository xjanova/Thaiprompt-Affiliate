<?php

namespace Tests\Unit\Services;

use App\Services\Fortune\FortuneSensitiveBudgetGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * 💰 (2026-10-30) ค่าประมาณต้นทุนของ GPT-6 ต้องตรงราคาจริง — เดิมตกไป default $2/M ทุกรุ่น
 *
 * เทสต์ล้วน ไม่ boot แอป ไม่แตะ DB
 */
class FortuneSensitiveBudgetCostTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: float}>
     */
    public static function gpt6Models(): array
    {
        return [
            'luna' => ['gpt-6-luna', 13.68],
            'terra' => ['gpt-6.1-terra', 136.8],
            'sol 6.1' => ['gpt-6.1-sol', 273.6],
            'sol 6' => ['gpt-6-sol', 273.6],
            'astra' => ['gpt-6-astra', 1368.0],
        ];
    }

    #[Test]
    #[DataProvider('gpt6Models')]
    public function gpt6_cost_uses_its_own_blended_price(string $model, float $thbPerMillion): void
    {
        $this->assertSame($thbPerMillion, FortuneSensitiveBudgetGuard::estimateCostThb(1_000_000, $model));
    }

    #[Test]
    public function gpt56_prices_stay_the_same(): void
    {
        $this->assertSame(32.4, FortuneSensitiveBudgetGuard::estimateCostThb(1_000_000, 'gpt-5.6-luna'));
        $this->assertSame(810.0, FortuneSensitiveBudgetGuard::estimateCostThb(1_000_000, 'gpt-5.6-sol'));
    }
}
