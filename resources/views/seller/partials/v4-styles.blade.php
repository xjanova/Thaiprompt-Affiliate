{{--
 | สไตล์เสริมของหน้าผู้ขายธีม V4 (ใช้ตัวแปร CSS ของ theme-v4 ทั้งหมด — ไม่มีสีตายตัว)
 | ใช้งาน: ใส่ใน stack styles ของหน้า แล้วครอบเนื้อหาด้วย class sv4-page
 | ใส่หลายครั้งในหน้าเดียวได้ (มี once กันซ้ำ)
 --}}
@once
<style>
    .sv4-page { display:flex; flex-direction:column; gap:18px; min-width:0; }
    .sv4-page .tp-input:focus, .sv4-page .tp-input:focus-within { box-shadow: var(--inset), 0 0 0 2px color-mix(in srgb, var(--accent1) 45%, transparent); }
    .sv4-page select.tp-input { cursor:pointer; }
    .sv4-page textarea.tp-input { resize:vertical; line-height:1.6; }
    .sv4-label { display:block; font-size:12.5px; font-weight:700; color:var(--ink); margin-bottom:6px; }
    .sv4-label .req { color:var(--tp-bad, #d9534f); }
    .sv4-hint { font-size:11.5px; color:var(--ink2); margin-top:5px; line-height:1.55; }
    .sv4-err { font-size:12px; color:var(--tp-bad, #d9534f); margin-top:5px; font-weight:600; }
    .sv4-grid { display:grid; gap:14px; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); }
    .sv4-grid-2 { display:grid; gap:14px; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); }
    .sv4-stats { display:grid; gap:14px; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); }
    .sv4-h2 { font-size:15px; font-weight:800; color:var(--ink); margin:0; display:flex; align-items:center; gap:8px; }
    .sv4-sub { font-size:12px; color:var(--ink2); margin-top:3px; }
    .sv4-divider { height:1px; border:0; margin:0; background:color-mix(in srgb, var(--ink2) 16%, transparent); }
    .sv4-row { display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .sv4-kv { display:flex; justify-content:space-between; gap:12px; font-size:13px; padding:6px 0; }
    .sv4-kv > span:first-child { color:var(--ink2); }
    .sv4-kv > span:last-child { font-weight:700; text-align:right; overflow-wrap:anywhere; }
    .sv4-link { color:var(--deep1); font-weight:700; text-decoration:none; }
    .sv4-link:hover { text-decoration:underline; }
    .sv4-pill { display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:700; line-height:1; padding:5px 10px; border-radius:20px; white-space:nowrap; }
    .sv4-well { padding:14px 16px; border-radius:16px; box-shadow:var(--inset-sm); }
    .sv4-note { padding:13px 15px; border-radius:15px; font-size:12.5px; line-height:1.6; color:var(--ink);
        background:color-mix(in srgb, var(--c, var(--accent1)) 11%, transparent);
        border:1px solid color-mix(in srgb, var(--c, var(--accent1)) 30%, transparent); }
    .sv4-btn-danger { color:var(--tp-on-accent, #fff); background:var(--tp-bad, #d9534f); }
    .sv4-btn-ok { color:var(--tp-on-accent, #fff); background:linear-gradient(135deg, var(--tp-ok, #5aa07e), color-mix(in srgb, var(--tp-ok, #5aa07e) 70%, var(--ink))); }
    .sv4-btn-block { width:100%; }
    .sv4-page .tp-btn[disabled], .sv4-page .tp-btn.is-disabled { opacity:.55; cursor:not-allowed; filter:grayscale(.3); }
    .sv4-page a.tp-btn { text-decoration:none; }

    /* ตาราง */
    .sv4-table-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; }
    .sv4-table { width:100%; border-collapse:collapse; font-size:13px; }
    .sv4-table thead tr { box-shadow:var(--inset-sm); }
    .sv4-table th { padding:11px 14px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:var(--ink2); text-align:left; white-space:nowrap; }
    .sv4-table td { padding:12px 14px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent); vertical-align:middle; }
    .sv4-table tbody tr { transition:background .15s ease; }
    .sv4-table tbody tr:hover { background:color-mix(in srgb, var(--accent1) 6%, transparent); }

    /* แท็บ */
    .sv4-tabs { display:flex; gap:6px; overflow-x:auto; padding:6px; border-radius:16px; box-shadow:var(--inset-sm); scrollbar-width:none; }
    .sv4-tabs::-webkit-scrollbar { display:none; }
    .sv4-tab { flex:none; display:inline-flex; align-items:center; gap:6px; padding:9px 14px; border-radius:12px; border:0; background:transparent; font-family:inherit; font-size:12.5px; font-weight:700; color:var(--ink2); text-decoration:none; white-space:nowrap; cursor:pointer; }
    .sv4-tab:hover { color:var(--ink); }
    .sv4-tab.on { color:var(--tp-on-accent, #fff); background:linear-gradient(135deg, var(--accent1), var(--accent2)); box-shadow:var(--raise); }
    .sv4-count { display:inline-grid; place-items:center; min-width:20px; height:20px; padding:0 6px; border-radius:99px; font-size:10.5px; font-weight:800; background:color-mix(in srgb, var(--ink2) 18%, transparent); }
    .sv4-tab.on .sv4-count { background:rgba(255,255,255,.28); }

    /* สวิตช์เปิด/ปิด (checkbox จริงอยู่ข้างใน — ส่งค่าในฟอร์มได้ตามปกติ) */
    .sv4-switch { position:relative; display:inline-flex; width:48px; height:28px; flex:none; }
    .sv4-switch input { position:absolute; inset:0; opacity:0; margin:0; cursor:pointer; z-index:2; }
    .sv4-switch span { position:absolute; inset:0; border-radius:99px; background:var(--surf); box-shadow:var(--inset-sm); transition:background .2s ease; }
    .sv4-switch span::after { content:''; position:absolute; top:4px; left:4px; width:20px; height:20px; border-radius:50%; background:var(--sl); box-shadow:var(--raise); transition:transform .2s ease; }
    .sv4-switch input:checked + span { background:linear-gradient(135deg, var(--accent1), var(--accent2)); }
    .sv4-switch input:checked + span::after { transform:translateX(20px); }
    .sv4-switch input:focus-visible + span { outline:2px solid var(--accent1); outline-offset:2px; }

    /* ส่วนพับได้ */
    .sv4-details > summary { list-style:none; cursor:pointer; display:flex; align-items:center; justify-content:space-between; gap:10px; }
    .sv4-details > summary::-webkit-details-marker { display:none; }
    .sv4-details > summary .sv4-chev { transition:transform .2s ease; color:var(--ink2); }
    .sv4-details[open] > summary .sv4-chev { transform:rotate(180deg); }

    /* ตัวเลือกแบบการ์ด (radio/checkbox) */
    .sv4-choice { display:flex; align-items:flex-start; gap:12px; padding:14px 16px; border-radius:16px; background:var(--card-bg); box-shadow:var(--card-shadow-sm); cursor:pointer; transition:box-shadow .15s ease; }
    .sv4-choice:hover { box-shadow:var(--card-shadow); }
    .sv4-choice.on { box-shadow:var(--inset-sm), 0 0 0 2px color-mix(in srgb, var(--accent1) 60%, transparent); }
    .sv4-choice.off { opacity:.55; cursor:not-allowed; }

    /* รูปสินค้าเล็ก */
    .sv4-thumb { width:48px; height:48px; flex:none; border-radius:13px; object-fit:cover; box-shadow:var(--inset-sm); display:grid; place-items:center; font-size:20px; background:var(--surf); }

    @media (max-width: 640px) {
        .sv4-hide-sm { display:none !important; }
        .sv4-stack-sm { flex-direction:column; align-items:stretch !important; }
        .sv4-table th, .sv4-table td { padding:10px 10px; }
    }
</style>
@endonce
