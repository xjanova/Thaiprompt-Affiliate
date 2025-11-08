<?php

namespace App\Services\Crypto;

use Web3\Web3;
use Web3\Contract;
use Web3\Utils;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Illuminate\Support\Facades\Log;
use kornrunner\Keccak;

class Web3Service
{
    protected array $rpcs;
    protected array $explorers;

    public function __construct()
    {
        $this->rpcs = [
            'ethereum' => env('ETHEREUM_RPC_URL', 'https://ethereum.publicnode.com'),
            'bsc' => env('BSC_RPC_URL', 'https://bsc-dataseed.binance.org'),
            'polygon' => env('POLYGON_RPC_URL', 'https://polygon-rpc.com'),
        ];

        $this->explorers = [
            'ethereum' => 'https://etherscan.io',
            'bsc' => 'https://bscscan.com',
            'polygon' => 'https://polygonscan.com',
        ];
    }

    /**
     * Get Web3 instance for specific network
     */
    public function getWeb3(string $network): Web3
    {
        $rpcUrl = $this->rpcs[$network] ?? $this->rpcs['ethereum'];
        return new Web3(new HttpProvider(new HttpRequestManager($rpcUrl)));
    }

    /**
     * Verify Ethereum signature
     *
     * @param string $message Original message that was signed
     * @param string $signature The signature to verify
     * @param string $expectedAddress The expected signer address
     * @return bool
     */
    public function verifySignature(string $message, string $signature, string $expectedAddress): bool
    {
        try {
            // Create the Ethereum Signed Message
            $messageHash = $this->hashMessage($message);

            // Recover the address from signature
            $recoveredAddress = $this->recoverAddress($messageHash, $signature);

            // Compare addresses (case insensitive)
            return strtolower($recoveredAddress) === strtolower($expectedAddress);
        } catch (\Exception $e) {
            Log::error('Signature verification failed', [
                'message' => $message,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Hash message using Ethereum's message hashing convention
     */
    protected function hashMessage(string $message): string
    {
        $prefix = "\x19Ethereum Signed Message:\n" . strlen($message);
        $prefixedMessage = $prefix . $message;

        return '0x' . Keccak::hash($prefixedMessage, 256);
    }

    /**
     * Recover address from message hash and signature
     */
    protected function recoverAddress(string $messageHash, string $signature): string
    {
        // Remove 0x prefix if present
        $signature = str_replace('0x', '', $signature);

        if (strlen($signature) !== 130) {
            throw new \Exception('Invalid signature length');
        }

        // Split signature into r, s, v
        $r = substr($signature, 0, 64);
        $s = substr($signature, 64, 64);
        $v = hexdec(substr($signature, 128, 2));

        // Adjust v if needed (some wallets use 0/1 instead of 27/28)
        if ($v < 27) {
            $v += 27;
        }

        // Recover public key and derive address
        // This is a simplified version - in production, use a proper ECDSA library
        return $this->recoverAddressFromSignature($messageHash, $r, $s, $v);
    }

    /**
     * Recover Ethereum address from ECDSA signature components
     * This is a placeholder - implement proper ECDSA recovery
     */
    protected function recoverAddressFromSignature(string $messageHash, string $r, string $s, int $v): string
    {
        // For production, use proper ECDSA recovery library
        // This is a simplified placeholder

        // In a real implementation, you would:
        // 1. Recover the public key from (r, s, v) and message hash
        // 2. Hash the public key with Keccak-256
        // 3. Take the last 20 bytes as the address

        throw new \Exception('ECDSA recovery not fully implemented - use frontend verification for now');
    }

    /**
     * Validate Ethereum address format
     */
    public function isValidAddress(string $address): bool
    {
        return preg_match('/^0x[a-fA-F0-9]{40}$/', $address) === 1;
    }

    /**
     * Validate Bitcoin address format
     */
    public function isValidBitcoinAddress(string $address): bool
    {
        // Simple validation for Bitcoin addresses
        // P2PKH: starts with 1, P2SH: starts with 3, Bech32: starts with bc1
        return preg_match('/^(1|3|bc1)[a-zA-Z0-9]{25,62}$/', $address) === 1;
    }

    /**
     * Get balance of Ethereum address
     */
    public function getBalance(string $network, string $address): ?string
    {
        try {
            $web3 = $this->getWeb3($network);
            $balance = null;

            $web3->eth->getBalance($address, function ($err, $result) use (&$balance) {
                if ($err) {
                    throw new \Exception($err->getMessage());
                }
                $balance = $result->toString();
            });

            return $balance;
        } catch (\Exception $e) {
            Log::error('Failed to get balance', [
                'network' => $network,
                'address' => $address,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get ERC-20 token balance
     */
    public function getTokenBalance(string $network, string $contractAddress, string $walletAddress): ?string
    {
        try {
            $web3 = $this->getWeb3($network);

            // ERC-20 balanceOf ABI
            $abi = '[{
                "constant": true,
                "inputs": [{"name": "_owner", "type": "address"}],
                "name": "balanceOf",
                "outputs": [{"name": "balance", "type": "uint256"}],
                "type": "function"
            }]';

            $contract = new Contract($web3->provider, $abi);
            $contract->at($contractAddress);

            $balance = null;
            $contract->call('balanceOf', $walletAddress, function ($err, $result) use (&$balance) {
                if ($err) {
                    throw new \Exception($err->getMessage());
                }
                $balance = $result[0]->toString();
            });

            return $balance;
        } catch (\Exception $e) {
            Log::error('Failed to get token balance', [
                'network' => $network,
                'contract' => $contractAddress,
                'wallet' => $walletAddress,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get transaction receipt
     */
    public function getTransactionReceipt(string $network, string $txHash): ?array
    {
        try {
            $web3 = $this->getWeb3($network);
            $receipt = null;

            $web3->eth->getTransactionReceipt($txHash, function ($err, $result) use (&$receipt) {
                if ($err) {
                    throw new \Exception($err->getMessage());
                }
                $receipt = $result;
            });

            return $receipt ? (array) $receipt : null;
        } catch (\Exception $e) {
            Log::error('Failed to get transaction receipt', [
                'network' => $network,
                'txHash' => $txHash,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get current block number
     */
    public function getBlockNumber(string $network): ?int
    {
        try {
            $web3 = $this->getWeb3($network);
            $blockNumber = null;

            $web3->eth->blockNumber(function ($err, $result) use (&$blockNumber) {
                if ($err) {
                    throw new \Exception($err->getMessage());
                }
                $blockNumber = hexdec($result->toString());
            });

            return $blockNumber;
        } catch (\Exception $e) {
            Log::error('Failed to get block number', [
                'network' => $network,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get block explorer URL for transaction
     */
    public function getExplorerTxUrl(string $network, string $txHash): string
    {
        $explorer = $this->explorers[$network] ?? $this->explorers['ethereum'];
        return "{$explorer}/tx/{$txHash}";
    }

    /**
     * Get block explorer URL for address
     */
    public function getExplorerAddressUrl(string $network, string $address): string
    {
        $explorer = $this->explorers[$network] ?? $this->explorers['ethereum'];
        return "{$explorer}/address/{$address}";
    }

    /**
     * Convert Wei to Ether
     */
    public function fromWei(string $wei, int $decimals = 18): string
    {
        return bcdiv($wei, bcpow('10', (string)$decimals, 0), $decimals);
    }

    /**
     * Convert Ether to Wei
     */
    public function toWei(string $ether, int $decimals = 18): string
    {
        return bcmul($ether, bcpow('10', (string)$decimals, 0), 0);
    }

    /**
     * Generate signature verification message
     */
    public function generateVerificationMessage(string $address, string $nonce): string
    {
        return "Sign this message to verify your wallet ownership.\n\n" .
               "Address: {$address}\n" .
               "Nonce: {$nonce}\n" .
               "Timestamp: " . now()->toIso8601String();
    }

    /**
     * Estimate gas for transaction
     */
    public function estimateGas(string $network, array $transaction): ?string
    {
        try {
            $web3 = $this->getWeb3($network);
            $gas = null;

            $web3->eth->estimateGas($transaction, function ($err, $result) use (&$gas) {
                if ($err) {
                    throw new \Exception($err->getMessage());
                }
                $gas = $result->toString();
            });

            return $gas;
        } catch (\Exception $e) {
            Log::error('Failed to estimate gas', [
                'network' => $network,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get gas price
     */
    public function getGasPrice(string $network): ?string
    {
        try {
            $web3 = $this->getWeb3($network);
            $gasPrice = null;

            $web3->eth->gasPrice(function ($err, $result) use (&$gasPrice) {
                if ($err) {
                    throw new \Exception($err->getMessage());
                }
                $gasPrice = $result->toString();
            });

            return $gasPrice;
        } catch (\Exception $e) {
            Log::error('Failed to get gas price', [
                'network' => $network,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get transaction count (nonce) for address
     */
    public function getNonce(string $network, string $address): int
    {
        try {
            $web3 = $this->getWeb3($network);
            $nonce = null;

            $web3->eth->getTransactionCount($address, 'pending', function ($err, $result) use (&$nonce) {
                if ($err) {
                    throw new \Exception($err->getMessage());
                }
                $nonce = hexdec($result->toString());
            });

            return $nonce ?? 0;
        } catch (\Exception $e) {
            Log::error('Failed to get nonce', [
                'network' => $network,
                'address' => $address,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Get current block number (alias for getBlockNumber)
     */
    public function getCurrentBlockNumber(string $network): int
    {
        return $this->getBlockNumber($network) ?? 0;
    }

    /**
     * Get transactions for address in block range
     * Note: This is a simplified implementation
     * In production, use blockchain indexer API (Etherscan, etc.)
     */
    public function getTransactions(
        string $network,
        string $address,
        int $fromBlock,
        int $toBlock
    ): array {
        // This would require blockchain indexing service
        // For now, return empty array and recommend using API
        Log::warning('getTransactions called - requires indexer service', [
            'network' => $network,
            'address' => $address,
            'fromBlock' => $fromBlock,
            'toBlock' => $toBlock,
        ]);

        // In production, use services like:
        // - Etherscan API
        // - The Graph
        // - Moralis
        // - Alchemy
        return [];
    }

    /**
     * Get ERC-20 token transfer events
     * Note: This is a simplified implementation
     * In production, use blockchain indexer API
     */
    public function getTokenTransferEvents(
        string $network,
        string $contractAddress,
        string $toAddress,
        int $fromBlock,
        int $toBlock
    ): array {
        // This would require blockchain indexing service or event logs
        // For now, return empty array and recommend using API
        Log::warning('getTokenTransferEvents called - requires indexer service', [
            'network' => $network,
            'contract' => $contractAddress,
            'toAddress' => $toAddress,
            'fromBlock' => $fromBlock,
            'toBlock' => $toBlock,
        ]);

        // In production, use getLogs with Transfer event filter
        // or use indexer services like Etherscan API
        return [];
    }

    /**
     * Get chain ID for network
     */
    public function getChainId(string $network): int
    {
        return match ($network) {
            'ethereum' => 1,
            'bsc' => 56,
            'polygon' => 137,
            default => 1,
        };
    }
}
