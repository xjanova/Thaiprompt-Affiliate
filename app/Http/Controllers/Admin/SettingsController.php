<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * Show settings page
     */
    public function index()
    {
        $settings = AppSetting::orderBy('group')->get()->groupBy('group');

        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($request->settings as $key => $value) {
            $setting = AppSetting::where('key', $key)->first();

            if (!$setting || !$setting->is_editable) {
                continue;
            }

            if ($setting->type === 'file' && $request->hasFile("file_{$key}")) {
                $file = $request->file("file_{$key}");
                $path = $file->store('settings', 'public');

                // Delete old file
                if ($setting->value) {
                    Storage::disk('public')->delete($setting->value);
                }

                $value = $path;
            }

            $setting->update(['value' => $value]);
        }

        AppSetting::clearCache();

        return redirect()->back()->with('success', 'Settings updated successfully');
    }

    /**
     * Upload logo
     */
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|max:2048',
        ]);

        $path = $request->file('logo')->store('logos', 'public');

        AppSetting::set('app_logo', $path, 'file', 'general');

        return response()->json([
            'success' => true,
            'path' => Storage::url($path),
        ]);
    }

    /**
     * Upload favicon
     */
    public function uploadFavicon(Request $request)
    {
        $request->validate([
            'favicon' => 'required|image|max:512',
        ]);

        $path = $request->file('favicon')->store('favicons', 'public');

        AppSetting::set('app_favicon', $path, 'file', 'general');

        return response()->json([
            'success' => true,
            'path' => Storage::url($path),
        ]);
    }

    /**
     * Get public settings for frontend
     */
    public function getPublicSettings()
    {
        $settings = AppSetting::getPublicSettings();

        return response()->json($settings);
    }
}
