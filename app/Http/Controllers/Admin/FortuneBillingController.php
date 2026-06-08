<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDeepFortuneReadingJob;
use App\Models\FortuneReading;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            // รองรับทั้ง Deep 39฿ และ Celtic Cross 99฿
            $query->where('is_paid', false)->whereIn('conversation_status', FortuneReading::PENDING_PAYMENT_STATUSES);
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function assignToUser(Request $request, FortuneReading $reading)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        if (! $reading->is_floating) {
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
     * เมื่อยืนยัน จะ:
     * 1. เปลี่ยนสถานะเป็น paid
     * 2. สร้างคำทำนายละเอียดด้วย AI (ถ้าเป็นบิลเชิงลึก)
     * 3. ส่งคำทำนายไป Messenger/LINE ให้ลูกค้าอัตโนมัติ
     *
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

        // อัพเดท amount_paid + sender_info ก่อน (processPaymentConfirmed จะเรียก confirmPayment เอง)
        $reading->update([
            'amount_paid' => $request->amount,
            'sender_info' => 'Manual: '.($request->note ?? 'Admin confirmed'),
        ]);

        // ประมวลผลคำทำนาย + ส่งข้อความ (ถ้าเป็นบิลเชิงลึกที่มีคำถาม)
        $deepReadingSent = false;
        $hasQuestions = ! empty($reading->getCollectedQuestions());

        if ($hasQuestions) {
            $userId = $reading->platform_user_id ?? $reading->facebook_user_id;
            $platform = $reading->platform ?: ((preg_match('/^U[0-9a-f]{32}$/i', $userId ?? '')) ? 'line' : 'facebook');

            if (! $userId) {
                return back()->with('error', 'ไม่พบ User ID — ไม่สามารถส่งข้อความได้');
            }

            // Dispatch background job → ไม่ติด web server timeout
            // Job จะ: confirmPayment → สร้าง chart → สร้างคำทำนาย 2 ข้อ → ส่ง Messenger → save DB
            ProcessDeepFortuneReadingJob::dispatchSmart(
                $reading->id, null, $platform, $userId
            );

            $deepReadingSent = true;

            Log::info('ManualConfirm: dispatch ProcessDeepFortuneReadingJob', [
                'reading_id' => $reading->id,
                'bill_reference' => $reading->bill_reference,
                'platform' => $platform,
            ]);
        } else {
            // ไม่มีคำถาม (บิลพื้นฐาน) → confirm payment + เปลี่ยนสถานะเป็นสิ้นสุดทันที
            // เพราะไม่มี deep reading ที่ต้องสร้าง จึงไม่ต้องรอ
            $reading->update([
                'is_paid' => true,
                'paid_at' => now(),
                'conversation_status' => FortuneReading::STATUS_COMPLETED,
            ]);
        }

        $successMessage = 'ยืนยันการชำระเงินสำเร็จ';
        if ($deepReadingSent) {
            $successMessage .= ' ✨ ส่งคำทำนายให้ลูกค้าแล้ว';
        }

        return back()->with('success', $successMessage);
    }

    /**
     * ยกเลิกบิล (Void)
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function void(FortuneReading $reading)
    {
        if (! $reading->is_paid) {
            return back()->with('error', 'ไม่สามารถยกเลิกบิลที่ยังไม่ได้ชำระ');
        }

        // 🚫 (2026-06-08) ใช้ engine กลาง FortuneReading::voidApproval() — reverse ครบทุกอย่าง
        //    เดิม flip is_paid อย่างเดียว → UPA ค้าง used + SMS ผูกค้าง + commission ไม่ถูกดึงคืน
        //    ใหม่: คืน UPA → cancelled, ปลด SMS notification, ดึงคืน commission, ปิดบิล + audit log
        $result = $reading->voidApproval('void จากหน้า billing', auth()->id());

        if (! ($result['ok'] ?? false)) {
            return back()->with('error', $result['message'] ?? 'ยกเลิกบิลไม่สำเร็จ');
        }

        $msg = 'ยกเลิกบิลสำเร็จ';
        if (! empty($result['warnings'])) {
            $msg .= ' ⚠️ '.implode('; ', $result['warnings']);
        }

        return back()->with('success', $msg);
    }

    /**
     * ส่งคำทำนายซ้ำ (Retry Fortune)
     *
     * สำหรับบิลที่ชำระเงินแล้วแต่ AI สร้างคำทำนายไม่สำเร็จ
     * จะลองสร้างคำทำนาย + ส่งข้อความใหม่อีกครั้ง
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function retryFortune(FortuneReading $reading)
    {
        if (! $reading->is_paid) {
            return back()->with('error', 'บิลนี้ยังไม่ได้ชำระเงิน');
        }

        $hasQuestions = ! empty($reading->getCollectedQuestions());
        if (! $hasQuestions) {
            return back()->with('error', 'บิลนี้ไม่มีคำถาม — ไม่สามารถสร้างคำทำนายได้');
        }

        $userId = $reading->platform_user_id ?? $reading->facebook_user_id;
        $platform = $reading->platform ?: ((preg_match('/^U[0-9a-f]{32}$/i', $userId ?? '')) ? 'line' : 'facebook');

        if (! $userId) {
            return back()->with('error', 'ไม่พบ User ID — ไม่สามารถส่งข้อความได้');
        }

        // Dispatch background job → ไม่ติด web server timeout
        // Job จะ: สร้าง chart → สร้างคำทำนาย 2 ข้อ → ส่ง Messenger → save DB
        ProcessDeepFortuneReadingJob::dispatchSmart(
            $reading->id, null, $platform, $userId
        );

        Log::info('RetryFortune: dispatch ProcessDeepFortuneReadingJob', [
            'reading_id' => $reading->id,
            'bill_reference' => $reading->bill_reference,
        ]);

        return back()->with('success', 'กำลังสร้างคำทำนายและส่งให้ลูกค้า... ⏳ (ใช้เวลาประมาณ 1-2 นาที)');
    }

    /**
     * Export รายงานรายได้เป็น CSV
     *
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

        $filename = 'fortune_revenue_'.$dateFrom.'_to_'.$dateTo.'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($readings) {
            $file = fopen('php://output', 'w');

            // BOM สำหรับ UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

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

        // รอชำระ (Deep 39฿ + Celtic Cross 99฿)
        $pendingCount = FortuneReading::whereIn('conversation_status', FortuneReading::PENDING_PAYMENT_STATUSES)
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

    /**
     * 💳 (2026-05-09) Refund Stripe payment
     *
     * Admin action — confirm dialog + reason field required
     * Body: amount (optional, null = full refund), reason (required)
     */
    public function stripeRefund(Request $request, FortuneReading $reading)
    {
        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0.01',
            'reason' => 'required|string|max:500',
        ]);

        if ($reading->payment_method !== FortuneReading::PAYMENT_METHOD_STRIPE) {
            return back()->withErrors(['error' => 'บิลนี้ไม่ได้ชำระผ่าน Stripe']);
        }

        if (empty($reading->stripe_payment_intent_id)) {
            return back()->withErrors(['error' => 'ไม่พบ Stripe payment_intent_id']);
        }

        $service = new \App\Services\Fortune\FortuneStripeService;
        $result = $service->refundPayment(
            $reading->stripe_payment_intent_id,
            $validated['amount'] ?? null,
            $validated['reason']
        );

        if (! ($result['success'] ?? false)) {
            return back()->withErrors(['error' => 'Refund ล้มเหลว: '.($result['error'] ?? 'unknown')]);
        }

        Log::info('FortuneBilling: Stripe refund processed by admin', [
            'reading_id' => $reading->id,
            'admin_id' => auth()->id(),
            'refund_id' => $result['refund_id'] ?? null,
            'amount' => $result['amount'] ?? null,
            'reason' => $validated['reason'],
        ]);

        return back()->with('success', "Refund สำเร็จ: {$result['amount']} บาท (refund_id: {$result['refund_id']})");
    }

    /**
     * 💳 (2026-05-09) Expire Stripe Checkout Session ที่ยังไม่จ่าย
     *
     * Use case: ลูกค้ายังไม่จ่าย แต่ admin อยากยกเลิกบิลก่อนที่ลูกค้าจะจ่าย
     */
    public function stripeExpire(FortuneReading $reading)
    {
        if ($reading->payment_method !== FortuneReading::PAYMENT_METHOD_STRIPE) {
            return back()->withErrors(['error' => 'บิลนี้ไม่ได้ใช้ Stripe']);
        }

        if ($reading->is_paid) {
            return back()->withErrors(['error' => 'บิลจ่ายแล้ว — ใช้ refund แทน']);
        }

        if (empty($reading->stripe_session_id)) {
            return back()->withErrors(['error' => 'ไม่พบ Stripe session_id']);
        }

        $service = new \App\Services\Fortune\FortuneStripeService;
        $ok = $service->expireSession($reading->stripe_session_id);

        if (! $ok) {
            return back()->withErrors(['error' => 'Expire session ล้มเหลว']);
        }

        // Revert state
        $reading->update([
            'conversation_status' => FortuneReading::STATUS_AWAITING_PAYMENT_METHOD,
        ]);

        Log::info('FortuneBilling: Stripe session expired by admin', [
            'reading_id' => $reading->id,
            'admin_id' => auth()->id(),
        ]);

        return back()->with('success', 'Expire session สำเร็จ');
    }

    /**
     * 💳 (2026-05-09) Resync จาก Stripe API
     *
     * Use case: webhook ตก / ลูกค้าจ่ายแล้วแต่ระบบยังไม่ update
     * → ดึง session status จาก Stripe ตรง → trigger flow ถ้า paid
     */
    public function stripeResync(FortuneReading $reading)
    {
        if ($reading->payment_method !== FortuneReading::PAYMENT_METHOD_STRIPE) {
            return back()->withErrors(['error' => 'บิลนี้ไม่ได้ใช้ Stripe']);
        }

        if (empty($reading->stripe_session_id)) {
            return back()->withErrors(['error' => 'ไม่พบ Stripe session_id']);
        }

        $service = new \App\Services\Fortune\FortuneStripeService;
        $session = $service->retrieveSession($reading->stripe_session_id);

        if (! $session) {
            return back()->withErrors(['error' => 'ดึง session จาก Stripe ไม่ได้']);
        }

        // ถ้า paid + ยังไม่ trigger → trigger เลย
        $paymentStatus = $session['payment_status'] ?? '';
        if ($paymentStatus === 'paid' && ! $reading->is_paid) {
            $event = ['type' => 'checkout.session.completed', 'data' => ['object' => $session]];
            $result = $service->handleWebhookEvent($event);

            if (($result['action'] ?? '') === 'paid') {
                // Trigger fortune flow
                try {
                    if ($reading->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS) {
                        $smsService = app(\App\Services\SmsPaymentService::class);
                        $smsService->handleCelticPaymentMatched(
                            $reading->fresh(),
                            null,
                            $reading->platform ?? 'facebook',
                            $reading->facebook_user_id ?? '',
                            (float) $reading->amount_paid
                        );
                    } else {
                        // 🐛 (self-review fix) ใช้ dispatchSmart() ส่ง args ครบ 4 ตัว
                        $platform = $reading->platform ?? 'facebook';
                        $userId = $reading->facebook_user_id ?? $reading->platform_user_id ?? '';
                        ProcessDeepFortuneReadingJob::dispatchSmart($reading->id, null, $platform, $userId);
                    }
                } catch (\Throwable $e) {
                    Log::error('FortuneBilling: Stripe resync trigger failed', [
                        'reading_id' => $reading->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return back()->with('success', "Resync สำเร็จ — payment_status: {$paymentStatus}, session_status: ".($session['status'] ?? 'unknown'));
    }
}
