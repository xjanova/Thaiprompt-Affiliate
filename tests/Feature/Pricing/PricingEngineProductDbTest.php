<?php

namespace Tests\Feature\Pricing;

use App\Models\MlmGlobalSetting;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Pricing\PricingEngine;
use App\Services\Pricing\StrategyAdvisor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * PricingEngine กับข้อมูลจริงในฐานข้อมูล (รันบน CI ที่มี MySQL)
 *
 * ตรวจ: migration เพิ่ม admin_gp_rate + ค่าตั้ง pricing, หา GP จากแพ็กเกจของร้านผ่าน seller_id,
 * แถว mlm_product_pv เป็นคำตอบของ PV (แม้เป็น 0), อัตรา custom ต่อ PV, snapshot ลง order_items ได้ครบ
 */
class PricingEngineProductDbTest extends TestCase
{
    use RefreshDatabase;

    private int $categoryId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->categoryId = (int) DB::table('product_categories')->insertGetId([
            'name' => 'หมวดทดสอบราคา',
            'slug' => 'pricing-test-'.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->setMlm(false);

        // ค่าที่ migration seed คือโปรฯ GP ฟรีช่วงเปิดตัว (0/0/0 + gp_free) — เทสต์ลำดับอำนาจ GP ใช้อัตราจริง
        Setting::set('pricing.gp_free', '0', 'boolean', 'pricing');
        Setting::set('pricing.default_gp_rate', '15', 'float', 'pricing');
        Setting::set('pricing.min_gp_rate', '5', 'float', 'pricing');
    }

    private function setMlm(bool $enabled, float $commissionPerPv = 1.0): void
    {
        MlmGlobalSetting::updateOrCreate(['key' => 'mlm_enabled'], ['value' => $enabled ? '1' : '0', 'type' => 'boolean', 'group' => 'general']);
        MlmGlobalSetting::updateOrCreate(['key' => 'commission_per_pv'], ['value' => (string) $commissionPerPv, 'type' => 'decimal', 'group' => 'pv']);
        Cache::flush();
    }

    private function sellerWithPackageStore(float $packageRate): User
    {
        $seller = User::factory()->create();

        $packageId = DB::table('vendor_packages')->insertGetId([
            'package_name' => 'pkg-'.uniqid(),
            'package_slug' => 'pkg-'.uniqid(),
            'display_name' => 'แพ็กเกจทดสอบ',
            'commission_rate' => $packageRate,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vendor_stores')->insert([
            'user_id' => $seller->id,
            'package_id' => $packageId,
            'store_name' => 'ร้านทดสอบ',
            'store_slug' => 'store-'.uniqid(),
            'commission_rate' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $seller;
    }

    private function product(User $seller, array $attributes = []): Product
    {
        $id = DB::table('products')->insertGetId(array_merge([
            'seller_id' => $seller->id,
            'store_id' => null,
            'category_id' => $this->categoryId,
            'name' => 'สินค้าทดสอบ',
            'slug' => 'p-'.uniqid(),
            'sku' => 'SKU-'.uniqid(),
            'price' => 200,
            'commission_rate' => 0,
            'pv_value' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        return Product::findOrFail($id);
    }

    public function test_migration_adds_admin_gp_rate_and_launch_pricing_defaults(): void
    {
        $this->assertTrue(Schema::hasColumn('products', 'admin_gp_rate'));

        // ค่าที่ migration seed (อ่านจากตารางตรงๆ เพราะ setUp ตั้งค่าทับเพื่อเทสต์อื่น)
        $seeded = DB::table('settings')->whereIn('key', [
            'pricing.fresh_market_gp_rate', 'pricing.referral_pool_percent',
        ])->pluck('value', 'key');
        $this->assertSame('0', (string) $seeded['pricing.fresh_market_gp_rate'], 'GP ตลาดสดช่วงเปิดตัว = 0');
        $this->assertSame(0.0, (float) $seeded['pricing.referral_pool_percent']);

        // โปรฯ GP ฟรีช่วงเปิดตัว: ร้านที่มีแพ็กเกจ 10% ก็ต้องได้ GP 0 จนกว่าแอดมินจะปิดโปรฯ
        Setting::set('pricing.gp_free', '1', 'boolean', 'pricing');
        $seller = $this->sellerWithPackageStore(10);
        $info = (new PricingEngine)->gpRateInfoForProduct($this->product($seller));
        $this->assertSame(0.0, $info['rate']);
        $this->assertSame(PricingEngine::GP_SOURCE_PROMO, $info['source']);
    }

    public function test_gp_comes_from_the_sellers_package_and_ignores_seller_commission_rate(): void
    {
        $seller = $this->sellerWithPackageStore(10);
        $product = $this->product($seller, ['commission_rate' => 0]);

        $info = (new PricingEngine)->gpRateInfoForProduct($product);

        $this->assertSame(10.0, $info['rate']);
        $this->assertSame(PricingEngine::GP_SOURCE_PACKAGE, $info['source']);
    }

    public function test_admin_gp_rate_overrides_package(): void
    {
        $seller = $this->sellerWithPackageStore(10);
        $product = $this->product($seller, ['admin_gp_rate' => 8]);

        $this->assertSame(8.0, (new PricingEngine)->gpRateForProduct($product));
    }

    public function test_pv_row_is_authoritative_even_when_zero(): void
    {
        $this->setMlm(true, 1.0);
        $seller = $this->sellerWithPackageStore(10);
        $product = $this->product($seller, ['pv_value' => 50]);

        DB::table('mlm_product_pv')->insert([
            'product_id' => $product->id,
            'mlm_plan_id' => null,
            'pv_value' => 0,
            'use_global_rate' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $b = (new PricingEngine)->quoteProduct($product);

        $this->assertSame(0.0, $b->referral_pool_amount, 'แอดมินตั้ง PV = 0 ต้องไม่หักค่าแนะนำ');
        $this->assertSame(180.0, $b->seller_net);
    }

    public function test_custom_commission_per_pv_is_used_when_not_global(): void
    {
        $this->setMlm(true, 1.0);
        $seller = $this->sellerWithPackageStore(10);
        $product = $this->product($seller);

        DB::table('mlm_product_pv')->insert([
            'product_id' => $product->id,
            'mlm_plan_id' => null,
            'pv_value' => 20,
            'use_global_rate' => false,
            'custom_commission_per_pv' => 0.5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $b = (new PricingEngine)->quoteProduct($product, 2);

        $this->assertSame(40.0, $b->pv_total);
        $this->assertSame(20.0, $b->referral_pool_amount);
    }

    public function test_product_without_pv_row_and_zero_pv_value_has_no_referral_deduction(): void
    {
        // บั๊กเดิม: global_pv_rate = 1 → หัก 100% ของยอดขาย
        $this->setMlm(true, 1.0);
        MlmGlobalSetting::updateOrCreate(['key' => 'global_pv_rate'], ['value' => '1.00', 'type' => 'decimal', 'group' => 'pv']);
        Cache::flush();

        $seller = $this->sellerWithPackageStore(10);
        $product = $this->product($seller);

        $b = (new PricingEngine)->quoteProduct($product);

        $this->assertSame(0.0, $b->referral_pool_amount);
        $this->assertSame(180.0, $b->seller_net);
    }

    public function test_vat_follows_store_registration_flag(): void
    {
        if (! Schema::hasColumn('vendor_stores', 'vat_registered')) {
            $this->markTestSkipped('vendor_stores.vat_registered ยังไม่มี (เพิ่มโดยงานฝั่งร้านค้า)');
        }

        $seller = $this->sellerWithPackageStore(10);
        $product = $this->product($seller, ['price' => 107]);

        $this->assertSame(0.0, (new PricingEngine)->quoteProduct($product)->vat_amount);

        DB::table('vendor_stores')->where('user_id', $seller->id)->update(['vat_registered' => true]);

        $this->assertSame(7.0, (new PricingEngine)->quoteProduct($product->fresh())->vat_amount);
    }

    public function test_item_snapshot_fills_order_item(): void
    {
        $seller = $this->sellerWithPackageStore(10);
        $product = $this->product($seller);

        $snapshot = (new PricingEngine)->itemSnapshot($product, 3, 200.0);
        $item = new OrderItem($snapshot);

        $this->assertSame(600.0, (float) $item->total);
        $this->assertSame(10.0, (float) $item->commission_rate);
        $this->assertSame(60.0, (float) $item->commission_amount);
        $this->assertSame(540.0, (float) $item->seller_earning);
    }

    public function test_package_gp_options_lists_active_packages_with_min_clamp(): void
    {
        DB::table('vendor_packages')->insert([
            ['package_name' => 'a', 'package_slug' => 'gp-a-'.uniqid(), 'display_name' => 'ทดสอบ A', 'commission_rate' => 2, 'is_active' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['package_name' => 'b', 'package_slug' => 'gp-b-'.uniqid(), 'display_name' => 'ทดสอบ B', 'commission_rate' => 9, 'is_active' => false, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $options = collect((new StrategyAdvisor)->packageGpOptions())->keyBy('label');

        $this->assertTrue($options->has('แพ็กเกจ ทดสอบ A'));
        $this->assertFalse($options->has('แพ็กเกจ ทดสอบ B'), 'แพ็กเกจที่ปิดอยู่ต้องไม่แสดง');
        $this->assertSame(5.0, $options['แพ็กเกจ ทดสอบ A']['gp_rate'], 'ต่ำกว่าขั้นต่ำต้องถูกปรับเป็น 5%');
    }

    public function test_market_reference_excludes_own_and_inactive_products(): void
    {
        $me = $this->sellerWithPackageStore(10);
        $other = $this->sellerWithPackageStore(10);

        $this->product($me, ['price' => 1000]);
        $this->product($other, ['price' => 100]);
        $this->product($other, ['price' => 300]);
        $this->product($other, ['price' => 5000, 'is_active' => false]);

        $ref = (new StrategyAdvisor)->marketReferenceForCategory($this->categoryId, $me->id);

        $this->assertSame(2, $ref['count']);
        $this->assertSame(200.0, $ref['median']);
    }
}
