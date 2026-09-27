{{--
 | 503 ปิดปรับปรุงชั่วคราว — ธีมโนวา (หน้าเต็ม ไม่มี layout)
 | ข้อมูล: SiteSetting (ข้อความ/ช่องทางติดต่อ/โซเชียล) + AppMaintenance (หัวข้อ ข้อความ เวลานับถอยหลัง)
 | ⚠️ หน้านี้ขึ้นตอน deploy/ฐานข้อมูลมีปัญหาได้ → ห่อการอ่าน DB ด้วย try/catch ทั้งหมด พังแล้วต้องยังแสดงหน้าได้
 | ⚠️ นับถอยหลังครบแล้วยังปิดอยู่ → ห้าม reload ทุกวินาที (เดิมยิงเซิร์ฟเวอร์วนไม่หยุด) → ลองใหม่ทุก 30 วินาที
 --}}
@php
    $nvBrand = config('app.brand_name', 'Thai Prompt');
    $nvCssVer = is_file(public_path('theme-nova/nova.css')) ? filemtime(public_path('theme-nova/nova.css')) : 1;

    $siteSettings = null;
    try {
        $siteSettings = \App\Models\SiteSetting::getSetting();
    } catch (\Throwable $e) {
        $siteSettings = null;
    }

    $appMaintenance = null;
    try {
        if (class_exists(\App\Models\AppMaintenance::class)) {
            $appMaintenance = \App\Models\AppMaintenance::getInstance();
        }
    } catch (\Throwable $e) {
        $appMaintenance = null;
    }

    $mtSubtitle = ($appMaintenance && $appMaintenance->title) ? $appMaintenance->title : 'กรุณารอสักครู่ เรากำลังปรับปรุงระบบให้ดียิ่งขึ้น';
    $mtMessage = ($appMaintenance && $appMaintenance->message) ? $appMaintenance->message : ($siteSettings->maintenance_message ?? null);
    $mtEnd = ($appMaintenance && $appMaintenance->show_countdown && $appMaintenance->scheduled_end && $appMaintenance->scheduled_end->isFuture())
        ? $appMaintenance->scheduled_end
        : null;

    $mtSocials = array_filter([
        ['url' => $siteSettings->facebook_url ?? null, 'icon' => 'fa-facebook-f', 'label' => 'Facebook'],
        ['url' => $siteSettings->line_url ?? null, 'icon' => 'fa-line', 'label' => 'LINE'],
        ['url' => $siteSettings->youtube_url ?? null, 'icon' => 'fa-youtube', 'label' => 'YouTube'],
        ['url' => $siteSettings->instagram_url ?? null, 'icon' => 'fa-instagram', 'label' => 'Instagram'],
        ['url' => $siteSettings->twitter_url ?? null, 'icon' => 'fa-x-twitter', 'label' => 'X'],
    ], fn ($s) => ! empty($s['url']));
    $mtEmail = $siteSettings->contact_email ?? null;
    $mtPhone = $siteSettings->contact_phone ?? null;
    $mtSiteName = $siteSettings->site_name ?? $nvBrand;
