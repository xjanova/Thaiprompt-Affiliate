<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ThemeSetting;
use App\Models\ThemeColor;
use App\Models\ThemeRgbEffect;
use App\Models\ThemeTypography;
use App\Models\ThemeComponent;
use App\Models\ThemePreset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * ArrowXThemeController
 *
 * จัดการ Arrow X Theme System ในระบบ Admin
 */
class ArrowXThemeController extends Controller
{
    /**
     * แสดงหน้า Dashboard ของ Arrow X Theme
     *
     * @return View
     */
    public function index(): View
    {
        $themeSetting = ThemeSetting::with([
            'color',
            'typography',
            'rgbEffects',
            'components'
        ])->where('is_active', true)->first();

        // ถ้ายังไม่มี theme setting ให้สร้างใหม่
        if (!$themeSetting) {
            $themeSetting = $this->createDefaultTheme();
        }

        return view('admin.arrow-x-theme.index', [
            'themeSetting' => $themeSetting,
            'pageTitle' => 'Arrow X Theme System',
        ]);
    }

    /**
     * แสดงหน้าตั้งค่าหลัก (General Settings)
     *
     * @return View
     */
    public function generalSettings(): View
    {
        $themeSetting = ThemeSetting::active() ?? $this->createDefaultTheme();

        return view('admin.arrow-x-theme.general-settings', [
            'themeSetting' => $themeSetting,
            'pageTitle' => 'การตั้งค่าทั่วไป - Arrow X',
        ]);
    }

    /**
     * บันทึกการตั้งค่าหลัก
     *
     * @param Request $request
     * @param \App\Services\ImageUploadService $imageUploadService
     * @return RedirectResponse
     */
    public function updateGeneralSettings(Request $request, \App\Services\ImageUploadService $imageUploadService): RedirectResponse
    {
        $validated = $request->validate([
            'theme_name' => 'required|string|max:100',
            'brand_name' => 'required|string|max:100',
            'brand_tagline' => 'nullable|string|max:255',
            'layout_type' => 'required|in:fixed,fluid,boxed',
            'sidebar_width' => 'required|integer|min:200|max:400',
            'navbar_height' => 'required|integer|min:50|max:100',
            'footer_height' => 'required|integer|min:60|max:150',
            'global_opacity' => 'required|integer|min:0|max:100',
            'sidebar_opacity' => 'required|integer|min:0|max:100',
            'navbar_opacity' => 'required|integer|min:0|max:100',
            'card_opacity' => 'required|integer|min:0|max:100',
            'modal_opacity' => 'required|integer|min:0|max:100',
            'card_blur_intensity' => 'required|integer|min:0|max:20',
            'card_border_width' => 'required|integer|min:0|max:10',
            'card_border_radius' => 'required|integer|min:0|max:50',
            'card_shadow_intensity' => 'required|in:none,sm,md,lg,xl,2xl',

            // Background Effects
            'bg_effects_enabled' => 'boolean',
            'bg_circle1_color1' => 'nullable|string|max:7',
            'bg_circle1_color2' => 'nullable|string|max:7',
            'bg_circle2_color1' => 'nullable|string|max:7',
            'bg_circle2_color2' => 'nullable|string|max:7',
            'bg_circle3_color1' => 'nullable|string|max:7',
            'bg_circle3_color2' => 'nullable|string|max:7',
            'bg_animation_speed' => 'nullable|in:slow,normal,fast',
            'bg_circle_opacity' => 'nullable|integer|min:0|max:100',
            'bg_circle_blur' => 'nullable|integer|min:0|max:200',
            'bg_circle_size' => 'nullable|integer|min:200|max:800',

            // Logo & Favicon (แยกจาก SiteSetting logo)
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048', // 2MB
            'footer_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048', // 2MB
            'footer_logo_animation' => 'nullable|in:none,float,spin,bounce,pulse,swing',
            'favicon' => 'nullable|image|mimes:jpeg,png,gif,webp,ico|max:1024', // 1MB
        ]);

        $themeSetting = ThemeSetting::active() ?? $this->createDefaultTheme();

        // Handle Theme Logo Upload (สำหรับแสดงใน Sidebar)
        if ($request->hasFile('logo')) {
            // ลบโลโก้ธีมเก่า (ถ้ามี)
            if ($themeSetting->logo_path) {
                $imageUploadService->deleteImage($themeSetting->logo_path);
            }

            // อัพโหลดโลโก้ธีมใหม่ (max 200x200, quality 90)
            $validated['logo_path'] = $imageUploadService->uploadImage(
                $request->file('logo'),
                'theme-logos',
                200,
                200,
                90
            );
        }

        // Handle Footer Logo Upload (โลโก้มุมล่างซ้าย Sidebar)
        if ($request->hasFile('footer_logo')) {
            // ลบโลโก้ footer เก่า (ถ้ามี)
            if ($themeSetting->footer_logo_path) {
                $imageUploadService->deleteImage($themeSetting->footer_logo_path);
            }

            // อัพโหลดโลโก้ footer ใหม่ (max 150x150, quality 90)
            $validated['footer_logo_path'] = $imageUploadService->uploadImage(
                $request->file('footer_logo'),
                'theme-logos',
                150,
                150,
                90
            );
        }

        // Handle Theme Favicon Upload
        if ($request->hasFile('favicon')) {
            // ลบ favicon ธีมเก่า (ถ้ามี)
            if ($themeSetting->favicon_path) {
                $imageUploadService->deleteImage($themeSetting->favicon_path);
            }

            // อัพโหลด favicon ธีมใหม่ (max 64x64, quality 90)
            $validated['favicon_path'] = $imageUploadService->uploadImage(
                $request->file('favicon'),
                'theme-favicons',
                64,
                64,
                90
            );
        }

        // แปลง boolean checkbox values
        $validated['bg_effects_enabled'] = $request->has('bg_effects_enabled');

        $themeSetting->update($validated);

        // ล้าง theme cache และ recompile เพื่อให้การเปลี่ยนแปลงมีผลทันที
        try {
            $compilerService = app(\App\Services\ThemeCompilerService::class);
            $compilerService->clearCache();

            // Recompile theme ด้วย settings ใหม่
            $compilerService->compile($themeSetting);
        } catch (\Exception $e) {
            // Log error but don't fail the update
            \Log::warning('Failed to clear/recompile theme cache: ' . $e->getMessage());
        }

        return redirect()
            ->back()
            ->with('success', 'บันทึกการตั้งค่าทั่วไปสำเร็จ - Theme ถูก compile ใหม่แล้ว');
    }

