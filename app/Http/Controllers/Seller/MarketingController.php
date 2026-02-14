<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\NewProductPromotion;
use App\Models\OfficialShopProduct;
use App\Models\OfficialShopSetting;
use App\Models\OfficialShopWarning;
use App\Models\Product;
use App\Models\StorePromotionCooldown;
use App\Services\OfficialShopSelectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * MarketingController
 *
 * จัดการการตลาดสำหรับ Seller
 * - ขอโปรโมทสินค้าใหม่
 * - ดูสถานะ Official Shop
 * - ดู Warnings
 */
class MarketingController extends Controller
{
    protected OfficialShopSelectionService $service;

    public function __construct(OfficialShopSelectionService $service)
    {
        $this->service = $service;
    }

    /**
     * หน้าหลัก Marketing Dashboard
     */
    public function index(Request $request): View|RedirectResponse
    {
        $store = $request->user()->vendorStore;

        // ตรวจสอบว่ามี store หรือไม่
        if (! $store) {
            return redirect()->route('seller.onboarding.index')
                ->with('warning', 'กรุณาสร้างร้านค้าก่อนใช้งานฟีเจอร์นี้');
        }

        // สินค้าที่อยู่ใน Official Shop
        $officialProducts = OfficialShopProduct::where('store_id', $store->id)
            ->active()
            ->with('product')
            ->get();

        // สินค้าที่กำลังโปรโมท
        $activePromotion = NewProductPromotion::where('store_id', $store->id)
            ->active()
            ->with('product')
            ->first();

        // ตรวจสอบสิทธิ์โปรโมทสินค้าใหม่
        $promoCheck = NewProductPromotion::canStorePromote($store->id);

        // Warnings ที่ยังไม่ได้แก้
        $pendingWarnings = OfficialShopWarning::where('store_id', $store->id)
            ->pending()
            ->with('product')
            ->get();

        // สถิติ
        $stats = [
            'in_official_shop' => $officialProducts->count(),
            'ai_featured' => $officialProducts->where('selection_type', OfficialShopProduct::TYPE_AI_FEATURED)->count(),
            'as_new_product' => $officialProducts->where('selection_type', OfficialShopProduct::TYPE_NEW_PRODUCT)->count(),
            'as_best_seller' => $officialProducts->where('selection_type', OfficialShopProduct::TYPE_BEST_SELLER)->count(),
            'pending_warnings' => $pendingWarnings->count(),
        ];

        return view('seller.marketing.index', [
            'store' => $store,
            'officialProducts' => $officialProducts,
            'activePromotion' => $activePromotion,
            'promoCheck' => $promoCheck,
            'pendingWarnings' => $pendingWarnings,
            'stats' => $stats,
            'pageTitle' => 'การตลาด',
        ]);
    }

    /**
     * หน้าเลือกสินค้าเพื่อโปรโมท
     */
    public function selectProductForPromotion(Request $request): View|RedirectResponse
    {
        $store = $request->user()->vendorStore;

        // ตรวจสอบว่ามี store หรือไม่
        if (! $store) {
            return redirect()->route('seller.onboarding.index')
                ->with('warning', 'กรุณาสร้างร้านค้าก่อนใช้งานฟีเจอร์นี้');
        }

        // ตรวจสอบสิทธิ์
        $promoCheck = NewProductPromotion::canStorePromote($store->id);

        // ดึงสินค้าที่ยังไม่เคยโปรโมท
        $products = Product::where('store_id', $store->id)
            ->where('is_active', true)
            ->whereDoesntHave('newProductPromotion')
            ->latest()
            ->paginate(20);

        return view('seller.marketing.select-product', [
            'products' => $products,
            'promoCheck' => $promoCheck,
            'pageTitle' => 'เลือกสินค้าโปรโมท',
        ]);
    }

