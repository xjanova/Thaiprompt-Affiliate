{{--
  น้อง Eve — อวาตาร์ + หน้าใหม่ที่แสดงอารมณ์ได้
  ⚠️ SVG (public/images/eve/eve-body.svg) ถูก "ลบตา/ปาก/พื้นหลังเก่าออกแล้ว" (path #0,#909,#910,#913)
     เหลือผิว+ผม+แก้ม = ผืนผ้าใบสะอาด → เราวาดตา/คิ้ว/จมูก/ปากใหม่ทับลงบนผิวจริง (ไม่มี skin patch, ไม่ทับโครงเก่า)
  base = idle · เพิ่มคลาส eve-talking / eve-thinking / eve-happy เพื่อเปลี่ยนอารมณ์
  props: size (px) · crop (true = ซูมเฉพาะหน้าเป็น badge วงกลม)
  ใช้ใน Alpine: <x-eve.avatar :size="56" crop x-bind:class="'eve-'+emotion" />
--}}
@props(['size' => 110, 'crop' => false])
@once
<style>
.eve-ava{position:relative;width:var(--eve-w,110px);height:var(--eve-w,110px);display:inline-block;line-height:0;user-select:none}
.eve-ava .eve-in{position:relative;width:100%;height:100%}
.eve-ava .eve-body{width:100%;height:100%;display:block;pointer-events:none}
.eve-ava .eve-face{position:absolute;left:38.3%;top:17.5%;width:25.4%;height:21%;pointer-events:none}
.eve-ava .eve-brow{position:absolute;height:3.4%;width:19%;background:#5a4a52;border-radius:50%}
/* ⬇️ ระยะห่างตา: ยิ่ง inset มาก = ตายิ่งชิดกัน
   ช่องว่างระหว่างตา = 100% − (inset×2) − (width×2)
   เดิม inset 17% → ห่าง 32% · ปัจจุบัน inset 20% → ห่าง 26% (owner ขอ "ใกล้อีกนิด")
   คิ้วขยับตามด้วยระยะเท่ากัน (9%→12%) เพื่อรักษาสัดส่วนคิ้ว-ตาเดิมไว้ */
.eve-ava .eve-brow.l{left:12%;top:16%;transform:rotate(-7deg)}
.eve-ava .eve-brow.r{right:12%;top:16%;transform:rotate(7deg)}
.eve-ava .eve-eye{position:absolute;top:38%;width:17%;height:25%;background:#3a2d3a;border-radius:48%;overflow:hidden;transform-origin:center}
.eve-ava .eve-eye.l{left:20%}
.eve-ava .eve-eye.r{right:20%}
.eve-ava .eve-hi{position:absolute;top:12%;left:44%;width:34%;height:30%;background:#fff;border-radius:50%}
.eve-ava .eve-hi2{position:absolute;bottom:16%;left:16%;width:20%;height:18%;background:#fff;opacity:.7;border-radius:50%}
.eve-ava .eve-nose{position:absolute;left:47.5%;top:55%;width:5%;height:6%;background:#e3a890;border-radius:50%;opacity:.7}
.eve-ava .eve-mouth{position:absolute;left:50%;top:64%;transform:translateX(-50%);width:17%;height:10%;border-bottom:3px solid #b56b7e;border-radius:0 0 70% 70%;overflow:hidden}
/* ฟัน/ลิ้น — ซ่อนไว้เป็นค่าเริ่มต้น มีเฉพาะ viseme ที่ต้องใช้เท่านั้นที่เปิด
   (อารมณ์ idle/thinking/happy จึงหน้าตาเหมือนเดิมทุกประการ) */
.eve-ava .eve-teeth,.eve-ava .eve-tongue{position:absolute;left:50%;transform:translateX(-50%);opacity:0}
.eve-ava .eve-teeth{top:0;width:96%;height:34%;background:#fff;border-radius:0 0 26% 26%}
.eve-ava .eve-tongue{bottom:-6%;width:62%;height:44%;background:#d4657f;border-radius:50% 50% 42% 42%}
.eve-ava .eve-dots{position:absolute;right:-26%;top:0;color:#7a5cff;font-size:.4em;font-weight:700;letter-spacing:1px;display:none}
.eve-ava .eve-spark{position:absolute;top:4%;right:-4%;color:#ffce4d;font-size:.5em;display:none}

/* face-crop badge (ซูมหน้า) */
.eve-ava.eve-crop{overflow:hidden;border-radius:50%}
.eve-ava.eve-crop .eve-in{position:absolute;width:270%;height:270%;left:-88%;top:-18%}

/* blink (idle + พื้นฐาน) */
@keyframes eve-blink{0%,93%,100%{transform:scaleY(1)}96%{transform:scaleY(.1)}}
.eve-ava .eve-eye{animation:eve-blink 4.6s infinite}

/* ── talking: ปากอ้าขยับ ──
   ค่าเริ่มต้น = คีย์เฟรมเด้งขึ้นลงแบบเดิม ใช้เป็น "ตัวสำรอง" เมื่อ JS ยังไม่ได้สั่ง viseme
   (เช่น เบราว์เซอร์ไม่มี Web Audio หรือสคริปต์ยังไม่ทำงาน) — ปากจึงไม่มีทางค้างนิ่ง

   ⚠️ ห้ามใช้ box-shadow ทำเงาริมฝีปาก
      ค่า offset/blur/spread ของ box-shadow รับได้เฉพาะ <length> เท่านั้น "เปอร์เซ็นต์ใช้ไม่ได้"
      เบราว์เซอร์จะทิ้งทั้งบรรทัดเงียบๆ — ของเดิมที่เขียนว่า `inset 0 -30% 0 #c75d76`
      จึงไม่เคยแสดงผลเลยตั้งแต่แรก (เช่นเดียวกับ `inset 0 40% 0 #fff` ของอารมณ์ happy)
      ใช้ linear-gradient แทน เพราะ % ในตำแหน่ง color-stop ใช้ได้จริงและยืดตามขนาดปาก */
.eve-ava.eve-talking .eve-mouth{width:15%;height:18%;background:linear-gradient(#8e3450 0 68%,#c75d76 68%);border:0;border-radius:50%;animation:eve-talk .25s infinite}
@keyframes eve-talk{0%,100%{height:7%}50%{height:20%}}

/* ── visemes: รูปปากขณะออกเสียงจริง ──
   ทุกคลาสมีผลเฉพาะตอน .eve-talking เท่านั้น → ไม่แตะอารมณ์อื่น
   ตั้ง animation:none เพื่อยึดคีย์เฟรมสำรองด้านบนออก แล้วให้ JS เป็นคนสลับรูปแทน
   ความต่างของแต่ละรูปมาจาก 4 อย่างที่ใช้ได้จริง: กว้าง/สูง/ความมน/ตำแหน่งเส้นแบ่งริมฝีปากใน gradient
   บวกกับฟัน-ลิ้นที่เป็น element จริง (ไม่ใช่เงาปลอม) จึงเห็นความต่างชัดทุกรูป */
.eve-ava[class*="eve-v-"].eve-talking .eve-mouth{animation:none;transition:width .07s linear,height .07s linear,border-radius .07s linear}
.eve-ava[class*="eve-v-"].eve-talking .eve-teeth,
.eve-ava[class*="eve-v-"].eve-talking .eve-tongue{transition:opacity .07s linear,height .07s linear}

/* ปิดสนิท — ม บ ป (M/B/P) : เกือบเป็นเส้น ริมฝีปากล่างกินพื้นที่เกือบทั้งหมด */
.eve-ava.eve-talking.eve-v-mbp .eve-mouth{width:19%;height:5%;border-radius:40%;background:linear-gradient(#8e3450 0 34%,#c75d76 34%)}
/* อ้ากว้าง — อา (AA) : ช่องปากลึก เห็นลิ้น */
.eve-ava.eve-talking.eve-v-aa .eve-mouth{width:20%;height:27%;border-radius:44%;background:linear-gradient(#7d2c45 0 80%,#c75d76 80%)}
.eve-ava.eve-talking.eve-v-aa .eve-tongue{opacity:.85}
/* กว้างแบน เห็นฟัน — อี แอ (EE) */
.eve-ava.eve-talking.eve-v-ee .eve-mouth{width:27%;height:11%;border-radius:32%;background:linear-gradient(#8e3450 0 62%,#c75d76 62%)}
.eve-ava.eve-talking.eve-v-ee .eve-teeth{opacity:1}
/* กลมเล็ก — อู โอ ว (OO/W) : ห่อปาก ริมฝีปากหนาขึ้น */
.eve-ava.eve-talking.eve-v-oo .eve-mouth{width:11%;height:16%;border-radius:50%;background:linear-gradient(#7d2c45 0 58%,#d0687f 58%)}
/* อ้ากลาง — เอ เออ (EH) : เห็นลิ้นรางๆ */
.eve-ava.eve-talking.eve-v-eh .eve-mouth{width:18%;height:16%;border-radius:42%;background:linear-gradient(#8e3450 0 72%,#c75d76 72%)}
.eve-ava.eve-talking.eve-v-eh .eve-tongue{opacity:.45}
/* ฟันแตะริมฝีปากล่าง — ฟ ว (F/V) : ฟันบนกดลงบนริมฝีปากล่าง */
.eve-ava.eve-talking.eve-v-fv .eve-mouth{width:16%;height:9%;border-radius:26%;background:linear-gradient(#8e3450 0 50%,#d0687f 50%)}
.eve-ava.eve-talking.eve-v-fv .eve-teeth{opacity:1;height:56%}
/* พักระหว่างคำ — แง้มนิดเดียว */
.eve-ava.eve-talking.eve-v-rest .eve-mouth{width:15%;height:8%;border-radius:44%;background:linear-gradient(#8e3450 0 46%,#c75d76 46%)}

/* ── thinking: ตาเหลือบบน + จุด ... ── */
.eve-ava.eve-thinking .eve-eye{height:20%;top:34%;animation:none}
.eve-ava.eve-thinking .eve-hi{top:6%}
.eve-ava.eve-thinking .eve-mouth{width:11%;height:7%}
.eve-ava.eve-thinking .eve-dots{display:block;animation:eve-dots 1.2s infinite}
@keyframes eve-dots{0%,100%{opacity:.3}50%{opacity:1}}

/* ── happy: ตาโค้ง ^_^ + ยิ้มกว้าง + ประกาย ── */
.eve-ava.eve-happy .eve-eye{height:12%;top:46%;background:transparent;border-bottom:4px solid #3a2d3a;border-radius:0 0 60% 60%;animation:none;overflow:visible}
.eve-ava.eve-happy .eve-hi,.eve-ava.eve-happy .eve-hi2{display:none}
/* ⚠️ เดิมใช้ `box-shadow:inset 0 40% 0 #fff` วาดฟันขาว — % ใช้กับ box-shadow ไม่ได้
   เบราว์เซอร์ทิ้งบรรทัดนั้นมาตลอด ฟันของรอยยิ้มจึงไม่เคยขึ้นจริง เปลี่ยนมาใช้ gradient */
.eve-ava.eve-happy .eve-mouth{width:24%;height:19%;background:linear-gradient(#fff 0 40%,#9a3b54 40%);border:0;border-radius:0 0 55% 55%}
.eve-ava.eve-happy .eve-spark{display:block;animation:eve-dots 1s infinite}

/* ── เคารพ prefers-reduced-motion ──
   ผู้ใช้ที่ตั้งค่าลดการเคลื่อนไหว: ไม่กะพริบตา ไม่เด้งปาก ไม่สลับรูปปากแบบมี transition
   แต่ยัง "แง้มปาก" ค้างไว้ตอนพูด เพื่อให้ยังสื่อได้ว่ากำลังพูดอยู่ */
@media (prefers-reduced-motion: reduce) {
    .eve-ava .eve-eye{animation:none}
    .eve-ava.eve-talking .eve-mouth{animation:none}
    .eve-ava[class*="eve-v-"].eve-talking .eve-mouth,
    .eve-ava[class*="eve-v-"].eve-talking .eve-teeth,
    .eve-ava[class*="eve-v-"].eve-talking .eve-tongue{transition:none}
    .eve-ava.eve-thinking .eve-dots,.eve-ava.eve-happy .eve-spark{animation:none}
}
</style>
@endonce
<div {{ $attributes->merge(['class' => 'eve-ava'.($crop ? ' eve-crop' : '')]) }} style="--eve-w:{{ (int) $size }}px">
    <div class="eve-in">
        {{-- ?v=mtime กัน Cloudflare/เบราว์เซอร์ cache SVG เก่า (ชื่อไฟล์เดิม) — เปลี่ยนเองทุกครั้งที่ไฟล์อัปเดต --}}
        <img class="eve-body" src="{{ asset('images/eve/eve-body.svg') }}?v={{ @filemtime(public_path('images/eve/eve-body.svg')) ?: '3' }}" alt="น้อง Eve" loading="lazy">
        <div class="eve-face">
            <div class="eve-brow l"></div><div class="eve-brow r"></div>
            <div class="eve-eye l"><span class="eve-hi"></span><span class="eve-hi2"></span></div>
            <div class="eve-eye r"><span class="eve-hi"></span><span class="eve-hi2"></span></div>
            <div class="eve-nose"></div>
            <div class="eve-mouth"><span class="eve-teeth"></span><span class="eve-tongue"></span></div>
            <div class="eve-dots">●●●</div>
            <div class="eve-spark">✦</div>
        </div>
    </div>
</div>
