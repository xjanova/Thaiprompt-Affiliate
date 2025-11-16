<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Settings Controller สำหรับจัดการการตั้งค่าของ User
 *
 * @package App\Http\Controllers\User
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
     * @param Request $request
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
            if (!isset($validated[$field])) {
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
     *
     * @return int
     */
    private function getActiveSessionsCount(): int
    {
        // TODO: Implement session counting logic
        // ตัวอย่างการนับจาก sessions table (ถ้ามี)
        // return DB::table('sessions')
        //     ->where('user_id', Auth::id())
        //     ->count();

        return 1; // ตอนนี้ return 1 (session ปัจจุบัน)
    }

    /**
     * ลบ session ที่ระบุ
     *
     * @param Request $request
     * @param string $sessionId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteSession(Request $request, string $sessionId)
    {
        // TODO: Implement session deletion logic
        // ตัวอย่าง:
        // DB::table('sessions')
        //     ->where('id', $sessionId)
        //     ->where('user_id', Auth::id())
        //     ->delete();

        return redirect()
            ->route('user.settings')
            ->with('success', 'ลบ session เรียบร้อยแล้ว');
    }

    /**
     * ลบ sessions ทั้งหมด ยกเว้น session ปัจจุบัน
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteOtherSessions(Request $request)
    {
        // TODO: Implement logic to delete all sessions except current
        // ตัวอย่าง:
        // DB::table('sessions')
        //     ->where('user_id', Auth::id())
        //     ->where('id', '!=', $request->session()->getId())
        //     ->delete();

        return redirect()
            ->route('user.settings')
            ->with('success', 'ลบ sessions อื่นๆ เรียบร้อยแล้ว');
    }
}
