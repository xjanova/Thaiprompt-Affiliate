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
     * Display settings (V3)
     */
    public function index()
    {
        $settings = Setting::all()->groupBy('group');
        $availablePermissions = \App\Models\User::availablePermissions();
        return view('admin.settings-v3', compact('settings', 'availablePermissions'));
    }

    /**
     * แสดงหน้าแก้ไขโปรไฟล์ (V3)
     */
    public function profile()
    {
        $user = auth()->user();
        return view('admin.profile-v3', compact('user'));
    }

    /**
     * อัพเดทข้อมูลโปรไฟล์ส่วนตัว
     */
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . auth()->id()],
            'phone' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:255'],
        ]);

        $user = auth()->user();
        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทข้อมูลส่วนตัวสำเร็จ',
            'user' => $user
        ]);
    }

    /**
     * เปลี่ยนรหัสผ่าน
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = auth()->user();

        // ตรวจสอบรหัสผ่านปัจจุบัน
        if (!\Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง'
            ], 422);
        }

        // อัพเดทรหัสผ่านใหม่
        $user->update([
            'password' => \Hash::make($validated['new_password'])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'เปลี่ยนรหัสผ่านสำเร็จ'
        ]);
    }

    /**
     * Update settings
     *
     * รองรับทั้ง form data และ JSON request
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

        // ตรวจสอบว่าเป็น JSON request หรือ form request
        $isJsonRequest = $request->isJson() || $request->header('Content-Type') === 'application/json';

        // Handle checkbox values
        // สำหรับ JSON request ใช้ค่า boolean โดยตรง
        // สำหรับ form request ใช้ $request->has()
        if ($isJsonRequest) {
            // JSON: ใช้ค่าที่ส่งมาโดยตรง (true/false)
            $validated['google_translate_enabled'] = (bool) ($validated['google_translate_enabled'] ?? false);
            $validated['translate_cache_enabled'] = (bool) ($validated['translate_cache_enabled'] ?? false);

            // Turnstile checkboxes
            $validated['turnstile_enabled'] = (bool) ($validated['turnstile_enabled'] ?? false);
            $validated['turnstile_bypass_admin'] = (bool) ($validated['turnstile_bypass_admin'] ?? false);
            $validated['turnstile_login'] = (bool) ($validated['turnstile_login'] ?? false);
            $validated['turnstile_register'] = (bool) ($validated['turnstile_register'] ?? false);
            $validated['turnstile_password_change'] = (bool) ($validated['turnstile_password_change'] ?? false);
            $validated['turnstile_profile_update'] = (bool) ($validated['turnstile_profile_update'] ?? false);
            $validated['turnstile_withdrawal'] = (bool) ($validated['turnstile_withdrawal'] ?? false);
            $validated['turnstile_affiliate_app'] = (bool) ($validated['turnstile_affiliate_app'] ?? false);

            // Page Loader
            $validated['page_loader_enabled'] = (bool) ($validated['page_loader_enabled'] ?? false);
        } else {
            // Form Data: ใช้ $request->has()
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
        }

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
        $turnstileKeys = ['turnstile_enabled', 'turnstile_site_key', 'turnstile_secret_key', 'turnstile_bypass_admin',
                          'turnstile_theme', 'turnstile_size', 'turnstile_login', 'turnstile_register',
                          'turnstile_password_change', 'turnstile_profile_update', 'turnstile_withdrawal', 'turnstile_affiliate_app'];

        $hasTurnstileChanges = !empty(array_intersect_key($validated, array_flip($turnstileKeys)));

        if ($hasTurnstileChanges) {
            // Update .env file
            $this->updateEnvFile($validated);
        }

        // ส่ง JSON response ถ้าเป็น JSON request
        if ($isJsonRequest) {
            return response()->json([
                'success' => true,
                'message' => 'บันทึกการตั้งค่าเรียบร้อยแล้ว',
            ]);
        }

        return back()->with('success', 'บันทึกการตั้งค่าเรียบร้อยแล้ว');
    }

    /**
     * Update .env file with Turnstile settings
     *
     * @param array $validated ข้อมูลที่ validated แล้ว
     */
    protected function updateEnvFile(array $validated)
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            return;
        }

        $envContent = file_get_contents($envPath);

        // Turnstile settings to update
        $envSettings = [
            'CLOUDFLARE_TURNSTILE_ENABLED' => ($validated['turnstile_enabled'] ?? false) ? 'true' : 'false',
            'CLOUDFLARE_TURNSTILE_SITE_KEY' => $validated['turnstile_site_key'] ?? '',
            'CLOUDFLARE_TURNSTILE_SECRET_KEY' => $validated['turnstile_secret_key'] ?? '',
            'CLOUDFLARE_TURNSTILE_BYPASS_ADMIN' => ($validated['turnstile_bypass_admin'] ?? false) ? 'true' : 'false',
            'CLOUDFLARE_TURNSTILE_THEME' => $validated['turnstile_theme'] ?? 'auto',
            'CLOUDFLARE_TURNSTILE_SIZE' => $validated['turnstile_size'] ?? 'normal',
            'CLOUDFLARE_TURNSTILE_LOGIN' => ($validated['turnstile_login'] ?? false) ? 'true' : 'false',
            'CLOUDFLARE_TURNSTILE_REGISTER' => ($validated['turnstile_register'] ?? false) ? 'true' : 'false',
            'CLOUDFLARE_TURNSTILE_PASSWORD_CHANGE' => ($validated['turnstile_password_change'] ?? false) ? 'true' : 'false',
            'CLOUDFLARE_TURNSTILE_PROFILE_UPDATE' => ($validated['turnstile_profile_update'] ?? false) ? 'true' : 'false',
            'CLOUDFLARE_TURNSTILE_WITHDRAWAL' => ($validated['turnstile_withdrawal'] ?? false) ? 'true' : 'false',
            'CLOUDFLARE_TURNSTILE_AFFILIATE_APP' => ($validated['turnstile_affiliate_app'] ?? false) ? 'true' : 'false',
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

    /**
     * แสดงหน้าการตั้งค่า Google Maps (V3)
     *
     * @return \Illuminate\View\View
     */
    public function googleMaps()
    {
        // ดึงการตั้งค่า Google Maps ทั้งหมด
        $settings = Setting::where('group', 'google_maps')->get()->keyBy('key');

        return view('admin.settings.google-maps', compact('settings'));
    }

    /**
     * อัพเดทการตั้งค่า Google Maps
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function updateGoogleMaps(Request $request)
    {
        // ตรวจสอบว่าเป็น JSON request หรือ form request
        $isJsonRequest = $request->isJson() || $request->header('Content-Type') === 'application/json';

        // Validation rules
        $validated = $request->validate([
            // API Key
            'google_maps_api_key' => ['nullable', 'string', 'max:500'],

            // Enable/Disable Features
            'google_maps_enabled' => ['nullable', 'boolean'],
            'google_maps_geocoding_enabled' => ['nullable', 'boolean'],
            'google_maps_directions_enabled' => ['nullable', 'boolean'],
            'google_maps_distance_matrix_enabled' => ['nullable', 'boolean'],
            'google_maps_places_enabled' => ['nullable', 'boolean'],

            // Cache Settings
            'google_maps_cache_enabled' => ['nullable', 'boolean'],
            'google_maps_cache_ttl' => ['nullable', 'integer', 'min:0', 'max:604800'], // สูงสุด 7 วัน

            // Geocoding Settings
            'google_maps_geocoding_language' => ['nullable', 'string', 'in:th,en'],
            'google_maps_geocoding_region' => ['nullable', 'string', 'in:TH,US,GB'],

            // Directions Settings
            'google_maps_default_mode' => ['nullable', 'string', 'in:driving,walking,bicycling,transit'],
            'google_maps_avoid_tolls' => ['nullable', 'boolean'],
            'google_maps_avoid_highways' => ['nullable', 'boolean'],
            'google_maps_avoid_ferries' => ['nullable', 'boolean'],

            // Distance Settings
            'google_maps_distance_unit' => ['nullable', 'string', 'in:metric,imperial'],

            // Places Settings
            'google_maps_places_default_radius' => ['nullable', 'integer', 'min:100', 'max:50000'],
            'google_maps_places_max_results' => ['nullable', 'integer', 'min:1', 'max:60'],

            // Map Display Settings
            'google_maps_default_zoom' => ['nullable', 'integer', 'min:1', 'max:21'],
            'google_maps_default_center_lat' => ['nullable', 'string', 'regex:/^-?\d+\.?\d*$/'],
            'google_maps_default_center_lng' => ['nullable', 'string', 'regex:/^-?\d+\.?\d*$/'],
            'google_maps_map_type' => ['nullable', 'string', 'in:roadmap,satellite,hybrid,terrain'],

            // Delivery Settings
            'google_maps_delivery_max_distance' => ['nullable', 'integer', 'min:1', 'max:500'],
            'google_maps_delivery_cost_per_km' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'google_maps_delivery_base_fee' => ['nullable', 'numeric', 'min:0', 'max:10000'],

            // Rate Limiting
            'google_maps_rate_limit_enabled' => ['nullable', 'boolean'],
            'google_maps_rate_limit_per_minute' => ['nullable', 'integer', 'min:1', 'max:1000'],

            // Error Handling
            'google_maps_fallback_enabled' => ['nullable', 'boolean'],
            'google_maps_fallback_calculation' => ['nullable', 'string', 'in:haversine'],
        ]);

        // จัดการ checkbox values
        $booleanFields = [
            'google_maps_enabled',
            'google_maps_geocoding_enabled',
            'google_maps_directions_enabled',
            'google_maps_distance_matrix_enabled',
            'google_maps_places_enabled',
            'google_maps_cache_enabled',
            'google_maps_avoid_tolls',
            'google_maps_avoid_highways',
            'google_maps_avoid_ferries',
            'google_maps_rate_limit_enabled',
            'google_maps_fallback_enabled',
        ];

        if ($isJsonRequest) {
            // JSON: ใช้ค่าที่ส่งมาโดยตรง (true/false)
            foreach ($booleanFields as $field) {
                $validated[$field] = (bool) ($validated[$field] ?? false);
            }
        } else {
            // Form Data: ใช้ $request->has()
            foreach ($booleanFields as $field) {
                $validated[$field] = $request->has($field);
            }
        }

        // บันทึกการตั้งค่าลง database
        foreach ($validated as $key => $value) {
            if ($value !== null) {
                // กำหนดประเภทข้อมูล
                $type = 'string';
                if (in_array($key, $booleanFields)) {
                    $type = 'boolean';
                } elseif (in_array($key, ['google_maps_cache_ttl', 'google_maps_places_default_radius', 'google_maps_places_max_results', 'google_maps_default_zoom', 'google_maps_delivery_max_distance', 'google_maps_rate_limit_per_minute'])) {
                    $type = 'integer';
                } elseif (in_array($key, ['google_maps_delivery_cost_per_km', 'google_maps_delivery_base_fee'])) {
                    $type = 'float';
                }

                Setting::set($key, $value, $type, 'google_maps');
            }
        }

        // อัพเดท .env file สำหรับ API Key
        if (isset($validated['google_maps_api_key'])) {
            $this->updateGoogleMapsEnv($validated);
        }

        // Clear cache เพื่อให้ GoogleMapsService โหลดค่าใหม่
        \Artisan::call('cache:clear');

        if ($isJsonRequest) {
            return response()->json([
                'success' => true,
                'message' => 'บันทึกการตั้งค่า Google Maps เรียบร้อยแล้ว',
            ]);
        }

        return back()->with('success', 'บันทึกการตั้งค่า Google Maps เรียบร้อยแล้ว');
    }

    /**
     * ทดสอบการเชื่อมต่อ Google Maps API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testGoogleMapsConnection()
    {
        try {
            $service = app(\App\Services\GoogleMapsService::class);

            // ทดสอบ geocoding ด้วยที่อยู่กรุงเทพฯ
            $result = $service->geocode('กรุงเทพมหานคร');

            return response()->json([
                'success' => true,
                'message' => 'เชื่อมต่อ Google Maps API สำเร็จ!',
                'data' => [
                    'address' => $result['formatted_address'] ?? 'N/A',
                    'location' => $result['location'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เชื่อมต่อ Google Maps API ไม่สำเร็จ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * คำนวณระยะทางระหว่าง 2 จุด (สำหรับทดสอบ)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculateDistance(Request $request)
    {
        $validated = $request->validate([
            'origin_lat' => ['required', 'numeric'],
            'origin_lng' => ['required', 'numeric'],
            'dest_lat' => ['required', 'numeric'],
            'dest_lng' => ['required', 'numeric'],
            'mode' => ['nullable', 'string', 'in:driving,walking,bicycling,transit'],
        ]);

        try {
            $service = app(\App\Services\GoogleMapsService::class);

            $origin = [
                'lat' => $validated['origin_lat'],
                'lng' => $validated['origin_lng'],
            ];

            $destination = [
                'lat' => $validated['dest_lat'],
                'lng' => $validated['dest_lng'],
            ];

            $result = $service->getDirections($origin, $destination, $validated['mode'] ?? 'driving');

            return response()->json([
                'success' => true,
                'message' => 'คำนวณระยะทางสำเร็จ',
                'data' => [
                    'distance' => $result['distance'],
                    'duration' => $result['duration'],
                    'start_address' => $result['start_address'],
                    'end_address' => $result['end_address'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'คำนวณระยะทางไม่สำเร็จ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * อัพเดท .env file สำหรับ Google Maps settings
     *
     * @param array $validated ข้อมูลที่ validated แล้ว
     * @return void
     */
    protected function updateGoogleMapsEnv(array $validated): void
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            return;
        }

        $envContent = file_get_contents($envPath);

        // Google Maps settings to update
        $envSettings = [
            'GOOGLE_MAPS_API_KEY' => $validated['google_maps_api_key'] ?? '',
        ];

        foreach ($envSettings as $key => $value) {
            // ตรวจสอบว่า key มีอยู่ใน .env หรือไม่
            if (preg_match("/^{$key}=/m", $envContent)) {
                // อัพเดทค่าเดิม
                $envContent = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $envContent
                );
            } else {
                // เพิ่ม key ใหม่
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);
    }

    /**
     * แสดงหน้าคำแนะนำการตั้งค่า Google Maps API
     *
     * @return \Illuminate\View\View
     */
    public function googleMapsGuide()
    {
        return view('admin.settings.google-maps-guide');
    }
}
