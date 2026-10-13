<?php

namespace Tests\Feature\RiderR2;

use App\Events\Marketplace\RiderAbandonedJob;
use App\Models\Notification;
use App\Models\Rider;
use App\Services\RiderGpsTrackingService;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\RiderR2\Concerns\BuildsTimeoutFixtures;

/**
 * ⏱️ กติกาตัดเวลา ข้อ 3 (2026-10-13): ไรเดอร์รับงานแล้ว GPS หาย 10 นาที (rider:gps-watch — ต้องใช้ MySQL)
 *
 * - ก่อนรับของ → คืนงานเข้าคิว นับเป็นการยกเลิกงาน แจ้งไรเดอร์/ผู้ซื้อ/ร้าน · 9 นาทียังไม่คืน
 * - หลังรับของ → แจ้งแอดมิน + ผู้ซื้อครั้งเดียวต่อเหตุการณ์ (ไม่ขึ้นกับ gps_active) · GPS กลับมาแล้วหายใหม่ = แจ้งใหม่
 */
#[Group('rider-r2')]
class RiderGpsLossTimeoutTest extends HandoverTestCase
{
    use BuildsTimeoutFixtures;

    public function test_gps_lost_before_pickup_releases_the_job_at_10_minutes(): void
    {
        Event::fake([RiderAbandonedJob::class]);

        [$order, $buyer, $seller] = $this->makeDeferredShopOrder();
        $rider = $this->onlineRider();
        $job = $this->makeOpenShopJob($order, [
            'rider_id' => $rider->id,
            'status' => 'accepted',
            'accepted_at' => now(),
            'gps_active' => true,
        ]);
        $rider->forceFill(['availability' => 'busy'])->save();

        // 9 นาที: หยุดชั่วคราว (เตือนไรเดอร์) แต่ยังไม่คืนงาน
        $this->travel(9)->minutes();
        $this->artisan('rider:gps-watch')->assertSuccessful();

        $job->refresh();
        $this->assertSame('accepted', $job->status);
        $this->assertSame((int) $rider->id, (int) $job->rider_id);
        $this->assertFalse($job->gps_active);

        // 10 นาที: ไม่ขึ้นกับ gps_active (เป็น false ไปแล้ว) → คืนงานเข้าคิว
        $this->travel(1)->minutes();
        $this->artisan('rider:gps-watch')->assertSuccessful();

        $job->refresh();
        $this->assertSame('pending', $job->status);
        $this->assertNull($job->rider_id);
        $this->assertContains((int) $rider->id, $job->releasedRiderIds(), 'ไม่เสนองานนี้ให้คนเดิมอีก');
        $this->assertEqualsWithDelta(now()->getTimestamp(), $job->searchStartedAt()->getTimestamp(), 2, 'เริ่มรอบหาไรเดอร์ใหม่');

        $rider->refresh();
        $this->assertSame(1, (int) $rider->cancelled_jobs, 'นับเป็นการยกเลิกงาน');
        $this->assertSame('offline', $rider->availability, 'GPS ยังไม่กลับ → ปิดรับงานไว้ก่อน');

        $this->assertSame(1, Notification::where('user_id', $rider->user_id)->where('type', 'rider_job_update')->where('data->event', 'gps_released')->count());
        $this->assertSame(1, Notification::where('user_id', $buyer->id)->where('type', 'delivery_update')->where('data->event', 'gps_released')->count());
        $this->assertSame(1, Notification::where('user_id', $seller->id)->where('type', 'delivery_update')->where('data->event', 'gps_released')->count());

        Event::assertDispatched(RiderAbandonedJob::class, fn (RiderAbandonedJob $e) => $e->riderJobId === (int) $job->id
            && $e->riderId === (int) $rider->id && $e->reason === RiderAbandonedJob::REASON_GPS_LOST);

        // รอบถัดไปไม่ทำซ้ำ
        $this->artisan('rider:gps-watch')->assertSuccessful();
        $this->assertSame(1, (int) $rider->fresh()->cancelled_jobs);
        Event::assertDispatchedTimes(RiderAbandonedJob::class, 1);
    }

    public function test_recent_location_keeps_the_job_even_after_10_minutes(): void
    {
        [$order] = $this->makeDeferredShopOrder();
        $rider = $this->onlineRider();
        $job = $this->makeOpenShopJob($order, ['rider_id' => $rider->id, 'status' => 'picking_up', 'accepted_at' => now()]);

        // ไรเดอร์ส่งตำแหน่งเข้ามาเรื่อยๆ (นาทีที่ 8) → นาทีที่ 15 ยังไม่ครบ 10 นาทีนับจากตำแหน่งล่าสุด
        $this->travel(8)->minutes();
        (new RiderGpsTrackingService)->recordRiderLocation($rider->fresh(), ['latitude' => 13.7295, 'longitude' => 100.5215]);

        $this->travel(7)->minutes();
        $this->artisan('rider:gps-watch')->assertSuccessful();

        $this->assertSame('picking_up', $job->fresh()->status);
        $this->assertSame(0, (int) $rider->fresh()->cancelled_jobs);
    }

    public function test_gps_lost_after_pickup_alerts_admins_and_buyer_once_per_incident(): void
    {
        $admin = $this->makeAdmin();
        [$order, $buyer] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider, 'delivering');
        Rider::whereKey($rider->id)->update(['last_location_update' => now()->subMinutes(11)]);

        $this->artisan('rider:gps-watch')->assertSuccessful();
        $this->artisan('rider:gps-watch')->assertSuccessful();

        $job->refresh();
        $this->assertSame('delivering', $job->status, 'หลังรับของไม่ทำอะไรกับงานเอง');
        $this->assertSame((int) $rider->id, (int) $job->rider_id);
        $this->assertNotNull($job->gps_lost_alerted_at);
        $this->assertSame(1, $this->notificationCount($admin, 'rider_admin', 'GPS ไรเดอร์หายระหว่างนำส่ง'));
        $this->assertSame(1, Notification::where('user_id', $buyer->id)->where('type', 'delivery_update')->where('data->event', 'gps_lost')->count());
        $this->assertSame(0, (int) $rider->fresh()->cancelled_jobs);

        // GPS กลับมา → จบเหตุการณ์
        (new RiderGpsTrackingService)->recordRiderLocation($rider->fresh(), self::NEAR);
        $this->assertNull($job->fresh()->gps_lost_alerted_at);
        $this->assertTrue($job->fresh()->gps_active);

        // หายอีกรอบเกิน 10 นาที = เหตุการณ์ใหม่ → แจ้งอีกครั้ง
        $this->travel(11)->minutes();
        $this->artisan('rider:gps-watch')->assertSuccessful();
        $this->artisan('rider:gps-watch')->assertSuccessful();

        $this->assertSame(2, $this->notificationCount($admin, 'rider_admin', 'GPS ไรเดอร์หายระหว่างนำส่ง'));
        $this->assertSame(2, Notification::where('user_id', $buyer->id)->where('type', 'delivery_update')->where('data->event', 'gps_lost')->count());
        $this->assertSame('delivering', $job->fresh()->status);
    }
}
