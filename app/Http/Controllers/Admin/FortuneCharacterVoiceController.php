<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneCharacterVoiceClip;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\FortuneCharacterVoiceService;
use App\Services\Tts\ElevenLabsTtsProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * 🎭 (2026-11-03) เสียงพากย์ตัวละคร — แม่หมอจันทรา / น้องบุษบา / แม่หมอโหมดคุณไสย (ElevenLabs v3)
 *
 * - (2026-11-04) ต่อ ElevenLabs ตรง — เจ้าของกรอก ElevenLabs API key เอง เก็บเข้ารหัส แสดงแบบ mask
 * - กด "สร้างเสียงตัวอย่าง" ทีละเสียง ฟังเทียบ แล้วเลือกเก็บเป็นเสียงของแต่ละตัวละคร
 * - ไฟล์ตัวอย่างแคชไว้ (ข้อความ+เสียงเดิม = ไฟล์เดิม กดซ้ำไม่เสียเครดิตซ้ำ)
 */
class FortuneCharacterVoiceController extends Controller
{
    protected const VOICES_CACHE_KEY = 'fortune:elevenlabs_voices';

    public function index(): View
    {
        $settings = FortuneTellingSetting::getGlobalSettings();
        $service = new FortuneCharacterVoiceService($settings);

        $personas = [];
        foreach (FortuneCharacterVoiceService::PERSONAS as $key => $persona) {
            $personas[$key] = $persona + [
                'selected_voice' => $service->voiceFor($key),
                'samples' => $service->samplesFor($key),
            ];
        }

        $voices = $service->provider()->isAvailable()
            ? $this->voices($service)
            : ['voices' => ElevenLabsTtsProvider::FALLBACK_VOICES, 'live' => false, 'max_chars' => ElevenLabsTtsProvider::MAX_CHARS];

        return view('admin.fortune.character-voices.index', [
            'pageTitle' => '🎭 เสียงพากย์ตัวละคร',
            'hasKey' => $settings->getElevenlabsApiKey() !== null,
            'maskedKey' => $settings->getElevenlabsApiKeyMasked(),
            'model' => $service->provider()->model(),
            'dailyLimit' => $service->dailyCharLimit(),
            'usedToday' => $service->charsUsedToday(),
            'personas' => $personas,
            'voices' => $voices,
            'chatClipCount' => FortuneCharacterVoiceClip::where('purpose', 'chat')->where('status', 'completed')->count(),
        ]);
    }

    /**
     * บันทึก key + ตั้งค่า — ช่อง key เว้นว่าง = ใช้ key เดิม
     * key ใหม่ต้องผ่านการทดสอบ (เช็คโควต้า ไม่เสียโควต้า) ก่อนบันทึก
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'elevenlabs_api_key' => ['nullable', 'string', 'max:200', 'regex:/^[A-Za-z0-9_]{20,200}$/'],
            'elevenlabs_tts_model' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9][a-z0-9_\-\.]*$/'],
            'character_voice_daily_char_limit' => 'nullable|integer|min:1000|max:2000000',
        ], [
            'elevenlabs_api_key.regex' => 'รูปแบบ key ไม่ถูกต้อง — คัดลอก key จาก elevenlabs.io → Developers → API Keys (ปกติขึ้นต้นด้วย sk_)',
            'elevenlabs_tts_model.regex' => 'ชื่อโมเดลไม่ถูกต้อง (เช่น eleven_v3)',
        ]);

        $settings = FortuneTellingSetting::getGlobalSettings();
        $update = [
            'elevenlabs_tts_model' => $validated['elevenlabs_tts_model'] ?? null,
            'character_voice_daily_char_limit' => $validated['character_voice_daily_char_limit'] ?? null,
        ];

        $newKey = trim((string) ($validated['elevenlabs_api_key'] ?? ''));
        $message = '✅ บันทึกการตั้งค่าเสียงตัวละครแล้ว';

        if ($newKey !== '') {
            $check = (new ElevenLabsTtsProvider($settings, $newKey))->credits();
            if (! $check['success']) {
                // ⚠️ ห้ามใส่ key ลงใน error / old input — withInput() ตัดช่อง key ทิ้ง
                return back()
                    ->withInput($request->except('elevenlabs_api_key'))
                    ->withErrors(['elevenlabs_api_key' => 'ใช้ key นี้ไม่ได้: '.$check['error']]);
            }

            $update['elevenlabs_api_key'] = $newKey;
            $message = $check['balance'] !== null
                ? '✅ บันทึก ElevenLabs key แล้ว (เหลือ '.number_format((int) $check['balance']).' ตัวอักษรในรอบบิลนี้)'
                : '✅ บันทึก ElevenLabs key แล้ว (key นี้ไม่มีสิทธิ์ดูโควต้า แต่สร้างเสียงได้)';
            Cache::forget(self::VOICES_CACHE_KEY);
        }

        $settings->update($update);
        FortuneTellingSetting::clearSettingsCache();

        return back()->with('success', $message);
    }

    /**
     * ลบ key ออก (หน้าเว็บมี confirm ก่อน)
     */
    public function deleteKey(): RedirectResponse
    {
        FortuneTellingSetting::getGlobalSettings()->update(['elevenlabs_api_key' => null]);
        FortuneTellingSetting::clearSettingsCache();
        Cache::forget(self::VOICES_CACHE_KEY);

        return back()->with('success', 'ลบ ElevenLabs key แล้ว — เสียงตัวละครจะหยุดสร้างใหม่ (ไฟล์ที่แคชไว้ยังเล่นได้)');
    }

