<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Backup\BackupService;
use App\Models\Backup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Show backups page
     */
    public function index()
    {
        $backups = $this->backupService->getBackups();

        return view('admin.backups.index', compact('backups'));
    }

    /**
     * Create new backup
     */
    public function create(Request $request)
    {
        $request->validate([
            'type' => 'required|in:full,database',
        ]);

        $userId = auth()->id();

        $backup = $request->type === 'full'
            ? $this->backupService->createFullBackup($userId)
            : $this->backupService->createDatabaseBackup($userId);

        if ($backup) {
            return response()->json([
                'success' => true,
                'message' => 'Backup created successfully',
                'backup' => $backup,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to create backup',
        ], 500);
    }

    /**
     * Download backup
     */
    public function download(Backup $backup)
    {
        if (!file_exists($backup->file_path)) {
            abort(404, 'Backup file not found');
        }

        return response()->download($backup->file_path);
    }

    /**
     * Restore backup
     */
    public function restore(Backup $backup)
    {
        $success = $this->backupService->restoreBackup($backup->id);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Backup restored successfully',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to restore backup',
        ], 500);
    }

    /**
     * Delete backup
     */
    public function destroy(Backup $backup)
    {
        if (file_exists($backup->file_path)) {
            unlink($backup->file_path);
        }

        $backup->delete();

        return response()->json([
            'success' => true,
            'message' => 'Backup deleted successfully',
        ]);
    }

    /**
     * Clean old backups
     */
    public function clean(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:1',
        ]);

        $deleted = $this->backupService->deleteOldBackups($request->days);

        return response()->json([
            'success' => true,
            'message' => "Deleted {$deleted} old backups",
            'deleted' => $deleted,
        ]);
    }
}
