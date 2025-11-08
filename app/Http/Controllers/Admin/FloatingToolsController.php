<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class FloatingToolsController extends Controller
{
    /**
     * Display floating tools settings
     */
    public function index()
    {
        $settings = [
            'floating_dark_mode_enabled' => Setting::get('floating_dark_mode_enabled', true),
            'floating_scroll_top_enabled' => Setting::get('floating_scroll_top_enabled', true),
            'floating_chatbot_enabled' => Setting::get('floating_chatbot_enabled', false),
            'floating_language_enabled' => Setting::get('floating_language_enabled', false),
            'floating_fab_icon' => Setting::get('floating_fab_icon', '⚙️'),
            'floating_fab_position' => Setting::get('floating_fab_position', 'bottom-right'),
            'floating_fab_color' => Setting::get('floating_fab_color', 'green'),
        ];

        return view('admin.floating-tools.index', compact('settings'));
    }

    /**
     * Update floating tools settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'floating_dark_mode_enabled' => 'nullable|boolean',
            'floating_scroll_top_enabled' => 'nullable|boolean',
            'floating_chatbot_enabled' => 'nullable|boolean',
            'floating_language_enabled' => 'nullable|boolean',
            'floating_fab_icon' => 'required|string|max:10',
            'floating_fab_position' => 'required|in:bottom-right,bottom-left',
            'floating_fab_color' => 'required|in:green,blue,purple,red,orange',
        ]);

        // Convert checkboxes to boolean
        $validated['floating_dark_mode_enabled'] = $request->has('floating_dark_mode_enabled');
        $validated['floating_scroll_top_enabled'] = $request->has('floating_scroll_top_enabled');
        $validated['floating_chatbot_enabled'] = $request->has('floating_chatbot_enabled');
        $validated['floating_language_enabled'] = $request->has('floating_language_enabled');

        foreach ($validated as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()->route('admin.floating-tools.index')
            ->with('success', 'บันทึกการตั้งค่า Floating Tools สำเร็จ');
    }
}
