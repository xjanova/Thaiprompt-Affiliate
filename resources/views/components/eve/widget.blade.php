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
.eve-panel{position:absolute;right:0;bottom:0;width:min(392px,calc(100vw - 24px));height:min(560px,calc(100vh - 90px));background:#fffdf9;border-radius:20px;box-shadow:0 24px 60px rgba(40,30,90,.34);display:flex;flex-direction:column;overflow:hidden;border:1px solid #efe6d6}
.eve-head{display:flex;align-items:center;gap:10px;padding:10px 12px;background:linear-gradient(135deg,#7a5cff,#9b7bff);color:#fff}
.eve-head .nm{font-weight:800;font-size:15px;line-height:1.1}
.eve-head .st{font-size:11px;opacity:.9}
.eve-ic{margin-left:auto;display:flex;gap:4px}
.eve-ic button{background:rgba(255,255,255,.18);border:0;color:#fff;width:30px;height:30px;border-radius:9px;cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center}
.eve-ic button:hover{background:rgba(255,255,255,.32)}
.eve-body{flex:1;display:flex;min-height:0}
.eve-evecol{width:116px;flex:0 0 116px;position:relative;background:linear-gradient(180deg,#f4eeff,#e7defa);display:flex;align-items:flex-end;justify-content:center;border-right:1px solid #efe6d6;overflow:hidden}
.eve-evecol .eve-ava{margin-bottom:-4px}
.eve-body-c{flex:1;min-width:0;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:10px;background:#fffdf9}
.eve-msg{max-width:82%;padding:9px 13px;border-radius:15px;font-size:13.5px;line-height:1.5;word-wrap:break-word;position:relative}
/* หางชี้ทิศ — บอกชัดว่าใครพูด: ลูกค้า=ชี้ขวา / น้อง Eve=ชี้ซ้าย */
.eve-msg.u{align-self:flex-end;background:linear-gradient(135deg,#7a5cff,#9b7bff);color:#fff;border-bottom-right-radius:3px}
.eve-msg.u::after{content:'';position:absolute;bottom:0;right:-6px;width:12px;height:14px;background:#9b7bff;clip-path:polygon(0 0,0 100%,100% 100%)}
.eve-msg.a{align-self:flex-start;background:#f1ecff;color:#3a2b5e;border-bottom-left-radius:3px}
.eve-msg.a::after{content:'';position:absolute;bottom:0;left:-6px;width:12px;height:14px;background:#f1ecff;clip-path:polygon(100% 0,100% 100%,0 100%)}
.eve-typing{align-self:flex-start;color:#9b8fc0;font-size:13px;padding:4px 8px}
.eve-cards{display:flex;gap:8px;overflow-x:auto;padding:2px 1px 6px;max-width:100%;scrollbar-width:thin}
.eve-card{flex:0 0 128px;width:128px;background:#fff;border:1px solid #ece4f7;border-radius:12px;overflow:hidden;text-decoration:none;color:#3a2b5e;box-shadow:0 3px 10px rgba(90,60,180,.08);transition:transform .12s}
.eve-card:hover{transform:translateY(-2px);box-shadow:0 8px 18px rgba(90,60,180,.16)}
.eve-card-img{width:100%;height:92px;background:#f6f2fc;display:flex;align-items:center;justify-content:center;overflow:hidden}
.eve-card-img img{width:100%;height:100%;object-fit:contain}
.eve-card-nm{font-size:11.5px;line-height:1.35;padding:6px 8px 2px;height:46px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical}
.eve-card-pr{font-size:13px;font-weight:800;color:#7a5cff;padding:0 8px 4px}
.eve-card-btn{margin:0 8px 8px;text-align:center;background:linear-gradient(135deg,#7a5cff,#9b7bff);color:#fff;font-size:11px;font-weight:700;padding:5px 0;border-radius:8px}
.eve-foot{display:flex;gap:8px;padding:10px;border-top:1px solid #efe6d6;background:#fff}
.eve-foot input{flex:1;border:1px solid #e3d9f0;border-radius:12px;padding:9px 13px;font-size:13.5px;outline:none;font-family:inherit}
.eve-foot input:focus{border-color:#9b7bff}
.eve-foot button{background:linear-gradient(135deg,#7a5cff,#9b7bff);color:#fff;border:0;border-radius:12px;width:42px;cursor:pointer;font-size:15px}
.eve-foot button:disabled{opacity:.5;cursor:default}

/* ── อีโมจิลอยตามอารมณ์ (เหนือหัว Eve) — ลอยขึ้น+จางหาย ใช้พื้นที่ว่างคอลัมน์ซ้าย ── */
.eve-floats{position:absolute;left:2px;right:2px;top:4px;bottom:25%;pointer-events:none;z-index:3;overflow:visible}
.eve-float{position:absolute;bottom:0;font-size:21px;line-height:1;will-change:transform,opacity;animation:eve-rise 2.4s ease-out forwards}
/* บัลลูนความคิด (ตอนคิด) — ฟองขาวมีหางจุดเล็ก */
.eve-float.bub{font-size:15px;background:#fff;border:1px solid #e7ddf4;border-radius:13px;padding:2px 9px;box-shadow:0 3px 11px rgba(90,60,180,.2)}
.eve-float.bub::after{content:'';position:absolute;left:-4px;bottom:2px;width:6px;height:6px;background:#fff;border:1px solid #e7ddf4;border-radius:50%}
/* ปิ๊งไอเดีย (หลอดไฟ) — เด้งโต+เรืองแสงทอง */
.eve-float.idea{font-size:26px;filter:drop-shadow(0 0 7px rgba(255,206,77,.9));animation:eve-idea 2.3s ease-out forwards}
@keyframes eve-rise{0%{transform:translateY(8px) scale(.4);opacity:0}18%{opacity:1;transform:translateY(0) scale(1.05)}38%{transform:translateY(-34px) scale(1)}100%{transform:translateY(-140px) scale(.82);opacity:0}}
@keyframes eve-idea{0%{transform:translateY(0) scale(.2);opacity:0}16%{opacity:1;transform:translateY(-4px) scale(1.3)}32%{transform:translateY(-8px) scale(1)}56%{transform:translateY(-13px) scale(1.14)}100%{transform:translateY(-122px) scale(.9);opacity:0}}
@media (prefers-reduced-motion:reduce){.eve-float{animation-duration:.01ms;opacity:0}}

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
            <div>
                <div class="nm">น้อง Eve</div>
                <div class="st" x-text="busy ? 'กำลังคิด...' : (speaking ? 'กำลังพูด...' : 'พร้อมช่วยค่ะ')"></div>
            </div>
            <div class="eve-ic">
                <button type="button" @click="ttsEnabled=!ttsEnabled; if(!ttsEnabled) stopSpeak()" :title="ttsEnabled ? 'ปิดเสียง' : 'เปิดเสียง'" x-text="ttsEnabled ? '🔊' : '🔇'"></button>
                <button type="button" @click="toggle()" title="ปิด">✕</button>
            </div>
        </div>

        <div class="eve-body">
            {{-- Eve เต็มตัว คอลัมน์ซ้าย (ไม่ทับข้อความ) + หน้าขยับตามอารมณ์ + อีโมจิลอยเหนือหัว --}}
            <div class="eve-evecol">
                <div class="eve-floats" aria-hidden="true">
                    <template x-for="f in floats" :key="f.id">
                        <div class="eve-float" :class="f.type" :style="`left:${f.x}%`" x-text="f.char"></div>
                    </template>
                </div>
                <x-eve.avatar :size="114" x-bind:class="'eve-'+emotion" />
            </div>
            <div class="eve-body-c" x-ref="list">
            <template x-for="(m,i) in messages" :key="i">
                <div style="display:flex;flex-direction:column;gap:6px;max-width:100%" :style="m.role==='user' ? 'align-items:flex-end' : 'align-items:flex-start'">
                    <div class="eve-msg" :class="m.role==='user' ? 'u' : 'a'" x-text="m.content"></div>
                    <template x-if="m.products && m.products.length">
                        <div class="eve-cards">
                            <template x-for="(p,pi) in m.products" :key="pi">
                                <a class="eve-card" :href="p.url" target="_blank" rel="noopener">
                                    <div class="eve-card-img"><img :src="p.image || '/images/no-image.png'" :alt="p.name" loading="lazy" onerror="this.style.opacity=0"></div>
                                    <div class="eve-card-nm" x-text="p.name"></div>
                                    <div class="eve-card-pr" x-text="'฿' + (p.price||0).toLocaleString('th-TH')"></div>
                                    <div class="eve-card-btn">ดูสินค้า</div>
                                </a>
                            </template>
                        </div>
                    </template>
                </div>
            </template>
            <div class="eve-typing" x-show="busy">น้อง Eve กำลังพิมพ์<span x-text="dots"></span></div>
            </div>
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
        _speakSeq: 0,
        floats: [],          // อีโมจิที่กำลังลอยอยู่ {id,char,type,x}
        _fid: 0,             // running id กัน key ซ้ำใน x-for
        _ambient: null,      // timer เด้งอีโมจิเบาๆ ตอนว่าง
        csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',

        // ชุดอีโมจิตามอารมณ์ — สุ่มหยิบมาลอย
        EMOJI: {
            happy:    ['😊','✨','💜','🥰','🎀','💖'],
            thinking: ['🤔','💭','❓'],
            talking:  ['💬','🎵','🌸'],
            idle:     ['✨','🌸','💜','🌟'],
            found:    ['🛍️','🎉','✨','👍'],
        },

        // ปล่อยอีโมจิ 1 ตัวให้ลอยขึ้น (type: emoji | bub บัลลูนความคิด | idea หลอดไฟ)
        pop(char, type = 'emoji') {
            if (!char || !this.open) return;                 // ไม่ปล่อยถ้าปิดแผง (กัน fetch ค้าง resolve ทีหลังแล้วอีโมจิค้าง)
            const id = ++this._fid;
            const x = 16 + Math.floor(Math.random() * 44);   // 16–60% เหนือหัว Eve (เผื่อบัลลูนกว้าง ไม่ให้โดนคอลัมน์ตัดขอบ)
            this.floats.push({ id, char, type, x });
            if (this.floats.length > 8) this.floats.splice(0, this.floats.length - 8); // กันค้างเยอะ
            setTimeout(() => { this.floats = this.floats.filter(f => f.id !== id); }, type === 'idea' ? 2300 : 2400);
        },

        // ปล่อยอีโมจิตามหมวดอารมณ์ (thinking = บัลลูนความคิด)
        popMood(mood) {
            const set = this.EMOJI[mood] || this.EMOJI.idle;
            this.pop(set[Math.floor(Math.random() * set.length)], mood === 'thinking' ? 'bub' : 'emoji');
        },

        // เด้งอีโมจิเบาๆ ตอนว่าง (ไม่รบกวนตอนคุย/คิด/พูด/แท็บถูกซ่อน)
        startAmbient() {
            this.stopAmbient();
            this._ambient = setInterval(() => {
                if (!this.open || this.busy || this.speaking || document.hidden) return;
                if (Math.random() < 0.5) this.popMood('idle');
            }, 5200);
        },
        stopAmbient() { clearInterval(this._ambient); this._ambient = null; },

        toggle() {
            this.open = !this.open;
            if (this.open) {
                if (this.messages.length === 0) {
                    this.messages.push({ role: 'assistant', content: 'สวัสดีค่ะ น้อง Eve เองค่ะ 🌸 อยากได้สินค้าแบบไหน หรือมีอะไรให้ช่วยไหมคะ?' });
                    this.emotion = 'happy';
                    this.speak('สวัสดีค่ะ น้อง Eve เองค่ะ อยากได้สินค้าแบบไหนคะ');
                    this.pop('👋'); setTimeout(() => this.pop('🌸'), 480);
                    setTimeout(() => { if (!this.speaking) this.emotion = 'idle'; }, 2600);
                }
                this.startAmbient();          // เริ่มเด้งอีโมจิเบาๆ ตอนเปิดแผง
            } else {
                this.stopSpeak();
                this.stopAmbient();
                this.floats = [];             // เคลียร์อีโมจิค้างเมื่อปิดแผง
            }
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
            this.popMood('thinking');   // บัลลูนความคิด 💭 ระหว่างค้นหา

            const history = this.messages.slice(-13, -1).map(m => ({ role: m.role, content: m.content }));
            try {
                const res = await fetch('{{ route('eve.chat') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ message: text, history })
                });
                const data = await res.json();
                const reply = data?.data?.reply || data?.message || 'ขออภัยค่ะ ตอนนี้น้อง Eve ตอบไม่ได้ ลองใหม่อีกครั้งนะคะ';
                const prods = data?.data?.products || [];
                this.messages.push({ role: 'assistant', content: reply, products: prods });
                this.emotion = this.moodToEmotion(data?.data?.mood);
                this.scroll();
                this.speak(reply);
                // เจอสินค้า → ปิ๊งไอเดีย 💡 + ดีใจ / ไม่งั้นเด้งอีโมจิตามอารมณ์ที่ตอบ
                if (prods.length) { this.pop('💡', 'idea'); setTimeout(() => this.popMood('found'), 440); }
                else { this.popMood(({ happy: 'happy', surprise: 'found', talking: 'talking' })[data?.data?.mood] || 'happy'); }
            } catch (e) {
                this.messages.push({ role: 'assistant', content: 'ขออภัยค่ะ การเชื่อมต่อมีปัญหา ลองใหม่อีกครั้งนะคะ 🙏' });
                this.emotion = 'idle';
                this.pop('🙏');
            } finally {
                this.busy = false; this.stopDots(); this.scroll();
            }
        },

        // ── Google Translate TTS (ฟรี ไทย) — chunk + คิวเล่นต่อเนื่อง + no-referrer ──
        chunkForTts(text) {
            const clean = (text || '').replace(/\[[^\]]*\]/g, '').replace(/[*_#`>]/g, '').trim();
            if (!clean) return [];
            const out = []; let buf = '';
            // แยกประโยคหลังคำลงท้าย (ค่ะ/คะ/ครับ) หรือเครื่องหมาย — ใช้ alternation ไม่ใช่ char-class (กันตัดกลางคำ) + คงเว้นวรรค
            const parts = clean.split(/(?<=(?:ค่ะ|คะ|ครับ|[.!?\n]))/);
            for (const p of parts) {
                if ((buf + p).length > 170) { if (buf.trim()) out.push(buf.trim()); buf = p; }
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
            const seq = this._speakSeq; // stopSpeak() เพิ่ง ++ ให้แล้ว = เซสชันพูดปัจจุบัน
            this._ttsQueue = chunks.map((c, i) =>
                `https://translate.google.com/translate_tts?ie=UTF-8&client=tw-ob&tl=th&q=${encodeURIComponent(c)}&total=${chunks.length}&idx=${i}&textlen=${c.length}`);
            this.speaking = true;
            const prev = this.emotion;
            this.emotion = 'talking';
            this._playNext(seq, () => { if (seq !== this._speakSeq) return; this.speaking = false; this.emotion = (prev === 'happy' ? 'happy' : 'idle'); });
        },

        _playNext(seq, done) {
            if (seq !== this._speakSeq) return;        // เซสชันเก่า (ถูก stopSpeak ไปแล้ว) — ทิ้ง ไม่ให้ stomp อารมณ์ใหม่
            if (!this._ttsQueue.length) { done && done(); return; }
            const url = this._ttsQueue.shift();
            const a = new Audio();
            a.referrerPolicy = 'no-referrer';
            a.src = url;
            this._audio = a;
            a.onended = () => this._playNext(seq, done);
            a.onerror = () => this._playNext(seq, done);
            a.play().catch(() => this._playNext(seq, done));
        },

        stopSpeak() {
            this._speakSeq++; // invalidate เซสชันพูดเก่า → callback ที่ค้างจะ bail ไม่ stomp อารมณ์
            this._ttsQueue = [];
            if (this._audio) {
                try { this._audio.onended = null; this._audio.onerror = null; this._audio.pause(); } catch (e) {}
                this._audio = null;
            }
            this.speaking = false;
            if (this.emotion === 'talking') this.emotion = 'idle';
        }
    };
}
</script>
@endpush
