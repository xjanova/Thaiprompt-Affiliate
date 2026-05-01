<?php

namespace App\Http\Controllers\Api\Admin\Fortune;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\Fortune\FortuneReadingResource;
use App\Models\FortuneReading;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin Mobile API: Fortune Readings
 *
 * คืนรายการ readings (คำทำนาย) สำหรับ admin app
 */
class FortuneReadingsController extends Controller
{
    /**
     * GET /api/admin/fortune/readings
     *
     * Query: ?search=&is_paid=&response_type=&date_from=&date_to=&page=
     */
    public function index(Request $request): JsonResponse
    {
        $query = FortuneReading::query();

        // Filters
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('facebook_user_name', 'like', "%{$search}%")
                    ->orWhere('facebook_user_id', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_paid')) {
            $query->where('is_paid', $request->boolean('is_paid'));
        }

        if ($request->filled('response_type')) {
            $query->where('response_type', $request->input('response_type'));
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to'));
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $readings = $query->with('user')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => FortuneReadingResource::collection($readings)->response()->getData(true),
        ]);
    }

    /**
     * GET /api/admin/fortune/readings/{reading}
     */
    public function show(FortuneReading $reading): JsonResponse
    {
        $reading->load('user');

        return response()->json([
            'success' => true,
            'data' => new FortuneReadingResource($reading),
        ]);
    }

    /**
     * GET /api/admin/fortune/readings/stats
     *
     * คืนสถิติแบ่งตามสถานะ
     */
    public function stats(): JsonResponse
    {
        try {
            $totalCount = FortuneReading::count();
            $paidCount = FortuneReading::where('is_paid', true)->count();
            $pendingCount = FortuneReading::whereNull('responded_at')->count();
            $deepCount = FortuneReading::where('reading_type', 'deep')->count();
            $totalRevenue = (float) FortuneReading::where('is_paid', true)->sum('amount_paid');

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $totalCount,
                    'paid' => $paidCount,
                    'pending' => $pendingCount,
                    'deep' => $deepCount,
                    'basic' => $totalCount - $deepCount,
                    'total_revenue_thb' => round($totalRevenue, 2),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total' => 0, 'paid' => 0, 'pending' => 0,
                    'deep' => 0, 'basic' => 0, 'total_revenue_thb' => 0,
                ],
            ]);
        }
    }
}
