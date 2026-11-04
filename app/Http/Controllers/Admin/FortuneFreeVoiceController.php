<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneVoiceUsage;
use App\Models\Setting;
use App\Services\Fortune\FortuneCharacterVoiceService;
use App\Services\Fortune\FortuneFreeVoiceService;
use App\Services\Fortune\JuntraVoiceLibrary;
use App\Services\FortuneVoiceStorageService;
use App\Services\Tts\GoogleCloudTtsProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * 🎬 (2026-11-04) การ์ด "เสียงอ่านฟรี (Google Cloud)" ในหน้าเสียงตัวละคร — ข้อ 21.2
 *
 * - กุญแจเก็บเข้ารหัสในตาราง settings (เหมือน ElevenLabs) · ทดสอบด้วยการดึงรายชื่อเสียงไทย (ไม่กินโควตา) ก่อนบันทึก
 * - เลือกเสียงไทยต่อห้อง + ปุ่มฟังตัวอย่าง (นับเข้าเพดานเดือนด้วย)
 * - เพดานตัวอักษรต่อเดือน — ถึงแล้วระบบถอยไป gTTS เอง ไม่โดนเก็บเงินเกินโควตาฟรี
 */
class FortuneFreeVoiceController extends Controller
{
    private const KEY_RULE = ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_\-]{30,100}$/'];

    private const VOICE_RULE = ['nullable', 'string', 'max:64', 'regex:/^[a-z]{2}-[A-Z]{2}-[A-Za-z0-9-]+$/'];

    /** ข้อมูลของการ์ดให้หน้า index */
    public static function viewData(FortuneTellingSetting $settings): array
    {
        $service = new FortuneFreeVoiceService($settings);

        return [
            'hasKey' => $settings->getGoogleTtsApiKey() !== null,
            'maskedKey' => $settings->getGoogleTtsApiKeyMasked(),
            'envKey' => collect(GoogleCloudTtsProvider::diagnoseCredentials()['keys_found'])->except('ADMIN_PAGE_KEY')->contains(true),
            'cap' => $service->monthlyCap(),
            'used' => $service->googleCharsThisMonth(),
            'roomVoices' => FortuneFreeVoiceService::roomVoices(),
            'labels' => collect(FortuneCharacterVoiceService::PERSONAS)->map(fn ($p) => $p['label'])->all(),
        ];
    }

    /** บันทึกกุญแจ (เว้นว่าง = ใช้ของเดิม) + เพดานเดือน + เสียงต่อห้อง */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'google_tts_api_key' => self::KEY_RULE,
            'google_tts_monthly_char_cap' => 'required|integer|min:10000|max:4000000',
            'voices' => 'sometimes|array',
            'voices.maemor' => self::VOICE_RULE,
            'voices.busaba' => self::VOICE_RULE,
            'voices.kunsai' => self::VOICE_RULE,
        ], [
            'google_tts_api_key.regex' => 'รูปแบบกุญแจไม่ถูกต้อง — คัดลอกจาก Google Cloud Console → APIs & Services → Credentials (ปกติขึ้นต้นด้วย AIza)',
            'voices.*.regex' => 'ชื่อเสียงไม่ถูกต้อง (เช่น th-TH-Neural2-C)',
        ]);

        $settings = FortuneTellingSetting::getGlobalSettings();
        $newKey = trim((string) ($validated['google_tts_api_key'] ?? ''));
        $message = '✅ บันทึกการตั้งค่าเสียงอ่านฟรีแล้ว';

        if ($newKey !== '') {
            $check = (new GoogleCloudTtsProvider($settings, $newKey))->listVoices();
            if (! $check['success']) {
                // ⚠️ ห้ามใส่กุญแจลงใน error / old input
                return back()
                    ->withInput($request->except('google_tts_api_key'))
                    ->withErrors(['google_tts_api_key' => 'ใช้กุญแจนี้ไม่ได้: '.$check['error']]);
            }
            $settings->setGoogleTtsApiKey($newKey);
            $message = '✅ บันทึกกุญแจ Google Cloud แล้ว (พบเสียงไทย '.count($check['voices']).' เสียง)';
        }

        Setting::set(FortuneFreeVoiceService::MONTHLY_CAP_SETTING, (int) $validated['google_tts_monthly_char_cap'], 'integer', 'fortune_voice');
        $voices = array_filter($validated['voices'] ?? [], fn ($v) => is_string($v) && $v !== '');
        Setting::set(FortuneFreeVoiceService::ROOM_VOICES_SETTING, json_encode($voices), 'string', 'fortune_voice');

        return back()->with('success', $message);
    }

    /** ลบกุญแจ (หน้าเว็บมี confirm) — เสียงอ่านฟรีถอยไป gTTS */
    public function deleteKey(): RedirectResponse
    {
        FortuneTellingSetting::getGlobalSettings()->setGoogleTtsApiKey(null);

        return back()->with('success', 'ลบกุญแจ Google Cloud แล้ว — เสียงอ่านคำทำนายฟรีจะใช้ gTTS แทน');
    }

    /** AJAX — ทดสอบกุญแจที่พิมพ์ (หรือที่บันทึกไว้) ด้วยการดึงรายชื่อเสียงไทย */
    public function test(Request $request): JsonResponse
    {
        $validated = $request->validate(['key' => self::KEY_RULE]);
        $key = trim((string) ($validated['key'] ?? ''));

        $result = (new GoogleCloudTtsProvider(FortuneTellingSetting::getGlobalSettings(), $key !== '' ? $key : null))->listVoices();

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /** AJAX — ฟังตัวอย่างเสียงของห้องด้วยเสียง Google ที่เลือก (ไฟล์แคชไว้ กดซ้ำไม่กินโควตา) */
    public function sample(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'persona' => 'required|in:'.implode(',', array_keys(FortuneCharacterVoiceService::PERSONAS)),
            'voice' => ['required', ...array_slice(self::VOICE_RULE, 1)],
        ]);

        $settings = FortuneTellingSetting::getGlobalSettings();
        $text = JuntraVoiceLibrary::plain(FortuneCharacterVoiceService::PERSONAS[$validated['persona']]['sample_text']);
        $cacheKey = 'free_voice_sample:'.hash('sha256', $validated['voice'].'|'.$text);
        if (is_string($url = Cache::get($cacheKey))) {
            return response()->json(['success' => true, 'url' => $url]);
        }

        $service = new FortuneFreeVoiceService($settings);
        if ($service->googleCharsThisMonth() + mb_strlen($text) > $service->monthlyCap()) {
            return response()->json(['success' => false, 'error' => 'ถึงเพดานตัวอักษรของเดือนนี้แล้ว'], 422);
        }

        $temp = storage_path('app/tmp-voice/'.Str::random(40).'.mp3');
        try {
            $result = (new GoogleCloudTtsProvider($settings))->synthesize($text, ['voice' => $validated['voice'], 'output_path' => $temp]);
            FortuneVoiceUsage::record([
                'kind' => 'tts_free', 'purpose' => 'sample', 'provider' => 'google_tts',
                'persona' => $validated['persona'], 'chars' => mb_strlen($text), 'success' => $result['success'],
            ]);
            if (! $result['success']) {
                // ข้อความดิบของ Google อาจยาว/เป็นอังกฤษ — แสดงแค่สถานะ
                return response()->json(['success' => false, 'error' => 'สร้างเสียงไม่สำเร็จ — เสียงนี้อาจใช้ไม่ได้กับกุญแจนี้ ลองเสียงอื่น'], 422);
            }
            $stored = (new FortuneVoiceStorageService($settings))->putAudio($temp, 'fortune-free-voice/samples/'.Str::random(32).'.mp3');
        } finally {
            if (is_file($temp)) {
                @unlink($temp);
            }
        }

        if (! $stored['success']) {
            return response()->json(['success' => false, 'error' => 'เก็บไฟล์เสียงไม่สำเร็จ'], 500);
        }
        Cache::put($cacheKey, $stored['url'], 60 * 60 * 24 * 30);

        return response()->json(['success' => true, 'url' => $stored['url']]);
    }
}
