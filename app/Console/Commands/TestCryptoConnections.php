<?php

namespace App\Console\Commands;

use App\Services\Crypto\Web3Service;
use App\Services\Crypto\BlockchainIndexerService;
use App\Services\Crypto\CryptoPriceService;
use Illuminate\Console\Command;

/**
 * Test Cryptocurrency Connections Command
 *
 * Tests all blockchain connections, API integrations, and services
 */
class TestCryptoConnections extends Command
{
    protected $signature = 'crypto:test-connections
                            {--network= : Test specific network only}
                            {--detail : Show detailed output}';

    protected $description = 'Test all cryptocurrency connections and services';

    protected Web3Service $web3Service;
    protected BlockchainIndexerService $indexerService;
    protected CryptoPriceService $priceService;

    public function __construct(
        Web3Service $web3Service,
        BlockchainIndexerService $indexerService,
        CryptoPriceService $priceService
    ) {
        parent::__construct();
        $this->web3Service = $web3Service;
        $this->indexerService = $indexerService;
        $this->priceService = $priceService;
    }

    public function handle(): int
    {
        $this->info('🚀 Testing Cryptocurrency Connections...');
        $this->newLine();

        $networks = $this->option('network')
            ? [$this->option('network')]
            : ['ethereum', 'bsc', 'polygon'];

        $allPassed = true;

        foreach ($networks as $network) {
            $this->info("📡 Testing {$network}...");
            $this->newLine();

            // Test 1: RPC Connection
            if (!$this->testRpcConnection($network)) {
                $allPassed = false;
            }

            // Test 2: Indexer API
            if (!$this->testIndexerApi($network)) {
                $allPassed = false;
            }

            // Test 3: Price Feed
            if (!$this->testPriceFeed($network)) {
                $allPassed = false;
            }

            $this->newLine();
        }

        // Test 4: Database Connection
        if (!$this->testDatabase()) {
            $allPassed = false;
        }

        // Test 5: Cache Connection
        if (!$this->testCache()) {
            $allPassed = false;
        }

        $this->newLine();
        $this->displaySummary($allPassed);

        return $allPassed ? 0 : 1;
    }

