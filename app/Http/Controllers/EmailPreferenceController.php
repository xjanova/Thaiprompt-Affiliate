<?php

namespace App\Http\Controllers;

use App\Models\EmailPreference;
use Illuminate\Http\Request;

class EmailPreferenceController extends Controller
{
    /** สวิตช์ทั้งหมดในฟอร์มหน้าเว็บ (checkbox) */
    private const WEB_TOGGLES = [
        'all_emails',
        'marketing_emails',
        'security_alerts',
        'commission_notifications',
        'withdrawal_notifications',
        'system_announcements',
        'weekly_reports',
    ];

    /**
     * Display email preferences.
     */
    public function index()
    {
        $preference = EmailPreference::getOrCreateForUser(auth()->id());

        return view('user.email-preferences', compact('preference'));
    }

    /**
     * Update email preferences.
     */
    public function update(Request $request)
    {
        $request->validate([
            'marketing_emails' => 'boolean',
            'security_alerts' => 'boolean',
            'commission_notifications' => 'boolean',
            'withdrawal_notifications' => 'boolean',
            'system_announcements' => 'boolean',
            'weekly_reports' => 'boolean',
            'all_emails' => 'boolean',
            'preferred_language' => 'nullable|string|in:th,en',
        ]);

        // 🐛 (2026-10-29) ฟอร์มส่งทั้งหน้า แต่ checkbox ที่ไม่ติ๊กจะไม่ถูกส่งมาเลย
        //    → เดิมปิดสวิตช์แล้วกดบันทึก ค่าเดิม (เปิด) ยังอยู่ · อ่านทุกสวิตช์ด้วย boolean() ให้ "ไม่ส่ง = ปิด"
        $data = [];
        foreach (self::WEB_TOGGLES as $field) {
            $data[$field] = $request->boolean($field);
        }
        if ($request->filled('preferred_language')) {
            $data['preferred_language'] = $request->input('preferred_language');
        }

        $preference = EmailPreference::getOrCreateForUser(auth()->id());
        $preference->update($data);

        return redirect()
            ->route('user.email.preferences')
            ->with('success', 'บันทึกการตั้งค่าอีเมลแล้ว');
    }

    /**
     * Get email preferences (API).
     */
    public function show()
    {
        $preference = EmailPreference::getOrCreateForUser(auth()->id());

        return response()->json($preference);
    }

    /**
     * Update email preferences (API).
     */
    public function updateApi(Request $request)
    {
        $validated = $request->validate([
            'marketing_emails' => 'boolean',
            'security_alerts' => 'boolean',
            'commission_notifications' => 'boolean',
            'withdrawal_notifications' => 'boolean',
            'system_announcements' => 'boolean',
            'weekly_reports' => 'boolean',
            'all_emails' => 'boolean',
            'preferred_language' => 'string|in:th,en',
        ]);

        $preference = EmailPreference::getOrCreateForUser(auth()->id());
        $preference->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'บันทึกการตั้งค่าอีเมลแล้ว',
            'data' => $preference,
        ]);
    }

    /**
     * Disable all emails.
     */
    public function disableAll()
    {
        $preference = EmailPreference::getOrCreateForUser(auth()->id());
        $preference->disableAll();

        return $this->bulkResponse('ปิดการรับอีเมลทั้งหมดแล้ว');
    }

    /**
     * Enable all emails.
     */
    public function enableAll()
    {
        $preference = EmailPreference::getOrCreateForUser(auth()->id());
        $preference->enableAll();

        return $this->bulkResponse('เปิดการรับอีเมลทั้งหมดแล้ว');
    }

    /**
     * ตอบกลับปุ่ม เปิด/ปิดทั้งหมด
     *
     * หน้าเว็บเรียกผ่าน fetch แล้ว reload เอง — เดิม redirect กลับ ทำให้ fetch ไปกินข้อความ flash
     * ก่อนหน้า reload จะเห็น → ลูกค้าไม่เห็นว่าทำสำเร็จ · ตอบ JSON และเก็บ flash ไว้ให้หน้าที่ reload
     */
    private function bulkResponse(string $message)
    {
        if (request()->expectsJson()) {
            session()->flash('success', $message);

            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }
}
