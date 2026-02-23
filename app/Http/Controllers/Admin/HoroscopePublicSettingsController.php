<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneTellingSetting;
use Illuminate\Http\Request;

/**
 * HoroscopePublicSettingsController — ตั้งค่าระบบดูดวงสาธารณะ
 *
 * จัดการ:
 * - เปิด/ปิด ระบบดูดวงสาธารณะ
 * - จำกัดการใช้งานฟรีต่อวัน (ดูดวง / ทำนายฝัน / เลขศาสตร์)
 * - SEO title / description
 */
class HoroscopePublicSettingsController extends Controller
{
    /**
     * แสดงหน้าตั้งค่า
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // ดึงตั้งค่าจากระบบ
        $settings = FortuneTellingSetting::first();

        return view('admin.fortune.horoscope-public.settings', [
            'pageTitle' => '⚙️ ตั้งค่าระบบดูดวงสาธารณะ',
            'settings' => $settings,
        ]);
    }

    /**
     * บันทึกการตั้งค่า
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'horoscope_public_enabled' => 'boolean',
            'horoscope_free_daily_limit' => 'required|integer|min:0|max:100',
            'horoscope_dream_free_limit' => 'required|integer|min:0|max:100',
            'horoscope_numerology_free_limit' => 'required|integer|min:0|max:100',
            'horoscope_seo_title_th' => 'nullable|string|max:255',
            'horoscope_seo_description_th' => 'nullable|string|max:1000',
        ]);

        // จัดการ checkbox
        $validated['horoscope_public_enabled'] = $request->boolean('horoscope_public_enabled');

        // ดึง setting record แรก (หรือสร้างใหม่ถ้ายังไม่มี)
        $settings = FortuneTellingSetting::first();

        if ($settings) {
            $settings->update($validated);
        }

        return redirect()
            ->route('admin.fortune.horoscope-public.settings')
            ->with('success', '✅ บันทึกการตั้งค่าสำเร็จ');
    }
}