    protected function testRpcConnection(string $network): bool
    {
        $this->line('  🔌 Testing RPC Connection...');

        try {
            $startTime = microtime(true);
            $blockNumber = $this->web3Service->getBlockNumber($network);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($blockNumber) {
                $this->info("     ✅ Connected - Block #{$blockNumber} ({$responseTime}ms)");

                if ($this->option('detail')) {
                    // Test gas price
                    $gasPrice = $this->web3Service->getGasPrice($network);
                    $gasPriceGwei = bcdiv($gasPrice, '1000000000', 2);
                    $this->line("     📊 Current Gas Price: {$gasPriceGwei} Gwei");

                    // Test chain ID
                    $chainId = $this->web3Service->getChainId($network);
                    $this->line("     🔗 Chain ID: {$chainId}");
                }

                return true;
            } else {
                $this->error('     ❌ Failed to get block number');
                return false;
            }
        } catch (\Exception $e) {
            $this->error('     ❌ Connection failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function testIndexerApi(string $network): bool
    {
        $this->line('  🔍 Testing Blockchain Indexer API...');

        if (!$this->indexerService->isConfigured($network)) {
            $this->warn('     ⚠️  API key not configured (set in .env)');
            return false;
        }

        try {
            $startTime = microtime(true);
            $blockNumber = $this->indexerService->getBlockNumber($network);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($blockNumber) {
                $this->info("     ✅ API Working - Block #{$blockNumber} ({$responseTime}ms)");

                if ($this->option('detail')) {
                    // Test gas oracle
                    $gasOracle = $this->indexerService->getGasOracle($network);
                    if ($gasOracle) {
                        $this->line("     ⛽ Gas Oracle:");
                        $this->line("        Safe: {$gasOracle['SafeGasPrice']} Gwei");
                        $this->line("        Propose: {$gasOracle['ProposeGasPrice']} Gwei");
                        $this->line("        Fast: {$gasOracle['FastGasPrice']} Gwei");
                    }

                    // Test sample address query
                    $testAddress = '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb';
                    $balance = $this->indexerService->getBalance($network, $testAddress);
                    if ($balance !== null) {
                        $balanceEth = bcdiv($balance, '1000000000000000000', 6);
                        $this->line("     💰 Test Query (Vitalik): {$balanceEth} " . $this->getNativeCurrency($network));
                    }
                }

                return true;
            } else {
                $this->error('     ❌ API request failed');
                return false;
            }
        } catch (\Exception $e) {
            $this->error('     ❌ API error: ' . $e->getMessage());
            return false;
        }
    }

    protected function testPriceFeed(string $network): bool
    {
        $this->line('  💹 Testing Price Feed...');

        $currency = $this->getNativeCurrency($network);

        try {
            $cryptoCurrency = \App\Models\CryptoCurrency::where('code', $currency)->first();

            if (!$cryptoCurrency) {
                $this->warn("     ⚠️  {$currency} not found in database");
                return false;
            }

            $startTime = microtime(true);
            $rate = $this->priceService->getCurrentRate($cryptoCurrency);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($rate && $rate->rate_thb) {
                $this->info("     ✅ Price: ฿" . number_format($rate->rate_thb, 2) . " ({$responseTime}ms)");

                if ($this->option('detail') && $rate->rate_usd) {
                    $this->line("     💵 USD: \$" . number_format($rate->rate_usd, 2));
                    if ($rate->change_24h) {
                        $changeColor = $rate->change_24h >= 0 ? 'info' : 'error';
                        $changeSymbol = $rate->change_24h >= 0 ? '▲' : '▼';
                        $this->$changeColor("     📈 24h Change: {$changeSymbol} " . number_format(abs($rate->change_24h), 2) . "%");
                    }
                }

                return true;
            } else {
                $this->error('     ❌ Failed to get price');
                return false;
            }
        } catch (\Exception $e) {
            $this->error('     ❌ Price feed error: ' . $e->getMessage());
            return false;
        }
    }

    protected function testDatabase(): bool
    {
        $this->info('💾 Testing Database Connection...');

        try {
            $startTime = microtime(true);
            \DB::connection()->getPdo();
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->info("  ✅ Database Connected ({$responseTime}ms)");

            if ($this->option('detail')) {
                $walletCount = \App\Models\CryptoWallet::count();
                $txCount = \App\Models\CryptoTransaction::count();
                $currencyCount = \App\Models\CryptoCurrency::count();

                $this->line("  📊 Statistics:");
                $this->line("     Wallets: {$walletCount}");
                $this->line("     Transactions: {$txCount}");
                $this->line("     Currencies: {$currencyCount}");
            }

            return true;
        } catch (\Exception $e) {
            $this->error('  ❌ Database error: ' . $e->getMessage());
            return false;
        }
    }

    protected function testCache(): bool
    {
        $this->info('💨 Testing Cache Connection...');

        try {
            $startTime = microtime(true);
            \Cache::put('crypto_test_' . time(), 'test', 60);
            \Cache::get('crypto_test_' . time());
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->info("  ✅ Cache Working ({$responseTime}ms)");

            if ($this->option('detail')) {
                $driver = config('cache.default');
                $this->line("  📦 Driver: {$driver}");
            }

            return true;
        } catch (\Exception $e) {
            $this->error('  ❌ Cache error: ' . $e->getMessage());
            return false;
        }
    }

    protected function displaySummary(bool $allPassed): void
    {
        $this->info('═══════════════════════════════════════');

        if ($allPassed) {
            $this->info('✅ All Tests Passed!');
            $this->newLine();
            $this->info('🎉 Cryptocurrency system is ready for use');
        } else {
            $this->error('❌ Some Tests Failed');
            $this->newLine();
            $this->warn('⚠️  Please check the errors above and fix configuration');
            $this->warn('💡 Run with --verbose for more details');
        }
    }

    protected function getNativeCurrency(string $network): string
    {
        return match($network) {
            'ethereum' => 'ETH',
            'bsc' => 'BNB',
            'polygon' => 'MATIC',
            'bitcoin' => 'BTC',
            default => 'ETH',
        };
    }
}
