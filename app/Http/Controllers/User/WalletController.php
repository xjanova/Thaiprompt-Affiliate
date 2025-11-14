<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\WalletService;
use App\Services\WithdrawalService;
use App\Services\PaymentGatewayService;
use App\Services\CashbackService;
use App\Models\PaymentMethod;
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
}
