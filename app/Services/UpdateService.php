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
    // Maximum retry attempts for network operations
    protected const MAX_RETRIES = 3;

    // Retry delay in seconds (exponential backoff)
    protected const RETRY_DELAY = 2;

    public function __construct()
    {
    }

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
     * Perform update with comprehensive error handling
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

            // Run pre-flight checks (from deploy.sh best practices)
            Log::info('Running pre-flight checks...');
            $preflightResults = $this->runPreflightChecks($systemUpdate);

            if (!$preflightResults['ready']) {
                $errorMessage = 'Pre-flight checks failed: ' . implode(', ', array_column($preflightResults['errors'], 'message'));
                Log::error($errorMessage, $preflightResults);
                throw new \Exception($errorMessage);
            }

            Log::info('Pre-flight checks passed', $preflightResults);

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

            $totalSteps = 7; // Increased to include verification step
            $currentStep = 0;

            // Step 1: Create backup
            $currentStep++;
            $this->updateProgress($log, 'backing_up', 'Creating backup...', $currentStep, $totalSteps);
            $backupPath = $this->createBackup();
            $log->update([
                'backup_path' => $backupPath,
                'backup_size' => File::exists($backupPath) ? File::size($backupPath) : 0,
            ]);

            // Step 2: Download update if needed (with retry logic)
            if ($systemUpdate->download_url) {
                $currentStep++;
                $this->updateProgress($log, 'downloading', 'Downloading update...', $currentStep, $totalSteps);

                try {
                    $downloadPath = $this->downloadUpdate($systemUpdate);
                    $systemUpdate->incrementDownloads();
                    Log::info('Update downloaded successfully', ['path' => $downloadPath]);
                } catch (\Exception $e) {
                    Log::error('Download failed after retries: ' . $e->getMessage());
                    throw $e;
                }
            } else {
                $currentStep++;
            }

            // Step 3: Run migrations
            if ($systemUpdate->requires_migration) {
                $currentStep++;
                $this->updateProgress($log, 'migrating', 'Running database migrations...', $currentStep, $totalSteps);
                $migrationResults = $this->runMigrations();
                $log->update(['migration_results' => $migrationResults]);
            } else {
                $currentStep++;
            }

            // Step 4: Run seeders
            $currentStep++;
            $this->updateProgress($log, 'seeding', 'Running database seeders...', $currentStep, $totalSteps);
            $this->runSeeders();

            // Step 5: Update version
            $currentStep++;
            $this->updateProgress($log, 'updating', 'Updating system files...', $currentStep, $totalSteps);
            $this->updateVersionFile($systemUpdate->version);

            // Step 6: Clear caches
            $currentStep++;
            $this->updateProgress($log, 'finalizing', 'Clearing caches...', $currentStep, $totalSteps);
            $this->clearCaches();

            // Complete
            $log->markAsCompleted('Update to version ' . $systemUpdate->version . ' completed successfully');
            $systemUpdate->incrementInstalls();

            // Verify update success (from deploy.sh post-deployment verification)
            $currentStep++;
            $this->updateProgress($log, 'verifying', 'Verifying update...', $currentStep, $totalSteps);

            $verificationResults = $this->verifyUpdateSuccess($log);

            // Log verification results
            Log::info('Post-update verification completed', $verificationResults);

            // Add verification results to log (if column exists)
            try {
                $log->update([
                    'verification_results' => json_encode($verificationResults),
                ]);
            } catch (\Exception $e) {
                // Ignore if column doesn't exist
                Log::debug('Could not save verification results: ' . $e->getMessage());
            }

            DB::commit();

            return [
                'success' => true,
                'log' => $log,
                'system_update' => $systemUpdate,
                'verification' => $verificationResults,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            // Get error solution (from deploy.sh best practices)
            $errorSolution = $this->getErrorSolution($e);
            $errorType = $this->classifyError($e);

            if (isset($log)) {
                $log->markAsFailed($e->getMessage(), $e->getTraceAsString());

                // Store error details
                try {
                    $log->update([
                        'error_type' => $errorType,
                        'error_solution' => json_encode($errorSolution),
                    ]);
                } catch (\Exception $updateException) {
                    // Ignore if columns don't exist
                    Log::debug('Could not save error details: ' . $updateException->getMessage());
                }
            }

            // Enhanced logging with error classification
            Log::error('Update failed: ' . $e->getMessage(), [
                'exception' => $e,
                'version' => $systemUpdate->version ?? 'unknown',
                'error_type' => $errorType,
                'error_solution' => $errorSolution,
                'retryable' => $this->isRetryableError($e),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_type' => $errorType,
                'error_solution' => $errorSolution,
                'log' => $log ?? null,
                'retryable' => $this->isRetryableError($e),
            ];
        }
    }

    /**
     * Update progress with detailed logging
     */
    protected function updateProgress($log, $status, $message, $currentStep, $totalSteps)
    {
        $percentage = round(($currentStep / $totalSteps) * 100);

        // Format progress message like deploy.sh [1/6], [2/6]
        $progressLabel = "[{$currentStep}/{$totalSteps}]";
        $detailedMessage = "{$progressLabel} {$message}";

        $log->update([
            'status' => $status,
            'message' => $detailedMessage,
            'progress' => $percentage,
        ]);

        // Log to Laravel logs
        Log::info("Update Progress: {$detailedMessage}", [
            'update_log_id' => $log->id,
            'step' => $currentStep,
            'total_steps' => $totalSteps,
            'percentage' => $percentage,
            'status' => $status,
        ]);

        // Broadcast progress update via events (if needed)
        // event(new UpdateProgressEvent($log));
    }

    /**
     * Run database seeders
     */
    protected function runSeeders()
    {
        try {
            Artisan::call('db:seed', ['--force' => true]);

            return [
                'success' => true,
                'output' => Artisan::output(),
            ];
        } catch (\Exception $e) {
            Log::warning('Seeder execution skipped or failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
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
     * Download update (uses retry logic)
     */
    protected function downloadUpdate($systemUpdate)
    {
        $downloadDir = storage_path('updates');
        if (!File::exists($downloadDir)) {
            File::makeDirectory($downloadDir, 0755, true);
        }

        $downloadFile = $downloadDir . "/update_{$systemUpdate->version}.zip";

        // Use downloadWithRetry for better reliability
        if ($this->downloadWithRetry($systemUpdate->download_url, $downloadFile)) {
            return $downloadFile;
        }

        throw new \Exception('Failed to download update after multiple attempts');
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

    /**
     * Classify error type (from deploy.sh best practices)
     */
    protected function classifyError(\Exception $exception): string
    {
        $message = $exception->getMessage();

        // Network/Timeout errors (retryable)
        if (preg_match('/(timeout|timed out|connection timed out|operation timed out)/i', $message)) {
            return 'timeout';
        }

        if (preg_match('/(could not read|failed to connect|unable to access|network error)/i', $message)) {
            return 'network';
        }

        if (preg_match('/(dns|resolution failed|temporary failure)/i', $message)) {
            return 'dns';
        }

        // File system errors
        if (preg_match('/(permission denied|no space left|disk full)/i', $message)) {
            return 'filesystem';
        }

        // Database errors
        if (preg_match('/(duplicate entry|constraint violation|table already exists)/i', $message)) {
            return 'database';
        }

        // Version/Compatibility errors
        if (preg_match('/(php version|extension|requirement)/i', $message)) {
            return 'compatibility';
        }

        // Default: unknown error
        return 'unknown';
    }

    /**
     * Check if error is retryable
     */
    protected function isRetryableError(\Exception $exception): bool
    {
        $errorType = $this->classifyError($exception);

        return in_array($errorType, ['timeout', 'network', 'dns']);
    }

    /**
     * Get user-friendly error message and solution (from deploy.sh best practices)
     */
    protected function getErrorSolution(\Exception $exception): array
    {
        $errorType = $this->classifyError($exception);

        $solutions = [
            'timeout' => [
                'title' => 'การเชื่อมต่อหมดเวลา (Timeout)',
                'message' => 'ระบบใช้เวลานานเกินไปในการเชื่อมต่อ',
                'solutions' => [
                    'ตรวจสอบการเชื่อมต่ออินเทอร์เน็ต',
                    'ลองอัพเดทใหม่อีกครั้งภายหลัง 5-10 นาที',
                    'ตรวจสอบว่า GitHub/Packagist สามารถเข้าถึงได้',
                ],
                'retryable' => true,
            ],
            'network' => [
                'title' => 'ปัญหาการเชื่อมต่อเครือข่าย',
                'message' => 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
                'solutions' => [
                    'ตรวจสอบการเชื่อมต่ออินเทอร์เน็ต',
                    'ตรวจสอบ Firewall settings',
                    'ลองอัพเดทใหม่ภายหลัง',
                ],
                'retryable' => true,
            ],
            'dns' => [
                'title' => 'ปัญหา DNS Resolution',
                'message' => 'ไม่สามารถค้นหา domain name ได้',
                'solutions' => [
                    'ตรวจสอบ DNS settings',
                    'ลอง flush DNS cache',
                    'ลองใช้ DNS อื่น เช่น 8.8.8.8',
                ],
                'retryable' => true,
            ],
            'filesystem' => [
                'title' => 'ปัญหา File System',
                'message' => 'พบปัญหาเกี่ยวกับ permissions หรือพื้นที่เก็บข้อมูล',
                'solutions' => [
                    'ตรวจสอบพื้นที่ว่างในฮาร์ดดิสก์',
                    'ตรวจสอบ permissions ของ storage/ และ bootstrap/cache/',
                    'รัน: chmod -R 775 storage bootstrap/cache',
                ],
                'retryable' => false,
            ],
            'database' => [
                'title' => 'ปัญหา Database',
                'message' => 'พบปัญหาในการทำงานกับฐานข้อมูล',
                'solutions' => [
                    'ตรวจสอบการเชื่อมต่อฐานข้อมูล',
                    'ดู migration status: php artisan migrate:status',
                    'อาจต้อง rollback: php artisan migrate:rollback',
                ],
                'retryable' => false,
            ],
            'compatibility' => [
                'title' => 'ปัญหา Compatibility',
                'message' => 'ระบบไม่ตรงตามความต้องการขั้นต่ำ',
                'solutions' => [
                    'ตรวจสอบ PHP version ว่าตรงตามที่กำหนดหรือไม่',
                    'ตรวจสอบ PHP extensions ที่จำเป็น',
                    'รัน: php artisan app:version --system',
                ],
                'retryable' => false,
            ],
            'unknown' => [
                'title' => 'ข้อผิดพลาดที่ไม่ทราบสาเหตุ',
                'message' => 'พบข้อผิดพลาดที่ไม่คาดคิด',
                'solutions' => [
                    'ตรวจสอบ logs: tail -f storage/logs/laravel.log',
                    'ตรวจสอบรายละเอียดข้อผิดพลาดด้านล่าง',
                    'ติดต่อทีมพัฒนาหากปัญหายังคงอยู่',
                ],
                'retryable' => false,
            ],
        ];

        return $solutions[$errorType] ?? $solutions['unknown'];
    }

    /**
     * Download file with retry logic (from deploy.sh best practices)
     */
    protected function downloadWithRetry(string $url, string $destination, int $maxRetries = self::MAX_RETRIES): bool
    {
        $attempt = 1;
        $delay = self::RETRY_DELAY;

        while ($attempt <= $maxRetries) {
            try {
                Log::info("Downloading (attempt {$attempt}/{$maxRetries}): {$url}");

                $response = Http::timeout(300)->get($url);

                if ($response->successful()) {
                    File::put($destination, $response->body());
                    Log::info("Download successful: {$destination}");
                    return true;
                }

                throw new \Exception("HTTP {$response->status()}: Download failed");
            } catch (\Exception $e) {
                Log::warning("Download attempt {$attempt} failed: " . $e->getMessage());

                // Check if error is retryable
                if (!$this->isRetryableError($e)) {
                    Log::error("Non-retryable error encountered");
                    throw $e;
                }

                // Last attempt failed
                if ($attempt >= $maxRetries) {
                    Log::error("Download failed after {$maxRetries} attempts");
                    throw $e;
                }

                // Wait before retry (exponential backoff)
                Log::info("Waiting {$delay}s before retry...");
                sleep($delay);
                $delay *= 2; // Exponential backoff

                $attempt++;
            }
        }

        return false;
    }

    /**
     * Verify update success (from deploy.sh post-deployment verification)
     */
    public function verifyUpdateSuccess(UpdateLog $log): array
    {
        $results = [
            'success' => true,
            'checks' => [],
            'warnings' => [],
            'errors' => [],
        ];

        // 1. Check version file updated
        try {
            $currentVersion = config('version.current');
            if ($currentVersion === $log->to_version) {
                $results['checks'][] = [
                    'name' => 'Version File',
                    'status' => 'passed',
                    'message' => "Version updated to {$currentVersion}",
                ];
            } else {
                $results['success'] = false;
                $results['errors'][] = [
                    'name' => 'Version File',
                    'status' => 'failed',
                    'message' => "Version mismatch: expected {$log->to_version}, got {$currentVersion}",
                ];
            }
        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = [
                'name' => 'Version File',
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }

        // 2. Check database connection
        try {
            DB::connection()->getPdo();
            $results['checks'][] = [
                'name' => 'Database Connection',
                'status' => 'passed',
                'message' => 'Database is accessible',
            ];
        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = [
                'name' => 'Database Connection',
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];
        }

        // 3. Check migrations status
        try {
            Artisan::call('migrate:status');
            $output = Artisan::output();

            // Check for pending migrations
            if (strpos($output, 'Pending') === false) {
                $results['checks'][] = [
                    'name' => 'Database Migrations',
                    'status' => 'passed',
                    'message' => 'All migrations applied',
                ];
            } else {
                $results['warnings'][] = [
                    'name' => 'Database Migrations',
                    'status' => 'warning',
                    'message' => 'Some migrations are pending',
                ];
            }
        } catch (\Exception $e) {
            $results['warnings'][] = [
                'name' => 'Database Migrations',
                'status' => 'warning',
                'message' => 'Could not verify migrations: ' . $e->getMessage(),
            ];
        }

        // 4. Check storage permissions
        try {
            $storagePath = storage_path('logs');
            if (is_writable($storagePath)) {
                $results['checks'][] = [
                    'name' => 'Storage Permissions',
                    'status' => 'passed',
                    'message' => 'Storage directory is writable',
                ];
            } else {
                $results['warnings'][] = [
                    'name' => 'Storage Permissions',
                    'status' => 'warning',
                    'message' => 'Storage directory may not be writable',
                ];
            }
        } catch (\Exception $e) {
            $results['warnings'][] = [
                'name' => 'Storage Permissions',
                'status' => 'warning',
                'message' => $e->getMessage(),
            ];
        }

        // 5. Check cache status
        try {
            Cache::get('test_key', 'default');
            $results['checks'][] = [
                'name' => 'Cache System',
                'status' => 'passed',
                'message' => 'Cache is functional',
            ];
        } catch (\Exception $e) {
            $results['warnings'][] = [
                'name' => 'Cache System',
                'status' => 'warning',
                'message' => 'Cache test failed: ' . $e->getMessage(),
            ];
        }

        return $results;
    }

    /**
     * Pre-flight checks before update (from deploy.sh best practices)
     */
    public function runPreflightChecks(SystemUpdate $systemUpdate): array
    {
        $results = [
            'ready' => true,
            'checks' => [],
            'warnings' => [],
            'errors' => [],
        ];

        // 1. Check system requirements
        try {
            if (!$systemUpdate->meetsRequirements()) {
                $results['ready'] = false;
                $results['errors'][] = [
                    'check' => 'System Requirements',
                    'message' => 'System does not meet minimum requirements for this update',
                ];
            } else {
                $results['checks'][] = [
                    'check' => 'System Requirements',
                    'status' => 'passed',
                ];
            }
        } catch (\Exception $e) {
            $results['ready'] = false;
            $results['errors'][] = [
                'check' => 'System Requirements',
                'message' => $e->getMessage(),
            ];
        }

        // 2. Check disk space
        try {
            $freeSpace = disk_free_space(base_path());
            $requiredSpace = 500 * 1024 * 1024; // 500 MB

            if ($freeSpace < $requiredSpace) {
                $results['ready'] = false;
                $results['errors'][] = [
                    'check' => 'Disk Space',
                    'message' => 'Insufficient disk space. Required: 500MB, Available: ' . round($freeSpace / 1024 / 1024) . 'MB',
                ];
            } else {
                $results['checks'][] = [
                    'check' => 'Disk Space',
                    'status' => 'passed',
                ];
            }
        } catch (\Exception $e) {
            $results['warnings'][] = [
                'check' => 'Disk Space',
                'message' => 'Could not check disk space: ' . $e->getMessage(),
            ];
        }

        // 3. Check database connection
        try {
            DB::connection()->getPdo();
            $results['checks'][] = [
                'check' => 'Database Connection',
                'status' => 'passed',
            ];
        } catch (\Exception $e) {
            $results['ready'] = false;
            $results['errors'][] = [
                'check' => 'Database Connection',
                'message' => 'Cannot connect to database: ' . $e->getMessage(),
            ];
        }

        // 4. Check write permissions
        $paths = [
            storage_path(),
            base_path('bootstrap/cache'),
            base_path('VERSION'),
        ];

        foreach ($paths as $path) {
            if (!is_writable($path)) {
                $results['ready'] = false;
                $results['errors'][] = [
                    'check' => 'Write Permissions',
                    'message' => "Path is not writable: {$path}",
                ];
            }
        }

        if (empty($results['errors']) || !isset($results['errors'][0]['check']) || $results['errors'][0]['check'] !== 'Write Permissions') {
            $results['checks'][] = [
                'check' => 'Write Permissions',
                'status' => 'passed',
            ];
        }

        // 5. Check backup directory exists and is writable
        $backupDir = storage_path('backups');
        if (!File::exists($backupDir)) {
            try {
                File::makeDirectory($backupDir, 0755, true);
                $results['checks'][] = [
                    'check' => 'Backup Directory',
                    'status' => 'created',
                ];
            } catch (\Exception $e) {
                $results['ready'] = false;
                $results['errors'][] = [
                    'check' => 'Backup Directory',
                    'message' => 'Cannot create backup directory: ' . $e->getMessage(),
                ];
            }
        } elseif (!is_writable($backupDir)) {
            $results['ready'] = false;
            $results['errors'][] = [
                'check' => 'Backup Directory',
                'message' => 'Backup directory is not writable',
            ];
        } else {
            $results['checks'][] = [
                'check' => 'Backup Directory',
                'status' => 'passed',
            ];
        }

        return $results;
    }
}
