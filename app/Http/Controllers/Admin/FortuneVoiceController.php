<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneSystemVoiceClip;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneSystemVoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 🎧 (2026-06-21) Fortune Voice Management — หน้ารวมจัดการเสียงทั้งหมด
 *
 * รวมการตั้งค่า TTS + คลังเสียงระบบ (ข้อความกลาง) ไว้ที่เดียว:
 *   - ตั้งค่าเครื่องเสียง: provider chain + เลือกโมเดล/เสียง + ปรับแต่ง
 *   - คลังเสียงระบบ: แก้ข้อความ + เปิด/ปิดรายอัน + กดฟัง/สร้างเก็บไว้ล่วงหน้า
 *   - ตั้งค่าเสียงทำนาย: เปิด/ปิด + tier scope
 *
 * เสียงระบบเก็บที่ public disk (local server) เท่านั้น — ไม่ส่ง cloud
 */
class FortuneVoiceController extends Controller
{
    /**
     * Provider ที่รองรับ (ใช้ validate)
     */
    protected const PROVIDERS = 'minimax,openai_tts,google_tts,gtts';

    /**
     * แสดงหน้าจัดการเสียง
     */
    public function index(): View
    {
        $settings = FortuneTellingSetting::getSettings();

        $clipsByGroup = FortuneSystemVoiceClip::orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('step_group');

        return view('admin.fortune.voice.index', [
            'pageTitle' => '🎧 จัดการเสียง',
            'settings' => $settings,
            'clipsByGroup' => $clipsByGroup,
            'groupLabels' => $this->groupLabels(),
        ]);
    }

    /**
     * บันทึกการตั้งค่าเครื่องเสียง + เสียงทำนาย + master toggle เสียงระบบ
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            // master toggle เสียงระบบ
            'system_voice_enabled' => 'boolean',
            // เสียงทำนาย
            'voice_summary_enabled' => 'boolean',
            'voice_summary_tier_scope' => 'nullable|in:celtic_99_only,paid_all,all',
            'voice_summary_max_chars' => 'nullable|integer|min:200|max:5000',
            'voice_summary_intro_message' => 'nullable|string|max:1000',
            // เครื่องเสียง (provider + โมเดล/เสียง + ปรับแต่ง)
            'voice_summary_primary_provider' => 'nullable|in:'.self::PROVIDERS,
            'voice_summary_fallback_providers' => 'nullable|array',
            'voice_summary_fallback_providers.*' => 'in:'.self::PROVIDERS,
            'minimax_model' => 'nullable|string|max:50',
            'minimax_voice_id' => 'nullable|string|max:100',
            'openai_tts_model' => 'nullable|string|max:50',
            'openai_tts_voice' => 'nullable|in:alloy,echo,fable,onyx,nova,shimmer',
            'google_tts_voice' => 'nullable|string|max:100',
            'google_tts_speaking_rate' => 'nullable|numeric|min:0.25|max:4.0',
        ]);

        // checkbox ที่ไม่ถูกส่ง = false
        $validated['system_voice_enabled'] = $request->boolean('system_voice_enabled');
        $validated['voice_summary_enabled'] = $request->boolean('voice_summary_enabled');

        // fallback: อ่านตรงจาก request เสมอ — ถ้า uncheck ทั้งหมด key จะหายจาก validated
        //   (checkbox array ไม่มี hidden sentinel) → ต้องเคลียร์เป็น null เองให้ chain กลับไปใช้ default
        $validated['voice_summary_fallback_providers'] = $request->input('voice_summary_fallback_providers', []) ?: null;

        $settings = FortuneTellingSetting::getSettings();
        $settings->update($validated);

        return back()->with('success', '✅ บันทึกการตั้งค่าเสียงสำเร็จ');
    }

    /**
     * บันทึกข้อความ/ตั้งค่าคลิปเสียงระบบ
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateClip(Request $request, FortuneSystemVoiceClip $clip)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'script_text' => 'required|string|max:3000',
            'enabled' => 'boolean',
            'voice_provider' => 'nullable|in:'.self::PROVIDERS,
            'voice_id' => 'nullable|string|max:100',
        ]);

        // override เสียงเฉพาะคลิป (null = ใช้ค่า global)
        $voiceConfig = [];
        if (! empty($validated['voice_provider'])) {
            $voiceConfig['provider'] = $validated['voice_provider'];
        }
        if (! empty($validated['voice_id'])) {
            $voiceConfig['voice'] = $validated['voice_id'];
        }

        $clip->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'script_text' => $validated['script_text'],
            'enabled' => $request->boolean('enabled'),
            'voice_config' => $voiceConfig ?: null,
        ]);

        return back()->with('success', '✅ บันทึกคลิป "'.$clip->title.'" สำเร็จ (กดสร้างเสียงใหม่ถ้าแก้ข้อความ)');
    }

    /**
     * AJAX — สร้าง/สร้างใหม่ไฟล์เสียงของคลิป (เก็บไว้ใช้จริง)
     */
    public function generateClip(FortuneSystemVoiceClip $clip): JsonResponse
    {
        $service = new FortuneSystemVoiceService(FortuneTellingSetting::getSettings());
        $result = $service->generateClip($clip);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * AJAX — พรีวิวเสียงจากข้อความใดๆ (ฟังก่อนบันทึก)
     */
    public function previewText(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string|max:2000',
            'voice_provider' => 'nullable|in:'.self::PROVIDERS,
            'voice_id' => 'nullable|string|max:100',
        ]);

