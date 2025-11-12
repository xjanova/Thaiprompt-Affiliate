<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassicXSetting;
use Illuminate\Http\Request;

class ClassicXSettingsController extends Controller
{
    /**
     * Display the Classic X settings page
     */
    public function index()
    {
        $settings = ClassicXSetting::getAll();
        return view('admin.classic-x-settings.index', compact('settings'));
    }

    /**
     * Update Classic X settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable'
        ]);

        foreach ($validated['settings'] as $key => $value) {
            // Get the setting to determine its type
            $setting = ClassicXSetting::where('key', $key)->first();

            if ($setting) {
                ClassicXSetting::set($key, $value, $setting->type);
            }
        }

        ClassicXSetting::clearCache();

        return redirect()->back()->with('success', 'Classic X settings updated successfully!');
    }

    /**
     * Reset settings to defaults
     */
    public function reset()
    {
        // Clear cache first
        ClassicXSetting::clearCache();

        // Return JSON response for AJAX request
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Classic X settings reset to defaults!'
            ]);
        }

        return redirect()->back()->with('success', 'Classic X settings reset to defaults!');
    }

    /**
     * Preview theme
     */
    public function preview()
    {
        return view('admin.classic-x-settings.preview');
    }
}
