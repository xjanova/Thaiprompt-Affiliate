{{--
    สไตล์ร่วมของห้องเรียน (Theme V4 นวลทองคำ) — ใช้คู่กับ layouts.user-v4 ใส่ใน stack styles ของแต่ละหน้า
    สีพื้น/ตัวอักษรอ้างตัวแปรธีมทั้งหมด (โหมดมืดเปลี่ยนตามเอง) · สีกรมท่า/ทองของตราอบรมเป็นสีแบรนด์คงที่
--}}
<style>
    .cr-scope {
        --cr-navy: #042C53; --cr-gold: #EF9F27; --cr-gold-soft: #FAC775;
        --cr-ok: var(--tp-ok, #5aa07e); --cr-warn: var(--tp-warn, #e0a52e); --cr-bad: var(--tp-bad, #d9534f);
        --tone: var(--accent1);
        display: flex; flex-direction: column; gap: 18px; max-width: 1080px; margin: 0 auto;
    }
    .cr-scope a { color: inherit; }
    .cr-tone-ok { --tone: var(--cr-ok); } .cr-tone-warn { --tone: var(--cr-warn); } .cr-tone-bad { --tone: var(--cr-bad); }
    .cr-tone-navy { --tone: var(--cr-navy); } .cr-tone-muted { --tone: var(--ink2); }
    .cr-row { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
    .cr-between { justify-content: space-between; }
    .cr-stack { display: flex; flex-direction: column; gap: 12px; }
    .cr-grid { display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(min(100%, 290px), 1fr)); }
    .cr-muted { color: var(--ink2); } .cr-small { font-size: 12.5px; }
    .cr-h1 { font-size: clamp(21px, 4.6vw, 28px); font-weight: 800; margin: 0; line-height: 1.25; color: var(--ink); }
    .cr-h2 { font-size: 16.5px; font-weight: 800; margin: 0; color: var(--ink); display: flex; align-items: center; gap: 8px; }
    .cr-num { font-family: var(--tp-font-num); font-weight: 800; }
    .cr-link { color: var(--deep1) !important; font-weight: 700; text-decoration: none; }
    .cr-link:hover { text-decoration: underline; }

    .cr-hero { position: relative; overflow: hidden; padding: 22px clamp(16px, 3vw, 28px) !important;
        background: radial-gradient(120% 150% at 100% 0%, color-mix(in srgb, var(--cr-navy) 20%, transparent) 0%, transparent 55%),
                    linear-gradient(120deg, color-mix(in srgb, var(--accent1) 20%, transparent), transparent 72%), var(--card-bg); }
    .cr-seal-lg { width: 64px; height: 64px; flex: none; filter: drop-shadow(0 4px 10px rgba(4,44,83,.35)); }

    .cr-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 700; line-height: 1; padding: 6px 11px; border-radius: 999px;
        color: var(--tone); background: color-mix(in srgb, var(--tone) 15%, transparent); white-space: nowrap; }
    .dark .cr-pill.cr-tone-navy { color: var(--cr-gold-soft); background: color-mix(in srgb, var(--cr-navy) 70%, transparent); }
    .cr-pill.solid { color: #fff; background: var(--tone); }

    .cr-bar { height: 10px; border-radius: 999px; box-shadow: var(--inset-sm); overflow: hidden; background: color-mix(in srgb, var(--ink2) 10%, transparent); }
    .cr-bar > span { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, var(--accent1), var(--accent2)); transition: width .4s ease; }
    .cr-bar.ok > span { background: linear-gradient(90deg, #6fbf93, var(--cr-ok)); }

    .cr-course { display: flex; flex-direction: column; gap: 12px; text-decoration: none; }
    .cr-course-top { display: flex; align-items: flex-start; gap: 12px; }
    .cr-role-tile { width: 46px; height: 46px; border-radius: 15px; flex: none; display: grid; place-items: center; font-size: 19px; color: var(--cr-gold-soft);
        background: linear-gradient(145deg, #0a3f73, var(--cr-navy)); box-shadow: var(--raise); }

    .cr-lesson { display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 16px; box-shadow: var(--inset-sm); text-decoration: none; }
    .cr-lesson:hover { box-shadow: var(--raise); }
    .cr-thumb { width: 104px; aspect-ratio: 16/9; border-radius: 11px; object-fit: cover; flex: none; background: var(--surf); }
    .cr-check { width: 30px; height: 30px; border-radius: 50%; flex: none; display: grid; place-items: center; font-size: 13px; color: #fff; background: var(--tone); }

    .cr-btn { position: relative; display: inline-flex; align-items: center; justify-content: center; gap: 9px; min-height: 48px; padding: 0 22px;
        border: 0; border-radius: 16px; cursor: pointer; font-family: inherit; font-weight: 800; font-size: 14.5px; color: #fff !important; text-decoration: none;
        background: linear-gradient(180deg, color-mix(in srgb, var(--tone) 72%, #fff) 0%, var(--tone) 48%, color-mix(in srgb, var(--tone) 82%, #000) 100%);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.45), 0 5px 0 color-mix(in srgb, var(--tone) 60%, #000), 0 12px 22px color-mix(in srgb, var(--tone) 30%, transparent);
        transform: translateY(-2px); transition: transform .12s ease, box-shadow .12s ease; }
    .cr-btn:active { transform: translateY(2px); box-shadow: inset 0 1px 0 rgba(255,255,255,.3), 0 1px 0 color-mix(in srgb, var(--tone) 60%, #000); }
    .cr-btn[disabled], .cr-btn.is-disabled { filter: grayscale(.7); opacity: .6; cursor: not-allowed; pointer-events: none; }
    .cr-btn-ghost { display: inline-flex; align-items: center; gap: 8px; min-height: 44px; padding: 0 16px; border-radius: 14px; font-weight: 700;
        text-decoration: none; color: var(--ink) !important; box-shadow: var(--raise); background: var(--card-bg); border: 0; cursor: pointer; font-family: inherit; }

    .cr-player { position: relative; width: 100%; aspect-ratio: 16/9; border-radius: 18px; overflow: hidden; background: #000; box-shadow: var(--card-shadow); }
    .cr-player iframe, .cr-player > div { position: absolute; inset: 0; width: 100% !important; height: 100% !important; }

    .cr-alert { padding: 12px 16px; border-radius: 14px; font-size: 13.5px; font-weight: 600; border-left: 4px solid var(--tone);
        background: color-mix(in srgb, var(--tone) 12%, var(--card-bg)); color: var(--ink); }

    .cr-q { padding: 18px; }
    .cr-q-title { font-size: 15.5px; font-weight: 700; color: var(--ink); margin: 0 0 12px; line-height: 1.55; }
    .cr-choice { display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; border-radius: 14px; cursor: pointer; box-shadow: var(--inset-sm);
        font-size: 14px; line-height: 1.5; color: var(--ink); transition: box-shadow .15s ease, background .15s ease; }
    .cr-choice + .cr-choice { margin-top: 8px; }
    .cr-choice input { margin-top: 4px; accent-color: var(--cr-navy); width: 18px; height: 18px; flex: none; }
    .dark .cr-choice input { accent-color: var(--cr-gold); }
    .cr-choice.is-picked { box-shadow: var(--raise); background: color-mix(in srgb, var(--accent1) 12%, transparent); }
    .cr-choice.is-correct { background: color-mix(in srgb, var(--cr-ok) 16%, transparent); }
    .cr-choice.is-wrong { background: color-mix(in srgb, var(--cr-bad) 14%, transparent); }

    .cr-sticky { position: sticky; top: 72px; z-index: 5; }
    @media (max-width: 640px) {
        .cr-thumb { width: 84px; }
        .cr-btn { width: 100%; }
        .cr-sticky { top: 60px; }
    }
    [x-cloak] { display: none !important; }
</style>
