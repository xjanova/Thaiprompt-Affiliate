{{--
    🎚️ (2026-11-04) จูนเสียง ElevenLabs ละเอียด — เจ้าของสั่ง "ปรับตั้งค่า จูน เสียงได้ละเอียดตามที่ api อนุญาติ"
    ทุกค่าที่ API text-to-speech รับ: voice_settings / seed / audio_effects / คุณภาพไฟล์ / ภาษา / การอ่านตัวเลข
    เก็บทุกค่าไว้ แต่ส่งจริงเฉพาะค่าที่โมเดลที่ใช้อยู่รับได้ (กรองที่ ElevenLabsTtsProvider::tuningPayload)
--}}
@php
    $tp = $tuning['personas'] ?? [];
    $voiceFields = [
        'stability' => ['label' => 'ความนิ่ง (Stability)', 'hint' => 'ต่ำ = อารมณ์กว้าง มีชีวิตชีวา · สูง = นิ่ง สม่ำเสมอ (สูงไปจะแบน)', 'default' => 0.5, 'step' => 0.05],
        'similarity_boost' => ['label' => 'ความเหมือนเสียงต้นฉบับ (Similarity)', 'hint' => 'สูง = ยึดเสียงต้นฉบับ · สูงไปอาจติดเสียงรบกวนจากต้นฉบับ', 'default' => 0.75, 'step' => 0.05],
        'style' => ['label' => 'ขยายสไตล์ (Style)', 'hint' => 'เน้นลีลาของผู้พูด · มากกว่า 0 ช้าลงเล็กน้อย', 'default' => 0, 'step' => 0.05],
        'speed' => ['label' => 'ความเร็ว (Speed)', 'hint' => '0.7 ช้าสุด · 1.0 ปกติ · 1.2 เร็วสุด', 'default' => 1, 'step' => 0.01],
    ];
    $effectNames = [
        'old_radio' => 'วิทยุเก่า', 'robot' => 'หุ่นยนต์', 'cheap_microphone' => 'ไมค์ราคาถูก', 'phone' => 'โทรศัพท์',
        'low_quality_phone' => 'โทรศัพท์สัญญาณแย่', 'bright_phone' => 'โทรศัพท์เสียงใส',
        'small_room' => 'ห้องเล็ก', 'big_room' => 'ห้องใหญ่', 'hall' => 'โถงกว้าง (ก้องยาว)', 'tunnel' => 'อุโมงค์',
        'street' => 'ถนน', 'valley' => 'หุบเขา', 'forest' => 'ป่า',
        'call_center' => 'คอลเซ็นเตอร์', 'cafe' => 'คาเฟ่', 'city' => 'เมือง', 'keyboard' => 'เสียงคีย์บอร์ด',
    ];
    $effectLabel = fn (array $e) => isset($effectNames[$e['id']]) ? $effectNames[$e['id']].' ('.$e['name'].')' : $e['name'];
    $isV3 = str_starts_with($model, 'eleven_v3');
    // 🎚️ ป้าย "โมเดลนี้ไม่ใช้" — เซิร์ฟเวอร์วาดตามโมเดลที่บันทึกไว้ แล้ว Alpine (supports() ของหน้าหลัก) สลับตามดรอปดาวน์ทันที
    // ค่าที่โมเดลรับ = ซ่อนไว้ก่อนด้วย x-cloak กันป้ายกะพริบตอนโหลด
    $unusedBadge = fn (string $field, string $extra = '') => new \Illuminate\Support\HtmlString(
        "<span x-show=\"!supports('$field')\"".(in_array($field, $supportedSettings, true) ? ' x-cloak' : '')
        .' class="'.$extra.'px-1.5 py-0.5 rounded text-[11px] bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300">โมเดลนี้ไม่ใช้</span>'
    );
    $selectClass = 'w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm';
@endphp

