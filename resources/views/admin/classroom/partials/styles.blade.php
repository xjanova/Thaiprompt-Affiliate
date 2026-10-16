{{--
    สไตล์ร่วมหลังบ้านห้องเรียน (Theme V4) — ใส่ใน stack styles ของแต่ละหน้า
    สีพื้น/ตัวอักษรอ้างตัวแปรธีม (โหมดมืดเปลี่ยนตามเอง) · กรมท่า/ทอง = สีแบรนด์ของตราอบรม
--}}
<style>
    .ac-scope { --ac-navy:#042C53; --ac-gold:#EF9F27; --ac-ok:#2f855a; --ac-warn:#c05621; --ac-bad:#c53030;
        max-width:1180px; margin:0 auto; display:flex; flex-direction:column; gap:18px; }
    .ac-row { display:flex; flex-wrap:wrap; align-items:center; gap:10px; }
    .ac-between { justify-content:space-between; }
    .ac-grid { display:grid; gap:14px; grid-template-columns:repeat(auto-fit, minmax(min(100%, 300px), 1fr)); }
    .ac-kpis { display:grid; gap:14px; grid-template-columns:repeat(auto-fit, minmax(min(100%, 170px), 1fr)); }
    .ac-eyebrow { font-size:.72rem; letter-spacing:.08em; text-transform:uppercase; color:var(--ink2); margin-bottom:4px; }
    .ac-h1 { font-size:1.55rem; font-weight:800; color:var(--ink); margin:0; }
    .ac-h2 { font-size:1.05rem; font-weight:800; color:var(--ink); margin:0; display:flex; align-items:center; gap:8px; }
    .ac-kpi-v { font-family:var(--tp-font-num); font-size:1.45rem; font-weight:800; color:var(--ink); }
    .ac-label { display:block; font-size:.8rem; font-weight:700; color:var(--ink2); margin-bottom:6px; }
    .ac-help { font-size:.75rem; color:var(--ink2); margin-top:5px; line-height:1.5; }
    .ac-error { font-size:.78rem; color:var(--ac-bad); margin-top:5px; font-weight:600; }
    .dark .ac-error { color:#fc8181; }
    .ac-form-grid { display:grid; gap:14px; grid-template-columns:repeat(auto-fit, minmax(min(100%, 200px), 1fr)); }
    .ac-flash { border-left:4px solid var(--ac-ok); }
    .ac-flash-bad { border-left:4px solid var(--ac-bad); }
    .ac-pill-ok { color:#fff; background:var(--ac-ok); }
    .ac-pill-off { color:#fff; background:#718096; }
    .ac-pill-warn { color:#fff; background:var(--ac-warn); }
    .ac-pill-navy { color:#FAC775; background:var(--ac-navy); }
    .ac-table { width:100%; border-collapse:collapse; font-size:.86rem; }
    .ac-table th { text-align:left; font-size:.72rem; letter-spacing:.04em; color:var(--ink2); font-weight:700; padding:8px 10px;
        border-bottom:1px solid color-mix(in srgb, var(--ink2) 22%, transparent); white-space:nowrap; }
    .ac-table td { padding:10px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 12%, transparent); color:var(--ink); vertical-align:top; }
    .ac-scroll { overflow-x:auto; }
    .ac-lesson { display:grid; grid-template-columns:auto 112px 1fr auto; gap:12px; align-items:center; padding:12px; border-radius:16px; box-shadow:var(--inset-sm); }
    .ac-thumb { width:112px; aspect-ratio:16/9; border-radius:10px; object-fit:cover; background:var(--surf); }
    .ac-order { display:flex; flex-direction:column; gap:4px; }
    .ac-danger { color:var(--ac-bad) !important; }
    .dark .ac-danger { color:#fc8181 !important; }
    .ac-bar { height:8px; border-radius:999px; box-shadow:var(--inset-sm); overflow:hidden; }
    .ac-bar > span { display:block; height:100%; background:linear-gradient(90deg, var(--accent1), var(--accent2)); }
    .ac-choice-row { display:grid; grid-template-columns:auto 1fr; gap:10px; align-items:center; }
    .ac-choice-row input[type=radio] { width:18px; height:18px; accent-color:var(--ac-navy); }
    .dark .ac-choice-row input[type=radio] { accent-color:var(--ac-gold); }
    @media (max-width: 720px) {
        .ac-lesson { grid-template-columns:auto 1fr; }
        .ac-lesson .ac-thumb { display:none; }
        .ac-lesson > .ac-actions { grid-column:1 / -1; }
    }
    [x-cloak] { display:none !important; }
</style>
