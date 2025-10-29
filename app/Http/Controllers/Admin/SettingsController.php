<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Display settings
     */
    public function index()
    {
        $settings = Setting::all()->groupBy('group');
        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'app_name' => ['nullable', 'string', 'max:255'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'multi_level_enabled' => ['nullable', 'boolean'],
        ]);

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                $type = is_bool($value) ? 'boolean' : (is_numeric($value) ? 'integer' : 'string');
                Setting::set($key, $value, $type);
            }
        }

        return back()->with('success', 'Settings updated successfully.');
    }
}