    /**
     * แสดงหน้าตั้งค่าสี (Color Settings)
     *
     * @return View
     */
    public function colorSettings(): View
    {
        $themeSetting = ThemeSetting::active() ?? $this->createDefaultTheme();
        $color = $themeSetting->color;

        return view('admin.arrow-x-theme.color-settings', [
            'themeSetting' => $themeSetting,
            'color' => $color,
            'pageTitle' => 'ตั้งค่าสี - Arrow X',
        ]);
    }

    /**
     * บันทึกการตั้งค่าสี
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateColorSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'scheme_name' => 'required|string|max:100',
            'primary_start' => 'required|string|max:7',
            'primary_middle' => 'required|string|max:7',
            'primary_end' => 'required|string|max:7',
            'secondary_start' => 'required|string|max:7',
            'secondary_middle' => 'required|string|max:7',
            'secondary_end' => 'required|string|max:7',
            'accent_color' => 'required|string|max:7',
            'success_color' => 'required|string|max:7',
            'warning_color' => 'required|string|max:7',
            'error_color' => 'required|string|max:7',
            'info_color' => 'required|string|max:7',
            'gradient_direction' => 'required|in:to-right,to-left,to-top,to-bottom,to-top-right,to-top-left,to-bottom-right,to-bottom-left',
        ]);

        $themeSetting = ThemeSetting::active() ?? $this->createDefaultTheme();

        if ($themeSetting->color) {
            $themeSetting->color->update($validated);
        } else {
            $themeSetting->color()->create(array_merge($validated, [
                'theme_setting_id' => $themeSetting->id,
            ]));
        }

        return redirect()
            ->back()
            ->with('success', 'บันทึกการตั้งค่าสีสำเร็จ');
    }

    /**
     * แสดงหน้าตั้งค่า RGB Effects
     *
     * @return View
     */
    public function rgbEffects(): View
    {
        $themeSetting = ThemeSetting::active() ?? $this->createDefaultTheme();
        $rgbEffects = $themeSetting->rgbEffects;

        return view('admin.arrow-x-theme.rgb-effects', [
            'themeSetting' => $themeSetting,
            'rgbEffects' => $rgbEffects,
            'pageTitle' => 'RGB Effects - Arrow X',
        ]);
    }

