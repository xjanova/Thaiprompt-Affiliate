<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Slogan;
use App\Models\VendorStore;
use App\Models\WithdrawalRequest;
use App\Services\ImageUploadService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class DashboardController extends Controller
{
    /**
     * Display seller dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $sellerId = $user->id;

        // Get or create vendor store
        $store = VendorStore::where('user_id', $sellerId)->first();

        if (!$store) {
            // Create default store for seller
            $store = VendorStore::create([
                'user_id' => $sellerId,
                'store_name' => $user->name . "'s Store",
                'store_slug' => \Str::slug($user->name . '-store-' . $sellerId),
                'status' => 'active',
                'subscription_status' => 'trial',
                'trial_ends_at' => now()->addDays(30),
            ]);
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
                'total' => $revenue
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
     * @param Request $request
     * @param ImageUploadService $imageUploadService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProfile(Request $request, ImageUploadService $imageUploadService)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
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
            if (!Hash::check($request->current_password, $user->password)) {
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

        return view('seller.wallet.index', compact('user', 'balance', 'transactions', 'pendingWithdrawals'));
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

        // ค่าธรรมเนียมและขั้นต่ำในการถอน
        $withdrawalSettings = [
            'min_amount' => 100,
            'max_amount' => 100000,
            'fee_percentage' => 0,
            'fee_fixed' => 0,
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
    public function submitWithdrawal(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100|max:100000',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'user_note' => 'nullable|string|max:500',
        ], [
            'amount.required' => 'กรุณาระบุจำนวนเงิน',
            'amount.min' => 'จำนวนเงินขั้นต่ำคือ 100 บาท',
            'amount.max' => 'จำนวนเงินสูงสุดคือ 100,000 บาท',
            'payment_method_id.required' => 'กรุณาเลือกช่องทางรับเงิน',
        ]);

        $user = Auth::user();
        $walletService = app(WalletService::class);
        $wallet = $walletService->getOrCreateWallet($user);

        // ตรวจสอบยอดเงินคงเหลือ
        if ($request->amount > $wallet->balance) {
            return back()->withErrors(['amount' => 'ยอดเงินไม่เพียงพอ'])->withInput();
        }

        // ตรวจสอบช่องทางรับเงิน
        $paymentMethod = $user->paymentMethods()->find($request->payment_method_id);
        if (!$paymentMethod) {
            return back()->withErrors(['payment_method_id' => 'ไม่พบช่องทางรับเงินที่เลือก'])->withInput();
        }

        DB::beginTransaction();
        try {
            // สร้างคำขอถอนเงิน
            $withdrawal = WithdrawalRequest::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'amount' => $request->amount,
                'fee' => 0,
                'tax' => 0,
                'net_amount' => $request->amount,
                'currency' => 'THB',
                'status' => 'pending',
                'payment_method_id' => $paymentMethod->id,
                'payment_type' => $paymentMethod->type,
                'payment_details' => [
                    'bank_name' => $paymentMethod->bank_name ?? null,
                    'account_name' => $paymentMethod->account_name ?? null,
                    'account_number' => $paymentMethod->account_number ?? null,
                ],
                'user_note' => $request->user_note,
                'metadata' => [
                    'source' => 'seller_dashboard',
                    'store_id' => VendorStore::where('user_id', $user->id)->value('id'),
                ],
            ]);

            // หักเงินจากกระเป๋า (hold)
            $walletService->debit(
                $wallet,
                $request->amount,
                'withdrawal_request',
                "คำขอถอนเงิน #{$withdrawal->request_id}",
                ['withdrawal_request_id' => $withdrawal->id]
            );

            DB::commit();

            return redirect()
                ->route('seller.wallet.withdrawals')
                ->with('success', 'ส่งคำขอถอนเงินสำเร็จ! กรุณารอการอนุมัติ');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()])->withInput();
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
        $walletService = app(WalletService::class);
        $wallet = $walletService->getOrCreateWallet($user);

        $withdrawal = WithdrawalRequest::where('user_id', $user->id)
            ->where('id', $id)
            ->where('status', 'pending')
            ->firstOrFail();

        DB::beginTransaction();
        try {
            // คืนเงินเข้ากระเป๋า
            $walletService->credit(
                $wallet,
                $withdrawal->amount,
                'withdrawal_cancelled',
                "ยกเลิกคำขอถอนเงิน #{$withdrawal->request_id}",
                ['withdrawal_request_id' => $withdrawal->id]
            );

            // อัพเดทสถานะ
            $withdrawal->update([
                'status' => 'cancelled',
            ]);

            DB::commit();

            return redirect()
                ->route('seller.wallet.withdrawals')
                ->with('success', 'ยกเลิกคำขอถอนเงินสำเร็จ');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
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
