{{--
  น้อง Eve — วิดเจ็ตผู้ช่วย AI หน้าเว็บ (ลอยมุมขวาล่าง)
  - คุยผ่าน AI Pool เดียวกับระบบทำนาย (POST /eve/chat)
  - พูดด้วย Google Translate TTS ฟรี (ไทย)
  - หน้าเปลี่ยนอารมณ์ตามสถานะ (thinking ระหว่างคิด / talking ระหว่างพูด / happy)
  สไตล์ self-contained — ใช้ได้ทั้ง storefront (ลูกค้า) และ admin-v4
--}}
@once
<style>
.eve-w{position:fixed;right:18px;bottom:18px;z-index:99990;font-family:inherit}
.eve-fab{display:flex;align-items:center;gap:8px;cursor:pointer;background:linear-gradient(135deg,#7a5cff,#9b7bff);color:#fff;border:none;border-radius:999px;padding:6px 16px 6px 6px;box-shadow:0 10px 28px rgba(90,60,180,.4);transition:transform .15s}
.eve-fab:hover{transform:translateY(-2px) scale(1.03)}
.eve-fab .eve-fab-txt{font-weight:700;font-size:13.5px;white-space:nowrap}
.eve-fab .eve-fab-sub{font-size:10px;opacity:.85;font-weight:400}
.eve-panel{position:absolute;right:0;bottom:0;width:min(360px,calc(100vw - 24px));height:min(560px,calc(100vh - 90px));background:#fffdf9;border-radius:20px;box-shadow:0 24px 60px rgba(40,30,90,.34);display:flex;flex-direction:column;overflow:hidden;border:1px solid #efe6d6}
.eve-head{display:flex;align-items:center;gap:10px;padding:10px 12px;background:linear-gradient(135deg,#7a5cff,#9b7bff);color:#fff}
.eve-head .nm{font-weight:800;font-size:15px;line-height:1.1}
.eve-head .st{font-size:11px;opacity:.9}
.eve-ic{margin-left:auto;display:flex;gap:4px}
.eve-ic button{background:rgba(255,255,255,.18);border:0;color:#fff;width:30px;height:30px;border-radius:9px;cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center}
.eve-ic button:hover{background:rgba(255,255,255,.32)}
.eve-body-c{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:10px;background:#fffdf9}
.eve-msg{max-width:82%;padding:9px 13px;border-radius:14px;font-size:13.5px;line-height:1.5;word-wrap:break-word}
.eve-msg.u{align-self:flex-end;background:linear-gradient(135deg,#7a5cff,#9b7bff);color:#fff;border-bottom-right-radius:4px}
.eve-msg.a{align-self:flex-start;background:#f1ecff;color:#3a2b5e;border-bottom-left-radius:4px}
.eve-typing{align-self:flex-start;color:#9b8fc0;font-size:13px;padding:4px 8px}
.eve-foot{display:flex;gap:8px;padding:10px;border-top:1px solid #efe6d6;background:#fff}
.eve-foot input{flex:1;border:1px solid #e3d9f0;border-radius:12px;padding:9px 13px;font-size:13.5px;outline:none;font-family:inherit}
.eve-foot input:focus{border-color:#9b7bff}
.eve-foot button{background:linear-gradient(135deg,#7a5cff,#9b7bff);color:#fff;border:0;border-radius:12px;width:42px;cursor:pointer;font-size:15px}
.eve-foot button:disabled{opacity:.5;cursor:default}
[x-cloak]{display:none!important}
</style>
@endonce

<div class="eve-w" x-data="eveWidget()" x-cloak>
    {{-- ปุ่มลอย --}}
    <button class="eve-fab" x-show="!open" @click="toggle()" type="button">
        <x-eve.avatar :size="46" crop />
        <span style="text-align:left">
            <span class="eve-fab-txt">น้อง Eve</span><br>
            <span class="eve-fab-sub">ผู้ช่วยหาของให้ค่ะ</span>
        </span>
    </button>

    {{-- แผงแชท --}}
    <div class="eve-panel" x-show="open" x-transition.scale.origin.bottom.right>
        <div class="eve-head">
            <x-eve.avatar :size="56" crop x-bind:class="'eve-'+emotion" />
            <div>
                <div class="nm">น้อง Eve</div>
                <div class="st" x-text="busy ? 'กำลังคิด...' : (speaking ? 'กำลังพูด...' : 'พร้อมช่วยค่ะ')"></div>
            </div>
            <div class="eve-ic">
                <button type="button" @click="ttsEnabled=!ttsEnabled; if(!ttsEnabled) stopSpeak()" :title="ttsEnabled ? 'ปิดเสียง' : 'เปิดเสียง'" x-text="ttsEnabled ? '🔊' : '🔇'"></button>
                <button type="button" @click="toggle()" title="ปิด">✕</button>
            </div>
        </div>

        <div class="eve-body-c" x-ref="list">
            <template x-for="(m,i) in messages" :key="i">
                <div class="eve-msg" :class="m.role==='user' ? 'u' : 'a'" x-text="m.content"></div>
            </template>
            <div class="eve-typing" x-show="busy">น้อง Eve กำลังพิมพ์<span x-text="dots"></span></div>
        </div>

        <div class="eve-foot">
            <input type="text" x-model="input" @keydown.enter="send()" :disabled="busy"
                   placeholder="พิมพ์ว่าอยากได้อะไร เช่น หูฟังบลูทูธ งบ 500..." maxlength="500">
            <button type="button" @click="send()" :disabled="busy || !input.trim()">➤</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function eveWidget() {
    return {
        open: false,
        input: '',
        messages: [],
        busy: false,
        speaking: false,
        emotion: 'idle',
        ttsEnabled: true,
        dots: '',
        _dotTimer: null,
        _audio: null,
        _ttsQueue: [],
        csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',

        toggle() {
            this.open = !this.open;
            if (this.open && this.messages.length === 0) {
                this.messages.push({ role: 'assistant', content: 'สวัสดีค่ะ น้อง Eve เองค่ะ 🌸 อยากได้สินค้าแบบไหน หรือมีอะไรให้ช่วยไหมคะ?' });
                this.emotion = 'happy';
                this.speak('สวัสดีค่ะ น้อง Eve เองค่ะ อยากได้สินค้าแบบไหนคะ');
                setTimeout(() => { if (!this.speaking) this.emotion = 'idle'; }, 2600);
            }
            if (!this.open) this.stopSpeak();
        },

        moodToEmotion(mood) {
            return ({ happy: 'happy', thinking: 'thinking', surprise: 'happy', concerned: 'idle', talking: 'idle' })[mood] || 'idle';
        },

        startDots() {
            clearInterval(this._dotTimer);
            let n = 0;
            this._dotTimer = setInterval(() => { n = (n + 1) % 4; this.dots = '.'.repeat(n); }, 400);
        },
        stopDots() { clearInterval(this._dotTimer); this.dots = ''; },

        scroll() { this.$nextTick(() => { const l = this.$refs.list; if (l) l.scrollTop = l.scrollHeight; }); },

        async send() {
            const text = this.input.trim();
            if (!text || this.busy) return;
            this.input = '';
            this.stopSpeak();
            this.messages.push({ role: 'user', content: text });
            this.busy = true; this.emotion = 'thinking'; this.startDots(); this.scroll();

            const history = this.messages.slice(-13, -1).map(m => ({ role: m.role, content: m.content }));
            try {
                const res = await fetch('{{ route('eve.chat') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ message: text, history })
                });
                const data = await res.json();
                const reply = data?.data?.reply || data?.message || 'ขออภัยค่ะ ตอนนี้น้อง Eve ตอบไม่ได้ ลองใหม่อีกครั้งนะคะ';
                this.messages.push({ role: 'assistant', content: reply });
                this.emotion = this.moodToEmotion(data?.data?.mood);
                this.scroll();
                this.speak(reply);
            } catch (e) {
                this.messages.push({ role: 'assistant', content: 'ขออภัยค่ะ การเชื่อมต่อมีปัญหา ลองใหม่อีกครั้งนะคะ 🙏' });
                this.emotion = 'idle';
            } finally {
                this.busy = false; this.stopDots(); this.scroll();
            }
        },

        // ── Google Translate TTS (ฟรี ไทย) — chunk + คิวเล่นต่อเนื่อง + no-referrer ──
        chunkForTts(text) {
            const clean = (text || '').replace(/\[[^\]]*\]/g, '').replace(/[*_#`>]/g, '').trim();
            if (!clean) return [];
            const out = []; let buf = '';
            const parts = clean.split(/(?<=[ค่ะคะครับ.!?\n])\s*/);
            for (const p of parts) {
                if ((buf + p).length > 170) { if (buf) out.push(buf.trim()); buf = p; }
                else buf += p;
            }
            if (buf.trim()) out.push(buf.trim());
            return out.filter(Boolean);
        },

        speak(text) {
            if (!this.ttsEnabled) return;
            this.stopSpeak();
            const chunks = this.chunkForTts(text);
            if (!chunks.length) return;
            this._ttsQueue = chunks.map((c, i) =>
                `https://translate.google.com/translate_tts?ie=UTF-8&client=tw-ob&tl=th&q=${encodeURIComponent(c)}&total=${chunks.length}&idx=${i}&textlen=${c.length}`);
            this.speaking = true;
            const prev = this.emotion;
            this.emotion = 'talking';
            this._playNext(() => { this.speaking = false; this.emotion = (prev === 'happy' ? 'happy' : 'idle'); });
        },

        _playNext(done) {
            if (!this._ttsQueue.length) { done && done(); return; }
            const url = this._ttsQueue.shift();
            const a = new Audio();
            a.referrerPolicy = 'no-referrer';
            a.src = url;
            this._audio = a;
            a.onended = () => this._playNext(done);
            a.onerror = () => this._playNext(done);
            a.play().catch(() => this._playNext(done));
        },

        stopSpeak() {
            this._ttsQueue = [];
            if (this._audio) { try { this._audio.pause(); } catch (e) {} this._audio = null; }
            this.speaking = false;
            if (this.emotion === 'talking') this.emotion = 'idle';
        }
    };
}
</script>
@endpush