    /**
     * สร้าง RGB Effect ใหม่
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function storeRgbEffect(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'effect_name' => 'required|string|max:100',
            'target_element' => 'required|string',
            'custom_selector' => 'nullable|string|max:255',
            'trigger_state' => 'required|in:always,hover,active,focus,click',
            'animation_type' => 'required|in:rainbow,wave,pulse,glow,breathing,slide,rotate,flash,static',
            'rgb_colors' => 'required|array',
            'rgb_colors.*' => 'required|string|max:7',
            'animation_duration' => 'required|integer|min:100|max:10000',
            'animation_timing' => 'required|in:linear,ease,ease-in,ease-out,ease-in-out',
            'intensity' => 'required|in:subtle,medium,strong,extreme',
            'blur_radius' => 'required|integer|min:0|max:50',
            'delay' => 'required|integer|min:0|max:5000',
            'iteration_count' => 'required|string|max:20',
            'is_enabled' => 'boolean',
        ]);

        $themeSetting = ThemeSetting::active() ?? $this->createDefaultTheme();

        $themeSetting->rgbEffects()->create($validated);

        return redirect()
            ->back()
            ->with('success', 'สร้าง RGB Effect สำเร็จ');
    }

    /**
     * อัปเดท RGB Effect
     *
     * @param Request $request
     * @param ThemeRgbEffect $rgbEffect
     * @return RedirectResponse
     */
    public function updateRgbEffect(Request $request, ThemeRgbEffect $rgbEffect): RedirectResponse
    {
        $validated = $request->validate([
            'effect_name' => 'required|string|max:100',
            'target_element' => 'required|string',
            'custom_selector' => 'nullable|string|max:255',
            'trigger_state' => 'required|in:always,hover,active,focus,click',
            'animation_type' => 'required|in:rainbow,wave,pulse,glow,breathing,slide,rotate,flash,static',
            'rgb_colors' => 'required|array',
            'rgb_colors.*' => 'required|string|max:7',
            'animation_duration' => 'required|integer|min:100|max:10000',
            'animation_timing' => 'required|in:linear,ease,ease-in,ease-out,ease-in-out',
            'intensity' => 'required|in:subtle,medium,strong,extreme',
            'blur_radius' => 'required|integer|min:0|max:50',
            'delay' => 'required|integer|min:0|max:5000',
            'iteration_count' => 'required|string|max:20',
            'is_enabled' => 'boolean',
        ]);

        $rgbEffect->update($validated);

        return redirect()
            ->back()
            ->with('success', 'อัปเดท RGB Effect สำเร็จ');
    }

    /**
     * ลบ RGB Effect
     *
     * @param ThemeRgbEffect $rgbEffect
     * @return RedirectResponse
     */
    public function destroyRgbEffect(ThemeRgbEffect $rgbEffect): RedirectResponse
    {
        $rgbEffect->delete();

        return redirect()
            ->back()
            ->with('success', 'ลบ RGB Effect สำเร็จ');
    }

    /**
     * แสดงหน้าตั้งค่า Typography
     *
     * @return View
     */
    public function typography(): View
    {
        $themeSetting = ThemeSetting::active() ?? $this->createDefaultTheme();
        $typography = $themeSetting->typography;

        return view('admin.arrow-x-theme.typography', [
            'themeSetting' => $themeSetting,
            'typography' => $typography,
            'pageTitle' => 'ตั้งค่าฟอนต์ - Arrow X',
        ]);
    }

