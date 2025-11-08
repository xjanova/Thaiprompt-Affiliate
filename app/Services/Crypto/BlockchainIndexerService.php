<?php

namespace App\Services\Crypto;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Blockchain Indexer Service
 *
 * Provides integration with blockchain indexer APIs for transaction history,
 * event logs, and balance queries. Supports multiple providers.
 *
 * PRODUCTION SETUP REQUIRED:
 * 1. Sign up for API keys at:
 *    - Etherscan: https://etherscan.io/apis
 *    - BSCScan: https://bscscan.com/apis
 *    - PolygonScan: https://polygonscan.com/apis
 * 2. Add to .env:
 *    ETHERSCAN_API_KEY=your_key
 *    BSCSCAN_API_KEY=your_key
 *    POLYGONSCAN_API_KEY=your_key
 */
class BlockchainIndexerService
{
    protected array $apiEndpoints;
    protected array $apiKeys;

    public function __construct()
    {
        $this->apiEndpoints = [
            'ethereum' => 'https://api.etherscan.io/api',
            'bsc' => 'https://api.bscscan.com/api',
            'polygon' => 'https://api.polygonscan.com/api',
        ];

        $this->apiKeys = [
            'ethereum' => env('ETHERSCAN_API_KEY', ''),
            'bsc' => env('BSCSCAN_API_KEY', ''),
            'polygon' => env('POLYGONSCAN_API_KEY', ''),
        ];
    }

