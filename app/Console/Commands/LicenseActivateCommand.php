<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use Illuminate\Console\Command;

class LicenseActivateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'license:activate
                            {license-key? : The license key to activate}
                            {--force : Force activation even if already activated}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate TP-Affiliate Pro license';

    /**
     * Execute the console command.
     */
    public function handle(LicenseService $licenseService): int
    {
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║          TP-Affiliate Pro License Activation              ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Get license key from argument or prompt
        $licenseKey = $this->argument('license-key');

        if (!$licenseKey) {
            $licenseKey = $this->ask('Please enter your license key');
        }

        if (!$licenseKey) {
            $this->error('License key is required.');
            return self::FAILURE;
        }

        // Validate format
        if (strlen($licenseKey) < 20) {
            $this->error('Invalid license key format.');
            return self::FAILURE;
        }

        // Check if already activated
        $currentStatus = $licenseService->status();
        if ($currentStatus['is_active'] && !$this->option('force')) {
            $this->warn('License is already activated!');
            $this->newLine();
            $this->table(
                ['Property', 'Value'],
                [
                    ['License Key', substr($currentStatus['license_key'], 0, 8) . '...' . substr($currentStatus['license_key'], -4)],
                    ['Status', $currentStatus['status']],
                    ['Domain', $currentStatus['domain'] ?? 'Not set'],
                    ['Expires', $currentStatus['expires_at'] ?? 'Never'],
                ]
            );
            $this->newLine();

            if (!$this->confirm('Do you want to activate a different license?', false)) {
                return self::SUCCESS;
            }
        }

        // Activate
        $this->info('Activating license...');

        $result = $licenseService->activate($licenseKey);

        $this->newLine();

        if ($result['success']) {
            $this->info('✓ License activated successfully!');
            $this->newLine();

            $activation = $result['activation'] ?? [];
            $license = $result['license'] ?? [];

            $this->table(
                ['Property', 'Value'],
                [
                    ['License Key', substr($licenseKey, 0, 8) . '...' . substr($licenseKey, -4)],
                    ['Status', $license['status'] ?? 'active'],
                    ['Domain', $activation['domain'] ?? url('/')],
                    ['Customer', $license['customer_name'] ?? $license['customer_email'] ?? 'N/A'],
                    ['Activated At', $activation['activated_at'] ?? now()->toDateTimeString()],
                    ['Expires At', $license['expires_at'] ?? 'Never'],
                    ['Max Activations', ($license['activation_count'] ?? 0) . '/' . ($license['max_activations'] ?? 'Unlimited')],
                ]
            );

            $this->newLine();
            $this->info('🎉 You can now use all features of TP-Affiliate Pro!');
            $this->newLine();

            $this->comment('Next steps:');
            $this->line('  • Run: php artisan addon:list          (Check available add-ons)');
            $this->line('  • Run: php artisan license:status      (View license details)');
            $this->line('  • Run: php artisan app:check-update    (Check for updates)');

            return self::SUCCESS;
        }

        $this->error('✗ License activation failed!');
        $this->newLine();

        $error = $result['error'] ?? 'unknown_error';
        $message = $result['message'] ?? 'An unknown error occurred';

        $this->error("Error: {$error}");
        $this->line($message);
        $this->newLine();

        // Provide helpful suggestions
        match ($error) {
            'invalid_license' => $this->comment('Please check your license key and try again.'),
            'expired_license' => $this->comment('Your license has expired. Please renew at https://xman4289.com'),
            'domain_mismatch' => $this->comment('This license is registered to a different domain.'),
            'activation_limit' => $this->comment('License activation limit reached. Please deactivate from another domain or contact support.'),
            'network_error' => $this->comment('Could not connect to license server. Please check your internet connection.'),
            default => $this->comment('If the problem persists, please contact support at https://xman4289.com/support'),
        };

        return self::FAILURE;
    }
}
