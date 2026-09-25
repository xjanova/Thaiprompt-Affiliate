<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MobileBanner;
use App\Services\AppBannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * แบนเนอร์แคมเปญของแอป (public — ไม่ต้องล็อกอิน)
 *
 * GET  /api/v1/banners?placement=home|taladsod|rider|merchant|shop&audience=buyer|rider|merchant
 * POST /api/v1/banners/{id}/click
 * POST /api/v1/banners/impressions {ids: [..]}
 *
 * ของเดิม /api/v1/mobile/banners (MobileDeviceController) ยังใช้ได้สำหรับแอปรุ่นเก่า
 */
class AppBannerApiController extends Controller
{
    public function __construct(private readonly AppBannerService $banners) {}

    /**
     * แบนเนอร์ที่กำลังแสดงของตำแหน่งหนึ่ง (เรียงตาม sort)
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'placement' => ['nullable', 'string', Rule::in(array_keys(MobileBanner::PLACEMENTS))],
            'audience' => ['nullable', 'string', Rule::in(array_keys(MobileBanner::AUDIENCES))],
        ], [
            'placement.in' => 'ตำแหน่งแบนเนอร์ไม่ถูกต้อง (home, taladsod, rider, merchant, shop)',
            'audience.in' => 'กลุ่มผู้ชมไม่ถูกต้อง (all, buyer, rider, merchant)',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()->toArray(),
                'data' => null,
            ], 422);
        }

        $placement = (string) ($request->query('placement') ?: MobileBanner::POSITION_HOME);
        $audience = $request->query('audience');
        // audience=all = ไม่กรองกลุ่ม (เหมือนไม่ส่ง)
        $audience = is_string($audience) && $audience !== '' && $audience !== 'all' ? $audience : null;

        try {
            $items = $this->banners->activeFor($placement, $audience);
        } catch (\Throwable $e) {
            Log::error('AppBannerApi: list failed', ['placement' => $placement, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'code' => 'BANNERS_UNAVAILABLE',
                'message' => 'ยังโหลดแบนเนอร์ไม่ได้ กรุณาลองใหม่อีกครั้ง',
                'data' => [],
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'ดึงแบนเนอร์สำเร็จ',
            'data' => $items,
            'meta' => [
                'placement' => $placement,
                'audience' => $audience ?? 'all',
                'count' => count($items),
                'cache_ttl_seconds' => AppBannerService::CACHE_TTL_SECONDS,
                'server_time' => now()->toIso8601String(),
            ],
        ])->header('Cache-Control', 'public, max-age=60');
    }

    /**
     * นับคลิกแบนเนอร์ (กดซ้ำจาก IP เดิมภายใน 1 นาทีไม่นับ)
     */
    public function click(Request $request, int $id): JsonResponse
    {
        $banner = MobileBanner::find($id);

        if ($banner === null) {
            return response()->json([
                'success' => false,
                'code' => 'BANNER_NOT_FOUND',
                'message' => 'ไม่พบแบนเนอร์นี้',
                'data' => null,
            ], 404);
        }

        try {
            $counted = $this->banners->recordClick($banner, $request->ip());
        } catch (\Throwable $e) {
            Log::warning('AppBannerApi: record click failed', ['banner_id' => $id, 'error' => $e->getMessage()]);
            $counted = false;
        }

        return response()->json([
            'success' => true,
            'message' => 'บันทึกการคลิกแล้ว',
            'data' => ['id' => (int) $banner->id, 'counted' => $counted],
        ]);
    }

    /**
     * นับการแสดงผลของแบนเนอร์ที่ผู้ใช้เห็นบนจอ (ส่งเป็นชุด ไม่เกิน 20 รายการ)
     */
    public function impressions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => ['required', 'array', 'min:1', 'max:20'],
            'ids.*' => ['integer', 'min:1'],
        ], [
            'ids.required' => 'กรุณาส่งรายการแบนเนอร์',
            'ids.array' => 'รายการแบนเนอร์ต้องเป็นอาร์เรย์',
            'ids.max' => 'ส่งได้ไม่เกิน 20 แบนเนอร์ต่อครั้ง',
            'ids.*.integer' => 'รหัสแบนเนอร์ไม่ถูกต้อง',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()->toArray(),
                'data' => null,
            ], 422);
        }

        try {
            $counted = $this->banners->recordImpressions((array) $request->input('ids', []), $request->ip());
        } catch (\Throwable $e) {
            Log::warning('AppBannerApi: record impressions failed', ['error' => $e->getMessage()]);
            $counted = 0;
        }

        return response()->json([
            'success' => true,
            'message' => 'บันทึกการแสดงผลแล้ว',
            'data' => ['counted' => $counted],
        ]);
    }
}
