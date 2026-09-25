<?php

namespace App\Services;

use App\Exceptions\DeliveryTrackingException;
use App\Models\FreshMarketOrder;
use App\Models\Order;
use App\Models\RiderJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * DeliveryTrackingService - ผู้ซื้อติดตามไรเดอร์ + แชร์ตำแหน่งตัวเองให้ไรเดอร์ (ยินยอมทั้งสองฝ่าย)
 *
 * ใช้ร่วมกันระหว่าง API แอป (/api/v1/orders/{source}/{id}/...) และหน้าเว็บ (session)
 * รองรับออเดอร์ 2 แบบ: source = shop (ร้านค้า App\Models\Order) | fresh-market (ตลาดสด FreshMarketOrder)
 *
 * กติกาความเป็นส่วนตัว:
 *   - เห็นได้เฉพาะออเดอร์ของตัวเอง (คนอื่นได้ 404 เหมือนไม่มีออเดอร์นี้)
 *   - ตำแหน่งไรเดอร์: เฉพาะตอนงานวิ่งอยู่ (รับงาน → กำลังส่ง) และไรเดอร์ยินยอมแชร์ตำแหน่งแล้ว
 *   - ส่งถึง/ยกเลิก/ส่งไม่สำเร็จ = หยุดทันที (ไม่มีพิกัดไรเดอร์ในคำตอบอีก)
 *   - ตำแหน่งลูกค้า: ลูกค้าเปิดแชร์เอง ไรเดอร์เห็นเฉพาะตอนยังสด (≤ 2 นาที) · งานจบ/ไรเดอร์คืนงาน = ล้างทิ้งอัตโนมัติ
 */
class DeliveryTrackingService
{
    /** แหล่งออเดอร์ที่รองรับ (ค่าใน URL) */
    public const SOURCES = ['shop', 'fresh-market'];

    /** แอป/เว็บควรถามตำแหน่งไรเดอร์ใหม่ทุกกี่วินาที */
    public const POLL_SECONDS = 15;

    /** ลูกค้าที่แชร์ตำแหน่งควรส่งตำแหน่งใหม่ทุกกี่วินาที (ต้องถี่กว่าอายุความสด 120 วินาที) */
    public const CUSTOMER_SEND_SECONDS = 30;

    /**
     * ออเดอร์ของผู้ซื้อคนนี้ (ไม่ใช่ของตัวเอง = ไม่พบ)
     *
     * @throws DeliveryTrackingException ORDER_NOT_FOUND
     */
    public function findBuyerOrder(User $user, string $source, int $id): Model
    {
        $order = match ($source) {
            'shop' => Order::where('id', $id)->where('user_id', $user->id)->first(),
            'fresh-market' => FreshMarketOrder::where('id', $id)->where('buyer_id', $user->id)->first(),
            default => null,
        };

        if (! $order) {
            throw DeliveryTrackingException::make('ORDER_NOT_FOUND', 'ไม่พบออเดอร์', 404);
        }

        return $order;
    }

    /**
     * งานไรเดอร์ของออเดอร์: งานที่ยังไม่จบล่าสุด → (ตลาดสด) งานที่ผูกไว้ในออเดอร์ → งานล่าสุด
     */
    public function jobFor(Model $order): ?RiderJob
    {
        $job = RiderJob::forSource($order)->nonTerminal()->latest('id')->first();

        if ($job) {
            return $job;
        }

        if ($order instanceof FreshMarketOrder && $order->rider_job_id) {
            $linked = RiderJob::find($order->rider_job_id);

            if ($linked) {
                return $linked;
            }
        }

        return RiderJob::forSource($order)->latest('id')->first();
    }

