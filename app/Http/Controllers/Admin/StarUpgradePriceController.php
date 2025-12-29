<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StarUpgradeHistory;
use App\Models\StarUpgradePrice;
use Illuminate\Http\Request;

/**
 * Admin StarUpgradePriceController
 *
 * จัดการราคาอัพเกรดดาวสำหรับ Admin
 * กำหนดราคา เงื่อนไข และสิทธิประโยชน์ของแต่ละระดับดาว
 */
class StarUpgradePriceController extends Controller
{
    /**
     * หน้าจัดการราคาดาว
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $starPrices = StarUpgradePrice::ordered()->get();

        // สถิติการอัพเกรด
        $stats = [
            'total_upgrades' => StarUpgradeHistory::where('status', 'completed')->count(),
            'total_coins_collected' => StarUpgradeHistory::where('status', 'completed')->sum('coins_paid'),
            'upgrades_today' => StarUpgradeHistory::where('status', 'completed')
                ->whereDate('created_at', today())
                ->count(),
            'upgrades_this_month' => StarUpgradeHistory::where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        // ยอดอัพเกรดแยกตามดาว
        $upgradesByStars = StarUpgradeHistory::where('status', 'completed')
            ->selectRaw('to_stars, COUNT(*) as count, SUM(coins_paid) as total_coins')
            ->groupBy('to_stars')
            ->orderBy('to_stars')
            ->get()
            ->keyBy('to_stars');

        return view('admin.star-upgrade.index', [
            'starPrices' => $starPrices,
            'stats' => $stats,
            'upgradesByStars' => $upgradesByStars,
            'starColors' => StarUpgradePrice::STAR_COLORS,
            'pageTitle' => 'จัดการราคาอัพเกรดดาว',
        ]);
    }

    /**
     * หน้าแก้ไขราคาดาว
     *
     * @return \Illuminate\View\View
     */
    public function edit(StarUpgradePrice $starPrice)
    {
        return view('admin.star-upgrade.edit', [
            'starPrice' => $starPrice,
            'starColors' => StarUpgradePrice::STAR_COLORS,
            'pageTitle' => 'แก้ไขราคาดาว: '.$starPrice->display_name,
        ]);
    }

    /**
     * อัพเดทราคาดาว
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, StarUpgradePrice $starPrice)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'star_color' => 'required|in:bronze,silver,gold,platinum,diamond',
            'price_coins' => 'required|numeric|min:0',
            'price_coins_from_previous' => 'nullable|numeric|min:0',
            'min_level' => 'required|integer|min:1',
            'require_previous_star' => 'boolean',
            'exp_multiplier' => 'required|numeric|min:1|max:10',
            'coin_multiplier' => 'required|numeric|min:1|max:10',
            'benefits' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $starPrice->update([
            'name' => $validated['name'],
            'name_th' => $validated['name_th'] ?? null,
            'description' => $validated['description'] ?? null,
            'description_th' => $validated['description_th'] ?? null,
            'star_color' => $validated['star_color'],
            'price_coins' => $validated['price_coins'],
            'price_coins_from_previous' => $validated['price_coins_from_previous'] ?? null,
            'min_level' => $validated['min_level'],
            'require_previous_star' => $request->boolean('require_previous_star', true),
            'exp_multiplier' => $validated['exp_multiplier'],
            'coin_multiplier' => $validated['coin_multiplier'],
            'benefits' => $validated['benefits'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $validated['sort_order'] ?? $starPrice->stars,
        ]);

        return redirect()
            ->route('admin.star-upgrade.index')
            ->with('success', 'อัพเดทราคาดาวสำเร็จ');
    }

    /**
     * สลับสถานะเปิด/ปิด
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleActive(StarUpgradePrice $starPrice)
    {
        $starPrice->update([
            'is_active' => ! $starPrice->is_active,
        ]);

        $status = $starPrice->is_active ? 'เปิด' : 'ปิด';

        return back()->with('success', "{$status}การขายดาว {$starPrice->stars} ดาวสำเร็จ");
    }

    /**
     * หน้าประวัติการอัพเกรดทั้งหมด
     *
     * @return \Illuminate\View\View
     */
    public function history(Request $request)
    {
        $query = StarUpgradeHistory::with('user')
            ->latest();

        // กรองตามดาว
        if ($request->filled('stars')) {
            $query->where('to_stars', $request->stars);
        }

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // ค้นหาตาม user
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // กรองตามวันที่
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $history = $query->paginate(20);

        // สถิติ
        $stats = [
            'total' => StarUpgradeHistory::count(),
            'completed' => StarUpgradeHistory::where('status', 'completed')->count(),
            'refunded' => StarUpgradeHistory::where('status', 'refunded')->count(),
            'total_coins' => StarUpgradeHistory::where('status', 'completed')->sum('coins_paid'),
        ];

        return view('admin.star-upgrade.history', [
            'history' => $history,
            'stats' => $stats,
            'starColors' => StarUpgradePrice::STAR_COLORS,
            'currentStars' => $request->stars,
            'currentStatus' => $request->status,
            'search' => $request->search,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
            'pageTitle' => 'ประวัติการอัพเกรดดาว',
        ]);
    }

    /**
     * Refund การอัพเกรด
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refund(StarUpgradeHistory $upgrade)
    {
        if ($upgrade->status !== 'completed') {
            return back()->with('error', 'ไม่สามารถคืนเงินรายการนี้ได้');
        }

        // คืน Coins
        $user = $upgrade->user;
        $videoCoin = $user->videoCoin;
        if ($videoCoin) {
            $videoCoin->addCoins(
                $upgrade->coins_paid,
                'refund_star_upgrade',
                'StarUpgradeHistory',
                $upgrade->id,
                'คืนเงินอัพเกรดดาว: '.$upgrade->from_stars.' -> '.$upgrade->to_stars
            );
        }

        // อัพเดทสถานะ
        $upgrade->update([
            'status' => 'refunded',
            'note' => 'คืนเงินโดย Admin: '.auth()->user()->name.' เมื่อ '.now()->format('d/m/Y H:i'),
        ]);

        // Reset purchased_stars ของผู้ใช้
        $userVideoLevel = $user->videoLevel;
        if ($userVideoLevel && $userVideoLevel->purchased_stars === $upgrade->to_stars) {
            $userVideoLevel->update([
                'purchased_stars' => $upgrade->from_stars,
                'purchased_star_color' => $upgrade->from_stars > 0
                    ? StarUpgradePrice::getByStars($upgrade->from_stars)?->star_color
                    : null,
            ]);
        }

        return back()->with('success', 'คืนเงินสำเร็จ: '.number_format($upgrade->coins_paid, 2).' Coins');
    }
}
