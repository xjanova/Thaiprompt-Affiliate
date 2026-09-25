{{--
    สไตล์ร่วมของหน้าเว็บไรเดอร์ (Theme V4 นวลทองคำ)
    ใช้คู่กับ layouts.user-v4 — ใส่ใน stack styles ของแต่ละหน้า
    สีทั้งหมดอ้างตัวแปรธีม (var(--accent1) ฯลฯ) — สีสถานะมีค่าสำรองใน var() เท่านั้น
--}}
<style>
    .rd-scope {
        --rd-ok: var(--tp-ok, #5aa07e);
        --rd-warn: var(--tp-warn, #e0a52e);
        --rd-bad: var(--tp-bad, #d9534f);
        --rd-info: var(--tp-info, #5689b8);
        --rd-on: var(--tp-on-accent, #fff);
        --tone: var(--accent1);
        display: flex; flex-direction: column; gap: 18px;
    }
    .rd-scope a { color: inherit; }
    .rd-tone-ok { --tone: var(--rd-ok); }
    .rd-tone-warn { --tone: var(--rd-warn); }
    .rd-tone-bad { --tone: var(--rd-bad); }
    .rd-tone-info { --tone: var(--rd-info); }
    .rd-tone-muted { --tone: var(--ink2); }
    .rd-tone-gold { --tone: var(--accent1); }

    .rd-grid { display: grid; gap: 14px; grid-template-columns: repeat(auto-fit, minmax(var(--rd-min, 220px), 1fr)); }
    .rd-row { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
    .rd-stack { display: flex; flex-direction: column; gap: 12px; }
    .rd-muted { color: var(--ink2); }
    .rd-small { font-size: 12px; }
    .rd-h1 { font-size: clamp(20px, 4.4vw, 27px); font-weight: 800; margin: 0; line-height: 1.25; color: var(--ink); }
    .rd-h2 { font-size: 16px; font-weight: 800; margin: 0; color: var(--ink); display: flex; align-items: center; gap: 8px; }
    .rd-num { font-family: var(--tp-font-num); font-weight: 800; letter-spacing: -.3px; }
    .rd-money { font-family: var(--tp-font-num); font-weight: 800; color: var(--deep1); }
    .rd-link { color: var(--deep1) !important; font-weight: 700; text-decoration: none; }
    .rd-link:hover { text-decoration: underline; }

    /* ป้ายสถานะตามโทน */
    .rd-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 700; line-height: 1; padding: 6px 11px; border-radius: 999px;
        color: var(--tone); background: color-mix(in srgb, var(--tone) 15%, transparent); white-space: nowrap; }
    .rd-pill.solid { color: var(--rd-on); background: var(--tone); box-shadow: 0 3px 10px color-mix(in srgb, var(--tone) 35%, transparent); }
    .rd-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--tone); box-shadow: 0 0 0 4px color-mix(in srgb, var(--tone) 22%, transparent); flex: none; }
    .rd-dot.live { animation: tpPulse 1.6s ease-in-out infinite; }

    /* การ์ดหัวหน้า (hero) — ไล่ทองนุ่ม + เงาลึก */
    .rd-hero { position: relative; overflow: hidden; padding: 0 !important; }
    .rd-hero-in { position: relative; z-index: 1; display: flex; flex-wrap: wrap; align-items: center; gap: 16px; padding: 22px clamp(16px, 3vw, 28px);
        background: radial-gradient(120% 140% at 100% 0%, color-mix(in srgb, var(--accent2) 22%, transparent) 0%, transparent 55%),
                    linear-gradient(120deg, color-mix(in srgb, var(--accent1) 20%, transparent), transparent 72%); }
    .rd-hero-img { position: absolute; inset: 0; background-size: cover; background-position: center right; opacity: .22; pointer-events: none; }
    .rd-hero-img::after { content: ""; position: absolute; inset: 0; background: linear-gradient(90deg, var(--surf) 18%, color-mix(in srgb, var(--surf) 55%, transparent) 60%, transparent); }
    .rd-avatar { width: 60px; height: 60px; border-radius: 20px; object-fit: cover; flex: none; box-shadow: var(--raise); background: var(--surf); }

    /* ปุ่ม 3 มิติ (แบบเดียวกับแอป) — ยกตัว มีขอบสว่าง กดแล้วยุบ */
    .rd-btn3d { position: relative; display: inline-flex; align-items: center; justify-content: center; gap: 9px; min-height: 48px; padding: 0 20px;
        border: 0; border-radius: 16px; cursor: pointer; font-family: inherit; font-weight: 800; font-size: 14.5px; line-height: 1.2; color: var(--rd-on) !important;
        text-decoration: none; text-align: center; text-shadow: 0 1px 2px rgba(0,0,0,.2);
        background: linear-gradient(180deg, color-mix(in srgb, var(--tone) 72%, var(--rd-on)) 0%, var(--tone) 48%, color-mix(in srgb, var(--tone) 82%, var(--ink)) 100%);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.5), inset 0 -3px 0 rgba(0,0,0,.14), 0 5px 0 color-mix(in srgb, var(--tone) 60%, var(--ink)), 0 12px 22px color-mix(in srgb, var(--tone) 32%, transparent);
        transform: translateY(-2px); transition: transform .12s ease, box-shadow .12s ease, filter .15s ease; -webkit-tap-highlight-color: transparent; }
    .rd-btn3d:hover { filter: brightness(1.04); }
    .rd-btn3d:active { transform: translateY(3px); box-shadow: inset 0 1px 0 rgba(255,255,255,.35), inset 0 -1px 0 rgba(0,0,0,.12), 0 1px 0 color-mix(in srgb, var(--tone) 60%, var(--ink)), 0 4px 10px color-mix(in srgb, var(--tone) 25%, transparent); }
    .rd-btn3d[disabled], .rd-btn3d.is-disabled { filter: grayscale(.55) opacity(.6); cursor: not-allowed; transform: none; }
    .rd-btn3d.block { width: 100%; }
    .rd-btn3d.lg { min-height: 56px; font-size: 16px; border-radius: 18px; }
    .rd-btn3d.sm { min-height: 44px; padding: 0 14px; font-size: 13px; border-radius: 13px; }

    /* ปุ่มรองแบบดินเหนียว (ใช้ .tp-btn ของธีม + ความสูงแตะนิ้ว 44px) */
    .rd-scope .tp-btn { min-height: 44px; text-decoration: none; }
    .rd-btn-ghost { background: transparent !important; box-shadow: inset 0 0 0 1.5px color-mix(in srgb, var(--tone) 55%, transparent) !important; color: var(--tone) !important; }

    /* สวิตช์ออนไลน์ — ปุ่มใหญ่ กดง่ายบนมือถือ */
    .rd-switch { display: inline-flex; align-items: center; gap: 12px; min-height: 56px; padding: 6px 18px 6px 6px; border: 0; border-radius: 999px; cursor: pointer;
        font-family: inherit; font-weight: 800; font-size: 14.5px; color: var(--ink); background: var(--surf); box-shadow: var(--inset); transition: box-shadow .2s ease; }
    .rd-switch .knob { width: 44px; height: 44px; border-radius: 50%; display: grid; place-items: center; color: var(--rd-on); font-size: 17px;
        background: linear-gradient(180deg, color-mix(in srgb, var(--tone) 70%, var(--rd-on)), var(--tone)); box-shadow: 0 4px 0 color-mix(in srgb, var(--tone) 60%, var(--ink)), 0 8px 16px color-mix(in srgb, var(--tone) 35%, transparent);
        transition: transform .25s cubic-bezier(.2,.8,.3,1.2); }
    .rd-switch:active .knob { transform: scale(.94) translateY(2px); }
    .rd-switch[disabled] { cursor: not-allowed; opacity: .7; }

    /* การ์ดตัวเลขสรุป */
    .rd-stat { display: flex; flex-direction: column; gap: 6px; min-height: 112px; position: relative; overflow: hidden; }
    .rd-stat .ic { width: 38px; height: 38px; border-radius: 12px; display: grid; place-items: center; font-size: 15px; color: var(--rd-on);
        background: linear-gradient(135deg, color-mix(in srgb, var(--tone) 80%, var(--rd-on)), var(--tone)); box-shadow: var(--raise); }
    .rd-stat .lbl { font-size: 12px; color: var(--ink2); font-weight: 600; }
    .rd-stat .val { font-family: var(--tp-font-num); font-weight: 800; font-size: clamp(22px, 5vw, 28px); color: var(--ink); line-height: 1.1; }
    .rd-stat .sub { font-size: 11.5px; color: var(--ink2); }
    .rd-stat.featured { background: linear-gradient(145deg, color-mix(in srgb, var(--accent1) 26%, var(--card-bg)), var(--card-bg) 70%); }
    .rd-stat.featured .val { color: var(--deep1); }

    /* แท็บเมนูไรเดอร์ (เลื่อนแนวนอนบนมือถือ) */
    .rd-nav { display: flex; gap: 8px; overflow-x: auto; padding: 4px 2px 8px; scrollbar-width: none; -webkit-overflow-scrolling: touch; }
    .rd-nav::-webkit-scrollbar { display: none; }
    .rd-nav a { flex: none; display: inline-flex; align-items: center; gap: 8px; min-height: 44px; padding: 0 16px; border-radius: 14px; font-size: 13px; font-weight: 700;
        color: var(--ink2); text-decoration: none; background: var(--surf); box-shadow: var(--raise); transition: color .15s ease, box-shadow .15s ease; }
    .rd-nav a:hover { color: var(--ink); }
    .rd-nav a.on { color: var(--rd-on); background: linear-gradient(135deg, var(--accent1), var(--accent2)); text-shadow: 0 1px 2px rgba(0,0,0,.15); }

    /* ฟอร์ม */
    .rd-field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
    .rd-field > label, .rd-label { font-size: 12.5px; font-weight: 700; color: var(--ink); }
    .rd-field .req { color: var(--rd-bad); }
    .rd-hint { font-size: 11.5px; color: var(--ink2); line-height: 1.5; }
    .rd-err { font-size: 12px; color: var(--rd-bad); font-weight: 600; }
    .rd-scope .tp-input { min-height: 46px; }
    .rd-scope textarea.tp-input { min-height: 90px; resize: vertical; line-height: 1.55; }
    .rd-scope .tp-input.is-bad { box-shadow: var(--inset), 0 0 0 2px color-mix(in srgb, var(--rd-bad) 55%, transparent); }
    .rd-scope .tp-input.is-ok { box-shadow: var(--inset), 0 0 0 2px color-mix(in srgb, var(--rd-ok) 45%, transparent); }

    /* ตัวเลือกแบบการ์ด (radio / checkbox) */
    .rd-choice { position: relative; display: block; cursor: pointer; }
    .rd-choice input { position: absolute; opacity: 0; width: 1px; height: 1px; }
    .rd-choice .box { display: flex; align-items: center; gap: 12px; min-height: 60px; padding: 12px 14px; border-radius: 16px; background: var(--surf); box-shadow: var(--raise);
        border: 2px solid transparent; transition: border-color .15s ease, box-shadow .15s ease; }
    .rd-choice .box i { width: 38px; height: 38px; border-radius: 12px; display: grid; place-items: center; flex: none; font-size: 16px; color: var(--deep1);
        background: color-mix(in srgb, var(--accent1) 16%, transparent); }
    .rd-choice input:checked + .box { border-color: var(--accent1); box-shadow: var(--inset-sm); }
    .rd-choice input:checked + .box i { color: var(--rd-on); background: linear-gradient(135deg, var(--accent1), var(--accent2)); }
    .rd-choice input:focus-visible + .box { outline: 2px solid var(--accent1); outline-offset: 2px; }
    .rd-chip { display: inline-flex; align-items: center; gap: 6px; min-height: 40px; padding: 0 14px; border-radius: 999px; font-size: 12.5px; font-weight: 700;
        background: var(--surf); box-shadow: var(--raise); color: var(--ink2); text-decoration: none; cursor: pointer; border: 0; font-family: inherit; }
    .rd-chip.on, .rd-choice input:checked + .rd-chip { color: var(--rd-on); background: linear-gradient(135deg, var(--accent1), var(--accent2)); }

    /* งาน: เส้นทางรับ → ส่ง */
    .rd-route { position: relative; display: flex; flex-direction: column; gap: 12px; padding-left: 26px; }
    .rd-route::before { content: ""; position: absolute; left: 9px; top: 14px; bottom: 14px; border-left: 2px dashed color-mix(in srgb, var(--ink2) 45%, transparent); }
    .rd-route .pt { position: relative; min-width: 0; }
    .rd-route .pt::before { content: ""; position: absolute; left: -23px; top: 3px; width: 14px; height: 14px; border-radius: 50%; background: var(--tone); box-shadow: 0 0 0 4px color-mix(in srgb, var(--tone) 22%, transparent); }
    .rd-route .pt .t { font-size: 11px; color: var(--ink2); font-weight: 700; letter-spacing: .2px; }
    .rd-route .pt .v { font-size: 13.5px; color: var(--ink); font-weight: 600; overflow-wrap: anywhere; }

    .rd-job { display: flex; flex-direction: column; gap: 14px; }
    .rd-job .earn { text-align: right; }
    .rd-job .earn .amt { font-family: var(--tp-font-num); font-weight: 800; font-size: 26px; color: var(--deep1); line-height: 1; }
    .rd-job .meta { display: flex; flex-wrap: wrap; gap: 8px; }
    .rd-meta { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: var(--ink2); padding: 6px 10px; border-radius: 10px; box-shadow: var(--inset-sm); }

    /* ขั้นตอนงาน */
    .rd-steps { display: flex; gap: 4px; }
    .rd-steps .st { flex: 1; min-width: 0; text-align: center; }
    .rd-steps .bar { height: 7px; border-radius: 6px; background: color-mix(in srgb, var(--ink2) 18%, transparent); }
    .rd-steps .st.done .bar { background: linear-gradient(90deg, var(--accent1), var(--accent2)); }
    .rd-steps .st.current .bar { background: linear-gradient(90deg, var(--accent1), color-mix(in srgb, var(--accent1) 35%, transparent)); animation: tpPulse 1.8s ease-in-out infinite; }
    .rd-steps .st.stopped .bar { background: var(--rd-bad); }
    .rd-steps .lb { font-size: 10.5px; font-weight: 700; color: var(--ink2); margin-top: 6px; line-height: 1.3; }
    .rd-steps .st.done .lb, .rd-steps .st.current .lb { color: var(--ink); }

    /* แถบแจ้งเตือนในหน้า */
    .rd-alert { display: flex; gap: 12px; align-items: flex-start; padding: 14px 16px; border-radius: 16px; font-size: 13px; line-height: 1.55; color: var(--ink);
        background: color-mix(in srgb, var(--tone) 12%, var(--card-bg)); box-shadow: inset 4px 0 0 var(--tone), var(--card-shadow-sm); }
    .rd-alert > i { color: var(--tone); font-size: 17px; margin-top: 1px; flex: none; }
    .rd-alert b { color: var(--ink); }

    /* สถานะว่าง */
    .rd-empty { text-align: center; padding: 30px 18px; }
    .rd-empty .ic { width: 76px; height: 76px; margin: 0 auto 12px; border-radius: 26px; display: grid; place-items: center; font-size: 30px; color: var(--deep1); box-shadow: var(--inset); }

    /* วงแหวนความคืบหน้า (conic-gradient) */
    .rd-ring { --p: 0; width: 92px; height: 92px; border-radius: 50%; flex: none; display: grid; place-items: center;
        background: conic-gradient(var(--tone) calc(var(--p) * 1%), color-mix(in srgb, var(--ink2) 16%, transparent) 0); box-shadow: var(--raise); }
    .rd-ring > span { width: 70px; height: 70px; border-radius: 50%; display: grid; place-items: center; background: var(--card-bg); font-family: var(--tp-font-num); font-weight: 800; font-size: 18px; color: var(--ink); }

    /* กราฟแท่งรายได้ (ต่อยอด .tp-bars) */
    .rd-bars { height: 160px; gap: 6px; }
    .rd-bars .bar { width: 70%; max-width: 30px; }
    .rd-bars .col.today .lbl { color: var(--deep1); font-weight: 800; }
    .rd-bars .amt { font-size: 10px; color: var(--ink2); font-family: var(--tp-font-num); font-weight: 700; white-space: nowrap; min-height: 12px; }
    .rd-scroll-x { overflow-x: auto; padding-bottom: 4px; }

    /* รูปพรีวิว */
    .rd-thumb { width: 100%; aspect-ratio: 16 / 10; border-radius: 14px; object-fit: cover; background: var(--surf); box-shadow: var(--inset-sm); display: block; }
    .rd-thumb-ph { width: 100%; aspect-ratio: 16 / 10; border-radius: 14px; display: grid; place-items: center; box-shadow: var(--inset); color: var(--ink2); font-size: 30px; }
    .rd-progress { height: 8px; border-radius: 6px; overflow: hidden; background: color-mix(in srgb, var(--ink2) 16%, transparent); }
    .rd-progress > i { display: block; height: 100%; border-radius: 6px; background: linear-gradient(90deg, var(--accent1), var(--accent2)); transition: width .2s ease; }

    /* ฉลองสำเร็จ */
    .rd-celebrate { position: relative; overflow: hidden; text-align: center; padding: 26px 18px !important;
        background: radial-gradient(90% 120% at 50% 0%, color-mix(in srgb, var(--rd-ok) 28%, transparent), transparent 70%), var(--card-bg) !important; }
    .rd-celebrate .big { font-family: var(--tp-font-num); font-size: clamp(34px, 9vw, 48px); font-weight: 800; color: var(--rd-ok); line-height: 1; animation: rdPop .7s cubic-bezier(.2,.9,.3,1.3) both; }
    .rd-celebrate .burst { position: absolute; inset: 0; pointer-events: none; background:
        radial-gradient(circle at 12% 30%, color-mix(in srgb, var(--accent1) 70%, transparent) 0 4px, transparent 5px),
        radial-gradient(circle at 85% 22%, color-mix(in srgb, var(--rd-ok) 70%, transparent) 0 5px, transparent 6px),
        radial-gradient(circle at 72% 78%, color-mix(in srgb, var(--accent2) 70%, transparent) 0 4px, transparent 5px),
        radial-gradient(circle at 25% 82%, color-mix(in srgb, var(--rd-info) 60%, transparent) 0 3px, transparent 4px);
        animation: rdFloat 3.2s ease-in-out infinite alternate; }
    @keyframes rdPop { 0% { transform: scale(.6); opacity: 0; } 70% { transform: scale(1.08); opacity: 1; } 100% { transform: scale(1); } }
    @keyframes rdFloat { from { transform: translateY(0); } to { transform: translateY(-8px); } }
    @keyframes rdSpin { to { transform: rotate(360deg); } }
    .rd-spin { display: inline-block; animation: rdSpin .8s linear infinite; }

    /* รายการ (ประวัติงาน/รายได้) */
    .rd-list { display: flex; flex-direction: column; }
    .rd-item { display: flex; align-items: center; gap: 12px; padding: 12px 4px; text-decoration: none; min-height: 56px; }
    .rd-item + .rd-item { border-top: 1px solid color-mix(in srgb, var(--ink2) 16%, transparent); }
    .rd-item .ic { width: 42px; height: 42px; border-radius: 13px; display: grid; place-items: center; flex: none; font-size: 16px; color: var(--tone); background: color-mix(in srgb, var(--tone) 15%, transparent); }
    .rd-item .main { flex: 1; min-width: 0; }
    .rd-item .ttl { font-size: 13.5px; font-weight: 700; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .rd-item .sub { font-size: 11.5px; color: var(--ink2); margin-top: 2px; }
    .rd-item .end { text-align: right; flex: none; }

    .rd-sticky-actions { position: sticky; bottom: 10px; z-index: 5; }
    .rd-details > summary { cursor: pointer; list-style: none; min-height: 44px; display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 13.5px; color: var(--ink); }
    .rd-details > summary::-webkit-details-marker { display: none; }
    .rd-details[open] > summary .chev { transform: rotate(180deg); }
    .rd-details .chev { transition: transform .2s ease; margin-left: auto; color: var(--ink2); }

    @media (max-width: 560px) {
        .rd-hide-sm { display: none !important; }
        .rd-job .earn .amt { font-size: 22px; }
        .rd-full-sm { width: 100%; }
    }
    @media (prefers-reduced-motion: reduce) {
        .rd-dot.live, .rd-steps .st.current .bar, .rd-celebrate .big, .rd-celebrate .burst, .rd-spin { animation: none !important; }
    }
</style>
