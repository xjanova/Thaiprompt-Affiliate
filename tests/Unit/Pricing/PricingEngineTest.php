<?php

namespace Tests\Unit\Pricing;

use App\Models\FreshMarketListing;
use App\Models\Product;
use App\Models\VendorPackage;
use App\Models\VendorStore;
use App\Services\Pricing\PriceBreakdown;
use App\Services\Pricing\PricingEngine;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * เครื่องคำนวณราคากลาง — สูตรเดียวที่ทุกจุดใช้ (checkout / แบ่งเงิน / ตัวจำลองของผู้ขาย / API แอป)
 *
 * ไม่ใช้ DB: ฉีดค่าตั้งผ่าน closure และสร้าง model ในหน่วยความจำ (exists = false จึงไม่ไปอ่าน mlm_product_pv)
 * ครอบบั๊กเดิม: ไม่มี PV แล้วถูกหักค่าแนะนำ 100%, ปิด MLM แล้วยังหัก, หัก VAT ร้านที่ไม่ได้จด VAT,
 * และผู้ขายส่ง commission_rate=0 มาเองแล้วแพลตฟอร์มได้ GP 0
 */
class PricingEngineTest extends TestCase
{
    private const OFFICIAL_SELLER_ID = 999;

    /**
     * @param  array<string, mixed>  $settings
     */
    private function engine(array $settings = []): PricingEngine
    {
        return new PricingEngine(
            fn (string $key, mixed $default) => array_key_exists($key, $settings) ? $settings[$key] : $default,
            fn () => self::OFFICIAL_SELLER_ID,
        );
    }

    private function amountOfLine(PriceBreakdown $b, string $key): ?float
    {
        foreach ($b->lines as $line) {
            if ($line['key'] === $key) {
                return $line['amount'];
            }
        }

        return null;
    }

    // ------------------------------------------------------------------
    // breakdown()
    // ------------------------------------------------------------------

    public function test_basic_split_without_vat_or_referral(): void
    {
        $b = $this->engine()->breakdown(100, 2, ['gp_rate' => 10, 'mlm_enabled' => false]);

        $this->assertSame(200.0, $b->gross);
        $this->assertSame(10.0, $b->gp_rate);
        $this->assertSame(20.0, $b->gp_amount);
        $this->assertSame(0.0, $b->vat_amount);
        $this->assertSame(0.0, $b->referral_pool_amount);
        $this->assertSame(180.0, $b->seller_net);
        $this->assertSame(20.0, $b->platform_net);
        $this->assertSame(200.0, $b->buyer_total);
        $this->assertSame([], $b->warnings);
        $this->assertSame(180.0, $this->amountOfLine($b, 'seller_net'));
        $this->assertSame(-20.0, $this->amountOfLine($b, 'gp'));
    }

    public function test_vat_is_extracted_only_for_vat_registered_sellers(): void
    {
        $registered = $this->engine()->breakdown(107, 1, ['gp_rate' => 10, 'mlm_enabled' => false, 'vat_registered' => true, 'vat_rate' => 7]);
        $this->assertSame(7.0, $registered->vat_amount, 'ราคารวม VAT แล้ว → ถอด 7/107');
        $this->assertSame(10.7, $registered->gp_amount);
        $this->assertSame(89.3, $registered->seller_net);

        $notRegistered = $this->engine()->breakdown(107, 1, ['gp_rate' => 10, 'mlm_enabled' => false, 'vat_registered' => false]);
        $this->assertSame(0.0, $notRegistered->vat_amount, 'ร้านที่ไม่ได้จด VAT ต้องไม่ถูกหัก VAT');
        $this->assertSame(0.0, $notRegistered->vat_rate);
        $this->assertSame(96.3, $notRegistered->seller_net);
        $this->assertNull($this->amountOfLine($notRegistered, 'vat'));
    }

    public function test_referral_pool_is_zero_when_mlm_disabled_even_with_pv(): void
    {
        $b = $this->engine()->breakdown(100, 1, ['gp_rate' => 10, 'mlm_enabled' => false, 'pv' => 20, 'commission_per_pv' => 1]);

        $this->assertSame(0.0, $b->referral_pool_amount);
        $this->assertSame(0.0, $b->pv_total);
        $this->assertSame(90.0, $b->seller_net);
    }

