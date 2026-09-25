<?php

namespace App\Http\Controllers\FreshMarket;

use App\Http\Controllers\Controller;
use App\Models\RiderJob;
use App\Services\RiderAccountService;
use App\Services\RiderGpsTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * หน้าติดตามไรเดอร์ของลูกค้า (/taladsod/track/{token}) + หน้างานที่กำลังทำของไรเดอร์บนเว็บ
 *
 * ลิงก์ติดตาม (ไม่ต้อง login — token 48 ตัวอักษรเป็นตัวคุมสิทธิ์):
 *   - ใช้ได้ระหว่างงานยังไม่จบ และหลังงานจบ (ส่งสำเร็จ/ยกเลิก/ส่งไม่สำเร็จ) อีก 1 ชั่วโมงเท่านั้น
 *   - ตำแหน่งสดของไรเดอร์โชว์เฉพาะช่วงงานวิ่งอยู่ (accepted → delivering) — ส่งของแล้ว = หยุดทันที
 *   - เบอร์ไรเดอร์โชว์เฉพาะช่วงงานวิ่งอยู่
 *   - รูปไรเดอร์เป็น URL เต็มผ่าน route ของ token นี้ (รูปอยู่บน private disk)
 */
class RiderTrackingController extends Controller
{
    /**
     * อายุลิงก์ติดตามหลังงานจบ (นาที)
     */
    private const LINK_GRACE_MINUTES = 60;

    /**
     * แสดงหน้าติดตามไรเดอร์ (taladsod.track.show)
     */
    public function show(string $token)
    {
        $job = $this->resolveJob($token);

        if (! $job) {
            abort(404, 'ลิงก์ติดตามไม่ถูกต้องหรือหมดอายุแล้ว');
        }

        $gps = new RiderGpsTrackingService;
        $rider = $job->rider;
        $isActive = $job->isTrackable();

        return view('taladsod.tracking', [
            'job' => $job,
            'rider' => $rider,
            'order' => $job->freshMarketOrder,
            'orderNumber' => $this->orderNumber($job),
            'riderPhotoUrl' => ($rider && $rider->profile_image) ? route('taladsod.track.rider-photo', $token) : null,
            'riderPhone' => ($rider && $isActive) ? $rider->phone : null,
            'isActive' => $isActive,
            'location' => $gps->getCurrentLocation($job),
            'customerLocation' => $gps->getCustomerLocation($job),
            'pickupLocation' => $gps->getPickupLocation($job),
            'token' => $token,
            'googleMapsApiKey' => config('services.google_maps.api_key', ''),
            'pollInterval' => ((int) ($gps->settings->gps_update_interval_seconds ?? 30)) * 1000,
            'customerPollInterval' => 180000, // 3 นาที
            // ผู้ซื้อที่ login อยู่ = เจ้าของออเดอร์ → ปุ่ม "แชร์ตำแหน่งของฉันให้ไรเดอร์" (null = ไม่ใช่เจ้าของ ไม่ต้องแสดงปุ่ม)
            'deliveryEndpoints' => $this->buyerDeliveryEndpoints($job),
        ]);
    }

    /**
     * ลิงก์ติดตาม/แชร์ตำแหน่งแบบ session ของผู้ซื้อ (เฉพาะผู้ที่ login เป็นเจ้าของออเดอร์)
     *
     * @return array{rider_location: string, share_location: string, source: string, order_id: int}|null
     */
    private function buyerDeliveryEndpoints(RiderJob $job): ?array
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        $source = $job->deliverableSource() ?? $job->freshMarketOrder;

        [$key, $ownerId] = match (true) {
            $source instanceof \App\Models\FreshMarketOrder => ['fresh-market', (int) $source->buyer_id],
            $source instanceof \App\Models\Order => ['shop', (int) $source->user_id],
            default => [null, 0],
        };

        if (! $key || $ownerId !== (int) $user->id) {
            return null;
        }

