<?php

namespace Tests\Feature\Rider;

use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FreshMarketService;
use App\Services\RiderDispatchService;
use App\Services\RiderJobService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * เปลี่ยนไรเดอร์กลางงาน → ตำแหน่งสดของลูกค้าต้องไม่ติดไปถึงไรเดอร์คนใหม่ (ลูกค้าต้องยินยอมใหม่) — ต้องใช้ MySQL
 *
 * - แอดมินย้ายงานให้ไรเดอร์คนอื่น (adminReassign)
 * - ไรเดอร์ถูกระงับ งานกลับเข้าคิว แล้วไรเดอร์คนใหม่รับ (handleRiderSuspended)
 */
#[Group('rider')]
class CustomerShareRiderChangeTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;

    protected User $sellerUser;

    protected FreshMarketListing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Storage::fake('public');
        Cache::flush();
        FreshMarketSetting::clearCache();
        FreshMarketSetting::create([
            'brand_name' => 'ตลาดสดทดสอบ',
            'ai_provider' => 'groq',
            'ai_model' => 'llama-3.3-70b-versatile',
            'platform_fee_percentage' => 0,
            'fee_mode' => 'percentage',
            'escrow_enabled' => true,
            'cod_enabled' => true,
            'rider_enabled' => true,
            'cashback_enabled' => false,
            'mlm_commission_enabled' => false,
        ]);
        FreshMarketSetting::clearCache();

        Setting::set('pricing.fresh_market_gp_rate', '0', 'float', 'pricing');
        Setting::set('fresh_market.auto_approve_sellers', '1', 'boolean', 'fresh_market');
        Setting::set('rider.require_deposit', '0', 'boolean', 'rider');
        Setting::set('rider.dispatch_mode', 'broadcast', 'string', 'rider');

        $this->buyer = User::factory()->create(['name' => 'ผู้ซื้อ']);
        $this->sellerUser = User::factory()->create(['name' => 'แม่ค้า']);
        Wallet::create(['user_id' => $this->buyer->id, 'balance' => 1000, 'currency' => 'THB', 'status' => 'active']);
        Wallet::create(['user_id' => $this->sellerUser->id, 'balance' => 0, 'currency' => 'THB', 'status' => 'active']);

        $seller = FreshMarketSeller::create([
            'user_id' => $this->sellerUser->id,
            'shop_name' => 'แผงผักป้าแดง',
            'phone' => '0812345678',
            'address' => 'ตลาดบางรัก',
            'latitude' => 13.7291,
            'longitude' => 100.5210,
            'is_active' => true,
            'is_suspended' => false,
            'is_verified' => true,
            'subscription_type' => 'free',
        ]);

        $this->listing = FreshMarketListing::create([
            'seller_id' => $seller->id,
            'title' => 'ผักบุ้งจีน',
            'price' => 40,
            'unit' => 'กำ',
            'quantity_available' => 20,
            'latitude' => 13.7291,
            'longitude' => 100.5210,
            'status' => 'active',
            'is_available' => true,
            'created_via' => 'web',
        ]);
    }

    public function test_admin_reassign_stops_customer_sharing_for_new_rider(): void
    {
        [$order, $job] = $this->jobWithRider($riderA = $this->makeRider());
        $this->buyerShares($order);

        $riderB = $this->makeRider();
        $admin = User::factory()->create();
        app(RiderJobService::class)->adminReassign($job->fresh(), $riderB, $admin);

        $fresh = $job->fresh();
        $this->assertSame((int) $riderB->id, (int) $fresh->rider_id);
        $this->assertFalse((bool) $fresh->customer_share_location);
        $this->assertNull($fresh->customer_last_latitude);

        Sanctum::actingAs($riderB->user);
        $this->getJson("/api/v1/rider/jobs/{$job->id}")->assertOk()
            ->assertJsonPath('data.job.customer_live_location', null);

        // ลูกค้าเปิดแชร์ใหม่ (ยินยอมกับไรเดอร์คนใหม่) → ไรเดอร์คนใหม่เห็น
        $this->buyerShares($order);
        Sanctum::actingAs($riderB->user);
        $live = $this->getJson("/api/v1/rider/jobs/{$job->id}")->assertOk()->json('data.job.customer_live_location');
        $this->assertNotNull($live);
        $this->assertNotSame((int) $riderA->id, (int) $riderB->id);
    }

    public function test_suspended_rider_release_stops_customer_sharing(): void
    {
        [$order, $job] = $this->jobWithRider($riderA = $this->makeRider());
        $this->buyerShares($order);

        $result = app(RiderJobService::class)->handleRiderSuspended($riderA->fresh());

        $this->assertSame('pending', $result?->status);
        $fresh = $job->fresh();
        $this->assertNull($fresh->rider_id);
        $this->assertFalse((bool) $fresh->customer_share_location);
        $this->assertNull($fresh->customer_last_latitude);
        $this->assertNull($fresh->customer_last_longitude);

        // ไรเดอร์คนถัดไปรับงาน → ไม่เห็นตำแหน่งลูกค้าจนกว่าลูกค้าจะเปิดแชร์ใหม่
        $riderB = $this->makeRider();
        app(RiderJobService::class)->accept($fresh, $riderB);

        Sanctum::actingAs($riderB->user);
        $this->getJson("/api/v1/rider/jobs/{$job->id}")->assertOk()
            ->assertJsonPath('data.job.customer_live_location', null);
    }

    /**
     * @return array{0: FreshMarketOrder, 1: RiderJob}
     */
    private function jobWithRider(Rider $rider): array
    {
        $service = app(FreshMarketService::class);
        $order = $service->createOrder($this->buyer, $this->listing->fresh(), [
            'quantity' => 1,
            'delivery_type' => 'rider',
            'payment_method' => 'wallet',
            'buyer_latitude' => 13.74,
            'buyer_longitude' => 100.53,
            'delivery_address' => '88 ถนนสีลม',
        ]);
        $service->applyAction($order, 'accept', 'seller', $this->sellerUser);

        $job = app(RiderDispatchService::class)->createJobForSource($order->fresh(), 'fresh_market')->fresh();
        app(RiderJobService::class)->accept($job, $rider);

        return [$order->fresh(), $job->fresh()];
    }

    private function buyerShares(FreshMarketOrder $order): void
    {
        Sanctum::actingAs($this->buyer);
        $this->postJson("/api/v1/orders/fresh-market/{$order->id}/share-location", [
            'share' => true,
            'latitude' => 13.7405,
            'longitude' => 100.5305,
        ])->assertOk();
    }

    private function makeRider(): Rider
    {
        $user = User::factory()->create();
        $rider = new Rider;
        $rider->forceFill([
            'user_id' => $user->id,
            'full_name' => 'ไรเดอร์ '.$user->id,
            'phone' => '08'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT),
            'status' => 'approved',
            'availability' => 'online',
            'rider_type' => 'delivery',
            'vehicle_type' => 'motorcycle',
            'gps_permission_granted' => true,
            'last_latitude' => 13.73,
            'last_longitude' => 100.522,
            'last_location_update' => now(),
            'share_location_consent_at' => now(),
            'approved_at' => now(),
        ])->save();
        app(WalletService::class)->getOrCreateWallet($user);

        return $rider->fresh();
    }
}