    public function test_product_without_pv_never_loses_the_whole_sale_to_referral_pool(): void
    {
        // บั๊กเดิม: ไม่มี PV → PV = ราคา × global_pv_rate(1) × commission_per_pv(1) = หัก 100%
        $b = $this->engine()->breakdown(500, 1, ['gp_rate' => 10, 'mlm_enabled' => true, 'pv' => 0, 'commission_per_pv' => 1]);

        $this->assertSame(0.0, $b->referral_pool_amount);
        $this->assertSame(450.0, $b->seller_net);
    }

    public function test_referral_pool_from_pv_scales_with_quantity(): void
    {
        $b = $this->engine()->breakdown(200, 3, [
            'gp_rate' => 10, 'mlm_enabled' => true, 'pv' => 10, 'commission_per_pv' => 1.5,
        ]);

        $this->assertSame(30.0, $b->pv_total);
        $this->assertSame(45.0, $b->referral_pool_amount);
        $this->assertSame(600.0 - 60.0 - 45.0, $b->seller_net);
        $this->assertSame(-45.0, $this->amountOfLine($b, 'referral_pool'));
    }

    public function test_referral_pool_percent_applies_only_without_pv(): void
    {
        $engine = $this->engine();
        $percent = $engine->breakdown(100, 1, ['gp_rate' => 10, 'mlm_enabled' => true, 'pv' => 0, 'referral_pool_percent' => 5]);
        $this->assertSame(5.0, $percent->referral_pool_amount);

        $pv = $engine->breakdown(100, 1, ['gp_rate' => 10, 'mlm_enabled' => true, 'pv' => 2, 'commission_per_pv' => 1, 'referral_pool_percent' => 5]);
        $this->assertSame(2.0, $pv->referral_pool_amount, 'มี PV แล้วใช้ PV ไม่ใช้ %');
    }

    public function test_referral_pool_is_capped(): void
    {
        $engine = $this->engine();

        $capped = $engine->breakdown(100, 1, [
            'gp_rate' => 10, 'mlm_enabled' => true, 'pv' => 1000, 'commission_per_pv' => 1, 'referral_pool_cap_percent' => 50,
        ]);
        $this->assertSame(50.0, $capped->referral_pool_amount);
        $this->assertTrue($capped->hasWarning('REFERRAL_POOL_CAPPED'));
        $this->assertSame(40.0, $capped->seller_net);

        // เพดานที่ 2: ไม่เกินยอดที่เหลือหลัง GP + VAT
        $remaining = $engine->breakdown(100, 1, [
            'gp_rate' => 60, 'mlm_enabled' => true, 'pv' => 1000, 'commission_per_pv' => 1, 'referral_pool_cap_percent' => 50,
        ]);
        $this->assertSame(40.0, $remaining->referral_pool_amount);
        $this->assertSame(0.0, $remaining->seller_net);
    }

    public function test_cashback_is_funded_by_platform_by_default(): void
    {
        $engine = $this->engine();

        $platform = $engine->breakdown(1000, 1, ['gp_rate' => 10, 'mlm_enabled' => false, 'cashback_rate' => 2]);
        $this->assertSame(20.0, $platform->cashback_amount);
        $this->assertSame('platform', $platform->cashback_funded_by);
        $this->assertSame(900.0, $platform->seller_net, 'เงินคืนตามโปรแพลตฟอร์มไม่หักจากร้าน');
        $this->assertSame(80.0, $platform->platform_net);

        $seller = $engine->breakdown(1000, 1, ['gp_rate' => 10, 'mlm_enabled' => false, 'cashback_rate' => 2, 'cashback_funded_by' => 'seller']);
        $this->assertSame(880.0, $seller->seller_net);
        $this->assertSame(100.0, $seller->platform_net);
    }

    public function test_payment_fee_subsidy_and_buyer_shipping(): void
    {
        $b = $this->engine()->breakdown(500, 1, [
            'gp_rate' => 10,
            'mlm_enabled' => false,
            'payment_fee_rate' => 1.5,
            'shipping_fee' => 40,
            'seller_shipping_subsidy' => 20,
        ]);

        $this->assertSame(7.5, $b->payment_fee);
        $this->assertSame(40.0, $b->shipping_fee);
        $this->assertSame(540.0, $b->buyer_total, 'ค่าส่งที่ลูกค้าจ่ายรวมในยอดชำระ');
        $this->assertSame(500.0 - 50.0 - 7.5 - 20.0, $b->seller_net, 'ค่าส่งที่ลูกค้าจ่ายไม่ใช่รายได้ร้าน แต่ค่าส่งที่ร้านออกเองต้องหัก');
    }

