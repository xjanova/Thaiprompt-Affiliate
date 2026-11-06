<?php

namespace App\Http\Controllers\Admin;

use App\Models\FortuneCharacterVoiceClip;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneVoiceCharacter;
use App\Models\FortuneVoiceLine;
use App\Services\Fortune\FortuneCharacterVoiceService;
use App\Services\Fortune\JuntraVoiceLibrary;
use App\Services\FortuneVoiceStorageService;
use App\Services\Tts\ElevenLabsTtsProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * 🎭 (2026-11-04) ตัวละครเสียงพากย์ + คลังบทพูด + ตารางคลิปเสียงทั้งหมด
 *
 * เจ้าของสั่ง: "เพิ่มตารางคลิปเสียงที่เราสร้างไว้ใช้ พร้อมระบบสร้างเสียงแบบเลือกได้ครบ เช่นเลือกใช้โมเดลไหนเสียงใคร
 * หรือใช้แบบเดียวกัน สร้างทีละเสียงหรือทั้งหมด เพิ่มลดเสียงที่ต้องการได้ เพิ่มลดเสียงต้นแบบได้ … เพื่อกำหนดใช้กับห้องพยากรณ์ใหม่ได้"
 *
 * สืบทอดหน้าเสียงพากย์เดิม เพื่อใช้รายชื่อเสียง/โมเดลที่แคชไว้ชุดเดียวกัน
 */
class FortuneVoiceLibraryController extends FortuneCharacterVoiceController
{
    /** หมวดของคลังที่รู้จัก (ชื่อไทยในหน้าแอดมิน) — ตั้งหมวดใหม่เองได้ */
    public const CATEGORIES = [
        'ack' => 'คำรับ (อืม/ค่ะ)',
        'listen' => 'ชวนเล่าต่อ',
        'think' => 'ขอคิดแป๊บ',
        'empathy' => 'เห็นใจ',
        'bright' => 'ดีใจด้วย',
        'prefix' => 'ขึ้นต้นคำตอบ',
        'call' => 'ขานรับเมื่อถูกเรียก',
        'mishear' => 'ฟังไม่ชัด',
        'ask_more' => 'ชวนถามต่อ',
        'bye' => 'ลาก่อน',
        'soothe' => 'ปลอบ (ระบบเลือกเอง)',
    ];

    // ═══════════════════════ ตัวละคร ═══════════════════════

    public function characters(): View
    {
        $service = new FortuneCharacterVoiceService;

        return view('admin.fortune.character-voices.characters', [
            'pageTitle' => '🎭 ตัวละครเสียงพากย์',
            'characters' => $characters = FortuneVoiceCharacter::orderBy('sort_order')->orderBy('id')->get(),
            // เสียงที่ใช้จริง (3 ตัวเดิมที่ยังไม่ได้ตั้งในตาราง = เสียงที่เลือกไว้ในหน้าตั้งค่า)
            'effectiveVoices' => $characters->mapWithKeys(fn ($c) => [$c->key => $service->voiceFor($c->key)])->all(),
            'lineCounts' => FortuneVoiceLine::select('persona', DB::raw('count(*) as n'))->groupBy('persona')->pluck('n', 'persona'),
            'voices' => $this->voiceList($service),
            'models' => $this->thaiModels($service->provider()),
            'globalModel' => $service->provider()->model(),
        ]);
    }

