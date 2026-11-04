@extends('layouts.admin-v4')

@section('title', '🗂️ คลังบทพูด & สร้างเสียง')

@php
    $input = 'w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm';
    $label = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
    $voiceOptions = collect($voices)->map(fn ($v) => ['id' => $v['id'], 'name' => explode(' — ', $v['name'])[0].(($v['gender'] ?? '') === 'male' ? ' (ชาย)' : '')])->values()->all();
    $modelOptions = collect($models)->map(fn ($m) => ['id' => $m['id'], 'name' => $m['name'], 'cost' => (float) $m['cost']])->values()->all();
    $characterModel = $character['model'] ?? null;
@endphp

@section('content')
<div x-data="voiceLibrary()" x-cloak>
    <div class="mb-5">
        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 tracking-wide">หลังบ้าน · ระบบดูดวง · เสียงพากย์ตัวละคร</div>
        <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white mt-1">🗂️ คลังบทพูด & สร้างเสียง</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            บทสั้นที่อัดไว้ใช้ซ้ำในโหมดคุยด้วยเสียง (คำรับ ชวนเล่า ขอคิด ลาก่อน …) — เล่นจากไฟล์ ไม่เสียเครดิตตอนลูกค้าฟัง ·
            ข้อความ+เสียง+โมเดลเดิม = ไฟล์เดิม กดซ้ำไม่เสียเครดิตซ้ำ
        </p>
    </div>

    @include('admin.fortune.character-voices._tabs')
    @include('admin.fortune.character-voices._flash')

    {{-- ═══ เลือกตัวละคร ═══ --}}
    <div class="flex flex-wrap gap-2 mb-4">
        @foreach($catalog as $key => $c)
            <a href="{{ route('admin.fortune.character-voices.library', ['persona' => $key]) }}"
               @class([
                   'px-3 py-1.5 rounded-full text-sm font-semibold border transition',
                   'bg-violet-600 border-violet-600 text-white' => $key === $persona,
                   'border-gray-300 text-gray-700 hover:bg-violet-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700' => $key !== $persona,
                   'opacity-60' => ! $c['active'],
               ])>{{ $c['label'] }}@unless($c['active']) (ปิด)@endunless</a>
        @endforeach
        <a href="{{ route('admin.fortune.character-voices.characters') }}" class="px-3 py-1.5 rounded-full text-sm border border-dashed border-gray-400 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">➕ เพิ่มตัวละคร</a>
    </div>

    @if($character)
        {{-- ═══ แผงสั่งสร้าง ═══ --}}
        <div class="tp-card p-5 mb-4 space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $character['label'] }} <code class="text-xs font-normal text-gray-500">{{ $persona }}</code></h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        ค่าของตัวละคร: เสียง <b x-text="voiceName(characterVoice)"></b> · โมเดล <b x-text="characterModel || globalModel"></b>
                        · <a href="{{ route('admin.fortune.character-voices.characters') }}" class="text-violet-700 dark:text-violet-300 hover:underline">เปลี่ยน</a>
                    </p>
                </div>
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    มีเสียงแล้ว <b x-text="lines.filter(l => hasAudio(l)).length"></b> / <span x-text="lines.length"></span> บท
                </div>
            </div>

            @unless($hasKey)
                <p class="text-sm text-amber-700 dark:text-amber-300">❗ ยังไม่ได้ตั้ง ElevenLabs key — ตั้งในแท็บ "ตั้งค่า & เลือกเสียง" ก่อนสร้างเสียง</p>
            @endunless
            @unless($character['active'])
                <p class="text-sm text-amber-700 dark:text-amber-300">❗ ตัวละครนี้ปิดอยู่ — เปิดในแท็บ "ตัวละคร" ก่อนสร้างเสียง</p>
            @endunless

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="{{ $label }}">โมเดลของรอบนี้</label>
                    <select x-model="batchModel" class="{{ $input }}">
                        <option value="">ตามที่ตั้งไว้ของแต่ละบท / ตัวละคร</option>
                        <template x-for="m in models" :key="m.id">
                            <option :value="m.id" x-text="'ใช้ ' + m.name + ' ทั้งหมด — ' + m.cost + ' เครดิต/ตัวอักษร'"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">เสียงของรอบนี้</label>
                    <select x-model="batchVoice" class="{{ $input }}">
                        <option value="">ตามที่ตั้งไว้ของแต่ละบท / ตัวละคร</option>
                        <template x-for="v in voices" :key="v.id">
                            <option :value="v.id" x-text="'ใช้เสียง ' + v.name + ' ทั้งหมด'"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" class="tp-btn" :disabled="running || !selectedLines().length" @click="generateMany(selectedLines())">
                    🎙️ สร้างที่เลือก (<span x-text="selectedLines().length"></span>)
                </button>
                <button type="button" class="tp-btn" :disabled="running || !missingLines().length" @click="generateMany(missingLines())">
                    🎙️ สร้างที่ยังไม่มีเสียง (<span x-text="missingLines().length"></span>)
                </button>
                <button type="button" class="tp-btn" :disabled="running || !activeLines().length" @click="generateMany(activeLines())">
                    🎙️ สร้างทั้งหมด (<span x-text="activeLines().length"></span>)
                </button>
                <button type="button" x-show="running" class="tp-btn text-red-700 dark:text-red-300" @click="stop = true">⏹️ หยุด</button>
                <span x-show="running" class="text-sm text-gray-600 dark:text-gray-300" x-text="progress"></span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                สร้างทีละบทตามลำดับ (ไม่ยิงพร้อมกัน) · ประเมินเครดิตให้ก่อนกดยืนยัน · บทที่เคยสร้างด้วยเสียง/โมเดลเดียวกันจะได้ไฟล์เดิมทันที ไม่เสียเครดิต
            </p>
        </div>

        {{-- ═══ ตารางบท ═══ --}}
        <div class="tp-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/60 text-xs text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-3 py-2 text-left"><input type="checkbox" class="rounded text-violet-600" :checked="lines.length && selected.length === lines.length" @change="selected = $event.target.checked ? lines.map(l => l.id) : []" aria-label="เลือกทั้งหมด"></th>
                            <th class="px-3 py-2 text-left font-medium">บท</th>
                            <th class="px-3 py-2 text-left font-medium min-w-[16rem]">ข้อความ</th>
                            <th class="px-3 py-2 text-left font-medium">เสียง / โมเดลของบทนี้</th>
                            <th class="px-3 py-2 text-left font-medium min-w-[14rem]">ไฟล์เสียง</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <template x-for="line in lines" :key="line.id">
                            <tr :class="line.is_active ? '' : 'opacity-50'" class="align-top">
                                <td class="px-3 py-3"><input type="checkbox" class="rounded text-violet-600" :value="line.id" x-model.number="selected" :aria-label="'เลือก ' + line.line_key"></td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <code class="text-xs text-gray-800 dark:text-gray-100" x-text="line.line_key"></code>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400" x-text="categories[line.category] || line.category"></div>
                                </td>
                                <td class="px-3 py-3">
                                    <textarea rows="2" x-model="line.script" @input="line.dirty = true"
                                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></textarea>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                        <span x-text="plainLength(line.script)"></span> ตัวอักษร ≈ <span x-text="estimate([line])"></span> เครดิต
                                    </div>
                                </td>
                                <td class="px-3 py-3 space-y-1 min-w-[12rem]">
                                    <select x-model="line.voice_id" @change="line.dirty = true" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs">
                                        <option value="">เสียงของตัวละคร</option>
                                        <template x-for="v in voices" :key="v.id"><option :value="v.id" x-text="v.name" :selected="v.id === line.voice_id"></option></template>
                                    </select>
                                    <select x-model="line.model" @change="line.dirty = true" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs">
                                        <option value="">โมเดลของตัวละคร</option>
                                        <template x-for="m in models" :key="m.id"><option :value="m.id" x-text="m.name" :selected="m.id === line.model"></option></template>
                                    </select>
                                    <label class="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400">
                                        <input type="checkbox" x-model="line.is_active" @change="line.dirty = true" class="rounded text-violet-600"> ใช้ในห้องแชท
                                    </label>
                                </td>
                                <td class="px-3 py-3">
                                    <template x-if="hasAudio(line)">
                                        <div>
                                            <audio controls preload="none" class="w-full h-9" :src="line.clip.url"></audio>
                                            <div class="text-[11px] text-gray-500 dark:text-gray-400" x-text="voiceName(line.clip.voice_id) + ' · ' + (line.clip.model || '')"></div>
                                        </div>
                                    </template>
                                    <p x-show="!hasAudio(line) && !busy[line.id] && !line.error" class="text-xs text-gray-500 dark:text-gray-400">ยังไม่มีเสียง</p>
                                    <p x-show="busy[line.id]" class="text-xs text-violet-700 dark:text-violet-300">⏳ กำลังสร้าง…</p>
                                    <p x-show="line.error" class="text-xs text-red-700 dark:text-red-300" x-text="line.error"></p>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap space-y-1 text-right">
                                    <button type="button" class="tp-btn w-full justify-center text-xs" :disabled="running || busy[line.id]" @click="generateOne(line)">🎙️ สร้าง</button>
                                    <button type="button" x-show="line.dirty" class="tp-btn w-full justify-center text-xs" @click="saveLine(line)">💾 บันทึก</button>
                                    <button type="button" class="tp-btn w-full justify-center text-xs text-red-700 dark:text-red-300" :disabled="running" @click="deleteLine(line)">🗑️ ลบ</button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="!lines.length">
                            <td colspan="6" class="px-3 py-10 text-center text-gray-500 dark:text-gray-400">ยังไม่มีบทในคลังของตัวละครนี้ — เพิ่มด้านล่าง</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ═══ เพิ่มบท ═══ --}}
        <form method="POST" action="{{ route('admin.fortune.character-voices.library.lines.store') }}" class="tp-card p-5 mt-4 space-y-3"
              x-data="{ cat: @js(old('category', 'ack')), custom: '' }">
            @csrf
            <input type="hidden" name="persona" value="{{ $persona }}">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">➕ เพิ่มบท</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="{{ $label }}">หมวด</label>
                    <select x-model="cat" class="{{ $input }}">
                        @foreach($categories as $key => $name)
                            <option value="{{ $key }}">{{ $name }} ({{ $key }})</option>
                        @endforeach
                        <option value="__new">+ หมวดใหม่…</option>
                    </select>
                    <input x-show="cat === '__new'" x-model="custom" type="text" placeholder="ชื่อหมวด a-z และ _ เช่น greet" pattern="[a-z][a-z_]{1,19}"
                           class="{{ $input }} mt-2 font-mono">
                    <input type="hidden" name="category" :value="cat === '__new' ? custom : cat">
                </div>
                <div>
                    <label class="{{ $label }}">เสียง</label>
                    <select name="voice_id" class="{{ $input }}">
                        <option value="">เสียงของตัวละคร</option>
                        @foreach($voiceOptions as $v)
                            <option value="{{ $v['id'] }}">{{ $v['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">โมเดล</label>
                    <select name="model" class="{{ $input }}">
                        <option value="">โมเดลของตัวละคร</option>
                        @foreach($modelOptions as $m)
                            <option value="{{ $m['id'] }}">{{ $m['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="{{ $label }}">บทพูด (ใส่แท็กอารมณ์ได้ เช่น [softly] [warmly] [excited])</label>
                <textarea name="script" rows="2" required maxlength="{{ \App\Services\Fortune\FortuneCharacterVoiceService::MAX_SAMPLE_CHARS }}" class="{{ $input }}">{{ old('script') }}</textarea>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="tp-btn w-full md:w-auto justify-center">➕ เพิ่มบท</button>
            </div>
        </form>
    @else
        <div class="tp-card p-10 text-center text-gray-500 dark:text-gray-400">ยังไม่มีตัวละคร — เพิ่มในแท็บ "ตัวละคร"</div>
    @endif

    <div x-show="toast" x-transition class="fixed bottom-4 right-4 z-50 max-w-sm px-4 py-3 rounded-xl shadow-lg text-sm text-white"
         :class="toastOk ? 'bg-emerald-600' : 'bg-red-600'" x-text="toast"></div>
</div>

<script>
    function voiceLibrary() {
        return {
            lines: {{ \Illuminate\Support\Js::from($lines->map(fn ($l) => \App\Http\Controllers\Admin\FortuneVoiceLibraryController::linePayload($l) + ['dirty' => false, 'error' => ''])->values()) }},
            voices: {{ \Illuminate\Support\Js::from($voiceOptions) }},
            models: {{ \Illuminate\Support\Js::from($modelOptions) }},
            categories: {{ \Illuminate\Support\Js::from($categories) }},
            characterVoice: @js($characterVoice),
            characterModel: @js($characterModel),
            globalModel: @js($globalModel),
            batchModel: '',
            batchVoice: '',
            selected: [],
            busy: {},
            running: false,
            stop: false,
            progress: '',
            toast: '',
            toastOk: true,
            CSRF: '{{ csrf_token() }}',
            URL: @json(url('/admin/fortune/character-voices')),

            hasAudio(line) { return !!(line.clip && line.clip.status === 'completed' && line.clip.url); },
            activeLines() { return this.lines.filter(l => l.is_active); },
            missingLines() { return this.lines.filter(l => l.is_active && !this.hasAudio(l)); },
            selectedLines() { return this.lines.filter(l => this.selected.includes(l.id)); },
            voiceName(id) { const v = this.voices.find(x => x.id === id); return v ? v.name : (id || '-'); },
            // ตัวอักษรที่คิดเครดิต — ไม่นับแท็กอารมณ์ [softly]
            plainLength(text) { return (text || '').replace(/\[[a-z_ ]+\]/gi, '').replace(/\s+/g, ' ').trim().length; },
            modelFor(line) { return this.batchModel || line.model || this.characterModel || this.globalModel; },
            estimate(list) {
                return Math.ceil(list.reduce((sum, l) => {
                    const m = this.models.find(x => x.id === this.modelFor(l));
                    return sum + this.plainLength(l.script) * (m ? m.cost : 1);
                }, 0)).toLocaleString('th-TH');
            },
            notify(message, ok = true) {
                this.toast = message;
                this.toastOk = ok;
                clearTimeout(this._t);
                this._t = setTimeout(() => this.toast = '', 4000);
            },
            async request(url, method, data) {
                const res = await fetch(url, {
                    method,
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.CSRF },
                    body: data ? JSON.stringify(data) : undefined,
                });
                let body = {};
                try { body = await res.json(); } catch (e) { body = {}; }
                if (res.status === 419) body.error = 'หน้าเว็บหมดอายุ กรุณารีเฟรชหน้า';
                if (res.status === 429 && !body.error) body.error = 'สั่งถี่เกินไป รอสักครู่แล้วลองใหม่';
                if (res.status === 422 && !body.error) body.error = body.message || 'ข้อมูลไม่ถูกต้อง';
                return { ok: res.ok, body };
            },
            async generateOne(line) {
                if (line.dirty && !confirm('บทนี้แก้แล้วยังไม่บันทึก — บันทึกก่อนแล้วค่อยสร้างไหม?')) return;
                if (line.dirty && !(await this.saveLine(line))) return;
                await this.generate(line);
            },
            async generate(line) {
                this.busy[line.id] = true;
                line.error = '';
                try {
                    const { body } = await this.request(`${this.URL}/library/lines/${line.id}/generate`, 'POST', { model: this.batchModel, voice_id: this.batchVoice });
                    let clip = body.data || null;
                    if (!clip) { line.error = body.error || 'สร้างเสียงไม่สำเร็จ'; return false; }
                    // อีกคำขอกำลังสร้างข้อความเดียวกันอยู่ → เช็คซ้ำทุก 3 วิ (ราว 1 นาที)
                    for (let i = 0; i < 20 && clip.status === 'pending'; i++) {
                        await new Promise(r => setTimeout(r, 3000));
                        const r = await this.request(`${this.URL}/clips/${clip.clip_id}`, 'GET');
                        clip = r.body.data || clip;
                    }
                    line.clip = { ...clip, model: this.modelFor(line) };
                    if (clip.status === 'failed') { line.error = clip.error || 'สร้างเสียงไม่สำเร็จ'; return false; }
                    if (clip.status === 'pending') { line.error = 'ยังไม่เสร็จ — กดสร้างอีกครั้งเพื่อเช็คผล (ไม่เสียเครดิตซ้ำ)'; return false; }
                    return true;
                } catch (e) {
                    line.error = 'เชื่อมต่อไม่สำเร็จ ลองใหม่อีกครั้ง';
                    return false;
                } finally {
                    this.busy[line.id] = false;
                }
            },
            async generateMany(list) {
                list = list.filter(l => !l.dirty);
                if (!list.length) { this.notify('บทที่เลือกยังมีที่แก้ไม่ได้บันทึก — กด 💾 ก่อน', false); return; }
                if (!confirm(`สร้างเสียง ${list.length} บท — ใช้ไม่เกินราว ${this.estimate(list)} เครดิต (บทที่เคยสร้างแบบเดียวกันไม่เสียซ้ำ)\nเริ่มเลยไหม?`)) return;
                this.running = true;
                this.stop = false;
                let ok = 0;
                try {
                    // ทีละบท — ElevenLabs จำกัดคำขอพร้อมกันตามแพ็ก และกดหยุดกลางทางได้
                    for (const [i, line] of list.entries()) {
                        if (this.stop) break;
                        this.progress = `กำลังสร้าง ${i + 1}/${list.length} (${line.line_key})`;
                        if (await this.generate(line)) ok++;
                    }
                    this.notify(`${this.stop ? 'หยุดแล้ว — ' : ''}สร้างเสียงสำเร็จ ${ok}/${list.length} บท`, ok > 0 || this.stop);
                } finally {
                    this.running = false;
                    this.progress = '';
                }
            },
            async saveLine(line) {
                const { ok, body } = await this.request(`${this.URL}/library/lines/${line.id}`, 'PUT', {
                    script: line.script, voice_id: line.voice_id || null, model: line.model || null, is_active: line.is_active,
                });
                if (!ok) { this.notify(body.error || 'บันทึกไม่สำเร็จ', false); return false; }
                Object.assign(line, body.data, { dirty: false, error: '' });
                this.notify(body.message || 'บันทึกแล้ว');
                return true;
            },
            async deleteLine(line) {
                if (!confirm(`ลบบท ${line.line_key}?\n"${line.script}"\nไฟล์เสียงที่สร้างไว้ยังอยู่ในตารางคลิป`)) return;
                const { ok, body } = await this.request(`${this.URL}/library/lines/${line.id}`, 'DELETE');
                if (!ok) { this.notify(body.error || 'ลบไม่สำเร็จ', false); return; }
                this.lines = this.lines.filter(l => l.id !== line.id);
                this.selected = this.selected.filter(id => id !== line.id);
                this.notify(body.message || 'ลบแล้ว');
            },
        };
    }
</script>
@endsection
