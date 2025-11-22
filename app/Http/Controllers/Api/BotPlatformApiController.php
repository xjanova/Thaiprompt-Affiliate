<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Bot Platform API Controller
 *
 * จัดการ API endpoints สำหรับ Bot Platform integration
 */
class BotPlatformApiController extends Controller
{
    /**
     * แสดงรายการ Bot Platforms ทั้งหมด
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return response()->json([
            'success' => false,
            'message' => 'Bot Platform API is not yet implemented',
        ], 501);
    }

    /**
     * แสดงรายการ Bot Platforms ที่พร้อมใช้งาน
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function available()
    {
        return response()->json([
            'success' => false,
            'message' => 'Bot Platform available API is not yet implemented',
        ], 501);
    }

    /**
     * เชื่อมต่อกับ Bot Platform
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function connect(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Bot Platform connect API is not yet implemented',
        ], 501);
    }

    /**
     * ตัดการเชื่อมต่อกับ Bot Platform
     *
     * @param int $connection
     * @return \Illuminate\Http\JsonResponse
     */
    public function disconnect($connection)
    {
        return response()->json([
            'success' => false,
            'message' => 'Bot Platform disconnect API is not yet implemented',
        ], 501);
    }
}
