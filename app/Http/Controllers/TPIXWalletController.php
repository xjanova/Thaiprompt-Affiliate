<?php

namespace App\Http\Controllers;

use App\Models\CryptoCurrency;
use App\Models\CryptoWallet;
use App\Models\CryptoTransaction;
use App\Models\CryptoAddress;
use App\Services\Crypto\TPIXBlockchainService;
use App\Services\Crypto\CryptoWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

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

        if (!$tpixCurrency) {
            return redirect()->route('dashboard')
                ->with('error', 'TPIX is not available at the moment.');
        }

        // Get or create wallet
        $wallet = CryptoWallet::firstOrCreate(
            [
                'user_id' => $user->id,
                'currency_id' => $tpixCurrency->id
            ],
            [
                'balance' => 0,
                'status' => 'active',
                'wallet_type' => 'custodial'
            ]
        );

        // Get deposit address
        $depositAddress = CryptoAddress::firstOrCreate(
            [
                'crypto_wallet_id' => $wallet->id,
                'crypto_currency_id' => $tpixCurrency->id,
                'address_type' => 'deposit'
            ],
            [
                'network' => 'tpix',
                'is_active' => true
            ]
        );

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

        if (!$tpixCurrency || !$tpixCurrency->deposit_enabled) {
            return redirect()->route('tpix.wallet')
                ->with('error', 'TPIX deposits are currently disabled.');
        }

        // Get or create wallet first
        $wallet = CryptoWallet::firstOrCreate(
            [
                'user_id' => $user->id,
                'currency_id' => $tpixCurrency->id
            ],
            [
                'balance' => 0,
                'status' => 'active',
                'wallet_type' => 'custodial'
            ]
        );

        // Get or create deposit address
        $depositAddress = CryptoAddress::firstOrCreate(
            [
                'crypto_wallet_id' => $wallet->id,
                'crypto_currency_id' => $tpixCurrency->id,
                'address_type' => 'deposit'
            ],
            [
                'network' => 'tpix',
                'is_active' => true
            ]
        );

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

        if (!$tpixCurrency || !$tpixCurrency->withdrawal_enabled) {
            return redirect()->route('tpix.wallet')
                ->with('error', 'TPIX withdrawals are currently disabled.');
        }

        // Get wallet
        $wallet = CryptoWallet::where('user_id', $user->id)
            ->where('currency_id', $tpixCurrency->id)
            ->first();

        if (!$wallet) {
            return redirect()->route('tpix.wallet')
                ->with('error', 'Wallet not found.');
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
            'amount' => 'required|numeric|min:0.0001',
        ]);

        $user = Auth::user();
        $tpixCurrency = CryptoCurrency::where('code', 'TPIX')->first();

        if (!$tpixCurrency || !$tpixCurrency->withdrawal_enabled) {
            return back()->with('error', 'TPIX withdrawals are currently disabled.');
        }

        // Validate address
        if (!$this->tpixService->isValidAddress($request->address)) {
            return back()->with('error', 'Invalid TPIX address.');
        }

        // Get wallet
        $wallet = CryptoWallet::where('user_id', $user->id)
            ->where('currency_id', $tpixCurrency->id)
            ->lockForUpdate()
            ->first();

        if (!$wallet) {
            return back()->with('error', 'Wallet not found.');
        }

        $amount = $request->amount;
        $fee = $tpixCurrency->withdrawal_fee;
        $totalRequired = $amount + $fee;

        // Validate amount
        if ($amount < $tpixCurrency->min_withdrawal) {
            return back()->with('error', 'Minimum withdrawal amount is ' . $tpixCurrency->min_withdrawal . ' TPIX.');
        }

        if ($tpixCurrency->max_withdrawal && $amount > $tpixCurrency->max_withdrawal) {
            return back()->with('error', 'Maximum withdrawal amount is ' . $tpixCurrency->max_withdrawal . ' TPIX.');
        }

        if ($wallet->balance < $totalRequired) {
            return back()->with('error', 'Insufficient balance. You need ' . $totalRequired . ' TPIX (including ' . $fee . ' TPIX fee).');
        }

        try {
            DB::beginTransaction();

            // Deduct from wallet
            $wallet->decrement('balance', $totalRequired);

            // Create transaction
            $transaction = CryptoTransaction::create([
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

            DB::commit();

            return redirect()->route('tpix.wallet')
                ->with('success', 'Withdrawal request submitted successfully! Your TPIX will be sent shortly.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('TPIX withdrawal error', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to process withdrawal: ' . $e->getMessage());
        }
    }

    /**
     * Transactions History
     */
    public function transactions(Request $request)
    {
        $user = Auth::user();
        $tpixCurrency = CryptoCurrency::where('code', 'TPIX')->first();

        if (!$tpixCurrency) {
            return redirect()->route('dashboard')
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
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
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
                    'error' => $e->getMessage()
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

        if (!$tpixCurrency) {
            return redirect()->route('tpix.wallet')
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
        } else if ($this->tpixService->isValidAddress($request->recipient)) {
            // Find by wallet address
            $recipientWallet = CryptoWallet::where('address', $request->recipient)
                ->where('currency_id', $tpixCurrency->id)
                ->first();
            $recipient = $recipientWallet ? $recipientWallet->user : null;
        }

        if (!$recipient) {
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
                return redirect()->route('tpix.wallet')
                    ->with('success', 'Successfully sent ' . $request->amount . ' TPIX to ' . $recipient->name);
            } else {
                return back()->with('error', $result['message']);
            }
        } catch (Exception $e) {
            Log::error('TPIX send error', [
                'user_id' => $user->id,
                'amount' => $request->amount,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to send TPIX: ' . $e->getMessage());
        }
    }
}
