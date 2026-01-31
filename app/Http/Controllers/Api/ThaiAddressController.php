<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Thai Address API Controller
 *
 * ให้บริการข้อมูลที่อยู่ไทย (จังหวัด, อำเภอ, ตำบล, รหัสไปรษณีย์)
 * สำหรับระบบกรอกที่อยู่อัจฉริยะแบบ cascading
 *
 * ข้อมูลจะถูก cache ไว้ 24 ชั่วโมง เพื่อลดการอ่านไฟล์ JSON ซ้ำ
 *
 * Endpoints:
 * - GET /api/thai-addresses/provinces           → รายชื่อจังหวัดทั้งหมด
 * - GET /api/thai-addresses/districts/{code}    → อำเภอ/เขต ตามรหัสจังหวัด
 * - GET /api/thai-addresses/sub-districts/{code} → ตำบล/แขวง ตามรหัสอำเภอ
 */
class ThaiAddressController extends Controller
{
    /**
     * ระยะเวลา cache ข้อมูลที่อยู่ (วินาที)
     * 24 ชั่วโมง = 86400 วินาที
     */
    private const CACHE_TTL = 86400;

    /**
     * แสดงรายชื่อจังหวัดทั้งหมด เรียงตามชื่อภาษาไทย
     *
     * GET /api/thai-addresses/provinces
     *
     * @return JsonResponse
     */
    public function provinces(): JsonResponse
    {
        try {
            // ดึงข้อมูลจังหวัดจาก cache หรืออ่านจากไฟล์ JSON
            $provinces = Cache::remember('thai_addresses_provinces', self::CACHE_TTL, function () {
                $json = Storage::disk('local')->get('data/thai_provinces.json');

                if ($json === null) {
                    return null;
                }

                $data = json_decode($json, true);

                if (!is_array($data)) {
                    return null;
                }

                // เรียงลำดับตามชื่อจังหวัดภาษาไทย
                usort($data, function ($a, $b) {
                    return strcmp($a['provinceNameTh'] ?? '', $b['provinceNameTh'] ?? '');
                });

                return $data;
            });

            // ตรวจสอบว่าอ่านข้อมูลได้สำเร็จหรือไม่
            if ($provinces === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถโหลดข้อมูลจังหวัดได้',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $provinces,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลจังหวัด',
            ], 500);
        }
    }

    /**
     * แสดงรายชื่ออำเภอ/เขต ตามรหัสจังหวัดที่ระบุ
     *
     * GET /api/thai-addresses/districts/{provinceCode}
     *
     * @param int $provinceCode รหัสจังหวัด (เช่น 10 = กรุงเทพมหานคร)
     * @return JsonResponse
     */
    public function districts(int $provinceCode): JsonResponse
    {
        try {
            // ดึงข้อมูลอำเภอทั้งหมดจาก cache หรืออ่านจากไฟล์ JSON
            $allDistricts = Cache::remember('thai_addresses_districts', self::CACHE_TTL, function () {
                $json = Storage::disk('local')->get('data/thai_districts.json');

                if ($json === null) {
                    return null;
                }

                $data = json_decode($json, true);

                if (!is_array($data)) {
                    return null;
                }

                return $data;
            });

            // ตรวจสอบว่าอ่านข้อมูลได้สำเร็จหรือไม่
            if ($allDistricts === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถโหลดข้อมูลอำเภอได้',
                ], 500);
            }

            // กรองเฉพาะอำเภอที่อยู่ในจังหวัดที่ระบุ
            $filtered = array_values(array_filter($allDistricts, function ($district) use ($provinceCode) {
                return ($district['provinceCode'] ?? null) === $provinceCode;
            }));

            // เรียงลำดับตามชื่ออำเภอภาษาไทย
            usort($filtered, function ($a, $b) {
                return strcmp($a['districtNameTh'] ?? '', $b['districtNameTh'] ?? '');
            });

            return response()->json([
                'success' => true,
                'data' => $filtered,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลอำเภอ',
            ], 500);
        }
    }

    /**
     * แสดงรายชื่อตำบล/แขวง ตามรหัสอำเภอที่ระบุ (พร้อมรหัสไปรษณีย์)
     *
     * GET /api/thai-addresses/sub-districts/{districtCode}
     *
     * @param int $districtCode รหัสอำเภอ (เช่น 1001 = พระนคร)
     * @return JsonResponse
     */
    public function subDistricts(int $districtCode): JsonResponse
    {
        try {
            // ดึงข้อมูลตำบลทั้งหมดจาก cache หรืออ่านจากไฟล์ JSON
            $allSubDistricts = Cache::remember('thai_addresses_subdistricts', self::CACHE_TTL, function () {
                $json = Storage::disk('local')->get('data/thai_subdistricts.json');

                if ($json === null) {
                    return null;
                }

                $data = json_decode($json, true);

                if (!is_array($data)) {
                    return null;
                }

                return $data;
            });

            // ตรวจสอบว่าอ่านข้อมูลได้สำเร็จหรือไม่
            if ($allSubDistricts === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถโหลดข้อมูลตำบลได้',
                ], 500);
            }

            // กรองเฉพาะตำบลที่อยู่ในอำเภอที่ระบุ
            $filtered = array_values(array_filter($allSubDistricts, function ($subDistrict) use ($districtCode) {
                return ($subDistrict['districtCode'] ?? null) === $districtCode;
            }));

            // เรียงลำดับตามชื่อตำบลภาษาไทย
            usort($filtered, function ($a, $b) {
                return strcmp($a['subdistrictNameTh'] ?? '', $b['subdistrictNameTh'] ?? '');
            });

            return response()->json([
                'success' => true,
                'data' => $filtered,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลตำบล',
            ], 500);
        }
    }
}
