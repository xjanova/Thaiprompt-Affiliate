<?php

namespace App\Console\Commands;

use App\Services\Crypto\Web3Service;
use App\Services\Crypto\BlockchainIndexerService;
use App\Models\CryptoWallet;
use App\Models\CryptoTransaction;
use App\Models\CryptoWithdrawalRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Cryptocurrency System Health Check Command
 *
 * Performs comprehensive health checks on the crypto payment gateway system
 */
class CryptoHealthCheck extends Command
{
    protected $signature = 'crypto:health-check
                            {--network= : Check specific network only}
                            {--fix : Attempt to fix issues automatically}';

    protected $description = 'Perform health check on cryptocurrency payment gateway';

    protected Web3Service $web3Service;
    protected BlockchainIndexerService $indexerService;
    protected array $issues = [];
    protected array $warnings = [];

    public function __construct(
        Web3Service $web3Service,
        BlockchainIndexerService $indexerService
    ) {
        parent::__construct();
        $this->web3Service = $web3Service;
        $this->indexerService = $indexerService;
    }

    public function handle(): int
    {
        $this->info('🔍 Starting Cryptocurrency System Health Check...');
        $this->newLine();

        // 1. Check RPC Connectivity
        $this->checkRpcConnectivity();

        // 2. Check Indexer API Keys
        $this->checkIndexerApiKeys();

        // 3. Check Database Health
        $this->checkDatabaseHealth();

        // 4. Check Queue Status
        $this->checkQueueStatus();

        // 5. Check Stuck Transactions
        $this->checkStuckTransactions();

        // 6. Check Pending Withdrawals
        $this->checkPendingWithdrawals();

        // 7. Check Hot Wallet Balances
        $this->checkHotWalletBalances();

        // 8. Check Scheduled Tasks
        $this->checkScheduledTasks();

        // 9. Check Cache Health
        $this->checkCacheHealth();

        // 10. Check Security Settings
        $this->checkSecuritySettings();

        // Display Summary
        $this->displaySummary();

        return count($this->issues) > 0 ? 1 : 0;
    }

    protected function checkRpcConnectivity(): void
    {
        $this->info('📡 Checking RPC Connectivity...');

        $networks = ['ethereum', 'bsc', 'polygon'];

        if ($this->option('network')) {
            $networks = [$this->option('network')];
        }

        foreach ($networks as $network) {
            try {
                $blockNumber = $this->web3Service->getBlockNumber($network);

                if ($blockNumber) {
                    $this->line("  ✅ {$network}: Connected (Block #{$blockNumber})");
                } else {
                    $this->issues[] = "{$network}: Failed to get block number";
                    $this->error("  ❌ {$network}: Connection failed");
                }
            } catch (\Exception $e) {
                $this->issues[] = "{$network}: {$e->getMessage()}";
                $this->error("  ❌ {$network}: {$e->getMessage()}");
            }
        }

        $this->newLine();
    }

    protected function checkIndexerApiKeys(): void
    {
        $this->info('🔑 Checking Indexer API Keys...');

        $networks = ['ethereum', 'bsc', 'polygon'];

        foreach ($networks as $network) {
            if ($this->indexerService->isConfigured($network)) {
                // Test API call
                $blockNumber = $this->indexerService->getBlockNumber($network);

                if ($blockNumber) {
                    $this->line("  ✅ {$network}: API key valid");
                } else {
                    $this->warnings[] = "{$network}: API key may be invalid or rate limited";
                    $this->warn("  ⚠️  {$network}: API call failed");
                }
            } else {
                $this->warnings[] = "{$network}: API key not configured";
                $this->warn("  ⚠️  {$network}: API key not configured");
            }
        }

        $this->newLine();
    }

