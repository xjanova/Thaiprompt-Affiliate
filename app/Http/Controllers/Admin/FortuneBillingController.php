<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneReading;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * Fortune Billing Controller
 *
 * จัดการบิลและรายได้จากระบบดูดวง
 * - แสดงสถิติรายได้ (รายวัน/เดือน/ทั้งหมด)
 * - จัดการบิลลอย (floating bills)
 * - Assign บิลลอยให้ผู้ใช้
 */
class FortuneBillingController extends Controller
{
    /**
     * แสดง Dashboard บิลดูดวง + สถิติรายได้
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // กรองตามช่วงวันที่
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $status = $request->input('status'); // all, paid, pending, floating

        // Query พื้นฐาน
        $query = FortuneReading::query()
            ->with(['user', 'smsNotification'])
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        // กรองตามสถานะ
        if ($status === 'paid') {
            $query->where('is_paid', true)->where('is_floating', false);
        } elseif ($status === 'pending') {
            $query->where('is_paid', false)->where('conversation_status', FortuneReading::STATUS_PENDING_PAYMENT);
        } elseif ($status === 'floating') {
            $query->where('is_floating', true);
        } elseif ($status === 'free') {
            $query->where('is_paid', false)->where('reading_type', 'basic');
        }

        $bills = $query->orderBy('created_at', 'desc')->paginate(20);

        // สถิติรายได้
        $stats = $this->calculateStats($dateFrom, $dateTo);

        // รายได้รายวัน (7 วันล่าสุด)
        $dailyRevenue = $this->getDailyRevenue(7);

        return view('admin.fortune.billing.index', [
            'bills' => $bills,
            'stats' => $stats,
            'dailyRevenue' => $dailyRevenue,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'status' => $status,
            'pageTitle' => 'จัดการบิลดูดวง',
        ]);
    }

    /**
     * แสดงหน้าจัดการบิลลอย (floating bills)
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function floatingBills(Request $request)
    {
        $query = FortuneReading::query()
            ->with('smsNotification')
            ->where('is_floating', true)
            ->orderBy('paid_at', 'desc');

        // ค้นหาตาม sender info
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('sender_info', 'like', "%{$search}%")
                    ->orWhere('sender_bank', 'like', "%{$search}%")
                    ->orWhere('facebook_user_name', 'like', "%{$search}%");
            });
        }

        $floatingBills = $query->paginate(20);

        // รายชื่อ users สำหรับ assign
        $users = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->limit(500)
            ->get();

        // สถิติบิลลอย
        $floatingStats = [
            'total_count' => FortuneReading::where('is_floating', true)->count(),
            'total_amount' => FortuneReading::where('is_floating', true)->sum('amount_paid'),
            'today_count' => FortuneReading::where('is_floating', true)->whereDate('paid_at', today())->count(),
            'today_amount' => FortuneReading::where('is_floating', true)->whereDate('paid_at', today())->sum('amount_paid'),
        ];

        return view('admin.fortune.billing.floating-bills', [
            'floatingBills' => $floatingBills,
            'users' => $users,
            'stats' => $floatingStats,
            'pageTitle' => 'จัดการบิลลอย',
        ]);
    }

    /**
     * Assign บิลลอยให้ผู้ใช้
     *
     * @param Request $request
     * @param FortuneReading $reading
     * @return \Illuminate\Http\RedirectResponse
     */
    public function assignToUser(Request $request, FortuneReading $reading)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        if (!$reading->is_floating) {
            return back()->with('error', 'บิลนี้ไม่ใช่บิลลอย');
        }

        $user = User::findOrFail($request->user_id);

        $reading->update([
            'user_id' => $user->id,
            'is_floating' => false,
        ]);

