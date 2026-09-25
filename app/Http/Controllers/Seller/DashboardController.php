<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\EarningsLedger;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Slogan;
use App\Models\VendorStore;
use App\Models\WalletSetting;
use App\Models\WithdrawalRequest;
use App\Rules\NotReservedEmailDomain;
use App\Services\ImageUploadService;
use App\Services\SellerPayoutService;
use App\Services\WalletService;
use App\Services\WithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class DashboardController extends Controller
{
    /**
     * แสดง Dashboard หลักของ Seller
     *
     * หมายเหตุ: Route นี้ถูกป้องกันด้วย middleware kyc.verified และ has.vendor.store
     * ดังนั้น user จะมี store ที่ active แน่นอนเมื่อเข้ามาถึงตรงนี้
     */
    public function index()
    {
        $user = Auth::user();
        $sellerId = $user->id;

        // ดึง vendor store ที่มีอยู่ (middleware รับประกันว่าจะมี)
        $store = VendorStore::where('user_id', $sellerId)->first();

        // ถ้าไม่มี store (ไม่ควรเกิดขึ้นเพราะมี middleware) ให้ redirect ไป onboarding
        if (! $store) {
            return redirect()->route('seller.onboarding.index')
                ->with('info', 'กรุณาตั้งค่าร้านค้าของคุณก่อน');
        }

        // Get package information
        $package = $store->package;

        // Sales statistics
        $totalSales = OrderItem::where('seller_id', $sellerId)->count();
        $pendingSales = OrderItem::where('seller_id', $sellerId)
            ->whereIn('status', ['pending', 'processing'])
            ->count();
        $completedSales = OrderItem::where('seller_id', $sellerId)
            ->where('status', 'completed')
            ->count();

        // Revenue statistics
        $totalRevenue = OrderItem::where('seller_id', $sellerId)
            ->whereHas('order', function ($q) {
                $q->where('payment_status', 'paid');
            })
            ->sum('seller_earning');

        // Today's revenue
        $todayRevenue = OrderItem::where('seller_id', $sellerId)
            ->whereHas('order', function ($q) {
                $q->where('payment_status', 'paid')
                    ->whereDate('created_at', today());
            })
            ->sum('seller_earning');

        // Previous month revenue for growth calculation
        $currentMonthRevenue = OrderItem::where('seller_id', $sellerId)
            ->whereHas('order', function ($q) {
                $q->where('payment_status', 'paid')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
            })
            ->sum('seller_earning');

        $previousMonthRevenue = OrderItem::where('seller_id', $sellerId)
            ->whereHas('order', function ($q) {
                $q->where('payment_status', 'paid')
                    ->whereMonth('created_at', now()->subMonth()->month)
                    ->whereYear('created_at', now()->subMonth()->year);
            })
            ->sum('seller_earning');

        $salesGrowth = 0;
        if ($previousMonthRevenue > 0) {
            $salesGrowth = (($currentMonthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100;
        }

        // Monthly revenue for the last 12 months
        $monthlyRevenue = collect();
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $revenue = OrderItem::where('seller_id', $sellerId)
                ->whereHas('order', function ($q) use ($date) {
                    $q->where('payment_status', 'paid')
                        ->whereMonth('created_at', $date->month)
                        ->whereYear('created_at', $date->year);
                })
                ->sum('seller_earning');

            $monthlyRevenue->push([
                'month' => $date->format('M Y'),
                'total' => $revenue,
            ]);
        }

        // Product stats
        $totalProducts = Product::where('seller_id', $sellerId)->count();
        $activeProducts = Product::where('seller_id', $sellerId)->where('is_active', true)->count();
        $outOfStockProducts = Product::where('seller_id', $sellerId)->outOfStock()->count();
        $lowStockProducts = Product::where('seller_id', $sellerId)->lowStock()->count();

        // Recent orders
        $recentOrders = Order::with(['items' => function ($q) use ($sellerId) {
            $q->where('seller_id', $sellerId);
        }, 'user'])
            ->whereHas('items', function ($q) use ($sellerId) {
                $q->where('seller_id', $sellerId);
            })
            ->latest()
            ->take(10)
            ->get();

        // Top selling products
        $topProducts = Product::where('seller_id', $sellerId)
            ->orderBy('sales_count', 'desc')
            ->take(5)
            ->get();

        // Store visitors (last 30 days) - Mock data for now
        $totalVisitors = rand(500, 5000);
        $conversionRate = $totalSales > 0 ? ($completedSales / $totalSales) * 100 : 0;

        // ดึงคำขวัญสำหรับ Seller Dashboard (สุ่ม 5 คำ)
        $slogans = Slogan::getForSellerDashboard(5);

        return view('seller.dashboard', compact(
            'user',
            'store',
            'package',
            'totalSales',
            'pendingSales',
            'completedSales',
            'totalRevenue',
            'todayRevenue',
            'monthlyRevenue',
            'salesGrowth',
            'totalProducts',
            'activeProducts',
            'outOfStockProducts',
            'lowStockProducts',
            'recentOrders',
            'topProducts',
            'totalVisitors',
            'conversionRate',
            'slogans'
        ));
    }

    /**
     * Display seller analytics
     * Note: This method is deprecated. Use AnalyticsController::index instead.
     * Redirects to the proper analytics route.
     */
    public function analytics(Request $request)
    {
        // Redirect to the proper analytics controller
        return redirect()->route('seller.analytics.index', $request->all());
    }

    /**
     * Display seller marketing
     */
    public function marketing()
    {
        $user = Auth::user();

        return view('seller.marketing', compact('user'));
    }

    /**
     * Display seller profile
     */
    public function profile()
    {
        $user = Auth::user();

        return view('seller.profile', compact('user'));
    }

    /**
     * อัพเดทโปรไฟล์ผู้ขาย
     *
     * รองรับการอัพโหลด avatar และข้อมูลส่วนตัว
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProfile(Request $request, ImageUploadService $imageUploadService)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id, new NotReservedEmailDomain],
            'phone' => ['nullable', 'string', 'max:20'],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:5120'], // 5MB max
            'current_password' => ['nullable', 'string'],
            'new_password' => ['nullable', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
        ]);

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            // ลบรูปเดิมถ้ามี
            if ($user->profile_picture) {
                $imageUploadService->deleteImage($user->profile_picture);
            }

            // อัพโหลดรูปใหม่ (แปลงเป็น WebP อัตโนมัติ)
            $validated['profile_picture'] = $imageUploadService->uploadImage(
                $request->file('profile_picture'),
                'avatars',
                800,
                800,
                90
            );
        }

        // Handle password change
        if ($request->filled('current_password') && $request->filled('new_password')) {
            if (! Hash::check($request->current_password, $user->password)) {
                return back()->with('error', 'รหัสผ่านปัจจุบันไม่ถูกต้อง');
            }

            if (Hash::check($request->new_password, $user->password)) {
                return back()->with('error', 'รหัสผ่านใหม่ต้องไม่เหมือนกับรหัสผ่านเดิม');
            }

            $validated['password'] = Hash::make($request->new_password);
        }

        // Remove password fields from validated array
        unset($validated['current_password'], $validated['new_password']);

        $user->update($validated);

        return redirect()->route('seller.profile')->with('success', 'อัพเดทโปรไฟล์สำเร็จ');
    }

    /**
     * Display seller commissions
     */
    public function commissions()
    {
        $user = Auth::user();
        $sellerId = $user->id;

        // Get commissions data (placeholder for now)
        $commissions = [];
        $totalCommissions = 0;
        $pendingCommissions = 0;
        $paidCommissions = 0;

        return view('seller.commissions', compact(
            'user',
            'commissions',
            'totalCommissions',
            'pendingCommissions',
            'paidCommissions'
        ));
    }

    /**
     * Display sales reports
     */
    public function salesReport()
    {
        $user = Auth::user();
        $sellerId = $user->id;

        // Get sales report data
        $salesData = OrderItem::where('seller_id', $sellerId)
            ->whereHas('order', function ($q) {
                $q->where('payment_status', 'paid');
            })
            ->with('order', 'product')
            ->latest()
            ->paginate(20);

        return view('seller.reports.sales', compact('user', 'salesData'));
    }

    /**
     * แสดงหน้ากระเป๋าเงินของร้านค้า
     *
     * นอกจากยอดในกระเป๋า แสดงรายได้จากการขายที่ "รอโอน" (EarningsLedger) ด้วย —
     * รายได้เข้ากระเป๋าอัตโนมัติหลังลูกค้าได้รับของ + ครบระยะพักเงิน (SellerPayoutService)
     */
    public function walletIndex()
    {
        $user = Auth::user();
        $walletService = app(WalletService::class);

        // ดึงข้อมูลกระเป๋าเงิน
        $wallet = $walletService->getOrCreateWallet($user);
        $balance = $wallet->balance;

        // ดึงธุรกรรมล่าสุด
        $transactions = $wallet->transactions()
            ->latest()
            ->take(10)
            ->get();

        // ดึงคำขอถอนเงินที่รอดำเนินการ
        $pendingWithdrawals = WithdrawalRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->sum('amount');

        // รายได้จากการขาย (รอโอน / โอนแล้ว)
        $earningsSummary = app(SellerPayoutService::class)->summaryForSeller((int) $user->id);
        $recentEarnings = EarningsLedger::where('user_id', $user->id)
            ->where('earning_type', EarningsLedger::TYPE_SELLER_SALE)
            ->latest('id')
            ->take(10)
            ->get();

        return view('seller.wallet.index', compact(
            'user',
            'balance',
            'transactions',
            'pendingWithdrawals',
            'earningsSummary',
            'recentEarnings'
        ));
    }

    /**
     * แสดงหน้าถอนเงินของร้านค้า
     */
    public function walletWithdraw()
    {
        $user = Auth::user();
        $walletService = app(WalletService::class);

        // ดึงข้อมูลกระเป๋าเงิน
        $wallet = $walletService->getOrCreateWallet($user);
        $balance = $wallet->balance;

        // ดึงช่องทางรับเงินของผู้ใช้
        $paymentMethods = $user->paymentMethods()->active()->get();

        // ดึงคำขอถอนเงินล่าสุด
        $recentWithdrawals = WithdrawalRequest::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        // ค่าธรรมเนียมและขั้นต่ำในการถอน (ค่าเดียวกับที่ WithdrawalService ใช้ตรวจจริง)
        $withdrawalSettings = [
            'min_amount' => (float) WalletSetting::get('withdrawal_min_amount', 0),
            'max_amount' => (float) WalletSetting::get('withdrawal_max_amount', 999999999),
            'fee_type' => (string) WalletSetting::get('withdrawal_fee_type', 'percentage'),
            'fee_amount' => (float) WalletSetting::get('withdrawal_fee_amount', 0),
            'requires_pin' => $wallet->hasPIN(),
            'kyc_verified' => $user->isKycVerified(),
        ];

        return view('seller.wallet.withdraw', compact(
            'user',
            'wallet',
            'balance',
            'paymentMethods',
            'recentWithdrawals',
            'withdrawalSettings'
        ));
    }

    /**
     * ส่งคำขอถอนเงินของร้านค้า
     */
    /**
     * ส่งคำขอถอนเงินของร้านค้า
     *
     * 🐛 (2026-09-25) audit SELLER-02: เดิมเรียก WalletService::debit() ซึ่งไม่มีอยู่จริง → 500 ทุกครั้ง
     *    และเช็คยอดนอก lock → กดซ้ำถอนเกินได้ ตอนนี้ใช้ WithdrawalService ตัวเดียวกับสมาชิก
     *    (ตรวจ KYC, ขั้นต่ำ/สูงสุด, ค่าธรรมเนียม, PIN, lock กระเป๋าตอนหักเงิน, แจ้งแอดมิน)
     */
    public function submitWithdrawal(Request $request)
    {
        $user = Auth::user();
        $wallet = app(WalletService::class)->getOrCreateWallet($user);

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('payment_methods', 'id')
                    ->where('user_id', $user->id)
                    ->where('is_active', true)
                    ->whereNull('deleted_at'),
            ],
            'user_note' => 'nullable|string|max:500',
            'pin' => $wallet->hasPIN() ? 'required|string|max:20' : 'nullable|string|max:20',
        ], [
            'amount.required' => 'กรุณาระบุจำนวนเงิน',
            'amount.numeric' => 'จำนวนเงินต้องเป็นตัวเลข',
            'amount.min' => 'จำนวนเงินต้องมากกว่า 0',
            'payment_method_id.required' => 'กรุณาเลือกช่องทางรับเงิน',
            'payment_method_id.exists' => 'ไม่พบช่องทางรับเงินที่เลือก',
            'pin.required' => 'กรุณากรอก PIN กระเป๋าเงิน',
        ]);

        $amount = round((float) $request->input('amount'), 2);

        // กันกดส่งซ้ำ: คำขอเดียวกันภายใน 10 วินาที
        $lock = \Illuminate\Support\Facades\Cache::lock('seller-withdraw:'.$user->id, 10);
        if (! $lock->get()) {
            return back()->withErrors(['amount' => 'กำลังส่งคำขอถอนเงินก่อนหน้า กรุณารอสักครู่'])->withInput();
        }

        try {
            $withdrawal = app(WithdrawalService::class)->createWithdrawalRequest(
                $user,
                $amount,
                (int) $request->input('payment_method_id'),
                $request->input('user_note'),
                $request->input('pin')
            );

            $withdrawal->update([
                'metadata' => array_merge($withdrawal->metadata ?? [], [
                    'source' => 'seller_dashboard',
                    'store_id' => VendorStore::where('user_id', $user->id)->value('id'),
                ]),
            ]);

            return redirect()
                ->route('seller.wallet.withdrawals')
                ->with('success', 'ส่งคำขอถอนเงินสำเร็จ รหัสคำขอ '.$withdrawal->request_id.' กรุณารอการอนุมัติ');
        } catch (\Throwable $e) {
            return back()->withErrors(['amount' => $this->withdrawErrorMessage($e)])->withInput();
        } finally {
            $lock->release();
        }
    }

    /**
     * แสดงประวัติการถอนเงิน
     */
    public function withdrawals()
    {
        $user = Auth::user();

        $withdrawals = WithdrawalRequest::where('user_id', $user->id)
            ->with('paymentMethod')
            ->latest()
            ->paginate(15);

        return view('seller.wallet.withdrawals', compact('user', 'withdrawals'));
    }

    /**
     * ยกเลิกคำขอถอนเงิน
     */
    public function cancelWithdrawal($id)
    {
        $user = Auth::user();

        $withdrawal = WithdrawalRequest::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        try {
            // WithdrawalService lock แถวคำขอ + ตรวจสถานะซ้ำ → กดยกเลิกซ้ำคืนเงินครั้งเดียว
            app(WithdrawalService::class)->cancelWithdrawal($withdrawal, $user);

            return redirect()
                ->route('seller.wallet.withdrawals')
                ->with('success', 'ยกเลิกคำขอถอนเงินสำเร็จ เงินคืนเข้ากระเป๋าแล้ว');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Seller cancel withdrawal failed', [
                'withdrawal_id' => $withdrawal->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'ยกเลิกคำขอถอนเงินไม่ได้ คำขออาจถูกดำเนินการไปแล้ว']);
        }
    }

    /**
     * แปลงข้อผิดพลาดการถอนเงินเป็นข้อความไทยที่ปลอดภัย (ไม่ส่งข้อความระบบดิบให้ผู้ใช้)
     */
    private function withdrawErrorMessage(\Throwable $e): string
    {
        $message = $e->getMessage();

        // ข้อความจาก WithdrawalService/WalletSetting เป็นภาษาไทยที่ตั้งใจแสดงผู้ใช้อยู่แล้ว
        if (preg_match('/\p{Thai}/u', $message) && ! str_contains($message, 'SQLSTATE')) {
            return $message;
        }

        \Illuminate\Support\Facades\Log::error('Seller withdrawal failed', ['error' => $message]);

        return match ($message) {
            'Insufficient balance' => 'ยอดเงินในกระเป๋าไม่เพียงพอ',
            'Invalid PIN' => 'PIN ไม่ถูกต้อง กรุณาลองใหม่',
            'Wallet is not active' => 'กระเป๋าเงินของคุณถูกระงับชั่วคราว กรุณาติดต่อผู้ดูแล',
            default => 'ส่งคำขอถอนเงินไม่สำเร็จ กรุณาลองใหม่อีกครั้ง',
        };
    }

    /**
     * Display seller settings
     */
    public function settings()
    {
        $user = Auth::user();
        $store = VendorStore::where('user_id', $user->id)->first();

        return view('seller.settings', compact('user', 'store'));
    }
}
