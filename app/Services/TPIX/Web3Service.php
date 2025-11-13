<?php

namespace App\Services\TPIX;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Real Web3 Integration for TPIX Blockchain
 * Connects to actual Polygon Edge node
 */
class Web3Service
{
    protected string $rpcUrl;
    protected int $chainId;

    public function __construct()
    {
        $this->rpcUrl = config('crypto.networks.tpix.rpc_url');
        $this->chainId = config('crypto.networks.tpix.chain_id', 7000);
    }

    /**
     * Make JSON-RPC call to TPIX blockchain
     */
    protected function rpcCall(string $method, array $params = []): array
    {
        $response = Http::timeout(30)
            ->post($this->rpcUrl, [
                'jsonrpc' => '2.0',
                'method' => $method,
                'params' => $params,
                'id' => time(),
            ]);

        if (!$response->successful()) {
            throw new \Exception("RPC call failed: {$response->body()}");
        }

        $result = $response->json();

        if (isset($result['error'])) {
            throw new \Exception("RPC error: {$result['error']['message']}");
        }

        return $result;
    }

    /**
     * Get current block number
     */
    public function getBlockNumber(): int
    {
        $result = $this->rpcCall('eth_blockNumber');
        return hexdec($result['result']);
    }

    /**
     * Get account balance in Wei
     */
    public function getBalance(string $address): string
    {
        $result = $this->rpcCall('eth_getBalance', [$address, 'latest']);
        return $result['result'];
    }

    /**
     * Get transaction by hash
     */
    public function getTransaction(string $txHash): ?array
    {
        $result = $this->rpcCall('eth_getTransactionByHash', [$txHash]);
        return $result['result'];
    }

    /**
     * Get transaction receipt
     */
    public function getTransactionReceipt(string $txHash): ?array
    {
        $result = $this->rpcCall('eth_getTransactionReceipt', [$txHash]);
        return $result['result'];
    }

    /**
     * Send raw signed transaction
     */
    public function sendRawTransaction(string $signedTx): string
    {
        $result = $this->rpcCall('eth_sendRawTransaction', [$signedTx]);
        return $result['result'];
    }

    /**
     * Estimate gas for transaction
     */
    public function estimateGas(array $transaction): string
    {
        $result = $this->rpcCall('eth_estimateGas', [$transaction]);
        return $result['result'];
    }

    /**
     * Get current gas price (in TPIX - our native coin!)
     */
    public function getGasPrice(): string
    {
        $result = $this->rpcCall('eth_gasPrice');
        return $result['result'];
    }

    /**
     * Get transaction count (nonce)
     */
    public function getTransactionCount(string $address, string $block = 'latest'): int
    {
        $result = $this->rpcCall('eth_getTransactionCount', [$address, $block]);
        return hexdec($result['result']);
    }

    /**
     * Call contract method (read-only)
     */
    public function call(array $transaction, string $block = 'latest'): string
    {
        $result = $this->rpcCall('eth_call', [$transaction, $block]);
        return $result['result'];
    }

    /**
     * Deploy smart contract
     */
    public function deployContract(string $bytecode, string $privateKey, ?array $constructorParams = null): array
    {
        // Get account address from private key
        $address = $this->getAddressFromPrivateKey($privateKey);

        // Get nonce
        $nonce = $this->getTransactionCount($address);

        // Build transaction
        $transaction = [
            'from' => $address,
            'nonce' => '0x' . dechex($nonce),
            'gasPrice' => $this->getGasPrice(),
            'gas' => '0x' . dechex(5000000), // 5M gas limit
            'data' => $bytecode,
            'chainId' => $this->chainId,
        ];

        // Estimate gas
        $estimatedGas = $this->estimateGas($transaction);
        $transaction['gas'] = $estimatedGas;

        // Sign transaction
        $signedTx = $this->signTransaction($transaction, $privateKey);

        // Send transaction
        $txHash = $this->sendRawTransaction($signedTx);

        // Wait for receipt
        $receipt = $this->waitForReceipt($txHash);

        return [
            'tx_hash' => $txHash,
            'contract_address' => $receipt['contractAddress'] ?? null,
            'gas_used' => hexdec($receipt['gasUsed'] ?? '0'),
            'status' => $receipt['status'] === '0x1' ? 'success' : 'failed',
        ];
    }

