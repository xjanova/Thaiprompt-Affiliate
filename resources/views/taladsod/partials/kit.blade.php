{{--
 | ชุดสไตล์ + สคริปต์กลางของหน้าตลาดสด ธีม V4 (ใช้ได้ทั้ง frontend-v4 และ user-v4)
 | ใส่ในหน้า:  @include('taladsod.partials.kit')   (คู่กับ <x-theme-v4.shop-kit /> สำหรับคลาส sf-*)
 |
 | คลาส ts-*: ป้าย/โทนสี, ปุ่ม 3 มิติ, การ์ดร้าน, แผนที่, แถบล่างมือถือ, กล่องยืนยัน, สวิตช์, ดาวรีวิว
 | สคริปต์ window.ts: req()/get()/post()/put()/del() (JSON + CSRF, ข้อความไทยเสมอ), geo(), money(), distance(), timeAgo()
 | คอมโพเนนต์ Alpine กลาง: tsPinMap(cfg) — แผนที่ปักหมุด (ลากหมุด/แตะแผนที่/ใช้ตำแหน่งปัจจุบัน)
 | สีทั้งหมดอ้างตัวแปรธีม — ค่า hex มีเฉพาะเป็นค่าสำรองใน var() เท่านั้น
 --}}
@once
@push('styles')
<style>
    .tp-root, .ts-scope {
        --ts-ok: var(--sf-ok, #4f9e7e);
        --ts-warn: var(--tp-warn, #e0a52e);
        --ts-bad: var(--sf-sale, #d9534f);
        --ts-info: var(--tp-info, #5689b8);
        --ts-on: var(--on-accent, #fff);
        --tone: var(--accent1);
    }
    .ts-tone-ok { --tone: var(--ts-ok); }
    .ts-tone-warn { --tone: var(--ts-warn); }
    .ts-tone-bad { --tone: var(--ts-bad); }
    .ts-tone-info { --tone: var(--ts-info); }
    .ts-tone-gold { --tone: var(--accent1); }
    .ts-tone-deep { --tone: var(--deep2); }
    .ts-tone-muted { --tone: var(--ink2); }

    .ts-row { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
    .ts-stack { display: flex; flex-direction: column; gap: 14px; min-width: 0; }
    .ts-grid { display: grid; gap: 14px; grid-template-columns: repeat(auto-fit, minmax(min(100%, var(--ts-min, 220px)), 1fr)); }
    .ts-muted { color: var(--ink2); }
    .ts-small { font-size: 12px; }
    .ts-h1 { margin: 0; font-size: clamp(22px, 4.6vw, 30px); font-weight: 800; line-height: 1.25; color: var(--ink); overflow-wrap: anywhere; }
    .ts-h2 { margin: 0; font-size: 16.5px; font-weight: 800; color: var(--ink); display: flex; align-items: center; gap: 9px; }
    .ts-num { font-family: var(--tp-font-num); font-weight: 800; letter-spacing: -.3px; }
    .ts-money { font-family: var(--tp-font-num); font-weight: 800; color: var(--deep1); }
    .ts-link { color: var(--deep1); font-weight: 700; text-decoration: none; }
    .ts-link:hover { text-decoration: underline; }
    .ts-divider { height: 1px; border: 0; margin: 4px 0; background: color-mix(in srgb, var(--ink2) 20%, transparent); }
    .ts-clamp2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

    /* ป้ายตามโทน */
    .ts-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 700; line-height: 1; padding: 6px 11px; border-radius: 999px; white-space: nowrap;
        color: color-mix(in srgb, var(--tone) 82%, var(--ink)); background: color-mix(in srgb, var(--tone) 15%, transparent); }
    .ts-pill.solid { color: var(--ts-on); background: var(--tone); box-shadow: 0 3px 10px color-mix(in srgb, var(--tone) 35%, transparent); text-shadow: 0 1px 1px rgba(0,0,0,.15); }
    .ts-dot { width: 8px; height: 8px; border-radius: 50%; flex: none; background: var(--tone); box-shadow: 0 0 0 4px color-mix(in srgb, var(--tone) 22%, transparent); }
    .ts-dot.live { animation: tpPulse 1.5s ease-in-out infinite; }

    /* ปุ่ม 3 มิติ (เหมือนปุ่มในแอป) — ยกตัว มีขอบสว่าง กดแล้วยุบ */
    .ts-btn3d { position: relative; display: inline-flex; align-items: center; justify-content: center; gap: 9px; min-height: 48px; padding: 0 20px;
        border: 0; border-radius: 16px; cursor: pointer; font-family: inherit; font-weight: 800; font-size: 14.5px; line-height: 1.2; text-align: center;
        color: var(--ts-on) !important; text-decoration: none; text-shadow: 0 1px 2px rgba(0,0,0,.2); -webkit-tap-highlight-color: transparent;
        background: linear-gradient(180deg, color-mix(in srgb, var(--tone) 70%, var(--ts-on)) 0%, var(--tone) 48%, color-mix(in srgb, var(--tone) 80%, var(--ink)) 100%);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.5), inset 0 -3px 0 rgba(0,0,0,.14), 0 5px 0 color-mix(in srgb, var(--tone) 58%, var(--ink)), 0 12px 22px color-mix(in srgb, var(--tone) 30%, transparent);
        transform: translateY(-2px); transition: transform .12s ease, box-shadow .12s ease, filter .15s ease; }
    .ts-btn3d:hover { filter: brightness(1.05); }
    .ts-btn3d:active { transform: translateY(3px); box-shadow: inset 0 1px 0 rgba(255,255,255,.35), inset 0 -1px 0 rgba(0,0,0,.12), 0 1px 0 color-mix(in srgb, var(--tone) 58%, var(--ink)), 0 4px 10px color-mix(in srgb, var(--tone) 22%, transparent); }
    .ts-btn3d[disabled], .ts-btn3d.is-disabled { filter: grayscale(.6) opacity(.55); cursor: not-allowed; transform: none; }
    .ts-btn3d.block { width: 100%; }
    .ts-btn3d.lg { min-height: 58px; font-size: 16.5px; border-radius: 19px; padding: 0 24px; }
    .ts-btn3d.sm { min-height: 44px; padding: 0 15px; font-size: 13px; border-radius: 13px; }
    .ts-btn3d.soft { color: var(--ink) !important; text-shadow: none; background: linear-gradient(180deg, var(--sl, var(--surf)) 0%, var(--surf) 100%);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.5), 0 4px 0 var(--sd, rgba(0,0,0,.12)), 0 9px 18px rgba(0,0,0,.10); }
    .ts-scope .tp-btn { min-height: 44px; text-decoration: none; }
    .ts-btn-ghost { background: transparent !important; box-shadow: inset 0 0 0 1.5px color-mix(in srgb, var(--tone) 55%, transparent) !important; color: color-mix(in srgb, var(--tone) 85%, var(--ink)) !important; }
    .ts-icon-btn { width: 44px; height: 44px; flex: none; border: 0; border-radius: 13px; cursor: pointer; display: grid; place-items: center; color: var(--ink); background: var(--surf); box-shadow: var(--raise); text-decoration: none; }
    .ts-icon-btn:active { box-shadow: var(--inset-sm); transform: translateY(1px); }
    .ts-spin { animation: tpSpin 1s linear infinite; }

    /* แถบเมนูตลาดสด */
    .ts-nav { display: flex; gap: 8px; overflow-x: auto; padding: 10px 2px 12px; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
    .ts-nav::-webkit-scrollbar { display: none; }
    .ts-nav a { position: relative; flex: none; display: inline-flex; align-items: center; gap: 7px; min-height: 42px; padding: 0 15px; border-radius: 14px; font-size: 13px; font-weight: 700;
        text-decoration: none; color: var(--ink2); background: var(--card-bg); box-shadow: var(--raise); white-space: nowrap; }
    .ts-nav a.is-on { color: var(--deep1); background: var(--a1soft); box-shadow: var(--inset-sm); }
    .ts-nav a.is-cta { color: var(--ts-on); background: linear-gradient(135deg, var(--accent2), var(--deep2)); text-shadow: 0 1px 2px rgba(0,0,0,.15); }
    .ts-badge { position: absolute; top: -6px; right: -6px; min-width: 20px; height: 20px; padding: 0 5px; border-radius: 10px; display: grid; place-items: center;
        font-family: var(--tp-font-num); font-size: 10.5px; font-weight: 800; color: var(--ts-on); background: linear-gradient(135deg, var(--ts-bad), color-mix(in srgb, var(--ts-bad) 70%, var(--ink))); box-shadow: 0 2px 6px rgba(0,0,0,.2); }

    /* แบนเนอร์ (รูปไม่มีตัวหนังสือ — วางข้อความทับ) */
    .ts-hero { position: relative; border-radius: 26px; overflow: hidden; min-height: 230px; background: var(--surf); box-shadow: var(--card-shadow); isolation: isolate; }
    .ts-hero-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: -2; }
    .ts-hero::before { content: ""; position: absolute; inset: 0; z-index: -1; background: linear-gradient(95deg, rgba(0,0,0,.62) 0%, rgba(0,0,0,.35) 45%, rgba(0,0,0,0) 78%); }
    .ts-hero-in { display: flex; flex-direction: column; justify-content: flex-end; gap: 10px; min-height: 230px; padding: clamp(18px, 4vw, 34px); max-width: 620px; color: var(--ts-on); }
    .ts-hero-title { margin: 0; font-size: clamp(24px, 5vw, 38px); font-weight: 800; line-height: 1.18; text-shadow: 0 2px 10px rgba(0,0,0,.35); }
    .ts-hero-sub { margin: 0; font-size: clamp(13.5px, 2.4vw, 16px); line-height: 1.55; opacity: .95; text-shadow: 0 1px 6px rgba(0,0,0,.35); }
    /* สไลด์แบนเนอร์ซ้อนช่องเดียวกัน: ช่วง fade สไลด์เก่า-ใหม่ทับกัน ไม่ต่อกันลงล่าง (หน้าไม่กระตุก) */
    .ts-hero-stack { display: grid; grid-template-columns: minmax(0, 1fr); }
    .ts-hero-stack > .ts-hero { grid-area: 1 / 1; min-width: 0; }
    .ts-hero-dots { position: absolute; right: 16px; bottom: 14px; display: flex; gap: 6px; }
    .ts-hero-dots button { width: 10px; height: 10px; padding: 0; border: 0; border-radius: 6px; cursor: pointer; background: rgba(255,255,255,.55); transition: width .2s ease; }
    .ts-hero-dots button.is-on { width: 26px; background: var(--ts-on); }
    @media (min-width: 900px) { .ts-hero, .ts-hero-in { min-height: 320px; } }

    /* หมวดหมู่ */
    .ts-cat { flex: none; display: flex; flex-direction: column; align-items: center; gap: 7px; width: 86px; padding: 12px 6px; border-radius: 18px; text-decoration: none; color: var(--ink);
        background: var(--card-bg); box-shadow: var(--card-shadow-sm); text-align: center; transition: transform .15s ease; }
    .ts-cat:hover { transform: translateY(-2px); }
    .ts-cat.is-on { box-shadow: var(--inset-sm); color: var(--deep1); }
    .ts-cat-ic { width: 46px; height: 46px; border-radius: 15px; display: grid; place-items: center; font-size: 22px; background: var(--a1soft); box-shadow: var(--inset-sm); overflow: hidden; }
    .ts-cat-ic img { width: 100%; height: 100%; object-fit: cover; }
    .ts-cat span { font-size: 11.5px; font-weight: 700; line-height: 1.3; }

    /* การ์ดร้าน */
    .ts-shop { display: flex; gap: 12px; align-items: flex-start; padding: 14px; border-radius: 20px; background: var(--card-bg); box-shadow: var(--card-shadow); border: var(--card-border);
        text-decoration: none; color: var(--ink); min-width: 0; transition: transform .18s ease, box-shadow .18s ease; }
    .ts-shop:hover { transform: translateY(-2px); box-shadow: var(--card-shadow-hover); }
    .ts-avatar { width: 54px; height: 54px; flex: none; border-radius: 17px; overflow: hidden; display: grid; place-items: center; font-weight: 800; font-size: 20px;
        color: var(--ts-on); background: linear-gradient(135deg, var(--accent1), var(--accent2)); box-shadow: var(--raise); }
    .ts-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .ts-avatar.lg { width: 84px; height: 84px; border-radius: 26px; font-size: 32px; }
    .ts-thumbs { display: flex; gap: 6px; margin-top: 8px; }
    .ts-thumbs img, .ts-thumbs span { width: 42px; height: 42px; border-radius: 11px; object-fit: cover; background: var(--surf); box-shadow: var(--inset-sm); display: grid; place-items: center; font-size: 16px; }

    /* แผนที่ */
    .ts-map { position: relative; z-index: 0; height: 320px; border-radius: 18px; overflow: hidden; background: var(--surf); box-shadow: var(--inset-sm); }
    .ts-map.sm { height: 230px; }
    .ts-map.lg { height: min(62vh, 480px); }
    .ts-map-empty { display: grid; place-items: center; text-align: center; padding: 24px; color: var(--ink2); font-size: 13px; }
    .tp-map-pin.ts-pin-live { box-shadow: 0 0 0 6px color-mix(in srgb, var(--ts-ok) 35%, transparent), 0 6px 14px rgba(0,0,0,.28); }
    .tp-map-pin.ts-pin-closed { filter: grayscale(.8); opacity: .75; }
    .ts-map-popup { min-width: 150px; font-family: var(--tp-font); }
    .ts-map-popup b { display: block; font-size: 13.5px; margin-bottom: 3px; }
    .ts-map-popup span { display: block; font-size: 12px; opacity: .75; margin-bottom: 6px; }
    .ts-map-popup a { font-weight: 700; }

    /* การ์ดตัวเลข */
    .ts-stat { display: flex; flex-direction: column; gap: 6px; min-height: 104px; position: relative; overflow: hidden; }
    .ts-stat .ic { width: 40px; height: 40px; border-radius: 13px; display: grid; place-items: center; font-size: 16px; color: var(--ts-on);
        background: linear-gradient(135deg, color-mix(in srgb, var(--tone) 72%, var(--ts-on)), var(--tone)); box-shadow: 0 6px 14px color-mix(in srgb, var(--tone) 30%, transparent); }
    .ts-stat .lbl { font-size: 12px; font-weight: 700; color: var(--ink2); }
    .ts-stat .val { font-family: var(--tp-font-num); font-size: clamp(20px, 3.6vw, 26px); font-weight: 800; color: var(--ink); line-height: 1.1; }
    .ts-stat .sub { font-size: 11.5px; color: var(--ink2); }

    /* ช่องกรอก */
    .ts-label { display: block; margin: 0 0 7px; font-size: 13px; font-weight: 700; color: var(--ink); }
    .ts-label .req { color: var(--ts-bad); }
    .ts-help { margin: 6px 0 0; font-size: 11.5px; line-height: 1.55; color: var(--ink2); }
    .ts-err { margin: 6px 0 0; font-size: 12px; font-weight: 600; color: var(--ts-bad); }
    .ts-scope .tp-input { min-height: 46px; font-size: 14px; }
    .ts-scope textarea.tp-input { min-height: 90px; resize: vertical; line-height: 1.6; }
    .ts-scope select.tp-input { cursor: pointer; }
    .ts-input-group { display: flex; align-items: center; gap: 8px; }
    .ts-input-group .suffix { flex: none; font-size: 13px; font-weight: 700; color: var(--ink2); }

    /* สวิตช์เปิด/ปิด */
    .ts-switch { display: inline-flex; align-items: center; gap: 12px; cursor: pointer; min-height: 44px; user-select: none; }
    .ts-switch input { position: absolute; opacity: 0; width: 1px; height: 1px; }
    .ts-switch .track { position: relative; width: 52px; height: 30px; flex: none; border-radius: 20px; background: var(--surf); box-shadow: var(--inset-sm); transition: background .2s ease; }
    .ts-switch .track::after { content: ""; position: absolute; top: 3px; left: 3px; width: 24px; height: 24px; border-radius: 50%; background: var(--card-bg); box-shadow: var(--raise); transition: transform .22s cubic-bezier(.2,.8,.3,1.2); }
    .ts-switch input:checked + .track { background: linear-gradient(135deg, var(--ts-ok), color-mix(in srgb, var(--ts-ok) 72%, var(--ink))); }
    .ts-switch input:checked + .track::after { transform: translateX(22px); }
    .ts-switch input:focus-visible + .track { outline: 2px solid var(--accent1); outline-offset: 2px; }

    /* การ์ดตัวเลือก (ตัวเลือกสินค้า / วิธีรับของ / วิธีจ่าย) */
    .ts-choice { position: relative; display: flex; align-items: center; gap: 12px; width: 100%; min-height: 58px; padding: 10px 14px; border-radius: 16px; cursor: pointer; text-align: left;
        font-family: inherit; color: var(--ink); background: var(--surf); box-shadow: var(--raise); border: 2px solid transparent; transition: box-shadow .15s ease, border-color .15s ease, transform .1s ease; }
    .ts-choice:active { transform: translateY(1px); }
    .ts-choice.is-on { box-shadow: var(--inset-sm); border-color: var(--accent1); }
    .ts-choice.is-off { opacity: .5; cursor: not-allowed; }
    .ts-choice .ind { width: 22px; height: 22px; flex: none; border-radius: 50%; display: grid; place-items: center; background: var(--bg); box-shadow: var(--inset-sm); font-size: 11px; color: transparent; }
    .ts-choice .ind.box { border-radius: 7px; }
    .ts-choice.is-on .ind { color: var(--ts-on); background: linear-gradient(135deg, var(--accent1), var(--accent2)); box-shadow: 0 2px 6px color-mix(in srgb, var(--accent1) 40%, transparent); }
    .ts-choice .img { width: 46px; height: 46px; flex: none; border-radius: 12px; object-fit: cover; box-shadow: var(--inset-sm); background: var(--bg); }
    .ts-choice .name { flex: 1; min-width: 0; font-size: 14px; font-weight: 700; line-height: 1.35; }
    .ts-choice .delta { flex: none; font-family: var(--tp-font-num); font-size: 13.5px; font-weight: 800; color: var(--deep1); }

    /* แถบปุ่มติดล่างจอ (มือถือ) */
    .ts-bottom-bar { position: fixed; left: 0; right: 0; bottom: 0; z-index: 45; display: none; align-items: center; gap: 12px; padding: 10px 16px calc(10px + env(safe-area-inset-bottom));
        background: var(--card-bg); -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px); box-shadow: 0 -10px 26px rgba(0,0,0,.12); border-top: var(--card-border); }
    @media (max-width: 980px) { .ts-bottom-bar { display: flex; } .ts-has-bottom-bar { padding-bottom: 96px; } }

    /* กล่องยืนยัน */
    .ts-dialog-bg { position: fixed; inset: 0; z-index: 95; display: flex; align-items: flex-end; justify-content: center; padding: 16px; background: rgba(0,0,0,.45); -webkit-backdrop-filter: blur(3px); backdrop-filter: blur(3px); }
    .ts-dialog { width: 100%; max-width: 480px; max-height: calc(100vh - 32px); overflow-y: auto; padding: 22px !important; border-radius: 24px !important; animation: tpPop .22s ease both; }
    @media (min-width: 640px) { .ts-dialog-bg { align-items: center; } }

    /* ดาวรีวิว */
    .ts-stars { display: inline-flex; gap: 4px; }
    .ts-stars button { width: 44px; height: 44px; border: 0; padding: 0; cursor: pointer; background: transparent; font-size: 26px; color: color-mix(in srgb, var(--ink2) 40%, transparent); transition: transform .1s ease, color .1s ease; }
    .ts-stars button.is-on { color: var(--sf-star, #e6b347); }
    .ts-stars button:active { transform: scale(.9); }
    .ts-star-view { color: var(--sf-star, #e6b347); letter-spacing: 1px; }

    /* สถานะว่าง */
    .ts-empty { display: flex; flex-direction: column; align-items: center; gap: 10px; text-align: center; padding: 38px 18px; }
    .ts-empty .em { font-size: 46px; line-height: 1; }

    /* หน้าสินค้า: จอกว้าง = รูป+ข้อมูลซ้าย / สั่งซื้อขวา · มือถือ = รูป → สั่งซื้อ → ข้อมูลร้าน/รีวิว */
    .ts-pdp { display: grid; gap: 20px; grid-template-columns: minmax(0, 1fr) 380px; grid-template-areas: "gallery buy" "info buy"; align-items: start; }
    .ts-pdp > .a-gallery { grid-area: gallery; min-width: 0; }
    .ts-pdp > .a-buy { grid-area: buy; position: sticky; top: 96px; min-width: 0; }
    .ts-pdp > .a-info { grid-area: info; min-width: 0; }
    @media (max-width: 980px) {
        .ts-pdp { grid-template-columns: minmax(0, 1fr); grid-template-areas: "gallery" "buy" "info"; }
        .ts-pdp > .a-buy { position: static; }
    }

    /* ป้ายแจ้งมีอัปเดต (หน้าออเดอร์) */
    .ts-refresh { position: sticky; top: 10px; z-index: 30; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 12px 16px; border-radius: 16px;
        color: var(--ts-on); background: linear-gradient(135deg, var(--accent2), var(--deep2)); box-shadow: 0 10px 24px color-mix(in srgb, var(--deep2) 35%, transparent); animation: tpPop .25s ease both; }

    /* แถวรายการ */
    .ts-line { display: flex; gap: 12px; align-items: flex-start; padding: 12px 0; border-bottom: 1px solid color-mix(in srgb, var(--ink2) 16%, transparent); }
    .ts-line:last-child { border-bottom: 0; }
    .ts-kv { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; font-size: 13.5px; color: var(--ink2); padding: 4px 0; }
    .ts-kv b, .ts-kv strong { color: var(--ink); }
</style>
@endpush

@push('scripts')
<script>
(function () {
    if (window.ts) { return; }

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function notify(message, type) {
        window.dispatchEvent(new CustomEvent('notify', { detail: { message: message, type: type || 'info' } }));
    }

    function hasThai(text) {
        return typeof text === 'string' && /[฀-๿]/.test(text);
    }

    function statusText(status) {
        if (status === 0) { return 'เชื่อมต่อไม่ได้ กรุณาตรวจสอบอินเทอร์เน็ตแล้วลองใหม่'; }
        if (status === 401) { return 'กรุณาเข้าสู่ระบบก่อนทำรายการ'; }
        if (status === 403) { return 'ไม่มีสิทธิ์ทำรายการนี้'; }
        if (status === 404) { return 'ไม่พบข้อมูลนี้แล้ว'; }
        if (status === 413) { return 'ไฟล์มีขนาดใหญ่เกินไป'; }
        if (status === 419) { return 'หน้านี้เปิดค้างไว้นานเกินไป กรุณารีเฟรชหน้าแล้วลองใหม่'; }
        if (status === 422) { return 'ข้อมูลไม่ถูกต้อง กรุณาตรวจสอบแล้วลองใหม่'; }
        if (status === 429) { return 'ทำรายการถี่เกินไป กรุณารอสักครู่แล้วลองใหม่'; }
        return 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง';
    }

    /**
     * เรียก endpoint แบบ JSON (session + CSRF) → {ok, status, code, message, data, body}
     * ข้อความที่แสดงผู้ใช้เป็นภาษาไทยเสมอ (ข้อความจากเซิร์ฟเวอร์ใช้เฉพาะที่เป็นภาษาไทย)
     */
    async function req(method, url, data) {
        method = (method || 'GET').toUpperCase();
        var isForm = (typeof FormData !== 'undefined') && (data instanceof FormData);
        var headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' };
        var opts = { method: method, headers: headers, credentials: 'same-origin' };

        if (method === 'GET') {
            if (data && typeof data === 'object') {
                var qs = new URLSearchParams();
                Object.keys(data).forEach(function (k) {
                    if (data[k] !== null && data[k] !== undefined && data[k] !== '') { qs.append(k, data[k]); }
                });
                var q = qs.toString();
                if (q) { url += (url.indexOf('?') === -1 ? '?' : '&') + q; }
            }
        } else if (isForm) {
            opts.body = data;
        } else {
            headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(data || {});
        }

        var res;
        try {
            res = await fetch(url, opts);
        } catch (e) {
            return { ok: false, status: 0, code: 'NETWORK', message: statusText(0), data: null, body: null };
        }

        var body = null;
        try { body = await res.json(); } catch (e) { body = null; }

        var ok = res.ok && !!body && body.success !== false;
        var message = '';

        if (body && hasThai(body.message)) {
            message = body.message;
        }
        if (!ok && body && body.errors && typeof body.errors === 'object') {
            var keys = Object.keys(body.errors);
            for (var i = 0; i < keys.length; i++) {
                var first = Array.isArray(body.errors[keys[i]]) ? body.errors[keys[i]][0] : body.errors[keys[i]];
                if (hasThai(first)) { message = first; break; }
            }
        }
        if (!ok && (!message || res.status >= 500)) {
            message = statusText(res.status);
        }

        return { ok: ok, status: res.status, code: (body && body.code) || null, message: message, data: body ? (body.data !== undefined ? body.data : null) : null, body: body };
    }

    /**
     * ขอพิกัดจากเบราว์เซอร์ → Promise<{lat, lng, accuracy}> (ไม่ได้ = reject {code, message} ภาษาไทย)
     */
    function geo(options) {
        return new Promise(function (resolve, reject) {
            if (!('geolocation' in navigator)) {
                reject({ code: 'UNSUPPORTED', message: 'เบราว์เซอร์นี้ไม่รองรับการระบุตำแหน่ง' });
                return;
            }
            navigator.geolocation.getCurrentPosition(function (pos) {
                resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude, accuracy: pos.coords.accuracy });
            }, function (err) {
                var map = {
                    1: { code: 'DENIED', message: 'ยังไม่ได้อนุญาตให้เข้าถึงตำแหน่ง กรุณากดอนุญาตในเบราว์เซอร์แล้วลองใหม่' },
                    2: { code: 'UNAVAILABLE', message: 'หาตำแหน่งไม่ได้ กรุณาเปิด GPS แล้วลองใหม่' },
                    3: { code: 'TIMEOUT', message: 'หาตำแหน่งนานเกินไป กรุณาลองใหม่อีกครั้ง' }
                };
                reject(map[err && err.code] || map[2]);
            }, Object.assign({ enableHighAccuracy: true, timeout: 15000, maximumAge: 20000 }, options || {}));
        });
    }

    /** เคยอนุญาตตำแหน่งแล้วหรือยัง (true/false/null = ไม่รู้) */
    async function geoGranted() {
        try {
            if (!navigator.permissions || !navigator.permissions.query) { return null; }
            var st = await navigator.permissions.query({ name: 'geolocation' });
            return st.state === 'granted';
        } catch (e) {
            return null;
        }
    }

    function money(n) {
        var v = Math.round((Number(n) || 0) * 100) / 100;
        var dec = Math.abs(v - Math.round(v)) >= 0.005 ? 2 : 0;
        return v.toLocaleString('th-TH', { minimumFractionDigits: dec, maximumFractionDigits: dec });
    }

    function distance(km) {
        if (km === null || km === undefined || km === '') { return '—'; }
        var v = Number(km) || 0;
        return v < 1 ? Math.max(0, Math.round(v * 1000)).toLocaleString('th-TH') + ' ม.' : v.toFixed(1) + ' กม.';
    }

    function timeAgo(iso) {
        if (!iso) { return '—'; }
        var t = new Date(iso).getTime();
        if (isNaN(t)) { return '—'; }
        var s = Math.max(0, Math.round((Date.now() - t) / 1000));
        if (s < 45) { return 'เมื่อสักครู่'; }
        if (s < 3600) { return Math.round(s / 60) + ' นาทีที่แล้ว'; }
        if (s < 86400) { return Math.round(s / 3600) + ' ชั่วโมงที่แล้ว'; }
        return Math.round(s / 86400) + ' วันที่แล้ว';
    }

    function hhmm(iso) {
        if (!iso) { return ''; }
        var d = new Date(iso);
        if (isNaN(d.getTime())) { return ''; }
        return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
    }

    window.ts = {
        csrf: csrf, notify: notify, req: req, geo: geo, geoGranted: geoGranted,
        get: function (u, d) { return req('GET', u, d); },
        post: function (u, d) { return req('POST', u, d); },
        put: function (u, d) { return req('PUT', u, d); },
        del: function (u, d) { return req('DELETE', u, d); },
        money: money, distance: distance, timeAgo: timeAgo, hhmm: hhmm
    };

    /**
     * แผนที่ปักหมุด (ลงทะเบียนร้าน / ตั้งค่าร้าน / ที่อยู่จัดส่ง)
     * cfg: { id, lat, lng, defaultLat, defaultLng, kind: 'shop'|'home' }
     * ส่งอีเวนต์ ts-pin {id, lat, lng} ทุกครั้งที่หมุดเปลี่ยน · ฟังอีเวนต์ ts-map-refresh เพื่อคำนวณขนาดแผนที่ใหม่ (เช่นเพิ่งแสดงผล)
     */
    window.tsPinMap = function (cfg) {
        cfg = cfg || {};
        var toNum = function (v) { var n = parseFloat(v); return isFinite(n) ? n : null; };
        // วัตถุ Leaflet เก็บนอก state ของ Alpine (ห้ามให้ถูกห่อเป็น reactive proxy)
        var map = null;
        var marker = null;

        return {
            lat: toNum(cfg.lat),
            lng: toNum(cfg.lng),
            locating: false,
            error: '',

            init: function () {
                var self = this;
                this.$nextTick(function () { self.boot(); });
                window.addEventListener('ts-map-refresh', function () { self.refresh(); });
                // ตั้งหมุดจากภายนอก (เช่นเลือกที่อยู่ที่บันทึกไว้): ts-pin-set {id?, lat, lng}
                window.addEventListener('ts-pin-set', function (e) {
                    var d = e.detail || {};
                    if (cfg.id && d.id && d.id !== cfg.id) { return; }
                    if (d.lat === null || d.lat === undefined || d.lng === null || d.lng === undefined) { return; }
                    self.setPoint(d.lat, d.lng, 17);
                });
            },

            boot: function () {
                if (!window.tpMap || !window.tpMap.ready()) {
                    this.error = 'โหลดแผนที่ไม่สำเร็จ — กดปุ่ม "ใช้ตำแหน่งปัจจุบัน" แทนได้';
                    return;
                }
                var has = this.lat !== null && this.lng !== null;
                var c = has ? [this.lat, this.lng] : [toNum(cfg.defaultLat) || 13.7563, toNum(cfg.defaultLng) || 100.5018];
                map = window.tpMap.create(this.$refs.map, c[0], c[1], has ? 16 : 11);
                if (!map) { return; }
                if (has) { this.placeMarker(this.lat, this.lng); }
                var self = this;
                map.on('click', function (e) { self.setPoint(e.latlng.lat, e.latlng.lng); });
                setTimeout(function () { self.refresh(); }, 300);
            },

            refresh: function () {
                if (map) { map.invalidateSize(); }
            },

            placeMarker: function (lat, lng) {
                if (!map) { return; }
                var self = this;
                if (!marker) {
                    marker = window.tpMap.pin(map, lat, lng, cfg.kind || 'home', true);
                    marker.on('dragend', function () {
                        var p = marker.getLatLng();
                        self.setPoint(p.lat, p.lng);
                    });
                } else {
                    marker.setLatLng([lat, lng]);
                }
            },

            setPoint: function (lat, lng, zoom) {
                this.lat = Math.round(Number(lat) * 1e7) / 1e7;
                this.lng = Math.round(Number(lng) * 1e7) / 1e7;
                this.error = '';
                this.placeMarker(this.lat, this.lng);
                if (map && zoom) { map.setView([this.lat, this.lng], zoom); }
                this.$dispatch('ts-pin', { id: cfg.id || null, lat: this.lat, lng: this.lng });
            },

            locate: function () {
                var self = this;
                this.locating = true;
                this.error = '';
                window.ts.geo().then(function (p) {
                    self.setPoint(p.lat, p.lng, 17);
                    if (p.accuracy && p.accuracy > 150) {
                        self.error = 'ตำแหน่งคลาดเคลื่อนประมาณ ' + Math.round(p.accuracy) + ' ม. — ลากหมุดให้ตรงจุดได้';
                    }
                }).catch(function (e) {
                    self.error = e.message;
                }).finally(function () {
                    self.locating = false;
                });
            }
        };
    };
})();
</script>
@endpush
@endonce