    protected function checkDatabaseHealth(): void
    {
        $this->info('💾 Checking Database Health...');

        try {
            // Check connection
            DB::connection()->getPdo();
            $this->line('  ✅ Database connection: OK');

            // Check table sizes
            $walletsCount = CryptoWallet::count();
            $transactionsCount = CryptoTransaction::count();
            $withdrawalsCount = CryptoWithdrawalRequest::count();

            $this->line("  📊 Wallets: {$walletsCount}");
            $this->line("  📊 Transactions: {$transactionsCount}");
            $this->line("  📊 Withdrawal Requests: {$withdrawalsCount}");

            // Check for missing indexes (sample check)
            $hasIndex = DB::select("
                SELECT indexname FROM pg_indexes
                WHERE tablename = 'crypto_transactions'
                AND indexname = 'idx_crypto_transactions_tx_hash'
            ");

            if (empty($hasIndex)) {
                $this->warnings[] = "Missing recommended database indexes";
                $this->warn('  ⚠️  Missing recommended indexes (check CRYPTO_PRODUCTION_DEPLOYMENT.md)');
            }

        } catch (\Exception $e) {
            $this->issues[] = "Database error: {$e->getMessage()}";
            $this->error("  ❌ Database error: {$e->getMessage()}");
        }

        $this->newLine();
    }

    protected function checkQueueStatus(): void
    {
        $this->info('📮 Checking Queue Status...');

        try {
            // Check Redis connection
            Cache::store('redis')->get('test');
            $this->line('  ✅ Redis connection: OK');

            // Check queue size
            $queueSize = DB::table('jobs')->count();

            if ($queueSize > 1000) {
                $this->warnings[] = "Large queue size: {$queueSize} jobs";
                $this->warn("  ⚠️  Queue size: {$queueSize} (consider adding more workers)");
            } else {
                $this->line("  ✅ Queue size: {$queueSize}");
            }

            // Check failed jobs
            $failedJobs = DB::table('failed_jobs')->count();

            if ($failedJobs > 0) {
                $this->warnings[] = "{$failedJobs} failed jobs";
                $this->warn("  ⚠️  Failed jobs: {$failedJobs}");
            } else {
                $this->line('  ✅ Failed jobs: 0');
            }

        } catch (\Exception $e) {
            $this->issues[] = "Queue error: {$e->getMessage()}";
            $this->error("  ❌ Queue error: {$e->getMessage()}");
        }

        $this->newLine();
    }

    protected function checkStuckTransactions(): void
    {
        $this->info('🔄 Checking for Stuck Transactions...');

        $stuckDeposits = CryptoTransaction::where('type', 'deposit')
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subHour())
            ->count();

        if ($stuckDeposits > 0) {
            $this->warnings[] = "{$stuckDeposits} deposits pending > 1 hour";
            $this->warn("  ⚠️  {$stuckDeposits} deposits pending > 1 hour");

            if ($this->option('fix')) {
                $this->info('  🔧 Attempting to fix...');
                $this->call('crypto:scan-deposits');
            }
        } else {
            $this->line('  ✅ No stuck deposits');
        }

        $stuckWithdrawals = CryptoTransaction::where('type', 'withdrawal')
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subHour())
            ->count();

        if ($stuckWithdrawals > 0) {
            $this->warnings[] = "{$stuckWithdrawals} withdrawals pending > 1 hour";
            $this->warn("  ⚠️  {$stuckWithdrawals} withdrawals pending > 1 hour");
        } else {
            $this->line('  ✅ No stuck withdrawals');
        }