    /**
     * บันทึกการตั้งค่า Typography
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateTypography(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'primary_font' => 'required|string|max:100',
            'secondary_font' => 'required|string|max:100',
            'code_font' => 'required|string|max:100',
            'base_font_size' => 'required|numeric|min:0.5|max:3',
            'heading_h1_size' => 'required|numeric|min:0.5|max:5',
            'heading_h2_size' => 'required|numeric|min:0.5|max:5',
            'heading_h3_size' => 'required|numeric|min:0.5|max:5',
            'heading_h4_size' => 'required|numeric|min:0.5|max:5',
            'heading_h5_size' => 'required|numeric|min:0.5|max:5',
            'heading_h6_size' => 'required|numeric|min:0.5|max:5',
            'heading_line_height' => 'required|numeric|min:0.5|max:3',
            'body_line_height' => 'required|numeric|min:0.5|max:3',
        ]);

        $themeSetting = ThemeSetting::active() ?? $this->createDefaultTheme();

        if ($themeSetting->typography) {
            $themeSetting->typography->update($validated);
        } else {
            $themeSetting->typography()->create(array_merge($validated, [
                'theme_setting_id' => $themeSetting->id,
            ]));
        }

        return redirect()
            ->back()
            ->with('success', 'บันทึกการตั้งค่าฟอนต์สำเร็จ');
    }

    /**
     * Upload Logo
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function uploadLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,svg|max:2048',
        ]);

        $themeSetting = ThemeSetting::active() ?? $this->createDefaultTheme();

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = 'arrow-x-logo-' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('theme/logos', $filename, 'public');

            $themeSetting->update(['logo_path' => $path]);
        }

        return redirect()
            ->back()
            ->with('success', 'อัปโหลด Logo สำเร็จ');
    }

    /**
     * Upload Favicon
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function uploadFavicon(Request $request): RedirectResponse
    {
        $request->validate([
            'favicon' => 'required|image|mimes:ico,png|max:512',
        ]);

        $themeSetting = ThemeSetting::active() ?? $this->createDefaultTheme();

        if ($request->hasFile('favicon')) {
            $file = $request->file('favicon');
            $filename = 'arrow-x-favicon-' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('theme/favicons', $filename, 'public');

            $themeSetting->update(['favicon_path' => $path]);
        }

        return redirect()
            ->back()
            ->with('success', 'อัปโหลด Favicon สำเร็จ');
    }

    /**
     * สร้าง Default Theme (ถ้ายังไม่มี)
     *
     * @return ThemeSetting
     */
    private function createDefaultTheme(): ThemeSetting
    {
        return DB::transaction(function () {
            // สร้าง theme setting
            $themeSetting = ThemeSetting::create([
                'theme_name' => 'Arrow X',
                'theme_version' => '1.0.0',
                'is_active' => true,
                'brand_name' => 'TP-Affiliate',
                'layout_type' => 'fluid',
                'sidebar_width' => 260,
                'navbar_height' => 64,
                'footer_height' => 80,
                'global_opacity' => 100,
                'sidebar_opacity' => 100,
                'navbar_opacity' => 100,
                'card_opacity' => 100,
                'modal_opacity' => 95,
                'card_blur_intensity' => 0,
                'card_border_width' => 1,
                'card_border_radius' => 16,
                'card_shadow_intensity' => 'lg',
                'default_language' => 'th',
                'available_languages' => ['th', 'en'],
                'rtl_enabled' => false,
            ]);

            // สร้าง theme color
            $themeSetting->color()->create([
                'scheme_name' => 'Arrow X Default',
                'is_default' => true,
                'primary_start' => '#9333EA',
                'primary_middle' => '#EC4899',
                'primary_end' => '#F97316',
                'secondary_start' => '#3B82F6',
                'secondary_middle' => '#06B6D4',
                'secondary_end' => '#14B8A6',
                'accent_color' => '#F59E0B',
                'success_color' => '#10B981',
                'warning_color' => '#F59E0B',
                'error_color' => '#EF4444',
                'info_color' => '#3B82F6',
                'gradient_direction' => 'to-right',
            ]);

            // สร้าง typography
            $themeSetting->typography()->create([
                'primary_font' => 'Inter',
                'secondary_font' => 'Noto Sans Thai',
                'code_font' => 'Fira Code',
                'base_font_size' => 1.00,
                'heading_h1_size' => 2.50,
                'heading_h2_size' => 2.00,
                'heading_h3_size' => 1.75,
                'heading_h4_size' => 1.50,
                'heading_h5_size' => 1.25,
                'heading_h6_size' => 1.00,
                'heading_line_height' => 1.20,
                'body_line_height' => 1.60,
            ]);

            return $themeSetting;
        });
    }

