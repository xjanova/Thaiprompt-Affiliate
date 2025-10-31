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
            'default_sponsor_referral_code' => ['nullable', 'string', 'exists:affiliates,referral_code'],
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
                $group = in_array($key, ['commission_rate', 'multi_level_enabled', 'default_sponsor_referral_code']) ? 'affiliate' : 'general';
                Setting::set($key, $value, $type, $group);
            }
        }

        return back()->with('success', 'บันทึกการตั้งค่าเรียบร้อยแล้ว');
    }

    /**
     * Update branding (logo, favicon)
     */
    public function updateBranding(Request $request)
    {
        // ตรวจสอบ storage symlink ก่อนอัพโหลด
        if (!$this->checkStorageLink()) {
            return back()->withErrors([
                'storage' => 'ไม่พบ storage symlink กรุณารันคำสั่ง "php artisan storage:fix" ก่อนอัพโหลดไฟล์'
            ]);
        }

        $validated = $request->validate([
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
            'favicon' => ['nullable', 'image', 'mimes:png,jpg,jpeg,ico', 'max:512'],
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Store in storage/app/public/branding (persistent across deployments)
            $logoPath = $request->file('logo')->store('branding', 'public');
            $logoUrl = '/storage/' . $logoPath;

            // Delete old logo if exists
            $oldLogo = Setting::get('logo');
            if ($oldLogo) {
                // Extract path from URL (/storage/branding/xxx.png -> branding/xxx.png)
                $oldPath = str_replace('/storage/', '', $oldLogo);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            Setting::set('logo', $logoUrl, 'string', 'branding');
        }

        // Handle favicon upload
        if ($request->hasFile('favicon')) {
            // Store in storage/app/public/branding (persistent across deployments)
            $faviconPath = $request->file('favicon')->store('branding', 'public');
            $faviconUrl = '/storage/' . $faviconPath;

            // Delete old favicon if exists
            $oldFavicon = Setting::get('favicon');
            if ($oldFavicon) {
                // Extract path from URL (/storage/branding/xxx.ico -> branding/xxx.ico)
                $oldPath = str_replace('/storage/', '', $oldFavicon);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            Setting::set('favicon', $faviconUrl, 'string', 'branding');
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

    /**
     * ตรวจสอบว่า storage symlink มีอยู่หรือไม่
     */
    protected function checkStorageLink(): bool
    {
        $link = public_path('storage');

        // ตรวจสอบว่ามี symlink และชี้ไปยัง storage/app/public
        if (is_link($link)) {
            $target = storage_path('app/public');
            return readlink($link) === $target;
        }

        return false;
    }
}
