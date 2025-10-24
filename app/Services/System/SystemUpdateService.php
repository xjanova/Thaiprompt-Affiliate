<?php

namespace App\Services\System;

use App\Models\SystemUpdate;
use App\Models\MigrationLog;
use App\Models\DatabaseBackup;
use App\Models\SystemMaintenanceMode;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Exception;

class SystemUpdateService
{
    protected DatabaseBackupService $backupService;

    public function __construct(DatabaseBackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Create a new system update
     */
    public function createUpdate(
        string $versionFrom,
        string $versionTo,
        string $description,
        int $initiatedBy,
        array $changes = [],
        string $updateType = 'minor',
        bool $requiresDowntime = false
    ): SystemUpdate {
        return SystemUpdate::create([
            'version_from' => $versionFrom,
            'version_to' => $versionTo,
            'update_type' => $updateType,
            'description' => $description,
            'changes' => $changes,
            'initiated_by' => $initiatedBy,
            'status' => 'pending',
            'requires_downtime' => $requiresDowntime,
        ]);
    }

    /**
     * Execute system update
     */
    public function executeUpdate(SystemUpdate $update): bool
    {
        try {
            // Start the update
            $update->start();

            // Create backup before update
            $backup = $this->createPreUpdateBackup($update);
            $update->update(['database_backup_id' => $backup->id]);

            // Enable maintenance mode if required
            if ($update->requires_downtime) {
                $this->enableMaintenanceMode($update);
                $update->startDowntime();
            }

            // Get pending migrations
            $pendingMigrations = $this->getPendingMigrations();
            $update->update([
                'total_steps' => count($pendingMigrations),
                'migration_files' => $pendingMigrations,
            ]);

            // Run migrations
            $this->runMigrations($update, $pendingMigrations);

            // Disable maintenance mode if was enabled
            if ($update->requires_downtime) {
                $this->disableMaintenanceMode();
                $update->endDowntime();
            }

            // Complete the update
            $update->complete();

            return true;
        } catch (Exception $e) {
            $update->fail($e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            // Disable maintenance mode on failure
            if ($update->requires_downtime) {
                $this->disableMaintenanceMode();
            }

            throw $e;
        }
    }

    /**
     * Run migrations
     */
    protected function runMigrations(SystemUpdate $update, array $migrations): void
    {
        $batch = $this->getNextBatchNumber();

        foreach ($migrations as $index => $migration) {
            $stepNumber = $index + 1;

            $migrationLog = MigrationLog::create([
                'system_update_id' => $update->id,
                'migration_file' => $migration,
                'migration_name' => $this->getMigrationName($migration),
                'batch' => $batch,
                'status' => 'pending',
                'step_number' => $stepNumber,
                'description' => "Running migration: {$migration}",
            ]);

            try {
                $migrationLog->start();

                // Run the migration
                $startTime = microtime(true);
                Artisan::call('migrate', [
                    '--path' => 'database/migrations/' . $migration,
                    '--force' => true,
                ]);
                $endTime = microtime(true);

                $output = Artisan::output();

                $migrationLog->complete(null, $output);
                $update->incrementCompleted();
            } catch (Exception $e) {
                $migrationLog->fail($e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
                $update->incrementFailed();

                // Optionally stop on first failure
                throw new Exception("Migration failed: {$migration}. Error: " . $e->getMessage());
            }
        }
    }

    /**
     * Rollback system update
     */
    public function rollbackUpdate(SystemUpdate $update, string $reason, int $userId): bool
    {
        try {
            // Get the backup
            if (!$update->backup) {
                throw new Exception('No backup found for this update');
            }

            // Enable maintenance mode
            $this->enableMaintenanceMode($update);

            // Restore from backup
            $this->backupService->restoreBackup($update->backup);

            // Mark as rolled back
            $update->rollback($reason, $userId);

            // Disable maintenance mode
            $this->disableMaintenanceMode();

            return true;
        } catch (Exception $e) {
            $this->disableMaintenanceMode();
            throw $e;
        }
    }

    /**
     * Create pre-update backup
     */
    protected function createPreUpdateBackup(SystemUpdate $update): DatabaseBackup
    {
        return $this->backupService->createBackup(
            $update->initiated_by,
            'pre_migration',
            "Backup before system update: {$update->update_number}"
        );
    }

    /**
     * Enable maintenance mode
     */
    protected function enableMaintenanceMode(SystemUpdate $update): void
    {
        $maintenance = SystemMaintenanceMode::create([
            'title' => 'System Update in Progress',
            'message' => 'The system is currently being updated. Please check back in a few minutes.',
            'system_update_id' => $update->id,
            'is_active' => false,
            'scheduled_start' => now(),
            'created_by' => $update->initiated_by,
        ]);

        $maintenance->activate();

        // Put Laravel in maintenance mode
        Artisan::call('down', [
            '--retry' => 60,
        ]);
    }

    /**
     * Disable maintenance mode
     */
    protected function disableMaintenanceMode(): void
    {
        $maintenance = SystemMaintenanceMode::getActive();
        if ($maintenance) {
            $maintenance->deactivate();
        }

        // Bring Laravel back up
        Artisan::call('up');
    }

    /**
     * Get pending migrations
     */
    protected function getPendingMigrations(): array
    {
        $ran = $this->getRanMigrations();
        $migrations = $this->getAllMigrationFiles();

        return array_values(array_diff($migrations, $ran));
    }

    /**
     * Get ran migrations
     */
    protected function getRanMigrations(): array
    {
        if (!Schema::hasTable('migrations')) {
            return [];
        }

        return DB::table('migrations')
            ->orderBy('batch')
            ->pluck('migration')
            ->toArray();
    }

    /**
     * Get all migration files
     */
    protected function getAllMigrationFiles(): array
    {
        $path = database_path('migrations');
        $files = File::files($path);

        return collect($files)
            ->map(function ($file) {
                return $file->getFilename();
            })
            ->filter(function ($file) {
                return preg_match('/\.php$/', $file);
            })
            ->values()
            ->toArray();
    }

    /**
     * Get next batch number
     */
    protected function getNextBatchNumber(): int
    {
        if (!Schema::hasTable('migrations')) {
            return 1;
        }

        return DB::table('migrations')->max('batch') + 1;
    }

    /**
     * Get migration name from file
     */
    protected function getMigrationName(string $file): string
    {
        // Remove timestamp and .php extension
        $name = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $file);
        $name = str_replace('.php', '', $name);
        $name = str_replace('_', ' ', $name);

        return ucwords($name);
    }

    /**
     * Get system version
     */
    public function getCurrentVersion(): string
    {
        // Get from config or latest update
        $latestUpdate = SystemUpdate::completed()
            ->latest('completed_at')
            ->first();

        return $latestUpdate ? $latestUpdate->version_to : '1.0.0';
    }

    /**
     * Check for available updates
     */
    public function checkForUpdates(): array
    {
        $currentVersion = $this->getCurrentVersion();
        $pendingMigrations = $this->getPendingMigrations();

        return [
            'current_version' => $currentVersion,
            'has_updates' => count($pendingMigrations) > 0,
            'pending_migrations' => count($pendingMigrations),
            'migrations' => $pendingMigrations,
        ];
    }

    /**
     * Get update history
     */
    public function getUpdateHistory(int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return SystemUpdate::with(['initiator', 'backup', 'migrationLogs'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get update statistics
     */
    public function getUpdateStatistics(): array
    {
        return [
            'total_updates' => SystemUpdate::count(),
            'completed_updates' => SystemUpdate::completed()->count(),
            'failed_updates' => SystemUpdate::failed()->count(),
            'running_updates' => SystemUpdate::running()->count(),
            'total_migrations' => MigrationLog::count(),
            'failed_migrations' => MigrationLog::failed()->count(),
            'average_duration' => SystemUpdate::completed()
                ->avg('duration_seconds'),
        ];
    }

    /**
     * Optimize database
     */
    public function optimizeDatabase(): array
    {
        $results = [];

        try {
            // Optimize tables
            Artisan::call('optimize');
            $results['optimize'] = 'Success';

            // Clear caches
            Artisan::call('cache:clear');
            $results['cache_clear'] = 'Success';

            Artisan::call('config:clear');
            $results['config_clear'] = 'Success';

            Artisan::call('view:clear');
            $results['view_clear'] = 'Success';

            Artisan::call('route:clear');
            $results['route_clear'] = 'Success';

            return $results;
        } catch (Exception $e) {
            $results['error'] = $e->getMessage();
            return $results;
        }
    }
}