    /**
     * Call contract method (write - requires gas)
     */
    public function sendContractTransaction(
        string $contractAddress,
        string $methodSignature,
        array $params,
        string $privateKey,
        ?string $value = null
    ): array {
        // Encode function call
        $data = $this->encodeFunctionCall($methodSignature, $params);

        // Get account address
        $address = $this->getAddressFromPrivateKey($privateKey);

        // Get nonce
        $nonce = $this->getTransactionCount($address);

        // Build transaction
        $transaction = [
            'from' => $address,
            'to' => $contractAddress,
            'nonce' => '0x' . dechex($nonce),
            'gasPrice' => $this->getGasPrice(),
            'gas' => '0x' . dechex(300000),
            'data' => $data,
            'chainId' => $this->chainId,
        ];

        if ($value) {
            $transaction['value'] = $value;
        }

        // Estimate gas
        $estimatedGas = $this->estimateGas($transaction);
        $transaction['gas'] = $estimatedGas;

        // Sign transaction
        $signedTx = $this->signTransaction($transaction, $privateKey);

        // Send transaction
        $txHash = $this->sendRawTransaction($signedTx);

        // Wait for receipt
        $receipt = $this->waitForReceipt($txHash);

        return [
            'tx_hash' => $txHash,
            'gas_used' => hexdec($receipt['gasUsed'] ?? '0'),
            'status' => $receipt['status'] === '0x1' ? 'success' : 'failed',
        ];
    }

    /**
     * Sign transaction with private key
     */
    protected function signTransaction(array $transaction, string $privateKey): string
    {
        // Use kornrunner/keccak or web3.php for actual signing
        // This is a placeholder - implement real signing

        // For now, use external library
        // composer require web3p/web3.php

        throw new \Exception('Transaction signing requires web3p/web3.php library. Run: composer require web3p/web3.php');
    }

    /**
     * Get address from private key
     */
    protected function getAddressFromPrivateKey(string $privateKey): string
    {
        // Use elliptic curve cryptography to derive address
        // This requires web3.php or similar library

        throw new \Exception('Address derivation requires web3p/web3.php library');
    }

    /**
     * Encode function call
     */
    protected function encodeFunctionCall(string $signature, array $params): string
    {
        // Function signature → 4-byte selector
        $selector = substr(hash('sha3-256', $signature), 0, 8);

        // Encode parameters (ABI encoding)
        // This requires proper ABI encoding library

        return '0x' . $selector;
    }

    /**
     * Wait for transaction receipt
     */
    protected function waitForReceipt(string $txHash, int $maxAttempts = 60): ?array
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            $receipt = $this->getTransactionReceipt($txHash);

            if ($receipt) {
                return $receipt;
            }

            sleep(2); // Wait 2 seconds between checks
        }

        throw new \Exception("Transaction receipt not found after {$maxAttempts} attempts");
    }

    /**
     * Convert Wei to TPIX
     */
    public function weiToTPIX(string $wei): string
    {
        return bcdiv($wei, '1000000000000000000', 18);
    }

    /**
     * Convert TPIX to Wei
     */
    public function tpixToWei(string $tpix): string
    {
        return bcmul($tpix, '1000000000000000000', 0);
    }

    /**
     * Validate Ethereum address
     */
    public function isValidAddress(string $address): bool
    {
        return preg_match('/^0x[a-fA-F0-9]{40}$/', $address);
    }

    /**
     * Check if node is synced and healthy
     */
    public function isNodeHealthy(): bool
    {
        try {
            $this->getBlockNumber();
            return true;
        } catch (\Exception $e) {
            Log::error('TPIX node health check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get network info
     */
    public function getNetworkInfo(): array
    {
        return [
            'chain_id' => $this->chainId,
            'rpc_url' => $this->rpcUrl,
            'block_number' => $this->getBlockNumber(),
            'gas_price' => $this->weiToTPIX($this->getGasPrice()) . ' TPIX',
            'is_healthy' => $this->isNodeHealthy(),
        ];
    }
}
