<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CryptoCurrency;
use App\Services\Crypto\CryptoExchangeService;
use App\Services\Crypto\CryptoPriceService;
use App\Services\Crypto\CryptoWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CryptoExchangeController extends Controller
{
    protected CryptoExchangeService $exchangeService;
    protected CryptoPriceService $priceService;
    protected CryptoWalletService $walletService;

    public function __construct(
        CryptoExchangeService $exchangeService,
        CryptoPriceService $priceService,
        CryptoWalletService $walletService
    ) {
        $this->exchangeService = $exchangeService;
        $this->priceService = $priceService;
        $this->walletService = $walletService;
    }

    /**
     * Exchange page (THB ↔ Crypto)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $thbWallet = $user->wallet;
        $cryptoWallet = $user->defaultCryptoWallet;

        // Get all exchange-enabled currencies
        $currencies = CryptoCurrency::exchangeEnabled()->ordered()->get();

        // Get crypto balances
        $cryptoBalances = [];
        if ($cryptoWallet) {
            $cryptoBalances = $this->walletService->getAllBalances($cryptoWallet);
        }

        // Get prices for all currencies
        $prices = [];
        foreach ($currencies as $currency) {
            $rate = $this->priceService->getCurrentRate($currency);
            if ($rate) {
                $prices[$currency->code] = [
                    'buy_rate' => $rate->buy_rate_thb,
                    'sell_rate' => $rate->sell_rate_thb,
                    'change_24h' => $rate->price_change_24h,
                ];
            }
        }

        return view('user.crypto-wallet.exchange', compact(
            'thbWallet',
            'cryptoWallet',
            'currencies',
            'cryptoBalances',
            'prices'
        ));
    }

    /**
     * Preview exchange (AJAX)
     */
    public function preview(Request $request)
    {
        $request->validate([
            'currency_id' => 'required|exists:crypto_currencies,id',
            'amount' => 'required|numeric|min:0.000001',
            'type' => 'required|in:buy,sell',
        ]);

        $currency = CryptoCurrency::findOrFail($request->currency_id);

        try {
            $preview = $this->exchangeService->getExchangePreview(
                $currency,
                $request->amount,
                $request->type,
                $request->type === 'buy' ? 'THB' : $currency->code
            );

            if (!$preview) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถดึงข้อมูลราคาได้ กรุณาลองใหม่อีกครั้ง',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'preview' => $preview,
            ]);

        } catch (\Exception $e) {
            Log::error('Exchange preview error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Buy crypto with THB
     */
    public function buyCrypto(Request $request)
    {
        $request->validate([
            'currency_id' => 'required|exists:crypto_currencies,id',
            'thb_amount' => 'required|numeric|min:1',
            'pin' => 'required|string',
            'accept_terms' => 'accepted',
        ]);

        $user = $request->user();
        $currency = CryptoCurrency::findOrFail($request->currency_id);

        try {
            $exchange = $this->exchangeService->buyCrypto(
                $user,
                $currency,
                $request->thb_amount,
                null, // Use default crypto wallet
                $request->pin,
                [
                    'max_slippage' => $request->max_slippage ?? 1.0,
                ]
            );

            return redirect()->route('user.crypto-wallet.exchange.history')
                ->with('success', sprintf(
                    'ซื้อ %s %s สำเร็จ! ใช้ %s บาท',
                    number_format($exchange->to_amount, 8),
                    $currency->code,
                    number_format($exchange->from_amount, 2)
                ));

        } catch (\Exception $e) {
            Log::error('Buy crypto failed', [
                'user_id' => $user->id,
                'currency' => $currency->code,
                'amount' => $request->thb_amount,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Sell crypto for THB
     */
    public function sellCrypto(Request $request)
    {
        $request->validate([
            'currency_id' => 'required|exists:crypto_currencies,id',
            'crypto_amount' => 'required|numeric|min:0.00000001',
            'pin' => 'required|string',
            'accept_terms' => 'accepted',
        ]);

        $user = $request->user();
        $currency = CryptoCurrency::findOrFail($request->currency_id);

        try {
            $exchange = $this->exchangeService->sellCrypto(
                $user,
                $currency,
                $request->crypto_amount,
                null, // Use default crypto wallet
                $request->pin,
                [
                    'max_slippage' => $request->max_slippage ?? 1.0,
                ]
            );

            return redirect()->route('user.crypto-wallet.exchange.history')
                ->with('success', sprintf(
                    'ขาย %s %s สำเร็จ! ได้รับ %s บาท',
                    number_format($exchange->from_amount, 8),
                    $currency->code,
                    number_format($exchange->final_to_amount, 2)
                ));

        } catch (\Exception $e) {
            Log::error('Sell crypto failed', [
                'user_id' => $user->id,
                'currency' => $currency->code,
                'amount' => $request->crypto_amount,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Exchange history
     */
    public function history(Request $request)
    {
        $user = $request->user();

        $query = $user->cryptoExchangeTransactions()
            ->with([
                'fromWallet',
                'toWallet',
                'fromCryptoWallet',
                'toCryptoWallet',
            ]);

        // Filters
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('currency')) {
            $query->currency($request->currency);
        }

        $exchanges = $query->orderBy('created_at', 'desc')->paginate(20);

        $currencies = CryptoCurrency::exchangeEnabled()->ordered()->get();

        return view('user.crypto-wallet.exchange-history', compact('exchanges', 'currencies'));
    }
}
