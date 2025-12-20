<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\StoreRating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * StoreRatingController (Seller)
 *
 * จัดการคะแนนร้านค้าสำหรับ Seller
 * - ดูคะแนนที่ได้รับ
 * - ตอบกลับรีวิว
 * - ดูสถิติ
 */
class StoreRatingController extends Controller
{
    /**
     * แสดง Dashboard คะแนนร้าน
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $store = $request->user()->vendorStore;

        // ดึงสถิติ
        $stats = StoreRating::getStoreStats($store->id);

        // คะแนนล่าสุด
        $recentRatings = StoreRating::where('store_id', $store->id)
            ->approved()
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // รอตอบกลับ
        $pendingResponse = StoreRating::where('store_id', $store->id)
            ->approved()
            ->whereNull('seller_response')
            ->withComment()
            ->count();

        return view('seller.store-rating.index', [
            'store' => $store,
            'stats' => $stats,
            'recentRatings' => $recentRatings,
            'pendingResponse' => $pendingResponse,
            'pageTitle' => 'คะแนนร้านค้า',
        ]);
    }

    /**
     * แสดงรายการคะแนนทั้งหมด
     *
     * @param Request $request
     * @return View
     */
    public function all(Request $request): View
    {
        $store = $request->user()->vendorStore;

        $query = StoreRating::where('store_id', $store->id)
            ->approved()
            ->with('user');

        // กรองตามคะแนน
        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        // กรองตามสถานะตอบกลับ
        if ($request->filled('responded')) {
            if ($request->responded == '1') {
                $query->whereNotNull('seller_response');
            } else {
                $query->whereNull('seller_response');
            }
        }

        // เรียงลำดับ
        $sortBy = $request->get('sort', 'latest');
        switch ($sortBy) {
            case 'helpful':
                $query->orderByHelpful();
                break;
            case 'highest':
                $query->orderByDesc('rating');
                break;
            case 'lowest':
                $query->orderBy('rating');
                break;
            default:
                $query->orderByDesc('created_at');
        }

        $ratings = $query->paginate(20);

        return view('seller.store-rating.all', [
            'ratings' => $ratings,
            'pageTitle' => 'คะแนนทั้งหมด',
        ]);
    }

    /**
     * ตอบกลับรีวิว
     *
     * @param Request $request
     * @param StoreRating $rating
     * @return JsonResponse|RedirectResponse
     */
    public function respond(Request $request, StoreRating $rating)
    {
        $store = $request->user()->vendorStore;

        // ตรวจสอบว่าเป็นคะแนนของร้านนี้
        if ($rating->store_id !== $store->id) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่มีสิทธิ์ตอบกลับคะแนนนี้',
                ], 403);
            }
            return back()->with('error', 'ไม่มีสิทธิ์ตอบกลับคะแนนนี้');
        }

        // ตรวจสอบข้อมูล
        $request->validate([
            'response' => 'required|string|max:1000',
        ], [
            'response.required' => 'กรุณาใส่ข้อความตอบกลับ',
            'response.max' => 'ข้อความต้องไม่เกิน 1000 ตัวอักษร',
        ]);

        // บันทึกการตอบกลับ
        $rating->addSellerResponse($request->input('response'));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'ตอบกลับสำเร็จ',
                'response' => $rating->seller_response,
                'responded_at' => $rating->seller_responded_at->format('d/m/Y H:i'),
            ]);
        }

        return back()->with('success', 'ตอบกลับรีวิวสำเร็จ');
    }

    /**
     * ดูรายละเอียดคะแนน
     *
     * @param Request $request
     * @param StoreRating $rating
     * @return View
     */
    public function show(Request $request, StoreRating $rating): View
    {
        $store = $request->user()->vendorStore;

        // ตรวจสอบว่าเป็นคะแนนของร้านนี้
        abort_if($rating->store_id !== $store->id, 403);

        $rating->load(['user', 'order']);

        return view('seller.store-rating.show', [
            'rating' => $rating,
            'pageTitle' => 'รายละเอียดคะแนน #' . $rating->id,
        ]);
    }

    /**
     * แสดงสถิติแบบละเอียด
     *
     * @param Request $request
     * @return View
     */
    public function statistics(Request $request): View
    {
        $store = $request->user()->vendorStore;

        // ดึงสถิติ
        $stats = StoreRating::getStoreStats($store->id);

        // คะแนนรายเดือน (6 เดือนล่าสุด)
        $monthlyStats = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthRatings = StoreRating::where('store_id', $store->id)
                ->approved()
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->get();

            $monthlyStats[] = [
                'month' => $date->format('M Y'),
                'count' => $monthRatings->count(),
                'average' => $monthRatings->count() > 0
                    ? round($monthRatings->avg('rating'), 1)
                    : 0,
            ];
        }

        return view('seller.store-rating.statistics', [
            'store' => $store,
            'stats' => $stats,
            'monthlyStats' => $monthlyStats,
            'pageTitle' => 'สถิติคะแนน',
        ]);
    }
}
