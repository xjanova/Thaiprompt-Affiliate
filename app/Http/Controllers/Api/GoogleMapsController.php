<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GoogleMapsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Google Maps API Controller
 *
 * Controller สำหรับจัดการ Google Maps API ทั้งหมด
 * รองรับ geocoding, directions, distance matrix, places search
 *
 * @version 1.0
 */
class GoogleMapsController extends Controller
{
    /**
     * Constructor
     *
     * @param GoogleMapsService $mapsService
     */
    public function __construct(
        protected GoogleMapsService $mapsService
    ) {
    }

    /**
     * Reverse Geocode: แปลงพิกัด GPS → ที่อยู่
     *
     * POST /api/v1/maps/reverse-geocode
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function reverseGeocode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        try {
            $result = $this->mapsService->reverseGeocode(
                $validated['lat'],
                $validated['lng']
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถแปลงพิกัดเป็นที่อยู่ได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Forward Geocode: แปลงที่อยู่ → พิกัด GPS
     *
     * POST /api/v1/maps/geocode
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function geocode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address' => 'required|string|max:500',
        ]);

        try {
            $result = $this->mapsService->geocode($validated['address']);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถแปลงที่อยู่เป็นพิกัดได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * คำนวณเส้นทางและระยะทาง
     *
     * POST /api/v1/maps/directions
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDirections(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin' => 'required|array',
            'origin.lat' => 'required|numeric|between:-90,90',
            'origin.lng' => 'required|numeric|between:-180,180',
            'destination' => 'required|array',
            'destination.lat' => 'required|numeric|between:-90,90',
            'destination.lng' => 'required|numeric|between:-180,180',
            'mode' => 'nullable|string|in:driving,walking,bicycling,transit',
        ]);

        try {
            $result = $this->mapsService->getDirections(
                $validated['origin'],
                $validated['destination'],
                $validated['mode'] ?? 'driving'
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถคำนวณเส้นทางได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * คำนวณระยะทางหลายจุดพร้อมกัน (Distance Matrix)
     *
     * POST /api/v1/maps/distance-matrix
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDistanceMatrix(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origins' => 'required|array|min:1|max:25',
            'origins.*.lat' => 'required|numeric|between:-90,90',
            'origins.*.lng' => 'required|numeric|between:-180,180',
            'destinations' => 'required|array|min:1|max:25',
            'destinations.*.lat' => 'required|numeric|between:-90,90',
            'destinations.*.lng' => 'required|numeric|between:-180,180',
            'mode' => 'nullable|string|in:driving,walking,bicycling,transit',
        ]);

        try {
            $result = $this->mapsService->getDistanceMatrix(
                $validated['origins'],
                $validated['destinations'],
                $validated['mode'] ?? 'driving'
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถคำนวณระยะทางได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ค้นหาสถานที่ใกล้เคียง
     *
     * POST /api/v1/maps/search-nearby
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchNearby(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'type' => 'required|string|max:50',
            'radius' => 'nullable|integer|min:100|max:50000',
        ]);

        try {
            $result = $this->mapsService->searchNearby(
                $validated['lat'],
                $validated['lng'],
                $validated['type'],
                $validated['radius'] ?? 1000
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'count' => count($result),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถค้นหาสถานที่ได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ดูรายละเอียดสถานที่จาก Place ID
     *
     * GET /api/v1/maps/place/{placeId}
     *
     * @param string $placeId
     * @return JsonResponse
     */
    public function getPlaceDetails(string $placeId): JsonResponse
    {
        try {
            $result = $this->mapsService->getPlaceDetails($placeId);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงข้อมูลสถานที่ได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ตรวจสอบสถานะ Google Maps API
     *
     * GET /api/v1/maps/status
     *
     * @return JsonResponse
     */
    public function getStatus(): JsonResponse
    {
        try {
            $capabilities = $this->mapsService->checkApiCapabilities();

            return response()->json([
                'success' => true,
                'data' => $capabilities,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถตรวจสอบสถานะได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