        return back()->with('success', "Assign บิลให้ {$user->name} สำเร็จ");
    }

    /**
     * ยืนยันการชำระเงินด้วยตนเอง (Manual confirm)
     *
     * @param Request $request
     * @param FortuneReading $reading
     * @return \Illuminate\Http\RedirectResponse
     */
    public function manualConfirm(Request $request, FortuneReading $reading)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:500',
        ]);

        if ($reading->is_paid) {
            return back()->with('error', 'บิลนี้ชำระเงินแล้ว');
        }

        $reading->update([
            'is_paid' => true,
            'amount_paid' => $request->amount,
            'paid_at' => now(),
            'conversation_status' => FortuneReading::STATUS_PAID,
            'sender_info' => 'Manual: ' . ($request->note ?? 'Admin confirmed'),
        ]);

        return back()->with('success', 'ยืนยันการชำระเงินสำเร็จ');
    }

    /**
     * ยกเลิกบิล (Void)
     *
     * @param FortuneReading $reading
     * @return \Illuminate\Http\RedirectResponse
     */
    public function void(FortuneReading $reading)
    {
        if (!$reading->is_paid) {
            return back()->with('error', 'ไม่สามารถยกเลิกบิลที่ยังไม่ได้ชำระ');
        }

        $reading->update([
            'is_paid' => false,
            'amount_paid' => 0,
            'paid_at' => null,
            'conversation_status' => FortuneReading::STATUS_BASIC_DONE,
            'sender_info' => 'Voided at ' . now()->format('Y-m-d H:i:s'),
        ]);

        return back()->with('success', 'ยกเลิกบิลสำเร็จ');
    }

    /**
     * Export รายงานรายได้เป็น CSV
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportRevenue(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $readings = FortuneReading::with(['user', 'smsNotification'])
            ->where('is_paid', true)
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->orderBy('paid_at', 'desc')
            ->get();

        $filename = 'fortune_revenue_' . $dateFrom . '_to_' . $dateTo . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($readings) {
            $file = fopen('php://output', 'w');

            // BOM สำหรับ UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header
            fputcsv($file, [
                'ID',
                'วันที่ชำระ',
                'ชื่อผู้ใช้',
                'Facebook ID',
                'จำนวนเงิน',
                'ประเภท',
                'ธนาคาร',
                'ผู้โอน',
                'บิลลอย',
            ]);

            // Data
            foreach ($readings as $reading) {
                fputcsv($file, [
                    $reading->id,
                    $reading->paid_at?->format('Y-m-d H:i:s'),
                    $reading->facebook_user_name ?? $reading->user?->name ?? '-',
                    $reading->facebook_user_id,
                    number_format($reading->amount_paid, 2),
                    $reading->reading_type === 'deep' ? 'เชิงลึก' : 'พื้นฐาน',
                    $reading->sender_bank ?? '-',
                    $reading->sender_info ?? '-',
                    $reading->is_floating ? 'ใช่' : 'ไม่ใช่',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * API: ดึงสถิติรายได้
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function statsApi(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $stats = $this->calculateStats($dateFrom, $dateTo);
        $dailyRevenue = $this->getDailyRevenue(30);

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'daily_revenue' => $dailyRevenue,
        ]);
    }

    /**
     * คำนวณสถิติรายได้
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    protected function calculateStats(string $dateFrom, string $dateTo): array
    {
        // รายได้ในช่วงที่เลือก
        $periodRevenue = FortuneReading::where('is_paid', true)
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->sum('amount_paid');

        $periodCount = FortuneReading::where('is_paid', true)
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->count();

        // รายได้วันนี้
        $todayRevenue = FortuneReading::where('is_paid', true)
            ->whereDate('paid_at', today())
            ->sum('amount_paid');

        $todayCount = FortuneReading::where('is_paid', true)
            ->whereDate('paid_at', today())
            ->count();

        // รายได้เดือนนี้
        $monthRevenue = FortuneReading::where('is_paid', true)
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount_paid');

        $monthCount = FortuneReading::where('is_paid', true)
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->count();

        // รายได้ทั้งหมด
        $totalRevenue = FortuneReading::where('is_paid', true)->sum('amount_paid');
        $totalCount = FortuneReading::where('is_paid', true)->count();

        // บิลลอย
        $floatingCount = FortuneReading::where('is_floating', true)->count();
        $floatingAmount = FortuneReading::where('is_floating', true)->sum('amount_paid');

        // รอชำระ
        $pendingCount = FortuneReading::where('conversation_status', FortuneReading::STATUS_PENDING_PAYMENT)
            ->where('is_paid', false)
            ->count();

        // อัตราส่วน paid vs free
        $totalReadings = FortuneReading::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->count();
        $paidReadings = FortuneReading::where('is_paid', true)
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->count();
        $conversionRate = $totalReadings > 0 ? round(($paidReadings / $totalReadings) * 100, 1) : 0;

        // เฉลี่ยต่อบิล
        $avgPerBill = $periodCount > 0 ? round($periodRevenue / $periodCount, 2) : 0;

        return [
            'period' => [
                'revenue' => $periodRevenue,
                'count' => $periodCount,
                'avg_per_bill' => $avgPerBill,
            ],
            'today' => [
                'revenue' => $todayRevenue,
                'count' => $todayCount,
            ],
            'month' => [
                'revenue' => $monthRevenue,
                'count' => $monthCount,
            ],
            'total' => [
                'revenue' => $totalRevenue,
                'count' => $totalCount,
            ],
            'floating' => [
                'count' => $floatingCount,
                'amount' => $floatingAmount,
            ],
            'pending' => [
                'count' => $pendingCount,
            ],
            'conversion_rate' => $conversionRate,
            'total_readings_period' => $totalReadings,
        ];
    }

    /**
     * ดึงรายได้รายวัน
     *
     * @param int $days
     * @return array
     */
    protected function getDailyRevenue(int $days): array
    {
        $result = [];
        $startDate = now()->subDays($days - 1)->startOfDay();

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dateStr = $date->format('Y-m-d');

            $revenue = FortuneReading::where('is_paid', true)
                ->whereDate('paid_at', $dateStr)
                ->sum('amount_paid');

            $count = FortuneReading::where('is_paid', true)
                ->whereDate('paid_at', $dateStr)
                ->count();

            $result[] = [
                'date' => $dateStr,
                'date_label' => $date->format('d M'),
                'revenue' => (float) $revenue,
                'count' => $count,
            ];
        }

        return $result;
    }
}
