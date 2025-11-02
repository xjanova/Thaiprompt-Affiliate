<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LineChatWidgetSetting;
use App\Models\LineAvatar;
use Illuminate\Http\Request;

class LineChatWidgetController extends Controller
{
    public function index()
    {
        $settings = LineChatWidgetSetting::first() ?? new LineChatWidgetSetting();

        return view('admin.line-bot.chat-widget.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|in:bottom-right,bottom-left,top-right,top-left',
            'primary_color' => 'required|string|max:7',
            'secondary_color' => 'required|string|max:7',
            'welcome_message' => 'required|string',
            'avatar_id' => 'nullable|exists:line_avatars,id',
            'show_on_pages' => 'boolean',
            'enabled_pages' => 'nullable|array',
            'enable_sound' => 'boolean',
            'enable_notifications' => 'boolean',
            'auto_open' => 'boolean',
            'auto_open_delay' => 'integer|min:0',
            'offline_message' => 'nullable|string',
            'enable_user_chat' => 'boolean',
            'enable_seller_chat' => 'boolean',
            'enable_ai_chat' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $setting = LineChatWidgetSetting::first();

        if ($setting) {
            $setting->update($validated);
        } else {
            $setting = LineChatWidgetSetting::create($validated);
        }

        LineChatWidgetSetting::clearCache();

        return redirect()->route('admin.line-bot.chat-widget.index')
            ->with('success', 'บันทึกการตั้งค่า Chat Widget สำเร็จ');
    }
}
