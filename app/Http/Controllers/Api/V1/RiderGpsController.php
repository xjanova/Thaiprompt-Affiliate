<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Services\RiderGpsTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * RiderGpsController - API สำหรับไรเดอร์จัดการ GPS
 *
 * ใช้ auth:sanctum — ไรเดอร์ต้อง login
 */
class RiderGpsController extends Controller
{
    protected RiderGpsTrackingService $gpsService;

    public function __construct()
    {
        $this->gpsService = new RiderGpsTrackingService();
    }

    /**
     * อัพเดทตำแหน่ง GPS ของไรเดอร์
     */
    public function updateLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'heading' => 'nullable|numeric',
            'altitude' => 'nullable|numeric',
            'battery_level' => 'nullable|integer|between:0,100',
            'is_charging' => 'nullable|boolean',
            'job_id' => 'required|integer|exists:rider_jobs,id',
        ]);

        $rider = Rider::where('user_id', $request->user()->id)->first();
        if (!$rider) {
            return response()->json(['error' => 'ไม่พบข้อมูลไรเดอร์'], 404);
        }

        $job = RiderJob::where('id', $validated['job_id'])
            ->where('rider_id', $rider->id)
            ->whereIn('status', ['accepted', 'picking_up', 'picked_up', 'delivering'])
            ->first();

        if (!$job) {
            return response()->json(['error' => 'ไม่พบงานที่กำลังดำเนินอยู่'], 404);
        }

        $this->gpsService->updateLocation($rider, $job, $validated);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทตำแหน่งสำเร็จ',
            'gps_active' => true,
        ]);
    }

    /**
     * แจ้งว่า GPS หาย (ฝั่ง client ตรวจจับ)
     */
    public function reportGpsLost(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'job_id' => 'required|integer|exists:rider_jobs,id',
        ]);

        $rider = Rider::where('user_id', $request->user()->id)->first();
        if (!$rider) {
            return response()->json(['error' => 'ไม่พบข้อมูลไรเดอร์'], 404);
        }

        // กรองเฉพาะงานที่กำลังดำเนินอยู่เท่านั้น (ป้องกันแก้ไขงานที่เสร็จแล้ว)
        $job = RiderJob::where('id', $validated['job_id'])
            ->where('rider_id', $rider->id)
            ->whereIn('status', ['accepted', 'picking_up', 'picked_up', 'delivering'])
            ->first();

        if (!$job) {
            return response()->json(['error' => 'ไม่พบงานที่กำลังดำเนินอยู่'], 404);
        }

        $result = $this->gpsService->handleGpsLost($job);

        return response()->json([
            'success' => true,
            ...$result,
        ]);
    }

    /**
     * ไรเดอร์ยืนยันปิด GPS จริงๆ
     */
    public function confirmGpsOff(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'job_id' => 'required|integer|exists:rider_jobs,id',
        ]);

        $rider = Rider::where('user_id', $request->user()->id)->first();
        if (!$rider) {
            return response()->json(['error' => 'ไม่พบข้อมูลไรเดอร์'], 404);
        }

        // กรองเฉพาะงานที่กำลังดำเนินอยู่เท่านั้น (ป้องกันแก้ไขงานที่เสร็จแล้ว)
        $job = RiderJob::where('id', $validated['job_id'])
            ->where('rider_id', $rider->id)
            ->whereIn('status', ['accepted', 'picking_up', 'picked_up', 'delivering'])
            ->first();

        if (!$job) {
            return response()->json(['error' => 'ไม่พบงานที่กำลังดำเนินอยู่'], 404);
        }

        $result = $this->gpsService->confirmGpsOff($job);

        return response()->json([
            'success' => true,
            ...$result,
        ]);
    }

    /**
     * GPS กลับมาแล้ว
     */
    public function resumeGps(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'job_id' => 'required|integer|exists:rider_jobs,id',
        ]);

        $rider = Rider::where('user_id', $request->user()->id)->first();
        if (!$rider) {
            return response()->json(['error' => 'ไม่พบข้อมูลไรเดอร์'], 404);
        }

        $job = RiderJob::where('id', $validated['job_id'])
            ->where('rider_id', $rider->id)
            ->first();

        if (!$job) {
            return response()->json(['error' => 'ไม่พบงาน'], 404);
        }

        $this->gpsService->handleGpsResume($job);

        return response()->json([
            'success' => true,
            'message' => 'GPS กลับมาแล้ว ดำเนินการจัดส่งต่อได้',
            'gps_active' => true,
        ]);
    }

    /**
     * ดึงตำแหน่งลูกค้า (สำหรับไรเดอร์นำทาง)
     */
    public function getCustomerLocation(Request $request, int $jobId): JsonResponse
    {
        $rider = Rider::where('user_id', $request->user()->id)->first();
        if (!$rider) {
            return response()->json(['error' => 'ไม่พบข้อมูลไรเดอร์'], 404);
        }

        // กรองเฉพาะงานที่กำลังดำเนินอยู่ (ป้องกันการเปิดเผย PII ของงานที่เสร็จแล้ว)
        $job = RiderJob::where('id', $jobId)
            ->where('rider_id', $rider->id)
            ->whereIn('status', ['accepted', 'picking_up', 'picked_up', 'delivering'])
            ->first();

        if (!$job) {
            return response()->json(['error' => 'ไม่พบงานที่กำลังดำเนินอยู่'], 404);
        }

        return response()->json([
            'success' => true,
            'customer' => $this->gpsService->getCustomerLocation($job),
            'pickup' => $this->gpsService->getPickupLocation($job),
        ]);
    }

    /**
     * ดึง tracking info สำหรับไรเดอร์ (tracking URL, ข้อมูลลูกค้า)
     */
    public function getTrackingInfo(Request $request, int $jobId): JsonResponse
    {
        $rider = Rider::where('user_id', $request->user()->id)->first();
        if (!$rider) {
            return response()->json(['error' => 'ไม่พบข้อมูลไรเดอร์'], 404);
        }

        $job = RiderJob::where('id', $jobId)
            ->where('rider_id', $rider->id)
            ->first();

        if (!$job) {
            return response()->json(['error' => 'ไม่พบงาน'], 404);
        }

        return response()->json([
            'success' => true,
            'tracking_url' => $job->tracking_url,
            'job' => [
                'id' => $job->id,
                'job_number' => $job->job_number,
                'status' => $job->status,
                'gps_active' => (bool) $job->gps_active,
                'gps_warning_count' => $job->gps_warning_count ?? 0,
            ],
            'customer' => $this->gpsService->getCustomerLocation($job),
            'pickup' => $this->gpsService->getPickupLocation($job),
        ]);
    }
}
