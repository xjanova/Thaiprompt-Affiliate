<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletTopupController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->middleware('auth');
        $this->paymentService = $paymentService;
    }

    /**
     * Show topup page
     */
    public function index()
    {
        $wallet = Auth::user()->wallet;
        $paymentMethods = collect($this->paymentService->getAvailablePaymentMethods())
            ->reject(function ($method) {
                return $method['id'] === 'wallet' || $method['id'] === 'cash_on_delivery';
            });

        $recentTransactions = PaymentTransaction::where('user_id', Auth::id())
            ->where('type', 'wallet_topup')
            ->latest()
            ->limit(10)
            ->get();

        return view('wallet.topup', compact('wallet', 'paymentMethods', 'recentTransactions'));
    }

    /**
     * Process topup
     */
    public function process(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:100|max:100000',
            'payment_method' => 'required|in:promptpay,credit_card,bank_transfer',
            // Card details if needed
            'card_number' => 'required_if:payment_method,credit_card',
            'exp_month' => 'required_if:payment_method,credit_card',
            'exp_year' => 'required_if:payment_method,credit_card',
            'cvv' => 'required_if:payment_method,credit_card',
        ]);

        try {
            // Create topup transaction
            $transaction = $this->paymentService->createWalletTopup(
                Auth::user(),
                $validated['amount'],
                $validated['payment_method']
            );

            // Prepare payment data
            $paymentData = [];
            if ($validated['payment_method'] === 'credit_card') {
                $paymentData = [
                    'card_number' => $validated['card_number'],
                    'exp_month' => $validated['exp_month'],
                    'exp_year' => $validated['exp_year'],
                    'cvv' => $validated['cvv'],
                ];
            }

            // Process payment
            $result = $this->paymentService->processPayment($transaction, $paymentData);

            if ($result['success']) {
                if ($result['transaction']->status === 'completed') {
                    return redirect()->route('wallet.topup.success', ['transaction' => $transaction->id])
                        ->with('success', 'เติมเงินสำเร็จ');
                } else {
                    return redirect()->route('wallet.topup.payment', ['transaction' => $transaction->id]);
                }
            } else {
                return redirect()->route('wallet.topup')
                    ->with('error', 'การเติมเงินล้มเหลว: ' . $result['message']);
            }

        } catch (\Exception $e) {
            return redirect()->route('wallet.topup')
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Show payment page
     */
    public function payment(PaymentTransaction $transaction)
    {
        $this->authorize('view', $transaction);

        if ($transaction->isCompleted()) {
            return redirect()->route('wallet.topup.success', ['transaction' => $transaction->id]);
        }

        return view('wallet.topup-payment', compact('transaction'));
    }

    /**
     * Show success page
     */
    public function success(PaymentTransaction $transaction)
    {
        $this->authorize('view', $transaction);

        if (!$transaction->isCompleted()) {
            return redirect()->route('wallet.topup.payment', ['transaction' => $transaction->id]);
        }

        return view('wallet.topup-success', compact('transaction'));
    }
}