    public function storeCharacter(Request $request): RedirectResponse
    {
        $service = new FortuneCharacterVoiceService;
        $validated = $request->validate($this->characterRules($service) + [
            'key' => ['required', 'string', 'regex:'.FortuneVoiceCharacter::KEY_PATTERN, Rule::unique('fortune_voice_characters', 'key')],
            'copy_lines_from' => ['nullable', 'string', Rule::exists('fortune_voice_characters', 'key')],
        ], $this->characterMessages());

        DB::transaction(function () use ($validated) {
            $character = FortuneVoiceCharacter::create([
                'key' => $validated['key'],
                'label' => $validated['label'],
                'brief' => $validated['brief'] ?? null,
                'sample_text' => $validated['sample_text'],
                'voice_id' => $validated['voice_id'] ?? null,
                'model' => $validated['model'] ?? null,
                'is_builtin' => false,
                'is_active' => true,
                'sort_order' => (int) FortuneVoiceCharacter::max('sort_order') + 1,
            ]);

            // ตั้งต้นจากคลังของตัวละครอื่น — คัดลอกเฉพาะบท ไฟล์เสียงต้องสร้างใหม่ด้วยเสียงของตัวนี้
            if ($from = $validated['copy_lines_from'] ?? null) {
                FortuneVoiceLine::where('persona', $from)->orderBy('sort_order')->get()
                    ->each(fn (FortuneVoiceLine $l) => FortuneVoiceLine::create([
                        'persona' => $character->key,
                        'line_key' => $l->line_key,
                        'category' => $l->category,
                        'script' => $l->script,
                        'is_active' => $l->is_active,
                        'sort_order' => $l->sort_order,
                    ]));
            }
        });

        return redirect()->route('admin.fortune.character-voices.characters')
            ->with('success', 'เพิ่มตัวละคร '.$validated['label'].' ('.$validated['key'].') แล้ว');
    }

    public function updateCharacter(Request $request, FortuneVoiceCharacter $character): RedirectResponse
    {
        $service = new FortuneCharacterVoiceService;
        $validated = $request->validate($this->characterRules($service) + [
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ], $this->characterMessages());

        // ตัวที่ห้องเดิมใช้อยู่ ปิดไม่ได้ — ปิดแล้วห้องนั้นจะสร้างเสียงไม่ได้ทันที
        $active = $character->is_builtin ? true : (bool) ($validated['is_active'] ?? false);

        $character->update([
            'label' => $validated['label'],
            'brief' => $validated['brief'] ?? null,
            'sample_text' => $validated['sample_text'],
            'voice_id' => $validated['voice_id'] ?? null,
            'model' => $validated['model'] ?? null,
            'is_active' => $active,
            'sort_order' => (int) ($validated['sort_order'] ?? $character->sort_order),
        ]);
        // 3 ตัวเดิมเก็บเสียงคู่ไว้ในค่ากลางด้วย (หน้าเลือกเสียงเดิมอ่านจากที่เดียวกัน)
        if ($character->is_builtin && ! empty($validated['voice_id'])) {
            $service->selectVoice($character->key, $validated['voice_id']);
        }

        return back()->with('success', 'บันทึกตัวละคร '.$character->label.' แล้ว');
    }

    public function destroyCharacter(FortuneVoiceCharacter $character): RedirectResponse
    {
        if ($character->is_builtin) {
            return back()->with('error', $character->label.' เป็นตัวละครของห้องเดิม ลบไม่ได้');
        }

        DB::transaction(function () use ($character) {
            FortuneVoiceLine::where('persona', $character->key)->delete();
            $character->delete();
        });
        JuntraVoiceLibrary::forget($character->key);

        return back()->with('success', 'ลบตัวละคร '.$character->label.' และบทในคลังแล้ว (ไฟล์เสียงที่สร้างไว้ยังอยู่ในตารางคลิป)');
    }

    // ═══════════════════════ คลังบทพูด + สร้างเสียง ═══════════════════════

