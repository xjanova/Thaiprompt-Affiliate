<?php

namespace App\Console\Commands;

use App\Services\VersionService;
use Illuminate\Console\Command;

class VersionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:version
                            {--check : Check for updates}
                            {--system : Show system requirements}
                            {--changelog : Show changelog}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display application version information';

    /**
     * Execute the console command.
     */
    public function handle(VersionService $versionService): int
    {
        // Show changelog
        if ($this->option('changelog')) {
            return $this->showChangelog($versionService);
        }

        // Show system requirements
        if ($this->option('system')) {
            return $this->showSystemRequirements($versionService);
        }

        // Check for updates
        if ($this->option('check')) {
            return $this->checkForUpdates($versionService);
        }

        // Default: Show version info
        return $this->showVersionInfo($versionService);
    }

    /**
     * Show version information
     */
    protected function showVersionInfo(VersionService $versionService): int
    {
        $info = $versionService->getVersionInfo();

        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║         TP-Affiliate - Version Information                ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->table(
            ['Property', 'Value'],
            [
                ['Version', $info['version']],
                ['Codename', $info['name']],
                ['Released', $info['released_at']],
                ['Laravel', $info['laravel_version']],
                ['PHP', $info['php_version']],
                ['Min PHP', $info['min_php_version']],
            ]
        );

        $this->newLine();
        $this->info('Run with --check to check for updates');
        $this->info('Run with --system to see system requirements');
        $this->info('Run with --changelog to see changelog');

        return self::SUCCESS;
    }

    /**
     * Check for updates
     */
    protected function checkForUpdates(VersionService $versionService): int
    {
        $this->info('Checking for updates...');
        $this->newLine();

        $updateInfo = $versionService->checkForUpdates();

        if ($updateInfo['update_available']) {
            $this->warn('🎉 ' . $updateInfo['message']);
            $this->newLine();
            $this->info("Current: {$updateInfo['current_version']}");
            $this->info("Latest: {$updateInfo['latest_version']}");
            $this->newLine();

            if (isset($updateInfo['changelog_url'])) {
                $this->info("📋 Changelog: {$updateInfo['changelog_url']}");
            }

            $this->newLine();
            $this->info('To update, run: php artisan app:update');

            return self::SUCCESS;
        }

        $this->info('✓ ' . $updateInfo['message']);

        return self::SUCCESS;
    }

    /**
     * Show system requirements
     */
    protected function showSystemRequirements(VersionService $versionService): int
    {
        $requirements = $versionService->getSystemRequirements();

        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║            System Requirements Check                      ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();

        $rows = [];

        foreach ($requirements as $component => $details) {
            $status = $details['status'] === 'ok' ? '✓' : '✗';
            $statusColor = $details['status'] === 'ok' ? 'green' : 'red';

            if (isset($details['current']) && isset($details['required'])) {
                $rows[] = [
                    ucfirst($component),
                    $details['current'],
                    $details['required'],
                    "<fg={$statusColor}>{$status}</>",
                ];
            } else {
                $rows[] = [
                    ucfirst($component),
                    $details['version'] ?? 'N/A',
                    '-',
                    "<fg={$statusColor}>{$status}</>",
                ];
            }
        }

        $this->table(
            ['Component', 'Current', 'Required', 'Status'],
            $rows
        );

        $allOk = collect($requirements)->every(fn($req) => $req['status'] === 'ok');

        if ($allOk) {
            $this->newLine();
            $this->info('✓ All system requirements are met!');
        } else {
            $this->newLine();
            $this->error('✗ Some system requirements are not met. Please update your system.');
        }

        return $allOk ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Show changelog
     */
    protected function showChangelog(VersionService $versionService): int
    {
        $changelog = $versionService->getChangelog();

        if (!$changelog) {
            $this->error('Changelog not found!');
            return self::FAILURE;
        }

        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║                      CHANGELOG                            ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Show only the latest version changelog
        $currentVersion = $versionService->getCurrentVersion();
        $versionChangelog = $versionService->getChangelogForVersion($currentVersion);

        if ($versionChangelog) {
            $this->line($versionChangelog);
        } else {
            $this->warn('No changelog found for current version.');
            $this->newLine();
            $this->info('Full changelog:');
            $this->line($changelog);
        }

        return self::SUCCESS;
    }
}