        $cfg = [];
        if (! empty($validated['voice_provider'])) {
            $cfg['provider'] = $validated['voice_provider'];
        }
        if (! empty($validated['voice_id'])) {
            $cfg['voice'] = $validated['voice_id'];
        }

        $service = new FortuneSystemVoiceService(FortuneTellingSetting::getSettings());
        $result = $service->previewText($validated['text'], $cfg ?: null);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * AJAX — ลบไฟล์เสียงของคลิป (สร้างใหม่)
     */
    public function deleteClipAudio(FortuneSystemVoiceClip $clip): JsonResponse
    {
        $service = new FortuneSystemVoiceService(FortuneTellingSetting::getSettings());
        $service->deleteClipAudio($clip);

        return response()->json(['success' => true, 'message' => 'ลบไฟล์เสียงแล้ว']);
    }

    /**
     * อัปโหลดไฟล์เสียงเอง — ใช้แทน TTS (รองรับทุกฟอร์แมต)
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function uploadClipAudio(Request $request, FortuneSystemVoiceClip $clip)
    {
        $request->validate([
            'audio_file' => 'required|file|max:61440', // สูงสุด 60MB
        ]);

        $file = $request->file('audio_file');
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $mime = (string) $file->getMimeType();

        // รองรับทุกฟอร์แมตเสียง — เช็คว่า "เป็นเสียง" จาก mime หรือ นามสกุล
        $audioExts = ['mp3', 'm4a', 'wav', 'ogg', 'oga', 'aac', 'flac', 'opus', 'weba', 'webm', '3gp', '3gpp', 'amr', 'wma', 'aiff', 'aif', 'caf', 'mp4'];
        $looksAudio = str_starts_with($mime, 'audio/')
            || in_array($ext, $audioExts, true)
            || $mime === 'video/mp4'; // m4a บางเครื่องส่ง mime เป็น video/mp4

        if (! $looksAudio) {
            return back()->withErrors(['audio_file' => "ไฟล์นี้ไม่ใช่ไฟล์เสียง (ชนิด: {$mime})"]);
        }

        $service = new FortuneSystemVoiceService(FortuneTellingSetting::getSettings());
        $result = $service->storeUploadedAudio($clip, $file);

        if (! $result['success']) {
            return back()->withErrors(['audio_file' => $result['error']]);
        }

        $warn = in_array($ext, ['mp3', 'm4a'], true) ? '' : ' ⚠️ ฟอร์แมต .'.$ext.' อาจส่งบน Facebook ไม่ได้ (แนะนำ mp3/m4a)';

        return back()->with('success', '✅ อัปโหลดเสียงคลิป "'.$clip->title.'" สำเร็จ'.$warn);
    }

    /**
     * ป้ายชื่อกลุ่มขั้นตอน (สำหรับแสดงในหน้า)
     *
     * @return array<string, string>
     */
    protected function groupLabels(): array
    {
        return [
            'sales_nudge' => '🔔 กล่องกระตุ้นการขาย',
            'consent' => '📜 กติกาก่อนดูดวง',
            'birthdate' => '🎂 ขั้นตอนกรอกวันเกิด',
            'payment' => '💰 การชำระเงิน',
            'welcome' => '👋 ทักทาย',
            'celtic' => '🔮 Celtic / เลือกไพ่',
            'other' => '🎧 อื่นๆ',
        ];
    }
}