    public function library(Request $request): View
    {
        $service = new FortuneCharacterVoiceService;
        $catalog = FortuneVoiceCharacter::catalog();
        $persona = (string) $request->query('persona', '');
        if (! isset($catalog[$persona])) {
            $persona = (string) array_key_first($catalog);
        }

        $lines = FortuneVoiceLine::with('clip')->where('persona', $persona)
            ->orderBy('sort_order')->orderBy('id')->get();
        $categories = self::CATEGORIES + $lines->pluck('category')->unique()
            ->reject(fn ($c) => isset(self::CATEGORIES[$c]))->mapWithKeys(fn ($c) => [$c => $c])->all();

        return view('admin.fortune.character-voices.library', [
            'pageTitle' => '🗂️ คลังบทพูด & สร้างเสียง',
            'catalog' => $catalog,
            'persona' => $persona,
            'character' => $catalog[$persona] ?? null,
            'characterVoice' => $persona !== '' ? $service->voiceFor($persona) : null,
            'lines' => $lines,
            'categories' => $categories,
            'voices' => $this->voiceList($service),
            'models' => $this->thaiModels($service->provider()),
            'globalModel' => $service->provider()->model(),
            'hasKey' => $service->provider()->isAvailable(),
        ]);
    }

    public function storeLine(Request $request): RedirectResponse
    {
        $service = new FortuneCharacterVoiceService;
        $validated = $request->validate($this->lineRules($service) + [
            'persona' => ['required', 'string', Rule::in(array_keys(FortuneVoiceCharacter::catalog()))],
        ], $this->lineMessages());

        $line = FortuneVoiceLine::create([
            'persona' => $validated['persona'],
            'line_key' => FortuneVoiceLine::nextKey($validated['persona'], $validated['category']),
            'category' => $validated['category'],
            'script' => $validated['script'],
            'voice_id' => $validated['voice_id'] ?? null,
            'model' => $validated['model'] ?? null,
            'is_active' => true,
            'sort_order' => (int) FortuneVoiceLine::where('persona', $validated['persona'])->max('sort_order') + 1,
        ]);

        return redirect()->route('admin.fortune.character-voices.library', ['persona' => $line->persona])
            ->with('success', 'เพิ่มบท '.$line->line_key.' แล้ว — กด 🎙️ สร้าง เพื่อสร้างไฟล์เสียง');
    }

    /** แก้บท/เสียง/โมเดลของบรรทัด (AJAX จากตาราง) — แก้ข้อความแล้วไฟล์เดิมไม่ตรงบท จึงปลดออก */
    public function updateLine(Request $request, FortuneVoiceLine $line): JsonResponse
    {
        $service = new FortuneCharacterVoiceService;
        // หมวดผูกกับ id ของบท (โหมดเสียงอ้างอิง) — เปลี่ยนหมวด = ลบแล้วเพิ่มใหม่
        $validated = $request->validate(array_diff_key($this->lineRules($service), ['category' => true]) + [
            'is_active' => 'nullable|boolean',
        ], $this->lineMessages());

        $changed = trim($validated['script']) !== trim($line->script);
        $line->update([
            'script' => $validated['script'],
            'voice_id' => $validated['voice_id'] ?? null,
            'model' => $validated['model'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? $line->is_active),
            'clip_id' => $changed ? null : $line->clip_id,
        ]);

        return response()->json(['success' => true, 'data' => $this->linePayload($line->fresh('clip')), 'message' => 'บันทึกบท '.$line->line_key.' แล้ว']);
    }

    public function destroyLine(FortuneVoiceLine $line): JsonResponse
    {
        $line->delete();

        return response()->json(['success' => true, 'message' => 'ลบบท '.$line->line_key.' แล้ว (ไฟล์เสียงยังอยู่ในตารางคลิป)']);
    }

    /**
     * 🎙️ สร้างเสียงของบรรทัดเดียว — หน้าเว็บเรียกทีละบรรทัด (สร้างทีละเสียง / ทั้งหมด / ที่เลือก)
     *
     * model / voice_id ที่ส่งมา = ใช้กับรอบนี้ (เลือก "แบบเดียวกันทั้งหมด") · ว่าง = ของบรรทัด → ของตัวละคร → ค่ากลาง
     * ข้อความ+เสียง+โมเดล+ค่าจูนเดิม = ไฟล์เดิม ไม่เสียเครดิตซ้ำ
     */
    public function generateLine(Request $request, FortuneVoiceLine $line): JsonResponse
    {
        $service = new FortuneCharacterVoiceService;
        $validated = $request->validate([
            'model' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9][a-z0-9_\-\.]*$/'],
            'voice_id' => ['nullable', 'string', 'regex:/^[A-Za-z0-9]{6,64}$/'],
        ]);

