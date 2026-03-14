<?php

namespace App\Http\Controllers\FreshMarket;

use App\Http\Controllers\Controller;
use App\Models\RiderJob;
use App\Services\RiderGpsTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * RiderTrackingController - หน้าติดตามไรเดอร์สำหรับลูกค้า
 *
 * ไม่ต้อง login — ใช้ tracking token เป็น access control
 * URL: /taladsod/track/{token}
 */
class RiderTrackingController extends Controller
{
    protected RiderGpsTrackingService $gpsService;

    public function __construct()
    {
        $this->gpsService = new RiderGpsTrackingService();
    }

    /**
     * แสดงหน้าติดตามไรเดอร์ (Blade view)
     */
    public function show(string $token)
    {
        $job = $this->gpsService->validateToken($token);

        if (!$job) {
            abort(404, 'ลิงก์ติดตามไม่ถูกต้องหรือหมดอายุแล้ว');
        }

        $location = $this->gpsService->getCurrentLocation($job);
        $customerLocation = $this->gpsService->getCustomerLocation($job);
        $pickupLocation = $this->gpsService->getPickupLocation($job);

        return view('taladsod.tracking', [
            'job' => $job,
            'rider' => $job->rider,
            'order' => $job->freshMarketOrder,
            'location' => $location,
            'customerLocation' => $customerLocation,
            'pickupLocation' => $pickupLocation,
            'token' => $token,
            'googleMapsApiKey' => config('services.google_maps.api_key', ''),
            'pollInterval' => ($this->gpsService->settings->gps_update_interval_seconds ?? 30) * 1000,
            'customerPollInterval' => 180000, // 3 นาที
        ]);
    }

    /**
     * AJAX: ดึงตำแหน่งปัจจุบันของไรเดอร์ (ลูกค้าเรียกทุก 3 นาที)
     */
    public function getLocation(string $token): JsonResponse
    {
        $job = $this->gpsService->validateToken($token);

        if (!$job) {
            return response()->json(['error' => 'token_invalid'], 404);
        }

        $location = $this->gpsService->getCurrentLocation($job);

        return response()->json([
            'success' => true,
            'location' => $location,
            'job_status' => $job->status,
            'gps_active' => (bool) $job->gps_active,
        ]);
    }

    /**
     * แสดงหน้างานปัจจุบันของไรเดอร์ (ต้อง login)
     *
     * ตรวจสอบว่าไรเดอร์เป็นเจ้าของงานจริงก่อนแสดงผล
     */
    public function riderActiveJob(RiderJob $job)
    {
        $user = Auth::user();

        // ตรวจสอบว่าไรเดอร์เป็นเจ้าของงานนี้
        $rider = $job->rider;
        if (!$rider || $rider->user_id !== $user->id) {
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึงงานนี้');
        }

        // ดึงพิกัดลูกค้าและจุดรับสินค้า
        $customerLocation = $this->gpsService->getCustomerLocation($job);
        $pickupLocation = $this->gpsService->getPickupLocation($job);

        // ดึงค่า settings สำหรับ GPS
        $settings = $this->gpsService->settings;
        $gpsUpdateInterval = ($settings->gps_update_interval_seconds ?? 30) * 1000;
        $gpsLostTimeout = ($settings->gps_lost_timeout_seconds ?? 120) * 1000;
        $maxWarnings = $settings->gps_warning_max ?? 3;

        return view('taladsod.rider-active-job', [
            'job' => $job,
            'rider' => $rider,
            'customerLocation' => $customerLocation,
            'pickupLocation' => $pickupLocation,
            'googleMapsApiKey' => config('services.google_maps.api_key', ''),
            'gpsUpdateInterval' => $gpsUpdateInterval,
            'gpsLostTimeout' => $gpsLostTimeout,
            'maxWarnings' => $maxWarnings,
        ]);
    }

    /**
     * AJAX: ดึงเส้นทางการเดินทาง
     */
    public function getRoute(string $token): JsonResponse
    {
        $job = $this->gpsService->validateToken($token);

        if (!$job) {
            return response()->json(['error' => 'token_invalid'], 404);
        }

        $route = $this->gpsService->getRouteHistory($job);

        return response()->json([
            'success' => true,
            'route' => $route,
        ]);
    }
}
