<?php

namespace App\Services\System;

use App\Models\DatabaseBackup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Exception;

class DatabaseBackupService
{
    protected string $backupPath = 'backups/database';

    /**
     * Create a database backup
     */
    public function createBackup(
        ?int $createdBy = null,
        string $triggerType = 'manual',
        ?string $notes = null
    ): DatabaseBackup {
        $backup = DatabaseBackup::create([
            'backup_name' => $this->generateBackupName(),
            'backup_path' => '',
            'backup_type' => 'full',
            'compression' => 'gzip',
            'status' => 'processing',
            'created_by' => $createdBy,
            'trigger_type' => $triggerType,
            'notes' => $notes,
            'started_at' => now(),
        ]);

        try {
            // Create backup directory if not exists
            $backupDir = storage_path('app/' . $this->backupPath);
            if (!File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            // Generate backup file path
            $fileName = $backup->backup_name . '.sql.gz';
            $filePath = $backupDir . '/' . $fileName;

            // Get database configuration
            $dbConfig = config('database.connections.' . config('database.default'));

            // Build mysqldump command
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s --port=%s %s | gzip > %s',
                escapeshellarg($dbConfig['username']),
                escapeshellarg($dbConfig['password']),
                escapeshellarg($dbConfig['host']),
                escapeshellarg($dbConfig['port'] ?? 3306),
                escapeshellarg($dbConfig['database']),
                escapeshellarg($filePath)
            );

            // Execute backup
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new Exception('Backup command failed with code: ' . $returnVar);
            }

            // Verify file exists and get size
            if (!File::exists($filePath)) {
                throw new Exception('Backup file was not created');
            }

            $fileSize = File::size($filePath);

            // Get database info
            $databaseInfo = $this->getDatabaseInfo();

            // Update backup record
            $backup->update([
                'backup_path' => $this->backupPath . '/' . $fileName,
                'database_info' => $databaseInfo,
            ]);

            $backup->markAsCompleted($fileSize);
            $backup->verify();

            return $backup;
        } catch (Exception $e) {
            $backup->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Restore database from backup
     */
    public function restoreBackup(DatabaseBackup $backup): bool
    {
        if (!$backup->isCompleted()) {
            throw new Exception('Cannot restore from incomplete backup');
        }

        if (!$backup->fileExists()) {
            throw new Exception('Backup file not found');
        }

        try {
            $filePath = storage_path('app/' . $backup->backup_path);

            // Get database configuration
            $dbConfig = config('database.connections.' . config('database.default'));

            // Build mysql restore command
            $command = sprintf(
                'gunzip < %s | mysql --user=%s --password=%s --host=%s --port=%s %s',
                escapeshellarg($filePath),
                escapeshellarg($dbConfig['username']),
                escapeshellarg($dbConfig['password']),
                escapeshellarg($dbConfig['host']),
                escapeshellarg($dbConfig['port'] ?? 3306),
                escapeshellarg($dbConfig['database'])
            );

            // Execute restore
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new Exception('Restore command failed with code: ' . $returnVar);
            }

            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Download backup file
     */
    public function downloadBackup(DatabaseBackup $backup): string
    {
        if (!$backup->fileExists()) {
            throw new Exception('Backup file not found');
        }

        return storage_path('app/' . $backup->backup_path);
    }

    /**
     * Delete backup
     */
    public function deleteBackup(DatabaseBackup $backup): bool
    {
        $backup->deleteFile();
        return $backup->delete();
    }

    /**
     * Generate backup name
     */
    protected function generateBackupName(): string
    {
        return 'backup_' . date('Y-m-d_His') . '_' . substr(md5(time()), 0, 8);
    }

    /**
     * Get database information
     */
    protected function getDatabaseInfo(): array
    {
        $dbName = DB::connection()->getDatabaseName();

        // Get table information
        $tables = DB::select('SHOW TABLES');
        $tableCount = count($tables);

        // Get database size
        $sizeQuery = DB::select(
            "SELECT SUM(data_length + index_length) as size
             FROM information_schema.TABLES
             WHERE table_schema = ?",
            [$dbName]
        );

        $dbSize = $sizeQuery[0]->size ?? 0;

        return [
            'database_name' => $dbName,
            'table_count' => $tableCount,
            'database_size' => $dbSize,
            'database_size_human' => $this->formatBytes($dbSize),
        ];
    }

    /**
     * Format bytes to human readable size
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Clean old backups
     */
    public function cleanOldBackups(int $keepDays = 30): int
    {
        $deleted = DatabaseBackup::deleteOldBackups();
        return $deleted;
    }

    /**
     * List all backups
     */
    public function listBackups(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return DatabaseBackup::orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get latest backup
     */
    public function getLatestBackup(): ?DatabaseBackup
    {
        return DatabaseBackup::completed()
            ->latest('created_at')
            ->first();
    }

    /**
     * Verify all backups
     */
    public function verifyAllBackups(): array
    {
        $backups = DatabaseBackup::completed()->get();
        $verified = 0;
        $failed = 0;

        foreach ($backups as $backup) {
            if ($backup->fileExists()) {
                $backup->verify();
                $verified++;
            } else {
                $failed++;
            }
        }

        return [
            'total' => $backups->count(),
            'verified' => $verified,
            'failed' => $failed,
        ];
    }
}
