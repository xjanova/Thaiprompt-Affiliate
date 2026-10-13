<?php

namespace Tests\Feature\RiderR2\Concerns;

use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\Setting;
use App\Models\User;
use App\Services\FreshMarketService;
use Illuminate\Support\Str;

/**
 * ⏱️ ฉากทดสอบกติกาตัดเวลา (2026-10-13) — ใช้กับ HandoverTestCase (MySQL)
 *
 * ตลาดสด: ร้านที่สีลม (13.7291, 100.5210) · ผู้ซื้อปักหมุดที่ DROP (13.7300, 100.5300) ~1 กม.
 * สินค้า 100 + ค่าส่งไรเดอร์ (ผู้ซื้อจ่าย) จ่ายผ่าน Wallet ทั้งก้อน
 */
trait BuildsTimeoutFixtures
{
    protected function freshMarketWorld(): void
    {
        FreshMarketSetting::clearCache();
        FreshMarketSetting::create([
            'brand_name' => 'ตลาดสดทดสอบ', 'ai_provider' => 'groq', 'ai_model' => 'llama-3.3-70b-versatile',
            'platform_fee_percentage' => 10, 'fee_mode' => 'percentage', 'escrow_enabled' => true, 'cod_enabled' => true,
            'rider_enabled' => true, 'cashback_enabled' => false, 'mlm_commission_enabled' => false,
        ]);
        FreshMarketSetting::clearCache();

        Setting::set('pricing.fresh_market_gp_rate', '10', 'float', 'pricing');
        Setting::set('rider.max_distance_km', '15', 'float', 'rider');
        Setting::set('rider.dispatch_mode', 'broadcast', 'string', 'rider');
        Setting::set('fresh_market.rider_dispatch_on_accept', '0', 'boolean', 'fresh_market');
    }

    /**
     * ร้านตลาดสด + สินค้า 100 บาท
     *
     * @return array{0: FreshMarketSeller, 1: User, 2: FreshMarketListing}
     */
    protected function makeFreshShop(float $sellerBalance = 0): array
    {
        $sellerUser = $this->makeUser('ร้านผักตัดเวลา', $sellerBalance);
        $seller = FreshMarketSeller::create([
            'user_id' => $sellerUser->id, 'shop_name' => 'ร้านผักตัดเวลา', 'phone' => '0812345678', 'address' => 'ตลาดทดสอบ',
            'latitude' => 13.7291, 'longitude' => 100.5210, 'is_active' => true, 'is_suspended' => false, 'is_verified' => true,
            'subscription_type' => 'free',
        ]);
        $listing = FreshMarketListing::create([
            'seller_id' => $seller->id, 'title' => 'ผักบุ้ง', 'price' => 100, 'unit' => 'กำ', 'quantity_available' => 10,
            'status' => 'active', 'is_available' => true, 'created_via' => 'web',
        ]);

        return [$seller, $sellerUser, $listing];
    }

    /**
     * ออเดอร์ตลาดสดส่งด้วยไรเดอร์ จ่ายผ่าน Wallet (สินค้า + ค่าส่ง)
     */
    protected function placeFreshRiderOrder(User $buyer, FreshMarketListing $listing): FreshMarketOrder
    {
        $order = (new FreshMarketService)->createOrder($buyer, $listing->fresh(), [
            'quantity' => 1,
            'delivery_type' => 'rider',
            'payment_method' => 'wallet',
            'buyer_latitude' => self::DROP_LAT,
            'buyer_longitude' => self::DROP_LNG,
            'delivery_address' => '99 ถนนทดสอบ บางรัก',
        ]);

        $order = FreshMarketOrder::findOrFail($order->id);
        $this->assertSame('paid', $order->payment_status);
        $this->assertGreaterThan(0, (float) $order->delivery_fee, 'ผู้ซื้อจ่ายค่าส่งเอง (ใช้ตรวจว่าคืนค่าส่งด้วย)');

        return $order;
    }

    protected function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * ไรเดอร์เปิดรับงานอยู่ใกล้ร้าน (ผ่าน eKYC แล้ว — ใช้ makeRider ของ HandoverTestCase)
     */
    protected function onlineRider(?User $user = null): Rider
    {
        $rider = $this->makeRider($user);
        $rider->forceFill(['availability' => 'online', 'last_location_update' => now()])->save();

        return $rider->fresh();
    }

    /**
     * งานไรเดอร์ของออเดอร์ร้านค้าที่ยังรอคนรับ (สร้างตรงๆ — ร้านในฉากทดสอบไม่มีพิกัดจุดรับของ)
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function makeOpenShopJob(Order $order, array $overrides = []): RiderJob
    {
        $job = new RiderJob;
        $job->forceFill(array_merge([
            'job_type' => 'shop_delivery',
            'source_type' => $order->getMorphClass(),
            'source_id' => $order->id,
            'customer_id' => $order->user_id,
            'title' => 'ส่งสินค้า '.$order->order_number,
            'pickup_address' => 'ร้านทดสอบ สีลม',
            'pickup_latitude' => 13.7291,
            'pickup_longitude' => 100.5210,
            'delivery_address' => 'บ้านลูกค้า บางรัก',
            'delivery_latitude' => self::DROP_LAT,
            'delivery_longitude' => self::DROP_LNG,
            'distance_km' => 1.2,
            'base_fee' => 30,
            'distance_fee' => 10,
            'total_fee' => 40,
            'rider_earnings' => 32,
            'platform_fee' => 8,
            'cod_amount' => 0,
            'status' => 'pending',
            'dispatch_type' => 'broadcast',
            'dispatch_radius_km' => 5,
            'tracking_token' => Str::random(48),
            'tracking_expires_at' => now()->addDay(),
        ], $overrides))->save();

        return $job->fresh();
    }

    protected function notificationCount(User|int $user, string $type, ?string $titleLike = null): int
    {
        return Notification::where('user_id', $user instanceof User ? $user->id : $user)
            ->where('type', $type)
            ->when($titleLike !== null, fn ($q) => $q->where('title', 'like', $titleLike))
            ->count();
    }
}