    public function test_cost_gives_profit_margin_and_below_cost_warning(): void
    {
        $engine = $this->engine();

        $ok = $engine->breakdown(200, 1, ['gp_rate' => 15, 'mlm_enabled' => false, 'cost_per_unit' => 100]);
        $this->assertSame(100.0, $ok->cost_total);
        $this->assertSame(70.0, $ok->profit);
        $this->assertSame(35.0, $ok->margin_percent);
        $this->assertFalse($ok->isLoss());

        $loss = $engine->breakdown(110, 1, ['gp_rate' => 15, 'mlm_enabled' => false, 'cost_per_unit' => 100]);
        $this->assertSame(-6.5, $loss->profit);
        $this->assertTrue($loss->isLoss());
        $this->assertTrue($loss->hasWarning('BELOW_COST'));
    }

    public function test_amounts_are_rounded_to_satang(): void
    {
        $b = $this->engine()->breakdown(33.33, 3, ['gp_rate' => 15, 'mlm_enabled' => false, 'vat_registered' => true, 'vat_rate' => 7]);

        $this->assertSame(99.99, $b->gross);
        $this->assertSame(15.0, $b->gp_amount);
        $this->assertSame(6.54, $b->vat_amount);
        $this->assertSame(78.45, $b->seller_net);
    }

    public function test_to_array_returns_real_numbers(): void
    {
        $array = $this->engine()->breakdown(99, 1, ['gp_rate' => 12.5, 'mlm_enabled' => false])->toArray();

        foreach (['gross', 'gp_rate', 'gp_amount', 'vat_amount', 'referral_pool_amount', 'cashback_amount', 'payment_fee', 'seller_net', 'platform_net'] as $key) {
            $this->assertIsFloat($array[$key], "{$key} ต้องเป็นตัวเลข ไม่ใช่ string");
        }
        $this->assertIsInt($array['quantity']);
        $this->assertIsArray($array['lines']);
        $this->assertFalse($array['is_loss']);
    }

    public function test_missing_options_fall_back_to_settings(): void
    {
        $engine = $this->engine([
            'pricing.default_gp_rate' => '12',
            'mlm:mlm_enabled' => '1',
            'mlm:commission_per_pv' => '2',
        ]);

        $b = $engine->breakdown(100, 1, ['pv' => 5]);

        $this->assertSame(12.0, $b->gp_rate);
        $this->assertSame(10.0, $b->referral_pool_amount);
    }

