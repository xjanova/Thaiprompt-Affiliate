<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SyncTPIXContracts extends Command
{
    protected $signature = 'tpix:sync-contracts {--force : Force sync even if contracts exist}';
    protected $description = 'Sync deployed TPIX smart contracts to database';

    public function handle()
    {
        $this->info('🔄 Syncing TPIX Contracts from blockchain deployment...');
        $this->newLine();

        // Load deployment file
        $deploymentPath = storage_path('app/tpix/deployments.json');

        if (!file_exists($deploymentPath)) {
            $this->error('❌ Deployment file not found!');
            $this->error('   Expected: ' . $deploymentPath);
            $this->newLine();
            $this->warn('💡 Please deploy contracts first:');
            $this->line('   cd tpix-blockchain && npm run deploy:mainnet');
            return 1;
        }

        $deployment = json_decode(file_get_contents($deploymentPath), true);

        if (!$deployment || !isset($deployment['contracts'])) {
            $this->error('❌ Invalid deployment file format!');
            return 1;
        }

        $this->info('📄 Deployment Info:');
        $this->line('   Network: ' . ($deployment['network'] ?? 'unknown'));
        $this->line('   Chain ID: ' . ($deployment['chainId'] ?? 'unknown'));
        $this->line('   Deployer: ' . ($deployment['deployer'] ?? 'unknown'));
        $this->line('   Timestamp: ' . ($deployment['timestamp'] ?? 'unknown'));
        $this->newLine();

        // Sync contracts
        $synced = 0;
        $failed = 0;

        foreach ($deployment['contracts'] as $name => $address) {
            try {
                DB::table('tpix_contracts')->updateOrInsert(
                    ['name' => $name],
                    [
                        'address' => $address,
                        'network' => $deployment['network'] ?? 'unknown',
                        'chain_id' => $deployment['chainId'] ?? 7000,
                        'deployer' => $deployment['deployer'] ?? null,
                        'deployed_at' => $deployment['timestamp'] ?? now(),
                        'updated_at' => now(),
                        'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                    ]
                );

                $this->line("  ✅ {$name}: {$address}");
                $synced++;
            } catch (\Exception $e) {
                $this->error("  ❌ {$name}: " . $e->getMessage());
                $failed++;
            }
        }

        $this->newLine();

        // Update .env if needed
        if ($this->option('force') || !env('TPIX_TOKEN_ADDRESS')) {
            $this->info('📝 Updating .env file...');

            $envPath = base_path('.env');
            $envContent = file_get_contents($envPath);

            $updates = [
                'TPIX_TOKEN_ADDRESS' => $deployment['contracts']['TPIXToken'] ?? '',
                'TPIX_DEX_FACTORY_ADDRESS' => $deployment['contracts']['DEXFactory'] ?? '',
                'TPIX_DEX_ROUTER_ADDRESS' => $deployment['contracts']['DEXRouter'] ?? '',
                'TPIX_WETH_ADDRESS' => $deployment['contracts']['WETH'] ?? '',
            ];

            foreach ($updates as $key => $value) {
                if ($value) {
                    if (str_contains($envContent, $key)) {
                        $envContent = preg_replace(
                            "/^{$key}=.*/m",
                            "{$key}={$value}",
                            $envContent
                        );
                    } else {
                        $envContent .= "\n{$key}={$value}";
                    }
                    $this->line("  ✅ Updated {$key}");
                }
            }

            file_put_contents($envPath, $envContent);
            $this->newLine();
        }

        // Summary
        $this->info('='.str_repeat('=', 50));
        $this->info('✅ Sync Complete!');
        $this->info('='.str_repeat('=', 50));
        $this->line("   Synced: {$synced} contracts");
        if ($failed > 0) {
            $this->error("   Failed: {$failed} contracts");
        }
        $this->newLine();

        // Next steps
        $this->comment('📖 Next Steps:');
        $this->line('   1. Verify contracts: cd tpix-blockchain && npm run verify');
        $this->line('   2. Setup initial pools: npm run setup-pools');
        $this->line('   3. Test deployment: npm run test-deployment');
        $this->line('   4. Clear config cache: php artisan config:clear');
        $this->newLine();

        return 0;
    }
}
