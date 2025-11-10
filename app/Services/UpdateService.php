<?php

namespace App\Services;

use App\Models\SystemUpdate;
use App\Models\UpdateLog;
use App\Models\UpdateNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class UpdateService
{
    /**
     * Check for available updates
     */
    public function checkForUpdates($force = false)
    {
        $cacheKey = 'available_updates';

        if (!$force && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $currentVersion = config('version.current');
            $repositoryConfig = config('version.repository');

            // Fetch from GitHub API
            $response = Http::get($repositoryConfig['api_url'] . '/releases');

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch updates from repository');
            }

            $releases = $response->json();
            $availableUpdates = [];

            foreach ($releases as $release) {
                // Skip draft releases (not approved for release yet)
                if ($release['draft'] ?? false) {
                    continue;
                }

                // Skip pre-releases (beta, alpha, etc.) unless explicitly enabled
                if (($release['prerelease'] ?? false) && !config('version.update.allow_prerelease', false)) {
                    continue;
                }

                $version = ltrim($release['tag_name'], 'v');

                // Skip if older than current version
                if (version_compare($version, $currentVersion, '<=')) {
                    continue;
                }

                // Check if already in database
                $systemUpdate = SystemUpdate::firstOrCreate(
                    ['version' => $version],
                    [
                        'version_name' => $release['name'] ?? null,
                        'description' => $release['body'] ?? null,
                        'changelog' => $release['body'] ?? null,
                        'type' => $this->determineUpdateType($currentVersion, $version),
                        'is_pre_release' => $release['prerelease'] ?? false,
                        'is_stable' => !($release['prerelease'] ?? false),
                        'released_at' => $release['published_at'] ?? now(),
                        'download_url' => $release['zipball_url'] ?? null,
                        'repository_url' => $release['html_url'] ?? null,
                    ]
                );

                $availableUpdates[] = $systemUpdate;
            }

            // Cache for 1 hour
            Cache::put($cacheKey, $availableUpdates, 3600);

            return $availableUpdates;
        } catch (\Exception $e) {
            Log::error('Failed to check for updates: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get latest available update
     */
    public function getLatestUpdate()
    {
        $updates = $this->checkForUpdates();

        if (empty($updates)) {
            return null;
        }

        return collect($updates)->sortByDesc('version')->first();
    }

    /**
     * Perform update
     */
    public function performUpdate($versionOrId, $userId = null, $isAutoUpdate = false)
    {
        DB::beginTransaction();

        try {
            // Get system update
            if (is_numeric($versionOrId)) {
                $systemUpdate = SystemUpdate::findOrFail($versionOrId);
            } else {
                $systemUpdate = SystemUpdate::where('version', $versionOrId)->firstOrFail();
            }

            // Check requirements
            if (!$systemUpdate->meetsRequirements()) {
                throw new \Exception('System does not meet update requirements');
            }

            // Create update log
            $log = UpdateLog::create([
                'system_update_id' => $systemUpdate->id,
                'from_version' => config('version.current'),
                'to_version' => $systemUpdate->version,
                'status' => 'pending',
                'started_at' => now(),
                'initiated_by' => $userId,
                'is_auto_update' => $isAutoUpdate,
            ]);

            // Step 1: Create backup
            $log->update(['status' => 'backing_up', 'message' => 'Creating backup...']);
            $backupPath = $this->createBackup();
            $log->update([
                'backup_path' => $backupPath,
                'backup_size' => File::exists($backupPath) ? File::size($backupPath) : 0,
            ]);

            // Step 2: Download update if needed
            if ($systemUpdate->download_url) {
                $log->update(['status' => 'downloading', 'message' => 'Downloading update...']);
                $downloadPath = $this->downloadUpdate($systemUpdate);
                $systemUpdate->incrementDownloads();
            }

            // Step 3: Run migrations
            if ($systemUpdate->requires_migration) {
                $log->update(['status' => 'migrating', 'message' => 'Running database migrations...']);
                $migrationResults = $this->runMigrations();
                $log->update(['migration_results' => $migrationResults]);
            }

            // Step 4: Update version
            $log->update(['status' => 'updating', 'message' => 'Updating system files...']);
            $this->updateVersionFile($systemUpdate->version);

            // Step 5: Clear caches
            $this->clearCaches();

            // Complete
            $log->markAsCompleted('Update to version ' . $systemUpdate->version . ' completed successfully');
            $systemUpdate->incrementInstalls();

            DB::commit();

            return [
                'success' => true,
                'log' => $log,
                'system_update' => $systemUpdate,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($log)) {
                $log->markAsFailed($e->getMessage(), $e->getTraceAsString());
            }

            Log::error('Update failed: ' . $e->getMessage(), [
                'exception' => $e,
                'version' => $systemUpdate->version ?? 'unknown',
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'log' => $log ?? null,
            ];
        }
    }

    /**
     * Create system backup
     */
    protected function createBackup()
    {
        $backupDir = storage_path('backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_His');
        $backupFile = $backupDir . "/backup_{$timestamp}.zip";

        // Create database backup
        $dbBackupFile = $backupDir . "/database_{$timestamp}.sql";
        $this->backupDatabase($dbBackupFile);

        // Create zip of important files
        $zip = new ZipArchive();
        if ($zip->open($backupFile, ZipArchive::CREATE) === TRUE) {
            // Add database backup
            $zip->addFile($dbBackupFile, 'database.sql');

            // Add .env file
            if (File::exists(base_path('.env'))) {
                $zip->addFile(base_path('.env'), '.env');
            }

            // Add storage files (selective)
            $this->addDirectoryToZip($zip, storage_path('app'), 'storage/app');

            $zip->close();

            // Remove temporary database backup
            File::delete($dbBackupFile);
        }

        return $backupFile;
    }

    /**
     * Backup database
     */
    protected function backupDatabase($outputFile)
    {
        try {
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');

            $command = sprintf(
                'mysqldump -h%s -u%s -p%s %s > %s',
                escapeshellarg($host),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($outputFile)
            );

            exec($command);

            return true;
        } catch (\Exception $e) {
            Log::error('Database backup failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Add directory to zip
     */
    protected function addDirectoryToZip($zip, $directory, $zipPath)
    {
        if (!File::exists($directory)) {
            return;
        }

        $files = File::allFiles($directory);

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = $zipPath . '/' . $file->getRelativePathname();

            $zip->addFile($filePath, $relativePath);
        }
    }

    /**
     * Download update
     */
    protected function downloadUpdate($systemUpdate)
    {
        $downloadDir = storage_path('updates');
        if (!File::exists($downloadDir)) {
            File::makeDirectory($downloadDir, 0755, true);
        }

        $downloadFile = $downloadDir . "/update_{$systemUpdate->version}.zip";

        $response = Http::timeout(300)->get($systemUpdate->download_url);

        if (!$response->successful()) {
            throw new \Exception('Failed to download update');
        }

        File::put($downloadFile, $response->body());

        return $downloadFile;
    }

    /**
     * Run migrations
     */
    protected function runMigrations()
    {
        try {
            Artisan::call('migrate', ['--force' => true]);

            return [
                'success' => true,
                'output' => Artisan::output(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update version file
     */
    protected function updateVersionFile($newVersion)
    {
        // Update VERSION file
        $versionFile = base_path('VERSION');
        File::put($versionFile, $newVersion);

        // Update package.json
        $packageJson = base_path('package.json');
        if (File::exists($packageJson)) {
            $package = json_decode(File::get($packageJson), true);
            $package['version'] = $newVersion;
            File::put($packageJson, json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        return true;
    }

    /**
     * Clear all caches
     */
    protected function clearCaches()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        return true;
    }

    /**
     * Rollback to previous version
     */
    public function rollback($logId)
    {
        $log = UpdateLog::findOrFail($logId);

        if (!$log->backup_path || !File::exists($log->backup_path)) {
            throw new \Exception('Backup file not found');
        }

        // Extract backup
        $zip = new ZipArchive();
        if ($zip->open($log->backup_path) === TRUE) {
            // Restore files
            $zip->extractTo(base_path());
            $zip->close();

            // Update version
            $this->updateVersionFile($log->from_version);

            // Update log
            $log->update(['status' => 'rolled_back', 'message' => 'Rolled back to version ' . $log->from_version]);

            return true;
        }

        throw new \Exception('Failed to extract backup file');
    }

    /**
     * Determine update type based on version numbers
     */
    protected function determineUpdateType($fromVersion, $toVersion)
    {
        $from = explode('.', $fromVersion);
        $to = explode('.', $toVersion);

        if ($to[0] > $from[0]) {
            return 'major';
        } elseif ($to[1] > $from[1]) {
            return 'minor';
        } else {
            return 'patch';
        }
    }

    /**
     * Create update notification for admins
     */
    public function notifyAdmins($systemUpdateId, $type = 'new_version')
    {
        // Get all admin users
        $admins = \App\Models\User::where('is_super_admin', true)->get();

        foreach ($admins as $admin) {
            UpdateNotification::create([
                'system_update_id' => $systemUpdateId,
                'user_id' => $admin->id,
                'type' => $type,
            ]);
        }
    }

    /**
     * Get update history
     */
    public function getUpdateHistory($limit = 10)
    {
        return UpdateLog::with(['systemUpdate', 'initiator'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
