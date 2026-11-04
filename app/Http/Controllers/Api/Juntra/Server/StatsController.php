<?php

namespace App\Http\Controllers\Api\Juntra\Server;

use App\Http\Controllers\Controller;
use App\Services\Fortune\FortuneLiveActivity;
use Illuminate\Http\JsonResponse;

/**
 * 🔴 (2026-11-04) GET /api/v1/juntra/server/stats/live — ตัวเลขสดของป้ายหน้าห้องเว็บจันทรา
 *
 * ทุกค่าเป็นของจริงจาก FortuneLiveActivity (ไม่มีเลขสมมติ) — ค่าที่นับไม่ได้เป็น null ให้เว็บซ่อนช่องนั้น
 */
class StatsController extends Controller
{
    public function live(FortuneLiveActivity $live): JsonResponse
    {
        return response()->json(['data' => $live->stats()]);
    }
}
