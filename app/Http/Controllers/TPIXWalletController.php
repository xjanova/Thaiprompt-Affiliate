<?php

namespace App\Http\Controllers;

use App\Models\CryptoAddress;
use App\Models\CryptoCurrency;
use App\Models\CryptoTransaction;
use App\Models\CryptoWallet;
use App\Services\Crypto\CryptoWalletService;
use App\Services\Crypto\TPIXBlockchainService;
use App\Services\NotificationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

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
                ->with('error', 'ขณะนี้ปิดการฝาก TPIX ชั่วคราว');
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

        // generateAddressForCurrency แค่สุ่มเลข ไม่มี private key — แสดงให้โอนเข้าเหรียญจะกู้คืนไม่ได้
        $canReceive = $this->tpixService->isValidAddress((string) $depositAddress->address)
            && ($depositAddress->is_imported || $depositAddress->encrypted_private_key);

        return view('user.tpix.deposit', compact(
            'depositAddress',
            'wallet',
            'recentDeposits',
            'tpixCurrency',
            'canReceive'
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
            $withdrawal = null;
            $error = DB::transaction(function () use ($user, $tpixCurrency, $request, $amount, $fee, $totalRequired, &$withdrawal) {
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
                $withdrawal = CryptoTransaction::create([
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

        // คำขอ commit แล้ว แจ้งแอดมินไม่สำเร็จต้องไม่ทำให้สมาชิกเห็นว่าถอนไม่ผ่าน
        try {
            app(NotificationService::class)->notifyAdminTpixWithdrawal($withdrawal);
        } catch (Exception $e) {
            Log::warning('TPIX withdrawal admin notification failed', [
                'transaction_id' => $withdrawal->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('user.tpix.wallet')
            ->with('success', 'รับคำขอถอน TPIX แล้ว รายการอยู่ระหว่างรอดำเนินการ');
    }

    /**
     * Transactions History
     */
    public function transactions(Request $request)
    {
        // วันที่ผิดรูปแบบส่งเข้า MySQL ตรงๆ จะเป็น error 1525 และหน้า 500
        $request->validate([
            'type' => 'nullable|string|max:20',
            'status' => 'nullable|string|max:20',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d',
        ], [
            'date_from.date_format' => 'วันที่เริ่มต้นไม่ถูกต้อง',
            'date_to.date_format' => 'วันที่สิ้นสุดไม่ถูกต้อง',
        ]);

        $user = Auth::user();
        $tpixCurrency = CryptoCurrency::where('code', 'TPIX')->first();

        if (! $tpixCurrency) {
            return redirect()->route('user.dashboard')
                ->with('error', 'ขณะนี้ยังไม่เปิดให้ใช้งาน TPIX');
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
            } catch (Throwable $e) {
                // ยอดเกิน 9.22 TPIX ทำ bcdiv ใน formatTransaction โยน ValueError ซึ่ง catch Exception ไม่ติด
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
            return redirect()->route('user.dashboard')
                ->with('error', 'ขณะนี้ยังไม่เปิดให้ใช้งาน TPIX');
        }

        $wallet = CryptoWallet::where('user_id', $user->id)
            ->where('currency_id', $tpixCurrency->id)
            ->first();

        // โทเคนใช้ครั้งเดียวต่อฟอร์ม — กดยืนยันซ้ำหรือย้อนกลับแล้วส่งใหม่จะไม่โอนซ้ำ
        $transferToken = (string) Str::uuid();

        return view('user.tpix.send', compact('wallet', 'tpixCurrency', 'transferToken'));
    }

    /**
     * Process Send TPIX
     */
    public function processSend(Request $request)
    {
        $request->validate([
            'recipient' => 'required|string|max:255',
            'amount' => ['required', 'numeric', 'min:0.0001', 'regex:/^\d+(\.\d{1,8})?$/'],
            'transfer_token' => 'nullable|string|max:64',
        ], [
            'recipient.required' => 'กรุณากรอกอีเมลหรือที่อยู่กระเป๋าของผู้รับ',
            'amount.required' => 'กรุณากรอกจำนวนที่ต้องการส่ง',
            'amount.numeric' => 'จำนวนต้องเป็นตัวเลข',
            'amount.min' => 'จำนวนต้องไม่น้อยกว่า 0.0001 TPIX',
            'amount.regex' => 'จำนวนต้องเป็นตัวเลขทศนิยมไม่เกิน 8 ตำแหน่ง',
        ]);

        $user = Auth::user();
        $tpixCurrency = CryptoCurrency::where('code', 'TPIX')->first();

        if (! $tpixCurrency || ! $tpixCurrency->is_active) {
            return back()->withInput()->with('error', 'ขณะนี้ยังไม่เปิดให้ส่ง TPIX');
        }

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
            return back()->withInput()->with('error', 'ไม่พบสมาชิกผู้รับ ถ้าจะส่งออกไปกระเป๋าภายนอกให้ใช้หน้าถอน');
        }

        if ($recipient->id === $user->id) {
            return back()->withInput()->with('error', 'ส่งให้ตัวเองไม่ได้');
        }

        $tokenKey = $request->filled('transfer_token')
            ? 'tpix-send:'.$user->id.':'.$request->transfer_token
            : null;

        if ($tokenKey && ! Cache::add($tokenKey, 1, now()->addMinutes(30))) {
            return redirect()->route('user.tpix.transactions')
                ->with('info', 'รายการนี้ถูกส่งไปแล้ว ตรวจสอบได้ในประวัติธุรกรรม');
        }

        try {
            $result = $this->walletService->transfer($user, $recipient, $tpixCurrency, (string) $request->amount);
        } catch (Exception $e) {
            Log::error('TPIX send error', [
                'user_id' => $user->id,
                'amount' => $request->amount,
                'error' => $e->getMessage(),
            ]);

            $result = ['success' => false, 'message' => 'ส่ง TPIX ไม่สำเร็จ กรุณาลองอีกครั้ง'];
        }

        if ($result['success']) {
            return redirect()->route('user.tpix.wallet')->with('success', $result['message']);
        }

        // ส่งไม่สำเร็จ → คืนโทเคนให้แก้ฟอร์มแล้วส่งใหม่ได้
        if ($tokenKey) {
            Cache::forget($tokenKey);
        }

        return back()->withInput()->with('error', $result['message']);
    }
}
