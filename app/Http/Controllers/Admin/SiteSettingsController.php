<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;

/**
 * Site Settings Controller
 *
 * จัดการการตั้งค่าเว็บไซต์ (Admin Only)
 *
 * @package App\Http\Controllers\Admin
 */
class SiteSettingsController extends Controller
{
    /**
     * แสดงหน้าการตั้งค่าเว็บไซต์
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // ดึงการตั้งค่าปัจจุบัน (Singleton with Cache)
        $settings = SiteSetting::getSetting();

        return view('admin.site-settings.index', compact('settings'));
    }

    /**
     * อัปเดตการตั้งค่าเว็บไซต์
     *
     * @param Request $request
     * @param ImageUploadService $imageUploadService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, ImageUploadService $imageUploadService)
    {
        // Validate input
        $validated = $request->validate([
            // General Settings
            'site_name' => 'required|string|max:255',
            'site_description' => 'nullable|string',

            // Logo & Favicon (จะแปลงเป็น WebP อัตโนมัติ)
            'logo' => 'nullable|image|mimes:jpeg,png,gif,webp,svg|max:2048', // 2MB
            'logo_dark' => 'nullable|image|mimes:jpeg,png,gif,webp,svg|max:2048',
            'logo_spin' => 'nullable|boolean',
            'favicon' => 'nullable|image|mimes:jpeg,png,gif,webp,ico|max:1024', // 1MB

            // SEO Settings
            'meta_keywords' => 'nullable|string',
            'meta_description' => 'nullable|string',

            // Note: Theme colors managed by ThemeSetting model (Custom Theme)

            // Contact Info
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
            'contact_address' => 'nullable|string|max:500',

            // Social Media
            'facebook_url' => 'nullable|url',
            'twitter_url' => 'nullable|url',
            'instagram_url' => 'nullable|url',
            'line_url' => 'nullable|url',
            'youtube_url' => 'nullable|url',

            // Analytics
            'google_analytics_id' => 'nullable|string',
            'facebook_pixel_id' => 'nullable|string',
            'google_tag_manager_id' => 'nullable|string',

            // Maintenance Mode
            'maintenance_mode' => 'nullable|boolean',
            'maintenance_message' => 'nullable|string',

            // Custom Scripts
            'header_scripts' => 'nullable|string',
            'footer_scripts' => 'nullable|string',
        ]);

        // แปลง boolean fields
        $validated['logo_spin'] = $request->has('logo_spin');
        $validated['maintenance_mode'] = $request->has('maintenance_mode');

        // ดึงการตั้งค่าปัจจุบัน
        $settings = SiteSetting::getSetting();

        // Handle Logo Upload
        if ($request->hasFile('logo')) {
            // ลบโลโก้เก่า (ถ้ามี)
            if ($settings->logo) {
                $imageUploadService->deleteImage($settings->logo);
            }

            // อัพโหลดโลโก้ใหม่ (แปลงเป็น WebP, max 800x800, quality 90)
            $validated['logo'] = $imageUploadService->uploadImage(
                $request->file('logo'),
                'site-settings',
                800,
                800,
                90
            );
        }

        // Handle Logo Dark Upload
        if ($request->hasFile('logo_dark')) {
            // ลบโลโก้ dark เก่า (ถ้ามี)
            if ($settings->logo_dark) {
                $imageUploadService->deleteImage($settings->logo_dark);
            }

            // อัพโหลดโลโก้ dark ใหม่
            $validated['logo_dark'] = $imageUploadService->uploadImage(
                $request->file('logo_dark'),
                'site-settings',
                800,
                800,
                90
            );
        }

        // Handle Favicon Upload
        if ($request->hasFile('favicon')) {
            // ลบ favicon เก่า (ถ้ามี)
            if ($settings->favicon) {
                $imageUploadService->deleteImage($settings->favicon);
            }

            // อัพโหลด favicon ใหม่ (max 256x256, quality 90)
            $validated['favicon'] = $imageUploadService->uploadImage(
                $request->file('favicon'),
                'site-settings',
                256,
                256,
                90
            );
        }

        // อัปเดตการตั้งค่า
        $settings->update($validated);

        // ล้าง cache
        SiteSetting::clearCache();

        return redirect()
            ->route('admin.site-settings.index')
            ->with('success', 'อัปเดตการตั้งค่าเว็บไซต์เรียบร้อยแล้ว');
    }

    /**
     * ลบโลโก้
     *
     * @param Request $request
     * @param ImageUploadService $imageUploadService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteLogo(Request $request, ImageUploadService $imageUploadService)
    {
        $type = $request->input('type', 'logo'); // logo, logo_dark, favicon

        $settings = SiteSetting::getSetting();

        if ($type === 'logo' && $settings->logo) {
            $imageUploadService->deleteImage($settings->logo);
            $settings->update(['logo' => null]);
        } elseif ($type === 'logo_dark' && $settings->logo_dark) {
            $imageUploadService->deleteImage($settings->logo_dark);
            $settings->update(['logo_dark' => null]);
        } elseif ($type === 'favicon' && $settings->favicon) {
            $imageUploadService->deleteImage($settings->favicon);
            $settings->update(['favicon' => null]);
        }

        SiteSetting::clearCache();

        return redirect()
            ->route('admin.site-settings.index')
            ->with('success', 'ลบรูปภาพเรียบร้อยแล้ว');
    }
}
