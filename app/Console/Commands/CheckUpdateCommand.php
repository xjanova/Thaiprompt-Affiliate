<?php

namespace App\Console\Commands;

use App\Services\VersionService;
use Illuminate\Console\Command;

class CheckUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-update
                            {--clear-cache : Clear version cache before checking}
                            {--list : List all available versions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for application updates from GitHub';

    /**
     * Execute the console command.
     */
    public function handle(VersionService $versionService): int
    {
        // Clear cache if requested
        if ($this->option('clear-cache')) {
            $this->info('Clearing version cache...');
            $versionService->clearCache();
            $this->newLine();
        }

        // List all versions
        if ($this->option('list')) {
            return $this->listVersions($versionService);
        }

        // Default: Check for updates
        return $this->checkForUpdates($versionService);
    }

    /**
     * Check for updates
     */
    protected function checkForUpdates(VersionService $versionService): int
    {
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║              Checking for Updates...                      ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();

        $updateInfo = $versionService->checkForUpdates();

        if ($updateInfo['update_available']) {
            $this->newLine();
            $this->warn('🎉 NEW VERSION AVAILABLE!');
            $this->newLine();

            $this->table(
                ['', 'Version'],
                [
                    ['Current', $updateInfo['current_version']],
                    ['Latest', $updateInfo['latest_version']],
                ]
            );

            $this->newLine();

            if (isset($updateInfo['changelog_url'])) {
                $this->info("📋 View Changelog:");
                $this->line("   {$updateInfo['changelog_url']}");
                $this->newLine();
            }

            $this->info('To update, run the following commands:');
            $this->line('   git fetch origin');
            $this->line('   git checkout v' . $updateInfo['latest_version']);
            $this->line('   composer install');
            $this->line('   php artisan migrate');
            $this->line('   php artisan app:optimize');
            $this->newLine();

            $this->comment('Or use the automated update command:');
            $this->line('   php artisan app:update');

            return self::SUCCESS;
        }

        $this->info('✓ ' . $updateInfo['message']);
        $this->newLine();

        $this->table(
            ['', 'Version'],
            [
                ['Current', $updateInfo['current_version']],
                ['Latest', $updateInfo['latest_version'] ?? 'Unknown'],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * List all available versions
     */
    protected function listVersions(VersionService $versionService): int
    {
        $this->info('Fetching available versions from GitHub...');
        $this->newLine();

        $versions = $versionService->getAvailableVersions();

        if (empty($versions)) {
            $this->error('Could not fetch versions from GitHub.');
            $this->info('Please check your internet connection or try again later.');
            return self::FAILURE;
        }

        $currentVersion = $versionService->getCurrentVersion();

        $rows = collect($versions)->map(function ($version) use ($currentVersion) {
            $isCurrent = $version['version'] === $currentVersion;
            $status = $isCurrent ? '✓ Current' : '';
            $type = $version['prerelease'] ? 'Pre-release' : 'Stable';

            return [
                $version['version'] . ($isCurrent ? ' ⬅' : ''),
                $version['name'] ?: '-',
                $type,
                $version['published_at'] ? date('Y-m-d', strtotime($version['published_at'])) : '-',
                $status,
            ];
        })->toArray();

        $this->table(
            ['Version', 'Name', 'Type', 'Released', 'Status'],
            $rows
        );

        $this->newLine();
        $this->info('Total versions: ' . count($versions));

        return self::SUCCESS;
    }
}
