<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CoinExchangeRate;
use App\Models\CoinExchangeRequest;
use App\Models\UserVideoLevel;
use App\Models\VideoCoin;
use App\Models\VideoCoinTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // สรุปวันนี้ (ตาราง video_coins ไม่มีคอลัมน์ earned_today/spent_today — เดิมหน้าแสดง 0 เสมอ)
        // รายการหัก (แลกเงิน/ซื้อของ) เก็บ amount ติดลบ · รายการได้รับ/คืนเหรียญเก็บบวก
        $todayTotals = VideoCoinTransaction::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->selectRaw('COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END), 0) AS earned, COALESCE(SUM(CASE WHEN amount < 0 THEN -amount ELSE 0 END), 0) AS spent')
            ->first();

        return view('user.video-coins.index', [
            'videoCoin' => $videoCoin,
            'earnedToday' => (float) ($todayTotals->earned ?? 0),
            'spentToday' => (float) ($todayTotals->spent ?? 0),
            'recentTransactions' => $recentTransactions,
            'exchangeRates' => $exchangeRates,
            'pageTitle' => 'Video Coins',
        ]);
    }

    /**
     * ดึงยอด coins แบบ AJAX สำหรับ topbar
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
     */
    public function transactions(Request $request): View
    {
        $user = auth()->user();

        $query = VideoCoinTransaction::where('user_id', $user->id);

        // กรองตามประเภท
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // กรองตามช่วงวันที่ — รับเฉพาะรูปแบบ YYYY-MM-DD (ค่าแปลก ๆ จาก URL ไม่ส่งต่อให้ฐานข้อมูล)
        $dateFrom = $this->validDate($request->query('date_from'));
        $dateTo = $this->validDate($request->query('date_to'));
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
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
     * ใช้ขั้นตอนเดียวกับ API (Api\CoinExchangeController::exchange):
     * หัก coins ทันทีตอนส่งคำขอ (แอดมินปฏิเสธ = คืน coins ใน CoinExchangeRequest::reject)
     * ทั้งหมดอยู่ใน transaction + ล็อกแถว video_coins กันกดซ้ำ/ส่งพร้อมกันหลายแท็บ
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function submitExchange(Request $request)
    {
        $request->validate([
            'exchange_rate_id' => 'required|integer|exists:coin_exchange_rates,id',
        ], [
            'exchange_rate_id.required' => 'กรุณาเลือกแพคเกจที่ต้องการแลก',
            'exchange_rate_id.integer' => 'แพคเกจที่เลือกไม่ถูกต้อง',
            'exchange_rate_id.exists' => 'ไม่พบแพคเกจที่เลือก',
        ]);

        $user = auth()->user();
        $rate = CoinExchangeRate::where('is_active', true)->find($request->exchange_rate_id);

        if (! $rate) {
            return back()->with('error', 'แพคเกจนี้ปิดการแลกแล้ว กรุณาเลือกแพคเกจอื่น');
        }

        // ตรวจสอบ level ขั้นต่ำ — ใช้เลเวลจากระบบดูวิดีโอ (user_video_levels) แบบเดียวกับ API
        // (ตาราง users ไม่มีคอลัมน์ level — เดิมอ่าน $user->level ได้ null จึงโดนปฏิเสธทุกครั้ง)
        $userLevel = (int) (UserVideoLevel::with('currentLevel')->where('user_id', $user->id)->first()?->currentLevel?->level ?? 0);
        if ($rate->min_level && $userLevel < (int) $rate->min_level) {
            return back()->with('error', 'ต้องมี Level อย่างน้อย '.$rate->min_level);
        }

        $error = DB::transaction(function () use ($user, $rate) {
            // ล็อกยอด coins ของผู้ใช้จนจบ transaction
            $videoCoin = VideoCoin::where('user_id', $user->id)->lockForUpdate()->first();

            if (! $videoCoin || $videoCoin->balance < $rate->coins_amount) {
                return 'ยอด Coins ไม่เพียงพอ';
            }

            // ตรวจสอบ limit ต่อวัน (นับคำขอที่ยังไม่ถูกปฏิเสธ)
            $todayExchanged = CoinExchangeRequest::where('user_id', $user->id)
                ->whereDate('created_at', today())
                ->where('status', '!=', 'rejected')
                ->sum('coins_amount');

            if ($rate->max_coins_per_day && ($todayExchanged + $rate->coins_amount) > $rate->max_coins_per_day) {
                return 'เกินจำนวนที่สามารถแลกได้ต่อวัน';
            }

            // หัก coins แล้วสร้างคำขอ (exchange_rate เป็นคอลัมน์ NOT NULL — เดิมไม่ได้ใส่ ทำให้บันทึกไม่ได้)
            $videoCoin->exchangeToMoney($rate->coins_amount, $rate->id);

            CoinExchangeRequest::create([
                'user_id' => $user->id,
                'exchange_rate_id' => $rate->id,
                'coins_amount' => $rate->coins_amount,
                'money_amount' => $rate->money_amount,
                'exchange_rate' => $rate->rate,
                'status' => 'pending',
            ]);

            return null;
        });

        if ($error !== null) {
            return back()->with('error', $error);
        }

        return redirect()
            ->route('user.video-coins.exchange')
            ->with('success', 'ส่งคำขอแลก Coins สำเร็จ หัก Coins แล้ว กรุณารอการอนุมัติ');
    }

    /**
     * คืนวันที่รูปแบบ YYYY-MM-DD ที่ถูกต้อง หรือ null
     */
    private function validDate(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $value));

        return checkdate($m, $d, $y) ? $value : null;
    }
}
