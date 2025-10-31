<?php

namespace App\Console\Commands;

use App\Services\AddonService;
use App\Services\LicenseService;
use Illuminate\Console\Command;

class AddonEnableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'addon:enable
                            {slug? : The add-on slug to enable}
                            {license-key? : The add-on license key}
                            {--force : Force enable even if validation fails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable an add-on with license key';

    /**
     * Execute the console command.
     */
    public function handle(AddonService $addonService, LicenseService $licenseService): int
    {
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║          TP-Affiliate Pro Add-on Activation               ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Check core license first
        $licenseStatus = $licenseService->status();
        if (!$licenseStatus['is_active']) {
            $this->error('✗ No active TP-Affiliate Pro license found!');
            $this->newLine();
            $this->comment('Add-ons require an active core license.');
            $this->line('Activate your license with: php artisan license:activate {key}');
            return self::FAILURE;
        }

        // Get add-on slug
        $slug = $this->argument('slug');
        if (!$slug) {
            $slug = $this->anticipate(
                'Enter the add-on slug',
                array_keys(config('license.addons', []))
            );
        }

        if (!$slug) {
            $this->error('Add-on slug is required.');
            return self::FAILURE;
        }

        // Validate slug exists in config
        $configuredAddons = config('license.addons', []);
        if (!isset($configuredAddons[$slug])) {
            $this->error("Add-on '{$slug}' is not configured.");
            $this->newLine();
            $this->comment('Available add-ons:');
            foreach (array_keys($configuredAddons) as $availableSlug) {
                $this->line("  • {$availableSlug}");
            }
            return self::FAILURE;
        }

        $addonConfig = $configuredAddons[$slug];

        // Check if already enabled
        if ($addonService->isAddonActivated($slug) && !$this->option('force')) {
            $this->warn("Add-on '{$addonConfig['name']}' is already activated!");
            $this->newLine();

            if (!$this->confirm('Do you want to reactivate with a different license?', false)) {
                return self::SUCCESS;
            }
        }

        // Get license key
        $licenseKey = $this->argument('license-key');
        if (!$licenseKey) {
            $licenseKey = $this->ask('Enter the add-on license key');
        }

        if (!$licenseKey) {
            $this->error('Add-on license key is required.');
            return self::FAILURE;
        }

        // Validate format
        if (strlen($licenseKey) < 20) {
            $this->error('Invalid license key format.');
            return self::FAILURE;
        }

        // Enable add-on
        $this->info("Activating {$addonConfig['name']}...");

        $result = $addonService->enableAddon($slug, $licenseKey);

        $this->newLine();

        if ($result['success']) {
            $this->info('✓ Add-on activated successfully!');
            $this->newLine();

            $this->table(
                ['Property', 'Value'],
                [
                    ['Add-on', $addonConfig['name']],
                    ['Slug', $slug],
                    ['License Key', substr($licenseKey, 0, 8) . '...' . substr($licenseKey, -4)],
                    ['Status', '<fg=green>Active</>'],
                ]
            );

            $this->newLine();
            $this->info('🎉 ' . $addonConfig['name'] . ' is now ready to use!');
            $this->newLine();

            // Show next steps if available
            if (isset($addonConfig['setup_instructions'])) {
                $this->comment('Next steps:');
                $this->line($addonConfig['setup_instructions']);
                $this->newLine();
            }

            $this->comment('Commands:');
            $this->line('  • addon:list              View all add-ons');
            $this->line('  • addon:disable {slug}    Disable this add-on');
            $this->line('  • license:status          View license details');

            return self::SUCCESS;
        }

        $this->error('✗ Add-on activation failed!');
        $this->newLine();

        $error = $result['error'] ?? 'unknown_error';
        $message = $result['message'] ?? 'An unknown error occurred';

        $this->error("Error: {$error}");
        $this->line($message);
        $this->newLine();

        // Provide helpful suggestions
        match ($error) {
            'invalid_addon_license' => $this->comment('The add-on license key is invalid. Please check and try again.'),
            'addon_expired' => $this->comment('The add-on license has expired. Renew at https://xman4289.com/renew'),
            'requires_core_license' => $this->comment('Add-on requires an active core license.'),
            'network_error' => $this->comment('Could not connect to license server. Check your connection.'),
            default => $this->comment('Contact support: https://xman4289.com/support'),
        };

        return self::FAILURE;
    }
}
