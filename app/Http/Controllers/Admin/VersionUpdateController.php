<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Version\VersionUpdateService;
use Illuminate\Http\Request;

class VersionUpdateController extends Controller
{
    protected VersionUpdateService $versionService;

    public function __construct(VersionUpdateService $versionService)
    {
        $this->versionService = $versionService;
        $this->middleware(['auth', 'role:admin']);
    }

    /**
     * Show version update dashboard
     */
    public function index()
    {
        $versionInfo = $this->versionService->getCurrentVersionInfo();
        $updateHistory = $this->versionService->getUpdateHistory(10);

        return view('admin.version.index', compact('versionInfo', 'updateHistory'));
    }

    /**
     * Check for updates
     */
    public function checkUpdates()
    {
        $result = $this->versionService->checkForUpdates();

        if ($result['success']) {
            return back()->with('success', 'Version check completed');
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Show update details
     */
    public function showUpdate()
    {
        $versionInfo = $this->versionService->getCurrentVersionInfo();

        if (!$versionInfo['update_available']) {
            return redirect()
                ->route('admin.version.index')
                ->with('info', 'No update available');
        }

        return view('admin.version.update', compact('versionInfo'));
    }

    /**
     * Start update process
     */
    public function startUpdate(Request $request)
    {
        $result = $this->versionService->startUpdate(auth()->user());

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'instructions' => $result['instructions'],
                'update_log_id' => $result['update_log_id'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 400);
    }

    /**
     * Complete update process
     */
    public function completeUpdate(Request $request)
    {
        $request->validate([
            'update_log_id' => 'required|exists:system_update_logs,id',
        ]);

        $result = $this->versionService->completeUpdate($request->update_log_id);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'new_version' => $result['new_version'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 400);
    }

    /**
     * Mark update as failed
     */
    public function failUpdate(Request $request)
    {
        $request->validate([
            'update_log_id' => 'required|exists:system_update_logs,id',
            'error_message' => 'required|string',
        ]);

        $this->versionService->failUpdate(
            $request->update_log_id,
            $request->error_message
        );

        return response()->json([
            'success' => true,
            'message' => 'Update marked as failed',
        ]);
    }

    /**
     * View update history
     */
    public function history()
    {
        $updateHistory = $this->versionService->getUpdateHistory(50);

        return view('admin.version.history', compact('updateHistory'));
    }

    /**
     * Get GitHub releases
     */
    public function releases()
    {
        $releases = $this->versionService->getGitHubReleases(20);

        return view('admin.version.releases', compact('releases'));
    }

    /**
     * API: Get current version
     */
    public function apiVersion()
    {
        $versionInfo = $this->versionService->getCurrentVersionInfo();

        return response()->json($versionInfo);
    }

    /**
     * API: Check for updates
     */
    public function apiCheckUpdates()
    {
        $result = $this->versionService->checkForUpdates();

        return response()->json($result);
    }
}
