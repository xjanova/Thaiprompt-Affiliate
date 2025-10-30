<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\WalletService;
use App\Services\WithdrawalService;
use App\Services\PaymentGatewayService;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Exception;

class WalletController extends Controller
{
    protected $walletService;
    protected $withdrawalService;
    protected $paymentGatewayService;

    public function __construct(
        WalletService $walletService,
        WithdrawalService $withdrawalService,
        PaymentGatewayService $paymentGatewayService
    ) {
        $this->walletService = $walletService;
        $this->withdrawalService = $withdrawalService;
        $this->paymentGatewayService = $paymentGatewayService;
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

        return view('user.wallet.index', compact(
            'wallet',
            'statistics',
            'recentTransactions',
            'paymentMethods',
            'availableGateways'
        ));
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
}
