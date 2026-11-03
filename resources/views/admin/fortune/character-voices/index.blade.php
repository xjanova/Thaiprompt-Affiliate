@extends('layouts.admin-v4')

@section('title', '🎭 เสียงพากย์ตัวละคร')

@php
    // ข้อมูลตัวละครสำหรับ Alpine — samples/busy เป็น object (คีย์ = voice_id) ไม่ใช่ array
    $personaData = collect($personas)->map(fn ($p) => [
        'label' => $p['label'],
        'brief' => $p['brief'],
        'text' => $p['sample_text'],
        'selected_voice' => $p['selected_voice'],
        'samples' => (object) $p['samples'],
        'busy' => (object) [],
        'busyAll' => false,
    ])->all();
@endphp

@section('content')
<div x-data="characterVoices()" x-cloak>

    {{-- Header --}}
    <div class="flex flex-wrap items-end justify-between gap-3 mb-5">
        <div>
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 tracking-wide">หลังบ้าน · ระบบดูดวง · จัดการเสียง</div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white mt-1">🎭 เสียงพากย์ตัวละคร</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                แม่หมอจันทรา · น้องบุษบา · แม่หมอโหมดคุณไสย · ElevenLabs v3 (ต่อตรงด้วย key ของเรา) ·
                สร้างเสียงเฉพาะตอนมีคนกดฟัง แล้วเก็บไฟล์ไว้ใช้ซ้ำ
            </p>
        </div>
        <a href="{{ route('admin.fortune.voice.index') }}" class="tp-btn">← จัดการเสียง</a>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="mb-4 p-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-300 dark:border-emerald-700 text-emerald-800 dark:text-emerald-200 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700 text-red-800 dark:text-red-200 text-sm">
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700 text-red-800 dark:text-red-200 text-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- ════════════════ ElevenLabs key ════════════════ --}}
    <div class="tp-card p-5 md:p-6 mb-6">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">🔑 ElevenLabs API key</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    สร้าง key ได้ที่ <a href="https://elevenlabs.io/app/developers/api-keys" target="_blank" rel="noopener" class="text-sky-600 dark:text-sky-400 underline">elevenlabs.io → Developers → API Keys</a>
                    (เปิดสิทธิ์ Text to Speech + Voices read) · เก็บแบบเข้ารหัส ไม่แสดง key เต็มที่ไหนอีก · ใช้โควต้าตัวอักษรของบัญชี ElevenLabs
                </p>
            </div>
            <div class="text-right">
                @if($hasKey)
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">✅ ตั้งแล้ว <code class="font-mono">{{ $maskedKey }}</code></span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">❗ ยังไม่ได้ตั้ง key</span>
                @endif
            </div>
        </div>

        <form method="POST" action="{{ route('admin.fortune.character-voices.settings.update') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @csrf
            @method('PUT')
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $hasKey ? 'เปลี่ยน key (เว้นว่าง = ใช้ key เดิม)' : 'วาง key ที่นี่' }}
                </label>
                <input type="password" name="elevenlabs_api_key" autocomplete="new-password" spellcheck="false"
                       placeholder="sk_…"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-mono">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ระบบทดสอบ key กับ ElevenLabs (เช็คโควต้า ไม่เสียโควต้า) ก่อนบันทึก</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">โมเดลเสียง</label>
                <input type="text" name="elevenlabs_tts_model" value="{{ old('elevenlabs_tts_model', $model) }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-mono">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ค่าเริ่มต้น <code>eleven_v3</code> (พูดไทยได้ ใส่แท็กอารมณ์ได้)</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เพดานตัวอักษรต่อวัน (ห้องแชท)</label>
                <input type="number" min="1000" max="2000000" name="character_voice_daily_char_limit" value="{{ old('character_voice_daily_char_limit', $dailyLimit) }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">วันนี้ใช้ไป {{ number_format($usedToday) }} / {{ number_format($dailyLimit) }} ตัวอักษร (นับเฉพาะที่สั่งสร้างใหม่ ไม่นับที่เล่นจากแคช)</p>
            </div>
            <div class="flex flex-col justify-end gap-2">
                <button type="submit" class="tp-btn w-full justify-center">💾 บันทึก</button>
                @if($hasKey)
                    <button type="button" @click="checkCredits()" class="tp-btn w-full justify-center" :disabled="creditsLoading">
                        <span x-text="creditsLoading ? 'กำลังเช็ค…' : '💳 เช็คโควต้า'"></span>
                    </button>
                @endif
            </div>
        </form>

        <div class="mt-3 text-sm" x-show="creditsText" x-text="creditsText"
             :class="creditsOk ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300'"></div>

        @if($hasKey)
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    ไฟล์เสียงในห้องแชทที่เก็บไว้แล้ว {{ number_format($chatClipCount) }} ไฟล์ ·
                    รายชื่อเสียง: {{ $voices['live'] ? 'ดึงสดจากบัญชี ElevenLabs' : 'รายชื่อสำรอง (ดึงสดไม่ได้)' }}
                </p>
                <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('admin.fortune.character-voices.refresh-voices') }}">
                        @csrf
                        <button type="submit" class="tp-btn">🔄 รีเฟรชรายชื่อเสียง</button>
                    </form>
                    <form method="POST" action="{{ route('admin.fortune.character-voices.key.delete') }}"
                          onsubmit="return confirm('ลบ ElevenLabs key ใช่ไหมคะ? เสียงตัวละครจะหยุดสร้างใหม่ (ไฟล์ที่เก็บไว้ยังเล่นได้)')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800 dark:hover:bg-red-900/40 transition">🗑️ ลบ key</button>
                    </form>
                </div>
            </div>
        @endif
    </div>

    {{-- ════════════════ ตัวละคร ════════════════ --}}
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-gray-600 dark:text-gray-300">
            กด <b>สร้างเสียงตัวอย่าง</b> ทีละเสียง (หรือทั้งหมด) ฟังเทียบ แล้วกด <b>เลือกเสียงนี้</b> ·
            แท็กอารมณ์ของ v3 เช่น <code>[softly]</code> <code>[laughs]</code> <code>[whispers]</code> <code>[sighs]</code> <code>[excited]</code> จะกลายเป็นน้ำเสียง ไม่ถูกอ่านออกมา
        </p>
        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" x-model="showMale" class="rounded text-sky-600"> แสดงเสียงผู้ชายด้วย
        </label>
    </div>

    @unless($hasKey)
        <div class="mb-4 p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 text-amber-800 dark:text-amber-200 text-sm">
            ตั้ง ElevenLabs key ด้านบนก่อน จึงจะสร้างเสียงตัวอย่างได้ (ระหว่างนี้กดฟังเสียงต้นฉบับภาษาอังกฤษได้ฟรี)
        </div>
    @endunless

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <template x-for="(p, pKey) in personas" :key="pKey">
            <div class="tp-card p-5 md:p-6">
                <div class="flex flex-wrap items-start justify-between gap-2 mb-3">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white" x-text="({ maemor: '🔮 ', busaba: '🌸 ', kunsai: '🕯️ ' }[pKey] || '') + p.label"></h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="p.brief"></p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-violet-100 text-violet-800 dark:bg-violet-900/30 dark:text-violet-300">
                        ใช้อยู่: <span x-text="voiceName(p.selected_voice)"></span>
                    </span>
                </div>

                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประโยคตัวอย่าง</label>
                <textarea x-model="p.text" rows="3" maxlength="{{ \App\Services\Fortune\FortuneCharacterVoiceService::MAX_SAMPLE_CHARS }}"
                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></textarea>
                <div class="flex flex-wrap items-center justify-between gap-2 mt-2 mb-4">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        <span x-text="[...p.text].length"></span> ตัวอักษร/เสียง · ข้อความ+เสียงเดิมกดซ้ำไม่เสียโควต้าซ้ำ
                    </span>
                    @if($hasKey)
                        <button type="button" class="tp-btn" @click="generateAll(pKey)" :disabled="p.busyAll">
                            <span x-text="p.busyAll ? 'กำลังสร้างทีละเสียง…' : '✨ สร้างเสียงตัวอย่างทุกเสียงที่แสดง'"></span>
                        </button>
                    @endif
                </div>

                <div class="space-y-3">
                    <template x-for="v in visibleVoices()" :key="pKey + v.id">
                        <div class="p-3 rounded-xl border transition"
                             :class="p.selected_voice === v.id
                                ? 'border-violet-400 bg-violet-50/70 dark:border-violet-500 dark:bg-violet-900/20'
                                : 'border-gray-200 dark:border-gray-700 bg-white/60 dark:bg-gray-800/40'">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                    <span x-text="v.name"></span>
                                    <span x-show="p.selected_voice === v.id" class="ml-1 text-xs text-violet-700 dark:text-violet-300">✔ เสียงที่ใช้</span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <a :href="'https://static.aiquickdraw.com/elevenlabs/voice/' + v.id + '.mp3'" target="_blank" rel="noopener"
                                       class="px-3 py-1.5 rounded-lg text-xs border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">🎧 ต้นฉบับ (อังกฤษ ฟรี)</a>
                                    @if($hasKey)
                                        <button type="button" @click="generate(pKey, v.id)" :disabled="isBusy(pKey, v.id)"
                                                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-sky-600 hover:bg-sky-700 text-white disabled:opacity-50">
                                            <span x-text="isBusy(pKey, v.id) ? 'กำลังสร้าง…' : 'สร้างเสียงตัวอย่าง'"></span>
                                        </button>
                                    @endif
                                    <button type="button" @click="select(pKey, v.id)" x-show="p.selected_voice !== v.id"
                                            class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-violet-600 hover:bg-violet-700 text-white">เลือกเสียงนี้</button>
                                </div>
                            </div>
                            <template x-if="sample(pKey, v.id)?.url">
                                <audio controls preload="none" class="w-full mt-2 h-10" :src="sample(pKey, v.id).url"></audio>
                            </template>
                            <p x-show="sample(pKey, v.id)?.status === 'pending'" class="text-xs text-amber-700 dark:text-amber-300 mt-1">⏳ ElevenLabs กำลังสร้างเสียง…</p>
                            <p x-show="sample(pKey, v.id)?.error" class="text-xs text-red-700 dark:text-red-300 mt-1" x-text="sample(pKey, v.id)?.error"></p>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    {{-- toast --}}
    <div x-show="toast" x-transition class="fixed bottom-4 right-4 z-50 max-w-sm px-4 py-3 rounded-xl shadow-lg text-sm text-white"
         :class="toastOk ? 'bg-emerald-600' : 'bg-red-600'" x-text="toast"></div>