    /**
     * Get normal transactions for address
     *
     * @param string $network
     * @param string $address
     * @param int|null $startBlock
     * @param int|null $endBlock
     * @param int $page
     * @param int $offset
     * @return array
     */
    public function getNormalTransactions(
        string $network,
        string $address,
        ?int $startBlock = 0,
        ?int $endBlock = 99999999,
        int $page = 1,
        int $offset = 100
    ): array {
        $cacheKey = "indexer:{$network}:txs:{$address}:{$startBlock}:{$endBlock}:{$page}";

        return Cache::remember($cacheKey, 60, function () use ($network, $address, $startBlock, $endBlock, $page, $offset) {
            try {
                $response = Http::timeout(30)->get($this->apiEndpoints[$network], [
                    'module' => 'account',
                    'action' => 'txlist',
                    'address' => $address,
                    'startblock' => $startBlock,
                    'endblock' => $endBlock,
                    'page' => $page,
                    'offset' => $offset,
                    'sort' => 'desc',
                    'apikey' => $this->apiKeys[$network],
                ]);

                if (!$response->successful()) {
                    throw new \Exception('API request failed: ' . $response->status());
                }

                $data = $response->json();

                if ($data['status'] != '1') {
                    if ($data['message'] === 'No transactions found') {
                        return [];
                    }
                    throw new \Exception('API error: ' . ($data['message'] ?? 'Unknown error'));
                }

                return $data['result'] ?? [];

            } catch (\Exception $e) {
                Log::error('Failed to get normal transactions from indexer', [
                    'network' => $network,
                    'address' => $address,
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /**
     * Get ERC-20 token transfer events
     *
     * @param string $network
     * @param string|null $contractAddress Filter by specific token contract
     * @param string|null $address Filter by address (from or to)
     * @param int|null $startBlock
     * @param int|null $endBlock
     * @return array
     */
    public function getTokenTransfers(
        string $network,
        ?string $contractAddress = null,
        ?string $address = null,
        ?int $startBlock = 0,
        ?int $endBlock = 99999999
    ): array {
        $cacheKey = "indexer:{$network}:tokens:{$contractAddress}:{$address}:{$startBlock}:{$endBlock}";

        return Cache::remember($cacheKey, 60, function () use ($network, $contractAddress, $address, $startBlock, $endBlock) {
            try {
                $params = [
                    'module' => 'account',
                    'action' => 'tokentx',
                    'startblock' => $startBlock,
                    'endblock' => $endBlock,
                    'sort' => 'desc',
                    'apikey' => $this->apiKeys[$network],
                ];

                if ($contractAddress) {
                    $params['contractaddress'] = $contractAddress;
                }

                if ($address) {
                    $params['address'] = $address;
                }

                $response = Http::timeout(30)->get($this->apiEndpoints[$network], $params);

                if (!$response->successful()) {
                    throw new \Exception('API request failed: ' . $response->status());
                }

                $data = $response->json();

                if ($data['status'] != '1') {
                    if ($data['message'] === 'No transactions found') {
                        return [];
                    }
                    throw new \Exception('API error: ' . ($data['message'] ?? 'Unknown error'));
                }

                return $data['result'] ?? [];

            } catch (\Exception $e) {
                Log::error('Failed to get token transfers from indexer', [
                    'network' => $network,
                    'contract' => $contractAddress,
                    'address' => $address,
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /**
     * Get internal transactions (contract calls)
     */
    public function getInternalTransactions(
        string $network,
        string $address,
        ?int $startBlock = 0,
        ?int $endBlock = 99999999
    ): array {
        $cacheKey = "indexer:{$network}:internal:{$address}:{$startBlock}:{$endBlock}";

        return Cache::remember($cacheKey, 60, function () use ($network, $address, $startBlock, $endBlock) {
            try {
                $response = Http::timeout(30)->get($this->apiEndpoints[$network], [
                    'module' => 'account',
                    'action' => 'txlistinternal',
                    'address' => $address,
                    'startblock' => $startBlock,
                    'endblock' => $endBlock,
                    'sort' => 'desc',
                    'apikey' => $this->apiKeys[$network],
                ]);

                if (!$response->successful()) {
                    throw new \Exception('API request failed: ' . $response->status());
                }

                $data = $response->json();

                if ($data['status'] != '1') {
                    if ($data['message'] === 'No transactions found') {
                        return [];
                    }
                    throw new \Exception('API error: ' . ($data['message'] ?? 'Unknown error'));
                }

                return $data['result'] ?? [];

            } catch (\Exception $e) {
                Log::error('Failed to get internal transactions from indexer', [
                    'network' => $network,
                    'address' => $address,
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /**
     * Get account balance from indexer
     */
    public function getBalance(string $network, string $address): ?string
    {
        try {
            $response = Http::timeout(10)->get($this->apiEndpoints[$network], [
                'module' => 'account',
                'action' => 'balance',
                'address' => $address,
                'tag' => 'latest',
                'apikey' => $this->apiKeys[$network],
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            if ($data['status'] != '1') {
                return null;
            }

            return $data['result'];

        } catch (\Exception $e) {
            Log::warning('Failed to get balance from indexer', [
                'network' => $network,
                'address' => $address,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get token balance from indexer
     */
    public function getTokenBalance(string $network, string $contractAddress, string $address): ?string
    {
        try {
            $response = Http::timeout(10)->get($this->apiEndpoints[$network], [
                'module' => 'account',
                'action' => 'tokenbalance',
                'contractaddress' => $contractAddress,
                'address' => $address,
                'tag' => 'latest',
                'apikey' => $this->apiKeys[$network],
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            if ($data['status'] != '1') {
                return null;
            }

            return $data['result'];

        } catch (\Exception $e) {
            Log::warning('Failed to get token balance from indexer', [
                'network' => $network,
                'contract' => $contractAddress,
                'address' => $address,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get current gas price oracle
     */
    public function getGasOracle(string $network): ?array
    {
        $cacheKey = "indexer:{$network}:gas_oracle";

        return Cache::remember($cacheKey, 15, function () use ($network) {
            try {
                $response = Http::timeout(10)->get($this->apiEndpoints[$network], [
                    'module' => 'gastracker',
                    'action' => 'gasoracle',
                    'apikey' => $this->apiKeys[$network],
                ]);

                if (!$response->successful()) {
                    return null;
                }

                $data = $response->json();

                if ($data['status'] != '1') {
                    return null;
                }

                return $data['result'];

            } catch (\Exception $e) {
                Log::warning('Failed to get gas oracle from indexer', [
                    'network' => $network,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Check if transaction is confirmed
     */
    public function getTransactionStatus(string $network, string $txHash): ?array
    {
        try {
            $response = Http::timeout(10)->get($this->apiEndpoints[$network], [
                'module' => 'transaction',
                'action' => 'gettxreceiptstatus',
                'txhash' => $txHash,
                'apikey' => $this->apiKeys[$network],
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            if ($data['status'] != '1') {
                return null;
            }

            return $data['result'];

        } catch (\Exception $e) {
            Log::warning('Failed to get transaction status from indexer', [
                'network' => $network,
                'txHash' => $txHash,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get current block number from indexer
     */
    public function getBlockNumber(string $network): ?int
    {
        $cacheKey = "indexer:{$network}:block_number";

        return Cache::remember($cacheKey, 10, function () use ($network) {
            try {
                $response = Http::timeout(10)->get($this->apiEndpoints[$network], [
                    'module' => 'proxy',
                    'action' => 'eth_blockNumber',
                    'apikey' => $this->apiKeys[$network],
                ]);

                if (!$response->successful()) {
                    return null;
                }

                $data = $response->json();

                if (!isset($data['result'])) {
                    return null;
                }

                return hexdec($data['result']);

            } catch (\Exception $e) {
                Log::warning('Failed to get block number from indexer', [
                    'network' => $network,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Check if API key is configured
     */
    public function isConfigured(string $network): bool
    {
        return !empty($this->apiKeys[$network]);
    }

    /**
     * Get API usage stats (if supported by provider)
     */
    public function getApiStats(string $network): ?array
    {
        // Some providers offer API usage stats
        // This is a placeholder for future implementation
        return null;
    }
}
