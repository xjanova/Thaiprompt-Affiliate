<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use App\Services\VersionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

class UpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update
                            {version? : Specific version to update to (e.g., v1.0.0)}
                            {--force : Skip confirmation prompts}
                            {--no-backup : Skip database backup}
                            {--skip-deps : Skip dependency installation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update application to the latest or specific version';

    protected VersionService $versionService;
    protected LicenseService $licenseService;

    /**
     * Execute the console command.
     */
    public function handle(VersionService $versionService, LicenseService $licenseService): int
    {
        $this->versionService = $versionService;
        $this->licenseService = $licenseService;

        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║              TP-Affiliate Update Manager                  ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Pre-flight checks
        if (!$this->preFlightChecks()) {
            return self::FAILURE;
        }

        // Get target version
        $targetVersion = $this->argument('version');
        if (!$targetVersion) {
            $targetVersion = 'v' . $this->versionService->getLatestVersion();
        }

        // Confirm update
        if (!$this->option('force')) {
            $currentVersion = $this->versionService->getCurrentVersion();

            $this->table(
                ['', 'Version'],
                [
                    ['Current', $currentVersion],
                    ['Target', ltrim($targetVersion, 'v')],
                ]
            );

            if (!$this->confirm('Do you want to continue with the update?', true)) {
                $this->warn('Update cancelled.');
                return self::SUCCESS;
            }
        }

        $this->newLine();

        // Step 1: Enable maintenance mode
        if (!$this->enableMaintenanceMode()) {
            return self::FAILURE;
        }

        // Step 2: Backup database
        if (!$this->option('no-backup')) {
            if (!$this->backupDatabase()) {
                $this->disableMaintenanceMode();
                return self::FAILURE;
            }
        }

        // Step 3: Update code from Git
        if (!$this->updateCode($targetVersion)) {
            $this->disableMaintenanceMode();
            return self::FAILURE;
        }

        // Step 4: Install dependencies
        if (!$this->option('skip-deps')) {
            if (!$this->installDependencies()) {
                $this->disableMaintenanceMode();
                return self::FAILURE;
            }
        }

        // Step 5: Run migrations
        if (!$this->runMigrations()) {
            $this->disableMaintenanceMode();
            return self::FAILURE;
        }

        // Step 6: Optimize application
        if (!$this->optimizeApplication()) {
            $this->disableMaintenanceMode();
            return self::FAILURE;
        }

        // Step 7: Disable maintenance mode
        $this->disableMaintenanceMode();

        // Success message
        $this->newLine();
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║          Update completed successfully! 🎉                ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->info('Application has been updated to version: ' . ltrim($targetVersion, 'v'));
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Pre-flight checks
     */
    protected function preFlightChecks(): bool
    {
        $this->info('Running pre-flight checks...');

        // Check if Git is available
        $result = Process::run('git --version');
        if (!$result->successful()) {
            $this->error('✗ Git is not installed or not available in PATH');
            return false;
        }
        $this->info('✓ Git is available');

        // Check if we're in a Git repository
        $result = Process::run('git rev-parse --git-dir');
        if (!$result->successful()) {
            $this->error('✗ Not a Git repository');
            return false;
        }
        $this->info('✓ Git repository detected');

        // Check for uncommitted changes
        $result = Process::run('git status --porcelain');
        if (!empty(trim($result->output()))) {
            $this->warn('⚠ You have uncommitted changes');

            if (!$this->option('force')) {
                if (!$this->confirm('Continue anyway? (changes will be stashed)', false)) {
                    $this->warn('Update cancelled. Please commit or stash your changes.');
                    return false;
                }

                // Stash changes
                $this->info('Stashing uncommitted changes...');
                Process::run('git stash');
            }
        } else {
            $this->info('✓ Working directory is clean');
        }

        // Check license
        $this->info('Checking license...');
        $licenseStatus = $this->licenseService->status();

        if (!$licenseStatus['is_active']) {
            $this->error('✗ No active license found');
            $this->newLine();
            $this->comment('Updates require an active license.');
            $this->line('Activate your license with: php artisan license:activate {key}');
            return false;
        }

        // Validate with server
        $validation = $this->licenseService->validate();

        if (!$validation['valid']) {
            $this->error('✗ License validation failed');
            $this->newLine();
            $error = $validation['error'] ?? 'unknown_error';
            $message = $validation['message'] ?? 'License is invalid';

            $this->error("Error: {$error}");
            $this->line($message);
            $this->newLine();

            if ($error === 'expired_license') {
                $this->comment('Your license has expired. Renew at: https://xman4289.com/renew');
            } else {
                $this->comment('Contact support: https://xman4289.com/support');
            }

            return false;
        }

        $this->info('✓ License is valid');

        // Warn if expiring soon
        if ($this->licenseService->isExpiringSoon(30)) {
            $this->warn('⚠ Your license is expiring soon. Renew at: https://xman4289.com/renew');
        }

        $this->newLine();

        return true;
    }

    /**
     * Enable maintenance mode
     */
    protected function enableMaintenanceMode(): bool
    {
        $this->info('[1/6] Enabling maintenance mode...');

        try {
            Artisan::call('down', ['--retry' => 60]);
            $this->info('✓ Maintenance mode enabled');
            $this->newLine();
            return true;
        } catch (\Exception $e) {
            $this->error('✗ Failed to enable maintenance mode: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Disable maintenance mode
     */
    protected function disableMaintenanceMode(): void
    {
        $this->info('Disabling maintenance mode...');

        try {
            Artisan::call('up');
            $this->info('✓ Maintenance mode disabled');
        } catch (\Exception $e) {
            $this->error('✗ Failed to disable maintenance mode: ' . $e->getMessage());
        }
    }

    /**
     * Backup database
     */
    protected function backupDatabase(): bool
    {
        $this->info('[2/6] Creating database backup...');

        $backupFile = storage_path('app/backups/database-' . date('Y-m-d-His') . '.sql');
        $backupDir = dirname($backupFile);

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $dbConnection = config('database.default');
        $dbDriver = config("database.connections.{$dbConnection}.driver");

        if ($dbDriver === 'sqlite') {
            $dbPath = config("database.connections.{$dbConnection}.database");
            $result = Process::run("cp \"{$dbPath}\" \"{$backupFile}\"");

            if ($result->successful()) {
                $this->info("✓ Database backed up to: {$backupFile}");
                $this->newLine();
                return true;
            }
        } else {
            $this->warn('⚠ Database backup skipped (only SQLite is supported by this command)');
            $this->info('  Please create a manual backup if needed.');
            $this->newLine();
            return true;
        }

        $this->error('✗ Database backup failed');
        return false;
    }

    /**
     * Update code from Git
     */
    protected function updateCode(string $version): bool
    {
        $this->info("[3/6] Updating code to {$version}...");

        // Fetch latest changes
        $result = Process::run('git fetch origin --tags');
        if (!$result->successful()) {
            $this->error('✗ Failed to fetch from origin');
            return false;
        }

        // Checkout the specified version
        $result = Process::run("git checkout {$version}");
        if (!$result->successful()) {
            $this->error("✗ Failed to checkout {$version}");
            $this->error($result->errorOutput());
            return false;
        }

        $this->info("✓ Code updated to {$version}");
        $this->newLine();

        return true;
    }

    /**
     * Install dependencies
     */
    protected function installDependencies(): bool
    {
        $this->info('[4/6] Installing dependencies...');

        // Composer install
        $this->line('  Installing PHP dependencies...');
        $result = Process::timeout(300)->run('composer install --no-dev --optimize-autoloader');
        if (!$result->successful()) {
            $this->error('✗ Composer install failed');
            $this->error($result->errorOutput());
            return false;
        }
        $this->info('  ✓ PHP dependencies installed');

        // NPM install (if package.json exists)
        if (file_exists(base_path('package.json'))) {
            $this->line('  Installing Node dependencies...');
            $result = Process::timeout(300)->run('npm install');
            if (!$result->successful()) {
                $this->warn('  ⚠ NPM install failed (continuing anyway)');
            } else {
                $this->info('  ✓ Node dependencies installed');
            }
        }

        $this->newLine();

        return true;
    }

    /**
     * Run migrations
     */
    protected function runMigrations(): bool
    {
        $this->info('[5/6] Running database migrations...');

        try {
            Artisan::call('migrate', ['--force' => true]);
            $this->info('✓ Migrations completed');
            $this->newLine();
            return true;
        } catch (\Exception $e) {
            $this->error('✗ Migrations failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Optimize application
     */
    protected function optimizeApplication(): bool
    {
        $this->info('[6/6] Optimizing application...');

        try {
            // Clear caches
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            // Optimize for production
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');

            $this->info('✓ Application optimized');
            $this->newLine();

            return true;
        } catch (\Exception $e) {
            $this->error('✗ Optimization failed: ' . $e->getMessage());
            return false;
        }
    }
}