        return [
            'source' => $key,
            'order_id' => (int) $source->getKey(),
            'rider_location' => route('taladsod.delivery.rider-location', [$key, $source->getKey()]),
            'share_location' => route('taladsod.delivery.share-location', [$key, $source->getKey()]),
        ];
    }

    /**
     * AJAX: ตำแหน่งปัจจุบันของไรเดอร์ (taladsod.track.location)
     */
    public function getLocation(string $token): JsonResponse
    {
        $job = $this->resolveJob($token);

        if (! $job) {
            return response()->json([
                'success' => false,
                'code' => 'TRACKING_EXPIRED',
                'message' => 'ลิงก์ติดตามไม่ถูกต้องหรือหมดอายุแล้ว',
            ], 404);
        }

        $location = (new RiderGpsTrackingService)->getCurrentLocation($job);

        return response()->json([
            'success' => true,
            'location' => $location,
            'job_status' => (string) $job->status,
            'job_status_text' => $job->status_text,
            'gps_active' => (bool) $job->gps_active,
            'is_active' => $job->isTrackable(),
        ]);
    }

    /**
     * AJAX: เส้นทางที่ไรเดอร์วิ่งมา (taladsod.track.route) — เฉพาะงานที่ยังวิ่งอยู่
     */
    public function getRoute(string $token): JsonResponse
    {
        $job = $this->resolveJob($token);

        if (! $job) {
            return response()->json([
                'success' => false,
                'code' => 'TRACKING_EXPIRED',
                'message' => 'ลิงก์ติดตามไม่ถูกต้องหรือหมดอายุแล้ว',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'route' => (new RiderGpsTrackingService)->getRouteHistory($job),
        ]);
    }

    /**
     * รูปโปรไฟล์ไรเดอร์ของงานนี้ (taladsod.track.rider-photo) — เปิดได้เฉพาะคนที่ถือ token ที่ยังไม่หมดอายุ
     */
    public function riderPhoto(string $token)
    {
        $job = $this->resolveJob($token);

        if (! $job || ! $job->rider || ! $job->rider->profile_image) {
            abort(404);
        }

        return app(RiderAccountService::class)->documentResponse($job->rider, 'profile');
    }

    /**
     * หน้างานที่กำลังทำของไรเดอร์บนเว็บ (taladsod.rider.active-job) — ต้อง login + เป็นเจ้าของงาน
     *
     * ปุ่มทั้งหมดเรียก route session user.rider.* (ไม่สร้าง Sanctum token ใส่หน้าเว็บอีกต่อไป)
     */
    public function riderActiveJob(RiderJob $job)
    {
        $user = Auth::user();
        $rider = $job->rider;

        if (! $rider || (int) $rider->user_id !== (int) $user->id) {
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึงงานนี้');
        }

        // งานจบแล้ว → ไปหน้ารายละเอียดงานแทน (ไม่ติดตาม GPS ต่อ)
        if (! $job->isTrackable()) {
            return redirect()->route('user.rider.jobs.show', $job)
                ->with('info', 'งานนี้'.$job->status_text.'แล้ว');
        }

        $gps = new RiderGpsTrackingService;
        $settings = $gps->settings;

        return view('taladsod.rider-active-job', [
            'job' => $job,
            'rider' => $rider,
            'orderNumber' => $this->orderNumber($job),
            'customerLocation' => $gps->getCustomerLocation($job),
            'pickupLocation' => $gps->getPickupLocation($job),
            'googleMapsApiKey' => config('services.google_maps.api_key', ''),
            'gpsUpdateInterval' => max(10, (int) ($settings->gps_update_interval_seconds ?? 30)) * 1000,
            'gpsLostTimeout' => max(30, (int) ($settings->gps_lost_timeout_seconds ?? 120)) * 1000,
            'maxWarnings' => (int) ($settings->gps_warning_max ?? 3),
            'codAmount' => round((float) $job->cod_amount, 2),
            'endpoints' => [
                'location' => route('user.rider.location'),
                'status' => route('user.rider.jobs.status', $job),
                'deliver' => route('user.rider.jobs.deliver', $job),
                'fail' => route('user.rider.jobs.fail', $job),
                'gps_lost' => route('user.rider.jobs.gps-lost', $job),
                'gps_off' => route('user.rider.jobs.gps-off', $job),
                'job_detail' => route('user.rider.jobs.show', $job),
                'jobs' => route('user.rider.jobs'),
            ],
        ]);
    }

    /**
     * หางานจาก token — ใช้ได้ถ้างานยังไม่จบ หรือจบไปไม่เกิน 1 ชั่วโมง
     */
    private function resolveJob(string $token): ?RiderJob
    {
        if (strlen($token) < 32 || strlen($token) > 64) {
            return null;
        }

        /** @var RiderJob|null $job */
        $job = RiderJob::where('tracking_token', $token)
            ->with(['rider', 'freshMarketOrder'])
            ->first();

        if (! $job || ! hash_equals((string) $job->tracking_token, $token)) {
            return null;
        }

        if (! $job->isTerminal()) {
            return $job;
        }

        $endedAt = $job->completed_at ?? $job->cancelled_at ?? $job->failed_at ?? $job->updated_at;

        return ($endedAt && $endedAt->gt(now()->subMinutes(self::LINK_GRACE_MINUTES))) ? $job : null;
    }

    /**
     * เลขออเดอร์ต้นทาง (null-safe: ออเดอร์ถูกลบ/ไม่มีเลข → null)
     */
    private function orderNumber(RiderJob $job): ?string
    {
        $number = data_get($job->deliverableSource(), 'order_number')
            ?? $job->freshMarketOrder?->order_number;

        return $number !== null && $number !== '' ? (string) $number : null;
    }
}
