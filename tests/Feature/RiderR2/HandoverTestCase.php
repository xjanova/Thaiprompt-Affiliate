<?php

namespace Tests\Feature\RiderR2;

use App\Models\DeliveryHandover;
use App\Models\Order;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Rider\HandoverService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Money\MoneyTestCase;

/**
 * ฐานของเทสต์เลน money (ไรเดอร์รอบ 2) — ต้องใช้ MySQL
 *
 * ออเดอร์มาตรฐาน: สินค้า 100 (GP ร้าน 10%) + ค่าส่ง 40 = 140 จ่ายผ่านวอลเลตแล้ว เงินพัก (settlement_deferred)
 * งานไรเดอร์: ค่าส่ง 40 = ไรเดอร์ 32 + แพลตฟอร์ม 8 · จุดส่ง (13.7300, 100.5300)
 */
abstract class HandoverTestCase extends MoneyTestCase
{
    protected const DROP_LAT = 13.7300;

    protected const DROP_LNG = 100.5300;

    /** ห่างจุดส่ง ~7 ม. */
    protected const NEAR = ['latitude' => 13.73005, 'longitude' => 100.53005];

    /** ห่างจุดส่ง ~1.1 กม. */
    protected const FAR = ['latitude' => 13.7400, 'longitude' => 100.5300];

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Queue::fake();
        Storage::fake('local');
        Storage::fake('public');

