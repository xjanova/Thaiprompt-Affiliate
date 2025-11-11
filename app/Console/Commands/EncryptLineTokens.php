<?php

namespace App\Console\Commands;

use App\Services\LineTokenService;
use Illuminate\Console\Command;

/**
 * Encrypt existing LINE access tokens
 *
 * This command should be run once during deployment to migrate
 * existing plain text tokens to encrypted format.
 *
 * Usage: php artisan line:encrypt-tokens
 */
class EncryptLineTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'line:encrypt-tokens
                          {--force : Force encryption without confirmation}
                          {--dry-run : Show what would be encrypted without actually encrypting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypt existing LINE access tokens in database';

    /**
     * Execute the console command.
     */
    public function handle(LineTokenService $tokenService): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('🔐 Starting LINE token encryption migration...');

        if (!$this->option('force') && !$isDryRun) {
            if (!$this->confirm('This will encrypt all LINE access tokens in the database. Continue?')) {
                $this->warn('❌ Migration cancelled.');
                return 1;
            }
        }

        // Start progress
        $this->newLine();
        $this->info('📊 Processing tokens...');

        if ($isDryRun) {
            // Just count how many would be affected
            $count = \App\Models\User::whereNotNull('line_access_token')->count();
            $this->info("ℹ️  Would process {$count} users with LINE tokens");
            return 0;
        }

        // Actual migration
        $stats = $tokenService->migrateExistingTokens();

        $this->newLine();
        $this->info('✅ Migration completed!');
        $this->table(
            ['Status', 'Count'],
            [
                ['Encrypted successfully', $stats['success']],
                ['Failed', $stats['failed']],
            ]
        );

        if ($stats['failed'] > 0) {
            $this->warn('⚠️  Some tokens failed to encrypt. Check logs for details.');
            return 1;
        }

        $this->info('🎉 All tokens encrypted successfully!');
        return 0;
    }
}
