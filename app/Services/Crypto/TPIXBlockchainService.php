<?php

namespace App\Services\Crypto;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * TPIX Blockchain Service
 *
 * This service handles all interactions with the TPIX native blockchain.
 * TPIX is a custom blockchain with its own native coin (not a token).
 *
 * Features:
 * - Native TPIX coin transactions (no gas fees from other coins)
 * - EVM-compatible smart contracts
 * - Fixed supply: 7,000,000,000 TPIX
 * - IBFT consensus mechanism
 * - 2-second block time
 *
 * @package App\Services\Crypto
 */
class TPIXBlockchainService
{
    /**
     * TPIX blockchain RPC endpoint
     *
     * @var string
     */
    protected string $rpcUrl;

    /**
     * Chain ID for TPIX network
     *
     * @var int
     */
    protected int $chainId = 7000;

    /**
     * Total supply of TPIX (in smallest unit)
     *
     * @var string
     */
    protected string $totalSupply = '7000000000000000000000000000'; // 7 billion TPIX

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->rpcUrl = config('crypto.tpix_rpc_url', 'http://localhost:8545');
    }

    /**
     * Get TPIX balance for an address
     *
     * @param string $address The wallet address
     * @return string Balance in TPIX (with decimals)
     * @throws Exception
     */
    public function getBalance(string $address): string
    {
        try {
            $response = $this->rpcCall('eth_getBalance', [$address, 'latest']);

            if (isset($response['result'])) {
                // Convert from hex wei to TPIX
                $weiBalance = hexdec($response['result']);
                $tpixBalance = bcdiv((string)$weiBalance, '1000000000000000000', 18);
                return $tpixBalance;
            }

            throw new Exception('Invalid response from TPIX node');
        } catch (Exception $e) {
            Log::error('TPIX getBalance error', [
                'address' => $address,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get current block number
     *
     * @return int Current block number
     * @throws Exception
     */
    public function getBlockNumber(): int
    {
        try {
            $response = $this->rpcCall('eth_blockNumber', []);

            if (isset($response['result'])) {
                return hexdec($response['result']);
            }

            throw new Exception('Invalid response from TPIX node');
        } catch (Exception $e) {
            Log::error('TPIX getBlockNumber error', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get transaction by hash
     *
     * @param string $txHash Transaction hash
     * @return array Transaction details
     * @throws Exception
     */
    public function getTransaction(string $txHash): array
    {
        try {
            $response = $this->rpcCall('eth_getTransactionByHash', [$txHash]);

            if (isset($response['result'])) {
                return $this->formatTransaction($response['result']);
            }

            throw new Exception('Transaction not found');
        } catch (Exception $e) {
            Log::error('TPIX getTransaction error', [
                'txHash' => $txHash,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get transaction receipt
     *
     * @param string $txHash Transaction hash
     * @return array Transaction receipt
     * @throws Exception
     */
    public function getTransactionReceipt(string $txHash): array
    {
        try {
            $response = $this->rpcCall('eth_getTransactionReceipt', [$txHash]);

            if (isset($response['result'])) {
                return $response['result'];
            }

            throw new Exception('Transaction receipt not found');
        } catch (Exception $e) {
            Log::error('TPIX getTransactionReceipt error', [
                'txHash' => $txHash,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send raw transaction to TPIX blockchain
     *
     * @param string $signedTx Signed transaction hex
     * @return string Transaction hash
     * @throws Exception
     */
    public function sendRawTransaction(string $signedTx): string
    {
        try {
            $response = $this->rpcCall('eth_sendRawTransaction', [$signedTx]);

            if (isset($response['result'])) {
                return $response['result'];
            }

            throw new Exception('Failed to send transaction: ' . ($response['error']['message'] ?? 'Unknown error'));
        } catch (Exception $e) {
            Log::error('TPIX sendRawTransaction error', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Estimate gas for a transaction
     *
     * @param array $transaction Transaction parameters
     * @return string Estimated gas in hex
     * @throws Exception
     */
    public function estimateGas(array $transaction): string
    {
        try {
            $response = $this->rpcCall('eth_estimateGas', [$transaction]);

            if (isset($response['result'])) {
                return $response['result'];
            }

            throw new Exception('Failed to estimate gas');
        } catch (Exception $e) {
            Log::error('TPIX estimateGas error', [
                'transaction' => $transaction,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get gas price
     *
     * @return string Gas price in hex
     * @throws Exception
     */
    public function getGasPrice(): string
    {
        try {
            $response = $this->rpcCall('eth_gasPrice', []);

            if (isset($response['result'])) {
                return $response['result'];
            }

            throw new Exception('Failed to get gas price');
        } catch (Exception $e) {
            Log::error('TPIX getGasPrice error', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get transaction count (nonce) for an address
     *
     * @param string $address Wallet address
     * @param string $block Block parameter (default: 'latest')
     * @return int Transaction count
     * @throws Exception
     */
    public function getTransactionCount(string $address, string $block = 'latest'): int
    {
        try {
            $response = $this->rpcCall('eth_getTransactionCount', [$address, $block]);

            if (isset($response['result'])) {
                return hexdec($response['result']);
            }

            throw new Exception('Failed to get transaction count');
        } catch (Exception $e) {
            Log::error('TPIX getTransactionCount error', [
                'address' => $address,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get chain ID
     *
     * @return int Chain ID
     */
    public function getChainId(): int
    {
        return $this->chainId;
    }

    /**
     * Get total supply
     *
     * @return string Total supply in TPIX
     */
    public function getTotalSupply(): string
    {
        return bcdiv($this->totalSupply, '1000000000000000000', 18);
    }

    /**
     * Get network info
     *
     * @return array Network information
     * @throws Exception
     */
    public function getNetworkInfo(): array
    {
        try {
            $blockNumber = $this->getBlockNumber();
            $gasPrice = $this->getGasPrice();

            return [
                'name' => 'TPIX Network',
                'chainId' => $this->chainId,
                'symbol' => 'TPIX',
                'decimals' => 18,
                'totalSupply' => $this->getTotalSupply(),
                'maxSupply' => $this->getTotalSupply(),
                'blockNumber' => $blockNumber,
                'gasPrice' => hexdec($gasPrice),
                'rpcUrl' => $this->rpcUrl,
                'explorerUrl' => config('crypto.tpix_explorer_url', 'http://localhost:4000'),
                'features' => [
                    'nativeCoin' => true,
                    'evmCompatible' => true,
                    'erc20Support' => true,
                    'smartContracts' => true,
                    'fixedSupply' => true
                ]
            ];
        } catch (Exception $e) {
            Log::error('TPIX getNetworkInfo error', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Call TPIX RPC endpoint
     *
     * @param string $method RPC method name
     * @param array $params Method parameters
     * @return array Response data
     * @throws Exception
     */
    protected function rpcCall(string $method, array $params = []): array
    {
        try {
            $response = Http::timeout(30)
                ->retry(3, 100)
                ->post($this->rpcUrl, [
                    'jsonrpc' => '2.0',
                    'method' => $method,
                    'params' => $params,
                    'id' => 1
                ]);

            if (!$response->successful()) {
                throw new Exception('RPC call failed: ' . $response->status());
            }

            $data = $response->json();

            if (isset($data['error'])) {
                throw new Exception('RPC error: ' . ($data['error']['message'] ?? 'Unknown error'));
            }

            return $data;
        } catch (Exception $e) {
            Log::error('TPIX RPC call error', [
                'method' => $method,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Format transaction data
     *
     * @param array $tx Raw transaction data
     * @return array Formatted transaction
     */
    protected function formatTransaction(array $tx): array
    {
        return [
            'hash' => $tx['hash'] ?? null,
            'from' => $tx['from'] ?? null,
            'to' => $tx['to'] ?? null,
            'value' => isset($tx['value']) ? bcdiv(hexdec($tx['value']), '1000000000000000000', 18) : '0',
            'gas' => isset($tx['gas']) ? hexdec($tx['gas']) : 0,
            'gasPrice' => isset($tx['gasPrice']) ? hexdec($tx['gasPrice']) : 0,
            'nonce' => isset($tx['nonce']) ? hexdec($tx['nonce']) : 0,
            'blockNumber' => isset($tx['blockNumber']) ? hexdec($tx['blockNumber']) : null,
            'blockHash' => $tx['blockHash'] ?? null,
            'transactionIndex' => isset($tx['transactionIndex']) ? hexdec($tx['transactionIndex']) : null,
            'input' => $tx['input'] ?? '0x',
            'chainId' => $this->chainId
        ];
    }

    /**
     * Validate TPIX address
     *
     * @param string $address Address to validate
     * @return bool True if valid
     */
    public function isValidAddress(string $address): bool
    {
        return preg_match('/^0x[a-fA-F0-9]{40}$/', $address) === 1;
    }

    /**
     * Convert TPIX to wei
     *
     * @param string $tpix Amount in TPIX
     * @return string Amount in wei
     */
    public function toWei(string $tpix): string
    {
        return bcmul($tpix, '1000000000000000000', 0);
    }

    /**
     * Convert wei to TPIX
     *
     * @param string $wei Amount in wei
     * @return string Amount in TPIX
     */
    public function fromWei(string $wei): string
    {
        return bcdiv($wei, '1000000000000000000', 18);
    }
}
