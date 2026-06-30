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
.eve-ava .eve-brow.l{left:9%;top:16%;transform:rotate(-7deg)}
.eve-ava .eve-brow.r{right:9%;top:16%;transform:rotate(7deg)}
.eve-ava .eve-eye{position:absolute;top:38%;width:17%;height:25%;background:#3a2d3a;border-radius:48%;overflow:hidden;transform-origin:center}
.eve-ava .eve-eye.l{left:17%}
.eve-ava .eve-eye.r{right:17%}
.eve-ava .eve-hi{position:absolute;top:12%;left:44%;width:34%;height:30%;background:#fff;border-radius:50%}
.eve-ava .eve-hi2{position:absolute;bottom:16%;left:16%;width:20%;height:18%;background:#fff;opacity:.7;border-radius:50%}
.eve-ava .eve-nose{position:absolute;left:47.5%;top:55%;width:5%;height:6%;background:#e3a890;border-radius:50%;opacity:.7}
.eve-ava .eve-mouth{position:absolute;left:50%;top:64%;transform:translateX(-50%);width:17%;height:10%;border-bottom:3px solid #b56b7e;border-radius:0 0 70% 70%}
.eve-ava .eve-dots{position:absolute;right:-26%;top:0;color:#7a5cff;font-size:.4em;font-weight:700;letter-spacing:1px;display:none}
.eve-ava .eve-spark{position:absolute;top:4%;right:-4%;color:#ffce4d;font-size:.5em;display:none}

/* face-crop badge (ซูมหน้า) */
.eve-ava.eve-crop{overflow:hidden;border-radius:50%}
.eve-ava.eve-crop .eve-in{position:absolute;width:270%;height:270%;left:-88%;top:-18%}

/* blink (idle + พื้นฐาน) */
@keyframes eve-blink{0%,93%,100%{transform:scaleY(1)}96%{transform:scaleY(.1)}}
.eve-ava .eve-eye{animation:eve-blink 4.6s infinite}

/* ── talking: ปากอ้าขยับ ── */
.eve-ava.eve-talking .eve-mouth{width:15%;height:18%;background:#9a3b54;border:0;border-radius:50%;box-shadow:inset 0 -30% 0 #c75d76;animation:eve-talk .25s infinite}
@keyframes eve-talk{0%,100%{height:7%}50%{height:20%}}

/* ── thinking: ตาเหลือบบน + จุด ... ── */
.eve-ava.eve-thinking .eve-eye{height:20%;top:34%;animation:none}
.eve-ava.eve-thinking .eve-hi{top:6%}
.eve-ava.eve-thinking .eve-mouth{width:11%;height:7%}
.eve-ava.eve-thinking .eve-dots{display:block;animation:eve-dots 1.2s infinite}
@keyframes eve-dots{0%,100%{opacity:.3}50%{opacity:1}}

/* ── happy: ตาโค้ง ^_^ + ยิ้มกว้าง + ประกาย ── */
.eve-ava.eve-happy .eve-eye{height:12%;top:46%;background:transparent;border-bottom:4px solid #3a2d3a;border-radius:0 0 60% 60%;animation:none;overflow:visible}
.eve-ava.eve-happy .eve-hi,.eve-ava.eve-happy .eve-hi2{display:none}
.eve-ava.eve-happy .eve-mouth{width:24%;height:19%;background:#9a3b54;border:0;border-radius:0 0 55% 55%;box-shadow:inset 0 40% 0 #fff}
.eve-ava.eve-happy .eve-spark{display:block;animation:eve-dots 1s infinite}
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
            <div class="eve-mouth"></div>
            <div class="eve-dots">●●●</div>
            <div class="eve-spark">✦</div>
        </div>
    </div>
</div>
