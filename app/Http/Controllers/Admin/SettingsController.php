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
            // API Settings
            'google_translate_enabled' => ['nullable', 'boolean'],
            'google_translate_api_key' => ['nullable', 'string'],
            'google_translate_project_id' => ['nullable', 'string'],
            'translate_source_language' => ['nullable', 'string', 'in:th,en'],
            'translate_cache_enabled' => ['nullable', 'boolean'],
            'tinymce_api_key' => ['nullable', 'string'],
            // Cloudflare Turnstile Settings
            'turnstile_enabled' => ['nullable', 'boolean'],
            'turnstile_site_key' => ['nullable', 'string'],
            'turnstile_secret_key' => ['nullable', 'string'],
            'turnstile_bypass_admin' => ['nullable', 'boolean'],
            'turnstile_theme' => ['nullable', 'string', 'in:auto,light,dark'],
            'turnstile_size' => ['nullable', 'string', 'in:normal,compact'],
            // Turnstile Protection Points
            'turnstile_login' => ['nullable', 'boolean'],
            'turnstile_register' => ['nullable', 'boolean'],
            'turnstile_password_change' => ['nullable', 'boolean'],
            'turnstile_profile_update' => ['nullable', 'boolean'],
            'turnstile_withdrawal' => ['nullable', 'boolean'],
            'turnstile_affiliate_app' => ['nullable', 'boolean'],
        ]);

        // Handle checkbox values
        $validated['google_translate_enabled'] = $request->has('google_translate_enabled');
        $validated['translate_cache_enabled'] = $request->has('translate_cache_enabled');

        // Handle Turnstile checkbox values
        $validated['turnstile_enabled'] = $request->has('turnstile_enabled');
        $validated['turnstile_bypass_admin'] = $request->has('turnstile_bypass_admin');
        $validated['turnstile_login'] = $request->has('turnstile_login');
        $validated['turnstile_register'] = $request->has('turnstile_register');
        $validated['turnstile_password_change'] = $request->has('turnstile_password_change');
        $validated['turnstile_profile_update'] = $request->has('turnstile_profile_update');
        $validated['turnstile_withdrawal'] = $request->has('turnstile_withdrawal');
        $validated['turnstile_affiliate_app'] = $request->has('turnstile_affiliate_app');

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                $type = is_bool($value) ? 'boolean' : (is_numeric($value) ? 'integer' : 'string');

                // Determine group
                if (in_array($key, ['commission_rate', 'multi_level_enabled', 'default_sponsor_referral_code'])) {
                    $group = 'affiliate';
                } elseif (str_starts_with($key, 'turnstile_')) {
                    $group = 'security';
                } else {
                    $group = 'general';
                }

                Setting::set($key, $value, $type, $group);
            }
        }

        // Update config cache if Turnstile settings changed
        if ($request->hasAny(['turnstile_enabled', 'turnstile_site_key', 'turnstile_secret_key', 'turnstile_bypass_admin',
                              'turnstile_theme', 'turnstile_size', 'turnstile_login', 'turnstile_register',
                              'turnstile_password_change', 'turnstile_profile_update', 'turnstile_withdrawal', 'turnstile_affiliate_app'])) {
            // Update .env file
            $this->updateEnvFile($request);
        }

        return back()->with('success', 'บันทึกการตั้งค่าเรียบร้อยแล้ว');
    }

    /**
     * Update .env file with Turnstile settings
     */
    protected function updateEnvFile(Request $request)
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            return;
        }

        $envContent = file_get_contents($envPath);

        // Turnstile settings to update
        $envSettings = [
            'CLOUDFLARE_TURNSTILE_ENABLED' => $request->has('turnstile_enabled') ? 'true' : 'false',
            'CLOUDFLARE_TURNSTILE_SITE_KEY' => $request->input('turnstile_site_key', ''),
            'CLOUDFLARE_TURNSTILE_SECRET_KEY' => $request->input('turnstile_secret_key', ''),
            'CLOUDFLARE_TURNSTILE_BYPASS_ADMIN' => $request->has('turnstile_bypass_admin') ? 'true' : 'false',
            'CLOUDFLARE_TURNSTILE_THEME' => $request->input('turnstile_theme', 'auto'),
            'CLOUDFLARE_TURNSTILE_SIZE' => $request->input('turnstile_size', 'normal'),
            'CLOUDFLARE_TURNSTILE_LOGIN' => $request->has('turnstile_login') ? 'true' : 'false',
            'CLOUDFLARE_TURNSTILE_REGISTER' => $request->has('turnstile_register') ? 'true' : 'false',
            'CLOUDFLARE_TURNSTILE_PASSWORD_CHANGE' => $request->has('turnstile_password_change') ? 'true' : 'false',
            'CLOUDFLARE_TURNSTILE_PROFILE_UPDATE' => $request->has('turnstile_profile_update') ? 'true' : 'false',
            'CLOUDFLARE_TURNSTILE_WITHDRAWAL' => $request->has('turnstile_withdrawal') ? 'true' : 'false',
            'CLOUDFLARE_TURNSTILE_AFFILIATE_APP' => $request->has('turnstile_affiliate_app') ? 'true' : 'false',
        ];

        foreach ($envSettings as $key => $value) {
            // Check if key exists in .env
            if (preg_match("/^{$key}=/m", $envContent)) {
                // Update existing value
                $envContent = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $envContent
                );
            } else {
                // Append new key
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);
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
