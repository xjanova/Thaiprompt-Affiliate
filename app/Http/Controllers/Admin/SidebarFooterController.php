<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ThemeSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * SidebarFooterController
 *
 * จัดการ footer content ของ sidebar ที่โหลดผ่าน iframe
 *
 * ประโยชน์:
 * - Real-time update version/license status
 * - แสดง promo/announcement แบบ dynamic
 * - โหลด content แยกจาก sidebar หลัก
 *
 * @version 1.0.0
 * @since 2026-01-19
 */
class SidebarFooterController extends Controller
{
    /**
     * แสดง footer content สำหรับ sidebar
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        // ตรวจสอบว่าเป็น compact mode หรือไม่
        $isCompact = $request->query('compact') === '1';

        // ดึง theme setting
        $themeSetting = ThemeSetting::active();

        // อ่าน version จากไฟล์
        $version = file_exists(base_path('VERSION'))
            ? trim(file_get_contents(base_path('VERSION')))
            : '3.0.0';

        // ตรวจสอบ license status (อาจมีการเรียก API หรือ check database)
        $licenseStatus = $this->checkLicenseStatus();

        // Logo animation
        $logoAnimation = $themeSetting->footer_logo_animation ?? 'float';
        $animationClass = match($logoAnimation) {
            'none' => '',
            'float' => 'animate-float',
            'spin' => 'animate-spin-slow',
            'bounce' => 'animate-bounce',
            'pulse' => 'animate-pulse',
            'swing' => 'animate-swing',
            default => 'animate-float'
        };

        return view('admin.sidebar.footer', [
            'isCompact' => $isCompact,
            'themeSetting' => $themeSetting,
            'version' => $version,
            'licenseStatus' => $licenseStatus,
            'animationClass' => $animationClass,
        ]);
    }

    /**
     * ตรวจสอบสถานะ license
     *
     * @return array
     */
    protected function checkLicenseStatus(): array
    {
        // TODO: Implement actual license checking
        // สามารถเชื่อมต่อกับ license server หรือ check local database

        return [
            'active' => true,
            'type' => 'PREMIUM',
            'expires_at' => null, // null = lifetime
            'status_text' => 'LICENSED',
            'status_color' => 'emerald',
        ];
    }

    /**
     * Reload footer (สำหรับ manual refresh)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reload(Request $request)
    {
        $isCompact = $request->query('compact') === '1';

        return response()->json([
            'success' => true,
            'timestamp' => now()->toIso8601String(),
            'compact' => $isCompact,
        ]);
    }
}
