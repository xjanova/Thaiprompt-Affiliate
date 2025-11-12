<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class HeaderSettingsController extends Controller
{
    /**
     * Display header settings
     */
    public function index()
    {
        return view('admin.header-settings.index');
    }

    /**
     * Update header settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            // Basic Header Settings
            'header_layout' => ['nullable', 'string', 'in:default,centered,split'],
            'header_position' => ['nullable', 'string', 'in:relative,fixed,absolute'],
            'header_height' => ['nullable', 'integer', 'min:40', 'max:200'],
            'header_height_scrolled' => ['nullable', 'integer', 'min:40', 'max:200'],
            'header_padding_x' => ['nullable', 'integer', 'min:0', 'max:100'],

            // Colors
            'header_bg_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'header_bg_opacity' => ['nullable', 'integer', 'min:0', 'max:100'],
            'header_text_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'header_text_color_scroll' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'header_hover_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],

            // Border
            'header_border_bottom' => ['nullable', 'boolean'],
            'header_border_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'header_border_width' => ['nullable', 'integer', 'min:0', 'max:10'],

            // Shadow & Effects
            'header_shadow' => ['nullable', 'string', 'in:none,sm,md,lg,xl,2xl'],
            'header_shadow_on_scroll' => ['nullable', 'boolean'],
            'header_blur' => ['nullable', 'boolean'],
            'header_blur_amount' => ['nullable', 'integer', 'min:0', 'max:30'],

            // Behavior
            'header_transparent' => ['nullable', 'boolean'],
            'header_transparent_on_scroll' => ['nullable', 'boolean'],
            'header_sticky_scroll' => ['nullable', 'boolean'],
            'header_hide_on_scroll' => ['nullable', 'boolean'],
            'header_shrink_on_scroll' => ['nullable', 'boolean'],

            // Animation
            'header_animation_duration' => ['nullable', 'integer', 'min:100', 'max:1000'],
            'header_animation_easing' => ['nullable', 'string', 'max:50'],

            // Gradient
            'header_gradient' => ['nullable', 'boolean'],
            'header_gradient_from' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'header_gradient_to' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'header_gradient_direction' => ['nullable', 'string', 'in:to-r,to-l,to-b,to-t,to-br,to-bl'],

            // Logo Settings
            'header_logo_height' => ['nullable', 'integer', 'min:20', 'max:200'],
            'header_logo_height_scrolled' => ['nullable', 'integer', 'min:20', 'max:200'],
            'logo_navigation_width' => ['nullable', 'integer', 'min:20', 'max:400'],
            'logo_navigation_height' => ['nullable', 'integer', 'min:20', 'max:200'],
            'logo_navigation_scrolled_width' => ['nullable', 'integer', 'min:20', 'max:400'],
            'logo_navigation_scrolled_height' => ['nullable', 'integer', 'min:20', 'max:200'],
            'header_show_logo' => ['nullable', 'boolean'],
            'header_logo_animated' => ['nullable', 'boolean'],

            // 3D Effects Settings
            'header_3d_enabled' => ['nullable', 'boolean'],
            'header_3d_depth' => ['nullable', 'integer', 'min:0', 'max:10'],
            'header_3d_perspective' => ['nullable', 'integer', 'min:500', 'max:2000'],
            'header_3d_shadow_intensity' => ['nullable', 'string', 'in:low,medium,high'],

            // Windows UI Settings
            'header_windows_ui_mode' => ['nullable', 'boolean'],
            'header_windows_ui_height' => ['nullable', 'integer', 'min:30', 'max:100'],
        ]);

        // Handle checkbox values
        $checkboxFields = [
            'header_border_bottom',
            'header_shadow_on_scroll',
            'header_blur',
            'header_transparent',
            'header_transparent_on_scroll',
            'header_sticky_scroll',
            'header_hide_on_scroll',
            'header_shrink_on_scroll',
            'header_gradient',
            'header_show_logo',
            'header_logo_animated',
            'header_3d_enabled',
            'header_windows_ui_mode',
        ];

        foreach ($checkboxFields as $field) {
            $validated[$field] = $request->has($field);
        }

        // Save each setting
        foreach ($validated as $key => $value) {
            $type = is_bool($value) ? 'boolean' : (is_numeric($value) ? 'integer' : 'string');
            Setting::set($key, $value, $type, 'header');
        }

        return redirect()
            ->route('admin.header-settings.index')
            ->with('success', 'การตั้งค่า Header ได้รับการอัพเดทเรียบร้อยแล้ว');
    }
}