    /**
     * AJAX — โควต้าตัวอักษรคงเหลือ
     */
    public function credits(): JsonResponse
    {
        $result = (new FortuneCharacterVoiceService)->provider()->credits();

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * AJAX — สร้าง (หรือดึงจากแคช) เสียงตัวอย่างของตัวละคร 1 เสียง
     */
    public function sample(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'persona' => 'required|in:'.implode(',', array_keys(FortuneCharacterVoiceService::PERSONAS)),
            'voice_id' => ['required', 'string', 'regex:/^[A-Za-z0-9]{6,64}$/'],
            'text' => 'nullable|string|max:'.FortuneCharacterVoiceService::MAX_SAMPLE_CHARS,
        ]);

        $service = new FortuneCharacterVoiceService;
        if (! $this->isKnownVoice($service, $validated['voice_id'])) {
            return response()->json(['success' => false, 'error' => 'ไม่รู้จักเสียงนี้'], 422);
        }

        $text = trim((string) ($validated['text'] ?? '')) ?: FortuneCharacterVoiceService::PERSONAS[$validated['persona']]['sample_text'];
        $result = $service->request($validated['persona'], $text, 'sample', $validated['voice_id'], 25);

        return $this->clipResponse($result);
    }

    /**
     * AJAX — poll สถานะเสียงที่ยังสร้างไม่เสร็จ
     */
    public function clipStatus(FortuneCharacterVoiceClip $clip): JsonResponse
    {
        $clip = (new FortuneCharacterVoiceService)->refresh($clip, 20);

        return response()->json(['success' => $clip->status !== 'failed', 'data' => $clip->toPublicArray(), 'error' => $clip->status === 'failed' ? $clip->error : null]);
    }

    /**
     * AJAX — เลือกเสียงให้ตัวละคร
     */
    public function select(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'persona' => 'required|in:'.implode(',', array_keys(FortuneCharacterVoiceService::PERSONAS)),
            'voice_id' => ['required', 'string', 'regex:/^[A-Za-z0-9]{6,64}$/'],
        ]);

        $service = new FortuneCharacterVoiceService;
        if (! $this->isKnownVoice($service, $validated['voice_id'])) {
            return response()->json(['success' => false, 'error' => 'ไม่รู้จักเสียงนี้'], 422);
        }

        $service->selectVoice($validated['persona'], $validated['voice_id']);

        return response()->json([
            'success' => true,
            'message' => 'เลือกเสียงให้ '.FortuneCharacterVoiceService::PERSONAS[$validated['persona']]['label'].' แล้ว',
        ]);
    }

    /**
     * ล้างรายชื่อเสียงที่แคชไว้ แล้วดึงใหม่
     */
    public function refreshVoices(): RedirectResponse
    {
        Cache::forget(self::VOICES_CACHE_KEY);
        $voices = $this->voices(new FortuneCharacterVoiceService);

        return back()->with(
            $voices['live'] ? 'success' : 'error',
            $voices['live']
                ? 'ดึงรายชื่อเสียงจากบัญชี ElevenLabs แล้ว ('.count($voices['voices']).' เสียง)'
                : 'ดึงรายชื่อเสียงจาก ElevenLabs ไม่สำเร็จ — ใช้รายชื่อสำรองไปก่อน'
        );
    }

    /**
     * รายชื่อเสียง — แคช 1 ชม. เฉพาะตอนดึงสดได้ (ดึงไม่ได้ไม่แคช จะได้ลองใหม่รอบหน้า)
     */
    protected function voices(FortuneCharacterVoiceService $service): array
    {
        $cached = Cache::get(self::VOICES_CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }

        $voices = $service->provider()->listVoices();
        if ($voices['live']) {
            Cache::put(self::VOICES_CACHE_KEY, $voices, 3600);
        }

        return $voices;
    }

    /**
     * เสียงที่ยอมให้สร้าง/เลือก = เสียงในบัญชี + เสียงมาตรฐานสำรอง (เสียงเริ่มต้นของตัวละครอยู่ในชุดนี้)
     */
    protected function isKnownVoice(FortuneCharacterVoiceService $service, string $voiceId): bool
    {
        $list = ElevenLabsTtsProvider::FALLBACK_VOICES;
        if ($service->provider()->isAvailable()) {
            $list = array_merge($list, $this->voices($service)['voices']);
        }

        return collect($list)->contains(fn ($v) => $v['id'] === $voiceId);
    }

    protected function clipResponse(array $result): JsonResponse
    {
        $clip = $result['clip'];

        return response()->json([
            'success' => $result['success'],
            'data' => $clip?->toPublicArray(),
            'error' => $result['error'],
        ], $result['success'] ? 200 : $result['code']);
    }
}
