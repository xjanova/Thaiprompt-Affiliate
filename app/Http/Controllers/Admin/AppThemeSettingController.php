<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppThemeSetting;
use App\Services\WebPService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AppThemeSettingController extends Controller
{
    /**
     * Display theme settings
     */
    public function index()
    {
        $themeSettings = AppThemeSetting::getInstance();
        return view('admin.app-theme.index', compact('themeSettings'));
    }

    /**
     * Update theme settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'theme_name' => ['required', 'string', 'max:100'],
            // Colors
            'primary_color' => ['required', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'primary_dark_color' => ['required', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'primary_light_color' => ['required', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'secondary_color' => ['required', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'secondary_dark_color' => ['required', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'secondary_light_color' => ['required', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'background_color' => ['required', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'background_dark_color' => ['required', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'surface_color' => ['required', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'surface_dark_color' => ['required', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'text_primary_color' => ['required', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'text_primary_dark_color' => ['required', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'text_secondary_color' => ['required', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'text_secondary_dark_color' => ['required', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'success_color' => ['required', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'warning_color' => ['required', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'error_color' => ['required', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'info_color' => ['required', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'border_color' => ['required', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'border_dark_color' => ['required', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'divider_color' => ['required', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'divider_dark_color' => ['required', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            // Fonts
            'font_family' => ['required', 'string', 'max:100'],
            'font_family_en' => ['required', 'string', 'max:100'],
            'font_size_base' => ['required', 'integer', 'min:10', 'max:30'],
            'font_size_small' => ['required', 'integer', 'min:8', 'max:20'],
            'font_size_large' => ['required', 'integer', 'min:12', 'max:40'],
            'font_size_xlarge' => ['required', 'integer', 'min:14', 'max:50'],
            // Border Radius
            'border_radius_small' => ['required', 'integer', 'min:0', 'max:20'],
            'border_radius_medium' => ['required', 'integer', 'min:0', 'max:30'],
            'border_radius_large' => ['required', 'integer', 'min:0', 'max:40'],
            'border_radius_xlarge' => ['required', 'integer', 'min:0', 'max:50'],
            // Spacing
            'spacing_small' => ['required', 'integer', 'min:0', 'max:50'],
            'spacing_medium' => ['required', 'integer', 'min:0', 'max:100'],
            'spacing_large' => ['required', 'integer', 'min:0', 'max:150'],
            'spacing_xlarge' => ['required', 'integer', 'min:0', 'max:200'],
            // Animation
            'animation_duration' => ['required', 'integer', 'min:100', 'max:2000'],
            'animation_curve' => ['required', 'string', 'max:50'],
            // Dark Mode
            'dark_mode_enabled' => ['nullable', 'boolean'],
            'dark_mode_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Handle image uploads
        $webpService = app(WebPService::class);

        $imageFields = [
            'logo_url',
            'logo_dark_url',
            'favicon_url',
            'app_icon_url',
            'splash_screen_url',
            'splash_screen_dark_url',
        ];

        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {
                $result = $webpService->convertAndStore(
                    $request->file($field),
                    'app-theme',
                    85
                );
                $validated[$field] = $result['url'];
            }
        }

        // Handle boolean checkboxes
        $validated['dark_mode_enabled'] = $request->has('dark_mode_enabled');
        $validated['dark_mode_default'] = $request->has('dark_mode_default');
        $validated['is_active'] = $request->has('is_active');

        $themeSettings = AppThemeSetting::getInstance();
        $themeSettings->update($validated);

        return redirect()
            ->route('admin.app-theme.index')
            ->with('success', 'อัพเดทธีมแอพสำเร็จ');
    }

    /**
     * Get theme settings for API
     */
    public function apiTheme()
    {
        $themeSettings = AppThemeSetting::getInstance();

        return response()->json([
            'success' => true,
            'data' => $themeSettings,
        ]);
    }
}
