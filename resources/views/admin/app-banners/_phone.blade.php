{{--
    กรอบมือถือจำลองการแสดงแบนเนอร์ในแอป (ใช้ใน index และ form)

    ต้องอยู่ใน Alpine scope ที่มี:
      slides          array ของ {title, subtitle, image_url, cta_label}
      current         index สไลด์ที่แสดง
      placementLabel  ชื่อตำแหน่งแสดง (ข้อความไทย)
      go(i)           เลื่อนไปสไลด์ที่ i
    ข้อความไทยวางทับบนรูป (รูปแบนเนอร์ไม่มีตัวหนังสือ) เหมือนที่แอปทำจริง
--}}
@push('styles')
<style>
    .ab-phone {
        width:min(300px, 100%); margin:0 auto; padding:10px; border-radius:42px;
        background:linear-gradient(160deg, rgba(52,46,38,.97), rgba(20,17,13,.99));
        box-shadow:var(--card-shadow), 0 22px 44px rgba(0,0,0,.28), inset 0 1px 0 rgba(255,255,255,.12);
    }
    .ab-screen { position:relative; border-radius:33px; overflow:hidden; background:var(--bg); min-height:540px; display:flex; flex-direction:column; }
    .ab-notch { position:absolute; top:8px; left:50%; transform:translateX(-50%); width:92px; height:22px; border-radius:14px; background:rgba(20,17,13,.99); z-index:3; }
    .ab-status { display:flex; justify-content:space-between; align-items:center; padding:10px 22px 4px; font-size:11px; font-weight:700; color:var(--ink); }
    .ab-appbar { margin:6px 12px 10px; padding:10px 14px; border-radius:16px; display:flex; align-items:center; justify-content:space-between; gap:8px;
        background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:var(--ab-on, #fff); box-shadow:var(--raise); }
    .ab-slides { display:grid; grid-template-columns:minmax(0, 1fr); }
    .ab-slides > .ab-slide { grid-area:1 / 1; min-width:0; }
    .ab-slide { position:relative; margin:0 12px; aspect-ratio:2 / 1; border-radius:18px; overflow:hidden; background:var(--surf); box-shadow:var(--card-shadow-sm); }
    .ab-slide img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
    .ab-scrim { position:absolute; inset:0; background:linear-gradient(90deg, rgba(0,0,0,.66) 0%, rgba(0,0,0,.32) 55%, rgba(0,0,0,0) 100%); }
    .ab-copy { position:absolute; left:12px; right:40%; bottom:10px; top:10px; display:flex; flex-direction:column; justify-content:center; gap:4px; color:var(--ab-on, #fff); text-shadow:0 1px 3px rgba(0,0,0,.45); }
    .ab-copy .t { font-weight:800; font-size:13.5px; line-height:1.25; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    .ab-copy .s { font-size:10.5px; line-height:1.35; opacity:.95; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    .ab-cta { align-self:flex-start; margin-top:4px; padding:6px 12px; border-radius:11px; font-size:10.5px; font-weight:800; color:var(--ab-on, #fff); text-shadow:0 1px 1px rgba(0,0,0,.2);
        background:linear-gradient(180deg, var(--accent1), var(--accent2));
        box-shadow:0 3px 0 var(--deep2), 0 6px 12px rgba(0,0,0,.28), inset 0 1px 0 rgba(255,255,255,.45); }
    .ab-dots { display:flex; justify-content:center; gap:6px; margin:10px 0 12px; }
    .ab-dots button { width:8px; height:8px; border-radius:6px; border:0; padding:0; cursor:pointer; background:color-mix(in srgb, var(--ink2) 40%, transparent); transition:width .25s ease; }
    .ab-dots button.on { width:22px; background:linear-gradient(90deg, var(--accent1), var(--accent2)); }
    .ab-skel { margin:0 12px 10px; height:52px; border-radius:14px; box-shadow:var(--inset-sm); background:var(--surf); }
    .ab-empty { margin:0 12px; aspect-ratio:2 / 1; border-radius:18px; display:grid; place-items:center; text-align:center; padding:12px; font-size:12px; color:var(--ink2); box-shadow:var(--inset-sm); background:var(--surf); }
</style>
@endpush

<div class="ab-phone" aria-label="ตัวอย่างการแสดงผลในแอป">
    <div class="ab-screen">
        <div class="ab-notch" aria-hidden="true"></div>
        <div class="ab-status" aria-hidden="true">
            <span class="tp-num">9:41</span>
            <span style="display:flex; gap:5px;"><i class="fas fa-signal"></i><i class="fas fa-wifi"></i><i class="fas fa-battery-three-quarters"></i></span>
        </div>
        <div class="ab-appbar">
            <span style="font-weight:800; font-size:13px;"><i class="fas fa-bolt"></i> ไทยพร้อม</span>
            <span class="tp-pill" style="background:rgba(255,255,255,.22); color:var(--ab-on, #fff);" x-text="placementLabel"></span>
        </div>

        <template x-if="slides.length === 0">
            <div class="ab-empty">ยังไม่มีแบนเนอร์ที่กำลังแสดงในตำแหน่งนี้</div>
        </template>

        {{-- สไลด์ซ้อนช่องเดียวกัน (grid) → ช่วง fade ไม่ต่อกันลงล่าง กรอบไม่กระโดด --}}
        <div class="ab-slides">
            <template x-for="(s, i) in slides" :key="'slide-' + i">
                <div class="ab-slide" x-show="i === current" x-transition.opacity.duration.400ms>
                    <img :src="s.image_url || ''" :alt="s.title || 'แบนเนอร์'" x-show="s.image_url" loading="lazy">
                    <div class="ab-scrim"></div>
                    <div class="ab-copy">
                        <div class="t" x-text="s.title || 'หัวข้อแบนเนอร์'"></div>
                        <div class="s" x-show="s.subtitle" x-text="s.subtitle"></div>
                        <span class="ab-cta" x-show="s.cta_label" x-text="s.cta_label"></span>
                    </div>
                </div>
            </template>
        </div>

        <div class="ab-dots" x-show="slides.length > 1">
            <template x-for="(s, i) in slides" :key="'dot-' + i">
                <button type="button" :class="i === current ? 'on' : ''" @click="go(i)" :aria-label="'สไลด์ที่ ' + (i + 1)"></button>
            </template>
        </div>
        <div x-show="slides.length <= 1" style="height:14px;"></div>

        <div class="ab-skel"></div>
        <div class="ab-skel" style="height:80px;"></div>
        <div class="ab-skel" style="height:40px; width:60%;"></div>
    </div>
</div>
