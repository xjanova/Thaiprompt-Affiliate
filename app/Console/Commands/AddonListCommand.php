<?php

namespace App\Console\Commands;

use App\Services\AddonService;
use App\Services\LicenseService;
use Illuminate\Console\Command;

class AddonListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'addon:list
                            {--available : Show all available add-ons from server}
                            {--activated : Show only activated add-ons}
                            {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List available and activated add-ons';

    /**
     * Execute the console command.
     */
    public function handle(AddonService $addonService, LicenseService $licenseService): int
    {
        // JSON output
        if ($this->option('json')) {
            $data = [
                'configured' => $addonService->checkAllAddons(),
            ];

            if ($this->option('available')) {
                $data['available'] = $addonService->getAvailableAddons();
            }

            if ($this->option('activated')) {
                $data['activated'] = $addonService->getActivatedAddons();
            }

            $this->line(json_encode($data, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║          TP-Affiliate Pro Add-ons Manager                 ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Check license status first
        $licenseStatus = $licenseService->status();
        if (!$licenseStatus['is_active']) {
            $this->warn('⚠️  No active license found.');
            $this->newLine();
            $this->comment('Add-ons require an active TP-Affiliate Pro license.');
            $this->line('Activate your license with: php artisan license:activate {key}');
            $this->newLine();
        }

        // Show available add-ons from server
        if ($this->option('available')) {
            return $this->showAvailableAddons($addonService);
        }

        // Show only activated add-ons
        if ($this->option('activated')) {
            return $this->showActivatedAddons($addonService);
        }

        // Default: Show configured add-ons status
        return $this->showConfiguredAddons($addonService);
    }

    /**
     * Show configured add-ons status
     */
    protected function showConfiguredAddons(AddonService $addonService): int
    {
        $addons = $addonService->checkAllAddons();

        if (empty($addons)) {
            $this->info('No add-ons configured.');
            $this->newLine();
            $this->comment('To see available add-ons, run:');
            $this->line('  php artisan addon:list --available');
            return self::SUCCESS;
        }

        $this->info('🧩 Configured Add-ons');
        $this->newLine();

        $rows = [];
        foreach ($addons as $slug => $addon) {
            $statusIcon = $addon['activated'] ? '<fg=green>✓</>' : '<fg=red>✗</>';
            $configStatus = $addon['enabled'] ? '<fg=green>Enabled</>' : '<fg=yellow>Disabled</>';
            $licenseStatus = $addon['has_license'] ? '<fg=green>🔑 Yes</>' : '<fg=red>✗ No</>';

            $rows[] = [
                $statusIcon,
                $addon['name'],
                $slug,
                $configStatus,
                $licenseStatus,
                $addon['activated'] ? '<fg=green>Active</>' : '<fg=red>Inactive</>',
            ];
        }

        $this->table(
            ['', 'Name', 'Slug', 'Config', 'License', 'Status'],
            $rows
        );

        $this->newLine();

        // Count summary
        $activeCount = collect($addons)->filter(fn($a) => $a['activated'])->count();
        $totalCount = count($addons);

        $this->info("Active: {$activeCount}/{$totalCount}");
        $this->newLine();

        // Show commands
        $this->comment('Commands:');
        $this->line('  • addon:enable {slug} {license-key}    Enable an add-on');
        $this->line('  • addon:disable {slug}                 Disable an add-on');
        $this->line('  • addon:list --available               Show all available add-ons');
        $this->line('  • addon:list --activated               Show activated add-ons');

        return self::SUCCESS;
    }

    /**
     * Show available add-ons from server
     */
    protected function showAvailableAddons(AddonService $addonService): int
    {
        $this->info('Fetching available add-ons from server...');
        $this->newLine();

        $addons = $addonService->getAvailableAddons();

        if (empty($addons)) {
            $this->warn('No add-ons available or could not connect to server.');
            return self::SUCCESS;
        }

        $this->info('🛒 Available Add-ons');
        $this->newLine();

        $configuredAddons = $addonService->checkAllAddons();

        $rows = [];
        foreach ($addons as $addon) {
            $slug = $addon['slug'] ?? '';
            $isConfigured = isset($configuredAddons[$slug]);
            $isActivated = $isConfigured && $configuredAddons[$slug]['activated'];

            $statusIcon = $isActivated ? '<fg=green>✓ Installed</>' : ($isConfigured ? '<fg=yellow>⚠ Configured</>' : '<fg=gray>Not installed</>');

            $rows[] = [
                $addon['name'] ?? 'Unknown',
                $slug,
                $addon['version'] ?? 'N/A',
                $addon['price'] ?? 'N/A',
                $statusIcon,
            ];
        }

        $this->table(
            ['Name', 'Slug', 'Version', 'Price (฿)', 'Status'],
            $rows
        );

        $this->newLine();
        $this->info('Total available: ' . count($addons));
        $this->newLine();
        $this->line('Purchase add-ons at: https://xman4289.com/addons');

        return self::SUCCESS;
    }

    /**
     * Show activated add-ons
     */
    protected function showActivatedAddons(AddonService $addonService): int
    {
        $this->info('Fetching activated add-ons...');
        $this->newLine();

        $addons = $addonService->getActivatedAddons();

        if (empty($addons)) {
            $this->warn('No activated add-ons found.');
            $this->newLine();
            $this->comment('To enable an add-on, run:');
            $this->line('  php artisan addon:enable {slug} {license-key}');
            return self::SUCCESS;
        }

        $this->info('✓ Activated Add-ons');
        $this->newLine();

        $rows = [];
        foreach ($addons as $addon) {
            $rows[] = [
                $addon['name'] ?? 'Unknown',
                $addon['slug'] ?? 'N/A',
                $addon['version'] ?? 'N/A',
                $addon['expires_at'] ?? 'Never',
                $addon['status'] ?? 'N/A',
            ];
        }

        $this->table(
            ['Name', 'Slug', 'Version', 'Expires', 'Status'],
            $rows
        );

        $this->newLine();
        $this->info('Total activated: ' . count($addons));

        return self::SUCCESS;
    }
}
