<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\TwoFactorUserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Settings Controller สำหรับจัดการการตั้งค่าของ User
 */
class SettingsController extends Controller
{
    /**
     * แสดงหน้าการตั้งค่าของ User
     *
     * 🐛 (2026-11-02) หน้านี้เดิมมีสวิตช์แจ้งเตือน/ความเป็นส่วนตัว/ธีม/เขตเวลา/สกุลเงิน ที่ "บันทึกแล้วไม่ติด"
     *    ตาราง users ไม่มีคอลัมน์เหล่านั้น และไม่อยู่ใน $fillable → update() ทิ้งค่าเงียบๆ ทุกครั้ง
     *    ตอนนี้เหลือเฉพาะค่าที่บันทึกได้จริง (ภาษา) ส่วนที่เหลือชี้ไปหน้าที่ทำงานจริง
     *    (ตั้งค่าอีเมล · การแจ้งเตือน · 2FA) และสถานะ 2FA อ่านจาก two_factor_user_settings
     *    (เดิมอ่าน $user->two_factor_enabled ที่ไม่มีอยู่จริง = ขึ้น "ยังไม่เปิด" ให้ทุกคน)
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();

        $twoFactor = TwoFactorUserSetting::where('user_id', $user->id)->first();

        // ดึงข้อมูลการตั้งค่าปัจจุบัน
        $settings = [
            // ตั้งค่าทั่วไป (บันทึกได้จริง — SetLocale อ่านค่านี้)
            'preferences' => [
                'language' => $user->preferred_language ?: 'th',
            ],

            // ความปลอดภัย
            'security' => [
                'two_factor_enabled' => (bool) ($twoFactor?->enabled),
                'two_factor_method' => $twoFactor?->enabled ? $twoFactor->preferred_method : null,
                'active_sessions_count' => $this->getActiveSessionsCount(),
            ],
        ];

        return view('user.settings-arrow-x', compact('user', 'settings'));
    }

    /**
     * อัปเดตการตั้งค่าของ User
     *
     * บันทึกได้เฉพาะภาษา — ช่องเก่า (แจ้งเตือน/ความเป็นส่วนตัว/ธีม ฯลฯ) ไม่มีคอลัมน์รองรับ จึงไม่รับอีกต่อไป
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'preferred_language' => 'required|in:th,en',
        ], [
            'preferred_language.required' => 'กรุณาเลือกภาษา',
            'preferred_language.in' => 'ภาษาที่เลือกไม่ถูกต้อง',
        ]);

        $user->update($validated);

        // เปลี่ยนภาษาของ session ทันที (SetLocale อ่าน session ก่อนค่าในบัญชี)
        session(['locale' => $validated['preferred_language']]);

        return redirect()
            ->route('user.settings')
            ->with('success', 'บันทึกการตั้งค่าเรียบร้อยแล้ว');
    }

    /**
     * นับจำนวน active sessions ของ user
     */
    private function getActiveSessionsCount(): int
    {
        try {
            if (\Schema::hasTable('sessions')) {
                return DB::table('sessions')
                    ->where('user_id', Auth::id())
                    ->count();
            }
        } catch (\Exception $e) {
            // ถ้า sessions table ไม่มีหรือ error ให้ return 1
        }

        return 1;
    }

    /**
     * ลบ session ที่ระบุ
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteSession(Request $request, string $sessionId)
    {
        try {
            if (\Schema::hasTable('sessions')) {
                DB::table('sessions')
                    ->where('id', $sessionId)
                    ->where('user_id', Auth::id())
                    ->delete();
            }
        } catch (\Exception $e) {
            return redirect()
                ->route('user.settings')
                ->with('error', 'ไม่สามารถลบ session ได้');
        }

        return redirect()
            ->route('user.settings')
            ->with('success', 'ลบ session เรียบร้อยแล้ว');
    }

    /**
     * ลบ sessions ทั้งหมด ยกเว้น session ปัจจุบัน
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteOtherSessions(Request $request)
    {
        try {
            if (\Schema::hasTable('sessions')) {
                DB::table('sessions')
                    ->where('user_id', Auth::id())
                    ->where('id', '!=', $request->session()->getId())
                    ->delete();
            }
        } catch (\Exception $e) {
            return redirect()
                ->route('user.settings')
                ->with('error', 'ไม่สามารถลบ sessions อื่นๆ ได้');
        }

        return redirect()
            ->route('user.settings')
            ->with('success', 'ลบ sessions อื่นๆ เรียบร้อยแล้ว');
    }
}
