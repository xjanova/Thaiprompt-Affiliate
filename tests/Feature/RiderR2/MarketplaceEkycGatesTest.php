<?php

namespace Tests\Feature\RiderR2;

use App\Models\Order;
use App\Models\SoftwareQuotation;
use App\Services\Ekyc\EkycGate;
use App\Services\RiderDispatchService;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\RiderR2\Concerns\BuildsTimeoutFixtures;

/**
 * 🪪 ด่าน eKYC ที่เหลือ (2026-10-13 — ต้องใช้ MySQL)
 *
 * - การกระจายงานไม่เสนองานให้ไรเดอร์ที่ยังไม่ผ่าน eKYC (เมื่อเปิด ekyc.enforce) — ไรเดอร์เหล่านี้กดรับงานไม่ได้อยู่แล้ว
 * - แปลงใบเสนอราคาเป็นคำสั่งซื้อ ต้องผ่านด่านผู้ซื้อแบบเดียวกับ ShopCheckoutService::checkout
 */
#[Group('rider-r2')]
class MarketplaceEkycGatesTest extends HandoverTestCase
{
    use BuildsTimeoutFixtures;

    public function test_dispatch_skips_riders_without_ekyc_when_enforced(): void
    {
        [$order] = $this->makeDeferredShopOrder();
        $job = $this->makeOpenShopJob($order);

        $verified = $this->onlineRider();
        $unverified = $this->onlineRider();
        $unverified->user->forceFill(['kyc_status' => null, 'kyc_verified_at' => null])->save();

        $dispatch = app(RiderDispatchService::class);

        // ปิดบังคับ (สวิตช์ฉุกเฉิน) → เสนอได้ทั้งคู่
        config(['ekyc.enforce' => false]);
        $ids = $dispatch->findEligibleRiders($job->fresh(), 5)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->assertContains((int) $verified->id, $ids);
        $this->assertContains((int) $unverified->id, $ids);

        config(['ekyc.enforce' => true]);
        $ids = $dispatch->findEligibleRiders($job->fresh(), 5)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->assertContains((int) $verified->id, $ids);
        $this->assertNotContains((int) $unverified->id, $ids);

        // กระจายงานจริงแจ้งเฉพาะคนที่ผ่าน eKYC
        $dispatch->broadcast($job->fresh(), 5);
        $candidates = array_map('intval', $job->fresh()->candidate_riders ?? []);
        $this->assertContains((int) $verified->id, $candidates);
        $this->assertNotContains((int) $unverified->id, $candidates);
        $this->assertSame(0, $this->notificationCount($unverified->user_id, 'rider_job_offer'));
    }

    public function test_quotation_convert_to_order_requires_buyer_ekyc(): void
    {
        config(['ekyc.enforce' => true]);

        $buyer = $this->makeUser('ผู้ซื้อซอฟต์แวร์');
        $quotation = SoftwareQuotation::create([
            'quotation_number' => 'QT-'.Str::upper(Str::random(8)),
            'user_id' => $buyer->id,
            'customer_name' => 'ผู้ซื้อซอฟต์แวร์',
            'customer_email' => 'buyer-'.Str::random(6).'@example.com',
            'subtotal' => 1000,
            'tax_amount' => 70,
            'total_amount' => 1070,
            'status' => 'accepted',
        ]);

        $this->actingAs($buyer)
            ->post(route('quotations.convert', $quotation))
            ->assertRedirect()
            ->assertSessionHas('error', EkycGate::buyerWebMessage())
            ->assertSessionHas(EkycGate::FLASH_KEY, true);

        $this->assertSame(0, Order::where('user_id', $buyer->id)->count());
        $this->assertNull($quotation->fresh()->order_id);
    }
}
