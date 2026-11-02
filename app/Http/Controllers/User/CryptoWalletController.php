<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crypto\ConnectExternalWalletRequest;
use App\Http\Requests\Crypto\CreateCustodialWalletRequest;
use App\Http\Requests\Crypto\SetDefaultWalletRequest;
use App\Http\Requests\Crypto\WithdrawCryptoRequest;
use App\Models\CryptoCurrency;
use App\Models\CryptoWithdrawalRequest;
use App\Services\Crypto\CryptoPriceService;
use App\Services\Crypto\CryptoWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CryptoWalletController extends Controller
{
    protected CryptoWalletService $walletService;

    protected CryptoPriceService $priceService;

    public function __construct(
        CryptoWalletService $walletService,
        CryptoPriceService $priceService
    ) {
        $this->walletService = $walletService;
        $this->priceService = $priceService;
    }

    /**
     * Crypto Wallet Dashboard
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $wallet = $user->defaultCryptoWallet;

        // Get all active cryptocurrencies
        $currencies = CryptoCurrency::active()->ordered()->get();

        // Get balances if wallet exists
        $balances = [];
        $totalValueTHB = 0;

        if ($wallet) {
            $balances = $this->walletService->getAllBalances($wallet);

            foreach ($balances as $balance) {
                $totalValueTHB += $balance['balance_thb'];
            }
        }

        // Get recent transactions
        $recentTransactions = $user->cryptoTransactions()
            ->with(['currency', 'cryptoWallet'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // มีกระเป๋าแต่ยังไม่ได้ตั้งกระเป๋าหลัก → หน้าแรกต้องพาไปตั้งกระเป๋าหลัก ไม่ใช่บอกว่า "ยังไม่มีกระเป๋า"
        $hasWallets = $wallet !== null || $user->cryptoWallets()->exists();

        return view('user.crypto-wallet.index', compact(
            'wallet',
            'hasWallets',
            'currencies',
            'balances',
            'totalValueTHB',
            'recentTransactions'
        ));
    }

    /**
     * List all wallets
     */
    public function wallets(Request $request)
    {
        $user = $request->user();
        $wallets = $user->cryptoWallets()->with('cryptoAddresses.currency')->get();

        return view('user.crypto-wallet.wallets', compact('wallets'));
    }

    /**
     * Premium Wallet Management - Enhanced multi-wallet interface
     */
    public function walletManagement(Request $request)
    {
        $user = $request->user();
        // CryptoWallet ไม่มี relation balances (ไม่มีตาราง crypto_balances) — เดิม eager load ตัวนี้ทำหน้าล่ม 500
        // ยอดคงเหลือคำนวณจาก cryptoAddresses ผ่าน getAllBalances() ด้านล่างแทน
        $wallets = $user->cryptoWallets()
            ->with(['cryptoAddresses.currency'])
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate balances by wallet
        $balancesByWallet = [];
        $totalValue = 0;
        $totalAssets = 0;

        foreach ($wallets as $wallet) {
            $balances = $this->walletService->getAllBalances($wallet);
            $balancesByWallet[$wallet->id] = collect($balances);

            foreach ($balances as $balance) {
                $totalValue += $balance['balance_thb'];
                $totalAssets++;
            }
        }

        return view('user.crypto-wallet.wallet-management', compact(
            'wallets',
            'balancesByWallet',
            'totalValue',
            'totalAssets'
        ));
    }

    /**
     * Create new custodial wallet
     */
    public function createWallet(CreateCustodialWalletRequest $request)
    {
        try {
            $wallet = $this->walletService->createCustodialWallet(
                $request->user(),
                $request->pin,
                [
                    'name' => $request->name ?? 'My Crypto Wallet',
                    'device_info' => $request->userAgent(),
                    'two_factor_enabled' => $request->two_factor_enabled ?? false,
                ]
            );

            return redirect()->route('user.crypto-wallet.index')
                ->with('success', 'สร้างกระเป๋าคริปโตสำเร็จ!');

        } catch (\Exception $e) {
            Log::error('Failed to create crypto wallet', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', self::userMessage($e, 'สร้างกระเป๋าไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'));
        }
    }

    /**
     * ข้อความผิดพลาดที่แสดงให้สมาชิกเห็น
     *
     * ข้อความภาษาไทยจาก service ตั้งใจให้ผู้ใช้อ่านได้ ส่งต่อได้เลย
     * ข้อความอื่น (อังกฤษ/ข้อผิดพลาดฐานข้อมูล) อาจเผยรายละเอียดภายใน → ใช้ข้อความกลางแทน (รายละเอียดอยู่ใน log)
     */
    public static function userMessage(\Throwable $e, string $fallback = 'ทำรายการไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'): string
    {
        $message = $e->getMessage();

        return preg_match('/\p{Thai}/u', $message) ? $message : $fallback;
    }

    /**
     * Connect external wallet (MetaMask, WalletConnect)
     */
    public function connectWallet(ConnectExternalWalletRequest $request)
    {
        try {
            $wallet = $this->walletService->connectExternalWallet(
                $request->user(),
                $request->address,
                $request->network,
                [
                    'name' => 'External Wallet',
                    'device_info' => $request->userAgent(),
                ]
            );

            // Verify wallet ownership if signature provided
            if ($request->signature && $request->message) {
                $this->walletService->verifyWalletOwnership(
                    $wallet,
                    $request->signature,
                    $request->message
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'เชื่อมต่อกระเป๋าสำเร็จ!',
                'wallet_id' => $wallet->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to connect wallet', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 400);
        }
    }

    /**
     * Deposit page
     */
    public function deposit(Request $request)
    {
        $user = $request->user();
        $wallet = $this->walletService->getOrCreateDefaultWallet($user);

        $currencies = CryptoCurrency::depositEnabled()->ordered()->get();

        return view('user.crypto-wallet.deposit', compact('wallet', 'currencies'));
    }

    /**
     * Get deposit address for specific currency
     */
    public function depositCurrency(Request $request, $currencyCode)
    {
        $user = $request->user();
        $wallet = $this->walletService->getOrCreateDefaultWallet($user);

        $currency = CryptoCurrency::where('code', strtoupper($currencyCode))
            ->depositEnabled()
            ->firstOrFail();

        // Get or create deposit address
        $depositAddress = $user->cryptoDepositAddresses()
            ->where('crypto_wallet_id', $wallet->id)
            ->where('crypto_currency_id', $currency->id)
            ->where('network', $currency->network)
            ->first();

        if (! $depositAddress) {
            // สร้างที่อยู่ฝากใหม่ (generateAddressForCurrency สร้างทั้ง crypto_addresses และ crypto_deposit_addresses)
            // เดิมเรียก $cryptoAddress->wallet ซึ่งไม่มี relation ชื่อนี้ (ชื่อจริง cryptoWallet) → 500 ทุกครั้งที่เปิดเหรียญใหม่ครั้งแรก
            $this->walletService->generateAddressForCurrency($wallet, $currency);
            $depositAddress = $wallet->depositAddresses()
                ->where('crypto_currency_id', $currency->id)
                ->latest('id')
                ->first();
        }

        // ที่อยู่ที่ระบบสุ่มขึ้นเอง (ไม่มี private key และไม่ได้นำเข้าจากกระเป๋าภายนอก) รับเหรียญจริงไม่ได้
        // โอนเข้าไปแล้วกู้คืนไม่ได้ — แสดงที่อยู่ให้ลูกค้าเฉพาะเมื่อระบบถือกุญแจของที่อยู่นั้นจริง (แนวเดียวกับ TPIXWalletController::deposit)
        $sourceAddress = $depositAddress
            ? $wallet->cryptoAddresses()
                ->where('crypto_currency_id', $currency->id)
                ->where('address', $depositAddress->address)
                ->first()
            : null;
        $canReceive = $sourceAddress !== null && $sourceAddress->canReceive();

        return view('user.crypto-wallet.deposit-currency', compact(
            'wallet',
            'currency',
            'depositAddress',
            'canReceive'
        ));
    }

    /**
     * ที่อยู่ฝากของเหรียญ (JSON) — route user.crypto-wallet.deposit.address
     *
     * เดิม route ชี้ไปเมธอดที่ไม่มีอยู่ (500) · ส่งที่อยู่กลับเฉพาะที่รับเหรียญได้จริง (CryptoAddress::canReceive)
     * ไม่สร้างที่อยู่สุ่มใหม่ เพราะที่อยู่สุ่มรับเหรียญไม่ได้
     */
    public function getDepositAddress(Request $request, $currencyCode)
    {
        $wallet = $request->user()->defaultCryptoWallet;

        $currency = CryptoCurrency::where('code', strtoupper((string) $currencyCode))
            ->depositEnabled()
            ->first();

        if (! $wallet || ! $currency) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบสกุลเงินที่เปิดรับฝาก',
            ], 404);
        }

        $address = $wallet->cryptoAddresses()
            ->where('crypto_currency_id', $currency->id)
            ->get()
            ->first(fn ($a) => $a->canReceive());

        if (! $address) {
            return response()->json([
                'success' => false,
                'can_receive' => false,
                'message' => 'เหรียญนี้ยังรับฝากไม่ได้ กรุณาติดต่อทีมงาน',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'can_receive' => true,
            'address' => $address->address,
            'network' => $address->network,
        ]);
    }

    /**
     * Withdraw page
     */
    public function withdraw(Request $request)
    {
        $user = $request->user();
        $wallet = $user->defaultCryptoWallet;

        if (! $wallet) {
            return redirect()->route('user.crypto-wallet.index')
                ->with('error', 'กรุณาสร้างกระเป๋าคริปโตก่อน');
        }

        // แสดงเฉพาะเหรียญที่เครือข่ายผ่าน WithdrawCryptoRequest — เหรียญเครือข่ายอื่น (เช่น TPIX บน tpix-chain)
        // ถ้าใส่ไว้ในฟอร์มจะถูกปัดตก "เครือข่ายที่เลือกไม่ถูกต้อง" ทุกครั้ง · TPIX ถอนผ่านหน้า user.tpix.withdrawal แทน
        $allCurrencies = CryptoCurrency::withdrawalEnabled()->ordered()->get();
        $currencies = $allCurrencies
            ->filter(fn ($c) => in_array($c->network, WithdrawCryptoRequest::NETWORKS, true))
            ->values();
        $tpixWithdrawable = $allCurrencies->contains(fn ($c) => $c->code === 'TPIX');
        $balances = $this->walletService->getAllBalances($wallet);

        return view('user.crypto-wallet.withdraw', compact(
            'wallet',
            'currencies',
            'balances',
            'tpixWithdrawable'
        ));
    }

    /**
     * Submit withdrawal request
     */
    public function submitWithdrawal(WithdrawCryptoRequest $request)
    {
        $user = $request->user();
        $wallet = $user->defaultCryptoWallet;

        // Verify PIN
        if (! $wallet->verifyPin($request->pin)) {
            return back()->with('error', 'PIN ไม่ถูกต้อง');
        }

        $currency = CryptoCurrency::where('code', $request->currency_code)->firstOrFail();

        // Check balance
        $balance = $this->walletService->getBalance($wallet, $currency);
        if ($balance < $request->amount) {
            return back()->with('error', 'ยอดเงินไม่เพียงพอ');
        }

        // Calculate fees
        $networkFee = $currency->withdrawal_fee;
        $platformFee = 0; // Can be configured
        $totalFee = $networkFee + $platformFee;
        $netAmount = $request->amount - $totalFee;

        try {
            // Create withdrawal request
            $withdrawalRequest = CryptoWithdrawalRequest::create([
                'user_id' => $user->id,
                'crypto_wallet_id' => $wallet->id,
                'crypto_currency_id' => $currency->id,
                'to_address' => $request->to_address,
                'network' => $request->network,
                'amount' => $request->amount,
                'network_fee' => $networkFee,
                'platform_fee' => $platformFee,
                'total_fee' => $totalFee,
                'net_amount' => $netAmount,
                'status' => 'pending',
                'user_note' => $request->note ?? '',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Calculate risk score
            $withdrawalRequest->calculateRiskScore();

            return redirect()->route('user.crypto-wallet.withdrawals')
                ->with('success', 'ส่งคำขอถอนเงินสำเร็จ! กรุณารอการอนุมัติ');

        } catch (\Exception $e) {
            Log::error('Failed to create withdrawal request', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', self::userMessage($e, 'ส่งคำขอถอนไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'));
        }
    }

    /**
     * Withdrawal history
     */
    public function withdrawals(Request $request)
    {
        $user = $request->user();

        $withdrawals = $user->cryptoWithdrawalRequests()
            ->with(['currency', 'cryptoWallet', 'cryptoTransaction.currency'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('user.crypto-wallet.withdrawals', compact('withdrawals'));
    }

    /**
     * Cancel withdrawal request
     */
    public function cancelWithdrawal(Request $request, $id)
    {
        $user = $request->user();
        $withdrawal = CryptoWithdrawalRequest::where('user_id', $user->id)
            ->findOrFail($id);

        if (! $withdrawal->isPending()) {
            return back()->with('error', 'ไม่สามารถยกเลิกได้ เนื่องจากคำขอถูกดำเนินการแล้ว');
        }

        $withdrawal->update(['status' => 'cancelled']);

        return back()->with('success', 'ยกเลิกคำขอถอนเงินสำเร็จ');
    }

    /**
     * Transaction history
     */
    public function transactions(Request $request)
    {
        $user = $request->user();

        $query = $user->cryptoTransactions()->with(['currency', 'cryptoWallet']);

        // ตัวกรอง — ใช้ filled() ไม่ใช่ has(): ฟอร์มส่ง type= ว่างมาเมื่อเลือก "ทั้งหมด"
        // has() เดิมจึงกรอง type = '' แล้วไม่เจออะไรเลย
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('currency')) {
            $currency = CryptoCurrency::where('code', $request->currency)->first();
            if ($currency) {
                $query->where('crypto_currency_id', $currency->id);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);

        $currencies = CryptoCurrency::active()->ordered()->get();

        return view('user.crypto-wallet.transactions', compact('transactions', 'currencies'));
    }

    /**
     * Transaction detail
     */
    public function transactionDetail(Request $request, $id)
    {
        $user = $request->user();
        $transaction = $user->cryptoTransactions()
            ->with(['currency', 'cryptoWallet', 'cryptoAddress'])
            ->findOrFail($id);

        return view('user.crypto-wallet.transaction-detail', compact('transaction'));
    }

    /**
     * Get all crypto prices
     */
    public function getPrices(Request $request)
    {
        $currencies = CryptoCurrency::active()->get();
        $prices = [];

        foreach ($currencies as $currency) {
            $rate = $this->priceService->getCurrentRate($currency);
            if ($rate) {
                $prices[$currency->code] = [
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'price_thb' => $rate->rate_thb,
                    'buy_price' => $rate->buy_rate_thb,
                    'sell_price' => $rate->sell_rate_thb,
                    'change_24h' => $rate->price_change_24h,
                    'updated_at' => $rate->created_at->toIso8601String(),
                ];
            }
        }

        return response()->json($prices);
    }

    /**
     * Get price for specific currency
     */
    public function getPrice(Request $request, $currencyCode)
    {
        $currency = CryptoCurrency::where('code', strtoupper($currencyCode))
            ->active()
            ->firstOrFail();

        $rate = $this->priceService->getCurrentRate($currency);

        if (! $rate) {
            return response()->json([
                'error' => 'Price not available',
            ], 404);
        }

        return response()->json([
            'code' => $currency->code,
            'name' => $currency->name,
            'price_thb' => $rate->rate_thb,
            'buy_price' => $rate->buy_rate_thb,
            'sell_price' => $rate->sell_rate_thb,
            'change_24h' => $rate->price_change_24h,
            'volume_24h' => $rate->volume_24h,
            'market_cap' => $rate->market_cap,
            'updated_at' => $rate->created_at->toIso8601String(),
        ]);
    }

    /**
     * Delete wallet
     */
    public function deleteWallet(Request $request, $id)
    {
        $user = $request->user();
        $wallet = $user->cryptoWallets()->findOrFail($id);

        try {
            $this->walletService->deleteWallet($wallet);

            return redirect()->route('user.crypto-wallet.wallets')
                ->with('success', 'ลบกระเป๋าสำเร็จ');

        } catch (\Exception $e) {
            return back()->with('error', self::userMessage($e, 'ลบกระเป๋าไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'));
        }
    }

    /**
     * Set default wallet
     */
    public function setDefaultWallet(SetDefaultWalletRequest $request, $id)
    {
        $user = $request->user();
        $wallet = $user->cryptoWallets()->findOrFail($id);

        $wallet->setAsDefault();

        return back()->with('success', 'ตั้งเป็นกระเป๋าหลักสำเร็จ');
    }

    /**
     * Trading Dashboard - Premium crypto exchange interface
     */
    public function tradingDashboard(Request $request)
    {
        $user = $request->user();
        $wallet = $user->defaultCryptoWallet;

        if (! $wallet) {
            return redirect()->route('user.crypto-wallet.index')
                ->with('error', 'กรุณาสร้างกระเป๋าคริปโตก่อน');
        }

        // Get active trading pairs
        $currencies = CryptoCurrency::active()->ordered()->get();

        // Get user balances
        $balances = $this->walletService->getAllBalances($wallet);

        // Get current prices with 24h change
        $prices = [];
        foreach ($currencies as $currency) {
            $rate = $this->priceService->getCurrentRate($currency);
            if ($rate) {
                $prices[$currency->code] = [
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'price_thb' => $rate->rate_thb,
                    'buy_price' => $rate->buy_rate_thb,
                    'sell_price' => $rate->sell_rate_thb,
                    'change_24h' => $rate->price_change_24h,
                    'volume_24h' => $rate->volume_24h,
                ];
            }
        }

        return view('user.crypto-wallet.trading-dashboard', compact(
            'wallet',
            'currencies',
            'balances',
            'prices'
        ));
    }

    /**
     * Portfolio Management - Advanced portfolio analytics
     */
    public function portfolio(Request $request)
    {
        $user = $request->user();
        $wallet = $user->defaultCryptoWallet;

        if (! $wallet) {
            return redirect()->route('user.crypto-wallet.index')
                ->with('error', 'กรุณาสร้างกระเป๋าคริปโตก่อน');
        }

        // Get balances with detailed metrics
        $balances = $this->walletService->getAllBalances($wallet);

        // มูลค่ารวมจากยอดจริง — ระบบยังไม่เก็บต้นทุนการซื้อ จึงไม่แสดงกำไร/ขาดทุน
        // (เดิมสมมติต้นทุน = มูลค่า × 0.85 แล้วโชว์ "กำไร" ปลอมให้สมาชิกเห็น)
        $totalValueTHB = 0;
        foreach ($balances as $balance) {
            $totalValueTHB += $balance['balance_thb'];
        }

        // Get recent transactions for activity feed
        $recentTransactions = $user->cryptoTransactions()
            ->with(['currency', 'cryptoWallet'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // จำนวนธุรกรรมทั้งหมดจริง — $recentTransactions ถูกจำกัดไว้ 20 รายการ นับจากตัวนั้นจะค้างที่ 20
        $totalTransactions = $user->cryptoTransactions()->count();

        // (เดิมมี getPortfolioPerformanceData() สุ่มตัวเลขกราฟผลตอบแทนด้วย rand() — ลบออก ระบบยังไม่เก็บมูลค่าย้อนหลัง)
        return view('user.crypto-wallet.portfolio', compact(
            'wallet',
            'balances',
            'totalValueTHB',
            'recentTransactions',
            'totalTransactions'
        ));
    }
}
