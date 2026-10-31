<?php

namespace App\Http\Controllers;

use App\Models\CryptoAddress;
use App\Models\CryptoCurrency;
use App\Models\CryptoTransaction;
use App\Models\CryptoWallet;
use App\Services\Crypto\CryptoWalletService;
use App\Services\Crypto\TPIXBlockchainService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TPIX Wallet Controller (User Frontend)
 *
 * Handle user TPIX wallet operations
 */
class TPIXWalletController extends Controller
{
    protected TPIXBlockchainService $tpixService;

    protected CryptoWalletService $walletService;

    public function __construct(
        TPIXBlockchainService $tpixService,
        CryptoWalletService $walletService
    ) {
        $this->tpixService = $tpixService;
        $this->walletService = $walletService;
        $this->middleware('auth');
    }

    /**
     * TPIX Wallet Dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $tpixCurrency = CryptoCurrency::where('code', 'TPIX')->first();

        if (! $tpixCurrency) {
            return redirect()->route('user.dashboard')
                ->with('error', 'TPIX is not available at the moment.');
        }

        // Get or create wallet
        $wallet = CryptoWallet::firstOrCreate(
            [
                'user_id' => $user->id,
                'currency_id' => $tpixCurrency->id,
            ],
            [
                'balance' => 0,
                'status' => 'active',
                'wallet_type' => 'custodial',
            ]
        );

        // Get or create deposit address
        $depositAddress = CryptoAddress::where('crypto_wallet_id', $wallet->id)
            ->where('crypto_currency_id', $tpixCurrency->id)
            ->where('address_type', 'deposit')
            ->first();

        if (! $depositAddress) {
            $depositAddress = $this->walletService->generateAddressForCurrency($wallet, $tpixCurrency);
            // Update address type to 'deposit'
            $depositAddress->update(['address_type' => 'deposit']);
        }

        // Recent transactions
        $recentTransactions = CryptoTransaction::where('user_id', $user->id)
            ->where('crypto_currency_id', $tpixCurrency->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Statistics
        $stats = [
            'total_received' => CryptoTransaction::where('user_id', $user->id)
                ->where('crypto_currency_id', $tpixCurrency->id)
                ->where('type', 'deposit')
                ->where('status', 'confirmed')
                ->sum('amount'),

            'total_sent' => CryptoTransaction::where('user_id', $user->id)
                ->where('crypto_currency_id', $tpixCurrency->id)
                ->where('type', 'withdrawal')
                ->where('status', 'confirmed')
                ->sum('amount'),

            'pending_deposits' => CryptoTransaction::where('user_id', $user->id)
                ->where('crypto_currency_id', $tpixCurrency->id)
                ->where('type', 'deposit')
                ->where('status', 'pending')
                ->count(),

            'pending_withdrawals' => CryptoTransaction::where('user_id', $user->id)
                ->where('crypto_currency_id', $tpixCurrency->id)
                ->where('type', 'withdrawal')
                ->where('status', 'pending')
                ->count(),
        ];

        // Network info
        try {
            $networkInfo = $this->tpixService->getNetworkInfo();
        } catch (Exception $e) {
            $networkInfo = null;
            Log::warning('Failed to fetch TPIX network info', ['error' => $e->getMessage()]);
        }

        return view('user.tpix.wallet', compact(
            'wallet',
            'depositAddress',
            'recentTransactions',
            'stats',
            'tpixCurrency',
            'networkInfo'
        ));
    }

    /**
     * Deposit Page
     */
    public function deposit()
    {
        $user = Auth::user();
        $tpixCurrency = CryptoCurrency::where('code', 'TPIX')->first();

        if (! $tpixCurrency || ! $tpixCurrency->deposit_enabled) {
            return redirect()->route('user.tpix.wallet')
                ->with('error', 'TPIX deposits are currently disabled.');
        }

        // Get or create wallet first
        $wallet = CryptoWallet::firstOrCreate(
            [
                'user_id' => $user->id,
                'currency_id' => $tpixCurrency->id,
            ],
            [
                'balance' => 0,
                'status' => 'active',
                'wallet_type' => 'custodial',
            ]
        );

        // Get or create deposit address
        $depositAddress = CryptoAddress::where('crypto_wallet_id', $wallet->id)
            ->where('crypto_currency_id', $tpixCurrency->id)
            ->where('address_type', 'deposit')
            ->first();

        if (! $depositAddress) {
            $depositAddress = $this->walletService->generateAddressForCurrency($wallet, $tpixCurrency);
            // Update address type to 'deposit'
            $depositAddress->update(['address_type' => 'deposit']);
        }

        // Recent deposits
        $recentDeposits = CryptoTransaction::where('user_id', $user->id)
            ->where('crypto_currency_id', $tpixCurrency->id)
            ->where('type', 'deposit')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('user.tpix.deposit', compact(
            'depositAddress',
            'wallet',
            'recentDeposits',
            'tpixCurrency'
        ));
    }

