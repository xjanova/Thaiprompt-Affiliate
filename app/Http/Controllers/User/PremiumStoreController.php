<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PremiumStore;
use App\Models\VendorStore;
use App\Services\PremiumStoreService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PremiumStoreController
 *
 * จัดการแสดงร้าน Premium สำหรับผู้ใช้
 */
class PremiumStoreController extends Controller
{
    /**
     * แสดงรายการร้าน Premium
     *
     * @param Request $request
     * @param PremiumStoreService $service
     * @return View
     */
    public function index(Request $request, PremiumStoreService $service): View
    {
        // ร้าน Premium ที่ AI คัดเลือก
        $premiumStores = PremiumStore::active()
            ->with(['store' => function ($query) {
                $query->with(['products' => function ($pq) {
                    $pq->where('is_active', true)
                        ->orderByDesc('sales_count')
                        ->limit(4);
                }]);
            }])
            ->orderByDesc('ai_score')
            ->orderByDesc('is_featured')
            ->paginate(12);

        // ร้าน Featured
        $featuredStores = PremiumStore::active()
            ->featured()
            ->with('store')
            ->orderByDesc('ai_score')
            ->limit(6)
            ->get()
            ->pluck('store');

        // สถิติ
        $stats = [
            'total_premium' => PremiumStore::active()->count(),
            'ai_selected' => PremiumStore::active()->aiSelected()->count(),
            'avg_rating' => PremiumStore::active()->avg('rating_snapshot') ?? 0,
        ];

        return view('user.premium-stores.index', [
            'premiumStores' => $premiumStores,
            'featuredStores' => $featuredStores,
            'stats' => $stats,
            'pageTitle' => 'ร้านพรีเมี่ยม',
        ]);
    }

    /**
     * แสดงรายละเอียดร้าน Premium
     *
     * @param VendorStore $store
     * @return View
     */
    public function show(VendorStore $store): View
    {
        // ตรวจสอบว่าเป็นร้าน Premium
        $premium = PremiumStore::where('store_id', $store->id)
            ->active()
            ->first();

        // โหลดข้อมูลร้าน
        $store->load([
            'products' => function ($query) {
                $query->where('is_active', true)
                    ->orderByDesc('sales_count')
                    ->limit(20);
            },
            'trophyAchievements.trophy',
        ]);

        // สินค้าขายดี
        $bestSellers = $store->products()
            ->where('is_active', true)
            ->orderByDesc('sales_count')
            ->limit(8)
            ->get();

        // สินค้าแนะนำ
        $recommended = $store->products()
            ->where('is_active', true)
            ->orderByDesc('rating_average')
            ->limit(8)
            ->get();

        return view('user.premium-stores.show', [
            'store' => $store,
            'premium' => $premium,
            'bestSellers' => $bestSellers,
            'recommended' => $recommended,
            'pageTitle' => $store->store_name,
        ]);
    }
}
