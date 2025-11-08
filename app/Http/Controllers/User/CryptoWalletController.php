<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crypto\CreateCustodialWalletRequest;
use App\Http\Requests\Crypto\ConnectExternalWalletRequest;
use App\Http\Requests\Crypto\WithdrawCryptoRequest;
use App\Http\Requests\Crypto\SetDefaultWalletRequest;
use App\Models\CryptoCurrency;
use App\Models\CryptoWallet;
use App\Models\CryptoWithdrawalRequest;
use App\Services\Crypto\CryptoWalletService;
use App\Services\Crypto\CryptoPriceService;
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

        return view('user.crypto-wallet.index', compact(
            'wallet',
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

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
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
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
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

        if (!$depositAddress) {
            // Generate new deposit address
            $cryptoAddress = $this->walletService->generateAddressForCurrency($wallet, $currency);
            $depositAddress = $cryptoAddress->wallet->depositAddresses()
                ->where('crypto_currency_id', $currency->id)
                ->first();
        }

        return view('user.crypto-wallet.deposit-currency', compact(
            'wallet',
            'currency',
            'depositAddress'
        ));
    }

    /**
     * Withdraw page
     */
    public function withdraw(Request $request)
    {
        $user = $request->user();
        $wallet = $user->defaultCryptoWallet;

        if (!$wallet) {
            return redirect()->route('user.crypto-wallet.index')
                ->with('error', 'กรุณาสร้างกระเป๋าคริปโตก่อน');
        }

        $currencies = CryptoCurrency::withdrawalEnabled()->ordered()->get();
        $balances = $this->walletService->getAllBalances($wallet);

        return view('user.crypto-wallet.withdraw', compact(
            'wallet',
            'currencies',
            'balances'
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
        if (!$wallet->verifyPin($request->pin)) {
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

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Withdrawal history
     */
    public function withdrawals(Request $request)
    {
        $user = $request->user();

        $withdrawals = $user->cryptoWithdrawalRequests()
            ->with(['currency', 'cryptoWallet'])
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

        if (!$withdrawal->isPending()) {
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

        // Filters
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('currency')) {
            $currency = CryptoCurrency::where('code', $request->currency)->first();
            if ($currency) {
                $query->where('crypto_currency_id', $currency->id);
            }
        }

        if ($request->has('status')) {
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

        if (!$rate) {
            return response()->json([
                'error' => 'Price not available'
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
            return back()->with('error', $e->getMessage());
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
}
