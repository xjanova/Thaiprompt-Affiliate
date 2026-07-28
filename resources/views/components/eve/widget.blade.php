{{--
  น้อง Eve — วิดเจ็ตผู้ช่วย AI หน้าเว็บ (ลอยมุมขวาล่าง)
  - คุยผ่าน AI Pool เดียวกับระบบทำนาย (POST /eve/chat)
  - พูดด้วย Google Translate TTS ฟรี (ไทย)
  - หน้าเปลี่ยนอารมณ์ตามสถานะ (thinking ระหว่างคิด / talking ระหว่างพูด / happy)
  สไตล์ self-contained — ใช้ได้ทั้ง storefront (ลูกค้า) และ admin-v4

  mobileOffset = ระยะห่างจากขอบล่างบนจอมือถือ (px)
    หน้าที่มีแถบเมนูล่างแบบ fixed (user-arrow-x / seller) ต้องส่ง 88 ขึ้นไป
    ไม่งั้นปุ่ม Eve (z-index สูงกว่า) จะไปทับแถบเมนูจนกดเมนูไม่ได้

  surface = พื้นที่ที่วิดเจ็ตถูกฝัง (storefront|user|seller|admin)
    แอดมินเปิด/ปิด Eve รายพื้นที่ได้ที่หน้า "ตั้งค่าน้อง Eve" — ปิด = ไม่ render เลย
--}}
@props(['mobileOffset' => 18, 'surface' => 'storefront'])

@php
    // ⚙️ อ่านตั้งค่าจากหลังบ้าน (แคช 60 วิ — ห้าม query หนักใน view)
    $eveEnabled = \App\Services\Eve\EveConfig::enabledFor($surface);
    $eveName = (string) \App\Services\Eve\EveConfig::get('assistant_name');
    $eveGreeting = trim((string) \App\Services\Eve\EveConfig::get('greeting'));
@endphp

