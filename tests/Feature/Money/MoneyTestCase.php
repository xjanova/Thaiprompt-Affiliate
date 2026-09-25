<?php

namespace Tests\Feature\Money;

use App\Models\MlmGlobalSetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PayoutSetting;
use App\Models\PlatformWallet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\User;
use App\Models\VendorStore;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * ฐานของเทสต์ระบบเงิน e-commerce (ต้องใช้ MySQL — รันบน CI)
 *
 * ค่าตั้งเริ่มต้นของเทสต์: GP ค่ากลาง 10%, ไม่มีขั้นต่ำ, ปิด MLM, พักเงินผู้ขาย 3 วัน
 */
abstract class MoneyTestCase extends TestCase
{
    use RefreshDatabase;

    protected ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        Setting::set('pricing.default_gp_rate', '10', 'float', 'pricing');
        Setting::set('pricing.min_gp_rate', '0', 'float', 'pricing');
        Setting::set('pricing.vat_rate', '7', 'float', 'pricing');
        Setting::set('pricing.referral_pool_percent', '0', 'float', 'pricing');
        // migration ตั้งโปรฯ GP ฟรีช่วงเปิดตัวไว้ → ปิดในเทสต์ระบบเงิน (ตรวจตัวเลข GP จริง)
        Setting::set('pricing.gp_free', '0', 'boolean', 'pricing');
        // เทสต์ใช้ออเดอร์ที่เพิ่งสร้าง → ไม่ติด cutoff ของ cron
        Setting::set('money.distribution_backfill_from', now()->subYear()->toDateTimeString(), 'string', 'money');

        MlmGlobalSetting::set('mlm_enabled', false);

        $payout = PayoutSetting::getSellerSetting();
        $payout->update(['holding_days' => 3]);
        PayoutSetting::clearCache();

        $this->category = ProductCategory::create([
            'name' => 'หมวดทดสอบ',
            'slug' => 'test-cat-'.Str::random(6),
            'is_active' => true,
        ]);
    }

    protected function makeUser(string $name = 'ผู้ใช้ทดสอบ', float $walletBalance = 0): User
    {
        $user = User::factory()->create(['name' => $name]);
        Wallet::create([
            'user_id' => $user->id,
            'balance' => $walletBalance,
            'currency' => 'THB',
            'status' => 'active',
        ]);

        return $user->fresh();
    }

    /**
     * ผู้ขาย + ร้าน (ไม่มีแพ็กเกจ → GP = vendor_stores.commission_rate)
     */
    protected function makeSeller(float $storeGpRate = 12.0, bool $vatRegistered = false): User
    {
        $seller = $this->makeUser('ผู้ขาย '.Str::random(4));
        $seller->forceFill(['role' => 'seller'])->save();

        $store = VendorStore::create([
            'user_id' => $seller->id,
            'store_name' => 'ร้าน '.$seller->id,
            'store_slug' => 'store-'.$seller->id.'-'.Str::random(4),
            'status' => 'active',
            'is_active' => true,
            'commission_rate' => $storeGpRate,
        ]);
        $store->forceFill(['vat_registered' => $vatRegistered])->save();

        return $seller;
    }

    /**
     * สินค้าของผู้ขาย — ตั้ง commission_rate = 0 ไว้เพื่อพิสูจน์ว่าค่าที่ผู้ขายตั้งเองไม่ถูกใช้คิดเงิน
     */
    protected function makeProduct(User $seller, float $price, array $overrides = []): Product
    {
        $product = Product::create(array_merge([
            'seller_id' => $seller->id,
            'category_id' => $this->category->id,
            'name' => 'สินค้า '.Str::random(5),
            'price' => $price,
            'stock_quantity' => 100,
            'is_active' => true,
            'is_virtual' => false,
        ], $overrides));
        $product->forceFill(['commission_rate' => 0])->save();

        return $product->fresh();
    }

    /**
     * ออเดอร์ที่ชำระแล้ว (สร้างเป็น paid ตรงๆ — ไม่มี handler created จึงยังไม่แบ่งเงิน)
     *
     * @param  array<int, array{0: Product, 1: int}>  $lines  [[product, qty], ...]
     */
    protected function makePaidOrder(User $buyer, array $lines, float $shippingFee = 0, array $overrides = []): Order
    {
        $subtotal = 0.0;
        foreach ($lines as [$product, $qty]) {
            $subtotal += (float) $product->price * $qty;
        }

        $order = Order::create(array_merge([
            'user_id' => $buyer->id,
            'status' => 'paid',
            'payment_method' => 'promptpay',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'total_amount' => $subtotal + $shippingFee,
        ], $overrides));

        foreach ($lines as [$product, $qty]) {
            $lineTotal = round((float) $product->price * $qty, 2);
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'seller_id' => $product->seller_id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'unit_price' => $product->price,
                'quantity' => $qty,
                'subtotal' => $lineTotal,
                'discount_amount' => 0,
                'total' => $lineTotal,
                'commission_rate' => 0,
                'commission_amount' => 0,
                'seller_earning' => $lineTotal,
                'status' => 'pending',
            ]);
        }

        return $order->fresh();
    }

    protected function walletBalance(User $user): float
    {
        return round((float) Wallet::where('user_id', $user->id)->value('balance'), 2);
    }

    protected function platformBalance(string $slug): float
    {
        return round((float) (PlatformWallet::where('slug', $slug)->value('balance') ?? 0), 2);
    }
}