    /**
     * Withdrawal Page
     */
    public function withdrawal()
    {
        $user = Auth::user();
        $tpixCurrency = CryptoCurrency::where('code', 'TPIX')->first();

        if (! $tpixCurrency || ! $tpixCurrency->withdrawal_enabled) {
            return redirect()->route('user.tpix.wallet')
                ->with('error', 'ขณะนี้ปิดการถอน TPIX ชั่วคราว');
        }

        // Get wallet
        $wallet = CryptoWallet::where('user_id', $user->id)
            ->where('currency_id', $tpixCurrency->id)
            ->first();

        if (! $wallet) {
            return redirect()->route('user.tpix.wallet')
                ->with('error', 'ไม่พบกระเป๋า TPIX กรุณาลองอีกครั้ง');
        }

        // Recent withdrawals
        $recentWithdrawals = CryptoTransaction::where('user_id', $user->id)
            ->where('crypto_currency_id', $tpixCurrency->id)
            ->where('type', 'withdrawal')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('user.tpix.withdrawal', compact(
            'wallet',
            'recentWithdrawals',
            'tpixCurrency'
        ));
    }

    /**
     * Process Withdrawal Request
     */
    public function processWithdrawal(Request $request)
    {
        $request->validate([
            'address' => 'required|string',
            'amount' => ['required', 'numeric', 'min:0.0001', 'regex:/^\d+(\.\d{1,8})?$/'],
        ], [
            'address.required' => 'กรุณากรอกที่อยู่ปลายทาง',
            'amount.required' => 'กรุณากรอกจำนวนที่ต้องการถอน',
            'amount.numeric' => 'จำนวนต้องเป็นตัวเลข',
            'amount.min' => 'จำนวนต้องมากกว่า 0.0001 TPIX',
            'amount.regex' => 'จำนวนต้องเป็นตัวเลขทศนิยมไม่เกิน 8 ตำแหน่ง',
        ]);

        $user = Auth::user();
        $tpixCurrency = CryptoCurrency::where('code', 'TPIX')->first();

        if (! $tpixCurrency || ! $tpixCurrency->withdrawal_enabled) {
            return back()->withInput()->with('error', 'ขณะนี้ปิดการถอน TPIX ชั่วคราว');
        }

        // Validate address
        if (! $this->tpixService->isValidAddress($request->address)) {
            return back()->withInput()->with('error', 'ที่อยู่ปลายทางไม่ถูกต้อง ต้องขึ้นต้นด้วย 0x ตามด้วยอักขระ 40 ตัว');
        }

        $amount = (string) $request->amount;
        $fee = (string) $tpixCurrency->withdrawal_fee;
        $totalRequired = bcadd($amount, $fee, 18);

        // Validate amount
        if (bccomp($amount, (string) $tpixCurrency->min_withdrawal, 18) < 0) {
            return back()->withInput()->with('error', 'ถอนขั้นต่ำ '.$tpixCurrency->min_withdrawal.' TPIX');
        }

        if ($tpixCurrency->max_withdrawal && bccomp($amount, (string) $tpixCurrency->max_withdrawal, 18) > 0) {
            return back()->withInput()->with('error', 'ถอนได้สูงสุด '.$tpixCurrency->max_withdrawal.' TPIX ต่อครั้ง');
        }

        try {
            // ตรวจยอดและหักยอดใน transaction เดียวกับล็อก — lockForUpdate นอก transaction ปล่อยล็อกทันที กดซ้อนกันจะถอนเกินยอดได้
            $error = DB::transaction(function () use ($user, $tpixCurrency, $request, $amount, $fee, $totalRequired) {
                // Get wallet
                $wallet = CryptoWallet::where('user_id', $user->id)
                    ->where('currency_id', $tpixCurrency->id)
                    ->lockForUpdate()
                    ->first();

                if (! $wallet) {
                    return 'ไม่พบกระเป๋า TPIX กรุณาลองอีกครั้ง';
                }

                if (! $wallet->isActive()) {
                    return 'กระเป๋า TPIX ของคุณถูกระงับการใช้งาน';
                }

                if (bccomp((string) $wallet->balance, $totalRequired, 18) < 0) {
                    return 'ยอดคงเหลือไม่พอ ต้องใช้ '.rtrim(rtrim($totalRequired, '0'), '.').' TPIX รวมค่าธรรมเนียม '.$fee.' TPIX';
                }

                // Deduct from wallet
                $wallet->decrement('balance', $totalRequired);

                // Create transaction
                CryptoTransaction::create([
                    'user_id' => $user->id,
                    'crypto_currency_id' => $tpixCurrency->id,
                    'crypto_wallet_id' => $wallet->id,
                    'type' => 'withdrawal',
                    'amount' => $amount,
                    'fee' => $fee,
                    'from_address' => $wallet->address,
                    'to_address' => $request->address,
                    'status' => 'pending',
                    'confirmations' => 0,
                    'network' => 'tpix',
                ]);

                return null;
            });
        } catch (Exception $e) {
            Log::error('TPIX withdrawal error', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'ส่งคำขอถอนไม่สำเร็จ กรุณาลองอีกครั้ง');
        }

        if ($error) {
            return back()->withInput()->with('error', $error);
        }

        return redirect()->route('user.tpix.wallet')
            ->with('success', 'รับคำขอถอน TPIX แล้ว รายการอยู่ระหว่างรอดำเนินการ');
    }

