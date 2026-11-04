{{--
    🎬 (2026-11-04) การ์ด "เสียงอ่านฟรี (Google Cloud)" — เสียงอ่านคำทำนายให้ลูกค้าที่ไม่ได้ซื้อเสียงสมจริง ฿100
    ลำดับ: Google Cloud → gTTS → ไม่มีเสียง · ไม่ใช้ ElevenLabs · ถึงเพดานเดือน = ถอยไป gTTS เอง
--}}
@php
    $percent = $free['cap'] > 0 ? min(100, (int) round($free['used'] * 100 / $free['cap'])) : 0;
@endphp
<div class="tp-card p-5 md:p-6 mb-6" x-data="googleFreeVoice()">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
        <div>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">🔊 เสียงอ่านฟรี (Google Cloud)</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                อ่านคำทำนายให้ลูกค้าที่ไม่ได้ซื้อเสียงสมจริง · สร้างกุญแจที่
                <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener" class="text-sky-600 dark:text-sky-400 underline">Google Cloud Console → Credentials</a>
                แล้วเปิด <b>Cloud Text-to-Speech API</b> ในโปรเจกต์เดียวกัน · ไม่มีกุญแจ = ใช้ gTTS (เสียงหุ่นยนต์ตัวเดียว)
            </p>
        </div>
        <div class="text-right">
            @if($free['hasKey'])
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">✅ ตั้งแล้ว <code class="font-mono">{{ $free['maskedKey'] }}</code></span>
            @elseif($free['envKey'])
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300">ใช้กุญแจจาก .env</span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">❗ ยังไม่มีกุญแจ (ใช้ gTTS)</span>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('admin.fortune.character-voices.google.update') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @csrf
        @method('PUT')
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ $free['hasKey'] ? 'เปลี่ยนกุญแจ (เว้นว่าง = ใช้กุญแจเดิม)' : 'วางกุญแจที่นี่' }}
            </label>
            <input type="password" name="google_tts_api_key" x-model="key" autocomplete="new-password" spellcheck="false"
                   placeholder="AIza…"
                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-mono">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ระบบทดสอบกุญแจด้วยการดึงรายชื่อเสียงไทย (ไม่กินโควตา) ก่อนบันทึก</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เพดานตัวอักษรต่อเดือน</label>
            <input type="number" min="10000" max="4000000" step="1000" name="google_tts_monthly_char_cap"
                   value="{{ old('google_tts_monthly_char_cap', $free['cap']) }}"
                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ตั้งต่ำกว่าโควตาฟรีของ Google (Neural2/WaveNet 1 ล้านตัวอักษร/เดือน)</p>
        </div>

        {{-- ใช้ไปเดือนนี้ --}}
        <div class="md:col-span-3">
            <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-300 mb-1">
                <span>เดือนนี้ใช้ไป {{ number_format($free['used']) }} / {{ number_format($free['cap']) }} ตัวอักษร</span>
                <span>{{ $percent }}%</span>
            </div>
            <div class="h-2 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                <div class="h-full rounded-full {{ $percent >= 80 ? 'bg-amber-500' : 'bg-emerald-500' }}" style="width: {{ $percent }}%"></div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ถึง 80% แจ้งเตือนแอดมินทาง Telegram · ถึงเพดาน ระบบสลับไปใช้ gTTS เอง (ไม่โดนเก็บเงิน)</p>
        </div>

        {{-- เสียงต่อห้อง --}}
        <div class="md:col-span-3">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">เสียงไทยของแต่ละห้อง</span>
                <button type="button" @click="loadVoices()" class="tp-btn" :disabled="testing">
                    <span x-text="testing ? 'กำลังทดสอบ…' : '🔌 ทดสอบกุญแจ + ดึงรายชื่อเสียง'"></span>
                </button>
            </div>
            <p class="text-sm mb-3" x-show="testText" x-text="testText"
               :class="testOk ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300'"></p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                @foreach($free['labels'] as $pKey => $label)
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3">
                        <div class="text-sm font-semibold text-gray-900 dark:text-white mb-2">{{ $label }}</div>
                        <select name="voices[{{ $pKey }}]" x-model="room['{{ $pKey }}']"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-mono">
                            <template x-for="v in voiceOptions('{{ $pKey }}')" :key="v.name">
                                <option :value="v.name" x-text="v.name + (v.gender ? ' · ' + (v.gender === 'female' ? 'หญิง' : v.gender === 'male' ? 'ชาย' : v.gender) : '')"></option>
                            </template>
                        </select>
                        <button type="button" @click="playSample('{{ $pKey }}')" class="tp-btn w-full justify-center mt-2"
                                :disabled="busy['{{ $pKey }}'] || !hasKey">
                            <span x-text="busy['{{ $pKey }}'] ? 'กำลังสร้าง…' : '▶️ ฟังตัวอย่าง'"></span>
                        </button>
                        <template x-if="samples['{{ $pKey }}']">
                            <audio controls autoplay preload="none" class="w-full mt-2 h-10" :src="samples['{{ $pKey }}']"></audio>
                        </template>
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">ฟังตัวอย่างนับเข้าเพดานเดือน (ครั้งละ ~150 ตัวอักษร) · กดซ้ำเสียงเดิมไม่นับซ้ำ · ต้องบันทึกกุญแจก่อนจึงฟังตัวอย่างได้</p>
        </div>

        <div class="md:col-span-3 flex justify-end">
            <button type="submit" class="tp-btn">💾 บันทึกเสียงอ่านฟรี</button>
        </div>
    </form>

    @if($free['hasKey'])
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-end">
            <form method="POST" action="{{ route('admin.fortune.character-voices.google.key.delete') }}"
                  onsubmit="return confirm('ลบกุญแจ Google Cloud ใช่ไหมคะ? เสียงอ่านคำทำนายฟรีจะกลับไปใช้ gTTS')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800 dark:hover:bg-red-900/40 transition">🗑️ ลบกุญแจ</button>
            </form>
        </div>
    @endif
