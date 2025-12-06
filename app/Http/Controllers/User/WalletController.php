<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\WalletService;
use App\Services\WithdrawalService;
use App\Services\PaymentGatewayService;
use App\Services\CashbackService;
use App\Models\PaymentMethod;
use App\Models\Wallet;
use App\Models\WalletSetting;
use Illuminate\Http\Request;
use Exception;

class WalletController extends Controller
{
    protected $walletService;
    protected $withdrawalService;
    protected $paymentGatewayService;
    protected $cashbackService;

    public function __construct(
        WalletService $walletService,
        WithdrawalService $withdrawalService,
        PaymentGatewayService $paymentGatewayService
    ) {
        $this->walletService = $walletService;
        $this->withdrawalService = $withdrawalService;
        $this->paymentGatewayService = $paymentGatewayService;
        $this->cashbackService = new CashbackService($walletService);
    }

    /**
     * Display wallet dashboard
     */
    public function index()
    {
        $user = auth()->user();
        $wallet = $this->walletService->getOrCreateWallet($user);
        $statistics = $this->walletService->getWalletStatistics($wallet);

        $recentTransactions = $wallet->transactions()
            ->with('relatedWallet.user')
            ->latest()
            ->take(10)
            ->get();

        $paymentMethods = $user->paymentMethods()->active()->get();
        $availableGateways = $this->paymentGatewayService->getAvailablePaymentMethods();

        // Get cashback statistics
        $cashbackTransactions = $wallet->transactions()
            ->where('type', 'cashback')
            ->where('status', 'completed')
            ->get();

        $cashbackStats = [
            'total' => $cashbackTransactions->sum('amount'),
            'count' => $cashbackTransactions->count(),
            'this_month' => $cashbackTransactions->filter(function($t) {
                return $t->created_at->isCurrentMonth();
            })->sum('amount'),
            'last_30_days' => $cashbackTransactions->filter(function($t) {
                return $t->created_at->greaterThanOrEqualTo(now()->subDays(30));
            })->sum('amount'),
        ];

        // Get admin adjustments and refunds statistics
        $adminTransactions = $wallet->transactions()
            ->where('status', 'completed')
            ->where(function($query) {
                $query->where('reference_type', 'admin_adjustment')
                      ->orWhere('reference_type', 'admin_refund')
                      ->orWhere('reference_type', 'refund')
                      ->orWhere('type', 'refund');
            })
            ->get();

        $adminStats = [
            'total' => $adminTransactions->sum('amount'),
            'count' => $adminTransactions->count(),
            'this_month' => $adminTransactions->filter(function($t) {
                return $t->created_at->isCurrentMonth();
            })->sum('amount'),
            'last_30_days' => $adminTransactions->filter(function($t) {
                return $t->created_at->greaterThanOrEqualTo(now()->subDays(30));
            })->sum('amount'),
        ];

        return view('user.wallet.index', compact(
            'wallet',
            'statistics',
            'recentTransactions',
            'paymentMethods',
            'availableGateways',
            'cashbackStats',
            'adminStats'
        ));
    }

    /**
     * แสดงหน้าเติมเงิน Wallet (Topup Packages)
     *
     * @return \Illuminate\View\View
     */
    public function topup()
    {
        $user = auth()->user();
        $wallet = $this->walletService->getOrCreateWallet($user);

        // ดึงแพ็คเกจเติมเงิน (virtual products ในหมวด wallet-topup)
        $topupPackages = \App\Models\Product::where('is_virtual', true)
            ->where('is_active', true)
            ->whereHas('category', function ($query) {
                $query->where('slug', 'wallet-topup');
            })
            ->orderBy('price', 'asc')
            ->get();

        return view('user.wallet.topup', compact('wallet', 'topupPackages'));
    }