<div class="tp-card p-5 md:p-6 mt-6">
    <div class="flex flex-wrap items-start justify-between gap-2 mb-2">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white">🎚️ จูนเสียงละเอียด (ElevenLabs)</h2>
        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300">
            โมเดลที่เลือก: <code x-text="tuneModel">{{ $model }}</code>
        </span>
    </div>
    <p x-show="tuneModel !== savedModel" x-cloak
       class="mb-3 px-3 py-2 rounded-lg text-xs bg-violet-50 text-violet-800 dark:bg-violet-900/30 dark:text-violet-200">
        เลือกโมเดลใหม่แล้วแต่ยังไม่บันทึก — ป้ายด้านล่างกับปุ่ม 🎧 ลองฟัง ใช้โมเดลที่เลือกแล้ว
        แต่ห้องแชทจริงยังใช้ <code x-text="savedModel">{{ $model }}</code> จนกว่าจะกด 💾 บันทึก ในกล่องตั้งค่าด้านบน
    </p>
    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
        ปรับได้ทุกค่าที่ ElevenLabs เปิดให้ — ค่าที่โมเดลที่เลือกไม่ใช้จะมีป้าย <span class="px-1.5 py-0.5 rounded text-[11px] bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300">โมเดลนี้ไม่ใช้</span>
        (เก็บไว้ให้ ใช้ได้ทันทีถ้าเปลี่ยนโมเดล) · กด <b>🎧 ลองฟัง</b> ท้ายกล่องตัวละครเพื่อฟังค่าที่ตั้งอยู่ได้เลยโดยยังไม่บันทึก — ค่าใหม่ = ไฟล์เสียงใหม่ เสียโควต้าตามจำนวนตัวอักษร (ค่าเดิมกดซ้ำไม่เสียซ้ำ)
        <span x-show="tuneModel.startsWith('eleven_v3')" @unless($isV3) x-cloak @endunless>
            <br><span class="text-amber-700 dark:text-amber-300">v3 รับความนิ่งแค่ 3 ระดับ (0 สร้างสรรค์ / 0.5 ธรรมชาติ / 1 นิ่ง) — ระบบปัดให้อัตโนมัติ</span>
        </span>
    </p>

    <form method="POST" action="{{ route('admin.fortune.character-voices.tuning.update') }}">
        @csrf
        @method('PUT')

        {{-- ═══ ค่ารวมทุกตัวละคร ═══ --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คุณภาพไฟล์เสียง</label>
                <select name="output_format" class="{{ $selectClass }}">
                    @foreach(\App\Services\Tts\ElevenLabsTtsProvider::OUTPUT_FORMATS as $value => $label)
                        <option value="{{ $value }}" @selected(old('output_format', $tuning['output_format'] ?? 'mp3_44100_128') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ภาษา</label>
                <select name="language_code" class="{{ $selectClass }}">
                    <option value="" @selected(old('language_code', $tuning['language_code'] ?? '') !== 'th')>ให้โมเดลดูจากข้อความเอง</option>
                    <option value="th" @selected(old('language_code', $tuning['language_code'] ?? '') === 'th')>บังคับภาษาไทย (อ่านคำทับศัพท์แบบไทย)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">อ่านตัวเลข / วันที่ / ตัวย่อ</label>
                <select name="apply_text_normalization" class="{{ $selectClass }}">
                    @foreach(['auto' => 'อัตโนมัติ (แนะนำ)', 'on' => 'แปลงเป็นคำอ่านเสมอ', 'off' => 'ไม่แปลง (อ่านตามที่เขียน)'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('apply_text_normalization', $tuning['apply_text_normalization'] ?? 'auto') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- ═══ แยกตัวละคร ═══ --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            @foreach(\App\Services\Fortune\FortuneCharacterVoiceService::personas() as $pKey => $persona)
                @php
                    $t = $tp[$pKey] ?? [];
                    $state = [
                        'custom' => (bool) old("personas.$pKey.custom", $t['custom'] ?? false),
                        'v' => collect($voiceFields)->mapWithKeys(fn ($f, $k) => [$k => (float) old("personas.$pKey.$k", $t[$k] ?? $f['default'])])->all(),
                        'env' => (string) old("personas.$pKey.environment_id", $t['environment_id'] ?? ''),
                        'send_level' => (float) old("personas.$pKey.send_level", $t['send_level'] ?? 1),
                        'distance' => (float) old("personas.$pKey.distance", $t['distance'] ?? 0),
                        'pan' => (float) old("personas.$pKey.pan", $t['pan'] ?? 0),
                    ];
                    $p = "personas[$pKey]";
                @endphp
                <fieldset x-data="{{ \Illuminate\Support\Js::from($state) }}"
                          class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white/60 dark:bg-gray-800/40 space-y-4">
                    <legend class="px-2 text-base font-bold text-gray-900 dark:text-white">
                        {{ ['maemor' => '🔮 ', 'busaba' => '🌸 ', 'kunsai' => '🕯️ '][$pKey] ?? '' }}{{ $persona['label'] }}
                    </legend>

                    {{-- น้ำเสียง --}}
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-800 dark:text-gray-200">
                        <input type="hidden" name="{{ $p }}[custom]" value="0">
                        <input type="checkbox" name="{{ $p }}[custom]" value="1" x-model="custom" class="rounded text-violet-600">
                        ใช้ค่าน้ำเสียงที่จูนเอง
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 -mt-2">ปิด = ใช้ค่าที่ติดมากับเสียงนั้น (ค่าที่ตั้งด้านล่างยังเก็บไว้)</p>

                    <div class="space-y-3" :class="custom ? '' : 'opacity-50'">
                        @foreach($voiceFields as $field => $f)
                            @php [$min, $max] = \App\Services\Tts\ElevenLabsTtsProvider::SETTING_RANGES[$field]; @endphp
                            <div>
                                <div class="flex items-center justify-between gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <span>{{ $f['label'] }}
                                        {{ $unusedBadge($field, 'ml-1 ') }}
                                    </span>
                                    <span class="font-mono text-xs" x-text="v.{{ $field }}.toFixed(2)"></span>
                                </div>
                                <input type="range" name="{{ $p }}[{{ $field }}]" min="{{ $min }}" max="{{ $max }}" step="{{ $f['step'] }}"
                                       x-model.number="v.{{ $field }}" class="w-full accent-violet-600">
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ $f['hint'] }}</p>
                            </div>
                        @endforeach

                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="hidden" name="{{ $p }}[use_speaker_boost]" value="0">
                            <input type="checkbox" name="{{ $p }}[use_speaker_boost]" value="1" @checked(old("personas.$pKey.use_speaker_boost", $t['use_speaker_boost'] ?? true)) class="rounded text-violet-600">
                            เพิ่มความชัดของผู้พูด (Speaker boost)
                            {{ $unusedBadge('use_speaker_boost') }}
                        </label>

                        <div>
                            <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">
                                Clone strength (0–1)
                                {{ $unusedBadge('clone_strength', 'ml-1 ') }}
                            </label>
                            <input type="number" name="{{ $p }}[clone_strength]" min="0" max="1" step="0.05" placeholder="เว้นว่าง = ไม่ส่ง"
                                   value="{{ old("personas.$pKey.clone_strength", $t['clone_strength'] ?? '') }}" class="{{ $selectClass }}">
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">ใช้กับเสียงโคลนแบบ Professional ที่จูนกับ v4 เท่านั้น · เสียงมาตรฐานเว้นว่างไว้</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Seed (ให้ได้เสียงเดิมซ้ำ)</label>
                        <input type="number" name="{{ $p }}[seed]" min="0" max="{{ \App\Services\Tts\ElevenLabsTtsProvider::SEED_MAX }}" step="1" placeholder="เว้นว่าง = สุ่มทุกครั้ง"
                               value="{{ old("personas.$pKey.seed", $t['seed'] ?? '') }}" class="{{ $selectClass }}">
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">ตัวเลข 0–4,294,967,295 · ElevenLabs พยายามให้ออกมาเหมือนเดิมแต่ไม่รับประกัน 100%</p>
                    </div>

                    {{-- เอฟเฟกต์ --}}
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700 space-y-3">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">✨ เอฟเฟกต์ (ไม่เสียโควต้าเพิ่ม)</p>
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">ฟิลเตอร์เสียง</label>
                            <select name="{{ $p }}[filter_preset_id]" class="{{ $selectClass }}">
                                <option value="">ไม่ใส่</option>
                                @foreach($audioEffects['effects']['filter_presets'] as $e)
                                    <option value="{{ $e['id'] }}" @selected(old("personas.$pKey.filter_preset_id", $t['filter_preset_id'] ?? '') === $e['id'])>{{ $effectLabel($e) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">ห้อง / เสียงก้อง</label>
                            <select name="{{ $p }}[environment_id]" x-model="env" class="{{ $selectClass }}">
                                <option value="">ไม่ใส่</option>
                                @foreach($audioEffects['effects']['environments'] as $e)
                                    <option value="{{ $e['id'] }}">{{ $effectLabel($e) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div x-show="env !== ''">
                            <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400"><span>ความก้อง</span><span class="font-mono" x-text="send_level.toFixed(2)"></span></div>
                            <input type="range" name="{{ $p }}[send_level]" min="0" max="1" step="0.05" x-model.number="send_level" class="w-full accent-violet-600">
                        </div>
                        <div>
                            <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400"><span>ระยะห่าง (0 ชิดไมค์ – 1 ไกล)</span><span class="font-mono" x-text="distance.toFixed(2)"></span></div>
                            <input type="range" name="{{ $p }}[distance]" min="0" max="1" step="0.05" x-model.number="distance" class="w-full accent-violet-600">
                        </div>
                        <div>
                            <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400"><span>ซ้าย – กลาง – ขวา</span><span class="font-mono" x-text="pan.toFixed(2)"></span></div>
                            <input type="range" name="{{ $p }}[pan]" min="-1" max="1" step="0.05" x-model.number="pan" class="w-full accent-violet-600">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">เสียงพื้นหลัง</label>
                            <select name="{{ $p }}[background_noise_id]" class="{{ $selectClass }}">
                                <option value="">ไม่ใส่</option>
                                @foreach($audioEffects['effects']['background_noises'] as $e)
                                    <option value="{{ $e['id'] }}" @selected(old("personas.$pKey.background_noise_id", $t['background_noise_id'] ?? '') === $e['id'])>{{ $effectLabel($e) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- 🎧 ลองฟังค่าที่ตั้งอยู่ตอนนี้ก่อนบันทึก (previewTuning อยู่ใน characterVoices() ของหน้าหลัก) --}}
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700 space-y-2">
                        <button type="button" @click="previewTuning('{{ $pKey }}', $el.closest('form'))"
                                :disabled="tuningPreview['{{ $pKey }}'].busy"
                                class="tp-btn w-full justify-center disabled:opacity-60 disabled:cursor-wait">
                            <span x-show="!tuningPreview['{{ $pKey }}'].busy">🎧 ลองฟังค่าที่ตั้งตอนนี้</span>
                            <span x-show="tuningPreview['{{ $pKey }}'].busy" x-cloak>⏳ ElevenLabs กำลังสร้างเสียง…</span>
                        </button>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            ใช้เสียงที่เลือกไว้ของ{{ $persona['label'] }} อ่านข้อความทดสอบในกล่องด้านบน · ยังไม่บันทึกค่า กด 💾 ด้านล่างเมื่อพอใจ
                        </p>
                        <template x-if="tuningPreview['{{ $pKey }}'].clip?.url">
                            <audio controls autoplay class="w-full h-10" :src="tuningPreview['{{ $pKey }}'].clip.url"></audio>
                        </template>
                        <p x-show="tuningPreview['{{ $pKey }}'].error" x-cloak class="text-xs text-red-700 dark:text-red-300"
                           x-text="tuningPreview['{{ $pKey }}'].error"></p>
                    </div>
                </fieldset>
            @endforeach
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                รายชื่อเอฟเฟกต์: {{ $audioEffects['live'] ? 'ดึงสดจากบัญชี ElevenLabs' : 'รายชื่อสำรอง (ดึงสดไม่ได้)' }}
            </p>
            <button type="submit" class="tp-btn w-full md:w-auto justify-center">💾 บันทึกค่าจูนเสียง</button>
        </div>
    </form>
</div>
