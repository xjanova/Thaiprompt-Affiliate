<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\LineTokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;

/**
 * Verify LINE token security implementation
 *
 * This command checks if all LINE tokens are properly encrypted
 * and provides security audit report.
 *
 * Usage: php artisan line:verify-security
 */
class VerifyLineTokenSecurity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'line:verify-security';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify LINE token security and encryption status';

    /**
     * Execute the console command.
     */
    public function handle(LineTokenService $tokenService): int
    {
        $this->info('🔒 LINE Token Security Audit');
        $this->info('═══════════════════════════════════════');
        $this->newLine();

        // Check 1: Count total users with LINE tokens
        $usersWithTokens = User::whereNotNull('line_access_token')->get();
        $totalUsers = $usersWithTokens->count();

        $this->info("📊 Total users with LINE tokens: {$totalUsers}");
        $this->newLine();

        if ($totalUsers === 0) {
            $this->info('✅ No LINE tokens found.');
            return 0;
        }

        // Check 2: Verify encryption status
        $encrypted = 0;
        $plainText = 0;
        $invalid = 0;
        $cached = 0;

        $bar = $this->output->createProgressBar($totalUsers);
        $bar->setFormat('Checking encryption: %current%/%max% [%bar%] %percent:3s%%');
        $bar->start();

        foreach ($usersWithTokens as $user) {
            try {
                // Try to decrypt - if successful, it's encrypted
                Crypt::decryptString($user->line_access_token);
                $encrypted++;

                // Check if cached
                if (Cache::has("line_token:{$user->id}")) {
                    $cached++;
                }
            } catch (\Exception $e) {
                // Check if it looks like a plain token (starts with typical patterns)
                if (strlen($user->line_access_token) > 100 &&
                    !str_contains($user->line_access_token, 'eyJ')) {
                    $plainText++;
                } else {
                    $invalid++;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Display results
        $this->table(
            ['Security Status', 'Count', 'Percentage'],
            [
                ['✅ Encrypted', $encrypted, $this->percentage($encrypted, $totalUsers)],
                ['⚠️  Plain Text', $plainText, $this->percentage($plainText, $totalUsers)],
                ['❌ Invalid', $invalid, $this->percentage($invalid, $totalUsers)],
                ['💾 Cached in Redis', $cached, $this->percentage($cached, $totalUsers)],
            ]
        );

        $this->newLine();

        // Check 3: Cache configuration
        $this->info('🔧 Cache Configuration:');
        $cacheDriver = config('cache.default');
        $this->line("  • Cache driver: {$cacheDriver}");

        if ($cacheDriver === 'redis') {
            $this->info('  ✅ Redis cache is enabled (optimal)');
        } else {
            $this->warn("  ⚠️  Cache driver is '{$cacheDriver}' - Redis recommended for production");
        }

        $this->newLine();

        // Check 4: Encryption configuration
        $this->info('🔐 Encryption Configuration:');
        $cipher = config('app.cipher');
        $this->line("  • Encryption cipher: {$cipher}");

        if ($cipher === 'AES-256-CBC') {
            $this->info('  ✅ AES-256-CBC encryption (secure)');
        } else {
            $this->warn("  ⚠️  Cipher is '{$cipher}' - AES-256-CBC recommended");
        }

        $this->newLine();

        // Overall security score
        $securityScore = 0;
        if ($plainText === 0 && $invalid === 0) {
            $securityScore += 50;
            $this->info('✅ All tokens are encrypted');
        } else {
            $this->error("❌ Found {$plainText} plain text and {$invalid} invalid tokens");
            $this->warn('   Run: php artisan line:encrypt-tokens');
        }

        if ($cacheDriver === 'redis') {
            $securityScore += 25;
        }

        if ($cipher === 'AES-256-CBC') {
            $securityScore += 25;
        }

        $this->newLine();
        $this->info("🎯 Overall Security Score: {$securityScore}/100");

        if ($securityScore === 100) {
            $this->info('🎉 Excellent! Your LINE token security is optimal!');
            return 0;
        } elseif ($securityScore >= 75) {
            $this->warn('⚠️  Good security, but there are improvements to make.');
            return 0;
        } else {
            $this->error('❌ Security needs improvement! Please address the issues above.');
            return 1;
        }
    }

    /**
     * Calculate percentage
     */
    private function percentage(int $part, int $total): string
    {
        if ($total === 0) {
            return '0%';
        }
        return round(($part / $total) * 100, 1) . '%';
    }
}
