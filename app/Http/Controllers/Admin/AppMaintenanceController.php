<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppMaintenance;
use App\Services\WebPService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AppMaintenanceController extends Controller
{
    /**
     * Display maintenance settings
     */
    public function index()
    {
        $maintenance = AppMaintenance::getInstance();
        return view('admin.app-maintenance.index', compact('maintenance'));
    }

    /**
     * Update maintenance settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'is_maintenance_mode' => ['nullable', 'boolean'],
            'title' => ['required', 'string', 'max:255'],
            'title_en' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'message_en' => ['nullable', 'string'],
            'image_url' => ['nullable', 'image', 'max:5120'],
            'scheduled_start' => ['nullable', 'date'],
            'scheduled_end' => ['nullable', 'date', 'after:scheduled_start'],
            'show_countdown' => ['nullable', 'boolean'],
            'allow_admin_access' => ['nullable', 'boolean'],
            'allowed_user_ids' => ['nullable', 'string'],
            'bypass_key' => ['nullable', 'string', 'max:100'],
            'platform' => ['nullable', 'string', 'in:android,ios,web,all'],
        ]);

        // Handle image upload
        if ($request->hasFile('image_url')) {
            $webpService = app(WebPService::class);
            $result = $webpService->convertAndStore(
                $request->file('image_url'),
                'app-maintenance',
                85
            );
            $validated['image_url'] = $result['url'];
        }

        // Handle boolean checkboxes
        $validated['is_maintenance_mode'] = $request->has('is_maintenance_mode');
        $validated['show_countdown'] = $request->has('show_countdown');
        $validated['allow_admin_access'] = $request->has('allow_admin_access');

        // Parse allowed user IDs
        if (!empty($validated['allowed_user_ids'])) {
            $validated['allowed_user_ids'] = array_map('intval', array_map('trim', explode(',', $validated['allowed_user_ids'])));
        } else {
            $validated['allowed_user_ids'] = null;
        }

        $maintenance = AppMaintenance::getInstance();
        $maintenance->update($validated);

        return redirect()
            ->route('admin.app-maintenance.index')
            ->with('success', 'อัพเดทการตั้งค่าการปรับปรุงระบบสำเร็จ');
    }

    /**
     * Enable maintenance mode
     */
    public function enable()
    {
        $maintenance = AppMaintenance::getInstance();
        $maintenance->startMaintenance();

        return response()->json([
            'success' => true,
            'message' => 'เปิดใช้งานโหมดปรับปรุงระบบสำเร็จ',
        ]);
    }

    /**
     * Disable maintenance mode
     */
    public function disable()
    {
        $maintenance = AppMaintenance::getInstance();
        $maintenance->endMaintenance();

        return response()->json([
            'success' => true,
            'message' => 'ปิดใช้งานโหมดปรับปรุงระบบสำเร็จ',
        ]);
    }

    /**
     * Toggle maintenance mode
     */
    public function toggle()
    {
        $maintenance = AppMaintenance::getInstance();

        if ($maintenance->is_maintenance_mode) {
            $maintenance->endMaintenance();
            $message = 'ปิดใช้งานโหมดปรับปรุงระบบสำเร็จ';
        } else {
            $maintenance->startMaintenance();
            $message = 'เปิดใช้งานโหมดปรับปรุงระบบสำเร็จ';
        }

        return response()->json([
            'success' => true,
            'is_maintenance_mode' => $maintenance->is_maintenance_mode,
            'message' => $message,
        ]);
    }

    /**
     * Check maintenance status for API
     */
    public function apiStatus(Request $request)
    {
        $platform = $request->platform ?? null;
        $isInMaintenance = AppMaintenance::isInMaintenanceMode($platform);

        if (!$isInMaintenance) {
            return response()->json([
                'success' => true,
                'is_maintenance' => false,
            ]);
        }

        $maintenance = AppMaintenance::getInstance();

        // Check if user can bypass
        $userId = $request->user()->id ?? null;
        $bypassKey = $request->bypass_key ?? null;
        $isAdmin = $request->user() ? $request->user()->isAdmin() : false;

        $canBypass = $maintenance->canUserBypass($userId, $bypassKey, $isAdmin);

        if ($canBypass) {
            return response()->json([
                'success' => true,
                'is_maintenance' => false,
                'bypass' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'is_maintenance' => true,
            'data' => [
                'title' => app()->getLocale() === 'th' ? $maintenance->title : $maintenance->title_en,
                'message' => app()->getLocale() === 'th' ? $maintenance->message : $maintenance->message_en,
                'image_url' => $maintenance->image_url,
                'scheduled_end' => $maintenance->getEndTime(),
                'show_countdown' => $maintenance->show_countdown,
            ],
        ]);
    }
}
