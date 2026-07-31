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
    public function index(Request $request)
    {
        $settings = FortuneTellingSetting::getSettings();
        $disabledCategories = $settings->getDisabledInviteCategories();

        // คอลเลกชันเต็ม (เบา — เฉพาะคอลัมน์ที่ใช้สถิติ/หมวด) สำหรับ KPI + ชิปกรอง + categoryStats
        // (ดึงทั้งหมดเพื่อให้สถิติ/หมวดถูกต้อง ไม่ใช่แค่หน้าปัจจุบัน)
        $all = FortuneInviteMessage::ordered()->get(['id', 'category', 'is_active', 'send_count']);

        // 🗂️ (2026-06-07) สรุปจำนวนข้อความต่อหมวด (ทั้งหมด/เปิด) + สถานะเปิด-ปิดหมวด
        $categoryStats = $all
            ->groupBy(fn ($m) => $m->category ?: 'general')
            ->map(fn ($group, $cat) => [
                'category' => $cat,
                'total' => $group->count(),
                'active' => $group->where('is_active', true)->count(),
                'enabled' => ! in_array($cat, $disabledCategories, true),
            ])
            ->sortKeys()
            ->values();

        // หมวดทั้งหมด (สำหรับชิปกรอง + datalist autocomplete)
        $categories = $all->pluck('category')->map(fn ($c) => $c ?: 'general')->unique()->filter()->sort()->values();

        // 📄 รายการแบบแบ่งหน้า + กรองหมวด (server-side — กรองได้ครบทุกหน้า ไม่ใช่แค่หน้าปัจจุบัน)
        $curCategory = trim((string) $request->query('category', ''));
        $query = FortuneInviteMessage::ordered();
        if ($curCategory !== '') {
            if ($curCategory === 'general') {
                $query->where(fn ($q) => $q->whereNull('category')->orWhere('category', '')->orWhere('category', 'general'));
            } else {
                $query->where('category', $curCategory);
            }
        }
        $messages = $query->paginate(20)->withQueryString();

        return view('admin.fortune.invite-messages.index', [
            'messages' => $messages,                          // แบ่งหน้า (สำหรับ list)
            'settings' => $settings,
            'totalMessages' => $all->count(),
            'totalSent' => (int) $all->sum('send_count'),
            'activeCount' => $all->where('is_active', true)->count(),
            'categoryStats' => $categoryStats,
            'categories' => $categories,
            'curCategory' => $curCategory,
            'disabledCategories' => $disabledCategories,
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
            'mode' => 'nullable|in:all,classic,transfer',
            'is_active' => 'nullable|boolean',
        ], [
            'message.required' => 'กรุณากรอกข้อความ',
            'message.max' => 'ข้อความยาวเกินไป (สูงสุด 1000 ตัวอักษร)',
        ]);

        // 🗂️ normalize หมวด — ว่าง/เว้นวรรค → 'general' (กันหมวด '' ที่ปิด/กรองไม่ได้)
        $category = trim((string) ($validated['category'] ?? ''));

        FortuneInviteMessage::create([
            'message' => trim($validated['message']),
            'category' => $category !== '' ? $category : 'general',
            // 🔀 (2026-07-28) all = ใช้ได้ทุกโหมด (ค่าเริ่มต้นเดิม) · transfer = เฉพาะโหมดพาไปเว็บ/LINE
            'mode' => $validated['mode'] ?? FortuneInviteMessage::MODE_ALL,
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
            'mode' => 'nullable|in:all,classic,transfer',
            'is_active' => 'nullable|boolean',
        ], [
            'message.required' => 'กรุณากรอกข้อความ',
            'message.max' => 'ข้อความยาวเกินไป (สูงสุด 1000 ตัวอักษร)',
        ]);

        // 🗂️ normalize หมวด — ว่าง/เว้นวรรค → คงของเดิม หรือ 'general'
        $category = trim((string) ($validated['category'] ?? ''));

        $inviteMessage->update([
            'message' => trim($validated['message']),
            'category' => $category !== '' ? $category : ($inviteMessage->category ?: 'general'),
            'mode' => $validated['mode'] ?? ($inviteMessage->mode ?: FortuneInviteMessage::MODE_ALL),
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
            // 🌙 (2026-07-31) แนบกล่องดวงรายวันนำหน้าข้อความ DM ปกติ
            'dm_daily_horoscope_enabled' => 'nullable|boolean',
        ]);

        $settings = FortuneTellingSetting::getSettings();
        $settings->update([
            'enable_invite_rotation' => (bool) $request->boolean('enable_invite_rotation'),
            'dm_daily_horoscope_enabled' => (bool) $request->boolean('dm_daily_horoscope_enabled'),
        ]);
        FortuneTellingSetting::clearSettingsCache();

        return redirect()->route('admin.fortune.invite-messages.index')
            ->with('success', '✅ บันทึกการตั้งค่าสำเร็จ');
    }

    /**
     * 🌍 (2026-06-07) บันทึกตัวกรองกลุ่มเป้าหมายของ DM กลับ (สัญชาติ + อายุ)
     *
     * ใช้กับ DM ตอบอัตโนมัติ เมื่อมีคนคอมเมนต์/กดไลก์ (outbound)
     * ไม่กระทบ flow จ่ายเงิน/รับคำทำนาย (inbound)
     */
    public function saveAudienceFilters(Request $request)
    {
        $validated = $request->validate([
            'dm_send_to_foreigners' => 'nullable|boolean',
            'dm_foreigner_detect_basis' => 'nullable|in:script,no_thai,lao_only',
            'dm_filter_age_enabled' => 'nullable|boolean',
            'dm_age_min' => 'nullable|integer|min:0|max:120',
            'dm_age_max' => 'nullable|integer|min:0|max:120',
            'dm_age_unknown_action' => 'nullable|in:send,skip',
        ], [
            'dm_age_min.max' => 'อายุต่ำสุดต้องไม่เกิน 120 ปี',
            'dm_age_max.max' => 'อายุสูงสุดต้องไม่เกิน 120 ปี',
            'dm_foreigner_detect_basis.in' => 'วิธีตรวจสัญชาติไม่ถูกต้อง',
        ]);

        $min = $validated['dm_age_min'] ?? null;
        $max = $validated['dm_age_max'] ?? null;

        // ถ้ากรอกทั้งคู่ + min > max → สลับให้ถูก (กันแอดมินกรอกกลับด้าน)
        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        $settings = FortuneTellingSetting::getSettings();
        $settings->update([
            'dm_send_to_foreigners' => $request->boolean('dm_send_to_foreigners'),
            'dm_foreigner_detect_basis' => $validated['dm_foreigner_detect_basis'] ?? 'script',
            'dm_filter_age_enabled' => $request->boolean('dm_filter_age_enabled'),
            'dm_age_min' => $min,
            'dm_age_max' => $max,
            'dm_age_unknown_action' => $validated['dm_age_unknown_action'] ?? 'send',
        ]);
        FortuneTellingSetting::clearSettingsCache();

        return redirect()->route('admin.fortune.invite-messages.index')
            ->with('success', '✅ บันทึกตัวกรองกลุ่มเป้าหมายสำเร็จ');
    }

    /**
     * 🗂️ (2026-06-07) บันทึกการเปิด/ปิดหมวดข้อความชวน
     *
     * รับ enabled_categories[] = หมวดที่ "เปิด" (checkbox ที่ติ๊ก)
     * หมวดที่ไม่ได้ส่งมา = ปิด → เก็บลง invite_disabled_categories
     * pickActive() จะไม่สุ่มข้อความจากหมวดที่ปิด
     */
    public function saveCategories(Request $request)
    {
        $request->validate([
            'enabled_categories' => 'nullable|array',
            'enabled_categories.*' => 'string|max:50',
        ]);

        $enabled = (array) $request->input('enabled_categories', []);

        // หมวดทั้งหมดที่มีจริงในคลังข้อความ (distinct จาก DB)
        $allCategories = FortuneInviteMessage::query()
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->filter(fn ($c) => trim((string) $c) !== '')
            ->values()
            ->all();

        // ปิด = ทั้งหมด - ที่เปิด
        $disabled = array_values(array_diff($allCategories, $enabled));

        $settings = FortuneTellingSetting::getSettings();
        $settings->update([
            'invite_disabled_categories' => $disabled,
        ]);
        FortuneTellingSetting::clearSettingsCache();

        $offCount = count($disabled);

        return redirect()->route('admin.fortune.invite-messages.index')
            ->with('success', $offCount > 0
                ? "✅ บันทึกหมวดสำเร็จ — ปิดอยู่ {$offCount} หมวด"
                : '✅ บันทึกหมวดสำเร็จ — เปิดทุกหมวด');
    }
}
