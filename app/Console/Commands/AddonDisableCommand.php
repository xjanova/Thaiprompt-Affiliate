<?php

namespace App\Console\Commands;

use App\Services\AddonService;
use Illuminate\Console\Command;

class AddonDisableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'addon:disable
                            {slug? : The add-on slug to disable}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable an add-on';

    /**
     * Execute the console command.
     */
    public function handle(AddonService $addonService): int
    {
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║          TP-Affiliate Pro Add-on Deactivation             ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Get add-on slug
        $slug = $this->argument('slug');
        if (!$slug) {
            $activeAddons = collect($addonService->checkAllAddons())
                ->filter(fn($addon) => $addon['enabled'])
                ->keys()
                ->toArray();

            if (empty($activeAddons)) {
                $this->warn('No enabled add-ons found.');
                return self::SUCCESS;
            }

            $slug = $this->choice('Select an add-on to disable', $activeAddons);
        }

        if (!$slug) {
            $this->error('Add-on slug is required.');
            return self::FAILURE;
        }

        // Validate slug exists in config
        $configuredAddons = config('license.addons', []);
        if (!isset($configuredAddons[$slug])) {
            $this->error("Add-on '{$slug}' is not configured.");
            return self::FAILURE;
        }

        $addonConfig = $configuredAddons[$slug];

        // Check if already disabled
        if (!$addonService->isAddonActivated($slug)) {
            $this->warn("Add-on '{$addonConfig['name']}' is already disabled.");
            return self::SUCCESS;
        }

        // Show current status
        $this->info('Current Add-on:');
        $this->table(
            ['Property', 'Value'],
            [
                ['Name', $addonConfig['name']],
                ['Slug', $slug],
                ['Status', '<fg=green>Active</>'],
            ]
        );
        $this->newLine();

        // Confirmation
        if (!$this->option('force')) {
            $this->warn('⚠️  Warning: Disabling will turn off all features of this add-on.');
            $this->newLine();

            if (!$this->confirm("Are you sure you want to disable '{$addonConfig['name']}'?", false)) {
                $this->info('Deactivation cancelled.');
                return self::SUCCESS;
            }
        }

        // Disable add-on
        $this->info('Disabling add-on...');

        $result = $addonService->disableAddon($slug);

        $this->newLine();

        if ($result['success']) {
            $this->info('✓ Add-on disabled successfully!');
            $this->newLine();
            $this->line("'{$addonConfig['name']}' has been disabled.");
            $this->line('Your license key remains valid and can be reactivated anytime.');
            $this->newLine();

            $this->comment('To reactivate, run:');
            $this->line("  php artisan addon:enable {$slug} {license-key}");

            return self::SUCCESS;
        }

        $this->error('✗ Add-on deactivation failed!');
        $this->newLine();

        $error = $result['error'] ?? 'unknown_error';
        $message = $result['message'] ?? 'An unknown error occurred';

        $this->error("Error: {$error}");
        $this->line($message);

        return self::FAILURE;
    }
}
