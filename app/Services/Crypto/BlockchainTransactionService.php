<?php

namespace App\Services\Crypto;

use App\Models\CryptoTransaction;
use App\Models\CryptoWallet;
use App\Models\CryptoCurrency;
use App\Models\CryptoAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BlockchainTransactionService
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
     * Send cryptocurrency to external address
     *
     * @param CryptoWallet $wallet
     * @param string $toAddress
     * @param float $amount
     * @param CryptoCurrency $currency
     * @param string $network
     * @return array
     */
    public function sendTransaction(
        CryptoWallet $wallet,
        string $toAddress,
        float $amount,
        CryptoCurrency $currency,
        string $network = 'ethereum'
    ): array {
        try {
            // Validate wallet has sufficient balance
            $balance = $this->getWalletBalance($wallet, $currency);
            if ($balance < $amount) {
                throw new \Exception('Insufficient balance');
            }

            // Estimate gas fee
            $gasFee = $this->estimateGasFee($network, $currency);

            // Get wallet's private key (for custodial wallets only)
            if ($wallet->type !== 'custodial') {
                throw new \Exception('Can only send from custodial wallets');
            }

            $fromAddress = $wallet->cryptoAddresses()
                ->whereHas('currency', fn($q) => $q->where('network', $network))
                ->first();

            if (!$fromAddress) {
                throw new \Exception('No address found for this network');
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

            // Execute blockchain transaction
            $txHash = $this->executeBlockchainTransaction(
                $fromAddress,
                $toAddress,
                $amount,
                $currency,
                $network
            );

            // Update transaction with hash
            $transaction->update([
                'tx_hash' => $txHash,
                'status' => 'pending',
                'confirmed_at' => null,
            ]);

            Log::info('Blockchain transaction sent', [
                'tx_hash' => $txHash,
                'from' => $fromAddress->address,
                'to' => $toAddress,
                'amount' => $amount,
                'currency' => $currency->code,
            ]);

            return [
                'success' => true,
                'tx_hash' => $txHash,
                'transaction_id' => $transaction->id,
                'gas_fee' => $gasFee,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send blockchain transaction', [
                'wallet_id' => $wallet->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
     * Execute actual blockchain transaction
     */
    protected function executeBlockchainTransaction(
        CryptoAddress $fromAddress,
        string $toAddress,
        float $amount,
        CryptoCurrency $currency,
        string $network
    ): string {
        // Get private key from encrypted storage
        $privateKey = $this->getPrivateKey($fromAddress);

        if ($currency->is_native) {
            // Send native currency (ETH, BNB, MATIC, etc.)
            return $this->sendNativeToken(
                $privateKey,
                $toAddress,
                $amount,
                $network
            );
        } else {
            // Send ERC-20/BEP-20 token
            return $this->sendERC20Token(
                $privateKey,
                $toAddress,
                $amount,
                $currency,
                $network
            );
        }
    }

    /**
     * Send native blockchain token (ETH, BNB, etc.)
     */
    protected function sendNativeToken(
        string $privateKey,
        string $toAddress,
        float $amount,
        string $network
    ): string {
        $web3 = $this->web3Service->getWeb3($network);

        // Convert amount to Wei
        $amountWei = bcmul($amount, '1000000000000000000', 0);

        // Get nonce
        $nonce = $this->web3Service->getNonce($network, $this->getAddressFromPrivateKey($privateKey));

        // Get gas price
        $gasPrice = $this->web3Service->getGasPrice($network);

        // Build transaction
        $transaction = [
            'from' => $this->getAddressFromPrivateKey($privateKey),
            'to' => $toAddress,
            'value' => '0x' . dechex($amountWei),
            'gas' => '0x5208', // 21000 gas for simple transfer
            'gasPrice' => '0x' . dechex($gasPrice),
            'nonce' => '0x' . dechex($nonce),
        ];

        // Sign transaction
        $signedTx = $this->signTransaction($transaction, $privateKey);

        // Send raw transaction
        $txHash = $web3->eth->sendRawTransaction('0x' . $signedTx, function ($err, $hash) {
            if ($err !== null) {
                throw new \Exception('Failed to send transaction: ' . $err->getMessage());
            }
        });

        return $txHash;
    }

    /**
     * Send ERC-20/BEP-20 token
     */
    protected function sendERC20Token(
        string $privateKey,
        string $toAddress,
        float $amount,
        CryptoCurrency $currency,
        string $network
    ): string {
        $web3 = $this->web3Service->getWeb3($network);

        // Convert amount based on token decimals
        $decimals = $currency->decimals ?? 18;
        $amountInSmallestUnit = bcmul($amount, bcpow('10', $decimals, 0), 0);

        // ERC-20 transfer function signature
        $functionSignature = '0xa9059cbb'; // transfer(address,uint256)

        // Encode parameters
        $toAddressEncoded = str_pad(substr($toAddress, 2), 64, '0', STR_PAD_LEFT);
        $amountEncoded = str_pad(dechex($amountInSmallestUnit), 64, '0', STR_PAD_LEFT);

        $data = $functionSignature . $toAddressEncoded . $amountEncoded;

        // Get nonce
        $fromAddress = $this->getAddressFromPrivateKey($privateKey);
        $nonce = $this->web3Service->getNonce($network, $fromAddress);

        // Get gas price
        $gasPrice = $this->web3Service->getGasPrice($network);

        // Estimate gas
        $gasLimit = 100000; // Standard for ERC-20 transfer

        // Build transaction
        $transaction = [
            'from' => $fromAddress,
            'to' => $currency->contract_address,
            'value' => '0x0',
            'gas' => '0x' . dechex($gasLimit),
            'gasPrice' => '0x' . dechex($gasPrice),
            'nonce' => '0x' . dechex($nonce),
            'data' => $data,
        ];

        // Sign transaction
        $signedTx = $this->signTransaction($transaction, $privateKey);

        // Send raw transaction
        $txHash = $web3->eth->sendRawTransaction('0x' . $signedTx, function ($err, $hash) {
            if ($err !== null) {
                throw new \Exception('Failed to send token transfer: ' . $err->getMessage());
            }
        });

        return $txHash;
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
            $feeWei = $gasPrice * $gasLimit;
            $fee = $feeWei / 1000000000000000000; // Convert to ETH/BNB/MATIC

            // Get native token price in THB
            $nativeToken = $this->getNativeTokenForNetwork($network);
            $rate = $this->priceService->getCurrentRate($nativeToken);

            return $fee * ($rate->rate_thb ?? 0);

        } catch (\Exception $e) {
            Log::warning('Failed to estimate gas fee', [
                'network' => $network,
                'error' => $e->getMessage(),
            ]);

            // Return default fee
            return 50; // 50 THB default
        }
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
        // Different networks require different confirmation counts
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
     * Get private key from encrypted storage
     * NOTE: This is a simplified version. In production, use HSM or KMS
     */
    protected function getPrivateKey(CryptoAddress $address): string
    {
        // In production, retrieve from secure key management system
        // For now, this is a placeholder

        if (!$address->encrypted_private_key) {
            throw new \Exception('Private key not found for custodial wallet');
        }

        return decrypt($address->encrypted_private_key);
    }

    /**
     * Get address from private key
     */
    protected function getAddressFromPrivateKey(string $privateKey): string
    {
        // Use elliptic curve cryptography to derive address from private key
        // This is a simplified version
        return '0x' . substr(hash('sha256', $privateKey), 0, 40);
    }

    /**
     * Sign transaction with private key
     */
    protected function signTransaction(array $transaction, string $privateKey): string
    {
        // Sign transaction using Ethereum transaction signing
        // This is a simplified version - in production use proper libraries

        $rlpEncoded = $this->rlpEncode($transaction);
        $hash = hash('sha256', $rlpEncoded);

        // Sign with private key (simplified)
        $signature = hash_hmac('sha256', $hash, $privateKey);

        return $signature . $rlpEncoded;
    }

    /**
     * RLP encode transaction (simplified)
     */
    protected function rlpEncode(array $transaction): string
    {
        // Simplified RLP encoding
        // In production, use proper RLP library
        return json_encode($transaction);
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
            $confirmations = $currentBlock - $receipt->blockNumber;

            return [
                'status' => $receipt->status ? 'confirmed' : 'failed',
                'confirmations' => $confirmations,
                'block_number' => $receipt->blockNumber,
                'gas_used' => $receipt->gasUsed,
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
