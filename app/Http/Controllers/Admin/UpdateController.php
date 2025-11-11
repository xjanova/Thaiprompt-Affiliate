<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemUpdate;
use App\Models\UpdateLog;
use App\Models\UpdateNotification;
use App\Services\UpdateService;
use App\Services\VersionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateController extends Controller
{
    protected UpdateService $updateService;
    protected VersionService $versionService;

    public function __construct(UpdateService $updateService, VersionService $versionService)
    {
        $this->updateService = $updateService;
        $this->versionService = $versionService;
    }

    /**
     * Display updates index page
     */
    public function index()
    {
        $currentVersion = config('version.current');
        $availableUpdates = SystemUpdate::whereRaw('version > ?', [$currentVersion])
            ->orderBy('released_at', 'desc')
            ->paginate(10);

        $updateHistory = UpdateLog::with(['systemUpdate', 'initiator'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $stats = [
            'current_version' => $currentVersion,
            'total_updates' => $availableUpdates->total(),
            'pending_updates' => SystemUpdate::whereRaw('version > ?', [$currentVersion])
                ->where('is_stable', true)
                ->count(),
            'last_update' => UpdateLog::where('status', 'completed')
                ->latest()
                ->first(),
        ];

        return view('admin.updates.index', compact('availableUpdates', 'updateHistory', 'stats'));
    }

    /**
     * Check for new updates
     */
    public function check()
    {
        try {
            // Clear version caches first to get fresh data
            $this->versionService->clearCache();

            // Force check for updates (bypasses UpdateService cache)
            $updates = $this->updateService->checkForUpdates(true);

            // Get fresh version comparison info
            $versionInfo = $this->versionService->checkForUpdates();

            return response()->json([
                'success' => true,
                'updates' => $updates,
                'count' => count($updates),
                'version_info' => $versionInfo,
                'message' => count($updates) > 0
                    ? 'พบอัพเดทใหม่ ' . count($updates) . ' รายการ'
                    : 'ระบบอัพเดทเป็นเวอร์ชันล่าสุดแล้ว'
            ]);
        } catch (\Exception $e) {
            \Log::error('Update check failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
                'error_details' => [
                    'message' => $e->getMessage(),
                    'type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => config('app.debug') ? $e->getTraceAsString() : 'Enable APP_DEBUG to see stack trace',
                ],
            ], 500);
        }
    }

    /**
     * Show update details
     */
    public function show($id)
    {
        $update = SystemUpdate::findOrFail($id);
        $currentVersion = config('version.current');

        $requirements = [
            'php_version' => [
                'required' => $update->min_php_version ?? '8.1.0',
                'current' => PHP_VERSION,
                'passed' => version_compare(PHP_VERSION, $update->min_php_version ?? '8.1.0', '>=')
            ],
            'extensions' => $update->required_extensions ?? [],
            'can_update' => $update->meetsRequirements(),
        ];

        return view('admin.updates.show', compact('update', 'currentVersion', 'requirements'));
    }

    /**
     * Install/perform update
     */
    public function install($id)
    {
        try {
            $result = $this->updateService->performUpdate($id, Auth::id(), false);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'อัพเดทสำเร็จ!',
                    'log' => $result['log'],
                    'verification' => $result['verification'] ?? null,
                ]);
            } else {
                // Return detailed error information
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'เกิดข้อผิดพลาดในการอัพเดท',
                    'error_type' => $result['error_type'] ?? 'unknown',
                    'error_solution' => $result['error_solution'] ?? null,
                    'retryable' => $result['retryable'] ?? false,
                    'log' => $result['log'] ?? null,
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Update installation failed', [
                'update_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
                'error_details' => [
                    'message' => $e->getMessage(),
                    'type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => config('app.debug') ? $e->getTraceAsString() : 'Enable APP_DEBUG to see stack trace',
                ],
            ], 500);
        }
    }

    /**
     * Show update logs
     */
    public function logs()
    {
        $logs = UpdateLog::with(['systemUpdate', 'initiator'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.updates.logs', compact('logs'));
    }

    /**
     * Show specific log details
     */
    public function showLog($id)
    {
        $log = UpdateLog::with(['systemUpdate', 'initiator'])->findOrFail($id);

        // If AJAX request, return JSON
        if (request()->expectsJson()) {
            return response()->json([
                'log' => $log
            ]);
        }

        return view('admin.updates.log-detail', compact('log'));
    }

    /**
     * Rollback to previous version
     */
    public function rollback($id)
    {
        try {
            $result = $this->updateService->rollback($id);

            return response()->json([
                'success' => true,
                'message' => 'Rollback สำเร็จ!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'auto_check' => 'boolean',
            'auto_update' => 'boolean',
            'backup_before_update' => 'boolean',
            'notification_email' => 'nullable|email',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set('update_' . $key, $value);
        }

        return response()->json([
            'success' => true,
            'message' => 'บันทึกการตั้งค่าสำเร็จ'
        ]);
    }

    /**
     * Get update notifications
     */
    public function notifications()
    {
        $notifications = UpdateNotification::with('systemUpdate')
            ->where('user_id', Auth::id())
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }

    /**
     * Dismiss notification
     */
    public function dismissNotification($id)
    {
        $notification = UpdateNotification::where('user_id', Auth::id())
            ->findOrFail($id);

        $notification->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'ทำเครื่องหมายว่าอ่านแล้ว'
        ]);
    }

    /**
     * Test GitHub connection
     */
    public function testConnection()
    {
        try {
            $results = $this->versionService->testGitHubConnection();

            return response()->json([
                'success' => $results['success'],
                'results' => $results,
                'message' => $results['success']
                    ? 'การเชื่อมต่อ GitHub ทำงานปกติ'
                    : 'พบปัญหาในการเชื่อมต่อ GitHub',
                'debug_info' => [
                    'api_url' => config('version.repository.api_url'),
                    'has_token' => !empty(config('version.repository.token', env('GITHUB_TOKEN'))),
                    'php_version' => PHP_VERSION,
                    'curl_enabled' => function_exists('curl_version'),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('GitHub connection test failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
                'error_details' => [
                    'message' => $e->getMessage(),
                    'type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => config('app.debug') ? $e->getTraceAsString() : 'Enable APP_DEBUG to see stack trace',
                ],
            ], 500);
        }
    }
}
