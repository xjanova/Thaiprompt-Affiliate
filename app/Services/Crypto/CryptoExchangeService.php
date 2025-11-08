<?php

namespace App\Services\Crypto;

use App\Models\User;
use App\Models\Wallet;
use App\Models\CryptoWallet;
use App\Models\CryptoCurrency;
use App\Models\CryptoExchangeTransaction;
use App\Models\CryptoTransaction;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CryptoExchangeService
{
    protected CryptoPriceService $priceService;
    protected WalletService $walletService;
    protected CryptoWalletService $cryptoWalletService;

    public function __construct(
        CryptoPriceService $priceService,
        WalletService $walletService,
        CryptoWalletService $cryptoWalletService
    ) {
        $this->priceService = $priceService;
        $this->walletService = $walletService;
        $this->cryptoWalletService = $cryptoWalletService;
    }

    /**
     * Buy crypto with THB (THB → Crypto)
     *
     * @param User $user
     * @param CryptoCurrency $currency
     * @param float $thbAmount
     * @param CryptoWallet|null $cryptoWallet
     * @param string|null $pin
     * @param array $options
     * @return CryptoExchangeTransaction
     */
    public function buyCrypto(
        User $user,
        CryptoCurrency $currency,
        float $thbAmount,
        ?CryptoWallet $cryptoWallet = null,
        ?string $pin = null,
        array $options = []
    ): CryptoExchangeTransaction {
        DB::beginTransaction();
        try {
            // Validate currency is active and exchange enabled
            if (!$currency->is_active || !$currency->exchange_enabled) {
                throw new \Exception('Currency exchange is not enabled');
            }

            // Check minimum exchange amount
            if ($thbAmount < $currency->min_exchange_amount) {
                throw new \Exception("Minimum exchange amount is {$currency->min_exchange_amount} THB");
            }

            // Get or create THB wallet
            $thbWallet = $this->walletService->getOrCreateWallet($user);

            // Verify PIN if provided
            if ($pin && !$thbWallet->verifyPin($pin)) {
                throw new \Exception('Invalid PIN');
            }

            // Check THB balance
            if ($thbWallet->balance < $thbAmount) {
                throw new \Exception('Insufficient THB balance');
            }

            // Get or create crypto wallet
            if (!$cryptoWallet) {
                $cryptoWallet = $this->cryptoWalletService->getOrCreateDefaultWallet($user);
            }

            // Get current exchange rate
            $rate = $this->priceService->getCurrentRate($currency);
            if (!$rate) {
                throw new \Exception('Failed to get exchange rate');
            }

            // Calculate crypto amount
            $cryptoAmount = $rate->calculateCryptoFromTHB($thbAmount);
            $feePercentage = $currency->exchange_fee_percentage;
            $feeAmount = $thbAmount * ($feePercentage / 100);

            // Create exchange transaction record
            $exchangeTx = CryptoExchangeTransaction::create([
                'user_id' => $user->id,
                'type' => 'buy',
                'from_currency_type' => 'fiat',
                'from_currency_code' => 'THB',
                'from_amount' => $thbAmount,
                'from_wallet_id' => $thbWallet->id,
                'to_currency_type' => 'crypto',
                'to_currency_code' => $currency->code,
                'to_amount' => $cryptoAmount,
                'to_crypto_wallet_id' => $cryptoWallet->id,
                'exchange_rate' => $rate->buy_rate_thb,
                'crypto_exchange_rate_id' => $rate->id,
                'fee_percentage' => $feePercentage,
                'fee_amount' => $feeAmount,
                'fee_currency' => 'THB',
                'final_from_amount' => $thbAmount,
                'final_to_amount' => $cryptoAmount,
                'status' => 'pending',
                'max_slippage_percentage' => $options['max_slippage'] ?? 1.0,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'description' => "Buy {$cryptoAmount} {$currency->code} with {$thbAmount} THB",
            ]);

            // Deduct THB from wallet
            $walletTx = $this->walletService->withdraw(
                $thbWallet,
                $thbAmount,
                $pin,
                'exchange_buy',
                "Exchange: Buy {$currency->code}",
                [
                    'exchange_transaction_id' => $exchangeTx->id,
                    'crypto_currency' => $currency->code,
                    'crypto_amount' => $cryptoAmount,
                ]
            );

            // Link wallet transaction
            $exchangeTx->update(['wallet_transaction_id' => $walletTx->id]);

            // Create crypto transaction (for balance tracking)
            $cryptoTx = CryptoTransaction::create([
                'user_id' => $user->id,
                'crypto_wallet_id' => $cryptoWallet->id,
                'crypto_currency_id' => $currency->id,
                'type' => 'exchange_buy',
                'amount' => $cryptoAmount,
                'fee' => 0, // Fee already deducted in THB
                'amount_thb' => $thbAmount,
                'exchange_rate' => $rate->buy_rate_thb,
                'status' => 'completed', // Exchange is instant
                'confirmations' => 999, // Internal transaction
                'confirmations_required' => 0,
                'wallet_transaction_id' => $walletTx->id,
                'description' => "Bought {$cryptoAmount} {$currency->code}",
                'network' => $currency->network,
                'completed_at' => now(),
            ]);

            // Link crypto transaction
            $exchangeTx->update(['crypto_transaction_id' => $cryptoTx->id]);

            // Update crypto address balance
            $address = $cryptoWallet->cryptoAddresses()
                ->where('crypto_currency_id', $currency->id)
                ->first();

            if ($address) {
                $address->updateBalanceFromBlockchain($address->balance + $cryptoAmount);
            }

            // Mark exchange as completed
            $exchangeTx->markAsCompleted();

            DB::commit();

            Log::info('Crypto purchase completed', [
                'user_id' => $user->id,
                'exchange_id' => $exchangeTx->exchange_id,
                'currency' => $currency->code,
                'thb_amount' => $thbAmount,
                'crypto_amount' => $cryptoAmount,
            ]);

            return $exchangeTx;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Crypto purchase failed', [
                'user_id' => $user->id,
                'currency' => $currency->code ?? null,
                'thb_amount' => $thbAmount,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sell crypto for THB (Crypto → THB)
     *
     * @param User $user
     * @param CryptoCurrency $currency
     * @param float $cryptoAmount
     * @param CryptoWallet|null $cryptoWallet
     * @param string|null $pin
     * @param array $options
     * @return CryptoExchangeTransaction
     */
    public function sellCrypto(
        User $user,
        CryptoCurrency $currency,
        float $cryptoAmount,
        ?CryptoWallet $cryptoWallet = null,
        ?string $pin = null,
        array $options = []
    ): CryptoExchangeTransaction {
        DB::beginTransaction();
        try {
            // Validate currency is active and exchange enabled
            if (!$currency->is_active || !$currency->exchange_enabled) {
                throw new \Exception('Currency exchange is not enabled');
            }

            // Get or create crypto wallet
            if (!$cryptoWallet) {
                $cryptoWallet = $this->cryptoWalletService->getOrCreateDefaultWallet($user);
            }

            // Verify crypto wallet PIN if custodial
            if ($cryptoWallet->isCustodial() && $pin && !$cryptoWallet->verifyPin($pin)) {
                throw new \Exception('Invalid crypto wallet PIN');
            }

            // Check crypto balance
            $balance = $this->cryptoWalletService->getBalance($cryptoWallet, $currency);
            if ($balance < $cryptoAmount) {
                throw new \Exception('Insufficient crypto balance');
            }

            // Get or create THB wallet
            $thbWallet = $this->walletService->getOrCreateWallet($user);

            // Get current exchange rate
            $rate = $this->priceService->getCurrentRate($currency);
            if (!$rate) {
                throw new \Exception('Failed to get exchange rate');
            }

            // Calculate THB amount
            $thbAmount = $rate->calculateSellPrice($cryptoAmount);
            $feePercentage = $currency->exchange_fee_percentage;
            $feeAmount = $thbAmount * ($feePercentage / 100);
            $finalThbAmount = $thbAmount - $feeAmount;

            // Check minimum exchange amount
            if ($finalThbAmount < $currency->min_exchange_amount) {
                throw new \Exception("Minimum exchange amount is {$currency->min_exchange_amount} THB");
            }

            // Create exchange transaction record
            $exchangeTx = CryptoExchangeTransaction::create([
                'user_id' => $user->id,
                'type' => 'sell',
                'from_currency_type' => 'crypto',
                'from_currency_code' => $currency->code,
                'from_amount' => $cryptoAmount,
                'from_crypto_wallet_id' => $cryptoWallet->id,
                'to_currency_type' => 'fiat',
                'to_currency_code' => 'THB',
                'to_amount' => $thbAmount,
                'to_wallet_id' => $thbWallet->id,
                'exchange_rate' => $rate->sell_rate_thb,
                'crypto_exchange_rate_id' => $rate->id,
                'fee_percentage' => $feePercentage,
                'fee_amount' => $feeAmount,
                'fee_currency' => 'THB',
                'final_from_amount' => $cryptoAmount,
                'final_to_amount' => $finalThbAmount,
                'status' => 'pending',
                'max_slippage_percentage' => $options['max_slippage'] ?? 1.0,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'description' => "Sell {$cryptoAmount} {$currency->code} for {$finalThbAmount} THB",
            ]);

            // Deduct crypto from balance
            $address = $cryptoWallet->cryptoAddresses()
                ->where('crypto_currency_id', $currency->id)
                ->first();

            if ($address) {
                $address->updateBalanceFromBlockchain($address->balance - $cryptoAmount);
            }

            // Create crypto transaction (for history)
            $cryptoTx = CryptoTransaction::create([
                'user_id' => $user->id,
                'crypto_wallet_id' => $cryptoWallet->id,
                'crypto_currency_id' => $currency->id,
                'type' => 'exchange_sell',
                'amount' => $cryptoAmount,
                'fee' => 0,
                'amount_thb' => $thbAmount,
                'exchange_rate' => $rate->sell_rate_thb,
                'status' => 'completed',
                'confirmations' => 999,
                'confirmations_required' => 0,
                'description' => "Sold {$cryptoAmount} {$currency->code} for THB",
                'network' => $currency->network,
                'completed_at' => now(),
            ]);

            // Add THB to wallet
            $walletTx = $this->walletService->deposit(
                $thbWallet,
                $finalThbAmount,
                'exchange_sell',
                "Exchange: Sell {$currency->code}",
                null,
                [
                    'exchange_transaction_id' => $exchangeTx->id,
                    'crypto_currency' => $currency->code,
                    'crypto_amount' => $cryptoAmount,
                    'fee_deducted' => $feeAmount,
                ]
            );

            // Link transactions
            $exchangeTx->update([
                'wallet_transaction_id' => $walletTx->id,
                'crypto_transaction_id' => $cryptoTx->id,
            ]);

            // Mark exchange as completed
            $exchangeTx->markAsCompleted();

            DB::commit();

            Log::info('Crypto sale completed', [
                'user_id' => $user->id,
                'exchange_id' => $exchangeTx->exchange_id,
                'currency' => $currency->code,
                'crypto_amount' => $cryptoAmount,
                'thb_amount' => $finalThbAmount,
                'fee' => $feeAmount,
            ]);

            return $exchangeTx;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Crypto sale failed', [
                'user_id' => $user->id,
                'currency' => $currency->code ?? null,
                'crypto_amount' => $cryptoAmount,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get exchange preview
     *
     * @param CryptoCurrency $currency
     * @param float $amount
     * @param string $type ('buy' or 'sell')
     * @param string $fromCurrency
     * @return array|null
     */
    public function getExchangePreview(
        CryptoCurrency $currency,
        float $amount,
        string $type,
        string $fromCurrency = 'THB'
    ): ?array {
        return $this->priceService->calculateExchangePreview(
            $currency,
            $amount,
            $fromCurrency,
            $type
        );
    }

    /**
     * Get user's exchange history
     *
     * @param User $user
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getExchangeHistory(User $user, array $filters = [])
    {
        $query = CryptoExchangeTransaction::where('user_id', $user->id);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['currency'])) {
            $query->currency($filters['currency']);
        }

        return $query->with(['fromWallet', 'toWallet', 'fromCryptoWallet', 'toCryptoWallet'])
            ->recent()
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Cancel exchange (if still pending)
     *
     * @param CryptoExchangeTransaction $exchange
     * @param User $user
     * @return bool
     */
    public function cancelExchange(CryptoExchangeTransaction $exchange, User $user): bool
    {
        if ($exchange->user_id !== $user->id) {
            throw new \Exception('Unauthorized');
        }

        if (!$exchange->isPending()) {
            throw new \Exception('Can only cancel pending exchanges');
        }

        DB::beginTransaction();
        try {
            // Refund THB or Crypto depending on type
            if ($exchange->type === 'buy' && $exchange->walletTransaction) {
                // Refund THB
                $this->walletService->refund(
                    $exchange->walletTransaction,
                    auth()->user(), // Admin user for refund
                    'Exchange cancelled by user'
                );
            } elseif ($exchange->type === 'sell' && $exchange->cryptoTransaction) {
                // Refund Crypto (restore balance)
                $address = $exchange->fromCryptoWallet->cryptoAddresses()
                    ->where('crypto_currency_id', $exchange->cryptoTransaction->crypto_currency_id)
                    ->first();

                if ($address) {
                    $address->updateBalanceFromBlockchain(
                        $address->balance + $exchange->from_amount
                    );
                }
            }

            $exchange->update([
                'status' => 'cancelled',
            ]);

            DB::commit();

            Log::info('Exchange cancelled', [
                'exchange_id' => $exchange->exchange_id,
                'user_id' => $user->id,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to cancel exchange', [
                'exchange_id' => $exchange->exchange_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
