<?php

namespace Tests\Feature\RiderR2\Concerns;

use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\Order;
use App\Models\Rider;
use App\Models\RiderHeart;
use App\Models\RiderJob;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Support\Str;

/**
 * ตัวช่วยสร้างข้อมูลทดสอบเลน social ของไรเดอร์รอบ 2 (ไรเดอร์ / ออเดอร์ / งานที่ส่งสำเร็จ / หัวใจ)
 */
trait BuildsRiderSocialFixtures
{
    /** จุดอ้างอิง (สีลม) */
    protected const BASE_LAT = 13.7291;

    protected const BASE_LNG = 100.5210;

    /**
     * ไรเดอร์ที่พร้อมรับงาน (approved + online + พิกัดสด + ยินยอมแชร์ตำแหน่ง)
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function makeRider(array $overrides = [], ?User $user = null): Rider
    {
        $user ??= User::factory()->create();

        $rider = new Rider;
        $rider->forceFill(array_merge([
            'user_id' => $user->id,
            'full_name' => 'สมชาย ใจดี',
            'phone' => '08'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT),
            'status' => 'approved',
            'availability' => 'online',
            'rider_type' => 'delivery',
            'vehicle_type' => 'motorcycle',
            'vehicle_plate' => '1กข 1234',
            'vehicle_brand' => 'Honda',
            'vehicle_color' => 'แดง',
            'gps_permission_granted' => true,
            'last_latitude' => self::BASE_LAT + 0.001,
            'last_longitude' => self::BASE_LNG + 0.001,
            'last_location_update' => now(),
            'share_location_consent_at' => now(),
            'approved_at' => now(),
            'show_on_nearby' => true,
        ], $overrides))->save();

        app(WalletService::class)->getOrCreateWallet($user);

        return $rider->fresh();
    }

    /**
     * ออเดอร์ร้านค้า (ตรงๆ ไม่ผ่าน checkout)
     */
    protected function makeShopOrder(User $buyer, array $overrides = []): Order
    {
        return Order::create(array_merge([
            'user_id' => $buyer->id,
            'status' => 'delivered',
            'payment_status' => 'paid',
            'payment_method' => 'wallet',
            'delivery_method' => 'rider',
            'subtotal' => 100,
            'shipping_fee' => 30,
            'discount_amount' => 0,
            'total_amount' => 130,
        ], $overrides));
    }

    /**
     * ออเดอร์ตลาดสด (ตรงๆ ไม่ผ่าน checkout)
     */
    protected function makeFreshMarketOrder(User $buyer): FreshMarketOrder
    {
        $sellerUser = User::factory()->create();
        $seller = FreshMarketSeller::create([
            'user_id' => $sellerUser->id,
            'shop_name' => 'ร้านทดสอบ '.Str::random(4),
            'phone' => '0812345678',
            'address' => 'ตลาดทดสอบ',
            'latitude' => self::BASE_LAT,
            'longitude' => self::BASE_LNG,
            'is_active' => true,
            'is_suspended' => false,
            'is_verified' => true,
            'subscription_type' => 'free',
        ]);

        $order = new FreshMarketOrder;
        $order->forceFill([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'quantity' => 1,
            'unit_price' => 50,
            'total_amount' => 50,
            'platform_fee' => 5,
            'seller_earning' => 45,
            'delivery_type' => 'rider',
            'delivery_fee' => 25,
            'payment_method' => 'wallet',
            'payment_status' => 'paid',
            'order_status' => 'completed',
        ])->save();

        return $order->fresh();
    }

    /**
     * งานไรเดอร์ของออเดอร์ (สถานะที่กำหนด) — สร้างตรงๆ ไม่ผ่าน dispatch
     */
    protected function makeJobFor($source, Rider $rider, User $buyer, string $status = 'completed'): RiderJob
    {
        $job = new RiderJob;
        $job->forceFill([
            'job_type' => 'shop_delivery',
            'source_type' => $source->getMorphClass(),
            'source_id' => $source->getKey(),
            'rider_id' => $rider->id,
            'customer_id' => $buyer->id,
            'title' => 'ส่งสินค้า',
            'pickup_address' => 'ร้านทดสอบ',
            'pickup_latitude' => self::BASE_LAT,
            'pickup_longitude' => self::BASE_LNG,
            'delivery_address' => '99 ถนนทดสอบ เขตบางรัก กรุงเทพมหานคร',
            'delivery_latitude' => self::BASE_LAT + 0.01,
            'delivery_longitude' => self::BASE_LNG + 0.01,
            'total_fee' => 30,
            'rider_earnings' => 25,
            'platform_fee' => 5,
            'status' => $status,
            'accepted_at' => now()->subMinutes(30),
            'completed_at' => $status === 'completed' ? now()->subMinutes(5) : null,
            'dispatch_type' => 'broadcast',
        ])->save();

        return $job->fresh();
    }

    /**
     * ให้หัวใจไรเดอร์ $count ดวงจากผู้ซื้อคนนี้ (สร้างงานที่ส่งสำเร็จ $count งาน)
     */
    protected function giveHearts(Rider $rider, User $buyer, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $job = $this->makeJobFor($this->makeShopOrder($buyer), $rider, $buyer);
            RiderHeart::create(['rider_id' => $rider->id, 'user_id' => $buyer->id, 'rider_job_id' => $job->id]);
        }

        Rider::whereKey($rider->id)->increment('hearts_count', $count);
    }
}
