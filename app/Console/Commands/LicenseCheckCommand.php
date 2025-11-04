<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use Illuminate\Console\Command;

class LicenseCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'license:check
                            {--clear-cache : Clear license cache before checking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check license validity with server';

    /**
     * Execute the console command.
     */
    public function handle(LicenseService $licenseService): int
    {
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║          Checking License Validity...                     ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Clear cache if requested
        if ($this->option('clear-cache')) {
            $this->info('Clearing license cache...');
            $licenseService->clearCache();
            $this->newLine();
        }

        // Validate license
        $result = $licenseService->validate();

        if ($result['valid']) {
            $this->info('✓ License is valid and active!');
            $this->newLine();

            $license = $result['license'] ?? [];

            $rows = [
                ['License Key', substr($license['key'] ?? '', 0, 8) . '...' . substr($license['key'] ?? '', -4)],
                ['Status', '✓ ' . ($license['status'] ?? 'active')],
                ['Domain', $license['domain'] ?? url('/')],
            ];

            if (isset($license['customer_name']) || isset($license['customer_email'])) {
                $rows[] = ['Customer', $license['customer_name'] ?? $license['customer_email']];
            }

            if (isset($license['expires_at'])) {
                $expiresAt = $license['expires_at'];
                $daysLeft = $expiresAt ? \Carbon\Carbon::parse($expiresAt)->diffInDays(now()) : null;

                if ($daysLeft !== null) {
                    $expiryText = $expiresAt . " ({$daysLeft} days left)";
                    if ($daysLeft <= 30) {
                        $expiryText = "⚠️  {$expiryText}";
                    }
                    $rows[] = ['Expires At', $expiryText];
                } else {
                    $rows[] = ['Expires At', 'Never'];
                }
            }

            if (isset($license['activation_count']) && isset($license['max_activations'])) {
                $rows[] = ['Activations', $license['activation_count'] . '/' . $license['max_activations']];
            }

            if (isset($license['created_at'])) {
                $rows[] = ['Created At', $license['created_at']];
            }

            if (isset($result['checked_at'])) {
                $rows[] = ['Last Checked', $result['checked_at']];
            }

            if (isset($result['cached'])) {
                $rows[] = ['Source', $result['cached'] ? '📦 Cached' : '🌐 Server'];
            }

            $this->table(['Property', 'Value'], $rows);

            // Check if expiring soon
            if ($licenseService->isExpiringSoon(30)) {
                $this->newLine();
                $this->warn('⚠️  Your license is expiring soon!');
                $this->line('Renew at: https://xman4289.com/renew');
            }

            return self::SUCCESS;
        }

        $this->error('✗ License validation failed!');
        $this->newLine();

        $error = $result['error'] ?? 'unknown_error';
        $message = $result['message'] ?? 'License is invalid or inactive';

        $this->error("Error: {$error}");
        $this->line($message);
        $this->newLine();

        // Provide helpful suggestions
        match ($error) {
            'no_license' => $this->comment('No license key found. Run: php artisan license:activate {key}'),
            'invalid_license' => $this->comment('License key is invalid. Please check your license key.'),
            'expired_license' => $this->comment('License has expired. Renew at https://xman4289.com/renew'),
            'domain_mismatch' => $this->comment('License is registered to a different domain.'),
            'inactive_license' => $this->comment('License is inactive. Please contact support.'),
            'network_error' => $this->comment('Could not connect to license server. Check your connection.'),
            default => $this->comment('Contact support: https://xman4289.com/support'),
        };

        $this->newLine();
        $this->comment('Commands:');
        $this->line('  • license:activate {key}    Activate a new license');
        $this->line('  • license:status            View license details');
        $this->line('  • license:deactivate        Deactivate current license');

        return self::FAILURE;
    }
}
