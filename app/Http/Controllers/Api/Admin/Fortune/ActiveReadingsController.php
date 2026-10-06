<?php

namespace App\Http\Controllers\Api\Admin\Fortune;

use App\Http\Controllers\Controller;
use App\Models\FortuneReading;
use App\Services\AdminApp\ActiveReadingPresenter;
use App\Services\AdminApp\StuckReadingFinder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Admin Mobile API: บิลจ่ายแล้วที่กำลังใช้บริการ + ธงค้าง (แอปไทยพร้อม แอดมิน)
 *
 * นิยาม "ค้าง" อยู่ที่ App\Services\AdminApp\StuckReadingFinder ที่เดียว (ใช้ร่วมกับ ops/summary)
 * อ่านอย่างเดียว — กู้บิลค้างใช้เครื่องมือเดิม (fortune:check-pending ทำเองทุกนาที / ปุ่ม retry บนเว็บ)
 */
class ActiveReadingsController extends Controller
{
    /** เพดานจำนวนแถวที่ประเมินต่อคำขอ (บิลจ่ายแล้วที่ยังใช้บริการใน 24 ชม. ปกติหลักสิบ) */
    private const SCAN_LIMIT = 500;

    /**
     * GET /api/admin/fortune/active-readings?stuck=&page=&per_page=
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'stuck' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $now = now();
        $perPage = (int) ($data['per_page'] ?? 20);
        $pageNo = (int) ($data['page'] ?? 1);

        // ธงค้างต้องเช็คธง "งานยังวิ่งอยู่" ใน cache ทีละบิล → คัดในหน่วยความจำ แล้วแบ่งหน้าเอง
        $items = StuckReadingFinder::activeScope(FortuneReading::query(), $now)
            ->with('user:id,name')
            ->orderByDesc('updated_at')
            ->limit(self::SCAN_LIMIT)
            ->get()
            ->map(fn (FortuneReading $r) => ActiveReadingPresenter::present($r, $now));

        $stuckCount = $items->where('stuck', true)->count();
        $allCount = $items->count();

        if ($request->boolean('stuck')) {
            $items = $items->where('stuck', true);
        }

        // ค้างขึ้นก่อน (ค้างนานสุดก่อน) แล้วตามด้วยที่ขยับล่าสุด
        $items = $items->sort(function ($a, $b) {
            if ($a['stuck'] !== $b['stuck']) {
                return $a['stuck'] ? -1 : 1;
            }
            if ($a['stuck']) {
                return ($b['minutes_since_activity'] ?? 0) <=> ($a['minutes_since_activity'] ?? 0);
            }

            return ($a['minutes_since_activity'] ?? 0) <=> ($b['minutes_since_activity'] ?? 0);
        })->values();

        $page = new LengthAwarePaginator(
            $items->forPage($pageNo, $perPage)->values(),
            $items->count(),
            $perPage,
            $pageNo
        );

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $page->items(),
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'summary' => [
                    'total' => $allCount,
                    'stuck' => $stuckCount,
                ],
            ],
        ]);
    }
}
