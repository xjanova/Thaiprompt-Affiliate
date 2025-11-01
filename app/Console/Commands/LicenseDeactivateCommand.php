<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use Illuminate\Console\Command;

class LicenseDeactivateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'license:deactivate
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate the current license';

    /**
     * Execute the console command.
     */
    public function handle(LicenseService $licenseService): int
    {
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║          TP-Affiliate Pro License Deactivation            ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Check current status
        $status = $licenseService->status();

        if (!$status['is_active']) {
            $this->warn('No active license found to deactivate.');
            return self::SUCCESS;
        }

        // Show current license info
        $this->info('Current License:');
        $this->table(
            ['Property', 'Value'],
            [
                ['License Key', substr($status['license_key'], 0, 8) . '...' . substr($status['license_key'], -4)],
                ['Status', $status['status']],
                ['Domain', $status['domain'] ?? url('/')],
                ['Activated', $status['activated_at'] ?? 'N/A'],
            ]
        );
        $this->newLine();

        // Confirmation
        if (!$this->option('force')) {
            $this->warn('⚠️  Warning: Deactivating will disable all premium features.');
            $this->newLine();

            if (!$this->confirm('Are you sure you want to deactivate this license?', false)) {
                $this->info('Deactivation cancelled.');
                return self::SUCCESS;
            }
        }

        // Deactivate
        $this->info('Deactivating license...');

        $result = $licenseService->deactivate();

        $this->newLine();

        if ($result['success']) {
            $this->info('✓ License deactivated successfully!');
            $this->newLine();

            $this->line('Your license has been released from this domain.');
            $this->line('You can now activate it on another domain.');
            $this->newLine();

            $this->comment('To reactivate, run:');
            $this->line('  php artisan license:activate {license-key}');

            return self::SUCCESS;
        }

        $this->error('✗ License deactivation failed!');
        $this->newLine();

        $error = $result['error'] ?? 'unknown_error';
        $message = $result['message'] ?? 'An unknown error occurred';

        $this->error("Error: {$error}");
        $this->line($message);
        $this->newLine();

        // Provide helpful suggestions
        match ($error) {
            'network_error' => $this->comment('Could not connect to license server. The license is still active locally. Try again later or contact support.'),
            'invalid_license' => $this->comment('License key is invalid or not found on server.'),
            default => $this->comment('If the problem persists, contact support: https://xman4289.com/support'),
        };

        return self::FAILURE;
    }
}
