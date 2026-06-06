<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneInviteMessage;
use App\Models\FortuneTellingSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * จัดการคลังข้อความ "ชวนดูดวงแบบเนียน" (Fortune Invite Messages)
 *
 * ข้อความสุ่มที่ส่งใน DM กลับ เมื่อลูกค้าได้รูปแบนเนอร์ในสัปดาห์นี้แล้ว
 * (ไม่ส่งรูปซ้ำ → ส่งข้อความเชิญชวนแทน)
 *
 * รองรับ:
 *   - เพิ่ม/แก้ไข/ลบข้อความ
 *   - เปิด/ปิดเป็นรายข้อความ
 *   - เรียงลำดับ
 *   - ดูสถิติการส่ง (send_count)
 *   - ตั้งค่า master toggle (enable_invite_rotation)
 */
class FortuneInviteMessageController extends Controller
{
    /**
     * แสดงรายการข้อความทั้งหมด + ฟอร์มเพิ่ม + ตั้งค่า
     */
    public function index()
    {
        $messages = FortuneInviteMessage::ordered()->get();
        $settings = FortuneTellingSetting::getSettings();

        return view('admin.fortune.invite-messages.index', [
            'messages' => $messages,
            'settings' => $settings,
            'totalSent' => (int) $messages->sum('send_count'),
            'activeCount' => $messages->where('is_active', true)->count(),
            'pageTitle' => 'ข้อความชวนดูดวง (สุ่ม)',
        ]);
    }

    /**
     * บันทึกข้อความใหม่
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'category' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ], [
            'message.required' => 'กรุณากรอกข้อความ',
            'message.max' => 'ข้อความยาวเกินไป (สูงสุด 1000 ตัวอักษร)',
        ]);

        FortuneInviteMessage::create([
            'message' => trim($validated['message']),
            'category' => $validated['category'] ?? 'general',
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => (int) (FortuneInviteMessage::max('sort_order') + 1),
            'created_by' => auth()->id(),
        ]);

        Log::info('Admin: เพิ่มข้อความชวนดูดวง', [
            'admin' => auth()->user()?->name,
        ]);

        return redirect()->route('admin.fortune.invite-messages.index')
            ->with('success', '✅ เพิ่มข้อความสำเร็จ');
    }

    /**
     * แก้ไขข้อความ (inline)
     */
    public function update(Request $request, FortuneInviteMessage $inviteMessage)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'category' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ], [
            'message.required' => 'กรุณากรอกข้อความ',
            'message.max' => 'ข้อความยาวเกินไป (สูงสุด 1000 ตัวอักษร)',
        ]);

        $inviteMessage->update([
            'message' => trim($validated['message']),
            'category' => $validated['category'] ?? $inviteMessage->category,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()->route('admin.fortune.invite-messages.index')
            ->with('success', '✅ บันทึกการแก้ไขสำเร็จ');
    }

    /**
     * ลบข้อความ (soft delete)
     */
    public function destroy(FortuneInviteMessage $inviteMessage)
    {
        $inviteMessage->delete();

        return redirect()->route('admin.fortune.invite-messages.index')
            ->with('success', '✅ ลบข้อความสำเร็จ');
    }

    /**
     * เปิด/ปิดข้อความเป็นรายตัว
     *
     * รองรับทั้ง form POST (redirect กลับ) และ AJAX (JSON)
     */
    public function toggle(Request $request, FortuneInviteMessage $inviteMessage)
    {
        $inviteMessage->update(['is_active' => ! $inviteMessage->is_active]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $inviteMessage->is_active,
            ]);
        }

        return redirect()->route('admin.fortune.invite-messages.index')
            ->with('success', $inviteMessage->is_active ? '▶️ เปิดข้อความแล้ว' : '⏸️ ปิดข้อความแล้ว');
    }

    /**
     * บันทึกลำดับใหม่ (drag & drop)
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:fortune_invite_messages,id',
        ]);

        foreach ($validated['order'] as $index => $id) {
            FortuneInviteMessage::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * บันทึกการตั้งค่า master toggle
     */
    public function saveSettings(Request $request)
    {
        $request->validate([
            'enable_invite_rotation' => 'nullable|boolean',
        ]);

        $settings = FortuneTellingSetting::getSettings();
        $settings->update([
            'enable_invite_rotation' => (bool) $request->boolean('enable_invite_rotation'),
        ]);

        return redirect()->route('admin.fortune.invite-messages.index')
            ->with('success', '✅ บันทึกการตั้งค่าสำเร็จ');
    }
}
