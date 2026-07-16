<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Models\VendorStore;
use App\Models\Wallet;
use App\Models\WalletDebt;
use App\Models\WalletSetting;
use App\Services\CashbackService;
use App\Services\DebtCollectionService;
use App\Services\Payment\PaymentService;
use App\Services\PaymentGatewayService;
use App\Services\WalletService;
use App\Services\WithdrawalService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
            'this_month' => $cashbackTransactions->filter(function ($t) {
                return $t->created_at->isCurrentMonth();
            })->sum('amount'),
            'last_30_days' => $cashbackTransactions->filter(function ($t) {
                return $t->created_at->greaterThanOrEqualTo(now()->subDays(30));
            })->sum('amount'),
        ];

        // Get admin adjustments and refunds statistics
        $adminTransactions = $wallet->transactions()
            ->where('status', 'completed')
            ->where(function ($query) {
                $query->where('reference_type', 'admin_adjustment')
                    ->orWhere('reference_type', 'admin_refund')
                    ->orWhere('reference_type', 'refund')
                    ->orWhere('type', 'refund');
            })
            ->get();

        $adminStats = [
            'total' => $adminTransactions->sum('amount'),
            'count' => $adminTransactions->count(),
            'this_month' => $adminTransactions->filter(function ($t) {
                return $t->created_at->isCurrentMonth();
            })->sum('amount'),
            'last_30_days' => $adminTransactions->filter(function ($t) {
                return $t->created_at->greaterThanOrEqualTo(now()->subDays(30));
            })->sum('amount'),
        ];

        // ดึงข้อมูลหนี้ค้างชำระ (ถ้ามี)
        $debtSummary = app(DebtCollectionService::class)->getUserDebtSummary($user->id);

        return view('user.wallet.index', compact(
            'wallet',
            'statistics',
            'recentTransactions',
            'paymentMethods',
            'availableGateways',
            'cashbackStats',
            'adminStats',
            'debtSummary'
        ));
    }

    /**
     * ดึงยอดคงเหลือสำหรับ topbar (AJAX endpoint)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBalanceAjax()
    {
        $user = auth()->user();

        try {
            $wallet = $this->walletService->getOrCreateWallet($user);

            return response()->json([
                'success' => true,
                'balance' => (float) ($wallet->balance ?? 0),
                'currency' => $wallet->currency ?? 'THB',
                'total_income' => (float) ($wallet->total_income ?? 0),
                'total_expense' => (float) ($wallet->total_expense ?? 0),
            ]);
        } catch (Exception $e) {
            Log::error('Get Wallet Balance AJAX Error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'balance' => 0,
                'message' => 'ไม่สามารถดึงยอดคงเหลือได้',
            ], 500);
        }
    }

    /**
     * แสดงหน้าเติมเงิน Wallet
     *
     * ระบบเติมเงินแยกออกจากตะกร้าสินค้าโดยสมบูรณ์
     * ใช้ PaymentTransaction โดยตรง
     *
     * @return \Illuminate\View\View
     */
    public function topup()
    {
        $user = auth()->user();
        $wallet = $this->walletService->getOrCreateWallet($user);

        return view('user.wallet.topup', compact('wallet'));
    }

    /**
     * ประมวลผลการเติมเงิน Wallet
     *
     * สร้าง PaymentTransaction โดยตรง และ redirect ไปหน้าชำระเงิน
     * ไม่ผ่านระบบตะกร้าสินค้า
     *
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
            $user = auth()->user();
            $amount = (float) $request->amount;

            // สร้าง PaymentTransaction สำหรับ wallet_topup โดยตรง
            $transaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'store_id' => VendorStore::getPlatformStoreId(),
                'type' => 'wallet_topup',
                'payment_method' => 'pending', // รอเลือกวิธีชำระเงิน
                'status' => 'pending',
                'amount' => $amount,
                'currency' => 'THB',
                'expired_at' => now()->addMinutes(30),
                'metadata' => [
                    'source' => 'web',
                    'original_amount' => $amount,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ]);

            Log::info('Wallet Topup Transaction Created', [
                'transaction_id' => $transaction->transaction_id,
                'user_id' => $user->id,
                'amount' => $amount,
            ]);

            // Redirect ไปหน้าชำระเงินเติมเงิน
            return redirect()->route('user.wallet.topup.payment', $transaction->transaction_id)
                ->with('success', 'สร้างรายการเติมเงินสำเร็จ กรุณาเลือกวิธีชำระเงิน');

        } catch (Exception $e) {
            Log::error('Wallet Topup Error', [
                'user_id' => auth()->id(),
                'amount' => $request->amount,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * แสดงหน้าชำระเงินสำหรับเติมเงิน
     *
     * @return \Illuminate\View\View
     */
    public function topupPayment(string $transactionId)
    {
        $user = auth()->user();

        // ค้นหา transaction
        $transaction = PaymentTransaction::where('transaction_id', $transactionId)
            ->where('user_id', $user->id)
            ->where('type', 'wallet_topup')
            ->firstOrFail();

        // ตรวจสอบว่า transaction หมดอายุหรือยัง
        if ($transaction->isExpired()) {
            return redirect()->route('user.wallet.topup')
                ->with('error', 'รายการเติมเงินหมดอายุแล้ว กรุณาสร้างรายการใหม่');
        }

        // ตรวจสอบว่า transaction ชำระเงินแล้วหรือยัง
        if ($transaction->isCompleted()) {
            return redirect()->route('user.wallet.index')
                ->with('success', 'รายการเติมเงินนี้ชำระเงินสำเร็จแล้ว');
        }

        // ดึง payment gateways ที่พร้อมใช้งาน
        $paymentGateways = PaymentGateway::where('is_active', true)
            ->where('is_available', true)
            ->where('is_coming_soon', false)
            ->where('supports_deposit', true)
            ->orderBy('sort_order')
            ->get();

        $wallet = $this->walletService->getOrCreateWallet($user);

        return view('user.wallet.topup-payment', compact('transaction', 'paymentGateways', 'wallet'));
    }

    /**
     * ประมวลผลการชำระเงินเติมเงิน
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function processTopupPayment(Request $request, string $transactionId)
    {
        $request->validate([
            'payment_method' => 'required|string',
        ], [
            'payment_method.required' => 'กรุณาเลือกวิธีชำระเงิน',
        ]);

        $user = auth()->user();

        // ค้นหา transaction
        $transaction = PaymentTransaction::where('transaction_id', $transactionId)
            ->where('user_id', $user->id)
            ->where('type', 'wallet_topup')
            ->firstOrFail();

        // ตรวจสอบว่า transaction หมดอายุหรือยัง
        if ($transaction->isExpired()) {
            return redirect()->route('user.wallet.topup')
                ->with('error', 'รายการเติมเงินหมดอายุแล้ว กรุณาสร้างรายการใหม่');
        }

        // ตรวจสอบว่า transaction ชำระเงินแล้วหรือยัง
        if ($transaction->isCompleted()) {
            return redirect()->route('user.wallet.index')
                ->with('success', 'รายการเติมเงินนี้ชำระเงินสำเร็จแล้ว');
        }

        try {
            $paymentMethod = $request->payment_method;

            // อัปเดต payment method
            $transaction->update([
                'payment_method' => $paymentMethod,
            ]);

            // ประมวลผลตามประเภทการชำระเงิน
            $paymentService = app(PaymentService::class);

            $result = $paymentService->processPayment($transaction, [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            if (! $result['success']) {
                return redirect()->back()
                    ->with('error', $result['message'] ?? 'การชำระเงินล้มเหลว');
            }

            // ตรวจสอบผลลัพธ์
            $updatedTransaction = $result['transaction'];

            // ถ้าต้องแสดง QR Code (PromptPay)
            if ($paymentMethod === 'promptpay' && isset($result['data']['qr_code'])) {
                return view('user.wallet.topup-promptpay', [
                    'transaction' => $updatedTransaction,
                    'qrCode' => $result['data']['qr_code'],
                    'refNo' => $result['data']['ref_no'] ?? null,
                    'response' => $result['data']['response'] ?? [],
                ]);
            }

            // ถ้าชำระเงินสำเร็จทันที
            if ($updatedTransaction->isCompleted()) {
                return redirect()->route('user.wallet.index')
                    ->with('success', 'เติมเงิน ฿'.number_format($transaction->amount, 2).' สำเร็จแล้ว!');
            }

            // ถ้ายังรอดำเนินการ
            return redirect()->route('user.wallet.topup.status', $transactionId)
                ->with('info', 'กำลังดำเนินการ กรุณารอสักครู่');

        } catch (Exception $e) {
            Log::error('Topup Payment Error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * ตรวจสอบสถานะการชำระเงินเติมเงิน
     *
     * @return \Illuminate\View\View
     */
    public function topupStatus(string $transactionId)
    {
        $user = auth()->user();

        $transaction = PaymentTransaction::where('transaction_id', $transactionId)
            ->where('user_id', $user->id)
            ->where('type', 'wallet_topup')
            ->firstOrFail();

        return view('user.wallet.topup-status', compact('transaction'));
    }

    /**
     * API: ตรวจสอบสถานะ transaction (สำหรับ polling)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkTopupStatus(string $transactionId)
    {
        $user = auth()->user();

        $transaction = PaymentTransaction::where('transaction_id', $transactionId)
            ->where('user_id', $user->id)
            ->where('type', 'wallet_topup')
            ->first();

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบรายการ',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $transaction->status,
                'is_completed' => $transaction->isCompleted(),
                'is_expired' => $transaction->isExpired(),
                'amount' => $transaction->amount,
                'paid_at' => $transaction->paid_at?->toIso8601String(),
            ],
        ]);
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
     *
     * ✅ KYC gate: ถ้ายังไม่ผ่าน → redirect ไปหน้า KYC พร้อมข้อความอธิบาย
     * (Super Admin/Admin ข้าม)
     */
    public function withdraw()
    {
        $user = auth()->user();

        // KYC gate — ป้องกันฟอกเงิน + กฎการเงิน
        $isAdmin = $user->is_super_admin || in_array($user->role, ['admin', 'super_admin'], true);
        if (! $isAdmin && ! $user->isKycVerified()) {
            return redirect()->route('user.kyc.index')
                ->with('warning', 'กรุณายืนยันตัวตน (KYC) ก่อนถอนเงิน — '
                    .'อัพโหลดบัตรประชาชน + เซลฟี่เพื่อยืนยันตัวตนตามกฎการเงิน');
        }

        $wallet = $this->walletService->getOrCreateWallet($user);
        $paymentMethods = $user->paymentMethods()->active()->get();

        // นโยบายค่าธรรมเนียม/ภาษี — ส่งให้หน้าเว็บแสดงยอดสุทธิก่อนกดยืนยัน
        // (เดิมหน้าเว็บไม่บอกค่าธรรมเนียมเลย ลูกค้าจะงงว่าทำไมได้เงินไม่เต็ม)
        $feePolicy = [
            'min_amount' => (float) \App\Models\WalletSetting::get('withdrawal_min_amount', 0),
            'max_amount' => (float) \App\Models\WalletSetting::get('withdrawal_max_amount', 999999999),
            'fee_type' => \App\Models\WalletSetting::get('withdrawal_fee_type', 'percentage'),
            'fee_amount' => (float) \App\Models\WalletSetting::get('withdrawal_fee_amount', 0),
            'fee_min' => (float) \App\Models\WalletSetting::get('withdrawal_fee_min', 0),
            'fee_max' => (float) \App\Models\WalletSetting::get('withdrawal_fee_max', 999999),
            'tax_enabled' => (bool) \App\Models\WalletSetting::get('tax_enabled', false),
            'tax_threshold' => (float) \App\Models\WalletSetting::get('tax_threshold', 0),
            'tax_percentage' => (float) \App\Models\WalletSetting::get('tax_percentage', 0),
        ];

        return view('user.wallet.withdraw', compact('wallet', 'paymentMethods', 'feePolicy'));
    }

    /**
     * Submit withdrawal request
     */
    public function submitWithdrawal(Request $request)
    {
        // PIN บังคับเฉพาะกระเป๋าที่ตั้ง PIN ไว้ — ลูกค้าบอท (ไม่มี PIN) ไม่ต้องกรอก
        // 🔒 (2026-07-16) เดิมฟอร์มบังคับกรอก PIN แต่ controller ทิ้งค่านั้นเลย
        //    (ไม่ validate ไม่ส่งต่อ) — คนตั้ง PIN จริงกลับถอนไม่ได้เพราะ service
        //    ได้แต่ค่าว่าง ตอนนี้ส่งต่อไป verify จริงใน WithdrawalService
        $wallet = $this->walletService->getOrCreateWallet(auth()->user());

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'user_note' => 'nullable|string|max:500',
            'pin' => $wallet->hasPIN() ? 'required|string' : 'nullable|string',
        ], [
            'pin.required' => 'กรุณากรอก PIN กระเป๋าเงิน',
        ]);

        try {
            $withdrawal = $this->withdrawalService->createWithdrawalRequest(
                auth()->user(),
                $request->amount,
                $request->payment_method_id,
                $request->user_note,
                $request->pin
            );

            return redirect()->route('user.wallet.withdrawals')
                ->with('success', 'ส่งคำขอถอนเงินเรียบร้อยแล้ว รหัสคำขอ: '.$withdrawal->request_id);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
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
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
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
                ->with('success', 'โอนเงินสำเร็จ: '.number_format($request->amount, 2).' บาท');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
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
        $validated = $request->validate([
            'type' => 'required|in:promptpay,bank_transfer,paypal',
            'name' => 'required|string|max:255',
            'account_name' => 'required_if:type,promptpay,bank_transfer|string|max:255',
            'account_number' => 'required_if:type,promptpay,bank_transfer|string|max:50',
            'bank_name' => 'required_if:type,bank_transfer|string|max:255',
            'paypal_email' => 'required_if:type,paypal|email|max:255',
            'is_default' => 'boolean',
        ]);

        try {
            // 🔒 (2026-07-15) ใช้ $validated ไม่ใช่ $request->all()
            //    PaymentMethod::$fillable มี is_verified/verified_at อยู่ด้วย และ validator
            //    ไม่ตัด key ที่ไม่ได้ประกาศออกจาก all() → เดิมผู้ใช้ยิง is_verified=1 มาเอง
            //    แล้วรับรองบัญชีธนาคารตัวเองว่าผ่านการตรวจแล้วได้
            //    (ยังไม่มีโค้ดไหนอ่าน is_verified — แต่ถ้าวันไหนบังคับใช้ตอนถอนเงิน
            //     ช่องนี้จะกลายเป็นทางข้ามทันที)
            $paymentMethod = auth()->user()->paymentMethods()->create($validated);

            return redirect()->back()->with('success', 'เพิ่มช่องทางการรับเงินเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
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
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
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
     * ฝากเงินผ่าน PromptPay — สร้าง PaymentTransaction จริง + SMS Payment Checker
     *
     * Flow: สร้าง PaymentTransaction → สร้าง unique amount (ทศนิยมสำหรับ SMS matching)
     * → สร้าง QR Code EMVCo → แสดงหน้า topup-promptpay (มี polling อัตโนมัติ)
     * → SMS Checker จับคู่ยอดเงิน → auto-complete → เงินเข้า wallet
     *
     * ใช้ flow เดียวกับระบบ Topup และ Checkout (PaymentService + PromptPayProvider)
     */
    public function depositPromptPay(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:1000000',
        ], [
            'amount.required' => 'กรุณาระบุจำนวนเงิน',
            'amount.numeric' => 'จำนวนเงินต้องเป็นตัวเลขเท่านั้น',
            'amount.min' => 'จำนวนเงินขั้นต่ำ 1 บาท',
            'amount.max' => 'จำนวนเงินสูงสุด 1,000,000 บาท',
        ]);

        try {
            $user = auth()->user();
            $amount = (float) $request->amount;

            // ─── ขั้นตอนที่ 1: สร้าง PaymentTransaction (แบบเดียวกับ processTopup) ───
            // ⚠️ ตั้ง payment_method = 'pending' ก่อน เพื่อป้องกัน model boot event
            // สร้าง unique amount ซ้ำซ้อนกับ PaymentService::processPayment()
            $transaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'store_id' => VendorStore::getPlatformStoreId(),
                'type' => 'wallet_topup',
                'payment_method' => 'pending',
                'status' => 'pending',
                'amount' => $amount,
                'currency' => 'THB',
                'expired_at' => now()->addMinutes(30),
                'metadata' => [
                    'source' => 'deposit_promptpay',
                    'original_amount' => $amount,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ]);

            Log::info('Deposit PromptPay: Transaction Created', [
                'transaction_id' => $transaction->transaction_id,
                'user_id' => $user->id,
                'amount' => $amount,
            ]);

            // ─── ขั้นตอนที่ 2: อัปเดต payment_method เป็น promptpay ───
            // (เหมือน processTopupPayment ที่ทำก่อนเรียก processPayment)
            $transaction->update([
                'payment_method' => 'promptpay',
            ]);
            $transaction->refresh(); // โหลดข้อมูลใหม่จาก DB เพื่อให้ metadata สดใหม่

            // ─── ขั้นตอนที่ 3: ประมวลผลผ่าน PaymentService ───
            // จะสร้าง unique amount + QR Code + ตั้งสถานะเป็น processing
            $paymentService = app(PaymentService::class);
            $result = $paymentService->processPayment($transaction, [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            if (! $result['success']) {
                Log::warning('Deposit PromptPay: processPayment failed', [
                    'transaction_id' => $transaction->transaction_id,
                    'message' => $result['message'] ?? 'unknown',
                ]);

                return redirect()->back()
                    ->with('error', $result['message'] ?? 'ไม่สามารถสร้าง QR Code ได้ กรุณาลองใหม่อีกครั้ง')
                    ->withInput();
            }

            // ─── ขั้นตอนที่ 4: แสดงหน้า QR Code (ใช้ view เดียวกับ topup) ───
            $updatedTransaction = $result['transaction'];

            return view('user.wallet.topup-promptpay', [
                'transaction' => $updatedTransaction,
                'qrCode' => $result['data']['qr_code'] ?? '',
                'refNo' => $result['data']['ref_no'] ?? null,
                'response' => $result['data']['response'] ?? [],
            ]);

        } catch (Exception $e) {
            Log::error('Deposit PromptPay Error', [
                'user_id' => auth()->id(),
                'amount' => $request->amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Process Bank Transfer deposit
     *
     * ⚠️ ขั้นต่ำ 100 บาท - เนื่องจาก SMS แจ้งเตือนไม่ทำงานถ้ายอดต่ำกว่า 100 บาท
     * ยอดต่ำกว่า 100 บาท → ต้องใช้ PromptPay เท่านั้น
     */
    public function depositBankTransfer(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100|max:1000000',
            'slip' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
            'note' => 'nullable|string|max:500',
        ], [
            'amount.min' => 'ยอดขั้นต่ำสำหรับโอนผ่านธนาคาร คือ 100 บาท กรุณาใช้ช่องทางพร้อมเพย์สำหรับยอดต่ำกว่า 100 บาท',
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
                ->with('success', 'อัพโหลดสลิปเรียบร้อยแล้ว รหัสอ้างอิง: '.$result['reference'].' กรุณารอการตรวจสอบจากแอดมิน 1-24 ชั่วโมง');
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
                ->with('success', 'ฝากเงินสำเร็จ จำนวน ฿'.number_format($request->amount, 2).' รหัส: '.$result['transaction_id']);
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
                ->with('success', 'ฝากเงินสำเร็จ จำนวน ฿'.number_format($request->amount, 2).' รหัส: '.$result['transaction_id']);
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
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
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
     * ระบบป้องกันหลายชั้น:
     * 1. Idempotency Middleware - ป้องกัน double submit
     * 2. Rate Limiting - จำกัดความถี่การโอน
     * 3. Transfer Lock - ป้องกัน concurrent transfers
     * 4. Row-level Locking - ป้องกัน race condition ใน DB
     * 5. Balance Verification - ตรวจยอดเงินภายใน transaction
     *
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

        $user = auth()->user();
        $transactionRef = 'QRT-'.Str::upper(Str::random(12));

        // บันทึก Audit Log เริ่มต้น
        Log::info('QR Transfer Initiated', [
            'ref' => $transactionRef,
            'user_id' => $user->id,
            'to_address' => $request->wallet_address,
            'amount' => $request->amount,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            // === ระบบป้องกันที่ 1: Transfer Lock ===
            // ป้องกันการโอนพร้อมกันหลายรายการจากบัญชีเดียวกัน
            $lockKey = "qr_transfer_lock:user:{$user->id}";
            $lock = Cache::lock($lockKey, 30); // lock 30 วินาที

            if (! $lock->get()) {
                Log::warning('QR Transfer Lock Failed', [
                    'ref' => $transactionRef,
                    'user_id' => $user->id,
                    'reason' => 'Another transfer in progress',
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'มีการโอนเงินอยู่ระหว่างดำเนินการ กรุณารอสักครู่แล้วลองใหม่',
                ], 409);
            }

            try {
                // === ดำเนินการภายใน Database Transaction ===
                return DB::transaction(function () use ($request, $user, $transactionRef) {
                    // ดึง Wallet ผู้ส่งพร้อม Lock (ป้องกัน race condition)
                    $fromWallet = Wallet::where('user_id', $user->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $fromWallet) {
                        throw new Exception('ไม่พบกระเป๋าเงินของคุณ');
                    }

                    // ดึง Wallet ผู้รับ
                    $toWallet = Wallet::where('wallet_address', $request->wallet_address)->first();

                    // ตรวจสอบ Wallet ผู้รับ
                    if (! $toWallet) {
                        throw new Exception('ไม่พบ Wallet ผู้รับ กรุณาตรวจสอบ QR Code');
                    }

                    // ป้องกันโอนให้ตัวเอง
                    if ($fromWallet->id === $toWallet->id) {
                        throw new Exception('ไม่สามารถโอนเงินให้ตัวเองได้');
                    }

                    $amount = (float) $request->amount;

                    // ตรวจสอบยอดขั้นต่ำ
                    $validationErrors = WalletSetting::validateTransferAmount($amount);
                    if (! empty($validationErrors)) {
                        throw new Exception($validationErrors[0]);
                    }

                    // คำนวณค่าธรรมเนียม
                    $fee = $this->calculateQrTransferFee($amount);
                    $totalDeduction = $amount + $fee;

                    // === ระบบป้องกันที่ 2: ตรวจยอดเงินซ้ำภายใน transaction ===
                    // (สำคัญ: ตรวจจาก row ที่ lock แล้วเท่านั้น)
                    if ($fromWallet->balance < $totalDeduction) {
                        Log::warning('QR Transfer Insufficient Balance', [
                            'ref' => $transactionRef,
                            'user_id' => $user->id,
                            'balance' => $fromWallet->balance,
                            'required' => $totalDeduction,
                        ]);

                        throw new Exception('ยอดเงินไม่เพียงพอ ต้องการ ฿'.number_format($totalDeduction, 2).' (รวมค่าธรรมเนียม ฿'.number_format($fee, 2).')');
                    }

                    // ดำเนินการโอนเงินผ่าน WalletService (รวมค่าธรรมเนียม)
                    $description = ($request->description ?? 'QR Transfer')." [Ref: {$transactionRef}]";
                    $result = $this->walletService->transferWithFee(
                        $fromWallet,
                        $toWallet,
                        $amount,
                        $request->pin,
                        $description
                    );

                    // บันทึก Audit Log สำเร็จ
                    Log::info('QR Transfer Success', [
                        'ref' => $transactionRef,
                        'user_id' => $user->id,
                        'from_wallet' => $fromWallet->wallet_address,
                        'to_wallet' => $toWallet->wallet_address,
                        'to_user_id' => $toWallet->user_id,
                        'amount' => $amount,
                        'fee' => $result['fee'] ?? $fee,
                        'transaction_id' => $result['out_transaction']->id ?? null,
                        'balance_before' => $result['out_transaction']->balance_before ?? null,
                        'balance_after' => $result['out_transaction']->balance_after ?? null,
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'โอนเงินสำเร็จ!',
                        'data' => [
                            'reference' => $transactionRef,
                            'amount' => $amount,
                            'fee' => $result['fee'] ?? $fee,
                            'total' => $result['total_amount'] ?? $totalDeduction,
                            'to_name' => $toWallet->user->name ?? 'ไม่ระบุชื่อ',
                            'to_address' => $toWallet->wallet_address,
                            'transaction_id' => $result['out_transaction']->id ?? null,
                        ],
                    ]);
                });
            } finally {
                $lock->release();
            }
        } catch (Exception $e) {
            // บันทึก Audit Log ล้มเหลว
            Log::error('QR Transfer Failed', [
                'ref' => $transactionRef,
                'user_id' => $user->id,
                'to_address' => $request->wallet_address,
                'amount' => $request->amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * API: ดึงข้อมูล Wallet จาก QR Code
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWalletFromQr(Request $request)
    {
        $request->validate([
            'wallet_address' => 'required|string',
        ]);

        try {
            $wallet = Wallet::where('wallet_address', $request->wallet_address)->first();

            if (! $wallet) {
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
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: คำนวณค่าธรรมเนียมการโอน QR
     *
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
     * แสดงหน้าหนี้ค้างชำระของ User
     */
    public function debts(Request $request)
    {
        $user = auth()->user();
        $debtSummary = app(DebtCollectionService::class)->getUserDebtSummary($user->id);

        // ดึงรายการหนี้ พร้อม filter
        $query = WalletDebt::forUser($user->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $debts = $query->latest()->paginate(15);

        return view('user.wallet.debts', compact('debtSummary', 'debts'));
    }

    /**
     * คำนวณค่าธรรมเนียมการโอน QR
     *
     * @param  float  $amount  จำนวนเงินที่โอน
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
