<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use App\Services\AddonService;
use Illuminate\Console\Command;

class LicenseStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'license:status
                            {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display detailed license status';

    /**
     * Execute the console command.
     */
    public function handle(LicenseService $licenseService, AddonService $addonService): int
    {
        $status = $licenseService->status();

        // JSON output
        if ($this->option('json')) {
            $this->line(json_encode($status, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        // Pretty output
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║          TP-Affiliate Pro License Status                  ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();

        if (!$status['is_active']) {
            $this->error('✗ No active license found');
            $this->newLine();
            $this->comment('To activate a license, run:');
            $this->line('  php artisan license:activate {license-key}');
            $this->newLine();
            $this->line('Get your license at: https://xman4289.com');
            return self::FAILURE;
        }

        // License Information
        $this->info('📋 License Information');
        $this->newLine();

        $licenseRows = [
            ['License Key', substr($status['license_key'], 0, 8) . '...' . substr($status['license_key'], -4)],
            ['Status', $this->formatStatus($status['status'])],
        ];

        if (isset($status['domain'])) {
            $licenseRows[] = ['Domain', $status['domain']];
        }

        if (isset($status['installation_id'])) {
            $licenseRows[] = ['Installation ID', substr($status['installation_id'], 0, 8) . '...'];
        }

        if (isset($status['expires_at'])) {
            $expiresAt = $status['expires_at'];
            if ($expiresAt) {
                $daysLeft = \Carbon\Carbon::parse($expiresAt)->diffInDays(now());
                $expiryText = $expiresAt . " ({$daysLeft} days left)";
                if ($daysLeft <= 30) {
                    $expiryText = "<fg=yellow>{$expiryText}</>";
                }
                $licenseRows[] = ['Expires', $expiryText];
            } else {
                $licenseRows[] = ['Expires', '✓ Never'];
            }
        }

        if (isset($status['activated_at'])) {
            $licenseRows[] = ['Activated', $status['activated_at']];
        }

        if (isset($status['last_check'])) {
            $licenseRows[] = ['Last Check', $status['last_check']];
        }

        $this->table(['Property', 'Value'], $licenseRows);

        // System Information
        $this->newLine();
        $this->info('💻 System Information');
        $this->newLine();

        $systemRows = [
            ['Application', 'TP-Affiliate Pro'],
            ['Version', config('version.current')],
            ['Laravel', app()->version()],
            ['PHP', PHP_VERSION],
            ['Environment', app()->environment()],
        ];

        if (config('license.developer_mode')) {
            $systemRows[] = ['Mode', '🛠️  Developer Mode'];
        }

        $this->table(['Property', 'Value'], $systemRows);

        // Add-ons Status
        $addons = $addonService->checkAllAddons();

        if (!empty($addons)) {
            $this->newLine();
            $this->info('🧩 Add-ons Status');
            $this->newLine();

            $addonRows = [];
            foreach ($addons as $slug => $addon) {
                $statusIcon = $addon['activated'] ? '✓' : '✗';
                $statusText = $addon['activated'] ? '<fg=green>Active</>' : '<fg=red>Inactive</>';
                $licenseIcon = $addon['has_license'] ? '🔑' : '-';

                $addonRows[] = [
                    $addon['name'],
                    $addon['enabled'] ? 'Enabled' : 'Disabled',
                    $licenseIcon,
                    $statusText,
                ];
            }

            $this->table(['Add-on', 'Config', 'License', 'Status'], $addonRows);
        }

        // Warnings
        $warnings = [];

        if ($licenseService->isExpiringSoon(30)) {
            $warnings[] = '⚠️  License is expiring soon!';
        }

        if ($licenseService->isExpiringSoon(7)) {
            $warnings[] = '🚨 License expires in less than 7 days!';
        }

        if (config('license.developer_mode')) {
            $warnings[] = '🛠️  Developer mode is enabled (license checks bypassed)';
        }

        if (!empty($warnings)) {
            $this->newLine();
            foreach ($warnings as $warning) {
                $this->warn($warning);
            }
            if ($licenseService->isExpiringSoon(30)) {
                $this->newLine();
                $this->line('Renew your license at: https://xman4289.com/renew');
            }
        }

        // Available Commands
        $this->newLine();
        $this->comment('Available Commands:');
        $this->line('  • license:check              Check license validity with server');
        $this->line('  • license:activate {key}     Activate a new license');
        $this->line('  • license:deactivate         Deactivate current license');
        $this->line('  • addon:list                 List available add-ons');
        $this->line('  • app:check-update           Check for updates');

        return self::SUCCESS;
    }

    /**
     * Format status with color
     */
    protected function formatStatus(string $status): string
    {
        return match ($status) {
            'active' => '<fg=green>✓ Active</>',
            'expired' => '<fg=red>✗ Expired</>',
            'inactive' => '<fg=yellow>⚠ Inactive</>',
            'suspended' => '<fg=red>✗ Suspended</>',
            default => $status,
        };
    }
}