    public function test_invalid_input_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->engine()->breakdown(-1, 1, []);
    }

    public function test_zero_quantity_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->engine()->breakdown(100, 0, []);
    }

    // ------------------------------------------------------------------
    // gpRateForProduct() — ลำดับอำนาจ
    // ------------------------------------------------------------------

    private function product(array $attributes, ?VendorStore $store = null): Product
    {
        $product = new Product;
        $product->forceFill(array_merge(['seller_id' => 10, 'price' => 100, 'commission_rate' => 0], $attributes));
        $product->setRelation('store', $store);

        return $product;
    }

    private function store(array $attributes = [], ?VendorPackage $package = null): VendorStore
    {
        $store = new VendorStore;
        $store->forceFill(array_merge(['user_id' => 10, 'commission_rate' => null], $attributes));
        $store->setRelation('package', $package);

        return $store;
    }

    private function package(float $rate): VendorPackage
    {
        $package = new VendorPackage;
        $package->forceFill(['package_name' => 'basic', 'display_name' => 'Basic', 'commission_rate' => $rate]);

        return $package;
    }

    public function test_official_shop_products_have_no_gp(): void
    {
        $info = $this->engine()->gpRateInfoForProduct($this->product(['seller_id' => self::OFFICIAL_SELLER_ID, 'admin_gp_rate' => 20]));

        $this->assertSame(0.0, $info['rate']);
        $this->assertSame(PricingEngine::GP_SOURCE_OFFICIAL, $info['source']);
    }

    public function test_admin_override_wins_over_package(): void
    {
        $product = $this->product(['admin_gp_rate' => '8.00'], $this->store([], $this->package(15)));

        $info = $this->engine()->gpRateInfoForProduct($product);

        $this->assertSame(8.0, $info['rate']);
        $this->assertSame(PricingEngine::GP_SOURCE_ADMIN, $info['source']);
    }

    public function test_package_rate_is_used_and_seller_commission_rate_is_ignored(): void
    {
        // ผู้ขายยิง commission_rate = 0 มาเอง → ต้องไม่มีผล
        $product = $this->product(['commission_rate' => 0], $this->store(['commission_rate' => 12], $this->package(7)));

        $info = $this->engine()->gpRateInfoForProduct($product);

        $this->assertSame(7.0, $info['rate']);
        $this->assertSame(PricingEngine::GP_SOURCE_PACKAGE, $info['source']);
        $this->assertStringContainsString('Basic', $info['label_th']);
    }

    public function test_store_rate_is_used_when_store_has_no_package(): void
    {
        $product = $this->product([], $this->store(['commission_rate' => '12.00']));

        $info = $this->engine()->gpRateInfoForProduct($product);

        $this->assertSame(12.0, $info['rate']);
        $this->assertSame(PricingEngine::GP_SOURCE_STORE, $info['source']);
    }

    public function test_default_rate_when_no_store(): void
    {
        $product = $this->product(['seller_id' => null]);

        $this->assertSame(15.0, $this->engine()->gpRateForProduct($product));
        $this->assertSame(20.0, $this->engine(['pricing.default_gp_rate' => '20'])->gpRateForProduct($product));
    }

    public function test_rate_is_clamped_to_minimum(): void
    {
        $product = $this->product(['admin_gp_rate' => 2], $this->store([], $this->package(1)));

        $info = $this->engine()->gpRateInfoForProduct($product);
        $this->assertSame(5.0, $info['rate']);
        $this->assertTrue($info['clamped']);

        $packageOnly = $this->product([], $this->store([], $this->package(1)));
        $this->assertSame(3.0, $this->engine(['pricing.min_gp_rate' => '3'])->gpRateForProduct($packageOnly));
    }

    // ------------------------------------------------------------------
    // โปรฯ GP ฟรีช่วงเปิดตัว (คำสั่งเจ้าของ 2026-09-25)
    // ------------------------------------------------------------------

    public function test_launch_promo_makes_gp_free_for_package_stores_but_keeps_admin_override(): void
    {
        $engine = $this->engine(['pricing.gp_free' => '1', 'pricing.min_gp_rate' => '5']);

        $info = $engine->gpRateInfoForProduct($this->product([], $this->store([], $this->package(15))));
        $this->assertSame(0.0, $info['rate'], 'แพ็กเกจฟรี 15% ต้องไม่ถูกเก็บ GP ช่วงโปรฯ');
        $this->assertSame(PricingEngine::GP_SOURCE_PROMO, $info['source']);
        $this->assertFalse($info['clamped'], 'ช่วงโปรฯ ต้องไม่ถูกบีบขึ้นเป็นอัตราขั้นต่ำ');

        $storeOnly = $engine->gpRateInfoForProduct($this->product([], $this->store(['commission_rate' => 10])));
        $this->assertSame(0.0, $storeOnly['rate']);

        // แอดมินตั้งอัตรารายสินค้าไว้ = ตัดสินใจเฉพาะสินค้านั้น → ยังใช้อัตราแอดมิน
        $this->assertSame(8.0, $engine->gpRateForProduct($this->product(['admin_gp_rate' => 8], $this->store([], $this->package(15)))));

        $listing = new FreshMarketListing;
        $this->assertSame(0.0, $this->engine(['pricing.gp_free' => '1', 'pricing.fresh_market_gp_rate' => '5'])->gpRateForFreshListing($listing));
        $this->assertSame(5.0, $this->engine(['pricing.gp_free' => '0', 'pricing.fresh_market_gp_rate' => '5'])->gpRateForFreshListing($listing));
    }

    public function test_launch_promo_end_date_is_inclusive_of_the_whole_day(): void
    {
        $engine = $this->engine(['pricing.gp_free' => '1', 'pricing.gp_free_until' => '2026-10-31']);

        $this->assertTrue($engine->gpPromoActive(new \DateTimeImmutable('2026-10-31 23:30:00')));
        $this->assertFalse($engine->gpPromoActive(new \DateTimeImmutable('2026-11-01 00:00:05')));

        $this->assertFalse($this->engine(['pricing.gp_free' => '0'])->gpPromoActive(), 'ปิดโปรฯ แล้วต้องกลับไปคิด GP ตามปกติ');
        $this->assertFalse($this->engine()->gpPromoActive(), 'ไม่มีค่าตั้ง = ไม่มีโปรฯ');
        $this->assertTrue($this->engine(['pricing.gp_free' => true, 'pricing.gp_free_until' => ''])->gpPromoActive(), 'ไม่ได้ตั้งวันสิ้นสุด = ฟรีจนกว่าแอดมินปิด');
        $this->assertTrue($this->engine(['pricing.gp_free' => '1', 'pricing.gp_free_until' => 'ไม่ใช่วันที่'])->gpPromoActive(), 'วันที่อ่านไม่ออก = ยังฟรี');
    }

    // ------------------------------------------------------------------
    // ตลาดสด
    // ------------------------------------------------------------------

    public function test_fresh_market_gp_rate_resolution(): void
    {
        $listing = new FreshMarketListing;
        $listing->forceFill(['price' => 50, 'commission_rate' => 0]);

        $this->assertSame(3.0, $this->engine(['fresh:platform_fee_percentage' => '3.00', 'fresh:fee_mode' => 'both'])->gpRateForFreshListing($listing));
        $this->assertSame(0.0, $this->engine(['fresh:platform_fee_percentage' => '3.00', 'fresh:fee_mode' => 'subscription'])->gpRateForFreshListing($listing), 'เก็บแต่ค่าสมาชิก = ไม่มี GP ต่อออเดอร์');
        $this->assertSame(4.5, $this->engine(['pricing.fresh_market_gp_rate' => '4.5', 'fresh:platform_fee_percentage' => '3'])->gpRateForFreshListing($listing));
    }

    public function test_fresh_market_quote_has_no_vat_and_no_referral(): void
    {
        $listing = new FreshMarketListing;
        $engine = $this->engine(['fresh:platform_fee_percentage' => '3', 'mlm:mlm_enabled' => '1']);

        $b = $engine->quoteFreshListing($listing, 250.0);

        $this->assertSame(7.5, $b->gp_amount);
        $this->assertSame(0.0, $b->vat_amount);
        $this->assertSame(0.0, $b->referral_pool_amount);
        $this->assertSame(242.5, $b->seller_net);
    }

    // ------------------------------------------------------------------
    // itemSnapshot() — คีย์ต้องตรงคอลัมน์ order_items
    // ------------------------------------------------------------------

    public function test_item_snapshot_matches_order_items_columns(): void
    {
        $product = $this->product(['price' => 250, 'pv_value' => 0], $this->store(['vat_registered' => false], $this->package(10)));
        $engine = $this->engine(['mlm:mlm_enabled' => '0']);

        $snapshot = $engine->itemSnapshot($product, 2, 250.0);

        $this->assertSame(
            ['unit_price', 'quantity', 'subtotal', 'discount_amount', 'total', 'commission_rate', 'commission_amount', 'seller_earning'],
            array_keys($snapshot)
        );
        $this->assertSame(500.0, $snapshot['subtotal']);
        $this->assertSame(500.0, $snapshot['total']);
        $this->assertSame(10.0, $snapshot['commission_rate']);
        $this->assertSame(50.0, $snapshot['commission_amount']);
        $this->assertSame(450.0, $snapshot['seller_earning']);
    }

    public function test_product_quote_uses_store_vat_flag_and_product_pv(): void
    {
        $product = $this->product(['price' => 107, 'pv_value' => 5, 'cost_price' => 50], $this->store(['vat_registered' => 1], $this->package(10)));
        $engine = $this->engine(['mlm:mlm_enabled' => '1', 'mlm:commission_per_pv' => '1']);

        $b = $engine->quoteProduct($product);

        $this->assertSame(10.7, $b->gp_amount);
        $this->assertSame(7.0, $b->vat_amount);
        $this->assertSame(5.0, $b->referral_pool_amount);
        $this->assertSame(84.3, $b->seller_net);
        $this->assertSame(34.3, $b->profit);
    }

    public function test_formula_steps_are_thai(): void
    {
        $steps = PricingEngine::formulaStepsTh();

        $this->assertNotEmpty($steps);
        foreach ($steps as $step) {
            $this->assertMatchesRegularExpression('/[\x{0E00}-\x{0E7F}]/u', $step);
        }
    }
}
