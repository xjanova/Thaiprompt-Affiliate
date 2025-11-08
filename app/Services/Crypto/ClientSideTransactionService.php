<?php

namespace App\Services\Crypto;

use App\Models\CryptoTransaction;
use App\Models\CryptoWallet;
use App\Models\CryptoCurrency;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Client-Side Transaction Service
 *
 * Handles cryptocurrency transactions that are signed client-side
 * (via MetaMask, WalletConnect, etc.) - Much more secure than server-side signing!
 *
 * Flow:
 * 1. Backend prepares transaction data
 * 2. Frontend requests user signature via MetaMask
 * 3. Backend receives signed transaction
 * 4. Backend broadcasts to blockchain
 * 5. Backend tracks confirmation status
 */
class ClientSideTransactionService
{
    protected Web3Service $web3Service;
    protected CryptoPriceService $priceService;

    public function __construct(
        Web3Service $web3Service,
        CryptoPriceService $priceService
    ) {
        $this->web3Service = $web3Service;
        $this->priceService = $priceService;
    }

    /**
     * Prepare transaction data for client-side signing
     *
     * @param CryptoWallet $wallet
     * @param string $toAddress
     * @param float $amount
     * @param CryptoCurrency $currency
     * @param string $network
     * @return array Transaction data ready for MetaMask
     */
    public function prepareTransaction(
        CryptoWallet $wallet,
        string $toAddress,
        float $amount,
        CryptoCurrency $currency,
        string $network = 'ethereum'
    ): array {
        try {
            // Validate balance
            $balance = $this->getWalletBalance($wallet, $currency);
            if ($balance < $amount) {
                throw new \Exception('Insufficient balance');
            }

            // Get wallet address
            $fromAddress = $wallet->cryptoAddresses()
                ->whereHas('currency', fn($q) => $q->where('network', $network))
                ->first();

            if (!$fromAddress) {
                throw new \Exception('No address found for this network');
            }

            // Get current gas price
            $gasPrice = $this->web3Service->getGasPrice($network);

            // Get nonce
            $nonce = $this->web3Service->getNonce($network, $fromAddress->address);

            // Estimate gas fee
            $gasFee = $this->estimateGasFee($network, $currency);

            if ($currency->is_native) {
                // Native token transfer (ETH, BNB, MATIC)
                $transactionData = $this->prepareNativeTransfer(
                    $fromAddress->address,
                    $toAddress,
                    $amount,
                    $gasPrice,
                    $nonce,
                    $network
                );
            } else {
                // ERC-20/BEP-20 token transfer
                $transactionData = $this->prepareTokenTransfer(
                    $fromAddress->address,
                    $toAddress,
                    $amount,
                    $currency,
                    $gasPrice,
                    $nonce,
                    $network
                );
            }

            // Create pending transaction record
            $transaction = $this->createPendingTransaction(
                $wallet,
                $currency,
                $fromAddress->address,
                $toAddress,
                $amount,
                $gasFee,
                'withdrawal'
            );

            return [
                'success' => true,
                'transaction_id' => $transaction->id,
                'transaction_data' => $transactionData,
                'estimated_gas_fee' => $gasFee,
                'estimated_gas_fee_native' => $this->calculateGasFeeInNative($gasPrice, $transactionData['gas'], $currency->decimals ?? 18),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to prepare transaction', [
                'wallet_id' => $wallet->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Prepare native token transfer data
     */
    protected function prepareNativeTransfer(
        string $from,
        string $to,
        float $amount,
        string $gasPrice,
        int $nonce,
        string $network
    ): array {
        // Convert amount to Wei
        $amountWei = $this->web3Service->toWei((string)$amount, 18);

        return [
            'from' => $from,
            'to' => $to,
            'value' => '0x' . dechex((int)$amountWei),
            'gas' => '0x5208', // 21000 gas for simple transfer
            'gasPrice' => '0x' . dechex((int)$gasPrice),
            'nonce' => '0x' . dechex($nonce),
            'chainId' => $this->web3Service->getChainId($network),
        ];
    }

    /**
     * Prepare ERC-20/BEP-20 token transfer data
     */
    protected function prepareTokenTransfer(
        string $from,
        string $to,
        float $amount,
        CryptoCurrency $currency,
        string $gasPrice,
        int $nonce,
        string $network
    ): array {
        // Convert amount based on token decimals
        $decimals = $currency->decimals ?? 18;
        $amountInSmallestUnit = bcmul((string)$amount, bcpow('10', (string)$decimals, 0), 0);

        // ERC-20 transfer function signature
        $functionSignature = '0xa9059cbb'; // transfer(address,uint256)

        // Encode parameters
        $toAddressEncoded = str_pad(substr($to, 2), 64, '0', STR_PAD_LEFT);
        $amountEncoded = str_pad(dechex((int)$amountInSmallestUnit), 64, '0', STR_PAD_LEFT);

        $data = $functionSignature . $toAddressEncoded . $amountEncoded;

        return [
            'from' => $from,
            'to' => $currency->contract_address,
            'value' => '0x0',
            'gas' => '0x' . dechex(100000), // 100k gas for ERC-20 transfer
            'gasPrice' => '0x' . dechex((int)$gasPrice),
            'nonce' => '0x' . dechex($nonce),
            'data' => $data,
            'chainId' => $this->web3Service->getChainId($network),
        ];
    }

    /**
     * Broadcast signed transaction to blockchain
     *
     * @param int $transactionId Our database transaction ID
     * @param string $signedTx Signed transaction from client
     * @param string $network Network name
     * @return array Result
     */
    public function broadcastTransaction(
        int $transactionId,
        string $signedTx,
        string $network
    ): array {
        try {
            $transaction = CryptoTransaction::findOrFail($transactionId);

            // Validate transaction is in pending state
            if ($transaction->status !== 'processing') {
                throw new \Exception('Transaction is not in pending state');
            }

            $web3 = $this->web3Service->getWeb3($network);

            // Send raw transaction
            $txHash = null;
            $web3->eth->sendRawTransaction($signedTx, function ($err, $hash) use (&$txHash) {
                if ($err !== null) {
                    throw new \Exception('Failed to broadcast transaction: ' . $err->getMessage());
                }
                $txHash = $hash;
            });

            if (!$txHash) {
                throw new \Exception('No transaction hash returned');
            }

            // Update transaction with hash
            $transaction->update([
                'tx_hash' => $txHash,
                'status' => 'pending',
                'confirmed_at' => null,
            ]);

            Log::info('Transaction broadcasted successfully', [
                'transaction_id' => $transactionId,
                'tx_hash' => $txHash,
                'network' => $network,
            ]);

            return [
                'success' => true,
                'tx_hash' => $txHash,
                'explorer_url' => $this->web3Service->getExplorerTxUrl($network, $txHash),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to broadcast transaction', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            if (isset($transaction)) {
                $transaction->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get wallet balance
     */
    protected function getWalletBalance(CryptoWallet $wallet, CryptoCurrency $currency): float
    {
        $balance = $wallet->balances()
            ->where('crypto_currency_id', $currency->id)
            ->first();

        return $balance ? $balance->balance : 0;
    }

    /**
     * Estimate gas fee for transaction
     */
    protected function estimateGasFee(string $network, CryptoCurrency $currency): float
    {
        try {
            $gasPrice = $this->web3Service->getGasPrice($network);

            // Estimate gas limit based on transaction type
            $gasLimit = $currency->is_native ? 21000 : 100000;

            // Calculate fee in native token
            $feeWei = bcmul((string)$gasPrice, (string)$gasLimit, 0);
            $fee = $this->web3Service->fromWei($feeWei, 18);

            // Get native token price in THB
            $nativeToken = $this->getNativeTokenForNetwork($network);
            if (!$nativeToken) {
                return 50; // Default fallback
            }

            $rate = $this->priceService->getCurrentRate($nativeToken);

            return (float)$fee * ($rate->rate_thb ?? 0);

        } catch (\Exception $e) {
            Log::warning('Failed to estimate gas fee', [
                'network' => $network,
                'error' => $e->getMessage(),
            ]);

            return 50; // 50 THB default
        }
    }

    /**
     * Calculate gas fee in native currency
     */
    protected function calculateGasFeeInNative(string $gasPrice, string $gasLimit, int $decimals): string
    {
        $gasPriceInt = hexdec(str_replace('0x', '', $gasPrice));
        $gasLimitInt = hexdec(str_replace('0x', '', $gasLimit));

        $totalWei = bcmul((string)$gasPriceInt, (string)$gasLimitInt, 0);
        return $this->web3Service->fromWei($totalWei, $decimals);
    }

    /**
     * Create pending transaction record
     */
    protected function createPendingTransaction(
        CryptoWallet $wallet,
        CryptoCurrency $currency,
        string $fromAddress,
        string $toAddress,
        float $amount,
        float $fee,
        string $type
    ): CryptoTransaction {
        return CryptoTransaction::create([
            'user_id' => $wallet->user_id,
            'crypto_wallet_id' => $wallet->id,
            'crypto_currency_id' => $currency->id,
            'type' => $type,
            'from_address' => $fromAddress,
            'to_address' => $toAddress,
            'amount' => $amount,
            'fee' => $fee,
            'status' => 'processing',
            'tx_hash' => null,
            'confirmations' => 0,
            'required_confirmations' => $this->getRequiredConfirmations($currency),
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
     * Get native token for network
     */
    protected function getNativeTokenForNetwork(string $network): ?CryptoCurrency
    {
        $codes = [
            'ethereum' => 'ETH',
            'bsc' => 'BNB',
            'polygon' => 'MATIC',
            'bitcoin' => 'BTC',
        ];

        if (!isset($codes[$network])) {
            return null;
        }

        return CryptoCurrency::where('code', $codes[$network])->first();
    }

    /**
     * Check transaction status on blockchain
     */
    public function checkTransactionStatus(string $txHash, string $network): array
    {
        try {
            $receipt = $this->web3Service->getTransactionReceipt($network, $txHash);

            if (!$receipt) {
                return [
                    'status' => 'pending',
                    'confirmations' => 0,
                ];
            }

            $currentBlock = $this->web3Service->getCurrentBlockNumber($network);
            $txBlock = isset($receipt['blockNumber']) ? hexdec($receipt['blockNumber']) : 0;
            $confirmations = $currentBlock - $txBlock;

            $status = isset($receipt['status']) && ($receipt['status'] === '0x1' || $receipt['status'] === 1) ? 'confirmed' : 'failed';

            return [
                'status' => $status,
                'confirmations' => $confirmations,
                'block_number' => $txBlock,
                'gas_used' => isset($receipt['gasUsed']) ? hexdec($receipt['gasUsed']) : 0,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to check transaction status', [
                'tx_hash' => $txHash,
                'network' => $network,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'unknown',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update transaction confirmations
     */
    public function updateTransactionConfirmations(CryptoTransaction $transaction): bool
    {
        if (!$transaction->tx_hash) {
            return false;
        }

        $currency = $transaction->currency;
        $status = $this->checkTransactionStatus($transaction->tx_hash, $currency->network);

        $transaction->update([
            'confirmations' => $status['confirmations'] ?? 0,
            'status' => $status['status'] ?? $transaction->status,
        ]);

        // Mark as confirmed if reached required confirmations
        if (($status['confirmations'] ?? 0) >= $transaction->required_confirmations) {
            $transaction->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            return true;
        }

        return false;
    }
}
