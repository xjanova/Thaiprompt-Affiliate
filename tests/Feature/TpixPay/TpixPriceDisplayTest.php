<?php

namespace Tests\Feature\TpixPay;

use App\Models\Product;
use App\Services\Shop\ShopPresenter;
use App\Services\TpixPay\TpixPaySettings;
use App\Services\TpixPay\TpixPriceService;
use App\Services\TpixPay\TpixRpcClient;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * 🪙 ราคาเหรียญ TPIX คู่ราคาบาทบนหน้าสินค้า/ใน API แอป — ไม่ต้องใช้เชนและไม่แตะฐานข้อมูล (รันได้ทุกเครื่อง)
 *
 * ราคาจำลอง: 0.10 USD × 32.5 บาท = 3.25 บาทต่อ TPIX
 *   ฿1,000 → 1000 / 3.25 = 307.6923… → ปัดขึ้น 4 ตำแหน่ง 307.6924 → แสดง (≥100 ใช้ 2 ตำแหน่ง) "307.69"
 *   ฿100   → 30.7692… → 30.7693 → แสดง (<100 ใช้ 4 ตำแหน่ง) "30.7693"
 */
class TpixPriceDisplayTest extends TestCase
{
    /** ราคาจำลองที่ spot() คืน (null = ไม่รู้ราคา) */
    private TpixPriceService $prices;

    private TpixPaySettings $settings;

    protected function setUp(): void
    {
        parent::setUp();

        // ตั้งค่าแอดมินจำลอง (ไม่อ่านตาราง settings)
        $this->settings = new class extends TpixPaySettings
        {
            public bool $show = true;

            public function all(): array
            {
                return [
                    'enabled' => false,
                    'receive_address' => '',
                    'confirmations' => 3,
                    'show_prices' => $this->show,
                    'max_per_payment_thb' => 20000.0,
                    'max_per_user_day_thb' => 50000.0,
                ];
            }
        };
        $this->app->instance(TpixPaySettings::class, $this->settings);

        // ราคาจำลอง (ไม่อ่านพูล DEX / ไม่ยิงอัตราแลกเปลี่ยน)
        $this->prices = new class(new TpixRpcClient) extends TpixPriceService
        {
            /** @var array<string, mixed>|null */
            public ?array $fake = null;

            public function spot(): ?array
            {
                return $this->fake;
            }
        };
        $this->prices->fake = [
            'price_usd' => '0.1',
            'usd_thb' => '32.5',
            'price_thb' => '3.25',
            'reserve_tpix' => '199.9',
            'reserve_usdt' => '19.99',
            'source' => 'dex',
            'at' => time(),
            'fx_at' => time(),
        ];
        $this->app->instance(TpixPriceService::class, $this->prices);
    }

    public function test_component_shows_tpix_price_for_baht_amount(): void
    {
        $html = Blade::render('<x-tpix-price :thb="$thb" />', ['thb' => 1000]);

        $this->assertStringContainsString('≈ 307.69 TPIX', $html);
        $this->assertStringContainsString('tpx-sm', $html);
        // บรรทัดอัตรา (title) บอกราคา 1 TPIX
        $this->assertStringContainsString('1 TPIX = ฿3.25', $html);
    }

    public function test_component_uses_four_decimals_below_one_hundred_tpix(): void
    {
        $html = Blade::render('<x-tpix-price :thb="$thb" />', ['thb' => '100.00']);

        $this->assertStringContainsString('≈ 30.7693 TPIX', $html);
    }

    public function test_large_component_shows_rate_line(): void
    {
        $html = Blade::render('<x-tpix-price :thb="$thb" size="lg" />', ['thb' => 1000]);

        $this->assertStringContainsString('tpx-lg', $html);
        $this->assertStringContainsString('≈ 307.69 TPIX', $html);
        $this->assertStringContainsString('ราคาจริงจาก TPIX DEX', $html);
        $this->assertStringNotContainsString('ราคาเหรียญคงที่จากร้าน', $html);
    }

    public function test_component_renders_nothing_when_price_unknown(): void
    {
        $this->prices->fake = null;

        $this->assertSame('', trim(Blade::render('<x-tpix-price :thb="$thb" />', ['thb' => 1000])));
        $this->assertSame(['price_tpix' => null, 'tpix' => null], $this->prices->apiFields(1000));
    }

    public function test_component_renders_nothing_when_admin_hides_prices(): void
    {
        $this->settings->show = false;

        $this->assertSame('', trim(Blade::render('<x-tpix-price :thb="$thb" size="lg" />', ['thb' => 1000])));
    }

    public function test_component_renders_nothing_for_missing_or_zero_price(): void
    {
        $this->assertSame('', trim(Blade::render('<x-tpix-price :thb="$thb" />', ['thb' => null])));
        $this->assertSame('', trim(Blade::render('<x-tpix-price :thb="$thb" />', ['thb' => 0])));
        $this->assertSame('', trim(Blade::render('<x-tpix-price :thb="$thb" />', ['thb' => 'ไม่ใช่ตัวเลข'])));
    }

    public function test_fixed_price_is_honored_for_official_shop_product(): void
    {
        // ราคาคงที่ 400 TPIX สูงกว่ามูลค่าตลาดของ ฿1,000 (307.69 TPIX) → ใช้ราคาคงที่
        $product = $this->product(official: true, priceTpix: '400');

        $this->assertSame('400.00000000', TpixPriceService::fixedPriceFor($product));

        $html = Blade::render('<x-tpix-price :product="$product" size="lg" />', ['product' => $product]);
        $this->assertStringContainsString('400.00 TPIX', $html);
        $this->assertStringNotContainsString('≈ 400.00', $html);
        $this->assertStringContainsString('ราคาเหรียญคงที่จากร้าน', $html);

        $api = $this->prices->apiFieldsForProduct($product);
        $this->assertSame('400', $api['price_tpix']);
        $this->assertTrue($api['tpix']['fixed']);
    }

