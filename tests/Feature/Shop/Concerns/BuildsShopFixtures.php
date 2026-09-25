<?php

namespace Tests\Feature\Shop\Concerns;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\ShippingAddress;
use App\Models\User;
use App\Models\VendorStore;
use App\Services\WalletService;
use Illuminate\Support\Str;

/**
 * ตัวช่วยสร้างข้อมูลทดสอบของระบบร้านค้า (ร้าน / สินค้า / ผู้ซื้อ / ที่อยู่ / ออเดอร์)
 */
trait BuildsShopFixtures
{
    /** จุดรับของของร้าน (สีลม) */
    protected const STORE_LAT = 13.7291;

    protected const STORE_LNG = 100.5210;

    /**
     * ตั้งอัตรา GP มาตรฐานของแพลตฟอร์ม (ปิดขั้นต่ำ) ให้ผลคำนวณคาดเดาได้
     */
    protected function setGpRate(float $rate): void
    {
        Setting::set('pricing.default_gp_rate', (string) $rate, 'float', 'pricing');
        Setting::set('pricing.min_gp_rate', '0', 'float', 'pricing');
        // migration ตั้งโปรฯ GP ฟรีช่วงเปิดตัวไว้ → ปิดในเทสต์ที่ตรวจตัวเลข GP
        Setting::set('pricing.gp_free', '0', 'boolean', 'pricing');
    }

    protected function makeCategory(): ProductCategory
    {
        return ProductCategory::create([
            'name' => 'หมวดทดสอบ '.Str::random(4),
            'slug' => 'test-cat-'.Str::lower(Str::random(8)),
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    /**
     * ผู้ขาย + ร้าน (เปิดใช้งาน, มีพิกัดจุดรับของ)
     *
     * @return array{0: User, 1: VendorStore}
     */
    protected function makeSellerWithStore(array $storeOverrides = []): array
    {
        $seller = User::factory()->create();
        $seller->role = 'seller';
        $seller->save();

        $store = VendorStore::create(array_merge([
            'user_id' => $seller->id,
            'store_name' => 'ร้านทดสอบ '.$seller->id,
            'store_slug' => 'test-store-'.$seller->id.'-'.Str::lower(Str::random(4)),
            'store_phone' => '0812345678',
            'store_address' => '1 ถนนสีลม',
            'status' => 'active',
            'is_active' => true,
            'subscription_status' => 'active',
            'pickup_latitude' => self::STORE_LAT,
            'pickup_longitude' => self::STORE_LNG,
            'rider_delivery_enabled' => false,
        ], $storeOverrides));

        return [$seller->fresh(), $store->fresh()];
    }

    protected function makeProduct(User $seller, ?VendorStore $store, array $overrides = []): Product
    {
        $category = $this->makeCategory();

        return Product::create(array_merge([
            'seller_id' => $seller->id,
            'store_id' => $store?->id,
            'category_id' => $category->id,
            'name' => 'สินค้าทดสอบ '.Str::random(5),
            'sku' => 'TST-'.Str::upper(Str::random(8)),
            'price' => 100,
            'stock_quantity' => 10,
            'track_inventory' => true,
            'stock_status' => 'in_stock',
            'shipping_method' => 'free',
            'is_active' => true,
            'is_hidden' => false,
            'is_blocked' => false,
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    protected function makeBuyer(float $walletBalance = 0): User
    {
        $buyer = User::factory()->create();

        $wallet = app(WalletService::class)->getOrCreateWallet($buyer);
        $wallet->forceFill(['balance' => $walletBalance, 'status' => 'active'])->save();

        return $buyer->fresh();
    }

    protected function makeAddress(User $user, bool $withLocation = true, array $overrides = []): ShippingAddress
    {
        return ShippingAddress::create(array_merge([
            'user_id' => $user->id,
            'recipient_name' => 'ผู้รับ ทดสอบ',
            'phone_number' => '0899999999',
            'address_line_1' => '99 ถนนพระราม 4',
            'sub_district' => 'สุริยวงศ์',
            'district' => 'บางรัก',
            'province' => 'กรุงเทพมหานคร',
            'postal_code' => '10500',
            'country' => 'Thailand',
            'latitude' => $withLocation ? self::STORE_LAT + 0.01 : null,
            'longitude' => $withLocation ? self::STORE_LNG + 0.01 : null,
            'is_default' => true,
        ], $overrides));
    }

    /**
     * ออเดอร์ตรงๆ (ไม่ผ่าน checkout) สำหรับทดสอบฝั่งร้าน/ยกเลิก
     *
     * @param  array<int, array{product: Product, qty: int}>  $lines
     */
    protected function makeOrder(User $buyer, array $lines, array $overrides = []): Order
    {
        $subtotal = 0;
        foreach ($lines as $line) {
            $subtotal += (float) $line['product']->price * $line['qty'];
        }

        $order = Order::create(array_merge([
            'user_id' => $buyer->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'promptpay',
            'delivery_method' => 'parcel',
            'subtotal' => $subtotal,
            'shipping_fee' => 0,
            'discount_amount' => 0,
            'total_amount' => $subtotal,
        ], $overrides));

        foreach ($lines as $line) {
            /** @var Product $product */
            $product = $line['product'];
            $total = (float) $product->price * $line['qty'];

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'seller_id' => $product->seller_id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'unit_price' => $product->price,
                'quantity' => $line['qty'],
                'subtotal' => $total,
                'total' => $total,
                'commission_rate' => 0,
                'commission_amount' => 0,
                'seller_earning' => $total,
                'status' => 'pending',
            ]);
        }

        return $order->fresh(['items']);
    }

    protected function walletBalance(User $user): float
    {
        return round((float) $user->wallet()->value('balance'), 2);
    }
}
