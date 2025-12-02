<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\StarUpgradeHistory;
use App\Models\StarUpgradePrice;
use App\Models\UserVideoLevel;
use Illuminate\Http\Request;

/**
 * StarUpgradeController
 *
 * จัดการการอัพเกรดดาวด้วย Coins สำหรับผู้ใช้
 * ระบบนี้อนุญาตให้ผู้ใช้ซื้อดาวเพิ่มเพื่อแสดงสถานะพิเศษ
 */
class StarUpgradeController extends Controller
{
    /**
     * หน้าหลักระบบอัพเกรดดาว
     *
     * แสดงดาวปัจจุบันและตัวเลือกการอัพเกรด
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = auth()->user();
        $videoLevel = $user->videoLevel ?? UserVideoLevel::firstOrCreate(
            ['user_id' => $user->id],
            [
                'current_level_id' => 1,
                'current_exp' => 0,
                'total_exp' => 0,
            ]
        );

        // ดึงราคาดาวทั้งหมด
        $starPrices = StarUpgradePrice::active()
            ->ordered()
            ->get();

        // ดาวปัจจุบัน
        $currentPurchasedStars = $videoLevel->purchased_stars ?? 0;
        $levelStars = $videoLevel->currentLevel?->stars ?? 1;
        $effectiveStars = $videoLevel->effective_stars;

        // Coins ปัจจุบัน
        $coinBalance = $user->videoCoin?->balance ?? 0;

        // เตรียมข้อมูลตัวเลือกการอัพเกรด
        $upgradeOptions = [];
        foreach ($starPrices as $starPrice) {
            // ตรวจสอบว่าอัพเกรดได้หรือไม่
            $check = $videoLevel->canUpgradeStars($starPrice->stars);

            $upgradeOptions[] = [
                'stars' => $starPrice->stars,
                'name' => $starPrice->display_name,
                'description' => $starPrice->display_description,
                'star_color' => $starPrice->star_color,
                'color_info' => StarUpgradePrice::STAR_COLORS[$starPrice->star_color] ?? StarUpgradePrice::STAR_COLORS['bronze'],
                'price' => $check['price'] ?? $starPrice->price_coins,
                'min_level' => $starPrice->min_level,
                'exp_multiplier' => $starPrice->exp_multiplier,
                'coin_multiplier' => $starPrice->coin_multiplier,
                'benefits' => $starPrice->benefits,
                'can_upgrade' => $check['can_upgrade'],
                'reason' => $check['reason'],
                'is_current' => $currentPurchasedStars === $starPrice->stars,
                'is_owned' => $currentPurchasedStars >= $starPrice->stars,
            ];
        }

        // ประวัติการอัพเกรดล่าสุด
        $recentUpgrades = StarUpgradeHistory::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return view('user.star-upgrade.index', [
            'videoLevel' => $videoLevel,
            'currentPurchasedStars' => $currentPurchasedStars,
            'levelStars' => $levelStars,
            'effectiveStars' => $effectiveStars,
            'coinBalance' => $coinBalance,
            'upgradeOptions' => $upgradeOptions,
            'recentUpgrades' => $recentUpgrades,
            'starColors' => StarUpgradePrice::STAR_COLORS,
            'pageTitle' => 'อัพเกรดดาว',
        ]);
    }

    /**
     * ดำเนินการอัพเกรดดาว
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function upgrade(Request $request)
    {
        $request->validate([
            'target_stars' => 'required|integer|min:1|max:5',
        ]);

        $user = auth()->user();
        $videoLevel = $user->videoLevel;

        if (!$videoLevel) {
            return back()->with('error', 'ยังไม่ได้เริ่มต้นระบบ Video Rewards');
        }

        $targetStars = (int) $request->target_stars;

        // ดำเนินการอัพเกรด
        $result = $videoLevel->upgradeStars(
            $targetStars,
            $request->ip(),
            $request->userAgent()
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * หน้าประวัติการอัพเกรดดาว
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function history(Request $request)
    {
        $user = auth()->user();

        $history = StarUpgradeHistory::where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        // สถิติ
        $stats = [
            'total_upgrades' => StarUpgradeHistory::where('user_id', $user->id)->count(),
            'total_coins_spent' => StarUpgradeHistory::where('user_id', $user->id)
                ->where('status', 'completed')
                ->sum('coins_paid'),
            'highest_star' => StarUpgradeHistory::where('user_id', $user->id)
                ->where('status', 'completed')
                ->max('to_stars') ?? 0,
        ];

        return view('user.star-upgrade.history', [
            'history' => $history,
            'stats' => $stats,
            'starColors' => StarUpgradePrice::STAR_COLORS,
            'pageTitle' => 'ประวัติอัพเกรดดาว',
        ]);
    }

    /**
     * API: ตรวจสอบว่าอัพเกรดได้หรือไม่
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkUpgrade(Request $request)
    {
        $request->validate([
            'target_stars' => 'required|integer|min:1|max:5',
        ]);

        $user = auth()->user();
        $videoLevel = $user->videoLevel;

        if (!$videoLevel) {
            return response()->json([
                'success' => false,
                'can_upgrade' => false,
                'reason' => 'ยังไม่ได้เริ่มต้นระบบ Video Rewards',
            ]);
        }

        $targetStars = (int) $request->target_stars;
        $check = $videoLevel->canUpgradeStars($targetStars);

        // ดึงข้อมูลราคา
        $priceData = StarUpgradePrice::getByStars($targetStars);

        return response()->json([
            'success' => true,
            'can_upgrade' => $check['can_upgrade'],
            'reason' => $check['reason'],
            'price' => $check['price'],
            'current_balance' => $user->videoCoin?->balance ?? 0,
            'target_stars' => $targetStars,
            'color_info' => $priceData ? StarUpgradePrice::STAR_COLORS[$priceData->star_color] ?? null : null,
        ]);
    }
}