    public function test_fixed_price_below_market_value_shows_the_market_price(): void
    {
        // ราคาคงที่ 12.5 TPIX ต่ำกว่ามูลค่าตลาดของ ฿1,000 → แสดง/คิดเงินตามตลาด (กันซื้อด้วยเหรียญถูกแล้วยกเลิกรับคืนเป็นบาท)
        $product = $this->product(official: true, priceTpix: '12.5');

        $html = Blade::render('<x-tpix-price :product="$product" size="lg" />', ['product' => $product]);
        $this->assertStringContainsString('≈ 307.69 TPIX', $html);
        $this->assertStringNotContainsString('ราคาเหรียญคงที่จากร้าน', $html);

        $api = $this->prices->apiFieldsForProduct($product);
        $this->assertSame('307.6924', $api['price_tpix']);
        $this->assertFalse($api['tpix']['fixed']);
    }

    public function test_fixed_price_is_ignored_for_third_party_seller_product(): void
    {
        $product = $this->product(official: false, priceTpix: '1');

        $this->assertNull(TpixPriceService::fixedPriceFor($product));

        $html = Blade::render('<x-tpix-price :product="$product" />', ['product' => $product]);
        $this->assertStringContainsString('≈ 307.69 TPIX', $html);
        $this->assertStringNotContainsString('1.00 TPIX', $html);

        $api = $this->prices->apiFieldsForProduct($product);
        $this->assertSame('307.6924', $api['price_tpix']);
        $this->assertFalse($api['tpix']['fixed']);
    }

    public function test_api_fields_shape(): void
    {
        $api = $this->prices->apiFields(1000);

        $this->assertSame(['price_tpix', 'tpix'], array_keys($api));
        $this->assertSame('307.6924', $api['price_tpix']);
        $this->assertSame(['amount', 'raw', 'fixed', 'rate_thb', 'rate_display', 'updated_at'], array_keys($api['tpix']));
        $this->assertSame('307.69', $api['tpix']['amount']);
        $this->assertSame('307.6924', $api['tpix']['raw']);
        $this->assertFalse($api['tpix']['fixed']);
        $this->assertSame('3.25', $api['tpix']['rate_thb']);
        $this->assertSame('฿3.25', $api['tpix']['rate_display']);
        $this->assertNotFalse(strtotime($api['tpix']['updated_at']));

        // ยอดเต็มร้อยต้องไม่ถูกตัดศูนย์หน้าจุด ("325" ไม่ใช่ "325." หรือ "3")
        $this->assertSame('325', $this->prices->apiFields(1056.25)['price_tpix']);
    }

    public function test_shop_presenter_adds_tpix_fields_and_skips_affiliate_products(): void
    {
        $official = $this->product(official: true, priceTpix: '400');
        $this->assertSame('400', ShopPresenter::productTpix($official, 1000)['price_tpix']);

        $thirdParty = $this->product(official: false, priceTpix: null);
        $this->assertSame('307.6924', ShopPresenter::productTpix($thirdParty, 1000)['price_tpix']);

        $affiliate = $this->product(official: false, priceTpix: null);
        $affiliate->is_affiliate = true;
        $this->assertSame(['price_tpix' => null, 'tpix' => null], ShopPresenter::productTpix($affiliate, 1000));
    }

    public function test_lazada_products_never_show_a_tpix_price(): void
    {
        // เจ้าของสั่ง: บน Thaiprompt สินค้า Lazada ไม่ต้องแสดงราคา TPIX (ทั้งลิงก์ affiliate และที่ดึงเข้ามาขายในร้าน)
        $imported = $this->product(official: true, priceTpix: null);
        $imported->forceFill(['sku' => 'LZD-5329571666']);

        $bySource = $this->product(official: false, priceTpix: null);
        $bySource->forceFill(['attributes' => ['source' => ['platform' => 'lazada', 'item_id' => '1']]]);

        $byPlatform = $this->product(official: false, priceTpix: null);
        $byPlatform->forceFill(['external_platform' => 'lazada']);

        foreach ([$imported, $bySource, $byPlatform] as $product) {
            $this->assertTrue(TpixPriceService::isExternalProduct($product));
            $this->assertSame('', trim(Blade::render('<x-tpix-price :product="$product" size="lg" />', ['product' => $product])));
            $this->assertSame(['price_tpix' => null, 'tpix' => null], ShopPresenter::productTpix($product, 1000));
            $this->assertSame(['price_tpix' => null, 'tpix' => null], $this->prices->apiFieldsForProduct($product));
        }

        // สินค้าปกติยังแสดง
        $normal = $this->product(official: false, priceTpix: null);
        $this->assertFalse(TpixPriceService::isExternalProduct($normal));
        $this->assertStringContainsString('307.69 TPIX', Blade::render('<x-tpix-price :product="$product" />', ['product' => $normal]));
    }

    /**
     * สินค้าจำลองที่ไม่แตะฐานข้อมูล (isOfficialShopProduct ปกติค้นผู้ขายทางการจากตาราง users)
     */
    private function product(bool $official, ?string $priceTpix, float $price = 1000): Product
    {
        $product = new class extends Product
        {
            public bool $fakeOfficial = false;

            public function isOfficialShopProduct(): bool
            {
                return $this->fakeOfficial;
            }
        };
        $product->fakeOfficial = $official;
        $product->forceFill([
            'id' => 1,
            'seller_id' => $official ? 77 : 88,
            'name' => 'สินค้าทดสอบ',
            'price' => $price,
            'price_tpix' => $priceTpix,
            'is_affiliate' => false,
        ]);

        return $product;
    }
}
