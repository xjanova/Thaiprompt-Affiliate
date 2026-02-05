<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\VideoCoin;
use App\Models\VideoCoinTransaction;
use App\Models\CoinExchangeRate;
use App\Models\CoinExchangeRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * VideoCoinController - จัดการ Video Coins สำหรับ User
 *
 * ควบคุมการแสดงยอด coins, ประวัติการทำรายการ,
 * และการแลก coins เป็นเงินบาท
 */
class VideoCoinController extends Controller
{
    /**
     * แสดงหน้าหลัก Video Coins
     *
     * @return View
     */
    public function index(): View
    {
        $user = auth()->user();

        // ดึงข้อมูล coins หรือสร้างใหม่ถ้ายังไม่มี
        $videoCoin = VideoCoin::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'lifetime_earned' => 0,
                'lifetime_spent' => 0,
                'lifetime_exchanged' => 0,
            ]
        );

        // ดึงประวัติธุรกรรมล่าสุด
        $recentTransactions = VideoCoinTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // ดึงอัตราแลกเปลี่ยน
        $exchangeRates = CoinExchangeRate::where('is_active', true)
            ->orderBy('coins_amount')
            ->get();

        return view('user.video-coins.index', [
            'videoCoin' => $videoCoin,
            'recentTransactions' => $recentTransactions,
            'exchangeRates' => $exchangeRates,
            'pageTitle' => 'Video Coins',
        ]);
    }

    /**
     * ดึงยอด coins แบบ AJAX สำหรับ topbar
     *
     * @return JsonResponse
     */
    public function getBalanceAjax(): JsonResponse
    {
        $user = auth()->user();

        $videoCoin = VideoCoin::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'lifetime_earned' => 0,
                'lifetime_spent' => 0,
                'lifetime_exchanged' => 0,
            ]
        );

        return response()->json([
            'success' => true,
            'balance' => (float) $videoCoin->balance,
            'lifetime_earned' => (float) $videoCoin->lifetime_earned,
            'lifetime_spent' => (float) $videoCoin->lifetime_spent,
            'lifetime_exchanged' => (float) $videoCoin->lifetime_exchanged,
            'formatted_balance' => number_format($videoCoin->balance, 2),
        ]);
    }

    /**
     * แสดงประวัติธุรกรรม coins
     *
     * @param Request $request
     * @return View
     */
    public function transactions(Request $request): View
    {
        $user = auth()->user();

        $query = VideoCoinTransaction::where('user_id', $user->id);

        // กรองตามประเภท
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // กรองตามช่วงวันที่
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('user.video-coins.transactions', [
            'transactions' => $transactions,
            'pageTitle' => 'ประวัติธุรกรรม Coins',
        ]);
    }

    /**
     * แสดงหน้าแลก coins เป็นเงิน
     *
     * @return View
     */
    public function exchange(): View
    {
        $user = auth()->user();

        $videoCoin = VideoCoin::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        // ดึงอัตราแลกเปลี่ยนที่ active
        $exchangeRates = CoinExchangeRate::where('is_active', true)
            ->orderBy('coins_amount')
            ->get();

        // ดึงประวัติการแลก
        $exchangeHistory = CoinExchangeRequest::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('user.video-coins.exchange', [
            'videoCoin' => $videoCoin,
            'exchangeRates' => $exchangeRates,
            'exchangeHistory' => $exchangeHistory,
            'pageTitle' => 'แลก Coins เป็นเงิน',
        ]);
    }

    /**
     * ส่งคำขอแลก coins
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function submitExchange(Request $request)
    {
        $request->validate([
            'exchange_rate_id' => 'required|exists:coin_exchange_rates,id',
        ]);

        $user = auth()->user();
        $rate = CoinExchangeRate::findOrFail($request->exchange_rate_id);

        // ตรวจสอบยอด coins
        $videoCoin = VideoCoin::where('user_id', $user->id)->first();

        if (!$videoCoin || $videoCoin->balance < $rate->coins_amount) {
            return back()->with('error', 'ยอด Coins ไม่เพียงพอ');
        }

        // ตรวจสอบ level ขั้นต่ำ
        if ($rate->min_level && $user->level < $rate->min_level) {
            return back()->with('error', 'ต้องมี Level อย่างน้อย ' . $rate->min_level);
        }

        // ตรวจสอบ limit ต่อวัน/เดือน
        $todayExchanged = CoinExchangeRequest::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->where('status', '!=', 'rejected')
            ->sum('coins_amount');

        if ($rate->max_coins_per_day && ($todayExchanged + $rate->coins_amount) > $rate->max_coins_per_day) {
            return back()->with('error', 'เกินจำนวนที่สามารถแลกได้ต่อวัน');
        }

        // สร้างคำขอแลก
        CoinExchangeRequest::create([
            'user_id' => $user->id,
            'exchange_rate_id' => $rate->id,
            'coins_amount' => $rate->coins_amount,
            'money_amount' => $rate->money_amount,
            'status' => 'pending',
        ]);

        return redirect()
            ->route('user.video-coins.exchange')
            ->with('success', 'ส่งคำขอแลก Coins สำเร็จ กรุณารอการอนุมัติ');
    }
}