        Setting::set('rider.require_deposit', '0', 'boolean', 'rider');
        Setting::set('rider.handover_enabled', '1', 'boolean', 'rider');
        Setting::set('rider.handover_geofence_m', '150', 'integer', 'rider');
        Setting::set('rider.handover_qr_ttl_seconds', '60', 'integer', 'rider');
        Setting::set('rider.handover_wait_seconds', '180', 'integer', 'rider');
        Setting::set('rider.handover_auto_release_hours', '24', 'integer', 'rider');
    }

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    // =====================================================
    // fixtures
    // =====================================================

    /**
     * ออเดอร์ร้านค้าเงินพักส่งด้วยไรเดอร์ (จ่ายผ่านวอลเลตแล้ว สถานะจัดส่งแล้ว)
     *
     * @return array{0: Order, 1: User, 2: User} [ออเดอร์, ผู้ซื้อ, ผู้ขาย]
     */
    protected function makeDeferredShopOrder(float $bonus = 0, float $subsidy = 0, float $shipping = 40, float $price = 100): array
    {
        $seller = $this->makeSeller(10.0);
        $buyer = $this->makeUser('สมหญิง ใจดี');
        $product = $this->makeProduct($seller, $price);

        $order = $this->makePaidOrder($buyer, [[$product, 1]], $shipping, [
            'status' => 'paid',
            'payment_method' => 'wallet',
            'delivery_method' => 'rider',
            'settlement_deferred' => true,
            'rider_bonus_amount' => $bonus,
            'delivery_subsidy_amount' => $subsidy,
        ]);

        return [$order, $buyer, $seller];
    }

    protected function makeRider(?User $user = null): Rider
    {
        $user ??= $this->makeUser('สมชาย ขยันส่ง');

        $rider = new Rider;
        $rider->forceFill([
            'user_id' => $user->id,
            'full_name' => 'สมชาย ขยันส่ง',
            'phone' => '08'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT),
            'status' => 'approved',
            'availability' => 'busy',
            'rider_type' => 'delivery',
            'vehicle_type' => 'motorcycle',
            'vehicle_plate' => '1กข 1234',
            'vehicle_brand' => 'Honda',
            'gps_permission_granted' => true,
            'share_location_consent_at' => now(),
            'last_latitude' => self::NEAR['latitude'],
            'last_longitude' => self::NEAR['longitude'],
            'last_location_update' => now(),
            'approved_at' => now(),
        ])->save();

        return $rider->fresh();
    }

    /**
     * งานไรเดอร์ที่ต้องสแกนส่งมอบ (ค่าเริ่มต้น: ไรเดอร์ถือของ กำลังจัดส่ง)
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function makeHandoverJob(Order $order, Rider $rider, string $status = 'delivering', array $overrides = []): RiderJob
    {
        $job = new RiderJob;
        $job->forceFill(array_merge([
            'rider_id' => $rider->id,
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
            'base_fee' => 30,
            'distance_fee' => 10,
            'total_fee' => 40,
            'rider_earnings' => 32,
            'platform_fee' => 8,
            'cod_amount' => 0,
            'status' => $status,
            'handover_required' => true,
            'accepted_at' => now()->subMinutes(30),
            'picked_up_at' => now()->subMinutes(20),
            'tracking_token' => \Illuminate\Support\Str::random(48),
            'tracking_expires_at' => now()->addDay(),
        ], $overrides))->save();

        // ออเดอร์ตามสถานะงาน (ไรเดอร์รับของแล้ว = จัดส่งแล้ว)
        Order::whereKey($order->id)->update(['status' => 'shipped', 'shipped_at' => now()->subMinutes(20)]);

        return $job->fresh();
    }

    // =====================================================
    // ตัวช่วยเรียก API
    // =====================================================

    protected function buyerGet(User $buyer, Order|int $order, string $source = 'shop')
    {
        Sanctum::actingAs($buyer);

        return $this->getJson('/api/v1/orders/'.$source.'/'.(is_int($order) ? $order : $order->id).'/handover');
    }

    protected function buyerScan(User $buyer, Order|int $order, string $token, string $source = 'shop')
    {
        Sanctum::actingAs($buyer);

        return $this->postJson('/api/v1/orders/'.$source.'/'.(is_int($order) ? $order : $order->id).'/handover/scan', ['token' => $token]);
    }

    protected function riderGet(Rider $rider, RiderJob $job)
    {
        Sanctum::actingAs($rider->user);

        return $this->getJson('/api/v1/rider/jobs/'.$job->id.'/handover');
    }

    /**
     * @param  array<string, mixed>  $payload  token|code + latitude/longitude
     */
    protected function riderScan(Rider $rider, RiderJob $job, array $payload)
    {
        Sanctum::actingAs($rider->user);

        return $this->postJson('/api/v1/rider/jobs/'.$job->id.'/handover/scan', $payload);
    }

    /**
     * @param  array<string, mixed>|null  $location
     */
    protected function riderPhoto(Rider $rider, RiderJob $job, string $kind, ?array $location = self::NEAR)
    {
        Sanctum::actingAs($rider->user);

        return $this->post('/api/v1/rider/jobs/'.$job->id.'/handover/'.$kind, array_merge(
            ['photo' => UploadedFile::fake()->image('door.jpg', 640, 480)],
            $location ?? []
        ), ['Accept' => 'application/json']);
    }

    /**
     * ทำให้สแกนครบทั้งสองฝ่าย (ไรเดอร์สแกน QR ผู้ซื้อ → ผู้ซื้อสแกน QR ไรเดอร์)
     */
    protected function completeByScans(User $buyer, Order $order, Rider $rider, RiderJob $job): void
    {
        $buyerToken = $this->buyerGet($buyer, $order)->assertOk()->json('data.handover.qr_token');
        $this->riderScan($rider, $job, ['token' => $buyerToken] + self::NEAR)->assertOk();

        $riderToken = $this->riderGet($rider, $job)->assertOk()->json('data.handover.qr_token');
        $this->buyerScan($buyer, $order, $riderToken)->assertOk()->assertJsonPath('data.handover.status', 'completed');
    }

    protected function handoverOf(RiderJob $job): ?DeliveryHandover
    {
        return DeliveryHandover::where('rider_job_id', $job->id)->first();
    }

    protected function handovers(): HandoverService
    {
        return app(HandoverService::class);
    }

    protected function riderEarningCredits(RiderJob $job): float
    {
        return round((float) \App\Models\WalletTransaction::where('reference_type', 'rider_job')
            ->where('reference_id', $job->id)
            ->where('type', 'deposit')
            ->sum('amount'), 2);
    }

    protected function walletOf(User $user): float
    {
        return round((float) Wallet::where('user_id', $user->id)->value('balance'), 2);
    }
}