        $this->newLine();
    }

    protected function checkPendingWithdrawals(): void
    {
        $this->info('💸 Checking Pending Withdrawals...');

        $pendingWithdrawals = CryptoWithdrawalRequest::whereIn('status', ['pending', 'reviewing'])
            ->count();

        if ($pendingWithdrawals > 0) {
            $this->line("  📋 Pending withdrawals: {$pendingWithdrawals}");

            $oldPending = CryptoWithdrawalRequest::whereIn('status', ['pending', 'reviewing'])
                ->where('created_at', '<', now()->subHours(24))
                ->count();

            if ($oldPending > 0) {
                $this->warnings[] = "{$oldPending} withdrawals pending > 24 hours";
                $this->warn("  ⚠️  {$oldPending} withdrawals pending > 24 hours (require manual review)");
            }
        } else {
            $this->line('  ✅ No pending withdrawals');
        }

        $this->newLine();
    }

    protected function checkHotWalletBalances(): void
    {
        $this->info('🔥 Checking Hot Wallet Balances...');

        $custodialWallets = CryptoWallet::where('type', 'custodial')
            ->where('is_active', true)
            ->with('balances.currency')
            ->get();

        foreach ($custodialWallets as $wallet) {
            foreach ($wallet->balances as $balance) {
                if ($balance->balance > 0) {
                    $maxBalance = $this->getMaxHotWalletBalance($balance->currency->code);

                    if ($balance->balance > $maxBalance) {
                        $this->warnings[] = "Hot wallet {$balance->currency->code} balance ({$balance->balance}) exceeds threshold ({$maxBalance})";
                        $this->warn("  ⚠️  {$balance->currency->code}: {$balance->balance} (threshold: {$maxBalance})");
                    } else {
                        $this->line("  ✅ {$balance->currency->code}: {$balance->balance}");
                    }
                }
            }
        }

        $this->newLine();
    }

    protected function checkScheduledTasks(): void
    {
        $this->info('⏰ Checking Scheduled Tasks...');

        // Check last deposit scan
        $lastDepositScan = Cache::get('crypto:last_deposit_scan');

        if ($lastDepositScan && $lastDepositScan > now()->subMinutes(5)) {
            $this->line('  ✅ Deposit scanning: Active');
        } else {
            $this->issues[] = 'Deposit scanning not running (last run > 5 minutes ago)';
            $this->error('  ❌ Deposit scanning: Inactive');
        }

        // Check last withdrawal processing
        $lastWithdrawalProcess = Cache::get('crypto:last_withdrawal_process');

        if ($lastWithdrawalProcess && $lastWithdrawalProcess > now()->subMinutes(10)) {
            $this->line('  ✅ Withdrawal processing: Active');
        } else {
            $this->issues[] = 'Withdrawal processing not running (last run > 10 minutes ago)';
            $this->error('  ❌ Withdrawal processing: Inactive');
        }

        $this->newLine();
    }

    protected function checkCacheHealth(): void
    {
        $this->info('💨 Checking Cache Health...');

        try {
            // Test cache read/write
            Cache::put('crypto:health_check_test', 'ok', 60);
            $value = Cache::get('crypto:health_check_test');

            if ($value === 'ok') {
                $this->line('  ✅ Cache read/write: OK');
            } else {
                $this->issues[] = 'Cache read/write failed';
                $this->error('  ❌ Cache read/write: Failed');
            }

            Cache::forget('crypto:health_check_test');

        } catch (\Exception $e) {
            $this->issues[] = "Cache error: {$e->getMessage()}";
            $this->error("  ❌ Cache error: {$e->getMessage()}");
        }

        $this->newLine();
    }

    protected function checkSecuritySettings(): void
    {
        $this->info('🔒 Checking Security Settings...');

        // Check auto-approval settings
        if (config('crypto.auto_approve_enabled')) {
            $maxAmount = config('crypto.max_auto_approve_amount');
            $this->warn("  ⚠️  Auto-approval enabled (max: {$maxAmount} THB)");

            if ($maxAmount > 100000) {
                $this->warnings[] = "High auto-approval limit: {$maxAmount} THB";
                $this->warn('  ⚠️  Consider lowering auto-approval limit for production');
            }
        } else {
            $this->line('  ✅ Auto-approval: Disabled (manual review required)');
        }

        // Check KYC requirement
        if (config('crypto.security.require_kyc_for_withdrawal')) {
            $this->line('  ✅ KYC required for withdrawals');
        } else {
            $this->warnings[] = 'KYC not required for withdrawals';
            $this->warn('  ⚠️  KYC not required (enable for production)');
        }

        // Check 2FA requirement
        if (config('crypto.security.require_2fa_for_withdrawal')) {
            $this->line('  ✅ 2FA required for withdrawals');
        } else {
            $this->warnings[] = '2FA not required for withdrawals';
            $this->warn('  ⚠️  2FA not required (enable for production)');
        }

        $this->newLine();
    }

    protected function displaySummary(): void
    {
        $this->newLine();
        $this->info('📊 Health Check Summary');
        $this->info('═══════════════════════════════════════');

        if (count($this->issues) === 0 && count($this->warnings) === 0) {
            $this->info('✅ All systems operational!');
        } else {
            if (count($this->issues) > 0) {
                $this->error('❌ Critical Issues: ' . count($this->issues));
                foreach ($this->issues as $issue) {
                    $this->error('   • ' . $issue);
                }
                $this->newLine();
            }

            if (count($this->warnings) > 0) {
                $this->warn('⚠️  Warnings: ' . count($this->warnings));
                foreach ($this->warnings as $warning) {
                    $this->warn('   • ' . $warning);
                }
            }
        }

        $this->newLine();
    }

    protected function getMaxHotWalletBalance(string $currency): float
    {
        return match($currency) {
            'ETH' => env('CRYPTO_HOT_WALLET_MAX_BALANCE_ETH', 10),
            'BNB' => env('CRYPTO_HOT_WALLET_MAX_BALANCE_BNB', 100),
            'MATIC' => env('CRYPTO_HOT_WALLET_MAX_BALANCE_MATIC', 10000),
            'BTC' => env('CRYPTO_HOT_WALLET_MAX_BALANCE_BTC', 1),
            default => PHP_FLOAT_MAX,
        };
    }
}
