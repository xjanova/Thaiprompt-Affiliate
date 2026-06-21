<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneReading;
use App\Models\FortuneSystemVoiceClip;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneSystemVoiceService;
use App\Services\FortuneVoiceStorageService;
use App\Services\Tts\GoogleCloudTtsProvider;
use App\Services\Tts\GttsProvider;
use App\Services\Tts\MiniMaxTtsProvider;
use App\Services\Tts\OpenAITtsProvider;
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

        // 🩺 (2026-06-21) รวม Voice Diagnostic เข้าหน้านี้ (owner: "ย้ายมาไว้ที่เดียวกัน")
        $storage = new FortuneVoiceStorageService($settings);
        $providers = $this->checkAllProviders($settings);
        $storageStatus = $storage->driverAvailability();
        $stats = [
            'total_celtic' => FortuneReading::where('reading_type', 'celtic_cross')->count(),
            'voice_generated' => FortuneReading::whereNotNull('voice_audio_path')->count(),
            'voice_failed_state' => FortuneReading::where('reading_type', 'celtic_cross')
                ->whereRaw("conversation_state LIKE '%\"voice_summary_status\":\"failed%'")
                ->count(),
        ];
        $recentFails = FortuneReading::where('reading_type', 'celtic_cross')
            ->whereNotNull('conversation_state')
            ->whereRaw("conversation_state LIKE '%voice_summary_error%'")
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'platform_user_id', 'platform', 'conversation_state', 'created_at'])
            ->map(function ($r) {
                $state = is_array($r->conversation_state) ? $r->conversation_state : (json_decode($r->conversation_state, true) ?: []);

                return [
                    'id' => $r->id,
                    'platform' => $r->platform,
                    'user_id' => $r->platform_user_id,
                    'created_at' => $r->created_at?->diffForHumans(),
                    'status' => $state['voice_summary_status'] ?? null,
                    'error' => $state['voice_summary_error'] ?? null,
                ];
            });

        return view('admin.fortune.voice.index', [
            'pageTitle' => '🎧 จัดการเสียง',
            'settings' => $settings,
            'clipsByGroup' => $clipsByGroup,
            'groupLabels' => $this->groupLabels(),
            // diagnostic (merged)
            'providers' => $providers,
            'storage' => $storage,
            'storageStatus' => $storageStatus,
            'stats' => $stats,
            'recentFails' => $recentFails,
            // 🎙️ (2026-06-21) รายชื่อเสียง MiniMax (dropdown เลือก) — cache 1 ชม.
            'minimaxVoices' => $this->fetchMinimaxVoices($settings),
        ]);
    }

    /**
     * ดึงรายชื่อเสียง MiniMax (system ไทย + เสียงโคลนในบัญชี + อื่นๆ) สำหรับ dropdown
     *
     * cache 1 ชม. — fail-safe คืน [] ถ้าไม่มี key / API ล่ม (dropdown ซ่อน เหลือช่องพิมพ์เอง)
     *
     * @return array{thai: array<string,string>, cloned: array<string,string>, system: array<string,string>}
     */
    protected function fetchMinimaxVoices(FortuneTellingSetting $settings): array
    {
        $empty = ['thai' => [], 'cloned' => [], 'system' => []];

        try {
            $key = $settings->getMinimaxApiKey();
            if (empty($key)) {
                return $empty;
            }

            return \Illuminate\Support\Facades\Cache::remember('fortune:minimax_voices', 3600, function () use ($key, $empty) {
                $resp = \Illuminate\Support\Facades\Http::withToken($key)->timeout(20)
                    ->post('https://api.minimax.io/v1/get_voice', ['voice_type' => 'all']);
                $d = $resp->json();
                if (($d['base_resp']['status_code'] ?? -1) !== 0) {
                    return $empty;
                }

                $thai = [];
                $system = [];
                foreach (($d['system_voice'] ?? []) as $v) {
                    $id = $v['voice_id'] ?? '';
                    $nm = $v['voice_name'] ?? $id;
                    if ($id === '') {
                        continue;
                    }
                    if (stripos($id, 'thai') !== false || stripos((string) $nm, 'thai') !== false) {
                        $thai[$id] = (string) $nm;
                    } else {
                        $system[$id] = (string) $nm;
                    }
                }

                $cloned = [];
                foreach (($d['voice_cloning'] ?? []) as $v) {
                    $id = $v['voice_id'] ?? '';
                    if ($id === '') {
                        continue;
                    }
                    $desc = $v['description'] ?? null; // ⚠️ บางที description เป็น array
                    $cloned[$id] = is_string($desc) && $desc !== '' ? $desc : 'เสียงโคลนในบัญชี';
                }

                asort($thai);
                asort($system);

                return ['thai' => $thai, 'cloned' => $cloned, 'system' => $system];
            });
        } catch (\Throwable $e) {
            return $empty;
        }
    }

    /**
     * เช็คทุก TTS provider (รวมจาก Voice Diagnostic เดิม)
     *
     * @return array<int, array<string, mixed>>
     */
    protected function checkAllProviders(FortuneTellingSetting $settings): array
    {
        $list = [
            ['name' => 'minimax', 'label' => '🎙️ MiniMax (premium, primary)'],
            ['name' => 'openai_tts', 'label' => '🎙️ OpenAI TTS (paid)'],
            ['name' => 'google_tts', 'label' => '🎙️ Google Cloud TTS (ฟรี 1M/เดือน)'],
            ['name' => 'gtts', 'label' => '🆓 gTTS (Google Translate, ไม่ต้องตั้ง key)'],
        ];

        $checks = [];
        foreach ($list as $row) {
            $instance = $this->resolveProvider($row['name'], $settings);
            $available = $instance && $instance->isAvailable();
            $checks[] = array_merge($row, [
                'available' => $available,
                'hint' => $available ? '✅ พร้อมใช้' : '❗ ยังตั้งค่าไม่ครบ (ดู "แก้ปัญหาที่พบบ่อย" ด้านล่าง)',
                'is_primary' => ($settings->voice_summary_primary_provider ?? 'minimax') === $row['name'],
                'in_fallback' => is_array($settings->voice_summary_fallback_providers)
                    && in_array($row['name'], $settings->voice_summary_fallback_providers, true),
            ]);
        }

        return $checks;
    }

    /**
     * Resolve TTS provider instance ตามชื่อ
     */
    protected function resolveProvider(string $name, FortuneTellingSetting $settings)
    {
        return match ($name) {
            'minimax' => new MiniMaxTtsProvider($settings),
            'openai_tts' => new OpenAITtsProvider($settings, app(\App\Services\AiApiKeyPoolService::class)),
            'google_tts' => new GoogleCloudTtsProvider($settings),
            'gtts' => new GttsProvider,
            default => null,
        };
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
            'celtic_pick_voice_delay_sec' => 'nullable|integer|min:0|max:600',
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

        // delay เป็น NOT NULL column — coerce null/ว่าง → 0 (= ปิด) กันเขียน NULL
        if (array_key_exists('celtic_pick_voice_delay_sec', $validated)) {
            $validated['celtic_pick_voice_delay_sec'] = (int) ($validated['celtic_pick_voice_delay_sec'] ?? 0);
        }

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

        // ข้อความตามผล: แปลงเป็น mp3 อัตโนมัติ (ffmpeg) / mp3 อยู่แล้ว / ไม่มี ffmpeg (เก็บตามเดิม)
        $note = match ($result['provider_used'] ?? 'upload') {
            'upload+mp3' => ' (แปลงเป็น mp3 อัตโนมัติ — พร้อมส่ง Facebook)',
            default => in_array($ext, ['mp3', 'm4a'], true)
                ? ''
                : ' ⚠️ ระบบไม่มี ffmpeg แปลงไฟล์ — ฟอร์แมต .'.$ext.' อาจส่งบน Facebook ไม่ได้ (แนะนำ mp3)',
        };

        return back()->with('success', '✅ อัปโหลดเสียงคลิป "'.$clip->title.'" สำเร็จ'.$note);
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
            'celtic' => '🔮 Celtic 99 / เลือกไพ่',
            'deep' => '🌙 ดูดวง 39 (Deep)',
            'other' => '🎧 อื่นๆ',
        ];
    }
}
