<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\WebPService;
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
            'commission_depth' => ['nullable', 'integer', 'min:1', 'max:100'],
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
            // Page Loader Settings
            'page_loader_enabled' => ['nullable', 'boolean'],
            'page_loader_type' => ['nullable', 'string', 'in:spinner,dots,pulse,progress,gradient_spinner,wave,bouncing_balls,custom_gif'],
            'page_loader_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'page_loader_color_secondary' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'page_loader_progress_mode' => ['nullable', 'string', 'in:real,fake'],
        ]);

        // Validate GIF file separately
        if ($request->hasFile('page_loader_gif')) {
            $request->validate([
                'page_loader_gif' => ['required', 'image', 'mimes:gif,webp', 'max:2048'],
            ]);
        }

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

        // Handle Page Loader checkbox
        $validated['page_loader_enabled'] = $request->has('page_loader_enabled');

        // Handle GIF upload for page loader
        if ($request->hasFile('page_loader_gif')) {
            $webpService = app(WebPService::class);

            // Note: GIFs are not converted to WebP to preserve animation
            $extension = strtolower($request->file('page_loader_gif')->getClientOriginalExtension());
            if ($extension === 'gif') {
                // Keep GIF as is for animation support
                $gifPath = $request->file('page_loader_gif')->store('page-loaders', 'public');
                $gifUrl = '/storage/' . $gifPath;
            } else {
                // Convert static images to WebP
                $result = $webpService->convertAndStore($request->file('page_loader_gif'), 'page-loaders', 85);
                $gifUrl = $result['url'];
            }

            // Delete old file if exists
            $oldGif = Setting::get('page_loader_gif');
            if ($oldGif) {
                $oldPath = str_replace('/storage/', '', $oldGif);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $validated['page_loader_gif'] = $gifUrl;
        }

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                $type = is_bool($value) ? 'boolean' : (is_numeric($value) ? 'integer' : 'string');

                // Determine group
                if (in_array($key, ['commission_rate', 'multi_level_enabled', 'commission_depth', 'default_sponsor_referral_code'])) {
                    $group = 'affiliate';
                } elseif (str_starts_with($key, 'turnstile_')) {
                    $group = 'security';
                } elseif (str_starts_with($key, 'page_loader_')) {
                    $group = 'appearance';
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
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'favicon' => ['nullable', 'image', 'mimes:png,jpg,jpeg,ico,webp', 'max:512'],
        ]);

        $webpService = app(WebPService::class);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Convert to WebP and store in storage/app/public/branding
            $result = $webpService->convertAndStore($request->file('logo'), 'branding', 90);
            $logoUrl = $result['url'];

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
            // Convert to WebP and store in storage/app/public/branding
            $result = $webpService->convertAndStore($request->file('favicon'), 'branding', 90);
            $faviconUrl = $result['url'];

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

    /**
     * Show OCR settings page
     */
    public function ocr()
    {
        $ocrSettings = Setting::all()->where('group', 'ocr');

        // Check if credentials file exists
        $credentialsPath = Setting::get('google_vision_credentials_path');
        $credentialsExists = !empty($credentialsPath) && file_exists($credentialsPath);

        // Get credentials info if file exists
        $credentialsInfo = null;
        if ($credentialsExists) {
            try {
                $content = file_get_contents($credentialsPath);
                $json = json_decode($content, true);
                if ($json) {
                    $credentialsInfo = [
                        'project_id' => $json['project_id'] ?? 'N/A',
                        'client_email' => $json['client_email'] ?? 'N/A',
                        'type' => $json['type'] ?? 'N/A',
                    ];
                }
            } catch (\Exception $e) {
                $credentialsInfo = null;
            }
        }

        return view('admin.settings.ocr', compact('ocrSettings', 'credentialsExists', 'credentialsInfo'));
    }

    /**
     * Update OCR settings
     */
    public function updateOcr(Request $request)
    {
        $validated = $request->validate([
            'google_vision_enabled' => 'nullable|boolean',
            'google_vision_project_id' => 'nullable|string|max:255',
            'credentials_file' => 'nullable|file|mimes:json|max:1024', // Max 1MB
        ]);

        // Update enabled status
        Setting::set('google_vision_enabled', $request->has('google_vision_enabled') ? '1' : '0', 'boolean', 'ocr');

        // Update project ID
        if ($request->filled('google_vision_project_id')) {
            Setting::set('google_vision_project_id', $request->google_vision_project_id, 'string', 'ocr');
        }

        // Upload credentials file if provided
        if ($request->hasFile('credentials_file')) {
            $file = $request->file('credentials_file');

            // Validate JSON content
            $content = file_get_contents($file->getRealPath());
            $json = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->with('error', 'ไฟล์ JSON ไม่ถูกต้อง กรุณาตรวจสอบไฟล์อีกครั้ง');
            }

            // Check if it's a valid service account key
            if (!isset($json['type']) || $json['type'] !== 'service_account') {
                return back()->with('error', 'ไฟล์นี้ไม่ใช่ Service Account Key ที่ถูกต้อง');
            }

            // Save the file
            $fileName = 'google-credentials.json';
            $path = storage_path('app/' . $fileName);

            // Backup old file if exists
            if (file_exists($path)) {
                $backupPath = storage_path('app/google-credentials-backup-' . date('Y-m-d-His') . '.json');
                copy($path, $backupPath);
            }

            // Save new file
            file_put_contents($path, $content);

            // Update setting
            Setting::set('google_vision_credentials_path', $path, 'string', 'ocr');

            return back()->with('success', 'บันทึกการตั้งค่า OCR เรียบร้อยแล้ว และอัปโหลดไฟล์ credentials สำเร็จ');
        }

        return back()->with('success', 'บันทึกการตั้งค่า OCR เรียบร้อยแล้ว');
    }

    /**
     * Test Google Cloud Vision API connection
     */
    public function testOcrConnection()
    {
        try {
            // Check if Google Cloud Vision library is installed
            if (!class_exists('\Google\Cloud\Vision\V1\ImageAnnotatorClient')) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบ Google Cloud Vision library กรุณาติดตั้งด้วย: composer require google/cloud-vision',
                ], 500);
            }

            $credentialsPath = Setting::get('google_vision_credentials_path');

            if (empty($credentialsPath) || !file_exists($credentialsPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบไฟล์ credentials กรุณาอัปโหลดไฟล์ก่อน',
                ], 400);
            }

            // Validate JSON file
            $jsonContent = file_get_contents($credentialsPath);
            $credentials = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไฟล์ credentials ไม่ใช่ JSON ที่ถูกต้อง',
                ], 400);
            }

            if (!isset($credentials['type']) || $credentials['type'] !== 'service_account') {
                return response()->json([
                    'success' => false,
                    'message' => 'ไฟล์นี้ไม่ใช่ Service Account Key ที่ถูกต้อง',
                ], 400);
            }

            // Try to initialize Google Cloud Vision client
            $client = new \Google\Cloud\Vision\V1\ImageAnnotatorClient([
                'credentials' => $credentialsPath
            ]);

            // Close the client
            $client->close();

            return response()->json([
                'success' => true,
                'message' => 'เชื่อมต่อ Google Cloud Vision API สำเร็จ! Project: ' . ($credentials['project_id'] ?? 'N/A'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เชื่อมต่อ Google Cloud Vision API ไม่สำเร็จ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show setup guide
     */
    public function setupGuide()
    {
        return view('admin.settings.setup-guide');
    }
}
