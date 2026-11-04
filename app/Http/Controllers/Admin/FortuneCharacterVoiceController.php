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

    protected const MODELS_CACHE_KEY = 'fortune:elevenlabs_models';

    protected const PLAN_CACHE_KEY = 'fortune:elevenlabs_plan';

    protected const EFFECTS_CACHE_KEY = 'fortune:elevenlabs_audio_effects';

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

        $models = $this->thaiModels($service->provider());

        return view('admin.fortune.character-voices.index', [
            'pageTitle' => '🎭 เสียงพากย์ตัวละคร',
            'hasKey' => $settings->getElevenlabsApiKey() !== null,
            'maskedKey' => $settings->getElevenlabsApiKeyMasked(),
            'model' => $service->provider()->model(),
            'models' => $models,
            // 🎚️ ฟอร์มจูนเปลี่ยนป้าย "โมเดลนี้ไม่ใช้" ตามโมเดลที่เลือกในดรอปดาวน์ทันที (ไม่ต้องบันทึกก่อน)
            'modelSettings' => collect($models)->mapWithKeys(fn ($m) => [$m['id'] => ElevenLabsTtsProvider::supportedVoiceSettings($m['id'])])->all(),
            'plan' => $this->plan($service->provider()),
            'dailyLimit' => $service->dailyCharLimit(),
            'usedToday' => $service->charsUsedToday(),
            'personas' => $personas,
            'voices' => $voices,
            'chatClipCount' => FortuneCharacterVoiceClip::where('purpose', 'chat')->where('status', 'completed')->count(),
            // 🎬 (2026-11-04) การ์ดเสียงอ่านฟรี (Google Cloud)
            'freeVoice' => FortuneFreeVoiceController::viewData($settings),
            'tuning' => $settings->getElevenlabsTuning(),
            'audioEffects' => $this->audioEffects($service->provider()),
            'supportedSettings' => ElevenLabsTtsProvider::supportedVoiceSettings($service->provider()->model()),
        ]);
    }

    /**
     * 🎚️ (2026-11-04) บันทึกค่าจูนเสียง ElevenLabs — เจ้าของสั่ง "ปรับตั้งค่า จูน เสียงได้ละเอียดตามที่ api อนุญาติ"
     *
     * เก็บทุกค่าที่ API รับ แม้โมเดลที่ใช้อยู่จะไม่ใช้บางค่า (เปลี่ยนโมเดลทีหลังค่าที่ตั้งไว้ยังอยู่)
     * provider เป็นคนกรองตอนส่งจริง · ปิด "ใช้ค่าที่จูนเอง" = ใช้ค่าที่ติดมากับเสียงนั้น
     */
    public function updateTuning(Request $request): RedirectResponse
    {
        $personaKeys = array_keys(FortuneCharacterVoiceService::PERSONAS);
        $validated = $request->validate($this->tuningRules($personaKeys), $this->tuningMessages());

        $personas = [];
        foreach ($personaKeys as $persona) {
            $in = $validated['personas'][$persona] ?? [];
            if ($field = $this->unknownEffect($in)) {
                return back()->withInput()->withErrors(["personas.$persona.$field" => 'ไม่มีเอฟเฟกต์นี้ในบัญชี ElevenLabs — เลือกจากรายการ']);
            }
            $personas[$persona] = $this->tuningPersona($in);
        }

        FortuneTellingSetting::getGlobalSettings()->setElevenlabsTuning($this->tuningSet($validated, $personas));

        return back()->with('success', '🎚️ บันทึกค่าจูนเสียงแล้ว — ห้องแชทใช้ค่านี้กับเสียงที่สร้างใหม่ตั้งแต่ตอนนี้');
    }

    /**
     * 🎧 (2026-11-04) AJAX — ลองฟังค่าจูนในฟอร์มก่อนบันทึก (เจ้าของสั่ง "ต้องมีการลองฟังเสียงที่จูนแล้วได้ด้วย")
     *
     * รับค่าชุดเดียวกับฟอร์มบันทึก (เฉพาะตัวละครที่กด) ไม่แตะค่าที่บันทึกไว้ · สร้างด้วยเสียงที่ตัวละครเลือกอยู่
     * ใช้แคชไฟล์ชุดเดียวกับห้องแชท — ค่าเดิมกดซ้ำได้ไฟล์เดิม ไม่เสียโควต้าซ้ำ
     * บันทึกค่านี้ทีหลัง ห้องแชทก็ได้ไฟล์ที่ลองฟังไว้แล้วทันที (กุญแจแคชตรงกัน)
     */
    public function previewTuning(Request $request): JsonResponse
    {
        $persona = (string) $request->input('persona');
        if (! FortuneCharacterVoiceService::isPersona($persona)) {
            return response()->json(['success' => false, 'error' => 'ไม่รู้จักตัวละครนี้'], 422);
        }

        $validated = $request->validate($this->tuningRules([$persona]) + [
            'text' => 'nullable|string|max:'.FortuneCharacterVoiceService::MAX_SAMPLE_CHARS,
            'model' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9][a-z0-9_\-\.]*$/'],
        ], $this->tuningMessages());

        $in = $validated['personas'][$persona] ?? [];
        if ($this->unknownEffect($in)) {
            return response()->json(['success' => false, 'error' => 'ไม่มีเอฟเฟกต์นี้ในบัญชี ElevenLabs — เลือกจากรายการ'], 422);
        }

        $service = new FortuneCharacterVoiceService;
        // 🎧 ลองฟังด้วยโมเดลที่เลือกในดรอปดาวน์ (ยังไม่บันทึก) — รับเฉพาะโมเดลไทยที่มีในบัญชี
        $model = (string) ($validated['model'] ?? '');
        if ($model !== '' && $model !== $service->provider()->model()) {
            if (! in_array($model, array_column($this->thaiModels($service->provider()), 'id'), true)) {
                return response()->json(['success' => false, 'error' => 'โมเดลนี้พูดภาษาไทยไม่ได้ หรือไม่มีในบัญชี ElevenLabs'], 422);
            }
            $service = $service->usingModel($model);
        }
        $tuning = $service->tuningFor($persona, $this->tuningSet($validated, [$persona => $this->tuningPersona($in)]));
        $text = trim((string) ($validated['text'] ?? '')) ?: FortuneCharacterVoiceService::PERSONAS[$persona]['sample_text'];

        return $this->clipResponse($service->request($persona, $text, 'sample', null, 25, tuning: $tuning));
    }

    /** กฎตรวจค่าจูน — ค่ารวม + ค่าของตัวละครที่ส่งมา (ช่วงค่าตามที่ ElevenLabs รับ) */
    protected function tuningRules(array $personas): array
    {
        $id = ['nullable', 'string', 'regex:/^[a-z0-9_]{1,64}$/'];
        $rules = [
            'output_format' => 'required|in:'.implode(',', array_keys(ElevenLabsTtsProvider::OUTPUT_FORMATS)),
            'language_code' => 'nullable|in:th',
            'apply_text_normalization' => 'required|in:auto,on,off',
        ];
        foreach ($personas as $persona) {
            $p = "personas.$persona.";
            $rules += [
                $p.'custom' => 'nullable|boolean',
                $p.'use_speaker_boost' => 'nullable|boolean',
                $p.'seed' => 'nullable|integer|min:0|max:'.ElevenLabsTtsProvider::SEED_MAX,
                $p.'filter_preset_id' => $id,
                $p.'environment_id' => $id,
                $p.'background_noise_id' => $id,
            ];
            foreach (ElevenLabsTtsProvider::SETTING_RANGES as $field => [$min, $max]) {
                $rules[$p.$field] = "nullable|numeric|min:$min|max:$max";
            }
        }

        return $rules;
    }

    protected function tuningMessages(): array
    {
        return [
            '*.numeric' => 'ค่าที่ใส่ต้องเป็นตัวเลข',
            '*.min' => 'ค่าต่ำกว่าที่ ElevenLabs รับ',
            '*.max' => 'ค่าสูงกว่าที่ ElevenLabs รับ',
        ];
    }

    /**
     * เอฟเฟกต์ต้องมีอยู่จริงในบัญชี — id ที่ไม่มี ElevenLabs ตอบ 404 ทั้งคำขอ (เสียงไม่ออกเลย)
     *
     * @return string|null ชื่อช่องที่เลือกเอฟเฟกต์ที่ไม่มี · null = ผ่าน
     */
    protected function unknownEffect(array $in): ?string
    {
        $effects = $this->audioEffects((new FortuneCharacterVoiceService)->provider())['effects'];
        $known = [
            'filter_preset_id' => array_column($effects['filter_presets'], 'id'),
            'environment_id' => array_column($effects['environments'], 'id'),
            'background_noise_id' => array_column($effects['background_noises'], 'id'),
        ];
        foreach ($known as $field => $ids) {
            if (! empty($in[$field]) && ! in_array($in[$field], $ids, true)) {
                return $field;
            }
        }

        return null;
    }

    /** ค่าจากฟอร์มของตัวละครหนึ่งตัว → รูปที่เก็บ (ตัวเลขปัด 2 ตำแหน่ง · ช่องว่าง = null) */
    protected function tuningPersona(array $in): array
    {
        $num = fn (string $field) => isset($in[$field]) && $in[$field] !== '' ? round((float) $in[$field], 2) : null;

        return [
            'custom' => (bool) ($in['custom'] ?? false),
            'stability' => $num('stability'),
            'similarity_boost' => $num('similarity_boost'),
            'style' => $num('style'),
            'use_speaker_boost' => (bool) ($in['use_speaker_boost'] ?? false),
            'speed' => $num('speed'),
            'clone_strength' => $num('clone_strength'),
            'seed' => isset($in['seed']) && $in['seed'] !== '' ? (int) $in['seed'] : null,
            'filter_preset_id' => ($in['filter_preset_id'] ?? '') ?: null,
            'environment_id' => ($in['environment_id'] ?? '') ?: null,
            'send_level' => $num('send_level'),
            'distance' => $num('distance'),
            'background_noise_id' => ($in['background_noise_id'] ?? '') ?: null,
            'pan' => $num('pan'),
        ];
    }

    /** ค่าจูนทั้งชุด (รูปเดียวกับที่บันทึกใน settings) */
    protected function tuningSet(array $validated, array $personas): array
    {
        return [
            'output_format' => $validated['output_format'],
            'language_code' => $validated['language_code'] ?? null,
            'apply_text_normalization' => $validated['apply_text_normalization'],
            'personas' => $personas,
        ];
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
            'character_voice_daily_char_limit' => $validated['character_voice_daily_char_limit'] ?? null,
        ];

        $newKey = trim((string) ($validated['elevenlabs_api_key'] ?? ''));
        $message = '✅ บันทึกการตั้งค่าเสียงตัวละครแล้ว';
        $check = null;

        if ($newKey !== '') {
            $check = (new ElevenLabsTtsProvider($settings, $newKey))->credits();
            if (! $check['success']) {
                // ⚠️ ห้ามใส่ key ลงใน error / old input — withInput() ตัดช่อง key ทิ้ง
                return back()
                    ->withInput($request->except('elevenlabs_api_key'))
                    ->withErrors(['elevenlabs_api_key' => 'ใช้ key นี้ไม่ได้: '.$check['error']]);
            }
        }

        // โมเดลต้องพูดไทยได้ในบัญชีของ key ที่จะใช้ — เช็คก่อนบันทึกอะไรทั้งนั้น จะได้ไม่บันทึกครึ่งๆ กลางๆ
        $model = trim((string) ($validated['elevenlabs_tts_model'] ?? ''));
        if ($model !== '') {
            $provider = $newKey !== ''
                ? new ElevenLabsTtsProvider($settings, $newKey)
                : (new FortuneCharacterVoiceService($settings))->provider();
            if (! collect($this->thaiModels($provider, $newKey !== ''))->contains(fn ($m) => $m['id'] === $model)) {
                return back()
                    ->withInput($request->except('elevenlabs_api_key'))
                    ->withErrors(['elevenlabs_tts_model' => 'โมเดลนี้พูดภาษาไทยไม่ได้ หรือไม่มีในบัญชี ElevenLabs — เลือกจากรายการ']);
            }
        }

        if ($check !== null) {
            $settings->setElevenlabsApiKey($newKey);
            $message = $check['balance'] !== null
                ? '✅ บันทึก ElevenLabs key แล้ว (เหลือ '.number_format((int) $check['balance']).' ตัวอักษรในรอบบิลนี้)'
                : '✅ บันทึก ElevenLabs key แล้ว (key นี้ไม่มีสิทธิ์ดูโควต้า แต่สร้างเสียงได้)';
            Cache::forget(self::VOICES_CACHE_KEY);
            Cache::forget(self::MODELS_CACHE_KEY);
            Cache::forget(self::PLAN_CACHE_KEY);
        }

        $settings->setElevenlabsTtsModel($model !== '' ? $model : null);
        $settings->update($update);
        FortuneTellingSetting::clearSettingsCache();

        return back()->with('success', $message);
    }

    /**
     * ลบ key ออก (หน้าเว็บมี confirm ก่อน)
     */
    public function deleteKey(): RedirectResponse
    {
        FortuneTellingSetting::getGlobalSettings()->setElevenlabsApiKey(null);
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
     * โมเดลที่พูดไทยได้ — ดึงสดจาก /v1/models (ฟรี) แคช 1 ชม. เฉพาะตอนดึงสดได้
     * ไม่มี key / ดึงไม่ได้ = ใช้รายชื่อสำรอง (v4, v4 Turbo, v3, v3 Conversational)
     * โมเดลที่ตั้งไว้อยู่แล้วแต่ไม่อยู่ในรายชื่อ ยังแสดงให้เห็น จะได้ไม่ถูกเปลี่ยนเงียบๆ ตอนกดบันทึก
     */
    protected function thaiModels(ElevenLabsTtsProvider $provider, bool $fresh = false): array
    {
        $cached = $fresh ? null : Cache::get(self::MODELS_CACHE_KEY);
        if (! is_array($cached)) {
            $cached = $provider->isAvailable()
                ? $provider->listModels()
                : ['models' => ElevenLabsTtsProvider::FALLBACK_MODELS, 'live' => false];
            if ($cached['live']) {
                Cache::put(self::MODELS_CACHE_KEY, $cached, 3600);
            }
        }

        $models = array_values(array_filter($cached['models'], fn ($m) => $m['thai']));
        $current = $provider->model();
        if (! collect($models)->contains(fn ($m) => $m['id'] === $current)) {
            $models[] = ['id' => $current, 'name' => $current, 'thai' => true, 'cost' => 1.0, 'max_chars' => null];
        }

        return $models;
    }

    /**
     * 💳 แพ็ก ElevenLabs ของบัญชี (เครดิตต่อเดือน + ราคา) — ใช้ประมาณค่าใช้จ่ายของแต่ละโมเดล
     * แคช 6 ชม. เฉพาะตอนอ่านได้ · อ่านไม่ได้ (key จำกัดสิทธิ์ / ล่ม) = null หน้าแสดงแค่เครดิต
     *
     * @return array{tier: ?string, limit: int, price_usd: ?float}|null
     */
    protected function plan(ElevenLabsTtsProvider $provider): ?array
    {
        if (! $provider->isAvailable()) {
            return null;
        }

        $cached = Cache::get(self::PLAN_CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }

        $credits = $provider->credits();
        if (! $credits['success'] || ! $credits['limit']) {
            return null;
        }

        $plan = ['tier' => $credits['tier'] ?? null, 'limit' => (int) $credits['limit'], 'price_usd' => $credits['price_usd'] ?? null];
        Cache::put(self::PLAN_CACHE_KEY, $plan, 6 * 3600);

        return $plan;
    }

    /**
     * 🎚️ เอฟเฟกต์เสียงของบัญชี — แคช 1 วัน เฉพาะตอนดึงสดได้ (รายชื่อนี้แทบไม่เปลี่ยน)
     */
    protected function audioEffects(ElevenLabsTtsProvider $provider): array
    {
        $cached = Cache::get(self::EFFECTS_CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }

        $effects = $provider->isAvailable()
            ? $provider->listAudioEffects()
            : ['effects' => ElevenLabsTtsProvider::FALLBACK_AUDIO_EFFECTS, 'live' => false];
        if ($effects['live']) {
            Cache::put(self::EFFECTS_CACHE_KEY, $effects, 86400);
        }

        return $effects;
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