    /**
     * ตำแหน่งไรเดอร์ของออเดอร์ (ผู้ซื้อ) — พร้อมสถานะการแชร์ตำแหน่งของผู้ซื้อเอง
     *
     * @return array<string, mixed>
     */
    public function riderLocation(Model $order, string $source): array
    {
        $job = $this->jobFor($order);

        $base = [
            'source' => $source,
            'order_id' => (int) $order->getKey(),
            'order_number' => (string) ($order->order_number ?? ''),
            'poll_interval_seconds' => self::POLL_SECONDS,
            'customer_send_interval_seconds' => self::CUSTOMER_SEND_SECONDS,
        ];

        if (! $job) {
            return $base + [
                'has_rider_job' => false,
                'job' => null,
                'rider' => null,
                'rider_location' => null,
                'location_available' => false,
                'reason' => 'no_rider_job',
                'reason_text' => 'ออเดอร์นี้ยังไม่มีงานไรเดอร์',
                'pickup' => null,
                'dropoff' => null,
                'customer_sharing' => $this->sharingPayload(null),
                'can_share_location' => false,
            ];
        }

        $job->loadMissing('rider');
        $active = $job->isTrackable();
        $gps = new RiderGpsTrackingService;
        $riderLocation = null;
        $reason = null;

        if ($job->status === 'pending') {
            $reason = 'waiting_for_rider';
        } elseif (! $active) {
            $reason = 'job_not_active';
        } elseif (! $gps->canShowRiderLocation($job)) {
            $reason = 'consent_missing';
        } elseif ($job->rider->last_latitude === null || $job->rider->last_longitude === null) {
            $reason = 'no_gps_yet';
        } else {
            $location = $gps->getCurrentLocation($job);
            $riderLocation = [
                'latitude' => (float) $location['latitude'],
                'longitude' => (float) $location['longitude'],
                'updated_at' => $location['updated_at'] ?? null,
                'heading' => $location['heading'] ?? null,
                'speed' => $location['speed'] ?? null,
                'gps_active' => (bool) ($location['gps_active'] ?? false),
                'is_stale' => ! ($location['available'] ?? false),
            ];

            if ($riderLocation['is_stale']) {
                $reason = 'gps_stale';
            }
        }

        $rider = $active ? $job->rider : null;

        return $base + [
            'has_rider_job' => true,
            'job' => [
                'id' => (int) $job->id,
                'job_number' => $job->job_number,
                'status' => (string) $job->status,
                'status_text' => $job->status_text,
                'is_active' => $active,
                'accepted_at' => $job->accepted_at?->toIso8601String(),
                'picked_up_at' => $job->picked_up_at?->toIso8601String(),
                'completed_at' => $job->completed_at?->toIso8601String(),
            ],
            'rider' => $rider ? [
                'name' => $rider->full_name,
                'vehicle_type' => $rider->vehicle_type,
                'vehicle_type_text' => $rider->vehicle_type_text,
                'vehicle_plate' => $rider->vehicle_plate,
                'phone' => $rider->phone,
                'rating' => round((float) $rider->rating, 2),
            ] : null,
            'rider_location' => $riderLocation,
            'location_available' => $riderLocation !== null && ! $riderLocation['is_stale'],
            'reason' => $reason,
            'reason_text' => $this->reasonText($reason),
            'pickup' => $active && $job->pickup_latitude !== null ? [
                'latitude' => (float) $job->pickup_latitude,
                'longitude' => (float) $job->pickup_longitude,
                'address' => $job->pickup_address,
            ] : null,
            'dropoff' => $active && $job->delivery_latitude !== null ? [
                'latitude' => (float) $job->delivery_latitude,
                'longitude' => (float) $job->delivery_longitude,
            ] : null,
            'customer_sharing' => $this->sharingPayload($job),
            'can_share_location' => $active,
        ];
    }

