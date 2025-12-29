<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\StoreTrophy;
use App\Models\StoreTrophyAchievement;
use App\Services\PremiumStoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AchievementController
 *
 * จัดการ Trophies และ Premium Status สำหรับ Seller
 */
class AchievementController extends Controller
{
    /**
     * หน้าหลัก Achievement Dashboard
     */
    public function index(Request $request, PremiumStoreService $service): View
    {
        $store = $request->user()->vendorStore;

        // Trophy ที่ได้รับ
        $achievements = StoreTrophyAchievement::where('store_id', $store->id)
            ->with('trophy')
            ->orderByDesc('achieved_at')
            ->get();

        // Trophy ที่ยังไม่ได้รับ
        $availableTrophies = StoreTrophy::active()
            ->where('is_premium', false)
            ->whereDoesntHave('achievements', function ($q) use ($store) {
                $q->where('store_id', $store->id);
            })
            ->orderBy('tier')
            ->orderBy('requirement_value')
            ->get();

        // Premium Status
        $premiumReport = $service->getPremiumEligibilityReport($store);

        // สถิติ
        $stats = [
            'total_trophies' => $achievements->count(),
            'trophy_points' => $store->trophy_points ?? 0,
            'is_premium' => $store->is_premium ?? false,
            'ai_score' => $store->ai_score ?? 0,
        ];

        return view('seller.achievements.index', [
            'store' => $store,
            'achievements' => $achievements,
            'availableTrophies' => $availableTrophies,
            'premiumReport' => $premiumReport,
            'stats' => $stats,
            'pageTitle' => 'รางวัลและสถานะ',
        ]);
    }

    /**
     * แสดง Trophy ทั้งหมด
     */
    public function trophies(Request $request, PremiumStoreService $service): View
    {
        $store = $request->user()->vendorStore;

        // ตรวจสอบและให้ Trophy ที่ผ่านเกณฑ์ใหม่
        $newlyAwarded = $service->checkAndAwardTrophies($store);

        // Trophy ที่ได้รับ
        $achievements = StoreTrophyAchievement::where('store_id', $store->id)
            ->with('trophy')
            ->orderByDesc('achieved_at')
            ->get();

        // Trophy ทั้งหมดแยกตาม tier
        $allTrophies = StoreTrophy::active()
            ->orderBy('display_order')
            ->get()
            ->groupBy('tier');

        return view('seller.achievements.trophies', [
            'store' => $store,
            'achievements' => $achievements,
            'allTrophies' => $allTrophies,
            'newlyAwarded' => $newlyAwarded,
            'pageTitle' => 'Trophy ของร้าน',
        ]);
    }

    /**
     * แสดงสถานะ Premium
     */
    public function premiumStatus(Request $request, PremiumStoreService $service): View
    {
        $store = $request->user()->vendorStore;

        $premiumReport = $service->getPremiumEligibilityReport($store);

        // ประวัติ Premium (ถ้ามี)
        $premiumHistory = $store->premiumStore;

        return view('seller.achievements.premium-status', [
            'store' => $store,
            'premiumReport' => $premiumReport,
            'premiumHistory' => $premiumHistory,
            'pageTitle' => 'สถานะร้าน Premium',
        ]);
    }

    /**
     * Toggle การแสดง Trophy บนหน้าร้าน
     */
    public function toggleTrophyDisplay(Request $request, StoreTrophyAchievement $trophy): JsonResponse
    {
        $store = $request->user()->vendorStore;

        // ตรวจสอบว่า Trophy นี้เป็นของร้านนี้
        if ($trophy->store_id !== $store->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์แก้ไข Trophy นี้',
            ], 403);
        }

        $isDisplayed = $trophy->toggleDisplay();

        return response()->json([
            'success' => true,
            'is_displayed' => $isDisplayed,
            'message' => $isDisplayed ? 'แสดง Trophy บนหน้าร้านแล้ว' : 'ซ่อน Trophy จากหน้าร้านแล้ว',
        ]);
    }
}