    /**
     * ประมวลผลการเติมเงิน Wallet
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processTopup(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100|max:100000',
        ], [
            'amount.required' => 'กรุณาระบุจำนวนเงินที่ต้องการเติม',
            'amount.numeric' => 'จำนวนเงินต้องเป็นตัวเลขเท่านั้น',
            'amount.min' => 'จำนวนเงินขั้นต่ำ 100 บาท',
            'amount.max' => 'จำนวนเงินสูงสุด 100,000 บาท',
        ]);

        try {
            $amount = $request->amount;

            // หาหรือสร้างแพ็คเกจเติมเงิน
            $product = $this->findOrCreateTopupPackage($amount);

            if (!$product) {
                return redirect()->back()
                    ->with('error', 'ไม่สามารถสร้างแพ็คเกจเติมเงินได้ กรุณาติดต่อผู้ดูแลระบบ')
                    ->withInput();
            }

            // เพิ่มสินค้าเข้าตะกร้า
            $cart = \App\Models\Cart::firstOrCreate([
                'user_id' => auth()->id(),
            ]);

            // ลบสินค้าเติมเงินเก่าออกจากตะกร้า (ถ้ามี)
            $cart->items()
                ->whereHas('product.category', function ($query) {
                    $query->where('slug', 'wallet-topup');
                })
                ->delete();

            // เพิ่มแพ็คเกจเติมเงินใหม่
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => $product->price,
            ]);

            // ไปหน้า Checkout
            return redirect()->route('checkout.index')
                ->with('success', 'เพิ่มแพ็คเกจเติมเงิน ' . number_format($amount, 2) . ' บาทเข้าตะกร้าแล้ว');
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * หาหรือสร้างแพ็คเกจเติมเงิน
     *
     * @param float $amount จำนวนเงิน
     * @return \App\Models\Product|null
     */
    private function findOrCreateTopupPackage(float $amount)
    {
        // หาหมวดหมู่ wallet-topup
        $category = \App\Models\ProductCategory::firstOrCreate(
            ['slug' => 'wallet-topup'],
            [
                'name' => 'เติมเงิน Wallet',
                'description' => 'แพ็คเกจเติมเงินเข้า Wallet',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        // หา admin user สำหรับเป็น seller
        $adminUser = \App\Models\User::whereIn('role', ['admin', 'super_admin'])->first();
        if (!$adminUser) {
            return null;
        }

        // หาแพ็คเกจที่มีราคาตรงกัน
        $slug = 'topup-' . (int)$amount;
        $product = \App\Models\Product::where('slug', $slug)
            ->where('category_id', $category->id)
            ->where('is_virtual', true)
            ->first();

        // ถ้าไม่มี ให้สร้างใหม่
        if (!$product) {
            $product = \App\Models\Product::create([
                'seller_id' => $adminUser->id,
                'category_id' => $category->id,
                'name' => '💰 เติมเงิน ' . number_format($amount) . ' บาท',
                'slug' => $slug,
                'sku' => 'TOPUP-' . strtoupper($slug),
                'description' => 'เติมเงิน ' . number_format($amount, 2) . ' บาท เข้า Wallet ของคุณ',
                'short_description' => 'เติม ' . number_format($amount) . ' บาท',
                'price' => $amount,
                'compare_at_price' => null,
                'cost_price' => $amount,
                'stock_quantity' => 999999,
                'track_inventory' => false,
                'stock_status' => 'in_stock',
                'is_virtual' => true,
                'is_active' => true,
                'is_featured' => false,
                'is_public_approved' => true,
                'public_approved_at' => now(),
                'public_approved_by' => $adminUser->id,
                'published_at' => now(),
                'commission_rate' => 0,
                'customer_cashback' => 0,
                'cashback_percentage' => 0,
            ]);
        }

        return $product;
    }

    /**
     * Display deposit page
     */
    public function deposit()
    {
        $user = auth()->user();
        $wallet = $this->walletService->getOrCreateWallet($user);
        $availableGateways = $this->paymentGatewayService->getAvailablePaymentMethods();

        return view('user.wallet.deposit', compact('wallet', 'availableGateways'));
    }

    /**
     * Display withdrawal page
     */
    public function withdraw()
    {
        $user = auth()->user();
        $wallet = $this->walletService->getOrCreateWallet($user);
        $paymentMethods = $user->paymentMethods()->active()->get();

        return view('user.wallet.withdraw', compact('wallet', 'paymentMethods'));
    }

    /**
     * Submit withdrawal request
     */
    public function submitWithdrawal(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'user_note' => 'nullable|string|max:500',
        ]);

        try {
            $withdrawal = $this->withdrawalService->createWithdrawalRequest(
                auth()->user(),
                $request->amount,
                $request->payment_method_id,
                $request->user_note
            );

            return redirect()->route('user.wallet.withdrawals')
                ->with('success', 'ส่งคำขอถอนเงินเรียบร้อยแล้ว รหัสคำขอ: ' . $withdrawal->request_id);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Display withdrawal history
     */
    public function withdrawals()
    {
        $user = auth()->user();
        $withdrawals = $this->withdrawalService->getUserWithdrawals($user, 20);
        $statistics = $this->withdrawalService->getWithdrawalStatistics($user);

        return view('user.wallet.withdrawals', compact('withdrawals', 'statistics'));
    }

    /**
     * Cancel withdrawal request
     */
    public function cancelWithdrawal($id)
    {
        try {
            $withdrawal = auth()->user()->withdrawalRequests()->findOrFail($id);
            $this->withdrawalService->cancelWithdrawal($withdrawal, auth()->user());

            return redirect()->back()->with('success', 'ยกเลิกคำขอถอนเงินเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Display transfer page
     */
    public function transfer()
    {
        $wallet = $this->walletService->getOrCreateWallet(auth()->user());

        return view('user.wallet.transfer', compact('wallet'));
    }

    /**
     * Submit transfer
     */
    public function submitTransfer(Request $request)
    {
        $request->validate([
            'wallet_address' => 'required|string|exists:wallets,wallet_address',
            'amount' => 'required|numeric|min:0.01',
            'pin' => 'required|string',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $fromWallet = $this->walletService->getOrCreateWallet(auth()->user());
            $toWallet = \App\Models\Wallet::where('wallet_address', $request->wallet_address)->firstOrFail();

            if ($fromWallet->id === $toWallet->id) {
                return redirect()->back()->with('error', 'ไม่สามารถโอนเงินให้ตัวเองได้');
            }

            $result = $this->walletService->transfer(
                $fromWallet,
                $toWallet,
                $request->amount,
                $request->pin,
                $request->description ?? 'Transfer'
            );

            return redirect()->route('user.wallet.index')
                ->with('success', 'โอนเงินสำเร็จ: ' . number_format($request->amount, 2) . ' บาท');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Display payment methods
     */
    public function paymentMethods()
    {
        $user = auth()->user();
        $paymentMethods = $user->paymentMethods()->latest()->get();

        return view('user.wallet.payment-methods', compact('paymentMethods'));
    }

    /**
     * Store payment method
     */
    public function storePaymentMethod(Request $request)
    {
        $request->validate([
            'type' => 'required|in:promptpay,bank_transfer,paypal',
            'name' => 'required|string|max:255',
            'account_name' => 'required_if:type,promptpay,bank_transfer|string|max:255',
            'account_number' => 'required_if:type,promptpay,bank_transfer|string|max:50',
            'bank_name' => 'required_if:type,bank_transfer|string|max:255',
            'paypal_email' => 'required_if:type,paypal|email|max:255',
            'is_default' => 'boolean',
        ]);

        try {
            $paymentMethod = auth()->user()->paymentMethods()->create($request->all());

            return redirect()->back()->with('success', 'เพิ่มช่องทางการรับเงินเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Delete payment method
     */
    public function deletePaymentMethod($id)
    {
        try {
            $paymentMethod = auth()->user()->paymentMethods()->findOrFail($id);
            $paymentMethod->delete();

            return redirect()->back()->with('success', 'ลบช่องทางการรับเงินเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Display transactions
     */
    public function transactions(Request $request)
    {
        $wallet = $this->walletService->getOrCreateWallet(auth()->user());

        $query = $wallet->transactions()->with('relatedWallet.user');

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->latest()->paginate(20);

        return view('user.wallet.transactions', compact('wallet', 'transactions'));
    }

    /**
     * Process PromptPay deposit
     */
    public function depositPromptPay(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:1000000',
        ]);

        try {
            $user = auth()->user();
            $result = $this->paymentGatewayService->processPromptPayDeposit(
                $user,
                $request->amount,
                ['ip_address' => $request->ip()]
            );

            return view('user.wallet.deposit-promptpay', compact('result'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Process Bank Transfer deposit
     */
    public function depositBankTransfer(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:1000000',
            'slip' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $user = auth()->user();
            $result = $this->paymentGatewayService->processBankTransfer(
                $user,
                $request->amount,
                $request->file('slip'),
                [
                    'note' => $request->note,
                    'ip_address' => $request->ip(),
                ]
            );

            return redirect()->route('user.wallet.index')
                ->with('success', 'อัพโหลดสลิปเรียบร้อยแล้ว รหัสอ้างอิง: ' . $result['reference'] . ' กรุณารอการตรวจสอบจากแอดมิน 1-24 ชั่วโมง');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Process Stripe deposit
     */
    public function depositStripe(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:1000000',
            'payment_method_id' => 'required|string',
        ]);

        try {
            $user = auth()->user();
            $result = $this->paymentGatewayService->processStripePayment(
                $user,
                $request->amount,
                $request->payment_method_id
            );

            return redirect()->route('user.wallet.index')
                ->with('success', 'ฝากเงินสำเร็จ จำนวน ฿' . number_format($request->amount, 2) . ' รหัส: ' . $result['transaction_id']);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Process PayPal deposit
     */
    public function depositPayPal(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:1000000',
            'order_id' => 'required|string',
        ]);

        try {
            $user = auth()->user();
            $result = $this->paymentGatewayService->processPayPalPayment(
                $user,
                $request->amount,
                $request->order_id
            );

            return redirect()->route('user.wallet.index')
                ->with('success', 'ฝากเงินสำเร็จ จำนวน ฿' . number_format($request->amount, 2) . ' รหัส: ' . $result['transaction_id']);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Verify deposit payment
     */
    public function verifyDeposit($reference)
    {
        try {
            // For PromptPay verification
            $verified = $this->paymentGatewayService->verifyPromptPayPayment($reference);

            if ($verified) {
                // Process the deposit
                // This would typically be called by a webhook in production
                return redirect()->route('user.wallet.index')
                    ->with('success', 'ชำระเงินสำเร็จ กำลังดำเนินการเติมเงินเข้ากระเป๋า');
            }

            return redirect()->route('user.wallet.index')
                ->with('error', 'ไม่พบการชำระเงิน กรุณาลองใหม่อีกครั้ง');
        } catch (Exception $e) {
            return redirect()->route('user.wallet.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Set default payment method
     */
    public function setDefaultPaymentMethod($id)
    {
        try {
            $user = auth()->user();

            // Remove default from all payment methods
            $user->paymentMethods()->update(['is_default' => false]);

            // Set new default
            $paymentMethod = $user->paymentMethods()->findOrFail($id);
            $paymentMethod->update(['is_default' => true]);

            return redirect()->back()->with('success', 'ตั้งค่าช่องทางรับเงินเริ่มต้นเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    // =====================================================
    // QR Code Transfer Functions
    // =====================================================

    /**
     * แสดง QR Code สำหรับรับเงิน
     *
     * สร้าง QR Code จาก Wallet Address
     * ผู้ใช้สามารถแชร์ QR ให้คนอื่นสแกนเพื่อโอนเงินมาได้
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function qrCode(Request $request)
    {
        $wallet = $this->walletService->getOrCreateWallet(auth()->user());

        // จำนวนเงินที่ต้องการรับ (ถ้ามี)
        $requestedAmount = $request->input('amount', null);

        // ค่าธรรมเนียมการโอน
        $transferFee = WalletSetting::get('transfer_fee_amount', 0);
        $transferFeeType = WalletSetting::get('transfer_fee_type', 'fixed');
        $minTransferAmount = WalletSetting::get('transfer_min_amount', 1);

        // สร้าง QR Data (JSON format)
        $qrData = [
            'type' => 'tpwallet_transfer',
            'address' => $wallet->wallet_address,
            'name' => auth()->user()->name,
            'amount' => $requestedAmount,
            'timestamp' => now()->timestamp,
        ];

        return view('user.wallet.qr-code', [
            'wallet' => $wallet,
            'qrData' => json_encode($qrData),
            'requestedAmount' => $requestedAmount,
            'transferFee' => $transferFee,
            'transferFeeType' => $transferFeeType,
            'minTransferAmount' => $minTransferAmount,
        ]);
    }

    /**
     * แสดงหน้าสแกน QR Code เพื่อโอนเงิน
     *
     * @return \Illuminate\View\View
     */
    public function qrTransfer()
    {
        $wallet = $this->walletService->getOrCreateWallet(auth()->user());

        // ค่าธรรมเนียมการโอน
        $transferFee = WalletSetting::get('transfer_fee_amount', 0);
        $transferFeeType = WalletSetting::get('transfer_fee_type', 'fixed');
        $minTransferAmount = WalletSetting::get('transfer_min_amount', 1);

        return view('user.wallet.qr-transfer', [
            'wallet' => $wallet,
            'transferFee' => $transferFee,
            'transferFeeType' => $transferFeeType,
            'minTransferAmount' => $minTransferAmount,
        ]);
    }

    /**
     * ดำเนินการโอนเงินผ่าน QR Code
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processQrTransfer(Request $request)
    {
        $request->validate([
            'wallet_address' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'pin' => 'required|string|size:6',
            'description' => 'nullable|string|max:255',
        ], [
            'wallet_address.required' => 'ไม่พบ Wallet Address ผู้รับ',
            'amount.required' => 'กรุณาระบุจำนวนเงิน',
            'amount.numeric' => 'จำนวนเงินต้องเป็นตัวเลข',
            'amount.min' => 'จำนวนเงินต้องมากกว่า 0',
            'pin.required' => 'กรุณาระบุ PIN',
            'pin.size' => 'PIN ต้องมี 6 หลัก',
        ]);

        try {
            $fromWallet = $this->walletService->getOrCreateWallet(auth()->user());
            $toWallet = Wallet::where('wallet_address', $request->wallet_address)->first();

            // ตรวจสอบ Wallet ผู้รับ
            if (!$toWallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบ Wallet ผู้รับ กรุณาตรวจสอบ QR Code',
                ], 400);
            }

            // ป้องกันโอนให้ตัวเอง
            if ($fromWallet->id === $toWallet->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถโอนเงินให้ตัวเองได้',
                ], 400);
            }

            $amount = (float) $request->amount;

            // ตรวจสอบยอดขั้นต่ำ
            $validationErrors = WalletSetting::validateTransferAmount($amount);
            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => $validationErrors[0],
                ], 400);
            }

            // คำนวณค่าธรรมเนียม
            $fee = $this->calculateQrTransferFee($amount);
            $totalDeduction = $amount + $fee;

            // ตรวจสอบยอดเงินเพียงพอ (รวมค่าธรรมเนียม)
            if ($fromWallet->balance < $totalDeduction) {
                return response()->json([
                    'success' => false,
                    'message' => 'ยอดเงินไม่เพียงพอ ต้องการ ฿' . number_format($totalDeduction, 2) . ' (รวมค่าธรรมเนียม ฿' . number_format($fee, 2) . ')',
                ], 400);
            }

            // ดำเนินการโอนเงินผ่าน WalletService (รวมค่าธรรมเนียม)
            $description = $request->description ?? 'QR Transfer';
            $result = $this->walletService->transferWithFee(
                $fromWallet,
                $toWallet,
                $amount,
                $request->pin,
                $description
            );

            return response()->json([
                'success' => true,
                'message' => 'โอนเงินสำเร็จ!',
                'data' => [
                    'amount' => $amount,
                    'fee' => $result['fee'] ?? $fee,
                    'total' => $result['total_amount'] ?? $totalDeduction,
                    'to_name' => $toWallet->user->name ?? 'ไม่ระบุชื่อ',
                    'to_address' => $toWallet->wallet_address,
                    'transaction_id' => $result['out_transaction']->id ?? null,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * API: ดึงข้อมูล Wallet จาก QR Code
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWalletFromQr(Request $request)
    {
        $request->validate([
            'wallet_address' => 'required|string',
        ]);

        try {
            $wallet = Wallet::where('wallet_address', $request->wallet_address)->first();

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบ Wallet Address นี้ในระบบ',
                ], 404);
            }

            // ไม่ให้โอนให้ตัวเอง
            $myWallet = $this->walletService->getOrCreateWallet(auth()->user());
            if ($wallet->id === $myWallet->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถโอนเงินให้ตัวเองได้',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'wallet_address' => $wallet->wallet_address,
                    'user_name' => $wallet->user->name ?? 'ไม่ระบุชื่อ',
                    'user_avatar' => $wallet->user->avatar ?? null,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: คำนวณค่าธรรมเนียมการโอน QR
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculateQrFee(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $amount = (float) $request->amount;
        $fee = $this->calculateQrTransferFee($amount);

        $myWallet = $this->walletService->getOrCreateWallet(auth()->user());
        $totalDeduction = $amount + $fee;
        $hasEnoughBalance = $myWallet->balance >= $totalDeduction;

        return response()->json([
            'success' => true,
            'data' => [
                'amount' => $amount,
                'fee' => $fee,
                'total' => $totalDeduction,
                'balance' => $myWallet->balance,
                'has_enough_balance' => $hasEnoughBalance,
            ],
        ]);
    }

    /**
     * คำนวณค่าธรรมเนียมการโอน QR
     *
     * @param float $amount จำนวนเงินที่โอน
     * @return float ค่าธรรมเนียม
     */
    private function calculateQrTransferFee(float $amount): float
    {
        $feeType = WalletSetting::get('transfer_fee_type', 'fixed');
        $feeAmount = WalletSetting::get('transfer_fee_amount', 0);
        $feeMin = WalletSetting::get('transfer_fee_min', 0);
        $feeMax = WalletSetting::get('transfer_fee_max', 999999);

        if ($feeType === 'percentage') {
            // คิดเป็นเปอร์เซ็นต์
            $fee = ($amount * $feeAmount) / 100;
        } else {
            // คิดเป็นจำนวนคงที่
            $fee = $feeAmount;
        }

        // Apply min/max limits
        $fee = max($feeMin, min($feeMax, $fee));

        return round($fee, 2);
    }
}
