<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class AppSettingController extends Controller
{
    /**
     * Display app settings
     */
    public function index()
    {
        $appSettings = AppSetting::getInstance();
        return view('admin.app-settings.index', compact('appSettings'));
    }

    /**
     * Update app settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:255'],
            'app_short_name' => ['nullable', 'string', 'max:50'],
            'app_description' => ['nullable', 'string'],
            'app_version' => ['required', 'string', 'max:20'],
            'app_build_number' => ['required', 'string', 'max:20'],
            'force_update' => ['nullable', 'boolean'],
            'min_supported_version' => ['nullable', 'string', 'max:20'],
            'latest_version' => ['nullable', 'string', 'max:20'],
            'update_message_th' => ['nullable', 'string'],
            'update_message_en' => ['nullable', 'string'],
            'app_store_url' => ['nullable', 'url', 'max:500'],
            'play_store_url' => ['nullable', 'url', 'max:500'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:20'],
            'support_line_id' => ['nullable', 'string', 'max:100'],
            'privacy_policy_url' => ['nullable', 'url', 'max:500'],
            'terms_url' => ['nullable', 'url', 'max:500'],
            'default_language' => ['required', 'string', 'in:th,en'],
            'supported_languages' => ['nullable', 'array'],
            'currency' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Handle boolean checkboxes
        $validated['force_update'] = $request->has('force_update');
        $validated['is_active'] = $request->has('is_active');

        $appSettings = AppSetting::getInstance();
        $appSettings->update($validated);

        return redirect()
            ->route('admin.app-management.settings.index')
            ->with('success', 'อัพเดทการตั้งค่าแอพสำเร็จ');
    }

    /**
     * Get app settings for API
     */
    public function apiSettings()
    {
        $appSettings = AppSetting::getInstance();

        return response()->json([
            'success' => true,
            'data' => $appSettings,
        ]);
    }
}