    /**
     * ผู้ซื้อเปิด/ปิดแชร์ตำแหน่งตัวเองให้ไรเดอร์ (+ ส่งตำแหน่งล่าสุด)
     *
     * - share=false: หยุดแชร์ + ลบพิกัดที่เก็บไว้ (ทำได้เสมอ)
     * - share=true: ได้เฉพาะตอนงานวิ่งอยู่ · ส่ง latitude/longitude มาด้วย = อัปเดตตำแหน่ง
     *   (อัปเดตแบบมีเงื่อนไขสถานะงาน → งานเพิ่งจบพร้อมกันก็ไม่มีพิกัดค้างในงานที่จบแล้ว)
     *
     * @return array{sharing: bool, location_saved: bool, customer_sharing: array, job_status: ?string}
     *
     * @throws DeliveryTrackingException JOB_NOT_ACTIVE|INVALID_LOCATION
     */
    public function setSharing(Model $order, bool $share, mixed $lat = null, mixed $lng = null): array
    {
        $job = $this->jobFor($order);

        if (! $share) {
            if ($job) {
                RiderJob::whereKey($job->id)->update([
                    'customer_share_location' => false,
                    'customer_last_latitude' => null,
                    'customer_last_longitude' => null,
                    'customer_location_at' => null,
                    'updated_at' => now(),
                ]);
                $job->refresh();
            }

            return [
                'sharing' => false,
                'location_saved' => false,
                'customer_sharing' => $this->sharingPayload($job),
                'job_status' => $job?->status,
            ];
        }

        if (! $job || ! $job->isTrackable()) {
            throw DeliveryTrackingException::make(
                'JOB_NOT_ACTIVE',
                $job && $job->isTerminal()
                    ? 'การจัดส่งจบแล้ว ระบบหยุดแชร์ตำแหน่งให้อัตโนมัติ'
                    : 'ยังไม่มีไรเดอร์กำลังมาส่ง แชร์ตำแหน่งได้เมื่อไรเดอร์รับงานแล้ว',
                409
            );
        }

        $hasPoint = $lat !== null && $lat !== '' && $lng !== null && $lng !== '';

        if ($hasPoint && (! DeliveryFeeCalculator::isValidCoordinate($lat, $lng) || ((float) $lat == 0.0 && (float) $lng == 0.0))) {
            throw DeliveryTrackingException::make('INVALID_LOCATION', 'พิกัดตำแหน่งไม่ถูกต้อง กรุณาเปิด GPS แล้วลองใหม่', 422);
        }

        $updates = ['customer_share_location' => true, 'updated_at' => now()];

        if ($hasPoint) {
            $updates += [
                'customer_last_latitude' => round((float) $lat, 7),
                'customer_last_longitude' => round((float) $lng, 7),
                'customer_location_at' => now(),
            ];
        }

        $updated = RiderJob::whereKey($job->id)
            ->whereIn('status', RiderJob::ACTIVE_STATUSES)
            ->update($updates);

        if ($updated === 0) {
            throw DeliveryTrackingException::make('JOB_NOT_ACTIVE', 'การจัดส่งจบแล้ว ระบบหยุดแชร์ตำแหน่งให้อัตโนมัติ', 409);
        }

        $job->refresh();

        Log::info('DeliveryTracking: customer sharing location', ['job_id' => $job->id, 'with_point' => $hasPoint]);

        return [
            'sharing' => true,
            'location_saved' => $hasPoint,
            'customer_sharing' => $this->sharingPayload($job),
            'job_status' => (string) $job->status,
        ];
    }

    /**
     * สถานะการแชร์ตำแหน่งของผู้ซื้อ
     *
     * @return array{enabled: bool, last_shared_at: ?string, is_fresh: bool, fresh_seconds: int}
     */
    public function sharingPayload(?RiderJob $job): array
    {
        $enabled = $job !== null && (bool) $job->customer_share_location && $job->isTrackable();

        return [
            'enabled' => $enabled,
            'last_shared_at' => $enabled ? $job->customer_location_at?->toIso8601String() : null,
            'is_fresh' => $enabled && $job->customerLiveLocation() !== null,
            'fresh_seconds' => RiderJob::CUSTOMER_LOCATION_FRESH_SECONDS,
        ];
    }

    protected function reasonText(?string $reason): ?string
    {
        return match ($reason) {
            'waiting_for_rider' => 'กำลังหาไรเดอร์ให้ออเดอร์นี้',
            'job_not_active' => 'การจัดส่งจบแล้ว จึงไม่แสดงตำแหน่งไรเดอร์',
            'consent_missing' => 'ไรเดอร์ยังไม่ได้ยินยอมแชร์ตำแหน่ง',
            'no_gps_yet' => 'ยังไม่ได้รับตำแหน่งจากไรเดอร์',
            'gps_stale' => 'สัญญาณ GPS ของไรเดอร์ขาดช่วง แสดงตำแหน่งล่าสุด',
            default => null,
        };
    }
}
