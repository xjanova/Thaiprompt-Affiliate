<?php

namespace App\Services\Crypto;

use App\Models\CryptoDepositAddress;
use App\Models\CryptoTransaction;
use App\Models\CryptoCurrency;
use App\Models\CryptoWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DepositDetectionService
{
    protected Web3Service $web3Service;
    protected CryptoPriceService $priceService;
    protected CryptoWalletService $walletService;

    public function __construct(
        Web3Service $web3Service,
        CryptoPriceService $priceService,
        CryptoWalletService $walletService
    ) {
        $this->web3Service = $web3Service;
        $this->priceService = $priceService;
        $this->walletService = $walletService;
    }

    /**
     * Scan all deposit addresses for new deposits
     */
    public function scanForDeposits(): array
    {
        $results = [
            'scanned' => 0,
            'detected' => 0,
            'processed' => 0,
            'errors' => 0,
        ];

        // Get all active deposit addresses
        $depositAddresses = CryptoDepositAddress::where('is_active', true)
            ->with(['currency', 'wallet', 'user'])
            ->get();

        foreach ($depositAddresses as $depositAddress) {
            $results['scanned']++;

            try {
                $deposits = $this->checkAddressForDeposits($depositAddress);

                if (!empty($deposits)) {
                    $results['detected'] += count($deposits);

                    foreach ($deposits as $deposit) {
                        if ($this->processDeposit($depositAddress, $deposit)) {
                            $results['processed']++;
                        }
                    }
                }

            } catch (\Exception $e) {
                $results['errors']++;
                Log::error('Deposit scan failed', [
                    'address' => $depositAddress->address,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Check specific address for deposits
     */
    public function checkAddressForDeposits(CryptoDepositAddress $depositAddress): array
    {
        $currency = $depositAddress->currency;
        $network = $currency->network;

        // Get last scanned block for this address
        $lastScannedBlock = $this->getLastScannedBlock($depositAddress);
        $currentBlock = $this->web3Service->getCurrentBlockNumber($network);

        if ($lastScannedBlock >= $currentBlock) {
            return []; // Already up to date
        }

        $deposits = [];

        if ($currency->is_native) {
            // Check for native token deposits (ETH, BNB, MATIC)
            $deposits = $this->scanNativeTokenDeposits(
                $depositAddress->address,
                $network,
                $lastScannedBlock,
                $currentBlock
            );
        } else {
            // Check for ERC-20/BEP-20 token deposits
            $deposits = $this->scanERC20TokenDeposits(
                $depositAddress->address,
                $currency,
                $network,
                $lastScannedBlock,
                $currentBlock
            );
        }

        // Update last scanned block
        $this->updateLastScannedBlock($depositAddress, $currentBlock);

        return $deposits;
    }

    /**
     * Scan for native token deposits
     */
    protected function scanNativeTokenDeposits(
        string $address,
        string $network,
        int $fromBlock,
        int $toBlock
    ): array {
        $deposits = [];

        try {
            // Get transactions for this address
            $transactions = $this->web3Service->getTransactions(
                $network,
                $address,
                $fromBlock,
                $toBlock
            );

            foreach ($transactions as $tx) {
                // Only process incoming transactions
                if (strtolower($tx->to) !== strtolower($address)) {
                    continue;
                }

                $deposits[] = [
                    'tx_hash' => $tx->hash,
                    'from_address' => $tx->from,
                    'to_address' => $tx->to,
                    'amount' => $this->weiToEther($tx->value),
                    'block_number' => $tx->blockNumber,
                    'timestamp' => $tx->timestamp ?? time(),
                    'gas_used' => $tx->gas ?? 0,
                ];
            }

        } catch (\Exception $e) {
            Log::error('Failed to scan native token deposits', [
                'address' => $address,
                'network' => $network,
                'error' => $e->getMessage(),
            ]);
        }

        return $deposits;
    }

    /**
     * Scan for ERC-20/BEP-20 token deposits
     */
    protected function scanERC20TokenDeposits(
        string $address,
        CryptoCurrency $currency,
        string $network,
        int $fromBlock,
        int $toBlock
    ): array {
        $deposits = [];

        try {
            // Get Transfer events for this token contract
            $events = $this->web3Service->getTokenTransferEvents(
                $network,
                $currency->contract_address,
                $address, // recipient
                $fromBlock,
                $toBlock
            );

            foreach ($events as $event) {
                $deposits[] = [
                    'tx_hash' => $event->transactionHash,
                    'from_address' => $event->from,
                    'to_address' => $event->to,
                    'amount' => $this->tokenAmountToDecimal($event->value, $currency->decimals ?? 18),
                    'block_number' => $event->blockNumber,
                    'timestamp' => $event->timestamp ?? time(),
                    'gas_used' => 0,
                ];
            }

        } catch (\Exception $e) {
            Log::error('Failed to scan ERC-20 token deposits', [
                'address' => $address,
                'contract' => $currency->contract_address,
                'network' => $network,
                'error' => $e->getMessage(),
            ]);
        }

        return $deposits;
    }

    /**
     * Process detected deposit
     */
    protected function processDeposit(CryptoDepositAddress $depositAddress, array $depositData): bool
    {
        try {
            // Check if transaction already exists
            $existing = CryptoTransaction::where('tx_hash', $depositData['tx_hash'])
                ->where('type', 'deposit')
                ->first();

            if ($existing) {
                Log::info('Deposit already processed', [
                    'tx_hash' => $depositData['tx_hash'],
                ]);
                return false;
            }

            return DB::transaction(function () use ($depositAddress, $depositData) {
                $currency = $depositAddress->currency;
                $wallet = $depositAddress->wallet;
                $user = $depositAddress->user;

                // Get current block to calculate confirmations
                $currentBlock = $this->web3Service->getCurrentBlockNumber($currency->network);
                $confirmations = max(0, $currentBlock - $depositData['block_number']);

                // Create transaction record
                $transaction = CryptoTransaction::create([
                    'user_id' => $user->id,
                    'crypto_wallet_id' => $wallet->id,
                    'crypto_currency_id' => $currency->id,
                    'crypto_address_id' => $depositAddress->cryptoAddress->id ?? null,
                    'type' => 'deposit',
                    'from_address' => $depositData['from_address'],
                    'to_address' => $depositData['to_address'],
                    'amount' => $depositData['amount'],
                    'fee' => 0, // No fee for deposits
                    'tx_hash' => $depositData['tx_hash'],
                    'block_number' => $depositData['block_number'],
                    'confirmations' => $confirmations,
                    'required_confirmations' => $this->getRequiredConfirmations($currency),
                    'status' => $confirmations >= $this->getRequiredConfirmations($currency) ? 'confirmed' : 'pending',
                    'confirmed_at' => $confirmations >= $this->getRequiredConfirmations($currency) ? now() : null,
                ]);

                // If confirmed, credit wallet balance
                if ($transaction->status === 'confirmed') {
                    $this->creditWalletBalance($wallet, $currency, $depositData['amount'], $transaction);
                }

                // Send notification to user
                $this->notifyUserOfDeposit($user, $transaction);

                Log::info('Deposit processed', [
                    'tx_hash' => $depositData['tx_hash'],
                    'user_id' => $user->id,
                    'amount' => $depositData['amount'],
                    'currency' => $currency->code,
                    'confirmations' => $confirmations,
                ]);

                return true;
            });

        } catch (\Exception $e) {
            Log::error('Failed to process deposit', [
                'tx_hash' => $depositData['tx_hash'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Credit wallet balance
     */
    protected function creditWalletBalance(
        CryptoWallet $wallet,
        CryptoCurrency $currency,
        float $amount,
        CryptoTransaction $transaction
    ): void {
        // Get or create balance record
        $balance = $wallet->balances()->firstOrCreate(
            ['crypto_currency_id' => $currency->id],
            ['balance' => 0, 'locked_balance' => 0]
        );

        // Add to balance
        $balance->increment('balance', $amount);

        Log::info('Wallet balance credited', [
            'wallet_id' => $wallet->id,
            'currency' => $currency->code,
            'amount' => $amount,
            'new_balance' => $balance->fresh()->balance,
        ]);
    }

    /**
     * Get required confirmations for currency
     */
    protected function getRequiredConfirmations(CryptoCurrency $currency): int
    {
        return match($currency->network) {
            'ethereum' => 12,
            'bsc' => 15,
            'polygon' => 128,
            'bitcoin' => 6,
            default => 12,
        };
    }

    /**
     * Convert Wei to Ether
     */
    protected function weiToEther($wei): float
    {
        return bcdiv($wei, '1000000000000000000', 18);
    }

    /**
     * Convert token amount to decimal
     */
    protected function tokenAmountToDecimal($amount, int $decimals): float
    {
        return bcdiv($amount, bcpow('10', $decimals, 0), $decimals);
    }

    /**
     * Get last scanned block for address
     */
    protected function getLastScannedBlock(CryptoDepositAddress $depositAddress): int
    {
        $cacheKey = "deposit_scan_block:{$depositAddress->id}";

        return Cache::get($cacheKey, $depositAddress->last_scanned_block ?? 0);
    }

    /**
     * Update last scanned block for address
     */
    protected function updateLastScannedBlock(CryptoDepositAddress $depositAddress, int $blockNumber): void
    {
        $cacheKey = "deposit_scan_block:{$depositAddress->id}";

        Cache::put($cacheKey, $blockNumber, now()->addHours(24));

        $depositAddress->update([
            'last_scanned_block' => $blockNumber,
            'last_scanned_at' => now(),
        ]);
    }

    /**
     * Notify user of deposit
     */
    protected function notifyUserOfDeposit($user, CryptoTransaction $transaction): void
    {
        try {
            $user->notify(new \App\Notifications\CryptoDepositReceived($transaction));
        } catch (\Exception $e) {
            Log::warning('Failed to send deposit notification', [
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Monitor pending deposits and update confirmations
     */
    public function updatePendingDeposits(): array
    {
        $results = [
            'checked' => 0,
            'confirmed' => 0,
            'errors' => 0,
        ];

        $pendingDeposits = CryptoTransaction::where('type', 'deposit')
            ->where('status', 'pending')
            ->with(['currency', 'wallet'])
            ->get();

        foreach ($pendingDeposits as $deposit) {
            $results['checked']++;

            try {
                if ($this->updateDepositConfirmations($deposit)) {
                    $results['confirmed']++;
                }
            } catch (\Exception $e) {
                $results['errors']++;
                Log::error('Failed to update deposit confirmations', [
                    'transaction_id' => $deposit->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Update confirmations for a deposit
     */
    protected function updateDepositConfirmations(CryptoTransaction $deposit): bool
    {
        if (!$deposit->tx_hash) {
            return false;
        }

        $currency = $deposit->currency;
        $currentBlock = $this->web3Service->getCurrentBlockNumber($currency->network);
        $confirmations = max(0, $currentBlock - $deposit->block_number);

        $deposit->update(['confirmations' => $confirmations]);

        // Check if reached required confirmations
        if ($confirmations >= $deposit->required_confirmations) {
            $deposit->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            // Credit wallet balance if not already done
            if ($deposit->wallet) {
                $this->creditWalletBalance(
                    $deposit->wallet,
                    $currency,
                    $deposit->amount,
                    $deposit
                );
            }

            Log::info('Deposit confirmed', [
                'transaction_id' => $deposit->id,
                'confirmations' => $confirmations,
            ]);

            return true;
        }

        return false;
    }
}
