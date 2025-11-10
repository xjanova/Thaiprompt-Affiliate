<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemUpdate;
use App\Models\UpdateLog;
use App\Models\UpdateNotification;
use App\Services\UpdateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateController extends Controller
{
    protected UpdateService $updateService;

    public function __construct(UpdateService $updateService)
    {
        $this->updateService = $updateService;
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
            $updates = $this->updateService->checkForUpdates(true);

            return response()->json([
                'success' => true,
                'updates' => $updates,
                'count' => count($updates),
                'message' => count($updates) > 0
                    ? 'พบอัพเดทใหม่ ' . count($updates) . ' รายการ'
                    : 'ระบบอัพเดทเป็นเวอร์ชันล่าสุดแล้ว'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
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
                    'log' => $result['log']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'เกิดข้อผิดพลาดในการอัพเดท'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
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
}
