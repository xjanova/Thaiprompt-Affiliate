<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BestSellerMonthly;
use App\Models\NewProductPromotion;
use App\Models\OfficialShopProduct;
use App\Models\OfficialShopSetting;
use App\Models\OfficialShopWarning;
use App\Models\Product;
use App\Services\OfficialShopSelectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * OfficialShopSelectionController
 *
 * จัดการระบบ AI Selection สำหรับ Official Shop ใน Admin Panel
 * - Dashboard AI Selection
 * - รัน AI Selection
 * - จัดการ Warnings
 * - การตั้งค่า
 */
class OfficialShopSelectionController extends Controller
{
    protected OfficialShopSelectionService $service;

    public function __construct(OfficialShopSelectionService $service)
    {
        $this->service = $service;
    }

    /**
     * Dashboard AI Selection
     */
    public function index(): View
    {
        $stats = $this->service->getStatistics();

        // สินค้าที่ถูกคัดเลือก
        $featuredProducts = OfficialShopProduct::aiFeatured()
            ->active()
            ->with(['product.category', 'store'])
            ->orderByDesc('ai_score')
            ->paginate(20);

        // สินค้าใหม่ที่กำลังโปรโมท
        $newProducts = OfficialShopProduct::newProducts()
            ->active()
            ->notExpired()
            ->with(['product', 'store'])
            ->orderByDesc('selected_at')
            ->limit(10)
            ->get();

        // สินค้าขายดีเดือนนี้
        $bestSellers = BestSellerMonthly::getThisMonthBestSellers(10);

        // Warnings ที่รอดำเนินการ
        $pendingWarnings = OfficialShopWarning::pending()
            ->with(['product', 'store'])
            ->orderBy('deadline_at')
            ->limit(10)
            ->get();

        return view('admin.official-shop.selection.index', [
            'stats' => $stats,
            'featuredProducts' => $featuredProducts,
            'newProducts' => $newProducts,
            'bestSellers' => $bestSellers,
            'pendingWarnings' => $pendingWarnings,
            'pageTitle' => 'AI Selection - Official Shop',
        ]);
    }

    /**
     * รัน AI Selection ทันที
     *
     * @return JsonResponse|RedirectResponse
     */
    public function runSelection(Request $request)
    {
        $result = $this->service->runAiSelection();

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            $message = "รัน AI Selection สำเร็จ! เลือกใหม่ {$result['stats']['new_selections']} รายการ";

            return back()->with('success', $message);
        }