@endphp
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#0d1b3d">
    <title>ระบบปิดปรับปรุงชั่วคราว | {{ $nvBrand }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/brand/favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@400;500;600;700&family=Trirong:wght@600;700&family=Cinzel:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="{{ asset('theme-nova/nova.css') }}?v={{ $nvCssVer }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body { font-family: var(--nv-font-ui); color: var(--nv-on-night); background: #060b1c; }
        .mt { position: relative; isolation: isolate; overflow: hidden; min-height: 100svh; display: grid; grid-template-columns: minmax(0, 1fr); place-items: center; padding: 104px 20px 56px; }
        .mt__logo { position: absolute; top: 22px; left: 50%; transform: translateX(-50%); z-index: 2; }
        .mt__logo img { height: 42px; width: auto; display: block; }
        .mt__kanok { position: absolute; width: clamp(160px, 24vw, 320px); opacity: .22; pointer-events: none; top: 70px; }
        .mt__kanok--l { left: -8px; transform: scaleX(-1); }
        .mt__kanok--r { right: -8px; }
        .mt__card { position: relative; z-index: 1; width: min(620px, 100%); padding: 40px 32px 30px; border-radius: 30px; text-align: center; background: linear-gradient(160deg, rgba(20, 36, 84, .8), rgba(7, 13, 32, .9)); border: 1px solid rgba(245, 210, 127, .26); box-shadow: 0 40px 90px -36px rgba(0, 0, 0, .9), inset 0 1px 0 rgba(255, 255, 255, .06); }
        .mt__deco { width: 132px; height: auto; margin: -112px auto 4px; display: block; filter: drop-shadow(0 22px 26px rgba(0, 0, 0, .55)); animation: nv-dfloat 7s ease-in-out infinite; --r0: -4deg; --r1: 3deg; }
        .mt__code { margin: 0; font-family: var(--nv-font-latin); font-weight: 700; font-size: clamp(56px, 12vw, 88px); line-height: 1; letter-spacing: .06em; }
        .mt__title { margin: 8px 0 8px; font-family: var(--nv-font-display); font-size: clamp(24px, 4.6vw, 34px); line-height: 1.3; color: #fbf6ea; }
        .mt__sub { margin: 0 auto 18px; max-width: 460px; color: var(--nv-on-night-2); line-height: 1.7; }
        .mt__note { margin: 0 auto 20px; max-width: 480px; padding: 12px 16px; border-radius: 16px; text-align: left; display: flex; gap: 10px; align-items: flex-start; background: rgba(255, 255, 255, .04); border: 1px solid rgba(245, 210, 127, .2); color: rgba(246, 239, 221, .85); line-height: 1.65; }
        .mt__note i { color: #f0c96a; margin-top: 4px; }
        .mt__bar { max-width: 360px; height: 8px; margin: 0 auto 8px; border-radius: 999px; overflow: hidden; background: rgba(255, 255, 255, .08); border: 1px solid rgba(245, 210, 127, .16); }
        .mt__bar i { display: block; height: 100%; width: 40%; border-radius: inherit; background: linear-gradient(90deg, rgba(240, 201, 106, 0), #f0c96a, #fbe3a8, rgba(240, 201, 106, 0)); animation: mt-slide 2.2s ease-in-out infinite; }
        @keyframes mt-slide { 0% { transform: translateX(-110%); } 100% { transform: translateX(260%); } }
        .mt__bar-t { margin: 0 0 22px; font-size: 13px; color: rgba(246, 239, 221, .55); }
        .mt__sec { margin: 0 auto 20px; padding-top: 18px; border-top: 1px solid rgba(245, 210, 127, .14); }
        .mt__h { margin: 0 0 12px; font-size: 14px; letter-spacing: .04em; color: #f0c96a; font-weight: 600; }
        .mt__cd { display: flex; justify-content: center; gap: 10px; }
        .mt__cd div { min-width: 70px; padding: 10px 6px; border-radius: 16px; background: rgba(255, 255, 255, .05); border: 1px solid rgba(245, 210, 127, .22); }
        .mt__cd b { display: block; font-family: var(--nv-font-latin); font-size: 28px; line-height: 1.1; color: #fbf6ea; font-variant-numeric: tabular-nums; }
        .mt__cd span { font-size: 12px; color: rgba(246, 239, 221, .6); }
        .mt__social { display: flex; justify-content: center; flex-wrap: wrap; gap: 10px; }
        .mt__social a { width: 46px; height: 46px; border-radius: 50%; display: grid; place-items: center; color: #1a1405; font-size: 19px; text-decoration: none; background: linear-gradient(180deg, #fbe3a8, #d4a64a); box-shadow: inset 0 1px 0 rgba(255, 255, 255, .7), 0 10px 22px -12px rgba(240, 201, 106, .8); transition: transform .2s; }
        .mt__social a:hover { transform: translateY(-2px); }
        .mt__contact { display: flex; justify-content: center; flex-wrap: wrap; gap: 10px; }
        .mt__contact a { display: inline-flex; align-items: center; gap: 8px; padding: 9px 16px; border-radius: 999px; color: #fbf6ea; text-decoration: none; background: rgba(255, 255, 255, .05); border: 1px solid rgba(245, 210, 127, .22); }
        .mt__contact a i { color: #f0c96a; }
        .mt__foot { margin: 22px 0 0; font-size: 12px; color: rgba(246, 239, 221, .45); }
        @media (max-width: 520px) { .mt__card { padding: 32px 18px 24px; } .mt__deco { width: 104px; margin-top: -88px; } .mt__cd div { min-width: 60px; } .mt__cd b { font-size: 23px; } }
        @media (prefers-reduced-motion: reduce) { .mt__deco, .mt__bar i { animation: none; } }
    </style>
</head>
<body>
<main class="mt">
    <div class="nv-bg" aria-hidden="true">
        <div class="nv-sky"></div>
        <div class="nv-aurora"><i class="a1"></i><i class="a2"></i><i class="a3"></i></div>
        <div class="nv-temple"></div>
        <div class="nv-vignette"></div>
    </div>
    <img class="mt__kanok mt__kanok--l" src="{{ asset('images/nova/brand/kanok-gold.webp') }}" alt="" aria-hidden="true">
    <img class="mt__kanok mt__kanok--r" src="{{ asset('images/nova/brand/kanok-gold.webp') }}" alt="" aria-hidden="true">
    <span class="mt__logo">
        <picture>
            <source srcset="{{ asset('images/brand/thaiprompt-logo-dark.webp') }}" type="image/webp">
            <img src="{{ asset('images/brand/thaiprompt-logo-dark.png') }}" alt="{{ $nvBrand }}" width="192" height="56">
        </picture>
    </span>

    <section class="mt__card" aria-labelledby="mt-title">
        <img class="mt__deco" src="{{ asset('images/nova/deco/lantern.webp') }}" alt="" aria-hidden="true">
        <p class="mt__code"><span class="nv-foil">503</span></p>
        <h1 class="mt__title" id="mt-title">ระบบปิดปรับปรุงชั่วคราว</h1>
        <p class="mt__sub">{{ $mtSubtitle }}</p>

        @if($mtMessage)
            <p class="mt__note"><i class="fas fa-circle-info" aria-hidden="true"></i><span>{{ $mtMessage }}</span></p>
        @endif

        <div class="mt__bar" aria-hidden="true"><i></i></div>
        <p class="mt__bar-t">กำลังดำเนินการ...</p>

        @if($mtEnd)
            <div class="mt__sec">
                <p class="mt__h"><i class="fas fa-clock" aria-hidden="true"></i> คาดว่าจะกลับมาในอีก</p>
                <div class="mt__cd" id="mt-cd" data-end="{{ $mtEnd->toISOString() }}" role="timer" aria-live="off">
                    <div><b data-u="d">00</b><span>วัน</span></div>
                    <div><b data-u="h">00</b><span>ชั่วโมง</span></div>
                    <div><b data-u="m">00</b><span>นาที</span></div>
                    <div><b data-u="s">00</b><span>วินาที</span></div>
                </div>
            </div>
        @endif

        @if(count($mtSocials))
            <div class="mt__sec">
                <p class="mt__h">ติดตามข่าวสารได้ที่</p>
                <div class="mt__social">
                    @foreach($mtSocials as $s)
                        <a href="{{ $s['url'] }}" target="_blank" rel="noopener" aria-label="{{ $s['label'] }}"><i class="fab {{ $s['icon'] }}" aria-hidden="true"></i></a>
                    @endforeach
                </div>
            </div>
        @endif

        @if($mtEmail || $mtPhone)
            <div class="mt__sec">
                <p class="mt__h">ติดต่อทีมงาน</p>
                <div class="mt__contact">
                    @if($mtEmail)<a href="mailto:{{ $mtEmail }}"><i class="fas fa-envelope" aria-hidden="true"></i>{{ $mtEmail }}</a>@endif
                    @if($mtPhone)<a href="tel:{{ $mtPhone }}"><i class="fas fa-phone" aria-hidden="true"></i>{{ $mtPhone }}</a>@endif
                </div>
            </div>
        @endif

        <button type="button" class="nv-btn nv-btn--gold nv-btn--lg" onclick="location.reload()"><i class="fas fa-rotate-right" aria-hidden="true"></i> ลองอีกครั้ง</button>
        <p class="mt__foot">© {{ date('Y') }} {{ $mtSiteName }} · ขออภัยในความไม่สะดวก เราจะกลับมาให้บริการเร็วที่สุด</p>
    </section>
</main>

@if($mtEnd)
<script>
    // นับถอยหลังถึงเวลาที่คาดว่าจะเปิด (ไม่พึ่ง Alpine/CDN)
    (function () {
        var box = document.getElementById('mt-cd');
        if (!box) return;
        var end = new Date(box.getAttribute('data-end')).getTime();
        var el = {};
        ['d', 'h', 'm', 's'].forEach(function (u) { el[u] = box.querySelector('[data-u="' + u + '"]'); });
        var pad = function (n) { return String(n).padStart(2, '0'); };
        var timer = null, done = false;
        function tick() {
            if (done) return;
            var diff = end - Date.now();
            if (diff <= 0) {
                done = true;
                // ครบเวลาแล้ว → หยุดนับ แล้วลองโหลดใหม่ทุก 30 วินาที (ถ้ายังปิดอยู่ หน้านี้จะนับรอบใหม่ไม่ได้ จึงไม่วนถี่)
                clearInterval(timer);
                ['d', 'h', 'm', 's'].forEach(function (u) { el[u].textContent = '00'; });
                setTimeout(function () { location.reload(); }, 30000);
                return;
            }
            el.d.textContent = pad(Math.floor(diff / 86400000));
            el.h.textContent = pad(Math.floor(diff % 86400000 / 3600000));
            el.m.textContent = pad(Math.floor(diff % 3600000 / 60000));
            el.s.textContent = pad(Math.floor(diff % 60000 / 1000));
        }
        tick();
        timer = setInterval(tick, 1000);
    })();
</script>
@endif
</body>
</html>
