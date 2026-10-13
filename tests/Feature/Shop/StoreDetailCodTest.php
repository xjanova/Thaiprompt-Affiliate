<?php

namespace Tests\Feature\Shop;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * หน้าร้านในแอป (GET /api/v1/mobile/stores/{id}) — ป้าย "เก็บเงินปลายทาง" ต้องตรงกับที่หน้าชำระเงินมีให้เลือกจริง
 *
 * cod_available = ส่งด้วยไรเดอร์ได้ + แอดมินเปิด rider.allow_cod + วงเงิน COD > 0
 * (เดิมดูแค่ส่งด้วยไรเดอร์ได้ → หน้าร้านโฆษณา COD แต่ checkout ไม่มีให้เลือกเพราะ rider.allow_cod ปิดเป็นค่าเริ่มต้น)
 */
#[Group('shop')]
class StoreDetailCodTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    public function test_cod_badge_hidden_when_rider_cod_is_off_by_default(): void
    {
        [, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true]);
        Sanctum::actingAs($this->makeBuyer());

        $this->getJson("/api/v1/mobile/stores/{$store->id}")
            ->assertOk()
            ->assertJsonPath('data.rider_delivery', true)
            ->assertJsonPath('data.cod_available', false);
    }

    public function test_cod_badge_shown_only_when_admin_allows_rider_cod_with_a_limit(): void
    {
        Setting::set('rider.allow_cod', '1', 'boolean', 'rider');
        [, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true]);
        [, $noRider] = $this->makeSellerWithStore(['rider_delivery_enabled' => false]);
        Sanctum::actingAs($this->makeBuyer());

        $this->getJson("/api/v1/mobile/stores/{$store->id}")
            ->assertOk()
            ->assertJsonPath('data.cod_available', true);

        // ร้านไม่ส่งด้วยไรเดอร์ = ไม่มี COD (COD มีเฉพาะงานไรเดอร์)
        $this->getJson("/api/v1/mobile/stores/{$noRider->id}")
            ->assertOk()
            ->assertJsonPath('data.cod_available', false);

        // วงเงิน COD = 0 = ปิดรับเก็บเงินปลายทาง
        Setting::set('rider.max_cod_amount', '0', 'float', 'rider');
        $this->getJson("/api/v1/mobile/stores/{$store->id}")
            ->assertOk()
            ->assertJsonPath('data.cod_available', false);
    }
}
