{{--
  น้อง Eve — อวาตาร์ + หน้าใหม่ที่แสดงอารมณ์ได้ (ซ้อนทับหน้าเดิมของ SVG)
  base = idle · เพิ่มคลาส eve-talking / eve-thinking / eve-happy เพื่อเปลี่ยนอารมณ์
  props:
    size : ขนาด (px)
    crop : true = ซูมเฉพาะหน้า (วงกลม badge) ให้เห็นอารมณ์ชัดในที่เล็กๆ
  ใช้ใน Alpine: <x-eve.avatar :size="58" crop x-bind:class="'eve-'+emotion" />
--}}
@props(['size' => 110, 'crop' => false])
@once
<style>
.eve-ava{position:relative;width:var(--eve-w,110px);height:var(--eve-w,110px);display:inline-block;line-height:0;user-select:none}
.eve-ava .eve-in{position:relative;width:100%;height:100%}
.eve-ava .eve-body{width:100%;height:100%;display:block;pointer-events:none}
.eve-ava .eve-face{position:absolute;left:38.5%;top:17.5%;width:25%;height:21%;pointer-events:none}
.eve-ava .eve-skin{position:absolute;left:-6%;top:8%;width:112%;height:86%;background:radial-gradient(ellipse at 50% 40%,#fde9d9 60%,#f7dcc6 100%);border-radius:50% 50% 48% 48%}
.eve-ava .eve-lash{position:absolute;top:28%;width:20%;height:8%;background:#2a1f2e;border-radius:40%}
.eve-ava .eve-lash.l{left:13%}.eve-ava .eve-lash.r{right:13%}
.eve-ava .eve-eye{position:absolute;top:34%;width:18%;height:27%;background:#3a2b3f;border-radius:50%;overflow:hidden;transform-origin:center}
.eve-ava .eve-eye.l{left:14%}.eve-ava .eve-eye.r{right:14%}
.eve-ava .eve-hi{position:absolute;top:14%;left:46%;width:32%;height:30%;background:#fff;border-radius:50%}
.eve-ava .eve-hi2{position:absolute;bottom:16%;left:18%;width:18%;height:18%;background:#fff;opacity:.7;border-radius:50%}
.eve-ava .eve-nose{position:absolute;left:49%;top:58%;width:5%;height:5%;background:#e7b89c;border-radius:50%}
.eve-ava .eve-blush{position:absolute;top:62%;width:18%;height:13%;background:#f7a8b8;opacity:.55;border-radius:50%;filter:blur(.5px)}
.eve-ava .eve-blush.l{left:2%}.eve-ava .eve-blush.r{right:2%}
.eve-ava .eve-mouth{position:absolute;left:50%;top:74%;transform:translateX(-50%);width:22%;height:11%;border-bottom:3px solid #c4708a;border-radius:0 0 60% 60%}
.eve-ava .eve-dots{position:absolute;right:-30%;top:-8%;color:#7a5cff;font-size:.42em;font-weight:700;letter-spacing:1px;display:none}
.eve-ava .eve-spark{position:absolute;top:4%;right:-8%;color:#ffce4d;font-size:.5em;display:none}

/* face-crop badge (ซูมหน้า) */
.eve-ava.eve-crop{overflow:hidden;border-radius:50%}
.eve-ava.eve-crop .eve-in{position:absolute;width:270%;height:270%;left:-88%;top:-18%}

/* blink (idle + พื้นฐาน) */
@keyframes eve-blink{0%,93%,100%{transform:scaleY(1)}96%{transform:scaleY(.12)}}
.eve-ava .eve-eye{animation:eve-blink 4.6s infinite}

/* ── talking: ปากอ้าขยับ ── */
.eve-ava.eve-talking .eve-mouth{width:20%;height:22%;background:#9a3b54;border:0;border-radius:50%;box-shadow:inset 0 -30% 0 #c75d76;animation:eve-talk .25s infinite}
@keyframes eve-talk{0%,100%{height:9%}50%{height:24%}}

/* ── thinking: ตาเหลือบบน + จุด ... ── */
.eve-ava.eve-thinking .eve-eye{height:22%;top:30%;animation:none}
.eve-ava.eve-thinking .eve-hi{top:4%}
.eve-ava.eve-thinking .eve-mouth{width:13%;height:8%;border-bottom-width:3px}
.eve-ava.eve-thinking .eve-dots{display:block;animation:eve-dots 1.2s infinite}
@keyframes eve-dots{0%,100%{opacity:.3}50%{opacity:1}}

/* ── happy: ตาโค้ง ^_^ + ยิ้มกว้าง + ประกาย ── */
.eve-ava.eve-happy .eve-eye{height:13%;top:42%;background:transparent;border-bottom:4px solid #3a2b3f;border-radius:0 0 60% 60%;animation:none;overflow:visible}
.eve-ava.eve-happy .eve-hi,.eve-ava.eve-happy .eve-hi2,.eve-ava.eve-happy .eve-lash{display:none}
.eve-ava.eve-happy .eve-mouth{width:30%;height:24%;background:#9a3b54;border:0;border-radius:0 0 55% 55%;box-shadow:inset 0 38% 0 #fff}
.eve-ava.eve-happy .eve-blush{opacity:.8;width:22%;height:15%}
.eve-ava.eve-happy .eve-spark{display:block;animation:eve-dots 1s infinite}
</style>
@endonce
<div {{ $attributes->merge(['class' => 'eve-ava'.($crop ? ' eve-crop' : '')]) }} style="--eve-w:{{ (int) $size }}px">
    <div class="eve-in">
        <img class="eve-body" src="{{ asset('images/eve/eve-body.svg') }}" alt="น้อง Eve" loading="lazy">
        <div class="eve-face">
            <div class="eve-skin"></div>
            <div class="eve-lash l"></div><div class="eve-lash r"></div>
            <div class="eve-eye l"><span class="eve-hi"></span><span class="eve-hi2"></span></div>
            <div class="eve-eye r"><span class="eve-hi"></span><span class="eve-hi2"></span></div>
            <div class="eve-nose"></div>
            <div class="eve-blush l"></div><div class="eve-blush r"></div>
            <div class="eve-mouth"></div>
            <div class="eve-dots">●●●</div>
            <div class="eve-spark">✦</div>
        </div>
    </div>
</div>