</div>

<script>
    function googleFreeVoice() {
        return {
            key: '',
            hasKey: @json($free['hasKey']),
            room: {{ \Illuminate\Support\Js::from($free['roomVoices']) }},
            voices: [],
            busy: {},
            samples: {},
            testing: false,
            testText: '',
            testOk: true,
            CSRF: '{{ csrf_token() }}',

            // ยังไม่ได้ดึงรายชื่อ = แสดงแค่เสียงที่เลือกไว้ (ไม่ให้ select ว่างแล้วบันทึกทับเป็นค่าว่าง)
            voiceOptions(pKey) {
                const list = this.voices.length ? this.voices : [];
                if (!list.find(v => v.name === this.room[pKey])) {
                    return [{ name: this.room[pKey], gender: '' }, ...list];
                }
                return list;
            },
            async post(url, payload) {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.CSRF },
                    body: JSON.stringify(payload),
                });
                let body = {};
                try { body = await res.json(); } catch (e) { body = {}; }
                if (res.status === 419) body.error = 'หน้าเว็บหมดอายุ กรุณารีเฟรชหน้า';
                if (res.status === 429 && !body.error) body.error = 'กดถี่เกินไป รอสักครู่แล้วลองใหม่';
                if (res.status === 422 && !body.error) body.error = body.message || 'ข้อมูลไม่ถูกต้อง';
                return { ok: res.ok, body };
            },
            async loadVoices() {
                this.testing = true;
                try {
                    const { ok, body } = await this.post(@json(route('admin.fortune.character-voices.google.test')), this.key ? { key: this.key } : {});
                    this.testOk = ok;
                    if (ok) {
                        this.voices = body.voices;
                        this.testText = `✅ กุญแจใช้ได้ · พบเสียงไทย ${body.voices.length} เสียง`;
                    } else {
                        this.testText = body.error || 'ทดสอบกุญแจไม่สำเร็จ';
                    }
                } catch (e) {
                    this.testOk = false;
                    this.testText = 'เชื่อมต่อไม่สำเร็จ ลองใหม่อีกครั้ง';
                } finally {
                    this.testing = false;
                }
            },
            async playSample(pKey) {
                if (this.busy[pKey]) return;
                this.busy[pKey] = true;
                this.samples[pKey] = null;
                try {
                    const { ok, body } = await this.post(@json(route('admin.fortune.character-voices.google.sample')), { persona: pKey, voice: this.room[pKey] });
                    if (ok) {
                        this.samples[pKey] = body.url;
                    } else {
                        this.testOk = false;
                        this.testText = body.error || 'สร้างเสียงตัวอย่างไม่สำเร็จ';
                    }
                } catch (e) {
                    this.testOk = false;
                    this.testText = 'เชื่อมต่อไม่สำเร็จ ลองใหม่อีกครั้ง';
                } finally {
                    this.busy[pKey] = false;
                }
            },
        };
    }
</script>