        if (! FortuneCharacterVoiceService::isPersona($line->persona)) {
            return response()->json(['success' => false, 'error' => 'ตัวละครนี้ปิดอยู่ — เปิดในหน้าตัวละครก่อน'], 422);
        }

        $model = ($validated['model'] ?? null) ?: $line->model;
        if ($model) {
            if (! in_array($model, array_column($this->thaiModels($service->provider()), 'id'), true)) {
                return response()->json(['success' => false, 'error' => 'โมเดลนี้พูดภาษาไทยไม่ได้ หรือไม่มีในบัญชี ElevenLabs'], 422);
            }
            $service = $service->usingModel($model);
        }

        $voice = ($validated['voice_id'] ?? null) ?: $line->voice_id;
        if ($voice && ! $this->isKnownVoice($service, $voice)) {
            return response()->json(['success' => false, 'error' => 'ไม่รู้จักเสียงนี้'], 422);
        }

        $result = $service->request($line->persona, $line->script, 'library', $voice ?: null, 25);
        if ($result['clip']) {
            $line->update(['clip_id' => $result['clip']->id]);
        }

        return $this->clipResponse($result);
    }

    // ═══════════════════════ ตารางคลิปทั้งหมด ═══════════════════════

    public function clips(Request $request): View
    {
        $filters = $request->validate([
            'persona' => 'nullable|string|max:20',
            'purpose' => 'nullable|string|max:16',
            'status' => 'nullable|in:pending,completed,failed',
            'model' => 'nullable|string|max:64',
            'q' => 'nullable|string|max:100',
        ]);

        $query = FortuneCharacterVoiceClip::query()
            ->when($filters['persona'] ?? null, fn ($q, $v) => $q->where('persona', $v))
            ->when($filters['purpose'] ?? null, fn ($q, $v) => $q->where('purpose', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['model'] ?? null, fn ($q, $v) => $q->where('model', $v))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where('text', 'like', '%'.addcslashes($v, '%_\\').'%'));

        $stats = (clone $query)->selectRaw('count(*) as n, coalesce(sum(chars),0) as chars, coalesce(sum(hits),0) as hits')->first();
        $clips = $query->orderByDesc('id')->paginate(30)->withQueryString();

        return view('admin.fortune.character-voices.clips', [
            'pageTitle' => '🎧 คลิปเสียงทั้งหมด',
            'clips' => $clips,
            'filters' => $filters,
            'stats' => $stats,
            'catalog' => FortuneVoiceCharacter::catalog(),
            'purposes' => FortuneCharacterVoiceClip::select('purpose')->distinct()->pluck('purpose')->all(),
            'modelsUsed' => FortuneCharacterVoiceClip::select('model')->distinct()->orderBy('model')->pluck('model')->all(),
            'voiceNames' => collect($this->voiceList(new FortuneCharacterVoiceService))->mapWithKeys(fn ($v) => [$v['id'] => explode(' — ', $v['name'])[0]])->all(),
            'linesByClip' => FortuneVoiceLine::whereIn('clip_id', $clips->pluck('id'))->get()->groupBy('clip_id'),
        ]);
    }

    /** ลบคลิป (ไฟล์ + แถว) ทีละไฟล์หรือที่เลือก — บทในคลังที่ผูกอยู่จะกลับเป็น "ยังไม่มีเสียง" */
    public function destroyClips(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1|max:200',
            'ids.*' => 'integer',
        ], ['ids.required' => 'เลือกคลิปที่จะลบก่อน']);

        $storage = new FortuneVoiceStorageService(FortuneTellingSetting::getGlobalSettings());
        $clips = FortuneCharacterVoiceClip::whereIn('id', $validated['ids'])->get();
        foreach ($clips as $clip) {
            if ($clip->audio_path) {
                $storage->deleteAudio($clip->audio_path, $clip->audio_disk);
            }
        }
        FortuneVoiceLine::whereIn('clip_id', $clips->pluck('id'))->get()->each->update(['clip_id' => null]);
        FortuneCharacterVoiceClip::whereIn('id', $clips->pluck('id'))->delete();

        return back()->with('success', 'ลบคลิปเสียง '.$clips->count().' ไฟล์แล้ว');
    }

    // ═══════════════════════ ตัวช่วย ═══════════════════════

    /** @return array<int, array{id: string, name: string, gender?: string}> */
    protected function voiceList(FortuneCharacterVoiceService $service): array
    {
        return $service->provider()->isAvailable() ? $this->voices($service)['voices'] : ElevenLabsTtsProvider::FALLBACK_VOICES;
    }

    protected function characterRules(FortuneCharacterVoiceService $service): array
    {
        return [
            'label' => 'required|string|max:100',
            'brief' => 'nullable|string|max:500',
            'sample_text' => 'required|string|max:'.FortuneCharacterVoiceService::MAX_SAMPLE_CHARS,
            'voice_id' => ['nullable', 'string', function ($attr, $value, $fail) use ($service) {
                if ($value && ! $this->isKnownVoice($service, $value)) {
                    $fail('ไม่รู้จักเสียงนี้ — เลือกจากรายการ');
                }
            }],
            'model' => ['nullable', 'string', function ($attr, $value, $fail) use ($service) {
                if ($value && ! in_array($value, array_column($this->thaiModels($service->provider()), 'id'), true)) {
                    $fail('โมเดลนี้พูดภาษาไทยไม่ได้ หรือไม่มีในบัญชี ElevenLabs');
                }
            }],
        ];
    }

    protected function characterMessages(): array
    {
        return [
            'key.required' => 'ใส่รหัสตัวละคร',
            'key.regex' => 'รหัสตัวละครใช้ a-z ตัวเลข และ _ ขึ้นต้นด้วยตัวอักษร ยาว 2–20 ตัว (เช่น tarot_room)',
            'key.unique' => 'รหัสนี้มีตัวละครใช้แล้ว',
            'label.required' => 'ใส่ชื่อตัวละคร',
            'sample_text.required' => 'ใส่ประโยคตัวอย่าง (ใช้ฟังเทียบเสียง)',
            'sample_text.max' => 'ประโยคตัวอย่างยาวได้ไม่เกิน :max ตัวอักษร',
        ];
    }

    protected function lineRules(FortuneCharacterVoiceService $service): array
    {
        return [
            'category' => ['required', 'string', 'regex:'.FortuneVoiceLine::CATEGORY_PATTERN],
            'script' => 'required|string|max:'.FortuneCharacterVoiceService::MAX_SAMPLE_CHARS,
        ] + array_intersect_key($this->characterRules($service), array_flip(['voice_id', 'model']));
    }

    protected function lineMessages(): array
    {
        return [
            'category.required' => 'เลือกหมวด',
            'category.regex' => 'ชื่อหมวดใช้ a-z และ _ เท่านั้น (เช่น greet หรือ ask_more)',
            'script.required' => 'ใส่บทพูด',
            'script.max' => 'บทพูดยาวได้ไม่เกิน :max ตัวอักษร',
        ];
    }

    /** ข้อมูลบรรทัดสำหรับตารางในหน้าเว็บ */
    public static function linePayload(FortuneVoiceLine $line): array
    {
        return [
            'id' => $line->id,
            'line_key' => $line->line_key,
            'category' => $line->category,
            'script' => $line->script,
            'voice_id' => $line->voice_id ?? '',
            'model' => $line->model ?? '',
            'is_active' => $line->is_active,
            'clip' => $line->clip ? $line->clip->toPublicArray() + ['model' => $line->clip->model] : null,
        ];
    }
}
