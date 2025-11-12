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
        // This would re-run the default settings from migration
        ClassicXSetting::resetToDefaults();

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
