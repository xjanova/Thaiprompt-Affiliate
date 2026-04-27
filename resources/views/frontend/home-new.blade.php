{{--
/**
 * หน้าแรก - ThaiPrompt Landing (V2 Theme)
 *
 * นำธีมจาก thaiprompt web.zip มาใช้เป็นธีมหลัก
 * พร้อมเพิ่มส่วนที่ธีมไม่มีของเราเข้าไป:
 *  - ส่วน Presentation Slides (สื่อการเรียนรู้ระบบ - 9 cards)
 *  - ส่วน Dynamic Sections จาก Homepage Manager
 *  - ใช้สถิติจริงจากฐานข้อมูล
 *
 * V3 Technologies:
 *  - Tailwind CSS (utility-first)
 *  - Alpine.js (reactive)
 *  - Theme CSS scoped ใต้ .tp-landing เพื่อไม่ชนกับ class อื่น
 *
 * @version 2.0.0
 * @author Thaiprompt Team
 * @updated 2026-04-27
 */
--}}

@extends('layouts.landing')

@section('title', 'ThaiPrompt — แพลตฟอร์มเดียว เพื่อคนไทยและเอเชีย')

@section('meta_description', 'ThaiPrompt (ไทยพร๊อมท์) แพลตฟอร์มคนไทย รวม Rider, ตลาดสด, AI Management, Wallet, ปันผลโปร่งใส ด้วย Blockchain ของเราเอง')

@php
    // ดึงข้อมูลจาก SiteSetting (ที่แอดมินตั้งค่าใน admin/site-settings)
    $siteSettings = \App\Models\SiteSetting::getSetting();
    $appName = $siteSettings->site_name ?? 'ThaiPrompt';
    $systemLogo = $siteSettings->logo;

    // ดึง version จาก package.json
    $version = '3.310.0';
    $packageJsonPath = base_path('package.json');
    if (file_exists($packageJsonPath)) {
        $packageJson = json_decode(file_get_contents($packageJsonPath), true);
        if (isset($packageJson['version'])) {
            $version = $packageJson['version'];
        }
    }

    // สถิติโครงการ (จากฐานข้อมูลจริง)
    $stats = [
        'users' => \App\Models\User::count(),
        'affiliates' => \App\Models\MlmMember::count(),
        'transactions' => \App\Models\WalletTransaction::count(),
        'systems' => 20, // 20+ integrated systems
    ];
@endphp