    /**
     * Transactions History
     */
    public function transactions(Request $request)
    {
        $user = Auth::user();
        $tpixCurrency = CryptoCurrency::where('code', 'TPIX')->first();

        if (! $tpixCurrency) {
            return redirect()->route('user.dashboard')
                ->with('error', 'TPIX is not available.');
        }

        $query = CryptoTransaction::where('user_id', $user->id)
            ->where('crypto_currency_id', $tpixCurrency->id);

        // Type filter
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Date range
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to.' 23:59:59');
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('user.tpix.transactions', compact('transactions', 'tpixCurrency'));
    }

    /**
     * Transaction Details
     */
    public function transactionDetails($id)
    {
        $user = Auth::user();
        $transaction = CryptoTransaction::where('user_id', $user->id)
            ->with(['currency', 'cryptoWallet'])
            ->findOrFail($id);

        // Get blockchain data if available
        $blockchainData = null;
        if ($transaction->tx_hash) {
            try {
                $blockchainData = $this->tpixService->getTransaction($transaction->tx_hash);
            } catch (Exception $e) {
                Log::warning('Failed to fetch blockchain data', [
                    'tx_hash' => $transaction->tx_hash,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('user.tpix.transaction-details', compact('transaction', 'blockchainData'));
    }

    /**
     * Send TPIX (P2P transfer)
     */
    public function send()
    {
        $user = Auth::user();
        $tpixCurrency = CryptoCurrency::where('code', 'TPIX')->first();

        if (! $tpixCurrency) {
            return redirect()->route('user.tpix.wallet')
                ->with('error', 'TPIX is not available.');
        }

        $wallet = CryptoWallet::where('user_id', $user->id)
            ->where('currency_id', $tpixCurrency->id)
            ->first();

        return view('user.tpix.send', compact('wallet', 'tpixCurrency'));
    }

    /**
     * Process Send TPIX
     */
    public function processSend(Request $request)
    {
        $request->validate([
            'recipient' => 'required|string',
            'amount' => 'required|numeric|min:0.0001',
        ]);

        $user = Auth::user();
        $tpixCurrency = CryptoCurrency::where('code', 'TPIX')->first();

        // Find recipient (by email or wallet address)
        $recipient = null;
        if (filter_var($request->recipient, FILTER_VALIDATE_EMAIL)) {
            $recipient = \App\Models\User::where('email', $request->recipient)->first();
        } elseif ($this->tpixService->isValidAddress($request->recipient)) {
            // Find by wallet address
            $recipientWallet = CryptoWallet::where('address', $request->recipient)
                ->where('currency_id', $tpixCurrency->id)
                ->first();
            $recipient = $recipientWallet ? $recipientWallet->user : null;
        }

        if (! $recipient) {
            return back()->with('error', 'Recipient not found.');
        }

        if ($recipient->id === $user->id) {
            return back()->with('error', 'You cannot send to yourself.');
        }

        try {
            $result = $this->walletService->transfer(
                $user,
                $recipient,
                $tpixCurrency,
                $request->amount,
                'TPIX transfer'
            );

            if ($result['success']) {
                return redirect()->route('user.tpix.wallet')
                    ->with('success', 'Successfully sent '.$request->amount.' TPIX to '.$recipient->name);
            } else {
                return back()->with('error', $result['message']);
            }
        } catch (Exception $e) {
            Log::error('TPIX send error', [
                'user_id' => $user->id,
                'amount' => $request->amount,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to send TPIX: '.$e->getMessage());
        }
    }
}
