<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * Display settings
     */
    public function index()
    {
        $settings = Setting::all()->groupBy('group');
        $availablePermissions = \App\Models\User::availablePermissions();
        return view('admin.settings.index', compact('settings', 'availablePermissions'));
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
            'home_custom_content' => ['nullable', 'string'],
            // API Settings
            'google_translate_enabled' => ['nullable', 'boolean'],
            'google_translate_api_key' => ['nullable', 'string'],
            'google_translate_project_id' => ['nullable', 'string'],
            'translate_source_language' => ['nullable', 'string', 'in:th,en'],
            'translate_cache_enabled' => ['nullable', 'boolean'],
            'tinymce_api_key' => ['nullable', 'string'],
        ]);

        // Handle checkbox values
        $validated['google_translate_enabled'] = $request->has('google_translate_enabled');
        $validated['translate_cache_enabled'] = $request->has('translate_cache_enabled');

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                $type = is_bool($value) ? 'boolean' : (is_numeric($value) ? 'integer' : 'string');
                Setting::set($key, $value, $type);
            }
        }

        return back()->with('success', 'บันทึกการตั้งค่าเรียบร้อยแล้ว');
    }

    /**
     * Update branding (logo, favicon)
     */
    public function updateBranding(Request $request)
    {
        $validated = $request->validate([
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
            'favicon' => ['nullable', 'image', 'mimes:png,jpg,jpeg,ico', 'max:512'],
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->move(
                public_path('uploads/branding'),
                'logo_' . time() . '.' . $request->file('logo')->getClientOriginalExtension()
            );
            $logoUrl = '/uploads/branding/' . basename($logoPath);
            Setting::set('logo', $logoUrl, 'string', 'branding');

            // Delete old logo if exists
            $oldLogo = Setting::get('logo');
            if ($oldLogo && file_exists(public_path($oldLogo)) && $oldLogo !== $logoUrl) {
                @unlink(public_path($oldLogo));
            }
        }

        // Handle favicon upload
        if ($request->hasFile('favicon')) {
            $faviconPath = $request->file('favicon')->move(
                public_path('uploads/branding'),
                'favicon_' . time() . '.' . $request->file('favicon')->getClientOriginalExtension()
            );
            $faviconUrl = '/uploads/branding/' . basename($faviconPath);
            Setting::set('favicon', $faviconUrl, 'string', 'branding');

            // Delete old favicon if exists
            $oldFavicon = Setting::get('favicon');
            if ($oldFavicon && file_exists(public_path($oldFavicon)) && $oldFavicon !== $faviconUrl) {
                @unlink(public_path($oldFavicon));
            }
        }

        return back()->with('success', 'Branding updated successfully.');
    }

    /**
     * Update theme colors
     */
    public function updateTheme(Request $request)
    {
        $validated = $request->validate([
            'theme_primary_start' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'theme_primary_end' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'theme_secondary_start' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'theme_secondary_end' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'theme_accent_start' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'theme_accent_end' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
        ]);

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                Setting::set($key, $value, 'string', 'theme');
            }
        }

        return back()->with('success', 'Theme colors updated successfully.');
    }
}