@push('styles')
{{-- Leaflet CSS สำหรับแผนที่จริงในส่วน Asia network --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin="" />
<style>
/* ================================================================
    ThaiPrompt Landing Theme — Blue & Gold
    (จาก design_handoff_thaiprompt_landing/styles.css)
    Scoped ใต้ .tp-landing เพื่อไม่ชนกับ Tailwind / layouts อื่น
================================================================ */
.tp-landing {
    /* Brand backgrounds (deep navy ramp) */
    --bg-deep: #050b1a;
    --bg-1: #0a1430;
    --bg-2: #0e1c4a;
    --bg-3: #122769;

    /* Primary blue ramp */
    --blue-50: #eaf2ff;
    --blue-100: #cfe0ff;
    --blue-200: #9dc0ff;
    --blue-300: #6aa0ff;
    --blue-400: #3d82ff;
    --blue-500: #1f64ff;
    --blue-600: #0b4cdb;
    --blue-700: #0a3aac;
    --blue-800: #082b7f;

    /* Gold ramp */
    --gold-300: #ffe28a;
    --gold-400: #ffcd54;
    --gold-500: #f5b423;
    --gold-600: #d99318;
    --gold-700: #a86c0d;

    --teal: #38d3ee;
    --cyan: #6ee7ff;
    --orange: #ff7a2d;

    --ink: #0a1230;
    --ink-2: #2a3760;
    --ink-3: #5b6890;
    --line: rgba(20, 50, 120, .12);

    --r-sm: 10px;
    --r-md: 14px;
    --r-lg: 20px;
    --r-xl: 28px;
    --r-2xl: 36px;

    --shadow-card: 0 18px 40px -22px rgba(8, 30, 90, .35), 0 4px 14px -6px rgba(8, 30, 90, .15);
    --shadow-glow: 0 30px 80px -30px rgba(31, 100, 255, .55);

    font-family: 'Prompt', 'Noto Sans Thai', 'Inter', system-ui, sans-serif;
    color: var(--ink);
    background: #fafbff;
    -webkit-font-smoothing: antialiased;
}

/* โหลดฟอนต์ Prompt + Noto Sans Thai (อาจซ้ำกับ layouts.landing แต่ปลอดภัย) */
.tp-landing,
.tp-landing * { box-sizing: border-box; }

.tp-landing img { max-width: 100%; display: block; }
.tp-landing a { color: inherit; text-decoration: none; }
.tp-landing button { font-family: inherit; cursor: pointer; }

/* Container ของธีม (ไม่ชนกับ Tailwind .container) */
.tp-landing .tp-container { max-width: 1280px; margin: 0 auto; padding: 0 24px; }

/* Eyebrow text */
.tp-landing .tp-eyebrow {
    display: inline-flex; align-items: center; gap: 8px;
    font-size: 12px; font-weight: 700; letter-spacing: .18em;
    text-transform: uppercase;
    color: var(--gold-500);
}
.tp-landing .tp-eyebrow::before {
    content: ''; width: 24px; height: 1px; background: currentColor;
}

/* ================== Buttons ================== */
.tp-landing .tp-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 12px 22px; border-radius: 12px;
    font-weight: 600; font-size: 14px;
    border: 0;
    transition: transform .25s cubic-bezier(.4,0,.2,1), box-shadow .25s, background .25s;
    white-space: nowrap;
    cursor: pointer;
}
.tp-landing .tp-btn-primary {
    background: linear-gradient(135deg, var(--blue-500), var(--blue-700));
    color: #fff;
    box-shadow: 0 10px 24px -10px rgba(11, 76, 219, .55);
}
.tp-landing .tp-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 18px 36px -12px rgba(11, 76, 219, .7); }
.tp-landing .tp-btn-gold {
    background: linear-gradient(135deg, #ffd76a, var(--gold-500));
    color: #2a1a00;
    box-shadow: 0 10px 24px -10px rgba(245, 180, 35, .55);
}
.tp-landing .tp-btn-gold:hover { transform: translateY(-2px); box-shadow: 0 18px 36px -12px rgba(245, 180, 35, .75); }
.tp-landing .tp-btn-outline-white {
    background: rgba(255,255,255,.08);
    color: #fff;
    border: 1px solid rgba(255,255,255,.25);
    backdrop-filter: blur(8px);
}
.tp-landing .tp-btn-outline-white:hover { background: rgba(255,255,255,.16); transform: translateY(-2px); }

/* ================== IMAGE SLIDE (full-bleed dividers) ================== */
.tp-landing .imgslide {
    position: relative;
    width: 100%;
    height: clamp(440px, 64vh, 760px);
    overflow: hidden;
    isolation: isolate;
    background: #0a1230;
    margin-bottom: -1px;
}
.tp-landing .imgslide.fullscreen { height: 100vh; min-height: 560px; }
.tp-landing .imgslide + section { margin-top: -1px; }
.tp-landing .imgslide-bg {
    position: absolute; inset: 0;
    background-size: cover;
    background-position: center;
}
.tp-landing .imgslide.animate .imgslide-bg {
    transform: scale(1.05);
    animation: tp-kenburns 16s ease-in-out infinite alternate;
}
@keyframes tp-kenburns {
    0% { transform: scale(1.05) translate(0, 0); }
    100% { transform: scale(1.12) translate(-1.5%, -1%); }
}
.tp-landing .imgslide::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(70% 60% at 50% 30%, transparent 0%, rgba(5,11,26,.15) 70%, rgba(5,11,26,.45) 100%);
    z-index: 1;
    pointer-events: none;
}
.tp-landing .imgslide::after {
    content: '';
    position: absolute; left: 0; right: 0; bottom: 0;
    height: 55%;
    background: linear-gradient(180deg,
        rgba(5,11,26,0) 0%,
        rgba(5,11,26,.3) 35%,
        rgba(5,11,26,.75) 65%,
        rgba(5,11,26,.95) 88%,
        #050b1a 100%);
    z-index: 2;
    pointer-events: none;
}
.tp-landing .imgslide.fade-light::after {
    background: linear-gradient(180deg,
        rgba(250,251,255,0) 0%,
        rgba(250,251,255,.25) 35%,
        rgba(250,251,255,.7) 65%,
        rgba(250,251,255,.95) 88%,
        #fafbff 100%);
}
.tp-landing .imgslide-decor { display: none; }
.tp-landing .imgslide.animate .imgslide-decor { display: block; position: absolute; inset: 0; z-index: 2; pointer-events: none; }
.tp-landing .imgslide.animate .imgslide-decor span {
    position: absolute;
    width: 6px; height: 6px; border-radius: 50%;
    background: var(--gold-400);
    box-shadow: 0 0 14px 4px rgba(255,205,84,.55);
    animation: tp-floatY 5s ease-in-out infinite;
}
.tp-landing .imgslide.animate .imgslide-decor span:nth-child(1) { top: 18%; left: 12%; animation-delay: 0s; }
.tp-landing .imgslide.animate .imgslide-decor span:nth-child(2) { top: 30%; left: 78%; animation-delay: -1.6s; background: #6ee7ff; box-shadow: 0 0 14px 4px rgba(110,231,255,.55); }
.tp-landing .imgslide.animate .imgslide-decor span:nth-child(3) { top: 60%; left: 22%; animation-delay: -2.4s; }
.tp-landing .imgslide.animate .imgslide-decor span:nth-child(4) { top: 25%; left: 50%; animation-delay: -3.2s; }
.tp-landing .imgslide.animate .imgslide-decor span:nth-child(5) { top: 72%; left: 70%; animation-delay: -1s; background: #6ee7ff; box-shadow: 0 0 14px 4px rgba(110,231,255,.45); }

/* ================== HERO ================== */
.tp-landing .tp-hero {
    position: relative;
    background:
        radial-gradient(1200px 700px at 80% -10%, rgba(31,100,255,.45), transparent 60%),
        radial-gradient(900px 600px at 0% 100%, rgba(245,180,35,.18), transparent 60%),
        linear-gradient(180deg, #050b1a 0%, #081231 60%, #0a1742 100%);
    color: #fff;
    overflow: hidden;
    isolation: isolate;
}
.tp-landing .tp-hero::before {
    content: '';
    position: absolute; inset: 0;
    background-image:
        linear-gradient(rgba(110,231,255,.08) 1px, transparent 1px),
        linear-gradient(90deg, rgba(110,231,255,.08) 1px, transparent 1px);
    background-size: 56px 56px;
    mask-image: radial-gradient(ellipse 100% 70% at 50% 40%, black 30%, transparent 75%);
    -webkit-mask-image: radial-gradient(ellipse 100% 70% at 50% 40%, black 30%, transparent 75%);
    z-index: 0;
    pointer-events: none;
}
.tp-landing .tp-hero::after {
    content: '';
    position: absolute; inset: 0;
    background:
        radial-gradient(2px 2px at 20% 30%, rgba(255,205,84,.7), transparent 60%),
        radial-gradient(2px 2px at 70% 50%, rgba(110,231,255,.7), transparent 60%),
        radial-gradient(1.5px 1.5px at 35% 70%, rgba(255,255,255,.6), transparent 60%),
        radial-gradient(2px 2px at 85% 20%, rgba(255,205,84,.6), transparent 60%),
        radial-gradient(1px 1px at 50% 85%, rgba(255,255,255,.5), transparent 60%),
        radial-gradient(2px 2px at 12% 80%, rgba(110,231,255,.6), transparent 60%);
    z-index: 0;
    animation: tp-twinkle 6s ease-in-out infinite alternate;
}
@keyframes tp-twinkle { 0%,100% { opacity: .55; } 50% { opacity: 1; } }

.tp-landing .hero-inner {
    position: relative; z-index: 2;
    display: grid; grid-template-columns: 1.1fr 1fr;
    gap: 56px; align-items: center;
    padding-block: 72px 96px;
}
@media (max-width: 980px) { .tp-landing .hero-inner { grid-template-columns: 1fr; padding-block: 48px 72px; } }

.tp-landing .hero-eyebrow {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 7px 14px 7px 8px;
    border-radius: 999px;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,205,84,.3);
    font-size: 12px; font-weight: 600; letter-spacing: .04em;
    color: #ffe7a8;
    backdrop-filter: blur(8px);
}
.tp-landing .hero-eyebrow .dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #4ade80; box-shadow: 0 0 0 4px rgba(74,222,128,.25);
    animation: tp-pulse 1.8s infinite;
}
@keyframes tp-pulse {
    0%,100% { box-shadow: 0 0 0 4px rgba(74,222,128,.25); }
    50% { box-shadow: 0 0 0 8px rgba(74,222,128,.05); }
}
.tp-landing .hero-h1 {
    font-family: 'Prompt','Noto Sans Thai',sans-serif;
    font-size: clamp(40px, 6vw, 78px);
    font-weight: 800; line-height: 1.02; letter-spacing: -.02em;
    margin: 22px 0 16px;
}
.tp-landing .hero-h1 .gold {
    background: linear-gradient(180deg, #fff2c2 0%, #ffd76a 45%, #d99318 100%);
    -webkit-background-clip: text; background-clip: text;
    -webkit-text-fill-color: transparent;
    filter: drop-shadow(0 4px 18px rgba(245,180,35,.3));
}
.tp-landing .hero-h1 .blue {
    background: linear-gradient(180deg, #cfe0ff, #6aa0ff 60%, #3d82ff);
    -webkit-background-clip: text; background-clip: text;
    -webkit-text-fill-color: transparent;
}
.tp-landing .hero-sub {
    font-size: clamp(17px, 1.5vw, 20px);
    color: rgba(255,255,255,.78);
    max-width: 560px; line-height: 1.55;
    margin-bottom: 32px;
}
.tp-landing .hero-pillars { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 28px; }
.tp-landing .pillar-chip {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 8px 14px; border-radius: 999px;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(110,231,255,.22);
    font-size: 13px; color: #cfe0ff; font-weight: 500;
}
.tp-landing .pillar-chip svg { width: 14px; height: 14px; color: var(--gold-400); }
.tp-landing .hero-cta { display: flex; gap: 14px; flex-wrap: wrap; }
.tp-landing .hero-meta {
    display: flex; gap: 24px; flex-wrap: wrap; margin-top: 36px;
    color: rgba(255,255,255,.55); font-size: 13px;
}
.tp-landing .hero-meta strong { color: #fff; font-weight: 700; }

/* hero card visual */
.tp-landing .hero-visual { position: relative; height: 540px; }
@media (max-width: 980px) { .tp-landing .hero-visual { height: 420px; } }
.tp-landing .hero-card {
    position: absolute; inset: 0;
    border-radius: 28px;
    overflow: hidden;
    background: linear-gradient(180deg, #102052, #050b1a 70%);
    box-shadow: 0 50px 100px -30px rgba(0,0,0,.6), inset 0 0 0 1px rgba(255,255,255,.06);
}
.tp-landing .hero-card-bg {
    position: absolute; inset: 0;
    background-image: linear-gradient(135deg, rgba(11,76,219,.5), rgba(8,43,127,.6)),
        radial-gradient(circle at 30% 30%, rgba(245,180,35,.2), transparent 60%);
    background-size: cover; background-position: center;
}
.tp-landing .hero-card-bg::after {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(180deg, rgba(5,11,26,0) 50%, rgba(5,11,26,.65) 100%);
}
.tp-landing .hero-floats { position: absolute; inset: 0; }
.tp-landing .float-card {
    position: absolute;
    background: rgba(255,255,255,.95);
    backdrop-filter: blur(20px);
    border-radius: 16px;
    padding: 12px 16px;
    box-shadow: 0 20px 50px -16px rgba(0,0,0,.45);
    display: flex; align-items: center; gap: 12px;
    font-size: 13px; color: var(--ink);
    animation: tp-floatY 4.5s ease-in-out infinite;
}
.tp-landing .float-card .ic {
    width: 36px; height: 36px; border-radius: 10px;
    display: grid; place-items: center;
    background: linear-gradient(135deg, var(--blue-500), var(--blue-700));
    color: #fff; flex-shrink: 0;
}
.tp-landing .float-card .lbl { font-size: 11px; color: var(--ink-3); font-weight: 500; }
.tp-landing .float-card .val { font-size: 14px; font-weight: 700; color: var(--ink); }
.tp-landing .float-1 { top: 12%; left: -28px; animation-delay: 0s; }
.tp-landing .float-2 { top: 50%; right: -30px; animation-delay: -1.4s; }
.tp-landing .float-3 { bottom: 8%; left: 12%; animation-delay: -2.6s; }
@keyframes tp-floatY { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }

.tp-landing .hero-badge-tl {
    position: absolute; top: 18px; left: 18px;
    display: flex; align-items: center; gap: 8px;
    padding: 8px 14px;
    border-radius: 999px;
    background: linear-gradient(135deg, var(--gold-400), var(--gold-600));
    color: #2a1a00; font-weight: 700; font-size: 12px;
    box-shadow: 0 12px 28px -10px rgba(245,180,35,.6);
}
.tp-landing .hero-badge-br {
    position: absolute; bottom: 18px; right: 18px;
    padding: 8px 14px;
    border-radius: 999px;
    background: rgba(0,0,0,.4); color: #cfe0ff; font-size: 12px;
    border: 1px solid rgba(255,255,255,.12);
    backdrop-filter: blur(10px);
}

/* hero stats strip (overlapping) */
.tp-landing .hero-stats {
    position: relative; z-index: 6;
    display: grid; grid-template-columns: repeat(4, 1fr);
    background: #fff;
    border-radius: 24px;
    margin: -56px auto 0;
    max-width: 1180px;
    box-shadow: 0 30px 60px -25px rgba(8,30,90,.35);
    overflow: hidden;
    border: 1px solid var(--line);
}
.tp-landing .hero-stats > div {
    padding: 26px 28px; border-right: 1px solid var(--line);
    display: flex; align-items: center; gap: 16px;
}
.tp-landing .hero-stats > div:last-child { border-right: 0; }
.tp-landing .hero-stats .num {
    font-family: 'Prompt'; font-weight: 800;
    font-size: 30px; color: var(--blue-700);
    letter-spacing: -.02em; line-height: 1;
}
.tp-landing .hero-stats .lbl { font-size: 13px; color: var(--ink-3); margin-top: 4px; }
.tp-landing .hero-stats .ic-wrap {
    width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
    display: grid; place-items: center;
    background: linear-gradient(135deg, #eaf2ff, #cfe0ff);
    color: var(--blue-700);
}
@media (max-width: 900px) {
    .tp-landing .hero-stats { grid-template-columns: repeat(2, 1fr); margin-top: -40px; }
    .tp-landing .hero-stats > div:nth-child(2) { border-right: 0; }
    .tp-landing .hero-stats > div:nth-child(1),
    .tp-landing .hero-stats > div:nth-child(2) { border-bottom: 1px solid var(--line); }
}
@media (max-width: 540px) {
    .tp-landing .hero-stats { grid-template-columns: 1fr; margin-top: -32px; border-radius: 18px; }
    .tp-landing .hero-stats > div { border-right: 0; border-bottom: 1px solid var(--line); padding: 20px 22px; }
    .tp-landing .hero-stats > div:last-child { border-bottom: 0; }
    .tp-landing .hero-stats .num { font-size: 24px; }
}

/* ================== SECTION ================== */
.tp-landing .tp-section { padding: 96px 0; }
@media (max-width: 900px) { .tp-landing .tp-section { padding: 64px 0; } }
@media (max-width: 540px) { .tp-landing .tp-section { padding: 56px 0; } }

.tp-landing .fade-bottom-light { position: relative; }
.tp-landing .fade-bottom-light::after {
    content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 80px;
    background: linear-gradient(180deg, transparent, #fafbff);
    pointer-events: none; z-index: 5;
}
.tp-landing .fade-top-light { position: relative; }
.tp-landing .fade-top-light::before {
    content: ''; position: absolute; left: 0; right: 0; top: 0; height: 60px;
    background: linear-gradient(180deg, #fafbff, transparent);
    pointer-events: none; z-index: 1;
}
.tp-landing .fade-bottom-dark { position: relative; }
.tp-landing .fade-bottom-dark::after {
    content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 80px;
    background: linear-gradient(180deg, transparent, #050b1a);
    pointer-events: none; z-index: 5;
}
.tp-landing .sec-head { text-align: center; max-width: 720px; margin: 0 auto 56px; }
.tp-landing .sec-head h2 {
    font-family: 'Prompt'; font-size: clamp(30px, 4vw, 48px);
    font-weight: 800; letter-spacing: -.02em; line-height: 1.1;
    margin: 12px 0 14px; color: var(--ink);
}
.tp-landing .sec-head p { font-size: 17px; color: var(--ink-3); line-height: 1.6; margin: 0; }
.tp-landing .sec-head .grad {
    background: linear-gradient(90deg, var(--blue-600), var(--gold-500));
    -webkit-background-clip: text; background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* ================== SERVICES ================== */
.tp-landing .services { background: #fafbff; position: relative; }
.tp-landing .services-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
@media (max-width: 980px) { .tp-landing .services-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px) { .tp-landing .services-grid { grid-template-columns: 1fr; } }

.tp-landing .service-card {
    position: relative;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 24px;
    padding: 28px 26px;
    transition: transform .35s, box-shadow .35s, border-color .35s;
    overflow: hidden;
}
.tp-landing .service-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-card);
    border-color: transparent;
}
.tp-landing .service-card .ic {
    width: 56px; height: 56px;
    border-radius: 16px;
    display: grid; place-items: center;
    background: var(--accent, linear-gradient(135deg, var(--blue-500), var(--blue-700)));
    color: #fff;
    margin-bottom: 18px;
    box-shadow: 0 12px 28px -12px var(--accent-shadow, rgba(11,76,219,.5));
}
.tp-landing .service-card h3 { font-family: 'Prompt'; font-size: 19px; font-weight: 700; margin: 0 0 6px; color: var(--ink); }
.tp-landing .service-card .tag {
    display: inline-block;
    font-size: 11px; font-weight: 600; letter-spacing: .08em;
    text-transform: uppercase; color: var(--blue-600);
    margin-bottom: 12px;
}
.tp-landing .service-card p { font-size: 14px; color: var(--ink-3); line-height: 1.6; margin: 0 0 16px; }
.tp-landing .service-card .feats { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 7px; }
.tp-landing .service-card .feats li { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--ink-2); }
.tp-landing .service-card .feats li svg { width: 14px; height: 14px; color: var(--gold-500); flex-shrink: 0; }
.tp-landing .service-card .arrow {
    margin-top: 18px;
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 13px; font-weight: 600; color: var(--blue-600);
    transition: gap .25s;
}
.tp-landing .service-card:hover .arrow { gap: 10px; }

/* ================== WALLET ================== */
.tp-landing .wallet-section {
    background:
        radial-gradient(800px 500px at 80% 20%, rgba(31,100,255,.35), transparent 70%),
        linear-gradient(180deg, #050b1a 0%, #060d22 8%, #0a1742 100%),
        radial-gradient(700px 500px at 10% 80%, rgba(245,180,35,.16), transparent 70%);
    color: #fff; position: relative; overflow: hidden;
}
.tp-landing .wallet-section::before {
    content: '';
    position: absolute; inset: 0;
    background-image:
        linear-gradient(rgba(110,231,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(110,231,255,.06) 1px, transparent 1px);
    background-size: 48px 48px;
    mask-image: radial-gradient(ellipse 70% 70% at 50% 50%, black 20%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse 70% 70% at 50% 50%, black 20%, transparent 80%);
}
.tp-landing .wallet-grid {
    position: relative; z-index: 1;
    display: grid; grid-template-columns: 1fr 1.1fr; gap: 64px; align-items: center;
}
@media (max-width: 980px) { .tp-landing .wallet-grid { grid-template-columns: 1fr; } }
.tp-landing .wallet-h2 {
    font-family: 'Prompt'; font-size: clamp(30px, 4vw, 46px);
    font-weight: 800; line-height: 1.1; letter-spacing: -.02em;
    margin: 16px 0 16px;
}
.tp-landing .wallet-h2 .gold {
    background: linear-gradient(180deg, #fff2c2, #ffcd54 60%, #d99318);
    -webkit-background-clip: text; background-clip: text;
    -webkit-text-fill-color: transparent;
}
.tp-landing .wallet-text-p { color: rgba(255,255,255,.72); font-size: 17px; line-height: 1.7; margin-bottom: 28px; }
.tp-landing .wallet-feat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 28px; }
@media (max-width: 540px) { .tp-landing .wallet-feat-grid { grid-template-columns: 1fr; } }
.tp-landing .wallet-feat {
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 16px;
    padding: 18px;
    backdrop-filter: blur(8px);
    transition: border-color .25s, transform .25s;
}
.tp-landing .wallet-feat:hover { border-color: rgba(255,205,84,.4); transform: translateY(-2px); }
.tp-landing .wallet-feat .ic {
    width: 38px; height: 38px; border-radius: 10px;
    display: grid; place-items: center;
    background: linear-gradient(135deg, rgba(110,231,255,.18), rgba(31,100,255,.18));
    border: 1px solid rgba(110,231,255,.25);
    color: var(--gold-400);
    margin-bottom: 12px;
}
.tp-landing .wallet-feat h4 { font-family: 'Prompt'; font-size: 15px; font-weight: 600; margin: 0 0 4px; color: #fff; }
.tp-landing .wallet-feat p { font-size: 13px; color: rgba(255,255,255,.6); margin: 0; line-height: 1.5; }

/* phone mock */
.tp-landing .phone-mock { position: relative; width: 100%; aspect-ratio: 1/1; display: grid; place-items: center; }
.tp-landing .phone-orbit { position: absolute; inset: 0; display: grid; place-items: center; }
.tp-landing .phone-orbit::before {
    content: ''; position: absolute;
    width: 90%; height: 90%; border-radius: 50%;
    border: 1px dashed rgba(110,231,255,.25);
    animation: tp-spin 40s linear infinite;
}
.tp-landing .phone-orbit::after {
    content: ''; position: absolute;
    width: 70%; height: 70%; border-radius: 50%;
    border: 1px dashed rgba(255,205,84,.18);
    animation: tp-spin 30s linear infinite reverse;
}
@keyframes tp-spin { to { transform: rotate(360deg); } }
.tp-landing .coin {
    position: absolute;
    width: 64px; height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ffe28a, #d99318);
    box-shadow: 0 0 0 2px #6e4a00, 0 12px 30px -10px rgba(245,180,35,.65);
    display: grid; place-items: center;
    font-family: 'Prompt'; font-weight: 900; font-size: 22px; color: #4a2e00;
    animation: tp-floatY 5s ease-in-out infinite;
}
.tp-landing .coin.c1 { top: 8%; right: 10%; animation-delay: 0s; }
.tp-landing .coin.c2 { bottom: 14%; left: 4%; animation-delay: -1.5s; width: 52px; height: 52px; font-size: 18px; }
.tp-landing .coin.c3 { top: 38%; left: -2%; animation-delay: -2.5s; width: 44px; height: 44px; font-size: 15px; }
.tp-landing .phone {
    position: relative;
    width: 280px; height: 560px;
    border-radius: 44px;
    background: linear-gradient(180deg, #0a1230, #050b1a);
    box-shadow: 0 60px 100px -30px rgba(0,0,0,.7),
        inset 0 0 0 2px rgba(255,255,255,.08),
        inset 0 0 0 8px #050b1a,
        inset 0 0 0 9px rgba(110,231,255,.18);
    padding: 12px;
    z-index: 2;
}
.tp-landing .phone-screen {
    width: 100%; height: 100%;
    border-radius: 36px;
    background: linear-gradient(180deg, #0d1d4f, #061030);
    overflow: hidden;
    position: relative;
    padding: 22px 18px;
    display: flex; flex-direction: column; gap: 14px;
}
.tp-landing .phone-notch {
    position: absolute; top: 16px; left: 50%; transform: translateX(-50%);
    width: 90px; height: 22px; border-radius: 14px; background: #050b1a;
    z-index: 5;
}
.tp-landing .phone-head { display: flex; justify-content: space-between; align-items: center; margin-top: 24px; }
.tp-landing .phone-head .h1 { font-size: 11px; color: rgba(255,255,255,.5); }
.tp-landing .phone-head .h2 { font-size: 11px; color: #4ade80; display: flex; align-items: center; gap: 4px; }
.tp-landing .phone-head .h2::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #4ade80; }
.tp-landing .balance-card {
    background: linear-gradient(135deg, #1f64ff, #0a3aac);
    border-radius: 20px; padding: 20px;
    position: relative; overflow: hidden;
    box-shadow: 0 18px 36px -16px rgba(11,76,219,.7);
}
.tp-landing .balance-card::before {
    content: ''; position: absolute; right: -30px; top: -30px;
    width: 140px; height: 140px; border-radius: 50%;
    background: radial-gradient(circle, rgba(255,205,84,.35), transparent 60%);
}
.tp-landing .balance-card .lbl { font-size: 11px; color: rgba(255,255,255,.7); }
.tp-landing .balance-card .val {
    font-family: 'Prompt'; font-weight: 800; font-size: 28px; color: #fff; margin-top: 4px;
    letter-spacing: -.02em;
}
.tp-landing .balance-card .val small { font-size: 14px; color: rgba(255,255,255,.7); font-weight: 500; }
.tp-landing .balance-card .pct {
    display: inline-flex; align-items: center; gap: 4px;
    background: rgba(255,255,255,.18); padding: 3px 8px; border-radius: 999px;
    font-size: 11px; color: #fff; margin-top: 10px;
}
.tp-landing .phone-actions { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
.tp-landing .phone-actions button {
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.08);
    color: rgba(255,255,255,.85);
    padding: 10px 6px; border-radius: 12px;
    font-size: 10px; display: flex; flex-direction: column; align-items: center; gap: 4px;
}
.tp-landing .phone-actions button svg { width: 16px; height: 16px; color: var(--gold-400); }
.tp-landing .phone-tx { display: flex; flex-direction: column; gap: 8px; flex: 1; }
.tp-landing .phone-tx-item {
    display: flex; align-items: center; gap: 10px;
    background: rgba(255,255,255,.04);
    padding: 10px;
    border-radius: 12px;
}
.tp-landing .phone-tx-item .ic {
    width: 30px; height: 30px; border-radius: 8px; display: grid; place-items: center;
    background: rgba(31,100,255,.25); color: #6aa0ff;
}
.tp-landing .phone-tx-item .meta { flex: 1; min-width: 0; }
.tp-landing .phone-tx-item .meta b { font-size: 11px; color: #fff; display: block; font-weight: 600; }
.tp-landing .phone-tx-item .meta span { font-size: 9px; color: rgba(255,255,255,.5); }
.tp-landing .phone-tx-item .amt { font-size: 11px; font-weight: 700; }
.tp-landing .phone-tx-item .amt.up { color: #4ade80; }
.tp-landing .phone-tx-item .amt.dn { color: #f87171; }

/* ================== HOW IT WORKS ================== */
.tp-landing .how { background: #fafbff; }
.tp-landing .how-track { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; position: relative; }
.tp-landing .how-track::before {
    content: ''; position: absolute;
    left: 5%; right: 5%; top: 36px; height: 2px;
    background: repeating-linear-gradient(90deg, var(--blue-300) 0, var(--blue-300) 8px, transparent 8px, transparent 16px);
    z-index: 0;
}
@media (max-width: 900px) {
    .tp-landing .how-track { grid-template-columns: 1fr 1fr; gap: 28px; }
    .tp-landing .how-track::before { display: none; }
}
@media (max-width: 540px) { .tp-landing .how-track { grid-template-columns: 1fr; } }
.tp-landing .how-step { position: relative; z-index: 1; text-align: center; padding: 0 8px; }
.tp-landing .how-step .num {
    width: 72px; height: 72px; border-radius: 24px;
    background: #fff;
    border: 2px solid #fff;
    box-shadow: 0 12px 30px -14px rgba(11,76,219,.4), inset 0 0 0 1px rgba(11,76,219,.08);
    margin: 0 auto 18px;
    display: grid; place-items: center;
    font-family: 'Prompt'; font-size: 28px; font-weight: 800; color: var(--blue-700);
    position: relative;
}
.tp-landing .how-step h4 { font-family: 'Prompt'; font-size: 17px; font-weight: 700; margin: 0 0 8px; color: var(--ink); }
.tp-landing .how-step p { font-size: 14px; color: var(--ink-3); line-height: 1.6; margin: 0; }

/* ================== ASIA ================== */
.tp-landing .asia { background: linear-gradient(180deg, #fafbff, #eef3ff); position: relative; overflow: hidden; }
.tp-landing .asia-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 56px; align-items: center; }
@media (max-width: 900px) { .tp-landing .asia-grid { grid-template-columns: 1fr; } }
.tp-landing .asia-flags { display: flex; flex-wrap: wrap; gap: 10px; margin: 24px 0; }
.tp-landing .flag-pill {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 8px 14px; border-radius: 999px;
    background: #fff; border: 1px solid var(--line);
    font-size: 13px; font-weight: 600; color: var(--ink-2);
    box-shadow: 0 2px 8px -2px rgba(0,0,0,.06);
    transition: transform .2s, border-color .2s;
}
.tp-landing .flag-pill:hover { transform: translateY(-2px); border-color: var(--gold-400); }
.tp-landing .flag-pill .em { font-size: 18px; }
.tp-landing .asia-map {
    position: relative;
    aspect-ratio: 2/1;
    background: linear-gradient(180deg, #f0f5ff, #e0ecff);
    border-radius: 28px;
    border: 1px solid var(--line);
    box-shadow: 0 30px 60px -30px rgba(8,30,90,.25);
    overflow: hidden;
    display: grid; place-items: center;
}
/* (เดิมมีกฎ .asia-map svg { width:100%; height:100% } สำหรับ SVG world map เก่า
    — ลบแล้ว เพราะทำให้ Leaflet attribution flag SVG (12x8px) ขยายเต็มจอ) */
@keyframes tp-pulse-pin { 0%,100% { transform: scale(1); } 50% { transform: scale(1.25); } }

/* ================== CTA STRIP ================== */
.tp-landing .cta-strip {
    position: relative;
    background:
        radial-gradient(700px 400px at 0% 0%, rgba(245,180,35,.25), transparent 60%),
        radial-gradient(700px 400px at 100% 100%, rgba(110,231,255,.18), transparent 60%),
        linear-gradient(135deg, #082b7f, #050b1a 70%);
    color: #fff;
    border-radius: 36px;
    padding: 64px clamp(24px, 5vw, 56px);
    margin: 0 auto;
    max-width: 1180px;
    display: grid;
    grid-template-columns: 1.4fr 1fr;
    gap: 40px;
    align-items: center;
    overflow: hidden;
    box-shadow: 0 40px 80px -30px rgba(8,30,90,.5);
}
@media (max-width: 900px) {
    .tp-landing .cta-strip { grid-template-columns: 1fr; padding: 48px 32px; border-radius: 28px; }
}
.tp-landing .cta-strip h2 {
    font-family: 'Prompt';
    font-size: clamp(28px, 3.5vw, 44px);
    font-weight: 800; letter-spacing: -.02em; line-height: 1.1; margin: 0 0 14px;
}
.tp-landing .cta-strip h2 .gold {
    background: linear-gradient(180deg, #fff2c2, #ffcd54 60%, #d99318);
    -webkit-background-clip: text; background-clip: text;
    -webkit-text-fill-color: transparent;
}
.tp-landing .cta-strip p { color: rgba(255,255,255,.75); font-size: 16px; line-height: 1.6; margin: 0 0 26px; }
.tp-landing .cta-form {
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 20px;
    padding: 22px;
    backdrop-filter: blur(12px);
}
.tp-landing .cta-form label { font-size: 12px; color: rgba(255,255,255,.6); display: block; margin-bottom: 8px; }
.tp-landing .cta-form-row { display: flex; gap: 8px; }
.tp-landing .cta-form input {
    flex: 1; min-width: 0;
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.16);
    border-radius: 12px;
    padding: 12px 14px;
    color: #fff; font-size: 14px;
    font-family: inherit;
    outline: none;
}
.tp-landing .cta-form input::placeholder { color: rgba(255,255,255,.4); }
.tp-landing .cta-form input:focus { border-color: var(--gold-400); }
.tp-landing .cta-form .hint { font-size: 11px; color: rgba(255,255,255,.45); margin-top: 10px; }

/* ================== FOOTER ================== */
.tp-landing .tp-footer { background: #050b1a; color: rgba(255,255,255,.7); padding: 72px 0 32px; }
.tp-landing .footer-grid { display: grid; grid-template-columns: 1.4fr 1fr 1fr 1fr; gap: 40px; margin-bottom: 48px; }
@media (max-width: 900px) { .tp-landing .footer-grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 540px) { .tp-landing .footer-grid { grid-template-columns: 1fr; } }
.tp-landing .tp-footer h5 { color: #fff; font-family: 'Prompt'; font-size: 15px; font-weight: 700; margin: 0 0 16px; letter-spacing: .02em; }
.tp-landing .tp-footer ul { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px; }
.tp-landing .tp-footer ul a { font-size: 14px; color: rgba(255,255,255,.6); transition: color .2s; }
.tp-landing .tp-footer ul a:hover { color: var(--gold-400); }
.tp-landing .footer-bottom {
    border-top: 1px solid rgba(255,255,255,.08);
    padding-top: 24px;
    display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;
    font-size: 13px; color: rgba(255,255,255,.45);
}
.tp-landing .footer-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
.tp-landing .footer-brand-mark {
    width: 40px; height: 40px; border-radius: 12px;
    background: linear-gradient(135deg, #0b4cdb, #082b7f 70%);
    display: grid; place-items: center;
    color: var(--gold-400);
    font-family: 'Prompt'; font-weight: 900; font-size: 20px;
    box-shadow: inset 0 0 0 1px rgba(255,205,84,.35), 0 8px 18px -10px rgba(11,76,219,.65);
}
.tp-landing .footer-social { display: flex; gap: 10px; }
.tp-landing .footer-social a {
    width: 38px; height: 38px; border-radius: 10px;
    display: grid; place-items: center;
    background: rgba(255,255,255,.06);
    color: rgba(255,255,255,.7);
    transition: background .2s, color .2s, transform .2s;
}
.tp-landing .footer-social a:hover { background: var(--gold-500); color: #0a1230; transform: translateY(-2px); }

/* ================== TICKER ================== */
.tp-landing .ticker {
    background: linear-gradient(90deg, #082b7f, #0a3aac, #082b7f);
    color: #fff; padding: 14px 0;
    overflow: hidden;
    border-top: 1px solid rgba(255,205,84,.25);
    border-bottom: 1px solid rgba(255,205,84,.25);
}
.tp-landing .ticker-track { display: flex; gap: 56px; animation: tp-ticker 35s linear infinite; white-space: nowrap; width: max-content; }
@keyframes tp-ticker { from { transform: translateX(0); } to { transform: translateX(-50%); } }
.tp-landing .ticker-item { display: inline-flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; letter-spacing: .03em; }
.tp-landing .ticker-item .em { color: var(--gold-400); }
.tp-landing .ticker-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--gold-400); }

/* ================== Reveal-on-scroll ================== */
.tp-landing .reveal { opacity: 0; transform: translateY(20px); transition: opacity .8s, transform .8s; }
.tp-landing .reveal.in { opacity: 1; transform: none; }

/* ================== Presentation Slides Section (เพิ่มจากของเดิม) ================== */
.tp-landing .demo-section {
    background: linear-gradient(180deg, #fafbff 0%, #f0f5ff 100%);
    position: relative;
}
.tp-landing .demo-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;
}
@media (max-width: 980px) { .tp-landing .demo-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px) { .tp-landing .demo-grid { grid-template-columns: 1fr; } }
.tp-landing .demo-card {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 24px;
    padding: 28px 26px;
    cursor: pointer;
    transition: transform .35s, box-shadow .35s, border-color .35s;
}
.tp-landing .demo-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-card);
    border-color: rgba(245,180,35,.4);
}
.tp-landing .demo-card .ic {
    width: 56px; height: 56px;
    border-radius: 16px;
    display: grid; place-items: center;
    color: #fff;
    margin-bottom: 18px;
    font-size: 24px;
}
.tp-landing .demo-card h3 { font-family: 'Prompt'; font-size: 18px; font-weight: 700; margin: 0 0 8px; color: var(--ink); }
.tp-landing .demo-card p { font-size: 14px; color: var(--ink-3); line-height: 1.6; margin: 0 0 14px; }
.tp-landing .demo-card .arrow {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 13px; font-weight: 600; color: var(--blue-600);
    transition: gap .25s;
}
.tp-landing .demo-card:hover .arrow { gap: 10px; }

/* ================== Leaflet (Asia real map) ================== */
.tp-landing #asia-real-map {
    width: 100%; height: 100%;
    border-radius: 28px;
    background: linear-gradient(180deg, #f0f5ff, #e0ecff);
}
.tp-landing #asia-real-map .leaflet-control-zoom a {
    background: rgba(255,255,255,.95);
    border: 1px solid var(--line);
    color: var(--ink-2);
    font-weight: 700;
}
.tp-landing #asia-real-map .leaflet-control-zoom a:hover {
    background: var(--gold-400); color: #2a1a00;
}
.tp-landing #asia-real-map .leaflet-control-attribution {
    font-size: 10px;
    background: rgba(255,255,255,.85);
}
.tp-landing .tp-leaflet-icon {
    background: transparent !important;
    border: 0 !important;
}
.tp-landing .tp-leaflet-pin {
    width: 14px; height: 14px; border-radius: 50%;
    border: 2px solid #fff;
    background: var(--blue-500);
    box-shadow: 0 0 0 4px rgba(31,100,255,.2), 0 0 0 8px rgba(31,100,255,.08), 0 4px 8px rgba(0,0,0,.15);
    animation: tp-pulse-pin 2.4s infinite;
}
.tp-landing .tp-leaflet-pin.tp-leaflet-pin-gold {
    width: 18px; height: 18px;
    background: var(--gold-500);
    box-shadow: 0 0 0 5px rgba(245,180,35,.3), 0 0 0 10px rgba(245,180,35,.12), 0 4px 12px rgba(245,180,35,.4);
    animation-duration: 1.8s;
}
.tp-landing #asia-real-map .leaflet-tooltip {
    background: rgba(8,30,90,.92);
    border: 1px solid var(--gold-400);
    color: #fff;
    font-family: 'Prompt','Noto Sans Thai',sans-serif;
    font-size: 12px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,.25);
}
.tp-landing #asia-real-map .leaflet-tooltip-top:before { border-top-color: var(--gold-400); }
</style>
@endpush

@section('content')

<div class="tp-landing">

    {{-- ================================================================
        SLIDE 1 — Full-bleed hero image (no text, fades into Hero)
    ================================================================ --}}
    <section class="imgslide fullscreen">
        <div class="imgslide-bg"
             style="background-image: url('{{ asset('images/landing/slide-top.png') }}'); background-position: center 35%;"></div>
    </section>

    {{-- ================================================================
        HERO — Headline + value props + CTAs + floating cards + stats strip
    ================================================================ --}}
    <section class="tp-hero">
        <div class="tp-container hero-inner">
            {{-- Left content --}}
            <div>
                <div class="hero-eyebrow">
                    <span class="dot"></span>
                    <span>แพลตฟอร์มคนไทย • เพื่อคนไทย • เพื่อเอเชีย</span>
                </div>

                <h1 class="hero-h1">
                    <span class="gold">ไทยพร๊อมท์</span> แพลตฟอร์ม<br>
                    <span class="blue">เพื่อชีวิตที่ดีกว่า</span>
                </h1>

                <p class="hero-sub">
                    รวมทุกโซลูชันในที่เดียว — Rider &amp; E-commerce, ตลาดสด, AI Management,
                    กระเป๋าเงินดิจิทัล และระบบปันผลที่โปร่งใสด้วย Blockchain ของเราเอง
                </p>

                <div class="hero-pillars">
                    <span class="pillar-chip">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l1.6 5.4L19 9l-5.4 1.6L12 16l-1.6-5.4L5 9l5.4-1.6L12 2z"/></svg>
                        โปร่งใส
                    </span>
                    <span class="pillar-chip">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l1.6 5.4L19 9l-5.4 1.6L12 16l-1.6-5.4L5 9l5.4-1.6L12 2z"/></svg>
                        ยุติธรรม
                    </span>
                    <span class="pillar-chip">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l1.6 5.4L19 9l-5.4 1.6L12 16l-1.6-5.4L5 9l5.4-1.6L12 2z"/></svg>
                        เติบโตไปด้วยกัน
                    </span>
                    <span class="pillar-chip">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l1.6 5.4L19 9l-5.4 1.6L12 16l-1.6-5.4L5 9l5.4-1.6L12 2z"/></svg>
                        ยั่งยืน
                    </span>
                </div>

                <div class="hero-cta">
                    <a href="{{ route('register') }}" class="tp-btn tp-btn-gold">
                        เริ่มต้นใช้งานฟรี
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                    <a href="#how" class="tp-btn tp-btn-outline-white">ดูวิธีการทำงาน</a>
                </div>

                <div class="hero-meta">
                    <div><strong>10+</strong> ประเทศในเอเชีย</div>
                    <div><strong>{{ number_format($stats['users']) }}+</strong> ผู้ใช้งาน</div>
                    <div><strong>24/7</strong> ตลอดเวลา</div>
                </div>
            </div>

            {{-- Right visual: card + floating product cards --}}
            <div class="hero-visual">
                <div class="hero-card">
                    <div class="hero-card-bg"></div>
                    <div class="hero-badge-tl">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.9 6.9 7.4.6-5.6 4.9 1.7 7.3L12 17.8 5.6 21.7l1.7-7.3L1.7 9.5l7.4-.6L12 2z"/></svg>
                        แพลตฟอร์มคนไทย เพื่อคนไทย
                    </div>
                    <div class="hero-badge-br">โปร่งใส · ตรวจสอบได้ด้วย Blockchain</div>
                </div>

                <div class="hero-floats">
                    {{-- Float 1: ออเดอร์วันนี้ --}}
                    <div class="float-card float-1">
                        <div class="ic" style="background: linear-gradient(135deg, #ff7a2d, #d04b00);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="17" r="2.5"/><circle cx="18" cy="17" r="2.5"/><path d="M6 17h12M9 8h4l4 9M9 8l-2 5M13 8l1-3h2"/></svg>
                        </div>
                        <div>
                            <div class="lbl">ออเดอร์วันนี้</div>
                            <div class="val">+2,481 <span style="color:#22c55e;font-size:12px">↑12%</span></div>
                        </div>
                    </div>

                    {{-- Float 2: ปันผลรายสัปดาห์ --}}
                    <div class="float-card float-2">
                        <div class="ic" style="background: linear-gradient(135deg, #f5b423, #a86c0d);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2v2H6a3 3 0 0 0 0 6h14v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"/><circle cx="16" cy="12" r="1.5"/></svg>
                        </div>
                        <div>
                            <div class="lbl">ปันผลรายสัปดาห์</div>
                            <div class="val">฿ 84,250</div>
                        </div>
                    </div>

                    {{-- Float 3: ธุรกรรมบนเชน --}}
                    <div class="float-card float-3">
                        <div class="ic" style="background: linear-gradient(135deg, #38d3ee, #0b4cdb);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 13a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7L10.5 5.8"/><path d="M15 11a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7L13.5 18.2"/></svg>
                        </div>
                        <div>
                            <div class="lbl">ธุรกรรมบนเชน</div>
                            <div class="val">{{ number_format($stats['transactions']) }}+ TX</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hero stats strip — overlaps hero (-56px) with REAL DB data --}}
        <div class="tp-container">
            <div class="hero-stats">
                <div>
                    <div class="ic-wrap">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 19a6 6 0 0 1 12 0M14 19a5 5 0 0 1 7 0"/></svg>
                    </div>
                    <div>
                        <div class="num">{{ number_format($stats['users']) }}+</div>
                        <div class="lbl">ผู้ใช้งานทั่วเอเชีย</div>
                    </div>
                </div>
                <div>
                    <div class="ic-wrap">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h2l2.4 11.4a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.5L21 8H6"/><circle cx="9" cy="20" r="1.5"/><circle cx="17" cy="20" r="1.5"/></svg>
                    </div>
                    <div>
                        <div class="num">{{ number_format($stats['affiliates']) }}+</div>
                        <div class="lbl">พันธมิตร Affiliate</div>
                    </div>
                </div>
                <div>
                    <div class="ic-wrap">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 13a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7L10.5 5.8"/><path d="M15 11a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7L13.5 18.2"/></svg>
                    </div>
                    <div>
                        <div class="num">99.9%</div>
                        <div class="lbl">ความเสถียรของเครือข่าย</div>
                    </div>
                </div>
                <div>
                    <div class="ic-wrap">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg>
                    </div>
                    <div>
                        <div class="num">{{ $stats['systems'] }}+</div>
                        <div class="lbl">ระบบรองรับ All-in-One</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================================================================
        TICKER — Animated marquee of blockchain status pills
    ================================================================ --}}
    <div class="ticker">
        <div class="ticker-track">
            @php
                // รายการสำหรับ ticker (ทำซ้ำเพื่อ seamless loop)
                $tickerItems = [
                    ['em' => '⚡', 't' => 'Block #2,481,902 confirmed'],
                    ['em' => '✦', 't' => '+12,408 transactions today'],
                    ['em' => '◈', 't' => 'Avg fee 0.0008 TPX'],
                    ['em' => '◆', 't' => 'Network uptime 99.98%'],
                    ['em' => '✸', 't' => 'Active validators 142'],
                    ['em' => '⬢', 't' => 'Cross-Asia payments enabled'],
                ];
            @endphp
            @foreach(array_merge($tickerItems, $tickerItems) as $item)
                <span class="ticker-item">
                    <span class="em">{{ $item['em'] }}</span> {{ $item['t'] }}
                    <span class="ticker-dot" style="margin-left: 24px"></span>
                </span>
            @endforeach
        </div>
    </div>

    {{-- ================================================================
        SERVICES — 6 service pillars
    ================================================================ --}}
    <section id="services" class="tp-section services fade-top-light fade-bottom-light">
        <div class="tp-container">
            <div class="sec-head">
                <div class="tp-eyebrow">หกเสาหลักของเรา</div>
                <h2>แพลตฟอร์มเดียว <span class="grad">รวมทุกโซลูชัน</span></h2>
                <p>เชื่อมร้านค้า ผู้บริโภค ไรเดอร์ และเครือข่ายปันผล เข้าด้วยกันบนระบบเดียว ที่โปร่งใส ยุติธรรม และเติบโตไปด้วยกัน</p>
            </div>

            <div class="services-grid">
                {{-- 1. Rider & Delivery --}}
                <article class="service-card" style="--accent: linear-gradient(135deg,#ff7a2d,#d04b00); --accent-shadow: rgba(255,122,45,.45);">
                    <div class="ic">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="17" r="2.5"/><circle cx="18" cy="17" r="2.5"/><path d="M6 17h12M9 8h4l4 9M9 8l-2 5M13 8l1-3h2"/></svg>
                    </div>
                    <span class="tag">Rider &amp; Delivery</span>
                    <h3>ไรเดอร์ &amp; อีคอมเมิร์ซ</h3>
                    <p>ส่งไว ปลอดภัย ครอบคลุมทั่วไทยและเอเชีย ด้วยระบบจัดเส้นทางอัจฉริยะ</p>
                    <ul class="feats">
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12l4 4 10-10"/></svg> ติดตามแบบ real-time</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12l4 4 10-10"/></svg> ค่าส่งโปร่งใส</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12l4 4 10-10"/></svg> รองรับร้านค้านับพัน</li>
                    </ul>
                    <div class="arrow">เรียนรู้เพิ่มเติม
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </div>
                </article>

                {{-- 2. Fresh Market --}}
                <article class="service-card" style="--accent: linear-gradient(135deg,#22c55e,#0e7e3a); --accent-shadow: rgba(34,197,94,.45);">
                    <div class="ic">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l1.5-4h15L21 9M3 9v10h18V9M3 9h18M8 13v3M16 13v3M12 13v6"/></svg>
                    </div>
                    <span class="tag">Fresh Market</span>
                    <h3>ตลาดสดออนไลน์</h3>
                    <p>ของสดคุณภาพดี ราคายุติธรรม จากเกษตรกรและพ่อค้าแม่ค้าตัวจริง</p>
                    <ul class="feats">
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12l4 4 10-10"/></svg> ของสดวันต่อวัน</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12l4 4 10-10"/></svg> ราคาจากต้นทาง</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12l4 4 10-10"/></svg> ส่งถึงบ้าน</li>
                    </ul>
                    <div class="arrow">เรียนรู้เพิ่มเติม
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </div>
                </article>

                {{-- 3. AI Management --}}
                <article class="service-card" style="--accent: linear-gradient(135deg,#3d82ff,#0a3aac); --accent-shadow: rgba(11,76,219,.5);">
                    <div class="ic">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="6" width="12" height="12" rx="2"/><path d="M9 9v6M15 9v6M9 12h6M3 9h3M3 15h3M18 9h3M18 15h3M9 3v3M15 3v3M9 18v3M15 18v3"/></svg>
                    </div>
                    <span class="tag">AI Management</span>
                    <h3>จัดการด้วย AI</h3>
                    <p>AI ช่วยวิเคราะห์ยอดขาย วางแผนสต็อก แม่นยำ เพิ่มประสิทธิภาพร้านค้าของคุณ</p>
                    <ul class="feats">
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12l4 4 10-10"/></svg> Forecast ยอดขาย</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12l4 4 10-10"/></svg> จัดการสต็อกอัตโนมัติ</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12l4 4 10-10"/></svg> แชทบอทตอบลูกค้า</li>
                    </ul>
                    <div class="arrow">เรียนรู้เพิ่มเติม
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </div>
                </article>

                {{-- 4. Digital Wallet --}}
                <article class="service-card" style="--accent: linear-gradient(135deg,#f5b423,#a86c0d); --accent-shadow: rgba(245,180,35,.45);">
                    <div class="ic">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2v2H6a3 3 0 0 0 0 6h14v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"/><circle cx="16" cy="12" r="1.5"/></svg>
                    </div>
                    <span class="tag">Digital Wallet</span>
                    <h3>กระเป๋าเงินดิจิทัล</h3>
                    <p>จ่าย รับ โอน ค่าธรรมเนียมต่ำ ปลอดภัยด้วย encryption ระดับธนาคาร</p>
                    <ul class="feats">
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12l4 4 10-10"/></svg> โอนทันที 24/7</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12l4 4 10-10"/></svg> ค่าธรรมเนียมต่ำ</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12l4 4 10-10"/></svg> รองรับหลายสกุลเงิน</li>
                    </ul>
                    <div class="arrow">เรียนรู้เพิ่มเติม
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </div>
                </article>

                {{-- 5. Dividend System --}}
                <article class="service-card" style="--accent: linear-gradient(135deg,#a855f7,#5b21b6); --accent-shadow: rgba(168,85,247,.45);">
                    <div class="ic">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 16l8-8M9 9h.01M15 15h.01"/></svg>
                    </div>
                    <span class="tag">Dividend System</span>
                    <h3>ระบบปันผลโปร่งใส</h3>
                    <p>แบ่งผลประโยชน์อย่างเป็นธรรม ตรวจสอบได้ทุกธุรกรรม บนเชนของเราเอง</p>
                    <ul class="feats">
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12l4 4 10-10"/></svg> Smart contract ปันผลอัตโนมัติ</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12l4 4 10-10"/></svg> ตรวจสอบบนเชนได้</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12l4 4 10-10"/></svg> ทุกบาทมีที่มาที่ไป</li>
                    </ul>
                    <div class="arrow">เรียนรู้เพิ่มเติม
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </div>
                </article>

                {{-- 6. ThaiPrompt Chain --}}
                <article class="service-card" style="--accent: linear-gradient(135deg,#06b6d4,#0a3aac); --accent-shadow: rgba(6,182,212,.45);">
                    <div class="ic">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 13a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7L10.5 5.8"/><path d="M15 11a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7L13.5 18.2"/></svg>
                    </div>
                    <span class="tag">ThaiPrompt Chain</span>
                    <h3>Blockchain ของเราเอง</h3>
                    <p>เครือข่ายของคนไทย เพื่อคนไทย รองรับ smart contract และ payment ขนาดใหญ่</p>
                    <ul class="feats">
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12l4 4 10-10"/></svg> Public chain ของไทย</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12l4 4 10-10"/></svg> Throughput สูง ค่า gas ต่ำ</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12l4 4 10-10"/></svg> นักพัฒนาเข้าถึงง่าย</li>
                    </ul>
                    <div class="arrow">เรียนรู้เพิ่มเติม
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </div>
                </article>
            </div>
        </div>
    </section>

    {{-- ================================================================
        SLIDE 2 — Temple image (animated, fades into Wallet)
    ================================================================ --}}
    <section class="imgslide animate">
        <div class="imgslide-bg"
             style="background-image: url('{{ asset('images/landing/slide-temple.png') }}'); background-position: center 40%;"></div>
        <div class="imgslide-decor"><span></span><span></span><span></span><span></span><span></span></div>
    </section>

    {{-- ================================================================
        WALLET — Phone mockup + feature grid
    ================================================================ --}}
    <section id="wallet" class="tp-section wallet-section fade-bottom-dark">
        <div class="tp-container wallet-grid">
            <div>
                <div class="tp-eyebrow" style="color:#ffe28a">ThaiPrompt Wallet</div>
                <h2 class="wallet-h2">กระเป๋าเงินดิจิทัล <span class="gold">ปลอดภัย โปร่งใส</span></h2>
                <p class="wallet-text-p">
                    จัดการเงิน ปันผล และเหรียญ TPX ได้จากแอปเดียว
                    ทุกธุรกรรมบันทึกบน Blockchain ของเรา ตรวจสอบได้ทุกขั้นตอน
                    ค่าธรรมเนียมต่ำ ใช้ได้ทั่วเอเชีย
                </p>

                <div class="wallet-feat-grid">
                    <div class="wallet-feat">
                        <div class="ic">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/><path d="M9 12l2 2 4-4"/></svg>
                        </div>
                        <h4>เข้ารหัสระดับธนาคาร</h4>
                        <p>ระบบ multi-signature และ cold storage ปกป้องสินทรัพย์ของคุณ</p>
                    </div>
                    <div class="wallet-feat">
                        <div class="ic">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M13 3L4 14h7l-1 7 9-11h-7l1-7z"/></svg>
                        </div>
                        <h4>โอนทันที 24/7</h4>
                        <p>ยืนยันธุรกรรมในไม่กี่วินาที รองรับการใช้งานข้ามประเทศ</p>
                    </div>
                    <div class="wallet-feat">
                        <div class="ic">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 16l8-8M9 9h.01M15 15h.01"/></svg>
                        </div>
                        <h4>รับปันผลอัตโนมัติ</h4>
                        <p>Smart contract กระจายผลประโยชน์ทุกสัปดาห์ ไร้คนกลาง</p>
                    </div>
                    <div class="wallet-feat">
                        <div class="ic">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg>
                        </div>
                        <h4>ใช้ได้ทั่วเอเชีย</h4>
                        <p>รองรับหลายสกุลเงิน เชื่อมต่อร้านค้าและบริการในเครือข่าย</p>
                    </div>
                </div>

                <div style="display:flex; gap:12px; flex-wrap:wrap;">
                    <a href="{{ route('register') }}" class="tp-btn tp-btn-gold">
                        เปิดกระเป๋า TPX
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                    <a href="#" class="tp-btn tp-btn-outline-white">ดู Whitepaper</a>
                </div>
            </div>

            {{-- Phone mockup --}}
            <div class="phone-mock">
                <div class="phone-orbit"></div>
                <div class="coin c1">฿</div>
                <div class="coin c2">TP</div>
                <div class="coin c3">$</div>

                <div class="phone">
                    <div class="phone-notch"></div>
                    <div class="phone-screen">
                        <div class="phone-head">
                            <div class="h1">สวัสดี, คุณสมชาย</div>
                            <div class="h2">Live</div>
                        </div>

                        <div class="balance-card">
                            <div class="lbl">ยอดคงเหลือทั้งหมด</div>
                            <div class="val">฿ 128,540<small>.25</small></div>
                            <div class="pct">↑ +5.2% สัปดาห์นี้</div>
                        </div>

                        <div class="phone-actions">
                            <button>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                                ส่ง
                            </button>
                            <button>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
                                รับ
                            </button>
                            <button>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 7h12l-3-3M17 17H5l3 3"/></svg>
                                สลับ
                            </button>
                            <button>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3M20 14v7M14 20h7"/></svg>
                                สแกน
                            </button>
                        </div>

                        <div class="phone-tx">
                            <div class="phone-tx-item">
                                <div class="ic">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M8 16l8-8"/></svg>
                                </div>
                                <div class="meta"><b>ปันผลรายสัปดาห์</b><span>วันนี้ 09:00</span></div>
                                <div class="amt up">+฿ 1,840</div>
                            </div>
                            <div class="phone-tx-item">
                                <div class="ic">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 4h2l2.4 11.4a2 2 0 0 0 2 1.6h7.6"/><circle cx="9" cy="20" r="1.5"/></svg>
                                </div>
                                <div class="meta"><b>ตลาดสด รังสิต</b><span>เมื่อวาน 16:24</span></div>
                                <div class="amt dn">-฿ 285</div>
                            </div>
                            <div class="phone-tx-item">
                                <div class="ic">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M9 13a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7"/></svg>
                                </div>
                                <div class="meta"><b>Stake TPX</b><span>27 เม.ย. 2569</span></div>
                                <div class="amt up">+12.4 TPX</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================================================================
        HOW IT WORKS — 4-step onboarding
    ================================================================ --}}
    <section id="how" class="tp-section how fade-top-light fade-bottom-light">
        <div class="tp-container">
            <div class="sec-head">
                <div class="tp-eyebrow">วิธีใช้งาน</div>
                <h2>เริ่มต้น <span class="grad">ใน 4 ขั้นตอน</span></h2>
                <p>ออกแบบให้ทุกคนใช้งานได้ ไม่ว่าจะเป็นพ่อค้าแม่ค้า ไรเดอร์ ผู้บริโภค หรือนักพัฒนา</p>
            </div>

            <div class="how-track">
                <div class="how-step">
                    <div class="num">1</div>
                    <h4>สมัครสมาชิก</h4>
                    <p>สร้างบัญชีฟรีในไม่กี่นาที ยืนยันตัวตนตามมาตรฐาน KYC</p>
                </div>
                <div class="how-step">
                    <div class="num">2</div>
                    <h4>เชื่อมต่อบริการ</h4>
                    <p>เลือกใช้ Rider, ตลาดสด, Wallet หรือเป็นพาร์ทเนอร์ร้านค้า</p>
                </div>
                <div class="how-step">
                    <div class="num">3</div>
                    <h4>เริ่มใช้งานทันที</h4>
                    <p>ซื้อ ขาย โอน ส่งของ จัดการร้าน ผ่านแพลตฟอร์มเดียว</p>
                </div>
                <div class="how-step">
                    <div class="num">4</div>
                    <h4>รับปันผล &amp; เติบโต</h4>
                    <p>ส่วนแบ่งจากเครือข่าย กระจายอัตโนมัติบนเชน ทุกสัปดาห์</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ================================================================
        ⭐ PRESENTATION SLIDES SECTION (สื่อการเรียนรู้ระบบ)
        ส่วนที่ธีมไม่มี — เพิ่มเข้ามาตามคำขอของผู้ใช้
        แสดงการ์ด 9 ใบเปิด modal สไลด์นำเสนอ
    ================================================================ --}}
    <section id="demo" class="tp-section demo-section fade-top-light fade-bottom-light">
        <div class="tp-container">
            <div class="sec-head">
                <div class="tp-eyebrow">สื่อการเรียนรู้ระบบ</div>
                <h2>เรียนรู้ <span class="grad">{{ $appName }}</span></h2>
                <p>สไลด์นำเสนอระบบแบบครบถ้วน สวยงาม มืออาชีพ พร้อมใช้งานทันที — เหมาะสำหรับนักลงทุน ผู้อยากร่วม และผู้สนใจทุกท่าน</p>
            </div>

            <div class="demo-grid">
                {{-- การ์ด 1: ภาพรวมระบบ --}}
                <div class="demo-card" onclick="if(typeof openPresentationWithTopic === 'function') openPresentationWithTopic('system-overview')">
                    <div class="ic" style="background: linear-gradient(135deg, #3d82ff, #0a3aac);">
                        <i class="fas fa-desktop"></i>
                    </div>
                    <h3>ภาพรวมระบบ</h3>
                    <p>เรียนรู้ระบบทั้งหมดใน {{ $appName }} รวมถึง Affiliate, MLM, E-Commerce และระบบอื่นๆ</p>
                    <span class="arrow">ดูสไลด์ <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                </div>

                {{-- การ์ด 2: แผน MLM --}}
                <div class="demo-card" onclick="if(typeof openPresentationWithTopic === 'function') openPresentationWithTopic('mlm-plans')">
                    <div class="ic" style="background: linear-gradient(135deg, #a855f7, #5b21b6);">
                        <i class="fas fa-sitemap"></i>
                    </div>
                    <h3>แผนการตลาด MLM</h3>
                    <p>เรียนรู้แผน Unilevel, Binary และระบบคอมมิชชั่นหลายชั้นที่ยืดหยุ่น</p>
                    <span class="arrow">ดูสไลด์ <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                </div>

                {{-- การ์ด 3: TPIX Token --}}
                <div class="demo-card" onclick="if(typeof openPresentationWithTopic === 'function') openPresentationWithTopic('tpix-token')">
                    <div class="ic" style="background: linear-gradient(135deg, #f5b423, #a86c0d); overflow:hidden; padding:6px;">
                        <img src="{{ asset('images/tpix-logo.svg') }}" alt="TPIX" style="width:100%; height:100%; object-fit:contain; filter:brightness(0) invert(1);">
                    </div>
                    <h3>TPIX Token</h3>
                    <p>เรียนรู้ TPIX Token ที่มี Blockchain ของตัวเอง Staking, DEX และ Tokenomics</p>
                    <span class="arrow">ดูสไลด์ <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                </div>

                {{-- การ์ด 4: AI & Automation --}}
                <div class="demo-card" onclick="if(typeof openPresentationWithTopic === 'function') openPresentationWithTopic('ai-automation')">
                    <div class="ic" style="background: linear-gradient(135deg, #22c55e, #0e7e3a);">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3>AI &amp; Automation</h3>
                    <p>เรียนรู้ระบบ AI Chatbot, LINE Bot และระบบอัตโนมัติที่ช่วยประหยัดเวลา</p>
                    <span class="arrow">ดูสไลด์ <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                </div>

                {{-- การ์ด 5: Academy & ปลดแอกธุรกิจ --}}
                <div class="demo-card" onclick="if(typeof openPresentationWithTopic === 'function') openPresentationWithTopic('academy-freedom')">
                    <div class="ic" style="background: linear-gradient(135deg, #f43f5e, #d04b00);">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Academy &amp; ปลดแอกธุรกิจ</h3>
                    <p>เรียนรู้ระบบ Academy ออนไลน์และอิสรภาพจากแพลตฟอร์มต่างชาติ</p>
                    <span class="arrow">ดูสไลด์ <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                </div>

                {{-- การ์ด 6: Ecosystem & Vision --}}
                <div class="demo-card" onclick="if(typeof openPresentationWithTopic === 'function') openPresentationWithTopic('ecosystem-vision')">
                    <div class="ic" style="background: linear-gradient(135deg, #6366f1, #5b21b6);">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h3>Ecosystem &amp; Vision</h3>
                    <p>ระบบนิเวศครบวงจรและ Roadmap การพัฒนา 2025-2027</p>
                    <span class="arrow">ดูสไลด์ <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                </div>

                {{-- การ์ด 7: พลิกเกมส์ธุรกิจ --}}
                <div class="demo-card" onclick="if(typeof openPresentationWithTopic === 'function') openPresentationWithTopic('game-changer')">
                    <div class="ic" style="background: linear-gradient(135deg, #f5b423, #d97706);">
                        <i class="fas fa-chess-king"></i>
                    </div>
                    <h3>พลิกเกมส์ธุรกิจ</h3>
                    <p>โอกาสการลงทุนที่แตกต่าง หลากหลายช่องทางรายได้</p>
                    <span class="arrow">ดูสไลด์ <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                </div>

                {{-- การ์ด 8: E-Commerce Empire --}}
                <div class="demo-card" onclick="if(typeof openPresentationWithTopic === 'function') openPresentationWithTopic('ecommerce-empire')">
                    <div class="ic" style="background: linear-gradient(135deg, #06b6d4, #0a3aac);">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <h3>E-Commerce Empire</h3>
                    <p>ระบบ E-Commerce ครบวงจร จาก Marketplace ถึง Food Passport</p>
                    <span class="arrow">ดูสไลด์ <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                </div>

                {{-- การ์ด 9: Community & Partnership --}}
                <div class="demo-card" onclick="if(typeof openPresentationWithTopic === 'function') openPresentationWithTopic('community')">
                    <div class="ic" style="background: linear-gradient(135deg, #ec4899, #be123c);">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Community &amp; Partnership</h3>
                    <p>ชุมชนผู้ใช้ พันธมิตรธุรกิจ และบริการสนับสนุน</p>
                    <span class="arrow">ดูสไลด์ <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                </div>
            </div>

            {{-- ปุ่มเปิดสไลด์เต็มจอ --}}
            <div style="text-align:center; margin-top: 48px;">
                <button onclick="if(typeof openPresentationFullscreen === 'function') openPresentationFullscreen()"
                        class="tp-btn tp-btn-gold" style="font-size:16px; padding: 16px 32px;">
                    <i class="fas fa-play-circle" style="font-size:18px;"></i>
                    เปิดสไลด์นำเสนอเต็มจอ
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </button>
            </div>
        </div>
    </section>

    {{-- รวม modal และ scripts สำหรับสไลด์นำเสนอ --}}
    @include('components.presentation-slides-modal')

    {{-- ================================================================
        SLIDE 3 — Flow image (animated, fades into Asia)
    ================================================================ --}}
    <section class="imgslide animate fade-light">
        <div class="imgslide-bg"
             style="background-image: url('{{ asset('images/landing/slide-flow.png') }}'); background-position: center 35%;"></div>
        <div class="imgslide-decor"><span></span><span></span><span></span><span></span><span></span></div>
    </section>

    {{-- ================================================================
        ASIA — Network map + flag pills
    ================================================================ --}}
    <section id="asia" class="tp-section asia fade-top-light fade-bottom-light">
        <div class="tp-container asia-grid">
            <div>
                <div class="tp-eyebrow">เครือข่ายเอเชีย</div>
                <h2 style="font-family:Prompt; font-size:clamp(28px,3.5vw,42px); font-weight:800; letter-spacing:-.02em; line-height:1.1; margin:12px 0 16px; color:var(--ink);">
                    เพื่อคนไทย <span class="grad">เพื่อเอเชีย</span><br>เพื่ออนาคตที่ดีกว่า
                </h2>
                <p style="color:var(--ink-3); font-size:17px; line-height:1.7; margin:0;">
                    เริ่มจากประเทศไทย ขยายต่อเนื่องไปยัง 10+ ประเทศในเอเชีย
                    สร้างเศรษฐกิจดิจิทัลร่วมกัน ที่ทุกคนได้ประโยชน์อย่างเป็นธรรม
                </p>

                <div class="asia-flags">
                    <span class="flag-pill"><span class="em">🇹🇭</span> ไทย</span>
                    <span class="flag-pill"><span class="em">🇻🇳</span> เวียดนาม</span>
                    <span class="flag-pill"><span class="em">🇮🇩</span> อินโดนีเซีย</span>
                    <span class="flag-pill"><span class="em">🇵🇭</span> ฟิลิปปินส์</span>
                    <span class="flag-pill"><span class="em">🇲🇾</span> มาเลเซีย</span>
                    <span class="flag-pill"><span class="em">🇸🇬</span> สิงคโปร์</span>
                    <span class="flag-pill"><span class="em">🇱🇦</span> ลาว</span>
                    <span class="flag-pill"><span class="em">🇲🇲</span> เมียนมา</span>
                    <span class="flag-pill"><span class="em">🇰🇭</span> กัมพูชา</span>
                    <span class="flag-pill"><span class="em">🇨🇳</span> จีน</span>
                    <span class="flag-pill"><span class="em">🇰🇷</span> เกาหลี</span>
                    <span class="flag-pill"><span class="em">🇯🇵</span> ญี่ปุ่น</span>
                </div>

                <div style="display:flex; gap:12px;">
                    <a href="{{ route('contact') }}" class="tp-btn tp-btn-primary">
                        เป็นพาร์ทเนอร์กับเรา
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                </div>
            </div>

            <div class="asia-map">
                {{-- แผนที่จริงด้วย Leaflet + Carto Voyager tile (init ใน @push('scripts')) --}}
                <div id="asia-real-map" style="width:100%; height:100%; border-radius:28px;"></div>
            </div>
        </div>
    </section>

    {{-- ================================================================
        CTA — Final call to action with newsletter form
    ================================================================ --}}
    <section id="contact" style="padding: 48px 0 96px; background: #fafbff;">
        <div class="tp-container">
            <div class="cta-strip">
                <div>
                    <div class="tp-eyebrow" style="color:#ffe28a">โหลดเลย — ฟรี</div>
                    <h2 style="margin-top:12px; color:#fff;">เริ่มต้น <span class="gold">อนาคตที่ดีกว่า</span><br>กับ {{ $appName }} วันนี้</h2>
                    <p>โปร่งใส · ยุติธรรม · เติบโตไปด้วยกัน — แพลตฟอร์มเดียวที่รวมทุกบริการ พร้อมระบบปันผลที่ตรวจสอบได้บน Blockchain ของเราเอง</p>
                    <div style="display:flex; gap:12px; flex-wrap:wrap;">
                        <a href="{{ route('register') }}" class="tp-btn tp-btn-gold">
                            ดาวน์โหลดแอป
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </a>
                        <a href="{{ route('contact') }}" class="tp-btn tp-btn-outline-white">เป็นร้านค้าพาร์ทเนอร์</a>
                    </div>
                </div>

                {{-- ฟอร์ม subscribe (ใช้ Alpine จัดการ state) --}}
                <form class="cta-form" x-data="{ email: '', sent: false }" @submit.prevent="sent = true">
                    <label>รับข่าวสารและสิทธิพิเศษก่อนใคร</label>
                    <div class="cta-form-row">
                        <input type="email" placeholder="กรอกอีเมลของคุณ" x-model="email" required>
                        <button class="tp-btn tp-btn-gold" type="submit" x-text="sent ? 'รับแล้ว ✓' : 'สมัครรับข่าว'"></button>
                    </div>
                    <div class="hint">เราเคารพความเป็นส่วนตัว ยกเลิกได้ทุกเมื่อ</div>
                </form>
            </div>
        </div>
    </section>

    {{-- ================================================================
        FOOTER — Theme footer (4 columns + social + bottom bar)
    ================================================================ --}}
    <footer class="tp-footer">
        <div class="tp-container">
            <div class="footer-grid">
                {{-- คอลัมน์ 1: Brand --}}
                <div>
                    <div class="footer-brand">
                        <div class="footer-brand-mark">TP</div>
                        <div style="display:flex; flex-direction:column; line-height:1;">
                            <strong style="color:#fff; font-family:Prompt; font-weight:800; font-size:17px;">
                                ThaiPrompt <span style="color:var(--gold-400)">ไทยพร๊อมท์</span>
                            </strong>
                            <span style="color:rgba(255,255,255,.5); font-size:11px; margin-top:3px;">POS · Blockchain · AI Management</span>
                        </div>
                    </div>
                    <p style="font-size:14px; color:rgba(255,255,255,.55); line-height:1.7; margin:0; max-width:360px;">
                        แพลตฟอร์มคนไทย เพื่อคนไทยและเอเชีย โปร่งใส ยุติธรรม เติบโตไปด้วยกัน อย่างยั่งยืน
                    </p>
                    <div class="footer-social" style="margin-top:20px;">
                        <a href="#" aria-label="Facebook"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M9 8h-3v4h3v12h5V12h3.6l.4-4H14V6.3c0-1 .2-1.3 1.2-1.3H18V0h-3.8C10.6 0 9 1.6 9 4.6V8z"/></svg></a>
                        <a href="#" aria-label="LINE"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19.4 10.5c0-3.4-3.4-6.1-7.5-6.1S4.4 7.1 4.4 10.5c0 3 2.7 5.6 6.4 6 .3 0 .6.2.7.5.1.2 0 .6 0 .8 0 0-.1.7-.1.8-.1.2-.2 1 .9.6 1-.4 5.6-3.3 7.7-5.7 1.4-1.6 2-3.3 2-5z"/></svg></a>
                        <a href="#" aria-label="X"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18.9 2H22l-7.5 8.6L23.3 22h-7l-5.5-7.2L4.5 22H1.4l8-9.2L1 2h7.2l5 6.6L18.9 2zm-1.2 18h1.9L7.4 4H5.4l12.3 16z"/></svg></a>
                        <a href="#" aria-label="YouTube"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M23 7.5a3 3 0 0 0-2-2C19 5 12 5 12 5s-7 0-9 .5a3 3 0 0 0-2 2C.5 9.5.5 12 .5 12s0 2.5.5 4.5a3 3 0 0 0 2 2c2 .5 9 .5 9 .5s7 0 9-.5a3 3 0 0 0 2-2c.5-2 .5-4.5.5-4.5s0-2.5-.5-4.5zM10 16V8l6 4-6 4z"/></svg></a>
                    </div>
                </div>

                {{-- คอลัมน์ 2: บริการ --}}
                <div>
                    <h5>บริการ</h5>
                    <ul>
                        <li><a href="#services">Rider &amp; Delivery</a></li>
                        <li><a href="#services">ตลาดสด</a></li>
                        <li><a href="#services">AI Management</a></li>
                        <li><a href="#wallet">ThaiPrompt Wallet</a></li>
                        <li><a href="#services">ระบบปันผล</a></li>
                    </ul>
                </div>

                {{-- คอลัมน์ 3: นักพัฒนา --}}
                <div>
                    <h5>นักพัฒนา</h5>
                    <ul>
                        <li><a href="#">Whitepaper</a></li>
                        <li><a href="#">API Documentation</a></li>
                        <li><a href="#">Block Explorer</a></li>
                        <li><a href="#">Smart Contract</a></li>
                    </ul>
                </div>

                {{-- คอลัมน์ 4: เกี่ยวกับเรา --}}
                <div>
                    <h5>เกี่ยวกับเรา</h5>
                    <ul>
                        <li><a href="{{ route('about') }}">เกี่ยวกับ {{ $appName }}</a></li>
                        <li><a href="#">ร่วมงานกับเรา</a></li>
                        <li><a href="{{ route('contact') }}">ติดต่อ</a></li>
                        <li><a href="#">ข้อตกลงการใช้งาน</a></li>
                        <li><a href="#">นโยบายความเป็นส่วนตัว</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <span>© {{ date('Y') }} {{ $appName }}. ไทยพร๊อมท์ — แพลตฟอร์มคนไทย เพื่อคนไทยและเอเชีย · v{{ $version }}</span>
                <span>www.thaiprompt.online</span>
            </div>
        </div>
    </footer>

</div>{{-- /.tp-landing --}}

@endsection

@push('scripts')
{{-- Leaflet JS สำหรับแผนที่จริง --}}
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""></script>
<script>
    /**
     * Reveal-on-scroll observer + Leaflet Asia real map init
     */
    document.addEventListener('DOMContentLoaded', function() {
        // ===== Reveal observer =====
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((e) => {
                if (e.isIntersecting) e.target.classList.add('in');
            });
        }, { threshold: 0.1 });
        document.querySelectorAll('.tp-landing .reveal').forEach(el => observer.observe(el));

        // ===== Asia real map (Leaflet) =====
        const mapEl = document.getElementById('asia-real-map');
        if (!mapEl || typeof L === 'undefined') return;

        // เริ่มต้นที่กึ่งกลางเอเชีย (เห็น TH, JP, ID, KR, CN ในเฟรมเดียว)
        const map = L.map('asia-real-map', {
            center: [18, 108],
            zoom: 3,
            minZoom: 2,
            maxZoom: 7,
            zoomControl: true,
            scrollWheelZoom: false,  // กันไม่ให้ดูดการ scroll ของหน้าเว็บ
            attributionControl: true,
        });

        // Carto Voyager — สีฟ้า/เทาดูสะอาด ตรงโทนธีม
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://carto.com/attributions">CARTO</a> &copy; <a href="https://www.openstreetmap.org/copyright">OSM</a>',
            subdomains: 'abcd',
            maxZoom: 19
        }).addTo(map);

        // 12 เมืองหลวงพาร์ทเนอร์ (ไทยเป็น home — หมุดทอง)
        const cities = [
            { lat: 13.7563, lng: 100.5018, name: 'กรุงเทพ',      flag: '🇹🇭', home: true },
            { lat: 21.0285, lng: 105.8542, name: 'ฮานอย',       flag: '🇻🇳' },
            { lat: -6.2088, lng: 106.8456, name: 'จาการ์ตา',     flag: '🇮🇩' },
            { lat: 14.5995, lng: 120.9842, name: 'มะนิลา',      flag: '🇵🇭' },
            { lat: 3.1390,  lng: 101.6869, name: 'กัวลาลัมเปอร์', flag: '🇲🇾' },
            { lat: 1.3521,  lng: 103.8198, name: 'สิงคโปร์',     flag: '🇸🇬' },
            { lat: 17.9757, lng: 102.6331, name: 'เวียงจันทน์',   flag: '🇱🇦' },
            { lat: 16.8661, lng: 96.1951,  name: 'ย่างกุ้ง',     flag: '🇲🇲' },
            { lat: 11.5564, lng: 104.9282, name: 'พนมเปญ',      flag: '🇰🇭' },
            { lat: 39.9042, lng: 116.4074, name: 'ปักกิ่ง',      flag: '🇨🇳' },
            { lat: 37.5665, lng: 126.9780, name: 'โซล',         flag: '🇰🇷' },
            { lat: 35.6762, lng: 139.6503, name: 'โตเกียว',     flag: '🇯🇵' },
        ];

        // คำนวณจุดบนเส้นโค้ง Bezier (quadratic) ระหว่าง 2 พิกัด
        function arcPoints(from, to, height = 8, segments = 30) {
            const midLat = (from[0] + to[0]) / 2 + height;
            const midLng = (from[1] + to[1]) / 2;
            const pts = [];
            for (let i = 0; i <= segments; i++) {
                const t = i / segments;
                const lat = (1 - t) * (1 - t) * from[0] + 2 * (1 - t) * t * midLat + t * t * to[0];
                const lng = (1 - t) * (1 - t) * from[1] + 2 * (1 - t) * t * midLng + t * t * to[1];
                pts.push([lat, lng]);
            }
            return pts;
        }

        const bangkok = [13.7563, 100.5018];

        // วาดเส้นโค้งทองจากกรุงเทพไปทุกเมือง
        cities.forEach((c) => {
            if (c.home) return;
            const pts = arcPoints(bangkok, [c.lat, c.lng], 6);
            L.polyline(pts, {
                color: '#f5b423',
                weight: 1.5,
                opacity: 0.65,
                dashArray: '4, 6',
                smoothFactor: 1
            }).addTo(map);
        });

        // ปักหมุดทุกเมือง (ไทย=ทอง, อื่น=น้ำเงิน) พร้อม tooltip
        cities.forEach((c) => {
            const cls = c.home ? 'tp-leaflet-pin tp-leaflet-pin-gold' : 'tp-leaflet-pin';
            const size = c.home ? 18 : 14;
            const icon = L.divIcon({
                html: `<div class="${cls}"></div>`,
                className: 'tp-leaflet-icon',
                iconSize: [size, size],
                iconAnchor: [size / 2, size / 2],
            });
            L.marker([c.lat, c.lng], { icon }).addTo(map)
                .bindTooltip(`${c.flag} ${c.name}`, {
                    direction: 'top',
                    offset: [0, -size / 2 - 2],
                    permanent: false,
                });
        });
    });
</script>
@endpush
