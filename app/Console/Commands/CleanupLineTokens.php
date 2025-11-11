<?php

namespace App\Console\Commands;

use App\Services\LineTokenService;
use Illuminate\Console\Command;

/**
 * Cleanup expired LINE access tokens
 *
 * This command verifies and removes invalid/expired LINE tokens
 * from both database and cache.
 *
 * Usage: php artisan line:cleanup-tokens
 * Scheduled: Should run daily via Task Scheduler
 */
class CleanupLineTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'line:cleanup-tokens
                          {--force : Force cleanup without confirmation}
                          {--dry-run : Show what would be cleaned without actually cleaning}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup expired and invalid LINE access tokens';

    /**
     * Execute the console command.
     */
    public function handle(LineTokenService $tokenService): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('🧹 Starting LINE token cleanup...');

        if (!$this->option('force') && !$isDryRun) {
            if (!$this->confirm('This will verify and remove expired LINE tokens. Continue?')) {
                $this->warn('❌ Cleanup cancelled.');
                return 1;
            }
        }

        if ($isDryRun) {
            // Count how many tokens exist
            $count = \App\Models\User::whereNotNull('line_access_token')->count();
            $this->info("ℹ️  Found {$count} users with LINE tokens");
            $this->info("ℹ️  Would verify each token with LINE API and remove expired ones");
            return 0;
        }

        // Show progress
        $this->newLine();
        $usersWithTokens = \App\Models\User::whereNotNull('line_access_token')->get();
        $totalUsers = $usersWithTokens->count();

        if ($totalUsers === 0) {
            $this->info('ℹ️  No LINE tokens found to cleanup.');
            return 0;
        }

        $this->info("📊 Checking {$totalUsers} LINE tokens...");
        $bar = $this->output->createProgressBar($totalUsers);
        $bar->start();

        $cleaned = 0;
        foreach ($usersWithTokens as $user) {
            // Verify token
            if (!$tokenService->verifyToken($user)) {
                // Token is expired or invalid, remove it
                $tokenService->revokeAccessToken($user);
                $cleaned++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Show results
        $this->info('✅ Cleanup completed!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total tokens checked', $totalUsers],
                ['Expired/invalid tokens cleaned', $cleaned],
                ['Valid tokens remaining', $totalUsers - $cleaned],
            ]
        );

        if ($cleaned > 0) {
            $this->info("🎉 Cleaned up {$cleaned} expired tokens!");
        } else {
            $this->info('✨ All tokens are valid!');
        }

        return 0;
    }
}