    /**
     * ขอโปรโมทสินค้าใหม่
     *
     * @return JsonResponse|RedirectResponse
     */
    public function requestPromotion(Request $request, Product $product)
    {
        $store = $request->user()->vendorStore;
        $user = $request->user();

        // ตรวจสอบว่ามี store หรือไม่
        if (! $store) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'กรุณาสร้างร้านค้าก่อนใช้งานฟีเจอร์นี้',
                ], 403);
            }

            return redirect()->route('seller.onboarding.index')
                ->with('warning', 'กรุณาสร้างร้านค้าก่อนใช้งานฟีเจอร์นี้');
        }

        // ตรวจสอบว่าเป็นสินค้าของร้านนี้
        if ($product->store_id !== $store->id) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'สินค้านี้ไม่ใช่ของร้านค้าคุณ',
                ], 403);
            }

            return back()->with('error', 'สินค้านี้ไม่ใช่ของร้านค้าคุณ');
        }

        $result = $this->service->requestNewProductPromotion($product, $store, $user->id);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()
                ->route('seller.marketing.index')
                ->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * แสดงประวัติโปรโมทสินค้าใหม่
     */
    public function promotionHistory(Request $request): View|RedirectResponse
    {
        $store = $request->user()->vendorStore;

        // ตรวจสอบว่ามี store หรือไม่
        if (! $store) {
            return redirect()->route('seller.onboarding.index')
                ->with('warning', 'กรุณาสร้างร้านค้าก่อนใช้งานฟีเจอร์นี้');
        }

        $promotions = NewProductPromotion::where('store_id', $store->id)
            ->with('product')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('seller.marketing.promotion-history', [
            'promotions' => $promotions,
            'pageTitle' => 'ประวัติโปรโมท',
        ]);
    }

    /**
     * แสดงรายละเอียด Warnings
     */
    public function warnings(Request $request): View|RedirectResponse
    {
        $store = $request->user()->vendorStore;

        // ตรวจสอบว่ามี store หรือไม่
        if (! $store) {
            return redirect()->route('seller.onboarding.index')
                ->with('warning', 'กรุณาสร้างร้านค้าก่อนใช้งานฟีเจอร์นี้');
        }

        $warnings = OfficialShopWarning::where('store_id', $store->id)
            ->with(['product', 'officialShopProduct'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('seller.marketing.warnings', [
            'warnings' => $warnings,
            'pageTitle' => 'การแจ้งเตือน Official Shop',
        ]);
    }

    /**
     * แสดงสินค้าที่อยู่ใน Official Shop
     */
    public function officialShopProducts(Request $request): View|RedirectResponse
    {
        $store = $request->user()->vendorStore;

        // ตรวจสอบว่ามี store หรือไม่
        if (! $store) {
            return redirect()->route('seller.onboarding.index')
                ->with('warning', 'กรุณาสร้างร้านค้าก่อนใช้งานฟีเจอร์นี้');
        }

        $products = OfficialShopProduct::where('store_id', $store->id)
            ->with(['product.category'])
            ->orderByDesc('selected_at')
            ->paginate(20);

        return view('seller.marketing.official-products', [
            'products' => $products,
            'pageTitle' => 'สินค้าใน Official Shop',
        ]);
    }

    /**
     * ดู Preview คะแนน AI ของสินค้า
     */
    public function previewScore(Request $request, Product $product): JsonResponse
    {
        $store = $request->user()->vendorStore;

        // ตรวจสอบว่ามี store หรือไม่
        if (! $store) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาสร้างร้านค้าก่อนใช้งานฟีเจอร์นี้',
            ], 403);
        }

        // ตรวจสอบว่าเป็นสินค้าของร้านนี้
        if ($product->store_id !== $store->id) {
            return response()->json([
                'success' => false,
                'message' => 'สินค้านี้ไม่ใช่ของร้านค้าคุณ',
            ], 403);
        }

        $scoreData = $this->service->calculateProductScore($product);
        $minScore = OfficialShopSetting::getMinAiScore();

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
            ],
            'score' => $scoreData['score'],
            'breakdown' => $scoreData['breakdown'],
            'min_required' => $minScore,
            'is_eligible' => $scoreData['score'] >= $minScore,
            'score_gap' => max(0, $minScore - $scoreData['score']),
            'tips' => $this->getImprovementTips($scoreData['breakdown'], $minScore - $scoreData['score']),
        ]);
    }

    /**
     * สร้างคำแนะนำในการปรับปรุงคะแนน
     */
    protected function getImprovementTips(array $breakdown, float $scoreGap): array
    {
        $tips = [];

        if ($scoreGap <= 0) {
            $tips[] = [
                'type' => 'success',
                'message' => 'คะแนนผ่านเกณฑ์แล้ว รักษามาตรฐานเพื่อให้อยู่ใน Official Shop ต่อไป',
            ];

            return $tips;
        }

        // วิเคราะห์แต่ละด้าน
        if (isset($breakdown['rating']) && $breakdown['rating']['score'] < 80) {
            $tips[] = [
                'type' => 'warning',
                'message' => 'เพิ่มคะแนน Rating โดยปรับปรุงคุณภาพสินค้าและบริการ เพื่อให้ลูกค้าให้รีวิวดี',
            ];
        }

        if (isset($breakdown['reviews']) && $breakdown['reviews']['raw'] < 30) {
            $tips[] = [
                'type' => 'info',
                'message' => 'กระตุ้นให้ลูกค้าเขียนรีวิว จะช่วยเพิ่มคะแนนได้',
            ];
        }

        if (isset($breakdown['sales']) && $breakdown['sales']['score'] < 50) {
            $tips[] = [
                'type' => 'info',
                'message' => 'เพิ่มยอดขายด้วยการทำโปรโมชั่น หรือปรับราคาให้แข่งขันได้',
            ];
        }

        if (isset($breakdown['followers']) && $breakdown['followers']['raw'] < 100) {
            $tips[] = [
                'type' => 'info',
                'message' => 'เพิ่มผู้ติดตามร้านโดยแชร์ร้านค้าในโซเชียลมีเดีย',
            ];
        }

        if (empty($tips)) {
            $tips[] = [
                'type' => 'info',
                'message' => "ต้องเพิ่มคะแนนอีก {$scoreGap} คะแนน เพื่อผ่านเกณฑ์ AI Selection",
            ];
        }

        return $tips;
    }

    /**
     * ดูรายละเอียด Cooldown
     */
    public function getCooldownStatus(Request $request): JsonResponse
    {
        $store = $request->user()->vendorStore;

        // ตรวจสอบว่ามี store หรือไม่
        if (! $store) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาสร้างร้านค้าก่อนใช้งานฟีเจอร์นี้',
            ], 403);
        }

        $cooldown = StorePromotionCooldown::where('store_id', $store->id)
            ->where('promotion_type', 'new_product')
            ->first();

        if (! $cooldown) {
            return response()->json([
                'is_available' => true,
                'message' => 'พร้อมโปรโมทสินค้าใหม่',
            ]);
        }

        return response()->json([
            'is_available' => ! $cooldown->is_on_cooldown,
            'available_at' => $cooldown->available_at?->toDateTimeString(),
            'time_remaining' => $cooldown->time_remaining,
            'usage_count' => $cooldown->usage_count,
            'last_used_at' => $cooldown->last_used_at?->toDateTimeString(),
        ]);
    }

    /**
     * ขอแก้ไขสินค้าที่ถูกล็อค (สร้าง Ticket)
     */
    public function requestEditLockedProduct(Request $request, Product $product): RedirectResponse
    {
        $store = $request->user()->vendorStore;

        // ตรวจสอบว่ามี store หรือไม่
        if (! $store) {
            return redirect()->route('seller.onboarding.index')
                ->with('warning', 'กรุณาสร้างร้านค้าก่อนใช้งานฟีเจอร์นี้');
        }

        // ตรวจสอบว่าเป็นสินค้าของร้านนี้
        if ($product->store_id !== $store->id) {
            return back()->with('error', 'สินค้านี้ไม่ใช่ของร้านค้าคุณ');
        }

        // ตรวจสอบว่าสินค้าถูกล็อคจริง
        if (! $product->is_promotion_locked) {
            return back()->with('error', 'สินค้านี้ไม่ได้ถูกล็อค');
        }

        // Redirect ไปหน้าสร้าง Ticket พร้อม context
        return redirect()
            ->route('seller.tickets.create', [
                'subject' => 'ขอแก้ไขสินค้าที่ถูกล็อค: '.$product->name,
                'product_id' => $product->id,
                'category' => 'product_edit_request',
            ]);
    }
}
