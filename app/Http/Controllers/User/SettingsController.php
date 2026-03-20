<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
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
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();

        // ดึงข้อมูลการตั้งค่าปัจจุบัน
        $settings = [
            // ตั้งค่าทั่วไป
            'preferences' => [
                'language' => $user->preferred_language ?? 'th',
                'timezone' => $user->timezone ?? 'Asia/Bangkok',
                'currency' => $user->preferred_currency ?? 'THB',
            ],

            // การแจ้งเตือน
            'notifications' => [
                'email_enabled' => $user->email_notifications ?? true,
                'email_commission' => $user->email_commission_notifications ?? true,
                'email_team' => $user->email_team_notifications ?? true,
                'email_marketing' => $user->email_marketing_notifications ?? false,
                'line_enabled' => $user->line_notifications ?? false,
                'push_enabled' => $user->push_notifications ?? true,
            ],

            // ความเป็นส่วนตัว
            'privacy' => [
                'profile_visibility' => $user->profile_visibility ?? 'public',
                'show_stats' => $user->show_stats ?? true,
                'show_team' => $user->show_team ?? true,
            ],

            // รูปลักษณ์
            'appearance' => [
                'theme' => $user->theme_preference ?? 'auto',
                'reduced_motion' => $user->reduced_motion ?? false,
            ],

            // ความปลอดภัย
            'security' => [
                'two_factor_enabled' => $user->two_factor_enabled ?? false,
                'active_sessions_count' => $this->getActiveSessionsCount(),
            ],
        ];

        return view('user.settings-arrow-x', compact('user', 'settings'));
    }

    /**
     * อัปเดตการตั้งค่าของ User
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // Validate input
        $validated = $request->validate([
            // ตั้งค่าทั่วไป
            'preferred_language' => 'nullable|in:th,en',
            'timezone' => 'nullable|timezone',
            'preferred_currency' => 'nullable|in:THB,USD,EUR',

            // การแจ้งเตือน
            'email_notifications' => 'nullable|boolean',
            'email_commission_notifications' => 'nullable|boolean',
            'email_team_notifications' => 'nullable|boolean',
            'email_marketing_notifications' => 'nullable|boolean',
            'line_notifications' => 'nullable|boolean',
            'push_notifications' => 'nullable|boolean',

            // ความเป็นส่วนตัว
            'profile_visibility' => 'nullable|in:public,private,friends',
            'show_stats' => 'nullable|boolean',
            'show_team' => 'nullable|boolean',

            // รูปลักษณ์
            'theme_preference' => 'nullable|in:light,dark,auto',
            'reduced_motion' => 'nullable|boolean',
        ]);

        // แปลง checkbox values เป็น boolean
        $booleanFields = [
            'email_notifications',
            'email_commission_notifications',
            'email_team_notifications',
            'email_marketing_notifications',
            'line_notifications',
            'push_notifications',
            'show_stats',
            'show_team',
            'reduced_motion',
        ];

        foreach ($booleanFields as $field) {
            if (! isset($validated[$field])) {
                $validated[$field] = false;
            }
        }

        // อัปเดตข้อมูล
        $user->update($validated);

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
