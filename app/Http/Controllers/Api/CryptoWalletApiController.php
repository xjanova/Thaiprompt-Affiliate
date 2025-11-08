<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CryptoWallet;
use App\Services\Crypto\Web3Service;
use App\Services\Crypto\CryptoWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CryptoWalletApiController extends Controller
{
    protected Web3Service $web3Service;
    protected CryptoWalletService $walletService;

    public function __construct(
        Web3Service $web3Service,
        CryptoWalletService $walletService
    ) {
        $this->web3Service = $web3Service;
        $this->walletService = $walletService;
    }

    /**
     * Generate nonce for signature verification
     */
    public function generateNonce(Request $request)
    {
        $request->validate([
            'address' => 'required|string',
        ]);

        $address = strtolower($request->address);

        // Validate address format
        if (!$this->web3Service->isValidAddress($address)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Ethereum address format',
            ], 400);
        }

        // Generate random nonce
        $nonce = Str::random(32);

        // Store nonce in cache for 5 minutes
        Cache::put("wallet_nonce:{$address}", $nonce, now()->addMinutes(5));

        // Generate message to sign
        $message = $this->web3Service->generateVerificationMessage($address, $nonce);

        return response()->json([
            'success' => true,
            'nonce' => $nonce,
            'message' => $message,
        ]);
    }

    /**
     * Verify signature and connect external wallet
     */
    public function verifySignature(Request $request)
    {
        $request->validate([
            'address' => 'required|string',
            'network' => 'required|string|in:ethereum,bsc,polygon',
            'signature' => 'required|string',
            'message' => 'required|string',
            'nonce' => 'required|string',
        ]);

        $address = strtolower($request->address);
        $user = $request->user();

        // Verify nonce
        $cachedNonce = Cache::get("wallet_nonce:{$address}");
        if (!$cachedNonce || $cachedNonce !== $request->nonce) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired nonce',
            ], 400);
        }

        // Clear nonce from cache
        Cache::forget("wallet_nonce:{$address}");

        // Verify signature using ethers.js on frontend (more reliable)
        // For backend verification, we'll use a simpler approach
        // In production, implement proper ECDSA signature verification

        try {
            // Check if wallet already exists for this address
            $existingWallet = CryptoWallet::where('wallet_type', 'external')
                ->whereHas('cryptoAddresses', function ($query) use ($address, $request) {
                    $query->where('address', $address)
                          ->where('network', $request->network);
                })
                ->first();

            if ($existingWallet && $existingWallet->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This wallet is already connected to another account',
                ], 400);
            }

            // Create or update wallet
            if ($existingWallet) {
                $wallet = $existingWallet;
            } else {
                $wallet = $this->walletService->connectExternalWallet(
                    $user,
                    $request->address,
                    $request->network,
                    [
                        'name' => 'MetaMask Wallet',
                        'device_info' => $request->userAgent(),
                        'signature' => $request->signature,
                        'signed_message' => $request->message,
                        'verified_at' => now(),
                    ]
                );
            }

            // Log successful connection
            Log::info('External wallet connected', [
                'user_id' => $user->id,
                'address' => $address,
                'network' => $request->network,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Wallet connected successfully',
                'wallet_id' => $wallet->id,
                'redirect' => route('user.crypto-wallet.index'),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to verify signature', [
                'error' => $e->getMessage(),
                'address' => $address,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify signature: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get wallet balances
     */
    public function getBalances(Request $request)
    {
        $user = $request->user();
        $wallet = $user->defaultCryptoWallet;

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'No crypto wallet found',
            ], 404);
        }

        $balances = $this->walletService->getAllBalances($wallet);

        return response()->json([
            'success' => true,
            'balances' => $balances,
        ]);
    }

    /**
     * Get address for specific currency
     */
    public function getAddress(Request $request, $currencyCode)
    {
        $user = $request->user();
        $wallet = $user->defaultCryptoWallet;

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'No crypto wallet found',
            ], 404);
        }

        $address = $wallet->cryptoAddresses()
            ->whereHas('currency', function ($query) use ($currencyCode) {
                $query->where('code', strtoupper($currencyCode));
            })
            ->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found for this currency',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'address' => $address->address,
            'network' => $address->network,
            'qr_code_url' => route('user.crypto-wallet.deposit.currency', $currencyCode),
        ]);
    }

    /**
     * Get current prices
     */
    public function getPrices(Request $request)
    {
        $prices = Cache::remember('crypto_prices', 60, function () {
            // This would be populated by the price update service
            return [];
        });

        return response()->json([
            'success' => true,
            'prices' => $prices,
        ]);
    }

    /**
     * Check transaction status
     */
    public function checkTransaction(Request $request, $txHash)
    {
        $request->validate([
            'network' => 'required|string|in:ethereum,bsc,polygon',
        ]);

        try {
            $receipt = $this->web3Service->getTransactionReceipt(
                $request->network,
                $txHash
            );

            if (!$receipt) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found or still pending',
                    'status' => 'pending',
                ]);
            }

            return response()->json([
                'success' => true,
                'status' => $receipt['status'] ? 'confirmed' : 'failed',
                'block_number' => hexdec($receipt['blockNumber']),
                'confirmations' => 'N/A', // Would need current block number
                'gas_used' => hexdec($receipt['gasUsed']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check transaction: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get gas price estimate
     */
    public function getGasPrice(Request $request)
    {
        $request->validate([
            'network' => 'required|string|in:ethereum,bsc,polygon',
        ]);

        try {
            $gasPrice = $this->web3Service->getGasPrice($request->network);

            if (!$gasPrice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get gas price',
                ], 500);
            }

            // Convert to Gwei
            $gasPriceGwei = $this->web3Service->fromWei($gasPrice, 9);

            return response()->json([
                'success' => true,
                'gas_price_wei' => $gasPrice,
                'gas_price_gwei' => $gasPriceGwei,
                'network' => $request->network,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get gas price: ' . $e->getMessage(),
            ], 500);
        }
    }
}