    /**
     * Apply theme preset
     *
     * ใช้ theme preset ที่เลือกไปกับ theme setting ที่ active อยู่
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function applyPreset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'preset_id' => 'required|exists:theme_presets,id',
        ]);

        $preset = ThemePreset::findOrFail($validated['preset_id']);
        $themeSetting = ThemeSetting::active() ?? $this->createDefaultTheme();

        // อัพเดทหรือสร้าง ThemeColor
        $color = $themeSetting->color;

        if ($color) {
            // อัพเดทสีจาก preset (ใช้ทุกฟิลด์)
            $color->update([
                'scheme_name' => $preset->display_name,
                'primary_start' => $preset->colors['primary_start'],
                'primary_middle' => $preset->colors['primary_middle'],
                'primary_end' => $preset->colors['primary_end'],
                'secondary_start' => $preset->colors['secondary_start'] ?? '#3B82F6',
                'secondary_middle' => $preset->colors['secondary_middle'] ?? '#06B6D4',
                'secondary_end' => $preset->colors['secondary_end'] ?? '#14B8A6',
                'accent_color' => $preset->colors['accent_color'] ?? '#F59E0B',
                'success_color' => $preset->colors['success_color'],
                'warning_color' => $preset->colors['warning_color'],
                'error_color' => $preset->colors['error_color'],
                'info_color' => $preset->colors['info_color'],
                'gradient_direction' => $preset->colors['gradient_direction'] ?? 'to-right',
            ]);
        } else {
            // สร้าง ThemeColor ใหม่
            $themeSetting->color()->create([
                'theme_setting_id' => $themeSetting->id,
                'scheme_name' => $preset->display_name,
                'primary_start' => $preset->colors['primary_start'],
                'primary_middle' => $preset->colors['primary_middle'],
                'primary_end' => $preset->colors['primary_end'],
                'secondary_start' => $preset->colors['secondary_start'] ?? '#3B82F6',
                'secondary_middle' => $preset->colors['secondary_middle'] ?? '#06B6D4',
                'secondary_end' => $preset->colors['secondary_end'] ?? '#14B8A6',
                'accent_color' => $preset->colors['accent_color'] ?? '#F59E0B',
                'success_color' => $preset->colors['success_color'],
                'warning_color' => $preset->colors['warning_color'],
                'error_color' => $preset->colors['error_color'],
                'info_color' => $preset->colors['info_color'],
                'gradient_direction' => $preset->colors['gradient_direction'] ?? 'to-right',
            ]);
        }

        // เพิ่ม usage count
        $preset->increment('usage_count');

        return redirect()
            ->back()
            ->with('success', "ใช้ Theme Preset \"{$preset->display_name}\" สำเร็จ");
    }

    /**
     * Compile theme และ refresh cache
     *
     * @return RedirectResponse
     */
    public function compileTheme(): RedirectResponse
    {
        try {
            $compilerService = app(\App\Services\ThemeCompilerService::class);
            $themeSetting = ThemeSetting::active();

            if (!$themeSetting) {
                return redirect()->back()->with('error', 'ไม่พบ active theme');
            }

            // Compile และ refresh cache
            $compiled = $compilerService->compile($themeSetting, true);

            $cssSize = number_format(strlen($compiled['css']));
            $jsSize = number_format(strlen($compiled['js']));

            return redirect()->back()->with('success', "Compile สำเร็จ! (CSS: {$cssSize} bytes, JS: {$jsSize} bytes)");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Compile ล้มเหลว: ' . $e->getMessage());
        }
    }

    /**
     * Clear theme cache
     *
     * @return RedirectResponse
     */
    public function clearCache(): RedirectResponse
    {
        try {
            $compilerService = app(\App\Services\ThemeCompilerService::class);
            $compilerService->clearCache();

            return redirect()->back()->with('success', 'ล้าง cache สำเร็จ!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'ล้าง cache ล้มเหลว: ' . $e->getMessage());
        }
    }

    /**
     * Compile theme เป็น static files
     *
     * @return RedirectResponse
     */
    public function compileToFiles(): RedirectResponse
    {
        try {
            $compilerService = app(\App\Services\ThemeCompilerService::class);
            $themeSetting = ThemeSetting::active();

            if (!$themeSetting) {
                return redirect()->back()->with('error', 'ไม่พบ active theme');
            }

            $files = $compilerService->compileToFile($themeSetting);

            return redirect()->back()->with('success', 'สร้าง static files สำเร็จ!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'สร้าง static files ล้มเหลว: ' . $e->getMessage());
        }
    }
}