@if ($eveEnabled)
@once
<style>
.eve-w{position:fixed;right:18px;bottom:18px;z-index:99990;font-family:inherit}
.eve-fab{display:flex;align-items:center;gap:8px;cursor:pointer;background:linear-gradient(135deg,#7a5cff,#9b7bff);color:#fff;border:none;border-radius:999px;padding:6px 16px 6px 6px;box-shadow:0 10px 28px rgba(90,60,180,.4);transition:transform .15s}
.eve-fab:hover{transform:translateY(-2px) scale(1.03)}
.eve-fab .eve-fab-txt{font-weight:700;font-size:13.5px;white-space:nowrap}
.eve-fab .eve-fab-sub{font-size:10px;opacity:.85;font-weight:400}
.eve-panel{position:absolute;right:0;bottom:0;width:min(392px,calc(100vw - 24px));height:min(560px,calc(100vh - 90px));background:#fffdf9;border-radius:20px;box-shadow:0 24px 60px rgba(40,30,90,.34);display:flex;flex-direction:column;overflow:hidden;border:1px solid #efe6d6}
/* 📱 จอมือถือ: หน้าที่มีแถบเมนูล่างแบบ fixed ต้องยก Eve ขึ้นไม่ให้ทับ (ส่ง mobileOffset มา)
   ต้องหดความสูงแผงลงเท่าที่ยกขึ้นด้วย ไม่งั้นหัวแผงจะทะลุพ้นจอด้านบน */
@media (max-width:1023px){
  .eve-w{bottom:var(--eve-b-mobile,18px)}
  .eve-panel{height:min(560px,calc(100vh - 72px - var(--eve-b-mobile,18px)))}
}
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

/* ── สถานะ "กำลังค้นหาสินค้า" — บอกให้ลูกค้าเห็นชัดว่าระบบกำลังทำอะไรอยู่ ──
   ต่างจาก "กำลังพิมพ์" ตรงที่มีโครงการ์ดสินค้ารออยู่ = สื่อว่าผลลัพธ์จะมาโผล่ตรงนี้ */
.eve-search{align-self:stretch;display:flex;flex-direction:column;gap:8px;padding:10px 11px;border-radius:14px;background:linear-gradient(135deg,#f6f1ff,#efe8ff);border:1px solid #e6dcfa}
.eve-search-hd{display:flex;align-items:center;gap:8px;font-size:12.5px;color:#5b4a8c;font-weight:700}
.eve-search-lens{width:23px;height:23px;flex:0 0 23px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;box-shadow:0 2px 8px rgba(90,60,180,.18);animation:eve-scan 1.7s ease-in-out infinite}
@keyframes eve-scan{0%,100%{transform:translateX(0) rotate(-14deg)}50%{transform:translateX(10px) rotate(14deg)}}
.eve-search-bar{height:3px;border-radius:3px;background:#e2d7f7;overflow:hidden}
.eve-search-bar i{display:block;height:100%;width:36%;border-radius:3px;background:linear-gradient(90deg,#7a5cff,#c9a7ff);animation:eve-sweep 1.3s ease-in-out infinite}
@keyframes eve-sweep{0%{transform:translateX(-110%)}100%{transform:translateX(300%)}}
/* โครงการ์ดสินค้าเปล่า (skeleton) วิบวับระหว่างรอ */
.eve-skel{display:flex;gap:8px;overflow:hidden}
.eve-skel-c{flex:0 0 86px;height:100px;border-radius:10px;background:linear-gradient(100deg,#eae2fa 30%,#fbf8ff 50%,#eae2fa 70%);background-size:220% 100%;animation:eve-shim 1.4s linear infinite}
.eve-skel-c:nth-child(2){animation-delay:.18s}
.eve-skel-c:nth-child(3){animation-delay:.36s}
@keyframes eve-shim{0%{background-position:120% 0}100%{background-position:-120% 0}}
/* ชิปบอก "คำที่ระบบใช้ค้นจริง" — ค้นผิดคำลูกค้าเห็นทันที แก้เองได้ ไม่ต้องเดาว่าระบบพัง */
.eve-qchip{align-self:flex-start;font-size:11px;color:#6d4dff;background:rgba(122,92,255,.09);border:1px solid rgba(122,92,255,.28);border-radius:999px;padding:3px 9px;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
@media (prefers-reduced-motion:reduce){.eve-search-lens,.eve-search-bar i,.eve-skel-c{animation:none}}
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

<div class="eve-w" x-data="eveWidget()" x-cloak style="--eve-b-mobile:{{ (int) $mobileOffset }}px">
    {{-- ปุ่มลอย --}}
    <button class="eve-fab" x-show="!open" @click="toggle()" type="button">
        <x-eve.avatar :size="46" crop />
        <span style="text-align:left">
            <span class="eve-fab-txt">{{ $eveName }}</span><br>
            <span class="eve-fab-sub">ผู้ช่วยหาของให้ค่ะ</span>
        </span>
    </button>

    {{-- แผงแชท --}}
    <div class="eve-panel" x-show="open" x-transition.scale.origin.bottom.right>
        <div class="eve-head">
            <div>
                <div class="nm">{{ $eveName }}</div>
                <div class="st" x-text="busy ? (searching ? 'กำลังค้นหาสินค้า...' : 'กำลังคิด...') : (speaking ? 'กำลังพูด...' : 'พร้อมช่วยค่ะ')"></div>
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

                    {{-- 🔎 บอกตรงๆ ว่าค้นด้วยคำว่าอะไร เจอกี่ชิ้น — ลูกค้าเห็นแล้วแก้คำค้นเองได้เลย --}}
                    <template x-if="m.search && m.search.query">
                        <div class="eve-qchip"
                             x-text="'🔎 ค้นหา: ' + m.search.query
                                     + (m.search.budget ? ' · งบ ฿' + Number(m.search.budget).toLocaleString('th-TH') : '')
                                     + ' · ' + (m.search.count ? 'เจอ ' + m.search.count + ' รายการ' : 'ยังไม่เจอในร้าน')"></div>
                    </template>

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

                    {{-- 🧭 ปุ่มลัดพาไปหน้าที่ถูกต้อง — url มาจากเซิร์ฟเวอร์ (resolve จากชื่อ route)
                         ไม่ใช่ลิงก์ที่ AI แต่งเอง จึงไม่มีทางพาไปหน้าที่ไม่มีอยู่จริง --}}
                    <template x-if="m.actions && m.actions.length">
                        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:2px">
                            <template x-for="(a,ai) in m.actions" :key="ai">
                                <a :href="a.url"
                                   style="font-size:12px;padding:5px 10px;border-radius:999px;text-decoration:none;
                                          border:1px solid rgba(122,92,255,.35);color:#6d4dff;background:rgba(122,92,255,.08);
                                          white-space:nowrap"
                                   x-text="a.label"></a>
                            </template>
                        </div>
                    </template>
                </div>
            </template>
            {{-- คุยทั่วไป = "กำลังพิมพ์" · หาของ = แผงค้นหาพร้อมโครงการ์ดสินค้า (รู้ทันทีว่ากำลังค้นให้อยู่) --}}
            <div class="eve-typing" x-show="busy && !searching" role="status" aria-live="polite">{{ $eveName }} กำลังพิมพ์<span x-text="dots"></span></div>
            <div class="eve-search" x-show="busy && searching" role="status" aria-live="polite">
                <div class="eve-search-hd">
                    <span class="eve-search-lens" aria-hidden="true">🔎</span>
                    <span x-text="searchLabel + dots"></span>
                </div>
                <div class="eve-search-bar" aria-hidden="true"><i></i></div>
                <div class="eve-skel" aria-hidden="true">
                    <div class="eve-skel-c"></div><div class="eve-skel-c"></div><div class="eve-skel-c"></div>
                </div>
            </div>
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
        searching: false,       // กำลังค้นหาสินค้าอยู่ (โชว์แผงค้นหาแทน "กำลังพิมพ์")
        searchLabel: '',        // ข้อความสถานะที่ไล่เปลี่ยนระหว่างค้น
        _searchTimer: null,
        speaking: false,
        emotion: 'idle',
        ttsEnabled: true,
        dots: '',
        _dotTimer: null,
        _audio: null,
        _ttsQueue: [],          // ข้อความที่รอพูด (ท่อนละ ≤170 ตัว)
        _preload: null,         // {text, audio} ท่อนถัดไปที่โหลดรออยู่
        _native: null,          // utterance ของเสียงสำรองในเครื่อง (Web Speech)
        _speakSeq: 0,
        // 🔊 พร็อกซีเสียงฝั่งเรา — ห้ามยิง translate.google.com ตรงๆ (Referer เว็บเรา = Google ตอบ 404)
        TTS_ENDPOINT: {{ Js::from(route('eve.tts')) }},
        floats: [],          // อีโมจิที่กำลังลอยอยู่ {id,char,type,x}
        _fid: 0,             // running id กัน key ซ้ำใน x-for
        _ambient: null,      // timer เด้งอีโมจิเบาๆ ตอนว่าง
        csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        // ชื่อ route ของหน้าปัจจุบัน — ส่งให้ Eve รู้ว่าลูกค้ายืนอยู่หน้าไหน
        // ใช้ Js::from ตามกฎโปรเจกต์ (เลี่ยง @directive ที่มีวงเล็บซ้อน)
        pageRoute: {{ Js::from(Route::currentRouteName()) }},

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
                // อุ่นรายชื่อเสียงในเครื่องไว้ก่อน (Chrome โหลดแบบ async — เรียกครั้งแรกมักได้ลิสต์ว่าง)
                try { window.speechSynthesis && window.speechSynthesis.getVoices(); } catch (e) {}
                if (this.messages.length === 0) {
                    // ข้อความทักทาย — แอดมินตั้งเองได้ที่หน้า "ตั้งค่าน้อง Eve" (ว่าง = ใช้มาตรฐาน)
                    const greeting = {{ Js::from($eveGreeting !== '' ? $eveGreeting : 'สวัสดีค่ะ '.$eveName.' เองค่ะ 🌸 อยากได้สินค้าแบบไหน หรือมีอะไรให้ช่วยไหมคะ?') }};
                    this.messages.push({ role: 'assistant', content: greeting });
                    this.emotion = 'happy';
                    this.speak(greeting.replace(/[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]/gu, '').trim());
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

        // ── สถานะ "กำลังค้นหาสินค้า" ─────────────────────────────────────────
        // ข้อความคาดเดาฝั่งไคลเอนต์เพื่อ "โชว์อนิเมชั่นให้ทันที" ระหว่างรอเซิร์ฟเวอร์
        // (ฝั่งเซิร์ฟเวอร์ตัดสินจริงอีกที — ถ้าเดาพลาดก็แค่เห็นแอนิเมชั่นค้นหา ไม่มีผลกับคำตอบ)
        looksLikeProductRequest(text) {
            const t = (text || '').trim();
            if (!t) return false;
            // ทักทาย/ขอบคุณ/ถามเรื่องระบบ = ไม่ใช่การหาของ (ตรงกับ blocklist ฝั่งเซิร์ฟเวอร์)
            if (/(สวัสดี|หวัดดี|ฮัลโหล|ขอบคุณ|ขอบใจ|บาย|เก่งมาก|น่ารัก|สมัคร|สมาชิก|เข้าสู่ระบบ|รหัสผ่าน|ค่าส่ง|กี่วัน|ติดตามพัสดุ|คือใคร|ชื่ออะไร)/.test(t)) return false;
            if (/(หา|อยากได้|อยากซื้อ|อยากดู|ซื้อ|ขาย|สนใจ|ต้องการ|มองหา|ตามหา|แนะนำ|ขอดู|เอา|สั่ง|ยี่ห้อ|รุ่น|ราคา|งบ|บาท)/.test(t)) return true;
            // พิมพ์ชื่อของเปล่าๆ สั้นๆ ("หูฟัง") — ไม่ใช่คำถามเชิงวิธีใช้ = ถือว่ากำลังหาของ
            return t.length <= 28 && !/[?？]/.test(t) && !/(ยังไง|อย่างไร|ทำไม|เมื่อไหร่|ที่ไหน)/.test(t);
        },

        startSearchStatus() {
            const L = [
                'กำลังค้นหาสินค้าให้อยู่ค่ะ',
                'ไล่ดูของในร้านทีละชิ้น',
                'กำลังคัดตัวที่น่าจะถูกใจ',
                'เกือบเสร็จแล้วค่ะ รออีกนิดนะคะ',
            ];
            let i = 0;
            this.searchLabel = L[0];
            clearInterval(this._searchTimer);
            this._searchTimer = setInterval(() => {
                if (i < L.length - 1) this.searchLabel = L[++i];   // ค้างที่ข้อความสุดท้าย ไม่วนซ้ำให้ดูปลอม
            }, 1800);
        },
        stopSearchStatus() { clearInterval(this._searchTimer); this._searchTimer = null; this.searching = false; this.searchLabel = ''; },

        scroll() { this.$nextTick(() => { const l = this.$refs.list; if (l) l.scrollTop = l.scrollHeight; }); },

        async send() {
            const text = this.input.trim();
            if (!text || this.busy) return;
            this.input = '';
            this.stopSpeak();
            this.messages.push({ role: 'user', content: text });
            this.busy = true; this.emotion = 'thinking'; this.startDots(); this.scroll();

            // ขอหาของ → แผงค้นหา + แว่นขยาย 🔎 / คุยทั่วไป → บัลลูนความคิด 💭
            this.searching = this.looksLikeProductRequest(text);
            if (this.searching) { this.startSearchStatus(); this.pop('🔎', 'bub'); this.scroll(); }
            else { this.popMood('thinking'); }

            // ⏱️ กันค้างไม่มีที่สิ้นสุด: AI ฝั่งเซิร์ฟเวอร์ใช้เวลาสูงสุด ~30s (15s × 2 provider)
            //    ถ้าเน็ตลูกค้าหลุดกลางทาง fetch จะไม่ reject เอง → บอทค้างที่ "กำลังค้นหา" ตลอดกาล
            const ctrl = new AbortController();
            const abortTimer = setTimeout(() => ctrl.abort(), 40000);

            const history = this.messages.slice(-13, -1).map(m => ({ role: m.role, content: m.content }));
            try {
                const res = await fetch('{{ route('eve.chat') }}', {
                    method: 'POST',
                    signal: ctrl.signal,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        // อ่าน token ใหม่ทุกครั้ง — หน้าที่เปิดค้างนานแล้ว session ถูก regenerate
                        // จะทำให้ token ที่แคชไว้ตอน init หมดอายุ แล้ว 419 ทุกครั้ง
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || this.csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    // page = ชื่อ route ปัจจุบัน → Eve รู้ว่าลูกค้ายืนอยู่หน้าไหน (เซิร์ฟเวอร์ตรวจ whitelist อีกชั้น)
                    body: JSON.stringify({ message: text, history, page: this.pageRoute })
                });

                // ⚠️ ต้องเช็ค res.ok ก่อน — ไม่งั้น 429 จะเอาข้อความ "Too Many Attempts."
                //    มาแสดงเป็นคำพูดของ Eve แล้ว TTS ก็อ่านออกเสียงเป็นภาษาไทยด้วย
                if (!res.ok) {
                    const msg = res.status === 429 ? 'ช่วงนี้มีคนคุยเยอะค่ะ 🙏 รอสักครู่แล้วลองใหม่นะคะ'
                              : res.status === 419 ? 'เซสชันหมดอายุค่ะ รบกวนรีเฟรชหน้าแล้วคุยต่อนะคะ 🙏'
                              : res.status >= 500  ? 'ระบบขัดข้องชั่วคราวค่ะ ลองใหม่อีกครั้งนะคะ 🙏'
                              : 'ตอนนี้น้อง Eve ตอบไม่ได้ค่ะ ลองใหม่อีกครั้งนะคะ 🙏';
                    this.messages.push({ role: 'assistant', content: msg });
                    this.emotion = 'idle'; this.pop('🙏'); this.scroll();
                    return;
                }

                const data = await res.json();
                const reply = data?.data?.reply || data?.message || 'ขออภัยค่ะ ตอนนี้น้อง Eve ตอบไม่ได้ ลองใหม่อีกครั้งนะคะ';
                const prods = data?.data?.products || [];
                const acts  = data?.data?.actions || [];
                // meta การค้น (คำค้นที่ใช้จริง/งบ/จำนวนที่เจอ) — null เมื่อรอบนี้ไม่ได้ค้น
                const srch  = data?.data?.search || null;
                this.messages.push({ role: 'assistant', content: reply, products: prods, actions: acts, search: srch });
                this.emotion = this.moodToEmotion(data?.data?.mood);
                this.scroll();
                this.speak(reply);
                // เจอสินค้า → ปิ๊งไอเดีย 💡 + ดีใจ / ไม่งั้นเด้งอีโมจิตามอารมณ์ที่ตอบ
                if (prods.length) { this.pop('💡', 'idea'); setTimeout(() => this.popMood('found'), 440); }
                else { this.popMood(({ happy: 'happy', surprise: 'found', talking: 'talking' })[data?.data?.mood] || 'happy'); }
            } catch (e) {
                // หมดเวลา (abort) ต้องบอกให้ชัดว่า "ค้นไม่สำเร็จ" ไม่ใช่ปล่อยให้รอต่อแบบไม่รู้อะไร
                const timedOut = e && e.name === 'AbortError';
                this.messages.push({
                    role: 'assistant',
                    content: timedOut
                        ? 'ขออภัยค่ะ รอบนี้ค้นนานผิดปกติจนต้องหยุดก่อน 🙏 รบกวนพิมพ์คำค้นอีกครั้งนะคะ เดี๋ยว Eve ลองใหม่ให้ค่ะ'
                        : 'ขออภัยค่ะ การเชื่อมต่อมีปัญหา ลองใหม่อีกครั้งนะคะ 🙏',
                });
                this.emotion = 'idle';
                this.pop('🙏');
            } finally {
                clearTimeout(abortTimer);
                this.busy = false; this.stopDots(); this.stopSearchStatus(); this.scroll();
            }
        },

        // ── Google Translate TTS (ฟรี ไทย) — chunk + คิวเล่นต่อเนื่อง ผ่านพร็อกซีของเรา ──
        //    ⚠️ ห้ามยิง translate.google.com ตรงจากเบราว์เซอร์: Audio() ไม่รองรับ referrerPolicy
        //    เบราว์เซอร์แนบ Referer เว็บเราไปด้วยเสมอ → Google ตอบ 404 → เงียบสนิท (บั๊กเดิม)
        chunkForTts(text) {
            const clean = (text || '').replace(/\[[^\]]*\]/g, '').replace(/[*_#`>]/g, '').trim();
            if (!clean) return [];
            const out = []; let buf = '';
            // แยกประโยคหลังคำลงท้าย (ค่ะ/คะ/ครับ) หรือเครื่องหมาย — ใช้ alternation ไม่ใช่ char-class (กันตัดกลางคำ) + คงเว้นวรรค
            // ⚠️ lookbehind ใช้ไม่ได้บน Safari เก่า (< 16.4) และถ้าเขียนเป็น "regex literal" เบราว์เซอร์จะ
            //    SyntaxError ตั้งแต่ตอน parse สคริปต์ = วิดเจ็ตทั้งตัวตายเงียบ (try/catch ไม่ทัน)
            //    จึงต้องสร้างด้วย new RegExp เพื่อให้ error เกิดตอนรัน แล้วถอยไปใช้ตัวแบ่งธรรมดาแทนได้
            let parts;
            try {
                parts = clean.split(new RegExp('(?<=(?:ค่ะ|คะ|ครับ|[.!?\\n]))'));
            } catch (e) {
                parts = clean.split(/([.!?\n])/).filter(Boolean);
            }
            for (const p of parts) {
                if ((buf + p).length > 170) { if (buf.trim()) out.push(buf.trim()); buf = p; }
                else buf += p;
            }
            if (buf.trim()) out.push(buf.trim());

            // ประโยคยาวที่ไม่มีจุดตัดเลย (เช่น สเปกสินค้ายาวๆ) จะโตเกินเพดานของ endpoint (300 ตัว)
            // แล้วโดนตีกลับ 422 → ต้องหั่นดิบๆ ให้อยู่ในพิสัยก่อนส่ง
            const capped = [];
            for (const c of out.filter(Boolean)) {
                if (c.length <= 170) { capped.push(c); continue; }
                for (let i = 0; i < c.length; i += 170) capped.push(c.slice(i, i + 170));
            }
            return capped.filter(c => c.trim());
        },

        speak(text) {
            if (!this.ttsEnabled) return;
            this.stopSpeak();
            const chunks = this.chunkForTts(text);
            if (!chunks.length) return;
            const seq = this._speakSeq; // stopSpeak() เพิ่ง ++ ให้แล้ว = เซสชันพูดปัจจุบัน
            this._ttsQueue = chunks.slice();   // เก็บ "ข้อความ" ไว้ด้วย เผื่อต้องถอยไปใช้เสียงในเครื่อง
            this.speaking = true;
            const prev = this.emotion;
            this.emotion = 'talking';
            this._playNext(seq, () => { if (seq !== this._speakSeq) return; this.speaking = false; this.emotion = (prev === 'happy' ? 'happy' : 'idle'); });
        },

        _ttsUrl(text) { return this.TTS_ENDPOINT + '?q=' + encodeURIComponent(text); },

        _makeAudio(text) {
            const a = new Audio();
            a.preload = 'auto';
            a.src = this._ttsUrl(text);
            return a;
        },

        _playNext(seq, done) {
            if (seq !== this._speakSeq) return;        // เซสชันเก่า (ถูก stopSpeak ไปแล้ว) — ทิ้ง ไม่ให้ stomp อารมณ์ใหม่
            if (!this._ttsQueue.length) { done && done(); return; }
            const text = this._ttsQueue.shift();

            // ใช้ท่อนที่พรีโหลดไว้ตอนกำลังเล่นท่อนก่อนหน้า — ต่อประโยคได้ไม่มีช่องว่างรอโหลด
            const a = (this._preload && this._preload.text === text) ? this._preload.audio : this._makeAudio(text);
            this._preload = null;
            this._audio = a;

            // พรีโหลดท่อนถัดไประหว่างที่ท่อนนี้กำลังเล่น
            if (this._ttsQueue.length) {
                this._preload = { text: this._ttsQueue[0], audio: this._makeAudio(this._ttsQueue[0]) };
            }

            let settled = false;
            const advance = () => { if (settled) return; settled = true; this._playNext(seq, done); };
            // เซิร์ฟเวอร์ล่ม / โดน throttle / Google ไม่ตอบ → ถอยไปใช้เสียงในเครื่องท่อนนี้ แล้วไปต่อ
            const fallback = () => {
                if (settled) return;
                settled = true;
                this._speakNative(text, seq, () => this._playNext(seq, done));
            };

            a.onended = advance;
            a.onerror = fallback;
            a.play().catch(err => {
                // เบราว์เซอร์บล็อกเสียงอัตโนมัติ (ยังไม่มี user gesture) — Web Speech ก็จะโดนบล็อกเหมือนกัน
                // จึงหยุดเงียบๆ ดีกว่าไล่ยิงทั้งคิวให้ error รัวๆ
                if (err && err.name === 'NotAllowedError') { this.stopSpeak(); return; }
                fallback();
            });
        },

        // สำรอง: เสียงสังเคราะห์ในเครื่อง (Web Speech) — เลือกเสียงไทยของ Google ก่อนถ้ามี
        _speakNative(text, seq, done) {
            const synth = window.speechSynthesis;
            const fin = () => { if (seq === this._speakSeq) done && done(); };
            if (!synth || seq !== this._speakSeq) { fin(); return; }
            try {
                const u = new SpeechSynthesisUtterance(text);
                u.lang = 'th-TH';
                const voices = synth.getVoices() || [];
                const v = voices.find(x => /^th/i.test(x.lang) && /google/i.test(x.name))
                       || voices.find(x => /^th/i.test(x.lang));
                if (v) u.voice = v;

                let settled = false;
                let started = false;
                let ticks = 0;
                let poll = null;
                const end = () => {
                    if (poll) { clearInterval(poll); poll = null; }
                    if (settled) return;
                    settled = true;
                    fin();
                };
                u.onstart = () => { started = true; };
                u.onend = end;
                u.onerror = end;
                this._native = u;
                synth.speak(u);

                // เครื่องที่ไม่มีเสียงไทยเลย บางเบราว์เซอร์ไม่ยิง onend/onerror → เฝ้าดูสถานะเอง ไม่ให้คิวค้าง
                if (!settled) {
                    poll = setInterval(() => {
                        if (settled) { clearInterval(poll); poll = null; return; }
                        ticks++;
                        if (seq !== this._speakSeq) { end(); return; }
                        // ต้องรอให้ "เริ่มพูดแล้วจริง" ก่อน ถึงจะเชื่อค่า speaking/pending ได้
                        // (ช่วงแรกบางเบราว์เซอร์รายงานว่าไม่ได้พูดและไม่ได้รอ ทั้งที่กำลังจะพูด)
                        if (started && !synth.speaking && !synth.pending) { end(); return; }
                        if (!started && ticks >= 6) end();   // ~4 วิยังไม่เริ่ม = เครื่องนี้พูดไทยไม่ได้ ไปท่อนต่อไป
                    }, 700);
                }
            } catch (e) { fin(); }
        },

        stopSpeak() {
            this._speakSeq++; // invalidate เซสชันพูดเก่า → callback ที่ค้างจะ bail ไม่ stomp อารมณ์
            this._ttsQueue = [];
            if (this._audio) {
                try { this._audio.onended = null; this._audio.onerror = null; this._audio.pause(); } catch (e) {}
                this._audio = null;
            }
            if (this._preload) {
                try { this._preload.audio.src = ''; } catch (e) {}   // ยกเลิกท่อนที่พรีโหลดค้าง
                this._preload = null;
            }
            if (this._native) {
                try { window.speechSynthesis && window.speechSynthesis.cancel(); } catch (e) {}
                this._native = null;
            }
            this.speaking = false;
            if (this.emotion === 'talking') this.emotion = 'idle';
        }
    };
}
</script>
@endpush
@endif
