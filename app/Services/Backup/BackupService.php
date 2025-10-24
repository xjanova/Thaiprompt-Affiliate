<?php

namespace App\Services\Backup;

use App\Models\Backup;
use App\Models\SystemInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Exception;

class BackupService
{
    protected string $backupPath;

    public function __construct()
    {
        $this->backupPath = storage_path('app/backups');

        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }

    /**
     * Create full backup (database + files)
     */
    public function createFullBackup(?int $userId = null): ?Backup
    {
        $backup = Backup::create([
            'name' => 'full_backup_' . now()->format('Y-m-d_H-i-s'),
            'type' => 'full',
            'file_path' => '',
            'version' => SystemInfo::getCurrentVersion(),
            'status' => 'pending',
            'started_at' => now(),
            'created_by' => $userId,
        ]);

        try {
            $zipFile = $this->backupPath . '/' . $backup->name . '.zip';
            $zip = new ZipArchive();

            if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
                throw new Exception('Could not create zip file');
            }

            // Backup database
            $dbBackupFile = $this->backupDatabase();
            $zip->addFile($dbBackupFile, 'database/' . basename($dbBackupFile));

            // Backup important files
            $this->addDirectoryToZip($zip, storage_path('app/public'), 'storage/public');
            $this->addDirectoryToZip($zip, base_path('public/uploads'), 'public/uploads');

            // Add .env file
            if (File::exists(base_path('.env'))) {
                $zip->addFile(base_path('.env'), '.env');
            }

            $zip->close();

            // Update backup record
            $backup->update([
                'file_path' => $zipFile,
                'file_size' => File::size($zipFile),
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Clean up temporary database backup
            if (File::exists($dbBackupFile)) {
                File::delete($dbBackupFile);
            }

            return $backup->fresh();
        } catch (Exception $e) {
            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            return null;
        }
    }

    /**
     * Create database backup only
     */
    public function createDatabaseBackup(?int $userId = null): ?Backup
    {
        $backup = Backup::create([
            'name' => 'db_backup_' . now()->format('Y-m-d_H-i-s'),
            'type' => 'database',
            'file_path' => '',
            'version' => SystemInfo::getCurrentVersion(),
            'status' => 'pending',
            'started_at' => now(),
            'created_by' => $userId,
        ]);

        try {
            $sqlFile = $this->backupDatabase();

            $backup->update([
                'file_path' => $sqlFile,
                'file_size' => File::size($sqlFile),
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return $backup->fresh();
        } catch (Exception $e) {
            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            return null;
        }
    }

    /**
     * Backup database to SQL file
     */
    protected function backupDatabase(): string
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');

        $filename = $this->backupPath . '/db_' . now()->format('Y-m-d_H-i-s') . '.sql';

        $command = sprintf(
            'mysqldump -h %s -u %s -p%s %s > %s',
            escapeshellarg($host),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($filename)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new Exception('Database backup failed');
        }

        return $filename;
    }

    /**
     * Add directory to zip archive
     */
    protected function addDirectoryToZip(ZipArchive $zip, string $path, string $zipPath): void
    {
        if (!File::exists($path)) {
            return;
        }

        $files = File::allFiles($path);

        foreach ($files as $file) {
            $relativePath = $zipPath . '/' . $file->getRelativePathname();
            $zip->addFile($file->getRealPath(), $relativePath);
        }
    }

    /**
     * Restore backup
     */
    public function restoreBackup(int $backupId): bool
    {
        $backup = Backup::findOrFail($backupId);

        try {
            if ($backup->type === 'database') {
                return $this->restoreDatabase($backup->file_path);
            } elseif ($backup->type === 'full') {
                return $this->restoreFullBackup($backup->file_path);
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Restore database from SQL file
     */
    protected function restoreDatabase(string $sqlFile): bool
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');

        $command = sprintf(
            'mysql -h %s -u %s -p%s %s < %s',
            escapeshellarg($host),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($sqlFile)
        );

        exec($command, $output, $returnVar);

        return $returnVar === 0;
    }

    /**
     * Restore full backup
     */
    protected function restoreFullBackup(string $zipFile): bool
    {
        $zip = new ZipArchive();

        if ($zip->open($zipFile) !== true) {
            return false;
        }

        $extractPath = $this->backupPath . '/restore_' . time();
        $zip->extractTo($extractPath);
        $zip->close();

        // Restore database
        $dbFiles = File::glob($extractPath . '/database/*.sql');
        if (!empty($dbFiles)) {
            $this->restoreDatabase($dbFiles[0]);
        }

        // Restore files
        if (File::exists($extractPath . '/storage/public')) {
            File::copyDirectory($extractPath . '/storage/public', storage_path('app/public'));
        }

        // Clean up
        File::deleteDirectory($extractPath);

        return true;
    }

    /**
     * Delete old backups
     */
    public function deleteOldBackups(int $daysToKeep = 30): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        $oldBackups = Backup::where('created_at', '<', $cutoffDate)->get();

        $deleted = 0;
        foreach ($oldBackups as $backup) {
            if (File::exists($backup->file_path)) {
                File::delete($backup->file_path);
            }
            $backup->delete();
            $deleted++;
        }

        return $deleted;
    }

    /**
     * Get backup list
     */
    public function getBackups()
    {
        return Backup::with('creator')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