</div>

<script>
    function characterVoices() {
        // ⚠️ ใช้ Js::from — directive json ของ Blade แยกอาร์กิวเมนต์ด้วยจุลภาค ส่ง array หลายคีย์ตรงๆ จะพัง
        const personas = {{ \Illuminate\Support\Js::from($personaData) }};

        return {
            personas,
            voices: @json($voices['voices']),
            showMale: false,
            creditsLoading: false,
            creditsText: '',
            creditsOk: true,
            toast: '',
            toastOk: true,
            CSRF: '{{ csrf_token() }}',

            visibleVoices() {
                return this.voices.filter(v => this.showMale || v.gender !== 'male');
            },
            voiceName(id) {
                const v = this.voices.find(x => x.id === id);
                return v ? v.name.split(' — ')[0] : id;
            },
            sample(pKey, voiceId) {
                return this.personas[pKey].samples[voiceId] || null;
            },
            isBusy(pKey, voiceId) {
                return !!this.personas[pKey].busy[voiceId];
            },
            notify(message, ok = true) {
                this.toast = message;
                this.toastOk = ok;
                clearTimeout(this._toastTimer);
                this._toastTimer = setTimeout(() => this.toast = '', 4000);
            },
            async request(url, options = {}) {
                const res = await fetch(url, {
                    ...options,
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.CSRF, ...(options.headers || {}) },
                });
                let body = {};
                try { body = await res.json(); } catch (e) { body = {}; }
                if (res.status === 419) body.error = 'หน้าเว็บหมดอายุ กรุณารีเฟรชหน้า';
                if (res.status === 429 && !body.error) body.error = 'กดถี่เกินไป รอสักครู่แล้วลองใหม่';
                if (res.status === 422 && !body.error && body.message) body.error = body.message;
                return { ok: res.ok, status: res.status, body };
            },
            async checkCredits() {
                this.creditsLoading = true;
                try {
                    const { ok, body } = await this.request(@json(route('admin.fortune.character-voices.credits')), { method: 'POST' });
                    this.creditsOk = ok;
                    this.creditsText = ok
                        ? (body.balance === null ? '💳 key นี้ไม่มีสิทธิ์ดูโควต้า (สร้างเสียงได้ตามปกติ)' : `💳 เหลือ ${Number(body.balance).toLocaleString('th-TH')} / ${Number(body.limit).toLocaleString('th-TH')} ตัวอักษรในรอบบิลนี้`)
                        : (body.error || 'เช็คโควต้าไม่สำเร็จ');
                } catch (e) {
                    this.creditsOk = false;
                    this.creditsText = 'เชื่อมต่อไม่สำเร็จ ลองใหม่อีกครั้ง';
                } finally {
                    this.creditsLoading = false;
                }
            },
            async generate(pKey, voiceId) {
                const p = this.personas[pKey];
                if (p.busy[voiceId]) return; // กันกดซ้ำระหว่างรอ
                p.busy[voiceId] = true;
                try {
                    const { body } = await this.request(@json(route('admin.fortune.character-voices.sample')), {
                        method: 'POST',
                        body: JSON.stringify({ persona: pKey, voice_id: voiceId, text: p.text }),
                    });
                    let clip = body.data || null;
                    if (!clip) {
                        p.samples[voiceId] = { status: 'failed', error: body.error || 'สร้างเสียงไม่สำเร็จ' };
                        return;
                    }
                    p.samples[voiceId] = clip;

                    // ยังไม่เสร็จ (อีกคำขอกำลังสร้างข้อความเดียวกันอยู่) → เช็คซ้ำทุก 3 วิ สูงสุดราว 3 นาที
                    for (let i = 0; i < 60 && clip.status === 'pending'; i++) {
                        await new Promise((resolve) => setTimeout(resolve, 3000));
                        const r = await this.request(@json(url('/admin/fortune/character-voices/clips')) + '/' + clip.clip_id);
                        clip = r.body.data || { status: 'failed', error: r.body.error || 'เช็คสถานะไม่สำเร็จ' };
                        p.samples[voiceId] = clip;
                    }
                    if (clip.status === 'pending') {
                        p.samples[voiceId] = { ...clip, status: 'failed', error: 'ยังไม่เสร็จ — กดสร้างอีกครั้งเพื่อเช็คผล (ไม่เสียโควต้าซ้ำ)' };
                    }
                } catch (e) {
                    p.samples[voiceId] = { status: 'failed', error: 'เชื่อมต่อไม่สำเร็จ ลองใหม่อีกครั้ง' };
                } finally {
                    p.busy[voiceId] = false;
                }
            },
            async generateAll(pKey) {
                const p = this.personas[pKey];
                if (p.busyAll) return;
                p.busyAll = true;
                try {
                    // ทีละเสียง — ไม่ยิงพร้อมกัน (ElevenLabs จำกัดคำขอพร้อมกันตามแพ็กเกจ และกันโควต้าไหลรวดเดียว)
                    for (const v of this.visibleVoices()) {
                        await this.generate(pKey, v.id);
                    }
                    this.notify('สร้างเสียงตัวอย่างของ ' + p.label + ' ครบแล้ว');
                } finally {
                    p.busyAll = false;
                }
            },
            async select(pKey, voiceId) {
                try {
                    const { ok, body } = await this.request(@json(route('admin.fortune.character-voices.select')), {
                        method: 'POST',
                        body: JSON.stringify({ persona: pKey, voice_id: voiceId }),
                    });
                    if (ok) {
                        this.personas[pKey].selected_voice = voiceId;
                    }
                    this.notify(ok ? body.message : (body.error || 'เลือกเสียงไม่สำเร็จ'), ok);
                } catch (e) {
                    this.notify('เชื่อมต่อไม่สำเร็จ ลองใหม่อีกครั้ง', false);
                }
            },
        };
    }
</script>
@endsection