        return back()->with('error', $result['message'] ?? 'เกิดข้อผิดพลาด');
    }

    /**
     * แสดงรายการ Warnings
     */
    public function warnings(Request $request): View
    {
        $query = OfficialShopWarning::with(['product', 'store', 'officialShopProduct']);

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->pending();
        }

        $warnings = $query->orderBy('deadline_at')->paginate(20);

        return view('admin.official-shop.selection.warnings', [
            'warnings' => $warnings,
            'pageTitle' => 'Warnings - Official Shop',
        ]);
    }

    /**
     * ประมวลผล Warnings
     *
     * @return JsonResponse|RedirectResponse
     */
    public function processWarnings(Request $request)
    {
        $result = $this->service->processWarnings();

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        $improved = count($result['improved']);
        $expired = count($result['expired']);

        return back()->with('success', "ประมวลผล Warnings สำเร็จ! ปรับปรุงแล้ว {$improved}, หมดเวลา {$expired}");
    }

    /**
     * ถอดสินค้าออกจาก Official Shop
     *
     * @return JsonResponse|RedirectResponse
     */
    public function removeProduct(Request $request, OfficialShopProduct $entry)
    {
        $reason = $request->input('reason', 'ถอดออกโดย Admin');
        $entry->removeFromOfficialShop($reason);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'ถอดสินค้าออกจาก Official Shop สำเร็จ',
            ]);
        }

        return back()->with('success', 'ถอดสินค้าออกจาก Official Shop สำเร็จ');
    }

    /**
     * หน้าตั้งค่า
     */
    public function settings(): View
    {
        $settings = OfficialShopSetting::getAll();

        return view('admin.official-shop.selection.settings', [
            'settings' => $settings,
            'pageTitle' => 'ตั้งค่า AI Selection',
        ]);
    }

    /**
     * บันทึกการตั้งค่า
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'min_ai_score' => 'required|integer|min:0|max:100',
            'best_seller_count' => 'required|integer|min:1|max:100',
            'warning_days' => 'required|integer|min:1|max:30',
            'new_product_promo_days' => 'required|integer|min:1|max:30',
            'new_product_cooldown_days' => 'required|integer|min:1|max:90',
            'ai_selection_enabled' => 'boolean',
            'new_product_promo_enabled' => 'boolean',
            'best_seller_enabled' => 'boolean',
            'max_featured_products' => 'required|integer|min:1|max:500',
            'max_new_products' => 'required|integer|min:1|max:100',
            // น้ำหนักคะแนน
            'score_weight_rating' => 'required|integer|min:0|max:100',
            'score_weight_reviews' => 'required|integer|min:0|max:100',
            'score_weight_sales' => 'required|integer|min:0|max:100',
            'score_weight_followers' => 'required|integer|min:0|max:100',
            'score_weight_products' => 'required|integer|min:0|max:100',
            'score_weight_age' => 'required|integer|min:0|max:100',
            'score_weight_trophies' => 'required|integer|min:0|max:100',
        ]);

        // ตรวจสอบน้ำหนักรวมเท่ากับ 100
        $totalWeight = $validated['score_weight_rating']
            + $validated['score_weight_reviews']
            + $validated['score_weight_sales']
            + $validated['score_weight_followers']
            + $validated['score_weight_products']
            + $validated['score_weight_age']
            + $validated['score_weight_trophies'];

        if ($totalWeight !== 100) {
            return back()
                ->withInput()
                ->with('error', "น้ำหนักคะแนนรวมต้องเท่ากับ 100% (ปัจจุบัน: {$totalWeight}%)");
        }

        // บันทึกการตั้งค่า
        foreach ($validated as $key => $value) {
            OfficialShopSetting::set($key, $value);
        }

        // ล้าง cache
        OfficialShopSetting::clearCache();

        return back()->with('success', 'บันทึกการตั้งค่าสำเร็จ');
    }

    /**
     * คำนวณสินค้าขายดีรายเดือน
     *
     * @return JsonResponse|RedirectResponse
     */
    public function calculateBestSellers(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $result = $this->service->calculateMonthlyBestSellers((int) $month, (int) $year);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        if (isset($result['products'])) {
            $count = count($result['products']);

            return back()->with('success', "คำนวณสินค้าขายดีสำเร็จ! พบ {$count} รายการ");
        }

        return back()->with('error', $result['message'] ?? 'เกิดข้อผิดพลาด');
    }

    /**
     * แสดงรายการสินค้าใหม่ที่กำลังโปรโมท
     */
    public function newProductPromotions(Request $request): View
    {
        $query = NewProductPromotion::with(['product', 'store', 'requestedBy']);

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $promotions = $query->orderByDesc('created_at')->paginate(20);

        return view('admin.official-shop.selection.new-promotions', [
            'promotions' => $promotions,
            'pageTitle' => 'โปรโมทสินค้าใหม่',
        ]);
    }

    /**
     * ยกเลิกโปรโมทสินค้าใหม่
     *
     * @return JsonResponse|RedirectResponse
     */
    public function cancelPromotion(Request $request, NewProductPromotion $promotion)
    {
        $reason = $request->input('reason', 'ยกเลิกโดย Admin');
        $promotion->cancelPromotion($reason);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'ยกเลิกโปรโมทสำเร็จ',
            ]);
        }

        return back()->with('success', 'ยกเลิกโปรโมทสำเร็จ');
    }

    /**
     * คำนวณคะแนนสินค้าแบบ Preview
     */
    public function previewScore(Request $request, Product $product): JsonResponse
    {
        $scoreData = $this->service->calculateProductScore($product);

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
            ],
            'score' => $scoreData['score'],
            'breakdown' => $scoreData['breakdown'],
            'min_required' => OfficialShopSetting::getMinAiScore(),
            'is_eligible' => $scoreData['score'] >= OfficialShopSetting::getMinAiScore(),
        ]);
    }

    /**
     * เพิ่มสินค้าเข้า Official Shop ด้วยตนเอง
     *
     * @return JsonResponse|RedirectResponse
     */
    public function addProductManually(Request $request, Product $product)
    {
        // คำนวณคะแนน
        $scoreData = $this->service->calculateProductScore($product);

        // เพิ่มเข้า Official Shop
        $entry = OfficialShopProduct::addProduct(
            $product,
            OfficialShopProduct::TYPE_AI_FEATURED,
            $scoreData['score'],
            $scoreData['breakdown']
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'เพิ่มสินค้าเข้า Official Shop สำเร็จ',
                'entry_id' => $entry->id,
            ]);
        }

        return back()->with('success', 'เพิ่มสินค้าเข้า Official Shop สำเร็จ: '.$product->name);
    }

    /**
     * แสดงรายการสินค้าขายดี
     */
    public function bestSellers(Request $request): View
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $bestSellers = BestSellerMonthly::with(['product.category', 'store'])
            ->forPeriod((int) $month, (int) $year)
            ->active()
            ->orderByRank()
            ->paginate(20);

        return view('admin.official-shop.selection.best-sellers', [
            'bestSellers' => $bestSellers,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'pageTitle' => 'สินค้าขายดีรายเดือน',
        ]);
    }
}
